<?php
/** modules/bistro-kds.php **/
?>
<div class="bg-slate-900 text-slate-100 font-sans h-screen flex flex-col select-none overflow-hidden relative">

    <!-- ========================================== -->
    <!-- 1. MODAL DETALLES DE COMANDA / NOTAS      -->
    <!-- ========================================== -->
    <div id="modal-kds-detail" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
            <div class="p-4 bg-slate-900 border-b border-slate-700 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg text-white" id="modal-order-title">Comanda</h3>
                    <p class="text-xs text-amber-400 font-semibold" id="modal-order-time">Tiempo transcurrido: -</p>
                </div>
                <button onclick="BistroKdsApp.closeModal('modal-kds-detail')" class="text-slate-400 hover:text-white font-bold text-xl px-2">✕</button>
            </div>

            <div class="p-4 overflow-y-auto space-y-3 max-h-[60vh]">
                <div class="p-3 bg-slate-900 rounded-xl border border-slate-700">
                    <p class="text-xs text-slate-400 font-bold uppercase mb-1">Mesero a cargo</p>
                    <p class="text-sm text-slate-200 font-semibold" id="modal-waiter-name">-</p>
                </div>

                <div class="p-3 bg-slate-900 rounded-xl border border-slate-700">
                    <p class="text-xs text-slate-400 font-bold uppercase mb-1">Notas Generales de la Mesa</p>
                    <p class="text-sm text-amber-300 font-medium" id="modal-order-notes">-</p>
                </div>
            </div>

            <div class="p-4 bg-slate-900 border-t border-slate-700 flex space-x-2">
                <button onclick="BistroKdsApp.closeModal('modal-kds-detail')" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl text-xs transition">
                    Entendido / Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. HEADER PRINCIPAL                       -->
    <!-- ========================================== -->
    <header class="bg-slate-800 border-b border-slate-700 px-4 py-2 flex justify-between items-center h-14 flex-shrink-0 z-10">
        <div class="flex items-center space-x-3">
            <div class="bg-amber-500/20 text-amber-400 border border-amber-500/30 font-black px-3 py-1.5 rounded-xl text-xs flex items-center space-x-2">
                <span class="text-base">👨🏻‍🍳</span>
                <span>KDS COCINA</span>
            </div>

            <!-- Filtros de Estaciones -->
            <div class="hidden md:flex items-center space-x-1 bg-slate-900 p-1 rounded-xl border border-slate-700/80 text-xs">
                <button onclick="BistroKdsApp.filterStation('all')" id="btn-filter-all" class="px-3 py-1 rounded-lg font-bold bg-indigo-600 text-white shadow">
                    Todas
                </button>
                <button onclick="BistroKdsApp.filterStation('cocina')" id="btn-filter-cocina" class="px-3 py-1 rounded-lg font-semibold text-slate-400 hover:text-white transition">
                    🍳 Calientes
                </button>
                <button onclick="BistroKdsApp.filterStation('fria')" id="btn-filter-fria" class="px-3 py-1 rounded-lg font-semibold text-slate-400 hover:text-white transition">
                    🥗 Fríos
                </button>
                <button onclick="BistroKdsApp.filterStation('bar')" id="btn-filter-bar" class="px-3 py-1 rounded-lg font-semibold text-slate-400 hover:text-white transition">
                    🥤 Bar
                </button>
            </div>
        </div>

        <div class="flex items-center space-x-3 text-xs flex-shrink-0">
            <div class="hidden sm:flex items-center space-x-2 bg-slate-900 px-3 py-1 rounded-xl border border-slate-700 text-slate-300">
                <span>⏱️ Promedio: <strong class="text-emerald-400" id="kds-avg-time">-- min</strong></span>
            </div>

            <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-full font-bold flex items-center">
                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>KDS Online
            </span>
        </div>
    </header>
</div>