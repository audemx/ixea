/**
 * IXEA OS - User Interfase:
 * IxeaDocks, IxeaStages, IxeaWidgets
 */

/**
 * IxeaDocks: Contenedores laterales de aplicaciones y widgets.
 */
const IxeaDocks = {
    lastTap: 0,
    
    initTouchTriggers: function() {
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

    openDock: function(el) {
        el.classList.add('is-open');
        // Se cierra solo tras 4 segundos
        setTimeout(() => el.classList.remove('is-open'), 2000);
    },

    passThrough: function(e, dock) {
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
    loadScript: function(jsName) {
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

    launch: function(title, url, icon, jsName = null) {
        const stageId = 'stage-' + jsName.toLowerCase();
        const jsObjectName = jsName.charAt(0).toUpperCase() + jsName.slice(1).toLowerCase() + 'App';
        
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
                console.log(`[System] ${jsName.charAt(0).toUpperCase() + jsName.slice(1)} successfully loaded.`);
                
                // 3. Inicializamos la App si tiene un objeto Init
                // Por ejemplo, si el JS de caja define window.TillApp
                const appObjectName = jsName.charAt(0).toUpperCase() + jsName.slice(1) + 'App';
                if (window[appObjectName] && typeof window[appObjectName].init === 'function') {
                    window[appObjectName].init();
                }
                // Registro dinámico en la biblioteca al cargar
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
    
    switch: function(stageId, title) {
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
            if(lpIcon) lpIcon.classList.add('active');
        }
        
        this.updateAppMenu(stageId, title);
    },
    
    updateAppMenu: function(stageId, title) {
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
    closeApp: function(stageId) {
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
                const scriptPath = `/assets/js/apps/${jsObjectName.replace('App', '').toLowerCase()}.js`;
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
    openModal: function(options) {
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

    closeModal: function(options) {
        const modalInstance = bootstrap.Modal.getInstance(this.modalEl);
        if (modalInstance) modalInstance.hide();
    }
};

/**
 * IxeaWidgets: Widgets
 */
const IxeaWidgets = {
    activeWidget: null,
    currentPos: 0,

    toggle: function(id) {
        const panel = document.getElementById('widget-panel');
        if (this.activeWidget === id) return this.close();
        
        this.loadWidget(id);
        panel.classList.add('active');
        this.updatePosition();
        this.activeWidget = id;
    },
    
    nextPos: function() {
        this.currentPos = (this.currentPos + 1) % 6;
        this.updatePosition();
    },
    
    updatePosition: function() {
        const panel = document.getElementById('widget-panel');
        // Limpiamos clases de posición
        for(let i=0; i<6; i++) panel.classList.remove(`pos-${i}`);
        panel.classList.add(`pos-${this.currentPos}`);
    },

    loadWidget: function(id) {
        const loader = document.getElementById('widget-loader');
        
        // Si el usuario clickeó super rápido y el panel aún no está en el DOM
        if (!loader) return; 
    
        loader.innerHTML = `<div class="text-center mt-5"><div class="spinner-border spinner-border-sm text-secondary"></div></div>`;
        
        // Solo usamos el FETCH actual
        fetch(`/includes/widgets/${id}.php`)
        .then(response => {
            if (!response.ok) throw new Error('No se encontró el widget');
            return response.text();
        })
        .then(html => {
            loader.innerHTML = html;
            
            if(id === 'calc' && typeof calc !== 'undefined') {
                setTimeout(() => calc.clear(), 10);
            }
            
            if(id === 'calendar' && typeof CalendarApp !== 'undefined') {
                setTimeout(() => CalendarApp.init(), 10);
            }
        
            if(id === 'notes' && typeof NotesApp !== 'undefined') {
                setTimeout(() => NotesApp.load(), 10);
            }
        })
        .catch(err => {
            loader.innerHTML = `<div class="p-3 small text-danger">Error: ${err.message}</div>`;
        });
    },

    close: function() {
        document.getElementById('widget-panel').classList.remove('active');
        this.activeWidget = null;
    }
};

/**
 * Iniciadores Globales
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Iniciar servicios de fondo
    IxeaKernel.init();
    // 2. Activamos doble toque
    IxeaDocks.initTouchTriggers();
    
    console.log("Ixea EROS: Boot Completed.");
});

// Coreografía de presentación
document.addEventListener('DOMContentLoaded', () => {
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
});
