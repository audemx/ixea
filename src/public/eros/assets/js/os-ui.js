/**
 * IXEA OS - User Interfase:
 * IxeaDocks, IxeaStages
 */

/**
 * IxeaDocks: Contenedores laterales de aplicaciones y widgets.
 */
const IxeaDocks = {
    lastTap: 0,

    initTouchTriggers: function () {
        const docks = document.querySelectorAll('.side-dock-container');

        docks.forEach(dock => {
            dock.addEventListener('touchstart', (e) => {
                const now = Date.now();
                const delta = now - this.lastTap;

                if (delta < 300 && delta > 0) {
                    // DOBLE TOQUE: Abrir y bloquear que pase abajo
                    e.preventDefault();
                    this.openDock(dock);
                } else {
                    // TOQUE SIMPLE: 
                    // No hacemos nada. El dock es transparente visualmente,
                    // pero si sientes que bloquea clics abajo, usamos esto:
                    this.passThrough(e, dock);
                }
                this.lastTap = now;
            }, { passive: false });
        });
    },

    openDock: function (el) {
        el.classList.add('is-open');
        // Se cierra solo tras 4 segundos
        setTimeout(() => el.classList.remove('is-open'), 2000);
    },

    passThrough: function (e, dock) {
        // Si el dock NO está abierto, queremos que el click pase al fondo
        if (!dock.classList.contains('is-open')) {
            // Este es un truco avanzado: ocultamos el dock un milisegundo, 
            // buscamos qué hay abajo y simulamos el click ahí.
            // Pero antes, probemos solo quitando el hover de CSS.
        }
    }
};

/**
 * IxeaStages: Escenarios de trabajo para aplicaciones.
 */
const IxeaStages = {
    loadedScripts: new Set(),
    launchedApps: new Map(),
    AppLibrary: {},
    activeStage: '',
    modalEl: document.getElementById('ixea-main-modal'),

    // Función interna para cargar JS bajo demanda
    loadScript: function (jsName) {
        return new Promise((resolve, reject) => {
            if (!jsName) return resolve(); // No hay JS que cargar

            const scriptPath = `/assets/js/apps/${jsName}.js`;
            if (this.loadedScripts.has(scriptPath)) return resolve();

            const script = document.createElement('script');
            script.src = scriptPath + '?v=' + Date.now();
            script.onload = () => {
                this.loadedScripts.add(scriptPath);
                resolve();
            };
            script.onerror = () => reject(new Error(`Error al cargar el script: ${jsName}`));
            document.head.appendChild(script);
        });
    },

    launch: function (title, url, icon, jsName = null) {
        const stageId = 'stage-' + jsName.toLowerCase();
        const jsObjectName = jsName
            .split('-')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join('') + 'App';

        if (!document.getElementById(stageId)) {
            // 1. Creamos el contenedor
            const html = `<div id="${stageId}" class="stage shadow-sm">
                            <div class="stage-loader text-center mt-5">
                                <div class="spinner-border text-primary"></div>
                            </div>
                          </div>`;
            document.getElementById('dynamic-stages').insertAdjacentHTML('beforeend', html);

            // 2. Cargamos HTML y JS en paralelo (o secuencia controlada)
            Promise.all([
                // Carga del HTML vía jQuery load convertido a Promise
                new Promise(resolve => {
                    $(`#${stageId}`).load(url, () => resolve());
                }),
                // Carga del JS
                this.loadScript(jsName)
            ]).then(() => {
                console.log(`[System] ${jsObjectName} successfully loaded.`);

                // 3. Registro dinámico e inicialización de App
                if (window[jsObjectName]) {
                    this.AppLibrary[jsObjectName] = window[jsObjectName];
                    if (typeof window[jsObjectName].init === 'function') {
                        window[jsObjectName].init();
                    }
                }
                // Guardamos la referencia: ID del escenario -> Nombre del Objeto
                this.launchedApps.set(stageId, jsObjectName);
            }).catch(err => {
                document.getElementById(stageId).innerHTML = `<div class="alert alert-danger m-5">${err.message}</div>`;
            });

            // 4. Estacionar en Dock
            const dock = document.querySelector('.dock-content');
            if (dock) {
                const iconHtml = `
                    <div id="item-${stageId}" class="dock-item animate__animated animate__bounceIn" 
                         onclick="IxeaStages.switch('${stageId}', '${title}')" title="${title}">
                        <i class="bi ${icon}"></i>
                    </div>`;
                dock.insertAdjacentHTML('beforeend', iconHtml);
            }
        }

        this.switch(stageId, title);
    },

    switch: function (stageId, title) {
        // 1. Desactivar todos los escenarios
        document.querySelectorAll('.stage').forEach(s => s.classList.remove('active'));

        // 2. Desactivar todos los íconos del Dock
        document.querySelectorAll('.dock-item').forEach(i => i.classList.remove('active'));

        // 3. Activar el escenario seleccionado
        const targetStage = document.getElementById(stageId);
        if (targetStage) {
            targetStage.classList.add('active');
            this.activeStage = stageId;
        }

        // 4. Activar el ícono correspondiente en el Dock
        // El ID del ícono en el dock suele ser "item-" + stageId (según el código anterior)
        const targetIcon = document.getElementById('item-' + stageId);
        if (targetIcon) {
            targetIcon.classList.add('active');
        }

        // Si el stage es el Launchpad, también podemos iluminar su ícono específico
        if (stageId === 'stage-launchpad') {
            const lpIcon = document.getElementById('item-launchpad'); // Ajusta según tu ID
            if (lpIcon) lpIcon.classList.add('active');
        }

        this.updateAppMenu(stageId, title);
    },

    updateAppMenu: function (stageId, title) {
        const display = document.getElementById('active-app-display');
        const menu = document.getElementById('dynamic-app-menu');

        if (!display || !menu) return;

        // 1. Actualizamos el nombre en la barra superior
        display.innerText = title;

        // Limpiamos menú
        const menuApp = document.getElementById('menu-app-actions');
        if (menuApp) menuApp.innerHTML = '';
        // Limpiamos filtro
        const filterApp = document.getElementById('menu-app-filters');
        if (filterApp) filterApp.innerHTML = '';

        // 2. Si estamos en el escritorio o launchpad, mostramos menú de sistema
        if (stageId === 'stage-0' || stageId === 'stage-launchpad') {
            menu.innerHTML = `
                <li><a class="dropdown-item small" href="#">Prueba tu suerte</a></li>
            `;
            return;
        }

        // 3. Si es una aplicación abierta, inyectamos opciones de control
        menu.innerHTML = `
            <li><a class="dropdown-item small" href="/about/${title}.php" target="_blank"><i class="bi bi-info-circle me-2"></i>Acerca de ${title}</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item small text-sfred" href="javascript:void(0)" 
                   onclick="IxeaStages.closeApp('${stageId}')">
                    <i class="bi bi-x-circle me-2"></i>Finalizar ${title}
                </a>
            </li>
        `;

        // Rehidratamos el menú de operaciones
        const appName = this.launchedApps.get(stageId);
        const jsObject = this.AppLibrary[appName];
        if (jsObject && typeof jsObject.renderAppActions === 'function') {
            jsObject.renderAppActions();
        }

    },

    // Cerrar app: Elimina objetos js de ventana y elimina elementos del DOM
    closeApp: function (stageId) {
        const stage = document.getElementById(stageId);
        if (stage) {
            const dockIcon = document.getElementById('item-' + stageId);
            const menuApp = document.getElementById('menu-app-actions');
            const filterApp = document.getElementById('menu-app-filters');
            const jsObjectName = this.launchedApps.get(stageId);
            const jsObject = this.AppLibrary[jsObjectName];

            if (jsObject) {
                console.log(`[System] Cleaning resources: ${jsObjectName}...`);

                // 1. Llamamos al método destroy si existe (para frenar intervalos)
                if (typeof jsObject.destroy === 'function') {
                    jsObject.destroy();
                }

                // 2. Eliminamos el objeto del entorno global (window) y de la biblioteca
                delete window[jsObjectName];
                delete this.AppLibrary[jsObjectName];

                // 3. Forzamos que se pueda volver a cargar el script si se desea
                // Buscamos la ruta del script para removerla del Set de cargados
                const scriptPath = `/assets/js/apps/${stageId.replace('stage-', '')}.js`;
                this.loadedScripts.delete(scriptPath);
            }


            // Animación de salida del Stage (Barrido)
            stage.classList.add('sweep-left');

            setTimeout(() => {
                // Cerramos modal
                this.closeModal();

                // Regresamos al Launchpad
                this.switch('stage-launchpad', 'Launchpad');

                // Limpieza total del DOM
                if (dockIcon) dockIcon.remove();
                if (menuApp) menuApp.innerHTML = '';
                if (filterApp) filterApp.innerHTML = '';
                // Borramos de diccionario
                if (this.launchedApps.has(stageId)) this.launchedApps.delete(stageId);

                stage.classList.remove('active', 'sweep-left');
                stage.remove();

                console.log(`[System] ${stageId} completely deleted.`);
            }, 600);
        }
    },

    /**
     * Lanza modal principal de stages
     * options: {
     * templateId: 'id-del-template',
     * size: 'sm', 'md', 'lg', 'xl',
     * onOpen: function() {} // Callback opcional
     * }
     */
    openModal: function (options) {
        const contentPlaceholder = document.getElementById('modal-content-placeholder');
        const sizeHandler = document.getElementById('modal-size-handler');
        const template = document.getElementById(options.templateId);

        if (!template) {
            console.error(`Template ${options.templateId} not founded.`);
            return;
        }

        // Ajustes generales
        sizeHandler.className = 'modal-dialog modal-dialog-centered'; // Reset
        if (options.size) sizeHandler.classList.add('modal-' + options.size);
        if (options.scrollable) sizeHandler.classList.add('modal-dialog-scrollable');

        // Limpiar
        contentPlaceholder.innerHTML = '';

        // Botón de cierre
        if (options.btnClose !== false) { // Por defecto true
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close btn-close-out';
            closeBtn.setAttribute('data-bs-dismiss', 'modal');
            contentPlaceholder.appendChild(closeBtn);
        }

        const clone = template.content.cloneNode(true);
        contentPlaceholder.appendChild(clone);

        // Mostrar
        const modalInstance = bootstrap.Modal.getOrCreateInstance(this.modalEl);
        modalInstance.show();

        // Ejecutar callback si existe
        if (options.onOpen) options.onOpen();

        // Configurar Focus Automático (Si se solicita)
        if (options.focusId) {
            // Usamos { once: true } para que el listener se auto-destruya 
            this.modalEl.addEventListener('shown.bs.modal', () => {
                const el = document.getElementById(options.focusId);
                if (el) el.focus();
            }, { once: true });
        }

        // Ejecuta callback si existe al cierre
        if (options.onClose) {
            // Usamos { once: true } para que el listener se auto-destruya 
            this.modalEl.addEventListener('hidden.bs.modal', () => {
                options.onClose();
            }, { once: true });
        }

    },

    closeModal: function (options) {
        const modalInstance = bootstrap.Modal.getInstance(this.modalEl);
        if (modalInstance) modalInstance.hide();
    }
};

/**
 * IxeaComponets: Componentes reutilizables.
 */
const IxeaComponents = {
    // Alertas personalizadas con SweetAlert
    showAlert: function ({ title = '¡Atención!', text = '', icon = 'warning', timer = null }) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            background: '#1e293b',
            backdrop: 'backdrop-blur-sm bg-slate-900/50',
            customClass: {
                popup: 'border border-slate-700 rounded-2xl p-5 shadow-2xl',
                title: 'text-white font-bold',
                htmlContainer: 'text-slate-300',
                confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-xl transition'
            },
            buttonsStyling: false,
            timer: timer,
            timerProgressBar: Boolean(timer),
            showConfirmButton: !timer
        });
    },

    showConfirm: function ({ title = 'Confirmar', text = '', icon = 'warning', confirmButtonText = 'Confirmar', cancelButtonText = 'Cancelar', onConfirm }) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            background: '#1e293b', // bg-slate-800
            backdrop: 'backdrop-blur-sm bg-slate-900/50',
            showCancelButton: true,
            reverseButtons: true, // Muestra primero el botón de cancelar
            confirmButtonColor: '#3b82f6', // blue-500
            cancelButtonColor: '#64748b', // slate-500
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            customClass: {
                popup: 'border border-slate-700 rounded-2xl p-5 shadow-2xl',
                title: 'text-white font-bold',
                htmlContainer: 'text-slate-300',
                cancelButton: 'bg-slate-600 hover:bg-slate-700 text-white font-medium py-2 px-4 mx-2 rounded-xl transition',
                confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 mx-2 rounded-xl transition'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed && onConfirm) {
                onConfirm();
            }
        });
    },

    showLoading: function ({ text = 'Procesando...' }) {
        Swal.fire({
            text: text,
            background: '#1e293b',
            backdrop: 'backdrop-blur-sm bg-slate-900/50',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: {
                popup: 'border border-slate-700 rounded-2xl p-5 shadow-2xl',
                htmlContainer: 'text-slate-300 font-medium'
            },
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },

    hideLoading: function () {
        Swal.close();
    },

    openModal: function (operation, options = {}) {
        const stage = IxeaStages.activeStage.replace("stage-", "");
        const modal = document.getElementById(`${stage}-modal`);

        let template;
        if (operation === 'auth') {
            template = document.getElementById(`bouncer-template`);
        } else {
            template = document.getElementById(`${stage}-template-${operation}`);
        }

        if (!modal || !template) return;

        modal.innerHTML = '';

        let modalClassList = 'fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-3'
        let contentClassList = 'bg-slate-800 border border-slate-700 rounded-2xl p-3 shadow-2xl';

        // Definir clases del modal
        options.backdrop ? modalClassList += ` backdrop-blur-${options.backdrop}` : modalClassList += ' backdrop-blur-sm';
        modal.className = modalClassList;

        // Definir clases del contenido
        options.width ? contentClassList += ` w-${options.width}` : contentClassList += ' w-full';
        options.height ? contentClassList += ` h-${options.height}` : contentClassList += '';
        options.maxw ? contentClassList += ` max-w-${options.maxw}` : contentClassList += '';
        options.maxh ? contentClassList += ` max-h-${options.maxh}` : contentClassList += '';
        options.xalign ? contentClassList += ` text-${options.xalign}` : contentClassList += ' text-center';
        options.yalign ? contentClassList += ` items-${options.yalign}` : contentClassList += ' items-center';
        options.scroll ? contentClassList += ` overflow-${options.scroll}` : contentClassList += ' overflow-hidden';
        options.flex ? contentClassList += ` flex flex-${options.flex}` : contentClassList += '';

        const child = document.createElement('div');
        child.className = contentClassList;

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
        const stage = IxeaStages.activeStage.replace("stage-", "");
        const modal = document.getElementById(`${stage}-modal`);
        if (modal) modal.classList.add('hidden');
    }
};

/**
 * IxeaBouncer - Control de permisos
 */
const IxeaBouncer = {
    pin: [],
    permission: null,
    onAuth: null,

    /** Manejo de entrada de PIN */
    handlePinInput: function (input) {
        if (input === 'cancel') {
            IxeaComponents.closeModal();
            return;
        }
        if (input === 'delete') {
            this.pin.pop();
            this.renderPin();
            return;
        }
        if (this.pin.length < 4) {
            this.pin.push(input);
            this.renderPin();
        }
        if (this.pin.length === 4) {
            this.verifyPin();
        }
    },

    /** Renderizado del PIN */
    renderPin: function () {
        const stage = IxeaStages.activeStage.replace("stage-", "");
        const modal = document.getElementById(`${stage}-modal`);
        const pinDots = modal.querySelectorAll('.pin-dot');

        if (pinDots) {
            pinDots.forEach((pinDot, index) => {
                pinDot.className = `pin-dot w-3.5 h-3.5 rounded-full ${index < this.pin.length ? 'bg-indigo-700' : 'bg-slate-500'}`;
            });
        }
    },

    /** Verificación del PIN */
    verifyPin: async function () {
        const pin = this.pin.join('');

        if (!pin) {
            IxeaComponents.showAlert({ text: 'PIN inválido', icon: 'warning' });
            return;
        }

        try {
            const body = {
                pin: pin,
                permission: IxeaBouncer.permission
            };
            const res = await IxeaBridge.post('/api/v1/bouncer/auth-operation', body);
            if (res.success) {
                // 1. Guardar la referencia del callback asignado
                const callback = IxeaBouncer.onAuth;

                // 2. Limpiar el estado global del modal/bouncer
                IxeaComponents.closeModal();
                IxeaBouncer.pin = [];
                IxeaBouncer.permission = null;
                IxeaBouncer.onAuth = null;

                if (typeof callback === 'function') {
                    callback(res.data);
                }
            } else {
                this.pin = [];
                this.renderPin();
                IxeaComponents.showAlert({ text: res?.message || 'PIN inválido', icon: 'error' });
            }
        } catch (err) {
            this.pin = [];
            this.renderPin();
            console.error('[VerifyPin] Error:', err);
            IxeaComponents.showAlert({
                text: err.message || 'Error al conectar con el servidor.',
                icon: 'error'
            });
        }
    },

    can: function (key) {
        const permissions = window.IXEA_USER?.permissions ?? [];
        return permissions.includes('all_access') || permissions.includes(key);
    },

    requestAuth: function (key, callback) {
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

    lockBtn: function (btn) {
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
    releaseBtn: function (btn) {
        if (!btn || !btn.dataset.originalHtml) return;

        btn.disabled = false;
        btn.classList.remove('btn-loading');
        btn.innerHTML = btn.dataset.originalHtml;
        delete btn.dataset.isLocked;
    },

    /** Muestra un pantallazo de carga frente a todo para bloquear todo proceso **/
    showLoading: function (message = 'Procesando...') {
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
    hideLoading: function () {
        const overlay = document.getElementById(this.overlayId);
        if (overlay) {
            overlay.remove();
        }
    },
};

/**
 * Objeto para manejar la intro
 */
const IxeaUI = {
    init: function () {
        this.intro();
    },

    intro: function () {
        // Retraso de 500ms antes de empezar toda la coreografía
        setTimeout(() => {
            const welcome = document.getElementById('intro-welcome');
            const afterWelcome = document.getElementById('intro-a');
            const logo = document.getElementById('intro-logo');
            const sloganWords = document.querySelectorAll('.slogan-word');

            // 1. "BIENVENIDO A"
            const text = welcome.innerText;
            welcome.innerHTML = text.split('').map((char, i) =>
                `<span class="letter" style="animation-delay: ${i * 50}ms">${char === ' ' ? '&nbsp;' : char}</span>`
            ).join('');
            welcome.classList.add('is-ready');

            setTimeout(() => {
                afterWelcome.style.visibility = 'visible';
            }, 1000);

            // 2. "IXEA OS"
            setTimeout(() => {
                logo.classList.add('logo-reveal');
            }, 2000);

            // 3. Slogan
            const delays = [3000, 3700, 4800];
            sloganWords.forEach((word, i) => {
                setTimeout(() => {
                    word.classList.add('slogan-land');
                }, delays[i]);
            });

            // 4. Salida de bienvenida y entrada de Launchpad
            setTimeout(() => {
                const wrapper = document.querySelector('.intro-wrapper');
                wrapper.classList.add('sweep-left');

                setTimeout(() => {
                    wrapper.remove();

                    // CAMBIO: Activamos la etapa del Launchpad
                    IxeaStages.switch('stage-launchpad', 'Launchpad');
                }, 1200); // Sincronizado con la salida
            }, 6200);
        }, 500); // <-- EL DELAY DE MEDIO SEGUNDO
    }
}
