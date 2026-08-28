/**
 * /modules/reports/reports.js
 * Gestión de Inteligencia de Negocios e Informes IXEA EROS
 */
window.ReportsApp = {
    isInitialized: false,
    currentFilter: 'day',
    currentTab: 'ventas',
    charts: {}, // Almacén dinámico para instancias de Chart.js
    reportData: null,
    appColor: 'sfcyan',

    init: async function() {
        if (this.isInitialized) return;
        
        this.renderAppActions();
        await this.loadReport();
        
        this.isInitialized = true;
    },

    renderAppActions: function() {
        const menuFilters = document.getElementById('menu-app-filters');
        if (!menuFilters) return;

        const filterNames = {
            'day': 'Hoy',
            'week': 'Últimos 7 días',
            'month': 'Este Mes',
            'last_month': 'Mes Anterior',
            'year': 'Este Año',
            'last_Year': 'Año Anterior'
        };
        const activeLabel = filterNames[this.currentFilter] || 'Hoy';

        menuFilters.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer text-${this.appColor} fw-bold" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar3 me-2"></i>Periodo: ${activeLabel}
                    </div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    ${Object.entries(filterNames).map(([key, label]) => `
                        <li><a class="dropdown-item small d-flex justify-content-between align-items-center" onclick="ReportsApp.updatePeriod('${key}')">
                            ${label} ${this.currentFilter === key ? '<i class="bi bi-check text-sfcyan fw-bold"></i>' : ''}
                        </a></li>
                    `).join('')}
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small" onclick="ReportsApp.loadReport()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                    </a></li>
                </ul>
            </div>
        `;
    },

    updatePeriod: function(newPeriod) {
        this.currentFilter = newPeriod;
        this.renderAppActions();
        this.loadReport();
    },

    switchTab: function(tabName) {
        this.currentTab = tabName;
        
        // Cambio estético de clases activas en los tags superiores
        document.querySelectorAll('#reports-tabs .nav-link').forEach(link => link.classList.remove('active'));
        document.getElementById(`tab-${tabName}`).classList.add('active');
        
        // Destruir gráficos anteriores para liberar memoria
        Object.keys(this.charts).forEach(key => {
            if (this.charts[key]) this.charts[key].destroy();
        });
        this.charts = {};

        this.loadReport();
    },

    loadReport: async function() {
        const contentContainer = document.getElementById('reports-dynamic-content');
        if (!contentContainer) return;

        // Mostrar indicador de carga estético
        contentContainer.innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center py-5 opacity-75">
                <div class="spinner-border text-sfcyan mb-2" role="status"></div>
                <span class="small text-muted">Procesando cubos de información...</span>
            </div>
        `;

        try {
            const url = `/api/get-data?action=get_report_data&tab=${this.currentTab}&period=${this.currentFilter}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP Error Status: ${res.status}`);
            
            const data = await res.json();
            if (!data.success) throw new Error(data.message || "Error en estructuración de datos.");

            this.reportData = data.payload;
            this.renderTabContent();

        } catch (err) {
            console.error("Fallo crítico en reportería:", err);
            contentContainer.innerHTML = `
                <div class="p-5 text-center text-danger small">
                    <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                    Error al compilar informe: ${err.message}
                </div>
            `;
        }
    },

    renderTabContent: function() {
        const container = document.getElementById('reports-dynamic-content');
        const templateId = `tpl-report-${this.currentTab}`;
        const template = document.getElementById(templateId);

        if (!template) return;

        // Clonamos el template HTML correspondiente
        container.innerHTML = '';
        container.appendChild(template.content.cloneNode(true));

        // Bifurcamos la renderización según la pestaña activa
        if (this.currentTab === 'sales') {
            this.processSalesSection();
        } else if (this.currentTab === 'expenses') {
            this.processExpensesSection();
        } else if (this.currentTab === 'balance') {
            this.processBalanceSection();
        }
    },

    processSalesSection: function() {
        // Adaptado a la respuesta tradicional payload
        const d = this.reportData.payload || this.reportData; 

        // Inyección de KPIs numéricos básicos (IDs actualizados)
        document.getElementById('report-sales-total').innerText = `$ ${toCurrency(d.kpis?.total_ventas || 0)}`;
        document.getElementById('report-sales-avg').innerText = `$ ${toCurrency(d.kpis?.ticket_promedio || 0)}`;
        document.getElementById('report-sales-profit').innerText = `$ ${toCurrency(d.kpis?.utilidad_bruta || 0)}`;
        document.getElementById('report-sales-operations').innerText = parseInt(d.kpis?.transacciones || 0);

        // Control de tendencias porcentuales
        const trendVal = d.kpis?.trend_porcentaje || 0;
        const trendContainer = document.getElementById('report-sales-trenContainer');
        const trendValEl = document.getElementById('report-sales-tren');
        
        if (trendContainer && trendValEl) {
            trendValEl.innerText = `${trendVal > 0 ? '+' : ''}${trendVal}%`;
            if (trendVal >= 0) {
                trendContainer.className = "text-xs mt-2 text-sfgreen";
                trendContainer.querySelector('i').className = "bi bi-graph-up";
            } else {
                trendContainer.className = "text-xs mt-2 text-sfred";
                trendContainer.querySelector('i').className = "bi bi-graph-down";
            }
        }

        // --- DESGLOSE DE MÉTODOS DE PAGO ---
        const paymentList = document.getElementById('report-sales-paymentMethods');
        if (paymentList && d.formas_pago) {
            paymentList.innerHTML = Object.values(d.formas_pago).map(pago => `
                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                    <div>
                        <i class="bi bi-credit-card-2-back text-sfcyan me-2"></i>
                        <span class="fw-bold text-secondary">${pago.name}</span>
                    </div>
                    <span class="fw-bold text-dark">$ ${toCurrency(pago.monto)}</span>
                </div>
            `).join('');
        }

        // --- TABLA TOP 10 PRODUCTOS ---
        const tableBody = document.getElementById('report-sales-tbProducts');
        if (tableBody && d.top_productos) {
            if (d.top_productos.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Sin movimientos de inventario</td></tr>`;
            } else {
                tableBody.innerHTML = d.top_productos.map(p => `
                    <tr>
                        <td class="ps-3 fw-bold text-secondary">${p.name}</td>
                        <td class="text-center">${p.unidades}</td>
                        <td class="text-end">$ ${toCurrency(p.ventas_totales)}</td>
                        <td class="text-end pe-3 fw-bold text-sfgreen">$ ${toCurrency(p.utilidad)}</td>
                    </tr>
                `).join('');
            }
        }

        // --- CONSTRUCCIÓN DE GRÁFICAS (Chart.js) ---
        const ctxTendencia = document.getElementById('report-sales-canvasTrend')?.getContext('2d');
        if (ctxTendencia && d.chart_tendencia) {
            this.charts['salesTrend'] = new Chart(ctxTendencia, {
                type: 'line',
                data: {
                    labels: d.chart_tendencia.labels,
                    datasets: [{
                        label: 'Ventas ($)',
                        data: d.chart_tendencia.valores,
                        borderColor: '#00b4d8',
                        backgroundColor: 'rgba(0, 180, 216, 0.08)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        const ctxCategorias = document.getElementById('report-sales-canvasCategories')?.getContext('2d');
        if (ctxCategorias && d.chart_categorias) {
            this.charts['salesCategories'] = new Chart(ctxCategorias, {
                type: 'doughnut',
                data: {
                    labels: d.chart_categorias.labels,
                    datasets: [{
                        data: d.chart_categorias.valores,
                        backgroundColor: ['#00b4d8', '#0077b6', '#90e0ef', '#03045e', '#caf0f8']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    },

    processExpensesSection: function() {
        const d = this.reportData.payload || this.reportData;
        
        // IDs actualizados a report-expenses-...
        document.getElementById('report-expenses-total').innerText = `$ ${toCurrency(d.total_gastos || 0)}`;
        document.getElementById('report-expenses-categories').innerText = d.categoria_critica || 'Ninguna';

        const tableBody = document.getElementById('report-expenses-tbExpenses');
        if (tableBody && d.lista_gastos) {
            tableBody.innerHTML = d.lista_gastos.map(g => `
                <tr>
                    <td class="ps-3 fw-bold text-secondary">${g.categoria}</td>
                    <td class="text-end pe-3 text-danger fw-bold">$ ${toCurrency(g.total)}</td>
                </tr>
            `).join('');
        }

        const ctxGastos = document.getElementById('report-expenses-canvasCategories')?.getContext('2d');
        if (ctxGastos && d.chart_gastos) {
            this.charts['expensesCategories'] = new Chart(ctxGastos, {
                type: 'bar',
                data: {
                    labels: d.chart_gastos.labels,
                    datasets: [{
                        label: 'Egresos por Categoría ($)',
                        data: d.chart_gastos.valores,
                        backgroundColor: '#ef476f'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    }
};