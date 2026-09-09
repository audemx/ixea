<?php
/**
* Barra de identidad estática
* includes/navbar.php
*/
// Detectar si el tráfico proviene del subdominio de desarrollo o de producción
$host = $_SERVER['HTTP_HOST'] ?? '';

$isLocal = (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1'));

// Definir la URL base de EROS
$erosUrl = $isLocal ? 'http://eros.localhost:8000/login' : 'https://eros.ixea.mx/login';
?>
<nav id="brandbar" class="navbar navbar-expand-lg navbar-light bg-white ixea-brandbar sticky-top py-2">
    <div class="container">
        <a class="navbar-brand brand-ixea" href="/">
            <?php 
                $svg = file_get_contents($root_path . '/includes/isotipo.php');
                $svg = str_replace('var(--bg-color)', '#1A1A1A', $svg);
                $svg = str_replace('var(--x-color)', '#FFF', $svg);
                echo $svg;
            ?>
            <div class="brand-text d-none d-sm-block">
                IXEA<span class="slogan-ixea">Inteligencia para Construir Valor</span>
            </div>
            <div class="brand-text d-block d-sm-none">IXEA</div>
        </a>

        <div class="d-flex align-items-center ms-auto">
            <a href="#" class="btn btn-link text-dark p-2" title="Buscar"><i class="bi bi-search"></i></a>
            <a href="#" class="btn btn-link text-dark p-2" title="Contacto"><i class="bi bi-envelope"></i></a>
            <a href="<?= $erosUrl ?>" class="btn btn-link text-dark p-2"><i class="bi bi-person me-1"></i></a>
        </div>
    </div>
</nav>