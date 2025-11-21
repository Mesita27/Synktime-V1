/**
 * Sistema de captura biométrica
 * Funciones para capturar rostros y huellas dactilares
 */

// Variables globales para el sistema de captura biométrica
let faceVideoStream = null;
let facialModel = null;
let capturedFaces = [];
let isCameraActive = false;
let isCapturing = false;
let faceCaptureProgress = 0;
let fingerprintCaptureProgress = 0;
let currentEmployeeId = window.currentEmployeeId || null;
let captureInterval = null;

// Intentar obtener el ID del empleado del modal cuando esté disponible
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('biometricEnrollmentModal');
    if (modal) {
        const employeeId = modal.getAttribute('data-employee-id');
        if (employeeId) {
            currentEmployeeId = employeeId;
            console.log('ID de empleado establecido en biometric-capture.js:', currentEmployeeId);
        }
    }
});

// Elementos DOM para facial
const faceVideo = document.getElementById('faceVideo');
const faceCanvas = document.getElementById('faceCanvas');
const faceProgress = document.getElementById('faceProgress');
const faceCaptures = document.getElementById('faceCaptures');
const startFaceCameraBtn = document.getElementById('startFaceCamera');
const captureFaceBtn = document.getElementById('captureFace');
const stopFaceCameraBtn = document.getElementById('stopFaceCamera');

// Elementos DOM para huella
const fingerprintProgress = document.getElementById('fingerprintProgress');
const fingerprintSamples = document.getElementById('fingerprintSamples');
const startFingerprintBtn = document.getElementById('startFingerprint');
const captureFingerprintBtn = document.getElementById('captureFingerprint');
const stopFingerprintBtn = document.getElementById('stopFingerprint');
const scannerAnimation = document.getElementById('scannerAnimation');
const fingerprintIcon = document.getElementById('fingerprintIcon');

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    setupBiometricCapture();
});

// Cargar modelos de TensorFlow para reconocimiento facial
async function loadFacialModels() {
    try {
        console.log('Cargando modelos faciales...');
        facialModel = await blazeface.load();
        console.log('Modelos faciales cargados correctamente');
        return true;
    } catch (error) {
        console.error('Error cargando modelos faciales:', error);
        return false;
    }
}

/**
 * Configurar eventos para captura biométrica
 */
function setupBiometricCapture() {
    // Inicializar pestañas del modal
    const enrollmentTabs = document.getElementById('enrollmentTabs');
    if (enrollmentTabs) {
        const tabs = enrollmentTabs.querySelectorAll('[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(event) {
                const target = event.target.getAttribute('data-bs-target');
                if (target === '#facial-panel') {
                    currentEnrollmentType = 'facial';
                    stopFingerprint(); // Detener captura de huella si está activa
                } else if (target === '#fingerprint-panel') {
                    currentEnrollmentType = 'fingerprint';
                    stopFaceCamera(); // Detener cámara si está activa
                }
            });
        });
    }
    
    // Eventos para reconocimiento facial
    if (startFaceCameraBtn) {
        startFaceCameraBtn.addEventListener('click', startFaceCamera);
    }
    
    if (captureFaceBtn) {
        captureFaceBtn.addEventListener('click', captureFace);
    }
    
    if (stopFaceCameraBtn) {
        stopFaceCameraBtn.addEventListener('click', stopFaceCamera);
    }
    
    // Eventos para captura de huella
    if (startFingerprintBtn) {
        startFingerprintBtn.addEventListener('click', startFingerprint);
    }
    
    if (captureFingerprintBtn) {
        captureFingerprintBtn.addEventListener('click', captureFingerprint);
    }
    
    if (stopFingerprintBtn) {
        stopFingerprintBtn.addEventListener('click', stopFingerprint);
    }
    
    // Botón de guardar enrolamiento
    const saveEnrollmentBtn = document.getElementById('saveEnrollment');
    if (saveEnrollmentBtn) {
        saveEnrollmentBtn.addEventListener('click', saveEnrollment);
    }
    
    // Cargar modelos faciales al inicio
    loadFacialModels().then(success => {
        console.log('Modelos faciales cargados:', success ? '✅' : '❌');
    });
    
    console.log('Sistema de captura biométrica inicializado');
}

/**
 * FACIAL RECOGNITION FUNCTIONS
 */
async function startFaceCamera() {
    try {
        if (isCameraActive) {
            console.log('La cámara ya está activa');
            return;
        }
        
        // Obtener acceso a la cámara
        faceVideoStream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: 640, 
                height: 480,
                facingMode: 'user' 
            } 
        });
        
        // Mostrar video en el elemento
        faceVideo.srcObject = faceVideoStream;
        await faceVideo.play();
        
        // Actualizar estado
        isCameraActive = true;
        startFaceCameraBtn.disabled = true;
        captureFaceBtn.disabled = false;
        stopFaceCameraBtn.disabled = false;
        
        console.log('Cámara iniciada correctamente');
        
        // Iniciar detección de rostros
        startFaceDetection();
    } catch (error) {
        console.error('Error al iniciar la cámara:', error);
        showNotification('No se pudo acceder a la cámara: ' + error.message, 'error');
    }
}

function startFaceDetection() {
    if (!facialModel) {
        console.warn('Modelo facial no cargado');
        return;
    }
    
    const ctx = faceCanvas.getContext('2d');
    
    // Función de detección de rostros
    async function detectFaces() {
        if (!isCameraActive) return;
        
        try {
            const predictions = await facialModel.estimateFaces(faceVideo);
            
            ctx.clearRect(0, 0, faceCanvas.width, faceCanvas.height);
            
            if (predictions.length > 0) {
                // Dibujar rectángulo alrededor del rostro
                for (let i = 0; i < predictions.length; i++) {
                    const start = predictions[i].topLeft;
                    const end = predictions[i].bottomRight;
                    const size = [end[0] - start[0], end[1] - start[1]];
                    
                    ctx.strokeStyle = '#00ff00';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(start[0], start[1], size[0], size[1]);
                    
                    // Mostrar probabilidad
                    const score = predictions[i].probability[0];
                    ctx.fillStyle = '#00ff00';
                    ctx.font = '16px Arial';
                    ctx.fillText(`Confianza: ${Math.round(score * 100)}%`, start[0], start[1] - 5);
                }
            }
        } catch (error) {
            console.error('Error en detección facial:', error);
        }
        
        requestAnimationFrame(detectFaces);
    }
    
    detectFaces();
}

async function captureFace() {
    if (!isCameraActive || !facialModel) {
        console.warn('La cámara no está activa o el modelo no está cargado');
        return;
    }
    
    try {
        const predictions = await facialModel.estimateFaces(faceVideo);
        
        if (predictions.length === 0) {
            showNotification('No se detectó ningún rostro', 'warning');
            return;
        }
        
        if (predictions.length > 1) {
            showNotification('Se detectaron múltiples rostros. Por favor, asegúrese de que solo haya una persona frente a la cámara.', 'warning');
            return;
        }
        
        // Capturar la imagen del rostro
        const canvas = document.createElement('canvas');
        canvas.width = 640;
        canvas.height = 480;
        const ctx = canvas.getContext('2d');
        
        // Dibujar el video en el canvas
        ctx.drawImage(faceVideo, 0, 0, canvas.width, canvas.height);
        
        // Recortar el rostro
        const start = predictions[0].topLeft;
        const end = predictions[0].bottomRight;
        const size = [end[0] - start[0], end[1] - start[1]];
        
        // Añadir margen al recorte
        const margin = 20;
        const faceX = Math.max(0, start[0] - margin);
        const faceY = Math.max(0, start[1] - margin);
        const faceWidth = Math.min(canvas.width - faceX, size[0] + margin * 2);
        const faceHeight = Math.min(canvas.height - faceY, size[1] + margin * 2);
        
        // Crear un nuevo canvas para el rostro recortado
        const faceCanvas = document.createElement('canvas');
        faceCanvas.width = faceWidth;
        faceCanvas.height = faceHeight;
        const faceCtx = faceCanvas.getContext('2d');
        
        // Copiar el rostro del canvas principal
        faceCtx.drawImage(canvas, faceX, faceY, faceWidth, faceHeight, 0, 0, faceWidth, faceHeight);
        
        // Convertir a base64
        const imageData = faceCanvas.toDataURL('image/jpeg', 0.9);
        
        // Guardar la captura
        capturedFaces.push({
            imageData,
            timestamp: new Date().toISOString(),
            confidence: predictions[0].probability[0]
        });
        
        // Actualizar progreso
        faceCaptureProgress = Math.min(100, (capturedFaces.length / 5) * 100);
        faceProgress.style.width = `${faceCaptureProgress}%`;
        faceCaptures.textContent = capturedFaces.length;
        
        // Mostrar notificación
        showNotification(`Rostro capturado (${capturedFaces.length}/5)`, 'success');
        
        // Activar botón de guardar si hay suficientes capturas
        const saveEnrollmentBtn = document.getElementById('saveEnrollment');
        if (saveEnrollmentBtn && capturedFaces.length >= 5) {
            saveEnrollmentBtn.disabled = false;
            stopFaceCamera();
        }
        
    } catch (error) {
        console.error('Error al capturar rostro:', error);
        showNotification('Error al capturar rostro', 'error');
    }
}

function stopFaceCamera() {
    if (!isCameraActive) return;
    
    try {
        // Detener el stream de video
        if (faceVideoStream) {
            faceVideoStream.getTracks().forEach(track => track.stop());
            faceVideoStream = null;
        }
        
        // Limpiar el video y canvas
        if (faceVideo) {
            faceVideo.srcObject = null;
        }
        
        if (faceCanvas) {
            const ctx = faceCanvas.getContext('2d');
            ctx.clearRect(0, 0, faceCanvas.width, faceCanvas.height);
        }
        
        // Actualizar estado
        isCameraActive = false;
        startFaceCameraBtn.disabled = false;
        captureFaceBtn.disabled = true;
        stopFaceCameraBtn.disabled = true;
        
        console.log('Cámara detenida correctamente');
        
    } catch (error) {
        console.error('Error al detener la cámara:', error);
    }
}

/**
 * FINGERPRINT FUNCTIONS
 */
function startFingerprint() {
    try {
        // Simulación de activación del escáner
        startFingerprintBtn.disabled = true;
        captureFingerprintBtn.disabled = false;
        stopFingerprintBtn.disabled = false;
        
        // Activar animación de escaneo
        const scannerArea = document.querySelector('.scanner-area');
        if (scannerArea) scannerArea.classList.add('active');
        
        console.log('Escáner de huella iniciado');
        showNotification('Escáner de huella activado', 'info');
        
    } catch (error) {
        console.error('Error al iniciar escáner de huella:', error);
        showNotification('Error al iniciar escáner de huella', 'error');
    }
}

function captureFingerprint() {
    try {
        // Obtener el tipo de dedo seleccionado
        const fingerType = document.querySelector('input[name="fingerType"]:checked').value;
        
        // Simular captura de huella
        fingerprintCaptureProgress += 34; // Aproximadamente 33% por cada muestra
        fingerprintProgress.style.width = `${fingerprintCaptureProgress}%`;
        
        const currentSamples = Math.ceil(fingerprintCaptureProgress / 34);
        fingerprintSamples.textContent = currentSamples;
        
        console.log(`Huella capturada (${fingerType}): ${currentSamples}/3`);
        showNotification(`Muestra ${currentSamples} capturada correctamente`, 'success');
        
        // Activar botón de guardar si hay suficientes muestras
        const saveEnrollmentBtn = document.getElementById('saveEnrollment');
        if (saveEnrollmentBtn && currentSamples >= 3) {
            saveEnrollmentBtn.disabled = false;
            stopFingerprint();
        }
        
    } catch (error) {
        console.error('Error al capturar huella:', error);
        showNotification('Error al capturar huella', 'error');
    }
}

function stopFingerprint() {
    try {
        // Desactivar controles
        startFingerprintBtn.disabled = false;
        captureFingerprintBtn.disabled = true;
        stopFingerprintBtn.disabled = true;
        
        // Desactivar animación de escaneo
        const scannerArea = document.querySelector('.scanner-area');
        if (scannerArea) scannerArea.classList.remove('active');
        
        console.log('Escáner de huella detenido');
        
    } catch (error) {
        console.error('Error al detener escáner de huella:', error);
    }
}

/**
 * GUARDAR ENROLAMIENTO
 */
async function saveEnrollment() {
    try {
        // Intentar obtener el ID del empleado de varias fuentes
        let employeeId = currentEmployeeId;
        
        // Si no está definido, intentar obtenerlo de la ventana global
        if (!employeeId && window.currentEmployeeId) {
            employeeId = window.currentEmployeeId;
        }
        
        // Si aún no está definido, intentar obtenerlo del atributo data del modal
        if (!employeeId) {
            const modal = document.getElementById('biometricEnrollmentModal');
            if (modal) {
                employeeId = modal.getAttribute('data-employee-id');
            }
        }
        
        // Si aún no está definido, intentar obtenerlo del campo oculto
        if (!employeeId) {
            const hiddenField = document.getElementById('current-employee-id');
            if (hiddenField && hiddenField.value) {
                employeeId = hiddenField.value.trim();
            }
        }
        
        // Si aún no está definido, intentar obtenerlo del código mostrado en el modal
        if (!employeeId) {
            const codeElement = document.getElementById('modal-employee-code');
            if (codeElement && codeElement.textContent && codeElement.textContent !== '-') {
                employeeId = codeElement.textContent.trim();
            }
        }
        
        console.log('ID de empleado para guardar:', employeeId);
        
        if (!employeeId) {
            console.error('ID de empleado no definido');
            showNotification('Error: ID de empleado no definido', 'error');
            return;
        }
        
        // Recopilar datos biométricos
        const biometricData = {
            employee_id: employeeId,
            facial: capturedFaces.length > 0 ? {
                images: capturedFaces.map(face => face.imageData),
                timestamp: new Date().toISOString()
            } : null,
            fingerprint: fingerprintCaptureProgress >= 100 ? {
                fingerType: document.querySelector('input[name="fingerType"]:checked').value,
                timestamp: new Date().toISOString()
            } : null
        };
        
        console.log('Guardando datos biométricos:', {
            employee_id: employeeId,
            hasFacial: !!biometricData.facial,
            hasFingerprint: !!biometricData.fingerprint
        });
        
        // Enviar datos al servidor
        const response = await fetch('api/biometric/save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(biometricData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Enrolamiento guardado correctamente', 'success');
            
            // Cerrar modal
            if (typeof bootstrap !== 'undefined') {
                const modalElement = document.getElementById('biometricEnrollmentModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            
            // Actualizar lista de empleados
            if (typeof refreshData === 'function') {
                refreshData();
            }
            
            // Reiniciar variables
            resetCapture();
            
        } else {
            throw new Error(result.message || 'Error al guardar enrolamiento');
        }
        
    } catch (error) {
        console.error('Error al guardar enrolamiento:', error);
        showNotification('Error al guardar enrolamiento: ' + error.message, 'error');
    }
}

/**
 * FUNCIONES AUXILIARES
 */
function resetCapture() {
    // Detener cámara y escáner
    stopFaceCamera();
    stopFingerprint();
    
    // Reiniciar variables
    capturedFaces = [];
    faceCaptureProgress = 0;
    fingerprintCaptureProgress = 0;
    currentEmployeeId = null;
    
    // Actualizar UI
    if (faceProgress) faceProgress.style.width = '0%';
    if (faceCaptures) faceCaptures.textContent = '0';
    if (fingerprintProgress) fingerprintProgress.style.width = '0%';
    if (fingerprintSamples) fingerprintSamples.textContent = '0';
    
    // Desactivar botón de guardar
    const saveEnrollmentBtn = document.getElementById('saveEnrollment');
    if (saveEnrollmentBtn) saveEnrollmentBtn.disabled = true;
}

// Función para mostrar notificaciones si no está definida globalmente
if (typeof showNotification !== 'function') {
    window.showNotification = function(message, type = 'info') {
        // Implementación básica de notificaciones
        const notificationTypes = {
            success: { icon: '✅', color: '#28a745' },
            error: { icon: '❌', color: '#dc3545' },
            warning: { icon: '⚠️', color: '#ffc107' },
            info: { icon: 'ℹ️', color: '#17a2b8' }
        };
        
        const notifType = notificationTypes[type] || notificationTypes.info;
        
        console.log(`${notifType.icon} ${message}`);
        
        // Mostrar alerta si no hay sistema de notificaciones
        if (typeof Toastify !== 'function' && typeof toastr !== 'object') {
            alert(`${type.toUpperCase()}: ${message}`);
        }
    };
}

// Exportar funciones para uso externo
window.startFaceCamera = startFaceCamera;
window.captureFace = captureFace;
window.stopFaceCamera = stopFaceCamera;
window.startFingerprint = startFingerprint;
window.captureFingerprint = captureFingerprint;
window.stopFingerprint = stopFingerprint;
window.saveEnrollment = saveEnrollment;
window.resetCapture = resetCapture;
