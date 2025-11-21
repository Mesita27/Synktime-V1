<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/facial-recognition-api.php';

header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

/**
 * API de Verificación Facial usando múltiples proveedores gratuitos
 * Soporta: face-api.js, MediaPipe, OpenCV.js
 */

// Función para registrar log biométrico
function logBiometricAttempt($pdo, $employee_id, $method, $success, $confidence, $operation_type = 'verification') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO biometric_logs 
            (employee_id, operation_type, method, success, confidence, details, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $details = json_encode([
            'method' => $method,
            'confidence_score' => $confidence,
            'verification_result' => $success ? 'success' : 'failed',
            'timestamp' => date('Y-m-d H:i:s'),
            'api_version' => '2.0'
        ]);
        
        $stmt->execute([
            $employee_id,
            $operation_type,
            $method,
            $success ? 1 : 0,
            $confidence,
            $details
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging biometric attempt: " . $e->getMessage());
        return false;
    }
}

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar campos requeridos
    $required_fields = ['employee_id', 'facial_data'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Campo requerido: $field");
        }
    }
    
    $employee_id = (int)$input['employee_id'];
    $facial_data = $input['facial_data'];
    $verification_method = $input['method'] ?? 'auto'; // auto, face-api, mediapipe, opencv
    
    // Get current user info
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        throw new Exception('Sesión inválida - empresa no encontrada');
    }
    
    // Validar empleado y que pertenezca a la empresa
    $stmt = $pdo->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO 
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 'S' AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$employee_id, $empresaId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado, inactivo o no pertenece a su empresa');
    }
    
    // Obtener datos biométricos almacenados
    $stmt = $pdo->prepare("
        SELECT biometric_data, additional_info
        FROM employee_biometrics
        WHERE employee_id = ? AND biometric_type = 'face'
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $stmt->execute([$employee_id]);
    $stored_biometric = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stored_biometric) {
        logBiometricAttempt($pdo, $employee_id, 'facial', false, 0);
        throw new Exception('No hay datos biométricos faciales registrados para este empleado');
    }
    
    // Inicializar API de reconocimiento facial
    $facialAPI = new FacialRecognitionAPI($pdo);
    
    // Verificar según el método especificado
    $verification_result = null;
    
    switch ($verification_method) {
        case 'face-api':
            $verification_result = $facialAPI->verifyWithFaceAPI(
                $stored_biometric['biometric_data'],
                $facial_data
            );
            break;
            
        case 'mediapipe':
            $verification_result = $facialAPI->verifyWithMediaPipe(
                $stored_biometric['biometric_data'],
                $facial_data
            );
            break;
            
        case 'opencv':
            $verification_result = $facialAPI->verifyWithOpenCV(
                $stored_biometric['biometric_data'],
                $facial_data
            );
            break;
            
        case 'auto':
        default:
            // Usar método automático que prueba múltiples APIs
            $verification_result = $facialAPI->verifyFace(
                $stored_biometric['biometric_data'],
                $facial_data
            );
            break;
    }
    
    // Extraer resultados
    $is_verified = $verification_result['success'];
    $confidence = $verification_result['confidence'];
    $method_used = $verification_result['method'];
    $details = $verification_result['details'] ?? [];
    
    // Registrar intento en logs
    logBiometricAttempt($pdo, $employee_id, $method_used, $is_verified, $confidence);
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'verified' => $is_verified,
        'confidence' => round($confidence, 3),
        'method' => $method_used,
        'employee' => [
            'id' => $employee['id'],
            'name' => $employee['nombre'] . ' ' . $employee['apellido']
        ],
        'verification_details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Agregar información adicional si la verificación fue exitosa
    if ($is_verified) {
        $response['message'] = 'Verificación facial exitosa';
        $response['authentication_status'] = 'approved';
        
        // Obtener estadísticas del empleado
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_attempts,
                AVG(confidence) as avg_confidence
            FROM biometric_logs 
            WHERE employee_id = ? AND method LIKE '%facial%' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAYS)
        ");
        
        $statsStmt->execute([$employee_id]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        $response['employee_stats'] = [
            'total_attempts_30d' => (int)$stats['total_attempts'],
            'success_rate_30d' => $stats['total_attempts'] > 0 ? 
                round(($stats['successful_attempts'] / $stats['total_attempts']) * 100, 2) : 0,
            'avg_confidence_30d' => round($stats['avg_confidence'], 3)
        ];
        
    } else {
        $response['message'] = 'Verificación facial fallida - Rostro no reconocido';
        $response['authentication_status'] = 'denied';
        $response['reason'] = 'La confianza de verificación (' . round($confidence, 3) . ') está por debajo del umbral requerido';
    }
    
    // Información sobre el método usado
    $method_info = [
        'face-api' => [
            'name' => 'face-api.js',
            'type' => 'Gratuito',
            'description' => 'Reconocimiento facial basado en descriptores TensorFlow'
        ],
        'mediapipe' => [
            'name' => 'MediaPipe',
            'type' => 'Gratuito (Google)',
            'description' => 'Análisis de malla facial 3D con 468 puntos de referencia'
        ],
        'opencv' => [
            'name' => 'OpenCV.js',
            'type' => 'Gratuito',
            'description' => 'Algoritmos Eigenfaces y características Haar'
        ],
        'simulation' => [
            'name' => 'Simulación',
            'type' => 'Fallback',
            'description' => 'Simulación cuando no hay APIs configuradas'
        ]
    ];
    
    $response['method_info'] = $method_info[$method_used] ?? $method_info['simulation'];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en verificación facial: " . $e->getMessage());
    
    // Si hay información del empleado, registrar intento fallido
    if (isset($employee_id)) {
        logBiometricAttempt($pdo, $employee_id, 'facial_error', false, 0);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'verified' => false,
        'message' => $e->getMessage(),
        'error_code' => 'FACIAL_VERIFICATION_ERROR',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

// Función para extraer características faciales (simulada)
function extractFacialFeatures($image_data) {
    // En un sistema real, aquí se usaría OpenCV, face-api.js, o un servicio como AWS Rekognition
    // Por ahora simulamos la extracción de características
    
    // Simular vector de características faciales
    $features = [];
    
    // Generar características simuladas basadas en la imagen
    $image_hash = md5($image_data);
    
    for ($i = 0; $i < 128; $i++) {
        // Generar valores basados en hash para consistencia
        $features[] = (hexdec(substr($image_hash, $i % 32, 2)) / 255) - 0.5;
    }
    
    return $features;
}

// Función para calcular similitud facial usando distancia euclidiana
function calculateFacialSimilarity($stored_features, $input_features) {
    if (!$stored_features || !$input_features) {
        return 0;
    }
    
    $stored_array = json_decode($stored_features, true);
    $input_array = json_decode($input_features, true);
    
    if (!is_array($stored_array) || !is_array($input_array)) {
        return 0;
    }
    
    if (count($stored_array) !== count($input_array)) {
        return 0;
    }
    
    // Calcular distancia euclidiana
    $sum_squared_diff = 0;
    for ($i = 0; $i < count($stored_array); $i++) {
        $diff = $stored_array[$i] - $input_array[$i];
        $sum_squared_diff += $diff * $diff;
    }
    
    $euclidean_distance = sqrt($sum_squared_diff);
    
    // Convertir distancia a similitud (0-1)
    // Umbral típico para reconocimiento facial es 0.6-0.8
    $max_distance = 2.0; // Ajustar según necesidades
    $similarity = max(0, 1 - ($euclidean_distance / $max_distance));
    
    return $similarity;
}

// Función para registrar log biométrico
function logBiometricAttempt($pdo, $employee_id, $method, $success, $confidence, $operation_type = 'verification') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, CONFIDENCE_SCORE, 
             API_SOURCE, OPERATION_TYPE, FECHA, HORA) 
            VALUES (?, ?, ?, ?, 'internal_api', ?, CURDATE(), CURTIME())
        ");
        
        $stmt->execute([
            $employee_id,
            $method,
            $success ? 1 : 0,
            $confidence,
            $operation_type
        ]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error logging biometric attempt: " . $e->getMessage());
        return false;
    }
}

// Función para validar formato de imagen
function validateImageFormat($image_data) {
    // Verificar que sea base64 válido
    if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $image_data)) {
        return false;
    }
    
    // Extraer y validar el contenido base64
    $image_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
    $decoded = base64_decode($image_base64);
    
    if (!$decoded) {
        return false;
    }
    
    // Verificar que sea una imagen válida
    $image_info = getimagesizefromstring($decoded);
    if (!$image_info) {
        return false;
    }
    
    return true;
}

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos JSON
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (!$data) {
        throw new Exception('Datos inválidos');
    }
    
    $employee_id = $data['employee_id'] ?? null;
    $facial_data = $data['facial_data'] ?? null;
    
    if (!$employee_id || !$facial_data) {
        throw new Exception('Datos requeridos faltantes');
    }
    
    // Validar formato de imagen
    if (!validateImageFormat($facial_data)) {
        throw new Exception('Formato de imagen inválido');
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
    
    // Buscar datos biométricos faciales almacenados
    $stmt = $pdo->prepare("
        SELECT biometric_data
        FROM employee_biometrics
        WHERE employee_id = ?
        AND biometric_type = 'face'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$employee_id]);
    $stored_biometrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stored_biometrics)) {
        // No hay datos biométricos registrados
        logBiometricAttempt($pdo, $employee_id, 'facial', false, 0);
        
        echo json_encode([
            'success' => false,
            'message' => 'No hay datos de reconocimiento facial registrados para este empleado. Debe completar el proceso de enrolamiento.',
            'requires_enrollment' => true
        ]);
        exit;
    }
    
    // Extraer características de la imagen actual
    $current_features = extractFacialFeatures($facial_data);
    $current_features_json = json_encode($current_features);
    
    // Comparar con cada muestra facial almacenada
    $best_match = 0;
    
    foreach ($stored_biometrics as $stored) {
        $similarity = calculateFacialSimilarity(
            $stored['biometric_data'],
            $current_features_json
        );
        
        if ($similarity > $best_match) {
            $best_match = $similarity;
        }
    }
    
    // Umbral de verificación facial (más estricto que huella)
    $verification_threshold = 0.70;
    $verification_success = $best_match >= $verification_threshold;
    
    // Registrar intento
    logBiometricAttempt($pdo, $employee_id, 'facial', $verification_success, $best_match);
    
    if ($verification_success) {
        echo json_encode([
            'success' => true,
            'message' => 'Rostro verificado correctamente',
            'confidence' => round($best_match, 4),
            'employee_name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
            'data' => 'facial_verified'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'El rostro no coincide con los registros del empleado',
            'confidence' => round($best_match, 4),
            'requires_retry' => true,
            'suggestion' => $best_match > 0.5 ? 'Intente mejorar la iluminación o posición del rostro' : 'Asegúrese de estar mirando directamente a la cámara'
        ]);
    }
    
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
