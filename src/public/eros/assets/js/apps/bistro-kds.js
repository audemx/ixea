/** assets/js/apps/bistro-kds.js **/
window.BistroKdsApp = {
    refreshTimer: null,
    orders: [],
    activeFilter: 'all',
    isInitialized: false,
    pollIntervalMs: 5000, // Ajustable según la carga de red en cocina

    /**
     * Inicialización del módulo KDS
     */
    init: function () {
        if (this.isInitialized) return;
        console.log("[KDS] Inicializando pantalla de cocina...");
        this.isInitialized = true;

        this.loadOrders(); // Carga inicial
        this.startMonitor();
    },

    /**
     * Purga de recursos al cerrar la vista/módulo
     */
    destroy: function () {
        console.log("[KDS] Purgando recursos e intervalos...");
        this.stopMonitor();
        this.orders = [];
        this.isInitialized = false;
    },

    /**
     * Inicia la consulta periódica al nodo central
     */
    startMonitor: function () {
        if (!this.refreshTimer) {
            console.log('[KDS] Monitor de cocina: Iniciado');
            this.refreshTimer = setInterval(() => {
                this.loadOrders();
            }, this.pollIntervalMs);
        }
    },

    /**
     * Detiene el monitoreo en segundo plano
     */
    stopMonitor: function () {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
            console.log('[KDS] Monitor de cocina: Detenido');
        }
    },

    /**
     * Solicitud HTTP al nodo central/servidor
     */
    loadOrders: async function () {
        try {
            const res = await fetch(`/api/kds?action=get_active_orders&station=${this.activeFilter}`);
            const data = await res.json();

            if (data.success) {
                this.orders = data.orders;
                this.renderTickets();
                this.updateMetrics(data.metrics);
            }
        } catch (err) {
            console.error("[KDS] Error conectando con el nodo central:", err);
        }
    },

    /**
     * Renderiza la grilla de comanda en pantalla
     */
    renderTickets: function (ordersToRender = null) {
        const orders = ordersToRender !== null ? ordersToRender : this.orders;
        const container = document.getElementById('kds-tickets-container');
        if (!container) return;

        if (orders.length === 0) {
            container.innerHTML = `
                <div class="w-full flex flex-col items-center justify-center h-full text-slate-500">
                    <span class="text-5xl mb-3">👨🏻‍🍳</span>
                    <p class="text-sm font-semibold">No hay comandas pendientes en esta estación</p>
                </div>`;
            return;
        }

        container.innerHTML = orders.map(order => {
            const isDelayed = order.elapsed_minutes >= 15;
            const isWarning = order.elapsed_minutes >= 8 && order.elapsed_minutes < 15;

            // Determinar clases visuales según criticidad del tiempo
            let borderStyle = "border-slate-700";
            let timerColor = "text-emerald-400";
            let statusBadge = "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
            let statusText = "Nuevo";

            if (isDelayed) {
                borderStyle = "border-2 border-rose-500 shadow-rose-950/20";
                timerColor = "text-rose-400 animate-pulse";
                statusBadge = "bg-rose-500/20 text-rose-300 border-rose-500/30";
                statusText = "Retrasado";
            } else if (isWarning) {
                borderStyle = "border border-amber-500/50 shadow-amber-950/20";
                timerColor = "text-amber-400";
                statusBadge = "bg-amber-500/10 text-amber-400 border-amber-500/20";
                statusText = "En Marcha";
            }

            return `
            <div id="ticket-${order.order_id}" class="w-80 bg-slate-800 ${borderStyle} rounded-2xl flex flex-col h-full shadow-2xl flex-shrink-0 overflow-hidden transition-all duration-300">
                ${isDelayed ? `
                    <div class="bg-rose-600 px-3 py-1 text-center font-bold text-[11px] text-white animate-pulse">
                        ⚠️ MÁS DE 15 MINUTOS DE ESPERA
                    </div>
                ` : ''}

                <!-- Header de Ticket -->
                <div class="p-3 bg-slate-900 border-b border-slate-700 flex justify-between items-center">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-black text-lg text-white">${order.table_name}</span>
                            <span class="bg-slate-800 text-slate-400 text-[10px] px-2 py-0.5 rounded border border-slate-700 font-bold">#${order.folio}</span>
                        </div>
                        <p class="text-xs text-slate-400">Mesero: <strong class="text-slate-200">${order.waiter_name}</strong></p>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-black ${timerColor} block">${order.elapsed_time_formatted}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border ${statusBadge}">${statusText}</span>
                    </div>
                </div>

                <!-- Lista de Platillos -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    ${order.items.map((item, idx) => `
                        <div onclick="BistroKdsApp.toggleItemDone(this)"
                            class="p-2.5 bg-slate-900 border border-slate-700 rounded-xl cursor-pointer hover:border-indigo-500 transition group select-none ${item.completed ? 'item-done opacity-40 bg-emerald-950/30' : ''}">
                            <div class="flex items-start justify-between">
                                <span class="font-bold text-sm text-white group-[.item-done]:line-through group-[.item-done]:text-slate-500">
                                    ${item.qty}x ${item.name}
                                </span>
                                <span class="text-[10px] bg-slate-800 text-indigo-300 px-2 py-0.5 rounded font-semibold">${item.target_person}</span>
                            </div>
                            
                            ${item.notes || item.modifiers ? `
                                <div class="mt-1 space-y-0.5 text-xs">
                                    ${item.modifiers ? item.modifiers.map(m => `<p class="text-amber-400 font-semibold">⚡ ${m}</p>`).join('') : ''}
                                    ${item.notes ? `<p class="text-rose-400 font-semibold">🚫 ${item.notes}</p>` : ''}
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>

                <!-- Footer de Ticket -->
                <div class="p-3 bg-slate-900 border-t border-slate-700 space-y-2">
                    <button onclick="BistroKdsApp.openOrderDetails(${order.order_id})"
                        class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold py-1.5 rounded-lg text-xs border border-slate-700 transition">
                        🔍 Ver Notas / Detalle
                    </button>
                    <button onclick="BistroKdsApp.completeTicket(${order.order_id})"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold py-3 rounded-xl text-xs shadow-lg transition flex items-center justify-center space-x-1.5">
                        <span>✅</span>
                        <span>DESPACHAR ORDEN</span>
                    </button>
                </div>
            </div>`;
        }).join('');
    },

    /**
     * Tacha o destacha un producto dentro del ticket de cocina
     */
    toggleItemDone: function (element) {
        element.classList.toggle('item-done');
        element.classList.toggle('opacity-40');
        element.classList.toggle('bg-emerald-950/30');
    },

    /**
     * Despacha una orden completa al nodo central
     */
    completeTicket: async function (orderId) {
        const ticketEl = document.getElementById(`ticket-${orderId}`);

        // Animación fluida inmediata antes de la respuesta del server
        if (ticketEl) {
            ticketEl.classList.add('scale-95', 'opacity-0');
        }

        try {
            const res = await fetch('/api/kds', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'complete_order',
                    order_id: orderId
                })
            });
            const data = await res.json();

            if (data.success) {
                setTimeout(() => {
                    this.loadOrders(); // Recarga y limpia la grilla
                }, 300);
            } else {
                // Si falla el servidor, revertimos la animación
                if (ticketEl) ticketEl.classList.remove('scale-95', 'opacity-0');
                alert("Error al despachar la orden. Intente nuevamente.");
            }
        } catch (err) {
            console.error("[KDS] Error al despachar orden:", err);
            if (ticketEl) ticketEl.classList.remove('scale-95', 'opacity-0');
        }
    },

    /**
     * Filtra los tickets por estación (Calientes, Fríos, Bar)
     */
    filterStation: function (station) {
        this.activeFilter = station;
        const buttons = ['all', 'cocina', 'fria', 'bar'];

        buttons.forEach(b => {
            const btn = document.getElementById(`btn-filter-${b}`);
            if (btn) {
                btn.className = 'px-3 py-1 rounded-lg font-semibold text-slate-400 hover:text-white transition';
            }
        });

        const activeBtn = document.getElementById(`btn-filter-${station}`);
        if (activeBtn) {
            activeBtn.className = 'px-3 py-1 rounded-lg font-bold bg-indigo-600 text-white shadow';
        }

        this.loadOrders();
    },

    /**
     * Muestra modal con detalles especiales
     */
    openOrderDetails: function (orderId) {
        const order = this.orders.find(o => o.order_id === orderId);
        if (!order) return;

        document.getElementById('modal-order-title').innerText = `${order.table_name} — Comanda #${order.folio}`;
        document.getElementById('modal-order-time').innerText = `Tiempo transcurrido: ${order.elapsed_time_formatted}`;
        document.getElementById('modal-waiter-name').innerText = order.waiter_name;
        document.getElementById('modal-order-notes').innerText = order.general_notes || 'Sin notas especiales.';

        this.openModal('modal-kds-detail');
    },

    /**
     * Métricas de tiempo de cocina
     */
    updateMetrics: function (metrics) {
        if (!metrics) return;
        const avgEl = document.getElementById('kds-avg-time');
        if (avgEl) {
            avgEl.innerText = metrics.avg_preparation_time || '0 min';
        }
    },

    openModal: function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('hidden');
    },

    closeModal: function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    }
};