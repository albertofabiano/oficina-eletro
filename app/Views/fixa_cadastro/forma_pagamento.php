<?php
$diasRestantes = 7;
if (!empty($assinatura['teste_fim'])) {
    $diasRestantes = max(0, (int) ceil((strtotime($assinatura['teste_fim']) - time()) / 86400));
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<style>
.cf-page{min-height:100vh;background:#1B1025;padding:48px 16px;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;display:flex;align-items:center;justify-content:center}
.cf-wrap{width:100%;max-width:440px}
.cf-brand{text-align:center;margin-bottom:20px}
.cf-brand a{text-decoration:none;font-size:24px;font-weight:900;color:#fff;letter-spacing:-.5px}
.cf-brand a span{color:#3CC9C0}
.cf-card{background:#fff;border-radius:18px;padding:32px 26px;box-shadow:0 24px 60px rgba(0,0,0,.35);border-top:4px solid #8C7CFF;text-align:center}
.cf-card .ico{font-size:2.6rem;margin-bottom:10px}
.cf-card h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.3rem;margin:0 0 10px;color:#111827}
.cf-card p{color:#64748b;font-size:.92rem;margin:0 0 14px;line-height:1.55}
.cf-box{background:#f6f4ff;border:1px solid #ddd6fe;border-radius:10px;padding:14px;font-size:.84rem;color:#5b21b6;margin-bottom:18px;text-align:left}
.cf-btn{display:inline-block;background:#8C7CFF;color:#fff;border:none;border-radius:10px;padding:12px 24px;font-weight:700;font-size:.95rem;text-decoration:none}
</style>

<div class="cf-page">
  <div class="cf-wrap">
    <div class="cf-brand"><a href="<?= url('/') ?>">Carteira<span> Fixa</span></a></div>
    <div class="cf-card">
      <div class="ico">🎉</div>
      <h1>Sua conta está pronta!</h1>
      <p>Seus <?= (int) $diasRestantes ?> dias grátis já começaram a contar — sem cobrança nenhuma até lá.</p>

      <div class="cf-box">
        <strong>Sobre a forma de pagamento:</strong> essa etapa ainda não está disponível —
        estamos ajustando o processamento de cartão/Pix automático. Você pode usar o Carteira
        Fixa normalmente durante o teste; avisaremos por e-mail assim que a cobrança estiver
        pronta, bem antes de qualquer valor ser cobrado.
      </div>

      <a class="cf-btn" href="<?= url('/financeiro-pessoal') ?>">Ir para o Carteira Fixa</a>
    </div>
  </div>
</div>
