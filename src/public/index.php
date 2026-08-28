<?php
/**
 * Página principal
 * /index.php
 */
$root_path = $_SERVER['DOCUMENT_ROOT'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include $root_path . '/includes/header.php'; ?>
    <title>IXEA - Inteligencia para Construir Valor</title>
</head>
<body>
    <?php include $root_path . '/includes/brandbar.php'; ?>
    <?php include $root_path . '/includes/navbar.php'; ?>
    <?php include $root_path . '/includes/navmov.php'; ?>

    <section id="hero" class="bg-white">
        <div class="container py-5 my-5 my-lg-0">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold text-dark mb-4" style="line-height: 1.1;">
                        Certeza operativa impulsada por <span class="text-primary">Inteligencia Predictiva.</span>
                    </h1>
                    <p class="lead text-muted mb-5">
                        Gestione su inventario, automatice sus compras y prediga la demanda con la inteligencia de <strong>Axiom</strong> e <strong>Iteris</strong>.
                    </p>
                    <div class="d-flex align-items-center gap-3">
                        <a href="#demo" class="btn btn-primary px-lg-4 py-lg-3 py-2 fw-bold">Comenzar ahora</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="p-4 bg-light border rounded-3 shadow-sm">
                        <div class="d-flex border-bottom pb-2 mb-3">
                            <div class="bg-danger rounded-circle me-1" style="width:12px; height:12px;"></div>
                            <div class="bg-warning rounded-circle me-1" style="width:12px; height:12px;"></div>
                            <div class="bg-success rounded-circle" style="width:12px; height:12px;"></div>
                        </div>
                        <div class="placeholder-glow">
                            <span class="placeholder col-12 mb-2"></span>
                            <span class="placeholder col-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section id="differentiators" class="bg-xdeep text-white overflow-hidden">
        <div id="carouselIxea" class="carousel slide py-5 my-5 my-lg-0" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselIxea" data-bs-slide-to="0" class="active bg-white"></button>
                <button type="button" data-bs-target="#carouselIxea" data-bs-slide-to="1" class="bg-white"></button>
                <button type="button" data-bs-target="#carouselIxea" data-bs-slide-to="2" class="bg-white"></button>
                <button type="button" data-bs-target="#carouselIxea" data-bs-slide-to="3" class="bg-white"></button>
            </div>
    
            <div class="carousel-inner">
                <div class="carousel-item active" data-bs-interval="6000">
                    <div class="container py-3 text-white text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <span class="badge bg-white text-primary mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 2px;">Evolución Tecnológica</span>
                                
                                <h2 class="display-5 fw-bold mb-4">
                                    Inteligencia que <span class="text-xaccent">trasciende</span> el registro
                                </h2>
                                
                                <p class="lead mb-3 mx-auto opacity-75" style="max-width: 800px;">
                                    En IXEA, transformamos los datos estáticos en un motor de crecimiento activo. 
                                    Nuestra tecnología libera el potencial de su capital, convirtiendo el inventario en 
                                    <strong>flujo de efectivo estratégico</strong> y constante.
                                </p>
                                
                                <div class="p-4 mx-auto bg-white bg-opacity-10 border border-white border-opacity-25 rounded-5 shadow-lg backdrop-blur" style="max-width: 400px;">
                                    <div class="position-relative d-inline-block mb-3">
                                        <i class="bi bi-cpu-fill text-xaccent display-2"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            NUEVO
                                        </span>
                                    </div>
                                    <h4 class="fw-bold text-white mb-0">Ecosistema Predictivo</h4>
                                    <p class="small text-white-50 mt-2 mb-0">Generación proactiva basada en Machine Learning.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="carousel-item" data-bs-interval="6000">
                    <div class="container py-3 text-white text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <span class="badge bg-white text-primary mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 2px;">Cimiento Operativo</span>
                                
                                <h2 class="display-5 fw-bold mb-4">
                                    Soberanía y <span class="text-xaccent">Control Total</span>
                                </h2>
                                
                                <p class="lead mb-3 mx-auto opacity-75" style="max-width: 800px;">
                                    Axiom es la columna vertebral que elimina la incertidumbre. Centralice su flujo de trabajo con una 
                                    <strong>trazabilidad impecable</strong> y gestión multi-almacén en tiempo real, garantizando que cada 
                                    movimiento en su empresa sea una decisión basada en la verdad absoluta.
                                </p>
                                
                                <div class="p-4 mx-auto bg-white bg-opacity-10 border border-white border-opacity-25 rounded-5 shadow-lg backdrop-blur" style="max-width: 400px;">
                                    <div class="position-relative d-inline-block mb-3">
                                        <i class="bi bi-diagram-3 text-xaccent display-2"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-dark">
                                            CORE
                                        </span>
                                    </div>
                                    <h4 class="fw-bold text-white mb-0">Arquitectura de Control</h4>
                                    <p class="small text-white-50 mt-2 mb-0">Visibilidad 360° de cada SKU y proceso.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="carousel-item" data-bs-interval="6000">
                    <div class="container py-3 text-white text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <span class="badge bg-white text-primary mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 2px;">Ingeniería Predictiva</span>
                                
                                <h2 class="display-5 fw-bold mb-4">
                                    Abastecimiento de <span class="text-warning">Precisión Matemática</span>
                                </h2>
                                
                                <p class="lead mb-3 mx-auto opacity-75" style="max-width: 800px;">
                                    Iteris procesa su historial para anticipar la demanda futura con exactitud científica. 
                                    <strong>Automatice sus decisiones de compra</strong> y elimine la carga administrativa, permitiendo que su equipo se enfoque en lo que realmente genera valor.
                                </p>
                                
                                <div class="p-4 mx-auto bg-white bg-opacity-10 border border-white border-opacity-25 rounded-5 shadow-lg backdrop-blur" style="max-width: 400px;">
                                    <div class="position-relative d-inline-block mb-3">
                                        <i class="bi bi-magic text-xaccent display-2"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            PRO
                                        </span>
                                    </div>
                                    <h4 class="fw-bold text-white mb-0">Órdenes Automáticas</h4>
                                    <p class="small text-white-50 mt-2 mb-0">Menos gestión manual, más velocidad operativa.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="carousel-item" data-bs-interval="6000">
                    <div class="container py-3 text-white text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <span class="badge bg-white text-primary mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 2px;">Resultados Tangibles</span>
                                
                                <h2 class="display-5 fw-bold mb-4">
                                    Sincronización <span class="text-warning">Dinámica</span> Total
                                </h2>
                                
                                <p class="lead mb-3 mx-auto opacity-75" style="max-width: 800px;">
                                    Logre una operación que se adapta sola. Con IXEA, sus niveles de seguridad y órdenes de compra 
                                    <strong>fluctúan automáticamente</strong> con la temporalidad del mercado, garantizando 
                                    disponibilidad absoluta con la mínima inversión posible.
                                </p>
                                
                                <div class="p-4 mx-auto bg-white bg-opacity-10 border border-white border-opacity-25 rounded-5 shadow-lg backdrop-blur" style="max-width: 400px;">
                                    <div class="position-relative d-inline-block mb-3">
                                        <i class="bi bi-graph-up-arrow text-xaccent display-2"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            LIVE
                                        </span>
                                    </div>
                                    <h4 class="fw-bold text-white mb-0">Stock Autoadaptable</h4>
                                    <p class="small text-white-50 mt-2 mb-3">Máxima rotación, cero desperdicio.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section id="products" class="border-top bg-light overflow-hidden">
        <div id="carouselProducts" class="carousel slide py-5 my-5 my-lg-0" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselProducts" data-bs-slide-to="0" class="active bg-primary"></button>
                <button type="button" data-bs-target="#carouselProducts" data-bs-slide-to="1" class="bg-primary"></button>
            </div>
            
            <div class="carousel-inner">
                <div class="carousel-item active" data-bs-interval="6000">
                    <div class="container py-5 text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <span class="badge bg-primary mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 2px;">Core Operativo</span>
                                <h2 class="display-5 fw-bold text-dark mb-4">Axiom: La Base de la <span class="text-primary">Soberanía</span></h2>
                                <p class="lead mb-4 mx-auto text-muted" style="max-width: 800px;">
                                    El desorden operativo es una fuga silenciosa de capital. Axiom centraliza su gestión, garantizando una trazabilidad total y un control multi-almacén sin fisuras.
                                </p>
                                
                                <div class="p-4 mx-auto bg-white border rounded-5 shadow-sm" style="max-width: 450px;">
                                    <div class="mb-3 text-primary">
                                        <i class="bi bi-layers-half display-3"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2">Control Centralizado</h4>
                                    <p class="small text-muted mb-3">La infraestructura necesaria para operar con cero errores.</p>
                                    <a href="#" class="btn btn-outline-primary fw-bold px-4">Explorar Axiom <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="carousel-item" data-bs-interval="6000">
                    <div class="container py-5 text-center">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <span class="badge bg-primary mb-3 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 2px;">Inteligencia Predictiva</span>
                                <h2 class="display-5 fw-bold text-dark mb-4">Iteris: El Futuro <span class="text-primary">Automatizado</span></h2>
                                <p class="lead mb-4 mx-auto text-muted" style="max-width: 800px;">
                                    Deje de adivinar qué comprar. Nuestra inteligencia predictiva ajusta sus niveles de stock automáticamente basándose en la demanda real y proyecciones de mercado.
                                </p>
                                
                                <div class="p-4 mx-auto bg-white border rounded-5 shadow-sm" style="max-width: 450px;">
                                    <div class="mb-3 text-primary">
                                        <i class="bi bi-cpu display-3"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2">Algoritmos de Valor</h4>
                                    <p class="small text-muted mb-3">Máximos y mínimos dinámicos que trabajan por usted.</p>
                                    <a href="#" class="btn btn-outline-primary fw-bold px-4">Conocer Iteris <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
    
    <section id="about" class="py-5 bg-dark text-white">
        <div class="container py-5 my-5 my-lg-0">
            <div class="row justify-content-center">
                <div class="col-md-10 text-center">
                    <h2 class="fw-bold mb-4">Construimos certeza para Mercados Impredecibles</h2>
                    <p class="fs-4 fw-light opacity-75">
                        "En <strong>IXEA</strong>, transformamos la <strong>Inteligencia</strong> en una herramienta tangible que <strong>Construye Valor</strong> para cada empresa que confía en nosotros."
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <section id="invitation" class="bg-white">
        <div class="container py-5 my-5 my-lg-0 text-center">
            <h2 class="fw-bold mb-3">¿Listo para elevar el valor de su empresa?</h2>
            <p class="text-muted mb-4 px-lg-5">
                Únase a las organizaciones que ya utilizan <strong>IXEA</strong> para optimizar sus operaciones.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#demo" class="btn btn-primary px-4 py-2 fw-bold">Solicitar Demo</a>
                <a href="#contacto" class="btn btn-outline-dark px-4 py-2">Contactar Ventas</a>
            </div>
        </div>
    </section>
    
    <?php include $root_path . '/includes/prefooter.php'; ?>
    <?php include $root_path . '/includes/footer.php'; ?>
    <?php include $root_path . '/includes/scripts.php'; ?>
</body>
</html>