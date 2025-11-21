<?php
require_once 'auth/session.php';
requireModuleAccess('horarios'); // Verificar permisos para módulo de horarios
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horarios | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/schedule.css">
    <link rel="stylesheet" href="assets/css/pagination.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <!-- Sección de Horarios -->
            <div class="schedule-header">
                <h2 class="page-title"><i class="fas fa-clock"></i> Horarios</h2>
                <div class="schedule-actions">
                    <button type="button" class="btn-primary" onclick="openScheduleModal()">
                        <i class="fas fa-plus"></i> Registrar Horario
                    </button>
                    <button type="button" class="btn-secondary" onclick="exportSchedulesToExcel()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
            
            <!-- Filtros de horarios -->
            <div class="schedule-query-box">
                <form id="scheduleFilterForm" class="schedule-query-form">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtro_id">ID Horario</label>
                            <input type="text" id="filtro_id" name="id" class="form-control" placeholder="ID">
                        </div>
                        <div class="form-group">
                            <label for="filtro_nombre">Nombre</label>
                            <input type="text" id="filtro_nombre" name="nombre" class="form-control" placeholder="Nombre">
                        </div>
                        <div class="form-group">
                            <label for="filtro_sede">Sede</label>
                            <select id="filtro_sede" name="sede" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_establecimiento">Establecimiento</label>
                            <select id="filtro_establecimiento" name="establecimiento" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_dia">Día</label>
                            <select id="filtro_dia" name="dia" class="form-control">
                                <option value="">Todos</option>
                                <option value="1">Lunes</option>
                                <option value="2">Martes</option>
                                <option value="3">Miércoles</option>
                                <option value="4">Jueves</option>
                                <option value="5">Viernes</option>
                                <option value="6">Sábado</option>
                                <option value="7">Domingo</option>
                            </select>
                        </div>
                        <div class="form-group query-btns">
                            <button type="button" id="btnBuscarHorario" class="btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" id="btnLimpiarHorario" class="btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla de horarios con paginación -->
            <div class="schedule-table-container">
                <div id="schedulePaginationControls" class="pagination-controls">
                    <!-- Controles de paginación se insertarán aquí -->
                </div>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllSchedules"></th>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Días</th>
                            <th>Hora entrada</th>
                            <th>Hora salida</th>
                            <th>Tolerancia (min)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <!-- JS: aquí se cargan los horarios -->
                        <tr>
                            <td colspan="10" class="loading-text">
                                <i class="fas fa-spinner fa-spin"></i> Cargando horarios...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sección de Vinculación de Empleados y Horarios -->
            <div class="schedule-header" style="margin-top: 2rem;">
                <h2 class="page-title"><i class="fas fa-link"></i> Vinculación de Empleados</h2>
            </div>
            
            <!-- Filtros de empleados -->
            <div class="schedule-query-box">
                <form id="employeeFilterForm" class="schedule-query-form">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtro_codigo">Código</label>
                            <input type="text" id="filtro_codigo" name="codigo" class="form-control" placeholder="Código">
                        </div>
                        <div class="form-group">
                            <label for="filtro_identificacion">Identificación</label>
                            <input type="text" id="filtro_identificacion" name="identificacion" class="form-control" placeholder="DNI">
                        </div>
                        <div class="form-group">
                            <label for="filtro_nombre_empleado">Nombre</label>
                            <input type="text" id="filtro_nombre_empleado" name="nombre" class="form-control" placeholder="Nombre o Apellido">
                        </div>
                        <div class="form-group">
                            <label for="filtro_sede_empleado">Sede</label>
                            <select id="filtro_sede_empleado" name="sede" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_establecimiento_empleado">Establecimiento</label>
                            <select id="filtro_establecimiento_empleado" name="establecimiento" class="form-control"></select>
                        </div>
                        <div class="form-group query-btns">
                            <button type="button" id="btnBuscarEmpleado" class="btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" id="btnLimpiarEmpleado" class="btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla de empleados con paginación -->
            <div class="schedule-table-container">
                <div id="employeePaginationControls" class="pagination-controls">
                    <!-- Controles de paginación se insertarán aquí -->
                </div>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Identificación</th>
                            <th>Empleado</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Horarios asignados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <!-- JS: aquí se cargan los empleados -->
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
<!-- Estos modales estarán ocultos y se mostrarán con JavaScript -->

<!-- Modal para crear/editar horarios -->
<div class="modal" id="scheduleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-clock"></i> <span id="scheduleModalTitle">Registrar Horario</span></h3>
                <button type="button" class="modal-close" onclick="closeScheduleModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm" class="schedule-form">
                    <input type="hidden" id="schedule_id" name="id_horario">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="schedule_nombre">Nombre del Horario*</label>
                            <input type="text" id="schedule_nombre" name="nombre" class="form-control" required maxlength="50" placeholder="Ej: Horario Matutino">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="schedule_sede">Sede*</label>
                            <select id="schedule_sede" name="sede" class="form-control" required></select>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_establecimiento">Establecimiento*</label>
                            <select id="schedule_establecimiento" name="establecimiento" class="form-control" required></select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="schedule_hora_entrada">Hora de Entrada*</label>
                            <input type="time" id="schedule_hora_entrada" name="hora_entrada" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_hora_salida">Hora de Salida*</label>
                            <input type="time" id="schedule_hora_salida" name="hora_salida" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_tolerancia">Tolerancia (min)*</label>
                            <input type="number" id="schedule_tolerancia" name="tolerancia" class="form-control" min="0" max="60" required placeholder="Ej: 15">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Días de la semana*</label>
                        <div class="days-selector">
                            <label class="day-checkbox">
                                <input type="checkbox" name="dias[]" value="1"> Lunes
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="dias[]" value="2"> Martes
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="dias[]" value="3"> Miércoles
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="dias[]" value="4"> Jueves
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="dias[]" value="5"> Viernes
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="dias[]" value="6"> Sábado
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="dias[]" value="7"> Domingo
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeScheduleModal()">Cancelar</button>
                <button type="button" class="btn-primary" id="btnSaveSchedule">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para vinculación de empleados con horarios -->
<div class="modal" id="employeeScheduleModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-link"></i> 
                    <span>Gestión de Horarios</span>
                </h3>
                <button type="button" class="modal-close" onclick="closeEmployeeScheduleModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
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
                
                <div class="schedule-management-container">
                    <div class="schedule-panel">
                        <h4 class="panel-title"><i class="fas fa-clock"></i> Horarios Asignados</h4>
                        <div class="schedule-list-container">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAllAssignedSchedules"></th>
                                        <th>Nombre</th>
                                        <th>Días</th>
                                        <th>Horario</th>
                                        <th>Desde</th>
                                        <th>Hasta</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="assignedSchedulesTable">
                                    <!-- Horarios asignados se cargan aquí -->
                                </tbody>
                            </table>
                        </div>
                        <div class="panel-actions">
                            <button type="button" class="btn-danger btn-sm" id="btnRemoveSchedules" disabled>
                                <i class="fas fa-unlink"></i> Desvincular Seleccionados
                            </button>
                        </div>
                    </div>
                    
                    <div class="schedule-panel">
                        <h4 class="panel-title"><i class="fas fa-plus-circle"></i> Horarios Disponibles</h4>
                        <div class="schedule-list-container">
                            <div class="filter-bar">
                                <input type="text" id="filterAvailableSchedules" class="form-control" placeholder="Filtrar horarios...">
                                <small id="filterResultsInfo" class="filter-results"></small>
                            </div>
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAllAvailableSchedules"></th>
                                        <th>Nombre</th>
                                        <th>Días</th>
                                        <th>Horario</th>
                                    </tr>
                                </thead>
                                <tbody id="availableSchedulesTable">
                                    <!-- Horarios disponibles se cargan aquí -->
                                </tbody>
                            </table>
                        </div>
                        <div class="assignment-dates">
                            <div class="date-group">
                                <label for="fechaDesde">Válido desde:</label>
                                <input type="date" id="fechaDesde" class="form-control" required>
                            </div>
                            <div class="date-group">
                                <label for="fechaHasta">Válido hasta (opcional):</label>
                                <input type="date" id="fechaHasta" class="form-control">
                            </div>
                        </div>
                        <div class="panel-actions">
                            <button type="button" class="btn-primary btn-sm" id="btnAssignSchedules" disabled>
                                <i class="fas fa-link"></i> Vincular Seleccionados
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeEmployeeScheduleModal()">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de un horario -->
<div class="modal" id="scheduleDetailsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-info-circle"></i> Detalles del Horario</h3>
                <button type="button" class="modal-close" onclick="closeScheduleDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="schedule-detail-info">
                    <h4 id="scheduleDetailName">Nombre del Horario</h4>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Sede:</span>
                            <span id="scheduleDetailSede" class="detail-value"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Establecimiento:</span>
                            <span id="scheduleDetailEstablecimiento" class="detail-value"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Días:</span>
                            <div id="scheduleDetailDays" class="detail-value days-badges"></div>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Hora Entrada:</span>
                            <span id="scheduleDetailEntrada" class="detail-value"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Hora Salida:</span>
                            <span id="scheduleDetailSalida" class="detail-value"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Tolerancia:</span>
                            <span id="scheduleDetailTolerancia" class="detail-value"></span>
                        </div>
                    </div>
                </div>
                
                <div class="schedule-employees-list">
                    <h4>Empleados Asignados</h4>
                    <div class="table-container">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleEmployeesTable">
                                <!-- Empleados asignados se cargan aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeScheduleDetailsModal()">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Sistema de notificaciones -->
<div id="notificationsContainer" class="notifications-container"></div>

<!-- Scripts -->
<script src="assets/js/layout.js"></script>
<script src="assets/js/schedule.js"></script>
</body>
</html>