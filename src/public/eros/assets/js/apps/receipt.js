/** assets/js/apps/receipt.js **/
window.ReceiptApp = {
    refreshTimer: null,
    orders: [],
    isInitialized: false,

    init: function() {
        if (this.isInitialized) return;
        this.isInitialized = true;
        
        this.loadOrders(); 
        this.startMonitor();
    },

    destroy: function() {
        this.stopMonitor();
        this.isInitialized = false;
    },

    startMonitor: function() {
        if (!this.refreshTimer) {
            this.refreshTimer = setInterval(() => this.loadOrders(), 60*1000); // 1m para recepciones
        }
    },

    stopMonitor: function() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    },

    loadOrders: async function() {
        const isHistory = document.getElementById('purchases-received').checked;
        const action = isHistory ? 'get_received_today' : 'get_pending_purchases';
        
        try {
            const res = await fetch(`/api/get-data?action=${action}`);
            const data = await res.json();
            const purchases = data.purchases;
            
            this.orders = purchases ?? [];
            this.filterList();
        } catch (err) { console.error("[Receipt] Load Error:", err); }
    },

    filterList: function() {
        const term = document.getElementById('receipt-search').value;
        if (!term) {
            this.renderList();
            return;
        }
        if (term.length < 2) return;
        const filtered = this.orders.filter(orders => 
            orders.folio.toLowerCase().includes(term.toLowerCase())
        );
        this.renderList(filtered);
    },

    renderList: function(listToRender = null) {
        const orders = listToRender !== null ? listToRender : this.orders;
        const container = document.getElementById('receipt-list-container');
        const counter = document.getElementById('receipt-counter');
        const isHistory = document.getElementById('purchases-received').checked;

        if (!container) return;
        if (orders.length === 1){
            counter.innerText = `${orders.length} ${isHistory ? 'Recibido' : 'Pendiente'}`;
        }
        counter.innerText = `${orders.length} ${isHistory ? 'Recibidos' : 'Pendientes'}`;
        

        if (orders.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center mt-5 text-muted">
                    <i class="bi bi-box-seam fs-1"></i>
                    <p>No hay ${isHistory ? 'entradas registradas hoy' : 'recepciones pendientes'}.</p>
                </div>`;
            return;
        }

        container.innerHTML = orders.map(order => `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm rounded-4 border-0 ${isHistory ? 'opacity-75 bg-light' : 'card-hover cursor-pointer'}" 
                     onclick="ReceiptApp.openReceiptModal(${order.purchase_id}, ${isHistory})">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="badge fw-bold ${isHistory ? 'bg-secondary' : 'text-sforange'}">${order.folio}</h6>
                            <div class="text-end">
                                <small class="d-block text-sfblue" title="Hora de orden">Orden: ${order.ordered_ago}</small>
                                <small class="d-block text-sfgreen" title="Hora de pago">Pago: ${order.payed_ago}</small>
                                ${isHistory ? `<small class="d-block text-sforange" title="Hora de entrada">Entrada: ${order.received_ago}</small>`: ''}
                                
                            </div>
                        </div>
                        <h6 class="fw-bold mb-1 text-truncate">${order.company_name}</h6>
                        <p class="text-muted mb-1">${order.rfc}</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-muted small">${order.items_count || 0} items</span>
                            ${isHistory ? '<span class="badge bg-success-soft text-success"><i class="bi bi-check-all"></i> Recibido</span>' : ''}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    openReceiptModal: async function(purchaseId, isReadOnly = false) {
        try {
            const res = await fetch(`/api/get-data?action=get_purchase_details&purchase_id=${purchaseId}`);
            const order = await res.json();
            
            IxeaStages.openModal({
                templateId: 'tpl-receipt-order',
                size: 'md',
                onOpen: () => {
                    document.getElementById('m-receipt-folio').innerText = order.purchase.folio + (isReadOnly ? " (Recibido)" : "");

                    const itemsList = document.getElementById('m-receipt-items');
                    itemsList.innerHTML = order.items.map((item, idx) => `
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="flex-grow-1">
                                <span class="fw-bold d-block">${item.name}</span>
                                <small class="text-muted">${item.sku}</small>
                            </div>
                            <small class="fw-bold me-3">${parseFloat(item.qty).toFixed(3)} ${item.unit}</small>
                            <div class="form-check fs-4">
                                <input class="form-check-input receipt-check" type="checkbox" 
                                       ${isReadOnly ? 'checked disabled' : ''}
                                       onchange="ReceiptApp.validateChecklist()">
                            </div>
                        </li>
                    `).join('');

                    const btnConfirm = document.getElementById('btn-confirm-receipt');
                    if (isReadOnly) {
                        IxeaStages.modalEl.querySelectorAll('.to-hide').forEach(el => {
                            el.classList.add('d-none');
                        });
                        btnConfirm.classList.add('d-none');
                    } else {
                        btnConfirm.classList.remove('d-none');
                        btnConfirm.onclick = () => this.processIngress(purchaseId);
                        this.validateChecklist();
                    }
                }
            });
        } catch (err) { console.error("[Receipt] Detail Error:", err); }
    },

    validateChecklist: function() {
        const checks = document.querySelectorAll('.receipt-check');
        const allChecked = checks.length > 0 && Array.from(checks).every(c => c.checked);
        document.getElementById('btn-confirm-receipt').disabled = !allChecked;
    },

    processIngress: function(purchaseId) {
        Swal.fire({
            title: '¿Registrar ingreso?',
            text: "Se modificará el inventario y la orden se marcará como recibida.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, ingresar',
            confirmButtonColor: 'var(--soft-orange)'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch('/modules/purchases/confirm-receipt', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ purchase_id: purchaseId })
                    });
                    const data = await res.json();
                    if(data.success) {
                        IxeaStages.closeModal();
                        this.loadOrders();
                        Swal.fire('¡Éxito!', 'Stock actualizado correctamente.', 'success');
                    }
                } catch (err) { Swal.fire('Error', 'No se pudo procesar el ingreso', 'error'); }
            }
        });
    }
};