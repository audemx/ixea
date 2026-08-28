<?php
/**
 * IXEA OS - Limpiador Automático de Temporales
 * Este script elimina archivos con más de 48 horas de antigüedad.
 */
if (php_sapi_name() !== 'cli') {
    $root_path = $_SERVER['DOCUMENT_ROOT'];
    require_once $root_path . '/security.php';
    $folder = dirname($root_path) . "/storage/temp_pdfs/";
} else {
    $folder = dirname(__DIR__) . "/storage/temp_pdfs/";
}
// 1. Ruta de la carpeta (Ajustada a tu estructura fuera de public_html)



if (is_dir($folder)) {
    $files = glob($folder . "*.pdf"); // Solo buscamos archivos PDF
    $now = time();
    $limit = 48 * 60 * 60; // 48 horas en segundos
    $count = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            $age = $now - filemtime($file);
            if ($age > $limit) {
                unlink($file); // ¡Adiós al archivo viejo!
                $count++;
            }
        }
    }
    
    if (php_sapi_name() !== 'cli') {
        echo "<h1>Limpieza Finalizada</h1>";
        echo "<p>Se eliminaron <strong>$count</strong> archivos de más de 48 horas.</p>";
    }
    
    // Opcional: Registrar en el error_log cuántos se borraron
    if($count > 0) error_log("IXEA Cron: Se eliminaron $count archivos temporales caducados.");
} else {
    error_log("IXEA Cron Error: No se encontró la carpeta $folder");
}