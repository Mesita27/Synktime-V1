/**
 * SISTEMA BIOMÉTRICO OPTIMIZADO Y FLUIDO
 * Versión mejorada para análisis rápido y confiable
 */

class OptimizedBiometricSystem {
    constructor() {
        this.config = {
            detection: {
                minConfidence: 0.6,
                frameRate: 8, // FPS optimizado
                sampleSize: 0.25, // Analizar solo 25% de píxeles para velocidad
                skinTolerance: 0.15,
                lightingTolerance: 0.2
            },
            quality: {
                minBrightness: 50,
                maxBrightness: 220,
                minContrast: 30
            }
        };
        
        this.stats = {
            detectionCount: 0,
            successfulDetections: 0,
            lastAnalysisTime: 0
        };
        
        this.cache = {
            lastFrame: null,
            lastResult: null,
            frameSkipCount: 0
        };
        
        this.isReady = false;
        this.init();
    }
    
    async init() {
        try {
            // Verificar APIs disponibles
            this.availableAPIs = this.checkAvailableAPIs();
            this.isReady = true;
            console.log('✅ Sistema biométrico optimizado listo');
        } catch (error) {
            console.error('❌ Error inicializando sistema:', error);
        }
    }
    
    checkAvailableAPIs() {
        const apis = [];
        
        // 1. Face Detection API (nativo del navegador)
        if ('FaceDetector' in window) {
            apis.push('browser-native');
        }
        
        // 2. Canvas Analysis (siempre disponible)
        apis.push('canvas-analysis-optimized');
        
        // 3. Motion Detection (siempre disponible)
        apis.push('motion-detection');
        
        return apis;
    }
    
    isSystemReady() {
        return this.isReady;
    }
    
    getSystemInfo() {
        return {
            ready: this.isReady,
            availableAPIs: this.availableAPIs,
            stats: this.stats,
            performance: {
                avgAnalysisTime: this.stats.lastAnalysisTime,
                successRate: this.stats.detectionCount > 0 ? 
                    (this.stats.successfulDetections / this.stats.detectionCount * 100).toFixed(1) + '%' : '0%'
            }
        };
    }
    
    /**
     * DETECCIÓN RÁPIDA PARA TIEMPO REAL
     */
    async detectFace(videoElement, options = {}) {
        const startTime = performance.now();
        
        try {
            // Usar cache para frames similares
            if (this.shouldSkipFrame()) {
                return this.cache.lastResult || this.getDefaultResult();
            }
            
            let result;
            
            // Probar APIs en orden de preferencia
            if (this.availableAPIs.includes('browser-native')) {
                result = await this.detectWithNativeAPI(videoElement);
            } else {
                result = await this.quickCanvasDetection(videoElement);
            }
            
            // Actualizar cache y estadísticas
            this.cache.lastResult = result;
            this.cache.frameSkipCount = 0;
            this.stats.detectionCount++;
            if (result.detected) this.stats.successfulDetections++;
            
            this.stats.lastAnalysisTime = performance.now() - startTime;
            
            return result;
            
        } catch (error) {
            console.warn('Error en detección rápida:', error);
            return this.getDefaultResult(false, 'error');
        }
    }
    
    /**
     * ANÁLISIS COMPLETO PARA CAPTURA
     */
    async analyzeImage(videoElement, options = {}) {
        const startTime = performance.now();
        
        try {
            console.log('🔍 Iniciando análisis optimizado...');
            
            // Capturar frame optimizado
            const imageData = this.captureOptimizedFrame(videoElement);
            if (!imageData) {
                throw new Error('No se pudo capturar frame del video');
            }
            
            // Análisis multi-método
            const results = await Promise.all([
                this.fastSkinAnalysis(imageData),
                this.quickFeatureDetection(imageData),
                this.lightingQualityCheck(imageData)
            ]);
            
            // Combinar resultados
            const finalResult = this.combineAnalysisResults(results, {
                method: 'optimized-analysis',
                timestamp: Date.now(),
                analysisTime: performance.now() - startTime
            });
            
            console.log(`✅ Análisis completado en ${finalResult.analysisTime.toFixed(1)}ms`);
            
            return finalResult;
            
        } catch (error) {
            console.error('❌ Error en análisis:', error);
            return {
                success: false,
                detected: false,
                confidence: 0,
                method: 'optimized-analysis',
                message: error.message,
                timestamp: Date.now()
            };
        }
    }
    
    /**
     * CAPTURA OPTIMIZADA DE FRAME
     */
    captureOptimizedFrame(videoElement) {
        try {
            // Crear canvas temporal más pequeño para velocidad
            const canvas = document.createElement('canvas');
            const scale = 0.5; // Reducir resolución para velocidad
            
            canvas.width = videoElement.videoWidth * scale;
            canvas.height = videoElement.videoHeight * scale;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            
            return ctx.getImageData(0, 0, canvas.width, canvas.height);
            
        } catch (error) {
            console.error('Error capturando frame:', error);
            return null;
        }
    }
    
    /**
     * ANÁLISIS RÁPIDO DE PIEL - PANTALLA COMPLETA
     */
    fastSkinAnalysis(imageData) {
        return new Promise((resolve) => {
            const { data, width, height } = imageData;
            const sampleRate = Math.ceil(1 / this.config.detection.sampleSize);
            
            let skinPixels = 0;
            let totalSamples = 0;
            let avgBrightness = 0;
            let skinClusters = []; // Detectar agrupaciones de piel
            
            // ANÁLISIS DE TODA LA PANTALLA con detección de clusters
            for (let y = 0; y < height; y += Math.ceil(sampleRate / 2)) {
                for (let x = 0; x < width; x += Math.ceil(sampleRate / 2)) {
                    const index = (y * width + x) * 4;
                    
                    if (index < data.length - 3) {
                        const r = data[index];
                        const g = data[index + 1];
                        const b = data[index + 2];
                        
                        totalSamples++;
                        const brightness = (r + g + b) / 3;
                        avgBrightness += brightness;
                        
                        // Detección optimizada de piel
                        if (this.isOptimizedSkinTone(r, g, b)) {
                            skinPixels++;
                            skinClusters.push({ x, y, brightness });
                        }
                    }
                }
            }
            
            avgBrightness = totalSamples > 0 ? avgBrightness / totalSamples : 0;
            
            // Analizar distribución de piel para encontrar regiones faciales
            const faceRegions = this.findFaceRegions(skinClusters, width, height);
            const skinRatio = totalSamples > 0 ? skinPixels / totalSamples : 0;
            
            // Calcular confianza UNIFICADA con análisis por regiones
            let confidence = 0;
            
            // Factor básico de presencia de piel (UNIFICADO)
            if (skinRatio > 0.05 && skinRatio < 0.6) {
                // Usar misma escala que analyzeRegion para consistencia
                confidence += Math.min(0.4, skinRatio * 8) * 0.5; // Convertir a escala 0-0.4
            }
            
            // Factor de distribución (similar a contraste en regions)
            if (faceRegions.concentration > 0.2) {
                confidence += 0.3; // Equivalente a factor contraste
            }
            
            // Factor de iluminación promedio (UNIFICADO)
            if (avgBrightness > 50 && avgBrightness < 200) {
                confidence += 0.2;
            }
            
            // Bonus por agrupaciones concentradas (equivale a características)
            if (faceRegions.clustersFound > 0) {
                confidence += 0.1 * Math.min(1, faceRegions.clustersFound / 2);
            }
            
            resolve({
                type: 'skin-analysis-fullscreen',
                skinRatio,
                avgBrightness,
                confidence: Math.min(0.95, confidence), // MISMO MÁXIMO que analyzeRegion
                hasValidSkin: skinRatio > 0.05 && skinRatio < 0.6,
                faceRegions,
                details: {
                    totalSkinPixels: skinPixels,
                    totalSamples,
                    clustersFound: faceRegions.clustersFound,
                    searchArea: 'fullscreen'
                }
            });
        });
    }
    
    /**
     * ENCONTRAR REGIONES FACIALES BASADAS EN AGRUPACIONES DE PIEL
     */
    findFaceRegions(skinClusters, width, height) {
        if (skinClusters.length < 10) {
            return { clustersFound: 0, concentration: 0, regions: [] };
        }
        
        // Dividir pantalla en grid para analizar concentración
        const gridSize = 8;
        const cellWidth = width / gridSize;
        const cellHeight = height / gridSize;
        const grid = Array(gridSize).fill().map(() => Array(gridSize).fill(0));
        
        // Contar píxeles de piel por celda
        skinClusters.forEach(cluster => {
            const gridX = Math.min(Math.floor(cluster.x / cellWidth), gridSize - 1);
            const gridY = Math.min(Math.floor(cluster.y / cellHeight), gridSize - 1);
            grid[gridY][gridX]++;
        });
        
        // Encontrar celdas con alta concentración
        const highDensityCells = [];
        let maxDensity = 0;
        
        for (let y = 0; y < gridSize; y++) {
            for (let x = 0; x < gridSize; x++) {
                if (grid[y][x] > maxDensity) {
                    maxDensity = grid[y][x];
                }
                if (grid[y][x] > 5) { // Threshold para considerar región facial
                    highDensityCells.push({ x, y, density: grid[y][x] });
                }
            }
        }
        
        // Calcular concentración general
        const concentration = maxDensity > 0 ? maxDensity / skinClusters.length : 0;
        
        return {
            clustersFound: highDensityCells.length,
            concentration,
            regions: highDensityCells.map(cell => ({
                x: cell.x * cellWidth,
                y: cell.y * cellHeight,
                width: cellWidth,
                height: cellHeight,
                density: cell.density
            })),
            maxDensity
        };
    }
    
    /**
     * DETECCIÓN RÁPIDA DE CARACTERÍSTICAS - PANTALLA COMPLETA
     */
    quickFeatureDetection(imageData) {
        return new Promise((resolve) => {
            const { data, width, height } = imageData;
            
            // ANÁLISIS DE PANTALLA COMPLETA con múltiples regiones
            const regions = this.defineSearchRegions(width, height);
            let bestRegion = null;
            let maxConfidence = 0;
            
            regions.forEach((region, index) => {
                const analysis = this.analyzeRegion(data, width, height, region);
                
                if (analysis.confidence > maxConfidence) {
                    maxConfidence = analysis.confidence;
                    bestRegion = { ...analysis, regionIndex: index, region };
                }
            });
            
            // Si no se encontró en regiones específicas, hacer barrido completo
            if (maxConfidence < 0.3) {
                const fullScreenAnalysis = this.fullScreenScan(data, width, height);
                if (fullScreenAnalysis.confidence > maxConfidence) {
                    maxConfidence = fullScreenAnalysis.confidence;
                    bestRegion = { ...fullScreenAnalysis, regionIndex: -1, region: 'fullscreen' };
                }
            }
            
            resolve({
                type: 'feature-detection-fullscreen',
                contrast: bestRegion?.contrast || 0,
                hasFeatures: maxConfidence > 0.2,
                confidence: maxConfidence,
                bestRegion: bestRegion?.region || 'none',
                details: {
                    regionsAnalyzed: regions.length + 1,
                    bestRegionIndex: bestRegion?.regionIndex || -1
                }
            });
        });
    }
    
    /**
     * DEFINIR REGIONES DE BÚSQUEDA EN TODA LA PANTALLA
     */
    defineSearchRegions(width, height) {
        return [
            // Región central (tradicional)
            {
                name: 'center',
                x: width * 0.25,
                y: height * 0.25,
                w: width * 0.5,
                h: height * 0.5
            },
            // Región superior (para caras altas)
            {
                name: 'upper',
                x: width * 0.2,
                y: height * 0.1,
                w: width * 0.6,
                h: height * 0.5
            },
            // Región inferior (para caras bajas)
            {
                name: 'lower',
                x: width * 0.2,
                y: height * 0.4,
                w: width * 0.6,
                h: height * 0.5
            },
            // Región izquierda
            {
                name: 'left',
                x: width * 0.1,
                y: height * 0.2,
                w: width * 0.5,
                h: height * 0.6
            },
            // Región derecha
            {
                name: 'right',
                x: width * 0.4,
                y: height * 0.2,
                w: width * 0.5,
                h: height * 0.6
            },
            // Región amplia (80% de pantalla)
            {
                name: 'wide',
                x: width * 0.1,
                y: height * 0.1,
                w: width * 0.8,
                h: height * 0.8
            }
        ];
    }
    
    /**
     * ANALIZAR REGIÓN ESPECÍFICA
     */
    analyzeRegion(data, width, height, region) {
        const { x, y, w, h } = region;
        
        let darkPixels = 0;
        let lightPixels = 0;
        let skinPixels = 0;
        let pixelCount = 0;
        let totalBrightness = 0;
        
        // Muestreo optimizado en la región
        const step = 3; // Analizar cada 3 píxeles para velocidad
        
        for (let py = y; py < y + h && py < height; py += step) {
            for (let px = x; px < x + w && px < width; px += step) {
                const index = (Math.floor(py) * width + Math.floor(px)) * 4;
                
                if (index < data.length - 3) {
                    const r = data[index];
                    const g = data[index + 1];
                    const b = data[index + 2];
                    
                    const brightness = (r + g + b) / 3;
                    totalBrightness += brightness;
                    pixelCount++;
                    
                    if (brightness < 80) darkPixels++;
                    if (brightness > 180) lightPixels++;
                    if (this.isOptimizedSkinTone(r, g, b)) skinPixels++;
                }
            }
        }
        
        if (pixelCount === 0) {
            return { confidence: 0, contrast: 0, skinRatio: 0 };
        }
        
        const avgBrightness = totalBrightness / pixelCount;
        const contrast = Math.abs(darkPixels - lightPixels) / pixelCount;
        const skinRatio = skinPixels / pixelCount;
        
        // Calcular confianza UNIFICADA (misma fórmula que pantalla completa)
        let confidence = 0;
        
        // Factor básico de presencia de piel (UNIFICADO)
        if (skinRatio > 0.1 && skinRatio < 0.7) {
            confidence += Math.min(0.4, skinRatio * 8) * 0.5; // Escala consistente 0-0.4
        }
        
        // Factor de contraste (equivale a concentración en fullscreen)
        if (contrast > 0.1) {
            confidence += 0.3; // Mismo peso que factor distribución
        }
        
        // Factor de iluminación (UNIFICADO)
        if (avgBrightness > 50 && avgBrightness < 200) {
            confidence += 0.2;
        }
        
        // Bonus por ratio óptimo de piel (equivale a clusters)
        if (skinRatio > 0.15) {
            confidence += 0.1 * Math.min(1, skinRatio * 3);
        }
        
        return {
            confidence: Math.min(0.95, confidence),
            contrast,
            skinRatio,
            avgBrightness,
            region: region.name
        };
    }
    
    /**
     * BARRIDO COMPLETO DE PANTALLA (último recurso)
     */
    fullScreenScan(data, width, height) {
        let skinPixels = 0;
        let totalPixels = 0;
        let avgBrightness = 0;
        
        // Muestreo más amplio para velocidad
        const step = 8; // Cada 8 píxeles
        
        for (let y = 0; y < height; y += step) {
            for (let x = 0; x < width; x += step) {
                const index = (y * width + x) * 4;
                
                if (index < data.length - 3) {
                    const r = data[index];
                    const g = data[index + 1];
                    const b = data[index + 2];
                    
                    totalPixels++;
                    avgBrightness += (r + g + b) / 3;
                    
                    if (this.isOptimizedSkinTone(r, g, b)) {
                        skinPixels++;
                    }
                }
            }
        }
        
        if (totalPixels === 0) {
            return { confidence: 0, contrast: 0, skinRatio: 0 };
        }
        
        const skinRatio = skinPixels / totalPixels;
        avgBrightness = avgBrightness / totalPixels;
        
        // Confianza UNIFICADA para pantalla completa (misma escala)
        let confidence = 0;
        if (skinRatio > 0.05 && skinRatio < 0.5) {
            confidence = Math.min(0.4, skinRatio * 8) * 0.5; // Base consistente
            
            // Factor de distribución en fullscreen
            if (skinRatio > 0.1) {
                confidence += 0.3; // Equivalente a contraste
            }
            
            // Bonus por presencia equilibrada
            confidence += 0.2; // Factor iluminación base
        }
        
        return {
            confidence: Math.min(0.95, confidence), // MISMO MÁXIMO
            contrast: 0.3, // Valor por defecto para fullscreen
            skinRatio,
            avgBrightness,
            region: 'fullscreen'
        };
    }
    
    /**
     * VERIFICACIÓN RÁPIDA DE CALIDAD DE ILUMINACIÓN
     */
    lightingQualityCheck(imageData) {
        return new Promise((resolve) => {
            const { data } = imageData;
            const sampleSize = Math.min(1000, data.length / 16); // Muestra pequeña
            
            let totalBrightness = 0;
            let samples = 0;
            
            for (let i = 0; i < sampleSize * 4; i += 16) {
                const brightness = (data[i] + data[i + 1] + data[i + 2]) / 3;
                totalBrightness += brightness;
                samples++;
            }
            
            const avgBrightness = samples > 0 ? totalBrightness / samples : 0;
            const isGoodLighting = avgBrightness >= this.config.quality.minBrightness && 
                                 avgBrightness <= this.config.quality.maxBrightness;
            
            resolve({
                type: 'lighting-quality',
                brightness: avgBrightness,
                quality: isGoodLighting ? 'good' : avgBrightness < 50 ? 'too-dark' : 'too-bright',
                confidence: isGoodLighting ? 0.7 : 0.3
            });
        });
    }
    
    /**
     * COMBINAR RESULTADOS DE ANÁLISIS - PANTALLA COMPLETA
     */
    combineAnalysisResults(results, metadata) {
        const [skinAnalysis, featureDetection, lightingQuality] = results;
        
        // Calcular confianza combinada con pesos optimizados para pantalla completa
        let totalConfidence = 0;
        let weightSum = 0;
        let analysisDetails = {};
        
        // Factor de piel (peso alto, muy confiable para detección facial)
        if (skinAnalysis.hasValidSkin) {
            const skinWeight = 0.5;
            totalConfidence += skinAnalysis.confidence * skinWeight;
            weightSum += skinWeight;
            
            // Bonus por agrupaciones de piel detectadas
            if (skinAnalysis.faceRegions && skinAnalysis.faceRegions.clustersFound > 0) {
                const clusterBonus = Math.min(0.2, skinAnalysis.faceRegions.clustersFound * 0.05);
                totalConfidence += clusterBonus;
                weightSum += 0.1;
            }
        }
        
        // Factor de características (peso medio)
        if (featureDetection.hasFeatures) {
            const featureWeight = 0.3;
            totalConfidence += featureDetection.confidence * featureWeight;
            weightSum += featureWeight;
            
            // Bonus por encontrar la mejor región
            if (featureDetection.bestRegion && featureDetection.bestRegion !== 'none') {
                totalConfidence += 0.1;
                weightSum += 0.05;
            }
        }
        
        // Factor de iluminación (peso menor, más variable)
        const lightingWeight = 0.2;
        totalConfidence += lightingQuality.confidence * lightingWeight;
        weightSum += lightingWeight;
        
        const finalConfidence = weightSum > 0 ? totalConfidence / weightSum : 0;
        const isSuccessful = finalConfidence >= this.config.detection.minConfidence;
        
        // Detalles extendidos del análisis
        analysisDetails = {
            skinAnalysis: {
                ratio: skinAnalysis.skinRatio.toFixed(3),
                brightness: Math.round(skinAnalysis.avgBrightness),
                valid: skinAnalysis.hasValidSkin,
                clusters: skinAnalysis.faceRegions?.clustersFound || 0,
                concentration: skinAnalysis.faceRegions?.concentration?.toFixed(3) || '0',
                regions: skinAnalysis.faceRegions?.regions?.length || 0
            },
            features: {
                contrast: featureDetection.contrast?.toFixed(3) || '0',
                detected: featureDetection.hasFeatures,
                bestRegion: featureDetection.bestRegion || 'none',
                regionsAnalyzed: featureDetection.details?.regionsAnalyzed || 0,
                searchArea: 'fullscreen'
            },
            lighting: {
                quality: lightingQuality.quality,
                brightness: Math.round(lightingQuality.brightness)
            },
            fullscreen: {
                enabled: true,
                totalWeight: weightSum.toFixed(2),
                algorithm: 'multi-region-analysis'
            }
        };
        
        // Mensaje mejorado con información de ubicación
        let message = '';
        if (isSuccessful) {
            const location = featureDetection.bestRegion || 'pantalla completa';
            message = `Rostro detectado en ${location}`;
            if (skinAnalysis.faceRegions?.clustersFound > 0) {
                message += ` (${skinAnalysis.faceRegions.clustersFound} regiones faciales)`;
            }
        } else {
            message = 'No se detectó rostro válido en pantalla completa';
        }
        
        return {
            success: isSuccessful,
            detected: isSuccessful,
            confidence: Math.min(0.95, finalConfidence),
            method: metadata.method,
            timestamp: metadata.timestamp,
            analysisTime: metadata.analysisTime,
            message,
            details: analysisDetails,
            features: {
                quality: {
                    brightness: Math.round(lightingQuality.brightness),
                    contrast: Math.round((featureDetection.contrast || 0) * 100),
                    quality: lightingQuality.quality
                },
                fullscreen: {
                    searchRegions: featureDetection.details?.regionsAnalyzed || 0,
                    skinClusters: skinAnalysis.faceRegions?.clustersFound || 0,
                    bestMatch: featureDetection.bestRegion || 'none'
                }
            }
        };
    }
    
    /**
     * DETECCIÓN OPTIMIZADA DE TONO DE PIEL
     */
    isOptimizedSkinTone(r, g, b) {
        // Algoritmo optimizado y más preciso
        if (r < 60 || g < 40 || b < 20) return false;
        if (r < g || r < b) return false;
        
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        
        return (max - min) > 15 && Math.abs(r - g) > 15;
    }
    
    /**
     * DETECCIÓN CON API NATIVA
     */
    async detectWithNativeAPI(videoElement) {
        try {
            const faceDetector = new FaceDetector({
                maxDetectedFaces: 1,
                fastMode: true
            });
            
            const faces = await faceDetector.detect(videoElement);
            
            return {
                detected: faces.length > 0,
                confidence: faces.length > 0 ? 0.85 : 0,
                method: 'browser-native',
                details: { facesFound: faces.length }
            };
            
        } catch (error) {
            console.warn('API nativa no disponible:', error);
            return this.quickCanvasDetection(videoElement);
        }
    }
    
    /**
     * DETECCIÓN RÁPIDA CON CANVAS
     */
    async quickCanvasDetection(videoElement) {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = 160; // Resolución muy baja para velocidad
            canvas.height = 120;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const analysis = await this.fastSkinAnalysis(imageData);
            
            return {
                detected: analysis.hasValidSkin && analysis.confidence > 0.4,
                confidence: analysis.confidence,
                method: 'quick-canvas',
                details: { skinRatio: analysis.skinRatio }
            };
            
        } catch (error) {
            return this.getDefaultResult(false, 'canvas-error');
        }
    }
    
    /**
     * UTILIDADES
     */
    shouldSkipFrame() {
        // Saltar frames para mantener FPS objetivo
        this.cache.frameSkipCount++;
        const targetInterval = 1000 / this.config.detection.frameRate;
        const timeSinceLastAnalysis = Date.now() - (this.stats.lastAnalysisTime || 0);
        
        return this.cache.frameSkipCount < 3 && timeSinceLastAnalysis < targetInterval;
    }
    
    getDefaultResult(detected = false, reason = 'no-detection') {
        return {
            detected,
            confidence: detected ? 0.6 : 0,
            method: 'default',
            details: { reason }
        };
    }
}

// Crear instancia global
window.OptimizedBiometricSystem = new OptimizedBiometricSystem();

// Mantener compatibilidad con el sistema anterior
window.SimpleBiometricSystem = window.OptimizedBiometricSystem;

console.log('🚀 Sistema biométrico optimizado cargado');
