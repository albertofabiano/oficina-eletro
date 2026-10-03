<?php
/**
 * Página de serviço por cidade do Diretório (Fase 2) —
 * `/assistencias/{uf}/{cidade-slug}/{servico-slug}`, ex. `/assistencias/sp/dracena/conserto-de-tv`.
 * Mesma ideia da página de cidade (diretorio/cidade.php), só com quem presta ESSE serviço
 * específico ali. Só existe (e só vira link nos chips da própria cidade.php) quando a cidade
 * atinge o mínimo de empresas pro serviço — ver DiretorioController::servico().
 *
 * Diferenças de cidade.php: sem filtro de bairro (não combinamos bairro+serviço nesta fase),
 * chips de serviço navegam ENTRE categorias (a ativa fica marcada, "Todas" volta pra cidade sem
 * filtro), e o H1/título/meta já nascem específicos do serviço.
 */
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$ufLower = strtolower($uf);
$urlCidadeBase  = $baseUrl . '/assistencias/' . $ufLower . '/' . $cidadeSlug;
$catTodas = diretorio_servico_categorias();
$servicoSlugUrlAtivo = $catTodas[$categoriaAtivaSlug]['slug_url'] ?? '';

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

$comGeo = array_values(array_filter($lista, fn($e) => $e['latitude'] !== null && $e['longitude'] !== null));
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css">

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início',            'item' => $baseUrl . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => uf_nome_estado($uf), 'item' => $baseUrl . '/assistencias?estado=' . $uf],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $cidadeReal,         'item' => $urlCidadeBase],
        ['@type' => 'ListItem', 'position' => 4, 'name' => $nomeServico,        'item' => $canonical],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'ItemList',
    'name'     => "{$nomeServico} em {$cidadePagina}",
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
/* .dc-container removido — usa o .container do Bootstrap, igual nav/footer de landing.php */

.dc-crumb{padding:1.1rem 0 .3rem;font-size:.82rem;color:var(--dc-muted)}
.dc-crumb a{color:var(--dc-muted);text-decoration:none}
.dc-crumb a:hover{color:var(--dc-navy);text-decoration:underline}
.dc-crumb span.sep{margin:0 .4rem;opacity:.6}

.dc-hero{padding:.6rem 0 1.6rem}
.dc-hero h1{font-size:clamp(1.6rem,3.4vw,2.3rem);font-weight:700;line-height:1.2;margin-bottom:.5rem}
.dc-hero-meta{font-size:.86rem;color:var(--dc-muted);margin-bottom:.9rem;display:flex;flex-wrap:wrap;gap:.3rem 1rem}
.dc-hero-meta b{color:var(--dc-text)}
.dc-hero p{font-size:1rem;line-height:1.65;color:var(--dc-muted);max-width:720px}

.dc-filtros{display:flex;flex-wrap:wrap;gap:.55rem;margin:1.3rem 0 2rem}
.dc-chip{
  display:inline-flex;align-items:center;gap:.4rem;padding:.42rem 1rem;border-radius:999px;
  font-size:.85rem;font-weight:600;border:1.5px solid var(--dc-border);background:var(--dc-card);
  color:var(--dc-text);text-decoration:none;transition:.15s;
}
.dc-chip:hover{border-color:var(--dc-navy);color:var(--dc-navy)}
.dc-chip.ativo{background:var(--dc-navy);border-color:var(--dc-navy);color:#fff}
.dc-chip .cnt{opacity:.7;font-weight:500}

.dc-sec{margin-bottom:2.6rem}
.dc-sec-head{display:flex;align-items:baseline;justify-content:space-between;gap:1rem;margin-bottom:1rem;flex-wrap:wrap}
.dc-sec h2{font-size:1.3rem;font-weight:700;margin:0}
.dc-sec-nota{font-size:.84rem;color:var(--dc-muted);margin-bottom:1.1rem}

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

.dc-toolbar{display:flex;flex-wrap:wrap;gap:.7rem;margin-bottom:1rem}
.dc-busca-wrap{position:relative;flex:1 1 240px}
.dc-busca-wrap i{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--dc-muted);font-size:.9rem}
.dc-busca-input{
  width:100%;padding:.62rem 1rem .62rem 2.3rem;border-radius:10px;border:1.5px solid var(--dc-border);
  background:var(--dc-card);color:var(--dc-text);font-family:'IBM Plex Sans',sans-serif;font-size:.88rem;
}
.dc-busca-input:focus{outline:none;border-color:var(--dc-navy)}
.dc-sem-resultado{font-size:.88rem;color:var(--dc-muted);text-align:center;padding:1.2rem;background:var(--dc-card);border:1px dashed var(--dc-border);border-radius:12px;margin-bottom:1rem}

#dcMapa{height:320px;border-radius:14px;border:1px solid var(--dc-border);overflow:hidden;background:#E9ECF2}

.dc-faq details{border:1px solid var(--dc-border);border-radius:10px;padding:.9rem 1.1rem;background:var(--dc-card);margin-bottom:.6rem}
.dc-faq summary{font-weight:700;font-size:.92rem;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:1rem}
.dc-faq summary::-webkit-details-marker{display:none}
.dc-faq summary::after{content:'+';font-size:1.2rem;color:var(--dc-muted);flex-shrink:0}
.dc-faq details[open] summary::after{content:'–'}
.dc-faq p{font-size:.88rem;color:var(--dc-muted);line-height:1.6;margin-top:.6rem}

.dc-proximas{display:flex;flex-wrap:wrap;gap:.6rem}
.dc-proxima-chip{background:var(--dc-card);border:1px solid var(--dc-border);border-radius:10px;padding:.55rem 1rem;font-size:.84rem;font-weight:600;text-decoration:none;color:var(--dc-text)}
.dc-proxima-chip:hover{border-color:var(--dc-navy);color:var(--dc-navy)}
.dc-proxima-chip span{color:var(--dc-muted);font-weight:500}

.dc-cta{background:var(--dc-navy);border-radius:18px;padding:2.4rem 2rem;text-align:center;color:#fff}
.dc-cta h2{color:#fff;font-size:1.5rem;margin-bottom:.5rem}
.dc-cta p{color:#C7CEEF;font-size:.95rem;margin-bottom:1.3rem}
.dc-cta a{background:#fff;color:var(--dc-navy);font-weight:700;padding:.8rem 1.8rem;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem}
.dc-cta a:hover{background:#EEF0F4}

@media(max-width:640px){
  .dc-item{flex-wrap:wrap}
  .dc-item-actions{width:100%;justify-content:flex-end}
}
</style>

<div class="dc-page">
  <div class="container">

    <!-- Breadcrumb -->
    <nav class="dc-crumb" aria-label="breadcrumb">
      <a href="<?= url('/') ?>">Início</a>
      <span class="sep">›</span>
      <a href="<?= url('/assistencias') ?>?estado=<?= e($uf) ?>"><?= e(uf_nome_estado($uf)) ?></a>
      <span class="sep">›</span>
      <a href="<?= e($urlCidadeBase) ?>"><?= e($cidadeReal) ?></a>
      <span class="sep">›</span>
      <span><?= e($nomeServico) ?></span>
    </nav>

    <!-- Hero -->
    <div class="dc-hero">
      <h1><?= e($nomeServico) ?> em <?= e($cidadePagina) ?></h1>
      <div class="dc-hero-meta">
        <span><b><?= (int) $totalGeral ?></b> assistência<?= $totalGeral === 1 ? '' : 's' ?> técnica<?= $totalGeral === 1 ? '' : 's' ?> com esse serviço</span>
        <?php if ($atualizadoEm !== ''): ?>
        <span>Atualizado em <?= e(mes_ano_br($atualizadoEm)) ?></span>
        <?php endif; ?>
      </div>
      <p>
        Encontre <?= (int) $totalGeral ?> assistência<?= $totalGeral === 1 ? '' : 's' ?> técnica<?= $totalGeral === 1 ? '' : 's' ?> especializada<?= $totalGeral === 1 ? '' : 's' ?>
        em <?= e(mb_strtolower($nomeServico)) ?> em <?= e($cidadeReal) ?>.
        Veja contato, serviços oferecidos e fale direto pelo WhatsApp com quem atende sua região.
      </p>
    </div>

    <div class="dc-toolbar">
      <div class="dc-busca-wrap">
        <i class="bi bi-search"></i>
        <input type="text" id="dcBusca" class="dc-busca-input" placeholder="Buscar por nome..." autocomplete="off">
      </div>
    </div>

    <!-- Filtros por serviço -->
    <div class="dc-filtros">
      <a href="<?= e($urlCidadeBase) ?>" class="dc-chip">Todas <span class="cnt">(<?= (int) array_sum(array_column($categoriasPresentes, 'total')) ?>)</span></a>
      <?php foreach ($categoriasPresentes as $cat): $ehAtiva = $cat['slug'] === $categoriaAtivaSlug; ?>
        <?php if ($ehAtiva): ?>
        <span class="dc-chip ativo"><?= e($cat['label']) ?> <span class="cnt">(<?= (int) $cat['total'] ?>)</span></span>
        <?php elseif ($cat['linkavel']): ?>
        <a href="<?= e($urlCidadeBase . '/' . $cat['slug_url']) ?>" class="dc-chip" style="border-color:<?= e($cat['cor_borda']) ?>;color:<?= e($cat['cor_texto']) ?>">
          <?= e($cat['label']) ?> <span class="cnt">(<?= (int) $cat['total'] ?>)</span>
        </a>
        <?php else: ?>
        <span class="dc-chip" style="opacity:.55" title="Ainda não há empresas suficientes pra uma página própria deste serviço nesta cidade">
          <?= e($cat['label']) ?> <span class="cnt">(<?= (int) $cat['total'] ?>)</span>
        </span>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- Destaques -->
    <?php if (!empty($destaques)): ?>
    <div class="dc-sec">
      <div class="dc-sec-head"><h2>Destaques em <?= e($nomeServico) ?></h2></div>
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
              <div class="dc-card-local"><?= e($e['bairro'] ? $e['bairro'] . ' · ' : '') . e($cidadeReal) ?></div>
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
              <?php if ($e['bairro']): ?><span><?= e($e['bairro']) ?></span><?php endif; ?>
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

    <!-- Mapa -->
    <?php if (!empty($comGeo)): ?>
    <div class="dc-sec">
      <div id="dcMapa" role="img" aria-label="Mapa com a localização das assistências de <?= e($nomeServico) ?> em <?= e($cidadeReal) ?>"></div>
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

    <!-- Cidades próximas -->
    <?php if (!empty($cidadesProximas)): ?>
    <div class="dc-sec">
      <div class="dc-sec-head"><h2>Cidades próximas</h2></div>
      <div class="dc-proximas">
        <?php foreach ($cidadesProximas as $c): ?>
        <a class="dc-proxima-chip" href="<?= url('/assistencias/' . strtolower($c['uf']) . '/' . slugify($c['cidade'])) ?>">
          <?= e($c['cidade']) ?> <span>(<?= (int) $c['total'] ?>)</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- CTA final -->
    <div class="dc-cta">
      <h2>Tem uma assistência em <?= e($cidadeReal) ?>?</h2>
      <p>Cadastro gratuito no diretório FixaOS — apareça pra quem já está procurando um conserto na sua região.</p>
      <a href="<?= url('/diretorio/cadastrar') ?>"><i class="bi bi-shop-window"></i> Reivindicar meu perfil</a>
    </div>

  </div>
</div>

<script>
(function(){
  // Busca por nome — via AJAX, mesmo motivo de cidade.php/estado.php (não escala pré-renderizar
  // tudo só pra filtrar no cliente numa cidade grande).
  var busca = document.getElementById('dcBusca');
  var lista = document.getElementById('dcLista');
  var semResultado = document.getElementById('dcSemResultado');
  if (busca && lista) {
    var htmlOriginal = lista.innerHTML;
    var urlBusca = <?= json_encode($baseUrl . '/api/diretorio/cidade/' . $ufLower . '/' . $cidadeSlug . '/' . $servicoSlugUrlAtivo . '/empresas') ?>;
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
    var montarItemHtml = function (e, numero) {
      var avatar = e.logo
        ? '<img class="dc-item-avatar" src="' + escaparHtml(e.logo) + '" alt="">'
        : '<div class="dc-item-avatar">' + escaparHtml(iniciaisDe(e.nome)) + '</div>';
      var badges = '';
      if (e.assinante) badges += '<span class="dc-badge-assinante" style="font-size:.66rem;padding:.12rem .5rem"><i class="bi bi-patch-check-fill"></i> Verificado</span>';
      if (!e.completo) badges += '<span class="dc-badge-incompleto">Perfil incompleto</span>';
      var sub = '';
      if (e.bairro) sub += '<span>' + escaparHtml(e.bairro) + '</span>';
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
  var bounds = [];
  pontos.forEach(function (p) {
    var m = L.marker([p.lat, p.lng]).addTo(mapa);
    m.bindPopup('<strong>' + p.nome.replace(/</g, '&lt;') + '</strong><br><a href="' + p.url + '">Ver perfil</a>');
    bounds.push([p.lat, p.lng]);
  });
  if (bounds.length === 1) {
    mapa.setView(bounds[0], 14);
  } else {
    mapa.fitBounds(bounds, { padding: [24, 24] });
  }
})();
</script>
<?php endif; ?>
