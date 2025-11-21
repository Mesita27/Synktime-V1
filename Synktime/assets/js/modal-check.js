/**
 * Script de diagnóstico para modales y cámara
 * Este script ayuda a detectar y corregir problemas en la inicialización de modales y cámaras.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Verificador de modales y cámara: Inicializado');
    
    // Verificar Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap no está disponible. Asegúrate de que bootstrap.js esté cargado.');
    } else {
        console.log('✅ Bootstrap está disponible en versión:', bootstrap.Tooltip.VERSION || 'desconocida');
    }
    
    // Verificar jQuery si está siendo usado
    if (typeof $ === 'undefined') {
        console.warn('⚠️ jQuery no está disponible. Algunos componentes podrían requerirlo.');
    } else {
        console.log('✅ jQuery está disponible en versión:', $.fn.jquery);
    }
    
    // Verificar TensorFlow.js
    if (typeof tf === 'undefined') {
        console.error('❌ TensorFlow.js no está disponible. El reconocimiento facial no funcionará.');
    } else {
        console.log('✅ TensorFlow.js está disponible en versión:', tf.version.tfjs);
    }
    
    // Verificar BlazeFace
    if (typeof blazeface === 'undefined') {
        console.error('❌ BlazeFace no está disponible. El reconocimiento facial no funcionará.');
    } else {
        console.log('✅ BlazeFace está disponible');
    }
    
    // Verificar elementos críticos para el biométrico
    const modalElements = [
        'biometricEnrollmentModal',
        'faceVideo',
        'faceCanvas',
        'startFaceCamera',
        'stopFaceCamera',
        'face-detection-status'
    ];
    
    modalElements.forEach(id => {
        const element = document.getElementById(id);
        console.log(`Elemento ${id}: ${element ? '✅ Encontrado' : '❌ No encontrado'}`);
    });
    
    // Intentar corregir problemas comunes
    attemptAutoCorrectModalIssues();
});

/**
 * Intenta corregir automáticamente problemas comunes con modales y cámara
 */
function attemptAutoCorrectModalIssues() {
    // Añadir un retardo para asegurar que el DOM está completamente cargado
    setTimeout(() => {
        // 1. Verificar si los botones de cámara no tienen eventos asignados
        const startButton = document.getElementById('startFaceCamera');
        const stopButton = document.getElementById('stopFaceCamera');
        
        if (startButton && !startButton._hasEventListener) {
            console.log('⚠️ Añadiendo event listener al botón de inicio de cámara');
            startButton._hasEventListener = true;
            startButton.addEventListener('click', function() {
                if (typeof startFaceCamera === 'function') {
                    startFaceCamera();
                } else if (window.parent && typeof window.parent.startFaceCamera === 'function') {
                    window.parent.startFaceCamera();
                } else {
                    console.error('❌ No se encontró la función startFaceCamera');
                }
            });
        }
        
        // 2. Añadir clase para forzar el zIndex del modal
        const modal = document.getElementById('biometricEnrollmentModal');
        if (modal && !modal.classList.contains('z-index-fix')) {
            modal.classList.add('z-index-fix');
            console.log('✅ Se agregó z-index-fix al modal para corregir problemas de superposición');
            
            // Añadir estilo en línea si no existe la clase
            if (!document.querySelector('style#modal-fixes')) {
                const style = document.createElement('style');
                style.id = 'modal-fixes';
                style.innerHTML = `.z-index-fix { z-index: 1060 !important; } 
                                 .modal-backdrop { z-index: 1050 !important; }`;
                document.head.appendChild(style);
            }
        }
        
        console.log('✅ Diagnóstico y corrección automática de modales completados');
    }, 500);
}

/**
 * Verifica la disponibilidad de la cámara
 */
function checkCameraAvailability() {
    return new Promise((resolve, reject) => {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                // Liberar la cámara inmediatamente después de la prueba
                stream.getTracks().forEach(track => track.stop());
                resolve({
                    available: true,
                    message: 'Cámara disponible y funcional'
                });
            })
            .catch(error => {
                resolve({
                    available: false,
                    message: 'Error al acceder a la cámara: ' + error.message,
                    error: error
                });
            });
    });
}

/**
 * Función de diagnóstico que se puede llamar manualmente
 */
function diagnoseModalAndCamera() {
    console.group('Diagnóstico de Modal y Cámara');
    
    // Verificar modal
    const modal = document.getElementById('biometricEnrollmentModal');
    if (modal) {
        console.log('✅ Modal encontrado en el DOM');
        
        const modalInstance = bootstrap.Modal.getInstance(modal);
        console.log('Modal Bootstrap instancia:', modalInstance ? '✅ Activa' : '⚠️ No inicializada');
        
        // Verificar si el modal está visible
        if (modal.classList.contains('show')) {
            console.log('✅ Modal actualmente visible');
        } else {
            console.log('ℹ️ Modal actualmente oculto');
        }
    } else {
        console.error('❌ Modal no encontrado en el DOM');
    }
    
    // Verificar elementos de cámara
    const videoElement = document.getElementById('faceVideo');
    if (videoElement) {
        console.log('✅ Elemento de video encontrado');
        console.log('- readyState:', videoElement.readyState);
        console.log('- srcObject:', videoElement.srcObject ? '✅ Presente' : '❌ No presente');
    } else {
        console.error('❌ Elemento de video no encontrado');
    }
    
    // Verificar botones
    const startButton = document.getElementById('startFaceCamera');
    const stopButton = document.getElementById('stopFaceCamera');
    
    if (startButton) {
        console.log('✅ Botón de inicio encontrado');
        console.log('- disabled:', startButton.disabled);
    }
    
    if (stopButton) {
        console.log('✅ Botón de parada encontrado');
        console.log('- disabled:', stopButton.disabled);
    }
    
    // Verificar cámara
    checkCameraAvailability().then(result => {
        console.log(result.available ? '✅ Cámara disponible' : '❌ Cámara no disponible');
        console.log('- Mensaje:', result.message);
        console.groupEnd();
    });
}

// Exponer función para uso desde consola
window.diagnoseModalAndCamera = diagnoseModalAndCamera;
