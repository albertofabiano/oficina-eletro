<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar — FixaOS</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
<link rel="shortcut icon" href="/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#1e3a5f">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= url('/css/app.css') ?>?v=<?= filemtime(BASE_PATH.'/public/css/app.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background: linear-gradient(135deg,#1a1d23 0%,#212529 100%); min-height:100vh; margin:0; }
.login-card { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.4); }

/* Menu fixo no topo das telas de acesso (login, esqueci senha, redefinir senha) — mesmo
   conteúdo/visual do menu da landing (layouts/landing.php, .nav-land), sem o botão "Entrar"
   (redundante aqui, a pessoa já está na tela de login). Duplicado, não compartilhado, porque
   este projeto não tem um framework de componentes entre layouts — ver CLAUDE.md. */
:root { --auth-nav-h: 66px; }
.auth-nav {
  position: sticky; top: 0; z-index: 999;
  background: rgba(11,13,16,.92);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255,255,255,.07);
  padding: .9rem 0;
}
.auth-nav-link { color:#94a3b8; font-size:.88rem; font-weight:500; transition:color .15s; text-decoration:none; }
.auth-nav-link:hover { color:#fff; }
.auth-btn-brand {
  background:#f97316; color:#fff; font-weight:700; border:none; border-radius:10px;
  padding:.6rem 1.3rem; font-size:.9rem; transition:.2s; white-space:nowrap;
}
.auth-btn-brand:hover { background:#ea6c0a; color:#fff; }
.auth-btn-demo {
  background:linear-gradient(135deg,#2dd4bf,#0891b2); color:#06222a; font-weight:800;
  border:none; border-radius:10px; padding:.6rem 1.1rem; font-size:.85rem; white-space:nowrap;
}
.auth-content {
  min-height: calc(100vh - var(--auth-nav-h));
  display: flex; align-items: center; justify-content: center;
}
</style>
</head>
<body>

<nav class="auth-nav" id="authNav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= url('/') ?>" aria-label="FixaOS — Página inicial">
      <svg width="100" height="26" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="FixaOS"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
    </a>
    <div class="d-none d-lg-flex align-items-center gap-4">
      <a href="<?= url('/') ?>#funcionalidades" class="auth-nav-link">Funcionalidades</a>
      <a href="<?= url('/') ?>#como-funciona" class="auth-nav-link">Como funciona</a>
      <a href="<?= url('/') ?>#planos" class="auth-nav-link">Planos</a>
      <a href="<?= url('/') ?>#faq" class="auth-nav-link">FAQ</a>
      <a href="<?= url('/manual') ?>" target="_blank" class="auth-nav-link"><i class="bi bi-book-half me-1"></i>Manual</a>
      <a href="<?= url('/forum') ?>" class="auth-nav-link"><i class="bi bi-chat-square-text me-1"></i>Fórum</a>
      <a href="<?= url('/assistencias') ?>" class="auth-nav-link" style="color:#5eead4"><i class="bi bi-geo-alt-fill me-1"></i>Encontrar Assistência</a>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= url('/demo') ?>" class="auth-btn-demo btn btn-sm d-none d-sm-inline-flex align-items-center"><i class="bi bi-play-circle-fill me-1"></i>Ver demonstração</a>
      <a href="<?= url('/cadastrar') ?>" class="auth-btn-brand btn btn-sm"><i class="bi bi-rocket-takeoff-fill me-1"></i>Teste grátis</a>
    </div>
  </div>
</nav>

<div class="auth-content">
  <div class="col-12 col-sm-8 col-md-5 col-lg-4">
    <?php ($content)(); ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/masks.js') ?>"></script>
<script>
// Mede a altura real do menu fixo (a estimativa de 66px no CSS já cobre a maioria dos casos,
// isso só ajusta fino se a fonte/ícones carregarem com uma métrica um pouco diferente) —
// usado por login.php pra calcular sua própria altura sem sobrar nem faltar espaço.
(function () {
  var nav = document.getElementById('authNav');
  if (!nav) return;
  function ajustar() { document.documentElement.style.setProperty('--auth-nav-h', nav.offsetHeight + 'px'); }
  ajustar();
  window.addEventListener('resize', ajustar);
})();
</script>
</body>
</html>
