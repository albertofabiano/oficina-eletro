<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\InfinitePayService;

class DiretorioAnunciosController extends Controller
{
    public function index(): void
    {
        $db  = DB::pdo();
        $eid = $this->empresaId();

        $planos = $db->query("SELECT * FROM diretorio_planos WHERE ativo=1 ORDER BY tipo, preco")->fetchAll();

        $assinaturas = $db->prepare("
            SELECT a.*, p.nome AS plano_nome, p.tipo AS plano_tipo, p.posicao_banner,
                   b.aprovado AS banner_aprovado, b.imagem AS banner_imagem, b.id AS banner_id
            FROM diretorio_assinaturas a
            JOIN diretorio_planos p ON p.id = a.plano_id
            LEFT JOIN diretorio_banners b ON b.assinatura_id = a.id
            WHERE a.empresa_id = ?
            ORDER BY a.criado_em DESC
        ");
        $assinaturas->execute([$eid]);
        $assinaturas = $assinaturas->fetchAll();

        $this->view('empresa.diretorio_anuncios', compact('planos','assinaturas'), 'main');
    }

    /**
     * Contratar/renovar um plano de anúncio (destaque ou banner) — mesmo padrão de
     * PagamentoController::assinar(): gera um link de checkout InfinitePay e manda o cliente
     * pra lá. Liberação é automática via webhook (App\Controllers\PagamentoController::webhook,
     * branch tipo='diretorio') assim que o pagamento é confirmado — não passa mais por
     * aprovação manual do Master. Renovar (o mesmo plano de novo) reaproveita a assinatura já
     * existente da empresa em vez de criar uma linha duplicada, estendendo data_fim a partir
     * do vencimento atual (mesma lógica de GREATEST/COALESCE já usada em licença de sistema).
     */
    public function contratar(int $planoId): void
    {
        if (!csrf_verify()) { $this->flash('error','Token inválido.'); $this->redirect(url('/empresa/publicidade')); }

        $db  = DB::pdo();
        $eid = $this->empresaId();

        $plano = $db->prepare("SELECT * FROM diretorio_planos WHERE id = ? AND ativo = 1");
        $plano->execute([$planoId]);
        $plano = $plano->fetch();

        if (!$plano) { $this->flash('error','Plano não encontrado.'); $this->redirect(url('/empresa/publicidade')); }

        if (!InfinitePayService::ativo()) {
            $this->flash('error', 'O pagamento online ainda não está ativo. Fale com o suporte para contratar. 🙂');
            $this->redirect(url('/empresa/publicidade'));
        }

        // Verificar se já tem assinatura ATIVA de OUTRA empresa na mesma posição de banner
        // (a própria empresa renovando o slot que já ocupa não conta como "ocupado").
        if ($plano['tipo'] === 'banner' && $plano['posicao_banner']) {
            $stmtCheck = $db->prepare("
                SELECT COUNT(*) FROM diretorio_assinaturas a
                JOIN diretorio_planos p ON p.id = a.plano_id
                WHERE a.status = 'ativo' AND p.posicao_banner = ? AND p.tipo = 'banner' AND a.empresa_id != ?
            ");
            $stmtCheck->execute([$plano['posicao_banner'], $eid]);
            if ($stmtCheck->fetchColumn() > 0) {
                $this->flash('error', 'Esta posição já está ocupada. Escolha outra.');
                $this->redirect(url('/empresa/publicidade'));
            }
        }

        // Renovação: já existe uma assinatura (própria) pra esse mesmo plano? Reaproveita a
        // linha (o webhook estende data_fim) em vez de duplicar assinatura/banner a cada mês.
        $existente = $db->prepare("SELECT id FROM diretorio_assinaturas WHERE empresa_id = ? AND plano_id = ? AND status != 'cancelado' ORDER BY id DESC LIMIT 1");
        $existente->execute([$eid, $planoId]);
        $assinaturaId = $existente->fetchColumn();

        if ($assinaturaId) {
            $assinaturaId = (int) $assinaturaId;
            if ($plano['tipo'] === 'banner') {
                $bannerTitulo = trim($this->post('banner_titulo', ''));
                $bannerLink   = trim($this->post('banner_link', ''));
                $temBanner = $db->prepare("SELECT COUNT(*) FROM diretorio_banners WHERE assinatura_id = ?");
                $temBanner->execute([$assinaturaId]);
                if (!$temBanner->fetchColumn()) {
                    $db->prepare("INSERT INTO diretorio_banners (assinatura_id, empresa_id, posicao, titulo, link_url, aprovado) VALUES (?,?,?,?,?,0)")
                       ->execute([$assinaturaId, $eid, $plano['posicao_banner'], $bannerTitulo, $bannerLink]);
                } elseif ($bannerTitulo !== '' || $bannerLink !== '') {
                    $db->prepare("UPDATE diretorio_banners SET titulo = COALESCE(NULLIF(?, ''), titulo), link_url = COALESCE(NULLIF(?, ''), link_url) WHERE assinatura_id = ?")
                       ->execute([$bannerTitulo, $bannerLink, $assinaturaId]);
                }
            }
        } else {
            $db->prepare("INSERT INTO diretorio_assinaturas (empresa_id, plano_id, valor_pago, status) VALUES (?,?,?,'pendente')")
               ->execute([$eid, $planoId, $plano['preco']]);
            $assinaturaId = (int) $db->lastInsertId();

            if ($plano['tipo'] === 'banner') {
                $db->prepare("INSERT INTO diretorio_banners (assinatura_id, empresa_id, posicao, titulo, link_url, aprovado) VALUES (?,?,?,?,?,0)")
                   ->execute([$assinaturaId, $eid, $plano['posicao_banner'], trim($this->post('banner_titulo','')), trim($this->post('banner_link',''))]);
            }
        }

        $se = $db->prepare("SELECT nome_fantasia, razao_social, email, telefone, whatsapp, whatsapp_publico FROM empresas WHERE id = ?");
        $se->execute([$eid]);
        $e = $se->fetch() ?: [];

        $orderNsu = 'fxd-' . $eid . '-' . time();
        $valorCentavos = (int) round(((float) $plano['preco']) * 100);

        $db->prepare("INSERT INTO cobrancas (empresa_id, tipo, plano, valor, order_nsu, status) VALUES (?, 'diretorio', ?, ?, ?, 'pendente')")
           ->execute([$eid, 'diretorio_' . $assinaturaId, $valorCentavos, $orderNsu]);
        $cobId = (int) $db->lastInsertId();

        $items    = [['description' => 'FixaOS Diretório — ' . $plano['nome'] . ' (' . $plano['duracao_dias'] . ' dias)', 'quantity' => 1, 'price' => $valorCentavos]];
        $customer = array_filter([
            'name'         => $e['nome_fantasia'] ?? ($e['razao_social'] ?? null),
            'email'        => $e['email'] ?? null,
            'phone_number' => telefone_internacional($e['whatsapp'] ?: ($e['whatsapp_publico'] ?: ($e['telefone'] ?? ''))),
        ]);

        $link = InfinitePayService::criarLink(
            $orderNsu, $items,
            url('/pagamento/retorno?c=' . $cobId),
            url('/webhook/infinitepay'),
            $customer
        );

        if (!$link) {
            $db->prepare("UPDATE cobrancas SET status='cancelado' WHERE id=?")->execute([$cobId]);
            $this->flash('error', 'Não foi possível gerar o pagamento agora. Tente novamente em instantes.');
            $this->redirect(url('/empresa/publicidade'));
        }

        $db->prepare("UPDATE cobrancas SET link_url=? WHERE id=?")->execute([$link, $cobId]);
        log_acao('cobranca', 'diretorio', $cobId, 'Anúncio ' . $plano['nome'] . ' — R$ ' . number_format($plano['preco'], 2, ',', '.'));

        header('Location: ' . $link);
        exit;
    }

    public function uploadBanner(int $bannerId): void
    {
        if (!csrf_verify()) { $this->flash('error','Token inválido.'); $this->redirect(url('/empresa/publicidade')); }

        $db  = DB::pdo();
        $eid = $this->empresaId();

        $stmt = $db->prepare("SELECT * FROM diretorio_banners WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$bannerId, $eid]);
        $banner = $stmt->fetch();

        if (!$banner) { $this->flash('error','Banner não encontrado.'); $this->redirect(url('/empresa/publicidade')); }

        if (!empty($_FILES['banner_img']['tmp_name'])) {
            $fn = $this->processarBanner($_FILES['banner_img'], $eid);
            if ($fn === null) {
                $this->flash('error','Formato de imagem inválido. Use JPG, PNG, GIF ou WebP.');
                $this->redirect(url('/empresa/publicidade'));
            }
            $db->prepare("UPDATE diretorio_banners SET imagem=?, aprovado=0 WHERE id=?")->execute([$fn, $bannerId]);
        }

        $link = trim($this->post('banner_link',''));
        $titulo = trim($this->post('banner_titulo',''));
        $db->prepare("UPDATE diretorio_banners SET link_url=?, titulo=? WHERE id=?")->execute([$link, $titulo, $bannerId]);

        $this->flash('success','Banner enviado para aprovação!');
        $this->redirect(url('/empresa/publicidade'));
    }

    // ── Processa criativo do banner → valida mime (inclui WebP) e converte para WebP ──
    private function processarBanner(array $file, int $eid): ?string
    {
        $permitidos = ['image/jpeg','image/png','image/webp','image/gif','image/bmp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $permitidos)) return null;

        $dir = BASE_PATH . '/storage/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fn  = 'banner_' . $eid . '_' . time() . '.webp';
        $tmp = $file['tmp_name'];

        // Sem GD/WebP: salva original
        if (!function_exists('imagewebp')) {
            $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','image/bmp'=>'bmp'][$mime];
            $fn  = 'banner_' . $eid . '_' . time() . '.' . $ext;
            return move_uploaded_file($tmp, $dir . $fn) ? $fn : null;
        }

        $src = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
            'image/gif'  => @imagecreatefromgif($tmp),
            'image/bmp'  => @imagecreatefrombmp($tmp),
            default      => null,
        };
        if (!$src) return move_uploaded_file($tmp, $dir . $fn) ? $fn : null;

        $ow = imagesx($src); $oh = imagesy($src);

        // Reduz se a largura passar de 600px, mantendo a proporção
        $maxW = 600;
        if ($ow > $maxW) {
            $nw = $maxW; $nh = (int)round($oh * $maxW / $ow);
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
            imagedestroy($src);
            $src = $dst;
        }

        $ok = imagewebp($src, $dir . $fn, 88);
        imagedestroy($src);
        return $ok ? $fn : null;
    }
}
