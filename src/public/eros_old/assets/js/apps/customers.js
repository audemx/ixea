window.CustomersApp = {
    table: null,
    isInitialized: false,

    init: function() {
        if (this.isInitialized) return;
        this.isInitialized = true;
        this.renderAppActions();
        this.initTable();
    },
    
    renderAppActions: function() {
        const menuActions = document.getElementById('menu-app-actions');
        if (!menuActions) return;
        menuActions.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">Operaciones</div>
                <ul class="dropdown-menu shadow border-0 mt-2">
                    <li><a class="dropdown-item small" onclick="CustomersApp.openCreateCustomer()">
                        <i class="bi bi-plus-circle me-2 text-sfblue"></i>Nuevo Cliente
                    </a></li>
                </ul>
            </div>
        `;
    },

    initTable: function() {
        // Configuración de DataTables con Server-side
        this.table = $('#tableCustomers').DataTable({
            scrollY: "70vh",
            scrollCollapse: true,
            processing: true,
            serverSide: true,
            pageLength: 25,
            order: [[1, 'asc']],
            ajax: {url: '/api/get-data?action=get_customers_datatables',type: 'POST'},
            columns: [
                { data: 'tax_id', className: 'fw-bold' },
                { data: 'full_name' },
                { data: 'email', className: 'text-muted', defaultContent: '<span class="text-muted small">-</span>' },
                { data: 'phone', className: 'text-muted', defaultContent: '<span class="text-muted small">-</span>' },
                { 
                    data: 'credit_status',
                    className: 'text-center',
                    render: (data) => {
                        const badges = {
                            'none': 'text-grey',
                            'approved': 'text-sfgren',
                            'suspended': 'text-sfred'
                        };
                        const creditNames = {
                            'none': 'Sin crédito',
                            'approved': 'Aprobado',
                            'suspended': 'Suspendido'
                        }
                        return `<span class="badge ${badges[data]} text-uppercase" style="font-size:0.7rem">${creditNames[data]}</span>`;
                    }
                },
                { 
                    data: 'status',
                    className: 'text-center',
                    render: (data) => {
                        const badges = {
                            'active': 'text-sfgreen',
                            'inactive': 'text-sfyellow',
                            'suspended': 'text-sfred'
                        };
                        const statusNames = {
                            'active': 'Activo',
                            'inactive': 'Inactivo',
                            'suspended': 'Suspendido'
                        };
                        return `<span class="badge ${badges[data]} text-uppercase" style="font-size:0.7rem">${statusNames[data]}</span>`;
                    }
                },
                {
                    data: 'customer_id',
                    className: 'text-center',
                    orderable: false,
                    render: (data, type, row) => `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-sfblue" onclick="CustomersApp.editCustomer('${data}')" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>`
                }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            dom: 'frt<"d-flex justify-content-between align-items-center small"ip>',
            initComplete: function() {
                $('.dataTables_filter label').contents().filter(function() {
                    return this.nodeType === 3; 
                }).remove();
                
                $('.dataTables_filter input')
                    .addClass('form-control form-control-sm border-0 shadow-sm')
                    .attr('placeholder', 'Buscar cliente...');
            },
            drawCallback: function() {
                $('.dataTables_info, .dataTables_paginate').addClass('small');
                $('.pagination').addClass('pagination-sm'); 
            }
        });
    }
}