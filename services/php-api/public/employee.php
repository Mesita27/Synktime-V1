<?php
require_once __DIR__ . '/auth/session.php';
requireModuleAccess('empleados'); // Verificar permisos para módulo de empleados
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empleados | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/pagination.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/employee.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="employee-header">
                <h2 class="page-title"><i class="fas fa-users"></i> Empleados</h2>
                <div class="employee-actions">
                    <button class="btn-primary" id="btnAddEmployee"><i class="fas fa-user-plus"></i> Registrar empleado</button>
                    <button class="btn-secondary" id="btnExportXLS"><i class="fas fa-file-excel"></i> Exportar .xlsx</button>
                    <button class="btn-primary" onclick="showEmployeeHelpModal()"><i class="fas fa-question-circle"></i> ¿Cómo usar?</button>
                </div>
            </div>
            <?php include 'components/employee_query.php'; ?>
            <div class="employee-table-container">
                <table class="employee-table" id="employeeTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Identificación</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Establecimiento</th>
                            <th>Sede</th>
                            <th>Fecha contratación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <!-- El JS llenará dinámicamente las filas aquí -->
                    </tbody>
                </table>
            </div>
            <?php include 'components/employee_modals.php'; ?>
        </main>
    </div>
</div>
<script src="assets/js/layout.js"></script>
<script src="assets/js/query.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/employee.js"></script>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
</body>
</html>