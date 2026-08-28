<?php
/* 
 * Encabezado global
 * includes/header.php
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?php 
    $svg = file_get_contents($root_path . '/includes/isotipo.php');
    
    // Reemplazamos las variables por colores sólidos para el navegador
    $svg = str_replace('var(--bg-color)', '#FFF', $svg);
    $svg = str_replace('var(--x-color)', '#000', $svg);
    
    $svg = preg_replace('/\s+/', ' ', $svg);
    echo str_replace('"', "'", rawurlencode(trim($svg))); 
?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="/assets/css/main.css?v=<?php echo filemtime($root_path . '/assets/css/main.css'); ?>">
<link rel="stylesheet" href="/assets/css/menu.css?v=<?php echo filemtime($root_path . '/assets/css/menu.css'); ?>">
<link rel="stylesheet" href="/assets/css/docks.css?v=<?php echo filemtime($root_path . '/assets/css/docks.css'); ?>">
<link rel="stylesheet" href="/assets/css/stages.css?v=<?php echo filemtime($root_path . '/assets/css/stages.css'); ?>">
<link rel="stylesheet" href="/assets/css/widgets.css?v=<?php echo filemtime($root_path . '/assets/css/widgets.css'); ?>">
<link rel="stylesheet" href="/assets/css/apps.css?v=<?php echo filemtime($root_path . '/assets/css/aps.css'); ?>">