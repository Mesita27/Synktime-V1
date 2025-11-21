<?php
require_once 'auth/session.php';
requireModuleAccess('horarios'); // Reutilizar permisos del módulo de horarios existente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horarios Personalizados | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/schedule.css">
    <link rel="stylesheet" href="assets/css/pagination.css">
    <link rel="stylesheet" href="assets/css/horarios-personalizados.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <!-- Header del módulo -->
            <div class="schedule-header">
                <h2 class="page-title">
                    <i class="fas fa-user-clock"></i> Horarios Personalizados
                </h2>
                <div class="schedule-actions">
                    <button type="button" class="btn-secondary" onclick="showHelpModal()">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </button>
                    <button type="button" class="btn-secondary" onclick="openExportModal()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                    <button type="button" class="btn-primary" onclick="openTemporalScheduleModal()">
                        <i class="fas fa-clock"></i> Horario Temporal
                    </button>
                </div>
            </div>

            <!-- Contenedor global de notificaciones -->
            <div id="globalNotificationsContainer" class="global-notifications-container"></div>

            <!-- Estadísticas rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="totalEmployees">-</div>
                        <div class="stat-label">Total Empleados</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="employeesWithSchedules">-</div>
                        <div class="stat-label">Con Horarios Personalizados</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="totalSchedules">-</div>
                        <div class="stat-label">Total Turnos Configurados</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="activeSchedules">-</div>
                        <div class="stat-label">Horarios Activos</div>
                    </div>
                </div>
            </div>

            <!-- Filtros de búsqueda -->
            <div class="schedule-query-box">
                <form id="employeeFilterForm" class="schedule-query-form">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtro_nombre">Empleado</label>
                            <input type="text" id="filtro_nombre" name="nombre" class="form-control" 
                                   placeholder="Buscar por nombre, apellido o Identificación">
                        </div>
                        <div class="form-group">
                            <label for="filtro_sede">Sede</label>
                            <select id="filtro_sede" name="sede" class="form-control">
                                <option value="">Todas las sedes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_establecimiento">Establecimiento</label>
                            <select id="filtro_establecimiento" name="establecimiento" class="form-control">
                                <option value="">Todos los establecimientos</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_estado_horario">Estado</label>
                            <select id="filtro_estado_horario" name="estado_horario" class="form-control">
                                <option value="">Todos</option>
                                <option value="con_horarios">Con horarios personalizados</option>
                                <option value="sin_horarios">Sin horarios personalizados</option>
                                <option value="horarios_vencidos">Con horarios vencidos</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_fecha_vigencia_desde">Fecha Vigencia Desde</label>
                            <input type="date" id="filtro_fecha_vigencia_desde" name="fecha_vigencia_desde" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="filtro_fecha_vigencia_hasta">Fecha Vigencia Hasta</label>
                            <input type="date" id="filtro_fecha_vigencia_hasta" name="fecha_vigencia_hasta" class="form-control">
                        </div>
                        <div class="form-group query-btns">
                            <button type="button" id="btnBuscarEmpleado" class="btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" id="btnLimpiarFiltros" class="btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Lista de empleados -->
            <div class="schedule-table-container">
                <div id="employeePaginationControls" class="pagination-controls">
                    <!-- Controles de paginación se insertarán aquí -->
                </div>
                
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Identificación</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Estado Horarios</th>
                            <th>Última Modificación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <tr>
                            <td colspan="7" class="loading-text">
                                <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- MODALES -->

<!-- Modal de configuración de horarios por empleado -->
<div class="modal modal-xl" id="employeeScheduleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" style="color: white;">
                    <i class="fas fa-user-clock"></i> 
                    <span id="employeeScheduleTitle">Configurar Horarios</span>
                </h3>
                <button type="button" class="modal-close" onclick="closeEmployeeScheduleModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <!-- Información del empleado -->
                <div class="employee-info-panel">
                    <div class="employee-photo">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="employee-details">
                        <h4 id="employeeName">Nombre del Empleado</h4>
                        <div id="employeeInfo" class="employee-metadata">
                            <!-- Detalles del empleado aquí -->
                        </div>
                        <input type="hidden" id="currentEmployeeId">
                    </div>
                </div>

                <!-- Configuración por días de la semana -->
                <div class="weekly-schedule-container">
                    <div class="day-tabs">
                        <button type="button" class="day-tab active" data-day="1" onclick="switchDay(1)">
                            <i class="fas fa-calendar-day"></i> Lunes
                        </button>
                        <button type="button" class="day-tab" data-day="2" onclick="switchDay(2)">
                            <i class="fas fa-calendar-day"></i> Martes
                        </button>
                        <button type="button" class="day-tab" data-day="3" onclick="switchDay(3)">
                            <i class="fas fa-calendar-day"></i> Miércoles
                        </button>
                        <button type="button" class="day-tab" data-day="4" onclick="switchDay(4)">
                            <i class="fas fa-calendar-day"></i> Jueves
                        </button>
                        <button type="button" class="day-tab" data-day="5" onclick="switchDay(5)">
                            <i class="fas fa-calendar-day"></i> Viernes
                        </button>
                        <button type="button" class="day-tab" data-day="6" onclick="switchDay(6)">
                            <i class="fas fa-calendar-day"></i> Sábado
                        </button>
                        <button type="button" class="day-tab" data-day="7" onclick="switchDay(7)">
                            <i class="fas fa-calendar-day"></i> Domingo
                        </button>
                    </div>

                    <div class="day-schedule-content">
                        <!-- Contenido del día activo -->
                        <div id="dayScheduleContainer">
                            <div class="day-header">
                                <h4 id="currentDayTitle">Lunes</h4>
                                <button type="button" class="btn-primary btn-sm" onclick="addShift()">
                                    <i class="fas fa-plus"></i> Agregar Turno
                                </button>
                            </div>
                            
                            <div id="shiftsContainer" class="shifts-container">
                                <!-- Los turnos se cargarán dinámicamente aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeEmployeeScheduleModal()">
                    Cancelar
                </button>
                <button type="button" class="btn-success" id="btnSaveSchedules">
                    <i class="fas fa-save"></i> Guardar Horarios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de ayuda -->
<div class="modal" id="helpModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" style="color: white;">
                    <i class="fas fa-question-circle"></i> Ayuda - Horarios Personalizados
                </h3>
                <button type="button" class="modal-close" onclick="closeHelpModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tabs de ayuda -->
                <div class="help-tabs">
                    <button type="button" class="help-tab active" onclick="switchHelpTab('general')">
                        <i class="fas fa-clock"></i> Horarios Generales
                    </button>
                    <button type="button" class="help-tab" onclick="switchHelpTab('temporal')">
                        <i class="fas fa-calendar-alt"></i> Horarios Temporales
                    </button>
                    <button type="button" class="help-tab" onclick="switchHelpTab('features')">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </button>
                </div>

                <!-- Contenido de tabs -->
                <div id="help-general" class="help-content active">
                    <h4>¿Qué son los horarios personalizados?</h4>
                    <p>Los horarios personalizados permiten configurar horarios únicos para cada empleado,
                    incluyendo múltiples turnos en el mismo día y horarios diferentes para cada día de la semana.</p>

                    <h4>Características principales:</h4>
                    <ul>
                        <li><strong>Múltiples turnos por día:</strong> Un empleado puede tener varios turnos en el mismo día</li>
                        <li><strong>Horarios únicos por día:</strong> Cada día de la semana puede tener horarios diferentes</li>
                        <li><strong>Vigencia configurable:</strong> Define desde cuándo y hasta cuándo aplican los horarios</li>
                        <li><strong>Tolerancia personalizada:</strong> Configura tolerancia específica por turno</li>
                        <li><strong>Turnos nocturnos:</strong> Soporte especial para turnos que cruzan la medianoche</li>
                    </ul>

                    <h4>Ejemplos de uso:</h4>
                    <ul>
                        <li><strong>Horario partido:</strong> 8:00-12:00 y 14:00-18:00 el mismo día</li>
                        <li><strong>Horarios rotativos:</strong> Diferentes horarios cada día de la semana</li>
                        <li><strong>Turnos especiales:</strong> Guardias nocturnas, fines de semana, etc.</li>
                    </ul>

                    <h4>¿Cómo configurar?</h4>
                    <ol>
                        <li>Busca el empleado en la lista usando filtros por sede, establecimiento o nombre</li>
                        <li>Haz clic en "Configurar Horarios" (icono de reloj)</li>
                        <li>Define la vigencia (fechas desde/hasta)</li>
                        <li>Selecciona cada día de la semana usando las pestañas</li>
                        <li>Agrega los turnos necesarios para cada día</li>
                        <li>Configura tolerancia y observaciones si es necesario</li>
                        <li>Guarda los cambios</li>
                    </ol>
                </div>

                <div id="help-temporal" class="help-content">
                    <h4>¿Qué son los horarios temporales?</h4>
                    <p>Los horarios temporales permiten asignar horarios excepcionales a empleados por un período limitado,
                    sin afectar sus horarios regulares. Son ideales para situaciones especiales como:</p>

                    <h4>Casos de uso:</h4>
                    <ul>
                        <li><strong>Cambios temporales:</strong> Un empleado necesita horario diferente por un día</li>
                        <li><strong>Eventos especiales:</strong> Ferias, capacitaciones, proyectos puntuales</li>
                        <li><strong>Reemplazos:</strong> Cubrir ausencias de otros empleados</li>
                    </ul>

                    <h4>¿Cómo crear un horario temporal?</h4>
                    <ol>
                        <li>Haz clic en "Horario Temporal" (botón azul)</li>
                        <li>Selecciona los empleados que necesitan el horario temporal</li>
                        <li>Define las fechas de vigencia (desde/hasta)</li>
                        <li>Configura los horarios para cada día seleccionado</li>
                        <li>Agrega observaciones explicativas</li>
                        <li>Aplica el horario temporal</li>
                    </ol>

                    <h4>Importante:</h4>
                    <ul>
                        <li>Las horas trabajadas en este lapso de tiempo son Horas Extras</li>
                        <li>Se aplican automáticamente según las fechas configuradas</li>
                        <li>No modifican los horarios permanentes del empleado</li>
                        <li>Pueden ser editados o eliminados en cualquier momento</li>
                    </ul>
                </div>

                <div id="help-features" class="help-content">
                    <h4>📊 Exportar Horarios a Excel</h4>

                    <h5>¿Qué incluye la exportación?</h5>
                    <p>La función de exportación genera un archivo Excel completo con toda la información de horarios personalizados:</p>
                    <ul>
                        <li><strong>Datos del empleado:</strong> Nombre, identificación, sede y establecimiento</li>
                        <li><strong>Horarios por día:</strong> Todos los turnos configurados para cada día de la semana</li>
                        <li><strong>Detalles de turnos:</strong> Hora entrada, hora salida, tolerancia y observaciones</li>
                        <li><strong>Estado de horarios:</strong> Activos/inactivos y fechas de vigencia</li>
                        <li><strong>Información adicional:</strong> Tipo de turno, orden y configuración especial</li>
                    </ul>

                    <h5>¿Cómo exportar?</h5>
                    <ol>
                        <li>Haz clic en el botón "Exportar" (icono de Excel verde)</li>
                        <li>El sistema procesará automáticamente todos los horarios</li>
                        <li>Se descargará un archivo Excel con nombre descriptivo</li>
                        <li>El archivo incluirá fecha y hora de generación</li>
                    </ol>

                    <h5>Usos del archivo exportado</h5>
                    <ul>
                        <li><strong>Reportes gerenciales:</strong> Análisis de distribución de horarios</li>
                        <li><strong>Auditorías:</strong> Verificación de cumplimiento de horarios</li>
                        <li><strong>Planificación:</strong> Base para programar recursos humanos</li>
                        <li><strong>Backup:</strong> Copia de seguridad de configuraciones</li>
                        <li><strong>Integración:</strong> Datos para otros sistemas (RRHH, nómina)</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary" onclick="closeHelpModal()">Entendido</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de configuración masiva -->
<div class="modal modal-lg" id="bulkScheduleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-users-cog"></i> Configuración Masiva de Horarios
                </h3>
                <button type="button" class="modal-close" onclick="closeBulkScheduleModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Aplicar la misma configuración de horarios a múltiples empleados.</p>
                
                <!-- Selección de empleados -->
                <div class="form-group">
                    <label>Empleados seleccionados</label>
                    <div id="selectedEmployeesList" class="selected-employees-list">
                        <p class="text-muted">Selecciona empleados de la lista principal usando los checkboxes</p>
                    </div>
                </div>
                
                <!-- Plantilla de horario -->
                <div class="form-group">
                    <label for="scheduleTemplate">Plantilla de horario</label>
                    <select id="scheduleTemplate" class="form-control">
                        <option value="">Seleccionar plantilla...</option>
                        <option value="standard">Estándar (L-V 8:00-17:00)</option>
                        <option value="halfday">Medio día (L-V 8:00-13:00)</option>
                        <option value="afternoon">Tarde (L-V 13:00-22:00)</option>
                        <option value="custom">Personalizada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeBulkScheduleModal()">Cancelar</button>
                <button type="button" class="btn-primary" id="btnApplyBulkSchedule">
                    <i class="fas fa-check"></i> Aplicar a Empleados Seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Horarios -->
<div id="viewEmployeeScheduleModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-alt"></i> Ver Horarios de Empleado</h3>
            <button class="modal-close" onclick="horariosPersonalizados.hideModal('viewEmployeeScheduleModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Información del empleado -->
            <div class="employee-info-section">
                <div class="employee-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="employee-details">
                    <h4 id="viewEmployeeName">Nombre del Empleado</h4>
                    <div id="viewEmployeeInfo" class="employee-metadata">
                        <!-- Detalles del empleado aquí -->
                    </div>
                </div>
            </div>

            <!-- Resumen de horarios -->
            <div class="schedule-summary">
                <div class="summary-card">
                    <div class="summary-item">
                        <i class="fas fa-clock text-primary"></i>
                        <span class="summary-label">Total Horarios:</span>
                        <span id="viewTotalSchedules" class="summary-value">0</span>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span class="summary-label">Activos:</span>
                        <span id="viewActiveSchedules" class="summary-value">0</span>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-calendar text-info"></i>
                        <span class="summary-label">Días Configurados:</span>
                        <span id="viewConfiguredDays" class="summary-value">0</span>
                    </div>
                </div>
            </div>

            <!-- Lista de horarios -->
            <div class="schedule-view-container">
                <div id="viewSchedulesLoading" class="loading-container" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Cargando horarios...
                </div>
                <div id="viewSchedulesContent">
                    <!-- Contenido de horarios se carga aquí -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="horariosPersonalizados.hideModal('viewEmployeeScheduleModal')">
                <i class="fas fa-times"></i> Cerrar
            </button>
            <button type="button" class="btn-primary" onclick="horariosPersonalizados.openEmployeeScheduleModal(horariosPersonalizados.viewingEmployeeId)">
                <i class="fas fa-edit"></i> Editar Horarios
            </button>
        </div>
    </div>
</div>

<!-- Modal de configuración de exportación -->
<div class="modal-empleados-export" id="exportModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-file-excel"></i> Configurar Exportación Excel
            </h3>
            <button type="button" class="modal-close" onclick="closeExportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p class="text-muted">Configura los filtros específicos para la exportación de horarios personalizados.</p>

            <!-- Filtros de búsqueda -->
            <div class="schedule-query-box">
                <form id="exportFilterForm" class="schedule-query-form">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="export_fecha_vigencia_desde">Fecha Vigencia Desde <span class="required">*</span></label>
                            <input type="date" id="export_fecha_vigencia_desde" name="fecha_vigencia_desde" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="export_fecha_vigencia_hasta">Fecha Vigencia Hasta <span class="required">*</span></label>
                            <input type="date" id="export_fecha_vigencia_hasta" name="fecha_vigencia_hasta" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="export_sede">Sede</label>
                            <select id="export_sede" name="sede" class="form-control">
                                <option value="">Todas las sedes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="export_establecimiento">Establecimiento</label>
                            <select id="export_establecimiento" name="establecimiento" class="form-control">
                                <option value="">Todos los establecimientos</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filtro de empleados -->
            <div class="form-group">
                <label>Empleados Específicos</label>
                <button type="button" id="btnSelectEmpleadosExport" class="btn-secondary empleados-selector">
                    <i class="fas fa-users"></i>
                    <span class="empleados-text">Seleccionar empleados...</span>
                    <span class="empleados-count" style="display: none;">0</span>
                </button>
                <small class="form-text text-muted">Selecciona empleados específicos para incluir en la exportación</small>
            </div>

            <!-- Opciones adicionales -->
            <div class="form-group">
                <label>Opciones de Exportación</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="export_incluir_inactivos" checked>
                        <span class="checkmark"></span>
                        Incluir empleados inactivos
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeExportModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn-success" onclick="confirmExport()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
        </div>
    </div>
</div>

<!-- Modal de selección de empleados para exportación -->
<div id="modalSelectEmpleadosExport" class="modal-empleados-export">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> Seleccionar Empleados para Exportación</h3>
            <button class="modal-close" id="closeSelectEmpleadosExport">&times;</button>
        </div>
        <div class="modal-body">
            <div class="empleados-search">
                <div class="search-group">
                    <input type="text" id="searchEmpleadosExport" placeholder="Buscar empleados..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>

            <div class="empleados-actions">
                <button type="button" class="btn-text" id="selectAllEmpleadosExport">
                    <i class="fas fa-check-double"></i> Seleccionar todos
                </button>
                <button type="button" class="btn-text" id="deselectAllEmpleadosExport">
                    <i class="fas fa-times"></i> Deseleccionar todos
                </button>
            </div>

            <div class="empleados-list-container">
                <div id="empleadosExportLoading" class="empleados-loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </div>
                <div id="empleadosExportListContent" class="empleados-list" style="display: none;">
                    <!-- Employee checkboxes will be populated here -->
                </div>
                <div id="empleadosExportNoResults" class="empleados-no-results" style="display: none;">
                    <i class="fas fa-info-circle"></i> No se encontraron empleados
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div class="selected-count">
                <span id="selectedCountExport"></span>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" id="cancelSelectEmpleadosExport">Cancelar</button>
                <button type="button" class="btn-primary" id="confirmSelectEmpleadosExport">Aplicar Selección</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de horario temporal -->
<div class="modal-temporal-schedule" id="temporalScheduleModal">
    <!-- Contenedor de notificaciones para el modal -->
    <div id="modalNotificationsContainer" class="modal-notifications-container"></div>
    <div class="modal-content-temporal">
        <div class="modal-header">
            <h3><i class="fas fa-clock"></i> Configurar Horario Temporal</h3>
            <button class="modal-close" id="closeTemporalScheduleModal">&times;</button>
        </div>
        <div class="modal-body-temporal">
            <!-- Paso 1: Selección de empleados (siempre visible) -->
            <div class="temporal-step-1" id="temporalStep1">
                <div class="empleados-section">
                    <h4><i class="fas fa-users"></i> Seleccionar Empleados</h4>
                    <div class="empleados-search">
                        <div class="search-group">
                            <input type="text" id="searchEmpleadosTemporal" placeholder="Buscar empleados..." class="search-input">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    <div class="empleados-actions">
                        <button type="button" class="btn-text" id="selectAllEmpleadosTemporal">
                            <i class="fas fa-check-double"></i> Seleccionar todos
                        </button>
                        <button type="button" class="btn-text" id="deselectAllEmpleadosTemporal">
                            <i class="fas fa-times"></i> Deseleccionar todos
                        </button>
                    </div>

                    <div class="empleados-list-container">
                        <div id="empleadosTemporalLoading" class="empleados-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                        </div>
                        <div id="empleadosTemporalListContent" class="empleados-list" style="display: none;">
                            <!-- Employee checkboxes will be populated here -->
                        </div>
                        <div id="empleadosTemporalNoResults" class="empleados-no-results" style="display: none;">
                            <i class="fas fa-info-circle"></i> No se encontraron empleados
                        </div>
                    </div>
                </div>

                <!-- Configuración del horario temporal (aparece cuando hay empleados seleccionados) -->
                <div class="schedule-config-section" id="temporalScheduleConfig" style="display: none;">
                    <h4><i class="fas fa-calendar-alt"></i> Configurar Horario</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="temporal_fecha">Fecha del Horario Temporal <span class="required">*</span></label>
                            <input type="date" id="temporal_fecha" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="temporal_hora_entrada">Hora de Entrada <span class="required">*</span></label>
                            <input type="time" id="temporal_hora_entrada" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="temporal_hora_salida">Hora de Salida <span class="required">*</span></label>
                            <input type="time" id="temporal_hora_salida" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="temporal_tolerancia">Tolerancia (minutos)</label>
                            <input type="number" id="temporal_tolerancia" class="form-control" value="15" min="0" max="120">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="temporal_observaciones">Observaciones</label>
                        <textarea id="temporal_observaciones" class="form-control" rows="3" placeholder="Motivo del horario temporal..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div class="selected-count">
                <span id="selectedCountTemporal">0 empleado(s) seleccionado(s)</span>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" id="cancelTemporalSchedule">Cancelar</button>
                <button type="button" class="btn-success" id="confirmTemporalSchedule" disabled>
                    <i class="fas fa-save"></i> Crear Horario Temporal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sistema de notificaciones -->
<div id="notificationsContainer" class="notifications-container"></div>

<!-- Scripts -->
<script src="assets/js/layout.js"></script>
<script src="assets/js/horarios-personalizados.js"></script>
</body>
</html>