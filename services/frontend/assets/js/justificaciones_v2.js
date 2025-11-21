/**
 * Sistema de Justificaciones v2.0
 * Funciona con nueva estructura de base de datos
 */

// Variables globales
let empleadosData = [];
let justificacionesData = [];
let configSistema = {};

// Configuración de notificaciones
window.globalNotification = function(message, type = 'info', duration = 5000) {
    console.log(`🔔 Notification [${type}]:`, message);
    
    // Crear contenedor de notificaciones si no existe
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
    
    // Crear notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    notification.style.cssText = `margin-bottom: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);`;
    
    notification.innerHTML = `
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <strong>${type === 'error' ? 'Error:' : type === 'success' ? 'Éxito:' : 'Info:'}</strong> ${message}
    `;
    
    container.appendChild(notification);
    
    // Auto-dismiss
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }
};

/**
 * Inicializar modal de justificaciones
 */
function inicializarModalJustificaciones() {
    console.log('🚀 Inicializando sistema de justificaciones v2.0');
    
    // Event listeners para el modal
    const modal = document.getElementById('justificacionesModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', async function() {
            console.log('📖 Modal abierto - cargando datos...');
            await Promise.all([
                cargarConfiguracion(),
                cargarSedes(),
                // No cargar establecimientos inicialmente, solo cuando se seleccione una sede
                cargarEmpleadosElegibles(),
                cargarJustificacionesRecientes()
            ]);
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            console.log('📕 Modal cerrado - limpiando formularios');
            limpiarFormularioJustificacion();
        });
    }
    
    // Event listeners para formularios
    setupFormularioEventListeners();
    setupFiltrosEventListeners();
}

/**
 * Configurar event listeners para formularios
 */
function setupFormularioEventListeners() {
    // Selector de empleado
    const selectEmpleado = document.getElementById('empleadoSelect');
    if (selectEmpleado) {
        selectEmpleado.addEventListener('change', function() {
            const empleadoId = this.value;
            if (empleadoId) {
                mostrarDetallesEmpleado(empleadoId);
                actualizarEstadoElegibilidad(empleadoId);
            }
        });
    }
    
    // Tipo de falta
    const tipoFalta = document.getElementById('tipoFalta');
    if (tipoFalta) {
        tipoFalta.addEventListener('change', function() {
            toggleCamposHorariosParciales(this.value === 'parcial');
        });
    }
    
    // Formulario de justificación
    const form = document.getElementById('formJustificacion');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await guardarJustificacion();
        });
    }
    
    // Botón de limpiar
    const btnLimpiar = document.getElementById('btnLimpiarJustificacion');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', limpiarFormularioJustificacion);
    }
}

/**
 * Configurar event listeners para filtros
 */
function setupFiltrosEventListeners() {
    // Filtro de sede
    const sedeSelect = document.getElementById('filtroSede');
    if (sedeSelect) {
        sedeSelect.addEventListener('change', async function() {
            await cargarEstablecimientos(this.value);
            await cargarEmpleadosElegibles();
        });
    }
    
    // Filtro de establecimiento
    const establecimientoSelect = document.getElementById('filtroEstablecimiento');
    if (establecimientoSelect) {
        establecimientoSelect.addEventListener('change', async function() {
            await cargarEmpleadosElegibles();
        });
    }
    
    // Filtro de fecha
    const fechaFalta = document.getElementById('fechaFalta');
    if (fechaFalta) {
        fechaFalta.addEventListener('change', async function() {
            await cargarEmpleadosElegibles();
        });
    }
}

/**
 * Cargar configuración del sistema
 */
async function cargarConfiguracion() {
    try {
        console.log('⚙️ Cargando configuración del sistema...');
        
        // Usar ruta relativa simple
        const apiUrl = 'api/justificaciones.php?action=config';
        
        console.log('🌐 API URL:', apiUrl);
        
        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('📄 Config response (first 200 chars):', text.substring(0, 200));
        
        const data = JSON.parse(text);
        
        if (data.success) {
            configSistema = data.configuracion;
            console.log('✅ Configuración cargada:', configSistema);
            aplicarConfiguracion();
        } else {
            throw new Error(data.message || 'Error cargando configuración');
        }
        
    } catch (error) {
        console.error('❌ Error cargando configuración:', error);
        globalNotification('Error cargando configuración del sistema', 'error');
        
        // Configuración por defecto
        configSistema = {
            horas_limite_justificacion: { valor: 16 },
            tipos_motivo_permitidos: { valor: ['Enfermedad', 'Cita médica', 'Emergencia familiar', 'Trámite personal', 'Otro'] }
        };
    }
}

/**
 * Aplicar configuración al UI
 */
function aplicarConfiguracion() {
    // Actualizar límite de horas en la interfaz
    const limitInfo = document.getElementById('limitInfo');
    if (limitInfo && configSistema.horas_limite_justificacion) {
        limitInfo.textContent = `Límite: ${configSistema.horas_limite_justificacion.valor} horas`;
    }
    
    // Actualizar opciones de motivo
    const motivoSelect = document.getElementById('motivo');
    if (motivoSelect && configSistema.tipos_motivo_permitidos) {
        const motivos = configSistema.tipos_motivo_permitidos.valor;
        motivoSelect.innerHTML = '<option value="">Seleccione un motivo...</option>';
        
        motivos.forEach(motivo => {
            const option = document.createElement('option');
            option.value = motivo;
            option.textContent = motivo;
            motivoSelect.appendChild(option);
        });
    }
}

/**
 * Cargar sedes
 */
async function cargarSedes() {
    try {
        console.log('🏢 Cargando sedes...');
        
        const url = 'api/get-sedes.php';
        console.log('🌐 Sedes URL:', url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('📄 Sedes response (first 200 chars):', text.substring(0, 200));
        
        const data = JSON.parse(text);
        
        if (data.success) {
            poblarSelectSedes(data.sedes || data.data);
            console.log('✅ Sedes cargadas:', (data.sedes || data.data).length);
        } else {
            throw new Error(data.message || 'Error cargando sedes');
        }
        
    } catch (error) {
        console.error('❌ Error cargando sedes:', error);
        globalNotification('Error cargando sedes', 'error');
    }
}

/**
 * Poblar select de sedes
 */
function poblarSelectSedes(sedes) {
    const select = document.getElementById('filtroSede');
    if (!select) return;
    
    select.innerHTML = '<option value="">Todas las sedes</option>';
    
    sedes.forEach(sede => {
        const option = document.createElement('option');
        option.value = sede.ID_SEDE || sede.id;
        option.textContent = sede.NOMBRE || sede.nombre;
        select.appendChild(option);
    });
}

/**
 * Cargar establecimientos
 */
async function cargarEstablecimientos(sedeId = null) {
    try {
        console.log('🏢 Cargando establecimientos...');
        
        let url = 'api/get-establecimientos.php';
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
        }
        
        console.log('🌐 Establecimientos URL:', url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('📄 Establecimientos response (first 200 chars):', text.substring(0, 200));
        
        const data = JSON.parse(text);
        
        if (data.success) {
            poblarSelectEstablecimientos(data.establecimientos || data.data);
            console.log('✅ Establecimientos cargados:', (data.establecimientos || data.data).length);
        } else {
            throw new Error(data.message || 'Error cargando establecimientos');
        }
        
    } catch (error) {
        console.error('❌ Error cargando establecimientos:', error);
        globalNotification('Error cargando establecimientos', 'error');
    }
}

/**
 * Poblar select de establecimientos
 */
function poblarSelectEstablecimientos(establecimientos) {
    const select = document.getElementById('filtroEstablecimiento');
    if (!select) return;
    
    select.innerHTML = '<option value="">Todos los establecimientos</option>';
    
    establecimientos.forEach(est => {
        const option = document.createElement('option');
        option.value = est.ID_ESTABLECIMIENTO || est.id;
        option.textContent = est.NOMBRE || est.nombre;
        select.appendChild(option);
    });
}

/**
 * Cargar empleados elegibles
 */
async function cargarEmpleadosElegibles() {
    try {
        console.log('👥 Cargando empleados elegibles...');
        
        const fechaFalta = document.getElementById('fechaFalta')?.value || new Date().toISOString().split('T')[0];
        const sedeId = document.getElementById('filtroSede')?.value || '';
        const establecimientoId = document.getElementById('filtroEstablecimiento')?.value || '';
        
        let url = `api/justificaciones.php?action=empleados_elegibles&fecha=${fechaFalta}`;
        if (sedeId) url += `&sede_id=${sedeId}`;
        if (establecimientoId) url += `&establecimiento_id=${establecimientoId}`;
        
        console.log('🌐 Empleados URL:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('📄 Empleados response (first 300 chars):', text.substring(0, 300));
        
        const data = JSON.parse(text);
        
        if (data.success) {
            empleadosData = data.empleados;
            poblarSelectEmpleados(data.empleados);
            actualizarEstadisticasEmpleados(data.empleados);
            console.log('✅ Empleados elegibles cargados:', data.empleados.length);
        } else {
            throw new Error(data.message || 'Error cargando empleados');
        }
        
    } catch (error) {
        console.error('❌ Error cargando empleados elegibles:', error);
        globalNotification('Error cargando empleados elegibles. Revisa la consola para más detalles.', 'error');
        
        // Limpiar select en caso de error
        const select = document.getElementById('empleadoSelect');
        if (select) {
            select.innerHTML = '<option value="">Error cargando empleados</option>';
        }
    }
}

/**
 * Poblar lista de empleados elegibles
 */
function poblarSelectEmpleados(empleados) {
    const container = document.getElementById('listaEmpleadosElegibles');
    const loadingElement = document.getElementById('loadingEmpleados');
    const noEmpleadosMessage = document.getElementById('noEmpleadosMessage');
    
    if (!container) {
        console.error('❌ Container listaEmpleadosElegibles no encontrado');
        return;
    }
    
    // Ocultar loading
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
    
    if (empleados.length === 0) {
        container.innerHTML = '';
        if (noEmpleadosMessage) {
            noEmpleadosMessage.style.display = 'block';
        }
        return;
    }
    
    // Ocultar mensaje de no empleados
    if (noEmpleadosMessage) {
        noEmpleadosMessage.style.display = 'none';
    }
    
    // Crear lista de empleados
    let html = '';
    empleados.forEach(emp => {
        // Con la nueva estructura simplificada, determinamos elegibilidad basada en turnos disponibles
        const esElegible = emp.turnos_disponibles && emp.turnos_disponibles.length > 0 && !emp.ya_justificado;
        const badgeClass = esElegible ? 'bg-success' : 'bg-warning';
        const cursorStyle = esElegible ? 'cursor: pointer;' : 'cursor: not-allowed; opacity: 0.6;';
        
        // Determinar estado de elegibilidad para mostrar
        let estadoElegibilidad = 'Sin turnos disponibles';
        if (emp.ya_justificado) {
            estadoElegibilidad = 'Ya justificado';
        } else if (emp.turnos_disponibles && emp.turnos_disponibles.length > 0) {
            estadoElegibilidad = 'Elegible';
        }
        
        // Información de turnos
        let turnosInfo = '';
        if (emp.multiple_turnos && emp.turnos_disponibles && emp.turnos_disponibles.length > 1) {
            turnosInfo = `<br><small class="text-info">🕐 ${emp.turnos_disponibles.length} turnos disponibles</small>`;
        } else if (emp.turnos_disponibles && emp.turnos_disponibles.length === 1) {
            const turno = emp.turnos_disponibles[0];
            turnosInfo = `<br><small class="text-muted">🕐 ${turno.hora_entrada}-${turno.hora_salida}</small>`;
        }
        
        html += `
            <div class="empleado-item p-2 border rounded mb-2" 
                 style="${cursorStyle}"
                 data-empleado-id="${emp.id}"
                 data-empleado='${JSON.stringify(emp)}'
                 ${esElegible ? 'onclick="seleccionarEmpleado(this)"' : ''}>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${emp.nombre_completo}</strong>
                        <br>
                        <small class="text-muted">
                            ${emp.codigo} | ${emp.establecimiento_nombre || 'Sin establecimiento'}
                        </small>
                        ${turnosInfo}
                    </div>
                    <div>
                        <span class="badge ${badgeClass}">${estadoElegibilidad}</span>
                        ${emp.ya_justificado ? '<br><small class="text-warning">Ya justificado</small>' : ''}
                        ${emp.multiple_turnos ? '<br><small class="text-primary">Múltiples turnos</small>' : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    console.log('✅ Lista de empleados poblada con', empleados.length, 'empleados');
}

/**
 * Actualizar estadísticas de empleados
 */
function actualizarEstadisticasEmpleados(empleados) {
    const stats = {
        total: empleados.length,
        elegibles: empleados.filter(e => e.turnos_disponibles && e.turnos_disponibles.length > 0 && !e.ya_justificado).length,
        ya_justificados: empleados.filter(e => e.ya_justificado).length,
        sin_turnos: empleados.filter(e => !e.turnos_disponibles || e.turnos_disponibles.length === 0).length
    };
    
    // Actualizar UI con estadísticas
    const statsContainer = document.getElementById('empleadosStats');
    if (statsContainer) {
        statsContainer.innerHTML = `
            <small class="text-muted">
                Total: ${stats.total} | 
                Elegibles: <span class="text-success">${stats.elegibles}</span> | 
                Ya justificados: <span class="text-warning">${stats.ya_justificados}</span> |
                Sin turnos: <span class="text-danger">${stats.sin_turnos}</span>
            </small>
        `;
    }
}

/**
 * Seleccionar empleado de la lista
 */
function seleccionarEmpleado(elemento) {
    try {
        // Remover selección anterior
        document.querySelectorAll('.empleado-item').forEach(item => {
            item.classList.remove('border-primary', 'bg-light');
        });
        
        // Marcar como seleccionado
        elemento.classList.add('border-primary', 'bg-light');
        
        // Obtener datos del empleado
        const empleadoData = JSON.parse(elemento.dataset.empleado);
        
        // Calcular horas programadas basándose en los turnos disponibles
        let horasCalculadas = 8.0; // Default fallback
        
        if (empleadoData.turnos_disponibles && empleadoData.turnos_disponibles.length > 0) {
            horasCalculadas = 0;
            empleadoData.turnos_disponibles.forEach(turno => {
                const horasDelTurno = calcularHorasTurno(turno.hora_entrada, turno.hora_salida);
                horasCalculadas += horasDelTurno;
            });
            console.log('💼 Horas calculadas basándose en turnos:', horasCalculadas);
        } else {
            console.log('💼 Usando horas por defecto (no hay turnos disponibles):', horasCalculadas);
        }
        
        // Llenar campos básicos del formulario
        document.getElementById('empleadoSeleccionado').value = empleadoData.id;
        document.getElementById('empleadoNombre').value = empleadoData.nombre_completo;
        document.getElementById('fechaFalta').value = empleadoData.fecha_falta || new Date().toISOString().split('T')[0];
        document.getElementById('fechaFaltaDisplay').value = empleadoData.fecha_falta || new Date().toLocaleDateString('es-ES');
        document.getElementById('horasProgramadas').value = horasCalculadas.toFixed(2);
        document.getElementById('horasProgramadasDisplay').value = horasCalculadas.toFixed(2) + ' horas';
        
        // Manejar múltiples turnos
        if (empleadoData.multiple_turnos && empleadoData.turnos_disponibles && empleadoData.turnos_disponibles.length > 1) {
            mostrarSelectorTurnos(empleadoData);
        } else {
            ocultarSelectorTurnos();
            // Si solo hay un turno, seleccionarlo automáticamente y remover required
            if (empleadoData.turnos_disponibles && empleadoData.turnos_disponibles.length === 1) {
                const turno = empleadoData.turnos_disponibles[0];
                const turnoInput = document.getElementById('turnoSeleccionado');
                if (turnoInput) {
                    turnoInput.value = turno.id_empleado_horario;
                    turnoInput.removeAttribute('required'); // Quitar required cuando hay un solo turno
                }
            }
        }
        
        console.log('✅ Empleado seleccionado:', empleadoData.nombre_completo);
        
    } catch (error) {
        console.error('❌ Error seleccionando empleado:', error);
        globalNotification('Error seleccionando empleado', 'error');
    }
}

/**
 * Mostrar selector de turnos para empleados con múltiples turnos
 */
function mostrarSelectorTurnos(empleadoData) {
    const selectorContainer = document.getElementById('selectorTurnosContainer');
    const selectTurnos = document.getElementById('turnoSeleccionado');
    
    if (!selectorContainer || !selectTurnos) {
        console.error('❌ Elementos del selector de turnos no encontrados');
        return;
    }
    
    // Mostrar el contenedor
    selectorContainer.style.display = 'block';
    
    // Agregar required cuando se muestra el selector
    selectTurnos.setAttribute('required', 'required');
    
    // Limpiar opciones anteriores
    selectTurnos.innerHTML = '';
    
    // Agregar opción por defecto
    selectTurnos.add(new Option('Seleccione un turno...', ''));
    
    // Agregar turnos individuales
    empleadoData.turnos_disponibles.forEach(turno => {
        const horasDelTurno = calcularHorasTurno(turno.hora_entrada, turno.hora_salida);
        const optionText = `${turno.nombre_turno} (${turno.hora_entrada} - ${turno.hora_salida}) - ${horasDelTurno}h`;
        const option = new Option(optionText, turno.id_empleado_horario);
        option.dataset.horas = horasDelTurno;
        selectTurnos.add(option);
    });
    
    // Agregar opción para ambos turnos si hay más de uno
    if (empleadoData.turnos_disponibles.length > 1) {
        const todosIds = empleadoData.turnos_disponibles.map(t => t.id_empleado_horario).join(',');
        const horasTotales = empleadoData.turnos_disponibles.reduce((total, turno) => {
            return total + calcularHorasTurno(turno.hora_entrada, turno.hora_salida);
        }, 0);
        const option = new Option(`🕐 Ambos turnos - ${horasTotales.toFixed(2)}h`, 'TODOS:' + todosIds);
        option.dataset.horas = horasTotales;
        selectTurnos.add(option);
    }
    
    // Agregar event listener para actualizar horas cuando se selecciona un turno
    selectTurnos.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.horas) {
            const horasSeleccionadas = parseFloat(selectedOption.dataset.horas);
            document.getElementById('horasProgramadas').value = horasSeleccionadas.toFixed(2);
            document.getElementById('horasProgramadasDisplay').value = horasSeleccionadas.toFixed(2) + ' horas';
            console.log('🕐 Horas actualizadas a:', horasSeleccionadas);
        }
    });
    
    console.log('🕐 Selector de turnos mostrado con', empleadoData.turnos_disponibles.length, 'opciones');
}

/**
 * Ocultar selector de turnos
 */
function ocultarSelectorTurnos() {
    const selectorContainer = document.getElementById('selectorTurnosContainer');
    const selectTurnos = document.getElementById('turnoSeleccionado');
    
    if (selectorContainer) {
        selectorContainer.style.display = 'none';
    }
    
    // Quitar required cuando se oculta el selector
    if (selectTurnos) {
        selectTurnos.removeAttribute('required');
    }
}

/**
 * Cargar justificaciones recientes
 */
async function cargarJustificacionesRecientes() {
    try {
        console.log('📋 Cargando justificaciones recientes...');
        
        const url = 'api/justificaciones.php?action=recientes&limit=10';
        
        console.log('🌐 Justificaciones URL:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('📄 Justificaciones response (first 300 chars):', text.substring(0, 300));
        
        const data = JSON.parse(text);
        
        if (data.success) {
            justificacionesData = data.justificaciones;
            mostrarJustificacionesRecientes(data.justificaciones);
            console.log('✅ Justificaciones recientes cargadas:', data.justificaciones.length);
        } else {
            throw new Error(data.message || 'Error cargando justificaciones');
        }
        
    } catch (error) {
        console.error('❌ Error cargando justificaciones recientes:', error);
        globalNotification('Error cargando justificaciones recientes', 'error');
        
        // Mostrar error en la tabla
        const tbody = document.querySelector('#tablaJustificacionesRecientes tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error cargando datos</td></tr>';
        }
    }
}

/**
 * Mostrar justificaciones recientes en tabla
 */
function mostrarJustificacionesRecientes(justificaciones) {
    const tbody = document.querySelector('#tablaJustificacionesRecientes tbody');
    if (!tbody) {
        console.warn('⚠️ No se encontró tabla de justificaciones recientes');
        return;
    }
    
    if (justificaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay justificaciones recientes</td></tr>';
        return;
    }

    // Forzar actualización del DOM
    tbody.innerHTML = '';
    
    const filas = justificaciones.map(j => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${j.empleado_nombre_completo || (j.empleado_nombre + ' ' + j.empleado_apellido)}</td>
            <td>${formatearFecha(j.fecha_falta)}</td>
            <td class="text-truncate" style="max-width: 200px;" title="${j.motivo}">${j.motivo}</td>
            <td>
                <span class="badge bg-info">${j.turno_descripcion || j.turno_nombre || 'Todos los turnos'}</span>
            </td>
            <td>${formatearFecha(j.created_at, true)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="verDetalleJustificacion(${j.id})">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        return row;
    });
    
    // Agregar filas una por una para asegurar que se rendericen
    filas.forEach(fila => tbody.appendChild(fila));
    
    console.log('✅ Tabla de justificaciones recientes actualizada con', justificaciones.length, 'registros');
}

/**
 * Mostrar detalles del empleado seleccionado
 */
function mostrarDetallesEmpleado(empleadoId) {
    const empleado = empleadosData.find(e => e.id == empleadoId);
    if (!empleado) return;
    
    const container = document.getElementById('detallesEmpleado');
    if (!container) return;

    // Determinar estado de elegibilidad
    let estadoElegibilidad = 'Sin turnos disponibles';
    let estadoColor = 'warning';
    if (empleado.ya_justificado) {
        estadoElegibilidad = 'Ya justificado';
        estadoColor = 'secondary';
    } else if (empleado.turnos_disponibles && empleado.turnos_disponibles.length > 0) {
        estadoElegibilidad = 'Elegible';
        estadoColor = 'success';
    }
    
    container.innerHTML = `
        <div class="card border-info">
            <div class="card-body p-3">
                <h6 class="card-title mb-2">${empleado.nombre_completo}</h6>
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted">DNI:</small><br>
                        <strong>${empleado.codigo}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Estado:</small><br>
                        <span class="badge bg-${estadoColor}">${estadoElegibilidad}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Establecimiento:</small><br>
                        ${empleado.establecimiento_nombre || 'Sin establecimiento'}
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Sede:</small><br>
                        ${empleado.sede_nombre || 'Sin sede'}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.style.display = 'block';
}

/**
 * Actualizar estado de elegibilidad
 */
function actualizarEstadoElegibilidad(empleadoId) {
    const empleado = empleadosData.find(e => e.id == empleadoId);
    if (!empleado) return;
    
    const alertContainer = document.getElementById('alertasJustificacion');
    if (!alertContainer) return;
    
    alertContainer.innerHTML = '';
    
    if (empleado.ya_justificado) {
        alertContainer.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Este empleado ya tiene una justificación para la fecha seleccionada.
            </div>
        `;
    } else if (!empleado.turnos_disponibles || empleado.turnos_disponibles.length === 0) {
        alertContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-clock"></i>
                Este empleado no tiene turnos programados para esta fecha.
            </div>
        `;
    } else {
        alertContainer.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Este empleado puede ser justificado para la fecha seleccionada.
                ${empleado.multiple_turnos ? '<br><small>Puede justificar turnos específicos o todos los turnos.</small>' : ''}
            </div>
        `;
    }
}

/**
 * Toggle campos de horarios parciales
 */
function toggleCamposHorariosParciales(mostrar) {
    const container = document.getElementById('horariosParcialesContainer');
    if (container) {
        container.style.display = mostrar ? 'block' : 'none';
        
        const horaInicio = document.getElementById('horaInicioFalta');
        const horaFin = document.getElementById('horaFinFalta');
        
        if (mostrar) {
            horaInicio?.setAttribute('required', 'required');
            horaFin?.setAttribute('required', 'required');
        } else {
            horaInicio?.removeAttribute('required');
            horaFin?.removeAttribute('required');
            if (horaInicio) horaInicio.value = '';
            if (horaFin) horaFin.value = '';
        }
    }
}

/**
 * Guardar justificación
 */
async function guardarJustificacion() {
    try {
        console.log('💾 Guardando justificación...');
        
        const formData = new FormData(document.getElementById('formJustificacion'));
        const data = {
            empleado_id: formData.get('empleado_id'),
            fecha_falta: formData.get('fecha_falta'),
            motivo: formData.get('motivo'),
            detalle_adicional: formData.get('detalle_adicional'),
            tipo_falta: formData.get('tipo_falta') || 'completa',
            horas_programadas: parseFloat(formData.get('horas_programadas')) || 8.0,
            impacto_salario: formData.get('impacto_salario') === 'on' ? 1 : 0
        };
        
        // Manejar turnos múltiples
        const turnoSeleccionado = formData.get('turno_id');
        if (turnoSeleccionado) {
            if (turnoSeleccionado.startsWith('TODOS:')) {
                // Múltiples turnos seleccionados
                data.turnos_ids = turnoSeleccionado.replace('TODOS:', '').split(',');
                data.justificar_todos_turnos = true;
            } else {
                // Un solo turno seleccionado
                data.turno_id = turnoSeleccionado;
                data.justificar_todos_turnos = false;
            }
        }
        
        // Agregar horarios si es falta parcial
        if (data.tipo_falta === 'parcial') {
            data.hora_inicio_falta = formData.get('hora_inicio_falta');
            data.hora_fin_falta = formData.get('hora_fin_falta');
        }
        
        // Validaciones
        if (!data.empleado_id) {
            throw new Error('Debe seleccionar un empleado');
        }
        
        if (!data.fecha_falta) {
            throw new Error('Debe seleccionar una fecha');
        }
        
        if (!data.motivo) {
            throw new Error('Debe seleccionar un motivo');
        }
        
        if (data.tipo_falta === 'parcial' && (!data.hora_inicio_falta || !data.hora_fin_falta)) {
            throw new Error('Para faltas parciales debe especificar las horas');
        }
        
        // Validar turno si el selector está visible
        const selectorTurnos = document.getElementById('selectorTurnosContainer');
        if (selectorTurnos && selectorTurnos.style.display !== 'none' && !turnoSeleccionado) {
            throw new Error('Debe seleccionar un turno para justificar');
        }
        
        console.log('📤 Datos a enviar:', data);
        
        const url = 'api/justificaciones.php';
        
        console.log('🌐 Guardar URL:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('📄 Response text:', text);
        
        const result = JSON.parse(text);
        
        if (result.success) {
            const mensaje = data.justificar_todos_turnos ? 
                'Justificación creada para todos los turnos exitosamente' : 
                'Justificación creada exitosamente';
            globalNotification(mensaje, 'success');
            
            // NO cerrar el modal automáticamente - dejar que el usuario lo cierre manualmente
            // const modal = bootstrap.Modal.getInstance(document.getElementById('justificacionesModal'));
            // if (modal) {
            //     modal.hide();
            // }
            
            // Limpiar formulario pero mantener empleado seleccionado
            limpiarFormularioJustificacion();
            
            // Actualizar datos sin limpiar el DOM
            await Promise.all([
                cargarEmpleadosElegibles(),
                cargarJustificacionesRecientes()
            ]);
            
            console.log('✅ Justificación guardada:', result.justificacion_id);
        } else {
            throw new Error(result.message || 'Error guardando justificación');
        }
        
    } catch (error) {
        console.error('❌ Error guardando justificación:', error);
        globalNotification(error.message || 'Error guardando justificación', 'error');
    }
}

/**
 * Limpiar formulario de justificación
 */
function limpiarFormularioJustificacion() {
    const form = document.getElementById('formJustificacion');
    if (form) {
        form.reset();
    }
    
    // Limpiar detalles del empleado
    const detalles = document.getElementById('detallesEmpleado');
    if (detalles) {
        detalles.style.display = 'none';
        detalles.innerHTML = '';
    }
    
    // Limpiar alertas
    const alertas = document.getElementById('alertasJustificacion');
    if (alertas) {
        alertas.innerHTML = '';
    }
    
    // Ocultar y limpiar selector de turnos
    ocultarSelectorTurnos();
    const selectTurnos = document.getElementById('turnoSeleccionado');
    if (selectTurnos) {
        selectTurnos.innerHTML = '';
    }
    
    // Remover selección de empleados
    document.querySelectorAll('.empleado-item').forEach(item => {
        item.classList.remove('border-primary', 'bg-light');
    });
    
    // Ocultar campos de horarios parciales
    toggleCamposHorariosParciales(false);
    
    console.log('🧹 Formulario limpiado');
}

/**
 * Ver detalle de justificación
 */
async function verDetalleJustificacion(id) {
    try {
        console.log('👁️ Viendo detalle de justificación:', id);
        
        const url = `api/justificaciones.php?action=detalle&id=${id}`;
        
        console.log('🌐 Detalle URL:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            mostrarModalDetalle(data.justificacion, data.log);
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('❌ Error viendo detalle:', error);
        globalNotification('Error cargando detalle de justificación', 'error');
    }
}

/**
 * Mostrar modal de detalle
 */
function mostrarModalDetalle(justificacion, log) {
    // TODO: Implementar modal de detalle
    console.log('📄 Detalle de justificación:', justificacion);
    console.log('📜 Log de cambios:', log);
    
    globalNotification('Modal de detalle no implementado aún', 'info');
}

/**
 * Abrir modal de justificaciones
 */
function abrirModalJustificaciones() {
    console.log('🚀 Abriendo modal de justificaciones...');
    
    const modal = new bootstrap.Modal(document.getElementById('justificacionesModal'));
    modal.show();
}

// Funciones de utilidad
function formatearFecha(fecha, incluirHora = false) {
    if (!fecha) return '-';
    
    const date = new Date(fecha);
    const opciones = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit'
    };
    
    if (incluirHora) {
        opciones.hour = '2-digit';
        opciones.minute = '2-digit';
    }
    
    return date.toLocaleDateString('es-ES', opciones);
}

function formatearEstado(estado) {
    const estados = {
        'pendiente': 'Pendiente',
        'aprobada': 'Aprobada',
        'rechazada': 'Rechazada',
        'revision': 'En Revisión'
    };
    return estados[estado] || estado;
}

function getEstadoColor(estado) {
    const colores = {
        'pendiente': 'warning',
        'aprobada': 'success',
        'rechazada': 'danger',
        'revision': 'info'
    };
    return colores[estado] || 'secondary';
}

function getEstadoElegibilidadColor(estado) {
    const colores = {
        'Elegible': 'success',
        'Ya justificado': 'warning',
        'Fuera de tiempo': 'danger',
        'Con asistencia': 'info',
        'No elegible': 'secondary'
    };
    return colores[estado] || 'secondary';
}

/**
 * Función auxiliar para calcular horas entre dos horarios
 */
function calcularHorasTurno(horaEntrada, horaSalida) {
    console.log('calcularHorasTurno - Entrada:', horaEntrada, 'Salida:', horaSalida);
    
    if (!horaEntrada || !horaSalida) {
        console.log('calcularHorasTurno - Faltan datos, devolviendo 8 por defecto');
        return 8; // Default fallback
    }
    
    try {
        // Convertir strings de hora a objetos Date para cálculo
        const [horaE, minE] = horaEntrada.split(':').map(Number);
        const [horaS, minS] = horaSalida.split(':').map(Number);
        
        console.log('calcularHorasTurno - Horas parseadas:', { horaE, minE, horaS, minS });
        
        let fechaEntrada = new Date();
        fechaEntrada.setHours(horaE, minE, 0, 0);
        
        let fechaSalida = new Date();
        fechaSalida.setHours(horaS, minS, 0, 0);
        
        // Si la hora de salida es menor que la de entrada, agregar un día
        if (fechaSalida <= fechaEntrada) {
            fechaSalida.setDate(fechaSalida.getDate() + 1);
            console.log('calcularHorasTurno - Turno nocturno detectado, agregando un día');
        }
        
        // Calcular diferencia en horas
        const diferenciaMs = fechaSalida - fechaEntrada;
        const horas = diferenciaMs / (1000 * 60 * 60);
        
        console.log('calcularHorasTurno - Resultado:', horas, 'horas');
        
        return Math.round(horas * 100) / 100; // Redondear a 2 decimales
    } catch (error) {
        console.error('Error calculando horas del turno:', error);
        return 8; // Default fallback
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 DOM cargado - inicializando justificaciones...');
    inicializarModalJustificaciones();
});

// Exponer funciones globales necesarias
window.abrirModalJustificaciones = abrirModalJustificaciones;
window.verDetalleJustificacion = verDetalleJustificacion;