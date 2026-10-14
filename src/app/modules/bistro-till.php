<?php
/** modules/bistro-till.php **/

use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Enums\Status;

$paymentMethods = PaymentMethod::where('status_id', Status::ACTIVE)
    ->whereNotNull('account_in')
    ->get();

$categories = ExpenseCategory::where('status_id', Status::ACTIVE)
    ->get();
?>
<div class="w-full h-full p-4">
    <div class="flex flex-col lg:flex-row h-full gap-4">
        
        <!-- Panel Izquierdo: Estado de Caja y Acciones -->
        <div class="w-full lg:w-1/3 flex flex-col h-full gap-4">
            <div class="bg-slate-700 border border-slate-400 rounded-2xl p-6 text-center shadow-lg">
                    
                <!-- Estado de la caja -->
                <div id="bistro-till-status-icon" class="mb-2 text-slate-400 text-4xl">
                    <i class="bi bi-door-closed"></i>
                </div>
                <h4 class="text-xl font-bold text-white mb-1" id="bistro-till-status">Caja Cerrada</h4>
                <p class="text-slate-400 text-xs mb-4" id="bistro-till-user-info"></p>
                
                <!-- Info cuando la caja está abierta -->
                <div id="bistro-till-open-info" class="hidden">
                    <div class="border-t border-slate-700 my-4"></div>
                    <div class="flex justify-between items-center mb-4 bg-slate-900/50 p-3 rounded-xl border border-slate-700">
                        <span class="text-slate-400 text-xs font-semibold">Saldo en Caja:</span>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-lg text-white" id="bistro-till-cash">******</span>
                            <button class="text-slate-400 hover:text-white transition p-1" onclick="BistroTillApp.toggleCashVisibility()" title="Mostrar/Ocultar">
                                <i class="bi bi-eye-slash text-lg" id="bistro-till-eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button class="w-full py-2 bg-slate-700 hover:bg-slate-600 text-blue-400 text-sm font-semibold rounded-xl border border-blue-500/30 transition flex items-center justify-center gap-2" onclick="BistroTillApp.openAuditModal()">
                            <i class="bi bi-calculator"></i>Arqueo / Retiro
                        </button>
                    </div>
                </div>

                <!-- Botón cuando la caja está cerrada -->
                <div id="bistro-till-closed-info">
                    <button class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl shadow-lg transition flex items-center justify-center gap-2" onclick="BistroTillApp.openShiftModal()">
                        <i class="bi bi-unlock"></i>Abrir Turno
                    </button>
                </div>

            </div>

            <!-- Botones de Acción (Visibles solo con turno abierto) -->
            <div id="bistro-till-actions-grid" class="grid grid-cols-2 gap-3 hidden">
                <button class="bg-slate-800 hover:bg-slate-700 border border-slate-700 p-4 rounded-2xl text-center shadow-md transition" onclick="BistroTillApp.cashMovement('in')">
                    <i class="bi bi-plus-circle-fill block text-2xl text-emerald-400 mb-1"></i>
                    <span class="font-bold block text-sm text-white">Entrada</span>
                    <small class="text-slate-400 text-[10px]">Ingreso manual</small>
                </button>
                
                <button class="bg-slate-800 hover:bg-slate-700 border border-slate-700 p-4 rounded-2xl text-center shadow-md transition" onclick="BistroTillApp.cashMovement('out')">
                    <i class="bi bi-dash-circle-fill block text-2xl text-rose-400 mb-1"></i>
                    <span class="font-bold block text-sm text-white">Salida</span>
                    <small class="text-slate-400 text-[10px]">Gasto o pago</small>
                </button>
                
                <button class="col-span-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 py-2.5 px-4 rounded-xl text-slate-300 text-xs font-semibold shadow-md transition flex items-center justify-center gap-2" onclick="BistroTillApp.openMovementsModal()">
                    <i class="bi bi-arrow-left-right"></i>Ver Historial de Movimientos
                </button>
            </div>

            <!-- Botones de Acción (Visibles solo con turno abierto) -->
            <div id="caja-actions-grid" class="row g-2 d-none">
                <div class="col-6">
                    <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm text-start ps-3" onclick="BistroTillApp.cashMovement('in')">
                        <i class="bi bi-plus-circle-fill d-block fs-3 text-success mb-1"></i>
                        <span class="fw-bold d-block small">Entrada</span>
                        <small class="text-muted" style="font-size: 0.75rem;">Ingreso manual</small>
                    </button>
                </div>
                <div class="col-6">
                    <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm text-start ps-3" onclick="BistroTillApp.cashMovement('out')">
                        <i class="bi bi-dash-circle-fill d-block fs-3 text-danger mb-1"></i>
                        <span class="fw-bold d-block small">Salida</span>
                        <small class="text-muted" style="font-size: 0.75rem;">Gasto o pago</small>
                    </button>
                </div>
                <div class="col-12 mt-2">
                    <button class="btn btn-light w-100 py-2 rounded-3 shadow-sm text-secondary small" onclick="BistroTillApp.openMovementsModal()">
                        <i class="bi bi-arrow-left-right me-2"></i>Ver Historial de Movimientos
                    </button>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Lista de Cuentas / Pedidos -->
        <div class="w-full lg:w-2/3 h-full flex flex-col">
            <div class="bg-slate-700 border border-slate-400 rounded-2xl shadow-lg overflow-hidden flex flex-col h-full">
                <div class="p-4 border-b border-slate-400 flex justify-between items-center bg-slate-700/50">
                    <h5 id="bistro-till-title" class="fw-bold m-0 text-white text-lg">
                        <i class="bi bi-receipt me-2"></i>Pedidos por Cobrar
                    </h5>
                    <div id="bistro-till-sync-countdown" class="hidden">
                        <!-- Renderizado dinamico por bistro-till.js -->
                    </div>
                </div>
                <div class="p-4 overflow-y-auto flex-1" id="bistro-till-pending-list">
                    <div class="text-center py-12 text-slate-500">
                        <i class="bi bi-receipt text-4xl block mb-2"></i>
                        <p class="text-sm">No hay pedidos registrados</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal -->
    <div id="bistro-till-modal"></div>
</div>

<!-- ==================== TEMPLATES MODALES ==================== -->

<!-- Modal: Apertura de Caja (uso: open) -->
<template id="bistro-till-template-open">
    <div class="flex flex-col w-full h-full max-h-[80vh] p-4 text-white">
        <div class="text-center mb-4">
            <div class="bg-slate-700/50 rounded-full inline-flex p-3 mb-2 text-blue-400">
                <i class="bi bi-cash-coin text-3xl"></i>
            </div> 
            <h3 class="font-bold text-lg">Apertura de Turno</h3>
            <p class="text-slate-400 text-xs">Ingresa el efectivo disponible en caja física</p>
        </div>
        
        <div class="flex justify-between items-center mb-4 bg-slate-900/50 p-3 rounded-xl border border-slate-700">
            <span class="text-slate-400 text-xs">Saldo en Sistema:</span>
            <span class="font-bold text-white" id="bistro-till-open-balance">$0.00</span>
        </div>
        
        <div class="mb-6">
            <label class="block text-xs font-bold text-slate-400 mb-1">Monto de Apertura</label>
            <div class="relative flex items-center">
                <span class="absolute left-3 text-slate-400 font-bold">$</span>
                <input type="number" id="bistro-till-open-amount" class="w-full bg-slate-900 border border-slate-700 rounded-xl py-3 pl-8 pr-3 text-center text-lg font-bold text-white focus:outline-none focus:border-blue-500" placeholder="0.00" step="0.01">
            </div>
            <small class="text-slate-400 block mt-1 text-[10px]">Si ingresas un monto mayor al saldo en sistema, se registrará un ajuste automático.</small>
        </div>
        
        <div class="flex space-x-2 pt-2 border-t border-slate-700">
            <button onclick="IxeaComponents.closeModal()" class="w-1/3 bg-slate-600 hover:bg-slate-500 text-white font-bold py-2.5 rounded-xl text-sm transition">
                Cancelar
            </button>
            <button onclick="BistroTillApp.confirmOpenShift()" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl text-sm shadow-lg transition">
                Abrir Turno
            </button>
        </div>
    </div>
</template>

<!-- Modal: Cierre de Turno (uso: close) -->
<template id="bistro-till-template-close">
    <div class="flex flex-col w-full h-full max-h-[80vh] p-4 text-center text-white">
        <h3 class="font-bold text-lg mb-1">Cierre de Turno</h3>
        <p class="text-slate-400 text-xs mb-4">Ingresa el conteo físico de efectivo en caja.</p>
        
        <div class="bg-slate-900/50 rounded-2xl p-4 border border-slate-700 mb-4 text-left">
            <div class="flex justify-between mb-2">
                <span class="text-slate-400 text-xs">Efectivo esperado:</span>
                <span class="font-bold text-white" id="bistro-till-close-expected">$0.00</span>
            </div>
            <div class="border-t border-slate-700 my-2"></div>
            <label class="text-xs font-bold text-slate-300 mb-2 block text-center">Efectivo Contado:</label>
            <div class="relative flex items-center">
                <span class="absolute left-3 text-slate-400 font-bold">$</span>
                <input type="number" id="bistro-till-close-counted" class="w-full bg-slate-900 border border-slate-700 rounded-xl py-3 pl-8 pr-3 text-center text-lg font-bold text-white focus:outline-none focus:border-blue-500" placeholder="0.00" step="0.01">
            </div>
        </div>

        <div id="bistro-till-close-diff-container" class="mb-4 hidden">
            <span class="text-xs block text-slate-400">Diferencia:</span>
            <h4 class="font-bold text-xl my-1" id="bistro-till-close-diff">$0.00</h4>
            <span id="bistro-till-close-diff-badge" class="px-3 py-1 rounded-full text-xs font-semibold inline-block">---</span>
        </div>

        <div class="flex space-x-2 pt-2 border-t border-slate-700">
            <button onclick="IxeaComponents.closeModal()" class="w-1/3 bg-slate-600 hover:bg-slate-500 text-white font-bold py-2.5 rounded-xl text-sm transition">
                Cancelar
            </button>
            <button onclick="BistroTillApp.confirmCloseShift()" class="w-2/3 bg-rose-600 hover:bg-rose-500 text-white font-bold py-2.5 rounded-xl text-sm shadow-lg transition">
                Cerrar Turno
            </button>
        </div>
    </div>
</template>

<!-- Modal: Arqueo / Retiro Parcial (uso: audit) -->
<template id="bistro-till-template-audit">
    <div class="flex flex-col w-full h-full max-h-[80vh] p-4 text-white">
        <div class="text-center mb-3">
            <i class="bi bi-calculator text-3xl text-blue-400 block mb-1"></i>
            <h3 class="font-bold text-lg">Arqueo y Retiro Parcial</h3>
        </div>
        
        <div class="bg-slate-900/50 rounded-2xl p-3 border border-slate-700 mb-4 text-xs space-y-1">
            <div class="flex justify-between">
                <span class="text-slate-400">Apertura:</span>
                <span id="bistro-till-audit-opening" class="font-semibold">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Ventas en Efectivo:</span>
                <span id="bistro-till-audit-sales" class="text-emerald-400 font-semibold">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Entradas:</span>
                <span id="bistro-till-audit-ins" class="text-emerald-400 font-semibold">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Salidas / Retiros:</span>
                <span id="bistro-till-audit-outs" class="text-rose-400 font-semibold">$0.00</span>
            </div>
            <div class="border-t border-slate-700 my-1"></div>
            <div class="flex justify-between font-bold text-sm">
                <span>Total Estimado en Caja:</span>
                <span id="bistro-till-audit-total" class="text-blue-400">$0.00</span>
            </div>
        </div>

        <div class="mb-4 space-y-3">
            <div>
                <label class="text-xs font-bold text-slate-300 block mb-1">Monto a retirar:</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3 text-slate-400 font-bold">$</span>
                    <input type="number" id="bistro-till-audit-amount" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 pl-8 text-center text-sm font-bold text-white focus:outline-none focus:border-blue-500" placeholder="0.00" step="0.01">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-300 block mb-1">Concepto / Motivo del retiro:</label>
                <input type="text" id="bistro-till-audit-concept" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500" placeholder="Ej. Retiro parcial a caja fuerte">
            </div>
        </div>

        <div class="flex space-x-2 pt-2 border-t border-slate-700">
            <button onclick="IxeaComponents.closeModal()" class="w-1/3 bg-slate-600 hover:bg-slate-500 text-white font-bold py-2.5 rounded-xl text-sm transition">
                Cancelar
            </button>
            <button onclick="BistroTillApp.confirmWithdrawal()" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl text-sm shadow-lg transition">
                Registrar Retiro
            </button>
        </div>
    </div>
</template>

<!-- Modal: Procesar Cobro (uso: pay) -->
<template id="bistro-till-template-pay">
    <div class="flex flex-col w-full h-full max-h-[85vh] px-3 py-2 text-white">
        <!-- Header -->
        <div class="flex justify-between items-center mb-2 border-b border-slate-700 pb-2">
            <h3 class="font-bold text-base text-blue-400 flex items-center gap-2">
                <i class="bi bi-cash-coin"></i>Procesar Cobro
            </h3>
            <span class="bg-slate-900 border border-slate-700 text-slate-300 px-3 py-1 rounded-xl text-xs">
                <strong id="bistro-till-pay-folio">---</strong>
            </span>
        </div>
        
        <!-- Cuerpo Principal -->
        <div class="space-y-4 mb-2">
            <!-- Selección de Método de Pago mediante Botones -->
            <div class="space-y-2">
                <!-- Input oculto para conservar la compatibilidad de datos -->
                <input type="hidden" id="bistro-till-pay-method" value="">

                <!-- Contenedor scrollable con grid para métodos de pago -->
                <div class="w-full overflow-x-auto py-2 scrollbar-thin scrollbar-thumb-slate-700">
                    <div class="grid grid-flow-col grid-rows-1 gap-2 auto-cols-[calc(25%-6px)]" id="bistro-till-pay-methods-grid">
                        <?php foreach ($paymentMethods as $method): 
                            $code = strtolower($method->name);
                            
                            // Si la base de datos tiene un ícono registrado se usa, de lo contrario se asigna un genérico
                            $icon = !empty($method->icon) ? $method->icon : 'bi-wallet2 text-slate-400';
                        ?>
                            <button type="button" 
                                    data-id="<?= $method->id ?>" 
                                    data-code="<?= htmlspecialchars($code) ?>"
                                    onclick="BistroTillApp.selectPayMethod(this)"
                                    class="pay-method-btn flex flex-col items-center justify-center p-3 bg-slate-900 border border-slate-700 hover:border-blue-500 rounded-xl transition cursor-pointer group h-24">
                                <i class="bi <?= $icon ?> text-2xl mb-1 group-hover:scale-110 transition-transform"></i>
                                <span class="text-[11px] font-bold text-slate-300 group-hover:text-white text-center line-clamp-1"><?= htmlspecialchars($method->description) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Campo Dinámico: Referencia -->
            <div id="bistro-till-pay-ref-container" class="mt-2"> 
                <label class="text-xs text-slate-400 font-bold block mb-1">Referencia / Observaciones</label> 
                <input type="text" 
                    id="bistro-till-pay-reference" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white text-center focus:outline-none focus:border-blue-500" 
                    maxlength="4" inputmode="numeric" placeholder="Últimos 4 dígitos" autocomplete="off"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);"> 
            </div>

            <!-- Caja Resumen y Montos -->
            <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-slate-700 space-y-3">
                <!-- Total Cobrado -->
                <div class="bg-blue-600/10 border border-blue-500/20 p-3 rounded-xl flex justify-between items-center">
                    <span class="text-xs text-slate-400 font-bold">TOTAL COBRADO</span>
                    <h2 class="text-xl font-black text-emerald-400" id="bistro-till-pay-total-display">$0.00</h2>
                </div>

                <!-- Propina Opcional -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 items-center">
                    <div>
                        <label class="text-xs text-slate-400 font-semibold block mb-1">Propina ($)</label>
                        <input type="text" 
                               id="bistro-till-pay-tip" 
                               class="w-full bg-slate-900 border border-slate-700 text-center rounded-xl p-2 text-xs font-bold text-white focus:outline-none focus:border-blue-500" 
                               placeholder="0.00" inputmode="decimal" autocomplete="off" 
                               oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); BistroTillApp.calculateChange();">
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 block mb-1">Accesos rápidos</span>
                        <div class="flex gap-1">
                            <button type="button" onclick="BistroTillApp.setTipPercentage(10)" class="w-full py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-bold rounded-lg border border-slate-700 transition">10%</button>
                            <button type="button" onclick="BistroTillApp.setTipPercentage(15)" class="w-full py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-bold rounded-lg border border-slate-700 transition">15%</button>
                        </div>
                    </div>
                </div>

                <!-- Calculadora Efectivo -->
                <div id="bistro-till-pay-cash-container" class="space-y-2 pt-2 border-t border-slate-800">
                    <div>
                        <label class="text-xs text-slate-400 font-semibold block mb-1">Efectivo Recibido</label>
                        <input type="text" 
                               id="bistro-till-pay-cash-received" 
                               class="w-full bg-slate-900 border border-slate-700 text-center text-lg rounded-xl p-2 font-bold text-white focus:outline-none focus:border-blue-500" 
                               placeholder="0.00" inputmode="decimal" autocomplete="off"
                               oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); BistroTillApp.calculateChange();">
                    </div>
                    <div class="flex justify-between items-center px-1">
                        <small class="text-slate-400 text-xs">Cambio:</small>
                        <h4 class="font-mono font-bold text-slate-500 text-base" id="bistro-till-pay-change-display">$0.00</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex space-x-2 pt-3 border-t border-slate-700 mt-auto">
            <button type="button" onclick="IxeaComponents.closeModal()" class="w-1/3 bg-slate-700 hover:bg-slate-600 text-white font-bold py-2.5 rounded-xl text-xs transition">
                Cancelar
            </button>
            <button type="button" onclick="BistroTillApp.confirmPayment()" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl text-xs shadow-lg transition flex items-center justify-center gap-2">
                <i class="bi bi-check-circle"></i>Confirmar
            </button>
        </div>
    </div>
</template>

<!-- Modal: Lista de Movimientos (uso: movements) -->
<template id="bistro-till-template-movements">
    <div class="flex flex-col w-full h-full max-h-[80vh] text-white">
        <div class="p-4 border-b border-slate-700 flex justify-between items-center bg-slate-800/50">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2 text-blue-400">
                    <i class="bi bi-arrow-left-right"></i>Movimientos de Caja
                </h3>
                <p class="text-slate-400 text-xs">Entradas y salidas del turno activo</p>
            </div>
            <button class="text-slate-400 hover:text-white transition" onclick="IxeaComponents.closeModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div id="bistro-till-movements-list" class="p-4 overflow-y-auto max-h-96 space-y-2">
            <!-- Render dinámico desde JS -->
        </div>

        <div class="p-3 border-t border-slate-700 text-right">
            <button onclick="IxeaComponents.closeModal()" class="bg-slate-600 hover:bg-slate-500 text-white font-bold px-4 py-2 rounded-xl text-xs transition">
                Cerrar
            </button>
        </div>
    </div>
</template>

<!-- Modal: Formulario Entrada/Salida (uso: movement) -->
<template id="bistro-till-template-movement">
    <div class="flex flex-col w-full h-full max-h-[80vh] p-3 text-center text-white">
        <div id="bistro-till-mov-icon" class="mb-2"></div>
        <h3 class="font-bold text-lg mb-3" id="bistro-till-mov-title">Registrar Movimiento</h3>
        <input type="hidden" id="bistro-till-mov-type">
        
        <div class="text-left space-y-3 mb-4">
            <div class="text-center">
                <label class="text-xs font-bold block mb-1 text-slate-300">Monto:</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3 text-slate-400 font-bold">$</span>
                    <input type="number" id="bistro-till-mov-amount" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 pl-8 text-center text-sm font-bold text-white focus:outline-none focus:border-blue-500" placeholder="0.00" step="0.01">
                </div>
            </div>
            
            <div class="text-center">
                <label class="text-xs font-bold block mb-1 text-slate-300">Concepto / Motivo:</label>
                <input type="text" id="bistro-till-mov-concept" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500 text-left" placeholder="Ej. Compra de insumos de emergencia">
            </div>

            <select id="bistro-till-mov-category" class="mt-3 w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-2.5 text-xs hidden focus:outline-none focus:border-blue-500" onchange="BistroTillApp.toggleProviderField()">
                <option value="" disabled selected>Selecciona una categoría:</option>
                <?php foreach ($categories as $cat): 
                    $id = $cat->id;
                    $desc = $cat->description;
                ?>
                    <option  value="<?= $id ?>"><?= htmlspecialchars($desc) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex space-x-2 pt-2 border-t border-slate-700">
            <button onclick="IxeaComponents.closeModal()" class="w-1/3 bg-slate-600 hover:bg-slate-500 text-white font-bold py-2.5 rounded-xl text-sm transition">
                Cancelar
            </button>
            <button onclick="BistroTillApp.confirmMovement()" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl text-sm shadow-lg transition">
                Guardar
            </button>
        </div>
    </div>
</template>