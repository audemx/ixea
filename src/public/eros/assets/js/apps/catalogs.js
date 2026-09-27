/** assets/js/apps/catalogs.js **/
window.CatalogsApp = {
    jsName: 'catalogs',
    isInitialized: false,

    init: function () {
        if (this.isInitialized) return;
        this.isInitialized = true;
        this.renderMenuActions();
    },

    /** Renderiza los botones del menú contextual (barra superior) */
    renderMenuActions: function () {
        const container = document.getElementById('menu-app-actions');
        if (!container) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = `
            <div class="dropdown">
                <div class="menu-item cursor-pointer" data-bs-toggle="dropdown">
                    Operaciones
                </div>
                <ul class="dropdown-menu shadow border-0 mt-2">
                    <li><a class="dropdown-item small" onclick="CatalogsApp.export()"><i class="bi bi-download me-2"></i>Exportar</a></li>
                    <li><a class="dropdown-item small" onclick="CatalogsApp.import()"><i class="bi bi-upload me-2"></i>Importar</a></li>
                </ul>
            </div>
        `;
    },

    export: function () {
        IxeaComponents.openModal('export', { 'width': 'md' });
    },

    doExport: async function () {
        const catalogId = parseInt(document.getElementById('catalog-export-select')?.value);
        if (!catalogId) {
            IxeaComponents.showAlert({ text: 'Selecciona un catálogo', icon: 'warning' });
            return;
        }

        // Petición estándar a través de tu IxeaBridge
        const res = await IxeaBridge.post('/api/v1/catalogs/export-catalog', {
            catalog_id: catalogId
        });

        if (!res || res.status !== 'success') {
            IxeaComponents.showAlert({ text: res?.message || 'No se pudo generar el archivo', icon: 'error' });
            return;
        }

        // Decodificar Base64 a Blob
        const byteCharacters = atob(res.file_data);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'text/csv;charset=utf-8;' });

        // Descargar
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = res.file_name || `${catalogId}.csv`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    }
};