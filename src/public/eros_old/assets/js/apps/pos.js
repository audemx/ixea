/** assets/js/apps/pos.js **/
window.PosApp = {
    cart: [],
    catalog: {},
    
    init: function() {
        console.log("POS Initialized...");
        document.getElementById('product-search').focus();
        this.bindEvents();
    },

    bindEvents: function() {
        const activeStage = IxeaStages.activeStage;
        if (!activeStage || activeStage !== 'stage-pos') return;

        // Atajos de teclado
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') {
                e.preventDefault();
                this.openSearch();
            }
            if (e.key === 'F12') {
                e.preventDefault();
                
                const btn = document.getElementById('pos-btn-checkout'); 
                if (btn) PosApp.checkout({ currentTarget: btn });
            }
        });

        // Búsqueda rápida al escribir
        const searchInput = document.getElementById('product-search');
        searchInput.addEventListener('input', (e) => {
            if(e.target.value.length > 2) this.quickSearch(e.target.value);
        });
    },

    quickSearch: function(term) {
        fetch(`/api/get-data?action=search_products&term=${encodeURIComponent(term)}`)
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
        const container = document.getElementById('quick-results');
        container.innerHTML = products.map(p => `
            <div class="col">
                <div class="card product-card h-100 rounded-4 p-2" data-id="${p.unit_id}" onclick="PosApp.addToCart(${p.unit_id})">
                    <div class="card-body p-2">
                        <div class="fw-bold small">${p.name}</div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="badge bg-light text-dark font-monospace">${p.sku}</span>
                            <span class="fw-bold text-primary">$${toCurrency(p.price)}</span>
                        </div>
                        <div class="text-secondary mt-1" style="font-size: 0.7rem;">
                            Unidad: ${p.unit} | Stock: ${Number((parseFloat(p.stock)/parseFloat(p.factor)).toFixed(2))}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    },
    
    validateStock: function(product, requestedQty) {
        const stockBase = parseFloat(product.stock);
        const factor = parseFloat(product.factor) || 1;
        
        let baseUsedByOtherUnits = this.cart
            .filter(item => item.product_id === product.product_id && item.unit_id !== product.unit_id)
            .reduce((sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.factor)), 0);
    
        const targetBase = requestedQty * factor;
        const totalImpactBase = baseUsedByOtherUnits + targetBase;
    
        if (totalImpactBase > stockBase) {
            const availableBase = stockBase - baseUsedByOtherUnits;
            return { valid: false, adjustTo: availableBase / factor };
        }
        
        return { valid: true };
    },
    
    addToCart: function(unitId) {
        const product = this.catalog[unitId];
        if (!product) return;
        
        const existing = this.cart.find(item => item.unit_id === unitId);
        const currentQty = existing ? parseFloat(existing.qty) : 0;
        const targetQty = currentQty + 1; // La meta es subir en 1
    
        // Validamos la cantidad FINAL deseada (targetQty)
        const validation = this.validateStock(product, targetQty);
        
        if (validation.valid) {
            if (existing) existing.qty = targetQty;
            else this.cart.push({ ...product, qty: 1 });
        } else if (validation.adjustTo !== undefined) {
            const maxPossible = parseFloat(validation.adjustTo);
            
            // Si el máximo posible es mayor a lo que ya tengo, ajustamos
            if (maxPossible > currentQty) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stock Limitado',
                    text: `Solo puedes agregar hasta ${maxPossible.toFixed(3)} ${product.unit}.`,
                    confirmButtonColor: '#f89406'
                });
                if (existing) existing.qty = maxPossible;
                else this.cart.push({ ...product, qty: maxPossible });
            } else {
                Swal.fire({ icon: 'error', title: 'Agotado', text: 'Ya tienes todo el stock disponible en el carrito.' });
            }
        }
        this.renderCart();
    },
    
    updateQty: function(index, newQty) {
        const itemInCart = this.cart[index];
        newQty = parseFloat(newQty);
    
        if (newQty <= 0 || isNaN(newQty)) {
            this.removeItem(index);
            return;
        }
    
        // Aquí también le preguntamos: "¿Es válido que este producto tenga NEWQTY?"
        const validation = this.validateStock(itemInCart, newQty);
    
        if (validation.valid) {
            this.cart[index].qty = newQty;
        } else if (validation.adjustTo !== undefined) {
            this.cart[index].qty = parseFloat(validation.adjustTo);
            Swal.fire({
                icon: 'warning',
                title: 'Cantidad Ajustada',
                text: `Ajustado al máximo disponible: ${validation.adjustTo.toFixed(3)}`,
                timer: 2000
            });
        }
        this.renderCart(); 
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
                    position: 'top-end',
                    icon: 'success',
                    title: 'Carrito vacío',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        });
    },
    
    renderCart: function() {
        const container = document.getElementById('cart-items');
        let total = 0;
        
        if (this.cart.length === 0) {
            container.innerHTML = `
                <div class="text-center text-secondary mt-5 pt-5">
                    <i class="bi bi-cart3 fs-1 opacity-25"></i>
                    <p class="mt-2">El carrito está vacío</p>
                </div>`;
            document.getElementById('total-val').innerText = "$0.00";
            return;
        }
    
        container.innerHTML = this.cart.map((item, index) => {
            const sub = item.price * item.qty;
            total += sub;
            return `
                <div class="cart-item py-1 border-bottom animate__animated animate__fadeIn" data-id="${item.unit_id}">
                    <div class="d-flex justify-content-between align-items-start mb-0">
                        <div class="d-flex flex-column lh-sm">
                            <div class="fw-bold small text-truncate" style="max-width: 180px;">${item.name}</div>
                            <div class="text-secondary" style="font-size: 0.7rem;">${item.sku}</div>
                        </div>
                        <button class="btn btn-sm text-danger p-0 m-0" onclick="PosApp.removeItem(${index})">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-0">
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <input type="number" class="form-control form-control-sm border-0 bg-light fw-bold py-0 text-end" 
                                   value="${item.qty}" 
                                   onchange="PosApp.updateQty(${index}, this.value)">
                        </div>
                        <div class="text-end">
                            <span class="small muted">${item.unit} x $${toCurrency(item.price)}</span>
                            <div class="fw-bold text-primary">$${toCurrency(sub)}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    
        document.getElementById('total-val').innerText = `$${toCurrency(total)}`;
    },

    openSearch: function() {
        let modalEl = document.getElementById('searchModal');
        if (!modalEl) return;
    
        // 1. Moverlo al body solo si no está ahí ya
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
        
        // 2. Inicializar o recuperar instancia
        if (!this.searchModal) {
            this.searchModal = new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: true
            });
    
            // Event listener para el input (solo se agrega una vez)
            const advInput = document.getElementById('adv-search-input');
            if (advInput) {
                advInput.addEventListener('keydown', (e) => {
                    const value = e.target.value;
                    
                    // Detecta la tecla Enter ('Enter') o Espacio (' ')
                    if (e.key === 'Enter' || e.key === ' ') {
                        if (value.trim().length > 2) {
                            this.advancedSearch(value);
                        }
                    }
                });
            }
    
            // LIMPIEZA: Si el modal se cierra, removemos manualmente cualquier "backdrop" huérfano
            modalEl.addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        }
    
        this.searchModal.show();
        modalEl.addEventListener('shown.bs.modal', () => {
          document.getElementById('adv-search-input').focus();
        });
    },
    
    advancedSearch: function(term) {
        const container = document.getElementById('adv-search-results');
        // Feedback visual inmediato
        container.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>`;
    
        fetch(`/api/get-data?action=adv_search_products&term=${encodeURIComponent(term)}`)
            .then(res => {
                if (!res.ok) throw new Error('Error en la respuesta del servidor');
                return res.json();
            })
            .then(data => {
                // Alimentar el catálogo global antes de renderizar
                if (data.length > 0) {
                    data.forEach(p => {
                        this.catalog[p.unit_id] = p; 
                    });
                }
                // Delegar el dibujo de la tabla al método especializado
                this.renderAdvancedResults(data);
            })
            .catch(err => {
                console.error("Error Advanced Search:", err);
                container.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error: ${err.message}</td></tr>`;
            });
    },
    
    renderAdvancedResults: function(products) {
        const container = document.getElementById('adv-search-results');
        
        if (products.length === 0) {
            container.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-secondary">No se encontraron productos</td></tr>`;
            return;
        }
    
        container.innerHTML = products.map(p => `
            <tr class="animate__animated animate__fadeIn cursor-pointer" 
                onclick="PosApp.addToCart(${p.unit_id}); PosApp.searchModal.hide();">
                <td><span class="badge bg-light text-dark font-monospace">${p.sku}</span></td>
                <td>
                    <div class="fw-bold text-dark">${p.name}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">
                        <span class="badge bg-secondary opacity-75 me-1">${p.category || 'General'}</span>
                        <span class="badge bg-secondary opacity-75">${p.brand || 'S/M'}</span>
                    </div>
                </td>
                <td><small class="text-secondary">${p.unit}</small></td>
                <td>
                    <span class="fw-bold ${parseFloat(p.stock) <= 0 ? 'text-danger' : 'text-success'}">
                        ${(Number(parseFloat(p.stock) / parseFloat(p.factor)).toFixed(2))}
                    </span>
                </td>
                <td class="fw-bold text-dark text-end">
                    $${toCurrency(p.price)}
                </td>
            </tr>
        `).join('');
    },

    checkout: function(event) {
        const btn = event.currentTarget;
        if (!IxeaBouncer.lockBtn(btn)) return;
        
        // 1. Mostrar loading mientras re-validamos stock real en el servidor
        Swal.fire({
            title: 'Verificando inventario...',
            didOpen: () => { Swal.showLoading(); }
        });
    
        // 2. Enviamos el carrito actual al servidor para verificar disponibilidad real
        fetch('/modules/sales/validate_cart_stock', {
            method: 'POST',
            body: JSON.stringify({ items: this.cart })
        })
        .then(res => res.json())
        .then(response => {
            Swal.close();
            if (response.error) {
                Swal.fire('Error de Stock', response.message, 'error');
                IxeaBouncer.releaseBtn(btn);
                return;
            }
    
            this.renderCheckoutLayout();
            IxeaBouncer.releaseBtn(btn);
        })
        .catch(err => {
            Swal.close();
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            IxeaBouncer.releaseBtn(btn);
        });
    },
    
    renderCheckoutLayout: function() {
        const activeStage = IxeaStages.activeStage;
        if (!activeStage || activeStage !== 'stage-pos') return;
        const mainPos = document.getElementById(activeStage);
        
        // Generamos un Overlay
        const overlay = document.createElement('div');
        overlay.id = "checkout-overlay";
        overlay.className = "position-absolute top-0 start-0 w-100 h-100 bg-white z-3 animate__animated animate__fadeInUp";
        overlay.innerHTML = `
            <div class="container py-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h1 class="fw-bold"><i class="bi bi-shield-check text-primary"></i> Confirmar Pedido</h1>
                    <button class="btn btn-light rounded-pill" onclick="document.getElementById('checkout-overlay').remove()">
                        <i class="bi bi-arrow-left"></i> Volver al carrito
                    </button>
                </div>
                
                <div class="row g-5">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio
                                            <button class="btn btn-sm btn-outline-secondary border-0" onclick="PosApp.unlockPrice()"><i class="bi bi-lock-fill"></i>
                                            </button>
                                        </th>
                                        <th>Descuento
                                            <button class="btn btn-sm btn-outline-secondary border-0" onclick="PosApp.unlockDiscount()"><i class="bi bi-lock-fill"></i>
                                            </button>
                                        </th>
                                        <th class="text-end pe-4">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${this.cart.map((item, i) => `
                                        <tr data-id="${item.unit_id}">
                                            <td class="ps-4">
                                                <div class="fw-bold">${item.name}</div>
                                                <small class="text-muted">${item.sku}</small>
                                            </td>
                                            <td>${Number(parseFloat(item.qty).toFixed(2))} ${item.unit}</td>
                                            <td>
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <span>$ </span>
                                                    <input type="text" id="price-input-${i}" class="form-control form-control-sm pos-price-input" style="width: 80px;" onchange="PosApp.applyLinePrice(${i}, this.value)" value="${toCurrency(item.price)}" readonly>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <input type="text" id="discount-input-${i}" class="form-control form-control-sm pos-discount-input" placeholder="0" style="width: 60px;" onchange="PosApp.applyLineDiscount(${i}, this.value)" readonly>
                                                    <span> %</span>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4 fw-bold" id="subtotal-line-${i}">
                                                $${toCurrency(item.qty * item.price)}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
    
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 p-2 bg-light">
                            <div class="mb-2 position-relative">
                                <label class="form-label">Cliente</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                    <input type="text" id="customer-search-input" class="form-control small" 
                                           placeholder="PÚBLICO EN GENERAL" autocomplete="off"
                                           oninput="PosApp.searchCustomers(this.value)">
                                </div>
                                <div id="customer-results" class="list-group position-absolute w-100 z-3 shadow-sm d-none"></div>
                            </div>
                            
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="require-invoice">
                                <label class="form-check-label fw-bold" for="require-invoice">Requiere Factura</label>
                            </div>
    
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-3 h4 fw-bold text-primary">
                                <span>Total: </span>
                                <span id="final-total">$${toCurrency(this.calculateTotal())}</span>
                            </div>
    
                            <button class="btn btn-primary w-100 btn-lg rounded-4 shadow" onclick="PosApp.processOrder(event)">
                                <i class="bi bi-check-circle-fill me-2"></i> Procesar Venta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        mainPos.appendChild(overlay);
        
        this.initCustomerEvents();
    },

    calculateTotal: function() {
        return this.cart.reduce((sum, item) => {
            // Si el item tiene un descuento aplicado (ej: 10% o monto fijo)
            const subtotal = item.qty * item.price;
            const discount = this.calculateLineDiscount(item);
            return sum + (subtotal - discount);
        }, 0);
    },
    
    calculateLineDiscount: function(item) {
        if (!item.discountValue || isNaN(item.discountValue)) return 0;
        
        const subtotal = parseFloat(item.qty) * parseFloat(item.price);
        
        // Tratamos el valor siempre como porcentaje (es lo estándar en retail)
        const percent = parseFloat(item.discountValue) / 100;
        return subtotal * percent;
    },
    
    applyLineDiscount: function(index, value) {
        // Aseguramos que el valor sea numérico y no pase de 100%
        let numValue = parseFloat(value) || 0;
        if (numValue > 100) numValue = 100;
        if (numValue < 0) numValue = 0;

        // Guardamos el valor
        this.cart[index].discountValue = numValue;
        
        const item = this.cart[index];
        const subtotalOriginal = item.qty * item.price;
        const discountAmount = this.calculateLineDiscount(item);
        const subtotalFinal = subtotalOriginal - discountAmount;
        
        // Actualizamos el DOM
        document.getElementById(`subtotal-line-${index}`).innerText = 
            `$${toCurrency(subtotalFinal)}`;
        
        // Actualizamos el Total General
        const total = this.calculateTotal();
        document.getElementById('final-total').innerText = 
            `$${toCurrency(total)}`;
    },
    
    applyLinePrice: function(index, value) {
        // Aseguramos que el valor sea numérico y sea positivo
        let numValue = parseFloat(value) || 0;
        if (numValue < 0) numValue = 0;

        // Guardamos el valor
        this.cart[index].price = numValue;
        
        const currentDiscount = this.cart[index].discountValue || 0;
        
        this.applyLineDiscount(index, currentDiscount);
    },
    

    /** Buscador de Clientes Dinámico **/
    initCustomerEvents: function() {
        const customerInput = document.getElementById('customer-search-input');
        if (!customerInput) return;
    
        // Limpieza al salir (Blur)
        customerInput.addEventListener('blur', () => {
            setTimeout(() => {
                // Si no hay ID, borramos el texto para que no engañe al usuario
                if (!this.selectedCustomerId) {
                    customerInput.value = ''; 
                    const res = document.getElementById('customer-results');
                    if (res) res.classList.add('d-none');
                }
            }, 250);
        });
    
        // Control de escritura
        customerInput.addEventListener('input', (e) => {
            this.selectedCustomerId = null; // Reset inmediato al escribir
            this.searchCustomers(e.target.value);
        });
    },
    
    searchCustomers: function(term) {
        this.selectedCustomerId = null; 

        if (term.length < 2) {
            document.getElementById('customer-results').classList.add('d-none');
            return;
        }
        // Apuntamos al API centralizada con el parámetro 'action'
        fetch(`/api/get-data?action=search_customers&term=${encodeURIComponent(term)}`)
            .then(res => {
                if (!res.ok) throw new Error("Error del servidor");
                return res.json();
            })
            .then(data => {
                const list = document.getElementById('customer-results');
                // Usamos los nombres de columna: customer_id, contact_name, tax_id, contact_phone
                list.innerHTML = data.map(c => `
                    <div class="list-group-item list-group-item-action cursor-pointer" 
                         onclick="PosApp.selectCustomer(${c.customer_id}, '${c.name}')">
                        <div class="small" style='font-size: .8rem;'>${c.name}</div>
                        <small class="text-muted" style="font-size: .6rem;">
                            ${[c.rfc || 'Sin RFC', c.phone, c.mail].filter(Boolean).join(' | ')}
                        </small>
                    </div>
                `).join('');
                list.classList.remove('d-none');
            })
            .catch(err => console.error("Error en búsqueda:", err));;
    },
    
    selectCustomer: function(customer_id, name) {
        this.selectedCustomerId = customer_id;
        const input = document.getElementById('customer-search-input');
        if (input) {
            input.value = name; // Aquí se fija el nombre real
        }
        document.getElementById('customer-results').classList.add('d-none');
    },
    
    unlockDiscount: function() {
        const p = 'aplicar_descuento';
        if (IxeaBouncer.can(p)) {
            this.unlockDiscountFields();
            return;
        }
        // Corregido el nombre del objeto a IxeaBouncer
        IxeaBouncer.requestAuth(p, (res) => {
            if (res.success) this.unlockDiscountFields();
        });
    },
    
    unlockDiscountFields: function() {
        const inputs = document.querySelectorAll('.pos-discount-input');
        inputs.forEach( input => {
            input.readOnly = false;
            input.classList.add('border-primary');
        });
    },
    
    unlockPrice: function() {
        const p = 'aplicar_descuento';
        if (IxeaBouncer.can(p)) {
            this.unlockPriceFields();
            return;
        }
        // Corregido el nombre del objeto a IxeaBouncer
        IxeaBouncer.requestAuth(p, (res) => {
            if (res.success) this.unlockPriceFields();
        });
    },
    
    unlockPriceFields: function() {
        const inputs = document.querySelectorAll('.pos-price-input');
        inputs.forEach( input => {
            input.readOnly = false;
            input.classList.add('border-primary');
        });
    },
    
    processOrder: function(event) {
        if (this.cart.length === 0) return;
        
        const btn = event.currentTarget;
        if (!IxeaBouncer.lockBtn(btn)) return;
    
        // 1. Recolectar datos de cabecera
        const requiresInvoice = document.getElementById('require-invoice')?.checked || false;
        
        // 2. Preparar los items con lógica de impuestos y descuentos
        const processedItems = this.cart.map((item, index) => {
            const qty = +item.qty;
            const price = +item.price;
            const factor = +(item.factor || 1);
            
            const subtotalBruto = qty * price;
            const discountAmount = this.calculateLineDiscount(item); // Ya calcula el monto basado en el %
            const baseImport = subtotalBruto - discountAmount;
            
            // Lógica de IVA: 
            // Si el producto tiene tax O si el cliente pidió factura globalmente
            const hasTax = (parseInt(item.tax) === 1 || requiresInvoice);
            const taxRate = hasTax ? 0.16 : 0; // Ajusta según tu país (ej. 0.16 para México)
            
            // El precio ya incluye IVA (Retail), así que desglosamos:
            // base = total / (1 + tasa)
            const netSubtotal = baseImport / (1 + taxRate);
            const taxAmount = baseImport - netSubtotal;
    
            return {
                product_id: parseInt(item.product_id),
                unit_id: parseInt(item.unit_id),
                quantity: qty,
                factor: factor,
                unit_price: price,
                discount_amount: +discountAmount.toFixed(2),
                tax_amount: +taxAmount.toFixed(2),
                subtotal: +baseImport.toFixed(2),
                name: item.name // Para feedback si falla stock
            };
        });
        const total_discount = processedItems.reduce((sum, i) => sum + i.discount_amount, 0);
        const totalAmount = processedItems.reduce((sum, i) => sum + i.subtotal, 0);
    
        // 3. Feedback visual de carga
        Swal.fire({
            title: 'Procesando venta...',
            text: 'Validando inventario final',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // 4. Envío al servidor con Debugging
        const payload = {
            customer_id: this.selectedCustomerId,
            items: processedItems,
            total_amount: totalAmount,
            total_discount: total_discount,
            is_taxable: requiresInvoice ? 1 : 0,
            items_count: processedItems.length
        }
        
        fetch('/modules/sales/process-sale', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(res => {
            // Proceso exitoso
            if (res.success) {
                IxeaBouncer.releaseBtn(btn);
                const dataForTicket = {
                    folio: res.folio,
                    date: new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
                    user_name: window.IXEA_USER.user_name,
                    customer_name: document.getElementById('customer-search-input')?.value.trim() || "PÚBLICO EN GENERAL",
                    items: this.cart.map(item => {
                        const descLinea = this.calculateLineDiscount(item);
                        return {
                            quantity: parseFloat(item.qty).toFixed(3),
                            name: item.name,
                            sku: item.sku || 'N/A',
                            unit: item.unit || 'PZA',
                            price: item.price,
                            discount: descLinea, 
                            subtotal: (item.qty * item.price) - descLinea
                        };
                    }),
                    totalDiscount: 0,
                    total: 0
                };
                dataForTicket.totalDiscount = dataForTicket.items.reduce((sum, i) => sum + i.discount, 0);
                dataForTicket.total = dataForTicket.items.reduce((sum, i) => sum + i.subtotal, 0);
                
                IxeaUtils.printTicket(dataForTicket);
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Venta Exitosa!',
                    text: `Folio: ${res.folio}`,
                    showConfirmButton: false,
                    timer: 3000
                }).then((result) => {
                    // No importa si fue por el timer o por el botón, limpiamos el EROS
                    this.resetPOS();
                });
            // Proceso fallido
            } else {
                IxeaBouncer.releaseBtn(btn);
                console.error("Error:", res.message);
                console.error("Fallo en línea:", res.line);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al procesar de registro',
                    text: 'Comunicate con soporte'
                });
            }
        })
        // Fallo en la solicitud
        .catch(err => {
            IxeaBouncer.releaseBtn(btn);
            console.error("Fallo en la solicitud:", err);
            Swal.fire({
                    icon: 'error',
                    title: 'Fallo en la solicitud',
                    text: 'Comunicate con soporte'
                });
        });
    },
    
    resetPOS: function() {
        // Vaciar el carrito
        this.cart = [];
        this.renderCart();
        
        // Quitar el overlay de pago
        const overlay = document.getElementById('checkout-overlay');
        if (overlay) overlay.remove();
        
        // Resetear cliente
        this.selectedCustomerId = null; 
    
        // Limpiar y punta al buscador rápido
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        
        // Limpiar tarjetas de busqueda
        const quickResults = document.getElementById('quick-results');
        if (quickResults) {
            quickResults.innerHTML = '';
        }
        
        // Limpiar buscador avanzado
        const advInput = document.getElementById('adv-search-input');
        if (advInput) {
            advInput.value = '';
        }
        
        // Limpiar resultados buscador avanzado
        const advResults = document.getElementById('adv-search-results');
        if (advResults) {
            advResults.innerHTML = '';
        }

        console.log("POS listo para la siguiente venta.");
    }
};