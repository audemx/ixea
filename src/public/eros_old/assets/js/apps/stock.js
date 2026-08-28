/** assets/js/apps/stock.js **/
window.StockApp = {
    appColor: 'sfpurple',
    varColor: 'var(--soft-purple)',
    table: null,
    currentFilter: 'all',
    activeRow: null,
    isInitialized: false,

    init: function() {
        if (this.isInitialized) return;
        this.isInitialized = true;

        this.initTable();
        this.loadMetrics();
        this.startMonitor();
    },
    
    startMonitor: function() {
        if (!this.refreshTimer) {
            this.refreshTimer = setInterval(async () => {
                // Stage activo
                const activeStage = IxeaStages.activeStage;
                if (!activeStage || activeStage !== 'stage-stock') return;
                
                // Refrescar la tabla principal (Server-side)
                this.table.ajax.reload(null, false);
                
            }, 300000); // 5 minutos
        }
    },
    
    stopMonitor: function() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
            console.log('[Stock] Monitor: Stopped');
        }
    },
    
    destroy: function() {
        console.log("[Stock] Purging resources...");
        this.stopMonitor();
        this.isInitialized = false;
    },

    initTable: function() {
        const domHtml = '<"d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3" f <"custom-toolbar d-flex gap-2">> rt <"d-flex justify-content-between align-items-center small" ip >'
        ;
        
        const tableHtml = `
            <div class="d-flex gap-2 me-2">
                <button class="btn btn-sm btn-grey text-white" onclick="StockApp.exportInventory()">
                    <i class="bi bi-file-earmark-excel me-1"></i> Exportar
                </button>
                <button class="btn btn-sm btn-${this.appColor} text-white" onclick="StockApp.openEditProduct()">
                    <i class="bi bi-plus-lg me-1"></i> Producto
                </button>
            </div>
        `;
        
        // Configuración de DataTables con Server-side
        this.table = $('#tableProducts').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            order: [[1, 'asc']], // Ordenar por Nombre
            ajax: {
                url: '/api/get-data?action=get_inventory_datatable',
                    type: 'POST',
                    data: (d) => {
                        d.filter_type = this.currentFilter;
                    }
            },
            rowId: 'product_id',
            columns: [
                { data: 'sku', className: 'fw-bold', orderable: false },
                { data: 'name', orderable: false },
                { data: 'category_name', orderable: false, defaultContent: '<span class="text-muted small">Sin categ.</span>' },
                { data: 'brand_name', orderable: false, defaultContent: '<span class="text-muted small">-</span>' },
                { 
                    data: 'current_stock', 
                    className: 'text-center fw-bold',
                    orderable: false,
                    render: (data, type, row) => {
                        const stock = parseFloat(data);
                        const min = parseFloat(row.min_stock);
                        let color = 'text-dark';
                        if (stock <= min) color = 'text-sfred';
                        return `<span class="${color}">${Number(stock).toFixed(2)}</span>`;
                    }
                },
                { 
                    data: 'status',
                    className: 'text-center',
                    orderable: false,
                    render: (data) => {
                        const badges = {
                            'active': 'text-sfgreen',
                            'discontinued': 'text-sfyellow',
                            'suspended': 'text-sfred',
                            'archived': 'bg-dark'
                        };
                        return `<span class="badge ${badges[data] || 'bg-light'} text-uppercase" style="font-size:0.7rem">${data}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: (data, type, row) => `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-sfcyan" onclick="StockApp.auditProduct('${row.product_id}')" title="Auditar">
                                <i class="bi bi-journal-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-sfcyan" onclick="StockApp.openAnalysis('${row.product_id}')" title="Análisis ML">
                                <i class="bi bi-graph-up-arrow"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-${this.appColor}" onclick="StockApp.openEditProduct('${row.product_id}')" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>`
                }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            dom: domHtml,
            initComplete: function() {
                const groupHtml = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-0 shadow-sm">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                    </div>
                `;
                
                const api = this.api();
                const container = $(api.table().container());
                const filterDiv = container.find('.dataTables_filter');
                const input = filterDiv.find('input');
                const inputGroup = $(groupHtml);
                
                // Limpiamos el filtro
                $('.dataTables_filter label').contents().filter(function() {
                    return this.nodeType === 3; 
                }).remove();
                
                // Estilizamos el input y lo metemos al grupo
                input.addClass('form-control border-0 shadow-sm ps-2')
                 .attr('placeholder', 'Buscar producto...')
                 .appendTo(inputGroup);
                 
                // Metemos el grupo al contenedor de DataTables
                filterDiv.empty().append(inputGroup);

                // Inyectamos  botones en el espacio que creado en 'dom'
                $("div.custom-toolbar").html(tableHtml);
            },
            drawCallback: function() {
                $('.dataTables_info, .dataTables_paginate').addClass('small');
                $('.pagination').addClass('pagination-sm'); 
            }
        });
    },

    loadMetrics: async function() {
        try {
            const res = await fetch('/api/get-data?action=get_inventory_summary');
            const data = await res.json();
            
            // Llenamos las tarjetas
            document.getElementById('count_productos').innerText = data.total_items;
            document.getElementById('count_criticos').innerText = data.critical_count;
            document.getElementById('count_over').innerText = data.overstock_count;
            document.getElementById('count_stuck').innerText = data.stuck_count;
            
            const valorTotalEl = document.getElementById('count_valor_total');
            if (valorTotalEl) {
                valorTotalEl.innerText = `$${toCurrency(data.total_value)}`;
            }
        } catch (err) { console.error("Error cargando métricas:", err); }
    },

    setFilter: function(filterType, element) {
        this.currentFilter = filterType;

        // UI: Cambiar clase activa en tarjetas
        document.querySelectorAll('.filter-card').forEach(c => {
            c.classList.remove('bg-dark', 'text-white', 'shadow-lg');
        });
        element.classList.add('bg-dark', 'text-white', 'shadow-lg');

        // Recargar tabla con el nuevo filtro
        this.table.ajax.reload();
    },

    openAnalysis: async function(productId) {
        this.activeRow = productId;
        
        IxeaStages.openModal({
            templateId: 'tpl-stock-analysis',
            size: 'xl',
            scrollable: true,
            onOpen: async () => {
                try {
                    const res = await fetch(`/api/get-data?action=get_product_analysis&product_id=${productId}`);
                    const data = await res.json();

                    if (data.success) {
                        this.renderAnalysisContent(data);
                    }
                } catch (err) { 
                    console.error("Error en análisis:", err); 
                }
            }
        });
    },
    
    renderAnalysisContent: function(data) {
        const product = data.product;
    
        document.getElementById('stock-analysis-title').innerText = `${product.sku}: ${product.name}`;
        document.getElementById('stock-analysis-unit').innerText = product.unit;
        document.getElementById('stock-analysis-stock').innerText = parseFloat(product.stock).toFixed(2);
        document.getElementById('stock-analysis-min').innerText = parseFloat(product.min).toFixed(2);
        document.getElementById('stock-analysis-max').innerText = parseFloat(product.max).toFixed(2);
        
        document.getElementById('stock-analysis-minSuggested').value = product.ml_min;
        document.getElementById('stock-analysis-maxSuggested').value = product.ml_max;
    
        this.initChart(data.analysis);
        
        document.getElementById('stock-analysis-btnPrev').classList.remove('d-none');
        document.getElementById('stock-analysis-btnNext').classList.remove('d-none');
        this.updateNavButtons();
    },

    initChart: function(data) {
        const canvas = document.getElementById('stock-analysis-plot');
        if (!canvas) return;
    
        const ctx = canvas.getContext('2d');

        // Colores css    
        const style = getComputedStyle(document.documentElement);
        const purpleColor = style.getPropertyValue('--soft-purple').trim();
        const purpleLight = purpleColor + '1A';
        const darkColor = style.getPropertyValue('--black').trim();
    
        if (this.chart) {
            this.chart.destroy();
        }
    
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Ventas',
                    data: data.values,
                    borderColor: purpleColor,       
                    backgroundColor: purpleLight,   
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: purpleColor,
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: darkColor,
                        titleFont: { weight: 'bold' },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                        ticks: { font: { size: 11 } }
                    },
                    x: {
                        grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    },

    updateNavButtons: function() {
        // Lógica para navegar entre filas sin cerrar el modal
        const rows = this.table.rows().data().toArray();
        const currentIndex = rows.findIndex(r => r.product_id === this.activeRow);

        const btnPrev = document.getElementById('stock-analysis-btnPrev');
        const btnNext = document.getElementById('stock-analysis-btnNext');
        
        currentIndex <= 0 ? btnPrev.classList.add('d-none') : ''
        currentIndex >= rows.length -1 ? btnNext.classList.add('d-none') : '';

        btnPrev.onclick = () => this.navigateAnalysis(currentIndex - 1);
        btnNext.onclick = () => this.navigateAnalysis(currentIndex + 1);
    },

    navigateAnalysis: function(newIndex) {
        const rows = this.table.rows().data().toArray();
        if (rows[newIndex]) {
            const nextId = rows[newIndex].product_id;
            this.activeRow = nextId;
            // En lugar de cerrar y abrir modal, solo pedimos la data nueva
            fetch(`/api/get-data?action=get_product_analysis&product_id=${nextId}`)
                .then(res => res.json())
                .then(data => {
                    if(data.success) this.renderAnalysisContent(data);
                });
        }
    },
    
    openEditProduct: async function(productId = null) {
        this.activeRow = productId;
        const isEdit = productId !== null;
    
        IxeaStages.openModal({
            templateId: 'tpl-stock-edit',
            size: 'xl',
            scrollable: true,
            onOpen: async () => {
                // 1. Configurar visualmente según el modo
                const titleEl = document.getElementById('stock-edit-title');
                const btn = document.getElementById('stock-edit-btnSave');
                
                const [cats, brands, units] = await Promise.all([
                    IxeaData.get('categories'),
                    IxeaData.get('brands'),
                    IxeaData.get('units')
                ]);

                // 2. Llenar los SELECTS del template
                this.renderSelects({ cats, brands, units });
                
                if (isEdit) {
                    // Modo edición
                    const params = new URLSearchParams({
                      action: 'get_product',
                      product_id: productId,
                      lim_stock: true,
                      scope: 1,
                      categories: true
                    });
                    
                    titleEl.innerText = 'Cargando...';
                    const res = await fetch(`/api/get-data?${params.toString()}`);
                    const data = await res.json();
                    
                    if (data.success) {
                        list = data.list;
                        this.fillEditForm(list);
                        titleEl.innerText = `${list[0].sku}: ${list[0].name}`;
                    }
                    btn.setAttribute('onclick',`StockApp.confirmEditProduct(${productId})`);
                    
                } else {
                    // Modo crear
                    titleEl.innerText = 'Nuevo Registro';
                    document.getElementById('stock-edit-sku').readOnly = false;
                    document.getElementById('stock-edit-sku').classList.remove('bg-light');
                    
                    document.getElementById('stock-edit-btnPrev').classList.add('d-none');
                    document.getElementById('stock-edit-btnNext').classList.add('d-none');
                    
                    btn.setAttribute('onclick',`StockApp.confirmEditProduct()`);
                }
            }
        });
    },
    
    renderSelects: function({ cats, brands, units }) {
        const fill = (id, list, val, text) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = '<option value="">Seleccionar...</option>' + 
                list.map(i => `<option value="${i[val]}">${i[text]}</option>`).join('');
        };

        fill('stock-edit-category', cats, 'category_id', 'name');
        fill('stock-edit-brand', brands, 'brand_id', 'name');
        fill('stock-edit-unit', units, 'unit_code', 'unit_code'); // Ojo con los nombres de columna
    },
    
    fillEditForm: function(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        
        const product = dataArray[0];
    
        document.getElementById('stock-edit-sku').value = product.sku ?? '';
        document.getElementById('stock-edit-name').value = product.name ?? '';
        document.getElementById('stock-edit-unit').value = product.main_unit ?? '';
        document.getElementById('stock-edit-tax').checked = product.is_taxable == 1 ?? false;
        document.getElementById('stock-edit-category').value = product.category_id ?? '';
        document.getElementById('stock-edit-brand').value = product.brand_id ?? '';
        document.getElementById('stock-edit-status').value = product.status;
    
        // 2. Stock y Niveles (también generales)
        document.getElementById('stock-edit-current').innerText = parseFloat(product.stock).toFixed(4) ?? 0;
        document.getElementById('stock-edit-min').value = parseFloat(product.min).toFixed(4) ?? '';
        document.getElementById('stock-edit-max').value = parseFloat(product.max).toFixed(4) ?? '';
    
        // 3. Limpiar y llenar contenedores de Unidades (Ventas y Compras)
        const salesCont = document.getElementById('stock-edit-sales-container');
        const purchCont = document.getElementById('stock-edit-purchases-container');
        
        salesCont.innerHTML = '';
        purchCont.innerHTML = '';
    
        dataArray.forEach(row => {
            if (row.is_sale == 1) {
                this.addUnitRow('sale', row);
            }
            if (row.is_purchase == 1) {
                this.addUnitRow('purchase', row);
            }
        });
    },
    
    addUnitRow: async function(type, data = null) {
        const containerId = type === 'sale' ? 'stock-edit-sales-container' : 'stock-edit-purchases-container';
        const container = document.getElementById(containerId);
        
        // Encabezado
        if (container.children.length === 0) {
            const header = document.createElement('div');
            header.className = 'row g-2 mb-1 small fw-bold text-muted px-2';
            header.innerHTML = `
                <div class="col-3 text-center">Unidad</div>
                <div class="col-2 text-center">Factor</div>
                <div class="col-3 text-center">${type === 'sale' ? 'Precio' : 'Costo'}</div>
                ${type === 'purchase' ? '<div class="col-3 text-center">Proveedor</div>' : ''}
                <div class="col-1"></div>
            `;
            container.appendChild(header);
        }
    
        const div = document.createElement('div');
        div.className = 'row g-2 align-items-center border-bottom pb-2 mb-2 unit-row';
        
        const rowId = Date.now() + Math.floor(Math.random() * 1000);
        const supplierCol = type === 'purchase' ? `
            <div class="col-3 position-relative">
                <input type="text" class="form-control form-control-sm supplier-search-input border-2" 
                       placeholder="Buscar proveedor..." autocomplete="off"
                       value="${data ? (data.rfc || '') : ''}"
                       oninput="StockApp.searchSuppliers(this, ${rowId})">
                <input type="hidden" class="unit-supplier-id" value="${data ? data.supplier_id : ''}">
                <div id="results-${rowId}" class="list-group position-absolute w-100 z-3 shadow-sm d-none small"></div>
            </div>` : '';
        
        // El HTML de la fila con los valores cargados si existen
        const units = await IxeaData.get('units');
        div.innerHTML = `
            <div class="col-3">
                <select class="form-select form-select-sm unit-select">
                    ${units.map(u => `<option value="${u.unit_code}" ${data && data.unit == u.unit_code ? 'selected' : ''}>${u.unit_code}</option>`).join('')}
                </select>
            </div>
            <div class="col-2">
                <input type="number" class="form-control form-control-sm text-center unit-factor" 
                       placeholder="Factor" value="${data ? data.factor : 1}">
            </div>
            <div class="col-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control unit-value" 
                           placeholder="${type === 'sale' ? 'Precio' : 'Costo'}" 
                           value="${data ? (type === 'sale' ? data.price : data.cost) : 0}">
                </div>
            </div>
            ${supplierCol}
            <div class="col-1">
                <button class="btn btn-sm text-danger" onclick="this.closest('.unit-row').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
    
        container.appendChild(div);
    },
    
    searchSuppliers: async function(input, rowId) {
        // Limpiamos bordes
        input.classList.remove('border-sfgreen');
        input.classList.remove('border-sfred');
        // Agregamos borde alerta
        input.classList.add('border-sfred');
        
        const query = input.value.trim();
        const resultsDiv = document.getElementById(`results-${rowId}`);
        
        if (query.length < 2) {
            resultsDiv.classList.add('d-none');
            return;
        }
    
        try {
            const res = await fetch(`/api/get-data?action=get_suppliers&query=${encodeURIComponent(query)}`);
            const data = await res.json();
            
            if (data.success && data.suppliers.length > 0) {
                resultsDiv.innerHTML = data.suppliers.map(s => `
                    <button type="button" class="list-group-item list-group-item-action py-1 px-2" 
                            onclick="StockApp.selectSupplier(${rowId}, '${s.supplier_id}', '${s.rfc}')">
                        <div class="fw-bold">${s.company_name}</div>
                        <div class="small text-muted">${s.rfc}</div>
                    </button>
                `).join('');
                resultsDiv.classList.remove('d-none');
            } else {
                resultsDiv.classList.add('d-none');
            }
        } catch (err) { 
            console.error(err); 
        }
    },
    
    selectSupplier: function(rowId, id, rfc) {
        // Buscamos el div de resultados que disparó el evento
        const resultsDiv = document.getElementById(`results-${rowId}`);
        if (!resultsDiv) return;
    
        // Buscamos el contenedor de la unidad (la fila completa .unit-row)
        const row = resultsDiv.closest('.unit-row');
        
        if (row) {
            // 3. Buscamos los inputs dentro de esa fila específica
            const inputSearch = row.querySelector('.supplier-search-input');
            const inputId = row.querySelector('.unit-supplier-id');
            
            if (inputSearch) {
                inputSearch.value = rfc;
                inputSearch.classList.add('border-sfgreen');
            }
            if (inputId) inputId.value = id;
            
        } else {
            console.error("No se encontró el contenedor .unit-row");
        }
    
        // 4. Escondemos los resultados
        resultsDiv.classList.add('d-none');
    },
    
    confirmEditProduct: async function(productId = null) {
        const btn = document.getElementById('stock-edit-btnSave');
        if (!IxeaBouncer.lockBtn(btn)) return;
        
        const isEdit = productId !== null;
        const action = isEdit ? 'edit_product' : 'create_product';
        
        const body = {
            action: action,
            product_id: productId,
            sku: document.getElementById('stock-edit-sku').value.trim(),
            name: document.getElementById('stock-edit-name').value.trim(),
            unit: document.getElementById('stock-edit-unit').value,
            is_taxable: document.getElementById('stock-edit-tax').checked ? 1 : 0,
            category_id: document.getElementById('stock-edit-category').value,
            brand_id: document.getElementById('stock-edit-brand').value,
            status: document.getElementById('stock-edit-status').value,
            min: document.getElementById('stock-edit-min').value,
            max: document.getElementById('stock-edit-max').value,
            units: [] // Aquí irán las filas dinámicas
        };
    
        // 2. Recolección de Unidades (Venta y Compra)
        document.querySelectorAll('.unit-row').forEach(row => {
            const supplierInput = row.querySelector('.unit-supplier-id'); // Buscamos el hidden del ID
            
            body.units.push({
                unit_code: row.querySelector('.unit-select').value,
                factor: row.querySelector('.unit-factor').value,
                value: row.querySelector('.unit-value').value,
                type: row.closest('#stock-edit-sales-container') ? 'sale' : 'purchase',
                supplier_id: supplierInput ? supplierInput.value : null 
            });
        });
    
        // 3. Validaciones rápidas
        if (!body.sku || !body.name) return Swal.fire('Atención', 'SKU y Nombre son obligatorios.', 'warning');
        Swal.fire({
            title: isEdit ? 'Actualizando...' : 'Creando...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        try {
            const res = await fetch('/modules/inventory/stock-handler', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
    
            const data = await res.json();
    
            if (data.success) {
                Swal.fire('¡Éxito!', data.message, 'success');
                IxeaStages.closeModal();
                this.table.ajax.reload(null, false);
                this.loadMetrics();
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            Swal.fire('Error', err.message || 'Error en el servidor.', 'error');
            console.error('Error: ', err.message);
        } finally {
            IxeaBouncer.releaseBtn(btn);
        }
    },
    
    auditProduct: async function(productId) {
    this.activeRow = productId;
        
        IxeaStages.openModal({
            templateId: 'tpl-stock-audit',
            size: 'xl',
            scrollable: true,
            onOpen: async () => {
                try {
                    const res = await fetch(`/api/get-data?action=get_product_auditory&product_id=${productId}`);
                    const data = await res.json();
                    
                    if (data.success) {
                        this.renderAuditoryContent(data);
                        
                        const btn = document.getElementById('stock-audit-btnAdjusment');
                        if (btn) btn.onclick = () => this.promptAdjustment(productId, data.unit, data.stock);
                    }
                } catch (err) { 
                    console.error("Error en análisis:", err); 
                }
            }
        });
    },
    
    renderAuditoryContent: function(data) {
        const titleEl = document.getElementById('audit-product-name');
        const tbody = document.getElementById('audit-table-body');
        
        const sku = data.sku;
        const name = data.name;
        const unit = data.unit;
        const movements = data.movements;
        
        if (titleEl) titleEl.innerText = `${sku}: ${name} [${unit}]`;
        if (!tbody) return;
    
        tbody.innerHTML = '';
    
        if (movements.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No hay movimientos registrados.</td></tr>`;
            return;
        }
    
        movements.forEach(mov => {
            const isIn = mov.type === 'in';
            const badgeClass = isIn ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
            const icon = isIn ? 'bi-arrow-down-left' : 'bi-arrow-up-right';
            const date = new Date(mov.created_at).toLocaleString('es-MX', { 
                day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' 
            });
    
            const row = `
                <tr>
                    <td class="ps-4 small text-muted">${date}</td>
                    <td>
                        <div class="fw-medium">${mov.notes || '<span class="opacity-50">Sin referencia</span>'}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge ${badgeClass} rounded-pill border">
                            <i class="bi ${icon} me-1"></i>${mov.type.toUpperCase()}
                        </span>
                    </td>
                    <td class="text-end fw-bold ${isIn ? 'text-success' : 'text-danger'}">
                        ${isIn ? '+' : '-'}${parseFloat(mov.quantity).toFixed(2)}
                    </td>
                    <td class="text-end pe-4 fw-bold text-dark">
                        ${parseFloat(mov.balance).toFixed(2)}
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    },
    
    promptAdjustment: function(productId, unit, stock) {
    
        Swal.fire({
            title: `<span class="text-${this.appColor}">Ajuste de Inventario</span>`,
            html: `
                <div class="text-center small">
                    <p class="mb-2">Estás a punto de registrar un movimiento de ajuste.</p>
                    <p class="mb-4">Esto afectará el balance contable y de stock.</p>
                    <label class="fw-bold text-center">Cantidad contabilizada [${unit}]:</label>
                </div>`,
            input: 'number',
            inputAttributes: { min: 0, step: 'any' },
            customClass: {
                input: 'text-end fw-bold mx-5 px-3'
            },
            showCancelButton: true,
            confirmButtonText: 'Aplicar Ajuste',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: this.varColor,
            footer: `<span class="text-muted small">Stock actual en sistema: <b>${stock} ${unit}</b></span>`,
            returnFocus: false,
            preConfirm: (value) => {
                if (!value || value < 0) Swal.showValidationMessage('Ingresa una cantidad válida');
                if (parseFloat(value) - stock === 0) Swal.showValidationMessage('No hay cambio en stock');
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.executeAdjustment(productId, unit, parseFloat(result.value));
            }
        });
    },
    
    executeAdjustment: async function(productId, unit, newStock) {
        try {
            const body = {
                action: 'record_adjustment',
                product_id: productId,
                stock: newStock
            };

            const res = await fetch('/modules/inventory/stock-handler', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            
            const data = await res.json();
            if (data.success) {
                Swal.fire('¡Actualizado!', `Se realizó el ajuste a ${newStock} ${unit}.`, 'success');
                this.auditProduct(productId); // Refrescar la tabla de movimientos
            }
        } catch (e) {
            Swal.fire('Error', 'No se pudo procesar el ajuste', 'error');
        }
    }
};