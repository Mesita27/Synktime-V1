// ===================================================================
// EMPLOYEE.JS - VERSIÓN INTEGRADA CON PAGINACIÓN AJAX
// ===================================================================

// Variables globales para paginación
let currentPage = 1;
let currentLimit = 10;
let totalPages = 1;
let currentFilters = {};

// Configuración de límites disponibles
const AVAILABLE_LIMITS = [10, 15, 20, 30, 40, 50];

// Variable para modal de eliminación
let empleadoAEliminar = null;

// Estado y configuraciones para el módulo de vacaciones
const VACATION_STATUS_LABELS = {
    PROGRAMADO: 'Programado',
    ACTIVO: 'Activo',
    FINALIZADO: 'Finalizado',
    CANCELADO: 'Cancelado',
};

const VACATION_BADGE_CLASSES = {
    PROGRAMADO: 'status-programado',
    ACTIVO: 'status-activo',
    FINALIZADO: 'status-finalizado',
    CANCELADO: 'status-cancelado',
};

const VACATION_API_ROUTES = {
    list: 'api/employee/vacations/list.php',
    create: 'api/employee/vacations/create.php',
    update: 'api/employee/vacations/update.php',
    changeStatus: 'api/employee/vacations/change-status.php',
};

const VACATION_DEFAULT_HEADERS = {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
};

const vacationState = {
    employeeId: null,
    employeeFullName: '',
    employeeEstado: '',
    employeeActivo: '',
    empleadoMeta: {
        codigo: '',
        sede: '',
        establecimiento: '',
    },
    vacations: [],
    summary: null,
    editingVacationId: null,
};

function sanitizeAttr(value) {
    if (value === undefined || value === null) {
        return '';
    }
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

const API_FETCH_DEFAULTS = Object.freeze({
    credentials: 'same-origin',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

function resolveApiUrl(endpoint) {
    try {
        return new URL(endpoint, window.location.href).toString();
    } catch (error) {
        console.warn('No se pudo resolver la URL para el endpoint solicitado:', endpoint, error);
        return endpoint;
    }
}

async function fetchJson(endpoint, options = {}) {
    const url = resolveApiUrl(endpoint);
    const mergedOptions = {
        ...API_FETCH_DEFAULTS,
        ...options,
        headers: {
            ...API_FETCH_DEFAULTS.headers,
            ...(options.headers || {}),
        },
    };

    const response = await fetch(url, mergedOptions);
    if (!response.ok) {
        const error = new Error(`HTTP ${response.status} - ${response.statusText}`);
        error.status = response.status;
        throw error;
    }

    return response.json();
}

// ===================================================================
// 1. INICIALIZACIÓN Y CONFIGURACIÓN
// ===================================================================

document.addEventListener('DOMContentLoaded', function () {
    initializePagination();
    cargarSedesEmpleado();
    loadEmployees();
    setupEventListeners();
    setupModalListeners();
    setupExportListener();
    setupVacationModule();
});

// ===================================================================
// 2. SISTEMA DE PAGINACIÓN
// ===================================================================

function initializePagination() {
    if (document.getElementById('paginationControls')) return;

    const limitContainer = document.createElement('div');
    limitContainer.className = 'pagination-controls';
    limitContainer.innerHTML = `
        <div class="limit-selector">
            <label for="limitSelector">Mostrar:</label>
            <select id="limitSelector" class="form-control limit-select">
                ${AVAILABLE_LIMITS.map(limit => 
                    `<option value="${limit}" ${limit === currentLimit ? 'selected' : ''}>${limit} registros</option>`
                ).join('')}
            </select>
        </div>
        <div class="pagination-info">
            <span id="paginationInfo">Cargando...</span>
        </div>
        <div class="pagination-buttons" id="paginationButtons">
            <!-- Los botones se generan dinámicamente -->
        </div>
    `;
    
    const tableContainer = document.querySelector('.employee-table-container');
    tableContainer.parentNode.insertBefore(limitContainer, tableContainer);
}

function setupEventListeners() {
    // Selector de límite
    const limitSelector = document.getElementById('limitSelector');
    if (limitSelector) {
        limitSelector.addEventListener('change', function() {
            currentLimit = parseInt(this.value);
            currentPage = 1;
            loadEmployees();
        });
    }

    // Formulario de filtros
    const form = document.getElementById('employeeQueryForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            updateFiltersFromForm();
            loadEmployees();
        });
    }

    // Botón limpiar filtros
    const clearBtn = document.getElementById('btnClearEmployeeQuery');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearFilters();
            loadEmployees();
        });
    }

    // Cambio de sede
    const sedeSelect = document.getElementById('q_sede');
    if (sedeSelect) {
        sedeSelect.addEventListener('change', function() {
            cargarEstablecimientosEmpleado();
            setTimeout(() => {
                currentPage = 1;
                updateFiltersFromForm();
                loadEmployees();
            }, 100);
        });
    }

    // Cambio de establecimiento
    const estSelect = document.getElementById('q_establecimiento');
    if (estSelect) {
        estSelect.addEventListener('change', function() {
            currentPage = 1;
            updateFiltersFromForm();
            loadEmployees();
        });
    }

    // Delegar acciones adicionales en la tabla de empleados (vacaciones)
    const employeeTableBody = document.getElementById('employeeTableBody');
    if (employeeTableBody && !employeeTableBody.dataset.vacationListener) {
        employeeTableBody.addEventListener('click', handleEmployeeRowAction);
        employeeTableBody.dataset.vacationListener = 'true';
    }
}

function updateFiltersFromForm() {
    const form = document.getElementById('employeeQueryForm');
    if (form) {
        currentFilters = {
            codigo: document.getElementById('q_codigo')?.value || '',
            identificacion: document.getElementById('q_identificacion')?.value || '',
            nombre: document.getElementById('q_nombre')?.value || '',
            sede: document.getElementById('q_sede')?.value || '',
            establecimiento: document.getElementById('q_establecimiento')?.value || '',
            estado: document.getElementById('q_estado')?.value || ''
        };
        
        // Remover filtros vacíos
        Object.keys(currentFilters).forEach(key => {
            if (!currentFilters[key]) {
                delete currentFilters[key];
            }
        });
    }
}

function clearFilters() {
    const form = document.getElementById('employeeQueryForm');
    if (form) {
        form.reset();
    }
    currentFilters = {};
    currentPage = 1;
}

// ===================================================================
// 3. CARGA DE DATOS CON PAGINACIÓN
// ===================================================================

async function loadEmployees() {
    try {
        showLoadingState();
        
        const params = new URLSearchParams({
            page: currentPage,
            limit: currentLimit,
            ...currentFilters
        });

        const response = await fetch(`api/employee/list.php?${params.toString()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        if (data.success) {
            renderEmployeeTable(data.data);
            updatePaginationInfo(data.pagination);
            renderPaginationButtons(data.pagination);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
        
    } catch (error) {
        console.error('Error al cargar empleados:', error);
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
                    <button onclick="loadEmployees()" class="btn-retry">Reintentar</button>
                </td>
            </tr>
        `;
    }
}

// ===================================================================
// 4. RENDERIZADO DE TABLA (MEJORADO CON PAGINACIÓN)
// ===================================================================

function renderEmployeeTable(data) {
    const tbody = document.getElementById('employeeTableBody');
    
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!data || !data.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data-state">
                    <i class="fas fa-users"></i>
                    No se encontraron empleados con los filtros aplicados
                </td>
            </tr>
        `;
        return;
    }

    data.forEach(emp => {
        // Format date if available
        let fechaFormateada = '';
        if (emp.FECHA_INGRESO) {
            try {
                const fecha = new Date(emp.FECHA_INGRESO + 'T00:00:00');
                fechaFormateada = fecha.toLocaleDateString('es-CO', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            } catch (e) {
                fechaFormateada = emp.FECHA_INGRESO;
            }
        }

        const estadoCodigo = String(emp.ESTADO || '').toUpperCase();
        const activoFlag = String(emp.ACTIVO || '').toUpperCase();
        const estadoTexto = mapEmployeeEstado(estadoCodigo, activoFlag) || (emp.ESTADO_DESCRIPTIVO || estadoCodigo || '');
        const estadoEsActivo = estadoCodigo === 'A' && activoFlag === 'S';
        const estadoClass = estadoEsActivo ? 'status-active' : 'status-inactive';
        
        tbody.innerHTML += `
            <tr>
                <td>${emp.ID_EMPLEADO ?? ''}</td>
                <td>${emp.DNI ?? ''}</td>
                <td>${(emp.NOMBRE ?? '') + ' ' + (emp.APELLIDO ?? '')}</td>
                <td>${emp.CORREO ?? ''}</td>
                <td>${emp.ESTABLECIMIENTO ?? ''}</td>
                <td>${emp.SEDE ?? ''}</td>
                <td>${fechaFormateada}</td>
                <td>
                    <span class="${estadoClass}">
                        ${estadoTexto || '—'}
                    </span>
                </td>
                <td>
                    <button class="btn-icon btn-vacation" title="Vacaciones" data-action="vacation"
                        data-id="${sanitizeAttr(emp.ID_EMPLEADO ?? '')}"
                        data-nombre="${sanitizeAttr(emp.NOMBRE ?? '')}"
                        data-apellido="${sanitizeAttr(emp.APELLIDO ?? '')}"
                        data-estado="${sanitizeAttr(emp.ESTADO ?? '')}"
                        data-activo="${sanitizeAttr(emp.ACTIVO ?? '')}"
                        data-establecimiento="${sanitizeAttr(emp.ESTABLECIMIENTO ?? '')}"
                        data-sede="${sanitizeAttr(emp.SEDE ?? '')}">
                        <i class="fas fa-umbrella-beach"></i>
                    </button>
                    <button class="btn-icon btn-edit" title="Editar" onclick="editarEmpleado('${emp.ID_EMPLEADO}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" title="Eliminar" onclick="eliminarEmpleado('${emp.ID_EMPLEADO}','${emp.NOMBRE ?? ''} ${emp.APELLIDO ?? ''}')">
                        <i class="fas fa-trash"></i>
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
        loadEmployees();
    }
}

// ===================================================================
// 6. FUNCIONES DE SEDES Y ESTABLECIMIENTOS (MANTIENEN COMPATIBILIDAD)
// ===================================================================

async function cargarSedesEmpleado() {
    const sedeSelect = document.getElementById('q_sede');

    try {
        const res = await fetchJson('api/get-sedes.php');
        if (sedeSelect) {
            sedeSelect.innerHTML = '<option value="">Seleccionar una Sede</option>';
            if (res.sedes && res.sedes.length > 0) {
                res.sedes.forEach(sede => {
                    const option = document.createElement('option');
                    option.value = sede.ID_SEDE;
                    option.textContent = sede.NOMBRE;
                    sedeSelect.appendChild(option);
                });
            }
            sedeSelect.value = '';
            await cargarEstablecimientosEmpleado();
        }
    } catch (error) {
        console.error('Error al cargar sedes:', error);
        if (sedeSelect) {
            sedeSelect.innerHTML = '<option value="">Error al cargar sedes</option>';
        }
    }
}

async function cargarEstablecimientosEmpleado() {
    const establecimientoSelect = document.getElementById('q_establecimiento');
    if (!establecimientoSelect) return;

    establecimientoSelect.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
    establecimientoSelect.disabled = true;

    const sedeId = document.getElementById('q_sede')?.value;
    if (!sedeId) {
        establecimientoSelect.disabled = false;
        return;
    }

    try {
        const res = await fetchJson(`api/get-establecimientos.php?sede_id=${encodeURIComponent(sedeId)}`);
        if (res.success && Array.isArray(res.establecimientos) && res.establecimientos.length > 0) {
            res.establecimientos.forEach(est => {
                const option = document.createElement('option');
                option.value = est.ID_ESTABLECIMIENTO;
                option.textContent = est.NOMBRE;
                establecimientoSelect.appendChild(option);
            });
            establecimientoSelect.value = '';
            establecimientoSelect.value = '';
        } else {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'Sin establecimientos disponibles';
            emptyOption.disabled = true;
            establecimientoSelect.appendChild(emptyOption);
            establecimientoSelect.value = '';
        }
    } catch (error) {
        console.error('Error al cargar establecimientos:', error);
        const errorOption = document.createElement('option');
        errorOption.value = '';
        errorOption.textContent = 'Error al cargar establecimientos';
        errorOption.disabled = true;
        establecimientoSelect.appendChild(errorOption);
        establecimientoSelect.value = '';
    } finally {
        establecimientoSelect.disabled = false;
    }
}

// ===================================================================
// 7. GESTIÓN DE MODALES (MANTIENE FUNCIONALIDAD ORIGINAL)
// ===================================================================

function setupModalListeners() {
    // Botón agregar empleado
    const btnAdd = document.getElementById('btnAddEmployee');
    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            openEmployeeModal('crear');
        });
    }

    // Listeners para cerrar modal de empleado
    const closeBtn = document.getElementById('closeEmployeeModal');
    const cancelBtn = document.getElementById('cancelEmployeeModal');
    const modal = document.getElementById('employeeModal');
    
    if (closeBtn) closeBtn.addEventListener('click', closeEmployeeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeEmployeeModal);
    if (modal) {
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) closeEmployeeModal();
        });
    }

    // Listeners para modal de eliminación
    setupDeleteModalListeners();

    // Formulario de registro/edición
    const form = document.getElementById('employeeRegisterForm');
    if (form) {
        form.addEventListener('submit', handleEmployeeFormSubmit);
    }

    // Selector de sede en modal
    const sedeEmpleado = document.getElementById('sedeEmpleado');
    if (sedeEmpleado) {
        sedeEmpleado.addEventListener('change', function() {
            cargarEstablecimientosRegistro();
        });
    }
}

function openEmployeeModal(mode, empleado = null) {
    console.log('openEmployeeModal llamado con modo:', mode, 'empleado:', empleado);
    const modal = document.getElementById('employeeModal');
    const form = document.getElementById('employeeRegisterForm');
    const errorDiv = document.getElementById('employeeFormError');
    
    if (!modal || !form) {
        console.error('Modal o formulario no encontrado');
        return;
    }
    
    modal.classList.add('show');
    form.reset();
    if (errorDiv) errorDiv.style.display = 'none';

    if (mode === 'editar' && empleado) {
        console.log('Modo editar - llenando campos con:', empleado);
        document.getElementById('employeeModalTitle').textContent = 'Editar Empleado';
        document.getElementById('employeeModalSubmitBtn').textContent = 'Guardar Cambios';
        document.getElementById('modoEmpleado').value = 'editar';
        
        // Verificar y llenar cada campo individualmente
        const idEmpleadoField = document.getElementById('id_empleado');
        if (idEmpleadoField) {
            idEmpleadoField.value = empleado.ID_EMPLEADO;
            idEmpleadoField.readOnly = true;
            console.log('ID_EMPLEADO establecido:', empleado.ID_EMPLEADO);
        } else {
            console.error('Campo id_empleado no encontrado');
        }
        
        const dniField = document.getElementById('dni');
        if (dniField) {
            dniField.value = empleado.DNI || '';
            console.log('DNI establecido:', empleado.DNI);
        } else {
            console.error('Campo dni no encontrado');
        }
        
        const nombreField = document.getElementById('nombre');
        if (nombreField) {
            nombreField.value = empleado.NOMBRE || '';
            console.log('NOMBRE establecido:', empleado.NOMBRE);
        } else {
            console.error('Campo nombre no encontrado');
        }
        
        const apellidoField = document.getElementById('apellido');
        if (apellidoField) {
            apellidoField.value = empleado.APELLIDO || '';
            console.log('APELLIDO establecido:', empleado.APELLIDO);
        } else {
            console.error('Campo apellido no encontrado');
        }
        
        const correoField = document.getElementById('correo');
        if (correoField) {
            correoField.value = empleado.CORREO || '';
            console.log('CORREO establecido:', empleado.CORREO);
        } else {
            console.error('Campo correo no encontrado');
        }
        
        const telefonoField = document.getElementById('telefono');
        if (telefonoField) {
            telefonoField.value = empleado.TELEFONO || '';
            console.log('TELEFONO establecido:', empleado.TELEFONO);
        } else {
            console.error('Campo telefono no encontrado');
        }
        
        const fechaIngresoField = document.getElementById('fecha_ingreso');
        if (fechaIngresoField) {
            fechaIngresoField.value = empleado.FECHA_INGRESO || '';
            console.log('FECHA_INGRESO establecido:', empleado.FECHA_INGRESO);
        } else {
            console.error('Campo fecha_ingreso no encontrado');
        }
        
        const estadoField = document.getElementById('estado');
        if (estadoField) {
            estadoField.value = empleado.ESTADO || 'A';
            console.log('ESTADO establecido:', empleado.ESTADO);
        } else {
            console.error('Campo estado no encontrado');
        }

        console.log('Cargando sedes con ID_SEDE:', empleado.ID_SEDE, 'ID_ESTABLECIMIENTO:', empleado.ID_ESTABLECIMIENTO);
        cargarSedesRegistro(empleado.ID_SEDE, empleado.ID_ESTABLECIMIENTO);
    } else {
        document.getElementById('employeeModalTitle').textContent = 'Registrar Empleado';
        document.getElementById('employeeModalSubmitBtn').textContent = 'Registrar';
        document.getElementById('modoEmpleado').value = 'crear';
        document.getElementById('id_empleado').readOnly = false;
        cargarSedesRegistro();
    }
}

function closeEmployeeModal() {
    const modal = document.getElementById('employeeModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

async function cargarSedesRegistro(selectedSede = '', selectedEstablecimiento = '') {
    const sedeSelect = document.getElementById('sedeEmpleado');

    try {
        const res = await fetchJson('api/get-sedes.php');
        if (sedeSelect) {
            sedeSelect.innerHTML = '<option value="">Seleccione sede</option>';
            if (res.sedes && res.sedes.length > 0) {
                res.sedes.forEach(sede => {
                    const option = document.createElement('option');
                    option.value = sede.ID_SEDE;
                    option.textContent = sede.NOMBRE;
                    if (String(selectedSede) === String(sede.ID_SEDE)) {
                        option.selected = true;
                    }
                    sedeSelect.appendChild(option);
                });
            }
            await cargarEstablecimientosRegistro(selectedEstablecimiento);
        }
    } catch (error) {
        console.error('Error al cargar sedes para registro:', error);
        if (sedeSelect) {
            sedeSelect.innerHTML = '<option value="">Error al cargar sedes</option>';
        }
    }
}

async function cargarEstablecimientosRegistro(selectedEstablecimiento = '') {
    const sedeId = document.getElementById('sedeEmpleado')?.value;
    const establecimientoSelect = document.getElementById('establecimientoEmpleado');

    if (!establecimientoSelect) return;

    establecimientoSelect.innerHTML = '<option value="">Seleccione establecimiento</option>';
    establecimientoSelect.disabled = true;

    if (!sedeId) {
        establecimientoSelect.disabled = false;
        return;
    }

    try {
        const res = await fetchJson(`api/get-establecimientos.php?sede_id=${encodeURIComponent(sedeId)}`);
        if (res.success && Array.isArray(res.establecimientos) && res.establecimientos.length > 0) {
            res.establecimientos.forEach(est => {
                const option = document.createElement('option');
                option.value = est.ID_ESTABLECIMIENTO;
                option.textContent = est.NOMBRE;
                if (selectedEstablecimiento && String(selectedEstablecimiento) === String(est.ID_ESTABLECIMIENTO)) {
                    option.selected = true;
                }
                establecimientoSelect.appendChild(option);
            });
            if (!selectedEstablecimiento) {
                establecimientoSelect.value = '';
            }
        } else {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'Sin establecimientos disponibles';
            emptyOption.disabled = true;
            establecimientoSelect.appendChild(emptyOption);
            establecimientoSelect.value = '';
        }
    } catch (error) {
        console.error('Error al cargar establecimientos:', error);
        const errorOption = document.createElement('option');
        errorOption.value = '';
        errorOption.textContent = 'Error al cargar establecimientos';
        errorOption.disabled = true;
        establecimientoSelect.appendChild(errorOption);
        establecimientoSelect.value = '';
    } finally {
        establecimientoSelect.disabled = false;
    }
}

function handleEmployeeFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const errorDiv = document.getElementById('employeeFormError');
    
    if (errorDiv) errorDiv.style.display = 'none';

    // Validación de campos
    const requiredFields = ['id_empleado', 'nombre', 'apellido', 'dni', 'correo', 'sede', 'establecimiento', 'fecha_ingreso', 'estado'];
    const missingFields = requiredFields.filter(field => !form[field]?.value);
    
    if (missingFields.length > 0) {
        if (errorDiv) {
            errorDiv.textContent = "Todos los campos requeridos deben estar completos.";
            errorDiv.style.display = 'block';
        }
        return;
    }

    const modo = form.modo.value;
    const url = modo === 'editar' ? 'api/employee/update.php' : 'api/employee/register.php';
    
    fetch(url, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeEmployeeModal();
            loadEmployees(); // Recargar con paginación
        } else {
            if (errorDiv) {
                errorDiv.textContent = res.message || "No se pudo registrar/editar el empleado.";
                errorDiv.style.display = 'block';
            }
        }
    })
    .catch(() => {
        if (errorDiv) {
            errorDiv.textContent = "Error de conexión con el servidor.";
            errorDiv.style.display = 'block';
        }
    });
}

// ===================================================================
// 8. FUNCIONES DE EDICIÓN Y ELIMINACIÓN (MANTIENEN FUNCIONALIDAD)
// ===================================================================

window.editarEmpleado = function(id) {
    console.log('Editando empleado con ID:', id);
    fetch('api/employee/get.php?id=' + encodeURIComponent(id))
        .then(r => {
            console.log('Respuesta HTTP:', r.status);
            return r.json();
        })
        .then(res => {
            console.log('Datos recibidos:', res);
            if (res.success && res.data) {
                console.log('Datos del empleado:', res.data);
                openEmployeeModal('editar', res.data);
            } else {
                console.error('Error en respuesta:', res.message);
                alert('Error al cargar datos del empleado: ' + (res.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al cargar empleado:', error);
            alert('Error al cargar datos del empleado');
        });
}

function setupDeleteModalListeners() {
    const closeBtn = document.getElementById('closeEmployeeDeleteModal');
    const cancelBtn = document.getElementById('cancelDeleteEmployeeBtn');
    const verifyBtn = document.getElementById('verifyDeleteEmployeeBtn');
    const confirmBtn = document.getElementById('confirmDeleteEmployeeBtn');
    const modal = document.getElementById('employeeDeleteModal');
    
    if (closeBtn) closeBtn.addEventListener('click', closeEmployeeDeleteModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeEmployeeDeleteModal);
    if (modal) {
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) closeEmployeeDeleteModal();
        });
    }
    
    if (verifyBtn) {
        verifyBtn.addEventListener('click', function() {
            document.getElementById('deleteStep1').style.display = 'none';
            document.getElementById('deleteStep2').style.display = '';
            document.getElementById('verifyDeleteEmployeeBtn').style.display = 'none';
            document.getElementById('confirmDeleteEmployeeBtn').style.display = '';
        });
    }
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (!empleadoAEliminar) return;
            
            fetch('api/employee/delete.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'id_empleado=' + encodeURIComponent(empleadoAEliminar)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    closeEmployeeDeleteModal();
                    loadEmployees(); // Recargar con paginación
                } else {
                    alert(res.message || 'No se pudo eliminar el empleado');
                }
            })
            .catch(error => {
                console.error('Error al eliminar empleado:', error);
                alert('Error de conexión al eliminar empleado');
            });
        });
    }
}

window.eliminarEmpleado = function(id, nombre) {
    empleadoAEliminar = id;
    const modal = document.getElementById('employeeDeleteModal');
    if (modal) {
        modal.classList.add('show');
        document.getElementById('deleteStep1').style.display = '';
        document.getElementById('deleteStep2').style.display = 'none';
        document.getElementById('verifyDeleteEmployeeBtn').style.display = '';
        document.getElementById('confirmDeleteEmployeeBtn').style.display = 'none';
    }
}

function closeEmployeeDeleteModal() {
    const modal = document.getElementById('employeeDeleteModal');
    if (modal) {
        modal.classList.remove('show');
    }
    empleadoAEliminar = null;
}

// ===================================================================
// 9. EXPORTACIÓN XLSX (USANDO PHPSPREADSHEET CON ESTILOS PROFESIONALES)
// ===================================================================

function setupExportListener() {
    const exportBtn = document.getElementById('btnExportXLS');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            // Usar los filtros actuales (sin paginación)
            const params = new URLSearchParams(currentFilters);
            
            // Crear enlace de descarga que apunta a la API de exportación
            const link = document.createElement('a');
            link.href = `api/employee/export.php?${params.toString()}`;
            link.download = 'empleados.xlsx';
            
            // Ejecutar descarga
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            console.log('🔄 Exportando empleados con filtros:', currentFilters);
        });
    }
}

// ===================================================================
// 10. FUNCIONES DEL MODAL DE AYUDA
// ===================================================================

function showEmployeeHelpModal() {
    const modal = document.getElementById('employeeHelpModal');
    if (modal) {
        modal.classList.add('show');
        // Mostrar el primer tab por defecto
        switchEmployeeHelpTab('general');
        
        // Agregar listener para cerrar con Escape
        document.addEventListener('keydown', handleEmployeeHelpModalKeydown);
        
        // Agregar listener para cerrar al hacer clic fuera
        modal.addEventListener('click', handleEmployeeHelpModalOutsideClick);
    }
}

function closeEmployeeHelpModal() {
    const modal = document.getElementById('employeeHelpModal');
    if (modal) {
        modal.classList.remove('show');
        
        // Remover listeners
        document.removeEventListener('keydown', handleEmployeeHelpModalKeydown);
        modal.removeEventListener('click', handleEmployeeHelpModalOutsideClick);
    }
}

function handleEmployeeHelpModalKeydown(event) {
    if (event.key === 'Escape') {
        closeEmployeeHelpModal();
    }
}

function handleEmployeeHelpModalOutsideClick(event) {
    const modal = document.getElementById('employeeHelpModal');
    if (event.target === modal) {
        closeEmployeeHelpModal();
    }
}

function switchEmployeeHelpTab(tabName) {
    // Ocultar todos los contenidos
    const contents = document.querySelectorAll('#employeeHelpModal .help-content');
    contents.forEach(content => content.classList.remove('active'));
    
    // Remover clase active de todos los tabs
    const tabs = document.querySelectorAll('#employeeHelpModal .help-tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Mostrar el contenido seleccionado
    const selectedContent = document.getElementById(`employee-help-${tabName}`);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }
    
    // Activar el tab seleccionado
    const selectedTab = document.querySelector(`#employeeHelpModal .help-tab[onclick*="${tabName}"]`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
}

// ===================================================================
// 11. MÓDULO DE VACACIONES
// ===================================================================

function setupVacationModule() {
    const modal = document.getElementById('employeeVacationModal');
    if (!modal) {
        return;
    }

    const closeBtn = document.getElementById('closeVacationModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeVacationModal);
    }

    modal.addEventListener('mousedown', function(event) {
        if (event.target === modal) {
            closeVacationModal();
        }
    });

    const newBtn = document.getElementById('btnNewVacation');
    if (newBtn) {
        newBtn.addEventListener('click', function(event) {
            event.preventDefault();
            startVacationCreation();
        });
    }

    const form = document.getElementById('vacationForm');
    if (form) {
        form.addEventListener('submit', handleVacationFormSubmit);
    }

    const resetBtn = document.getElementById('vacationFormReset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(event) {
            event.preventDefault();
            resetVacationForm();
        });
    }

    const cancelEditBtn = document.getElementById('cancelVacationEdit');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function(event) {
            event.preventDefault();
            resetVacationForm();
        });
    }

    const table = document.getElementById('vacationTable');
    if (table) {
        table.addEventListener('click', handleVacationTableAction);
    }
}

function handleEmployeeRowAction(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) {
        return;
    }

    if (button.dataset.action === 'vacation') {
        event.preventDefault();
        const employeeId = parseInt(button.dataset.id, 10);
        if (!employeeId) {
            return;
        }

        openVacationModal({
            id: employeeId,
            nombre: button.dataset.nombre || '',
            apellido: button.dataset.apellido || '',
            estado: button.dataset.estado || '',
            activo: button.dataset.activo || '',
            establecimiento: button.dataset.establecimiento || '',
            sede: button.dataset.sede || '',
        });
    }
}

function openVacationModal(employeeData) {
    const modal = document.getElementById('employeeVacationModal');
    if (!modal) {
        return;
    }

    vacationState.employeeId = employeeData.id;
    const fullName = `${employeeData.nombre} ${employeeData.apellido}`.trim() || `Empleado #${employeeData.id}`;
    vacationState.employeeFullName = fullName;
    vacationState.employeeEstado = employeeData.estado || '';
    vacationState.employeeActivo = employeeData.activo || '';
    vacationState.empleadoMeta = {
        codigo: employeeData.id,
        sede: employeeData.sede || '',
        establecimiento: employeeData.establecimiento || '',
    };
    vacationState.editingVacationId = null;

    const nameEl = document.getElementById('vacationEmployeeName');
    if (nameEl) {
        nameEl.textContent = fullName;
    }

    const metaEl = document.getElementById('vacationEmployeeMeta');
    if (metaEl) {
        const parts = [];
        parts.push(`Código ${employeeData.id}`);
        if (employeeData.sede) {
            parts.push(`Sede ${employeeData.sede}`);
        }
        if (employeeData.establecimiento) {
            parts.push(`Establecimiento ${employeeData.establecimiento}`);
        }
        const estado = mapEmployeeEstado(employeeData.estado, employeeData.activo);
        if (estado) {
            parts.push(`Estado actual: ${estado}`);
        }
        metaEl.textContent = parts.join(' · ');
    }

    modal.classList.add('show');
    resetVacationForm();
    setVacationLoading(true);
    loadVacationsForEmployee();
}

function closeVacationModal() {
    const modal = document.getElementById('employeeVacationModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function setVacationLoading(isLoading) {
    const loadingEl = document.getElementById('vacationLoading');
    const contentEl = document.getElementById('vacationContent');
    if (loadingEl) {
        loadingEl.style.display = isLoading ? 'flex' : 'none';
    }
    if (contentEl) {
        if (isLoading) {
            contentEl.style.display = 'none';
        } else {
            contentEl.style.display = 'flex';
            contentEl.scrollTop = 0;
        }
    }
}

async function loadVacationsForEmployee() {
    if (!vacationState.employeeId) {
        return;
    }

    try {
        setVacationLoading(true);
        const url = `${VACATION_API_ROUTES.list}?id_empleado=${encodeURIComponent(vacationState.employeeId)}`;
        const response = await fetch(url, buildAjaxOptions());
        const payload = await parseJsonResponse(response);

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'No se pudieron cargar las vacaciones.');
        }

        vacationState.vacations = Array.isArray(payload.data) ? payload.data : [];
        vacationState.summary = payload.summary || null;

        renderVacationSummary(vacationState.summary);
        renderVacationTable(vacationState.vacations);
        setVacationLoading(false);
    } catch (error) {
        console.error('Error al cargar vacaciones:', error);
        vacationState.vacations = [];
        vacationState.summary = null;
        renderVacationSummary(null);
        renderVacationTable([]);
        setVacationLoading(false);
        showVacationAlert(error.message || 'No se pudieron cargar las vacaciones.', 'error', 6000);
    }
}

function renderVacationSummary(summary) {
    const container = document.getElementById('vacationSummaryCards');
    if (!container) {
        return;
    }

    if (!summary) {
        container.innerHTML = '';
        return;
    }

    const cards = [
        { label: 'Activas', value: summary.activos || 0, icon: 'fa-sun', className: 'card-activo' },
        { label: 'Programadas', value: summary.programados || 0, icon: 'fa-calendar-check', className: 'card-programado' },
        { label: 'Finalizadas', value: summary.finalizados || 0, icon: 'fa-circle-check', className: 'card-finalizado' },
        { label: 'Canceladas', value: summary.cancelados || 0, icon: 'fa-ban', className: 'card-cancelado' },
    ];

    const cardsHtml = cards.map(function(card) {
        return `
            <div class="vacation-card ${card.className}">
                <div class="vacation-card-icon"><i class="fas ${card.icon}"></i></div>
                <div class="vacation-card-info">
                    <span class="vacation-card-value">${card.value}</span>
                    <span class="vacation-card-label">${card.label}</span>
                </div>
            </div>
        `;
    }).join('');

    let nextHtml = '';
    if (summary.proxima_vacacion) {
        const prox = summary.proxima_vacacion;
        nextHtml = `
            <div class="vacation-next">
                <div class="vacation-next-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="vacation-next-info">
                    <span class="vacation-next-title">Próxima vacación</span>
                    <span class="vacation-next-range">${formatPeriod(prox.fecha_inicio, prox.fecha_fin)}</span>
                    <span class="vacation-next-days">${formatDaysLabel(prox)}</span>
                </div>
            </div>
        `;
    }

    container.innerHTML = cardsHtml + nextHtml;
}

function renderVacationTable(vacations) {
    const tbody = document.querySelector('#vacationTable tbody');
    const emptyState = document.getElementById('vacationEmptyState');
    if (!tbody || !emptyState) {
        return;
    }

    if (!Array.isArray(vacations) || vacations.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'flex';
        return;
    }

    emptyState.style.display = 'none';

    const rows = vacations.map(function(vacation) {
        const status = vacation.estado || 'PROGRAMADO';
        const badgeClass = VACATION_BADGE_CLASSES[status] || '';
        const statusLabel = VACATION_STATUS_LABELS[status] || status;
        const period = formatPeriod(vacation.fecha_inicio, vacation.fecha_fin);
        const diasLabel = formatDaysLabel(vacation);
        const motivo = (vacation.motivo && vacation.motivo.trim()) ? sanitizeAttr(vacation.motivo) : '—';
        const observaciones = (vacation.observaciones && vacation.observaciones.trim()) ? sanitizeAttr(vacation.observaciones) : '—';
        const reactiva = vacation.reactivacion_automatica ? 'Sí' : 'No';
        const isEditing = vacationState.editingVacationId === vacation.id_vacacion;
        const rowClass = [
            status === 'ACTIVO' ? 'vacation-row-active' : '',
            isEditing ? 'vacation-row-editing' : '',
        ].filter(Boolean).join(' ');

        return `
            <tr class="${rowClass}">
                <td>
                    <span class="vacation-status ${badgeClass}">${statusLabel}</span>
                </td>
                <td>
                    <div class="vacation-period">${period}</div>
                    <div class="vacation-updated">Actualizado: ${formatDateToLocale(vacation.fecha_actualizacion || vacation.fecha_creacion)}</div>
                </td>
                <td>
                    <div class="vacation-days">${diasLabel}</div>
                    <div class="vacation-reactivation ${vacation.reactivacion_automatica ? 'is-on' : 'is-off'}">
                        <i class="fas fa-rotate-right"></i> Reactivar automáticamente: ${reactiva}
                    </div>
                </td>
                <td>${motivo}</td>
                <td>${observaciones}</td>
                <td>
                    <div class="vacation-actions">
                        ${renderVacationActions(vacation)}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    tbody.innerHTML = rows;
}

function renderVacationActions(vacation) {
    const status = vacation.estado || 'PROGRAMADO';
    const id = vacation.id_vacacion;
    const base = `data-id="${id}"`;
    let actions = '';

    if (status === 'PROGRAMADO') {
        actions += `
            <button type="button" class="vacation-action" data-vacation-action="edit" ${base} title="Editar vacación">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="vacation-action" data-vacation-action="activate" ${base} title="Activar vacación">
                <i class="fas fa-play"></i>
            </button>
            <button type="button" class="vacation-action" data-vacation-action="cancel" ${base} title="Cancelar vacación">
                <i class="fas fa-ban"></i>
            </button>
        `;
    } else if (status === 'ACTIVO') {
        actions += `
            <button type="button" class="vacation-action" data-vacation-action="edit" ${base} title="Editar vacación">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="vacation-action" data-vacation-action="finalize" ${base} title="Finalizar vacación">
                <i class="fas fa-check"></i>
            </button>
            <button type="button" class="vacation-action" data-vacation-action="cancel" ${base} title="Cancelar vacación">
                <i class="fas fa-ban"></i>
            </button>
        `;
    } else {
        actions += '<span class="vacation-no-actions">—</span>';
    }

    return actions;
}

function startVacationCreation() {
    resetVacationForm();
    const container = document.getElementById('vacationFormContainer');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    const startInput = document.getElementById('vacationStartDate');
    if (startInput) {
        startInput.focus();
    }
}

function handleVacationTableAction(event) {
    const button = event.target.closest('button[data-vacation-action]');
    if (!button) {
        return;
    }

    const action = button.dataset.vacationAction;
    const vacationId = parseInt(button.dataset.id, 10);
    if (!vacationId) {
        return;
    }

    const vacation = vacationState.vacations.find(function(item) {
        return item.id_vacacion === vacationId;
    });

    if (!vacation) {
        return;
    }

    if (action === 'edit') {
        populateVacationForm(vacation);
    } else if (action === 'activate') {
        if (confirm('¿Deseas activar esta vacación? Esto inactivará temporalmente al empleado.')) {
            changeVacationStatus(vacation, 'ACTIVO');
        }
    } else if (action === 'finalize') {
        if (confirm('¿Deseas finalizar esta vacación?')) {
            changeVacationStatus(vacation, 'FINALIZADO');
        }
    } else if (action === 'cancel') {
        if (confirm('¿Deseas cancelar esta vacación?')) {
            changeVacationStatus(vacation, 'CANCELADO');
        }
    }
}

function populateVacationForm(vacation) {
    const form = document.getElementById('vacationForm');
    if (!form) {
        return;
    }

    document.getElementById('vacationFormMode').value = 'edit';
    document.getElementById('vacationId').value = vacation.id_vacacion;
    document.getElementById('vacationStartDate').value = vacation.fecha_inicio;
    document.getElementById('vacationEndDate').value = vacation.fecha_fin;
    document.getElementById('vacationReactiveToggle').checked = !!vacation.reactivacion_automatica;
    document.getElementById('vacationReason').value = vacation.motivo || '';
    document.getElementById('vacationNotes').value = vacation.observaciones || '';

    const title = document.getElementById('vacationFormTitle');
    if (title) {
        title.innerHTML = '<i class="fas fa-edit"></i> Editar vacación';
    }

    const cancelBtn = document.getElementById('cancelVacationEdit');
    if (cancelBtn) {
        cancelBtn.style.display = 'inline-flex';
    }

    setVacationFormMessage(null);
    vacationState.editingVacationId = vacation.id_vacacion;
    const container = document.getElementById('vacationFormContainer');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function resetVacationForm(clearMessages) {
    const form = document.getElementById('vacationForm');
    if (!form) {
        return;
    }

    form.reset();
    document.getElementById('vacationFormMode').value = 'create';
    document.getElementById('vacationId').value = '';
    document.getElementById('vacationReactiveToggle').checked = true;

    const title = document.getElementById('vacationFormTitle');
    if (title) {
        title.innerHTML = '<i class="fas fa-calendar-plus"></i> Registrar nuevas vacaciones';
    }

    const cancelBtn = document.getElementById('cancelVacationEdit');
    if (cancelBtn) {
        cancelBtn.style.display = 'none';
    }

    vacationState.editingVacationId = null;

    if (clearMessages === undefined || clearMessages === true) {
        setVacationFormMessage(null);
    }
}

function handleVacationFormSubmit(event) {
    event.preventDefault();
    if (!vacationState.employeeId) {
        setVacationFormMessage('error', 'Selecciona primero un empleado.');
        return;
    }

    const form = event.target;
    const mode = document.getElementById('vacationFormMode').value || 'create';

    const fechaInicio = form.fecha_inicio.value;
    const fechaFin = form.fecha_fin.value;
    const motivo = form.motivo.value.trim();
    const observaciones = form.observaciones.value.trim();
    const reactivar = form.reactivacion_automatica.checked;

    if (!fechaInicio || !fechaFin) {
        setVacationFormMessage('error', 'Las fechas de inicio y fin son obligatorias.');
        return;
    }

    if (fechaFin < fechaInicio) {
        setVacationFormMessage('error', 'La fecha de fin no puede ser anterior a la fecha de inicio.');
        return;
    }

    const payload = {
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        motivo: motivo !== '' ? motivo : null,
        observaciones: observaciones !== '' ? observaciones : null,
        reactivacion_automatica: reactivar,
    };

    if (mode === 'edit') {
        const id = parseInt(document.getElementById('vacationId').value, 10);
        if (!id) {
            setVacationFormMessage('error', 'No se pudo identificar la vacación a actualizar.');
            return;
        }
        payload.id_vacacion = id;
    } else {
        payload.id_empleado = vacationState.employeeId;
    }

    submitVacationForm(mode, payload);
}

async function submitVacationForm(mode, payload) {
    const submitBtn = document.getElementById('vacationFormSubmit');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');
    }

    try {
        const route = mode === 'edit' ? VACATION_API_ROUTES.update : VACATION_API_ROUTES.create;
        const response = await fetch(route, buildAjaxOptions({
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        }));

        const result = await parseJsonResponse(response);
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'No se pudo guardar la vacación.');
        }

        setVacationFormMessage('success', result.message || 'Vacación guardada correctamente.');
        await loadVacationsForEmployee();
        resetVacationForm(false);
        loadEmployees();
    } catch (error) {
        console.error('Error al guardar vacación:', error);
        setVacationFormMessage('error', error.message || 'No se pudo guardar la vacación.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('is-loading');
        }
    }
}

async function changeVacationStatus(vacation, estadoDestino) {
    try {
        setVacationLoading(true);
        const response = await fetch(VACATION_API_ROUTES.changeStatus, buildAjaxOptions({
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_vacacion: vacation.id_vacacion,
                estado: estadoDestino,
                fecha_inicio: vacation.fecha_inicio,
                fecha_fin: vacation.fecha_fin,
                observaciones: vacation.observaciones || null,
            }),
        }));

        const result = await parseJsonResponse(response);
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'No se pudo actualizar el estado.');
        }

        showVacationAlert(result.message || 'Estado actualizado correctamente.', 'success');
        await loadVacationsForEmployee();
        resetVacationForm();
        loadEmployees();
    } catch (error) {
        console.error('Error al cambiar estado de vacación:', error);
        showVacationAlert(error.message || 'No se pudo actualizar el estado.', 'error', 6000);
        setVacationLoading(false);
    }
}

function setVacationFormMessage(type, message) {
    const errorBox = document.getElementById('vacationFormError');
    const successBox = document.getElementById('vacationFormSuccess');

    if (errorBox) {
        errorBox.style.display = 'none';
    }
    if (successBox) {
        successBox.style.display = 'none';
    }

    if (type === 'error' && errorBox) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
    }

    if (type === 'success' && successBox) {
        successBox.textContent = message;
        successBox.style.display = 'block';
    }
}

function showVacationAlert(message, type, timeout) {
    const alertBox = document.getElementById('vacationAlert');
    if (!alertBox) {
        return;
    }

    alertBox.textContent = message || '';
    alertBox.classList.remove('is-success', 'is-error', 'is-info');
    if (type === 'success') {
        alertBox.classList.add('is-success');
    } else if (type === 'error') {
        alertBox.classList.add('is-error');
    } else {
        alertBox.classList.add('is-info');
    }

    alertBox.style.display = message ? 'block' : 'none';

    if (message && timeout !== null) {
        const delay = typeof timeout === 'number' ? timeout : 4000;
        setTimeout(function() {
            if (alertBox.textContent === message) {
                alertBox.style.display = 'none';
            }
        }, delay);
    }
}

function formatDateToLocale(isoDate) {
    if (!isoDate) {
        return '—';
    }
    let normalized = String(isoDate).trim();
    if (normalized.includes(' ')) {
        normalized = normalized.split(' ')[0];
    }
    const testDate = new Date(`${normalized}T00:00:00`);
    if (!Number.isNaN(testDate.getTime())) {
        return testDate.toLocaleDateString('es-CO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    const fallback = new Date(isoDate);
    if (!Number.isNaN(fallback.getTime())) {
        return fallback.toLocaleDateString('es-CO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    return isoDate;
}

function formatPeriod(fechaInicio, fechaFin) {
    return `${formatDateToLocale(fechaInicio)} - ${formatDateToLocale(fechaFin)}`;
}
function formatDaysLabel(vacation) {
    const total = vacation.dias_totales || 0;
    if (vacation.estado === 'FINALIZADO' || vacation.estado === 'CANCELADO') {
        return `${total} día${total === 1 ? '' : 's'} totales`;
    }

    const restantes = vacation.dias_restantes || 0;
    return `${total} día${total === 1 ? '' : 's'} • ${restantes} por transcurrir`;
}

function mapEmployeeEstado(estado, activo) {
    if (!estado) {
        return '';
    }
    const normalized = estado.toUpperCase();
    if (normalized === 'A') {
        if (activo && activo.toUpperCase() === 'N') {
            return 'Activo (suspendido)';
        }
        return 'Activo';
    }
    if (normalized === 'I') {
        return 'Inactivo';
    }
    return normalized;
}

function buildAjaxOptions(options = {}) {
    const mergedHeaders = {
        ...VACATION_DEFAULT_HEADERS,
        ...(options.headers || {}),
    };

    return {
        credentials: 'same-origin',
        ...options,
        headers: mergedHeaders,
    };
}

async function parseJsonResponse(response) {
    const text = await response.text();
    if (!text) {
        return {};
    }

    try {
        return JSON.parse(text);
    } catch (error) {
        const snippet = text.replace(/\s+/g, ' ').trim().slice(0, 240);
        throw new Error(`Respuesta inválida del servidor (${response.status}): ${snippet || 'sin contenido'}`);
    }
}