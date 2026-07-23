<div class="card login-card">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <svg width="160" height="40" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" style="display:inline-block"><rect x="0" y="0" width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black, sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
      <p class="text-muted small mt-2">Gestão de Assistência Técnica</p>
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
      <div class="mb-1">
        <label class="form-label fw-semibold small">Senha</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="senha" id="loginSenha" class="form-control" placeholder="••••••••" required>
          <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('loginSenha', this)" tabindex="-1" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="text-end mb-3">
        <a href="<?= url('/esqueci-senha') ?>" class="small text-decoration-none">Esqueci minha senha</a>
      </div>
      <button class="btn btn-primary w-100 fw-semibold mt-2">
        <i class="bi bi-box-arrow-in-right"></i> Entrar
      </button>
    </form>

    <div class="d-flex align-items-center gap-2 my-3">
      <hr class="flex-grow-1 m-0"><span class="text-muted small">ou</span><hr class="flex-grow-1 m-0">
    </div>

    <a href="<?= url('/auth/google') ?>" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">
      <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
      Entrar com Google
    </a>

    <div class="text-center mt-3 text-muted small">
      Não tem conta? <a href="<?= url('/cadastrar') ?>">Criar gratuitamente</a>
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
