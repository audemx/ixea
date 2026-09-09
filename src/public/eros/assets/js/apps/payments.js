/**
 * /modules/payments/payments.js
 * Gestión de Tesorería IXEA EROS
 */
window.PaymentsApp = {
    isInitialized: false,
    currentFilter: 'day',
    currentView: 'pending',
    query: '',
    accounts: [],
    purchases: [],
    mappingAccounts: {
        'cash': 'Caja',
        'main_cash': 'C. Efectivo',
        'bank': 'C. Bancaria',
        'credit_card': 'T. Crédito',
        'card': 'Terminal'
    },
    mappingMethods: {
        'transfer': 'Transferencia',
        'main_cash': 'Efectivo',
        'deposit': 'Deposito',
        'check': 'Cheque',
        'credit_card': 'T. Crédito'
    },
    mappingMA: {
        'transfer': 'bank',
        'main_cash': 'main_cash',
        'deposit': 'main_cash',
        'check': 'bank',
        'credit_card': 'credit_card'
    },

    init: async function () {
        if (this.isInitialized) return;

        this.renderAppActions();
        await this.loadBalance();
        await this.loadList();

        this.isInitialized = true;
    },

    renderAppActions: function () {
        const menuFilters = document.getElementById('menu-app-filters');
        if (menuFilters) {
            // Obtenemos el nombre amigable del filtro actual
            const filterNames = {
                'llast_month': 'Mes Antepasado',
                'last_month': 'Mes Pasado',
                'day': 'Hoy',
                'week': 'Esta Semana',
                'month': 'Este Mes',
                'next_month': 'Siguiente Mes'
            };
            const activeLabel = filterNames[this.currentFilter] || 'Hoy';

            menuFilters.innerHTML = `
                <div class="dropdown">
                    <div class="menu-item cursor-pointer text-sforange fw-bold" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar3 me-2"></i>Vista: ${activeLabel}
                    </div>
                    <ul class="dropdown-menu shadow border-0 mt-2">
                        <li><a class="dropdown-item small d-flex justify-content-between" onclick="PaymentsApp.filter('llast_month')">
                            Mes Antepasado ${this.currentFilter === 'llast_month' ? '<i class="bi bi-check text-sforange"></i>' : ''}
                        </a></li>
                        <li><a class="dropdown-item small d-flex justify-content-between" onclick="PaymentsApp.filter('last_month')">
                            Mes Pasado ${this.currentFilter === 'last_month' ? '<i class="bi bi-check text-sforange"></i>' : ''}
                        </a></li>
                        <li><a class="dropdown-item small d-flex justify-content-between" onclick="PaymentsApp.filter('day')">
                            Hoy ${this.currentFilter === 'day' ? '<i class="bi bi-check text-sforange"></i>' : ''}
                        </a></li>
                        <li><a class="dropdown-item small d-flex justify-content-between" onclick="PaymentsApp.filter('week')">
                            Esta Semana ${this.currentFilter === 'week' ? '<i class="bi bi-check text-sforange"></i>' : ''}
                        </a></li>
                        <li><a class="dropdown-item small d-flex justify-content-between" onclick="PaymentsApp.filter('month')">
                            Este Mes ${this.currentFilter === 'month' ? '<i class="bi bi-check text-sforange"></i>' : ''}
                        </a></li>
                        <li><a class="dropdown-item small d-flex justify-content-between" onclick="PaymentsApp.filter('next_month')">
                            Siguiente Mes ${this.currentFilter === 'next_month' ? '<i class="bi bi-check text-sforange"></i>' : ''}
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small" onclick="PaymentsApp.loadData()">
                            <i class="bi bi-refresh me-2"></i>Actualizar
                        </a></li>
                        
                    </ul>
                </div>
            `;
        }

        const menuActions = document.getElementById('menu-app-actions');
        if (!menuActions) return;
        menuActions.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">Operaciones</div>
                <ul class="dropdown-menu shadow border-0 mt-2">
                    <li><a class="dropdown-item small" onclick="PaymentsApp.showAccountTransfers()">
                        <i class="bi bi-arrow-left-right text-sforange me-2"></i>Traspasos
                    </a></li>
                    <li><a class="dropdown-item small" onclick="PaymentsApp.showGeneralExpense()">
                        <i class="bi bi-plus-circle me-2 text-sfred"></i>Gasto
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small" onclick="PaymentsApp.showExpensesHistory()">
                        <i class="bi bi-arrow-down-circle me-2 text-sfred"></i>Egresos
                    </a></li>
                </ul>
            </div>
        `;
    },

    filter: function (period) {
        this.currentFilter = period;
        this.renderAppActions();
        this.loadData();
    },

    loadBalance: async function () {
        const period = this.currentFilter || 'day';

        try {
            const url = `/api/v1/payments/get-balances?period=${period}`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                if (data.balances) {
                    this.accounts = data.balances;
                    this.updateBalances();
                }
            } else {
                throw new Error(data.message || "Error desconocido");
            }

        } catch (err) {
            console.error("Error cargando Dashboard de Pagos:", err);
        }
    },

    loadList: async function () {
        const period = this.currentFilter || 'day';
        const query = this.query || '';
        const view = this.currentView || 'pending';

        try {
            const url = `/api/v1/payments/get-purchases?period=${period}&query=${query}&payment_status=${view}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error(`Error en servidor: ${res.status}`);

            const data = await res.json();
            if (data.success) {
                if (data.purchases) {
                    this.purchases = data.purchases;
                    this.renderList();
                }
            } else {
                throw new Error(data.message || "Error desconocido");
            }

        } catch (err) {
            console.error("Error cargando Dashboard de Pagos:", err);
        }
    },

    loadData: function () {
        this.loadBalance();
        this.loadList();
    },

    updateBalances: function () {
        const balances = this.accounts;
        if (!balances) return;

        // Iteramos sobre los balances que recibimos del servidor
        Object.values(balances).forEach(data => {
            // Usamos el account_code para encontrar los elementos en el DOM
            // Ejemplo: si el code es 'main_cash', buscará 'pay-net-main_cash'
            const key = data.code;

            const netEl = document.getElementById(`pay-net-${key}`);
            const inEl = document.getElementById(`pay-in-${key}`);
            const outEl = document.getElementById(`pay-out-${key}`);

            const bNet = data.net || 0;
            const bIn = data.in || 0;
            const bOut = data.out || 0;
            // Actualizamos los indicadores visuales (los KPIs del dashboard)
            if (netEl) netEl.innerText = bNet < 0 ? `-$ ${toCurrency(-bNet)}` : `$ ${toCurrency(bNet)}`;
            if (inEl) inEl.innerText = bIn < 0 ? `-$ ${toCurrency(-bIn)}` : `$ ${toCurrency(bIn)}`;
            if (outEl) outEl.innerText = bOut < 0 ? `-$ ${toCurrency(-bOut)}` : `$ ${toCurrency(bOut)}`;
        });
    },

    switchView: function (view) {
        this.currentView = view;

        document.querySelectorAll('#payments-tabs .nav-link').forEach(link => link.classList.remove('active'));
        document.getElementById(`payments-${view}`).classList.add('active');

        this.loadList();
        this.renderList();
    },

    handleSearch: function (value) {
        if (value.length < 3 && value != '') return;
        this.query = value.toLowerCase().trim();
        this.loadList();
        this.renderList();
    },

    renderList: function () {
        const purchases = this.purchases;
        if (!purchases) return Swal.fire('Atención', 'Base de datos de compras vacia.', 'warning');

        const container = document.getElementById('pending-purchases-list');
        if (!container) return;

        if (purchases.length === 0) {
            container.innerHTML = `<div class="p-5 text-center opacity-50">No hay registros para mostrar.</div>`;
            return;
        }

        let html = `
            <table class="table table-hover align-middle mb-0 small">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">Folio</th>
                        <th>Proveedor</th>
                        <th class='text-center'>Fecha Orden</th>
                        <th class='text-center'>Fecha Recepción</th>
                        <th class='text-center'>Fecha Pago</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>`;

        purchases.forEach(p => {
            const isTaxable = p.is_taxable === 1;
            const cancelled = p.payment_status === 'cancelled' || p.payment_status === 'returned';

            let total = p.total_amount;
            if (isTaxable) total *= 1.16;

            html += `
                ${this.currentView !== 'pending' ?
                    `<tr ${cancelled ? 'disabled' : `onclick="PaymentsApp.openDetailsModal(${p.purchase_id})"`}>` :
                    `<tr onclick="PaymentsApp.openPaymentModal(${p.purchase_id})"}>`
                }
                    <td class="ps-3 fw-bold text-sforange">${p.folio}</td>
                    <td>
                        <div class="fw-bold">${p.company_name}</div>
                        <div class="text-muted text-xs">${p.rfc}</div>
                    </td>
                    <td class="text-center text-sfblue">${p.operation_date}</td>
                    ${cancelled ? `<td class="text-center text-grey">Cancelado</td>` : `<td class="text-center text-sforange">${p.received_date}</td>`}
                    ${this.currentView !== 'pending' ?
                    `<td class="text-center ${cancelled ? 'text-grey' : 'text-sfgreen'}">${cancelled ? 'Cancelado' : p.payment_date}</td>` :
                    `<td class="text-center text-sfred">${p.due_date}</td>`
                }
                    <td class="text-end fw-bold text-sfred">$${toCurrency(total)}</td>
                </tr>`;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    },

    syncSelectBalances: function (selectId) {
        const selectEl = document.getElementById(selectId);
        if (!selectEl || !this.accounts) return;

        Array.from(selectEl.options).forEach(opt => {
            if (!opt.value) return;

            // Buscamos por ID de cuenta (ya sea en data-account o el valor directo)
            const optText = opt.innerText;
            const accId = opt.dataset.account;
            const accData = this.accounts[accId];

            if (accData) {
                // Actualizamos el texto: "Nombre Cuenta ($ 1,234.56)"
                opt.text = `${optText} ($${toCurrency(accData.net)})`;
            }
        });
    },

    openPaymentModal: function (purchaseId) {
        // 1. Buscar la compra en la lista de pendientes cargada
        const purchase = this.purchases.find(p => p.purchase_id == purchaseId);
        if (!purchase) return Swal.fire('Error', 'No se encontró la información de la compra.', 'error');

        // 2. Abrir el modal usando tu sistema de stages
        IxeaStages.openModal({
            templateId: 'tpl-payment-form',
            size: 'md',
            onOpen: () => {
                this.syncSelectBalances('payments-pay-method');

                // Llenar datos de la compra
                const isTaxable = purchase.is_taxable === 1;
                let total = purchase.total_amount;
                if (isTaxable) total *= 1.16;

                document.getElementById('payments-pay-folio').innerText = purchase.folio;
                document.getElementById('payments-pay-total').innerText = `$${toCurrency(total)}`;


                const btn = document.getElementById('payments-btnConfirmPayment');
                btn.setAttribute('onclick', `PaymentsApp.confirmPayment(${purchaseId})`);
            }
        });
    },

    confirmPayment: async function (purchaseId) {
        const purchase = this.purchases.find(p => p.purchase_id == purchaseId);
        if (!purchase) return Swal.fire('Error', 'No se encontró la información de la compra.', 'error');

        const isTaxable = purchase.is_taxable === 1;
        let total = purchase.total_amount;
        if (isTaxable) total *= 1.16;

        const methodEl = document.getElementById('payments-pay-method');
        const selectedOpt = methodEl.selectedOptions[0];
        const methodId = selectedOpt.dataset.method;
        const accountId = selectedOpt.dataset.account;
        const reference = document.getElementById('payments-pay-reference').value;

        // Validaciones
        if (!methodId) return Swal.fire('Error', 'Método de pago no encontrado.', 'error');

        const accountData = this.accounts[accountId];
        if (!accountData) return Swal.fire('Error', 'Cuenta origen no encontrada.', 'error');

        // Validación de saldo para Activos (Caja/Bancos)
        if (accountData.type === 'asset') {
            if (total > accountData.net) {
                return Swal.fire('Saldo Insuficiente', `No hay fondos suficientes en ${accountData.name}.`, 'error');
            }
        }

        const btn = document.getElementById('payments-btnConfirmPayment');
        if (!IxeaBouncer.lockBtn(btn)) return;

        try {
            const res = await fetch('/api/v1/payments/confirm-payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    purchase_id: purchaseId,
                    method: methodId,
                    account: accountId,
                    reference: reference
                })
            });

            const data = await res.json();
            if (data.success) {
                Swal.fire('¡Éxito!', 'Pago registrado correctamente', 'success');
                IxeaStages.closeModal();
                this.loadData(); // O this.loadBalance() según tu flujo
            } else {
                throw new Error(data.message || 'No se pudo procesar el pago');
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        } finally {
            IxeaBouncer.releaseBtn(btn);
        }
    },

    showAccountTransfers: function () {
        IxeaStages.openModal({
            templateId: 'tpl-account-transfer',
            size: 'md',
            onOpen: () => {
                this.syncSelectBalances('payments-transfer-origin');
                this.syncSelectBalances('payments-transfer-destination');

                const btn = document.getElementById('payments-btnConfirmTransfer');
                if (!btn) return Swal.fire('Error', 'Error en la carga. Actualiza la app.', 'error');
                btn.setAttribute('onclick', `PaymentsApp.confirmTransfer(origin,destination)`);
            }
        });
    },

    handleTransferUI: function () {
        const originEl = document.getElementById('payments-transfer-origin');
        const destEl = document.getElementById('payments-transfer-destination');
        const btn = document.getElementById('payments-btnConfirmTransfer');

        if (!originEl || !destEl || !btn) return;

        const originCode = originEl.value; // Ya es 'bank', 'cash_office', etc.
        const destCode = destEl.value;

        // REGLA: Si el origen es 'terminal' (o 'card'), el destino es forzoso 'bank'
        // Nota: Según tu PHP, 'terminal' no aparece en Source, pero si llegara a estar:
        if (originCode === 'terminal') {
            destEl.value = 'bank';
            destEl.disabled = true;
        } else {
            destEl.disabled = false;
        }

        // Bloqueo de confirmación si son iguales
        if (originCode === destCode) {
            btn.classList.add('disabled', 'opacity-50');
            btn.style.pointerEvents = 'none';
            btn.onclick = null;
        } else {
            btn.classList.remove('disabled', 'opacity-50');
            btn.style.pointerEvents = 'auto';
            btn.onclick = () => this.confirmTransfer();
        }
    },

    confirmTransfer: async function () {
        const originEl = document.getElementById('payments-transfer-origin');
        const destEl = document.getElementById('payments-transfer-destination');

        // Sacamos los IDs reales del dataset
        const originId = originEl.selectedOptions[0].dataset.account;
        const destId = destEl.selectedOptions[0].dataset.account;

        const amount = parseFloat(document.getElementById('payments-transfer-amount').value);
        const reference = document.getElementById('payments-transfer-reference').value;

        // Validaciones de negocio
        if (isNaN(amount) || amount <= 0) return Swal.fire('Atención', 'Ingresa un monto válido.', 'warning');
        if (originId === destId) return Swal.fire('Error', 'No puedes transferir a la misma cuenta.', 'error');

        // Validación de saldo usando el ID
        const balance = this.accounts[originId]?.net || 0;
        if (amount > balance) return Swal.fire('Atención', 'Saldo insuficiente.', 'error');

        const btn = document.getElementById('payments-btnConfirmTransfer');
        if (!IxeaBouncer.lockBtn(btn)) return;

        // Nombres para el concepto
        const oriName = originEl.selectedOptions[0].text.split(' ($')[0];
        const dstName = destEl.selectedOptions[0].text.split(' ($')[0];

        try {
            const res = await fetch('/api/v1/payments/store-transfer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    origin: originId,      // Enviamos el ID numérico
                    dest: destId,   // Enviamos el ID numérico
                    amount: amount,
                    reference: reference,
                    concept: `Traspaso: ${oriName} -> ${dstName}`
                })
            });

            const data = await res.json();
            if (data.success) {
                Swal.fire('¡Éxito!', 'Traspaso realizado correctamente.', 'success');
                IxeaStages.closeModal();
                this.loadBalance(); // Recargar KPIs y saldos
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        } finally {
            IxeaBouncer.releaseBtn(btn);
        }
    },

    showGeneralExpense: function () {
        IxeaStages.openModal({
            templateId: 'tpl-general-expense-form',
            size: 'md',
            onOpen: () => {
                this.syncSelectBalances('payments-expense-method');

                const btn = document.getElementById('payments-btnConfirmExpense');
                btn.onclick = () => this.confirmExpense();
            }
        });
    },

    confirmExpense: async function () {
        const methodEl = document.getElementById('payments-expense-method');
        const selectedOpt = methodEl.selectedOptions[0];
        const method = selectedOpt.value;
        const methodId = selectedOpt.dataset.method;
        const accountId = selectedOpt.dataset.account;

        const body = {
            category: document.getElementById('payments-expense-category').value,
            deductible: document.getElementById('payments-expense-deductible').checked ? 1 : 0,
            concept: document.getElementById('payments-expense-concept').value,
            amount: parseFloat(document.getElementById('payments-expense-amount').value),
            method: methodId,
            account: accountId,
            reference: document.getElementById('payments-expense-reference').value
        };

        // Validaciones
        if (!body.category) return Swal.fire('Atención', 'La categoría es obligatoria.', 'warning');
        if (!body.concept) return Swal.fire('Atención', 'El concepto es obligatorio.', 'warning');
        if (isNaN(body.amount) || body.amount <= 0) return Swal.fire('Atención', 'Monto no válido.', 'warning');
        if (!body.account) return Swal.fire('Atención', 'Selecciona el método de pago.', 'warning');

        const balance = this.accounts[body.account]?.net || 0;

        if (method === 'credit_card') { // Validación de crédito
            const creditLimit = this.accounts[body.account]?.limit || 99999; // Definir valor límite de crédito
            if (body.amount > creditLimit) return Swal.fire('Atención', 'Saldo insuficiente en la cuenta seleccionada.', 'error');

        } else if (body.amount > balance) { // Validación de saldo
            return Swal.fire('Atención', 'Saldo insuficiente en la cuenta seleccionada.', 'error');
        }

        const btn = document.getElementById('payments-btnConfirmExpense');
        if (!IxeaBouncer.lockBtn(btn)) return;

        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch('/api/v1/payments/store-expense', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            const data = await res.json();
            if (data.success) {
                Swal.fire('¡Éxito!', 'Gasto registrado correctamente.', 'success');
                IxeaStages.closeModal();
                this.loadBalance();
            } else {
                throw new Error(data.message || 'Error al registrar gasto');
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        } finally {
            IxeaBouncer.releaseBtn(btn);
        }
    },

    showExpensesHistory: async function () {
        const activeStage = IxeaStages.activeStage;
        if (!activeStage || activeStage !== 'stage-payments') return;
        const mainCanvas = document.getElementById(activeStage);

        const overlayId = "ledger-overlay";
        let overlay = document.getElementById('overlayId');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = overlayId;
            overlay.className = "position-absolute top-0 start-0 w-100 h-100 bg-white z-3 animate__animated animate__fadeInUp overflow-auto";
        } else {
            mainCanvas.remove(overlay);
        }

        overlay.innerHTML = `
            <div class="container-fluid py-3">
                <div class="d-flex justify-content-between align-items-center mb-3 px-3">
                    <div>
                        <h2 class="fw-bold mb-0 text-sforange"><i class="bi bi-arrow-down-circle me-2"></i> Egresos</h2>
                    </div>
                    <button class="btn btn-light rounded-pill border shadow-sm" onclick="document.getElementById('ledger-overlay').remove()">
                        <i class="bi bi-arrow-left me-1"></i> Volver pagos
                    </button>
                </div>
    
                <div class="card border-0 shadow-sm rounded-4 mx-3 mb-3 bg-light">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-2 px-3">
                                <input type="date" id="filter-date" class="form-control form-control-sm border-0 shadow-none" onchange="PaymentsApp.loadUnifiedLedger()">
                            </div>
                            <div class="col-md-4 px-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search"></i></span>
                                    <input type="text" id="filter-search" class="form-control border-0 shadow-none" placeholder="Buscar concepto o referencia..." onkeyup="PaymentsApp.loadUnifiedLedger()">
                                </div>
                            </div>
                            <div class="col-md-3 px-3">
                                <select id="filter-account" class="form-select form-select-sm border-0 shadow-none" onchange="PaymentsApp.loadUnifiedLedger()">
                                    <option value="all">Todas las cuentas</option>
                                    ${Object.entries(this.mappingAccounts).map(([key, name]) => `<option value="${key}">${name}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm rounded-4 mx-3">
                    <div class="table-responsive" style="max-height: 65vh;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light sticky-top">
                                <tr class="small text-muted">
                                    <th class="ps-4">FECHA / HORA</th>
                                    <th>CONCEPTO / REFERENCIA</th>
                                    <th class="text-center">CUENTA</th>
                                    <th class="text-center">FISCAL</th>
                                    <th class="text-end">SALIDA</th>
                                    <th class="text-center pe-4">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="unified-ledger-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        mainCanvas.appendChild(overlay);
        this.loadUnifiedLedger();
    },

    loadUnifiedLedger: async function () {
        const tbody = document.getElementById('unified-ledger-body');
        const params = new URLSearchParams({
            account: document.getElementById('filter-account')?.value ?? 'all',
            ref_type: document.getElementById('filter-ref-type')?.value ?? 'all',
            type: 'out',
            date: document.getElementById('filter-date')?.value ?? '',
            search: document.getElementById('filter-search')?.value ?? ''
        });

        try {
            const res = await fetch(`/api/v1/payments/get-ledgers?${params.toString()}`);
            const data = await res.json();

            if (!data || !data.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted">No hay movimientos registrados</td></tr>`;
                return;
            }

            const outflows = ['global_accounts', 'expenses', 'purchases'];
            const movements = data.filter(m => outflows.includes(m.reference_type) ||
                (m.reference_type === 'till_movements' &&
                    (m.concept?.startsWith('Gasto') || m.concept?.startsWith('Pago'))
                )
            );

            // Mapeamos e inyectamos uniendo con un string vacío para evitar comas fantasmas
            tbody.innerHTML = movements.map(m => {
                // Separamos fecha y hora si vienen juntos en created_at del PHP
                const [date, time] = m.created_at ? m.created_at.split(' ') : ['', ''];

                // Definimos cancelados (Validando primero que m.concept no sea null/undefined)
                const isCancelled = m.concept && m.concept.startsWith('[CANCELADO]');

                // CORREGIDO: Se removió el paréntesis huerfano al final
                const amountClass = isCancelled
                    ? 'text-decoration-line-through opacity-50 text-muted fw-normal'
                    : 'text-sfred fw-bold';

                return `
                    <tr>
                        <td class="ps-4 small">
                            <div class="fw-bold">${date}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">${time || ''}</div>
                        </td>
                        <td>
                            <div class="fw-bold">${m.concept}</div>
                            <div class="d-flex align-items-center gap-2">
                                ${m.detail_label ? `
                                    <span class="text-sforange fw-bold" style="font-size: 0.65rem;">
                                        <i class="bi bi-dot"></i> ${m.detail_label}
                                    </span>` : ''}
                            </div>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-white text-dark border px-3">
                                ${(this.mappingAccounts[m.account] || m.account || '').toUpperCase()}
                            </span>
                        </td>
                        <td class="text-center">
                            <input class="form-check-input" type="checkbox" value="" ${m.is_taxable ? 'checked' : ''} disabled>
                        </td>
                        <td class="text-end ${amountClass}">
                            -$${toCurrency(m.amount)}
                        </td>
                        <td class="text-center pe-4">
                            ${m.reference_type === 'expenses' && !isCancelled ? `
                                <button class="btn btn-sm btn-outline-danger border-0" onclick="PaymentsApp.cancelMovement(event, '${m.reference_type}', ${m.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : '<i class="bi bi-shield-lock text-muted" title="Solo lectura"></i>'}
                        </td>
                    </tr>
                `;
            }).join(''); // Convierte el array resultante en una sola cadena HTML limpia

        } catch (err) {
            console.error("Error ledger:", err);
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger">Error al cargar datos</td></tr>`;
            }
        }
    },

    cancelMovement: async function (event, type, id) {
        // Encontrar el botón
        const btn = event.target.closest('.btn');
        if (!IxeaBouncer.lockBtn(btn)) return;

        // Alerta de confirmación
        const confirmation = await Swal.fire({
            title: '¿Cancelar este movimiento?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, mantener'
        });

        // Si el usuario cancela en el Swal, salimos sin hacer nada
        if (!confirmation.isConfirmed) return IxeaBouncer.releaseBtn(btn);

        // Preparar el body
        const body = { type, id };

        // Validaciones rápidas de seguridad
        if (!body.type || !body.id) {
            IxeaBouncer.releaseBtn(btn);
            return Swal.fire('Atención', 'Datos del movimiento incompletos.', 'warning');
        }

        // Loader estético de procesamiento
        Swal.fire({
            title: 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch('/api/v1/payments/cancel-movement', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            const data = await res.json();

            if (data.success) {
                await Swal.fire('¡Éxito!', 'Movimiento cancelado correctamente.', 'success');
                // Recargamos el balance/pantalla
                if (typeof this.loadBalance === 'function') {
                    this.loadBalance();
                    this.loadUnifiedLedger();
                }
            } else {
                throw new Error(data.message || 'Error en el proceso del servidor.');
            }
        } catch (err) {
            console.error("Error al cancelar movimiento:", err);
            Swal.fire('Error', err.message, 'error');
            // Si falló, liberamos el botón para que puedan reintentar
            IxeaBouncer.releaseBtn(btn);
        }
        // Nota: Quitamos el "releaseBtn" del finally de manera intencional si fue exitoso, 
        // ya que la fila o la vista se va a recargar con 'loadBalance()'.
    }
};