<?php
/** modules/admin/catalogs.php **/
?>
<div class="container-fluid h-100 py-2 px-4 overflow-auto">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden backdrop-blur bg-white bg-opacity-75">
        <div class="card-header bg-transparent border-bottom pt-1 px-4">
            <ul class="nav nav-pills card-header-pills" id="catalogTabs" role="tablist">
                <li class="nav-item"><button class="nav-link link-grey active" data-bs-toggle="tab" data-bs-target="#tab-categories" onclick="CatalogsApp.loadList('categories')">Categorías</button></li>
                <li class="nav-item"><button class="nav-link link-grey" data-bs-toggle="tab" data-bs-target="#tab-brands" onclick="CatalogsApp.loadList('brands')">Marcas</button></li>
                <li class="nav-item"><button class="nav-link link-grey" data-bs-toggle="tab" data-bs-target="#tab-units" onclick="CatalogsApp.loadList('units')">Unidades</button></li>
                <li class="nav-item ms-auto">
                    <button class="nav-link link-grey fw-bold border border-secondary border-opacity-25" data-bs-toggle="tab" data-bs-target="#tab-import" onclick="CatalogsApp.loadTypes()">
                        <i class="bi bi-file-earmark-excel me-1"></i> Importar CSV
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body tab-content px-3 py-2">
            <div class="tab-pane fade show active" id="tab-categories">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-secondary btn-sm rounded-pill px-3" onclick="CatalogsApp.openManualModal('categories')">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Categoría
                    </button>
                </div>
                <div class="p-2" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                    <div id="list-categories" class="row g-3 mx-0"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-brands">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-secondary btn-sm rounded-pill px-3" onclick="CatalogsApp.openManualModal('brands')">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Marca
                    </button>
                </div>
                <div class="p-2" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                    <div id="list-brands" class="row g-3 mx-0"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-units">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-secondary btn-sm rounded-pill px-3" onclick="CatalogsApp.openManualModal('units')">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Unidad
                    </button>
                </div>
                <div class="p-2" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                    <div id="list-units" class="row g-3 mx-0"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-import">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="bg-light p-4 rounded-4 border border-dashed text-center mb-3">
                            <i class="bi bi-cloud-arrow-up fs-1 text-success opacity-50"></i>
                            <h6 class="fw-bold mt-2">Carga Masiva de Datos</h6>
                        </div>
                        <form id="form-import-catalogs" class="bg-white p-4 rounded-4 border shadow-sm">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tipo de Importación</label>
                                <select class="form-select" name="import_type" id="import_type" onchange="CatalogsApp.updateImportInfo()">
                                    </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">Seleccionar CSV</label>
                                <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold rounded-pill py-2">
                                <i class="bi bi-play-fill me-1"></i> Iniciar Procesamiento
                            </button>
                        </form>
                    </div>
                    <div class="col-md-7">
                        <div class="card border-0 bg-light rounded-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold m-0">Estructura Requerida</h6>
                                    <button class="btn btn-sm btn-outline-dark rounded-pill" onclick="CatalogsApp.downloadTemplate()">
                                        <i class="bi bi-download me-1"></i> Plantilla
                                    </button>
                                </div>
                                <div id="import-instructions" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="import-results" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>

<template id="tpl-modal-catalog">
    <div class="modal-header bg-dark text-white border-0">
        <h5 class="modal-title fw-bold" id="cat-modal-title">Catálogo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-4">
        <form id="form-manual-catalog">
            <input type="hidden" name="action" value="save_manual">
            <input type="hidden" name="type" id="cat-type">
            <div id="cat-fields"></div>
            <button type="submit" class="btn btn-secondary w-100 rounded-pill mt-4 fw-bold">Guardar Registro</button>
        </form>
    </div>
</template>