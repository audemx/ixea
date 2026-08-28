/**
 * IXEA OS - Integración APIs:
 * GmailApi
 */

/**
 * GMAIL
 */
const GmailApi = {
    
    gmailConfirmation: function () {
        Swal.fire({
            title: 'Vincular Gmail',
            text: 'IXEA EROS requiere permiso para envíar correos desde tu cuenta profesional. Se abrirá una ventana nueva para autorizar con Google.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Autorizar Google',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Abrimos en pestaña nueva
                window.open('/api/gmail/get-refresh-token.php', 'GoogleAuth', 'width=600,height=700');
            }
        });
    }
}

/**
 * FaceApi
 */
const FaceApi = {
    faceApiLoaded: false,
    faceModelsLoaded: false,
    
    injectFaceApiScript: function() {
        if (this.faceApiLoaded) return;
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = "/face_models_api/face-api.min.js";
            script.async = true;
            script.onload = () => {
                this.faceApiLoaded = true;
                console.log("JS face-api inyectado y listo");
                resolve();
            };
            script.onerror = () => reject(new Error("No se pudo cargar face-api.min.js"));
            document.head.appendChild(script);
        });
    },

    loadModels: async function() {
        if (this.faceModelsLoaded) return;
        
        const MODEL_URL = '/face_models_api/'; 
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);
            this.faceModelsLoaded = true;
            console.log("Modelos tinyFaceDetector cargados con éxito");
        } catch (err) {
            console.error("FaceAPI: Error al cargar modelos", err);
        }
    },
}
