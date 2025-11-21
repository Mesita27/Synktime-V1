/**
 * SISTEMA BIOMÉTRICO OPTIMIZADO CON TENSORFLOW.JS
 * Versión: 3.0 - Reconocimiento Facial Avanzado
 * Autor: Sistema SynkTime
 * Fecha: Agosto 2025
 */

class TensorFlowBiometricSystem {
    constructor() {
        this.model = null;
        this.isModelLoaded = false;
        this.faceMatcher = null;
        this.enrolledFaces = new Map(); // Cache de rostros registrados
        
        // Configuración optimizada
        this.config = {
            detection: {
                minConfidence: 0.7,        // Confianza mínima para detección
                maxFaces: 1,               // Máximo rostros por frame
                inputSize: 416,            // Tamaño de entrada optimizado
                scoreThreshold: 0.5,       // Umbral de puntuación
                nmsThreshold: 0.3          // Non-maximum suppression
            },
            recognition: {
                threshold: 0.6,            // Umbral de reconocimiento
                descriptorLength: 128,     // Longitud del descriptor facial
                useMobileNet: true         // Usar MobileNet para velocidad
            },
            performance: {
                maxCacheSize: 50,          // Máximo rostros en cache
                analysisInterval: 500,     // Intervalo de análisis (ms)
                frameSkipping: 2           // Saltar frames para rendimiento
            }
        };
        
        this.stats = {
            detectionCount: 0,
            recognitionCount: 0,
            avgProcessingTime: 0,
            cacheHits: 0
        };
        
        this.initializeSystem();
    }
    
    /**
     * INICIALIZAR SISTEMA TENSORFLOW
     */
    async initializeSystem() {
        try {
            console.log('🚀 Inicializando TensorFlow Biometric System...');
            
            // Configurar TensorFlow.js
            await tf.ready();
            
            // Configurar backend optimizado
            if (await tf.getBackend() !== 'webgl') {
                await tf.setBackend('webgl');
            }
            
            // Cargar modelos de detección y reconocimiento
            await this.loadModels();
            
            console.log('✅ TensorFlow Biometric System listo');
            
        } catch (error) {
            console.error('❌ Error inicializando TensorFlow:', error);
            this.fallbackToOptimizedSystem();
        }
    }
    
    /**
     * CARGAR MODELOS DE TENSORFLOW
     */
    async loadModels() {
        try {
            // Cargar modelo de detección facial (BlazeFace o SSD MobileNet)
            this.detectionModel = await blazeface.load({
                maxFaces: this.config.detection.maxFaces,
                inputSize: this.config.detection.inputSize
            });
            
            // Cargar modelo de reconocimiento facial (FaceNet/MobileFaceNet)
            this.recognitionModel = await facemesh.load({
                maxFaces: this.config.detection.maxFaces
            });
            
            this.isModelLoaded = true;
            console.log('✅ Modelos TensorFlow cargados correctamente');
            
        } catch (error) {
            console.error('❌ Error cargando modelos:', error);
            throw error;
        }
    }
    
    /**
     * DETECCIÓN RÁPIDA DE ROSTROS
     */
    async detectFaces(videoElement) {
        if (!this.isModelLoaded) {
            throw new Error('Modelos no cargados');
        }
        
        const startTime = performance.now();
        
        try {
            // Crear tensor desde video
            const videoTensor = tf.browser.fromPixels(videoElement);
            
            // Detectar rostros usando BlazeFace
            const predictions = await this.detectionModel.estimateFaces(
                videoTensor, 
                false, // returnTensors
                this.config.detection.scoreThreshold
            );
            
            // Limpiar tensor
            videoTensor.dispose();
            
            // Procesar resultados
            const faces = predictions.map(prediction => ({
                confidence: prediction.probability?.[0] || 0.9,
                box: {
                    x: prediction.topLeft[0],
                    y: prediction.topLeft[1],
                    width: prediction.bottomRight[0] - prediction.topLeft[0],
                    height: prediction.bottomRight[1] - prediction.topLeft[1]
                },
                landmarks: prediction.landmarks,
                mesh: prediction.mesh
            }));
            
            const processingTime = performance.now() - startTime;
            this.updateStats('detection', processingTime);
            
            return {
                success: true,
                faces: faces,
                processingTime: processingTime,
                confidence: faces.length > 0 ? faces[0].confidence : 0
            };
            
        } catch (error) {
            console.error('Error en detección TensorFlow:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * EXTRACCIÓN DE CARACTERÍSTICAS FACIALES
     */
    async extractFaceDescriptor(videoElement, faceBox) {
        try {
            const startTime = performance.now();
            
            // Crear canvas con región facial
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Configurar tamaño del canvas
            const faceSize = 160; // Tamaño estándar para reconocimiento
            canvas.width = faceSize;
            canvas.height = faceSize;
            
            // Extraer región facial del video
            ctx.drawImage(
                videoElement,
                faceBox.x, faceBox.y, faceBox.width, faceBox.height,
                0, 0, faceSize, faceSize
            );
            
            // Crear tensor de la cara
            const faceTensor = tf.browser.fromPixels(canvas)
                .resizeNearestNeighbor([160, 160])
                .expandDims(0)
                .div(255.0);
            
            // Extraer descriptor usando modelo de reconocimiento
            const descriptor = await this.recognitionModel.predict(faceTensor);
            const descriptorArray = await descriptor.data();
            
            // Limpiar tensores
            faceTensor.dispose();
            descriptor.dispose();
            
            const processingTime = performance.now() - startTime;
            
            return {
                success: true,
                descriptor: Array.from(descriptorArray),
                processingTime: processingTime
            };
            
        } catch (error) {
            console.error('Error extrayendo descriptor:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * REGISTRO DE ROSTRO (ENROLAMIENTO)
     */
    async enrollFace(employeeId, videoElement) {
        try {
            console.log(`🔍 Iniciando enrolamiento para empleado ${employeeId}...`);
            
            // Detectar rostro
            const detection = await this.detectFaces(videoElement);
            if (!detection.success || detection.faces.length === 0) {
                throw new Error('No se detectó rostro válido para enrolamiento');
            }
            
            const face = detection.faces[0];
            if (face.confidence < this.config.detection.minConfidence) {
                throw new Error(`Confianza insuficiente: ${face.confidence.toFixed(2)}`);
            }
            
            // Extraer múltiples descriptores para mejor precisión
            const descriptors = [];
            for (let i = 0; i < 5; i++) {
                const descriptor = await this.extractFaceDescriptor(videoElement, face.box);
                if (descriptor.success) {
                    descriptors.push(descriptor.descriptor);
                }
                await new Promise(resolve => setTimeout(resolve, 200)); // Pausa entre capturas
            }
            
            if (descriptors.length < 3) {
                throw new Error('No se pudieron capturar suficientes muestras faciales');
            }
            
            // Calcular descriptor promedio
            const avgDescriptor = this.calculateAverageDescriptor(descriptors);
            
            // Guardar en base de datos
            const enrollResult = await this.saveFaceEnrollment(employeeId, avgDescriptor);
            
            // Agregar a cache local
            this.enrolledFaces.set(employeeId, {
                descriptor: avgDescriptor,
                enrolledAt: new Date(),
                sampleCount: descriptors.length
            });
            
            console.log(`✅ Enrolamiento completado para empleado ${employeeId}`);
            
            return {
                success: true,
                employeeId: employeeId,
                confidence: face.confidence,
                sampleCount: descriptors.length,
                message: 'Rostro registrado correctamente'
            };
            
        } catch (error) {
            console.error('Error en enrolamiento:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * VERIFICACIÓN DE IDENTIDAD
     */
    async verifyIdentity(videoElement, expectedEmployeeId = null) {
        try {
            const startTime = performance.now();
            
            // Detectar rostro actual
            const detection = await this.detectFaces(videoElement);
            if (!detection.success || detection.faces.length === 0) {
                return {
                    success: false,
                    message: 'No se detectó rostro'
                };
            }
            
            const face = detection.faces[0];
            if (face.confidence < this.config.detection.minConfidence) {
                return {
                    success: false,
                    message: `Calidad de imagen insuficiente: ${(face.confidence * 100).toFixed(1)}%`
                };
            }
            
            // Extraer descriptor del rostro actual
            const currentDescriptor = await this.extractFaceDescriptor(videoElement, face.box);
            if (!currentDescriptor.success) {
                throw new Error('Error extrayendo características faciales');
            }
            
            let bestMatch = null;
            let bestDistance = Infinity;
            
            // Si se especifica empleado, verificar solo contra él
            if (expectedEmployeeId) {
                const employeeData = await this.getEmployeeFaceData(expectedEmployeeId);
                if (employeeData) {
                    const distance = this.calculateEuclideanDistance(
                        currentDescriptor.descriptor,
                        employeeData.descriptor
                    );
                    
                    const similarity = Math.max(0, 1 - distance);
                    const isMatch = similarity >= this.config.recognition.threshold;
                    
                    return {
                        success: isMatch,
                        employeeId: expectedEmployeeId,
                        similarity: similarity,
                        confidence: face.confidence,
                        message: isMatch ? 'Identidad verificada' : 'Identidad no coincide',
                        processingTime: performance.now() - startTime
                    };
                }
            }
            
            // Buscar coincidencia en todos los empleados registrados
            for (const [employeeId, faceData] of this.enrolledFaces) {
                const distance = this.calculateEuclideanDistance(
                    currentDescriptor.descriptor,
                    faceData.descriptor
                );
                
                if (distance < bestDistance) {
                    bestDistance = distance;
                    bestMatch = employeeId;
                }
            }
            
            const similarity = bestMatch ? Math.max(0, 1 - bestDistance) : 0;
            const isMatch = similarity >= this.config.recognition.threshold;
            
            const result = {
                success: isMatch,
                employeeId: bestMatch,
                similarity: similarity,
                confidence: face.confidence,
                message: isMatch ? `Empleado identificado: ${bestMatch}` : 'Empleado no reconocido',
                processingTime: performance.now() - startTime
            };
            
            this.updateStats('recognition', performance.now() - startTime);
            
            return result;
            
        } catch (error) {
            console.error('Error en verificación:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * CALCULAR DESCRIPTOR PROMEDIO
     */
    calculateAverageDescriptor(descriptors) {
        const length = descriptors[0].length;
        const avgDescriptor = new Array(length).fill(0);
        
        for (const descriptor of descriptors) {
            for (let i = 0; i < length; i++) {
                avgDescriptor[i] += descriptor[i];
            }
        }
        
        for (let i = 0; i < length; i++) {
            avgDescriptor[i] /= descriptors.length;
        }
        
        return avgDescriptor;
    }
    
    /**
     * CALCULAR DISTANCIA EUCLIDIANA
     */
    calculateEuclideanDistance(desc1, desc2) {
        if (desc1.length !== desc2.length) {
            throw new Error('Descriptores de diferente longitud');
        }
        
        let sum = 0;
        for (let i = 0; i < desc1.length; i++) {
            const diff = desc1[i] - desc2[i];
            sum += diff * diff;
        }
        
        return Math.sqrt(sum);
    }
    
    /**
     * GUARDAR ENROLAMIENTO EN BD
     */
    async saveFaceEnrollment(employeeId, descriptor) {
        try {
            const response = await fetch('api/biometric/enroll.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    biometric_type: 'facial',
                    descriptor: descriptor,
                    model_version: 'tensorflow_v3'
                })
            });
            
            const result = await response.json();
            return result;
            
        } catch (error) {
            console.error('Error guardando enrolamiento:', error);
            throw error;
        }
    }
    
    /**
     * OBTENER DATOS FACIALES DE EMPLEADO
     */
    async getEmployeeFaceData(employeeId) {
        // Primero buscar en cache
        if (this.enrolledFaces.has(employeeId)) {
            this.stats.cacheHits++;
            return this.enrolledFaces.get(employeeId);
        }
        
        // Si no está en cache, obtener de BD
        try {
            const response = await fetch(`api/biometric/get-face-data.php?employee_id=${employeeId}`);
            const result = await response.json();
            
            if (result.success && result.data) {
                // Agregar a cache
                this.enrolledFaces.set(employeeId, {
                    descriptor: result.data.descriptor,
                    enrolledAt: new Date(result.data.created_at)
                });
                
                return this.enrolledFaces.get(employeeId);
            }
            
            return null;
            
        } catch (error) {
            console.error('Error obteniendo datos faciales:', error);
            return null;
        }
    }
    
    /**
     * CARGAR EMPLEADOS REGISTRADOS
     */
    async loadEnrolledEmployees() {
        try {
            const response = await fetch('api/biometric/get-enrolled-employees.php');
            const result = await response.json();
            
            if (result.success && result.employees) {
                this.enrolledFaces.clear();
                
                for (const employee of result.employees) {
                    this.enrolledFaces.set(employee.id, {
                        descriptor: employee.descriptor,
                        enrolledAt: new Date(employee.created_at)
                    });
                }
                
                console.log(`✅ Cargados ${this.enrolledFaces.size} empleados registrados`);
            }
            
        } catch (error) {
            console.error('Error cargando empleados registrados:', error);
        }
    }
    
    /**
     * FALLBACK AL SISTEMA OPTIMIZADO ANTERIOR
     */
    fallbackToOptimizedSystem() {
        console.warn('⚠️ Fallback al sistema optimizado anterior');
        if (window.OptimizedBiometricSystem) {
            this.fallbackSystem = new OptimizedBiometricSystem();
        }
    }
    
    /**
     * ACTUALIZAR ESTADÍSTICAS
     */
    updateStats(type, processingTime) {
        if (type === 'detection') {
            this.stats.detectionCount++;
        } else if (type === 'recognition') {
            this.stats.recognitionCount++;
        }
        
        // Calcular tiempo promedio
        const totalOps = this.stats.detectionCount + this.stats.recognitionCount;
        this.stats.avgProcessingTime = (this.stats.avgProcessingTime * (totalOps - 1) + processingTime) / totalOps;
    }
    
    /**
     * OBTENER ESTADÍSTICAS DEL SISTEMA
     */
    getSystemStats() {
        return {
            ...this.stats,
            modelsLoaded: this.isModelLoaded,
            enrolledEmployees: this.enrolledFaces.size,
            memoryUsage: tf.memory()
        };
    }
    
    /**
     * LIMPIAR RECURSOS
     */
    dispose() {
        if (this.detectionModel) {
            this.detectionModel.dispose();
        }
        if (this.recognitionModel) {
            this.recognitionModel.dispose();
        }
        this.enrolledFaces.clear();
    }
}

// Inicializar sistema global
window.TensorFlowBiometricSystem = TensorFlowBiometricSystem;
