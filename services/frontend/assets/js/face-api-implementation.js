/**
 * Implementación completa de face-api.js para reconocimiento facial
 * Sistema biométrico gratuito y offline para SynkTime
 */

class FaceApiImplementation {
    constructor() {
        this.isModelLoaded = false;
        this.faceDescriptors = new Map(); // Cache de descriptores
        this.modelPath = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights';
        this.threshold = 0.6; // Umbral de similitud
        this.canvas = null;
        this.videoElement = null;
        this.detectionInterval = null;
        
        console.log('🔧 FaceApiImplementation inicializado');
    }
    
    /**
     * Cargar todos los modelos necesarios de face-api.js
     */
    async loadModels() {
        try {
            console.log('📥 Cargando modelos de face-api.js...');
            
            // Verificar si face-api.js está disponible
            if (typeof faceapi === 'undefined') {
                throw new Error('face-api.js no está cargado. Incluya el script en el HTML.');
            }
            
            // Cargar modelos necesarios
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(this.modelPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(this.modelPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(this.modelPath),
                faceapi.nets.faceExpressionNet.loadFromUri(this.modelPath)
            ]);
            
            this.isModelLoaded = true;
            console.log('✅ Modelos cargados exitosamente');
            
            return { success: true, message: 'Modelos cargados correctamente' };
            
        } catch (error) {
            console.error('❌ Error cargando modelos:', error);
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Inicializar cámara y canvas para detección
     */
    async initializeCamera(videoElement, canvasElement) {
        try {
            this.videoElement = videoElement;
            this.canvas = canvasElement;
            
            // Configurar video
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                }
            });
            
            this.videoElement.srcObject = stream;
            
            return new Promise((resolve) => {
                this.videoElement.onloadedmetadata = () => {
                    this.videoElement.play();
                    
                    // Configurar canvas
                    this.canvas.width = this.videoElement.videoWidth;
                    this.canvas.height = this.videoElement.videoHeight;
                    
                    console.log('✅ Cámara inicializada');
                    resolve({ success: true });
                };
            });
            
        } catch (error) {
            console.error('❌ Error inicializando cámara:', error);
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Detectar y extraer características faciales
     */
    async detectFace(imageElement) {
        try {
            if (!this.isModelLoaded) {
                throw new Error('Modelos no están cargados');
            }
            
            // Configurar opciones de detección
            const options = new faceapi.TinyFaceDetectorOptions({
                inputSize: 416,
                scoreThreshold: 0.5
            });
            
            // Detectar rostro con landmarks y descriptor
            const detection = await faceapi
                .detectSingleFace(imageElement, options)
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (!detection) {
                return { success: false, message: 'No se detectó ningún rostro' };
            }
            
            // Verificar calidad de la detección
            const quality = this.assessFaceQuality(detection);
            if (quality.score < 0.7) {
                return { 
                    success: false, 
                    message: `Calidad de imagen insuficiente: ${quality.issues.join(', ')}` 
                };
            }
            
            return {
                success: true,
                detection: detection,
                descriptor: Array.from(detection.descriptor),
                landmarks: detection.landmarks.positions,
                box: detection.detection.box,
                quality: quality
            };
            
        } catch (error) {
            console.error('❌ Error en detección facial:', error);
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Evaluar calidad del rostro detectado
     */
    assessFaceQuality(detection) {
        const issues = [];
        let score = 1.0;
        
        // Verificar tamaño del rostro
        const faceSize = detection.detection.box.width * detection.detection.box.height;
        if (faceSize < 10000) { // Rostro muy pequeño
            issues.push('rostro muy pequeño');
            score -= 0.3;
        }
        
        // Verificar confianza de detección
        if (detection.detection.score < 0.8) {
            issues.push('baja confianza de detección');
            score -= 0.2;
        }
        
        // Verificar orientación del rostro (usando landmarks)
        const landmarks = detection.landmarks.positions;
        const leftEye = landmarks[36]; // Ojo izquierdo
        const rightEye = landmarks[45]; // Ojo derecho
        const nose = landmarks[30]; // Nariz
        
        // Calcular inclinación
        const eyeDistance = Math.abs(leftEye.y - rightEye.y);
        if (eyeDistance > 20) {
            issues.push('rostro inclinado');
            score -= 0.2;
        }
        
        return {
            score: Math.max(0, score),
            issues: issues
        };
    }
    
    /**
     * Inscribir un nuevo rostro
     */
    async enrollFace(employeeId, imageElement) {
        try {
            const result = await this.detectFace(imageElement);
            
            if (!result.success) {
                return result;
            }
            
            // Guardar descriptor en cache local
            this.faceDescriptors.set(employeeId, {
                descriptor: result.descriptor,
                enrolledAt: new Date(),
                quality: result.quality
            });
            
            // Enviar al servidor
            const response = await fetch('api/biometric/enroll.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    biometric_type: 'facial',
                    biometric_data: JSON.stringify({
                        descriptor: result.descriptor,
                        landmarks: result.landmarks,
                        quality: result.quality,
                        method: 'face-api.js'
                    })
                })
            });
            
            const serverResult = await response.json();
            
            if (serverResult.success) {
                console.log(`✅ Rostro inscrito para empleado ${employeeId}`);
                return {
                    success: true,
                    message: 'Rostro inscrito exitosamente',
                    quality: result.quality
                };
            } else {
                return serverResult;
            }
            
        } catch (error) {
            console.error('❌ Error inscribiendo rostro:', error);
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Verificar un rostro contra la base de datos
     */
    async verifyFace(imageElement, employeeId = null) {
        try {
            const result = await this.detectFace(imageElement);
            
            if (!result.success) {
                return result;
            }
            
            const detectedDescriptor = result.descriptor;
            let bestMatch = null;
            let bestDistance = Infinity;
            
            if (employeeId) {
                // Verificar contra un empleado específico
                const match = await this.compareWithEmployee(detectedDescriptor, employeeId);
                if (match.success && match.distance < this.threshold) {
                    bestMatch = { employeeId, distance: match.distance };
                }
            } else {
                // Buscar en toda la base de datos
                const allMatches = await this.searchAllEmployees(detectedDescriptor);
                if (allMatches.length > 0) {
                    bestMatch = allMatches[0];
                }
            }
            
            // Registrar intento de verificación
            await this.logVerificationAttempt(detectedDescriptor, bestMatch, result.quality);
            
            if (bestMatch && bestMatch.distance < this.threshold) {
                const confidence = Math.max(0, (1 - bestMatch.distance) * 100);
                
                return {
                    success: true,
                    verified: true,
                    employee_id: bestMatch.employeeId,
                    confidence: confidence.toFixed(2),
                    distance: bestMatch.distance.toFixed(4),
                    quality: result.quality
                };
            } else {
                return {
                    success: true,
                    verified: false,
                    message: 'Rostro no reconocido',
                    quality: result.quality
                };
            }
            
        } catch (error) {
            console.error('❌ Error verificando rostro:', error);
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Comparar descriptor con un empleado específico
     */
    async compareWithEmployee(descriptor, employeeId) {
        try {
            // Verificar cache local primero
            if (this.faceDescriptors.has(employeeId)) {
                const stored = this.faceDescriptors.get(employeeId);
                const distance = this.calculateEuclideanDistance(descriptor, stored.descriptor);
                return { success: true, distance };
            }
            
            // Obtener del servidor
            const response = await fetch(`api/biometric/get-employee-biometric.php?employee_id=${employeeId}&type=facial`);
            const data = await response.json();
            
            if (data.success && data.biometric_data) {
                const storedData = JSON.parse(data.biometric_data);
                const storedDescriptor = storedData.descriptor;
                
                // Guardar en cache
                this.faceDescriptors.set(employeeId, {
                    descriptor: storedDescriptor,
                    quality: storedData.quality
                });
                
                const distance = this.calculateEuclideanDistance(descriptor, storedDescriptor);
                return { success: true, distance };
            }
            
            return { success: false, message: 'Datos biométricos no encontrados' };
            
        } catch (error) {
            console.error('❌ Error comparando con empleado:', error);
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Buscar en toda la base de datos de empleados
     */
    async searchAllEmployees(descriptor) {
        try {
            const response = await fetch('api/biometric/get-all-facial-data.php');
            const data = await response.json();
            
            if (!data.success) {
                return [];
            }
            
            const matches = [];
            
            for (const employee of data.employees) {
                try {
                    const storedData = JSON.parse(employee.biometric_data);
                    const storedDescriptor = storedData.descriptor;
                    const distance = this.calculateEuclideanDistance(descriptor, storedDescriptor);
                    
                    matches.push({
                        employeeId: employee.employee_id,
                        distance: distance,
                        name: employee.nombre_completo
                    });
                    
                    // Actualizar cache
                    this.faceDescriptors.set(employee.employee_id, {
                        descriptor: storedDescriptor,
                        quality: storedData.quality
                    });
                    
                } catch (parseError) {
                    console.warn(`Error procesando datos de empleado ${employee.employee_id}:`, parseError);
                }
            }
            
            // Ordenar por menor distancia (mayor similitud)
            matches.sort((a, b) => a.distance - b.distance);
            
            return matches;
            
        } catch (error) {
            console.error('❌ Error buscando empleados:', error);
            return [];
        }
    }
    
    /**
     * Calcular distancia euclidiana entre dos descriptores
     */
    calculateEuclideanDistance(desc1, desc2) {
        if (desc1.length !== desc2.length) {
            throw new Error('Descriptores de diferentes tamaños');
        }
        
        let sum = 0;
        for (let i = 0; i < desc1.length; i++) {
            const diff = desc1[i] - desc2[i];
            sum += diff * diff;
        }
        
        return Math.sqrt(sum);
    }
    
    /**
     * Iniciar detección en tiempo real
     */
    async startRealTimeDetection(callback) {
        if (!this.videoElement || !this.canvas) {
            throw new Error('Video y canvas no están inicializados');
        }
        
        const detect = async () => {
            if (this.videoElement.paused || this.videoElement.ended) {
                return;
            }
            
            try {
                const detection = await this.detectFace(this.videoElement);
                
                // Limpiar canvas
                const ctx = this.canvas.getContext('2d');
                ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                
                if (detection.success) {
                    // Dibujar box de detección
                    const { x, y, width, height } = detection.box;
                    ctx.strokeStyle = '#00ff00';
                    ctx.lineWidth = 3;
                    ctx.strokeRect(x, y, width, height);
                    
                    // Dibujar landmarks
                    ctx.fillStyle = '#ff0000';
                    detection.landmarks.forEach(point => {
                        ctx.fillRect(point.x - 2, point.y - 2, 4, 4);
                    });
                    
                    // Mostrar información de calidad
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '16px Arial';
                    ctx.fillText(`Calidad: ${(detection.quality.score * 100).toFixed(0)}%`, x, y - 10);
                }
                
                if (callback) {
                    callback(detection);
                }
                
            } catch (error) {
                console.error('Error en detección en tiempo real:', error);
            }
        };
        
        this.detectionInterval = setInterval(detect, 100); // 10 FPS
        console.log('🎥 Detección en tiempo real iniciada');
    }
    
    /**
     * Detener detección en tiempo real
     */
    stopRealTimeDetection() {
        if (this.detectionInterval) {
            clearInterval(this.detectionInterval);
            this.detectionInterval = null;
            
            // Limpiar canvas
            if (this.canvas) {
                const ctx = this.canvas.getContext('2d');
                ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            }
            
            console.log('🛑 Detección en tiempo real detenida');
        }
    }
    
    /**
     * Capturar foto del video
     */
    capturePhoto() {
        if (!this.videoElement) {
            throw new Error('Video no está inicializado');
        }
        
        const canvas = document.createElement('canvas');
        canvas.width = this.videoElement.videoWidth;
        canvas.height = this.videoElement.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(this.videoElement, 0, 0);
        
        return canvas;
    }
    
    /**
     * Registrar intento de verificación
     */
    async logVerificationAttempt(descriptor, match, quality) {
        try {
            await fetch('api/biometric/log-attempt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    method: 'face-api.js',
                    success: match !== null,
                    employee_id: match ? match.employeeId : null,
                    confidence: match ? (1 - match.distance) * 100 : 0,
                    quality: quality,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.warn('No se pudo registrar el intento:', error);
        }
    }
    
    /**
     * Limpiar recursos
     */
    cleanup() {
        this.stopRealTimeDetection();
        
        if (this.videoElement && this.videoElement.srcObject) {
            const tracks = this.videoElement.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            this.videoElement.srcObject = null;
        }
        
        this.faceDescriptors.clear();
        console.log('🧹 Recursos limpiados');
    }
    
    /**
     * Obtener estadísticas del sistema
     */
    getStats() {
        return {
            modelsLoaded: this.isModelLoaded,
            cachedDescriptors: this.faceDescriptors.size,
            threshold: this.threshold,
            isDetecting: this.detectionInterval !== null
        };
    }
}

// Exportar para uso global
window.FaceApiImplementation = FaceApiImplementation;

// Instancia global
window.faceApiSystem = null;

/**
 * Función helper para inicializar el sistema
 */
async function initializeFaceApiSystem() {
    try {
        if (!window.faceApiSystem) {
            window.faceApiSystem = new FaceApiImplementation();
        }
        
        const result = await window.faceApiSystem.loadModels();
        
        if (result.success) {
            console.log('🎉 Sistema FaceAPI listo para usar');
            return window.faceApiSystem;
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('❌ Error inicializando FaceAPI:', error);
        throw error;
    }
}

// Auto-inicializar si face-api.js está disponible
document.addEventListener('DOMContentLoaded', async () => {
    if (typeof faceapi !== 'undefined') {
        try {
            await initializeFaceApiSystem();
        } catch (error) {
            console.warn('No se pudo auto-inicializar FaceAPI:', error);
        }
    }
});

console.log('📄 face-api-implementation.js cargado');
