<?php
/**
 * Editor de imagem (recorte/redimensionar/girar + exportar) — redesenho estilo editor
 * profissional (Fireworks), substituindo o visual Bootstrap+Cropper.js antigo. Tudo client-side
 * via <canvas>; a ferramenta nunca mais depende de lib externa de crop. Dois contextos possíveis
 * (ver ImagemController::editor() / Controller::layoutAtual()): standalone (layout main, com
 * sidebar/topbar) ou embutido num <iframe> da aba Configurações → Editor de Imagens (layout
 * painel.php, sem sidebar/topbar) — os dois carregam Bootstrap/app.css/tokens.css globais, por
 * isso TODO seletor deste arquivo vive sob `.ie` (classe do wrapper raiz), nunca solto — um
 * `.btn`/`.modal`/`body` sem escopo aqui vazaria pro resto da tela (Bootstrap usa essas mesmas
 * classes) ou, pior, pintaria a página inteira de escuro (era literalmente o que a regra
 * `body{...}` do design original fazia).
 *
 * "Baixar arquivo" é 100% local (canvas → blob → <a download>), sem round-trip nenhum — o
 * endpoint antigo (POST /imagem/processar) sempre converteu pra WebP no servidor e nunca
 * guardou nada (processa e descarta); como o navegador já sabe codificar WebP/PNG/JPG sozinho,
 * o roundtrip deixou de ser necessário. Esta tela nunca teve "salvar no sistema" (diferente do
 * outro editor do projeto, /editor-imagens) — não inventei esse botão aqui.
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.ie{
  --win:#232327;--ui:#2c2c31;--ui-2:#36363c;--well:#18181b;--line:#43434b;--fg:#ecebf2;--muted:#a9a8b4;
  --accent:#7c5cff;--accent-2:#9a82ff;--accent-ink:#ffffff;--ok:#3ccf86;--warn:#f2b23c;
  --chk1:#cfcfd6;--chk2:#f4f4f7;
  --font-ui:"IBM Plex Sans","Segoe UI",system-ui,sans-serif;
  --font-num:"IBM Plex Mono",ui-monospace,"SFMono-Regular",Menlo,monospace;
  color-scheme:dark;
  font:13px/1.45 var(--font-ui);color:var(--fg);
}
.ie *{box-sizing:border-box}
.ie button,.ie input{font:inherit;color:inherit}
.ie button{cursor:pointer}
.ie :focus-visible{outline:2px solid var(--accent-2);outline-offset:2px}

.ie .app{min-height:560px;display:grid;grid-template-columns:76px minmax(0,1fr) 232px;grid-template-rows:auto minmax(0,1fr) auto;grid-template-areas:"bar bar bar" "tools stage panel" "insp insp insp";background:var(--win);border:1px solid var(--line);border-radius:10px;overflow:hidden}

.ie .bar{grid-area:bar;display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 14px;border-bottom:1px solid var(--line);background:var(--ui)}
.ie .brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:15px;letter-spacing:-.01em}
.ie .mark{width:28px;height:28px;border-radius:6px;background:var(--accent);color:var(--accent-ink);display:grid;place-items:center;font:600 12px/1 var(--font-num)}
.ie .file{display:flex;flex-direction:column;min-width:0;padding-left:12px;border-left:1px solid var(--line)}
.ie .file b{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:26ch}
.ie .file span{color:var(--muted);font:12px var(--font-num)}
.ie .spacer{flex:1}
.ie .zoom{display:flex;align-items:center;gap:2px;background:var(--well);border:1px solid var(--line);border-radius:7px;padding:2px}
.ie .zoom button{border:0;background:transparent;height:30px;min-width:30px;padding:0 8px;border-radius:5px}
.ie .zoom button:hover{background:var(--ui-2)}
.ie .zoom output{min-width:52px;text-align:center;font:500 12px var(--font-num);font-variant-numeric:tabular-nums}
.ie .btn{height:36px;padding:0 14px;border-radius:7px;border:1px solid var(--line);background:var(--ui-2);font-weight:600;display:inline-flex;align-items:center;gap:8px;white-space:nowrap}
.ie .btn:hover{border-color:#5a5a64}
.ie .btn.primary{background:var(--accent);border-color:var(--accent);color:var(--accent-ink)}
.ie .btn.primary:hover{background:var(--accent-2);border-color:var(--accent-2)}
.ie .btn:disabled{opacity:.45;cursor:not-allowed}
.ie .btn svg{width:16px;height:16px;flex:none}
.ie kbd{font:500 10.5px var(--font-num);color:var(--muted);border:1px solid var(--line);border-radius:4px;padding:1px 4px}
.ie .btn.primary kbd{color:var(--accent-ink);border-color:rgba(255,255,255,.4)}
.ie .vsep{width:1px;height:28px;background:var(--line)}
.ie .btn.icon{width:36px;padding:0;justify-content:center}
.ie .btn.back{background:#ff7a1a;border-color:#ff7a1a;color:#1c0d00}
.ie .btn.back:hover{background:#ff9446;border-color:#ff9446}

.ie .tools{grid-area:tools;display:flex;flex-direction:column;gap:4px;padding:10px 4px;border-right:1px solid var(--line);background:var(--ui)}
.ie .tool{border:1px solid transparent;background:transparent;border-radius:7px;padding:8px 0 6px;display:flex;flex-direction:column;align-items:center;gap:4px;font-size:10.5px;letter-spacing:-.01em;white-space:nowrap;color:var(--muted)}
.ie .tool svg{width:22px;height:22px}
.ie .tool:hover{background:var(--ui-2);color:var(--fg)}
.ie .tool[aria-pressed="true"]{background:var(--accent);color:var(--accent-ink)}
.ie .tool:disabled{opacity:.4;cursor:not-allowed}
.ie .sep{height:1px;background:var(--line);margin:6px 4px}

.ie .stage{grid-area:stage;display:flex;overflow:auto;padding:28px;position:relative;background:var(--well);min-height:360px}
.ie .stage.drop::after{content:"Solte a imagem para abrir";position:absolute;inset:12px;border:2px dashed var(--accent-2);border-radius:10px;display:grid;place-items:center;font-weight:600;font-size:16px;background:rgba(124,92,255,.12)}
.ie .sheet{position:relative;margin:auto;flex:none;touch-action:none;background-color:var(--chk2);background-image:conic-gradient(var(--chk1) 25%,var(--chk2) 0 50%,var(--chk1) 0 75%,var(--chk2) 0);background-size:16px 16px;box-shadow:0 10px 40px rgba(0,0,0,.45)}
.ie .sheet canvas{display:block;width:100%;height:100%}
.ie .sheet.cropping{cursor:crosshair}
.ie .empty{margin:auto;display:flex;flex-direction:column;align-items:center;gap:10px;padding:40px;text-align:center;color:var(--muted)}
.ie .empty svg{width:48px;height:48px;opacity:.5}
.ie .empty-title{font-size:16px;font-weight:700;color:var(--fg)}
.ie .empty-sub{font-size:13px;max-width:34ch;line-height:1.5}
.ie .empty .btnrow{display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;justify-content:center}

.ie .crop{position:absolute;outline:1px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.6),0 0 0 100vmax rgba(10,10,14,.62);cursor:move;
  background-image:
    linear-gradient(to right,transparent calc(33.333% - .5px),rgba(255,255,255,.55) 0 calc(33.333% + .5px),transparent 0 calc(66.666% - .5px),rgba(255,255,255,.55) 0 calc(66.666% + .5px),transparent 0),
    linear-gradient(to bottom,transparent calc(33.333% - .5px),rgba(255,255,255,.55) 0 calc(33.333% + .5px),transparent 0 calc(66.666% - .5px),rgba(255,255,255,.55) 0 calc(66.666% + .5px),transparent 0)}
.ie .h{position:absolute;width:12px;height:12px;margin:-6px 0 0 -6px;background:#fff;border:1px solid #16161a}
.ie .h[data-h="nw"]{left:0;top:0;cursor:nwse-resize}.ie .h[data-h="n"]{left:50%;top:0;cursor:ns-resize}.ie .h[data-h="ne"]{left:100%;top:0;cursor:nesw-resize}
.ie .h[data-h="e"]{left:100%;top:50%;cursor:ew-resize}.ie .h[data-h="se"]{left:100%;top:100%;cursor:nwse-resize}.ie .h[data-h="s"]{left:50%;top:100%;cursor:ns-resize}
.ie .h[data-h="sw"]{left:0;top:100%;cursor:nesw-resize}.ie .h[data-h="w"]{left:0;top:50%;cursor:ew-resize}
.ie .rbox{position:absolute;inset:0;outline:1px solid var(--accent-2);box-shadow:0 0 0 1px rgba(0,0,0,.5)}
.ie .rbox .h{background:var(--accent-2);border-color:#fff}
.ie .rbox .dim{background:var(--accent)}
.ie .crop.locked .h[data-h="n"],.ie .crop.locked .h[data-h="e"],.ie .crop.locked .h[data-h="s"],.ie .crop.locked .h[data-h="w"]{display:none}
.ie .dim{position:absolute;left:0;top:-30px;white-space:nowrap;font:500 12px var(--font-num);background:#111114;color:#fff;padding:3px 7px;border-radius:4px;pointer-events:none}

.ie .panel{grid-area:panel;border-left:1px solid var(--line);background:var(--ui);overflow:auto;display:flex;flex-direction:column}
.ie .psec{padding:14px;display:flex;flex-direction:column;gap:10px;border-bottom:1px solid var(--line)}
.ie .psec h2{margin:0;font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px}
.ie .psec h2 small{font:500 11px var(--font-num);color:var(--muted);border:1px solid var(--line);border-radius:4px;padding:0 5px}
.ie .hint{margin:0;color:var(--muted);font-size:12.5px}
.ie .lbl{font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)}
.ie .chips{display:flex;flex-wrap:wrap;gap:6px}
.ie .chip{height:30px;padding:0 10px;border-radius:6px;border:1px solid var(--line);background:var(--ui-2);font:500 12px var(--font-num)}
.ie .chip:hover{border-color:#5a5a64}
.ie .chip[aria-pressed="true"]{background:var(--accent);border-color:var(--accent);color:var(--accent-ink)}
.ie .fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.ie .field{display:flex;align-items:center;gap:5px;background:var(--well);border:1px solid var(--line);border-radius:6px;padding:0 6px 0 8px;height:34px}
.ie .field:focus-within{border-color:var(--accent-2)}
.ie .field label{font:600 11px var(--font-num);color:var(--muted);min-width:12px}
.ie .field input{flex:1;min-width:0;border:0;background:transparent;font:500 13px var(--font-num);font-variant-numeric:tabular-nums;outline:none;height:100%;-moz-appearance:textfield;appearance:textfield;padding:0;letter-spacing:-.01em}
.ie .field input::-webkit-outer-spin-button,.ie .field input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.ie .field em{display:none}
.ie .unit{font:500 11px var(--font-num);color:var(--muted);text-transform:none;letter-spacing:0}
.ie .presets{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;border:1px solid var(--line);border-radius:7px;overflow:hidden}
.ie .presets button{width:100%;display:flex;justify-content:space-between;gap:10px;padding:8px 10px;border:0;border-bottom:1px solid var(--line);background:transparent;text-align:left}
.ie .presets li:last-child button{border-bottom:0}
.ie .presets button:hover{background:var(--ui-2)}
.ie .presets button span:last-child{font:12px var(--font-num);color:var(--muted);font-variant-numeric:tabular-nums}
.ie .presets button[aria-pressed="true"]{background:rgba(124,92,255,.22);box-shadow:inset 3px 0 0 var(--accent)}
.ie .presets button[aria-pressed="true"] span:last-child{color:var(--fg)}
.ie .dimrow{display:grid;grid-template-columns:minmax(0,1fr) 36px minmax(0,1fr);gap:6px;align-items:center}
.ie .lock{height:34px;border-radius:6px;border:1px solid var(--line);background:var(--ui-2);display:grid;place-items:center}
.ie .lock[aria-pressed="true"]{color:var(--accent-2);border-color:var(--accent)}
.ie .lock svg{width:16px;height:16px}
.ie .ba{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:8px;background:var(--well);border:1px solid var(--line);border-radius:7px;padding:10px}
.ie .ba div{display:flex;flex-direction:column;gap:2px}
.ie .ba div:last-child{text-align:right}
.ie .ba span{font-size:11px;color:var(--muted)}
.ie .ba b{font:500 13px var(--font-num);font-variant-numeric:tabular-nums}
.ie .ba svg{width:16px;height:16px;color:var(--muted)}
.ie .note{font-size:12.5px;border-radius:7px;padding:8px 10px;border:1px solid}
.ie .note.warn{color:#ffd88a;border-color:rgba(242,178,60,.45);background:rgba(242,178,60,.1)}
.ie .actions{display:flex;flex-direction:column-reverse;gap:8px}
.ie .actions .btn{flex:1;justify-content:center}
.ie .hist{display:flex;flex-direction:column;align-items:stretch;gap:8px}
.ie .hist span{color:var(--muted);font-size:12.5px}

.ie .insp{grid-area:insp;display:flex;flex-wrap:wrap;gap:6px 22px;align-items:center;padding:8px 14px;border-top:1px solid var(--line);background:var(--ui);font-size:12px;color:var(--muted)}
.ie .insp b{color:var(--fg);font:500 12px var(--font-num);font-variant-numeric:tabular-nums}
.ie .insp .keys{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}

.ie .modal{position:fixed;inset:0;z-index:20;background:rgba(6,6,9,.72);display:grid;place-items:center;padding:16px}
.ie .dlg{width:min(980px,100%);max-height:100%;display:flex;flex-direction:column;background:var(--win);border:1px solid var(--line);border-radius:12px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.6)}
.ie .dlg header,.ie .dlg footer{display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--ui)}
.ie .dlg header{border-bottom:1px solid var(--line)}
.ie .dlg footer{border-top:1px solid var(--line);justify-content:flex-end;flex-wrap:wrap}
.ie .dlg header h2{margin:0;font-size:15px;flex:1}
.ie .x{width:34px;height:34px;border-radius:7px;border:0;background:transparent;font-size:20px;color:var(--muted)}
.ie .x:hover{background:var(--ui-2);color:var(--fg)}
.ie .dlgbody{display:grid;grid-template-columns:minmax(0,1fr) 320px;min-height:0;overflow:auto}
.ie .pv{display:flex;flex-direction:column;min-width:0;background:var(--well)}
.ie .pvbox{flex:1;min-height:260px;display:grid;place-items:center;padding:20px}
.ie .pvbox img{max-width:100%;max-height:46vh;background-color:var(--chk2);background-image:conic-gradient(var(--chk1) 25%,var(--chk2) 0 50%,var(--chk1) 0 75%,var(--chk2) 0);background-size:16px 16px;box-shadow:0 8px 30px rgba(0,0,0,.45)}
.ie .pvcap{display:flex;flex-wrap:wrap;gap:4px 16px;padding:10px 16px;border-top:1px solid var(--line);font:12px var(--font-num);color:var(--muted)}
.ie .pvcap b{color:var(--fg);font-weight:500}
.ie .pvcap .good{color:var(--ok)}
.ie .opts{padding:16px;display:flex;flex-direction:column;gap:14px;border-left:1px solid var(--line);background:var(--ui)}
.ie .fmts{display:flex;flex-direction:column;gap:6px}
.ie .fmt{display:grid;grid-template-columns:auto 1fr auto;gap:2px 10px;align-items:center;text-align:left;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--ui-2)}
.ie .fmt:hover{border-color:#5a5a64}
.ie .fmt[aria-checked="true"]{border-color:var(--accent);background:rgba(124,92,255,.18)}
.ie .fmt .dot{grid-row:span 2;width:16px;height:16px;border-radius:50%;border:2px solid var(--muted)}
.ie .fmt[aria-checked="true"] .dot{border-color:var(--accent-2);background:radial-gradient(var(--accent-2) 40%,transparent 45%)}
.ie .fmt b{font-size:13.5px}
.ie .fmt small{color:var(--muted);font-size:12px;grid-column:2}
.ie .fmt .sz{grid-row:span 2;grid-column:3;text-align:right;font:500 12.5px var(--font-num);font-variant-numeric:tabular-nums;display:flex;flex-direction:column;align-items:flex-end;gap:2px}
.ie .tag{font:600 10px var(--font-ui);letter-spacing:.05em;text-transform:uppercase;color:#0b2a1b;background:var(--ok);border-radius:4px;padding:1px 5px}
.ie .fmt:disabled{opacity:.5;cursor:not-allowed}
.ie .range{display:flex;align-items:center;gap:10px}
.ie .range input{flex:1;accent-color:var(--accent)}
.ie .range output{min-width:36px;text-align:right;font:500 13px var(--font-num)}
.ie .bgrow{display:flex;align-items:center;gap:10px}
.ie .bgrow input[type=color]{width:40px;height:30px;border:1px solid var(--line);border-radius:6px;background:var(--ui-2);padding:2px}
.ie .fname{display:flex;align-items:center;background:var(--well);border:1px solid var(--line);border-radius:6px;height:36px;padding:0 10px}
.ie .fname:focus-within{border-color:var(--accent-2)}
.ie .fname input{flex:1;min-width:0;border:0;background:transparent;outline:none;font:13px var(--font-num)}
.ie .fname span{color:var(--muted);font:13px var(--font-num)}
.ie .status{min-height:18px}

.ie .toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%);z-index:30;background:#111114;color:#fff;border:1px solid var(--line);border-radius:8px;padding:9px 14px;font-weight:500;box-shadow:0 10px 30px rgba(0,0,0,.5);transition:opacity .2s}
.ie .toast[data-off]{opacity:0;pointer-events:none}
.ie .sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}

@media (max-width:860px){
  .ie .app{min-height:0;grid-template-columns:minmax(0,1fr);grid-template-rows:auto auto 420px auto auto;grid-template-areas:"bar" "tools" "stage" "panel" "insp"}
  .ie .tools{flex-direction:row;border-right:0;border-bottom:1px solid var(--line);overflow-x:auto}
  .ie .tool{min-width:64px}
  .ie .sep{width:1px;height:auto;margin:4px 6px}
  .ie .panel{border-left:0;border-top:1px solid var(--line)}
  .ie .stage{padding:24px}
  .ie .dlgbody{grid-template-columns:minmax(0,1fr)}
  .ie .opts{border-left:0;border-top:1px solid var(--line)}
  .ie .insp .keys{display:none}
}
@media (prefers-reduced-motion:reduce){.ie .toast{transition:none}}

/* Alguns elementos acima (.modal, .empty) já têm `display` próprio (grid/flex) — isso tem
   prioridade sobre o `[hidden]` nativo do navegador (regra do UA stylesheet, sem !important),
   então alternar `.hidden = true/false` em JS parava de escondê-los de verdade. Única regra
   que precisa vencer sempre, sem depender de especificidade/ordem entre as outras. */
.ie [hidden]{display:none!important}
</style>

<div class="ie">
<div class="app">
  <header class="bar">
    <div class="brand"><span class="mark" aria-hidden="true">Fx</span>Editor de Imagens</div>
    <div class="file"><b id="fName">Nenhuma imagem aberta</b><span id="fDims">—</span></div>
    <div class="spacer"></div>
    <div class="zoom" role="group" aria-label="Zoom">
      <button id="zOut" aria-label="Diminuir zoom">−</button>
      <output id="zVal">—</output>
      <button id="zIn" aria-label="Aumentar zoom">+</button>
      <button id="zFit">Ajustar</button>
      <button id="z100">100%</button>
    </div>
    <label class="btn" for="fileIn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>
      Abrir imagem
    </label>
    <input type="file" id="fileIn" accept="image/png,image/jpeg,image/webp,image/gif,image/bmp" class="sr">
    <button class="btn primary" id="exportBtn" disabled>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
      Exportar <kbd>Ctrl ⇧ X</kbd>
    </button>
    <div class="vsep" aria-hidden="true"></div>
    <button class="btn icon" id="fsBtn" title="Tela cheia" aria-label="Tela cheia">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path id="fsPath" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
    </button>
    <button class="btn back" id="backBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
      Voltar ao sistema
    </button>
  </header>

  <nav class="tools" aria-label="Ferramentas">
    <button class="tool" id="tCrop" aria-pressed="true" title="Cortar (C)" disabled>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg>Cortar
    </button>
    <button class="tool" id="tResize" aria-pressed="false" title="Dimensionar imagem (R)" disabled>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="10" height="10" rx="1"/><path d="M14 3h7v7M21 3l-8 8"/></svg>Dimensionar
    </button>
    <button class="tool" id="tRotate" title="Girar 90° à direita" disabled>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>Girar
    </button>
    <div class="sep" aria-hidden="true"></div>
    <button class="tool" id="tUndo" title="Desfazer (Ctrl+Z)" disabled>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/></svg>Desfazer
    </button>
  </nav>

  <main class="stage" id="stage" aria-label="Área de trabalho">
    <div class="empty" id="emptyState">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15V9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m3 17 5-5 4 4"/><path d="M17 15v6M14 18h6"/></svg>
      <div class="empty-title">Abra uma imagem para começar</div>
      <div class="empty-sub">Clique, arraste e solte na área ao lado, ou cole (Ctrl+V) uma imagem copiada.</div>
      <div class="btnrow">
        <button type="button" class="btn primary" id="emptyOpenBtn">Abrir imagem</button>
      </div>
    </div>
    <div class="sheet cropping" id="sheet" hidden>
      <canvas id="view" aria-label="Imagem em edição"></canvas>
      <div class="crop" id="crop">
        <span class="dim" id="cropDim">0 × 0</span>
        <i class="h" data-h="nw"></i><i class="h" data-h="n"></i><i class="h" data-h="ne"></i><i class="h" data-h="e"></i>
        <i class="h" data-h="se"></i><i class="h" data-h="s"></i><i class="h" data-h="sw"></i><i class="h" data-h="w"></i>
      </div>
      <div class="rbox" id="rbox" hidden>
        <span class="dim" id="rDim">0 × 0</span>
        <i class="h" data-h="nw"></i><i class="h" data-h="n"></i><i class="h" data-h="ne"></i><i class="h" data-h="e"></i>
        <i class="h" data-h="se"></i><i class="h" data-h="s"></i><i class="h" data-h="sw"></i><i class="h" data-h="w"></i>
      </div>
    </div>
  </main>

  <aside class="panel" aria-label="Propriedades">
    <section class="psec" id="pCrop">
      <h2>Cortar <small>C</small></h2>
      <p class="hint">Arraste os cantos ou mova a área. Arraste fora dela para marcar outra.</p>
      <div class="lbl">Proporção</div>
      <div class="chips" id="ratios">
        <button class="chip" data-r="free" aria-pressed="true">Livre</button>
        <button class="chip" data-r="orig" aria-pressed="false">Original</button>
        <button class="chip" data-r="1" aria-pressed="false">1:1</button>
        <button class="chip" data-r="1.333333" aria-pressed="false">4:3</button>
        <button class="chip" data-r="1.5" aria-pressed="false">3:2</button>
        <button class="chip" data-r="1.777778" aria-pressed="false">16:9</button>
      </div>
      <div class="lbl">Tamanhos do FixaOS <span class="unit">(corta e já ajusta)</span></div>
      <ul class="presets" id="presets">
        <li><button type="button" data-w="900" data-h="300" aria-pressed="false"><span>Logo retangular</span><span>900 × 300</span></button></li>
        <li><button type="button" data-w="512" data-h="512" aria-pressed="false"><span>Logo quadrado</span><span>512 × 512</span></button></li>
        <li><button type="button" data-w="1920" data-h="480" aria-pressed="false"><span>Topo do diretório</span><span>1920 × 480</span></button></li>
        <li><button type="button" data-w="1600" data-h="500" aria-pressed="false"><span>Capa do perfil</span><span>1600 × 500</span></button></li>
        <li><button type="button" data-w="1200" data-h="1200" aria-pressed="false"><span>Produto / peça</span><span>1200 × 1200</span></button></li>
        <li><button type="button" data-w="970" data-h="250" aria-pressed="false"><span>Anúncio do marketplace</span><span>970 × 250</span></button></li>
        <li><button type="button" data-w="320" data-h="100" aria-pressed="false"><span>Anúncio mobile</span><span>320 × 100</span></button></li>
        <li><button type="button" data-w="128" data-h="128" aria-pressed="false"><span>Avatar do usuário</span><span>128 × 128</span></button></li>
      </ul>
      <div class="lbl">Posição e tamanho <span class="unit">(px)</span></div>
      <div class="fields">
        <div class="field"><label for="cx">X</label><input id="cx" type="number" min="0" inputmode="numeric"><em>px</em></div>
        <div class="field"><label for="cy">Y</label><input id="cy" type="number" min="0" inputmode="numeric"><em>px</em></div>
        <div class="field"><label for="cw">L</label><input id="cw" type="number" min="1" inputmode="numeric"><em>px</em></div>
        <div class="field"><label for="ch">A</label><input id="ch" type="number" min="1" inputmode="numeric"><em>px</em></div>
      </div>
      <div class="note warn" id="cropWarn" hidden></div>
      <div class="actions">
        <button class="btn" id="cropReset">Selecionar tudo</button>
        <button class="btn primary" id="cropApply">Cortar</button>
      </div>
    </section>

    <section class="psec" id="pResize" hidden>
      <h2>Dimensionar imagem <small>R</small></h2>
      <p class="hint">Arraste as alças na própria imagem ou digite o tamanho. Com o cadeado fechado, a imagem não deforma.</p>
      <div class="lbl">Largura e altura <span class="unit">(px)</span></div>
      <div class="dimrow">
        <div class="field"><label for="rw">L</label><input id="rw" type="number" min="1" inputmode="numeric"><em>px</em></div>
        <button class="lock" id="lock" aria-pressed="true" aria-label="Manter proporção" title="Manter proporção">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path id="lockPath" d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        </button>
        <div class="field"><label for="rh">A</label><input id="rh" type="number" min="1" inputmode="numeric"><em>px</em></div>
      </div>
      <div class="lbl">Escala</div>
      <div class="chips" id="scales">
        <button class="chip" data-p="0.25">25%</button>
        <button class="chip" data-p="0.5">50%</button>
        <button class="chip" data-p="0.75">75%</button>
        <button class="chip" data-p="1">100%</button>
      </div>
      <div class="lbl">Largura máxima</div>
      <div class="chips" id="maxw">
        <button class="chip" data-m="1920">1920</button>
        <button class="chip" data-m="1200">1200</button>
        <button class="chip" data-m="800">800</button>
        <button class="chip" data-m="512">512</button>
      </div>
      <div class="ba">
        <div><span>Atual</span><b id="baFrom">0 × 0</b></div>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
        <div><span>Novo</span><b id="baTo">0 × 0</b></div>
      </div>
      <div class="note warn" id="resWarn" hidden>Aumentar a imagem além do tamanho atual deixa a foto menos nítida.</div>
      <div class="actions">
        <button class="btn" id="resReset">Voltar ao atual</button>
        <button class="btn primary" id="resApply">Redimensionar</button>
      </div>
    </section>

    <section class="psec">
      <h2>Histórico</h2>
      <div class="hist"><span id="histTxt">Nenhuma alteração ainda.</span><button class="btn" id="restore" disabled>Voltar ao original</button></div>
    </section>
  </aside>

  <footer class="insp">
    <span>Imagem <b id="iSize">—</b></span>
    <span>Zoom <b id="iZoom">—</b></span>
    <span>Cursor <b id="iPos">–</b></span>
    <span class="keys"><span><kbd>C</kbd> cortar</span><span><kbd>R</kbd> dimensionar</span><span><kbd>Enter</kbd> aplicar</span><span><kbd>Ctrl Z</kbd> desfazer</span></span>
  </footer>
</div>

<div class="modal" id="exModal" hidden role="dialog" aria-modal="true" aria-labelledby="exTitle">
  <div class="dlg">
    <header><h2 id="exTitle">Visualizar e exportar</h2><button class="x" data-close aria-label="Fechar">×</button></header>
    <div class="dlgbody">
      <div class="pv">
        <div class="pvbox"><img id="exImg" alt="Pré-visualização do arquivo exportado"></div>
        <div class="pvcap" id="exCap"></div>
      </div>
      <div class="opts">
        <div class="lbl">Formato</div>
        <div class="fmts" role="radiogroup" aria-label="Formato do arquivo">
          <button class="fmt" role="radio" data-f="webp" aria-checked="true"><span class="dot"></span><b>WebP</b><small>Arquivo menor, ideal para site e Google</small><span class="sz" id="szwebp">…</span></button>
          <button class="fmt" role="radio" data-f="png" aria-checked="false"><span class="dot"></span><b>PNG transparente</b><small>Mantém o fundo transparente, sem perda</small><span class="sz" id="szpng">…</span></button>
          <button class="fmt" role="radio" data-f="jpg" aria-checked="false"><span class="dot"></span><b>JPG</b><small>Fotos; não tem transparência</small><span class="sz" id="szjpg">…</span></button>
        </div>
        <div id="qRow">
          <div class="lbl"><label for="exQ">Qualidade</label></div>
          <div class="range"><input type="range" id="exQ" min="10" max="100" value="82"><output id="exQv">82</output></div>
        </div>
        <div id="bgRow" hidden>
          <div class="lbl"><label for="exBg">Cor do fundo</label></div>
          <div class="bgrow"><input type="color" id="exBg" value="#ffffff"><span class="hint">As áreas transparentes ficam com esta cor no JPG.</span></div>
        </div>
        <div>
          <div class="lbl"><label for="exName">Nome do arquivo</label></div>
          <div class="fname"><input id="exName" value="imagem"><span id="exExt">.webp</span></div>
        </div>
        <p class="hint status" id="exStatus" aria-live="polite"></p>
      </div>
    </div>
    <footer>
      <button class="btn" data-close>Cancelar</button>
      <button class="btn primary" id="exBaixar">Baixar arquivo</button>
    </footer>
  </div>
</div>

<div class="modal" id="leaveModal" hidden role="dialog" aria-modal="true" aria-labelledby="leaveTitle">
  <div class="dlg" style="width:min(440px,100%)">
    <header><h2 id="leaveTitle">Sair sem exportar?</h2><button class="x" data-leave-close aria-label="Fechar">×</button></header>
    <p style="margin:0;padding:18px 16px;color:var(--muted)">As alterações nesta imagem ainda não foram exportadas e serão perdidas.</p>
    <footer><button class="btn" data-leave-close>Continuar editando</button><button class="btn primary" id="leaveGo">Sair</button></footer>
  </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite" data-off></div>
</div>

<script>
(() => {
  const $ = s => document.querySelector('.ie ' + s);
  const $$ = s => [...document.querySelectorAll('.ie ' + s)];
  const view = $('#view'), vctx = view.getContext('2d');
  const stage = $('#stage'), sheet = $('#sheet'), cropEl = $('#crop');
  const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
  const fmtB = b => b < 1024 ? b + ' B' : b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1).replace('.', ',') + ' MB';
  const dims = (w, h) => `${Math.round(w)} × ${Math.round(h)}`;

  const S = { cv: null, orig: null, name: 'imagem', origBytes: 0, origType: '', hist: [], ver: 0,
              tool: 'crop', zoom: 1, fit: true, crop: null, ratio: null, target: null, lock: true, rw: 0, rh: 0, dirty: false };

  function mk(w, h) { const c = document.createElement('canvas'); c.width = Math.max(1, Math.round(w)); c.height = Math.max(1, Math.round(h)); return c; }
  function hq(c) { const x = c.getContext('2d'); x.imageSmoothingEnabled = true; x.imageSmoothingQuality = 'high'; return x; }

  /* ---------- reamostragem de alta qualidade ---------- */
  function resample(src, w, h) {
    w = Math.max(1, Math.round(w)); h = Math.max(1, Math.round(h));
    let cur = src, cw = src.width, ch = src.height;
    while (cw / 2 >= w && ch / 2 >= h) { cw = Math.round(cw / 2); ch = Math.round(ch / 2); const t = mk(cw, ch); hq(t).drawImage(cur, 0, 0, cw, ch); cur = t; }
    const out = mk(w, h); hq(out).drawImage(cur, 0, 0, w, h); return out;
  }

  /* ---------- estado vazio / com imagem ---------- */
  function showEmpty(vazio) {
    $('#emptyState').hidden = !vazio;
    $('#sheet').hidden = vazio;
    ['exportBtn', 'tCrop', 'tResize', 'tRotate'].forEach(id => $('#' + id).disabled = vazio);
    if (vazio) {
      $('#fName').textContent = 'Nenhuma imagem aberta'; $('#fDims').textContent = '—';
      $('#iSize').textContent = '—'; $('#iZoom').textContent = '—'; $('#iPos').textContent = '–';
      $('#zVal').textContent = '—';
      $('#histTxt').textContent = 'Nenhuma alteração ainda.'; $('#tUndo').disabled = true; $('#restore').disabled = true;
    }
  }

  /* ---------- estado da imagem ---------- */
  function setImage(c) { showEmpty(false); S.hist = []; S.orig = c; S.cv = c; S.ver++; S.dirty = false; afterChange(); }
  function commit(c, msg) { S.hist.push(S.cv); if (S.hist.length > 30) S.hist.shift(); S.cv = c; S.ver++; S.dirty = true; afterChange(); if (msg) toast(msg); }
  function afterChange() {
    S.rw = S.cv.width; S.rh = S.cv.height;
    if (S.target) { S.ratio = S.target.w / S.target.h; }
    resetCrop(); S.fit = true; render(); syncAll();
  }
  function undo() { if (!S.cv || !S.hist.length) return toast('Não há nada para desfazer.'); S.cv = S.hist.pop(); S.ver++; afterChange(); toast('Alteração desfeita'); }
  function restore() { if (!S.cv || !S.hist.length) return; S.hist.push(S.cv); S.cv = S.orig; S.ver++; afterChange(); toast('Imagem original restaurada'); }

  function fitRect(r) { const W = S.cv.width, H = S.cv.height; if (!r) return { x: 0, y: 0, w: W, h: H }; let w = W, h = w / r; if (h > H) { h = H; w = h * r; } return { x: (W - w) / 2, y: (H - h) / 2, w, h }; }
  function resetCrop() { S.crop = fitRect(S.ratio); }

  /* ---------- desenho ---------- */
  function fitZoom() { const r = stage.getBoundingClientRect(); const pad = r.width < 500 ? 48 : 80; return clamp(Math.min(1, (r.width - pad) / S.cv.width, (r.height - pad) / S.cv.height), 0.02, 1); }
  function render() {
    if (!S.cv) return;
    const c = S.cv;
    if (view.width !== c.width || view.height !== c.height) { view.width = c.width; view.height = c.height; }
    vctx.clearRect(0, 0, c.width, c.height); vctx.drawImage(c, 0, 0);
    if (S.fit) S.zoom = fitZoom();
    applyZoom();
  }
  function applyZoom() {
    const rs = S.tool === 'resize';
    sheet.style.width = (rs ? S.rw : S.cv.width) * S.zoom + 'px'; sheet.style.height = (rs ? S.rh : S.cv.height) * S.zoom + 'px';
    $('#rbox').hidden = !rs;
    if (rs) { const p = Math.round(S.rw / S.cv.width * 100); $('#rDim').textContent = `${dims(S.rw, S.rh)}${p !== 100 ? '  ·  ' + p + '%' : ''}`; }
    view.style.imageRendering = S.zoom >= 2 ? 'pixelated' : 'auto';
    const z = Math.round(S.zoom * 100) + '%'; $('#zVal').textContent = z; $('#iZoom').textContent = z;
    drawCrop();
  }
  function drawCrop() {
    const on = S.tool === 'crop' && S.crop;
    cropEl.hidden = !on; sheet.classList.toggle('cropping', S.tool === 'crop');
    if (!on) return;
    const c = S.crop, z = S.zoom;
    Object.assign(cropEl.style, { left: c.x * z + 'px', top: c.y * z + 'px', width: c.w * z + 'px', height: c.h * z + 'px' });
    cropEl.classList.toggle('locked', !!S.ratio);
    $('#cropDim').textContent = dims(c.w, c.h) + (S.target ? `  →  ${dims(S.target.w, S.target.h)}` : '');
  }
  const ZL = [0.05, 0.1, 0.15, 0.25, 0.33, 0.5, 0.67, 0.75, 1, 1.5, 2, 3, 4, 6, 8];
  function zoomStep(dir) { if (!S.cv) return; S.fit = false; const z = S.zoom; S.zoom = dir > 0 ? (ZL.find(v => v > z + 0.001) || 8) : ([...ZL].reverse().find(v => v < z - 0.001) || 0.05); applyZoom(); }

  /* ---------- seleção de corte ---------- */
  function pt(e) { const r = sheet.getBoundingClientRect(); return { x: clamp((e.clientX - r.left) / S.zoom, 0, S.cv.width), y: clamp((e.clientY - r.top) / S.zoom, 0, S.cv.height) }; }
  function corner(ax, ay, px, py) {
    const W = S.cv.width, H = S.cv.height, r = S.ratio;
    const sx = px - ax < 0 ? -1 : 1, sy = py - ay < 0 ? -1 : 1;
    let aw = Math.abs(px - ax), ah = Math.abs(py - ay);
    if (r) { if (aw / r > ah) ah = aw / r; else aw = ah * r; }
    const mw = sx > 0 ? W - ax : ax, mh = sy > 0 ? H - ay : ay;
    if (r) { const f = Math.min(1, aw ? mw / aw : 1, ah ? mh / ah : 1); aw *= f; ah *= f; }
    else { aw = Math.min(aw, mw); ah = Math.min(ah, mh); }
    return { x: sx > 0 ? ax : ax - aw, y: sy > 0 ? ay : ay - ah, w: aw, h: ah };
  }
  let drag = null;
  sheet.addEventListener('pointerdown', e => {
    if (e.button > 0 || !S.cv) return;
    if (S.tool === 'resize') {
      const h = e.target.dataset && e.target.dataset.h;
      if (!h || !e.target.closest('#rbox')) return;
      e.preventDefault();
      const kx = sheet.offsetWidth < stage.clientWidth - 56 ? 2 : 1, ky = sheet.offsetHeight < stage.clientHeight - 56 ? 2 : 1;
      drag = { mode: 'res', h, sx: e.clientX, sy: e.clientY, w: S.rw, hh: S.rh, kx, ky };
      sheet.setPointerCapture(e.pointerId);
      return;
    }
    if (S.tool !== 'crop') return;
    e.preventDefault();
    const p = pt(e), h = e.target.dataset && e.target.dataset.h, c = S.crop;
    if (h) drag = { mode: 'h', h, s: { ...c } };
    else if (e.target.closest('#crop')) drag = { mode: 'move', ox: p.x - c.x, oy: p.y - c.y };
    else { drag = { mode: 'new', ax: p.x, ay: p.y }; S.crop = { x: p.x, y: p.y, w: 0, h: 0 }; }
    sheet.setPointerCapture(e.pointerId);
  });
  sheet.addEventListener('pointermove', e => {
    if (!S.cv) return;
    const p = pt(e); $('#iPos').textContent = `${Math.round(p.x)}, ${Math.round(p.y)}`;
    if (!drag) return;
    if (drag.mode === 'res') {
      const dx = (e.clientX - drag.sx) * drag.kx / S.zoom, dy = (e.clientY - drag.sy) * drag.ky / S.zoom, h = drag.h;
      let w = drag.w, hh = drag.hh;
      if (h.includes('e')) w += dx; if (h.includes('w')) w -= dx;
      if (h.includes('s')) hh += dy; if (h.includes('n')) hh -= dy;
      if (S.lock) {
        const r = S.cv.width / S.cv.height;
        if (h.length === 2) { if (Math.abs(w - drag.w) / drag.w >= Math.abs(hh - drag.hh) / drag.hh) hh = w / r; else w = hh * r; }
        else if (h === 'e' || h === 'w') hh = w / r; else w = hh * r;
      }
      S.rw = Math.max(1, Math.round(w)); S.rh = Math.max(1, Math.round(hh)); syncRes();
      return;
    }
    const W = S.cv.width, H = S.cv.height;
    if (drag.mode === 'move') { const c = S.crop; c.x = clamp(p.x - drag.ox, 0, W - c.w); c.y = clamp(p.y - drag.oy, 0, H - c.h); }
    else if (drag.mode === 'new') S.crop = corner(drag.ax, drag.ay, p.x, p.y);
    else {
      const s = drag.s, h = drag.h;
      if (h.length === 2) { const ax = h.includes('w') ? s.x + s.w : s.x, ay = h.includes('n') ? s.y + s.h : s.y; S.crop = corner(ax, ay, p.x, p.y); }
      else {
        const c = { ...s };
        if (h === 'e') c.w = clamp(p.x, s.x + 1, W) - s.x;
        if (h === 'w') { const nx = clamp(p.x, 0, s.x + s.w - 1); c.w = s.x + s.w - nx; c.x = nx; }
        if (h === 's') c.h = clamp(p.y, s.y + 1, H) - s.y;
        if (h === 'n') { const ny = clamp(p.y, 0, s.y + s.h - 1); c.h = s.y + s.h - ny; c.y = ny; }
        S.crop = c;
      }
    }
    drawCrop(); syncCrop();
  });
  const endDrag = () => { if (!drag) return; if (drag.mode === 'res') { drag = null; return; } drag = null; if (S.crop.w < 3 || S.crop.h < 3) resetCrop(); drawCrop(); syncCrop(); };
  sheet.addEventListener('pointerup', endDrag); sheet.addEventListener('pointercancel', endDrag);
  sheet.addEventListener('pointerleave', () => { if (!drag) $('#iPos').textContent = '–'; });

  function setCropField(k, v) {
    if (!isFinite(v)) return;
    const W = S.cv.width, H = S.cv.height, c = { ...S.crop }, r = S.ratio;
    if (k === 'x') c.x = clamp(v, 0, W - c.w);
    if (k === 'y') c.y = clamp(v, 0, H - c.h);
    if (k === 'w') { c.w = clamp(v, 1, W - c.x); if (r) { c.h = c.w / r; if (c.y + c.h > H) { c.h = H - c.y; c.w = c.h * r; } } }
    if (k === 'h') { c.h = clamp(v, 1, H - c.y); if (r) { c.w = c.h * r; if (c.x + c.w > W) { c.w = W - c.x; c.h = c.w / r; } } }
    S.crop = c; drawCrop(); syncCrop();
  }
  [['cx', 'x'], ['cy', 'y'], ['cw', 'w'], ['ch', 'h']].forEach(([id, k]) => {
    const el = $('#' + id);
    const go = () => { if (el.value === '' || !S.cv) return; const v = +el.value; if ((k === 'w' || k === 'h') && v < 1) return; setCropField(k, v); };
    el.addEventListener('input', go);
    el.addEventListener('change', () => { go(); if (S.crop) el.value = Math.round(S.crop[k]); });
  });

  function pickRatio(btn) {
    $$('#ratios .chip').forEach(b => b.setAttribute('aria-pressed', b === btn)); $$('#presets button').forEach(b => b.setAttribute('aria-pressed', 'false'));
    const v = btn.dataset.r; S.target = null; S.ratio = v === 'free' ? null : v === 'orig' ? S.cv.width / S.cv.height : +v;
    resetCrop(); drawCrop(); syncCrop();
  }
  function pickPreset(btn) {
    $$('#ratios .chip').forEach(b => b.setAttribute('aria-pressed', 'false')); $$('#presets button').forEach(b => b.setAttribute('aria-pressed', b === btn));
    S.target = { w: +btn.dataset.w, h: +btn.dataset.h }; S.ratio = S.target.w / S.target.h;
    resetCrop(); drawCrop(); syncCrop();
  }
  $$('#ratios .chip').forEach(b => b.addEventListener('click', () => { if (S.cv) pickRatio(b); }));
  $$('#presets button').forEach(b => b.addEventListener('click', () => { if (S.cv) pickPreset(b); }));

  function applyCrop() {
    if (!S.cv) return;
    const c = S.crop, x = Math.round(c.x), y = Math.round(c.y), w = Math.max(1, Math.round(c.w)), h = Math.max(1, Math.round(c.h));
    const full = x === 0 && y === 0 && w === S.cv.width && h === S.cv.height;
    if (full && !S.target) return toast('Marque uma área menor que a imagem para cortar.');
    let out = mk(w, h); out.getContext('2d').drawImage(S.cv, x, y, w, h, 0, 0, w, h);
    let msg = `Cortada para ${dims(w, h)} px`;
    if (S.target) { out = resample(out, S.target.w, S.target.h); msg = `Cortada e ajustada para ${dims(S.target.w, S.target.h)} px`; }
    commit(out, msg);
  }

  /* ---------- redimensionar ---------- */
  function setRes(w, h) { S.rw = Math.max(1, Math.round(w)); S.rh = Math.max(1, Math.round(h)); syncRes(); }
  $('#rw').addEventListener('input', e => { if (!S.cv) return; const v = +e.target.value; if (!v) return; S.rw = v; if (S.lock) S.rh = Math.max(1, Math.round(v * S.cv.height / S.cv.width)); syncRes('rw'); });
  $('#rh').addEventListener('input', e => { if (!S.cv) return; const v = +e.target.value; if (!v) return; S.rh = v; if (S.lock) S.rw = Math.max(1, Math.round(v * S.cv.width / S.cv.height)); syncRes('rh'); });
  $('#lock').addEventListener('click', () => { S.lock = !S.lock; $('#lock').setAttribute('aria-pressed', S.lock); $('#lockPath').setAttribute('d', S.lock ? 'M8 11V7a4 4 0 0 1 8 0v4' : 'M8 11V7a4 4 0 0 1 7.5-2'); if (S.lock && S.cv) setRes(S.rw, S.rw * S.cv.height / S.cv.width); });
  $$('#scales .chip').forEach(b => b.addEventListener('click', () => { if (S.cv) setRes(S.cv.width * b.dataset.p, S.cv.height * b.dataset.p); }));
  $$('#maxw .chip').forEach(b => b.addEventListener('click', () => { if (!S.cv) return; const m = +b.dataset.m, W = S.cv.width; if (W <= m) { setRes(W, S.cv.height); toast(`A imagem já tem ${W} px de largura.`); } else setRes(m, m * S.cv.height / W); }));
  function applyResize() {
    if (!S.cv) return;
    if (S.rw === S.cv.width && S.rh === S.cv.height) return toast('Escolha um tamanho diferente do atual.');
    commit(resample(S.cv, S.rw, S.rh), `Redimensionada para ${dims(S.rw, S.rh)} px`);
  }

  /* ---------- girar ---------- */
  function rotate() { if (!S.cv) return; const s = S.cv, c = mk(s.height, s.width), x = c.getContext('2d'); x.translate(c.width, 0); x.rotate(Math.PI / 2); x.drawImage(s, 0, 0); commit(c, 'Girada 90° para a direita'); }

  /* ---------- painéis ---------- */
  function syncCrop() {
    const c = S.crop; if (!c) return;
    const set = (id, v) => { const el = $('#' + id); if (document.activeElement !== el) el.value = Math.round(v); };
    set('cx', c.x); set('cy', c.y); set('cw', c.w); set('ch', c.h);
    $('#cropApply').textContent = S.target ? `Cortar e ajustar para ${dims(S.target.w, S.target.h)}` : 'Cortar';
    const warn = $('#cropWarn');
    if (S.target && (c.w < S.target.w - 0.5)) { warn.hidden = false; warn.textContent = `A área marcada tem ${dims(c.w, c.h)} px, menor que ${dims(S.target.w, S.target.h)}. A imagem será ampliada e pode perder nitidez.`; }
    else warn.hidden = true;
  }
  function syncRes(skip) {
    if (skip !== 'rw' || document.activeElement !== $('#rw')) $('#rw').value = S.rw;
    if (skip !== 'rh' || document.activeElement !== $('#rh')) $('#rh').value = S.rh;
    if (!S.cv) return;
    $('#baFrom').textContent = dims(S.cv.width, S.cv.height); $('#baTo').textContent = dims(S.rw, S.rh);
    $('#resWarn').hidden = !(S.rw > S.cv.width || S.rh > S.cv.height);
    $('#resApply').textContent = `Dimensionar para ${dims(S.rw, S.rh)}`;
    if (S.tool === 'resize') applyZoom();
  }
  function syncAll() {
    if (!S.cv) return;
    const W = S.cv.width, H = S.cv.height;
    $('#fName').textContent = S.name + (S.origType ? '.' + S.origType.toLowerCase() : '.png');
    $('#fDims').textContent = `${dims(W, H)} px`; $('#iSize').textContent = `${dims(W, H)} px`;
    const n = S.hist.length;
    $('#histTxt').textContent = n ? `${n} ${n === 1 ? 'alteração' : 'alterações'} nesta imagem.` : 'Nenhuma alteração ainda.';
    $('#tUndo').disabled = !n; $('#restore').disabled = !n;
    syncCrop(); syncRes();
  }
  function setTool(t) {
    if (!S.cv) return;
    S.tool = t;
    $('#tCrop').setAttribute('aria-pressed', t === 'crop'); $('#tResize').setAttribute('aria-pressed', t === 'resize');
    $('#pCrop').hidden = t !== 'crop'; $('#pResize').hidden = t !== 'resize';
    if (t === 'resize') setRes(S.cv.width, S.cv.height);
    applyZoom();
  }

  /* ---------- abrir arquivo ---------- */
  function openFile(file) {
    if (!file || !/^image\//.test(file.type)) return toast('Escolha um arquivo de imagem: JPG, PNG ou WebP.');
    const url = URL.createObjectURL(file), im = new Image();
    im.onload = () => {
      const c = mk(im.naturalWidth, im.naturalHeight); c.getContext('2d').drawImage(im, 0, 0); URL.revokeObjectURL(url);
      S.name = (file.name || 'imagem').replace(/\.[^.]+$/, '') || 'imagem';
      S.origBytes = file.size; S.origType = (file.type.split('/')[1] || 'img').toUpperCase().replace('JPEG', 'JPG');
      setImage(c); toast(`${file.name || 'Imagem'} aberta · ${dims(c.width, c.height)} px`);
    };
    im.onerror = () => { URL.revokeObjectURL(url); toast('Não foi possível abrir esse arquivo. Tente um JPG, PNG ou WebP.'); };
    im.src = url;
  }
  $('#fileIn').addEventListener('change', e => { openFile(e.target.files[0]); e.target.value = ''; });
  $('#emptyOpenBtn').addEventListener('click', () => $('#fileIn').click());
  ['dragenter', 'dragover'].forEach(t => stage.addEventListener(t, e => { e.preventDefault(); stage.classList.add('drop'); }));
  ['dragleave', 'drop'].forEach(t => stage.addEventListener(t, e => { e.preventDefault(); if (t === 'drop' || e.target === stage) stage.classList.remove('drop'); }));
  stage.addEventListener('drop', e => openFile(e.dataTransfer.files[0]));
  document.addEventListener('paste', e => { const it = [...(e.clipboardData?.items || [])].find(i => i.type.startsWith('image/')); if (it) { const f = it.getAsFile(); openFile(new File([f], 'imagem-colada.' + (f.type.split('/')[1] || 'png'), { type: f.type })); } });

  /* ---------- exportar (100% local — sem round-trip de servidor) ---------- */
  const EX = { fmt: 'webp', q: 82, bg: '#ffffff', blobs: {}, url: null, seq: 0, pngVer: -1, pngBlob: null };
  const MIME = { png: 'image/png', jpg: 'image/jpeg', webp: 'image/webp' };
  const EXT = { png: '.png', jpg: '.jpg', webp: '.webp' };
  function encode(f) {
    if (f === 'png' && EX.pngVer === S.ver && EX.pngBlob) return Promise.resolve(EX.pngBlob);
    let src = S.cv;
    if (f === 'jpg') { const c = mk(src.width, src.height), x = c.getContext('2d'); x.fillStyle = EX.bg; x.fillRect(0, 0, c.width, c.height); x.drawImage(src, 0, 0); src = c; }
    return new Promise(res => src.toBlob(b => { if (f === 'png') { EX.pngBlob = b; EX.pngVer = S.ver; } res(b); }, MIME[f], f === 'png' ? undefined : EX.q / 100));
  }
  async function refreshExport() {
    if (!S.cv) return;
    const my = ++EX.seq; $('#exStatus').textContent = 'Calculando o tamanho dos arquivos…';
    const out = {}; for (const f of ['webp', 'png', 'jpg']) out[f] = await encode(f);
    if (my !== EX.seq) return;
    EX.blobs = out;
    const ok = f => out[f] && out[f].type === MIME[f];
    const sizes = ['webp', 'png', 'jpg'].filter(ok).map(f => out[f].size); const min = Math.min(...sizes);
    ['webp', 'png', 'jpg'].forEach(f => {
      const el = $('#sz' + f), btn = el.closest('.fmt');
      if (!ok(f)) { el.textContent = 'não suportado'; btn.disabled = true; if (EX.fmt === f) pickFmt('png'); return; }
      btn.disabled = false; el.innerHTML = '';
      const s = document.createElement('span'); s.textContent = fmtB(out[f].size); el.append(s);
      if (out[f].size === min) { const t = document.createElement('span'); t.className = 'tag'; t.textContent = 'menor'; el.append(t); }
    });
    showPreview(); $('#exStatus').textContent = '';
  }
  function showPreview() {
    const b = EX.blobs[EX.fmt]; if (!b) return;
    if (EX.url) URL.revokeObjectURL(EX.url); EX.url = URL.createObjectURL(b); $('#exImg').src = EX.url;
    const cap = $('#exCap'); cap.innerHTML = '';
    const add = (label, val, cls) => { const s = document.createElement('span'); s.append(label + ' '); const v = document.createElement('b'); v.textContent = val; if (cls) v.className = cls; s.append(v); cap.append(s); };
    add('Tamanho', `${dims(S.cv.width, S.cv.height)} px`);
    add('Formato', EX.fmt === 'png' ? 'PNG' : `${EX.fmt.toUpperCase()} ${EX.q}%`);
    add('Arquivo', fmtB(b.size));
    if (S.origBytes) { const p = Math.round((1 - b.size / S.origBytes) * 100); add('Original', fmtB(S.origBytes)); if (p > 0) add('Economia', `${p}% menor`, 'good'); }
  }
  function pickFmt(f) {
    EX.fmt = f; $$('.fmt').forEach(b => b.setAttribute('aria-checked', b.dataset.f === f));
    $('#qRow').hidden = f === 'png'; $('#bgRow').hidden = f !== 'jpg'; $('#exExt').textContent = EXT[f];
    showPreview();
  }
  $$('.fmt').forEach(b => b.addEventListener('click', () => pickFmt(b.dataset.f)));
  let qT; $('#exQ').addEventListener('input', e => { EX.q = +e.target.value; $('#exQv').textContent = EX.q; clearTimeout(qT); qT = setTimeout(refreshExport, 220); });
  let bT; $('#exBg').addEventListener('input', e => { EX.bg = e.target.value; clearTimeout(bT); bT = setTimeout(refreshExport, 220); });
  let lastFocus = null;
  function openExport() { if (!S.cv) return; lastFocus = document.activeElement; $('#exModal').hidden = false; $('#exName').value = S.name; pickFmt(EX.fmt); refreshExport(); $('#exBaixar').focus(); }
  function closeExport() { $('#exModal').hidden = true; EX.seq++; if (lastFocus) lastFocus.focus(); }
  $$('[data-close]').forEach(b => b.addEventListener('click', closeExport));
  $('#exModal').addEventListener('click', e => { if (e.target.id === 'exModal') closeExport(); });

  function nomeArquivoBase() {
    return ($('#exName').value || '').trim().replace(/[\\/:*?"<>|]+/g, '-').replace(/\.(png|jpe?g|webp)$/i, '') || 'imagem';
  }
  $('#exBaixar').addEventListener('click', () => {
    const blob = EX.blobs[EX.fmt]; if (!blob) return;
    const base = nomeArquivoBase();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = base + EXT[EX.fmt];
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    S.dirty = false;
    closeExport();
    toast(`${base}${EXT[EX.fmt]} baixado · ${fmtB(blob.size)}`);
  });

  /* ---------- tela cheia ---------- */
  const FS_IN = 'M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5', FS_OUT = 'M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5';
  function syncFs() { const on = !!document.fullscreenElement; $('#fsPath').setAttribute('d', on ? FS_OUT : FS_IN); const l = on ? 'Sair da tela cheia' : 'Tela cheia'; $('#fsBtn').title = l; $('#fsBtn').setAttribute('aria-label', l); setTimeout(() => { if (S.fit) render(); }, 60); }
  $('#fsBtn').addEventListener('click', async () => {
    try { if (document.fullscreenElement) await document.exitFullscreen(); else await document.documentElement.requestFullscreen(); }
    catch (_) { toast('Este navegador não permitiu a tela cheia aqui.'); }
  });
  document.addEventListener('fullscreenchange', syncFs);

  /* ---------- voltar ao sistema ---------- */
  const BACK_URL = <?= json_encode(url('/dashboard')) ?>;
  function leave() {
    $('#leaveModal').hidden = true;
    if (document.fullscreenElement) document.exitFullscreen().catch(() => {});
    window.top.location.href = BACK_URL;
  }
  $('#backBtn').addEventListener('click', () => { if (S.dirty) { $('#leaveModal').hidden = false; $('#leaveGo').focus(); } else leave(); });
  $('#leaveGo').addEventListener('click', () => { S.dirty = false; leave(); });
  $$('[data-leave-close]').forEach(b => b.addEventListener('click', () => { $('#leaveModal').hidden = true; }));

  /* ---------- botões e atalhos ---------- */
  $('#tCrop').addEventListener('click', () => setTool('crop'));
  $('#tResize').addEventListener('click', () => setTool('resize'));
  $('#tRotate').addEventListener('click', rotate);
  $('#tUndo').addEventListener('click', undo);
  $('#restore').addEventListener('click', restore);
  $('#cropApply').addEventListener('click', applyCrop);
  $('#cropReset').addEventListener('click', () => { if (!S.cv) return; resetCrop(); drawCrop(); syncCrop(); });
  $('#resApply').addEventListener('click', applyResize);
  $('#resReset').addEventListener('click', () => { if (S.cv) setRes(S.cv.width, S.cv.height); });
  $('#exportBtn').addEventListener('click', openExport);
  $('#zIn').addEventListener('click', () => zoomStep(1)); $('#zOut').addEventListener('click', () => zoomStep(-1));
  $('#zFit').addEventListener('click', () => { if (!S.cv) return; S.fit = true; render(); });
  $('#z100').addEventListener('click', () => { if (!S.cv) return; S.fit = false; S.zoom = 1; applyZoom(); });
  stage.addEventListener('wheel', e => { if (!e.ctrlKey && !e.metaKey) return; e.preventDefault(); zoomStep(e.deltaY < 0 ? 1 : -1); }, { passive: false });
  window.addEventListener('resize', () => { if (S.fit) render(); });

  document.addEventListener('keydown', e => {
    const typing = /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName);
    const mod = e.ctrlKey || e.metaKey, k = e.key.toLowerCase();
    if (!$('#exModal').hidden) { if (e.key === 'Escape') closeExport(); return; }
    if (!$('#leaveModal').hidden) { if (e.key === 'Escape') $('#leaveModal').hidden = true; return; }
    if (mod && e.shiftKey && k === 'x') { e.preventDefault(); openExport(); return; }
    if (mod && k === 'z' && !typing) { e.preventDefault(); undo(); return; }
    if (mod && k === 'o') { e.preventDefault(); $('#fileIn').click(); return; }
    if (typing) { if (e.key === 'Enter') e.target.dispatchEvent(new Event('change')); return; }
    if (!S.cv) return;
    if (k === 'c' && !mod) setTool('crop');
    else if (k === 'r' && !mod) setTool('resize');
    else if (e.key === 'Enter') { e.preventDefault(); S.tool === 'crop' ? applyCrop() : applyResize(); }
    else if (e.key === 'Escape' && S.tool === 'crop') { resetCrop(); drawCrop(); syncCrop(); }
    else if (e.key === 'Escape' && S.tool === 'resize') setRes(S.cv.width, S.cv.height);
  });

  let tT; function toast(msg) { const t = $('#toast'); t.textContent = msg; t.removeAttribute('data-off'); clearTimeout(tT); tT = setTimeout(() => t.setAttribute('data-off', ''), 2600); }

  /* início: estado vazio, aguardando o usuário abrir uma imagem */
  showEmpty(true);
})();
</script>
