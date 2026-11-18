/**
 * PowerMagz — Service Worker
 * Handles offline fallbacks and basic caching.
 */
const CACHE_NAME = 'pmgz-cache-v2';
// Resolve relative to this file so it works under any subfolder.
const OFFLINE_URL = new URL('offline.html', self.location).pathname;

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
            // Only page navigations get the offline screen. Returning HTML for a
            // failed asset or API call would just hide the real error.
            if (event.request.mode === 'navigate') {
                return caches.match(OFFLINE_URL);
            }
            return Response.error();
        })
    );
});