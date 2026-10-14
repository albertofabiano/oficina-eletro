<?php
// Fixa Indica — layout público (indica.fixaos.com.br). Mesmo CONTRATO de variáveis de SEO já
// usado em layouts/landing.php ($tituloFull/$metaDesc/$canonical/$ogImage/$ogImageWidth/
// $ogImageHeight/$noindex), não o HTML/CSS dele. Visual redesenhado a partir de um protótipo
// fornecido pelo usuário: paleta violeta/laranja sobre fundo claro, tipografia Bricolage
// Grotesque (display) + Inter (corpo) + IBM Plex Mono (rótulos/specs/preço).
$tituloFull = $tituloFull ?? 'Fixa Indica — produtos indicados por técnicos';
$metaDesc   = $metaDesc ?? 'Ferramentas, peças e eletrônicos indicados por técnicos de assistência técnica, com link direto pro Mercado Livre ou AliExpress.';
$canonical  = $canonical ?? indica_url($_SERVER['REQUEST_URI'] ?? '/');
$noindex    = $noindex ?? false;
$faixaFixaosAtiva = \App\Services\Indica\CatalogoService::config('faixa_fixaos_ativa', '1') !== '0';
$whatsappContato   = \App\Services\Indica\CatalogoService::config('whatsapp_contato', '');
$categoriasRodape = $categoriasMenu ?? [];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($tituloFull) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($noindex): ?>
<meta name="robots" content="noindex, follow">
<?php else: ?>
<meta name="robots" content="index, follow">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($tituloFull) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if (!empty($ogImage)): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php if (!empty($ogImageWidth)): ?><meta property="og:image:width" content="<?= (int) $ogImageWidth ?>"><?php endif; ?>
<?php if (!empty($ogImageHeight)): ?><meta property="og:image:height" content="<?= (int) $ogImageHeight ?>"><?php endif; ?>
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fixaos.com.br">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --ink:#17132E; --ink-2:#403A5E; --ink-3:#716B8C;
  --violet:#6D2BE0; --violet-d:#4E17B0; --violet-soft:#F1EBFE;
  --orange:#F2641E; --orange-soft:#FFF0E7;
  --green:#0E7A52; --green-soft:#E6F5EE;
  --paper:#F6F5FA; --surf:#FFFFFF; --line:#E7E4F0; --line-2:#F0EEF6;
  --r:16px;
  --sh:0 1px 2px rgba(23,19,46,.05), 0 14px 32px -20px rgba(23,19,46,.4);
  --sh-lift:0 2px 4px rgba(23,19,46,.06), 0 22px 44px -22px rgba(23,19,46,.5);
}
html{scroll-behavior:smooth}
body{
  font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
  background:var(--paper); color:var(--ink); line-height:1.55; font-size:16px;
  -webkit-font-smoothing:antialiased;
}
h1,h2,h3,h4,.disp{font-family:'Bricolage Grotesque','Inter',sans-serif;letter-spacing:-.03em;line-height:1.08;font-weight:800}
.mono{font-family:'IBM Plex Mono',ui-monospace,Menlo,monospace;font-variant-numeric:tabular-nums}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
button{font:inherit;color:inherit;border:0;background:none;cursor:pointer}
:focus-visible{outline:3px solid var(--violet);outline-offset:3px;border-radius:8px}
.wrap{width:100%;max-width:1180px;margin:0 auto;padding:0 24px}
.eyebrow{font-family:'IBM Plex Mono',monospace;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--ink-3)}

/* ---------- header ---------- */
.hdr{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
.hdr-in{display:flex;align-items:center;gap:22px;height:66px}
.logo{display:flex;align-items:baseline;gap:5px;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:21px;letter-spacing:-.045em;flex:0 0 auto}
.logo .a{color:var(--violet)} .logo .b{color:var(--orange)}
.logo .dot{width:6px;height:6px;border-radius:50%;background:var(--orange);display:inline-block;margin-left:1px}
.nav{display:flex;gap:4px;margin-left:auto}
.nav a{padding:8px 13px;border-radius:9px;font-size:14.5px;font-weight:500;color:var(--ink-2)}
.nav a:hover{background:var(--paper);color:var(--ink)}
.hdr-cta{padding:9px 16px;border-radius:10px;background:var(--ink);color:#fff;font-weight:600;font-size:14px}
.hdr-cta:hover{background:var(--violet)}
.hdr-busca{display:flex;align-items:center;gap:0;background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:0 4px 0 12px;max-width:260px}
.hdr-busca input{flex:1;border:0;outline:0;background:none;padding:9px 6px;font-size:13.5px;min-width:0;color:var(--ink)}
.hdr-busca button{padding:7px 9px;color:var(--ink-3)}
.burger{display:none;width:40px;height:40px;border-radius:10px;align-items:center;justify-content:center;margin-left:auto}
.burger span{display:block;width:19px;height:2px;background:var(--ink);box-shadow:0 -6px 0 var(--ink),0 6px 0 var(--ink)}
.hdr.nav-open .mobile-panel{display:flex}
.mobile-panel{display:none;flex-direction:column;gap:4px;padding:10px 0 16px;border-top:1px solid var(--line)}
.mobile-panel a.nav-link{padding:10px 4px;font-weight:600;font-size:15px;color:var(--ink-2)}
.mobile-panel .hdr-cta{align-self:flex-start;margin-top:6px}

/* ---------- régua: motivo visual da marca ---------- */
.rule{height:13px;background-image:
  repeating-linear-gradient(to right,var(--line) 0 1px,transparent 1px 11px);
  background-position:bottom;background-repeat:repeat-x;background-size:auto 7px;
  border-bottom:1px solid var(--line);}
.rule.on-dark{background-image:repeating-linear-gradient(to right,rgba(255,255,255,.26) 0 1px,transparent 1px 11px);border-bottom-color:rgba(255,255,255,.16)}

/* ---------- hero (só home) ---------- */
.hero{position:relative;background:var(--ink);color:#fff;overflow:hidden;padding:56px 0 0}
.hero .traces{position:absolute;inset:0;opacity:.5;pointer-events:none}
.hero-in{position:relative;display:grid;grid-template-columns:1.25fr .85fr;gap:56px;align-items:center;padding-bottom:50px}
.tagline{display:inline-flex;align-items:center;gap:9px;padding:6px 13px 6px 7px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16);font-size:12.5px;font-weight:600;color:#DCD6F5;margin-bottom:22px}
.tagline i{width:22px;height:22px;border-radius:50%;background:var(--orange);display:grid;place-items:center;font-size:12px;font-style:normal}
.hero h1{font-size:clamp(32px,4.4vw,52px);color:#fff}
.hero h1 em{font-style:normal;color:#C6B2FF}
.hero p.lead{margin-top:20px;font-size:17.5px;line-height:1.55;color:#BFB8DD;max-width:46ch}
.search{margin-top:30px;display:flex;background:#fff;border-radius:14px;padding:6px;box-shadow:0 20px 50px -20px rgba(0,0,0,.65);max-width:560px}
.search input{flex:1;border:0;outline:0;padding:13px 14px;font-size:15.5px;color:var(--ink);background:none;min-width:0}
.search input::placeholder{color:#9A94B2}
.search button{background:var(--violet);color:#fff;padding:0 22px;border-radius:10px;font-weight:700;font-size:14.5px;display:flex;align-items:center;gap:8px}
.search button:hover{background:var(--violet-d)}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.chips a{padding:7px 13px;border-radius:999px;border:1px solid rgba(255,255,255,.2);font-size:13px;font-weight:500;color:#D5CFEC}
.chips a:hover{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.4);color:#fff}

.cred{background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.13);border-radius:var(--r);padding:24px}
.cred .who{display:flex;gap:13px;align-items:center;padding-bottom:18px;border-bottom:1px solid rgba(255,255,255,.12)}
.cred .av{width:50px;height:50px;border-radius:14px;background:linear-gradient(140deg,var(--violet),var(--orange));display:grid;place-items:center;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;flex:0 0 50px}
.cred .who b{display:block;font-size:15.5px;font-weight:700;letter-spacing:-.01em}
.cred .who span{font-size:12.5px;color:#A8A0C9}
.cred ul{list-style:none;margin-top:16px;display:grid;gap:12px}
.cred li{display:flex;gap:11px;font-size:14px;color:#D7D2EC;line-height:1.4}
.cred li svg{flex:0 0 18px;margin-top:2px}
.cred li b{color:#fff;font-weight:600}

.assure{background:#120F25;border-top:1px solid rgba(255,255,255,.1)}
.assure-in{display:grid;grid-template-columns:repeat(3,1fr);gap:2px}
.assure div{padding:20px 24px;display:flex;gap:12px;align-items:center;color:#CFC9E8;font-size:13.5px;line-height:1.35}
.assure div+div{box-shadow:-1px 0 0 rgba(255,255,255,.09)}
.assure b{color:#fff;font-weight:600;display:block;font-size:14px;letter-spacing:-.01em}

/* ---------- pagehead (páginas que não são a home) ---------- */
.pagehead{background:var(--ink);color:#fff;padding:36px 0 32px}
.pagehead .crumbs{font-size:13px;color:#A8A0C9;margin-bottom:10px}
.pagehead .crumbs a{color:#C6B2FF}
.pagehead .crumbs a:hover{color:#fff}
.pagehead h1{font-size:clamp(24px,3vw,34px);color:#fff}
.pagehead p{color:#BFB8DD;margin-top:8px;max-width:64ch;font-size:15px}

/* ---------- seções ---------- */
section{padding:60px 0}
.sec-h{display:flex;align-items:flex-end;gap:20px;margin-bottom:28px;flex-wrap:wrap}
.sec-h h2{font-size:clamp(23px,2.8vw,32px)}
.sec-h p{color:var(--ink-3);font-size:15.5px;margin-top:9px;max-width:56ch}
.sec-h .more{margin-left:auto;font-weight:600;font-size:14.5px;color:var(--violet);display:inline-flex;align-items:center;gap:6px}
.sec-h .more:hover{gap:10px}

/* categorias */
.cats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.cat{background:var(--surf);border:1px solid var(--line);border-radius:var(--r);padding:22px 20px;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;display:block}
.cat:hover{transform:translateY(-3px);box-shadow:var(--sh-lift);border-color:#D9D2F0}
.cat .ic{width:46px;height:46px;border-radius:13px;background:var(--violet-soft);display:grid;place-items:center;margin-bottom:16px;font-size:20px}
.cat h3{font-size:17px;font-weight:700;letter-spacing:-.02em}
.cat p{font-size:13.5px;color:var(--ink-3);margin-top:5px}
.cat .n{font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--violet);font-weight:600;margin-top:12px;display:block}

/* produtos */
.prods{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.prod{background:var(--surf);border:1px solid var(--line);border-radius:var(--r);overflow:hidden;display:flex;flex-direction:column;transition:transform .18s ease,box-shadow .18s ease}
.prod:hover{transform:translateY(-4px);box-shadow:var(--sh-lift)}
.prod .shot{position:relative;aspect-ratio:4/3;background:linear-gradient(170deg,#FAF9FD,#EFECF8);display:grid;place-items:center;padding:16px;border-bottom:1px solid var(--line-2)}
.prod .shot img{max-height:100%;width:auto;object-fit:contain;mix-blend-mode:multiply}
.prod .shot .sem-foto{color:#B9B2D2;font-size:11px;font-family:'IBM Plex Mono',monospace}
.badge{position:absolute;top:11px;left:11px;display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border-radius:7px;font-size:11px;font-weight:700;letter-spacing:.01em;background:var(--ink);color:#fff}
.badge.bench{background:var(--green-soft);color:var(--green)}
.badge.hot{background:var(--orange-soft);color:#C2460D}
.prod .body{padding:16px;display:flex;flex-direction:column;flex:1;gap:10px}
.prod h3{font-family:'Inter',sans-serif;font-size:14.5px;font-weight:600;letter-spacing:-.01em;line-height:1.35;color:var(--ink);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.3em}
.preco{margin-top:-4px}
.preco strong{font-family:'Bricolage Grotesque',sans-serif;font-size:19px;font-weight:800;display:block}
.preco span{font-size:11px;color:var(--ink-3)}
.verdict{font-size:13px;color:var(--ink-2);line-height:1.5;padding-left:11px;border-left:2px solid var(--violet)}
.verdict b{font-weight:600}
.specs{display:flex;flex-wrap:wrap;gap:6px;margin-top:auto}
.specs span{font-family:'IBM Plex Mono',monospace;font-size:10.5px;font-weight:600;padding:3px 7px;border-radius:6px;background:var(--paper);color:var(--ink-3);border:1px solid var(--line-2)}
.buy{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border-radius:11px;background:var(--ink);color:#fff;font-weight:600;font-size:14px;transition:background .16s;margin-top:auto}
.buy:hover{background:var(--violet)}
.buy.ae{background:#D62D13} .buy.ae:hover{background:var(--ink)}
.ml-note{display:flex;align-items:center;justify-content:center;gap:5px;font-size:11px;color:var(--ink-3);margin-top:-3px;text-align:center}

/* lojas e filtro */
.storebar{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:22px}
.sfilter{padding:8px 16px;border-radius:999px;border:1px solid var(--line);background:var(--surf);font-size:13.5px;font-weight:600;color:var(--ink-2);display:inline-flex;align-items:center;gap:7px;transition:all .15s}
.sfilter:hover{border-color:#C9BFEA}
.sfilter.on{background:var(--ink);color:#fff;border-color:var(--ink)}
.sfilter i{width:7px;height:7px;border-radius:50%;flex:0 0 7px}
.sfilter .ml-d{background:#F5C400} .sfilter .ae-d{background:#E03218}
.meta{display:flex;align-items:center;justify-content:space-between;gap:8px}
.store{display:inline-flex;align-items:center;gap:5px;font-family:'IBM Plex Mono',monospace;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;padding:3px 8px;border-radius:6px;white-space:nowrap}
.store i{width:6px;height:6px;border-radius:50%;flex:0 0 6px}
.store.ml{background:#FFF8CC;color:#7A6500} .store.ml i{background:#F5C400}
.store.ae{background:#FFEBE6;color:#B82B06} .store.ae i{background:#E03218}
.ship{font-size:11px;color:var(--ink-3);white-space:nowrap;font-weight:500}
.prod[hidden]{display:none}

/* comparativo de lojas */
.shops{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.shop{background:var(--surf);border:1px solid var(--line);border-radius:var(--r);padding:26px;position:relative;overflow:hidden}
.shop::before{content:'';position:absolute;top:0;left:0;right:0;height:4px}
.shop.ml::before{background:#F5C400} .shop.ae::before{background:#E03218}
.shop h3{font-size:20px;display:flex;align-items:center;gap:9px;margin-bottom:5px}
.shop .when{font-size:13.5px;color:var(--ink-3);margin-bottom:18px}
.shop dl{display:grid;gap:13px}
.shop dt{font-family:'IBM Plex Mono',monospace;font-size:10.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-3)}
.shop dd{font-size:14.5px;color:var(--ink-2);line-height:1.5;margin-top:2px}
.shop dd b{color:var(--ink);font-weight:600}

/* critérios */
.crit{background:var(--surf);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.crit-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:0}
.crit-grid > div{padding:30px 30px 30px 0}
.crit-grid > div+div{padding-left:30px;border-left:1px solid var(--line-2)}
.crit .num{font-family:'IBM Plex Mono',monospace;font-size:12px;font-weight:600;color:var(--orange);letter-spacing:.1em}
.crit h3{font-size:19px;margin:10px 0 9px}
.crit p{font-size:14.5px;color:var(--ink-2);line-height:1.6}

/* transparência */
.disclose{display:grid;grid-template-columns:auto 1fr;gap:18px;align-items:start;background:var(--violet-soft);border:1px solid #DED1FB;border-radius:var(--r);padding:24px 26px;margin-top:34px}
.disclose .ic{width:40px;height:40px;border-radius:12px;background:var(--violet);display:grid;place-items:center;flex:0 0 40px}
.disclose h3{font-size:18px;margin-bottom:7px}
.disclose p{font-size:14.5px;color:var(--ink-2);line-height:1.6;max-width:72ch}

/* quem indica */
.about{display:grid;grid-template-columns:.9fr 1.1fr;gap:48px;align-items:center}
.about-card{background:var(--ink);color:#fff;border-radius:22px;padding:34px;position:relative;overflow:hidden}
.about-card .traces{position:absolute;inset:0;opacity:.45}
.about-card > *{position:relative}
.about-card .av{width:62px;height:62px;border-radius:18px;background:linear-gradient(140deg,var(--violet),var(--orange));display:grid;place-items:center;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:22px;margin-bottom:18px}
.about-card h3{font-size:22px;margin-bottom:6px}
.about-card .role{font-size:14px;color:#A8A0C9;margin-bottom:22px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding-top:20px;border-top:1px solid rgba(255,255,255,.14)}
.stats .v{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;letter-spacing:-.04em}
.stats .l{font-size:11.5px;color:#A8A0C9;line-height:1.3;margin-top:3px}
.about h2{font-size:clamp(25px,3vw,34px);margin-bottom:18px}
.about p{font-size:16px;color:var(--ink-2);line-height:1.65;margin-bottom:14px}
.about .sig{font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;margin-top:22px;color:var(--ink)}

/* guias */
.guides{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.guide{background:var(--surf);border:1px solid var(--line);border-radius:var(--r);padding:24px;display:flex;flex-direction:column;gap:12px;transition:transform .18s ease,box-shadow .18s ease}
.guide:hover{transform:translateY(-3px);box-shadow:var(--sh-lift)}
.guide h3{font-size:18.5px;line-height:1.25}
.guide p{font-size:14px;color:var(--ink-3);line-height:1.55;flex:1}
.guide .go{font-weight:600;font-size:14px;color:var(--violet);display:inline-flex;gap:6px;align-items:center}
.guide:hover .go{gap:10px}

/* faixa fixaos */
.band{background:linear-gradient(120deg,var(--violet-d),var(--violet) 55%,#8B46F0);color:#fff;border-radius:22px;padding:40px 44px;display:grid;grid-template-columns:1fr auto;gap:32px;align-items:center}
.band h2{font-size:clamp(21px,2.5vw,29px);margin-bottom:10px;color:#fff}
.band p{color:#E4DAFB;font-size:15.5px;max-width:58ch;line-height:1.6}
.band a{background:#fff;color:var(--violet-d);padding:14px 26px;border-radius:12px;font-weight:700;font-size:15px;white-space:nowrap;display:inline-block}
.band a:hover{background:var(--ink);color:#fff}

/* contato/whatsapp */
.wa-box{background:var(--surf);border:1px solid var(--line);border-radius:22px;padding:40px;text-align:center}
.wa-box h2{font-size:clamp(22px,2.5vw,29px)}
.wa-box p{color:var(--ink-3);margin:12px auto 22px;max-width:50ch;font-size:15.5px}
.wa-box a.btn-wa{display:inline-flex;align-items:center;gap:9px;background:#128C4A;color:#fff;padding:13px 26px;border-radius:12px;font-weight:700;font-size:15px}
.wa-box a.btn-wa:hover{background:var(--ink)}

/* produto */
.produto-grid{display:grid;grid-template-columns:minmax(0,380px) 1fr 260px;gap:32px;align-items:start}
.produto-sidebar{position:sticky;top:90px;display:flex;flex-direction:column;gap:14px}
.banner-promo{display:flex;flex-direction:column;gap:4px;border-radius:14px;padding:18px;color:#fff}
.banner-promo strong{font-size:.95rem;line-height:1.3}
.banner-promo span{font-size:.82rem;opacity:.92;line-height:1.4}
.banner-promo .seta{margin-top:6px;font-weight:700;opacity:1}
.foto-produto{position:relative;aspect-ratio:1;background:linear-gradient(170deg,#FAF9FD,#EFECF8);border:1px solid var(--line);border-radius:var(--r);overflow:hidden}
.carrossel-img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;mix-blend-mode:multiply;opacity:0;transition:opacity .2s;pointer-events:none;padding:18px}
.carrossel-img.ativa{opacity:1}
.carrossel-seta{position:absolute;top:50%;transform:translateY(-50%);width:36px;height:36px;border-radius:999px;border:none;background:rgba(255,255,255,.95);box-shadow:var(--sh);font-size:1.4rem;line-height:1;color:var(--ink);cursor:pointer;display:flex;align-items:center;justify-content:center}
.carrossel-seta:hover{background:#fff}
.carrossel-prev{left:10px}
.carrossel-next{right:10px}
.carrossel-thumbs{display:flex;gap:8px;margin-top:10px;overflow-x:auto;padding-bottom:2px}
.carrossel-thumb{width:58px;height:58px;flex:0 0 auto;object-fit:contain;background:var(--paper);border-radius:8px;border:2px solid var(--line);cursor:pointer;opacity:.7}
.carrossel-thumb.ativa{border-color:var(--violet);opacity:1}
@media (max-width:900px){.produto-grid{grid-template-columns:1fr}.produto-sidebar{position:static;flex-direction:row;flex-wrap:wrap}.produto-sidebar .banner-promo{flex:1 1 220px}}

@media(max-width:980px){
  .hero-in{grid-template-columns:1fr;gap:38px;padding-bottom:42px}
  .cats,.prods{grid-template-columns:repeat(2,1fr)}
  .crit-grid,.guides,.about,.assure-in{grid-template-columns:1fr}
  .crit-grid > div{padding:26px 0}
  .crit-grid > div+div{padding-left:0;border-left:0;border-top:1px solid var(--line-2)}
  .assure div+div{box-shadow:0 -1px 0 rgba(255,255,255,.09)}
  .f-grid{grid-template-columns:1fr 1fr}
  .band{grid-template-columns:1fr;padding:32px}
  .shops{grid-template-columns:1fr}
}
@media(max-width:620px){
  .nav,.hdr-cta,.hdr-busca{display:none} .burger{display:flex}
  section{padding:44px 0} .wrap{padding:0 18px}
  .hero{padding-top:40px}
  .cats,.prods{grid-template-columns:1fr 1fr;gap:11px}
  .prod .body{padding:13px} .cat{padding:17px 15px}
  .f-grid{grid-template-columns:1fr;gap:26px}
  .wa-box{padding:28px 20px}
}
@media(prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}

/* rodapé */
footer{background:var(--ink);color:#B6AFD4;padding:56px 0 28px;margin-top:10px}
.f-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:36px;padding-bottom:34px;border-bottom:1px solid rgba(255,255,255,.12)}
footer .logo{color:#fff;margin-bottom:14px}
footer p{font-size:14px;line-height:1.6;max-width:38ch}
footer h4{color:#fff;font-family:'Inter',sans-serif;font-size:12.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-bottom:14px}
footer ul{list-style:none;display:grid;gap:9px}
footer ul a{font-size:14px}
footer ul a:hover{color:#fff}
.f-bot{display:flex;justify-content:space-between;gap:20px;padding-top:22px;font-size:12.5px;flex-wrap:wrap;line-height:1.6}
.f-bot a{text-decoration:underline;text-underline-offset:3px}
</style>
</head>
<body>

<header class="hdr" id="hdrIndica">
  <div class="wrap">
    <div class="hdr-in">
      <a href="/" class="logo"><span class="a">Fixa</span> <span class="b">Indica</span><span class="dot"></span></a>
      <nav class="nav">
        <a href="/#indicados">Indicados</a>
        <a href="/#categorias">Categorias</a>
        <a href="/#lojas">Onde comprar</a>
        <a href="/#criterio">Como escolhemos</a>
        <a href="/guias">Guias</a>
      </nav>
      <form class="hdr-busca" action="/busca" method="GET" role="search">
        <input type="search" name="q" placeholder="Buscar..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Buscar">
        <button type="submit" aria-label="Buscar">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </button>
      </form>
      <a href="https://fixaos.com.br" target="_blank" rel="noopener" class="hdr-cta">Conheça o FixaOS</a>
      <button class="burger" aria-label="Abrir menu" onclick="document.getElementById('hdrIndica').classList.toggle('nav-open')"><span></span></button>
    </div>
    <div class="mobile-panel">
      <a class="nav-link" href="/#indicados">Indicados</a>
      <a class="nav-link" href="/#categorias">Categorias</a>
      <a class="nav-link" href="/#lojas">Onde comprar</a>
      <a class="nav-link" href="/#criterio">Como escolhemos</a>
      <a class="nav-link" href="/guias">Guias</a>
      <form action="/busca" method="GET" role="search" style="display:flex;gap:6px;margin-top:6px">
        <input type="search" name="q" placeholder="Buscar..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Buscar" style="flex:1;padding:9px 12px;border:1px solid var(--line);border-radius:9px">
        <button type="submit" style="background:var(--ink);color:#fff;padding:0 14px;border-radius:9px">Ir</button>
      </form>
      <a href="https://fixaos.com.br" target="_blank" rel="noopener" class="hdr-cta">Conheça o FixaOS</a>
    </div>
  </div>
</header>

<main>
<?= $content() ?>
</main>

<footer>
  <div class="wrap">
    <?php if ($faixaFixaosAtiva): ?>
    <section style="padding:0 0 34px">
      <div class="band">
        <div>
          <h2>Tem assistência técnica? O sistema é nosso também.</h2>
          <p>Ordens de serviço, PDV, estoque, financeiro e página pública da sua assistência no Google. O Fixa Indica é um produto do ecossistema FixaOS.</p>
        </div>
        <a href="https://fixaos.com.br" target="_blank" rel="noopener">Conhecer o FixaOS →</a>
      </div>
    </section>
    <?php endif; ?>

    <div class="f-grid">
      <div>
        <div class="logo"><span style="color:#A78BFA">Fixa</span> <span style="color:var(--orange)">Indica</span></div>
        <p>Vitrine de ferramentas, peças e eletrônicos indicados por técnicos de assistência técnica. Um produto do ecossistema FixaOS.</p>
        <?php if ($whatsappContato !== ''): ?>
        <p style="margin-top:10px"><a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsappContato)) ?>" target="_blank" rel="noopener" style="color:#fff;text-decoration:underline">Fale com a gente pelo WhatsApp</a></p>
        <?php endif; ?>
      </div>
      <div>
        <h4>Categorias</h4>
        <ul>
          <?php foreach (array_slice($categoriasRodape, 0, 5) as $c): ?>
            <li><a href="/categoria/<?= e($c['slug']) ?>"><?= e($c['nome']) ?></a></li>
          <?php endforeach; ?>
          <?php if (empty($categoriasRodape)): ?><li><a href="/">Ver vitrine</a></li><?php endif; ?>
        </ul>
      </div>
      <div>
        <h4>Conteúdo</h4>
        <ul>
          <li><a href="/guias">Guias</a></li>
          <li><a href="/#lojas">Mercado Livre ou AliExpress</a></li>
          <li><a href="/#criterio">Como escolhemos</a></li>
          <li><a href="/busca">Buscar</a></li>
        </ul>
      </div>
      <div>
        <h4>FixaOS</h4>
        <ul>
          <li><a href="https://fixaos.com.br" target="_blank" rel="noopener">Sistema para assistências</a></li>
          <li><a href="https://fixaos.com.br/assistencias" target="_blank" rel="noopener">Diretório de assistências</a></li>
          <li><a href="https://carteirafixa.fixaos.com.br/" target="_blank" rel="noopener">Carteira Fixa</a></li>
        </ul>
      </div>
    </div>

    <div class="f-bot">
      <span>© <?= date('Y') ?> Fixa Indica · indica.fixaos.com.br · Todos os direitos reservados</span>
      <span style="max-width:62ch">Participamos dos programas de afiliados do <strong style="color:#fff">Mercado Livre</strong> e do <strong style="color:#fff">AliExpress</strong> e podemos receber comissão pelas compras feitas por links desta página, <strong style="color:#fff">sem custo adicional para você</strong>. Preços, estoque, prazos, impostos de importação e garantia são definidos e exibidos pela loja em que a compra é concluída. <a href="https://fixaos.com.br/privacidade" target="_blank" rel="noopener">Política de privacidade</a></span>
    </div>
  </div>
</footer>
</body>
</html>
