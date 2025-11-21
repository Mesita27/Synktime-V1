/**
 * SISTEMA DE VERIFICACIÓN Y REPARACIÓN AUTOMÁTICA
 * Para el módulo de inscripción biométrica
 */

// Ejecutar verificaciones después de que todo se haya cargado
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(performSystemCheck, 1000);
});

/**
 * Realizar verificación completa del sistema
 */
function performSystemCheck() {
    console.log('🔧 Iniciando verificación automática del sistema biométrico...');
    
    // 1. Verificar Bootstrap
    if (typeof bootstrap === 'undefined') {
        console.warn('⚠️ Bootstrap no detectado, intentando cargar...');
        loadBootstrapFallback();
    }
    
    // 2. Verificar modal biométrico
    const modal = document.getElementById('biometricEnrollmentModal');
    if (!modal) {
        console.error('❌ Modal biométrico no encontrado');
        showSystemAlert('Modal biométrico no encontrado. Verifique que el archivo del modal esté incluido.');
    }
    
    // 3. Verificar botones en la tabla
    setTimeout(checkTableButtons, 500);
    
    // 4. Verificar funciones globales
    checkGlobalFunctions();
    
    // 5. Agregar eventos de respaldo
    addFallbackEventListeners();
}

/**
 * Verificar que los botones de la tabla estén presentes y funcionando
 */
function checkTableButtons() {
    const enrollButtons = document.querySelectorAll('.btn-enroll');
    const viewButtons = document.querySelectorAll('.btn-view');
    
    console.log(`📊 Botones encontrados: ${enrollButtons.length} enroll, ${viewButtons.length} view`);
    
    // Solo agregar event listeners si no hay onclick handlers
    enrollButtons.forEach(btn => {
        if (!btn.hasAttribute('onclick') && !btn.hasAttribute('data-fixed')) {
            btn.addEventListener('click', handleEnrollButtonClick);
            btn.setAttribute('data-fixed', 'true');
        }
    });
    
    viewButtons.forEach(btn => {
        if (!btn.hasAttribute('onclick') && !btn.hasAttribute('data-fixed')) {
            btn.addEventListener('click', handleViewButtonClick);
            btn.setAttribute('data-fixed', 'true');
        }
    });
}

/**
 * Manejar click del botón de enrolamiento
 */
function handleEnrollButtonClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const button = event.currentTarget;
    
    // Obtener datos del botón
    let employeeId = button.getAttribute('data-employee-id');
    let employeeName = button.getAttribute('data-employee-name');
    
    // Fallback: obtener de la fila si no están en el botón
    if (!employeeId) {
        const row = button.closest('tr');
        if (row) {
            employeeId = row.getAttribute('data-employee-id');
            const nameCell = row.cells[1]; // Segunda columna tiene el nombre
            employeeName = nameCell ? nameCell.textContent.trim() : 'Empleado';
        }
    }
    
    if (employeeId) {
        console.log(`🔗 Click de enrolamiento: ${employeeId} - ${employeeName}`);
        
        if (typeof openBiometricModal === 'function') {
            openBiometricModal(employeeId, employeeName);
        } else {
            showSystemAlert(`Función openBiometricModal no disponible. ID: ${employeeId}`);
        }
    } else {
        console.warn('⚠️ No se pudo obtener el ID del empleado');
    }
}

/**
 * Manejar click del botón de ver detalles
 */
function handleViewButtonClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const button = event.currentTarget;
    
    // Obtener ID del empleado del botón
    let employeeId = button.getAttribute('data-employee-id');
    
    // Fallback: obtener de la fila si no está en el botón
    if (!employeeId) {
        const row = button.closest('tr');
        if (row) {
            employeeId = row.getAttribute('data-employee-id');
        }
    }
    
    if (employeeId) {
        console.log(`👁️ Click de ver detalles: ${employeeId}`);
        
        if (typeof viewBiometricDetails === 'function') {
            viewBiometricDetails(employeeId);
        } else {
            showSystemAlert(`Función viewBiometricDetails no disponible. ID: ${employeeId}`);
        }
    } else {
        console.warn('⚠️ No se pudo obtener el ID del empleado para ver detalles');
    }
}

/**
 * Verificar funciones globales
 */
function checkGlobalFunctions() {
    const requiredFunctions = [
        'openBiometricModal',
        'viewBiometricDetails',
        'loadBiometricEmployees'
    ];
    
    requiredFunctions.forEach(funcName => {
        const available = typeof window[funcName] === 'function';
        console.log(`${available ? '✅' : '❌'} Función ${funcName}: ${available ? 'Disponible' : 'No disponible'}`);
    });
}

/**
 * Cargar Bootstrap como fallback
 */
function loadBootstrapFallback() {
    if (document.querySelector('script[src*="bootstrap"]')) {
        console.log('🔄 Bootstrap ya está siendo cargado...');
        return;
    }
    
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
    script.onload = () => {
        console.log('✅ Bootstrap cargado como fallback');
    };
    script.onerror = () => {
        console.error('❌ Error cargando Bootstrap fallback');
    };
    document.head.appendChild(script);
}

/**
 * Agregar event listeners de respaldo
 */
function addFallbackEventListeners() {
    // Observer para nuevos botones que se agreguen dinámicamente
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                setTimeout(checkTableButtons, 100);
            }
        });
    });
    
    const tableBody = document.getElementById('employeeTableBody');
    if (tableBody) {
        observer.observe(tableBody, {
            childList: true,
            subtree: true
        });
    }
}

/**
 * Mostrar alerta del sistema
 */
function showSystemAlert(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning alert-dismissible';
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    alertDiv.innerHTML = `
        <strong><i class="fas fa-exclamation-triangle"></i> Sistema Biométrico:</strong>
        <br>${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remover después de 10 segundos
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 10000);
}

/**
 * Función de reparación manual
 */
function repairBiometricSystem() {
    console.log('🔧 Ejecutando reparación manual del sistema...');
    
    // Recargar scripts principales si es necesario
    if (typeof openBiometricModal !== 'function') {
        const script = document.createElement('script');
        script.src = 'assets/js/biometric-enrollment-ajax.js';
        script.onload = () => {
            console.log('✅ Script principal recargado');
            performSystemCheck();
        };
        document.head.appendChild(script);
    }
    
    // Forzar recarga de empleados
    if (typeof loadBiometricEmployees === 'function') {
        loadBiometricEmployees();
    }
}

// Exponer funciones para debugging
window.performSystemCheck = performSystemCheck;
window.repairBiometricSystem = repairBiometricSystem;
window.checkTableButtons = checkTableButtons;

console.log('🛡️ Sistema de verificación biométrica cargado');
