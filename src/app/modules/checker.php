<?php
/** /modules/rrhh/checker.php **/
$appColor = 'sfmagenta';
?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 mt-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 py-4 text-center">
                    <h1 class="fw-bold mb-0 text-<?= $appColor ?>" id="liveClock">00:00:00</h1>
                    <p class="text-muted fw-bold text-uppercase mb-0" id="liveDate">Cargando fecha...</p>
                </div>
                
                <div class="card-body px-4 py-5 text-center">
                    <div id="checkerCameraBox" class="d-none mb-4 mx-auto rounded-4 overflow-hidden shadow-sm" 
                         style="width:320px; height:240px; background:#000; position: relative; border: 4px solid #f8f9fa;">
                        
                        <video id="checkerVideo" autoplay muted playsinline 
                               style="width:100%; height:100%; object-fit:cover; transform: scaleX(-1);"></video>
                        
                        <canvas id="checkerCanvas" width="320" height="240" 
                                style="position: absolute; top:0; left:0; z-index: 100; pointer-events: none;"></canvas>
                    </div>

                    <div id="idleState">
                        <div class="mb-4">
                            <i class="bi bi-person-badge" style="font-size: 4rem; color: #dee2e6;"></i>
                        </div>
                        <h4 class="fw-bold">Registro de Asistencia</h4>
                        <p class="text-muted">Posiciónate frente a la pantalla y presiona el botón para iniciar.</p>
                    </div>

                    <div class="d-grid gap-2 col-8 mx-auto">
                        <button type="button" id="btnInitChecker" class="btn btn-lg text-white rounded-pill shadow-sm bg-<?= $appColor ?>" onclick="CheckerApp.startAuth()">
                            <i class="bi bi-scan me-2"></i>Iniciar Reconocimiento
                        </button>
                    </div>

                    <div id="checkerMsg" class="mt-4 h5 fw-bold"></div>
                </div>
                
                <div class="card-footer bg-light border-0 py-3 text-center">
                    <span class="badge rounded-pill bg-white text-dark shadow-sm px-3 py-2">
                        <i class="bi bi-shield-check text-success me-2"></i>Validación Biométrica Activa
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>