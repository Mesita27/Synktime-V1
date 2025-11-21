/**
 * Mejoras para mostrar información de horarios personalizados en la tabla de asistencias
 */

// Función para obtener información detallada del horario personalizado
async function getScheduleInfo(employeeId, date) {
    try {
        const response = await fetch(`api/check-employee-schedule.php?employee_id=${employeeId}&date=${date}`);
        const data = await response.json();
        
        if (data.success && data.horarios) {
            return data.horarios;
        }
        return null;
    } catch (error) {
        console.error('Error al obtener información del horario:', error);
        return null;
    }
}

// Función para formatear la información del horario personalizado
function formatScheduleInfo(scheduleData, tipo = 'ENTRADA') {
    if (!scheduleData || scheduleData.length === 0) {
        return '<span class="no-schedule">Sin horario</span>';
    }
    
    let html = '<div class="schedule-info-container">';
    
    scheduleData.forEach((horario, index) => {
        const isActive = horario.es_horario_actual;
        const priorityClass = isActive ? 'priority-high' : 'priority-normal';
        
        html += `
            <div class="schedule-item ${priorityClass}">
                <div class="schedule-name">
                    <strong>${horario.nombre_turno || 'Turno ' + (index + 1)}</strong>
                    ${isActive ? '<span class="badge-active">ACTIVO</span>' : ''}
                </div>
                <div class="schedule-times">
                    <span class="time-entry">${horario.hora_entrada}</span>
                    <span class="separator">-</span>
                    <span class="time-exit">${horario.hora_salida}</span>
                </div>
                <div class="schedule-meta">
                    <small class="schedule-day">${horario.dia_nombre}</small>
                    ${horario.activo ? 
                        '<span class="status-enabled">✓</span>' : 
                        '<span class="status-disabled">✗</span>'
                    }
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

// Función para obtener el tipo de horario (tradicional vs personalizado)
function getScheduleType(attendance) {
    if (attendance.ID_EMPLEADO_HORARIO) {
        return 'personalizado';
    } else if (attendance.ID_HORARIO) {
        return 'tradicional';
    }
    return 'ninguno';
}

// Función para crear el HTML de información del horario
function createScheduleInfoHTML(attendance, scheduleData) {
    const scheduleType = getScheduleType(attendance);
    
    if (scheduleType === 'personalizado' && scheduleData) {
        return formatScheduleInfo(scheduleData, attendance.TIPO);
    } else if (scheduleType === 'tradicional') {
        return `
            <div class="schedule-traditional">
                <div class="schedule-name">
                    <strong>${attendance.HORARIO_NOMBRE || 'Horario Tradicional'}</strong>
                </div>
                <div class="schedule-times">
                    <span class="time-entry">${attendance.HORA_ENTRADA_PROGRAMADA || '--:--'}</span>
                    <span class="separator">-</span>
                    <span class="time-exit">${attendance.HORA_SALIDA_PROGRAMADA || '--:--'}</span>
                </div>
                <div class="schedule-meta">
                    <small class="schedule-type">Horario fijo</small>
                </div>
            </div>
        `;
    } else {
        return '<span class="no-schedule">Sin horario asignado</span>';
    }
}

// CSS para los estilos de información del horario
const scheduleInfoCSS = `
.schedule-info-container {
    max-width: 200px;
}

.schedule-item {
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    border-radius: 4px;
    border-left: 3px solid #ddd;
}

.schedule-item.priority-high {
    border-left-color: #28a745;
    background-color: #f8fff9;
}

.schedule-item.priority-normal {
    border-left-color: #ffc107;
    background-color: #fffef8;
}

.schedule-name {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.badge-active {
    font-size: 0.7rem;
    background-color: #28a745;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 0.5rem;
}

.schedule-times {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.25rem;
}

.time-entry {
    color: #28a745;
    font-weight: bold;
}

.time-exit {
    color: #dc3545;
    font-weight: bold;
}

.separator {
    color: #666;
}

.schedule-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8rem;
}

.schedule-day {
    color: #666;
}

.status-enabled {
    color: #28a745;
}

.status-disabled {
    color: #dc3545;
}

.schedule-traditional {
    padding: 0.5rem;
    border-radius: 4px;
    border-left: 3px solid #17a2b8;
    background-color: #f8fdff;
}

.no-schedule {
    color: #6c757d;
    font-style: italic;
}

.schedule-type {
    color: #17a2b8;
    font-weight: 500;
}
`;

// Inyectar CSS al DOM
function injectScheduleInfoCSS() {
    const style = document.createElement('style');
    style.textContent = scheduleInfoCSS;
    document.head.appendChild(style);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    injectScheduleInfoCSS();
});

// Exportar funciones para usar en attendance.js
window.ScheduleInfoHelper = {
    getScheduleInfo,
    formatScheduleInfo,
    createScheduleInfoHTML,
    getScheduleType
};