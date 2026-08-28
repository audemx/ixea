/** assets/js/apps/credit.js **/
window.CreditApp = {
    isInitialized: false,
    currentCustomerId: null,
    currentCustomerData: null,

    init: function() {
        if (this.isInitialized) return;
        this.isInitialized = true;
        this.loadSummary();
        console.log("[Credit] App Started ...");
    },
    
    loadSummary: function() {
        // Consultamos directamente la cuenta de crédito (ID 5)
        fetch('api/get-data?action=get_balance&account=credit')
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const elTotal = document.getElementById('total-receivable');
                    const elCount = document.getElementById('active-credit-clients');
                    
                    if(elTotal) elTotal.textContent = '$' + toCurrency(data.balance);
                    if(elCount) elCount.textContent = data.active_clients || 0;
                }
            })
            .catch(err => console.error("[Credit] Error al sincronizar balance:", err));
    },
    
    showOverdueReport: function() {
        IxeaStages.openModal({
            templateId: 'tpl-credit-risk-report',
            size: 'lg',
            onOpen: () => {
                fetch('api/get-data?action=get_overdue_report')
                    .then(res => res.json())
                    .then(data => {
                        this.renderRiskReport(data);
                    });
            }
        });
    },
    
    renderRiskReport: function(data) {
        const container = document.getElementById('risk-report-body');
        // Aquí mapeas los clientes que tienen facturas vencidas
        // y dibujas una gráfica pequeña o una lista con badges de colores:
        // Rojo para > 30 días, Amarillo para > 15 días.
    },
    
    // Busqueda de clientes
    searchCustomer: function(query) {
        const resultsDiv = document.getElementById('search-results');
        
        if (query.length < 2) {
            resultsDiv.classList.add('d-none');
            return;
        }
    
        // Usamos el endpoint unificado
        fetch(`api/get-data?action=search_customers&term=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            
            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="list-group-item small text-muted">No hay coincidencias</div>';
            } else {
                data.forEach(customer => {
                    const btn = document.createElement('button');
                    btn.className = "list-group-item list-group-item-action border-0 py-2";
                    // Filtramos visualmente si el cliente tiene crédito aprobado o no
                    const badgeClass = customer.credit_status === 'approved' ? 'bg-sfgreen' : 'bg-secondary';
                    
                    btn.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small">${customer.name}</div>
                                <div style="font-size: 0.7rem;" class="text-muted">${customer.rfc} | ${customer.phone || 'S/T'}</div>
                            </div>
                            <span class="badge ${badgeClass} rounded-pill" style="font-size: 0.6rem;">
                                ${customer.credit_status === 'approved' ? '$' + toCurrency(customer.balance) : 'Sin Crédito'}
                            </span>
                        </div>
                    `;
                    btn.onclick = () => {
                        if(customer.credit_status === 'approved') {
                            this.selectCustomer(customer.customer_id);
                        } else {
                            Swal.fire('Atención', 'Este cliente no tiene una cuenta de crédito activa.', 'info');
                        }
                        resultsDiv.classList.add('d-none');
                    };
                    resultsDiv.appendChild(btn);
                });
            }
            resultsDiv.classList.remove('d-none');
        });
    },
    
    selectCustomer: function(customer_id) {
        document.getElementById('search-results').classList.add('d-none');
        document.getElementById('credit-empty-state').classList.add('d-none');
        document.getElementById('active-profile').classList.remove('d-none');
        
        // Llamada a la API para traer el detalle completo y renderizar el template
        this.loadCustomerDetails(customer_id);
    },
    
    loadCustomerDetails: function(customer_id) {
        
        fetch(`api/get-data?action=get_customer_credit_profile&customer_id=${customer_id}`)
            .then(res => res.json())
            .then(data => {
                // Rastreo de operación
                if (data.steps) {
                    console.group("Rastreo de Ejecución en Servidor");
                    data.steps.forEach(s => console.log(`[%c${s.time}%c] ${s.step}`, "color: blue", "color: inherit", s.data));
                    console.groupEnd();
                }
                
                if (!data || data.error) {
                    Swal.fire('Error', data.message || 'No se pudo cargar el perfil', 'error');
                    return;
                }
                // Si la tabla está vacía, aseguramos que aging no sea undefined
                data.profile.aging = data.profile.aging || { total_balance: 0, range_0_15: 0, range_16_30: 0, range_over_30: 0 };
                data.profile.recent_movements = data.profile.recent_movements || [];
                
                this.currentCustomerId = customer_id;
                this.currentCustomerData = data.profile;
                this.renderProfile(data.profile);
            })
            // Fallo en la solicitud
            .catch(err => {
                console.error("Fallo en la solicitud:", err);
                Swal.fire({
                        icon: 'error',
                        title: 'Fallo en la solicitud',
                        text: 'Comunicate con soporte'
                    });
            });
    },

    renderProfile: function(data) {
        const container = document.getElementById('active-profile');
        const template = document.getElementById('tpl-credit-detail-pro');
        if (!container || !template) return;
        
        container.innerHTML = '';
        const clone = template.content.cloneNode(true);
        
        // Llenar Datos Básicos
        clone.querySelector('#det-customer-name').textContent = data.full_name;
        clone.querySelector('#det-customer-rfc').textContent = data.tax_id || 'SIN RFC';
        clone.querySelector('#det-customer-phone').textContent = data.phone || 'SIN TEL';
        clone.querySelector('#det-balance').textContent = toCurrency(data.aging.total_balance);
        clone.querySelector('#btn-credit-payment').onclick = () => CreditApp.openPaymentModal(data);
        clone.querySelector('#btn-print-statement').onclick = () => CreditApp.printStatement(data);
    
        // Lógica de Inteligencia (Score de Salud)
        const score = data.current_score || 100;
        const healthBar = clone.querySelector('#ai-health-bar');
        const healthText = clone.querySelector('#ai-health-text');
        
        healthBar.style.width = score + '%';
        if(score > 80) {
            healthBar.className = 'progress-bar bg-sfgreen';
            healthText.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Cliente Puntual.';
        } else if(score > 50) {
            healthBar.className = 'progress-bar bg-sfyellow text-dark';
            healthText.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Retrasos leves.';
        } else {
            healthBar.className = 'progress-bar bg-sfred';
            healthText.innerHTML = '<i class="bi bi-x-octagon-fill me-1"></i> Riesgo alto.';
        }
    
        // --- NUEVO: Widgets de Disponibilidad ---
        const statsContainer = clone.querySelector('#credit-usage-widget'); 
        if(statsContainer) {
            const limit = parseFloat(data.credit_limit) || 0;
            const used = parseFloat(data.aging.total_balance) || 0;
            const available = limit - used;
            const usedPercentage = limit > 0 ? Math.min((used / limit) * 100, 100) : 0;
    
            statsContainer.innerHTML = `
                <div class="d-flex justify-content-between mb-1 small fw-bold">
                    <span>Crédito Utilizado</span>
                    <span>${usedPercentage.toFixed(1)}%</span>
                </div>
                <div class="progress rounded-pill mb-2" style="height: 12px;">
                    <div class="progress-bar ${usedPercentage > 85 ? 'bg-danger' : 'bg-primary'}" 
                         style="width: ${usedPercentage}%"></div>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted text-truncate">Límite: ${toCurrency(limit)}</span>
                    <span class="fw-bold text-sfgreen text-truncate">Disp: ${toCurrency(available)}</span>
                </div>
            `;
        }
    
        // Insertamos el clon en el DOM antes de renderizar la lista interna
        container.appendChild(clone);
    
        // Renderizar solo los primeros 5 movimientos inicialmente
        this.renderMovementsList(data.recent_movements.slice(0, 5));
    },
    
    renderMovementsList: function(movements) {
        const list = document.getElementById('det-movements-list');
        if (!list) return;
    
        if(movements.length === 0) {
            list.innerHTML = '<div class="p-3 text-center text-muted small">Sin movimientos registrados</div>';
            return;
        }
    
        list.innerHTML = ''; // Limpiamos para renderizar (sea el top 5 o el total)
        movements.forEach(m => {
            const item = document.createElement('div');
            item.className = "d-flex justify-content-between align-items-center p-3 border-bottom";
            
            // Verificamos si está cancelado
            const isCancelled = (m.status === 'cancelled');
            const isCargo = (m.type === 'cargo');
            
            item.style.opacity = isCancelled ? '0.5' : '1';
            item.style.textDecoration = isCancelled ? 'line-through' : 'none';
    
            item.innerHTML = `
                <div>
                    <span class="small fw-bold text-uppercase">
                        ${isCargo ? 'Venta: ' : 'Abono: '} ${m.ref_id}
                        ${isCancelled ? '<span class="badge bg-light text-dark ms-1">CANCELADO</span>' : ''}
                    </span>
                    <small class="d-block text-muted" style="font-size:0.65rem">${m.date}</small>
                </div>
                <div>
                    <span class="fw-bold ${isCancelled ? 'text-muted' : (isCargo ? 'text-sfred' : 'text-sfgreen')}">
                        ${isCargo ? '+' : '-'}${toCurrency(m.amount)}
                    </span>
                    ${(isCancelled || isCargo) ? '<button class="btn btn-sm btn-secondary ms-3" disabled><i class="bi bi-shield-fill"></i></button>' : `<button class="btn btn-sm btn-outline-danger ms-3" title="Cancelar Abono" onclick="CreditApp.cancelPayment('${m.ref_id.split("-")[2]}')"><i class="bi bi-x-lg"></i></i></button>`}
                </div>
            `;
            list.appendChild(item);
        });
    },
    
    viewAllMovements: function() {
        if (!this.currentCustomerData) return;
        // Renderizamos la lista completa que ya tenemos en memoria
        this.renderMovementsList(this.currentCustomerData.recent_movements);
        
        // Opcional: esconder el botón de "Ver todos" para indicar que ya se muestran
        const btn = document.querySelector('button[onclick="CreditApp.viewAllMovements()"]');
        if(btn) btn.classList.add('d-none');
    },
    
    printStatement: function(data) {
        if (!data) {
            Swal.fire('Error', 'No hay datos del cliente para imprimir.', 'error');
            return;
        }
    
        // Preparamos el objeto exacto que espera IxeaUtils.printCustomerStatement
        const printData = {
            customer_name: data.full_name,
            tax_id: data.tax_id,
            // Si no tenemos fechas de filtro, usamos el rango de los movimientos cargados
            period_start: data.recent_movements.length > 0 
                          ? data.recent_movements[data.recent_movements.length - 1].date.substring(0, 10) 
                          : 'Inicio',
            period_end: new Date().toISOString().substring(0, 10),
            
            // El saldo anterior en este contexto sería el saldo total antes de los movimientos 
            // mostrados, pero para simplificar usamos 0 y dejamos que el historial sume el balance.
            // O puedes usar: parseFloat(data.aging.total_balance) - (cargos) + (abonos)
            previous_balance: 0, 
    
            history: data.recent_movements.map(m => ({
                date: m.date,
                type: m.type,
                amount: m.amount,
                ref: m.ref_id,
                status: m.status // Importante para la lógica de sumatoria e impresión
            })),
            
            pending: data.pending_tickets || []
        };
    
        console.log("[Credit] Generando Ticket de Estado de Cuenta...", printData);
        IxeaUtils.printCustomerStatement(printData);
    },

    // Modal de abono de a cuanta de crédito
    openPaymentModal: function() {
        const data = this.currentCustomerData;
        if (!data) return Swal.fire('Error', 'No hay cliente seleccionado.', 'error');
        
        IxeaStages.openModal({
            templateId: 'tpl-credit-payment',
            size: 'sm', // Cambiado a md para que se vea más centrado como el de caja
            onOpen: () => {
                document.getElementById('pay-cust-balance').innerText = `$${toCurrency(data.aging.total_balance)}`;
                
                // Pre-llenar el input con el saldo total por comodidad
                const inputAmount = document.querySelector('#form-customer-payment input[name="amount"]');
                if(inputAmount) inputAmount.value = data.aging.total_balance;
            }
        });
    },
    
    toggleReference: function() {
        const method = document.getElementById('credit-pay-method').value;
        const container = document.getElementById('credit-reference-container');
        const input = document.getElementById('credit-pay-reference');

        if (method === 'cash') {
            container.classList.add('d-none');
            input.value = ''; // Limpiar si cambia a efectivo
            input.required = false;
        } else {
            container.classList.remove('d-none');
            input.required = true; // Hacerlo obligatorio en el navegador
        }
    },

    savePayment: async function() {
        const btn = document.getElementById('credit-pay-btnPayment');
        if (!IxeaBouncer.lockBtn(btn)) return;
        
        try {
            const form = document.getElementById('form-customer-payment');
            const formData = new FormData(form);
            
            const body = {
                customer_id: this.currentCustomerId,
                amount: parseFloat(formData.get('amount')),
                method: formData.get('method'),
                reference: formData.get('reference')
            };
    
            // Validaciones locales
            if (!body.amount || body.amount <= 0) {
                Swal.fire('Atención', 'El monto debe ser mayor a 0', 'warning');
                return;
            }
            
            if (body.method !== 'cash' && (!body.reference || body.reference.trim() === '')) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Referencia requerida',
                    text: 'Ingresa la referencia del pago para métodos que no sean efectivo.'
                });
            }
    
            // Confirmación y Ejecución
            const result = await Swal.fire({
                title: '¿Confirmar Abono?',
                html: `Se aplicarán $${toCurrency(body.amount)} a la cuenta de<br><b>${this.currentCustomerData.full_name}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Revisar',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        // Importante: .php añadido a la ruta
                        const response = await fetch('modules/credit/credit-operations.php?action=complete_customer_payment', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(body)
                        });
    
                        const data = await response.json();
    
                        // Log de pasos del servidor (opcional para debug)
                        if (data.steps) {
                            data.steps.forEach(s => console.log(`Step: ${s.step} | Time: ${s.time}`));
                        }
    
                        if (!data.success) {
                            throw new Error(data.message || data.error || 'Error al procesar el pago');
                        }
    
                        return data;
                    } catch (error) {
                        Swal.showValidationMessage(`Error: ${error.message}`);
                    }
                }
            });
    
            // Respuesta final
            if (result.isConfirmed && result.value) {
                await Swal.fire('¡Pago Registrado!', result.value.message || 'El abono se aplicó correctamente.', 'success');
                
                // Acciones post-pago
                if (typeof IxeaStages !== 'undefined') IxeaStages.closeModal();
                
                await Promise.all([
                    this.loadCustomerDetails(this.currentCustomerId),
                    this.loadSummary()
                ]);
            }
    
        } catch (err) {
            console.error("Error en la conexión", err);
            Swal.fire('Error en la conexión', err.message, 'error');
        } finally {
            IxeaBouncer.releaseBtn(btn);
        }
    }
};