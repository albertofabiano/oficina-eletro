<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Master Login — FixaOS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body { background:linear-gradient(135deg,#0a0d13 0%,#0f1117 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; }
.login-card { background:#141720; border:1px solid rgba(255,255,255,.08); border-radius:16px; padding:2.5rem; width:100%; max-width:380px; box-shadow:0 30px 80px rgba(0,0,0,.6); }
.form-control { background:rgba(255,255,255,.06)!important; border:1px solid rgba(255,255,255,.12)!important; color:#fff!important; }
.form-control::placeholder { color:rgba(255,255,255,.3); }
</style>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2477643924516118" crossorigin="anonymous"></script>
</head>
<body>
<div class="login-card">
  <div class="text-center mb-4">
    <div class="bg-danger rounded-2 d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px">
      <i class="bi bi-shield-fill-check text-white fs-4"></i>
    </div>
    <h4 class="text-white fw-bold mb-0">Master Admin</h4>
    <p class="text-muted small">Painel de gerenciamento global</p>
  </div>

  <?php $err = flash('error'); if ($err): ?>
  <div class="alert alert-danger py-2 small mb-3"><?= e($err) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= url('/master/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label text-muted small">E-mail</label>
      <input type="email" name="email" class="form-control" placeholder="master@oficina.com" required autofocus>
    </div>
    <div class="mb-4">
      <label class="form-label text-muted small">Senha</label>
      <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
    </div>
    <button class="btn btn-danger w-100 fw-semibold">
      <i class="bi bi-shield-lock me-1"></i>Acessar painel master
    </button>
  </form>

  <div class="text-center mt-3">
    <a href="<?= url('/login') ?>" class="text-muted small">← Voltar ao login normal</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
