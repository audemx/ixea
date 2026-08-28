<?php
/**
* Barra de navegación para móviles (Submenu)
* includes/navmov.php
*/
?>
<div class="ixea-mobile-nav fixed-bottom bg-dark border-top border-secondary d-md-none">
    <div class="container">
        <div class="row text-center align-items-center py-2">
            <div class="col">
                <a href="/about/eros" class="nav-link text-white small">
                    <i class="bi bi-stack d-block fs-4"></i>
                    Productos
                </a>
            </div>
            <div class="col">
                <a href="#beneficios" class="nav-link text-white small">
                    <i class="bi bi-people d-block fs-4"></i>
                    Clientes
                </a>
            </div>
            <div class="col">
                <a href="#about" class="nav-link text-white small">
                    <i class="bi bi-building d-block fs-4"></i>
                    Compañia
                </a>
            </div>
            <div class="col">
                <button class="btn btn-link nav-link text-white small w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                    <i class="bi bi-list d-block fs-4"></i>
                    Menú
                </button>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end bg-dark text-white" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title fw-bold text-primary" id="mobileMenuLabel">IXEA ECOSYSTEM</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column gap-3">
            <li class="nav-item border-bottom border-secondary pb-2">
                <a class="nav-link link-light fs-5 p-0" href="#demo"><i class="bi bi-calendar-check-fill me-2"></i> Demo</a>
            </li>
            <li class="nav-item border-bottom border-secondary pb-2">
                <a class="nav-link link-light fs-5 p-0" href="#soporte"><i class="bi bi-headset me-2"></i> Soporte Técnico</a>
            </li>
            <li class="nav-item border-bottom border-secondary pb-2">
                <a class="nav-link link-light fs-5 p-0 fw-bold d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#collapseMobileLabs" role="button" aria-expanded="false" aria-controls="collapseMobileLabs">
                    <span><i class="bi bi-cpu-fill me-2"></i> AI Labs</span>
                    <i class="bi bi-chevron-down small fs-6"></i>
                </a>
                <div class="collapse mt-2 ps-3" id="collapseMobileLabs">
                    <ul class="nav flex-column gap-2 border-start border-secondary ps-2">
                        <li class="nav-item">
                            <a class="nav-link text-white-50 p-0 py-1 small" href="/labs/market-intelligence" data-bs-dismiss="offcanvas">
                                <i class="bi bi-graph-up-arrow text-primary me-2"></i> Market Intelligence
                            </a>
                        </li>
                        <li class="nav-item">
                            <span class="text-muted small py-1 d-block opacity-50">
                                <i class="bi bi-lock me-2"></i> Más módulos en desarrollo...
                            </span>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
        <div class="mt-5 text-center">
            <div class="mb-3 opacity-50">
                <?php echo str_replace(['var(--bg-color)', 'var(--x-color)'], ['#0d6efd', '#FFFFFF'], $svg); ?>
            </div>
            <p class="small text-muted">IXEA © 2026<br>Inteligencia Predictiva</p>
        </div>
    </div>
</div>