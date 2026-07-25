<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Sistema') ?> — FixaOS</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
<link rel="shortcut icon" href="/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#1e3a5f">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= url('/css/app.css') ?>?v=<?= filemtime(BASE_PATH.'/public/css/app.css') ?>">
<style>
.is-valid   { border-color: #198754 !important; }
.is-invalid { border-color: #dc3545 !important; }
input.border-success { border-color: #198754 !important; box-shadow: 0 0 0 .2rem rgba(25,135,84,.15); }
</style>
<style>
:root { --sidebar-w: 240px; }
body { background: #f0f2f5; }
#sidebar {
  width: var(--sidebar-w); height: 100vh; background: #1a1d23;
  position: fixed; top: 0; left: 0; z-index: 1000;
  display: flex; flex-direction: column; overflow: hidden;
  transition: transform .25s;
}
#sidebar .brand { padding: 1.2rem 1rem; border-bottom: 1px solid #2d3139; flex-shrink: 0; }
/* Área rolável do menu (logo fica fixo no topo) */
.sb-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding-bottom: 1.5rem;
  scrollbar-width: thin; scrollbar-color: #4b5563 #1a1d23; }
.sb-scroll::-webkit-scrollbar { width: 9px; }
.sb-scroll::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 5px; }
.sb-scroll::-webkit-scrollbar-thumb:hover { background: #6b7280; }
.sb-scroll::-webkit-scrollbar-track { background: #1a1d23; }
#sidebar .nav-link { color: #e2e8f0; padding: .5rem 1rem; border-radius: 6px; margin: 1px 8px; font-size: .92rem; }
#sidebar .nav-link:hover, #sidebar .nav-link.active { background: #0d6efd22; color: #fff; }
#sidebar .nav-link:hover { color: #fff !important; }
#osStatusFilter .nav-link:hover { background: rgba(255,255,255,.07) !important; color: #fff !important; }
#osStatusFilter .os-status-ativo { background: rgba(255,255,255,.1) !important; }
#sidebar .nav-link i { width: 20px; }
#sidebar .section-label { font-size: .7rem; color: #6c757d; text-transform: uppercase; letter-spacing: .08em; padding: .6rem 1.2rem .2rem; }
/* Sanfona */
.sb-group-btn {
  width:100%; background:none; border:none; text-align:left;
  color:#eef1f4; font-weight:600; font-size:.83rem; text-transform:uppercase;
  letter-spacing:.08em; padding:.55rem 1.2rem .3rem;
  display:flex; align-items:center; justify-content:space-between;
  cursor:pointer; transition:color .15s;
}
.sb-group-btn:hover { color:#fff; }
.sb-group-btn .sb-chevron { font-size:.65rem; transition:transform .25s; }
.sb-group-btn[aria-expanded="true"] .sb-chevron { transform:rotate(180deg); }
.sb-group-btn .sb-label { display:flex; align-items:center; gap:6px; }
.sb-body { overflow:hidden; }
.sb-body ul { padding-bottom:4px; }
/* Botões de ação rápida (mesmo tamanho, full-width) */
.sb-actions { padding:.35rem 0 .25rem; }
.sb-action {
  display:flex; align-items:center; gap:8px;
  margin:.28rem 8px; padding:9px 12px; border-radius:10px;
  font-size:.8rem; font-weight:700; line-height:1.15;
  color:#fff; text-decoration:none; white-space:nowrap;
  transition:transform .15s, filter .15s;
}
.sb-action i { font-size:1rem; width:18px; text-align:center; flex-shrink:0; }
.sb-action:hover { transform:translateY(-1px); filter:brightness(1.07); color:#fff; }
.sb-action-green  { background:linear-gradient(135deg,#22c55e,#16a34a); box-shadow:0 4px 12px rgba(22,163,74,.40); }
.sb-action-blue   { background:linear-gradient(135deg,#3b82f6,#1d4ed8); box-shadow:0 4px 12px rgba(37,99,235,.40); }
.sb-action-yellow { background:linear-gradient(135deg,#fbbf24,#f59e0b); color:#3a2d00; box-shadow:0 4px 12px rgba(245,158,11,.40); }
.sb-action-yellow:hover { color:#3a2d00; }
.sb-action-purple { background:linear-gradient(135deg,#a78bfa,#7c3aed); box-shadow:0 4px 12px rgba(124,58,237,.40); }
.sb-action-cyan   { background:linear-gradient(135deg,#22d3ee,#0891b2); box-shadow:0 4px 12px rgba(8,145,178,.40); }
.sb-action-indigo { background:linear-gradient(135deg,#818cf8,#4f46e5); box-shadow:0 4px 12px rgba(79,70,229,.40); }
.sb-action-orange { background:linear-gradient(135deg,#fb923c,#ea580c); box-shadow:0 4px 12px rgba(234,88,12,.40); }
.sb-action-slate  { background:linear-gradient(135deg,#94a3b8,#475569); box-shadow:0 4px 12px rgba(71,85,105,.40); }
.sb-action-teal   { background:linear-gradient(135deg,#2dd4bf,#0d9488); box-shadow:0 4px 12px rgba(13,148,136,.40); }
#main { margin-left: var(--sidebar-w); min-height: 100vh; }
#topbar { background: #fff; border-bottom: 2px solid #cccccc; padding: .6rem 1.5rem; position: sticky; top: 0; z-index: 1030; box-shadow: 0 2px 6px rgba(0,0,0,.07); }
.page-content { padding: 1.5rem; }
.stat-card { border: none; border-radius: 12px; }
.badge-prioridade-urgente { background: #dc3545; }
.badge-prioridade-alta    { background: #fd7e14; }
.badge-prioridade-normal  { background: #0d6efd; }
.badge-prioridade-baixa   { background: #6c757d; }
@media (max-width: 768px) {
  #sidebar { transform: translateX(-100%); }
  #sidebar.show { transform: translateX(0); }
  #main { margin-left: 0; }
  #topbar { padding: .5rem .75rem; }
  #topbar .gap-3 { gap: .6rem !important; }
  #topbar h6 { font-size: .95rem; }
}
#topbar h6.topbar-title { width: 100%; margin-top: .35rem; }
#sidebarOverlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999;
}
#sidebarOverlay.show { display: block; }
.dropdown-menu { z-index: 1035; }
/* Preferência de exibição: tudo em MAIÚSCULAS — site inteiro (sidebar, topo e conteúdo) */
body.ui-uppercase { text-transform: uppercase; }
</style>
</head>
<?php
// Preferência de exibição do usuário (maiúsculas x normal), cacheada na sessão.
if (!isset($_SESSION['texto_maiusculo'])) {
    try {
        $stmtTm = \App\Core\DB::pdo()->prepare("SELECT texto_maiusculo FROM usuarios WHERE id = ?");
        $stmtTm->execute([\App\Core\Auth::id()]);
        $_SESSION['texto_maiusculo'] = (int) $stmtTm->fetchColumn();
    } catch (\Throwable $e) { $_SESSION['texto_maiusculo'] = 0; }
}
$textoMaiusculo = (int) ($_SESSION['texto_maiusculo'] ?? 0);

// Configs DE CHAT da EMPRESA — lidas a cada carga (NÃO cacheia na sessão) pra que
// mudanças propaguem na hora para todos os usuários. Defaults: tudo ligado.
$chatHabilitado = 1; $chatSom = 1; $chatInsistente = 1; $mostrarPrevisao = 1;
try {
    $stmtCh = \App\Core\DB::pdo()->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ? AND chave IN ('chat_habilitado','chat_som','chat_insistente','mostrar_previsao')");
    $stmtCh->execute([\App\Core\Auth::empresaId()]);
    foreach ($stmtCh->fetchAll(\PDO::FETCH_KEY_PAIR) as $k => $v) {
        $iv = ($v === '' || $v === null) ? 1 : (int) $v;
        if ($k === 'chat_habilitado') $chatHabilitado = $iv;
        elseif ($k === 'chat_som') $chatSom = $iv;
        elseif ($k === 'chat_insistente') $chatInsistente = $iv;
        elseif ($k === 'mostrar_previsao') $mostrarPrevisao = $iv;
    }
} catch (\Throwable $e) { $chatHabilitado = 1; $chatSom = 1; $chatInsistente = 1; $mostrarPrevisao = 1; }
$_SESSION['chat_habilitado'] = $chatHabilitado; // disponível para as views (ex.: os/show)
$_SESSION['mostrar_previsao'] = $mostrarPrevisao; // controla a exibição da "Previsão de entrega"
?>
<body class="<?= $textoMaiusculo ? 'ui-uppercase' : '' ?>">

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
      <div style="flex:1;min-width:0">
        <svg width="100%" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" style="display:block">
          <rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/>
          <text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text>
        </svg>
        <div class="text-muted text-center mt-1" style="font-size:.7rem"><?= e(\App\Core\Auth::user()['empresa_nome'] ?? '') ?></div>
      </div>
    <?php endif; ?>
    <button type="button" class="btn-close btn-close-white d-md-none flex-shrink-0" onclick="fecharSidebar()" aria-label="Fechar menu"></button>
  </div>
  <div class="sb-scroll">
  <?php $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'; ?>
  <?php function navAtivo(string $uri, string $caminho): string {
    return str_starts_with($uri, $caminho) ? 'active' : '';
  } ?>
  <?php
  // Menu sanfona sempre fechado por padrão (não abre automaticamente pela URL atual)
  $grpAtendimento = false;
  $grpCrm         = false;
  $grpEstoque     = false;
  $grpFinanceiro  = false;
  $grpMarketplace = false;
  $grpForum       = false;
  $grpConfig      = false;

  // Dados de OS para Atendimento
  if (\App\Core\Auth::check()):
    $eid = \App\Core\Auth::empresaId();
    // Centralizado: a regra de contagem vem SÓ do Model (mesma fonte da tela /os — sem divergir).
    $osModel = new \App\Models\OrdemServico();
    $totalGarSidebar = $osModel->totalEmGarantia();
    $statusSidebar   = $osModel->totaisPorStatus();
    $totalAberto   = array_sum(array_column(
      array_filter($statusSidebar, fn($s) => in_array($s['tipo'], ['aberta','em_andamento','aguardando'])),
      'total'
    ));
  endif;
  ?>

  <?php if (\App\Core\Auth::soDiretorio()): ?>
  <!-- Menu mínimo: conta só do diretório -->
  <div class="pt-2 pb-1">
    <div style="padding:14px 14px 3px;font-size:.62rem;letter-spacing:.09em;text-transform:uppercase;color:#9fb0c3;font-weight:700">Meu diretório</div>
    <a class="nav-link <?= navAtivo($uri,'/empresa/perfil-publico') ?>" href="<?= url('/empresa/perfil-publico') ?>">
      <i class="bi bi-shop-window"></i> Editar Diretório
    </a>
    <a class="nav-link" href="<?= url('/assistencias') ?>" target="_blank">
      <i class="bi bi-globe2"></i> Ver o diretório <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.6rem;opacity:.5"></i>
    </a>
  </div>
  <?php else: ?>

  <!-- Atalhos coloridos principais (arrastáveis, ordem salva por usuário) -->
  <?php
    // Ordem personalizada do menu (por usuário). Carrega 1x por sessão.
    if (!array_key_exists('menu_ordem', $_SESSION)) {
      try {
        $stMO = \App\Core\DB::pdo()->prepare("SELECT menu_ordem FROM usuarios WHERE id = ?");
        $stMO->execute([\App\Core\Auth::id()]);
        $_SESSION['menu_ordem'] = (string) $stMO->fetchColumn();
      } catch (\Throwable $e) { $_SESSION['menu_ordem'] = ''; }
    }
    $menuOrdem = json_decode($_SESSION['menu_ordem'] ?: '[]', true);
    if (!is_array($menuOrdem)) $menuOrdem = [];
  ?>
  <div class="pt-2 pb-1">
    <div class="sb-actions" id="sbActions">
      <a href="<?= url('/dashboard') ?>" data-key="dashboard" class="sb-action sb-action-blue">
        <i class="bi bi-speedometer2"></i> <?= __('menu_dashboard') ?>
      </a>
      <?php if (\App\Core\Auth::can('pdv')): ?>
      <a href="<?= url('/pdv') ?>" data-key="pdv" class="sb-action sb-action-purple">
        <i class="bi bi-cash-stack"></i> PDV / Frente de Caixa
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('os')): ?>
      <a href="<?= url('/os/nova') ?>" data-key="abrir-os" class="sb-action sb-action-green">
        <i class="bi bi-plus-circle-fill"></i> Abrir Nova OS
      </a>
      <a href="<?= url('/os') ?>" data-key="os" class="sb-action sb-action-teal">
        <i class="bi bi-clipboard2-pulse"></i> Ordens de Serviço
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('estoque')): ?>
      <a href="<?= url('/produtos') ?>" data-key="produtos" class="sb-action sb-action-orange">
        <i class="bi bi-box-seam"></i> Produtos
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('clientes')): ?>
      <a href="<?= url('/clientes') ?>" data-key="clientes" class="sb-action sb-action-cyan">
        <i class="bi bi-people"></i> Clientes
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('financeiro')): ?>
      <a href="<?= url('/financeiro') ?>" data-key="financeiro" class="sb-action sb-action-yellow">
        <i class="bi bi-currency-dollar"></i> Financeiro
      </a>
      <a href="<?= url('/relatorios') ?>" data-key="relatorios" class="sb-action sb-action-indigo">
        <i class="bi bi-bar-chart-line"></i> Relatórios
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('config')): ?>
      <a href="<?= url('/configuracoes') ?>" data-key="config" class="sb-action sb-action-slate">
        <i class="bi bi-gear-fill"></i> Config. do Sistema
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('agenda')): ?>
      <a href="<?= url('/agenda') ?>" data-key="agenda" class="sb-action sb-action-teal">
        <i class="bi bi-calendar3"></i> Agenda
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('config')): ?>
      <a href="<?= url('/empresa/whatsapp') ?>" data-key="whatsapp" class="sb-action sb-action-green">
        <i class="bi bi-whatsapp"></i> WhatsApp da Empresa
      </a>
      <?php endif; ?>
    </div>
  </div>

  <script>
  (function () {
    var cont = document.getElementById('sbActions');
    if (!cont) return;
    var salvo = <?= json_encode($menuOrdem) ?>;

    // 1) Restaura a ordem salva do usuário (itens novos/sem ordem ficam no fim, na ordem padrão)
    if (salvo && salvo.length) {
      var itens = Array.prototype.slice.call(cont.querySelectorAll('.sb-action'));
      var origem = new Map();
      itens.forEach(function (el, i) { origem.set(el, i); });
      itens.sort(function (a, b) {
        var ia = salvo.indexOf(a.dataset.key); if (ia === -1) ia = 1000 + origem.get(a);
        var ib = salvo.indexOf(b.dataset.key); if (ib === -1) ib = 1000 + origem.get(b);
        return ia - ib;
      });
      itens.forEach(function (el) { cont.appendChild(el); });
    }

    // 2) Ativa o arrastar e salva no servidor.
    //    <a> são arrastáveis nativos (fantasma da URL) e brigam com o Sortable → força fallback.
    function initSort() {
      cont.querySelectorAll('.sb-action').forEach(function (a) {
        a.setAttribute('draggable', 'false');
        a.style.cursor = 'grab';
      });
      Sortable.create(cont, {
        animation: 150,
        forceFallback: true,
        fallbackTolerance: 5,
        onEnd: function () {
          var ordem = Array.prototype.map.call(
            cont.querySelectorAll('.sb-action'),
            function (el) { return el.dataset.key; }
          );
          fetch('<?= url('/menu/ordem') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
            body: JSON.stringify({ ordem: ordem })
          });
        }
      });
    }
    if (typeof Sortable !== 'undefined') { initSort(); }
    else {
      var sc = document.createElement('script');
      sc.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
      sc.onload = initSort;
      document.head.appendChild(sc);
    }
  })();
  </script>

  <div id="sbAccordion">

    <div style="padding:14px 14px 3px;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#9fb0c3;font-weight:700">Operação</div>
    <?php if (\App\Core\Auth::can('clientes')): ?>
    <a class="nav-link <?= navAtivo($uri,'/clientes') ?>" href="<?= url('/clientes') ?>">
      <i class="bi bi-people"></i> Clientes
    </a>
    <?php endif; ?>

    <!-- ── Atendimento ── -->
    <?php if (\App\Core\Auth::can('os')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbAtendimento"
              aria-expanded="<?= $grpAtendimento ? 'true' : 'false' ?>">
        <span class="sb-label">
          <i class="bi bi-clipboard2-pulse"></i> Atendimento
          <?php if (!empty($totalAberto)): ?>
          <span class="badge rounded-pill ms-1" style="background:#dc3545;font-size:.6rem"><?= $totalAberto ?></span>
          <?php endif; ?>
        </span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbAtendimento" class="collapse sb-body <?= $grpAtendimento ? 'show' : '' ?>">
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link <?= str_starts_with($uri,'/os') && !isset($_GET['status_id']) && !isset($_GET['em_garantia']) ? 'active' : '' ?>"
               href="<?= url('/os') ?>">
              <i class="bi bi-clipboard2-pulse"></i> Ordens de Serviço
            </a>
          </li>
          <?php if (!empty($totalGarSidebar)): ?>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between <?= isset($_GET['em_garantia']) ? 'active' : '' ?>"
               href="<?= url('/os') ?>?em_garantia=1" style="font-size:.84rem">
              <span><i class="bi bi-shield-check me-2" style="color:#dc3545"></i>Em Garantia</span>
              <span class="badge rounded-pill" style="background:#dc3545;font-size:.65rem"><?= $totalGarSidebar ?></span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (!empty($statusSidebar)): ?>
          <li class="nav-item px-2">
            <button class="btn btn-link w-100 text-start p-0 d-flex align-items-center justify-content-between"
                    style="color:#6c757d;font-size:.75rem;text-decoration:none"
                    data-bs-toggle="collapse" data-bs-target="#osStatusFilter" aria-expanded="false">
              <span><i class="bi bi-funnel me-1"></i>Filtrar por status</span>
              <?php if ($totalAberto): ?>
              <span class="badge rounded-pill" style="background:#dc3545;font-size:.6rem"><?= $totalAberto ?></span>
              <?php endif; ?>
            </button>
            <div class="collapse" id="osStatusFilter">
              <ul class="nav flex-column mt-1" style="padding-left:4px">
                <?php $statusIdAtivo = $_GET['status_id'] ?? null; ?>
                <?php foreach ($statusSidebar as $s): ?>
                <li class="nav-item">
                  <a href="<?= url('/os') ?>?status_id=<?= $s['id'] ?>"
                     class="nav-link d-flex align-items-center justify-content-between py-1 px-2 rounded <?= $statusIdAtivo == $s['id'] ? 'os-status-ativo' : '' ?>"
                     style="font-size:.78rem;<?= $statusIdAtivo==$s['id']?'background:rgba(255,255,255,.1)':'' ?>">
                    <div class="d-flex align-items-center gap-2" style="min-width:0;overflow:hidden;flex:1">
                      <span class="rounded-circle flex-shrink-0"
                            style="width:8px;height:8px;background:<?= e($s['cor']) ?>;display:inline-block"></span>
                      <span class="text-truncate" style="color:<?= $statusIdAtivo==$s['id']?'#fff':'#adb5bd' ?>">
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
                <li class="nav-item">
                  <a href="<?= url('/os') ?>" class="nav-link py-1 px-2 rounded" style="font-size:.75rem;color:#6c757d">
                    <i class="bi bi-x-circle me-1" style="font-size:.75rem"></i>Limpar filtro
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <?php endif; ?>
          <?php if (\App\Core\Auth::can('agenda')): ?>
          <li class="nav-item">
            <a class="nav-link <?= navAtivo($uri,'/agenda') ?>" href="<?= url('/agenda') ?>">
              <i class="bi bi-calendar3"></i> Agenda
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── CRM ── -->
    <?php if (\App\Core\Auth::can('crm')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbCrm"
              aria-expanded="<?= $grpCrm ? 'true' : 'false' ?>">
        <span class="sb-label"><i class="bi bi-people"></i> CRM</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbCrm" class="collapse sb-body <?= $grpCrm ? 'show' : '' ?>">
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/crm') ?>" href="<?= url('/crm') ?>"><i class="bi bi-funnel"></i> Pipeline</a></li>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <div style="padding:14px 14px 3px;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#9fb0c3;font-weight:700">Gestão</div>

    <!-- ── Estoque ── -->
    <?php if (\App\Core\Auth::can('estoque')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbEstoque"
              aria-expanded="<?= $grpEstoque ? 'true' : 'false' ?>">
        <span class="sb-label"><i class="bi bi-box-seam"></i> Estoque</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbEstoque" class="collapse sb-body <?= $grpEstoque ? 'show' : '' ?>">
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link <?= (str_starts_with($uri,'/produtos') && !str_starts_with($uri,'/produtos/categorias')) ? 'active' : '' ?>" href="<?= url('/produtos') ?>"><i class="bi bi-box-seam"></i> Produtos</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/produtos/categorias') ?>" href="<?= url('/produtos/categorias') ?>"><i class="bi bi-tags"></i> Categorias</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/fornecedores') ?>" href="<?= url('/fornecedores') ?>"><i class="bi bi-truck"></i> Fornecedores</a></li>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Financeiro ── -->
    <?php if (\App\Core\Auth::can('financeiro')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbFinanceiro"
              aria-expanded="<?= $grpFinanceiro ? 'true' : 'false' ?>">
        <span class="sb-label"><i class="bi bi-currency-dollar"></i> Financeiro</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbFinanceiro" class="collapse sb-body <?= $grpFinanceiro ? 'show' : '' ?>">
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link <?= str_starts_with($uri,'/financeiro') && !str_starts_with($uri,'/financeiro/categorias') ? 'active' : '' ?>" href="<?= url('/financeiro') ?>"><i class="bi bi-currency-dollar"></i> Fluxo de Caixa</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/financeiro/categorias') ?>" href="<?= url('/financeiro/categorias') ?>"><i class="bi bi-tags"></i> Categorias</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/relatorios') ?>" href="<?= url('/relatorios') ?>"><i class="bi bi-bar-chart-line"></i> Relatórios</a></li>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Marketplace ── -->
    <?php if (\App\Core\Auth::can('marketplace')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbMarketplace"
              aria-expanded="<?= $grpMarketplace ? 'true' : 'false' ?>">
        <span class="sb-label"><i class="bi bi-shop"></i> Marketplace</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbMarketplace" class="collapse sb-body <?= $grpMarketplace ? 'show' : '' ?>">
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/marketplace') && !str_starts_with($uri,'/marketplace/meus-anuncios') && !str_starts_with($uri,'/marketplace/categorias') ? 'active' : '' ?>" href="<?= url('/marketplace') ?>"><i class="bi bi-shop"></i> Vitrine</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/marketplace/meus-anuncios') ?>" href="<?= url('/marketplace/meus-anuncios') ?>"><i class="bi bi-bag-check"></i> Meus Anúncios</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/marketplace/categorias') ?>" href="<?= url('/marketplace/categorias') ?>"><i class="bi bi-tags"></i> Categorias</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/marketplace/pedidos') ?>" href="<?= url('/marketplace/pedidos') ?>"><i class="bi bi-megaphone"></i> Pedidos de Peças</a></li>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Divulgação ── -->
    <div style="padding:14px 14px 3px;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#9fb0c3;font-weight:700">Divulgação</div>
    <?php if (\App\Core\Auth::can('config')): ?>
    <a class="nav-link <?= navAtivo($uri,'/empresa/perfil-publico') ?>" href="<?= url('/empresa/perfil-publico') ?>">
      <i class="bi bi-shop-window"></i> Editar Diretório
    </a>
    <a class="nav-link <?= navAtivo($uri,'/empresa/anuncios-diretorio') ?>" href="<?= url('/empresa/publicidade') ?>">
      <i class="bi bi-megaphone"></i> <?= __('menu_publicidade') ?>
    </a>
    <?php endif; ?>
    <a class="nav-link" href="<?= url('/forum') ?>" target="_blank">
      <i class="bi bi-chat-dots"></i> Fórum Técnico <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.6rem;opacity:.5"></i>
    </a>
    <a class="nav-link" href="<?= url('/pecas') ?>" target="_blank">
      <i class="bi bi-globe2"></i> Marketplace Público <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.6rem;opacity:.5"></i>
    </a>

    <div style="padding:14px 14px 3px;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#9fb0c3;font-weight:700">Sistema</div>

    <!-- ── Configurações ── -->
    <?php if (\App\Core\Auth::can('config') || \App\Core\Auth::can('usuarios')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbConfig"
              aria-expanded="<?= $grpConfig ? 'true' : 'false' ?>">
        <span class="sb-label"><i class="bi bi-gear"></i> Configurações</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbConfig" class="collapse sb-body <?= $grpConfig ? 'show' : '' ?>">
        <ul class="nav flex-column">
          <?php if (\App\Core\Auth::can('config')): ?>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'tecnicos' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=tecnicos"><i class="bi bi-tools"></i> Técnicos</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'status' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=status"><i class="bi bi-tags"></i> Status de OS</a></li>
          <?php if (\App\Core\Auth::isAdmin()): ?>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'chat' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=chat"><i class="bi bi-chat-dots"></i> Chat da equipe</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'previsao' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=previsao"><i class="bi bi-clock-history"></i> Previsão de entrega</a></li>
          <?php endif; ?>
          <?php endif; ?>
          <?php if (\App\Core\Auth::can('usuarios')): ?>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'usuarios' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=usuarios"><i class="bi bi-person-gear"></i> Usuários</a></li>
          <?php endif; ?>
          <?php if (\App\Core\Auth::can('config')): ?>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'exibicao' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=exibicao"><i class="bi bi-fonts"></i> Exibição do texto</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'empresa' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=empresa"><i class="bi bi-building"></i> Empresa</a></li>
          <li class="nav-item"><a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'imagens' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=imagens"><i class="bi bi-image"></i> Editor de Imagens</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Ajuda ── -->
    <div class="pt-2 pb-3 border-top mt-2" style="border-color:#2d3139!important">
      <a class="nav-link <?= navAtivo($uri,'/planos') ?>" href="<?= url('/planos') ?>">
        <i class="bi bi-credit-card"></i> Planos e Assinatura
      </a>
      <a class="nav-link <?= navAtivo($uri,'/manual') ?>" href="<?= url('/manual') ?>">
        <i class="bi bi-book"></i> <?= __('menu_manual') ?>
      </a>
      <a class="nav-link" href="<?= url('/ajuda') ?>" target="_blank">
        <i class="bi bi-question-circle"></i> <?= __('menu_ajuda') ?>
      </a>
    </div>

  </div><!-- /sbAccordion -->
  <?php endif; ?>
  </div><!-- /sb-scroll -->
</nav>

<!-- Main -->
<div id="main">
  <!-- Topbar -->
  <div id="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-md-none flex-shrink-0" onclick="abrirSidebar()">
        <i class="bi bi-list"></i>
      </button>

      <!-- Data e hora -->
      <div style="text-align:left;line-height:1">
        <div id="relogio" style="color:#1e3a5f;font-weight:800;font-size:1.4rem;font-variant-numeric:tabular-nums;letter-spacing:.02em;line-height:1"></div>
        <div id="diaSemana" style="color:#f97316;font-weight:700;font-size:.88rem;text-transform:capitalize;line-height:1;margin-top:.2rem"></div>
      </div>

      <div class="d-flex align-items-center gap-3 ms-auto">

      <!-- Sino de chat da equipe (só quando o chat está habilitado para a empresa) -->
      <?php if ($chatHabilitado): ?>
      <div class="dropdown" id="chatDropdown">
        <button class="btn btn-sm position-relative" style="background:none;border:none;color:#64748b;padding:.4rem"
                data-bs-toggle="dropdown" data-bs-auto-close="outside" onclick="carregarChat()" title="Conversas das OS">
          <i class="bi bi-chat-dots-fill" style="font-size:1.2rem"></i>
          <span id="chatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                style="font-size:.6rem;display:none">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0" style="width:340px;max-height:480px;overflow:hidden;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.15)">
          <div style="background:#1e3a5f;color:#fff;padding:.9rem 1.2rem;display:flex;align-items:center;justify-content:space-between;border-radius:14px 14px 0 0">
            <span class="fw-bold"><i class="bi bi-chat-dots me-1"></i>Conversas das OS</span>
            <button onclick="marcarChatLido()" class="btn btn-sm" style="background:rgba(34,197,94,.3);color:#fff;border:none;padding:.35rem .55rem" title="Marcar tudo como lido">
              <i class="bi bi-check2-all"></i>
            </button>
          </div>
          <div id="chatLista" style="overflow-y:auto;max-height:400px">
            <div class="text-center py-4 text-muted small"><i class="bi bi-chat-square-dots d-block fs-3 mb-2 opacity-25"></i>Sem mensagens novas</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Sino de notificações -->
      <div class="dropdown" id="notifDropdown">
        <button class="btn btn-sm position-relative" style="background:none;border:none;color:#64748b;padding:.4rem"
                data-bs-toggle="dropdown" data-bs-auto-close="outside" onclick="carregarNotifs()">
          <i class="bi bi-bell-fill" style="font-size:1.2rem"></i>
          <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size:.6rem;display:none">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0" style="width:360px;max-height:480px;overflow:hidden;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.15)">
          <div style="background:#1e3a5f;color:#fff;padding:.9rem 1.2rem;display:flex;align-items:center;justify-content:space-between;border-radius:14px 14px 0 0">
            <span class="fw-bold">Notificações</span>
            <div class="d-flex gap-2">
              <button onclick="marcarTodasLidas()" class="btn btn-sm" style="background:rgba(34,197,94,.3);color:#fff;border:none;padding:.35rem .55rem" title="Marcar todas como lidas">
                <i class="bi bi-clipboard2-check"></i>
              </button>
              <button onclick="limparTodasNotifs()" class="btn btn-sm" style="background:rgba(239,68,68,.3);color:#fff;border:none;padding:.35rem .55rem" title="Excluir todas as notificações">
                <i class="bi bi-trash3"></i>
              </button>
              <a href="<?= url('/notificacoes') ?>" class="btn btn-sm" style="background:rgba(59,130,246,.3);color:#fff;border:none;padding:.35rem .55rem" title="Ver todas as notificações">
                <i class="bi bi-eye"></i>
              </a>
            </div>
          </div>
          <div id="notifLista" style="overflow-y:auto;max-height:380px">
            <div class="text-center py-4 text-muted small">
              <i class="bi bi-bell-slash d-block fs-3 mb-2 opacity-25"></i>Carregando...
            </div>
          </div>
        </div>
      </div>

      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
          <span class="fw-semibold"><?= avatar_iniciais(\App\Core\Auth::user()['nome'] ?? 'U') ?></span>
          <span class="d-none d-md-inline"><?= e(\App\Core\Auth::user()['nome'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= url('/notificacoes') ?>"><i class="bi bi-bell me-2"></i>Notificações</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalFeedback"><i class="bi bi-chat-heart me-2"></i>Enviar feedback</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
        </ul>
      </div>
      </div>
    </div>
    <h6 class="mb-0 fw-semibold topbar-title"><?= e($titulo ?? '') ?></h6>
  </div>

  <?php if ((\App\Core\Auth::user()['email'] ?? '') === 'demo@fixaos.com.br'): ?>
  <div style="background:linear-gradient(90deg,#4f46e5,#7c3aed);color:#fff;padding:.55rem 1.2rem;display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap;font-size:.88rem;text-align:center">
    <span><i class="bi bi-controller me-1"></i><strong>Você está no modo demonstração</strong> — explore à vontade! Os dados são de exemplo e reiniciam sozinhos.</span>
    <a href="<?= url('/cadastrar') ?>" style="background:#fff;color:#4f46e5;font-weight:800;text-decoration:none;padding:.35rem 1rem;border-radius:20px;white-space:nowrap"><i class="bi bi-rocket-takeoff me-1"></i>Gostei! Criar minha conta grátis</a>
  </div>
  <?php endif; ?>

  <?php
    // Aviso de vencimento de assinatura (trial_ate/licenca_ate) quando faltarem 2 dias ou menos.
    $diasAssinatura = null;
    if (\App\Core\Auth::check()) {
      $stmtAv = \App\Core\DB::pdo()->prepare("SELECT trial_ate, licenca_ate FROM empresas WHERE id = ? LIMIT 1");
      $stmtAv->execute([\App\Core\Auth::empresaId()]);
      if ($empAv = $stmtAv->fetch()) {
        $d = licenca_dias_restantes($empAv);
        if ($d !== null && $d <= 2) { $diasAssinatura = $d; }
      }
    }
  ?>
  <?php if ($diasAssinatura !== null): ?>
  <div style="background:<?= $diasAssinatura <= 0 ? '#dc2626' : '#f59e0b' ?>;color:#fff;padding:.55rem 1.2rem;display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap;font-size:.88rem;text-align:center">
    <span><i class="bi bi-exclamation-triangle-fill me-1"></i>
      <?php if ($diasAssinatura <= 0): ?>
        <strong>Sua assinatura venceu.</strong> Ative um plano para continuar usando o FixaOS sem interrupções.
      <?php else: ?>
        <strong>Sua assinatura vence em <?= $diasAssinatura ?> dia<?= $diasAssinatura === 1 ? '' : 's' ?>.</strong> Ative um plano para não perder o acesso.
      <?php endif; ?>
    </span>
    <a href="<?= url('/planos') ?>" style="background:#fff;color:#111827;font-weight:800;text-decoration:none;padding:.35rem 1rem;border-radius:20px;white-space:nowrap"><i class="bi bi-rocket-takeoff me-1"></i>Ver planos</a>
  </div>
  <?php endif; ?>

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

  <!-- Rodapé do sistema -->
  <?php $emp = require BASE_PATH . '/config/empresa.php'; ?>
  <footer style="border-top:1px solid #e5e7eb;background:#fff;padding:.9rem 1.5rem;margin-top:1.5rem;color:#94a3b8;font-size:.78rem">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        &copy; <?= date('Y') ?> <strong style="color:#64748b"><?= e($emp['nome']) ?></strong> — <?= e($emp['descricao']) ?>
        <?php if (!empty($emp['razao'])): ?> &middot; <?= e($emp['razao']) ?><?php endif; ?>
        <?php if (!empty($emp['cnpj'])): ?> &middot; CNPJ <?= e($emp['cnpj']) ?><?php endif; ?>
      </div>
      <div class="d-flex align-items-center gap-3">
        <a href="#" data-bs-toggle="modal" data-bs-target="#modalFeedback" style="color:#0d9488;text-decoration:none;font-weight:600"><i class="bi bi-chat-heart me-1"></i>Enviar feedback</a>
        <a href="<?= url('/privacidade') ?>" target="_blank" style="color:#94a3b8;text-decoration:none">Privacidade</a>
        <a href="<?= url('/termos') ?>" target="_blank" style="color:#94a3b8;text-decoration:none">Termos</a>
        <span style="background:#f1f5f9;border-radius:20px;padding:.15rem .65rem;color:#64748b;font-weight:600">v<?= e($emp['versao']) ?></span>
      </div>
    </div>
    <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9;display:flex;flex-wrap:wrap;justify-content:center;gap:1.4rem;color:#94a3b8">
      <span><i class="bi bi-shield-lock-fill me-1" style="color:#16a34a"></i>Conexão segura (SSL)</span>
      <span><i class="bi bi-geo-alt-fill me-1" style="color:#0d9488"></i>Servidores no Brasil</span>
      <span><i class="bi bi-file-earmark-check-fill me-1" style="color:#4f46e5"></i>Conforme a LGPD</span>
    </div>
  </footer>

  <!-- Modal de Feedback (crítica / elogio / sugestão) -->
  <div class="modal fade" id="modalFeedback" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="<?= url('/feedback') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="pagina" id="fbPagina" value="">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-chat-heart-fill me-2" style="color:#0d9488"></i>Fale com a gente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-3">Sua opinião ajuda o FixaOS a melhorar. O que você quer nos dizer?</p>
            <div class="btn-group w-100 mb-3" role="group" aria-label="Tipo de feedback">
              <input type="radio" class="btn-check" name="tipo" id="fbCritica" value="critica" autocomplete="off">
              <label class="btn btn-outline-danger" for="fbCritica"><i class="bi bi-emoji-frown me-1"></i>Crítica</label>
              <input type="radio" class="btn-check" name="tipo" id="fbElogio" value="elogio" autocomplete="off">
              <label class="btn btn-outline-success" for="fbElogio"><i class="bi bi-emoji-smile me-1"></i>Elogio</label>
              <input type="radio" class="btn-check" name="tipo" id="fbSugestao" value="sugestao" autocomplete="off" checked>
              <label class="btn btn-outline-primary" for="fbSugestao"><i class="bi bi-lightbulb me-1"></i>Sugestão</label>
            </div>
            <textarea name="mensagem" class="form-control" rows="4" maxlength="2000" placeholder="Escreva aqui o que achou, o que faltou ou o que poderia melhorar..." required></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn" style="background:#0d9488;color:#fff"><i class="bi bi-send me-1"></i>Enviar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    var fp = document.getElementById('fbPagina');
    if (fp) fp.value = location.pathname + location.search;
  });
  </script>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/masks.js') ?>"></script>
<script>
// Mover todos os modais e offcanvas para o body (Bootstrap best practice)
// Evita problemas de z-index com elementos pai que criam stacking context
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.modal, .offcanvas').forEach(function(el) {
    if (el.parentElement !== document.body) {
      document.body.appendChild(el);
    }
  });
});
</script>
<script>
function abrirSidebar() {
  document.getElementById('sidebar').classList.add('show');
  document.getElementById('sidebarOverlay').classList.add('show');
}
function fecharSidebar() {
  document.getElementById('sidebar').classList.remove('show');
  document.getElementById('sidebarOverlay').classList.remove('show');
}
</script>
<script>
// ── Relógio ───────────────────────────────────────────────────────────
const dias = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
const mesesR = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
function atualizarRelogio() {
  const n = new Date();
  const h = String(n.getHours()).padStart(2,'0');
  const m = String(n.getMinutes()).padStart(2,'0');
  const s = String(n.getSeconds()).padStart(2,'0');
  document.getElementById('relogio').textContent   = `${h}:${m}:${s}`;
  document.getElementById('diaSemana').textContent = `${dias[n.getDay()]}, ${n.getDate()} ${mesesR[n.getMonth()]}`;
}
atualizarRelogio();
setInterval(atualizarRelogio, 1000);

// ── Notificações ──────────────────────────────────────────────────────
const NOTIF_URL     = '<?= url('/api/notificacoes') ?>';
const NOTIF_LER_URL = '<?= url('/notificacoes/') ?>';
const NOTIF_TODAS   = '<?= url('/notificacoes/todas-lidas') ?>';
const CSRF_TOKEN    = '<?= csrf_token() ?>';

const coresNotif = {
  danger:'#ef4444', warning:'#f59e0b', success:'#22c55e', info:'#3b82f6', primary:'#6366f1'
};

async function carregarNotifs() {
  try {
    const r = await fetch(NOTIF_URL);
    const d = await r.json();
    atualizarBadge(d.total);
    renderNotifs(d.lista);
  } catch(e) {}
}

function atualizarBadge(total) {
  const b = document.getElementById('notifBadge');
  if (total > 0) { b.textContent = total > 99 ? '99+' : total; b.style.display = 'block'; }
  else { b.style.display = 'none'; }
}

function renderNotifs(lista) {
  const el = document.getElementById('notifLista');
  if (!lista || !lista.length) {
    el.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-bell-slash d-block fs-3 mb-2 opacity-25"></i>Sem notificações</div>';
    return;
  }
  el.innerHTML = lista.map(n => {
    const cor = coresNotif[n.cor] || '#6366f1';
    const lida = n.lida == 1;
    return `<div style="padding:.9rem 1.2rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.8rem;align-items:flex-start;${lida?'opacity:.6':'background:#fff9f5'}">
      <div style="width:36px;height:36px;border-radius:8px;background:${cor}15;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px">
        <i class="bi ${n.icone}" style="color:${cor}"></i>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:.85rem;font-weight:${lida?'400':'700'};color:#0f172a;line-height:1.3">${n.titulo}</div>
        ${n.mensagem ? `<div style="font-size:.75rem;color:#64748b;margin-top:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${n.mensagem}</div>` : ''}
        <div style="font-size:.72rem;color:#94a3b8;margin-top:.3rem">${formatData(n.criado_em)}</div>
      </div>
      ${n.link ? `<a href="${n.link}" onclick="lerNotif(${n.id})" style="color:${cor};font-size:.75rem;white-space:nowrap;margin-top:2px;text-decoration:none"><i class="bi bi-arrow-right"></i></a>` : ''}
      <button onclick="event.stopPropagation();excluirNotif(${n.id})" title="Excluir notificação"
              style="border:none;background:none;color:#cbd5e1;font-size:.85rem;padding:2px 4px;margin-top:2px;flex-shrink:0;line-height:1"
              onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#cbd5e1'">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>`;
  }).join('');
}

function formatData(dt) {
  const d = new Date(dt.replace(' ','T'));
  const agora = new Date();
  const diff = Math.floor((agora - d) / 60000);
  if (diff < 1) return 'Agora';
  if (diff < 60) return `${diff}min atrás`;
  if (diff < 1440) return `${Math.floor(diff/60)}h atrás`;
  return d.toLocaleDateString('pt-BR');
}

function lerNotif(id) {
  fetch(NOTIF_LER_URL + id + '/ler');
  carregarNotifs();
}

function marcarTodasLidas() {
  fetch(NOTIF_TODAS, { method:'POST', headers:{'X-CSRF-TOKEN': CSRF_TOKEN} })
    .then(() => carregarNotifs());
}

function excluirNotif(id) {
  fetch(NOTIF_LER_URL + id, { method:'DELETE', headers:{'X-CSRF-TOKEN': CSRF_TOKEN} })
    .then(() => carregarNotifs());
}

function limparTodasNotifs() {
  if (!confirm('Excluir todas as notificações? Essa ação não pode ser desfeita.')) return;
  fetch('<?= url("/notificacoes/limpar-todas") ?>', { method:'POST', headers:{'X-CSRF-TOKEN': CSRF_TOKEN} })
    .then(() => carregarNotifs());
}

// Verificar notificações a cada 2 minutos
carregarNotifs();
setInterval(carregarNotifs, 120000);

// ── Sino de chat da equipe (contador + som ao chegar mensagem) ──
const CHAT_STATUS_URL = '<?= url('/api/chat/status') ?>';
const CHAT_LIDO_URL   = '<?= url('/api/chat/lido') ?>';
const CHAT_SOM        = <?= $chatSom ? 'true' : 'false' ?>;         // aviso sonoro ligado?
const CHAT_INSISTENTE = <?= $chatInsistente ? 'true' : 'false' ?>;  // repetir a cada 10s?
let chatPrevNaoLidas = null, chatAudio = null;
function tocarBeepChat() {
  try {
    if (!chatAudio) chatAudio = new (window.AudioContext || window.webkitAudioContext)();
    if (chatAudio.state === 'suspended') chatAudio.resume();
    const ctx = chatAudio, o = ctx.createOscillator(), g = ctx.createGain();
    o.connect(g); g.connect(ctx.destination); o.type = 'sine';
    o.frequency.setValueAtTime(880, ctx.currentTime);
    o.frequency.setValueAtTime(660, ctx.currentTime + 0.12);
    g.gain.setValueAtTime(0.0001, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.4);
    o.start(); o.stop(ctx.currentTime + 0.4);
  } catch (e) {}
}
function escC(s){ const d=document.createElement('div'); d.textContent=(s==null?'':s); return d.innerHTML; }
async function carregarChat() {
  try {
    const d = await (await fetch(CHAT_STATUS_URL)).json();
    const n = d.nao_lidas || 0;
    const b = document.getElementById('chatBadge');
    if (b) { if (n > 0) { b.textContent = n > 99 ? '99+' : n; b.style.display=''; } else { b.style.display='none'; } }
    const lista = document.getElementById('chatLista');
    if (lista) {
      if (!d.itens || !d.itens.length) {
        lista.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-chat-square-dots d-block fs-3 mb-2 opacity-25"></i>Sem mensagens novas</div>';
      } else {
        lista.innerHTML = d.itens.map(function(m){
          return '<a href="<?= url('/os/') ?>'+m.os_id+'" class="d-block text-decoration-none text-dark px-3 py-2 border-bottom" style="font-size:.85rem">'
            + '<div class="d-flex justify-content-between"><span class="fw-semibold text-primary"><i class="bi bi-tools me-1"></i>OS '+escC(m.numero)+'</span><span class="text-muted" style="font-size:.72rem">'+escC(m.quando)+'</span></div>'
            + '<div><span class="fw-semibold">'+escC(m.usuario_nome||'—')+':</span> '+escC((m.mensagem||'').slice(0,60))+'</div></a>';
        }).join('');
      }
    }
    // Som: respeita as configs da empresa.
    // - CHAT_SOM off  -> nunca bipa.
    // - CHAT_INSISTENTE on -> bipa a cada ciclo (10s) enquanto n>0.
    // - CHAT_INSISTENTE off -> bipa só quando chega mensagem nova (n aumentou).
    if (CHAT_SOM && n > 0 && (CHAT_INSISTENTE || (chatPrevNaoLidas !== null && n > chatPrevNaoLidas))) {
      tocarBeepChat();
    }
    chatPrevNaoLidas = n;
  } catch (e) {}
}
async function marcarChatLido() {
  try { await fetch(CHAT_LIDO_URL, { method:'POST', headers:{'X-CSRF-Token':'<?= csrf_token() ?>'} }); } catch(e){}
  carregarChat();
}
<?php if ($chatHabilitado): ?>
carregarChat();
setInterval(carregarChat, 10000);
<?php endif; ?>

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

<!-- Chat da equipe, Previsão de entrega e Exibição do texto agora vivem nas abas de /configuracoes -->
<!-- ===== Mentor IA (assistente do dono) ===== -->
<style>
  #mentorFab{position:fixed;right:22px;bottom:22px;z-index:1040;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;
    padding:12px 18px;border-radius:999px;color:#fff;font-weight:600;font-size:15px;font-family:inherit;
    background:linear-gradient(135deg,#5b53e6,#8b5cf6);box-shadow:0 8px 24px rgba(91,83,230,.42);transition:transform .15s}
  #mentorFab:hover{transform:translateY(-2px)}
  @media(max-width:520px){#mentorFab .lbl{display:none}#mentorFab{padding:14px;border-radius:50%}}
  #mentorPanel{position:fixed;right:22px;bottom:22px;z-index:1041;width:min(384px,calc(100vw - 28px));height:min(566px,calc(100vh - 36px));
    background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(20,20,50,.30);display:none;flex-direction:column;overflow:hidden}
  #mentorPanel .mh{background:linear-gradient(135deg,#5b53e6,#8b5cf6);color:#fff;padding:14px 16px;display:flex;align-items:center;gap:11px}
  #mentorPanel .mh .ico{width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:18px}
  #mentorPanel .mh h6{margin:0;font-size:15px;font-weight:700;line-height:1.1}
  #mentorPanel .mh small{opacity:.85;font-size:11.5px}
  #mentorPanel .mh button{margin-left:auto;background:none;border:none;color:#fff;font-size:22px;line-height:1;cursor:pointer;opacity:.9}
  #mentorMsgs{flex:1;overflow-y:auto;padding:16px;background:#f6f7fb;display:flex;flex-direction:column;gap:10px}
  .m-msg{max-width:85%;padding:10px 13px;border-radius:14px;font-size:14px;line-height:1.5;white-space:pre-wrap;word-wrap:break-word}
  .m-bot{background:#fff;border:1px solid #e7e9f2;align-self:flex-start;border-bottom-left-radius:4px;color:#1f2430}
  .m-user{background:#5b53e6;color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
  .m-chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:2px}
  .m-chip{background:#fff;border:1px solid #d6d9e8;color:#4b4de0;border-radius:999px;padding:6px 11px;font-size:12.5px;cursor:pointer;transition:background .12s}
  .m-chip:hover{background:#eeeffb}
  .m-typing{align-self:flex-start;color:#8a90a2;font-size:13px;padding:2px 6px}
  #mentorForm{display:flex;gap:8px;padding:12px;border-top:1px solid #eceef4;background:#fff}
  #mentorInput{flex:1;border:1px solid #d6d9e8;border-radius:12px;padding:10px 13px;font-size:14px;resize:none;font-family:inherit;max-height:92px;line-height:1.4}
  #mentorInput:focus{outline:none;border-color:#5b53e6;box-shadow:0 0 0 3px rgba(91,83,230,.15)}
  #mentorSend{border:none;background:#5b53e6;color:#fff;border-radius:12px;padding:0 15px;cursor:pointer;font-size:16px}
  #mentorSend:disabled{opacity:.5;cursor:default}
  .m-disc{font-size:10.5px;color:#9aa0b0;text-align:center;padding:2px 12px 8px;background:#fff}
</style>
<button id="mentorFab" onclick="mentorToggle(true)" title="Mentor FixaOS"><span style="font-size:18px">💡</span><span class="lbl">Mentor</span></button>
<div id="mentorPanel" role="dialog" aria-label="Mentor FixaOS">
  <div class="mh">
    <div class="ico">💡</div>
    <div><h6>Mentor FixaOS</h6><small>Seu parceiro pra tocar a loja</small></div>
    <button onclick="mentorToggle(false)" aria-label="Fechar">&times;</button>
  </div>
  <div id="mentorMsgs"></div>
  <div class="m-disc">Orientação geral de um assistente de IA — confira decisões importantes.</div>
  <form id="mentorForm" onsubmit="return mentorEnviar(event)">
    <textarea id="mentorInput" rows="1" placeholder="Pergunte algo… ex.: quanto cobrar por troca de tela?" autocomplete="off"></textarea>
    <button id="mentorSend" type="submit" title="Enviar" aria-label="Enviar">&#10148;</button>
  </form>
</div>
<script>
(function(){
  var URL_MENTOR='<?= url('/mentor/perguntar') ?>', CSRF='<?= csrf_token() ?>';
  var hist=[], ini=false;
  var sugestoes=['Quanto cobrar por troca de tela?','Como dou garantia sem me prejudicar?','O que escrever na OS pra me proteger?','Meu caixa tá no vermelho, e agora?','Como faço meu primeiro atendimento?'];
  function elc(cls,txt){var d=document.createElement('div');d.className=cls;if(txt!=null)d.textContent=txt;return d;}
  function box(){return document.getElementById('mentorMsgs');}
  function fim(){var m=box();m.scrollTop=m.scrollHeight;}
  function addBot(t){box().appendChild(elc('m-msg m-bot',t));fim();}
  function addUser(t){box().appendChild(elc('m-msg m-user',t));fim();}
  window.mentorToggle=function(abrir){
    document.getElementById('mentorPanel').style.display=abrir?'flex':'none';
    document.getElementById('mentorFab').style.display=abrir?'none':'flex';
    if(abrir){ if(!ini){boasVindas();ini=true;} setTimeout(function(){document.getElementById('mentorInput').focus();},80); }
  };
  function boasVindas(){
    addBot('Opa! 👋 Sou o Mentor do FixaOS. Pode me perguntar de tudo sobre tocar a sua assistência — preço, garantia, caixa, atendimento — ou como fazer algo aqui no sistema. Por onde começamos?');
    var wrap=elc('m-chips');
    sugestoes.forEach(function(s){var c=elc('m-chip',s);c.onclick=function(){enviar(s);};wrap.appendChild(c);});
    box().appendChild(wrap);fim();
  }
  window.mentorEnviar=function(e){e.preventDefault();var v=document.getElementById('mentorInput').value.trim();if(v)enviar(v);return false;};
  function enviar(texto){
    var inp=document.getElementById('mentorInput'),btn=document.getElementById('mentorSend');
    inp.value='';inp.style.height='auto';
    var chips=document.querySelector('#mentorMsgs .m-chips');if(chips)chips.remove();
    addUser(texto);hist.push({role:'user',content:texto});btn.disabled=true;
    var typing=elc('m-typing','Mentor está pensando…');box().appendChild(typing);fim();
    fetch(URL_MENTOR,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF},
      body:'pergunta='+encodeURIComponent(texto)+'&historico='+encodeURIComponent(JSON.stringify(hist.slice(-8)))})
      .then(function(r){return r.json();}).then(function(j){
        typing.remove();btn.disabled=false;
        if(j.ok){addBot(j.resposta);hist.push({role:'assistant',content:j.resposta});}
        else{addBot(j.erro||'Não consegui responder agora. Tenta de novo?');}
      }).catch(function(){typing.remove();btn.disabled=false;addBot('Falha de conexão. Tenta de novo?');});
  }
  var inp=document.getElementById('mentorInput');
  inp.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();mentorEnviar(e);}});
  inp.addEventListener('input',function(){this.style.height='auto';this.style.height=Math.min(this.scrollHeight,92)+'px';});
})();
</script>
</body>
</html>
