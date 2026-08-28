<?php
/** /header.php
 *  Configura la estructura básica y los estilos de la cabecera de un sitio web.
 *  Su función principal es preparar la página para que se vea de forma correcta y moderna en cualquier pantalla.
 *  Para lograrlo, realiza tres tareas clave de forma automática: activa la compatibilidad con dispositivos móviles, conecta el diseño del sitio con herramientas externas de diseño (Bootstrap), e implementa un sistema inteligente que actualiza el icono de la pestaña y los estilos CSS locales en tiempo real, evitando que los usuarios vean una versión desactualizada o rota de la página.
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
    <link rel="stylesheet" href="/styles.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/styles.css'); ?>">