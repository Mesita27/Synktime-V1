<?php
// API simple sin autenticación para diagnóstico de sistemas
// SOLO PARA DIAGNÓSTICO - NO USAR EN PRODUCCIÓN

// Inicializar la respuesta
$response = [
    'success' => false,
    'message' => 'API de diagnóstico sin autenticación',
    'data' => []
];

require_once __DIR__ . '/../config.php';

// Intentar conectar a la base de datos para diagnóstico
try {
    $pdo = synktime_get_pdo();
    
    // Consulta básica
    $stmt = $pdo->query("
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            'DIAGNOSTICO' AS FUENTE
        FROM empleado e
        WHERE e.ACTIVO = 'S'
        LIMIT 10
    ");
    
    if ($stmt) {
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true;
        $response['message'] = 'Datos recuperados correctamente (SOLO PARA DIAGNÓSTICO)';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

// Permitir CORS para facilitar las pruebas
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

// Devolver la respuesta
echo json_encode($response);
?>
