<?php
/**
 * Verificador de tablas biométricas
 * Este script verifica que existan las tablas necesarias para el sistema biométrico
 * y las crea si no existen
 */

// Cabeceras necesarias
header("Content-Type: application/json");

// Conexión a la base de datos
require_once '../../config/database.php';

try {
    // Comprobar si existe la tabla de biometría
    $checkTableQuery = "SHOW TABLES LIKE 'employee_biometrics'";
    $stmt = $pdo->query($checkTableQuery);
    $tableExists = ($stmt->rowCount() > 0);
    
    // Si la tabla no existe, crearla
    if (!$tableExists) {
        $createTableSQL = "CREATE TABLE `employee_biometrics` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `biometric_type` varchar(50) NOT NULL COMMENT 'face, fingerprint, etc',
            `biometric_data` longtext NOT NULL COMMENT 'JSON encoded data',
            `additional_info` text DEFAULT NULL COMMENT 'JSON encoded additional info',
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `employee_id` (`employee_id`),
            KEY `biometric_type` (`biometric_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($createTableSQL);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tabla de biometría creada correctamente',
            'created' => true
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'La tabla de biometría ya existe',
            'created' => false
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar/crear tablas: ' . $e->getMessage()
    ]);
}
?>
