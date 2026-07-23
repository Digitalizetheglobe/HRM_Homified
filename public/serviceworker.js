const staticCacheName = "pwa-v" + new Date().getTime();

const filesToCache = [
  '/',
  '/dashboard',
  '/offline',
  '/css/app.css',
  '/js/app.js',
  '/assets/fonts/tabler-icons.min.css',
  '/assets/fonts/fontawesome.css',
  '/assets/fonts/feather.css',
  '/assets/css/plugins/style.css',
  '/assets/fonts/tabler/tabler-icons.woff2',
  '/assets/fonts/tabler/tabler-icons.woff',
  '/assets/fonts/tabler/tabler-icons.ttf',
  '/assets/fonts/fontawesome/fa-solid-900.woff2',
  '/assets/fonts/fontawesome/fa-solid-900.woff',
  '/assets/fonts/fontawesome/fa-solid-900.ttf',
  '/assets/fonts/fontawesome/fa-regular-400.woff2',
  '/assets/fonts/fontawesome/fa-regular-400.woff',
  '/assets/fonts/fontawesome/fa-regular-400.ttf',
  '/assets/fonts/fontawesome/fa-brands-400.woff2',
  '/assets/fonts/fontawesome/fa-brands-400.woff',
  '/assets/fonts/fontawesome/fa-brands-400.ttf',
  '/images/icons/apk_icon.png',
];

// Install
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(staticCacheName).then(cache => {
      // Use return to ensure install fails if cache fails
      return cache.addAll(filesToCache);
    })
  );
});

// Activate
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames =>
      Promise.all(
        cacheNames
          .filter(cache => cache.startsWith("pwa-"))
          .filter(cache => cache !== staticCacheName)
          .map(cache => caches.delete(cache))
      )
    )
  );
});

// Fetch
self.addEventListener('fetch', event => {
  // Skip cross-origin requests and non-GET requests
  if (event.request.method !== 'GET') return;

  // Skip PDF and download routes to ensure background downloads work correctly
  const url = event.request.url;
  if (url.includes('.pdf') || 
      url.includes('download') || 
      url.includes('export') ||
      url.includes('offer_letter') ||
      url.includes('increment_letter')) {
    return;
  }

  // Navigation requests (Pages): Network First, fallback to cache/offline
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          // Fallback to cached dashboard or offline page
          return caches.match('/dashboard') || caches.match('/') || caches.match('/offline');
        })
    );
    return;
  }

  // Static assets: Cache First, fallback to network
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request).catch(() => {
        // Only return offline page if it's a page request
        if (event.request.destination === 'document') {
          return caches.match('/offline');
        }
      });
    })
  );
});
