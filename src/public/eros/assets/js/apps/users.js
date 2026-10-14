window.UsersApp = {
    users: [],
    roles: [],
    isInitialized: false,

    init: async function () {
        if (this.isInitialized) return;
        this.isInitialized = true;

        await this.loadData({ forceSync: true });
    },

    loadData: async function ({ cacheOnly = false, forceSync = false } = {}) {
        try {
            const res = await IxeaData.get("users", "get-users", { cacheOnly, forceSync });
            if (res) {
                this.users = res.data.users || [];
                this.roles = res.data.roles || [];

                this.renderTable(this.users);
                this.updateMetrics(res.data.summary || { total: 0, active: 0, fingerprint: 0 });
            }
        } catch (err) {
            console.error("Error cargando usuarios:", err);
            IxeaComponents.showAlert({
                title: 'Error',
                text: 'No se pudieron obtener los datos de usuarios',
                icon: 'error'
            });
        }
    },

    updateMetrics: function (summary) {
        const totalEl = document.getElementById('users-count-total');
        const activeEl = document.getElementById('users-count-active');
        const fingerprintEl = document.getElementById('users-count-fingerprint');

        if (totalEl) totalEl.innerText = summary.total;
        if (activeEl) activeEl.innerText = summary.active;
        if (fingerprintEl) fingerprintEl.innerText = summary.fingerprint;
    },

    renderTable: function (data) {
        const tbody = document.getElementById('users-table-body');
        if (!tbody) return;

        if (!data || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-8 text-slate-500">
                        <i class="bi bi-people text-3xl block mb-2"></i>
                        No se encontraron usuarios
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = data.map(u => `
            <tr class="hover:bg-slate-600/40 transition border-b border-slate-600/40">
                <td class="py-3 px-4">
                    <div class="font-bold text-white">${u.first_name || ''} ${u.last_name || ''}</div>
                    <div class="text-xs text-slate-400">${u.rfc || ''}</div>
                </td>
                <td class="py-3 px-4">
                    <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-600 text-slate-200 border border-slate-500">
                        ${u.role_name || 'Sin Rol'}
                    </span>
                </td>
                <td class="py-3 px-4 text-center">
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase rounded-full ${u.status === 'active'
                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                : 'bg-rose-500/10 text-rose-400 border border-rose-500/30'
            }">
                        ${u.status}
                    </span>
                </td>
                <td class="py-3 px-4 text-center">
                    ${u.has_fingerprint
                ? '<i class="bi bi-fingerprint text-emerald-400 text-xl" title="Huella registrada"></i>'
                : '<i class="bi bi-fingerprint text-slate-500/40 text-xl" title="Sin huella"></i>'}
                </td>
                <td class="py-3 px-4 text-right">
                    <button class="text-slate-400 hover:text-amber-400 p-1.5 rounded-lg transition hover:bg-slate-600 me-1" 
                            title="Registrar / Actualizar Huella"
                            onclick="UsersApp.openEnrollFingerprint(${u.user_id})">
                        <i class="bi bi-fingerprint text-base"></i>
                    </button>
                    <button class="text-slate-400 hover:text-blue-400 p-1.5 rounded-lg transition hover:bg-slate-600" 
                            title="Editar usuario"
                            onclick="UsersApp.openEditUser(${u.user_id})">
                        <i class="bi bi-pencil-square text-base"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },

    filterUsers: function (val) {
        const query = val.toLowerCase().trim();
        const filtered = this.users.filter(u =>
            (u.first_name && u.first_name.toLowerCase().includes(query)) ||
            (u.last_name && u.last_name.toLowerCase().includes(query)) ||
            (u.role_name && u.role_name.toLowerCase().includes(query)) ||
            (u.rfc && u.rfc.toLowerCase().includes(query))
        );
        this.renderTable(filtered);
    },

    openCreateUser: function () {
        IxeaComponents.openModal('form', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-full',
            onOpen: () => {
                const titleEl = document.getElementById('users-form-title');
                if (titleEl) titleEl.innerHTML = '<i class="bi bi-person-plus text-blue-400 me-2"></i>Nuevo Usuario';
                this.populateRoles();
            }
        });
    },

    openEditUser: function (userId) {
        const user = this.users.find(u => u.user_id == userId);
        if (!user) return;

        IxeaComponents.openModal('form', {
            addClassModal: 'backdrop-blur-md',
            addClass: 'w-full',
            onOpen: () => {
                const titleEl = document.getElementById('users-form-title');
                if (titleEl) titleEl.innerHTML = '<i class="bi bi-pencil-square text-blue-400 me-2"></i>Editar Usuario';

                const form = document.getElementById('users-form');
                if (form) {
                    form.elements['user_id'].value = user.user_id;
                    form.elements['first_name'].value = user.first_name || '';
                    form.elements['last_name'].value = user.last_name || '';
                    form.elements['tax_id'].value = user.rfc || '';
                    form.elements['email'].value = user.email || '';
                    form.elements['status'].value = user.status || 'active';
                    if (form.elements['auth_pin']) form.elements['auth_pin'].value = user.auth_pin || '';
                }

                this.populateRoles(user.role_id);
            }
        });
    },

    populateRoles: function (selectedId = null) {
        const select = document.getElementById('users-field-role');
        if (!select) return;
        select.innerHTML = '<option value="">Seleccionar Rol...</option>' +
            this.roles.map(r => `<option value="${r.role_id}" ${r.role_id == selectedId ? 'selected' : ''}>${r.role_name}</option>`).join('');
    },

    openEnrollFingerprint: function (userId) {
        // Disparador para captura de huella biométrica
        IxeaComponents.showAlert({
            title: 'Captura de Huella',
            text: `Coloque el dedo sobre el lector para el usuario ID: ${userId}`,
            icon: 'info'
        });
    },

    saveUser: async function () {
        const form = document.getElementById('users-form');
        if (!form) return;
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const pin = formData.get('auth_pin');
        if (pin && pin.length !== 4) {
            IxeaComponents.showAlert({
                title: 'Atención',
                text: 'El PIN debe ser exactamente de 4 dígitos',
                icon: 'warning'
            });
            return;
        }

        const payload = Object.fromEntries(formData.entries());
        try {
            const res = await IxeaBridge.post('/api/v1/users/save-user', payload);

            if (res && res.success) {
                IxeaComponents.showAlert({
                    title: 'Usuario guardado correctamente',
                    icon: 'success',
                    timer: 1500
                });
                IxeaComponents.closeModal();
                await this.loadData();
            } else {
                throw new Error(res?.error || 'Error desconocido al guardar usuario');
            }
        } catch (err) {
            console.error("Error cargando usuarios:", err);
            IxeaComponents.showAlert({
                title: 'Error',
                text: 'Hubo un error al guardar el usuario',
                icon: 'error'
            });
        }
    }
};