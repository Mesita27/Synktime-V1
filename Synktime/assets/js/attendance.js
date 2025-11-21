// ===========================================================================
// SISTEMA DE ASISTENCIAS CON PAGINACIÓN AJAX - VERSIÓN ADAPTADA
// Integrado con las mejoras de control de horarios múltiples por trabajador
// ===========================================================================

// Variables globales para paginación
let currentPage = 1;
let currentLimit = 10;
let totalPages = 1;
let currentFilters = {};

// Configuración de límites disponibles
const AVAILABLE_LIMITS = [10, 15, 20, 30, 40, 50];

// Variables existentes
let empleadoSeleccionado = null;
let horarioSeleccionado = null;
let tipoRegistroSeleccionado = null;
let imageBase64 = '';
let autoRefreshTimer;
let observacionIdAsistencia = null;
let observacionTipo = null;

// ===========================================================================
// 1. INICIALIZACIÓN Y CONFIGURACIÓN GENERAL
// ===========================================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired - Iniciando attendance.js');
    
    // Configurar sistema de modales PRIMERO
    setupAttendanceModalListeners();
    
    // Configurar botón principal
    const btnRegisterAttendance = document.getElementById('btnRegisterAttendance');
    console.log('Botón encontrado:', btnRegisterAttendance);
    if (btnRegisterAttendance) {
        btnRegisterAttendance.addEventListener('click', openAttendanceRegisterModal);
        console.log('Event listener agregado al botón');
    } else {
        console.error('Botón btnRegisterAttendance no encontrado');
    }
    
    // Luego inicializar componentes que pueden fallar
    try {
        initializeAttendancePagination();
        console.log('Paginación inicializada');
    } catch(e) {
        console.error('Error en paginación:', e);
    }
    
    try {
        cargarFiltros();
        console.log('Filtros iniciando carga');
    } catch(e) {
        console.error('Error en cargarFiltros:', e);
    }
    
    try {
        loadAttendanceDay();
        console.log('Asistencias del día iniciando carga');
    } catch(e) {
        console.error('Error en loadAttendanceDay:', e);
    }
    
    // Iniciar actualización automática cada 30 minutos
    try {
        startAutoRefresh();
        console.log('Auto refresh iniciado');
    } catch(e) {
        console.error('Error en startAutoRefresh:', e);
    }
    
    // Configurar eventos del modal de registro
    const btnBuscarCodigoRegistro = document.getElementById('btnBuscarCodigoRegistro');
    const codigoRegistroBusqueda = document.getElementById('codigoRegistroBusqueda');
    const nombreRegistroBusqueda = document.getElementById('nombreRegistroBusqueda');
    
    if (btnBuscarCodigoRegistro && codigoRegistroBusqueda) {
        btnBuscarCodigoRegistro.onclick = cargarEmpleadosParaRegistro;
        codigoRegistroBusqueda.addEventListener('keyup', function(e) {
            if (e.key === "Enter") cargarEmpleadosParaRegistro();
        });
    }
    
    if (nombreRegistroBusqueda) {
        nombreRegistroBusqueda.addEventListener('keyup', function(e) {
            if (e.key === "Enter") cargarEmpleadosParaRegistro();
        });
    }
    
    // Configurar cierre de modales con clic fuera o ESC
    setupModalBehaviors();
    
    // Configurar botones del modal de foto
    setupPhotoModalButtons();
    
    // Agregar evento para cuando la página pierde el foco
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Página no visible, pausamos la actualización para ahorrar recursos
            stopAutoRefresh();
        } else {
            // Página visible de nuevo, actualizamos datos y reiniciamos temporizador
            loadAttendanceDay();
            startAutoRefresh();
        }
    });

    const textarea = document.getElementById('observacionTexto');
    if (textarea) {
        textarea.addEventListener('input', updateCharCounter);
    }

    

});

// ===========================================================================
// 2. SISTEMA DE PAGINACIÓN
// ===========================================================================
function initializeAttendancePagination() {
    if (document.getElementById('attendancePaginationControls')) return;

    const limitContainer = document.createElement('div');
    limitContainer.className = 'pagination-controls';
    limitContainer.id = 'attendancePaginationControls';
    limitContainer.innerHTML = `
        <div class="limit-selector">
            <label for="attendanceLimitSelector">Mostrar:</label>
            <select id="attendanceLimitSelector" class="form-control limit-select">
                ${AVAILABLE_LIMITS.map(limit => 
                    `<option value="${limit}" ${limit === currentLimit ? 'selected' : ''}>${limit} registros</option>`
                ).join('')}
            </select>
        </div>
        <div class="pagination-info">
            <span id="attendancePaginationInfo">Cargando...</span>
        </div>
        <div class="pagination-buttons" id="attendancePaginationButtons">
            <!-- Los botones se generan dinámicamente -->
        </div>
    `;
    
    const tableContainer = document.querySelector('.attendance-table-container');
    tableContainer.parentNode.insertBefore(limitContainer, tableContainer);
    
    // Configurar evento del selector de límite
    setupPaginationEventListeners();
}

function setupPaginationEventListeners() {
    const limitSelector = document.getElementById('attendanceLimitSelector');
    if (limitSelector) {
        limitSelector.addEventListener('change', function() {
            currentLimit = parseInt(this.value);
            currentPage = 1;
            loadAttendanceDay();
        });
    }
}

function updateAttendanceFiltersFromForm() {
    currentFilters = {
        sede: document.getElementById('filtro_sede')?.value || '',
        establecimiento: document.getElementById('filtro_establecimiento')?.value || '',
        codigo: document.getElementById('codigoBusqueda')?.value?.trim() || '',
        nombre: document.getElementById('nombreBusqueda')?.value?.trim() || ''
    };
    
    // Remover filtros vacíos
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
}

function goToAttendancePage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadAttendanceDay();
    }
}

// ===========================================================================
// 3. CONFIGURACIÓN DE MODALES (EXISTENTE MANTENIDA)
// ===========================================================================
function setupModalBehaviors() {
    // Cerrar modales al hacer click fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) {
                const modalId = this.id;
                
                if (modalId === 'attendanceRegisterModal') {
                    closeAttendanceRegisterModal();
                } else if (modalId === 'attendancePhotoModal') {
                    closeAttendancePhotoModal();
                } else if (modalId === 'photoModal') {
                    closePhotoModal();
                }
            }
        });
    });
    
    // Cerrar modales con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const modalId = modal.id;
                
                if (modalId === 'attendanceRegisterModal') {
                    closeAttendanceRegisterModal();
                } else if (modalId === 'attendancePhotoModal') {
                    closeAttendancePhotoModal();
                } else if (modalId === 'photoModal') {
                    closePhotoModal();
                }
            });
        }
    });
}

function setupPhotoModalButtons() {
    // Botón para tomar foto
    const takePhotoBtn = document.getElementById('takePhotoBtn');
    if (takePhotoBtn) {
        takePhotoBtn.onclick = takePhoto;
    }
    
    // Botón para guardar asistencia
    const saveAttendanceBtn = document.getElementById('saveAttendanceBtn');
    if (saveAttendanceBtn) {
        saveAttendanceBtn.onclick = saveAttendance;
    }
}

// Función para registrar salida de un empleado
async function registrarSalida(idEmpleado, fecha, idHorario) {
    if (!confirm('¿Está seguro de registrar la salida de este empleado?')) {
        return;
    }

    try {
        // Mostrar indicador de carga
        const tbody = document.getElementById('attendanceTableBody');
        const loadingRow = document.createElement('tr');
        loadingRow.innerHTML = '<td colspan="10" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Registrando salida...</td>';
        tbody.insertBefore(loadingRow, tbody.firstChild);

        // Preparar datos para enviar
        const formData = new FormData();
        formData.append('id_empleado', idEmpleado);
        formData.append('fecha', fecha);
        if (idHorario !== undefined && idHorario !== null && String(idHorario).trim() !== '') {
            formData.append('id_horario', idHorario);
        }

        // Hacer la petición
        const response = await fetch('api/attendance/register-salida.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        // Remover indicador de carga
        if (loadingRow.parentNode) {
            loadingRow.parentNode.removeChild(loadingRow);
        }

        if (result.success) {
            showNotification('Salida registrada correctamente', 'success');
            // Recargar la lista de asistencias
            loadAttendanceDay();
        } else {
            showNotification('Error: ' + result.message, 'error');
        }

    } catch (error) {
        console.error('Error al registrar salida:', error);
        
        // Remover indicador de carga en caso de error
        const tbody = document.getElementById('attendanceTableBody');
        const loadingRow = tbody.querySelector('.loading-text');
        if (loadingRow && loadingRow.parentNode) {
            loadingRow.parentNode.removeChild(loadingRow);
        }
        
        showNotification('Error al registrar la salida: ' + error.message, 'error');
    }
}

// Hacer la función global
window.registrarSalida = registrarSalida;

// ===========================================================================
// 4. FUNCIONES DE FORMATEO Y UTILIDADES
// ===========================================================================
function normalizeDateValue(value) {
    if (value === null || value === undefined) {
        return null;
    }

    const raw = String(value).trim();
    if (!raw) {
        return null;
    }

    const lower = raw.toLowerCase();
    if (lower === 'null' || lower === 'undefined' || lower === 'nan') {
        return null;
    }

    if (raw === '0000-00-00' || raw === '0000-00-00 00:00:00') {
        return null;
    }

    if (raw.includes('T')) {
        return raw.split('T')[0];
    }

    if (raw.includes(' ')) {
        return raw.split(' ')[0];
    }

    // Validar formato de fecha YYYY-MM-DD
    const dateMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (dateMatch) {
        const year = parseInt(dateMatch[1]);
        const month = parseInt(dateMatch[2]);
        const day = parseInt(dateMatch[3]);
        
        // Validar rangos válidos
        if (year >= 1900 && year <= 2100 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
            return raw;
        }
    }

    // Si no es un formato válido, retornar null
    console.warn(`Fecha no válida encontrada: ${raw}`);
    return null;
}

function normalizeTimeValue(value) {
    if (value === null || value === undefined) {
        return null;
    }

    let raw = String(value).trim();
    if (!raw) {
        return null;
    }

    const lower = raw.toLowerCase();
    if (lower === 'null' || lower === 'undefined' || lower === 'nan') {
        return null;
    }

    // Si la cadena contiene fecha y hora (ISO u otro formato), extraer la parte de hora
    if (raw.includes('T')) {
        raw = raw.split('T')[1] || raw.split('T')[0];
    }

    if (raw.includes(' ')) {
        const parts = raw.split(' ');
        raw = parts[parts.length - 1];
    }

    raw = raw.replace(/\.+$/, ''); // eliminar fracciones de segundo

    const timeMatch = raw.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
    if (!timeMatch) {
        return null;
    }

    const hours = timeMatch[1].padStart(2, '0');
    const minutes = timeMatch[2];
    const seconds = (timeMatch[3] || '00').padStart(2, '0');

    return `${hours}:${minutes}:${seconds}`;
}

function getBogotaDateSafe() {
    if (window.Bogota?.getDate) {
        return window.Bogota.getDate();
    }
    return new Date();
}

function getBogotaDateStringSafe() {
    if (window.Bogota?.getDateString) {
        return window.Bogota.getDateString();
    }
    return new Date().toISOString().split('T')[0];
}

function getBogotaTimeStringSafe() {
    if (window.Bogota?.getTimeString) {
        return window.Bogota.getTimeString();
    }
    return new Date().toTimeString().split(' ')[0];
}

function formatDate(dateStr) {
    // Verificar que la fecha existe
    if (!dateStr || dateStr === 'null' || dateStr === 'undefined') {
        return '--/--/----';
    }
    
    // Convertir a string y limpiar
    let dateString = dateStr.toString().trim();
    
    // Si tiene hora, extraer solo la fecha
    if (dateString.includes(' ')) {
        dateString = dateString.split(' ')[0];
    }
    if (dateString.includes('T')) {
        dateString = dateString.split('T')[0];
    }
    
    // Si está en formato YYYY-MM-DD, convertir a DD/MM/YYYY
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        const [year, month, day] = dateString.split('-');
        const formattedDate = `${day}/${month}/${year}`;
        console.log(`formatDate: ${dateStr} -> ${formattedDate}`);
        return formattedDate;
    }
    
    // Si ya está en otro formato, retornarlo tal como está
    console.log(`formatDate: Retornando fecha sin cambios: ${dateString}`);
    return dateString;
}

function formatTime(timeStr) {
    const normalized = normalizeTimeValue(timeStr);
    if (!normalized) return '--:--';
    
    // Extraer solo HH:MM del formato HH:MM:SS
    const timeParts = normalized.split(':');
    if (timeParts.length >= 2) {
        return `${timeParts[0]}:${timeParts[1]}`;
    }
    
    return normalized;
}

function formatDateTimeDisplay(dateStr, timeStr) {
    const dateFormatted = formatDate(dateStr);
    const hasDate = dateFormatted && dateFormatted !== '-';
    const timeFormatted = formatTime(timeStr);
    const hasTime = timeFormatted && timeFormatted !== '-' && timeFormatted !== '--:--';

    if (hasDate && hasTime) {
        return `${dateFormatted} ${timeFormatted}`;
    }

    if (hasDate) {
        return dateFormatted;
    }

    if (hasTime) {
        return timeFormatted;
    }

    return '--';
}

// ===========================================================================
// 5. FILTROS PRINCIPALES (SIN FECHAS)
// ===========================================================================
async function cargarFiltros() {
    try {
        // Cargar sedes
        let sedes = await fetch('api/get-sedes.php').then(r => r.json());
        let sedeSel = document.getElementById('filtro_sede');
        if (sedeSel) {
            sedeSel.innerHTML = '<option value="">Seleccionar una Sede</option>';
            sedes.sedes.forEach(s => {
                sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
            });
            sedeSel.onchange = cargarEstablecimientosFiltro2;
            await cargarEstablecimientosFiltro2();
        } else {
            console.warn('Elemento filtro_sede no encontrado');
        }
        
        // Configurar eventos
        const btnBuscarCodigo = document.getElementById('btnBuscarCodigo');
        if (btnBuscarCodigo) {
            btnBuscarCodigo.addEventListener('click', function() {
                currentPage = 1;
                updateAttendanceFiltersFromForm();
                loadAttendanceDay();
            });
        } else {
            console.warn('Elemento btnBuscarCodigo no encontrado');
        }
        
        const btnLimpiar = document.getElementById('btnLimpiar');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', limpiarFiltros);
        } else {
            console.warn('Elemento btnLimpiar no encontrado');
        }
        
        const codigoBusqueda = document.getElementById('codigoBusqueda');
        if (codigoBusqueda) {
            codigoBusqueda.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    updateAttendanceFiltersFromForm();
                    loadAttendanceDay();
                }
            });
        } else {
            console.warn('Elemento codigoBusqueda no encontrado');
        }
        
        const nombreBusqueda = document.getElementById('nombreBusqueda');
        if (nombreBusqueda) {
            nombreBusqueda.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    updateAttendanceFiltersFromForm();
                    loadAttendanceDay();
                }
            });
        } else {
            console.warn('Elemento nombreBusqueda no encontrado');
        }
        
        // Agregar búsqueda automática para dropdowns
        const filtroSede = document.getElementById('filtro_sede');
        if (filtroSede) {
            filtroSede.addEventListener('change', function() {
                currentPage = 1;
                updateAttendanceFiltersFromForm();
                loadAttendanceDay();
            });
        }
        
        const filtroEstablecimiento = document.getElementById('filtro_establecimiento');
        if (filtroEstablecimiento) {
            filtroEstablecimiento.addEventListener('change', function() {
                currentPage = 1;
                updateAttendanceFiltersFromForm();
                loadAttendanceDay();
            });
        }
        
        console.log('cargarFiltros completado exitosamente');
    } catch (error) {
        console.error('Error en cargarFiltros:', error);
    }
}

function buscarAsistencias() {
    currentPage = 1;
    updateAttendanceFiltersFromForm();
    loadAttendanceDay();
}

function limpiarFiltros() {
    document.getElementById('filtro_sede').value = '';
    document.getElementById('filtro_establecimiento').value = '';
    document.getElementById('codigoBusqueda').value = '';
    document.getElementById('nombreBusqueda').value = '';
    currentFilters = {};
    currentPage = 1;
    cargarEstablecimientosFiltro2();
    loadAttendanceDay();
}

async function cargarEstablecimientosFiltro2() {
    let sedeId = document.getElementById('filtro_sede').value;
    let estSel = document.getElementById('filtro_establecimiento');
    estSel.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
    let url = 'api/get-establecimientos.php';
    if (sedeId) url += '?sede_id=' + encodeURIComponent(sedeId);
    let res = await fetch(url).then(r => r.json());
    if (res.establecimientos) {
        res.establecimientos.forEach(e => {
            estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
        });
    }
    
    // Solo recargar si no es la carga inicial
    if (currentPage > 0) {
        currentPage = 1;
        updateAttendanceFiltersFromForm();
        loadAttendanceDay();
    }
}

// ===========================================================================
// 6. AUTO-REFRESH Y TABLA PRINCIPAL CON PAGINACIÓN 
// ===========================================================================

// Función para iniciar la actualización automática cada 30 minutos
function startAutoRefresh() {
    // Limpiar cualquier temporizador existente
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
    }
    
    // Establecer intervalo de actualización (30 minutos = 1800000 ms)
    autoRefreshTimer = setInterval(function() {
        loadAttendanceDay();
        console.log('Actualización automática de asistencias: ' + new Date().toLocaleString('es-CO'));
    }, 1800000); // 30 minutos
}

// Función para detener la actualización automática
function stopAutoRefresh() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
}

function loadAttendanceDay() {
    updateAttendanceFiltersFromForm();
    
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '<tr><td colspan="10" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</td></tr>';
    
    // Construir URL con paginación y filtros
    const params = new URLSearchParams({
        page: currentPage,
        limit: currentLimit,
        preserve_dates: 'true', // Añadimos este parámetro para indicar al backend que preserve las fechas
        ...currentFilters
    });
    
    const url = `api/attendance/list.php?${params.toString()}`;
    
    // Agregar indicador visual de búsqueda por código
    const codigoInput = document.getElementById('codigoBusqueda');
    if (currentFilters.codigo) {
        codigoInput.classList.add('searching');
    } else {
        codigoInput.classList.remove('searching');
    }

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Actualizar el indicador de última actualización con la hora de Colombia
            actualizarIndicadorTiempo(data.server_time || null);
            
            if (data.success) {
                renderAttendanceTable(data.data);
                updateAttendancePaginationInfo(data.pagination);
                renderAttendancePaginationButtons(data.pagination);
            } else {
                throw new Error(data.message || 'Error al cargar datos');
            }
        })
        .catch(error => {
            console.error('Error al cargar asistencias:', error);
            tbody.innerHTML = '<tr><td colspan="10" class="error-text">Error al cargar datos. Intente de nuevo.</td></tr>';
        });
}

/**
 * Actualiza el indicador de tiempo con la hora correcta del servidor
 * @param {string|null} serverTime - Timestamp del servidor (si está disponible)
 */
function actualizarIndicadorTiempo(serverTime) {
    const ahora = serverTime ? new Date(serverTime) : new Date();
    
    // Formatear fecha y hora con el formato local de Colombia
    const options = { 
        day: 'numeric', 
        month: 'numeric', 
        year: 'numeric',
        hour: 'numeric', 
        minute: 'numeric',
        second: 'numeric',
        hour12: true 
    };
    
    const fechaHoraFormateada = ahora.toLocaleDateString('es-CO', options);
    
    const infoActualizacion = document.getElementById('lastUpdateInfo');
    if (infoActualizacion) {
        infoActualizacion.textContent = `Última actualización: ${fechaHoraFormateada}`;
    } else {
        const infoElement = document.createElement('div');
        infoElement.id = 'lastUpdateInfo';
        infoElement.className = 'last-update-info';
        infoElement.textContent = `Última actualización: ${fechaHoraFormateada}`;
        
        const header = document.querySelector('.attendance-header');
        if (header) {
            const actionDiv = header.querySelector('.attendance-actions') || header;
            actionDiv.prepend(infoElement);
        }
    }
}

function renderAttendanceTable(data) {
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '';
    
    if (!data || !data.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="no-data-text">No se encontraron asistencias para las últimas 20 horas</td></tr>';
        return;
    }
    
    // Organizar los datos por empleado, horario y jornada para poder mostrarlos agrupados
    const asistenciasAgrupadas = {};
    let fallbackIndex = 0;
    
    data.forEach(asistencia => {
        const fechaEntradaRegistro = normalizeDateValue(asistencia.fecha);
        const fechaSalidaRegistro = normalizeDateValue(asistencia.SALIDA_FECHA);

        // Determinar la fecha base de la jornada priorizando la fecha de entrada
    let fechaJornada = fechaEntradaRegistro;

        if (!fechaJornada && fechaSalidaRegistro) {
            // Si solo existe la salida, usarla como referencia inicial
            fechaJornada = fechaSalidaRegistro;
        }

        if (asistencia.ES_TURNO_NOCTURNO === 'S') {
            const salidaPosteriorEntrada = asistencia.HORA_SALIDA_PERSONALIZADO &&
                asistencia.HORA_ENTRADA_PERSONALIZADO &&
                asistencia.HORA_SALIDA_PERSONALIZADO <= asistencia.HORA_ENTRADA_PERSONALIZADO;

            if (!fechaJornada && fechaSalidaRegistro && salidaPosteriorEntrada) {
                // Turno nocturno sin entrada registrada: asumir que la jornada comenzó el día anterior
                const fechaSalida = new Date(`${fechaSalidaRegistro}T00:00:00`);
                if (!Number.isNaN(fechaSalida.getTime())) {
                    fechaSalida.setDate(fechaSalida.getDate() - 1);
                    fechaJornada = fechaSalida.toISOString().split('T')[0];
                }
            } else if (fechaEntradaRegistro && fechaSalidaRegistro && salidaPosteriorEntrada) {
                // Hay entrada y salida en distintas fechas: la jornada corresponde al día de la entrada
                fechaJornada = fechaEntradaRegistro;
            }
        }

        // Si después de todos los cálculos seguimos sin fecha, usar la más cercana disponible
        if (!fechaJornada) {
            fechaJornada = fechaEntradaRegistro || fechaSalidaRegistro || getBogotaDateStringSafe();
        }
        
        // Normalizar horas de entrada/salida y horarios programados
        const horaEntradaReal = normalizeTimeValue(asistencia.hora);
        const horaSalidaReal = normalizeTimeValue(asistencia.SALIDA_HORA);
        const horaEntradaProgramada = normalizeTimeValue(
            asistencia.HORA_ENTRADA_PROGRAMADA || asistencia.HORA_ENTRADA_PERSONALIZADO
        );
        const horaSalidaProgramada = normalizeTimeValue(
            asistencia.HORA_SALIDA_PROGRAMADA || asistencia.HORA_SALIDA_PERSONALIZADO
        );
        const horaEntradaPersonalizada = normalizeTimeValue(asistencia.HORA_ENTRADA_PERSONALIZADO || asistencia.HORA_ENTRADA_PROGRAMADA);
        const horaSalidaPersonalizada = normalizeTimeValue(asistencia.HORA_SALIDA_PERSONALIZADO || asistencia.HORA_SALIDA_PROGRAMADA);
        const toleranciaAsignada = asistencia.TOLERANCIA_PERSONALIZADA ?? asistencia.TOLERANCIA ?? 0;

        // Crear una clave única para cada combinación de empleado, horario y fecha de jornada
        const keyFecha = fechaJornada || 'sin_fecha';
        const entradaId = asistencia.id || asistencia.ID_ASISTENCIA || null;
    const horaReferencia = horaEntradaReal || horaSalidaReal || horaEntradaProgramada || horaSalidaProgramada || 'sin_hora';
        const key = entradaId
            ? `entrada_${entradaId}`
            : `${asistencia.codigo_empleado}_${asistencia.ID_EMPLEADO_HORARIO || asistencia.ID_HORARIO || 'default'}_${keyFecha}_${horaReferencia}_${fallbackIndex++}`;
        
        if (!asistenciasAgrupadas[key]) {
            asistenciasAgrupadas[key] = {
                ID_EMPLEADO: asistencia.codigo_empleado,
                NOMBRE: asistencia.nombre_empleado,
                establecimiento: asistencia.establecimiento,
                sede: asistencia.sede,
                FECHA: fechaJornada, // Usar fecha de jornada como fecha principal
                FECHA_ORIGINAL: fechaEntradaRegistro || fechaSalidaRegistro, // Mantener fecha original para referencia
                
                // Información del horario (ya procesada por el API)
                HORARIO_NOMBRE: asistencia.HORARIO_NOMBRE || 'Sin horario',
                HORA_ENTRADA_PROGRAMADA: horaEntradaProgramada,
                HORA_SALIDA_PROGRAMADA: horaSalidaProgramada,
                TOLERANCIA: toleranciaAsignada,
                tipo_horario: asistencia.tipo_horario || 'ninguno',
                
                // Campos de turno nocturno
                ES_TURNO_NOCTURNO: asistencia.ES_TURNO_NOCTURNO || 'N',
                HORA_CORTE_NOCTURNO: asistencia.HORA_CORTE_NOCTURNO,
                
                // IDs de horarios
                ID_HORARIO: asistencia.ID_HORARIO,
                ID_EMPLEADO_HORARIO: asistencia.ID_EMPLEADO_HORARIO,
                
                // Información adicional de horario personalizado
                NOMBRE_TURNO: asistencia.NOMBRE_TURNO,
                DIA_NOMBRE: asistencia.DIA_NOMBRE,
                ORDEN_TURNO: asistencia.ORDEN_TURNO,
                FECHA_DESDE: asistencia.FECHA_DESDE,
                FECHA_HASTA: asistencia.FECHA_HASTA,
                HORARIO_PERSONALIZADO_ACTIVO: asistencia.HORARIO_PERSONALIZADO_ACTIVO,
                HORA_ENTRADA_PERSONALIZADO: horaEntradaPersonalizada,
                HORA_SALIDA_PERSONALIZADO: horaSalidaPersonalizada,
                
                // Campos de entrada y salida
                ENTRADA_HORA: null,
                ENTRADA_TARDANZA: null,
                ENTRADA_ID: null,
                ENTRADA_FOTO: null,
                ENTRADA_OBSERVACION: null,
                ENTRADA_FECHA: null,
                SALIDA_HORA: null,
                SALIDA_TARDANZA: null,
                SALIDA_ID: null,
                SALIDA_FOTO: null,
                SALIDA_OBSERVACION: null,
                SALIDA_FECHA: null
            };
        }
        asistenciasAgrupadas[key].ENTRADA_HORA = horaEntradaReal;
        // Asignar datos - ahora todos los registros son ENTRADAS con información de salida incluida
        // No hay registros separados de SALIDA
        asistenciasAgrupadas[key].ENTRADA_HORA = asistencia.hora;
        asistenciasAgrupadas[key].ENTRADA_TARDANZA = asistencia.tardanza;
        asistenciasAgrupadas[key].ENTRADA_ID = asistencia.id;
        asistenciasAgrupadas[key].ENTRADA_FOTO = asistencia.foto;
        asistenciasAgrupadas[key].ENTRADA_OBSERVACION = asistencia.observacion;
        asistenciasAgrupadas[key].ENTRADA_FECHA = fechaEntradaRegistro;
        if (!asistenciasAgrupadas[key].FECHA && fechaEntradaRegistro) {
            asistenciasAgrupadas[key].FECHA = fechaEntradaRegistro;
        }
        
        // Asignar información de salida si existe (viene incluida en el registro de entrada)
        if (horaSalidaReal) {
            asistenciasAgrupadas[key].SALIDA_HORA = horaSalidaReal;
            asistenciasAgrupadas[key].SALIDA_TARDANZA = asistencia.SALIDA_TARDANZA;
            asistenciasAgrupadas[key].SALIDA_ID = asistencia.SALIDA_ID;
            asistenciasAgrupadas[key].SALIDA_FOTO = asistencia.SALIDA_FOTO;
            asistenciasAgrupadas[key].SALIDA_OBSERVACION = asistencia.SALIDA_OBSERVACION;
            asistenciasAgrupadas[key].SALIDA_FECHA = fechaSalidaRegistro;
        }
    });
    
    const parseDateTime = (fecha, hora) => {
        if (!fecha || !hora) {
            return null;
        }

        const date = new Date(`${fecha}T${hora}`);
        return Number.isNaN(date.getTime()) ? null : date;
    };

    const desplazarFecha = (fecha, dias) => {
        if (!fecha) {
            return null;
        }

        const baseDate = parseDateTime(fecha, '00:00:00');
        if (!baseDate) {
            return null;
        }

        baseDate.setDate(baseDate.getDate() + dias);
        return formatDate(baseDate);
    };

    const normalizarToleranciaMinutos = valor => {
        if (valor === null || valor === undefined) {
            return 0;
        }

        if (typeof valor === 'number' && Number.isFinite(valor)) {
            return valor;
        }

        if (typeof valor === 'string') {
            const texto = valor.trim();
            if (!texto) {
                return 0;
            }

            const numericoDirecto = Number(texto.replace(',', '.'));
            if (Number.isFinite(numericoDirecto)) {
                return numericoDirecto;
            }

            if (texto.includes(':')) {
                const partes = texto.split(':').map(parte => Number(parte));
                if (partes.every(parte => Number.isFinite(parte))) {
                    const [horas = 0, minutos = 0, segundos = 0] = partes;
                    let total = horas * 60 + minutos;
                    if (segundos >= 30) {
                        total += 1;
                    }
                    return total;
                }
            }

            const coincidenciaNumero = texto.match(/\d+/);
            if (coincidenciaNumero) {
                return Number(coincidenciaNumero[0]);
            }
        }

        return 0;
    };

    const obtenerTimestampProgramadoCercano = (fechaReferencia, horaProgramada, fechaReal) => {
        if (!fechaReferencia || !horaProgramada || !fechaReal) {
            return null;
        }

        const fechasCandidatas = [
            fechaReferencia,
            desplazarFecha(fechaReferencia, -1),
            desplazarFecha(fechaReferencia, 1)
        ].filter(Boolean);

        let mejorTimestamp = null;
        let menorDiferencia = Infinity;

        fechasCandidatas.forEach(fechaCandidata => {
            const candidato = parseDateTime(fechaCandidata, horaProgramada);
            if (!candidato) {
                return;
            }

            const diferencia = Math.abs(fechaReal.getTime() - candidato.getTime());
            if (diferencia < menorDiferencia) {
                menorDiferencia = diferencia;
                mejorTimestamp = candidato;
            }
        });

        return mejorTimestamp;
    };

    const evaluarEstadoConTolerancia = (programado, actual, toleranciaMinutos = 0, etiquetas = {}) => {
        if (!programado || !actual) {
            return { estado: '--', diferenciaMinutos: null };
        }

    const toleranciaValor = normalizarToleranciaMinutos(toleranciaMinutos);
        const diferenciaMs = actual.getTime() - programado.getTime();

        if (toleranciaValor <= 0) {
            if (diferenciaMs < 0) {
                return {
                    estado: etiquetas.tempranoLabel || 'Temprano',
                    diferenciaMinutos: diferenciaMs / 60000
                };
            }

            if (diferenciaMs === 0) {
                return {
                    estado: etiquetas.puntualLabel || 'Puntual',
                    diferenciaMinutos: 0
                };
            }

            return {
                estado: etiquetas.tardanzaLabel || 'Tardanza',
                diferenciaMinutos: diferenciaMs / 60000
            };
        }

        const diferenciaMinutosRedondeada = diferenciaMs >= 0
            ? Math.floor(diferenciaMs / 60000)
            : Math.ceil(diferenciaMs / 60000);

        if (diferenciaMinutosRedondeada < -toleranciaValor) {
            return {
                estado: etiquetas.tempranoLabel || 'Temprano',
                diferenciaMinutos: diferenciaMs / 60000
            };
        }

        if (diferenciaMinutosRedondeada <= toleranciaValor) {
            return {
                estado: etiquetas.puntualLabel || 'Puntual',
                diferenciaMinutos: diferenciaMs / 60000
            };
        }

        return {
            estado: etiquetas.tardanzaLabel || 'Tardanza',
            diferenciaMinutos: diferenciaMs / 60000
        };
    };

    // Convertir el objeto en array y calcular estados
    const asistenciasFinal = Object.values(asistenciasAgrupadas).map(att => {
    const toleranciaMinutos = normalizarToleranciaMinutos(att.TOLERANCIA);
        const fechaReferenciaNormalizada = normalizeDateValue(
            att.FECHA || att.ENTRADA_FECHA || att.FECHA_ORIGINAL || att.SALIDA_FECHA || null
        ) || getBogotaDateStringSafe();

        // Calcular estado de entrada utilizando la tolerancia completa
        let estadoEntrada = '--';
        let diferenciaEntradaMinutos = null;
        if (att.ENTRADA_HORA && att.HORA_ENTRADA_PROGRAMADA) {
            const fechaRealEntrada = parseDateTime(att.ENTRADA_FECHA || att.FECHA, att.ENTRADA_HORA);
            const fechaReferenciaEntrada = att.FECHA || att.ENTRADA_FECHA;
            const programadoEntrada = obtenerTimestampProgramadoCercano(
                fechaReferenciaEntrada,
                att.HORA_ENTRADA_PROGRAMADA,
                fechaRealEntrada
            );

            const resultadoEntrada = evaluarEstadoConTolerancia(
                programadoEntrada,
                fechaRealEntrada,
                toleranciaMinutos
            );

            estadoEntrada = resultadoEntrada.estado;
            diferenciaEntradaMinutos = resultadoEntrada.diferenciaMinutos;
        }
        
        // Calcular estado de salida utilizando la tolerancia completa
        let estadoSalida = '--';
        let diferenciaSalidaMinutos = null;
        if (att.SALIDA_HORA && (att.HORA_SALIDA_PROGRAMADA || att.HORA_SALIDA_PERSONALIZADO)) {
            const horaProgramadaSalida = att.HORA_SALIDA_PROGRAMADA || att.HORA_SALIDA_PERSONALIZADO;
            const horaProgramadaEntrada = att.HORA_ENTRADA_PROGRAMADA || att.HORA_ENTRADA_PERSONALIZADO;
            const fechaBaseEntrada = normalizeDateValue(att.ENTRADA_FECHA) || normalizeDateValue(att.FECHA);
            const fechaSalidaRealBase = att.SALIDA_FECHA
                || (att.ES_TURNO_NOCTURNO === 'S' && fechaBaseEntrada ? desplazarFecha(fechaBaseEntrada, 1) : fechaBaseEntrada);
            const fechaRealSalida = parseDateTime(fechaSalidaRealBase || fechaBaseEntrada, att.SALIDA_HORA);

            let programadoSalida = null;

            if (fechaBaseEntrada && horaProgramadaSalida) {
                programadoSalida = parseDateTime(fechaBaseEntrada, horaProgramadaSalida);

                if (programadoSalida && horaProgramadaEntrada) {
                    const programadoEntrada = parseDateTime(fechaBaseEntrada, horaProgramadaEntrada);
                    if (programadoEntrada && programadoSalida.getTime() <= programadoEntrada.getTime()) {
                        programadoSalida.setDate(programadoSalida.getDate() + 1);
                    }
                }
            }

            if (programadoSalida && fechaRealSalida) {
                const mediaJornadaMs = 12 * 60 * 60 * 1000;
                const diffMs = fechaRealSalida.getTime() - programadoSalida.getTime();
                if (diffMs > mediaJornadaMs) {
                    programadoSalida.setDate(programadoSalida.getDate() + 1);
                } else if (diffMs < -mediaJornadaMs) {
                    programadoSalida.setDate(programadoSalida.getDate() - 1);
                }
            } else if (!programadoSalida && fechaRealSalida && horaProgramadaSalida) {
                const referenciaFallback = normalizeDateValue(fechaSalidaRealBase || fechaBaseEntrada);
                programadoSalida = obtenerTimestampProgramadoCercano(
                    referenciaFallback,
                    horaProgramadaSalida,
                    fechaRealSalida
                );
            }

            if (programadoSalida && fechaRealSalida) {
                const resultadoSalida = evaluarEstadoConTolerancia(
                    programadoSalida,
                    fechaRealSalida,
                    toleranciaMinutos
                );

                estadoSalida = resultadoSalida.estado;
                diferenciaSalidaMinutos = resultadoSalida.diferenciaMinutos;
            }
        }
        
        return {
            ...att,
            FECHA_REFERENCIA: fechaReferenciaNormalizada,
            FECHA: fechaReferenciaNormalizada,
            ENTRADA_ESTADO: estadoEntrada,
            ENTRADA_DIFERENCIA_MINUTOS: diferenciaEntradaMinutos,
            SALIDA_ESTADO: estadoSalida,
            SALIDA_DIFERENCIA_MINUTOS: diferenciaSalidaMinutos
        };
    });
    
    // Ordenar por fecha y hora más reciente
    asistenciasFinal.sort((a, b) => {
        const fechaRefA = normalizeDateValue(a.FECHA_REFERENCIA) || getBogotaDateStringSafe();
        const fechaRefB = normalizeDateValue(b.FECHA_REFERENCIA) || getBogotaDateStringSafe();

        const horaRefA = a.ENTRADA_HORA || a.SALIDA_HORA || '00:00:00';
        const horaRefB = b.ENTRADA_HORA || b.SALIDA_HORA || '00:00:00';

        const dateTimeA = new Date(`${fechaRefA}T${horaRefA}`);
        const dateTimeB = new Date(`${fechaRefB}T${horaRefB}`);

        if (dateTimeA.getTime() !== dateTimeB.getTime()) {
            return dateTimeB.getTime() - dateTimeA.getTime();
        }

        // Si tienen la misma fecha y hora principal, comparar por ID para mantener orden estable
        return (b.ENTRADA_ID || b.SALIDA_ID || 0) - (a.ENTRADA_ID || a.SALIDA_ID || 0);
    });
    
    // Renderizar la tabla
    asistenciasFinal.forEach(att => {
        const fechaReferencia = normalizeDateValue(att.FECHA_REFERENCIA) || normalizeDateValue(att.ENTRADA_FECHA) || normalizeDateValue(att.SALIDA_FECHA) || normalizeDateValue(att.FECHA_ORIGINAL) || getBogotaDateStringSafe();
        const fechaEntradaNorm = normalizeDateValue(att.ENTRADA_FECHA);
        const fechaSalidaNorm = normalizeDateValue(att.SALIDA_FECHA);

        let accion = '';
        
        // Si hay entrada pero no salida para este horario, mostrar botón de salida
        if (att.ENTRADA_HORA && !att.SALIDA_HORA) {
            // **CORREGIDO: Enviar el ID de horario correcto según el tipo**
            const horarioIdParaEnviar = att.tipo_horario === 'personalizado' ? 
                (att.ID_EMPLEADO_HORARIO ?? '') : 
                (att.ID_HORARIO ?? '');
            const fechaParaSalida = fechaEntradaNorm || fechaReferencia;
                
            accion = `<button type="button" class="btn-primary btn-sm" onclick="registrarSalida(${att.ID_EMPLEADO}, '${fechaParaSalida}', '${horarioIdParaEnviar}')">
                        <i class="fas fa-sign-out-alt"></i> Registrar Salida
                      </button>`;
        }
        
        // Botones de observación para entrada y salida
        let observacionEntradaBtn = att.ENTRADA_ID ? 
            `<button type="button" class="btn-icon btn-comment" 
                title="${att.ENTRADA_OBSERVACION ? 'Editar observación' : 'Agregar observación'}" 
                onclick="openObservationModal(${att.ENTRADA_ID}, 'ENTRADA', '${att.NOMBRE.replace("'", "\\'")}', '${fechaEntradaNorm || fechaReferencia}', '${att.ENTRADA_HORA}', '${(att.ENTRADA_OBSERVACION || '').replace("'", "\\'")}')">
                <i class="fas fa-${att.ENTRADA_OBSERVACION ? 'edit' : 'comment-medical'}"></i>
             </button>` : '';
        
        let observacionSalidaBtn = att.SALIDA_ID ? 
            `<button type="button" class="btn-icon btn-comment" 
                title="${att.SALIDA_OBSERVACION ? 'Editar observación' : 'Agregar observación'}" 
                onclick="openObservationModal(${att.SALIDA_ID}, 'SALIDA', '${att.NOMBRE.replace("'", "\\'")}', '${fechaSalidaNorm || fechaReferencia}', '${att.SALIDA_HORA}', '${(att.SALIDA_OBSERVACION || '').replace("'", "\\'")}')">
                <i class="fas fa-${att.SALIDA_OBSERVACION ? 'edit' : 'comment-medical'}"></i>
             </button>` : '';
        
        // Formatear fotos con clase para hacerlas ampliables
        let fotoEntrada = att.ENTRADA_FOTO ? 
            `<img src="uploads/${att.ENTRADA_FOTO}" alt="Foto de entrada" class="asistencia-foto" 
             onclick="openPhotoModal('uploads/${att.ENTRADA_FOTO}', '${att.NOMBRE}')">` : 
            '-';
            
        let fotoSalida = att.SALIDA_FOTO ? 
            `<img src="uploads/${att.SALIDA_FOTO}" alt="Foto de salida" class="asistencia-foto" 
             onclick="openPhotoModal('uploads/${att.SALIDA_FOTO}', '${att.NOMBRE}')">` : 
            '-';
        
    // Formatear horarios programados con valores normalizados
    const horarioEntradaProgramadaDisplay = formatTime(att.HORA_ENTRADA_PROGRAMADA || att.HORA_ENTRADA_PERSONALIZADO);
    const horarioSalidaProgramadaDisplay = formatTime(att.HORA_SALIDA_PROGRAMADA || att.HORA_SALIDA_PERSONALIZADO);
        
        // Mostrar el horario programado con información mejorada
        let horarioProgramado = '';
        
        if (att.tipo_horario === 'personalizado') {
            // Es un horario personalizado - aplicar estilos de turno nocturno si corresponde
            const esNocturno = att.ES_TURNO_NOCTURNO === 'S';
            const claseNocturno = esNocturno ? 'shift-nocturno' : '';
            const badgeNocturno = esNocturno ? '<span class="badge-purple">NOCTURNO</span>' : '';
            
            horarioProgramado = `
                <div class="schedule-item priority-high ${claseNocturno}">
                    <div class="schedule-name">
                        <strong>${att.NOMBRE_TURNO || 'Horario Personalizado'}</strong>
                        <span class="badge-active">PERSONALIZADO</span>
                        ${badgeNocturno}
                    </div>
                    <div class="schedule-times">
                        <span class="time-entry">${horarioEntradaProgramadaDisplay}</span>
                        <span class="separator"> - </span>
                        <span class="time-exit">${horarioSalidaProgramadaDisplay}</span>
                        ${esNocturno ? '<small class="schedule-night-indicator">(día siguiente)</small>' : ''}
                    </div>
                    <div class="schedule-meta">
                        <small class="schedule-type">${att.DIA_NOMBRE || ''} - ID: ${att.ID_EMPLEADO_HORARIO}</small>
                        <span class="status-enabled">✓</span>
                        ${esNocturno ? '<i class="fas fa-moon schedule-night-icon"></i>' : ''}
                    </div>
                </div>
            `;
        } else if (att.tipo_horario === 'tradicional') {
            // Es un horario tradicional
            horarioProgramado = `
                <div class="schedule-traditional">
                    <div class="schedule-name">
                        <strong>${att.HORARIO_NOMBRE || 'Horario Fijo'}</strong>
                    </div>
                    <div class="schedule-times">
                        <span class="time-entry">${horarioEntradaProgramadaDisplay}</span>
                        <span class="separator"> - </span>
                        <span class="time-exit">${horarioSalidaProgramadaDisplay}</span>
                    </div>
                    <div class="schedule-meta">
                        <small class="schedule-type">Tolerancia: ${att.TOLERANCIA || 0} min</small>
                    </div>
                </div>
            `;
        } else {
            // Sin horario asignado
            horarioProgramado = `
                <div class="no-schedule">
                    <span>Sin horario asignado</span><br>
                    <small class="text-muted">No hay horario configurado</small>
                </div>
            `;
        }
        
        // Mostrar observaciones si existen
        const observacionEntrada = att.ENTRADA_OBSERVACION ? 
            `<div class="observacion-badge" title="${att.ENTRADA_OBSERVACION}">
                <i class="fas fa-comment"></i> ${truncateText(att.ENTRADA_OBSERVACION, 20)}
             </div>` : '';
        
        const observacionSalida = att.SALIDA_OBSERVACION ? 
            `<div class="observacion-badge" title="${att.SALIDA_OBSERVACION}">
                <i class="fas fa-comment"></i> ${truncateText(att.SALIDA_OBSERVACION, 20)}
             </div>` : '';
        
        // Resaltar la fila si coincide con el código buscado
        const highlightClass = currentFilters.codigo && att.ID_EMPLEADO == currentFilters.codigo ? 'highlighted-row' : '';
        
        // **CORREGIDO: Preparar las horas reales registradas para la nueva columna**
        let horasRegistradas = '';
        
        console.log(`Procesando empleado ${att.ID_EMPLEADO}: ENTRADA_HORA=${att.ENTRADA_HORA}, SALIDA_HORA=${att.SALIDA_HORA}, ENTRADA_FECHA=${att.ENTRADA_FECHA}, SALIDA_FECHA=${att.SALIDA_FECHA}`);
        
        const entradaHoraSolo = formatTime(att.ENTRADA_HORA);
        const salidaHoraSolo = formatTime(att.SALIDA_HORA);

        if (att.ENTRADA_HORA && att.SALIDA_HORA && (fechaEntradaNorm || att.ENTRADA_FECHA) && (fechaSalidaNorm || att.SALIDA_FECHA)) {
            // Calcular duración exacta considerando turnos nocturnos
            const fechaEntradaCalculo = fechaEntradaNorm || att.ENTRADA_FECHA;
            const fechaSalidaCalculo = fechaSalidaNorm || att.SALIDA_FECHA;
            let entradaDateTime = new Date(`${fechaEntradaCalculo}T${att.ENTRADA_HORA}`);
            let salidaDateTime = new Date(`${fechaSalidaCalculo}T${att.SALIDA_HORA}`);

            // Si la duración es negativa, significa que la salida ocurrió al día siguiente
            // (común en turnos nocturnos)
            let duracionMs = salidaDateTime.getTime() - entradaDateTime.getTime();

            // DEBUG: Log para verificar valores
            console.log(`Empleado ${att.ID_EMPLEADO}: Entrada ${att.ENTRADA_FECHA} ${att.ENTRADA_HORA}, Salida ${att.SALIDA_FECHA} ${att.SALIDA_HORA}, Duración inicial: ${duracionMs}ms`);

            if (duracionMs < 0) {
                // La salida está al día siguiente, ajustar la fecha de salida
                const salidaAjustada = new Date(salidaDateTime);
                salidaAjustada.setDate(salidaAjustada.getDate() + 1);
                duracionMs = salidaAjustada.getTime() - entradaDateTime.getTime();
                console.log(`Duración ajustada (turno nocturno): ${duracionMs}ms`);
            }

            // Validar que la duración no sea mayor a 24 horas (error en datos)
            if (duracionMs > 24 * 60 * 60 * 1000) {
                console.log(`Duración inválida (>24h): ${duracionMs}ms`);
                horasRegistradas = `
                    <div class="horas-registradas">
                        <div><strong>Entrada:</strong> ${entradaDisplay}</div>
                        <div><strong>Salida:</strong> ${salidaDisplay}</div>
                        <div class="duracion-total"><strong>Duración:</strong> Error en cálculo</div>
                    </div>
                `;
            } else {
                const duracionHoras = duracionMs / (1000 * 60 * 60);
                const horas = Math.floor(duracionHoras);
                const minutos = Math.round((duracionHoras - horas) * 60);

                const duracionTexto = `${horas}h ${minutos}m`;
                console.log(`Duración final: ${duracionTexto} (${duracionMs}ms)`);

                const claseNocturno = att.ES_TURNO_NOCTURNO === 'S' ? 'nocturno-time' : '';

                horasRegistradas = `
                    <div class="horas-registradas ${claseNocturno}">
                        <div><strong>Entrada:</strong> ${entradaHoraSolo}</div>
                        <div><strong>Salida:</strong> ${salidaHoraSolo}</div>
                        <div class="duracion-total"><strong>Duración:</strong> ${duracionTexto}</div>
                        ${att.ES_TURNO_NOCTURNO === 'S' ? '<small class="turno-nocturno-label">Turno Nocturno</small>' : ''}
                    </div>
                `;
            }
        } else {
            // Mostrar entrada y salida por separado cuando no se puede calcular duración
            horasRegistradas = `
                <div class="horas-registradas">
                    <div><strong>Entrada:</strong> ${entradaHoraSolo}</div>
                    <div><strong>Salida:</strong> ${salidaHoraSolo}</div>
                    <div class="duracion-total"><strong>Duración:</strong> --</div>
                </div>
            `;
        }
        // Formatear fecha para mostrar (incluyendo fechas duales para turnos nocturnos)
        let fechaMostrar = '';
        
        // Debug: Verificar valores de fechas con más detalle
        console.log(`=== PROCESANDO EMPLEADO ${att.ID_EMPLEADO} ===`);
        console.log(`fechaReferencia: ${fechaReferencia}`);
        console.log(`fechaEntradaNorm: ${fechaEntradaNorm}`);
        console.log(`fechaSalidaNorm: ${fechaSalidaNorm}`);
        console.log(`ES_TURNO_NOCTURNO: ${att.ES_TURNO_NOCTURNO}`);
        console.log(`ENTRADA_FECHA original: ${att.ENTRADA_FECHA}`);
        console.log(`SALIDA_FECHA original: ${att.SALIDA_FECHA}`);
        console.log(`FECHA original: ${att.FECHA}`);
        console.log(`FECHA_REFERENCIA: ${att.FECHA_REFERENCIA}`);
        
        // Verificar si es turno nocturno (entrada y salida en días diferentes)
        const esTurnoNocturno = att.ES_TURNO_NOCTURNO === 'S' || (fechaEntradaNorm && fechaSalidaNorm && fechaEntradaNorm !== fechaSalidaNorm);
        
        if (esTurnoNocturno && fechaEntradaNorm && fechaSalidaNorm) {
            // Turno nocturno: mostrar fecha de entrada y salida
            fechaMostrar = `
                <div class="date-range-night">
                    <div class="date-entry-line">
                        <span class="date-label">Entrada:</span>
                        <span class="date-value">${formatDate(fechaEntradaNorm)}</span>
                    </div>
                    <div class="date-exit-line">
                        <span class="date-label">Salida:</span>
                        <span class="date-value">${formatDate(fechaSalidaNorm)}</span>
                    </div>
                    <small class="night-shift-label">Turno Nocturno</small>
                </div>
            `;
        } else {
            // Turno normal: usar la primera fecha válida disponible
            let fechaParaMostrar = null;
            
            console.log(`--- Turno normal para empleado ${att.ID_EMPLEADO} ---`);
            console.log(`Opciones disponibles: fechaEntradaNorm=${fechaEntradaNorm}, fechaReferencia=${fechaReferencia}, fechaSalidaNorm=${fechaSalidaNorm}`);
            
            // Prioridad: fecha de entrada normalizada > fecha de referencia > fecha de salida
            if (fechaEntradaNorm) {
                fechaParaMostrar = fechaEntradaNorm;
                console.log(`Seleccionada fechaEntradaNorm: ${fechaParaMostrar}`);
            } else if (fechaReferencia && fechaReferencia !== getBogotaDateStringSafe()) {
                fechaParaMostrar = fechaReferencia;
                console.log(`Seleccionada fechaReferencia: ${fechaParaMostrar}`);
            } else if (fechaSalidaNorm) {
                fechaParaMostrar = fechaSalidaNorm;
                console.log(`Seleccionada fechaSalidaNorm: ${fechaParaMostrar}`);
            } else {
                fechaParaMostrar = fechaReferencia; // Fallback a la fecha de referencia (que puede ser hoy)
                console.log(`Fallback a fechaReferencia: ${fechaParaMostrar}`);
            }
            
            console.log(`Empleado ${att.ID_EMPLEADO} - Fecha final seleccionada para mostrar: ${fechaParaMostrar}`);
            
            if (fechaParaMostrar) {
                const fechaFormateada = formatDate(fechaParaMostrar);
                console.log(`Fecha formateada para ${att.ID_EMPLEADO}: ${fechaParaMostrar} -> ${fechaFormateada}`);
                fechaMostrar = `<span class="date-single">${fechaFormateada}</span>`;
            } else {
                console.log(`Sin fecha válida para empleado ${att.ID_EMPLEADO}`);
                fechaMostrar = `<span class="date-single">--/--/----</span>`;
            }
        }
        
        tbody.innerHTML += `
            <tr class="${highlightClass}">
                <td>${att.ID_EMPLEADO}</td>
                <td>${att.NOMBRE}</td>
                <td>${att.establecimiento}</td>
                <td>${att.sede}</td>
                <td>${fechaMostrar}</td>
                <td>${horarioProgramado}</td>
                <td>${horasRegistradas}</td>
                <td>
                    <strong>Entrada:</strong> <span class="status-${att.ENTRADA_ESTADO?.toLowerCase()}">${att.ENTRADA_ESTADO || '--'}</span>
                    ${observacionEntrada}<br>
                    <strong>Salida:</strong> <span class="status-${att.SALIDA_ESTADO?.toLowerCase()}">${att.SALIDA_ESTADO || '--'}</span>
                    ${observacionSalida}
                </td>
                <td>
                    <strong>Entrada:</strong> ${fotoEntrada}<br>
                    <strong>Salida:</strong> ${fotoSalida}
                </td>
                <td>
                    <div class="btn-actions">
                        ${observacionEntradaBtn}
                        ${observacionSalidaBtn}
                        ${accion}
                    </div>
                </td>
            </tr>
        `;
    });
}

/**
 * Trunca un texto si supera la longitud máxima
 * @param {string} text - Texto a truncar
 * @param {number} maxLength - Longitud máxima
 * @returns {string} - Texto truncado
 */
function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

// ===========================================================================
// 7. CONTROLES DE PAGINACIÓN
// ===========================================================================
function updateAttendancePaginationInfo(pagination) {
    const info = document.getElementById('attendancePaginationInfo');
    if (info && pagination) {
        const start = ((pagination.current_page - 1) * pagination.limit) + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
        
        info.textContent = `Mostrando ${start} - ${end} de ${pagination.total_records} asistencias`;
        
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
    }
}

function renderAttendancePaginationButtons(pagination) {
    const container = document.getElementById('attendancePaginationButtons');
    if (!container || !pagination) return;

    let buttonsHTML = '';
    
    // Botón anterior
    if (pagination.has_prev) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(${pagination.current_page - 1})">
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
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(1)">1</button>`;
        if (startPage > 2) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        buttonsHTML += `<button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                            onclick="goToAttendancePage(${i})">${i}</button>`;
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }

    // Botón siguiente
    if (pagination.has_next) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(${pagination.current_page + 1})">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
    }

    container.innerHTML = buttonsHTML;
}

// ===========================================================================
// 8. SISTEMA DE MODALES MEJORADO (SIGUIENDO PATRÓN DE EMPLOYEE.JS)
// ===========================================================================

/**
 * Abre el modal de registro de asistencia
 */
window.openAttendanceRegisterModal = async function() {
    console.log('openAttendanceRegisterModal llamada');
    const modal = document.getElementById('attendanceRegisterModal');
    console.log('Modal encontrado:', modal);
    
    if (!modal) {
        console.error('Modal attendanceRegisterModal no encontrado');
        return;
    }
    
    // No mostramos mensaje de carga inicial, usaremos el mensaje de filtros directamente
    
    // Resetear los campos de filtro
    const codigoInput = document.getElementById('codigoRegistroBusqueda');
    if (codigoInput) {
        codigoInput.value = '';
    }
    
    // Mostrar el modal
    modal.classList.add('show');
    console.log('Clase show agregada al modal');
    
    // Establecer fecha actual
    const fechaActual = window.Bogota?.toLocaleDateString?.('es-CO') || new Date().toLocaleDateString('es-CO');
    const fechaElement = document.getElementById('reg_fecha');
    if (fechaElement) {
        fechaElement.textContent = fechaActual;
    }
    
    // Inicializar contenido del modal
    inicializarModalRegistro();
};

/**
 * Cierra el modal de registro de asistencia
 */
window.closeAttendanceRegisterModal = function() {
    console.log('closeAttendanceRegisterModal llamada');
    const modal = document.getElementById('attendanceRegisterModal');
    if (modal) {
        modal.classList.remove('show');
        console.log('Modal cerrado');
    }
    resetAttendanceRegisterModalState();
};

/**
 * Configura todos los event listeners para los modales
 */
function setupAttendanceModalListeners() {
    console.log('Configurando event listeners de modales');
    
    // Event listeners para cerrar modal
    const closeBtn = document.querySelector('#attendanceRegisterModal .modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeAttendanceRegisterModal);
        console.log('Event listener de cerrar agregado');
    }
    
    // Cerrar modal con click fuera del contenido
    const modal = document.getElementById('attendanceRegisterModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAttendanceRegisterModal();
            }
        });
        console.log('Event listener de click fuera agregado');
    }
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('attendanceRegisterModal');
            if (modal && modal.classList.contains('show')) {
                closeAttendanceRegisterModal();
            }
        }
    });
    console.log('Event listener de ESC agregado');
}

const REGISTER_MODAL_EMPTY_STATE_HTML = `
    <tr>
        <td colspan="5" class="no-data-text">
            <div class="text-center p-3">
                <i class="fas fa-filter fa-2x mb-2 text-muted"></i>
                <p>Para ver empleados, seleccione al menos un filtro:</p>
                <ul class="mt-2 list-unstyled">
                    <li><i class="fas fa-building text-primary"></i> Seleccione una sede, o</li>
                    <li><i class="fas fa-store text-success"></i> Seleccione un establecimiento, o</li>
                    <li><i class="fas fa-id-card text-info"></i> Ingrese un código de empleado</li>
                </ul>
            </div>
        </td>
    </tr>
`;

function renderAttendanceRegisterEmptyState(targetBody = null) {
    const tbody = targetBody || document.getElementById('attendanceRegisterTableBody');
    if (tbody) {
        tbody.innerHTML = REGISTER_MODAL_EMPTY_STATE_HTML;
    }
}

function resetAttendanceRegisterModalState() {
    const fieldsToClear = ['codigoRegistroBusqueda', 'nombreRegistroBusqueda'];
    fieldsToClear.forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.value = '';
        }
    });

    const sedeSel = document.getElementById('reg_sede');
    if (sedeSel) {
        sedeSel.selectedIndex = 0;
        sedeSel.disabled = false;
    }

    const estSel = document.getElementById('reg_establecimiento');
    if (estSel) {
        estSel.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
        estSel.disabled = false;
        estSel.selectedIndex = 0;
    }

    renderAttendanceRegisterEmptyState();
}

/**
 * Inicializa el contenido del modal de registro
 */
async function inicializarModalRegistro() {
    try {
        console.log('Inicializando modal de registro');
        await cargarSedesRegistro();
        await cargarEstablecimientosRegistro();
        
        // Mostrar mensaje de filtros en lugar de cargar empleados automáticamente
        const tbody = document.getElementById('attendanceRegisterTableBody');
        if (tbody) {
            renderAttendanceRegisterEmptyState(tbody);
        }
        
        configureRegistroEventListeners();
        console.log('Modal de registro inicializado exitosamente');
    } catch (error) {
        console.error('Error al inicializar modal:', error);
    }
}

/**
 * Funciones utilitarias para manejo de modales
 */
const ModalUtils = {
    /**
     * Cierra todos los modales abiertos
     */
    closeAllModals: function() {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            modal.classList.remove('show');
            if (modal.id === 'attendanceRegisterModal') {
                resetAttendanceRegisterModalState();
            }
        });
        console.log('Todos los modales cerrados');
    },
    
    /**
     * Verifica si hay algún modal abierto
     */
    isAnyModalOpen: function() {
        return document.querySelector('.modal.show') !== null;
    },
    
    /**
     * Previene scroll del body cuando modal está abierto
     */
    preventBodyScroll: function(prevent = true) {
        document.body.style.overflow = prevent ? 'hidden' : '';
    }
};

// Mejorar las funciones existentes con las utilidades
const originalOpenModal = window.openAttendanceRegisterModal;
window.openAttendanceRegisterModal = async function() {
    console.log('Modal wrapper ejecutándose');
    // Cerrar otros modales primero
    ModalUtils.closeAllModals();
    
    // Llamar función original
    await originalOpenModal();
    
    // Prevenir scroll del body
    ModalUtils.preventBodyScroll(true);
    
    // No cargamos nada aquí, la función original ya se encarga de inicializar el modal
    // ya que llama a inicializarModalRegistro()
    
    // No cargamos empleados automáticamente, solo preparamos la tabla
    const tbody = document.getElementById('attendanceRegisterTableBody');
    if (tbody) {
        renderAttendanceRegisterEmptyState(tbody);
    }
};

const originalCloseModal = window.closeAttendanceRegisterModal;
window.closeAttendanceRegisterModal = function() {
    // Llamar función original
    originalCloseModal();
    
    // Restaurar scroll del body
    ModalUtils.preventBodyScroll(false);
};

function configureRegistroEventListeners() {
    console.log('Configurando eventos para el modal de registro');
    
    const btnBuscar = document.getElementById('btnBuscarCodigoRegistro');
    if (btnBuscar) {
        console.log('Configurando botón de búsqueda');
        btnBuscar.onclick = function(e) {
            e.preventDefault();
            cargarEmpleadosParaRegistro();
        };
    }
    
    const codigoBusqueda = document.getElementById('codigoRegistroBusqueda');
    if (codigoBusqueda) {
        console.log('Configurando campo de código');
        codigoBusqueda.onkeypress = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cargarEmpleadosParaRegistro();
            }
        };
    }
    
    const sedeSel = document.getElementById('reg_sede');
    if (sedeSel) {
        console.log('Configurando selector de sede');
        sedeSel.onchange = async function() {
            console.log('Sede cambiada, actualizando establecimientos y empleados');
            const sedeId = this.value;
            
            // Mostrar mensaje de carga
            const estSel = document.getElementById('reg_establecimiento');
            if (estSel) {
                estSel.innerHTML = '<option value="">Cargando...</option>';
            }
            
            // Cargar establecimientos para la sede seleccionada
            await cargarEstablecimientosRegistro(sedeId);
            
            // No cargar empleados automáticamente, dejar que el usuario seleccione filtros primero
            // cargarEmpleadosParaRegistro();
        };
    }
    
    const estSel = document.getElementById('reg_establecimiento');
    if (estSel) {
        console.log('Configurando selector de establecimiento');
        estSel.onchange = function() {
            console.log('Establecimiento cambiado, actualizando empleados');
            // No cargar empleados automáticamente, dejar que el usuario use el botón de búsqueda
            // cargarEmpleadosParaRegistro();
        };
    }
}

// Cargar sedes en el modal de registro
async function cargarSedesRegistro() {
    try {
        console.log('Cargando sedes para el modal de registro');
        
        const sedeSel = document.getElementById('reg_sede');
        if (!sedeSel) {
            console.error('Elemento reg_sede no encontrado');
            return;
        }
        
        // Mostrar mensaje de carga
        sedeSel.innerHTML = '<option value="">Cargando sedes...</option>';
        sedeSel.disabled = true;
        
        const response = await fetch('api/get-sedes.php');
        if (!response.ok) {
            throw new Error(`Error al cargar sedes: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Llenar el selector de sedes
        sedeSel.innerHTML = '<option value="">Seleccionar una Sede</option>';
        
        if (data.sedes && data.sedes.length > 0) {
            data.sedes.forEach(s => {
                sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
            });
            console.log(`Cargadas ${data.sedes.length} sedes`);
        }
        
        // Habilitar el selector
        sedeSel.disabled = false;
        
        // Cargar establecimientos iniciales (para todas las sedes)
        await cargarEstablecimientosRegistro();
        
        // Configurar eventos después de cargar los datos
        configureRegistroEventListeners();
        
    } catch (error) {
        console.error('Error al cargar sedes:', error);
        
        const sedeSel = document.getElementById('reg_sede');
        if (sedeSel) {
            sedeSel.innerHTML = '<option value="">Error al cargar sedes</option>';
            sedeSel.disabled = false;
        }
    }
}

// Cargar establecimientos en el modal de registro
async function cargarEstablecimientosRegistro(sedeId = null) {
    try {
        console.log('Cargando establecimientos para el modal de registro');
        
        // Si no se proporciona sedeId, intentar obtenerlo del selector
        if (sedeId === null) {
            sedeId = document.getElementById('reg_sede')?.value || '';
        }
        
        // Obtener el elemento del selector de establecimientos
        const estSel = document.getElementById('reg_establecimiento');
        if (!estSel) {
            console.error('Elemento reg_establecimiento no encontrado');
            return;
        }
        
        // Mostrar mensaje de carga
        estSel.innerHTML = '<option value="">Cargando establecimientos...</option>';
        estSel.disabled = true;
        
        // Construir URL con o sin filtro de sede
        let url = 'api/get-establecimientos.php';
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
            console.log(`Filtrando establecimientos por sede: ${sedeId}`);
        } else {
            console.log('Cargando todos los establecimientos (sin filtro de sede)');
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Error al cargar establecimientos: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Llenar el selector
        estSel.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
        
        if (data.establecimientos && data.establecimientos.length > 0) {
            data.establecimientos.forEach(e => {
                estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
            });
            console.log(`Cargados ${data.establecimientos.length} establecimientos`);
        } else {
            console.log('No se encontraron establecimientos');
        }
        
        // Habilitar el selector
        estSel.disabled = false;
        
    } catch (error) {
        console.error('Error al cargar establecimientos:', error);
        
        // Mostrar mensaje de error
        const estSel = document.getElementById('reg_establecimiento');
        if (estSel) {
            estSel.innerHTML = '<option value="">Error al cargar establecimientos</option>';
            estSel.disabled = false;
        }
    }
}

// Cargar empleados disponibles 
async function cargarEmpleadosParaRegistro() {
    console.log('Iniciando carga de empleados para registro');
    
    const tbody = document.getElementById('attendanceRegisterTableBody');
    if (!tbody) {
        console.error('Elemento attendanceRegisterTableBody no encontrado');
        return;
    }
    
    const sede = document.getElementById('reg_sede')?.value || '';
    const establecimiento = document.getElementById('reg_establecimiento')?.value || '';
    const codigo = document.getElementById('codigoRegistroBusqueda')?.value?.trim() || '';
    const nombre = document.getElementById('nombreRegistroBusqueda')?.value?.trim() || '';
    
    // Si no hay filtros seleccionados, mostrar mensaje informativo
    if (!sede && !establecimiento && !codigo && !nombre) {
        console.log('No se han seleccionado filtros');
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="no-data-text">
                    <div class="text-center p-3">
                        <i class="fas fa-filter fa-2x mb-2 text-muted"></i>
                        <p>Para ver empleados, seleccione al menos un filtro:</p>
                        <ul class="mt-2 list-unstyled">
                            <li><i class="fas fa-building text-primary"></i> Seleccione una sede, o</li>
                            <li><i class="fas fa-store text-success"></i> Seleccione un establecimiento, o</li>
                            <li><i class="fas fa-id-card text-info"></i> Ingrese un código de empleado, o</li>
                            <li><i class="fas fa-user text-warning"></i> Ingrese un nombre de empleado</li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    // Mostrar indicador de carga
    tbody.innerHTML = '<tr><td colspan="5" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando empleados disponibles...</td></tr>';
    
    // Asegurar que tengamos la ruta correcta independiente de la ubicación del servidor
    const baseUrl = window.location.href.split('/').slice(0, -1).join('/');
    const apiPath = `${baseUrl}/api/employee/list-fixed.php`;
    
    console.log('Base URL detectada:', baseUrl);
    console.log('API Path completo:', apiPath);
    
    const params = new URLSearchParams();
    
    // Convertir a enteros para evitar problemas con el tipo de datos
    if (sede) params.append('sede', parseInt(sede));
    if (establecimiento) params.append('establecimiento', parseInt(establecimiento));
    if (codigo) params.append('codigo', parseInt(codigo) || codigo); // Intentar convertir a entero si es posible
    if (nombre) params.append('nombre', nombre);
    
    // Límite fijo como número
    params.append('limit', 100);
    
    // Timestamp para evitar caché
    params.append('_t', Date.now());
    
    const url = `${apiPath}?${params.toString()}`;
    
    try {
        console.log('Consultando API:', url);
        
        let response;
        try {
            response = await fetch(url);
            
            if (!response.ok) {
                console.error(`Error HTTP: ${response.status}. Intentando con ruta alternativa...`);
                // Intentar con rutas alternativas
                const alternativeUrls = [
                    `api/employee/list-fixed.php?${params.toString()}`,
                    `api/employee/list.php?${params.toString()}`,
                    `api/biometric/get-employees-fixed.php?${params.toString()}`,
                    `api/biometric/get-employees.php?${params.toString()}`,
                    `api/test/simple-employees.php`, // API simple sin autenticación
                    `api/test/api-test.php` // API de diagnóstico para verificar conectividad
                ];
                
                let success = false;
                let altUrl = '';
                
                for (const url of alternativeUrls) {
                    console.log('Intentando con URL alternativa:', url);
                    try {
                        const altResponse = await fetch(url);
                        if (altResponse.ok) {
                            altUrl = url;
                            success = true;
                            console.log('URL alternativa exitosa:', url);
                            break;
                        }
                    } catch (err) {
                        console.error(`Error con URL alternativa ${url}:`, err);
                    }
                }
                
                if (!success) {
                    throw new Error('No se pudo conectar con ninguna API');
                }
                if (success) {
                    response = await fetch(altUrl);
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                } else {
                    throw new Error('No se pudo conectar con ninguna API disponible');
                }
            }
        } catch (fetchError) {
            console.error('Error de fetch:', fetchError);
            throw new Error(`Error de conexión: ${fetchError.message}`);
        }
        
        const responseText = await response.text();
        let data;
        
        try {
            data = JSON.parse(responseText);
            console.log('Respuesta recibida:', data);
        } catch (e) {
            console.error('Respuesta no es JSON válido:', responseText.substring(0, 100));
            throw new Error('Formato de respuesta inválido: ' + responseText.substring(0, 50));
        }
        
        if (!data.success) {
            throw new Error(data.message || 'Error en la respuesta del servidor');
        }
        
        tbody.innerHTML = '';
        
        // Determinar de dónde obtener los datos
        let empleados = [];
        
        // Intentar diferentes formatos de respuesta
        if (data.data && Array.isArray(data.data)) {
            empleados = data.data;
        } else if (data.employees && Array.isArray(data.employees)) {
            empleados = data.employees;
        } else if (Array.isArray(data)) {
            empleados = data;
        } else {
            console.error('Formato de datos desconocido:', data);
            tbody.innerHTML = '<tr><td colspan="5" class="error-text">Formato de datos no reconocido</td></tr>';
            return;
        }
        
        console.log('Empleados encontrados:', empleados.length);
        
        if (empleados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="no-data-text">No hay empleados disponibles para registro de asistencia</td></tr>';
            return;
        }
        
        // Normalizar el formato de datos para manejar diferentes estructuras JSON
        const empleadosNormalizados = empleados.map(emp => {
            // Función auxiliar para buscar valores en diferentes posibles nombres de campo
            const getField = (obj, possibleFields, defaultValue = '') => {
                for (const field of possibleFields) {
                    if (obj[field] !== undefined) {
                        return obj[field];
                    }
                }
                return defaultValue;
            };
            
            return {
                id: getField(emp, ['ID_EMPLEADO', 'id', 'codigo', 'ID']),
                nombre: getField(emp, ['NOMBRE', 'nombre', 'name', 'firstName']),
                apellido: getField(emp, ['APELLIDO', 'apellido', 'lastName']),
                establecimiento: getField(emp, ['ESTABLECIMIENTO', 'establecimiento', 'establecimiento_id']),
                sede: getField(emp, ['SEDE', 'sede', 'sede_id'])
            };
        });
        
        // Agrupar empleados por sede para mejor organización
        const empleadosPorSede = {};
        empleadosNormalizados.forEach(emp => {
            const sede = emp.sede || 'Sin Sede';
            if (!empleadosPorSede[sede]) {
                empleadosPorSede[sede] = [];
            }
            empleadosPorSede[sede].push(emp);
        });
        
        // Agregar cada grupo de empleados a la tabla
        Object.keys(empleadosPorSede).sort().forEach(sede => {
            // Agregar encabezado de sede
            tbody.innerHTML += `
                <tr class="sede-header" style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="5" style="text-align: center; padding: 8px;">
                        📍 ${sede} (${empleadosPorSede[sede].length} empleados)
                    </td>
                </tr>
            `;
            
            // Agregar empleados de esta sede
            empleadosPorSede[sede].forEach(emp => {
                const nombreCompleto = `${emp.nombre} ${emp.apellido}`.trim();
                
                tbody.innerHTML += `
                    <tr>
                        <td>${emp.id}</td>
                        <td>${nombreCompleto}</td>
                        <td>${emp.establecimiento}</td>
                        <td>${sede}</td>
                        <td>
                            <button type="button" class="btn-primary btn-sm" onclick="openBiometricVerificationForEmployee(${emp.id}, '${nombreCompleto}')">
                                <i class="fas fa-shield-alt"></i> Verificar
                            </button>
                            <button type="button" class="btn-secondary btn-sm" onclick="openAttendancePhotoModal(${emp.id}, '${nombreCompleto}')">
                                <i class="fas fa-camera"></i> Tradicional
                            </button>
                        </td>
                    </tr>
                `;
            });
        });
        
    } catch (error) {
        console.error('Error al cargar empleados:', error);
        
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="error-text">
                    <div>Error al cargar datos: ${error.message || 'Error desconocido'}</div>
                    <div><small>Pruebe con un código de empleado específico o reinicie la aplicación</small></div>
                    <button onclick="cargarEmpleadosParaRegistro()" class="btn-sm btn-secondary mt-2">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
    
    // Evento para tecla Enter en el campo de búsqueda
    const codigoInput = document.getElementById('codigoRegistroBusqueda');
    if (codigoInput) {
        codigoInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                cargarEmpleadosParaRegistro();
            }
        });
    }
}

// Función para seleccionar un empleado para registro
function seleccionarEmpleadoRegistro(id, nombre) {
    console.log('Seleccionando empleado para registro:', id, nombre);
    
    // Abrir el modal correspondiente para registro
    openAttendancePhotoModal(id, nombre);
}

// ===========================================================================
// 9. MODAL DE FOTO PARA REGISTRO (EXISTENTE MANTENIDA)
// ===========================================================================
window.openAttendancePhotoModal = async function(id_empleado) {
    // VALIDACIÓN ESTRICTA: Verificar horarios antes de abrir el modal
    console.log('Validating schedule before opening photo modal for employee:', id_empleado);
    
    const bogotaDate = getBogotaDateSafe();
    const hoy = getBogotaDateStringSafe(); // YYYY-MM-DD en zona horaria de Bogotá
    const diaSemana = window.Bogota?.getDayOfWeek?.() ?? bogotaDate.getDay(); // 0=domingo, 1=lunes, etc.
    
    try {
        const response = await fetch(`api/check-employee-schedule.php?employee_id=${id_empleado}&fecha=${hoy}&dia_semana=${diaSemana}`, {
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // Si no tiene horarios, mostrar error y NO abrir el modal
        if (!data.success || !data.tiene_horario) {
            const errorMessage = data.message || 'El empleado no tiene horario asignado para hoy';
            console.log('Schedule validation failed for photo modal:', errorMessage);
            alert('❌ ERROR: ' + errorMessage + '\n\nNo se puede abrir el modal de captura de foto.');
            return; // NO continuar abriendo el modal
        }
        
        console.log('Schedule validation passed, opening photo modal...');
        
    } catch (error) {
        console.error('Error validating schedule for photo modal:', error);
        alert('❌ ERROR: Error al validar horario del empleado\n\nNo se puede abrir el modal de captura de foto.');
        return; // NO continuar abriendo el modal
    }
    
    // Si la validación pasa, continuar con la apertura normal del modal
    empleadoSeleccionado = id_empleado;
    document.getElementById('attendancePhotoModal').classList.add('show');
    const video = document.getElementById('video');
    navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
        video.srcObject = stream;
    }).catch(err => {
        console.error("Error accediendo a la cámara:", err);
        alert("Error al acceder a la cámara. Por favor verifique los permisos.");
    });
    
    document.getElementById('canvas').style.display = 'none';
    document.getElementById('photoPreview').innerHTML = '';
    document.getElementById('saveAttendanceBtn').disabled = true;
    
    document.getElementById('takePhotoBtn').style.display = 'inline-flex';
    document.getElementById('saveAttendanceBtn').style.display = 'inline-flex';
};

window.closeAttendancePhotoModal = function() {
    document.getElementById('attendancePhotoModal').classList.remove('show');
    const video = document.getElementById('video');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
};

function takePhoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    
    if (!video.srcObject || !video.srcObject.active) {
        alert("La cámara no está activa. Por favor recargue la página e intente de nuevo.");
        return;
    }
    
    canvas.style.display = 'none';
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    imageBase64 = canvas.toDataURL('image/jpeg');
    
    document.getElementById('photoPreview').innerHTML = `<img src="${imageBase64}" alt="Vista previa">`;
    
    const saveBtn = document.getElementById('saveAttendanceBtn');
    saveBtn.disabled = false;
    saveBtn.style.display = 'inline-flex';
}

function saveAttendance() {
    if (!imageBase64) {
        alert("Debe tomar una foto primero.");
        return;
    }
    
    const saveBtn = document.getElementById('saveAttendanceBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;

    const fechaBogota = getBogotaDateStringSafe();
    const horaBogota = getBogotaTimeStringSafe();
    const requestData = {
        id_empleado: empleadoSeleccionado,
        fecha: fechaBogota,
        hora: horaBogota,
        metodo: 'facial', // El backend decidirá si corresponde entrada o salida
        observaciones: 'Registro desde módulo de asistencia',
        foto: imageBase64 // Incluir la foto capturada
    };
    
    fetch('api/horarios-personalizados/register-attendance-personalized.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(r => {
        if (!r.ok) {
            throw new Error(`HTTP error! status: ${r.status}`);
        }
        return r.json();
    })
    .then(res => {
        if (res.success) {
            const registro = res.registro || res.data || {};
            const tipoRegistro = registro.tipo || 'ENTRADA';
            const etiqueta = tipoRegistro === 'SALIDA' ? 'Salida' : 'Entrada';
            showNotification(`${etiqueta} registrada correctamente`, 'success');

            closeAttendancePhotoModal();
            closeAttendanceRegisterModal();

            loadAttendanceDay();
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo registrar la asistencia.'), 'error');

            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error al registrar asistencia:', error);
        showNotification('Error al comunicarse con el servidor. Por favor intente de nuevo.', 'error');
        
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// ===========================================================================
// 10. REGISTRAR SALIDA
// ===========================================================================
window.registrarSalida = function(id_empleado, fecha, id_horario) {
    if(!confirm("¿Está seguro de registrar la salida?")) return;
    
    const salidaParams = new URLSearchParams({
        id_empleado: id_empleado,
        fecha: fecha
    });

    if (id_horario !== undefined && id_horario !== null && String(id_horario).trim() !== '') {
        salidaParams.append('id_horario', id_horario);
    }

    fetch('api/attendance/register-salida.php', {
        method: 'POST',
        body: salidaParams
    })
    .then(async response => {
        // Verificar si la respuesta es exitosa
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Obtener el texto de la respuesta para debugging
        const responseText = await response.text();
        console.log('Response from register-salida.php:', responseText);
        
        // Intentar parsear como JSON
        try {
            return JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was not valid JSON. Response text:', responseText);
            
            // Si contiene HTML con errores, extraer el mensaje de error
            if (responseText.includes('<br />') || responseText.includes('<b>')) {
                throw new Error('Server returned HTML instead of JSON. Check server logs for PHP errors.');
            }
            
            throw new Error(`Invalid JSON response: ${parseError.message}`);
        }
    })
    .then(res => {
        if (res.success) {
            showNotification('Salida registrada correctamente', 'success');
            loadAttendanceDay();
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo registrar la salida.'), 'error');
        }
    })
    .catch(error => {
        console.error('Error al registrar salida:', error);
        showNotification('Error al comunicarse con el servidor: ' + error.message, 'error');
    });
};

// ===========================================================================
// 11. FUNCIONALIDAD DE AMPLIACIÓN DE FOTOS
// ===========================================================================
window.openPhotoModal = function(photoUrl, nombreEmpleado = '') {
    let modal = document.getElementById('photoModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'photoModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="photo-modal-content">
                <h3 id="photoModalTitle"></h3>
                <img id="photoModalImage" src="" alt="Foto de asistencia">
                <button class="photo-modal-close" onclick="closePhotoModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    const modalImg = document.getElementById('photoModalImage');
    const modalTitle = document.getElementById('photoModalTitle');
    
    modalImg.src = photoUrl;
    
    if (nombreEmpleado) {
        modalTitle.textContent = nombreEmpleado;
        modalTitle.style.display = 'block';
    } else {
        modalTitle.style.display = 'none';
    }
    
    modalImg.style.opacity = '0';
    modal.classList.add('show');
    
    modalImg.onload = function() {
        setTimeout(() => {
            modalImg.style.opacity = '1';
        }, 100);
    };
    
    if (modalImg.complete) {
        setTimeout(() => {
            modalImg.style.opacity = '1';
        }, 100);
    }
};

window.closePhotoModal = function() {
    const modal = document.getElementById('photoModal');
    if (!modal) return;
    
    const img = document.getElementById('photoModalImage');
    
    img.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.remove('show');
    }, 300);
};






function openObservationModal(idAsistencia, tipo, empleado, fecha, hora, observacionActual = '') {
    // Guardar datos en variables globales
    observacionIdAsistencia = idAsistencia;
    observacionTipo = tipo;
    
    // Actualizar el título del modal
    document.getElementById('observationModalTitle').textContent = 
        `${tipo === 'ENTRADA' ? 'Entrada' : 'Salida'} - Observación`;
    
    // Actualizar la información del modal
    document.getElementById('observationModalInfo').innerHTML = 
        `<strong>Empleado:</strong> ${empleado}<br>` +
        `<strong>Fecha:</strong> ${formatDate(fecha)}<br>` +
        `<strong>Hora ${tipo.toLowerCase()}:</strong> ${hora}`;
    
    // Establecer la observación actual (si existe)
    document.getElementById('observacionTexto').value = observacionActual || '';
    
    // Actualizar el contador de caracteres
    updateCharCounter();
    
    // Configurar los campos ocultos
    document.getElementById('observacionIdAsistencia').value = idAsistencia;
    document.getElementById('observacionTipo').value = tipo;
    
    // Mostrar el modal
    document.getElementById('observationModal').classList.add('show');
    
    // Enfocar el campo de texto
    setTimeout(() => {
        document.getElementById('observacionTexto').focus();
    }, 300);
}
/**
 * Cierra el modal de observaciones
 */
function closeObservationModal() {
    document.getElementById('observationModal').classList.remove('show');
    
    // Limpiar variables globales
    observacionIdAsistencia = null;
    observacionTipo = null;
}

/**
 * Actualiza el contador de caracteres
 */
function updateCharCounter() {
    const textarea = document.getElementById('observacionTexto');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        charCount.textContent = textarea.value.length;
        
        // Cambiar color si se acerca al límite
        if (textarea.value.length >= 180) {
            charCount.style.color = '#dc3545'; // Rojo cuando está cerca del límite
        } else {
            charCount.style.color = ''; // Color por defecto
        }
    }
}
/**
 * Guarda la observación
 */
function saveObservation() {
    const idAsistencia = document.getElementById('observacionIdAsistencia').value;
    const observacion = document.getElementById('observacionTexto').value;
    
    if (!idAsistencia) {
        showNotification('Error: ID de asistencia no válido', 'error');
        return;
    }
    
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('btnSaveObservation');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    // Enviar solicitud al servidor
    fetch('api/attendance/update_observation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            id_asistencia: idAsistencia,
            observacion: observacion
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Observación guardada correctamente', 'success');
            closeObservationModal();
            
            // Recargar los datos para mostrar la observación actualizada
            loadAttendanceDay();
        } else {
            showNotification('Error: ' + (data.message || 'No se pudo guardar la observación'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de comunicación con el servidor', 'error');
    })
    .finally(() => {
        // Restaurar el botón
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// ===========================================================================
// FUNCIONES AUXILIARES PARA MODALES
// ===========================================================================

/**
 * Función genérica para cerrar modales
 * @param {string} modalId - ID del modal a cerrar
 */
window.cerrarModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
};

/**
 * Función genérica para abrir modales
 * @param {string} modalId - ID del modal a abrir
 */
window.abrirModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
};

/**
 * Función para exportar a Excel
 * @param {string} tipo - Tipo de exportación
 */
window.exportarExcel = function(tipo) {
    // Implementar lógica de exportación según el tipo
    console.log('Exportando', tipo);
    showNotification('Función de exportación en desarrollo', 'info');
};

// ===========================================================================
// FUNCIONES PARA EL SISTEMA DE ASISTENCIA BIOMÉTRICA
// ===========================================================================

/**
 * Función global para abrir el modal de registro de asistencia
 */
function openAttendanceRegisterModal() {
    console.log('Abriendo modal de registro de asistencia');
    
    // Verificar si el modal existe
    const modal = document.getElementById('attendanceRegisterModal');
    if (modal) {
        // Usar Bootstrap para mostrar el modal
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        console.log('Modal de asistencia abierto');
    } else {
        console.error('Modal attendanceRegisterModal no encontrado');
        alert('Error: No se pudo abrir el modal de registro de asistencia');
    }
}

/**
 * Función para configurar los listeners de los modales
 */
function setupAttendanceModalListeners() {
    console.log('Configurando listeners de modales');
    
    // Configurar modal de registro de asistencia
    const attendanceModal = document.getElementById('attendanceRegisterModal');
    if (attendanceModal) {
        attendanceModal.addEventListener('shown.bs.modal', function() {
            console.log('Modal de asistencia mostrado');
        });
        
        attendanceModal.addEventListener('hidden.bs.modal', function() {
            console.log('Modal de asistencia ocultado');
        });
    }
    
    // Configurar modal de estado biométrico
    const biometricModal = document.getElementById('biometricStatusModal');
    if (biometricModal) {
        biometricModal.addEventListener('shown.bs.modal', function() {
            console.log('Modal biométrico mostrado');
        });
    }
}

/**
 * Función para mostrar notificaciones
 */
function showNotification(message, type = 'info') {
    console.log(`Notificación (${type}):`, message);
    
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Función para abrir verificación biométrica para un empleado específico
// Hacer las funciones globales disponibles
window.openAttendanceRegisterModal = openAttendanceRegisterModal;
window.setupAttendanceModalListeners = setupAttendanceModalListeners;
window.showNotification = showNotification;
window.loadAttendanceDay = loadAttendanceDay;
