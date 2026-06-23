<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar — OficinaTech</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background: linear-gradient(135deg,#1a1d23 0%,#212529 100%); min-height:100vh; }
.login-card { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="col-12 col-sm-8 col-md-5 col-lg-4">
  <?php ($content)(); ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>"></script>
</body>
</html>
