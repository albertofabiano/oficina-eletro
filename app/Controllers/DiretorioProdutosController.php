<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\ImageService;

/**
 * Vitrine de produtos do Diretório — desvencilhada do Marketplace de Peças (não usa
 * marketplace_anuncios, não consome crédito, não depende de "anúncio"). Tabela própria
 * (diretorio_produtos), benefício de plano pago ativo (perfil_diretorio_completo()), até
 * LIMITE produtos por vez (padrão pros planos que não declaram `max_produtos_diretorio` em
 * config/planos.php -- ver status()), cada um com capa + até GALERIA_MAX fotos de galeria,
 * padronizadas em WebP 800x800 fundo branco via ImageService::padronizar() (mesmo pipeline já
 * usado noutros cadastros de foto do sistema — Marketplace tinha sua própria cópia manual em
 * GD, esta tela já nasce usando o serviço compartilhado).
 */
class DiretorioProdutosController extends Controller
{
    private const LIMITE               = 10;
    private const GALERIA_MAX          = 2; // + 1 capa = 3 fotos no total, como pedido
    private const MIME_IMAGEM_PERMITIDA = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
    private const IMAGEM_TAMANHO_MAX    = 8 * 1024 * 1024; // 8MB, mesmo limite do resto do sistema

    /** Motivo da última falha de uploadImagem()/copiarImagemDoEstoque() — `validarImagem()` já
     *  filtra mime/tamanho ANTES de chegar aqui, então se mesmo assim retornar null, sobra só
     *  uma causa real pra descobrir: a pasta de destino sem permissão de escrita, ou o GD não
     *  conseguindo processar/gravar o arquivo. Guardado num campo (não só um log) pra aparecer
     *  na própria mensagem de erro que o usuário vê — sem acesso ao log do VPS, essa é a única
     *  forma de saber a causa real de um "não salva" sem reproduzir localmente. */
    private ?string $ultimoErroUpload = null;

    /** Plano ativo + quantos produtos já ocupam vaga (status IN ('ativo','vendido') — um
     *  vendido/esgotado continua ocupando a vaga até ser excluído/substituído, mesmo raciocínio
     *  já usado antes na integração com o Marketplace). $ignorarId exclui o próprio produto da
     *  contagem ao editar. */
    private function status(int $eid, ?int $ignorarId = null): array
    {
        $db = DB::pdo();
        $st = $db->prepare("SELECT licenca_ate, plano_atual FROM empresas WHERE id = ? LIMIT 1");
        $st->execute([$eid]);
        $emp = $st->fetch() ?: [];
        $planoCompleto = perfil_diretorio_completo($emp);
        // Plano pode declarar seu próprio teto de vitrine (`max_produtos_diretorio`); quem não
        // declara mantém o LIMITE de sempre. Mesma convenção "0 = ilimitado" já usada em todo
        // config/planos.php (limite_plano_atingido(), scan_ia_verificar() etc.) — sem isso,
        // um plano com max_produtos_diretorio=0 (ilimitado, hoje todos os 4) travaria pra
        // sempre, já que `??` só cai no fallback com null, nunca com 0.
        $limite     = (int) (plano_da_empresa($emp)['max_produtos_diretorio'] ?? self::LIMITE);
        $ilimitado  = $limite <= 0;

        $sql = "SELECT COUNT(*) FROM diretorio_produtos WHERE empresa_id = ? AND status IN ('ativo','vendido')";
        $params = [$eid];
        if ($ignorarId) { $sql .= " AND id != ?"; $params[] = $ignorarId; }
        $stQtd = $db->prepare($sql);
        $stQtd->execute($params);
        $qtd = (int) $stQtd->fetchColumn();

        return [
            'plano_completo' => $planoCompleto,
            'qtd'            => $qtd,
            'limite'         => $limite,
            'ilimitado'      => $ilimitado,
            'pode_cadastrar' => $planoCompleto && ($ilimitado || $qtd < $limite),
        ];
    }

    public function index(): void
    {
        $eid = $this->empresaId();
        $status = $this->status($eid);

        $stSlug = DB::pdo()->prepare("SELECT slug FROM empresas WHERE id = ? LIMIT 1");
        $stSlug->execute([$eid]);
        $slug = $stSlug->fetchColumn();
        $appCfg = require BASE_PATH . '/config/app.php';
        $urlPublica = $slug ? rtrim($appCfg['url'], '/') . '/assistencias/' . $slug : null;

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
                    // Avisa na tela que a foto do Estoque será reaproveitada se o usuário não
                    // anexar outra aqui (ver DiretorioProdutosController::criar()) — sem isso o
                    // usuário não tem como saber que já tem foto, achando que precisa reenviar.
                    'tem_foto'   => !empty($produto['imagem']),
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
            'ilimitado'     => $status['ilimitado'],
            'prefill'       => $prefill,
            'urlPublica'    => $urlPublica,
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

        $titulo     = trim($this->post('titulo', ''));
        $valor      = moeda_float($this->post('valor', '0'));
        $quantidade = max(1, (int) $this->post('quantidade', 1));
        $tags       = $this->sanitizarTags($this->post('tags', ''));
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
                $erroUpload = $_FILES['galeria']['error'][$k] ?? UPLOAD_ERR_NO_FILE;
                if ($erroUpload === UPLOAD_ERR_NO_FILE) continue;
                $erro = $this->validarImagem(['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k], 'error' => $erroUpload]);
                if ($erro) { $this->flash('error', 'Foto da galeria: ' . $erro); $this->redirect(url('/empresa/produtos-diretorio')); }
            }
        }

        // Se veio da tela de Produtos ("Cadastrar no Diretório", ver produtos/form.php), confirma
        // que o produto é desta empresa antes de vincular — guarda a linha inteira (não só o id)
        // porque, se o usuário não anexar foto nenhuma aqui, reaproveitamos a foto que o produto
        // já tem no Estoque (ver abaixo) em vez de obrigar reenviar a mesma imagem duas vezes.
        $produtoId = (int) $this->post('produto_id', 0);
        $produtoOrigem = $produtoId ? (new \App\Models\Produto())->find($produtoId) : null;
        if ($produtoId && !$produtoOrigem) $produtoId = 0;

        $imgPrincipal = null;
        $avisoImagem  = '';
        if (!empty($_FILES['imagem_principal']['tmp_name'])) {
            $imgPrincipal = $this->uploadImagem($_FILES['imagem_principal'], 'main', $titulo);
            if (!$imgPrincipal) $avisoImagem = ' A foto principal não pôde ser salva (' . ($this->ultimoErroUpload ?? 'motivo desconhecido') . ') — edite o produto pra tentar de novo.';
        } elseif ($produtoOrigem && !empty($produtoOrigem['imagem'])) {
            $imgPrincipal = $this->copiarImagemDoEstoque($produtoOrigem['imagem'], 'main', $titulo);
            if (!$imgPrincipal) $avisoImagem = ' Não deu pra reaproveitar a foto do Estoque (' . ($this->ultimoErroUpload ?? 'motivo desconhecido') . ') — edite o produto e anexe uma foto manualmente.';
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
        } elseif ($produtoOrigem && !empty($produtoOrigem['imagens_galeria'])) {
            foreach ((json_decode($produtoOrigem['imagens_galeria'], true) ?: []) as $k => $arquivoOrigem) {
                if (count($galeria) >= self::GALERIA_MAX) break;
                $nome = $this->copiarImagemDoEstoque($arquivoOrigem, 'gal' . $k, $titulo);
                if ($nome) $galeria[] = $nome;
            }
        }

        $db = DB::pdo();
        $db->prepare(
            "INSERT INTO diretorio_produtos (empresa_id, produto_id, titulo, descricao, tags, valor, quantidade, imagem_principal, imagens_galeria)
             VALUES (?,?,?,?,?,?,?,?,?)"
        )->execute([
            $eid,
            $produtoId ?: null,
            $titulo,
            trim($this->post('descricao', '')),
            $tags,
            $valor,
            $quantidade,
            $imgPrincipal,
            $galeria ? json_encode($galeria) : null,
        ]);
        // Slug só dá pra calcular DEPOIS do INSERT (precisa do id novo pra desempate de
        // unicidade, mesmo padrão de MarketplaceController::gerarSlug()) — por isso é um
        // segundo UPDATE, não dá pra incluir no INSERT acima.
        $novoId = (int) $db->lastInsertId();
        $db->prepare("UPDATE diretorio_produtos SET slug = ? WHERE id = ?")
           ->execute([$this->gerarSlugProduto($titulo, $novoId), $novoId]);

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
            'ilimitado'     => $status['ilimitado'],
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

        $titulo     = trim($this->post('titulo', ''));
        $valor      = moeda_float($this->post('valor', '0'));
        $quantidade = max(1, (int) $this->post('quantidade', 1));
        $tags       = $this->sanitizarTags($this->post('tags', ''));
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
                $erroUpload = $_FILES['galeria']['error'][$k] ?? UPLOAD_ERR_NO_FILE;
                if ($erroUpload === UPLOAD_ERR_NO_FILE) continue;
                $erro = $this->validarImagem(['tmp_name' => $tmp, 'size' => $_FILES['galeria']['size'][$k], 'error' => $erroUpload]);
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
                $avisoImagem = ' A nova foto principal não pôde ser salva (' . ($this->ultimoErroUpload ?? 'motivo desconhecido') . ') — a foto anterior foi mantida.';
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

        // Recalcula o slug a cada edição (mesmo padrão de MarketplaceController::atualizar()) —
        // cobre tanto o título mudar quanto o backfill de produtos antigos sem slug (criados
        // antes desta coluna existir): editar uma vez já preenche sozinho, sem precisar de
        // script de migração de dado.
        $novoSlug = $this->gerarSlugProduto($titulo, (int) $id);

        DB::pdo()->prepare(
            "UPDATE diretorio_produtos SET titulo=?, slug=?, descricao=?, tags=?, valor=?, quantidade=?, imagem_principal=?, imagens_galeria=?
             WHERE id=? AND empresa_id=?"
        )->execute([
            $titulo,
            $novoSlug,
            trim($this->post('descricao', '')),
            $tags,
            $valor,
            $quantidade,
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

    /**
     * `empty($file['tmp_name'])` sozinho não distingue "usuário não escolheu foto nenhuma" de
     * "o PHP rejeitou o arquivo antes de chegar aqui" (upload_max_filesize/post_max_size do
     * servidor menor que o arquivo enviado) — nos dois casos tmp_name vem vazio. Sem checar
     * `error`, o segundo caso passava batido (retornava null, "sem problema") e o produto era
     * salvo silenciosamente sem foto nenhuma, sem o usuário nunca saber por quê (mesma causa já
     * documentada pra Empresa → Perfil Público, ver CLAUDE.md "Bug: upload de foto... falhava
     * em silêncio"). UPLOAD_ERR_NO_FILE (4) é o único caso legítimo de "nada foi anexado".
     */
    private function validarImagem(array $file): ?string
    {
        $erro = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($erro === UPLOAD_ERR_NO_FILE) return null;
        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            return 'Imagem grande demais para o limite do servidor. Reduza o tamanho e tente novamente.';
        }
        if ($erro !== UPLOAD_ERR_OK) {
            return 'Falha ao enviar a imagem. Tente novamente.';
        }
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

    /** URL amigável (/produto-diretorio/{slug}) — mesmo algoritmo de
     *  MarketplaceController::gerarSlug(), só que escopado em diretorio_produtos em vez de
     *  marketplace_anuncios (tabelas/produtos diferentes, sem risco de colisão entre elas). */
    private function gerarSlugProduto(string $titulo, int $id): string
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
        $base = trim($base, '-') ?: 'produto';

        $db = DB::pdo();
        $slug = $base;
        $i = 2;
        while (true) {
            $s = $db->prepare("SELECT id FROM diretorio_produtos WHERE slug = ? AND id != ?");
            $s->execute([$slug, $id]);
            if (!$s->fetch()) break;
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** Normaliza a lista de tags vinda do campo oculto (mesmo formato "CSV" já usado por
     *  `clientes.tags`/`empresas.especialidades`): quebra por vírgula, tira espaço nas pontas,
     *  descarta vazio, limita cada tag a 30 caracteres e o total a 10 tags (suficiente pra
     *  palavra-chave de busca sem virar um parágrafo disfarçado de tag), remove duplicata
     *  (case-insensitive, preservando a primeira grafia) — sempre grava de volta como CSV, sem
     *  espaço depois da vírgula, pra recompor de forma previsível tanto na tela quanto no
     *  JSON-LD/meta keywords da página pública (ver DiretorioController::produto()). */
    private function sanitizarTags(string $raw): ?string
    {
        $vistos = [];
        $tags = [];
        foreach (explode(',', $raw) as $tag) {
            $tag = trim(preg_replace('/\s+/', ' ', $tag));
            if ($tag === '') continue;
            $tag = mb_substr($tag, 0, 30, 'UTF-8');
            $chave = mb_strtolower($tag, 'UTF-8');
            if (isset($vistos[$chave])) continue;
            $vistos[$chave] = true;
            $tags[] = $tag;
            if (count($tags) >= 10) break;
        }
        return $tags ? implode(',', $tags) : null;
    }

    private function nomeArquivo(string $prefixo, string $titulo): string
    {
        $mapa = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
                 'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c','Á'=>'a','Ã'=>'a',
                 'Ç'=>'c','É'=>'e','Ó'=>'o'];
        $slug = $titulo ? mb_substr(trim(preg_replace('/[^a-z0-9-]+/', '-', strtr(mb_strtolower(trim($titulo), 'UTF-8'), $mapa)), '-'), 0, 60) : $prefixo;
        if ($slug === '') $slug = $prefixo;
        return $slug . '-' . $prefixo . '-' . $this->empresaId() . '-' . time() . '.webp';
    }

    /** Garante que storage/uploads/diretorio_produtos/ existe e é gravável — cria se faltar
     *  (mesmo tratamento que uploadImagem()/copiarImagemDoEstoque() já faziam, só que agora
     *  confirmando de verdade em vez de assumir que mkdir() funcionou), preenchendo
     *  $this->ultimoErroUpload com um motivo claro se não conseguir. */
    private function garantirDirUpload(): ?string
    {
        $dir = BASE_PATH . '/storage/uploads/diretorio_produtos/';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->ultimoErroUpload = 'não foi possível criar a pasta de upload no servidor (permissão negada?)';
            return null;
        }
        if (!is_writable($dir)) {
            $this->ultimoErroUpload = 'a pasta de upload no servidor não tem permissão de escrita';
            return null;
        }
        return $dir;
    }

    /** 800x800 WebP fundo branco via ImageService::padronizar() — mesmo "esquema" já usado no
     *  Marketplace, só que reaproveitando o serviço compartilhado em vez de duplicar GD cru. */
    private function uploadImagem(array $file, string $prefixo, string $titulo): ?string
    {
        $this->ultimoErroUpload = null;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::MIME_IMAGEM_PERMITIDA, true)) {
            $this->ultimoErroUpload = "formato não suportado ($mime)";
            return null;
        }
        if (($file['size'] ?? 0) > self::IMAGEM_TAMANHO_MAX) {
            $this->ultimoErroUpload = 'maior que 8MB';
            return null;
        }

        $dir = $this->garantirDirUpload();
        if ($dir === null) return null;

        $nome = $this->nomeArquivo($prefixo, $titulo);
        $ok = ImageService::padronizar($file['tmp_name'], $dir . $nome, ['tamanho' => 800, 'qualidade' => 87]);
        if (!$ok) $this->ultimoErroUpload = 'o servidor não conseguiu processar a imagem (ImageService::padronizar retornou falso — verifique suporte a WebP no GD)';
        return $ok ? $nome : null;
    }

    /**
     * Reaproveita uma foto que o produto já tem no Estoque (`produtos.imagem`/
     * `imagens_galeria`, sempre WebP 800x800 já padronizado por
     * `ProdutoController::uploadImagemProduto()`) como foto do Diretório — cópia direta em
     * disco, sem reprocessar (já está no formato certo). Usado quando o cadastro veio do
     * atalho "Cadastrar no Diretório" (produtos/form.php) e o usuário não anexou foto nenhuma
     * aqui: sem isso, o produto ficava sem foto na vitrine mesmo já tendo uma no Estoque,
     * porque o formulário nunca copiava — só reaproveitava título/valor/descrição.
     */
    private function copiarImagemDoEstoque(string $arquivoOrigem, string $prefixo, string $titulo): ?string
    {
        $this->ultimoErroUpload = null;

        $origem = BASE_PATH . '/storage/uploads/produtos/' . basename($arquivoOrigem);
        if (!is_file($origem)) {
            $this->ultimoErroUpload = 'a foto original não existe mais no Estoque';
            return null;
        }

        $dir = $this->garantirDirUpload();
        if ($dir === null) return null;

        $nome = $this->nomeArquivo($prefixo, $titulo);
        $ok = @copy($origem, $dir . $nome);
        if (!$ok) $this->ultimoErroUpload = 'falha ao copiar o arquivo no servidor';
        return $ok ? $nome : null;
    }
}
