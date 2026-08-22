/* PearlEdu offline shell. Bump CACHE when this file changes. */
const CACHE = 'pearledu-offline-v1';
const PRECACHE = [
  '/js/offline-first.js',
  '/js/idle-session.js',
  '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  let url;
  try {
    url = new URL(request.url);
  } catch (e) {
    return;
  }

  if (url.origin !== self.location.origin) {
    return;
  }

  if (url.pathname === '/sw.js') {
    return;
  }

  const isAsset = /\.(js|css|png|jpe?g|gif|svg|ico|woff2?|webmanifest)$/i.test(url.pathname)
    || url.pathname.startsWith('/images/');

  if (isAsset) {
    event.respondWith(cacheFirst(request));
    return;
  }

  event.respondWith(networkFirst(request));
});

function cacheFirst(request) {
  return caches.match(request).then((cached) => {
    if (cached) {
      return cached;
    }
    return fetch(request).then((response) => {
      put(request, response);
      return response;
    });
  });
}

function networkFirst(request) {
  return fetch(request).then((response) => {
    put(request, response);
    return response;
  }).catch(() => caches.match(request).then((cached) => {
    if (cached) {
      return cached;
    }
    return new Response('PearlEdu is offline and this page was not saved on this device yet. Open Attendance or Marks once while online, then try again.', {
      status: 503,
      headers: { 'Content-Type': 'text/plain; charset=utf-8' },
    });
  }));
}

function put(request, response) {
  if (!response || !response.ok || response.type === 'opaque') {
    return;
  }
  const copy = response.clone();
  caches.open(CACHE).then((cache) => cache.put(request, copy)).catch(() => {});
}
