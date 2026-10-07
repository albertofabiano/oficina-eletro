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

  /* ── Fase 2: cabeçalho da tela principal (saudação + seletor de mês) ────────────────── */
  .fp-page-header{display:flex;flex-direction:column;gap:12px;margin-bottom:16px}
  @media (min-width:640px){ .fp-page-header{flex-direction:row;align-items:center;justify-content:space-between} }
  .fp-greeting{font-size:1.3rem;margin:0 0 2px;font-weight:800}
  .fp-month-nav{display:flex;align-items:center;gap:10px;background:var(--surf);border:1px solid var(--line);border-radius:12px;padding:6px 10px;align-self:flex-start}
  .fp-month-btn{width:32px;height:32px;border-radius:8px;border:none;background:transparent;color:var(--text);display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:1rem}
  .fp-month-btn:hover{background:var(--surf2)}
  .fp-month-label{min-width:120px;text-align:center;font-size:.88rem;font-weight:700}

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
  /* "Escanear conta" — pedido do usuário pra melhorar o visual do ghost genérico que tinha
     antes (sumia contra o fundo escuro). Tom laranja translúcido, mesma paleta de --accent
     (não uma cor nova) — reforça que é uma ação de câmera/scan sem competir com o botão
     sólido de "+ Adicionar" ao lado. */
  .fp-btn-scan{background:var(--accentSoft);color:var(--accent);border:1.5px solid var(--accentLine);transition:background .15s,border-color .15s}
  .fp-btn-scan:hover{background:var(--accentLine);border-color:var(--accent)}
  .fp-btn-scan:active{transform:translateY(1px)}
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
  $ativoCategorias = $uriAtual === '/financeiro-pessoal/categorias' ? 'active' : '';
  $ativoConfiguracoes = $uriAtual === '/financeiro-pessoal/configuracoes' ? 'active' : '';
  // Pedido do usuário: o rosto dele no lugar do pontinho decorativo da marca — só quando já
  // configurou uma foto em Configurações (ver financeiro_pessoal/configuracoes.php); sem
  // avatar nenhum, continua exatamente como sempre foi (o pontinho).
  $avatarUrlSidebar = financeiro_pessoal_avatar_url($_SESSION['usuario']['avatar'] ?? null);
?>
<div class="fp-shell">

  <aside class="fp-sidebar">
    <div class="fp-sidebar-brand" title="Fixa">
      <?php if ($avatarUrlSidebar): ?>
      <img src="<?= e($avatarUrlSidebar) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px">
      <?php else: ?>
      <span class="dot" aria-hidden="true"></span>
      <?php endif; ?>
    </div>
    <nav class="fp-sidebar-nav">
      <a href="<?= url('/financeiro-pessoal') ?>" class="<?= $ativoResumo ?>" title="Resumo" aria-label="Resumo"><?= fp_icone('bar-chart-fill') ?></a>
      <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" class="<?= $ativoLancamentos ?>" title="Lançamentos" aria-label="Lançamentos"><?= fp_icone('list-ul') ?></a>
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
        <div class="brand"><b>Fixa</b><span aria-hidden="true"></span></div>
        <div class="fp-clock" id="fpClock"></div>
      </div>
      <div class="fp-topbar-right">
        <button type="button" class="fp-theme-btn" id="fpThemeToggle" aria-label="Alternar tema claro/escuro">
          <span id="fpThemeIcon" aria-hidden="true"><?= fp_icone('moon-stars') ?></span>
        </button>
        <div class="fp-avatar" title="<?= e($nomeUsuario) ?>"><?= e(avatar_iniciais($nomeUsuario)) ?></div>
        <a href="<?= url('/dashboard') ?>" class="fp-voltar">← Voltar pro FixaOS</a>
      </div>
    </div>
    <div class="fp-wrap<?= !empty($wrapFull) ? ' fp-wrap-full' : '' ?>">
    <?php ($content)(); ?>
    <footer class="fp-footer">
      <div class="fp-footer-brand">
        <span class="dot" aria-hidden="true"></span>
        <span>Fixa é um produto da <a href="<?= url('/') ?>" target="_blank" rel="noopener">FixaOS</a></span>
      </div>
      <div class="fp-footer-copy">© <?= date('Y') ?> fixaos.com.br — Gestão para Assistências Técnicas</div>
    </footer>
    </div>
  </div>

</div>

<nav class="fp-bottomnav">
  <a href="<?= url('/financeiro-pessoal') ?>" class="<?= $ativoResumo ?>"><?= fp_icone('bar-chart-fill') ?>Resumo</a>
  <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" class="<?= $ativoLancamentos ?>"><?= fp_icone('list-ul') ?>Lançamentos</a>
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
</script>
</body>
</html>
