/**
 * Musoftware Production-Grade Service Worker
 * ===========================================
 * Orchestrates offline functionality, static resource precaching,
 * and high-fidelity runtime caching policies for Laravel + React Inertia.
 */

const CACHE_VERSION = 'musoftware-v3';
const CACHE_NAME = `musoftware-cache-${CACHE_VERSION}`;
const OFFLINE_PAGE = '/offline.html';

// Resources to pre-cache on install (App Shell)
const PRECACHE_ASSETS = [
    '/',
    '/dashboard',
    '/tools',
    OFFLINE_PAGE,
    '/favicon.svg',
    '/favicon-16x16.png',
    '/favicon-32x32.png',
    '/favicon-48x48.png',
    '/icons/pwa-192.png',
    '/icons/pwa-512.png',
    '/icons/apple-touch-icon.png',
    '/icons/maskable-192.png',
    '/icons/maskable-512.png'
];

// Cache Limits Config
const MAX_IMAGE_CACHE_ITEMS = 50;
const MAX_API_CACHE_ITEMS = 100;

// Helper: Limit cache size to prevent browser storage overflow
async function trimCache(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxItems) {
        // Delete oldest entries
        for (let i = 0; i < keys.length - maxItems; i++) {
            await cache.delete(keys[i]);
        }
    }
}

// ── 1. INSTALL: Pre-cache App Shell ──────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.debug('[SW] Pre-caching core App Shell');
                return cache.addAll(PRECACHE_ASSETS).catch(err => {
                    console.warn('[SW] Pre-caching encountered non-fatal issues:', err);
                });
            })
            .then(() => self.skipWaiting())
    );
});

// ── 2. ACTIVATE: Claim clients and clean old cache instances ─────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => {
                return Promise.all(
                    keys.map(key => {
                        if (key !== CACHE_NAME) {
                            console.debug('[SW] Removing deprecated cache instance:', key);
                            return caches.delete(key);
                        }
                    })
                );
            })
            .then(() => self.clients.claim())
    );
});

// ── 3. FETCH: Intercept requests and apply custom caching models ──────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-HTTP schemes (chrome-extension://, data:, blob:, etc.)
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return;
    }

    // Skip local automation agents (always let requests pass natively)
    if (url.hostname === '127.0.0.1' || url.port === '18400' || url.port === '18401') {
        return;
    }

    // Skip Vite Hot Module Replacement (HMR) and web-sockets
    if (url.pathname.startsWith('/@') || url.pathname.includes('hot-update') || request.headers.get('Upgrade') === 'websocket') {
        return;
    }

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // A. API Calls (/api/*) -> Network First with cache fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            networkFirst(request, MAX_API_CACHE_ITEMS)
        );
        return;
    }

    // B. Static Assets (CSS, JS, WebFonts, Images) -> Stale-While-Revalidate
    const isStaticAsset = /\.(js|css|png|jpg|jpeg|webp|gif|svg|ico|woff2?|ttf|otf|mp3|mp4)(\?.*)?$/.test(url.pathname);
    if (isStaticAsset) {
        event.respondWith(
            staleWhileRevalidate(request, isStaticAsset && /\.(png|jpg|jpeg|webp|gif)$/.test(url.pathname) ? MAX_IMAGE_CACHE_ITEMS : 200)
        );
        return;
    }

    // C. HTML Navigation Documents -> Network First falling back to App Shell or Offline page
    if (request.mode === 'navigate') {
        event.respondWith(
            navigationHandler(request)
        );
    }
});

// ── 4. STRATEGIES ────────────────────────────────────────────────────────────

// Strategy: Network First (falls back to cached copy, otherwise returns error response)
async function networkFirst(request, maxItems) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            if (maxItems) {
                trimCache(CACHE_NAME, maxItems);
            }
        }
        return networkResponse;
    } catch (error) {
        console.debug('[SW] Fetch failed; retrieving from cache:', request.url);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        // If it's a JSON request, send offline fallback json
        if (request.headers.get('Accept')?.includes('application/json')) {
            return new Response(
                JSON.stringify({ error: 'offline', message: 'You are currently offline. Please check your connection.' }),
                { status: 503, headers: { 'Content-Type': 'application/json' } }
            );
        }
        throw error;
    }
}

// Strategy: Stale-While-Revalidate (returns cache instantly, updates cache in background)
async function staleWhileRevalidate(request, maxItems) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);

    const fetchPromise = fetch(request).then(async networkResponse => {
        if (networkResponse.ok && networkResponse.status === 200) {
            await cache.put(request, networkResponse.clone());
            if (maxItems) {
                trimCache(CACHE_NAME, maxItems);
            }
        }
        return networkResponse;
    }).catch(err => {
        console.debug('[SW] Background fetch failed for stale-while-revalidate:', request.url, err);
    });

    return cachedResponse || fetchPromise;
}

// Strategy: Navigation Handler (Network first, falls back to specific HTML pages or offline.html)
async function navigationHandler(request) {
    try {
        // Always try fetching latest version from network first
        return await fetch(request);
    } catch (error) {
        console.debug('[SW] Navigation fetch failed, initiating offline fallback routing', error);
        
        // 1. Check exact match in cache
        const exactCached = await caches.match(request);
        if (exactCached) return exactCached;

        // 2. Check general Dashboard fallback if dashboard sub-url
        const url = new URL(request.url);
        if (url.pathname.startsWith('/dashboard')) {
            const cachedDashboard = await caches.match('/dashboard');
            if (cachedDashboard) return cachedDashboard;
        }

        // 3. Fall back to Offline Shell page
        const offlineShell = await caches.match(OFFLINE_PAGE);
        if (offlineShell) return offlineShell;

        // 4. Raw response if nothing works
        return new Response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Offline</title></head><body><h1>Offline</h1><p>Check your internet connection.</p></body></html>',
            { headers: { 'Content-Type': 'text/html' } }
        );
    }
}

// ── 5. PUSH NOTIFICATIONS ───────────────────────────────────────────────────
self.addEventListener('push', event => {
    if (!event.data) return;
    
    let payload = {};
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Musoftware', body: event.data.text() };
    }

    const options = {
        body: payload.body || '',
        icon: payload.icon || '/icons/pwa-192.png',
        badge: payload.badge || '/icons/pwa-72.png',
        vibrate: [100, 50, 100],
        data: {
            url: payload.url || '/dashboard'
        },
        actions: payload.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(payload.title || 'Musoftware', options)
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    const clickActionPromise = self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then(windowClients => {
            const targetUrl = event.notification.data?.url || '/dashboard';
            // If tab already exists, focus it
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            // Otherwise open a new window/tab
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        });

    event.waitUntil(clickActionPromise);
});

// ── 6. BACKGROUND SYNC ──────────────────────────────────────────────────────
self.addEventListener('sync', event => {
    if (event.tag === 'sync-platform-data') {
        console.debug('[SW] Background syncing platform data...');
        event.waitUntil(performBackgroundSync());
    }
});

async function performBackgroundSync() {
    // In-app sync logic: fetch offline queues and dispatch to server
    // Future extension hook point
    return Promise.resolve();
}
