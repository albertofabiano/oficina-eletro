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
<!-- IMask — mesma lib/versão já usada em public/js/masks.js pro resto do FixaOS (máscara de
     CPF/CNPJ do perfil, ver financeiro_pessoal/configuracoes.php). Única dependência externa
     de JS deste módulo isolado — é só uma lib de máscara de campo, não arrasta Bootstrap nem
     nenhuma outra coisa junto. -->
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<!-- Sem CDN de ícones de propósito — Financeiro Pessoal é isolado do resto do FixaOS (só o
     login é compartilhado); ícones são SVG inline via fp_icone(), ver app/Helpers/functions.php -->
<style>
  /* Paleta clara (padrão — bare :root) / escura ([data-theme="dark"]), mesma convenção já
     usada em public/css/tokens.css pro resto do FixaOS. "fixa" mantém uma identidade visual
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
    --blue:#1D5FD1; --blueInk:#FFFFFF; --blueSoft:rgba(29,95,209,.09); --blueLine:rgba(29,95,209,.35);
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
    --blue:#5B9DFF; --blueInk:#071A33; --blueSoft:rgba(91,157,255,.14); --blueLine:rgba(91,157,255,.40);
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
  .fp-sidebar-brand{width:34px;height:34px;border-radius:10px;background:var(--accentSoft);display:flex;align-items:center;justify-content:center;margin-bottom:14px;overflow:hidden}
  .fp-sidebar-brand .dot{width:9px;height:9px;border-radius:50%;background:var(--accent)}
  .fp-sidebar-nav{display:flex;flex-direction:column;gap:6px;width:100%;align-items:center}
  /* --text em vez de branco fixo (pedido do usuário) — no tema escuro --text já é um tom
     quase branco (#F4EEF8), lê como "branco" na tela dele; no tema claro ele vira escuro
     (#1E1326), continua legível contra o --side branco de lá. Branco fixo sumiria no claro. */
  .fp-sidebar-nav a{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;text-decoration:none;color:var(--text);font-size:1.2rem}
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
  .fp-avatar{width:30px;height:30px;border-radius:50%;background:var(--surf2);border:1.5px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--text);flex:0 0 auto;font-family:'Space Grotesk',sans-serif;overflow:hidden}
  @media (max-width:420px){ .fp-topbar-right a.fp-voltar{display:none} }

  /* Fixa Fase 1 (PF/PJ) — faixa fina com a cor do perfil ativo, topo absoluto da página (antes
     até da sidebar/topbar) + seletor de perfil no topo, ao lado do relógio. */
  .fp-perfil-stripe{height:4px;width:100%}
  .fp-perfil-seletor{display:flex;align-items:center;gap:6px;background:var(--surf2);border:1px solid var(--line);border-radius:999px;padding:4px 10px 4px 8px;flex:0 0 auto}
  .fp-perfil-dot{width:9px;height:9px;border-radius:50%;flex:0 0 auto}
  .fp-perfil-select{border:none;background:transparent;color:var(--text);font-family:'Baloo 2',sans-serif;font-weight:700;font-size:.82rem;outline:none;cursor:pointer;max-width:140px;text-overflow:ellipsis}
  .fp-perfil-select option{background:var(--surf);color:var(--text)}
  @media (max-width:640px){ .fp-perfil-select{max-width:90px} }
  .fp-conta-arquivada{opacity:.55}
  /* Alternância rápida de tema — ícone sol/lua, mesmo padrão do botão rápido já usado no
     topbar do resto do FixaOS (layouts/main.php). Alvo de toque ≥44px mesmo com ícone
     pequeno. */
  .fp-theme-btn{width:38px;height:38px;border-radius:50%;border:1.5px solid var(--line);background:var(--surf2);color:var(--text);display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;flex:0 0 auto}
  .fp-theme-btn:hover{border-color:var(--accentLine)}
  .fp-theme-btn:focus-visible{outline:2px solid var(--accent);outline-offset:2px}

  /* ── Sino de notificação (eventos da Agenda chegando no horário) — pedido do usuário.
     Mesmo formato de botão circular do tema (.fp-theme-btn), com um badge de contagem no
     canto e um painel suspenso ancorado por baixo (position:relative no wrapper). */
  .fp-notif-wrap{position:relative;flex:0 0 auto}
  .fp-notif-btn{width:38px;height:38px;border-radius:50%;border:1.5px solid var(--line);background:var(--surf2);color:var(--text);display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;position:relative}
  .fp-notif-btn:hover{border-color:var(--accentLine)}
  .fp-notif-btn:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
  .fp-notif-badge{position:absolute;top:-2px;right:-2px;min-width:16px;height:16px;padding:0 3px;border-radius:999px;background:var(--exp);color:var(--expInk);font-size:.6rem;font-weight:800;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;line-height:1}
  .fp-notif-panel{display:none;position:absolute;top:calc(100% + 8px);right:0;width:300px;max-width:calc(100vw - 32px);max-height:360px;overflow-y:auto;background:var(--surf);border:1px solid var(--line);border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.25);z-index:40}
  .fp-notif-panel.show{display:block}
  .fp-notif-panel-header{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 14px;border-bottom:1px solid var(--line)}
  .fp-notif-panel-titulo{font-weight:800;font-size:.86rem}
  .fp-notif-marcar-todas{background:transparent;border:none;color:var(--accent);font-size:.74rem;font-weight:700;cursor:pointer;padding:4px}
  .fp-notif-item{display:flex;align-items:flex-start;gap:8px;padding:10px 14px;border-bottom:1px solid var(--line)}
  .fp-notif-item:last-child{border-bottom:none}
  .fp-notif-item-info{flex:1;min-width:0}
  .fp-notif-item-titulo{font-size:.84rem;font-weight:700;color:var(--text)}
  .fp-notif-item-hora{font-size:.72rem;color:var(--faint);margin-top:2px}
  .fp-notif-item-ok{background:transparent;border:1.5px solid var(--line);color:var(--muted);width:26px;height:26px;border-radius:50%;cursor:pointer;flex:0 0 auto;font-size:.8rem;display:flex;align-items:center;justify-content:center}
  .fp-notif-item-ok:hover{border-color:var(--inc);color:var(--inc)}
  .fp-notif-vazio{padding:20px 14px;text-align:center;font-size:.82rem;color:var(--faint)}

  /* Popup que aparece quando chega uma notificação NOVA (desde que a aba foi aberta — mesmo
     princípio já documentado no alerta sonoro do sistema principal: não reabre sozinho pra
     histórico não lido já existente ao carregar a página, só pro que chega depois). Some
     sozinho depois de usuarios.fp_notif_tempo_exibicao segundos (configurável). */
  .fp-notif-toast-wrap{position:fixed;top:16px;right:16px;z-index:60;display:flex;flex-direction:column;gap:8px;max-width:calc(100vw - 32px)}
  .fp-notif-toast{display:flex;align-items:flex-start;gap:10px;background:var(--surf);border:1.5px solid var(--accentLine);border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.3);padding:12px 14px;width:300px;max-width:100%;animation:fpToastIn .2s ease-out}
  .fp-notif-toast-icone{color:var(--accent);flex:0 0 auto;font-size:1.1rem;margin-top:1px}
  .fp-notif-toast-texto{flex:1;min-width:0}
  .fp-notif-toast-titulo{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.03em;color:var(--accent);margin-bottom:2px}
  .fp-notif-toast-evento{font-size:.86rem;font-weight:700;color:var(--text)}
  .fp-notif-toast-close{background:transparent;border:none;color:var(--faint);cursor:pointer;font-size:1.1rem;line-height:1;flex:0 0 auto}
  @keyframes fpToastIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

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

  /* ── Fase 2: cabeçalho da tela principal (saudação + seletor de mês) ────────────────── */
  .fp-page-header{display:flex;flex-direction:column;gap:12px;margin-bottom:16px}
  @media (min-width:640px){ .fp-page-header{flex-direction:row;align-items:center;justify-content:space-between} }
  .fp-greeting{font-size:1.3rem;margin:0 0 2px;font-weight:800}
  .fp-month-nav{display:flex;align-items:center;gap:10px;background:var(--surf);border:1px solid var(--line);border-radius:12px;padding:6px 10px;align-self:flex-start}
  .fp-month-btn{width:32px;height:32px;border-radius:8px;border:none;background:transparent;color:var(--text);display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:1rem}
  .fp-month-btn:hover{background:var(--surf2)}
  .fp-month-label{min-width:120px;text-align:center;font-size:.88rem;font-weight:700}

  /* ── Agenda (financeiro_pessoal/calendario.php) — grade mensal construída à mão em PHP
     (mesma convenção já usada na Agenda do FixaOS pra grade de mês, ver CLAUDE.md), sem lib
     de calendário nenhuma. Cada dia mostra o título do primeiro evento (truncado numa linha)
     direto no quadradinho, não só um indicador — pedido do usuário pra não precisar clicar no
     dia só pra saber o que tem nele. Sem aspect-ratio:1 (removido de propósito): a altura da
     linha passa a seguir o conteúdo (CSS Grid já auto-ajusta a altura da linha pelo maior
     item), senão o texto esmagaria num quadrado fixo pequeno no mobile. */
  .fp-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
  .fp-cal-weekday{text-align:center;font-size:.68rem;text-transform:uppercase;letter-spacing:.03em;color:var(--faint);font-weight:700;padding-bottom:4px}
  .fp-cal-day{min-height:58px;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-start;gap:2px;padding:6px 5px;border-radius:12px;border:1.5px solid var(--line);background:var(--surf2);cursor:pointer;font-family:'Baloo 2',sans-serif;transition:border-color .15s,background .15s;overflow:hidden}
  .fp-cal-day:hover{border-color:var(--accent)}
  .fp-cal-day.selected{border-color:var(--accent);background:var(--accentSoft)}
  .fp-cal-day.feriado:not(.selected){background:var(--warnSoft);border-color:var(--warnSoft)}
  .fp-cal-day.feriado .fp-cal-day-num{color:var(--warn)}
  .fp-cal-day.hoje .fp-cal-day-num{color:var(--accent)}
  .fp-cal-day.vazio{visibility:hidden;cursor:default}
  .fp-cal-day-num{font-size:.8rem;font-weight:700;color:var(--text);flex:0 0 auto}
  .fp-cal-day-feriado-marca{color:var(--warn);font-size:.55rem;margin-left:3px;vertical-align:text-top}
  .fp-cal-day-eventos{display:flex;flex-direction:column;gap:1px;width:100%;min-width:0}
  .fp-cal-day-titulo{font-size:.6rem;font-weight:700;color:var(--text);width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.25}
  .fp-cal-day-mais{font-size:.52rem;font-weight:700;color:var(--faint)}
  .fp-cal-day-feriado-nome{font-size:.58rem;font-weight:700;color:var(--warn);width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.25}

  /* Linha de evento no painel do dia selecionado — mesma linguagem visual de .fp-item-row
     (card arredondado, fundo surf2), só que sem o círculo de status (não há "pago/não pago"
     num evento de agenda, só título + hora). */
  .fp-evento-row{display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--surf2);border:1px solid var(--line);border-radius:14px}
  .fp-evento-row:hover{border-color:var(--accentLine)}
  .fp-evento-row-info{flex:1;min-width:0}
  .fp-evento-titulo{font-weight:700;font-size:.92rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .fp-evento-hora{font-size:.76rem;color:var(--faint);margin-top:2px}

  /* ── Card de Saldo (KPI em destaque) ──────────────────────────────────────────────────── */
  .fp-card-saldo{border-color:var(--accentLine)}
  .fp-saldo-num{font-weight:800;font-size:1.9rem}
  @media (min-width:560px){ .fp-saldo-num{font-size:2.2rem} }
  .fp-bar-track{height:6px;border-radius:3px;background:var(--line);overflow:hidden}
  .fp-bar-fill{height:100%;background:var(--exp);border-radius:3px;transition:width .3s}

  /* ── Badges / chips de status, reaproveitados em vários lugares ──────────────────────── */
  .fp-badge{display:inline-flex;align-items:center;font-size:.64rem;font-weight:800;letter-spacing:.02em;text-transform:uppercase;padding:2px 8px;border-radius:999px}
  .fp-badge-inc{background:var(--incSoft);color:var(--inc)}
  .fp-badge-exp{background:var(--expSoft);color:var(--exp)}
  .fp-badge-debt{background:var(--debtSoft);color:var(--debt)}
  .fp-badge-accent{background:var(--accentSoft);color:var(--accent)}
  .fp-chip{display:inline-flex;align-items:center;font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:999px;white-space:nowrap;flex:0 0 auto}
  .fp-chip-inc{background:var(--incSoft);color:var(--inc)}
  .fp-chip-exp{background:var(--expSoft);color:var(--exp)}
  .fp-chip-warn{background:var(--warnSoft);color:var(--warn)}
  .fp-chip-danger{background:var(--dangerSoft);color:var(--danger)}
  .fp-chip-muted{background:var(--surf2);color:var(--faint)}

  /* Chip de categoria clicável (card colapsado de cada lançamento) — alternativa ao <select>
     nativo, cujo popup de opções é renderizado pelo sistema operacional e não segue o tema
     escuro do site (realce azul de fábrica, fundo claro). */
  .fp-cat-chip{display:inline-flex;align-items:center;gap:6px;font-family:'Baloo 2',sans-serif;font-size:.78rem;font-weight:700;padding:6px 12px;border-radius:999px;border:1.5px solid var(--line);background:var(--surf2);color:var(--muted);cursor:pointer}
  .fp-cat-chip:hover{border-color:var(--accent)}
  .fp-cat-chip.active{border-color:var(--accent);background:var(--accentSoft);color:var(--text)}
  .fp-cat-chip-dot{width:8px;height:8px;border-radius:50%;flex:0 0 auto}
  /* Lápis de editar dentro do chip (nome/cor da categoria) — <button> real aninhado no <span>
     do chip (não um <button> dentro de outro <button>, inválido em HTML), com stopPropagation
     no clique pra não disparar a seleção da categoria ao mesmo tempo. */
  .fp-cat-chip-edit{border:none;background:transparent;padding:0;margin-left:2px;font-size:.72rem;line-height:1;color:inherit;opacity:.6;cursor:pointer}
  .fp-cat-chip-edit:hover{opacity:1;color:#3B82F6}

  .fp-btn-sm{padding:8px 12px;font-size:.82rem;min-height:38px}

  /* fp-form-scan-row/fp-scan-cta* removidas — formulário de lançamento e "Escanear conta"
     viraram modal + botões compactos (#modalLancamento, .fp-acoes-rapidas), a pedido do
     usuário, pra desafogar o topo da página. Mais tarde, "Adicionar lançamento"/"Escanear
     conta" e a seção "Contas e débitos" saíram de vez da tela principal (pedido do usuário) —
     .fp-lanc-col (lista de Lançamentos) ficou sozinha, sem mais o layout de 2 colunas que
     existia aqui (.fp-main-cols/.fp-contas-col, removidas junto do HTML que as usava). */
  .fp-section-titulo{font-size:1.02rem;margin:0 0 2px;font-weight:800}

  /* ── Lista recolhível (card de "Contas da casa"/"Débitos e parcelas") ────────────────── */
  .fp-lista-card{overflow:hidden;margin-bottom:14px}
  .fp-lista-header-wrap{display:flex;align-items:stretch}
  .fp-lista-header{flex:1;min-width:0;display:flex;align-items:center;gap:12px;padding:16px;background:transparent;border:none;color:var(--text);cursor:pointer;text-align:left;font-family:'Baloo 2',sans-serif;min-height:44px}
  .fp-lista-header:hover{background:var(--surf2)}
  .fp-lista-header:focus-visible{outline:2px solid var(--accent);outline-offset:-2px}
  .fp-lista-header > svg:first-child{font-size:1.15rem;color:var(--muted);flex:0 0 auto}
  .fp-lista-header-texto{flex:1;min-width:0}
  .fp-lista-nome{font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .fp-lista-header-valor{text-align:right;flex:0 0 auto}
  .fp-lista-chevron{flex:0 0 auto;transition:transform .2s;color:var(--muted)}
  .fp-lista-excluir{width:44px;flex:0 0 auto;background:transparent;border:none;border-left:1px solid var(--line);color:var(--muted);cursor:pointer;font-size:.95rem}
  .fp-lista-excluir:hover{color:var(--danger);background:var(--dangerSoft)}
  .fp-lista-corpo{padding:6px 14px 14px;display:flex;flex-direction:column;gap:8px}

  /* Cada LANÇAMENTO (não um grupo por dia) é quem colapsa — pedido do usuário, corrigindo o
     entendimento errado da rodada anterior ("cada linha vai ser um colapse e dentro ter o
     novo comando que vamos criar"): a linha de sempre (dot/descrição/categoria/valor/editar/
     excluir) vira o cabeçalho clicável de um card; o corpo abaixo fica vazio por enquanto,
     reservado pro comando novo que ainda vai ser definido. */
  .fp-lanc-card{background:var(--surf);border:1px solid var(--line);border-radius:16px;overflow:hidden;margin-bottom:10px}
  .fp-lanc-header{display:flex;align-items:center;gap:12px;padding:14px 16px;cursor:pointer}
  .fp-lanc-header:hover{background:var(--surf2)}
  .fp-lanc-chevron{flex:0 0 auto;transition:transform .2s;color:var(--muted);display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px}
  .fp-lanc-corpo{padding:0 16px 14px;display:none}
  .fp-lanc-corpo.show{display:block}

  /* Mesma linguagem visual do card de Lançamentos (.fp-card na lista da direita) — cada item
     vira seu próprio card arredondado, com borda esquerda colorida por tipo (débito/despesa),
     em vez de uma linha solta dentro da lista, pra ficar consistente entre as duas colunas. */
  .fp-item-row{display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--surf2);border:1px solid var(--line);border-radius:14px}
  .fp-item-row:hover{border-color:var(--accentLine)}
  /* 38px — mesmo tamanho já usado pro alvo de toque reduzido do filtro lateral (.fp-filtro-btn
     abaixo de 360px), perto o bastante do mínimo de 44px sem desenhar um círculo gigante. */
  .fp-item-circle{width:38px;height:38px;border-radius:50%;border:2px solid var(--line);background:transparent;display:flex;align-items:center;justify-content:center;color:var(--incInk);cursor:pointer;flex:0 0 auto;font-size:.9rem}
  .fp-item-circle.pago{background:var(--inc);border-color:var(--inc)}
  .fp-item-texto{flex:1;min-width:0}
  .fp-item-nome{font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .fp-item-nome.pago{text-decoration:line-through;color:var(--muted)}
  .fp-item-valor{font-weight:700;font-size:.88rem;flex:0 0 auto}
  .fp-item-del{background:transparent;border:none;color:var(--faint);cursor:pointer;font-size:1.05rem;flex:0 0 auto;min-width:36px;min-height:36px}
  .fp-item-del:hover{color:var(--danger)}

  .fp-lista-rodape{display:flex;gap:8px;flex-wrap:wrap;padding:10px 16px 2px}
  .fp-item-form{display:flex;flex-direction:column;gap:8px;padding:10px 16px 14px;background:var(--surf2)}

  /* ── Modal próprio (CSS puro, sem Bootstrap JS — esta área não carrega o bundle) ─────── */
  .fp-modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:50;align-items:center;justify-content:center;padding:16px}
  .fp-modal-backdrop.show{display:flex}
  .fp-modal{background:var(--surf);border:1px solid var(--line);border-radius:18px;max-width:440px;width:100%;max-height:92vh;overflow-y:auto;padding:20px}
  .fp-modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px}
  .fp-modal-close{background:transparent;border:none;color:var(--muted);font-size:1.4rem;cursor:pointer;width:36px;height:36px;border-radius:8px;flex:0 0 auto}
  .fp-modal-close:hover{background:var(--surf2)}

  /* Dashboard: 1 coluna empilhada no mobile (mesmo visual de sempre); no desktop os 4 KPIs
     viram uma linha e o gráfico ganha mais espaço que "Por categoria" ao lado — usa a largura
     cheia que o .fp-wrap-full liberou, em vez de ficar tudo espremido numa coluna central. */
  .fp-dash-kpis{display:flex;flex-direction:column;gap:16px;margin-bottom:16px}
  .fp-dash-main{display:flex;flex-direction:column;gap:16px;margin-bottom:20px}
  .fp-dash-chart{height:140px}
  @media (min-width:992px){
    /* auto-fit (não repeat(4,1fr) fixo) — o card novo "Guardado em caixinhas" soma 5 KPIs;
       com largura mínima de 200px eles reflow sozinhos (5 numa linha se couber, senão quebra
       igual pros 4 de sempre), sem precisar de breakpoint extra só pra essa 5ª coluna. */
    .fp-dash-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
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
  /* "Escanear conta" — pedido do usuário pra melhorar o visual do ghost genérico que tinha
     antes (sumia contra o fundo escuro). Tom laranja translúcido, mesma paleta de --accent
     (não uma cor nova) — reforça que é uma ação de câmera/scan sem competir com o botão
     sólido de "+ Adicionar" ao lado. */
  .fp-btn-scan{background:var(--accentSoft);color:var(--accent);border:1.5px solid var(--accentLine);transition:background .15s,border-color .15s}
  .fp-btn-scan:hover{background:var(--accentLine);border-color:var(--accent)}
  .fp-btn-scan:active{transform:translateY(1px)}
  /* "Contas recorrentes" — pedido do usuário ("em azul"), mesmo padrão visual de
     .fp-btn-scan (fundo translúcido + borda + hover), só com a cor azul nova (--blue), pra
     não competir com o laranja de "Escanear conta" nem com o sólido de "+ Adicionar" ao lado. */
  .fp-btn-recorrente{background:var(--blueSoft);color:var(--blue);border:1.5px solid var(--blueLine);transition:background .15s,border-color .15s}
  .fp-btn-recorrente:hover{background:var(--blueLine);border-color:var(--blue)}
  .fp-btn-recorrente:active{transform:translateY(1px)}
  /* Variantes semânticas — o botão de SALVAR acompanha a cor do tipo escolhido (sólido),
     reforçando antes de salvar que aquele lançamento é despesa ou receita. Continuam sólidas
     de propósito, sem borda extra — são usadas só pelo botão de ação primária
     (fpBtnSalvar/recBtnSalvar), não pelo toggle Gasto/Entrada (ver .fp-btn-despesa-ativo/
     .fp-btn-receita-ativo logo abaixo, usadas só pelo toggle). */
  .fp-btn-despesa{background:var(--exp);color:var(--expInk)}
  .fp-btn-despesa:disabled{opacity:.55;cursor:default}
  .fp-btn-receita{background:var(--inc);color:var(--incInk)}
  .fp-btn-receita:disabled{opacity:.55;cursor:default}
  /* Toggle Gasto/Entrada: volta ao padrão original (ghost quando não selecionado) — pedido do
     usuário depois de testar o "Entrada sempre verde sólido". Nova regra pro lado SELECIONADO:
     em vez do preenchimento sólido de .fp-btn-despesa/.fp-btn-receita (que ficaria com borda
     invisível por ser a mesma cor do fundo), o toggle usa fundo claro (--expSoft/--incSoft,
     mesmo tom translúcido de .fp-chip-exp/.fp-chip-inc) + borda 2px na cor cheia — a borda vira
     o destaque principal, visível de verdade contra o fundo claro. */
  .fp-btn-despesa-ativo{background:var(--expSoft);color:var(--exp);border:2px solid var(--exp)}
  .fp-btn-receita-ativo{background:var(--incSoft);color:var(--inc);border:2px solid var(--inc)}

  /* Filtro lateral da listagem de lançamentos do mês (Todos/Entradas/Saídas) — coluna estreita
     de botões ao lado da lista, não embaixo, mesmo em mobile (3 botões empilhados ocupam
     pouca largura mesmo em 320px, e "na lateral" foi pedido explícito do usuário). */
  /* Ícone só, sem rótulo — mesma linguagem visual do rail principal (.fp-sidebar-nav),
     "menu na lateral com ícone" pedido pelo usuário. Rótulo vira title/aria-label (mantém
     acessibilidade) em vez de texto visível — a coluna fica bem mais estreita, sobrando
     largura de verdade pra lista de lançamentos ao lado. */
  .fp-filtros{display:flex;flex-direction:column;gap:6px;flex:0 0 auto}
  .fp-filtro-btn{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;border:1.5px solid var(--line);background:var(--surf);color:var(--muted);font-size:1.1rem;cursor:pointer}
  .fp-filtro-btn span{display:none}
  /* Borda e ícone já na cor da própria ação (verde/vermelho), mesmo parado — sem isso os dois
     botões ficam idênticos (borda cinza neutra) até alguém ativar um, sem nenhuma pista visual
     de qual seta é "Entradas" e qual é "Saídas". */
  .fp-filtro-btn[data-filtro="receita"]{border-color:var(--inc);color:var(--inc)}
  .fp-filtro-btn[data-filtro="despesa"]{border-color:var(--exp);color:var(--exp)}
  /* Ativo preenche com a mesma cor (verde/vermelho), avisando visualmente o que a lista vai
     mostrar antes mesmo de ler o rótulo. */
  .fp-filtro-btn.active[data-filtro="receita"]{background:var(--inc);border-color:var(--inc);color:var(--incInk)}
  .fp-filtro-btn.active[data-filtro="despesa"]{background:var(--exp);border-color:var(--exp);color:var(--expInk)}

  /* No mobile, os cards (KPIs, lançamentos, listas de contas) ganham menos respiro interno e
     a página ganha menos respiro lateral — em telas estreitas, o espaço "perdido" em padding/
     borda é proporcionalmente grande; reduzir os dois deixa o conteúdo de verdade (valor,
     nome, chips) ocupar mais da largura real da tela, pedido explícito do usuário. */
  @media (max-width:480px){
    .fp-wrap{padding-left:12px;padding-right:12px}
    .fp-card{padding:13px}
    /* .fp-lista-card gerencia o próprio padding (zera o do .fp-card genérico acima e controla
       cabeçalho/itens/rodapé à parte) — reduz os mesmos pontos pra ficar consistente. */
    .fp-lista-header{padding:13px}
    .fp-item-row{padding:9px 13px}
    .fp-lista-rodape{padding:8px 13px 2px}
    .fp-item-form{padding:8px 13px 12px}
    .fp-lista-excluir{width:40px}
  }

  /* Barra inferior — só no mobile (a sidebar acima cobre telas largas). */
  .fp-bottomnav{display:flex;position:fixed;left:0;right:0;bottom:0;background:var(--side);border-top:1px solid var(--line);padding:8px 8px calc(8px + env(safe-area-inset-bottom,0px));z-index:10}
  @media (min-width:768px){ .fp-bottomnav{display:none} }
  .fp-bottomnav a{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 0;text-decoration:none;color:var(--muted);font-size:.68rem;font-weight:700}
  .fp-bottomnav a svg{font-size:1.2rem}
  .fp-bottomnav a.active{color:var(--accent)}

  /* ── Rodapé — mesma identidade "fixa" (não a azul/teal do resto do FixaOS), pedido do
     usuário pra deixar claro que é produto da FixaOS mesmo sendo uma área isolada visualmente.
     Dentro de .fp-wrap (não um <footer> solto por fora dela) de propósito: herda a mesma
     largura/padding lateral do conteúdo da página, e o padding-bottom de 90px que .fp-wrap já
     reserva pro .fp-bottomnav fixo no mobile continua valendo depois dele, sem precisar de
     mais um ajuste de espaçamento à parte. */
  .fp-footer{margin-top:28px;padding-top:16px;border-top:1px solid var(--line);display:flex;flex-direction:column;align-items:center;gap:4px;text-align:center}
  .fp-footer-brand{display:flex;align-items:center;gap:6px;font-size:.82rem;color:var(--muted)}
  .fp-footer-brand .dot{width:5px;height:5px;border-radius:50%;background:var(--accent);flex:0 0 auto}
  .fp-footer-brand a{color:var(--text);font-weight:700;text-decoration:none}
  .fp-footer-brand a:hover{color:var(--accent)}
  .fp-footer-copy{font-size:.72rem;color:var(--faint)}
</style>
</head>
<body>
<?php
  $uriAtual = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
  // "Lançamentos" e "Resumo" eram duas páginas separadas, viraram uma só
  // (/financeiro-pessoal) — pedido do usuário ("o dashboard vai ficar no lugar dela"). Um
  // ícone só na barra lateral agora, não mais dois apontando pro mesmo lugar.
  $ativoResumo = $uriAtual === '/financeiro-pessoal' ? 'active' : '';
  $ativoLancamentos = $uriAtual === '/financeiro-pessoal/lancamentos' ? 'active' : '';
  $ativoCalendario = $uriAtual === '/financeiro-pessoal/calendario' ? 'active' : '';
  $ativoContas = $uriAtual === '/financeiro-pessoal/contas' ? 'active' : '';
  $ativoCaixinhas = $uriAtual === '/financeiro-pessoal/caixinhas' ? 'active' : '';
  $ativoCategorias = $uriAtual === '/financeiro-pessoal/categorias' ? 'active' : '';
  $ativoConfiguracoes = $uriAtual === '/financeiro-pessoal/configuracoes' ? 'active' : '';
  // Pedido do usuário: o rosto dele no lugar do pontinho decorativo da marca — só quando já
  // configurou uma foto em Configurações (ver financeiro_pessoal/configuracoes.php); sem
  // avatar nenhum, continua exatamente como sempre foi (o pontinho).
  $avatarUrlSidebar = financeiro_pessoal_avatar_url($_SESSION['usuario']['avatar'] ?? null);
  // Fixa Fase 1 (PF/PJ) — $perfil/$perfis só existem nas páginas que já resolveram o acesso
  // liberado (ver FinanceiroPessoalController::__construct()); numa tela bloqueada (!$liberado)
  // nenhum dos dois é passado, por isso o `?? []`/`?? null` em tudo que segue.
  $perfilAtivoCor = $perfil['cor'] ?? null;
  $perfisNaoArquivados = array_values(array_filter($perfis ?? [], fn($p) => empty($p['arquivado'])));
?>
<?php if ($perfilAtivoCor): ?>
<div class="fp-perfil-stripe" style="background:<?= e($perfilAtivoCor) ?>" aria-hidden="true"></div>
<?php endif; ?>
<div class="fp-shell">

  <aside class="fp-sidebar">
    <div class="fp-sidebar-brand" title="Carteira Fixa">
      <?php if ($avatarUrlSidebar): ?>
      <!-- Se o arquivo da foto não carregar (ex.: avatar configurado antes que o arquivo
           sumisse do storage), cai pro ponto laranja de sempre em vez de deixar o navegador
           desenhar o ícone de "imagem quebrada" por cima do quadrado da marca. -->
      <img src="<?= e($avatarUrlSidebar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span class="dot" aria-hidden="true" style="display:none"></span>
      <?php else: ?>
      <span class="dot" aria-hidden="true"></span>
      <?php endif; ?>
    </div>
    <nav class="fp-sidebar-nav">
      <a href="<?= url('/financeiro-pessoal') ?>" class="<?= $ativoResumo ?>" title="Resumo" aria-label="Resumo"><?= fp_icone('bar-chart-fill') ?></a>
      <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" class="<?= $ativoLancamentos ?>" title="Lançamentos" aria-label="Lançamentos"><?= fp_icone('list-ul') ?></a>
      <a href="<?= url('/financeiro-pessoal/calendario') ?>" class="<?= $ativoCalendario ?>" title="Calendário" aria-label="Calendário"><?= fp_icone('calendar3') ?></a>
      <a href="<?= url('/financeiro-pessoal/contas') ?>" class="<?= $ativoContas ?>" title="Contas" aria-label="Contas"><?= fp_icone('wallet2') ?></a>
      <a href="<?= url('/financeiro-pessoal/caixinhas') ?>" class="<?= $ativoCaixinhas ?>" title="Caixinhas" aria-label="Caixinhas"><?= fp_icone('piggy-bank-fill') ?></a>
      <a href="<?= url('/financeiro-pessoal/categorias') ?>" class="<?= $ativoCategorias ?>" title="Categorias" aria-label="Categorias"><?= fp_icone('tag-fill') ?></a>
      <a href="<?= url('/financeiro-pessoal/configuracoes') ?>" class="<?= $ativoConfiguracoes ?>" title="Configurações" aria-label="Configurações"><?= fp_icone('sliders') ?></a>
    </nav>
    <div class="fp-sidebar-bottom">
      <a href="<?= url('/dashboard') ?>" title="Voltar pro FixaOS"><?= fp_icone('box-arrow-left') ?></a>
    </div>
  </aside>

  <?php $nomeUsuario = \App\Core\Auth::user()['nome'] ?? 'Você'; ?>
  <div class="fp-main">
    <div class="fp-topbar">
      <div class="fp-topbar-left">
        <div class="brand"><b>Carteira Fixa</b><span aria-hidden="true"></span></div>
        <?php if (count($perfisNaoArquivados) > 1): ?>
        <!-- Seletor de perfil — só aparece com mais de 1 perfil ativo (pedido explícito: quem
             só tem o "Pessoal" nunca vê isso). Form simples (<select onchange=submit()>, não
             AJAX) porque trocar de perfil muda praticamente tudo que a página mostra — mais
             simples recarregar do que reconciliar tudo em JS. -->
        <form method="POST" action="<?= url('/financeiro-pessoal/perfil-ativo') ?>" class="fp-perfil-seletor">
          <?= csrf_field() ?>
          <span class="fp-perfil-dot" style="background:<?= e($perfilAtivoCor ?? '#8C7CFF') ?>" aria-hidden="true"></span>
          <select name="perfil_id" class="fp-perfil-select" onchange="this.form.submit()" aria-label="Trocar de perfil">
            <?php foreach ($perfisNaoArquivados as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) ($perfil['id'] ?? 0) ? 'selected' : '' ?>>
              <?= $p['tipo'] === 'pj' ? '🏢 ' : '👤 ' ?><?= e($p['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </form>
        <?php endif; ?>
        <div class="fp-clock" id="fpClock"></div>
      </div>
      <div class="fp-topbar-right">
        <button type="button" class="fp-theme-btn" id="fpThemeToggle" aria-label="Alternar tema claro/escuro">
          <span id="fpThemeIcon" aria-hidden="true"><?= fp_icone('moon-stars') ?></span>
        </button>
        <?php if (!empty($liberado)): ?>
        <!-- Sino de notificação (eventos da Agenda chegando no horário) — só existe pra quem
             tem o módulo liberado, mesmo critério de tudo mais aqui; sem isso o polling só
             bateria em guard()/403 à toa. -->
        <div class="fp-notif-wrap">
          <button type="button" class="fp-notif-btn" id="fpNotifBtn" aria-haspopup="true" aria-expanded="false" aria-label="Notificações">
            <?= fp_icone('bell-fill') ?>
            <span class="fp-notif-badge" id="fpNotifBadge" style="display:none">0</span>
          </button>
          <div class="fp-notif-panel" id="fpNotifPanel">
            <div class="fp-notif-panel-header">
              <span class="fp-notif-panel-titulo">Notificações</span>
              <button type="button" class="fp-notif-marcar-todas" id="fpNotifMarcarTodas">Marcar todas como lidas</button>
            </div>
            <div id="fpNotifLista"></div>
          </div>
        </div>
        <?php endif; ?>
        <!-- Mesma foto da sidebar (ver .fp-sidebar-brand acima) — antes a topbar sempre
             desenhava só as iniciais, nunca a foto de verdade, inconsistente com o que a
             sidebar já mostrava. Mesmo fallback pra imagem quebrada. -->
        <div class="fp-avatar" title="<?= e($nomeUsuario) ?>">
          <?php if ($avatarUrlSidebar): ?>
          <img src="<?= e($avatarUrlSidebar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center"><?= e(avatar_iniciais($nomeUsuario)) ?></span>
          <?php else: ?>
          <?= e(avatar_iniciais($nomeUsuario)) ?>
          <?php endif; ?>
        </div>
        <a href="<?= url('/dashboard') ?>" class="fp-voltar">← Voltar pro FixaOS</a>
      </div>
    </div>
    <div class="fp-wrap<?= !empty($wrapFull) ? ' fp-wrap-full' : '' ?>">
    <?php ($content)(); ?>
    <footer class="fp-footer">
      <div class="fp-footer-brand">
        <span class="dot" aria-hidden="true"></span>
        <span>Carteira Fixa é um produto da <a href="<?= url('/') ?>" target="_blank" rel="noopener">FixaOS</a></span>
      </div>
      <div class="fp-footer-copy">© <?= date('Y') ?> fixaos.com.br — Gestão para Assistências Técnicas</div>
    </footer>
    </div>
  </div>

</div>

<?php if (!empty($liberado)): ?>
<!-- Toast que aparece quando chega uma notificação NOVA (evento da Agenda chegando no
     horário) — some sozinho depois de N segundos (usuarios.fp_notif_tempo_exibicao). -->
<div class="fp-notif-toast-wrap" id="fpNotifToastWrap" aria-live="polite"></div>

<!-- Alerta de lançamento vencido sem pagar — modal global (aparece em qualquer página do
     módulo), diferente do sino (que é só um painel suspenso, não força atenção). Sem botão ×
     no cabeçalho de propósito — só o "Agora não" no rodapé dispensa, pra deixar claro que é
     uma decisão, não um clique sem querer; não fecha clicando no fundo escuro (nenhum
     onclick no .fp-modal-backdrop em si). Reaparece sozinho de 3 em 3h enquanto o lançamento
     continuar sem data_pagamento (throttle no servidor, ver alertasVencidosAjax()). -->
<div class="fp-modal-backdrop" id="fpModalVencidos">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>⚠️ Lançamento(s) vencido(s)</strong>
    </div>
    <p class="fp-faint" style="font-size:.85rem;margin:0 0 12px">
      Passaram do vencimento e ainda não foram marcados como pagos.
    </p>
    <div id="fpVencidosLista" style="display:flex;flex-direction:column;gap:8px;max-height:360px;overflow-y:auto"></div>
    <button type="button" class="fp-btn fp-btn-ghost" id="fpVencidosFechar" style="margin-top:14px;align-self:flex-start">Agora não</button>
  </div>
</div>
<?php endif; ?>

<nav class="fp-bottomnav">
  <a href="<?= url('/financeiro-pessoal') ?>" class="<?= $ativoResumo ?>"><?= fp_icone('bar-chart-fill') ?>Resumo</a>
  <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" class="<?= $ativoLancamentos ?>"><?= fp_icone('list-ul') ?>Lançamentos</a>
  <a href="<?= url('/financeiro-pessoal/calendario') ?>" class="<?= $ativoCalendario ?>"><?= fp_icone('calendar3') ?>Calendário</a>
  <a href="<?= url('/financeiro-pessoal/contas') ?>" class="<?= $ativoContas ?>"><?= fp_icone('wallet2') ?>Contas</a>
  <a href="<?= url('/financeiro-pessoal/categorias') ?>" class="<?= $ativoCategorias ?>"><?= fp_icone('tag-fill') ?>Categorias</a>
  <a href="<?= url('/financeiro-pessoal/configuracoes') ?>" class="<?= $ativoConfiguracoes ?>"><?= fp_icone('sliders') ?>Config.</a>
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
    var dia = dias[d.getDay()];
    dia = dia.charAt(0).toUpperCase() + dia.slice(1);
    el.textContent = dia + ', ' + d.getDate() + ' de ' + meses[d.getMonth()] + ' · ' + hh + ':' + mm;
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
  // Ícones gerados pelo mesmo fp_icone() do PHP (sem CDN) — innerHTML, não className,
  // já que agora é SVG inline, não mais uma classe de fonte de ícone.
  var SVG_MOON = <?= json_encode(fp_icone('moon-stars')) ?>;
  var SVG_SUN = <?= json_encode(fp_icone('sun')) ?>;
  function atualizarIcone() {
    var escuro = document.documentElement.dataset.theme === 'dark';
    if (icon) icon.innerHTML = escuro ? SVG_SUN : SVG_MOON;
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

<?php if (!empty($liberado)): ?>
// ── Sino de notificação (eventos da Agenda chegando no horário) — pedido do usuário. Poll a
// cada 30s em TODA página do módulo (não só na Agenda), mesmo espírito do polling de
// notificações que já roda em toda página logada do sistema principal. Beep sintetizado via
// Web Audio API (mesma técnica de tocarBeepAlerta() em layouts/main.php, reescrita aqui
// porque esta área é isolada do resto do FixaOS, sem script compartilhado entre os dois).
(function () {
  var CSRF_TOKEN = '<?= csrf_token() ?>';
  var POLL_URL = '<?= url('/api/financeiro-pessoal/notificacoes') ?>';
  var LER_URL = '<?= url('/financeiro-pessoal/notificacoes') ?>';
  var SOM_ATIVO = <?= json_encode(!empty($_SESSION['usuario']['fp_notif_som'] ?? 1)) ?>;
  var TEMPO_EXIBICAO_MS = <?= (int) ($_SESSION['usuario']['fp_notif_tempo_exibicao'] ?? 6) * 1000 ?>;

  var btn = document.getElementById('fpNotifBtn');
  var badge = document.getElementById('fpNotifBadge');
  var painel = document.getElementById('fpNotifPanel');
  var lista = document.getElementById('fpNotifLista');
  var marcarTodasBtn = document.getElementById('fpNotifMarcarTodas');
  var toastWrap = document.getElementById('fpNotifToastWrap');
  if (!btn) return;

  // null = ainda não fez a 1ª leitura (não beepa/não mostra toast de histórico já pendente ao
  // carregar a página) — mesmo princípio já documentado no alerta sonoro do sistema principal.
  var idsConhecidos = null;

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  function fmtRelativo(dataHora) {
    var d = new Date(String(dataHora).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    var diffMin = Math.max(0, Math.round((Date.now() - d.getTime()) / 60000));
    if (diffMin < 1) return 'agora';
    if (diffMin < 60) return 'há ' + diffMin + ' min';
    var diffH = Math.round(diffMin / 60);
    if (diffH < 24) return 'há ' + diffH + (diffH === 1 ? ' hora' : ' horas');
    var diffD = Math.round(diffH / 24);
    return 'há ' + diffD + (diffD === 1 ? ' dia' : ' dias');
  }

  function tocarBeep() {
    if (!SOM_ATIVO) return;
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      if (ctx.state === 'suspended') ctx.resume();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.55);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.55);
      osc.onended = function () { ctx.close(); };
    } catch (e) {
      // Sem Web Audio, ou autoplay bloqueado por falta de interação ainda — sem fallback, o
      // toast/badge já avisa visualmente de qualquer forma.
    }
  }

  function mostrarToast(n) {
    if (!toastWrap) return;
    var el = document.createElement('div');
    el.className = 'fp-notif-toast';
    el.innerHTML =
      '<span class="fp-notif-toast-icone" aria-hidden="true">' + <?= json_encode(fp_icone('bell-fill')) ?> + '</span>' +
      '<div class="fp-notif-toast-texto">' +
        '<div class="fp-notif-toast-titulo">Evento chegou</div>' +
        '<div class="fp-notif-toast-evento">' + escapeHtml(n.titulo) + '</div>' +
      '</div>' +
      '<button type="button" class="fp-notif-toast-close" aria-label="Fechar aviso">×</button>';
    toastWrap.appendChild(el);
    var sumir = function () { if (el.parentNode) el.parentNode.removeChild(el); };
    el.querySelector('.fp-notif-toast-close').onclick = sumir;
    setTimeout(sumir, TEMPO_EXIBICAO_MS);
  }

  function renderPainel(notificacoes) {
    if (!notificacoes.length) {
      lista.innerHTML = '<div class="fp-notif-vazio">Nenhuma notificação pendente.</div>';
      return;
    }
    lista.innerHTML = notificacoes.map(function (n) {
      return '<div class="fp-notif-item">' +
        '<div class="fp-notif-item-info">' +
          '<div class="fp-notif-item-titulo">' + escapeHtml(n.titulo) + '</div>' +
          '<div class="fp-notif-item-hora">' + fmtRelativo(n.data_hora) + '</div>' +
        '</div>' +
        '<button type="button" class="fp-notif-item-ok" data-id="' + n.id + '" aria-label="Marcar como lida" title="Marcar como lida">✓</button>' +
      '</div>';
    }).join('');
    lista.querySelectorAll('.fp-notif-item-ok').forEach(function (b) {
      b.onclick = function () { marcarLida(b.dataset.id); };
    });
  }

  function marcarLida(id) {
    fetch(LER_URL + '/' + id + '/ler', { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } })
      .then(carregar);
  }

  if (marcarTodasBtn) {
    marcarTodasBtn.onclick = function () {
      fetch(LER_URL + '/ler-todas', { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } })
        .then(carregar);
    };
  }

  function carregar() {
    fetch(POLL_URL)
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        var notificacoes = j.notificacoes || [];
        var idsAtuais = notificacoes.map(function (n) { return n.id; });

        badge.textContent = String(notificacoes.length);
        badge.style.display = notificacoes.length ? 'flex' : 'none';
        renderPainel(notificacoes);

        if (idsConhecidos === null) {
          idsConhecidos = new Set(idsAtuais);
          return;
        }
        notificacoes.forEach(function (n) {
          if (!idsConhecidos.has(n.id)) {
            tocarBeep();
            mostrarToast(n);
            idsConhecidos.add(n.id);
          }
        });
      })
      .catch(function () { /* falha de rede num poll não precisa de aviso nenhum */ });
  }

  btn.addEventListener('click', function (ev) {
    ev.stopPropagation();
    var abrindo = !painel.classList.contains('show');
    painel.classList.toggle('show', abrindo);
    btn.setAttribute('aria-expanded', abrindo ? 'true' : 'false');
  });
  document.addEventListener('click', function (ev) {
    if (painel.classList.contains('show') && !painel.contains(ev.target) && ev.target !== btn) {
      painel.classList.remove('show');
      btn.setAttribute('aria-expanded', 'false');
    }
  });

  carregar();
  setInterval(carregar, 30000);
})();

// ── Alerta em modal: lançamento vencido sem pagar, repete de 3 em 3h (throttle no servidor,
// ver FinanceiroPessoalController::alertasVencidosAjax()) — poll próprio, independente do
// sino acima. Enquanto o modal já está aberto, o poll só é ignorado (não sobrescreve a lista
// no meio da leitura do usuário) — o servidor já marcou esses ids como "alertados agora", então
// um poll nessa janela tende a vir vazio mesmo; a próxima leitura de verdade só acontece depois
// que o usuário fechar e um novo ciclo de 3h se completar (ou um lançamento novo vencer).
(function () {
  var modal = document.getElementById('fpModalVencidos');
  if (!modal) return;
  var lista = document.getElementById('fpVencidosLista');
  var btnFechar = document.getElementById('fpVencidosFechar');
  var CSRF_TOKEN = '<?= csrf_token() ?>';
  var URL_VENCIDOS = '<?= url('/api/financeiro-pessoal/vencidos') ?>';
  var URL_MARCAR_PAGO = '<?= url('/financeiro-pessoal') ?>';

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }
  function fmtValor(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function diasVencido(iso) {
    var hoje = new Date(); hoje.setHours(0, 0, 0, 0);
    var venc = new Date(iso + 'T00:00:00');
    var dias = Math.round((hoje - venc) / 86400000);
    return dias <= 0 ? 'Venceu hoje' : 'Venceu há ' + dias + (dias === 1 ? ' dia' : ' dias');
  }

  function renderVencidos(vencidos) {
    lista.innerHTML = vencidos.map(function (v) {
      return '<div class="fp-card" style="padding:10px 12px;display:flex;align-items:center;gap:10px">' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-weight:700;font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(v.descricao) + '</div>' +
          '<div class="fp-faint" style="font-size:.78rem;margin-top:2px">' + diasVencido(v.vencimento) + ' · ' + fmtValor(v.valor) + '</div>' +
        '</div>' +
        '<button type="button" class="fp-btn fp-btn-primary fp-btn-sm fp-vencido-pagar" data-id="' + v.id + '">✅ Marcar pago</button>' +
      '</div>';
    }).join('');
    lista.querySelectorAll('.fp-vencido-pagar').forEach(function (btn) {
      btn.onclick = function () { marcarPagoRapido(btn); };
    });
  }

  // Resolução de 1 clique — paga hoje, valor cheio (o mesmo default que o modal "Marcar como
  // pago" da tela de Lançamentos já sugere). Pra uma data/valor diferente, o caminho continua
  // sendo abrir o lançamento em Lançamentos — esse alerta é só pro caso comum.
  function marcarPagoRapido(btn) {
    var id = btn.dataset.id;
    btn.disabled = true;
    btn.textContent = 'Marcando...';
    fetch(URL_MARCAR_PAGO + '/' + id + '/marcar-pago', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CSRF_TOKEN },
      body: new URLSearchParams({ pago_em: new Date().toISOString().slice(0, 10) })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { btn.disabled = false; btn.textContent = 'Tentar de novo'; return; }
        // Insere valor/data de verdade — se a página atual tiver cards de KPI (Resumo,
        // Lançamentos), eles precisam refletir isso na hora, não só este alerta sumindo.
        window.location.reload();
      })
      .catch(function () { btn.disabled = false; btn.textContent = 'Tentar de novo'; });
  }

  btnFechar.onclick = function () { modal.classList.remove('show'); };

  function carregarVencidos() {
    if (modal.classList.contains('show')) return; // já mostrando — não sobrescreve no meio da leitura
    fetch(URL_VENCIDOS)
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok || !j.vencidos || !j.vencidos.length) return;
        renderVencidos(j.vencidos);
        modal.classList.add('show');
      })
      .catch(function () { /* falha de rede num poll não precisa de aviso nenhum */ });
  }

  carregarVencidos();
  setInterval(carregarVencidos, 60000);
})();
<?php endif; ?>
</script>
</body>
</html>
