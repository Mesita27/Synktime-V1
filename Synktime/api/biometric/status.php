<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }
    
    $employee_id = $_GET['employee_id'] ?? null;
    
    if (!$employee_id) {
        throw new Exception('ID de empleado requerido');
    }
    
    // Verificar que el empleado existe
    $stmt = $pdo->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO 
        FROM empleado 
        WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Obtener datos biométricos registrados desde employee_biometrics
    $stmt = $pdo->prepare("
        SELECT
            id,
            biometric_type,
            biometric_data,
            additional_info,
            created_at,
            updated_at
        FROM employee_biometrics
        WHERE employee_id = ?
        ORDER BY biometric_type, created_at DESC
    ");
    $stmt->execute([$employee_id]);
    $biometric_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar datos por tipo
    $organized_data = [
        'fingerprint' => [],
        'facial' => []
    ];

    foreach ($biometric_data as $data) {
        $type = $data['biometric_type'];

        // Parse biometric_data JSON to get additional info
        $biometric_info = json_decode($data['biometric_data'], true);
        $additional_info = json_decode($data['additional_info'], true);

        if ($type === 'fingerprint') {
            // For fingerprints, we might need to extract finger type from additional_info
            $finger_type = $additional_info['finger_type'] ?? 'unknown';
            $organized_data['fingerprint'][$finger_type] = [
                'id' => $data['id'],
                'finger_type' => $finger_type,
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
                'active' => true, // employee_biometrics table doesn't have ACTIVO field
                'device_id' => $additional_info['device_id'] ?? 'unknown'
            ];
        } elseif ($type === 'face' || $type === 'facial') {
            $organized_data['facial'][] = [
                'id' => $data['id'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
                'active' => true, // employee_biometrics table doesn't have ACTIVO field
                'device_id' => $additional_info['device_id'] ?? 'unknown',
                'template_id' => $biometric_info['template_id'] ?? 'unknown'
            ];
        }
    }
    
    // Obtener estadísticas de logs biométricos
    $stmt = $pdo->prepare("
        SELECT 
            VERIFICATION_METHOD,
            OPERATION_TYPE,
            COUNT(*) as total_attempts,
            SUM(VERIFICATION_SUCCESS) as successful_attempts,
            AVG(CONFIDENCE_SCORE) as avg_confidence,
            MAX(CREATED_AT) as last_attempt
        FROM biometric_logs 
        WHERE ID_EMPLEADO = ? 
        AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY VERIFICATION_METHOD, OPERATION_TYPE
    ");
    $stmt->execute([$employee_id]);
    $logs_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas de completitud
    $fingerprint_fingers = ['thumb_right', 'index_right', 'middle_right', 'ring_right', 'pinky_right'];
    $enrolled_fingers = array_keys($organized_data['fingerprint']);
    $fingerprint_completion = count($enrolled_fingers) / count($fingerprint_fingers) * 100;
    
    $facial_completion = count($organized_data['facial']) > 0 ? 100 : 0;
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'employee' => [
            'id' => $employee['ID_EMPLEADO'],
            'name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO']
        ],
        'biometric_data' => $organized_data,
        'completion_stats' => [
            'fingerprint' => [
                'enrolled_fingers' => count($enrolled_fingers),
                'total_fingers' => count($fingerprint_fingers),
                'completion_percentage' => round($fingerprint_completion, 2),
                'available_fingers' => $fingerprint_fingers,
                'enrolled_finger_types' => $enrolled_fingers
            ],
            'facial' => [
                'enrolled_samples' => count($organized_data['facial']),
                'completion_percentage' => $facial_completion
            ]
        ],
        'logs_statistics' => []
    ];
    
    // Procesar estadísticas de logs
    foreach ($logs_stats as $stat) {
        $key = $stat['VERIFICATION_METHOD'] . '_' . $stat['OPERATION_TYPE'];
        $response['logs_statistics'][$key] = [
            'method' => $stat['VERIFICATION_METHOD'],
            'operation' => $stat['OPERATION_TYPE'],
            'total_attempts' => (int)$stat['total_attempts'],
            'successful_attempts' => (int)$stat['successful_attempts'],
            'success_rate' => $stat['total_attempts'] > 0 ? 
                round(($stat['successful_attempts'] / $stat['total_attempts']) * 100, 2) : 0,
            'average_confidence' => $stat['avg_confidence'] ? round($stat['avg_confidence'], 4) : null,
            'last_attempt' => $stat['last_attempt']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
