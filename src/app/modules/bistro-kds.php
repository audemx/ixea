<?php
/** modules/bistro-kds.php **/
?>
<div class="bg-slate-900 text-slate-100 font-sans h-screen flex flex-col select-none overflow-hidden relative">
    <!-- ========================================== -->
    <!-- 1. HEADER PRINCIPAL                       -->
    <!-- ========================================== -->
    <header class="bg-slate-800 border-b border-slate-700 px-4 py-2 flex justify-between items-center h-14 flex-shrink-0 z-10">
        <!-- Selector de Estado de Entrega (Pendientes / Entregadas) -->
        <div class="flex items-center space-x-3">
            <div class="flex items-center bg-slate-900 p-1 rounded-xl border border-slate-700 text-xs">
                <button onclick="BistroKdsApp.setDeliveryStatus('pending')" id="bistro-kds-get-pending" class="px-3 py-1 rounded-lg font-bold bg-indigo-600 text-white shadow">
                    Pendientes
                </button>
                <button onclick="BistroKdsApp.setDeliveryStatus('delivered')" id="bistro-kds-get-delivered" class="px-3 py-1 rounded-lg font-semibold text-slate-400 hover:text-white transition">
                    Entregadas
                </button>
            </div>
        </div>

        <!-- Indicador de sincronización -->
        <div id="kds-sync-countdown" class="hidden">
            <!-- Renderizado dinamico por bistro-kds.js -->
        </div>

        <!-- Indicadores KPIs Triples (Item | Persona | Pedido) -->
        <div class="flex items-center space-x-3 text-xs flex-shrink-0">
            <!-- KPI 1: Conteo Abiertos / Despachados -->
            <div class="flex items-center space-x-2 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-700 text-slate-300">
                <span>👩🏻‍🍳 <span id="bistro-kds-kpi-count-label">Por cerrar</span>: <strong class="text-indigo-400 font-mono" id="bistro-kds-kpi-counts">0 | 0 | 0</strong></span>
            </div>

            <!-- KPI 2: Tiempo Promedio -->
            <div class="hidden sm:flex items-center space-x-2 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-700 text-slate-300">
                <span>⏱️ Promedio: <strong class="text-emerald-400 font-mono" id="bistro-kds-kpi-avg">⏱️ 0m</strong></span>
            </div>
        </div>
    </header>

    <!-- ========================================== -->
    <!-- 2. ÁREA PRINCIPAL DE CONTENIDO             -->
    <!-- ========================================== -->
    <main class="flex-1 overflow-hidden p-3 relative flex flex-col">
        <!-- Filtros de Estaciones -->
        <div id="bistro-kds-stations" class="flex space-x-2 overflow-x-auto pb-2 mb-2 text-sm scrollbar-none flex-shrink-0">
            <!-- Se renderiza dinámicamente con BistroKdsApp.renderStations() -->
        </div>
        
        <!-- VISTA DE TARJETAS CON SCROLL HORIZONTAL PURO -->
        <div id="bistro-kds-cards" class="flex-1 flex space-x-3 overflow-x-auto overflow-y-hidden pb-2 scrollbar-thin items-stretch">
            <!-- Se renderizan dinámicamente mediante BistroKdsApp.renderOrderCards() -->
        </div>
    </main>
</div>

<!-- Template para Tarjeta de Pedido (Order) -->
<template id="bistro-kds-template-order">
    <div class="kds-order-card bg-slate-800 border-2 border-slate-700 rounded-2xl flex flex-col h-full overflow-hidden shadow-2xl relative w-[240px] sm:w-[260px] shrink-0 transition-all">
        <!-- Encabezado Estático de la Orden -->
        <div class="kds-order-header p-1.5 bg-slate-850 border-b border-slate-700 flex justify-between items-center shrink-0 cursor-grab select-none relative overflow-hidden">
            <div class="swipe-indicator absolute inset-0 bg-transparent transition-colors pointer-events-none z-10 flex items-center justify-between px-3 font-bold text-xs text-white"></div>
            
            <div class="relative z-20">
                <div class="flex items-center space-x-2">
                    <span class="kds-order-channel-badge text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-700 text-indigo-300"></span>
                    <span class="kds-order-name font-black text-base text-white"></span>
                </div>
                <div class="text-[11px] text-slate-400 font-medium flex space-x-2 ps-1 mt-1.5 rounded-lg border border-slate-700">
                    <span>👥 <strong class="kds-order-pcount text-slate-200">0</strong> p.</span>
                    <span>🍴 <strong class="kds-order-icount text-slate-200">0</strong> ítems</span>
                </div>
            </div>

            <div class="text-right relative z-20 items-center bg-slate-900 p-1 rounded-xl border border-slate-700">
                <div class="kds-order-timer font-mono font-bold">
                    --:--
                </div>
                <div class="kds-order-remaining text-[10px] font-mono font-bold mt-1">--</div>
            </div>
        </div>

        <!-- Cuerpo de Personas con Scroll Interno Independiente -->
        <div class="kds-order-body flex-1 overflow-y-auto p-2 space-y-3 min-h-0">
            <!-- Subtarjetas de Personas se inyectan aquí -->
        </div>

        <!-- Panel Flotante Estático de Confirmación de Acciones (Oculto por defecto) -->
        <div class="kds-order-actions-bar hidden p-2 bg-slate-900 border-t border-slate-700 flex items-center justify-between shrink-0 z-30">
            <span class="kds-actions-summary text-[11px] font-bold text-amber-400">0 marcados</span>
            <div class="flex space-x-1.5">
                <button class="btn-cancel-marked bg-rose-600 hover:bg-rose-500 text-white text-xs px-2.5 py-1.5 rounded-lg font-bold transition">
                    🗑️ Cancelar
                </button>
                <button class="btn-release-marked bg-emerald-600 hover:bg-emerald-500 text-white text-xs px-2.5 py-1.5 rounded-lg font-bold transition">
                    🚀 Liberar
                </button>
            </div>
        </div>
    </div>
</template>