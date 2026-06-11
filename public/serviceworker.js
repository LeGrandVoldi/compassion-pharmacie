const CACHE_NAME = 'mini-pwa-cache-v1';
const URLsToCache = [
'/',
'/favicon.ico',
'/manifest.json',
// ajouter d'autres ressources si besoin
];


self.addEventListener('install', (event) => {
event.waitUntil(
caches.open(CACHE_NAME).then((cache) => cache.addAll(URLsToCache))
);
});


self.addEventListener('activate', (event) => {
event.waitUntil(self.clients.claim());
});


self.addEventListener('fetch', (event) => {
event.respondWith(
caches.match(event.request).then((response) => {
return response || fetch(event.request);
})
);
});
