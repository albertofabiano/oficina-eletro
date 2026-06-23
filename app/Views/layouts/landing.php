<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($titulo) ? e($titulo) . ' — ' : '' ?>OficinaTech</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --brand: #0d6efd;
  --dark:  #0f1117;
  --dark2: #1a1d23;
}
body { font-family: 'Segoe UI', system-ui, sans-serif; }

/* NAV */
.navbar-land {
  background: rgba(15,17,23,.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,.06);
  position: sticky; top: 0; z-index: 999;
}
.navbar-land .nav-link { color: #adb5bd; font-size: .9rem; }
.navbar-land .nav-link:hover { color: #fff; }

/* HERO */
.hero {
  background: linear-gradient(135deg, var(--dark) 0%, #0a1628 60%, #0d1f3c 100%);
  min-height: 100vh;
  display: flex; align-items: center;
  position: relative; overflow: hidden;
}
.hero::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at 70% 50%, rgba(13,110,253,.15) 0%, transparent 70%);
}
.hero-badge {
  background: rgba(13,110,253,.15);
  border: 1px solid rgba(13,110,253,.3);
  color: #74b0ff; border-radius: 100px;
  font-size: .8rem; padding: .3rem .9rem; display: inline-block; margin-bottom: 1.5rem;
}
.hero h1 { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800; color: #fff; line-height: 1.15; }
.hero h1 span { color: var(--brand); }
.hero p.lead { color: #adb5bd; font-size: 1.1rem; max-width: 520px; }
.hero-img {
  background: var(--dark2);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 40px 100px rgba(0,0,0,.5);
}

/* STATS */
.stats-bar { background: var(--dark2); border-top: 1px solid rgba(255,255,255,.06); border-bottom: 1px solid rgba(255,255,255,.06); }
.stat-item { text-align: center; padding: 2rem 1rem; }
.stat-num  { font-size: 2rem; font-weight: 800; color: var(--brand); }
.stat-label{ color: #6c757d; font-size: .85rem; }

/* FEATURES */
.feature-card {
  background: #fff; border-radius: 16px;
  border: 1px solid #e9ecef;
  padding: 2rem; height: 100%;
  transition: transform .2s, box-shadow .2s;
}
.feature-card:hover { transform: translateY(-4px); box-shadow: 0 20px 50px rgba(0,0,0,.1); }
.feature-icon {
  width: 52px; height: 52px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; margin-bottom: 1rem;
}

/* PRICING */
.price-card {
  border-radius: 20px; border: 2px solid #e9ecef;
  padding: 2.5rem; transition: transform .2s;
}
.price-card:hover { transform: translateY(-4px); }
.price-card.destaque { border-color: var(--brand); position: relative; }
.price-card.destaque::before {
  content: 'Mais popular';
  position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
  background: var(--brand); color: #fff;
  font-size: .75rem; font-weight: 700; padding: .3rem .9rem; border-radius: 100px;
}
.price-valor { font-size: 2.5rem; font-weight: 800; color: #212529; }
.price-valor sup { font-size: 1rem; vertical-align: top; margin-top: .6rem; }
.price-valor small { font-size: .9rem; font-weight: 400; color: #6c757d; }

/* HOW IT WORKS */
.step-num {
  width: 48px; height: 48px; border-radius: 50%;
  background: var(--brand); color: #fff;
  font-size: 1.2rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem;
}

/* DARK SECTIONS */
.section-dark { background: var(--dark); color: #fff; }
.section-dark .text-muted { color: #6c757d !important; }

/* CTA BOTTOM */
.cta-section {
  background: linear-gradient(135deg, var(--brand) 0%, #0a58ca 100%);
  padding: 5rem 0; text-align: center; color: #fff;
}

/* FOOTER */
footer { background: var(--dark); color: #6c757d; padding: 3rem 0 1.5rem; border-top: 1px solid rgba(255,255,255,.06); }
footer a { color: #6c757d; text-decoration: none; }
footer a:hover { color: #fff; }

.btn-hero { padding: .75rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 10px; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar-land px-4 py-3 d-flex align-items-center justify-content-between">
  <a href="<?= url('/') ?>" class="d-flex align-items-center gap-2 text-decoration-none">
    <div class="bg-primary rounded-2 d-flex align-items-center justify-content-center" style="width:34px;height:34px">
      <i class="bi bi-cpu-fill text-white"></i>
    </div>
    <span class="text-white fw-bold fs-5">OficinaTech</span>
  </a>
  <div class="d-none d-md-flex align-items-center gap-4">
    <a href="#funcionalidades" class="nav-link">Funcionalidades</a>
    <a href="#como-funciona" class="nav-link">Como funciona</a>
    <a href="#planos" class="nav-link">Planos</a>
    <a href="<?= url('/login') ?>" class="btn btn-outline-secondary btn-sm px-3">Entrar</a>
    <a href="<?= url('/cadastrar') ?>" class="btn btn-primary btn-sm px-3 fw-semibold">Teste grátis</a>
  </div>
  <a href="<?= url('/cadastrar') ?>" class="btn btn-primary btn-sm d-md-none">Começar</a>
</nav>

<!-- Flash -->
<?php $err = flash('error'); $suc = flash('success'); ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible m-3 fade show"><?= e($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($suc): ?><div class="alert alert-success alert-dismissible m-3 fade show"><?= e($suc) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<?php ($content)(); ?>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="bg-primary rounded-2 d-flex align-items-center justify-content-center" style="width:30px;height:30px">
            <i class="bi bi-cpu-fill text-white small"></i>
          </div>
          <span class="text-white fw-bold">OficinaTech</span>
        </div>
        <p class="small">Sistema completo para gestão de assistências técnicas de eletrônicos e eletrodomésticos.</p>
      </div>
      <div class="col-md-2">
        <div class="text-white small fw-semibold mb-2">Sistema</div>
        <div class="d-flex flex-column gap-1 small">
          <a href="#funcionalidades">Funcionalidades</a>
          <a href="#planos">Planos</a>
          <a href="<?= url('/cadastrar') ?>">Criar conta</a>
          <a href="<?= url('/login') ?>">Entrar</a>
        </div>
      </div>
      <div class="col-md-3">
        <div class="text-white small fw-semibold mb-2">Módulos</div>
        <div class="d-flex flex-column gap-1 small">
          <span>Ordens de Serviço</span>
          <span>CRM / Clientes</span>
          <span>Estoque / Peças</span>
          <span>Financeiro</span>
        </div>
      </div>
    </div>
    <hr style="border-color:rgba(255,255,255,.06)">
    <div class="d-flex justify-content-between align-items-center small">
      <span>© <?= date('Y') ?> OficinaTech. Todos os direitos reservados.</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>"></script>
<script>
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const t = document.querySelector(a.getAttribute('href'));
    if (t) { e.preventDefault(); t.scrollIntoView({behavior:'smooth'}); }
  });
});
</script>
</body>
</html>
