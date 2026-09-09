/** assets/js/apps/till.js **/
window.TillApp = {
    isShiftOpen: false,
    cashVisible: false,
    refreshTimer: null,
    lastOrderCount: 0,
    isInitialized: false,
    currentBalance: 0,

    // Inicializa app
    init: function() {
        if (this.isInitialized) return;
        console.log("Till Initialized...");
        this.isInitialized = true;
        
        this.renderAppActions();
        this.checkStatus();
    },
    
    destroy: function() {
        console.log("[Till] Purging resources...");
        this.stopMonitor();
        this.isInitialized = false;
    },

    // Inyecta botones específicos en la barra superior del OS
    renderAppActions: function() {
        const container = document.getElementById('menu-app-actions');
        if (!container || !this.isShiftOpen) {
            container.innerHTML = ''; 
            return;
        }
    
        container.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">
                    Operaciones
                </div>
                <ul class="dropdown-menu shadow border-0 mt-2">
                    <li><a class="dropdown-item small" onclick="TillApp.auditTillModal()"><i class="bi bi-search me-2"></i>Arqueo de Caja</a></li>
                    <li><a class="dropdown-item small" onclick="TillApp.showMovements()"><i class="bi bi-arrow-left-right me-2"></i>Movimientos</a></li>
                    <li><a class="dropdown-item small" onclick="TillApp.showOrderHistory()"><i class="bi bi-clock-history me-2"></i>Historial de Ventas</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item small text-sfred fw-bold" onclick="TillApp.closeShiftModal()">
                            <i class="bi bi-door-closed me-2"></i>Cerrar Turno
                        </a>
                    </li>
                </ul>
            </div>
        `;
    },
    
    checkStatus: function() {
        // Consultamos el endpoint que configuramos en get-data.php
        fetch('/api/get-data?action=get_till_open')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'open') {
                    this.currentBalance = data.current_balance;
                    this.shiftId = data.shift_id;
                    this.updateUI(true);
                } else {
                    this.updateUI(false);
                }
            })
            .catch(err => {
                console.error("Error checking till status:", err);
                IxeaStages.showMessage('Error de conexión con la caja', 'error');
            });
    },
    
    // Activa timer para llamar pedidos pendientes por cobrar
    startMonitor: function() {
        if (!this.refreshTimer) {
            console.log('[Till] Monitor: Started');
            this.refreshPending();

            this.refreshTimer = setInterval(() => {
                if (this.isShiftOpen) {
                    this.refreshPending(true);
                }
            }, 10000);
        }
    },
    
    // Desactiva timer
    stopMonitor: function() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
            console.log('[Till] Monitor: Stopped');
        }
    },

    // Actualiza pedidos pendientes
    refreshPending: function(isBackground = false) {
        fetch(`/api/get-data?action=get_pending_sales`)
            .then(res => {
                // Validación: Si el servidor responde con error de sesión (403)
                if (!res.ok) throw new Error("Session expired");
                return res.json();
            })
            .then(data => {
                // Solo renderizamos si cambió la cantidad de pedidos o si no es modo fondo
                
                /**
                 * En una siguiente versión para validar que cambió no solo contamos el número de filas
                 * pues puede pasar que el número de filas se mantiene aunque con diferentes productos.
                 * Lo que haremos será generar un token con el número de productos y sus ids.
                 * Este se manda a servidor y si cambia, entonces devuelve cambios.
                **/
                if (data.orders.length !== this.lastOrderCount || !isBackground) {
                    this.orders = data.orders;
                    this.renderOrders(data.orders);
                    this.lastOrderCount = data.orders.length;
                }
            })
            .catch(err => {
                console.warn("[Till] Fetch failed:", err.message);
                if (err.message === "Session expired") this.stopMonitor();
            });
    },

    // Actualiza la caja según si está abierta o cerrada
    updateUI: function(isOpen) {
        const stateChanged = (this.isShiftOpen !== isOpen);
        if (!stateChanged) return;
    
        this.isShiftOpen = isOpen;
        this.renderAppActions();
    
        const infoOpen = document.getElementById('caja-open-info');
        const infoClosed = document.getElementById('caja-closed-info');
        const actionsGrid = document.getElementById('caja-actions-grid');
        const statusText = document.getElementById('caja-status-text');
        const statusIcon = document.getElementById('caja-status-icon');
    
        if (isOpen) {
            infoOpen?.classList.remove('d-none');
            infoClosed?.classList.add('d-none');
            actionsGrid?.classList.remove('d-none');
            if(statusText) statusText.innerText = "Caja Operativa";
            if(statusIcon) statusIcon.innerHTML = '<i class="bi bi-unlock-fill fs-1 text-sfgreen"></i>';
            this.startMonitor();
        } else {
            infoOpen?.classList.add('d-none');
            infoClosed?.classList.remove('d-none');
            actionsGrid?.classList.add('d-none');
            if(statusText) statusText.innerText = "Caja Cerrada";
            if(statusIcon) statusIcon.innerHTML = '<i class="bi bi-lock-fill fs-1 text-secondary"></i>';
            this.stopMonitor();
            
            const list = document.getElementById('pending-orders-list');
            if(list) list.innerHTML = '<div class="text-center py-5 opacity-25">Debes abrir turno para cobrar</div>';
        }
    },
    
    // Lanza modal para abrir caja
    openShiftModal: async function() {
        const res = await fetch('/api/get-data?action=get_balance&account=cash'); // Ajusta a tu ruta real
        const data = await res.json();
        this.currentBalance = data.balance || 0;
        
        IxeaStages.openModal({
            templateId: 'tpl-open-caja',
            size: 'sm',
            focusId: 'opening-cash',
            onOpen: () => {
                // Inyectamos el saldo que el sistema cree que hay
                document.getElementById('till-open-balance').innerText = `$${toCurrency(this.currentBalance)}`;
            }
        });
    },
    
    // El proceso de abrir caja enviando datos al servidor
    confirmOpenShift: async function(event) {
        const btn = event.currentTarget;
        
        const amountInput = document.getElementById('opening-cash');
        const amount = amountInput.value;
        
        // 1. Validación inmediata (UI)
        if (amount === "" || amount < this.currentBalance) {
            return Swal.fire('Atención', 'Ingresa un monto inicial válido', 'warning');
        }
    
        try {
            // 2. Esperamos la respuesta del servidor
            const response = await fetch('/modules/till/till-operations?action=open_till', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: parseFloat(amount) })
            });
    
            // 3. Esperamos la conversión del JSON
            const data = await response.json();
    
            if (data.success) {
                // Éxito: Notificamos, cerramos y refrescamos
                await Swal.fire({
                    icon: 'success',
                    title: 'Turno Iniciado',
                    text: `Fondo de caja: $${toCurrency(amount)}`,
                    showConfirmButton: false
                });
    
                IxeaStages.closeModal();
                this.checkStatus(); // Refresca la UI para mostrar "Caja Operativa"
            } else {
                Swal.fire('Error', data.error || 'No se pudo abrir el turno', 'error');
            }
    
        } catch (err) {
            console.error("Error en apertura:", err);
            Swal.fire('Error de Conexión', 'Ocurrió un fallo al intentar abrir la caja', 'error');
        }
    },

    // Gestiona visibilidad efectivo
    toggleCashVisibility: function() {
        this.cashVisible = !this.cashVisible;
        const cashDisplay = document.getElementById('caja-current-cash');
        const icon = document.getElementById('eye-icon');
        
        if (this.cashVisible) {
            cashDisplay.innerText = `$${toCurrency(this.currentBalance)}`;
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            cashDisplay.innerText = '******';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    },
    
    // Muestra las ordenes pendientes de pago
    renderOrders: function(orders) {
        const container = document.getElementById('pending-orders-list');
        if (orders.length === 0) {
            container.innerHTML = '<div class="text-center py-5 opacity-50"><i class="bi bi-receipt fs-1"></i><p>No hay pedidos pendientes</p></div>';
            return;
        }
    
        let html = '<div class="list-group list-group-flush">';
        orders.forEach(order => {
            html += `
                <div class="list-group-item list-group-item-action p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="cursor-pointer flex-grow-1" onclick="TillApp.openPayModal(${JSON.stringify(order).replace(/"/g, '&quot;')})">
                        <div class="fw-bold text-sfblue">${order.folio}</div>
                        <div class="small text-muted">${order.customer_name || 'Público General'}</div>
                        <div class="fs-5 fw-bold text-dark">$${toCurrency(order.total)}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light border" onclick="TillApp.reprintOrder('${order.sale_id}')" title="Reimprimir">
                            <i class="bi bi-printer text-sfcyan"></i>
                        </button>
                        <button class="btn btn-sm btn-light border" onclick="TillApp.editOrder('${order.sale_id}')" title="Editar">
                            <i class="bi bi-pencil-square text-sfblue"></i>
                        </button>
                        <button class="btn btn-sm btn-light border" onclick="TillApp.cancelOrder('${order.sale_id}')" title="Cancelar">
                            <i class="bi bi-x-lg text-sfred"></i>
                        </button>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    },
    
    openPayModal: function(order) {
        this.currentOrder = order;
        IxeaStages.openModal({
            templateId: 'tpl-pay-order',
            size: 'lg',
            focusId: 'cash-received',
            onOpen: () => {
                document.getElementById('display-till-folio').innerText = `${order.folio}`;
                document.getElementById('display-till-total').innerText = `$${order.total}`;
                
                document.getElementById('cash-received').addEventListener('input', (e) => {
                    const received = parseFloat(e.target.value) || 0;
                    const change = received - parseFloat(this.currentOrder.total);
                    document.getElementById('display-change').innerText = `$${change > 0 ? toCurrency(change) : '0.00'}`;
                });
            }
        });
    },
    
    togglePayInputs: function() {
        const method = document.getElementById('pay-method').value;
        const ref = document.getElementById('reference-container');
        const credit = document.getElementById('credit-container');
        const calc = document.getElementById('cash-calc');
        
        if (method === 'cash') {
            ref.classList.add('d-none');
            credit.classList.add('d-none');
            calc.classList.remove('d-none');
            document.getElementById('cash-received').focus();
        } else if (method === 'credit'){
            ref.classList.add('d-none');
            credit.classList.remove('d-none');
            calc.classList.add('d-none');
            
            const order = this.currentOrder;
            if (order.credit_status !== 'approved') {
                const message = (order.credit_status === 'suspended') 
                    ? 'LA CUENTA DE ESTE CLIENTE ESTÁ SUSPENDIDA.' 
                    : 'EL CLIENTE NO TIENE CRÉDITO AUTORIZADO.';
                
                const alertHtml = `
                    <div class="alert alert-danger mt-2 small p-2 mb-0">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> ${message}
                    </div>`;
                credit.innerHTML = alertHtml;
            } else {
                credit.innerHTML = `
                    <div class="alert alert-info mt-2 small p-2 mb-0 d-flex flex-column align-items-center text-center">
                    <span>
                        <i class="bi bi-info-circle-fill me-2"></i> Crédito disponible.<br/>
                        Se requiere impresión de pagaré.
                    </span>
                    <button class="btn btn-sm btn-light border mt-2" onclick="TillApp.reprintOrder('${order.sale_id}',true)" title="Pagaré">
                        <i class="bi bi-printer text-sfblue"></i>
                    </button>
                </div>`;
            }
        } else {
            ref.classList.remove('d-none');
            credit.classList.add('d-none');
            calc.classList.add('d-none');
            document.getElementById('pay-reference').focus();
        }
    },
    
    confirmPayment: async function(event) {
        const btn = event.currentTarget;
        if (!IxeaBouncer.lockBtn(btn)) return;

        const method = document.getElementById('pay-method').value;
        const received = document.getElementById('cash-received').value;
        const reference = document.getElementById('pay-reference').value;
        
        // Validación de pago por método
        switch (method) {
            case 'cash':
                if (received && parseFloat(received) < this.currentOrder.total) {
                    return Swal.fire('Error', 'Monto inferior al total.', 'error');
                }
                break;
    
            case 'card':
            case 'transfer':
            case 'check':
                if (!reference) {
                    return Swal.fire('Error', 'Falta referencia.', 'error');
                }
                break;
    
            case 'credit':
                const status = this.currentOrder.credit_status;
                if (status !== 'approved') {
                    return Swal.fire({
                        icon: 'error',
                        title: 'Operación denegada',
                        text: status === 'suspended' ? 'La cuenta está SUSPENDIDA.' : 'El cliente no tiene CRÉDITO.',
                        footer: 'Selecciona otro método de pago.',
                        showConfirmButton: false
                    });
                }
                
                const result = await Swal.fire({
                    icon: 'info',
                    title: 'Crédito',
                    text: 'Recuerda imprimir y autorizar pagaré.',
                    showConfirmButton: true,
                    confirmButtonText: 'Listo',
                    showCancelButton: true
                });
                
                if (!result.isConfirmed) return;
                break;
        }
    
        const payload = {
            sale_id: this.currentOrder.sale_id,
            method: method,
            reference: reference
        };
        
        try {
            const res = await fetch('/modules/till/till-operations?action=complete_sale', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const data = await res.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Cobrado!',
                    text: `Registro finalizado con éxito`,
                    showConfirmButton: false
                });
                
                IxeaBouncer.releaseBtn(btn);
                IxeaStages.closeModal();
                this.checkStatus();
                this.refreshPending(); 
            } else {
                IxeaBouncer.releaseBtn(btn);
                Swal.fire('Error', data.error, 'error');
            }
        } catch (err) {
            IxeaBouncer.releaseBtn(btn);
            console.error("Error al cobrar:", err);
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    },
    
    // Lanza modal para cerrar caja
    closeShiftModal: function() {
        IxeaStages.openModal({
            templateId: 'tpl-cash-count',
            size: 'sm',
            focusId: 'real-cash-count',
            onOpen: () => {
                // Inyectamos el saldo que el sistema cree que hay
                document.getElementById('till-close-balance').innerText = `$${toCurrency(this.currentBalance)}`;
            }
        });
    },
    
    // Confirmación de cierre. Petición a servidor
    confirmCloseShift: async function(event) {
        const realInput = document.getElementById('real-cash-count').value;
    
        // Validaciones
        if (realInput === "" || isNaN(realInput)) {
            return Swal.fire('Atención', 'Debes ingresar el conteo físico de la caja', 'warning');
        }
        
        
        const balanceSystem = parseFloat(this.currentBalance).toFixed(2);
        const balanceUser = parseFloat(realInput).toFixed(2);
        if (balanceUser < balanceSystem) {
            const diferencia = (balanceSystem - balanceUser).toFixed(2);
            return Swal.fire('Error de Arqueo', `Faltan $${diferencia} para poder cerrar.`, 'error');
        }
    
        // 2. Primera confirmación (UI)
        const confirm = await Swal.fire({
            title: '¿Confirmar cierre?',
            text: "Una vez cerrado no podrás registrar más ventas en este turno.",
            icon: 'warning',
            showCancelButton: true,
            showConfirmButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, finalizar turno',
            cancelButtonText: 'Revisar de nuevo'
        });
    
        if (!confirm.isConfirmed) return;
    
        // 3. Proceso de cierre en servidor
        const btn = event.currentTarget;
        
        try {
            const response = await fetch('/modules/till/till-operations?action=close_till', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    shift_id: this.shiftId,
                    system_amount: parseFloat(balanceSystem),
                    real_amount: parseFloat(balanceUser)
                })
            });
    
            const data = await response.json();
    
            if (data.success) {
                // Éxito total
                await Swal.fire({
                    icon: 'success',
                    title: '¡Cerrado!',
                    text: 'El turno ha finalizado correctamente',
                    showConfirmButton: false
                });
    
                this.reprintOrder(data.sale_id);
                IxeaStages.closeModal();
                this.checkStatus();
            } else {
                Swal.fire('Error', data.error || 'No se pudo cerrar el turno', 'error');
            }
    
        } catch (err) {
            console.error("Error en cierre de turno:", err);
            Swal.fire('Error de Conexión', 'El servidor no respondió. Verifica tu internet antes de reintentar.', 'error');
        }
    },
    
    // Arqueo de caja
    auditTillModal: function() {
        fetch('/api/get-data.php?action=get_till_open&term=' + window.IXEA_USER.user_id)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'open') return;

                IxeaStages.openModal({
                    templateId: 'tpl-audit-caja',
                    size: 'sm',
                    focusId: 'audit-withdraw-amount',
                    onOpen: () => {
                        document.getElementById('audit-opening').innerText = `$${toCurrency(data.opening_amount)}`;
                        document.getElementById('audit-system-sales').innerText = `$${toCurrency(data.current_sales)}`;
                        document.getElementById('audit-system-ins').innerText = `$${toCurrency(data.current_ins)}`;
                        document.getElementById('audit-system-outs').innerText = `$${toCurrency(data.current_outs)}`;
                        document.getElementById('audit-system-total').innerText = `$${toCurrency(data.current_balance)}`;
                    }
                });
            })
            .catch(err => console.error("Error en arqueo:", err));
    },
    
    // Confirmación de retiro
    confirmWithdrawal: async function(event) {
        const amount = document.getElementById('audit-withdraw-amount').value;
        const concept = document.getElementById('audit-withdraw-concept').value || 'Retiro de efectivo';
    
        // 1. Validaciones previas (Síncronas)
        if (!amount || amount <= 0) {
            return Swal.fire('Atención', 'Ingresa un monto válido para retirar', 'warning');
        }
    
        if (parseFloat(amount) > this.currentBalance) {
            return Swal.fire('Saldo Insuficiente', 'No puedes retirar más de lo que hay en caja', 'error');
        }
    
        // Cuerpo de solicitud
        const body = { 
            type: 'out',
            is_withdrawal: true,
            amount: parseFloat(amount),
            concept: concept
        };
        
        // Prevención de duplicados
        const btn = event.currentTarget;
        if (!IxeaBouncer.lockBtn(btn)) return;
        
        // 2. Operación asíncrona
        try {
            const response = await fetch('/modules/till/till-operations?action=till_movement', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
    
            const data = await response.json();
    
            if (data.success) {
                // Esperamos a que el usuario vea el éxito antes de seguir
                await Swal.fire({
                    icon: 'success',
                    title: 'Retiro Exitoso',
                    text: `Se han retirado $${toCurrency(amount)} de la caja.`,
                    showConfirmButton: false
                });
    
                IxeaBouncer.releaseBtn(btn);
                IxeaStages.closeModal();
                this.checkStatus();
            } else {
                IxeaBouncer.releaseBtn(btn);
                Swal.fire('Error', data.error || 'No se pudo procesar el retiro', 'error');
            }
        } catch (err) {
            IxeaBouncer.releaseBtn(btn);
            console.error("Error en retiro:", err);
            Swal.fire('Error de red', 'Error en la conexión', 'error');
        }
    },
    
    showMovements: function() {
        IxeaStages.openModal({
            templateId: 'tpl-till-movements',
            size: 'md',
            onOpen: () => {
                const list = document.getElementById('till-movements-list');
                list.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

                fetch(`/api/get-data?action=get_till_movements&term=${this.shiftId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.movements || data.movements.length === 0) {
                            list.innerHTML = '<div class="text-center py-4 text-muted small">No hay movimientos registrados en este turno</div>';
                            return;
                        }

                        let html = '<div class="list-group list-group-flush small">';
                        data.movements.forEach(m => {
                            const isOut = m.type === 'out';
                            html += `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark">${m.concept}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">${m.created_at}</div>
                                    </div>
                                    <div class="fw-bold ${isOut ? 'text-danger' : 'text-success'}">
                                        ${isOut ? '-' : '+'}$${toCurrency(m.amount)}
                                    </div>
                                </div>`;
                        });
                        html += '</div>';
                        list.innerHTML = html;
                    });
            }
        });
    },
    
    cashMovement: function(type) {
        const isIn = type === 'in';
        
        IxeaStages.openModal({
            templateId: 'tpl-till-movement-form',
            size: 'sm',
            focusId: 'till-mov-amount',
            onOpen: () => {
                // Personalizar el modal según el tipo
                if (isIn) {
                    document.getElementById('till-mov-title').innerText = 'Entrada de Efectivo';
                    document.getElementById('till-mov-icon').className = 'bi bi-plus-circle fs-1 text-sfgreen';
                    document.getElementById('till-mov-concept').hidden = false;
                    document.getElementById('till-mov-category').hidden = true;
                } else {
                    document.getElementById('till-mov-title').innerText = 'Salida de Efectivo';
                    document.getElementById('till-mov-icon').className = 'bi bi-dash-circle fs-1 text-sfred';
                    document.getElementById('till-mov-concept').hidden = false;
                    document.getElementById('till-mov-category').hidden = false;
                }
                document.getElementById('till-mov-type').value = type;
            }
        });
    },
    
    toggleProviderField: function() {
        const category = document.getElementById('till-mov-category').value;
        if (category === 'general_expense') {
            document.getElementById('till-mov-concept').hidden = false;
            document.getElementById('purchase-folio-wrapper').classList.add('d-none');
            document.getElementById('till-mov-pay-folio').value = '';
        } else {
            document.getElementById('till-mov-concept').hidden = true;
            document.getElementById('till-mov-concept').value = ''
            document.getElementById('purchase-folio-wrapper').classList.remove('d-none');
        }
    },
    
    searchPurchases: async function(query) {
        if (query.length < 3) return; // Empezar a buscar después de 2 letras
    
        try {
            const res = await fetch(`/api/get-data?action=search_pending_purchases&query=${query}`);
            const data = await res.json();
            
            const purchases = data.purchases;
            const list = document.getElementById('purchases-list');
            list.innerHTML = '';
    
            this.purchaseMatch = purchases.find(p => p.folio.toLowerCase() === query.toLowerCase());
            if (this.purchaseMatch) {
                document.getElementById('till-mov-pay-folio').classList.add('text-sfgreen');
                return; 
            }
            document.getElementById('till-mov-pay-folio').classList.remove('text-sfgreen');
            
            purchases.forEach(p => {
                const option = document.createElement('option');
                // Guardamos el ID en el value para que sea fácil de atrapar
                option.value = p.folio; 
                option.dataset.purchase_id = p.purchase_id;
                option.dataset.amount = p.total_amount;
                option.textContent = `Total: $${p.total_amount}`;
                list.appendChild(option);
            });
        } catch (err) { console.error("Error buscando compras", err); }
    },

    confirmMovement: async function(event) {
        const type = document.getElementById('till-mov-type').value;
        const amount = document.getElementById('till-mov-amount').value;
        const category = document.getElementById('till-mov-category').value ?? null;
        const folioInput = document.getElementById('till-mov-pay-folio');
        const concept = document.getElementById('till-mov-concept').value ?? null;
        
        // 1. Validaciones de UI (Síncronas)
        if (!amount || amount <= 0) {
            return Swal.fire('Atención', 'Ingresa un monto válido', 'warning');
        }
        if (category == 'general_expense' && !concept) {
            return Swal.fire('Atención', 'Debes indicar el concepto del movimiento', 'warning');
        }
        if (type === 'out' && parseFloat(amount) > this.currentBalance) {
            return Swal.fire('Saldo Insuficiente', 'No hay suficiente efectivo en caja', 'error');
        }
    
        // --- LÓGICA ESPECÍFICA PARA PAGOS A PROVEEDOR ---
        let purchase_id = null;
        
        if (category === 'supplier_payment') {
            if (!folioInput.classList.contains('text-sfgreen') || !this.purchaseMatch) {
                return Swal.fire('Atención', 'Debes indicar un folio válido', 'warning');
            }
    
            purchase_id = this.purchaseMatch.purchase_id;
            const purchase_amount = this.purchaseMatch.total_amount;
            const change = parseFloat(amount) - parseFloat(purchase_amount);
    
            if (change < 0) {
                return Swal.fire('Atención', 'El monto es inferior al total de la compra.', 'error');
            }
            
            // Si hay cambio, podrías manejarlo aquí o dejarlo como propina/ajuste, 
            // pero por ahora bloqueamos el error de undefined.
        }
    
        // 2. Proceso Asíncrono
        try {
            const response = await fetch('/modules/till/till-operations?action=till_movement', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    type: type,
                    amount: parseFloat(amount),
                    category: category,
                    purchase_id: purchase_id, // Será null si es gasto general
                    concept: concept
                })
            });
    
            const data = await response.json();
    
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Registrado',
                    text: `Movimiento de ${type === 'in' ? 'entrada' : 'salida'} guardado`,
                    timer: 1500,
                    showConfirmButton: false
                });
    
                IxeaStages.closeModal();
                this.checkStatus(); 
                
                // Si vienes desde el módulo de Pagos, refrescamos también ese dashboard
                if (window.PaymentsApp) window.PaymentsApp.loadData();
                
            } else {
                Swal.fire('Error', data.error || 'No se pudo registrar', 'error');
            }
        } catch (err) {
            console.error("Error al registrar movimiento:", err);
            Swal.fire('Error de red', 'No se pudo comunicar con el servidor', 'error');
        }
    },
    
    editOrder: function(saleId) {
        const sale = this.orders.find(s => s.sale_id == saleId);
        if (!sale) return;
        this.currentOrder = sale;
    
        IxeaStages.openModal({
            templateId: 'tpl-till-edit-sale',
            size: 'md',
            focusId: 'till-edit-customer',
            onOpen: () => {
                // Llenar datos básicos en el modal
                document.getElementById('till-edit-displayId').innerText = sale.folio;
                
                const customerEl = document.getElementById('till-edit-customer');
                customerEl.value = sale.rfc || '';
                customerEl.dataset.customer_id = sale.customer_id || 0;
                customerEl.classList.add('border-sfgreen');
    
                // Estado del Switch de factura
                const taxCheck = document.getElementById('till-edit-tax');
                taxCheck.checked = sale.is_taxable === 1 ? true: false;
    
                // Vincular el evento al botón por su ID
                const btn = document.getElementById('till-btnConfirmEdition');
                btn.onclick = () => this.confirmEdition();
            }
        });
    },
    
    searchCustomers: async function(query) {
        const customerEl = document.getElementById('till-edit-customer');
        const list = document.getElementById('till-customer-list');
        
        // Limpiamos
        list.innerHTML = '';
        customerEl.classList.remove('border-sfgreen');
        customerEl.classList.remove('border-sfred');
        console.log(query);
        // Público en General
        if (query == '') return customerEl.classList.add('border-sfgreen');
        if (query.length < 3) return customerEl.classList.add('border-sfred');
        
        // Cargamos Lista
        try {
            const res = await fetch(`/api/get-data?action=get_customers&query=${query}`);
            const data = await res.json();
            
            const customers = data.customers;
            console.log(customers);
            customers.forEach(c => {
                const option = document.createElement('option');
                option.value = c.rfc; 
                option.dataset.customer_id = c.customer_id;
                option.textContent = `${c.customer_name}`;
                list.appendChild(option);
            });
    
            this.customerMatch = customers.find(c => 
                    c.customer_name.toLowerCase() === query.toLowerCase() || 
                    c.rfc.toLowerCase() === query.toLowerCase()
            );
            
            if (this.customerMatch) {
                // March
                customerEl.classList.add('border-sfgreen');
                customerEl.dataset.customer_id = this.customerMatch.customer_id;
                return; 
            }
            // Else
            customerEl.classList.add('border-sfred');

        } catch (err) { 
            console.error("Error de conexión:", err);
            Swal.fire('Error de conexión', 'Error al conectar con servidor', 'error');
        }
    },
    
    confirmEdition: async function() {
        const btn = document.getElementById('till-btnConfirmEdition');
        if (!IxeaBouncer.lockBtn(btn)) return;
    
        const body = {
            sale_id: this.currentOrder.sale_id,
            customer_id: document.getElementById('till-edit-customer').dataset.customer_id,
            is_taxable: document.getElementById('till-edit-tax').checked ? 1 : 0
        };
    
        try {
            const res = await fetch('/modules/payments/payment-handler?action=update_sale', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
    
            const data = await res.json();
    
            if (data.success) {
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Exito!',
                    text: data.message,
                    showConfirmButton: false
                });
                
                IxeaBouncer.releaseBtn(btn);
                IxeaStages.closeModal();
                this.checkStatus();
                this.refreshPending(); 
            } else {
                IxeaBouncer.releaseBtn(btn);
                Swal.fire('Error', data.message || 'Error desconocido', 'error');
            }
            
        } catch (error) {
            IxeaBouncer.releaseBtn(btn);
            console.error("Error de conexión:", error);
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    },

    // Cancelación. Borrado lógico en el servidor
    cancelOrder: function(saleId) {
        Swal.fire({
            title: '¿Cancelar pedido?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, mantener'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch('/modules/till/till-operations?action=cancel_sale', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ sale_id: saleId })
                    });
                    const data = await res.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Ticket cancelado',
                            showConfirmButton: false
                        });
                        
                        // Actualizamos la vista principal de pendientes
                        this.refreshPending();
                        
                        // Detección y actualización del historial
                        const historyActive = document.getElementById('hist-search-folio');
                        if (historyActive) {
                            this.performOrderSearch();
                        }
                        
                    } else {
                        Swal.fire('Error', data.error || 'No se pudo cancelar', 'error');
                    }
                } catch (err) {
                    console.error("Error al cancelar:", err);
                    Swal.fire('Error', 'Fallo de conexión con el servidor', 'error');
                }
            }
        });
    },

    // 2. Reimprimir Pedido
    reprintOrder: async function(saleId, is_promissory = false) {
        try {
            // Usamos un SweetAlert de carga para que el usuario no desespere
            Swal.fire({
                title: 'Generando Ticket',
                didOpen: () => { Swal.showLoading(); }
            });

            const res = await fetch(`/api/get-data?action=get_sale_details&term=${saleId}`);
            const data = await res.json();

            if (data.success) {
                // Recreamos el objeto data exactamente como lo pide el proceso de impresión
                const ticketData = {
                    folio: data.sale.folio,
                    date: data.sale.operation_date, 
                    user_name: data.sale.user_name,
                    customer_name: data.sale.customer_name,
                    items: data.items.map(item => ({
                        name: item.product_name,
                        sku: item.sku,
                        qty: parseFloat(item.qty).toFixed(3),
                        unit: item.unit,
                        price: parseFloat(item.price),
                        subtotal: parseFloat(item.subtotal)
                    })),
                    totalDiscount: parseFloat(data.sale.total_discount || 0),
                    total: parseFloat(data.sale.total_amount),
                    is_promissory: is_promissory
                };

                Swal.close();
                IxeaUtils.printTicket(ticketData);
                
            } else {
                Swal.fire('Error', 'No se pudo obtener la data del ticket', 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Error al procesar la reimpresión', 'error');
        }
    },
    
    /** Muestra el historial de todas las ventas realizadas en el turno **/
    showOrderHistory: function() {
        IxeaStages.openModal({
            templateId: 'tpl-order-history',
            size: 'lg',
            onOpen: () => {
                // Ejecutamos la búsqueda inicial (por defecto el turno actual)
                this.performOrderSearch();
                
                // Permitir que al presionar "Enter" en el buscador se ejecute el filtro
                document.getElementById('hist-search-folio')?.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') this.performOrderSearch();
                });
            }
        });
    },
    
    performOrderSearch: function() {
        const list = document.getElementById('history-orders-list');
        const folioBusqueda = document.getElementById('hist-search-folio')?.value.trim() || '';
        const fechaBusqueda = document.getElementById('hist-search-date')?.value || '';
        
        list.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
    
        // Construimos la URL con los nuevos filtros, manteniendo el shiftId por defecto
        let url = `/api/get-data?action=get_order_history&shift_id=${this.shiftId}`;
        if (folioBusqueda !== '') {
            url += `&folio=${folioBusqueda}`;
        }
    
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.orders.length === 0) {
                    const msj = folioBusqueda !== '' 
                        ? `No se encontró el folio <b>${folioBusqueda}</b>. Verifica el número.` 
                        : `No hay ventas registradas en este turno.`;
                    
                    list.innerHTML = `
                        <div class="text-center py-5 opacity-75">
                            <i class="bi bi-search mb-2 d-block h4"></i>
                            <p class="small">${msj}</p>
                        </div>`;
                    return;
                }
    
                let html = `
                    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3">Folio</th>
                                    <th>Cliente</th>
                                    <th>Hora</th>
                                    <th>Método</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>`;
    
                data.orders.forEach(order => {
                    // Mantenemos tu lógica exacta de cancelación
                    const isCancelled = order.payment_status === 'cancelled' || order.payment_status === 'returned';
                    
                    html += `
                        <tr class="${isCancelled ? 'opacity-50' : ''}">
                            <td class="ps-3 fw-bold text-primary">${order.folio}</td>
                            <td>${order.customer_name || 'Público General'}</td>
                            <td class="text-muted">${order.operation_date.split(' ')[1]}</td>
                            <td>
                                ${!isCancelled ? `<span class="badge bg-light text-dark border">${IxeaUtils.paymentMethod[order.payment_method]}</span>` : ''}
                                ${isCancelled ? '<span class="badge bg-danger ms-1">Cancelado</span>' : ''}
                            </td>
                            <td class="text-end fw-bold">$${toCurrency(order.total_amount)}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" title="Reimprimir" onclick="TillApp.reprintOrder('${order.sale_id}')">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                    ${!isCancelled ? `
                                        <button class="btn btn-sm btn-outline-danger" title="Cancelar" onclick="TillApp.cancelOrder('${order.sale_id}')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>` : ''}
                                </div>
                            </td>
                        </tr>`;
                });
    
                html += '</tbody></table></div>';
                list.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                list.innerHTML = '<div class="alert alert-danger m-3">Error al cargar historial</div>';
            });
    }
};