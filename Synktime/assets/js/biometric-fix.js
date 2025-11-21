/**
 * Script para asegurarse que el botón de diagnóstico funcione
 * Este script se ejecuta al final de la carga para garantizar la funcionalidad
 */

// Ejecutar cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('💉 Inyectando correcciones finales...');
    
    // 1. Corregir el botón de diagnóstico de forma directa
    const diagnosticButton = document.getElementById('btnDiagnostic');
    if (diagnosticButton) {
        // Eliminar todos los eventos existentes (pueden estar causando conflictos)
        const newButton = diagnosticButton.cloneNode(true);
        if (diagnosticButton.parentNode) {
            diagnosticButton.parentNode.replaceChild(newButton, diagnosticButton);
        }
        
        // Añadir un manejador de eventos directo
        newButton.addEventListener('click', function() {
            console.log('🚨 Click en botón de diagnóstico (corrección final)');
            
            // Intentar llamar a todas las funciones de diagnóstico posibles
            if (typeof window.runDiagnostic === 'function') {
                console.log('Ejecutando runDiagnostic()');
                window.runDiagnostic();
            } else if (typeof window.runSystemDiagnostic === 'function') {
                console.log('Ejecutando runSystemDiagnostic()');
                window.runSystemDiagnostic();
            } else if (typeof window.diagnosticoBiometrico === 'function') {
                console.log('Ejecutando diagnosticoBiometrico()');
                window.diagnosticoBiometrico();
            } else {
                console.log('Ejecutando diagnóstico local');
                executeDiagnostic();
            }
        });
        
        console.log('✅ Botón de diagnóstico corregido exitosamente');
    } else {
        console.error('❌ No se encontró el botón de diagnóstico');
    }
    
    // 2. Verificar si hay datos en la tabla, si no hay, intentar cargarlos
    const employeeTableBody = document.getElementById('employeeTableBody');
    if (employeeTableBody) {
        if (!employeeTableBody.children || employeeTableBody.children.length === 0 || 
            (employeeTableBody.children.length === 1 && 
             employeeTableBody.children[0].textContent.includes('No se encontraron'))) {
            
            console.log('⚠️ No hay datos en la tabla de empleados, intentando cargar...');
            
            // Intentar cargar datos de todas las formas posibles
            if (typeof window.loadEmployeeData === 'function') {
                window.loadEmployeeData();
            } else if (typeof window.forceDataLoad === 'function') {
                window.forceDataLoad();
            } else {
                loadEmployeesDirectly();
            }
        } else {
            console.log('✅ La tabla de empleados ya tiene datos');
        }
    }
});

/**
 * Ejecutar diagnóstico directo si todas las demás opciones fallan
 */
function executeDiagnostic() {
    console.log('🔍 Ejecutando diagnóstico directo');
    
    // Mostrar alerta visual
    showSimpleNotification('Ejecutando diagnóstico del sistema...', 'info');
    
    // Cambiar estilo del botón para indicar actividad
    const btnDiagnostic = document.getElementById('btnDiagnostic');
    if (btnDiagnostic) {
        const originalText = btnDiagnostic.innerHTML;
        btnDiagnostic.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analizando...';
        btnDiagnostic.disabled = true;
        
        // Restaurar después del diagnóstico
        setTimeout(() => {
            btnDiagnostic.innerHTML = originalText;
            btnDiagnostic.disabled = false;
        }, 5000);
    }
    
    // Hacer la llamada directamente
    fetch('api/biometric/direct-employees.php')
        .then(response => response.json())
        .then(data => {
            console.log('Diagnóstico completado:', data);
            
            if (data.data && data.data.length > 0) {
                // Actualizar tabla si hay datos
                showSimpleNotification('Diagnóstico completado: Se encontraron datos de empleados', 'success');
                
                // Actualizar tabla si hay función disponible
                if (typeof window.renderEmployeeTable === 'function') {
                    window.renderEmployeeTable(data.data);
                } else {
                    updateEmployeeTable(data.data);
                }
            } else {
                showSimpleNotification('Diagnóstico completado, pero no se encontraron empleados', 'warning');
            }
        })
        .catch(error => {
            console.error('Error en diagnóstico directo:', error);
            showSimpleNotification('Error en diagnóstico: ' + error.message, 'error');
        });
}

/**
 * Cargar empleados directamente desde la API
 */
function loadEmployeesDirectly() {
    console.log('🔄 Cargando empleados directamente');
    
    fetch('api/biometric/direct-employees.php')
        .then(response => response.json())
        .then(data => {
            if (data.data && data.data.length > 0) {
                updateEmployeeTable(data.data);
                showSimpleNotification('Datos de empleados cargados correctamente', 'success');
            } else {
                showSimpleNotification('No se encontraron empleados', 'warning');
            }
        })
        .catch(error => {
            console.error('Error cargando empleados:', error);
            showSimpleNotification('Error al cargar empleados: ' + error.message, 'error');
        });
}

/**
 * Actualizar tabla de empleados directamente
 */
function updateEmployeeTable(employees) {
    const tableBody = document.getElementById('employeeTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (employees.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-info-circle me-2"></i> No se encontraron empleados
                </td>
            </tr>
        `;
        return;
    }
    
    employees.forEach(employee => {
        const row = document.createElement('tr');
        
        // Determinar estado biométrico para las insignias
        const biometricStatus = employee.biometric_status || 'pending';
        const facialEnrolled = employee.facial_enrolled || false;
        const fingerprintEnrolled = employee.fingerprint_enrolled || false;
        
        // Calcular clases de estado
        const badgeClass = biometricStatus === 'enrolled' ? 'bg-success' : 
                          (biometricStatus === 'partial' ? 'bg-warning' : 'bg-secondary');
        
        const facialBadgeClass = facialEnrolled ? 'bg-success' : 'bg-secondary';
        const fingerprintBadgeClass = fingerprintEnrolled ? 'bg-success' : 'bg-secondary';
        
        // Formatear fecha de última actualización
        const lastUpdated = employee.last_updated ? new Date(employee.last_updated).toLocaleDateString() : 'N/A';
        
        row.innerHTML = `
            <td>${employee.codigo || employee.ID_EMPLEADO || ''}</td>
            <td>${employee.nombre}</td>
            <td>${employee.establecimiento || employee.ESTABLECIMIENTO || 'Sin asignar'}</td>
            <td><span class="badge ${badgeClass}">${biometricStatus === 'enrolled' ? 'Inscrito' : 
                (biometricStatus === 'partial' ? 'Parcial' : 'Pendiente')}</span></td>
            <td><span class="badge ${facialBadgeClass}">${facialEnrolled ? 'Inscrito' : 'Pendiente'}</span></td>
            <td><span class="badge ${fingerprintBadgeClass}">${fingerprintEnrolled ? 'Inscrito' : 'Pendiente'}</span></td>
            <td>${lastUpdated}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="openEnrollmentModal(${employee.id || employee.ID_EMPLEADO})">
                    <i class="fas fa-fingerprint"></i> Inscribir
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

/**
 * Mostrar notificación simple si no hay función global disponible
 */
function showSimpleNotification(message, type = 'info') {
    if (typeof window.showNotification === 'function') {
        return window.showNotification(message, type);
    }
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    return notification;
}
