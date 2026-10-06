<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1B1025">
<title><?= e($titulo ?? 'Financeiro pessoal') ?> — FixaOS</title>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root{
    --bg:#1B1025; --surface:#271A33; --surface-2:#2A1B38; --border:rgba(245,239,250,.08);
    --text:#F5EFFA; --text-muted:#8C7A9E; --accent:#FF6B47; --accent-ink:#1B1025;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Baloo 2',sans-serif;min-height:100vh}
  input::placeholder,textarea::placeholder{color:var(--text-muted);opacity:1}

  .fp-shell{display:flex;min-height:100vh}

  /* Sidebar (trilha de ícones) — só em telas largas; no mobile vira barra inferior
     (.fp-bottomnav), mesma dualidade já usada no conceito original (Desktop.dc.html vs.
     Main.dc.html). */
  .fp-sidebar{display:none}
  @media (min-width:768px){
    .fp-sidebar{
      display:flex;flex-direction:column;align-items:center;width:80px;flex:0 0 auto;
      padding:20px 0;border-right:1px solid var(--border);gap:8px;
    }
  }
  .fp-sidebar-brand{width:34px;height:34px;border-radius:10px;background:rgba(255,107,71,.16);display:flex;align-items:center;justify-content:center;margin-bottom:14px}
  .fp-sidebar-brand .dot{width:9px;height:9px;border-radius:50%;background:var(--accent)}
  .fp-sidebar-nav{display:flex;flex-direction:column;gap:6px;width:100%;align-items:center}
  .fp-sidebar-nav a{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;text-decoration:none;color:var(--text-muted);font-size:1.2rem}
  .fp-sidebar-nav a.active{color:var(--accent-ink);background:var(--accent)}
  .fp-sidebar-nav a:hover:not(.active){background:var(--surface)}
  .fp-sidebar-bottom{margin-top:auto;padding:0 10px;width:100%}
  .fp-sidebar-bottom a{display:flex;align-items:center;justify-content:center;padding:10px 4px;border-radius:12px;text-decoration:none;color:var(--text-muted);font-size:1.1rem}
  .fp-sidebar-bottom a:hover{background:var(--surface)}

  .fp-main{flex:1;min-width:0;display:flex;flex-direction:column}
  .fp-topbar{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border)}
  .fp-topbar .brand{display:flex;align-items:center;gap:8px}
  .fp-topbar .brand b{font-size:1.1rem;letter-spacing:-.02em}
  .fp-topbar .brand span{width:7px;height:7px;border-radius:50%;background:var(--accent)}
  .fp-topbar a{color:var(--text-muted);text-decoration:none;font-size:.85rem}
  @media (min-width:768px){ .fp-topbar .brand{display:none} }

  .fp-wrap{max-width:640px;margin:0 auto;padding:20px 16px 90px;width:100%}
  @media (min-width:768px){ .fp-wrap{padding-bottom:20px} }

  .fp-card{background:var(--surface);border-radius:16px;padding:18px}
  .fp-mono{font-family:'Space Grotesk',sans-serif}
  .fp-muted{color:var(--text-muted)}
  .fp-input,.fp-select{width:100%;background:var(--surface-2);border:1.5px solid var(--border);border-radius:12px;padding:12px 14px;font-family:'Baloo 2',sans-serif;font-size:.95rem;color:var(--text);outline:none}
  .fp-input:focus,.fp-select:focus{border-color:var(--accent)}
  .fp-btn{border:none;border-radius:12px;padding:12px 18px;font-family:'Baloo 2',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer}
  .fp-btn-primary{background:var(--accent);color:var(--accent-ink)}
  .fp-btn-primary:disabled{opacity:.55;cursor:default}
  .fp-btn-ghost{background:transparent;color:var(--text);border:1.5px solid var(--border)}

  /* Barra inferior — só no mobile (a sidebar acima cobre telas largas). */
  .fp-bottomnav{display:flex;position:fixed;left:0;right:0;bottom:0;background:var(--bg);border-top:1px solid var(--border);padding:8px 8px calc(8px + env(safe-area-inset-bottom,0px));z-index:10}
  @media (min-width:768px){ .fp-bottomnav{display:none} }
  .fp-bottomnav a{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 0;text-decoration:none;color:var(--text-muted);font-size:.68rem;font-weight:700}
  .fp-bottomnav a i{font-size:1.2rem}
  .fp-bottomnav a.active{color:var(--accent)}
</style>
</head>
<body>
<?php
  $uriAtual = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
  $ativoLancamentos = $uriAtual === '/financeiro-pessoal' ? 'active' : '';
  $ativoResumo = $uriAtual === '/financeiro-pessoal/dashboard' ? 'active' : '';
?>
<div class="fp-shell">

  <aside class="fp-sidebar">
    <div class="fp-sidebar-brand" title="grana"><span class="dot" aria-hidden="true"></span></div>
    <nav class="fp-sidebar-nav">
      <a href="<?= url('/financeiro-pessoal') ?>" class="<?= $ativoLancamentos ?>" title="Lançamentos" aria-label="Lançamentos"><i class="bi bi-chat-dots-fill"></i></a>
      <a href="<?= url('/financeiro-pessoal/dashboard') ?>" class="<?= $ativoResumo ?>" title="Resumo" aria-label="Resumo"><i class="bi bi-bar-chart-fill"></i></a>
    </nav>
    <div class="fp-sidebar-bottom">
      <a href="<?= url('/dashboard') ?>" title="Voltar pro FixaOS"><i class="bi bi-box-arrow-left"></i></a>
    </div>
  </aside>

  <div class="fp-main">
    <div class="fp-topbar">
      <div class="brand"><b>grana</b><span aria-hidden="true"></span></div>
      <a href="<?= url('/dashboard') ?>">← Voltar pro FixaOS</a>
    </div>
    <div class="fp-wrap">
    <?php ($content)(); ?>
    </div>
  </div>

</div>

<nav class="fp-bottomnav">
  <a href="<?= url('/financeiro-pessoal') ?>" class="<?= $ativoLancamentos ?>"><i class="bi bi-chat-dots-fill"></i>Lançamentos</a>
  <a href="<?= url('/financeiro-pessoal/dashboard') ?>" class="<?= $ativoResumo ?>"><i class="bi bi-bar-chart-fill"></i>Resumo</a>
</nav>
</body>
</html>
