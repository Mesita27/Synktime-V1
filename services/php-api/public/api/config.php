<?php
// api/config.php - Configuración de base de datos para APIs usando variables de entorno/docker

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = synktime_get_pdo();
    $conn = $pdo; // Compatibilidad legacy
} catch (Throwable $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ]);
    exit;
}
?>
