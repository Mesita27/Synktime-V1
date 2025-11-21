<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../php_integration/BiometricServiceClient.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Enhanced Biometric Enrollment API
 * Integrates with Python microservice for advanced biometric processing
 */

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }
    
    // Validate required fields
    $requiredFields = ['employee_id', 'biometric_type', 'biometric_data'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo requerido: $field");
        }
    }
    
    $employeeId = (int)$input['employee_id'];
    $biometricType = $input['biometric_type'];
    $biometricData = $input['biometric_data'];
    $fingerType = $input['finger_type'] ?? null;
    $qualityThreshold = (float)($input['quality_threshold'] ?? 0.5);
    
    // Validate employee exists
    $stmt = $pdo->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO 
        FROM empleado 
        WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'
    ");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Initialize biometric service client
    $biometricClient = BiometricServiceManager::getInstance()->getClient();
    $pythonServiceAvailable = $biometricClient->isServiceAvailable();
    
    $result = null;
    
    if ($pythonServiceAvailable) {
        // Use Python microservice for enrollment
        switch ($biometricType) {
            case 'facial':
                $result = $biometricClient->enrollFacial(
                    $employeeId,
                    BiometricServiceClient::extractBase64FromDataUrl($biometricData),
                    $qualityThreshold
                );
                break;
                
            case 'fingerprint':
                if (empty($fingerType)) {
                    throw new Exception('finger_type requerido para huellas dactilares');
                }
                $result = $biometricClient->enrollFingerprint($employeeId, $fingerType);
                break;
                
            case 'rfid':
                $timeout = (int)($input['timeout'] ?? 10);
                $result = $biometricClient->enrollRFID($employeeId, null, $timeout);
                break;
                
            default:
                throw new Exception('Tipo biométrico no soportado');
        }
        
        if (!$result) {
            throw new Exception('Error de comunicación con el servicio Python: ' . $biometricClient->getLastError());
        }
        
        // Store result in database if successful
        if ($result['success']) {
            $biometricClient->storeBiometricResult($pdo, $result, 'enrollment');
        }
        
    } else {
        // Fallback to legacy PHP implementation
        $result = enrollWithLegacyMethod($pdo, $employeeId, $biometricType, $biometricData, $fingerType);
    }
    
    // Log enrollment attempt
    logBiometricAction($pdo, $employeeId, 'enrollment', $biometricType, $result['success']);
    
    // Prepare response
    $response = [
        'success' => $result['success'],
        'message' => $result['message'],
        'employee' => [
            'id' => $employeeId,
            'name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO']
        ],
        'biometric_type' => $biometricType,
        'python_service_used' => $pythonServiceAvailable,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add additional data from Python service
    if ($pythonServiceAvailable && $result['success']) {
        $response['quality_score'] = $result['quality_score'] ?? null;
        $response['template_id'] = $result['template_id'] ?? null;
        $response['device_id'] = $result['device_id'] ?? null;
        $response['processing_time_ms'] = $result['processing_time_ms'] ?? null;
    }
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error if we have employee_id
    if (isset($employeeId)) {
        logBiometricAction($pdo, $employeeId, 'enrollment_error', $biometricType ?? 'unknown', false);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Legacy enrollment method (fallback when Python service unavailable)
 */
function enrollWithLegacyMethod($pdo, $employeeId, $biometricType, $biometricData, $fingerType = null) {
    try {
        $pdo->beginTransaction();
        
        // Process biometric data based on type
        $processedData = null;
        $qualityScore = 0.8; // Default quality for legacy method
        
        switch ($biometricType) {
            case 'facial':
                // Simple facial data processing (legacy)
                $processedData = json_encode([
                    'type' => 'facial_legacy',
                    'data' => substr($biometricData, 0, 1000), // Truncate for storage
                    'processed_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'fingerprint':
                // Simple fingerprint data processing (legacy)
                $processedData = json_encode([
                    'type' => 'fingerprint_legacy',
                    'finger_type' => $fingerType,
                    'data' => substr($biometricData, 0, 1000),
                    'processed_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            default:
                throw new Exception('Tipo biométrico no soportado en modo legacy');
        }
        
        // Check if biometric data already exists
        $stmt = $pdo->prepare("
            SELECT ID FROM biometric_data 
            WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? 
            AND (FINGER_TYPE = ? OR FINGER_TYPE IS NULL) 
            AND ACTIVO = 1
        ");
        $stmt->execute([$employeeId, $biometricType, $fingerType]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE biometric_data 
                SET BIOMETRIC_DATA = ?, 
                    QUALITY_SCORE = ?,
                    TEMPLATE_VERSION = '1.0',
                    UPDATED_AT = NOW()
                WHERE ID = ?
            ");
            $stmt->execute([$processedData, $qualityScore, $existing['ID']]);
            $message = 'Datos biométricos actualizados correctamente';
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO biometric_data 
                (ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, 
                 QUALITY_SCORE, TEMPLATE_VERSION, CREATED_AT, ACTIVO)
                VALUES (?, ?, ?, ?, ?, '1.0', NOW(), 1)
            ");
            $stmt->execute([$employeeId, $biometricType, $fingerType, $processedData, $qualityScore]);
            $message = 'Enrolamiento biométrico completado correctamente';
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => $message . ' (modo legacy)',
            'quality_score' => $qualityScore
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error en enrolamiento legacy: ' . $e->getMessage());
    }
}

/**
 * Log biometric action
 */
function logBiometricAction($pdo, $employeeId, $action, $biometricType, $success) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, 
             API_SOURCE, OPERATION_TYPE, FECHA, HORA, CREATED_AT)
            VALUES (?, ?, ?, 'enhanced_enrollment_api', ?, CURDATE(), CURTIME(), NOW())
        ");
        
        $stmt->execute([
            $employeeId,
            $biometricType,
            $success ? 1 : 0,
            $action
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging biometric action: " . $e->getMessage());
    }
}
?>