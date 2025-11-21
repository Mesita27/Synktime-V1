<?php
/**
 * SynkTime - Modales del módulo de reportes
 *
 * Este componente contiene los modales necesarios para visualizar detalles
 * de asistencias y empleados desde el módulo de reportes.
 */
?>

<!-- Modal de detalles de asistencia -->
<div id="attendanceDetailsModal" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check"></i>
                    Detalles de Asistencia
                </h5>
                <button type="button" class="btn-close" onclick="closeAttendanceModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="attendanceDetailsContent">
                <!-- Contenido se carga dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal de detalles de justificación -->
<div id="justificationDetailsModal" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list"></i>
                    Detalles de Justificación
                </h5>
                <button type="button" class="btn-close" onclick="closeJustificationModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="justificationDetailsContent">
                <!-- Contenido se carga dinámicamente -->
            </div>
        </div>
    </div>
</div>
<div id="employeeDetailsModal" class="modal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user"></i>
                    Información del Empleado
                </h5>
                <button type="button" class="btn-close" onclick="closeEmployeeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="employeeDetailsContent">
                <!-- Contenido se carga dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal de ayuda -->
<div id="helpModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle"></i>
                    Ayuda - Reportes de Asistencia
                </h5>
                <button type="button" class="btn-close" onclick="closeHelpModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="help-content">
                    <h6><i class="fas fa-filter"></i> Filtros</h6>
                    <p>Utiliza los filtros para buscar asistencias específicas por empleado, sede, establecimiento, fechas y estados.</p>

                    <h6><i class="fas fa-clock"></i> Estados de Asistencia</h6>
                    <ul>
                        <li><strong>Temprano:</strong> Llegada antes de la hora programada</li>
                        <li><strong>Puntual:</strong> Llegada dentro del margen de tolerancia</li>
                        <li><strong>Tardanza:</strong> Llegada después del tiempo permitido</li>
                        <li><strong>Ausente:</strong> Sin registro de entrada</li>
                    </ul>

                    <h6><i class="fas fa-moon"></i> Turnos Nocturnos</h6>
                    <p>Los turnos nocturnos se detectan automáticamente cuando la salida ocurre al día siguiente de la entrada con el mismo horario personalizado.</p>

                    <h6><i class="fas fa-file-excel"></i> Exportación</h6>
                    <p>Exporta los datos filtrados a Excel con formato profesional y estilos avanzados.</p>
                </div>
            </div>
        </div>
    </div>
</div>