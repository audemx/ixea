<?php
/** modules/till/till.php **/
?>
<div class="container-fluid h-100 py-3 px-4">
    <div class="row h-100 g-4">
        
        <div class="col-md-4 d-flex flex-column h-100">
            <div class="card border-0 rounded-4 shadow-sm mb-4 backdrop-blur">
                <div class="card-body p-4 text-center">
                    <div id="caja-status-icon" class="mb-2">
                        <i class="bi bi-door-closed fs-1 text-secondary"></i>
                    </div>
                    <h4 class="fw-bold mb-1" id="caja-status-text">Caja Cerrada</h4>
                    
                    <div id="caja-open-info" class="d-none">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2 bg-light p-2 rounded-3">
                            <span class="text-secondary small">En Caja:</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark" id="caja-current-cash">******</span>
                                <button class="btn btn-sm btn-link p-0 text-muted" onclick="TillApp.toggleCashVisibility()">
                                    <i class="bi bi-eye" id="eye-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="caja-closed-info">
                        <button class="btn btn-sfblue w-100 rounded-3 shadow" onclick="TillApp.openShiftModal()">
                            <i class="bi bi-unlock me-2"></i>Abrir Turno
                        </button>
                    </div>
                </div>
            </div>

            <div id="caja-actions-grid" class="row g-2 d-none">
                <div class="col-6">
                    <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm" onclick="TillApp.cashMovement('in')">
                        <i class="bi bi-plus-circle d-block fs-4 text-sfgreen"></i>
                        <span class="small">Entrada</span>
                    </button>
                </div>
                <div class="col-6">
                    <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm" onclick="TillApp.cashMovement('out')">
                        <i class="bi bi-dash-circle d-block fs-4 text-sfred"></i>
                        <span class="small">Salida</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8 h-100 d-flex flex-column">
            <div class="card h-100 border-0 rounded-4 shadow-sm overflow-hidden">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-sfblue"><i class="bi bi-clock-history me-2"></i>
                    Pedidos por Cobrar
                    </h5>
                </div>
                <div class="card-body p-0 overflow-auto" id="pending-orders-list">
                    <div class="text-center py-5 opacity-50">
                        <i class="bi bi-receipt fs-1"></i>
                        <p>No hay pedidos pendientes por cobrar</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<template id="tpl-open-caja">
    <div class="modal-body p-4">
        <div class="text-center mb-4">
            <div class="bg-light rounded-circle d-inline-flex p-3 mb-2">
                <i class="bi bi-cash-coin fs-2 text-primary"></i>
            </div> 
            <h5 class="fw-bold mb-0">Apertura de Turno</h5>
            <p class="text-muted small">Ingresa el efectivo en caja</p>
        </div>
        
        <div class="d-flex justify-content-end mb-2">
            <span class="text-secondary">Saldo:</span>
            <span class="fw-bold ps-2" id="till-open-balance">$0.00</span>
        </div>
        
        <div class="form-group mb-3">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white border-end-0 text-muted">$</span>
                <input type="number" id="opening-cash" class="form-control border-start-0 ps-0 text-center" placeholder="0.00" step="0.01">
            </div>
        </div>
        
        <button class="btn btn-sfblue btn-lg w-100 rounded-4 fw-bold shadow-sm" onclick="TillApp.confirmOpenShift(event)">
            Confirmar
        </button>
    </div>
</template>

<template id="tpl-cash-count">
    <div class="modal-body p-4 text-center">
        <h5 class="fw-bold">Cierre de Turno</h5>
        <p class="text-muted small">Por favor, cuenta el efectivo en la caja.</p>
        
        <div class="bg-light rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Saldo:</span>
                <span class="fw-bold" id="till-close-balance">$0.00</span>
            </div>
            <hr>
            <label class="small fw-bold text-dark mb-2">Efectivo en Caja:</label>
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white border-end-0 text-muted">$</span>
                <input type="number" id="real-cash-count" class="form-control text-center border-start-0 ps-0 fw-bold" placeholder="0.00">
            </div>
        </div>

        <div id="diff-display" class="mb-4 d-none">
            <span class="small d-block">Diferencia:</span>
            <h4 class="fw-bold" id="diff-amount">$0.00</h4>
            <span id="diff-label" class="badge rounded-pill">---</span>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-danger btn-lg w-100 rounded-4 fw-bold shadow-sm" onclick="TillApp.confirmCloseShift(event)">Finalizar</button>
        </div>
    </div>
</template>

<template id="tpl-audit-caja">
    <div class="modal-body p-4 text-center">
        <i class="bi bi-search fs-1 text-primary mb-3"></i>
        <h5 class="fw-bold">Arqueo de Caja</h5>
        
        <div class="bg-light rounded-4 p-3 mb-4 text-start">
            <div class="d-flex justify-content-between small mb-1">
                <span>Efectivo inicial:</span>
                <span id="audit-opening">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span>Ventas:</span>
                <span id="audit-system-sales" class="text-success">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span>Entradas:</span>
                <span id="audit-system-ins" class="text-success">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span>Salidas:</span>
                <span id="audit-system-outs" class="text-danger">$0.00</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span>Balance total:</span>
                <span id="audit-system-total" class="text-primary">$0.00</span>
            </div>
        </div>

        <div class="mb-4">
            <label class="small fw-bold text-dark mb-2">¿Cuánto efectivo retira?</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">$</span>
                <input type="number" id="audit-withdraw-amount" class="form-control text-center border-start-0 ps-0 fw-bold" placeholder="0.00">
            </div>

            <label class="small fw-bold mb-2">Concepto / Referencia:</label>
            <input type="text" id="audit-withdraw-concept" class="form-control" placeholder="Ej. Retiro parcial para oficina">
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-sfblue btn-lg w-100 rounded-3 fw-bold" onclick="TillApp.confirmWithdrawal(event)">Confirmar</button>
        </div>
    </div>
</template>

<template id="tpl-pay-order">
    <div class="modal-body p-4">
        <div class="d-flex justify-content-between">
            <h5 class="fw-bold mb-4 text-sfblue"><i class="bi bi-cash-coin me-2"></i>Procesar Cobro</h5>
            <h6 class="text-secondary mb-4 text-center me-2">Folio: <span id="display-till-folio"></span></h6>
        </div>
        
        <div class="row g-3">
            <div class="col-md-6 border-end">
                <div class="mb-3">
                    <label class="small text-muted d-block">Forma de Pago</label>
                    <select id="pay-method" class="form-select border-0 bg-light rounded-3" onchange="TillApp.togglePayInputs()">
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta (Débito/Crédito)</option>
                        <option value="transfer">Transferencia</option>
                        <option value="cheque">Cheque</option>
                        <option value="credit">Crédito</option>
                    </select>
                </div>
                
                <div id="reference-container" class="mb-3 d-none">
                    <label class="small text-muted d-block">Referencia / Últimos 4 dígitos</label>
                    <input type="text" id="pay-reference" class="form-control border-0 bg-light rounded-3">
                </div>
                
                <div id="credit-container" class="mb-3 d-none"></div>
            </div>

            <div class="col-md-6 text-center">
                <div class="bg-sfblue bg-opacity-10 p-3 rounded-4 mb-3">
                    <span class="small text-black fw-bold">TOTAL A COBRAR</span>
                    <h2 class="fw-bold text-black mb-0" id="display-till-total">$0.00</h2>
                </div>

                <div id="cash-calc">
                    <label class="small text-muted mb-1">Efectivo Recibido</label>
                    <input type="number" id="cash-received" class="form-control form-control-lg text-center border-0 bg-light rounded-4 fw-bold" placeholder="0.00">
                    <div class="mt-2">
                        <small class="text-muted">Cambio:</small>
                        <h4 class="fw-bold text-sfgreen" id="display-change">$0.00</h4>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-sfblue btn-lg w-100 rounded-4 mt-4 fw-bold shadow-sm" onclick="TillApp.confirmPayment(event)">
            Confirmar
        </button>
    </div>
</template>

<template id="tpl-till-movements">
    <div class="modal-body p-0">
        <div class="p-4 border-bottom bg-light">
            <h5 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2 text-sfblue"></i>Movimientos</h5>
            <p class="text-muted small mb-0">Entradas y salidas registradas</p>
        </div>
        <div id="till-movements-list" style="max-height: 400px; overflow-y: auto;">
            </div>
        <div class="p-3 text-end bg-light">
            <button class="btn btn-secondary btn-sm px-4 rounded-3" onclick="IxeaStages.closeModal()">Cerrar</button>
        </div>
    </div>
</template>

<template id="tpl-till-movement-form">
    <div class="modal-body p-4 text-center">
        <div id="till-mov-icon" class="mb-3"></div>
        <h5 class="fw-bold" id="till-mov-title">Movimiento</h5>
        <input type="hidden" id="till-mov-type">
        
        <div class="text-start mt-4">
            <label class="small fw-bold mb-2">Monto:</label>
            <div class="input-group input-group-lg mb-3">
                <span class="input-group-text bg-white border-end-0 text-muted">$</span>
                <input type="number" id="till-mov-amount" class="form-control border-start-0 ps-0 fw-bold" placeholder="0.00" step="0.01">
            </div>
            
            <select id="till-mov-category" class="form-select form-select-sm mb-3" onchange="TillApp.toggleProviderField()" hidden>
                <option value="general_expense" selected>Gasto General</option>
                <option value="supplier_payment">Pago a Proveedor</option>
            </select>
            
            <div id="purchase-folio-wrapper" class="d-none mt-2">
                <label class="small fw-bold">Buscar Compra (Folio):</label>
                <input type="text" 
                       id="till-mov-pay-folio" 
                       class="form-control bg-light" 
                       list="purchases-list" 
                       placeholder="Escribe el folio..." 
                       autocomplete="off"
                       oninput="TillApp.searchPurchases(this.value)">
                       
                <datalist id="purchases-list"></datalist>
            </div>

            <input type="text" id="till-mov-concept" class="form-control rounded-3" placeholder="Concepto" hidden>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-sfblue btn-lg w-100 rounded-3 fw-bold" onclick="TillApp.confirmMovement()">Confirmar</button>
        </div>
    </div>
</template>

<template id="tpl-order-history">
    <div class="modal-header border-0 pb-2">
        <div>
            <h5 class="fw-bold mb-0">Historial de Ventas</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    
    <div class="modal-body p-2">
        <div class="px-3 py-2 bg-light border-bottom border-top">
            <div class="row g-2">
                <div class="col-7">
                    <div class="input-group input-group-sm">
                        <input type="text" id="hist-search-folio" class="form-control form-control-sm border-start-0" placeholder="Buscar por folio: V-XXXXXX-XX">
                        <button class="btn btn-sfblue" onclick="TillApp.performOrderSearch()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="history-orders-list" style="max-height: 400px; overflow-y: auto;">
        </div>
    </div>
</template>

<template id="tpl-till-edit-sale">
    <div class="modal-header border-0 py-3">
        <button type="button" class="btn-close btn-modal-fuera" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-sfblue mb-0">
                <i class="bi bi-pencil-square me-2"></i>Modificar Pedido
            </h5>
            <span class="badge bg-light text-dark rounded-pill px-3 py-2 border" id="till-edit-displayId"></span>
        </div>
        
    </div>
    <div class="modal-body p-4">

        <div class="row g-4">
            <div class="col-sm-12">
                <div class="p-3 bg-light rounded-4 border-0">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" 
                               id="till-edit-customer" 
                               class="form-control bg-light" 
                               list="till-customer-list"
                               placeholder="Público en general" 
                               autocomplete="off"
                               oninput="TillApp.searchCustomers(this.value)">
                        <datalist id="till-customer-list"></datalist>
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="p-3 bg-light rounded-4 border-0">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="till-edit-tax">
                        <label class="form-check-label fw-bold" for="till-edit-tax">Solicita Factura</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button id="till-btnConfirmEdition" class="btn btn-sfblue rounded-4 px-4 fw-bold text-white flex-grow-1 shadow-sm">
                Confirmar
            </button>
        </div>
    </div>
</template>