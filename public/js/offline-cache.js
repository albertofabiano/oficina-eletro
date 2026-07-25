/**
 * FixaOS — cache local (IndexedDB) para consulta de OS sem internet.
 * Fase 1: somente leitura. Guarda a lista e o detalhe das OS já abertas
 * enquanto online, pra continuar consultando quando a conexão cair.
 */
(function () {
  'use strict';

  var DB_NAME = 'fixaos_offline';
  var DB_VERSION = 1;
  var dbPromise = null;

  function abrirDB() {
    if (dbPromise) return dbPromise;
    dbPromise = new Promise(function (resolve, reject) {
      if (!('indexedDB' in window)) { reject(new Error('IndexedDB indisponível')); return; }
      var req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = function () {
        var db = req.result;
        if (!db.objectStoreNames.contains('os_list')) db.createObjectStore('os_list', { keyPath: 'id' });
        if (!db.objectStoreNames.contains('os_detail')) db.createObjectStore('os_detail', { keyPath: 'id' });
      };
      req.onsuccess = function () { resolve(req.result); };
      req.onerror = function () { reject(req.error); };
    });
    return dbPromise;
  }

  function comStore(nome, modo, fn) {
    return abrirDB().then(function (db) {
      return new Promise(function (resolve, reject) {
        var tx = db.transaction(nome, modo);
        var store = tx.objectStore(nome);
        var resultado = fn(store);
        tx.oncomplete = function () { resolve(resultado); };
        tx.onerror = function () { reject(tx.error); };
      });
    });
  }

  function salvarListaOS(linhas) {
    if (!Array.isArray(linhas) || !linhas.length) return Promise.resolve();
    return comStore('os_list', 'readwrite', function (store) {
      linhas.forEach(function (row) {
        if (row && row.id) { row._salvo_em = Date.now(); store.put(row); }
      });
    }).catch(function (e) { console.warn('[offline] falha ao salvar lista de OS', e); });
  }

  function salvarDetalheOS(os) {
    if (!os || !os.id) return Promise.resolve();
    os._salvo_em = Date.now();
    return comStore('os_detail', 'readwrite', function (store) { store.put(os); })
      .catch(function (e) { console.warn('[offline] falha ao salvar detalhe de OS', e); });
  }

  function listarOSCache() {
    return comStore('os_list', 'readonly', function (store) {
      return new Promise(function (resolve, reject) {
        var req = store.getAll();
        req.onsuccess = function () { resolve(req.result || []); };
        req.onerror = function () { reject(req.error); };
      });
    }).then(function (p) { return p; });
  }

  function buscarOSCache(id) {
    return comStore('os_detail', 'readonly', function (store) {
      return new Promise(function (resolve, reject) {
        var req = store.get(Number(id));
        req.onsuccess = function () { resolve(req.result || null); };
        req.onerror = function () { reject(req.error); };
      });
    }).then(function (p) { return p; });
  }

  function registrarServiceWorker(swUrl) {
    if (!('serviceWorker' in navigator)) return;
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(swUrl).catch(function (e) {
        console.warn('[offline] falha ao registrar service worker', e);
      });
    });
  }

  function iniciarBannerConexao() {
    var el = document.getElementById('fixaosOfflineBanner');
    if (!el) return;
    function atualizar() {
      el.style.display = navigator.onLine ? 'none' : 'flex';
    }
    window.addEventListener('online', atualizar);
    window.addEventListener('offline', atualizar);
    atualizar();
  }

  window.FixaosOffline = {
    salvarListaOS: salvarListaOS,
    salvarDetalheOS: salvarDetalheOS,
    listarOSCache: listarOSCache,
    buscarOSCache: buscarOSCache,
    registrarServiceWorker: registrarServiceWorker,
    iniciarBannerConexao: iniciarBannerConexao
  };
})();
