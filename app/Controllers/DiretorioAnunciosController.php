<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;

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

    public function contratar(int $planoId): void
    {
        if (!csrf_verify()) { $this->flash('error','Token inválido.'); $this->redirect(url('/empresa/publicidade')); }

        $db  = DB::pdo();
        $eid = $this->empresaId();

        $plano = $db->prepare("SELECT * FROM diretorio_planos WHERE id = ? AND ativo = 1");
        $plano->execute([$planoId]);
        $plano = $plano->fetch();

        if (!$plano) { $this->flash('error','Plano não encontrado.'); $this->redirect(url('/empresa/publicidade')); }

        // Verificar se já tem assinatura ativa para banner na mesma posição
        if ($plano['tipo'] === 'banner' && $plano['posicao_banner']) {
            $stmtCheck = $db->prepare("
                SELECT COUNT(*) FROM diretorio_assinaturas a
                JOIN diretorio_planos p ON p.id = a.plano_id
                WHERE a.status = 'ativo' AND p.posicao_banner = ? AND p.tipo = 'banner'
            ");
            $stmtCheck->execute([$plano['posicao_banner']]);
            if ($stmtCheck->fetchColumn() > 0) {
                $this->flash('error', 'Esta posição já está ocupada. Escolha outra.');
                $this->redirect(url('/empresa/publicidade'));
            }
        }

        // Upload comprovante
        $comprovante = null;
        if (!empty($_FILES['comprovante']['tmp_name'])) {
            $ext  = pathinfo($_FILES['comprovante']['name'], PATHINFO_EXTENSION);
            $fn   = 'comp_' . $eid . '_' . time() . '.' . $ext;
            $dir  = BASE_PATH . '/storage/uploads/';
            if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $dir . $fn)) {
                $comprovante = $fn;
            }
        }

        $db->prepare("INSERT INTO diretorio_assinaturas (empresa_id, plano_id, valor_pago, comprovante, observacoes, status) VALUES (?,?,?,?,?,'pendente')")
           ->execute([$eid, $planoId, $plano['preco'], $comprovante, trim($this->post('observacoes',''))]);

        $assinaturaId = (int)$db->lastInsertId();

        // Se for banner, criar registro de banner
        if ($plano['tipo'] === 'banner') {
            $db->prepare("INSERT INTO diretorio_banners (assinatura_id, empresa_id, posicao, titulo, link_url, aprovado) VALUES (?,?,?,?,?,0)")
               ->execute([$assinaturaId, $eid, $plano['posicao_banner'], trim($this->post('banner_titulo','')), trim($this->post('banner_link',''))]);
        }

        $this->flash('success', 'Pedido enviado! Aguarde a aprovação do pagamento pelo Master Admin.');
        $this->redirect(url('/empresa/publicidade'));
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
