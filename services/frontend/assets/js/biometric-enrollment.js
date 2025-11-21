/**
 * SISTEMA DE ENROLAMIENTO BIOMÉTRICO
 * JavaScript para la página de inscripción biométrica
 * Integrado con el diseño del sistema SynkTime
 */

// Variables globales
let employeeData = [];
let filteredEmployees = [];
let currentPage = 1;
let employeesPerPage = 10;
let totalEmployees = 0;
let totalPages = 0;
let currentModal = null;

// Elementos del DOM
const elements = {
    totalEmployees: document.getElementById('totalEmployees'),
    enrolledCount: document.getElementById('enrolledCount'),
    pendingCount: document.getElementById('pendingCount'),
    enrollmentPercentage: document.getElementById('enrollmentPercentage'),
    employeeTableBody: document.getElementById('employeeTableBody'),
    paginationContainer: document.getElementById('paginationContainer'),
    filtroSede: document.getElementById('filtro_sede'),
    filtroEstablecimiento: document.getElementById('filtro_establecimiento'),
    filtroEstado: document.getElementById('filtro_estado'),
    busquedaEmpleado: document.getElementById('busqueda_empleado'),
    btnBuscarEmpleados: document.getElementById('btnBuscarEmpleados'),
    btnLimpiarFiltros: document.getElementById('btnLimpiarFiltros'),
    btnRefreshStats: document.getElementById('btnRefreshStats')
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeBiometricEnrollment();
});

/**
 * Inicializar el sistema de enrolamiento
 */
async function initializeBiometricEnrollment() {
    try {
        await loadSedes();
        await loadEstablecimientos();
        await loadEmployeeData();
        updateStatistics();
        displayEmployees();
        
        // Configurar event listeners después de cargar datos
        setupEventListeners();
        
        console.log('Sistema de enrolamiento biométrico inicializado correctamente');
    } catch (error) {
        console.error('Error al inicializar el sistema:', error);
        showNotification('Error al cargar el sistema de enrolamiento', 'error');
    }
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Filtros
    if (elements.btnBuscarEmpleados) {
        elements.btnBuscarEmpleados.addEventListener('click', applyFilters);
    }
    
    if (elements.btnLimpiarFiltros) {
        elements.btnLimpiarFiltros.addEventListener('click', clearFilters);
    }
    
    if (elements.btnRefreshStats) {
        elements.btnRefreshStats.addEventListener('click', refreshData);
    }
    
    // Búsqueda en tiempo real
    if (elements.busquedaEmpleado) {
        elements.busquedaEmpleado.addEventListener('input', debounce(applyFilters, 500));
    }
    
    // Filtros de dropdown
    if (elements.filtroSede) {
        elements.filtroSede.addEventListener('change', applyFilters);
    }
    
    if (elements.filtroEstablecimiento) {
        elements.filtroEstablecimiento.addEventListener('change', applyFilters);
    }
    
    if (elements.filtroEstado) {
        elements.filtroEstado.addEventListener('change', applyFilters);
    }
}

/**
 * Cargar datos de empleados
 */
async function loadEmployeeData() {
    try {
        // Construir parámetros para la solicitud
        const params = new URLSearchParams();
        
        // Añadir filtros actuales
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
        params.append('page', currentPage);
        params.append('limit', employeesPerPage);
        
        // Añadir timestamp para evitar caché
        params.append('_t', Date.now());
        
        console.log('Cargando empleados con parámetros:', params.toString());
        
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
                            <p>Cargando empleados...</p>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        // Hacer solicitud al API
        const response = await fetch(`api/biometric/direct-employees.php?${params.toString()}`);
        const jsonData = await response.json();
        
        if (jsonData && jsonData.success && jsonData.data) {
            console.log('Datos cargados correctamente:', jsonData);
            
            // Actualizar datos de empleados (ahora solo los de la página actual)
            employeeData = jsonData.data;
            filteredEmployees = [...employeeData];
            
            // Actualizar información de paginación si está disponible
            if (jsonData.pagination) {
                updatePaginationInfo(jsonData.pagination);
            }
            
            return jsonData.data;
        } else {
            console.warn('La API no devolvió datos válidos:', jsonData);
            throw new Error(jsonData.message || 'Error al cargar datos');
        }
                updateStatistics();
                displayEmployees();
                return;
            }
        }
        
        // Obtener estado biométrico para cada empleado
        for (let employee of employeeData) {
            try {
                const biometricStatus = await getBiometricStatus(employee.ID_EMPLEADO);
                employee.biometric_status = biometricStatus;
            } catch (err) {
                console.error('Error al obtener estado biométrico:', err);
                employee.biometric_status = {
                    facial: false,
                    fingerprint: false,
                    enrolled: false
                };
            }
        }
        
        filteredEmployees = [...employeeData];
        console.log('Datos de empleados cargados correctamente', employeeData.length);
        
        // Actualizar estadísticas
        updateStatistics();
        
    } catch (error) {
        console.error('Error cargando empleados:', error);
        showNotification('Error al cargar los empleados: ' + error.message, 'error');
        
        // Mostrar mensaje de error en la tabla
        const tableBody = document.getElementById('employeeTableBody');
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="error-indicator">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                            <p>Error al cargar empleados: ${error.message}</p>
                            <button onclick="refreshData()" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="fas fa-sync"></i> Reintentar
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
}

/**
 * Obtener estado biométrico de un empleado
 */
async function getBiometricStatus(employeeId) {
    if (!employeeId) {
        console.error('ID de empleado indefinido en getBiometricStatus');
        return {
            facial: false,
            fingerprint: false,
            enrolled: false,
            status: 'pending',
            message: 'ID no válido'
        };
    }
    
    try {
        console.log('Consultando estado biométrico para empleado ID:', employeeId);
        const response = await fetch(`api/biometric/status.php?employee_id=${employeeId}`);
        
        if (!response.ok) {
            console.warn(`Error HTTP ${response.status} al obtener estado biométrico`);
            return {
                facial: false,
                fingerprint: false,
                enrolled: false,
                status: 'pending',
                message: `Error: ${response.status}`
            };
        }
        
        const responseText = await response.text();
        let data;
        
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Error al analizar JSON:', e);
            console.log('Respuesta recibida:', responseText.substring(0, 200));
            return {
                facial: false,
                fingerprint: false,
                enrolled: false,
                status: 'pending',
                message: 'Error en formato de respuesta'
            };
        }
        
        if (data.success) {
            if (data.biometric_data) {
                const hasFacial = data.biometric_data.facial && data.biometric_data.facial.length > 0;
                const hasFingerprint = Object.keys(data.biometric_data.fingerprint || {}).length > 0;
                
                return {
                    facial: hasFacial,
                    fingerprint: hasFingerprint,
                    enrolled: hasFacial || hasFingerprint,
                    status: (hasFacial || hasFingerprint) ? 'enrolled' : 'pending',
                    data: data.biometric_data
                };
            } else {
                return data.status || {
                    facial: false,
                    fingerprint: false,
                    enrolled: false,
                    status: 'pending'
                };
            }
        }
        
        return {
            facial: false,
            fingerprint: false,
            enrolled: false,
            status: 'pending',
            message: data.message || 'Sin datos biométricos'
        };
    } catch (error) {
        console.error('Error obteniendo estado biométrico:', error);
        return {
            facial: false,
            fingerprint: false,
            status: 'pending',
            last_update: null
        };
    }
}

/**
 * Cargar sedes
 */
async function loadSedes() {
    try {
        const response = await fetch('api/get-sedes.php');
        const data = await response.json();
        
        if (data.success && elements.filtroSede) {
            elements.filtroSede.innerHTML = '<option value="">Seleccionar una Sede</option>';
            data.sedes.forEach(sede => {
                const option = document.createElement('option');
                option.value = sede.ID_SEDE;
                option.textContent = sede.NOMBRE;
                elements.filtroSede.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando sedes:', error);
    }
}

/**
 * Cargar establecimientos
 */
async function loadEstablecimientos() {
    try {
        const response = await fetch('api/get-establecimientos.php');
        const data = await response.json();
        
        if (data.success && elements.filtroEstablecimiento) {
            elements.filtroEstablecimiento.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
            data.establecimientos.forEach(establecimiento => {
                const option = document.createElement('option');
                option.value = establecimiento.ID_ESTABLECIMIENTO;
                option.textContent = establecimiento.NOMBRE;
                elements.filtroEstablecimiento.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando establecimientos:', error);
    }
}

/**
 * Actualizar estadísticas
 */
function updateStatistics() {
    // Verificar que tenemos datos
    if (!employeeData) {
        console.warn('No hay datos para actualizar estadísticas');
        if (elements.totalEmployees) elements.totalEmployees.textContent = '0';
        if (elements.enrolledCount) elements.enrolledCount.textContent = '0';
        if (elements.pendingCount) elements.pendingCount.textContent = '0';
        if (elements.enrollmentPercentage) elements.enrollmentPercentage.textContent = '0%';
        return;
    }
    
    console.log('Actualizando estadísticas con', employeeData.length, 'empleados');
    
    const total = employeeData.length;
    
    // Contar empleados enrolados verificando que biometric_status exista
    const enrolled = employeeData.filter(emp => {
        return emp && emp.biometric_status && 
              (emp.biometric_status.status === 'enrolled' || 
               emp.biometric_status.enrolled === true ||
               emp.biometric_status.facial === true ||
               emp.biometric_status.fingerprint === true);
    }).length;
    
    const pending = total - enrolled;
    const percentage = total > 0 ? Math.round((enrolled / total) * 100) : 0;
    
    console.log(`Estadísticas: Total=${total}, Enrolados=${enrolled}, Pendientes=${pending}, Porcentaje=${percentage}%`);
    
    if (elements.totalEmployees) elements.totalEmployees.textContent = total;
    if (elements.enrolledCount) elements.enrolledCount.textContent = enrolled;
    if (elements.pendingCount) elements.pendingCount.textContent = pending;
    if (elements.enrollmentPercentage) elements.enrollmentPercentage.textContent = `${percentage}%`;
}

/**
 * Mostrar empleados en la tabla
 */
function displayEmployees() {
    if (!elements.employeeTableBody) {
        console.error('No se encontró el elemento de tabla de empleados');
        return;
    }
    
    // Si no hay datos de empleados, mostrar un mensaje de diagnóstico
    if (!employeeData || employeeData.length === 0) {
        console.warn('No hay datos de empleados para mostrar');
        elements.employeeTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="error-indicator">
                        <i class="fas fa-exclamation-triangle text-warning mb-3 fa-2x"></i>
                        <p class="mb-2">No se encontraron datos de empleados en el sistema</p>
                        <div class="mt-3">
                            <button onclick="refreshData()" class="btn btn-sm btn-primary">
                                <i class="fas fa-sync-alt"></i> Actualizar datos
                            </button>
                            <button onclick="window.location.href='api/biometric/self-diagnostic.php'" class="btn btn-sm btn-info ml-2">
                                <i class="fas fa-stethoscope"></i> Ver diagnóstico
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    console.log(`Mostrando empleados: ${employeeData.length} en página ${currentPage}`);
    
    elements.employeeTableBody.innerHTML = '';
    
    // Los datos ya vienen paginados del servidor, mostrar todos
    employeeData.forEach(employee => {
        const row = createEmployeeRow(employee);
        elements.employeeTableBody.appendChild(row);
    });
    
    console.log(`Empleados mostrados en la tabla: ${employeeData.length}`);
}

/**
 * Crear fila de empleado
 */
function createEmployeeRow(employee) {
    const tr = document.createElement('tr');
    
    // Debug: ver el formato original del empleado
    console.log('Empleado original:', employee);
    
    // Normalizar datos del empleado para compatibilidad con diferentes formatos JSON
    const normalizedEmployee = {
        id: employee.ID_EMPLEADO || employee.id_empleado || employee.id || employee.codigo || '',
        nombre: employee.NOMBRE || employee.nombre || employee.NOMBRES || '',
        apellido: employee.APELLIDO || employee.apellido || employee.APELLIDOS || '',
        codigo: employee.codigo || employee.CODIGO || employee.ID_EMPLEADO || employee.id || '',
        establecimiento: employee.nombre_establecimiento || employee.ESTABLECIMIENTO || employee.establecimiento || '-',
        sede: employee.nombre_sede || employee.SEDE || employee.sede || '-',
        // Estado biométrico usando los campos reales del endpoint
        facial_enrolled: employee.facial_enrolled === 1 || employee.facial_enrolled === true,
        fingerprint_enrolled: employee.fingerprint_enrolled === 1 || employee.fingerprint_enrolled === true,
        biometric_status: employee.biometric_status || 'pending',
        last_updated: employee.last_updated
    };
    
    // Debug: ver normalización
    console.log('Empleado normalizado:', normalizedEmployee);
    
    // Determinar estado biométrico basado en los campos reales
    let statusClass = 'badge-secondary';
    let statusText = 'Pendiente';
    
    if (normalizedEmployee.facial_enrolled && normalizedEmployee.fingerprint_enrolled) {
        statusClass = 'badge-success';
        statusText = 'Inscrito';
        normalizedEmployee.biometric_status = 'enrolled';
    } else if (normalizedEmployee.facial_enrolled || normalizedEmployee.fingerprint_enrolled) {
        statusClass = 'badge-warning';
        statusText = 'Parcial';
        normalizedEmployee.biometric_status = 'partial';
    } else {
        statusClass = 'badge-secondary';
        statusText = 'Pendiente';
        normalizedEmployee.biometric_status = 'pending';
    }
    
    const nombreCompleto = `${normalizedEmployee.nombre} ${normalizedEmployee.apellido}`.trim();
    
    tr.innerHTML = `
        <td><strong>${normalizedEmployee.codigo}</strong></td>
        <td>${nombreCompleto}</td>
        <td>${normalizedEmployee.establecimiento}</td>
        <td><span class="badge ${statusClass}">${statusText}</span></td>
        <td>
            <i class="fas fa-circle ${normalizedEmployee.facial_enrolled ? 'text-success' : 'text-secondary'}"></i>
            ${normalizedEmployee.facial_enrolled ? 'Registrado' : 'Pendiente'}
        </td>
        <td>
            <i class="fas fa-circle ${normalizedEmployee.fingerprint_enrolled ? 'text-success' : 'text-secondary'}"></i>
            ${normalizedEmployee.fingerprint_enrolled ? 'Registrado' : 'Pendiente'}
        </td>
        <td>${normalizedEmployee.last_updated ? formatDate(normalizedEmployee.last_updated) : '-'}</td>
        <td>
            <div class="action-buttons">
                <button type="button" class="btn-action btn-primary" 
                        onclick="openEnrollmentModal(${normalizedEmployee.id})"
                        title="Enrolar empleado">
                    <i class="fas fa-fingerprint"></i>
                </button>
                <button type="button" class="btn-action btn-info" 
                        onclick="viewEnrollmentHistory(${normalizedEmployee.id})"
                        title="Ver historial">
                    <i class="fas fa-history"></i>
                </button>
                ${normalizedEmployee.biometric_status === 'enrolled' ? `
                <button type="button" class="btn-action btn-warning" 
                        onclick="updateEnrollment(${normalizedEmployee.id})"
                        title="Actualizar datos">
                    <i class="fas fa-sync-alt"></i>
                </button>` : ''}
            </div>
        </td>
    `;
    
    return tr;
}
                </button>
                ` : ''}
            </div>
        </td>
    `;
    
    return tr;
}

/**
 * Obtener clase CSS para el estado
 */
function getStatusClass(status) {
    switch (status) {
        case 'enrolled': return 'bg-success';
        case 'partial': return 'bg-warning';
        case 'pending': 
        default: return 'bg-secondary';
    }
}

/**
 * Obtener texto para el estado
 */
function getStatusText(status) {
    switch (status) {
        case 'enrolled': return 'Inscrito';
        case 'partial': return 'Parcial';
        case 'pending': 
        default: return 'Pendiente';
    }
}

/**
 * Actualizar información de paginación desde el servidor
 */
function updatePaginationInfo(paginationData) {
    console.log('Actualizando paginación con datos del servidor:', paginationData);
    
    // Actualizar la paginación global
    if (paginationData.total_pages) {
        // Reinicializar el sistema de paginación con los datos del servidor
        initPagination({
            currentPage: paginationData.current_page,
            itemsPerPage: paginationData.per_page,
            totalItems: paginationData.total,
            containerId: 'paginationContainer',
            onPageChange: function(page) {
                currentPage = page;
                loadEmployeeData(); // Recargar datos del servidor
            }
        });
    }
}

/**
 * Aplicar filtros
 */
/**
 * Aplicar filtros
 */
async function applyFilters() {
    console.log('Aplicando filtros...');
    
    // Resetear a la primera página cuando se aplican filtros
    currentPage = 1;
    
    try {
        await loadEmployeeData();
        updateStatistics();
        displayEmployees();
        
        console.log('Filtros aplicados correctamente');
    } catch (error) {
        console.error('Error al aplicar filtros:', error);
        showNotification('Error al aplicar filtros: ' + error.message, 'error');
    }
}

/**
 * Limpiar filtros
 */
async function clearFilters() {
    if (elements.filtroSede) elements.filtroSede.value = '';
    if (elements.filtroEstablecimiento) elements.filtroEstablecimiento.value = '';
    if (elements.filtroEstado) elements.filtroEstado.value = '';
    if (elements.busquedaEmpleado) elements.busquedaEmpleado.value = '';
    
    currentPage = 1;
    
    try {
        await loadEmployeeData();
        updateStatistics();
        displayEmployees();
        
        console.log('Filtros limpiados correctamente');
    } catch (error) {
        console.error('Error al limpiar filtros:', error);
        showNotification('Error al limpiar filtros: ' + error.message, 'error');
    }
}

/**
 * Refrescar datos
 */
async function refreshData() {
    const originalText = elements.btnRefreshStats?.innerHTML;
    if (elements.btnRefreshStats) {
        elements.btnRefreshStats.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        elements.btnRefreshStats.disabled = true;
    }
    
    try {
        await loadEmployeeData();
        applyFilters();
        showNotification('Datos actualizados correctamente', 'success');
    } catch (error) {
        console.error('Error al refrescar:', error);
        showNotification('Error al actualizar los datos', 'error');
    } finally {
        if (elements.btnRefreshStats) {
            elements.btnRefreshStats.innerHTML = originalText;
            elements.btnRefreshStats.disabled = false;
        }
    }
}

/**
 * Actualizar paginación
 */
function updatePagination() {
    if (!elements.paginationContainer) return;
    
    // Utilizar el nuevo sistema de paginación
    updatePaginationState({
        currentPage: currentPage,
        itemsPerPage: employeesPerPage,
        totalItems: filteredEmployees.length,
        containerId: 'paginationContainer',
        onPageChange: function(page) {
            currentPage = page;
            displayEmployees();
        }
    });
}

// La función changePage ahora se maneja en pagination.js

/**
 * Abrir modal de enrolamiento
 */
function openEnrollmentModal(employeeId) {
    console.log('Abriendo modal para empleado ID:', employeeId);
    
    // Buscar el empleado por ID
    const employee = employeeData.find(emp => 
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
        
        // Normalizar los nombres de propiedades para manejar tanto mayúsculas como minúsculas
        if (codeElement) codeElement.textContent = employee.ID_EMPLEADO || employee.id_empleado || employee.CODIGO || employee.codigo || employee.id || employeeId;
        if (nameElement) {
            const firstName = employee.NOMBRES || employee.nombres || employee.NOMBRE || employee.nombre || '';
            const lastName = employee.APELLIDOS || employee.apellidos || employee.APELLIDO || employee.apellido || '';
            nameElement.textContent = `${firstName} ${lastName}`.trim() || '-';
        }
        if (establishmentElement) {
            establishmentElement.textContent = employee.ESTABLECIMIENTO || employee.establecimiento || employee.SEDE || employee.sede || '-';
        }
        
        // Asegurar que los campos ocultos tengan el ID del empleado
        const hiddenFields = ['current-employee-id', 'employee_id', 'hidden_employee_id'];
        hiddenFields.forEach(id => {
            const field = document.getElementById(id);
            if (field) field.value = employeeId;
        });
        
        // Mostrar el modal usando Bootstrap
        try {
            // Detener cualquier cámara activa antes de abrir un nuevo modal
            if (typeof stopFaceCamera === 'function') stopFaceCamera();
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Asegurarse que los elementos del DOM estén disponibles antes de intentar operaciones
            setTimeout(() => {
                // Inicializar BlazeFace si está disponible
                if (typeof initBlazeFace === 'function') initBlazeFace();
                
                // Verificar estado biométrico del empleado
                checkBiometricStatus(employeeId).then(status => {
                    if (status) {
                        const facialStatus = document.getElementById('facial-status');
                        const fingerprintStatus = document.getElementById('fingerprint-status');
                        
                        if (facialStatus) {
                            facialStatus.className = status.facial ? 'badge bg-success' : 'badge bg-secondary';
                            facialStatus.textContent = status.facial ? 'Inscrito' : 'Pendiente';
                        }
                        
                        if (fingerprintStatus) {
                            fingerprintStatus.className = status.fingerprint ? 'badge bg-success' : 'badge bg-secondary';
                            fingerprintStatus.textContent = status.fingerprint ? 'Inscrito' : 'Pendiente';
                        }
                    }
                });
            }, 300); // Pequeño retraso para garantizar que el DOM esté listo
        } catch (modalError) {
            console.error('Error al abrir el modal:', modalError);
            showNotification('Error al abrir el modal de enrolamiento', 'error');
        }
    } catch (error) {
        console.error('Error al preparar datos para el modal:', error);
        showNotification('Error al preparar el enrolamiento', 'error');
    }
}
    
    // Almacenar el ID del empleado de múltiples formas para asegurar acceso
    const enrollmentModal = document.getElementById('biometricEnrollmentModal');
    if (enrollmentModal) {
        enrollmentModal.setAttribute('data-employee-id', employeeId);
    }
    
    // Almacenar en el campo oculto
    const hiddenEmployeeIdField = document.getElementById('current-employee-id');
    if (hiddenEmployeeIdField) {
        hiddenEmployeeIdField.value = employeeId;
        console.log('ID de empleado guardado en campo oculto:', employeeId);
    }
    
    // También almacenar el ID en una variable global para fácil acceso
    window.currentEmployeeId = employeeId;
    console.log('ID de empleado guardado en variable global:', employeeId);
    
    // Actualizar estado biométrico
    const biometricStatus = employee.biometric_status || {};
    const facialStatus = document.getElementById('facial-status');
    const fingerprintStatus = document.getElementById('fingerprint-status');
    
    if (facialStatus) {
        facialStatus.className = `badge ${biometricStatus.facial ? 'bg-success' : 'bg-secondary'}`;
        facialStatus.textContent = biometricStatus.facial ? 'Registrado' : 'Pendiente';
    }
    
    if (fingerprintStatus) {
        fingerprintStatus.className = `badge ${biometricStatus.fingerprint ? 'bg-success' : 'bg-secondary'}`;
        fingerprintStatus.textContent = biometricStatus.fingerprint ? 'Registrado' : 'Pendiente';
    }
    
    // Establecer el empleado actual para el sistema de captura
    if (window.currentEmployeeId !== undefined) {
        window.currentEmployeeId = employeeId;
    }
    
    // Reiniciar el sistema de captura si está disponible
    if (typeof resetCapture === 'function') {
        resetCapture();
    }
    
    // Obtener el elemento modal
    const modalElement = document.getElementById('biometricEnrollmentModal');
    if (!modalElement) {
        console.error('Elemento modal no encontrado');
        showNotification('Error: Modal de enrolamiento no encontrado', 'error');
        return;
    }
    
    try {
        // Eliminar cualquier modal anterior que pueda estar causando problemas
        if (currentModal) {
            try {
                currentModal.hide();
                currentModal.dispose();
            } catch (e) {
                console.log('Error al limpiar modal anterior:', e);
            }
        }
        
        // Limpiar cualquier instancia de modal anterior
        try {
            const bootstrapModal = bootstrap.Modal.getInstance(modalElement);
            if (bootstrapModal) {
                bootstrapModal.dispose();
            }
        } catch (e) {
            console.log('No había instancias previas del modal');
        }
        
        // Asegurarnos de que el modal no tenga estilos inline que puedan interferir
        modalElement.style.display = '';
        modalElement.style.paddingRight = '';
        
        // Eliminar clases que puedan interferir
        modalElement.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Eliminar posibles backdrops residuales
        const backdropElements = document.querySelectorAll('.modal-backdrop');
        backdropElements.forEach(backdrop => backdrop.remove());
        
        // Verificar si Bootstrap está disponible
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal !== 'function') {
            console.error('Bootstrap no está disponible correctamente');
            showNotification('Error: Bootstrap no está cargado correctamente', 'error');
            
            // Intento alternativo usando jQuery si está disponible
            if (typeof $ !== 'undefined' && typeof $(modalElement).modal === 'function') {
                $(modalElement).modal('show');
                currentModal = $(modalElement);
                console.log('Modal mostrado usando jQuery');
                
                // Activar la primera pestaña
                const firstTab = document.querySelector('#enrollmentTabs .nav-link');
                if (firstTab && typeof $(firstTab).tab === 'function') {
                    $(firstTab).tab('show');
                }
                
                return;
            } else {
                // Si todo falla, intentar un enfoque básico
                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                document.body.classList.add('modal-open');
                
                // Crear backdrop manualmente
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                
                console.log('Modal mostrado manualmente como último recurso');
                return;
            }
        }
        
        // Abrir modal usando Bootstrap 5
        const modal = new bootstrap.Modal(modalElement, {
            keyboard: true,
            focus: true,
            backdrop: true
        });
        modal.show();
        currentModal = modal;
        console.log('Modal mostrado correctamente con Bootstrap 5');
        
        // Activar la primera pestaña
        const tabs = document.querySelectorAll('#enrollmentTabs .nav-link');
        if (tabs.length > 0 && typeof bootstrap.Tab === 'function') {
            tabs.forEach(tab => {
                if (tab.id === 'facial-tab') {
                    const tabInstance = new bootstrap.Tab(tab);
                    tabInstance.show();
                }
            });
        }
        
    } catch (error) {
        console.error('Error al mostrar el modal:', error);
        showNotification('Error al abrir el modal: ' + error.message, 'error');
    }
}

/**
 * Ver historial de enrolamiento
 */
function viewEnrollmentHistory(employeeId) {
    // Implementar vista de historial
    showNotification('Función de historial en desarrollo', 'info');
}

/**
 * Actualizar enrolamiento
 */
function updateEnrollment(employeeId) {
    openEnrollmentModal(employeeId);
}

/**
 * Formatear fecha
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CO');
}

/**
 * Función debounce
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Mostrar notificación
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de alerta: 'info', 'success', 'warning', 'error'
 * @param {number} duration - Duración en milisegundos antes de auto-cerrar (0 para no cerrar)
 * @returns {HTMLElement} El elemento de notificación creado
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remover después del tiempo especificado (si es mayor que 0)
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }
    
    // Devolver el elemento para que pueda ser manipulado más tarde
    return notification;
}

// Hacer funciones globales
window.changePage = changePage;
window.refreshData = refreshData;
window.clearFilters = clearFilters;
window.openEnrollmentModal = openEnrollmentModal;
window.viewEnrollmentHistory = viewEnrollmentHistory;
window.updateEnrollment = updateEnrollment;
