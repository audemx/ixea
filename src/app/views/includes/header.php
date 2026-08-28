<?php
/**
 * IXEA EROS - Encabezado Global (Meta & Assets CSS)
 * /src/app/views/includes/header.php
 */

$publicPath = $_SERVER['DOCUMENT_ROOT']; // Apunta a /src/public

// Helper seguro para versionado de assets por timestamp
$getAssetVersion = function($relativePath) use ($publicPath) {
    $fullPath = $publicPath . $relativePath;
    return file_exists($fullPath) ? filemtime($fullPath) : '1.0.0';
};
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Favicon dinámico generado desde el isotipo SVG -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?php 
    $isotypePath = __DIR__ . '/isotype.php';
    if (file_exists($isotypePath)) {
        $svg = file_get_contents($isotypePath);
        $svg = str_replace('var(--bg-color)', '#FFF', $svg);
        $svg = str_replace('var(--x-color)', '#000', $svg);
        $svg = preg_replace('/\s+/', ' ', $svg);
        echo str_replace('"', "'", rawurlencode(trim($svg))); 
    }
?>">

<!-- Frameworks CSS externos (CDNs) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- Estilos propios de la aplicación con cache busting dinámico -->
<link rel="stylesheet" href="/assets/css/main.css?v=<?= $getAssetVersion('/assets/css/main.css'); ?>">
<link rel="stylesheet" href="/assets/css/menu.css?v=<?= $getAssetVersion('/assets/css/menu.css'); ?>">
<link rel="stylesheet" href="/assets/css/dock.css?v=<?= $getAssetVersion('/assets/css/dock.css'); ?>">
<link rel="stylesheet" href="/assets/css/stage.css?v=<?= $getAssetVersion('/assets/css/stage.css'); ?>">
<link rel="stylesheet" href="/assets/css/apps.css?v=<?= $getAssetVersion('/assets/css/apps.css'); ?>">