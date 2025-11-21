<?php
error_reporting(0); // Suprimir warnings para obtener JSON limpio

// Simular el entorno de POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['empleados'] = [910];
$_POST['fechaDesde'] = '2025-09-19';
$_POST['fechaHasta'] = '2025-09-19';

// Capturar la salida
ob_start();
include('api/horas-trabajadas/get-horas.php');
$response = ob_get_clean();

// Extraer solo el JSON (después de las líneas de warning)
$json_start = strpos($response, '{');
if ($json_start !== false) {
    $json_response = substr($response, $json_start);
    echo $json_response;
} else {
    echo $response;
}
?>