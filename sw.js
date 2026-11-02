// sw.js - Service Worker for MovieManager (Network-First for HTML, Cache-First for static)
const CACHE_VERSION = 'v1.2.1';
const CACHE_STATIC = `moviemanager-static-${CACHE_VERSION}`;
const CACHE_IMAGES = `moviemanager-images-${CACHE_VERSION}`;
const CACHE_RUNTIME = `moviemanager-runtime-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

// فایل‌های استاتیک ضروری جهت کش اولیه برای عملکرد کامل آفلاین
const PRECACHE_STATIC = [
    '/manifest.json',
    '/offline.html',
    '/static/icons/icon-192.png',
    '/static/icons/icon-512.png',
    '/static/fonts/IRANSansX-Regular.woff2',
    '/static/fonts/IRANSansX-Bold.woff2',
    '/static/fonts/IRANSansX-Thin.woff2',
    '/static/js/html2canvas.min.js',
    '/static/js/jspdf.umd.min.js',
    'https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js'
];

// Whitelist: چه چیزهایی رو کش کنیم
const CACHEABLE_ORIGINS = [
    self.location.origin,
    'https://cdn.jsdelivr.net',
    'https://cdnjs.cloudflare.com'
];

// ============================================================
// Install Event - Precache Essential Static Files
// ============================================================
self.addEventListener('install', (event) => {
    console.log('[SW] Installing version:', CACHE_VERSION);

    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then((cache) => {
                console.log('[SW] Precaching static files');
                // استفاده از addAll با مدیریت خطا برای هر فایل
                return Promise.allSettled(
                    PRECACHE_STATIC.map(url =>
                        cache.add(url).catch(err => {
                            console.warn('[SW] Failed to precache:', url, err);
                        })
                    )
                );
            })
            .then(() => {
                console.log('[SW] Skip waiting');
                return self.skipWaiting();
            })
    );
});

// ============================================================
// Activate Event - Clean Old Caches
// ============================================================
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating version:', CACHE_VERSION);

    const validCaches = [CACHE_STATIC, CACHE_IMAGES, CACHE_RUNTIME];

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter(name => !validCaches.includes(name))
                        .map(name => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[SW] Claiming clients');
                return self.clients.claim();
            })
    );
});

// ============================================================
// Fetch Event - Smart Caching Strategy
// ============================================================
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // فقط GET رو کش کن
    if (request.method !== 'GET') return;

    // API calls: همیشه network-only (بدون cache)
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    // Logout: همیشه network-only
    if (url.pathname.includes('logout')) {
        return;
    }

    // Admin paths: network-only
    if (url.pathname.startsWith('/admin/')) {
        return;
    }

    // ===== استراتژی‌ها =====

    // 1. فایل‌های استاتیک (fonts, icons, css, js) → Cache-First
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request, CACHE_STATIC));
        return;
    }

    // 2. تصاویر آیکن دستاوردها (trophies) → Cache-First با cache مجزا
    if (url.pathname.startsWith('/static/trophies/')) {
        event.respondWith(cacheFirst(request, CACHE_IMAGES, 100)); // max 100 items
        return;
    }

    // 3. تصاویر بنر → Network-First با fallback
    if (url.pathname.startsWith('/uploads/banners/')) {
        event.respondWith(networkFirst(request, CACHE_IMAGES, 50));
        return;
    }

    // 4. HTML pages (PHP) → Network-First با offline fallback
    if (isHTMLRequest(request)) {
        event.respondWith(networkFirstWithOffline(request));
        return;
    }

    // 5. سایر درخواست‌ها → Network-First
    event.respondWith(networkFirst(request, CACHE_RUNTIME, 50));
});

// ============================================================
// Helper: Cache-First Strategy
// ============================================================
async function cacheFirst(request, cacheName, maxItems = null) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);

        // فقط پاسخ‌های موفق و same-origin یا CORS معتبر رو کش کن
        if (response.ok && (response.type === 'basic' || response.type === 'cors')) {
            cache.put(request, response.clone());

            // اگر maxItems مشخص شده، قدیمی‌ها رو پاک کن
            if (maxItems) {
                trimCache(cacheName, maxItems);
            }
        }

        return response;
    } catch (error) {
        console.warn('[SW] Fetch failed:', request.url, error);

        // fallback برای تصاویر
        if (request.destination === 'image') {
            return new Response(
                '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text y="50" x="50" text-anchor="middle" fill="#999" font-size="12">آفلاین</text></svg>',
                { headers: { 'Content-Type': 'image/svg+xml' } }
            );
        }

        throw error;
    }
}

// ============================================================
// Helper: Network-First Strategy
// ============================================================
async function networkFirst(request, cacheName, maxItems = null) {
    const cache = await caches.open(cacheName);

    try {
        const response = await fetch(request);

        if (response.ok && (response.type === 'basic' || response.type === 'cors')) {
            cache.put(request, response.clone());

            if (maxItems) {
                trimCache(cacheName, maxItems);
            }
        }

        return response;
    } catch (error) {
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }

        throw error;
    }
}

// ============================================================
// Helper: Network-First with Offline Fallback (برای HTML)
// ============================================================
async function networkFirstWithOffline(request) {
    const cache = await caches.open(CACHE_RUNTIME);

    try {
        const response = await fetch(request);

        // ذخیره موفقیت‌آمیز صفحات در کش ران‌تایم برای دسترسی آفلاین PWA
        if (response.ok && (response.type === 'basic' || response.type === 'cors')) {
            cache.put(request, response.clone());
            trimCache(CACHE_RUNTIME, 20);
        }
        return response;
    } catch (error) {
        // ارائه نسخه کش شده صفحه در صورت قطعی اینترنت
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }

        // در صورت نبود کش این صفحه، ارائه صفحه آفلاین
        const offline = await caches.match(OFFLINE_URL);
        if (offline) {
            return offline;
        }

        return new Response(
            '<html dir="rtl"><body style="font-family:Tahoma;text-align:center;padding:50px;"><h1>آفلاین هستید</h1><p>لطفاً اتصال اینترنت خود را بررسی کنید.</p></body></html>',
            {
                status: 503,
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
            }
        );
    }
}

// ============================================================
// Helper: تشخیص نوع فایل
// ============================================================
function isStaticAsset(url) {
    const path = url.pathname;
    return (
        path.startsWith('/static/fonts/') ||
        path.startsWith('/static/icons/') ||
        path.endsWith('.css') ||
        path.endsWith('.js') ||
        path.endsWith('.woff2') ||
        path.endsWith('.woff')
    );
}

function isHTMLRequest(request) {
    const accept = request.headers.get('accept') || '';
    return (
        request.destination === 'document' ||
        accept.includes('text/html')
    );
}

// ============================================================
// Helper: Trim Cache (LRU-like)
// ============================================================
async function trimCache(cacheName, maxItems) {
    try {
        const cache = await caches.open(cacheName);
        const keys = await cache.keys();

        if (keys.length > maxItems) {
            // حذف قدیمی‌ترین‌ها (FIFO)
            const toDelete = keys.slice(0, keys.length - maxItems);
            await Promise.all(toDelete.map(key => cache.delete(key)));
            console.log(`[SW] Trimmed ${toDelete.length} items from ${cacheName}`);
        }
    } catch (error) {
        console.warn('[SW] Trim cache error:', error);
    }
}

// ============================================================
// Message Handler
// ============================================================
self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }

    if (event.data === 'clearCache') {
        event.waitUntil(
            caches.keys().then(names =>
                Promise.all(names.map(name => caches.delete(name)))
            ).then(() => {
                console.log('[SW] All caches cleared');
            })
        );
    }
});