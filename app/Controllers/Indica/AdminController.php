<?php

namespace App\Controllers\Indica;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Indica\MercadoLivreService;
use PDO;

/**
 * Painel admin do Fixa Indica (/admin/*, atrás de Indica\MasterMiddleware). CRUD de
 * categorias/produtos/guias + config — forms simples com redirect, mesmo padrão já usado em
 * CRUDs pequenos do resto do projeto (ex. ServicosCatalogoController), sem Model dedicado.
 */
class AdminController extends Controller
{
    private const SELOS_VALIDOS = ['frete_gratis', 'mais_vendido', 'oferta_do_dia'];

    // ── Dashboard ──────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $db = DB::pdo();

        $totais = $db->query(
            "SELECT
               (SELECT COUNT(*) FROM indica_categorias WHERE ativa = 1) AS categorias,
               (SELECT COUNT(*) FROM indica_produtos WHERE ativo = 1) AS produtos,
               (SELECT COUNT(*) FROM indica_guias WHERE status = 'publicado') AS guias,
               (SELECT COUNT(*) FROM indica_cliques WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS cliques_30d"
        )->fetch(PDO::FETCH_ASSOC);

        $maisClicados = $db->query(
            "SELECT p.nome, p.slug, COUNT(c.id) AS total
             FROM indica_cliques c
             JOIN indica_produtos p ON p.id = c.produto_id
             WHERE c.criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY c.produto_id ORDER BY total DESC LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        $cliquesPorDia = $db->query(
            "SELECT DATE(criado_em) AS dia, COUNT(*) AS total
             FROM indica_cliques WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(criado_em) ORDER BY dia"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view('indica.admin.dashboard', [
            'ativo'         => 'dashboard',
            'tituloFull'    => 'Dashboard — Fixa Indica Admin',
            'totais'        => $totais,
            'maisClicados'  => $maisClicados,
            'cliquesPorDia' => $cliquesPorDia,
        ], 'indica_admin');
    }

    // ── Categorias ─────────────────────────────────────────────────────────

    public function categorias(): void
    {
        $db = DB::pdo();
        $categorias = $db->query("SELECT * FROM indica_categorias ORDER BY ordem ASC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('indica.admin.categorias', [
            'ativo'      => 'categorias',
            'tituloFull' => 'Categorias — Fixa Indica Admin',
            'categorias' => $categorias,
        ], 'indica_admin');
    }

    public function categoriaSalvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/categorias')); return; }

        $nome = trim((string) $this->post('nome', ''));
        if ($nome === '') { $this->flash('error', 'Nome é obrigatório.'); $this->redirect(indica_url('/admin/categorias')); return; }

        $slug = $this->slugUnico($nome, 'indica_categorias');
        $db = DB::pdo();
        $stmt = $db->prepare(
            "INSERT INTO indica_categorias (nome, slug, icone, texto_seo, ordem, ativa) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nome,
            $slug,
            trim((string) $this->post('icone', '')) ?: null,
            trim((string) $this->post('texto_seo', '')) ?: null,
            (int) $this->post('ordem', 0),
            $this->post('ativa') ? 1 : 0,
        ]);

        $this->flash('success', 'Categoria criada.');
        $this->redirect(indica_url('/admin/categorias'));
    }

    public function categoriaAtualizar(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/categorias')); return; }

        $nome = trim((string) $this->post('nome', ''));
        if ($nome === '') { $this->flash('error', 'Nome é obrigatório.'); $this->redirect(indica_url('/admin/categorias')); return; }

        $db = DB::pdo();
        $stmt = $db->prepare("SELECT slug FROM indica_categorias WHERE id = ?");
        $stmt->execute([$id]);
        $atual = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$atual) { $this->flash('error', 'Categoria não encontrada.'); $this->redirect(indica_url('/admin/categorias')); return; }

        $slug = $this->slugUnico($nome, 'indica_categorias', $id);

        $upd = $db->prepare(
            "UPDATE indica_categorias SET nome=?, slug=?, icone=?, texto_seo=?, ordem=?, ativa=? WHERE id=?"
        );
        $upd->execute([
            $nome,
            $slug,
            trim((string) $this->post('icone', '')) ?: null,
            trim((string) $this->post('texto_seo', '')) ?: null,
            (int) $this->post('ordem', 0),
            $this->post('ativa') ? 1 : 0,
            $id,
        ]);

        $this->flash('success', 'Categoria atualizada.');
        $this->redirect(indica_url('/admin/categorias'));
    }

    public function categoriaExcluir(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/categorias')); return; }

        // Produtos da categoria não são apagados — só perdem o vínculo (FK ON DELETE SET NULL,
        // ver migration 105), continuam existindo como "sem categoria".
        DB::pdo()->prepare("DELETE FROM indica_categorias WHERE id = ?")->execute([$id]);

        $this->flash('success', 'Categoria excluída.');
        $this->redirect(indica_url('/admin/categorias'));
    }

    // ── Banners (sidebar da página de produto) ────────────────────────────

    public function banners(): void
    {
        $db = DB::pdo();
        $banners = $db->query("SELECT * FROM indica_banners ORDER BY ordem ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('indica.admin.banners', [
            'ativo'      => 'banners',
            'tituloFull' => 'Banners — Fixa Indica Admin',
            'banners'    => $banners,
        ], 'indica_admin');
    }

    public function bannerSalvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/banners')); return; }

        $dados = $this->dadosBannerDoPost();
        if ($dados === null) { $this->redirect(indica_url('/admin/banners')); return; }

        $stmt = DB::pdo()->prepare(
            "INSERT INTO indica_banners (titulo, descricao, link, cor, posicao, ordem, ativo) VALUES (?, ?, ?, ?, 'sidebar_produto', ?, ?)"
        );
        $stmt->execute([$dados['titulo'], $dados['descricao'], $dados['link'], $dados['cor'], $dados['ordem'], $dados['ativo']]);

        $this->flash('success', 'Banner criado.');
        $this->redirect(indica_url('/admin/banners'));
    }

    public function bannerAtualizar(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/banners')); return; }

        $dados = $this->dadosBannerDoPost();
        if ($dados === null) { $this->redirect(indica_url('/admin/banners')); return; }

        $upd = DB::pdo()->prepare(
            "UPDATE indica_banners SET titulo=?, descricao=?, link=?, cor=?, ordem=?, ativo=? WHERE id=?"
        );
        $upd->execute([$dados['titulo'], $dados['descricao'], $dados['link'], $dados['cor'], $dados['ordem'], $dados['ativo'], $id]);

        $this->flash('success', 'Banner atualizado.');
        $this->redirect(indica_url('/admin/banners'));
    }

    public function bannerExcluir(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/banners')); return; }

        DB::pdo()->prepare("DELETE FROM indica_banners WHERE id = ?")->execute([$id]);

        $this->flash('success', 'Banner excluído.');
        $this->redirect(indica_url('/admin/banners'));
    }

    /** @return array|null null quando a validação falha (já seta a flash de erro). */
    private function dadosBannerDoPost(): ?array
    {
        $titulo = trim((string) $this->post('titulo', ''));
        if ($titulo === '') { $this->flash('error', 'Título é obrigatório.'); return null; }

        $link = trim((string) $this->post('link', ''));
        if ($link === '') { $this->flash('error', 'Link é obrigatório.'); return null; }

        $cor = trim((string) $this->post('cor', ''));
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) { $cor = '#7c3aed'; }

        return [
            'titulo'    => $titulo,
            'descricao' => trim((string) $this->post('descricao', '')) ?: null,
            'link'      => $link,
            'cor'       => $cor,
            'ordem'     => (int) $this->post('ordem', 0),
            'ativo'     => $this->post('ativo') ? 1 : 0,
        ];
    }

    // ── Produtos ───────────────────────────────────────────────────────────

    public function produtos(): void
    {
        $db = DB::pdo();
        $produtos = $db->query(
            "SELECT p.*, c.nome AS categoria_nome FROM indica_produtos p
             LEFT JOIN indica_categorias c ON c.id = p.categoria_id
             ORDER BY p.atualizado_em DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view('indica.admin.produtos', [
            'ativo'      => 'produtos',
            'tituloFull' => 'Produtos — Fixa Indica Admin',
            'produtos'   => $produtos,
        ], 'indica_admin');
    }

    public function produtoForm(?int $id = null): void
    {
        $db = DB::pdo();
        $produto = null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM indica_produtos WHERE id = ?");
            $stmt->execute([$id]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$produto) { $this->flash('error', 'Produto não encontrado.'); $this->redirect(indica_url('/admin/produtos')); return; }
            $produto['selos'] = $produto['selos'] ? (json_decode($produto['selos'], true) ?: []) : [];
            $produto['imagens_extra'] = $produto['imagens_extra'] ? (json_decode($produto['imagens_extra'], true) ?: []) : [];
        }

        $categorias = $db->query("SELECT id, nome FROM indica_categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('indica.admin.produto_form', [
            'ativo'       => 'produtos',
            'tituloFull'  => ($produto ? 'Editar' : 'Novo') . ' produto — Fixa Indica Admin',
            'produto'     => $produto,
            'categorias'  => $categorias,
            'selosValidos' => self::SELOS_VALIDOS,
            'mlConectado' => MercadoLivreService::conectado(),
        ], 'indica_admin');
    }

    public function produtoSalvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/produtos')); return; }

        $dados = $this->dadosProdutoDoPost();
        if ($dados === null) { $this->redirect(indica_url('/admin/produtos/novo')); return; }

        $dados['slug'] = $this->slugUnico($dados['nome'], 'indica_produtos');

        $db = DB::pdo();
        $stmt = $db->prepare(
            "INSERT INTO indica_produtos
                (categoria_id, loja, nome, slug, especificacao_curta, descricao, preco, disponivel,
                 link_ml, link_afiliado, imagem_url, imagens_extra, selos, destaque_home,
                 vendedor_parceiro, meta_title, meta_description, ativo, preco_atualizado_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $dados['categoria_id'], $dados['loja'], $dados['nome'], $dados['slug'], $dados['especificacao_curta'],
            $dados['descricao'], $dados['preco'], $dados['disponivel'], $dados['link_ml'],
            $dados['link_afiliado'], $dados['imagem_url'], $dados['imagensExtraJson'], $dados['selosJson'],
            $dados['destaque_home'], $dados['vendedor_parceiro'], $dados['meta_title'],
            $dados['meta_description'], $dados['ativo'],
            $dados['preco'] !== null ? date('Y-m-d H:i:s') : null,
        ]);

        $this->flash('success', 'Produto criado.');
        $this->redirect(indica_url('/admin/produtos'));
    }

    public function produtoAtualizar(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/produtos')); return; }

        $db = DB::pdo();
        $stmt = $db->prepare("SELECT preco FROM indica_produtos WHERE id = ?");
        $stmt->execute([$id]);
        $atual = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$atual) { $this->flash('error', 'Produto não encontrado.'); $this->redirect(indica_url('/admin/produtos')); return; }

        $dados = $this->dadosProdutoDoPost();
        if ($dados === null) { $this->redirect(indica_url('/admin/produtos/' . $id . '/editar')); return; }

        $dados['slug'] = $this->slugUnico($dados['nome'], 'indica_produtos', $id);
        // Só atualiza o carimbo de "preço consultado em" se o preço de fato mudou — editar
        // outro campo sem tocar no preço não deveria fingir que o preço foi "revisado agora".
        $precoMudou = (float) $atual['preco'] !== (float) $dados['preco'];

        $upd = $db->prepare(
            "UPDATE indica_produtos SET
                categoria_id=?, loja=?, nome=?, slug=?, especificacao_curta=?, descricao=?, preco=?,
                disponivel=?, link_ml=?, link_afiliado=?, imagem_url=?, imagens_extra=?, selos=?,
                destaque_home=?, vendedor_parceiro=?, meta_title=?, meta_description=?, ativo=?" .
                ($precoMudou ? ", preco_atualizado_em=NOW()" : "") . "
             WHERE id=?"
        );
        $upd->execute([
            $dados['categoria_id'], $dados['loja'], $dados['nome'], $dados['slug'], $dados['especificacao_curta'],
            $dados['descricao'], $dados['preco'], $dados['disponivel'], $dados['link_ml'],
            $dados['link_afiliado'], $dados['imagem_url'], $dados['imagensExtraJson'], $dados['selosJson'],
            $dados['destaque_home'], $dados['vendedor_parceiro'], $dados['meta_title'],
            $dados['meta_description'], $dados['ativo'],
            $id,
        ]);

        $this->flash('success', 'Produto atualizado.');
        $this->redirect(indica_url('/admin/produtos'));
    }

    public function produtoExcluir(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/produtos')); return; }

        // Hard delete — indica_cliques tem ON DELETE CASCADE (migration 105), e indica_guia_
        // blocos tipo 'produto' só guarda o id dentro do JSON (sem FK), então um bloco de guia
        // apontando pra um produto excluído passa a não renderizar nada (checado em
        // CatalogoService::blocosDoGuia(), que já só inclui 'produto' quando a busca acha a
        // linha) — não quebra a página do guia, só aquele bloco específico some.
        DB::pdo()->prepare("DELETE FROM indica_produtos WHERE id = ?")->execute([$id]);

        $this->flash('success', 'Produto excluído.');
        $this->redirect(indica_url('/admin/produtos'));
    }

    /** @return array|null null quando a validação falha (já seta a flash de erro). */
    private function dadosProdutoDoPost(): ?array
    {
        $nome = trim((string) $this->post('nome', ''));
        if ($nome === '') { $this->flash('error', 'Nome é obrigatório.'); return null; }

        $precoRaw = trim((string) $this->post('preco', ''));
        $preco = $precoRaw !== '' ? moeda_float($precoRaw) : null;
        if ($preco !== null && $preco < 0) { $this->flash('error', 'Preço não pode ser negativo.'); return null; }

        $selosPost = $this->post('selos', []);
        $selos = is_array($selosPost) ? array_values(array_intersect($selosPost, self::SELOS_VALIDOS)) : [];

        $categoriaId = (int) $this->post('categoria_id', 0);

        $loja = (string) $this->post('loja', 'mercado_livre');
        if (!in_array($loja, ['mercado_livre', 'aliexpress'], true)) $loja = 'mercado_livre';

        // Vem de um campo oculto (JSON montado em JS, pela busca automática ou pela galeria
        // mantida à mão) — nunca confia no formato: decodifica, filtra só string não-vazia,
        // deduplica, limita a 7 (a capa é o 8º lugar, em imagem_url) — mesmo cuidado de nunca
        // deixar POST forjado estourar o que o banco espera.
        $imagensExtraRaw = json_decode((string) $this->post('imagens_extra', '[]'), true);
        $imagensExtra = [];
        if (is_array($imagensExtraRaw)) {
            foreach ($imagensExtraRaw as $url) {
                $url = trim((string) $url);
                if ($url !== '' && !in_array($url, $imagensExtra, true)) {
                    $imagensExtra[] = $url;
                }
                if (count($imagensExtra) >= 7) break;
            }
        }

        return [
            'categoria_id'        => $categoriaId > 0 ? $categoriaId : null,
            'loja'                => $loja,
            'nome'                => $nome,
            'especificacao_curta' => trim((string) $this->post('especificacao_curta', '')) ?: null,
            'descricao'           => trim((string) $this->post('descricao', '')) ?: null,
            'preco'               => $preco,
            'disponivel'          => $this->post('disponivel') ? 1 : 0,
            'link_ml'             => trim((string) $this->post('link_ml', '')) ?: null,
            'link_afiliado'       => trim((string) $this->post('link_afiliado', '')) ?: null,
            'imagem_url'          => trim((string) $this->post('imagem_url', '')) ?: null,
            'imagensExtraJson'    => $imagensExtra ? json_encode($imagensExtra, JSON_UNESCAPED_UNICODE) : null,
            'selosJson'           => json_encode($selos, JSON_UNESCAPED_UNICODE),
            'destaque_home'       => $this->post('destaque_home') ? 1 : 0,
            'vendedor_parceiro'   => $this->post('vendedor_parceiro') ? 1 : 0,
            'meta_title'          => trim((string) $this->post('meta_title', '')) ?: null,
            'meta_description'    => trim((string) $this->post('meta_description', '')) ?: null,
            'ativo'               => $this->post('ativo') ? 1 : 0,
        ];
    }

    // ── Guias ──────────────────────────────────────────────────────────────

    public function guias(): void
    {
        $guias = DB::pdo()->query("SELECT * FROM indica_guias ORDER BY criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('indica.admin.guias', [
            'ativo'      => 'guias',
            'tituloFull' => 'Guias — Fixa Indica Admin',
            'guias'      => $guias,
        ], 'indica_admin');
    }

    public function guiaForm(?int $id = null): void
    {
        $db = DB::pdo();
        $guia = null;
        $blocos = [];
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM indica_guias WHERE id = ?");
            $stmt->execute([$id]);
            $guia = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$guia) { $this->flash('error', 'Guia não encontrado.'); $this->redirect(indica_url('/admin/guias')); return; }

            $stmtB = $db->prepare("SELECT tipo, conteudo FROM indica_guia_blocos WHERE guia_id = ? ORDER BY ordem ASC");
            $stmtB->execute([$id]);
            foreach ($stmtB->fetchAll(PDO::FETCH_ASSOC) as $b) {
                $blocos[] = ['tipo' => $b['tipo'], 'conteudo' => json_decode($b['conteudo'], true) ?: []];
            }
        }

        $produtos = $db->query("SELECT id, nome FROM indica_produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('indica.admin.guia_form', [
            'ativo'      => 'guias',
            'tituloFull' => ($guia ? 'Editar' : 'Novo') . ' guia — Fixa Indica Admin',
            'guia'       => $guia,
            'blocos'     => $blocos,
            'produtos'   => $produtos,
        ], 'indica_admin');
    }

    public function guiaSalvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/guias')); return; }

        $dados = $this->dadosGuiaDoPost();
        if ($dados === null) { $this->redirect(indica_url('/admin/guias/novo')); return; }

        $slug = $this->slugUnico($dados['titulo'], 'indica_guias');

        $db = DB::pdo();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO indica_guias (titulo, slug, status, meta_title, meta_description, publicado_em)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $dados['titulo'], $slug, $dados['status'], $dados['meta_title'], $dados['meta_description'],
                $dados['status'] === 'publicado' ? date('Y-m-d H:i:s') : null,
            ]);
            $guiaId = (int) $db->lastInsertId();
            $this->salvarBlocosGuia($db, $guiaId, $dados['blocos']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->flash('error', 'Erro ao salvar guia: ' . $e->getMessage());
            $this->redirect(indica_url('/admin/guias/novo'));
            return;
        }

        $this->flash('success', 'Guia criado.');
        $this->redirect(indica_url('/admin/guias'));
    }

    public function guiaAtualizar(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/guias')); return; }

        $db = DB::pdo();
        $stmt = $db->prepare("SELECT status, publicado_em FROM indica_guias WHERE id = ?");
        $stmt->execute([$id]);
        $atual = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$atual) { $this->flash('error', 'Guia não encontrado.'); $this->redirect(indica_url('/admin/guias')); return; }

        $dados = $this->dadosGuiaDoPost();
        if ($dados === null) { $this->redirect(indica_url('/admin/guias/' . $id . '/editar')); return; }

        $slug = $this->slugUnico($dados['titulo'], 'indica_guias', $id);
        // Só grava publicado_em na transição rascunho -> publicado (não sobrescreve a data da
        // primeira publicação a cada edição).
        $publicadoEm = $atual['publicado_em'];
        if ($dados['status'] === 'publicado' && $atual['status'] !== 'publicado') {
            $publicadoEm = date('Y-m-d H:i:s');
        }

        $db->beginTransaction();
        try {
            $upd = $db->prepare(
                "UPDATE indica_guias SET titulo=?, slug=?, status=?, meta_title=?, meta_description=?, publicado_em=? WHERE id=?"
            );
            $upd->execute([$dados['titulo'], $slug, $dados['status'], $dados['meta_title'], $dados['meta_description'], $publicadoEm, $id]);
            $this->salvarBlocosGuia($db, $id, $dados['blocos']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->flash('error', 'Erro ao salvar guia: ' . $e->getMessage());
            $this->redirect(indica_url('/admin/guias/' . $id . '/editar'));
            return;
        }

        $this->flash('success', 'Guia atualizado.');
        $this->redirect(indica_url('/admin/guias'));
    }

    public function guiaExcluir(int $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/guias')); return; }

        // indica_guia_blocos tem ON DELETE CASCADE (migration 105) — some sozinho.
        DB::pdo()->prepare("DELETE FROM indica_guias WHERE id = ?")->execute([$id]);

        $this->flash('success', 'Guia excluído.');
        $this->redirect(indica_url('/admin/guias'));
    }

    /** @return array|null null quando a validação falha (já seta a flash de erro). */
    private function dadosGuiaDoPost(): ?array
    {
        $titulo = trim((string) $this->post('titulo', ''));
        if ($titulo === '') { $this->flash('error', 'Título é obrigatório.'); return null; }

        $status = $this->post('status') === 'publicado' ? 'publicado' : 'rascunho';

        $blocosJson = (string) $this->post('blocos_json', '[]');
        $blocosRaw = json_decode($blocosJson, true);
        if (!is_array($blocosRaw)) { $this->flash('error', 'Blocos inválidos.'); return null; }

        $tiposValidos = ['titulo', 'paragrafo', 'produto', 'aviso'];
        $blocos = [];
        foreach ($blocosRaw as $b) {
            if (!is_array($b) || !in_array($b['tipo'] ?? '', $tiposValidos, true)) continue;
            $tipo = $b['tipo'];
            if ($tipo === 'produto') {
                $pid = (int) ($b['produto_id'] ?? 0);
                if ($pid <= 0) continue;
                $blocos[] = ['tipo' => $tipo, 'conteudo' => ['produto_id' => $pid]];
            } else {
                $texto = trim((string) ($b['texto'] ?? ''));
                if ($texto === '') continue;
                $blocos[] = ['tipo' => $tipo, 'conteudo' => ['texto' => $texto]];
            }
        }

        return [
            'titulo'           => $titulo,
            'status'           => $status,
            'meta_title'       => trim((string) $this->post('meta_title', '')) ?: null,
            'meta_description' => trim((string) $this->post('meta_description', '')) ?: null,
            'blocos'           => $blocos,
        ];
    }

    private function salvarBlocosGuia(PDO $db, int $guiaId, array $blocos): void
    {
        $db->prepare("DELETE FROM indica_guia_blocos WHERE guia_id = ?")->execute([$guiaId]);
        $ins = $db->prepare("INSERT INTO indica_guia_blocos (guia_id, ordem, tipo, conteudo) VALUES (?, ?, ?, ?)");
        foreach ($blocos as $i => $b) {
            $ins->execute([$guiaId, $i, $b['tipo'], json_encode($b['conteudo'], JSON_UNESCAPED_UNICODE)]);
        }
    }

    // ── Configurações ────────────────────────────────────────────────────────

    public function config(): void
    {
        $db = DB::pdo();
        $rows = $db->query("SELECT chave, valor FROM indica_config")->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->view('indica.admin.config', [
            'ativo'         => 'config',
            'tituloFull'    => 'Configurações — Fixa Indica Admin',
            'config'        => $rows,
            'mlConfigurado' => MercadoLivreService::configurado(),
            'mlStatus'      => MercadoLivreService::statusConexao(),
        ], 'indica_admin');
    }

    public function configSalvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/config')); return; }

        $db = DB::pdo();
        $valores = [
            'whatsapp_contato'   => trim((string) $this->post('whatsapp_contato', '')),
            'faixa_fixaos_ativa' => $this->post('faixa_fixaos_ativa') ? '1' : '0',
        ];

        $stmt = $db->prepare(
            "INSERT INTO indica_config (chave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        foreach ($valores as $chave => $valor) {
            $stmt->execute([$chave, $valor]);
        }

        $this->flash('success', 'Configurações salvas.');
        $this->redirect(indica_url('/admin/config'));
    }

    // ── Helpers privados ───────────────────────────────────────────────────

    /** Slug único por tabela — mesmo padrão de slug_empresa_unico() (app/Helpers/functions.php),
     *  evitando o iconv//TRANSLIT de slugify() (pode falhar silenciosamente conforme locale). */
    private function slugUnico(string $texto, string $tabela, int $idAtual = 0): string
    {
        $base = remover_acentos($texto);
        $base = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $base));
        $base = trim($base, '-');
        if ($base === '') $base = 'item';

        $db = DB::pdo();
        $slug = $base;
        $i = 2;
        while (true) {
            $stmt = $db->prepare("SELECT id FROM {$tabela} WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $idAtual]);
            if (!$stmt->fetch()) break;
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
