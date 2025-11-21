/**
 * SISTEMA DE ASISTENCIA BIOMÉTRICA OPTIMIZADO
 * Gestión de modales, selección de empleados y verificación biométrica
 */

class AttendanceBiometricSystem {
    constructor() {
        this.selectedEmployee = null;
        this.currentVerificationMethod = null;
        this.biometricSystem = null;
        this.videoStream = null;
        this.isVerifying = false;
        
        // Inicializar sistemas biométricos
        this.initializeBiometricSystems();
        
        // Configurar eventos
        this.setupEventListeners();
        
        console.log('🎯 Sistema de Asistencia Biométrica inicializado');
    }
    
    /**
     * INICIALIZAR SISTEMAS BIOMÉTRICOS
     */
    async initializeBiometricSystems() {
        try {
            // Inicializar TensorFlow para reconocimiento facial
            if (window.TensorFlowBiometricSystem) {
                this.tensorflowSystem = new TensorFlowBiometricSystem();
                console.log('✅ TensorFlow Biometric System cargado');
            }
            
            // Inicializar sistema de huellas dactilares
            if (window.FingerprintRecognitionSystem) {
                this.fingerprintSystem = new FingerprintRecognitionSystem();
                console.log('✅ Fingerprint Recognition System cargado');
            }
            
            // Fallback al sistema optimizado anterior
            if (window.OptimizedBiometricSystem) {
                this.fallbackSystem = new OptimizedBiometricSystem();
                console.log('✅ Fallback Biometric System cargado');
            }
            
        } catch (error) {
            console.error('❌ Error inicializando sistemas biométricos:', error);
        }
    }
    
    /**
     * CONFIGURAR EVENT LISTENERS
     */
    setupEventListeners() {
        // Event listeners para selección de empleados
        $(document).on('keyup', '#searchEmployee', (e) => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.searchEmployees(), 300);
        });
        
        $(document).on('change', '#filterEstablishment, #filterStatus, #filterBiometric', () => {
            this.searchEmployees();
        });
        
        // Event listeners para selección de método de verificación
        $(document).on('click', '.verification-option', (e) => {
            this.selectVerificationMethod($(e.currentTarget));
        });
        
        // Event listeners para tabla de empleados
        $(document).on('click', '#employeesTable tbody tr', (e) => {
            this.selectEmployee($(e.currentTarget));
        });
        
        // Event listeners para modales
        $('#employeeSelectionModal').on('shown.bs.modal', () => {
            this.loadEmployeeData();
        });
        
        $('#biometricAttendanceModal').on('shown.bs.modal', () => {
            this.initializeAttendanceModal();
        });
        
        $('#biometricAttendanceModal').on('hidden.bs.modal', () => {
            this.cleanupAttendanceModal();
        });
    }
    
    /**
     * ABRIR MODAL DE SELECCIÓN DE EMPLEADOS
     */
    openEmployeeSelection() {
        this.selectedEmployee = null;
        $('#selectedEmployeeInfo').hide();
        $('#proceedToAttendanceBtn').prop('disabled', true);
        $('#employeeSelectionModal').modal('show');
    }
    
    /**
     * CARGAR DATOS DE EMPLEADOS
     */
    async loadEmployeeData() {
        try {
            console.log('📋 Cargando datos de empleados...');
            
            // Cargar establecimientos para filtro
            await this.loadEstablishments();
            
            // Cargar lista inicial de empleados
            await this.searchEmployees();
            
        } catch (error) {
            console.error('❌ Error cargando datos de empleados:', error);
            this.showError('Error cargando datos de empleados');
        }
    }
    
    /**
     * CARGAR ESTABLECIMIENTOS
     */
    async loadEstablishments() {
        try {
            const response = await fetch('api/get-establecimientos.php');
            const data = await response.json();
            
            if (data.success) {
                const select = $('#filterEstablishment');
                select.empty().append('<option value="">Todos los establecimientos</option>');
                
                data.establecimientos.forEach(est => {
                    select.append(`<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`);
                });
            }
            
        } catch (error) {
            console.error('Error cargando establecimientos:', error);
        }
    }
    
    /**
     * BUSCAR EMPLEADOS
     */
    async searchEmployees() {
        try {
            const filters = {
                search: $('#searchEmployee').val(),
                establishment: $('#filterEstablishment').val(),
                status: $('#filterStatus').val(),
                biometric: $('#filterBiometric').val()
            };
            
            const response = await fetch('api/employee/list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ filters })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayEmployees(data.employees);
                $('#employeeCount').text(data.employees.length);
            } else {
                throw new Error(data.message || 'Error buscando empleados');
            }
            
        } catch (error) {
            console.error('Error buscando empleados:', error);
            this.showError('Error buscando empleados');
        }
    }
    
    /**
     * MOSTRAR EMPLEADOS EN TABLA
     */
    displayEmployees(employees) {
        const tbody = $('#employeesTableBody');
        tbody.empty();
        
        if (employees.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        <i class="fas fa-info-circle"></i> No se encontraron empleados
                    </td>
                </tr>
            `);
            return;
        }
        
        employees.forEach((employee, index) => {
            const biometricStatus = this.getBiometricStatus(employee);
            
            tbody.append(`
                <tr data-employee-id="${employee.ID_EMPLEADO}">
                    <td>${index + 1}</td>
                    <td>
                        <strong>${employee.NOMBRE} ${employee.APELLIDO}</strong>
                        <br><small class="text-muted">${employee.CORREO || 'Sin email'}</small>
                    </td>
                    <td>${employee.DNI}</td>
                    <td>${employee.ESTABLECIMIENTO || 'N/A'}</td>
                    <td>
                        <span class="badge badge-${employee.ESTADO === 'A' ? 'success' : 'secondary'}">
                            ${employee.ESTADO === 'A' ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>${biometricStatus}</td>
                    <td>
                        <button type="button" 
                                class="btn btn-sm btn-primary" 
                                onclick="attendanceSystem.selectEmployeeById(${employee.ID_EMPLEADO})">
                            <i class="fas fa-check"></i> Seleccionar
                        </button>
                    </td>
                </tr>
            `);
        });
    }
    
    /**
     * OBTENER ESTADO BIOMÉTRICO
     */
    getBiometricStatus(employee) {
        if (employee.BIOMETRIC_ENROLLED) {
            return '<span class="badge badge-success">Registrado</span>';
        } else {
            return '<span class="badge badge-warning">Pendiente</span>';
        }
    }
    
    /**
     * SELECCIONAR EMPLEADO
     */
    selectEmployee(row) {
        // Limpiar selección anterior
        $('#employeesTable tbody tr').removeClass('selected');
        
        // Marcar como seleccionado
        row.addClass('selected');
        
        // Obtener ID del empleado
        const employeeId = row.data('employee-id');
        this.selectEmployeeById(employeeId);
    }
    
    /**
     * SELECCIONAR EMPLEADO POR ID
     */
    async selectEmployeeById(employeeId) {
        try {
            const response = await fetch(`api/employee/get.php?id=${employeeId}`);
            const data = await response.json();
            
            if (data.success) {
                this.selectedEmployee = data.employee;
                this.displaySelectedEmployee();
                $('#proceedToAttendanceBtn').prop('disabled', false);
            } else {
                throw new Error('Error obteniendo datos del empleado');
            }
            
        } catch (error) {
            console.error('Error seleccionando empleado:', error);
            this.showError('Error seleccionando empleado');
        }
    }
    
    /**
     * MOSTRAR EMPLEADO SELECCIONADO
     */
    displaySelectedEmployee() {
        if (!this.selectedEmployee) return;
        
        const employee = this.selectedEmployee;
        const details = `
            <strong>${employee.NOMBRE} ${employee.APELLIDO}</strong> 
            (DNI: ${employee.DNI}) - 
            ${employee.ESTABLECIMIENTO || 'Sin establecimiento'}
        `;
        
        $('#selectedEmployeeDetails').html(details);
        $('#selectedEmployeeInfo').show();
    }
    
    /**
     * PROCEDER AL REGISTRO DE ASISTENCIA
     */
    proceedToAttendanceRegistration() {
        if (!this.selectedEmployee) {
            this.showError('Debe seleccionar un empleado primero');
            return;
        }
        
        // Cerrar modal de selección
        $('#employeeSelectionModal').modal('hide');
        
        // Abrir modal de registro biométrico
        $('#biometricAttendanceModal').modal('show');
    }
    
    /**
     * INICIALIZAR MODAL DE ASISTENCIA
     */
    initializeAttendanceModal() {
        if (!this.selectedEmployee) return;
        
        // Mostrar información del empleado
        this.displayEmployeeInfoInAttendance();
        
        // Verificar disponibilidad de métodos biométricos
        this.checkBiometricAvailability();
        
        // Resetear estado
        this.currentVerificationMethod = null;
        this.isVerifying = false;
        $('#verificationArea').hide();
        $('#verificationResult').hide();
        $('#completeAttendanceBtn').prop('disabled', true);
    }
    
    /**
     * MOSTRAR INFO DEL EMPLEADO EN MODAL DE ASISTENCIA
     */
    displayEmployeeInfoInAttendance() {
        const employee = this.selectedEmployee;
        const info = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Nombre:</strong> ${employee.NOMBRE} ${employee.APELLIDO}<br>
                    <strong>DNI:</strong> ${employee.DNI}<br>
                    <strong>Email:</strong> ${employee.CORREO || 'No disponible'}
                </div>
                <div class="col-md-6">
                    <strong>Establecimiento:</strong> ${employee.ESTABLECIMIENTO || 'No asignado'}<br>
                    <strong>Estado:</strong> <span class="badge badge-${employee.ESTADO === 'A' ? 'success' : 'secondary'}">${employee.ESTADO === 'A' ? 'Activo' : 'Inactivo'}</span><br>
                    <strong>Registro biométrico:</strong> ${employee.BIOMETRIC_ENROLLED ? 'Sí' : 'No'}
                </div>
            </div>
        `;
        
        $('#attendanceEmployeeInfo').html(info);
    }
    
    /**
     * VERIFICAR DISPONIBILIDAD DE MÉTODOS BIOMÉTRICOS
     */
    async checkBiometricAvailability() {
        // Verificar reconocimiento facial
        if (this.tensorflowSystem) {
            const facialAvailable = await this.checkFacialRecognitionAvailability();
            $('#facialStatus').html(
                facialAvailable ? 
                '<span class="badge badge-success">Disponible</span>' : 
                '<span class="badge badge-warning">No disponible</span>'
            );
        } else {
            $('#facialStatus').html('<span class="badge badge-danger">No soportado</span>');
        }
    }
    
    /**
     * VERIFICAR DISPONIBILIDAD DE RECONOCIMIENTO FACIAL
     */
    async checkFacialRecognitionAvailability() {
        try {
            if (!this.selectedEmployee.BIOMETRIC_ENROLLED) {
                return false;
            }
            
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(track => track.stop());
            return true;
            
        } catch (error) {
            console.error('Error verificando cámara:', error);
            return false;
        }
    }
    
    /**
     * SELECCIONAR MÉTODO DE VERIFICACIÓN
     */
    selectVerificationMethod(element) {
        // Limpiar selección anterior
        $('.verification-option').removeClass('selected');
        
        // Marcar como seleccionado
        element.addClass('selected');
        
        // Obtener método seleccionado
        const method = element.data('method');
        this.currentVerificationMethod = method;
        
        // Mostrar área de verificación
        this.showVerificationArea(method);
    }
    
    /**
     * MOSTRAR ÁREA DE VERIFICACIÓN
     */
    async showVerificationArea(method) {
        $('#verificationArea').show();
        
        // Ocultar todas las áreas
        $('#cameraArea, #photoArea').hide();
        
        switch (method) {
            case 'facial':
                await this.setupFacialRecognition();
                break;
            case 'photo':
                await this.setupPhotoCapture();
                break;
        }
    }
    
    /**
     * CONFIGURAR RECONOCIMIENTO FACIAL
     */
    async setupFacialRecognition() {
        try {
            $('#verificationTitle').text('Reconocimiento Facial en Progreso');
            $('#cameraArea').show();
            
            // Obtener stream de video
            this.videoStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: 640, 
                    height: 480,
                    facingMode: 'user'
                } 
            });
            
            const video = document.getElementById('attendanceVideo');
            video.srcObject = this.videoStream;
            
            // Esperar a que el video esté listo
            await new Promise(resolve => {
                video.onloadedmetadata = resolve;
            });
            
            // Iniciar verificación facial
            this.startFacialVerification();
            
        } catch (error) {
            console.error('Error configurando reconocimiento facial:', error);
            this.showError('Error accediendo a la cámara');
        }
    }
    
    /**
     * INICIAR VERIFICACIÓN FACIAL
     */
    async startFacialVerification() {
        if (this.isVerifying) return;
        
        this.isVerifying = true;
        const video = document.getElementById('attendanceVideo');
        let attempts = 0;
        const maxAttempts = 10;
        
        $('#verificationStatusText').html('<span class="badge badge-info">Verificando...</span>');
        
        const verifyLoop = async () => {
            if (!this.isVerifying || attempts >= maxAttempts) return;
            
            try {
                attempts++;
                const progress = (attempts / maxAttempts) * 100;
                $('#verificationProgress').css('width', `${progress}%`).text(`${Math.round(progress)}%`);
                
                // Verificar con TensorFlow
                const result = await this.tensorflowSystem.verifyIdentity(video, this.selectedEmployee.ID_EMPLEADO);
                
                $('#verificationResults').html(`
                    <small>
                        Intento ${attempts}/${maxAttempts}<br>
                        Confianza: ${result.confidence ? (result.confidence * 100).toFixed(1) : 0}%<br>
                        ${result.message || 'Verificando...'}
                    </small>
                `);
                
                if (result.success && result.similarity >= 0.7) {
                    this.completeVerification(result);
                    return;
                }
                
                setTimeout(verifyLoop, 1000);
                
            } catch (error) {
                console.error('Error en verificación facial:', error);
                this.showVerificationError('Error en verificación facial');
            }
        };
        
        verifyLoop();
    }
    
    /**
     * CONFIGURAR CAPTURA DE FOTO
     */
    async setupPhotoCapture() {
        try {
            $('#verificationTitle').text('Captura de Foto Tradicional');
            $('#photoArea').show();
            
            // Obtener stream de video
            this.videoStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: 640, 
                    height: 480,
                    facingMode: 'user'
                } 
            });
            
            const video = document.getElementById('photoVideo');
            video.srcObject = this.videoStream;
            
        } catch (error) {
            console.error('Error configurando captura de foto:', error);
            this.showError('Error accediendo a la cámara');
        }
    }
    
    /**
     * CAPTURAR FOTO
     */
    capturePhoto() {
        try {
            const video = document.getElementById('photoVideo');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            ctx.drawImage(video, 0, 0);
            
            const photoData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Mostrar preview
            $('#previewImage').attr('src', photoData);
            $('#capturedPhotoPreview').show();
            
            // Completar verificación con foto
            this.completeVerification({
                success: true,
                method: 'photo',
                photoData: photoData,
                confidence: 1.0,
                message: 'Foto capturada correctamente'
            });
            
        } catch (error) {
            console.error('Error capturando foto:', error);
            this.showError('Error capturando foto');
        }
    }
    
    /**
     * COMPLETAR VERIFICACIÓN
     */
    completeVerification(result) {
        this.isVerifying = false;
        
        this.verificationResult = result;
        
        $('#verificationResult').show();
        
        if (result.success) {
            $('#verificationAlert').removeClass('alert-danger').addClass('alert-success').html(`
                <h6><i class="fas fa-check-circle"></i> Verificación Exitosa</h6>
                <p class="mb-0">
                    ${result.message}<br>
                    <small>Confianza: ${(result.confidence * 100).toFixed(1)}% | Método: ${result.method || this.currentVerificationMethod}</small>
                </p>
            `);
            
            $('#completeAttendanceBtn').prop('disabled', false);
        } else {
            $('#verificationAlert').removeClass('alert-success').addClass('alert-danger').html(`
                <h6><i class="fas fa-exclamation-triangle"></i> Verificación Fallida</h6>
                <p class="mb-0">${result.message || 'No se pudo verificar la identidad'}</p>
            `);
        }
    }
    
    /**
     * COMPLETAR REGISTRO DE ASISTENCIA
     */
    async completeAttendanceRegistration() {
        if (!this.verificationResult || !this.verificationResult.success) {
            this.showError('Debe completar la verificación primero');
            return;
        }
        
        try {
            const attendanceData = {
                employee_id: this.selectedEmployee.ID_EMPLEADO,
                verification_method: this.currentVerificationMethod,
                verification_result: this.verificationResult,
                photo_data: this.verificationResult.photoData,
                timestamp: new Date().toISOString()
            };
            
            const response = await fetch('api/attendance/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(attendanceData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Asistencia registrada correctamente');
                $('#biometricAttendanceModal').modal('hide');
                
                // Recargar datos si estamos en la página de asistencias
                if (typeof loadAttendanceList === 'function') {
                    loadAttendanceList();
                }
            } else {
                throw new Error(result.message || 'Error registrando asistencia');
            }
            
        } catch (error) {
            console.error('Error registrando asistencia:', error);
            this.showError('Error registrando asistencia');
        }
    }
    
    /**
     * LIMPIAR MODAL DE ASISTENCIA
     */
    cleanupAttendanceModal() {
        this.isVerifying = false;
        
        // Detener stream de video
        if (this.videoStream) {
            this.videoStream.getTracks().forEach(track => track.stop());
            this.videoStream = null;
        }
        
        // Limpiar elementos de video
        const videos = ['attendanceVideo', 'photoVideo'];
        videos.forEach(id => {
            const video = document.getElementById(id);
            if (video) video.srcObject = null;
        });
        
        // Resetear interfaz
        $('.verification-option').removeClass('selected');
        $('#verificationArea').hide();
        $('#verificationResult').hide();
        $('#capturedPhotoPreview').hide();
    }
    
    /**
     * LIMPIAR FILTROS DE EMPLEADOS
     */
    clearEmployeeFilters() {
        $('#searchEmployee').val('');
        $('#filterEstablishment').val('');
        $('#filterStatus').val('A');
        $('#filterBiometric').val('');
        this.searchEmployees();
    }
    
    /**
     * MOSTRAR ERROR
     */
    showError(message) {
        console.error('❌', message);
        // Aquí puedes integrar con tu sistema de notificaciones
        alert(message);
    }
    
    /**
     * MOSTRAR ÉXITO
     */
    showSuccess(message) {
        console.log('✅', message);
        // Aquí puedes integrar con tu sistema de notificaciones
        alert(message);
    }
}

// Funciones globales para compatibilidad
function searchEmployees() {
    if (window.attendanceSystem) {
        window.attendanceSystem.searchEmployees();
    }
}

function clearEmployeeFilters() {
    if (window.attendanceSystem) {
        window.attendanceSystem.clearEmployeeFilters();
    }
}

function proceedToAttendanceRegistration() {
    if (window.attendanceSystem) {
        window.attendanceSystem.proceedToAttendanceRegistration();
    }
}

function capturePhoto() {
    if (window.attendanceSystem) {
        window.attendanceSystem.capturePhoto();
    }
}

function completeAttendanceRegistration() {
    if (window.attendanceSystem) {
        window.attendanceSystem.completeAttendanceRegistration();
    }
}

// Inicializar sistema cuando el DOM esté listo
$(document).ready(function() {
    window.attendanceSystem = new AttendanceBiometricSystem();
    
    console.log('🚀 Sistema de Asistencia Biométrica listo para usar');
});
