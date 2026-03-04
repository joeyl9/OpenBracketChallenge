const CACHE_NAME = 'open-bracket-v1';
const ASSETS_TO_CACHE = [
    './',
    './index.php',
    './dashboard.php',
    './images/style.css',
    './manifest.json',
    './images/scoreboard_icon.png',
    './js/jquery-3.7.1.min.js',
    './images/jquery.dataTables.min.css',
    './js/jquery.dataTables.min.js',
    './js/lib/chart.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

self.addEventListener('fetch', (event) => {
    // Simple cache-first strategy for static assets, network-first for php
    if (event.request.url.includes('.php')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match(event.request);
            })
        );
    } else {
        event.respondWith(
            caches.match(event.request).then((response) => {
                return response || fetch(event.request);
            })
        );
    }
});
