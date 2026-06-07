const CACHE_NAME = 'von-barbershop-v4';
const urlsToCache = [
  '/',
  '/assets/css/style.css',
  '/assets/images/rubiks.jpg',
  '/assets/images/von-barber-logo.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        // Cache each URL individually, ignore failures
        return Promise.allSettled(
          urlsToCache.map(url => 
            fetch(url)
              .then(response => cache.put(url, response.clone()))
              .catch(err => console.log('Failed to cache:', url, err))
          )
        );
      })
  );
  self.skipWaiting();
});

self.addEventListener('fetch', event => {
  // NEVER cache PHP files - they contain dynamic session data!
  if (event.request.url.endsWith('.php') || event.request.url.includes('.php?')) {
    event.respondWith(
      fetch(event.request)
        .catch(err => {
          console.log('PHP fetch failed, returning network error:', err);
          // Return a fallback response for failed PHP requests
          return new Response('Network error. Please check your connection.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain' }
          });
        })
    );
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request).then(response => {
          // Don't cache non-successful responses
          if (!response || response.status !== 200) {
            return response;
          }
          // Only cache GET requests
          if (event.request.method !== 'GET') {
            return response;
          }
          // NEVER cache HTML or PHP files
          const contentType = response.headers.get('content-type');
          if (contentType && (contentType.includes('text/html') || contentType.includes('php'))) {
            return response;
          }
          // Clone the response
          const responseToCache = response.clone();
          caches.open(CACHE_NAME)
            .then(cache => {
              // Only cache same-origin requests
              if (event.request.url.startsWith(self.location.origin)) {
                cache.put(event.request, responseToCache);
              }
            })
            .catch(err => console.log('Cache put failed:', err));
          return response;
        }).catch(err => {
          console.log('Fetch failed:', err);
          // Return offline fallback if available
          return caches.match('/index.php');
        });
      })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(cacheName => cacheName !== CACHE_NAME)
          .map(cacheName => caches.delete(cacheName))
      );
    })
  );
  self.clients.claim();
});
