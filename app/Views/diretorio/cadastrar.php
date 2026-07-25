<?php
  $gSignup = $_SESSION['google_signup'] ?? null;
  if ($gSignup) { unset($_SESSION['google_signup']); }
  $preNome  = $gSignup['nome']  ?? ($_POST['nome']  ?? '');
  $preEmail = $gSignup['email'] ?? ($_POST['email'] ?? '');
?>
<style>
  .dir-eye{position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:.25rem;z-index:3}
  .dir-eye:hover{color:#475569}
  .dir-google{display:flex;align-items:center;justify-content:center;gap:.6rem;width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:12px;background:#fff;color:#1e293b;font-weight:700;text-decoration:none;transition:background .15s}
  .dir-google:hover{background:#f8fafc;color:#0f172a}
  .dir-divider{display:flex;align-items:center;gap:.8rem;margin:1.1rem 0;color:#94a3b8;font-size:.82rem}
  .dir-divider::before,.dir-divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
  .dir-steps{display:flex;gap:.5rem;margin-top:14px;font-size:.72rem}
  .dir-step{flex:1;text-align:center;padding:.4rem;border-radius:8px;background:rgba(255,255,255,.08);color:#cbd5e1}
  .dir-step.on{background:#f97316;color:#fff;font-weight:700}
</style>
<div style="min-height:100vh;background:linear-gradient(160deg,#0f172a 0%,#1e3a5f 100%);padding:40px 16px">
  <div style="max-width:480px;margin:0 auto">

    <div style="text-align:center;margin-bottom:18px">
      <a href="<?= url('/assistencias') ?>" style="text-decoration:none;font-size:26px;font-weight:900;color:#fff;letter-spacing:-.5px">Fixa<span style="color:#f97316">OS</span></a>
      <div style="color:#cbd5e1;font-size:.9rem;margin-top:6px">Diretório de Assistências Técnicas</div>
      <div class="dir-steps">
        <div class="dir-step on">1 · Criar conta</div>
        <div class="dir-step">2 · Dados da empresa</div>
      </div>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
      <div style="background:#f97316;padding:22px 26px;color:#fff">
        <h1 style="margin:0;font-size:1.3rem;font-weight:800"><i class="bi bi-shop-window me-2"></i>Cadastre sua empresa grátis</h1>
        <p style="margin:6px 0 0;font-size:.9rem;opacity:.95">Crie sua conta em 30 segundos. Os dados da empresa (nome, cidade, serviços...) você preenche no próximo passo.</p>
      </div>

      <div class="card-body" style="padding:26px 26px 30px">

        <?php foreach(['error','info','success'] as $t): $m = flash($t); if($m): ?>
          <div class="alert alert-<?= $t==='error'?'danger':$t ?> py-2 small"><?= e($m) ?></div>
        <?php endif; endforeach; ?>

        <?php if(!$gSignup): ?>
          <a href="<?= url('/auth/google?to=diretorio') ?>" class="dir-google">
            <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            Continuar com Google
          </a>
          <div class="dir-divider"><span>ou crie com e-mail</span></div>
        <?php else: ?>
          <div style="background:rgba(34,197,94,.09);border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:.7rem 1rem;font-size:.86rem;color:#16a34a;margin-bottom:1rem">
            <i class="bi bi-google me-1"></i>Conta Google vinculada! Confirme abaixo pra continuar.
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/diretorio/cadastrar') ?>" autocomplete="off">
          <?= csrf_field() ?>
          <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
          <?php if($gSignup): ?><input type="hidden" name="google_id" value="<?= e($gSignup['google_id']) ?>"><?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Seu nome <span class="text-danger">*</span></label>
            <input type="text" name="nome" class="form-control" required maxlength="100"
                   placeholder="Seu nome" value="<?= e($preNome) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">E-mail <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required maxlength="100"
                   placeholder="voce@empresa.com.br" value="<?= e($preEmail) ?>" <?= $gSignup ? 'readonly style="background:#f1f5f9"' : '' ?>>
          </div>

          <?php if($gSignup): ?>
            <input type="hidden" name="senha" value="<?= e(bin2hex(random_bytes(16))) ?>">
            <input type="hidden" name="senha_confirm" value="">
          <?php else: ?>
          <div class="row g-3">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold small">Senha <span class="text-danger">*</span></label>
              <div style="position:relative">
                <input type="password" name="senha" id="dirSenha" class="form-control" required minlength="6" placeholder="Mín. 6 caracteres" style="padding-right:2.5rem">
                <button type="button" class="dir-eye" onclick="dirToggle('dirSenha',this)" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold small">Confirmar senha <span class="text-danger">*</span></label>
              <div style="position:relative">
                <input type="password" name="senha_confirm" id="dirSenha2" class="form-control" required minlength="6" placeholder="Repita a senha" style="padding-right:2.5rem">
                <button type="button" class="dir-eye" onclick="dirToggle('dirSenha2',this)" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-lg w-100 mt-2" style="background:#f97316;color:#fff;font-weight:700;border-radius:12px">
            Criar conta e continuar <i class="bi bi-arrow-right-circle-fill ms-1"></i>
          </button>

          <p class="text-center text-muted small mt-3 mb-0">
            Já tem cadastro? <a href="<?= url('/login') ?>" style="color:#f97316;font-weight:600">Entrar</a>
          </p>
        </form>
      </div>
    </div>

    <p style="text-align:center;color:#94a3b8;font-size:.78rem;margin-top:18px">
      Conta grátis de diretório — não dá acesso ao sistema de gestão completo (que virá por convite).<br>
      Ao cadastrar, você concorda com os <a href="<?= url('/termos') ?>" style="color:#cbd5e1">Termos</a> e a
      <a href="<?= url('/privacidade') ?>" style="color:#cbd5e1">Privacidade</a>.
    </p>
    <p style="text-align:center;color:#fdba74;font-size:.78rem;margin-top:6px">
      <i class="bi bi-stars me-1"></i>Com a conta grátis já dá pra editar logo, descrição e horário de funcionamento. Assinando um plano da FixaOS, sua empresa libera edição completa: cidade/UF, site, redes sociais, lista de serviços e contagem de visitas.
    </p>
  </div>
</div>

<script>
function dirToggle(id, btn){
  var input = document.getElementById(id);
  var isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.querySelector('i').className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
