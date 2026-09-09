<?php
/** modules/credit/credit.php **/
?>
<div class="container-fluid h-100 py-2 px-4">
    <div class="row g-4 h-100">
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm mb-4 bg-sfblue">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-white">
                        <i class="bi bi-search me-2"></i>Buscador
                    </h5>
                    <div class="position-relative">
                        <input type="text" id="credit-customer-search" 
                               class="form-control border-0 bg-light rounded-3 py-2" 
                               placeholder="Nombre, RFC o Tel..." 
                               onkeyup="CreditApp.searchCustomer(this.value)">
                        <div id="search-results" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1050; max-height: 300px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 rounded-4 shadow-sm bg-sfgreen text-white mb-4">
                <div class="card-body p-4">
                    <h6 class="small opacity-75 text-white text-uppercase fw-bold">Cartera Total</h6>
                    <h2 class="fw-bold mb-1 text-white" id="total-receivable">$0.00</h2>
                    <p class="small mb-0">
                        <i class="bi bi-graph-up-arrow me-1"></i> <span id="active-credit-clients">0</span> Clientes activos
                    </p>
                    <hr class="opacity-25">
                    <button class="btn btn-secondary w-100 rounded-3 fw-bold text-white" onclick="CreditApp.showOverdueReport()">
                        Analizar Riesgo
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9">
            <div id="customer-profile-container" class="h-100">
                <div id="credit-empty-state" class="card border-0 rounded-4 shadow-sm h-100 d-flex align-items-center justify-content-center opacity-50">
                    <div class="text-center">
                        <i class="bi bi-person-bounding-box fs-1 d-block mb-3"></i>
                        <h5>Selecciona un cliente para ver su perfil</h5>
                        <p class="small">Busca por RFC, Teléfono o Nombre en el panel izquierdo.</p>
                    </div>
                </div>

                <div id="active-profile" class="d-none h-100">
                    </div>
            </div>
        </div>
    </div>
</div>

<template id="tpl-credit-detail-pro">
    <div class="card border-0 rounded-4 shadow-sm h-100 overflow-hidden">
        <div class="card-header bg-white border-0 py-2 px-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="fw-bold mb-1" id="det-customer-name">---</h3>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light text-dark border rounded-pill" id="det-customer-rfc">RFC</span>
                        <span class="badge bg-light text-dark border rounded-pill" id="det-customer-phone">Tel</span>
                    </div>
                </div>
                <div class="col-auto text-end">
                    <span class="small text-muted d-block">Saldo Deudor</span>
                    <h2 class="fw-bold text-sfred mb-0" id="det-balance">$0.00</h2>
                </div>
            </div>
        </div>
        
        <div class="card-body p-2 bg-light">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-2 bg-white">
                        <h6 class="small fw-bold text-muted mb-2 text-uppercase">Score de Salud</h6>
                        <div class="progress rounded-pill mb-2" style="height: 10px;">
                            <div id="ai-health-bar" class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                        <p class="small mb-0" id="ai-health-text">Cliente puntual.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-75 text-center py-0" title="Abonar">
                        <button class="btn btn-link text-decoration-none text-dark" id="btn-credit-payment">
                            <i class="bi bi-cash-coin fs-2 d-block text-sfgreen mb-2"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-75 text-center py-0" title="Estado de Cuenta">
                        <button class="btn btn-link text-decoration-none text-dark" id="btn-print-statement">
                            <i class="bi bi-printer fs-2 d-block text-sfblue mb-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold m-0">Últimos Movimientos</h6>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="CreditApp.viewAllMovements()">Ver todos</button>
                </div>
                <div id="det-movements-list" class="bg-white rounded-4 shadow-sm overflow-hidden">
                    </div>
            </div>
        </div>
    </div>
</template>

<template id="tpl-credit-payment">
    <div class="modal-content border-0 rounded-4 overflow-hidden">
        <div class="modal-header bg-sfgreen text-white border-0 p-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2"></i>Registrar Abono</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <div class="mb-4 text-center">
                <span class="text-muted small d-block">Saldo Pendiente:</span>
                <h3 class="fw-bold text-sfred" id="pay-cust-balance">$0.00</h3>
            </div>
            
            <form id="form-customer-payment">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Monto del Abono</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-0">$</span>
                            <input type="number" step="0.01" class="form-control bg-light border-0 fw-bold text-end" name="amount" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Método</label>
                        <select class="form-select form-select-sm bg-light border-0" name="method" id="credit-pay-method" onchange="CreditApp.toggleReference()" required>
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                            <option value="check">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-5 d-none" id="credit-reference-container">
                        <label class="form-label small fw-bold">Referencia</label>
                        <input type="text" class="form-control form-control-sm bg-light border-0" name="reference" id="credit-pay-reference">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer border-0 p-4 pt-0">
            <button id="credit-pay-btnPayment" type="button" class="btn btn-sfgreen text-white rounded-3 px-4 fw-bold" onclick="CreditApp.savePayment()">
                Confirmar Pago
            </button>
        </div>
    </div>
</template>

<template id="tpl-credit-risk-report">
    <div class="p-3">
        <h5 class="mb-3"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Análisis de Cartera Vencida</h5>
        
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <div class="small text-muted">Total por Cobrar</div>
                        <div class="h4 fw-bold" id="risk-total-receivable">$0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger-subtle border-0">
                    <div class="card-body text-center">
                        <div class="small text-danger fw-bold">En Mora (>30 días)</div>
                        <div class="h4 fw-bold text-danger" id="risk-total-critical">$0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary-subtle border-0">
                    <div class="card-body text-center">
                        <div class="small text-primary fw-bold">Clientes en Riesgo</div>
                        <div class="h4 fw-bold text-primary" id="risk-count">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>CLIENTE</th>
                        <th>SALDO TOTAL</th>
                        <th>VENCIDO (>15d)</th>
                        <th>MÁS ANTIGUO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="risk-report-body">
                    </tbody>
            </table>
        </div>
    </div>
</template>
