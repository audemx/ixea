<?php
/** /modules/payments/payments.php **/
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/back-functions.php';

// Obtenemos datos
$pdo = connectDB();
$accountsList = getGlobalAccounts($pdo); // Todas las cuentas activas para traspasos
$methodsList = getPaymentMethods($pdo, ['for_purchase' => 1]); // Solo métodos para pagos
$categoriesList = getExpenseCategories($pdo, ['status' => 'active']);

// Generar HTML de opciones para Cuentas
$optSource = "";
$optDest = "";
foreach ($accountsList as $acc) {
    $account_id = $acc['account_id'];
    $code = $acc['account_code'];
    $name = $acc['name'];
    $type = $acc['type']; // asset / liability
    $subtype = $acc['subtype'];

    // --- REGLAS PARA CUENTA ORIGEN (SALIDA) ---
    // Solo activos líquidos (cash/bank)
    // EXCEPCIÓN: No se puede sacar dinero de la 'cash' (POS) ni de 'terminal' desde aquí
    if ($code !== 'cash' && $type === 'asset' && in_array($subtype, ['cash', 'bank'])) {
        $optSource .= "<option value='$code' data-account='$account_id'>$name</option>";
    }

    // --- REGLAS PARA CUENTA DESTINO (ENTRADA) ---
    // Prácticamente todas, pero bloqueamos la entrada a POS (cash) y Terminal
    // Nota: Permitimos 'liability' porque transferir a una tarjeta es "Pagar la tarjeta"
    if (in_array($code, ['main_cash', 'bank', 'credit_card'])) {
        $optDest .= "<option value='$code' data-account='$account_id'>$name</option>";
    }
}

// Generar HTML de opciones para Métodos de Pago
$optMethods = "";
foreach ($methodsList as $meth) {
    $method_id = $meth['method_id'];
    $account_id = $meth['account_id'];
    $code = $meth['method_code'];
    $name = $meth['name'];
    $optMethods .= "<option value='$code' data-method='$method_id' data-account='$account_id'>$name</option>";
}

// Generar HTML de opciones para Categorías
$optCategories = "";
foreach ($categoriesList as $cat) {
    // Omitimos la categoría 1 (que ahora sabemos que es del sistema o compras)
    if ($cat['category_id'] == 1) continue;
    
    $optCategories .= "<option value='{$cat['category_id']}'>{$cat['name']}</option>";
}

$appColor = 'sforange';
?>
<div class="container-fluid py-2 animate__animated animate__fadeIn" id="payments-canvas">
    <div class="row g-3 mb-2">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4 cursor-pointer shadow-lg" onclick="PaymentsApp.showAccount('main_cash')">
                <div class="card-body py-3">
                    <div class="fw-bold font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem;">C. Efectivo (Principal)</div>
                    <div class="h4 mb-0 font-weight-bold text-<?= $appColor ?> pb-1" id="pay-net-main_cash">$0.00</div>
                    <div class="d-flex justify-content-between border-top pt-1" style="font-size: 0.75rem;">
                        <span class="text-sfgreen"><i class="bi bi-arrow-up-short"></i> <span id="pay-in-main_cash">$0.00</span></span>
                        <span class="text-sfred"><i class="bi bi-arrow-down-short"></i> <span id="pay-out-main_cash">$0.00</span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4 cursor-pointer shadow-lg" onclick="PaymentsApp.showAccount('bank')">
                <div class="card-body py-3">
                    <div class="fw-bold font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem;">C. Bancaria (Banorte)</div>
                    <div class="h4 mb-0 font-weight-bold text-<?= $appColor ?> pb-1" id="pay-net-bank">$0.00</div>
                    <div class="d-flex justify-content-between border-top pt-1" style="font-size: 0.75rem;">
                        <span class="text-sfgreen"><i class="bi bi-arrow-up-short"></i> <span id="pay-in-bank">$0.00</span></span>
                        <span class="text-sfred"><i class="bi bi-arrow-down-short"></i> <span id="pay-out-bank">$0.00</span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4 cursor-pointer shadow-lg" onclick="PaymentsApp.showAccount('credit_card')">
                <div class="card-body py-3">
                    <div class="fw-bold font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem;">T. Crédito (Banorte)</div>
                    <div class="h4 mb-0 font-weight-bold text-<?= $appColor ?> pb-1" id="pay-net-credit_card">$0.00</div>
                    <div class="d-flex justify-content-between border-top pt-1" style="font-size: 0.75rem;">
                        <span class="text-sfgreen"><i class="bi bi-arrow-up-short"></i> <span id="pay-in-credit_card">$0.00</span></span>
                        <span class="text-sfred"><i class="bi bi-arrow-down-short"></i> <span id="pay-out-credit_card">$0.00</span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4 cursor-pointer shadow-lg" onclick="PaymentsApp.showAccount('terminal')">
                <div class="card-body py-3">
                    <div class="fw-bold font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem;">Terminal</div>
                    <div class="h4 mb-0 font-weight-bold text-<?= $appColor ?> pb-1" id="pay-net-terminal">$0.00</div>
                    <div class="d-flex justify-content-between border-top pt-1" style="font-size: 0.75rem;">
                        <span class="text-sfred"><i class="bi bi-arrow-down-short"></i> <span id="pay-out-card">$0.00</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-0 border-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <nav class="nav nav-pills nav-justified flex-nowrap py-2" id="payments-tabs">
                            <a class="nav-link link-<?= $appColor ?> active small fw-bold px-3 py-2 cursor-pointer" id="payments-pending" onclick="PaymentsApp.switchView('pending')"> Pendientes
                            </a>
                            <a class="nav-link link-<?= $appColor ?> small fw-bold px-3 py-2 cursor-pointer" id="payments-processed" onclick="PaymentsApp.switchView('processed')">
                                Procesados
                            </a>
                        </nav>
                        
                        <div class="input-group input-group-sm w-auto">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0 bg-light" id="payments-search" placeholder="Filtrar compra..." oninput="PaymentsApp.handleSearch(this.value)">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="pending-purchases-list" class="table-responsive" style="max-height: 65vh;">
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="tpl-payment-form">
    <div class="p-3">
        <div class="p-3 bg-light rounded-4 mb-4 border d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted d-block">Folio de Compra:</small>
                <strong id="payments-pay-folio" class="text-<?= $appColor ?> fs-5">---</strong>
            </div>
            <div class="text-end">
                <small class="text-muted d-block">Monto a Liquidar:</small>
                <strong id="payments-pay-total" class="text-dark fs-5">---</strong>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label small fw-bold">Método de pago</label>
                <select id="payments-pay-method" class="form-select shadow-sm">
                    <option value="" selected disabled>Seleccionar método...</option>
                    <?php echo $optMethods; ?>
                </select>
            </div>
            
            <div class="col-5">
                <label class="form-label small fw-bold">Referencia (Opcional)</label>
                <input type="text" class="form-control" id="payments-pay-reference" placeholder="Ej. Clave de rastreo">
            </div>
        </div>
        
        <div class="mt-4 pt-3">
            <button type="button" class="btn btn-<?= $appColor ?> text-white btn-lg w-100 rounded-pill shadow" id="payments-btnConfirmPayment">
                Confirmar Pago
            </button>
        </div>
    </div>
</template>

<template id="tpl-account-transfer">
    <div class="modal-header d-flex justify-content-center align-items-center p-3 m-3 bg-light rounded-4">
        <h5 class="fw-bold text-<?= $appColor ?>"><i class="bi bi-arrow-left-right me-2"></i>Traspaso entre Cuentas</h5>
    </div>
    <div class="px-3 pb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="small fw-bold mb-1">Cuenta Origen</label>
                <select id="payments-transfer-origin" class="form-select shadow-sm border-danger-subtle" onchange="PaymentsApp.handleTransferUI()">
                    <option value="" selected disabled>C. Origen...</option>
                    <?php echo $optSource; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="small fw-bold mb-1">Cuenta Destino</label>
                <select id="payments-transfer-destination" class="form-select shadow-sm border-success-subtle" onchange="PaymentsApp.handleTransferUI()">
                    <option value="" selected disabled>C. Destino...</option>
                    <?php echo $optDest; ?>
                </select>
            </div>

            <div class="col-md-7">
                <label class="small fw-bold mb-1">Referencia</label>
                <input type="text" id="payments-transfer-reference" class="form-control" placeholder="Ej. Depósito de ventas del día">
            </div>
            
            <div class="col-md-5">
                <label class="small fw-bold mb-1">Monto a Traspasar</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white">$</span>
                    <input type="number" id="payments-transfer-amount" class="form-control fw-bold text-end" placeholder="0.00">
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-<?= $appColor ?> text-white w-100 py-2 fw-bold rounded-pill shadow" id="payments-btnConfirmTransfer">
                Confirmar
            </button>
        </div>
    </div>
</template>

<template id="tpl-general-expense-form">
    <div class="modal-header d-flex justify-content-center align-items-center p-2 m-3 bg-light rounded-4">
        <h5 class="fw-bold text-<?= $appColor ?> px-3 mt-3"><i class="bi bi-receipt me-2"></i>Registrar Gasto</h5>
    </div>
    <div class="px-3 pb-3">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-muted">Categoría del Gasto</label>
                <select class="form-select shadow-sm border-<?= $appColor ?>" id="payments-expense-category">
                    <option value="" selected disabled>Seleccione categoría...</option>
                    <?= $optCategories ?>
                </select>
            </div>
            
            <div class="col-md-4 d-flex align-items-end pb-1">
                <div class="form-check form-switch p-2 w-100 d-flex justify-content-between align-items-center">
                    <label class="form-check-label small fw-bold mb-0 ms-1" for="expense-deductible">Es Deducible</label>
                    <input class="form-check-input" type="checkbox" id="payments-expense-deductible" checked>
                </div>
            </div>

            <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Concepto Detallado</label>
                <input type="text" class="form-control shadow-sm" id="payments-expense-concept" placeholder="Ej. Pago de servicio de agua Marzo">
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Monto del Gasto</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white fw-bold">$</span>
                    <input type="number" class="form-control fw-bold text-end" id="payments-expense-amount" placeholder="0.00">
                </div>
            </div>

            <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Método de Pago</label>
                <select id="payments-expense-method" class="form-select shadow-sm">
                    <option value="" selected disabled>Seleccionar método...</option>
                    <?php echo $optMethods; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Referencia / Folio</label>
                <input type="text" class="form-control shadow-sm" id="payments-expense-reference" placeholder="Opcional">
            </div>
        </div>
        
        <div class="mt-4 pt-2">
            <button type="button" class="btn btn-<?= $appColor ?> text-white btn-lg w-100 rounded-pill shadow fw-bold" id="payments-btnConfirmExpense">
                Confirmar
            </button>
        </div>
    </div>
</template>