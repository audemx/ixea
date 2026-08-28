<?php
/**
 * Página por defecto para recursos no encontrados / Error 404
 * /default.php
 */
http_response_code(404);
$root_path = $_SERVER['DOCUMENT_ROOT'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include $root_path . '/includes/header.php'; ?>
    <title>Página no encontrada - IXEA</title>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <?php include $root_path . '/includes/brandbar.php'; ?>
    <?php include $root_path . '/includes/navbar.php'; ?>
    <?php include $root_path . '/includes/navmov.php'; ?>

    <section class="container my-auto py-5 text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <span class="display-1 fw-bold text-primary">404</span>
                <h1 class="fw-bold text-dark mt-2">Recurso no disponible</h1>
                <p class="lead text-muted my-4">
                    La página o el recurso al que intenta acceder no existe o se ha movido de ubicación dentro del ecosistema **IXEA**.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="/" class="btn btn-primary px-4 py-2 fw-bold">Volver al Inicio</a>
                    <a href="#contacto" class="btn btn-outline-dark px-4 py-2">Soporte Tecnico</a>
                </div>
            </div>
        </div>
    </section>

    <?php include $root_path . '/includes/prefooter.php'; ?>
    <?php include $root_path . '/includes/footer.php'; ?>
    <?php include $root_path . '/includes/footer_scripts.php'; ?>
</body>
</html>