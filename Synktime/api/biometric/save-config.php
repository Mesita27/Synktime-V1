<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../auth/session.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['provider']) || !isset($input['config'])) {
        throw new Exception('Datos incompletos');
    }
    
    $provider = $input['provider'];
    $config = $input['config'];
    
    // Validar provider
    $validProviders = ['face-api', 'mediapipe', 'opencv', 'azure', 'aws'];
    if (!in_array($provider, $validProviders)) {
        throw new Exception('Proveedor no válido');
    }
    
    // Crear tabla de configuración si no existe
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS biometric_api_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(50) NOT NULL UNIQUE,
            config JSON NOT NULL,
            enabled BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $pdo->exec($createTableSQL);
    
    // Insertar o actualizar configuración
    $stmt = $pdo->prepare("
        INSERT INTO biometric_api_config (provider, config, enabled) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        config = VALUES(config), 
        enabled = VALUES(enabled),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $configJson = json_encode($config);
    $enabled = isset($config['enabled']) ? $config['enabled'] : true;
    
    $stmt->execute([$provider, $configJson, $enabled]);
    
    // Log de configuración
    $logStmt = $pdo->prepare("
        INSERT INTO biometric_logs (employee_id, action, details, created_at) 
        VALUES (?, 'config_update', ?, NOW())
    ");
    
    $logDetails = json_encode([
        'provider' => $provider,
        'action' => 'configuration_saved',
        'user_id' => $_SESSION['user_id']
    ]);
    
    $logStmt->execute([null, $logDetails]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración guardada exitosamente',
        'provider' => $provider
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
