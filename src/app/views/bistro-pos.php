<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>POS Tablet - Sistema Completo con Dividir Cuenta</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-900 text-slate-100 font-sans h-screen flex flex-col select-none overflow-hidden relative">

    <!-- ========================================== -->
    <!-- 1. DRAWER LATERAL DE MESAS                -->
    <!-- ========================================== -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/60 z-40 hidden backdrop-blur-sm transition-opacity"
        onclick="toggleTablesDrawer()"></div>

    <aside id="tables-drawer"
        class="fixed top-0 left-0 bottom-0 w-80 bg-slate-800 border-r border-slate-700 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col shadow-2xl">
        <div class="p-4 border-b border-slate-700 flex justify-between items-center bg-slate-900">
            <div>
                <h2 class="font-bold text-white text-base">Área de Atención</h2>
                <p class="text-xs text-slate-400">Selecciona o cambia de mesa</p>
            </div>
            <button onclick="toggleTablesDrawer()"
                class="text-slate-400 hover:text-white font-bold text-xl px-2">✕</button>
        </div>

        <div class="p-3 bg-slate-800/50 border-b border-slate-700/50 flex justify-around text-xs text-slate-400">
            <span class="flex items-center"><span
                    class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span>Libre</span>
            <span class="flex items-center"><span
                    class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-1.5"></span>Ocupada</span>
            <span class="flex items-center"><span
                    class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-1.5"></span>Cuenta</span>
        </div>

        <div class="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-3">
            <button onclick="selectTable('Mesa 01')"
                class="bg-slate-900 hover:border-emerald-500 border-2 border-emerald-500/40 p-3 rounded-xl flex flex-col items-center justify-between active:scale-95 transition">
                <span class="text-xs font-semibold text-slate-400">Mesa 01</span>
                <span class="text-3xl my-2">🪑</span>
                <span
                    class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">Libre</span>
            </button>

            <button onclick="selectTable('Mesa 02')"
                class="bg-slate-900 border-2 border-indigo-500 p-3 rounded-xl flex flex-col items-center justify-between shadow-lg active:scale-95 transition">
                <span class="text-xs font-semibold text-indigo-300">Mesa 02</span>
                <span class="text-3xl my-2">👥</span>
                <span class="text-[10px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full">Activa
                    (3)</span>
            </button>

            <button onclick="selectTable('Mesa 03')"
                class="bg-slate-900 hover:border-rose-500 border border-slate-700 p-3 rounded-xl flex flex-col items-center justify-between active:scale-95 transition">
                <span class="text-xs font-semibold text-slate-400">Mesa 03</span>
                <span class="text-3xl my-2">👥</span>
                <span class="text-[10px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full">Ocupada
                    (2)</span>
            </button>

            <button onclick="selectTable('Mesa 04')"
                class="bg-slate-900 hover:border-amber-500 border border-slate-700 p-3 rounded-xl flex flex-col items-center justify-between active:scale-95 transition">
                <span class="text-xs font-semibold text-slate-400">Mesa 04</span>
                <span class="text-3xl my-2">🧾</span>
                <span
                    class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">Cuenta</span>
            </button>
        </div>

        <!-- Footer del Drawer -->
        <div class="p-3 bg-slate-900 border-t border-slate-700">
            <button
                class="w-full bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs py-2.5 rounded-lg border border-slate-700 font-semibold">
                ➕ Abrir Mesa Temporal / Delivery
            </button>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- 2. MODAL DE MODIFICADORES CON ELIMINAR     -->
    <!-- ========================================== -->
    <div id="modal-modifiers"
        class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div
            class="bg-slate-800 border border-slate-700 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div class="p-4 bg-slate-900 border-b border-slate-700 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg text-white">🐟 Pescado Frito</h3>
                    <p class="text-xs text-emerald-400 font-semibold">Editar orden activa</p>
                </div>
                <button onclick="closeModal('modal-modifiers')"
                    class="text-slate-400 hover:text-white font-bold text-xl px-2">✕</button>
            </div>

            <div class="p-4 overflow-y-auto space-y-4 flex-1">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Término del
                        pescado</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            class="py-2.5 px-3 bg-indigo-600 text-white font-bold text-xs rounded-xl border border-indigo-500">Medio</button>
                        <button
                            class="py-2.5 px-3 bg-slate-900 text-slate-300 font-bold text-xs rounded-xl border border-slate-700">3/4</button>
                        <button
                            class="py-2.5 px-3 bg-slate-900 text-slate-300 font-bold text-xs rounded-xl border border-slate-700">Bien
                            Cocido</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Remover
                        ingredientes</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            class="py-2.5 px-3 bg-rose-500/20 text-rose-300 font-bold text-xs rounded-xl border border-rose-500/40 text-left">🚫
                            Sin Cebolla</button>
                        <button
                            class="py-2.5 px-3 bg-slate-900 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 text-left">🚫
                            Sin Tomate</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Agregar
                        Extras</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            class="py-2.5 px-3 bg-indigo-600/30 text-indigo-200 font-bold text-xs rounded-xl border border-indigo-500 text-left flex justify-between">
                            <span>🍟 Papas Fritas</span>
                            <span class="text-emerald-400">+$1.50</span>
                        </button>
                        <button
                            class="py-2.5 px-3 bg-slate-900 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 text-left flex justify-between">
                            <span>🥗 Ensalada</span>
                            <span class="text-emerald-400">+$2.00</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nota para
                        cocina</label>
                    <input type="text" placeholder="Ej. Salsa aparte..."
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="p-4 bg-slate-900 border-t border-slate-700 flex space-x-2">
                <button onclick="closeModal('modal-modifiers')"
                    class="w-1/3 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-bold py-3 rounded-xl text-xs transition flex items-center justify-center space-x-1">
                    <span>🗑️</span>
                    <span>Eliminar</span>
                </button>
                <button onclick="closeModal('modal-modifiers')"
                    class="w-2/3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl text-xs shadow-lg">Guardar
                    Cambios ($14.00)</button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. MODAL DE CAMBIO DE USUARIO (PIN)        -->
    <!-- ========================================== -->
    <div id="modal-pin"
        class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl w-80 p-5 text-center shadow-2xl">
            <h3 class="font-bold text-lg text-white mb-1">Cambiar Mesero</h3>
            <p class="text-xs text-slate-400 mb-4">Ingresa tu PIN de 4 dígitos</p>

            <div class="flex justify-center space-x-3 mb-6">
                <div class="w-3.5 h-3.5 rounded-full bg-indigo-500"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-indigo-500"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-slate-700"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-slate-700"></div>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-2">
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">1</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">2</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">3</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">4</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">5</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">6</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">7</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">8</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">9</button>
                <button onclick="closeModal('modal-pin')"
                    class="bg-rose-500/20 text-rose-300 py-3 rounded-xl font-bold text-xs border border-rose-500/30">Cancel</button>
                <button
                    class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">0</button>
                <button class="bg-slate-700 text-slate-300 py-3 rounded-xl font-bold text-sm">⌫</button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 4. NUEVO: MODAL CONFIRMACIÓN DE CUENTA    -->
    <!-- ========================================== -->
    <div id="modal-bill-confirm"
        class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-md">
        <div
            class="bg-slate-800 border border-slate-700 rounded-2xl w-full max-w-sm p-5 text-center shadow-2xl flex flex-col items-center">
            <div
                class="w-12 h-12 rounded-full bg-amber-500/20 border border-amber-500/30 text-amber-400 text-2xl flex items-center justify-center mb-3">
                🧾
            </div>
            <h3 class="font-bold text-lg text-white mb-1">Confirmar Solicitud de Cuenta</h3>
            <p class="text-xs text-slate-400 mb-5">Mesa 02 • Total: <span
                    class="text-emerald-400 font-bold">$12.50</span></p>

            <p class="text-xs text-slate-300 mb-4 font-semibold">¿Cómo desea emitir la cuenta?</p>

            <div class="w-full space-y-2.5 mb-5">
                <button onclick="processBill('global')"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs border border-indigo-400 shadow transition flex items-center justify-center space-x-2">
                    <span>📄</span>
                    <span>Cuenta Global (Un solo ticket)</span>
                </button>

                <button onclick="openSplitOptions()"
                    class="w-full bg-slate-900 hover:bg-slate-700 active:bg-slate-800 text-amber-300 font-bold py-3 rounded-xl text-xs border border-amber-500/40 transition flex items-center justify-center space-x-2">
                    <span>👥</span>
                    <span>Dividir Cuentas (Por Personas)</span>
                </button>
            </div>

            <button onclick="closeModal('modal-bill-confirm')"
                class="text-xs text-slate-400 hover:text-white font-semibold underline">
                Cancelar y continuar pidiendo
            </button>
        </div>
    </div>

    <!-- MODAL SECUNDARIO: OPCIONES DE DIVISIÓN -->
    <div id="modal-split-options"
        class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4 backdrop-blur-md">
        <div
            class="bg-slate-800 border border-slate-700 rounded-2xl w-full max-w-sm p-5 text-center shadow-2xl flex flex-col items-center">
            <h3 class="font-bold text-base text-white mb-1">Dividir Cuenta Mesa 02</h3>
            <p class="text-xs text-slate-400 mb-4">Selecciona el método de división</p>

            <div class="w-full space-y-2.5 mb-5">
                <button onclick="processBill('split-person')"
                    class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold p-3 rounded-xl text-xs border border-slate-700 text-left flex items-center justify-between transition">
                    <div>
                        <div class="font-bold text-slate-200">Por Comensal Asignado</div>
                        <div class="text-[10px] text-slate-400 font-normal">Separar según (P1, P2, P3, ...)</div>
                    </div>
                    <span>➔</span>
                </button>

                <button onclick="processBill('split-equal')"
                    class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold p-3 rounded-xl text-xs border border-slate-700 text-left flex items-center justify-between transition">
                    <div>
                        <div class="font-bold text-slate-200">En partes iguales</div>
                        <div class="text-[10px] text-slate-400 font-normal">Dividir total entre número de personas</div>
                    </div>
                    <span>➔</span>
                </button>
            </div>

            <button onclick="closeModal('modal-split-options'); openModal('modal-bill-confirm')"
                class="text-xs text-slate-400 hover:text-white font-semibold underline">
                ⬅ Regresar a confirmación
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 5. HEADER PRINCIPAL                       -->
    <!-- ========================================== -->
    <header
        class="bg-slate-800 border-b border-slate-700 px-4 py-2 flex justify-between items-center h-14 flex-shrink-0 z-10">
        <div class="flex items-center space-x-3 overflow-hidden mr-2">
            <button onclick="toggleTablesDrawer()"
                class="bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs shadow flex items-center space-x-1.5 transition flex-shrink-0">
                <span>🪑</span>
                <span id="current-table-label">Mesa 02</span>
                <span class="text-[9px] bg-indigo-800 px-1 py-0.5 rounded text-indigo-200">▼</span>
            </button>

            <div id="people-bar" class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none py-0.5">
                <button id="btn-person-center" onclick="selectPerson('center', '🍽️ Centro')"
                    class="person-btn bg-indigo-600 text-white font-bold px-2.5 py-1.5 rounded-xl text-xs whitespace-nowrap border border-indigo-400 flex items-center space-x-1 flex-shrink-0 shadow">
                    <span>🍽️ Centro</span>
                </button>

                <button id="add-person-btn" onclick="addNewPerson()"
                    class="bg-slate-900 hover:bg-slate-700 text-indigo-400 font-bold px-2.5 py-1.5 rounded-xl text-xs border border-slate-700 flex items-center space-x-1 flex-shrink-0 transition">
                    <span>+ Persona</span>
                </button>
            </div>
        </div>

        <div class="flex items-center space-x-2 text-xs flex-shrink-0">
            <span
                class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-full font-bold flex items-center">
                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>En línea
            </span>

            <button onclick="openModal('modal-pin')"
                class="flex items-center space-x-2 bg-slate-900 hover:bg-slate-700 border border-slate-700 px-3 py-1 rounded-xl transition">
                <div
                    class="w-6 h-6 rounded-full bg-indigo-500 text-white text-[11px] font-bold flex items-center justify-center">
                    JP</div>
                <span class="font-semibold text-slate-200 hidden sm:inline">Juan P.</span>
                <span class="text-slate-400 text-[10px]">🔄</span>
            </button>
        </div>
    </header>

    <!-- ========================================== -->
    <!-- 6. BASE DE 2 COLUMNAS                      -->
    <!-- ========================================== -->
    <div class="flex-1 flex overflow-hidden">

        <!-- COLUMNA IZQUIERDA: MENÚ -->
        <section class="w-7/12 p-3 flex flex-col bg-slate-900 border-r border-slate-800">
            <div class="flex space-x-2 overflow-x-auto pb-2 mb-2 text-sm scrollbar-none flex-shrink-0">
                <button class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold whitespace-nowrap shadow">🔥
                    Populares</button>
                <button
                    class="bg-slate-800 text-slate-200 px-5 py-2.5 rounded-xl font-bold whitespace-nowrap border border-slate-700">🍲
                    Platos Fuertes</button>
                <button
                    class="bg-slate-800 text-slate-200 px-5 py-2.5 rounded-xl font-bold whitespace-nowrap border border-slate-700">🥤
                    Bebidas</button>
            </div>

            <div class="grid grid-cols-2 gap-2.5 overflow-y-auto pr-1 flex-1">
                <div onclick="addDefaultProduct('Aguachile Verde', 25.50)"
                    class="bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex flex-col justify-between active:scale-95 transition cursor-pointer hover:border-emerald-500">
                    <div class="flex items-start justify-between">
                        <h2 class="font-bold text-lg text-slate-100">🐠 Aguachile Verde</h2>
                        <span class="font-black text-emerald-400 text-base">$25.50</span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-slate-400">Tocar para agregar predeterminado</p>
                    </div>
                </div>

                <div onclick="addDefaultProduct('Camarones Zarandeados', 15.50)"
                    class="bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex flex-col justify-between active:scale-95 transition cursor-pointer hover:border-emerald-500">
                    <div class="flex items-start justify-between">
                        <h2 class="font-bold text-lg text-slate-100">🍤 Camarones Zarandeados</h2>
                        <span class="font-black text-emerald-400 text-base">$15.50</span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-slate-400">Tocar para agregar predeterminado</p>
                    </div>
                </div>

                <div onclick="addDefaultProduct('Pescado Frito', 35.50)"
                    class="bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex flex-col justify-between active:scale-95 transition cursor-pointer hover:border-emerald-500">
                    <div class="flex items-start justify-between">
                        <h2 class="font-bold text-lg text-slate-100">🐟 Pescado Frito</h2>
                        <span class="font-black text-emerald-400 text-base">$35.50</span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-slate-400">Tocar para agregar predeterminado</p>
                    </div>
                </div>

                <div onclick="addDefaultProduct('Refresco 500ml', 2.50)"
                    class="bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex flex-col justify-between active:scale-95 transition cursor-pointer hover:border-emerald-500">
                    <div class="flex items-start justify-between">
                        <h2 class="font-bold text-lg text-slate-100">🥤 Refresco 500ml</h2>
                        <span class="font-black text-emerald-400 text-base">$2.50</span>
                    </div>
                    <div class="mt-2">

                        <p class="text-xs text-slate-400">Tocar para agregar predeterminado</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- COLUMNA DERECHA: RESUMEN / COMANDA -->
        <section class="w-5/12 bg-slate-800/90 flex flex-col">
            <div id="cart-list" class="flex-1 overflow-y-auto p-3 space-y-2">
                <div onclick="openModal('modal-modifiers')"
                    class="bg-slate-900/90 border border-slate-700 p-3 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-sm text-slate-200">1x Pescado Frito</h4>
                        <span class="font-bold text-emerald-400 text-sm">$35.50</span>
                    </div>
                    <div class="flex justify-between items-center mt-1.5">
                        <p class="text-xs text-slate-400">Opción Estándar</p>
                        <span
                            class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-md font-semibold">🍽️
                            Centro</span>
                    </div>
                </div>
            </div>

            <!-- BOTONES FINALES CON TRIGGER DE PEDIR CUENTA -->
            <div class="p-3 bg-slate-900 border-t border-slate-700 space-y-2">
                <div class="flex justify-between items-center text-sm font-bold">
                    <span class="text-slate-400">Total Orden</span>
                    <span class="text-emerald-400 text-xl">$12.50</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button onclick="openModal('modal-bill-confirm')"
                        class="bg-amber-600 hover:bg-amber-500 active:bg-amber-700 text-white font-bold py-3 rounded-xl text-xs shadow flex items-center justify-center space-x-1">
                        <span>🧾</span>
                        <span>Pedir Cuenta</span>
                    </button>
                    <button
                        class="bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold py-3 rounded-xl text-xs shadow flex items-center justify-center space-x-1">
                        <span>👨🏻‍🍳</span>
                        <span>Enviar Cocina</span>
                    </button>
                </div>
            </div>
        </section>

    </div>

    <!-- SCRIPT INTEGRADO -->
    <script>
        let currentTargetPerson = '🍽️ Centro';
        let personCount = 0;

        function toggleTablesDrawer() {
            const drawer = document.getElementById('tables-drawer');
            const overlay = document.getElementById('drawer-overlay');
            drawer.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function selectTable(tableName) {
            document.getElementById('current-table-label').innerText = tableName;
            toggleTablesDrawer();
        }

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function openSplitOptions() {
            closeModal('modal-bill-confirm');
            openModal('modal-split-options');
        }

        function processBill(type) {
            closeModal('modal-bill-confirm');
            closeModal('modal-split-options');

            if (type === 'global') {
                alert('🧾 Impresora: Generando Pre-cuenta Global para Mesa 02');
            } else if (type === 'split-person') {
                alert('🧾 Impresora: Generando Cuentas Separadas por Comensal');
            } else if (type === 'split-equal') {
                alert('🧾 Impresora: Generando Cuentas Divididas en Partes Iguales');
            }
        }

        function selectPerson(id, label) {
            currentTargetPerson = label;

            document.querySelectorAll('.person-btn').forEach(btn => {
                btn.className = 'person-btn bg-slate-900 hover:bg-slate-700 text-slate-300 font-semibold px-2.5 py-1.5 rounded-xl text-xs border border-slate-700 flex items-center space-x-1 flex-shrink-0 transition';
            });

            const activeBtn = document.getElementById(`btn-person-${id}`);
            if (activeBtn) {
                activeBtn.className = 'person-btn bg-indigo-600 text-white font-bold px-2.5 py-1.5 rounded-xl text-xs border border-indigo-400 flex items-center space-x-1 flex-shrink-0 shadow';
            }
        }

        function addNewPerson() {
            personCount++;
            const personId = `p${personCount}`;
            const personLabel = `👤 P${personCount}`;

            const addBtn = document.getElementById('add-person-btn');
            const newPersonBtn = document.createElement('button');
            newPersonBtn.id = `btn-person-${personId}`;
            newPersonBtn.onclick = () => selectPerson(personId, personLabel);
            newPersonBtn.className = 'person-btn bg-slate-900 hover:bg-slate-700 text-slate-300 font-semibold px-2.5 py-1.5 rounded-xl text-xs border border-slate-700 flex items-center space-x-1 flex-shrink-0 transition';
            newPersonBtn.innerHTML = `<span>${personLabel}</span>`;

            addBtn.parentNode.insertBefore(newPersonBtn, addBtn);
            selectPerson(personId, personLabel);
        }

        function addDefaultProduct(name, price) {
            const list = document.getElementById('cart-list');
            const itemHTML = `
        <div onclick="openModal('modal-modifiers')" class="bg-slate-900/90 border border-slate-700 p-3 rounded-xl cursor-pointer hover:border-indigo-500 transition">
          <div class="flex justify-between items-start">
            <h4 class="font-bold text-sm text-slate-200">1x ${name}</h4>
            <span class="font-bold text-emerald-400 text-sm">$${price.toFixed(2)}</span>
          </div>
          <div class="flex justify-between items-center mt-1.5">
            <p class="text-xs text-slate-400">Opción Estándar</p>
            <span class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-md font-semibold">${currentTargetPerson}</span>
          </div>
        </div>`;
            list.insertAdjacentHTML('beforeend', itemHTML);
        }
    </script>

</body>

</html>