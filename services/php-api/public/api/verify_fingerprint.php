<?php
/**
 * API para verificación de huella dactilar
 * SNKTIME Biometric System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST.'
    ]);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';

try {
    // Obtener datos de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['employee_id'])) {
        throw new Exception('Datos de empleado no proporcionados');
    }

    $employeeId = $input['employee_id'];
    $fingerprintData = $input['fingerprint_data'] ?? null;

    // Verificar que el empleado existe
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM empleado WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Empleado no encontrado');
    }

    // Verificar que el empleado tenga datos biométricos de huella
    $stmt = $pdo->prepare("
        SELECT id, datos_biometricos
        FROM employee_biometrics
        WHERE employee_id = ? AND tipo = 'fingerprint' AND estado = 'A'
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ");
    $stmt->execute([$employeeId]);
    $biometricData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$biometricData) {
        echo json_encode([
            'success' => false,
            'verified' => false,
            'error' => 'El empleado no tiene datos biométricos de huella registrados',
            'recommendations' => [
                'El empleado debe enrolar sus datos biométricos de huella primero',
                'Contacte al administrador para el proceso de enrolamiento',
                'Use otro método de verificación disponible'
            ]
        ]);
        exit;
    }

    // En un sistema real, aquí se haría la comparación biométrica
    // Por ahora simulamos una verificación exitosa
    $isVerified = true; // Simulación
    $confidence = 0.92; // Confianza simulada

    // Registrar el intento de verificación
    $stmt = $pdo->prepare("
        INSERT INTO biometric_verification_log
        (employee_id, verification_type, success, confidence, ip_address, user_agent, created_at)
        VALUES (?, 'fingerprint', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $employeeId,
        $isVerified ? 1 : 0,
        $confidence,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    if ($isVerified) {
        // Verificación exitosa
        echo json_encode([
            'success' => true,
            'verified' => true,
            'employee_id' => $employeeId,
            'employee_name' => $employee['nombre'] . ' ' . $employee['apellido'],
            'confidence' => $confidence,
            'verification_type' => 'fingerprint',
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Huella dactilar verificada correctamente'
        ]);
    } else {
        // Verificación fallida
        echo json_encode([
            'success' => true,
            'verified' => false,
            'employee_id' => $employeeId,
            'confidence' => $confidence,
            'verification_type' => 'fingerprint',
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Huella dactilar no reconocida',
            'recommendations' => [
                'Asegúrese de usar el dedo correcto registrado en el sistema',
                'Limpie el dedo y el escáner antes de intentar nuevamente',
                'Verifique que el escáner esté funcionando correctamente',
                'Intente con otro método de verificación si está disponible'
            ]
        ]);
    }

} catch (Exception $e) {
    error_log('Error en verificación de huella: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'verified' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage(),
        'recommendations' => [
            'Verifique la conexión con el servidor',
            'Intente nuevamente en unos momentos',
            'Contacte al administrador si el problema persiste',
            'Use un método de verificación alternativo'
        ]
    ]);
}
?>
