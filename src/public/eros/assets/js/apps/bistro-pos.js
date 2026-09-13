window.BistroPosApp = {
    // ==========================================
    // Variables de Estado
    // ==========================================
    currentUser: IxeaUser.userId,
    categories: [],
    currentCategory: null,
    menu: [],
    modifiers: [],
    tables: [],
    tablesHash: null,
    currentTable: null,
    personCount: 0,
    currentPerson: null,
    cart: [],
    activeDraftItem: null,
    isInitialized: false,
    swipeInitialized: false,
    swipeListeners: [],

    // ==========================================
    // Funciones de Inicialización
    // ==========================================
    init: function () {
        if (this.isInitialized) return;
        this.isInitialized = true;
        this.initCartSwipeListener();
        this.fetchMenu();
        this.fetchModifiers();
        this.fetchCategories();
        this.fetchTables();
    },

    /**
     * Inicializa los listeners mediante delegación de eventos
     */
    initCartSwipeListener: function () {
        const cartEl = document.getElementById('bistro-pos-cart');
        if (!cartEl || this.swipeInitialized) return;

        let activeItem = null;
        let startX = 0;
        let currentX = 0;
        let isSwiping = false;

        // 1. Definimos las funciones manejadoras (Handler functions)
        const handleStart = (e) => {
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const item = e.target.closest('.swipe-item');
            if (!item) return;

            if (activeItem && activeItem !== item) {
                activeItem.style.transform = 'translateX(0px)';
            }

            activeItem = item;
            startX = clientX;
            currentX = clientX;
            isSwiping = true;
        };

        const handleMove = (e) => {
            if (!isSwiping || !activeItem) return;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            currentX = clientX;
            const diffX = startX - currentX;

            if (diffX > 0 && diffX <= 70) {
                activeItem.style.transform = `translateX(-${diffX}px)`;
            }
        };

        const handleEnd = () => {
            if (!isSwiping || !activeItem) return;
            isSwiping = false;
            const diffX = startX - currentX;

            if (diffX > 35) {
                activeItem.style.transform = 'translateX(-64px)';
            } else if (diffX < -10 || diffX <= 35) {
                activeItem.style.transform = 'translateX(0px)';

                if (Math.abs(diffX) < 5) {
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

        // 2. Guardamos las referencias en nuestro array swipeListeners
        this.swipeListeners = [
            { target: cartEl, type: 'touchstart', handler: handleStart, options: { passive: true } },
            { target: cartEl, type: 'touchmove', handler: handleMove, options: { passive: true } },
            { target: cartEl, type: 'touchend', handler: handleEnd, options: false },
            { target: cartEl, type: 'mousedown', handler: handleStart, options: false },
            { target: cartEl, type: 'mousemove', handler: handleMove, options: false },
            { target: cartEl, type: 'mouseup', handler: handleEnd, options: false },
            { target: cartEl, type: 'mouseleave', handler: handleMouseLeave, options: false }
        ];

        // 3. Adjuntamos los eventos
        this.swipeListeners.forEach(listener => {
            listener.target.addEventListener(listener.type, listener.handler, listener.options);
        });

        this.swipeInitialized = true;
    },

    /**
     * Se ejecuta automáticamente al cerrar el módulo desde el orquestador de tu app
     */
    destroy: function () {
        // 1. Remover todos los event listeners registrados de forma explícita
        if (this.swipeListeners && this.swipeListeners.length > 0) {
            this.swipeListeners.forEach(listener => {
                listener.target.removeEventListener(listener.type, listener.handler, listener.options);
            });
            this.swipeListeners = [];
        }

        // 2. Reiniciar estados
        this.swipeInitialized = false;
        this.cart = [];
        this.menu = [];
        this.modifiers = [];
        this.activeDraftItem = null;
    },

    // ==========================================
    // LLAMADAS GET
    // ==========================================
    fetchMenu: async function () {
        try {
            const res = await fetch(`/api/v1/bistro-pos/get-menu`);
            const data = await res.json();
            this.menu = data.menu;
        } catch (err) {
            console.error("[POS] Error al obtener productos:", err);
        }
    },

    fetchModifiers: async function () {
        try {
            const res = await fetch(`/api/v1/bistro-pos/get-modifiers`);
            const data = await res.json();
            this.modifiers = data.groups;
        } catch (err) {
            console.error("[POS] Error al obtener modificadores:", err);
        }
    },

    fetchCategories: async function () {
        try {
            const res = await fetch('/api/v1/bistro-pos/get-categories');
            const data = await res.json();
            this.categories = data.categories;
            this.renderCategories();
        } catch (err) {
            console.error("[POS] Error al obtener categorías:", err);
        }
    },

    fetchTables: async function () {
        try {
            const res = await fetch(`/api/v1/bistro-pos/get-tables?hash=${this.tablesHash || ''}`);
            const data = await res.json();
            if (data.success && data.changed) {
                this.tablesHash = data.hash;
                this.tables = data.tables;
                this.renderTablesMap();
            }
        } catch (err) {
            console.error("[POS] Error al obtener mesas:", err);
        }
    },

    // ==========================================
    // Gestión de Categorías
    // ==========================================
    renderCategories: function () {
        const container = document.getElementById('bistro-pos-categories');

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

    renderTablesMap: function () {
        const template = document.getElementById('bistro-pos-template-table');
        const container = document.getElementById('bistro-pos-tables');

        container.innerHTML = '';
        this.tables.forEach(table => {
            // 1. Clonar el contenido del template (true asegura clonar los hijos)
            const clone = template.content.cloneNode(true);

            // 2. Obtener los elementos internos del clon
            const button = clone.querySelector('.bistro-pos-table-btn');
            const nameSpan = clone.querySelector('.bistro-pos-table-name');
            const iconSpan = clone.querySelector('.bistro-pos-table-icon');
            const badgeSpan = clone.querySelector('.bistro-pos-table-status-badge');

            // 3. Rellenar los datos básicos
            nameSpan.textContent = table.name;
            button.setAttribute('onclick', `BistroPosApp.selectTable(${table.id})`);

            // 4. Aplicar la lógica condicional
            if (table.status === 'open') {
                button.classList.add('border-emerald-500', 'hover:border-emerald-500', 'border-emerald-500/40');
                iconSpan.textContent = '🪑';
                badgeSpan.textContent = 'Libre';
                badgeSpan.classList.add('text-emerald-400', 'bg-emerald-500/10');
            } else if (table.status === 'pending') {
                button.classList.add('border-indigo-500', 'hover:border-indigo-500', 'border-indigo-500/40');
                iconSpan.textContent = '🧾';
                badgeSpan.textContent = 'Cuenta';
                badgeSpan.classList.add('text-indigo-400', 'bg-indigo-500/10');
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

    selectTable: function (id) {
        // Limpiar la selección de mesa
        if (id === null) {
            this.currentTable = null;
            this.personCount = 0;
            const labelEl = document.getElementById('bistro-pos-table');
            if (labelEl) labelEl.innerText = "Seleccionar Mesa";
            const peopleEl = document.getElementById('bistro-pos-people');
            if (peopleEl) peopleEl.innerHTML = '';
            this.selectCategory(null);
            this.cleanCart();
            return;
        }

        if (this.currentTable === id) return;

        const renderTable = () => {
            this.currentTable = id;
            this.toggleTablesDrawer();
            this.renderPeople();
            this.cleanCart();

            const labelEl = document.getElementById('bistro-pos-table');
            const table = this.tables.find(table => table.id === id);
            if (labelEl) labelEl.innerText = table.name;
        }

        if (this.cart.length > 0) {
            IxeaComponents.showConfirm({
                text: '¿Seguro que quieres cambiar de mesa? Se borrará la comanda actual.',
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
                <h2 class="font-bold text-lg text-slate-100">${product.name}</h2>
                <p class="font-black text-emerald-400 text-base">$ ${product.price}</p>
                <p class="text-xs text-slate-400">Tocar para agregar predeterminado</p>
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
            maxw: '2xl',
            maxh: '[80vh]',
            scroll: 'hidden', // Importante: el scroll lo maneja el div interno
            flex: 'col',
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
                    <p class="text-xs font-semibold">Sin productos en la mesa</p>
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
                    onclick="BistroPosApp.modifyItem(${index})" 
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
            const orderPayload = {
                table_id: this.currentTable ? this.currentTable.id : null,
                waiter_id: this.currentUser ? (this.currentUser.id || this.currentUser) : null,
                items: this.cart.map(item => ({
                    product_id: item.product_id,
                    qty: item.qty,
                    price: parseFloat(item.price),
                    target: item.target || 0, // 0 = Centro, 1+ = Comensal
                    notes: item.notes || '',
                    modifiers: (item.modifiers || []).map(mod => ({
                        id: mod.id,
                        group_id: mod.group_id,
                        price: parseFloat(mod.price)
                    }))
                }))
            };

            // 3. Petición al backend
            const response = await fetch('/api/v1/bistro-pos/process-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(orderPayload)
            });

            const result = await response.json();

            // 4. Cerrar la pantalla de carga antes de mostrar la alerta final
            IxeaComponents.hideLoading();

            if (response.ok && result.success) {
                IxeaComponents.showAlert({
                    title: '¡Comanda Enviada!',
                    text: result.message || 'Orden enviada a cocina con éxito.',
                    icon: 'success',
                    timer: 2500
                });

                // Limpiar mesa seleccionada y carrito
                this.selectTable(null);
            } else {
                throw new Error(result.message || 'No se pudo procesar la orden en cocina.');
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
    openSplitOptions: function () {
        IxeaComponents.closeModal();
        IxeaComponents.openModal('modal-split-options');
    },

    processBill: async function (type) {
        IxeaComponents.closeModal();

        try {
            const res = await fetch('/api/v1/bistro-pos/request-bill', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    table: this.currentTable,
                    type: type
                })
            });
            const data = await res.json();

            if (data.success) {
                alert(`🧾 Cuenta solicitada (${type}) para ${this.currentTable}`);
            }
        } catch (err) {
            console.error("[POS] Error al procesar pre-cuenta:", err);
        }
    }
};