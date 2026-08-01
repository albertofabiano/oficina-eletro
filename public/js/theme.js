/* Sistema de temas do FixaOS — aplicação, reação ao SO e persistência.
   O flash inicial já foi evitado por um script inline no <head> (roda antes
   deste arquivo); aqui só ficam o listener de mudança do SO e a gravação
   da preferência quando o usuário troca no seletor. */
(function () {
  'use strict';

  var STORAGE_KEY = 'fx_tema';

  function computedTheme(pref) {
    return pref === 'dark' || (pref === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
      ? 'dark' : 'light';
  }

  function preferenciaAtual() {
    return localStorage.getItem(STORAGE_KEY) || 'auto';
  }

  function aplicarTema(pref) {
    var escuro = computedTheme(pref) === 'dark';
    document.documentElement.dataset.theme = escuro ? 'dark' : 'light';
    window.dispatchEvent(new CustomEvent('fx-theme-change', { detail: { theme: document.documentElement.dataset.theme, pref: pref } }));
  }

  // Enquanto a aba estiver aberta em "automático", acompanha o tema do SO em tempo real.
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
      if (preferenciaAtual() === 'auto') aplicarTema('auto');
    });
  }

  window.FxTheme = {
    current: preferenciaAtual,
    computed: computedTheme,
    apply: aplicarTema,
    /** Grava a preferência (local + servidor) e aplica na hora. */
    set: function (pref, csrfToken, saveUrl) {
      localStorage.setItem(STORAGE_KEY, pref);
      aplicarTema(pref);
      if (saveUrl) {
        fetch(saveUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
          body: 'tema=' + encodeURIComponent(pref)
        }).catch(function () {});
      }
    }
  };
})();
