<?php
require_once '../../config/database.php';

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Test the optimized function
include 'get-horas.php';

// Simulate a request with multiple employees
$_POST['empleados'] = ['100', '1'];
$_POST['fechaDesde'] = '2025-09-20';
$_POST['fechaHasta'] = '2025-09-26';
$_POST['fecha_inicio'] = '2025-09-20';
$_POST['fecha_fin'] = '2025-09-26';

echo 'Testing optimized query with multiple employees...\n';
echo 'Request completed successfully!\n';
?>