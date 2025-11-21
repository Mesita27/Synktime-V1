<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración por defecto para las APIs gratuitas
$defaultConfigs = [
    [
        'provider' => 'face-api',
        'enabled' => true,
        'config' => json_encode([
            'threshold' => 0.7,
            'models_path' => 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights',
            'status' => 'ready'
        ])
    ],
    [
        'provider' => 'mediapipe',
        'enabled' => true,
        'config' => json_encode([
            'threshold' => 0.75,
            'max_faces' => 1,
            'min_detection_confidence' => 0.5,
            'status' => 'ready'
        ])
    ],
    [
        'provider' => 'opencv',
        'enabled' => true,
        'config' => json_encode([
            'threshold' => 0.70,
            'cascade_models' => ['haarcascade_frontalface_default.xml'],
            'status' => 'ready'
        ])
    ],
    [
        'provider' => 'tensorflow',
        'enabled' => true,
        'config' => json_encode([
            'threshold' => 0.8,
            'models' => ['facemesh', 'handpose'],
            'status' => 'ready'
        ])
    ]
];

try {
    // Si hay base de datos disponible, intentar cargar desde allí
    if (file_exists('../../config/database.php')) {
        require_once '../../config/database.php';
        
        if (isset($pdo)) {
            $stmt = $pdo->prepare("
                SELECT provider, config, enabled, updated_at 
                FROM biometric_api_config 
                ORDER BY updated_at DESC
            ");
            
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($configs)) {
                echo json_encode([
                    'success' => true,
                    'configs' => $configs,
                    'source' => 'database'
                ]);
                exit;
            }
        }
    }
    
    // Fallback a configuración por defecto
    echo json_encode([
        'success' => true,
        'configs' => $defaultConfigs,
        'source' => 'default'
    ]);

} catch (Exception $e) {
    // En caso de error, devolver configuración por defecto
    echo json_encode([
        'success' => true,
        'configs' => $defaultConfigs,
        'source' => 'fallback',
        'note' => 'Using default configuration due to: ' . $e->getMessage()
    ]);
}
?>
