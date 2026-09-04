<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Models\Produto;

class ProdutoController extends Controller
{
    private Produto $model;

    // Mesmos limites já validados em MarketplaceController — reaproveitados aqui, não
    // reinventados, pro upload de foto do produto (capa + galeria).
    private const MIME_IMAGEM_PERMITIDA = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
    private const IMAGEM_TAMANHO_MAX    = 8 * 1024 * 1024; // 8MB
    private const GALERIA_MAX           = 3; // + 1 capa = 4 fotos no total, pedido do usuário

    public function __construct() { $this->model = new Produto(); }

    public function index(): void
    {
        $page     = (int) $this->get('page', 1);
        $busca    = $this->get('busca', '');
        $cat      = $this->get('categoria_id') ?: null;
        $soBaixo  = (bool) $this->get('baixo');
        $result   = $this->model->listar($page, 20, $busca, $cat ? (int) $cat : null, $soBaixo);
        $alertas  = $this->model->emEstoqueMinimo();

        $eid = $this->empresaId();
        $stmtC = DB::pdo()->prepare("SELECT * FROM categorias_produto WHERE empresa_id = ? ORDER BY nome");
        $stmtC->execute([$eid]);

        $this->view('produtos.index', [
            'titulo'     => 'Estoque / Produtos',
            'paginator'  => $result,
            'busca'      => $busca,
            'categorias' => $stmtC->fetchAll(),
            'alertas'    => $alertas,
            'soBaixo'    => $soBaixo,
        ]);
    }

    /** Exclui um produto permanentemente — admin only, com reautenticacao por senha. */
    public function excluir(string $id): void
    {
        $back = url('/produtos');
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        if (!Auth::isAdmin()) {
            $this->flash('error', 'Apenas o administrador pode excluir um produto.');
            $this->redirect($back);
        }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Reautenticação por senha — segurança extra numa ação IRREVERSÍVEL.
        $senha = (string) $this->post('senha', '');
        $uStmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $uStmt->execute([Auth::id()]);
        $hash = (string) $uStmt->fetchColumn();
        if ($senha === '' || $hash === '' || !password_verify($senha, $hash)) {
            $this->flash('error', 'Senha incorreta — o produto NÃO foi excluído.');
            $this->redirect($back);
        }

        $produto = $this->model->find((int) $id);
        if (!$produto || (int) $produto['empresa_id'] !== $eid) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect($back);
        }

        // Antes bloqueava a exclusão se o produto já tivesse sido usado em alguma OS. Removido
        // a pedido — a proteção passou a ser só o aviso de "ação IRREVERSÍVEL" + reautenticação
        // por senha já exigidos acima. os_pecas.produto_id é ON DELETE SET NULL, então excluir
        // o produto não apaga nem altera as peças já lançadas em OS antigas — só desvincula o
        // produto_id delas (a descrição/valor da linha continuam intactos, é só o link que some).

        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM movimentos_estoque WHERE produto_id = ? AND empresa_id = ?")->execute([(int) $id, $eid]);
            $db->prepare("DELETE FROM produtos WHERE id = ? AND empresa_id = ?")->execute([(int) $id, $eid]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->flash('error', 'Não foi possível excluir o produto. Tente novamente.');
            $this->redirect($back);
        }

        log_acao('produtos', 'excluir', (int) $id, 'Produto ' . ($produto['codigo'] ? $produto['codigo'] . ' — ' : '') . $produto['nome']);
        $this->flash('success', 'Produto "' . $produto['nome'] . '" excluído permanentemente.');
        $this->redirect($back);
    }

    /**
     * Dar entrada de estoque (repor) — cria movimento 'entrada' e soma ao saldo.
     * Usa Produto::movimentar(). Só para quem pode editar estoque.
     */
    public function entrada(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }
        if (!Auth::can('estoque', 'editar')) {
            $this->flash('error', 'Você não tem permissão para movimentar estoque.');
            $this->redirect(url('/produtos'));
        }
        $produto = $this->model->find((int) $id);
        if (!$produto || (int) $produto['empresa_id'] !== $this->empresaId()) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect(url('/produtos'));
        }
        $qtd = (float) str_replace(',', '.', (string) $this->post('quantidade', '0'));
        if ($qtd <= 0) { $this->flash('error', 'Informe uma quantidade maior que zero.'); $this->redirectBack(); }

        $valorCusto = moeda_float($this->post('valor_custo', $produto['valor_custo'] ?? 0));
        $motivo     = trim((string) $this->post('motivo')) ?: 'Reposição de estoque';

        $this->model->movimentar((int) $id, 'entrada', $qtd, $valorCusto, $motivo);
        // Atualiza o custo do produto se um novo custo foi informado
        if ($valorCusto > 0) {
            DB::pdo()->prepare("UPDATE produtos SET valor_custo = ? WHERE id = ? AND empresa_id = ?")
                     ->execute([$valorCusto, (int) $id, $this->empresaId()]);
        }
        $this->flash('success', "Entrada de {$qtd} {$produto['unidade']} em \"{$produto['nome']}\" registrada!");
        $this->redirect(url('/produtos'));
    }

    public function criar(): void
    {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);

        // Próximo código sequencial sugerido (00001, 00002, ...) — considera só códigos
        // curtos numéricos (ignora EAN-13 e afins), pega o maior e soma 1, com 5 dígitos.
        $stmt = DB::pdo()->prepare(
            "SELECT COALESCE(MAX(CAST(codigo_barras AS UNSIGNED)), 0)
             FROM produtos WHERE empresa_id = ? AND codigo_barras REGEXP '^[0-9]{1,6}$'"
        );
        $stmt->execute([$this->empresaId()]);
        $codigoSugerido = str_pad((string) ((int) $stmt->fetchColumn() + 1), 5, '0', STR_PAD_LEFT);

        // Código interno sugerido: 3 primeiras letras do nome fantasia + '-' + 4 dígitos sequenciais (ELE-0001)
        $db  = DB::pdo();
        $eid = $this->empresaId();
        $se  = $db->prepare("SELECT nome_fantasia, razao_social FROM empresas WHERE id = ?");
        $se->execute([$eid]);
        $emp = $se->fetch();
        $nomeEmp = $emp['nome_fantasia'] ?: ($emp['razao_social'] ?: 'PRD');
        $prefixo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nomeEmp)), 0, 3));
        $prefixo = str_pad($prefixo ?: 'PRD', 3, 'X');
        $si = $db->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)), 0)
                            FROM produtos WHERE empresa_id = ? AND codigo LIKE ?");
        $si->execute([$eid, $prefixo . '-%']);
        $codigoInternoSugerido = $prefixo . '-' . str_pad((string) ((int) $si->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

        $this->view('produtos.form', array_merge(
            ['titulo' => 'Novo Produto', 'produto' => $old,
             'codigoSugerido' => $codigoSugerido, 'codigoInternoSugerido' => $codigoInternoSugerido],
            $this->aux()
        ));
    }

    public function salvar(): void
    {
        if (!csrf_verify()) {
            $this->backWithInput('Sua sessão foi renovada. Confira os dados abaixo e clique em Cadastrar novamente.', $this->produtoOldInput());
        }

        // Limite de produtos do plano (dormente se cobrança off).
        $stCnt = DB::pdo()->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id = ?");
        $stCnt->execute([$this->empresaId()]);
        $msgLim = limite_plano_atingido($this->empresaId(), 'max_produtos', (int) $stCnt->fetchColumn());
        if ($msgLim) { $this->flash('error', $msgLim . ' 👉 Veja os planos em Configurações → Planos.'); $this->redirect(url('/produtos')); }

        // Garantia é obrigatória — o "required" do HTML é só a primeira barreira; sem checar
        // aqui também, um POST direto (ou o campo removido via devtools) salvaria sem garantia.
        // 0 é um valor válido explícito ("sem garantia"); só o campo vazio/negativo é rejeitado.
        if ($this->post('garantia_dias', '') === '' || (int) $this->post('garantia_dias') < 0) {
            $this->backWithInput('Informe a garantia do produto (em dias) — pode ser 0 se não houver garantia.', $this->produtoOldInput());
        }

        // Valida formato/tamanho ANTES de gravar — mesma cautela já aplicada no Marketplace
        // (uploadImagem() falhando em silêncio deixava o registro salvo sem a foto, sem
        // ninguém entender por quê).
        if ($erro = $this->validarImagemProduto($_FILES['imagem'] ?? [])) {
            $this->backWithInput($erro, $this->produtoOldInput());
        }
        if ($erro = $this->validarGaleriaProduto($_FILES['galeria'] ?? [])) {
            $this->backWithInput($erro, $this->produtoOldInput());
        }

        $data = [
            'codigo_barras'  => $this->post('codigo_barras'),
            'estado_id'      => $this->post('estado_id') ?: null,
            'tipo_id'        => $this->post('tipo_id') ?: null,
            'marca_id'       => $this->post('marca_id') ?: null,
            'nome'           => trim($this->post('nome')),
            'codigo'         => $this->post('codigo'),
            'codigo_peca'    => $this->post('codigo_peca') ?: null,
            'descricao'      => $this->post('descricao'),
            'categoria_id'   => $this->post('categoria_id') ?: null,
            'fornecedor_id'  => $this->post('fornecedor_id') ?: null,
            'unidade'        => $this->post('unidade', 'un'),
            'estoque_atual'  => (float) $this->post('estoque_atual', 0),
            'estoque_minimo' => (float) $this->post('estoque_minimo', 0),
            'localizacao'    => $this->post('localizacao'),
            'valor_custo'    => moeda_float($this->post('valor_custo', 0)),
            'valor_venda'    => moeda_float($this->post('valor_venda', 0)),
            'garantia_dias'  => (int) $this->post('garantia_dias'),
            'ativo'          => 1,
        ];

        // Foto de capa (opcional) — otimizada para 800×800 WebP
        $img = $this->uploadImagemProduto($_FILES['imagem'] ?? [], 'capa', $data['nome']);
        if ($img) $data['imagem'] = $img;

        // Galeria (opcional, até GALERIA_MAX fotos)
        $galeria = $this->uploadGaleriaProduto($_FILES['galeria'] ?? [], $data['nome']);
        if ($galeria) $data['imagens_galeria'] = json_encode($galeria);

        $id = $this->model->insert($data);
        $this->flash('success', 'Produto cadastrado!');
        $this->redirect(url('/produtos'));
    }

    public function editar(string $id): void
    {
        $produto = $this->model->find((int) $id);
        if (!$produto) { $this->flash('error', 'Produto não encontrado.'); $this->redirect(url('/produtos')); }
        $old = $_SESSION['_old'] ?? null;
        unset($_SESSION['_old']);
        if ($old) { $produto = array_merge($produto, $old); }
        $this->view('produtos.form', array_merge(
            ['titulo' => 'Editar Produto', 'produto' => $produto],
            $this->aux()
        ));
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) {
            $this->backWithInput('Sua sessão foi renovada. Confira os dados abaixo e clique em Salvar novamente.', $this->produtoOldInput());
        }

        // Mesma validação de salvar() — garantia é obrigatória também na edição.
        if ($this->post('garantia_dias', '') === '' || (int) $this->post('garantia_dias') < 0) {
            $this->backWithInput('Informe a garantia do produto (em dias) — pode ser 0 se não houver garantia.', $this->produtoOldInput());
        }
        if ($erro = $this->validarImagemProduto($_FILES['imagem'] ?? [])) {
            $this->backWithInput($erro, $this->produtoOldInput());
        }
        if ($erro = $this->validarGaleriaProduto($_FILES['galeria'] ?? [])) {
            $this->backWithInput($erro, $this->produtoOldInput());
        }

        $atual = $this->model->find((int) $id);
        if (!$atual || (int) $atual['empresa_id'] !== $this->empresaId()) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect(url('/produtos'));
        }

        $data = [
            'codigo_barras'  => $this->post('codigo_barras'),
            'estado_id'      => $this->post('estado_id') ?: null,
            'tipo_id'        => $this->post('tipo_id') ?: null,
            'marca_id'       => $this->post('marca_id') ?: null,
            'nome'           => trim($this->post('nome')),
            'codigo'         => $this->post('codigo'),
            'codigo_peca'    => $this->post('codigo_peca') ?: null,
            'descricao'      => $this->post('descricao'),
            'categoria_id'   => $this->post('categoria_id') ?: null,
            'fornecedor_id'  => $this->post('fornecedor_id') ?: null,
            'unidade'        => $this->post('unidade', 'un'),
            'garantia_dias'  => (int) $this->post('garantia_dias'),
            'estoque_minimo' => (float) $this->post('estoque_minimo', 0),
            'localizacao'    => $this->post('localizacao'),
            'valor_custo'    => moeda_float($this->post('valor_custo', 0)),
            'valor_venda'    => moeda_float($this->post('valor_venda', 0)),
        ];

        // Ajuste manual do estoque atual (só se o campo veio no form — evita zerar por engano)
        if (isset($_POST['estoque_atual']) && $_POST['estoque_atual'] !== '') {
            $data['estoque_atual'] = (float) $this->post('estoque_atual', 0);
        }

        // Capa: nova foto (substitui e apaga a antiga) ou remoção explícita
        $imgAtual = $atual['imagem'] ?? null;
        $novaCapa = $this->uploadImagemProduto($_FILES['imagem'] ?? [], 'capa', $data['nome']);
        if ($novaCapa) {
            if ($imgAtual) @unlink(BASE_PATH . '/storage/uploads/produtos/' . $imgAtual);
            $imgAtual = $novaCapa;
        } elseif ($this->post('remover_imagem') === '1') {
            if ($imgAtual) @unlink(BASE_PATH . '/storage/uploads/produtos/' . $imgAtual);
            $imgAtual = null;
        }

        // Galeria: parte do que já existia, mais trocas/remoções/novos uploads
        $galeriaAtual = !empty($atual['imagens_galeria']) ? (json_decode($atual['imagens_galeria'], true) ?: []) : [];

        // Tornar uma foto já existente da galeria a nova capa — troca de posição, sem
        // reenviar arquivo (mesmo padrão já usado no Marketplace).
        $trocaCapa = $this->post('nova_capa', '');
        if ($trocaCapa !== '' && in_array($trocaCapa, $galeriaAtual, true)) {
            $indice = array_search($trocaCapa, $galeriaAtual, true);
            $capaAnterior = $imgAtual;
            $imgAtual = $trocaCapa;
            if ($capaAnterior) {
                $galeriaAtual[$indice] = $capaAnterior;
            } else {
                unset($galeriaAtual[$indice]);
                $galeriaAtual = array_values($galeriaAtual);
            }
        }

        // Remoção seletiva de fotos da galeria
        $remover = $this->post('remover_galeria', []);
        if (is_array($remover) && $remover) {
            foreach ($remover as $arq) {
                @unlink(BASE_PATH . '/storage/uploads/produtos/' . basename((string) $arq));
            }
            $galeriaAtual = array_values(array_filter($galeriaAtual, fn($i) => !in_array($i, $remover, true)));
        }

        // Novos uploads de galeria, respeitando o teto de GALERIA_MAX no total
        $vagas = self::GALERIA_MAX - count($galeriaAtual);
        if ($vagas > 0) {
            $novas = $this->uploadGaleriaProduto($_FILES['galeria'] ?? [], $data['nome'], $vagas);
            $galeriaAtual = array_merge($galeriaAtual, $novas);
        }

        $data['imagem']          = $imgAtual;
        $data['imagens_galeria'] = $galeriaAtual ? json_encode(array_values($galeriaAtual)) : null;

        $this->model->update((int) $id, $data);
        $this->flash('success', 'Produto atualizado!');
        $this->redirect(url('/produtos'));
    }

    /** Valida formato/tamanho ANTES de gravar — sem isso, uma foto inválida falha em
     *  silêncio dentro do upload e o produto fica sem foto (ou mantém a antiga) sem
     *  ninguém entender por quê. Retorna null se ok (ou se nada foi enviado). */
    private function validarImagemProduto(array $file): ?string
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

    private function validarGaleriaProduto(array $filesGaleria): ?string
    {
        if (empty($filesGaleria['tmp_name'])) return null;
        foreach ($filesGaleria['tmp_name'] as $k => $tmp) {
            if (empty($tmp)) continue;
            $erro = $this->validarImagemProduto(['tmp_name' => $tmp, 'size' => $filesGaleria['size'][$k] ?? 0]);
            if ($erro) return 'Foto da galeria: ' . $erro;
        }
        return null;
    }

    // Valida e padroniza a foto enviada (WebP 800x800), salva em storage/uploads/produtos.
    // $prefixo entra no nome do arquivo pra evitar colisão quando várias fotos do MESMO
    // produto são enviadas na mesma requisição (capa + galeria) — sem isso, o nome sairia
    // igual pra todas (slug do nome + empresa + time() com resolução de 1s) e cada upload
    // sobrescreveria o anterior no disco (mesmo bug já corrigido no Marketplace).
    private function uploadImagemProduto(array $file, string $prefixo, string $nome): ?string
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        if ($this->validarImagemProduto($file)) return null;

        $slug = slugify($nome) ?: 'produto';
        $arquivo = $slug . '-' . $prefixo . '-' . $this->empresaId() . '-' . time() . '.webp';
        $dest = BASE_PATH . '/storage/uploads/produtos/' . $arquivo;
        $ok = \App\Services\ImageService::padronizar($file['tmp_name'], $dest, ['tamanho' => 800, 'qualidade' => 82]);
        return ($ok && is_file($dest)) ? $arquivo : null;
    }

    /** Envia até $limite fotos de galeria (padrão GALERIA_MAX). Ignora arquivos vazios/com
     *  erro de upload; já assume que validarGaleriaProduto() rodou antes (não revalida aqui). */
    private function uploadGaleriaProduto(array $filesGaleria, string $nome, int $limite = self::GALERIA_MAX): array
    {
        if (empty($filesGaleria['tmp_name'])) return [];
        $galeria = [];
        foreach ($filesGaleria['tmp_name'] as $k => $tmp) {
            if (count($galeria) >= $limite) break;
            if (empty($tmp) || ($filesGaleria['error'][$k] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $fileArr = ['tmp_name' => $tmp, 'size' => $filesGaleria['size'][$k] ?? 0, 'error' => UPLOAD_ERR_OK];
            $nomeArq = $this->uploadImagemProduto($fileArr, 'gal' . $k, $nome);
            if ($nomeArq) $galeria[] = $nomeArq;
        }
        return $galeria;
    }

    private function aux(): array
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();
        $q   = fn(string $t) => $db->prepare("SELECT * FROM {$t} WHERE empresa_id=? ORDER BY nome")->execute([$eid])
               ?: $db->prepare("SELECT * FROM {$t} WHERE empresa_id=? ORDER BY nome");

        $se = $db->prepare("SELECT * FROM produto_estados WHERE empresa_id=? ORDER BY nome"); $se->execute([$eid]);
        $st = $db->prepare("SELECT * FROM produto_tipos   WHERE empresa_id=? ORDER BY nome"); $st->execute([$eid]);
        $sm = $db->prepare("SELECT * FROM produto_marcas  WHERE empresa_id=? ORDER BY nome"); $sm->execute([$eid]);
        $sc = $db->prepare("SELECT * FROM categorias_produto WHERE empresa_id=? ORDER BY nome"); $sc->execute([$eid]);
        $sf = $db->prepare("SELECT id, razao_social FROM fornecedores WHERE empresa_id=? AND ativo=1 ORDER BY razao_social"); $sf->execute([$eid]);
        $su = $db->prepare("SELECT id, nome FROM produto_unidades WHERE empresa_id=? ORDER BY nome"); $su->execute([$eid]);

        return [
            'estados'     => $se->fetchAll(),
            'tipos'       => $st->fetchAll(),
            'marcas'      => $sm->fetchAll(),
            'categorias'  => $sc->fetchAll(),
            'fornecedores'=> $sf->fetchAll(),
            'unidades'    => $su->fetchAll(),
        ];
    }

    public function buscarAjax(): void
    {
        $this->json($this->model->buscar($this->get('q', '')));
    }

    private function produtoOldInput(): array
    {
        $old = $this->post();
        $old['valor_custo'] = moeda_float($old['valor_custo'] ?? 0);
        $old['valor_venda'] = moeda_float($old['valor_venda'] ?? 0);
        return $old;
    }
}
