<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Sistema') ?> — OficinaTech</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
.is-valid   { border-color: #198754 !important; }
.is-invalid { border-color: #dc3545 !important; }
input.border-success { border-color: #198754 !important; box-shadow: 0 0 0 .2rem rgba(25,135,84,.15); }
</style>
<style>
:root { --sidebar-w: 240px; }
body { background: #f0f2f5; }
#sidebar {
  width: var(--sidebar-w); min-height: 100vh; background: #1a1d23;
  position: fixed; top: 0; left: 0; z-index: 1000; overflow-y: auto;
  transition: transform .25s;
}
#sidebar .brand { padding: 1.2rem 1rem; border-bottom: 1px solid #2d3139; }
#sidebar .nav-link { color: #adb5bd; padding: .5rem 1rem; border-radius: 6px; margin: 1px 8px; font-size: .87rem; }
#sidebar .nav-link:hover, #sidebar .nav-link.active { background: #0d6efd22; color: #fff; }
#sidebar .nav-link:hover { color: #fff !important; }
#osStatusFilter .nav-link:hover { background: rgba(255,255,255,.07) !important; color: #fff !important; }
#osStatusFilter .os-status-ativo { background: rgba(255,255,255,.1) !important; }
#sidebar .nav-link i { width: 20px; }
#sidebar .section-label { font-size: .7rem; color: #6c757d; text-transform: uppercase; letter-spacing: .08em; padding: .6rem 1.2rem .2rem; }
#main { margin-left: var(--sidebar-w); min-height: 100vh; }
#topbar { background: #fff; border-bottom: 1px solid #dee2e6; padding: .6rem 1.5rem; }
#topbar .topbar-title { width: 100%; margin-top: .35rem; }
.page-content { padding: 1.5rem; }
@media (max-width: 576px) {
  #topbar { padding: .6rem .8rem; }
  #topbar .topbar-title { font-size: .85rem; }
  #topbar .user-name { display: none; }
  .page-content { padding: 1rem; }
}
.stat-card { border: none; border-radius: 12px; }
.badge-prioridade-urgente { background: #dc3545; }
.badge-prioridade-alta    { background: #fd7e14; }
.badge-prioridade-normal  { background: #0d6efd; }
.badge-prioridade-baixa   { background: #6c757d; }
@media (max-width: 768px) {
  #sidebar { transform: translateX(-100%); }
  #sidebar.show { transform: translateX(0); }
  #main { margin-left: 0; }
}
#sidebarOverlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999;
}
#sidebarOverlay.show { display: block; }
</style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebarOverlay" onclick="fecharSidebar()"></div>
<nav id="sidebar">
  <?php
    $logoEmpresa = null;
    if (\App\Core\Auth::check()) {
      $stmtLogo = \App\Core\DB::pdo()->prepare("SELECT logo FROM empresas WHERE id = ? LIMIT 1");
      $stmtLogo->execute([\App\Core\Auth::empresaId()]);
      $logoEmpresa = $stmtLogo->fetchColumn() ?: null;
    }
  ?>
  <div class="brand d-flex align-items-center gap-2 py-3 px-3">
    <?php if ($logoEmpresa): ?>
      <img src="<?= url('/uploads/' . e($logoEmpresa)) ?>"
           alt="Logo"
           style="max-width:140px;max-height:48px;object-fit:contain;filter:brightness(1.1)">
    <?php else: ?>
      <i class="bi bi-cpu-fill text-primary fs-5"></i>
      <div>
        <div class="text-white fw-bold" style="font-size:.9rem">OficinaTech</div>
        <div class="text-muted" style="font-size:.7rem"><?= e(\App\Core\Auth::user()['empresa_nome'] ?? '') ?></div>
      </div>
    <?php endif; ?>
    <button type="button" class="btn-close btn-close-white d-md-none ms-auto" onclick="fecharSidebar()" aria-label="Fechar menu"></button>
  </div>
  <?php $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
  <?php function navAtivo(string $uri, string $caminho): string {
    return str_starts_with($uri, $caminho) ? 'active' : '';
  } ?>
  <ul class="nav flex-column pt-2">
    <li class="nav-item"><a class="nav-link <?= ($uri === '/' || str_starts_with($uri, '/dashboard')) ? 'active' : '' ?>" href="<?= url('/dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

    <li class="section-label mt-2">Atendimento</li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/os') && !isset($_GET['status_id']) && !isset($_GET['em_garantia']) ? 'active' : '' ?>" href="<?= url('/os') ?>"><i class="bi bi-clipboard2-pulse"></i> Ordens de Serviço</a></li>
    <?php
    // Contagem de OS em garantia para o link na sidebar
    if (\App\Core\Auth::check()):
      $stmtGarSidebar = \App\Core\DB::pdo()->prepare(
        "SELECT COUNT(*) FROM ordens_servico
         WHERE empresa_id = ? AND tipo_servico = 'garantia' AND os_origem_id IS NOT NULL"
      );
      $stmtGarSidebar->execute([\App\Core\Auth::empresaId()]);
      $totalGarSidebar = (int) $stmtGarSidebar->fetchColumn();
    endif;
    ?>
    <?php if (!empty($totalGarSidebar)): ?>
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center justify-content-between <?= isset($_GET['em_garantia']) ? 'active' : '' ?>"
         href="<?= url('/os') ?>?em_garantia=1"
         style="font-size:.84rem">
        <span><i class="bi bi-shield-check me-2" style="color:#dc3545"></i>Em Garantia</span>
        <span class="badge rounded-pill" style="background:#dc3545;font-size:.65rem"><?= $totalGarSidebar ?></span>
      </a>
    </li>
    <?php endif; ?>

    <?php
    // Widget de filtro por status
    if (\App\Core\Auth::check()):
      $eid = \App\Core\Auth::empresaId();
      $stmtSidebar = \App\Core\DB::pdo()->prepare(
        "SELECT s.id, s.nome, s.cor, s.tipo, s.ordem,
         COUNT(os.id) AS total
         FROM os_status s
         LEFT JOIN ordens_servico os ON os.status_id = s.id
           AND os.empresa_id = s.empresa_id
           AND (os.tipo_servico IS NULL OR os.tipo_servico != 'garantia' OR os.os_origem_id IS NULL)
         WHERE s.empresa_id = ?
         GROUP BY s.id
         ORDER BY s.ordem"
      );
      $stmtSidebar->execute([$eid]);
      $statusSidebar = $stmtSidebar->fetchAll();
      $totalAberto   = array_sum(array_column(
        array_filter($statusSidebar, fn($s) => in_array($s['tipo'], ['aberta','em_andamento','aguardando'])),
        'total'
      ));
    ?>
    <?php if ($statusSidebar): ?>
    <li class="nav-item px-2 mt-1">
      <button class="btn btn-link w-100 text-start p-0 d-flex align-items-center justify-content-between"
              style="color:#adb5bd;font-size:.78rem;text-decoration:none"
              data-bs-toggle="collapse" data-bs-target="#osStatusFilter" aria-expanded="false">
        <span><i class="bi bi-funnel me-1"></i>Filtrar por status</span>
        <?php if ($totalAberto): ?>
        <span class="badge rounded-pill" style="background:#dc3545;font-size:.65rem"><?= $totalAberto ?></span>
        <?php endif; ?>
      </button>
      <div class="collapse" id="osStatusFilter">
        <ul class="nav flex-column mt-1" style="padding-left:4px">
          <?php $statusIdAtivo = $_GET['status_id'] ?? null; ?>
          <?php foreach ($statusSidebar as $s): ?>
          <li class="nav-item">
            <a href="<?= url('/os') ?>?status_id=<?= $s['id'] ?>"
               class="nav-link d-flex align-items-center justify-content-between py-1 px-2 rounded <?= $statusIdAtivo == $s['id'] ? 'os-status-ativo' : '' ?>"
               style="font-size:.78rem;<?= $statusIdAtivo == $s['id'] ? 'background:rgba(255,255,255,.1)' : '' ?>">
              <div class="d-flex align-items-center gap-2 min-w-0">
                <span class="rounded-circle flex-shrink-0"
                      style="width:8px;height:8px;background:<?= e($s['cor']) ?>;display:inline-block"></span>
                <span class="text-truncate" style="color:<?= $statusIdAtivo == $s['id'] ? '#fff' : '#adb5bd' ?>">
                  <?= e($s['nome']) ?>
                </span>
              </div>
              <?php if ($s['total'] > 0): ?>
              <span class="badge rounded-pill flex-shrink-0"
                    style="background:<?= e($s['cor']) ?>;font-size:.65rem;min-width:18px">
                <?= $s['total'] ?>
              </span>
              <?php endif; ?>
            </a>
          </li>
          <?php endforeach; ?>
          <li class="nav-item mt-1">
            <a href="<?= url('/os') ?>"
               class="nav-link py-1 px-2 rounded d-flex align-items-center gap-2"
               style="font-size:.75rem;color:#6c757d">
              <i class="bi bi-x-circle" style="font-size:.75rem"></i>
              <span>Limpar filtro</span>
            </a>
          </li>
        </ul>
      </div>
    </li>
    <?php endif; ?>
    <?php endif; ?>

    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/agenda') ?>" href="<?= url('/agenda') ?>"><i class="bi bi-calendar3"></i> Agenda</a></li>

    <li class="section-label mt-2">CRM</li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/clientes') ?>" href="<?= url('/clientes') ?>"><i class="bi bi-people"></i> Clientes</a></li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/crm') ?>" href="<?= url('/crm') ?>"><i class="bi bi-funnel"></i> Pipeline</a></li>

    <li class="section-label mt-2">Estoque</li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/produtos') ?>" href="<?= url('/produtos') ?>"><i class="bi bi-box-seam"></i> Produtos</a></li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/fornecedores') ?>" href="<?= url('/fornecedores') ?>"><i class="bi bi-truck"></i> Fornecedores</a></li>

    <li class="section-label mt-2">Financeiro</li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/financeiro') ?>" href="<?= url('/financeiro') ?>"><i class="bi bi-currency-dollar"></i> Lançamentos</a></li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/relatorios') ?>" href="<?= url('/relatorios') ?>"><i class="bi bi-bar-chart-line"></i> Relatórios</a></li>

    <li class="section-label mt-2">Config</li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/tecnicos') ?>" href="<?= url('/tecnicos') ?>"><i class="bi bi-tools"></i> Técnicos</a></li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/os/status') ?>" href="<?= url('/os/status') ?>"><i class="bi bi-tags"></i> Status de OS</a></li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/usuarios') ?>" href="<?= url('/usuarios') ?>"><i class="bi bi-person-gear"></i> Usuários</a></li>
    <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/empresa') ?>" href="<?= url('/empresa') ?>"><i class="bi bi-building"></i> Empresa</a></li>
  </ul>
</nav>

<!-- Main -->
<div id="main">
  <!-- Topbar -->
  <div id="topbar">
    <div class="d-flex align-items-center">
      <button class="btn btn-sm btn-outline-secondary d-md-none flex-shrink-0" onclick="abrirSidebar()">
        <i class="bi bi-list"></i>
      </button>
      <div class="d-flex align-items-center gap-3 flex-shrink-0 ms-auto">
        <?php $alertas = (new \App\Models\Produto())->emEstoqueMinimo(); if (count($alertas)): ?>
          <a href="<?= url('/produtos') ?>" class="text-warning" title="<?= count($alertas) ?> produto(s) em estoque mínimo">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span class="badge bg-warning text-dark"><?= count($alertas) ?></span>
          </a>
        <?php endif; ?>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            <span class="fw-semibold"><?= avatar_iniciais(\App\Core\Auth::user()['nome'] ?? 'U') ?></span>
            <span class="user-name"><?= e(\App\Core\Auth::user()['nome'] ?? '') ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
          </ul>
        </div>
      </div>
    </div>
    <h6 class="mb-0 fw-semibold topbar-title" title="<?= e($titulo ?? '') ?>"><?= e($titulo ?? '') ?></h6>
  </div>

  <!-- Flash messages -->
  <div class="page-content pb-0">
    <?php foreach (['success','error','warning','info'] as $type): ?>
      <?php $msg = flash($type); if ($msg): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
          <?= e($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- Page Content -->
  <div class="page-content">
    <?php ($content)(); ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>"></script>
<script>
function abrirSidebar() {
  document.getElementById('sidebar').classList.add('show');
  document.getElementById('sidebarOverlay').classList.add('show');
}
function fecharSidebar() {
  document.getElementById('sidebar').classList.remove('show');
  document.getElementById('sidebarOverlay').classList.remove('show');
}

// _method override para DELETE
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-method]');
  if (!btn) return;
  e.preventDefault();
  const method = btn.dataset.method.toUpperCase();
  const href   = btn.dataset.href || (btn.href && !btn.href.endsWith('#') ? btn.href : null);
  if (!href) return;
  if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = href;
  form.innerHTML = `<input name="_token" value="<?= csrf_token() ?>"><input name="_method" value="${method}">`;
  document.body.appendChild(form);
  form.submit();
});

// AJAX helpers
async function apiPost(url, data) {
  const resp = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: JSON.stringify(data)
  });
  return resp.json();
}
</script>
</body>
</html>
