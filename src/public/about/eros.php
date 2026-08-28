<?php
/**
 * About IXEA EROS
 */
 $root_path = $_SERVER['DOCUMENT_ROOT'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include $root_path . '/includes/header.php'; ?>
    <title>IXEA EROS - Enterprise Resource Operative System</title>
</head>
<body class="bg-light"> <?php include $root_path . '/includes/brandbar.php'; ?>
    <?php include $root_path . '/includes/navbar.php'; ?>
    <?php include $root_path . '/includes/navmov.php'; ?>

    <section class="pt-5 d-flex align-items-center">
        <div class="container pt-3 animate__animated animate__fadeIn" style="max-width: 900px;">
            <div class="row mb-3 align-items-center text-center text-md-start">
                <div class="col-md-3 mb-4 mb-md-0 d-flex justify-content-center">
                    <div class="system-icon-wrapper p-4 rounded-5 bg-white border border-light shadow-sm" style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%;">
                            <defs>
                                <linearGradient id="FernandaGold" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#D4AF37"/>
                                    <stop offset="100%" stop-color="#f2e2a4"/>
                                </linearGradient>
                            </defs>
                            <path d="M10 20 Q15 20 15 50 V80
                                     M15 50 C15 10 47.5 10 47.5 50 V70
                                     M47.5 50 C47.5 10 80 10 80 50 V80 Q80 90 90 90
                                     M80 60 C80 40 95 40 95 55 
                                     C95 65 80 90 60 90" 
                                  stroke="url(#FernandaGold)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            <circle cx="90" cy="90" r="3" fill="#D4AF37">
                                <animate attributeName="opacity" values="0.2;1;0.2" dur="2s" repeatCount="indefinite" />
                            </circle>
                        </svg>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="display-6 fw-light mb-0 text-uppercase" style="letter-spacing: 7px; color: #D4AF37; font-size: 1.2rem;">Kafer</h1>
                    <h1 class="display-4 fw-bold mb-1 text-xdeep">IXEA <span class="text-xaccent">EROS</span></h1>
                    <p class="lead opacity-50 mb-0 fw-light" style="letter-spacing: 1px;">Enterprise Resource Operative System</p>
                    
                    <div class="d-flex gap-2 mt-3 justify-content-center justify-content-md-start">
                        <span class="badge rounded-pill bg-xdeep bg-opacity-10 text-light border border-xdeep border-opacity-25 px-3 py-2 fw-bold">v3.0.4</span>
                        <span class="badge rounded-pill bg-xaccent text-dark px-3 py-2 fw-bold shadow-sm">ML Enabled</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section id="differentiators" class="pt-5 d-flex align-items-start overflow-hidden">
        <div class="container animate__animated animate__fadeIn" style="max-width: 900px;">
            <div class="text-center py-3">
                <h2 class="display-5 fw-bold text-xdeep">Más que una interfaz</h2>
                <p class="lead opacity-50 mx-auto" style="max-width: 600px;">
                    Un ecosistema operativo
                </p>
            </div>
            
            <div id="carouselIxea" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#carouselIxea" data-bs-slide-to="0" class="bg-xaccent active"></button>
                    <button type="button" data-bs-target="#carouselIxea" data-bs-slide-to="1" class="bg-xaccent"></button>
                    <button type="button" data-bs-target="#carouselIxea" data-bs-slide-to="2" class="bg-xaccent"></button>
                </div>

                <div class="carousel-inner">
                    
                    <div class="carousel-item active" data-bs-interval="8000">
                        <div class="container py-2">
                            <div class="row align-items-center g-5">
                                <div class="col-lg-6 text-center text-lg-start">
                                    <h2 class="display-5 fw-bold mb-4">
                                        Navegación <span class="text-xaccent">Multitarea</span>
                                    </h2>
                                    
                                    <p class="lead mb-3 mx-auto opacity-75" style="max-width: 800px;">
                                        El <strong>Dock Inteligente</strong> de EROS es el núcleo de su flujo de trabajo. Alterne entre módulos críticos, invoque herramientas de IA y gestione sus procesos activos sin perder el contexto de su operación, eliminando la fricción del software tradicional.
                                    </p>
                                </div>
                                <div class="col-md-12 col-lg-6 text-center">
                                    <div class="pt-3 px-3 bg-light rounded-5 border border-light shadow-lg d-inline-block">
                                        <video autoplay loop muted playsinline preload="auto" class="rounded-3" style="max-width: 100%; height: auto;">
                                            <source src="/assets/videos/eros-dock.mp4?v=1.1" type="video/mp4">
                                        </video>
                                        <span class="badge bg-white text-primary px-3 py-0 text-uppercase fw-bold" style="letter-spacing: 2px;">Interfaz de Alto Rendimiento</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="carousel-item" data-bs-interval="8000">
                        <div class="container py-2">
                            <div class="row align-items-center g-5">
                                <div class="col-lg-6 text-center text-lg-start">
                                    <h2 class="display-5 fw-bold mb-4">Arquitectura de <span class="text-xaccent">Stages</span></h2>
                                    <p class="lead mb-4 opacity-75">
                                        <strong>Ejecución dinámica</strong> con capacidad multitarea nativa. EROS gestiona escenarios que permiten saltar entre procesos con la fluidez de un sistema nativo.
                                    </p>
                                </div>
                                <div class="col-md-12 col-lg-6 text-center">
                                    <div class="pt-3 px-3 bg-light rounded-5 border border-light shadow-lg d-inline-block">
                                        <video autoplay loop muted playsinline preload="auto" class="rounded-3" style="max-width: 100%; height: auto;">
                                            <source src="/assets/videos/eros-stages.mp4?v=1.1" type="video/mp4">
                                        </video>
                                        <span class="badge bg-white text-primary px-3 py-0 text-uppercase fw-bold" style="letter-spacing: 2px;">Evolución Tecnológica</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <div class="carousel-item" data-bs-interval="8000">
                        <div class="container py-2">
                            <div class="row align-items-center g-5">
                                <div class="col-lg-6 text-center text-lg-start">
                                    <h2 class="display-5 fw-bold mb-4">Widgets <span class="text-xaccent">On-Demand</span></h2>
                                    <p class="lead mb-4 opacity-75">
                                        Consultas analíticas y herramientas de gestión inmediatas para no interrumpir ninguna operación. EROS despliega <strong>Micro-servicios On-Demand</strong> siempre disponibles sobre sus escenarios. 
                                    </p>
                                </div>
                                <div class="col-md-12 col-lg-6 text-center">
                                    <div class="pt-3 px-3 bg-light rounded-5 border border-light shadow-lg d-inline-block">
                                        <video autoplay loop muted playsinline preload="auto" class="rounded-3" style="max-width: 100%; height: auto;">
                                            <source src="/assets/videos/eros-widgets.mp4?v=1.1" type="video/mp4">
                                        </video>
                                        <span class="badge bg-white text-primary px-3 py-0 text-uppercase fw-bold" style="letter-spacing: 2px;">Productividad sin Interrupciones</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </section>
    
    
    <section id="cores" class="py-5 overflow-hidden">
        <div class="container" style="max-width: 1100px;">
            <div class="text-center pt-3">
                <h2 class="display-5 fw-bold text-xdeep">Juntos son mejores</h2>
                <p class="lead opacity-50 mx-auto" style="max-width: 600px;">
                    La ingeniería detrás de la eficiencia
                </p>
            </div>
            
            <div class="row flex-nowrap overflow-auto pb-4 g-4 snap-x-mandatory" style="scrollbar-width: none; -ms-overflow-style: none;">
                
                <div class="col-11 col-md-4 snap-align-start">
                    <div class="card h-100 border-0 rounded-5 bg-white shadow-sm p-4 border-bottom border-4 border-xdeep">
                        <div class="mb-4 d-flex align-items-center gap-3">
                            <div class="p-3 bg-xdeep bg-opacity-10 rounded-4">
                                <i class="bi bi-database-fill text-light fs-3"></i>
                            </div>
                            <h5 class="fw-bold text-uppercase small mb-0" style="letter-spacing: 2px;">Axiom</h5>
                        </div>
                        <h3 class="fw-bold mb-3">Soberanía de Datos</h3>
                        <p class="opacity-75 lh-lg mb-0">
                            <strong>Axiom</strong> redefine la lógica operativa mediante la <strong>atomización de procesos</strong>. Centraliza el control y garantiza una trazabilidad absoluta, transformando la complejidad en rentabilidad simplificada.
                        </p>
                    </div>
                </div>
    
                <div class="col-11 col-md-4 snap-align-start">
                    <div class="card h-100 border-0 rounded-5 bg-white shadow-sm p-4 border-bottom border-4 border-xaccent">
                        <div class="mb-4 d-flex align-items-center gap-3">
                            <div class="p-3 bg-xaccent bg-opacity-10 rounded-4">
                                <i class="bi bi-cpu-fill text-xdeep fs-3"></i>
                            </div>
                            <h5 class="fw-bold text-uppercase small mb-0" style="letter-spacing: 2px;">Iteris</h5>
                        </div>
                        <h3 class="fw-bold mb-3">Núcleo Predictivo</h3>
                        <p class="opacity-75 lh-lg mb-0">
                            <strong>Iteris</strong> transforma el historial de procesos en <strong>proyecciones de mercado</strong>. Ajusta niveles de stock automáticamente basándose en modelos de aprendizaje estadístico real.
                        </p>
                    </div>
                </div>
    
                <div class="col-11 col-md-4 snap-align-start">
                    <div class="card h-100 border-0 rounded-5 bg-white shadow-sm p-4 border-bottom border-4 border-info">
                        <div class="mb-4 d-flex align-items-center gap-3">
                            <div class="p-3 bg-info bg-opacity-10 rounded-4">
                                <i class="bi bi-layers-half text-info fs-3"></i>
                            </div>
                            <h5 class="fw-bold text-uppercase small mb-0" style="letter-spacing: 2px;">Stages</h5>
                        </div>
                        <h3 class="fw-bold mb-3">Entorno Consciente</h3>
                        <p class="opacity-75 lh-lg mb-0">
                            La arquitectura de <strong>Stages</strong> permite un flujo de trabajo ininterrumpido donde las aplicaciones coexisten y transicionan sin latencia, preservando el estado crítico de su operación en cada salto.
                        </p>
                    </div>
                </div>
    
            </div>
        </div>
    </section>
    
    
    <section class="pt-5 d-flex align-items-center">
        <div class="container pt-5 animate__animated animate__fadeIn" style="max-width: 900px;">
            <div class="bg-white bg-opacity-5 rounded-5 p-5 border border-white border-opacity-10 shadow-lg backdrop-blur">
                <div class="row g-4">
                    <div class="col-6 col-md-3 text-center border-end border-white border-opacity-10">
                        <div class="h3 fw-bold mb-0">Web-SaaS</div>
                        <div class="small opacity-50 text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Plataforma</div>
                    </div>
                    <div class="col-6 col-md-3 text-center border-md-end border-white border-opacity-10">
                        <div class="h3 fw-bold mb-0">SQL/JS</div>
                        <div class="small opacity-50 text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Stack Core</div>
                    </div>
                    <div class="col-6 col-md-3 text-center border-end border-white border-opacity-10">
                        <div class="h3 fw-bold mb-0">FaceID</div>
                        <div class="small opacity-50 text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Seguridad</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="h3 fw-bold mb-0">256-bit</div>
                        <div class="small opacity-50 text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Encripción</div>
                    </div>
                </div>
            </div>
        
            <div class="my-3 py-3 text-center border-top border-white border-opacity-10">
                <p class="opacity-40 mb-0 fw-light">
                    Diseñado y desarrollado por la Arquitectura de Soluciones IXEA.
                </p>
            </div>
        </div>
    </section>
    
    <?php include $root_path . '/includes/prefooter.php'; ?>
    <?php include $root_path . '/includes/footer.php'; ?>
    <?php include $root_path . '/includes/scripts.php'; ?>
</body>
</html>