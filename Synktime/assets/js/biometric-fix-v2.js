/**
 * Script para corregir problemas de visualización y paginación en el sistema biométrico
 * Este script resuelve:
 * 1. El problema de visualización de códigos e información incorrecta
 * 2. La limitación de mostrar solo 10 empleados cuando hay más disponibles
 * 3. Problemas con el modal de detalles
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🛠️ Script de corrección biométrica v2 cargado');
    
    // Esperar un momento para que los otros scripts se inicialicen
    setTimeout(() => {
        fixBiometricSystem();
    }, 800);
});

/**
 * Función principal de corrección
 */
function fixBiometricSystem() {
    console.log('🔧 Aplicando correcciones al sistema biométrico...');
    
    // 1. Corregir la carga y visualización de empleados
    fixEmployeeDisplay();
    
    // 2. Corregir modales y eventos
    fixModals();
    
    // 3. Asegurar que el botón de diagnóstico funcione
    fixDiagnosticButton();
    
    console.log('✅ Sistema biométrico corregido');
}

/**
 * Corregir la visualización de empleados
 */
function fixEmployeeDisplay() {
    console.log('🔄 Corrigiendo visualización de empleados...');
    
    // Intentar primero con el endpoint real que solo devuelve datos reales
    fetch('api/biometric/real-employees.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error en el endpoint de datos reales (${response.status})`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success && data.data && data.data.length > 0) {
                const employees = data.data;
                console.log(`✅ ${employees.length} empleados REALES cargados correctamente`);
                showNotification('Datos reales de la base de datos cargados correctamente', 'success');
                
                // Guardar en variables globales para ambos scripts
                window.employeeData = employees;
                window.filteredEmployees = [...employees];
                
                // Actualizar contadores
                updateEmployeeCounters(employees);
                
                // Implementar visualización mejorada con paginación adecuada
                displayEmployeesWithPagination(employees);
            } else {
                throw new Error('El endpoint de datos reales no devolvió datos válidos');
            }
        })
        .catch(error => {
            console.warn('⚠️ No se pudieron cargar datos reales:', error.message);
            console.log('🔄 Intentando con endpoint alternativo...');
            
            // Cargar empleados con el endpoint tradicional que puede devolver datos simulados
            fetch('api/biometric/direct-employees.php')
                .then(response => response.json())
                .then(data => {
                    if (data && data.success && data.data) {
                        const employees = data.data;
                        console.log(`✅ ${employees.length} empleados cargados correctamente`);
                        
                        // Determinar si son datos simulados
                        const esSimulado = employees.some(emp => emp.codigo && emp.codigo.startsWith('E00'));
                        if (esSimulado) {
                            showNotification('⚠️ Usando datos simulados - La base de datos no contiene empleados reales', 'warning');
                        }
                        
                        // Guardar en variables globales para ambos scripts
                        window.employeeData = employees;
                        window.filteredEmployees = [...employees];
                        
                        // Actualizar contadores
                        updateEmployeeCounters(employees);
                        
                        // Implementar visualización mejorada con paginación adecuada
                        displayEmployeesWithPagination(employees);
                    } else {
                        console.error('Error al cargar datos de empleados:', data);
                        showNotification('Error al cargar datos de empleados', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar empleados:', error);
                    showNotification('Error al cargar empleados: ' + error.message, 'error');
                });
        });
}

/**
 * Actualizar contadores de empleados
 */
function updateEmployeeCounters(employees) {
    // Actualizar elementos de estadísticas
    const totalEmployees = employees.length;
    const enrolledCount = employees.filter(emp => emp.biometric_status === 'enrolled').length;
    const pendingCount = totalEmployees - enrolledCount;
    const percentage = totalEmployees > 0 ? Math.round((enrolledCount / totalEmployees) * 100) : 0;
    
    // Actualizar elementos del DOM
    const elements = {
        totalEmployees: document.getElementById('totalEmployees'),
        enrolledCount: document.getElementById('enrolledCount'),
        pendingCount: document.getElementById('pendingCount'),
        enrollmentPercentage: document.getElementById('enrollmentPercentage')
    };
    
    if (elements.totalEmployees) elements.totalEmployees.textContent = totalEmployees;
    if (elements.enrolledCount) elements.enrolledCount.textContent = enrolledCount;
    if (elements.pendingCount) elements.pendingCount.textContent = pendingCount;
    if (elements.enrollmentPercentage) elements.enrollmentPercentage.textContent = `${percentage}%`;
}

/**
 * Mostrar empleados con paginación mejorada
 */
function displayEmployeesWithPagination(employees) {
    const tableBody = document.getElementById('employeeTableBody');
    if (!tableBody) {
        console.error('No se encontró la tabla de empleados');
        return;
    }
    
    // Limpiar tabla
    tableBody.innerHTML = '';
    
    // Configurar paginación
    const employeesPerPage = 20; // Aumentar a 20 empleados por página
    const totalPages = Math.ceil(employees.length / employeesPerPage);
    let currentPage = 1;
    
    // Función para mostrar una página específica
    function showPage(page) {
        // Validar página
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        currentPage = page;
        
        // Calcular índices
        const startIndex = (page - 1) * employeesPerPage;
        const endIndex = Math.min(startIndex + employeesPerPage, employees.length);
        const pageEmployees = employees.slice(startIndex, endIndex);
        
        // Limpiar tabla
        tableBody.innerHTML = '';
        
        // Mostrar empleados de la página actual
        pageEmployees.forEach(employee => {
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
            
            // Asegurarse de que se muestran los códigos correctamente
            const codigo = employee.codigo || employee.CODIGO || employee.ID_EMPLEADO || employee.id || '';
            
            // Asegurarse de que se muestra el nombre completo correctamente
            let nombreCompleto = '';
            if (employee.nombre && typeof employee.nombre === 'string') {
                nombreCompleto = employee.nombre;
            } else {
                const nombre = employee.NOMBRE || employee.nombre || '';
                const apellido = employee.APELLIDO || employee.apellido || '';
                nombreCompleto = `${nombre} ${apellido}`.trim();
            }
            
            // Asegurarse de que se muestra el establecimiento correctamente
            const establecimiento = employee.ESTABLECIMIENTO || employee.establecimiento || employee.nombre_establecimiento || 'Sin asignar';
            
            row.innerHTML = `
                <td><strong>${codigo}</strong></td>
                <td>${nombreCompleto}</td>
                <td>${establecimiento}</td>
                <td><span class="badge ${badgeClass}">${biometricStatus === 'enrolled' ? 'Inscrito' : 
                    (biometricStatus === 'partial' ? 'Parcial' : 'Pendiente')}</span></td>
                <td><span class="badge ${facialBadgeClass}">${facialEnrolled ? 'Inscrito' : 'Pendiente'}</span></td>
                <td><span class="badge ${fingerprintBadgeClass}">${fingerprintEnrolled ? 'Inscrito' : 'Pendiente'}</span></td>
                <td>${lastUpdated}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="openEnrollmentModal('${employee.ID_EMPLEADO || employee.id || employee.codigo || employee.CODIGO}')">
                        <i class="fas fa-fingerprint"></i> Inscribir
                    </button>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Actualizar controles de paginación
        updatePaginationControls(page, totalPages);
    }
    
    // Crear controles de paginación
    function updatePaginationControls(currentPage, totalPages) {
        const paginationContainer = document.getElementById('paginationContainer');
        if (!paginationContainer) return;
        
        paginationContainer.innerHTML = '';
        
        // No mostrar paginación si hay una sola página
        if (totalPages <= 1) return;
        
        const pagination = document.createElement('div');
        pagination.className = 'pagination';
        
        // Botón anterior
        const prevButton = document.createElement('button');
        prevButton.className = 'pagination-btn' + (currentPage === 1 ? ' disabled' : '');
        prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevButton.disabled = currentPage === 1;
        prevButton.addEventListener('click', () => showPage(currentPage - 1));
        pagination.appendChild(prevButton);
        
        // Determinar qué páginas mostrar
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        // Ajustar si estamos cerca del final
        if (endPage === totalPages) {
            startPage = Math.max(1, endPage - 4);
        }
        
        // Primera página siempre
        if (startPage > 1) {
            const firstBtn = document.createElement('button');
            firstBtn.className = 'pagination-btn';
            firstBtn.textContent = '1';
            firstBtn.addEventListener('click', () => showPage(1));
            pagination.appendChild(firstBtn);
            
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = '...';
                pagination.appendChild(ellipsis);
            }
        }
        
        // Botones de página
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn' + (i === currentPage ? ' active' : '');
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => showPage(i));
            pagination.appendChild(pageBtn);
        }
        
        // Última página siempre
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = '...';
                pagination.appendChild(ellipsis);
            }
            
            const lastBtn = document.createElement('button');
            lastBtn.className = 'pagination-btn';
            lastBtn.textContent = totalPages;
            lastBtn.addEventListener('click', () => showPage(totalPages));
            pagination.appendChild(lastBtn);
        }
        
        // Botón siguiente
        const nextButton = document.createElement('button');
        nextButton.className = 'pagination-btn' + (currentPage === totalPages ? ' disabled' : '');
        nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextButton.disabled = currentPage === totalPages;
        nextButton.addEventListener('click', () => showPage(currentPage + 1));
        pagination.appendChild(nextButton);
        
        // Mostrar información de paginación
        const paginationInfo = document.createElement('div');
        paginationInfo.className = 'pagination-info';
        paginationInfo.textContent = `Mostrando ${(currentPage - 1) * employeesPerPage + 1} a ${Math.min(currentPage * employeesPerPage, employees.length)} de ${employees.length} empleados`;
        
        // Agregar elementos a contenedor
        paginationContainer.appendChild(pagination);
        paginationContainer.appendChild(paginationInfo);
    }
    
    // Mostrar primera página
    showPage(1);
    
    // Buscar empleados al escribir
    const searchInput = document.getElementById('busqueda_empleado');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            if (searchTerm.length > 0) {
                // Filtrar empleados
                const filtered = employees.filter(emp => {
                    const codigo = (emp.codigo || emp.CODIGO || emp.ID_EMPLEADO || emp.id || '').toString().toLowerCase();
                    const nombre = (emp.nombre || emp.NOMBRE || '').toString().toLowerCase();
                    const apellido = (emp.apellido || emp.APELLIDO || '').toString().toLowerCase();
                    
                    return codigo.includes(searchTerm) || 
                           nombre.includes(searchTerm) || 
                           apellido.includes(searchTerm);
                });
                
                window.filteredEmployees = filtered;
                displayEmployeesWithPagination(filtered);
            } else {
                // Mostrar todos los empleados
                window.filteredEmployees = [...employees];
                displayEmployeesWithPagination(employees);
            }
        });
    }
    
    // Aplicar filtros
    const btnBuscarEmpleados = document.getElementById('btnBuscarEmpleados');
    if (btnBuscarEmpleados) {
        btnBuscarEmpleados.addEventListener('click', applyAdvancedFilters);
    }
    
    // Limpiar filtros
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', function() {
            // Limpiar campos
            const searchInput = document.getElementById('busqueda_empleado');
            if (searchInput) searchInput.value = '';
            
            const sedeSelect = document.getElementById('filtro_sede');
            if (sedeSelect) sedeSelect.value = '';
            
            const establecimientoSelect = document.getElementById('filtro_establecimiento');
            if (establecimientoSelect) establecimientoSelect.value = '';
            
            const estadoSelect = document.getElementById('filtro_estado');
            if (estadoSelect) estadoSelect.value = '';
            
            // Mostrar todos los empleados
            window.filteredEmployees = [...employees];
            displayEmployeesWithPagination(employees);
        });
    }
    
    // Función para aplicar filtros avanzados
    function applyAdvancedFilters() {
        const searchTerm = document.getElementById('busqueda_empleado')?.value.toLowerCase() || '';
        const sedeId = document.getElementById('filtro_sede')?.value || '';
        const establecimientoId = document.getElementById('filtro_establecimiento')?.value || '';
        const estado = document.getElementById('filtro_estado')?.value || '';
        
        // Filtrar empleados
        const filtered = employees.filter(emp => {
            // Filtro de búsqueda
            if (searchTerm) {
                const codigo = (emp.codigo || emp.CODIGO || emp.ID_EMPLEADO || emp.id || '').toString().toLowerCase();
                const nombre = (emp.nombre || emp.NOMBRE || '').toString().toLowerCase();
                const apellido = (emp.apellido || emp.APELLIDO || '').toString().toLowerCase();
                
                if (!codigo.includes(searchTerm) && 
                    !nombre.includes(searchTerm) && 
                    !apellido.includes(searchTerm)) {
                    return false;
                }
            }
            
            // Filtro de sede
            if (sedeId && emp.ID_SEDE && emp.ID_SEDE.toString() !== sedeId) {
                return false;
            }
            
            // Filtro de establecimiento
            if (establecimientoId && emp.ID_ESTABLECIMIENTO && emp.ID_ESTABLECIMIENTO.toString() !== establecimientoId) {
                return false;
            }
            
            // Filtro de estado biométrico
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
            
            return true;
        });
        
        window.filteredEmployees = filtered;
        displayEmployeesWithPagination(filtered);
    }
}

/**
 * Corregir modales y eventos
 */
function fixModals() {
    // Sobrescribir la función openEnrollmentModal para arreglar el modal
    window.openEnrollmentModalOriginal = window.openEnrollmentModal;
    
    window.openEnrollmentModal = function(employeeId) {
        console.log('📋 Abriendo modal mejorado para empleado ID:', employeeId);
        
        // Buscar el empleado por ID
        const employee = window.employeeData.find(emp => 
            emp.ID_EMPLEADO == employeeId || 
            emp.id_empleado == employeeId || 
            emp.id == employeeId || 
            emp.CODIGO == employeeId || 
            emp.codigo == employeeId);
        
        if (!employee) {
            console.error('Empleado no encontrado con ID:', employeeId);
            showNotification('Empleado no encontrado', 'error');
            return;
        }
        
        console.log('Datos del empleado encontrado:', employee);
        
        try {
            // Llenar datos del modal
            const modal = document.getElementById('biometricEnrollmentModal');
            const codeElement = document.getElementById('modal-employee-code');
            const nameElement = document.getElementById('modal-employee-name');
            const establishmentElement = document.getElementById('modal-employee-establishment');
            
            // Asegurarse que existen los elementos del modal
            if (!modal) {
                console.error('Error: Modal de enrolamiento no encontrado');
                showNotification('Error: El componente modal no está disponible', 'error');
                return;
            }
            
            // Codigo - Asegurar que siempre se muestre correctamente
            if (codeElement) {
                const codigo = employee.codigo || employee.CODIGO || employee.ID_EMPLEADO || employee.id;
                codeElement.textContent = codigo || employeeId;
                console.log('Código usado:', codigo);
            }
            
            // Nombre - Asegurar que siempre se muestre correctamente
            if (nameElement) {
                let nombreCompleto;
                
                if (employee.nombre && typeof employee.nombre === 'string' && employee.nombre.includes(' ')) {
                    nombreCompleto = employee.nombre;
                } else {
                    const firstName = employee.NOMBRE || employee.nombre || employee.NOMBRES || '';
                    const lastName = employee.APELLIDO || employee.apellido || employee.APELLIDOS || '';
                    nombreCompleto = `${firstName} ${lastName}`.trim();
                }
                
                nameElement.textContent = nombreCompleto || '-';
                console.log('Nombre usado:', nombreCompleto);
            }
            
            // Establecimiento - Asegurar que siempre se muestre correctamente
            if (establishmentElement) {
                const establecimiento = employee.ESTABLECIMIENTO || employee.establecimiento || 
                                       employee.nombre_establecimiento || employee.SEDE || employee.sede;
                establishmentElement.textContent = establecimiento || '-';
                console.log('Establecimiento usado:', establecimiento);
            }
            
            // Asegurar que los campos ocultos tengan el ID del empleado
            const hiddenFields = ['current-employee-id', 'employee_id', 'hidden_employee_id'];
            hiddenFields.forEach(id => {
                const field = document.getElementById(id);
                if (field) {
                    field.value = employee.ID_EMPLEADO || employee.id || employeeId;
                    console.log(`Campo ${id} actualizado con:`, field.value);
                }
            });
            
            // Mostrar el modal usando Bootstrap
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
        } catch (error) {
            console.error('Error al mostrar el modal:', error);
            showNotification('Error al mostrar el modal: ' + error.message, 'error');
            
            // Intentar método alternativo
            try {
                const modalElement = document.getElementById('biometricEnrollmentModal');
                if (modalElement) {
                    const bsModal = new bootstrap.Modal(modalElement);
                    bsModal.show();
                }
            } catch (altError) {
                console.error('Error en método alternativo:', altError);
                alert('No se pudo mostrar el modal. Por favor recargue la página.');
            }
        }
    };
}

/**
 * Corregir botón de diagnóstico
 */
function fixDiagnosticButton() {
    const btnDiagnostic = document.getElementById('btnDiagnostic');
    if (btnDiagnostic) {
        // Eliminar todos los event listeners existentes
        const newBtn = btnDiagnostic.cloneNode(true);
        btnDiagnostic.parentNode.replaceChild(newBtn, btnDiagnostic);
        
        // Añadir nuevo event listener
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Ejecutando diagnóstico...');
            
            // Cambiar apariencia del botón
            const originalContent = newBtn.innerHTML;
            newBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analizando...';
            newBtn.disabled = true;
            
            // Hacer diagnóstico básico del sistema
            Promise.all([
                fetch('api/get-sedes.php').then(r => r.ok ? { url: 'api/get-sedes.php', ok: true } : { url: 'api/get-sedes.php', ok: false }),
                fetch('api/get-establecimientos.php').then(r => r.ok ? { url: 'api/get-establecimientos.php', ok: true } : { url: 'api/get-establecimientos.php', ok: false }),
                fetch('api/biometric/direct-employees.php').then(r => r.ok ? { url: 'api/biometric/direct-employees.php', ok: true } : { url: 'api/biometric/direct-employees.php', ok: false })
            ]).then(results => {
                // Restaurar botón
                newBtn.innerHTML = originalContent;
                newBtn.disabled = false;
                // Restaurar botón
                newBtn.innerHTML = originalContent;
                newBtn.disabled = false;
                
                // Contar errores
                const errors = results.filter(r => !r.ok).length;
                
                // Mostrar resultados
                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.id = 'diagnosticResultsModal';
                modal.setAttribute('tabindex', '-1');
                modal.setAttribute('aria-hidden', 'true');
                
                let resultsHtml = '';
                results.forEach(result => {
                    resultsHtml += `
                        <div class="card mb-3 ${result.ok ? 'border-success' : 'border-danger'}">
                            <div class="card-header ${result.ok ? 'bg-success text-white' : 'bg-danger text-white'}">
                                ${result.url}
                            </div>
                            <div class="card-body">
                                ${result.ok ? 
                                    '<p class="text-success"><i class="fas fa-check-circle"></i> Funcionando correctamente</p>' : 
                                    '<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error en el endpoint</p>'
                                }
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
                                <div class="alert ${errors === 0 ? 'alert-success' : 'alert-warning'}">
                                    <i class="fas fa-info-circle"></i>
                                    ${errors === 0 ? 'Todos los sistemas funcionan correctamente' : `Se encontraron ${errors} problemas`}
                                </div>
                                ${resultsHtml}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary" onclick="window.location.reload()">Recargar Página</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Mostrar modal
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
                
                // Eliminar modal al cerrarlo
                modal.addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(modal);
                });
                
            }).catch(error => {
                console.error('Error durante diagnóstico:', error);
                newBtn.innerHTML = originalContent;
                newBtn.disabled = false;
                showNotification('Error durante el diagnóstico: ' + error.message, 'error');
            });
        });
        
        console.log('✅ Botón de diagnóstico arreglado');
    }
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

// Exponer funciones útiles globalmente
window.fixBiometricSystem = fixBiometricSystem;
window.fixEmployeeDisplay = fixEmployeeDisplay;
window.fixModals = fixModals;
window.fixDiagnosticButton = fixDiagnosticButton;
window.showNotification = showNotification;
