<?php
require_once 'auth/session.php';
requireModuleAccess('reportes');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Asistencia | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/reports.css">
    <link rel="stylesheet" href="assets/css/reports_modals.css">
    <link rel="stylesheet" href="assets/css/pagination.css">

    <!-- Chart.js para estadísticas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="reports-header">
                <h2 class="page-title"><i class="fas fa-file-alt"></i> Reportes de Asistencia</h2>
                <div class="reports-actions">
                    <button class="btn-secondary" id="btnAyudaReports" title="Ayuda - Reportes de Asistencia">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </button>
                    <button class="btn-primary" id="btnExportarXLS">
                        <i class="fas fa-file-excel"></i> Exportar a XLS
                    </button>
                </div>
            </div>
            
            <!-- Contenido del tab de asistencia -->
            <div id="asistencia-content">
            
            <!-- Filtros rápidos -->
            <div class="quick-filters">
                <button id="btnDiaActual" class="btn-filter">
                    <i class="fas fa-calendar-day"></i> Hoy
                </button>
                <button id="btnUltimos7Dias" class="btn-filter">
                    <i class="fas fa-calendar-week"></i> Últimos 7 días
                </button>
                <button id="btnSemanaActual" class="btn-filter">
                    <i class="fas fa-calendar-week"></i> Semana actual
                </button>
                <button id="btnUltimos30Dias" class="btn-filter">
                    <i class="fas fa-calendar-alt"></i> Últimos 30 días
                </button>
                <button id="btnMesActual" class="btn-filter">
                    <i class="fas fa-calendar-alt"></i> Mes actual
                </button>
            </div>
                <!-- Formulario de búsqueda -->
                <div class="reports-query-box">
                <form class="reports-query-form" id="reportsQueryForm">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtroCodigo">Código</label>
                            <input type="text" id="filtroCodigo" placeholder="Código de empleado">
                        </div>
                        <div class="form-group">
                            <label for="filtroNombre">Nombre</label>
                            <input type="text" id="filtroNombre" placeholder="Nombre o apellido">
                        </div>
                        <div class="form-group">
                            <label for="filtroSede">Sede</label>
                            <select id="filtroSede">
                                <option value="Todas">Todas</option>
                                <!-- Se cargará con JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtroEstablecimiento">Establecimiento</label>
                            <select id="filtroEstablecimiento">
                                <option value="Todos">Todos</option>
                                <!-- Se cargará con JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtroEstadoEntrada">Estado Entrada</label>
                            <select id="filtroEstadoEntrada">
                                <option value="Todos">Todos</option>
                                <option value="A Tiempo">A Tiempo</option>
                                <option value="Temprano">Temprano</option>
                                <option value="Tardanza">Tardanza</option>
                                <option value="Justificado">Justificado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtroEstadoSalida">Estado Salida</label>
                            <select id="filtroEstadoSalida">
                                <option value="Todos">Todos</option>
                                <option value="Puntual">A Tiempo</option>
                                <option value="Temprano">Temprano</option>
                                <option value="Tardanza">Tardanza</option>
                                <option value="Registrada">Registrada</option>
                                <option value="--">Sin salida</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="query-row">
                        <div class="form-group">
                            <label for="fechaDesde">Fecha desde</label>
                            <input type="date" id="fechaDesde">
                        </div>
                        <div class="form-group">
                            <label for="fechaHasta">Fecha hasta</label>
                            <input type="date" id="fechaHasta">
                        </div>
                        <div class="query-btns">
                            <button type="button" id="btnConsultarReporte" class="btn-primary">
                                <i class="fas fa-search"></i> Consultar
                            </button>
                            <button type="button" id="btnLimpiarReporte" class="btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabla de reportes -->
            <div class="reports-table-container">
                <table class="reports-table" id="reportsTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empleado</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Fecha</th>
                            <th>Hora Entrada</th>
                            <th>Estado Entrada</th>
                            <th>Hora Salida</th>
                            <th>Estado Salida</th>
                            <th>Horas Trabajadas</th>
                            <th>Horario</th>
                            <th>Tipo</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="reporteTableBody">
                        <tr>
                            <td colspan="13" style="text-align: center; padding: 20px;">
                                <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="pagination-container">
                    <div class="pagination-info" id="paginationInfo">
                        Mostrando 0-0 de 0 registros
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Controles de paginación -->
                    </div>
                </div>
            </div>
            
            <!-- Modal de Justificaciones -->
            <div class="modal" id="modalJustificaciones">
                <div class="modal-content" style="max-width: 95%; width: 1200px;">
                    <div class="modal-header">
                        <h3><i class="fas fa-clipboard-list"></i> Reportes de Justificaciones</h3>
                        <button class="modal-close" id="btnCerrarModalJustificaciones">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Filtros rápidos -->
                        <div class="modal-section">
                            <div class="modal-section-title">
                                <i class="fas fa-filter"></i> Filtros Rápidos
                            </div>
                            <div class="quick-filters">
                                <button id="btnJustHoy" class="btn-filter">
                                    <i class="fas fa-calendar-day"></i> Hoy
                                </button>
                                <button id="btnJustSemanaActual" class="btn-filter">
                                    <i class="fas fa-calendar-week"></i> Esta semana
                                </button>
                                <button id="btnJustMesActual" class="btn-filter">
                                    <i class="fas fa-calendar-alt"></i> Este mes
                                </button>
                                <button id="btnJustTrimestre" class="btn-filter">
                                    <i class="fas fa-calendar"></i> Trimestre
                                </button>
                                <button class="btn-primary" id="btnExportarJustificacionesCSV">
                                    <i class="fas fa-file-csv"></i> Exportar CSV
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filtros de búsqueda -->
                        <div class="modal-section">
                            <div class="modal-section-title">
                                <i class="fas fa-search"></i> Filtros de Búsqueda
                            </div>
                            <form id="formFiltrosJust" class="reports-query-form">
                                <div class="query-row">
                                    <div class="form-group">
                                        <label for="fechaInicioJust">Fecha Inicio</label>
                                        <input type="date" id="fechaInicioJust" name="fecha_inicio">
                                    </div>
                                    <div class="form-group">
                                        <label for="fechaFinJust">Fecha Fin</label>
                                        <input type="date" id="fechaFinJust" name="fecha_fin">
                                    </div>
                                    <div class="form-group">
                                        <label for="sedeSelectJust">Sede</label>
                                        <select id="sedeSelectJust" name="sede_id">
                                            <option value="">Todas las sedes</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="establecimientoSelectJust">Establecimiento</label>
                                        <select id="establecimientoSelectJust" name="establecimiento_id" disabled>
                                            <option value="">Selecciona una sede primero</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="query-row">
                                    <div class="form-group">
                                        <label for="empleadoSelectJust">Empleado</label>
                                        <input type="text" id="empleadoSelectJust" name="empleado_nombre" placeholder="Buscar por nombre del empleado...">
                                    </div>
                                    <div class="form-group">
                                        <label for="tipoFaltaSelectJust">Tipo de Falta</label>
                                        <select id="tipoFaltaSelectJust" name="tipo_falta">
                                            <option value="">Todos los tipos</option>
                                            <option value="completa">Día Completo</option>
                                            <option value="parcial">Parcial</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="motivoBuscarJust">Motivo</label>
                                        <input type="text" id="motivoBuscarJust" name="motivo" placeholder="Buscar por motivo...">
                                    </div>
                                    <div class="form-group"></div> <!-- Spacer for grid alignment -->
                                </div>
                                <div class="query-row">
                                    <div class="query-btns">
                                        <button type="submit" class="btn-primary">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                        <button type="button" class="btn-secondary" id="btnLimpiarFiltrosJust">
                                            <i class="fas fa-times"></i> Limpiar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Estadísticas rápidas -->
                        <div class="modal-section">
                            <div class="modal-section-title">
                                <i class="fas fa-chart-bar"></i> Estadísticas
                            </div>
                            <div class="stats-grid">
                                <div class="stat-card stat-primary">
                                    <div class="stat-icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-label">Total Justificaciones</span>
                                        <span class="stat-value" id="totalJustificacionesCard">-</span>
                                    </div>
                                </div>
                                <div class="stat-card stat-success">
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-label">Faltas Día Completo</span>
                                        <span class="stat-value" id="empleadosDistintosCard">-</span>
                                    </div>
                                </div>
                                <div class="stat-card stat-warning">
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-label">Horas Justificadas</span>
                                        <span class="stat-value" id="horasJustificadasCard">0</span>
                                    </div>
                                </div>
                                <div class="stat-card stat-info">
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-minus"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-label">Faltas Parciales</span>
                                        <span class="stat-value" id="faltasParcialesCard">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de justificaciones -->
                        <div class="modal-section">
                            <div class="modal-section-title">
                                <i class="fas fa-table"></i> Lista de Justificaciones
                                <span class="badge-count" id="badgeTotalJust">0</span>
                            </div>
                            
                            <!-- Loading spinner -->
                            <div id="loadingSpinnerJust" class="loader-container">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>Cargando justificaciones...</span>
                            </div>
                            
                            <!-- Tabla -->
                            <div class="table-container" id="tablaContainerJust" style="display: none;">
                                <div class="table-controls">
                                    <div class="table-info">
                                        <span>Mostrar:</span>
                                        <select id="limitSelectJust">
                                            <option value="25">25</option>
                                            <option value="50" selected>50</option>
                                            <option value="100">100</option>
                                            <option value="500">500</option>
                                        </select>
                                        <span>registros</span>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="reports-table" id="tablaJustificaciones">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha Falta</th>
                                                <th>Empleado</th>
                                                <th>DNI</th>
                                                <th>Motivo</th>
                                                <th>Tipo</th>
                                                <th>Turno</th>
                                                <th>Horas</th>
                                                <th>Fecha Creación</th>
                                                <th>Observaciones</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaBodyJust">
                                            <!-- Datos dinámicos -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Paginación -->
                                <div class="pagination-container" id="paginacionContainerJust">
                                    <div class="pagination-info" id="paginacionInfoJust">
                                        Mostrando 0-0 de 0 registros
                                    </div>
                                    <div class="pagination-controls" id="paginacionJust">
                                        <!-- Paginación dinámica -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mensaje cuando no hay datos -->
                            <div id="noDataMessageJust" class="empty-state" style="display: none;">
                                <i class="fas fa-search"></i>
                                <h4>No se encontraron justificaciones</h4>
                                <p>Prueba ajustando los filtros de búsqueda</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Incluir modales de reportes -->
            <?php include 'components/reports_modals.php'; ?>
        </main>
    </div>
</div>

<!-- Scripts -->
<!-- jQuery (required for Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- App Scripts -->
<script src="assets/js/layout.js"></script>
<script src="assets/js/reports_modals.js"></script>
<script src="assets/js/reports.js"></script>
<script src="assets/js/reportes_justificaciones_integrado.js"></script>

<script>
// Script de inicialización del modal de justificaciones
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando modal de justificaciones');
    
    // Función para abrir el modal
    function abrirModalJustificaciones() {
        const modal = document.getElementById('modalJustificaciones');
        if (modal) {
            modal.classList.add('show');
            console.log('✅ Modal de justificaciones abierto');
            
            // Inicializar datos si el módulo está disponible
            if (window.reportesJustificaciones) {
                setTimeout(() => {
                    window.reportesJustificaciones.cargarDatosIniciales();
                }, 100);
            }
        }
    }
    
    // Función para cerrar el modal
    function cerrarModalJustificaciones() {
        const modal = document.getElementById('modalJustificaciones');
        if (modal) {
            modal.classList.remove('show');
            console.log('✅ Modal de justificaciones cerrado');
        }
    }
    
    // Event listeners
    const btnAbrir = document.getElementById('btnReportesJustificaciones');
    if (btnAbrir) {
        btnAbrir.addEventListener('click', abrirModalJustificaciones);
    }
    
    const btnCerrar = document.getElementById('btnCerrarModalJustificaciones');
    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModalJustificaciones);
    }
    
    // Cerrar modal al hacer clic en el fondo
    const modal = document.getElementById('modalJustificaciones');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalJustificaciones();
            }
        });
    }
    
    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalJustificaciones();
        }
    });
    
    // Exposer funciones globalmente para debugging
    window.abrirModalJustificaciones = abrirModalJustificaciones;
    window.cerrarModalJustificaciones = cerrarModalJustificaciones;
    
    console.log('✅ Modal de justificaciones configurado');

    // Inicializar módulos de reportes nuevos
    console.log('🚀 Inicializando módulos de reportes nuevos...');
    
    // Inicializar modales
    if (typeof ReportsModals !== 'undefined') {
        window.reportsModals = new ReportsModals();
        console.log('✅ Modales de reportes inicializados');
    } else {
        console.error('❌ Clase ReportsModals no encontrada');
    }
    
    // Inicializar manager de reportes
    if (typeof ReportsManager !== 'undefined') {
        window.reportsManager = new ReportsManager();
        console.log('✅ Manager de reportes inicializado');
    } else {
        console.error('❌ Clase ReportsManager no encontrada');
    }

    // Función de prueba para abrir modales manualmente (para debugging)
    window.testModals = function() {
        console.log('🧪 Probando apertura manual de modales...');
        
        // Probar modal de asistencia
        if (window.reportsManager && typeof window.reportsManager.showAttendanceModal === 'function') {
            console.log('📅 Abriendo modal de asistencia de prueba...');
            window.reportsManager.showAttendanceModal('EMP001', '2024-01-15');
        } else {
            console.error('❌ Método showAttendanceModal no encontrado');
        }
        
        // Probar modal de empleado después de 2 segundos
        setTimeout(() => {
            if (window.reportsManager && typeof window.reportsManager.showEmployeeModal === 'function') {
                console.log('👤 Abriendo modal de empleado de prueba...');
                window.reportsManager.showEmployeeModal('EMP001');
            } else {
                console.error('❌ Método showEmployeeModal no encontrado');
            }
        }, 2000);
    };
    
    console.log('✅ Función de prueba testModals() disponible en consola');
});

// Inicialización del modal de ayuda de reportes
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando modal de ayuda de reportes...');

    const helpButton = document.getElementById('btnAyudaReports');

    if (helpButton) {
        console.log('✅ Botón de ayuda de reportes encontrado');
        // Asegurar que el botón tenga el event listener correcto
        helpButton.onclick = function(e) {
            e.preventDefault();
            showReportsHelpModal();
        };
    } else {
        console.warn('⚠️ Botón de ayuda de reportes no encontrado');
    }

    console.log('✅ Inicialización del modal de ayuda de reportes completada');
});

// Script de debug para verificar el modal de reportes
console.log('🔍 === DEBUG MODAL REPORTES ===');

// Verificar elementos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Verificando elementos del modal de reportes...');

    const button = document.getElementById('btnAyudaReports');
    const modal = document.getElementById('reportsHelpModal');

    console.log('Botón encontrado:', !!button);
    console.log('Modal encontrado:', !!modal);

    if (modal) {
        console.log('Modal display inicial:', modal.style.display);
        console.log('Modal visibility:', window.getComputedStyle(modal).visibility);
        console.log('Modal opacity:', window.getComputedStyle(modal).opacity);

        // Verificar contenido del modal
        const content = modal.querySelector('.reports-modal-content');
        console.log('Contenido del modal encontrado:', !!content);

        if (content) {
            const tabs = content.querySelectorAll('.reports-tab-content');
            console.log('Número de tabs encontrados:', tabs.length);

            const tabBtns = content.querySelectorAll('.reports-tab-btn');
            console.log('Número de botones de tab encontrados:', tabBtns.length);
        }
    }
});
</script>

<!-- Modal de ayuda para reportes -->
<?php include 'modal_ayuda_reports.php'; ?>
</body>
</html>
