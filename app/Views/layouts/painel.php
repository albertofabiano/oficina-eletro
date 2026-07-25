<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo ?? 'Configurações') ?> — FixaOS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= url('/css/app.css') ?>?v=<?= filemtime(BASE_PATH.'/public/css/app.css') ?>">
<style>
body { background: #fff; padding: 1.25rem 1.5rem 2rem; }
.is-valid   { border-color: #198754 !important; }
.is-invalid { border-color: #dc3545 !important; }
</style>
</head>
<body>
<?php ($content)(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/masks.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.modal, .offcanvas').forEach(function (el) {
    if (el.parentElement !== document.body) document.body.appendChild(el);
  });
});
// Avisa a página pai (aba de Configurações) da altura real, pra ajustar o iframe sem scroll interno.
// Debounced: o próprio redimensionamento do iframe pelo pai dispara 'resize' aqui dentro,
// então sem isso dava pra entrar num vaivém de mensagens (tremor de scroll).
var _avisarAlturaTimer = null;
function avisarAltura() {
  if (window.parent === window) return;
  clearTimeout(_avisarAlturaTimer);
  _avisarAlturaTimer = setTimeout(function () {
    window.parent.postMessage({ fixaosPainelAltura: document.documentElement.scrollHeight }, window.location.origin);
  }, 80);
}
window.addEventListener('load', avisarAltura);
window.addEventListener('resize', avisarAltura);
new MutationObserver(avisarAltura).observe(document.body, { childList: true, subtree: true });
</script>
</body>
</html>
