/**
 * Modal de Verificación Biométrica Completa - JavaScript
 * Sistema SNKTIME - Verificación mejorada con manejo de errores
 */

class BiometricVerificationModal {
    constructor() {
        this.modal = null;
        this.selectedEmployee = null;
        this.employeeData = null;
        this.currentTab = 'facial';
        this.verificationResults = {
            facial: null,
            fingerprint: null,
            rfid: null
        };

        // Estados de verificación
        this.isVerifying = {
            facial: false,
            fingerprint: false,
            rfid: false
        };

        // Streams de medios
        this.videoStream = null;
        this.fingerprintStream = null;
        this.rfidStream = null;

        // Configuración
        this.config = {
            facial: {
                confidenceThreshold: 0.85,
                qualityThreshold: 0.80,
                maxAttempts: 3
            },
            fingerprint: {
                confidenceThreshold: 0.90,
                maxAttempts: 3
            },
            rfid: {
                confidenceThreshold: 0.95,
                readTimeout: 5000
            }
        };

        // Intentos de verificación
        this.attempts = {
            facial: 0,
            fingerprint: 0,
            rfid: 0
        };

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadConfiguration();
        this.checkServiceStatus();
    }

    bindEvents() {
        // Evento cuando se abre el modal
        document.getElementById('biometricVerificationModal').addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (button) {
                const employeeId = button.getAttribute('data-employee-id');
                const attendanceType = button.getAttribute('data-attendance-type') || 'ENTRADA';
                this.loadEmployeeData(employeeId, attendanceType);
            }
        });

        // Evento cuando se cierra el modal
        document.getElementById('biometricVerificationModal').addEventListener('hide.bs.modal', () => {
            this.stopAllVerification();
            this.resetModal();
        });

        // Eventos de pestañas
        document.querySelectorAll('#verificationTabs .nav-link').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.id.replace('-verification-tab', '').replace('verification-', ''));
            });
        });

        // Eventos de controles faciales
        document.getElementById('startFacialVerification').addEventListener('click', () => this.startFacialVerification());
        document.getElementById('stopFacialVerification').addEventListener('click', () => this.stopFacialVerification());
        document.getElementById('verifyFacialNow').addEventListener('click', () => this.verifyFacialNow());

        // Eventos de controles de huella
        document.getElementById('startFingerprintVerification').addEventListener('click', () => this.startFingerprintVerification());
        document.getElementById('verifyFingerprintNow').addEventListener('click', () => this.verifyFingerprintNow());
        document.getElementById('stopFingerprintVerification').addEventListener('click', () => this.stopFingerprintVerification());

        // Eventos de controles RFID
        document.getElementById('startRfidVerification').addEventListener('click', () => this.startRfidVerification());
        document.getElementById('verifyRfidNow').addEventListener('click', () => this.verifyRfidNow());
        document.getElementById('stopRfidVerification').addEventListener('click', () => this.stopRfidVerification());

        // Evento de completar verificación
        document.getElementById('completeVerification').addEventListener('click', () => this.completeVerification());

        // Evento de reintentar verificación
        document.getElementById('retry-verification-btn').addEventListener('click', () => this.retryVerification());
    }

    async loadConfiguration() {
        try {
            const response = await fetch('biometric_config.json');
            if (response.ok) {
                const config = await response.json();
                this.config = {
                    facial: {
                        confidenceThreshold: config.biometric.methods.facial.confidence_threshold,
                        qualityThreshold: 0.80,
                        maxAttempts: config.biometric.verification.max_attempts
                    },
                    fingerprint: {
                        confidenceThreshold: config.biometric.methods.fingerprint.confidence_threshold,
                        maxAttempts: config.biometric.verification.max_attempts
                    },
                    rfid: {
                        confidenceThreshold: config.biometric.methods.rfid.confidence_threshold,
                        readTimeout: config.biometric.methods.rfid.read_timeout * 1000
                    }
                };
            }
        } catch (error) {
            console.warn('No se pudo cargar la configuración, usando valores por defecto:', error);
        }
    }

    async checkServiceStatus() {
        try {
            const response = await fetch('http://127.0.0.1:8000/health');
            const status = response.ok ? 'Conectado' : 'Desconectado';
            document.getElementById('facial-service-status').textContent = status;
            document.getElementById('facial-service-status').className = response.ok ? 'text-success' : 'text-danger';
        } catch (error) {
            document.getElementById('facial-service-status').textContent = 'Desconectado';
            document.getElementById('facial-service-status').className = 'text-danger';
        }
    }

    async loadEmployeeData(employeeId, attendanceType) {
        try {
            this.showLoading('Cargando información del empleado...');

            const response = await fetch(`api/employee.php?id=${employeeId}`);
            if (!response.ok) {
                throw new Error('No se pudo cargar la información del empleado');
            }

            const employee = await response.json();
            this.employeeData = employee;
            this.selectedEmployee = employeeId;

            // Verificar si se puede registrar asistencia
            const validation = await this.validateAttendanceRegistration(employeeId, attendanceType);
            if (!validation.canRegister) {
                this.hideLoading();
                this.showAttendanceValidationError(validation.reason, validation.details);
                return; // No continuar con la carga del modal
            }

            // Actualizar UI
            document.getElementById('verification-employee-id').value = employeeId;
            document.getElementById('verification-display-employee-id').textContent = employeeId;
            document.getElementById('verification-employee-code').textContent = employee.codigo || employeeId;
            document.getElementById('verification-employee-name').textContent = `${employee.nombre} ${employee.apellido}`;
            document.getElementById('verification-employee-establishment').textContent = employee.establecimiento || 'No especificado';
            document.getElementById('verification-attendance-type').value = attendanceType;
            document.getElementById('verification-type-display').textContent = attendanceType;

            // Cargar estado biométrico
            await this.loadBiometricStatus(employeeId);

            this.hideLoading();

        } catch (error) {
            this.hideLoading();
            this.showError('Error al cargar empleado', error.message, [
                'Verifique que el empleado esté registrado en el sistema',
                'Contacte al administrador si el problema persiste',
                'Intente recargar la página'
            ]);
        }
    }

    async loadBiometricStatus(employeeId) {
        try {
            const response = await fetch(`api/employee_biometrics.php?employee_id=${employeeId}`);
            if (response.ok) {
                const biometrics = await response.json();

                // Actualizar estados
                const facialStatus = biometrics.find(b => b.tipo === 'face') ?
                    '<span class="badge bg-success">Registrado</span>' :
                    '<span class="badge bg-warning">No registrado</span>';

                const fingerprintStatus = biometrics.find(b => b.tipo === 'fingerprint') ?
                    '<span class="badge bg-success">Registrado</span>' :
                    '<span class="badge bg-warning">No registrado</span>';

                const rfidStatus = biometrics.find(b => b.tipo === 'rfid') ?
                    '<span class="badge bg-success">Registrado</span>' :
                    '<span class="badge bg-warning">No registrado</span>';

                document.getElementById('facial-verification-status').innerHTML = `Facial: ${facialStatus}`;
                document.getElementById('fingerprint-verification-status').innerHTML = `Huella: ${fingerprintStatus}`;
                document.getElementById('rfid-verification-status').innerHTML = `RFID: ${rfidStatus}`;
            }
        } catch (error) {
            console.warn('No se pudo cargar el estado biométrico:', error);
        }
    }

    switchTab(tabName) {
        this.currentTab = tabName;

        // Detener verificación actual
        this.stopAllVerification();

        // Resetear estados
        this.resetTabStates();

        // Cambiar a la nueva pestaña
        const tabButton = document.getElementById(`${tabName}-verification-tab`);
        if (tabButton) {
            tabButton.click();
        }
    }

    resetTabStates() {
        // Resetear todos los estados de verificación
        Object.keys(this.isVerifying).forEach(key => {
            this.isVerifying[key] = false;
        });

        // Resetear UI
        document.querySelectorAll('.scanner-animation').forEach(el => {
            el.style.display = 'none';
        });

        document.querySelectorAll('.btn').forEach(btn => {
            btn.disabled = false;
        });

        // Resetear resultados
        document.querySelectorAll('.verification-result').forEach(el => {
            el.style.display = 'none';
        });
    }

    // === VERIFICACIÓN FACIAL ===
    async startFacialVerification() {
        try {
            this.isVerifying.facial = true;
            this.updateFacialStatus('Iniciando cámara...', 'info');

            // Solicitar acceso a la cámara
            this.videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: 640,
                    height: 480,
                    facingMode: 'user'
                }
            });

            const video = document.getElementById('facialVerificationVideo');
            video.srcObject = this.videoStream;

            video.onloadedmetadata = () => {
                video.play();
                this.updateFacialStatus('Cámara iniciada. Buscando rostro...', 'success');
                this.enableFacialControls(true);
                this.startFacialDetection();
            };

        } catch (error) {
            this.isVerifying.facial = false;
            this.showError('Error de cámara', 'No se pudo acceder a la cámara del dispositivo.', [
                'Asegúrese de que la cámara esté conectada y funcionando',
                'Verifique los permisos de cámara en el navegador',
                'Cierre otras aplicaciones que puedan estar usando la cámara',
                'Intente refrescar la página y volver a intentar'
            ], error);
        }
    }

    stopFacialVerification() {
        this.isVerifying.facial = false;

        if (this.videoStream) {
            this.videoStream.getTracks().forEach(track => track.stop());
            this.videoStream = null;
        }

        const video = document.getElementById('facialVerificationVideo');
        video.srcObject = null;

        this.updateFacialStatus('Verificación detenida', 'secondary');
        this.enableFacialControls(false);
    }

    enableFacialControls(enabled) {
        document.getElementById('startFacialVerification').disabled = enabled;
        document.getElementById('stopFacialVerification').disabled = !enabled;
        document.getElementById('verifyFacialNow').disabled = !enabled;
    }

    startFacialDetection() {
        const video = document.getElementById('facialVerificationVideo');
        const canvas = document.getElementById('facialVerificationCanvas');
        const ctx = canvas.getContext('2d');

        const detectFrame = async () => {
            if (!this.isVerifying.facial) return;

            try {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);

                const imageData = canvas.toDataURL('image/jpeg', 0.8);

                // Enviar frame al servicio Python
                const response = await fetch('http://127.0.0.1:8000/verify_face', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_id: this.selectedEmployee,
                        image: imageData,
                        threshold: this.config.facial.confidenceThreshold
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    this.handleFacialResult(result);
                } else {
                    throw new Error(`Error del servicio: ${response.status}`);
                }

            } catch (error) {
                console.error('Error en detección facial:', error);
                this.attempts.facial++;

                if (this.attempts.facial >= this.config.facial.maxAttempts) {
                    this.showError('Error de verificación facial', 'No se pudo completar la verificación facial después de varios intentos.', [
                        'Verifique que el servicio Python esté ejecutándose',
                        'Asegúrese de que el empleado tenga datos faciales registrados',
                        'Intente con mejor iluminación',
                        'Contacte al administrador del sistema'
                    ], error);
                    this.stopFacialVerification();
                }
            }

            if (this.isVerifying.facial) {
                setTimeout(detectFrame, 1000); // Verificar cada segundo
            }
        };

        detectFrame();
    }

    handleFacialResult(result) {
        const confidencePercent = (result.confidence * 100).toFixed(1);
        const qualityPercent = result.quality ? (result.quality * 100).toFixed(1) : 'N/A';

        document.getElementById('facial-verification-confidence').textContent = `${confidencePercent}%`;
        document.getElementById('facial-verification-quality').textContent = `${qualityPercent}%`;

        if (result.verified) {
            this.verificationResults.facial = result;
            this.updateFacialStatus(`Verificación exitosa - ${result.employee_name}`, 'success');
            this.showFacialResult('success', 'Verificación Exitosa', `Empleado identificado: ${result.employee_name} (Confianza: ${confidencePercent}%)`);
            this.stopFacialVerification();
            this.checkVerificationComplete();
        } else {
            this.updateFacialStatus(`Rostro detectado - Confianza: ${confidencePercent}%`, 'warning');
        }
    }

    async verifyFacialNow() {
        if (!this.videoStream) {
            this.showError('Error', 'La cámara no está iniciada.', ['Inicie la verificación facial primero']);
            return;
        }

        try {
            const video = document.getElementById('facialVerificationVideo');
            const canvas = document.getElementById('facialVerificationCanvas');
            const ctx = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);

            const imageData = canvas.toDataURL('image/jpeg', 0.8);

            this.updateFacialStatus('Verificando...', 'info');

            const response = await fetch('http://127.0.0.1:8000/verify_face', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee,
                    image: imageData,
                    threshold: this.config.facial.confidenceThreshold
                })
            });

            if (response.ok) {
                const result = await response.json();
                this.handleFacialResult(result);
            } else {
                throw new Error(`Error del servicio: ${response.status}`);
            }

        } catch (error) {
            this.showError('Error en verificación', 'No se pudo completar la verificación facial.', [
                'Verifique la conexión con el servicio Python',
                'Asegúrese de que la imagen sea clara',
                'Intente nuevamente'
            ], error);
        }
    }

    updateFacialStatus(message, type) {
        const statusElement = document.getElementById('facial-verification-status-text');
        statusElement.textContent = message;

        const alertElement = statusElement.closest('.alert');
        alertElement.className = `alert alert-${type}`;
    }

    showFacialResult(type, title, message) {
        const resultDiv = document.getElementById('facial-verification-result');
        const alertDiv = document.getElementById('facial-result-alert');
        const titleDiv = document.getElementById('facial-result-title');
        const messageDiv = document.getElementById('facial-result-message');

        alertDiv.className = `alert alert-${type}`;
        titleDiv.textContent = title;
        messageDiv.textContent = message;
        resultDiv.style.display = 'block';

        // Animación
        resultDiv.classList.add(type === 'success' ? 'verification-success' : 'verification-error');
        setTimeout(() => {
            resultDiv.classList.remove('verification-success', 'verification-error');
        }, 500);
    }

    // === VERIFICACIÓN DE HUELLA ===
    async startFingerprintVerification() {
        try {
            this.isVerifying.fingerprint = true;
            this.updateFingerprintStatus('Iniciando escáner...', 'info');

            // Mostrar animación de carga
            document.getElementById('fingerprintVerificationAnimation').style.display = 'block';

            // Aquí iría la lógica para conectar con el escáner de huellas
            // Por ahora simulamos la conexión
            setTimeout(() => {
                this.updateFingerprintStatus('Escáner listo. Coloque el dedo.', 'success');
                this.enableFingerprintControls(true);
            }, 2000);

        } catch (error) {
            this.isVerifying.fingerprint = false;
            this.showError('Error de escáner', 'No se pudo conectar con el escáner de huellas.', [
                'Verifique que el escáner esté conectado y encendido',
                'Instale los drivers del dispositivo',
                'Reinicie el escáner y vuelva a intentar',
                'Contacte al soporte técnico'
            ], error);
        }
    }

    stopFingerprintVerification() {
        this.isVerifying.fingerprint = false;
        document.getElementById('fingerprintVerificationAnimation').style.display = 'none';
        this.updateFingerprintStatus('Verificación detenida', 'secondary');
        this.enableFingerprintControls(false);
    }

    enableFingerprintControls(enabled) {
        document.getElementById('startFingerprintVerification').disabled = enabled;
        document.getElementById('stopFingerprintVerification').disabled = !enabled;
        document.getElementById('verifyFingerprintNow').disabled = !enabled;
    }

    async verifyFingerprintNow() {
        try {
            this.updateFingerprintStatus('Verificando huella...', 'info');

            // Simular verificación de huella (reemplazar con lógica real)
            const response = await fetch('api/verify_fingerprint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee,
                    fingerprint_data: 'simulated_fingerprint_data'
                })
            });

            if (response.ok) {
                const result = await response.json();
                this.handleFingerprintResult(result);
            } else {
                throw new Error('Error en verificación de huella');
            }

        } catch (error) {
            this.showError('Error en verificación de huella', 'No se pudo verificar la huella dactilar.', [
                'Asegúrese de colocar el dedo correctamente en el escáner',
                'Limpie el dedo antes de la verificación',
                'Verifique que el escáner esté calibrado',
                'Intente con otro dedo si está disponible'
            ], error);
        }
    }

    handleFingerprintResult(result) {
        if (result.verified) {
            this.verificationResults.fingerprint = result;
            this.updateFingerprintStatus('Verificación exitosa', 'success');
            this.showFingerprintResult('success', 'Huella Verificada', `Empleado identificado correctamente (Confianza: ${(result.confidence * 100).toFixed(1)}%)`);
            this.stopFingerprintVerification();
            this.checkVerificationComplete();
        } else {
            this.updateFingerprintStatus('Huella no reconocida', 'danger');
            this.showFingerprintResult('danger', 'Huella No Reconocida', 'La huella no coincide con los registros del empleado.');
        }
    }

    updateFingerprintStatus(message, type) {
        document.getElementById('fingerprint-verification-status-text').textContent = message;
        document.getElementById('fingerprint-verification-status-text').className = `text-${type}`;
    }

    showFingerprintResult(type, title, message) {
        const resultDiv = document.getElementById('fingerprint-verification-result');
        const alertDiv = document.getElementById('fingerprint-result-alert');
        const titleDiv = document.getElementById('fingerprint-result-title');
        const messageDiv = document.getElementById('fingerprint-result-message');

        alertDiv.className = `alert alert-${type}`;
        titleDiv.textContent = title;
        messageDiv.textContent = message;
        resultDiv.style.display = 'block';
    }

    // === VERIFICACIÓN RFID ===
    async startRfidVerification() {
        try {
            this.isVerifying.rfid = true;
            this.updateRfidStatus('Iniciando lector RFID...', 'info');

            // Mostrar animación de carga
            document.getElementById('rfidVerificationAnimation').style.display = 'block';

            // Simular conexión con lector RFID
            setTimeout(() => {
                this.updateRfidStatus('Lector listo. Acerque el carné.', 'success');
                this.enableRfidControls(true);
                this.startRfidDetection();
            }, 2000);

        } catch (error) {
            this.isVerifying.rfid = false;
            this.showError('Error de lector RFID', 'No se pudo conectar con el lector RFID.', [
                'Verifique que el lector esté conectado y encendido',
                'Instale los drivers del dispositivo RFID',
                'Asegúrese de que el puerto USB esté funcionando',
                'Contacte al soporte técnico'
            ], error);
        }
    }

    stopRfidVerification() {
        this.isVerifying.rfid = false;
        document.getElementById('rfidVerificationAnimation').style.display = 'none';
        this.updateRfidStatus('Verificación detenida', 'secondary');
        this.enableRfidControls(false);
    }

    enableRfidControls(enabled) {
        document.getElementById('startRfidVerification').disabled = enabled;
        document.getElementById('stopRfidVerification').disabled = !enabled;
        document.getElementById('verifyRfidNow').disabled = !enabled;
    }

    startRfidDetection() {
        // Simular detección continua de RFID
        const detectRfid = async () => {
            if (!this.isVerifying.rfid) return;

            try {
                // Simular lectura RFID (reemplazar con lógica real)
                const mockUid = 'A1B2C3D4E5F6'; // UID simulado
                document.getElementById('rfid-verification-uid').textContent = mockUid;
                document.getElementById('rfid-verification-type').textContent = 'MIFARE Classic';
                this.updateRfidStatus('UID detectado. Verificando...', 'info');

                // Verificar UID
                const response = await fetch('api/verify_rfid.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_id: this.selectedEmployee,
                        rfid_uid: mockUid
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    this.handleRfidResult(result);
                } else {
                    throw new Error('Error en verificación RFID');
                }

            } catch (error) {
                console.error('Error en detección RFID:', error);
                this.attempts.rfid++;

                if (this.attempts.rfid >= 3) {
                    this.showError('Error en verificación RFID', 'No se pudo completar la verificación RFID.', [
                        'Asegúrese de que el carné esté cerca del lector',
                        'Verifique que el carné no esté dañado',
                        'Intente desde diferentes ángulos',
                        'Contacte al administrador si el problema persiste'
                    ], error);
                    this.stopRfidVerification();
                }
            }

            if (this.isVerifying.rfid) {
                setTimeout(detectRfid, 2000); // Verificar cada 2 segundos
            }
        };

        detectRfid();
    }

    async verifyRfidNow() {
        const uid = document.getElementById('rfid-verification-uid').textContent;
        if (uid === '-') {
            this.showError('Error', 'No se ha detectado ningún UID.', ['Acerque el carné al lector primero']);
            return;
        }

        try {
            this.updateRfidStatus('Verificando UID...', 'info');

            const response = await fetch('api/verify_rfid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee,
                    rfid_uid: uid
                })
            });

            if (response.ok) {
                const result = await response.json();
                this.handleRfidResult(result);
            } else {
                throw new Error('Error en verificación RFID');
            }

        } catch (error) {
            this.showError('Error en verificación RFID', 'No se pudo verificar el UID.', [
                'Verifique que el UID sea correcto',
                'Asegúrese de que el empleado tenga RFID registrado',
                'Contacte al administrador del sistema'
            ], error);
        }
    }

    handleRfidResult(result) {
        if (result.verified) {
            this.verificationResults.rfid = result;
            this.updateRfidStatus('Verificación exitosa', 'success');
            this.showRfidResult('success', 'RFID Verificado', `Carné identificado correctamente (UID: ${result.uid})`);
            this.stopRfidVerification();
            this.checkVerificationComplete();
        } else {
            this.updateRfidStatus('UID no reconocido', 'danger');
            this.showRfidResult('danger', 'RFID No Reconocido', 'El UID no coincide con los registros del empleado.');
        }
    }

    updateRfidStatus(message, type) {
        document.getElementById('rfid-verification-status-text').textContent = message;
        document.getElementById('rfid-verification-status-text').className = `text-${type}`;
    }

    showRfidResult(type, title, message) {
        const resultDiv = document.getElementById('rfid-verification-result');
        const alertDiv = document.getElementById('rfid-result-alert');
        const titleDiv = document.getElementById('rfid-result-title');
        const messageDiv = document.getElementById('rfid-result-message');

        alertDiv.className = `alert alert-${type}`;
        titleDiv.textContent = title;
        messageDiv.textContent = message;
        resultDiv.style.display = 'block';
    }

    // === UTILIDADES GENERALES ===
    stopAllVerification() {
        this.stopFacialVerification();
        this.stopFingerprintVerification();
        this.stopRfidVerification();
    }

    resetModal() {
        this.selectedEmployee = null;
        this.employeeData = null;
        this.currentTab = 'facial';
        this.verificationResults = {
            facial: null,
            fingerprint: null,
            rfid: null
        };
        this.attempts = {
            facial: 0,
            fingerprint: 0,
            rfid: 0
        };

        // Resetear UI
        document.querySelectorAll('.badge').forEach(badge => {
            badge.className = 'badge bg-secondary';
            badge.textContent = 'Pendiente';
        });

        document.querySelectorAll('.verification-result').forEach(el => {
            el.style.display = 'none';
        });

        document.getElementById('completeVerification').disabled = true;
    }

    checkVerificationComplete() {
        const hasAnyVerification = Object.values(this.verificationResults).some(result => result !== null);
        document.getElementById('completeVerification').disabled = !hasAnyVerification;
    }

    async completeVerification() {
        try {
            this.showLoading('Registrando asistencia...');

            const attendanceData = {
                employee_id: this.selectedEmployee,
                type: document.getElementById('verification-attendance-type').value,
                verification_results: this.verificationResults,
                timestamp: new Date().toISOString()
            };

            const response = await fetch('api/register_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(attendanceData)
            });

            if (response.ok) {
                const result = await response.json();
                this.showVerificationConfirmation(result);
            } else {
                throw new Error('Error al registrar la asistencia');
            }

        } catch (error) {
            this.showError('Error al completar verificación', 'No se pudo registrar la asistencia.', [
                'Verifique la conexión a internet',
                'Intente nuevamente',
                'Contacte al administrador si el problema persiste'
            ], error);
        } finally {
            this.hideLoading();
        }
    }

    showVerificationConfirmation(result) {
        const summaryDiv = document.getElementById('verificationSummary');
        summaryDiv.innerHTML = `
            <p><strong>Empleado:</strong> ${this.employeeData.nombre} ${this.employeeData.apellido}</p>
            <p><strong>Tipo:</strong> ${result.type}</p>
            <p><strong>Hora:</strong> ${new Date(result.timestamp).toLocaleString()}</p>
            <p><strong>Método de verificación:</strong> ${this.getVerificationMethodsText()}</p>
        `;

        // Cerrar modal de verificación y abrir modal de confirmación
        const verificationModal = bootstrap.Modal.getInstance(document.getElementById('biometricVerificationModal'));
        verificationModal.hide();

        const confirmationModal = new bootstrap.Modal(document.getElementById('verificationConfirmationModal'));
        confirmationModal.show();
    }

    getVerificationMethodsText() {
        const methods = [];
        if (this.verificationResults.facial) methods.push('Facial');
        if (this.verificationResults.fingerprint) methods.push('Huella');
        if (this.verificationResults.rfid) methods.push('RFID');
        return methods.join(', ') || 'Ninguno';
    }

    showError(title, message, recommendations = [], technicalError = null) {
        // Configurar timestamp
        const now = new Date();
        document.getElementById('error-timestamp').textContent = now.toLocaleString();

        // Configurar título y subtítulo del modal
        document.getElementById('error-modal-title').textContent = title;
        document.getElementById('error-modal-subtitle').textContent = 'Sistema de Control de Asistencia';

        // Configurar mensaje principal
        document.getElementById('error-title').textContent = title;
        document.getElementById('error-message').textContent = message;

        // Configurar alert personalizado con clase específica según el tipo de error
        const mainAlert = document.getElementById('error-main-alert');
        mainAlert.className = 'alert alert-custom';

        // Determinar tipo de error y aplicar clase específica
        if (title.includes('Entrada Abierta')) {
            mainAlert.classList.add('alert-entrada-abierta');
        } else if (title.includes('Horarios Completados')) {
            mainAlert.classList.add('alert-horarios-completados');
        } else if (title.includes('Límite de Tiempo')) {
            mainAlert.classList.add('alert-limite-tiempo');
        } else if (title.includes('Sin Entradas')) {
            mainAlert.classList.add('alert-sin-entrada');
        } else {
            mainAlert.classList.add('alert-warning');
        }

        // Configurar recomendaciones
        const recommendationsList = document.getElementById('error-recommendation-list');
        if (recommendations && recommendations.length > 0) {
            recommendationsList.innerHTML = recommendations.map(rec =>
                `<li class="mb-2">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    ${rec}
                </li>`
            ).join('');
            document.getElementById('error-recommendations').style.display = 'block';
        } else {
            document.getElementById('error-recommendations').style.display = 'none';
        }

        // Configurar información adicional si existe
        if (technicalError) {
            document.getElementById('error-additional-info').innerHTML = `
                <div class="alert alert-secondary">
                    <pre class="mb-0" style="font-size: 0.8rem;">${technicalError.message || technicalError}</pre>
                </div>
            `;
            document.getElementById('error-info-section').style.display = 'block';
            document.getElementById('error-code').textContent = 'TECH_' + Date.now().toString().slice(-4);
            document.getElementById('error-code-section').style.display = 'inline-block';
        } else {
            document.getElementById('error-info-section').style.display = 'none';
            document.getElementById('error-code-section').style.display = 'none';
        }

        // Mostrar modal
        const errorModal = new bootstrap.Modal(document.getElementById('verificationErrorModal'));
        errorModal.show();
    }

    // Función para mostrar confirmación de salida
    showExitConfirmation(entryTime, currentTime, workedHours) {
        // Configurar información de la jornada
        document.getElementById('exit-entry-time').textContent = entryTime || '08:00:00';
        document.getElementById('exit-current-time').textContent = currentTime || new Date().toLocaleTimeString();
        document.getElementById('exit-worked-hours').textContent = workedHours || '0.0';

        // Mostrar modal
        const exitModal = new bootstrap.Modal(document.getElementById('exitConfirmationModal'));
        exitModal.show();

        // Retornar promesa para manejar la confirmación
        return new Promise((resolve) => {
            document.getElementById('confirm-exit-btn').onclick = () => {
                exitModal.hide();
                resolve(true);
            };

            // También manejar el cierre del modal como cancelación
            document.getElementById('exitConfirmationModal').addEventListener('hidden.bs.modal', () => {
                resolve(false);
            }, { once: true });
        });
    }

    // Función mejorada para mostrar errores de validación de asistencia
    showAttendanceValidationError(reason, details = null) {
        // Cerrar el modal de verificación si está abierto
        const verificationModal = bootstrap.Modal.getInstance(document.getElementById('biometricVerificationModal'));
        if (verificationModal) {
            verificationModal.hide();
        }

        // Determinar el tipo de error y configuraciones específicas
        let title = 'Registro No Permitido';
        let message = reason;
        let recommendations = [];
        let iconClass = 'fas fa-exclamation-triangle';
        let headerClass = 'bg-gradient-danger';

        if (reason.includes('entrada abierta') || reason.includes('salida correspondiente')) {
            title = 'Entrada Abierta Detectada';
            message = 'Ya tiene una entrada registrada sin la salida correspondiente. Debe registrar primero su salida antes de marcar una nueva entrada.';
            recommendations = [
                'Registre primero su salida antes de marcar una nueva entrada',
                'Verifique su estado de asistencia actual en el dashboard',
                'Contacte a su supervisor si necesita ayuda con el registro'
            ];
            iconClass = 'fas fa-clock';
            headerClass = 'bg-gradient-warning';
        } else if (reason.includes('horarios disponibles') || reason.includes('completado todos los horarios')) {
            title = 'Horarios Completados';
            message = 'Ya ha completado todos los horarios de trabajo asignados para el día de hoy.';
            recommendations = [
                'Ha completado exitosamente todos sus horarios laborales',
                'Si necesita trabajar horas extras, contacte a su supervisor',
                'Verifique su horario laboral asignado para mañana'
            ];
            iconClass = 'fas fa-calendar-check';
            headerClass = 'bg-gradient-info';
        } else if (reason.includes('8 horas') || reason.includes('tiempo transcurrido') || reason.includes('horario actual')) {
            title = 'Límite de Tiempo Excedido';
            message = 'Han transcurrido más de 8 horas desde el inicio de su horario laboral actual.';
            recommendations = [
                'Ha excedido el límite de 8 horas para este horario',
                'Contacte a RRHH para gestionar horas extras si es necesario',
                'Verifique las políticas de tiempo de su empresa'
            ];
            iconClass = 'fas fa-hourglass-end';
            headerClass = 'bg-gradient-danger';
        } else if (reason.includes('entradas abiertas') || reason.includes('registrar salida')) {
            title = 'Sin Entradas Activas';
            message = 'No tiene entradas activas registradas para poder marcar una salida.';
            recommendations = [
                'Verifique que haya registrado su entrada al inicio de la jornada',
                'Si cree que hay un error, contacte al administrador del sistema',
                'Revise su historial de asistencias para verificar registros'
            ];
            iconClass = 'fas fa-sign-in-alt';
            headerClass = 'bg-gradient-secondary';
        } else {
            recommendations = [
                'Verifique las políticas de asistencia de su empresa',
                'Contacte a su supervisor para más información',
                'Revise su horario de trabajo asignado'
            ];
        }

        // Actualizar el icono del header
        const modalHeader = document.querySelector('#verificationErrorModal .modal-header');
        modalHeader.className = `modal-header ${headerClass} text-white position-relative overflow-hidden`;

        const iconWrapper = document.querySelector('#verificationErrorModal .modal-icon-wrapper');
        if (iconWrapper) {
            iconWrapper.innerHTML = `<i class="${iconClass} fa-2x"></i>`;
        }

        // Mostrar mensaje de error usando la función mejorada
        this.showError(title, message, recommendations, details);
    }

    retryVerification() {
        // Cerrar modal de error
        const errorModal = bootstrap.Modal.getInstance(document.getElementById('verificationErrorModal'));
        errorModal.hide();

        // Reiniciar intentos
        this.attempts[this.currentTab] = 0;

        // Reintentar según la pestaña actual
        switch (this.currentTab) {
            case 'facial':
                this.startFacialVerification();
                break;
            case 'fingerprint':
                this.startFingerprintVerification();
                break;
            case 'rfid':
                this.startRfidVerification();
                break;
        }
    }

    showLoading(message = 'Cargando...') {
        // Crear overlay de carga si no existe
        let loadingOverlay = document.getElementById('loading-overlay');
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loading-overlay';
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2" id="loading-message">${message}</p>
                </div>
            `;
            document.body.appendChild(loadingOverlay);
        }

        document.getElementById('loading-message').textContent = message;
        loadingOverlay.style.display = 'flex';
    }

    hideLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

    // Verificar si se puede registrar asistencia
    async validateAttendanceRegistration(employeeId, attendanceType) {
        try {
            const response = await fetch(`api/attendance/validate-attendance.php?employee_id=${employeeId}&attendance_type=${attendanceType}`);
            if (!response.ok) {
                console.warn('No se pudo validar el registro de asistencia');
                return { canRegister: true, reason: '' }; // Por defecto permitir si hay error
            }

            const result = await response.json();
            if (result.success) {
                return {
                    canRegister: result.can_register,
                    reason: result.block_reason,
                    details: result.validation_details
                };
            }

            return { canRegister: true, reason: '' }; // Por defecto permitir si hay error
        } catch (error) {
            console.error('Error al validar registro de asistencia:', error);
            return { canRegister: true, reason: '' }; // Por defecto permitir si hay error
        }
    }

    // Función mejorada para mostrar errores de validación de asistencia
    showAttendanceValidationError(reason, details = null) {
        // Esta función está duplicada - usar la versión anterior
        return;
    }
            recommendations = [
                'Registre primero su salida antes de marcar una nueva entrada',
                'Verifique su estado de asistencia actual en el dashboard',
                'Contacte a su supervisor si necesita ayuda con el registro'
            ];
            iconClass = 'fas fa-clock';
            headerClass = 'bg-gradient-warning';
        } else if (reason.includes('horarios disponibles') || reason.includes('completado todos los horarios')) {
            title = 'Horarios Completados';
            message = 'Ya ha completado todos los horarios de trabajo asignados para el día de hoy.';
            recommendations = [
                'Ha completado exitosamente todos sus horarios laborales',
                'Si necesita trabajar horas extras, contacte a su supervisor',
                'Verifique su horario laboral asignado para mañana'
            ];
            iconClass = 'fas fa-calendar-check';
            headerClass = 'bg-gradient-info';
        } else if (reason.includes('8 horas') || reason.includes('tiempo transcurrido') || reason.includes('horario actual')) {
            title = 'Límite de Tiempo Excedido';
            message = 'Han transcurrido más de 8 horas desde el inicio de su horario laboral actual.';
            recommendations = [
                'Ha excedido el límite de 8 horas para este horario',
                'Contacte a RRHH para gestionar horas extras si es necesario',
                'Verifique las políticas de tiempo de su empresa'
            ];
            iconClass = 'fas fa-hourglass-end';
            headerClass = 'bg-gradient-danger';
        } else if (reason.includes('entradas abiertas') || reason.includes('registrar salida')) {
            title = 'Sin Entradas Activas';
            message = 'No tiene entradas activas registradas para poder marcar una salida.';
            recommendations = [
                'Verifique que haya registrado su entrada al inicio de la jornada',
                'Si cree que hay un error, contacte al administrador del sistema',
                'Revise su historial de asistencias para verificar registros'
            ];
            iconClass = 'fas fa-sign-in-alt';
            headerClass = 'bg-gradient-secondary';
        } else {
            recommendations = [
                'Verifique las políticas de asistencia de su empresa',
                'Contacte a su supervisor para más información',
                'Revise su horario de trabajo asignado'
            ];
        }

        // Actualizar el icono del header
        const modalHeader = document.querySelector('#verificationErrorModal .modal-header');
        modalHeader.className = `modal-header ${headerClass} text-white position-relative overflow-hidden`;

        const iconWrapper = document.querySelector('#verificationErrorModal .modal-icon-wrapper');
        if (iconWrapper) {
            iconWrapper.innerHTML = `<i class="${iconClass} fa-2x"></i>`;
        }

        // Mostrar mensaje de error usando la función mejorada
        this.showError(title, message, recommendations, details);
    }
}

// Función global para alternar detalles técnicos
function toggleTechnicalDetails() {
    const detailsDiv = document.getElementById('error-technical-details');
    detailsDiv.style.display = detailsDiv.style.display === 'none' ? 'block' : 'none';
}

// Estilos para el overlay de carga
const loadingStyles = `
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-content {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
`;

// Agregar estilos al head
const styleSheet = document.createElement('style');
styleSheet.textContent = loadingStyles;
document.head.appendChild(styleSheet);

// Función global para mostrar confirmación de salida
async function showExitConfirmation(employeeId, employeeName) {
    try {
        // Obtener información de la última entrada del empleado
        const response = await fetch(`api/attendance/last-record.php?employee_id=${employeeId}`);
        const data = await response.json();

        if (!data.success || !data.record) {
            throw new Error('No se pudo obtener la información de entrada');
        }

        const entryTime = data.record.HORA;
        const currentTime = new Date().toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        // Calcular horas trabajadas (simplificado)
        const entryDateTime = new Date(`${data.record.FECHA} ${entryTime}`);
        const currentDateTime = new Date();
        const hoursWorked = ((currentDateTime - entryDateTime) / (1000 * 60 * 60)).toFixed(1);

        // Mostrar modal de confirmación
        const confirmed = await window.biometricVerificationModal.showExitConfirmation(
            entryTime,
            currentTime,
            hoursWorked
        );

        if (confirmed) {
            // Proceder con el registro de salida
            console.log('Salida confirmada, procediendo con el registro...');

            // Aquí se puede agregar la lógica para registrar la salida
            // Por ahora, solo mostramos un mensaje
            alert(`✅ Salida registrada exitosamente\n\nEmpleado: ${employeeName}\nHora de salida: ${currentTime}\nHoras trabajadas: ${hoursWorked}`);

            // Recargar la página para actualizar el estado
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

    } catch (error) {
        console.error('Error al mostrar confirmación de salida:', error);
        alert('Error al procesar la confirmación de salida: ' + error.message);
    }
}

// Hacer la función globalmente disponible
window.showExitConfirmation = showExitConfirmation;

// Inicializar inmediatamente si el DOM ya está listo, sino esperar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.biometricVerificationModal = new BiometricVerificationModal();
    });
} else {
    // DOM ya está listo
    window.biometricVerificationModal = new BiometricVerificationModal();
}
