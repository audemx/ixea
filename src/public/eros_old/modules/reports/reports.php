<?php
// /modules/reports/reports.php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';

// El color dinámico definido previamente en el core
$appColor = 'sfcyan'; 
?>

<div class="container-fluid py-3 px-4 animate__animated animate__fadeIn" id="reports-canvas">
    
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-2 mb-4 gap-3">
        <nav class="nav nav-pills gap-2" id="reports-tabs">
            <a class="nav-link link-<?= $appColor ?> active small fw-bold px-3 py-2 cursor-pointer" id="tab-sales" onclick="ReportsApp.switchTab('sales')">
                <i class="bi bi-graph-up-arrow me-2"></i>Ventas
            </a>
            <a class="nav-link link-<?= $appColor ?> small fw-bold px-3 py-2 cursor-pointer" id="tab-expenses" onclick="ReportsApp.switchTab('expenses')">
                <i class="bi bi-wallet2 me-2"></i>Gastos
            </a>
        </nav>
        
        <div id="menu-app-filters" class="d-flex align-items-center"></div>
    </div>

    <div id="reports-dynamic-content"></div>
</div>

<template id="tpl-report-sales">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4">
                <div class="card-body py-3">
                    <div class="text-xs fw-bold text-uppercase text-muted mb-1">Ventas Totales</div>
                    <div class="h4 mb-0 fw-bold text-dark" id="report-sales-total">$0.00</div>
                    <div class="text-xs mt-2" id="report-sales-trenContainer">
                        <i class="bi"></i> <span id="report-sales-tren">0%</span> <span class="text-muted">vs per. anterior</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4">
                <div class="card-body py-3">
                    <div class="text-xs fw-bold text-uppercase text-muted mb-1">Ticket Promedio</div>
                    <div class="h4 mb-0 fw-bold text-dark" id="report-sales-avg">$0.00</div>
                    <small class="text-muted text-xs">Por transacción</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4">
                <div class="card-body py-3">
                    <div class="text-xs fw-bold text-uppercase text-muted mb-1">Utilidad Bruta</div>
                    <div class="h4 mb-0 fw-bold text-dark" id="report-sales-profit">$0.00</div>
                    <small class="text-muted text-xs">Precio venta - Costo compra</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-<?= $appColor ?> border-4">
                <div class="card-body py-3">
                    <div class="text-xs fw-bold text-uppercase text-muted mb-1">Transacciones</div>
                    <div class="h4 mb-0 fw-bold text-dark" id="report-sales-operations">0</div>
                    <small class="text-muted text-xs">Operaciones registradas</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark small">Tendencia Dinámica de Ingresos</h6>
                    <span class="badge bg-light text-<?= $appColor ?> border" id="report-sales-timeline">---</span>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height:280px;"><canvas id="report-sales-canvasTrend"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0"><h6 class="m-0 fw-bold text-dark small">Desglose: Formas de Pago</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush small" id="report-sales-paymentMethods">
                        <div class="p-3 text-center opacity-50">Cargando flujos de efectivo...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0"><h6 class="m-0 fw-bold text-dark small">Top 10 Productos con Mayor Margen</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 style-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Producto</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-end">Vendido</th>
                                    <th class="text-end pe-3">Margen Real</th>
                                </tr>
                            </thead>
                            <tbody id="report-sales-tbProducts"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0"><h6 class="m-0 fw-bold text-dark small">Distribución por Categorías</h6></div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container" style="height:220px; width:100%;"><canvas id="report-sales-canvasCategories"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="tpl-report-expenses">
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 border-start border-sfred border-4">
                <div class="card-body py-3">
                    <div class="text-xs fw-bold text-uppercase text-muted mb-1">Egresos Totales del Periodo</div>
                    <div class="h3 mb-0 fw-bold text-dark" id="report-expenses-total">$0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body py-3">
                    <div class="text-xs fw-bold text-uppercase text-muted mb-1">Categoría con Mayor Impacto</div>
                    <div class="h3 mb-0 fw-bold text-dark" id="report-expenses-categories">---</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 p-3">
                <h6 class="fw-bold text-dark small mb-3">Distribución de Gastos por Categoría</h6>
                <div class="chart-container" style="height:260px;"><canvas id="report-expenses-canvasCategories"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <h6 class="fw-bold text-dark small p-3 m-0 border-bottom">Gastos Operativos Críticos</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 style-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Concepto/Categoría</th>
                                <th class="text-end pe-3">Monto Total</th>
                            </tr>
                        </thead>
                        <tbody id="report-expenses-tbExpenses"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>