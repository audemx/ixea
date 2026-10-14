// /src/public/eros/sw.js - IXEA EROS Kernel Worker

const CACHE_NAME = 'ixea-eros-v1.0.2';

self.addEventListener('install', (event) => {
  // Fuerza al SW nuevo a activarse sin esperar a que el usuario cierre la pestaña
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  // Toma el control inmediato de todas las páginas abiertas dentro del scope
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // 1. IGNORAR ABSOLUTAMENTE PETICIONES AL SERVIDOR LOCAL DE IMPRESIÓN
  // Esto evita que el SW intercepte las llamadas a localhost:9100 provocando bloqueos de CORS/PNA o errores 503.
  if (url.hostname === 'localhost' || url.hostname === '127.0.0.1' || url.port === '9100') {
    return; // Permite que la petición fluya directo por la red local sin interferencias.
  }

  // 2. GESTIÓN TRANSPARENTE DE RED Y OFFLINE PARA LA NUBE
  event.respondWith(
    fetch(event.request).catch((error) => {
      console.warn('[Eros Kernel] Fetch failed or offline:', event.request.url, error);

      // Retorna una respuesta de error HTTP 503 (Service Unavailable) estándar
      return new Response('Network error occurred', {
        status: 503,
        statusText: 'Service Unavailable',
        headers: new Headers({ 'Content-Type': 'text/plain' })
      });
    })
  );
});