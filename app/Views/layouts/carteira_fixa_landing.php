<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex, follow">
<?php endif; ?>
<title><?= !empty($titulo) ? e($titulo) : 'Carteira Fixa — controle financeiro pessoal' ?></title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
<link rel="shortcut icon" href="/favicon.ico">
<meta name="theme-color" content="#1B1025">
<meta name="description" content="Carteira Fixa — controle financeiro pessoal e de MEI/empresa: lançamentos, leitura de conta por foto, contas recorrentes, agenda de vencimentos e caixinhas de reserva, tudo num só lugar. Grátis por 1 ano pros primeiros 100 clientes.">
<meta property="og:title" content="Carteira Fixa — controle financeiro pessoal">
<meta property="og:description" content="Lançamentos, leitura de conta por foto, contas recorrentes, agenda de vencimentos e caixinhas de reserva — grátis por 1 ano pros primeiros 100 clientes.">
<meta property="og:type" content="website">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600;700;800&display=swap">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:#1B1025;color:#f5f5fa;-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{text-decoration:none}
h1,h2,h3{font-family:'Poppins',sans-serif}
.fxl-container{max-width:1120px;margin:0 auto;padding:0 20px}

/* NAV — só a identidade do Carteira Fixa; nenhum link pro FixaOS (mesma cautela já aplicada em
   /carteira-fixa/cadastrar e /carteira-fixa/login — ver layouts/fixa_publico.php). */
.fxl-nav{position:sticky;top:0;z-index:999;background:rgba(27,16,37,.92);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.09);padding:.85rem 0}
.fxl-brand{font-size:1.25rem;font-weight:900;color:#fff;letter-spacing:-.02em}
.fxl-brand span{color:#3CC9C0}
.fxl-nav-links{display:flex;align-items:center;gap:1.8rem}
.fxl-nav-links a.fxl-navlink{color:#c7c5d9;font-size:.9rem;font-weight:600}
.fxl-nav-links a.fxl-navlink:hover{color:#fff}
.fxl-btn-ghost{background:rgba(255,255,255,.06);color:#fff;font-weight:600;border:1px solid rgba(255,255,255,.14);border-radius:10px;padding:.5rem 1.1rem;font-size:.88rem;white-space:nowrap}
.fxl-btn-ghost:hover{background:rgba(255,255,255,.1);color:#fff}
.fxl-btn-primary{background:#8C7CFF;color:#fff;font-weight:700;border:none;border-radius:10px;padding:.6rem 1.3rem;font-size:.9rem;white-space:nowrap}
.fxl-btn-primary:hover{background:#7563f0;color:#fff}

/* FOOTER — mesmo conteúdo essencial de layouts/fixa_publico.php. */
.fxl-footer{background:#15081f;border-top:1px solid rgba(255,255,255,.09);padding:2.4rem 0}
.fxl-footer a{color:#a7a9c4;font-size:.84rem}
.fxl-footer a:hover{color:#fff}
</style>
</head>
<body>

<nav class="fxl-nav">
  <div class="fxl-container d-flex align-items-center justify-content-between">
    <a href="/" class="fxl-brand" aria-label="Carteira Fixa">Carteira<span> Fixa</span></a>
    <div class="fxl-nav-links d-none d-lg-flex">
      <a href="#funcionalidades" class="fxl-navlink">Funcionalidades</a>
      <a href="#como-funciona" class="fxl-navlink">Como funciona</a>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= url('/carteira-fixa/login') ?>" class="fxl-btn-ghost btn btn-sm">Entrar</a>
      <a href="<?= url('/carteira-fixa/cadastrar') ?>" class="fxl-btn-primary btn btn-sm d-none d-sm-inline-block">Criar conta grátis</a>
    </div>
  </div>
</nav>

<?php ($content)(); ?>

<footer class="fxl-footer">
  <div class="fxl-container">
    <div class="d-flex flex-wrap justify-content-center gap-3 mb-3" style="color:#a7a9c4;font-size:.8rem">
      <span><i class="bi bi-shield-lock-fill me-1" style="color:#22c55e"></i>Conexão segura (SSL)</span>
      <span><i class="bi bi-geo-alt-fill me-1" style="color:#2dd4bf"></i>Servidores no Brasil</span>
      <span><i class="bi bi-file-earmark-check-fill me-1" style="color:#818cf8"></i>Conforme a LGPD</span>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="color:#a7a9c4;font-size:.78rem">
      <span>© <?= date('Y') ?> Carteira Fixa. Todos os direitos reservados.</span>
      <div class="d-flex gap-3">
        <a href="<?= url('/termos') ?>">Termos de uso</a>
        <a href="<?= url('/privacidade') ?>">Privacidade</a>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
