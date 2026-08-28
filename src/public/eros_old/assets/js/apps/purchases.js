/** assets/js/apps/purchases.js **/
window.PurchasesApp = {
    cart: [],
    catalog: {},
    selectedSupplierId: null,
    isInitialized: false,
    radarMode: 'low',
    groupedCart: {},
    suppliersInfo: [],
    summaryOrders: [],

    init: function() {
        if (this.isInitialized) return;
        console.log("Purchases Initialized...");
        this.isInitialized = true;
        
        this.renderAppActions();
        
        document.getElementById('purchase-product-search').focus();
        this.bindEvents();
    },

    bindEvents: function() {
        const activeStage = IxeaStages.activeStage;
        if (!activeStage || activeStage !== 'stage-purchases') return;
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') { e.preventDefault(); this.openSearch(); }
            if (e.key === 'F12') { e.preventDefault(); this.checkout(); }
        });

        const searchInput = document.getElementById('purchase-product-search');
        searchInput.addEventListener('input', (e) => {
            if(e.target.value.length > 2) this.quickSearch(e.target.value);
        });
    },
    
    renderAppActions: function() {
        const menuActions = document.getElementById('menu-app-actions');
        if (!menuActions) return;
    
        menuActions.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">
                    Operaciones
                </div>
                <ul class="dropdown-menu shadow border-0 mt-2">
                    <li>
                        <a class="dropdown-item small" onclick="PurchasesApp.openRadar()">
                            <i class="bi bi-radar me-2 text-sforange"></i>Radar de Abastecimiento
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item small" onclick="PurchasesApp.showPurchaseHistory()">
                            <i class="bi bi-clock-history me-2"></i>Historial de Compras
                        </a>
                    </li>
                </ul>
            </div>
        `;
    },
    
    quickSearch: function(term) {
        fetch(`/api/get-data?action=search_products&context=purchase&term=${encodeURIComponent(term)}`)
            .then(res => {
                if (!res.ok) throw new Error('Error en la respuesta del servidor');
                return res.json();
            })
            .then(data => {
                this.catalog = this.catalog || {}; // Evita error si no está init
                data.forEach(p => { this.catalog[p.unit_id] = p; });
                this.renderQuickResults(data);
            })
            .catch(err => {
                console.error("Detalle del error:", err);
                const msg = err.message || 'Error desconocido';
                document.getElementById('quick-results').innerHTML = 
                    `<div class="alert alert-danger small p-2">Error: ${msg}</div>`;
            });
    },

    renderQuickResults: function(products) {
        const container = document.getElementById('purchase-results');
        container.innerHTML = products.map(p => {
            const hasPrice = p.cost && parseFloat(p.cost) > 0;
            const displayPrice = hasPrice ? `$${toCurrency(p.cost)}` : 'SIN PRECIO';
            
            const factor = parseFloat(p.factor) || 1;
            const currentStock = parseFloat(p.stock) / factor;
            const minSuggested = parseFloat(p.min) / factor;
            const maxSuggested = parseFloat(p.max) / factor;
            // Lógica de recomendación: Si el stock es bajo, sugerimos llegar al máximo
            const textColor = (currentStock <= minSuggested) ? 'text-sfred' : (currentStock > maxSuggested) ? 'text-sfyellow' : 'text-sfgreen';
            
            return `<div class="col">
                <div class="card product-card h-100 rounded-4 p-2 border-sforange" data-id="${p.unit_id}" onclick="PurchasesApp.addToCart(${p.unit_id})">
                    <div class="card-body p-2">
                        <div class="fw-bold small">${p.name}</div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="badge bg-light text-dark font-monospace">${p.sku}</span>
                            <div class="fw-bold ${hasPrice ? 'text-sforange' : 'text-sfred small'}">
                                ${displayPrice}
                            </div>
                        </div>
                        <div class="text-secondary mt-1" style="font-size: 0.7rem;">
                            Unidad: ${p.unit} | Stock:
                            <span class="fw-bold ${textColor}">
                                ${Number(currentStock).toFixed(2)}
                            </span>
                        </div>
                        
                    </div>
                </div>
            </div>`
        }).join('');
    },
    
    openSearch: function() {
        IxeaStages.openModal({
            templateId: 'tpl-adv-search-purchase',
            size: 'md',
            focusId: 'adv-search-input-purchase',
            onOpen: () => {
                const advInput = document.getElementById('adv-search-input-purchase');
                if (advInput) {
                    advInput.addEventListener('keydown', (e) => {
                        const value = e.target.value;
                        
                        // Detecta la tecla Enter ('Enter') o Espacio (' ')
                        if (e.key === 'Enter' || e.key === ' ') {
                            if (value.trim().length > 2) {
                                this.advancedSearchPurchase(value);
                            }
                        }
                    });
                }
            }
        });
    },
    
    /** Realiza la búsqueda avanzada tokenizada para el módulo de compras **/
    advancedSearchPurchase: function(term) {
        const container = document.getElementById('adv-search-results-purchase');
        if (!container) return;
    
        container.innerHTML = `<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div></td></tr>`;
    
        fetch(`/api/get-data?action=adv_search_products&context=purchase&term=${encodeURIComponent(term)}`)
            .then(res => res.json())
            .then(data => {
                // Guardamos en el catálogo local de compras para acceso rápido
                if (data.length > 0) {
                    data.forEach(p => {
                        this.catalog[p.unit_id] = p; 
                    });
                }
                this.renderAdvancedPurchaseResults(data);
            })
            .catch(err => {
                console.error("Error Advanced Purchase Search:", err);
                container.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4 small">Error al conectar con el catálogo</td></tr>`;
            });
    },
    
    /** Dibuja los resultados en la tabla del modal de búsqueda de compras **/
    renderAdvancedPurchaseResults: function(products) {
        const container = document.getElementById('adv-search-results-purchase');
        
        if (products.length === 0) {
            container.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-secondary small">No hay productos que coincidan</td></tr>`;
            return;
        }
    
        container.innerHTML = products.map(p => {
            const hasPrice = p.cost && parseFloat(p.cost) > 0;
            const displayPrice = hasPrice ? `$${toCurrency(p.cost)}` : 'SIN PRECIO';
            
            const factor = parseFloat(p.factor) || 1;
            const currentStock = parseFloat(p.stock) / factor;
            const minSuggested = parseFloat(p.min) / factor;
            const maxSuggested = parseFloat(p.max) / factor;
            // Lógica de recomendación: Si el stock es bajo, sugerimos llegar al máximo
            const textColor = (currentStock <= minSuggested) ? 'text-sfred' : (currentStock > maxSuggested) ? 'text-sfyellow' : 'text-sfgreen';
    
            return `
                <tr class="cursor-pointer align-middle" onclick="PurchasesApp.addToCart(${p.unit_id})">
                    <td><span class="badge bg-light text-dark font-monospace border">${p.sku}</span></td>
                    <td>
                        <div class="fw-bold text-dark">${p.name}</div>
                        <div class="text-muted small">
                            <span class="badge bg-secondary opacity-75 me-1">${p.category || 'N/A'}</span>
                            <span class="badge bg-secondary opacity-75">${p.brand || 'N/A'}</span>
                        </div>
                    </td>
                    <td class="small text-center">${p.unit}</td>
                    <td class="text-center">
                        <span class="fw-bold ${textColor}">
                            ${Number(currentStock).toFixed(2)}
                        </span>
                    </td>
                    
                    <td class="fw-bold ${hasPrice ? 'text-sforange' : 'text-sfred small'} text-end">
                        ${displayPrice}
                    </td>
                </tr>
            `;
        }).join('');
    },

    addToCart: function(unitId) {
        const p = this.catalog[unitId];
        const cost = parseFloat(p.cost || 0);
        if (!p || cost <= 0) {
            Swal.fire({
                title: '¡Precio Faltante!',
                text: `El producto "${p?.name || 'Desconocido'}" no tiene un costo de compra asignado. Por favor, configúralo en el catálogo.`,
                icon: 'warning',
                confirmButtonColor: 'var(--soft-orange)',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        const existing = this.cart.find(item => item.unit_id === unitId);
        
        if (existing) {
            existing.qty += 1;
        } else {
            const factor = parseFloat(p.factor) || 1;
            const currentStock = parseFloat(p.stock) / factor;
            const minSuggested = parseFloat(p.min) / factor;
            const maxSuggested = parseFloat(p.max) / factor;
            
            let initialQty = 1;
            
            // Si el stock está en zona crítica, sugerimos completar al máximo
            if (currentStock < minSuggested && maxSuggested > currentStock) {
                initialQty = Math.ceil(maxSuggested - currentStock);
            }
    
            // 3. AGREGAR AL CARRITO
            this.cart.push({ 
                ...p, 
                qty: initialQty, 
                cost: cost // Guardamos como cost para el subtotal
            });
        }
        this.renderCart();
    },

    renderCart: function() {
        const container = document.getElementById('purchase-items');
        let total = 0;
    
        if (this.cart.length === 0) {
            container.innerHTML = `<p class="text-center mt-5 opacity-50">Orden vacía</p>`;
            document.getElementById('purchase-total-val').innerText = "$0.00";
            return;
        }
    
        container.innerHTML = this.cart.map((item, index) => {
            const sub = item.cost * item.qty;
            total += sub;
            
            // --- CÁLCULO ATÓMICO GLOBAL (Impacto de toda la orden) ---
            const factor = parseFloat(item.factor) || 1;
            
            // Sumamos TODO lo que hay en el carrito que coincida con el product_id
            const totalBaseInOrder = this.cart
                .filter(i => i.product_id === item.product_id)
                .reduce((sum, i) => sum + (parseFloat(i.qty) * (parseFloat(i.factor) || 1)), 0);
    
            const currentStockBase = parseFloat(item.stock); 
            const finalStockBase = currentStockBase + totalBaseInOrder;
    
            // Convertimos el stock final a la unidad actual de esta fila para la etiqueta
            const finalStockCurrentUnit = finalStockBase / factor;
            const maxSuggestedUnit = parseFloat(item.max) / factor;
            const minSuggestedUnit = parseFloat(item.min) / factor;
    
            // Colores basados en la salud global del producto
            let progressColor = 'bg-sfgreen';
            if (finalStockCurrentUnit < minSuggestedUnit) {
                progressColor = 'bg-sfred';
            } else if (finalStockCurrentUnit > maxSuggestedUnit) {
                progressColor = 'bg-sfyellow';
            }
    
            const percent = maxSuggestedUnit > 0 ? Math.min((finalStockCurrentUnit / maxSuggestedUnit) * 100, 100) : 100;
            
            return `
                <div class="cart-item py-1 border-bottom animate__animated animate__fadeIn" data-id="${item.unit_id}">
                    <div class="d-flex justify-content-between align-items-start mb-0">
                        <div class="d-flex flex-column lh-sm">
                            <div class="fw-bold small text-truncate" style="max-width: 180px;">${item.name}</div>
                            <div class="text-secondary" style="font-size: 0.7rem;">${item.sku}</div>
                        </div>
                        <button class="btn btn-sm text-danger p-0 m-0" onclick="PurchasesApp.removeItem(${index})">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-0">
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <input type="number" class="form-control form-control-sm border-0 bg-light fw-bold py-0 text-end" 
                                   value="${item.qty}" 
                                   onchange="PurchasesApp.updateQty(${index}, this.value)">
                        </div>
                        <div class="text-end">
                            <span class="small muted">${item.unit} x $${toCurrency(item.cost)}</span>
                            <div class="fw-bold text-sforange">$${toCurrency(sub)}</div>
                        </div>
                    </div>
                    <div class="mt-1">
                        <div class="progress" style="height: 4px; background-color: #f0f0f0; border-radius: 10px; overflow: hidden;">
                            <div class="progress-bar ${progressColor} transition-all" 
                                 role="progressbar" 
                                 style="width: ${percent}%" 
                                 aria-valuenow="${percent}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1" style="font-size: 0.6rem; color: #999;">
                            <span>Stock Proyectado: <b>${finalStockCurrentUnit.toFixed(1)} ${item.unit}</b></span>
                            <span>Máx: ${maxSuggestedUnit.toFixed(0)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    
        document.getElementById('purchase-total-val').innerText = `$${toCurrency(total)}`;
    },
    
    removeItem: function(index) {
        this.cart.splice(index, 1);
        this.renderCart();
    },
    
    clearCart: function() {
        if (this.cart.length === 0) return;
    
        Swal.fire({
            title: '¿Vaciar carrito?',
            text: "Se eliminarán todos los productos seleccionados",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--soft-red, #ff5f5f)',
            cancelButtonColor: '#6e7881',
            confirmButtonText: 'Sí, vaciar',
            cancelButtonText: 'Cancelar',
            background: 'rgba(255, 255, 255, 0.9)',
            backdrop: `backdrop-filter: blur(4px)`
        }).then((result) => {
            if (result.isConfirmed) {
                this.cart = [];
                this.renderCart();
                Swal.fire({
                    toast: true,
                    position: 'bottom',
                    icon: 'success',
                    title: 'Carrito vacío',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        });
    },
    
    updateQty: function(index, newQty) {
        newQty = parseFloat(newQty);
    
        if (newQty <= 0 || isNaN(newQty)) {
            this.removeItem(index);
            return;
        }
    
        this.cart[index].qty = newQty;
        this.renderCart();
    },
    
    openRadar: function() {
        IxeaStages.openModal({
            templateId: 'tpl-purchase-suggestions',
            size: 'lg',
            onOpen: () => {
                const listContainer = document.getElementById('radar-suppliers-list');
                const tableContainer = document.getElementById('radar-suppliers-container');
                
                // Mostramos cargando en las tarjetas
                document.getElementById('radar-total-critical').innerText = "...";
                document.getElementById('radar-total-low').innerText = "...";
                document.getElementById('radar-total-suppliers').innerText = "...";
            
                // Llamamos data
                fetch('/api/get-data?action=get_purchase_radar')
                .then(res => res.json())
                .then(data => {
                    // 1. Actualizar Tarjetas
                    document.getElementById('radar-total-critical').innerText = data.critical_count;
                    document.getElementById('radar-total-low').innerText = data.low_stock_count;
                    document.getElementById('radar-total-suppliers').innerText = data.suppliers.length;
        
                    // 2. Renderizar lista de proveedores (oculta por default)
                    tableContainer.classList.add('d-none'); 
                    let html = '';
                    data.suppliers.forEach(s => {
                        html += `
                            <tr data-id="${s.id}">
                                <td>
                                    <div class="fw-bold">${s.name}</div>
                                    <div class="text-muted small">${s.rfc}</div>
                                </td>
                                <td class="text-center"><span class="badge bg-sfyellow text-dark border">${s.total_items} productos</span></td>
                                <td class="text-center">
                                    ${s.critical_items > 0 ? `<span class="badge bg-sfred text-dark">${s.critical_items} en cero</span>` : '--'}
                                </td>
                                <td class="ps-3">
                                    <input class="form-check-input radar-checkbox" type="checkbox" checked>
                                </td>
                            </tr>
                        `;
                    });
                    listContainer.innerHTML = html;
                });
            }
        });
    },
    
    // Esta función se llama desde las tarjetas del modal
    filterRadar: function(mode) {
        this.radarMode = mode;
        const tableContainer = document.getElementById('radar-suppliers-container');
        
        if (mode === 'suppliers') {
            tableContainer.classList.remove('d-none');
            // Scroll suave hacia la tabla
            tableContainer.scrollIntoView({ behavior: 'smooth' });
        } else {
            // Para 'critical' o 'low', ocultamos la lista de proveedores 
            // ya que el botón "Generar" hará la magia globalmente
            tableContainer.classList.add('d-none');
            
            // Opcional: Podrías filtrar la lista de proveedores internamente 
            // para que si el usuario luego abre 'suppliers', solo vea los relevantes.
            Swal.fire({
                toast: true,
                position: 'bottom',
                icon: 'info',
                title: `Modo ${mode === 'critical' ? 'Crítico' : 'General'} seleccionado`,
                showConfirmButton: false,
                timer: 2000
            });
        }
    },
    
    toggleRadarSelection: function(checked) {
        document.querySelectorAll('.radar-checkbox').forEach(cb => cb.checked = checked);
    },
    
    generateSuggestedOrder: function() {
        // 1. Recopilar proveedores seleccionados (si la tabla está visible o si queremos filtrar)
        const checkedBoxes = document.querySelectorAll('.radar-checkbox:checked');
        const selectedSuppliers = Array.from(checkedBoxes).map(cb => cb.value);

        // Si el modo es 'suppliers' y no hay nada marcado, avisamos
        if (this.radarMode === 'suppliers' && selectedSuppliers.length === 0) {
            Swal.fire('Atención', 'Selecciona al menos un proveedor de la lista', 'info');
            return;
        }

        // Definimos el modo de búsqueda para el API
        // Si el radarMode es 'suppliers', pedimos todo lo 'low' pero filtrado por esos IDs
        const fetchMode = (this.radarMode === 'critical') ? 'critical' : 'low';
        const suppliersParam = selectedSuppliers.join(',');

        Swal.fire({
            title: 'Generando sugerencias...',
            text: 'Calculando cantidades óptimas',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch(`/api/get-data?action=get_radar_products&mode=${fetchMode}&suppliers=${suppliersParam}`)
        .then(res => {
            if (!res.ok) throw new Error("Error del servidor");
            return res.json();
        })
        .then(response => {
            
            // --- DEPURACIÓN ---
            console.group("📡 Debug Radar Abastecimiento");
            if (response.steps) {
                console.log(response.steps); // Muestra tus tracks en una tabla bonita
            }
            console.groupEnd();
            // ------------------
            console.log(response.data);
            const products = response.data || [];
            let addedCount = 0;

            products.forEach(p => {
                // Actualizamos catálogo local para que addToCart funcione
                this.catalog[p.unit_id] = p;
                
                // Verificamos si ya está en el carrito
                const exists = this.cart.find(item => item.unit_id === p.unit_id);
                if (!exists) {
                    this.addToCart(p.unit_id);
                    addedCount++;
                }
            });

            Swal.close();
            
            if (addedCount > 0) {
                // Cerrar modal del Radar
                IxeaStages.closeModal();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Orden Generada',
                    text: `Se agregaron ${addedCount} productos que requieren atención.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Sin cambios', 'Los productos ya están en tu carrito o no hay nada que surtir en este modo.', 'info');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'No se pudieron obtener las sugerencias', 'error');
        });
    },
    
    groupCartBySupplier: function() {
        this.groupedCart = this.cart.reduce((groups, item) => {
            const sId = item.supplier_id || 0;
            if (!groups[sId]) {
                groups[sId] = {
                    items: [],
                    subtotal: 0
                };
            }
            groups[sId].items.push(item);
            groups[sId].subtotal += (parseFloat(item.qty) * parseFloat(item.cost));
            return groups;
        }, {});
    },

    checkout: async function() { // Añadimos async para esperar los datos de proveedores
        if (this.cart.length === 0) return Swal.fire('Error', 'La orden está vacía', 'error');
    
        const activeStage = IxeaStages.activeStage;
        const mainPos = document.getElementById(activeStage);
    
        this.groupCartBySupplier(); // Agrupamos por proveedores
        const supplierIds = Object.keys(this.groupedCart).join(',');
        
        try {
            Swal.fire({
                title: 'Preparando Checkout',
                text: 'Consultando datos de proveedores...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    // ESTO es el "seguro de vida": forzamos al contenedor global de Swal
                    const container = Swal.getContainer();
                    if (container) {
                        container.style.zIndex = "3000";
                    }
                }
            });
    
            // Consulta datos de proveedores
            const response = await fetch(`/api/get-data?action=get_suppliers_info&ids=${supplierIds}`);
            if (!response.ok) throw new Error("Error en la respuesta del servidor");
            this.suppliersInfo = await response.json();
            Swal.close();
        
            const overlay = document.createElement('div');
            overlay.id = "checkout-overlay";
            overlay.className = "position-absolute top-0 start-0 w-100 h-100 bg-white z-3 animate__animated animate__fadeInUp overflow-auto";
        
            let suppliersHtml = '<div class="accordion accordion-flush shadow-sm rounded-4 overflow-hidden" id="accordionSuppliers">';
        
            Object.entries(this.groupedCart).forEach(([sId, data], index) => {
                const info = this.suppliersInfo.find(s => s.supplier_id == sId) || { company_name: 'N/A', rfc: 'N/A', credit_days: 0 };
                const showCredit = parseInt(info.credit_days) > 0; // Si es mayor a 0, mostramos el switch
        
                suppliersHtml += `
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button ${index === 0 ? '' : 'collapsed'} py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${sId}">
                                <div class="d-flex justify-content-between w-100 align-items-center pe-3">
                                    <div>
                                        <div class="fw-bold text-dark">${info.company_name}</div>
                                        <div class="small text-muted">${info.rfc}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-muted">${data.items.length} ítems</div>
                                        <div class="fw-bold text-sforange" id="supplier-total-${sId}">$${toCurrency(data.subtotal*1.16)}</div>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse-${sId}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#accordionSuppliers">
                            <div class="accordion-body bg-light-subtle">
                                <div class="row g-2 mb-3 bg-white px-3 pb-2 rounded-3 shadow-sm border border-sforange">
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted">DOCUMENTO</label>
                                        <select class="form-select form-select-sm bg-light border-0" id="doc-type-${sId}">
                                            <option value="order">Orden de Compra</option>
                                            <option value="referral">Remisión de Entrada</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        ${showCredit ? `
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input switch-sforange" type="checkbox" id="is-credit-${sId}" checked>
                                                <label class="form-check-label small fw-bold" for="is-credit-${sId}">Crédito</label>
                                            </div>
                                        ` : '<span class="text-muted small italic">Solo pago de contado</span>'}
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input switch-sforange" type="checkbox" id="is-tax-${sId}" onchange="PurchasesApp.refreshCheckoutTotals()" checked>
                                                <label class="form-check-label small fw-bold" for="is-tax-${sId}">Fiscal</label>
                                            </div>
                                    </div>
                                </div>
        
                                <div class="table-responsive bg-white rounded-3 shadow-sm">
                                    <table class="table align-middle mb-0">
                                        <thead class="bg-light small text-uppercase text-muted">
                                            <tr>
                                                <th class="ps-4">Producto</th>
                                                <th>Cantidad</th>
                                                <th>Costo Unit.</th>
                                                <th class="text-end pe-4">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.items.map((item, i) => `
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold">${item.name}</div>
                                                        <small class="text-muted">${item.sku}</small>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border">${item.qty} ${item.unit}</span></td>
                                                    <td style="width: 140px;">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-transparent border-0 pe-1">$</span>
                                                            <input type="number" class="form-control form-control-sm" 
                                                                   value="${item.cost}" 
                                                                   onchange="PurchasesApp.updateItemCost(${item.unit_id}, this.value)">
                                                        </div>
                                                    </td>
                                                    <td class="text-end pe-4 fw-bold" id="subtotal-line-${item.unit_id}">$${toCurrency(item.qty * item.cost)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
            suppliersHtml += '</div>';
        
            overlay.innerHTML = `
                <div class="container py-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="fw-bold mb-0"><i class="bi bi-cart-check text-sforange"></i> Confirmar Compra</h1>
                        <button class="btn btn-light rounded-pill px-4" onclick="document.getElementById('checkout-overlay').remove()">
                            <i class="bi bi-arrow-left"></i> Volver a Carrito
                        </button>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-lg-8">
                            ${suppliersHtml}
                        </div>
                        <div class="col-lg-4">
                            <div class="card border-0 shadow rounded-4 p-4 sticky-top">
                                <h4 class="fw-bold mb-2">Resumen General</h4>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Productos:</span>
                                    <span class="fw-bold">${this.cart.length}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Proveedores:</span>
                                    <span class="fw-bold">${Object.keys(this.groupedCart).length}</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="h4 mb-0">IVA:</span>
                                    <span class="h3 mb-0 fw-bold" id="checkout-tax-total" >$${toCurrency(this.calculateTotal()*.16)}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <span class="h5 mb-0">Total:</span>
                                    <span class="h4 mb-0 fw-bold text-sforange" id="checkout-grand-total">$${toCurrency(this.calculateTotal()*1.16)}</span>
                                </div>
                                <button class="btn btn-sforange text-white fw-bold w-100 rounded-4 shadow p-2" onclick="PurchasesApp.processPurchaseOrder()">
                                    <i class="bi bi-send-check-fill me-2"></i> Generar ordenes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            
            mainPos.appendChild(overlay);
        } catch (err) {
            Swal.close();
            Swal.fire('Error', 'No se pudieron obtener los datos de los proveedores', 'error');
            console.error(err);
        }
    },
    
    updateItemCost: function(unitId, newCost) {
        const cost = parseFloat(newCost);
        if (isNaN(cost) || cost < 0) return;
    
        // 1. Actualizar el costo en el carrito principal (this.cart)
        // Buscamos por unit_id ya que es la clave única de la variante
        const item = this.cart.find(i => i.unit_id == unitId);
        if (item) {
            item.cost = cost; // Actualizamos el precio/costo
        }
    
        // 2. Re-agrupar para actualizar los subtotales de this.groupedCart
        this.groupCartBySupplier();
    
        // 3. Actualizar la UI sin cerrar el overlay
        this.refreshCheckoutTotals(unitId);
    },
    
    calculateTax: function() {
        let totalTax = 0;
        Object.keys(this.groupedCart).forEach(sId => {
            const taxSwitch = document.getElementById(`is-tax-${sId}`);
            // Si el switch existe y está activo, calculamos el 16% del subtotal de ese proveedor
            if (taxSwitch && taxSwitch.checked) {
                totalTax += this.groupedCart[sId].subtotal * 0.16;
            }
        });
        return totalTax;
    },
    
    calculateTotal: function() {
        let grandTotal = 0;
        Object.keys(this.groupedCart).forEach(sId => {
            const subtotal = this.groupedCart[sId].subtotal;
            const taxSwitch = document.getElementById(`is-tax-${sId}`);
            
            grandTotal += subtotal;
            // Si es fiscal, sumamos el IVA al total global
            if (taxSwitch && taxSwitch.checked) {
                grandTotal += subtotal * 0.16;
            }
        });
        return grandTotal;
    },
    
    refreshCheckoutTotals: function(unitId) {
        // 1. Si se cambió un costo específico, actualizamos esa línea primero
        if (unitId) {
            const item = this.cart.find(i => i.unit_id == unitId);
            const lineSubtotalEl = document.getElementById(`subtotal-line-${unitId}`);
            if (item && lineSubtotalEl) {
                lineSubtotalEl.innerText = `$${toCurrency(item.qty * item.cost)}`;
            }
        }
    
        // 2. Actualizar totales por Proveedor (Accordion Header)
        Object.keys(this.groupedCart).forEach(sId => {
            const supplierTotalEl = document.getElementById(`supplier-total-${sId}`);
            if (supplierTotalEl) {
                const subtotal = this.groupedCart[sId].subtotal;
                const isTax = document.getElementById(`is-tax-${sId}`)?.checked;
                
                // Si el proveedor es fiscal, el encabezado muestra Subtotal + IVA
                const totalWithTax = isTax ? subtotal * 1.16 : subtotal;
                supplierTotalEl.innerText = `$${toCurrency(totalWithTax)}`;
            }
        });
    
        // 3. Actualizar Resumen General (Panel Derecho)
        const taxEl = document.getElementById('checkout-tax-total');
        const grandTotalEl = document.getElementById('checkout-grand-total');
        
        if (taxEl) taxEl.innerText = `$${toCurrency(this.calculateTax())}`;
        if (grandTotalEl) grandTotalEl.innerText = `$${toCurrency(this.calculateTotal())}`;
    },
    
    processPurchaseOrder: async function() {
        const orders = [];
    
        // Recopilamos la configuración de cada proveedor desde el Accordion
        Object.keys(this.groupedCart).forEach(sId => {
            orders.push({
                supplier_id: sId,
                doc_type: document.getElementById(`doc-type-${sId}`).value,
                is_credit: document.getElementById(`is-credit-${sId}`)?.checked ? 1 : 0,
                is_tax: document.getElementById(`is-tax-${sId}`)?.checked ? 1 : 0,
                items: this.groupedCart[sId].items,
                total: this.groupedCart[sId].subtotal
            });
        });
    
        try {
            Swal.fire({ 
                title: 'Procesando órdenes...', 
                allowOutsideClick: false, 
                didOpen: () => {
                    Swal.showLoading();
                    // ESTO es el "seguro de vida": forzamos al contenedor global de Swal
                    const container = Swal.getContainer();
                    if (container) {
                        container.style.zIndex = "3000";
                    }
                }
            });
    
            const response = await fetch('/modules/purchases/purchase-handler?action=process_purchase', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orders: orders })
            });
    
            const result = await response.json();
    
            if (result.success) {
                Swal.close();
                this.cart = []; // Limpiamos el carrito local
                this.renderProcessingSummary(result.processed_orders); // El nuevo layout
            } else {
                Swal.fire('Error', result.message || 'Error al procesar', 'error');
            }
        } catch (error) {
            Swal.close();
            Swal.fire('Error de red', 'No se pudo conectar con el servidor', 'error');
        }
    },
    
    renderProcessingSummary: function(processedOrders) {
        const mainPos = document.getElementById(IxeaStages.activeStage);
        const overlay = document.getElementById('checkout-overlay');
        
        // Mapeamos los resultados con la info de proveedores que ya tenemos en this.suppliersInfo
        this.summaryOrders = processedOrders.map(order => {
            const info = this.suppliersInfo.find(s => s.supplier_id == order.supplier_id) || {};
            return { ...order, ...info };
        });
    
        overlay.innerHTML = `
            <div class="container py-2 animate__animated animate__fadeIn">
                <div class="text-center mb-3">
                    <div class="display-1 text-sfgreen"><i class="bi bi-check2-circle"></i></div>
                    <h2 class="fw-bold">Órdenes Procesadas</h2>
                    <p class="text-muted">Se han registrado correctamente las compras en el sistema.</p>
                </div>
    
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="list-group shadow-sm rounded-4 overflow-hidden">
                            ${this.summaryOrders.map(order => `
                                <div class="list-group-item p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <h5 class="mb-1 fw-bold">${order.company_name}</h5>
                                            <div class="small text-muted">Folio: <span class="badge bg-dark">${order.folio}</span></div>
                                        </div>
                                        <div class="col-md-7 text-end">
                                            <div class="btn-group">
                                                <button class="btn btn-outline-secondary" onclick="PurchasesApp.print('${order.purchase_id}', 'ticket')">
                                                    <i class="bi bi-receipt"></i>
                                                </button>
                                                
                                                ${order.phone ? `
                                                    <button class="btn btn-outline-success" onclick="PurchasesApp.sendWA('${order.purchase_id}')">
                                                        <i class="bi bi-whatsapp"></i>
                                                    </button>
                                                ` : `
                                                    <button class="btn btn-outline-light text-muted" disabled><i class="bi bi-whatsapp"></i></button>
                                                `}
    
                                                ${order.email ? `
                                                    <button class="btn btn-outline-primary" onclick="PurchasesApp.sendEmail('${order.purchase_id}')">
                                                        <i class="bi bi-envelope"></i>
                                                    </button>
                                                ` : `
                                                    <button class="btn btn-outline-light text-muted" disabled><i class="bi bi-envelope"></i></button>
                                                `}
                                                
                                                <button class="btn btn-sforange text-white" onclick="PurchasesApp.print('${order.purchase_id}', 'pdf')">
                                                    PDF <i class="bi bi-file-earmark-pdf"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="text-center mt-5">
                            <button class="btn btn-sforange btn-lg text-white rounded-pill px-5" onclick="PurchasesApp.resetPurchase()">Finalizar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    sendWA: async function(purchaseId) {
        // 1. Buscamos la orden en nuestro resumen guardado
        const order = this.summaryOrders.find(i => i.purchase_id == purchaseId);
        
        if (!order) {
            Swal.fire('Error', 'No se encontró la información de la orden', 'error');
            return;
        }
    
        // 2. Generamos el link real (aquí es donde fallaba antes porque enviabas el teléfono como ID)
        const urlReal = await this.print(purchaseId, 'share'); 
        if (!urlReal) return; 
        
        const cleanPhone = order.phone.replace(/\D/g, ''); 
        
        // 3. Mensaje ultra-personalizado
        const saludo = order.contact_name ? `Hola ${order.contact_name},` : `Hola,`;
        
        const text = encodeURIComponent(
            `*${order.company_name}*\n` +
            `*ORDEN DE COMPRA: ${order.folio}*\n` +
            `__________________________________\n\n` +
            `${saludo}\n\n` +
            `Le envío el detalle de nuestra orden de compra.\n\n` +
            `*Consultar y Descargar aquí:*\n` +
            `${urlReal}\n\n` +
            `_Favor de confirmar recepción y tiempo estimado de entrega._\n\n` +
            `Atentamente,\n` +
            `${window.IXEA_USER.user_name},\n` +
            `*Tlapalería y Ferretería Diego*`
        );
    
        window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank');
    },
    
    sendEmail: async function(purchaseId) {
        const indicador = document.getElementById('gmail-indicator');
        const isConnected = indicador ? indicador.dataset.connected === 'true' : false;
        
        if (!isConnected) {
            Swal.fire({
                title: 'Gmail no vinculado',
                text: 'Para enviar la orden, primero vincula tu cuenta de Gmail.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Vincular ahora',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    GmailApi.gmailConfirmation();
                }
            });
            return; // Bloqueamos el envío
        }
        
        const order = this.summaryOrders.find(i => i.purchase_id == purchaseId);
        if (!order) return;
    
        // Mostrar un loader de SweetAlert mientras se envía
        Swal.fire({
            title: 'Enviando Orden...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    
        const urlReal = await this.print(purchaseId, 'share');
        if (!urlReal) { Swal.close(); return; }
    
        const formData = new FormData();
        formData.append('folio', order.folio);
        formData.append('email', order.email);
        formData.append('url', urlReal);
        formData.append('company_name', order.company_name);
        formData.append('contact_name', order.contact_name);
    
        try {
            const response = await fetch('/modules/purchases/mail-purchase-order', {
                method: 'POST',
                body: formData
            });
            
            
            const rawResponse = await response.text();
            let res;
            try {
                res = JSON.parse(rawResponse);
            } catch (err) {
                // SI FALLA EL JSON: Aquí es donde PHP mandó un Error 500
                console.error("ERROR CRÍTICO DEL SERVIDOR (PHP):", err);
                Swal.fire('Error 500', 'El servidor murió. Revisa la consola para ver el error de PHP.', 'error');
                return; // Detenemos la ejecución
            }
            
            
            if (res.success) {
                console.log("Exito - Pasos del Servidor:", res.steps);
                Swal.fire('¡Enviado!', 'La orden se envió directamente.', 'success');
            } else if (res.message.includes('invalid_grant')) {
                // Si el token ya no sirve, avisamos y podríamos limpiar la UI
                Swal.fire('Sesión Expirada', 'La conexión con Gmail se perdió. Por favor, vuelve a vincular tu cuenta.', 'warning');
            } else {
                console.error("DEBUG IXEA - Fallo en la petición:", res.message);
                console.group("DEBUG IXEA - Listado de Pasos");
                res.steps.forEach((step, index) => {
                    console.log(`Paso ${index}: `, step);
                });
                console.groupEnd();
                
                Swal.fire('Error', 'Error en el servicio', 'error');
            }
        } catch (err) {
            console.error("DEBUG IXEA - Fallo en la petición:", err);
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    },
    
    print: async function(purchaseId, type) {
        try {
            // 1. Bloqueo visual
            Swal.fire({
                title: 'Generando documento',
                didOpen: () => { Swal.showLoading(); },
                allowOutsideClick: false
            });
    
            if (type === 'ticket') {
                // Caso ticket impreso
                const response = await fetch(`/api/get-data?action=get_purchase_details&purchase_id=${purchaseId}`);
                if (!response.ok) throw new Error('Error al obtener datos del ticket');
                const data = await response.json();
                
                IxeaUtils.printPurchaseTicket(data);
                Swal.close(); // Cerramos aquí porque el ticket es síncrono después de esto
                return;
    
            } else {
                // Caso PDF o URL para compartir
                const response = await fetch(`/modules/purchases/pdf-purchase?purchase_id=${purchaseId}`);
                if (!response.ok) throw new Error('Error al generar el PDF');
                const data = await response.json();
    
                if (data.success) {
                    Swal.close(); // Cerramos antes de abrir ventana o retornar
    
                    if (type === 'pdf') {
                        // Abrir pdf
                        window.open(data.url, '_blank');
                    } else {
                        // Si es para WhatsApp o Mail, retornamos la URL completa
                        return data.url; 
                    }
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            }
    
        } catch (err) {
            Swal.close();
            Swal.fire('Error', err.message || 'No se pudo procesar la solicitud', 'error');
            console.error(err);
            return null; // Retornamos null para que quien llamó a la función sepa que falló
        }
    },

    resetPurchase: function() {
        // 1. Limpieza de Datos Críticos
        this.cart = [];          // Vaciamos el carrito real
        this.groupedCart = {};   // Limpiamos la agrupación por proveedores
        this.suppliersInfo = []; // Limpiamos info proveedor
        this.summaryOrders = []; // Limpiamos info ordenes
        
        // 2. Reset de Estado de Interfaz
        this.selectedSupplierId = null;
        this.radarMode = 'low';
        
        // 3. Limpieza de UI
        const overlay = document.getElementById('checkout-overlay');
        if (overlay) overlay.remove();
        
        const resultsContainer = document.getElementById('purchase-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '';
        }
    
        // 4. Renderizamos el carrito (que ahora está vacío) para que desaparezcan los indicadores de cantidad y subtotales.
        this.renderCart(); 
        
        const searchInput = document.getElementById('purchase-product-search');
        if (searchInput) searchInput.value = '';
        const quickContainer = document.getElementById('purchase-results');
        if (quickContainer) quickContainer.html = '';
        
        const advInput = document.getElementById('adv-search-input-purchase');
        if (advInput) searchInput.value = '';
        const advContainer = document.getElementById('adv-search-results-purchase');
        if (advContainer) advContainer.html = '';
        
    },
    
    /** Lanza el modal de historial de compras **/
    showPurchaseHistory: function() {
        IxeaStages.openModal({
            templateId: 'tpl-purchase-history',
            size: 'xl',
            scrollable: true,
            onOpen: () => {
                this.performPurchaseSearch();
                // Listener para Enter
                document.getElementById('hist-purchase-search-folio')?.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') this.performPurchaseSearch();
                });
            }
        });
    },
    
    /** Ejecuta la búsqueda en el servidor **/
    performPurchaseSearch: async function() {
        const list = document.getElementById('history-purchases-list');
        const query = document.getElementById('hist-purchase-search-folio')?.value.trim() || '';
        const status = document.getElementById('hist-purchase-filter-status')?.value || '';
        
        // 1. Estado de carga
        list.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-orange"></div>
                <p class="mt-2 text-muted">Consultando archivos...</p>
            </div>`;
    
        try {
            const res = await fetch(`/api/get-data?action=get_purchases&query=${query}&payment_status=${status}`);
            const data = await res.json();
            console.log(data);
            const purchases = data.purchases;
            if (!purchases || purchases.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-5 opacity-50">
                        <i class="bi bi-folder-x mb-2 d-block h1"></i>
                        <p>No se encontraron registros de compra.</p>
                    </div>`;
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Folio</th>
                                <th>Proveedor</th>
                                <th class="text-center">Orden</th>
                                <th class="text-center">Pago</th>
                                <th class="text-center">Entrada</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;

            purchases.forEach(p => {
                const isTaxable = p.is_taxable === 1;
                const isPaid = p.payment_status === 'paid';
                const isCancelled = p.payment_status === 'cancelled';
                
                // Calculo de IVA
                let total = p.total_amount;
                if (isTaxable) total *= 1.16;
                
                // Estética de la fila según estado
                let sPaidBadge = '';
                if (p.payment_status === 'pending') sPaidBadge = '<span class="badge bg-warning text-dark">Pendiente</span>';
                else if (p.payment_status === 'paid') sPaidBadge = '<span class="badge bg-success">Pagado</span>';
                else sPaidBadge = '<span class="badge bg-danger">Cancelado</span>';
                
                let sReceiptBadge = '';
                if (p.received_status === 'pending') sReceiptBadge = '<span class="badge bg-warning text-dark">Pendiente</span>';
                else if (p.received_status == 'received') sReceiptBadge = '<span class="badge bg-success">Recibido</span>';
                else sReceiptBadge = '<span class="badge bg-danger">Cancelado</span>';

                html += `
                    <tr class="${isCancelled ? 'opacity-50' : ''}">
                        <td class="ps-3 fw-bold text-orange">${p.folio}</td>
                        <td>
                            <div class="fw-bold">${p.company_name || 'Proveedor N/A'}</div>
                            <div class="text-muted" style="font-size: 0.75rem">${p.rfc}</div>
                        </td>
                        <td class="text-center">${p.operation_date}</td>
                        <td class="text-center">${sPaidBadge}</td>
                        <td class="text-center">${sReceiptBadge}</td>
                        <td class="text-end fw-bold">$${toCurrency(total)}</td>
                        <td class="text-center">
                            <div class="btn-group shadow-sm">
                                <button class="btn btn-sm btn-outline-secondary" title="Ver Detalles" onclick="PurchasesApp.print('${p.purchase_id}', 'ticket')">
                                    <i class="bi bi-eye"></i>
                                </button>
                                ${(!isCancelled) ? `
                                    <button class="btn btn-sm btn-outline-danger" title="Cancelar Orden" onclick="PurchasesApp.cancelPurchase('${p.purchase_id}')">
                                        <i class="bi bi-x-lg"></i></i>
                                    </button>` : ''}
                            </div>
                        </td>
                    </tr>`;
            });

            html += '</tbody></table></div>';
            list.innerHTML = html;
            
        } catch (err) {
        console.error("[Purchases History Error]:", err);
        list.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Error al cargar el historial: ${err.message}
            </div>`;
        }
    },
    
    cancelPurchase: async function(purchaseId) {
        const result = await Swal.fire({
            title: '¿Confirmar Cancelación?',
            text: "Esta acción revertirá los movimientos de stock y financieros asociados. Es una operación irreversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, cancelar orden',
            cancelButtonText: 'No, regresar'
        });
    
        if (result.isConfirmed) {
            try {
                const res = await fetch('/modules/purchases/purchase-handler?action=cancel_purchase', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ purchase_id: purchaseId })
                });
                const data = await res.json();
    
                if (data.success) {
                    Swal.fire('Cancelada', data.message, 'success');
                    this.performPurchaseSearch(); // Recargar historial
                } else {
                    throw new Error(data.message);
                }
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }
    }
};