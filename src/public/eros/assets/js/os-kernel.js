/**
 * IXEA OS - Kernel & Herramientas de sistema
 * IxeaKernel, IxeaBouncer, IxeaUtils, IxeaData
 */

const IxeaKernel = {
    ticks: 0,

    init: function () {
        this.initHUD();
        this.checkSystemHealth();
        IxeaData.init();

        // Heartbeat único cada segundo
        setInterval(() => {
            this.tick();
        }, 1000);
    },

    tick: function () {
        this.ticks++;
        this.updateFullClock();

        let cadence = 'sec';

        // Evaluación de cadencias basadas en segundos acumulados
        if (this.ticks % 5 === 0) cadence = 'stream';       // Cada 5 seg
        if (this.ticks % 300 === 0) cadence = 'near';     // Cada 300 seg (5 min)
        if (this.ticks % 3600 === 0) cadence = 'far';     // Cada 3600 seg (1 hora)
        if (this.ticks % 43200 === 0) {                   // Cada 43200 seg (12 horas)
            cadence = 'batch';
            this.ticks = 0; // Reinicio para evitar overflow
        }

        // Ejecutar revisiones de salud de sistema cada 5s
        if (cadence === 'stream') {
            this.checkSystemHealth();
        }

        // Sincronización de datos en segundo plano
        IxeaData.syncData(cadence);

        // Ejecución de funciones en segundo plano
        this.runAppTicks(cadence);
    },

    /**
     * Ejecuta las funciones registradas en la stage activa
     */
    runAppTicks: function (cadence) {
        if (typeof IxeaStages === 'undefined' || !IxeaStages.activeStage) return;

        // Obtenemos el objeto de la app activa basándonos en la etapa actual
        const activeStageId = IxeaStages.activeStage;
        const jsObjectName = IxeaStages.launchedApps.get(activeStageId);

        if (!jsObjectName) return;
        const appObj = window[jsObjectName];

        // A) Mapeo por objeto onTick (Soporta funciones por cadencia)
        if (appObj?.onTick) {
            // Si onTick es una función simple, se ejecuta en cada segundo ('sec')
            if (typeof appObj.onTick === 'function') {
                appObj.onTick(this.ticks, cadence);
            }
            // Si onTick es un objeto con funciones asignadas a cadencias específicas:
            // Ej: onTick: { sec: 'updateTimers', stream: 'refreshView' }
            else if (typeof appObj.onTick === 'object') {
                const action = appObj.onTick[cadence];

                if (typeof action === 'function') {
                    action.call(appObj, this.ticks);
                } else if (typeof action === 'string' && typeof appObj[action] === 'function') {
                    appObj[action](this.ticks);
                }
            }
        }
    },

    // Telemetría (FPS)
    initHUD: function () {
        let lastTime = 0;
        const update = (time) => {
            const fps = Math.round(1000 / (time - lastTime));
            lastTime = time;
            const fpsEl = document.getElementById('hud-fps');
            if (fpsEl && fps > 0) fpsEl.innerText = fps;
            requestAnimationFrame(update);
        };
        requestAnimationFrame(update);
    },

    /* Lógica de Salud y Telemetría */
    checkSystemHealth: function () {
        const indicator = document.getElementById('status-indicator');
        const msLabel = document.getElementById('health-ms');

        const latency = IxeaBridge.getAvgPing();
        if (msLabel && latency) msLabel.innerText = latency + " ms";

        // Lógica de semáforo por rendimiento
        if (latency < 200) {
            indicator.className = "bi bi-circle-fill text-sfgreen";
            msLabel.className = "small text-sfgreen";
        } else if (latency < 400) {
            indicator.className = "bi bi-circle-fill text-sfyellow";
            msLabel.className = "small text-sfyellow";
        } else {
            indicator.className = "bi bi-circle-fill text-sfred text-blink";
            msLabel.className = "small text-sfred";
        }
    },

    /* Reloj Extendido */
    updateFullClock: function () {
        const now = new Date();
        const clockDetail = document.getElementById('full-clock-detail');
        if (clockDetail) {
            clockDetail.innerText = now.toLocaleTimeString();
        }
    }
};

/**
 * IxeaUtils - Herramientas de sistema
 */
const IxeaUtils = {
    paymentMethod: {
        'cash': 'Efectivo',
        'card': 'Tarjeta',
        'transfer': 'Transferencia',
        'check': 'Cheque',
        'credit': 'Crédito'
    },

    /**
     * Imprime ticket de venta o pagaré. 
     * @param {Object} data - Debe contener:
     * folio, date, user_name, customer_name, items[], total, is_promissory
     **/
    printTicket: function (data) {
        // 1. Clonar el molde original
        const masterBody = document.getElementById("saleTicketBody");
        const clonedTicket = masterBody.cloneNode(true);
        clonedTicket.removeAttribute('hidden');

        // 2. Llenar encabezados desde el objeto 'data'
        // Si 'date' no viene, usamos la actual (pero para reimpresión debería venir del server)
        const ticketDate = data.date.slice(0, -3) || new Date().toLocaleString();

        clonedTicket.querySelector(".folioTicket").textContent = data.folio;
        clonedTicket.querySelector(".dateTicket").textContent = ticketDate;

        // Aquí usamos el nombre que viene en la data (el cajero original)
        clonedTicket.querySelector(".userTicket").textContent = data.user_name || "N/A";

        clonedTicket.querySelector(".clientTicket").textContent = data.customer_name || "PÚBLICO EN GENERAL";

        // 3. Llenar tabla usando los items que vienen en 'data'
        const tbTicket = clonedTicket.querySelector('.tbTicket');
        const trTemplate = document.getElementById('saleTicketTr').content;
        tbTicket.innerHTML = '';

        // Iteramos sobre data.items en lugar de this.cart
        if (data.items && Array.isArray(data.items)) {
            data.items.forEach(item => {
                const tr = trTemplate.cloneNode(true);

                // Fila Principal
                tr.querySelector('.tdProduct').textContent = item.name;
                tr.querySelector('.tdQuant').textContent = item.qty;
                tr.querySelector('.tdAmount').textContent = toCurrency(item.subtotal);

                // Fila de Detalles (Nuevos campos)
                tr.querySelector('.tdSku').textContent = item.sku;
                tr.querySelector('.tdUnit').textContent = item.unit;
                tr.querySelector('.tdUnitPrice').textContent = toCurrency(item.price);

                tbTicket.appendChild(tr);
            });
        }

        // Bloque de total y descuento
        const discRow = clonedTicket.querySelector(".discount");
        if (data.totalDiscount > 0) {
            discRow.style.display = "block";
            clonedTicket.querySelector(".discountTicket").textContent = toCurrency(data.totalDiscount);
        }
        clonedTicket.querySelector(".totalTicket").textContent = toCurrency(data.total);

        // Bloque de pagaré
        let promissoryHtml = '';
        if (data.is_promissory) {
            const totalLetras = this.numberToLetters(data.total);

            promissoryHtml = `
                <p style="text-align: center; font-weight: bold; margin-bottom: 5px;">
                    ESTE DOCUMENTO ES UN PAGARÉ
                </p>
                
                <div style="text-align: justify; margin-bottom: 10px;">
                    <p>
                        Me obligo incondicionalmente a pagar a favor de <b>ELIZABETH BERNARDINA MIRANDA ROSAS</b>, 
                        la cantidad de <b>$${toCurrency(data.total)}</b> (${totalLetras}) 
                        correspondiente al folio <b>${data.folio}</b> con fecha <b>${ticketDate}</b>.
                    </p>
                    <p>El pago se realizará en un plazo no mayor a 30 días.</p>
                </div>
                
                <p style="margin: 0;"><b>Nombre:</b> ${data.customer_name}</p>
                <p style="margin: 0;"><b>RFC:</b> ${data.tax_id || 'N/A'}</p>
            
                <!-- Firma con borde más delgado (0.5px) -->
                <div style="margin-top: 50px; text-align: center;">
                    <div style="border-bottom: 0.5px solid #000; width: 70%; margin: 0 auto 5px auto;"></div>
                    <p style="margin: 0;">Firma del Cliente</p>
                </div>
            `;
            const promissory = clonedTicket.querySelector(".promissory-note");
            promissory.innerHTML = promissoryHtml;
            promissory.hidden = false;
            clonedTicket.querySelector(".footer").hidden = true;
            clonedTicket.querySelector(".greetings").hidden = true;
        }

        // 4. Inyección en Iframe
        const ticketStyle = document.getElementById('saleTicketStyle').innerHTML;
        this.injectAndPrint(clonedTicket.innerHTML, ticketStyle);
    },

    /**
     * Imprime ticket de estado de cuenta de crédigo
     * @param {Object} data - Debe contener:
     * customer_name, tax_id, period_start, period_end, previous_balance, history[], pending[]
     **/
    printCustomerStatement: function (data) {
        const masterBody = document.getElementById("statementTicketBody");
        const clonedTicket = masterBody.cloneNode(true);
        clonedTicket.removeAttribute('hidden');

        // 1. Cabecera y Detalles
        clonedTicket.querySelector(".stClient").textContent = data.customer_name;
        clonedTicket.querySelector(".stTaxId").textContent = data.tax_id || "N/A";
        clonedTicket.querySelector(".stPeriod").textContent = `${data.period_start} al ${data.period_end}`;
        clonedTicket.querySelector(".stPrintDate").textContent = new Date().toLocaleString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });

        // 2. Inicialización de contadores
        let totalCargos = 0;
        let totalAbonos = 0;
        const historyBody = clonedTicket.querySelector('.stHistoryBody');
        const historyTemplate = document.getElementById('stHistoryTr').content;
        historyBody.innerHTML = '';

        // 3. Procesar Historial (Suma y Dibujo en un solo paso)
        data.history.forEach(mov => {
            const tr = historyTemplate.cloneNode(true);
            const isCancelled = (mov.status === 'cancelled');
            const dateShort = mov.date.substring(5, 10); // MM-DD

            // CORRECCIÓN: Definir refText antes de usarlo
            let refText = mov.ref || 'S/N';
            if (isCancelled) refText += " (CANC)";

            tr.querySelector('.stDateRef').innerHTML = `<b>${dateShort}</b><br>${refText}`;

            const cellCargo = tr.querySelector('.stCargo');
            const cellAbono = tr.querySelector('.stAbono');

            if (mov.type === 'cargo') {
                cellCargo.textContent = toCurrency(mov.amount);
                if (isCancelled) cellCargo.style.textDecoration = "line-through";
                // Solo sumar al total si es válido
                if (!isCancelled) totalCargos += parseFloat(mov.amount);
            } else {
                cellAbono.textContent = toCurrency(mov.amount);
                if (isCancelled) cellAbono.style.textDecoration = "line-through";
                // Solo sumar al total si es válido
                if (!isCancelled) totalAbonos += parseFloat(mov.amount);
            }
            historyBody.appendChild(tr);
        });

        // 4. Actualizar Resumen con montos limpios
        const actualBalance = parseFloat(data.previous_balance) + totalCargos - totalAbonos;

        clonedTicket.querySelector(".stPrevBalance").textContent = toCurrency(data.previous_balance);
        clonedTicket.querySelector(".stPeriodCargos").textContent = toCurrency(totalCargos);
        clonedTicket.querySelector(".stPeriodAbonos").textContent = toCurrency(totalAbonos);
        clonedTicket.querySelector(".stTotalBalance").textContent = toCurrency(actualBalance);

        // 5. Llenar Pendientes (Antigüedad) - Tu lógica está perfecta aquí
        const pendingBody = clonedTicket.querySelector('.stPendingBody');
        const pendingTemplate = document.getElementById('stPendingTr').content;
        pendingBody.innerHTML = '';

        data.pending.forEach(p => {
            const tr = pendingTemplate.cloneNode(true);
            // Cálculo de días transcurridos
            const days = Math.floor((new Date() - new Date(p.created_at)) / (1000 * 60 * 60 * 24));

            tr.querySelector('.stFolioP').textContent = p.folio;
            tr.querySelector('.stDaysP').textContent = days;
            tr.querySelector('.stAmountP').textContent = '$' + toCurrency(p.remaining_balance);
            pendingBody.appendChild(tr);
        });

        // 6. Lanzar Impresión
        const slStyle = document.getElementById('saleTicketStyle').innerHTML;
        const stStyle = document.getElementById('statementTicketStyle').innerHTML;

        this.injectAndPrint(clonedTicket.innerHTML, slStyle + stStyle);
    },

    printPurchaseTicket: function (data) {
        const masterBody = document.getElementById("purchaseTicketBody");
        const clonedTicket = masterBody.cloneNode(true);
        clonedTicket.removeAttribute('hidden');

        const purchase = data.purchase;

        clonedTicket.querySelector(".folioP").textContent = purchase.folio;
        clonedTicket.querySelector(".dateP").textContent = purchase.operation_date || new Date().toLocaleString();
        clonedTicket.querySelector(".supplierP").textContent = purchase.company_name;
        clonedTicket.querySelector(".typeP").textContent = purchase.payment_method === "credit" ? "CRÉDITO" : "CONTADO";
        clonedTicket.querySelector(".userP").textContent = purchase.user_name;

        const tb = clonedTicket.querySelector('.tbPurchase');
        data.items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-size:0.7rem;"><b>${item.name}</b></td>
                <td style="text-align:right;">${parseFloat(item.qty).toFixed(3)}</td>
                <td style="text-align:right;">${toCurrency(item.qty * item.cost)}</td>
            `;
            tb.appendChild(tr);

            const trDetails = document.createElement('tr');
            trDetails.setAttribute("class", "tr-details")
            trDetails.innerHTML = `
                <td colspan="3">
                    SKU: ${item.sku} | 
                    Unidad: ${item.unit} | 
                    P. Unit: $${item.cost}
                </td>
            `;
            tb.appendChild(trDetails);
        });

        clonedTicket.querySelector(".totalP").textContent = toCurrency(purchase.total_amount);

        // Inyectamos Iframe
        const ticketStyle = document.getElementById('saleTicketStyle').innerHTML;
        this.injectAndPrint(clonedTicket.innerHTML, ticketStyle);
    },

    // Función de apoyo para no repetir código de Iframe
    injectAndPrint: function (html, style) {
        let iframe = document.getElementById('print-iframe') || document.createElement('iframe');
        if (!iframe.id) {
            iframe.id = 'print-iframe';
            iframe.style.display = 'none';
            document.body.appendChild(iframe);
        }
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                <style>${style}</style>
            </head>
            <body>${html}</body>
            </html>
        `);
        doc.close();
        setTimeout(() => { iframe.contentWindow.focus(); iframe.contentWindow.print(); }, 500);
    },

    /**
     * Convierte un número a su representación en texto legal (Formato M.N.)
     * @param {number} num - El monto a convertir
     * @returns {string} - El monto en letras
     */
    numberToLetters: function (num) {
        const data = {
            numero: num,
            enteros: Math.floor(num),
            centavos: Math.round(num * 100) - Math.floor(num) * 100,
            letrasMonedaPlural: 'PESOS',
            letrasMonedaSingular: 'PESO',
            letrasMonedaCentavo: '/100 M.N.'
        };

        // Formateo de centavos
        let centavosStr = (data.centavos < 10 ? '0' : '') + data.centavos + data.letrasMonedaCentavo;

        if (data.enteros === 0) return `CERO ${data.letrasMonedaPlural} ${centavosStr}`;

        const nombreMoneda = (data.enteros === 1) ? data.letrasMonedaSingular : data.letrasMonedaPlural;
        return `${this._helperMillones(data.enteros)} ${nombreMoneda} ${centavosStr}`.toUpperCase().trim();
    },

    /* Helpers Privados (No se acceden directamente usualmente) */
    _helperUnidades: (num) => {
        const u = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        return u[num] || '';
    },

    _helperDecenas: function (num) {
        let decena = Math.floor(num / 10);
        let unidad = num - (decena * 10);

        switch (decena) {
            case 1:
                const especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE'];
                return unidad <= 5 ? especiales[unidad] : 'DIECI' + this._helperUnidades(unidad);
            case 2: return unidad === 0 ? 'VEINTE' : 'VEINTI' + this._helperUnidades(unidad);
            case 3: return unidad === 0 ? 'TREINTA' : 'TREINTA Y ' + this._helperUnidades(unidad);
            case 4: return unidad === 0 ? 'CUARENTA' : 'CUARENTA Y ' + this._helperUnidades(unidad);
            case 5: return unidad === 0 ? 'CINCUENTA' : 'CINCUENTA Y ' + this._helperUnidades(unidad);
            case 6: return unidad === 0 ? 'SESENTA' : 'SESENTA Y ' + this._helperUnidades(unidad);
            case 7: return unidad === 0 ? 'SETENTA' : 'SETENTA Y ' + this._helperUnidades(unidad);
            case 8: return unidad === 0 ? 'OCHENTA' : 'OCHENTA Y ' + this._helperUnidades(unidad);
            case 9: return unidad === 0 ? 'NOVENTA' : 'NOVENTA Y ' + this._helperUnidades(unidad);
            default: return this._helperUnidades(unidad);
        }
    },

    _helperCentenas: function (num) {
        let centena = Math.floor(num / 100);
        let resto = num - (centena * 100);

        switch (centena) {
            case 1: return resto > 0 ? 'CIENTO ' + this._helperDecenas(resto) : 'CIEN';
            case 2: return 'DOSCIENTOS ' + this._helperDecenas(resto);
            case 3: return 'TRESCIENTOS ' + this._helperDecenas(resto);
            case 4: return 'CUATROCIENTOS ' + this._helperDecenas(resto);
            case 5: return 'QUINIENTOS ' + this._helperDecenas(resto);
            case 6: return 'SEISCIENTOS ' + this._helperDecenas(resto);
            case 7: return 'SETECIENTOS ' + this._helperDecenas(resto);
            case 8: return 'OCHOCIENTOS ' + this._helperDecenas(resto);
            case 9: return 'NOVECIENTOS ' + this._helperDecenas(resto);
            default: return this._helperDecenas(resto);
        }
    },

    _helperMiles: function (num) {
        let divisor = 1000;
        let cientos = Math.floor(num / divisor);
        let resto = num - (cientos * divisor);

        let strMiles = '';
        if (cientos === 1) strMiles = 'UN MIL';
        if (cientos > 1) strMiles = this._helperCentenas(cientos) + ' MIL';

        return `${strMiles} ${this._helperCentenas(resto)}`.trim();
    },

    _helperMillones: function (num) {
        let divisor = 1000000;
        let millones = Math.floor(num / divisor);
        let resto = num - (millones * divisor);

        let strMillones = '';
        if (millones === 1) strMillones = 'UN MILLON DE';
        if (millones > 1) strMillones = this._helperCentenas(millones) + ' MILLONES DE';

        return `${strMillones} ${this._helperMiles(resto)}`.trim();
    }
};

/**
 * IxeaBridge - Objeto global para comunicaciones externas y UI
 */
const IxeaBridge = {
    pingHistory: [],
    maxHistorySize: 20,
    defaultHeaders: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },

    /** Obtiene dinámicamente el Token CSRF desde el meta tag */
    _getCsrfToken: function () {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    /** Función centralizada para peticiones GET/POST/PUT/DELETE */
    request: async function (url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const startTime = performance.now();

        // Extraemos headers explícitamente para evitar duplicados
        const { headers: customHeaders, body: customBody, ...extraOptions } = options;

        // Inyectamos X-CSRF-TOKEN automáticamente en métodos no-idempotentes (POST, PUT, DELETE, PATCH)
        const csrfHeader = {};
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            csrfHeader['X-CSRF-TOKEN'] = this._getCsrfToken();
        }

        const config = {
            method,
            headers: {
                ...IxeaBridge.defaultHeaders,
                ...csrfHeader,
                ...customHeaders
            },
            ...extraOptions
        };

        if (customBody && typeof customBody === 'object' && !(customBody instanceof FormData)) {
            config.body = JSON.stringify(customBody);
        } else if (customBody) {
            config.body = customBody;
        }

        try {
            const response = await fetch(url, config);
            const endTime = performance.now();
            const duration = Math.round(endTime - startTime);

            // Registrar latencia (usando la referencia directa al objeto)
            IxeaBridge._recordPing(url, method, response.status, duration);

            // 1. Validar Tipo de Contenido (JSON vs HTML/Texto)
            const contentType = response.headers.get('content-type') || '';
            let data = {};

            if (contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const textError = await response.text();
                throw new Error(`[Server Non-JSON Response ${response.status}]: ${textError.substring(0, 150)}...`);
            }

            // 2. Manejo de Errores HTTP o Respuestas del Backend ($data['success'] = false)
            if (!response.ok || data.success === false) {
                const errorMsg = data.message || data.error || `HTTP Error ${response.status}`;
                throw new Error(errorMsg);
            }

            return data;

        } catch (error) {
            console.error(`[IxeaBridge Error] [${method}] ${url}:`, error.message);
            throw error;
        }
    },

    /** Métodos Helper HTTP */
    get: function (url, params = null, headers = {}) {
        let finalUrl = url;

        // Si enviamos un objeto de parámetros en un GET, lo convertimos a query string
        if (params && typeof params === 'object') {
            const queryString = new URLSearchParams(params).toString();
            if (queryString) {
                finalUrl += (finalUrl.includes('?') ? '&' : '?') + queryString;
            }
        }

        return IxeaBridge.request(finalUrl, { method: 'GET', headers });
    },

    post: function (url, body = {}, headers = {}) {
        return IxeaBridge.request(url, { method: 'POST', body, headers });
    },

    put: function (url, body = {}, headers = {}) {
        return IxeaBridge.request(url, { method: 'PUT', body, headers });
    },

    delete: function (url, body = {}, headers = {}) {
        return IxeaBridge.request(url, { method: 'DELETE', body, headers });
    },

    /** Helper para registrar latencia */
    _recordPing: function (url, method, status, durationMs) {
        IxeaBridge.pingHistory.push({
            url,
            method,
            status,
            duration: durationMs,
            timestamp: new Date().toISOString()
        });

        if (IxeaBridge.pingHistory.length > IxeaBridge.maxHistorySize) {
            IxeaBridge.pingHistory.shift();
        }
    },

    /** Promedio del Ping (ms) de las últimas N peticiones */
    getAvgPing: function () {
        if (IxeaBridge.pingHistory.length === 0) return 0;
        const total = IxeaBridge.pingHistory.reduce((acc, item) => acc + item.duration, 0);
        return Math.round(total / IxeaBridge.pingHistory.length);
    },

    /** Muestra las métricas acumuladas de red */
    getNetworkMetrics: function () {
        return {
            avgPingMs: IxeaBridge.getAvgPing(),
            lastPingMs: IxeaBridge.pingHistory.length > 0
                ? IxeaBridge.pingHistory[IxeaBridge.pingHistory.length - 1].duration
                : 0,
            history: IxeaBridge.pingHistory
        };
    },

    /** Callback para procesar conexión externa de Gmail */
    mailConnection: function (status) {
        console.log("IxeaBridge - Recibida señal de Gmail:", status);
        if (!status) return;

        const indicador = document.getElementById('gmail-indicator');
        if (indicador) {
            indicador.className = "bi bi-circle-fill text-sfgreen";
            indicador.title = "Gmail Conectado";
            indicador.dataset.connected = "true";
        }

        const btnConfirmation = document.getElementById('gmail-confirmation');
        if (btnConfirmation) {
            btnConfirmation.remove();
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'bottom',
                icon: 'success',
                title: 'Gmail vinculado con éxito',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
};

/**
 * IxeaData - Sincronizador Central de Datos y Caché de Endpoints
 */
const IxeaData = {
    cache: {},
    timestamps: {},

    init: function () {
        try {
            const storedCache = localStorage.getItem('ixea_data_cache');
            const storedTs = localStorage.getItem('ixea_data_timestamps');

            if (storedCache) this.cache = JSON.parse(storedCache);
            if (storedTs) this.timestamps = JSON.parse(storedTs);
        } catch (e) {
            console.warn('[IxeaData] Error leyendo localStorage:', e);
        }
    },

    /**
     * Petición de datos hacia adentro (Caché local primero)
     * @param {string} appName - Ej: 'bistro-pos'
     * @param {string} endpoint - Ej: 'get-modifiers'
     * @param {object} params - Parámetros extra para la petición
     */
    get: async function (appName, endpoint, params = {}) {
        // Inicializar rama del módulo si no existe en caché
        if (!this.cache[appName]) this.cache[appName] = {};
        if (!this.timestamps[appName]) this.timestamps[appName] = {};

        // Si tenemos datos en caché y solo se pide caché, los entregamos
        const cachedData = this.cache[appName][endpoint];
        if (cachedData && params.cacheOnly) {
            return { success: true, data: cachedData };
        }

        return await this.fetchAndCache(appName, endpoint, params);
    },

    /**
     * Executa la petición y actualiza el caché según la respuesta
     */
    fetchAndCache: async function (appName, endpoint, params = {}) {
        // Si forceSync es true, enviamos hash vacío '' para obligar al backend a responder con datos
        const currentHash = params.forceSync ? '' : (this.timestamps?.[appName]?.[endpoint] || '');

        try {
            const response = await IxeaBridge.get(`/api/v1/${appName}/${endpoint}`, {
                ...params,
                hash: currentHash
            });

            // CASO A: No hubo cambios en el servidor
            if (response.success && response.changed === false) {
                return { success: true, changed: false, data: this.cache[appName][endpoint] };
            }

            // CASO B: Hubo cambios o es primera carga (o forceSync)
            if (response.success && response.changed === true) {
                this.cache[appName][endpoint] = response.data;
                this.timestamps[appName][endpoint] = response.hash;

                this._saveToDisk();

                return { success: true, changed: true, data: response.data };
            }

        } catch (e) {
            console.warn(`[IxeaData] Error conectando a ${appName}/${endpoint}. Usando copia local.`, e.message);
            return { success: false, message: e.message, data: this.cache[appName]?.[endpoint] || null };
        }
    },

    /**
     * Proceso de Sincronización Automática por Cadencia (Invocado por IxeaKernel)
     */
    syncData: async function (cadence) {
        if (cadence === 'sec') return;

        // Usaremos una lista/mapa de tareas estructuradas en lugar de un Set de strings simples
        const syncTasks = [];

        // Iterar sobre las apps activas en IxeaStages
        if (typeof IxeaStages !== 'undefined' && IxeaStages.launchedApps) {
            IxeaStages.launchedApps.forEach(appKey => {
                const appObj = window[appKey];
                if (!appObj) return;

                const appName = appObj.jsName;
                let list = [];
                switch (cadence) {
                    case 'stream':
                        const stageName = IxeaStages.activeStage.replace("stage-", "");
                        if (appName === stageName) {
                            list = appObj.toStream || [];
                        }
                        break;
                    case 'near': list = appObj.toNear || []; break;
                    case 'far': list = appObj.toFar || []; break;
                    case 'batch': list = appObj.toBatch || []; break;
                }

                if (list.length > 0) {
                    list.forEach(item => {
                        let endpoint = '';
                        let params = {};

                        if (Array.isArray(item)) {
                            // Formato: ['get-orders', { deliveryStatus: 'pending' }]
                            endpoint = item[0];

                            // Si el segundo elemento es una función (ej: obtener estado en tiempo real),
                            // la ejecutamos para obtener los params actualizados del módulo.
                            if (typeof item[1] === 'function') {
                                params = item[1]() || {};
                            } else if (typeof item[1] === 'object') {
                                params = item[1] || {};
                            }
                        } else if (typeof item === 'string') {
                            // Formato String simple: 'get-orders'
                            endpoint = item;
                        }

                        if (endpoint) {
                            syncTasks.push({
                                appName,
                                endpoint,
                                params
                            });
                        }
                    });
                }
            });
        }

        if (syncTasks.length === 0) return;

        // Refrescar en segundo plano cada endpoint configurado pasando sus parámetros
        syncTasks.forEach(task => {
            this.fetchAndCache(task.appName, task.endpoint, task.params);
        });
    },

    _saveToDisk: function () {
        try {
            localStorage.setItem('ixea_data_cache', JSON.stringify(this.cache));
            localStorage.setItem('ixea_data_timestamps', JSON.stringify(this.timestamps));
        } catch (e) {
            console.error('[IxeaData] Error guardando en localStorage (Quota Exceeded?):', e);
        }
    }
};

/**
 * Iniciadores Globales
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Iniciar servicios de fondo
    IxeaKernel.init();
    // 2. Activamos doble toque
    IxeaDocks.initTouchTriggers();
    // 3. Iniciar intro
    IxeaUI.init();

    console.log("Ixea EROS: Boot Completed.");
});

/**
 * Funciones Globales
 */
const toCurrency = (amount) => {
    return Number(amount || 0).toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};