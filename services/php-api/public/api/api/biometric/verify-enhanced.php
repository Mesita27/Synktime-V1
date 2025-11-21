<?php
// Limpiar cualquier output anterior
ob_clean();

require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../php_integration/BiometricServiceClient.php';

// Headers para JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Enhanced Biometric Verification API
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
    $confidenceThreshold = (float)($input['confidence_threshold'] ?? 0.6);
    $uid = $input['uid'] ?? null; // For RFID verification
    
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
    
    // Check if employee has enrolled biometric data
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as enrolled
        FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1
    ");
    $stmt->execute([$employeeId, $biometricType]);
    $enrolled = $stmt->fetch()['enrolled'] > 0;
    
    if (!$enrolled) {
        throw new Exception('No hay datos biométricos enrollados para este empleado');
    }
    
    // Initialize biometric service client
    $biometricClient = BiometricServiceManager::getInstance()->getClient();
    $pythonServiceAvailable = $biometricClient->isServiceAvailable();
    
    $result = null;
    
    if ($pythonServiceAvailable) {
        // Use Python microservice for verification
        switch ($biometricType) {
            case 'facial':
                $result = $biometricClient->verifyFacial(
                    $employeeId,
                    BiometricServiceClient::extractBase64FromDataUrl($biometricData),
                    $confidenceThreshold
                );
                break;
                
            case 'fingerprint':
                $result = $biometricClient->verifyFingerprint($employeeId, $fingerType);
                break;
                
            case 'rfid':
                if (empty($uid)) {
                    throw new Exception('UID requerido para verificación RFID');
                }
                $result = $biometricClient->verifyRFID($uid);
                break;
                
            default:
                throw new Exception('Tipo biométrico no soportado');
        }
        
        if (!$result) {
            throw new Exception('Error de comunicación con el servicio Python: ' . $biometricClient->getLastError());
        }
        
        // Store result in database
        $biometricClient->storeBiometricResult($pdo, $result, 'verification');
        
    } else {
        // Fallback to legacy PHP implementation
        $result = verifyWithLegacyMethod($pdo, $employeeId, $biometricType, $biometricData, $fingerType);
    }
    
    // Log verification attempt
    logBiometricAction($pdo, $employeeId, 'verification', $biometricType, $result['success']);
    
    // Process attendance if verification successful
    $attendanceProcessed = false;
    if ($result['success']) {
        $attendanceProcessed = processAttendance($pdo, $employeeId, $biometricType);
    }
    
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
        'attendance_processed' => $attendanceProcessed,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add additional data from Python service
    if ($pythonServiceAvailable && isset($result['confidence'])) {
        $response['confidence'] = $result['confidence'];
        $response['quality_score'] = $result['quality_score'] ?? null;
        $response['device_id'] = $result['device_id'] ?? null;
        $response['processing_time_ms'] = $result['processing_time_ms'] ?? null;
    }
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error if we have employee_id
    if (isset($employeeId)) {
        logBiometricAction($pdo, $employeeId, 'verification_error', $biometricType ?? 'unknown', false);
    }
    
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Legacy verification method (fallback when Python service unavailable)
 */
function verifyWithLegacyMethod($pdo, $employeeId, $biometricType, $biometricData, $fingerType = null) {
    try {
        // Get stored biometric data
        $stmt = $pdo->prepare("
            SELECT BIOMETRIC_DATA, QUALITY_SCORE
            FROM biometric_data 
            WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? 
            AND (FINGER_TYPE = ? OR FINGER_TYPE IS NULL) 
            AND ACTIVO = 1
            ORDER BY CREATED_AT DESC
            LIMIT 1
        ");
        $stmt->execute([$employeeId, $biometricType, $fingerType]);
        $stored = $stmt->fetch();
        
        if (!$stored) {
            return [
                'success' => false,
                'message' => 'No hay datos biométricos para comparar'
            ];
        }
        
        // Simple similarity calculation (legacy method)
        $similarity = calculateLegacySimilarity($biometricData, $stored['BIOMETRIC_DATA']);
        $threshold = 0.7; // Legacy threshold
        
        $success = $similarity >= $threshold;
        
        return [
            'success' => $success,
            'message' => $success ? 
                'Verificación exitosa (modo legacy)' : 
                'Verificación fallida (modo legacy)',
            'confidence' => $similarity,
            'quality_score' => $stored['QUALITY_SCORE']
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error en verificación legacy: ' . $e->getMessage()
        ];
    }
}

/**
 * Simple similarity calculation for legacy method
 */
function calculateLegacySimilarity($data1, $data2) {
    // Very basic similarity calculation
    // In a real implementation, this would be much more sophisticated
    
    $hash1 = md5($data1);
    $hash2 = md5($data2);
    
    // Count matching characters
    $matches = 0;
    $total = min(strlen($hash1), strlen($hash2));
    
    for ($i = 0; $i < $total; $i++) {
        if ($hash1[$i] === $hash2[$i]) {
            $matches++;
        }
    }
    
    return $total > 0 ? $matches / $total : 0;
}

/**
 * Process attendance record
 */
function processAttendance($pdo, $employeeId, $verificationType) {
    try {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        // Check if there's already an entry for today
        $stmt = $pdo->prepare("
            SELECT ID_ASISTENCIA, HORA_ENTRADA, HORA_SALIDA
            FROM asistencia 
            WHERE ID_EMPLEADO = ? AND FECHA = ?
        ");
        $stmt->execute([$employeeId, $today]);
        $existingAttendance = $stmt->fetch();
        
        if ($existingAttendance) {
            // Update exit time if entry exists and no exit time recorded
            if (empty($existingAttendance['HORA_SALIDA'])) {
                $stmt = $pdo->prepare("
                    UPDATE asistencia 
                    SET HORA_SALIDA = ?, 
                        VERIFICATION_METHOD = ?,
                        UPDATED_AT = NOW()
                    WHERE ID_ASISTENCIA = ?
                ");
                $stmt->execute([$currentTime, $verificationType, $existingAttendance['ID_ASISTENCIA']]);
                return true;
            }
        } else {
            // Create new attendance record
            $stmt = $pdo->prepare("
                INSERT INTO asistencia 
                (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TARDANZA, REGISTRO_MANUAL, ID_HORARIO, CREATED_AT)
                VALUES (?, ?, 'ENTRADA', ?, ?, 'N', 'N', NULL, NOW())
            ");
            $stmt->execute([$employeeId, $today, $currentTime, $verificationType]);
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error processing attendance: " . $e->getMessage());
        return false;
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
            VALUES (?, ?, ?, 'enhanced_verification_api', ?, CURDATE(), CURTIME(), NOW())
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