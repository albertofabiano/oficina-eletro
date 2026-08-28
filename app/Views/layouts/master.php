<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Master') ?> — FixaOS Master</title>
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
:root { --ms: 220px; }
body { background:#0f1117; }
#msbar {
  width:var(--ms); height:100vh; background:#0a0d13;
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
  <div class="brand">
    <svg width="100%" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg">
      <rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/>
      <text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text>
    </svg>
    <div style="font-size:.68rem;color:#555;text-align:center;margin-top:4px">Master Admin</div>
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
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/leads') ? 'active' : '' ?>" href="<?= url('/master/leads') ?>">
        <i class="bi bi-person-lines-fill"></i> Leads
        <?php
        try { $ldNovos = \App\Core\DB::pdo()->query("SELECT COUNT(*) FROM lista_espera WHERE convidado=0")->fetchColumn(); } catch (\Throwable $e) { $ldNovos = 0; }
        if($ldNovos > 0):?>
        <span class="badge rounded-pill ms-1" style="background:#10b981;color:#fff;font-size:.65rem"><?= $ldNovos ?></span>
        <?php endif;?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/prospeccao') ? 'active' : '' ?>" href="<?= url('/master/prospeccao') ?>">
        <i class="bi bi-search-heart"></i> Prospecção
        <?php
        try { $prNovos = \App\Core\DB::pdo()->query("SELECT COUNT(*) FROM leads_prospeccao WHERE status='novo'")->fetchColumn(); } catch (\Throwable $e) { $prNovos = 0; }
        if($prNovos > 0):?>
        <span class="badge rounded-pill ms-1" style="background:#10b981;color:#fff;font-size:.65rem"><?= $prNovos ?></span>
        <?php endif;?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/diretorio-emails') ? 'active' : '' ?>" href="<?= url('/master/diretorio-emails') ?>">
        <i class="bi bi-envelope-heart"></i> E-mails do Diretório
        <?php
        try {
            $deElegiveis = \App\Core\DB::pdo()->query(
                "SELECT COUNT(*) FROM diretorio_leads_email
                 WHERE email_convite_enviado_em IS NULL AND descadastrado_em IS NULL AND reivindicada = 0"
            )->fetchColumn();
        } catch (\Throwable $e) { $deElegiveis = 0; }
        if($deElegiveis > 0):?>
        <span class="badge rounded-pill ms-1" style="background:#10b981;color:#fff;font-size:.65rem"><?= $deElegiveis ?></span>
        <?php endif;?>
      </a>
    </li>

    <li class="section-label mt-2">Marketplace</li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/marketplace') ? 'active' : '' ?>"
         href="<?= url('/master/marketplace/creditos') ?>">
        <i class="bi bi-coin"></i> Créditos
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/adsense') ? 'active' : '' ?>"
         href="<?= url('/master/adsense') ?>">
        <i class="bi bi-megaphone"></i> Blocos de Anúncio
      </a>
    </li>

    <li class="section-label mt-2">Diretório</li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/diretorio') ? 'active' : '' ?>"
         href="<?= url('/master/diretorio') ?>">
        <i class="bi bi-megaphone-fill"></i> Anúncios
        <?php
        $pendAnuncios = \App\Core\DB::pdo()->query("SELECT COUNT(*) FROM diretorio_assinaturas WHERE status='pendente'")->fetchColumn();
        if($pendAnuncios > 0):?>
        <span class="badge rounded-pill ms-1" style="background:#f97316;color:#fff;font-size:.65rem"><?= $pendAnuncios ?></span>
        <?php endif;?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/avaliacoes') ? 'active' : '' ?>"
         href="<?= url('/master/avaliacoes') ?>">
        <i class="bi bi-star-half"></i> Avaliações
        <?php
        $pendCount = \App\Core\DB::pdo()->query("SELECT COUNT(*) FROM diretorio_avaliacoes WHERE aprovado=0")->fetchColumn();
        if($pendCount > 0):?>
        <span class="badge rounded-pill ms-1" style="background:#f59e0b;color:#000;font-size:.65rem"><?= $pendCount ?></span>
        <?php endif;?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/feedbacks') ? 'active' : '' ?>"
         href="<?= url('/master/feedbacks') ?>">
        <i class="bi bi-chat-heart"></i> Feedbacks
        <?php
        try { $fbNovos = \App\Core\DB::pdo()->query("SELECT COUNT(*) FROM feedbacks WHERE status='novo'")->fetchColumn(); } catch (\Throwable $e) { $fbNovos = 0; }
        if($fbNovos > 0):?>
        <span class="badge rounded-pill ms-1" style="background:#3b82f6;color:#fff;font-size:.65rem"><?= $fbNovos ?></span>
        <?php endif;?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/reivindicacoes') ? 'active' : '' ?>"
         href="<?= url('/master/reivindicacoes') ?>">
        <i class="bi bi-patch-check"></i> Reivindicações
        <?php
        try { $rvCount = \App\Core\DB::pdo()->query("SELECT COUNT(*) FROM diretorio_reivindicacoes WHERE status='pendente'")->fetchColumn(); } catch (\Throwable $e) { $rvCount = 0; }
        if($rvCount > 0):?>
        <span class="badge rounded-pill ms-1" style="background:#f97316;color:#fff;font-size:.65rem"><?= $rvCount ?></span>
        <?php endif;?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/kb') ? 'active' : '' ?>" href="<?= url('/master/kb') ?>">
        <i class="bi bi-journal-text"></i> Base de Conhecimento
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/ia') ? 'active' : '' ?>" href="<?= url('/master/ia') ?>">
        <i class="bi bi-robot"></i> IA / Bot de Suporte
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/imei') ? 'active' : '' ?>" href="<?= url('/master/imei') ?>">
        <i class="bi bi-phone-vibrate"></i> Consulta de IMEI
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/interesse-nf') ? 'active' : '' ?>" href="<?= url('/master/interesse-nf') ?>">
        <i class="bi bi-receipt"></i> Interesse em NF
      </a>
    </li>

    <li class="section-label mt-2">Sistema</li>
    <li class="nav-item">
      <a class="nav-link <?= str_starts_with($uri,'/master/whatsapp') ? 'active' : '' ?>" href="<?= url('/master/whatsapp') ?>">
        <i class="bi bi-whatsapp"></i> WhatsApp
      </a>
    </li>
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
