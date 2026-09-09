window.CheckerApp = {
    isInitialized: false,
    videoStream: null,

    init: function() {
        if (this.isInitialized) return;
        console.log("Checker Initialized...");
        this.isInitialized = true;
        
        this.updateClock();
        this.interval = setInterval(() => this.updateClock(), 1000);
        this.prepareBiometrics();
    },
    
    destroy: function() {
      this.stopAuth();
      clearInterval(this.interval);
    },

    updateClock: function() {
        const now = new Date();
        const clockEl = document.getElementById('liveClock');
        const dateEl = document.getElementById('liveDate');
        if(clockEl) clockEl.innerText = now.toLocaleTimeString();
        if(dateEl) dateEl.innerText = now.toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    },

    prepareBiometrics: async function() {
        try {
            if (!FaceApi.faceApiLoaded) await FaceApi.injectFaceApiScript();
            if (!FaceApi.faceModelsLoaded) await FaceApi.loadModels();
            
        } catch (e) {
            console.error("Error cargando biométricos", e);
        }
    },

    startAuth: async function() {
        if (!FaceApi.faceApiLoaded || !FaceApi.faceModelsLoaded) return Swal.fire('Atención', 'Cargando IA', 'warning');
        
        const btn = document.getElementById('btnInitChecker');
        if (!IxeaBouncer.lockBtn(btn)) return;

        const video = document.getElementById('checkerVideo');
        const box = document.getElementById('checkerCameraBox');
        const idle = document.getElementById('idleState');

        try {
            this.videoStream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = this.videoStream;
            
            box.classList.remove('d-none');
            idle.classList.add('d-none');;
            
            this.drawGuide('none');
            this.scanFace();
            
        } catch (err) {
            Swal.fire('Error', 'Camara no detectada', 'warning');
            IxeaBouncer.releaseBtn(btn);
        }
    },

    drawGuide: function(status) {
        const canvas = document.getElementById('checkerCanvas');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = "rgba(0,0,0,0.4)";
        ctx.fillRect(0,0, canvas.width, canvas.height);
        
        ctx.globalCompositeOperation = 'destination-out';
        ctx.beginPath();
        ctx.ellipse(160, 120, 80, 110, 0, 0, 2 * Math.PI);
        ctx.fill();
        
        ctx.globalCompositeOperation = 'source-over';
        ctx.strokeStyle = status === 'success' ? '#28a745' : (status === 'error' ? '#dc3545' : '#ffffff');
        ctx.lineWidth = 5;
        ctx.stroke();
    },

    scanFace: async function() {
        if (!this.videoStream) return;
        const canvas = document.getElementById('checkerCanvas'); // 320x240
        const video = document.getElementById('checkerVideo');
        const msg = document.getElementById('checkerMsg');

        if (video.paused || video.ended || video.readyState < 2) {
            console.log("Video no listo, reintentando en 200ms...");
            setTimeout(() => this.scanFace(), 200);
            return;
        }
        
        msg.innerHTML = '<span class="text-muted">Escaneando rostro...</span>';
        
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
            
            // Geometría de detección
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
            
            if (isCentered && isRightSize) {
                this.drawGuide('success');
                msg.innerHTML = '<span class="text-primary">Validando identidad...</span>';
                this.verifyIdentity(Array.from(detection.descriptor));
            } else {
                this.drawGuide('error');
                msg.innerHTML = '<span class="text-primary">Coloca tu rostro en el área de detección.</span>';
                setTimeout(() => this.drawGuide(), 2000);
                setTimeout(() => this.scanFace(), 3000);
            }
            
        } else {
            msg.innerHTML = '<span class="text-muted">Sin detección, reintentando...</span>';
            this.drawGuide('error');
            setTimeout(() => this.drawGuide(), 2000);
            setTimeout(() => this.scanFace(), 3000);
        }
    },

    verifyIdentity: async function(descriptor) {
        try {
            const body = {
                action: 'attendance',
                descriptor: descriptor
            }
            const res = await fetch('/modules/rrhh/checker-handler', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await res.json();

            const msg = document.getElementById('checkerMsg');
            if (data.success && data.checkin) {
                msg.innerHTML = `<div class="text-success animate__animated animate__bounceIn">
                                    <i class="bi bi-check-circle-fill d-block fs-1"></i>
                                    ¡Hola ${data.name}!<br>Registro de ${data.type_label} exitoso.
                                 </div>`;
                this.stopAuth(true);
            } else if (data.success && !data.checkin) {
                msg.innerHTML = `<span class="text-danger">${data.message}<br>Reintentando...</span>`;
                this.drawGuide('error');
                setTimeout(() => this.drawGuide(), 2000);
                setTimeout(() => this.scanFace(), 3000);
                
            } else {
                msg.innerHTML = `<span class="text-danger">${data.message}<br>Cerrando...</span>`;
                this.drawGuide('error');
                this.stopAuth(true);
            }
        } catch (e) {
            this.stopAuth(true);
            console.error(e);
        }
    },

    stopAuth: function(wait = false) {
        setTimeout(() => {
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(t => t.stop());
                this.videoStream = null;
            }
            document.getElementById('checkerCameraBox').classList.add('d-none');
            document.getElementById('idleState').classList.remove('d-none');
            document.getElementById('checkerMsg').innerHTML = '';
            
            const btn = document.getElementById('btnInitChecker');
            IxeaBouncer.releaseBtn(btn);
            
        }, wait ? 3000 : 0);
    }
};