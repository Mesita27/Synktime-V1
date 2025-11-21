/**
 * Script de verificación y corrección biométrica
 * Este script soluciona problemas comunes en el módulo de inscripción biométrica
 */

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Verificador biométrico inicializado');
    
    // 1. Verificar que Bootstrap esté disponible
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap no está disponible, intentando cargar');
        loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js');
    } else {
        console.log('✅ Bootstrap disponible');
    }
    
    // 2. Verificar elementos críticos en el DOM
    const criticalElements = [
        { id: 'employeeTableBody', name: 'Tabla de empleados' },
        { id: 'totalEmployees', name: 'Contador de empleados' },
        { id: 'btnRefreshStats', name: 'Botón de actualizar' },
        { id: 'btnDiagnostic', name: 'Botón de diagnóstico' }
    ];
    
    let missingElements = [];
    criticalElements.forEach(element => {
        if (!document.getElementById(element.id)) {
            console.error(`❌ Elemento crítico no encontrado: ${element.name} (${element.id})`);
            missingElements.push(element);
        } else {
            console.log(`✅ Elemento encontrado: ${element.name}`);
        }
    });
    
    // 3. Verificar que la tabla de empleados tenga manejador de eventos
    const employeeTable = document.getElementById('employeeTableBody');
    if (employeeTable && employeeTable.__events === undefined) {
        console.warn('⚠️ La tabla de empleados no tiene eventos asignados, posible problema de inicialización');
    }
    
    // 4. Verificar variables globales importantes
    if (typeof employeeData === 'undefined' || typeof filteredEmployees === 'undefined') {
        console.error('❌ Variables globales críticas no definidas');
        
        // Intentar definir las variables si no existen
        if (typeof window.employeeData === 'undefined') {
            window.employeeData = [];
            console.log('✅ Variable employeeData creada');
        }
        
        if (typeof window.filteredEmployees === 'undefined') {
            window.filteredEmployees = [];
            console.log('✅ Variable filteredEmployees creada');
        }
    }
    
    // 5. Verificar botón de diagnóstico
    const diagnosticButton = document.getElementById('btnDiagnostic');
    if (diagnosticButton) {
        if (!diagnosticButton.onclick) {
            diagnosticButton.addEventListener('click', function() {
                console.log('🔍 Ejecutando diagnóstico desde el verificador');
                if (typeof runSystemDiagnostic === 'function') {
                    runSystemDiagnostic();
                } else {
                    runDiagnostic();
                }
            });
            console.log('✅ Evento click añadido al botón de diagnóstico');
        }
    }
    
    // 6. Verificar botón de actualizar
    const refreshButton = document.getElementById('btnRefreshStats');
    if (refreshButton) {
        if (!refreshButton.onclick) {
            refreshButton.addEventListener('click', function() {
                console.log('🔄 Actualizando datos desde el verificador');
                if (typeof refreshData === 'function') {
                    refreshData();
                } else {
                    location.reload();
                }
            });
            console.log('✅ Evento click añadido al botón de actualizar');
        }
    }
    
    // 7. Agregar botón de forzar carga si hay elementos faltantes
    if (missingElements.length > 0) {
        const actionArea = document.querySelector('.employee-actions');
        if (actionArea) {
            const forceButton = document.createElement('button');
            forceButton.className = 'btn-danger';
            forceButton.innerHTML = '<i class="fas fa-bolt"></i> Forzar carga';
            forceButton.addEventListener('click', forceDataLoad);
            actionArea.appendChild(forceButton);
            console.log('✅ Botón de forzar carga añadido');
        }
    }
    
    // 8. Verificar si hay datos cargados
    setTimeout(checkDataLoaded, 2000);
});

/**
 * Cargar script dinámicamente
 */
function loadScript(url) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = url;
        script.onload = () => {
            console.log(`✅ Script cargado: ${url}`);
            resolve();
        };
        script.onerror = () => {
            console.error(`❌ Error al cargar script: ${url}`);
            reject();
        };
        document.head.appendChild(script);
    });
}

/**
 * Forzar carga de datos
 */
function forceDataLoad() {
    console.log('⚡ Forzando carga de datos');
    
    fetch('api/biometric/direct-employees.php')
        .then(response => response.json())
        .then(data => {
            console.log('Datos forzados recibidos:', data);
            
            if (data.data && data.data.length > 0) {
                if (typeof employeeData !== 'undefined') {
                    employeeData = data.data;
                    filteredEmployees = [...employeeData];
                    
                    if (typeof updateStatistics === 'function') {
                        updateStatistics();
                    }
                    
                    if (typeof displayEmployees === 'function') {
                        displayEmployees();
                    } else {
                        renderEmployeeTable(data.data);
                    }
                    
                    showNotification('Datos cargados correctamente mediante método forzado', 'success');
                } else {
                    renderEmployeeTable(data.data);
                }
            } else {
                showNotification('No se encontraron empleados', 'warning');
            }
        })
        .catch(error => {
            console.error('Error en carga forzada:', error);
            showNotification('Error en carga forzada: ' + error.message, 'error');
        });
}

/**
 * Ejecutar diagnóstico del sistema biométrico
 */
function runDiagnostic() {
    console.log('🔍 Ejecutando diagnóstico del sistema biométrico');
    
    // Mostrar notificación de inicio
    showNotification('Ejecutando diagnóstico del sistema...', 'info');
    
    // Cambiar apariencia del botón
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
    
    // Realizar diagnóstico con la API
    fetch('api/biometric/self-diagnostic.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Diagnóstico completado:', data);
            
            // Procesar resultados
            if (data && data.success !== false) {
                showDiagnosticResults(data);
                
                // Si el diagnóstico devuelve datos de empleados, intentar usarlos
                if (data.data && data.data.length > 0) {
                    console.log('El diagnóstico encontró empleados, intentando cargarlos');
                    
                    if (typeof window.employeeData !== 'undefined') {
                        window.employeeData = data.data;
                        window.filteredEmployees = [...data.data];
                        
                        if (typeof updateStatistics === 'function') {
                            updateStatistics();
                        }
                        
                        if (typeof displayEmployees === 'function') {
                            displayEmployees();
                        }
                    } else {
                        renderEmployeeTable(data.data);
                    }
                }
            } else {
                showNotification('El diagnóstico encontró problemas en el sistema', 'warning');
                console.error('Problemas en el diagnóstico:', data);
                showDiagnosticResults(data);
            }
        })
        .catch(error => {
            console.error('Error en el diagnóstico:', error);
            showNotification('Error al ejecutar el diagnóstico: ' + error.message, 'error');
            
            // Intentar con API alternativa
            setTimeout(() => {
                console.log('Intentando diagnóstico alternativo con direct-employees.php');
                fetch('api/biometric/direct-employees.php')
                    .then(response => response.json())
                    .then(data => {
                        console.log('Datos alternativos recibidos:', data);
                        showNotification('Diagnóstico alternativo completado', 'info');
                        
                        if (data.data && data.data.length > 0) {
                            // Intentar actualizar con estos datos
                            if (typeof window.employeeData !== 'undefined') {
                                window.employeeData = data.data;
                                window.filteredEmployees = [...data.data];
                                
                                if (typeof updateStatistics === 'function') updateStatistics();
                                if (typeof displayEmployees === 'function') displayEmployees();
                            }
                        }
                    })
                    .catch(altError => {
                        console.error('Error en diagnóstico alternativo:', altError);
                    });
            }, 1000);
        });
}

/**
 * Mostrar resultados del diagnóstico en un modal
 */
function showDiagnosticResults(data) {
    // Crear contenido HTML para los resultados
    const resultsHtml = `
        <div class="p-3">
            <div class="alert ${data.success ? 'alert-success' : 'alert-warning'}">
                <h5><i class="fas fa-${data.success ? 'check-circle' : 'exclamation-triangle'}"></i> 
                    ${data.success ? 'Diagnóstico exitoso' : 'Se detectaron problemas'}</h5>
                <p>${data.message || 'Diagnóstico del sistema completado.'}</p>
            </div>
            
            ${data.tests && data.tests.length ? `
                <div class="mt-3">
                    <h6>Resultados de las pruebas:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Prueba</th>
                                    <th>Estado</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.tests.map(test => `
                                    <tr>
                                        <td>${test.name}</td>
                                        <td>
                                            ${test.success 
                                                ? '<span class="badge bg-success">Éxito</span>' 
                                                : '<span class="badge bg-danger">Error</span>'}
                                        </td>
                                        <td>${test.message}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            ` : ''}
            
            ${data.recommendations && data.recommendations.length ? `
                <div class="mt-3">
                    <h6>Recomendaciones:</h6>
                    <ul class="list-group">
                        ${data.recommendations.map(rec => `
                            <li class="list-group-item list-group-item-${rec.priority === 'high' ? 'danger' : rec.priority === 'medium' ? 'warning' : 'info'}">
                                ${rec.message}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            ` : ''}
            
            <div class="mt-3 text-muted">
                <small>Diagnóstico ejecutado: ${data.timestamp || new Date().toLocaleString()}</small>
            </div>
        </div>
    `;
    
    // Crear el modal
    const modalHtml = `
        <div class="modal fade" id="diagnosticResultsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Resultados del diagnóstico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${resultsHtml}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="forceDataLoad()">
                            <i class="fas fa-sync-alt"></i> Forzar carga
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Añadir modal al DOM
    let modalContainer = document.getElementById('diagnosticModalContainer');
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.id = 'diagnosticModalContainer';
        document.body.appendChild(modalContainer);
    }
    
    modalContainer.innerHTML = modalHtml;
    
    // Mostrar modal
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('diagnosticResultsModal'));
        modal.show();
    } else {
        alert('No se puede mostrar el modal: Bootstrap no está disponible');
        console.log('Resultados del diagnóstico:', data);
    }
}

/**
 * Mostrar una notificación en pantalla
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '350px';
    
    notification.innerHTML = `
        <strong>${type === 'success' ? '✅' : type === 'warning' ? '⚠️' : type === 'error' ? '❌' : 'ℹ️'} ${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> 
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    `;
    
    // Añadir a la página
    document.body.appendChild(notification);
    
    // Auto-cerrar después del tiempo especificado
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
    
    return notification;
}

/**
 * Verificar si se han cargado datos
 */
function checkDataLoaded() {
    const tableBody = document.getElementById('employeeTableBody');
    
    if (tableBody && (!tableBody.children || tableBody.children.length === 0)) {
        console.warn('⚠️ No se detectaron datos cargados en la tabla');
        
        // Verificar si las variables globales están disponibles pero vacías
        if (typeof employeeData !== 'undefined' && employeeData.length === 0) {
            console.log('Variables disponibles pero sin datos, intentando forzar carga');
            forceDataLoad();
        }
    }
}

/**
 * Renderizar tabla de empleados cuando las funciones normales no están disponibles
 */
function renderEmployeeTable(employees) {
    const tableBody = document.getElementById('employeeTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (employees.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center">No hay empleados disponibles</td>
            </tr>
        `;
        return;
    }
    
    employees.forEach(employee => {
        const row = document.createElement('tr');
        
        const hasFacial = employee.biometric_status?.facial || false;
        const hasFingerprint = employee.biometric_status?.fingerprint || false;
        const isEnrolled = hasFacial || hasFingerprint;
        const statusClass = isEnrolled ? 'text-success' : 'text-warning';
        const statusText = isEnrolled ? 'Inscrito' : 'Pendiente';
        
        row.innerHTML = `
            <td><strong>${employee.ID_EMPLEADO || employee.id || ''}</strong></td>
            <td>${employee.NOMBRE || employee.nombre || ''} ${employee.APELLIDO || employee.apellido || ''}</td>
            <td>${employee.ESTABLECIMIENTO || employee.establecimiento || '-'}</td>
            <td><span class="badge ${statusClass}">${statusText}</span></td>
            <td>${hasFacial ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'}</td>
            <td>${hasFingerprint ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'}</td>
            <td>${employee.UPDATED_AT || '-'}</td>
            <td>
                <button class="btn btn-sm btn-primary">
                    <i class="fas fa-fingerprint"></i>
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Actualizar contadores
    const total = employees.length;
    const enrolled = employees.filter(emp => (emp.biometric_status?.facial || emp.biometric_status?.fingerprint)).length;
    const pending = total - enrolled;
    const percentage = total > 0 ? Math.round((enrolled / total) * 100) : 0;
    
    const elements = {
        totalEmployees: document.getElementById('totalEmployees'),
        enrolledCount: document.getElementById('enrolledCount'),
        pendingCount: document.getElementById('pendingCount'),
        enrollmentPercentage: document.getElementById('enrollmentPercentage')
    };
    
    if (elements.totalEmployees) elements.totalEmployees.textContent = total;
    if (elements.enrolledCount) elements.enrolledCount.textContent = enrolled;
    if (elements.pendingCount) elements.pendingCount.textContent = pending;
    if (elements.enrollmentPercentage) elements.enrollmentPercentage.textContent = `${percentage}%`;
}

/**
 * Verificar si los datos se han cargado
 */
function checkDataLoaded() {
    const tableBody = document.getElementById('employeeTableBody');
    if (!tableBody) return;
    
    // Verificar si hay filas en la tabla (excluyendo filas de mensajes)
    const hasRows = tableBody.querySelectorAll('tr:not(.no-data)').length > 0;
    const hasError = tableBody.querySelector('.error-indicator') !== null;
    
    if (!hasRows && !hasError) {
        console.warn('⚠️ No se detectan datos cargados en la tabla después de 2 segundos');
        showNotification('No se detectan datos cargados. Use el botón "Forzar carga" si el problema persiste.', 'warning');
    }
}

/**
 * Mostrar notificación si la función no existe en el ámbito global
 */
function showNotification(message, type = 'info') {
    console.log(`Notificación (${type}): ${message}`);
    
    if (typeof window.showNotification === 'function') {
        // Usar la función existente si está disponible
        return window.showNotification(message, type);
    }
    
    // Implementación fallback
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    return notification;
}

// Exponer funciones globalmente
window.forceDataLoad = forceDataLoad;
window.runDiagnostic = runDiagnostic;
window.showDiagnosticResults = showDiagnosticResults;
window.renderEmployeeTable = renderEmployeeTable;
window.checkDataLoaded = checkDataLoaded;

// Ejecutar diagnóstico programado
setTimeout(() => {
    console.log('Ejecutando verificación programada...');
    
    const tableBody = document.getElementById('employeeTableBody');
    if (tableBody && (!tableBody.children || tableBody.children.length === 0)) {
        console.warn('⚠️ No se detectaron datos después de inicialización, ejecutando carga forzada');
        forceDataLoad();
    }
}, 5000);
