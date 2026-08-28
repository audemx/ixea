<?php
/**
* Barra de identidad estática
* includes/navbar.php
*/
// Detectar si estamos trabajando en entorno local o en producción
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost:8000' || $_SERVER['HTTP_HOST'] === '127.0.0.1:8000');

// Definir la URL de acceso a la plataforma EROS
$erosUrl = $isLocal ? '/eros' : 'https://eros.ixea.mx';
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