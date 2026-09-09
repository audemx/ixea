<?php
/** modules/inventory/stock.php **/
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/security.php';

$finance_permission = $is_super || in_array('ver_finanzas', $user_permissions);
$appColor = 'sfpurple';
?>
<div class="container-fluid h-100 d-flex flex-column py-3 px-4">
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm border-start border-<?=$appColor?>  border-4 cursor-pointer filter-card bg-dark text-white shadow-lg" 
                 onclick="StockApp.setFilter('all', this)" id="card-all">
                <div class="card-body py-2">
                    <h6 class="text-<?=$appColor?> small text-uppercase fw-bold opacity-75 mb-1">Productos</h6>
                    <div class="h4 mb-0 fw-bold" id="count_productos"><span class="spinner-border spinner-border-sm"></span></div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm border-start border-sfred border-4 cursor-pointer filter-card" 
                 onclick="StockApp.setFilter('critical', this)">
                <div class="card-body py-2">
                    <h6 class="text-sfred small text-uppercase fw-bold mb-1">Crítico</h6>
                    <div class="h4 mb-0 fw-bold" id="count_criticos">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm border-start border-sforange border-4 cursor-pointer filter-card" 
                 onclick="StockApp.setFilter('over', this)">
                <div class="card-body py-2">
                    <h6 class="text-sforange  small text-uppercase fw-bold mb-1">Sobrado</h6>
                    <div class="h4 mb-0 fw-bold" id="count_over">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm border-start border-sfyellow border-4 cursor-pointer filter-card" 
                 onclick="StockApp.setFilter('stuck', this)">
                <div class="card-body py-2">
                    <h6 class="text-sfyellow small text-uppercase fw-bold mb-1">Estancado</h6>
                    <div class="h4 mb-0 fw-bold" id="count_stuck">0</div>
                </div>
            </div>
        </div>
        <?php if ($finance_permission): ?>
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-sfgreen border-4 bg-white bg-opacity-50">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-sfgreen small text-uppercase fw-bold mb-1">Valor Inventario</h6>
                        <div class="h4 mb-0 fw-bold text-dark" id="count_valor_total">$0.00</div>
                    </div>
                    <i class="bi bi-cash-stack fs-3 text-sfgreen opacity-50"></i>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="col-md-4 d-none d-md-block"></div>
        <?php endif; ?>
    </div>

    <div class="flex-grow-1 bg-white bg-opacity-75 backdrop-blur rounded-4 shadow-sm border overflow-hidden d-flex flex-column">
        <div class="p-4 overflow-auto">
            <table id="tableProducts" class="table table-hover align-middle w-100">
                <thead>
                    <tr class="small text-uppercase text-muted border-bottom">
                        <th>SKU / ID</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="small">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<template id="tpl-stock-analysis">
    <div class="modal-header d-flex justify-content-center align-items-center p-2 m-3 bg-light rounded-4">
        <h5 class="fw-bold text-<?=$appColor?> px-3 mt-3">
            <i class="bi bi-graph-up-arrow me-2"></i><span id="stock-analysis-title"></span>
        </h5>
    </div>
    
    <div class="px-3 pb-3">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4 mt-3">
                    <div class="card-body">
                        <div style="height: 300px; position: relative;">
                            <canvas id="stock-analysis-plot"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-<?=$appColor?>  border-2 shadow-sm rounded-4 my-2">
                    <div class="card-body text-center py-2">
                        <div class="my-1">
                            <div class="display-6 fw-bold" id="stock-analysis-stock">0</div>
                            <div class="text-muted small" id="stock-analysis-unit">unidades</div>
                        </div>
                        <hr>
                        <div class="row g-0">
                            <div class="col-6">
                                <small class="d-block text-muted">Mínimo</small>
                                <span class="fw-bold" id="stock-analysis-min">0</span>
                            </div>
                            <div class="col-6">
                                <small class="d-block text-muted">Máximo</small>
                                <span class="fw-bold" id="stock-analysis-max">0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-sfcyan border-2 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-sfcyan text-white text-center border-0 py-1">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-robot me-2"></i>Sugerencia ML</h6>
                    </div>
                    <div class="card-body">
                         <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small ps-3">Mínimo</label>
                                <input type="number" id="stock-analysis-minSuggested" class="form-control text-center">
                            </div>
                            <div class="col-6">
                                <label class="form-label small ps-3">Máximo</label>
                                <input type="number" id="stock-analysis-maxSuggested" class="form-control text-center">
                            </div>
                        </div>
                        <button id="stock-analysis-btnApply" class="btn btn-sfcyan text-white w-100 rounded-3 py-1">
                            <i class="bi bi-check-lg me-1"></i> Aplicar Ajustes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="button" id="stock-analysis-btnPrev" class="btn btn-dark btn-nav btn-nav-prev">
        <i class="bi bi-chevron-left"></i>
    </button>
    
    <button type="button" id="stock-analysis-btnNext" class="btn btn-dark btn-nav btn-nav-next">
        <i class="bi bi-chevron-right"></i>
    </button>
</template>

<template id="tpl-stock-edit">
    <div class="modal-header d-flex justify-content-center align-items-center p-2 m-3 bg-light rounded-4">
        <h5 class="fw-bold text-<?=$appColor?> px-3 mt-3">
            <i class="bi bi-pencil-square me-2"></i><span id="stock-edit-title"></span>
        </h5>
    </div>

    <div class="px-4 pb-4">
        <ul class="nav nav-pills nav-fill mb-4 bg-white p-1 rounded-3 shadow-sm border" id="stock-edit-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link link-<?=$appColor?> active small fw-bold" data-bs-toggle="tab" data-bs-target="#tab-stock-general" type="button">
                    <i class="bi bi-info-circle me-1"></i> General
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link link-<?=$appColor?> small fw-bold" data-bs-toggle="tab" data-bs-target="#tab-stock-levels" type="button">
                    <i class="bi bi-layers me-1"></i> Stock
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link link-<?=$appColor?> small fw-bold" data-bs-toggle="tab" data-bs-target="#tab-stock-sales" type="button">
                    <i class="bi bi-tags me-1"></i> Ventas
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link link-<?=$appColor?> small fw-bold" data-bs-toggle="tab" data-bs-target="#tab-stock-purchases" type="button">
                    <i class="bi bi-cart me-1"></i> Compras
                </button>
            </li>
        </ul>

        <div class="tab-content bg-white p-4 rounded-4 border shadow-sm" id="stock-edit-content">
            
            <div class="tab-pane fade show active" id="tab-stock-general" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">SKU / ID</label>
                        <input type="text" id="stock-edit-sku" class="form-control bg-light" autocomplete="off" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nombre del Producto</label>
                        <input type="text" id="stock-edit-name" class="form-control" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Unidad</label>
                        <select id="stock-edit-unit" class="form-select"></select>
                    </div>
                    <div class="col-md-1 d-flex flex-column align-items-center text-center form-check form-switch">
                        <label class="form-check-label me-4 fw-bold" for="stock-edit-tax">SAE</label>
                        <input class="form-check-input mt-3" type="checkbox" id="stock-edit-tax" role="switch">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Categoría</label>
                        <select id="stock-edit-category" class="form-select"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Marca</label>
                        <select id="stock-edit-brand" class="form-select"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Estado</label>
                        <select id="stock-edit-status" class="form-select">
                            <option value="active">Activo</option>
                            <option value="discontinued">Descontinuado</option>
                            <option value="suspended">Suspendido</option>
                            <option value="archived">Archivado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-stock-levels" role="tabpanel">
                <div class="row g-4 align-items-center">
                    <div class="col-md-4 text-center">
                        <div class="text-muted small">Stock Actual</div>
                        <div class="display-6 fw-bold text-dark" id="stock-edit-current">0</div>
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="form-label small fw-bold">Mínimo Requerido</label>
                        <input type="number" id="stock-edit-min" class="form-control form-control-lg border-sfyellow text-center fw-bold" autocomplete="off">
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="form-label small fw-bold">Máximo Permitido</label>
                        <input type="number" id="stock-edit-max" class="form-control form-control-lg border-sfgreen text-center fw-bold" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-stock-sales" role="tabpanel">
                <div class="d-flex justify-content-between mb-3">
                    <button class="btn btn-sm btn-dark rounded-pill px-3" onclick="StockApp.addUnitRow('sale')">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Unidad
                    </button>
                </div>
                <div id="stock-edit-sales-container" class="vstack gap-2">
                    </div>
            </div>

            <div class="tab-pane fade" id="tab-stock-purchases" role="tabpanel">
                <div class="d-flex justify-content-between mb-3">
                    <button class="btn btn-sm btn-dark rounded-pill px-3" onclick="StockApp.addUnitRow('purchase')">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Unidad
                    </button>
                </div>
                <div id="stock-edit-purchases-container" class="vstack gap-2">
                    </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <button id="stock-edit-btnSave" class="btn btn-<?=$appColor?> w-100 py-3 rounded-4 shadow fw-bold text-white">
                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Guardar
            </button>
        </div>
    </div>

    <button type="button" id="stock-edit-btnPrev" class="btn btn-dark btn-nav btn-nav-prev">
        <i class="bi bi-chevron-left"></i>
    </button>
    <button type="button" id="stock-edit-btnNext" class="btn btn-dark btn-nav btn-nav-next">
        <i class="bi bi-chevron-right"></i>
    </button>
</template>

<template id="tpl-stock-audit">
    <div class="modal-header p-3 pe-5 bg-light rounded-4-top">
        <h5 class="fw-bold text-<?=$appColor?> mb-0">
            <i class="bi bi-journal-check me-2"></i> <span id="audit-product-name"></span>
        </h5>
        <button class="btn btn-sm btn-<?=$appColor?> text-white rounded-pill px-3 ms-auto" id="stock-audit-btnAdjusment">
            <i class="bi bi-plus-slash-minus me-1"></i> Ajustar Stock
        </button>
    </div>
    
    <div class="modal-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small uppercase">
                    <tr>
                        <th class="ps-4">Fecha</th>
                        <th>Detalle / Notas</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end pe-4">Balance</th>
                    </tr>
                </thead>
                <tbody id="audit-table-body">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>
        <div id="audit-load-more" class="text-center p-3 d-none">
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-4">Cargar más movimientos...</button>
        </div>
    </div>
</template>