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
