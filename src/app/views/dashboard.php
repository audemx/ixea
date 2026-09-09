<?php
/**
 * IXEA EROS - Dashboard View (Panel Principal)
 * /src/app/views/dashboard.php
 */
$viewsPath = __DIR__;        // Ruta a la carpeta /var/www/app/views
$publicPath = $_SERVER['DOCUMENT_ROOT']; // Ruta a la carpeta /var/www/html
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include $viewsPath . '/includes/header.php'; ?>
    <title>IXEA EROS - Dashboard</title>
</head>
<body>
    
    <?php include $viewsPath . '/includes/menu.php'; ?>

    <?php include $viewsPath . '/includes/dock.php'; ?>

    <?php include $viewsPath . '/includes/stage.php'; ?>
    
    <!-- Modal Global de la aplicación -->
    <div class="modal fade" id="ixea-main-modal" data-bs-focus="false" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div id="modal-size-handler" class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg" id="modal-content-placeholder">
            </div>
        </div>
    </div>

    <?php include $viewsPath . '/includes/scripts.php'; ?>
    
</body>
</html>