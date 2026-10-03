<?php
/**
 * Página de estado do Diretório (`/assistencias/{uf}`) — mesma ideia da página de cidade
 * (ver diretorio/cidade.php), agregando TODAS as cidades da UF. Não tem rota própria: o Router
 * não suporta regex por segmento, então `/assistencias/{slug}` é a mesma rota física pro perfil
 * de empresa e pra esta página — DiretorioController::empresa() delega pra cá quando o "slug"
 * é na verdade uma UF válida.
 *
 * Reaproveita a mesma paleta/tipografia/classes `.dc-*` de cidade.php (não existe CSS
 * compartilhado entre views neste projeto — cada uma escreve o próprio `<style>` — mas os
 * nomes de classe batem de propósito, pra manter visualmente idêntico). Principais diferenças
 * de cidade.php:
 * - Sem filtro de bairro (não faz sentido no nível de estado) — o "segundo nível" de navegação
 *   aqui é por CIDADE: um combobox que, ao escolher, NAVEGA direto pra página daquela cidade
 *   (ou pra busca geral filtrada, se a cidade não tiver página própria), em vez de filtrar a
 *   própria página de estado — cada cidade já tem sua própria página dedicada, então filtrar
 *   duplicaria esse conteúdo.
 * - Mapa com cluster de marcadores (leaflet.markercluster, já usado em encontrar.php) — um
 *   estado pode ter muito mais empresas com coordenada do que uma única cidade.
 * - Itens da lista mostram "{bairro}, {cidade}" (a cidade varia por empresa, diferente da
 *   página de cidade onde é sempre a mesma).
 */
$appCfg   = require BASE_PATH . '/config/app.php';
$baseUrl  = rtrim($appCfg['url'], '/');
$ufLower  = strtolower($uf);
$urlEstadoBase = $baseUrl . '/assistencias/' . $ufLower;

$catPorSlug = array_column($categoriasPresentes, null, 'slug');

$iniciaisDe = function (string $nome): string {
    $nome = trim($nome);
    if ($nome === '') return '?';
    $partes = preg_split('/\s+/', $nome);
    $ini = mb_strtoupper(mb_substr($partes[0], 0, 1));
    if (count($partes) > 1) {
        $ini .= mb_strtoupper(mb_substr(end($partes), 0, 1));
    }
    return $ini;
};

$waLinkDe = function (array $e): ?string {
    $wa = preg_replace('/\D/', '', $e['whatsapp_publico'] ?? $e['telefone'] ?? '');
    if ($wa === '') return null;
    $nomeEmp = $e['nome_fantasia'] ?: 'sua assistência';
    return 'https://wa.me/55' . $wa . '?text=' . urlencode("Olá! Vi $nomeEmp no FixaOS e gostaria de mais informações.");
};

$pillsDe = function (array $e) use ($catPorSlug): array {
    $out = [];
    foreach ($e['categorias'] as $slug) {
        if (isset($catPorSlug[$slug])) $out[] = $catPorSlug[$slug];
    }
    return array_slice($out, 0, 3);
};

$localDe = function (array $e): string {
    $partes = array_filter([$e['bairro'] ?? '', $e['cidade'] ?? '']);
    return implode(', ', $partes);
};

// Companhias com coordenada — só essas viram marcador no mapa.
$comGeo = array_values(array_filter($lista, fn($e) => $e['latitude'] !== null && $e['longitude'] !== null));

$limiteSemFiltro = \App\Controllers\DiretorioController::CIDADE_LIMITE_SEM_BAIRRO;
$sugereBusca = $totalGeral > $limiteSemFiltro && count($cidadesAtendidas) > 1;

$introServicos = implode(', ', array_slice(array_column($categoriasPresentes, 'label'), 0, 3));
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início',      'item' => $baseUrl . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $nomeEstado,   'item' => $canonical],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'ItemList',
    'name'     => "Assistências técnicas em {$nomeEstado}",
    'numberOfItems' => count($lista),
    'itemListElement' => array_map(function ($e, $i) use ($baseUrl) {
        return [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'url'      => $baseUrl . '/assistencias/' . $e['slug'],
            'name'     => $e['nome_fantasia'],
        ];
    }, $lista, array_keys($lista)),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<script type="application/ld+json">
<?= json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(fn($f) => [
        '@type'          => 'Question',
        'name'           => $f['pergunta'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['resposta']],
    ], $faq),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<style>
.dc-page{
  --dc-bg:#F5F6FA; --dc-card:#FFFFFF; --dc-border:#E2E5EE;
  --dc-navy:#1B2A6B; --dc-navy-dark:#131F52;
  --dc-wa:#1C7C45; --dc-wa-dark:#15602F;
  --dc-orange:#B4410C;
  --dc-text:#1C2333; --dc-muted:#5B6472;
  background:var(--dc-bg); color:var(--dc-text);
  font-family:'IBM Plex Sans',sans-serif;
  padding-bottom:4rem;
}
.dc-page h1,.dc-page h2,.dc-page h3,.dc-page .dc-font-title{
  font-family:'Space Grotesk',sans-serif; color:var(--dc-navy); letter-spacing:-.01em;
}
.dc-page a{color:inherit}
.dc-container{max-width:1080px;margin:0 auto;padding:0 1.25rem}

/* Breadcrumb */
.dc-crumb{padding:1.1rem 0 .3rem;font-size:.82rem;color:var(--dc-muted)}
.dc-crumb a{color:var(--dc-muted);text-decoration:none}
.dc-crumb a:hover{color:var(--dc-navy);text-decoration:underline}
.dc-crumb span.sep{margin:0 .4rem;opacity:.6}

/* Hero */
.dc-hero{padding:.6rem 0 1.6rem}
.dc-hero h1{font-size:clamp(1.6rem,3.4vw,2.3rem);font-weight:700;line-height:1.2;margin-bottom:.5rem}
.dc-hero-meta{font-size:.86rem;color:var(--dc-muted);margin-bottom:.9rem;display:flex;flex-wrap:wrap;gap:.3rem 1rem}
.dc-hero-meta b{color:var(--dc-text)}
.dc-hero p{font-size:1rem;line-height:1.65;color:var(--dc-muted);max-width:720px}

/* Filtros (chips de serviço) — só informativos no estado, sem link pra página de serviço */
.dc-filtros{display:flex;flex-wrap:wrap;gap:.55rem;margin:1.3rem 0 2rem}
.dc-chip{
  display:inline-flex;align-items:center;gap:.4rem;padding:.42rem 1rem;border-radius:999px;
  font-size:.85rem;font-weight:600;border:1.5px solid var(--dc-border);background:var(--dc-card);
  color:var(--dc-text);text-decoration:none;
}
.dc-chip .cnt{opacity:.7;font-weight:500}

/* Seções */
.dc-sec{margin-bottom:2.6rem}
.dc-sec-head{display:flex;align-items:baseline;justify-content:space-between;gap:1rem;margin-bottom:1rem;flex-wrap:wrap}
.dc-sec h2{font-size:1.3rem;font-weight:700;margin:0}
.dc-sec-nota{font-size:.84rem;color:var(--dc-muted);margin-bottom:1.1rem}

/* Destaques */
.dc-destaques{display:grid;grid-template-columns:repeat(4,1fr);gap:1.1rem}
@media(max-width:900px){.dc-destaques{grid-template-columns:repeat(2,1fr)}}
@media(max-width:560px){.dc-destaques{grid-template-columns:1fr}}
.dc-card{
  background:var(--dc-card);border:1px solid var(--dc-border);border-radius:14px;
  padding:1.3rem;display:flex;flex-direction:column;gap:.7rem;
}
.dc-card-head{display:flex;flex-direction:column;align-items:center;gap:.6rem;text-align:center}
.dc-card-logo-wrap{
  width:100%;height:88px;border-radius:10px;background:var(--dc-bg);
  display:flex;align-items:center;justify-content:center;overflow:hidden;
}
.dc-card-logo{max-width:100%;max-height:100%;object-fit:contain}
.dc-card-logo-fallback{
  background:var(--dc-navy);color:#fff;font-weight:700;font-size:1.6rem;
  font-family:'Space Grotesk',sans-serif;
}
.dc-card-titulo{min-width:0}
.dc-card-nome{font-weight:700;font-size:1rem;color:var(--dc-text);line-height:1.3}
.dc-card-local{font-size:.8rem;color:var(--dc-muted)}
.dc-badge-assinante{
  display:inline-flex;align-items:center;gap:.3rem;background:#E8F5EB;color:var(--dc-wa-dark);
  border:1px solid #BFE6C8;border-radius:999px;padding:.2rem .65rem;font-size:.72rem;font-weight:700;
  width:fit-content;
}
.dc-card-desc{font-size:.86rem;color:var(--dc-muted);line-height:1.55}
.dc-pills{display:flex;flex-wrap:wrap;gap:.35rem}
.dc-pill{font-size:.72rem;font-weight:600;padding:.22rem .6rem;border-radius:999px;border:1px solid transparent}
.dc-card-actions{display:flex;gap:.5rem;margin-top:auto;padding-top:.4rem}
.dc-btn{
  flex:1;text-align:center;border-radius:9px;padding:.55rem .8rem;font-size:.84rem;font-weight:700;
  text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;
}
.dc-btn-wa{background:var(--dc-wa);color:#fff}
.dc-btn-wa:hover{background:var(--dc-wa-dark);color:#fff}
.dc-btn-perfil{background:#fff;color:var(--dc-navy);border:1.5px solid var(--dc-navy)}
.dc-btn-perfil:hover{background:var(--dc-navy);color:#fff}

/* Lista numerada */
.dc-lista{display:flex;flex-direction:column;border:1px solid var(--dc-border);border-radius:14px;overflow:hidden;background:var(--dc-card)}
.dc-item{display:flex;align-items:center;gap:.9rem;padding:.95rem 1.2rem;border-bottom:1px solid var(--dc-border)}
.dc-item:last-child{border-bottom:none}
.dc-item-num{width:26px;flex-shrink:0;color:var(--dc-muted);font-weight:700;font-size:.85rem;font-family:'Space Grotesk',sans-serif}
.dc-item-avatar{width:38px;height:38px;border-radius:9px;background:var(--dc-navy);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;flex-shrink:0;font-family:'Space Grotesk',sans-serif}
img.dc-item-avatar{object-fit:cover}
.dc-item-body{flex:1;min-width:0}
.dc-item-nome{font-weight:700;font-size:.92rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.dc-item-sub{font-size:.78rem;color:var(--dc-muted);margin-top:.1rem;display:flex;flex-wrap:wrap;gap:.3rem .6rem}
.dc-item-actions{flex-shrink:0;display:flex;gap:.5rem;align-items:center}
.dc-link-incompleto{color:var(--dc-orange);font-weight:700;font-size:.82rem;text-decoration:none;white-space:nowrap}
.dc-link-incompleto:hover{text-decoration:underline}
.dc-badge-incompleto{font-size:.68rem;font-weight:700;color:var(--dc-muted);background:#EEF0F4;border-radius:6px;padding:.1rem .4rem}
.dc-icon-wa{width:34px;height:34px;border-radius:50%;background:var(--dc-wa);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.dc-icon-wa:hover{background:var(--dc-wa-dark)}
.dc-aviso{background:#FFF7E8;border:1px solid #F3DDA8;color:#7A5A12;border-radius:10px;padding:.7rem 1rem;font-size:.84rem;margin-bottom:1rem}

/* Toolbar: busca + ir pra cidade */
.dc-toolbar{display:flex;flex-wrap:wrap;gap:.7rem;margin-bottom:1rem}
.dc-busca-wrap{position:relative;flex:1 1 240px}
.dc-busca-wrap i{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--dc-muted);font-size:.9rem}
.dc-busca-input{
  width:100%;padding:.62rem 1rem .62rem 2.3rem;border-radius:10px;border:1.5px solid var(--dc-border);
  background:var(--dc-card);color:var(--dc-text);font-family:'IBM Plex Sans',sans-serif;font-size:.88rem;
}
.dc-busca-input:focus{outline:none;border-color:var(--dc-navy)}
.dc-filtro-bairro-wrap{display:flex;align-items:center;gap:.5rem;flex:1 1 220px}
.dc-bairro-combo{position:relative;flex:1}
.dc-bairro-combo > i.bi-geo-alt{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--dc-muted);font-size:.9rem;pointer-events:none}
.dc-bairro-chevron{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);color:var(--dc-muted);font-size:.7rem;pointer-events:none}
.dc-bairro-input{
  width:100%;padding:.62rem 2rem .62rem 2.3rem;border-radius:10px;border:1.5px solid var(--dc-border);
  background:var(--dc-card);color:var(--dc-text);font-family:'IBM Plex Sans',sans-serif;font-size:.88rem;
}
.dc-bairro-input:focus{outline:none;border-color:var(--dc-navy)}
.dc-bairro-dropdown{
  display:none;position:absolute;left:0;right:0;top:calc(100% + 6px);
  background:var(--dc-card);border:1px solid var(--dc-border);border-radius:12px;
  box-shadow:0 14px 34px rgba(15,23,42,.14);z-index:60;max-height:280px;overflow-y:auto;
}
.dc-bairro-dropdown.aberto{display:block}
.dc-bairro-opcao{padding:.6rem 1rem;font-size:.86rem;cursor:pointer;border-bottom:1px solid var(--dc-border)}
.dc-bairro-opcao:last-child{border-bottom:none}
.dc-bairro-opcao:hover,.dc-bairro-opcao.realce{background:var(--dc-bg)}
.dc-bairro-opcao .cnt{color:var(--dc-muted);font-weight:500}
.dc-sem-resultado{font-size:.88rem;color:var(--dc-muted);text-align:center;padding:1.2rem;background:var(--dc-card);border:1px dashed var(--dc-border);border-radius:12px;margin-bottom:1rem}
@media(max-width:560px){
  .dc-filtro-bairro-wrap{width:100%;flex:1 1 100%}
}

/* Cidades + mapa */
.dc-mapa-wrap{display:grid;grid-template-columns:1.3fr 1fr;gap:1.2rem;align-items:start}
#dcMapa{height:380px;border-radius:14px;border:1px solid var(--dc-border);overflow:hidden;background:#E9ECF2}
.dc-bairros-box{background:var(--dc-card);border:1px solid var(--dc-border);border-radius:14px;padding:1.1rem;max-height:380px;overflow-y:auto}
.dc-bairros-box h3{font-size:.95rem;margin-bottom:.7rem}
.dc-bairro-chips{display:flex;flex-wrap:wrap;gap:.45rem}
.dc-bairro-chip{font-size:.78rem;font-weight:600;padding:.3rem .7rem;border-radius:999px;border:1px solid var(--dc-border);background:var(--dc-bg);text-decoration:none;color:var(--dc-text)}
.dc-bairro-chip:hover{border-color:var(--dc-navy)}

/* FAQ */
.dc-faq details{border:1px solid var(--dc-border);border-radius:10px;padding:.9rem 1.1rem;background:var(--dc-card);margin-bottom:.6rem}
.dc-faq summary{font-weight:700;font-size:.92rem;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:1rem}
.dc-faq summary::-webkit-details-marker{display:none}
.dc-faq summary::after{content:'+';font-size:1.2rem;color:var(--dc-muted);flex-shrink:0}
.dc-faq details[open] summary::after{content:'–'}
.dc-faq p{font-size:.88rem;color:var(--dc-muted);line-height:1.6;margin-top:.6rem}

/* CTA final */
.dc-cta{background:var(--dc-navy);border-radius:18px;padding:2.4rem 2rem;text-align:center;color:#fff}
.dc-cta h2{color:#fff;font-size:1.5rem;margin-bottom:.5rem}
.dc-cta p{color:#C7CEEF;font-size:.95rem;margin-bottom:1.3rem}
.dc-cta a{background:#fff;color:var(--dc-navy);font-weight:700;padding:.8rem 1.8rem;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem}
.dc-cta a:hover{background:#EEF0F4}

@media(max-width:820px){
  .dc-mapa-wrap{grid-template-columns:1fr}
  #dcMapa{height:280px}
}
@media(max-width:640px){
  .dc-item{flex-wrap:wrap}
  .dc-item-actions{width:100%;justify-content:flex-end}
}
</style>

<div class="dc-page">
  <div class="dc-container">

    <!-- Breadcrumb -->
    <nav class="dc-crumb" aria-label="breadcrumb">
      <a href="<?= url('/') ?>">Início</a>
      <span class="sep">›</span>
      <span><?= e($nomeEstado) ?></span>
    </nav>

    <!-- Hero -->
    <div class="dc-hero">
      <h1>Assistências técnicas em <?= e($nomeEstado) ?></h1>
      <div class="dc-hero-meta">
        <span><b><?= (int) $totalGeral ?></b> assistência<?= $totalGeral === 1 ? '' : 's' ?> técnica<?= $totalGeral === 1 ? '' : 's' ?> cadastrada<?= $totalGeral === 1 ? '' : 's' ?></span>
        <span><b><?= count($cidadesAtendidas) ?></b> cidade<?= count($cidadesAtendidas) === 1 ? '' : 's' ?> atendida<?= count($cidadesAtendidas) === 1 ? '' : 's' ?></span>
        <?php if ($atualizadoEm !== ''): ?>
        <span>Atualizado em <?= e(mes_ano_br($atualizadoEm)) ?></span>
        <?php endif; ?>
      </div>
      <p>
        Encontre <?= (int) $totalGeral ?> assistência<?= $totalGeral === 1 ? '' : 's' ?> técnica<?= $totalGeral === 1 ? '' : 's' ?> em <?= e($nomeEstado) ?>
        <?php if ($introServicos !== ''): ?>para <?= e(mb_strtolower($introServicos)) ?> e outros serviços<?php endif; ?>.
        Veja contato, serviços oferecidos e fale direto pelo WhatsApp com quem atende sua cidade.
      </p>
    </div>

    <?php if ($sugereBusca): ?>
    <div class="dc-aviso">
      <i class="bi bi-info-circle"></i> São <?= (int) $totalGeral ?> assistências em <?= e($nomeEstado) ?> — use a busca ou vá direto pra sua cidade abaixo pra encontrar mais rápido.
    </div>
    <?php endif; ?>

    <div class="dc-toolbar">
      <div class="dc-busca-wrap">
        <i class="bi bi-search"></i>
        <input type="text" id="dcBusca" class="dc-busca-input" placeholder="Buscar por nome..." autocomplete="off">
      </div>
      <?php if (!empty($cidadesAtendidas)): ?>
      <div class="dc-filtro-bairro-wrap" id="dcCidadeWrap">
        <div class="dc-bairro-combo">
          <i class="bi bi-geo-alt"></i>
          <input type="text" id="dcBuscaCidade" class="dc-bairro-input" placeholder="Ir para uma cidade..." autocomplete="off">
          <i class="bi bi-chevron-down dc-bairro-chevron"></i>
          <div class="dc-bairro-dropdown" id="dcCidadeDropdown">
            <?php foreach ($cidadesAtendidas as $c): ?>
            <div class="dc-bairro-opcao" data-url="<?= e($c['url']) ?>"><?= e($c['nome']) ?> <span class="cnt">(<?= (int) $c['total'] ?>)</span></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Serviços presentes no estado -->
    <?php if (!empty($categoriasPresentes)): ?>
    <div class="dc-filtros">
      <?php foreach ($categoriasPresentes as $cat): ?>
        <?php if ($cat['linkavel']): ?>
        <a href="<?= e($urlEstadoBase . '/' . $cat['slug_url']) ?>" class="dc-chip" style="border-color:<?= e($cat['cor_borda']) ?>;color:<?= e($cat['cor_texto']) ?>">
          <?= e($cat['label']) ?> <span class="cnt">(<?= (int) $cat['total'] ?>)</span>
        </a>
        <?php else: ?>
        <span class="dc-chip" style="opacity:.55" title="Ainda não há empresas suficientes pra uma página própria deste serviço neste estado">
          <?= e($cat['label']) ?> <span class="cnt">(<?= (int) $cat['total'] ?>)</span>
        </span>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Destaques -->
    <?php if (!empty($destaques)): ?>
    <div class="dc-sec">
      <div class="dc-sec-head"><h2>Destaques em <?= e($nomeEstado) ?></h2></div>
      <div class="dc-destaques">
        <?php foreach ($destaques as $e): $wa = $waLinkDe($e); ?>
        <div class="dc-card">
          <div class="dc-card-head">
            <?php if (!empty($e['logo'])): ?>
              <div class="dc-card-logo-wrap"><img class="dc-card-logo" src="<?= e($baseUrl . '/uploads/' . $e['logo']) ?>" alt="<?= e($e['nome_fantasia']) ?>"></div>
            <?php else: ?>
              <div class="dc-card-logo-wrap dc-card-logo-fallback"><?= e($iniciaisDe($e['nome_fantasia'] ?? '')) ?></div>
            <?php endif; ?>
            <div class="dc-card-titulo">
              <div class="dc-card-nome"><?= e($e['nome_fantasia']) ?></div>
              <div class="dc-card-local"><?= e($localDe($e)) ?></div>
            </div>
          </div>
          <span class="dc-badge-assinante"><i class="bi bi-patch-check-fill"></i> Verificado por FixaOS</span>
          <?php if (!empty($e['descricao_publica'])): ?>
          <p class="dc-card-desc"><?= e(mb_substr(trim(strip_tags($e['descricao_publica'])), 0, 110)) ?><?= mb_strlen(strip_tags($e['descricao_publica'])) > 110 ? '…' : '' ?></p>
          <?php endif; ?>
          <?php $pills = $pillsDe($e); if ($pills): ?>
          <div class="dc-pills">
            <?php foreach ($pills as $p): ?>
            <span class="dc-pill" style="background:<?= e($p['cor_fundo']) ?>;color:<?= e($p['cor_texto']) ?>;border-color:<?= e($p['cor_borda']) ?>"><?= e($p['label']) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <div class="dc-card-actions">
            <?php if ($wa): ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="dc-btn dc-btn-wa"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
            <a href="<?= url('/assistencias/' . $e['slug']) ?>" class="dc-btn dc-btn-perfil">Ver perfil</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Todas as assistências -->
    <div class="dc-sec">
      <div class="dc-sec-head">
        <h2>Todas as assistências (<?= count($lista) ?>)</h2>
      </div>
      <p class="dc-sec-nota">Perfis completos aparecem primeiro, dos mais atualizados; perfis incompletos aparecem com um link pra completar o cadastro gratuitamente.</p>

      <p id="dcSemResultado" class="dc-sem-resultado" style="display:none">Nenhuma assistência encontrada com esse nome.</p>

      <div class="dc-lista" id="dcLista">
        <?php foreach ($lista as $i => $e): $wa = $waLinkDe($e); ?>
        <div class="dc-item">
          <div class="dc-item-num"><?= $i + 1 ?></div>
          <?php if (!empty($e['logo'])): ?>
            <img class="dc-item-avatar" src="<?= e($baseUrl . '/uploads/' . $e['logo']) ?>" alt="">
          <?php else: ?>
            <div class="dc-item-avatar"><?= e($iniciaisDe($e['nome_fantasia'] ?? '')) ?></div>
          <?php endif; ?>
          <div class="dc-item-body">
            <div class="dc-item-nome">
              <?= e($e['nome_fantasia']) ?>
              <?php if ($e['assinante']): ?><span class="dc-badge-assinante" style="font-size:.66rem;padding:.12rem .5rem"><i class="bi bi-patch-check-fill"></i> Verificado</span><?php endif; ?>
              <?php if (!$e['completo']): ?><span class="dc-badge-incompleto">Perfil incompleto</span><?php endif; ?>
            </div>
            <div class="dc-item-sub">
              <?php if ($localDe($e) !== ''): ?><span><?= e($localDe($e)) ?></span><?php endif; ?>
              <?php foreach ($pillsDe($e) as $p): ?><span style="color:<?= e($p['cor_texto']) ?>;font-weight:600"><?= e($p['label']) ?></span><?php endforeach; ?>
            </div>
          </div>
          <div class="dc-item-actions">
            <?php if (!$e['completo']): ?>
              <a href="<?= url('/assistencias/' . $e['slug']) ?>" class="dc-link-incompleto">É sua empresa? Complete grátis</a>
            <?php else: ?>
              <?php if ($wa): ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="dc-icon-wa" title="WhatsApp"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
              <a href="<?= url('/assistencias/' . $e['slug']) ?>" class="dc-btn dc-btn-perfil" style="padding:.45rem .9rem">Ver perfil</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Mapa + cidades -->
    <?php if (!empty($comGeo) || !empty($cidadesAtendidas)): ?>
    <div class="dc-sec">
      <div class="dc-mapa-wrap">
        <?php if (!empty($comGeo)): ?>
        <div id="dcMapa" role="img" aria-label="Mapa com a localização das assistências em <?= e($nomeEstado) ?>"></div>
        <?php endif; ?>
        <?php if (!empty($cidadesAtendidas)): ?>
        <div class="dc-bairros-box">
          <h3>Cidades atendidas</h3>
          <div class="dc-bairro-chips">
            <?php foreach (array_slice($cidadesAtendidas, 0, 40) as $c): ?>
            <a href="<?= e($c['url']) ?>" class="dc-bairro-chip"><?= e($c['nome']) ?> <span style="opacity:.75">(<?= (int) $c['total'] ?>)</span></a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- FAQ -->
    <div class="dc-sec dc-faq">
      <div class="dc-sec-head"><h2>Perguntas frequentes</h2></div>
      <?php foreach ($faq as $f): ?>
      <details>
        <summary><?= e($f['pergunta']) ?></summary>
        <p><?= e($f['resposta']) ?></p>
      </details>
      <?php endforeach; ?>
    </div>

    <!-- CTA final -->
    <div class="dc-cta">
      <h2>Tem uma assistência em <?= e($nomeEstado) ?>?</h2>
      <p>Cadastro gratuito no diretório FixaOS — apareça pra quem já está procurando um conserto na sua região.</p>
      <a href="<?= url('/diretorio/cadastrar') ?>"><i class="bi bi-shop-window"></i> Reivindicar meu perfil</a>
    </div>

  </div>
</div>

<script>
(function(){
  function normalizar(s) {
    return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  }

  // Combobox "Ir para uma cidade" — diferente do filtro de bairro da página de cidade, aqui
  // NÃO filtra a própria página: cada cidade já tem sua própria página dedicada (ou cai na
  // busca geral filtrada, se não tiver página própria ainda), então escolher uma SEMPRE navega
  // pra fora desta página, nunca mexe no que está sendo exibido aqui.
  var buscaCidade = document.getElementById('dcBuscaCidade');
  var dropdownCidade = document.getElementById('dcCidadeDropdown');
  var wrapCidade = document.getElementById('dcCidadeWrap');
  if (buscaCidade && dropdownCidade && wrapCidade) {
    var opcoesCidade = Array.prototype.slice.call(dropdownCidade.querySelectorAll('.dc-bairro-opcao'));
    var realcada = null;

    var opcoesVisiveis = function () {
      return opcoesCidade.filter(function (op) { return op.style.display !== 'none'; });
    };
    var realcar = function (op) {
      opcoesCidade.forEach(function (o) { o.classList.remove('realce'); });
      realcada = op || null;
      if (op) op.classList.add('realce');
    };
    var filtrarCidades = function () {
      var termo = normalizar(buscaCidade.value.trim());
      opcoesCidade.forEach(function (op) {
        var bate = termo === '' || normalizar(op.textContent).indexOf(termo) !== -1;
        op.style.display = bate ? '' : 'none';
      });
      realcar(opcoesVisiveis()[0] || null);
    };
    var abrirCidade = function () { filtrarCidades(); dropdownCidade.classList.add('aberto'); };
    var fecharCidade = function () { dropdownCidade.classList.remove('aberto'); realcar(null); };
    var irPara = function (op) {
      var url = op && op.getAttribute('data-url');
      if (url) window.location.href = url;
    };

    buscaCidade.addEventListener('focus', abrirCidade);
    buscaCidade.addEventListener('input', abrirCidade);
    buscaCidade.addEventListener('keydown', function (ev) {
      var vis = opcoesVisiveis();
      if (!vis.length) return;
      var idx = realcada ? vis.indexOf(realcada) : -1;
      if (ev.key === 'ArrowDown') {
        ev.preventDefault();
        realcar(vis[Math.min(idx + 1, vis.length - 1)]);
      } else if (ev.key === 'ArrowUp') {
        ev.preventDefault();
        realcar(vis[Math.max(idx - 1, 0)]);
      } else if (ev.key === 'Enter') {
        ev.preventDefault();
        irPara(realcada || vis[0]);
      } else if (ev.key === 'Escape') {
        fecharCidade();
        buscaCidade.blur();
      }
    });
    opcoesCidade.forEach(function (op) {
      op.addEventListener('mousedown', function (ev) {
        ev.preventDefault();
        irPara(op);
      });
    });
    document.addEventListener('click', function (ev) {
      if (!wrapCidade.contains(ev.target)) fecharCidade();
    });
  }

  // Busca por nome — via AJAX (mesmo motivo de cidade.php: um estado pode ter MUITO mais
  // empresas do que até a maior cidade sozinha, filtrar isso tudo no cliente não escala).
  var busca = document.getElementById('dcBusca');
  var lista = document.getElementById('dcLista');
  var semResultado = document.getElementById('dcSemResultado');
  if (busca && lista) {
    var htmlOriginal = lista.innerHTML;
    var urlBusca = <?= json_encode($baseUrl . '/api/diretorio/estado/' . $ufLower . '/empresas') ?>;
    var categoriasInfo = <?= json_encode(array_map(fn($c) => ['label' => $c['label'], 'cor_texto' => $c['cor_texto']], $catPorSlug), JSON_UNESCAPED_UNICODE) ?>;
    var timerBusca = null;
    var ctrlBusca = null;

    var escaparHtml = function (s) {
      return (s || '').replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    };
    var iniciaisDe = function (nome) {
      var partes = (nome || '').trim().split(/\s+/);
      if (!partes[0]) return '?';
      var ini = partes[0].charAt(0).toUpperCase();
      if (partes.length > 1) ini += partes[partes.length - 1].charAt(0).toUpperCase();
      return ini;
    };
    var localDe = function (e) {
      return [e.bairro, e.cidade].filter(Boolean).join(', ');
    };
    var montarItemHtml = function (e, numero) {
      var avatar = e.logo
        ? '<img class="dc-item-avatar" src="' + escaparHtml(e.logo) + '" alt="">'
        : '<div class="dc-item-avatar">' + escaparHtml(iniciaisDe(e.nome)) + '</div>';
      var badges = '';
      if (e.assinante) badges += '<span class="dc-badge-assinante" style="font-size:.66rem;padding:.12rem .5rem"><i class="bi bi-patch-check-fill"></i> Verificado</span>';
      if (!e.completo) badges += '<span class="dc-badge-incompleto">Perfil incompleto</span>';
      var sub = '';
      var local = localDe(e);
      if (local) sub += '<span>' + escaparHtml(local) + '</span>';
      (e.categorias || []).slice(0, 3).forEach(function (slug) {
        var cat = categoriasInfo[slug];
        if (cat) sub += '<span style="color:' + cat.cor_texto + ';font-weight:600">' + escaparHtml(cat.label) + '</span>';
      });
      var acoes;
      if (!e.completo) {
        acoes = '<a href="' + escaparHtml(e.url) + '" class="dc-link-incompleto">É sua empresa? Complete grátis</a>';
      } else {
        var wa = e.whatsapp
          ? '<a href="https://wa.me/55' + e.whatsapp + '?text=' + encodeURIComponent('Olá! Vi ' + e.nome + ' no FixaOS e gostaria de mais informações.') + '" target="_blank" rel="noopener" class="dc-icon-wa" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>'
          : '';
        acoes = wa + '<a href="' + escaparHtml(e.url) + '" class="dc-btn dc-btn-perfil" style="padding:.45rem .9rem">Ver perfil</a>';
      }
      return '<div class="dc-item">'
        + '<div class="dc-item-num">' + numero + '</div>'
        + avatar
        + '<div class="dc-item-body"><div class="dc-item-nome">' + escaparHtml(e.nome) + ' ' + badges + '</div>'
        + '<div class="dc-item-sub">' + sub + '</div></div>'
        + '<div class="dc-item-actions">' + acoes + '</div>'
        + '</div>';
    };

    var buscarNoServidor = function (termo) {
      if (ctrlBusca) ctrlBusca.abort();
      ctrlBusca = ('AbortController' in window) ? new AbortController() : null;
      var qs = 'q=' + encodeURIComponent(termo);
      fetch(urlBusca + '?' + qs, ctrlBusca ? { signal: ctrlBusca.signal } : {})
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var itens = d.itens || [];
          lista.innerHTML = itens.map(function (e, i) { return montarItemHtml(e, i + 1); }).join('');
          if (semResultado) semResultado.style.display = itens.length === 0 ? '' : 'none';
        })
        .catch(function (err) {
          if (err && err.name === 'AbortError') return;
        });
    };

    busca.addEventListener('input', function () {
      var termo = busca.value.trim();
      clearTimeout(timerBusca);
      if (termo === '') {
        if (ctrlBusca) ctrlBusca.abort();
        lista.innerHTML = htmlOriginal;
        if (semResultado) semResultado.style.display = 'none';
        return;
      }
      timerBusca = setTimeout(function () { buscarNoServidor(termo); }, 250);
    });
  }
})();
</script>

<?php if (!empty($comGeo)): ?>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
(function(){
  var pontos = <?= json_encode(array_map(fn($e) => [
      'lat'   => (float) $e['latitude'],
      'lng'   => (float) $e['longitude'],
      'nome'  => $e['nome_fantasia'],
      'url'   => url('/assistencias/' . $e['slug']),
  ], $comGeo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  if (!pontos.length || typeof L === 'undefined') return;
  var mapa = L.map('dcMapa', { scrollWheelZoom: false });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap'
  }).addTo(mapa);
  // Um estado pode ter muito mais marcador que uma cidade só — agrupa em cluster pra não
  // empilhar centenas/milhares de pin no mesmo lugar (mesma lib já usada em encontrar.php).
  var cluster = (typeof L.markerClusterGroup === 'function')
    ? L.markerClusterGroup({ maxClusterRadius: 60, chunkedLoading: true })
    : L.layerGroup();
  var bounds = [];
  pontos.forEach(function (p) {
    var m = L.marker([p.lat, p.lng]);
    m.bindPopup('<strong>' + p.nome.replace(/</g, '&lt;') + '</strong><br><a href="' + p.url + '">Ver perfil</a>');
    cluster.addLayer(m);
    bounds.push([p.lat, p.lng]);
  });
  mapa.addLayer(cluster);
  if (bounds.length === 1) {
    mapa.setView(bounds[0], 12);
  } else {
    mapa.fitBounds(bounds, { padding: [24, 24] });
  }
})();
</script>
<?php endif; ?>
