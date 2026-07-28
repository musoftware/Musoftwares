// Auto-Clear Service Worker & Pass-through Native Fetch
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(keys.map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

// Pass all requests to native browser network without holding or caching
self.addEventListener('fetch', () => {
    return;
});
