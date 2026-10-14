window.BistroPosApp = {
    // ==========================================
    // Variables de Estado
    // ==========================================

    // Global
    jsName: 'bistro-pos',
    isInitialized: false,

    // Estado
    activeDraftItem: null, // Item activo en el carrito. Es un objeto con las propiedades: item_id, quantity, name, unit_price, subtotal, notes, modifiers
    currentUser: IxeaUser.userId, // ID del usuario activo o mesero seleccionado.
    currentCategory: null, // ID de la categoría seleccionada.
    currentTable: null, // ID de la mesa seleccionada. Es -1 si es temporal.
    tableName: null, // Nombre de la mesa seleccionada.
    currentPerson: null, // Número de persona seleccionada.
    currentChannel: null, // ID de canal seleccionado en modal de nueva orden.
    currentArea: 'all', // 'all', ID de área, o 'temporal'. Indica el área a mostrar en el listado de mesas.
    currentOrder: null, // ID de la orden activa que se está editando.

    // Data
    categories: [], // Array de categorías con formato {id, name, description, emoji, sort, status_id}
    menu: [], // Array de platillos con formato {id, name, description, category_id, station_id, price, prep_time}
    modifiers: [], // Array de modificadores con formato {id, name, description, price}
    tables: [], // Array de mesas con formato {id, name, area_id, capacity, pos_x, pos_y, size, orientation, use_status_id, status_id}
    cart: [], // Array de items en el carrito con formato: {item_id, quantity, name, unit_price, subtotal, notes, modifiers}
    personCount: 0, // Número de personas en la orden.
    swipeListeners: [], // Array de listeners de eventos de swipe.
    areas: [], // Array de áreas con formato {id, name, description, emoji, sort, status_id}
    orders: [], // Array de órdenes con formato {id, name, description, emoji, sort, status_id}


    // ==========================================
    // Funciones de Inicialización
    // ==========================================
    init: function () {
        if (this.isInitialized) return;
        this.isInitialized = true;
        this.initCartSwipeListener();

        // Ahora pasas un objeto limpio y legible
        this.fetchMenu({ forceSync: true });
        this.fetchModifiers({ forceSync: true });
        this.fetchCategories({ forceSync: true });
        this.fetchTables({ forceSync: true });
        this.fetchAreas({ forceSync: true });
        this.fetchOrders({ forceSync: true });
    },

    /** Menu de operaciones de la Pos */
    renderAppActions: function (activeOrder) {
        const menuActions = document.getElementById('menu-app-actions');
        if (!menuActions) return;

        // Si hay una orden activa, mostrar opciones de edición
        if (activeOrder) {
            menuActions.innerHTML = `
                <div class="dropdown">
                    <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">Operaciones</div>
                    <ul class="dropdown-menu shadow border-0 mt-2 bg-slate-800 text-white">
                        <li>
                            <a class="dropdown-item small cursor-pointer" onclick="BistroPosApp.editCurrentOrder(true)">
                                <i class="bi bi-pencil me-2 text-sfgreen"></i>Editar Orden
                            </a>
                        </li>
                    </ul>
                </div>
            `;
        } else {
            menuActions.innerHTML = '';
        }
    },

    /**
     * Inicializa los listeners mediante delegación de eventos
     * Si el usuario desliza el dedo hacia la izquierda más de 35px, se muestra el botón de eliminar
     * Si el usuario desliza el dedo hacia la derecha menos de 35px, se oculta el botón de eliminar
     * Si el usuario desliza hacia arriba o abajo más de 35px, domina el scroll.
     * Si el usuario no mueve el dedo más de 35px en ninguna dirección, se abre el modal de modificación.
     */
    initCartSwipeListener: function () {
        const cartEl = document.getElementById('bistro-pos-cart');
        if (!cartEl) return;

        let activeItem = null;
        let startX = 0;
        let startY = 0;
        let currentX = 0;
        let currentY = 0;
        let isSwiping = false;
        let hasMoved = false;

        const handleStart = (e) => {
            const touch = e.touches ? e.touches[0] : e;
            const item = e.target.closest('.swipe-item');
            if (!item) return;

            if (activeItem && activeItem !== item) {
                activeItem.style.transform = 'translateX(0px)';
            }

            activeItem = item;
            startX = touch.clientX;
            startY = touch.clientY;
            currentX = touch.clientX;
            currentY = touch.clientY;
            isSwiping = true;
            hasMoved = false;
        };

        const handleMove = (e) => {
            if (!isSwiping || !activeItem) return;
            const touch = e.touches ? e.touches[0] : e;
            currentX = touch.clientX;
            currentY = touch.clientY;

            const diffX = startX - currentX;
            const diffY = Math.abs(startY - currentY);

            // Si el movimiento vertical es mayor que el horizontal, el usuario está haciendo SCROLL
            if (diffY > Math.abs(diffX) && diffY > 8) {
                isSwiping = false;
                activeItem.style.transform = 'translateX(0px)';
                return;
            }

            // Marcar que hubo desplazamiento significativo (evita falsos clics)
            if (Math.abs(diffX) > 8 || diffY > 8) {
                hasMoved = true;
            }

            if (diffX > 0 && diffX <= 70) {
                activeItem.style.transform = `translateX(-${diffX}px)`;
            }
        };

        const handleEnd = (e) => {
            if (!isSwiping || !activeItem) return;
            isSwiping = false;

            const diffX = startX - currentX;
            const diffY = Math.abs(startY - currentY);

            if (diffX > 35) {
                activeItem.style.transform = 'translateX(-64px)';
            } else {
                activeItem.style.transform = 'translateX(0px)';

                // Solo abre/modifica el ítem si NO hubo intención de scroll ni de swipe
                if (!hasMoved && Math.abs(diffX) < 8 && diffY < 8) {
                    const index = parseInt(activeItem.dataset.index);
                    if (!isNaN(index)) {
                        this.modifyItem(index);
                    }
                }
            }
        };

        const handleMouseLeave = () => {
            if (isSwiping && activeItem) {
                isSwiping = false;
                activeItem.style.transform = 'translateX(0px)';
            }
        };

        this.swipeListeners = [
            { target: cartEl, type: 'touchstart', handler: handleStart, options: { passive: true } },
            { target: cartEl, type: 'touchmove', handler: handleMove, options: { passive: true } },
            { target: cartEl, type: 'touchend', handler: handleEnd, options: false },
            { target: cartEl, type: 'mousedown', handler: handleStart, options: false },
            { target: cartEl, type: 'mousemove', handler: handleMove, options: false },
            { target: cartEl, type: 'mouseup', handler: handleEnd, options: false },
            { target: cartEl, type: 'mouseleave', handler: handleMouseLeave, options: false }
        ];

        this.swipeListeners.forEach(listener => {
            listener.target.addEventListener(listener.type, listener.handler, listener.options);
        });
    },

    // ==========================================
    // LLAMADAS GET
    // ==========================================
    fetchMenu: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-pos", "get-menu", { cacheOnly, forceSync });
            if (!res.success) throw new Error(res.message);
            if ((res.changed || cacheOnly) && res.data) {
                this.menu = res.data;
                this.renderMenu();
            }
        } catch (err) {
            console.error("[POS] Error al obtener productos:", err);
        }
    },

    fetchModifiers: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-pos", "get-modifiers", { cacheOnly, forceSync });
            if (!res.success) throw new Error(res.message);
            if ((res.changed || cacheOnly) && res.data) {
                this.modifiers = res.data;
            }
        } catch (err) {
            console.error("[POS] Error al obtener modificadores:", err);
        }
    },

    fetchCategories: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-pos", "get-categories", { cacheOnly, forceSync });
            if (!res.success) throw new Error(res.message);
            if ((res.changed || cacheOnly) && res.data) {
                this.categories = res.data;
                this.renderCategories();
            }
        } catch (err) {
            console.error("[POS] Error al obtener categorías:", err);
        }
    },

    fetchTables: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-pos", "get-tables", { cacheOnly, forceSync });
            if (!res.success) throw new Error(res.message);
            if ((res.changed || cacheOnly) && res.data) {
                this.tables = res.data;
            }
            this.renderTables();
        } catch (err) {
            console.error("[POS] Error al obtener mesas:", err);
        }
    },

    fetchAreas: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-pos", "get-areas", { cacheOnly, forceSync });
            if (res.success && res.data) {
                this.areas = res.data;
                this.renderAreas();
            }
        } catch (err) {
            console.error("[POS] Error al obtener áreas:", err);
        }
    },

    fetchOrders: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("bistro-pos", "get-orders", { cacheOnly, forceSync });
            if (res.success && res.data) {
                this.orders = res.data;
            }
        } catch (err) {
            console.error("[POS] Error al obtener órdenes:", err);
        }
    },

    // ==========================================
    // Cambio de Mesero
    // ==========================================
    switchWaiter: function () {
        IxeaBouncer.permission = 'can_waiter';
        IxeaBouncer.onAuth = (data) => {
            if (data.user) {
                // Actualiza estado interno y UI
                this.currentUser = data.user.id;

                const userLabel = document.getElementById('bistro-pos-user');
                if (userLabel) {
                    userLabel.innerText = data.user.name;
                }
            }
        };
        IxeaComponents.openModal('auth', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-80'
        });
    },

    // ==========================================
    // Renderizado de Areas
    // ==========================================
    renderAreas: function () {
        const container = document.getElementById('bistro-pos-areas');
        if (!container) return;

        container.innerHTML = `
            <button onclick="BistroPosApp.selectArea('all')" class="px-2 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition ${this.currentArea === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-300 border border-slate-700'}">
                Todas
            </button>
        `;

        this.areas.forEach(area => {
            const isActive = this.currentArea === area.id;
            const btn = document.createElement('button');
            btn.onclick = () => this.selectArea(area.id);
            btn.className = `px-2 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition ${isActive ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-300 border border-slate-700'}`;
            btn.textContent = area.name;
            container.appendChild(btn);
        });

        const temporalButton = document.createElement('button');
        temporalButton.onclick = () => this.selectArea('temporal');
        temporalButton.className = `px-2 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition ${this.currentArea === 'temporal' ? 'bg-amber-600 text-white' : 'bg-slate-900 text-amber-400 border border-slate-700'}`;
        temporalButton.textContent = 'Temporal';
        container.appendChild(temporalButton);
    },

    /** Selecciona un área y renderiza el mapa */
    selectArea: function (areaId = 'all') {
        this.currentArea = areaId;
        this.renderTables();
        this.renderAreas();
    },

    // ==========================================
    // Gestión de Categorías
    // ==========================================
    renderCategories: function () {
        const container = document.getElementById('bistro-pos-categories');
        if (!container) return;

        container.innerHTML = '';
        this.categories.forEach(category => {
            const button = document.createElement('button');
            button.id = `bistro-pos-category-${category.id}`;
            button.className = 'bg-slate-800 text-slate-200 px-2 py-1 rounded-xl whitespace-nowrap border border-slate-700';
            button.textContent = `${category.emoji} ${category.name}`;
            button.onclick = () => this.selectCategory(category.id);
            container.appendChild(button);
        });
    },

    selectCategory: function (id) {
        const container = document.getElementById('bistro-pos-categories');
        container.querySelectorAll('button').forEach(button => {
            button.className = 'bg-slate-800 text-slate-200 px-2 py-1 rounded-xl whitespace-nowrap border border-slate-700';
        });

        if (id) {
            this.currentCategory = this.categories.find(category => category.id === id);
            const button = document.getElementById(`bistro-pos-category-${id}`);
            button.className = 'bg-indigo-600 text-white px-2 py-1 rounded-xl font-bold whitespace-nowrap shadow';
        } else {
            this.currentCategory = null;
        }
        this.renderMenu();
    },

    // ==========================================
    // Gestión de Mesas
    // ==========================================
    toggleTablesDrawer: function () {
        const overlay = document.getElementById('bistro-pos-drawer-overlay');
        const drawer = document.getElementById('bistro-pos-drawer');
        if (!drawer || !overlay) return;

        // 1. Verificar si está cerrado ANTES de alternar la clase
        const isOpening = drawer.classList.contains('-translate-x-full');

        // 2. Alternar clases
        drawer.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');

        // 3. Ejecutar la acción si se estaba abriendo
        if (isOpening) {
            this.fetchTables();
        }
    },

    renderTables: function () {
        const template = document.getElementById('bistro-pos-template-table');
        const container = document.getElementById('bistro-pos-tables');

        container.innerHTML = '';

        const areaId = this.currentArea;
        let filterTables = this.tables;
        if (areaId === 'temporal') {
            filterTables = this.orders.filter(order => order.table_id === -1);
        } else if (areaId !== 'all') {
            filterTables = this.tables.filter(table => table.area_id === areaId);
        }

        filterTables.forEach(table => {
            let tableId = table.id;
            let orderId = table.order_id;
            if (areaId === 'temporal') {
                tableId = -1;
                orderId = table.id;
            } else {
                tableId = table.id;
                orderId = table.order_id;
            }
            // 1. Clonar el contenido del template (true asegura clonar los hijos)
            const clone = template.content.cloneNode(true);

            // 2. Obtener los elementos internos del clon
            const button = clone.querySelector('.bistro-pos-table-btn');
            const nameSpan = clone.querySelector('.bistro-pos-table-name');
            const iconSpan = clone.querySelector('.bistro-pos-table-icon');
            const badgeSpan = clone.querySelector('.bistro-pos-table-status-badge');

            // 3. Rellenar los datos básicos
            nameSpan.textContent = table.name;
            button.setAttribute('onclick', `BistroPosApp.selectTable(${tableId}, ${orderId ?? null})`); // Si la mesa no está abierta, no tiene orden. Por lo tanto es null
            button.dataset.orderId = orderId ?? null;

            // 4. Aplicar la lógica condicional
            if (table.status === 'open') {
                button.classList.add('border-emerald-500', 'hover:border-emerald-500', 'border-emerald-500/40');
                iconSpan.textContent = '🪑';
                badgeSpan.textContent = 'Libre';
                badgeSpan.classList.add('text-emerald-400', 'bg-emerald-500/10');
            } else if (table.status === 'pending') {
                button.classList.add('border-amber-500', 'hover:border-amber-500', 'border-amber-500/40');
                iconSpan.textContent = '🧾';
                badgeSpan.textContent = 'Cuenta';
                badgeSpan.classList.add('text-amber-400', 'bg-amber-500/10');
            } else {
                button.classList.add('border-rose-500', 'hover:border-rose-500', 'border-rose-500/40');
                iconSpan.textContent = '👥';
                badgeSpan.textContent = 'Ocupada';
                badgeSpan.classList.add('text-rose-400', 'bg-rose-500/10');
            }

            // 5. Inyectar el clon en el contenedor del DOM
            container.appendChild(clone);
        });
    },

    selectTable: function (id = null, orderId = null) {
        // Limpiar la selección
        if (id === null && orderId === null) {
            this.currentTable = null;
            this.currentOrder = null;
            this.currentChannel = null;
            this.tableName = null;
            this.personCount = 0;
            const labelEl = document.getElementById('bistro-pos-table');
            if (labelEl) labelEl.innerText = "Seleccionar Mesa";
            const peopleEl = document.getElementById('bistro-pos-people');
            if (peopleEl) peopleEl.innerHTML = '';
            this.selectCategory(null);
            this.cleanCart();
            this.renderAppActions(false);
            return;
        }

        if (this.currentTable === id && this.currentOrder === orderId) {
            this.toggleTablesDrawer();
            return;
        }

        const renderTable = () => {
            const table = this.tables.find(t => t.id === id);
            const order = this.orders.find(o => o.id === orderId);

            this.currentTable = id;
            this.currentOrder = orderId;

            // Obtener el canal de la orden o de la mesa de forma segura
            this.currentChannel = order?.channel_id ?? table?.channel_id ?? null;

            // Obtener el nombre priorizando la mesa o el nombre de la orden temporal
            this.tableName = table?.name ?? order?.name ?? 'Cliente';

            this.toggleTablesDrawer();
            this.renderPeople();
            this.cleanCart();

            const activeOrder = orderId !== null || (table && table.status === 'closed');
            this.renderAppActions(activeOrder);

            const labelEl = document.getElementById('bistro-pos-table');
            if (labelEl) labelEl.innerText = this.tableName;
        };

        if (this.cart.length > 0) {
            IxeaComponents.showConfirm({
                text: '¿Seguro que quieres cambiar de mesa/cuenta? Se borrará la comanda actual.',
                icon: 'warning',
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar',
                onConfirm: () => {
                    renderTable();
                }
            });
            return;
        }
        renderTable();
    },

    // ==========================================
    // Cuentas temporales
    // ==========================================

    /** Abrir modal para crear cuenta temporal */
    openTemporaryTable: function () {
        this.currentChannel = null;
        IxeaComponents.openModal('order', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-full max-w-lg',
            onOpen: () => {
                const nameInput = document.getElementById('bistro-pos-customer-name');
                if (nameInput) nameInput.focus();
            }
        });
    },

    /** Selección de canal de atención */
    selectChannel: function (btn) {
        document.querySelectorAll('.channel-btn').forEach(b => {
            b.className = "channel-btn flex flex-col items-center justify-center p-3 bg-slate-900 border border-slate-700 hover:border-blue-500 rounded-xl transition cursor-pointer group h-24";
        });
        btn.className = "channel-btn flex flex-col items-center justify-center p-3 bg-indigo-600 border border-indigo-400 rounded-xl transition cursor-pointer group h-24 shadow-md";
        this.currentChannel = parseInt(btn.dataset.id);
    },

    confirmTemporaryOrder: async function () {
        const input = document.getElementById('bistro-pos-customer-name');
        const customerName = input ? input.value.trim() : '';
        const waiter = this.currentUser;

        if (!this.currentChannel) {
            IxeaComponents.showAlert({ text: 'Seleccione un canal de atención.', icon: 'warning' });
            return;
        }
        if (!customerName) {
            IxeaComponents.showAlert({ text: 'Ingrese el nombre del cliente.', icon: 'warning' });
            return;
        }

        IxeaComponents.showLoading({ text: 'Abriendo cuenta...' });

        try {
            const res = await IxeaBridge.post('/api/v1/bistro-pos/open-temporal', {
                channel_id: this.currentChannel,
                customer_name: customerName,
                waiter_id: waiter
            });

            IxeaComponents.hideLoading();

            if (res.success) {
                this.orders.unshift(res.data);
                this.selectTable(-1, res.data.id);
                IxeaComponents.closeModal();
                this.renderAppActions(true);
                IxeaComponents.showAlert({
                    icon: 'success',
                    text: 'Cuenta abierta correctamente.',
                    timer: 1500
                });
            } else {
                console.error(res.message);
                IxeaComponents.showAlert({
                    icon: 'error',
                    text: res.message || 'Error al abrir cuenta.'
                });
            }
        } catch (err) {
            IxeaComponents.hideLoading();
            console.error(err);
            IxeaComponents.showAlert({
                icon: 'error',
                text: 'Error de conexión, intente nuevamente.'
            });
        }
    },

    // ==========================================
    // Gestión de Comensales
    // ==========================================
    renderPeople: function () {
        const peopleEl = document.getElementById('bistro-pos-people');
        if (!peopleEl) return;
        peopleEl.innerHTML = '';

        const table = this.tables.find(table => table.id === this.currentTable);
        const count = table ? table.count : 0;
        this.personCount = count;

        // Agregar comensal centro
        const btnCenter = document.createElement('button');
        btnCenter.id = `bistro-pos-p0`;
        btnCenter.onclick = () => this.selectPerson(0);
        btnCenter.textContent = '🍽️ Centro';
        peopleEl.appendChild(btnCenter);

        for (let i = 1; i <= this.personCount; i++) {
            const button = document.createElement('button');
            button.id = `bistro-pos-p${i}`;
            button.onclick = () => this.selectPerson(i);
            button.textContent = `👤 P${i}`;
            peopleEl.appendChild(button);
        }

        // Agregar botón de personas
        const btnAddPerson = document.createElement('button');
        btnAddPerson.id = `bistro-pos-pAdd`;
        btnAddPerson.onclick = () => this.addPerson();
        btnAddPerson.className = 'bg-slate-900 hover:bg-slate-700 text-indigo-400 font-bold px-2.5 py-1.5 rounded-xl text-xs border border-slate-700 flex items-center space-x-1 flex-shrink-0 transition';
        btnAddPerson.textContent = '+ Persona';
        peopleEl.appendChild(btnAddPerson);

        this.selectPerson(0);
    },

    selectPerson: function (id) {
        const peopleEl = document.getElementById('bistro-pos-people');
        if (!peopleEl) return;

        this.currentPerson = id;

        peopleEl.querySelectorAll('button').forEach(btn => {
            btn.className = 'bg-slate-900 hover:bg-slate-700 text-indigo-400 font-bold px-2.5 py-1.5 rounded-xl text-xs border border-slate-700 flex items-center space-x-1 flex-shrink-0 transition';
        });

        const activeBtn = document.getElementById(`bistro-pos-p${id}`);
        if (activeBtn) {
            activeBtn.className = 'bg-indigo-600 text-white font-bold px-2.5 py-1.5 rounded-xl text-xs whitespace-nowrap border border-indigo-400 flex items-center space-x-1 flex-shrink-0 shadow';
        }
    },

    addPerson: function () {
        if (!this.currentTable) {
            IxeaComponents.showAlert({
                text: 'Por favor seleccione una mesa.',
                icon: 'warning'
            });
            return;
        }

        this.personCount++;
        const personId = this.personCount;

        const addBtn = document.getElementById('bistro-pos-pAdd');
        if (!addBtn) return;

        const newPersonBtn = document.createElement('button');
        newPersonBtn.id = `bistro-pos-p${personId}`;
        newPersonBtn.onclick = () => this.selectPerson(personId);
        newPersonBtn.className = 'bg-slate-900 hover:bg-slate-700 text-indigo-400 font-bold px-2.5 py-1.5 rounded-xl text-xs border border-slate-700 flex items-center space-x-1 flex-shrink-0 transition';
        newPersonBtn.textContent = `👤 P${personId}`;

        addBtn.parentNode.insertBefore(newPersonBtn, addBtn);
        this.selectPerson(personId);
    },

    // ==========================================
    // Gestión de Productos
    // ==========================================
    /**
     * Renderiza el menú según la selección de categoría
     */
    renderMenu: function () {
        const container = document.getElementById('bistro-pos-menu');
        container.innerHTML = '';
        if (!this.currentCategory) return;

        const menuFiltered = this.menu.filter(product => product.category_id === this.currentCategory.id);
        menuFiltered.forEach(product => {
            const button = document.createElement('button');
            button.id = `bistro-pos-product-${product.id}`;
            button.className = 'bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex flex-col justify-between active:scale-95 transition cursor-pointer hover:border-emerald-500';
            button.innerHTML = `
                <h2 class="font-extrabold text-xl text-slate-100">${product.name}</h2>
                <p class="font-black text-emerald-400 text-base mt-2">$ ${product.price}</p>
            `;
            button.onclick = () => this.selectProduct(product.id);
            container.appendChild(button);
        });
    },

    /**
     * Limpia el carrito
     */
    cleanCart: function () {
        this.cart = [];
        this.renderCart();
    },

    /**
     * Elimina un producto del carrito directamente
     */
    removeItem: function (index) {
        if (index < 0 || index >= this.cart.length) return;
        this.cart.splice(index, 1);
        this.renderCart();
    },

    /**
     * Punto de entrada al hacer clic en un producto del menú
     */
    selectProduct: function (id) {
        if (!this.currentTable) {
            IxeaComponents.showAlert({
                text: 'Por favor seleccione una mesa.',
                icon: 'warning'
            });
            return;
        }

        const product = this.menu.find(p => p.id === id);
        if (!product) return;

        // Obtenemos todos los objetos de grupos pertenecientes a este producto
        const groups = (product.groups || [])
            .map(groupId => this.modifiers.find(g => g.id === groupId))
            .filter(Boolean);

        // Verificamos si requiere modal: Si tiene grupos o si alguno es obligatorio
        const hasModifiers = groups.length > 0;
        const hasRequired = groups.some(g => g.required);

        // Creamos el borrador del item
        this.activeDraftItem = {
            created_at: Date.now(),
            product_id: product.id,
            name: product.name,
            price: product.price,
            qty: 1,
            target: this.currentPerson,
            modifiers: [], // Guardará los objetos de modificadores seleccionados
            notes: ""
        };

        if (hasModifiers && hasRequired) {
            // Lanza el modal para configurar
            this.openModifiers(groups);
        } else {
            // Si no, lo agrega directo al carrito
            this.commitDraftToCart();
        }
    },

    /**
     * Modificar item seleccionado en el carrito
     */
    modifyItem: function (index) {
        const item = this.cart[index];
        if (!item) return;

        // Buscar la información original del producto desde el menú
        const product = this.menu.find(p => p.id === item.product_id);
        if (!product) return;

        // Extraer los grupos de modificadores del producto (product.modifier_groups o product.groups según tu API)
        const groupIds = product.modifier_groups || product.groups || [];
        const groups = groupIds
            .map(groupId => this.modifiers.find(g => g.id === groupId))
            .filter(Boolean);

        // Cargar en el borrador los datos existentes del item en el carrito
        this.activeDraftItem = {
            index: index, // Indicamos que estamos editando un item existente
            created_at: item.created_at,
            product_id: item.product_id,
            name: item.name,
            price: item.price,
            qty: item.qty,
            target: item.target,
            modifiers: [...item.modifiers], // Mantenemos la copia de los modificadores elegidos
            notes: item.notes || ""
        };

        // Abre el modal con los grupos
        this.openModifiers(groups);

        // 1. Cargar las notas previas en el campo de texto
        const notesInput = document.getElementById('bistro-pos-modifier-notes');
        if (notesInput) {
            notesInput.value = item.notes || "";
        }

        // 2. Marcar visualmente los botones de modificadores ya seleccionados
        const buttons = document.getElementById('bistro-pos-modifier-content')?.querySelectorAll('button') || [];

        buttons.forEach(btn => {
            const modId = parseInt(btn.dataset.modId);

            // Verificar si este modificador ya está en la lista guardada del item
            const isActive = this.activeDraftItem.modifiers.some(m => m.id === modId);

            if (isActive) {
                btn.className = this.getBtnStyle(true);
            }
        });
    },

    /**
     * Abre y renderiza el modal dinámico
     */
    openModifiers: function (groups) {
        IxeaComponents.openModal('modifiers', {
            addClass: 'max-w-2xl max-h-[80vh] overflow-hidden flex flex-col',
            onOpen: () => {
                // Título
                const titleEl = document.getElementById('bistro-pos-modifier-title');
                if (titleEl && this.activeDraftItem) {
                    titleEl.innerText = this.activeDraftItem.name;
                }

                const container = document.getElementById('bistro-pos-modifier-content');
                if (!container) return;

                container.innerHTML = '';

                if (!groups || groups.length === 0) {
                    container.innerHTML = '<p class="text-slate-500 text-sm text-center py-4">Este producto no tiene modificadores.</p>';
                    return;
                }

                // 1. Priorizar la posición: Ordenar grupos dejando los Obligatorios (required = true) primero
                const sortedGroups = [...groups].sort((a, b) => (b.required ? 1 : 0) - (a.required ? 1 : 0));

                // 2. Renderizar cada grupo de modificadores
                sortedGroups.forEach(group => {
                    const groupEl = document.createElement('div');
                    groupEl.className = 'space-y-2';

                    // Subtítulo con indicadores
                    const reqBadge = group.required
                        ? `<span class="text-rose-400 text-[10px] ml-2 font-bold uppercase">(Obligatorio)</span>`
                        : `<span class="text-slate-500 text-[10px] ml-2 font-normal">(Opcional)</span>`;

                    const maxText = group.max ? ` - Máx ${group.max}` : '';

                    // Cambiado a grid-cols-3 para aprovechar 3 columnas horizontales
                    groupEl.innerHTML = `
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                            ${group.name} ${reqBadge} <span class="text-slate-500 text-xs text-normal">${maxText}</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2" id="group-options-${group.id}"></div>
                    `;

                    container.appendChild(groupEl);

                    const optionsContainer = groupEl.querySelector(`#group-options-${group.id}`);

                    // Renderizar botones de opciones
                    group.modifiers.forEach(mod => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.dataset.groupId = group.id;
                        btn.dataset.modId = mod.id;
                        btn.className = this.getBtnStyle(false);

                        const priceText = mod.price > 0 ? `<span class="text-emerald-400 ml-1">+$${parseFloat(mod.price).toFixed(2)}</span>` : '';
                        btn.innerHTML = `<span class="truncate">${mod.name}</span>${priceText}`;

                        // Evento de selección
                        btn.onclick = () => this.toggleModifierSelection(group, mod, btn);

                        optionsContainer.appendChild(btn);
                    });
                });
            }
        });
    },

    /**
     * Maneja la lógica de selección/deselección de modificadores respetando los límites (max)
     */
    toggleModifierSelection: function (group, mod, btn) {
        const selected = this.activeDraftItem.modifiers;
        const index = selected.findIndex(m => m.id === mod.id);

        if (index > -1) {
            // Deseleccionar si ya estaba
            selected.splice(index, 1);
            btn.className = this.getBtnStyle(false);
        } else {
            // Contar cuántos modificadores de este mismo grupo ya están seleccionados
            const countInGroup = selected.filter(m => m.group_id === group.id).length;

            if (group.max === 1) {
                // Caso Selección Única (Radio): Quitar el seleccionado anterior del mismo grupo
                this.activeDraftItem.modifiers = selected.filter(m => m.group_id !== group.id);

                // Actualizar estilos UI de los botones del grupo
                const siblingBtns = btn.parentElement.querySelectorAll('button');
                siblingBtns.forEach(b => b.className = this.getBtnStyle(false));

                // Agregar el nuevo
                this.activeDraftItem.modifiers.push({ ...mod, group_id: group.id });
                btn.className = this.getBtnStyle(true);

            } else if (!group.max || countInGroup < group.max) {
                // Caso Múltiple: Agregar si no ha superado el máximo permitido
                this.activeDraftItem.modifiers.push({ ...mod, group_id: group.id });
                btn.className = this.getBtnStyle(true);
            } else {
                IxeaComponents.showAlert({
                    text: `Solo puedes seleccionar hasta ${group.max} opciones en ${group.name}.`,
                    icon: 'info'
                });
            }
        }
    },

    /**
     * Estilos visuales dinámicos para los botones de modificadores
     */
    getBtnStyle: function (isSelected) {
        const base = "py-2.5 px-3 font-bold text-xs rounded-xl border text-left flex justify-between items-center transition-all duration-150 ";
        if (isSelected) {
            return base + "bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-900/40";
        }
        return base + "bg-slate-900 text-slate-300 border-slate-700 hover:border-slate-500";
    },

    /**
     * Valida reglas obligatorias y guarda el borrador en el carrito
     */
    saveModifiers: function () {
        if (!this.activeDraftItem) return;

        // Buscar la información original del producto desde el menú
        const product = this.menu.find(p => p.id === this.activeDraftItem.product_id);
        if (!product) return;

        // Extraer los grupos de modificadores del producto
        const groups = (product.groups || [])
            .map(groupId => this.modifiers.find(g => g.id === groupId))
            .filter(Boolean);

        // 1. Validar grupos requeridos
        for (const group of groups) {
            if (group.required) {
                const selectedInGroup = this.activeDraftItem.modifiers.filter(m => m.group_id === group.id);
                if (selectedInGroup.length === 0) {
                    IxeaComponents.showAlert({
                        text: `Por favor seleccione una opción para: ${group.name}`,
                        icon: 'warning'
                    });
                    return;
                }
            }
        }

        // 2. Extraer notas ingresadas
        const notesInput = document.getElementById('bistro-pos-modifier-notes');
        if (notesInput) {
            this.activeDraftItem.notes = notesInput.value.trim();
        }

        // 3. Confirmar adición
        this.commitDraftToCart();
        IxeaComponents.closeModal();
    },

    /**
     * Inserta el producto procesado al carrito de compras
     */
    commitDraftToCart: function () {
        if (!this.activeDraftItem) return;

        const { index, ...finalItem } = this.activeDraftItem;

        if (typeof index === 'number' && index >= 0) {
            // Reemplazar el producto existente en esa posición
            this.cart[index] = finalItem;
        } else {
            // Agregar uno nuevo
            this.cart.push(finalItem);
        }

        this.activeDraftItem = null;
        this.renderCart();
    },

    renderCart: function () {
        const cartEl = document.getElementById('bistro-pos-cart');
        const totalEl = document.getElementById('bistro-pos-amount');
        if (!cartEl) return;

        if (this.cart.length === 0) {
            cartEl.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-slate-500 py-10">
                    <span class="text-4xl mb-2">🍽️</span>
                    <p class="text-xs font-semibold">Comandera sin ordenes</p>
                </div>`;
            if (totalEl) totalEl.innerText = "$ 0.00";
            return;
        }

        let total = 0;

        cartEl.innerHTML = this.cart.map((item, index) => {
            // 1. Sumar el precio base + la suma de los precios de los modificadores elegidos
            const modifiersPrice = (item.modifiers || []).reduce((acc, mod) => acc + (parseFloat(mod.price) || 0), 0);
            const itemUnitPrice = (parseFloat(item.price) || 0) + modifiersPrice;
            const itemSubtotal = itemUnitPrice * item.qty;

            total += itemSubtotal;

            // 2. Formatear la lista de modificadores en texto pequeño
            const modifiersText = (item.modifiers || []).length > 0
                ? `<div class="text-[11px] text-slate-400 mt-1 pl-2 border-l-2 border-indigo-500/50 space-y-0.5">
                    ${item.modifiers.map(m => `<div>• ${m.name} ${m.price > 0 ? `<span class="text-emerald-400/80">(+$ ${toCurrency(m.price)})</span>` : ''}</div>`).join('')}
                </div>`
                : '';

            return `
            <div class="relative overflow-hidden rounded-xl border border-slate-700 bg-slate-900/90 mb-2 group">
                <!-- Botón de eliminación revelado al deslizar -->
                <button onclick="BistroPosApp.removeItem(${index})" 
                        class="absolute right-0 top-0 bottom-0 w-16 bg-rose-600 hover:bg-rose-500 text-white flex flex-col items-center justify-center transition-all duration-200 z-10 shadow-inner">
                    <span class="text-lg">🗑️</span>
                    <span class="text-[10px] font-bold">Quitar</span>
                </button>

                <!-- Contenido deslizable -->
                <div id="cart-item-${index}"
                    data-index="${index}"
                    class="swipe-item relative bg-slate-900 p-3 cursor-pointer transition-transform duration-200 ease-out z-20 hover:border-indigo-500">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-sm text-slate-200">${item.qty}x ${item.name}</h4>
                        <span class="font-bold text-emerald-400 text-sm">${toCurrency(itemSubtotal)}</span>
                    </div>

                    ${modifiersText}

                    <div class="flex justify-between items-center mt-2 pt-1 border-t border-slate-800/80">
                        ${item.notes ? `<p class="text-xs text-amber-400/90 italic">📝 ${item.notes}</p>` : '<div></div>'}
                        <span class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-md font-semibold">
                            ${item.target === 0 || !item.target ? '🍽️ Centro' : '👤 P' + item.target}
                        </span>
                    </div>
                </div>
            </div>`;
        }).join('');

        cartEl.scrollTop = cartEl.scrollHeight;
        if (totalEl) totalEl.innerText = `$${toCurrency(total)}`;
    },

    sendToKitchen: async function () {
        if (this.cart.length === 0) {
            IxeaComponents.showAlert({
                text: 'No hay productos para enviar a cocina.',
                icon: 'warning'
            });
            return;
        }

        if (this.cart.length > 0) {
            IxeaComponents.showConfirm({
                title: '¿Enviar orden a cocina?',
                icon: 'question',
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar',
                onConfirm: () => this.processOrder()
            });
            return;
        }
    },

    processOrder: async function () {
        // 1. Mostrar pantalla de carga
        IxeaComponents.showLoading({ text: 'Enviando comanda a cocina...' });

        try {
            // 2. Estructurar el payload que va al servidor
            const payload = {
                waiter_id: this.currentUser ?? null,
                table_id: this.currentTable ?? null,
                order_id: this.currentOrder ?? null,
                channel_id: this.currentChannel ?? null,
                items: this.cart.map(item => ({
                    product_id: item.product_id,
                    qty: item.qty,
                    price: parseFloat(item.price),
                    target: item.target || 0, // 0 = Centro, 1+ = Comensal
                    notes: item.notes || '',
                    modifiers: (item.modifiers || []).map(mod => ({
                        id: mod.id
                    }))
                }))
            };
            console.log(payload);
            // 3. Petición al backend
            const res = await IxeaBridge.post('/api/v1/bistro-pos/process-order', payload);

            // 4. Cerrar la pantalla de carga antes de mostrar la alerta final
            IxeaComponents.hideLoading();

            if (res.success) {
                if (res.data) {
                    try {
                        await IxeaBridge.sendToLocalServer('/print-ticket/order', res.data);
                    } catch (error) {
                        console.error('[Print Warning]: No se pudo imprimir la comanda localmente:', error);
                    }
                }

                IxeaComponents.showAlert({
                    title: '¡Comanda Enviada!',
                    text: res.message || 'Orden enviada a cocina con éxito.',
                    icon: 'success',
                    timer: 2500
                });

                this.selectTable();
                this.fetchTables();
            } else {
                throw new Error(res.message || 'No se pudo procesar la orden en cocina.');
            }

        } catch (error) {
            IxeaComponents.hideLoading();
            console.error('[POS processOrder Error]:', error);

            IxeaComponents.showAlert({
                title: 'Error de Envío',
                text: error.message || 'Ocurrió un fallo de conexión al enviar la comanda.',
                icon: 'error'
            });
        }
    },

    // ==========================================
    // CUENTAS Y FACTURACIÓN
    // ==========================================
    /**
     * Pregunta al usuario si desea solicitar la cuenta
     */
    requestBill: function () {
        if (!this.currentTable) {
            IxeaComponents.showAlert({
                text: 'Debes seleccionar una mesa o cuenta.',
                icon: 'warning'
            });
            return;
        }

        let canSplit = false;
        if (this.currentTable === -1) {
            const order = this.orders.find(order => order.id === this.currentOrder);
            if (!order) {
                IxeaComponents.showAlert({
                    text: 'No se encontró la cuenta.',
                    icon: 'warning'
                });
                return;
            }

            if (order.status !== 'closed') {
                IxeaComponents.showAlert({
                    text: 'La cuenta no se puede cerrar.',
                    icon: 'warning'
                });
                return;
            }
        } else {
            const table = this.tables.find(table => table.id === this.currentTable);
            if (table.status === 'open') {
                IxeaComponents.showAlert({
                    text: 'No hay cuentas abiertas en esta mesa.',
                    icon: 'warning'
                });
                return;
            }
            if (table.status === 'pending') {
                IxeaComponents.showAlert({
                    text: 'Esta mesa ya tiene una cuenta en proceso.',
                    icon: 'warning'
                });
                return;
            }
            if (this.cart.length > 0) {
                IxeaComponents.showAlert({
                    text: 'Hay productos pendientes en la comanda, favor de cerrar la comanda antes de solicitar la cuenta',
                    icon: 'warning',
                });
                return;
            }
            if (table.count > 1) canSplit = true;
        }

        IxeaComponents.openModal('bill', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-80',
            onOpen: () => {
                const el = document.getElementById('bistro-pos-split-equal');
                if (!el) return;
                if (canSplit) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            }
        });
    },

    /**
     * Abre el modal para elegir método de división de cuenta.
     */
    openSplitOptions: function () {
        IxeaComponents.openModal('bill-split', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-80'
        });
    },

    confirmBillRequest: async function (type) {
        IxeaComponents.closeModal();
        IxeaComponents.showLoading({ text: 'Procesando cuenta...' });

        try {
            let payload = {};
            if (this.currentTable === -1) {
                payload = {
                    order_id: this.currentOrder,
                    type: type
                };
            } else {
                payload = {
                    table_id: this.currentTable,
                    type: type
                };
            }
            const res = await IxeaBridge.post('/api/v1/bistro-pos/request-bill', payload);

            IxeaComponents.hideLoading();
            console.log(res);

            if (res.success) {
                IxeaComponents.showAlert({
                    title: 'Cuenta solicitada. Enviando a impresora...',
                    text: res.message || '',
                    icon: 'success',
                    timer: 2500
                });

                // Petición al servidor local usando try / catch / finally
                try {
                    const printRes = await IxeaBridge.sendToLocalServer('/print-ticket/bill', { ticket: res.data });

                    IxeaComponents.showAlert({
                        text: 'Cuenta impresa en caja',
                        icon: 'success',
                        timer: 2500
                    });
                } catch (err) {
                    IxeaComponents.showAlert({
                        title: 'Aviso de impresión',
                        text: 'La cuenta se registró pero no se pudo conectar con la impresora local.',
                        icon: 'warning',
                        timer: 2500
                    });
                } finally {
                    // Se ejecuta siempre, haya fallado o no la impresión
                    this.selectTable();
                    this.fetchTables();
                }

            } else {
                IxeaComponents.showAlert({
                    title: 'Error al procesar cuenta',
                    text: res.message || 'No se pudo procesar la cuenta.',
                    icon: 'error'
                });
                console.error("[BistroPosApp] Error al procesar cuenta:", res);
            }
        } catch (err) {
            IxeaComponents.hideLoading();
            IxeaComponents.showAlert({
                title: 'Error al procesar cuenta',
                text: 'Ocurrió un error al procesar la cuenta.',
                icon: 'error'
            });
            console.error("[BistroPosApp] Error al procesar cuenta:", err);
        }
    },

    editCurrentOrder: async function (reload = true) {
        if (!this.currentOrder) {
            IxeaComponents.showAlert({
                text: 'No hay ninguna orden activa seleccionada.',
                icon: 'warning'
            });
            return;
        }

        try {
            if (reload) {
                IxeaComponents.showLoading({ text: 'Cargando detalles de la orden...' });
                console.log('Llamando getOrder para Orden #' + this.currentOrder);
                const res = await IxeaBridge.get('/api/v1/bistro-pos/get-order', { order_id: this.currentOrder });
                if (!res || !res.success || !res.data) {
                    throw new Error(res?.message || 'No se pudo cargar la información de la orden.');
                }
                this.currentOrderData = res.data; // Guardamos orden en memoria
                this.currentEditingOrder = this.currentOrderData; // Generamos copia editable de la orden
                this.currentEditingHash = res.hash;
                IxeaComponents.hideLoading();
            }
            const order = this.currentOrderData;

            IxeaComponents.openModal('order-edit', {
                addClassModal: 'backdrop-blur-md',
                addClass: 'w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col',
                showCancel: true, // <-- Activa el botón circular de cerrar arriba a la derecha
                onOpen: () => {
                    const titleEl = document.getElementById('bistro-pos-order-title');
                    const subtitleEl = document.getElementById('bistro-pos-order-subtitle');

                    // Encabezados
                    if (titleEl) titleEl.innerText = `${order.folio}: ${order.name}`;
                    if (subtitleEl) subtitleEl.innerText = `${order.waiter} | ${order.channel_name} | ${new Date(order.created_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`;

                    // Total Global
                    const orderTotal = document.getElementById('bistro-pos-order-total');
                    if (orderTotal) orderTotal.innerText = `$${toCurrency(order.amount)}`;

                    // Descuento Global
                    const globalDiscountInput = document.getElementById('bistro-pos-global-discount');
                    if (globalDiscountInput) globalDiscountInput.value = order.discount || 0;

                    const container = document.getElementById('bistro-pos-order-items');
                    if (!container) return;

                    if (!order.items || order.items.length === 0) {
                        container.innerHTML = '<p class="text-slate-500 text-sm text-center py-6">La orden no contiene ítems registrados.</p>';
                        return;
                    }

                    // Inicializar marcas locales de edición para esta orden si no existen
                    if (!BistroPosApp.markedOrderItems) BistroPosApp.markedOrderItems = {};
                    if (!BistroPosApp.markedOrderItems[order.id]) BistroPosApp.markedOrderItems[order.id] = {};

                    const maxPeople = Math.max(order.people || 1, 1);
                    let targetOptionsHtml = `<option value="0">🍽️ Centro</option>`;
                    for (let p = 1; p <= maxPeople + 1; p++) {
                        targetOptionsHtml += `<option value="${p}">👤 P${p}</option>`;
                    }

                    container.innerHTML = order.items.map((item) => {
                        const itemMark = BistroPosApp.markedOrderItems[order.id][item.id];
                        let markClass = 'bg-slate-900 border-slate-700/80';
                        if (itemMark === 'release') markClass = 'border-emerald-400 bg-emerald-950/40 shadow-[0_0_8px_rgba(52,211,153,0.2)]';
                        if (itemMark === 'cancel') markClass = 'border-rose-400 bg-rose-950/40 shadow-[0_0_8px_rgba(251,113,133,0.2)]';

                        const modifiersText = (item.modifiers || []).length > 0
                            ? `<div class="text-left text-[11px] text-slate-400 mt-1 pl-2 border-l-2 border-indigo-500/50 space-y-0.5">
                                ${item.modifiers.map(m => `<div>${m.name} (${m.qty}x$${toCurrency(m.price)})</div>`).join('')}
                            </div>`
                            : '';

                        // Precio unitario base + modificadores
                        const finalAmount = (parseFloat(item.unitary) * parseFloat(item.quantity)) + (item.modifiers || []).reduce((acc, m) => acc + (parseFloat(m.price) * parseFloat(m.qty || 1)), 0);

                        return `
                        <div class="item-edit-card ${markClass} border rounded-xl p-3 mb-2.5 shadow-sm transition cursor-pointer select-none" data-item-id="${item.id}">
                            <div class="flex justify-between items-start mb-1.5">
                                <div class="flex-1 pr-2">
                                    <h4 class="font-extrabold text-sm text-slate-100 flex justify-between items-center">
                                        <span>${item.quantity}x ${item.name}</span>
                                        <span id="item-amount-${item.id}" class="text-emerald-400 text-sm font-mono font-bold">$${toCurrency(finalAmount)}</span>
                                    </h4>
                                </div>
                            </div>

                            ${modifiersText}
                            ${item.notes ? `<div class="mt-2 ps-2 text-left text-[11px] text-amber-400/90 italic">📝 ${item.notes}</div>` : ''}

                            <div class="flex flex-wrap justify-between items-center mt-2 pt-2 border-t border-slate-800/80 text-xs gap-2">
                                <!-- Selector de Target interactivo -->
                                <div class="flex items-center space-x-1" onclick="event.stopPropagation()">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase">Asignar:</span>
                                    <select onchange="BistroPosApp.updateItemTarget(${item.id}, this.value)" 
                                        id="item-target-${item.id}"
                                        class="bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-indigo-300 font-semibold focus:outline-none focus:border-indigo-500">
                                        ${targetOptionsHtml.replace(`value="${item.target}"`, `value="${item.target}" selected`)}
                                    </select>
                                </div>

                                <!-- Input de Descuento Porcentual (0 a 100) -->
                                <div class="flex items-center space-x-1" onclick="event.stopPropagation()">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase">Desc (%):</span>
                                    <input type="number" min="0" max="100" step="1" value="${item.discount || 0}"
                                        id="item-discount-${item.id}"
                                        oninput="BistroPosApp.calculateOrderTotals()"
                                        onchange="BistroPosApp.calculateOrderTotals()"
                                        class="w-16 bg-slate-950 border border-slate-700 rounded-lg px-1.5 py-0.5 text-xs text-center text-amber-400 font-bold focus:outline-none focus:border-indigo-500">
                                </div>
                            </div>
                        </div>`;
                    }).join('');

                    // Vincular eventos de toque y doble toque estilo KDS en cada tarjeta de ítem
                    container.querySelectorAll('.item-edit-card').forEach(card => {
                        const itemId = parseInt(card.dataset.itemId);
                        BistroPosApp.bindItemClickEvents(card, order.id, itemId);
                    });
                    this.calculateOrderTotals();
                },
                onConfirm: () => { // <-- Activa el botón circular azul con la flecha -> abajo a la derecha
                    BistroPosApp.saveOrderEdits();
                }
            });

        } catch (err) {
            IxeaComponents.hideLoading();
            console.error('[BistroPosApp] Error al abrir edición de orden:', err);
            IxeaComponents.showAlert({
                text: err.message || 'Error al obtener la orden.',
                icon: 'error'
            });
        }
    },

    bindItemClickEvents: function (element, orderId, itemId) {
        let clickTimeout = null;

        element.addEventListener('click', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.closest('input') || e.target.closest('select')) return;

            if (clickTimeout !== null) {
                clearTimeout(clickTimeout);
                clickTimeout = null;
                this.markItemAction(orderId, itemId, 'cancel'); // Doble clic = Cancelar
            } else {
                clickTimeout = setTimeout(() => {
                    clickTimeout = null; // Un clic: Nada
                }, 280);
            }
        });
    },

    /**
     * Marca un item para ser cancelado o restaurado al guardar la edición.
     * @param {number} orderId - ID de la orden.
     * @param {number} itemId - ID del item.
     * @param {string} action - 'cancel' para cancelar, 'restore' para restaurar.
     */
    markItemAction: function (orderId, itemId, action) {
        if (!this.markedOrderItems[orderId]) this.markedOrderItems[orderId] = {};
        const current = this.markedOrderItems[orderId][itemId];
        const item = this.currentEditingOrder.items.find(i => i.id === itemId);
        if (!item) return;

        if (current === action) {
            delete this.markedOrderItems[orderId][itemId];
            item.status = item.last_status || 'pending';
        } else {
            this.markedOrderItems[orderId][itemId] = action;
            item.last_status = item.status;
            item.status = 'cancelled';
        }

        // 1. Capturar la posición actual del scroll del contenedor de ítems
        const container = document.getElementById('bistro-pos-order-items');
        const scrollPosition = container ? container.scrollTop : 0;

        // 2. Refrescar las tarjetas
        this.editCurrentOrder(false);

        // 3. Restaurar exactamente la posición del scroll en el siguiente frame
        requestAnimationFrame(() => {
            const refreshedContainer = document.getElementById('bistro-pos-order-items');
            if (refreshedContainer) {
                refreshedContainer.scrollTop = scrollPosition;
            }
        });
    },

    updateItemTarget: function (itemId, target) {
        // Obtenemos la orden actual
        const item = this.currentEditingOrder.items.find(item => item.id === itemId);
        item.target = parseInt(target);
    },


    /**
     * Calcula el total de la orden basado en los items y descuentos.
     * @returns {number} El total de la orden.
     */
    calculateOrderTotals: function () {
        if (!this.currentEditingOrder || !this.currentEditingOrder.items) return 0;

        let subtotalAccumulator = 0;
        const items = this.currentEditingOrder.items;

        items.forEach(item => {

            // Leer el input de descuento del ítem directamente del DOM
            const discountInput = document.getElementById(`item-discount-${item.id}`);
            const itemDiscountPct = discountInput ? Math.min(100, Math.max(0, parseFloat(discountInput.value) || 0)) : (parseFloat(item.discount) || 0);

            // Actualizar en el objeto en memoria
            item.discount = itemDiscountPct;

            // Calcular base (unitario * cantidad + modificadores)
            const baseUnitPrice = parseFloat(item.unitary) || 0;
            const qty = parseFloat(item.quantity) || 1;
            const itemModifiers = item.modifiers || [];

            const modifiersTotal = itemModifiers.reduce((acc, m) => acc + ((parseFloat(m.price) || 0) * (parseFloat(m.qty || 1))), 0);
            const itemBaseTotal = (baseUnitPrice * qty) + modifiersTotal;

            // Aplicar descuento porcentual a nivel ítem
            const itemFinalAmount = itemBaseTotal * (1 - (itemDiscountPct / 100));
            item.amount = itemFinalAmount;

            const isCancelled = item.status === 'cancelled';
            const amountDisplay = isCancelled ? '$0.00' : `$${toCurrency(itemFinalAmount)}`;
            const amountClass = isCancelled ? 'text-slate-500 line-through' : 'text-emerald-400';
            subtotalAccumulator += isCancelled ? 0 : itemFinalAmount;

            // Actualizar el texto del monto individual en su respectiva tarjeta del DOM
            const amountEl = document.getElementById(`item-amount-${item.id}`);
            if (amountEl) {
                amountEl.innerText = amountDisplay;
                amountEl.className = `text-sm font-mono font-bold ${amountClass}`;
            }

        });

        // Aplicar Descuento Global (%)
        const globalDiscountInput = document.getElementById('bistro-pos-global-discount');
        const globalDiscountPct = globalDiscountInput ? Math.min(100, Math.max(0, parseFloat(globalDiscountInput.value) || 0)) : (parseFloat(this.currentEditingOrder.discount) || 0);

        this.currentEditingOrder.discount = globalDiscountPct;

        const grandTotal = subtotalAccumulator * (1 - (globalDiscountPct / 100));
        this.currentEditingOrder.amount = grandTotal;

        // Actualizar el Total General en el encabezado del modal
        const totalHeaderEl = document.getElementById('bistro-pos-order-total');
        if (totalHeaderEl) {
            totalHeaderEl.innerText = `$${toCurrency(grandTotal)}`;
        }
    },

    saveOrderEdits: async function () {
        if (!this.currentEditingOrder) return;

        const payload = {
            order: this.currentEditingOrder,
            hash: this.currentEditingHash
        }

        try {
            IxeaComponents.showLoading({ text: 'Guardando cambios de la orden...' });

            const res = await IxeaBridge.post('/api/v1/bistro-pos/update-order', payload);

            IxeaComponents.hideLoading();

            if (!res.success) {
                throw new Error(res.message || 'Error al guardar los cambios en el servidor.');
            }

            IxeaComponents.showAlert({ text: 'Orden actualizada con éxito.', icon: 'success', timer: 1800 });
            IxeaComponents.closeModal();
            this.fetchTables();

        } catch (err) {
            IxeaComponents.hideLoading();
            IxeaComponents.showAlert({ text: err.message || 'Error de comunicación.', icon: 'error' });
        }
    }
}