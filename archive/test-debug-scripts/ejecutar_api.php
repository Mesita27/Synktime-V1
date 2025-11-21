<?php
// Simular una llamada GET al API
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id_empleado'] = 100;
$_GET['fecha_inicio'] = '2025-09-28';
$_GET['fecha_fin'] = '2025-09-28';

// Ejecutar el API directamente
ob_start();
include 'api/horas-trabajadas/get-horas.php';
$output = ob_get_clean();

// Intentar parsear como JSON
$json = json_decode($output, true);
if ($json !== null) {
    echo "RESPUESTA DEL API (JSON parseado):\n";
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "RESPUESTA DEL API (texto plano):\n";
    echo $output;
}
?>