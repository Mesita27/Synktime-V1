<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Intentar incluir el archivo de configuración
    require_once '../config/database.php';

    // Verificar si la conexión existe
    if (!isset($pdo)) {
        echo json_encode([
            'success' => false,
            'error' => 'Variable $pdo no está definida',
            'message' => 'El archivo database.php no está configurando correctamente la conexión PDO'
        ]);
        exit;
    }

    // Probar una consulta simple
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Conexión a base de datos exitosa',
        'test_result' => $result,
        'pdo_status' => 'Conectado'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}
?>
