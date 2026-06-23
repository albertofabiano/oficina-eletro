<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Master') ?> — OficinaTech Master</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --ms: 220px; }
body { background:#0f1117; }
#msbar {
  width:var(--ms); min-height:100vh; background:#0a0d13;
  border-right:1px solid rgba(255,255,255,.06);
  position:fixed; top:0; left:0; z-index:100; overflow-y:auto;
}
#msbar .brand { padding:1.2rem 1rem; border-bottom:1px solid rgba(255,255,255,.06); }
#msbar .nav-link { color:#6c757d; padding:.45rem 1rem; border-radius:6px; margin:1px 8px; font-size:.84rem; }
#msbar .nav-link:hover, #msbar .nav-link.active { background:rgba(220,53,69,.15); color:#ff6b7a; }
#msbar .nav-link i { width:20px; }
#msbar .section-label { font-size:.68rem; color:#444; text-transform:uppercase; letter-spacing:.08em; padding:.5rem 1.2rem .2rem; }
#msmain { margin-left:var(--ms); min-height:100vh; }
#mstopbar { background:#141720; border-bottom:1px solid rgba(255,255,255,.06); padding:.6rem 1.5rem; }
.ms-card { background:#141720; border:1px solid rgba(255,255,255,.07); border-radius:12px; }
.ms-card .card-header { background:transparent; border-bottom:1px solid rgba(255,255,255,.07); }
body, .table, .form-control, .form-select, .input-group-text, .modal-content {
  color:#e0e0e0 !important;
}
.table { --bs-table-bg: transparent; --bs-table-hover-bg: rgba(255,255,255,.04); }
.table thead th { border-bottom:1px solid rgba(255,255,255,.1); color:#888; font-size:.78rem; }
.table td { border-bottom:1px solid rgba(255,255,255,.05); }
.form-control, .form-select {
  background:rgba(255,255,255,.05) !important;
  border:1px solid rgba(255,255,255,.1) !important;
  color:#e0e0e0 !important;
}
.form-select option { background:#1a1d23; }
.badge-plano-basico       { background:#6c757d; }
.badge-plano-profissional { background:#0d6efd; }
.badge-plano-enterprise   { background:#6f42c1; }
</style>
</head>
<body>

<nav id="msbar">
  <div class="brand d-flex align-items-center gap-2">
    <div class="bg-danger rounded-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px">
      <i class="bi bi-shield-fill-check text-white small"></i>
    </div>
    <div>
      <div class="text-white fw-bold" style="font-size:.9rem">Master Admin</div>
      <div style="font-size:.68rem;color:#555">OficinaTech</div>
    </div>
  </div>

  <?php $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
  <ul class="nav flex-column pt-2">
    <li class="nav-item">
      <a class="nav-link <?= $uri === '/master' ? 'active' : '' ?>" href="<?= url('/master') ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </li>

    <li class="section-label mt-2">Gestão</li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/empresas') ? 'active' : '' ?>" href="<?= url('/master/empresas') ?>">
        <i class="bi bi-building"></i> Empresas
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/usuarios') ? 'active' : '' ?>" href="<?= url('/master/usuarios') ?>">
        <i class="bi bi-people"></i> Usuários
      </a>
    </li>

    <li class="section-label mt-2">Sistema</li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/admins') ? 'active' : '' ?>" href="<?= url('/master/admins') ?>">
        <i class="bi bi-shield-lock"></i> Admins Master
      </a>
    </li>
  </ul>
</nav>

<div id="msmain">
  <div id="mstopbar" class="d-flex align-items-center justify-content-between">
    <h6 class="mb-0 text-white fw-semibold"><?= e($titulo ?? '') ?></h6>
    <div class="d-flex align-items-center gap-3">
      <span class="small text-muted"><?= e($_SESSION['master_nome'] ?? '') ?></span>
      <a href="<?= url('/master/logout') ?>" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right"></i> Sair
      </a>
    </div>
  </div>

  <div class="p-4">
    <?php foreach(['success','error','warning','info'] as $t): ?>
      <?php $msg = flash($t); if ($msg): ?>
      <div class="alert alert-<?= $t==='error'?'danger':$t ?> alert-dismissible fade show">
        <?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php ($content)(); ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-method]');
  if (!btn) return;
  e.preventDefault();
  const method = btn.dataset.method.toUpperCase();
  const href   = btn.dataset.href || (btn.href && !btn.href.endsWith('#') ? btn.href : null);
  if (!href) return;
  if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
  const form = document.createElement('form');
  form.method = 'POST'; form.action = href;
  form.innerHTML = `<input name="_token" value="<?= csrf_token() ?>"><input name="_method" value="${method}">`;
  document.body.appendChild(form); form.submit();
});
</script>
</body>
</html>
