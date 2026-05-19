/**
 * Musoftware Service Worker
 * ==========================
 * Caches tool pages so they work offline.
 * Even without internet, the user can open the tool UI and the local agent
 * (127.0.0.1:18400 / :18401) handles execution.
 *
 * Strategy:
 *   - App shell (HTML, JS, CSS) → Cache First
 *   - API calls to platform → Network First, fallback to cache
 *   - Local agent calls (127.0.0.1) → Network Only (always fresh)
 */

const CACHE_NAME    = 'musoftware-v1';
const OFFLINE_PAGE  = '/offline.html';

// Resources to pre-cache on install (app shell)
const PRECACHE = [
    '/',
    '/tools',
    '/dashboard',
    OFFLINE_PAGE,
];

// ── Install: pre-cache app shell ────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(PRECACHE).catch(err => {
                console.warn('[SW] Pre-cache error (non-fatal):', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// ── Activate: clean old caches ──────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch: routing strategy ─────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // 0. Skip non-cacheable schemes (chrome-extension://, data:, blob:, etc.)
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return;
    }

    // 1. Local agent calls → ALWAYS network only (never cache)
    if (url.hostname === '127.0.0.1') {
        return; // let browser handle it natively
    }

    // 2. Non-GET → network only
    if (event.request.method !== 'GET') {
        return;
    }

    // 3. Vite HMR / websocket → skip
    if (url.pathname.startsWith('/@') || url.pathname.includes('hot-update')) {
        return;
    }

    // 4. API calls → Network First, fallback to cache
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(event.request));
        return;
    }

    // 5. Static assets (JS, CSS, images) → Cache First
    if (/\.(js|css|png|jpg|jpeg|svg|ico|woff2?|ttf)(\?.*)?$/.test(url.pathname)) {
        event.respondWith(cacheFirst(event.request));
        return;
    }

    // 6. HTML navigation → Network First, fallback to cached page or offline
    event.respondWith(navigationHandler(event.request));
});

// ── Strategies ───────────────────────────────────────────────────────────────

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('Asset unavailable offline', { status: 503 });
    }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        return cached ?? new Response(
            JSON.stringify({ error: 'offline', cached: false }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

async function navigationHandler(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        // Try exact URL cache
        const cached = await caches.match(request);
        if (cached) return cached;

        // Try the base tools page cached version
        const toolsPage = await caches.match('/tools');
        if (toolsPage) return toolsPage;

        // Last resort: offline page
        const offlinePage = await caches.match(OFFLINE_PAGE);
        return offlinePage ?? new Response('<h1>Offline</h1>', {
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

// ── Push Notifications (future) ──────────────────────────────────────────────
self.addEventListener('push', event => {
    const data = event.data?.json() ?? {};
    event.waitUntil(
        self.registration.showNotification(data.title ?? 'Musoftware', {
            body: data.body ?? '',
            icon: '/icons/pwa-192.png',
            badge: '/icons/badge-72.png',
            data: { url: data.url ?? '/tools' },
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data?.url ?? '/tools'));
});
