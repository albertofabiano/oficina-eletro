<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#1B1025">
<script>
/* Aplica o tema antes de qualquer CSS carregar, pra não piscar — mesmo mecanismo do resto do
   FixaOS (layouts/main.php): a preferência salva no servidor (usuarios.tema, carregada na
   sessão) vence a local, só cai pro localStorage quando não há usuário com preferência salva
   ainda. Financeiro pessoal reaproveita a MESMA preferência de conta (fx_tema/POST
   /preferencias/tema) em vez de ter a própria — trocar o tema aqui também troca no resto do
   sistema, e vice-versa, porque é a mesma pessoa, a mesma conta. */
(function () {
  var srv = <?= json_encode($_SESSION['usuario']['tema'] ?? null) ?>;
  var pref = srv || localStorage.getItem('fx_tema') || 'auto';
  if (srv) { try { localStorage.setItem('fx_tema', srv); } catch (e) {} }
  var escuro = pref === 'dark' || (pref === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.dataset.theme = escuro ? 'dark' : 'light';
})();
</script>
<title><?= e($titulo ?? 'Financeiro pessoal') ?> — FixaOS</title>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  /* Paleta clara (padrão — bare :root) / escura ([data-theme="dark"]), mesma convenção já
     usada em public/css/tokens.css pro resto do FixaOS. "grana" mantém uma identidade visual
     própria (fundo arroxeado + laranja-coral), separada da paleta azul/teal do sistema
     principal — decisão já tomada antes nesta área, só ganhou o par claro/escuro agora. */
  :root{
    --bg:#F6F2F8; --side:#FFFFFF; --surf:#FFFFFF; --surf2:#F3EEF6; --input:#FBF9FC;
    --line:rgba(30,19,38,.10);
    --text:#1E1326; --muted:#625470; --faint:#8A7C96;
    --accent:#C2490A; --accentInk:#FFFFFF; --accentSoft:rgba(194,73,10,.09); --accentLine:rgba(194,73,10,.35);
    --inc:#0B7A54; --incSoft:rgba(11,122,84,.09); --incInk:#FFFFFF;
    --exp:#B83A29; --expSoft:rgba(184,58,41,.08); --expInk:#FFFFFF;
    --warn:#965A00; --warnSoft:rgba(184,110,0,.11);
    --danger:#B8262A; --dangerSoft:rgba(184,38,42,.07); --dangerLine:rgba(184,38,42,.24);
    --debt:#6B3FCF; --debtSoft:rgba(107,63,207,.10);
    /* Categorias — 6 do design de referência + "Saúde" (própria do FixaOS, não fazia parte da
       lista original; mesma técnica de escurecer+saturar pro claro que as outras já usam). */
    --cat-moradia:#2E8B47; --cat-transporte:#0E8078; --cat-compras:#5A47D6;
    --cat-alimentacao:#B8650A; --cat-outros:#7A6A88; --cat-lazer:#9A32C8; --cat-saude:#A8295F;
    color-scheme: light;
  }
  [data-theme="dark"]{
    --bg:#120A18; --side:#170E1F; --surf:#1D1327; --surf2:#271B33; --input:#170E20;
    --line:rgba(255,255,255,.07);
    --text:#F4EEF8; --muted:#AE9FBC; --faint:#827490;
    --accent:#FF6B1A; --accentInk:#1A0B02; --accentSoft:rgba(255,107,26,.13); --accentLine:rgba(255,107,26,.38);
    --inc:#4FD8A8; --incSoft:rgba(79,216,168,.12); --incInk:#0E2A22;
    --exp:#FF8270; --expSoft:rgba(255,130,112,.12); --expInk:#3A0E0E;
    --warn:#FFB547; --warnSoft:rgba(255,181,71,.13);
    --danger:#FF6464; --dangerSoft:rgba(255,100,100,.10); --dangerLine:rgba(255,100,100,.30);
    --debt:#B794FF; --debtSoft:rgba(183,148,255,.14);
    --cat-moradia:#5BD47A; --cat-transporte:#3CC9C0; --cat-compras:#8C7CFF;
    --cat-alimentacao:#FF9F43; --cat-outros:#B3A3C4; --cat-lazer:#D46BFF; --cat-saude:#D9467C;
    color-scheme: dark;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Baloo 2',sans-serif;min-height:100vh;transition:background .25s,color .25s}
  input::placeholder,textarea::placeholder{color:var(--muted);opacity:1}

  .fp-shell{display:flex;min-height:100vh}

  /* Sidebar (trilha de ícones) — só em telas largas; no mobile vira barra inferior
     (.fp-bottomnav), mesma dualidade já usada no conceito original (Desktop.dc.html vs.
     Main.dc.html). */
  .fp-sidebar{display:none}
  @media (min-width:768px){
    .fp-sidebar{
      display:flex;flex-direction:column;align-items:center;width:80px;flex:0 0 auto;
      padding:20px 0;background:var(--side);border-right:1px solid var(--line);gap:8px;
    }
  }
  .fp-sidebar-brand{width:34px;height:34px;border-radius:10px;background:var(--accentSoft);display:flex;align-items:center;justify-content:center;margin-bottom:14px}
  .fp-sidebar-brand .dot{width:9px;height:9px;border-radius:50%;background:var(--accent)}
  .fp-sidebar-nav{display:flex;flex-direction:column;gap:6px;width:100%;align-items:center}
  .fp-sidebar-nav a{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;text-decoration:none;color:var(--muted);font-size:1.2rem}
  .fp-sidebar-nav a.active{color:var(--accentInk);background:var(--accent)}
  .fp-sidebar-nav a:hover:not(.active){background:var(--surf2)}
  .fp-sidebar-bottom{margin-top:auto;padding:0 10px;width:100%}
  .fp-sidebar-bottom a{display:flex;align-items:center;justify-content:center;padding:10px 4px;border-radius:12px;text-decoration:none;color:var(--muted);font-size:1.1rem}
  .fp-sidebar-bottom a:hover{background:var(--surf2)}

  .fp-main{flex:1;min-width:0;display:flex;flex-direction:column}
  .fp-topbar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 20px;background:var(--side);border-bottom:1px solid var(--line)}
  .fp-topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
  .fp-topbar .brand{display:flex;align-items:center;gap:8px;flex:0 0 auto}
  .fp-topbar .brand b{font-size:1.1rem;letter-spacing:-.02em}
  .fp-topbar .brand span{width:7px;height:7px;border-radius:50%;background:var(--accent)}
  .fp-clock{font-family:'Space Grotesk',sans-serif;font-size:.76rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  @media (min-width:768px){ .fp-topbar .brand{display:none} }
  .fp-topbar-right{display:flex;align-items:center;gap:10px;flex:0 0 auto}
  .fp-topbar a.fp-voltar{color:var(--muted);text-decoration:none;font-size:.85rem;white-space:nowrap}
  .fp-avatar{width:30px;height:30px;border-radius:50%;background:var(--surf2);border:1.5px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--text);flex:0 0 auto;font-family:'Space Grotesk',sans-serif}
  @media (max-width:420px){ .fp-topbar-right a.fp-voltar{display:none} }
  /* Alternância rápida de tema — ícone sol/lua, mesmo padrão do botão rápido já usado no
     topbar do resto do FixaOS (layouts/main.php). Alvo de toque ≥44px mesmo com ícone
     pequeno. */
  .fp-theme-btn{width:38px;height:38px;border-radius:50%;border:1.5px solid var(--line);background:var(--surf2);color:var(--text);display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;flex:0 0 auto}
  .fp-theme-btn:hover{border-color:var(--accentLine)}
  .fp-theme-btn:focus-visible{outline:2px solid var(--accent);outline-offset:2px}

  /* Largura e respiro das laterais fluidos — cresce suavemente com o viewport em vez de
     travar num max-width fixo (que, em telas largas, sobrava muito vazio dos dois lados —
     ver pedido do usuário, "layout mais fluido pras laterais"). clamp() evita o salto brusco
     de um breakpoint único: o mínimo garante respiro em telas pequenas, o máximo evita linha
     de texto/cards esticados demais em monitor largo. */
  .fp-wrap{max-width:min(820px,94vw);margin:0 auto;padding:20px clamp(16px,4vw,28px) 90px;width:100%}
  @media (min-width:768px){ .fp-wrap{padding-bottom:20px;max-width:min(860px,88vw)} }

  /* Dashboard usa a tela inteira no desktop (dono de empresa usa isso mais no computador —
     pedido explícito do usuário) — form de lançamento (fp-wrap normal) continua numa coluna
     estreita, onde um <input> esticado por 1600px ficaria ruim de usar. */
  @media (min-width:768px){ .fp-wrap-full{max-width:none;padding-left:32px;padding-right:32px} }

  /* Linha Valor+Categoria do formulário — empilha em telas bem estreitas (ex.: 320px), onde
     os dois campos lado a lado espremiam o <select> a ponto de cortar o texto da categoria. */
  .fp-row-valor-cat{display:flex;gap:8px}
  @media (max-width:380px){ .fp-row-valor-cat{flex-direction:column} }

  /* 3 cards (Entrada/Saída/Saldo) no topo da tela de Lançamentos — empilha no mobile; 3
     cards precisam de mais largura que o par Valor+Categoria do form (que já quebra a
     partir de 380px), por isso vira linha só a partir de 560px. */
  .fp-kpis-3{display:flex;flex-direction:column;gap:10px;margin-bottom:16px}
  @media (min-width:560px){ .fp-kpis-3{display:grid;grid-template-columns:repeat(3,1fr)} }

  .fp-card{background:var(--surf);border:1px solid var(--line);border-radius:16px;padding:18px}

  /* Dashboard: 1 coluna empilhada no mobile (mesmo visual de sempre); no desktop os 4 KPIs
     viram uma linha e o gráfico ganha mais espaço que "Por categoria" ao lado — usa a largura
     cheia que o .fp-wrap-full liberou, em vez de ficar tudo espremido numa coluna central. */
  .fp-dash-kpis{display:flex;flex-direction:column;gap:16px;margin-bottom:16px}
  .fp-dash-main{display:flex;flex-direction:column;gap:16px}
  .fp-dash-chart{height:140px}
  @media (min-width:992px){
    .fp-dash-kpis{display:grid;grid-template-columns:repeat(4,1fr)}
    .fp-dash-main{display:grid;grid-template-columns:2fr 1fr;align-items:start}
    .fp-dash-chart{height:280px}
  }
  .fp-mono{font-family:'Space Grotesk',sans-serif;font-variant-numeric:tabular-nums;letter-spacing:-.02em}
  .fp-muted{color:var(--muted)}
  .fp-faint{color:var(--faint)}
  .fp-input,.fp-select{width:100%;background:var(--input);border:1.5px solid var(--line);border-radius:12px;padding:12px 14px;font-family:'Baloo 2',sans-serif;font-size:.95rem;color:var(--text);outline:none}
  .fp-input:focus,.fp-select:focus{border-color:var(--accent)}
  .fp-btn{border:none;border-radius:12px;padding:12px 18px;font-family:'Baloo 2',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;min-height:44px}
  .fp-btn-primary{background:var(--accent);color:var(--accentInk)}
  .fp-btn-primary:disabled{opacity:.55;cursor:default}
  .fp-btn-ghost{background:transparent;color:var(--text);border:1.5px solid var(--line)}
  /* Variantes semânticas — toggle Gasto/Entrada do form e o botão de salvar acompanham a cor
     do tipo escolhido, reforçando antes mesmo de salvar que aquele lançamento é despesa ou
     receita. */
  .fp-btn-despesa{background:var(--exp);color:var(--expInk)}
  .fp-btn-despesa:disabled{opacity:.55;cursor:default}
  .fp-btn-receita{background:var(--inc);color:var(--incInk)}
  .fp-btn-receita:disabled{opacity:.55;cursor:default}

  /* Filtro lateral da listagem de lançamentos do mês (Todos/Entradas/Saídas) — coluna estreita
     de botões ao lado da lista, não embaixo, mesmo em mobile (3 botões empilhados ocupam
     pouca largura mesmo em 320px, e "na lateral" foi pedido explícito do usuário). */
  .fp-filtros{display:flex;flex-direction:column;gap:6px;flex:0 0 auto}
  .fp-filtro-btn{display:flex;flex-direction:column;align-items:center;gap:3px;width:58px;padding:9px 4px;border-radius:12px;border:1.5px solid var(--line);background:var(--surf);color:var(--muted);font-size:.62rem;font-weight:700;cursor:pointer;font-family:'Baloo 2',sans-serif;line-height:1.15;text-align:center}
  .fp-filtro-btn i{font-size:1.05rem}
  .fp-filtro-btn.active{background:var(--accent);color:var(--accentInk);border-color:var(--accent)}
  /* "Todos" fica na cor neutra de marca (acima); "Entradas"/"Saídas" ativos puxam pro mesmo
     verde/vermelho usado no resto da tela, pra o filtro já avisar visualmente o que a lista
     vai mostrar antes mesmo de ler o rótulo. */
  .fp-filtro-btn.active[data-filtro="receita"]{background:var(--inc);border-color:var(--inc);color:var(--incInk)}
  .fp-filtro-btn.active[data-filtro="despesa"]{background:var(--exp);border-color:var(--exp);color:var(--expInk)}
  /* Abaixo de 360px, o rótulo some (vira ícone só, mesmo padrão do rail do desktop) — a
     coluna de filtro cair pra 58px+label espremia demais o título dos lançamentos ao lado
     (ex.: "Supermercado Dia" virava "Sup…"); ícone com title/aria-label já basta aqui. */
  @media (max-width:360px){
    .fp-filtro-btn{width:38px;height:38px;padding:0;justify-content:center}
    .fp-filtro-btn span{display:none}
  }

  /* Barra inferior — só no mobile (a sidebar acima cobre telas largas). */
  .fp-bottomnav{display:flex;position:fixed;left:0;right:0;bottom:0;background:var(--side);border-top:1px solid var(--line);padding:8px 8px calc(8px + env(safe-area-inset-bottom,0px));z-index:10}
  @media (min-width:768px){ .fp-bottomnav{display:none} }
  .fp-bottomnav a{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 0;text-decoration:none;color:var(--muted);font-size:.68rem;font-weight:700}
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
        <button type="button" class="fp-theme-btn" id="fpThemeToggle" aria-label="Alternar tema claro/escuro">
          <i id="fpThemeIcon" class="bi bi-moon-stars" aria-hidden="true"></i>
        </button>
        <div class="fp-avatar" title="<?= e($nomeUsuario) ?>"><?= e(avatar_iniciais($nomeUsuario)) ?></div>
        <a href="<?= url('/dashboard') ?>" class="fp-voltar">← Voltar pro FixaOS</a>
      </div>
    </div>
    <div class="fp-wrap<?= !empty($wrapFull) ? ' fp-wrap-full' : '' ?>">
    <?php ($content)(); ?>
    </div>
  </div>

</div>

<nav class="fp-bottomnav">
  <a href="<?= url('/financeiro-pessoal') ?>" class="<?= $ativoLancamentos ?>"><i class="bi bi-chat-dots-fill"></i>Lançamentos</a>
  <a href="<?= url('/financeiro-pessoal/dashboard') ?>" class="<?= $ativoResumo ?>"><i class="bi bi-bar-chart-fill"></i>Resumo</a>
</nav>
<script src="<?= url('/js/theme.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/theme.js') ?>"></script>
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

// Alternância rápida de tema — reaproveita window.FxTheme (public/js/theme.js) e o mesmo
// endpoint POST /preferencias/tema já usado no resto do FixaOS, então a preferência é da
// CONTA, não só desta área. Mesmo padrão do botão rápido do topbar em layouts/main.php.
(function () {
  var CSRF_TOKEN = '<?= csrf_token() ?>';
  var SAVE_URL = '<?= url('/preferencias/tema') ?>';
  var btn = document.getElementById('fpThemeToggle');
  var icon = document.getElementById('fpThemeIcon');
  function atualizarIcone() {
    var escuro = document.documentElement.dataset.theme === 'dark';
    if (icon) icon.className = escuro ? 'bi bi-sun' : 'bi bi-moon-stars';
    if (btn) btn.title = escuro ? 'Mudar para tema claro' : 'Mudar para tema escuro';
  }
  atualizarIcone();
  window.addEventListener('fx-theme-change', atualizarIcone);
  if (btn) {
    btn.addEventListener('click', function () {
      var novo = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      if (window.FxTheme) window.FxTheme.set(novo, CSRF_TOKEN, SAVE_URL);
    });
  }
})();
</script>
</body>
</html>
