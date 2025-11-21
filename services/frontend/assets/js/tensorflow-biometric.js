/**
 * Integración de TensorFlow.js para Reconocimiento de Manos/Huellas
 * Sistema gratuito usando HandPose y PoseNet
 */

class TensorFlowBiometricAPI {
    constructor() {
        this.models = {
            handpose: null,
            posenet: null,
            facemesh: null
        };
        this.isLoaded = false;
        
        this.init();
    }
    
    async init() {
        try {
            console.log('🔧 Inicializando TensorFlow.js Biometric API...');
            
            // Cargar TensorFlow.js
            if (!window.tf) {
                await this.loadScript('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.10.0/dist/tf.min.js');
            }
            
            // Cargar modelos específicos
            await Promise.all([
                this.loadHandPoseModel(),
                this.loadPoseNetModel(),
                this.loadFaceMeshModel()
            ]);
            
            this.isLoaded = true;
            console.log('✅ TensorFlow.js Biometric API cargado correctamente');
            
        } catch (error) {
            console.error('❌ Error cargando TensorFlow.js:', error);
        }
    }
    
    async loadHandPoseModel() {
        try {
            if (!window.handpose) {
                await this.loadScript('https://cdn.jsdelivr.net/npm/@tensorflow-models/handpose@0.0.7/dist/handpose.min.js');
            }
            
            this.models.handpose = await handpose.load();
            console.log('📱 HandPose model cargado');
            
        } catch (error) {
            console.error('Error cargando HandPose:', error);
        }
    }
    
    async loadPoseNetModel() {
        try {
            if (!window.posenet) {
                await this.loadScript('https://cdn.jsdelivr.net/npm/@tensorflow-models/posenet@2.2.2/dist/posenet.min.js');
            }
            
            this.models.posenet = await posenet.load();
            console.log('🏃 PoseNet model cargado');
            
        } catch (error) {
            console.error('Error cargando PoseNet:', error);
        }
    }
    
    async loadFaceMeshModel() {
        try {
            if (!window.facemesh) {
                await this.loadScript('https://cdn.jsdelivr.net/npm/@tensorflow-models/facemesh@0.0.5/dist/facemesh.min.js');
            }
            
            this.models.facemesh = await facemesh.load();
            console.log('👤 FaceMesh model cargado');
            
        } catch (error) {
            console.error('Error cargando FaceMesh:', error);
        }
    }
    
    async loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Verificar si la API está lista para usar
     */
    isReady() {
        return this.isLoaded && 
               this.models.facemesh !== null && 
               this.models.handpose !== null;
    }
    
    /**
     * Obtener información del sistema
     */
    getSystemInfo() {
        return {
            loaded: this.isLoaded,
            models: {
                handpose: !!this.models.handpose,
                posenet: !!this.models.posenet,
                facemesh: !!this.models.facemesh
            },
            ready: this.isReady()
        };
    }
    
    /**
     * Análisis de mano para simulación de huella dactilar
     */
    async analyzeHandprint(imageElement) {
        if (!this.models.handpose) {
            throw new Error('HandPose model no está cargado');
        }
        
        try {
            const predictions = await this.models.handpose.estimateHands(imageElement);
            
            if (predictions.length === 0) {
                throw new Error('No se detectó ninguna mano');
            }
            
            const hand = predictions[0];
            const landmarks = hand.landmarks;
            
            // Extraer características únicas de la mano
            const handFeatures = this.extractHandFeatures(landmarks);
            
            // Simular patrón de huella dactilar basado en geometría de la mano
            const fingerprintPattern = this.generateFingerprintPattern(landmarks);
            
            return {
                method: 'tensorflow-handpose',
                landmarks: landmarks,
                handFeatures: handFeatures,
                fingerprintPattern: fingerprintPattern,
                confidence: hand.handInViewConfidence,
                fingerPositions: this.identifyFingerPositions(landmarks),
                minutiae: this.extractMinutiae(fingerprintPattern),
                quality: this.calculateHandQuality(landmarks),
                timestamp: Date.now()
            };
            
        } catch (error) {
            console.error('Error en análisis de mano:', error);
            throw error;
        }
    }
    
    extractHandFeatures(landmarks) {
        // Extraer características geométricas únicas de la mano
        const features = {};
        
        // Distancias entre puntos clave
        features.palmLength = this.calculateDistance(landmarks[0], landmarks[9]);
        features.palmWidth = this.calculateDistance(landmarks[5], landmarks[17]);
        
        // Longitudes de dedos
        features.thumbLength = this.calculateDistance(landmarks[1], landmarks[4]);
        features.indexLength = this.calculateDistance(landmarks[5], landmarks[8]);
        features.middleLength = this.calculateDistance(landmarks[9], landmarks[12]);
        features.ringLength = this.calculateDistance(landmarks[13], landmarks[16]);
        features.pinkyLength = this.calculateDistance(landmarks[17], landmarks[20]);
        
        // Ángulos entre dedos
        features.thumbIndexAngle = this.calculateAngle(landmarks[4], landmarks[0], landmarks[8]);
        features.indexMiddleAngle = this.calculateAngle(landmarks[8], landmarks[0], landmarks[12]);
        
        // Ratios únicos
        features.palmRatio = features.palmLength / features.palmWidth;
        features.fingerSpread = this.calculateFingerSpread(landmarks);
        
        return features;
    }
    
    generateFingerprintPattern(landmarks) {
        // Generar patrón simulado de huella dactilar basado en la geometría de la mano
        const pattern = {
            type: 'whorl', // loop, arch, whorl
            corePoints: [],
            ridgeEndings: [],
            bifurcations: []
        };
        
        // Simular puntos característicos basados en landmarks
        const fingertipIndices = [4, 8, 12, 16, 20]; // Puntas de los dedos
        
        fingertipIndices.forEach((index, fingerIndex) => {
            const fingertip = landmarks[index];
            
            // Generar puntos centrales (cores)
            pattern.corePoints.push({
                x: fingertip[0] + (Math.random() - 0.5) * 10,
                y: fingertip[1] + (Math.random() - 0.5) * 10,
                finger: fingerIndex,
                confidence: 0.8 + Math.random() * 0.2
            });
            
            // Generar terminaciones de cresta
            for (let i = 0; i < 5; i++) {
                pattern.ridgeEndings.push({
                    x: fingertip[0] + (Math.random() - 0.5) * 20,
                    y: fingertip[1] + (Math.random() - 0.5) * 20,
                    angle: Math.random() * 360,
                    finger: fingerIndex
                });
            }
            
            // Generar bifurcaciones
            for (let i = 0; i < 3; i++) {
                pattern.bifurcations.push({
                    x: fingertip[0] + (Math.random() - 0.5) * 15,
                    y: fingertip[1] + (Math.random() - 0.5) * 15,
                    angle1: Math.random() * 360,
                    angle2: Math.random() * 360,
                    finger: fingerIndex
                });
            }
        });
        
        return pattern;
    }
    
    extractMinutiae(fingerprintPattern) {
        // Extraer minutiae del patrón de huella
        const minutiae = [];
        
        // Agregar terminaciones como minutiae
        fingerprintPattern.ridgeEndings.forEach(ending => {
            minutiae.push({
                type: 'ending',
                x: ending.x,
                y: ending.y,
                angle: ending.angle,
                finger: ending.finger,
                quality: 0.8 + Math.random() * 0.2
            });
        });
        
        // Agregar bifurcaciones como minutiae
        fingerprintPattern.bifurcations.forEach(bifurcation => {
            minutiae.push({
                type: 'bifurcation',
                x: bifurcation.x,
                y: bifurcation.y,
                angle: (bifurcation.angle1 + bifurcation.angle2) / 2,
                finger: bifurcation.finger,
                quality: 0.7 + Math.random() * 0.3
            });
        });
        
        return minutiae;
    }
    
    identifyFingerPositions(landmarks) {
        // Identificar posiciones de los dedos
        const fingers = {
            thumb: landmarks.slice(1, 5),
            index: landmarks.slice(5, 9),
            middle: landmarks.slice(9, 13),
            ring: landmarks.slice(13, 17),
            pinky: landmarks.slice(17, 21)
        };
        
        const positions = {};
        
        Object.keys(fingers).forEach(fingerName => {
            const fingerLandmarks = fingers[fingerName];
            positions[fingerName] = {
                base: fingerLandmarks[0],
                tip: fingerLandmarks[fingerLandmarks.length - 1],
                joints: fingerLandmarks,
                extended: this.isFingerExtended(fingerLandmarks)
            };
        });
        
        return positions;
    }
    
    isFingerExtended(fingerLandmarks) {
        // Determinar si el dedo está extendido basado en la posición de las articulaciones
        const base = fingerLandmarks[0];
        const tip = fingerLandmarks[fingerLandmarks.length - 1];
        const middle = fingerLandmarks[Math.floor(fingerLandmarks.length / 2)];
        
        // Calcular si el dedo está "recto" (extendido)
        const baseTipDistance = this.calculateDistance(base, tip);
        const baseMiddleDistance = this.calculateDistance(base, middle);
        const middleTipDistance = this.calculateDistance(middle, tip);
        
        // Si la suma de los segmentos es aproximadamente igual a la distancia directa, está extendido
        const segmentSum = baseMiddleDistance + middleTipDistance;
        const straightnessRatio = baseTipDistance / segmentSum;
        
        return straightnessRatio > 0.85; // Umbral para considerar extendido
    }
    
    calculateHandQuality(landmarks) {
        // Calcular calidad de la detección de la mano
        let quality = 1.0;
        
        // Verificar que todos los landmarks estén presentes
        if (landmarks.length < 21) {
            quality *= 0.5;
        }
        
        // Verificar que los puntos estén en posiciones lógicas
        const palmCenter = landmarks[0];
        let validPositions = 0;
        
        landmarks.forEach(point => {
            const distance = this.calculateDistance(palmCenter, point);
            if (distance > 0 && distance < 200) { // Rango razonable
                validPositions++;
            }
        });
        
        quality *= (validPositions / landmarks.length);
        
        // Factor de visibilidad (simulado)
        quality *= (0.8 + Math.random() * 0.2);
        
        return Math.max(0.1, Math.min(1.0, quality));
    }
    
    calculateDistance(point1, point2) {
        return Math.sqrt(
            Math.pow(point1[0] - point2[0], 2) + 
            Math.pow(point1[1] - point2[1], 2)
        );
    }
    
    calculateAngle(point1, center, point2) {
        const vector1 = [point1[0] - center[0], point1[1] - center[1]];
        const vector2 = [point2[0] - center[0], point2[1] - center[1]];
        
        const dot = vector1[0] * vector2[0] + vector1[1] * vector2[1];
        const mag1 = Math.sqrt(vector1[0] * vector1[0] + vector1[1] * vector1[1]);
        const mag2 = Math.sqrt(vector2[0] * vector2[0] + vector2[1] * vector2[1]);
        
        const cosAngle = dot / (mag1 * mag2);
        return Math.acos(Math.max(-1, Math.min(1, cosAngle))) * (180 / Math.PI);
    }
    
    calculateFingerSpread(landmarks) {
        // Calcular separación entre dedos
        const fingertips = [landmarks[4], landmarks[8], landmarks[12], landmarks[16], landmarks[20]];
        let totalSpread = 0;
        
        for (let i = 0; i < fingertips.length - 1; i++) {
            totalSpread += this.calculateDistance(fingertips[i], fingertips[i + 1]);
        }
        
        return totalSpread / (fingertips.length - 1);
    }
    
    /**
     * Análisis facial usando TensorFlow FaceMesh
     */
    async analyzeFaceMesh(imageElement) {
        if (!this.models.facemesh) {
            throw new Error('FaceMesh model no está cargado');
        }
        
        try {
            const predictions = await this.models.facemesh.estimateFaces(imageElement);
            
            if (predictions.length === 0) {
                throw new Error('No se detectó ningún rostro');
            }
            
            const face = predictions[0];
            const mesh = face.scaledMesh;
            
            return {
                method: 'tensorflow-facemesh',
                mesh: mesh,
                boundingBox: face.boundingBox,
                faceInViewConfidence: face.faceInViewConfidence,
                faceGeometry: this.calculateAdvancedFaceGeometry(mesh),
                landmarks468: this.extract468Landmarks(mesh),
                quality: this.calculateFaceQuality(face),
                timestamp: Date.now()
            };
            
        } catch (error) {
            console.error('Error en análisis facial:', error);
            throw error;
        }
    }
    
    calculateAdvancedFaceGeometry(mesh) {
        // Calcular geometría facial avanzada con 468 puntos
        const landmarks = {
            leftEye: mesh[33],
            rightEye: mesh[263],
            noseTip: mesh[1],
            mouthCenter: mesh[13],
            chin: mesh[175],
            forehead: mesh[10]
        };
        
        return {
            eyeDistance: this.calculateDistance(landmarks.leftEye, landmarks.rightEye),
            noseLength: this.calculateDistance(landmarks.forehead, landmarks.noseTip),
            mouthWidth: this.calculateDistance(mesh[61], mesh[291]),
            faceWidth: this.calculateDistance(mesh[234], mesh[454]),
            faceHeight: this.calculateDistance(landmarks.forehead, landmarks.chin),
            jawWidth: this.calculateDistance(mesh[172], mesh[397]),
            eyebrowDistance: this.calculateDistance(mesh[70], mesh[300])
        };
    }
    
    extract468Landmarks(mesh) {
        // Extraer puntos clave específicos de los 468 landmarks
        const keyLandmarks = {
            contour: mesh.slice(0, 17),
            leftEyebrow: mesh.slice(17, 22),
            rightEyebrow: mesh.slice(22, 27),
            noseBridge: mesh.slice(27, 31),
            lowerNose: mesh.slice(31, 36),
            leftEye: mesh.slice(36, 42),
            rightEye: mesh.slice(42, 48),
            outerLip: mesh.slice(48, 60),
            innerLip: mesh.slice(60, 68)
        };
        
        return keyLandmarks;
    }
    
    calculateFaceQuality(face) {
        let quality = face.faceInViewConfidence || 0.8;
        
        // Verificar que la cara esté bien centrada
        const bbox = face.boundingBox;
        const centerX = bbox.topLeft[0] + bbox.bottomRight[0] / 2;
        const centerY = bbox.topLeft[1] + bbox.bottomRight[1] / 2;
        
        // Penalizar si la cara está muy cerca de los bordes
        const imageWidth = 640; // Asumir tamaño estándar
        const imageHeight = 480;
        
        if (centerX < imageWidth * 0.2 || centerX > imageWidth * 0.8 ||
            centerY < imageHeight * 0.2 || centerY > imageHeight * 0.8) {
            quality *= 0.7;
        }
        
        // Factor de tamaño de cara
        const faceArea = (bbox.bottomRight[0] - bbox.topLeft[0]) * 
                        (bbox.bottomRight[1] - bbox.topLeft[1]);
        const imageArea = imageWidth * imageHeight;
        const sizeRatio = faceArea / imageArea;
        
        if (sizeRatio < 0.1 || sizeRatio > 0.6) {
            quality *= 0.8;
        }
        
        return Math.max(0.1, Math.min(1.0, quality));
    }
    
    // Método público para verificar si los modelos están cargados
    isReady() {
        return this.isLoaded && 
               (this.models.handpose || this.models.facemesh || this.models.posenet);
    }
    
    // Obtener información del sistema
    getModelInfo() {
        return {
            loaded: this.isLoaded,
            models: {
                handpose: !!this.models.handpose,
                facemesh: !!this.models.facemesh,
                posenet: !!this.models.posenet
            },
            capabilities: {
                fingerprint_simulation: !!this.models.handpose,
                facial_recognition: !!this.models.facemesh,
                pose_detection: !!this.models.posenet
            }
        };
    }
}

// Inicializar TensorFlow API
window.TensorFlowBiometricAPI = new TensorFlowBiometricAPI();

// Export para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TensorFlowBiometricAPI;
}
