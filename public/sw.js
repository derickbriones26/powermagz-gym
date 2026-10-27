/**
 * PowerMagz — Service Worker
 * Handles offline fallbacks and basic caching.
 */
const CACHE_NAME = 'pmgz-cache-v1';
const OFFLINE_URL = '/src/Views/errors/offline.php'; // Adjust if your base path is different

// 1. Install event - cache the offline page
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            // We only strictly need the offline page for the fallback
            return cache.addAll([OFFLINE_URL]);
        })
    );
    self.skipWaiting();
});

// 2. Fetch event - intercept network requests
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request).catch(() => {
            // If network fails (offline), return the cached offline page
            return caches.match(OFFLINE_URL);
        })
    );
});