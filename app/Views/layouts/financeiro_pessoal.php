<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1B1025">
<title><?= e($titulo ?? 'Financeiro pessoal') ?> — FixaOS</title>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#1B1025; --surface:#271A33; --surface-2:#2A1B38; --border:rgba(245,239,250,.08);
    --text:#F5EFFA; --text-muted:#8C7A9E; --accent:#FF6B47; --accent-ink:#1B1025;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Baloo 2',sans-serif;min-height:100vh}
  input::placeholder,textarea::placeholder{color:var(--text-muted);opacity:1}
  .fp-topbar{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border)}
  .fp-topbar .brand{display:flex;align-items:center;gap:8px}
  .fp-topbar .brand b{font-size:1.1rem;letter-spacing:-.02em}
  .fp-topbar .brand span{width:7px;height:7px;border-radius:50%;background:var(--accent)}
  .fp-topbar a{color:var(--text-muted);text-decoration:none;font-size:.85rem}
  .fp-wrap{max-width:640px;margin:0 auto;padding:20px 16px 90px}
  .fp-card{background:var(--surface);border-radius:16px;padding:18px}
  .fp-mono{font-family:'Space Grotesk',sans-serif}
  .fp-muted{color:var(--text-muted)}
  .fp-input,.fp-select{width:100%;background:var(--surface-2);border:1.5px solid var(--border);border-radius:12px;padding:12px 14px;font-family:'Baloo 2',sans-serif;font-size:.95rem;color:var(--text);outline:none}
  .fp-input:focus,.fp-select:focus{border-color:var(--accent)}
  .fp-btn{border:none;border-radius:12px;padding:12px 18px;font-family:'Baloo 2',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer}
  .fp-btn-primary{background:var(--accent);color:var(--accent-ink)}
  .fp-btn-primary:disabled{opacity:.55;cursor:default}
  .fp-btn-ghost{background:transparent;color:var(--text);border:1.5px solid var(--border)}
</style>
</head>
<body>
<div class="fp-topbar">
  <div class="brand"><b>grana</b><span aria-hidden="true"></span></div>
  <a href="<?= url('/dashboard') ?>">← Voltar pro FixaOS</a>
</div>
<div class="fp-wrap">
<?php ($content)(); ?>
</div>
</body>
</html>
