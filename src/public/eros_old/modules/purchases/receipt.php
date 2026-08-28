<?php
/** modules/purchase/receipt.php **/
?>
<div class="container-fluid h-100 d-flex flex-column py-3 px-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-4 d-flex justify-content-start">
            <div class="btn-group btn-group-sm w-80" role="group">
                <input type="radio" class="btn-check" name="receipt-view" id="purchases-pending" onchange="ReceiptApp.loadOrders()" checked>
                <label class="btn btn-outline-sforange" for="purchases-pending">Pendientes</label>
                
                <input type="radio" class="btn-check" name="receipt-view" id="purchases-received" onchange="ReceiptApp.loadOrders()">
                <label class="btn btn-outline-sforange" for="purchases-received">Recibidos</label>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-box-seam text-sforange"></i></span>
                <input type="text" id="receipt-search" class="form-control border-start-0 ps-0 text-center"
                    placeholder="Buscar por folio..." onkeyup="ReceiptApp.filterList()">
            </div>
        </div>

        <div class="col-md-4 text-end">
            <div class="d-inline-flex align-items-center bg-sforange text-white rounded-pill px-3 py-1 shadow-sm">
                <span class="fw-bold" id="receipt-counter">0</span>
            </div>
        </div>
    </div>

    <div class="flex-grow-1 overflow-auto p-4 bg-light rounded-4 border">
        <div class="row g-3" id="receipt-list-container">
            </div>
    </div>
</div>

<template id="tpl-receipt-order">
    <div class="modal-header bg-light">
        <h5 class="modal-title">Compra: <span id="m-receipt-folio" class="fw-bold"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <ul class="list-group list-group-flush border rounded-3 mb-3" id="m-receipt-items">
            </ul>
            
        <div class="alert alert-info d-flex align-items-center mb-0 to-hide">
            <i class="bi bi-info-circle me-2"></i>
            <small>Verifica físicamente cada producto recibido.</small>
        </div>
    </div>
    <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-success w-50" id="btn-confirm-receipt" disabled>
            <i class="bi bi-check2-all me-1"></i> Confirmar recepción
        </button>
    </div>
</template>