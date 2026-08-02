<div class="card login-card">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <svg width="160" height="40" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" style="display:inline-block"><rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
      <p class="text-muted small mt-2">Criar nova senha</p>
    </div>

    <?php $err = flash('error'); if ($err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endif; ?>

    <form method="POST" action="<?= url('/redefinir-senha/' . e($token)) ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold small">Nova senha</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="senha" id="novaSenha" class="form-control" placeholder="Mínimo 6 caracteres" minlength="6" required autofocus>
          <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('novaSenha', this)" tabindex="-1" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold small">Confirmar senha</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="senha_confirma" id="confSenha" class="form-control" placeholder="Repita a nova senha" minlength="6" required>
          <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('confSenha', this)" tabindex="-1" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <button class="btn btn-primary w-100 fw-semibold"><i class="bi bi-check-lg me-1"></i>Redefinir senha</button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= url('/login') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Voltar ao login</a>
    </div>
  </div>
</div>
<script>
function toggleSenha(id, btn){
  var i = document.getElementById(id); if(!i) return;
  var mostrar = i.type === 'password';
  i.type = mostrar ? 'text' : 'password';
  var ic = btn.querySelector('i'); if(ic) ic.className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
  btn.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
}
</script>
