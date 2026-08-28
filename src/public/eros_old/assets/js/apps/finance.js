/**
 * /modules/finance/finance.php
 * Gestión de Contabilidad y Finanzas
 */
window.FinanceApp = {
    isInitialized: false,
    currentFilter: 'month',
    accounts: [],
    mappingAccounts: {
        'cash': 'Caja',
        'main_cash': 'C. Efectivo',
        'bank': 'C. Bancaria',
        'credit_card': 'T. Crédito',
        'card': 'Terminal'
    },

    init: async function() {
        if (this.isInitialized) return;
        this.isInitialized = true;
        
        this.renderAppActions();
        await this.loadBalance();
    },
    
    renderAppActions: function() {
        const menuActions = document.getElementById('menu-app-actions');
        if (!menuActions) return;
        menuActions.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">Operaciones</div>
                <ul class="dropdown-menu shadow border-0 mt-2">
                    <li><a class="dropdown-item small" onclick="FinanceApp.showGlobalLedger()">
                        <i class="bi bi-journals me-2 text-sfcyan"></i> Libro Mayor
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small text-sfred" onclick="FinanceApp.validateMonthlyClosing()">
                        <i class="bi bi-lock-fill me-2"></i> Cierre de Mes
                    </a></li>
                </ul>
                
            </div>
        `;
    },
    
    loadBalance: async function(){
        const period = this.currentFilter || 'month';
        
        try {
            const res = await fetch(`/api/get-data?action=get_finance&period=${period}`);
            const data = await res.json();
            console.log(data);
            if (data.success) {
                this.balances = data.balances;
                this.fiscal = data.fiscal;
                this.updateBalances();
                this.updateFiscal();
            } else {
                throw new Error(data.message || "Error desconocido");
            }

        } catch (err) {
            console.error("Error cargando Dashboard de Pagos:", err);
        }
    },

    updateBalances: function() {
        const b = this.balances;
        if (!b) return;

        // Indicadores estándar
        document.getElementById('fin-net-liquidity').textContent = `$ ${toCurrency(b.liquidity)}`;
        document.getElementById('fin-net-receivable').textContent = `$ ${toCurrency(b.receivable)}`;
        document.getElementById('fin-net-payable').textContent = `$ ${toCurrency(b.payable)}`;
        document.getElementById('fin-net-inventory').textContent = `$ ${toCurrency(b.inventory)}`;
        document.getElementById('fin-net-worth').textContent = `$ ${toCurrency(b.worth)}`;
        document.getElementById('fin-net-dividend').textContent = `$ ${toCurrency(b.dividend)}`;
        
        // Bloque: Resultado del Ejercicio
        document.getElementById('fin-gen-sales').textContent = `$ ${toCurrency(b.income)}`;
        document.getElementById('fin-gen-purchases').textContent = `$ ${toCurrency(b.payments)}`;
        document.getElementById('fin-gen-expenses').textContent = `$ ${toCurrency(b.expenses)}`;
        
        const profitEl = document.getElementById('fin-gen-profit');
        profitEl.textContent = `$ ${toCurrency(b.profit)}`;
        profitEl.classList.remove('text-sfred', 'text-sfgreen');
        profitEl.classList.add(b.profit > 0 ? 'text-sfgreen' : 'text-sfred');
    },

    updateFiscal: function() {
        const f = this.fiscal;
        if (!f) return;
    
        // Bloque: Resultado del Ejercicio
        document.getElementById('fin-tax-sales').textContent = `$ ${toCurrency(f.income + f.daily)}`;
        document.getElementById('fin-tax-purchases').textContent = `$ ${toCurrency(f.payments)}`;
        document.getElementById('fin-tax-expenses').textContent = `$ ${toCurrency(f.expenses)}`;
        
        const profitEl = document.getElementById('fin-tax-profit');
        profitEl.textContent = `$ ${toCurrency(f.profit)}`;
        profitEl.classList.remove('text-sfred', 'text-sfgreen');
        profitEl.classList.add(f.profit > 0 ? 'text-sfgreen' : 'text-sfred');
    
        // Bloque: ISR
        const isrEl = document.getElementById('fin-tax-isr');
        isrEl.textContent = `$ ${toCurrency(f.isr)}`;
        isrEl.classList.remove('text-sfred', 'text-sfgreen');
        isrEl.classList.add(f.isr > 0 ? 'text-sfred' : 'text-sfgreen');
    
        // Bloque: Resumen de IVA
        document.getElementById('fin-tax-ivain').textContent = `$ ${toCurrency(f.iva_in)}`;
        document.getElementById('fin-tax-ivaout').textContent = `$ ${toCurrency(f.iva_out)}`;
        
        const ivaNetEl = document.getElementById('fin-tax-ivanet');
        ivaNetEl.textContent = `$ ${toCurrency(f.iva_net)}`;
        ivaNetEl.classList.remove('text-sfred', 'text-sfgreen');
        ivaNetEl.classList.add(f.iva_net > 0 ? 'text-sfred' : 'text-sfgreen');
    },
    
    // Estructura ideal para Chart.js
    initEvolutionChart: function(data) {
        const ctx = document.getElementById('finance-chart-flow').getContext('2d');
        new Chart(ctx, {
            type: 'bar', // Combinado: barras para flujo, línea para valor
            data: {
                labels: data.months, // ['Ene', 'Feb', 'Mar'...]
                datasets: [
                    {
                        label: 'Ingresos',
                        data: data.income,
                        backgroundColor: '#28a745' // sfgreen
                    },
                    {
                        label: 'Egresos',
                        data: data.expenses,
                        backgroundColor: '#dc3545' // sfred
                    },
                    {
                        label: 'Valor Negocio',
                        data: data.worth,
                        type: 'line',
                        borderColor: '#00bcd4', // sfcyan
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    },
    
    showGlobalLedger: async function() {
        const activeStage = IxeaStages.activeStage;
        if (!activeStage || activeStage !== 'stage-finance') return;
        const mainCanvas = document.getElementById(activeStage);

        const overlay = document.createElement('div');
        overlay.id = "ledger-overlay";
        overlay.className = "position-absolute top-0 start-0 w-100 h-100 bg-white z-3 animate__animated animate__fadeInUp overflow-auto";
        
        overlay.innerHTML = `
            <div class="container-fluid py-3">
                <div class="d-flex justify-content-between align-items-center mb-3 px-3">
                    <div>
                        <h2 class="fw-bold mb-0 text-sfcyan"><i class="bi bi-arrow-down-circle me-2"></i> Libro Mayor</h2>
                    </div>
                    <button class="btn btn-light rounded-pill border shadow-sm" onclick="document.getElementById('ledger-overlay').remove()">
                        <i class="bi bi-arrow-left me-1"></i> Volver a Finanzas
                    </button>
                </div>
    
                <div class="card border-0 shadow-sm rounded-4 mx-3 mb-3 bg-light">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-2 px-3">
                                <input type="date" id="filter-date" class="form-control form-control-sm border-0 shadow-none" onchange="FinanceApp.loadUnifiedLedger()">
                            </div>
                            <div class="col-md-3 px-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search"></i></span>
                                    <input type="text" id="filter-search" class="form-control border-0 shadow-none" placeholder="Buscar concepto o referencia..." onkeyup="FinanceApp.loadUnifiedLedger()">
                                </div>
                            </div>
                            <div class="col-md-3 px-3">
                                <select id="filter-account" class="form-select form-select-sm border-0 shadow-none" onchange="FinanceApp.loadUnifiedLedger()">
                                    <option value="all">Todas las cuentas</option>
                                    ${Object.entries(this.mappingAccounts).map(([key, name]) => `<option value="${key}">${name}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="filter-type" class="form-select form-select-sm border-0 shadow-none" onchange="FinanceApp.loadUnifiedLedger()">
                                    <option value="all">Tipo (In/Out)</option>
                                    <option value="in">Entradas</option>
                                    <option value="out">Salidas</option>
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
                                    <th>CUENTA</th>
                                    <th class="text-center">FISCAL</th>
                                    <th class="text-end">SALIDA</th>
                                    <th class="text-end pe-4">ENTRADA</th>
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

    loadUnifiedLedger: async function() {
        const tbody = document.getElementById('unified-ledger-body');
        const params = new URLSearchParams({
            action: 'get_unified_ledger',
            account: document.getElementById('filter-account')?.value ?? 'all',
            ref_type: document.getElementById('filter-ref-type')?.value ?? 'all',
            type: document.getElementById('filter-type')?.value ?? 'all',
            date: document.getElementById('filter-date')?.value ?? '',
            search: document.getElementById('filter-search')?.value ?? ''
        });
        
        try {
            const res = await fetch(`/api/get-data?${params.toString()}`);
            const data = await res.json();
            
            if (!data || !data.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted">No hay movimientos registrados</td></tr>`;
                return;
            }

            tbody.innerHTML = data.map(m => {
                const isOut = m.type === 'out';
                // Separamos fecha y hora si vienen juntos en created_at del PHP
                const [date, time] = m.created_at.split(' ');
                
                return `
                    <tr>
                        <td class="ps-4 small">
                            <div class="fw-bold">${date}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">${time || ''}</div>
                        </td>
                        <td>
                            <div class="fw-bold">${m.concept}</div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">
                                    ${(m.reference_type || 'MOVIMIENTO').toUpperCase()}
                                </span>
                                ${m.detail_label ? `
                                    <span class="text-sfcyan fw-bold" style="font-size: 0.65rem;">
                                        <i class="bi bi-dot"></i> ${m.detail_label}
                                    </span>` : ''}
                            </div>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-white text-dark border px-3">
                                ${(this.mappingAccounts[m.account] || m.account).toUpperCase()}
                            </span>
                        </td>
                        <td class="text-center">
                            <input class="form-check-input" type="checkbox" value="" ${m.is_taxable ? 'checked' : ''} disabled>
                        </td>
                        <td class="text-end fw-bold ${isOut ? 'text-sfred' : 'text-muted'}">${isOut ? `-$${toCurrency(m.amount)}` : '-'}</td>
                        <td class="text-end fw-bold text-sfgreen">${!isOut ? `$${toCurrency(m.amount)}` : '-'}</td>
                        <td class="text-center pe-4">
                            ${m.reference_type === 'expenses' || m.reference_type === 'global_accounts' ? `
                                <button class="btn btn-sm btn-outline-danger border-0" onclick="FinanceApp.cancelMovement('${m.reference_type}', ${m.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : '<i class="bi bi-shield-lock text-muted" title="Solo lectura"></i>'}
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (err) {
            console.error("Error ledger:", err);
            document.getElementById('unified-ledger-body').innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger">Error al cargar datos</td></tr>`;
        }
    },
    
    validateMonthlyClosing: async function(){
        IxeaBouncer.showLoading('Validando Cierre Financiero...'); // Bloqueador de pantalla mientras procesa
        
        try {
            const res = await fetch(`/api/get-data?action=validate_monthly_closing`);
            const data = await res.json();
            
            if (data.success) {
                if (data.period){
                    this.confirmMonthlyClosing(data.period);
                } else {
                    console.error(data)
                    Swal.fire('Cierre no disponible en este priodo.', '', 'error');
                }

            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            Swal.fire('Error en validación de cierre', err.message, 'error');
        } finally {
            IxeaBouncer.hideLoading();
        }
    },
    
    confirmMonthlyClosing: async function(period) {
        // Verificar si el periodo actual ya está cerrado o si hay turnos abiertos
        const { value: confirm } = await Swal.fire({
            title: `¿Ejecutar Cierre de ${period.month}?`,
            html: `
                <div class="text-start small">
                    <p class="mb-2">Esta acción realizará lo siguiente:</p>
                    <ul>
                        <li>Calcula y registra los indicadores financieros de este mes.</li>
                        <li>Genera el reporte fiscal para contabilidad.</li>
                        <li>Registra los saldos finales para el siguiente periodo.</li>
                    </ul>
                    <b class="text-danger">Nota: Una vez cerrado, no deberás registrar ventas o gastos dentro del mes de ${period.month}.</b>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, Cerrar Periodo',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
        });
    
        if (confirm) {
            this.executeClosing(period);
        }
    },
    
    executeClosing: async function(period) {
        IxeaBouncer.showLoading('Generando Cierre Financiero...'); // Bloqueador de pantalla mientras procesa
        
        try {
            const res = await fetch('/modules/finance/finance-handler?action=execute_monthly_closing', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    'p_key': period.p_key,
                    'end_date': period.end_date,
                    'month': period.month
                })
            });
    
            const data = await res.json();
            console.log(data.steps);
            if (data.success) {
                Swal.fire({
                    title: '¡Periodo Cerrado!',
                    text: 'El cierre financiero ha sido guardado.',
                    icon: 'success',
                    showConfirmButton: true
                });
                
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error en Cierre', err.message, 'error');
        } finally {
            IxeaBouncer.hideLoading();
        }
    }
};