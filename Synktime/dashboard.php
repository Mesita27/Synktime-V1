<?php
require_once 'config/database.php';
require_once 'dashboard-controller.php';
require_once 'dashboard-controller-simplified.php';
require_once 'auth/session.php';

// Verificar autenticación y permisos para dashboard
requireModuleAccess('dashboard');

// La sesión ya está iniciada por requireModuleAccess()
$usuarioInfo = getCurrentUser(); // Usar la función de sesión existente
$empresaId = $usuarioInfo ? $usuarioInfo['id_empresa'] : 1;

// Solo obtener info adicional si es necesario
if (!$usuarioInfo || !$empresaId) {
    // Fallback si hay problemas con la sesión
    if (isset($_SESSION['username'])) {
        $usuarioInfoDB = getUsuarioInfo($_SESSION['username']);
        if ($usuarioInfoDB) {
            $empresaId = $usuarioInfoDB['ID_EMPRESA'];
        }
    }
}

$fechaDashboard = date('Y-m-d');
$empresaInfo = getEmpresaInfo($empresaId);
$sedes = getSedesByEmpresa($empresaId);

// No seleccionar valores predeterminados inicialmente
$sedeDefaultId = null;
$establecimientoDefaultId = null;
$establecimientos = [];

// Por defecto muestra la info de la empresa completa usando funciones simplificadas
$estadisticas = getEstadisticasAsistenciaSimplified('empresa', $empresaId, $fechaDashboard);
$asistenciasPorHora = getAsistenciasPorHoraSimplified($empresaId, $fechaDashboard);
$distribucionAsistencias = getDistribucionAsistenciasSimplified($empresaId, $fechaDashboard);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SynkTime - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
    <div class="app-container">
        <?php include 'components/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php include 'components/header.php'; ?>
            <main class="main-content">
                <div class="dashboard-container">
                    <!-- Filtros -->
                    <div class="filters-section">
                        <div class="company-info">
                            <h2><?php echo htmlspecialchars($empresaInfo['NOMBRE'] ?? 'Empresa'); ?></h2>
                            <p class="company-details"><i class="fas fa-building"></i> <?php echo htmlspecialchars($empresaInfo['RUC'] ?? 'RUC no disponible'); ?></p>
                        </div>
                        <div class="location-filters">
                            <div class="filter-group">
                                <label for="selectSede">Sede:</label>
                                <select id="selectSede" class="filter-select">
                                    <option value="">Todos</option>
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
                                    <option value="">Todos</option>
                                    <?php foreach ($establecimientos as $establecimiento): ?>
                                        <option value="<?php echo $establecimiento['ID_ESTABLECIMIENTO']; ?>">
                                            <?php echo htmlspecialchars($establecimiento['NOMBRE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="selectFecha">Fecha:</label>
                                <input type="date" id="selectFecha" class="filter-select" value="<?php echo $fechaDashboard; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card clickable" id="stat-card-temprano" data-type="temprano" title="Click para ver detalles">
                            <div class="stat-icon info"><i class="fas fa-user-clock"></i></div>
                            <div class="stat-info">
                                <h3>Llegadas Tempranas</h3>
                                <div class="stat-value" id="llegadasTemprano"><?php echo $estadisticas['llegadas_temprano'] ?? 0; ?></div>
                            </div>
                        </div>
                        <div class="stat-card clickable" id="stat-card-atiempo" data-type="aTiempo" title="Click para ver detalles">
                            <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
                            <div class="stat-info">
                                <h3>A Tiempo</h3>
                                <div class="stat-value" id="llegadasTiempo"><?php echo $estadisticas['llegadas_tiempo'] ?? 0; ?></div>
                            </div>
                        </div>
                        <div class="stat-card clickable" id="stat-card-tarde" data-type="tarde" title="Click para ver detalles">
                            <div class="stat-icon warning"><i class="fas fa-user-clock"></i></div>
                            <div class="stat-info">
                                <h3>Llegadas Tarde</h3>
                                <div class="stat-value" id="llegadasTarde"><?php echo $estadisticas['llegadas_tarde'] ?? 0; ?></div>
                            </div>
                        </div>
                        <div class="stat-card clickable" id="stat-card-faltas" data-type="faltas" title="Click para ver detalles">
                            <div class="stat-icon danger"><i class="fas fa-user-times"></i></div>
                            <div class="stat-info">
                                <h3>Faltas</h3>
                                <div class="stat-value" id="faltas"><?php echo $estadisticas['faltas'] ?? 0; ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-clock"></i></div>
                            <div class="stat-info">
                                <h3>Horas Trabajadas</h3>
                                <div class="stat-value" id="horasTrabajadas"><?php 
                                    if (isset($estadisticas['horas_trabajadas'])) {
                                        $horas = floor($estadisticas['horas_trabajadas']);
                                        $minutos = round(($estadisticas['horas_trabajadas'] - $horas) * 60);
                                        echo sprintf('%02d:%02d', $horas, $minutos);
                                    } else {
                                        echo '00:00';
                                    }
                                ?></div>
                            </div>
                        </div>
                    </div>
                    <!-- Charts Grid -->
                    <div class="charts-grid">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3>Asistencia por Hora</h3>
                                <div class="chart-actions">
                                    <button class="btn-icon" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container" id="hourlyAttendanceChart"></div>
                        </div>
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3>Distribución de Asistencias</h3>
                                <div class="chart-actions">
                                    <button class="btn-icon" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container" id="attendanceDistributionChart"></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="assets/js/layout.js"></script>
    <!-- Incluir timezone de Bogotá para fechas y horas -->
    <script src="js/timezone-bogota.js"></script>
    <script>
    /**
     * Convierte horas decimales a formato HH:MM
     * @param {number} horasDecimales - Horas en formato decimal (ej: 8.5)
     * @returns {string} Horas en formato HH:MM (ej: "08:30")
     */
    function formatearHorasMinutos(horasDecimales) {
        if (!horasDecimales || horasDecimales === 0) {
            return '00:00';
        }
        
        const horas = Math.floor(horasDecimales);
        const minutos = Math.round((horasDecimales - horas) * 60);
        
        return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;
    }
    </script>
    <script>
    // Variables globales para los datos iniciales
    const initialData = {
        sedeId: null,
        establecimientoId: null,
        fecha: <?php echo json_encode($fechaDashboard); ?>,
        hourlyAttendanceData: <?php echo json_encode($asistenciasPorHora); ?>,
        distributionData: {
            tempranos: <?php echo $estadisticas['llegadas_temprano'] ?? 0; ?>,
            atiempo: <?php echo $estadisticas['llegadas_tiempo'] ?? 0; ?>,
            tarde: <?php echo $estadisticas['llegadas_tarde'] ?? 0; ?>,
            faltas: <?php echo $estadisticas['faltas'] ?? 0; ?>
        }
    };

    // Clase Dashboard simple para manejar gráficos
    class Dashboard {
        constructor() {
            this.attendanceByHourChart = null;
            this.attendanceDistributionChart = null;
        }
        
        initializeChartsWithData(data) {
            this.initializeHourlyChart(data.hourlyAttendanceData);
            this.initializeDistributionChart(data.distributionData);
        }
        
        updateCharts(hourlyData, distributionData) {
            this.updateHourlyChart(hourlyData);
            this.updateDistributionChart(distributionData);
        }
        
        initializeHourlyChart(data) {
            // Asegurar que siempre tengamos datos válidos para ApexCharts
            const validData = (data && Array.isArray(data.data)) ? data.data : [];
            const validCategories = (data && Array.isArray(data.categories)) ? data.categories : [];
            
            const options = {
                series: [{
                    name: 'Entradas',
                    data: validData
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: { show: false }
                },
                colors: ['#4B96FA'],
                xaxis: {
                    categories: validCategories
                }
            };
            
            this.attendanceByHourChart = new ApexCharts(document.querySelector("#hourlyAttendanceChart"), options);
            this.attendanceByHourChart.render();
        }
        
        initializeDistributionChart(data) {
            const options = {
                series: [data.tempranos, data.atiempo, data.tarde, data.faltas],
                chart: {
                    type: 'donut',
                    height: 350
                },
                labels: ['Temprano', 'A tiempo', 'Tarde', 'Faltas'],
                colors: ['#00C853', '#2196F3', '#FF9800', '#F44336']
            };
            
            this.attendanceDistributionChart = new ApexCharts(document.querySelector("#attendanceDistributionChart"), options);
            this.attendanceDistributionChart.render();
        }
        
        updateHourlyChart(data) {
            if (this.attendanceByHourChart) {
                // Validación más robusta para datos de ApexCharts
                let validData = [];
                let validCategories = [];
                
                if (data && typeof data === 'object') {
                    // Validar y limpiar el array de datos
                    if (Array.isArray(data.data)) {
                        validData = data.data.map(value => {
                            // Convertir null/undefined a 0, asegurar que sea número
                            return (value === null || value === undefined || isNaN(value)) ? 0 : Number(value);
                        });
                    }
                    
                    // Validar y limpiar categorías
                    if (Array.isArray(data.categories)) {
                        validCategories = data.categories.map(category => {
                            // Convertir null/undefined a string vacío
                            return (category === null || category === undefined) ? '' : String(category);
                        });
                    }
                }
                
                console.log('Datos para gráfico:', { validData, validCategories });
                
                this.attendanceByHourChart.updateSeries([{
                    name: 'Entradas',
                    data: validData
                }]);
                
                this.attendanceByHourChart.updateOptions({
                    xaxis: { categories: validCategories }
                });
            }
        }
        
        updateDistributionChart(data) {
            if (this.attendanceDistributionChart) {
                // Asegurar que siempre tengamos datos válidos para ApexCharts
                const validData = data ? [
                    data.tempranos || 0,
                    data.atiempo || 0,
                    data.tarde || 0,
                    data.faltas || 0
                ] : [0, 0, 0, 0];
                
                this.attendanceDistributionChart.updateSeries(validData);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const dashboard = new Dashboard();
        dashboard.initializeChartsWithData(initialData);
        
        // Los event listeners para tarjetas son manejados por dashboard-popups.js
        
        const selectSede = document.getElementById('selectSede');
        const selectEstablecimiento = document.getElementById('selectEstablecimiento');
        const selectFecha = document.getElementById('selectFecha');
        const llegadasTemprano = document.getElementById('llegadasTemprano');
        const llegadasTiempo = document.getElementById('llegadasTiempo');
        const llegadasTarde = document.getElementById('llegadasTarde');
        const faltas = document.getElementById('faltas');
        const horasTrabajadas = document.getElementById('horasTrabajadas');
        const today = window.Bogota.getDateString(); // Fecha actual en zona horaria de Bogotá
        selectFecha.setAttribute('max', today);
    const establecimientosDashboardCache = {};

        // Cargar filtros dinámicamente
        cargarFiltrosDashboard().then(() => {
            // Cargar estadísticas iniciales después de cargar filtros
            cargarEstadisticas();
        }).catch(error => {
            console.error('Error en la carga inicial:', error);
            // Cargar estadísticas de todos modos con los datos disponibles
            cargarEstadisticas();
        });
        
        // Función para cargar filtros del dashboard
        async function cargarFiltrosDashboard() {
            try {
                // Cargar sedes
                const sedesResponse = await fetch('api/get-sedes.php', {
                    credentials: 'same-origin'
                });
                const sedesData = await sedesResponse.json();
                
                const selectSede = document.getElementById('selectSede');
                if (selectSede && sedesData.success) {
                    // No mantener valor por defecto, dejar vacío inicialmente
                    const currentSedeValue = selectSede.value || '';
                    
                    selectSede.innerHTML = '<option value="">Todos</option>';
                    sedesData.sedes.forEach(sede => {
                        const option = document.createElement('option');
                        option.value = sede.ID_SEDE;
                        option.textContent = sede.NOMBRE;
                        if (sede.ID_SEDE == currentSedeValue) {
                            option.selected = true;
                        }
                        selectSede.appendChild(option);
                    });
                } else {
                    console.warn('No se pudieron cargar las sedes o el elemento no existe');
                }
                
                // Cargar establecimientos iniciales
                await cargarEstablecimientosDashboard();
                
                // Cargar estadísticas iniciales después de cargar filtros
                cargarEstadisticas();
                
            } catch (error) {
                console.error('Error cargando filtros del dashboard:', error);
            }
        }
        
        // Función para cargar establecimientos según sede seleccionada
        async function cargarEstablecimientosDashboard() {
            const selectSede = document.getElementById('selectSede');
            const selectEstablecimiento = document.getElementById('selectEstablecimiento');
            
            if (!selectEstablecimiento) return;
            
            const sedeId = selectSede ? selectSede.value : '';
            
            // No mantener valor por defecto, dejar vacío inicialmente
            const currentEstablecimientoValue = selectEstablecimiento.value || '';
            
            selectEstablecimiento.innerHTML = '<option value="">Todos</option>';
            
            if (!sedeId) {
                return;
            }

            const cacheKey = String(sedeId);

            const renderOptions = (establecimientos) => {
                establecimientos.forEach(establecimiento => {
                    const option = document.createElement('option');
                    option.value = establecimiento.ID_ESTABLECIMIENTO;
                    option.textContent = establecimiento.NOMBRE;
                    if (establecimiento.ID_ESTABLECIMIENTO == currentEstablecimientoValue) {
                        option.selected = true;
                    }
                    selectEstablecimiento.appendChild(option);
                });
            };

            if (establecimientosDashboardCache[cacheKey]) {
                renderOptions(establecimientosDashboardCache[cacheKey]);
                return;
            }

            try {
                const response = await fetch(`api/get-establecimientos.php?sede_id=${encodeURIComponent(sedeId)}`, {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success && Array.isArray(data.establecimientos)) {
                    establecimientosDashboardCache[cacheKey] = data.establecimientos;
                    renderOptions(data.establecimientos);
                }
            } catch (error) {
                console.error('Error cargando establecimientos:', error);
            }
        }
        
        // Evento para cambio de sede
        if (selectSede) {
            selectSede.addEventListener('change', async function() {
                await cargarEstablecimientosDashboard();
                cargarEstadisticas();
            });
        }

        // Evento para cambio de establecimiento
        if (selectEstablecimiento) {
            selectEstablecimiento.addEventListener('change', cargarEstadisticas);
        }
        // Evento para cambio de fecha
        if (selectFecha) {
            selectFecha.addEventListener('change', cargarEstadisticas);
        }

        function cargarEstadisticas() {
            const sedeId = selectSede.value;
            const establecimientoId = selectEstablecimiento.value;
            const fecha = selectFecha.value || initialData.fecha;
            let url = "api/get-dashboard-stats-simplified.php?";
            if (establecimientoId) {
                url += "establecimiento_id=" + encodeURIComponent(establecimientoId) + "&";
            } else if (sedeId) {
                url += "sede_id=" + encodeURIComponent(sedeId) + "&";
            } // Si ambos son "", no se agrega nada y será a nivel empresa
            if (fecha) url += "fecha=" + encodeURIComponent(fecha) + "&";
            
            fetch(url, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        llegadasTemprano.textContent = data.estadisticas.llegadas_temprano || 0;
                        llegadasTiempo.textContent = data.estadisticas.llegadas_tiempo || 0;
                        llegadasTarde.textContent = data.estadisticas.llegadas_tarde || 0;
                        faltas.textContent = data.estadisticas.faltas || 0;
                        horasTrabajadas.textContent = formatearHorasMinutos(data.estadisticas.horas_trabajadas || 0);
                        
                        // Update charts with proper data structure
                        const hourlyData = data.asistenciasPorHora || { categories: [], data: [] };
                        const distributionData = {
                            tempranos: data.estadisticas.llegadas_temprano || 0,
                            atiempo: data.estadisticas.llegadas_tiempo || 0,
                            tarde: data.estadisticas.llegadas_tarde || 0,
                            faltas: data.estadisticas.faltas || 0
                        };
                        dashboard.updateCharts(hourlyData, distributionData);
                    } else {
                        // Resetear estadísticas en caso de error
                        llegadasTemprano.textContent = '0';
                        llegadasTiempo.textContent = '0';
                        llegadasTarde.textContent = '0';
                        faltas.textContent = '0';
                        horasTrabajadas.textContent = '00:00';
                        dashboard.updateCharts({ categories: [], data: [] }, { tempranos: 0, atiempo: 0, tarde: 0, faltas: 0 });
                    }
                })
                .catch(() => {
                    // Resetear estadísticas en caso de error
                    llegadasTemprano.textContent = '0';
                    llegadasTiempo.textContent = '0';
                    llegadasTarde.textContent = '0';
                    faltas.textContent = '0';
                    horasTrabajadas.textContent = '00:00';
                    dashboard.updateCharts({ categories: [], data: [] }, { tempranos: 0, atiempo: 0, tarde: 0, faltas: 0 });
                });
        }

        function limpiarEstadisticas() {
            llegadasTemprano.textContent = '0';
            llegadasTiempo.textContent = '0';
            llegadasTarde.textContent = '0';
            faltas.textContent = '0';
            horasTrabajadas.textContent = '00:00';
            dashboard.updateCharts({ categories: [], data: [] }, { tempranos: 0, atiempo: 0, tarde: 0, faltas: 0 });
        }
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
    </script>

    <!-- Al final del dashboard.php, justo antes del cierre de body -->

<!-- Incluir los modales de asistencia -->
<?php include 'components/attendance_modals.php'; ?>
<?php include 'components/biometric_enrollment_modal.php'; ?>

<!-- Script para exportar a Excel -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- TensorFlow.js y BlazeFace para reconocimiento biométrico -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.11.0/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js"></script>
<script src="assets/js/biometric-blazeface.js"></script>

<!-- Script para los popups de asistencia -->
<script src="assets/js/dashboard-popups.js"></script>

<!-- Scripts para funcionalidad biométrica -->
<script src="assets/js/biometric-stats.js"></script>
<script src="assets/js/real-data-only.js"></script>

<!-- Incluir los modales de asistencia -->
<?php include 'components/attendance_modals.php'; ?>

</body>
</html>