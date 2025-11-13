const CACHE_NAME = 'planificador-kanban-v3';
const urlsToCache = [
  '/planificador/',
  '/planificador/index.php',
  '/planificador/public/css/styles.css',
  '/planificador/public/js/app.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
];

// Instalar Service Worker
self.addEventListener('install', event => {
  console.log('[Service Worker] Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Cacheando archivos');
        return cache.addAll(urlsToCache);
      })
      .catch(err => {
        console.error('[Service Worker] Error al cachear:', err);
      })
  );
  self.skipWaiting();
});

// Activar Service Worker
self.addEventListener('activate', event => {
  console.log('[Service Worker] Activando...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Eliminando caché antigua:', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Estrategia: Network First, fallback a Cache
self.addEventListener('fetch', event => {
  // Solo cachear GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Ignorar requests de Chrome extensions
  if (event.request.url.startsWith('chrome-extension://')) {
    return;
  }

  // No cachear peticiones a la API (con action= en la URL)
  if (event.request.url.includes('?action=')) {
    // Dejar pasar directamente a la red sin cachear
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Si la respuesta es válida, clonarla y guardar en caché
        if (response && response.status === 200 && response.type === 'basic') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        // Si falla la red, intentar servir desde caché
        return caches.match(event.request).then(response => {
          if (response) {
            console.log('[Service Worker] Sirviendo desde caché:', event.request.url);
            return response;
          }
          
          // Si no está en caché, mostrar página offline
          if (event.request.destination === 'document') {
            return caches.match('/planificador/');
          }
        });
      })
  );
});

// Sincronización en segundo plano
self.addEventListener('sync', event => {
  if (event.tag === 'sync-tasks') {
    console.log('[Service Worker] Sincronizando tareas...');
    event.waitUntil(syncTasks());
  }
});

async function syncTasks() {
  // Aquí podrías implementar lógica de sincronización
  // por ejemplo, enviar tareas pendientes cuando vuelva la conexión
  console.log('[Service Worker] Sincronización completada');
}

// Notificaciones push (opcional)
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Nueva actualización disponible',
    icon: '/planificador/public/icons/icon-192x192.png',
    badge: '/planificador/public/icons/icon-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Ver',
        icon: '/planificador/public/icons/icon-96x96.png'
      },
      {
        action: 'close',
        title: 'Cerrar',
        icon: '/planificador/public/icons/icon-96x96.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Planificador Kanban', options)
  );
});

// Manejo de clics en notificaciones
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/planificador/')
    );
  }
});
