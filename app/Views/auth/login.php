<?php $err = flash('error'); ?>
<style>
/* Escopo desta tela (login) — nenhuma outra view referencia estas classes fx-*. */
body { background: #0F1523 !important; }

.fx-login-page {
  --fx-bg:            #0F1523;
  --fx-left-grad-1:   #151D30;
  --fx-left-grad-2:   #0F1523;
  --fx-left-border:   rgba(255,255,255,.06);
  --fx-title:         #E8EBF0;
  --fx-subtitle:      #8B93A5;
  --fx-feature-text:  #A9B0BF;
  --fx-orange:        #E8823C;
  --fx-whatsapp:      #5DCAA5;
  --fx-scan:          #8FBEF0;
  --fx-badge:         #6B7285;
  --fx-label:         #C3C9D6;
  --fx-input-bg:      #1A2233;
  --fx-input-border:  rgba(255,255,255,.11);
  --fx-focus-border:  #4A87D0;
  --fx-link:          #8FBEF0;
  --fx-btn-bg:        #3B82D6;
  --fx-divider:       rgba(255,255,255,.09);
  --fx-google-border: rgba(255,255,255,.16);
  --fx-error:         #F09595;
  --fx-radius:        9px;
  --fx-font:          var(--font, 'Inter', 'Segoe UI', system-ui, sans-serif);

  /* O layout compartilhado (layouts/auth.php) envolve o conteúdo num
     .col-md-5 estreito e centralizado — como não posso editar esse arquivo
     (é usado também por esqueci-senha e reset-senha), "escapo" da largura
     dele aqui, só nesta view, pra ocupar a viewport inteira. */
  width: 100vw;
  margin-left: calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  min-height: 100vh;
  background: var(--fx-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--fx-font);
}

.fx-container {
  width: 100%;
  max-width: 960px;
  min-height: 100vh;
  display: flex;
  background: var(--fx-bg);
}

/* ── Coluna esquerda (marketing) ─────────────────────────────────── */
.fx-left {
  flex: 0 0 52%;
  max-width: 52%;
  padding: 40px;
  background: linear-gradient(160deg, var(--fx-left-grad-1), var(--fx-left-grad-2));
  border-right: .5px solid var(--fx-left-border);
  display: flex;
  flex-direction: column;
}
.fx-logo { font-weight: 800; font-size: 22px; letter-spacing: -.02em; }
.fx-logo .fx-logo-fixa { color: #FFFFFF; }
.fx-logo .fx-logo-os   { color: var(--fx-orange); }
.fx-left-title {
  font-size: 24px; font-weight: 700; color: var(--fx-title);
  margin: 28px 0 10px; line-height: 1.3;
}
.fx-left-sub {
  font-size: 14px; color: var(--fx-subtitle); line-height: 1.6; margin: 0 0 24px;
}
.fx-features { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
.fx-features li {
  display: flex; align-items: center; gap: 10px;
  font-size: 14px; color: var(--fx-feature-text); line-height: 1.4;
}
.fx-features li i { font-size: 16px; flex-shrink: 0; }
.fx-feat-camera   { color: var(--fx-orange); }
.fx-feat-whatsapp { color: var(--fx-whatsapp); }
.fx-feat-scan      { color: var(--fx-scan); }

.fx-badges {
  margin-top: auto; padding-top: 32px;
  display: flex; flex-wrap: wrap; gap: 14px;
}
.fx-badges span {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--fx-badge);
}

/* ── Coluna direita (formulário) ─────────────────────────────────── */
.fx-right {
  flex: 0 0 48%;
  max-width: 48%;
  padding: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.fx-right-inner { width: 100%; max-width: 340px; }
.fx-mobile-logo { display: none; }
.fx-right-title    { font-size: 18px; font-weight: 700; color: var(--fx-title); margin: 0 0 4px; }
.fx-right-sub       { font-size: 13px; color: var(--fx-subtitle); margin: 0 0 24px; }

.fx-error {
  font-size: 13px; color: var(--fx-error);
  background: rgba(240,149,149,.08);
  border: .5px solid rgba(240,149,149,.3);
  border-radius: var(--fx-radius);
  padding: 10px 13px; margin-bottom: 16px;
}

.fx-field { margin-bottom: 16px; }
.fx-field label {
  display: block; font-size: 13px; font-weight: 600; color: var(--fx-label);
  margin-bottom: 6px;
}
.fx-field input {
  width: 100%; min-height: 44px; box-sizing: border-box;
  background: var(--fx-input-bg);
  border: .5px solid var(--fx-input-border);
  border-radius: var(--fx-radius);
  padding: 11px 13px;
  font-size: 14px; color: var(--fx-title);
  font-family: inherit;
}
.fx-field input::placeholder { color: #5C6478; }
.fx-field input:focus {
  outline: none; border-color: var(--fx-focus-border);
  box-shadow: 0 0 0 3px rgba(74,135,208,.18);
}
/* Sem isso, o Chrome/Edge troca o fundo do campo autopreenchido (senha salva) pro highlight
   claro padrão deles, por cima do nosso fundo escuro — o ícone do olho (colorido pra contrastar
   com fundo escuro) quase some ali, e piora no hover (fica ainda mais claro sobre um fundo já
   quase branco). Força o campo a manter a cor do tema mesmo autopreenchido. */
.fx-field input:-webkit-autofill,
.fx-field input:-webkit-autofill:hover,
.fx-field input:-webkit-autofill:focus {
  -webkit-text-fill-color: var(--fx-title);
  -webkit-box-shadow: 0 0 0 1000px var(--fx-input-bg) inset;
  box-shadow: 0 0 0 1000px var(--fx-input-bg) inset;
  transition: background-color 9999s ease-in-out 0s;
}

.fx-pass-wrap { position: relative; }
.fx-pass-wrap input { padding-right: 44px; }
.fx-eye-btn {
  position: absolute; top: 0; right: 0; bottom: 0;
  width: 44px; min-height: 44px;
  display: flex; align-items: center; justify-content: center;
  background: transparent; border: none; color: var(--fx-subtitle);
  cursor: pointer; padding: 0;
}
.fx-eye-btn:hover { color: var(--fx-title); }
.fx-eye-btn:focus-visible { outline: 2px solid var(--fx-focus-border); outline-offset: -2px; border-radius: var(--fx-radius); }

.fx-forgot { text-align: right; margin: -6px 0 20px; }
.fx-forgot a {
  font-size: 12.5px; color: var(--fx-link); text-decoration: none;
  min-height: 44px; display: inline-flex; align-items: center;
}
.fx-forgot a:hover { text-decoration: underline; }

.fx-btn-primary {
  width: 100%; min-height: 44px;
  background: var(--fx-btn-bg); color: #fff; border: none;
  border-radius: var(--fx-radius); padding: 12px;
  font-size: 14px; font-weight: 700; font-family: inherit;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
}
.fx-btn-primary:hover { filter: brightness(1.08); }
.fx-btn-primary:disabled { opacity: .7; cursor: default; filter: none; }
.fx-btn-primary:focus-visible { outline: 2px solid var(--fx-focus-border); outline-offset: 2px; }
.fx-spinner {
  width: 15px; height: 15px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,.4); border-top-color: #fff;
  animation: fx-spin .7s linear infinite;
}
@keyframes fx-spin { to { transform: rotate(360deg); } }

.fx-divider { display: flex; align-items: center; gap: 10px; margin: 20px 0; }
.fx-divider::before, .fx-divider::after { content: ''; flex: 1; height: .5px; background: var(--fx-divider); }
.fx-divider span { font-size: 12px; color: var(--fx-subtitle); }

.fx-btn-google {
  width: 100%; min-height: 44px; box-sizing: border-box;
  background: transparent; border: .5px solid var(--fx-google-border);
  border-radius: var(--fx-radius); color: var(--fx-title);
  font-size: 14px; font-weight: 600; font-family: inherit;
  text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;
}
.fx-btn-google:hover { background: rgba(255,255,255,.04); color: var(--fx-title); }
.fx-btn-google:focus-visible { outline: 2px solid var(--fx-focus-border); outline-offset: 2px; }

.fx-signup { text-align: center; margin-top: 24px; font-size: 13px; color: var(--fx-subtitle); }
.fx-signup a { color: var(--fx-orange); text-decoration: none; font-weight: 600; }
.fx-signup a:hover { text-decoration: underline; }

@media (max-width: 899.98px) {
  .fx-container { max-width: 400px; min-height: auto; margin: 48px auto; flex-direction: column; }
  .fx-left { display: none; }
  .fx-right { flex: 1 1 100%; max-width: 100%; padding: 0 24px; }
  .fx-mobile-logo { display: block; text-align: center; margin-bottom: 24px; }
}
</style>

<div class="fx-login-page">
  <div class="fx-container">

    <div class="fx-left">
      <div class="fx-logo"><span class="fx-logo-fixa">Fixa</span><span class="fx-logo-os">OS</span></div>
      <h1 class="fx-left-title">Sua bancada, organizada.</h1>
      <p class="fx-left-sub">Ordens de serviço, clientes, estoque e financeiro num lugar só.</p>
      <ul class="fx-features">
        <li><i class="bi bi-camera-fill fx-feat-camera" aria-hidden="true"></i> Fotos do estado de entrada em toda OS</li>
        <li><i class="bi bi-whatsapp fx-feat-whatsapp" aria-hidden="true"></i> Orçamento e comprovante pelo WhatsApp</li>
        <li><i class="bi bi-upc-scan fx-feat-scan" aria-hidden="true"></i> Etiqueta lida pela câmera do celular</li>
      </ul>
      <div class="fx-badges">
        <span><i class="bi bi-lock-fill" aria-hidden="true"></i> Conexão segura</span>
        <span><i class="bi bi-hdd-network-fill" aria-hidden="true"></i> Servidores no Brasil</span>
        <span><i class="bi bi-shield-check" aria-hidden="true"></i> Conforme a LGPD</span>
      </div>
    </div>

    <div class="fx-right">
      <div class="fx-right-inner">

        <div class="fx-mobile-logo">
          <span class="fx-logo"><span class="fx-logo-fixa">Fixa</span><span class="fx-logo-os">OS</span></span>
        </div>

        <h2 class="fx-right-title">Bem-vindo de volta</h2>
        <p class="fx-right-sub">Entre para continuar</p>

        <?php if ($err): ?>
          <div class="fx-error" role="alert"><?= e($err) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/login') ?>" id="fxLoginForm" novalidate>
          <?= csrf_field() ?>

          <div class="fx-field">
            <label for="fxEmail">E-mail, celular ou CNPJ/CPF</label>
            <input type="text" name="login" id="fxEmail" placeholder="seu@email.com, celular ou CNPJ/CPF" autocomplete="username"
                   value="<?= e(old('login')) ?>" required autofocus>
          </div>

          <div class="fx-field">
            <label for="fxSenha">Senha</label>
            <div class="fx-pass-wrap">
              <input type="password" name="senha" id="fxSenha" placeholder="••••••••" autocomplete="current-password" required>
              <button type="button" class="fx-eye-btn" id="fxToggleSenha" aria-label="Mostrar senha">
                <i class="bi bi-eye" id="fxEyeIcon" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <div class="fx-forgot">
            <a href="<?= url('/esqueci-senha') ?>">Esqueci minha senha</a>
          </div>

          <button type="submit" class="fx-btn-primary" id="fxBtnEntrar">
            <span id="fxBtnLabel">Entrar</span>
          </button>
        </form>

        <div class="fx-divider"><span>ou</span></div>

        <a href="<?= url('/auth/google') ?>" class="fx-btn-google">
          <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
          Entrar com Google
        </a>

        <div class="fx-signup">
          Ainda não usa o FixaOS?
          <a href="<?= url('/cadastrar') ?>">Teste grátis por 15 dias, sem cartão</a>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
(function(){
  var eyeBtn  = document.getElementById('fxToggleSenha');
  var senha   = document.getElementById('fxSenha');
  var eyeIcon = document.getElementById('fxEyeIcon');
  eyeBtn.addEventListener('click', function(){
    var mostrando = senha.type === 'password';
    senha.type = mostrando ? 'text' : 'password';
    eyeIcon.className = mostrando ? 'bi bi-eye-slash' : 'bi bi-eye';
    eyeBtn.setAttribute('aria-label', mostrando ? 'Ocultar senha' : 'Mostrar senha');
  });

  var form  = document.getElementById('fxLoginForm');
  var btn   = document.getElementById('fxBtnEntrar');
  var label = document.getElementById('fxBtnLabel');
  form.addEventListener('submit', function(){
    if (btn.disabled) return;
    btn.disabled = true;
    label.textContent = 'Entrando…';
    var spin = document.createElement('span');
    spin.className = 'fx-spinner';
    spin.setAttribute('aria-hidden', 'true');
    btn.insertBefore(spin, label);
  });
})();
</script>
