<?php
/**
 * API para verificación de RFID/Carné
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

    if (!$input || !isset($input['employee_id']) || !isset($input['rfid_uid'])) {
        throw new Exception('Datos incompletos: se requiere employee_id y rfid_uid');
    }

    $employeeId = $input['employee_id'];
    $rfidUid = trim($input['rfid_uid']);

    // Validar formato del UID
    if (empty($rfidUid) || strlen($rfidUid) < 8) {
        throw new Exception('UID de RFID inválido');
    }

    // Verificar que el empleado existe
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM empleado WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode([
            'success' => false,
            'verified' => false,
            'error' => 'Empleado no encontrado',
            'recommendations' => [
                'Verifique que el ID del empleado sea correcto',
                'Contacte al administrador si el empleado no está registrado'
            ]
        ]);
        exit;
    }

    // Verificar que el empleado tenga datos biométricos RFID
    $stmt = $pdo->prepare("
        SELECT id, datos_biometricos
        FROM employee_biometrics
        WHERE employee_id = ? AND tipo = 'rfid' AND estado = 'A'
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ");
    $stmt->execute([$employeeId]);
    $biometricData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$biometricData) {
        echo json_encode([
            'success' => false,
            'verified' => false,
            'error' => 'El empleado no tiene datos RFID registrados',
            'recommendations' => [
                'El empleado debe enrolar su carné RFID primero',
                'Contacte al administrador para el proceso de enrolamiento',
                'Use otro método de verificación disponible'
            ]
        ]);
        exit;
    }

    // Decodificar los datos biométricos almacenados
    $storedBiometricData = json_decode($biometricData['datos_biometricos'], true);

    if (!$storedBiometricData || !isset($storedBiometricData['uid'])) {
        throw new Exception('Datos biométricos RFID corruptos');
    }

    $storedUid = trim($storedBiometricData['uid']);

    // Comparar UIDs (en un sistema real se haría una comparación más sofisticada)
    $isVerified = strcasecmp($rfidUid, $storedUid) === 0;
    $confidence = $isVerified ? 0.98 : 0.0;

    // Registrar el intento de verificación
    $stmt = $pdo->prepare("
        INSERT INTO biometric_verification_log
        (employee_id, verification_type, success, confidence, ip_address, user_agent, additional_data, created_at)
        VALUES (?, 'rfid', ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $employeeId,
        $isVerified ? 1 : 0,
        $confidence,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        json_encode(['rfid_uid' => $rfidUid, 'stored_uid' => $storedUid])
    ]);

    if ($isVerified) {
        // Verificación exitosa
        echo json_encode([
            'success' => true,
            'verified' => true,
            'employee_id' => $employeeId,
            'employee_name' => $employee['nombre'] . ' ' . $employee['apellido'],
            'confidence' => $confidence,
            'verification_type' => 'rfid',
            'uid' => $rfidUid,
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Carné RFID verificado correctamente'
        ]);
    } else {
        // Verificación fallida
        echo json_encode([
            'success' => true,
            'verified' => false,
            'employee_id' => $employeeId,
            'confidence' => $confidence,
            'verification_type' => 'rfid',
            'provided_uid' => $rfidUid,
            'stored_uid' => $storedUid,
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Carné RFID no reconocido',
            'recommendations' => [
                'Asegúrese de usar el carné correcto registrado en el sistema',
                'Verifique que el carné no esté dañado o desmagnetizado',
                'Acerque el carné más cerca del lector',
                'Intente desde diferentes ángulos',
                'Contacte al administrador si el carné necesita reprogramación'
            ]
        ]);
    }

} catch (Exception $e) {
    error_log('Error en verificación RFID: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'verified' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage(),
        'recommendations' => [
            'Verifique la conexión con el servidor',
            'Asegúrese de que el lector RFID esté funcionando',
            'Intente nuevamente en unos momentos',
            'Contacte al administrador si el problema persiste',
            'Use un método de verificación alternativo'
        ]
    ]);
}
?>
