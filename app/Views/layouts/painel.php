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
<div id="painelConteudo"><?php ($content)(); ?></div>
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
// IMPORTANTE: mede o <div id="painelConteudo"> e não document.documentElement — scrollHeight do
// documento nunca fica menor que a altura do próprio iframe, então cada resize do pai fazia a
// próxima medição vir maior, crescendo pra sempre (+24px a cada ciclo, sem nunca parar sozinho).
// O div, sendo dimensionado só pelo próprio conteúdo, não sofre desse efeito.
var _avisarAlturaTimer = null;
function avisarAltura() {
  if (window.parent === window) return;
  clearTimeout(_avisarAlturaTimer);
  _avisarAlturaTimer = setTimeout(function () {
    var el = document.getElementById('painelConteudo');
    var altura = el ? Math.ceil(el.getBoundingClientRect().height) : document.documentElement.scrollHeight;
    window.parent.postMessage({ fixaosPainelAltura: altura }, window.location.origin);
  }, 80);
}
window.addEventListener('load', avisarAltura);
window.addEventListener('resize', avisarAltura);
new MutationObserver(avisarAltura).observe(document.body, { childList: true, subtree: true });

// _method override para DELETE/PUT (mesmo mecanismo do layout principal — aqui dentro do iframe
// os botões com data-method não tinham esse script e ficavam sem funcionar).
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-method]');
  if (!btn) return;
  e.preventDefault();
  const method = btn.dataset.method.toUpperCase();
  const href   = btn.dataset.href || (btn.href && !btn.href.endsWith('#') ? btn.href : null);
  if (!href) return;
  if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = href;
  form.innerHTML = `<input name="_token" value="<?= csrf_token() ?>"><input name="_method" value="${method}">`;
  document.body.appendChild(form);
  form.submit();
});
</script>
</body>
</html>
