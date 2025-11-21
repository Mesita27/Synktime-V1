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
 * API DE ENROLAMIENTO BIOMÉTRICO
 * Soporta: Facial y huellas dactilares
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
    
    // Validar campos requeridos
    $required_fields = ['employee_id', 'biometric_type', 'biometric_data'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Campo requerido: $field");
        }
    }
    
    $employee_id = (int)$input['employee_id'];
    $biometric_type = $input['biometric_type']; // 'face' o 'fingerprint'
    $biometric_data = $input['biometric_data'];
    $finger_type = $input['finger_type'] ?? null; // Solo para huellas
    
    // Validar tipo biométrico
    if (!in_array($biometric_type, ['face', 'fingerprint'])) {
        throw new Exception('Tipo biométrico no válido');
    }
    
    // Validar empleado existe
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, CODIGO, NOMBRES, APELLIDOS FROM empleado WHERE ID_EMPLEADO = ? AND ACTIVO = 'Y'");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Procesar datos biométricos según el tipo
    $processed_data = processBiometricData($biometric_type, $biometric_data);
    
    // Verificar si ya existe un registro de este tipo
    $check_query = "SELECT ID FROM biometric_data WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ?";
    $check_params = [$employee_id, $biometric_type];
    
    if ($biometric_type === 'fingerprint' && $finger_type) {
        $check_query .= " AND FINGER_TYPE = ?";
        $check_params[] = $finger_type;
    }
    
    $stmt = $conn->prepare($check_query);
    $stmt->execute($check_params);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Actualizar registro existente
        $update_query = "UPDATE biometric_data SET 
                        BIOMETRIC_DATA = ?, 
                        UPDATED_AT = NOW(),
                        ACTIVO = 'S'
                        WHERE ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->execute([$processed_data, $existing['ID']]);
        
        $action = 'update';
    } else {
        // Crear nuevo registro
        $insert_query = "INSERT INTO biometric_data 
                        (ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, CREATED_AT, UPDATED_AT, ACTIVO) 
                        VALUES (?, ?, ?, ?, NOW(), NOW(), 1)";
        $stmt = $conn->prepare($insert_query);
        $stmt->execute([$employee_id, $biometric_type, $finger_type, $processed_data]);
        
        $action = 'enroll';
    }
    
    // Registrar log de la acción
    logBiometricAction($conn, $employee_id, $action, $biometric_type, true);
    
    // Obtener estadísticas actualizadas del empleado
    $stats_query = "SELECT 
                    BIOMETRIC_TYPE, 
                    COUNT(*) as total,
                    MAX(UPDATED_AT) as last_update
                    FROM biometric_data 
                    WHERE ID_EMPLEADO = ? AND ACTIVO = 'S' 
                    GROUP BY BIOMETRIC_TYPE";
    $stmt = $conn->prepare($stats_query);
    $stmt->execute([$employee_id]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear estadísticas
    $enrollment_stats = [
        'facial' => false,
        'fingerprint' => false,
        'last_update' => null
    ];
    
    foreach ($stats as $stat) {
        $enrollment_stats[$stat['BIOMETRIC_TYPE']] = true;
        if (!$enrollment_stats['last_update'] || $stat['last_update'] > $enrollment_stats['last_update']) {
            $enrollment_stats['last_update'] = $stat['last_update'];
        }
    }
    
    // Determinar estado general
    if ($enrollment_stats['facial'] && $enrollment_stats['fingerprint']) {
        $status = 'enrolled';
    } elseif ($enrollment_stats['facial'] || $enrollment_stats['fingerprint']) {
        $status = 'partial';
    } else {
        $status = 'pending';
    }
    
    $response = [
        'success' => true,
        'message' => 'Enrolamiento completado exitosamente',
        'action' => $action,
        'employee' => [
            'id' => $employee_id,
            'code' => $employee['CODIGO'],
            'name' => $employee['NOMBRES'] . ' ' . $employee['APELLIDOS']
        ],
        'biometric_type' => $biometric_type,
        'finger_type' => $finger_type,
        'enrollment_stats' => $enrollment_stats,
        'status' => $status,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    // Registrar error en log si tenemos el employee_id
    if (isset($employee_id)) {
        logBiometricAction($conn, $employee_id, 'enroll_error', $biometric_type ?? 'unknown', false);
    }
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Procesar datos biométricos según el tipo
 */
function processBiometricData($type, $data) {
    switch ($type) {
        case 'facial':
            // Los datos faciales vienen como array de características de TensorFlow
            if (is_array($data)) {
                return json_encode($data);
            }
            // Si es base64 de imagen, extraer características básicas
            if (is_string($data) && strpos($data, 'data:image') === 0) {
                // Simular extracción de características (en producción usar un modelo real)
                $features = [];
                $image_hash = md5($data);
                
                for ($i = 0; $i < 128; $i++) {
                    $features[] = (hexdec(substr($image_hash, $i % 32, 2)) / 255) - 0.5;
                }
                
                return json_encode($features);
            }
            return is_string($data) ? $data : json_encode($data);
            
        case 'fingerprint':
            // Los datos de huella pueden ser minutiae o imagen
            if (is_array($data)) {
                return json_encode($data);
            }
            // Si es base64 de imagen, procesar
            if (is_string($data) && strpos($data, 'data:image') === 0) {
                // Simular extracción de minutiae (en producción usar algoritmo real)
                $minutiae = [];
                $image_hash = md5($data);
                
                // Generar minutiae simuladas
                for ($i = 0; $i < 50; $i++) {
                    $minutiae[] = [
                        'x' => hexdec(substr($image_hash, ($i * 2) % 32, 2)),
                        'y' => hexdec(substr($image_hash, ($i * 2 + 1) % 32, 2)),
                        'angle' => (hexdec(substr($image_hash, ($i * 3) % 32, 2)) * 360) / 255,
                        'type' => ($i % 2 === 0) ? 'ending' : 'bifurcation'
                    ];
                }
                
                return json_encode($minutiae);
            }
            return is_string($data) ? $data : json_encode($data);
            
        default:
            throw new Exception('Tipo biométrico no soportado');
    }
}

/**
 * Registrar log de acción biométrica
 */
function logBiometricAction($pdo, $employee_id, $action, $type, $success) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, OPERATION_TYPE, VERIFICATION_METHOD, VERIFICATION_SUCCESS, CREATED_AT) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $employee_id,
            $action === 'enroll' || $action === 'update' ? 'enrollment' : 'verification',
            $type,
            $success ? 1 : 0
        ]);
        
    } catch (Exception $e) {
        // No fallar si no se puede registrar el log
        error_log("Error logging biometric action: " . $e->getMessage());
    }
}
?>
