window.BistroPosApp = {
    // ==========================================
    // Variables de Estado
    // ==========================================
    categories: [],
    currentCategory: null,
    menu: [],
    tables: [],
    tablesHash: null,
    currentTable: null,
    personCount: 0,
    currentPerson: null,
    cartItems: [],
    isInitialized: false,
    editingIndex: null,

    // ==========================================
    // Funciones de Inicialización
    // ==========================================
    init: function () {
        if (this.isInitialized) return;
        console.log("[BISTRO POS] Inicializando Punto de Venta...");
        this.isInitialized = true;
        this.fetchMenu();
        this.fetchCategories();
        this.fetchTables();
    },

    destroy: function () {
        this.isInitialized = false;
        this.tablesHash = null;
        this.currentTable = null;
        this.personCount = 0;
        this.currentPerson = null;
        this.cartItems = [];
        this.editingIndex = null;
    },

    // ==========================================
    // Gestión de Modales
    // ==========================================
    openModal: function (operation, options = {}) {
        const modal = document.getElementById('bistro-pos-modal');
        const template = document.getElementById(`bistro-pos-template-${operation}`);
        if (!modal || !template) return;

        modal.innerHTML = '';

        let modalClassList = 'fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4'
        let contentClassList = 'bg-slate-800 border border-slate-700 rounded-2xl p-5 shadow-2xl';

        // Definir clases del modal
        options.backdrop ? modalClassList += ` backdrop-blur-${options.backdrop}` : modalClassList += ' backdrop-blur-sm';
        modal.className = modalClassList;

        // Definir clases del contenido
        options.width ? contentClassList += ` w-${options.width}` : contentClassList += ' w-full';
        options.maxw ? contentClassList += ` max-w-${options.maxw}` : contentClassList += '';
        options.maxh ? contentClassList += ` max-h-${options.maxh}` : contentClassList += '';
        options.xalign ? contentClassList += ` text-${options.xalign}` : contentClassList += ' text-center';
        options.yalign ? contentClassList += ` items-${options.yalign}` : contentClassList += ' items-center';
        options.scroll ? contentClassList += ` overflow-${options.scroll}` : contentClassList += ' overflow-hidden';
        options.flex ? contentClassList += ` flex flex-${options.flex}` : contentClassList += '';

        const child = document.createElement('div');
        child.className = contentClassList;
        console.log(contentClassList);
        const clone = template.content.cloneNode(true);
        child.appendChild(clone);
        modal.appendChild(child);

        // Ejecutar callback si existe
        if (options.onOpen) options.onOpen();

        // Configurar Focus Automático (Si se solicita)
        if (options.focusId) {
            const el = document.getElementById(options.focusId);
            if (el) el.focus();
        }

    },

    closeModal: function () {
        const modal = document.getElementById('bistro-pos-modal');
        if (modal) modal.classList.add('hidden');
    },

    // ==========================================
    // Gestión de Productos
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

    // ==========================================
    // Gestión de Categorías
    // ==========================================
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

        this.currentCategory = this.categories.find(category => category.id === id);
        const button = document.getElementById(`bistro-pos-category-${id}`);
        button.className = 'bg-indigo-600 text-white px-2 py-1 rounded-xl font-bold whitespace-nowrap shadow';
        this.renderMenu();
    },

    renderMenu: function () {
        const container = document.getElementById('bistro-pos-menu');
        const menuFiltered = this.menu.filter(product => product.category_id === this.currentCategory.id);
        console.log(menuFiltered);

        container.innerHTML = '';
        menuFiltered.forEach(product => {
            const button = document.createElement('button');
            button.id = `bistro-pos-product-${product.id}`;
            button.className = 'bg-slate-800 border border-slate-700/80 rounded-xl p-3 flex flex-col justify-between active:scale-95 transition cursor-pointer hover:border-emerald-500';
            button.innerHTML = `
                <h2 class="font-bold text-lg text-slate-100">${product.name}</h2>
                <p class="font-black text-emerald-400 text-base">$ ${product.price}</p>
                <p class="text-xs text-slate-400">Tocar para agregar predeterminado</p>
            `;
            button.onclick = () => this.addProduct(product.id);
            container.appendChild(button);
        });
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
            console.log("[POS] Abriendo cajón de mesas...");
            this.fetchTables();
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
        if (this.currentTable === id) return;
        if (this.cartItems.length > 0) {
            if (!confirm('¿Seguro que quieres cambiar de mesa? Se borrará la comanda actual.')) return;
        }
        this.currentTable = id;
        this.cartItems = [];
        this.renderCart();

        const labelEl = document.getElementById('bistro-pos-table');
        const table = this.tables.find(table => table.id === id);
        console.log(table);
        if (labelEl) labelEl.innerText = table.name;

        this.toggleTablesDrawer();
        this.renderCart();
        this.renderPeople();
    },

    // ==========================================
    // Gestión de Comensales
    // ==========================================
    renderPeople: function () {
        const peopleEl = document.getElementById('bistro-pos-people');
        if (!peopleEl) return;
        peopleEl.innerHTML = '';

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
            alert("Por favor seleccione una mesa.");
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
    // CARRITO Y MODIFICADORES
    // ==========================================
    addProduct: function (id) {
        if (!this.currentTable) {
            alert("Por favor seleccione una mesa.");
            return;
        }

        const rawItem = this.menu.find(item => item.id === id);
        if (!rawItem) return;

        const newItem = {
            id: Date.now(),
            name: rawItem.name,
            price: rawItem.price,
            qty: 1,
            target_person: this.currentPerson,
            modifiers: [],
            notes: ""
        };

        this.cartItems.push(newItem);
        this.renderCart();
    },

    openItemModifiers: function (index) {
        this.editingIndex = index;
        this.openModal('modal-modifiers');
    },

    renderCart: function () {
        const cartEl = document.getElementById('bistro-pos-cart');
        const totalEl = document.getElementById('bistro-pos-amount');
        if (!cartEl) return;

        if (this.cartItems.length === 0) {
            cartEl.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-slate-500 py-10">
                    <span class="text-4xl mb-2">🍽️</span>
                    <p class="text-xs font-semibold">Sin productos en la mesa</p>
                </div>`;
            if (totalEl) totalEl.innerText = "$ 0.00";
            return;
        }

        let total = 0;
        cartEl.innerHTML = this.cartItems.map((item, index) => {
            total += item.price * item.qty;
            return `
            <div onclick="BistroPosApp.modifyItem(${index})" 
                 class="bg-slate-900/90 border border-slate-700 p-3 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                <div class="flex justify-between items-start">
                    <h4 class="font-bold text-sm text-slate-200">${item.qty}x ${item.name}</h4>
                    <span class="font-bold text-emerald-400 text-sm">$${(item.price * item.qty).toFixed(2)}</span>
                </div>
                <div class="flex justify-between items-center mt-1.5">
                    ${item.notes ? `<p class="text-xs text-slate-400">${item.notes}</p>` : ''}
                    <span class="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-md font-semibold">
                        ${item.target_person === 0 ? '🍽️ Centro' : '👤 P' + item.target_person}
                    </span>
                </div>
            </div>`;
        }).join('');

        cartEl.scrollTop = cartEl.scrollHeight;

        if (totalEl) totalEl.innerText = `$${toCurrency(total)}`;
    },

    modifyItem: function (index) {
        const item = this.cartItems[index];
        this.editingIndex = index;


        this.openModal('modifiers');
    },

    sendToKitchen: async function () {
        if (this.cartItems.length === 0) {
            alert("No hay productos para enviar a cocina.");
            return;
        }

        try {
            const res = await fetch('/api/v1/bistro-pos/send-to-kitchen', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    table: this.currentTable,
                    items: this.cartItems
                })
            });
            const data = await res.json();
            if (data.success) {
                alert(`✅ Comanda enviada a cocina para ${this.currentTable}`);
            }
        } catch (err) {
            console.error("[POS] Error al enviar a cocina:", err);
        }
    },

    // ==========================================
    // CUENTAS Y FACTURACIÓN
    // ==========================================
    openSplitOptions: function () {
        this.closeModal('modal-bill-confirm');
        this.openModal('modal-split-options');
    },

    processBill: async function (type) {
        this.closeModal('modal-bill-confirm');
        this.closeModal('modal-split-options');

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