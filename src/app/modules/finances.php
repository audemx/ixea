<?php
/** modules/finance.php **/

use App\Models\Shift;
use App\Enums\Status;

// Turnos recientes o activos para filtros de reportes
$shifts = Shift::orderBy('id', 'desc')->limit(10)->get();
?>
<div class="w-full h-full p-4 flex flex-col gap-4 bg-slate-900 text-slate-100 font-sans select-none overflow-hidden">
    
    <!-- HEADER Y FILTROS DE FECHA / TURNO -->
    <header class="bg-slate-800 border border-slate-700 px-4 py-3 rounded-2xl flex flex-col sm:flex-row justify-between items-center gap-3 shadow-md flex-shrink-0">
        <h5 class="m-0 font-bold text-white text-lg flex items-center gap-2">
            <i class="bi bi-graph-up-arrow text-blue-400"></i>Finanzas y Reportes de Caja
        </h5>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <!-- Selector de Turno / Rango -->
            <select id="finance-filter-shift" class="bg-slate-900 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-blue-500">
                <option value="today">Ventas del Día (Hoy)</option>
                <?php foreach ($shifts as $shift): ?>
                    <option value="<?= $shift->id ?>">Turno #<?= $shift->id ?> (<?= $shift->created_at ?>)</option>
                <?php endforeach; ?>
            </select>

            <button onclick="FinanceApp.loadReport()" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2 shadow-md">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>
        </div>
    </header>

    <!-- TARJETAS MÉTRICAS / KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 flex-shrink-0">
        <!-- Total Ventas -->
        <div class="bg-slate-800 border-l-4 border-emerald-500 rounded-2xl p-4 shadow-md border-t border-r border-b border-slate-700 flex items-center justify-between">
            <div>
                <h6 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Ventas Totales</h6>
                <div class="text-2xl font-extrabold text-white" id="finance-kpi-sales">$0.00</div>
            </div>
            <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-400 text-2xl">
                <i class="bi bi-cash-stack"></i>
            </div>
        </div>

        <!-- Total Propinas -->
        <div class="bg-slate-800 border-l-4 border-amber-500 rounded-2xl p-4 shadow-md border-t border-r border-b border-slate-700 flex items-center justify-between">
            <div>
                <h6 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Propinas Recaudadas</h6>
                <div class="text-2xl font-extrabold text-white" id="finance-kpi-tips">$0.00</div>
            </div>
            <div class="p-3 bg-amber-500/10 rounded-xl text-amber-400 text-2xl">
                <i class="bi bi-heart-fill"></i>
            </div>
        </div>

        <!-- Efectivo en Caja -->
        <div class="bg-slate-800 border-l-4 border-blue-500 rounded-2xl p-4 shadow-md border-t border-r border-b border-slate-700 flex items-center justify-between">
            <div>
                <h6 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Ingresos Efectivo</h6>
                <div class="text-2xl font-extrabold text-white" id="finance-kpi-cash">$0.00</div>
            </div>
            <div class="p-3 bg-blue-500/10 rounded-xl text-blue-400 text-2xl">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>

        <!-- Pagos Electrónicos / Tarjetas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 flex-shrink-0">
    <div class="bg-slate-800 border-l-4 border-purple-500 rounded-2xl p-4 shadow-md border-t border-r border-b border-slate-700 flex items-center justify-between">
        <div>
            <h6 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pagos Digitales</h6>
            <div class="text-2xl font-extrabold text-white" id="finance-kpi-digital">$0.00</div>
        </div>
        <div class="p-3 bg-purple-500/10 rounded-xl text-purple-400 text-2xl">
            <i class="bi bi-credit-card-2-front"></i>
        </div>
    </div>
</div>

    <!-- SECCIÓN DE DESGLOSE (Gráficos / Métodos de Pago y Tabla de Movimientos) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 overflow-hidden">
        
        <!-- Columna Izquierda: Desglose por Método de Pago -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-lg p-4 flex flex-col">
            <h6 class="font-bold text-white text-sm mb-3 flex items-center gap-2 border-b border-slate-700 pb-2">
                <i class="bi bi-pie-chart text-blue-400"></i> Desglose por Método de Pago
            </h6>
            <div id="finance-payment-methods-breakdown" class="space-y-3 overflow-y-auto flex-1 pr-1">
                <!-- Se inyecta dinámicamente con JS -->
                <div class="text-center py-8 text-slate-500 text-xs">Cargando métodos de pago...</div>
            </div>
        </div>

        <!-- Columna Derecha: Detalle de Movimientos / Transacciones -->
        <div class="lg:col-span-2 bg-slate-800 border border-slate-700 rounded-2xl shadow-lg flex flex-col overflow-hidden">
            <div class="p-4 border-b border-slate-700 bg-slate-800/80 flex justify-between items-center">
                <h6 class="font-bold text-white text-sm flex items-center gap-2">
                    <i class="bi bi-list-check text-emerald-400"></i> Historial de Cobros y Transacciones
                </h6>
                <span class="text-xs text-slate-400" id="finance-records-count">0 registros</span>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/60 uppercase text-slate-400 border-b border-slate-700">
                        <tr>
                            <th class="py-3 px-4">Folio / Orden</th>
                            <th class="py-3 px-4">Método</th>
                            <th class="py-3 px-4 text-center">Referencia</th>
                            <th class="py-3 px-4 text-right">Propina</th>
                            <th class="py-3 px-4 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50" id="finance-transactions-table-body">
                        <!-- Se llena dinámicamente con JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>