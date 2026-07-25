/**
 * FixaOS — service worker (Fase 1: só leitura offline).
 * Cacheia o "app shell" (CSS/JS/ícones) e cai pra uma página offline
 * quando a navegação falha por falta de internet.
 */
var CACHE_NAME = 'fixaos-shell-v1';

var PRECACHE_URLS = [
  '/offline.html',
  '/css/app.css',
  '/js/masks.js',
  '/js/offline-cache.js',
  '/site.webmanifest',
  '/favicon.ico',
  '/icon-192.png',
  '/icon-512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
  'https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js'
];

self.addEventListener('install', function (event) {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return Promise.all(
        PRECACHE_URLS.map(function (u) {
          return cache.add(u).catch(function (e) {
            console.warn('[sw] falha ao pré-cachear', u, e);
          });
        })
      );
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (nomes) {
      return Promise.all(
        nomes.filter(function (n) { return n !== CACHE_NAME; }).map(function (n) { return caches.delete(n); })
      );
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET') return;

  // Navegação de página inteira (clicar num link, digitar URL, F5)
  if (req.mode === 'navigate') {
    var url = new URL(req.url);
    event.respondWith(
      fetch(req).catch(function () {
        if (url.pathname === '/offline.html') return caches.match('/offline.html');
        return Response.redirect('/offline.html?from=' + encodeURIComponent(url.pathname), 302);
      })
    );
    return;
  }

  // Assets estáticos (próprios + CDN): cache-first com atualização em segundo plano
  event.respondWith(
    caches.match(req).then(function (cached) {
      var fetchPromise = fetch(req).then(function (resp) {
        if (resp && resp.ok) {
          var copia = resp.clone();
          caches.open(CACHE_NAME).then(function (cache) { cache.put(req, copia); });
        }
        return resp;
      }).catch(function () { return cached; });
      return cached || fetchPromise;
    })
  );
});
