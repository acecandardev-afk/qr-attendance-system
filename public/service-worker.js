const CACHE_NAME = 'smart-attendance-shell-v5';
// Do not precache '/' — that HTML embeds a CSRF token; stale copies cause POST 419 Page Expired.
const SHELL_URLS = [
  '/offline.html',
  '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(SHELL_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      )
    ).then(() => self.clients.claim())
  );
});

// Navigation requests: network first, fallback to offline shell
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request, { cache: 'no-store' }).catch(() =>
        caches.match('/offline.html').then((response) => response)
      )
    );
    return;
  }

  const url = new URL(event.request.url);
  // Live faculty JSON must not be read from the precache (stale subjects after edit).
  if (url.pathname.includes('/sessions/today-data')) {
    event.respondWith(fetch(event.request, { cache: 'no-store' }));
    return;
  }

  // Never cache auth HTML — stale pages embed old CSRF tokens → 419 or "stuck" POST on mobile.
  const p = url.pathname;
  if (
    p === '/' ||
    p === '/login' ||
    p.startsWith('/forgot-password') ||
    p.startsWith('/reset-password')
  ) {
    event.respondWith(fetch(event.request, { cache: 'no-store' }));
    return;
  }

  // For other GETs, try network, then cache
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        return response;
      })
      .catch(() => caches.match(event.request).then((response) => response || caches.match('/')))
  );
});
