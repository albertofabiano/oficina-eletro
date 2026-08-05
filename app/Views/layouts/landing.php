<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Google tag (gtag.js) — rastreamento de conversão do Google Ads -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-18351792124"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'AW-18351792124');
</script>
<meta name="google-adsense-account" content="ca-pub-2477643924516118">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex, follow">
<?php endif; ?>
<title><?php
    if (!empty($tituloFull))      { echo e($tituloFull); }
    elseif (!empty($titulo))      { echo e($titulo) . ' — FixaOS — Gestão para Assistências Técnicas'; }
    else                          { echo 'FixaOS — Gestão para Assistências Técnicas'; }
?></title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
<link rel="shortcut icon" href="/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#1e3a5f">
<?php
  $__canon   = 'https://fixaos.com.br' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
  $__ogTitle = !empty($tituloFull) ? $tituloFull : (!empty($titulo) ? $titulo . ' — FixaOS — Gestão para Assistências Técnicas' : 'FixaOS — Gestão para Assistências Técnicas');
  $__ogDesc  = $metaDesc ?? 'Sistema completo de gestão para assistências técnicas: ordens de serviço, PDV, clientes, estoque e financeiro. Teste grátis 7 dias, sem cartão.';
?>
<meta name="description" content="<?= e($__ogDesc) ?>">
<link rel="canonical" href="<?= e($__canon) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="FixaOS">
<meta property="og:locale" content="pt_BR">
<meta property="og:title" content="<?= e($__ogTitle) ?>">
<meta property="og:description" content="<?= e($__ogDesc) ?>">
<meta property="og:url" content="<?= e($__canon) ?>">
<meta property="og:image" content="https://fixaos.com.br/apple-touch-icon.png">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= e($__ogTitle) ?>">
<meta name="twitter:description" content="<?= e($__ogDesc) ?>">
<meta name="twitter:image" content="https://fixaos.com.br/apple-touch-icon.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0b0d10;
  --bg2:#111318;
  --bg3:#181b22;
  --border:rgba(255,255,255,.07);
  --text:#e2e8f0;
  --muted:#64748b;
  --brand:#f97316;
  --brand2:#ea6c0a;
  --blue:#3b82f6;
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased}
a{text-decoration:none}

/* NAV */
.nav-land{
  position:sticky;top:0;z-index:999;
  background:rgba(11,13,16,.92);
  backdrop-filter:blur(16px);
  border-bottom:1px solid var(--border);
  padding:.9rem 0;
}
.nav-link-land{color:#94a3b8;font-size:.88rem;font-weight:500;transition:color .15s}
.nav-link-land:hover{color:#fff}

/* HERO */
.hero{
  background:var(--bg);
  padding:7rem 0 5rem;
  position:relative;overflow:hidden;
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 80% 60% at 60% 40%, rgba(249,115,22,.08) 0%, transparent 70%),
             radial-gradient(ellipse 60% 50% at 20% 80%, rgba(59,130,246,.06) 0%, transparent 60%);
}
.hero-tag{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(249,115,22,.1);border:1px solid rgba(249,115,22,.25);
  color:#fb923c;border-radius:100px;font-size:.78rem;font-weight:600;
  padding:.35rem 1rem;margin-bottom:1.5rem;
}
.hero h1{font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.02em}
.hero h1 em{font-style:normal;color:var(--brand)}
.hero-sub{color:#94a3b8;font-size:1.1rem;line-height:1.7;max-width:500px;margin-top:1.2rem}
.btn-brand{background:var(--brand);color:#fff;font-weight:700;border:none;border-radius:10px;padding:.75rem 1.8rem;font-size:1rem;transition:.2s}
.btn-brand:hover{background:var(--brand2);color:#fff;transform:translateY(-1px)}
.btn-ghost{background:rgba(255,255,255,.06);color:#fff;font-weight:600;border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:.75rem 1.8rem;font-size:1rem;transition:.2s}
.btn-ghost:hover{background:rgba(255,255,255,.1);color:#fff}
.btn-demo{
  background:linear-gradient(135deg,#2dd4bf,#0891b2);color:#06222a;font-weight:800;
  border:none;border-radius:10px;padding:.75rem 1.4rem;font-size:.92rem;white-space:nowrap;
  box-shadow:0 0 0 0 rgba(45,212,191,.55);animation:pulseDemo 2.2s infinite;transition:transform .15s ease;
}
.btn-demo:hover{color:#06222a;transform:translateY(-1px)}
@keyframes pulseDemo{
  0%{box-shadow:0 0 0 0 rgba(45,212,191,.55)}
  70%{box-shadow:0 0 0 9px rgba(45,212,191,0)}
  100%{box-shadow:0 0 0 0 rgba(45,212,191,0)}
}
@media (prefers-reduced-motion: reduce){ .btn-demo{ animation:none } }

/* DASHBOARD MOCK */
.dash-mock{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:16px;overflow:hidden;
  box-shadow:0 40px 80px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.04);
}
.dash-topbar{background:#0f1117;border-bottom:1px solid var(--border);padding:.6rem 1rem;display:flex;align-items:center;gap:6px}
.dash-dot{width:10px;height:10px;border-radius:50%}

/* LOGOS BAR */
.logos-bar{border-top:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--bg2);padding:2rem 0}
.logos-bar span{color:var(--muted);font-size:.8rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase}

/* FEATURES */
.feat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem}
.feat-card{
  background:var(--bg2);border:1px solid var(--border);border-radius:16px;
  padding:1.8rem;transition:.2s;
}
.feat-card:hover{border-color:rgba(249,115,22,.3);background:var(--bg3)}
.feat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem}
.feat-title{color:#fff;font-weight:700;font-size:1rem;margin-bottom:.4rem}
.feat-desc{color:var(--muted);font-size:.87rem;line-height:1.6}

/* SECTION LABELS */
.sec-tag{color:var(--brand);font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.6rem}
.sec-title{color:#fff;font-size:clamp(1.8rem,3vw,2.6rem);font-weight:800;letter-spacing:-.02em;line-height:1.2}
.sec-sub{color:var(--muted);font-size:1rem;line-height:1.7;margin-top:.8rem}

/* STEPS */
.step-wrap{display:flex;gap:1.5rem;align-items:flex-start}
.step-num{width:44px;height:44px;border-radius:50%;background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.3);color:var(--brand);font-weight:800;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.step-line{flex:1;border-top:1px dashed var(--border);margin-top:22px}

/* PRICING */
.price-toggle{background:var(--bg2);border:1px solid var(--border);border-radius:100px;display:inline-flex;padding:4px;gap:2px}
.ptab{padding:.4rem 1.2rem;border-radius:100px;font-size:.85rem;font-weight:600;color:var(--muted);cursor:pointer;transition:.2s;border:none;background:none}
.ptab.active{background:var(--brand);color:#fff}
.ptab:hover:not(.active){color:#fff}
.price-card{background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:2rem;transition:.2s;position:relative}
.price-card:hover{border-color:rgba(249,115,22,.25)}
.price-card.featured{border-color:var(--brand);background:linear-gradient(135deg,rgba(249,115,22,.06) 0%,var(--bg2) 60%)}
.price-badge{display:inline-block;background:var(--brand);color:#fff;font-size:.72rem;font-weight:700;padding:.25rem .9rem;border-radius:100px;white-space:nowrap;margin-bottom:.8rem}
.price-val{font-size:2.8rem;font-weight:900;color:#fff;line-height:1.2;margin-top:.4rem;display:flex;align-items:flex-start;gap:.15rem}
.price-val sup{font-size:1rem;font-weight:600;color:#94a3b8;margin-top:.45rem;vertical-align:baseline;line-height:1}
.price-val small{font-size:.9rem;font-weight:400;color:var(--muted)}
.price-economy{background:rgba(34,197,94,.1);color:#4ade80;border-radius:6px;font-size:.75rem;font-weight:700;padding:.2rem .6rem;display:inline-block;margin-top:.5rem}
.price-item{display:flex;align-items:flex-start;gap:.6rem;font-size:.88rem;color:#cbd5e1;margin-bottom:.6rem}
.price-item i{color:#4ade80;flex-shrink:0;margin-top:2px}

/* TESTIMONIALS */
.testi-card{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:1.8rem}
.testi-stars{color:#f59e0b;font-size:.9rem;margin-bottom:.8rem}
.testi-text{color:#cbd5e1;font-size:.92rem;line-height:1.7;margin-bottom:1.2rem}
.testi-author{color:#fff;font-weight:600;font-size:.88rem}
.testi-role{color:var(--muted);font-size:.8rem}

/* FAQ */
.faq-item{border-bottom:1px solid var(--border);padding:1.2rem 0}
.faq-q{color:#fff;font-weight:600;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:.95rem}
.faq-a{color:var(--muted);font-size:.88rem;line-height:1.7;padding-top:.8rem;display:none}
.faq-a.open{display:block}

/* CTA */
.cta-sect{background:linear-gradient(135deg,rgba(249,115,22,.15) 0%,rgba(59,130,246,.08) 100%);border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:5rem 0;text-align:center}

/* FOOTER */
footer{background:var(--bg);border-top:1px solid var(--border);padding:3.5rem 0 2rem}
.foot-link{color:var(--muted);font-size:.87rem;display:block;margin-bottom:.5rem;transition:color .15s}
.foot-link:hover{color:#fff}
.foot-title{color:#fff;font-size:.8rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;margin-bottom:1rem}

@media(max-width:768px){
  .hero{padding:4rem 0 3rem}
  .feat-grid{grid-template-columns:1fr}
}
</style>
<?php
// AdSense só nas páginas do DIRETÓRIO (conteúdo com tráfego orgânico).
// Fica fora da landing de vendas, marketplace, fórum e ajuda — que compartilham este layout.
$__adsPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (preg_match('#^/(encontrar|assistencias)(/|$)#', $__adsPath)):
?>
<?php /* AdSense ATIVO no diretório (conteúdo). O loader só carrega a lib — anúncios aparecem onde houver blocos em /master/adsense. Fora da landing de vendas. */ ?>
<?php if (true): ?>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2477643924516118" crossorigin="anonymous"></script>
<?php endif; ?>
<?php endif; ?>
</head>
<body>

<!-- NAV -->
<nav class="nav-land">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= url('/') ?>" aria-label="FixaOS — Página inicial">
      <svg width="110" height="28" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="FixaOS"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
    </a>
    <div class="d-none d-md-flex align-items-center gap-4">
      <a href="<?= url('/') ?>#funcionalidades" class="nav-link-land">Funcionalidades</a>
      <a href="<?= url('/') ?>#como-funciona" class="nav-link-land">Como funciona</a>
      <a href="<?= url('/') ?>#planos" class="nav-link-land">Planos</a>
      <a href="<?= url('/') ?>#faq" class="nav-link-land">FAQ</a>
      <a href="<?= url('/manual') ?>" target="_blank" class="nav-link-land"><i class="bi bi-book-half me-1"></i>Manual</a>
      <a href="<?= url('/forum') ?>" class="nav-link-land"><i class="bi bi-chat-square-text me-1"></i>Fórum</a>
      <a href="<?= url('/assistencias') ?>" class="nav-link-land" style="color:#5eead4"><i class="bi bi-geo-alt-fill me-1"></i>Encontrar Assistência</a>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= url('/login') ?>" class="btn-ghost btn btn-sm px-3 d-none d-sm-inline-block">Entrar</a>
      <?php
        $__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $__isDir = (bool) preg_match('#^/(encontrar|assistencias)(/|$)#', $__path);
        $__isAcompanhar = (bool) preg_match('#^/os/acompanhar(/|$)#', $__path);
      ?>
      <?php if ($__isDir): ?>
      <a href="<?= url('/diretorio/cadastrar') ?>" class="btn-brand btn btn-sm px-3" style="white-space:nowrap"><i class="bi bi-shop-window me-1"></i>Cadastre sua empresa</a>
      <?php else: ?>
      <a href="<?= url('/demo') ?>" class="btn-demo btn btn-sm px-3 d-none d-sm-inline-flex align-items-center"><i class="bi bi-play-circle-fill me-1"></i>Ver demonstração</a>
      <?php if (!$__isAcompanhar): ?>
      <a href="<?= url('/cadastrar') ?>" class="btn-brand btn btn-sm px-3" style="white-space:nowrap"><i class="bi bi-rocket-takeoff-fill me-1"></i>Teste grátis</a>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</nav>

<?php $err = flash('error'); $suc = flash('success'); $inf = flash('info'); ?>
<?php if ($err): ?><div class="alert alert-danger m-3"><?= e($err) ?></div><?php endif; ?>
<?php if ($suc): ?><div class="alert alert-success m-3"><?= e($suc) ?></div><?php endif; ?>
<?php if ($inf): ?><div class="alert alert-info m-3"><?= e($inf) ?></div><?php endif; ?>

<?php ($content)(); ?>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <svg width="100" height="25" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" class="mb-3 d-block"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
        <p style="color:var(--muted);font-size:.87rem;line-height:1.7">Sistema completo para gestão de assistências técnicas.<br>Feito por quem entende do setor.</p>
      </div>
      <div class="col-6 col-md-2">
        <div class="foot-title">Sistema</div>
        <a href="<?= url('/') ?>#funcionalidades" class="foot-link">Funcionalidades</a>
        <a href="<?= url('/') ?>#planos" class="foot-link">Planos</a>
        <a href="<?= url('/cadastrar') ?>" class="foot-link">Criar conta grátis</a>
        <a href="<?= url('/manual') ?>" class="foot-link">Manual do Usuário</a>
        <a href="<?= url('/forum') ?>" class="foot-link">Fórum</a>
        <a href="<?= url('/login') ?>" class="foot-link">Entrar</a>
      </div>
      <div class="col-6 col-md-2">
        <div class="foot-title">Módulos</div>
        <span class="foot-link">Ordens de Serviço</span>
        <span class="foot-link">CRM / Clientes</span>
        <span class="foot-link">Estoque</span>
        <span class="foot-link">Financeiro</span>
        <span class="foot-link">Marketplace</span>
      </div>
      <div class="col-md-4">
        <div class="foot-title">Fique por dentro</div>
        <p style="color:var(--muted);font-size:.85rem;margin-bottom:1rem">Novidades, dicas e atualizações direto no seu e-mail.</p>
        <div class="d-flex gap-2">
          <input type="email" placeholder="seu@email.com" class="form-control form-control-sm" style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:8px">
          <button class="btn-brand btn btn-sm px-3" style="white-space:nowrap">OK</button>
        </div>
      </div>
    </div>
    <hr style="border-color:var(--border)">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="color:var(--muted);font-size:.82rem">
      <span>© <?= date('Y') ?> FixaOS. Todos os direitos reservados.</span>
      <div class="d-flex gap-3">
        <a href="<?= url('/termos') ?>" class="foot-link" style="margin:0">Termos de uso</a>
        <a href="<?= url('/privacidade') ?>" class="foot-link" style="margin:0">Privacidade</a>
      </div>
    </div>
    <div class="mt-3 pt-3" style="border-top:1px solid var(--border);display:flex;flex-wrap:wrap;justify-content:center;gap:1.4rem;color:var(--muted);font-size:.8rem">
      <span><i class="bi bi-shield-lock-fill me-1" style="color:#22c55e"></i>Conexão segura (SSL)</span>
      <span><i class="bi bi-geo-alt-fill me-1" style="color:#2dd4bf"></i>Servidores no Brasil</span>
      <span><i class="bi bi-file-earmark-check-fill me-1" style="color:#818cf8"></i>Conforme a LGPD</span>
    </div>
  </div>
</footer>

<!-- ═══ Banner de Cookies (LGPD) ═══ -->
<div id="cookieBar" style="display:none;position:fixed;left:0;right:0;bottom:0;z-index:2000;background:#111318;border-top:1px solid rgba(249,115,22,.4);box-shadow:0 -8px 24px rgba(0,0,0,.5)">
  <div class="container py-3">
    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
      <div style="flex:1;color:#cbd5e1;font-size:.86rem;line-height:1.6">
        <i class="bi bi-shield-check me-1" style="color:#f97316"></i>
        Usamos cookies para melhorar sua experiência, analisar o tráfego e exibir anúncios. Ao continuar navegando, você concorda com nossa
        <a href="<?= url('/privacidade') ?>" style="color:#fb923c;font-weight:600">Política de Privacidade</a>.
      </div>
      <div class="d-flex gap-2 flex-shrink-0">
        <button onclick="cookieConsent('reject')" class="btn-ghost btn btn-sm px-3">Recusar</button>
        <button onclick="cookieConsent('accept')" class="btn-brand btn btn-sm px-4">Aceitar</button>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  try {
    if (!localStorage.getItem('fixaos_cookie_consent')) {
      document.getElementById('cookieBar').style.display = 'block';
    }
  } catch(e) {}
})();
function cookieConsent(v){
  try { localStorage.setItem('fixaos_cookie_consent', v); } catch(e) {}
  var b = document.getElementById('cookieBar');
  if (b) b.style.display = 'none';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/masks.js') ?>"></script>
<script>
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'})}
  });
});
</script>
<!-- ═══ Botão flutuante WhatsApp ═══ -->
<style>
  #waWidget{position:fixed;right:22px;bottom:22px;z-index:1500;font-family:'Inter',system-ui,sans-serif;transition:bottom .3s}
  #waWidget.wa-lift{bottom:104px}
  .wa-btn{position:relative;width:60px;height:60px;border-radius:50%;border:none;cursor:pointer;background:#25d366;color:#fff;font-size:1.9rem;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(37,211,102,.45);transition:transform .2s}
  .wa-btn:hover{transform:scale(1.06)}
  .wa-btn::before{content:'';position:absolute;inset:0;border-radius:50%;background:#25d366;opacity:.55;animation:waPulse 2.2s ease-out infinite;z-index:-1}
  @keyframes waPulse{0%{transform:scale(1);opacity:.55}70%{transform:scale(1.7);opacity:0}100%{opacity:0}}
  .wa-badge{position:absolute;top:-2px;right:-2px;background:#ef4444;color:#fff;font-size:.62rem;font-weight:800;min-width:18px;height:18px;border-radius:20px;display:flex;align-items:center;justify-content:center;border:2px solid #0b0d10}
  .wa-teaser{position:absolute;right:74px;bottom:14px;background:#fff;color:#0f172a;font-size:.82rem;font-weight:600;padding:.5rem .8rem;border-radius:12px;box-shadow:0 8px 22px rgba(0,0,0,.28);white-space:nowrap;cursor:pointer;opacity:0;transform:translateX(10px);pointer-events:none;transition:.3s}
  .wa-teaser.show{opacity:1;transform:none;pointer-events:auto}
  .wa-teaser::after{content:'';position:absolute;right:-5px;bottom:12px;width:11px;height:11px;background:#fff;transform:rotate(45deg)}
  .wa-card{position:absolute;right:0;bottom:74px;width:320px;max-width:calc(100vw - 44px);background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.45);transform:translateY(14px) scale(.96);opacity:0;pointer-events:none;transform-origin:bottom right;transition:.22s ease}
  .wa-card.open{transform:none;opacity:1;pointer-events:auto}
  .wa-head{background:linear-gradient(135deg,#128C7E,#25d366);padding:.9rem 1rem;display:flex;align-items:center;gap:.7rem;color:#fff}
  .wa-avatar{width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.35rem;flex-shrink:0}
  .wa-name{font-weight:700;font-size:.98rem;line-height:1.1}
  .wa-status{font-size:.72rem;opacity:.92;display:flex;align-items:center;gap:.3rem;margin-top:2px}
  .wa-dot{width:7px;height:7px;border-radius:50%;background:#a7f3d0;animation:waBlink 1.6s infinite}
  @keyframes waBlink{0%,100%{opacity:1}50%{opacity:.35}}
  .wa-close{margin-left:auto;background:none;border:none;color:#fff;font-size:1.5rem;line-height:1;cursor:pointer;opacity:.85}
  .wa-close:hover{opacity:1}
  .wa-body{background:#e6ddd4;padding:1rem .9rem;min-height:96px}
  .wa-bubble{background:#fff;border-radius:0 10px 10px 10px;padding:.7rem .85rem;font-size:.85rem;line-height:1.5;color:#0f172a;box-shadow:0 1px 2px rgba(0,0,0,.12);max-width:90%}
  .wa-time{display:block;text-align:right;font-size:.62rem;color:#94a3b8;margin-top:4px}
  .wa-start{display:flex;align-items:center;justify-content:center;gap:.5rem;background:#25d366;color:#fff;font-weight:700;font-size:.92rem;padding:.85rem;text-decoration:none;transition:background .2s}
  .wa-start:hover{background:#1eb85b;color:#fff}
  @media (max-width:400px){#waWidget{right:14px;bottom:14px}}
</style>
<div id="waWidget">
  <div id="waCard" class="wa-card" role="dialog" aria-label="Conversar no WhatsApp">
    <div class="wa-head">
      <div class="wa-avatar"><i class="bi bi-whatsapp"></i></div>
      <div>
        <div class="wa-name">FixaOS</div>
        <div class="wa-status"><span class="wa-dot"></span> Online &middot; responde rápido</div>
      </div>
      <button class="wa-close" onclick="waToggle(false)" aria-label="Fechar">&times;</button>
    </div>
    <div class="wa-body">
      <div class="wa-bubble">
        Olá! 👋 Aqui é o time do <b>FixaOS</b>.<br>
        Tem dúvida sobre o sistema, preços ou quer uma demonstração? Chama a gente no WhatsApp.
        <span class="wa-time">agora</span>
      </div>
    </div>
    <a class="wa-start" target="_blank" rel="noopener"
       href="https://wa.me/5511979930702?text=Ol%C3%A1!%20Vim%20pela%20p%C3%A1gina%20do%20FixaOS%20e%20quero%20saber%20mais.">
      <i class="bi bi-whatsapp"></i> Iniciar conversa
    </a>
  </div>
  <div id="waTeaser" class="wa-teaser" onclick="waToggle(true)">Precisa de ajuda? 💬</div>
  <button class="wa-btn" onclick="waToggle()" aria-label="Abrir WhatsApp">
    <i class="bi bi-whatsapp"></i>
    <span class="wa-badge" id="waBadge">1</span>
  </button>
</div>
<script>
(function(){
  var w=document.getElementById('waWidget'),card=document.getElementById('waCard'),
      teaser=document.getElementById('waTeaser'),badge=document.getElementById('waBadge'),
      bar=document.getElementById('cookieBar');
  // Cancela animações INJETADAS por terceiros (ex.: AdSense) que prendiam o widget em opacity:0.
  // Mantém minhas animações CSS (pulso/piscada) e transições.
  function waClean(){
    [w].concat([].slice.call(w.querySelectorAll('*'))).forEach(function(el){
      if(!el.getAnimations) return;
      el.getAnimations().forEach(function(a){
        var keep=(typeof CSSAnimation!=='undefined'&&a instanceof CSSAnimation)||(typeof CSSTransition!=='undefined'&&a instanceof CSSTransition);
        if(!keep) a.cancel();
      });
    });
  }
  window.waToggle=function(force){
    var open=(typeof force==='boolean')?force:!card.classList.contains('open');
    waClean();
    card.classList.toggle('open',open);
    teaser.classList.remove('show');
    if(open&&badge) badge.style.display='none';
  };
  function sync(){ if(bar&&getComputedStyle(bar).display!=='none') w.classList.add('wa-lift'); else w.classList.remove('wa-lift'); }
  sync();
  if(typeof window.cookieConsent==='function'){ var o=window.cookieConsent; window.cookieConsent=function(v){o(v);sync();}; }
  waClean();
  [150,600,1500,3000].forEach(function(t){ setTimeout(waClean,t); });
  setTimeout(function(){ if(!card.classList.contains('open')) teaser.classList.add('show'); },4000);
})();
</script>
</body>
</html>
