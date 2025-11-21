// Script para corregir la inicialización del modal biométrico
// Este script se debe ejecutar después de que el DOM esté listo

console.log('🔧 Executing modal initialization fix...');

// Función para limpiar e inicializar correctamente el modal
function fixBiometricModalInitialization() {
    console.log('🔄 Fixing biometric modal initialization...');
    
    // Limpiar cualquier referencia previa
    if (window.biometricVerificationModal) {
        console.log('🧹 Cleaning previous modal reference...');
        delete window.biometricVerificationModal;
    }
    
    if (window.biometricModalInstance) {
        console.log('🧹 Cleaning previous modal instance...');
        delete window.biometricModalInstance;
    }
    
    try {
        // Verificar que la clase esté disponible
        if (typeof BiometricVerificationModal === 'undefined') {
            console.error('❌ BiometricVerificationModal class not available');
            return false;
        }
        
        // Crear nueva instancia limpia con manejo de errores
        console.log('🆕 Creating new BiometricVerificationModal instance...');
        
        // Crear instancia mínima solo con los métodos esenciales
        const modalInstance = Object.create(BiometricVerificationModal.prototype);
        
        // Inicializar propiedades básicas sin llamar al constructor completo
        modalInstance.selectedEmployee = null;
        modalInstance.employeeData = null;
        modalInstance.currentTab = 'face';
        modalInstance.verificationResults = {
            face: null,
            fingerprint: null,
            rfid: null
        };
        modalInstance.isVerifying = {
            face: false,
            fingerprint: false,
            rfid: false
        };
        modalInstance.videoStream = null;
        modalInstance.fingerprintStream = null;
        modalInstance.rfidStream = null;
        modalInstance.config = {
            facial: { confidenceThreshold: 0.88, qualityThreshold: 0.80, maxAttempts: 3 },
            face: { confidenceThreshold: 0.88, qualityThreshold: 0.80, maxAttempts: 3 },
            fingerprint: { confidenceThreshold: 0.95, maxAttempts: 3 },
            rfid: { confidenceThreshold: 0.95, readTimeout: 5000 }
        };
        modalInstance.attempts = { face: 0, fingerprint: 0, rfid: 0 };
        modalInstance.employeeBiometrics = {
            face: false,
            fingerprint: false,
            rfid: false
        };
        modalInstance.deviceStatus = {
            face: { connected: false, available: false, lastCheck: null },
            fingerprint: { connected: false, available: false, lastCheck: null },
            rfid: { connected: false, available: false, lastCheck: null }
        };
        
        // Asignar métodos críticos si no están disponibles
        if (typeof modalInstance.selectCandidate !== 'function') {
            modalInstance.selectCandidate = async function(employeeId, employeeName) {
                console.log('🎯 selectCandidate called (fallback) with:', employeeId, employeeName);
                
                try {
                    // Mostrar confirmación
                    const confirmed = confirm(`¿Confirma que desea registrar asistencia para ${employeeName}?`);
                    if (!confirmed) {
                        console.log('👤 User cancelled employee selection');
                        return;
                    }
                    
                    // Buscar datos del empleado
                    const response = await fetch(`api/employee/get_details.php?id=${employeeId}`);
                    const result = await response.json();
                    
                    if (result.success && result.employee) {
                        this.selectedEmployee = employeeId;
                        this.employeeData = result.employee;
                        
                        // Llamar registro de asistencia
                        if (typeof this.registerAttendanceAfterIdentification === 'function') {
                            await this.registerAttendanceAfterIdentification(result.employee);
                        } else {
                            // Fallback directo al API
                            console.log('🔄 Using fallback attendance registration...');
                            alert('Empleado seleccionado: ' + employeeName + '. Proceda manualmente con el registro.');
                        }
                    } else {
                        alert('Error: No se pudieron obtener los datos del empleado');
                    }
                } catch (error) {
                    console.error('❌ Error in selectCandidate:', error);
                    alert('Error al seleccionar empleado: ' + error.message);
                }
            };
        }
        
        // Asegurar que los métodos de verificación estén disponibles con fallback
        if (typeof modalInstance.startAutoIdentification !== 'function') {
            modalInstance.startAutoIdentification = function() {
                alert('La función de identificación automática no está completamente cargada. Por favor, recargue la página.');
            };
        }
        
        if (typeof modalInstance.startFaceVerification !== 'function') {
            modalInstance.startFaceVerification = function() {
                alert('La función de verificación facial no está completamente cargada. Por favor, recargue la página.');
            };
        }
        
        // Vincular eventos a los botones si no están vinculados
        setTimeout(() => {
            bindButtonEvents(modalInstance);
        }, 100);
        
        // Asignar a ambas referencias globales
        window.biometricModalInstance = modalInstance;
        window.biometricVerificationModal = modalInstance;
        
        console.log('✅ Modal initialization fix successful');
        console.log('🔍 Instance type:', typeof window.biometricModalInstance);
        console.log('🔍 Constructor name:', window.biometricModalInstance.constructor.name);
        console.log('🔍 Has selectCandidate:', typeof window.biometricModalInstance.selectCandidate);
        
        return true;
    } catch (error) {
        console.error('❌ Error fixing modal initialization:', error);
        return false;
    }
}

// Función mejorada para seleccionar candidato
function selectEmployeeCandidateFixed(employeeId, employeeName) {
    console.log('🎯 Selecting employee candidate (fixed version):', employeeId, employeeName);
    
    // Obtener la instancia correcta
    let modalInstance = window.biometricModalInstance || window.biometricVerificationModal;
    
    // Verificar si la instancia es válida
    if (!modalInstance || modalInstance.constructor.name !== 'BiometricVerificationModal' || 
        typeof modalInstance.selectCandidate !== 'function') {
        console.log('🔧 Invalid modal instance detected, attempting fix...');
        if (fixBiometricModalInitialization()) {
            modalInstance = window.biometricModalInstance;
        } else {
            alert('Error: No se pudo inicializar el modal biométrico.');
            return;
        }
    }
    
    // Verificar que el método existe
    if (typeof modalInstance.selectCandidate === 'function') {
        console.log('✅ Calling selectCandidate method...');
        try {
            modalInstance.selectCandidate(employeeId, employeeName);
        } catch (error) {
            console.error('❌ Error calling selectCandidate:', error);
            alert('Error al seleccionar empleado: ' + error.message);
        }
    } else {
        console.error('❌ selectCandidate method not found');
        alert('Error: Método de selección no disponible.');
    }
}

// Función para vincular eventos a botones
function bindButtonEvents(modalInstance) {
    console.log('🔗 Binding button events to modal instance...');
    
    // Botones de verificación facial
    const startAutoBtn = document.getElementById('startAutoIdentification');
    const startFaceBtn = document.getElementById('startFaceVerification');
    const stopFaceBtn = document.getElementById('stopFaceVerification');
    
    if (startAutoBtn && !startAutoBtn._eventsBound) {
        startAutoBtn.addEventListener('click', () => {
            console.log('🎯 startAutoIdentification button clicked');
            if (modalInstance && typeof modalInstance.startAutoIdentification === 'function') {
                modalInstance.startAutoIdentification();
            } else {
                alert('La función de identificación automática no está disponible.');
            }
        });
        startAutoBtn._eventsBound = true;
        console.log('✅ startAutoIdentification button bound');
    }
    
    if (startFaceBtn && !startFaceBtn._eventsBound) {
        startFaceBtn.addEventListener('click', () => {
            console.log('👤 startFaceVerification button clicked');
            if (modalInstance && typeof modalInstance.startFaceVerification === 'function') {
                modalInstance.startFaceVerification();
            } else {
                alert('La función de verificación facial no está disponible.');
            }
        });
        startFaceBtn._eventsBound = true;
        console.log('✅ startFaceVerification button bound');
    }
    
    if (stopFaceBtn && !stopFaceBtn._eventsBound) {
        stopFaceBtn.addEventListener('click', () => {
            console.log('🛑 stopFaceVerification button clicked');
            if (modalInstance && typeof modalInstance.stopFaceVerification === 'function') {
                modalInstance.stopFaceVerification();
            }
        });
        stopFaceBtn._eventsBound = true;
        console.log('✅ stopFaceVerification button bound');
    }
    
    // Botones de verificación de huella
    const startFingerprintBtn = document.getElementById('startFingerprintVerification');
    const verifyFingerprintBtn = document.getElementById('verifyFingerprintNow');
    const stopFingerprintBtn = document.getElementById('stopFingerprintVerification');
    
    if (startFingerprintBtn && !startFingerprintBtn._eventsBound) {
        startFingerprintBtn.addEventListener('click', () => {
            console.log('👆 startFingerprintVerification button clicked');
            if (modalInstance && typeof modalInstance.startFingerprintVerification === 'function') {
                modalInstance.startFingerprintVerification();
            } else {
                alert('La función de verificación de huella no está disponible.');
            }
        });
        startFingerprintBtn._eventsBound = true;
        console.log('✅ startFingerprintVerification button bound');
    }
    
    if (verifyFingerprintBtn && !verifyFingerprintBtn._eventsBound) {
        verifyFingerprintBtn.addEventListener('click', () => {
            console.log('🔍 verifyFingerprintNow button clicked');
            if (modalInstance && typeof modalInstance.verifyFingerprintNow === 'function') {
                modalInstance.verifyFingerprintNow();
            }
        });
        verifyFingerprintBtn._eventsBound = true;
        console.log('✅ verifyFingerprintNow button bound');
    }
    
    if (stopFingerprintBtn && !stopFingerprintBtn._eventsBound) {
        stopFingerprintBtn.addEventListener('click', () => {
            console.log('🛑 stopFingerprintVerification button clicked');
            if (modalInstance && typeof modalInstance.stopFingerprintVerification === 'function') {
                modalInstance.stopFingerprintVerification();
            }
        });
        stopFingerprintBtn._eventsBound = true;
        console.log('✅ stopFingerprintVerification button bound');
    }
    
    // Botones RFID
    const startRfidBtn = document.getElementById('startRfidVerification');
    const verifyRfidBtn = document.getElementById('verifyRfidNow');
    const stopRfidBtn = document.getElementById('stopRfidVerification');
    
    if (startRfidBtn && !startRfidBtn._eventsBound) {
        startRfidBtn.addEventListener('click', () => {
            console.log('📡 startRfidVerification button clicked');
            if (modalInstance && typeof modalInstance.startRfidVerification === 'function') {
                modalInstance.startRfidVerification();
            } else {
                alert('La función de verificación RFID no está disponible.');
            }
        });
        startRfidBtn._eventsBound = true;
        console.log('✅ startRfidVerification button bound');
    }
    
    if (verifyRfidBtn && !verifyRfidBtn._eventsBound) {
        verifyRfidBtn.addEventListener('click', () => {
            console.log('🔍 verifyRfidNow button clicked');
            if (modalInstance && typeof modalInstance.verifyRfidNow === 'function') {
                modalInstance.verifyRfidNow();
            }
        });
        verifyRfidBtn._eventsBound = true;
        console.log('✅ verifyRfidNow button bound');
    }
    
    if (stopRfidBtn && !stopRfidBtn._eventsBound) {
        stopRfidBtn.addEventListener('click', () => {
            console.log('🛑 stopRfidVerification button clicked');
            if (modalInstance && typeof modalInstance.stopRfidVerification === 'function') {
                modalInstance.stopRfidVerification();
            }
        });
        stopRfidBtn._eventsBound = true;
        console.log('✅ stopRfidVerification button bound');
    }
    
    // Botón de completar verificación
    const completeBtn = document.getElementById('completeVerification');
    if (completeBtn && !completeBtn._eventsBound) {
        completeBtn.addEventListener('click', () => {
            console.log('✅ completeVerification button clicked');
            if (modalInstance && typeof modalInstance.completeVerification === 'function') {
                modalInstance.completeVerification();
            }
        });
        completeBtn._eventsBound = true;
        console.log('✅ completeVerification button bound');
    }
    
    console.log('🔗 Button events binding completed');
}

// Reemplazar la función global existente
window.selectEmployeeCandidate = selectEmployeeCandidateFixed;

// Función global para forzar la corrección de botones
window.fixButtonEvents = function() {
    console.log('🔧 Manual button fix requested...');
    
    let modalInstance = window.biometricModalInstance || window.biometricVerificationModal;
    
    if (!modalInstance) {
        console.log('🔄 No modal instance found, creating...');
        if (fixBiometricModalInitialization()) {
            modalInstance = window.biometricModalInstance;
        }
    }
    
    if (modalInstance) {
        bindButtonEvents(modalInstance);
        alert('✅ Eventos de botones corregidos. Intente usar los botones nuevamente.');
    } else {
        alert('❌ No se pudo corregir los eventos. Recargue la página.');
    }
};

// Función global para diagnóstico
window.diagnosticModalButtons = function() {
    console.log('🔍 Diagnostic: Checking modal button status...');
    
    const buttons = [
        'startAutoIdentification',
        'startFaceVerification',
        'stopFaceVerification',
        'startFingerprintVerification',
        'verifyFingerprintNow',
        'stopFingerprintVerification',
        'startRfidVerification',
        'verifyRfidNow',
        'stopRfidVerification',
        'completeVerification'
    ];
    
    let report = 'Button Status Report:\n';
    
    buttons.forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            report += `✅ ${buttonId}: Found, Events bound: ${button._eventsBound ? 'Yes' : 'No'}\n`;
        } else {
            report += `❌ ${buttonId}: Not found\n`;
        }
    });
    
    const modalInstance = window.biometricModalInstance || window.biometricVerificationModal;
    report += `\nModal Instance: ${modalInstance ? 'Available' : 'Not found'}`;
    if (modalInstance) {
        report += `\nInstance Type: ${modalInstance.constructor?.name || 'Unknown'}`;
    }
    
    console.log(report);
    alert(report);
};

// Ejecutar la corrección si el DOM está listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixBiometricModalInitialization);
} else {
    // Solo intentar la corrección si hay elementos DOM disponibles
    if (document.getElementById('biometricVerificationModal')) {
        fixBiometricModalInitialization();
    } else {
        console.log('⏳ DOM elements not ready, will fix on first selectEmployeeCandidate call');
    }
}

// Asegurar que los eventos se vinculen cuando el modal se abra
document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('biometricVerificationModal');
    if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', () => {
            console.log('📱 Modal shown, ensuring button events are bound...');
            
            // Asegurar que la instancia esté disponible
            let modalInstance = window.biometricModalInstance || window.biometricVerificationModal;
            
            if (!modalInstance || modalInstance.constructor.name !== 'BiometricVerificationModal') {
                console.log('🔧 Modal instance not valid, fixing...');
                if (fixBiometricModalInitialization()) {
                    modalInstance = window.biometricModalInstance;
                }
            }
            
            if (modalInstance) {
                bindButtonEvents(modalInstance);
            } else {
                console.error('❌ Could not bind button events: modal instance not available');
            }
        });
    }
});

console.log('✅ Modal initialization fix script loaded');