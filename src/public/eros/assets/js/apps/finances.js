window.FinanceApp = {
    jsName: 'finance',
    isInitialized: false,

    // Estado interno
    summary: {},
    transactions: [],
    paymentMethods: [],
    selectedShift: 'today',

    // Configuración de Streaming / Sincronización en segundo plano
    toStream: [
        ['get-finance-report', () => ({
            shift_id: FinanceApp.selectedShift
        })]
    ],

    init: async function () {
        if (this.isInitialized) return;
        this.isInitialized = true;

        await this.loadReport({ forceSync: true });
    },

    destroy: function () {
        this.summary = {};
        this.transactions = [];
        this.paymentMethods = [];
        this.isInitialized = false;
    },

    onTick: {
        stream: function () {
            // Sincronización automática periódica gestionada por el motor IxeaData
            const latestHash = (IxeaData.timestamps && IxeaData.timestamps['finance'])
                ? IxeaData.timestamps['finance']['get-finance-report']
                : null;

            if (latestHash !== this.reportHash) {
                this.reportHash = latestHash;
                const cachedData = IxeaData.cache?.['finance']?.['get-finance-report'];
                if (cachedData) {
                    this.processReportData(cachedData);
                }
            }
        }
    },

    // ==========================================
    // CARGA DE DATOS Y PETICIONES API
    // ==========================================
    loadReport: async function ({ cacheOnly = false, forceSync = false } = {}) {
        const selectEl = document.getElementById('finance-filter-shift');
        if (selectEl) {
            this.selectedShift = selectEl.value;
        }

        try {
            IxeaComponents.showLoading({ text: 'Calculando reporte financiero...' });

            const res = await IxeaData.get("finance", "get-finance-report", {
                shift_id: this.selectedShift,
                cacheOnly,
                forceSync
            });

            IxeaComponents.hideLoading();

            if (res && res.success && res.data) {
                this.reportHash = IxeaData.timestamps?.['finance']?.['get-finance-report'] || null;
                this.processReportData(res.data);
            } else {
                throw new Error(res?.message || 'No se pudo obtener el reporte financiero.');
            }
        } catch (err) {
            IxeaComponents.hideLoading();
            console.error("[Finance] Error al cargar reporte:", err);
            IxeaComponents.showAlert({
                title: 'Error',
                text: err.message || 'Error de conexión al obtener finanzas.',
                icon: 'error'
            });
        }
    },

    processReportData: function (data) {
        this.summary = data.summary || { total_sales: 0, total_tips: 0, total_cash: 0, total_digital: 0 };
        this.transactions = data.transactions || [];
        this.paymentMethods = data.payment_methods || [];

        this.updateMetrics();
        this.renderPaymentMethodsBreakdown();
        this.renderTransactionsTable();
    },

    // ==========================================
    // ACTUALIZACIÓN DE KPIS Y MÉTRICAS
    // ==========================================
    updateMetrics: function () {
        const salesEl = document.getElementById('finance-kpi-sales');
        const tipsEl = document.getElementById('finance-kpi-tips');
        const cashEl = document.getElementById('finance-kpi-cash');
        const digitalEl = document.getElementById('finance-kpi-digital');

        if (salesEl) salesEl.innerText = `$${toCurrency(this.summary.total_sales || 0)}`;
        if (tipsEl) tipsEl.innerText = `$${toCurrency(this.summary.total_tips || 0)}`;
        if (cashEl) cashEl.innerText = `$${toCurrency(this.summary.total_cash || 0)}`;
        if (digitalEl) digitalEl.innerText = `$${toCurrency(this.summary.total_digital || 0)}`;
    },

    // ==========================================
    // RENDERIZADO DE DESGLOSE Y TABLAS
    // ==========================================
    renderPaymentMethodsBreakdown: function () {
        const container = document.getElementById('finance-payment-methods-breakdown');
        if (!container) return;

        if (!this.paymentMethods || this.paymentMethods.length === 0) {
            container.innerHTML = `<div class="text-center py-8 text-slate-500 text-xs">No hay registros de métodos de pago.</div>`;
            return;
        }

        const totalSales = this.summary.total_sales || 1; // Evitar división por cero

        container.innerHTML = this.paymentMethods.map(pm => {
            const percentage = Math.min(100, Math.max(0, ((pm.total / totalSales) * 100))).toFixed(1);
            return `
                <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-700/60 space-y-1.5">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-200 flex items-center gap-1.5">
                            <i class="bi ${pm.icon || 'bi-wallet2'} text-blue-400"></i> ${pm.name}
                        </span>
                        <span class="font-mono font-bold text-emerald-400">$${toCurrency(pm.total || 0)}</span>
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-500" style="width: ${percentage}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400">
                        <span>${pm.count || 0} transacciones</span>
                        <span>${percentage}%</span>
                    </div>
                </div>
            `;
        }).join('');
    },

    renderTransactionsTable: function () {
        const tbody = document.getElementById('finance-transactions-table-body');
        const countLabel = document.getElementById('finance-records-count');

        if (countLabel) {
            countLabel.textContent = `${this.transactions.length} registros`;
        }

        if (!tbody) return;

        if (!this.transactions || this.transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-10 text-slate-500">
                        <i class="bi bi-receipt text-3xl block mb-2"></i>
                        No se encontraron transacciones para el filtro seleccionado
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.transactions.map(tx => `
            <tr class="hover:bg-slate-700/40 transition border-b border-slate-700/50">
                <td class="py-3 px-4">
                    <div class="font-bold text-white">#${tx.folio || 'N/A'}</div>
                    <div class="text-[10px] text-slate-400">${tx.table_name || 'Mostrador'} | Atendió: ${tx.waiter || 'N/A'}</div>
                </td>
                <td class="py-3 px-4">
                    <span class="inline-block px-2.5 py-0.5 text-[11px] font-semibold rounded-lg bg-slate-900 text-slate-200 border border-slate-700">
                        ${tx.payment_method || 'Efectivo'}
                    </span>
                </td>
                <td class="py-3 px-4 text-center font-mono text-xs text-slate-300">
                    ${tx.reference || '---'}
                </td>
                <td class="py-3 px-4 text-right font-mono text-amber-400 font-semibold">
                    +$${toCurrency(tx.tip || 0)}
                </td>
                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">
                    $${toCurrency(tx.amount || 0)}
                </td>
            </tr>
        `).join('');
    }
};