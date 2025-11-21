<?php
/**
 * API Simple: Registro de Asistencia Biométrica
 * Basado en attendance_register_fixed.php que funciona
 */

// Headers básicos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configuración
require_once __DIR__ . '/../../config/database.php';

// Zona horaria
date_default_timezone_set('America/Bogota');

function getBogotaDateTime() {
    return date('Y-m-d H:i:s');
}

function getBogotaDate() {
    return date('Y-m-d');
}

function getBogotaTime() {
    return date('H:i');
}

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit();
    }
    
    // Obtener datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        exit();
    }
    
    // Extraer parámetros principales
    $employee_id = $data['employee_id'] ?? null;
    $type = strtoupper($data['type'] ?? 'ENTRADA');
    $verification_results = $data['verification_results'] ?? [];
    $confidence_score = $verification_results['confidence_score'] ?? 0;
    
    // Validaciones básicas
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit();
    }
    
    if (!in_array($type, ['ENTRADA', 'SALIDA'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
        exit();
    }
    
    // Verificar empleado
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, CONCAT(NOMBRE, ' ', APELLIDO) as NOMBRE_COMPLETO FROM empleado WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'");
    $stmt->execute([$employee_id]);
    $result = $stmt->fetchAll();
    
    if (count($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
        exit();
    }
    
    $employee = $result[0];
    
    // Preparar datos para inserción
    $fecha = getBogotaDate();
    $hora = getBogotaTime();
    $created_at = getBogotaDateTime();
    $verification_method = 'facial';
    $observacion = "Registro biométrico biometric_facial - Confianza: " . number_format($confidence_score, 1) . "%";
    
    // Insertar registro
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
    
    if ($stmt->execute([
        $employee_id,
        $fecha,
        $hora,
        $type,
        $verification_method,
        $observacion,
        $created_at
    ])) {
        $attendance_id = $conn->lastInsertId();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Asistencia registrada exitosamente',
            'data' => [
                'attendance_id' => $attendance_id,
                'employee_id' => $employee_id,
                'employee_name' => $employee['NOMBRE_COMPLETO'],
                'type' => $type,
                'date' => $fecha,
                'time' => $hora,
                'created_at' => $created_at
            ]
        ]);
    } else {
        throw new Exception('Error al insertar registro');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
}
?>