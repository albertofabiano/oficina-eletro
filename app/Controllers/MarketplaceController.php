<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Marketplace;

class MarketplaceController extends Controller
{
    private Marketplace $model;

    // Formatos que uploadImagem() de fato sabe converter (mesmo conjunto do match($mime) lá
    // embaixo) — fonte única usada também por validarImagem(), pra nunca divergir dos dois.
    private const MIME_IMAGEM_PERMITIDA = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
    private const IMAGEM_TAMANHO_MAX    = 8 * 1024 * 1024; // 8MB, mesmo limite de EmpresaController::processarFoto()

    public function __construct()
    {
        $this->model = new Marketplace();
    }

    /** Valida formato/tamanho ANTES de gastar crédito ou gravar no banco — sem isso,
     *  uploadImagem() falha em silêncio (retorna null) e o anúncio é salvo sem a foto, ou
     *  mantém a antiga, sem o usuário nunca saber por quê. Retorna null se o arquivo está ok
     *  (ou se nenhum arquivo foi enviado), ou a mensagem de erro pra mostrar. */
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

    // ── Vitrine pública (Google indexável) ───────────────────────────────

    public function publico(): void
    {
        $page    = (int) ($this->get('page') ?? 1);
        $filtros = [
            'busca'   => $this->get('busca', ''),
            'tipo'    => $this->get('tipo', ''),
            'marca'   => $this->get('marca', ''),
            'empresa' => (int) $this->get('empresa', 0),
        ];

        $db    = \App\Core\DB::pdo();
        $where = "a.status = 'ativo'";
        $params = [];

        if ($filtros['busca']) {
            $b = "%{$filtros['busca']}%";
            $where .= " AND (a.titulo LIKE ? OR a.marca LIKE ? OR a.modelo LIKE ? OR a.tipo LIKE ?)";
            array_push($params, $b, $b, $b, $b);
        }
        if ($filtros['tipo'])  { $where .= " AND a.tipo  LIKE ?"; $params[] = "%{$filtros['tipo']}%"; }
        if ($filtros['marca']) { $where .= " AND a.marca LIKE ?"; $params[] = "%{$filtros['marca']}%"; }

        $empresaNome = null;
        if ($filtros['empresa']) {
            $where .= " AND a.empresa_id_vendedor = ?";
            $params[] = $filtros['empresa'];
            $stmtE = $db->prepare("SELECT nome_fantasia FROM empresas WHERE id = ? AND ativo = 1");
            $stmtE->execute([$filtros['empresa']]);
            $empresaNome = $stmtE->fetchColumn() ?: null;
        }

        $perPage = 12;
        $stmtC   = $db->prepare("SELECT COUNT(*) FROM marketplace_anuncios a WHERE {$where}");
        $stmtC->execute($params);
        $total   = (int) $stmtC->fetchColumn();
        $offset  = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            "SELECT a.id, a.titulo, a.descricao, a.tipo, a.marca, a.modelo,
                    a.valor, a.imagem_principal, a.data_criacao,
                    e.nome_fantasia AS empresa_nome,
                    e.whatsapp      AS empresa_whatsapp,
                    e.telefone      AS empresa_tel,
                    e.cidade AS empresa_cidade, e.uf AS empresa_uf
             FROM marketplace_anuncios a
             JOIN empresas e ON e.id = a.empresa_id_vendedor
             WHERE {$where}
             ORDER BY a.data_criacao DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        $tipos  = $db->query("SELECT DISTINCT tipo  FROM marketplace_anuncios WHERE status='ativo' AND tipo  != '' ORDER BY tipo")->fetchAll(\PDO::FETCH_COLUMN);
        $marcas = $db->query("SELECT DISTINCT marca FROM marketplace_anuncios WHERE status='ativo' AND marca != '' ORDER BY marca")->fetchAll(\PDO::FETCH_COLUMN);

        $paginator = [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];

        $cfg     = require BASE_PATH . '/config/app.php';
        $baseUrl = rtrim($cfg['url'], '/');

        // Categorias da vitrine + blocos de anúncio — usados só pela sidebar do
        // template anônimo (partials/_publico_body.php).
        $_cats  = $db->query("SELECT * FROM marketplace_categorias WHERE ativo=1 ORDER BY ordem,nome")->fetchAll();
        $_adMap = [];
        foreach ($db->query("SELECT posicao,codigo FROM master_adsense_blocos WHERE ativo=1")->fetchAll() as $_b) {
            $_adMap[$_b['posicao']] = $_b['codigo'];
        }

        // Requisição AJAX (busca/filtro/paginação sem recarregar a página) — ver
        // public/js/marketplace-ajax.js. Devolve só o miolo (#mpBody), não a página inteira.
        $ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

        $dados = [
            'titulo'      => 'Marketplace de Peças',
            'paginator'   => $paginator,
            'filtros'     => $filtros,
            'tipos'       => $tipos,
            'marcas'      => $marcas,
            'baseUrl'     => $baseUrl,
            'empresaNome' => $empresaNome,
            '_cats'       => $_cats,
            '_adMap'      => $_adMap,
        ];

        if ($ajax) {
            $tituloAba = $empresaNome ? "Anúncios de {$empresaNome}" : 'Marketplace de Peças';
            $tituloAba = preg_replace('/[\r\n]+/', ' ', $tituloAba) . ' | FixaOS';
            // rawurlencode pra não corromper acentos — cabeçalhos HTTP não são UTF-8 seguros
            // (Headers.get() no fetch decodifica como Latin-1). JS faz decodeURIComponent().
            header('X-Page-Title: ' . rawurlencode($tituloAba));
        }

        if (\App\Core\Auth::check()) {
            if ($ajax) {
                extract($dados);
                require BASE_PATH . '/app/Views/marketplace/publico_content.php';
                exit;
            }
            $this->view('marketplace.publico_content', $dados);
        } else {
            extract($dados);
            if ($ajax) {
                require BASE_PATH . '/app/Views/marketplace/partials/_publico_body.php';
                exit;
            }
            require BASE_PATH . '/app/Views/marketplace/publico.php';
            exit;
        }
    }

    public function peca(string $id): void
    {
        $db   = \App\Core\DB::pdo();
        // Busca por slug ou por ID (fallback)
        $select = "SELECT a.*,
                    p.estoque_atual AS produto_estoque,
                    e.nome_fantasia AS empresa_nome,
                    e.telefone      AS empresa_telefone,
                    e.whatsapp      AS empresa_whatsapp,
                    e.logradouro    AS empresa_logradouro,
                    e.numero        AS empresa_numero,
                    e.bairro        AS empresa_bairro,
                    e.cidade        AS empresa_cidade,
                    e.uf            AS empresa_uf,
                    e.logo          AS empresa_logo
             FROM marketplace_anuncios a
             JOIN empresas e ON e.id = a.empresa_id_vendedor
             LEFT JOIN produtos p ON p.id = a.produto_id";

        // Tenta por slug primeiro, depois por ID
        $stmt = $db->prepare("$select WHERE a.slug = ? AND a.status = 'ativo'");
        $stmt->execute([$id]);
        $peca = $stmt->fetch();

        // Fallback por ID numérico
        if (!$peca && is_numeric($id)) {
            $stmt = $db->prepare("$select WHERE a.id = ? AND a.status = 'ativo'");
            $stmt->execute([(int)$id]);
            $peca = $stmt->fetch();
        }

        if (!$peca) {
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            exit;
        }

        // Se acessou por ID numérico e existe slug, redireciona para URL amigável (301)
        if (is_numeric($id) && !empty($peca['slug'])) {
            $appCfg = require BASE_PATH . '/config/app.php';
            $this->redirect(rtrim($appCfg['url'], '/') . '/pecas/' . $peca['slug'], 301);
        }

        $rel = $db->prepare(
            "SELECT a.id, a.titulo, a.marca, a.modelo, a.valor, a.imagem_principal, e.cidade, e.uf
             FROM marketplace_anuncios a
             JOIN empresas e ON e.id = a.empresa_id_vendedor
             WHERE a.status='ativo' AND a.id != ?
               AND (a.tipo = ? OR a.marca = ?)
             ORDER BY RAND() LIMIT 4"
        );
        $rel->execute([(int)$id, $peca['tipo'], $peca['marca']]);
        $relacionadas = $rel->fetchAll();

        $cfg     = require BASE_PATH . '/config/app.php';
        $baseUrl = rtrim($cfg['url'], '/');

        if (\App\Core\Auth::check()) {
            $this->view('marketplace.peca_content', [
                'titulo'     => e($peca['titulo']) . ' — Marketplace',
                'peca'       => $peca,
                'relacionadas' => $relacionadas,
                'baseUrl'    => $baseUrl,
            ]);
        } else {
            require BASE_PATH . '/app/Views/marketplace/peca.php';
            exit;
        }
    }

    // ── Vitrine global ────────────────────────────────────────────────────

    public function index(): void
    {
        // Rota sem login: manda pra vitrine pública (indexável pelo Google) em vez da
        // tela de login — /marketplace continua sendo a área do lojista já autenticado
        // (créditos, "meus anúncios", ocultar meus próprios anúncios etc).
        if (!\App\Core\Auth::check()) {
            $this->redirect(url('/pecas'));
        }

        $page    = (int) $this->get('page', 1);
        $filtros = [
            'busca'            => $this->get('busca', ''),
            'tipo'             => $this->get('tipo', ''),
            'marca'            => $this->get('marca', ''),
            'modelo'           => $this->get('modelo', ''),
            'ocultar_proprios' => true,
        ];

        $dados = [
            'titulo'   => 'Marketplace de Peças',
            'paginator'=> $this->model->vitrine($page, 12, $filtros),
            'filtros'  => $filtros,
            'tipos'    => $this->model->tiposDisponiveis(),
            'marcas'   => $this->model->marcasDisponiveis(),
            'saldo'    => $this->model->saldo(),
            'forcarTemaClaro' => true,
        ];

        // Requisição AJAX (busca/filtro/paginação sem recarregar a página) — mesmo
        // mecanismo de /pecas, ver public/js/marketplace-ajax.js.
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            extract($dados);
            require BASE_PATH . '/app/Views/marketplace/vitrine.php';
            exit;
        }

        $this->view('marketplace.vitrine', $dados);
    }

    // ── Meus anúncios ─────────────────────────────────────────────────────

    public function meusAnuncios(): void
    {
        $page   = (int) $this->get('page', 1);
        $status = $this->get('status', '');

        // Veio do botão "Anunciar no Diretório" da tela de Produtos — pré-preenche o formulário.
        $prefill = null;
        $produtoId = (int) $this->get('produto_id', 0);
        if ($produtoId) {
            $produto = (new \App\Models\Produto())->find($produtoId);
            if ($produto) {
                $prefill = [
                    'produto_id' => $produto['id'],
                    'titulo'     => $produto['nome'],
                    'valor'      => $produto['valor_venda'],
                    'codigo_interno' => $produto['codigo'],
                    'descricao'  => $produto['descricao'],
                    'estoque'    => $produto['estoque_atual'],
                ];
            }
        }

        $stmtCat = \App\Core\DB::pdo()->prepare(
            "SELECT nome FROM marketplace_categorias WHERE empresa_id = ? AND ativo = 1 ORDER BY ordem, nome"
        );
        $stmtCat->execute([$this->empresaId()]);

        $this->view('marketplace.meus_anuncios', [
            'titulo'     => 'Meus Anúncios',
            'paginator'  => $this->model->meusAnuncios($page, 12, $status),
            'saldo'      => $this->model->saldo(),
            'historico'  => $this->model->historico(1, 5),
            'status'     => $status,
            'prefill'    => $prefill,
            'categorias' => $stmtCat->fetchAll(\PDO::FETCH_COLUMN),
            'forcarTemaClaro' => true,
        ]);
    }

    // ── Upload de imagem — 800×800 WebP com fundo branco ──────────────────

    private function uploadImagem(array $file, string $prefixo = 'img', string $titulo = ''): ?string
    {
        // 'image/tiff' já apareceu aqui antes, mas o GD não lê TIFF (sem case correspondente no
        // match() abaixo) — caía no fallback "salva sem processar", gravando bytes de TIFF num
        // arquivo com extensão .webp (imagem quebrada em qualquer navegador). Removido: a lista
        // agora reflete só os formatos que o match() de fato sabe converter.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::MIME_IMAGEM_PERMITIDA, true)) return null;
        if (($file['size'] ?? 0) > self::IMAGEM_TAMANHO_MAX) return null;

        // Nome SEO baseado no título do anúncio
        if ($titulo) {
            $mapa = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
                     'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c','Á'=>'a','Ã'=>'a',
                     'Ç'=>'c','É'=>'e','Ó'=>'o'];
            $slug = strtr(strtolower(trim($titulo)), $mapa);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            $slug = mb_substr($slug, 0, 60);
        } else {
            $slug = $prefixo;
        }
        $nome = $slug . '-' . $this->empresaId() . '-' . time() . '.webp';
        $dir  = BASE_PATH . '/storage/uploads/marketplace/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $tmp = $file['tmp_name'];

        // Carregar imagem original com GD
        $src = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
            'image/gif'  => @imagecreatefromgif($tmp),
            'image/bmp'  => @imagecreatefrombmp($tmp),
            default      => null,
        };

        // Mime correto mas o GD não conseguiu decodificar (arquivo corrompido/truncado no
        // envio) — falha limpo em vez de salvar os bytes originais com extensão .webp (o
        // fallback antigo aqui produzia um arquivo cujo conteúdo nunca bate com a extensão,
        // sempre quebrado ao exibir).
        if (!$src) return null;

        $ow = imagesx($src);
        $oh = imagesy($src);

        // Canvas 800×800 fundo branco
        $canvas = imagecreatetruecolor(800, 800);
        $branco = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $branco);

        // PNG com transparência: mesclar sobre fundo branco
        imagealphablending($src, false);
        imagesavealpha($src, true);

        // Calcular dimensões mantendo proporção dentro de 800×800
        $ratio = min(800 / $ow, 800 / $oh);
        $nw    = (int)($ow * $ratio);
        $nh    = (int)($oh * $ratio);
        $ox    = (int)((800 - $nw) / 2);
        $oy    = (int)((800 - $nh) / 2);

        // Copiar imagem centralizada no canvas branco
        imagecopyresampled($canvas, $src, $ox, $oy, 0, 0, $nw, $nh, $ow, $oh);

        // Salvar como WebP qualidade 87
        imagewebp($canvas, $dir . $nome, 87);

        imagedestroy($src);
        imagedestroy($canvas);

        return $nome;
    }


    // ── Criar anúncio ─────────────────────────────────────────────────────

    public function criar(): void
    {
        if (!csrf_verify()) {
            $this->flash('error', 'Token inválido.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }

        $eid    = $this->empresaId();
        $saldo  = $this->model->saldo($eid);

        if ($saldo < 1) {
            $this->flash('error', 'Saldo insuficiente. Você precisa de pelo menos 1 crédito para anunciar. Solicite créditos ao administrador.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }

        $titulo = trim($this->post('titulo', ''));
        $valor  = moeda_float($this->post('valor', '0'));

        if (!$titulo || $valor <= 0) {
            $this->flash('error', 'Título e valor são obrigatórios.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }

        // Valida formato/tamanho das imagens ANTES de debitar o crédito — uploadImagem() falhava
        // em silêncio (retornava null) e o anúncio era criado sem foto mesmo já tendo cobrado o
        // crédito, sem o usuário nunca saber por quê (ver CLAUDE.md).
        if ($erro = $this->validarImagem($_FILES['imagem_principal'] ?? [])) {
            $this->flash('error', $erro);
            $this->redirect(url('/marketplace/meus-anuncios'));
        }
        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp)) continue;
                $erro = $this->validarImagem(['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k]]);
                if ($erro) {
                    $this->flash('error', 'Foto da galeria: ' . $erro);
                    $this->redirect(url('/marketplace/meus-anuncios'));
                }
            }
        }

        // Debitar 1 crédito atomicamente
        if (!$this->model->consumirCredito($eid)) {
            $this->flash('error', 'Falha ao debitar crédito. Tente novamente.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }

        // Upload imagem principal
        $imgPrincipal   = null;
        $avisoImagem    = '';
        if (!empty($_FILES['imagem_principal']['tmp_name'])) {
            $imgPrincipal = $this->uploadImagem($_FILES['imagem_principal'], 'main', $titulo);
            if (!$imgPrincipal) $avisoImagem = ' A foto principal não pôde ser salva (arquivo corrompido) — edite o anúncio pra tentar de novo.';
        }

        // Upload galeria (até 3 imagens)
        $galeria = [];
        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp) || $_FILES['galeria']['error'][$k] !== UPLOAD_ERR_OK) continue;
                $fileArr = [
                    'tmp_name' => $tmp,
                    'size'     => $_FILES['galeria']['size'][$k],
                    'error'    => $_FILES['galeria']['error'][$k],
                ];
                $nome = $this->uploadImagem($fileArr, 'gal' . $k, $titulo);
                if ($nome) $galeria[] = $nome;
                if (count($galeria) >= 3) break;
            }
        }

        // Se veio da tela de Produtos, confirma que o produto é desta empresa antes de vincular.
        $produtoId = (int) $this->post('produto_id', 0);
        if ($produtoId && !(new \App\Models\Produto())->find($produtoId)) {
            $produtoId = 0;
        }

        // Salvar anúncio
        $anuncioId = $this->model->criarAnuncio([
            'titulo'            => $titulo,
            'descricao'         => trim($this->post('descricao', '')),
            'tipo'              => trim($this->post('tipo', '')),
            'marca'             => trim($this->post('marca', '')),
            'modelo'            => trim($this->post('modelo', '')),
            'codigo_interno'    => trim($this->post('codigo_interno', '')),
            'valor'             => $valor,
            'produto_id'        => $produtoId ?: null,
            'imagem_principal'  => $imgPrincipal,
            'imagens_galeria'   => $galeria ? json_encode($galeria) : null,
        ]);

        // Gerar slug amigável
        $slug = $this->gerarSlug($titulo, $anuncioId);
        \App\Core\DB::pdo()->prepare("UPDATE marketplace_anuncios SET slug=? WHERE id=?")->execute([$slug, $anuncioId]);

        // Registrar consumo no histórico
        $this->model->registrarHistorico(
            $eid,
            'consumo',
            -1,
            "Anúncio criado: {$titulo}",
            $anuncioId,
            $this->usuarioId()
        );

        $this->flash('success', "Anúncio publicado com sucesso! Saldo restante: " . ($saldo - 1) . " crédito(s)." . $avisoImagem);
        $this->redirect(url('/marketplace/meus-anuncios'));
    }

    // ── Editar anúncio ────────────────────────────────────────────────────

    public function editar(string $id): void
    {
        $anuncio = $this->model->findAnuncio((int) $id);
        if (!$anuncio) {
            $this->flash('error', 'Anúncio não encontrado.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }
        $this->view('marketplace.editar', [
            'titulo'   => 'Editar Anúncio',
            'anuncio'  => $anuncio,
            'saldo'    => $this->model->saldo(),
            'forcarTemaClaro' => true,
        ]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) {
            $this->flash('error', 'Token inválido.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }

        $anuncio = $this->model->findAnuncio((int) $id);
        if (!$anuncio) {
            $this->flash('error', 'Anúncio não encontrado.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }

        $titulo = trim($this->post('titulo', ''));
        $valor  = moeda_float($this->post('valor', '0'));

        if (!$titulo || $valor <= 0) {
            $this->flash('error', 'Título e valor são obrigatórios.');
            $this->redirect(url('/marketplace/anuncios/' . $id . '/editar'));
        }

        // Mesma validação de criar(): sem isso, uma foto em formato não suportado (ou grande
        // demais) fazia uploadImagem() falhar em silêncio — a foto ANTIGA ficava mantida sem
        // aviso nenhum, dando a entender que a troca "não pegou" por algum motivo desconhecido.
        if ($erro = $this->validarImagem($_FILES['imagem_principal'] ?? [])) {
            $this->flash('error', $erro);
            $this->redirect(url('/marketplace/anuncios/' . $id . '/editar'));
        }
        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp)) continue;
                $erro = $this->validarImagem(['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k]]);
                if ($erro) {
                    $this->flash('error', 'Foto da galeria: ' . $erro);
                    $this->redirect(url('/marketplace/anuncios/' . $id . '/editar'));
                }
            }
        }

        // Imagem principal: nova ou manter a existente
        $imgPrincipal = $anuncio['imagem_principal'];
        $avisoImagem  = '';
        if (!empty($_FILES['imagem_principal']['tmp_name']) && $_FILES['imagem_principal']['error'] === UPLOAD_ERR_OK) {
            $nova = $this->uploadImagem($_FILES['imagem_principal'], 'main', $titulo);
            if ($nova) {
                // Deletar antiga
                if ($imgPrincipal) @unlink(BASE_PATH . '/storage/uploads/marketplace/' . $imgPrincipal);
                $imgPrincipal = $nova;
            } else {
                $avisoImagem = ' A nova foto principal não pôde ser salva (arquivo corrompido) — a foto anterior foi mantida.';
            }
        }
        // Remover imagem principal se solicitado
        if ($this->post('remover_principal') === '1') {
            if ($imgPrincipal) @unlink(BASE_PATH . '/storage/uploads/marketplace/' . $imgPrincipal);
            $imgPrincipal = null;
        }

        // Galeria: manter existentes + novas
        $galeriaAtual = !empty($anuncio['imagens_galeria']) ? json_decode($anuncio['imagens_galeria'], true) : [];

        // Remover itens marcados
        $remover = $this->post('remover_galeria', []);
        if (is_array($remover)) {
            foreach ($remover as $img) {
                @unlink(BASE_PATH . '/storage/uploads/marketplace/' . basename($img));
                $galeriaAtual = array_filter($galeriaAtual, fn($i) => $i !== $img);
            }
            $galeriaAtual = array_values($galeriaAtual);
        }

        // Upload de novas fotos de galeria
        if (!empty($_FILES['galeria']['tmp_name'])) {
            foreach ($_FILES['galeria']['tmp_name'] as $k => $tmp) {
                if (empty($tmp) || $_FILES['galeria']['error'][$k] !== UPLOAD_ERR_OK) continue;
                if (count($galeriaAtual) >= 3) break;
                $fileArr = ['tmp_name'=>$tmp,'size'=>$_FILES['galeria']['size'][$k],'error'=>UPLOAD_ERR_OK];
                $nome = $this->uploadImagem($fileArr, 'gal' . $k, $titulo);
                if ($nome) $galeriaAtual[] = $nome;
            }
        }

        $novoSlug = $this->gerarSlug($titulo, (int)$id);

        \App\Core\DB::pdo()->prepare(
            "UPDATE marketplace_anuncios
             SET titulo=?, slug=?, descricao=?, tipo=?, marca=?, modelo=?, codigo_interno=?,
                 valor=?, imagem_principal=?, imagens_galeria=?
             WHERE id=? AND empresa_id_vendedor=?"
        )->execute([
            $titulo,
            $novoSlug,
            trim($this->post('descricao', '')),
            trim($this->post('tipo', '')),
            trim($this->post('marca', '')),
            trim($this->post('modelo', '')),
            trim($this->post('codigo_interno', '')),
            $valor,
            $imgPrincipal,
            $galeriaAtual ? json_encode(array_values($galeriaAtual)) : null,
            (int) $id,
            $this->empresaId(),
        ]);

        $this->flash('success', 'Anúncio atualizado com sucesso!' . $avisoImagem);
        $this->redirect(url('/marketplace/meus-anuncios'));
    }

    // ── Pausar anúncio ────────────────────────────────────────────────────

    public function pausar(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $anuncio = $this->model->findAnuncio((int) $id);
        if (!$anuncio) { $this->json(['error' => 'Anúncio não encontrado'], 404); }

        $novoStatus = $anuncio['status'] === 'ativo' ? 'pausado' : 'ativo';
        $this->model->alterarStatus((int) $id, $novoStatus);

        $this->json(['success' => true, 'status' => $novoStatus]);
    }

    // ── Marcar como vendido ───────────────────────────────────────────────

    public function vender(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $anuncio = $this->model->findAnuncio((int) $id);
        if (!$anuncio) { $this->json(['error' => 'Anúncio não encontrado'], 404); }

        $this->model->alterarStatus((int) $id, 'vendido');
        $this->json(['success' => true]);
    }

    // ── Excluir anúncio ───────────────────────────────────────────────────

    public function excluir(string $id): void
    {
        $anuncio = $this->model->findAnuncio((int) $id);
        if (!$anuncio) {
            $this->flash('error', 'Anúncio não encontrado.');
            $this->redirect(url('/marketplace/meus-anuncios'));
        }

        \App\Core\DB::pdo()->prepare(
            "DELETE FROM marketplace_anuncios WHERE id = ? AND empresa_id_vendedor = ?"
        )->execute([(int) $id, $this->empresaId()]);

        $this->flash('success', 'Anúncio removido.');
        $this->redirect(url('/marketplace/meus-anuncios'));
    }

    private function gerarSlug(string $titulo, int $id): string
    {
        $mapa = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
                 'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
                 'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
                 'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
                 'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
                 'ç'=>'c','ñ'=>'n',
                 'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a',
                 'É'=>'e','Ê'=>'e','Í'=>'i','Ó'=>'o','Ô'=>'o','Õ'=>'o',
                 'Ú'=>'u','Ç'=>'c'];
        $base = strtr(trim($titulo), $mapa);
        $base = mb_strtolower($base, 'UTF-8');
        $base = preg_replace('/[^a-z0-9\s-]/', '', $base);
        $base = preg_replace('/[\s-]+/', '-', $base);
        $base = trim($base, '-') ?: 'peca';

        // Verificar unicidade — se já existe para outro produto, adicionar contador
        $db = \App\Core\DB::pdo();
        $slug = $base;
        $i = 2;
        while (true) {
            $s = $db->prepare("SELECT id FROM marketplace_anuncios WHERE slug = ? AND id != ?");
            $s->execute([$slug, $id]);
            if (!$s->fetch()) break;
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
