/* TreinoPro — service worker.
   O nome do cache vem da versão passada no registro (sw.js?v=X.Y.Z):
   versão nova => cache novo => todo mundo recebe a atualização na hora. */
var VERSAO = new URL(self.location).searchParams.get('v') || 'dev';
var CACHE = 'treinopro-' + VERSAO;
var ASSETS = ['assets/app.css?v=' + VERSAO, 'assets/app.js?v=' + VERSAO];

self.addEventListener('install', function (e) {
  e.waitUntil(caches.open(CACHE).then(function (c) { return c.addAll(ASSETS).catch(function () {}); }));
  self.skipWaiting();                       // assume o controle imediatamente
});

self.addEventListener('activate', function (e) {
  e.waitUntil(
    caches.keys().then(function (ks) {
      return Promise.all(ks.filter(function (k) { return k !== CACHE; })
                          .map(function (k) { return caches.delete(k); }));
    }).then(function () { return self.clients.claim(); })
     .then(function () {                    // avisa as abas abertas para recarregar
       return self.clients.matchAll({ type: 'window' }).then(function (cs) {
         cs.forEach(function (c) { c.postMessage({ tipo: 'versao-nova', versao: VERSAO }); });
       });
     })
  );
});

self.addEventListener('message', function (e) {
  if (e.data && e.data.tipo === 'atualizar-agora') self.skipWaiting();
});

/* ---------------- notificações push ---------------- */
self.addEventListener('push', function (e) {
  var d = {};
  try { d = e.data ? e.data.json() : {}; } catch (err) { d = { corpo: e.data && e.data.text() }; }
  e.waitUntil(self.registration.showNotification(d.titulo || 'TreinoPro', {
    body: d.corpo || '',
    icon: 'assets/icone-192.png',
    badge: 'assets/icone-192.png',
    vibrate: [80, 50, 80],
    tag: d.tag || 'treinopro',
    data: { url: d.url || './' }
  }));
});

self.addEventListener('notificationclick', function (e) {
  e.notification.close();
  var alvo = (e.notification.data && e.notification.data.url) || './';
  e.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (cs) {
    for (var i = 0; i < cs.length; i++) {
      if (cs[i].url.indexOf(self.registration.scope) === 0 && 'focus' in cs[i]) {
        cs[i].navigate(alvo); return cs[i].focus();
      }
    }
    return clients.openWindow(alvo);
  }));
});

self.addEventListener('fetch', function (e) {
  var url = new URL(e.request.url);
  if (e.request.method !== 'GET') return;                  // nunca cacheia POST/AJAX
  if (url.pathname.indexOf('/assets/') === -1) return;     // só assets estáticos
  e.respondWith(caches.match(e.request).then(function (r) { return r || fetch(e.request); }));
});
