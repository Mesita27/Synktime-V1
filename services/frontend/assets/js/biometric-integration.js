/**
 * Integración del sistema BlazeFace con el módulo de enrolamiento biométrico
 */

// Variables globales
let biometricModalInitialized = false;

// Función para abrir el modal de enrolamiento biométrico
function openEnrollmentModal(employeeId, employeeName = '', establishmentName = '') {
    console.log('Abriendo modal para empleado:', employeeId);
    
    // Verificar si el modal existe
    const modal = document.getElementById('biometricEnrollmentModal');
    if (!modal) {
        console.error('Error: Modal de enrolamiento no encontrado');
        alert('Error: El modal de enrolamiento no está disponible');
        return;
    }
    
    try {
        // Detener cualquier cámara activa antes de abrir el modal
        if (typeof stopFaceCamera === 'function') {
            try {
                stopFaceCamera();
            } catch (e) {
                console.warn('Error al detener cámara:', e);
            }
        }
        
        // Primero actualizar con los datos disponibles
        const codeElement = document.getElementById('modal-employee-code');
        const nameElement = document.getElementById('modal-employee-name');
        const establishmentElement = document.getElementById('modal-employee-establishment');
        
        if (codeElement) codeElement.textContent = employeeId || '-';
        if (nameElement) nameElement.textContent = employeeName || '-';
        if (establishmentElement) establishmentElement.textContent = establishmentName || '-';
        
        // Asegurar que los campos ocultos tengan el ID del empleado
        const currentIdField = document.getElementById('current-employee-id');
        const employeeIdField = document.getElementById('employee_id');
        const hiddenIdField = document.getElementById('hidden_employee_id');
        
        if (currentIdField) currentIdField.value = employeeId;
        if (employeeIdField) employeeIdField.value = employeeId;
        if (hiddenIdField) hiddenIdField.value = employeeId;
        
        // Mostrar ID en el pie del modal
        const displayId = document.getElementById('display-employee-id');
        if (displayId) displayId.textContent = employeeId;
        
        // Abrir el modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        console.log('Modal mostrado correctamente');
        
        // Cargar datos completos del empleado desde la API
        fetch(`api/employee/get_details.php?id=${employeeId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Datos del empleado cargados:', data);
                
                if (data.success && data.employee) {
                    // Actualizar datos del empleado
                    if (codeElement) codeElement.textContent = data.employee.codigo || employeeId;
                    if (nameElement) nameElement.textContent = data.employee.nombre_completo || employeeName;
                    if (establishmentElement) {
                        const establecimiento = data.employee.establecimiento?.nombre || '-';
                        const sede = data.employee.sede?.nombre || '';
                        establishmentElement.textContent = establecimiento + (sede ? ` (${sede})` : '');
                    }
                    
                    // Actualizar estado biométrico
                    if (data.biometric_status) {
                        const facialStatus = document.getElementById('facial-status');
                        const fingerprintStatus = document.getElementById('fingerprint-status');
                        
                        if (facialStatus) {
                            facialStatus.className = data.biometric_status.facial ? 'badge bg-success' : 'badge bg-secondary';
                            facialStatus.textContent = data.biometric_status.facial ? 'Inscrito' : 'Pendiente';
                        }
                        
                        if (fingerprintStatus) {
                            fingerprintStatus.className = data.biometric_status.fingerprint ? 'badge bg-success' : 'badge bg-secondary';
                            fingerprintStatus.textContent = data.biometric_status.fingerprint ? 'Inscrito' : 'Pendiente';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar datos del empleado:', error);
            });
        
        // Asegurarse de que el modal esté completamente cargado antes de inicializarlo
        setTimeout(() => {
            // Inicializar el modal si no se ha hecho
            if (!biometricModalInitialized) {
                initBiometricModal();
            }
            
            // Inicializar diagnóstico
            if (typeof window.diagnosticoBiometrico === 'function') {
                window.diagnosticoBiometrico();
            }
        }, 300);
        
    } catch (error) {
        console.error('Error al mostrar modal:', error);
        alert('Error al abrir el modal de enrolamiento: ' + error.message);
    }
}

// Inicializar el modal cuando se abre
function initBiometricModal() {
    console.log('Inicializando modal de enrolamiento biométrico...');
    
    try {
        // Asignar evento al botón de inicio de cámara
        const startButton = document.getElementById('startFaceCamera');
        if (startButton) {
            startButton.addEventListener('click', function() {
                console.log('Botón inicio cámara pulsado');
                if (typeof startFaceCamera === 'function') {
                    startFaceCamera();
                } else {
                    console.error('La función startFaceCamera no está definida');
                }
            });
        }
        
        // Asignar evento al botón de detener cámara
        const stopButton = document.getElementById('stopFaceCamera');
        if (stopButton) {
            stopButton.addEventListener('click', function() {
                console.log('Botón detener cámara pulsado');
                if (typeof stopFaceCamera === 'function') {
                    stopFaceCamera();
                } else {
                    console.error('La función stopFaceCamera no está definida');
                }
            });
        }
        
        // Asignar evento al botón de guardar
        const saveButton = document.getElementById('saveEnrollment');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                console.log('Botón guardar pulsado');
                if (typeof saveEnrollment === 'function') {
                    saveEnrollment();
                } else {
                    console.error('La función saveEnrollment no está definida');
                }
            });
        }
        
        // Verificar carga de BlazeFace
        if (typeof blazeface !== 'undefined') {
            console.log('BlazeFace está disponible');
            // Precargar el modelo
            if (typeof initBlazeFace === 'function') {
                initBlazeFace().then(() => {
                    console.log('Modelo BlazeFace precargado');
                });
            }
        } else {
            console.warn('BlazeFace no está disponible, intentando cargar...');
            // Intentar cargar BlazeFace si no está disponible
            loadExternalScript('https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js')
                .then(() => {
                    console.log('BlazeFace cargado dinámicamente');
                })
                .catch(err => {
                    console.error('Error al cargar BlazeFace:', err);
                });
        }
        
        biometricModalInitialized = true;
        console.log('Modal inicializado correctamente');
        
    } catch (error) {
        console.error('Error al inicializar el modal:', error);
    }
}

// Función auxiliar para cargar scripts externos
function loadExternalScript(url) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = url;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

// Manejar evento cuando se muestra el modal
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando eventos para modal de enrolamiento');
    
    // Verificar jQuery
    if (typeof $ === 'undefined') {
        console.warn('jQuery no está disponible, usando addEventListener nativo');
        
        // Alternativa nativa para jQuery
        document.body.addEventListener('shown.bs.modal', function(event) {
            if (event.target.id === 'biometricEnrollmentModal') {
                handleModalShown();
            }
        });
    } else {
        // Usar delegación de eventos para manejar el modal (compatible con carga dinámica)
        $(document).on('shown.bs.modal', '#biometricEnrollmentModal', function() {
            handleModalShown();
        });
    }
    
    function handleModalShown() {
        console.log('Modal mostrado - Preparando sistema biométrico');
        
        // Re-inicializar el modal cuando se muestre
        if (!biometricModalInitialized) {
            initBiometricModal();
        }
        
        // Verificar que los elementos DOM están accesibles
        setTimeout(() => {
            if (typeof getDOMElements === 'function') {
                getDOMElements();
            }
            
            // Inicializar BlazeFace si está disponible
            if (typeof initBlazeFace === 'function') {
                initBlazeFace();
            }
        }, 300);
    }
    
    // Limpiar recursos cuando se cierre el modal
    $(document).on('hidden.bs.modal', '#biometricEnrollmentModal', function() {
        console.log('Modal cerrado - Limpiando recursos');
        if (typeof stopFaceCamera === 'function') {
            stopFaceCamera();
        }
    });
    
    // Detectar botones de enrolamiento en la tabla
    $(document).on('click', '.btn-enroll', function() {
        const employeeId = this.getAttribute('data-employee-id');
        const employeeName = this.getAttribute('data-employee-name');
        const establishmentName = this.getAttribute('data-establishment');
        
        console.log('Botón de enrolamiento clickeado para empleado:', employeeId);
        openEnrollmentModal(employeeId, employeeName, establishmentName);
    });
});
