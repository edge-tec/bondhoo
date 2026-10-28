// Bondhoo Social Network Enterprise Progressive Web App (PWA) Service Worker v3.0 (jugajug-v2-cache)
const CACHE_NAME = 'bondhoo-pwa-v3.0';
const OFFLINE_URL = '/offline';

const PRECACHE_ASSETS = [
    '/',
    '/dashboard',
    '/offline',
    '/manifest.json',
    '/favicon.ico',
    'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap'
];

// 1. Service Worker Installation & Asset Pre-caching
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// 2. Service Worker Activation & Old Cache Eviction
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Network Fetch Routing & Offline Fallbacks
self.addEventListener('fetch', (event) => {
    // Only handle HTTP/HTTPS GET requests
    if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
        return;
    }

    // A. Navigation Requests (HTML pages) -> Network First with Offline Fallback
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    if (networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(event.request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    const offlinePage = await caches.match(OFFLINE_URL);
                    return offlinePage || new Response('Offline', { status: 503, statusText: 'Offline' });
                })
        );
        return;
    }

    // B. Static Assets (CSS, JS, Fonts, Images) -> Stale-While-Revalidate
    const url = new URL(event.request.url);
    const isStaticAsset = url.pathname.match(/\.(css|js|woff2|woff|ttf|png|jpg|jpeg|svg|webp|ico)$/i)
        || url.hostname.includes('fonts.googleapis.com')
        || url.hostname.includes('fonts.gstatic.com');

    if (isStaticAsset) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                const fetchPromise = fetch(event.request).then((networkResponse) => {
                    if (networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // C. Default Requests -> Network First with Cache Fallback
    event.respondWith(
        fetch(event.request)
            .then((networkResponse) => {
                if (networkResponse.status === 200 && event.request.method === 'GET') {
                    const copy = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                }
                return networkResponse;
            })
            .catch(() => caches.match(event.request))
    );
});

// 4. Web Push Notification Listener (VAPID / FCM Web Push)
self.addEventListener('push', (event) => {
    let payload = {};
    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            payload = { body: event.data.text() };
        }
    }

    const title = payload.title || payload.notification?.title || 'বন্ধু সোশ্যাল নোটিফিকেশন';
    const body = payload.body || payload.notification?.body || 'আপনার অ্যাকাউন্টে নতুন আপডেট এসেছে।';
    const icon = payload.icon || '/images/bondhoo-icon-192.png';
    const badge = payload.badge || '/favicon.ico';
    const targetUrl = payload.data?.url || payload.click_action || '/dashboard';
    const isCall = payload.data?.type === 'call.incoming' || payload.data?.action === 'call.incoming';

    const options = {
        body: body,
        icon: icon,
        badge: badge,
        vibrate: isCall ? [250, 100, 250, 100, 250] : [100, 50, 100],
        tag: payload.data?.tag || (isCall ? 'call-notification' : 'general-notification'),
        renotify: isCall,
        requireInteraction: isCall, // Keeps call notification on screen until user interacts
        data: {
            url: targetUrl,
            type: payload.data?.type || 'general',
            call_id: payload.data?.call_id || null,
        },
        actions: isCall ? [
            { action: 'answer', title: 'কল রিসিভ করুন' },
            { action: 'decline', title: 'কেটে দিন' }
        ] : [
            { action: 'open', title: 'দেখুন' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// 5. Notification Click & App Navigation Handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const action = event.action;
    const notificationData = event.notification.data || {};
    let targetUrl = notificationData.url || '/dashboard';

    if (action === 'decline' && notificationData.call_id) {
        // Handle call decline if needed
        return;
    }

    if (action === 'answer' && notificationData.call_id) {
        targetUrl = `/calls/${notificationData.call_id}`;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            // Check if there is already a window/tab open with the app
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes(self.registration.scope) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            // If not, open a new window/tab
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
