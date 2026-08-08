<?php
$appUrl   = require BASE_PATH . '/config/app.php';
$baseUrl  = rtrim($appUrl['url'], '/');
$busca    = htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8');
$tipoFilt = htmlspecialchars($filtros['tipo']  ?? '', ENT_QUOTES, 'UTF-8');
$marcaFilt= htmlspecialchars($filtros['marca'] ?? '', ENT_QUOTES, 'UTF-8');

$titulo  = $empresaNome ? 'Anúncios de ' . htmlspecialchars($empresaNome, ENT_QUOTES, 'UTF-8') : 'Marketplace de Peças para Assistência Técnica';
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
#mpBody { transition:opacity .15s ease; }
</style>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2477643924516118" crossorigin="anonymous"></script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark" style="background:#1a1d23;border-bottom:1px solid rgba(255,255,255,.08)">
  <div class="container flex-wrap gap-2">
    <a class="navbar-brand d-flex align-items-center" href="<?= $baseUrl ?>">
      <svg width="110" height="28" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
    </a>
    <form method="GET" action="<?= $baseUrl ?>/pecas" class="mp-ajax-form flex-grow-1 mx-lg-4" style="max-width:420px">
      <div class="input-group input-group-sm">
        <input type="search" name="busca" class="form-control" placeholder="Buscar peça, marca, modelo..." value="<?= $busca ?>">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>
    <div class="d-flex gap-2">
      <a href="<?= $baseUrl ?>/login" class="btn btn-outline-light btn-sm">Entrar</a>
      <a href="<?= $baseUrl ?>/cadastrar" class="btn btn-primary btn-sm fw-semibold">Cadastrar grátis</a>
    </div>
  </div>
</nav>

<?php require BASE_PATH . '/app/Views/marketplace/partials/_publico_body.php'; ?>

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
<script src="<?= $baseUrl ?>/js/marketplace-ajax.js"></script>
</body>
</html>
