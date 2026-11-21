// Service worker: membuat aplikasi bisa diinstal, menampilkan halaman offline,
// dan menerima Web Push (notifikasi di layar walau aplikasi ditutup).
// Halaman aplikasi sengaja TIDAK di-cache karena berisi data pribadi dan harus selalu terbaru.
var NAMA_CACHE = 'monitoring-ta-v2';
var HALAMAN_OFFLINE = '/offline.html';

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(NAMA_CACHE).then(function (cache) {
            return cache.addAll([HALAMAN_OFFLINE, '/icons/icon-192.png']);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (daftar) {
            return Promise.all(daftar.filter(function (k) { return k !== NAMA_CACHE; }).map(function (k) { return caches.delete(k); }));
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    var req = event.request;

    // Hanya tangani perpindahan halaman; kalau jaringan gagal, tampilkan halaman offline.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(function () { return caches.match(HALAMAN_OFFLINE); })
        );
    }
});

// ---- Web Push ----
self.addEventListener('push', function (event) {
    var data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (daftar) {
            // Kalau aplikasi sedang terlihat di layar, cukup toast di dalam aplikasi (hindari notifikasi ganda).
            var terlihat = daftar.some(function (c) { return c.visibilityState === 'visible'; });
            if (terlihat) { return; }

            return self.registration.showNotification(data.title || 'Monitoring TA', {
                body: data.body || '',
                icon: '/icons/icon-192.png',
                tag: data.tag || 'monitoring-ta',
                data: { url: data.url || '/dashboard' }
            });
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (daftar) {
            for (var i = 0; i < daftar.length; i++) {
                var c = daftar[i];
                if (c.url.indexOf(self.location.origin) === 0 && 'focus' in c) {
                    return c.focus().then(function (klien) {
                        return ('navigate' in klien) ? klien.navigate(url) : self.clients.openWindow(url);
                    });
                }
            }
            return self.clients.openWindow(url);
        })
    );
});
