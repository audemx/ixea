window.BistroTillApp = {
    jsName: 'bistro-till',
    isInitialized: false,

    // Estado
    isOpen: false, // Estado de la caja
    cashVisible: false, // Estado de visibilidad de la caja
    filter: 'pending', // 'pending', 'open', 'paid'
    shift: null, // Objeto del turno activo o null
    bills: IxeaData.cache?.['bistro-till']?.['get-bills'] ?? [], // Cuentas:Arrancamos con caché
    openOrders: IxeaData.cache?.['bistro-till']?.['get-opens'] ?? [], // Ordenes abiertas: Arrancamos con caché
    activePayBill: null, // Pedido actualmente abierto por pago
    currentRequestId: 0, // Contador de peticiones para evitar race conditions

    // Streaming
    billsHash: IxeaData.timestamps?.['bistro-till']?.['get-bills'] ?? null, // Hash de las cuentas
    openOrdersHash: IxeaData.timestamps?.['bistro-till']?.['get-opens'] ?? null, // Hash de las ordenes abiertas
    autoRender: null, // Estado de auto-render
    countdown: 0, // Contador de auto-render
    isEditing: false, // Estado de edición, para impedir actualizaciones mientras se está editando una cuenta

    toStream: [], // Lista de datos llamados por stream

    // ==========================================
    // INICIALIZACIÓN Y STREAMING
    // ==========================================
    init: async function () {
        if (this.isInitialized) return;
        this.isInitialized = true;
        // Se valida obligatoriamente el estado de la caja
        await this.checkTillStatus({ forceSync: true });
        await this.fetchBills({ forceSync: true });
    },

    onStream: function () {
        // Si la caja está cerrada o no hay turno activo, desactiva el stream
        if (!this.isOpen || !this.shift) {
            this.toStream = [];
            return;
        }
        if (this.filter === 'open') {
            this.toStream = [
                ['get-opens']
            ];
        } else {
            this.toStream = [
                ['get-bills', () => ({
                    filter: this.filter,
                    shift_id: this.shift.id
                })]
            ];
        }
    },

    onTick: {
        stream: function () {
            // Solo procesar stream si la caja está abierta
            if (this.isOpen && this.shift) {
                this.checkAndRenderOnStream();
            }
        }
    },

    renderAppActions: function () {
        const menuFilters = document.getElementById('menu-app-filters');
        if (!menuFilters || !this.isOpen) {
            menuFilters.innerHTML = '';
            return;
        }

        // Nombres descriptivos para la interfaz
        const filterNames = {
            'pending': 'Pendientes',
            'open': 'Abiertas',
            'paid': 'Cobradas'
        };
        const activeLabel = filterNames[this.filter] || 'Pendientes';

        menuFilters.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer text-emerald-400 font-bold" data-bs-toggle="dropdown">
                    <i class="bi bi-funnel me-2"></i>Vista: ${activeLabel}
                </div>
                <ul class="dropdown-menu shadow border-0 mt-2 bg-slate-800 text-white">
                    <li>
                        <a class="dropdown-item small d-flex justify-content-between cursor-pointer" onclick="BistroTillApp.setFilter('pending')">
                            Pendientes ${this.filter === 'pending' ? '<i class="bi bi-check text-emerald-400"></i>' : ''}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item small d-flex justify-content-between cursor-pointer" onclick="BistroTillApp.setFilter('open')">
                            Abiertas ${this.filter === 'open' ? '<i class="bi bi-check text-emerald-400"></i>' : ''}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item small d-flex justify-content-between cursor-pointer" onclick="BistroTillApp.setFilter('paid')">
                            Pagadas ${this.filter === 'paid' ? '<i class="bi bi-check text-emerald-400"></i>' : ''}
                        </a>
                    </li>
                </ul>
            </div>
        `;

        const menuActions = document.getElementById('menu-app-actions');
        if (!menuActions) return;

        // Menú de operaciones del turno/caja
        menuActions.innerHTML = `
                <div class="dropdown">
                    <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">Operaciones</div>
                    <ul class="dropdown-menu shadow border-0 mt-2 bg-slate-800 text-white">
                        <li><hr class="dropdown-divider border-slate-700"></li>
                        <li>
                            <a class="dropdown-item small cursor-pointer" onclick="BistroTillApp.closeShiftModal()">
                                <i class="bi bi-lock me-2 text-rose-400"></i>Cerrar Turno
                            </a>
                        </li>
                    </ul>
                </div>
            `;
    },

    // ==========================================
    // ESTADO DE LA CAJA (TILL STATUS)
    // ==========================================
    checkTillStatus: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-till", "get-status", { cacheOnly, forceSync });
            if (!res.success) {
                IxeaComponents.showAlert({
                    title: 'Error',
                    message: res.message || 'No se pudo obtener el estatus de la caja',
                    type: 'error'
                });
                return;
            }
            if ((res.changed || cacheOnly) && res.data) {
                this.isOpen = res.data.is_open;
                this.shift = res.data.shift;
                this.updateTillStatus();
            }
        } catch (err) {
            console.log(err);
            IxeaComponents.showAlert({
                title: 'Error',
                message: 'Error de conexión',
                type: 'error'
            });
        }
    },

    renderTillStatus: function () {
        const icon = document.getElementById('bistro-till-status-icon');
        const text = document.getElementById('bistro-till-status');
        const openInfo = document.getElementById('bistro-till-open-info');
        const closedInfo = document.getElementById('bistro-till-closed-info');
        const actions = document.getElementById('bistro-till-actions-grid');

        if (this.isOpen) {
            if (icon) icon.innerHTML = '<i class="bi bi-door-open text-emerald-400"></i>';
            if (text) {
                text.textContent = 'Caja Abierta';
                text.className = 'text-xl font-bold text-emerald-400 mb-1';
            }
            if (openInfo) openInfo.classList.remove('hidden');
            if (closedInfo) closedInfo.classList.add('hidden');
            if (actions) actions.classList.remove('hidden');
            this.updateCashDisplay();
        } else {
            if (icon) icon.innerHTML = '<i class="bi bi-door-closed text-rose-400"></i>';
            if (text) {
                text.textContent = 'Caja Cerrada';
                text.className = 'text-xl font-bold text-rose-400 mb-1';
            }
            if (openInfo) openInfo.classList.add('hidden');
            if (closedInfo) closedInfo.classList.remove('hidden');
            if (actions) actions.classList.add('hidden');

            this.bills = [];
            this.renderBills();
        }
    },

    /**
     * Verifica si existen cambios en la BD desde la transmisión SSE / Polling
     * Fuerza renderizado solo si el usuario no tiene selección activa.
     */
    checkAndRenderOnStream: function () {
        if (this.filter === 'open') {
            const latestHash = (IxeaData.timestamps?.['bistro-till']?.['get-opens']) ?? null;
            if (latestHash !== this.openOrdersHash) {
                // Si el usuario tiene ítems marcados o ya hay un contador en marcha, ignoramos la activación
                if (this.isEditing || this.autoRender !== null) {
                    return;
                }
                // Iniciar la cuenta regresiva visual
                this.startAutoRender(latestHash, 3);
            }
        } else {
            const latestHash = (IxeaData.timestamps?.['bistro-till']?.['get-bills']) ?? null;
            if (latestHash !== this.billsHash) {
                // Si el usuario tiene ítems marcados o ya hay un contador en marcha, ignoramos la activación
                if (this.isEditing || this.autoRender !== null) {
                    return;
                }
                // Iniciar la cuenta regresiva visual
                this.startAutoRender(latestHash, 3);
            }
        }
    },

    /**
     * Inicia una cuenta regresiva para el renderizado automático con badge en el header.
     */
    startAutoRender: function (targetHash, seconds = 3) {
        this.cancelAutoRender();

        this.countdown = seconds;
        this.updateCountdown();

        this.autoRender = setInterval(() => {
            if (this.isEditing) {
                this.cancelAutoRender();
                return;
            }

            this.countdown--;
            this.updateCountdown();
            // 
            if (this.countdown <= 0) {
                this.cancelAutoRender();
                if (this.filter === 'open') {
                    this.openOrders = (IxeaData.cache?.['bistro-till']?.['get-opens']) ?? [];
                    this.openOrdersHash = targetHash;
                    this.renderOpens();
                } else {
                    this.bills = (IxeaData.cache?.['bistro-till']?.['get-bills']) ?? [];
                    this.billsHash = targetHash;
                    this.renderBills();
                }
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
        let container = document.getElementById('bistro-till-sync-countdown');

        if (this.countdown > 0) {
            if (!container) return;
            container.className = 'flex items-center gap-2 bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3 py-1 rounded-full text-xs font-bold animate-pulse';
            container.innerHTML = `<span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span> Actualizando en ${this.countdown}s...`;
        } else if (container) {
            container.innerHTML = '';
            container.className = 'hidden';
        }
    },

    toggleCashVisibility: function () {
        this.cashVisible = !this.cashVisible;
        this.updateCashDisplay();
    },

    updateCashDisplay: function () {
        const display = document.getElementById('bistro-till-cash');
        const eyeIcon = document.getElementById('bistro-till-eye-icon');

        if (display) {
            display.textContent = this.cashVisible
                ? `$${toCurrency(this.shift.balance)}`
                : '******';
        }

        if (eyeIcon) {
            eyeIcon.className = this.cashVisible
                ? 'bi bi-eye text-lg'
                : 'bi bi-eye-slash text-lg';
        }
    },

    openShiftModal: function () {
        IxeaComponents.openModal('open', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-full max-w-md bg-slate-800 border border-slate-700 rounded-2xl',
            onOpen: () => {
                this.checkTillStatus();
                const balanceEl = document.getElementById('bistro-till-open-balance');
                if (balanceEl) {
                    balanceEl.textContent = `$${toCurrency(this.shift.balance || 0)}`;
                    document.getElementById('bistro-till-open-amount').focus();
                }
            }
        });
    },

    confirmOpenShift: async function () {
        const amountInput = document.getElementById('bistro-till-open-amount');
        const amount = amountInput ? parseFloat(amountInput.value) : null;

        if (amount === null || isNaN(amount) || amount < 0) {
            IxeaComponents.showAlert({
                text: 'Ingrese un monto válido para apertura.',
                icon: 'warning'
            });
            return;
        }

        if (amount < this.shift.balance) {
            IxeaComponents.showAlert({
                text: 'El monto de apertura no puede ser menor que el saldo en sistema.',
                icon: 'warning'
            });
            return;
        }

        IxeaComponents.showLoading({ text: 'Abriendo turno...' });

        try {
            const res = await IxeaBridge.post('/api/v1/bistro-till/open-shift', { amount: amount });
            if (res.success) {
                this.isOpen = res.data.is_open;
                this.shift = res.data.shift;
                IxeaComponents.showAlert({ text: 'Turno abierto correctamente', icon: 'success' });
            } else {
                IxeaComponents.showAlert({ text: res.message || 'No se pudo abrir el turno.', icon: 'error' });
            }
        } catch (err) {
            IxeaComponents.showAlert({ text: 'Error de conexión.', icon: 'error' });
        } finally {
            this.updateTillStatus();
            IxeaComponents.hideLoading();
            IxeaComponents.closeModal();
        }
    },

    updateTillStatus: function () {
        this.renderAppActions();
        this.renderTillStatus();
        this.onStream();
    },

    closeShiftModal: function () {
        const len = this.openOrders.length;
        if (len > 0) {
            IxeaComponents.showAlert({
                icon: 'error',
                text: 'No se puede cerrar el turno, hay pedidos pendientes de cobrar.'
            });
            return;
        }
        IxeaComponents.openModal('close', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-full max-w-md bg-slate-800 border border-slate-700 rounded-2xl',
            onOpen: () => {
                this.checkTillStatus();
                const expectEl = document.getElementById('bistro-till-close-expected');
                if (expectEl) {
                    expectEl.textContent = `$${toCurrency(this.shift.balance || 0)}`;
                }
            }
        });
    },

    confirmCloseShift: async function () {
        IxeaComponents.showLoading({ text: 'Cerrando turno...' });
        const amountInput = document.getElementById('bistro-till-close-counted');
        const amount = amountInput ? parseFloat(amountInput.value) : null;

        if (amount === null || isNaN(amount) || amount < 0) {
            IxeaComponents.showAlert({
                icon: 'error',
                text: 'Ingrese un monto válido para el cierre.'
            });
            return;
        }

        const diff = amount - this.shift.balance;
        if (diff < 0) {
            IxeaComponents.showAlert({
                icon: 'error',
                text: 'El monto de cierre no puede ser menor al saldo actual en sistema.'
            });
            return;
        }

        try {
            const payload = {
                shift_id: this.shift.id,
                amount: amount
            };

            const res = await IxeaBridge.post('/api/v1/bistro-till/close-shift', payload);
            console.log(res);
            if (res.success) {
                this.isOpen = res.data.is_open;
                this.shift = res.data.shift;
                this.bills = [];
                this.updateTillStatus();
                IxeaComponents.showAlert({
                    icon: 'success',
                    text: 'Turno cerrado correctamente',
                    timer: 2500
                });
            } else {
                IxeaComponents.showAlert({ text: res.message || 'No se pudo cerrar el turno.', icon: 'error' });
            }
        } catch (err) {
            IxeaComponents.showAlert({
                icon: 'error',
                text: 'Error de conexión.'
            });
        } finally {
            IxeaComponents.hideLoading();
            IxeaComponents.closeModal();
        }
    },

    // ==========================================
    // GESTIÓN DE CUENTAS Y RENDERIZADO
    // ==========================================

    // Cambia el filtro activo y fuerza la actualización de la lista
    setFilter: async function (filter) {
        if (!this.isOpen) return;
        if (this.filter === filter) return;

        this.filter = filter;
        this.renderAppActions();

        // Actualizamos el título inmediatamente para feedback visual instantáneo
        const container = document.getElementById('bistro-till-title');
        if (container) {
            const titles = {
                'open': '<i class="bi bi-bag me-2"></i>Pedidos Abiertos',
                'pending': '<i class="bi bi-receipt me-2"></i>Pedidos por Cobrar',
                'paid': '<i class="bi bi-receipt me-2"></i>Pedidos Pagados'
            };
            container.innerHTML = titles[filter] || '<i class="bi bi-receipt me-2"></i>Pedidos';
        }

        // Ejecutamos la petición adecuada
        if (filter === 'open') {
            await this.fetchOpens();
        } else {
            await this.fetchBills();
        }
    },

    fetchOpens: async function ({ cacheOnly = false, forceSync = false } = {}) {
        if (!this.isOpen || !this.shift) {
            this.openOrders = [];
            this.renderOpens();
            return;
        }

        // Generamos un ID único para esta petición
        const requestId = ++this.currentRequestId;

        try {
            const res = await IxeaData.get("bistro-till", "get-opens", {
                cacheOnly,
                forceSync
            });

            // Si el usuario cambió de filtro mientras esta petición viajaba, la ignoramos
            if (requestId !== this.currentRequestId) return;

            if (res && res.data) {
                this.openOrders = res.data;
            }
            this.renderOpens();
        } catch (err) {
            if (requestId === this.currentRequestId) {
                console.error("[Till] Error al obtener órdenes abiertas:", err);
            }
        }
    },

    fetchBills: async function ({ cacheOnly = false, forceSync = false } = {}) {
        if (!this.isOpen || !this.shift) {
            this.bills = [];
            this.renderBills();
            return;
        }

        // Generamos un ID único para esta petición
        const requestId = ++this.currentRequestId;
        const requestedFilter = this.filter; // Capturamos el filtro solicitado

        try {
            const res = await IxeaData.get("bistro-till", "get-bills", {
                filter: requestedFilter,
                shift_id: this.shift.id,
                cacheOnly,
                forceSync
            });

            // Si el usuario ya dio clic a otro filtro o lanzó otra petición, ignoramos esta respuesta
            if (requestId !== this.currentRequestId || this.filter !== requestedFilter) {
                return;
            }

            if (IxeaData.timestamps?.['bistro-till']?.['get-bills']) {
                this.billsHash = IxeaData.timestamps['bistro-till']['get-bills'];
            }

            if (res && res.data) {
                this.bills = res.data;
            }

            // Garantizamos el renderizado con los datos recibidos
            this.renderBills();
        } catch (err) {
            if (requestId === this.currentRequestId) {
                console.error("[Till] Error al obtener pedidos:", err);
            }
        }
    },

    renderOpens: function () {
        const container = document.getElementById('bistro-till-pending-list');
        if (!container) return;

        container.innerHTML = '';

        if (!this.openOrders || this.openOrders.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center text-slate-500 py-16 text-center">
                    <i class="bi bi-shop text-4xl mb-2"></i>
                    <p class="text-sm">No hay órdenes abiertas en servicio actualmente.</p>
                </div>`;
            return;
        }

        const listHtml = this.openOrders.map(order => {
            const isPendingOrder = order.status === 'pending';
            const actionMethod = isPendingOrder ? 'editBill' : 'editOrder';

            return `
                <div onclick="BistroTillApp.${actionMethod}(${order.order_id})" 
                    class="w-full bg-slate-800 border border-slate-700 hover:border-blue-500/50 hover:bg-slate-700/60 rounded-xl p-3.5 mb-3 flex justify-between items-center shadow-sm cursor-pointer transition group">
                    
                    <!-- Información general -->
                    <div class="text-left">
                        <h6 class="font-bold text-white text-sm mb-0.5 flex items-center gap-2 group-hover:text-blue-400 transition">
                            ${order.table || 'Mesa'}
                            ${isPendingOrder
                    ? `<span class="text-[10px] bg-amber-500/20 text-amber-400 font-bold px-2 py-0.5 rounded-full border border-amber-500/30">Pidió Cuenta</span>`
                    : `<span class="text-[10px] bg-blue-500/20 text-blue-400 font-bold px-2 py-0.5 rounded-full border border-blue-500/30">En Servicio</span>`
                }
                        </h6>
                        <span class="text-xs text-slate-400 block">
                            Folio: <strong class="text-slate-300">#${order.folio}</strong> | Atendió: ${order.waiter} | Personas: ${order.people}
                        </span>
                    </div>
                    
                    <!-- Monto e indicador de acción -->
                    <div class="flex items-center gap-3 text-right">
                        <div>
                            <span class="block text-emerald-400 font-extrabold text-base">$${toCurrency(order.amount || 0)}</span>
                            <span class="text-[10px] text-slate-400 block">${order.items} ítems</span>
                        </div>

                        <i class="bi bi-chevron-right text-slate-500 group-hover:text-blue-400 transition text-lg"></i>
                    </div>

                </div>
            `;
        }).join('');

        container.innerHTML = listHtml;
    },

    renderBills: function (filteredBills = null) {
        const container = document.getElementById('bistro-till-pending-list');
        if (!container) return;

        const bills = filteredBills || this.bills;
        container.innerHTML = '';

        if (!this.isOpen) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center text-slate-500 py-16 text-center">
                    <i class="bi bi-lock text-5xl mb-3"></i>
                    <p class="text-sm font-semibold pb-2">La caja está cerrada.</p>
                    <p class="text-xs text-slate-400">Abra un turno para gestionar los pedidos.</p>
                </div>`;
            return;
        }

        if (!bills || bills.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center text-slate-500 py-16 text-center">
                    <i class="bi bi-receipt text-4xl mb-2"></i>
                    <p class="text-sm">No hay pedidos registrados en este turno.</p>
                </div>`;
            return;
        }

        // Renderizado dinámico de cuentas disponibles
        const listHtml = bills.map(bill => `
            <div onclick="BistroTillApp.${this.filter === 'pending' ? 'openPayModal' : 'viewBill'}(${bill.id})" 
                class="w-full bg-slate-800 border border-slate-700 hover:border-blue-500/50 hover:bg-slate-700/60 rounded-xl p-4 mb-3 flex justify-between items-center shadow-sm cursor-pointer transition group">
                
                <div class="text-left">
                    <h6 class="font-bold text-white text-sm mb-1 group-hover:text-blue-400 transition">${bill.name || 'Cuenta'}</h6>
                    <span class="text-xs text-slate-400 block">Folio: <strong class="text-slate-300">${bill.folio}</strong> | Atendió: ${bill.waiter} | Cuenta: #${bill.index}</span>
                </div>

                <div class="text-right flex items-center gap-3">
                    <div>
                        <span class="block text-emerald-400 font-extrabold text-base">$${toCurrency(bill.amount || 0)}</span>
                        ${bill.discount > 0 ? `<span class="text-[10px] text-rose-400 block">Desc: -$${toCurrency(bill.discount)}</span>` : ''}
                    </div>
                    
                    <i class="bi bi-chevron-right text-slate-500 group-hover:text-blue-400 transition text-lg"></i>
                </div>

            </div>
        `).join('');

        container.innerHTML = listHtml;
    },

    // ==========================================
    // OPERACIONES DE RESTAURANTE (GESTIÓN)
    // ==========================================
    openAccountManage: function (billId) {
        if (!this.isOpen) return;
        const bill = this.bills.find(bill => bill.id === billId);
        if (!bill) return;

        IxeaComponents.openModal('bistro-till-tpl-account-manage', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-full max-w-md',
            onOpen: () => {
                document.getElementById('manage-account-folio').textContent = bill.folio;
                document.getElementById('manage-account-table').textContent = `Mesa ${bill.table_name}`;

                const tbody = document.getElementById('manage-account-items');
                tbody.innerHTML = bill.items.map(item => `
                    <tr>
                        <td class="p-2 break-words">
                            ${item.qty}x ${item.name}
                            ${item.target > 0 ? `<span class="text-[10px] bg-slate-700 px-1 rounded ml-1">P${item.target}</span>` : ''}
                        </td>
                        <td class="p-2 text-right text-emerald-400 font-mono">${toCurrency(item.price)}</td>
                        <td class="p-2 text-center flex flex-col gap-1">
                            <button onclick="BistroTillApp.moveItemTarget('${bill.id}', '${item.id}')" class="text-[10px] bg-indigo-600 hover:bg-indigo-500 text-white px-2 py-1 rounded">Reasignar</button>
                            <button onclick="BistroTillApp.applyDiscountModal('item', '${bill.id}', '${item.id}')" class="text-[10px] bg-amber-600 hover:bg-amber-500 text-white px-2 py-1 rounded">Desc.</button>
                        </td>
                    </tr>
                `).join('');
            }
        });
    },

    reopenAccount: async function () {
        if (!this.isOpen) return;
        IxeaComponents.showConfirm({
            title: 'Reabrir Cuenta',
            text: '¿Deseas devolver esta cuenta a la mesa para seguir agregando productos?',
            icon: 'question',
            onConfirm: async () => {
                IxeaComponents.showAlert({ text: 'Cuenta reabierta y asignada a la mesa.', icon: 'success' });
                IxeaComponents.closeModal();
                this.fetchBills();
            }
        });
    },

    cancelAccount: async function () {
        if (!this.isOpen) return;
        IxeaComponents.showConfirm({
            title: 'Cancelar Cuenta',
            text: '¿Estás seguro de cancelar toda la orden? Esta acción requiere justificación.',
            icon: 'warning',
            onConfirm: async () => {
                IxeaComponents.showAlert({ text: 'Cuenta cancelada.', icon: 'info' });
                IxeaComponents.closeModal();
                this.fetchBills();
            }
        });
    },

    moveItemTarget: function (accountId, itemId) {
        if (!this.isOpen) return;
        let targetStr = prompt("Ingrese el nuevo número de comensal (0 para Centro):", "0");
        if (targetStr !== null) {
            IxeaComponents.showAlert({ text: `Ítem reasignado al comensal ${targetStr}`, icon: 'success' });
            IxeaComponents.closeModal();
        }
    },

    applyDiscountModal: function (type, accountId = null, itemId = null) {
        if (!this.isOpen) return;
        let discount = prompt(`Ingrese el porcentaje de descuento ${type === 'global' ? 'para la cuenta' : 'para el ítem'}:`, "10");
        if (discount) {
            IxeaComponents.showAlert({ text: `Descuento del ${discount}% aplicado correctamente.`, icon: 'success' });
            IxeaComponents.closeModal();
            this.fetchBills();
        }
    },

    // ==========================================
    // COBRO Y PROPINAS (PAYMENT)
    // ==========================================
    openPayModal: function (accountId) {
        if (!this.isOpen) {
            IxeaComponents.showAlert({ text: 'La caja debe estar abierta para cobrar.', icon: 'warning' });
            return;
        }

        const bill = this.bills.find(b => b.id === accountId);
        if (!bill) return;

        this.activePayBill = bill;

        IxeaComponents.openModal('pay', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-full max-w-lg', // Modal compacto
            onOpen: () => {
                const folioEl = document.getElementById('bistro-till-pay-folio');
                const totalEl = document.getElementById('bistro-till-pay-total-display');
                const tipEl = document.getElementById('bistro-till-pay-tip');
                const cashEl = document.getElementById('bistro-till-pay-cash-received');
                const refEl = document.getElementById('bistro-till-pay-reference');
                const changeEl = document.getElementById('bistro-till-pay-change-display');

                if (folioEl) folioEl.textContent = `${bill.folio}: ${bill.name}`;
                if (totalEl) totalEl.textContent = `$${toCurrency(bill.amount || 0)}`;
                if (tipEl) tipEl.value = '';
                if (cashEl) cashEl.value = '';
                if (refEl) refEl.value = '';
                if (changeEl) {
                    changeEl.textContent = '$0.00';
                    changeEl.className = 'font-mono font-bold text-slate-500 text-base';
                }

                // Seleccionar automáticamente el primer botón por defecto (Efectivo)
                const firstBtn = document.querySelector('#bistro-till-pay-methods-grid .pay-method-btn');
                if (firstBtn) {
                    this.selectPayMethod(firstBtn);
                }
            }
        });
    },

    selectPayMethod: function (btnElement) {
        if (!btnElement) return;

        const methodId = btnElement.getAttribute('data-id');
        const code = btnElement.getAttribute('data-code');

        // 1. Asignar el ID al input oculto
        const hiddenInput = document.getElementById('bistro-till-pay-method');
        if (hiddenInput) hiddenInput.value = methodId;

        // 2. Actualizar estado visual de los botones
        document.querySelectorAll('#bistro-till-pay-methods-grid .pay-method-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'bg-blue-600/20', 'ring-2', 'ring-blue-500/50');
            btn.classList.add('border-slate-700', 'bg-slate-900');
        });

        btnElement.classList.remove('border-slate-700', 'bg-slate-900');
        btnElement.classList.add('border-blue-500', 'bg-blue-600/20', 'ring-2', 'ring-blue-500/50');

        // 3. Alternar contenedores según el código
        const refContainer = document.getElementById('bistro-till-pay-ref-container');
        const cashContainer = document.getElementById('bistro-till-pay-cash-container');

        // Limpiar inputs
        const cashInput = document.getElementById('bistro-till-pay-cash-received');
        if (cashInput) cashInput.value = '';
        const refInput = document.getElementById('bistro-till-pay-reference');
        if (refInput) refInput.value = '';

        // Ocultar ambos contenedores
        const tipInput = document.getElementById('bistro-till-pay-tip');
        if (tipInput) tipInput.readOnly = false;
        if (refContainer) refContainer.classList.add('hidden');
        if (cashContainer) cashContainer.classList.add('hidden');

        // Mostrar contenedor correspondiente
        if (code === 'cash') {
            if (cashContainer) cashContainer.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('bistro-till-pay-cash-received').focus();
            }, 100);
        } else if (code !== 'uber' && code !== 'didi' && code !== 'rappi') {
            if (refContainer) refContainer.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('bistro-till-pay-reference').focus();
            }, 100);
        } else {
            tipInput.value = '';
            tipInput.readOnly = true;
        }
        this.calculateChange();
    },

    setTipPercentage: function (percent) {
        if (!this.activePayBill) return;

        const totalBase = parseFloat(this.activePayBill.amount || 0);
        const tip = totalBase * (percent / 100);

        const tipInput = document.getElementById('bistro-till-pay-tip');
        if (tipInput) tipInput.value = tip.toFixed(2);

        this.calculateChange();
    },

    calculateChange: function () {
        if (!this.activePayBill) return;

        const hiddenInput = document.getElementById('bistro-till-pay-method');
        const selectedBtn = document.querySelector(`#bistro-till-pay-methods-grid button[data-id="${hiddenInput?.value}"]`);
        const code = selectedBtn ? selectedBtn.getAttribute('data-code') : '';

        const totalBase = parseFloat(this.activePayBill.amount || 0);
        const tipInput = parseFloat(document.getElementById('bistro-till-pay-tip')?.value) || 0;
        const finalTotal = totalBase + tipInput;

        const totalDisplay = document.getElementById('bistro-till-pay-total-display');
        if (totalDisplay) totalDisplay.textContent = `$${toCurrency(finalTotal)}`;

        if (code === 'cash') {
            const received = parseFloat(document.getElementById('bistro-till-pay-cash-received')?.value) || 0;
            const change = received - finalTotal;
            const changeDisplay = document.getElementById('bistro-till-pay-change-display');

            if (changeDisplay) {
                if (change >= 0 && received > 0) {
                    changeDisplay.textContent = `$${toCurrency(change)}`;
                    changeDisplay.className = 'font-mono font-bold text-emerald-400 text-lg';
                } else {
                    changeDisplay.textContent = `$${toCurrency(0)}`;
                    changeDisplay.className = 'font-mono font-bold text-slate-500 text-base';
                }
            }
        }
    },

    confirmPayment: async function () {
        if (!this.activePayBill) return;

        const hiddenInput = document.getElementById('bistro-till-pay-method');
        const methodId = hiddenInput ? parseInt(hiddenInput.value) : 0;

        const selectedBtn = document.querySelector(`#bistro-till-pay-methods-grid button[data-id="${methodId}"]`);
        const code = selectedBtn ? selectedBtn.getAttribute('data-code') : '';
        const isCash = (code === 'cash');

        const referenceInput = document.getElementById('bistro-till-pay-reference');
        const reference = referenceInput ? referenceInput.value.trim() : '';

        const tipRaw = parseFloat(document.getElementById('bistro-till-pay-tip')?.value) || 0;
        const tip = Math.max(0, Math.round(tipRaw * 100) / 100);

        const receivedRaw = parseFloat(document.getElementById('bistro-till-pay-cash-received')?.value) || 0;
        const received = Math.max(0, Math.round(receivedRaw * 100) / 100);

        const baseAmount = parseFloat(this.activePayBill.amount || 0);
        const totalToPay = Math.round((baseAmount + tip) * 100) / 100;

        // Validaciones en el cliente
        if (!methodId || methodId <= 0) {
            IxeaComponents.showAlert({
                title: 'Atención',
                text: 'Seleccione un método de pago válido.',
                icon: 'warning'
            });
            return;
        }

        if (isCash) {
            if (received <= 0) {
                IxeaComponents.showAlert({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Ingrese el monto de efectivo recibido.'
                });
                return;
            }

            if (received < totalToPay) {
                IxeaComponents.showAlert({
                    icon: 'error',
                    title: 'Efectivo Insuficiente',
                    text: `El monto recibido ($${received.toFixed(2)}) es menor al total a cobrar ($${totalToPay.toFixed(2)}).`
                });
                return;
            }
        } else if (code !== 'uber' && code !== 'didi' && code !== 'rappi') {
            if (!reference || reference.length !== 4 || !/^\d{4}$/.test(reference)) {
                IxeaComponents.showAlert({
                    icon: 'warning',
                    title: 'Referencia Inválida',
                    text: 'Debe ingresar exactamente 4 dígitos numéricos de referencia para este método de pago.'
                });
                return;
            }
        }

        try {
            const payload = {
                payment_id: parseInt(this.activePayBill.id),
                method_id: methodId,
                reference: reference,
                tip: tip,
                received: received
            };

            const res = await IxeaBridge.post('/api/v1/bistro-till/pay-bill', payload);

            if (res && res.success) {
                IxeaComponents.showAlert({
                    title: 'Cobro Procesado',
                    text: res.message || 'El pago fue registrado con éxito.',
                    icon: 'success',
                    timer: 2000
                });
                if (res.data) {
                    this.billsHash = res.hash;
                    this.bills = res.data.bills;
                    this.shift.balance = res.data.shift.balance;
                    IxeaData.timestamps['bistro-till']['get-bills'] = this.billsHash;
                    IxeaData.cache['bistro-till']['get-bills'] = this.bills;

                    this.renderBills();
                } else {
                    await this.checkStatus();
                }
                IxeaComponents.closeModal();
            } else {
                IxeaComponents.showAlert({
                    icon: 'error',
                    title: 'Error',
                    text: res?.error || 'Error al procesar el cobro.'
                });
            }
        } catch (err) {
            IxeaComponents.showAlert({
                icon: 'error',
                title: 'Error',
                text: err.message || 'Error en la conexión.'
            });
        }
    },

    cashMovement: function (type) {
        if (!this.isOpen) {
            IxeaComponents.showAlert({ text: 'La caja debe estar abierta para realizar movimientos.', icon: 'warning' });
            return;
        }

        IxeaComponents.openModal('movement', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-80',
            onOpen: () => {
                const icon = document.getElementById('bistro-till-mov-icon');
                const title = document.getElementById('bistro-till-mov-title');
                const concept = document.getElementById('bistro-till-mov-concept');
                document.getElementById('bistro-till-mov-type').value = type;

                if (type === 'in') {
                    if (icon) icon.innerHTML = '<i class="bi bi-plus-circle-fill d-block fs-3 text-success mb-1"></i>';
                    if (title) title.textContent = 'Entrada de Efectivo';
                    if (concept) concept.placeholder = 'Ej. Cambio para caja';
                } else {
                    if (icon) icon.innerHTML = '<i class="bi bi-dash-circle-fill d-block fs-3 text-danger mb-1"></i>';
                    if (title) title.textContent = 'Salida de Efectivo';
                    if (concept) concept.placeholder = 'Ej. Compra de energencia';
                    const category = document.getElementById('bistro-till-mov-category');
                    if (category) category.classList.remove('hidden');
                }
            }
        });
    },

    toggleProviderField: function () {
        const category = document.getElementById('bistro-till-mov-category');
        //const provider = document.getElementById('bistro-till-mov-provider');
        if (category.value === 'supplier_payment') {
            //provider.classList.remove('hidden');
        } else {
            //provider.classList.add('hidden');
        }
    },

    confirmMovement: async function () {
        const typeInput = document.getElementById('bistro-till-mov-type');
        const amountInput = document.getElementById('bistro-till-mov-amount');
        const conceptInput = document.getElementById('bistro-till-mov-concept');
        const categoryInput = document.getElementById('bistro-till-mov-category');

        const type = typeInput?.value || 'in';
        const amount = parseFloat(amountInput?.value || 0);
        const concept = conceptInput?.value?.trim() || '';
        const categoryId = categoryInput ? parseInt(categoryInput.value) : null;

        if (amount <= 0) {
            IxeaComponents.showAlert({ text: 'Ingresa un monto válido mayor a cero.', icon: 'warning' });
            return;
        }

        if (!concept) {
            IxeaComponents.showAlert({ text: 'El concepto del movimiento es obligatorio.', icon: 'warning' });
            return;
        }

        if (type === 'out' && (!categoryId || !categoryId)) {
            IxeaComponents.showAlert({ text: 'Selecciona una categoría para la salida de efectivo.', icon: 'warning' });
            return;
        }

        try {
            IxeaComponents.showLoading({ text: 'Registrando movimiento...' });

            const payload = {
                type: type,
                amount: amount,
                concept: concept,
                category_id: categoryId // Solo aplica si es 'out'
            };

            const res = await IxeaBridge.post('/api/v1/bistro-till/cash-movement', payload);

            IxeaComponents.hideLoading();
            IxeaComponents.closeModal();
            if (!res.success) {
                throw new Error(res.message || 'Error al registrar el movimiento.');
            }

            IxeaComponents.showAlert({ text: 'Movimiento registrado con éxito.', icon: 'success', timer: 1500 });
            this.checkTillStatus();

        } catch (err) {
            IxeaComponents.hideLoading();
            IxeaComponents.showAlert({ text: err.message || 'Error de comunicación.', icon: 'error' });
        }
    }

};