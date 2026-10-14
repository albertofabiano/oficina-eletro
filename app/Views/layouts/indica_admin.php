<?php
// Layout do painel admin autenticado do Fixa Indica — sidebar fixa + conteúdo. Visual
// utilitário (não precisa da polidez de marketing do layout público), mas reaproveita a
// mesma paleta (laranja/roxo) pra ficar reconhecível como o mesmo produto.
$tituloFull = $tituloFull ?? 'Fixa Indica Admin';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($tituloFull) ?></title>
<meta name="robots" content="noindex, nofollow">
<style>
:root{--fx-laranja:#f97316;--fx-roxo:#7c3aed;--bg:#0f172a;--card:#1e293b;--borda:#334155;--texto:#e2e8f0;--texto-sec:#94a3b8;--verde:#16a34a;--vermelho:#dc2626}
*{box-sizing:border-box}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:var(--texto);display:flex;min-height:100vh}
a{color:inherit;text-decoration:none}
.sidebar{width:220px;flex:0 0 220px;background:var(--card);border-right:1px solid var(--borda);padding:20px 0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-logo{padding:0 20px 20px;font-weight:800;font-size:1.1rem;border-bottom:1px solid var(--borda);margin-bottom:12px}
.sidebar-logo .fixa{color:var(--fx-roxo)}
.sidebar-logo .indica{color:var(--fx-laranja)}
.sidebar nav a{display:block;padding:10px 20px;color:var(--texto-sec);font-size:.9rem;font-weight:600}
.sidebar nav a:hover,.sidebar nav a.ativo{background:rgba(249,115,22,.1);color:var(--fx-laranja)}
.sidebar-rodape{padding:14px 20px;border-top:1px solid var(--borda);margin-top:12px}
.sidebar-rodape a{color:var(--texto-sec);font-size:.82rem}
.main{flex:1;padding:28px 32px;min-width:0}
.topo{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.topo h1{font-size:1.4rem;margin:0}
.card{background:var(--card);border:1px solid var(--borda);border-radius:10px;padding:20px;margin-bottom:20px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:none;font-weight:700;font-size:.88rem;cursor:pointer;background:var(--fx-laranja);color:#fff}
.btn-secundario{background:#334155;color:var(--texto)}
.btn-perigo{background:var(--vermelho);color:#fff}
.btn-sm{padding:5px 10px;font-size:.8rem}
table{width:100%;border-collapse:collapse;font-size:.88rem}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--borda)}
th{color:var(--texto-sec);font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.03em}
input[type=text],input[type=number],input[type=email],input[type=password],input[type=url],input[type=date],textarea,select{width:100%;padding:9px 12px;border:1px solid var(--borda);border-radius:8px;background:#0f172a;color:var(--texto);font-size:.9rem;font-family:inherit}
label{display:block;font-size:.82rem;color:var(--texto-sec);margin-bottom:5px;font-weight:600}
.campo{margin-bottom:16px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.88rem}
.flash-success{background:#052e16;border:1px solid var(--verde);color:#86efac}
.flash-error{background:#450a0a;border:1px solid var(--vermelho);color:#fca5a5}
.badge{display:inline-block;padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:700}
.badge-verde{background:rgba(22,163,74,.15);color:#4ade80}
.badge-cinza{background:rgba(148,163,184,.15);color:var(--texto-sec)}
.badge-amarelo{background:rgba(245,196,0,.18);color:#f5c400}
.badge-vermelho{background:rgba(224,50,24,.18);color:#f87171}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px}
.kpi{background:var(--card);border:1px solid var(--borda);border-radius:10px;padding:18px}
.kpi .num{font-size:1.8rem;font-weight:800}
.kpi .rotulo{color:var(--texto-sec);font-size:.8rem}
.checkbox-row{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.checkbox-row input{width:auto}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo"><span class="fixa">Fixa</span><span class="indica">Indica</span> Admin</div>
  <nav>
    <a href="/admin" class="<?= ($ativo ?? '') === 'dashboard' ? 'ativo' : '' ?>">Dashboard</a>
    <a href="/admin/categorias" class="<?= ($ativo ?? '') === 'categorias' ? 'ativo' : '' ?>">Categorias</a>
    <a href="/admin/produtos" class="<?= ($ativo ?? '') === 'produtos' ? 'ativo' : '' ?>">Produtos</a>
    <a href="/admin/guias" class="<?= ($ativo ?? '') === 'guias' ? 'ativo' : '' ?>">Guias</a>
    <a href="/admin/banners" class="<?= ($ativo ?? '') === 'banners' ? 'ativo' : '' ?>">Banners</a>
    <a href="/admin/config" class="<?= ($ativo ?? '') === 'config' ? 'ativo' : '' ?>">Configurações</a>
  </nav>
  <div class="sidebar-rodape">
    <div style="margin-bottom:8px"><?= e($_SESSION['master_nome'] ?? '') ?></div>
    <a href="/admin/logout">Sair</a>
  </div>
</aside>
<main class="main">
  <?php if ($msg = flash('success')): ?><div class="flash flash-success"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($msg = flash('error')): ?><div class="flash flash-error"><?= e($msg) ?></div><?php endif; ?>
  <?= $content() ?>
</main>
</body>
</html>
