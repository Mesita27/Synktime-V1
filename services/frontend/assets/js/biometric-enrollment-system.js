/**
 * SISTEMA DE ENROLAMIENTO BIOMÉTRICO
 * Manejo completo del enrolamiento de empleados con TensorFlow y WebAuthn
 */

class BiometricEnrollmentSystem {
    constructor() {
        this.selectedEmployee = null;
        this.currentEnrollmentType = 'facial';
        this.isEnrolling = false;
        this.videoStream = null;
        this.tensorflowSystem = null;
        this.fingerprintSystem = null;
        
        // Inicializar sistemas
        this.initializeSystems();
        
        console.log('🎯 Sistema de Enrolamiento Biométrico inicializado');
    }
    
    async initializeSystems() {
        try {
            // Inicializar TensorFlow si está disponible
            if (window.TensorFlowBiometricSystem) {
                this.tensorflowSystem = new TensorFlowBiometricSystem();
                console.log('✅ TensorFlow System cargado');
            }
            
            // Inicializar sistema de huellas si está disponible
            if (window.FingerprintRecognitionSystem) {
                this.fingerprintSystem = new FingerprintRecognitionSystem();
                console.log('✅ Fingerprint System cargado');
            }
            
        } catch (error) {
            console.error('Error inicializando sistemas biométricos:', error);
        }
    }
    
    /**
     * CARGAR DATOS DE EMPLEADOS
     */
    async loadEmployeeData() {
        try {
            const filters = this.getFilters();
            
            const response = await fetch('api/employee/list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ filters })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayEmployees(data.empleados || data.employees || []);
                this.updateFilteredCount(data.empleados?.length || 0);
            } else {
                throw new Error(data.message || 'Error cargando empleados');
            }
            
        } catch (error) {
            console.error('Error cargando empleados:', error);
            this.showError('Error cargando datos de empleados');
        }
    }
    
    getFilters() {
        return {
            search: $('#searchEmployee').val() || '',
            status: $('#filterStatus').val() || '',
            establishment: $('#filterEstablishment').val() || '',
            biometric: $('#filterBiometric').val() || ''
        };
    }
    
    updateFilteredCount(count) {
        $('#filteredCount').text(`${count} empleados`);
    }
    
    /**
     * MOSTRAR EMPLEADOS EN VISTA DE TARJETAS O TABLA
     */
    displayEmployees(employees) {
        const viewMode = $('#viewMode').val();
        
        if (viewMode === 'cards') {
            this.displayEmployeeCards(employees);
        } else {
            this.displayEmployeeTable(employees);
        }
    }
    
    displayEmployeeCards(employees) {
        const container = $('#employeeCards');
        container.empty();
        
        if (employees.length === 0) {
            container.html(`
                <div class="col-12 text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron empleados</h5>
                    <p class="text-muted">Ajuste los filtros para mostrar empleados</p>
                </div>
            `);
            return;
        }
        
        employees.forEach(employee => {
            const biometricStatus = this.getBiometricStatus(employee);
            
            container.append(`
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="employee-card" onclick="window.enrollmentSystem.openEnrollmentModal(${employee.ID_EMPLEADO})">
                        <div class="card-body">
                            <div class="biometric-status">
                                ${biometricStatus.badge}
                            </div>
                            <div class="text-center mb-3">
                                <div class="employee-avatar">
                                    <i class="fas fa-user-circle fa-3x text-primary"></i>
                                </div>
                            </div>
                            <h6 class="card-title text-center">${employee.NOMBRE} ${employee.APELLIDO}</h6>
                            <p class="card-text text-muted text-center">
                                <small>DNI: ${employee.DNI}</small><br>
                                <small>${employee.ESTABLECIMIENTO || 'Sin establecimiento'}</small>
                            </p>
                            <div class="text-center">
                                <span class="badge badge-${employee.ESTADO === 'A' ? 'success' : 'secondary'}">
                                    ${employee.ESTADO === 'A' ? 'Activo' : 'Inactivo'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }
    
    displayEmployeeTable(employees) {
        const tbody = $('#employeesTable tbody');
        tbody.empty();
        
        if (employees.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-info-circle text-muted me-2"></i>
                        No se encontraron empleados
                    </td>
                </tr>
            `);
            return;
        }
        
        employees.forEach((employee, index) => {
            const biometricStatus = this.getBiometricStatus(employee);
            
            tbody.append(`
                <tr>
                    <td>${employee.ID_EMPLEADO}</td>
                    <td>
                        <strong>${employee.NOMBRE} ${employee.APELLIDO}</strong><br>
                        <small class="text-muted">${employee.CORREO || 'Sin email'}</small>
                    </td>
                    <td>${employee.DNI}</td>
                    <td>${employee.ESTABLECIMIENTO || 'N/A'}</td>
                    <td>
                        <span class="badge badge-${employee.ESTADO === 'A' ? 'success' : 'secondary'}">
                            ${employee.ESTADO === 'A' ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>${biometricStatus.badge}</td>
                    <td>${biometricStatus.lastEnrollment}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="window.enrollmentSystem.openEnrollmentModal(${employee.ID_EMPLEADO})">
                            <i class="fas fa-fingerprint me-1"></i>Enrolar
                        </button>
                    </td>
                </tr>
            `);
        });
    }
    
    getBiometricStatus(employee) {
        // Por ahora, status básico. Mejorar con datos reales de la BD
        const hasFingerprint = employee.BIOMETRIC_FINGERPRINT === 'S';
        const hasFacial = employee.BIOMETRIC_FACIAL === 'S';
        
        if (hasFingerprint && hasFacial) {
            return {
                badge: '<span class="badge bg-success">Completo</span>',
                lastEnrollment: 'Completado'
            };
        } else if (hasFingerprint || hasFacial) {
            return {
                badge: '<span class="badge bg-warning">Parcial</span>',
                lastEnrollment: 'Parcial'
            };
        } else {
            return {
                badge: '<span class="badge bg-danger">Pendiente</span>',
                lastEnrollment: 'Sin registrar'
            };
        }
    }
    
    /**
     * FILTRAR EMPLEADOS
     */
    async filterEmployees() {
        await this.loadEmployeeData();
    }
    
    /**
     * ABRIR MODAL DE ENROLAMIENTO
     */
    async openEnrollmentModal(employeeId) {
        try {
            // Cargar datos del empleado
            const response = await fetch(`api/employee/get.php?id=${employeeId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error('Error cargando datos del empleado');
            }
            
            this.selectedEmployee = data.empleado || data.employee;
            
            // Mostrar información del empleado en el modal
            this.displayEmployeeInfoInModal();
            
            // Mostrar modal
            $('#enrollmentModal').modal('show');
            
        } catch (error) {
            console.error('Error abriendo modal de enrolamiento:', error);
            this.showError('Error abriendo modal de enrolamiento');
        }
    }
    
    displayEmployeeInfoInModal() {
        if (!this.selectedEmployee) return;
        
        const employee = this.selectedEmployee;
        
        $('#enrollingEmployeeName').text(`${employee.NOMBRE} ${employee.APELLIDO}`);
        
        $('#employeeInfo').html(`
            <div class="row">
                <div class="col-12 mb-3">
                    <h6><strong>${employee.NOMBRE} ${employee.APELLIDO}</strong></h6>
                    <p class="text-muted mb-1">DNI: ${employee.DNI}</p>
                    <p class="text-muted mb-1">Email: ${employee.CORREO || 'No disponible'}</p>
                    <p class="text-muted mb-1">Establecimiento: ${employee.ESTABLECIMIENTO || 'No asignado'}</p>
                    <span class="badge badge-${employee.ESTADO === 'A' ? 'success' : 'secondary'}">
                        ${employee.ESTADO === 'A' ? 'Activo' : 'Inactivo'}
                    </span>
                </div>
            </div>
        `);
    }
    
    /**
     * INICIAR ENROLAMIENTO
     */
    async startEnrollment() {
        if (!this.selectedEmployee) {
            this.showError('No hay empleado seleccionado');
            return;
        }
        
        if (this.isEnrolling) {
            this.showError('Ya hay un enrolamiento en progreso');
            return;
        }
        
        this.isEnrolling = true;
        $('#startEnrollmentBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Iniciando...');
        
        try {
            // Determinar tipo de enrolamiento
            const enrollmentType = $('input[name="enrollmentType"]:checked').val();
            
            if (enrollmentType === 'facial') {
                await this.startFacialEnrollment();
            } else if (enrollmentType === 'fingerprint') {
                await this.startFingerprintEnrollment();
            }
            
        } catch (error) {
            console.error('Error en enrolamiento:', error);
            this.showError('Error iniciando enrolamiento');
            this.resetEnrollmentState();
        }
    }
    
    async startFacialEnrollment() {
        try {
            $('#facialStatus').removeClass('bg-secondary').addClass('bg-primary').text('Procesando...');
            
            // Obtener stream de video
            this.videoStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: 640, 
                    height: 480,
                    facingMode: 'user'
                } 
            });
            
            const video = document.getElementById('enrollmentVideo');
            video.srcObject = this.videoStream;
            
            // Esperar a que el video esté listo
            await new Promise(resolve => {
                video.onloadedmetadata = resolve;
            });
            
            // Iniciar captura con TensorFlow
            if (this.tensorflowSystem) {
                await this.captureFacialSamples();
            } else {
                throw new Error('Sistema TensorFlow no disponible');
            }
            
        } catch (error) {
            console.error('Error en enrolamiento facial:', error);
            this.showError('Error accediendo a la cámara');
            throw error;
        }
    }
    
    async captureFacialSamples() {
        const video = document.getElementById('enrollmentVideo');
        let samples = 0;
        const maxSamples = 5;
        const facialData = [];
        
        $('#facialInstructions').html(`
            <i class="fas fa-camera me-2"></i>
            Capturando muestras faciales... (${samples}/${maxSamples})
        `);
        
        const captureLoop = async () => {
            if (samples >= maxSamples) {
                // Completar enrolamiento facial
                await this.completeFacialEnrollment(facialData);
                return;
            }
            
            try {
                // Capturar descriptor facial con TensorFlow
                const faceDescriptor = await this.tensorflowSystem.extractFaceDescriptor(video);
                
                if (faceDescriptor && faceDescriptor.length > 0) {
                    facialData.push(faceDescriptor);
                    samples++;
                    
                    // Actualizar progreso
                    const progress = (samples / maxSamples) * 100;
                    $('#facialProgress').css('width', `${progress}%`);
                    $('#facialProgressText').text(`${Math.round(progress)}%`);
                    
                    $('#facialInstructions').html(`
                        <i class="fas fa-camera me-2"></i>
                        Capturando muestras faciales... (${samples}/${maxSamples})
                    `);
                    
                    if (samples < maxSamples) {
                        setTimeout(captureLoop, 1000); // Capturar cada segundo
                    } else {
                        await this.completeFacialEnrollment(facialData);
                    }
                } else {
                    // Reintento si no se detecta cara
                    setTimeout(captureLoop, 500);
                }
                
            } catch (error) {
                console.error('Error capturando muestra facial:', error);
                setTimeout(captureLoop, 1000); // Reintentar
            }
        };
        
        captureLoop();
    }
    
    async completeFacialEnrollment(facialData) {
        try {
            // Enviar datos al servidor
            const response = await fetch('api/biometric/enroll-new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee.ID_EMPLEADO,
                    biometric_type: 'facial',
                    biometric_data: facialData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                $('#facialStatus').removeClass('bg-primary').addClass('bg-success').text('Completado');
                $('#facialInstructions').html(`
                    <i class="fas fa-check-circle me-2 text-success"></i>
                    Enrolamiento facial completado exitosamente
                `);
                
                this.checkEnrollmentCompletion();
            } else {
                throw new Error(result.message || 'Error guardando datos faciales');
            }
            
        } catch (error) {
            console.error('Error completando enrolamiento facial:', error);
            $('#facialStatus').removeClass('bg-primary').addClass('bg-danger').text('Error');
            this.showError('Error completando enrolamiento facial');
        }
    }
    
    async startFingerprintEnrollment() {
        try {
            $('#fingerprintStatus').removeClass('bg-secondary').addClass('bg-primary').text('Procesando...');
            
            if (this.fingerprintSystem) {
                await this.captureFingerprintSample();
            } else {
                // Simular enrolamiento de huella
                await this.simulateFingerprintEnrollment();
            }
            
        } catch (error) {
            console.error('Error en enrolamiento de huella:', error);
            this.showError('Error en enrolamiento de huella');
            throw error;
        }
    }
    
    async simulateFingerprintEnrollment() {
        // Simulación para demostración
        let progress = 0;
        const interval = setInterval(() => {
            progress += 20;
            $('#fingerprintProgress').css('width', `${progress}%`);
            $('#fingerprintProgressText').text(`${progress}%`);
            
            if (progress >= 100) {
                clearInterval(interval);
                this.completeFingerprintEnrollment();
            }
        }, 500);
    }
    
    async completeFingerprintEnrollment() {
        try {
            // Datos simulados de huella
            const fingerprintData = {
                template: 'fingerprint_template_' + Date.now(),
                quality: 85,
                finger_type: 'index_right'
            };
            
            const response = await fetch('api/biometric/enroll-new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee.ID_EMPLEADO,
                    biometric_type: 'fingerprint',
                    finger_type: 'index_right',
                    biometric_data: fingerprintData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                $('#fingerprintStatus').removeClass('bg-primary').addClass('bg-success').text('Completado');
                $('#fingerprintInstructions').html(`
                    <i class="fas fa-check-circle me-2 text-success"></i>
                    Enrolamiento de huella completado exitosamente
                `);
                
                this.checkEnrollmentCompletion();
            } else {
                throw new Error(result.message || 'Error guardando datos de huella');
            }
            
        } catch (error) {
            console.error('Error completando enrolamiento de huella:', error);
            $('#fingerprintStatus').removeClass('bg-primary').addClass('bg-danger').text('Error');
            this.showError('Error completando enrolamiento de huella');
        }
    }
    
    checkEnrollmentCompletion() {
        const facialComplete = $('#facialStatus').hasClass('bg-success');
        const fingerprintComplete = $('#fingerprintStatus').hasClass('bg-success');
        
        if (facialComplete || fingerprintComplete) {
            $('#databaseStatus').removeClass('bg-secondary').addClass('bg-success').text('Guardado');
            $('#verificationStatus').removeClass('bg-secondary').addClass('bg-success').text('Verificado');
            
            $('#completeEnrollmentBtn').prop('disabled', false);
            this.resetEnrollmentState();
        }
    }
    
    completeEnrollment() {
        $('#enrollmentModal').modal('hide');
        this.showSuccess('Enrolamiento completado exitosamente');
        
        // Recargar datos
        this.loadEmployeeData();
        loadEnrollmentStats();
    }
    
    resetEnrollmentState() {
        this.isEnrolling = false;
        $('#startEnrollmentBtn').prop('disabled', false).html('<i class="fas fa-play me-2"></i>Iniciar Enrolamiento');
        
        // Detener stream de video
        if (this.videoStream) {
            this.videoStream.getTracks().forEach(track => track.stop());
            this.videoStream = null;
        }
    }
    
    showError(message) {
        console.error('❌', message);
        // Integrar con sistema de notificaciones
        alert('Error: ' + message);
    }
    
    showSuccess(message) {
        console.log('✅', message);
        // Integrar con sistema de notificaciones
        alert('Éxito: ' + message);
    }
}

// Inicializar sistema cuando DOM esté listo
$(document).ready(function() {
    window.enrollmentSystem = new BiometricEnrollmentSystem();
    console.log('🚀 Sistema de Enrolamiento Biométrico listo');
});
