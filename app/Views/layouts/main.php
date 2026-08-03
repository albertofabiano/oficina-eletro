<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light dark">
<script>
/* Aplica o tema antes de qualquer CSS carregar, pra não piscar branco.
   A preferência salva no servidor (sessão) vence a local — só cai pro
   localStorage quando ainda não há usuário logado com preferência salva. */
(function () {
  var srv = <?= json_encode($_SESSION['usuario']['tema'] ?? null) ?>;
  var pref = srv || localStorage.getItem('fx_tema') || 'auto';
  if (srv) { try { localStorage.setItem('fx_tema', srv); } catch (e) {} }
  var escuro = pref === 'dark' || (pref === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.dataset.theme = escuro ? 'dark' : 'light';
})();
</script>
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
<link rel="stylesheet" href="<?= url('/css/tokens.css') ?>?v=<?= filemtime(BASE_PATH.'/public/css/tokens.css') ?>">
<script src="<?= url('/js/theme.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/theme.js') ?>"></script>
<script src="<?= url('/js/offline-cache.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/offline-cache.js') ?>"></script>
<style>
.is-valid   { border-color: #198754 !important; }
.is-invalid { border-color: #dc3545 !important; }
input.border-success { border-color: #198754 !important; box-shadow: 0 0 0 .2rem rgba(25,135,84,.15); }
</style>
<style>
:root { --sidebar-w: 246px; }
body { background: var(--surface-0, #f0f2f5); }
#sidebar {
  width: var(--sidebar-w); height: 100vh; background: var(--surface-nav, #131A2B);
  position: fixed; top: 0; left: 0; z-index: 1000;
  display: flex; flex-direction: column; overflow: hidden;
  transition: transform .25s;
  /* A barra é um "rail" de chrome com fundo fixo (--surface-nav não varia entre
     temas, de propósito — ver notas do redesenho). --text-2/--text-3 são
     desenhados pra inverter com o tema; num fundo que não inverte eles ficariam
     ilegíveis no tema claro. Por isso os neutros aqui ficam presos nos MESMOS
     valores que --text-2/--text-3 já assumem no tema escuro. */
  --sb-text: #A9B0BF; --sb-icon: #8B93A5; --sb-label: #6E7890;
  --sb-border: rgba(255,255,255,.08); --sb-hover: rgba(255,255,255,.06);
}
#sidebar .brand { padding: 16px 16px 14px; border-bottom: 1px solid var(--sb-border); flex-shrink: 0; display: flex; align-items: center; gap: 8px; }
/* Área rolável do menu (logo fica fixo no topo) */
.sb-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 12px 10px 20px;
  scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.18) transparent; }
.sb-scroll::-webkit-scrollbar { width: 8px; }
.sb-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }

/* ── Zona de ação ── */
.sb-primary {
  display:flex; align-items:center; justify-content:space-between; gap:8px;
  width:100%; border:none; text-decoration:none; cursor:pointer;
  background:var(--accent); color:#fff; font-size:.86rem; font-weight:700;
  padding:10px 12px; border-radius:var(--radius); margin-bottom:8px;
  transition:background .15s, transform .1s;
}
.sb-primary:hover { background:var(--accent-hover); color:#fff; }
.sb-primary:active { transform:scale(.98); }
.sb-primary .lbl { display:flex; align-items:center; gap:8px; }
.sb-primary i { font-size:1rem; }
.sb-kbd {
  font-size:.68rem; font-weight:700; color:rgba(255,255,255,.85); background:rgba(255,255,255,.18);
  border-radius:5px; padding:2px 6px; letter-spacing:.02em; font-family:ui-monospace,Menlo,monospace;
}
.sb-tonal-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px; }
.sb-tonal-row.single { grid-template-columns:1fr; }
.sb-tonal {
  position:relative; display:flex; flex-direction:column; align-items:center; gap:5px;
  text-decoration:none; border-radius:var(--radius); padding:10px 8px 9px;
  font-size:.74rem; font-weight:700; text-align:center; line-height:1.15;
  border:.5px solid transparent; transition:filter .15s, transform .1s;
}
.sb-tonal:hover { filter:brightness(1.08); }
.sb-tonal:active { transform:scale(.98); }
.sb-tonal i { font-size:1.05rem; }
.sb-tonal.accent { background:var(--accent-bg); color:var(--accent-text); border-color:rgba(55,138,221,.35); }
.sb-tonal.success { background:var(--success-bg); color:var(--success); border-color:rgba(15,110,86,.35); }
.sb-status-dot {
  position:absolute; top:7px; right:8px; width:6px; height:6px; border-radius:50%;
  background:var(--text-4); box-shadow:0 0 0 2px var(--success-bg);
}
.sb-status-dot.on { background:var(--success-fill); }

.sb-divider { height:.5px; background:var(--sb-border); margin:2px 2px 10px; }

/* ── Navegação ── */
.sb-label { padding:14px 11px 4px; font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--sb-label); }

#sidebar .nav-link, .sb-group-btn {
  display:flex; align-items:center; gap:10px; width:100%;
  padding:8px 11px; margin:1px 0; border-radius:var(--radius);
  color:var(--sb-text); text-decoration:none; font-size:.85rem; font-weight:500;
  cursor:pointer; border:none; background:none; font-family:inherit; text-align:left;
  transition:background .12s, color .12s; position:relative;
}
#sidebar .nav-link i, .sb-group-btn i { color:var(--sb-icon); font-size:16px; width:16px; text-align:center; flex-shrink:0; transition:color .12s; }
#sidebar .nav-link:hover, .sb-group-btn:hover { background:var(--sb-hover); color:#fff; }
#sidebar .nav-link:hover i, .sb-group-btn:hover i { color:#fff; }

#sidebar .nav-link.active { background:var(--accent-bg); color:var(--accent-text); font-weight:700; }
#sidebar .nav-link.active i { color:var(--accent-text); }
#sidebar .nav-link.active::before {
  content:""; position:absolute; left:0; top:6px; bottom:6px; width:3px; border-radius:0 3px 3px 0; background:var(--accent);
}

.sb-txt { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sb-badge { flex-shrink:0; font-size:.68rem; font-weight:700; padding:1px 7px; border-radius:999px; background:var(--danger-bg); color:var(--danger); }
.sb-badge.neutral { background:rgba(255,255,255,.1); color:var(--sb-text); }
.sb-chevron { margin-left:auto; font-size:.68rem; color:var(--sb-icon); transition:transform .2s, color .12s; flex-shrink:0; }
.sb-group-btn[aria-expanded="true"] .sb-chevron { transform:rotate(180deg); }
.sb-group-btn[aria-expanded="true"] { color:#fff; }
.sb-group-btn[aria-expanded="true"] i { color:#fff; }

.sb-body { overflow:hidden; }
.sb-body .nav-link { padding-left:37px; font-size:.81rem; }
.sb-body .sb-group-btn { padding-left:37px; font-size:.81rem; }
.sb-body .sb-body .nav-link { padding-left:56px; font-size:.78rem; }
.sb-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; display:inline-block; }

.sb-ext-ic { margin-left:auto; color:var(--sb-icon); font-size:.68rem; opacity:.7; }

.sb-footer { margin-top:6px; padding-top:8px; border-top:.5px solid var(--sb-border); }
#main { margin-left: var(--sidebar-w); min-height: 100vh; }
.page-content { padding: 1.5rem; }
.stat-card { border: none; border-radius: 12px; }
.badge-prioridade-urgente { background: #dc3545; }
.badge-prioridade-alta    { background: #fd7e14; }
.badge-prioridade-normal  { background: #0d6efd; }
.badge-prioridade-baixa   { background: #6c757d; }

/* ── Topbar ── */
#topbar {
  background: var(--surface-1); border-bottom: .5px solid var(--border);
  padding: 11px 20px; position: sticky; top: 0; z-index: 1030;
  display: flex; align-items: center; gap: 14px;
}
.tb-title { min-width: 0; flex-shrink: 0; }
.tb-titulo {
  margin: 0; font-size: 16px; font-weight: 600; color: var(--text-1);
  text-transform: none !important; line-height: 1.25;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;
}
.tb-meta { font-size: 11px; color: var(--text-3); line-height: 1.3; margin-top: 1px; white-space: nowrap; }

.tb-busca {
  position: relative; display: flex; align-items: center; gap: 7px;
  margin-left: 10px; max-width: 260px; width: 100%; flex: 1 1 auto;
  background: var(--surface-2); border: .5px solid var(--border); border-radius: 8px;
  padding: 7px 9px; transition: border-color .12s;
}
.tb-busca:focus-within { border-color: var(--accent); }
.tb-busca > i.bi-search { font-size: 16px; color: var(--text-3); flex-shrink: 0; }
.tb-busca input {
  border: none; background: none; outline: none; flex: 1 1 auto; min-width: 0;
  font-size: 12.5px; color: var(--text-1); padding: 0;
}
.tb-busca input::placeholder { color: var(--text-3); }
.tb-busca-kbd {
  flex-shrink: 0; font-size: 10.5px; font-weight: 600; color: var(--text-3);
  background: var(--surface-1); border: .5px solid var(--border); border-radius: 4px; padding: 1px 5px;
  font-family: ui-monospace, Menlo, monospace; line-height: 1.4;
}
.tb-busca:focus-within .tb-busca-kbd { display: none; }
.tb-busca-mobile-btn {
  display: none; background: none; border: none; color: var(--text-2); padding: 6px;
  align-items: center; justify-content: center; flex-shrink: 0;
}
.tb-busca-mobile-btn i { font-size: 17px; }

.tb-actions { display: flex; align-items: center; gap: 12px; margin-left: auto; flex-shrink: 0; }

.tb-nova-os {
  display: flex; align-items: center; gap: 6px; background: var(--accent); color: #fff;
  border-radius: 8px; padding: 7px 13px; font-size: 13px; font-weight: 600; text-decoration: none;
  white-space: nowrap; transition: background .15s; flex-shrink: 0;
}
.tb-nova-os:hover { background: var(--accent-hover); color: #fff; }
.tb-nova-os i { font-size: 15px; }

#fxThemeSwitch { gap: 10px; }
#fxThemeSwitch .btn { border-radius: 6px !important; margin-left: 0 !important; }

.tb-theme-toggle {
  background: none; border: none; color: var(--text-2); padding: 4px;
  display: flex; align-items: center; justify-content: center; line-height: 1; flex-shrink: 0;
}
.tb-theme-toggle i { font-size: 18px; }
.tb-theme-toggle:hover { color: var(--text-1); }

.tb-bell {
  position: relative; background: none; border: none; color: var(--text-2); padding: 4px;
  display: flex; align-items: center; justify-content: center; line-height: 1;
}
.tb-bell i { font-size: 19px; }
.tb-bell-badge {
  position: absolute; top: -3px; right: -5px; min-width: 15px; height: 15px; padding: 0 3px;
  border-radius: 999px; background: var(--danger-fill); color: #fff; font-size: 9.5px; font-weight: 700;
  align-items: center; justify-content: center; line-height: 1;
}
/* ── Painel de notificações ── */
/* data-bs-display="static" no gatilho desliga o Popper pra esse dropdown —
   #topbar é position:sticky, e o Popper vinha calculando a posição errada
   por causa disso (bug conhecido do Bootstrap com headers sticky), jogando
   o painel colado na borda esquerda em vez de alinhado à direita do sino.
   Com o Popper fora da jogada, o alinhamento fica só por conta deste CSS. */
#notifDropdown { position: relative; }
#notifDropdown .tb-notif-panel {
  position: absolute; top: calc(100% + 8px); right: 0; left: auto; margin: 0;
}
.tb-notif-panel {
  width: 380px; max-width: calc(100vw - 24px); max-height: 480px; background: var(--surface-1);
  border: .5px solid var(--border); border-radius: var(--radius-lg); box-shadow: 0 10px 40px rgba(0,0,0,.15);
  overflow: hidden; display: flex; flex-direction: column;
}
/* Regra 1 do redesenho: caixa normal em tudo aqui dentro, mesmo com a
   preferência "Exibição do texto: maiúsculas" ligada — só os rótulos de
   seção (Precisa de ação / Recentes) ficam em maiúsculas, de propósito. */
.tb-notif-panel, .tb-notif-panel * { text-transform: none !important; }
.tb-notif-header {
  flex-shrink: 0; padding: .85rem 1.1rem; display: flex; align-items: center; justify-content: space-between;
  border-bottom: .5px solid var(--border); background: var(--surface-1); gap: 10px;
}
.tb-notif-header-titulo { font-size: 14px; font-weight: 600; color: var(--text-1); }
.tb-notif-header-acoes { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
.tb-notif-link { font-size: 12px; color: var(--accent-text); text-decoration: none; background: none; border: none; padding: 0; font-family: inherit; white-space: nowrap; }
.tb-notif-link:hover { text-decoration: underline; }
.tb-notif-gear { color: var(--text-3); font-size: 17px; display: flex; align-items: center; text-decoration: none; }
.tb-notif-gear:hover { color: var(--text-1); }
.tb-notif-close { color: var(--text-3); font-size: 16px; background: none; border: none; padding: 0; display: flex; align-items: center; }
.tb-notif-close:hover { color: var(--text-1); }
.tb-notif-body { overflow-y: auto; flex: 1 1 auto; padding: 6px 0; }
.tb-notif-sec-label {
  padding: 10px 1.1rem 4px; font-size: 10.5px; font-weight: 700; letter-spacing: .6px;
  text-transform: uppercase !important; color: var(--text-3);
}
.tb-notif-footer { flex-shrink: 0; padding: .6rem; text-align: center; border-top: .5px solid var(--border); }
.tb-notif-footer a { font-size: 12.5px; color: var(--accent-text); text-decoration: none; }
.tb-notif-footer a:hover { text-decoration: underline; }
.tb-notif-vazio { padding: 1.6rem 1.1rem; text-align: center; font-size: 12.5px; color: var(--text-3); }

/* Grupo "Precisa de ação" — pendência agrupada, uma linha, clicável */
.tb-notif-grupo {
  display: flex; align-items: flex-start; gap: 10px; margin: 0 8px 6px; padding: 10px 10px 10px 11px;
  border-radius: 8px; text-decoration: none; border-left: 3px solid transparent; cursor: pointer;
}
.tb-notif-grupo:hover { filter: brightness(.97); }
.tb-notif-grupo.danger  { background: var(--danger-bg);  border-left-color: var(--danger-fill); }
.tb-notif-grupo.warning { background: var(--warning-bg); border-left-color: var(--warning-fill); }
.tb-notif-grupo-ic { font-size: 16px; margin-top: 1px; flex-shrink: 0; }
.tb-notif-grupo.danger  .tb-notif-grupo-ic { color: var(--danger-fill); }
.tb-notif-grupo.warning .tb-notif-grupo-ic { color: var(--warning-fill); }
.tb-notif-grupo-txt { flex: 1; min-width: 0; }
.tb-notif-grupo-titulo { font-size: 13px; font-weight: 600; color: var(--text-1); line-height: 1.35; text-transform: none; }
.tb-notif-grupo-sub { font-size: 11.5px; color: var(--text-3); margin-top: 2px; line-height: 1.35; }
.tb-notif-grupo-chev { color: var(--text-3); font-size: 13px; margin-top: 3px; flex-shrink: 0; }

/* Item "Recentes" — evento individual */
.tb-notif-item { position: relative; display: flex; align-items: flex-start; gap: 10px; margin: 0 8px 2px; padding: 9px 30px 9px 10px; border-radius: 8px; text-decoration: none; }
.tb-notif-item.nao-lida { background: var(--accent-bg); }
.tb-notif-ic {
  width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 14px; margin-top: 1px;
}
.tb-notif-item-txt { flex: 1; min-width: 0; }
.tb-notif-item-titulo { font-size: 13px; line-height: 1.35; color: var(--text-1); text-transform: none; }
.tb-notif-item.lida .tb-notif-item-titulo { color: var(--text-2); font-weight: 400; }
.tb-notif-item:not(.lida) .tb-notif-item-titulo { font-weight: 600; }
.tb-notif-item-sub { font-size: 11.5px; color: var(--text-3); margin-top: 2px; line-height: 1.4; }
.tb-notif-item-tempo { font-size: 11px; color: var(--text-4); margin-top: 3px; }
.tb-notif-dot { position: absolute; top: 15px; right: 12px; width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }
.tb-notif-item-del {
  position: absolute; top: 8px; right: 8px; border: none; background: none; color: var(--text-3);
  font-size: 13px; padding: 3px 5px; line-height: 1; opacity: 0; transition: opacity .12s; cursor: pointer;
}
.tb-notif-item:hover .tb-notif-item-del { opacity: 1; }
.tb-notif-item:hover .tb-notif-dot { display: none; }
.tb-notif-item-del:hover { color: var(--danger); }

.tb-user { display: flex; align-items: center; gap: 8px; background: none; border: none; padding: 2px; }
.tb-avatar {
  width: 28px; height: 28px; border-radius: 50%; background: var(--accent); color: #fff;
  display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.tb-user-name { font-size: 12.5px; color: var(--text-2); font-weight: 600; white-space: nowrap; }
.tb-user > i.bi-chevron-down { font-size: 11px; color: var(--text-3); }

#buscaGlobalWrapMobile input {
  background: var(--surface-2) !important; border-color: var(--border) !important; color: var(--text-1);
}

@media (max-width: 1199.98px) {
  .tb-user-rest { display: none; }
}
@media (max-width: 991.98px) {
  .tb-nova-os-label { display: none; }
  .tb-nova-os { padding: 7px 10px; }
}
@media (max-width: 767.98px) {
  #sidebar { transform: translateX(-100%); }
  #sidebar.show { transform: translateX(0); }
  #main { margin-left: 0; }
  #topbar { padding: 9px 14px; gap: 10px; }
  .tb-busca { display: none; }
  .tb-busca-mobile-btn { display: flex; }
  .tb-meta { display: none; }
  .tb-titulo { max-width: 46vw; }
}
#sidebarOverlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999;
}
#sidebarOverlay.show { display: block; }
.dropdown-menu { z-index: 1035; }
.busca-global-dropdown {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0; max-height: 420px; overflow-y: auto;
  background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,.15); z-index: 1035;
}
.busca-global-cat {
  padding: .5rem .9rem .3rem; font-size: .7rem; font-weight: 700; letter-spacing: .04em;
  text-transform: uppercase; color: #94a3b8;
}
.busca-global-item {
  display: flex; align-items: center; gap: .7rem; padding: .55rem .9rem; text-decoration: none;
  color: inherit; border-top: 1px solid #f1f5f9;
}
.busca-global-item:hover, .busca-global-item.ativo { background: #f8fafc; }
.busca-global-item .bg-icone {
  width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0; color: #4f46e5;
}
.busca-global-item .titulo { font-size: .85rem; font-weight: 600; color: #0f172a; line-height: 1.3; }
.busca-global-item .subtitulo { font-size: .75rem; color: #64748b; line-height: 1.3; }
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
$chatHabilitado = 1; $chatSom = 1; $chatInsistente = 1; $mostrarPrevisao = 1; $mostrarCalculadora = 1; $mostrarMentor = 1;
try {
    $stmtCh = \App\Core\DB::pdo()->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ? AND chave IN ('chat_habilitado','chat_som','chat_insistente','mostrar_previsao','mostrar_calculadora','mostrar_mentor')");
    $stmtCh->execute([\App\Core\Auth::empresaId()]);
    foreach ($stmtCh->fetchAll(\PDO::FETCH_KEY_PAIR) as $k => $v) {
        $iv = ($v === '' || $v === null) ? 1 : (int) $v;
        if ($k === 'chat_habilitado') $chatHabilitado = $iv;
        elseif ($k === 'chat_som') $chatSom = $iv;
        elseif ($k === 'chat_insistente') $chatInsistente = $iv;
        elseif ($k === 'mostrar_previsao') $mostrarPrevisao = $iv;
        elseif ($k === 'mostrar_calculadora') $mostrarCalculadora = $iv;
        elseif ($k === 'mostrar_mentor') $mostrarMentor = $iv;
    }
} catch (\Throwable $e) { $chatHabilitado = 1; $chatSom = 1; $chatInsistente = 1; $mostrarPrevisao = 1; $mostrarCalculadora = 1; $mostrarMentor = 1; }
$_SESSION['chat_habilitado'] = $chatHabilitado; // disponível para as views (ex.: os/show)
$_SESSION['mostrar_previsao'] = $mostrarPrevisao; // controla a exibição da "Previsão de entrega"
?>
<body class="<?= $textoMaiusculo ? 'ui-uppercase' : '' ?>">

<div id="fixaosOfflineBanner" style="display:none;position:fixed;top:0;left:0;right:0;z-index:2000;background:#dc2626;color:#fff;padding:8px 16px;align-items:center;justify-content:center;gap:8px;font-size:.85rem;font-weight:600">
  <i class="bi bi-wifi-off"></i> Sem conexão com a internet — algumas ações podem não funcionar até a conexão voltar.
</div>
<div id="fixaosSyncBanner" style="display:none;position:fixed;bottom:16px;left:16px;z-index:2000;background:#1e3a5f;color:#fff;padding:10px 16px;align-items:center;gap:8px;font-size:.82rem;font-weight:600;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.2)">
  <span class="spinner-border spinner-border-sm"></span> <span class="msg">Sincronizando...</span>
</div>

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
  <div class="brand">
    <?php if ($logoEmpresa): ?>
      <img src="<?= url('/uploads/' . e($logoEmpresa)) ?>"
           alt="Logo"
           style="width:100%;max-height:48px;object-fit:contain;filter:brightness(1.1)">
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
  $grpDivulgacao  = false;
  $grpConfig      = false;

  // Dados de OS para os badges (Ordens de Serviço, Atendimento, Em Garantia)
  if (\App\Core\Auth::check()):
    $eid = \App\Core\Auth::empresaId();
    // Centralizado: a regra de contagem vem SÓ do Model (mesma fonte da tela /os — sem divergir).
    $osModel = new \App\Models\OrdemServico();
    $totalGarSidebar  = $osModel->totalEmGarantia();
    $totalAtrasadas   = $osModel->totalAtrasadas();
    $statusSidebar    = $osModel->totaisPorStatus();
    $totalAberto   = array_sum(array_column(
      array_filter($statusSidebar, fn($s) => in_array($s['tipo'], ['aberta','em_andamento','aguardando'])),
      'total'
    ));
  endif;

  // Status de conexão do WhatsApp da empresa, cacheado na sessão por 2 min —
  // checar de verdade custa uma chamada HTTP à API, não pode rodar a cada
  // carregamento de página. Se a integração não está configurada, statusEmpresa()
  // devolve 'unknown' sem sequer tentar a chamada.
  $waConectado = false;
  if (\App\Core\Auth::check() && \App\Core\Auth::can('config')) {
    if (!isset($_SESSION['wa_status_at']) || (time() - $_SESSION['wa_status_at']) > 120) {
      try { $_SESSION['wa_status_on'] = \App\Services\WhatsAppService::statusEmpresa(\App\Core\Auth::empresaId()) === 'open'; }
      catch (\Throwable $e) { $_SESSION['wa_status_on'] = false; }
      $_SESSION['wa_status_at'] = time();
    }
    $waConectado = (bool) ($_SESSION['wa_status_on'] ?? false);
  }
  ?>

  <?php if (\App\Core\Auth::soDiretorio()): ?>
  <!-- Menu mínimo: conta só do diretório -->
  <div class="pt-2 pb-1">
    <div class="sb-label">Meu diretório</div>
    <a class="nav-link <?= navAtivo($uri,'/empresa/perfil-publico') ?>" href="<?= url('/empresa/perfil-publico') ?>">
      <i class="bi bi-shop-window"></i> <span class="sb-txt">Editar Diretório</span>
    </a>
    <a class="nav-link" href="<?= url('/assistencias') ?>" target="_blank">
      <i class="bi bi-globe2"></i> <span class="sb-txt">Ver o diretório</span> <i class="bi bi-box-arrow-up-right sb-ext-ic"></i>
    </a>
  </div>
  <?php else: ?>

  <!-- ── Zona de ação ── -->
  <div class="pt-1 pb-1">
    <?php if (\App\Core\Auth::can('os')): ?>
    <a href="<?= url('/os/nova') ?>" class="sb-primary">
      <span class="lbl"><i class="bi bi-plus-circle-fill"></i> Nova OS</span>
      <span class="sb-kbd">F2</span>
    </a>
    <?php endif; ?>
    <?php if (\App\Core\Auth::can('pdv') || \App\Core\Auth::can('config')): ?>
    <div class="sb-tonal-row <?= (\App\Core\Auth::can('pdv') && \App\Core\Auth::can('config')) ? '' : 'single' ?>">
      <?php if (\App\Core\Auth::can('pdv')): ?>
      <a href="<?= url('/pdv') ?>" class="sb-tonal accent"><i class="bi bi-cash-stack"></i>Caixa</a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('config')): ?>
      <a href="<?= url('/empresa/whatsapp') ?>" class="sb-tonal success">
        <span class="sb-status-dot <?= $waConectado ? 'on' : '' ?>"></span>
        <i class="bi bi-whatsapp"></i>WhatsApp
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="sb-divider"></div>

  <div id="sbAccordion">

    <a class="nav-link <?= ($uri === '/' || str_starts_with($uri,'/dashboard')) ? 'active' : '' ?>" href="<?= url('/dashboard') ?>">
      <i class="bi bi-speedometer2"></i> <span class="sb-txt"><?= __('menu_dashboard') ?></span>
    </a>
    <?php if (\App\Core\Auth::can('os')): ?>
    <a class="nav-link <?= str_starts_with($uri,'/os') && !isset($_GET['status_id']) && !isset($_GET['em_garantia']) ? 'active' : '' ?>" href="<?= url('/os') ?>">
      <i class="bi bi-clipboard2-pulse"></i> <span class="sb-txt">Ordens de Serviço</span>
      <?php if (!empty($totalAtrasadas)): ?><span class="sb-badge"><?= $totalAtrasadas ?></span><?php endif; ?>
    </a>
    <?php endif; ?>
    <?php if (\App\Core\Auth::can('agenda')): ?>
    <a class="nav-link <?= navAtivo($uri,'/agenda') ?>" href="<?= url('/agenda') ?>">
      <i class="bi bi-calendar3"></i> <span class="sb-txt">Agenda</span>
    </a>
    <?php endif; ?>

    <div class="sb-label">Operação</div>
    <?php if (\App\Core\Auth::can('clientes')): ?>
    <a class="nav-link <?= navAtivo($uri,'/clientes') ?>" href="<?= url('/clientes') ?>">
      <i class="bi bi-people"></i> <span class="sb-txt">Clientes</span>
    </a>
    <?php endif; ?>

    <!-- ── Atendimento (Em Garantia + filtro por status) ── -->
    <?php if (\App\Core\Auth::can('os')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbAtendimento"
              aria-expanded="<?= $grpAtendimento ? 'true' : 'false' ?>">
        <i class="bi bi-clipboard2-pulse"></i> <span class="sb-txt">Atendimento</span>
        <?php if (!empty($totalAberto)): ?><span class="sb-badge neutral"><?= $totalAberto ?></span><?php endif; ?>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbAtendimento" class="collapse sb-body <?= $grpAtendimento ? 'show' : '' ?>">
        <?php if (!empty($totalGarSidebar)): ?>
        <a class="nav-link <?= isset($_GET['em_garantia']) ? 'active' : '' ?>" href="<?= url('/os') ?>?em_garantia=1">
          <i class="bi bi-shield-check"></i> <span class="sb-txt">Em Garantia</span>
          <span class="sb-badge"><?= $totalGarSidebar ?></span>
        </a>
        <?php endif; ?>
        <?php if (!empty($statusSidebar)): ?>
        <div class="sb-group">
          <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#osStatusFilter" aria-expanded="false">
            <i class="bi bi-funnel"></i> <span class="sb-txt">Filtrar por status</span>
            <?php if ($totalAberto): ?><span class="sb-badge neutral"><?= $totalAberto ?></span><?php endif; ?>
            <i class="bi bi-chevron-down sb-chevron"></i>
          </button>
          <div class="collapse sb-body" id="osStatusFilter">
            <?php $statusIdAtivo = $_GET['status_id'] ?? null; ?>
            <?php foreach ($statusSidebar as $s): ?>
            <a href="<?= url('/os') ?>?status_id=<?= $s['id'] ?>" class="nav-link <?= $statusIdAtivo == $s['id'] ? 'active' : '' ?>">
              <span class="sb-dot" style="background:<?= e($s['cor']) ?>"></span>
              <span class="sb-txt"><?= e($s['nome']) ?></span>
              <?php if ($s['total'] > 0): ?><span class="sb-badge neutral"><?= $s['total'] ?></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
            <a href="<?= url('/os') ?>" class="nav-link">
              <i class="bi bi-x-circle"></i> <span class="sb-txt">Limpar filtro</span>
            </a>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Produtos e estoque ── -->
    <?php if (\App\Core\Auth::can('estoque')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbEstoque"
              aria-expanded="<?= $grpEstoque ? 'true' : 'false' ?>">
        <i class="bi bi-box-seam"></i> <span class="sb-txt">Produtos e estoque</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbEstoque" class="collapse sb-body <?= $grpEstoque ? 'show' : '' ?>">
        <a class="nav-link <?= (str_starts_with($uri,'/produtos') && !str_starts_with($uri,'/produtos/categorias')) ? 'active' : '' ?>" href="<?= url('/produtos') ?>"><i class="bi bi-box-seam"></i> <span class="sb-txt">Produtos</span></a>
        <a class="nav-link <?= navAtivo($uri,'/produtos/categorias') ?>" href="<?= url('/produtos/categorias') ?>"><i class="bi bi-tags"></i> <span class="sb-txt">Categorias</span></a>
        <a class="nav-link <?= navAtivo($uri,'/fornecedores') ?>" href="<?= url('/fornecedores') ?>"><i class="bi bi-truck"></i> <span class="sb-txt">Fornecedores</span></a>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── CRM ── -->
    <?php if (\App\Core\Auth::can('crm')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbCrm"
              aria-expanded="<?= $grpCrm ? 'true' : 'false' ?>">
        <i class="bi bi-funnel"></i> <span class="sb-txt">CRM</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbCrm" class="collapse sb-body <?= $grpCrm ? 'show' : '' ?>">
        <a class="nav-link <?= navAtivo($uri,'/crm') ?>" href="<?= url('/crm') ?>"><i class="bi bi-funnel"></i> <span class="sb-txt">Pipeline</span></a>
      </div>
    </div>
    <?php endif; ?>

    <div class="sb-label">Gestão</div>

    <!-- ── Financeiro ── -->
    <?php if (\App\Core\Auth::can('financeiro')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbFinanceiro"
              aria-expanded="<?= $grpFinanceiro ? 'true' : 'false' ?>">
        <i class="bi bi-currency-dollar"></i> <span class="sb-txt">Financeiro</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbFinanceiro" class="collapse sb-body <?= $grpFinanceiro ? 'show' : '' ?>">
        <a class="nav-link <?= str_starts_with($uri,'/financeiro') && !str_starts_with($uri,'/financeiro/categorias') ? 'active' : '' ?>" href="<?= url('/financeiro') ?>"><i class="bi bi-currency-dollar"></i> <span class="sb-txt">Fluxo de Caixa</span></a>
        <a class="nav-link <?= navAtivo($uri,'/financeiro/categorias') ?>" href="<?= url('/financeiro/categorias') ?>"><i class="bi bi-tags"></i> <span class="sb-txt">Categorias</span></a>
        <a class="nav-link <?= navAtivo($uri,'/comissoes') ?>" href="<?= url('/comissoes') ?>"><i class="bi bi-cash-coin"></i> <span class="sb-txt">Comissões</span></a>
      </div>
    </div>
    <a class="nav-link <?= navAtivo($uri,'/relatorios') ?>" href="<?= url('/relatorios') ?>"><i class="bi bi-bar-chart-line"></i> <span class="sb-txt">Relatórios</span></a>
    <?php endif; ?>

    <!-- ── Marketplace ── -->
    <?php if (\App\Core\Auth::can('marketplace')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbMarketplace"
              aria-expanded="<?= $grpMarketplace ? 'true' : 'false' ?>">
        <i class="bi bi-shop"></i> <span class="sb-txt">Marketplace</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbMarketplace" class="collapse sb-body <?= $grpMarketplace ? 'show' : '' ?>">
        <a class="nav-link <?= navAtivo($uri,'/marketplace') && !str_starts_with($uri,'/marketplace/meus-anuncios') && !str_starts_with($uri,'/marketplace/categorias') ? 'active' : '' ?>" href="<?= url('/marketplace') ?>"><i class="bi bi-shop"></i> <span class="sb-txt">Vitrine</span></a>
        <a class="nav-link <?= navAtivo($uri,'/marketplace/meus-anuncios') ?>" href="<?= url('/marketplace/meus-anuncios') ?>"><i class="bi bi-bag-check"></i> <span class="sb-txt">Meus Anúncios</span></a>
        <a class="nav-link <?= navAtivo($uri,'/marketplace/categorias') ?>" href="<?= url('/marketplace/categorias') ?>"><i class="bi bi-tags"></i> <span class="sb-txt">Categorias</span></a>
        <a class="nav-link <?= navAtivo($uri,'/marketplace/pedidos') ?>" href="<?= url('/marketplace/pedidos') ?>"><i class="bi bi-megaphone"></i> <span class="sb-txt">Pedidos de Peças</span></a>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Divulgação ── -->
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbDivulgacao"
              aria-expanded="<?= $grpDivulgacao ? 'true' : 'false' ?>">
        <i class="bi bi-megaphone"></i> <span class="sb-txt">Divulgação</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbDivulgacao" class="collapse sb-body <?= $grpDivulgacao ? 'show' : '' ?>">
        <?php if (\App\Core\Auth::can('config')): ?>
        <a class="nav-link <?= navAtivo($uri,'/empresa/perfil-publico') ?>" href="<?= url('/empresa/perfil-publico') ?>"><i class="bi bi-shop-window"></i> <span class="sb-txt">Editar Diretório</span></a>
        <a class="nav-link <?= navAtivo($uri,'/empresa/anuncios-diretorio') ?>" href="<?= url('/empresa/publicidade') ?>"><i class="bi bi-megaphone"></i> <span class="sb-txt"><?= __('menu_publicidade') ?></span></a>
        <?php endif; ?>
        <a class="nav-link" href="<?= url('/forum') ?>" target="_blank"><i class="bi bi-chat-dots"></i> <span class="sb-txt">Fórum</span> <i class="bi bi-box-arrow-up-right sb-ext-ic"></i></a>
        <a class="nav-link" href="<?= url('/pecas') ?>" target="_blank"><i class="bi bi-globe2"></i> <span class="sb-txt">Marketplace público</span> <i class="bi bi-box-arrow-up-right sb-ext-ic"></i></a>
      </div>
    </div>

    <div class="sb-label">Sistema</div>

    <!-- ── Configurações ── -->
    <?php if (\App\Core\Auth::can('config') || \App\Core\Auth::can('usuarios')): ?>
    <div class="sb-group">
      <button class="sb-group-btn" data-bs-toggle="collapse" data-bs-target="#sbConfig"
              aria-expanded="<?= $grpConfig ? 'true' : 'false' ?>">
        <i class="bi bi-gear"></i> <span class="sb-txt">Configurações</span>
        <i class="bi bi-chevron-down sb-chevron"></i>
      </button>
      <div id="sbConfig" class="collapse sb-body <?= $grpConfig ? 'show' : '' ?>">
        <?php if (\App\Core\Auth::can('config')): ?>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'tecnicos' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=tecnicos"><i class="bi bi-tools"></i> <span class="sb-txt">Técnicos</span></a>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'status' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=status"><i class="bi bi-tags"></i> <span class="sb-txt">Status de OS</span></a>
        <?php if (\App\Core\Auth::isAdmin()): ?>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'chat' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=chat"><i class="bi bi-chat-dots"></i> <span class="sb-txt">Chat da equipe</span></a>
        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalFerramentas"><i class="bi bi-sliders"></i> <span class="sb-txt">Calculadora e Mentor</span></a>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'previsao' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=previsao"><i class="bi bi-clock-history"></i> <span class="sb-txt">Previsão de entrega</span></a>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (\App\Core\Auth::can('usuarios')): ?>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'usuarios' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=usuarios"><i class="bi bi-person-gear"></i> <span class="sb-txt">Usuários</span></a>
        <?php endif; ?>
        <?php if (\App\Core\Auth::can('config')): ?>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'exibicao' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=exibicao"><i class="bi bi-fonts"></i> <span class="sb-txt">Exibição do texto</span></a>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'empresa' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=empresa"><i class="bi bi-building"></i> <span class="sb-txt">Empresa</span></a>
        <a class="nav-link <?= navAtivo($uri,'/configuracoes') && ($_GET['aba'] ?? '') === 'imagens' ? 'active' : '' ?>" href="<?= url('/configuracoes') ?>?aba=imagens"><i class="bi bi-image"></i> <span class="sb-txt">Editor de Imagens</span></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <a class="nav-link <?= navAtivo($uri,'/manual') ?>" href="<?= url('/manual') ?>">
      <i class="bi bi-book"></i> <span class="sb-txt">Ajuda e manual</span>
    </a>

    <!-- ── Rodapé ── -->
    <div class="sb-footer">
      <a class="nav-link <?= navAtivo($uri,'/planos') ?>" href="<?= url('/planos') ?>">
        <i class="bi bi-credit-card"></i> <span class="sb-txt">Planos</span>
      </a>
    </div>

  </div><!-- /sbAccordion -->
  <?php endif; ?>
  </div><!-- /sb-scroll -->
</nav>

<!-- Main -->
<div id="main">
  <?php if (\App\Core\Auth::check() && (int) (\App\Core\Auth::user()['email_verificado'] ?? 1) === 0): ?>
  <div style="background:#fef3c7;color:#92400e;padding:.6rem 1.25rem;font-size:.85rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid #fde68a">
    <span><i class="bi bi-envelope-exclamation-fill"></i> Confirme seu e-mail (<?= e(\App\Core\Auth::user()['email']) ?>) pra garantir o acesso à sua conta.</span>
    <form method="POST" action="<?= url('/verificar-email/reenviar') ?>" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-sm btn-outline-dark py-0 px-2" style="font-size:.78rem">Reenviar e-mail</button>
    </form>
  </div>
  <?php endif; ?>
  <!-- Topbar -->
  <?php
    $tbNome    = \App\Core\Auth::user()['nome'] ?? '';
    $tbPartes  = preg_split('/\s+/', trim($tbNome));
    $tbPrimeiro = $tbPartes[0] ?? '';
    $tbResto    = trim(substr($tbNome, strlen($tbPrimeiro)));
  ?>
  <div id="topbar">
    <button class="btn btn-sm btn-outline-secondary d-md-none flex-shrink-0" onclick="abrirSidebar()">
      <i class="bi bi-list"></i>
    </button>

    <!-- Bloco 1: título da página + data/hora -->
    <div class="tb-title">
      <h6 class="tb-titulo"><?= e($titulo ?? '') ?></h6>
      <div class="tb-meta" id="relogio"></div>
    </div>

    <!-- Bloco 2: busca global (desktop) -->
    <div class="tb-busca" id="buscaGlobalWrap">
      <i class="bi bi-search"></i>
      <input type="text" id="buscaGlobalInput" placeholder="Buscar OS, cliente, produto" autocomplete="off">
      <span class="tb-busca-kbd">/</span>
      <div id="buscaGlobalResultados" class="busca-global-dropdown d-none"></div>
    </div>
    <!-- Busca global (mobile): ícone que abre a busca em linha própria -->
    <button class="tb-busca-mobile-btn" id="btnBuscaGlobalMobile" title="Buscar">
      <i class="bi bi-search"></i>
    </button>

    <!-- Bloco 3: ações -->
    <div class="tb-actions">
      <?php if (\App\Core\Auth::can('os')): ?>
      <a href="<?= url('/os/nova') ?>" class="tb-nova-os" title="Nova OS">
        <i class="bi bi-plus-lg"></i><span class="tb-nova-os-label">Nova OS</span>
      </a>
      <?php endif; ?>

      <!-- Alternância rápida de tema (a mesma preferência do menu do avatar) -->
      <button type="button" class="tb-theme-toggle" id="btnThemeToggle" title="Alternar tema">
        <i class="bi bi-moon-stars" id="iconThemeToggle"></i>
      </button>

      <!-- Sino de chat da equipe (só quando o chat está habilitado para a empresa) -->
      <?php if ($chatHabilitado): ?>
      <div class="dropdown" id="chatDropdown">
        <button class="tb-bell" data-bs-toggle="dropdown" data-bs-auto-close="outside" onclick="carregarChat()" title="Conversas das OS">
          <i class="bi bi-chat-dots"></i>
          <span id="chatBadge" class="tb-bell-badge" style="display:none">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0" style="width:340px;max-height:480px;overflow:hidden;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.15)">
          <div style="background:#1e3a5f;color:#fff;padding:.9rem 1.2rem;display:flex;align-items:center;justify-content:space-between;border-radius:14px 14px 0 0">
            <span class="fw-bold"><i class="bi bi-chat-dots me-1"></i>Conversas das OS</span>
            <button onclick="marcarChatLido()" class="btn btn-sm" style="background:rgba(34,197,94,.3);color:#fff;border:none;padding:.35rem .55rem" title="Marcar tudo como lido">
              <i class="bi bi-check2-all"></i>
            </button>
          </div>
          <div id="chatLista" style="overflow-y:auto;max-height:400px">
            <div class="text-center py-4 text-muted small"><i class="bi bi-chat-square-dots d-block fs-3 mb-2 opacity-25"></i>Carregando...</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Sino de notificações -->
      <div class="dropdown" id="notifDropdown">
        <button class="tb-bell" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-display="static"
                onclick="carregarNotifs();carregarPendencias();" title="Notificações">
          <i class="bi bi-bell"></i>
          <span id="notifBadge" class="tb-bell-badge" style="display:none">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0 tb-notif-panel">
          <div class="tb-notif-header">
            <div class="tb-notif-header-top">
              <span class="tb-notif-header-titulo">Notificações</span>
              <button type="button" onclick="fecharNotifDropdown()" class="tb-notif-close" title="Fechar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="tb-notif-header-acoes">
              <button type="button" onclick="marcarTodasLidas()" class="tb-notif-link">Marcar todas como lidas</button>
              <button type="button" onclick="limparTodasNotifs()" class="tb-notif-link tb-notif-link-muted">Excluir notificações</button>
              <a href="<?= url('/configuracoes') ?>" class="tb-notif-gear" title="Configurações"><i class="bi bi-gear"></i></a>
            </div>
          </div>
          <div class="tb-notif-body">
            <div id="secPendencias" style="display:none">
              <div class="tb-notif-sec-label">Precisa de ação</div>
              <div id="listaPendencias"></div>
            </div>
            <div id="secRecentes" style="display:none">
              <div class="tb-notif-sec-label">Recentes</div>
              <div id="listaRecentes"></div>
            </div>
            <div id="notifVazioGeral" class="tb-notif-vazio">Carregando...</div>
          </div>
          <div class="tb-notif-footer">
            <a href="<?= url('/notificacoes') ?>">Ver todas as notificações</a>
          </div>
        </div>
      </div>

      <div class="dropdown">
        <button class="tb-user" data-bs-toggle="dropdown">
          <span class="tb-avatar"><?= avatar_iniciais(\App\Core\Auth::user()['nome'] ?? 'U') ?></span>
          <span class="tb-user-name"><?= e($tbPrimeiro) ?><span class="tb-user-rest"><?= $tbResto !== '' ? ' ' . e($tbResto) : '' ?></span></span>
          <i class="bi bi-chevron-down"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= url('/notificacoes') ?>"><i class="bi bi-bell me-2"></i>Notificações</a></li>
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalFeedback"><i class="bi bi-chat-heart me-2"></i>Enviar feedback</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><h6 class="dropdown-header">Aparência</h6></li>
          <li class="px-3 pb-2">
            <div class="btn-group w-100" role="group" aria-label="Aparência" id="fxThemeSwitch">
              <input type="radio" class="btn-check" name="fxTema" id="fxTemaLight" autocomplete="off" value="light">
              <label class="btn btn-outline-secondary btn-sm" for="fxTemaLight"><i class="bi bi-sun me-1"></i>Claro</label>

              <input type="radio" class="btn-check" name="fxTema" id="fxTemaDark" autocomplete="off" value="dark">
              <label class="btn btn-outline-secondary btn-sm" for="fxTemaDark"><i class="bi bi-moon-stars me-1"></i>Escuro</label>

              <input type="radio" class="btn-check" name="fxTema" id="fxTemaAuto" autocomplete="off" value="auto">
              <label class="btn btn-outline-secondary btn-sm" for="fxTemaAuto"><i class="bi bi-display me-1"></i>Automático</label>
            </div>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Busca global (mobile): linha própria, some por padrão -->
  <div class="position-relative d-none" id="buscaGlobalWrapMobile" style="width:100%;padding:.5rem 14px;background:var(--surface-1);border-bottom:.5px solid var(--border)">
    <i class="bi bi-search" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:.85rem;pointer-events:none"></i>
    <input type="text" id="buscaGlobalInputMobile" class="form-control" placeholder="Buscar OS, cliente, produto..."
           autocomplete="off" style="height:44px;padding-right:2.2rem;border-radius:22px">
    <div id="buscaGlobalResultadosMobile" class="busca-global-dropdown d-none"></div>
  </div>

  <?php if ((\App\Core\Auth::user()['email'] ?? '') === 'demo@fixaos.com.br'): ?>
  <div style="background:linear-gradient(90deg,#4f46e5,#7c3aed);color:#fff;padding:.55rem 1.2rem;display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap;font-size:.88rem;text-align:center">
    <span><i class="bi bi-controller me-1"></i><strong>Você está no modo demonstração</strong> — explore à vontade! Os dados são de exemplo e reiniciam sozinhos.</span>
    <a href="<?= url('/demo/sair-para-cadastro') ?>" style="background:#fff;color:#4f46e5;font-weight:800;text-decoration:none;padding:.35rem 1rem;border-radius:20px;white-space:nowrap"><i class="bi bi-rocket-takeoff me-1"></i>Gostei! Criar minha conta grátis</a>
  </div>
  <?php endif; ?>

  <?php
    // Aviso de vencimento de assinatura (trial_ate/licenca_ate) quando faltarem 3 dias ou menos.
    $diasAssinatura = null;
    $planosSugeridos = [];
    if (\App\Core\Auth::check()) {
      $stmtAv = \App\Core\DB::pdo()->prepare("SELECT trial_ate, licenca_ate FROM empresas WHERE id = ? LIMIT 1");
      $stmtAv->execute([\App\Core\Auth::empresaId()]);
      if ($empAv = $stmtAv->fetch()) {
        $d = licenca_dias_restantes($empAv);
        if ($d !== null && $d <= 3) {
          $diasAssinatura = $d;
          $planosSugeridos = (require BASE_PATH . '/config/planos.php')['planos'];
        }
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
    <a href="<?= url('/planos') ?>" target="_top" style="background:#fff;color:#111827;font-weight:800;text-decoration:none;padding:.35rem 1rem;border-radius:20px;white-space:nowrap"><i class="bi bi-rocket-takeoff me-1"></i>Ver planos</a>
  </div>

  <!-- Modal de aviso de vencimento -->
  <div class="modal fade" id="modalVencimentoPlano" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header" style="background:<?= $diasAssinatura <= 0 ? '#dc2626' : '#f59e0b' ?>;color:#fff;border:none">
          <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= $diasAssinatura <= 0 ? 'Sua assinatura venceu' : 'Sua assinatura vence em ' . $diasAssinatura . ' dia' . ($diasAssinatura === 1 ? '' : 's') ?>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted mb-3">Escolha um plano para continuar usando o FixaOS sem interrupções:</p>
          <div class="row g-3">
            <?php foreach ($planosSugeridos as $p): $precoMes = (int) $p['preco_mensal']; ?>
            <div class="col-md-4">
              <div class="border rounded-3 p-3 h-100 d-flex flex-column <?= !empty($p['destaque']) ? 'border-primary border-2' : '' ?>">
                <?php if (!empty($p['destaque'])): ?><span class="badge bg-primary align-self-start mb-2">Mais popular</span><?php endif; ?>
                <div class="fw-bold"><?= e($p['nome']) ?></div>
                <div class="mb-2"><span class="fs-4 fw-bold">R$ <?= number_format($precoMes / 100, 2, ',', '.') ?></span><span class="text-muted small">/mês</span></div>
                <a href="<?= url('/assinar/' . $p['codigo'] . '/mensal') ?>" target="_top" class="btn <?= !empty($p['destaque']) ? 'btn-primary' : 'btn-outline-primary' ?> fw-bold w-100 mt-auto">
                  <i class="bi bi-credit-card me-1"></i>Assinar agora
                </a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="text-center mt-3">
            <a href="<?= url('/planos') ?>" target="_top" class="small text-muted">Ver todos os planos e ciclos de pagamento</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
  (function(){
    var dias = <?= json_encode($diasAssinatura) ?>;
    var chave = 'fixaos_modal_vencimento_' + dias + '_' + new Date().toDateString();
    if (!sessionStorage.getItem(chave)) {
      sessionStorage.setItem(chave, '1');
      window.addEventListener('load', function(){
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVencimentoPlano')).show();
      });
    }
  })();
  </script>
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
if (window.FixaosOffline) {
  window.FixaosOffline.registrarServiceWorker('<?= url('/sw.js') ?>');
  window.FixaosOffline.iniciarBannerConexao();

  (function () {
    var SYNC_URL = '<?= url('/os/sincronizar-rascunho') ?>';
    var CSRF = '<?= csrf_token() ?>';

    function toastSync(msg, erro) {
      var t = document.createElement('div');
      t.className = 'toast align-items-center text-white ' + (erro ? 'bg-danger' : 'bg-success') + ' border-0 show position-fixed';
      t.style.cssText = 'bottom:20px;right:20px;z-index:2100;min-width:280px';
      t.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Fechar"></button></div>';
      t.querySelector('.btn-close').addEventListener('click', function () { t.remove(); });
      document.body.appendChild(t);
      setTimeout(function () { t.remove(); }, 6000);
    }

    function tentarSincronizarRascunhos() {
      if (!navigator.onLine) return;
      window.FixaosOffline.listarRascunhos().then(function (rascunhos) {
        var pendentes = rascunhos.filter(function (r) { return r.status_sync !== 'erro'; });
        if (!pendentes.length) return;
        var banner = document.getElementById('fixaosSyncBanner');
        if (banner) { banner.style.display = 'flex'; banner.querySelector('.msg').textContent = 'Sincronizando ' + pendentes.length + ' OS criada(s) offline...'; }
        window.FixaosOffline.sincronizarRascunhos(SYNC_URL, CSRF, function (res) {
          if (res.sucesso) toastSync('OS nº ' + res.numero + ' (criada offline) sincronizada com sucesso!');
        }).then(function (resumo) {
          if (banner) banner.style.display = 'none';
          if (resumo.erro > 0) toastSync(resumo.erro + ' rascunho(s) não puderam ser sincronizados — confira em "OS offline".', true);
        });
      });
    }

    window.addEventListener('online', tentarSincronizarRascunhos);
    tentarSincronizarRascunhos();
  })();
}
</script>
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
const diasLower  = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
const mesesLower = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
function atualizarRelogio() {
  const n = new Date();
  const h = String(n.getHours()).padStart(2,'0');
  const m = String(n.getMinutes()).padStart(2,'0');
  const el = document.getElementById('relogio');
  if (el) el.textContent = `${diasLower[n.getDay()]}, ${n.getDate()} de ${mesesLower[n.getMonth()]} · ${h}:${m}`;
}
atualizarRelogio();
setInterval(atualizarRelogio, 1000);

// ── Notificações ──────────────────────────────────────────────────────
const NOTIF_URL     = '<?= url('/api/notificacoes') ?>';
const NOTIF_LER_URL = '<?= url('/notificacoes/') ?>';
const NOTIF_TODAS   = '<?= url('/notificacoes/todas-lidas') ?>';
const CSRF_TOKEN    = '<?= csrf_token() ?>';

// ── Seletor de tema (menu do avatar + botão rápido do topbar) ────────────
(function () {
  var atual = window.FxTheme ? window.FxTheme.current() : 'auto';
  document.querySelectorAll('input[name="fxTema"]').forEach(function (r) {
    r.checked = (r.value === atual);
    r.addEventListener('change', function () {
      if (this.checked && window.FxTheme) {
        window.FxTheme.set(this.value, CSRF_TOKEN, '<?= url('/preferencias/tema') ?>');
      }
    });
  });

  // Botão de alternância rápida no topbar — mesma preferência do menu do
  // avatar, só que visível de cara (sun/moon), sem precisar abrir o menu.
  var btnToggle = document.getElementById('btnThemeToggle');
  var iconToggle = document.getElementById('iconThemeToggle');
  function atualizarIconeToggle() {
    var escuro = document.documentElement.dataset.theme === 'dark';
    if (iconToggle) iconToggle.className = escuro ? 'bi bi-sun' : 'bi bi-moon-stars';
    if (btnToggle) btnToggle.title = escuro ? 'Mudar para tema claro' : 'Mudar para tema escuro';
  }
  atualizarIconeToggle();
  window.addEventListener('fx-theme-change', atualizarIconeToggle);
  if (btnToggle) {
    btnToggle.addEventListener('click', function () {
      var novo = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      if (window.FxTheme) window.FxTheme.set(novo, CSRF_TOKEN, '<?= url('/preferencias/tema') ?>');
      document.querySelectorAll('input[name="fxTema"]').forEach(function (r) { r.checked = (r.value === novo); });
    });
  }
})();

const coresNotif = {
  danger:'#ef4444', warning:'#f59e0b', success:'#22c55e', info:'#3b82f6', primary:'#6366f1'
};
const PENDENCIAS_URL = '<?= url('/api/notificacoes/pendencias') ?>';
const ICONE_GRUPO = { atrasadas: 'bi-exclamation-triangle-fill', aguardando: 'bi-hourglass-split', estoque: 'bi-box-seam' };

// Badge mostra o número real, sem teto de "99+" — um número estourado não
// informa nada (99+ pode ser 100 ou 4000, o usuário ignora do mesmo jeito).
function atualizarBadgeNotif(total) {
  const b = document.getElementById('notifBadge');
  if (total > 0) { b.textContent = total; b.style.display = 'flex'; }
  else { b.style.display = 'none'; }
}

async function carregarNotifs() {
  try {
    const r = await fetch(NOTIF_URL);
    const d = await r.json();
    atualizarBadgeNotif(d.total || 0);
    renderNotifs(d.lista);
  } catch(e) {}
}

// "Precisa de ação": pendências ao vivo (não vem da tabela notificacoes),
// só carregada quando o dropdown abre — não entra no polling de 2 em 2 min.
async function carregarPendencias() {
  try {
    const r = await fetch(PENDENCIAS_URL);
    const d = await r.json();
    renderPendencias(d.grupos || []);
  } catch (e) {}
}

function renderPendencias(grupos) {
  const sec = document.getElementById('secPendencias');
  const el  = document.getElementById('listaPendencias');
  sec.dataset.tem = grupos.length ? '1' : '0';
  notifCarregado.pend = true;
  el.innerHTML = grupos.map(function (g) {
    const icone = ICONE_GRUPO[g.chave] || 'bi-exclamation-triangle-fill';
    return '<a href="' + g.url + '" class="tb-notif-grupo ' + g.estilo + '">'
      + '<i class="bi ' + icone + ' tb-notif-grupo-ic"></i>'
      + '<span class="tb-notif-grupo-txt">'
      + '<span class="tb-notif-grupo-titulo d-block">' + escC(g.titulo) + '</span>'
      + (g.subtitulo ? '<span class="tb-notif-grupo-sub d-block">' + escC(g.subtitulo) + '</span>' : '')
      + '</span>'
      + '<i class="bi bi-chevron-right tb-notif-grupo-chev"></i>'
      + '</a>';
  }).join('');
  atualizarVisibilidadeNotif();
}

// "Recentes": eventos individuais (tabela notificacoes), já sem os tipos que
// aparecem resumidos em "Precisa de ação" — filtro aplicado no backend.
function renderNotifs(lista) {
  const sec   = document.getElementById('secRecentes');
  const el    = document.getElementById('listaRecentes');
  const itens = lista || [];
  sec.dataset.tem = itens.length ? '1' : '0';
  notifCarregado.rec = true;
  el.innerHTML = itens.map(function (n) {
    const cor   = coresNotif[n.cor] || '#6366f1';
    const lida  = n.lida == 1;
    const href  = n.link || '#';
    const click = n.link ? ('lerNotif(' + n.id + ')') : 'return false;';
    return '<a href="' + href + '" onclick="' + click + '" class="tb-notif-item ' + (lida ? 'lida' : 'nao-lida') + '">'
      + '<span class="tb-notif-ic" style="background:' + cor + '18;color:' + cor + '"><i class="bi ' + n.icone + '"></i></span>'
      + '<span class="tb-notif-item-txt">'
      + '<span class="tb-notif-item-titulo d-block">' + escC(n.titulo) + '</span>'
      + (n.mensagem ? '<span class="tb-notif-item-sub d-block">' + escC(n.mensagem) + '</span>' : '')
      + '<span class="tb-notif-item-tempo d-block">' + tempoRelativo(n.criado_em) + '</span>'
      + '</span>'
      + (!lida ? '<span class="tb-notif-dot"></span>' : '')
      + '<button onclick="event.preventDefault();event.stopPropagation();excluirNotif(' + n.id + ')" title="Excluir notificação" class="tb-notif-item-del"><i class="bi bi-x-lg"></i></button>'
      + '</a>';
  }).join('');
  atualizarVisibilidadeNotif();
}

// Pendências e Recentes vêm de dois fetches independentes disparados juntos
// (onclick do sino) — só decide a mensagem central única depois que os DOIS
// já responderam, senão o mais rápido pisca "sem nada" antes do outro chegar.
const notifCarregado = { pend: false, rec: false };
function atualizarVisibilidadeNotif() {
  const pend  = document.getElementById('secPendencias');
  const rec   = document.getElementById('secRecentes');
  const vazio = document.getElementById('notifVazioGeral');
  const pendTem = pend.dataset.tem === '1';
  const recTem  = rec.dataset.tem === '1';
  pend.style.display = pendTem ? '' : 'none';
  rec.style.display  = recTem ? '' : 'none';
  if (!notifCarregado.pend || !notifCarregado.rec) return;
  if (!pendTem && !recTem) {
    vazio.textContent = 'Nenhuma notificação por enquanto';
    vazio.style.display = '';
  } else {
    vazio.style.display = 'none';
  }
}

function tempoRelativo(dt) {
  const d = new Date(dt.replace(' ','T'));
  const diffMin = Math.floor((Date.now() - d.getTime()) / 60000);
  if (diffMin < 1) return 'agora mesmo';
  if (diffMin < 60) return 'há ' + diffMin + (diffMin === 1 ? ' minuto' : ' minutos');
  const diffH = Math.floor(diffMin / 60);
  if (diffH < 24) return 'há ' + diffH + (diffH === 1 ? ' hora' : ' horas');
  const diffD = Math.floor(diffH / 24);
  if (diffD < 30) return 'há ' + diffD + (diffD === 1 ? ' dia' : ' dias');
  return d.toLocaleDateString('pt-BR');
}

function lerNotif(id) {
  fetch(NOTIF_LER_URL + id + '/ler');
  carregarNotifs();
}

function fecharNotifDropdown() {
  var btn = document.querySelector('#notifDropdown .tb-bell');
  if (!btn || typeof bootstrap === 'undefined') return;
  var inst = bootstrap.Dropdown.getInstance(btn) || bootstrap.Dropdown.getOrCreateInstance(btn);
  inst.hide();
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
    if (b) { if (n > 0) { b.textContent = n; b.style.display = 'flex'; } else { b.style.display = 'none'; } }
    const lista = document.getElementById('chatLista');
    if (lista) {
      if (!d.itens || !d.itens.length) {
        lista.innerHTML = '<div class="text-center py-3 text-muted small">Sem mensagens novas</div>';
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

<script>
// ── Busca global (topbar): OS, clientes e produtos ──────────────────────
(function () {
  const BUSCA_URL = '<?= url('/api/busca-global') ?>';

  function iniciar(inputId, resultadosId) {
    const input = document.getElementById(inputId);
    const box   = document.getElementById(resultadosId);
    if (!input || !box) return;

    let timer = null;
    let ativo = -1;
    let itens = [];

    function renderizar(resultados) {
      itens = resultados;
      ativo = -1;
      if (!resultados.length) {
        box.innerHTML = '<div class="text-center py-3 text-muted small">Nada encontrado</div>';
        box.classList.remove('d-none');
        return;
      }
      let categoriaAnterior = null;
      box.innerHTML = resultados.map((r, i) => {
        const cabecalho = r.categoria !== categoriaAnterior ? `<div class="busca-global-cat">${r.categoria}</div>` : '';
        categoriaAnterior = r.categoria;
        return `${cabecalho}<a href="${r.url}" class="busca-global-item" data-idx="${i}">
          <span class="bg-icone"><i class="bi ${r.icone}"></i></span>
          <span style="flex:1;min-width:0">
            <span class="titulo d-block text-truncate">${r.titulo}</span>
            ${r.subtitulo ? `<span class="subtitulo d-block text-truncate">${r.subtitulo}</span>` : ''}
          </span>
        </a>`;
      }).join('');
      box.classList.remove('d-none');
    }

    async function buscar(q) {
      if (q.trim().length < 2) { box.classList.add('d-none'); box.innerHTML = ''; return; }
      try {
        const r = await fetch(BUSCA_URL + '?q=' + encodeURIComponent(q));
        const d = await r.json();
        // Ignora resposta se o usuário já mudou o texto (evita resultado desatualizado "piscar")
        if (input.value.trim() === q.trim()) renderizar(d.resultados || []);
      } catch (e) {}
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      const q = input.value;
      timer = setTimeout(function () { buscar(q); }, 300);
    });

    input.addEventListener('keydown', function (ev) {
      const links = box.querySelectorAll('.busca-global-item');
      if (ev.key === 'ArrowDown') {
        ev.preventDefault();
        if (!links.length) return;
        ativo = Math.min(ativo + 1, links.length - 1);
        links.forEach(function (el, i) { el.classList.toggle('ativo', i === ativo); });
        links[ativo].scrollIntoView({ block: 'nearest' });
      } else if (ev.key === 'ArrowUp') {
        ev.preventDefault();
        if (!links.length) return;
        ativo = Math.max(ativo - 1, 0);
        links.forEach(function (el, i) { el.classList.toggle('ativo', i === ativo); });
        links[ativo].scrollIntoView({ block: 'nearest' });
      } else if (ev.key === 'Enter') {
        if (ativo >= 0 && itens[ativo]) { window.location.href = itens[ativo].url; }
      } else if (ev.key === 'Escape') {
        box.classList.add('d-none');
        input.blur();
      }
    });

    document.addEventListener('click', function (ev) {
      if (!input.contains(ev.target) && !box.contains(ev.target)) box.classList.add('d-none');
    });
  }

  iniciar('buscaGlobalInput', 'buscaGlobalResultados');
  iniciar('buscaGlobalInputMobile', 'buscaGlobalResultadosMobile');

  // Botão de lupa no mobile: abre a linha de busca e foca
  const btnMobile = document.getElementById('btnBuscaGlobalMobile');
  const wrapMobile = document.getElementById('buscaGlobalWrapMobile');
  if (btnMobile && wrapMobile) {
    btnMobile.addEventListener('click', function () {
      const abrindo = wrapMobile.classList.contains('d-none');
      wrapMobile.classList.toggle('d-none');
      if (abrindo) {
        document.getElementById('buscaGlobalInputMobile').focus();
      } else {
        document.getElementById('buscaGlobalResultadosMobile').classList.add('d-none');
      }
    });
  }

  // Atalho "/": foca a busca de qualquer lugar, contanto que o usuário não
  // esteja já digitando em outro campo. Esc pra limpar/desfocar já existe
  // dentro do próprio input (ver keydown em iniciar()).
  document.addEventListener('keydown', function (ev) {
    if (ev.key !== '/') return;
    const alvo = ev.target;
    const digitando = alvo && (alvo.tagName === 'INPUT' || alvo.tagName === 'TEXTAREA' || alvo.tagName === 'SELECT' || alvo.isContentEditable);
    if (digitando) return;
    const input = document.getElementById('buscaGlobalInput');
    if (!input) return;
    ev.preventDefault();
    input.focus();
  });
})();
</script>

<!-- Chat da equipe, Previsão de entrega e Exibição do texto agora vivem nas abas de /configuracoes -->

<!-- ===== Modal: ligar/desligar Calculadora e Mentor (botões flutuantes) ===== -->
<div class="modal fade" id="modalFerramentas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Calculadora e Mentor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">Ligue ou desligue os botões flutuantes da Calculadora e do Mentor IA, para toda a empresa.</p>
        <div class="form-check form-switch fs-5 mb-3">
          <input class="form-check-input" type="checkbox" id="cfgCalcToggle" role="switch" <?= $mostrarCalculadora ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold" for="cfgCalcToggle" id="cfgCalcToggleLabel">🧮 Calculadora <?= $mostrarCalculadora ? 'ativada' : 'desativada' ?></label>
        </div>
        <div class="form-check form-switch fs-5 mb-1">
          <input class="form-check-input" type="checkbox" id="cfgMentorToggle" role="switch" <?= $mostrarMentor ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold" for="cfgMentorToggle" id="cfgMentorToggleLabel">💡 Mentor <?= $mostrarMentor ? 'ativado' : 'desativado' ?></label>
        </div>
        <div class="text-muted small mt-2">Desligar esconde o botão da tela de todo mundo na empresa. Nada é apagado — é só ligar de novo quando quiser.</div>
      </div>
      <div class="modal-footer">
        <span class="text-success small me-auto d-none" id="cfgFerramentasSalvoMsg"><i class="bi bi-check-circle-fill me-1"></i>Salvo</span>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="cfgBtnSalvarFerramentas"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('cfgCalcToggle')?.addEventListener('change', function () {
  document.getElementById('cfgCalcToggleLabel').textContent = '🧮 Calculadora ' + (this.checked ? 'ativada' : 'desativada');
});
document.getElementById('cfgMentorToggle')?.addEventListener('change', function () {
  document.getElementById('cfgMentorToggleLabel').textContent = '💡 Mentor ' + (this.checked ? 'ativado' : 'desativado');
});
document.getElementById('cfgBtnSalvarFerramentas')?.addEventListener('click', function () {
  var calc = document.getElementById('cfgCalcToggle')?.checked ? '1' : '0';
  var mentor = document.getElementById('cfgMentorToggle')?.checked ? '1' : '0';
  var btn = this; btn.disabled = true;
  fetch('<?= url('/preferencias/ferramentas') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: 'calculadora=' + calc + '&mentor=' + mentor
  }).then(function (r) { return r.json(); }).then(function (resp) {
    btn.disabled = false;
    if (!resp || !resp.ok) return;
    var msg = document.getElementById('cfgFerramentasSalvoMsg');
    msg.classList.remove('d-none');
    setTimeout(function () { location.reload(); }, 700);
  }).catch(function () { btn.disabled = false; });
});
</script>

<!-- ===== Mentor IA (assistente do dono) ===== -->
<?php if ($mostrarMentor): ?>
<style>
  #mentorFabWrap{position:fixed;right:22px;bottom:22px;z-index:1040}
  #mentorFab{position:static;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;
    padding:12px 18px;border-radius:999px;color:#fff;font-weight:600;font-size:15px;font-family:inherit;
    background:linear-gradient(135deg,#5b53e6,#8b5cf6);box-shadow:0 8px 24px rgba(91,83,230,.42);transition:transform .15s}
  #mentorFab:hover{transform:translateY(-2px)}
  #mentorFabClose{position:absolute;top:-6px;right:-6px;width:21px;height:21px;border-radius:50%;
    background:#fff;color:#5b53e6;border:1.5px solid #e2e0fb;font-size:14px;line-height:1;padding:0;
    display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(20,20,50,.25)}
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
<div id="mentorFabWrap">
  <button id="mentorFab" onclick="mentorToggle(true)" title="Mentor FixaOS"><span style="font-size:18px">💡</span><span class="lbl">Mentor</span></button>
  <button id="mentorFabClose" onclick="mentorFabHide(event)" title="Esconder" aria-label="Esconder Mentor">&times;</button>
</div>
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
    document.getElementById('mentorFabWrap').style.display=abrir?'none':'block';
    if(abrir){ if(!ini){boasVindas();ini=true;} setTimeout(function(){document.getElementById('mentorInput').focus();},80); }
  };
  window.mentorFabHide=function(e){
    if(e) e.stopPropagation();
    document.getElementById('mentorFabWrap').style.display='none';
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
<?php endif; ?>

<!-- ===== Calculadora flutuante (arrastável) ===== -->
<?php if ($mostrarCalculadora): ?>
<style>
  #calcFabWrap{position:fixed;right:22px;bottom:82px;z-index:1040}
  #calcFab{position:static;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;
    padding:12px 18px;border-radius:999px;color:#fff;font-weight:600;font-size:15px;font-family:inherit;
    background:linear-gradient(135deg,#1f2937,#374151);box-shadow:0 8px 24px rgba(31,41,55,.42);transition:transform .15s}
  #calcFab:hover{transform:translateY(-2px)}
  #calcFabClose{position:absolute;top:-6px;right:-6px;width:21px;height:21px;border-radius:50%;
    background:#fff;color:#374151;border:1.5px solid #e2e0fb;font-size:14px;line-height:1;padding:0;
    display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(20,20,50,.25)}
  @media(max-width:520px){#calcFab .lbl{display:none}#calcFab{padding:14px;border-radius:50%}}
  #calcPanel{position:fixed;right:22px;bottom:82px;z-index:1042;width:min(270px,calc(100vw - 28px));
    background:#111827;border-radius:18px;box-shadow:0 24px 64px rgba(20,20,50,.35);display:none;flex-direction:column;overflow:hidden}
  #calcPanel .ch{background:linear-gradient(135deg,#1f2937,#374151);color:#fff;padding:12px 14px;
    display:flex;align-items:center;gap:10px;cursor:move;touch-action:none;user-select:none}
  #calcPanel .ch .ico{font-size:17px}
  #calcPanel .ch h6{margin:0;font-size:14px;font-weight:700;flex:1}
  #calcPanel .ch button{background:none;border:none;color:#fff;font-size:20px;line-height:1;cursor:pointer;opacity:.85}
  .calc-body{padding:12px}
  .calc-display{background:#0b1220;color:#fff;font-size:26px;font-weight:600;text-align:right;
    padding:14px 12px;border-radius:10px;margin-bottom:10px;overflow-x:auto;white-space:nowrap;font-variant-numeric:tabular-nums}
  .calc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
  .c-btn{border:none;border-radius:10px;padding:14px 0;font-size:17px;font-weight:600;background:#374151;color:#fff;cursor:pointer;transition:.1s}
  .c-btn:hover{background:#4b5563}
  .c-btn:active{transform:scale(.95)}
  .c-op{background:#4b5563}
  .c-op:hover{background:#5b6472}
  .c-eq{background:#5b53e6}
  .c-eq:hover{background:#6d63f2}
  .c-zero{grid-column:span 2}
</style>
<div id="calcFabWrap">
  <button id="calcFab" onclick="calcToggle(true)" title="Calculadora"><span style="font-size:18px">🧮</span><span class="lbl">Calculadora</span></button>
  <button id="calcFabClose" onclick="calcFabHide(event)" title="Esconder" aria-label="Esconder Calculadora">&times;</button>
</div>
<div id="calcPanel" role="dialog" aria-label="Calculadora">
  <div class="ch" id="calcDragHandle">
    <span class="ico">🧮</span>
    <h6>Calculadora</h6>
    <button onclick="calcToggle(false)" aria-label="Fechar">&times;</button>
  </div>
  <div class="calc-body">
    <div id="calcDisplay" class="calc-display">0</div>
    <div class="calc-grid">
      <button class="c-btn c-op" onclick="calcClear()">C</button>
      <button class="c-btn c-op" onclick="calcBackspace()">⌫</button>
      <button class="c-btn c-op" onclick="calcPercent()">%</button>
      <button class="c-btn c-op" onclick="calcOperator('÷')">÷</button>

      <button class="c-btn" onclick="calcDigit('7')">7</button>
      <button class="c-btn" onclick="calcDigit('8')">8</button>
      <button class="c-btn" onclick="calcDigit('9')">9</button>
      <button class="c-btn c-op" onclick="calcOperator('×')">×</button>

      <button class="c-btn" onclick="calcDigit('4')">4</button>
      <button class="c-btn" onclick="calcDigit('5')">5</button>
      <button class="c-btn" onclick="calcDigit('6')">6</button>
      <button class="c-btn c-op" onclick="calcOperator('-')">−</button>

      <button class="c-btn" onclick="calcDigit('1')">1</button>
      <button class="c-btn" onclick="calcDigit('2')">2</button>
      <button class="c-btn" onclick="calcDigit('3')">3</button>
      <button class="c-btn c-op" onclick="calcOperator('+')">+</button>

      <button class="c-btn c-zero" onclick="calcDigit('0')">0</button>
      <button class="c-btn" onclick="calcDot()">,</button>
      <button class="c-btn c-eq" onclick="calcEquals()">=</button>
    </div>
  </div>
</div>
<script>
(function(){
  var st={display:'0',prev:null,op:null,waiting:false};
  function render(){document.getElementById('calcDisplay').textContent=st.display;}
  function fmt(n){
    if(!isFinite(n)) return 'Erro';
    var s=(Math.round(n*1e10)/1e10).toString();
    return s.replace('.',',');
  }
  function num(s){return parseFloat(String(s).replace(',','.'))||0;}
  function compute(a,b,op){
    switch(op){
      case '+': return a+b;
      case '-': return a-b;
      case '×': return a*b;
      case '÷': return b===0?NaN:a/b;
    }
    return b;
  }
  window.calcDigit=function(d){
    if(st.waiting||st.display==='Erro'){st.display=d;st.waiting=false;}
    else{st.display=st.display==='0'?d:st.display+d;}
    render();
  };
  window.calcDot=function(){
    if(st.waiting||st.display==='Erro'){st.display='0,';st.waiting=false;render();return;}
    if(st.display.indexOf(',')===-1){st.display+=',';render();}
  };
  window.calcClear=function(){st={display:'0',prev:null,op:null,waiting:false};render();};
  window.calcBackspace=function(){
    if(st.waiting||st.display==='Erro') return;
    st.display=st.display.length>1?st.display.slice(0,-1):'0';
    render();
  };
  window.calcPercent=function(){
    var cur=num(st.display);
    if(st.op&&st.prev!==null){
      cur=(st.op==='+'||st.op==='-')?st.prev*(cur/100):cur/100;
    }else{
      cur=cur/100;
    }
    st.display=fmt(cur);
    render();
  };
  window.calcOperator=function(op){
    var cur=num(st.display);
    if(st.op&&!st.waiting){cur=compute(st.prev,cur,st.op);st.display=fmt(cur);}
    st.prev=cur;st.op=op;st.waiting=true;
    render();
  };
  window.calcEquals=function(){
    if(st.op==null) return;
    var cur=num(st.display);
    var result=compute(st.prev,cur,st.op);
    st.display=fmt(result);
    st.prev=null;st.op=null;st.waiting=true;
    render();
  };
  window.calcToggle=function(abrir){
    document.getElementById('calcPanel').style.display=abrir?'flex':'none';
    document.getElementById('calcFabWrap').style.display=abrir?'none':'block';
  };
  window.calcFabHide=function(e){
    if(e) e.stopPropagation();
    document.getElementById('calcFabWrap').style.display='none';
  };

  // ── Arrastar pelo cabeçalho ──
  var panel=document.getElementById('calcPanel'), handle=document.getElementById('calcDragHandle');
  var dragging=false, offX=0, offY=0;
  function startDrag(x,y){
    var r=panel.getBoundingClientRect();
    panel.style.left=r.left+'px'; panel.style.top=r.top+'px';
    panel.style.right='auto'; panel.style.bottom='auto';
    offX=x-r.left; offY=y-r.top; dragging=true;
  }
  function moveDrag(x,y){
    if(!dragging) return;
    var nx=x-offX, ny=y-offY;
    var maxX=window.innerWidth-panel.offsetWidth, maxY=window.innerHeight-panel.offsetHeight;
    nx=Math.max(0,Math.min(nx,maxX)); ny=Math.max(0,Math.min(ny,maxY));
    panel.style.left=nx+'px'; panel.style.top=ny+'px';
  }
  function endDrag(){dragging=false;}
  handle.addEventListener('mousedown',function(e){e.preventDefault();startDrag(e.clientX,e.clientY);});
  document.addEventListener('mousemove',function(e){moveDrag(e.clientX,e.clientY);});
  document.addEventListener('mouseup',endDrag);
  handle.addEventListener('touchstart',function(e){var t=e.touches[0];startDrag(t.clientX,t.clientY);},{passive:true});
  document.addEventListener('touchmove',function(e){if(!dragging) return;var t=e.touches[0];moveDrag(t.clientX,t.clientY);},{passive:true});
  document.addEventListener('touchend',endDrag);
})();
</script>
<?php endif; ?>
</body>
</html>
