<div class="card login-card">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <i class="bi bi-cpu-fill text-primary" style="font-size:2.5rem"></i>
      <h4 class="mt-2 fw-bold">OficinaTech</h4>
      <p class="text-muted small">Gestão de Assistência Técnica</p>
    </div>

    <?php $err = flash('error'); if ($err): ?>
      <div class="alert alert-danger py-2 small"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/login') ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold small">E-mail</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold small">Senha</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
        </div>
      </div>
      <button class="btn btn-primary w-100 fw-semibold mt-2">
        <i class="bi bi-box-arrow-in-right"></i> Entrar
      </button>
    </form>

    <div class="text-center mt-3 text-muted small">
      Demo: admin@techfix.com / Admin@123
    </div>
  </div>
</div>
