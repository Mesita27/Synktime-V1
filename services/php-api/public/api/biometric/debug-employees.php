<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';

header('Content-Type: application/json');

try {
    // Consulta de prueba para ver los valores exactos
    $query = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            CASE WHEN eb_facial.id IS NOT NULL THEN 1 ELSE 0 END as facial_enrolled,
            CASE WHEN eb_huella.id IS NOT NULL THEN 1 ELSE 0 END as fingerprint_enrolled,
            CASE WHEN eb_rfid.id IS NOT NULL THEN 1 ELSE 0 END as rfid_enrolled
        FROM empleado e
        LEFT JOIN employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id
            AND eb_facial.biometric_type = 'face'
        LEFT JOIN employee_biometrics eb_huella ON e.ID_EMPLEADO = eb_huella.employee_id
            AND eb_huella.biometric_type = 'fingerprint'
        LEFT JOIN employee_biometrics eb_rfid ON e.ID_EMPLEADO = eb_rfid.employee_id
            AND eb_rfid.biometric_type = 'rfid'
        WHERE e.ACTIVO = 'S'
        ORDER BY e.NOMBRE, e.APELLIDO
        LIMIT 10
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar empleados
    foreach ($employees as &$employee) {
        $employee['NOMBRE_COMPLETO'] = trim($employee['NOMBRE'] . ' ' . $employee['APELLIDO']);

        // Debug: mostrar tipos de datos
        $employee['debug_facial_type'] = gettype($employee['facial_enrolled']);
        $employee['debug_facial_value'] = $employee['facial_enrolled'];
        $employee['debug_facial_bool'] = (bool)$employee['facial_enrolled'];

        // Calcular estado biométrico
        $biometric_count = 0;
        if ($employee['facial_enrolled']) $biometric_count++;
        if ($employee['fingerprint_enrolled']) $biometric_count++;
        if ($employee['rfid_enrolled']) $biometric_count++;

        if ($biometric_count === 3) {
            $employee['estado_biometrico'] = 'completo';
        } elseif ($biometric_count > 0) {
            $employee['estado_biometrico'] = 'parcial';
        } else {
            $employee['estado_biometrico'] = 'pendiente';
        }
    }

    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'debug_info' => 'Valores de debug incluidos'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
