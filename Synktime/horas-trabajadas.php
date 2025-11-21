<?php
require_once 'auth/session.php';
requireModuleAccess('horas-trabajadas'); // Verificar permisos para módulo de horas trabajadas
require_once 'config/database.php';

// Inicializar sesión si es necesario
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$usuarioInfo = null;
$empresaId = 1;

if (isset($_SESSION['username'])) {
    // Reutilizar función existente del dashboard
    require_once 'dashboard-controller.php';
    $usuarioInfo = getUsuarioInfo($_SESSION['username']);
    if ($usuarioInfo) {
        $empresaId = $usuarioInfo['ID_EMPRESA'];
        $_SESSION['id_empresa'] = $empresaId;
    }
} else {
    $empresaId = isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : 1;
}

// Obtener datos iniciales usando funciones existentes
$empresaInfo = getEmpresaInfo($empresaId);
$sedes = getSedesByEmpresa($empresaId);
$sedeDefault = count($sedes) > 0 ? $sedes[0] : null;
$sedeDefaultId = $sedeDefault ? $sedeDefault['ID_SEDE'] : null;
$establecimientos = $sedeDefaultId ? getEstablecimientosByEmpresa($empresaId, $sedeDefaultId) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horas Trabajadas | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/horas-trabajadas.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="horas-trabajadas-header">
                <h2 class="page-title"><i class="fas fa-clock"></i> Gestión de Horas Trabajadas</h2>
                <div class="horas-trabajadas-actions">
                    <button class="btn-secondary" id="btnAyudaHorasTrabajadas" title="Ayuda - Gestión de Horas Trabajadas">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </button>
                    <button class="btn-secondary" id="btnAprobacionHorasExtras" style="display: none;">
                        <i class="fas fa-check-circle"></i> Aprobación Horas Extras
                    </button>
                    <button class="btn-secondary" id="btnRegistrarDiaCivico">
                        <i class="fas fa-calendar-plus"></i> Registrar Día Cívico
                    </button>
                </div>
            </div>
            
            <!-- Filtros rápidos -->
            <div class="quick-filters">
                <button id="btnHoy" class="btn-filter active">
                    <i class="fas fa-calendar-day"></i> Hoy
                </button>
                <button id="btnAyer" class="btn-filter">
                    <i class="fas fa-calendar-minus"></i> Ayer
                </button>
                <button id="btnSemanaActual" class="btn-filter">
                    <i class="fas fa-calendar-week"></i> Semana actual
                </button>
                <button id="btnSemanaPasada" class="btn-filter">
                    <i class="fas fa-calendar-week"></i> Semana pasada
                </button>
                <button id="btnMesActual" class="btn-filter">
                    <i class="fas fa-calendar-alt"></i> Mes actual
                </button>
                <button id="btnMesPasado" class="btn-filter">
                    <i class="fas fa-calendar-alt"></i> Mes pasado
                </button>
            </div>
            
            <!-- Filtros detallados -->
            <div class="filters-section">
                <div class="filters-form">
                    <div class="filter-group">
                        <label for="selectSede">Sede:</label>
                        <select id="selectSede" class="filter-select">
                            <option value="">Todas las sedes</option>
                            <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo $sede['ID_SEDE']; ?>">
                                    <?php echo htmlspecialchars($sede['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="selectEstablecimiento">Establecimiento:</label>
                        <select id="selectEstablecimiento" class="filter-select">
                            <option value="">Todos los establecimientos</option>
                            <?php foreach ($establecimientos as $establecimiento): ?>
                                <option value="<?php echo $establecimiento['ID_ESTABLECIMIENTO']; ?>">
                                    <?php echo htmlspecialchars($establecimiento['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="btnSelectEmpleados">Empleados:</label>
                        <button type="button" id="btnSelectEmpleados" class="filter-select empleados-btn">
                            <span class="empleados-text">Todos los empleados</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="filter-group">
                        <label for="fechaDesde">Fecha desde:</label>
                        <input type="date" id="fechaDesde" class="filter-select" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="fechaHasta">Fecha hasta:</label>
                        <input type="date" id="fechaHasta" class="filter-select" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="filter-group">
                        <button class="btn-primary" id="btnFiltrar">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button class="btn-secondary" id="btnLimpiarFiltros">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <!-- Horas Regulares -->
                <div class="stat-card">
                    <div class="stat-icon regular"><i class="fas fa-business-time"></i></div>
                    <div class="stat-info">
                        <h3>Horas Regulares</h3>
                        <div class="stat-value" id="horasRegular">0h 0m</div>
                        <small class="stat-description">Dentro del horario asignado</small>
                    </div>
                </div>
                <!-- Recargos -->
                <div class="stat-card">
                    <div class="stat-icon nocturno"><i class="fas fa-moon"></i></div>
                    <div class="stat-info">
                        <h3>Recargo Nocturno</h3>
                        <div class="stat-value" id="recargoNocturno">0h 0m</div>
                        <small class="stat-description">9PM - 6AM</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon dominical"><i class="fas fa-church"></i></div>
                    <div class="stat-info">
                        <h3>Recargo Dominical/Festivo</h3>
                        <div class="stat-value" id="recargoDominical">0h 0m</div>
                        <small class="stat-description">Domingos y festivos</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon nocturno-dominical"><i class="fas fa-star-and-crescent"></i></div>
                    <div class="stat-info">
                        <h3>Recargo Nocturno Dom/Fest</h3>
                        <div class="stat-value" id="recargoNocturnoDominical">0h 0m</div>
                        <small class="stat-description">9PM-6AM dom/fest</small>
                    </div>
                </div>
                <!-- Extras -->
                <div class="stat-card">
                    <div class="stat-icon extra-diurna"><i class="fas fa-sun"></i></div>
                    <div class="stat-info">
                        <h3>Extra Diurna</h3>
                        <div class="stat-value" id="extraDiurna">0h 0m</div>
                        <small class="stat-description">6AM - 9PM (fuera horario)</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon extra-nocturna"><i class="fas fa-moon"></i></div>
                    <div class="stat-info">
                        <h3>Extra Nocturna</h3>
                        <div class="stat-value" id="extraNocturna">0h 0m</div>
                        <small class="stat-description">9PM - 6AM (fuera horario)</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon extra-diurna-dominical"><i class="fas fa-calendar-plus"></i></div>
                    <div class="stat-info">
                        <h3>Extra Diurna Dom/Fest</h3>
                        <div class="stat-value" id="extraDiurnaDominical">0h 0m</div>
                        <small class="stat-description">6AM-9PM dom/fest (fuera)</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon extra-nocturna-dominical"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <h3>Extra Nocturna Dom/Fest</h3>
                        <div class="stat-value" id="extraNocturnaDominical">0h 0m</div>
                        <small class="stat-description">9PM-6AM dom/fest (fuera)</small>
                    </div>
                </div>
                <!-- Horas Extras Pendientes -->
                <div class="stat-card">
                    <div class="stat-icon pendiente"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Horas Extras Pendientes</h3>
                        <div class="stat-value" id="horasExtrasPendientes">0h 0m</div>
                        <small class="stat-description">Esperando aprobación</small>
                    </div>
                </div>
                <!-- Horas Extras Rechazadas -->
                <div class="stat-card">
                    <div class="stat-icon rechazada"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-info">
                        <h3>Horas Extras Rechazadas</h3>
                        <div class="stat-value" id="horasExtrasRechazadas">0h 0m</div>
                        <small class="stat-description">No aprobadas</small>
                    </div>
                </div>
                <!-- Horas Extras Aprobadas -->
                <div class="stat-card">
                    <div class="stat-icon aprobada"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3>Horas Extras Aprobadas</h3>
                        <div class="stat-value" id="horasExtrasAprobadas">0h 0m</div>
                        <small class="stat-description">Ya aprobadas</small>
                    </div>
                </div>
                <!-- Total -->
                <div class="stat-card total">
                    <div class="stat-icon total"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Total Horas</h3>
                        <div class="stat-value" id="totalHoras">0h 0m</div>
                        <small class="stat-description">Suma de todas las categorías</small>
                    </div>
                </div>
            </div>

            <!-- Tabla de horas trabajadas -->
            <div class="table-section">
                <div class="table-header">
                    <h3>Detalle de Horas Trabajadas</h3>
                    <div class="table-actions">
                        <button class="btn-primary" id="btnExportExcel">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </button>
                        <button class="btn-icon" id="btnRefresh" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="horas-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Fecha</th>
                                <th>Día</th>
                                <th>Horario Asignado</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Regulares</th>
                                <th>Rec. Nocturno</th>
                                <th>Rec. Dom/Fest</th>
                                <th>Rec. Noct Dom/Fest</th>
                                <th>Extra Diurna</th>
                                <th>Extra Nocturna</th>
                                <th>Extra D. Dom/Fest</th>
                                <th>Extra N. Dom/Fest</th>
                                <th>Total</th>
                                <th>Justificación</th>
                            </tr>
                        </thead>
                        <tbody id="horasTableBody">
                            <tr>
                                <td colspan="16" class="no-data">
                                    <i class="fas fa-info-circle"></i> Seleccione los filtros y presione "Filtrar" para ver las horas trabajadas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="horasPaginationContainer" style="display: none;">
                    <div class="pagination-info" id="horasPaginationInfo">Mostrando 0 - 0 de 0 registros</div>
                    <div class="pagination-controls">
                        <div class="pagination-limit">
                            <label for="horasPaginationSize">Mostrar</label>
                            <select id="horasPaginationSize" class="pagination-select">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                            </select>
                            <span>registros</span>
                        </div>
                        <div class="pagination-buttons" id="horasPaginationButtons"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal para registrar día cívico -->
<div id="modalDiaCivico" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus"></i> Registrar Día Cívico</h3>
            <button class="modal-close" id="closeDiaCivico">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formDiaCivico">
                <div class="form-group">
                    <label for="fechaDiaCivico">Fecha:</label>
                    <input type="date" id="fechaDiaCivico" name="fecha" required>
                </div>
                <div class="form-group">
                    <label for="nombreDiaCivico">Nombre del día cívico:</label>
                    <input type="text" id="nombreDiaCivico" name="nombre" placeholder="Ej: Día de la Madre" required>
                </div>
                <div class="form-group">
                    <label for="descripcionDiaCivico">Descripción:</label>
                    <textarea id="descripcionDiaCivico" name="descripcion" rows="3" placeholder="Descripción opcional"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelDiaCivico">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para seleccionar empleados -->
<div id="modalSelectEmpleados" class="modal">
    <div class="modal-content modal-empleados">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> Seleccionar Empleados</h3>
            <button class="modal-close" id="closeSelectEmpleados">&times;</button>
        </div>
        <div class="modal-body">
            <div class="empleados-search">
                <div class="search-group">
                    <input type="text" id="searchEmpleados" placeholder="Buscar empleados..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
            
            <div class="empleados-actions">
                <button type="button" class="btn-text" id="selectAllEmpleados">
                    <i class="fas fa-check-double"></i> Seleccionar todos
                </button>
                <button type="button" class="btn-text" id="deselectAllEmpleados">
                    <i class="fas fa-times"></i> Deseleccionar todos
                </button>
            </div>
            
            <div class="empleados-list-container">
                <div id="empleadosLoading" class="empleados-loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </div>
                <div id="empleadosListContent" class="empleados-list" style="display: none;">
                    <!-- Employee checkboxes will be populated here -->
                </div>
                <div id="empleadosNoResults" class="empleados-no-results" style="display: none;">
                    <i class="fas fa-info-circle"></i> No se encontraron empleados
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="selected-count">
                    <span id="selectedCount">0</span> empleado(s) seleccionado(s)
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelSelectEmpleados">Cancelar</button>
                    <button type="button" class="btn-primary" id="confirmSelectEmpleados">Aplicar Selección</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para aprobación de horas extras -->
<div id="modalAprobacionHorasExtras" class="modal">
    <div class="modal-content modal-horas-extras">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle"></i> Aprobación de Horas Extras</h3>
            <button class="modal-close" id="closeAprobacionHorasExtras">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Filtros para horas extras -->
            <div class="horas-extras-filters">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filtroFechaDesdeExtras">Fecha desde:</label>
                        <input type="date" id="filtroFechaDesdeExtras" class="filter-select">
                    </div>
                    <div class="filter-group">
                        <label for="filtroFechaHastaExtras">Fecha hasta:</label>
                        <input type="date" id="filtroFechaHastaExtras" class="filter-select">
                    </div>
                    <div class="filter-group">
                        <label for="filtroSedeExtras">Sede:</label>
                        <select id="filtroSedeExtras" class="filter-select">
                            <option value="">Todas las sedes</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filtroEstablecimientoExtras">Establecimiento:</label>
                        <select id="filtroEstablecimientoExtras" class="filter-select">
                            <option value="">Todos los establecimientos</option>
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filtroEstadoExtras">Estado:</label>
                        <select id="filtroEstadoExtras" class="filter-select">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="rechazada">Rechazada</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="btnSelectEmpleadosExtras">Empleados:</label>
                        <button type="button" id="btnSelectEmpleadosExtras" class="filter-select empleados-btn">
                            <span class="empleados-text">Todos los empleados</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="filter-group">
                        <button class="btn-primary" id="btnFiltrarHorasExtras">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button class="btn-secondary" id="btnLimpiarFiltrosExtras" title="Limpiar filtros de sede, establecimiento y estado">
                            <i class="fas fa-eraser"></i> Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- Botones rápidos para consultas -->
                <div class="filter-row">
                    <div class="quick-buttons">
                        <label>Consultas rápidas:</label>
                        <button class="btn-quick" id="btnQuickPendientes" data-estado="pendiente">
                            <i class="fas fa-clock"></i> Pendientes
                        </button>
                        <button class="btn-quick" id="btnQuickAprobadas" data-estado="aprobada">
                            <i class="fas fa-check"></i> Aprobadas
                        </button>
                        <button class="btn-quick" id="btnQuickRechazadas" data-estado="rechazada">
                            <i class="fas fa-times"></i> Rechazadas
                        </button>
                        <button class="btn-quick" id="btnQuickTodas" data-estado="">
                            <i class="fas fa-list"></i> Todas
                        </button>
                    </div>
                </div>

                <!-- Botones rápidos para fechas -->
                <div class="filter-row">
                    <div class="quick-buttons">
                        <label>Filtros de fecha:</label>
                        <button class="btn-quick" id="btnQuickHoy" data-dias="0">
                            <i class="fas fa-calendar-day"></i> Hoy
                        </button>
                        <button class="btn-quick" id="btnQuickUltimos7" data-dias="7">
                            <i class="fas fa-calendar-week"></i> Últimos 7 días
                        </button>
                        <button class="btn-quick" id="btnQuickUltimos30" data-dias="30">
                            <i class="fas fa-calendar-alt"></i> Últimos 30 días
                        </button>
                        <button class="btn-quick" id="btnQuickMesActual" data-dias="mes">
                            <i class="fas fa-calendar"></i> Mes actual
                        </button>
                        <button class="btn-quick" id="btnQuickLimpiarFechas" data-dias="limpiar">
                            <i class="fas fa-eraser"></i> Limpiar fechas
                        </button>
                    </div>
                </div>
            </div>

            <!-- Acciones masivas -->
            <div class="bulk-actions" id="bulkActions" style="display: none;">
                <div class="selected-count">
                    <span id="selectedExtrasCount">0</span> horas extras seleccionadas
                </div>
                <div class="bulk-buttons">
                    <button class="btn-success" id="btnAprobarSeleccionadas">
                        <i class="fas fa-check"></i> Aprobar Seleccionadas
                    </button>
                    <button class="btn-danger" id="btnRechazarSeleccionadas">
                        <i class="fas fa-times"></i> Rechazar Seleccionadas
                    </button>
                </div>
            </div>

            <!-- Tabla de horas extras -->
            <div class="table-container">
                <table class="horas-extras-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAllExtras"></th>
                            <th>Empleado</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Horario</th>
                            <th>Fecha</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Horas Extras</th>
                            <th>Tipo Extra</th>
                            <th>Tipo Horario</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="horasExtrasTableBody">
                        <tr>
                            <td colspan="13" class="no-data">
                                <i class="fas fa-info-circle"></i> No hay horas extras para mostrar. Las horas extras se generan automáticamente al calcular las horas trabajadas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination-container" id="horasExtrasPaginationContainer" style="display: none;">
                <div class="pagination-info" id="horasExtrasPaginationInfo">Mostrando 0 - 0 de 0 registros</div>
                <div class="pagination-controls">
                    <div class="pagination-limit">
                        <label for="horasExtrasPaginationSize">Mostrar</label>
                        <select id="horasExtrasPaginationSize" class="pagination-select">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        <span>registros</span>
                    </div>
                    <div class="pagination-buttons" id="horasExtrasPaginationButtons"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para seleccionar empleados (Horas Extras) -->
<div id="modalSelectEmpleadosExtras" class="modal">
    <div class="modal-content modal-empleados">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> Seleccionar Empleados (Horas Extras)</h3>
            <button class="modal-close" id="closeSelectEmpleadosExtras">&times;</button>
        </div>
        <div class="modal-body">
            <div class="empleados-search">
                <div class="search-group">
                    <input type="text" id="searchEmpleadosExtras" placeholder="Buscar empleados..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>

            <div class="empleados-actions">
                <button type="button" class="btn-text" id="selectAllEmpleadosExtras">
                    <i class="fas fa-check-double"></i> Seleccionar todos
                </button>
                <button type="button" class="btn-text" id="deselectAllEmpleadosExtras">
                    <i class="fas fa-times"></i> Deseleccionar todos
                </button>
            </div>

            <div class="empleados-list-container">
                <div id="empleadosExtrasLoading" class="empleados-loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </div>
                <div id="empleadosExtrasListContent" class="empleados-list" style="display: none;">
                    <!-- Employee checkboxes will be populated here -->
                </div>
                <div id="empleadosExtrasNoResults" class="empleados-no-results" style="display: none;">
                    <i class="fas fa-info-circle"></i> No se encontraron empleados
                </div>
            </div>

            <div class="modal-footer">
                <div class="selected-count">
                    <span id="selectedExtrasEmpleadosCount">0</span> empleado(s) seleccionado(s)
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelSelectEmpleadosExtras">Cancelar</button>
                    <button type="button" class="btn-primary" id="confirmSelectEmpleadosExtras">Aplicar Selección</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/layout.js"></script>
<script src="assets/js/horas-trabajadas.js"></script>

<!-- Modal de Ayuda para Horas Trabajadas -->
<?php include 'modal_ayuda_horas_trabajadas.php'; ?>

<!-- Inicialización del modal de ayuda de horas trabajadas -->
<script>
// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando modal de ayuda de horas trabajadas...');

    const helpButton = document.getElementById('btnAyudaHorasTrabajadas');

    if (helpButton) {
        console.log('✅ Botón de ayuda de horas trabajadas encontrado');
        // Asegurar que el botón tenga el event listener correcto
        helpButton.onclick = function(e) {
            e.preventDefault();
            showHorasTrabajadasHelpModal();
        };
    } else {
        console.warn('⚠️ Botón de ayuda de horas trabajadas no encontrado');
    }

    console.log('✅ Inicialización del modal de ayuda de horas trabajadas completada');
});

// Script de debug para verificar el modal de horas trabajadas
console.log('🔍 === DEBUG MODAL HORAS TRABAJADAS ===');

// Verificar elementos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Verificando elementos del modal de horas trabajadas...');

    const button = document.getElementById('btnAyudaHorasTrabajadas');
    const modal = document.getElementById('horasTrabajadasHelpModal');

    console.log('Botón encontrado:', !!button);
    console.log('Modal encontrado:', !!modal);

    if (modal) {
        console.log('Modal display inicial:', modal.style.display);
        console.log('Modal visibility:', window.getComputedStyle(modal).visibility);
        console.log('Modal opacity:', window.getComputedStyle(modal).opacity);

        // Verificar contenido del modal
        const content = modal.querySelector('.horas-modal-content');
        console.log('Contenido del modal encontrado:', !!content);

        if (content) {
            const tabs = content.querySelectorAll('.horas-tab-content');
            console.log('Número de tabs encontrados:', tabs.length);

            const tabBtns = content.querySelectorAll('.horas-tab-btn');
            console.log('Número de botones de tab encontrados:', tabBtns.length);
        }
    }

    // Agregar función de debug global
    window.debugHorasTrabajadasModal = function() {
        console.log('🔍 === DEBUG MANUAL DEL MODAL HORAS TRABAJADAS ===');

        const modal = document.getElementById('horasTrabajadasHelpModal');
        if (!modal) {
            console.error('❌ Modal de horas trabajadas no encontrado');
            return;
        }

        console.log('Modal element:', modal);
        console.log('Modal classes:', modal.className);
        console.log('Modal display:', modal.style.display);
        console.log('Modal computed display:', window.getComputedStyle(modal).display);
        console.log('Modal computed visibility:', window.getComputedStyle(modal).visibility);

        const content = modal.querySelector('.horas-modal-content');
        if (content) {
            console.log('Content found, innerHTML length:', content.innerHTML.length);
            console.log('Content visible:', window.getComputedStyle(content).display !== 'none');
        } else {
            console.error('❌ Content not found');
        }

        const tabs = modal.querySelectorAll('.horas-tab-content');
        console.log('Tabs found:', tabs.length);

        tabs.forEach((tab, index) => {
            console.log(`Tab ${index}:`, tab.id, tab.classList.contains('active'));
        });
    };

    console.log('✅ Debug functions available. Use debugHorasTrabajadasModal() in console.');
});

console.log('🔍 === FIN DEBUG MODAL HORAS TRABAJADAS ===');
</script>

</body>
</html>