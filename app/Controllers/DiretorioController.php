<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class DiretorioController extends Controller
{
    public function empresa(string $slug): void
    {
        $db = DB::pdo();

        $stmt = $db->prepare("SELECT * FROM empresas WHERE slug = ? AND ativo = 1 AND listagem_publica = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $empresa = $stmt->fetch();

        if (!$empresa) {
            http_response_code(404);
            $this->view('diretorio.404', ['titulo' => 'Empresa não encontrada'], 'landing');
            return;
        }

        // Contador de visitas — só perfis reivindicados (benefício de reivindicar),
        // 1x por sessão por empresa para não inflar com refresh/robô simples.
        if (!empty($empresa['reivindicada'])) {
            $vk = 'dir_visitou_' . (int) $empresa['id'];
            if (empty($_SESSION[$vk])) {
                $_SESSION[$vk] = 1;
                $db->prepare("UPDATE empresas SET visitas = visitas + 1 WHERE id = ?")->execute([$empresa['id']]);
                $db->prepare("INSERT INTO diretorio_visitas (empresa_id, dia, total) VALUES (?, CURDATE(), 1)
                              ON DUPLICATE KEY UPDATE total = total + 1")->execute([$empresa['id']]);
                $empresa['visitas'] = (int) ($empresa['visitas'] ?? 0) + 1;
            }
        }

        // Serviços
        $servicos = $db->prepare("SELECT * FROM empresa_servicos WHERE empresa_id = ? ORDER BY ordem, nome");
        $servicos->execute([$empresa['id']]);
        $servicos = $servicos->fetchAll();

        // Vitrine do Diretório: produtos cadastrados pela própria empresa direto pra aparecer
        // aqui (diretorio_produtos — desvencilhada do Marketplace de Peças, ver
        // DiretorioProdutosController) — benefício de plano pago ativo (mesmo critério de
        // perfil_diretorio_completo()), recalculado a cada carregamento — o plano vencer já
        // esconde os produtos sozinho, sem precisar mexer em nenhum registro. Sem plano
        // completo, a seção mostra um aviso "exclusivo pra assinante" em vez do grid de 10
        // vagas — não faz sentido um visitante qualquer ver 10 convites "cadastrar produto"
        // numa página que não é dele; $planoCompletoVitrine também decide se o botão "Produtos
        // em destaque" rola até a seção ou abre um modal explicando o benefício.
        $produtosVitrine = [];
        $planoCompletoVitrine = perfil_diretorio_completo($empresa);
        if ($planoCompletoVitrine) {
            // status IN ('ativo','vendido'): um produto vendido/esgotado não desaparece da
            // vitrine sozinho — continua ocupando a vaga (das 10) com aviso vermelho até a
            // empresa excluí-lo/desmarcá-lo (liberando espaço pra outro). estoque_atual (via
            // produto_id, quando o produto veio do Estoque) cobre o caso de a peça ter esgotado
            // por uma venda feita por fora (PDV, OS) sem ninguém lembrar de marcar como vendido.
            $pv = $db->prepare(
                "SELECT dp.id, dp.slug, dp.titulo, dp.valor, dp.imagem_principal, dp.status, p.estoque_atual
                 FROM diretorio_produtos dp
                 LEFT JOIN produtos p ON p.id = dp.produto_id
                 WHERE dp.empresa_id = ? AND dp.status IN ('ativo','vendido')
                 ORDER BY (dp.status = 'vendido') ASC, dp.criado_em DESC
                 LIMIT 10"
            );
            $pv->execute([$empresa['id']]);
            $produtosVitrine = $pv->fetchAll();
            foreach ($produtosVitrine as &$pvItem) {
                $pvItem['esgotado'] = $pvItem['status'] === 'vendido'
                    || ($pvItem['estoque_atual'] !== null && (int) $pvItem['estoque_atual'] <= 0);
            }
            unset($pvItem);
        }

        // Galeria de fotos (diferencial do perfil reivindicado)
        $fq = $db->prepare("SELECT * FROM empresa_fotos WHERE empresa_id = ? ORDER BY principal DESC, ordem, id");
        $fq->execute([$empresa['id']]);
        $fotos = $fq->fetchAll();

        // Avaliações aprovadas
        $avals = $db->prepare("SELECT * FROM diretorio_avaliacoes WHERE empresa_id = ? AND aprovado = 1 AND situacao = 'publicada' ORDER BY verificada DESC, criado_em DESC");
        $avals->execute([$empresa['id']]);
        $avaliacoes = $avals->fetchAll();

        // Estatísticas
        $stats = $db->prepare("SELECT AVG(nota) as media, COUNT(*) as total, SUM(nota=5) as c5, SUM(nota=4) as c4, SUM(nota=3) as c3, SUM(nota=2) as c2, SUM(nota=1) as c1 FROM diretorio_avaliacoes WHERE empresa_id = ? AND aprovado = 1 AND situacao = 'publicada'");
        $stats->execute([$empresa['id']]);
        $estatisticas = $stats->fetch();

        // Empresas similares (mesma cidade)
        $sim = $db->prepare("SELECT e.*, COALESCE(AVG(a.nota),0) as media_nota FROM empresas e LEFT JOIN diretorio_avaliacoes a ON a.empresa_id = e.id AND a.aprovado=1 WHERE e.id != ? AND e.cidade = ? AND e.ativo=1 AND e.listagem_publica=1 AND e.slug IS NOT NULL AND e.slug != '' GROUP BY e.id ORDER BY media_nota DESC LIMIT 4");
        $sim->execute([$empresa['id'], $empresa['cidade']]);
        $similares = $sim->fetchAll();

        // SEO: título e meta únicos por perfil
        $cidadeUf   = trim(($empresa['cidade'] ?? '') . (!empty($empresa['uf']) ? ', ' . $empresa['uf'] : ''));
        $nomeEmp    = $empresa['nome_fantasia'] ?: 'Assistência Técnica';
        $jaTemAT    = stripos($nomeEmp, 'assist') !== false; // nome já contém "assistência"?
        if ($cidadeUf) {
            $tituloFull = $jaTemAT
                ? "{$nomeEmp} em {$cidadeUf} | FixaOS"
                : "{$nomeEmp} — Assistência Técnica em {$cidadeUf} | FixaOS";
        } else {
            $tituloFull = $jaTemAT ? "{$nomeEmp} | FixaOS" : "{$nomeEmp} — Assistência Técnica | FixaOS";
        }
        // \s+ -> espaço único: a descrição livre da empresa pode ter quebra de linha (endereço
        // em linha própria, parágrafos) — sem normalizar antes de truncar, a meta/og/twitter
        // description saía com \n literal no meio do atributo HTML. strip_tags() primeiro
        // porque a descrição agora pode vir com HTML de verdade (editor rico com negrito/
        // listas, ver "Descrição pública editável" em CLAUDE.md) — sem isso, tag apareceria
        // literal ("<b>reparo</b> rápido...") na meta description.
        $metaBase = preg_replace('/\s+/u', ' ', trim(strip_tags($empresa['descricao_publica'] ?? '')));
        if ($metaBase === '') {
            $metaBase = $nomeEmp . ($cidadeUf ? " em {$cidadeUf}" : '')
                      . ' — veja serviços, avaliações de clientes, telefone e endereço no diretório FixaOS.';
        }
        if (mb_strlen($metaBase) <= 155) {
            $metaDesc = $metaBase;
        } else {
            $corte = mb_substr($metaBase, 0, 155);
            $ultimoEspaco = mb_strrpos($corte, ' ');
            $metaDesc = rtrim($ultimoEspaco !== false ? mb_substr($corte, 0, $ultimoEspaco) : $corte) . '…';
        }

        // SEO: indexa fichas com nome de verdade (título real pra página) — reivindicada indexa
        // sempre (um humano já verificou/reivindicou aquele perfil, sinal forte o bastante,
        // não importa se o nome bate com alguma palavra do setor). Não reivindicada só indexa
        // se o NOME também sinalizar que é do ramo de assistência técnica/conserto — filtra o
        // ruído da base de CNPJ importada (empresas de outro ramo dentro da mesma CNAE, ex.:
        // "Software Developer", "Via Legis", nome de pessoa física como MEI — achado real ao
        // amostrar os dados). Ver empresa_nome_indica_servico() (app/Helpers/functions.php).
        $temNome = trim((string)($empresa['nome_fantasia'] ?? '')) !== '';
        $noindex = !$temNome
            || (empty($empresa['reivindicada']) && !empresa_nome_indica_servico($empresa['nome_fantasia']));

        // og:image: a antiga foto de capa (banner real da fachada) virou cor de fundo do
        // título — sem foto nenhuma pra usar como imagem de preview, o link compartilhado no
        // WhatsApp cai no ícone genérico do FixaOS, mesmo fallback de sempre quando a empresa
        // nunca teve foto de capa.
        $appCfg    = require BASE_PATH . '/config/app.php';
        $baseUrl   = rtrim($appCfg['url'], '/');
        $canonical = $baseUrl . '/assistencias/' . $empresa['slug'];

        // Anúncio de banner: só em perfil REIVINDICADO sem plano pago ativo (mesmo critério de
        // perfil_diretorio_completo()) — é o "custo" do diretório grátis. Quem assina qualquer
        // plano do FixaOS libera o perfil sem anúncio, junto com o resto do perfil completo.
        // Posição fixa 'perfil' (ver diretorio_banner_posicoes()) — antes sorteava entre TODOS os
        // banners aprovados, não importa a posição comprada; agora só mostra o banner que de fato
        // pagou por ESTE espaço.
        $anuncio = null;
        if (!empty($empresa['reivindicada']) && !perfil_diretorio_completo($empresa)) {
            $anuncio = $this->bannerPosicao('perfil');
        }

        // Selo "N visualizações no perfil" (mais abaixo, no sidebar de contato): mostrava o
        // número real de graça pra QUALQUER visitante, mesmo perfil sem plano nenhum — dado
        // de analytics que devia ser benefício de quem paga, igual a "Visitas ao perfil" já é
        // na tela interna (Empresa → Perfil Público). Mesmo critério de "destaque PAGO" já
        // usado em RelatorioVisitasDiretorioService (`diretorio_destaque_ate` não-nulo e não
        // vencido — `_ate IS NULL` é a assinatura do destaque grátis já removido, nunca deve
        // contar aqui) somado a `perfil_diretorio_completo()` (plano do sistema completo).
        $destaquePago = ($empresa['diretorio_destaque'] ?? 'none') !== 'none'
            && !empty($empresa['diretorio_destaque_ate'])
            && $empresa['diretorio_destaque_ate'] >= date('Y-m-d');
        $visitasDesbloqueadas = perfil_diretorio_completo($empresa) || $destaquePago;

        // Seção de Avaliações liga/desliga em Empresa → Perfil Público (avaliacoes_publicas,
        // default 1 na coluna) — mas esse toggle só existe pra quem loga no painel, e perfil não
        // reivindicado não tem ninguém logado pra mexer nele. Por isso, sem reivindicar, a seção
        // fica desligada por padrão (não importa o valor gravado na coluna, herdado do DEFAULT
        // da migration pras ~28 mil fichas importadas de CNPJ) — evita convidar avaliação pública
        // pra um perfil que ninguém da empresa está de fato gerenciando/moderando. Reivindicando,
        // o dono ganha o toggle de verdade (ligado por padrão nesse momento) e pode desligar se
        // quiser. Desligada, some o resumo/lista/formulário — mas avaliações já feitas continuam
        // guardadas no banco, só não aparecem enquanto ficar desligada.
        $avaliacoesAtivas = !empty($empresa['reivindicada']) && (bool) ($empresa['avaliacoes_publicas'] ?? 1);
        if (!$avaliacoesAtivas) { $avaliacoes = []; $estatisticas = []; }

        $this->view('diretorio.empresa', compact('empresa','servicos','avaliacoes','estatisticas','similares','fotos','tituloFull','metaDesc','noindex','canonical','anuncio','avaliacoesAtivas','visitasDesbloqueadas','produtosVitrine','planoCompletoVitrine'), 'landing');
    }

    /**
     * Mini página pública de UM produto da vitrine do Diretório (diretorio_produtos) —
     * imagem principal + carrossel da galeria + "produtos relacionados" (outros produtos da
     * mesma empresa). Mesmo gate de plano pago ativo da vitrine em si (empresa()) — se o plano
     * vencer, a página some junto (404), sem precisar apagar o produto do banco.
     */
    public function produto(string $slug): void
    {
        $db = DB::pdo();

        $select = "SELECT dp.*, e.nome_fantasia, e.slug AS empresa_slug, e.cidade, e.uf,
                          e.whatsapp_publico, e.telefone, e.licenca_ate
                   FROM diretorio_produtos dp
                   JOIN empresas e ON e.id = dp.empresa_id
                   WHERE e.ativo = 1 AND e.listagem_publica = 1
                         AND e.slug IS NOT NULL AND e.slug != ''";

        // Tenta por slug primeiro (URL amigável); cai pro id numérico só como fallback — mesmo
        // padrão de MarketplaceController::peca(), pra produtos cadastrados antes da coluna
        // `slug` existir (ficam com slug NULL até a próxima edição, que já preenche sozinho).
        $stmt = $db->prepare("$select AND dp.slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $produto = $stmt->fetch();

        if (!$produto && ctype_digit($slug)) {
            $stmt = $db->prepare("$select AND dp.id = ? LIMIT 1");
            $stmt->execute([(int) $slug]);
            $produto = $stmt->fetch();
        }

        if (!$produto || !perfil_diretorio_completo($produto)) {
            http_response_code(404);
            $this->view('diretorio.404', ['titulo' => 'Produto não encontrado'], 'landing');
            return;
        }

        $appCfg  = require BASE_PATH . '/config/app.php';
        $baseUrl = rtrim($appCfg['url'], '/');

        // Acessou pelo id numérico mas já existe slug -> redireciona pra URL amigável (301),
        // mesmo tratamento de SEO já usado em MarketplaceController::peca().
        if (ctype_digit($slug) && !empty($produto['slug'])) {
            $this->redirect($baseUrl . '/produto-diretorio/' . $produto['slug'], 301);
            return;
        }

        $prodStmt = $db->prepare(
            "SELECT p.estoque_atual FROM produtos p WHERE p.id = ? LIMIT 1"
        );
        $prodStmt->execute([$produto['produto_id']]);
        $estoqueAtual = $produto['produto_id'] ? $prodStmt->fetchColumn() : null;
        $produto['esgotado'] = $produto['status'] === 'vendido'
            || ($estoqueAtual !== null && $estoqueAtual !== false && (int) $estoqueAtual <= 0);

        $relStmt = $db->prepare(
            "SELECT id, slug, titulo, valor, imagem_principal, status
             FROM diretorio_produtos
             WHERE empresa_id = ? AND id != ? AND status IN ('ativo','vendido')
             ORDER BY (status = 'vendido') ASC, criado_em DESC
             LIMIT 8"
        );
        $relStmt->execute([$produto['empresa_id'], $produto['id']]);
        $relacionados = $relStmt->fetchAll();

        $canonical = $baseUrl . '/produto-diretorio/' . ($produto['slug'] ?: $produto['id']);
        $nomeEmpresa = $produto['nome_fantasia'] ?: 'Assistência Técnica';
        $tituloFull  = $produto['titulo'] . ' — ' . $nomeEmpresa . ' | FixaOS';
        $metaDesc    = 'R$ ' . number_format((float) $produto['valor'], 2, ',', '.') . ' — '
                     . $produto['titulo'] . ', anunciado por ' . $nomeEmpresa
                     . (($produto['cidade'] ?? '') ? ' (' . $produto['cidade'] . '/' . $produto['uf'] . ')' : '') . '.';

        $this->view('diretorio.produto', compact('produto', 'relacionados', 'tituloFull', 'metaDesc', 'canonical', 'baseUrl'), 'landing');
    }

    public function encontrar(): void
    {
        extract($this->buscarListagem($_GET));

        // SEO: título e meta únicos
        $tituloFull = 'Encontrar Assistência Técnica Perto de Você — FixaOS';
        $metaDesc   = 'Busque assistências técnicas por CEP, cidade ou serviço e encontre a mais próxima de você. Avaliações reais de clientes no diretório FixaOS.';

        // SEO: a página limpa /assistencias é indexável; buscas filtradas/paginadas
        // (resultados finos/duplicados) levam noindex,follow.
        $noindex = ($busca || $cep || $estado || $cidade || $bairro || $serv || $lat || $lng || $pag > 1);

        // Banners da busca geral — sempre exibidos pra qualquer visitante (diferente do banner
        // de perfil, que só aparece pra quem não paga o sistema completo: esta página não é de
        // UMA empresa específica, então não existe "empresa que paga" pra poupar do anúncio aqui).
        $bannerTopo    = $this->bannerPosicao('busca_topo');
        $bannerLateral = $this->bannerPosicao('busca_lateral');

        $this->view('diretorio.encontrar', compact(
            'empresas','mapaEmpresas','busca','cep','estado','cidade','bairro','raio','serv',
            'lat','lng','total','pag','limit','totalPags','servicos',
            'ordenar','notaMin','raioIgnorado','servicoIgnorado','bairroIgnorado','tituloFull','metaDesc','noindex',
            'bannerTopo','bannerLateral'
        ), 'landing');
    }

    /**
     * Página dedicada por cidade (`/assistencias/{uf}/{cidade-slug}`) — indexável, diferente da
     * busca geral filtrada (que leva noindex). Existe pra capturar buscas locais no Google tipo
     * "assistência técnica em Campinas" — hoje só o /assistencias puro e os perfis individuais
     * são indexáveis, então nenhuma página do diretório aparece pra esse tipo de busca direta.
     * Reaproveita a mesma view/lógica de encontrar() (buscarListagem()), só forçando estado/
     * cidade pela URL em vez do formulário, e trocando SEO por variantes indexáveis.
     */
    public function cidade(string $uf, string $cidadeSlug): void
    {
        $uf = strtoupper(trim($uf));
        if (!preg_match('/^[A-Z]{2}$/', $uf)) { $this->redirect(url('/assistencias')); return; }

        $db = DB::pdo();
        // Cidade não tem coluna de slug própria (é texto livre digitado por cada empresa) —
        // resolve o nome real comparando slugify() de cada cidade distinta do estado contra o
        // slug da URL. slugify() é o mesmo helper usado pra gerar o link (ver diretorio/encontrar.php
        // e SitemapController), então a ida e volta é consistente.
        $stmt = $db->prepare(
            "SELECT cidade, COUNT(*) AS total FROM empresas
              WHERE ativo = 1 AND listagem_publica = 1 AND slug IS NOT NULL AND slug <> ''
                AND uf = ? AND cidade IS NOT NULL AND cidade <> ''
              GROUP BY cidade"
        );
        $stmt->execute([$uf]);
        $cidadeReal = null; $totalCidade = 0;
        foreach ($stmt->fetchAll() as $row) {
            if (slugify($row['cidade']) === $cidadeSlug) {
                $cidadeReal = $row['cidade'];
                $totalCidade = (int) $row['total'];
                break;
            }
        }

        // Só existe página dedicada (indexável) com um mínimo de empresas — cidade com poucas
        // fichas vira "conteúdo raso" pro Google e pode prejudicar o domínio em vez de ajudar.
        // Abaixo do mínimo (ou cidade não encontrada), manda pra busca geral já filtrada.
        if (!$cidadeReal || $totalCidade < self::MIN_EMPRESAS_PAGINA_CIDADE) {
            $this->redirect(url('/assistencias') . '?estado=' . urlencode($uf) . '&cidade=' . urlencode($cidadeReal ?? ''));
            return;
        }

        $q = $_GET;
        $q['estado'] = $uf;
        $q['cidade'] = $cidadeReal;
        extract($this->buscarListagem($q));

        $cidadePagina = "{$cidadeReal}, {$uf}";
        $tituloFull = "Assistência Técnica em {$cidadePagina} — {$total} empresa" . ($total === 1 ? '' : 's')
                    . " avaliada" . ($total === 1 ? '' : 's') . " | FixaOS";
        $metaDesc   = "Encontre assistência técnica em {$cidadePagina}: telefone, endereço, avaliações reais "
                    . "de clientes e serviços oferecidos. Diretório gratuito FixaOS.";

        // Só a página "limpa" da cidade é indexável — qualquer filtro extra (busca, serviço,
        // bairro, raio/geo, nota mínima) ou paginação leva noindex,follow, mesmo critério de encontrar().
        $noindex = (bool) ($busca || $serv || $bairro || $raio || $lat || $lng || $notaMin > 0 || $pag > 1);

        $appCfg    = require BASE_PATH . '/config/app.php';
        $canonical = rtrim($appCfg['url'], '/') . '/assistencias/' . strtolower($uf) . '/' . $cidadeSlug;

        // Posição própria 'cidade' no topo (produto de anúncio distinto de 'busca_topo' — quem
        // compra aqui mira especificamente tráfego de busca local); a lateral é compartilhada
        // com a busca geral ('busca_lateral'), já que ambas são páginas de listagem.
        $bannerTopo    = $this->bannerPosicao('cidade');
        $bannerLateral = $this->bannerPosicao('busca_lateral');

        $this->view('diretorio.encontrar', compact(
            'empresas','mapaEmpresas','busca','cep','estado','cidade','bairro','raio','serv',
            'lat','lng','total','pag','limit','totalPags','servicos',
            'ordenar','notaMin','raioIgnorado','servicoIgnorado','bairroIgnorado',
            'tituloFull','metaDesc','noindex','canonical','cidadePagina',
            'bannerTopo','bannerLateral'
        ), 'landing');
    }

    /**
     * Gate de "conteúdo raso" das páginas de cidade — ver cidade() acima. Público porque
     * SitemapController usa o mesmo número pra decidir quais cidades entram no sitemap.xml
     * (mesmo critério: sem isso, o sitemap listaria uma URL que o próprio controller redireciona).
     */
    public const MIN_EMPRESAS_PAGINA_CIDADE = 3;

    /**
     * Banner aprovado e com assinatura ativa pra uma posição específica (ver
     * diretorio_banner_posicoes()) — no máximo 1 anunciante por posição por vez (mesma regra já
     * aplicada em DiretorioAnunciosController::contratar(), que bloqueia comprar uma posição já
     * ocupada por outra empresa). Sem sorteio: cada posição mostra só quem pagou por ELA.
     */
    private function bannerPosicao(string $posicao): ?array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT b.* FROM diretorio_banners b
             JOIN diretorio_assinaturas a ON a.id = b.assinatura_id
             WHERE b.aprovado = 1 AND b.imagem IS NOT NULL AND b.posicao = ?
               AND a.status = 'ativo' AND (a.data_fim IS NULL OR a.data_fim >= CURDATE())
             LIMIT 1"
        );
        $stmt->execute([$posicao]);
        return $stmt->fetch() ?: null;
    }

    /** Núcleo da busca/listagem do diretório, compartilhado entre encontrar() e cidade(). */
    private function buscarListagem(array $q): array
    {
        $db    = DB::pdo();
        $busca  = trim($q['busca']  ?? '');
        $cep    = preg_replace('/\D/', '', $q['cep'] ?? '');
        $estado = trim($q['estado'] ?? '');
        $cidade = trim($q['cidade'] ?? '');
        $bairro = trim($q['bairro'] ?? '');
        $raio   = (float)($q['raio']  ?? 0);
        $serv   = trim($q['servico'] ?? '');
        $lat    = (float)($q['lat'] ?? 0);
        $lng    = (float)($q['lng'] ?? 0);
        $pag    = max(1, (int)($q['pag'] ?? 1));
        $limit  = 12;
        $offset = ($pag - 1) * $limit;

        // ---- Filtros ----
        // baseWhere: sempre aplicado. filtros: "relaxáveis" (podem ser afrouxados se zerarem).
        $baseWhere = ["e.ativo = 1", "e.listagem_publica = 1", "e.slug IS NOT NULL", "e.slug != ''"];
        $filtros   = [];

        if ($busca) {
            $filtros[] = ['chave'=>'busca', 'sql'=>"(e.nome_fantasia LIKE ? OR e.descricao_publica LIKE ?)", 'params'=>["%$busca%","%$busca%"]];
        }
        if ($estado) $filtros[] = ['chave'=>'estado',  'sql'=>"e.uf = ?",        'params'=>[$estado]];
        if ($cidade) $filtros[] = ['chave'=>'cidade',  'sql'=>"e.cidade LIKE ?", 'params'=>["%$cidade%"]];
        if ($bairro) $filtros[] = ['chave'=>'bairro',  'sql'=>"e.bairro LIKE ?", 'params'=>["%$bairro%"]];
        if ($serv)   $filtros[] = ['chave'=>'servico', 'sql'=>"EXISTS (SELECT 1 FROM empresa_servicos es WHERE es.empresa_id = e.id AND es.nome LIKE ?)", 'params'=>["%$serv%"]];

        // Ordenação + nota mínima
        $ordenar = $q['ordenar'] ?? '';
        $notaMin = min(5, max(0, (float)($q['nota_min'] ?? 0)));

        // Distância (Haversine) — calculada sempre que houver lat/lng do usuário
        $temGeo = ($lat && $lng);
        $selectDistance = '';
        $having = [];
        if ($temGeo) {
            $selectDistance = ", (6371 * ACOS(LEAST(1, COS(RADIANS($lat)) * COS(RADIANS(e.latitude)) * COS(RADIANS(e.longitude) - RADIANS($lng)) + SIN(RADIANS($lat)) * SIN(RADIANS(e.latitude))))) AS distancia_km";
            // Só restringe por coordenada/distância quando há um raio definido.
            // Com raio "Qualquer" (0), calcula a distância só para exibir/ordenar, sem excluir quem não tem geo.
            if ($raio > 0) {
                $baseWhere[] = "e.latitude IS NOT NULL AND e.longitude IS NOT NULL";
                $having[]    = "distancia_km <= $raio";
            }
        }
        if ($notaMin > 0) $having[] = "media_nota >= $notaMin";

        // Monta WHERE/params ignorando filtros relaxados; HAVING opcionalmente sem o raio.
        $montar = function(array $excluir = []) use ($baseWhere, $filtros) {
            $where = $baseWhere; $params = [];
            foreach ($filtros as $f) {
                if (in_array($f['chave'], $excluir, true)) continue;
                $where[] = $f['sql'];
                foreach ($f['params'] as $p) $params[] = $p;
            }
            return [implode(' AND ', $where), $params];
        };
        $montarHaving = function(bool $semRaio) use ($having) {
            $h = $semRaio ? array_values(array_filter($having, fn($x) => strpos($x, 'distancia_km <=') === false)) : $having;
            return $h ? 'HAVING ' . implode(' AND ', $h) : '';
        };

        // Ordenação (whitelist p/ evitar SQL injection)
        $ordenarMap = [
            'relevancia' => 'em_destaque DESC, media_nota DESC, e.nome_fantasia ASC',
            'avaliacao'  => 'em_destaque DESC, media_nota DESC, total_avaliacoes DESC',
            'avaliacoes' => 'total_avaliacoes DESC, media_nota DESC',
            'az'         => 'e.nome_fantasia ASC',
        ];
        if ($temGeo) $ordenarMap['proximas'] = 'distancia_km IS NULL, distancia_km ASC';
        if ($ordenar === '')          $ordenar = $temGeo ? 'proximas' : 'relevancia';
        if (!isset($ordenarMap[$ordenar])) $ordenar = 'relevancia';
        $orderBy = 'e.reivindicada DESC, ' . $ordenarMap[$ordenar];

        // Conta respeitando WHERE (com exclusões) + HAVING (com/sem raio)
        $contar = function(array $excluir, bool $semRaio) use ($db, $montar, $montarHaving, $selectDistance) {
            [$w, $p] = $montar($excluir);
            $havC = $montarHaving($semRaio);
            $st = $db->prepare("SELECT COUNT(*) FROM (SELECT e.id, COALESCE(AVG(a.nota),0) AS media_nota $selectDistance FROM empresas e LEFT JOIN diretorio_avaliacoes a ON a.empresa_id = e.id AND a.aprovado = 1 WHERE $w GROUP BY e.id $havC) t");
            $st->execute($p);
            return (int)$st->fetchColumn();
        };

        // Relaxamento progressivo: se zerar, afrouxa raio → serviço → bairro, avisando o usuário.
        // (As coordenadas ainda são a nível de cidade; e nem toda empresa tem todos os serviços cadastrados.)
        $raioIgnorado = $servicoIgnorado = $bairroIgnorado = false;
        $excluir = []; $semRaio = false;
        $total = $contar($excluir, $semRaio);

        if ($total === 0 && $raio > 0 && $temGeo) {
            $t = $contar($excluir, true);
            if ($t > 0) { $semRaio = true; $raioIgnorado = true; $total = $t; }
        }
        if ($total === 0 && $serv) {
            $t = $contar(array_merge($excluir, ['servico']), $semRaio);
            if ($t > 0) { $excluir[] = 'servico'; $servicoIgnorado = true; $total = $t; }
        }
        if ($total === 0 && $bairro) {
            $t = $contar(array_merge($excluir, ['bairro']), $semRaio);
            if ($t > 0) { $excluir[] = 'bairro'; $bairroIgnorado = true; $total = $t; }
        }

        // Estado final das cláusulas para as consultas de listagem/mapa
        [$whereStr, $params] = $montar($excluir);
        $havingClause = $montarHaving($semRaio);

        $stmt = $db->prepare("
            SELECT e.*,
                   COALESCE(AVG(a.nota), 0) AS media_nota,
                   COUNT(a.id) AS total_avaliacoes,
                   CASE WHEN e.diretorio_destaque != 'none' AND (e.diretorio_destaque_ate IS NULL OR e.diretorio_destaque_ate >= CURDATE()) THEN 1 ELSE 0 END AS em_destaque
                   $selectDistance
            FROM empresas e
            LEFT JOIN diretorio_avaliacoes a ON a.empresa_id = e.id AND a.aprovado = 1
            WHERE $whereStr
            GROUP BY e.id
            $havingClause
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $empresas = $stmt->fetchAll();

        // Empresas para o MAPA: mais que a página (até 600), só as que têm coordenada, mesmos filtros.
        $mapaStmt = $db->prepare("
            SELECT e.*, 0 AS media_nota, 0 AS total_avaliacoes,
                   CASE WHEN e.diretorio_destaque != 'none' AND (e.diretorio_destaque_ate IS NULL OR e.diretorio_destaque_ate >= CURDATE()) THEN 1 ELSE 0 END AS em_destaque
                   $selectDistance
            FROM empresas e
            WHERE $whereStr AND e.latitude IS NOT NULL AND e.longitude IS NOT NULL
            GROUP BY e.id
            $havingClause
            LIMIT 600
        ");
        $mapaStmt->execute($params);
        $mapaEmpresas = $mapaStmt->fetchAll();

        $servicos = $db->query("SELECT nome, COUNT(*) as total FROM empresa_servicos GROUP BY nome ORDER BY total DESC LIMIT 16")->fetchAll();
        $totalPags = ceil($total / $limit);

        return compact(
            'empresas','mapaEmpresas','busca','cep','estado','cidade','bairro','raio','serv',
            'lat','lng','total','pag','limit','totalPags','servicos',
            'ordenar','notaMin','raioIgnorado','servicoIgnorado','bairroIgnorado'
        );
    }

    /** Busca instantânea (AJAX) por nome da empresa — usada no autocomplete do diretório. */
    public function buscarAjax(): void
    {
        $q      = trim($_GET['q'] ?? '');
        $lat    = (float)($_GET['lat'] ?? 0);
        $lng    = (float)($_GET['lng'] ?? 0);
        $temGeo = ($lat && $lng);

        if (mb_strlen($q) < 2 && !$temGeo) { $this->json(['itens' => []]); return; }

        $db      = DB::pdo();
        $appCfg  = require BASE_PATH . '/config/app.php';
        $baseUrl = rtrim($appCfg['url'], '/');

        $where  = ['e.ativo = 1', 'e.listagem_publica = 1', "e.slug IS NOT NULL", "e.slug != ''"];
        $params = [];

        if ($q !== '') {
            $where[]  = '(e.nome_fantasia LIKE ? OR e.cidade LIKE ? OR e.bairro LIKE ? OR e.uf LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        $selectDist = '';
        $having     = '';
        $orderGeo   = '';
        if ($temGeo) {
            $selectDist = ", (6371 * ACOS(LEAST(1, COS(RADIANS($lat)) * COS(RADIANS(e.latitude)) * COS(RADIANS(e.longitude) - RADIANS($lng)) + SIN(RADIANS($lat)) * SIN(RADIANS(e.latitude))))) AS distancia_km";
            $where[]  = 'e.latitude IS NOT NULL AND e.longitude IS NOT NULL';
            $having   = 'HAVING distancia_km <= 30';
            $orderGeo = 'distancia_km ASC,';
        }

        $sql = "
            SELECT e.slug, e.nome_fantasia, e.cidade, e.bairro, e.uf, e.logo, e.reivindicada$selectDist,
                   CASE WHEN e.diretorio_destaque != 'none' AND (e.diretorio_destaque_ate IS NULL OR e.diretorio_destaque_ate >= CURDATE()) THEN 1 ELSE 0 END AS em_destaque,
                   COALESCE(AVG(a.nota), 0) AS media_nota,
                   COUNT(a.id) AS total_avaliacoes
            FROM empresas e
            LEFT JOIN diretorio_avaliacoes a ON a.empresa_id = e.id AND a.aprovado = 1
            WHERE " . implode(' AND ', $where) . "
            GROUP BY e.id
            $having
            ORDER BY e.reivindicada DESC, $orderGeo em_destaque DESC, total_avaliacoes DESC, media_nota DESC, e.nome_fantasia ASC
            LIMIT 8
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $itens = array_map(function($e) use ($baseUrl, $temGeo) {
            $local = trim(($e['bairro'] ? $e['bairro'] . ', ' : '') . ($e['cidade'] ?? '') . ($e['uf'] ? '/' . $e['uf'] : ''), ', ');
            return [
                'slug'      => $e['slug'],
                'nome'      => $e['nome_fantasia'],
                'local'     => $local,
                'distancia' => ($temGeo && isset($e['distancia_km'])) ? round((float)$e['distancia_km'], 1) : null,
                'logo'      => $e['logo'] ? $baseUrl . '/uploads/' . $e['logo'] : null,
                'inicial'   => mb_strtoupper(mb_substr($e['nome_fantasia'], 0, 1)),
                'nota'      => round((float)$e['media_nota'], 1),
                'aval'      => (int)$e['total_avaliacoes'],
                'destaque'  => (bool)$e['em_destaque'],
                'url'       => $baseUrl . '/assistencias/' . $e['slug'],
            ];
        }, $rows);

        $this->json(['itens' => $itens]);
    }

    /** /encontrar foi unificado em /assistencias — redireciona (301) preservando os filtros. */
    public function encontrarLegado(): void
    {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        header('Location: ' . url('/assistencias') . ($qs ? '?' . $qs : ''), true, 301);
        exit;
    }

    public function geocode(): void
    {
        $cep = preg_replace('/\D/', '', $_GET['cep'] ?? '');
        if (strlen($cep) !== 8) { $this->json(['error' => 'CEP inválido']); }

        // ViaCEP para cidade/estado
        $viacep = @file_get_contents("https://viacep.com.br/ws/$cep/json/");
        $dados  = $viacep ? json_decode($viacep, true) : null;

        if (!$dados || isset($dados['erro'])) { $this->json(['error' => 'CEP não encontrado']); }

        // Nominatim para coordenadas
        $query   = urlencode("{$dados['logradouro']}, {$dados['localidade']}, {$dados['uf']}, Brasil");
        $ctx     = stream_context_create(['http' => ['header' => 'User-Agent: FixaOS/1.0']]);
        $nominatim = @file_get_contents("https://nominatim.openstreetmap.org/search?q=$query&format=json&limit=1", false, $ctx);
        $coord   = $nominatim ? json_decode($nominatim, true) : [];

        $lat = $coord[0]['lat'] ?? null;
        $lng = $coord[0]['lon'] ?? null;

        // Fallback: buscar por cidade/estado
        if (!$lat) {
            $q2  = urlencode("{$dados['localidade']}, {$dados['uf']}, Brasil");
            $nom2 = @file_get_contents("https://nominatim.openstreetmap.org/search?q=$q2&format=json&limit=1", false, $ctx);
            $c2  = $nom2 ? json_decode($nom2, true) : [];
            $lat = $c2[0]['lat'] ?? null;
            $lng = $c2[0]['lon'] ?? null;
        }

        $this->json([
            'cidade' => $dados['localidade'],
            'estado' => $dados['uf'],
            'bairro' => $dados['bairro'],
            'lat'    => $lat ? (float)$lat : null,
            'lng'    => $lng ? (float)$lng : null,
        ]);
    }

    public function avaliar(string $slug): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT id, avaliacoes_publicas, reivindicada FROM empresas WHERE slug = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $empresa = $stmt->fetch();

        // Empresa desligou a seção de avaliações (ou nunca foi reivindicada, ver empresa() acima
        // — sem reivindicar, a seção fica desligada por padrão independente da coluna) — não
        // aceita novo envio, mesmo via POST direto.
        if (!$empresa || !csrf_verify() || empty($empresa['reivindicada']) || empty($empresa['avaliacoes_publicas'])) {
            $this->redirect(url('/assistencias/' . $slug));
        }

        $nome    = trim($this->post('nome', ''));
        $email   = trim($this->post('email', ''));
        $nota    = (int)$this->post('nota', 0);
        $coment  = trim($this->post('comentario', ''));
        $captcha = (int)$this->post('captcha', -1);

        // Validar CAPTCHA
        $ca = (int)($_SESSION['captcha_a'] ?? 0);
        $cb = (int)($_SESSION['captcha_b'] ?? 0);
        unset($_SESSION['captcha_a'], $_SESSION['captcha_b']);

        if ($captcha !== ($ca + $cb)) {
            $this->flash('error', 'Verificação anti-robô incorreta. Tente novamente.');
            $this->redirect(url('/assistencias/' . $slug . '#avaliacoes'));
        }

        if (!$nome || $nota < 1 || $nota > 5) {
            $this->flash('error', 'Preencha seu nome e selecione uma nota.');
            $this->redirect(url('/assistencias/' . $slug . '#avaliacoes'));
        }

        $db->prepare("INSERT INTO diretorio_avaliacoes (empresa_id, nome, email, nota, comentario, aprovado) VALUES (?,?,?,?,?,0)")
           ->execute([$empresa['id'], $nome, $email ?: null, $nota, $coment ?: null]);

        $this->flash('success', 'Avaliação enviada! Ela será publicada após aprovação. Obrigado pelo feedback.');
        $this->redirect(url('/assistencias/' . $slug . '#avaliacoes'));
    }

    // ── Reivindicar perfil (empresa semeada, reivindicada=0) ─────────────
    public function reivindicar(string $id): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT id, slug, nome_fantasia, reivindicada, cnpj FROM empresas WHERE id=? AND ativo=1 AND listagem_publica=1");
        $stmt->execute([(int)$id]);
        $emp = $stmt->fetch();
        if (!$emp) { $this->redirect(url('/assistencias')); }
        if (!empty($emp['reivindicada'])) { $this->redirect(url('/assistencias/' . $emp['slug'])); }

        $nome  = trim($this->post('nome', ''));
        $email = trim($this->post('email', ''));
        $whats = trim($this->post('whatsapp', ''));
        $senha = (string) $this->post('senha', '');
        $cnpjInput = preg_replace('/\D/', '', (string) $this->post('cnpj', ''));
        $back  = url('/assistencias/' . $emp['slug']);

        if (!$nome || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6) {
            $this->flash('error', 'Preencha seu nome, um e-mail válido e uma senha (mínimo 6 caracteres).');
            $this->redirect($back);
        }
        if (!cnpj_valido($cnpjInput)) {
            $this->flash('error', 'Informe o CNPJ da empresa (válido) — é assim que confirmamos que ela é sua.');
            $this->redirect($back);
        }

        // Se já existe um usuário com esse e-mail, manda pro login (não recria).
        $chk = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $chk->execute([$email]);
        if ((int) $chk->fetchColumn() > 0) {
            $this->flash('error', 'Já existe uma conta com esse e-mail. Faça login para gerenciar seu perfil.');
            $this->redirect(url('/login'));
        }

        $db->exec("CREATE TABLE IF NOT EXISTS diretorio_reivindicacoes (
          id INT AUTO_INCREMENT PRIMARY KEY,
          empresa_id INT NOT NULL,
          nome VARCHAR(100) NOT NULL,
          email VARCHAR(100) NOT NULL,
          whatsapp VARCHAR(20) NULL,
          senha_hash VARCHAR(255) NOT NULL,
          status ENUM('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
          criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          processado_em TIMESTAMP NULL,
          INDEX(empresa_id), INDEX(status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Coluna de auditoria do CNPJ informado (best-effort — só na 1ª vez).
        try { $db->exec("ALTER TABLE diretorio_reivindicacoes ADD COLUMN cnpj_informado VARCHAR(18) NULL"); } catch (\Throwable $e) { /* já existe */ }

        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

        // ── VERIFICAÇÃO DE POSSE ────────────────────────────────────────────
        // O CNPJ informado tem que bater com o CNPJ público da ficha (veio da base
        // da Receita no seed). Bateu → assume a ficha na hora. Não bateu → vai pra
        // FILA DE MODERAÇÃO do master (o espertinho não sequestra a ficha de ninguém).
        $cnpjFicha = preg_replace('/\D/', '', (string) ($emp['cnpj'] ?? ''));
        $match     = $cnpjFicha !== '' && hash_equals($cnpjFicha, $cnpjInput);

        // Capta o e-mail como lead (nos dois caminhos).
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS lista_espera (
              id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL,
              origem VARCHAR(40) NOT NULL DEFAULT 'landing', convidado TINYINT NOT NULL DEFAULT 0,
              convidado_em TIMESTAMP NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->prepare("INSERT IGNORE INTO lista_espera (email, origem) VALUES (?, 'reivindicacao')")
               ->execute([mb_strtolower(mb_substr($email,0,150))]);
        } catch (\Throwable $e) { /* silencioso */ }

        if (!$match) {
            // NÃO assume a ficha — cria um pedido PENDENTE pro master verificar.
            try {
                $db->prepare("INSERT INTO diretorio_reivindicacoes (empresa_id,nome,email,whatsapp,senha_hash,cnpj_informado,status)
                              VALUES (?,?,?,?,?,?, 'pendente')")
                   ->execute([(int)$emp['id'], mb_substr($nome,0,100), mb_substr($email,0,100), mb_substr($whats,0,20), $senhaHash, mb_substr($cnpjInput,0,18)]);
            } catch (\Throwable $e) {
                $this->flash('error', 'Não foi possível concluir agora. Tente novamente em instantes.');
                $this->redirect($back);
            }
            // Avisa o master do pedido pendente (best-effort).
            try {
                $en = htmlspecialchars($nome); $ee = htmlspecialchars($email); $ew = htmlspecialchars($whats);
                $ec = htmlspecialchars($cnpjInput); $ef = htmlspecialchars($cnpjFicha ?: '—');
                \App\Services\EmailService::send(
                    'suporte@fixaos.com.br', 'FixaOS',
                    'Reivindicação PENDENTE (CNPJ não confere) — ' . htmlspecialchars($emp['nome_fantasia'] ?? ''),
                    "<p><b>Pedido de reivindicação para verificar</b> (o CNPJ informado não bateu com o da ficha):</p>
                     <ul><li><b>Empresa:</b> " . htmlspecialchars($emp['nome_fantasia'] ?? '') . "</li>
                     <li><b>CNPJ da ficha:</b> {$ef}</li><li><b>CNPJ informado:</b> {$ec}</li>
                     <li><b>Solicitante:</b> {$en} — {$ee} — {$ew}</li></ul>
                     <p>Aprove ou rejeite em /master/reivindicacoes.</p>"
                );
            } catch (\Throwable $e) { /* silencioso */ }

            $this->flash('success', 'Recebemos sua solicitação! 🔒 Como o CNPJ informado não confere com o cadastro público desta empresa, ela passa por uma verificação rápida da nossa equipe. Assim que confirmarmos, você recebe o acesso por e-mail.');
            $this->redirect($back);
        }

        // ── MATCH: CNPJ confere → assume a ficha na hora (auto-aprovada) ─────
        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO usuarios (empresa_id,nome,email,senha,perfil,ativo) VALUES (?,?,?,?, 'admin', 1)")
               ->execute([(int)$emp['id'], mb_substr($nome,0,100), mb_substr($email,0,100), $senhaHash]);

            $db->prepare("UPDATE empresas
                            SET reivindicada = 1,
                                tipo_conta = 'diretorio',
                                email = COALESCE(NULLIF(email,''), ?),
                                whatsapp_publico = COALESCE(NULLIF(whatsapp_publico,''), ?)
                          WHERE id = ?")
               ->execute([mb_substr($email,0,100), mb_substr($whats,0,20), (int)$emp['id']]);

            $db->prepare("INSERT INTO diretorio_reivindicacoes (empresa_id,nome,email,whatsapp,senha_hash,cnpj_informado,status,processado_em)
                          VALUES (?,?,?,?,?,?, 'aprovada', NOW())")
               ->execute([(int)$emp['id'], mb_substr($nome,0,100), mb_substr($email,0,100), mb_substr($whats,0,20), $senhaHash, mb_substr($cnpjInput,0,18)]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $this->flash('error', 'Não foi possível concluir agora. Tente novamente em instantes.');
            $this->redirect($back);
        }

        // Login automático → cai direto no gerenciamento do perfil público.
        try {
            $stmtLogin = $db->prepare(
                "SELECT u.*, e.nome_fantasia AS empresa_nome FROM usuarios u
                 JOIN empresas e ON e.id = u.empresa_id
                 WHERE u.empresa_id = ? AND u.email = ? LIMIT 1"
            );
            $stmtLogin->execute([(int)$emp['id'], $email]);
            if ($novo = $stmtLogin->fetch()) { \App\Core\Auth::login($novo, []); }
        } catch (\Throwable $e) { /* se falhar, redireciona pro login abaixo */ }

        // Aviso ao dono (best-effort): novo perfil reivindicado (lead capturado).
        try {
            $empNome = htmlspecialchars($emp['nome_fantasia'] ?? '');
            $en = htmlspecialchars($nome); $ee = htmlspecialchars($email); $ew = htmlspecialchars($whats);
            \App\Services\EmailService::send(
                'suporte@fixaos.com.br', 'FixaOS',
                'Novo perfil reivindicado (lead) — ' . $empNome,
                "<p>Uma empresa <b>reivindicou o perfil</b> no diretório (conta só-diretório, lead capturado):</p>
                 <ul>
                   <li><b>Empresa:</b> {$empNome}</li>
                   <li><b>Solicitante:</b> {$en}</li>
                   <li><b>E-mail:</b> {$ee}</li>
                   <li><b>WhatsApp:</b> {$ew}</li>
                 </ul>
                 <p>Ela já pode gerenciar a página pública. Convide para testar o sistema quando estiver pronto.</p>"
            );
        } catch (\Throwable $e) { /* silencioso */ }

        // Agradecimento + guia detalhado ao solicitante (best-effort, não trava o fluxo).
        try {
            \App\Services\EmailService::perfilReivindicado($email, $nome, $emp['nome_fantasia'] ?? '');
        } catch (\Throwable $e) { /* silencioso */ }

        $this->flash('success', 'Perfil reivindicado com sucesso! Agora você pode editar a página da sua empresa. Quando o sistema completo abrir, avisaremos você por e-mail. 🎉');
        $this->redirect(\App\Core\Auth::check() ? url('/empresa/perfil-publico') : url('/login'));
    }

    // ── Cadastrar NOVA empresa no diretório (grátis) ─────────────────────
    // Independente do CADASTRO_ABERTO: cria uma conta só-diretório (não dá acesso
    // ao sistema completo — isso vem por convite). É pra empresa que ainda não está listada.
    public function cadastrarForm(): void
    {
        if (\App\Core\Auth::check()) { $this->redirect(url('/empresa/perfil-publico')); }
        $this->view('diretorio.cadastrar', ['titulo' => 'Cadastre sua empresa grátis'], 'landing');
    }

    public function cadastrarSalvar(): void
    {
        if (\App\Core\Auth::check()) { $this->redirect(url('/empresa/perfil-publico')); }
        $back = url('/diretorio/cadastrar');

        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        // Honeypot anti-bot
        if (trim((string) $this->post('website', '')) !== '') { $this->redirect(url('/assistencias')); }

        // Cadastro em UMA tela só: dados de acesso + dados da empresa juntos (antes era um
        // passo 2 separado, só depois de logar — muita gente criava a conta e nunca voltava
        // pra completar, ficando uma empresa "casca vazia" sem nome/cidade no diretório).
        $admNome = trim($this->post('nome', ''));
        $email   = trim($this->post('email', ''));
        $senha   = (string) $this->post('senha', '');
        $confirm = (string) $this->post('senha_confirm', '');
        $googleId  = trim($this->post('google_id', ''));
        $viaGoogle = $googleId !== '';

        $nomeEmpresa = trim($this->post('nome_fantasia', ''));
        $cidade      = trim($this->post('cidade', ''));
        $uf          = strtoupper(substr(trim($this->post('uf', '')), 0, 2));
        $whatsapp    = only_numbers($this->post('whatsapp_publico', ''));

        // Whitelist dos 27 estados — o <select> do formulário já só oferece esses valores,
        // mas um POST direto não passa pelo <select>, então valida de novo aqui.
        $ufsValidas = array_flip(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
            'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']);

        // Preserva tudo que já foi digitado (contexto Google + dados da empresa) se a
        // validação falhar — ninguém deveria ter que redigitar o que já preencheu.
        $manterContexto = function () use ($viaGoogle, $googleId, $email, $admNome, $nomeEmpresa, $cidade, $uf, $whatsapp) {
            if ($viaGoogle) {
                $_SESSION['google_signup'] = ['google_id' => $googleId, 'email' => $email, 'nome' => $admNome];
            }
            $_SESSION['cadastro_empresa_rascunho'] = compact('nomeEmpresa', 'cidade', 'uf', 'whatsapp');
        };

        if (!$admNome || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Informe seu nome e um e-mail válido.');
            $manterContexto(); $this->redirect($back);
        }
        if ($nomeEmpresa === '' || $cidade === '' || $uf === '' || !isset($ufsValidas[$uf])) {
            $this->flash('error', 'Informe o nome da empresa, a cidade e o estado.');
            $manterContexto(); $this->redirect($back);
        }
        if (!$viaGoogle) {
            if (strlen($senha) < 6) { $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.'); $manterContexto(); $this->redirect($back); }
            if ($senha !== $confirm) { $this->flash('error', 'As senhas não conferem.'); $manterContexto(); $this->redirect($back); }
        } elseif (strlen($senha) < 6) {
            $senha = bin2hex(random_bytes(16)); // segurança: garante hash forte mesmo se o hidden vier vazio
        }

        $db = DB::pdo();
        $chk = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $chk->execute([$email]);
        if ((int) $chk->fetchColumn() > 0) {
            $this->flash('error', 'Já existe uma conta com esse e-mail. Faça login para gerenciar seu perfil.');
            $this->redirect(url('/login'));
        }

        $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        // Já nasce PUBLICADA (listagem_publica=1) — diferente do fluxo antigo de 2 passos,
        // agora já temos nome+cidade suficientes pra um perfil útil no diretório desde já.
        // slug calculado logo depois do INSERT (precisa do id real pra resolver colisão).
        $db->beginTransaction();
        try {
            $db->prepare(
                "INSERT INTO empresas
                   (razao_social, nome_fantasia, cidade, uf, whatsapp_publico, email, email_publico,
                    tipo_conta, plano, reivindicada, listagem_publica, diretorio_publicado_em, ativo)
                 VALUES (?,?,?,?,?,?,?, 'diretorio', 'basico', 1, 1, NOW(), 1)"
            )->execute([
                mb_substr($admNome, 0, 150), mb_substr($nomeEmpresa, 0, 150), mb_substr($cidade, 0, 80),
                ($uf ?: null), ($whatsapp ?: null), mb_substr($email, 0, 100), mb_substr($email, 0, 120),
            ]);
            $empresaId = (int) $db->lastInsertId();

            $slug = slug_empresa_unico($nomeEmpresa, $cidade, $empresaId, null);
            $db->prepare("UPDATE empresas SET slug = ? WHERE id = ?")->execute([$slug, $empresaId]);

            $db->prepare("INSERT INTO usuarios (empresa_id,nome,email,senha,google_id,perfil,ativo) VALUES (?,?,?,?,?, 'admin', 1)")
               ->execute([$empresaId, mb_substr($admNome,0,100), mb_substr($email,0,100), $senhaHash, ($viaGoogle ? $googleId : null)]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $this->flash('error', 'Não foi possível concluir agora. Tente novamente em instantes.');
            $manterContexto(); $this->redirect($back);
        }

        // Registro (aprovado) pra aparecer no funil de Leads.
        try {
            $db->prepare("INSERT INTO diretorio_reivindicacoes (empresa_id,nome,email,whatsapp,senha_hash,status,processado_em)
                          VALUES (?,?,?,?,?, 'aprovada', NOW())")
               ->execute([$empresaId, mb_substr($admNome,0,100), mb_substr($email,0,100), null, $senhaHash]);
        } catch (\Throwable $e) { /* silencioso */ }

        // Lead na lista de espera (origem 'diretorio').
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS lista_espera (
              id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL,
              origem VARCHAR(40) NOT NULL DEFAULT 'landing', convidado TINYINT NOT NULL DEFAULT 0,
              convidado_em TIMESTAMP NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->prepare("INSERT IGNORE INTO lista_espera (email, origem) VALUES (?, 'diretorio')")
               ->execute([mb_strtolower(mb_substr($email,0,150))]);
        } catch (\Throwable $e) { /* silencioso */ }

        // Aviso ao dono + agradecimento/guia ao cadastrante (best-effort).
        try {
            $en = htmlspecialchars($admNome); $ee = htmlspecialchars($email);
            \App\Services\EmailService::send(
                'suporte@fixaos.com.br', 'FixaOS',
                'Nova conta de diretório criada — ' . $en,
                "<p>Alguém <b>criou uma conta no diretório</b> (perfil já publicado):</p>
                 <ul><li><b>Responsável:</b> {$en}</li><li><b>E-mail:</b> {$ee}</li>
                 <li><b>Empresa:</b> " . htmlspecialchars($nomeEmpresa) . " — " . htmlspecialchars($cidade) . "/" . htmlspecialchars($uf) . "</li></ul>"
            );
        } catch (\Throwable $e) { /* silencioso */ }
        try {
            \App\Services\EmailService::perfilReivindicado($email, $admNome, '');
        } catch (\Throwable $e) { /* silencioso */ }

        // Login automático → cai no editor do perfil público, já publicado, pra quem quiser
        // enriquecer com logo/fotos/serviços/redes sociais (tudo opcional a partir daqui).
        try {
            $stmtLogin = $db->prepare(
                "SELECT u.*, e.nome_fantasia AS empresa_nome FROM usuarios u
                 JOIN empresas e ON e.id = u.empresa_id WHERE u.empresa_id = ? AND u.email = ? LIMIT 1"
            );
            $stmtLogin->execute([$empresaId, $email]);
            if ($novo = $stmtLogin->fetch()) { \App\Core\Auth::login($novo, []); }
        } catch (\Throwable $e) { /* redireciona pro login abaixo */ }

        unset($_SESSION['cadastro_empresa_rascunho']);
        $this->flash('success', 'Conta criada e seu perfil já está no ar! 🎉 Quando quiser, adicione logo, fotos e serviços pra deixar sua página ainda mais completa.');
        $this->redirect(\App\Core\Auth::check() ? url('/empresa/perfil-publico') : url('/login'));
    }

    // ── Cadastro RÁPIDO (sem login) ──────────────────────────────────────
    // Destino do convite via WhatsApp "cadastre-se grátis" (ver WhatsAppService::
    // conviteDiretorioCadastrar()) — fricção mínima de propósito: só nome + WhatsApp
    // obrigatórios, sem senha/e-mail/CNPJ. A ficha nasce PÚBLICA mas NÃO REIVINDICADA
    // (igual uma linha importada de CNPJ) porque não existe login nenhum criado aqui pra
    // gerenciá-la — se a empresa quiser editar depois, usa o mesmo "Esta é sua empresa?
    // Reivindique grátis" que qualquer ficha não reivindicada já mostra (cai na fila de
    // moderação do master, já que não há CNPJ aqui pra bater automaticamente).
    public function cadastroRapidoForm(): void
    {
        $this->view('diretorio.cadastro_rapido', ['titulo' => 'Cadastre sua empresa grátis'], 'landing');
    }

    public function cadastroRapidoSalvar(): void
    {
        $back = url('/diretorio/cadastro-rapido');

        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        // Honeypot anti-bot (mesmo campo/padrão de cadastrarSalvar()).
        if (trim((string) $this->post('website', '')) !== '') { $this->redirect(url('/assistencias')); }

        $nomeEmpresa = trim($this->post('nome_fantasia', ''));
        $whatsapp    = only_numbers($this->post('whatsapp', ''));
        $email       = trim($this->post('email', ''));

        if ($nomeEmpresa === '') {
            $this->flash('error', 'Informe o nome da empresa.');
            $this->redirect($back);
        }
        if (strlen($whatsapp) < 10) {
            $this->flash('error', 'Informe um WhatsApp válido, com DDD.');
            $this->redirect($back);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'O e-mail informado não é válido — deixe em branco se não tiver.');
            $this->redirect($back);
        }

        $db = DB::pdo();
        $db->beginTransaction();
        try {
            $db->prepare(
                "INSERT INTO empresas
                   (razao_social, nome_fantasia, whatsapp_publico, email,
                    tipo_conta, plano, reivindicada, listagem_publica, ativo)
                 VALUES (?,?,?,?, 'diretorio', 'basico', 0, 1, 1)"
            )->execute([
                mb_substr($nomeEmpresa, 0, 150), mb_substr($nomeEmpresa, 0, 100),
                mb_substr($whatsapp, 0, 20), ($email !== '' ? mb_substr($email, 0, 100) : null),
            ]);
            $empresaId = (int) $db->lastInsertId();

            $slug = slug_empresa_unico($nomeEmpresa, '', $empresaId, null);
            $db->prepare("UPDATE empresas SET slug = ? WHERE id = ?")->execute([$slug, $empresaId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $this->flash('error', 'Não foi possível concluir agora. Tente novamente em instantes.');
            $this->redirect($back);
        }

        // Logo é opcional — falha de upload não pode derrubar o cadastro em si.
        if (!empty($_FILES['logo']['name']) && (int) $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['logo']['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $tiposPermitidos, true) && $_FILES['logo']['size'] <= 4 * 1024 * 1024) {
                $dir = BASE_PATH . '/storage/uploads/logos/';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $nomeArq = 'empresa_' . $empresaId . '_' . time() . '.webp';
                if (\App\Services\ImageService::paraWebp($_FILES['logo']['tmp_name'], $dir . $nomeArq, 85, 600)) {
                    $db->prepare("UPDATE empresas SET logo = ? WHERE id = ?")->execute([$nomeArq, $empresaId]);
                }
            }
        }

        // Aviso ao dono (best-effort) — mesmo padrão dos outros cadastros do diretório.
        try {
            \App\Services\EmailService::send(
                'suporte@fixaos.com.br', 'FixaOS',
                'Nova empresa via cadastro rápido — ' . htmlspecialchars($nomeEmpresa),
                "<p>Uma empresa se cadastrou pelo <b>formulário rápido</b> (sem login, veio do convite por WhatsApp):</p>
                 <ul><li><b>Empresa:</b> " . htmlspecialchars($nomeEmpresa) . "</li>
                 <li><b>WhatsApp:</b> " . htmlspecialchars($whatsapp) . "</li>
                 <li><b>E-mail:</b> " . htmlspecialchars($email ?: '—') . "</li></ul>"
            );
        } catch (\Throwable $e) { /* silencioso */ }

        $this->view('diretorio.cadastro_rapido_sucesso', [
            'titulo'  => 'Cadastro concluído',
            'noindex' => true,
            'slug'    => $slug,
        ], 'landing');
    }
}
