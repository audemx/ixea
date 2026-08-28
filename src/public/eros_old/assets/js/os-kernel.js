/**
 * IXEA OS - Kernel & Herramientas de sistema
 * IxeaKernel, IxeaBouncer, IxeaUtils, IxeaData
 */
 
const IxeaKernel = {
    init: function() {
        this.initHUD();
        this.checkSystemHealth();
        setInterval(() => this.checkSystemHealth(), 10000);
        setInterval(() => this.updateFullClock(), 1000);
        console.log("IxeaKernel: Operational");
    },

    // Telemetría (FPS)
    initHUD: function() {
        let lastTime = 0;
        const update = (time) => {
            const fps = Math.round(1000 / (time - lastTime));
            lastTime = time;
            const fpsEl = document.getElementById('hud-fps');
            if(fpsEl && fps > 0) fpsEl.innerText = fps;
            requestAnimationFrame(update);
        };
        requestAnimationFrame(update);
    },
    
    /* Lógica de Salud y Telemetría */
    checkSystemHealth: function() {
        const start = Date.now();
        const indicator = document.getElementById('status-indicator');
        const msLabel = document.getElementById('health-ms');
        const loadLabel = document.getElementById('health-load');
        
        // Petición al servidor para medir latencia real
        // Apuntamos a un archivo .txt que NO pase por security.php
        fetch('/ping.txt?t=' + Date.now(), { method: 'HEAD', cache: 'no-store' })
        .then(() => {
            const latency = Date.now() - start;
            if(msLabel) msLabel.innerText = latency + " ms";
            
            // Lógica de semáforo por rendimiento
            if (latency < 120) {
                indicator.className = "bi bi-circle-fill text-sfgreen";
                msLabel.className = "small text-sfgreen";
            } else if (latency < 350) {
                indicator.className = "bi bi-circle-fill text-sfyellow";
                msLabel.className = "small text-sfyellow";
            } else {
                indicator.className = "bi bi-circle-fill text-sfred";
                msLabel.className = "small text-sfred";
            }
        })
        .catch(() => {
            indicator.className = "bi bi-circle-fill text-sfred text-blink";
            if(msLabel){
                msLabel.innerText = "Offline";
                msLabel.className = "text-sfred";
            }
            if(loadLabel) {
                loadLabel.innerText = "Desconectado";
                loadLabel.className = "text-sfred";
            }
        });
    },
    
    /* Reloj Extendido */
    updateFullClock: function() {
        const now = new Date();
        const clockDetail = document.getElementById('full-clock-detail');
        if(clockDetail) {
            clockDetail.innerText = now.toLocaleTimeString();
        }
    }
};

/**
 * IxeaBouncer - Control de permisos
 */
const IxeaBouncer = {
    overlayId: 'ixea-bouncer-overlay',
    
    can: function(key) {
        const permissions = window.IXEA_USER?.permissions ?? [];
        return permissions.includes('all_access') || permissions.includes(key);
    },
    
    requestAuth: function(key, callback){ 
        Swal.fire({
            title: 'Autorización Requerida',
            text: 'Ingresa PIN de autorización',
            input: 'password',
            inputAttributes: { maxlength: 4, inputmode: 'numeric', style: 'text-align: center; letter-spacing: 10px;' },
            showCancelButton: true,
            confirmButtonText: 'Validar',
            preConfirm: (pin) => {
                let fd = new FormData();
                fd.append('pin', pin);
                fd.append('auth_type', key); // Corregido: antes decía 'permission'

                return fetch('/api/post-handler.php?action=verify_auth', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    return data;
                })
                .catch(err => Swal.showValidationMessage(err.message));
            }
        }).then((result) => {
            // Corregido: antes decía 'onAuthorized'
            if (result.isConfirmed && callback) callback(result.value);
        });
    },
    
    lockBtn: function(btn) {
        if (!btn || btn.disabled || btn.classList.contains('btn-loading')) return false;

        // Guardamos el contenido original para restaurarlo después
        btn.dataset.originalHtml = btn.innerHTML;
        btn.dataset.isLocked = "true";
        
        btn.disabled = true;
        btn.classList.add('btn-loading');
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Procesando...`;
        return true;
    },

    // Restaura el botón a su estado original
    releaseBtn: function(btn) {
        if (!btn || !btn.dataset.originalHtml) return;

        btn.disabled = false;
        btn.classList.remove('btn-loading');
        btn.innerHTML = btn.dataset.originalHtml;
        delete btn.dataset.isLocked;
    },
    
    /** Muestra un pantallazo de carga frente a todo para bloquear todo proceso **/
    showLoading: function(message = 'Procesando...') {
        const activeStage = IxeaStages.activeStage;
        const mainPos = document.getElementById(activeStage);
        
        let overlay = document.getElementById(this.overlayId);
        if (!overlay) {
            // Crear el overlay si no existe
            overlay = document.createElement('div');
            overlay.id = this.overlayId;
            overlay.className = "animate__animated animate__fadeInUp";
            overlay.innerHTML = `
                <div class="container-fluid d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="bouncer-spinner mb-3"></div>
                    <div class="bouncer-text w-100" id="bouncer-msg">${message}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        }
    },

    // Elimina el elemento de bloqueo
    hideLoading: function() {
        const overlay = document.getElementById(this.overlayId);
        if (overlay) {
            overlay.remove();
        }
    },
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
    printTicket: function(data) {
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
    printCustomerStatement: function(data) {
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
    
    printPurchaseTicket: function(data) {
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
    injectAndPrint: function(html, style) {
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
    numberToLetters: function(num) {
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

    _helperDecenas: function(num) {
        let decena = Math.floor(num / 10);
        let unidad = num - (decena * 10);

        switch(decena) {
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

    _helperCentenas: function(num) {
        let centena = Math.floor(num / 100);
        let resto = num - (centena * 100);

        switch(centena) {
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

    _helperMiles: function(num) {
        let divisor = 1000;
        let cientos = Math.floor(num / divisor);
        let resto = num - (cientos * divisor);

        let strMiles = '';
        if (cientos === 1) strMiles = 'UN MIL';
        if (cientos > 1) strMiles = this._helperCentenas(cientos) + ' MIL';

        return `${strMiles} ${this._helperCentenas(resto)}`.trim();
    },

    _helperMillones: function(num) {
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
 * IxeaBridge - Objeto global para comunicaciones externas
 */
const IxeaBridge = {
    // Función de respuesta al éxito de vinculo
    mailConnection: function(status) {
        console.log("IxeaBridge - Recibida señal de Gmail:", status);
        if (!status) return;
        
        const indicador = document.getElementById('gmail-indicator');
        if (indicador) {
            indicador.className = "bi bi-circle-fill text-sfgreen";
            indicador.title = "Gmail Conectado";
            indicador.dataset.connected = "true";
        }

        // 2. Removemos el botón de "VINCULAR GMAIL"
        const btnConfirmation = document.getElementById('gmail-confirmation');
        if (btnConfirmation) {
            btnConfirmation.remove();
        }

        // 3. Opcional: Notificación discreta (Toast)
        Swal.fire({
            toast: true,
            position: 'bottom',
            icon: 'success',
            title: 'Gmail vinculado con éxito',
            showConfirmButton: false,
            timer: 3000
        });
    },
};

const IxeaData = {
    cache: {},
    checksums: {},

    get: async function(table) {
        const currentHash = this.checksums[table] || '';
        
        try {
            const res = await fetch(`/api/get-data?action=get_sync_catalog&table=${table}&hash=${currentHash}`);
            const data = await res.json();

            if (data.has_changes) {
                this.cache[table] = data.items;
                this.checksums[table] = data.hash;
                // Evento opcional para avisar a las apps que algo cambió
                console.log('Catálogo actualizado:', table);
            }
        } catch (err) {
            console.error(`Error en Sync ${table}:`, err);
        }

        return this.cache[table] || [];
    }
};

/**
 * Funciones Globales
 */
const toCurrency = (amount) => {
    return Number(amount || 0).toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};