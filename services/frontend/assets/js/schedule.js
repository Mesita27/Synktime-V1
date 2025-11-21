/**
 * SynkTime - Módulo de Horarios
 * JavaScript corregido para trabajar con la estructura HTML existente
 */

// Variables globales
let scheduleData = {
    horarios: [],
    empleados: [],
    pagination: {
        horarios: {
            page: 1,
            limit: 10,
            totalPages: 1
        },
        empleados: {
            page: 1, 
            limit: 10,
            totalPages: 1
        }
    },
    filtros: {
        horarios: {},
        empleados: {}
    },
    seleccion: {
        horarios: [],
        horarios_empleado: [],
        horarios_disponibles: []
    },
    current: {
        empleado: null,
        horario: null
    }
};

// Inicialización del módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    initScheduleModule();
});

/**
 * Inicializa el módulo de horarios
 */
function initScheduleModule() {
    console.log('Inicializando módulo de horarios');
    
    // Cargar sedes para filtros
    loadSedesForFilters();
    
    // Cargar datos iniciales
    loadSchedules();
    loadEmployees();
    
    // Configurar eventos de los botones
    setupEventListeners();
    
    // Establecer fecha actual por defecto
    setDefaultDate();
}

/**
 * Configura los eventos de los elementos
 */
function setupEventListeners() {
    // Filtro de horarios
    document.getElementById('btnBuscarHorario').addEventListener('click', function() {
        scheduleData.pagination.horarios.page = 1;
        updateScheduleFilters();
        loadSchedules();
    });
    
    document.getElementById('btnLimpiarHorario').addEventListener('click', function() {
        document.getElementById('filtro_id').value = '';
        document.getElementById('filtro_nombre').value = '';
        document.getElementById('filtro_sede').value = '';
        document.getElementById('filtro_establecimiento').value = '';
        document.getElementById('filtro_dia').value = '';
        
        scheduleData.filtros.horarios = {};
        scheduleData.pagination.horarios.page = 1;
        loadSchedules();
    });
    
    // Filtro de empleados
    document.getElementById('btnBuscarEmpleado').addEventListener('click', function() {
        scheduleData.pagination.empleados.page = 1;
        updateEmployeeFilters();
        loadEmployees();
    });
    
    document.getElementById('btnLimpiarEmpleado').addEventListener('click', function() {
        document.getElementById('filtro_codigo').value = '';
        document.getElementById('filtro_identificacion').value = '';
        document.getElementById('filtro_nombre_empleado').value = '';
        document.getElementById('filtro_sede_empleado').value = '';
        document.getElementById('filtro_establecimiento_empleado').value = '';
        
        scheduleData.filtros.empleados = {};
        scheduleData.pagination.empleados.page = 1;
        loadEmployees();
    });
    
    // Eventos de cambio de sede en filtros
    document.getElementById('filtro_sede').addEventListener('change', function() {
        loadEstablecimientosForFilters('filtro_sede', 'filtro_establecimiento');
    });
    
    document.getElementById('filtro_sede_empleado').addEventListener('change', function() {
        loadEstablecimientosForFilters('filtro_sede_empleado', 'filtro_establecimiento_empleado');
    });
    
    // Modal de horarios
    document.getElementById('schedule_sede').addEventListener('change', function() {
        loadEstablecimientosForFilters('schedule_sede', 'schedule_establecimiento');
    });
    
    // Botón guardar horario
    document.getElementById('btnSaveSchedule').addEventListener('click', saveSchedule);
    
    // Botón para gestionar vínculos de empleados
    document.getElementById('btnOpenEmployeeManager')?.addEventListener('click', function() {
        // Este botón abre el modal solo cuando se selecciona un empleado de la tabla
        if (!scheduleData.current.empleado) {
            showNotification('Por favor, seleccione un empleado de la tabla', 'warning');
            return;
        }
        manageEmployeeSchedules(scheduleData.current.empleado);
    });
    
    // Botones del modal de gestión de horarios de empleado
    document.getElementById('btnAssignSchedules').addEventListener('click', assignSchedulesToEmployee);
    document.getElementById('btnRemoveSchedules').addEventListener('click', removeSchedulesFromEmployee);
    
    // Checkbox para seleccionar todos
    document.getElementById('selectAllSchedules')?.addEventListener('change', function() {
        selectAllSchedules(this.checked);
    });
    
    document.getElementById('selectAllAssignedSchedules')?.addEventListener('change', function() {
        selectAllEmployeeSchedules(this.checked);
    });
    
    document.getElementById('selectAllAvailableSchedules')?.addEventListener('change', function() {
        selectAllAvailableSchedules(this.checked);
    });
    
    // Filtro de horarios disponibles
    document.getElementById('filterAvailableSchedules')?.addEventListener('input', function() {
        filterAvailableSchedules(this.value);
    });
}

/**
 * Establece la fecha actual como valor por defecto para fechaDesde
 */
function setDefaultDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    
    const formattedDate = `${year}-${month}-${day}`;
    
    if (document.getElementById('fechaDesde')) {
        document.getElementById('fechaDesde').value = formattedDate;
    }
}

/**
 * Carga las sedes para los filtros
 */
function loadSedesForFilters() {
    console.log('Cargando sedes para filtros');
    
    fetch('api/get-sedes.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar sedes');
            }
            
            fillSedeSelect('filtro_sede', data.sedes);
            fillSedeSelect('filtro_sede_empleado', data.sedes);
            fillSedeSelect('schedule_sede', data.sedes, true);
        })
        .catch(error => {
            console.error('Error al cargar sedes:', error);
            showNotification('Error al cargar sedes: ' + error.message, 'error');
        });
}

/**
 * Rellena un select con las sedes
 */
function fillSedeSelect(selectId, sedes, withEmpty = false) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.innerHTML = withEmpty ? 
        '<option value="">Seleccione sede</option>' : 
        '<option value="">Seleccionar una Sede</option>';
    
    if (sedes && Array.isArray(sedes)) {
        sedes.forEach(sede => {
            select.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
        });
    }
}

/**
 * Carga los establecimientos para una sede seleccionada
 */
function loadEstablecimientosForFilters(sedeElementId, establecimientoElementId) {
    const sedeId = document.getElementById(sedeElementId).value;
    const establecimientoSelect = document.getElementById(establecimientoElementId);
    
    if (!establecimientoSelect) return;
    
    establecimientoSelect.innerHTML = establecimientoElementId === 'schedule_establecimiento' ? 
        '<option value="">Seleccione establecimiento</option>' : 
        '<option value="">Seleccionar un Establecimiento</option>';
    
    if (!sedeId) return;
    
    fetch(`api/get-establecimientos.php?sede_id=${sedeId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar establecimientos');
            }
            
            if (data.establecimientos && Array.isArray(data.establecimientos)) {
                data.establecimientos.forEach(establecimiento => {
                    establecimientoSelect.innerHTML += `<option value="${establecimiento.ID_ESTABLECIMIENTO}">${establecimiento.NOMBRE}</option>`;
                });
            }
        })
        .catch(error => {
            console.error('Error al cargar establecimientos:', error);
            showNotification('Error al cargar establecimientos: ' + error.message, 'error');
        });
}

/**
 * Actualiza los filtros de horarios
 */
function updateScheduleFilters() {
    scheduleData.filtros.horarios = {
        id: document.getElementById('filtro_id').value.trim(),
        nombre: document.getElementById('filtro_nombre').value.trim(),
        sede: document.getElementById('filtro_sede').value,
        establecimiento: document.getElementById('filtro_establecimiento').value,
        dia: document.getElementById('filtro_dia').value
    };
}

/**
 * Actualiza los filtros de empleados
 */
function updateEmployeeFilters() {
    scheduleData.filtros.empleados = {
        codigo: document.getElementById('filtro_codigo').value.trim(),
        identificacion: document.getElementById('filtro_identificacion').value.trim(),
        nombre: document.getElementById('filtro_nombre_empleado').value.trim(),
        sede: document.getElementById('filtro_sede_empleado').value,
        establecimiento: document.getElementById('filtro_establecimiento_empleado').value
    };
}

/**
 * Carga la lista de horarios
 */
function loadSchedules() {
    console.log('Cargando horarios');
    
    const tbody = document.getElementById('scheduleTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="10" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando horarios...</td></tr>';
    
    // Construir parámetros
    const params = new URLSearchParams({
        page: scheduleData.pagination.horarios.page,
        limit: scheduleData.pagination.horarios.limit
    });
    
    // Agregar filtros
    Object.entries(scheduleData.filtros.horarios).forEach(([key, value]) => {
        if (value) {
            params.append(key, value);
        }
    });
    
    fetch(`api/horario/list.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar horarios');
            }
            
            scheduleData.horarios = data.data;
            renderSchedulesTable();
            updatePagination(
                data.pagination, 
                scheduleData.pagination.horarios, 
                'schedulePaginationControls', 
                goToSchedulePage
            );
        })
        .catch(error => {
            console.error('Error al cargar horarios:', error);
            tbody.innerHTML = `<tr><td colspan="10" class="error-text">${error.message || 'Error al cargar horarios'}</td></tr>`;
            showNotification('Error al cargar horarios: ' + error.message, 'error');
        });
}

/**
 * Renderiza la tabla de horarios
 */
function renderSchedulesTable() {
    const tbody = document.getElementById('scheduleTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!scheduleData.horarios || scheduleData.horarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="no-data-text">No se encontraron horarios</td></tr>';
        return;
    }
    
    scheduleData.horarios.forEach(horario => {
        // Formatear días de la semana
        const diasArray = horario.dias ? horario.dias.split(',') : [];
        let diasHTML = '';
        
        if (diasArray.length > 0) {
            diasHTML = '<div class="days-badges">';
            diasArray.forEach(dia => {
                const nombreDia = getDayName(parseInt(dia));
                diasHTML += `<span class="day-badge">${nombreDia}</span>`;
            });
            diasHTML += '</div>';
        } else {
            diasHTML = '-';
        }
        
        tbody.innerHTML += `
            <tr>
                <td><input type="checkbox" value="${horario.ID_HORARIO}" onchange="toggleScheduleSelection(this.value, this.checked)"></td>
                <td>${horario.ID_HORARIO}</td>
                <td>${horario.NOMBRE || ''}</td>
                <td>${horario.sede || '-'}</td>
                <td>${horario.establecimiento || '-'}</td>
                <td>${diasHTML}</td>
                <td>${horario.HORA_ENTRADA || '-'}</td>
                <td>${horario.HORA_SALIDA || '-'}</td>
                <td>${horario.TOLERANCIA || '0'}</td>
                <td>
                    <button type="button" class="btn-icon" title="Ver detalles" onclick="viewScheduleDetails(${horario.ID_HORARIO})">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button type="button" class="btn-icon" title="Editar horario" onclick="editSchedule(${horario.ID_HORARIO})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn-icon btn-danger" title="Eliminar horario" onclick="deleteSchedule(${horario.ID_HORARIO})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

/**
 * Carga la lista de empleados
 */
function loadEmployees() {
    console.log('Cargando empleados');
    
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="7" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando empleados...</td></tr>';
    
    // Construir parámetros
    const params = new URLSearchParams({
        page: scheduleData.pagination.empleados.page,
        limit: scheduleData.pagination.empleados.limit
    });
    
    // Agregar filtros
    Object.entries(scheduleData.filtros.empleados).forEach(([key, value]) => {
        if (value) {
            params.append(key, value);
        }
    });
    
    fetch(`api/horario/list-employees.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar empleados');
            }
            
            scheduleData.empleados = data.data;
            renderEmployeesTable();
            updatePagination(
                data.pagination, 
                scheduleData.pagination.empleados, 
                'employeePaginationControls', 
                goToEmployeePage
            );
        })
        .catch(error => {
            console.error('Error al cargar empleados:', error);
            tbody.innerHTML = `<tr><td colspan="7" class="error-text">${error.message || 'Error al cargar empleados'}</td></tr>`;
            showNotification('Error al cargar empleados: ' + error.message, 'error');
        });
}

/**
 * Renderiza la tabla de empleados
 */
function renderEmployeesTable() {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!scheduleData.empleados || scheduleData.empleados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data-text">No se encontraron empleados</td></tr>';
        return;
    }
    
    scheduleData.empleados.forEach(empleado => {
        // Formatear horarios asignados
        let horariosHTML = '';
        
        if (empleado.horarios_count > 0) {
            horariosHTML = `<span class="schedule-count">${empleado.horarios_count}</span> horario${empleado.horarios_count !== 1 ? 's' : ''}`;
        } else {
            horariosHTML = '<span class="no-schedules">Sin horarios</span>';
        }
        
        tbody.innerHTML += `
            <tr>
                <td>${empleado.ID_EMPLEADO || ''}</td>
                <td>${empleado.DNI || ''}</td>
                <td>${empleado.NOMBRE || ''} ${empleado.APELLIDO || ''}</td>
                <td>${empleado.sede || '-'}</td>
                <td>${empleado.establecimiento || '-'}</td>
                <td>${horariosHTML}</td>
                <td>
                    <button type="button" class="btn-icon" title="Gestionar horarios" onclick="manageEmployeeSchedules(${empleado.ID_EMPLEADO})">
                        <i class="fas fa-user-clock"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

/**
 * Actualiza la paginación
 */
function updatePagination(pagination, storePagination, containerId, goToPageFunction) {
    if (!pagination) return;
    
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Guardar datos de paginación
    storePagination.page = pagination.current_page;
    storePagination.totalPages = pagination.total_pages;
    
    // Crear controles de paginación
    let html = `
        <div class="pagination-info">
            Mostrando ${((pagination.current_page - 1) * pagination.limit) + 1} - 
            ${Math.min(pagination.current_page * pagination.limit, pagination.total_records)} 
            de ${pagination.total_records} registros
        </div>
        <div class="pagination-buttons">
    `;
    
    // Botón anterior
    if (pagination.has_prev) {
        html += `<button class="pagination-btn" onclick="${goToPageFunction.name}(${pagination.current_page - 1})">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>`;
    }

    // Páginas numeradas
    const maxButtons = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxButtons / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxButtons - 1);
    
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="${goToPageFunction.name}(1)">1</button>`;
        if (startPage > 2) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                    onclick="${goToPageFunction.name}(${i})">${i}</button>`;
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
        html += `<button class="pagination-btn" onclick="${goToPageFunction.name}(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }

    // Botón siguiente
    if (pagination.has_next) {
        html += `<button class="pagination-btn" onclick="${goToPageFunction.name}(${pagination.current_page + 1})">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Navega a una página específica de horarios
 */
function goToSchedulePage(page) {
    if (page >= 1 && page <= scheduleData.pagination.horarios.totalPages && page !== scheduleData.pagination.horarios.page) {
        scheduleData.pagination.horarios.page = page;
        loadSchedules();
    }
}

/**
 * Navega a una página específica de empleados
 */
function goToEmployeePage(page) {
    if (page >= 1 && page <= scheduleData.pagination.empleados.totalPages && page !== scheduleData.pagination.empleados.page) {
        scheduleData.pagination.empleados.page = page;
        loadEmployees();
    }
}

/**
 * Abre el modal para crear un nuevo horario
 */
function openScheduleModal() {
    // Limpiar formulario
    document.getElementById('scheduleForm').reset();
    document.getElementById('schedule_id').value = '';
    
    // Actualizar título del modal
    document.getElementById('scheduleModalTitle').textContent = 'Registrar Horario';
    
    // Mostrar modal
    document.getElementById('scheduleModal').classList.add('show');
}

/**
 * Cierra el modal de horarios
 */
function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('show');
}

/**
 * Guarda un horario (nuevo o actualizado)
 */
function saveSchedule() {
    const form = document.getElementById('scheduleForm');
    
    // Recopilar días seleccionados
    const diasSeleccionados = [];
    const checkboxes = form.querySelectorAll('input[name="dias[]"]:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Debe seleccionar al menos un día de la semana', 'warning');
        return;
    }
    
    checkboxes.forEach(checkbox => {
        diasSeleccionados.push(parseInt(checkbox.value));
    });
    
    // Validar datos del formulario
    const nombre = document.getElementById('schedule_nombre').value.trim();
    const establecimiento = document.getElementById('schedule_establecimiento').value;
    const horaEntrada = document.getElementById('schedule_hora_entrada').value;
    const horaSalida = document.getElementById('schedule_hora_salida').value;
    const tolerancia = document.getElementById('schedule_tolerancia').value;
    
    if (!nombre || !establecimiento || !horaEntrada || !horaSalida || tolerancia === '') {
        showNotification('Todos los campos marcados con * son obligatorios', 'warning');
        return;
    }
    
    // Recoger datos del formulario
    const formData = {
        id_horario: document.getElementById('schedule_id').value || null,
        nombre: nombre,
        establecimiento: establecimiento,
        hora_entrada: horaEntrada,
        hora_salida: horaSalida,
        tolerancia: tolerancia,
        dias: diasSeleccionados
    };
    
    // Mostrar indicador de carga
    const button = document.getElementById('btnSaveSchedule');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    button.disabled = true;
    
    // Verificar duplicados primero
    fetch('api/horario/check-duplicate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            nombre: formData.nombre,
            establecimiento: formData.establecimiento,
            id_horario: formData.id_horario
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Error al verificar duplicados');
        }
        
        if (data.isDuplicate) {
            showNotification(data.message, 'warning');
            button.innerHTML = originalText;
            button.disabled = false;
            return Promise.reject(new Error('Horario duplicado'));
        }
        
        // Si no hay duplicados, guardar el horario
        return fetch('api/horario/save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Error al guardar horario');
        }
        
        showNotification(data.message, 'success');
        closeScheduleModal();
        loadSchedules();
    })
    .catch(error => {
        if (error.message !== 'Horario duplicado') {
            console.error('Error al guardar horario:', error);
            showNotification('Error al guardar horario: ' + error.message, 'error');
        }
    })
    .finally(() => {
        // Restaurar botón
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

/**
 * Abre el modal para editar un horario
 */
function editSchedule(id) {
    // Limpiar formulario
    document.getElementById('scheduleForm').reset();
    
    // Mostrar indicador de carga
    document.getElementById('scheduleModalTitle').textContent = 'Cargando...';
    document.getElementById('scheduleModal').classList.add('show');
    
    fetch(`api/horario/details.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar detalles del horario');
            }
            
            const horario = data.data;
            
            // Actualizar título del modal
            document.getElementById('scheduleModalTitle').textContent = 'Editar Horario';
            
            // Llenar formulario con datos del horario
            document.getElementById('schedule_id').value = horario.ID_HORARIO;
            document.getElementById('schedule_nombre').value = horario.NOMBRE;
            
            // Cargar sedes y establecimientos
            document.getElementById('schedule_sede').value = horario.ID_SEDE;
            loadEstablecimientosForFilters('schedule_sede', 'schedule_establecimiento');
            
            // Temporizador para asegurarse que los establecimientos estén cargados
            setTimeout(() => {
                document.getElementById('schedule_establecimiento').value = horario.ID_ESTABLECIMIENTO;
            }, 300);
            
            // Horarios y tolerancia
            document.getElementById('schedule_hora_entrada').value = horario.HORA_ENTRADA;
            document.getElementById('schedule_hora_salida').value = horario.HORA_SALIDA;
            document.getElementById('schedule_tolerancia').value = horario.TOLERANCIA;
            
            // Marcar días de la semana
            const diasArray = horario.dias ? horario.dias.split(',') : [];
            document.querySelectorAll('input[name="dias[]"]').forEach(checkbox => {
                checkbox.checked = diasArray.includes(checkbox.value);
            });
        })
        .catch(error => {
            console.error('Error al cargar datos del horario:', error);
            showNotification('Error al cargar datos del horario: ' + error.message, 'error');
            closeScheduleModal();
        });
}

/**
 * Elimina un horario
 */
function deleteSchedule(id) {
    if (!confirm('¿Está seguro de eliminar este horario? Se eliminarán todas las asignaciones relacionadas.')) {
        return;
    }
    
    // Verificar si hay asistencias registradas con este horario
    fetch(`api/horario/check-attendance.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al verificar asistencias');
            }
            
            if (data.hasAttendance) {
                // Si hay asistencias, mostrar advertencia adicional
                if (!confirm(`Hay ${data.count} registros de asistencia vinculados a este horario. Al eliminarlo, estos registros quedarán inconsistentes. ¿Está seguro de continuar?`)) {
                    return Promise.reject(new Error('Operación cancelada por el usuario'));
                }
            }
            
            // Proceder a eliminar
            return fetch('api/horario/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_horario: id })
            });
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al eliminar horario');
            }
            
            showNotification(data.message, 'success');
            loadSchedules();
        })
        .catch(error => {
            if (error.message !== 'Operación cancelada por el usuario') {
                console.error('Error:', error);
                showNotification('Error al eliminar el horario: ' + error.message, 'error');
            }
        });
}

/**
 * Abre el modal para ver detalles de un horario
 */
function viewScheduleDetails(id) {
    // Mostrar modal con indicador de carga
    document.getElementById('scheduleDetailsModal').classList.add('show');
    document.getElementById('scheduleEmployeesTable').innerHTML = '<tr><td colspan="4" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    
    fetch(`api/horario/details.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar detalles del horario');
            }
            
            const horario = data.data;
            
            // Actualizar datos del horario
            document.getElementById('scheduleDetailName').textContent = horario.NOMBRE;
            document.getElementById('scheduleDetailSede').textContent = horario.sede;
            document.getElementById('scheduleDetailEstablecimiento').textContent = horario.establecimiento;
            document.getElementById('scheduleDetailEntrada').textContent = horario.HORA_ENTRADA;
            document.getElementById('scheduleDetailSalida').textContent = horario.HORA_SALIDA;
            document.getElementById('scheduleDetailTolerancia').textContent = `${horario.TOLERANCIA} min`;
            
            // Formatear días
            const diasArray = horario.dias ? horario.dias.split(',') : [];
            let diasHTML = '';
            
            if (diasArray.length > 0) {
                diasArray.forEach(dia => {
                    const nombreDia = getDayName(parseInt(dia));
                    diasHTML += `<span class="day-badge">${nombreDia}</span> `;
                });
            }
            
            document.getElementById('scheduleDetailDays').innerHTML = diasHTML;
            
            // Renderizar lista de empleados asignados
            const employeesTable = document.getElementById('scheduleEmployeesTable');
            
            if (!horario.empleados || horario.empleados.length === 0) {
                employeesTable.innerHTML = '<tr><td colspan="4" class="no-data-text">No hay empleados asignados a este horario</td></tr>';
            } else {
                employeesTable.innerHTML = '';
                
                horario.empleados.forEach(emp => {
                    employeesTable.innerHTML += `
                        <tr>
                            <td>${emp.codigo || ''}</td>
                            <td>${emp.nombre || ''} ${emp.apellido || ''}</td>
                            <td>${formatDate(emp.fecha_desde) || '-'}</td>
                            <td>${emp.fecha_hasta ? formatDate(emp.fecha_hasta) : 'Sin límite'}</td>
                        </tr>
                    `;
                });
            }
        })
        .catch(error => {
            console.error('Error al cargar detalles del horario:', error);
            document.getElementById('scheduleEmployeesTable').innerHTML = `<tr><td colspan="4" class="error-text">Error al cargar datos: ${error.message}</td></tr>`;
            showNotification('Error al cargar detalles del horario: ' + error.message, 'error');
        });
}

/**
 * Cierra el modal de detalles del horario
 */
function closeScheduleDetailsModal() {
    document.getElementById('scheduleDetailsModal').classList.remove('show');
}

/**
 * Abre el modal para gestionar horarios de un empleado
 */
function manageEmployeeSchedules(id) {
    // Guardar ID del empleado actual
    scheduleData.current.empleado = id;
    
    // Limpiar selecciones previas
    scheduleData.seleccion.horarios_empleado = [];
    scheduleData.seleccion.horarios_disponibles = [];
    
    // Mostrar indicador de carga
    document.getElementById('employeeName').textContent = 'Cargando...';
    document.getElementById('employeeScheduleModal').classList.add('show');
    document.getElementById('employeeInfo').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    document.getElementById('assignedSchedulesTable').innerHTML = '<tr><td colspan="7" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    document.getElementById('availableSchedulesTable').innerHTML = '<tr><td colspan="4" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    
    // Establecer fecha desde por defecto (hoy)
    setDefaultDate();
    
    // URL con ID para depurar
    const url = `api/horario/employee-schedules.php?id=${id}`;
    console.log('Consultando URL para horarios de empleado:', url);
    
    // Cargar información del empleado y sus horarios
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar datos del empleado');
            }
            
            console.log('Datos de empleado recibidos:', data);
            
            // Actualizar información del empleado
            document.getElementById('employeeName').textContent = `${data.empleado.nombre || ''} ${data.empleado.apellido || ''}`;
            document.getElementById('currentEmployeeId').value = data.empleado.id_empleado;
            
            const info = document.getElementById('employeeInfo');
            info.innerHTML = `
                <span><i class="fas fa-id-card"></i> ${data.empleado.dni || '-'}</span>
                <span><i class="fas fa-building"></i> ${data.empleado.establecimiento || '-'}</span>
                <span><i class="fas fa-map-marker-alt"></i> ${data.empleado.sede || '-'}</span>
            `;
            
            // Renderizar horarios asignados
            renderAssignedSchedules(data.data);
            
            // Cargar horarios disponibles
            return fetch(`api/horario/available-schedules.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    return response.json();
                });
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar horarios disponibles');
            }
            
            console.log('Horarios disponibles recibidos:', data);
            
            // Renderizar horarios disponibles
            renderAvailableSchedules(data.data);
        })
        .catch(error => {
            console.error('Error al gestionar horarios del empleado:', error);
            document.getElementById('employeeInfo').innerHTML = `<span class="error-text">Error: ${error.message}</span>`;
            document.getElementById('assignedSchedulesTable').innerHTML = '<tr><td colspan="7" class="error-text">Error al cargar datos</td></tr>';
            document.getElementById('availableSchedulesTable').innerHTML = '<tr><td colspan="4" class="error-text">Error al cargar datos</td></tr>';
            showNotification('Error al cargar datos del empleado: ' + error.message, 'error');
        });
    
    // Actualizar estado de los botones
    updateAssignRemoveButtonsState();
}

/**
 * Renderiza los horarios asignados a un empleado
 */
function renderAssignedSchedules(horarios) {
    const tbody = document.getElementById('assignedSchedulesTable');
    if (!tbody) return;
    
    if (!horarios || horarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data-text">Sin horarios asignados</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    horarios.forEach(horario => {
        // Formatear días de la semana
        const diasArray = horario.dias ? horario.dias.split(',') : [];
        let diasHTML = '';
        
        if (diasArray.length > 0) {
            diasHTML = '<div class="days-badges">';
            diasArray.forEach(dia => {
                const nombreDia = getDayName(parseInt(dia));
                diasHTML += `<span class="day-badge">${nombreDia}</span>`;
            });
            diasHTML += '</div>';
        } else {
            diasHTML = '-';
        }
        
        // Formatear las fechas
        const fechaDesde = formatDate(horario.FECHA_DESDE);
        const fechaHasta = horario.FECHA_HASTA ? formatDate(horario.FECHA_HASTA) : 'Sin límite';
        
        // Crear el objeto de asignación
        const employeeId = document.getElementById('currentEmployeeId').value;
        const asignacion = {
            empleado: parseInt(employeeId),
            horario: parseInt(horario.ID_HORARIO),
            fecha_desde: horario.FECHA_DESDE
        };
        
        // Generar un ID único para este botón
        const btnId = `remove-btn-${horario.ID_HORARIO}-${horario.FECHA_DESDE.replace(/[^0-9]/g, '')}`;
        
        tbody.innerHTML += `
            <tr>
                <td>
                    <input type="checkbox" value='${JSON.stringify(asignacion)}' 
                        onchange="toggleEmployeeScheduleSelection(this.value, this.checked)">
                </td>
                <td>${horario.NOMBRE}</td>
                <td>${diasHTML}</td>
                <td>${horario.HORA_ENTRADA} - ${horario.HORA_SALIDA}</td>
                <td>${fechaDesde}</td>
                <td>${fechaHasta}</td>
                <td>
                    <button type="button" id="${btnId}" class="btn-icon btn-danger" title="Desvincular">
                        <i class="fas fa-unlink"></i>
                    </button>
                </td>
            </tr>
        `;
        
        // Adjuntamos el event listener después de crear el elemento
        setTimeout(() => {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.addEventListener('click', function() {
                    removeEmployeeSchedule(JSON.stringify(asignacion));
                });
            }
        }, 0);
    });
}


/**
 * Renderiza los horarios disponibles para asignar
 */
function renderAvailableSchedules(horarios) {
    const tbody = document.getElementById('availableSchedulesTable');
    if (!tbody) return;
    
    if (!horarios || horarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="no-data-text">No hay horarios disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    horarios.forEach(horario => {
        // Formatear días de la semana
        const diasArray = horario.dias ? horario.dias.split(',') : [];
        let diasHTML = '';
        
        if (diasArray.length > 0) {
            diasHTML = '<div class="days-badges">';
            diasArray.forEach(dia => {
                const nombreDia = getDayName(parseInt(dia));
                diasHTML += `<span class="day-badge">${nombreDia}</span>`;
            });
            diasHTML += '</div>';
        } else {
            diasHTML = '-';
        }
        
        tbody.innerHTML += `
            <tr data-search="${horario.NOMBRE.toLowerCase()}">
                <td>
                    <input type="checkbox" value="${horario.ID_HORARIO}" 
                        onchange="toggleAvailableScheduleSelection(this.value, this.checked)">
                </td>
                <td>${horario.NOMBRE}</td>
                <td>${diasHTML}</td>
                <td>${horario.HORA_ENTRADA} - ${horario.HORA_SALIDA}</td>
            </tr>
        `;
    });
}

/**
 * Filtra los horarios disponibles
 */
function filterAvailableSchedules(filterText) {
    const rows = document.querySelectorAll('#availableSchedulesTable tr[data-search]');
    const lowerFilter = filterText.toLowerCase();
    let matchCount = 0;
    let totalCount = 0;
    
    rows.forEach(row => {
        totalCount++;
        const searchText = row.getAttribute('data-search');
        
        if (searchText.includes(lowerFilter)) {
            row.style.display = '';
            matchCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Actualizar contador de resultados
    const filterInfo = document.getElementById('filterResultsInfo');
    if (filterInfo) {
        if (filterText) {
            filterInfo.textContent = `Coincidencias: ${matchCount} de ${totalCount}`;
            filterInfo.style.display = 'block';
        } else {
            filterInfo.style.display = 'none';
        }
    }
}

/**
 * Cierra el modal de gestión de horarios de empleado
 */
function closeEmployeeScheduleModal() {
    document.getElementById('employeeScheduleModal').classList.remove('show');
    
    // Recargar los datos de empleados para mostrar cambios
    loadEmployees();
}

/**
 * Actualiza la selección de horarios en la tabla principal
 */
function toggleScheduleSelection(id, checked) {
    const scheduleId = parseInt(id);
    
    if (checked) {
        if (!scheduleData.seleccion.horarios.includes(scheduleId)) {
            scheduleData.seleccion.horarios.push(scheduleId);
        }
    } else {
        scheduleData.seleccion.horarios = scheduleData.seleccion.horarios.filter(itemId => itemId !== scheduleId);
    }
}

/**
 * Actualiza la selección de horarios asignados en el modal
 */
function toggleEmployeeScheduleSelection(asignacionJson, checked) {
    try {
        const asignacion = JSON.parse(asignacionJson);
        
        if (checked) {
            if (!scheduleData.seleccion.horarios_empleado.some(a => 
                a.empleado === asignacion.empleado && 
                a.horario === asignacion.horario && 
                a.fecha_desde === asignacion.fecha_desde)) {
                scheduleData.seleccion.horarios_empleado.push(asignacion);
            }
        } else {
            scheduleData.seleccion.horarios_empleado = scheduleData.seleccion.horarios_empleado.filter(a => 
                !(a.empleado === asignacion.empleado && 
                a.horario === asignacion.horario && 
                a.fecha_desde === asignacion.fecha_desde)
            );
        }
        
        updateAssignRemoveButtonsState();
    } catch (e) {
        console.error('Error al procesar asignación:', e);
    }
}

/**
 * Actualiza la selección de horarios disponibles en el modal
 */
function toggleAvailableScheduleSelection(id, checked) {
    const scheduleId = parseInt(id);
    
    if (checked) {
        if (!scheduleData.seleccion.horarios_disponibles.includes(scheduleId)) {
            scheduleData.seleccion.horarios_disponibles.push(scheduleId);
        }
    } else {
        scheduleData.seleccion.horarios_disponibles = scheduleData.seleccion.horarios_disponibles.filter(itemId => itemId !== scheduleId);
    }
    
    updateAssignRemoveButtonsState();
}

/**
 * Selecciona todos los horarios asignados al empleado
 */
function selectAllEmployeeSchedules(checked) {
    const checkboxes = document.querySelectorAll('#assignedSchedulesTable input[type="checkbox"]');
    
    scheduleData.seleccion.horarios_empleado = [];
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
        if (checked) {
            try {
                scheduleData.seleccion.horarios_empleado.push(JSON.parse(checkbox.value));
            } catch (e) {
                console.error('Error al procesar asignación:', e);
            }
        }
    });
    
    updateAssignRemoveButtonsState();
}

/**
 * Selecciona todos los horarios disponibles
 */
function selectAllAvailableSchedules(checked) {
    const checkboxes = document.querySelectorAll('#availableSchedulesTable input[type="checkbox"]');
    
    scheduleData.seleccion.horarios_disponibles = [];
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
        if (checked && checkbox.value) {
            scheduleData.seleccion.horarios_disponibles.push(parseInt(checkbox.value));
        }
    });
    
    updateAssignRemoveButtonsState();
}

/**
 * Selecciona todos los horarios en la tabla principal
 */
function selectAllSchedules(checked) {
    const checkboxes = document.querySelectorAll('#scheduleTableBody input[type="checkbox"]');
    
    scheduleData.seleccion.horarios = [];
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
        if (checked && checkbox.value) {
            scheduleData.seleccion.horarios.push(parseInt(checkbox.value));
        }
    });
}

/**
 * Actualiza el estado de los botones de asignar y eliminar
 */
function updateAssignRemoveButtonsState() {
    const btnRemove = document.getElementById('btnRemoveSchedules');
    if (btnRemove) {
        btnRemove.disabled = scheduleData.seleccion.horarios_empleado.length === 0;
    }
    
    const btnAssign = document.getElementById('btnAssignSchedules');
    if (btnAssign) {
        btnAssign.disabled = scheduleData.seleccion.horarios_disponibles.length === 0;
    }
}

/**
 * Asigna los horarios seleccionados al empleado
 */
function assignSchedulesToEmployee() {
    if (!scheduleData.current.empleado || scheduleData.seleccion.horarios_disponibles.length === 0) {
        showNotification('Seleccione al menos un horario para asignar', 'warning');
        return;
    }
    
    // Validar campos de fecha con verificación de existencia
    const fechaDesdeElement = document.getElementById('fechaDesde');
    const fechaHastaElement = document.getElementById('fechaHasta');
    
    if (!fechaDesdeElement) {
        console.error('Campo fechaDesde no encontrado en el DOM');
        showNotification('Error: Campo de fecha desde no encontrado', 'error');
        return;
    }
    
    const fechaDesde = fechaDesdeElement.value;
    if (!fechaDesde) {
        showNotification('La fecha desde es obligatoria', 'error');
        fechaDesdeElement.focus();
        return;
    }
    
    const fechaHasta = fechaHastaElement ? fechaHastaElement.value : null;
    
    // Validar que la fecha hasta sea posterior a la fecha desde
    if (fechaHasta && new Date(fechaHasta) <= new Date(fechaDesde)) {
        showNotification('La fecha hasta debe ser posterior a la fecha desde', 'error');
        if (fechaHastaElement) fechaHastaElement.focus();
        return;
    }
    
    // Mostrar indicador de carga
    const button = document.getElementById('btnAssignSchedules');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Asignando...';
    button.disabled = true;
    
    fetch('api/horario/assign-schedules.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id_empleado: scheduleData.current.empleado,
            horarios: scheduleData.seleccion.horarios_disponibles,
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta || null
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Error al asignar horarios');
        }
        
        showNotification(`${data.count} horario(s) asignado(s) correctamente`, 'success');
        
        // Limpiar selección
        scheduleData.seleccion.horarios_disponibles = [];
        document.querySelectorAll('#availableSchedulesTable input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
        document.getElementById('selectAllAvailableSchedules').checked = false;
        
        // Recargar datos
        manageEmployeeSchedules(scheduleData.current.empleado);
    })
    .catch(error => {
        console.error('Error al asignar horarios:', error);
        showNotification('Error al asignar horarios: ' + error.message, 'error');
    })
    .finally(() => {
        // Restaurar botón
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

/**
 * Elimina las asignaciones de horarios seleccionados del empleado
 */
function removeSchedulesFromEmployee() {
    if (scheduleData.seleccion.horarios_empleado.length === 0) {
        showNotification('Seleccione al menos un horario para desvincular', 'warning');
        return;
    }
    
    if (!confirm('¿Está seguro de desvincular los horarios seleccionados?')) {
        return;
    }
    
    // Mostrar indicador de carga
    const button = document.getElementById('btnRemoveSchedules');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    button.disabled = true;
    
    // Eliminar cada asignación individualmente
    const promises = scheduleData.seleccion.horarios_empleado.map(asignacion => {
        return fetch('api/horario/remove-schedules.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(asignacion)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al desvincular horario');
            }
            return data;
        });
    });
    
    Promise.all(promises)
        .then(results => {
            const successCount = results.filter(r => r.success).length;
            showNotification(`${successCount} horario(s) desvinculado(s) correctamente`, 'success');
            
            // Limpiar selección
            scheduleData.seleccion.horarios_empleado = [];
            document.getElementById('selectAllAssignedSchedules').checked = false;
            
            // Recargar datos
            manageEmployeeSchedules(scheduleData.current.empleado);
        })
        .catch(error => {
            console.error('Error al desvincular horarios:', error);
            showNotification('Error al desvincular horarios: ' + error.message, 'error');
        })
        .finally(() => {
            // Restaurar botón
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

/**
 * Elimina un horario asignado específico
 */
function removeEmployeeSchedule(asignacionJson) {
    try {
        const asignacion = JSON.parse(asignacionJson);
        
        if (!confirm('¿Está seguro de desvincular este horario?')) {
            return;
        }
        
        fetch('api/horario/remove-schedules.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(asignacion)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al desvincular horario');
            }
            
            showNotification('Horario desvinculado correctamente', 'success');
            
            // Recargar datos
            manageEmployeeSchedules(scheduleData.current.empleado);
        })
        .catch(error => {
            console.error('Error al desvincular horario:', error);
            showNotification('Error al desvincular horario: ' + error.message, 'error');
        });
    } catch (e) {
        console.error('Error al procesar asignación:', e);
        showNotification('Error al procesar datos de asignación', 'error');
    }
}

/**
 * Formatea una fecha YYYY-MM-DD a formato DD/MM/YYYY
 */
function formatDate(dateStr) {
    if (!dateStr) return '';
    
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

/**
 * Obtiene el nombre del día de la semana
 */
function getDayName(day) {
    const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    return days[day - 1] || `Día ${day}`;
}

/**
 * Exporta los horarios a Excel
 */
function exportSchedulesToExcel() {
    // Crear URL con los filtros actuales
    const params = new URLSearchParams();
    
    // Agregar filtros actuales
    Object.entries(scheduleData.filtros.horarios).forEach(([key, value]) => {
        if (value) {
            params.append(key, value);
        }
    });
    
    // Redireccionar a la página de exportación
    window.location.href = `api/horario/export.php?${params.toString()}`;
}

/**
 * Muestra una notificación
 */
function showNotification(message, type = 'info') {
    // Verificar si existe el contenedor de notificaciones
    let container = document.getElementById('notificationsContainer');
    
    // Si no existe, crearlo
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationsContainer';
        container.className = 'notifications-container';
        document.body.appendChild(container);
    }
    
    // Crear la notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Determinar el icono según el tipo
    let icon = 'info-circle';
    switch (type) {
        case 'success':
            icon = 'check-circle';
            break;
        case 'error':
            icon = 'exclamation-circle';
            break;
        case 'warning':
            icon = 'exclamation-triangle';
            break;
    }
    
    // Construir el HTML de la notificación
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${icon}"></i>
        </div>
        <div class="notification-content">
            <p>${message}</p>
        </div>
        <button class="notification-close" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Agregar al contenedor
    container.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Manejar cierre manual
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        removeNotification(notification);
    });
    
    // Auto cierre después de 5 segundos
    setTimeout(() => {
        removeNotification(notification);
    }, 5000);
    
    function removeNotification(element) {
        element.classList.add('removing');
        setTimeout(() => {
            if (element.parentNode === container) {
                container.removeChild(element);
            }
        }, 300); // Duración de la animación
    }
}