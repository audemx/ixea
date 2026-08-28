<?php
/**
* Barra de navegación funcional (Submenu)
* includes/navbar.php
*/
?>
<div class="ixea-navbar sticky-top bg-dark d-none d-md-block">
    <div class="container">
        <ul class="nav d-flex justify-content-between align-items-center">
            <li id="navLogo" class="nav-item me-3 ixea-nav-logo">
                <a class="navbar-brand p-0" href="/">
                    <?php 
                        $svg = file_get_contents($root_path . '/includes/isotipo.php');
                        $svg = str_replace('var(--bg-color)', '#FFF', $svg);
                        $svg = str_replace('var(--x-color)', '#000', $svg);
                        echo $svg;
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link link-light ps-0 active" href="/about/eros">Productos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link link-light" href="#beneficios">Clientes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link link-light" href="#precios">Compañia</a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle link-light fw-bold" href="#" id="nav-dropdown-labs" role="button" data-bs-toggle="dropdown" aria-expanded="false"> AI Labs</a>
                <ul class="dropdown-menu dropdown-menu-dark bg-dark border-secondary shadow" aria-labelledby="nav-dropdown-labs">
                    <li>
                        <a class="dropdown-item py-2 text-white" href="/labs/market-intelligence">
                            <i class="bi bi-graph-up-arrow link-light me-2"></i> Market Intelligence
                        </a>
                    </li>
                    <!-- Incorporar nuevos items -->
                </ul>
            </li>
            <li class="nav-item ms-auto">
                <a class="nav-link link-light" href="#demo">Agendar Demo</a>
            </li>
        </ul>
    </div>
</div>