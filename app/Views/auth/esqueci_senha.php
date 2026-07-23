<div class="card login-card">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <svg width="160" height="40" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" style="display:inline-block"><rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
      <p class="text-muted small mt-2">Recuperar acesso</p>
    </div>

    <?php $ok = flash('success'); $err = flash('error');
    if ($ok): ?><div class="alert alert-success py-2 small"><?= e($ok) ?></div><?php endif;
    if ($err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endif; ?>

    <p class="text-muted small mb-3">Digite o e-mail da sua conta. Enviaremos um link para você criar uma nova senha.</p>

    <form method="POST" action="<?= url('/esqueci-senha') ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold small">E-mail</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus>
        </div>
      </div>
      <button class="btn btn-primary w-100 fw-semibold"><i class="bi bi-send me-1"></i>Enviar link de recuperação</button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= url('/login') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Voltar ao login</a>
    </div>
  </div>
</div>
