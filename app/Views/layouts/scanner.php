<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e3a5f">
<title><?= e($titulo ?? 'FixaOS') ?></title>
<style>
  :root{--brand:#1e3a5f;--accent:#f97316;--ok:#16a34a;--muted:#64748b;--border:#e2e8f0}
  *{box-sizing:border-box}
  body{margin:0;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f1f5f9;color:#0f172a;min-height:100vh}
  .topbar{background:var(--brand);color:#fff;padding:14px 16px;display:flex;align-items:center;gap:8px}
  .topbar b{font-size:1.05rem}
  .topbar span{font-size:.85rem;opacity:.85}
  .wrap{max-width:480px;margin:0 auto;padding:16px}
  .card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:14px}
  h1{font-size:1.05rem;margin:.2rem 0 .6rem}
  label{display:block;font-size:.82rem;font-weight:600;color:#334155;margin:.7rem 0 .25rem}
  input[type=text]{width:100%;padding:14px;font-size:1rem;border:1.5px solid var(--border);border-radius:12px;-webkit-appearance:none}
  input[type=text]:focus{outline:none;border-color:var(--brand)}
  .btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:16px;font-size:1.05rem;font-weight:700;border:none;border-radius:14px;cursor:pointer;text-decoration:none}
  .btn-cam{background:var(--brand);color:#fff}
  .btn-send{background:var(--accent);color:#fff;margin-top:10px}
  .btn:disabled{opacity:.6}
  .preview{width:100%;max-height:42vh;object-fit:contain;border-radius:12px;margin-top:12px;display:none}
  .hint{color:var(--muted);font-size:.84rem;line-height:1.45}
  .ok-box{text-align:center;padding:24px}
  .ok-box .big{font-size:3rem;line-height:1}
  .spin{width:18px;height:18px;border:3px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:sp .8s linear infinite;display:inline-block}
  @keyframes sp{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="topbar"><b>📷 FixaOS</b><span>Scanner de etiqueta</span></div>
<div class="wrap">
<?php ($content)(); ?>
</div>
</body>
</html>
