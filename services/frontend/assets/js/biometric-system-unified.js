/**
 * Sistema Biométrico Unificado - SynkTime
 * 
 * Este archivo unifica todas las funcionalidades del sistema biométrico
 * de los diferentes scripts dispersos en el proyecto.
 */

// Módulo principal del sistema biométrico
const BiometricSystem = (function() {
    // Configuración por defecto
    const defaultConfig = {
        apiEndpoints: {
            enrollFacial: 'api/biometric/enroll-facial.php',
            enrollFingerprint: 'api/biometric/enroll-fingerprint.php',
            verifyFacial: 'api/biometric/verify-facial.php',
            verifyFingerprint: 'api/biometric/verify-fingerprint.php',
            biometricStatus: 'api/biometric/status.php',
            employeeList: 'api/biometric/enrollment-employees.php'
        },
        faceDetection: {
            modelPath: 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7',
            minConfidence: 0.5,
            maxFaces: 1
        },
        enrollmentSettings: {
            facialSamples: 5,
            fingerprintSamples: 3
        },
        uiElements: {
            faceVideo: 'faceVideo',
            faceCanvas: 'faceCanvas',
            startFaceButton: 'startFaceCamera',
            captureButton: 'captureFace',
            enrollButton: 'enrollBiometric',
            statusIndicator: {
                facial: 'facial-status',
                fingerprint: 'fingerprint-status'
            },
            employeeInfo: {
                id: 'modal-employee-code',
                name: 'modal-employee-name'
            },
            enrollmentProgress: 'enrollment-progress',
            enrollmentMessage: 'enrollment-message'
        }
    };
    
    // Variables globales
    let state = {
        initialized: false,
        filters: {},
        camera: {
            active: false,
            stream: null,
            facing: 'user' // 'user' para cámara frontal, 'environment' para trasera
        },
        faceDetection: {
            model: null,
            initialized: false
        },
        employee: {
            id: null,
            name: '',
            biometricStatus: {
                facial: false,
                fingerprint: false
            }
        },
        enrollment: {
            inProgress: false,
            type: null, // 'facial' o 'fingerprint'
            samples: [],
            currentSample: 0,
            totalSamples: 0
        }
    };
    
    // Elementos de la UI
    let ui = {};
    
    // Inicializar el sistema
    function initialize(customConfig = {}) {
        try {
            console.log('🚀 Inicializando sistema biométrico unificado...');
            
            // Fusionar configuración por defecto con la personalizada
            const config = {...defaultConfig, ...customConfig};
            
            // Obtener elementos de la UI
            ui = getUIElements(config.uiElements);
            
            // Agregar listeners a los botones
            setupEventListeners();
            
            // Inicializar detector facial
            initFaceDetection();
            
            state.initialized = true;
            console.log('✅ Sistema biométrico inicializado correctamente');
            
            return true;
        } catch (error) {
            console.error('❌ Error al inicializar el sistema biométrico:', error);
            return false;
        }
    }
    
    // Obtener elementos de la UI
    function getUIElements(elementConfig) {
        const elements = {};
        
        for (const [key, value] of Object.entries(elementConfig)) {
            if (typeof value === 'object') {
                elements[key] = {};
                for (const [subKey, id] of Object.entries(value)) {
                    elements[key][subKey] = document.getElementById(id);
                }
            } else {
                elements[key] = document.getElementById(value);
            }
        }
        
        return elements;
    }
    
    // Configurar event listeners
    function setupEventListeners() {
        if (ui.startFaceButton) {
            ui.startFaceButton.addEventListener('click', toggleCamera);
        }
        
        if (ui.captureButton) {
            ui.captureButton.addEventListener('click', captureFacialSample);
        }
        
        if (ui.enrollButton) {
            ui.enrollButton.addEventListener('click', startEnrollment);
        }
    }
    
    // Inicializar detección facial con BlazeFace
    async function initFaceDetection() {
        if (!window.tf || !window.blazeface) {
            console.warn('⚠️ TensorFlow.js o BlazeFace no están disponibles');
            return false;
        }
        
        try {
            console.log('🧠 Cargando modelo de detección facial...');
            state.faceDetection.model = await blazeface.load();
            state.faceDetection.initialized = true;
            console.log('✅ Modelo de detección facial cargado correctamente');
            return true;
        } catch (error) {
            console.error('❌ Error al cargar el modelo de detección facial:', error);
            return false;
        }
    }
    
    // Alternar la cámara (encender/apagar)
    async function toggleCamera() {
        if (state.camera.active) {
            stopCamera();
            return;
        }
        
        try {
            console.log('📸 Iniciando cámara...');
            
            if (!ui.faceVideo) {
                throw new Error('Elemento de video no encontrado');
            }
            
            // Solicitar acceso a la cámara
            state.camera.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: state.camera.facing,
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            });
            
            // Asignar stream al elemento de video
            ui.faceVideo.srcObject = state.camera.stream;
            state.camera.active = true;
            
            // Actualizar texto del botón
            if (ui.startFaceButton) {
                ui.startFaceButton.innerHTML = '<i class="fas fa-camera-slash"></i> Detener Cámara';
                ui.startFaceButton.classList.replace('btn-success', 'btn-danger');
            }
            
            // Iniciar la detección facial
            if (ui.faceCanvas && state.faceDetection.initialized) {
                startFaceDetection();
            }
            
            console.log('✅ Cámara iniciada correctamente');
            return true;
        } catch (error) {
            console.error('❌ Error al iniciar la cámara:', error);
            showNotification('Error al acceder a la cámara: ' + error.message, 'error');
            return false;
        }
    }
    
    // Detener la cámara
    function stopCamera() {
        if (!state.camera.active || !state.camera.stream) return;
        
        try {
            // Detener todos los tracks del stream
            state.camera.stream.getTracks().forEach(track => track.stop());
            
            // Limpiar el stream del elemento de video
            if (ui.faceVideo) {
                ui.faceVideo.srcObject = null;
            }
            
            state.camera.active = false;
            
            // Actualizar texto del botón
            if (ui.startFaceButton) {
                ui.startFaceButton.innerHTML = '<i class="fas fa-camera"></i> Iniciar Cámara';
                ui.startFaceButton.classList.replace('btn-danger', 'btn-success');
            }
            
            console.log('✅ Cámara detenida correctamente');
            return true;
        } catch (error) {
            console.error('❌ Error al detener la cámara:', error);
            return false;
        }
    }
    
    // Iniciar la detección facial
    function startFaceDetection() {
        if (!state.camera.active || !state.faceDetection.initialized) return;
        
        const canvas = ui.faceCanvas;
        const video = ui.faceVideo;
        
        if (!canvas || !video) return;
        
        // Ajustar tamaño del canvas al video
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        const ctx = canvas.getContext('2d');
        
        // Función para detectar rostros en cada frame
        async function detectFaces() {
            if (!state.camera.active) return;
            
            try {
                // Dibujar el frame actual en el canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Detectar rostros en el frame
                const predictions = await state.faceDetection.model.estimateFaces(video);
                
                // Limpiar canvas para dibujar detecciones
                ctx.fillStyle = 'rgba(0, 0, 0, 0.2)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Dibujar detecciones
                if (predictions.length > 0) {
                    predictions.forEach(prediction => {
                        // Obtener coordenadas del rostro
                        const start = prediction.topLeft;
                        const end = prediction.bottomRight;
                        const size = [end[0] - start[0], end[1] - start[1]];
                        
                        // Dibujar rectángulo alrededor del rostro
                        ctx.strokeStyle = '#00ff00';
                        ctx.lineWidth = 2;
                        ctx.strokeRect(start[0], start[1], size[0], size[1]);
                        
                        // Dibujar puntos de referencia (ojos, nariz, boca)
                        const landmarks = prediction.landmarks;
                        ctx.fillStyle = '#ff0000';
                        landmarks.forEach(landmark => {
                            ctx.beginPath();
                            ctx.arc(landmark[0], landmark[1], 3, 0, 2 * Math.PI);
                            ctx.fill();
                        });
                    });
                }
                
                // Continuar detección en el siguiente frame
                requestAnimationFrame(detectFaces);
            } catch (error) {
                console.error('Error en la detección facial:', error);
            }
        }
        
        // Iniciar la detección cuando el video esté listo
        video.addEventListener('loadedmetadata', () => {
            console.log('🔍 Iniciando detección facial...');
            detectFaces();
        });
    }
    
    // Capturar una muestra facial
    async function captureFacialSample() {
        if (!state.camera.active || !ui.faceCanvas) {
            showNotification('La cámara no está activa', 'warning');
            return false;
        }
        
        try {
            console.log('📸 Capturando muestra facial...');
            
            // Verificar que haya un rostro detectado
            if (state.faceDetection.initialized && ui.faceVideo) {
                const predictions = await state.faceDetection.model.estimateFaces(ui.faceVideo);
                
                if (predictions.length === 0) {
                    showNotification('No se detectó ningún rostro', 'warning');
                    return false;
                }
            }
            
            // Capturar imagen del canvas
            const imageData = ui.faceCanvas.toDataURL('image/jpeg', 0.8);
            
            // Si está en proceso de inscripción, agregar muestra
            if (state.enrollment.inProgress && state.enrollment.type === 'facial') {
                state.enrollment.samples.push(imageData);
                state.enrollment.currentSample++;
                
                // Actualizar progreso
                updateEnrollmentProgress();
                
                // Si se completaron todas las muestras, proceder a la inscripción
                if (state.enrollment.currentSample >= state.enrollment.totalSamples) {
                    console.log('✅ Muestras faciales completadas, procediendo a inscripción...');
                    processFacialEnrollment();
                } else {
                    showNotification(`Muestra ${state.enrollment.currentSample} de ${state.enrollment.totalSamples} capturada`, 'success');
                }
            }
            
            return imageData;
        } catch (error) {
            console.error('❌ Error al capturar muestra facial:', error);
            showNotification('Error al capturar muestra facial', 'error');
            return false;
        }
    }
    
    // Iniciar proceso de inscripción biométrica
    function startEnrollment(type = 'facial') {
        if (!state.employee.id) {
            showNotification('No hay un empleado seleccionado', 'warning');
            return false;
        }
        
        try {
            console.log(`🔐 Iniciando inscripción ${type}...`);
            
            state.enrollment.inProgress = true;
            state.enrollment.type = type;
            state.enrollment.samples = [];
            state.enrollment.currentSample = 0;
            
            // Configurar número de muestras según el tipo
            if (type === 'facial') {
                state.enrollment.totalSamples = defaultConfig.enrollmentSettings.facialSamples;
                
                // Iniciar cámara si no está activa
                if (!state.camera.active) {
                    toggleCamera();
                }
                
                showNotification(`Presione "Capturar" para tomar ${state.enrollment.totalSamples} muestras faciales`, 'info');
            } else if (type === 'fingerprint') {
                state.enrollment.totalSamples = defaultConfig.enrollmentSettings.fingerprintSamples;
                showNotification(`Coloque su huella ${state.enrollment.totalSamples} veces cuando se le indique`, 'info');
                // Aquí iría la lógica específica para captura de huellas
            }
            
            // Actualizar UI
            updateEnrollmentProgress();
            
            return true;
        } catch (error) {
            console.error(`❌ Error al iniciar inscripción ${type}:`, error);
            showNotification(`Error al iniciar inscripción ${type}`, 'error');
            return false;
        }
    }
    
    // Actualizar progreso de inscripción
    function updateEnrollmentProgress() {
        if (!state.enrollment.inProgress) return;
        
        const progress = Math.round((state.enrollment.currentSample / state.enrollment.totalSamples) * 100);
        
        if (ui.enrollmentProgress) {
            ui.enrollmentProgress.style.width = `${progress}%`;
            ui.enrollmentProgress.setAttribute('aria-valuenow', progress);
            ui.enrollmentProgress.textContent = `${progress}%`;
        }
        
        if (ui.enrollmentMessage) {
            ui.enrollmentMessage.textContent = `Muestra ${state.enrollment.currentSample} de ${state.enrollment.totalSamples}`;
        }
    }
    
    // Procesar inscripción facial
    async function processFacialEnrollment() {
        if (!state.enrollment.inProgress || state.enrollment.type !== 'facial') return;
        
        try {
            console.log('📤 Enviando datos de inscripción facial al servidor...');
            showNotification('Procesando inscripción...', 'info');
            
            // Preparar datos para enviar
            const enrollmentData = {
                employee_id: state.employee.id,
                facial_data: state.enrollment.samples
            };
            
            // Enviar al servidor
            const response = await fetch(defaultConfig.apiEndpoints.enrollFacial, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(enrollmentData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Inscripción facial exitosa:', result);
                showNotification('Inscripción facial completada con éxito', 'success');
                
                // Actualizar estado biométrico
                updateBiometricStatus();
                
                // Reiniciar estado de inscripción
                resetEnrollment();
            } else {
                console.error('❌ Error en inscripción facial:', result.message);
                showNotification('Error en inscripción facial: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('❌ Error al procesar inscripción facial:', error);
            showNotification('Error al procesar inscripción facial', 'error');
        }
    }
    
    // Actualizar estado biométrico de un empleado
    async function updateBiometricStatus() {
        if (!state.employee.id) return;
        
        try {
            console.log('🔄 Actualizando estado biométrico...');
            
            // Solicitar estado biométrico actual
            const response = await fetch(`${defaultConfig.apiEndpoints.biometricStatus}?employee_id=${state.employee.id}`);
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Estado biométrico actualizado:', result);
                
                // Actualizar estado interno
                state.employee.biometricStatus = {
                    facial: result.status.facial || false,
                    fingerprint: result.status.fingerprint || false
                };
                
                // Actualizar UI
                updateBiometricStatusUI();
            } else {
                console.error('❌ Error al obtener estado biométrico:', result.message);
            }
        } catch (error) {
            console.error('❌ Error al actualizar estado biométrico:', error);
        }
    }
    
    // Actualizar UI con estado biométrico
    function updateBiometricStatusUI() {
        // Actualizar indicador facial
        if (ui.statusIndicator && ui.statusIndicator.facial) {
            ui.statusIndicator.facial.className = state.employee.biometricStatus.facial ? 
                'badge bg-success' : 'badge bg-secondary';
            ui.statusIndicator.facial.textContent = state.employee.biometricStatus.facial ? 
                'Inscrito' : 'Pendiente';
        }
        
        // Actualizar indicador huella
        if (ui.statusIndicator && ui.statusIndicator.fingerprint) {
            ui.statusIndicator.fingerprint.className = state.employee.biometricStatus.fingerprint ? 
                'badge bg-success' : 'badge bg-secondary';
            ui.statusIndicator.fingerprint.textContent = state.employee.biometricStatus.fingerprint ? 
                'Inscrito' : 'Pendiente';
        }
    }
    
    // Reiniciar estado de inscripción
    function resetEnrollment() {
        state.enrollment.inProgress = false;
        state.enrollment.type = null;
        state.enrollment.samples = [];
        state.enrollment.currentSample = 0;
        state.enrollment.totalSamples = 0;
        
        // Reiniciar UI
        if (ui.enrollmentProgress) {
            ui.enrollmentProgress.style.width = '0%';
            ui.enrollmentProgress.setAttribute('aria-valuenow', 0);
            ui.enrollmentProgress.textContent = '0%';
        }
        
        if (ui.enrollmentMessage) {
            ui.enrollmentMessage.textContent = '';
        }
    }
    
    // Abrir modal de inscripción biométrica para un empleado
    function openEnrollmentModal(employeeId, employeeName, establishmentName = '') {
        try {
            console.log('🔄 Abriendo modal para empleado:', employeeId, employeeName);
            
            // Actualizar datos del empleado
            state.employee.id = employeeId;
            state.employee.name = employeeName;
            
            // Actualizar campos ocultos para compatibilidad con biometric-blazeface.js
            const hiddenFields = [
                'employee_id',
                'hidden_employee_id',
                'current-employee-id',
                'display-employee-id'
            ];
            
            hiddenFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) element.value = employeeId;
            });
            
            // Actualizar UI del modal
            const nameField = document.getElementById('modal-employee-name');
            const codeField = document.getElementById('modal-employee-code');
            const displayIdField = document.getElementById('display-employee-id');
            const establishmentField = document.getElementById('modal-employee-establishment');
            
            if (nameField) nameField.textContent = employeeName;
            if (codeField) codeField.textContent = employeeId;
            if (displayIdField) displayIdField.textContent = employeeId;
            if (establishmentField) establishmentField.textContent = establishmentName || '-';
            
            // Obtener estado biométrico
            updateBiometricStatus();
            
            // Mostrar modal
            const modal = document.getElementById('biometricEnrollmentModal');
            if (!modal) {
                console.error('❌ Modal no encontrado');
                showNotification({
                    type: 'error',
                    message: 'El modal de enrolamiento biométrico no está disponible'
                });
                return false;
            }
            
            try {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                console.log('✅ Modal abierto exitosamente');
                return true;
            } catch (modalError) {
                console.error('❌ Error al abrir modal con bootstrap:', modalError);
                
                // Intentar con jQuery como fallback
                if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
                    try {
                        $(modal).modal('show');
                        console.log('✅ Modal abierto con jQuery');
                        return true;
                    } catch (jqError) {
                        console.error('❌ Error al abrir modal con jQuery:', jqError);
                        return false;
                    }
                }
                
                return false;
            }
        } catch (error) {
            console.error('❌ Error al abrir modal de inscripción:', error);
            return false;
        }
    }
    
    // Mostrar notificación
    function showNotification(message, type = 'info') {
        console.log(`[${type}] ${message}`);
        
        // Si existe la función window.showNotification, usarla
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }
        
        // Alternativa: mostrar notificación en la UI
        const alertClass = {
            success: 'alert-success',
            error: 'alert-danger',
            warning: 'alert-warning',
            info: 'alert-info'
        };
        
        // Crear elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass[type] || 'alert-info'} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        `;
        
        // Agregar al DOM
        const container = document.querySelector('.main-content') || document.body;
        container.insertAdjacentElement('afterbegin', alertDiv);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
    
    // Cargar datos de empleados para inscripción biométrica
    async function loadEmployeeData(filters = {}) {
        try {
            console.log('🔄 Cargando datos de empleados...');
            
            // Mostrar indicador de búsqueda
            if (typeof window.showSearchIndicator === 'function') {
                window.showSearchIndicator();
            }
            
            // Construir parámetros
            const params = new URLSearchParams();
            
            // Añadir filtros
            for (const [key, value] of Object.entries(filters)) {
                if (value) params.append(key, value);
            }
            
            // Guardar filtros en el estado
            state.filters = filters;
            
            // Añadir timestamp para evitar caché
            params.append('_t', Date.now());
            
            console.log('Parámetros de la solicitud:', params.toString());
            
            // Solicitar datos
            const response = await fetch(`${defaultConfig.apiEndpoints.employeeList}?${params.toString()}`);
            const jsonData = await response.json();
            
            if (jsonData.success) {
                console.log(`✅ Cargados ${jsonData.data?.length || 0} empleados`);
                
                // Actualizar tabla de empleados
                updateEmployeeTable(jsonData.data || []);
                
                // Actualizar estadísticas
                updateStats(jsonData.stats);
                
                // Ocultar indicador de búsqueda
                if (typeof window.hideSearchIndicator === 'function') {
                    window.hideSearchIndicator();
                }
                
                // Mostrar notificación con resultados si hay filtros aplicados
                if (Object.values(filters).some(v => v) && typeof window.showNotification === 'function') {
                    window.showNotification({
                        type: 'success',
                        message: `Se encontraron ${jsonData.data?.length || 0} empleados`,
                        duration: 3000
                    });
                }
                
                return jsonData.data || [];
            } else {
                console.error('❌ Error al cargar empleados:', jsonData.message);
                
                // Ocultar indicador de búsqueda
                if (typeof window.hideSearchIndicator === 'function') {
                    window.hideSearchIndicator();
                }
                
                // Mostrar notificación de error
                if (typeof window.showNotification === 'function') {
                    window.showNotification({
                        type: 'warning',
                        message: jsonData.message || 'No se encontraron empleados con los filtros aplicados'
                    });
                }
                return [];
            }
        } catch (error) {
            console.error('❌ Error al cargar datos de empleados:', error);
            
            // Ocultar indicador de búsqueda
            if (typeof window.hideSearchIndicator === 'function') {
                window.hideSearchIndicator();
            }
            
            // Mostrar notificación de error
            if (typeof window.showNotification === 'function') {
                window.showNotification({
                    type: 'error',
                    message: 'Error al cargar datos de empleados: ' + (error.message || 'Error de conexión')
                });
            }
            return [];
        }
    }
    
    // Actualizar tabla de empleados
    function updateEmployeeTable(employees) {
        const tableBody = document.getElementById('employeeTableBody');
        if (!tableBody) return;
        
        if (!employees || employees.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No se encontraron empleados</h5>
                            <p>Intente con otros filtros de búsqueda</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        
        employees.forEach(employee => {
            // Información básica
            const employeeId = employee.ID_EMPLEADO || employee.id || employee.codigo;
            const nombreCompleto = `${employee.NOMBRE || ''} ${employee.APELLIDO || ''}`.trim();
            const sede = employee.sede || employee.SEDE || '-';
            const establecimiento = employee.establecimiento || employee.ESTABLECIMIENTO || '-';
            
            // Estado biométrico
            const facialStatus = employee.facial_enrolled ? 
                '<span class="badge bg-success"><i class="fas fa-check"></i> Registrado</span>' : 
                '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendiente</span>';
            
            const fingerprintStatus = employee.fingerprint_enrolled ? 
                '<span class="badge bg-success"><i class="fas fa-check"></i> Registrado</span>' : 
                '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendiente</span>';
            
            html += `
                <tr>
                    <td><strong>${employeeId}</strong></td>
                    <td>${nombreCompleto}</td>
                    <td>${sede}</td>
                    <td>${establecimiento}</td>
                    <td class="text-center">${facialStatus}</td>
                    <td class="text-center">${fingerprintStatus}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-primary btn-sm me-1" 
                                onclick="BiometricSystem.openEnrollmentModal(${employeeId}, '${nombreCompleto}', '${establecimiento}')"
                                title="Enrolar empleado">
                            <i class="fas fa-fingerprint"></i> Enrolar
                        </button>
                        <button type="button" class="btn btn-info btn-sm" 
                                onclick="viewEnrollmentHistory(${employeeId})"
                                title="Ver historial">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
    }
    
    // Función para actualizar estadísticas
    function updateStats(stats) {
        console.log('📊 Actualizando estadísticas:', stats);
        if (stats) {
            // Actualizar elementos de estadísticas si existen
            const totalEl = document.getElementById('total-empleados');
            const facialEl = document.getElementById('facial-enrolled');
            const fingerprintEl = document.getElementById('fingerprint-enrolled');
            const pendingEl = document.getElementById('pending-enrollment');
            
            if (totalEl) totalEl.textContent = stats.total || 0;
            if (facialEl) facialEl.textContent = stats.facial_enrolled || 0;
            if (fingerprintEl) fingerprintEl.textContent = stats.fingerprint_enrolled || 0;
            if (pendingEl) pendingEl.textContent = stats.pending || 0;
        }
    }
    
    // Actualizar estadísticas biométricas
    async function updateBiometricStats(data) {
        try {
            // Si se proporcionaron datos, usarlos directamente
            if (data && data.stats) {
                updateStatsUI(data.stats);
                return;
            }
            
            // De lo contrario, solicitar al servidor
            console.log('📊 Obteniendo estadísticas biométricas...');
            const response = await fetch('api/biometric/stats.php');
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Estadísticas obtenidas:', result);
                updateStatsUI(result.stats);
            } else {
                console.error('❌ Error al obtener estadísticas:', result.message);
            }
        } catch (error) {
            console.error('❌ Error al actualizar estadísticas:', error);
        }
    }
    
    // Actualizar UI con estadísticas
    function updateStatsUI(stats) {
        // Actualizar contadores
        const elements = {
            totalEmployees: document.getElementById('totalEmployees'),
            enrolledCount: document.getElementById('enrolledCount'),
            pendingCount: document.getElementById('pendingCount'),
            enrollmentPercentage: document.getElementById('enrollmentPercentage')
        };
        
        if (elements.totalEmployees) {
            elements.totalEmployees.textContent = stats.total || 0;
        }
        
        if (elements.enrolledCount) {
            elements.enrolledCount.textContent = stats.enrolled || 0;
        }
        
        if (elements.pendingCount) {
            elements.pendingCount.textContent = stats.pending || 0;
        }
        
        if (elements.enrollmentPercentage) {
            elements.enrollmentPercentage.textContent = `${stats.percentage || 0}%`;
        }
    }
    
    // Función diagnóstica para verificar el sistema
    function diagnosticoBiometrico() {
        console.log('🔍 Ejecutando diagnóstico del sistema biométrico...');
        
        // Verificar inicialización
        console.log(`Sistema inicializado: ${state.initialized ? '✅' : '❌'}`);
        
        // Verificar modelo facial
        console.log(`Detector facial cargado: ${state.faceDetection.initialized ? '✅' : '❌'}`);
        
        // Verificar elementos críticos
        const criticalElements = [
            'employeeTableBody',
            'btnBuscarEmpleados',
            'biometricEnrollmentModal',
            'startFaceCamera',
            'faceVideo',
            'faceCanvas'
        ];
        
        criticalElements.forEach(id => {
            const element = document.getElementById(id);
            console.log(`Elemento ${id}: ${element ? '✅' : '❌'}`);
        });
        
        // Verificar si Bootstrap está disponible
        console.log(`Bootstrap disponible: ${typeof bootstrap !== 'undefined' ? '✅' : '❌'}`);
        console.log(`jQuery disponible: ${typeof $ !== 'undefined' ? '✅' : '❌'}`);
        
        // Verificar endpoints
        console.log('API Endpoints:');
        Object.entries(defaultConfig.apiEndpoints).forEach(([key, endpoint]) => {
            console.log(`- ${key}: ${endpoint}`);
        });
        
        console.log('🔍 Diagnóstico completo');
    }
    
    // Función para filtrar empleados con búsqueda
    function filterEmployees() {
        const searchField = document.getElementById('busqueda_empleado');
        const sedeSelect = document.getElementById('filtro_sede');
        const establecimientoSelect = document.getElementById('filtro_establecimiento');
        const estadoSelect = document.getElementById('filtro_estado');
        
        const filters = {
            busqueda: searchField ? searchField.value.trim() : '',
            sede: sedeSelect ? sedeSelect.value : '',
            establecimiento: establecimientoSelect ? establecimientoSelect.value : '',
            estado: estadoSelect ? estadoSelect.value : ''
        };
        
        // Mostrar indicador de búsqueda si existe la función
        if (typeof window.showSearchIndicator === 'function') {
            window.showSearchIndicator();
        }
        
        // Reiniciar paginación
        if (typeof window.currentPage !== 'undefined') {
            window.currentPage = 1;
        }
        
        // Cargar datos filtrados
        return loadEmployeeData(filters);
    }
    
    // Inicializar filtros y eventos
    function initializeFilterEvents() {
        console.log('🔍 Inicializando eventos para filtros y búsqueda...');
        
        try {
            // Botón de búsqueda
            const searchButton = document.getElementById('btnBuscarEmpleados');
            if (searchButton) {
                searchButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🔍 Botón de búsqueda clickeado');
                    filterEmployees();
                    return false;
                });
                
                console.log('✅ Botón de búsqueda configurado');
            }
            
            // Campo de búsqueda (tecla Enter)
            const searchField = document.getElementById('busqueda_empleado');
            if (searchField) {
                searchField.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        console.log('⌨️ Tecla Enter presionada en campo de búsqueda');
                        filterEmployees();
                        return false;
                    }
                });
                
                console.log('✅ Campo de búsqueda configurado para tecla Enter');
            }
            
            // Botón de limpiar
            const clearButton = document.getElementById('btnLimpiarFiltros');
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    console.log('🧹 Limpiando filtros...');
                    
                    // Limpiar campos
                    if (searchField) searchField.value = '';
                    
                    const sedeSelect = document.getElementById('filtro_sede');
                    if (sedeSelect) sedeSelect.value = '';
                    
                    const establecimientoSelect = document.getElementById('filtro_establecimiento');
                    if (establecimientoSelect) establecimientoSelect.value = '';
                    
                    const estadoSelect = document.getElementById('filtro_estado');
                    if (estadoSelect) estadoSelect.value = '';
                    
                    // Reiniciar paginación y cargar datos
                    if (typeof window.currentPage !== 'undefined') {
                        window.currentPage = 1;
                    }
                    
                    loadEmployeeData();
                });
                
                console.log('✅ Botón de limpiar configurado');
            }
            
            // Eventos de cambio para los selectores
            const selectors = ['filtro_sede', 'filtro_establecimiento', 'filtro_estado'];
            selectors.forEach(id => {
                const selector = document.getElementById(id);
                if (selector) {
                    selector.addEventListener('change', function() {
                        // Sedes cargan establecimientos
                        if (id === 'filtro_sede') {
                            loadEstablecimientos(this.value);
                        }
                    });
                }
            });
            
            console.log('✅ Eventos de filtros inicializados correctamente');
        } catch (error) {
            console.error('❌ Error al inicializar eventos:', error);
        }
    }
    
    // Cargar sedes y establecimientos
    function loadSedesYEstablecimientos() {
        console.log('🏢 Cargando sedes y establecimientos...');
        
        // Cargar sedes
        fetch('api/get-sedes.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.sedes) {
                    const sedeSelect = document.getElementById('filtro_sede');
                    if (sedeSelect) {
                        sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';
                        data.sedes.forEach(sede => {
                            const option = document.createElement('option');
                            option.value = sede.ID_SEDE;
                            option.textContent = sede.NOMBRE;
                            sedeSelect.appendChild(option);
                        });
                        
                        console.log(`✅ ${data.sedes.length} sedes cargadas`);
                        
                        // Configurar evento change para cargar establecimientos
                        sedeSelect.addEventListener('change', function() {
                            loadEstablecimientos(this.value);
                        });
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error en solicitud de sedes:', error);
            });
            
        // Cargar todos los establecimientos inicialmente
        loadEstablecimientos();
    }
    
    // Cargar establecimientos filtrados por sede
    function loadEstablecimientos(sedeId = '') {
        const url = sedeId ? `api/get-establecimientos.php?sede_id=${sedeId}` : 'api/get-establecimientos.php';
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.establecimientos) {
                    const establecimientoSelect = document.getElementById('filtro_establecimiento');
                    if (establecimientoSelect) {
                        establecimientoSelect.innerHTML = '<option value="">Todos los establecimientos</option>';
                        data.establecimientos.forEach(est => {
                            const option = document.createElement('option');
                            option.value = est.ID_ESTABLECIMIENTO;
                            option.textContent = est.NOMBRE;
                            establecimientoSelect.appendChild(option);
                        });
                        
                        console.log(`✅ ${data.establecimientos.length} establecimientos cargados`);
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error en solicitud de establecimientos:', error);
            });
    }
    
    // Inicializar el módulo completo
    function initModule() {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando módulo biométrico completo...');
            
            // Inicializar sistema biométrico
            initialize();
            
            // Cargar sedes y establecimientos
            loadSedesYEstablecimientos();
            
            // Configurar eventos de filtro
            initializeFilterEvents();
            
            // Cargar datos iniciales
            loadEmployeeData();
            
            // Actualizar estadísticas
            updateBiometricStats();
            
            console.log('✅ Módulo biométrico inicializado completamente');
        });
    }
    
    // Exponer API pública
    return {
        initialize,
        toggleCamera,
        openEnrollmentModal,
        loadEmployeeData,
        updateBiometricStats,
        diagnosticoBiometrico,
        filterEmployees,
        initializeFilterEvents,
        loadSedesYEstablecimientos,
        initModule
    };
})();

// Inicializar el módulo cuando el DOM esté listo
BiometricSystem.initModule();

// Exponer funciones globales necesarias para la compatibilidad con código existente
window.openEnrollmentModal = BiometricSystem.openEnrollmentModal;
window.loadEmployeeData = BiometricSystem.loadEmployeeData;
window.updateBiometricStats = BiometricSystem.updateBiometricStats;
window.diagnosticoBiometrico = BiometricSystem.diagnosticoBiometrico;
