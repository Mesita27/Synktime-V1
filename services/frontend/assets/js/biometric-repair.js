/**
 * Script para reparar el botón de diagnóstico y mostrar empleados
 */

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Script de reparación biométrica cargado');

    // 1. Arreglar el botón de diagnóstico de forma directa
    fixDiagnosticButton();
    
    // 2. Añadir los escuchadores de eventos faltantes
    attachEventListeners();
    
    // 3. Cargar datos de empleados directamente
    loadEmployeesDirectly();
    
    // 4. Cargar sedes y establecimientos
    loadLocationsDirectly();
    
    console.log('🔧 Correcciones aplicadas');
});

/**
 * Arreglar el botón de diagnóstico
 */
function fixDiagnosticButton() {
    console.log('🔧 Corrigiendo el botón de diagnóstico');
    
    const btnDiagnostic = document.getElementById('btnDiagnostic');
    if (btnDiagnostic) {
        // Eliminar handlers existentes
        const newBtn = btnDiagnostic.cloneNode(true);
        if (btnDiagnostic.parentNode) {
            btnDiagnostic.parentNode.replaceChild(newBtn, btnDiagnostic);
        }
        
        // Añadir nuevo event listener
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🔍 Botón de diagnóstico clickeado');
            runDiagnostic();
        });
        
        console.log('✅ Botón de diagnóstico reparado');
    } else {
        console.error('❌ No se encontró el botón de diagnóstico');
    }
}

/**
 * Ejecutar diagnóstico
 */
function runDiagnostic() {
    console.log('🔍 Ejecutando diagnóstico del sistema');
    showNotification('Ejecutando diagnóstico del sistema...', 'info');
    
    // Cambiar apariencia del botón
    const btnDiagnostic = document.getElementById('btnDiagnostic');
    if (btnDiagnostic) {
        const originalContent = btnDiagnostic.innerHTML;
        btnDiagnostic.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analizando...';
        btnDiagnostic.disabled = true;
        
        // Restaurar después del diagnóstico
        setTimeout(function() {
            btnDiagnostic.innerHTML = originalContent;
            btnDiagnostic.disabled = false;
        }, 3000);
    }
    
    // Verificar conexiones
    Promise.all([
        testApiEndpoint('api/get-sedes.php'),
        testApiEndpoint('api/get-establecimientos.php'),
        testApiEndpoint('api/employee/list.php'),
        testApiEndpoint('api/biometric/direct-employees.php')
    ]).then(results => {
        console.log('Resultados del diagnóstico:', results);
        
        // Contar errores
        const errors = results.filter(r => !r.success).length;
        
        if (errors === 0) {
            showNotification('Diagnóstico completado: Todos los sistemas funcionan correctamente', 'success');
            // Intentar cargar datos de nuevo
            loadEmployeesDirectly();
            loadLocationsDirectly();
        } else {
            showNotification(`Se encontraron ${errors} problemas en el sistema`, 'warning');
            showDiagnosticResults(results);
        }
    }).catch(error => {
        console.error('Error en diagnóstico:', error);
        showNotification('Error al ejecutar diagnóstico: ' + error.message, 'error');
    });
}

/**
 * Probar un endpoint de API
 */
function testApiEndpoint(url) {
    return fetch(url)
        .then(response => {
            return response.text()
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        return {
                            url: url,
                            success: response.ok,
                            status: response.status,
                            statusText: response.statusText,
                            data: data
                        };
                    } catch (e) {
                        return {
                            url: url,
                            success: false,
                            status: response.status,
                            statusText: 'Error al parsear JSON: ' + e.message,
                            rawData: text.substring(0, 100) + '...'
                        };
                    }
                });
        })
        .catch(error => {
            return {
                url: url,
                success: false,
                status: 0,
                statusText: 'Error de conexión: ' + error.message
            };
        });
}

/**
 * Mostrar resultados del diagnóstico
 */
function showDiagnosticResults(results) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'diagnosticResultsModal';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-hidden', 'true');
    
    let resultsHtml = '';
    results.forEach(result => {
        resultsHtml += `
            <div class="card mb-3 ${result.success ? 'border-success' : 'border-danger'}">
                <div class="card-header ${result.success ? 'bg-success text-white' : 'bg-danger text-white'}">
                    ${result.url}
                </div>
                <div class="card-body">
                    <p><strong>Estado:</strong> ${result.status} ${result.statusText}</p>
                    ${result.success ? '<p class="text-success"><i class="fas fa-check-circle"></i> Funcionando correctamente</p>' : 
                                       '<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error en el endpoint</p>'}
                </div>
            </div>
        `;
    });
    
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resultados del Diagnóstico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ${resultsHtml}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnReloadPage">Recargar Página</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Inicializar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    // Añadir evento al botón de recargar
    document.getElementById('btnReloadPage').addEventListener('click', function() {
        window.location.reload();
    });
    
    // Auto-eliminar modal al cerrar
    modal.addEventListener('hidden.bs.modal', function() {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
    });
}

/**
 * Añadir escuchadores de eventos faltantes
 */
function attachEventListeners() {
    // Botón de recargar estadísticas
    const btnRefreshStats = document.getElementById('btnRefreshStats');
    if (btnRefreshStats) {
        btnRefreshStats.addEventListener('click', function() {
            console.log('🔄 Actualizando datos');
            loadEmployeesDirectly();
            loadLocationsDirectly();
        });
    }
    
    // Botón de buscar empleados
    const btnBuscarEmpleados = document.getElementById('btnBuscarEmpleados');
    if (btnBuscarEmpleados) {
        btnBuscarEmpleados.addEventListener('click', function() {
            applyFilters();
        });
    }
    
    // Botón de limpiar filtros
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', function() {
            clearFilters();
        });
    }
}

/**
 * Cargar empleados directamente
 */
function loadEmployeesDirectly() {
    console.log('📋 Cargando empleados directamente');
    
    // Mostrar indicador de carga
    const tableBody = document.getElementById('employeeTableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="loading-indicator">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p>Cargando datos de empleados...</p>
                    </div>
                </td>
            </tr>
        `;
    }
    
    // Intentar primero con direct-employees.php
    fetch('api/biometric/direct-employees.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success && data.data && data.data.length > 0) {
                console.log('✅ Datos cargados desde direct-employees.php:', data.data.length);
                window.employeeData = data.data;
                window.filteredEmployees = [...data.data];
                displayEmployees();
                updateStatistics();
                showNotification('Datos cargados correctamente', 'success');
            } else {
                throw new Error('No se encontraron empleados');
            }
        })
        .catch(error => {
            console.error('Error con direct-employees.php:', error);
            
            // Intentar con endpoint alternativo
            fetch('api/employee/list.php')
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Datos cargados desde endpoint alternativo:', data);
                    if (data && data.data && data.data.length > 0) {
                        window.employeeData = data.data;
                        window.filteredEmployees = [...data.data];
                        displayEmployees();
                        updateStatistics();
                        showNotification('Datos cargados desde fuente alternativa', 'info');
                    } else {
                        showNotification('No se encontraron empleados', 'warning');
                        
                        // Mostrar mensaje en tabla
                        if (tableBody) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No se encontraron empleados en el sistema
                                        </div>
                                        <button class="btn btn-sm btn-primary" onclick="runDiagnostic()">
                                            <i class="fas fa-stethoscope"></i> Ejecutar Diagnóstico
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al cargar empleados:', error);
                    showNotification('Error al cargar empleados: ' + error.message, 'error');
                    
                    // Mostrar mensaje de error en tabla
                    if (tableBody) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        Error al cargar empleados: ${error.message}
                                    </div>
                                    <button class="btn btn-sm btn-primary" onclick="runDiagnostic()">
                                        <i class="fas fa-stethoscope"></i> Ejecutar Diagnóstico
                                    </button>
                                </td>
                            </tr>
                        `;
                    }
                });
        });
}

/**
 * Cargar sedes y establecimientos directamente
 */
function loadLocationsDirectly() {
    console.log('🏢 Cargando sedes y establecimientos');
    
    // Cargar sedes
    fetch('api/get-sedes.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sedes && data.sedes.length > 0) {
                const filtroSede = document.getElementById('filtro_sede');
                if (filtroSede) {
                    filtroSede.innerHTML = '<option value="">Todas las sedes</option>';
                    data.sedes.forEach(sede => {
                        const option = document.createElement('option');
                        option.value = sede.ID_SEDE;
                        option.textContent = sede.NOMBRE;
                        filtroSede.appendChild(option);
                    });
                    console.log('✅ Sedes cargadas:', data.sedes.length);
                }
            }
        })
        .catch(error => console.error('Error al cargar sedes:', error));
    
    // Cargar establecimientos
    fetch('api/get-establecimientos.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.establecimientos && data.establecimientos.length > 0) {
                const filtroEstablecimiento = document.getElementById('filtro_establecimiento');
                if (filtroEstablecimiento) {
                    filtroEstablecimiento.innerHTML = '<option value="">Todos los establecimientos</option>';
                    data.establecimientos.forEach(est => {
                        const option = document.createElement('option');
                        option.value = est.ID_ESTABLECIMIENTO;
                        option.textContent = est.NOMBRE;
                        filtroEstablecimiento.appendChild(option);
                    });
                    console.log('✅ Establecimientos cargados:', data.establecimientos.length);
                }
            }
        })
        .catch(error => console.error('Error al cargar establecimientos:', error));
}

/**
 * Aplicar filtros a los empleados
 */
function applyFilters() {
    console.log('🔍 Aplicando filtros');
    
    if (!window.employeeData || window.employeeData.length === 0) {
        console.warn('No hay datos para filtrar');
        return;
    }
    
    // Obtener valores de filtro
    const sede = document.getElementById('filtro_sede')?.value || '';
    const establecimiento = document.getElementById('filtro_establecimiento')?.value || '';
    const estado = document.getElementById('filtro_estado')?.value || '';
    const busqueda = document.getElementById('busqueda_empleado')?.value || '';
    
    console.log('Filtros:', { sede, establecimiento, estado, busqueda });
    
    // Filtrar empleados
    window.filteredEmployees = window.employeeData.filter(emp => {
        // Filtro de sede
        if (sede && emp.ID_SEDE && emp.ID_SEDE != sede) {
            return false;
        }
        
        // Filtro de establecimiento
        if (establecimiento && emp.ID_ESTABLECIMIENTO && emp.ID_ESTABLECIMIENTO != establecimiento) {
            return false;
        }
        
        // Filtro de estado
        if (estado) {
            if (estado === 'enrolled' && emp.biometric_status !== 'enrolled') {
                return false;
            }
            if (estado === 'pending' && emp.biometric_status !== 'pending') {
                return false;
            }
            if (estado === 'partial' && emp.biometric_status !== 'partial') {
                return false;
            }
        }
        
        // Filtro de búsqueda
        if (busqueda) {
            const searchLower = busqueda.toLowerCase();
            const nombre = (emp.nombre || emp.NOMBRE || '').toLowerCase();
            const apellido = (emp.apellido || emp.APELLIDO || '').toLowerCase();
            const codigo = (emp.codigo || '').toLowerCase();
            
            if (!nombre.includes(searchLower) && 
                !apellido.includes(searchLower) && 
                !codigo.includes(searchLower)) {
                return false;
            }
        }
        
        return true;
    });
    
    // Actualizar tabla
    displayEmployees();
}

/**
 * Limpiar filtros
 */
function clearFilters() {
    console.log('🧹 Limpiando filtros');
    
    // Limpiar campos
    if (document.getElementById('filtro_sede')) {
        document.getElementById('filtro_sede').value = '';
    }
    
    if (document.getElementById('filtro_establecimiento')) {
        document.getElementById('filtro_establecimiento').value = '';
    }
    
    if (document.getElementById('filtro_estado')) {
        document.getElementById('filtro_estado').value = '';
    }
    
    if (document.getElementById('busqueda_empleado')) {
        document.getElementById('busqueda_empleado').value = '';
    }
    
    // Resetear filtros
    if (window.employeeData) {
        window.filteredEmployees = [...window.employeeData];
        displayEmployees();
    }
}

/**
 * Actualizar estadísticas
 */
function updateStatistics() {
    // Verificar que tenemos datos
    if (!window.employeeData || window.employeeData.length === 0) {
        console.warn('No hay datos para actualizar estadísticas');
        
        const elements = {
            totalEmployees: document.getElementById('totalEmployees'),
            enrolledCount: document.getElementById('enrolledCount'),
            pendingCount: document.getElementById('pendingCount'),
            enrollmentPercentage: document.getElementById('enrollmentPercentage')
        };
        
        if (elements.totalEmployees) elements.totalEmployees.textContent = '0';
        if (elements.enrolledCount) elements.enrolledCount.textContent = '0';
        if (elements.pendingCount) elements.pendingCount.textContent = '0';
        if (elements.enrollmentPercentage) elements.enrollmentPercentage.textContent = '0%';
        
        return;
    }
    
    console.log('📊 Actualizando estadísticas');
    
    const total = window.employeeData.length;
    
    // Contar empleados enrolados
    const enrolled = window.employeeData.filter(emp => 
        emp.biometric_status === 'enrolled' || 
        (emp.facial_enrolled === true && emp.fingerprint_enrolled === true)
    ).length;
    
    // Contar empleados parcialmente enrolados
    const partial = window.employeeData.filter(emp => 
        emp.biometric_status === 'partial' || 
        ((emp.facial_enrolled === true && emp.fingerprint_enrolled === false) || 
         (emp.facial_enrolled === false && emp.fingerprint_enrolled === true))
    ).length;
    
    // Calcular pendientes
    const pending = total - enrolled - partial;
    
    // Calcular porcentaje
    const percentage = total > 0 ? Math.round((enrolled / total) * 100) : 0;
    
    // Actualizar elementos del DOM
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
    
    console.log('Estadísticas actualizadas:', { total, enrolled, pending, percentage });
}

/**
 * Mostrar empleados en la tabla
 */
function displayEmployees() {
    if (!window.filteredEmployees) {
        console.warn('No hay empleados filtrados para mostrar');
        return;
    }
    
    console.log('🖥️ Mostrando empleados en tabla:', window.filteredEmployees.length);
    
    const tableBody = document.getElementById('employeeTableBody');
    if (!tableBody) {
        console.error('No se encontró el elemento de la tabla');
        return;
    }
    
    // Limpiar tabla
    tableBody.innerHTML = '';
    
    // Mostrar mensaje si no hay empleados
    if (window.filteredEmployees.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-info-circle me-2"></i> No se encontraron empleados con los filtros actuales
                </td>
            </tr>
        `;
        return;
    }
    
    // Agregar filas de empleados
    window.filteredEmployees.forEach(employee => {
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
            <td>${employee.nombre || (employee.NOMBRE + ' ' + employee.APELLIDO)}</td>
            <td>${employee.establecimiento || employee.ESTABLECIMIENTO || 'Sin asignar'}</td>
            <td><span class="badge ${badgeClass}">${biometricStatus === 'enrolled' ? 'Inscrito' : 
                (biometricStatus === 'partial' ? 'Parcial' : 'Pendiente')}</span></td>
            <td><span class="badge ${facialBadgeClass}">${facialEnrolled ? 'Inscrito' : 'Pendiente'}</span></td>
            <td><span class="badge ${fingerprintBadgeClass}">${fingerprintEnrolled ? 'Inscrito' : 'Pendiente'}</span></td>
            <td>${lastUpdated}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="openEnrollmentModal(${employee.ID_EMPLEADO || employee.id})">
                    <i class="fas fa-fingerprint"></i> Inscribir
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Usar la función global si existe
    if (typeof window.showNotification === 'function') {
        return window.showNotification(message, type, duration);
    }
    
    console.log(`${type.toUpperCase()}: ${message}`);
    
    // Crear notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <div>${message}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remover después de la duración especificada
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, duration);
    
    return notification;
}

// Exponer funciones globalmente
window.runDiagnostic = runDiagnostic;
window.loadEmployeesDirectly = loadEmployeesDirectly;
window.loadLocationsDirectly = loadLocationsDirectly;
window.applyFilters = applyFilters;
window.clearFilters = clearFilters;
window.showNotification = showNotification;
window.displayEmployees = displayEmployees;
window.updateStatistics = updateStatistics;
