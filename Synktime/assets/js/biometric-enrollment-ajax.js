// ===================================================================
// BIOMETRIC-ENROLLMENT-AJAX.JS - SISTEMA COMPLETO CON PAGINACIÓN
// ===================================================================

// Variables globales para paginación
let currentPage = 1;
let currentLimit = 20;
let totalPages = 1;
let currentFilters = {};

// Variable global para almacenar datos de empleados
window.currentEmployeeData = [];

// Configuración de límites disponibles
const AVAILABLE_LIMITS = [10, 15, 20, 30, 50, 100];

// ===================================================================
// 1. INICIALIZACIÓN Y CONFIGURACIÓN
// ===================================================================

document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Inicializando módulo biométrico con AJAX...');
    initializePagination();
    cargarSedesBiometric();
    loadBiometricEmployees();
    setupEventListeners();
    setupModalListeners();
});

// ===================================================================
// 2. SISTEMA DE PAGINACIÓN
// ===================================================================

function initializePagination() {
    // La paginación ya está en el HTML, solo configuramos eventos
    const limitSelector = document.getElementById('limitSelector');
    if (limitSelector) {
        limitSelector.value = currentLimit;
    }
}

function setupEventListeners() {
    // Selector de límite
    const limitSelector = document.getElementById('limitSelector');
    if (limitSelector) {
        limitSelector.addEventListener('change', function() {
            currentLimit = parseInt(this.value);
            currentPage = 1;
            loadBiometricEmployees();
        });
    }

    // Formulario de filtros
    const form = document.getElementById('formBusquedaEmpleados');
    if (form) {
        const btnBuscar = document.getElementById('btnBuscarEmpleados');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = 1;
                updateFiltersFromForm();
                loadBiometricEmployees();
            });
        }
    }

    // Botón limpiar filtros
    const clearBtn = document.getElementById('btnLimpiarFiltros');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearFilters();
            loadBiometricEmployees();
        });
    }

    // Cambio de sede
    const sedeSelect = document.getElementById('filtro_sede');
    if (sedeSelect) {
        sedeSelect.addEventListener('change', function() {
            cargarEstablecimientosBiometric();
            setTimeout(() => {
                currentPage = 1;
                updateFiltersFromForm();
                loadBiometricEmployees();
            }, 100);
        });
    }

    // Cambio de establecimiento
    const estSelect = document.getElementById('filtro_establecimiento');
    if (estSelect) {
        estSelect.addEventListener('change', function() {
            currentPage = 1;
            updateFiltersFromForm();
            loadBiometricEmployees();
        });
    }

    // Botón actualizar
    const refreshBtn = document.getElementById('btnActualizarDatos');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadBiometricEmployees();
        });
    }
}

function updateFiltersFromForm() {
    currentFilters = {
        busqueda: document.getElementById('busqueda_empleado')?.value || '',
        sede: document.getElementById('filtro_sede')?.value || '',
        establecimiento: document.getElementById('filtro_establecimiento')?.value || '',
        estado: document.getElementById('filtro_estado')?.value || ''
    };
    
    // Remover filtros vacíos
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
}

function clearFilters() {
    const form = document.getElementById('formBusquedaEmpleados');
    if (form) {
        form.reset();
    }
    currentFilters = {};
    currentPage = 1;
}

// ===================================================================
// 3. CARGA DE DATOS CON PAGINACIÓN
// ===================================================================

async function loadBiometricEmployees() {
    try {
        showLoadingState();
        
        const params = new URLSearchParams({
            page: currentPage,
            limit: currentLimit,
            ...currentFilters
        });

        console.log('📡 Cargando empleados biométricos:', params.toString());

        const response = await fetch(`api/biometric/enrollment-employees.php?${params.toString()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        if (data.success) {
            // Almacenar datos globalmente para uso en modales
            window.currentEmployeeData = data.data || [];
            
            renderBiometricTable(data.data);
            updatePaginationInfo(data.pagination);
            renderPaginationButtons(data.pagination);
            updateStatsDisplay(data.stats);
            
            console.log(`✅ Cargados ${data.data?.length || 0} empleados biométricos`);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
        
    } catch (error) {
        console.error('❌ Error al cargar empleados biométricos:', error);
        showErrorState(error.message);
    }
}

function showLoadingState() {
    const tbody = document.getElementById('employeeTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </td>
            </tr>
        `;
    }
}

function showErrorState(message) {
    const tbody = document.getElementById('employeeTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="error-state">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Error: ${message}
                    <button onclick="loadBiometricEmployees()" class="btn-retry">Reintentar</button>
                </td>
            </tr>
        `;
    }
}

// ===================================================================
// 4. RENDERIZADO DE TABLA
// ===================================================================

function renderBiometricTable(data) {
    const tbody = document.getElementById('employeeTableBody');
    
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!data.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data-state">
                    <i class="fas fa-fingerprint"></i>
                    No se encontraron empleados con los filtros aplicados
                </td>
            </tr>
        `;
        return;
    }

    data.forEach(emp => {
        const facialStatus = emp.facial_enrolled ? 
            '<span class="status-enrolled"><i class="fas fa-check"></i> Inscrito</span>' : 
            '<span class="status-pending"><i class="fas fa-times"></i> Pendiente</span>';
        
        const fingerprintStatus = emp.fingerprint_enrolled ? 
            '<span class="status-enrolled"><i class="fas fa-check"></i> Inscrito</span>' : 
            '<span class="status-pending"><i class="fas fa-times"></i> Pendiente</span>';
        
        let statusBadge = '';
        switch(emp.biometric_status) {
            case 'complete':
                statusBadge = '<span class="status-complete">Completo</span>';
                break;
            case 'partial':
                statusBadge = '<span class="status-partial">Parcial</span>';
                break;
            default:
                statusBadge = '<span class="status-pending">Pendiente</span>';
        }

        tbody.innerHTML += `
            <tr data-employee-id="${emp.ID_EMPLEADO}">
                <td>${emp.codigo || emp.ID_EMPLEADO}</td>
                <td>${emp.nombre || emp.NOMBRE + ' ' + emp.APELLIDO}</td>
                <td>${emp.DNI || ''}</td>
                <td>${emp.sede || emp.SEDE}</td>
                <td>${emp.establecimiento || emp.ESTABLECIMIENTO}</td>
                <td>${facialStatus}</td>
                <td>${fingerprintStatus}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-icon btn-enroll" title="Inscribir Biometría" 
                            data-employee-id="${emp.ID_EMPLEADO}" 
                            data-employee-name="${emp.nombre || emp.NOMBRE + ' ' + emp.APELLIDO}">
                        <i class="fas fa-fingerprint"></i>
                    </button>
                    <button class="btn-icon btn-view" title="Ver Detalles" 
                            data-employee-id="${emp.ID_EMPLEADO}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

// ===================================================================
// 5. CONTROLES DE PAGINACIÓN
// ===================================================================

function updatePaginationInfo(pagination) {
    const info = document.getElementById('paginationInfo');
    if (info && pagination) {
        const start = ((pagination.current_page - 1) * pagination.limit) + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
        
        info.textContent = `Mostrando ${start} - ${end} de ${pagination.total_records} empleados`;
        
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
    }
}

function renderPaginationButtons(pagination) {
    const container = document.getElementById('paginationButtons');
    if (!container || !pagination) return;

    let buttonsHTML = '';
    
    // Botón anterior
    if (pagination.has_prev) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(${pagination.current_page - 1})">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>`;
    }

    // Botones de páginas
    const maxButtons = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxButtons / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxButtons - 1);
    
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        buttonsHTML += `<button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                            onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }

    // Botón siguiente
    if (pagination.has_next) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(${pagination.current_page + 1})">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
    }

    container.innerHTML = buttonsHTML;
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadBiometricEmployees();
    }
}

// ===================================================================
// 6. ACTUALIZACIÓN DE ESTADÍSTICAS
// ===================================================================

function updateStatsDisplay(stats) {
    if (!stats) return;
    
    // Actualizar contadores
    const totalEl = document.getElementById('totalEmployees');
    const enrolledEl = document.getElementById('enrolledCount');
    const pendingEl = document.getElementById('pendingCount');
    const percentageEl = document.getElementById('enrollmentPercentage');
    
    if (totalEl) totalEl.textContent = stats.total_empleados || 0;
    if (enrolledEl) enrolledEl.textContent = stats.total_inscritos || 0;
    if (pendingEl) pendingEl.textContent = stats.no_inscritos || 0;
    
    if (percentageEl && stats.total_empleados > 0) {
        const percentage = Math.round((stats.total_inscritos / stats.total_empleados) * 100);
        percentageEl.textContent = `${percentage}%`;
    }
}

// ===================================================================
// 7. FUNCIONES DE SEDES Y ESTABLECIMIENTOS
// ===================================================================

function cargarSedesBiometric() {
    fetch('api/get-sedes.php')
        .then(r => r.json())
        .then(res => {
            const sedeSelect = document.getElementById('filtro_sede');
            if (sedeSelect) {
                sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';
                if (res.sedes && res.sedes.length > 0) {
                    res.sedes.forEach(sede => {
                        sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
                    });
                }
                sedeSelect.value = "";
                cargarEstablecimientosBiometric();
            }
        })
        .catch(error => console.error('Error al cargar sedes:', error));
}

function cargarEstablecimientosBiometric() {
    const sedeId = document.getElementById('filtro_sede')?.value;
    const establecimientoSelect = document.getElementById('filtro_establecimiento');
    
    if (!establecimientoSelect) return;
    
    establecimientoSelect.innerHTML = '<option value="">Todos los establecimientos</option>';
    if (!sedeId) return;
    
    fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(sedeId))
        .then(r => r.json())
        .then(res => {
            if (res.establecimientos && res.establecimientos.length > 0) {
                res.establecimientos.forEach(est => {
                    establecimientoSelect.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
                });
            }
            establecimientoSelect.value = "";
        })
        .catch(error => console.error('Error al cargar establecimientos:', error));
}

// ===================================================================
// 8. FUNCIONES BIOMÉTRICAS
// ===================================================================

function setupModalListeners() {
    // Aquí se configurarían los eventos de los modales biométricos
    console.log('🔧 Configurando listeners de modales biométricos...');
}

function openBiometricModal(employeeId, employeeName) {
    console.log(`🔓 Abriendo modal biométrico para empleado ${employeeId}: ${employeeName}`);
    
    try {
        // Prevenir modales duplicados
        preventDuplicateModals('biometricEnrollmentModal');
        
        // Buscar el modal en el DOM
        const modal = document.getElementById('biometricEnrollmentModal');
        if (!modal) {
            console.error('❌ Modal biométrico no encontrado en el DOM');
            alert('Error: Modal biométrico no disponible');
            return;
        }

        // Llenar información del empleado en el modal
        const modalEmployeeCode = document.getElementById('modal-employee-code');
        const modalEmployeeName = document.getElementById('modal-employee-name');
        const modalEmployeeEstablishment = document.getElementById('modal-employee-establishment');
        
        if (modalEmployeeCode) modalEmployeeCode.textContent = employeeId;
        if (modalEmployeeName) modalEmployeeName.textContent = employeeName;
        if (modalEmployeeEstablishment) modalEmployeeEstablishment.textContent = 'Cargando...';

        // Establecer IDs en campos ocultos
        const hiddenFields = ['current-employee-id', 'hidden_employee_id', 'employee_id'];
        hiddenFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.value = employeeId;
        });

        // Verificar que Bootstrap esté disponible
        if (typeof bootstrap === 'undefined') {
            console.error('❌ Bootstrap no está cargado');
            modal.style.display = 'block';
            modal.classList.add('show');
            return;
        }

        // Abrir modal con Bootstrap
        const bootstrapModal = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: false
        });
        
        bootstrapModal.show();
        console.log('✅ Modal biométrico abierto correctamente');
        
        // Cargar datos adicionales del empleado de forma asíncrona
        loadEmployeeDetailsForModal(employeeId);
        
    } catch (error) {
        console.error('❌ Error abriendo modal biométrico:', error);
        alert('Error al abrir el modal de enrolamiento: ' + error.message);
    }
}

/**
 * Función para prevenir y limpiar modales duplicados
 */
function preventDuplicateModals(modalId = null) {
    // Lista de IDs de modales que podrían estar abiertos
    const modalIds = [
        'employeeDetailsModal',
        'biometricEnrollmentModal',
        modalId
    ].filter(Boolean);
    
    modalIds.forEach(id => {
        const existingModal = document.getElementById(id);
        if (existingModal) {
            // Cerrar modal si está abierto con Bootstrap
            if (typeof bootstrap !== 'undefined') {
                const bsModal = bootstrap.Modal.getInstance(existingModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
            
            // Remover backdrop si existe
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Remover modal del DOM si es dinámico
            if (id === 'employeeDetailsModal') {
                existingModal.remove();
            }
        }
    });
    
    // Limpiar clases del body
    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
    document.body.style.overflow = '';
}

function viewBiometricDetails(employeeId) {
    console.log(`👁️ Viendo detalles biométricos para empleado ${employeeId}`);
    
    try {
        // Prevenir múltiples modales abiertos
        preventDuplicateModals('employeeDetailsModal');
        
        // Buscar empleado en los datos cargados
        const employee = window.currentEmployeeData ? 
            window.currentEmployeeData.find(emp => emp.ID_EMPLEADO == employeeId) : null;
        
        // Crear modal de detalles dinámicamente
        createEmployeeDetailsModal(employeeId, employee);
        
    } catch (error) {
        console.error('❌ Error mostrando detalles:', error);
        alert('Error al mostrar detalles del empleado: ' + error.message);
    }
}

/**
 * Cargar detalles adicionales del empleado para el modal
 */
async function loadEmployeeDetailsForModal(employeeId) {
    try {
        // Buscar empleado en datos actuales
        const employee = window.currentEmployeeData ? 
            window.currentEmployeeData.find(emp => emp.ID_EMPLEADO == employeeId) : null;
        
        if (employee) {
            const modalEmployeeEstablishment = document.getElementById('modal-employee-establishment');
            if (modalEmployeeEstablishment) {
                modalEmployeeEstablishment.textContent = employee.establecimiento || employee.ESTABLECIMIENTO || 'No especificado';
            }
        }
        
        // Cargar estado biométrico actual
        const response = await fetch(`api/biometric/status.php?employee_id=${employeeId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            updateModalBiometricStatus(result.data);
        }
        
    } catch (error) {
        console.warn('⚠️ No se pudieron cargar detalles adicionales:', error);
    }
}

/**
 * Actualizar estado biométrico en el modal
 */
function updateModalBiometricStatus(statusData) {
    const facialStatus = document.getElementById('facial-status');
    const fingerprintStatus = document.getElementById('fingerprint-status');
    
    if (facialStatus) {
        facialStatus.className = statusData.facial ? 'badge bg-success' : 'badge bg-secondary';
        facialStatus.textContent = statusData.facial ? 'Registrado' : 'Pendiente';
    }
    
    if (fingerprintStatus) {
        fingerprintStatus.className = statusData.fingerprint ? 'badge bg-success' : 'badge bg-secondary';
        fingerprintStatus.textContent = statusData.fingerprint ? 'Registrado' : 'Pendiente';
    }
}

/**
 * Crear modal de detalles del empleado
 */
function createEmployeeDetailsModal(employeeId, employee) {
    // Remover modal existente si lo hay
    const existingModal = document.getElementById('employeeDetailsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const employeeName = employee ? 
        `${employee.nombre || employee.NOMBRE || ''} ${employee.apellido || employee.APELLIDO || ''}`.trim() :
        'Empleado no encontrado';
    
    const modalHTML = `
        <div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-circle text-primary"></i> Detalles del Empleado
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-info-circle text-info"></i> Información General</h6>
                                <table class="table table-sm table-borderless">
                                    <tr><td><strong>ID:</strong></td><td>${employeeId}</td></tr>
                                    <tr><td><strong>Código:</strong></td><td>${employee?.codigo || employee?.CODIGO || employeeId}</td></tr>
                                    <tr><td><strong>Nombre:</strong></td><td>${employeeName}</td></tr>
                                    <tr><td><strong>DNI:</strong></td><td>${employee?.DNI || employee?.dni || 'No especificado'}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-building text-warning"></i> Ubicación</h6>
                                <table class="table table-sm table-borderless">
                                    <tr><td><strong>Sede:</strong></td><td>${employee?.sede || employee?.SEDE || 'No especificado'}</td></tr>
                                    <tr><td><strong>Establecimiento:</strong></td><td>${employee?.establecimiento || employee?.ESTABLECIMIENTO || 'No especificado'}</td></tr>
                                </table>
                                
                                <h6 class="mb-3 mt-4"><i class="fas fa-fingerprint text-success"></i> Estado Biométrico</h6>
                                <table class="table table-sm table-borderless">
                                    <tr><td><strong>Facial:</strong></td><td>
                                        <span class="badge ${employee?.facial_enrolled ? 'bg-success' : 'bg-secondary'}">
                                            ${employee?.facial_enrolled ? 'Registrado' : 'Pendiente'}
                                        </span>
                                    </td></tr>
                                    <tr><td><strong>Huella:</strong></td><td>
                                        <span class="badge ${employee?.fingerprint_enrolled ? 'bg-success' : 'bg-secondary'}">
                                            ${employee?.fingerprint_enrolled ? 'Registrado' : 'Pendiente'}
                                        </span>
                                    </td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="openBiometricModal('${employeeId}', '${employeeName.replace(/'/g, '\\\'')}')" data-bs-dismiss="modal">
                            <i class="fas fa-fingerprint"></i> Inscribir Biometría
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Añadir al DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Mostrar modal
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));
        modal.show();
    } else {
        // Fallback si Bootstrap no está disponible
        const modalElement = document.getElementById('employeeDetailsModal');
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
    }
    
    // Limpiar cuando se cierre
    document.getElementById('employeeDetailsModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// ===================================================================
// 9. API PÚBLICA Y DIAGNÓSTICO
// ===================================================================

/**
 * Función de diagnóstico para verificar que todos los componentes estén disponibles
 */
function diagnosticBiometricSystem() {
    console.log('🔍 Ejecutando diagnóstico del sistema biométrico...');
    
    const checks = {
        'Bootstrap disponible': typeof bootstrap !== 'undefined',
        'Modal biométrico en DOM': !!document.getElementById('biometricEnrollmentModal'),
        'Tabla de empleados en DOM': !!document.getElementById('employeeTableBody'),
        'Formulario de filtros en DOM': !!document.getElementById('busqueda_empleado'),
        'Datos de empleados cargados': Array.isArray(window.currentEmployeeData) && window.currentEmployeeData.length > 0
    };
    
    Object.entries(checks).forEach(([check, passed]) => {
        console.log(`${passed ? '✅' : '❌'} ${check}: ${passed ? 'OK' : 'FALTA'}`);
    });
    
    return checks;
}

/**
 * Función auxiliar para mostrar el modal manualmente (fallback)
 */
function showModalFallback(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        
        // Agregar botón de cierre manual
        const closeBtn = modal.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.onclick = () => {
                modal.style.display = 'none';
                modal.classList.remove('show');
            };
        }
    }
}

// Exponer funciones globalmente para compatibilidad
window.loadBiometricEmployees = loadBiometricEmployees;
window.goToPage = goToPage;
window.openBiometricModal = openBiometricModal;
window.viewBiometricDetails = viewBiometricDetails;
window.diagnosticBiometricSystem = diagnosticBiometricSystem;

// Ejecutar diagnóstico al cargar
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        diagnosticBiometricSystem();
    }, 2000);
});

console.log('✅ Módulo biométrico AJAX inicializado correctamente');
