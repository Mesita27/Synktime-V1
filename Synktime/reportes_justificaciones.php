<?php
require_once 'auth/session.php';
requireModuleAccess('reportes'); // Verificar permisos para módulo de reportes
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Justificaciones | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/reports_query.css">
    <link rel="stylesheet" href="assets/css/reports_modals.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <!-- Header del módulo -->
            <div class="reports-header">
                <h2 class="page-title">
                    <i class="fas fa-clipboard-list"></i> Reportes de Justificaciones de Faltas
                </h2>
                <div class="reports-actions">
                    <button class="btn btn-primary" id="btnExportarCSV">
                        <i class="fas fa-file-csv"></i> Exportar CSV
                    </button>
                    <button class="btn btn-info" id="btnEstadisticas">
                        <i class="fas fa-chart-bar"></i> Estadísticas
                    </button>
                </div>
            </div>
            
            <!-- Filtros rápidos -->
            <div class="quick-filters mb-4">
                <div class="row g-2">
                    <div class="col-auto">
                        <button id="btnHoy" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-calendar-day"></i> Hoy
                        </button>
                    </div>
                    <div class="col-auto">
                        <button id="btnSemanaActual" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-calendar-week"></i> Esta semana
                        </button>
                    </div>
                    <div class="col-auto">
                        <button id="btnMesActual" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-calendar-alt"></i> Este mes
                        </button>
                    </div>
                    <div class="col-auto">
                        <button id="btnTrimestre" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-calendar"></i> Trimestre
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Formulario de filtros avanzados -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filtros de Búsqueda
                        <button class="btn btn-sm btn-outline-primary float-end" type="button" id="btnToggleFiltros">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </h5>
                </div>
                <div class="card-body" id="filtrosContainer">
                    <form id="formFiltros">
                        <div class="row g-3">
                            <!-- Filtros de fecha -->
                            <div class="col-md-3">
                                <label class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fechaInicio" name="fecha_inicio">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fechaFin" name="fecha_fin">
                            </div>
                            
                            <!-- Filtros de empleado -->
                            <div class="col-md-3">
                                <label class="form-label">Empleado</label>
                                <select class="form-select" id="empleadoSelect" name="empleado_id">
                                    <option value="">Todos los empleados</option>
                                </select>
                            </div>
                            
                            <!-- Filtros de sede/establecimiento -->
                            <div class="col-md-3">
                                <label class="form-label">Sede</label>
                                <select class="form-select" id="sedeSelect" name="sede_id">
                                    <option value="">Todas las sedes</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Establecimiento</label>
                                <select class="form-select" id="establecimientoSelect" name="establecimiento_id">
                                    <option value="">Todos los establecimientos</option>
                                </select>
                            </div>
                            
                            <!-- Filtros específicos -->
                            <div class="col-md-3">
                                <label class="form-label">Tipo de Falta</label>
                                <select class="form-select" id="tipoFaltaSelect" name="tipo_falta">
                                    <option value="">Todos los tipos</option>
                                    <option value="completa">Día Completo</option>
                                    <option value="parcial">Parcial</option>
                                    <option value="tardanza">Tardanza</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Motivo (buscar)</label>
                                <input type="text" class="form-control" id="motivoBuscar" name="motivo" placeholder="Ej: médica, familiar...">
                            </div>
                            
                            <!-- Botones -->
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltros">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4" id="estadisticasRapidas">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Justificaciones</h6>
                                    <h4 id="totalJustificaciones">-</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Empleados Distintos</h6>
                                    <h4 id="empleadosDistintos">-</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Horas Justificadas</h6>
                                    <h4 id="horasJustificadas">-</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Faltas Completas</h6>
                                    <h4 id="faltasCompletas">-</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-times fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de resultados -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table"></i> Justificaciones
                        <span class="badge bg-secondary" id="badgeTotal">0</span>
                    </h5>
                    <div class="d-flex align-items-center">
                        <label class="me-2">Mostrar:</label>
                        <select class="form-select form-select-sm" id="limitSelect" style="width: auto;">
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Loading spinner -->
                    <div id="loadingSpinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando justificaciones...</p>
                    </div>
                    
                    <!-- Tabla -->
                    <div class="table-responsive" id="tablaContainer" style="display: none;">
                        <table class="table table-striped table-hover" id="tablaJustificaciones">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Falta</th>
                                    <th>Empleado</th>
                                    <th>DNI</th>
                                    <th>Motivo</th>
                                    <th>Tipo</th>
                                    <th>Turno</th>
                                    <th>Horas</th>
                                    <th>Establecimiento</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBody">
                                <!-- Datos dinámicos -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <nav aria-label="Paginación de justificaciones" id="paginacionContainer" style="display: none;">
                        <ul class="pagination justify-content-center" id="paginacion">
                            <!-- Paginación dinámica -->
                        </ul>
                    </nav>
                    
                    <!-- Mensaje cuando no hay datos -->
                    <div id="noDataMessage" class="text-center py-5" style="display: none;">
                        <i class="fas fa-search fa-3x text-muted"></i>
                        <h5 class="mt-3 text-muted">No se encontraron justificaciones</h5>
                        <p class="text-muted">Prueba ajustando los filtros de búsqueda</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal de Estadísticas -->
<div class="modal fade" id="modalEstadisticas" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar"></i> Estadísticas de Justificaciones
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Justificaciones por Motivo</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartMotivos" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Justificaciones por Tipo de Falta</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartTipos" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6>Evolución Mensual (Últimos 12 meses)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartMensual" width="400" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6>Top 10 Empleados con Más Justificaciones</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm" id="tablaTopEmpleados">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Empleado</th>
                                                <th>Total Justificaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="topEmpleadosBody">
                                            <!-- Datos dinámicos -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i> Detalle de Justificación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/reportes_justificaciones.js"></script>
</body>
</html>