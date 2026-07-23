<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">

    <div class="text-center mb-4">
      <div class="d-inline-flex align-items-center justify-content-center mb-3"
           style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#1e3a5f,#0f172a)">
        <i class="bi bi-chat-square-heart-fill text-warning fs-3"></i>
      </div>
      <h1 class="h4 fw-bold mb-1">Participe do Fórum</h1>
      <p class="text-muted mb-0">Cadastro simples e <strong>100% gratuito</strong> — sem cartão, sem cobrança. Tire dúvidas e compartilhe soluções com outros técnicos.</p>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form method="POST" action="<?= $appUrl ?>/forum/cadastrar" autocomplete="off">
          <?= csrf_field() ?>
          <!-- honeypot anti-bot -->
          <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">

          <div class="mb-3">
            <label class="form-label fw-semibold">Seu nome</label>
            <input type="text" name="nome" class="form-control form-control-lg" required maxlength="100"
                   placeholder="Como quer ser chamado no fórum">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">E-mail</label>
            <input type="email" name="email" class="form-control form-control-lg" required placeholder="seu@email.com">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Senha</label>
            <input type="password" name="senha" class="form-control form-control-lg" required minlength="6"
                   placeholder="Mínimo 6 caracteres">
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
            <i class="bi bi-person-plus me-1"></i>Criar conta grátis
          </button>
        </form>

        <p class="text-center text-muted small mt-3 mb-0">
          Já tem conta? <a href="<?= $appUrl ?>/login" class="fw-semibold text-decoration-none">Entrar</a>
        </p>
      </div>
    </div>

    <p class="text-center text-muted small mt-3">
      <i class="bi bi-shield-check me-1"></i>Essa conta dá acesso ao fórum da comunidade.
      O sistema completo de gestão (OS, financeiro, estoque) será liberado em breve.
    </p>

  </div>
</div>
