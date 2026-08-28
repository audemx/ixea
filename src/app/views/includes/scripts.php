<?php
/**
 * IXEA OS - Scripts y Bibliotecas Globales
 * /src/app/views/includes/scripts.php
 * 
 * NOTA: Configura el objeto global JS window.IXEA_USER usando $currentUser
 */

$publicPath = $_SERVER['DOCUMENT_ROOT'];

// Helper seguro para cache-busting dinámico de archivos JavaScript
$getJsVersion = function($relativePath) use ($publicPath) {
    $fullPath = $publicPath . $relativePath;
    return file_exists($fullPath) ? filemtime($fullPath) : '1.0.0';
};
?>
<!-- Dependencias de Terceros (CDNs) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.0.1"></script>

<!-- Contexto global del usuario autenticado para el Kernel de JS -->
<script>
window.IXEA_USER = {
    permissions: <?= json_encode($currentUser['userPermissions'] ?? []); ?>,
    user_id: <?= json_encode($currentUser['userId']); ?>,
    user_name: <?= json_encode($currentUser['userName']); ?>,
    user_role: <?= json_encode($currentUser['roleName']); ?>,
    user_email: <?= json_encode($currentUser['userEmail']); ?>,
    is_super: <?= json_encode((bool)$currentUser['isSuper']); ?>
};

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW registrado correctamente'))
            .catch(err => console.error('Error registrando SW:', err));
    });
}
</script>

<!-- Scripts principales de IXEA OS con versionado dinámico -->
<script src="/assets/js/os-kernel.js?v=<?= $getJsVersion('/assets/js/os-kernel.js'); ?>"></script>
<script src="/assets/js/os-ui.js?v=<?= $getJsVersion('/assets/js/os-ui.js'); ?>"></script>
<script src="/assets/js/os-widgets.js?v=<?= $getJsVersion('/assets/js/os-widgets.js'); ?>"></script>
<script src="/assets/js/os-apis.js?v=<?= $getJsVersion('/assets/js/os-apis.js'); ?>"></script>