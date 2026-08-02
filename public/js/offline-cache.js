/**
 * FixaOS — cache local (IndexedDB) para consulta de OS sem internet.
 * Fase 1: somente leitura (lista + detalhe das OS já abertas enquanto online).
 * Fase 2: rascunho de OS NOVA criada offline, sincronizado sozinho quando a
 * internet volta (nunca edita uma OS existente offline — só cria novas).
 */
(function () {
  'use strict';

  var DB_NAME = 'fixaos_offline';
  var DB_VERSION = 2;
  var dbPromise = null;

  function abrirDB() {
    if (dbPromise) return dbPromise;
    dbPromise = new Promise(function (resolve, reject) {
      if (!('indexedDB' in window)) { reject(new Error('IndexedDB indisponível')); return; }
      var req = indexedDB.open(DB_NAME, DB_VERSION);
      var caiu = false;
      // Se outra aba/versão travar a atualização, não fica pendurado pra sempre.
      var timeout = setTimeout(function () {
        caiu = true;
        dbPromise = null;
        reject(new Error('IndexedDB bloqueado — feche outras abas do FixaOS e tente de novo.'));
      }, 4000);
      req.onupgradeneeded = function () {
        var db = req.result;
        if (!db.objectStoreNames.contains('os_list')) db.createObjectStore('os_list', { keyPath: 'id' });
        if (!db.objectStoreNames.contains('os_detail')) db.createObjectStore('os_detail', { keyPath: 'id' });
        if (!db.objectStoreNames.contains('os_rascunhos')) db.createObjectStore('os_rascunhos', { keyPath: 'localId' });
      };
      req.onblocked = function () {
        // Outra aba com uma versão mais antiga do banco ainda aberta — pede pra ela liberar.
        console.warn('[offline] IndexedDB bloqueado por outra aba.');
      };
      req.onsuccess = function () {
        clearTimeout(timeout);
        if (caiu) { try { req.result.close(); } catch (e) {} return; }
        var db = req.result;
        // Libera a conexão sozinho se outra aba/versão futura precisar atualizar o banco.
        db.onversionchange = function () { db.close(); dbPromise = null; };
        resolve(db);
      };
      req.onerror = function () {
        clearTimeout(timeout);
        if (!caiu) { dbPromise = null; reject(req.error); }
      };
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

  function salvarRascunho(dados) {
    var rascunho = Object.assign({}, dados, {
      localId: 'r_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8),
      criado_offline_em: new Date().toISOString(),
      status_sync: 'pendente'
    });
    return comStore('os_rascunhos', 'readwrite', function (store) { store.put(rascunho); })
      .then(function () { return rascunho; });
  }

  function listarRascunhos() {
    return comStore('os_rascunhos', 'readonly', function (store) {
      return new Promise(function (resolve, reject) {
        var req = store.getAll();
        req.onsuccess = function () { resolve(req.result || []); };
        req.onerror = function () { reject(req.error); };
      });
    }).then(function (p) { return p; });
  }

  function removerRascunho(localId) {
    return comStore('os_rascunhos', 'readwrite', function (store) { store.delete(localId); });
  }

  function marcarErroRascunho(localId, mensagem) {
    return comStore('os_rascunhos', 'readwrite', function (store) {
      return new Promise(function (resolve, reject) {
        var req = store.get(localId);
        req.onsuccess = function () {
          var r = req.result;
          if (r) { r.status_sync = 'erro'; r.erro_msg = mensagem; store.put(r); }
        };
        req.onerror = function () { reject(req.error); };
      });
    });
  }

  function resetarErroRascunho(localId) {
    return comStore('os_rascunhos', 'readwrite', function (store) {
      return new Promise(function (resolve, reject) {
        var req = store.get(localId);
        req.onsuccess = function () {
          var r = req.result;
          if (r) { r.status_sync = 'pendente'; delete r.erro_msg; store.put(r); }
        };
        req.onerror = function () { reject(req.error); };
      });
    });
  }

  var sincronizando = false;
  function sincronizarRascunhos(syncUrl, csrfToken, onResultado) {
    if (sincronizando) return Promise.resolve({ ok: 0, erro: 0 });
    sincronizando = true;
    return listarRascunhos().then(function (rascunhos) {
      var pendentes = rascunhos.filter(function (r) { return r.status_sync !== 'erro'; });
      var ok = 0, erro = 0;
      return pendentes.reduce(function (p, rascunho) {
        return p.then(function () {
          var body = new URLSearchParams();
          Object.keys(rascunho).forEach(function (k) {
            if (k === 'localId' || k === 'status_sync' || k === 'erro_msg') return;
            body.append(k, rascunho[k] == null ? '' : rascunho[k]);
          });
          return fetch(syncUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
            body: body.toString()
          }).then(function (r) { return r.json().then(function (j) { return { r: r, j: j }; }); })
            .then(function (res) {
              if (res.r.ok && res.j.success) {
                ok++;
                return removerRascunho(rascunho.localId).then(function () {
                  if (onResultado) onResultado({ sucesso: true, rascunho: rascunho, numero: res.j.numero });
                });
              }
              erro++;
              return marcarErroRascunho(rascunho.localId, res.j.error || 'Falha ao sincronizar.').then(function () {
                if (onResultado) onResultado({ sucesso: false, rascunho: rascunho, erro: res.j.error });
              });
            }).catch(function () {
              erro++;
              if (onResultado) onResultado({ sucesso: false, rascunho: rascunho, erro: 'Falha de conexão.' });
            });
        });
      }, Promise.resolve()).then(function () {
        sincronizando = false;
        return { ok: ok, erro: erro };
      });
    }).catch(function () { sincronizando = false; return { ok: 0, erro: 0 }; });
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
    salvarRascunho: salvarRascunho,
    listarRascunhos: listarRascunhos,
    removerRascunho: removerRascunho,
    resetarErroRascunho: resetarErroRascunho,
    sincronizarRascunhos: sincronizarRascunhos,
    registrarServiceWorker: registrarServiceWorker,
    iniciarBannerConexao: iniciarBannerConexao
  };
})();
