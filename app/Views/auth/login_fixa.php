<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<style>
.cf-page{min-height:100vh;background:#1B1025;padding:48px 16px;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;display:flex;align-items:center;justify-content:center}
.cf-wrap{width:100%;max-width:400px}
.cf-brand{text-align:center;margin-bottom:20px}
.cf-brand a{text-decoration:none;font-size:24px;font-weight:900;color:#fff;letter-spacing:-.5px}
.cf-brand a span{color:#3CC9C0}
.cf-card{background:#fff;border-radius:18px;padding:28px 26px;box-shadow:0 24px 60px rgba(0,0,0,.35);border-top:4px solid #8C7CFF}
.cf-card h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.35rem;margin:0 0 6px;color:#111827;text-align:center}
.cf-card .sub{color:#64748b;font-size:.88rem;text-align:center;margin-bottom:22px}
.cf-field{margin-bottom:14px}
.cf-field label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px}
.cf-field input[type=text],.cf-field input[type=email],.cf-field input[type=password]{
  width:100%;border:1px solid #d8dee9;border-radius:10px;padding:12px 14px;font-size:1rem;box-sizing:border-box;
}
.cf-field input:focus{outline:none;border-color:#8C7CFF;box-shadow:0 0 0 3px rgba(140,124,255,.15)}
.cf-btn{width:100%;background:#8C7CFF;color:#fff;border:none;border-radius:10px;padding:13px;font-weight:700;font-size:1rem;cursor:pointer;margin-top:6px}
.cf-btn:hover{background:#7563f0}
.cf-flash{border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px}
.cf-flash-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.cf-links{text-align:center;margin-top:16px;font-size:.84rem;color:#64748b;display:flex;flex-direction:column;gap:6px}
.cf-links a{color:#8C7CFF;font-weight:600;text-decoration:none}
.cf-google{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;border:1.5px solid #d8dee9;border-radius:10px;padding:11px;font-size:.92rem;font-weight:600;color:#374151;text-decoration:none;margin-bottom:14px;background:#fff}
.cf-google:hover{background:#f8fafc}
.cf-div{display:flex;align-items:center;gap:10px;margin:0 0 16px;color:#94a3b8;font-size:.78rem}
.cf-div::before,.cf-div::after{content:'';flex:1;height:1px;background:#e2e8f0}
</style>

<div class="cf-page">
  <div class="cf-wrap">
    <div class="cf-brand"><a href="<?= url('/') ?>">Carteira<span> Fixa</span></a></div>
    <div class="cf-card">
      <h1>Entrar</h1>
      <div class="sub">Acesse sua conta do Carteira Fixa</div>

      <?php $err = flash('error'); if ($err): ?>
        <div class="cf-flash cf-flash-err"><?= e($err) ?></div>
      <?php endif; ?>

      <a href="<?= url('/auth/google?to=fixa') ?>" class="cf-google">
        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
        Continuar com Google
      </a>
      <div class="cf-div"><span>ou com e-mail e senha</span></div>

      <form method="POST" action="<?= url('/login') ?>" novalidate>
        <?= csrf_field() ?>

        <div class="cf-field">
          <label for="cfLoginEmail">E-mail</label>
          <input type="email" id="cfLoginEmail" name="login" required autocomplete="email" autofocus>
        </div>
        <div class="cf-field">
          <label for="cfLoginSenha">Senha</label>
          <input type="password" id="cfLoginSenha" name="senha" required autocomplete="current-password">
        </div>

        <button type="submit" class="cf-btn">Entrar</button>
      </form>

      <div class="cf-links">
        <a href="<?= url('/esqueci-senha') ?>">Esqueci minha senha</a>
        <span>Ainda não tem conta? <a href="<?= url('/carteira-fixa/cadastrar') ?>">Cadastre-se grátis</a></span>
      </div>
    </div>
  </div>
</div>
