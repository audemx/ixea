<?php
/** /modules/finance/finance.php **/
$appColor = 'sfcyan';
?>
<div class="container-fluid py-2 animate__animated animate__fadeIn" id="finance-canvas">

    <div class="row g-2 mb-3">
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-<?=$appColor?> border-3 mx-2">
                <div class="card-body bg-light p-3">
                    <h6 class="fw-bold">TESORERÍA</h6>
                    <div class="h5 fw-bold text-sfgreen mb-0" id="fin-net-liquidity">$0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-<?=$appColor?> border-3 mx-2">
                <div class="card-body bg-light p-3">
                    <h6 class="fw-bold">POR COBRAR</h6>
                    <div class="h5 fw-bold text-sfgreen mb-0" id="fin-net-receivable">$0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-<?=$appColor?> border-3 mx-2">
                <div class="card-body bg-light p-3">
                    <h6 class="fw-bold">POR PAGAR</h6>
                    <div class="h5 fw-bold text-sforange mb-0" id="fin-net-payable">$0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-<?=$appColor?> border-3 mx-2">
                <div class="card-body bg-light p-3">
                    <h6 class="fw-bold">INVENTARIO</h6>
                    <div class="h5 fw-bold text-sfpurple mb-0" id="fin-net-inventory">$0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-<?=$appColor?> border-3 mx-2">
                <div class="card-body bg-light p-3">
                    <h6 class="fw-bold">PATRIMONIO</h6>
                    <div class="h5 fw-bold text-sfpurple mb-0" id="fin-net-worth">$0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-<?=$appColor?> border-3 mx-2" style="border-top-color: #6f42c1 !important;">
                <div class="card-body bg-light p-3">
                    <h6 class="fw-bold">DIVIDENDOS</h6>
                    <div class="h5 fw-bold text-<?=$appColor?> mb-0" id="fin-net-dividend" style="color: #6f42c1;">$0.00</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-4 text-sfcyan">Evolución del Flujo de Caja</h5>
                    <canvas id="finance-chart-flow" style="height: 380px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2">
            <div class="card shadow-sm border-0 rounded-4 bg-dark text-white mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-sfcyan mb-3 w-100 text-center"><i class="bi bi-calculator me-2"></i>GENERAL</h6>
                    
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Ventas:</span>
                            <span id="fin-gen-sales" class="text-sfgreen">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Compras:</span>
                            <span id="fin-gen-purchases" class="text-sfred">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Gastos:</span>
                            <span id="fin-gen-expenses" class="text-sfred">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold pt-2 border-top">
                            <span>Utilidad:</span>
                            <span id="fin-gen-profit">$0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 rounded-4 bg-dark text-white mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-sfcyan mb-3 w-100 text-center"><i class="bi bi-calculator me-2"></i>FISCAL</h6>
                    
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">V. Facturadas:</span>
                            <span id="fin-tax-sales" class="text-sfgreen">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Compras:</span>
                            <span id="fin-tax-purchases" class="text-sfred">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Gastos:</span>
                            <span id="fin-tax-expenses" class="text-sfred">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold pt-2 border-top">
                            <span>Utilidad:</span>
                            <span id="fin-tax-profit">$0.00</span>
                        </div>
                    </div>
                    
                    <div class="bg-secondary bg-opacity-25 rounded-3 border border-secondary p-2 my-3 text-center">
                        <h6 class="d-block">ISR por Pagar</h6>
                        <h4 id="fin-tax-isr">$0.00</h4>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">IVA Cobrado:</span>
                            <span id="fin-tax-ivain" class="text-sfred">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">IVA Pagado:</span>
                            <span id="fin-tax-ivaout" class="text-sfgreen">$0.00</span>
                        </div>
                        <div class="bg-secondary bg-opacity-25 rounded-3 border border-secondary p-2 mt-3 text-center">
                            <h6 class="d-block">IVA por Pagar</h6>
                            <h4 id="fin-tax-ivanet">$0.00</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>