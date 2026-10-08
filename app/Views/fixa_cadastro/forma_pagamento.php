<?php
/**
 * Escolha de ciclo de pagamento do Carteira Fixa standalone — mesmo motor de checkout da
 * InfinitePay já usado pelo plano completo do FixaOS (ver FixaCadastroController::assinar()),
 * só que ramificado por `tipo='fixa'` no webhook. Cada botão gera um link de pagamento avulso
 * (não é recorrência automática de verdade — a InfinitePay não oferece isso) e redireciona.
 */
$diasRestantes = 7;
if (!empty($assinatura['teste_fim'])) {
    $diasRestantes = max(0, (int) ceil((strtotime($assinatura['teste_fim']) - time()) / 86400));
}
$jaVenceu = $assinatura && in_array($assinatura['status'], ['inadimplente', 'bloqueada'], true);
$precoMensal = (int) ($plano['preco_mensal'] ?? 0);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<style>
.cf-page{min-height:100vh;background:#1B1025;padding:48px 16px;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;display:flex;align-items:center;justify-content:center}
.cf-wrap{width:100%;max-width:480px}
.cf-brand{text-align:center;margin-bottom:20px}
.cf-brand a{text-decoration:none;font-size:24px;font-weight:900;color:#fff;letter-spacing:-.5px}
.cf-brand a span{color:#3CC9C0}
.cf-card{background:#fff;border-radius:18px;padding:32px 26px;box-shadow:0 24px 60px rgba(0,0,0,.35);border-top:4px solid #8C7CFF;text-align:center}
.cf-card .ico{font-size:2.6rem;margin-bottom:10px}
.cf-card h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.3rem;margin:0 0 10px;color:#111827}
.cf-card p{color:#64748b;font-size:.92rem;margin:0 0 14px;line-height:1.55}
.cf-box{background:#f6f4ff;border:1px solid #ddd6fe;border-radius:10px;padding:14px;font-size:.84rem;color:#5b21b6;margin-bottom:18px;text-align:left}
.cf-box-alerta{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.cf-btn{display:inline-block;background:#8C7CFF;color:#fff;border:none;border-radius:10px;padding:12px 24px;font-weight:700;font-size:.95rem;text-decoration:none}
.cf-ciclos{display:flex;flex-direction:column;gap:10px;margin:0 0 18px;text-align:left}
.cf-ciclo{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border:1.5px solid #e5e7eb;border-radius:12px;text-decoration:none;color:#111827;transition:border-color .15s,background .15s}
.cf-ciclo:hover{border-color:#8C7CFF;background:#faf9ff}
.cf-ciclo-destaque{border-color:#8C7CFF;background:#f6f4ff;position:relative}
.cf-ciclo-badge{position:absolute;top:-10px;right:14px;background:#8C7CFF;color:#fff;font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:999px;letter-spacing:.3px}
.cf-ciclo-nome{font-weight:700;font-size:.95rem}
.cf-ciclo-desc{font-size:.76rem;color:#64748b;margin-top:2px}
.cf-ciclo-preco{font-weight:800;font-size:1.05rem;color:#111827;white-space:nowrap}
.cf-ciclo-preco small{display:block;font-size:.68rem;font-weight:600;color:#16a34a}
.cf-link-sec{display:block;margin-top:14px;font-size:.84rem;color:#8C7CFF;text-decoration:none}
</style>

<div class="cf-page">
  <div class="cf-wrap">
    <div class="cf-brand"><a href="<?= url('/') ?>">Carteira<span> Fixa</span></a></div>
    <div class="cf-card">
      <?php if ($jaVenceu): ?>
      <div class="ico">⚠️</div>
      <h1>Sua assinatura venceu</h1>
      <p>Escolha um ciclo abaixo pra continuar usando o Carteira Fixa normalmente.</p>
      <?php else: ?>
      <div class="ico">🎉</div>
      <h1>Sua conta está pronta!</h1>
      <p>Seus <?= (int) $diasRestantes ?> dias grátis já começaram a contar — sem cobrança nenhuma até lá. Quando quiser, já pode escolher o ciclo de pagamento.</p>
      <?php endif; ?>

      <?php if (!$infinitePayAtivo): ?>
      <div class="cf-box cf-box-alerta">
        O pagamento online ainda não está disponível neste momento. Você pode usar o Carteira
        Fixa normalmente durante o teste; avisaremos por e-mail assim que estiver pronto.
      </div>
      <a class="cf-btn" href="<?= url('/financeiro-pessoal') ?>">Ir para o Carteira Fixa</a>
      <?php else: ?>

      <div class="cf-ciclos">
        <?php foreach ($ciclos as $codigoCiclo => $c): ?>
        <?php
            $valorCentavos = plano_preco_ciclo($precoMensal, $c);
            $destaque = $codigoCiclo === 'anual';
        ?>
        <a class="cf-ciclo<?= $destaque ? ' cf-ciclo-destaque' : '' ?>" href="<?= url('/carteira-fixa/assinar/' . $codigoCiclo) ?>">
          <?php if ($destaque): ?><span class="cf-ciclo-badge">Melhor opção</span><?php endif; ?>
          <div>
            <div class="cf-ciclo-nome"><?= e($c['nome']) ?></div>
            <div class="cf-ciclo-desc">
              <?= (int) $c['meses'] === 1 ? 'Cobrança mensal' : 'Cobrado a cada ' . (int) $c['meses'] . ' meses' ?>
              <?= ((int) $c['desconto'] > 0) ? ' — ' . (int) $c['desconto'] . '% de desconto' : '' ?>
            </div>
          </div>
          <div class="cf-ciclo-preco">
            <?= money($valorCentavos / 100) ?>
            <?php if ((int) $c['meses'] > 1): ?><small><?= money(($valorCentavos / $c['meses']) / 100) ?>/mês</small><?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <a class="cf-link-sec" href="<?= url('/financeiro-pessoal') ?>">Continuar usando o teste grátis por agora</a>
      <?php endif; ?>
    </div>
  </div>
</div>
