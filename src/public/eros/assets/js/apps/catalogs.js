/** assets/js/apps/catalogs.js **/
window.CatalogsApp = {
    isInitialized: false,
    importConfig: {
        categories: {
            title: "Categorías",
            columns: ["Nombre*", "Descripción"],
            example: "HERRAMIENTAS,Artículos manuales y eléctricos",
            csvHeader: "Nombre,Descripción"
        },
        brands: {
            title: "Marcas",
            columns: ["Nombre*"],
            example: "TRUPER",
            csvHeader: "Nombre"
        },
        units: {
            title: "Unidades de Medida",
            columns: ["Código* (Máx 3 caracteres)", "Nombre*"],
            example: "PZA,PIEZA",
            csvHeader: "Código Unidad,Nombre"
        },
        products: {
            title: "Catálogo de Productos",
            columns: ["SKU*", "Nombre*", "Categoría*", "Marca*", "IVA (1/0)*", "Stock Actual", "Mínimo", "Máximo"],
            example: "TRU-100,Martillo 16oz,HERRAMIENTAS,TRUPER,1,10.00,5.00,20.00",
            csvHeader: "sku,Nombre,Categoría,Marca,IVA,Stock Actual,Stock Mínimo,Stock Máximo",
            note: "¡Atención! Los nombres de Categoría y Marca deben coincidir exactamente con los registrados en sus catálogos."
        },
        sale_units: {
            title: "Precios de Venta",
            columns: ["SKU Producto*", "Código Unidad*", "Factor Conversión", "Precio Venta"],
            example: "ACE-M0001,PZA,1,26.00",
            csvHeader: "sku,código unidad,factor de conversión,precio de venta",
            note: "Define cómo se vende el producto."
        },
        purchase_units: {
            title: "Costos de Compra",
            columns: ["SKU Producto*", "Código Unidad*", "Factor Conversión", "Precio Compra", "Proveedor (RFC)"],
            example: "ACE-M0001,PZA,1,20.50,XAXX010101000",
            csvHeader: "sku,código unidad,factor de conversión,precio de compra,proveedor",
            note: "Define cómo se le compra al proveedor."
        },
        clients: {
            title: "Clientes",
            columns: [
                "RFC/ID*", "Nombre Completo*", "Email", "Teléfono", "Fecha Nac (YYYY-MM-DD)", "Género (male/female/other)",
                "Edo. Cliente (active/inactive)", "Edo. Crédito (none/approved)", "Razón Social", "Email Facturación",
                "Calle", "Num Ext", "Num Int", "Colonia", "Ciudad", "Estado", "CP"
            ],
            example: "XAXX010101000,Público General,ventas@ejemplo.com,5551234567,1990-01-01,other,active,none,Público General SA,,Av. Central,10,,Centro,Nezahualcóyotl,Edomex,57000",
            csvHeader: "tax_id,full_name,email,phone,birth_date,gender,status,credit_status,company_name,billing_email,address_street,address_ext_num,address_int_num,address_neighborhood,address_city,address_state,address_zip_code",
            note: "El RFC/ID y el Nombre Completo son obligatorios. El formato de fecha debe ser AAAA-MM-DD."
        },
         customer_credit_profiles: {
            title: "Perfiles de Crédito (Clientes)",
            columns: ["RFC Cliente*", "Límite de Crédito", "Días de Crédito", "Score Actual (0-100)"],
            example: "XAXX010101000,5000.00,30,85",
            csvHeader: "tax_id,credit_limit,credit_days,current_score",
            note: "El cliente debe existir previamente. Si el perfil ya existe, se actualizarán los montos y plazos."
        },
        suppliers: {
            title: "Proveedores",
            columns: [
                "RFC*", "Nombre Empresa*", "Nombre Comercial", "Régimen Fiscal", "Email Facturación",
                "Calle", "Num Ext", "Num Int", "Colonia", "Municipio/Ciudad", "Estado", "CP",
                "Nombre Contacto", "Email Contacto", "Teléfono Contacto", "Días Entrega", "Días Crédito", "Límite Crédito",
                "Banco", "Beneficiario", "Cuenta", "CLABE"
            ],
            example: "PROV123456ABC,Ferretera Central SA,El Centro del Tornillo,601,facturas@ferrecentral.com,Av. Principal,100,,Centro,Nezahualcóyotl,Edomex,57000,Juan Pérez,juan@prov.com,5551234567,3,15,50000,BBVA,Ferretera Central,0123456789,012345678901234567",
            csvHeader: "tax_id,company_name,commercial_name,tax_regime,billing_email,address_street,address_ext_num,address_int_num,address_neighborhood,address_city,address_state,address_zip_code,contact_name,contact_email,contact_phone,delivery_days,credit_days,credit_limit,bank_name,bank_beneficiary,bank_account,bank_clabe",
            note: "El RFC y Nombre de Empresa son obligatorios. Los campos vacíos se guardarán como NULL."
        },
        sales: {
            title: "Historial de Ventas",
            columns: [
                "Folio*", "Fecha Op.*", "Vendedor (RFC)*", "Cliente (RFC)", "Tipo Op.", 
                "Total*", "Num Items*", "Fiscal (1/0)", "Edo. Pago", "Forma Pago", 
                "Ref. Pago", "Fecha Pago", "Cajero (RFC)", "Edo. Despacho", 
                "Fecha Entrega", "Despachador (RFC)"
            ],
            example: "V-251202-001,2025-12-02 08:59:13,PISC570901DON,XAXX010101000,venta,269,1,0,pagado,efectivo,500,2025-12-02 09:03:33,AOMI810522VA4,entregado,2025-12-02 10:09:07,TEGS920411S10",
            csvHeader: "folio,fecha_op,vendedor_rfc,cliente_rfc,tipo,total,items,fiscal,edo_pago,forma_pago,ref,fecha_pago,cajero_rfc,edo_despacho,fecha_entrega,despachador_rfc",
            note: "Asegúrese de que los RFC de Vendedor, Cajero y Despachador existan en la tabla de usuarios."
        },
        sale_details: {
            title: "Detalle de Ventas",
            columns: ["Folio*", "No. Item*", "SKU Producto*", "Cantidad*", "Unidad*", "Precio Unitario*", "Subtotal*"],
            example: "V-251202-001,1,EE-0970.30,1,LTR,269.00,269.00",
            csvHeader: "folio,item_order,sku,quantity,unit_code,unit_price,subtotal",
            note: "El Folio debe existir en el historial de ventas. El impuesto (16%) se calculará automáticamente si el producto está marcado como gravable."
        },
        import_credits: {
            title: "Migración de Pagarés (Créditos)",
            columns: ["RFC Cliente*", "Folio Venta*", "Monto Total*", "Saldo Restante*", "Estatus*", "Fecha Creación*"],
            example: "XAXX010101000,V-251114-043,1091.50,1091.50,pending,2025-11-14 12:00:00",
            csvHeader: "customer_rfc,sale_folio,total_amount,remaining_balance,status,created_at",
            note: "Carga las deudas históricas. Si el saldo restante es 0, el estatus debe ser 'paid'."
        },
        import_payments: {
            title: "Migración de Abonos (Pagos)",
            columns: ["RFC Cliente*", "Monto*", "Método*", "Referencia*", "Fecha Pago*"],
            example: "BERALIJIMFLO,1174.00,cash,Abono: $1,174.00,2025-10-17 12:00:00",
            csvHeader: "customer_rfc,amount,method,reference,created_at",
            note: "Carga los pagos realizados. El sistema aplicará automáticamente estos montos a las deudas pendientes por fecha (FIFO)."
        }
    },

    init: function() {
        if (this.isInitialized) return;
        this.isInitialized = true;
        this.updateImportInfo();
        this.bindEvents();
        this.loadList('categories');
    },

    bindEvents: function() {
        $('#form-import-catalogs').on('submit', (e) => this.handleImport(e));
    },

    loadList: async function(table) {
        const res = await fetch(`/api/get-data.php?action=get_catalog&table=${table}`);
        const data = await res.json();
        const container = document.getElementById('list-' + table);
        
        if (data.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center p-5 opacity-50">
                    <i class="bi bi-folder2-open fs-1"></i>
                    <p>No hay registros en este catálogo</p>
                </div>`;
            return;
        }
        
        container.innerHTML = data.map(item => `
            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            ${table == 'units' ? `<div class="fw-bold text-primary">${item.id}</div>` : ''}
                            <div class="${table == 'units' ? 'x-small text-muted' : 'fw-bold text-primary'}">${item.name}</div>
                            ${table == 'categories' ? `<div class="x-small text-muted">${item.descript || 'Sin descripción'}</div>` : ''}
                        </div>
                        <button class="btn btn-link text-danger p-0" onclick="CatalogsApp.deleteItem('${table}', '${item.id}')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            </div>`).join('');
    },
    
    loadTypes: function() {
        const selectTypes = document.getElementById('import_type');
        selectTypes.innerHTML = ''; // Limpiamos
        
        for (const [key, value] of Object.entries(this.importConfig)) {
            const option = document.createElement('option');
            option.value = key;
            option.innerText = value.title;
            selectTypes.appendChild(option);
        }
        // Disparamos la actualización de la interfaz para el primer elemento
        this.updateImportInfo();
    },

    handleImport: function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        Swal.fire({
            title: 'Procesando...',
            text: 'Ixea EROS está validando la integridad del archivo',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: '/modules/admin/catalog-management',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (resp) => {
                $('#import-results').html(resp);
                Swal.fire('Proceso Finalizado', 'Revisa los cambios realizados', 'success');
            }
        });
    },

    updateImportInfo: function() {
        const type = document.getElementById('import_type').value;
        const config = this.importConfig[type];
        const container = document.getElementById('import-instructions');
    
        if (!config) {
            container.innerHTML = "";
            return;
        }
    
        let html = `
            <div class="alert alert-warning py-2 border-0 bg-warning bg-opacity-10 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Importante:</strong> La primera fila del archivo se ignora (cabeceras).
            </div>
            <h6 class="fw-bold mb-2 text-dark">${config.title}</h6>
            <p class="mb-2 text-muted x-small">El archivo debe contener exactamente estas columnas en este orden:</p>
            <div class="d-flex flex-wrap gap-1 mb-3">
                ${config.columns.map((col, idx) => `
                    <span class="badge bg-white text-dark border fw-normal">
                        <span class="text-primary fw-bold">${idx + 1}.</span> ${col}
                    </span>
                `).join('')}
            </div>
            <p class="mb-1 text-muted x-small">Ejemplo de una fila de datos:</p>
            <code class="d-block p-2 bg-dark text-light rounded x-small border-0 mb-2" style="font-family: monospace;">
                ${config.example}
            </code>
            ${config.note ? `<p class="text-danger x-small mb-0"><i class="bi bi-info-circle me-1"></i>${config.note}</p>` : ''}
        `;
    
        container.innerHTML = html;
    },
    
    downloadTemplate: function() {
        const type = document.getElementById('import_type').value;
        const config = this.importConfig[type];
    
        if (!config) return;
    
        // Creamos el contenido del CSV (Cabecera + Ejemplo)
        const csvContent = config.csvHeader + "\n" + config.example;
        
        // Proceso de descarga
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        
        link.setAttribute("href", url);
        link.setAttribute("download", `plantilla_ixea_${type}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};