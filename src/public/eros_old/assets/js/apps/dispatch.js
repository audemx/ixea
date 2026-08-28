window.DispatchApp = {
    refreshTimer: null,
    orders: [],
    isInitialized: false,

    init: function() {
        if (this.isInitialized) return;
        console.log("[Dispatch] Initializing...");
        this.isInitialized = true;
        
        this.loadOrders(); // Carga inicial
        this.startMonitor();
    },

    destroy: function() {
        console.log("[Dispatch] Purging resources...");
        this.stopMonitor();
        this.isInitialized = false;
    },

    startMonitor: function() {
        if (!this.refreshTimer) {
            console.log('[Dispatch] Monitor: Started');
            this.refreshTimer = setInterval(() => {
                this.loadOrders();
            }, 10000);
        }
    },

    stopMonitor: function() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
            console.log('[Dispatch] Monitor: Stopped');
        }
    },

    loadOrders: async function() {
        const isHistory = document.getElementById('sales-delivered').checked;
        const action = isHistory ? 'get_delivered_today' : 'get_pending_dispatch';
        
        try {
            const res = await fetch(`/api/get-data?action=${action}`);
            const data = await res.json();
            if (data.success) {
                this.orders = data.orders;
                this.filterList();
            }
        } catch (err) { console.error(err); }
    },
    
    filterList: function() {
        const term = document.getElementById('dispatch-search').value;
        if (!term) {
            this.renderList();
            return;
        }
        // Filtramos sobre el array actual cargado en memoria
        if (term.length < 2) return;
        const filtered = this.orders.filter(o => 
            o.folio.toLowerCase().includes(term.toLowerCase())
        );
        this.renderList(filtered);
    },

    renderList: function(listToRender = null) {
        const orders = listToRender !== null ? listToRender : this.orders;
        const container = document.getElementById('dispatch-list-container');
        const counter = document.getElementById('dispatch-counter');
        const isHistory = document.getElementById('sales-delivered').checked;
        
        if (!container) return;
        // Actualiza contador
        if (orders.length === 1){
            counter.innerText = `${orders.length} ${isHistory ? 'Entregado' : 'Pendiente'}`;
        }
        counter.innerText = `${orders.length} ${isHistory ? 'Entregados' : 'Pendientes'}`;
        // Contenedor de pedidos vacio
        if (orders.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center mt-5 text-muted">
                    <i class="bi bi-emoji-smile fs-1"></i>
                    <p>No hay pedidos pendientes de entrega.</p>
                </div>`;
            return;
        }

        container.innerHTML = orders.map(order => `
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm ${isHistory ? 'bg-light border-secondary opacity-75' : 'border-primary card-hover cursor-pointer'}" onclick="${isHistory ? `DispatchApp.viewOrderDetails(${order.sale_id})` : `DispatchApp.openDispatchModal(${order.sale_id})`}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-dark pe-2">${order.folio} ${isHistory ? '<i class="bi bi-check-circle-fill text-sfgreen"></i>' : ''}</span>
                            <div class="text-end">
                                <small class="d-block text-sfblue" title="Hora de pedido">Orden: ${order.ordered_ago}</small>
                                <small class="d-block text-sfgreen" title="Hora de pago">Pago: ${order.payed_ago}</small>
                                ${isHistory ? `<small class="d-block text-sfyellow" title="Hora de entrega">Entrega: ${order.delivered_ago}</small>`: ''}
                                ${order.payed ? `<small class="d-block text-sfgreen" title="Hora de pago">Pago: ${order.delivered_ago}</small>`: ''}
                            </div>
                        </div>
                        <h6 class="card-title text-truncate">${order.customer_name || 'PÚBLICO EN GENERAL'}</h6>
                        ${isHistory ? '' : `<p class="card-text small text-muted">${order.total_items} productos por entregar</p>`}
                    </div>
                </div>
            </div>
        `).join('');
    },

    openDispatchModal: function(saleId) {
        const order = this.orders.find(o => o.sale_id === saleId);
        if (!order) return;

        IxeaStages.openModal({
            templateId: 'tpl-dispatch-order',
            size: 'md',
            onOpen: () => {
                document.getElementById('m-dispatch-folio').innerText = order.folio;
                this.loadOrderItems(saleId);
            }
        });
    },

    loadOrderItems: async function(saleId, isReadOnly = false) {
        try {
            const res = await fetch(`/api/get-data?action=get_order_items&sale_id=${saleId}`);
            const data = await res.json();
            const list = document.getElementById('m-dispatch-items');
            
            list.innerHTML = data.items.map((item, idx) => `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${item.name}</div>
                        <small class="text-muted d-block">${item.sku}</small>
                    </div>
                    <div class="d-flex text-end align-items-center">
                        <small class="fw-bold me-3">${parseFloat(item.qty).toFixed(3)} ${item.unit}</small>
                        ${isReadOnly ? 
                            '<i class="bi bi-check-lg text-success ms-2"></i>' : 
                            `<div class="form-check fs-4">
                                <input class="form-check-input dispatch-check" type="checkbox" value="${idx}" onchange="DispatchApp.validateChecks()">
                             </div>`
                        }
                    <div>
                </li>
            `).join('');

            if(!isReadOnly) {
                document.getElementById('btn-confirm-dispatch').onclick = () => this.confirmDelivery(saleId);
            }
        } catch (err) {
            console.error("[Dispatch] Error loading items:", err);
        }
    },
    
    viewOrderDetails: function(saleId) {
        const order = this.orders.find(o => o.sale_id === saleId);
        if (!order) return;
    
        IxeaStages.openModal({
            templateId: 'tpl-dispatch-order',
            size: 'md',
            onOpen: () => {
                document.getElementById('m-dispatch-folio').innerText = order.folio + " (Entregado)";
                IxeaStages.modalEl.querySelectorAll('.to-hide').forEach(el => {
                    el.classList.add('d-none');
                });
                
                // 1. Ocultamos el botón de confirmar ya que es solo vista
                const btnConfirm = document.getElementById('btn-confirm-dispatch');
                if(btnConfirm) btnConfirm.style.display = 'none';
    
                // 2. Cargamos los items en modo lectura
                this.loadOrderItems(saleId, true); // Pasamos true para modo lectura
            }
        });
    },

    validateChecks: function() {
        const checks = document.querySelectorAll('.dispatch-check');
        const allChecked = checks.length > 0 && Array.from(checks).every(c => c.checked);
        document.getElementById('btn-confirm-dispatch').disabled = !allChecked;
    },

    confirmDelivery: async function(saleId) {
        try {
            const res = await fetch('/modules/sales/dispatch-operations', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sale_id: saleId })
            });
            const data = await res.json();
            
            if (data.success) {
                Swal.fire({ 
                    icon: 'success',
                    title: 'Entrega Exitosa',
                    showConfirmButton: false,
                    timer: 2000 
                });
                IxeaStages.closeModal();
                document.getElementById('dispatch-search').value = '';
                this.loadOrders();
            }
        } catch (err) {
            Swal.fire('Error', 'No se pudo registrar la entrega', 'error');
        }
    }
};