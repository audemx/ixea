window.UsersApp = {
    users: [],
    roles: [],
    isInitialized: false,
    videoStream: null,

    init: async function() {
        if (this.isInitialized) return;
        this.isInitialized = true;
        
        if (typeof faceapi === 'undefined') {
            await this.injectFaceApiScript();
            await this.loadModels();
        }
        
        this.loadData();
    },
    
    destroy: function() {
        this.stopCamera();
    },
    
    injectFaceApiScript: function() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = "/face_models_api/face-api.min.js";
            script.async = true;
            script.onload = () => {
                console.log("JS face-api inyectado y listo");
                resolve();
            };
            script.onerror = () => reject(new Error("No se pudo cargar face-api.min.js"));
            document.head.appendChild(script);
        });
    },

    loadModels: async function() {
        const MODEL_URL = '/face_models_api/'; 
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);
            console.log("Modelos tinyFaceDetector cargados con éxito");
        } catch (err) {
            console.error("FaceAPI: Error al cargar modelos", err);
        }
    },

    loadData: async function() {
        try {
            const res = await fetch('/api/get-data?action=get_users');
            const data = await res.json();
            this.users = data.users;
            this.roles = data.roles;
            
            this.renderTable(this.users);
            this.updateMetrics(data.summary);
        } catch (err) { console.error("Error cargando usuarios:", err); }
    },

    updateMetrics: function(summary) {
        document.getElementById('count_total_users').innerText = summary.total;
        document.getElementById('count_active').innerText = summary.active;
        document.getElementById('count_no_face').innerText = summary.no_face;
    },

    renderTable: function(data) {
        const tbody = document.querySelector('#tablaUsuarios tbody');
        tbody.innerHTML = data.map(u => `
            <tr>
                <td class="ps-3">
                    <div class="fw-bold">${u.first_name} ${u.last_name}</div>
                    <div class="x-small text-muted">${u.rfc}</div>
                </td>
                <td><span class="badge bg-light text-dark border">${u.role_name}</span></td>
                <td class="text-center">
                    <span class="badge ${u.status === 'active' ? 'text-sfgreen' : 'text-sfred'} text-uppercase" style="font-size:0.7rem">
                        ${u.status}
                    </span>
                </td>
                <td class="text-center">
                    ${u.has_face ? '<i class="bi bi-person-check text-sfgreen fs-5"></i>' : '<i class="bi bi-person-x text-sfred opacity-50 fs-5"></i>'}
                </td>
                <td class="text-end pe-3">
                    <button class="btn btn-sm btn-outline-primary border-0" onclick="UsersApp.openEditModal(${u.user_id})">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },

    filterUsers: function(val) {
        const query = val.toLowerCase();
        const filtered = this.users.filter(u => 
            u.first_name.toLowerCase().includes(query) ||
            u.last_name.toLowerCase().includes(query) ||
            u.role_name.toLowerCase().includes(query) ||
            u.rfc.toLowerCase().includes(query)
        );
        this.renderTable(filtered);
    },

    openCreateModal: function() {
        IxeaStages.openModal({
            templateId: 'tpl-user-form',
            size: 'md',
            scrollable: true,
            onOpen: () => {
                this.populateRoles();
            },
            onClose: () => {
                this.stopCamera();
            }
        });
    },
    
    openEditModal: function(userId) {
        // Buscamos los datos en nuestro array local que ya tiene todo
        const user = this.users.find(u => u.user_id == userId);
        if (!user) return;
    
        IxeaStages.openModal({
            templateId: 'tpl-user-form',
            size: 'md',
            scrollable: true,
            onOpen: () => {
                // Poblar campos del formulario (usando los nombres de tu tabla)
                const form = document.getElementById('formUsuario');
                form.elements['user_id'].value = user.user_id;
                form.elements['first_name'].value = user.first_name;
                form.elements['last_name'].value = user.last_name;
                form.elements['tax_id'].value = user.rfc;
                form.elements['email'].value = user.email;
                form.elements['status'].value = user.status;
    
                this.populateRoles(user.role_id);
    
                // Mensaje sobre biometría
                if(user.has_face) {
                    document.getElementById('faceMessage').innerHTML = 
                        '<span class="text-info fw-bold"><i class="bi bi-info-circle"></i> Este usuario ya tiene rostro registrado. Escanear de nuevo lo actualizará.</span>';
                }
            },
            onClose: () => {
                this.stopCamera();
            }
        });
    },

    populateRoles: function(selectedId = null) {
        const select = document.getElementById('sel_rol');
        select.innerHTML = '<option value="">Seleccionar Rol...</option>' + 
            this.roles.map(r => `<option value="${r.role_id}" ${r.role_id == selectedId ? 'selected' : ''}>${r.role_name}</option>`).join('');
    },
    
    drawFaceGuide: function(detected = 'none') {
        const canvas = document.getElementById('faceCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // 1. Fondo oscuro
        ctx.fillStyle = "rgba(0, 0, 0, 0.5)";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // 2. Agujero del óvalo
        ctx.globalCompositeOperation = 'destination-out';
        ctx.beginPath();
        ctx.ellipse(140, 105, 75, 100, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        // 3. Borde (Verde si detecta, Blanco/Gris si no)
        ctx.globalCompositeOperation = 'source-over';
        if (detected == 'success'){
            ctx.strokeStyle = "#28a745";
            ctx.lineWidth = 4;
            ctx.setLineDash([]);
        } else if (detected == 'error') {
            ctx.strokeStyle = "#ff6961";
            ctx.lineWidth = 4;
            ctx.setLineDash([]);
        } else if (detected == 'none'){
            ctx.strokeStyle = "rgba(255, 255, 255, 0.6)";
            ctx.lineWidth = 2;
            ctx.setLineDash([5,5]);
        }
        ctx.stroke();
    },

    startCamera: async function() {
        const video = document.getElementById('videoElement');
        document.getElementById('cameraBox').classList.remove('d-none');
        document.getElementById('users-btnStartCamera').classList.add('d-none');
        document.getElementById('users-btnStopCamera').classList.remove('d-none');
        document.getElementById('users-btnCaptureFace').classList.remove('d-none');
        try {
            this.videoStream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = this.videoStream;
            this.drawFaceGuide()
            this.captures = [];
        } catch (err) { alert("Cámara no disponible"); }
    },

    captureFace: async function() {
        const canvas = document.getElementById('faceCanvas');
        const video = document.getElementById('videoElement');
        const msg = document.getElementById('faceMessage');
        
        if (!video || video.paused || video.ended) return;

        msg.innerHTML = '<span class="text-info">Analizando rostro...</span>';

        try {
            const options = new faceapi.TinyFaceDetectorOptions({
                inputSize: 512, // Tamaño estándar para balance velocidad/precisión
                scoreThreshold: 0.7 // Probabilidad mínima para aceptar que es un rostro
            });
            const detection = await faceapi.detectSingleFace(video, options)
                                    .withFaceLandmarks()
                                    .withFaceDescriptor();

            if (detection) {
                const width = canvas.width;
                const height = canvas.height;
                const resized = faceapi.resizeResults(detection, { width: width, height: height });
                
                const box = resized.detection.box;
                const faceCenterX = box.x + (box.width / 2);
                const faceCenterY = box.y + (box.height / 2);
    
                // Centro del óvalo (mitad del canvas)
                const idealX = width / 2;  // 160
                const idealY = height / 2; // 120
                
                // Tolerancia
                const toleranceX = width * 0.15;
                const toleranceY = height * 0.15;
                
                // Validación de centrado: ¿Qué tan lejos del centro permitimos?
                const isCentered = Math.abs(faceCenterX - idealX) < toleranceX && 
                       Math.abs(faceCenterY - idealY) < toleranceY;
                       
                // Validación de tamaño: Que el rostro ocupe al menos el 40% del alto del óvalo
                const isRightSize = box.height > (height * 0.4);
                
                if (!isCentered || !isRightSize) {
                    this.drawFaceGuide('error');
                    msg.innerHTML = '<span class="text-primary">Coloca tu rostro en el área de detección.</span>';
                    setTimeout(() => {
                        if (this.videoStream) this.drawFaceGuide();
                    }, 1000);
                    return;
                }
                
                this.drawFaceGuide('success');
                // Guardamos el descriptor (huella facial)
                const descriptorArray = Array.from(detection.descriptor);
                this.captures.push(descriptorArray);
                
                const step = this.captures.length;
                const stepEl = document.getElementById(`step${step}`);
                if (stepEl) stepEl.classList.add('active');
                
                if (step < 3) {
                    setTimeout(() => {
                        msg.innerHTML = `<span class="text-primary">¡Bien! Toma la captura ${step + 1}</span>`;
                        document.getElementById('capCount').innerHTML = step + 1;
                        if (this.videoStream) this.drawFaceGuide();
                    }, 1000);
                } else {
                    // Calculamos el promedio de los 3 descriptores (128 flotantes cada uno)
                    const totalCaptures = this.captures.length;
                    const vectorSize = this.captures[0].length; // 128
                    const averageDescriptor = new Float32Array(vectorSize);
                
                    for (let i = 0; i < vectorSize; i++) {
                        let sum = 0;
                        for (let j = 0; j < totalCaptures; j++) {
                            sum += this.captures[j][i];
                        }
                        averageDescriptor[i] = sum / totalCaptures;
                    }
                
                    // 2. Guardamos el PROMEDIO en el form
                    document.getElementById('faceDescriptorInput').value = JSON.stringify(Array.from(averageDescriptor));
                    msg.innerHTML = '<span class="text-success">¡Rostro capturado correctamente!</span>';
                    // Bloqueamos botón de captura y apagamos cámara
                    document.getElementById('users-btnCaptureFace').classList.add('d-none');
                    this.stopCamera(true);
                }
            } else {
                this.drawFaceGuide('error');
                setTimeout(() => {
                    if (this.videoStream) this.drawFaceGuide();
                }, 1000);
                msg.innerHTML = '<span class="text-danger">No se detectó rostro. Posiciónate frente a la cámara.</span>';
            }
        } catch (err) {
            console.error("Error en captura:", err);
            msg.innerHTML = '<span class="text-danger">Error al procesar biométricos.</span>';
        }
    },

    stopCamera: function(success = false) {
        if (this.videoStream) {
            this.videoStream.getTracks().forEach(t => t.stop());
            document.getElementById('cameraBox').classList.add('d-none');
            if (!success) {
                document.getElementById('users-btnStartCamera').classList.remove('d-none');
                document.getElementById('faceMessage').innerHTML = '';
            }
            document.getElementById('capCount').innerHTML = 1;
            document.getElementById('users-btnStopCamera').classList.add('d-none');
            document.getElementById('users-btnCaptureFace').classList.add('d-none');
            this.captures = [];
        }
    },
    
    saveUser: async function() {
        const form = document.getElementById('formUsuario');
        
        // Validación básica
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        // Podríamos añadir validaciones extras del PIN aquí
        const pin = formData.get('auth_pin');
        if (pin && pin.length !== 4) {
            Swal.fire('Error', 'El PIN debe ser de 4 dígitos', 'error');
            return;
        }

        try {
            const res = await fetch('/modules/users/user-handler?action=save_user', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Logrado!',
                    text: 'Usuario guardado correctamente',
                    timer: 1500,
                    showConfirmButton: false
                });
                IxeaStages.closeModal();
                this.loadData();
                this.stopCamera();
            } else {
                throw new Error(result.error || 'Error desconocido');
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        }
    }
};