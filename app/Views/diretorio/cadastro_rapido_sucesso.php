<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$urlPublica = rtrim($appCfg['url'], '/') . '/assistencias/' . $slug;
?>
<style>
.cr-page{min-height:100vh;background:#0a1526;padding:48px 16px;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;display:flex;align-items:center;justify-content:center}
.cr-wrap{width:100%;max-width:440px}
.cr-brand{text-align:center;margin-bottom:20px}
.cr-brand a{text-decoration:none;font-size:24px;font-weight:900;color:#fff;letter-spacing:-.5px}
.cr-brand a span{color:#f97316}
.cr-card{background:#fff;border-radius:18px;padding:32px 26px;box-shadow:0 24px 60px rgba(0,0,0,.35);border-top:4px solid #16a34a;text-align:center}
.cr-card .ico{font-size:2.6rem;margin-bottom:10px}
.cr-card h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.3rem;margin:0 0 10px;color:#111827}
.cr-card p{color:#64748b;font-size:.92rem;margin:0 0 18px}
.cr-link{display:block;background:#f8fafc;border:1px solid #d8dee9;border-radius:10px;padding:12px;font-family:monospace;font-size:.85rem;color:#0f172a;word-break:break-all;margin-bottom:18px;text-decoration:none}
.cr-btn{display:inline-block;background:#f97316;color:#fff;border:none;border-radius:10px;padding:12px 24px;font-weight:700;font-size:.95rem;text-decoration:none}
</style>

<div class="cr-page">
  <div class="cr-wrap">
    <div class="cr-brand"><a href="<?= url('/') ?>">Fixa<span>OS</span></a></div>
    <div class="cr-card">
      <div class="ico">🎉</div>
      <h1>Sua empresa já está no ar!</h1>
      <p>Cadastro grátis concluído — sua página já pode ser encontrada no Diretório FixaOS.</p>
      <a class="cr-link" href="<?= e($urlPublica) ?>" target="_blank" rel="noopener"><?= e($urlPublica) ?></a>
      <p style="font-size:.82rem">Quer editar logo, fotos, endereço e horário de funcionamento? Volte nessa página e clique em "Esta é a sua empresa? Reivindique grátis".</p>
      <a class="cr-btn" href="<?= e($urlPublica) ?>" target="_blank" rel="noopener">Ver minha página</a>
    </div>
  </div>
</div>
