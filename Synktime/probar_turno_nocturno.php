<?php
// Simular llamada a la API con los datos de prueba
$empleado = 1231;
$fecha = '2025-09-18';

echo "=== Probando API de horas trabajadas con turno nocturno ===\n";
echo "Empleado: $empleado\n";
echo "Fecha: $fecha\n";
echo "Datos esperados: Entrada 20:15, Salida 00:30, ES_TURNO_NOCTURNO='S'\n\n";

// Simular entorno HTTP
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';

// Incluir el archivo de la API
ob_start();
$_GET['id_empleado'] = $empleado;
$_GET['fecha_inicio'] = $fecha;
$_GET['fecha_fin'] = $fecha;
include 'api/horas-trabajadas/get-horas.php';
$api_result = ob_get_clean();

echo "=== Resultado de la API ===\n";
echo $api_result;

// Intentar decodificar JSON para ver si hay errores
$json_data = json_decode($api_result, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "\n❌ Error JSON: " . json_last_error_msg() . "\n";
    echo "Salida raw de la API:\n";
    echo $api_result;
} else {
    echo "\n✅ JSON válido\n";
    echo "Datos procesados:\n";
    print_r($json_data);
}
?>