<?php
/**
 * Creador de tablas biométricas
 */
header('Content-Type: application/json');

try {
    // Conexión a la base de datos
    require_once '../../config/database.php';
    
    // SQL para crear la tabla biometric_data
    $createBiometricDataSQL = "CREATE TABLE IF NOT EXISTS `biometric_data` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `ID_EMPLEADO` int(11) NOT NULL,
        `BIOMETRIC_TYPE` varchar(20) NOT NULL COMMENT 'facial, fingerprint, etc', 
        `BIOMETRIC_DATA` longtext COMMENT 'JSON encoded data',
        `FINGER_TYPE` varchar(20) DEFAULT NULL COMMENT 'Para datos de huellas',
        `CONFIDENCE_THRESHOLD` float DEFAULT 0.9,
        `QUALITY_SCORE` float DEFAULT 0,
        `CREATED_AT` datetime DEFAULT CURRENT_TIMESTAMP,
        `UPDATED_AT` datetime DEFAULT CURRENT_TIMESTAMP,
        `ACTIVO` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`ID`),
        KEY `ID_EMPLEADO` (`ID_EMPLEADO`),
        KEY `BIOMETRIC_TYPE` (`BIOMETRIC_TYPE`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // SQL para crear la tabla biometric_logs
    $createBiometricLogsSQL = "CREATE TABLE IF NOT EXISTS `biometric_logs` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `ID_EMPLEADO` int(11) DEFAULT NULL,
        `VERIFICATION_METHOD` varchar(20) DEFAULT NULL,
        `OPERATION_TYPE` varchar(20) DEFAULT NULL,
        `VERIFICATION_SUCCESS` tinyint(1) DEFAULT 0,
        `CONFIDENCE_SCORE` float DEFAULT NULL,
        `ATTEMPT_DATA` json DEFAULT NULL,
        `DEVICE_INFO` varchar(255) DEFAULT NULL,
        `IP_ADDRESS` varchar(45) DEFAULT NULL,
        `CREATED_AT` datetime DEFAULT CURRENT_TIMESTAMP,
        `NOTES` text DEFAULT NULL,
        PRIMARY KEY (`ID`),
        KEY `ID_EMPLEADO` (`ID_EMPLEADO`),
        KEY `VERIFICATION_METHOD` (`VERIFICATION_METHOD`),
        KEY `CREATED_AT` (`CREATED_AT`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // Ejecutar SQL para ambas tablas
    $pdo->exec($createBiometricDataSQL);
    $pdo->exec($createBiometricLogsSQL);
    
    // Verificar si las tablas existen
    $checkDataTableQuery = "SHOW TABLES LIKE 'biometric_data'";
    $stmtData = $pdo->query($checkDataTableQuery);
    $dataTableExists = ($stmtData->rowCount() > 0);
    
    $checkLogsTableQuery = "SHOW TABLES LIKE 'biometric_logs'";
    $stmtLogs = $pdo->query($checkLogsTableQuery);
    $logsTableExists = ($stmtLogs->rowCount() > 0);
    
    if ($dataTableExists && $logsTableExists) {
        echo json_encode([
            'success' => true,
            'message' => 'Tablas biométricas creadas/verificadas correctamente',
            'tables' => [
                'biometric_data' => true,
                'biometric_logs' => true
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudieron crear todas las tablas requeridas',
            'tables' => [
                'biometric_data' => $dataTableExists,
                'biometric_logs' => $logsTableExists
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear tablas: ' . $e->getMessage(),
        'created' => false
    ]);
}
?>
