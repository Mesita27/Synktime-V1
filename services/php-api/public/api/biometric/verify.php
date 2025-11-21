<?php
/**
 * API Endpoint: Verificación Biométrica
 * Versión: 2.0
 * Método: POST
 * Descripción: Verifica y valida datos biométricos antes del registro
 */

// Configuración de errores para producción
error_reporting(0);
ini_set('display_errors', 0);

// Limpiar cualquier output anterior
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Headers para API REST
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Includes necesarios
    require_once __DIR__ . '/../../config/database.php';
    
    // Configurar zona horaria
    date_default_timezone_set('America/Bogota');
    
    // Función para crear timestamp de Bogotá
    function getBogotaDateTime() {
        return date('Y-m-d H:i:s');
    }
    
    // Función para respuesta JSON estandarizada
    function sendResponse($success, $message, $data = null, $httpCode = 200) {
        http_response_code($httpCode);
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => getBogotaDateTime()
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Método HTTP no permitido. Use POST.', null, 405);
    }
    
    // Obtener datos del request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Si no hay datos JSON, usar $_POST
    if (!$data) {
        $data = $_POST;
    }
    
    // Log para debugging
    error_log("API Biometric Verify - Datos recibidos: " . json_encode($data));
    
    // Extraer parámetros
    $employee_id = $data['employee_id'] ?? $data['id_empleado'] ?? null;
    $verification_method = $data['verification_method'] ?? 'facial';
    $biometric_data = $data['biometric_data'] ?? null;
    $confidence_score = $data['confidence_score'] ?? 0;
    
    // Validaciones
    if (!$employee_id) {
        sendResponse(false, 'ID de empleado requerido', null, 400);
    }
    
    if (!in_array($verification_method, ['facial', 'fingerprint', 'traditional'])) {
        sendResponse(false, 'Método de verificación inválido', null, 400);
    }
    
    // Verificar que el empleado existe y está activo
    $stmt = $conn->prepare("
        SELECT 
            e.ID_EMPLEADO,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as NOMBRE_COMPLETO,
            e.CEDULA,
            e.CARGO,
            est.NOMBRE as ESTABLECIMIENTO,
            e.ACTIVO
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        WHERE e.ID_EMPLEADO = ?
    ");
    
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'Empleado no encontrado', [
            'employee_id' => $employee_id,
            'verification_method' => $verification_method
        ], 404);
    }
    
    $employee = $result->fetch_assoc();
    
    if ($employee['ACTIVO'] !== 'S') {
        sendResponse(false, 'Empleado inactivo', [
            'employee_id' => $employee_id,
            'employee_name' => $employee['NOMBRE_COMPLETO'],
            'status' => $employee['ACTIVO']
        ], 403);
    }
    
    // Verificar si ya existe un registro reciente (últimos 5 minutos)
    $fecha_limite = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $stmt = $conn->prepare("
        SELECT 
            ID_ASISTENCIA,
            TIPO,
            FECHA,
            HORA,
            CREATED_AT
        FROM asistencia 
        WHERE ID_EMPLEADO = ? 
        AND CREATED_AT > ?
        ORDER BY CREATED_AT DESC 
        LIMIT 1
    ");
    
    $stmt->bind_param("is", $employee_id, $fecha_limite);
    $stmt->execute();
    $recent_result = $stmt->get_result();
    
    $has_recent_record = false;
    $recent_record = null;
    
    if ($recent_result->num_rows > 0) {
        $has_recent_record = true;
        $recent_record = $recent_result->fetch_assoc();
    }
    
    // Determinar el próximo tipo de registro basado en el último registro
    $stmt = $conn->prepare("
        SELECT TIPO, FECHA, HORA
        FROM asistencia 
        WHERE ID_EMPLEADO = ? 
        ORDER BY FECHA DESC, HORA DESC 
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $last_result = $stmt->get_result();
    
    $suggested_type = 'ENTRADA'; // Por defecto
    $last_record = null;
    
    if ($last_result->num_rows > 0) {
        $last_record = $last_result->fetch_assoc();
        // Si el último registro fue ENTRADA, sugerir SALIDA
        $suggested_type = ($last_record['TIPO'] === 'ENTRADA') ? 'SALIDA' : 'ENTRADA';
    }
    
    // Validar el nivel de confianza
    $confidence_status = 'low';
    if ($confidence_score >= 85) {
        $confidence_status = 'high';
    } elseif ($confidence_score >= 70) {
        $confidence_status = 'medium';
    }
    
    // Preparar datos de respuesta
    $response_data = [
        'employee' => [
            'id' => $employee['ID_EMPLEADO'],
            'name' => $employee['NOMBRE_COMPLETO'],
            'cedula' => $employee['CEDULA'],
            'cargo' => $employee['CARGO'],
            'establecimiento' => $employee['ESTABLECIMIENTO'],
            'active' => $employee['ACTIVO'] === 'S'
        ],
        'verification' => [
            'method' => $verification_method,
            'confidence_score' => $confidence_score,
            'confidence_status' => $confidence_status,
            'is_valid' => $confidence_score >= 70 // Mínimo 70% para validar
        ],
        'attendance' => [
            'suggested_type' => $suggested_type,
            'has_recent_record' => $has_recent_record,
            'recent_record' => $recent_record,
            'last_record' => $last_record
        ],
        'warnings' => []
    ];
    
    // Agregar advertencias si es necesario
    if ($has_recent_record) {
        $response_data['warnings'][] = "Registro reciente encontrado hace " . 
            round((time() - strtotime($recent_record['CREATED_AT'])) / 60) . " minutos";
    }
    
    if ($confidence_score < 70) {
        $response_data['warnings'][] = "Nivel de confianza bajo: {$confidence_score}%";
    }
    
    // Determinar el mensaje y código de respuesta
    if ($confidence_score >= 70) {
        sendResponse(true, 'Verificación biométrica exitosa', $response_data, 200);
    } else {
        sendResponse(false, 'Verificación biométrica fallida - Confianza insuficiente', $response_data, 422);
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en API Biometric Verify: " . $e->getMessage());
    
    // Respuesta de error
    sendResponse(false, 'Error interno del servidor: ' . $e->getMessage(), null, 500);
}

// Limpiar output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>