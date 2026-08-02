<div class="page-content">
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <?php if (!empty($paga)): ?>
        <i class="bi bi-check-circle-fill display-3 text-success mb-3 d-block"></i>
        <h5 class="fw-bold">Pagamento confirmado! 🎉</h5>
        <p class="text-muted mb-4" style="max-width:520px;margin:0 auto">Sua assinatura está ativa. Obrigado por fazer parte do FixaOS!</p>
      <?php else: ?>
        <i class="bi bi-hourglass-split display-3 text-primary mb-3 d-block"></i>
        <h5 class="fw-bold">Recebemos seu pagamento 👍</h5>
        <p class="text-muted mb-4" style="max-width:520px;margin:0 auto">Assim que a InfinitePay confirmar (poucos instantes no PIX), sua licença é ativada automaticamente. Você pode atualizar esta página.</p>
      <?php endif; ?>
      <a href="<?= url('/planos') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Ver planos</a>
      <a href="<?= url('/dashboard') ?>" class="btn btn-primary ms-2"><i class="bi bi-house me-1"></i>Ir para o sistema</a>
    </div>
  </div>
</div>
