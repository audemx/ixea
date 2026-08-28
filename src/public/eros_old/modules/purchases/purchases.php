<?php
/** modules/purchase/purchase.php **/
?>
<div class="container-fluid h-100 py-2 px-4">
    <div class="row h-100 g-4">
        
        <div class="col-md-8 d-flex flex-column h-100">
            <div class="search-container mb-4">
                <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden border-0">
                    <input type="text" id="purchase-product-search" class="form-control border-0 ps-5" placeholder="Buscar productos (F2 busqueda avanzada)">
                    <span class="input-group-text bg-white border-0">
                        <btn class="btn" onclick="PurchasesApp.openSearch()"><i class="bi bi-search"></i></btn>
                    </span>
                </div>
            </div>

            <div id="purchase-results" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 align-content-start overflow-auto pe-2" style="flex-grow: 1;">
                <div class="col-12 text-center text-secondary m-5 p-5">
                    <i class="bi bi-box-seam fs-1 opacity-25"></i>
                    <p>Escanea o busca productos para agregar a la orden de compra</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 h-100">
            <div class="card h-100 border-0 rounded-4 shadow-sm bg-white bg-opacity-75 backdrop-blur">
                <div class="card-header bg-transparent border-bottom pt-2 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-orange"><i class="bi bi-bag-check-fill pe-2"></i> Orden</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light" onclick="PurchasesApp.clearCart()"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                
                <div class="card-body overflow-auto px-4" id="purchase-items">
                    <div class="text-center text-secondary mt-5 pt-5">
                        <i class="bi bi-cart-plus fs-1 opacity-25"></i>
                        <p class="mt-2">No hay productos en la orden</p>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-top py-2 px-4">
                    <div id="supplier-info-display" class="mb-2 small text-muted d-none">
                        </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fs-4 fw-bold">Subtotal: </span>
                        <span id="purchase-total-val" class="fs-4 fw-bold text-orange">$0.00</span>
                    </div>
                    <button class="btn btn-sforange w-100 rounded-4 fw-bold shadow text-white" onclick="PurchasesApp.checkout()">
                        <i class="bi bi-check2-circle me-2"></i> Procesar Entrada (F12)
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<template id="tpl-adv-search-purchase">
    <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center w-100 gap-3">
            <div class="position-relative flex-grow-1">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="adv-search-input-purchase" 
                       class="form-control form-control-lg rounded-pill ps-5 border-2 border-primary-subtle" 
                       placeholder="Buscar por nombre, marca o categoría...">
            </div>
        </div>
    </div>
    
    <div class="modal-body px-4">
        <div class="table-responsive" style="max-height: 65vh; min-height: 300px;">
            <table class="table table-hover align-middle border-top">
                <thead class="sticky-top bg-white shadow-sm" style="z-index: 10;">
                    <tr class="small text-uppercase text-muted">
                        <th class="text-start ps-3">SKU</th>
                        <th class="text-start">Producto</th>
                        <th class="text-center">Unidad</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center pe-3">Costo</th>
                    </tr>
                </thead>
                <tbody id="adv-search-results-purchase">
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="bi bi-box-seam d-block h1 text-light"></i>
                            <span class="text-secondary">Escribe algo para encontrar productos</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<template id="tpl-purchase-suggestions">
    <div class="modal-header bg-light text-center">
        <h5 class="modal-title"><i class="bi bi-radar me-2 text-sforange"></i>Radar de Abastecimiento</h5>
    </div>
        
    <div class="modal-body bg-light">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-sfred border-4 cursor-pointer hover-elevate" onclick="PurchasesApp.filterRadar('critical')">
                    <div class="card-body">
                        <h6 class="text-sfred fw-bold small text-uppercase">Agotados</h6>
                        <div class="h3 mb-0 fw-bold" id="radar-total-critical">0</div>
                        <p class="text-muted small mb-0">Productos con urgencia</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-sfyellow border-4 cursor-pointer hover-elevate" onclick="PurchasesApp.filterRadar('low')">
                    <div class="card-body">
                        <h6 class="text-sfyellow fw-bold small text-uppercase">Bajo</h6>
                        <div class="h3 mb-0 fw-bold" id="radar-total-low">0</div>
                        <p class="text-muted small mb-0">Productos con stock bajo</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-sfcyan border-4 cursor-pointer" onclick="PurchasesApp.filterRadar('suppliers')">
                    <div class="card-body">
                        <h6 class="text-sfcyan fw-bold small text-uppercase">Proveedores</h6>
                        <div class="h3 mb-0 fw-bold" id="radar-total-suppliers">0</div>
                        <p class="text-muted small mb-0">Necesita atención</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm" id="radar-suppliers-container">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Seleccionar Proveedores para Orden</h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="radar-select-all" checked onchange="PurchasesApp.toggleRadarSelection(this.checked)">
                    <label class="form-check-label small fw-bold" for="radar-select-all">Marcar todos</label>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Proveedor</th>
                                <th class="text-center">Productos con Alerta</th>
                                <th class="text-center">Estado Crítico</th>
                                <th width="40" class="ps-3"></th>
                            </tr>
                        </thead>
                        <tbody id="radar-suppliers-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer bg-white border-top-0">
        <button type="button" class="btn btn-sforange px-4 fw-bold text-white" onclick="PurchasesApp.generateSuggestedOrder()">
            <i class="bi bi-magic me-2"></i>Generar Sugerencias de Compra
        </button>
    </div>
</template>

<template id="tpl-purchase-history">
    <div class="modal-header border-0 pb-2">
        <h5 class="fw-bold mb-0 text-orange">Historial de Órdenes de Compra</h5>
    </div>
    
    <div class="modal-body p-0">
        <div class="px-3 py-3 bg-light border-bottom border-top">
            <div class="row g-2">
                <div class="col-8">
                    <div class="input-group">
                        <input type="text" id="hist-purchase-search-folio" class="form-control border-start-0" placeholder="Buscar por folio o proveedor..." onchange="PurchasesApp.performPurchaseSearch()">
                        <button class="btn btn-sforange text-white" onclick="PurchasesApp.performPurchaseSearch()">
                            <i class="bi bi-search me-1"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="col-4">
                    <select id="hist-purchase-filter-status" class="form-select" onchange="PurchasesApp.performPurchaseSearch()">
                        <option value="">Estado de pago...</option>
                        <option value="pending">Pendiente</option>
                        <option value="paid">Pagado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="history-purchases-list" style="min-height: 300px; max-height: 500px; overflow-y: auto;">
            </div>
    </div>
</template>