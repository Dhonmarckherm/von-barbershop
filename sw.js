self.addEventListener('push', function(event) {
  console.log('Push notification received:', event);
  
  if (event.data) {
    const data = event.data.json();
    
    const options = {
      body: data.body || 'New notification from VON BARBER STUDIO',
      icon: '/assets/images/rubiks.jpg',
      badge: '/assets/images/rubiks.jpg',
      vibrate: [200, 100, 200],
      tag: data.tag || 'von-barbershop-notification',
      data: {
        url: data.url || '/my_appointments.php'
      },
      actions: [
        {
          action: 'view',
          title: 'View',
          icon: '/assets/images/rubiks.jpg'
        },
        {
          action: 'close',
          title: 'Close',
          icon: '/assets/images/rubiks.jpg'
        }
      ]
    };
    
    event.waitUntil(
      self.registration.showNotification(data.title || 'VON BARBER STUDIO', options)
    );
  }
});

self.addEventListener('notificationclick', function(event) {
  console.log('Notification clicked:', event);
  
  event.notification.close();
  
  if (event.action === 'view' || !event.action) {
    const urlToOpen = event.notification.data.url || '/my_appointments.php';
    
    event.waitUntil(
      clients.matchAll({
        type: 'window',
        includeUncontrolled: true
      }).then(function(windowClients) {
        // Check if there's already a window/tab open
        for (let i = 0; i < windowClients.length; i++) {
          const client = windowClients[i];
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        // Open a new window/tab
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
    );
  }
});

self.addEventListener('pushsubscriptionchange', function(event) {
  console.log('Push subscription changed, re-subscribing...');
  event.waitUntil(
    self.registration.pushManager.subscribe({
      userVisibleOnly: true
    }).then(function(subscription) {
      console.log('Re-subscribed to push notifications');
      // Send new subscription to server
      return fetch('/api/save_push_subscription.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          subscription: subscription
        })
      });
    })
  );
});

const CACHE_NAME = 'von-barbershop-v5';
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
