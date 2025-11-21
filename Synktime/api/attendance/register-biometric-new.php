<?php
/**
 * API Endpoint: Registro de Asistencia Biométrica
 * Versión: 2.0 - Sin redirecciones de autenticación
 * Método: POST
 * Descripción: Registra asistencia usando verificación biométrica facial
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
    
    function getBogotaDate() {
        return date('Y-m-d');
    }
    
    function getBogotaTime() {
        return date('H:i');
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
    error_log("API Biometric Register - Datos recibidos: " . json_encode($data));
    
    // Extraer parámetros
    $employee_id = $data['employee_id'] ?? $data['id_empleado'] ?? null;
    $type = strtoupper($data['type'] ?? $data['tipo'] ?? 'ENTRADA');
    $verification_method = $data['verification_method'] ?? 'facial';
    $verification_results = $data['verification_results'] ?? [];
    $confidence_score = $verification_results['confidence_score'] ?? $data['confidence_score'] ?? 0;
    
    // Validaciones
    if (!$employee_id) {
        sendResponse(false, 'ID de empleado requerido', null, 400);
    }
    
    if (!in_array($type, ['ENTRADA', 'SALIDA'])) {
        sendResponse(false, 'Tipo de registro inválido. Use ENTRADA o SALIDA', null, 400);
    }
    
    // Verificar que el empleado existe
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, CONCAT(NOMBRE, ' ', APELLIDO) as NOMBRE_COMPLETO FROM empleado WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $employee_result = $stmt->get_result();
    
    if ($employee_result->num_rows === 0) {
        sendResponse(false, 'Empleado no encontrado o inactivo', null, 404);
    }
    
    $employee = $employee_result->fetch_assoc();
    
    // Preparar datos para inserción
    $fecha = getBogotaDate();
    $hora = getBogotaTime();
    $created_at = getBogotaDateTime();
    
    // Mapear método de verificación a ENUM correcto
    $verification_method_enum = 'facial'; // Por defecto facial
    if (strpos($verification_method, 'fingerprint') !== false) {
        $verification_method_enum = 'fingerprint';
    } elseif (strpos($verification_method, 'traditional') !== false) {
        $verification_method_enum = 'traditional';
    }
    
    // Crear observación descriptiva
    $observacion = "Registro biométrico {$verification_method} - Confianza: " . number_format($confidence_score, 1) . "%";
    
    // Insertar registro de asistencia
    $sql = "INSERT INTO asistencia (
        ID_EMPLEADO, 
        FECHA, 
        HORA, 
        TIPO, 
        VERIFICATION_METHOD, 
        OBSERVACION, 
        CREATED_AT,
        REGISTRO_MANUAL
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'N')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", 
        $employee_id,
        $fecha,
        $hora,
        $type,
        $verification_method_enum,
        $observacion,
        $created_at
    );
    
    if ($stmt->execute()) {
        $attendance_id = $conn->insert_id;
        
        // Datos de respuesta exitosa
        $response_data = [
            'attendance_id' => $attendance_id,
            'employee_id' => $employee_id,
            'employee_name' => $employee['NOMBRE_COMPLETO'],
            'type' => $type,
            'date' => $fecha,
            'time' => $hora,
            'verification_method' => $verification_method_enum,
            'confidence_score' => $confidence_score,
            'created_at' => $created_at
        ];
        
        sendResponse(true, 'Asistencia registrada exitosamente', $response_data, 201);
        
    } else {
        throw new Exception('Error al insertar registro en base de datos: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en API Biometric Register: " . $e->getMessage());
    
    // Respuesta de error
    sendResponse(false, 'Error interno del servidor: ' . $e->getMessage(), null, 500);
}

// Limpiar output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>