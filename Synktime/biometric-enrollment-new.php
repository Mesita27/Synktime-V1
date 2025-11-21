<?php
require_once __DIR__ . '/auth/session.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscripción Biométrica | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/pagination.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/employee.css">
    <!-- TensorFlow.js para reconocimiento facial -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.8.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/facemesh@0.0.5/dist/facemesh.js"></script>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="employee-header">
                <h2 class="page-title"><i class="fas fa-fingerprint"></i> Inscripción Biométrica</h2>
                <div class="employee-actions">
                    <button class="btn-primary" id="btnRefreshStats"><i class="fas fa-sync-alt"></i> Actualizar</button>
                    <button class="btn-secondary" id="btnExportReport"><i class="fas fa-file-pdf"></i> Reporte</button>
                </div>
            </div>

            <!-- Estadísticas de enrolamiento -->
            <div class="stats-container" id="enrollmentStats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="totalEmployees">0</h3>
                        <p class="stat-label">Total Empleados</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-fingerprint text-success"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="enrolledCount">0</h3>
                        <p class="stat-label">Inscritos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus text-warning"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="pendingCount">0</h3>
                        <p class="stat-label">Pendientes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-pie text-info"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="enrollmentPercentage">0%</h3>
                        <p class="stat-label">Progreso</p>
                    </div>
                </div>
            </div>

            <!-- Filtros de búsqueda -->
            <div class="employee-query-box">
                <form class="employee-query-form" autocomplete="off">
                    <div class="query-row">
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
                            <label for="filtro_estado">Estado Biométrico</label>
                            <select id="filtro_estado" name="estado" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="enrolled">Inscrito</option>
                                <option value="pending">Pendiente</option>
                                <option value="partial">Parcial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="busqueda_empleado">Buscar</label>
                            <input type="text" id="busqueda_empleado" name="busqueda" class="form-control" placeholder="Código o nombre...">
                        </div>
                        <div class="form-group query-btns">
                            <button type="button" id="btnBuscarEmpleados" class="btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" id="btnLimpiarFiltros" class="btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla de empleados -->
            <div class="employee-table-container">
                <table class="employee-table" id="employeeTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Establecimiento</th>
                            <th>Estado Biométrico</th>
                            <th>Facial</th>
                            <th>Huella</th>
                            <th>Última Actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <!-- El JS llenará dinámicamente las filas aquí -->
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="pagination-container" id="paginationContainer">
                <!-- El JS generará los controles de paginación aquí -->
            </div>

            <!-- Modales -->
            <?php include 'components/biometric_enrollment_modal.php'; ?>

        </main>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/pagination.js"></script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/biometric-enrollment.js"></script>
</body>
</html>
