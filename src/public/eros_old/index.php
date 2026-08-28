<?php
/** /index.php **/
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include $root_path . '/includes/header.php'; ?>
    <title>IXEA EROS</title>
</head>
<body>

    <?php include $root_path . '/includes/menu-bar.php'; ?>

    <?php include $root_path . '/includes/dock-main.php'; ?>

    <?php include $root_path . '/includes/dock-widgets.php'; ?>

    <?php include $root_path . '/includes/stage-manager.php'; ?>
    
    <?php include $root_path . '/includes/widgets-panel.php'; ?>
    
    <?php include $root_path . '/modules/sales/sale-ticket.php'; ?>
    
    <?php include $root_path . '/modules/credit/credit-statement-ticket.php'; ?>
    
    <?php include $root_path . '/modules/purchases/purchase-ticket.php'; ?>
    
    <div class="modal fade" id="ixea-main-modal" data-bs-focus="false" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div id="modal-size-handler" class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg" id="modal-content-placeholder">
                </div>
        </div>
    </div>

    <?php include $root_path . '/includes/scripts.php'; ?>
    
</body>
</html>