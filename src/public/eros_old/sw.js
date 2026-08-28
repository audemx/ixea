// sw.js - IXEA EROS V3
const CACHE_NAME = 'ixea-eros-v3';

// SOLO cacheamos archivos críticos que sabemos que existen y son estáticos
const urlsToCache = [
  '/'
 ];

self.addEventListener('fetch', event => {
});