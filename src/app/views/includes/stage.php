<?php
/**
 * IXEA OS - Contenedor Principal de Escenarios (Stage Manager)
 * /src/app/views/includes/stage-manager.php
 */

$viewsPath = dirname(__DIR__);
?>

<main id="os-viewport">
    <!-- Escenario 0: Splash Screen / Introducción -->
    <div id="stage-0" class="stage active">
        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center intro-wrapper overflow-hidden">
            <div style="min-height: 120px;" class="d-flex flex-column justify-content-center">
                <p>
                    <span id="intro-welcome" class="display-2 intro-text text-dark mb-0">BIENVENIDO </span>
                    <span id="intro-a" class="display-2 intro-text text-dark mb-0" style="visibility: hidden;">A</span>
                </p>
                <p id="intro-logo" class="display-1 intro-text fw-bold text-dark" style="visibility: hidden; height: 0; margin: 0;">
                    IXEA <span class="text-xaccent">EROS</span>
                </p>
            </div>
            
            <div class="d-flex gap-4 mt-2" id="intro-slogan">
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Intuitivo.</span>
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Rápido.</span>
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Brutal.</span>
            </div>
        </div>
    </div>

    <!-- Escenario Launchpad (Cargado desde su propio include) -->
    <?php include $viewsPath . '/includes/launchpad.php'; ?>

    <!-- Contenedor para aplicaciones dinámicas activas -->
    <div id="dynamic-stages" class="p-3"></div>
</main>