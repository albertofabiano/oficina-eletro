<?php
// Layout minimalista só pra tela de login do admin do Fixa Indica — sem sidebar, sem dado
// nenhum de catálogo (a tela de login não tem acesso a isso antes de autenticar).
$tituloFull = $tituloFull ?? 'Login — Fixa Indica Admin';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($tituloFull) ?></title>
<meta name="robots" content="noindex, nofollow">
<style>
:root{--fx-laranja:#f97316;--fx-roxo:#7c3aed;--fx-bg:#0f172a;--fx-card:#1e293b;--fx-borda:#334155;--fx-texto:#e2e8f0;--fx-texto-sec:#94a3b8}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--fx-bg);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:var(--fx-texto)}
.login-card{background:var(--fx-card);border:1px solid var(--fx-borda);border-radius:12px;padding:32px;width:100%;max-width:360px}
.login-logo{font-weight:800;font-size:1.3rem;text-align:center;margin-bottom:4px}
.login-logo .fixa{color:var(--fx-roxo)}
.login-logo .indica{color:var(--fx-laranja)}
.login-sub{text-align:center;color:var(--fx-texto-sec);font-size:.85rem;margin-bottom:24px}
label{display:block;font-size:.85rem;margin-bottom:6px;color:var(--fx-texto-sec)}
input{width:100%;padding:10px 12px;border:1px solid var(--fx-borda);border-radius:8px;background:#0f172a;color:var(--fx-texto);margin-bottom:16px;font-size:.95rem}
button{width:100%;padding:11px;border:none;border-radius:8px;background:var(--fx-laranja);color:#fff;font-weight:700;font-size:.95rem;cursor:pointer}
.erro{background:#450a0a;border:1px solid #991b1b;color:#fca5a5;padding:10px 12px;border-radius:8px;font-size:.85rem;margin-bottom:16px}
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo"><span class="fixa">Fixa</span><span class="indica">Indica</span></div>
  <div class="login-sub">Painel administrativo</div>
  <?= $content() ?>
</div>
</body>
</html>
