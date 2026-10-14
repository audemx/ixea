window.BistroKdsApp = {
    jsName: 'bistro-kds',
    isInitialized: false,

    deliveryStatus: 'pending', // 'pending' | 'delivered'
    currentStation: 0,         // 0 = Todas, N = Estación específica

    orders: [],
    stations: [],

    orderHash: null,
    autoRender: null,
    countdown: 0,

    // Almacén local de marcas temporales por orden: { [order_id]: { [item_id]: 'release' | 'cancel' } }
    markedItems: {},

    toStream: [
        ['get-orders', () => ({
            deliveryStatus: BistroKdsApp.deliveryStatus
        })]
    ],

    init: function () {
        if (this.isInitialized) return;
        this.isInitialized = true;

        this.fetchStations({ forceSync: true });
        this.fetchOrders({ forceSync: true });
    },

    destroy: function () {
        this.orders = [];
        this.markedItems = {};
        this.isInitialized = false;
    },

    onTick: {
        sec: function () {
            this.processAndCalculateTimers();
            this.updateDomTimers();
        },
        stream: function () {
            this.checkAndRenderOnStream();
        }
    },

    // ==========================================
    // API FETCHING
    // ==========================================
    fetchStations: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-kds", "get-stations", { cacheOnly, forceSync });
            if (!res.success) throw new Error(res.message);
            if ((res.changed || cacheOnly) && res.data) {
                this.stations = res.data;
                this.renderStations();
            }
        } catch (err) {
            console.error("[KDS] Error obteniendo estaciones:", err);
        }
    },

    fetchOrders: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const params = {
                deliveryStatus: this.deliveryStatus,
                currentStation: this.currentStation,
                cacheOnly,
                forceSync
            };

            const res = await IxeaData.get("bistro-kds", "get-orders", params);

            // Guardar el hash actual de la operación
            this.orderHash = IxeaData.timestamps['bistro-kds']['get-orders'] || null;

            if ((res.changed || cacheOnly) && res.data) {
                this.cancelAutoRender();
                this.processAndCalculateTimers();
                this.render();
                this.calculateKPIs();
            }
        } catch (err) {
            console.error("[KDS] Error obteniendo comandas:", err);
        }
    },

    // ==========================================
    // ESTADOS Y FILTROS
    // ==========================================
    setDeliveryStatus: function (status) {
        this.deliveryStatus = status;

        const btnPending = document.getElementById('bistro-kds-get-pending');
        const btnDelivered = document.getElementById('bistro-kds-get-delivered');

        if (status === 'pending') {
            btnPending.className = 'px-3 py-1 rounded-lg font-bold bg-indigo-600 text-white shadow';
            btnDelivered.className = 'px-3 py-1 rounded-lg font-semibold text-slate-400 hover:text-white transition';
        } else {
            btnDelivered.className = 'px-3 py-1 rounded-lg font-bold bg-indigo-600 text-white shadow';
            btnPending.className = 'px-3 py-1 rounded-lg font-semibold text-slate-400 hover:text-white transition';
        }

        this.fetchOrders();
    },

    filterStation: function (stationId) {
        this.currentStation = parseInt(stationId);

        const buttons = document.querySelectorAll('#bistro-kds-stations button');
        buttons.forEach(btn => {
            const btnStationId = parseInt(btn.id.replace('bistro-kds-station-', ''));
            if (btnStationId === this.currentStation) {
                btn.className = 'bg-indigo-600 text-white px-3 py-1 rounded-xl font-bold whitespace-nowrap shadow text-xs transition shrink-0';
            } else {
                btn.className = 'bg-slate-800 text-slate-200 px-3 py-1 rounded-xl whitespace-nowrap border border-slate-700 text-xs transition shrink-0';
            }
        });

        this.processAndCalculateTimers();
        this.render();
        this.calculateKPIs();
    },

    // ==========================================
    // KPIS Y TIEMPOS
    // ==========================================
    calculateKPIs: function () {
        const avgEl = document.getElementById('bistro-kds-kpi-avg');
        const countsEl = document.getElementById('bistro-kds-kpi-counts');
        const labelEl = document.getElementById('bistro-kds-kpi-count-label');

        if (labelEl) {
            labelEl.textContent = this.deliveryStatus === 'pending' ? 'Por cerrar' : 'Despachados';
        }

        let totalItems = 0;
        let totalPeople = 0;
        let totalOrders = this.orders.length;

        let sumItemsSec = 0;

        this.orders.forEach(o => {
            totalPeople += o.people.length;

            o.people.forEach(p => {
                totalItems += p.items.length;

                p.items.forEach(i => {
                    sumItemsSec += (i.running || 0);
                });
            });
        });

        if (avgEl) {
            const avgI = totalItems ? Math.round((sumItemsSec / totalItems) / 60) : 0;
            avgEl.textContent = `${avgI}m`;
        }

        if (countsEl) {
            countsEl.textContent = `🍴 ${totalItems} | 👥 ${totalPeople} | 📋 ${totalOrders}`;
        }
    },

    formatTime: function (seconds) {
        if (seconds === undefined || seconds === null) return '00:00';
        const isNegative = seconds < 0;
        const absSec = Math.abs(seconds);
        const min = String(Math.floor(absSec / 60)).padStart(2, '0');
        const sec = String(absSec % 60).padStart(2, '0');
        return `${isNegative ? '-' : ''}${min}:${sec}`;
    },

    // ==========================================
    // PROCESAMIENTO, FILTRADO Y TIEMPOS (CLIENTE)
    // ==========================================
    processAndCalculateTimers: function () {
        const nowMs = Date.now();
        const filtered = [];
        const rawOrders = (typeof IxeaData !== 'undefined' && IxeaData.cache && IxeaData.cache['bistro-kds'])
            ? (IxeaData.cache['bistro-kds']['get-orders'] || [])
            : (this.rawOrders || []);

        rawOrders.forEach(rawOrder => {
            const validPeople = [];

            (rawOrder.people || []).forEach(person => {
                const validItems = (person.items || []).filter(item => {
                    return this.currentStation === 0 || item.station_id === this.currentStation;
                }).map(item => {
                    const rawItemTime = item.created_at ? new Date(item.created_at).getTime() : nowMs;
                    const itemCreatedMs = isNaN(rawItemTime) ? nowMs : rawItemTime;

                    // Tiempo transcurrido e individual en segundos
                    const itemRunning = Math.max(0, Math.floor((nowMs - itemCreatedMs) / 1000));
                    const targetPrepSec = (item.prep_time || 0) * 60;

                    // Tiempo restante en segundos
                    const itemRemaining = targetPrepSec - itemRunning;

                    return {
                        ...item,
                        created_at_ms: itemCreatedMs,
                        running: itemRunning,
                        remaining: itemRemaining
                    };
                });

                if (validItems.length > 0) {
                    // Persona: running máximo (item más antiguo) y remaining mínimo (el más demorado)
                    const personRunning = Math.max(...validItems.map(i => i.running));
                    const personRemaining = Math.min(...validItems.map(i => i.remaining));

                    validPeople.push({
                        ...person,
                        running: personRunning,
                        remaining: personRemaining,
                        items: validItems
                    });
                }
            });

            if (validPeople.length > 0) {
                // Orden: running máximo de sus personas y remaining mínimo
                const orderRunning = Math.max(...validPeople.map(p => p.running));
                const orderRemaining = Math.min(...validPeople.map(p => p.remaining));

                filtered.push({
                    ...rawOrder,
                    running: orderRunning,
                    remaining: orderRemaining,
                    people: validPeople
                });
            }
        });

        this.orders = filtered;
        this.sortDataHierarchy();
    },

    // ==========================================
    // ORDENAMIENTO POR PRIORIDAD DE URGENCIA (ADR-013)
    // ==========================================
    sortDataHierarchy: function () {
        const getUrgScore = (entity) => {
            if (entity.remaining) {
                return entity.remaining;
            }
            return -(entity.running || 0);
        };

        this.orders.forEach(order => {
            order.people.forEach(person => {
                person.items.sort((a, b) => getUrgScore(a) - getUrgScore(b));
            });
            order.people.sort((a, b) => {
                const minA = a.items.length ? Math.min(...a.items.map(getUrgScore)) : getUrgScore(a);
                const minB = b.items.length ? Math.min(...b.items.map(getUrgScore)) : getUrgScore(b);
                return minA - minB;
            });
        });

        this.orders.sort((a, b) => {
            const minA = a.people.length ? Math.min(...a.people.map(p => p.items.length ? Math.min(...p.items.map(getUrgScore)) : getUrgScore(p))) : getUrgScore(a);
            const minB = b.people.length ? Math.min(...b.people.map(p => p.items.length ? Math.min(...p.items.map(getUrgScore)) : getUrgScore(p))) : getUrgScore(b);
            return minA - minB;
        });
    },

    // ==========================================
    // RENDERIZADO
    // ==========================================
    renderStations: function () {
        const container = document.getElementById('bistro-kds-stations');
        if (!container) return;

        let html = `
            <button onclick="BistroKdsApp.filterStation(0)" id="bistro-kds-station-0" 
                    class="${this.currentStation === 0 ? 'bg-indigo-600 text-white font-bold shadow' : 'bg-slate-800 text-slate-200 border border-slate-700'} px-3 py-1 rounded-xl whitespace-nowrap text-xs transition shrink-0">
                Todas
            </button>
        `;

        this.stations.forEach(st => {
            const isSel = this.currentStation === st.id;
            html += `
                <button onclick="BistroKdsApp.filterStation(${st.id})" id="bistro-kds-station-${st.id}" 
                        class="${isSel ? 'bg-indigo-600 text-white font-bold shadow' : 'bg-slate-800 text-slate-200 border border-slate-700'} px-3 py-1 rounded-xl whitespace-nowrap text-xs transition shrink-0">
                    ${st.emoji || '🍳'} ${st.name}
                </button>
            `;
        });

        container.innerHTML = html;
    },

    /** Genera todas las tarjetas de pedidos preservando el scroll actual */
    render: function () {
        const container = document.getElementById('bistro-kds-cards');
        const template = document.getElementById('bistro-kds-template-order');
        if (!container || !template) return;

        // 1. Guardar la posición de scroll de cada cuerpo de tarjeta antes de redibujar
        const scrollPositions = {};
        container.querySelectorAll('.kds-order-card').forEach(card => {
            const orderId = card.dataset.orderId;
            const body = card.querySelector('.kds-order-body');
            if (orderId && body) {
                scrollPositions[orderId] = body.scrollTop;
            }
        });

        container.innerHTML = '';

        if (this.orders.length === 0) {
            container.innerHTML = `
                <div class="flex-1 flex items-center justify-center text-slate-500 py-20">
                    <p class="text-xl font-bold">Sin comandas ${this.deliveryStatus === 'pending' ? 'pendientes' : 'entregadas'}</p>
                </div>`;
            return;
        }

        this.orders.forEach(order => {
            const clone = template.content.cloneNode(true);
            const card = clone.querySelector('.kds-order-card');
            card.dataset.orderId = order.order_id;

            // Header Metadata
            card.querySelector('.kds-order-name').textContent = order.order;
            card.querySelector('.kds-order-channel-badge').textContent = order.channel || 'Local';
            card.querySelector('.kds-order-pcount').textContent = order.p_count || order.people.length;

            const totalPendingItems = order.people.reduce((acc, p) => acc + p.items.length, 0);
            card.querySelector('.kds-order-icount').textContent = totalPendingItems;

            // Timer & Status
            const timerEl = card.querySelector('.kds-order-timer');
            timerEl.textContent = this.formatTime(order.running);

            const remEl = card.querySelector('.kds-order-remaining');
            if (order.remaining < 0) {
                remEl.textContent = `${Math.ceil(Math.abs(order.remaining / 60))} min`;
                remEl.className = 'kds-order-remaining text-[14px] font-mono font-bold text-rose-600';
            } else {
                remEl.textContent = `${Math.ceil(order.remaining / 60)} min`;
                remEl.className = 'kds-order-remaining text-[14px] font-mono font-bold text-emerald-600';
            }

            // Click / Doble Click en Header Orden
            const orderHeader = card.querySelector('.kds-order-header');
            this.bindClickEvents(orderHeader, (action) => this.markOrder(order.order_id, action));

            // Render Subtarjetas Personas
            const body = card.querySelector('.kds-order-body');
            order.people.forEach(person => {
                const personEl = this.createPersonElement(order.order_id, person);
                body.appendChild(personEl);
            });

            // Acción Unificada de Confirmación
            const btnCancel = card.querySelector('.btn-cancel-marked');
            const btnExecute = card.querySelector('.btn-execute-marked');
            btnCancel.onclick = () => this.cancelAllMarkedActions(order.order_id);
            btnExecute.onclick = () => this.executeAllMarkedActions(order.order_id);

            container.appendChild(clone);

            // 2. Restaurar la posición de scroll exacta que tenía esta tarjeta
            const newCard = container.querySelector(`.kds-order-card[data-order-id="${order.order_id}"]`);
            if (newCard) {
                const newBody = newCard.querySelector('.kds-order-body');
                if (newBody && scrollPositions[order.order_id] !== undefined) {
                    newBody.scrollTop = scrollPositions[order.order_id];
                }
            }

            this.updateActionToolbar(order.order_id);
        });
    },

    /** Crea subtarjeta de persona */
    createPersonElement: function (orderId, person) {
        const div = document.createElement('div');
        div.className = 'kds-person-block bg-slate-900/90 border border-slate-700/80 rounded-xl p-2 space-y-1.5';
        div.dataset.personId = person.p_id;

        const header = document.createElement('div');
        header.className = 'kds-person-header flex justify-between items-center text-xs pb-1 border-b border-slate-800 cursor-pointer select-none';
        header.innerHTML = `
            <span class="font-black text-indigo-300">${person.p_id === 0 ? '🍽️ Centro' : '👤 Persona ' + person.p_id}</span>
            <span class="text-[10px] font-mono text-slate-400">${person.items.length} ítems</span>
        `;

        this.bindClickEvents(header, (action) => this.markPerson(orderId, person.p_id, action));
        div.appendChild(header);

        const itemsBox = document.createElement('div');
        itemsBox.className = 'space-y-1.5';

        person.items.forEach(item => {
            const itemEl = this.createItemElement(orderId, item);
            itemsBox.appendChild(itemEl);
        });

        div.appendChild(itemsBox);
        return div;
    },

    /** Crea subtarjeta de item */
    createItemElement: function (orderId, item) {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'kds-item-card p-2 bg-slate-800 rounded-lg text-xs border border-slate-700 relative cursor-pointer select-none transition-all';
        itemDiv.dataset.itemId = item.item_id;

        const markState = this.markedItems[orderId]?.[item.item_id];
        let markBgClass = '';
        // Colores mejorados con mayor contraste y brillo para fondo oscuro
        if (markState === 'release') markBgClass = 'border-emerald-400 bg-emerald-900/60 shadow-[0_0_12px_rgba(52,211,153,0.3)]';
        if (markState === 'cancel') markBgClass = 'border-rose-400 bg-rose-900/60 shadow-[0_0_12px_rgba(251,113,133,0.3)]';

        if (markBgClass) itemDiv.className += ` ${markBgClass}`;

        const formattedTime = `${Math.ceil(Math.abs(item.remaining / 60))} min`;
        const timeClass = item.remaining < 0 ? 'text-rose-400 font-bold' : 'text-emerald-400';

        itemDiv.innerHTML = `
            <div class="flex justify-between items-start font-bold text-white relative">
                <span>${item.quantity}x ${item.name}</span>
                <span class="kds-item-timer font-mono text-end text-[10px] ${timeClass}">${formattedTime}</span>
            </div>
            ${(item.modifiers || []).map(m => `<div class="text-[11px] text-amber-400 pl-2">• ${m}</div>`).join('')}
            ${item.notes ? `<div class="text-[10px] text-amber-300 italic mt-0.5">📝 ${item.notes}</div>` : ''}
        `;

        // Pasamos el itemId de forma explícita al callback
        this.bindClickEvents(itemDiv, (action) => this.markItem(orderId, item.item_id, action));
        return itemDiv;
    },

    // ==========================================
    // ACTUALIZACIÓN DE DOM REPOSITORIO DE TIMERS
    // ==========================================
    updateDomTimers: function () {
        this.orders.forEach(order => {
            const card = document.querySelector(`.kds-order-card[data-order-id="${order.order_id}"]`);
            if (!card) return;

            // 1. Timers a nivel Orden
            const timerEl = card.querySelector('.kds-order-timer');
            if (timerEl) timerEl.textContent = this.formatTime(order.running);

            const remEl = card.querySelector('.kds-order-remaining');
            if (remEl) {
                remEl.textContent = `${Math.ceil(Math.abs(order.remaining / 60))} min`;
                remEl.className = `kds-order-remaining text-[14px] font-mono font-bold ${order.remaining < 0 ? 'text-rose-600' : 'text-emerald-600'}`;
            }

            // 2. Timers a nivel de Ítem Individual
            order.people.forEach(person => {
                const personBlock = card.querySelector(`.kds-person-block[data-person-id="${person.p_id}"]`);
                if (!personBlock) return;

                person.items.forEach(item => {
                    const itemCard = personBlock.querySelector(`.kds-item-card[data-item-id="${item.item_id}"]`);
                    if (!itemCard) return;

                    const iTimer = itemCard.querySelector('.kds-item-timer');
                    if (iTimer) {
                        iTimer.textContent = `${Math.ceil(Math.abs(item.remaining / 60))} min`;
                        iTimer.className = `kds-item-timer font-mono text-end text-[10px] ${item.remaining < 0 ? 'text-rose-400 font-bold' : 'text-emerald-400'}`;
                    }
                });
            });
        });
    },

    /**
     * Verifica si existen cambios en la BD desde la transmisión SSE / Polling
     * Fuerza renderizado solo si el usuario no tiene selección activa.
     */
    checkAndRenderOnStream: function () {
        const latestHash = (IxeaData.timestamps && IxeaData.timestamps['bistro-kds'])
            ? IxeaData.timestamps['bistro-kds']['get-orders']
            : null;

        if (latestHash !== this.orderHash) {
            // Si el usuario tiene ítems marcados o ya hay un contador en marcha, ignoramos la activación
            if (this.hasUserInteractions() || this.autoRender !== null) {
                return;
            }
            // Iniciar la cuenta regresiva visual
            this.startAutoRender(latestHash, 3);
        }
    },

    /**
     * Comprueba si el usuario tiene alguna selección activa en cualquier comanda.
     */
    hasUserInteractions: function () {
        if (!this.markedItems) return false;
        return Object.keys(this.markedItems).some(orderId => {
            const marks = this.markedItems[orderId];
            return marks && typeof marks === 'object' && Object.keys(marks).length > 0;
        });
    },

    /**
     * Inicia una cuenta regresiva para el renderizado automático con badge en el header.
     */
    startAutoRender: function (targetHash, seconds = 3) {
        this.cancelAutoRender();

        this.countdown = seconds;
        this.updateCountdown();

        this.autoRender = setInterval(() => {
            if (this.hasUserInteractions()) {
                this.cancelAutoRender();
                return;
            }

            this.countdown--;
            this.updateCountdown();
            // 
            if (this.countdown <= 0) {
                this.cancelAutoRender();
                this.orderHash = targetHash;
                this.render();
            }
        }, 1000);
    },

    /**
     * Cancela la cuenta regresiva y oculta el indicador del header.
     */
    cancelAutoRender: function () {
        if (this.autoRender !== null) {
            clearInterval(this.autoRender);
            this.autoRender = null;
        }
        this.countdown = 0;
        this.updateCountdown();
    },

    /**
     * Muestra u oculta el badge de actualización en el header.
     */
    updateCountdown: function () {
        let container = document.getElementById('kds-sync-countdown');

        if (this.countdown > 0) {
            if (!container) return;
            container.className = 'flex items-center gap-2 bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3 py-1 rounded-full text-xs font-bold animate-pulse';
            container.innerHTML = `<span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span> Actualizando en ${this.countdown}s...`;
        } else if (container) {
            container.innerHTML = '';
            container.className = 'hidden';
        }
    },

    // ==========================================
    // GESTIÓN DE SELECCIÓN POR TOQUES (1 clic = release, 2 clics = cancel)
    // ==========================================
    bindClickEvents: function (element, callback) {
        let clickTimeout = null;

        element.addEventListener('click', (e) => {
            // Evitar que reaccione si hace clic en botones internos
            if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;

            if (clickTimeout !== null) {
                // Segundo clic detectado dentro de la ventana de tiempo -> CANCELAR
                clearTimeout(clickTimeout);
                clickTimeout = null;
                callback('cancel');
            } else {
                // Primer clic -> Esperar por si hay un segundo clic, si no, ejecutar LIBERAR
                clickTimeout = setTimeout(() => {
                    clickTimeout = null;
                    callback('release');
                }, 280); // 280ms ventana estándar para doble toque
            }
        });
    },

    markItem: function (orderId, itemId, action) {
        if (!this.markedItems[orderId]) this.markedItems[orderId] = {};
        const current = this.markedItems[orderId][itemId];

        if (current === action) {
            delete this.markedItems[orderId][itemId];
        } else {
            this.markedItems[orderId][itemId] = action;
        }

        this.render();
    },

    markPerson: function (orderId, personId, action) {
        const order = this.orders.find(o => o.order_id === orderId);
        if (!order) return;
        const person = order.people.find(p => p.p_id === personId);
        if (!person) return;

        if (!this.markedItems[orderId]) this.markedItems[orderId] = {};

        person.items.forEach(item => {
            const current = this.markedItems[orderId][item.item_id];
            if (current === action) {
                delete this.markedItems[orderId][item.item_id];
            } else {
                this.markedItems[orderId][item.item_id] = action;
            }
        });

        this.render();
    },

    markOrder: function (orderId, action) {
        const order = this.orders.find(o => o.order_id === orderId);
        if (!order) return;

        if (!this.markedItems[orderId]) this.markedItems[orderId] = {};

        order.people.forEach(p => {
            p.items.forEach(item => {
                const current = this.markedItems[orderId][item.item_id];
                if (current === action) {
                    delete this.markedItems[orderId][item.item_id];
                } else {
                    this.markedItems[orderId][item.item_id] = action;
                }
            });
        });

        this.render();
    },

    /** Actualiza la barra de acciones de la comanda */
    updateActionToolbar: function (orderId) {
        const card = document.querySelector(`.kds-order-card[data-order-id="${orderId}"]`);
        if (!card) return;

        const toolbar = card.querySelector('.kds-order-actions-bar');
        const btnExecute = card.querySelector('.btn-execute-marked');
        const orderMarks = this.markedItems[orderId] || {};
        const count = Object.keys(orderMarks).length;

        if (count > 0) {
            toolbar.classList.remove('hidden');
            btnExecute.textContent = `✓ Confirmar (${count})`;
        } else {
            toolbar.classList.add('hidden');
        }
    },

    /** Cancela todas las acciones marcadas */
    cancelAllMarkedActions: async function (orderId) {
        const orderMarks = this.markedItems[orderId] || {};
        const releaseIds = Object.keys(orderMarks).filter(id => orderMarks[id] === 'release');
        const cancelIds = Object.keys(orderMarks).filter(id => orderMarks[id] === 'cancel');

        if (releaseIds.length === 0 && cancelIds.length === 0) {
            IxeaComponents.showAlert({ text: 'No hay ítems marcados en esta comanda', icon: 'info' });
            return;
        }

        this.markedItems[orderId] = {};
        this.render();
    },

    /**
     * Ejecuta todas las acciones marcadas (liberaciones, cancelaciones o mezcla de ambas) con un solo botón
     */
    executeAllMarkedActions: async function (orderId) {
        const orderMarks = this.markedItems[orderId] || {};
        const releaseIds = Object.keys(orderMarks).filter(id => orderMarks[id] === 'release');
        const cancelIds = Object.keys(orderMarks).filter(id => orderMarks[id] === 'cancel');

        if (releaseIds.length === 0 && cancelIds.length === 0) {
            IxeaComponents.showAlert({ text: 'No hay ítems marcados en esta comanda', icon: 'info' });
            return;
        }

        try {
            IxeaComponents.showLoading({ text: 'Procesando acciones en comanda...' });

            // Ejecutar liberaciones si las hay
            if (releaseIds.length > 0) {
                const resRelease = await IxeaBridge.post('/api/v1/bistro-kds/release-items', {
                    order_id: orderId,
                    item_ids: releaseIds
                });
                if (!resRelease.success) throw new Error(resRelease.message || 'Error al liberar ítems');
            }

            // Ejecutar cancelaciones si las hay
            if (cancelIds.length > 0) {
                const resCancel = await IxeaBridge.post('/api/v1/bistro-kds/cancel-items', {
                    order_id: orderId,
                    item_ids: cancelIds
                });
                if (!resCancel.success) throw new Error(resCancel.message || 'Error al cancelar ítems');
            }

            IxeaComponents.hideLoading();
            delete this.markedItems[orderId];
            IxeaComponents.showAlert({ text: 'Acciones aplicadas exitosamente', icon: 'success', timer: 1200 });
            this.fetchOrders();

        } catch (err) {
            IxeaComponents.hideLoading();
            IxeaComponents.showAlert({ text: err.message || 'Error al procesar las acciones', icon: 'error' });
        }
    }
};