/**
 * Override para asegurar que biometric-enrollment.js use solo datos reales
 * Este script anula las funciones problemáticas que cargan datos de prueba
 */

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Aplicando override para datos reales solamente...');
    
    // Esperar un poco para que otros scripts se carguen
    setTimeout(function() {
        overrideDataLoading();
        loadSedesYEstablecimientos(); // Cargar sedes y establecimientos
        // Ejecutar múltiples veces para asegurar override
        setTimeout(overrideDataLoading, 2000);
        setTimeout(overrideDataLoading, 5000);
    }, 500);
});

/**
 * Cargar sedes y establecimientos para los filtros
 */
function loadSedesYEstablecimientos() {
    console.log('🏢 Cargando sedes y establecimientos...');
    
    // Cargar sedes
    fetch('api/get-sedes.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sedes) {
                const sedeSelect = document.getElementById('filtro_sede');
                if (sedeSelect) {
                    sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';
                    data.sedes.forEach(sede => {
                        const option = document.createElement('option');
                        option.value = sede.ID_SEDE;
                        option.textContent = sede.NOMBRE;
                        sedeSelect.appendChild(option);
                    });
                    
                    console.log(`✅ ${data.sedes.length} sedes cargadas`);
                    
                    // Configurar evento change para cargar establecimientos
                    sedeSelect.addEventListener('change', function() {
                        loadEstablecimientos(this.value);
                    });
                } else {
                    console.warn('⚠️ Selector de sedes no encontrado');
                }
            } else {
                console.error('❌ Error al cargar sedes:', data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('❌ Error en solicitud de sedes:', error);
        });
        
    // Cargar todos los establecimientos inicialmente
    loadEstablecimientos();
}

/**
 * Cargar establecimientos basados en la sede seleccionada
 */
function loadEstablecimientos(sedeId = '') {
    const url = sedeId ? `api/get-establecimientos.php?sede_id=${sedeId}` : 'api/get-establecimientos.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.establecimientos) {
                const establecimientoSelect = document.getElementById('filtro_establecimiento');
                if (establecimientoSelect) {
                    establecimientoSelect.innerHTML = '<option value="">Todos los establecimientos</option>';
                    data.establecimientos.forEach(est => {
                        const option = document.createElement('option');
                        option.value = est.ID_ESTABLECIMIENTO;
                        option.textContent = est.NOMBRE;
                        establecimientoSelect.appendChild(option);
                    });
                    
                    console.log(`✅ ${data.establecimientos.length} establecimientos cargados`);
                } else {
                    console.warn('⚠️ Selector de establecimientos no encontrado');
                }
            } else {
                console.error('❌ Error al cargar establecimientos:', data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('❌ Error en solicitud de establecimientos:', error);
        });
}

/**
 * Anular las funciones de carga de datos para usar solo la API principal
 */
function overrideDataLoading() {
    // Anular la función loadEmployeeData si existe
    if (typeof window.loadEmployeeData === 'function') {
        console.log('🔧 Anulando loadEmployeeData para usar solo datos reales');
        
        window.loadEmployeeData = async function() {
            console.log('🚨 DEBUG: loadEmployeeData fue llamada');
            try {
                console.log('🔄 Cargando empleados usando SOLO la API principal...');
                
                // Construir parámetros
                const params = new URLSearchParams();
                
                // Añadir filtros actuales si existen
                const elements = {
                    busquedaEmpleado: document.getElementById('busqueda_empleado'),
                    filtroSede: document.getElementById('filtro_sede'),
                    filtroEstablecimiento: document.getElementById('filtro_establecimiento'),
                    filtroEstado: document.getElementById('filtro_estado')
                };
                
                if (elements.busquedaEmpleado && elements.busquedaEmpleado.value.trim()) {
                    params.append('busqueda', elements.busquedaEmpleado.value.trim());
                }
                if (elements.filtroSede && elements.filtroSede.value) {
                    params.append('sede', elements.filtroSede.value);
                }
                if (elements.filtroEstablecimiento && elements.filtroEstablecimiento.value) {
                    params.append('establecimiento', elements.filtroEstablecimiento.value);
                }
                if (elements.filtroEstado && elements.filtroEstado.value) {
                    params.append('estado', elements.filtroEstado.value);
                }
                
                // Añadir paginación
                const currentPage = window.currentPage || 1;
                const employeesPerPage = window.employeesPerPage || 10;
                params.append('page', currentPage);
                params.append('limit', employeesPerPage);
                
                // Añadir timestamp para evitar caché
                params.append('_t', Date.now());
                
                console.log('Parámetros de la solicitud:', params.toString());
                
                // Usar el endpoint ESPECÍFICO para inscripción biométrica
                const response = await fetch(`api/biometric/enrollment-employees.php?${params.toString()}`);
                
                if (!response.ok) {
                    throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                }
                
                const jsonData = await response.json();
                console.log('Datos recibidos del endpoint de inscripción:', jsonData);
                
                // Actualizar contadores de estadísticas
                updateBiometricStats(jsonData);
                
                if (jsonData && jsonData.success && jsonData.data) {
                    // Actualizar variables globales
                    if (typeof window.employeeData !== 'undefined') {
                        window.employeeData = jsonData.data;
                    }
                    if (typeof window.filteredEmployees !== 'undefined') {
                        window.filteredEmployees = [...jsonData.data];
                    }
                    
                    // Actualizar información de paginación si está disponible
                    if (jsonData.pagination && typeof window.updatePaginationInfo === 'function') {
                        window.updatePaginationInfo(jsonData.pagination);
                    } else if (jsonData.pagination) {
                        // Implementar paginación si no existe la función
                        setupPaginationControls(jsonData.pagination);
                    }
                    
                    console.log(`✅ Cargados ${jsonData.data.length} empleados reales`);
                    
                    // Actualizar interfaz si las funciones existen
                    if (typeof window.updateStatistics === 'function') {
                        window.updateStatistics();
                    }
                    if (typeof window.displayEmployees === 'function') {
                        window.displayEmployees();
                    }
                    
                    return jsonData.data;
                } else {
                    throw new Error(jsonData.message || 'Error: La API no devolvió datos válidos');
                }
                
            } catch (error) {
                console.error('❌ Error al cargar empleados:', error);
                
                // Mostrar error en la interfaz
                if (typeof window.showNotification === 'function') {
                    window.showNotification('Error al cargar empleados: ' + error.message, 'error');
                }
                
                // Mostrar mensaje de error en la tabla
                const tableBody = document.getElementById('employeeTableBody');
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="error-indicator">
                                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                                    <h5>Error al cargar empleados</h5>
                                    <p class="text-muted">${error.message}</p>
                                    <button class="btn btn-primary mt-2" onclick="window.location.reload()">
                                        <i class="fas fa-sync-alt"></i> Recargar página
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                
                throw error;
            }
        };
        
        console.log('✅ Override aplicado exitosamente');
        
        // Recargar datos inmediatamente
        try {
            window.loadEmployeeData();
        } catch (e) {
            console.error('Error al recargar datos:', e);
        }
    } else {
        console.warn('⚠️ Función loadEmployeeData no encontrada');
    }
    
    // También anular fetch para interceptar llamadas a APIs no deseadas
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        // Si es una llamada a APIs de empleados que no sean la específica para inscripción, redirigir
        if (typeof url === 'string') {
            if (url.includes('api/employee/list.php') || 
                url.includes('api/biometric/get-employees.php') || 
                url.includes('api/biometric/mock-employees.php') ||
                url.includes('api/biometric/direct-employees.php') ||
                url.includes('api/test/simple-employees.php')) {
                
                console.warn('🚫 Bloqueando llamada a API:', url);
                console.log('💡 Redirigiendo al endpoint específico de inscripción...');
                
                // Redirigir al endpoint específico para inscripción biométrica
                const newUrl = url.replace(/api\/(employee\/list|biometric\/(get-employees|mock-employees|direct-employees)|test\/simple-employees)\.php/, 'api/biometric/enrollment-employees.php');
                return originalFetch(newUrl, options);
            }
        }
        
        // Para otras llamadas, usar fetch original
        return originalFetch(url, options);
    };
    
    console.log('🔧 Override del fetch aplicado para bloquear APIs alternativas');
    
    // Monitorear cambios en employeeData cada segundo
    setInterval(function() {
        if (window.employeeData && window.employeeData.length > 0) {
            // Verificar si los datos son reales (deberían tener 41 empleados)
            if (window.employeeData.length !== 41) {
                console.warn('⚠️ Detectados datos incorrectos, recargando desde API principal...');
                window.loadEmployeeData();
            }
            
            // Verificar si hay empleados con nombres simples (datos de prueba)
            const testNames = ['Paula', 'Andrés', 'Valentina', 'María', 'Ricardo', 'Sofía', 'Carlos', 'Ana'];
            const hasTestData = window.employeeData.some(emp => 
                testNames.includes(emp.NOMBRE) && testNames.includes(emp.APELLIDO)
            );
            
            if (hasTestData) {
                console.warn('⚠️ Detectados datos de prueba, recargando desde API principal...');
                window.loadEmployeeData();
            }
        }
    }, 3000);
    
    // Inicializar eventos para filtros y botón de búsqueda
    initializeFilterEvents();
}

/**
 * Inicializa los eventos para los filtros y botón de búsqueda
 */
function initializeFilterEvents() {
    console.log('🔍 Inicializando eventos para filtros y búsqueda...');
    
    try {
        // Botón de búsqueda
        const searchButton = document.getElementById('btnBuscarEmpleados');
        if (searchButton) {
            // Usar addEventListener como método principal
            searchButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('🔍 Botón de búsqueda clickeado');
                
                // Reiniciar paginación
                if (typeof window.currentPage !== 'undefined') {
                    window.currentPage = 1;
                }
                
                // Cargar datos
                if (typeof window.loadEmployeeData === 'function') {
                    window.loadEmployeeData();
                } else {
                    console.error('❌ Función loadEmployeeData no está disponible');
                }
                
                return false;
            });
            
            // También configurar con método onclick por si falla addEventListener
            searchButton.onclick = function() {
                console.log('🔍 Botón de búsqueda clickeado (onclick)');
                
                // Reiniciar paginación
                if (typeof window.currentPage !== 'undefined') {
                    window.currentPage = 1;
                }
                
                // Cargar datos
                if (typeof window.loadEmployeeData === 'function') {
                    window.loadEmployeeData();
                    return false;
                }
            };
            
            console.log('✅ Botón de búsqueda configurado');
        } else {
            console.warn('⚠️ Botón de búsqueda no encontrado');
        }
        
        // Campo de búsqueda (tecla Enter)
        const searchField = document.getElementById('busqueda_empleado');
        if (searchField) {
            searchField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    console.log('⌨️ Tecla Enter presionada en campo de búsqueda');
                    
                    // Simular clic en el botón de búsqueda
                    const searchBtn = document.getElementById('btnBuscarEmpleados');
                    if (searchBtn) {
                        searchBtn.click();
                    }
                    
                    return false;
                }
            });
            
            console.log('✅ Campo de búsqueda configurado para tecla Enter');
        } else {
            console.warn('⚠️ Campo de búsqueda no encontrado');
        }
        
        // Botón de limpiar
        const clearButton = document.getElementById('btnLimpiarFiltros');
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                console.log('🧹 Limpiando filtros...');
                
                // Limpiar campos
                if (searchField) searchField.value = '';
                
                const sedeSelect = document.getElementById('filtro_sede');
                if (sedeSelect) sedeSelect.value = '';
                
                const establecimientoSelect = document.getElementById('filtro_establecimiento');
                if (establecimientoSelect) establecimientoSelect.value = '';
                
                const estadoSelect = document.getElementById('filtro_estado');
                if (estadoSelect) estadoSelect.value = '';
                
                // Reiniciar paginación
                if (typeof window.currentPage !== 'undefined') {
                    window.currentPage = 1;
                }
                
                // Recargar datos
                if (typeof window.loadEmployeeData === 'function') {
                    window.loadEmployeeData();
                }
            });
            
            console.log('✅ Botón de limpiar configurado');
        } else {
            console.warn('⚠️ Botón de limpiar no encontrado');
        }
    } catch (error) {
        console.error('❌ Error al inicializar eventos:', error);
    }
}

/**
 * Configurar controles de paginación AJAX
 */
function setupPaginationControls(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) {
        console.error('❌ Contenedor de paginación no encontrado');
        return;
    }
    
    console.log('📊 Configurando paginación con:', pagination);
    
    const { current_page, total_pages, has_prev, has_next, total } = pagination;
    
    // Actualizar variables globales
    window.currentPage = current_page;
    window.totalPages = total_pages;
    window.totalEmployees = total;
    
    // Actualizar contador en el texto "Mostrando X de Y empleados"
    const employeeCounter = document.getElementById('employeeCounter');
    if (employeeCounter) {
        employeeCounter.textContent = `Mostrando ${Math.min(total, window.employeesPerPage)} de ${total} empleados`;
    }
    
    // Si solo hay una página, ocultar paginación
    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let paginationHTML = '<nav aria-label="Paginación de empleados"><ul class="pagination pagination-sm justify-content-center">';
    
    // Botón primera página
    paginationHTML += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="${current_page > 1 ? 'changePage(1)' : ''};return false;" aria-label="Primera">
            <i class="fas fa-angle-double-left"></i>
        </a>
    </li>`;
    
    // Botón anterior
    paginationHTML += `<li class="page-item ${!has_prev ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="${has_prev ? 'changePage(' + (current_page - 1) + ')' : ''};return false;" aria-label="Anterior">
            <i class="fas fa-angle-left"></i>
        </a>
    </li>`;
    
    // Números de página
    const startPage = Math.max(1, current_page - 2);
    const endPage = Math.min(total_pages, current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current_page) {
            paginationHTML += `<li class="page-item active">
                <span class="page-link">${i}</span>
            </li>`;
        } else {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${i});return false;">${i}</a>
            </li>`;
        }
    }
    
    // Botón siguiente
    paginationHTML += `<li class="page-item ${!has_next ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="${has_next ? 'changePage(' + (current_page + 1) + ')' : ''};return false;" aria-label="Siguiente">
            <i class="fas fa-angle-right"></i>
        </a>
    </li>`;
    
    // Botón última página
    paginationHTML += `<li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="${current_page < total_pages ? 'changePage(' + total_pages + ')' : ''};return false;" aria-label="Última">
            <i class="fas fa-angle-double-right"></i>
        </a>
    </li>`;
    
    paginationHTML += '</ul></nav>';
    
    // Agregar información de resultados
    paginationHTML += `<div class="pagination-info text-center mt-2">
        <small class="text-muted">
            Página ${current_page} de ${total_pages} (${total} empleados total)
        </small>
    </div>`;
    
    container.innerHTML = paginationHTML;
    
    console.log('✅ Controles de paginación configurados');
}

/**
 * Cambiar página (función global para paginación AJAX)
 */
window.changePage = function(page) {
    console.log('📄 Cambiando a página:', page);
    if (page < 1 || (window.totalPages && page > window.totalPages)) {
        console.warn('⚠️ Página fuera de rango:', page);
        return;
    }
    
    window.currentPage = page;
    
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
                        <p class="mt-2">Cargando página ${page}...</p>
                    </div>
                </td>
            </tr>
        `;
    }
    
    // Recargar datos
    if (typeof window.loadEmployeeData === 'function') {
        window.loadEmployeeData();
    } else {
        console.error('Función loadEmployeeData no disponible');
    }
};

/**
 * Abrir modal de enrolamiento biométrico
 */
window.openEnrollmentModal = function(employeeId) {
    console.log('🔍 Abriendo modal de enrolamiento para empleado:', employeeId);
    
    try {
        // Buscar el empleado en los datos actuales
        const employee = window.employeeData?.find(emp => 
            emp.ID_EMPLEADO == employeeId || emp.id == employeeId || emp.codigo == employeeId
        );
        
        if (!employee) {
            console.error('❌ Empleado no encontrado:', employeeId);
            alert('Error: Empleado no encontrado. Por favor, recarga la página e intenta de nuevo.');
            return;
        }
        
        console.log('✅ Datos del empleado encontrados:', employee);
        
        // Buscar el modal
        const modal = document.getElementById('biometricEnrollmentModal');
        if (!modal) {
            console.error('❌ Modal de enrolamiento no encontrado en el DOM');
            alert('Error: El modal de enrolamiento no está disponible. Por favor, recarga la página.');
            return;
        }
        
        console.log('✅ Modal encontrado, configurando datos');
        
        // Llenar información del empleado en el modal
        const idDisplay = document.getElementById('modal-employee-code');
        const nameDisplay = document.getElementById('modal-employee-name');
        const establishmentDisplay = document.getElementById('modal-employee-establishment');
        
        if (idDisplay) idDisplay.textContent = employee.codigo || employee.ID_EMPLEADO || '-';
        if (nameDisplay) {
            nameDisplay.textContent = (employee.NOMBRE && employee.APELLIDO) 
                ? `${employee.NOMBRE} ${employee.APELLIDO}` 
                : (employee.nombre || '-');
        }
        if (establishmentDisplay) {
            establishmentDisplay.textContent = employee.establecimiento || employee.ESTABLECIMIENTO || '-';
        }
        
        // Configurar IDs ocultos para el formulario - IMPORTANTE PARA QUE FUNCIONE BIOMETRIC-BLAZEFACE.JS
        const hiddenIds = ['current-employee-id', 'hidden_employee_id', 'employee_id'];
        hiddenIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = employee.ID_EMPLEADO || employee.id || employee.codigo || '';
                console.log(`✅ Campo oculto ${id} establecido a: ${element.value}`);
            }
        });
        
        // Actualizar estado de enrolamiento
        const facialStatus = document.getElementById('facial-status');
        const fingerprintStatus = document.getElementById('fingerprint-status');
        
        if (facialStatus) {
            facialStatus.className = employee.facial_enrolled ? 'badge bg-success' : 'badge bg-secondary';
            facialStatus.textContent = employee.facial_enrolled ? 'Inscrito' : 'Pendiente';
        }
        
        if (fingerprintStatus) {
            fingerprintStatus.className = employee.fingerprint_enrolled ? 'badge bg-success' : 'badge bg-secondary';
            fingerprintStatus.textContent = employee.fingerprint_enrolled ? 'Inscrito' : 'Pendiente';
        }
        
        console.log('✅ Información del empleado configurada en el modal');
        
        // Asegurar que Bootstrap esté disponible
        if (typeof bootstrap === 'undefined') {
            console.error('❌ Bootstrap no está disponible');
            alert('Error: Bootstrap no está cargado correctamente. Intenta recargar la página.');
            // Intentar cargar bootstrap si no está disponible
            const bootstrapScript = document.createElement('script');
            bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
            document.head.appendChild(bootstrapScript);
            setTimeout(() => {
                if (typeof bootstrap !== 'undefined') {
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                } else {
                    alert('Error: No se pudo cargar Bootstrap. Por favor, recarga la página.');
                }
            }, 1000);
            return;
        }
        
        // Abrir el modal usando Bootstrap 5
        try {
            const myModal = new bootstrap.Modal(modal);
            myModal.show();
            console.log('✅ Modal abierto exitosamente usando bootstrap.Modal');
            
            // IMPORTANTE: Configurar los botones del modal después de abrirlo
            setTimeout(() => {
                setupBiometricFunctionality();
            }, 500);
            
        } catch (modalError) {
            console.error('❌ Error al abrir el modal con bootstrap.Modal:', modalError);
            
            // Backup: abrir usando jQuery si está disponible
            if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
                console.log('⚠️ Intentando abrir modal con jQuery como fallback...');
                try {
                    $(modal).modal('show');
                    console.log('✅ Modal abierto con jQuery');
                    
                    // IMPORTANTE: Configurar los botones del modal después de abrirlo
                    setTimeout(() => {
                        setupBiometricFunctionality();
                    }, 500);
                    
                } catch (jqError) {
                    console.error('❌ Error al abrir con jQuery:', jqError);
                    alert('Error al abrir el modal: ' + modalError.message);
                }
            } else {
                alert('Error al abrir el modal: ' + modalError.message);
            }
        }
    } catch (error) {
        console.error('❌ Error general al procesar modal:', error);
        alert('Error al abrir el modal: ' + error.message);
    }
};

/**
 * Configuración e integración del sistema biométrico con BlazeFace
 */
function setupBiometricFunctionality() {
    console.log('⚙️ Configurando funcionalidad biométrica...');
    
    // 1. Primero aseguramos que estén cargados los scripts necesarios
    if (typeof startFaceCamera !== 'function') {
        console.log('⚠️ La función startFaceCamera no está disponible, cargando biometric-blazeface.js...');
        
        // Verificar si ya está cargado el script
        let scriptLoaded = false;
        document.querySelectorAll('script').forEach(script => {
            if (script.src && script.src.includes('biometric-blazeface.js')) {
                scriptLoaded = true;
            }
        });
        
        if (!scriptLoaded) {
            // Cargar el script biometric-blazeface.js dinámicamente
            const script = document.createElement('script');
            script.src = 'assets/js/biometric-blazeface.js';
            script.onload = function() {
                console.log('✅ biometric-blazeface.js cargado correctamente');
                configureButtonEvents();
            };
            script.onerror = function() {
                console.error('❌ Error al cargar biometric-blazeface.js');
                alert('Error al cargar el sistema de reconocimiento facial. Por favor, recarga la página.');
            };
            document.head.appendChild(script);
        } else {
            // Si ya está cargado pero no disponible, esperar un momento
            setTimeout(configureButtonEvents, 1000);
        }
    } else {
        // Si la función ya está disponible, configurar eventos directamente
        configureButtonEvents();
    }
    
    function configureButtonEvents() {
        try {
            // Configurar botón para iniciar cámara
            const startCameraBtn = document.getElementById('startFaceCamera');
            const stopCameraBtn = document.getElementById('stopFaceCamera');
            
            if (startCameraBtn) {
                console.log('✅ Configurando botón startFaceCamera');
                startCameraBtn.onclick = function() {
                    console.log('▶️ Ejecutando startFaceCamera()');
                    if (typeof startFaceCamera === 'function') {
                        startFaceCamera();
                    } else {
                        console.error('❌ La función startFaceCamera no está disponible');
                        alert('Error: Sistema de reconocimiento facial no disponible. Recarga la página.');
                    }
                };
            }
            
            // Configurar botón para detener cámara
            if (stopCameraBtn) {
                console.log('✅ Configurando botón stopFaceCamera');
                stopCameraBtn.onclick = function() {
                    console.log('⏹️ Ejecutando stopFaceCamera()');
                    if (typeof stopFaceCamera === 'function') {
                        stopFaceCamera();
                    } else {
                        console.error('❌ La función stopFaceCamera no está disponible');
                        alert('Error: Sistema de reconocimiento facial no disponible. Recarga la página.');
                    }
                };
            }
            
            // Configurar tabs de navegación para pestañas
            const facialTab = document.getElementById('facial-tab');
            const fingerprintTab = document.getElementById('fingerprint-tab');
            
            if (facialTab) {
                facialTab.addEventListener('click', function() {
                    console.log('👁️ Tab facial activado');
                });
            }
            
            if (fingerprintTab) {
                fingerprintTab.addEventListener('click', function() {
                    console.log('👆 Tab de huella dactilar activado');
                });
            }
            
            // Configurar botón de guardar enrolamiento si existe
            const saveButton = document.getElementById('saveEnrollment');
            if (saveButton) {
                saveButton.addEventListener('click', function() {
                    if (typeof saveEnrollment === 'function') {
                        saveEnrollment();
                    } else {
                        alert('La funcionalidad de guardado está en desarrollo.');
                    }
                });
            }
            
            // Verificar si TensorFlow.js y BlazeFace están cargados
            if (typeof tf !== 'undefined' && typeof blazeface !== 'undefined') {
                console.log('✅ TensorFlow.js y BlazeFace están cargados correctamente');
            } else {
                console.warn('⚠️ TensorFlow.js o BlazeFace no están cargados');
                
                // Intentar cargar si no están disponibles
                if (typeof tf === 'undefined') {
                    const tfScript = document.createElement('script');
                    tfScript.src = 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.11.0/dist/tf.min.js';
                    document.head.appendChild(tfScript);
                }
                
                if (typeof blazeface === 'undefined') {
                    const blazefaceScript = document.createElement('script');
                    blazefaceScript.src = 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js';
                    document.head.appendChild(blazefaceScript);
                }
            }
            
            console.log('✅ Eventos del modal configurados correctamente');
        } catch (error) {
            console.error('❌ Error al configurar eventos del modal:', error);
        }
    }
}

/**
 * Ver historial de enrolamiento
 */
window.viewEnrollmentHistory = function(employeeId) {
    console.log('Ver historial para empleado:', employeeId);
    
    try {
        // Buscar el empleado en los datos actuales
        const employee = window.employeeData?.find(emp => 
            emp.ID_EMPLEADO == employeeId || emp.id == employeeId || emp.codigo == employeeId
        );
        
        if (!employee) {
            console.error('❌ Empleado no encontrado:', employeeId);
            alert('Error: Empleado no encontrado. Por favor, recarga la página e intenta de nuevo.');
            return;
        }
        
        // Mostrar información básica por ahora
        const nombreCompleto = (employee.NOMBRE && employee.APELLIDO) 
            ? `${employee.NOMBRE} ${employee.APELLIDO}` 
            : (employee.nombre || '-');
            
        alert(`Historial de enrolamiento para: ${nombreCompleto}\n\nID: ${employeeId}\n\nEstado Facial: ${employee.facial_enrolled ? 'Inscrito' : 'Pendiente'}\nEstado Huella: ${employee.fingerprint_enrolled ? 'Inscrito' : 'Pendiente'}\n\nFunción completa en desarrollo.`);
    } catch (error) {
        console.error('Error al mostrar historial:', error);
        alert('Error al mostrar historial: ' + error.message);
    }
};

/**
 * Inicializar eventos para filtros y botones
 */
function initializeFilterEvents() {
    console.log('🔄 Inicializando eventos de filtros y botones...');
    
    try {
        // Botón de búsqueda - Implementación directa
        const searchButton = document.getElementById('btnBuscarEmpleados');
        if (searchButton) {
            console.log('✅ Configurando botón de búsqueda');
            
            // Remover todos los event listeners previos (clonando el botón)
            const newSearchButton = searchButton.cloneNode(true);
            searchButton.parentNode.replaceChild(newSearchButton, searchButton);
            
            // Añadir el event listener directamente
            newSearchButton.onclick = function() {
                console.log('🔍 CLICK EN BOTÓN BUSCAR - Ejecutando búsqueda...');
                
                // Efecto visual para confirmar la acción
                this.style.backgroundColor = '#28a745';
                setTimeout(() => {
                    this.style.backgroundColor = '';
                }, 300);
                
                window.currentPage = 1; // Reiniciar a página 1
                
                // Llamar a loadEmployeeData directamente como función global
                window.loadEmployeeData();
            };
        } else {
            console.error('⚠️ BOTÓN DE BÚSQUEDA NO ENCONTRADO - Elemento #btnBuscarEmpleados no existe en el DOM');
        }
        
        // Campo de búsqueda - Evento Enter
        const searchInputField = document.getElementById('busqueda_empleado');
        if (searchInputField) {
            console.log('✅ Configurando evento Enter para campo de búsqueda');
            searchInputField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Evitar envío del formulario
                    console.log('🔍 Búsqueda mediante Enter...');
                    
                    // Efecto visual para mostrar que se activó la búsqueda
                    const searchBtn = document.getElementById('btnBuscarEmpleados');
                    if (searchBtn) {
                        // Simular clic en el botón de búsqueda
                        searchBtn.click();
                    } else {
                        // Si no se encuentra el botón, ejecutar búsqueda directamente
                        window.currentPage = 1;
                        window.loadEmployeeData();
                    }
                }
            });
        }
        
        // Botón de limpiar filtros
        const clearButton = document.getElementById('btnLimpiarFiltros');
        if (clearButton) {
            console.log('✅ Configurando botón de limpiar filtros');
            clearButton.addEventListener('click', function() {
                console.log('🧹 Limpiando filtros...');
                
                // Limpiar campo de búsqueda
                const searchInput = document.getElementById('busqueda_empleado');
                if (searchInput) searchInput.value = '';
                
                // Resetear selectores
                const selectors = ['filtro_sede', 'filtro_establecimiento', 'filtro_estado'];
                selectors.forEach(id => {
                    const select = document.getElementById(id);
                    if (select) select.value = '';
                });
                
                // Recargar datos
                window.currentPage = 1;
                window.loadEmployeeData();
            });
        } else {
            console.warn('⚠️ Botón de limpiar filtros no encontrado');
        }
        
        // Botón de actualizar
        const refreshButton = document.getElementById('btnRefreshStats');
        if (refreshButton) {
            console.log('✅ Configurando botón de actualizar');
            refreshButton.addEventListener('click', function() {
                console.log('🔄 Actualizando datos...');
                window.loadEmployeeData();
                alert('Datos actualizados correctamente');
            });
        } else {
            console.warn('⚠️ Botón de actualizar no encontrado');
        }
        
        // Configurar filtros automáticos para los selectores
        const selectors = ['filtro_sede', 'filtro_establecimiento', 'filtro_estado'];
        selectors.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                console.log(`✅ Configurando cambio automático para ${id}`);
                select.addEventListener('change', function() {
                    console.log(`🔄 Filtro ${id} cambió a ${select.value}`);
                    window.currentPage = 1; // Reiniciar a página 1
                    window.loadEmployeeData();
                });
            }
        });
        
        console.log('✅ Eventos de filtros y botones configurados correctamente');
    } catch (error) {
        console.error('❌ Error al inicializar eventos:', error);
    }
}

/**
 * Función para mostrar empleados en la tabla
 */
window.displayEmployees = function(employees) {
    console.log('📋 Mostrando empleados en la tabla...');
    
    const tableBody = document.getElementById('employeeTableBody');
    if (!tableBody) {
        console.error('❌ Tabla de empleados no encontrada');
        return;
    }
    
    // Si no se pasan empleados, usar los datos globales
    if (!employees && window.employeeData) {
        employees = window.employeeData;
    }
    
    // Si no hay datos, mostrar mensaje
    if (!employees || employees.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                    <h5>No se encontraron empleados</h5>
                </td>
            </tr>
        `;
        return;
    }
    
    // Generar HTML de la tabla
    tableBody.innerHTML = employees.map(employee => {
        let statusClass = 'badge bg-secondary';
        let statusText = 'Pendiente';
        
        if (employee.facial_enrolled && employee.fingerprint_enrolled) {
            statusClass = 'badge bg-success';
            statusText = 'Inscrito';
        } else if (employee.facial_enrolled || employee.fingerprint_enrolled) {
            statusClass = 'badge bg-warning';
            statusText = 'Parcial';
        }
        
        const employeeId = employee.ID_EMPLEADO || employee.id || employee.codigo;
        
        return `
            <tr>
                <td><strong>${employee.codigo || employee.ID_EMPLEADO || '-'}</strong></td>
                <td>${(employee.NOMBRE && employee.APELLIDO) ? 
                    `${employee.NOMBRE} ${employee.APELLIDO}` : 
                    (employee.nombre || '-')}</td>
                <td>${employee.establecimiento || employee.ESTABLECIMIENTO || '-'}</td>
                <td><span class="${statusClass}">${statusText}</span></td>
                <td>
                    <i class="fas fa-circle ${employee.facial_enrolled ? 'text-success' : 'text-secondary'}"></i>
                    ${employee.facial_enrolled ? 'Registrado' : 'Pendiente'}
                </td>
                <td>
                    <i class="fas fa-circle ${employee.fingerprint_enrolled ? 'text-success' : 'text-secondary'}"></i>
                    ${employee.fingerprint_enrolled ? 'Registrado' : 'Pendiente'}
                </td>
                <td>${employee.last_updated && employee.last_updated !== '1970-01-01' ? employee.last_updated : '-'}</td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm" 
                            onclick="openEnrollmentModal(${employeeId})"
                            title="Enrolar empleado">
                        <i class="fas fa-fingerprint"></i>
                    </button>
                    <button type="button" class="btn btn-info btn-sm" 
                            onclick="viewEnrollmentHistory(${employeeId})"
                            title="Ver historial">
                        <i class="fas fa-history"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    console.log(`✅ Mostrando ${employees.length} empleados`);
};

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando módulo de inscripción biométrica...');
    
    // Esperar a que todo el DOM esté listo y luego inicializar eventos
    setTimeout(function() {
        console.log('🔄 DOM completamente cargado, inicializando eventos...');
        
        // Inicializar eventos primero, para que estén listos cuando carguemos los datos
        initializeFilterEvents();
        
        // Verificar el botón de búsqueda de forma directa
        const searchBtn = document.getElementById('btnBuscarEmpleados');
        if (searchBtn) {
            console.log('✅ Verificación adicional del botón de búsqueda');
            searchBtn.onclick = function() {
                console.log('🔍 CLICK DIRECTO en botón buscar');
                window.currentPage = 1;
                if (typeof window.loadEmployeeData === 'function') {
                    window.loadEmployeeData();
                }
            };
        }
        
        // Cargar datos iniciales después de un momento
        setTimeout(function() {
            // Verificar si la función loadEmployeeData está disponible
            if (typeof window.loadEmployeeData === 'function') {
                window.loadEmployeeData();
            } else {
                console.error('❌ La función loadEmployeeData no está disponible');
            }
        }, 1500);
    }, 800);
    
    // Cargar datos iniciales después de un momento
    setTimeout(function() {
        // Verificar si la función loadEmployeeData está disponible
        if (typeof window.loadEmployeeData === 'function') {
            window.loadEmployeeData();
        } else {
            console.error('❌ La función loadEmployeeData no está disponible');
        }
    }, 1500);
});

console.log('🔧 Script de override para datos reales cargado correctamente');
