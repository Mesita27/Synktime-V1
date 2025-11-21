/**
 * Biometric BlazeFace Implementation
 * Implementa el reconocimiento facial usando BlazeFace para el enrolamiento y verificación biométrica.
 */

let blazeFaceModel = null;
let faceInterval = null;
let faceStream = null;
let faceDetected = false;
let detectionConfidence = 0;
let captureTimeout = null;
let faceData = [];
let isProcessing = false;
let currentEmployeeId = null;

// Referencias a elementos DOM - se inicializarán cuando se necesiten
let videoElement = null;
let canvasElement = null;
let startButton = null;
let stopButton = null;
let detectionStatus = null;
let detectionConfidenceElement = null;
let faceProgressBar = null;
let faceCaptures = null;

// Función para obtener los elementos DOM cuando se necesiten
function getDOMElements() {
    console.log('Buscando elementos DOM necesarios para la cámara...');
    
    // Intentar acceder a los elementos con manejo de errores
    try {
        // Asegurarse que estamos en un contexto donde el DOM está accesible
        if (typeof document === 'undefined') {
            console.error('Documento no disponible');
            return false;
        }
        
        // Limpiar referencias previas
        videoElement = null;
        canvasElement = null;
        startButton = null;
        stopButton = null;
        detectionStatus = null;
        detectionConfidenceElement = null;
        faceProgressBar = null;
        faceCaptures = null;
        
        // Obtener nuevas referencias con verificación
        videoElement = document.getElementById('faceVideo');
        canvasElement = document.getElementById('faceCanvas');
        startButton = document.getElementById('startFaceCamera');
        stopButton = document.getElementById('stopFaceCamera');
        detectionStatus = document.getElementById('face-detection-status');
        detectionConfidenceElement = document.getElementById('face-detection-confidence');
        faceProgressBar = document.getElementById('faceProgress');
        faceCaptures = document.getElementById('faceCaptures');
        
        // Registrar detalladamente qué elementos se encontraron y cuáles no
        const elementChecks = {
            'faceVideo (videoElement)': videoElement ? 'Encontrado ✅' : 'No encontrado ❌',
            'faceCanvas (canvasElement)': canvasElement ? 'Encontrado ✅' : 'No encontrado ❌',
            'startFaceCamera (startButton)': startButton ? 'Encontrado ✅' : 'No encontrado ❌',
            'stopFaceCamera (stopButton)': stopButton ? 'Encontrado ✅' : 'No encontrado ❌',
            'face-detection-status': detectionStatus ? 'Encontrado ✅' : 'No encontrado ❌',
            'face-detection-confidence': detectionConfidenceElement ? 'Encontrado ✅' : 'No encontrado ❌',
            'faceProgress': faceProgressBar ? 'Encontrado ✅' : 'No encontrado ❌',
            'faceCaptures': faceCaptures ? 'Encontrado ✅' : 'No encontrado ❌'
        };
        
        console.log('Estado de elementos DOM:', elementChecks);
        
        // Crear elementos faltantes si es posible
        if (!detectionStatus && document.querySelector('.detection-status')) {
            detectionStatus = document.createElement('span');
            detectionStatus.id = 'face-detection-status';
            document.querySelector('.detection-status').appendChild(detectionStatus);
            console.log('Elemento face-detection-status creado dinámicamente');
        }
        
        if (!detectionConfidenceElement && document.querySelector('.detection-status')) {
            detectionConfidenceElement = document.createElement('span');
            detectionConfidenceElement.id = 'face-detection-confidence';
            document.querySelector('.detection-status').appendChild(detectionConfidenceElement);
            console.log('Elemento face-detection-confidence creado dinámicamente');
        }
        
        // Verificar si todos los elementos esenciales están disponibles
        if (!videoElement || !canvasElement) {
            console.error('Elementos críticos de video/canvas no encontrados');
            return false;
        }
        
        console.log('Elementos DOM principales encontrados correctamente');
        return true;
    } catch (error) {
        console.error('Error al buscar elementos DOM:', error);
        return false;
    }
}

// Configuración
const MIN_CONFIDENCE_THRESHOLD = 0.90; // Umbral mínimo de confianza (90%)
const MAX_CAPTURES = 5;                // Número máximo de capturas para enrolamiento
const CAPTURE_INTERVAL = 1000;         // Intervalo entre capturas automáticas (ms)
const FACE_CHECK_INTERVAL = 100;       // Intervalo para verificar rostros (ms)

/**
 * Inicializa BlazeFace y carga el modelo
 */
async function initBlazeFace() {
    try {
        if (!blazeFaceModel) {
            updateStatus('Cargando modelo facial...', 'text-info');
            blazeFaceModel = await blazeface.load();
            updateStatus('Modelo facial listo', 'text-success');
            console.log('BlazeFace modelo cargado');
        }
        return true;
    } catch (error) {
        console.error('Error al cargar el modelo BlazeFace:', error);
        updateStatus('Error al cargar modelo facial', 'text-danger');
        return false;
    }
}

/**
 * Inicia la cámara y el proceso de detección facial
 */
async function startFaceCamera() {
    try {
        console.log('Iniciando cámara para reconocimiento facial...');
        
        // Obtener referencias a los elementos del DOM - Esta función debe ejecutarse antes de cualquier operación
        if (!getDOMElements()) {
            console.error('No se encontraron todos los elementos necesarios del DOM');
            showAlert('Error: No se encontraron todos los elementos necesarios en el DOM', 'danger');
            return; // Salir inmediatamente si no tenemos los elementos del DOM
        }
        
        // Obtiene el ID del empleado
        const employeeIdElement = document.getElementById('employee_id') || 
                                document.getElementById('hidden_employee_id') || 
                                document.getElementById('current-employee-id');
        
        if (employeeIdElement) {
            currentEmployeeId = employeeIdElement.value;
            console.log('ID de empleado encontrado:', currentEmployeeId);
        }
        
        if (!currentEmployeeId) {
            console.warn('No se encontró ID de empleado en los campos normales');
            // Intenta buscar el ID en el texto del modal
            const employeeCode = document.getElementById('modal-employee-code');
            if (employeeCode) {
                currentEmployeeId = employeeCode.textContent.trim();
                console.log('Usando código de empleado como alternativa:', currentEmployeeId);
            }
        }
        
        // Actualizar el ID visible
        const displayEmployeeId = document.getElementById('display-employee-id');
        if (displayEmployeeId && currentEmployeeId) {
            displayEmployeeId.textContent = currentEmployeeId;
        }
        
        // Reinicia los datos
        faceData = [];
        updateFaceProgress(0);
        document.getElementById('faceCaptures').textContent = '0';
        
        // Carga el modelo si no está cargado
        if (!blazeFaceModel) {
            updateStatus('Cargando modelo facial...', 'text-info');
            await initBlazeFace();
        }
        
        // Configura la cámara
        const constraints = {
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        };
        
        try {
            // Primero detener cualquier stream previo para evitar conflictos
            if (faceStream) {
                faceStream.getTracks().forEach(track => track.stop());
                faceStream = null;
            }
            
            // Limpiar cualquier srcObject previo
            if (videoElement.srcObject) {
                videoElement.srcObject = null;
                videoElement.load(); // Forzar limpieza del elemento video
            }
            
            console.log('Solicitando acceso a la cámara...');
            faceStream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElement.srcObject = faceStream;
            
            // Espera a que el video esté listo con un timeout para evitar bloqueos
            await Promise.race([
                new Promise(resolve => {
                    videoElement.onloadedmetadata = () => {
                        console.log('Video metadata cargada correctamente');
                        resolve();
                    };
                }),
                new Promise((_, reject) => setTimeout(() => reject(new Error('Timeout esperando metadata')), 5000))
            ]);
            
            // Manejo seguro de reproducción
            try {
                console.log('Intentando reproducir video...');
                await videoElement.play();
                console.log('Video reproduciendo correctamente');
            } catch (playError) {
                console.error('Error al reproducir video:', playError);
                throw new Error('Error al reproducir video: ' + playError.message);
            }
            
            updateStatus('Buscando rostro...', 'text-info');
            
            // Configura el canvas al tamaño del video
            canvasElement.width = videoElement.videoWidth || 640;
            canvasElement.height = videoElement.videoHeight || 480;
            
            // Inicia la detección facial
            startFaceDetection();
            
            // Actualiza los botones con máxima seguridad
            try {
                // Siempre volver a obtener las referencias a los botones para evitar usar referencias obsoletas
                const currentStartButton = document.getElementById('startFaceCamera');
                const currentStopButton = document.getElementById('stopFaceCamera');
                
                if (currentStartButton) {
                    currentStartButton.disabled = true;
                    console.log('Botón de inicio desactivado correctamente');
                    
                    // Actualizar la variable global para consistencia
                    startButton = currentStartButton;
                } else {
                    console.warn('No se pudo acceder al botón de inicio');
                }
                
                if (currentStopButton) {
                    currentStopButton.disabled = false;
                    console.log('Botón de parada activado correctamente');
                    
                    // Actualizar la variable global para consistencia
                    stopButton = currentStopButton;
                } else {
                    console.warn('No se pudo acceder al botón de parada');
                }
                
                // Actualizar el estado visual para indicar que la cámara está activa
                const cameraContainer = document.querySelector('.camera-container');
                if (cameraContainer) {
                    cameraContainer.classList.add('camera-active');
                }
            } catch (buttonError) {
                console.error('Error al actualizar estado de botones:', buttonError);
                // No interrumpimos el flujo por un error en los botones
            }
            
            console.log('Cámara iniciada exitosamente');
        } catch (cameraError) {
            console.error('Error específico de la cámara:', cameraError);
            updateStatus('Error al acceder a la cámara', 'text-danger');
            showAlert('No se pudo acceder a la cámara. Verifique los permisos del navegador.', 'danger');
        }
        
    } catch (error) {
        console.error('Error al iniciar la cámara:', error);
        updateStatus('Error al iniciar la cámara', 'text-danger');
        showAlert('No se pudo acceder a la cámara. Verifique los permisos.', 'danger');
    }
}

/**
 * Inicia el proceso de detección facial continua
 */
function startFaceDetection() {
    // Verificamos que tengamos acceso a los elementos necesarios
    if (!videoElement || !canvasElement) {
        console.error('No se pueden iniciar la detección: elementos de video/canvas no disponibles');
        return;
    }
    
    if (faceInterval) {
        clearInterval(faceInterval);
    }
    
    console.log('Iniciando detección facial con BlazeFace');
    
    faceInterval = setInterval(async () => {
        // Verificamos que todo esté listo antes de procesar
        if (isProcessing || !blazeFaceModel || !videoElement || !videoElement.videoWidth) {
            return;
        }
        
        isProcessing = true;
        
        try {
            const returnTensors = false;
            const predictions = await blazeFaceModel.estimateFaces(videoElement, returnTensors);
            
            // Asegurarnos de que el canvas sigue disponible
            if (!canvasElement) {
                isProcessing = false;
                return;
            }
            
            // Dibuja en el canvas
            const ctx = canvasElement.getContext('2d');
            ctx.clearRect(0, 0, canvasElement.width, canvasElement.height);
            
            // Primero dibuja el video en el canvas
            ctx.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            
            if (predictions.length > 0) {
                // Toma la primera cara detectada (la más prominente)
                const face = predictions[0];
                
                // Calcula la confianza media (usando probabilidad y tamaño relativo)
                const probability = face.probability[0] || 0;
                
                // Calcula el tamaño relativo de la cara (comparado con el frame)
                const faceWidth = face.bottomRight[0] - face.topLeft[0];
                const faceHeight = face.bottomRight[1] - face.topLeft[1];
                const videoArea = canvasElement.width * canvasElement.height;
                const faceArea = faceWidth * faceHeight;
                const sizeRatio = Math.min(1, faceArea / (videoArea * 0.15)); // 15% del área es ideal
                
                // Combina probabilidad y tamaño para la confianza general
                detectionConfidence = probability * 0.7 + sizeRatio * 0.3;
                
                // Dibuja el rectángulo alrededor del rostro
                ctx.beginPath();
                ctx.lineWidth = 2;
                
                // Color basado en la confianza
                if (detectionConfidence >= MIN_CONFIDENCE_THRESHOLD) {
                    ctx.strokeStyle = '#00FF00'; // Verde
                    if (!faceDetected) {
                        faceDetected = true;
                        updateStatus('Rostro detectado - Mantenga posición', 'text-success');
                        attemptAutoCapture();
                    }
                } else {
                    ctx.strokeStyle = '#FFA500'; // Naranja
                    faceDetected = false;
                    updateStatus('Ajuste posición para mejor detección', 'text-warning');
                    clearTimeout(captureTimeout);
                }
                
                // Dibuja el cuadro alrededor de la cara
                ctx.rect(
                    face.topLeft[0], 
                    face.topLeft[1], 
                    faceWidth, 
                    faceHeight
                );
                ctx.stroke();
                
                // Dibuja puntos faciales
                const landmarks = [
                    face.landmarks[0], // ojo derecho
                    face.landmarks[1], // ojo izquierdo
                    face.landmarks[2], // nariz
                    face.landmarks[3], // boca derecha
                    face.landmarks[4], // boca izquierda
                    face.landmarks[5]  // oreja derecha (si está disponible)
                ];
                
                landmarks.forEach(point => {
                    if (point) {
                        ctx.beginPath();
                        ctx.arc(point[0], point[1], 3, 0, 2 * Math.PI);
                        ctx.fillStyle = '#FF0000';
                        ctx.fill();
                    }
                });
                
                // Actualizar el indicador de confianza
                updateConfidence(detectionConfidence * 100);
                
            } else {
                faceDetected = false;
                detectionConfidence = 0;
                updateStatus('No se detecta rostro', 'text-secondary');
                updateConfidence(0);
                clearTimeout(captureTimeout);
            }
            
        } catch (error) {
            console.error('Error en la detección facial:', error);
        }
        
        isProcessing = false;
    }, FACE_CHECK_INTERVAL);
}

/**
 * Intenta capturar automáticamente cuando la confianza es suficiente
 */
function attemptAutoCapture() {
    if (detectionConfidence >= MIN_CONFIDENCE_THRESHOLD && faceData.length < MAX_CAPTURES) {
        clearTimeout(captureTimeout);
        captureTimeout = setTimeout(() => {
            if (detectionConfidence >= MIN_CONFIDENCE_THRESHOLD) {
                captureFace();
            }
        }, CAPTURE_INTERVAL);
    }
}

/**
 * Captura el rostro actual
 */
function captureFace() {
    if (faceData.length >= MAX_CAPTURES || !faceDetected || detectionConfidence < MIN_CONFIDENCE_THRESHOLD) {
        return;
    }
    
    try {
        // Obtener referencia al canvas
        const canvas = document.getElementById('faceCanvas');
        if (!canvas) {
            console.error('No se puede capturar: elemento canvas no encontrado');
            return;
        }
        
        const ctx = canvas.getContext('2d');
        
        // Almacena los datos de la imagen como base64
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        faceData.push(imageData);
        
        // Actualiza el progreso
        const progress = (faceData.length / MAX_CAPTURES) * 100;
        updateFaceProgress(progress);
        
        // Actualizar el contador de capturas
        const capturesElement = document.getElementById('faceCaptures');
        if (capturesElement) {
            capturesElement.textContent = faceData.length;
        }
        
        // Efecto visual de captura
        flashEffect();
        
        // Comprueba si hemos completado todas las capturas
        if (faceData.length >= MAX_CAPTURES) {
            updateStatus('Capturas completas - Puede guardar', 'text-success');
            
            // Habilita el botón de guardar
            const saveButton = document.getElementById('saveEnrollment');
            if (saveButton) {
                saveButton.disabled = false;
            }
            
            // Muestra un mensaje de confirmación
            showAlert('Enrolamiento facial completo. Pulse "Guardar" para completar el proceso.', 'success');
            
            // Detiene la cámara automáticamente
            stopFaceCamera();
        } else {
            // Continúa con la siguiente captura
            updateStatus(`Captura ${faceData.length}/${MAX_CAPTURES} realizada`, 'text-success');
            
            // Programa la siguiente captura
            attemptAutoCapture();
        }
    } catch (error) {
        console.error('Error al capturar rostro:', error);
        updateStatus('Error al capturar rostro', 'text-danger');
    }
}

/**
 * Detiene la cámara y limpia los recursos
 */
function stopFaceCamera() {
    console.log('Deteniendo cámara y recursos...');
    
    try {
        // Detiene el intervalo de detección
        if (faceInterval) {
            clearInterval(faceInterval);
            faceInterval = null;
            console.log('Intervalo de detección detenido');
        }
        
        // Limpia el timeout de captura
        if (captureTimeout) {
            clearTimeout(captureTimeout);
            captureTimeout = null;
            console.log('Timeout de captura cancelado');
        }
        
        // Detiene el stream de video con manejo de errores mejorado
        if (faceStream) {
            try {
                const tracks = faceStream.getTracks();
                console.log(`Deteniendo ${tracks.length} tracks de cámara`);
                
                tracks.forEach(track => {
                    try {
                        track.stop();
                        console.log(`Track ${track.kind} detenido correctamente`);
                    } catch (trackError) {
                        console.warn(`Error al detener track ${track.kind}:`, trackError);
                    }
                });
            } catch (streamError) {
                console.warn('Error al acceder a tracks del stream:', streamError);
            }
            faceStream = null;
        }
        
        // Obtenemos las referencias a los elementos nuevamente por si han cambiado
        const videoElementRef = document.getElementById('faceVideo');
        const canvasElementRef = document.getElementById('faceCanvas');
        const startButtonRef = document.getElementById('startFaceCamera');
        const stopButtonRef = document.getElementById('stopFaceCamera');
        
        // Limpia el video con pausado previo para evitar errores
        if (videoElementRef) {
            try {
                if (videoElementRef.srcObject) {
                    videoElementRef.pause(); // Pausar video antes de limpiar para evitar errores
                }
                videoElementRef.srcObject = null;
                console.log('Elemento de video limpiado correctamente');
            } catch (videoError) {
                console.warn('Error al limpiar video:', videoError);
            }
        }
        
        // Limpia el canvas
        if (canvasElementRef) {
            try {
                const ctx = canvasElementRef.getContext('2d');
                ctx.clearRect(0, 0, canvasElementRef.width, canvasElementRef.height);
                console.log('Canvas limpiado correctamente');
            } catch (canvasError) {
                console.warn('Error al limpiar canvas:', canvasError);
            }
        }
        
        // Actualiza los botones
        try {
            if (startButtonRef) {
                startButtonRef.disabled = false;
                console.log('Botón de inicio habilitado');
            }
            
            if (stopButtonRef) {
                stopButtonRef.disabled = true;
                console.log('Botón de parada deshabilitado');
            }
            
            // Actualizar referencias globales
            startButton = startButtonRef;
            stopButton = stopButtonRef;
        } catch (buttonError) {
            console.warn('Error al actualizar estado de botones:', buttonError);
        }
        
        // Actualiza estado
        updateStatus('Cámara detenida', 'text-secondary');
        try {
            updateConfidence(0);
        } catch (error) {
            console.warn('Error al actualizar confianza:', error);
        }
        
        console.log('Proceso de limpieza completado correctamente');
    } catch (error) {
        console.error('Error general al detener la cámara:', error);
    }
    
    console.log('Cámara detenida correctamente');
}

/**
 * Guarda los datos del enrolamiento
 */
function saveEnrollment() {
    if (faceData.length === 0) {
        showAlert('No hay datos faciales para guardar. Por favor capture su rostro primero.', 'warning');
        return;
    }
    
    // Verifica que tengamos un ID de empleado
    if (!currentEmployeeId) {
        currentEmployeeId = document.getElementById('employee_id').value || 
                           document.getElementById('hidden_employee_id').value;
        
        if (!currentEmployeeId) {
            showAlert('No se puede guardar: ID de empleado no encontrado', 'danger');
            return;
        }
    }
    
    // Prepara los datos para el envío
    const enrollmentData = {
        employee_id: currentEmployeeId,
        biometric_type: 'face',
        biometric_data: faceData,
        additional_info: {
            timestamp: new Date().toISOString(),
            device_info: navigator.userAgent
        }
    };
    
    // Mostrar indicador de carga
    showAlert('Guardando datos biométricos...', 'info');
    
    // Envío a servidor mediante AJAX
    $.ajax({
        url: 'api/employee/save_biometric.php', // Ajusta esta URL según tu estructura
        type: 'POST',
        data: JSON.stringify(enrollmentData),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Enrolamiento biométrico guardado exitosamente', 'success');
                
                // Cierra el modal después de un breve retraso
                setTimeout(() => {
                    $('#biometricEnrollmentModal').modal('hide');
                    
                    // Opcionalmente, recargar la página o actualizar la información
                    // location.reload();
                }, 1500);
            } else {
                showAlert('Error al guardar: ' + (response.message || 'Error desconocido'), 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            showAlert('Error de comunicación con el servidor', 'danger');
        }
    });
}

/**
 * Muestra un efecto de flash al capturar
 */
function flashEffect() {
    // Crea un elemento div para el flash
    const flash = document.createElement('div');
    flash.className = 'camera-flash';
    flash.style.position = 'absolute';
    flash.style.top = '0';
    flash.style.left = '0';
    flash.style.right = '0';
    flash.style.bottom = '0';
    flash.style.backgroundColor = 'white';
    flash.style.opacity = '0.7';
    flash.style.zIndex = '10';
    flash.style.pointerEvents = 'none';
    
    // Añade el flash a la cámara
    const cameraContainer = document.querySelector('.camera-container');
    cameraContainer.appendChild(flash);
    
    // Reproduce un sonido de cámara si es posible
    try {
        const shutterSound = new Audio('/assets/sounds/camera-shutter.mp3');
        shutterSound.play().catch(e => console.log('No se pudo reproducir sonido'));
    } catch (e) {
        // Ignora errores de sonido
    }
    
    // Elimina el flash después de un breve momento
    setTimeout(() => {
        cameraContainer.removeChild(flash);
    }, 150);
}

/**
 * Actualiza el estado de la detección facial
 */
function updateStatus(message, className = '') {
    try {
        // Buscar el elemento cada vez para asegurar que está disponible
        const detectionStatusElement = document.getElementById('face-detection-status');
        if (detectionStatusElement) {
            detectionStatusElement.textContent = message;
            detectionStatusElement.className = '';
            if (className) {
                detectionStatusElement.classList.add(className);
            }
            console.log(`Estado actualizado: ${message}`);
        } else {
            console.warn(`No se pudo actualizar estado: "${message}" - Elemento no encontrado`);
        }
    } catch (error) {
        console.error('Error al actualizar estado:', error);
    }
}

/**
 * Actualiza el indicador de confianza
 */
function updateConfidence(value) {
    const confidenceElement = document.getElementById('face-detection-confidence');
    if (confidenceElement) {
        const roundedValue = Math.round(value);
        confidenceElement.textContent = `${roundedValue}%`;
        
        // Cambia el color basado en el valor
        confidenceElement.className = '';
        if (value >= 90) {
            confidenceElement.classList.add('text-success');
        } else if (value >= 70) {
            confidenceElement.classList.add('text-info');
        } else if (value >= 50) {
            confidenceElement.classList.add('text-warning');
        } else {
            confidenceElement.classList.add('text-danger');
        }
    }
}

/**
 * Actualiza la barra de progreso
 */
function updateFaceProgress(percentage) {
    const progressBar = document.getElementById('faceProgress');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
        progressBar.setAttribute('aria-valuenow', percentage);
    }
}

/**
 * Muestra una alerta en el modal
 */
function showAlert(message, type = 'info') {
    // Busca o crea el contenedor de alertas
    let alertContainer = document.getElementById('enrollment-alerts');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'enrollment-alerts';
        alertContainer.className = 'mt-3';
        
        // Inserta después de los controles de la cámara
        const cameraControls = document.querySelector('.camera-controls');
        if (cameraControls && cameraControls.parentNode) {
            cameraControls.parentNode.insertBefore(alertContainer, cameraControls.nextSibling);
        }
    }
    
    // Crea la alerta
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Añade la alerta
    alertContainer.innerHTML = alertHtml + alertContainer.innerHTML;
    
    // Auto-elimina después de un tiempo
    setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando sistema biométrico BlazeFace');
    
    // Inicializar escuchadores de eventos cuando el DOM está completamente cargado
    function initializeEventListeners() {
        console.log('Inicializando escuchadores de eventos');
        
        // Asignar evento al botón de inicio de cámara
        const startCameraButton = document.getElementById('startFaceCamera');
        if (startCameraButton) {
            console.log('Botón de inicio de cámara encontrado');
            startCameraButton.addEventListener('click', startFaceCamera);
        } else {
            console.error('No se encontró el botón de inicio de cámara');
        }
        
        // Asignar evento al botón de detener cámara
        const stopCameraButton = document.getElementById('stopFaceCamera');
        if (stopCameraButton) {
            console.log('Botón de detener cámara encontrado');
            stopCameraButton.addEventListener('click', stopFaceCamera);
        } else {
            console.error('No se encontró el botón de detener cámara');
        }
        
        // Botón de guardar enrolamiento
        const saveButton = document.getElementById('saveEnrollment');
        if (saveButton) {
            console.log('Botón de guardar encontrado');
            saveButton.addEventListener('click', saveEnrollment);
        } else {
            console.error('No se encontró el botón de guardar');
        }
    }
    
    // Inicializar inmediatamente si el modal ya está en el DOM
    initializeEventListeners();
    
    // También inicializar cuando se muestre el modal (por si se carga dinámicamente)
    $(document).on('shown.bs.modal', '#biometricEnrollmentModal', function() {
        console.log('Modal mostrado - Preparando sistema biométrico');
        // Re-inicializar los escuchadores en caso de que los elementos recién se hayan cargado
        initializeEventListeners();
        // Precargar el modelo de BlazeFace
        initBlazeFace();
        
        // Mostrar ID del empleado si existe
        const employeeIdElement = document.getElementById('employee_id') || 
                               document.getElementById('hidden_employee_id') || 
                               document.getElementById('current-employee-id');
        
        if (employeeIdElement && employeeIdElement.value) {
            const displayEmployeeId = document.getElementById('display-employee-id');
            if (displayEmployeeId) {
                displayEmployeeId.textContent = employeeIdElement.value;
            }
        }
    });
    
    // Maneja el cierre del modal para limpiar recursos
    $(document).on('hidden.bs.modal', '#biometricEnrollmentModal', function() {
        console.log('Modal cerrado - Limpiando recursos');
        stopFaceCamera();
    });
});
