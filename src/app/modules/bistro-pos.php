<?php
/** modules/bistro-pos.php **/

use App\Models\Channel;
use App\Enums\Status;

$channels = Channel::where('status_id', Status::ACTIVE)
    ->get();
?>
<div class="bg-slate-900 text-slate-100 font-sans h-screen flex flex-col select-none overflow-hidden relative">

    <!-- HEADER PRINCIPAL -->
    <header class="bg-slate-800 border-b border-slate-700 px-4 py-2 flex justify-between items-center h-14 flex-shrink-0 z-10">
        <div class="flex items-center space-x-3 overflow-hidden mr-2">
            <!-- Mesas -->
            <button onclick="BistroPosApp.toggleTablesDrawer()"
                class="bg-slate-800 hover:bg-slate-700 active:bg-slate-900 text-white font-bold px-3 py-1.5 border border-slate-700 rounded-xl text-xs shadow flex items-center space-x-1.5 transition flex-shrink-0">
                <span id="bistro-pos-table">Seleccionar Mesa</span>
            </button>

            <!-- Comensales -->
            <div id="bistro-pos-people" class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none py-0.5"></div>
        </div>

        <!-- Mesero -->
        <div class="flex items-center space-x-2 text-xs flex-shrink-0">
            <button onclick="BistroPosApp.switchWaiter()"
                class="flex items-center space-x-2 bg-slate-900 hover:bg-slate-700 border border-slate-700 px-3 py-1 rounded-xl transition">
                <span id="bistro-pos-user" class="font-semibold text-slate-200 hidden sm:inline"><?php echo $currentUser['userName'] ?></span>
            </button>
        </div>
    </header>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col md:flex-row overflow-hidden">

        <!-- COLUMNA IZQUIERDA: MENÚ (O fila superior en vertical) -->
        <section class="w-full md:w-8/12 p-3 flex flex-col bg-slate-900 border-b md:border-b-0 md:border-r border-slate-800 flex-1 md:flex-initial">
            <!-- Categorias -->
            <div id="bistro-pos-categories" class="flex space-x-2 overflow-x-auto pb-2 mb-2 text-sm scrollbar-none flex-shrink-0">
                <!-- Se renderiza dinámicamente con BistroPosApp.renderCategories() -->
            </div>

            <!-- Menú -->
            <div id="bistro-pos-menu" class="grid grid-cols-3 gap-2.5 overflow-y-auto pr-1 flex-1">
                <!-- Se renderiza dinámicamente con BistroPosApp.renderMenu() -->
            </div>
        </section>

        <!-- COLUMNA DERECHA: COMANDA (O fila inferior en vertical) -->
        <section class="w-full md:w-4/12 bg-slate-800/90 flex flex-col h-1/2 md:h-auto">
            <div id="bistro-pos-cart" class="flex-1 overflow-y-auto p-3 space-y-2">
                <div class="h-full flex flex-col items-center justify-center text-slate-500 py-10">
                    <span class="text-4xl mb-2">🍽️</span>
                    <p class="text-xs font-semibold">Comandera sin ordenes</p>
                </div>
            </div>

            <div class="px-3 py-2 bg-slate-900 border-t border-slate-700 space-y-2">
                <div class="flex justify-between items-center text-sm font-bold hidden">
                    <span class="text-slate-400">Total Orden</span>
                    <span id="bistro-pos-amount" class="text-emerald-400 text-xl">$0.00</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button onclick="BistroPosApp.requestBill()"
                        class="bg-amber-600 hover:bg-amber-500 active:bg-amber-700 text-white font-bold py-3 rounded-xl text-xs shadow flex items-center justify-center space-x-1">
                        <span>🧾</span>
                        <span>Pedir Cuenta</span>
                    </button>
                    <button onclick="BistroPosApp.sendToKitchen()"
                        class="bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs shadow flex items-center justify-center space-x-1">
                        <span>👨🏻‍🍳</span>
                        <span>Enviar Cocina</span>
                    </button>
                </div>
            </div>
        </section>

    </div>

    <!-- DRAWER LATERAL DE MESAS -->
    <div id="bistro-pos-drawer-overlay" class="fixed inset-0 bg-black/60 z-40 hidden backdrop-blur-sm transition-opacity"
        onclick="BistroPosApp.toggleTablesDrawer()"></div>

    <aside id="bistro-pos-drawer"
        class="fixed top-0 left-0 bottom-0 w-80 bg-slate-800 border-r border-slate-700 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col shadow-2xl">
        <div class="px-4 py-2 border-b border-slate-700 flex justify-between items-center bg-slate-900">
            <div>
                <h2 class="font-bold text-white text-base">Área de Atención</h2>
                <p class="text-xs text-slate-400">Selecciona o cambia de mesa</p>
            </div>
            <button onclick="BistroPosApp.toggleTablesDrawer()" class="text-slate-400 hover:text-white font-bold text-xl px-2">✕</button>
        </div>

        <div id="bistro-pos-areas" class="flex space-x-2 overflow-x-auto p-2 text-sm scrollbar-none flex-shrink-0 border-b border-slate-700/50">
            </div>

        <div class="px-2 py-2 bg-slate-800/50 flex justify-around text-xs text-slate-400">
            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span>Libre</span>
            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-1.5"></span>Ocupada</span>
            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-1.5"></span>Cuenta</span>
        </div>

        <div id="bistro-pos-tables" class="flex-1 overflow-y-auto px-4 grid grid-cols-2 gap-3">
            </div>

        <div class="p-3 bg-slate-900 border-t border-slate-700">
            <button onclick="BistroPosApp.openTemporaryTable()"
            class="w-full bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs py-2.5 rounded-lg border border-slate-700 font-semibold">
                ➕ Abrir Mesa Temporal / Delivery
            </button>
        </div>
    </aside>

    <!-- Modal -->
    <div id="bistro-pos-modal"></div>

</div>

<!-- Template de mesa -->
<template id="bistro-pos-template-table">
    <button class="bistro-pos-table-btn bg-slate-900 border-2 p-3 rounded-xl flex flex-col items-center justify-between active:scale-95 transition">
        <span class="bistro-pos-table-name text-xs font-semibold text-slate-400"></span>
        <span class="bistro-pos-table-icon text-3xl my-2"></span>
        <span class="bistro-pos-table-status-badge text-[10px] font-bold px-2 py-0.5 rounded-full"></span>
    </button>
</template>

<!-- Modal de modificadores -->
<template id="bistro-pos-template-modifiers">
    <div class="flex flex-col w-full h-full max-h-[80vh]">
        <!-- Encabezado Estático -->
        <div class="px-3 border-b border-slate-700 flex justify-between items-center shrink-0">
            <h3 id="bistro-pos-modifier-title" class="font-bold text-lg text-white">---</h3>
        </div>

        <!-- Contenido Dinámico con Scroll Interno -->
        <div class="px-2 py-2 overflow-y-auto text-center">
            <!-- Grupos y Modificadores -->
            <div id="bistro-pos-modifier-content" class="mb-2 space-y-3 flex-1"></div>

            <!-- Notas para cocina (Estático) -->
            <div class="px-2 py-2 border-t border-slate-700 shrink-0">
                <input type="text" id="bistro-pos-modifier-notes" placeholder="Nota para cocina..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>
        </div>

        <!-- Botones de Acción Estáticos -->
        <div class="px-2 pt-2 border-t border-slate-700 flex space-x-2 shrink-0">
            <button onclick="IxeaComponents.closeModal()" class="w-1/3 bg-slate-600 hover:bg-slate-500 text-white font-bold mx-3 py-2.5 rounded-xl text-sm transition">
                Cancelar
            </button>
            <button onclick="BistroPosApp.saveModifiers()" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white font-bold mx-3 py-2.5 rounded-xl text-sm shadow-lg transition">
                Aceptar
            </button>
        </div>
    </div>
</template>

<!-- Modal de confirmación de cuenta -->
<template id="bistro-pos-template-bill">
    <h3 class="font-bold text-lg text-white mb-1">Confirmar Solicitud de Cuenta</h3>
    <p class="text-xs text-slate-300 mb-4 font-semibold">¿Cómo desea emitir la cuenta?</p>

    <div class="w-full space-y-2.5 mb-4">
        <div id="bistro-pos-split-global">
            <button onclick="BistroPosApp.confirmBillRequest('global')"
                class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs border border-indigo-400 shadow transition flex items-center justify-center space-x-2">
                <span>📄</span>
                <span>Cuenta Global</span>
            </button>
            <p class="text-xs text-slate-400 mb-4 font-semibold">Un ticket por mesa.</p>
        </div>

        <div id="bistro-pos-split-equal">
            <button onclick="BistroPosApp.openSplitOptions()"
                class="w-full bg-slate-900 hover:bg-slate-700 active:bg-slate-800 text-amber-300 font-bold py-3 rounded-xl text-xs border border-amber-500/40 transition flex items-center justify-center space-x-2">
                <span>👥</span>
                <span>Dividir Cuentas</span>
            </button>
            <p class="text-xs text-slate-400 mb-4 font-semibold">Un ticket por persona.</p>
        </div>
    </div>

    <button onclick="IxeaComponents.closeModal()"
    class="w-50 text-xs bg-slate-500 hover:bg-slate-400 border border-slate-700 transition text-white py-2 rounded-xl">
        Cancelar
    </button>
</template>

<!-- Modal de selección de método de división de cuenta -->
<template id="bistro-pos-template-bill-split">
    <h3 class="font-bold text-lg text-white mb-1">Dividir Cuenta</h3>
    <p class="text-xs text-slate-300 mb-4 font-semibold">Selecciona el método de división</p>

    <div class="w-full space-y-2.5">
        <button onclick="BistroPosApp.confirmBillRequest('split-person')"
            class="mb-4 w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs border border-indigo-400 shadow transition flex items-center justify-center space-x-2">
            Por Comensal
        </button>

        <button onclick="BistroPosApp.confirmBillRequest('split-equal')"
            class="mb-4 w-full bg-slate-900 hover:bg-slate-700 active:bg-slate-800 text-amber-300 font-bold py-3 rounded-xl text-xs border border-amber-500/40 transition flex items-center justify-center space-x-2">
            En partes iguales
        </button>
    </div>

    <button onclick="BistroPosApp.requestBill()"
        class="w-50 text-xs bg-slate-500 hover:bg-slate-400 border border-slate-700 transition text-white py-2 rounded-xl">
        Regresar
    </button>
</template>

<!-- Modal para abrir cuenta temporal / delivery -->
<template id="bistro-pos-template-order">
    <div class="flex flex-col w-full h-full max-h-[80vh]">
        <h3 class="px-3 pb-3 border-b border-slate-700 font-bold text-lg text-white">Abrir Cuenta Temporal</h3>

        <div class="py-3 overflow-y-auto space-y-4 flex-1 px-1">
            <!-- Canales -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Canal de Atención</label>
                <!-- Contenedor scrollable con grid para métodos de pago -->
                <div class="w-full overflow-x-auto py-2 scrollbar-thin scrollbar-thumb-slate-700">
                    <div class="grid grid-flow-col grid-rows-1 gap-2 auto-cols-[calc(25%-6px)]" id="bistro-pos-channels">
                    <?php 
                    foreach ($channels as $channel): 
                        $icon = !empty($channel->icon) ? $channel->icon : 'bi-shop text-slate-400';
                    ?>
                        <button type="button" 
                                data-id="<?= $channel->id ?>" 
                                data-name="<?= $channel->name ?>"
                                onclick="BistroPosApp.selectChannel(this)"
                                class="channel-btn flex flex-col items-center justify-center p-3 bg-slate-900 border border-slate-700 hover:border-blue-500 rounded-xl transition cursor-pointer group h-24">
                            <i class="bi <?= $icon ?> text-2xl mb-1 group-hover:scale-110 transition-transform"></i>
                            <span class="text-[11px] font-bold text-slate-300 group-hover:text-white text-center line-clamp-1"><?= htmlspecialchars($channel->name) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Nombre del Cliente -->
            <div class="text-center mt-4">
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Nombre del Cliente</label>
                <input type="text" id="bistro-pos-customer-name" placeholder="Ej. Juan Pérez" autocomplete="off" 
                    class="w-[50%] text-center bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>
        </div>

        <div class="pt-3 border-t border-slate-700 flex space-x-2 shrink-0">
            <button onclick="IxeaComponents.closeModal()" class="w-1/2 bg-slate-600 hover:bg-slate-500 text-white font-bold py-2.5 rounded-xl text-sm transition">
                Cancelar
            </button>
            <button onclick="BistroPosApp.confirmTemporaryOrder()" class="w-1/2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl text-sm shadow-lg transition">
                Confirmar
            </button>
        </div>
    </div>
</template>

<!-- Modal de Edición de Orden Activa -->
<template id="bistro-pos-template-order-edit">
    <div class="flex flex-col w-full h-full max-h-[85vh] text-slate-100 font-sans">
        <!-- Encabezado con datos generales y descuento global -->
        <div class="px-4 py-2 border border-slate-700 rounded-xl bg-slate-900/50 flex justify-between items-center space-x-4">
            <div>
                <h3 class="font-extrabold text-lg text-white" id="bistro-pos-order-title">Editar Orden</h3>
                <p class="text-xs text-slate-400" id="bistro-pos-order-subtitle">Folio / Mesa / Canal</p>
            </div>
            
            <!-- Controles Financieros: Total general arriba del descuento global -->
            <div class="flex flex-col items-end space-y-1">
                <div class="flex items-center space-x-1.5">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Total:</span>
                    <h4 id="bistro-pos-order-total" class="font-extrabold text-emerald-400 text-lg font-mono">
                        $0.00
                    </h4>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-300">Desc. Global (%)</span>
                    <input type="number" id="bistro-pos-global-discount" min="0" max="100" step="1" 
                        oninput="BistroPosApp.calculateOrderTotals()"
                        onchange="BistroPosApp.calculateOrderTotals()"
                        class="w-16 bg-slate-900 border border-slate-700 rounded-lg p-1 text-xs text-center text-amber-400 font-bold focus:outline-none focus:border-indigo-500"
                        value="0">
                </div>
            </div>
        </div>

        <!-- Lista de ítems con scroll vertical -->
        <div class="px-4 py-2 mb-4 overflow-y-auto flex-1 space-y-2.5" id="bistro-pos-order-items">
            <!-- Renderizado dinámico -->
        </div>
    </div>
</template>