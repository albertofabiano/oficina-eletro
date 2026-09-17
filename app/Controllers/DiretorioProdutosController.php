<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\ImageService;

/**
 * Vitrine de produtos do Diretório — desvencilhada do Marketplace de Peças (não usa
 * marketplace_anuncios, não consome crédito, não depende de "anúncio"). Tabela própria
 * (diretorio_produtos), benefício de plano pago ativo (perfil_diretorio_completo()), até
 * LIMITE produtos por vez, cada um com capa + até GALERIA_MAX fotos de galeria, padronizadas
 * em WebP 800x800 fundo branco via ImageService::padronizar() (mesmo pipeline já usado noutros
 * cadastros de foto do sistema — Marketplace tinha sua própria cópia manual em GD, esta tela
 * já nasce usando o serviço compartilhado).
 */
class DiretorioProdutosController extends Controller
{
    private const LIMITE               = 10;
    private const GALERIA_MAX          = 2; // + 1 capa = 3 fotos no total, como pedido
    private const MIME_IMAGEM_PERMITIDA = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
    private const IMAGEM_TAMANHO_MAX    = 8 * 1024 * 1024; // 8MB, mesmo limite do resto do sistema

    /** Plano ativo + quantos produtos já ocupam vaga (status IN ('ativo','vendido') — um
     *  vendido/esgotado continua ocupando a vaga até ser excluído/substituído, mesmo raciocínio
     *  já usado antes na integração com o Marketplace). $ignorarId exclui o próprio produto da
     *  contagem ao editar. */
    private function status(int $eid, ?int $ignorarId = null): array
    {
        $db = DB::pdo();
        $st = $db->prepare("SELECT licenca_ate FROM empresas WHERE id = ? LIMIT 1");
        $st->execute([$eid]);
        $planoCompleto = perfil_diretorio_completo($st->fetch() ?: []);

        $sql = "SELECT COUNT(*) FROM diretorio_produtos WHERE empresa_id = ? AND status IN ('ativo','vendido')";
        $params = [$eid];
        if ($ignorarId) { $sql .= " AND id != ?"; $params[] = $ignorarId; }
        $stQtd = $db->prepare($sql);
        $stQtd->execute($params);
        $qtd = (int) $stQtd->fetchColumn();

        return [
            'plano_completo' => $planoCompleto,
            'qtd'            => $qtd,
            'limite'         => self::LIMITE,
            'pode_cadastrar' => $planoCompleto && $qtd < self::LIMITE,
        ];
    }

    public function index(): void
    {
        $eid = $this->empresaId();
        $status = $this->status($eid);

        // Veio do botão "Cadastrar no Diretório" da tela de Produtos — pré-preenche o formulário.
        $prefill = null;
        $produtoId = (int) $this->get('produto_id', 0);
        if ($produtoId) {
            $produto = (new \App\Models\Produto())->find($produtoId);
            if ($produto) {
                $prefill = [
                    'produto_id' => $produto['id'],
                    'titulo'     => $produto['nome'],
                    'valor'      => $produto['valor_venda'],
                    'descricao'  => $produto['descricao'],
                ];
            }
        }

        $st = DB::pdo()->prepare("SELECT * FROM diretorio_produtos WHERE empresa_id = ? ORDER BY criado_em DESC");
        $st->execute([$eid]);

        $this->view('empresa.produtos_diretorio', [
            'titulo'        => 'Produtos no Diretório',
            'produtos'      => $st->fetchAll(),
            'planoCompleto' => $status['plano_completo'],
            'qtd'           => $status['qtd'],
            'limite'        => $status['limite'],
            'prefill'       => $prefill,
            'forcarTemaClaro' => true,
        ]);
    }

    public function criar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/empresa/produtos-diretorio')); }

        $eid    = $this->empresaId();
        $status = $this->status($eid);
        if (!$status['plano_completo']) {
            $this->flash('error', 'Cadastrar produtos no Diretório exige um plano pago ativo.');
            $this->redirect(url('/empresa/produtos-diretorio'));
        }
        if (!$status['pode_cadastrar']) {
            $this->flash('error', "Limite de {$status['limite']} produtos atingido. Exclua ou marque outro como vendido pra liberar espaço.");
            $this->redirect(url('/empresa/produtos-diretorio'));
        }

        $titulo = trim($this->post('titulo', ''));
        $valor  = moeda_float($this->post('valor', '0'));
        if (!$titulo || $valor <= 0) {
            $this->flash('error', 'Título e valor são obrigatórios.');
            $this->redirect(url('/empresa/produtos-diretorio'));
        }

        if ($erro = $this->validarImagem($_FILES['imagem_principal'] ?? [])) {
            $this->flash('error', $erro);
            $this->redirect(url('/empresa/produtos-diretorio'));
        }
        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp)) continue;
                $erro = $this->validarImagem(['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k]]);
                if ($erro) { $this->flash('error', 'Foto da galeria: ' . $erro); $this->redirect(url('/empresa/produtos-diretorio')); }
            }
        }

        // Se veio da tela de Produtos, confirma que o produto é desta empresa antes de vincular.
        $produtoId = (int) $this->post('produto_id', 0);
        if ($produtoId && !(new \App\Models\Produto())->find($produtoId)) {
            $produtoId = 0;
        }

        $imgPrincipal = null;
        $avisoImagem  = '';
        if (!empty($_FILES['imagem_principal']['tmp_name'])) {
            $imgPrincipal = $this->uploadImagem($_FILES['imagem_principal'], 'main', $titulo);
            if (!$imgPrincipal) $avisoImagem = ' A foto principal não pôde ser salva (arquivo corrompido) — edite o produto pra tentar de novo.';
        }

        $galeria = [];
        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp) || $_FILES['galeria']['error'][$k] !== UPLOAD_ERR_OK) continue;
                if (count($galeria) >= self::GALERIA_MAX) break;
                $fileArr = ['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k], 'error' => $_FILES['galeria']['error'][$k]];
                $nome = $this->uploadImagem($fileArr, 'gal' . $k, $titulo);
                if ($nome) $galeria[] = $nome;
            }
        }

        DB::pdo()->prepare(
            "INSERT INTO diretorio_produtos (empresa_id, produto_id, titulo, descricao, valor, imagem_principal, imagens_galeria)
             VALUES (?,?,?,?,?,?,?)"
        )->execute([
            $eid,
            $produtoId ?: null,
            $titulo,
            trim($this->post('descricao', '')),
            $valor,
            $imgPrincipal,
            $galeria ? json_encode($galeria) : null,
        ]);

        $this->flash('success', 'Produto cadastrado no Diretório!' . $avisoImagem);
        $this->redirect(url('/empresa/produtos-diretorio'));
    }

    public function editar(string $id): void
    {
        $eid = $this->empresaId();
        $st  = DB::pdo()->prepare("SELECT * FROM diretorio_produtos WHERE id = ? AND empresa_id = ?");
        $st->execute([(int) $id, $eid]);
        $produto = $st->fetch();
        if (!$produto) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect(url('/empresa/produtos-diretorio'));
        }

        $status = $this->status($eid, (int) $id);

        $this->view('empresa.produtos_diretorio_editar', [
            'titulo'        => 'Editar Produto do Diretório',
            'produto'       => $produto,
            'planoCompleto' => $status['plano_completo'],
            'qtd'           => $status['qtd'],
            'limite'        => $status['limite'],
            'galeriaAtual'  => !empty($produto['imagens_galeria']) ? json_decode($produto['imagens_galeria'], true) : [],
            'forcarTemaClaro' => true,
        ]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/empresa/produtos-diretorio')); }

        $eid = $this->empresaId();
        $st  = DB::pdo()->prepare("SELECT * FROM diretorio_produtos WHERE id = ? AND empresa_id = ?");
        $st->execute([(int) $id, $eid]);
        $produto = $st->fetch();
        if (!$produto) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect(url('/empresa/produtos-diretorio'));
        }

        $titulo = trim($this->post('titulo', ''));
        $valor  = moeda_float($this->post('valor', '0'));
        if (!$titulo || $valor <= 0) {
            $this->flash('error', 'Título e valor são obrigatórios.');
            $this->redirect(url('/empresa/produtos-diretorio/' . $id . '/editar'));
        }

        if ($erro = $this->validarImagem($_FILES['imagem_principal'] ?? [])) {
            $this->flash('error', $erro);
            $this->redirect(url('/empresa/produtos-diretorio/' . $id . '/editar'));
        }
        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp)) continue;
                $erro = $this->validarImagem(['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k]]);
                if ($erro) { $this->flash('error', 'Foto da galeria: ' . $erro); $this->redirect(url('/empresa/produtos-diretorio/' . $id . '/editar')); }
            }
        }

        $imgPrincipal = $produto['imagem_principal'];
        $avisoImagem  = '';
        if (!empty($_FILES['imagem_principal']['tmp_name']) && $_FILES['imagem_principal']['error'] === UPLOAD_ERR_OK) {
            $nova = $this->uploadImagem($_FILES['imagem_principal'], 'main', $titulo);
            if ($nova) {
                if ($imgPrincipal) @unlink(BASE_PATH . '/storage/uploads/diretorio_produtos/' . $imgPrincipal);
                $imgPrincipal = $nova;
            } else {
                $avisoImagem = ' A nova foto principal não pôde ser salva (arquivo corrompido) — a foto anterior foi mantida.';
            }
        }
        if ($this->post('remover_principal') === '1') {
            if ($imgPrincipal) @unlink(BASE_PATH . '/storage/uploads/diretorio_produtos/' . $imgPrincipal);
            $imgPrincipal = null;
        }

        $galeriaAtual = !empty($produto['imagens_galeria']) ? json_decode($produto['imagens_galeria'], true) : [];

        // Tornar uma foto já existente da galeria a nova capa — troca de posição, sem re-upload.
        $novaCapa = $this->post('nova_capa', '');
        if ($novaCapa !== '' && in_array($novaCapa, $galeriaAtual, true)) {
            $indiceEscolhido = array_search($novaCapa, $galeriaAtual, true);
            $capaAnterior = $imgPrincipal;
            $imgPrincipal = $novaCapa;
            if ($capaAnterior) {
                $galeriaAtual[$indiceEscolhido] = $capaAnterior;
            } else {
                unset($galeriaAtual[$indiceEscolhido]);
                $galeriaAtual = array_values($galeriaAtual);
            }
        }

        $remover = $this->post('remover_galeria', []);
        if (is_array($remover)) {
            foreach ($remover as $img) {
                @unlink(BASE_PATH . '/storage/uploads/diretorio_produtos/' . basename($img));
                $galeriaAtual = array_filter($galeriaAtual, fn($i) => $i !== $img);
            }
            $galeriaAtual = array_values($galeriaAtual);
        }

        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp) || $_FILES['galeria']['error'][$k] !== UPLOAD_ERR_OK) continue;
                if (count($galeriaAtual) >= self::GALERIA_MAX) break;
                $fileArr = ['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k], 'error' => UPLOAD_ERR_OK];
                $nome = $this->uploadImagem($fileArr, 'gal' . $k, $titulo);
                if ($nome) $galeriaAtual[] = $nome;
            }
        }

        DB::pdo()->prepare(
            "UPDATE diretorio_produtos SET titulo=?, descricao=?, valor=?, imagem_principal=?, imagens_galeria=?
             WHERE id=? AND empresa_id=?"
        )->execute([
            $titulo,
            trim($this->post('descricao', '')),
            $valor,
            $imgPrincipal,
            $galeriaAtual ? json_encode(array_values($galeriaAtual)) : null,
            (int) $id,
            $eid,
        ]);

        $this->flash('success', 'Produto atualizado!' . $avisoImagem);
        $this->redirect(url('/empresa/produtos-diretorio'));
    }

    /** Alterna ativo <-> vendido — "vendido" continua na vitrine com aviso vermelho (ver
     *  DiretorioController::empresa()) até a empresa excluir ou desmarcar. */
    public function vender(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $eid = $this->empresaId();
        $st  = DB::pdo()->prepare("SELECT status FROM diretorio_produtos WHERE id = ? AND empresa_id = ?");
        $st->execute([(int) $id, $eid]);
        $atual = $st->fetchColumn();
        if ($atual === false) { $this->json(['error' => 'Produto não encontrado'], 404); }

        $novoStatus = $atual === 'ativo' ? 'vendido' : 'ativo';
        DB::pdo()->prepare("UPDATE diretorio_produtos SET status = ? WHERE id = ? AND empresa_id = ?")
                 ->execute([$novoStatus, (int) $id, $eid]);

        $this->json(['success' => true, 'status' => $novoStatus]);
    }

    public function excluir(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/empresa/produtos-diretorio')); }

        $eid = $this->empresaId();
        $st  = DB::pdo()->prepare("SELECT * FROM diretorio_produtos WHERE id = ? AND empresa_id = ?");
        $st->execute([(int) $id, $eid]);
        $produto = $st->fetch();
        if (!$produto) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect(url('/empresa/produtos-diretorio'));
        }

        if ($produto['imagem_principal']) @unlink(BASE_PATH . '/storage/uploads/diretorio_produtos/' . $produto['imagem_principal']);
        foreach (json_decode($produto['imagens_galeria'] ?? '[]', true) ?: [] as $img) {
            @unlink(BASE_PATH . '/storage/uploads/diretorio_produtos/' . basename($img));
        }

        DB::pdo()->prepare("DELETE FROM diretorio_produtos WHERE id = ? AND empresa_id = ?")->execute([(int) $id, $eid]);

        $this->flash('success', 'Produto removido.');
        $this->redirect(url('/empresa/produtos-diretorio'));
    }

    private function validarImagem(array $file): ?string
    {
        if (empty($file['tmp_name'])) return null;
        if (($file['size'] ?? 0) > self::IMAGEM_TAMANHO_MAX) {
            return 'Imagem maior que 8MB. Reduza o tamanho e tente de novo.';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::MIME_IMAGEM_PERMITIDA, true)) {
            return 'Formato de imagem não suportado. Use JPG, PNG, WebP, GIF ou BMP.';
        }
        return null;
    }

    /** 800x800 WebP fundo branco via ImageService::padronizar() — mesmo "esquema" já usado no
     *  Marketplace, só que reaproveitando o serviço compartilhado em vez de duplicar GD cru. */
    private function uploadImagem(array $file, string $prefixo, string $titulo): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::MIME_IMAGEM_PERMITIDA, true)) return null;
        if (($file['size'] ?? 0) > self::IMAGEM_TAMANHO_MAX) return null;

        $mapa = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
                 'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c','Á'=>'a','Ã'=>'a',
                 'Ç'=>'c','É'=>'e','Ó'=>'o'];
        $slug = $titulo ? mb_substr(trim(preg_replace('/[^a-z0-9-]+/', '-', strtr(mb_strtolower(trim($titulo), 'UTF-8'), $mapa)), '-'), 0, 60) : $prefixo;
        if ($slug === '') $slug = $prefixo;

        $nome = $slug . '-' . $prefixo . '-' . $this->empresaId() . '-' . time() . '.webp';
        $dir  = BASE_PATH . '/storage/uploads/diretorio_produtos/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ok = ImageService::padronizar($file['tmp_name'], $dir . $nome, ['tamanho' => 800, 'qualidade' => 87]);
        return $ok ? $nome : null;
    }
}
