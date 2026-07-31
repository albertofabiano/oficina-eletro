<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($titulo ?? 'Manual do Usuário — FixaOS') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  html,body{margin:0;padding:0;background:#f1f5f9}
  body{min-height:100vh}
  .manual-wrap{min-height:100vh!important}
  .manual-fechar{
    position:fixed;top:14px;right:14px;z-index:2000;
    background:#1e3a5f;color:#fff;border:none;border-radius:999px;
    width:40px;height:40px;display:flex;align-items:center;justify-content:center;
    text-decoration:none;box-shadow:0 4px 14px rgba(0,0,0,.3);font-size:1.15rem;
  }
  .manual-fechar:hover{background:#15294a;color:#fff}
</style>
</head>
<body>
<a href="<?= url('/') ?>" class="manual-fechar" title="Voltar ao site"><i class="bi bi-x-lg"></i></a>
<?php ($content)(); ?>
</body>
</html>
