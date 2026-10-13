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
<meta name="description" content="Carteira Fixa — controle financeiro pessoal e de MEI/empresa: cartão, contas recorrentes, leitura de conta por foto e caixinhas, tudo num só lugar.">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#1B1025;color:#f5f5fa;-webkit-font-smoothing:antialiased}
a{text-decoration:none}

/* NAV — só a identidade do Carteira Fixa; nenhum link pro FixaOS, nenhuma outra seção do site
   (Funcionalidades/Planos/FAQ/Manual/Fórum/Diretório são páginas do FixaOS, não deste produto
   standalone — ver CLAUDE.md, pedido explícito de deixar esta página "sem ligação com o
   fixaos"). */
.fxp-nav{
  position:sticky;top:0;z-index:999;
  background:rgba(27,16,37,.92);
  backdrop-filter:blur(16px);
  border-bottom:1px solid rgba(255,255,255,.09);
  padding:.9rem 0;
}
.fxp-brand{font-size:1.3rem;font-weight:900;color:#fff;letter-spacing:-.02em}
.fxp-brand span{color:#3CC9C0}
.fxp-nav .btn-ghost{background:rgba(255,255,255,.06);color:#fff;font-weight:600;border:1px solid rgba(255,255,255,.14);border-radius:10px;padding:.55rem 1.3rem;font-size:.92rem}
.fxp-nav .btn-ghost:hover{background:rgba(255,255,255,.1);color:#fff}

/* FOOTER — só o essencial legal (confiança + termos), sem seções de navegação do FixaOS. */
.fxp-footer{background:#15081f;border-top:1px solid rgba(255,255,255,.09);padding:2.2rem 0}
.fxp-footer a{color:#a7a9c4;font-size:.84rem}
.fxp-footer a:hover{color:#fff}
</style>
</head>
<body>

<nav class="fxp-nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= url('/carteira-fixa/cadastrar') ?>" class="fxp-brand" aria-label="Carteira Fixa">Carteira<span> Fixa</span></a>
    <a href="<?= url('/carteira-fixa/login') ?>" class="btn-ghost btn btn-sm">Entrar</a>
  </div>
</nav>

<?php $err = flash('error'); $suc = flash('success'); $inf = flash('info'); ?>
<?php if ($err): ?><div class="alert alert-danger m-3"><?= e($err) ?></div><?php endif; ?>
<?php if ($suc): ?><div class="alert alert-success m-3"><?= e($suc) ?></div><?php endif; ?>
<?php if ($inf): ?><div class="alert alert-info m-3"><?= e($inf) ?></div><?php endif; ?>

<?php ($content)(); ?>

<footer class="fxp-footer">
  <div class="container">
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
