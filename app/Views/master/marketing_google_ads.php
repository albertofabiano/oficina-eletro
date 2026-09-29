<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="text-white fw-bold mb-0">Marketing — Google Ads</h5>
    <small style="color:#6c757d">Conexão da conta Gerenciadora (MCC) — credencial global, compartilhada por toda empresa do módulo Marketing</small>
  </div>
</div>

<?php $erro = flash('error'); $sucesso = flash('success'); ?>
<?php if ($erro): ?>
<div class="alert alert-danger py-2 small mb-3"><?= e($erro) ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
<div class="alert alert-success py-2 small mb-3"><?= e($sucesso) ?></div>
<?php endif; ?>

<?php if (!$configOk): ?>
<div class="alert alert-warning py-2 small mb-3">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  <code>client_id</code>/<code>client_secret</code> do Google Ads ainda não estão preenchidos em
  <code>config/marketing.php</code> (seção <code>google_ads</code>) — preencha com os valores
  gerados no Google Cloud Console (APIs e serviços → Credenciais → ID do cliente OAuth) antes de
  tentar conectar.
</div>
<?php endif; ?>

<div class="ms-card p-4">
  <?php if ($conectado): ?>
  <div class="d-flex align-items-center gap-2 mb-3">
    <span class="badge bg-success fs-6"><i class="bi bi-check-circle-fill me-1"></i>Conectado</span>
  </div>
  <p class="text-white mb-1">A conta Gerenciadora do Google Ads está conectada.</p>
  <?php if (!empty($cred['updated_at'])): ?>
  <p class="text-muted small mb-3">Última atualização do token: <?= date_br($cred['updated_at'], true) ?></p>
  <?php endif; ?>
  <?php if ($loginCustomerId): ?>
  <p class="text-muted small mb-3">Login Customer ID configurado: <code><?= e($loginCustomerId) ?></code></p>
  <?php else: ?>
  <p class="text-warning small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i>
    <code>login_customer_id</code> ainda não está preenchido em <code>config/marketing.php</code>.
  </p>
  <?php endif; ?>

  <div class="d-flex gap-2">
    <a href="<?= url('/marketing/conectar/google') ?>" class="btn btn-outline-light btn-sm">
      <i class="bi bi-arrow-repeat me-1"></i>Reconectar (trocar de conta)
    </a>
    <form method="POST" action="<?= url('/master/marketing/google-ads/desconectar') ?>"
          onsubmit="return confirm('Desconectar o Google Ads? O módulo Marketing para de conseguir sincronizar campanhas reais até reconectar.')">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-power me-1"></i>Desconectar
      </button>
    </form>
  </div>

  <?php else: ?>
  <div class="d-flex align-items-center gap-2 mb-3">
    <span class="badge bg-secondary fs-6"><i class="bi bi-x-circle me-1"></i>Não conectado</span>
  </div>
  <p class="text-muted small mb-3">
    Sem essa conexão, o módulo Marketing só funciona com a conta de demonstração
    (<code>FakeAdPlatform</code>) — nenhuma empresa consegue sincronizar campanhas reais do
    Google Ads.
  </p>
  <a href="<?= url('/marketing/conectar/google') ?>"
     class="btn btn-primary btn-sm fw-semibold <?= $configOk ? '' : 'disabled' ?>">
    <i class="bi bi-google me-1"></i>Conectar Google Ads
  </a>
  <?php endif; ?>
</div>

<div class="alert alert-secondary py-2 small mt-3">
  <i class="bi bi-info-circle me-1"></i>
  Essa credencial é <strong>global</strong> (uma só, para o sistema inteiro) — a mesma conta
  Gerenciadora enxerga a conta de anúncio de cada empresa cliente vinculada a ela. Não é uma
  conexão por empresa.
</div>
