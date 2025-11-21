/**
 * SISTEMA DE RECONOCIMIENTO DE HUELLAS DACTILARES
 * Versión: 2.0 - Implementación Completa
 * Compatible con sensores Web Biometric API
 */

class FingerprintRecognitionSystem {
    constructor() {
        this.isSupported = false;
        this.enrolledFingerprints = new Map();
        this.sensorConnected = false;
        
        // Configuración del sistema
        this.config = {
            minQuality: 0.7,           // Calidad mínima de huella
            matchThreshold: 0.8,       // Umbral de coincidencia
            enrollSamples: 3,          // Muestras necesarias para enrolamiento
            timeoutSeconds: 30,        // Timeout para captura
            retryAttempts: 3           // Intentos de verificación
        };
        
        this.stats = {
            enrollments: 0,
            verifications: 0,
            successRate: 0
        };
        
        this.initializeSystem();
    }
    
    /**
     * INICIALIZAR SISTEMA DE HUELLAS
     */
    async initializeSystem() {
        try {
            console.log('🖐️ Inicializando sistema de huellas...');
            
            // Verificar soporte del navegador
            this.checkBrowserSupport();
            
            // Detectar sensores disponibles
            await this.detectSensors();
            
            // Configurar SDK de huellas si está disponible
            await this.initializeFingerprintSDK();
            
            console.log('✅ Sistema de huellas listo');
            
        } catch (error) {
            console.error('❌ Error inicializando sistema de huellas:', error);
            this.initializeFallbackSystem();
        }
    }
    
    /**
     * VERIFICAR SOPORTE DEL NAVEGADOR
     */
    checkBrowserSupport() {
        // Verificar APIs necesarias
        const hasWebAuthn = !!(navigator.credentials && navigator.credentials.create);
        const hasGetUserMedia = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        const hasFingerprint = !!(navigator.fingerprint || window.FingerprintSDK);
        
        this.isSupported = hasWebAuthn || hasFingerprint;
        
        console.log('📱 Soporte del navegador:', {
            webauthn: hasWebAuthn,
            userMedia: hasGetUserMedia,
            fingerprint: hasFingerprint,
            supported: this.isSupported
        });
    }
    
    /**
     * DETECTAR SENSORES DE HUELLAS
     */
    async detectSensors() {
        try {
            // Intentar detectar sensores físicos
            if (window.FingerprintSDK) {
                const sensors = await FingerprintSDK.getSensors();
                this.sensorConnected = sensors && sensors.length > 0;
                console.log('🔍 Sensores detectados:', sensors);
            } else {
                // Usar WebAuthn como alternativa
                this.sensorConnected = await this.testWebAuthnSupport();
            }
            
        } catch (error) {
            console.warn('⚠️ No se detectaron sensores de huellas:', error);
            this.sensorConnected = false;
        }
    }
    
    /**
     * PROBAR SOPORTE DE WEBAUTHN
     */
    async testWebAuthnSupport() {
        try {
            const publicKeyCredentialCreationOptions = {
                challenge: new Uint8Array(32),
                rp: {
                    name: "SynkTime",
                    id: location.hostname,
                },
                user: {
                    id: new Uint8Array(16),
                    name: "test@synktime.com",
                    displayName: "Test User",
                },
                pubKeyCredParams: [{alg: -7, type: "public-key"}],
                authenticatorSelection: {
                    authenticatorAttachment: "platform",
                    userVerification: "required"
                },
                timeout: 5000,
                attestation: "direct"
            };
            
            // Solo verificar disponibilidad, no crear credencial
            const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            return available;
            
        } catch (error) {
            return false;
        }
    }
    
    /**
     * INICIALIZAR SDK DE HUELLAS
     */
    async initializeFingerprintSDK() {
        if (window.FingerprintSDK) {
            try {
                await FingerprintSDK.initialize({
                    quality: this.config.minQuality,
                    timeout: this.config.timeoutSeconds * 1000
                });
                console.log('✅ SDK de huellas inicializado');
            } catch (error) {
                console.error('Error inicializando SDK:', error);
            }
        }
    }
    
    /**
     * CAPTURAR HUELLA DACTILAR
     */
    async captureFingerprint(options = {}) {
        try {
            if (!this.isSupported) {
                throw new Error('Sistema de huellas no soportado');
            }
            
            console.log('🖐️ Iniciando captura de huella...');
            
            // Usar SDK nativo si está disponible
            if (window.FingerprintSDK && this.sensorConnected) {
                return await this.captureWithSDK(options);
            }
            
            // Usar WebAuthn como alternativa
            return await this.captureWithWebAuthn(options);
            
        } catch (error) {
            console.error('Error capturando huella:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * CAPTURAR CON SDK NATIVO
     */
    async captureWithSDK(options) {
        try {
            const result = await FingerprintSDK.capture({
                quality: options.quality || this.config.minQuality,
                timeout: options.timeout || this.config.timeoutSeconds * 1000
            });
            
            return {
                success: true,
                template: result.template,
                quality: result.quality,
                image: result.image,
                method: 'native_sdk'
            };
            
        } catch (error) {
            throw new Error(`Error con SDK nativo: ${error.message}`);
        }
    }
    
    /**
     * CAPTURAR CON WEBAUTHN
     */
    async captureWithWebAuthn(options) {
        try {
            const challenge = new Uint8Array(32);
            crypto.getRandomValues(challenge);
            
            const publicKeyCredentialCreationOptions = {
                challenge: challenge,
                rp: {
                    name: "SynkTime Biometric",
                    id: location.hostname,
                },
                user: {
                    id: new Uint8Array(16),
                    name: options.employeeId || "employee",
                    displayName: options.employeeName || "Employee",
                },
                pubKeyCredParams: [{alg: -7, type: "public-key"}],
                authenticatorSelection: {
                    authenticatorAttachment: "platform",
                    userVerification: "required"
                },
                timeout: this.config.timeoutSeconds * 1000,
                attestation: "direct"
            };
            
            const credential = await navigator.credentials.create({
                publicKey: publicKeyCredentialCreationOptions
            });
            
            return {
                success: true,
                template: credential.rawId,
                credentialId: credential.id,
                method: 'webauthn',
                quality: 0.9 // WebAuthn no proporciona calidad específica
            };
            
        } catch (error) {
            throw new Error(`Error con WebAuthn: ${error.message}`);
        }
    }
    
    /**
     * ENROLAR HUELLA DACTILAR
     */
    async enrollFingerprint(employeeId, fingerType = 'index_right') {
        try {
            console.log(`🖐️ Iniciando enrolamiento de huella para empleado ${employeeId}...`);
            
            const samples = [];
            const requiredSamples = this.config.enrollSamples;
            
            // Capturar múltiples muestras
            for (let i = 0; i < requiredSamples; i++) {
                console.log(`📸 Capturando muestra ${i + 1}/${requiredSamples}...`);
                
                const capture = await this.captureFingerprint({
                    employeeId: employeeId,
                    employeeName: `Employee ${employeeId}`
                });
                
                if (!capture.success) {
                    throw new Error(`Error en muestra ${i + 1}: ${capture.error}`);
                }
                
                if (capture.quality < this.config.minQuality) {
                    throw new Error(`Calidad insuficiente en muestra ${i + 1}: ${capture.quality}`);
                }
                
                samples.push(capture);
                
                // Pausa entre capturas
                if (i < requiredSamples - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            }
            
            // Crear template promedio o compuesto
            const enrollmentTemplate = await this.createEnrollmentTemplate(samples);
            
            // Guardar en base de datos
            const saveResult = await this.saveFingerprintEnrollment(
                employeeId, 
                fingerType, 
                enrollmentTemplate
            );
            
            if (!saveResult.success) {
                throw new Error('Error guardando enrolamiento en BD');
            }
            
            // Agregar a cache local
            const enrollmentKey = `${employeeId}_${fingerType}`;
            this.enrolledFingerprints.set(enrollmentKey, {
                template: enrollmentTemplate,
                enrolledAt: new Date(),
                samples: samples.length,
                quality: samples.reduce((avg, s) => avg + s.quality, 0) / samples.length
            });
            
            this.stats.enrollments++;
            
            console.log(`✅ Enrolamiento completado para empleado ${employeeId}`);
            
            return {
                success: true,
                employeeId: employeeId,
                fingerType: fingerType,
                samples: samples.length,
                avgQuality: samples.reduce((avg, s) => avg + s.quality, 0) / samples.length,
                message: 'Huella registrada correctamente'
            };
            
        } catch (error) {
            console.error('Error en enrolamiento de huella:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * VERIFICAR HUELLA DACTILAR
     */
    async verifyFingerprint(employeeId, fingerType = 'index_right') {
        try {
            console.log(`🔍 Verificando huella para empleado ${employeeId}...`);
            
            // Obtener template registrado
            const enrolledTemplate = await this.getEnrolledTemplate(employeeId, fingerType);
            if (!enrolledTemplate) {
                throw new Error('Empleado no tiene huella registrada');
            }
            
            // Capturar huella actual
            const capture = await this.captureFingerprint({
                employeeId: employeeId,
                verification: true
            });
            
            if (!capture.success) {
                throw new Error(`Error capturando huella: ${capture.error}`);
            }
            
            if (capture.quality < this.config.minQuality) {
                throw new Error(`Calidad insuficiente: ${capture.quality.toFixed(2)}`);
            }
            
            // Comparar templates
            const matchScore = await this.compareTemplates(
                enrolledTemplate.template,
                capture.template
            );
            
            const isMatch = matchScore >= this.config.matchThreshold;
            
            this.stats.verifications++;
            if (isMatch) {
                this.stats.successRate = (this.stats.successRate * (this.stats.verifications - 1) + 1) / this.stats.verifications;
            } else {
                this.stats.successRate = (this.stats.successRate * (this.stats.verifications - 1)) / this.stats.verifications;
            }
            
            console.log(`${isMatch ? '✅' : '❌'} Verificación ${isMatch ? 'exitosa' : 'fallida'}: ${matchScore.toFixed(3)}`);
            
            return {
                success: isMatch,
                employeeId: employeeId,
                matchScore: matchScore,
                quality: capture.quality,
                message: isMatch ? 'Huella verificada correctamente' : 'Huella no coincide'
            };
            
        } catch (error) {
            console.error('Error en verificación de huella:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * CREAR TEMPLATE DE ENROLAMIENTO
     */
    async createEnrollmentTemplate(samples) {
        if (window.FingerprintSDK && samples[0].method === 'native_sdk') {
            // Usar SDK para crear template compuesto
            return await FingerprintSDK.createTemplate(samples.map(s => s.template));
        } else {
            // Para WebAuthn, usar el mejor template
            const bestSample = samples.reduce((best, current) => 
                current.quality > best.quality ? current : best
            );
            return bestSample.template;
        }
    }
    
    /**
     * COMPARAR TEMPLATES
     */
    async compareTemplates(template1, template2) {
        if (window.FingerprintSDK) {
            // Usar SDK nativo para comparación
            const result = await FingerprintSDK.match(template1, template2);
            return result.score;
        } else {
            // Comparación básica para WebAuthn
            if (template1 instanceof ArrayBuffer && template2 instanceof ArrayBuffer) {
                const arr1 = new Uint8Array(template1);
                const arr2 = new Uint8Array(template2);
                
                if (arr1.length !== arr2.length) return 0;
                
                let matches = 0;
                for (let i = 0; i < arr1.length; i++) {
                    if (arr1[i] === arr2[i]) matches++;
                }
                
                return matches / arr1.length;
            }
            return 0;
        }
    }
    
    /**
     * GUARDAR ENROLAMIENTO EN BD
     */
    async saveFingerprintEnrollment(employeeId, fingerType, template) {
        try {
            // Convertir template a string para almacenamiento
            const templateString = template instanceof ArrayBuffer ? 
                Array.from(new Uint8Array(template)).join(',') : 
                JSON.stringify(template);
            
            const response = await fetch('api/biometric/enroll.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    biometric_type: 'fingerprint',
                    finger_type: fingerType,
                    biometric_data: templateString,
                    quality: this.config.minQuality
                })
            });
            
            const result = await response.json();
            return result;
            
        } catch (error) {
            console.error('Error guardando enrolamiento:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * OBTENER TEMPLATE REGISTRADO
     */
    async getEnrolledTemplate(employeeId, fingerType) {
        const enrollmentKey = `${employeeId}_${fingerType}`;
        
        // Buscar en cache primero
        if (this.enrolledFingerprints.has(enrollmentKey)) {
            return this.enrolledFingerprints.get(enrollmentKey);
        }
        
        // Obtener de base de datos
        try {
            const response = await fetch(
                `api/biometric/get-fingerprint.php?employee_id=${employeeId}&finger_type=${fingerType}`
            );
            const result = await response.json();
            
            if (result.success && result.data) {
                const templateData = {
                    template: this.parseTemplate(result.data.biometric_data),
                    enrolledAt: new Date(result.data.created_at),
                    quality: result.data.quality || this.config.minQuality
                };
                
                // Agregar a cache
                this.enrolledFingerprints.set(enrollmentKey, templateData);
                
                return templateData;
            }
            
            return null;
            
        } catch (error) {
            console.error('Error obteniendo template:', error);
            return null;
        }
    }
    
    /**
     * PARSEAR TEMPLATE DESDE STRING
     */
    parseTemplate(templateString) {
        try {
            // Si es una lista de números separados por coma (ArrayBuffer)
            if (templateString.includes(',')) {
                const numbers = templateString.split(',').map(n => parseInt(n));
                return new Uint8Array(numbers).buffer;
            }
            
            // Si es JSON
            return JSON.parse(templateString);
            
        } catch (error) {
            console.error('Error parseando template:', error);
            return templateString;
        }
    }
    
    /**
     * INICIALIZAR SISTEMA FALLBACK
     */
    initializeFallbackSystem() {
        console.warn('⚠️ Usando sistema de huellas simulado');
        this.isSupported = true;
        this.sensorConnected = true;
        
        // Mock de funciones para desarrollo/testing
        this.captureFingerprint = async (options) => {
            await new Promise(resolve => setTimeout(resolve, 2000));
            return {
                success: true,
                template: this.generateMockTemplate(),
                quality: 0.85,
                method: 'simulation'
            };
        };
    }
    
    /**
     * GENERAR TEMPLATE SIMULADO
     */
    generateMockTemplate() {
        const template = new Uint8Array(256);
        for (let i = 0; i < template.length; i++) {
            template[i] = Math.floor(Math.random() * 256);
        }
        return template.buffer;
    }
    
    /**
     * OBTENER ESTADÍSTICAS
     */
    getSystemStats() {
        return {
            ...this.stats,
            isSupported: this.isSupported,
            sensorConnected: this.sensorConnected,
            enrolledFingerprints: this.enrolledFingerprints.size
        };
    }
    
    /**
     * LIMPIAR RECURSOS
     */
    dispose() {
        this.enrolledFingerprints.clear();
        if (window.FingerprintSDK) {
            FingerprintSDK.dispose?.();
        }
    }
}

// Inicializar sistema global
window.FingerprintRecognitionSystem = FingerprintRecognitionSystem;
