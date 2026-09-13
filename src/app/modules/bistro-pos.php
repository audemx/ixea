<?php
/** modules/bistro-pos.php **/
?>
<div class="bg-slate-900 text-slate-100 font-sans h-screen flex flex-col select-none overflow-hidden relative">

    <!-- HEADER PRINCIPAL -->
    <header class="bg-slate-800 border-b border-slate-700 px-4 py-2 flex justify-between items-center h-14 flex-shrink-0 z-10">
        <div class="flex items-center space-x-3 overflow-hidden mr-2">
            <!-- Mesas -->
            <button onclick="BistroPosApp.toggleTablesDrawer()"
                class="bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs shadow flex items-center space-x-1.5 transition flex-shrink-0">
                <span id="bistro-pos-table">Seleccionar Mesa</span>
            </button>

            <!-- Comensales -->
            <div id="bistro-pos-people" class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none py-0.5"></div>
        </div>

        <!-- Mesero -->
        <div class="flex items-center space-x-2 text-xs flex-shrink-0">
            <button onclick="IxeaComponents.openModal('auth', { backdrop: 'md',width: '80' })"
                class="flex items-center space-x-2 bg-slate-900 hover:bg-slate-700 border border-slate-700 px-3 py-1 rounded-xl transition">
                <span id="bistro-pos-user" class="font-semibold text-slate-200 hidden sm:inline"><?php echo $currentUser['userName'] ?></span>
            </button>
        </div>
    </header>

    <!-- MAIN -->
    <div class="flex-1 flex overflow-hidden">

        <!-- COLUMNA IZQUIERDA: MENÚ -->
        <section class="w-8/12 p-3 flex flex-col bg-slate-900 border-r border-slate-800">
            <!-- Categorias -->
            <div id="bistro-pos-categories" class="flex space-x-2 overflow-x-auto pb-2 mb-2 text-sm scrollbar-none flex-shrink-0">
                <!-- Se renderiza dinámicamente con BistroPosApp.renderCategories() -->
            </div>

            <!-- Menú -->
            <div id="bistro-pos-menu" class="grid grid-cols-3 gap-2.5 overflow-y-auto pr-1 flex-1">
                <!-- Se renderiza dinámicamente con BistroPosApp.renderMenu() -->
            </div>
        </section>

        <!-- COLUMNA DERECHA: COMANDA -->
        <section class="w-4/12 bg-slate-800/90 flex flex-col">
            <div id="bistro-pos-cart" class="flex-1 overflow-y-auto p-3 space-y-2">
                <!-- Se renderiza dinámicamente con BistroPosApp.renderCart() -->
            </div>

            <div class="p-3 bg-slate-900 border-t border-slate-700 space-y-2">
                <div class="flex justify-between items-center text-sm font-bold">
                    <span class="text-slate-400">Total Orden</span>
                    <span id="bistro-pos-amount" class="text-emerald-400 text-xl">$0.00</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button onclick="IxeaComponents.openModal('bill', {backdrop: 'md', maxw: 'sm', flex: 'col'})"
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
        <div class="p-4 border-b border-slate-700 flex justify-between items-center bg-slate-900">
            <div>
                <h2 class="font-bold text-white text-base">Área de Atención</h2>
                <p class="text-xs text-slate-400">Selecciona o cambia de mesa</p>
            </div>
            <button onclick="BistroPosApp.toggleTablesDrawer()" class="text-slate-400 hover:text-white font-bold text-xl px-2">✕</button>
        </div>

        <div class="p-3 bg-slate-800/50 border-b border-slate-700/50 flex justify-around text-xs text-slate-400">
            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span>Libre</span>
            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-1.5"></span>Ocupada</span>
            <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-1.5"></span>Cuenta</span>
        </div>

        <div id="bistro-pos-tables" class="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-3">
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

    <div id="modal-split-options" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl w-full max-w-sm p-5 text-center shadow-2xl flex flex-col items-center">
            <h3 class="font-bold text-base text-white mb-1">Dividir Cuenta</h3>
            <p class="text-xs text-slate-400 mb-4">Selecciona el método de división</p>

            <div class="w-full space-y-2.5 mb-5">
                <button onclick="BistroPosApp.processBill('split-person')"
                    class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold p-3 rounded-xl text-xs border border-slate-700 text-left flex items-center justify-between transition">
                    <div>
                        <div class="font-bold text-slate-200">Por Comensal Asignado</div>
                        <div class="text-[10px] text-slate-400 font-normal">Separar según (P1, P2, P3, ...)</div>
                    </div>
                    <span>➔</span>
                </button>

                <button onclick="BistroPosApp.processBill('split-equal')"
                    class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold p-3 rounded-xl text-xs border border-slate-700 text-left flex items-center justify-between transition">
                    <div>
                        <div class="font-bold text-slate-200">En partes iguales</div>
                        <div class="text-[10px] text-slate-400 font-normal">Dividir total entre número de personas</div>
                    </div>
                    <span>➔</span>
                </button>
            </div>

            <button onclick="IxeaComponents.closeModal(); IxeaComponents.openModal('modal-bill-confirm')"
                class="text-xs text-slate-400 hover:text-white font-semibold underline">
                ⬅ Regresar a confirmación
            </button>
        </div>
    </div>

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
        <div id="bistro-pos-modifier-content" class="px-2 py-2 overflow-y-auto space-y-5 flex-1 min-h-0 text-center">
            <!-- Grupos y Modificadores -->
        </div>

        <!-- Notas para cocina (Estático) -->
        <div class="px-2 pb-2 border-t border-slate-800 shrink-0">
            <input type="text" id="bistro-pos-modifier-notes" placeholder="Nota para cocina..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-indigo-500">
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

<!-- Modal de cambio de mesero -->
<template id="bistro-pos-template-auth">
    <h3 class="font-bold text-lg text-white mb-1">Cambiar Mesero</h3>
    <p class="text-xs text-slate-400 mb-4">Ingresa tu PIN de 4 dígitos</p>

    <div class="flex justify-center space-x-3 mb-6">
        <div class="w-3.5 h-3.5 rounded-full bg-indigo-500"></div>
        <div class="w-3.5 h-3.5 rounded-full bg-indigo-500"></div>
        <div class="w-3.5 h-3.5 rounded-full bg-slate-700"></div>
        <div class="w-3.5 h-3.5 rounded-full bg-slate-700"></div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-2">
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">1</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">2</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">3</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">4</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">5</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">6</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">7</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">8</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">9</button>
        <button onclick="IxeaComponents.closeModal()" class="bg-rose-500/20 text-rose-300 py-3 rounded-xl font-bold text-xs border border-rose-500/30">Cancel</button>
        <button class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">0</button>
        <button class="bg-slate-700 text-slate-300 py-3 rounded-xl font-bold text-sm">⌫</button>
    </div>
</template>

<!-- Modal de confirmación de cuenta -->
<template id="bistro-pos-template-bill">
    <h3 class="font-bold text-lg text-white mb-1">Confirmar Solicitud de Cuenta</h3>

    <p class="text-xs text-slate-300 mb-4 font-semibold">¿Cómo desea emitir la cuenta?</p>

    <div class="w-full space-y-2.5 mb-5">
        <button onclick="BistroPosApp.processBill('global')"
            class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs border border-indigo-400 shadow transition flex items-center justify-center space-x-2">
            <span>📄</span>
            <span>Cuenta Global (Un solo ticket)</span>
        </button>

        <button onclick="BistroPosApp.openSplitOptions()"
            class="w-full bg-slate-900 hover:bg-slate-700 active:bg-slate-800 text-amber-300 font-bold py-3 rounded-xl text-xs border border-amber-500/40 transition flex items-center justify-center space-x-2">
            <span>👥</span>
            <span>Dividir Cuentas (Por Personas)</span>
        </button>
    </div>

    <button onclick="IxeaComponents.closeModal()" class="text-xs text-slate-400 hover:text-white font-semibold underline">
        Cancelar y continuar pidiendo
    </button>
</template>