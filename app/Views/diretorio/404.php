<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$titulo  = 'Empresa não encontrada — FixaOS';
?>
<title><?= $titulo ?></title>
<meta name="robots" content="noindex">

<div style="min-height:62vh;display:flex;align-items:center;justify-content:center;padding:4rem 1.2rem;background:linear-gradient(135deg,#0B1220 0%,#16264A 55%,#1E3A5F 100%);position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 60% at 70% 40%,rgba(13,148,136,.18),transparent 70%)"></div>
  <div style="text-align:center;max-width:540px;position:relative">
    <div style="font-size:clamp(4rem,14vw,6.5rem);font-weight:900;color:#2dd4bf;line-height:.9;letter-spacing:-.03em">404</div>
    <h1 style="color:#fff;font-size:clamp(1.4rem,4vw,1.8rem);font-weight:800;margin:.6rem 0 .5rem">Não encontramos essa assistência</h1>
    <p style="color:#9fb0cc;font-size:1rem;line-height:1.6;margin-bottom:2rem">
      O link pode estar errado ou a empresa saiu do diretório.<br>Mas você pode <strong style="color:#5eead4">buscar outra</strong> — ou <strong style="color:#5eead4">cadastrar a sua</strong>.
    </p>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;justify-content:center">
      <a href="<?= $baseUrl ?>/assistencias" style="background:#0d9488;color:#fff;padding:.8rem 1.7rem;border-radius:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem">
        <i class="bi bi-search"></i> Buscar no diretório
      </a>
      <a href="<?= $baseUrl ?>/cadastrar?tipo=diretorio" style="background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.2);color:#fff;padding:.8rem 1.7rem;border-radius:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem">
        <i class="bi bi-shop-window"></i> Cadastrar minha empresa
      </a>
    </div>
    <p style="color:#64748b;font-size:.82rem;margin-top:1.6rem">
      <i class="bi bi-info-circle me-1"></i>É autônomo e sua empresa não aparece? O cadastro é <strong>grátis</strong>, mesmo <strong>sem CNPJ</strong>.
    </p>
  </div>
</div>
