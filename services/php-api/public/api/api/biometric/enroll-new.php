<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

/**
 * API DE ENROLAMIENTO BIOMÉTRICO AVANZADO
 * Soporta: Facial (TensorFlow), Huellas dactilares, Múltiples métodos
 */

try {
    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }
    
    // Validar datos requeridos
    $employee_id = $input['employee_id'] ?? null;
    $biometric_type = $input['biometric_type'] ?? null;
    
    if (!$employee_id || !$biometric_type) {
        throw new Exception('employee_id y biometric_type son requeridos');
    }
    
    // Validar que el empleado existe
    $stmt = $pdo->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO, ESTADO 
        FROM empleado 
        WHERE ID_EMPLEADO = ? AND ESTADO = 'A'
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Procesar según tipo biométrico
    switch ($biometric_type) {
        case 'facial':
            $result = enrollFacialData($pdo, $employee_id, $input);
            break;
            
        case 'fingerprint':
            $result = enrollFingerprintData($pdo, $employee_id, $input);
            break;
            
        default:
            throw new Exception('Tipo biométrico no soportado: ' . $biometric_type);
    }
    
    // Registrar en logs
    logBiometricAction($pdo, $employee_id, 'ENROLLMENT', $biometric_type, $result['success']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * ENROLAR DATOS FACIALES (TENSORFLOW)
 */
function enrollFacialData($pdo, $employee_id, $input) {
    $descriptor = $input['descriptor'] ?? null;
    $model_version = $input['model_version'] ?? 'tensorflow_v3';
    
    if (!$descriptor || !is_array($descriptor)) {
        throw new Exception('Descriptor facial inválido');
    }
    
    // Validar longitud del descriptor
    if (count($descriptor) < 100 || count($descriptor) > 1000) {
        throw new Exception('Longitud de descriptor facial inválida');
    }
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Desactivar enrolamientos faciales anteriores
        $stmt = $pdo->prepare("
            UPDATE biometric_data 
            SET ACTIVO = 0, UPDATED_AT = NOW()
            WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'face'
        ");
        $stmt->execute([$employee_id]);
        
        // Insertar nuevo enrolamiento facial
        $biometric_data = json_encode([
            'descriptor' => $descriptor,
            'model_version' => $model_version,
            'descriptor_length' => count($descriptor),
            'enrollment_date' => date('Y-m-d H:i:s'),
            'quality_score' => calculateDescriptorQuality($descriptor)
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO biometric_data 
            (ID_EMPLEADO, BIOMETRIC_TYPE, BIOMETRIC_DATA, CREATED_AT, ACTIVO) 
            VALUES (?, 'face', ?, NOW(), 1)
        ");
        $stmt->execute([$employee_id, $biometric_data]);
        
        $biometric_id = $pdo->lastInsertId();
        
        // Confirmar transacción
        $pdo->commit();
        
        return [
            'success' => true,
            'biometric_id' => $biometric_id,
            'employee_id' => $employee_id,
            'type' => 'facial',
            'model_version' => $model_version,
            'descriptor_length' => count($descriptor),
            'message' => 'Enrolamiento facial completado correctamente'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error en enrolamiento facial: ' . $e->getMessage());
    }
}

/**
 * ENROLAR DATOS DE HUELLA DACTILAR
 */
function enrollFingerprintData($pdo, $employee_id, $input) {
    $finger_type = $input['finger_type'] ?? 'index_right';
    $biometric_data = $input['biometric_data'] ?? null;
    $quality = $input['quality'] ?? 0.7;
    
    if (!$biometric_data) {
        throw new Exception('Datos de huella dactilar requeridos');
    }
    
    // Validar tipo de dedo
    $valid_fingers = [
        'thumb_right', 'index_right', 'middle_right', 'ring_right', 'pinky_right',
        'thumb_left', 'index_left', 'middle_left', 'ring_left', 'pinky_left'
    ];
    
    if (!in_array($finger_type, $valid_fingers)) {
        throw new Exception('Tipo de dedo inválido');
    }
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Desactivar enrolamientos de huella anteriores para el mismo dedo
        $stmt = $pdo->prepare("
            UPDATE biometric_data 
            SET ACTIVO = 0, UPDATED_AT = NOW()
            WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'fingerprint' AND FINGER_TYPE = ?
        ");
        $stmt->execute([$employee_id, $finger_type]);
        
        // Insertar nuevo enrolamiento de huella
        $fingerprint_data = json_encode([
            'template' => $biometric_data,
            'finger_type' => $finger_type,
            'quality' => $quality,
            'enrollment_date' => date('Y-m-d H:i:s'),
            'template_size' => strlen($biometric_data)
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO biometric_data 
            (ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, CREATED_AT, ACTIVO) 
            VALUES (?, 'fingerprint', ?, ?, NOW(), 1)
        ");
        $stmt->execute([$employee_id, $finger_type, $fingerprint_data]);
        
        $biometric_id = $pdo->lastInsertId();
        
        // Confirmar transacción
        $pdo->commit();
        
        return [
            'success' => true,
            'biometric_id' => $biometric_id,
            'employee_id' => $employee_id,
            'type' => 'fingerprint',
            'finger_type' => $finger_type,
            'quality' => $quality,
            'message' => 'Enrolamiento de huella completado correctamente'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error en enrolamiento de huella: ' . $e->getMessage());
    }
}

/**
 * CALCULAR CALIDAD DEL DESCRIPTOR FACIAL
 */
function calculateDescriptorQuality($descriptor) {
    if (!is_array($descriptor) || empty($descriptor)) return 0;
    
    // Calcular varianza del descriptor (mayor varianza = mejor calidad)
    $mean = array_sum($descriptor) / count($descriptor);
    $variance = 0;
    
    foreach ($descriptor as $value) {
        $variance += pow($value - $mean, 2);
    }
    $variance /= count($descriptor);
    
    // Normalizar a escala 0-1
    $quality = min(1.0, $variance / 0.1);
    
    return round($quality, 3);
}

/**
 * REGISTRAR ACCIÓN BIOMÉTRICA EN LOGS
 */
function logBiometricAction($pdo, $employee_id, $action, $type, $success) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, ACTION_TYPE, BIOMETRIC_TYPE, SUCCESS, IP_ADDRESS, USER_AGENT, CREATED_AT) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $employee_id,
            $action,
            $type,
            $success ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        // No fallar si no se puede registrar el log
        error_log("Error logging biometric action: " . $e->getMessage());
    }
}
?>
