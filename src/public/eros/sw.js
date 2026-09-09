// /src/public/eros/sw.js - IXEA OS Kernel Worker

const CACHE_NAME = 'ixea-eros-v1';

self.addEventListener('install', (event) => {
  // Fuerzo al SW nuevo a activarse sin esperar a que el usuario cierre la pestaña
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  // Toma el control inmediato de todas las páginas abiertas dentro del scope
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
  // Pasa todas las peticiones transparente a la red y maneja errores de conexión
  event.respondWith(
    fetch(event.request).catch((error) => {
      console.warn('[Eros Kernel] Fetch failed or offline:', event.request.url, error);

      // Retorna una respuesta de error HTTP 503 (Service Unavailable) para peticiones estándar
      return new Response('Network error occurred', {
        status: 503,
        statusText: 'Service Unavailable',
        headers: new Headers({ 'Content-Type': 'text/plain' })
      });
    })
  );
});
