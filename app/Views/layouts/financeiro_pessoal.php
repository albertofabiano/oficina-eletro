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
  .fp-topbar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 20px;border-bottom:1px solid var(--border)}
  .fp-topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
  .fp-topbar .brand{display:flex;align-items:center;gap:8px;flex:0 0 auto}
  .fp-topbar .brand b{font-size:1.1rem;letter-spacing:-.02em}
  .fp-topbar .brand span{width:7px;height:7px;border-radius:50%;background:var(--accent)}
  .fp-clock{font-family:'Space Grotesk',sans-serif;font-size:.76rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  @media (min-width:768px){ .fp-topbar .brand{display:none} }
  .fp-topbar-right{display:flex;align-items:center;gap:12px;flex:0 0 auto}
  .fp-topbar a.fp-voltar{color:var(--text-muted);text-decoration:none;font-size:.85rem;white-space:nowrap}
  .fp-avatar{width:30px;height:30px;border-radius:50%;background:var(--surface-2);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--text);flex:0 0 auto;font-family:'Space Grotesk',sans-serif}
  @media (max-width:420px){ .fp-topbar-right a.fp-voltar{display:none} }

  /* Largura e respiro das laterais fluidos — cresce suavemente com o viewport em vez de
     travar num max-width fixo (que, em telas largas, sobrava muito vazio dos dois lados —
     ver pedido do usuário, "layout mais fluido pras laterais"). clamp() evita o salto brusco
     de um breakpoint único: o mínimo garante respiro em telas pequenas, o máximo evita linha
     de texto/cards esticados demais em monitor largo. */
  .fp-wrap{max-width:min(820px,94vw);margin:0 auto;padding:20px clamp(16px,4vw,28px) 90px;width:100%}
  @media (min-width:768px){ .fp-wrap{padding-bottom:20px;max-width:min(860px,88vw)} }

  /* Linha Valor+Categoria do formulário — empilha em telas bem estreitas (ex.: 320px), onde
     os dois campos lado a lado espremiam o <select> a ponto de cortar o texto da categoria. */
  .fp-row-valor-cat{display:flex;gap:8px}
  @media (max-width:380px){ .fp-row-valor-cat{flex-direction:column} }

  .fp-card{background:var(--surface);border-radius:16px;padding:18px}
  .fp-mono{font-family:'Space Grotesk',sans-serif}
  .fp-muted{color:var(--text-muted)}
  .fp-input,.fp-select{width:100%;background:var(--surface-2);border:1.5px solid var(--border);border-radius:12px;padding:12px 14px;font-family:'Baloo 2',sans-serif;font-size:.95rem;color:var(--text);outline:none}
  .fp-input:focus,.fp-select:focus{border-color:var(--accent)}
  .fp-btn{border:none;border-radius:12px;padding:12px 18px;font-family:'Baloo 2',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer}
  .fp-btn-primary{background:var(--accent);color:var(--accent-ink)}
  .fp-btn-primary:disabled{opacity:.55;cursor:default}
  .fp-btn-ghost{background:transparent;color:var(--text);border:1.5px solid var(--border)}

  /* Filtro lateral da listagem de lançamentos do mês (Todos/Entradas/Saídas) — coluna estreita
     de botões ao lado da lista, não embaixo, mesmo em mobile (3 botões empilhados ocupam
     pouca largura mesmo em 320px, e "na lateral" foi pedido explícito do usuário). */
  .fp-filtros{display:flex;flex-direction:column;gap:6px;flex:0 0 auto}
  .fp-filtro-btn{display:flex;flex-direction:column;align-items:center;gap:3px;width:58px;padding:9px 4px;border-radius:12px;border:1.5px solid var(--border);background:var(--surface);color:var(--text-muted);font-size:.62rem;font-weight:700;cursor:pointer;font-family:'Baloo 2',sans-serif;line-height:1.15;text-align:center}
  .fp-filtro-btn i{font-size:1.05rem}
  .fp-filtro-btn.active{background:var(--accent);color:var(--accent-ink);border-color:var(--accent)}
  /* Abaixo de 360px, o rótulo some (vira ícone só, mesmo padrão do rail do desktop) — a
     coluna de filtro cair pra 58px+label espremia demais o título dos lançamentos ao lado
     (ex.: "Supermercado Dia" virava "Sup…"); ícone com title/aria-label já basta aqui. */
  @media (max-width:360px){
    .fp-filtro-btn{width:38px;height:38px;padding:0;justify-content:center}
    .fp-filtro-btn span{display:none}
  }

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

  <?php $nomeUsuario = \App\Core\Auth::user()['nome'] ?? 'Você'; ?>
  <div class="fp-main">
    <div class="fp-topbar">
      <div class="fp-topbar-left">
        <div class="brand"><b>grana</b><span aria-hidden="true"></span></div>
        <div class="fp-clock" id="fpClock"></div>
      </div>
      <div class="fp-topbar-right">
        <div class="fp-avatar" title="<?= e($nomeUsuario) ?>"><?= e(avatar_iniciais($nomeUsuario)) ?></div>
        <a href="<?= url('/dashboard') ?>" class="fp-voltar">← Voltar pro FixaOS</a>
      </div>
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
<script>
(function () {
  var el = document.getElementById('fpClock');
  if (!el) return;
  var dias = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
  var meses = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
  function atualizar() {
    var d = new Date();
    var hh = String(d.getHours()).padStart(2, '0');
    var mm = String(d.getMinutes()).padStart(2, '0');
    el.textContent = dias[d.getDay()] + ', ' + d.getDate() + ' de ' + meses[d.getMonth()] + ' · ' + hh + ':' + mm;
  }
  atualizar();
  setInterval(atualizar, 15000);
})();
</script>
</body>
</html>
