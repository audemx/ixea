<?php
/** modules/sales/pos.php **/
?>
<div class="container-fluid h-100 d-flex flex-column py-3 px-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-3 text-start">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="dispatch-search" class="form-control border-start-0 ps-0" 
                       placeholder="Busca por folio..." onkeyup="DispatchApp.filterList()">
            </div>
        </div>
    
        <div class="col-md-6 d-flex justify-content-center px-5">
            <div class="btn-group btn-group-sm w-100" role="group">
                <input type="radio" class="btn-check" name="dispatch-view" id="sales-pending" onchange="DispatchApp.loadOrders()" checked>
                <label class="btn btn-outline-primary" for="sales-pending">Pendientes</label>
                
                <input type="radio" class="btn-check" name="dispatch-view" id="sales-delivered" onchange="DispatchApp.loadOrders()">
                <label class="btn btn-outline-primary" for="sales-delivered">Entregados Hoy</label>
            </div>
        </div>
    
        <div class="col-md-3 text-end">
            <div class="d-inline-flex align-items-center bg-primary text-white rounded-pill px-3 py-1 shadow-sm">
                <span class="me-2" id="dispatch-counter">0</span>
            </div>
        </div>
    </div>

    <div class="flex-grow-1 overflow-auto p-4 bg-light rounded">
        <div class="row g-3" id="dispatch-list-container"></div>
    </div>
</div>

<template id="tpl-dispatch-order">
    <div class="modal-header bg-light">
        <h5 class="modal-title">Pedido: <span id="m-dispatch-folio" class="fw-bold"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <p class="text-muted small mb-3 to-hide">Valida que todos los productos estén listos para entrega:</p>
        <ul class="list-group mb-3" id="m-dispatch-items">
            </ul>
        <div class="alert alert-info d-flex align-items-center mb-0 to-hide">
            <i class="bi bi-info-circle me-2"></i>
            <small>Debes marcar todos los productos para confirmar la salida.</small>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-success w-50" id="btn-confirm-dispatch" disabled>
            <i class="bi bi-check2-all me-1"></i> Confirmar Entrega
        </button>
    </div>
</template>