// ============================================================================
// SISTEMA DE JUSTIFICACIONES DE FALTAS
// Manejo de empleados elegibles y creación de justificaciones
// ============================================================================

// Variables globales para justificaciones
let justificacionesModal;
let empleadosElegibles = [];
let empleadoSeleccionadoJustificacion = null;

// ============================================================================
// INICIALIZACIÓN DEL SISTEMA DE JUSTIFICACIONES
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    initializeJustificacionesSystem();
});

function initializeJustificacionesSystem() {
    // Inicializar modal
    const modalElement = document.getElementById('justificacionesModal');
    if (modalElement) {
        justificacionesModal = new bootstrap.Modal(modalElement);
        
        // Configurar eventos del formulario
        const formJustificacion = document.getElementById('formJustificacion');
        if (formJustificacion) {
            formJustificacion.addEventListener('submit', manejarSubmitJustificacion);
        }
        
        // Configurar evento cuando se abre el modal
        modalElement.addEventListener('shown.bs.modal', function() {
            cargarEstablecimientos();
            cargarEmpleadosElegibles();
            cargarJustificacionesRecientes();
        });
        
        console.log('Sistema de justificaciones inicializado');
    }
}

// ============================================================================
// FUNCIÓN PRINCIPAL PARA ABRIR EL MODAL
// ============================================================================
function abrirModalJustificaciones() {
    if (justificacionesModal) {
        justificacionesModal.show();
    } else {
        console.error('Modal de justificaciones no inicializado');
    }
}

// ============================================================================
// CARGA DE DATOS INICIALES
// ============================================================================
async function cargarEstablecimientos() {
    try {
        const response = await fetch('api/establecimientos.php');
        const data = await response.json();
        
        const select = document.getElementById('filtroEstablecimiento');
        select.innerHTML = '<option value="">Todos los establecimientos</option>';
        
        if (data.success && data.establecimientos) {
            data.establecimientos.forEach(establecimiento => {
                const option = document.createElement('option');
                option.value = establecimiento.id;
                option.textContent = establecimiento.nombre;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando establecimientos:', error);
    }
}

async function cargarSedesPorEstablecimiento() {
    const establecimientoId = document.getElementById('filtroEstablecimiento').value;
    const selectSede = document.getElementById('filtroSede');
    
    selectSede.innerHTML = '<option value="">Todas las sedes</option>';
    
    if (!establecimientoId) return;
    
    try {
        const response = await fetch(`api/sedes.php?establecimiento_id=${establecimientoId}`);
        const data = await response.json();
        
        if (data.success && data.sedes) {
            data.sedes.forEach(sede => {
                const option = document.createElement('option');
                option.value = sede.id;
                option.textContent = sede.nombre;
                selectSede.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando sedes:', error);
    }
}

// ============================================================================
// CARGA DE EMPLEADOS ELEGIBLES PARA JUSTIFICACIÓN
// ============================================================================
async function cargarEmpleadosElegibles() {
    const loadingDiv = document.getElementById('loadingEmpleados');
    const listaDiv = document.getElementById('listaEmpleadosElegibles');
    const noEmpleadosDiv = document.getElementById('noEmpleadosMessage');
    
    // Mostrar loading
    loadingDiv.style.display = 'block';
    listaDiv.innerHTML = '';
    noEmpleadosDiv.style.display = 'none';
    
    try {
        const establecimientoId = document.getElementById('filtroEstablecimiento').value;
        const sedeId = document.getElementById('filtroSede').value;
        
        let url = 'api/justificaciones.php?action=getEmpleadosElegibles';
        if (establecimientoId) url += `&establecimiento_id=${establecimientoId}`;
        if (sedeId) url += `&sede_id=${sedeId}`;
        
        console.log('🔍 Cargando empleados elegibles desde:', url);
        
        const response = await fetch(url);
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response headers:', response.headers.get('content-type'));
        
        const responseText = await response.text();
        console.log('📄 Raw response (first 500 chars):', responseText.substring(0, 500));
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ JSON Parse Error:', parseError);
            console.error('📄 Full response text:', responseText);
            throw new Error('Respuesta del servidor no válida. Revisa la consola para más detalles.');
        }
        
        loadingDiv.style.display = 'none';
        
        if (data.success && data.empleados && data.empleados.length > 0) {
            empleadosElegibles = data.empleados;
            mostrarEmpleadosElegibles(data.empleados);
            console.log('✅ Empleados cargados:', data.empleados.length);
        } else {
            noEmpleadosDiv.style.display = 'block';
            empleadosElegibles = [];
            console.log('ℹ️ No hay empleados elegibles:', data.message || 'Sin mensaje');
        }
    } catch (error) {
        console.error('❌ Error cargando empleados elegibles:', error);
        loadingDiv.style.display = 'none';
        noEmpleadosDiv.style.display = 'block';
        
        // Mostrar error al usuario
        showNotification('Error al cargar empleados elegibles: ' + error.message, 'error');
    }
}

function mostrarEmpleadosElegibles(empleados) {
    const listaDiv = document.getElementById('listaEmpleadosElegibles');
    
    empleados.forEach(empleado => {
        const empleadoDiv = document.createElement('div');
        empleadoDiv.className = 'empleado-item';
        empleadoDiv.setAttribute('data-empleado-id', empleado.id);
        empleadoDiv.setAttribute('data-fecha', empleado.fecha_falta);
        
        // Calcular horas reales desde los turnos disponibles
        let horasCalculadas = 0;
        if (empleado.turnos_disponibles && empleado.turnos_disponibles.length > 0) {
            empleado.turnos_disponibles.forEach(turno => {
                horasCalculadas += calcularHorasTurno(turno.hora_entrada, turno.hora_salida);
            });
        } else {
            horasCalculadas = empleado.horas_programadas || 8;
        }
        
        empleadoDiv.setAttribute('data-horas', horasCalculadas);
        
        // Información de turnos para mostrar
        let infoTurnos = '';
        if (empleado.multiple_turnos && empleado.turnos_disponibles.length > 1) {
            infoTurnos = ` (${empleado.turnos_disponibles.length} turnos)`;
        }
        
        empleadoDiv.innerHTML = `
            <div class="empleado-info">
                <div class="empleado-codigo">${empleado.codigo} - ${empleado.nombre}</div>
                <div class="empleado-fecha">
                    <i class="fas fa-calendar"></i> Falta: ${empleado.fecha_falta} 
                    <i class="fas fa-clock ms-2"></i> ${horasCalculadas}h programadas${infoTurnos}
                </div>
                <div class="empleado-fecha">
                    <i class="fas fa-building"></i> ${empleado.establecimiento} - ${empleado.sede}
                </div>
            </div>
        `;
        
        empleadoDiv.addEventListener('click', function() {
            seleccionarEmpleadoParaJustificacion(empleado, empleadoDiv);
        });
        
        listaDiv.appendChild(empleadoDiv);
    });
}

function seleccionarEmpleadoParaJustificacion(empleado, elementoDiv) {
    // Limpiar selección anterior
    document.querySelectorAll('.empleado-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Marcar como seleccionado
    elementoDiv.classList.add('selected');
    empleadoSeleccionadoJustificacion = empleado;
    
    // Llenar el formulario
    document.getElementById('empleadoSeleccionado').value = empleado.id;
    document.getElementById('empleadoNombre').value = `${empleado.codigo} - ${empleado.nombre}`;
    document.getElementById('fechaFalta').value = empleado.fecha_falta;
    document.getElementById('fechaFaltaDisplay').value = empleado.fecha_falta;
    
    // Manejar turnos y horas
    const selectorTurnosContainer = document.getElementById('selectorTurnosContainer');
    const turnoSelect = document.getElementById('turnoSeleccionado');
    
    if (empleado.multiple_turnos && empleado.turnos_disponibles && empleado.turnos_disponibles.length > 1) {
        // Empleado con múltiples turnos - mostrar selector
        selectorTurnosContainer.style.display = 'block';
        turnoSelect.innerHTML = '<option value="">Seleccione un turno...</option>';
        
        let totalHoras = 0;
        empleado.turnos_disponibles.forEach(turno => {
            const horasCalculadas = calcularHorasTurno(turno.hora_entrada, turno.hora_salida);
            totalHoras += horasCalculadas;
            
            turnoSelect.innerHTML += `<option value="${turno.id_empleado_horario}" data-horas="${horasCalculadas}">
                ${turno.nombre_turno || 'Turno'} (${turno.hora_entrada} - ${turno.hora_salida}) - ${horasCalculadas}h
            </option>`;
        });
        
        // Establecer horas totales iniciales
        document.getElementById('horasProgramadas').value = totalHoras;
        document.getElementById('horasProgramadasDisplay').value = `${totalHoras} horas (total)`;
        
        // Evento para actualizar horas cuando se selecciona un turno específico
        turnoSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const horasSeleccionadas = selectedOption.dataset.horas;
                document.getElementById('horasProgramadas').value = horasSeleccionadas;
                document.getElementById('horasProgramadasDisplay').value = `${horasSeleccionadas} horas`;
            } else {
                document.getElementById('horasProgramadas').value = totalHoras;
                document.getElementById('horasProgramadasDisplay').value = `${totalHoras} horas (total)`;
            }
        });
        
    } else if (empleado.turnos_disponibles && empleado.turnos_disponibles.length === 1) {
        // Empleado con un solo turno
        selectorTurnosContainer.style.display = 'none';
        const turno = empleado.turnos_disponibles[0];
        const horasCalculadas = calcularHorasTurno(turno.hora_entrada, turno.hora_salida);
        
        document.getElementById('horasProgramadas').value = horasCalculadas;
        document.getElementById('horasProgramadasDisplay').value = `${horasCalculadas} horas`;
        
        // Establecer turno automáticamente
        turnoSelect.innerHTML = `<option value="${turno.id_empleado_horario}" selected>${turno.nombre_turno || 'Turno'}</option>`;
        
    } else {
        // Fallback - usar horas programadas del empleado o 8 por defecto
        selectorTurnosContainer.style.display = 'none';
        const horasDefault = empleado.horas_programadas || 8;
        document.getElementById('horasProgramadas').value = horasDefault;
        document.getElementById('horasProgramadasDisplay').value = `${horasDefault} horas`;
    }
    
    // Limpiar campos de justificación
    document.getElementById('motivoJustificacion').value = '';
    document.getElementById('detalleJustificacion').value = '';
}

// Función auxiliar para calcular horas entre dos horarios
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

// ============================================================================
// MANEJO DEL FORMULARIO DE JUSTIFICACIÓN
// ============================================================================
async function manejarSubmitJustificacion(event) {
    console.log('🚀 manejarSubmitJustificacion - Iniciando');
    event.preventDefault();
    
    if (!empleadoSeleccionadoJustificacion) {
        console.log('❌ No hay empleado seleccionado');
        showNotification('Por favor selecciona un empleado de la lista', 'warning');
        return;
    }
    
    console.log('👤 Empleado seleccionado:', empleadoSeleccionadoJustificacion);
    
    const formData = new FormData(event.target);
    const justificacionData = {
        empleado_id: formData.get('empleado_id'),
        fecha_falta: formData.get('fecha'),
        motivo: formData.get('motivo'),
        detalle_adicional: formData.get('detalle'),
        horas_programadas: parseFloat(formData.get('horas_programadas')) || 8.0
    };
    
    console.log('📋 Datos del formulario:', justificacionData);
    
    // Agregar turno_id si está seleccionado
    const turnoSeleccionado = formData.get('turno_id');
    if (turnoSeleccionado && turnoSeleccionado !== '') {
        justificacionData.turno_id = parseInt(turnoSeleccionado);
    }
    
    // Validar campos requeridos
    if (!justificacionData.motivo) {
        showNotification('El motivo es requerido', 'warning');
        return;
    }
    
    // Validar que se haya seleccionado un turno si hay múltiples turnos
    const selectorTurnos = document.getElementById('selectorTurnosContainer');
    if (selectorTurnos && selectorTurnos.style.display !== 'none') {
        const turnoSelect = document.getElementById('turnoSeleccionado');
        if (!turnoSelect.value || turnoSelect.value === '') {
            showNotification('Debe seleccionar un turno específico para justificar', 'warning');
            return;
        }
    }
    
    const btnSubmit = document.getElementById('btnCrearJustificacion');
    const originalText = btnSubmit.innerHTML;
    
    try {
        // Deshabilitar botón y mostrar loading
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        
        console.log('📡 Enviando request al API...');
        const response = await fetch('api/justificaciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(justificacionData)
        });
        
        console.log('📡 Response status:', response.status);
        const data = await response.json();
        console.log('📡 Response data:', data);
        
        if (data.success) {
            console.log('✅ Justificación creada exitosamente');
            showNotification('Justificación creada exitosamente', 'success');
            
            // Limpiar formulario
            console.log('🧹 Limpiando formulario...');
            document.getElementById('formJustificacion').reset();
            document.getElementById('empleadoNombre').value = '';
            document.getElementById('fechaFaltaDisplay').value = '';
            document.getElementById('horasProgramadasDisplay').value = '';
            
            // Ocultar selector de turnos
            const selectorTurnosContainer = document.getElementById('selectorTurnosContainer');
            if (selectorTurnosContainer) {
                selectorTurnosContainer.style.display = 'none';
            }
            
            // Limpiar selección
            document.querySelectorAll('.empleado-item').forEach(item => {
                item.classList.remove('selected');
            });
            empleadoSeleccionadoJustificacion = null;
            
            // Recargar solo la lista de empleados elegibles y justificaciones recientes
            // NO recargar asistencia para evitar reiniciar el DOM
            console.log('🔄 Recargando listas...');
            cargarEmpleadosElegibles();
            cargarJustificacionesRecientes();
            
        } else {
            console.log('❌ Error del API:', data.message);
            showNotification(data.message || 'Error al crear justificación', 'error');
        }
    } catch (error) {
        console.error('❌ Error de conexión creando justificación:', error);
        showNotification('Error de conexión al crear justificación', 'error');
    } finally {
        // Restaurar botón
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;
    }
}

// ============================================================================
// CARGA DE JUSTIFICACIONES RECIENTES
// ============================================================================
async function cargarJustificacionesRecientes() {
    try {
        console.log('🔍 Cargando justificaciones recientes...');
        
        const response = await fetch('api/justificaciones.php?action=getRecientes&limit=10');
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response headers:', response.headers.get('content-type'));
        
        const responseText = await response.text();
        console.log('📄 Raw response (first 500 chars):', responseText.substring(0, 500));
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ JSON Parse Error:', parseError);
            console.error('📄 Full response text:', responseText);
            throw new Error('Respuesta del servidor no válida para justificaciones recientes');
        }
        
        const tbody = document.getElementById('tablaJustificacionesRecientes');
        tbody.innerHTML = '';
        
        if (data.success && data.justificaciones && data.justificaciones.length > 0) {
            console.log('✅ Justificaciones cargadas:', data.justificaciones.length);
            data.justificaciones.forEach(justificacion => {
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${justificacion.empleado_codigo} - ${justificacion.empleado_nombre}</td>
                    <td>${justificacion.fecha}</td>
                    <td>${justificacion.motivo}</td>
                    <td>${justificacion.tipo_falta || 'Completa'}</td>
                    <td>${justificacion.created_at || justificacion.fecha_justificacion || 'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalleJustificacion(${justificacion.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i> Detalles
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay justificaciones recientes</td></tr>';
            console.log('ℹ️ No hay justificaciones recientes');
        }
    } catch (error) {
        console.error('❌ Error cargando justificaciones recientes:', error);
        showNotification('Error al cargar justificaciones recientes: ' + error.message, 'error');
    }
}

// ============================================================================
// FUNCIONES DE GESTIÓN DE JUSTIFICACIONES
// ============================================================================
async function verDetalleJustificacion(justificacionId) {
    try {
        const response = await fetch(`api/justificaciones.php?action=getDetalle&id=${justificacionId}`);
        const data = await response.json();
        
        if (data.success && data.justificacion) {
            const justificacion = data.justificacion;
            
            // Crear modal dinámico para mostrar detalles
            const detalleHtml = `
                <div class="modal fade" id="detalleJustificacionModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalle de Justificación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <strong>Empleado:</strong> ${justificacion.empleado_codigo} - ${justificacion.empleado_nombre}
                                    </div>
                                    <div class="col-6 mb-3">
                                        <strong>Fecha:</strong> ${justificacion.fecha}
                                    </div>
                                    <div class="col-6 mb-3">
                                        <strong>Horas Programadas:</strong> ${justificacion.horas_programadas}h
                                    </div>
                                    <div class="col-12 mb-3">
                                        <strong>Motivo:</strong> ${justificacion.motivo}
                                    </div>
                                    <div class="col-12 mb-3">
                                        <strong>Detalle:</strong><br>
                                        ${justificacion.detalle_falta || 'Sin detalles adicionales'}
                                    </div>
                                    <div class="col-6 mb-3">
                                        <strong>Estado:</strong> ${justificacion.aprobado === 1 ? 'Aprobada' : 'Pendiente'}
                                    </div>
                                    <div class="col-6 mb-3">
                                        <strong>Creado por:</strong> ${justificacion.usuario_creacion || 'Sistema'}
                                    </div>
                                    <div class="col-12">
                                        <strong>Fecha de creación:</strong> ${justificacion.fecha_creacion}
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Eliminar modal anterior si existe
            const modalAnterior = document.getElementById('detalleJustificacionModal');
            if (modalAnterior) {
                modalAnterior.remove();
            }
            
            // Agregar nuevo modal al DOM
            document.body.insertAdjacentHTML('beforeend', detalleHtml);
            
            // Mostrar modal
            const nuevoModal = new bootstrap.Modal(document.getElementById('detalleJustificacionModal'));
            nuevoModal.show();
            
            // Limpiar modal cuando se cierre
            document.getElementById('detalleJustificacionModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }
    } catch (error) {
        console.error('Error obteniendo detalle de justificación:', error);
        showNotification('Error al cargar detalle de justificación', 'error');
    }
}

async function eliminarJustificacion(justificacionId) {
    if (!confirm('¿Está seguro de que desea eliminar esta justificación?')) {
        return;
    }
    
    try {
        const response = await fetch('api/justificaciones.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_justificacion: justificacionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Justificación eliminada exitosamente', 'success');
            cargarJustificacionesRecientes();
            cargarEmpleadosElegibles(); // Puede que el empleado vuelva a ser elegible
        } else {
            showNotification(data.message || 'Error al eliminar justificación', 'error');
        }
    } catch (error) {
        console.error('Error eliminando justificación:', error);
        showNotification('Error de conexión al eliminar justificación', 'error');
    }
}

// ============================================================================
// FUNCIONES DE UTILIDAD
// ============================================================================
function showNotification(message, type = 'info') {
    // Si existe una función global de notificaciones diferente, usarla
    if (typeof window.globalNotification === 'function') {
        window.globalNotification(message, type);
        return;
    }
    
    // Crear notificación estilo toast más prominente
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const iconClass = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    }[type] || 'fas fa-info-circle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed shadow-lg`;
    notification.style.cssText = `
        top: 20px; 
        right: 20px; 
        z-index: 10000; 
        max-width: 450px; 
        min-width: 300px;
        border: none;
        border-radius: 8px;
        animation: slideInRight 0.3s ease-out;
    `;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="${iconClass} me-2" style="font-size: 1.2rem;"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    // Agregar estilos de animación si no existen
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos con animación
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

console.log('Sistema de justificaciones cargado correctamente');