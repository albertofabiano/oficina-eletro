<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Fórum de Técnicos') ?> | FixaOS</title>
<meta name="description" content="<?= e($metaDesc ?? 'Fórum de técnicos de eletrônica — defeitos de placa, firmware, dicas de reparo e downloads de atualizações.') ?>">
<?php if (!empty($noindex)): ?>
<meta name="robots" content="noindex, follow">
<?php else: ?>
<meta name="robots" content="index, follow">
<?php endif; ?>
<?php if (!empty($canonical)): ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>

<!-- Open Graph -->
<meta property="og:type"        content="website">
<meta property="og:site_name"   content="FixaOS Fórum">
<meta property="og:title"       content="<?= e($titulo ?? 'Fórum de Técnicos') ?>">
<meta property="og:description" content="<?= e($metaDesc ?? '') ?>">
<?php if (!empty($canonical)): ?>
<meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>

<?php if (!empty($schemaJson)): ?>
<script type="application/ld+json"><?= $schemaJson ?></script>
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= $appUrl ?>/css/app.css">
<style>
body { background:#f1f5f9; font-family:'Inter',system-ui,sans-serif; }
.forum-navbar { background:#0f172a; border-bottom:1px solid rgba(255,255,255,.07); }
.forum-hero { background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%); padding:2.5rem 0 2rem; }
.cat-icon { width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0; }
.topico-row:hover { background:#f8fafc; }
.badge-perfil-tecnico { background:#dbeafe; color:#1e40af; }
.badge-perfil-admin   { background:#fef3c7; color:#92400e; }
.badge-perfil-default { background:#f3f4f6; color:#4b5563; }
</style>
<!-- AdSense — fórum é conteúdo público (ver também diretório em landing.php) -->
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2477643924516118" crossorigin="anonymous"></script>
</head>
<body>

<!-- Navbar -->
<nav class="forum-navbar navbar navbar-dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= $appUrl ?>">
      <svg width="110" height="28" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= $appUrl ?>/forum" class="text-white-50 small me-2 text-decoration-none">
        <i class="bi bi-chat-dots me-1"></i>Fórum
      </a>
      <?php if (\App\Core\Auth::check()): ?>
      <a href="<?= $appUrl ?>/dashboard" class="btn btn-outline-light btn-sm">
        <i class="bi bi-speedometer2 me-1"></i>Sistema
      </a>
      <?php else: ?>
      <a href="<?= $appUrl ?>/login" class="btn btn-outline-light btn-sm">Entrar</a>
      <a href="<?= $appUrl ?>/forum/cadastrar" class="btn btn-primary btn-sm fw-semibold">Cadastrar grátis</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Conteúdo -->
<div class="container py-4">
  <?php $fe = flash('error'); $fs = flash('success'); ?>
  <?php if ($fe): ?><div class="alert alert-danger"><?= e($fe) ?></div><?php endif; ?>
  <?php if ($fs): ?><div class="alert alert-success"><?= e($fs) ?></div><?php endif; ?>
  <?php ($content)(); ?>
</div>

<!-- Footer -->
<footer class="mt-5 py-4" style="background:#0f172a;color:#64748b;font-size:.85rem">
  <div class="container text-center">
    <span>© <?= date('Y') ?> FixaOS — Fórum de Técnicos de Eletrônica</span>
    &nbsp;|&nbsp;
    <a href="<?= $appUrl ?>/login" class="text-muted">Entrar no sistema</a>
    &nbsp;|&nbsp;
    <a href="<?= $appUrl ?>/forum/cadastrar" class="text-muted">Cadastrar grátis</a>
    &nbsp;|&nbsp;
    <a href="<?= $appUrl ?>/forum" class="text-muted">Fórum</a>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
