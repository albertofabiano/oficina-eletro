/**
 * Busca/filtro/paginação do marketplace público (/pecas) sem recarregar a página.
 * Intercepta os formulários de busca (.mp-ajax-form) e os links de filtro/categoria/
 * paginação (.mp-ajax-link, .mp-cat-link, .page-link), busca o HTML novo via fetch
 * e troca só o miolo (#mpBody) — a URL na barra de endereço continua real (pushState),
 * então recarregar a página ou compartilhar o link sempre funciona normalmente.
 * Se algo falhar, cai pra navegação comum (window.location).
 */
(function () {
  function ajaxLoad(url, push) {
    push = push !== false;
    var atual = document.getElementById('mpBody');
    if (!atual) { window.location.href = url; return; }

    atual.style.opacity = '.45';
    atual.style.pointerEvents = 'none';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) {
        if (!r.ok) throw new Error('http ' + r.status);
        var titulo = r.headers.get('X-Page-Title');
        if (titulo) { try { document.title = decodeURIComponent(titulo); } catch (err) {} }
        return r.text();
      })
      .then(function (html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        var novo = tmp.querySelector('#mpBody');
        var alvo = document.getElementById('mpBody');
        if (!novo || !alvo) throw new Error('resposta sem #mpBody');
        alvo.replaceWith(novo);
        if (push) history.pushState({ mpAjax: true }, '', url);
        novo.scrollIntoView({ behavior: 'smooth', block: 'start' });
      })
      .catch(function () {
        window.location.href = url;
      });
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('mp-ajax-form')) return;
    e.preventDefault();
    var params = new URLSearchParams();
    new FormData(form).forEach(function (v, k) { if (v) params.append(k, v); });
    var qs = params.toString();
    ajaxLoad(form.getAttribute('action') + (qs ? '?' + qs : ''));
  });

  document.addEventListener('click', function (e) {
    var a = e.target.closest('a.mp-ajax-link, a.mp-cat-link, a.page-link');
    if (!a) return;
    e.preventDefault();
    ajaxLoad(a.getAttribute('href'));
  });

  window.addEventListener('popstate', function () {
    ajaxLoad(location.href, false);
  });
})();
