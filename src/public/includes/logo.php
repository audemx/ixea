<?php
/**
 * Ixea - Identidad Visual
 * includes/logo.php
 */
?>
<div class="ixetech-brand py-2 text-center">
    <div class="d-flex align-items-center justify-content-center">
        <div class="me-3">
            <?php include 'isotipo.php'; ?>
        </div>
    
        <div class="d-flex flex-column align-items-start">
            <div class="lh-1">
                <span class="ixe-text">IXE</span><span class="tech-text">TECH</span>
            </div>
        </div>
    </div>
    
    <div class="slogan-text">
        Inteligencia para el control empresarial
    </div>
</div>

<style>
    /* Importamos los pesos necesarios: 200 (Tech), 500 (Slogan), 600 (Ixe) */
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@200;500;600&display=swap');

    .ixetech-brand {
        font-family: 'Montserrat', sans-serif;
        display: inline-block;
    }

    .ixe-text {
        font-weight: 600; /* Peso Semi-Bold: más delgado pero con presencia */
        font-size: 38px;
        letter-spacing: -0.5px;
        color: var(--ixe-color);
        line-height: 1;
    }

    .tech-text {
        font-weight: 200; /* Peso Extra-Light: el toque tecnológico */
        font-size: 38px;
        letter-spacing: -1px;
        color: var(--ixe-color);
        line-height: 1;
    }

    .slogan-text {
        font-weight: 500;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--ixe-color);
        opacity: 0.95;
        margin-top: 5px;
        text-align: center;
    }

    :root { 
        --ixe-color: #002B5B; 
    }
    
    /* Adaptación a modo oscuro */
    [data-bs-theme="dark"] :root, .dark-mode :root { 
        --ixe-color: #ffffff; 
    }
</style>