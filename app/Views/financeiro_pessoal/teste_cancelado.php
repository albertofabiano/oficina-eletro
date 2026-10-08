<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px 16px">
  <div style="max-width:420px;text-align:center">
    <div style="font-size:40px;margin-bottom:12px"><?= $cancelou ? '✅' : '🤔' ?></div>
    <h1 style="font-size:20px;color:#0f172a;margin-bottom:10px">
      <?= $cancelou ? 'Teste do Carteira Fixa cancelado' : 'Link não reconhecido' ?>
    </h1>
    <p style="color:#64748b;font-size:14.5px;line-height:1.6">
      <?php if ($cancelou): ?>
        Nenhuma cobrança será feita. Seus dados ficam guardados por 30 dias, caso queira
        continuar depois.
      <?php else: ?>
        Este link já foi usado, ou o teste já tinha sido cancelado antes.
      <?php endif; ?>
    </p>
  </div>
</div>
