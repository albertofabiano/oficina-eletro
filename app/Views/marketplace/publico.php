<?php
$appUrl   = require BASE_PATH . '/config/app.php';
$baseUrl  = rtrim($appUrl['url'], '/');
$busca    = htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8');
$tipoFilt = htmlspecialchars($filtros['tipo']  ?? '', ENT_QUOTES, 'UTF-8');
$marcaFilt= htmlspecialchars($filtros['marca'] ?? '', ENT_QUOTES, 'UTF-8');

// Carregar categorias e blocos de anúncio
$_db = \App\Core\DB::pdo();
$_cats   = $_db->query("SELECT * FROM marketplace_categorias WHERE ativo=1 ORDER BY ordem,nome")->fetchAll();
$_adMap  = [];
foreach ($_db->query("SELECT posicao,codigo FROM master_adsense_blocos WHERE ativo=1")->fetchAll() as $_b)
    $_adMap[$_b['posicao']] = $_b['codigo'];

$titulo  = 'Marketplace de Peças para Assistência Técnica';
$desc    = 'Compre e venda peças, componentes e acessórios de eletrônicos entre assistências técnicas. ' . $paginator['total'] . ' peças disponíveis.';
$canonical = $baseUrl . '/pecas';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $titulo ?> | FixaOS</title>
<meta name="description" content="<?= $desc ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= $canonical ?>">

<!-- Open Graph -->
<meta property="og:type"        content="website">
<meta property="og:title"       content="<?= $titulo ?>">
<meta property="og:description" content="<?= $desc ?>">
<meta property="og:url"         content="<?= $canonical ?>">
<meta property="og:site_name"   content="FixaOS">

<!-- Schema.org -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "<?= $titulo ?>",
  "description": "<?= $desc ?>",
  "numberOfItems": <?= $paginator['total'] ?>,
  "itemListElement": [
    <?php foreach (array_slice($paginator['data'], 0, 5) as $i => $item): ?>
    {
      "@type": "ListItem",
      "position": <?= $i + 1 ?>,
      "url": "<?= $baseUrl ?>/pecas/<?= $item['id'] ?>",
      "name": "<?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>"
    }<?= $i < min(4, count($paginator['data'])-1) ? ',' : '' ?>
    <?php endforeach; ?>
  ]
}
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/css/app.css">
<style>
:root { --brand: #0d6efd; }
body  { background:#f0f2f5; font-family:'Segoe UI',system-ui,sans-serif; }

.hero {
  background:linear-gradient(135deg,#1a1d23 0%,#0d1f3c 100%);
  padding:3rem 0 2rem;
}
.hero h1 { font-size:clamp(1.5rem,4vw,2.4rem); font-weight:800; }

.mp-card { transition:.2s; border:1.5px solid #cbd5e1 !important; border-radius:12px; }
.mp-card:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.12); }
.mp-preco { font-size:1.3rem; font-weight:800; color:#198754; }

.blur-contact {
  filter:blur(4px);
  user-select:none;
  pointer-events:none;
}
.contact-overlay {
  position:relative;
  overflow:hidden;
}
.contact-overlay::after {
  content:'';
  position:absolute;
  inset:0;
  background:rgba(255,255,255,.6);
  backdrop-filter:blur(3px);
}
.contact-cta {
  position:absolute;
  inset:0;
  z-index:2;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-direction:column;
  gap:4px;
}

.navbar-brand { font-weight:800; font-size:1.2rem; }
.badge-tipo { background:#e8f0fe; color:#1a56db; font-size:.72rem; }

.mp-img {
  width:100%; aspect-ratio:4/3; object-fit:cover;
  border-radius:10px 10px 0 0;
  background:#f0f2f5;
}
.mp-img-placeholder {
  width:100%; aspect-ratio:4/3;
  border-radius:10px 10px 0 0;
  background:linear-gradient(135deg,#f0f2f5 0%,#e2e6ea 100%);
  display:flex; align-items:center; justify-content:center;
  color:#adb5bd;
}
</style>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2477643924516118" crossorigin="anonymous"></script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark" style="background:#1a1d23;border-bottom:1px solid rgba(255,255,255,.08)">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= $baseUrl ?>">
      <svg width="110" height="28" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
    </a>
    <div class="d-flex gap-2">
      <a href="<?= $baseUrl ?>/login" class="btn btn-outline-light btn-sm">Entrar</a>
      <a href="<?= $baseUrl ?>/cadastrar" class="btn btn-primary btn-sm fw-semibold">Cadastrar grátis</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="hero text-white">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <div class="badge bg-primary bg-opacity-25 text-primary mb-3">
          <i class="bi bi-shop me-1"></i> Marketplace de Peças
        </div>
        <h1 class="mb-2">Peças e Componentes entre Assistências Técnicas</h1>
        <p class="text-white-50 mb-0">
          <?= $paginator['total'] ?> peça<?= $paginator['total'] !== 1 ? 's' : '' ?> disponível<?= $paginator['total'] !== 1 ? 'is' : '' ?> de assistências cadastradas.
          Cadastre-se para ver os dados do vendedor e entrar em contato.
        </p>
      </div>
      <div class="col-lg-6">
        <form method="GET" action="<?= $baseUrl ?>/pecas">
          <div class="input-group input-group-lg shadow">
            <input type="search" name="busca" class="form-control border-0"
              placeholder="Buscar peça, marca, modelo..."
              value="<?= $busca ?>">
            <button class="btn btn-primary px-4" type="submit">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Contador de resultados -->
<div style="background:#fff;border-bottom:1px solid #dee2e6">
  <div class="container py-2">
    <span class="text-muted small"><?= $paginator['total'] ?> peça(s) encontrada(s)<?= $tipoFilt ? ' em <strong>'.$tipoFilt.'</strong>' : '' ?><?= $marcaFilt ? ' · marca <strong>'.$marcaFilt.'</strong>' : '' ?><?= ($tipoFilt||$marcaFilt) ? ' — <a href="'.$baseUrl.'/pecas'.($busca?'?busca='.urlencode($busca):'').'">Limpar filtro</a>' : '' ?></span>
  </div>
</div>

<!-- Layout principal com sidebar -->
<div class="container py-4">
<div class="row g-4">

<!-- ── SIDEBAR ESQUERDA ── -->
<div class="col-lg-3 d-none d-lg-block">
  <div style="position:sticky;top:20px">

    <!-- Busca -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:14px">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;padding:10px 14px 6px;border-bottom:1px solid #f1f5f9">
        <i class="bi bi-search me-1"></i>Buscar
      </div>
      <div style="padding:10px 14px">
        <form method="GET" action="<?= $baseUrl ?>/pecas">
          <div class="input-group input-group-sm">
            <input type="search" name="busca" class="form-control" placeholder="Peça, marca..." value="<?= $busca ?>">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
          </div>
        </form>
      </div>
    </div>

    <!-- Categorias da empresa -->
    <?php if (!empty($_cats)): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:14px">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;padding:10px 14px 6px;border-bottom:1px solid #f1f5f9">
        <i class="bi bi-tags me-1"></i>Categorias
      </div>
      <a href="<?= $baseUrl ?>/pecas" style="display:block;padding:9px 14px;font-size:.83rem;color:#334155;text-decoration:none;font-weight:600;border-bottom:1px solid #f8fafc;<?= !$tipoFilt?'background:#eff6ff;color:#2563eb;border-left:3px solid #2563eb':'' ?>">
        Todas as categorias
      </a>
      <?php foreach ($_cats as $_c): ?>
      <a href="<?= $baseUrl ?>/pecas?tipo=<?= urlencode($_c['nome']) ?>"
         style="display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:.83rem;color:#334155;text-decoration:none;border-bottom:1px solid #f8fafc;transition:.12s;<?= $tipoFilt===$_c['nome']?'background:#eff6ff;color:#2563eb;border-left:3px solid #2563eb':'' ?>"
         onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='<?= $tipoFilt===$_c['nome']?'#eff6ff':'transparent' ?>'">
        <span style="width:8px;height:8px;border-radius:50%;background:<?= e($_c['cor']) ?>;flex-shrink:0;display:inline-block"></span>
        <?= htmlspecialchars($_c['nome'], ENT_QUOTES, 'UTF-8') ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tipos dinâmicos -->
    <?php if (!empty($tipos)): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:14px">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;padding:10px 14px 6px;border-bottom:1px solid #f1f5f9">
        <i class="bi bi-tag me-1"></i>Tipo de Peça
      </div>
      <?php foreach (array_slice($tipos,0,12) as $_t): ?>
      <a href="<?= $baseUrl ?>/pecas?tipo=<?= urlencode($_t) ?>"
         style="display:block;padding:8px 14px;font-size:.82rem;color:#334155;text-decoration:none;border-bottom:1px solid #f8fafc;<?= $tipoFilt===$_t?'background:#eff6ff;color:#2563eb;font-weight:700;border-left:3px solid #2563eb':'' ?>">
        <?= htmlspecialchars($_t, ENT_QUOTES, 'UTF-8') ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Bloco anúncio 1 -->
    <?php if (!empty($_adMap[1])): ?>
    <div style="margin-bottom:14px"><?= $_adMap[1] ?></div>
    <?php else: ?>
    <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;min-height:90px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:4px;margin-bottom:14px">
      <i class="bi bi-megaphone" style="font-size:1.5rem;opacity:.2;color:#64748b"></i>
      <span style="font-size:.7rem;color:#94a3b8">Espaço publicitário</span>
    </div>
    <?php endif; ?>

    <!-- Bloco anúncio 2 -->
    <?php if (!empty($_adMap[2])): ?>
    <div style="margin-bottom:14px"><?= $_adMap[2] ?></div>
    <?php else: ?>
    <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;min-height:90px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:4px;margin-bottom:14px">
      <i class="bi bi-megaphone" style="font-size:1.5rem;opacity:.2;color:#64748b"></i>
      <span style="font-size:.7rem;color:#94a3b8">Espaço publicitário</span>
    </div>
    <?php endif; ?>

    <!-- Bloco anúncio 3 -->
    <?php if (!empty($_adMap[3])): ?>
    <div><?= $_adMap[3] ?></div>
    <?php else: ?>
    <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;min-height:90px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:4px">
      <i class="bi bi-megaphone" style="font-size:1.5rem;opacity:.2;color:#64748b"></i>
      <span style="font-size:.7rem;color:#94a3b8">Espaço publicitário</span>
    </div>
    <?php endif; ?>

  </div>
</div><!-- /sidebar -->

<!-- ── CONTEÚDO PRINCIPAL ── -->
<div class="col-lg-9">

  <?php if (!$paginator['data']): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-search fs-1 d-block mb-3 opacity-30"></i>
    <h4>Nenhuma peça encontrada</h4>
    <p>Tente outros termos ou <a href="<?= $baseUrl ?>/pecas">veja todas as peças</a>.</p>
  </div>
  <?php else: ?>

  <div class="row g-3 mb-4">
    <?php foreach ($paginator['data'] as $item): ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
      <a href="<?= $baseUrl ?>/pecas/<?= $item['id'] ?>" class="text-decoration-none text-dark">
        <div class="card mp-card border-0 shadow-sm h-100">
          <!-- Imagem principal -->
          <?php if (!empty($item['imagem_principal'])): ?>
          <img src="<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars($item['imagem_principal'], ENT_QUOTES, 'UTF-8') ?>"
               class="mp-img" alt="<?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
          <?php else: ?>
          <div class="mp-img-placeholder">
            <i class="bi bi-image" style="font-size:2.5rem"></i>
          </div>
          <?php endif; ?>

          <div class="card-body d-flex flex-column p-3 text-center">

            <!-- Badges -->
            <div class="d-flex flex-wrap gap-1 mb-2 justify-content-center">
              <?php if ($item['tipo']): ?>
              <span class="badge badge-tipo"><?= htmlspecialchars($item['tipo'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
              <?php if ($item['marca']): ?>
              <span class="badge bg-light text-dark border" style="font-size:.7rem"><?= htmlspecialchars($item['marca'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </div>

            <!-- Título -->
            <h6 class="fw-bold mb-1 flex-grow-1"><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></h6>

            <?php if ($item['modelo']): ?>
            <div class="text-muted small mb-2"><?= htmlspecialchars($item['modelo'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <!-- Preço -->
            <div class="mt-auto pt-2 border-top">
              <div class="mp-preco mb-1"><?= 'R$ ' . number_format($item['valor'],2,',','.') ?></div>
              <div class="text-muted" style="font-size:.75rem">
                <i class="bi bi-geo-alt"></i>
                <?= htmlspecialchars($item['empresa_cidade'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($item['empresa_uf'], ENT_QUOTES, 'UTF-8') ?>
              </div>
            </div>
          </div>

          <!-- Botão de contato público -->
          <?php
          $waNum = preg_replace('/\D/', '', $item['empresa_whatsapp'] ?? $item['empresa_tel'] ?? '');
          $linkPeca = $baseUrl . '/pecas/' . ($item['slug'] ?? $item['id']);
          $msgWa = urlencode('Olá! Vi seu anúncio no FixaOS e tenho interesse:' . "\n\n" . '📦 *' . $item['titulo'] . '*' . "\n" . '💰 R$ ' . number_format($item['valor'],2,',','.') . "\n" . '🔗 ' . $linkPeca . "\n\n" . 'Ainda disponível?');
          ?>
          <div class="card-footer bg-transparent border-top-0 px-3 pb-3">
            <?php if ($waNum): ?>
            <a href="https://wa.me/55<?= $waNum ?>?text=<?= $msgWa ?>" target="_blank"
               class="btn btn-success w-100 fw-semibold">
              <i class="bi bi-whatsapp me-1"></i>Chamar no WhatsApp
            </a>
            <?php else: ?>
            <a href="<?= $baseUrl ?>/pecas/<?= $item['id'] ?>" class="btn btn-outline-primary w-100 fw-semibold">
              <i class="bi bi-eye me-1"></i>Ver detalhes
            </a>
            <?php endif; ?>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Paginação -->
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="d-flex justify-content-center">
    <?= paginacao_condensada((int)$paginator['current_page'], (int)$paginator['last_page'], function($p) use($baseUrl,$busca,$tipoFilt,$marcaFilt){
          $q = array_filter(['busca'=>$busca,'tipo'=>$tipoFilt,'marca'=>$marcaFilt,'page'=>$p>1?$p:null]);
          return $baseUrl.'/pecas'.($q?'?'.http_build_query($q):'');
        }) ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>

  <!-- Banner CTA -->
  <div class="card border-0 mt-4" style="background:linear-gradient(135deg,#1a1d23,#0d1f3c);border-radius:16px">
    <div class="card-body text-center py-4 text-white">
      <h4 class="fw-bold">Tem peças para vender?</h4>
      <p class="text-white-50 mb-3 small">Cadastre sua assistência gratuitamente e publique anúncios.</p>
      <a href="<?= $baseUrl ?>/cadastrar" class="btn btn-primary fw-semibold px-4">
        <i class="bi bi-shop me-2"></i>Anunciar agora — Grátis
      </a>
    </div>
  </div>

</div><!-- /col-lg-9 conteúdo -->
</div><!-- /row -->
</div><!-- /container -->

<!-- Footer -->
<footer class="mt-5 py-4" style="background:#1a1d23;color:#6c757d;font-size:.85rem">
  <div class="container text-center">
    <span>© <?= date('Y') ?> FixaOS — Marketplace de Peças para Assistências Técnicas</span>
    &nbsp;|&nbsp;
    <a href="<?= $baseUrl ?>/login" class="text-muted">Entrar</a>
    &nbsp;|&nbsp;
    <a href="<?= $baseUrl ?>/cadastrar" class="text-muted">Cadastrar</a>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
