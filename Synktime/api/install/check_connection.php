<?php
/**
 * Verificador de conexión a la base de datos
 */
header('Content-Type: application/json');

try {
    // Intentar conectar a la base de datos
    require_once '../../config/database.php';
    
    // Si llegamos aquí, la conexión fue exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa a la base de datos'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>
