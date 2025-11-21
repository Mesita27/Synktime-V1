<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar si es una solicitud OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Verificar que el archivo de configuración existe
    $configPath = '../../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception("Archivo de configuración no encontrado: $configPath");
    }

    require_once $configPath;

    // Verificar que la conexión PDO existe
    if (!isset($pdo)) {
        throw new Exception('Conexión a base de datos no disponible');
    }

    // Obtener empleados con datos biométricos para verificación de asistencia
    $query = "
        SELECT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.ACTIVO,
            CASE WHEN eb_facial.id IS NOT NULL THEN 1 ELSE 0 END as facial_registered,
            CASE WHEN eb_huella.id IS NOT NULL THEN 1 ELSE 0 END as fingerprint_registered,
            CASE WHEN eb_rfid.id IS NOT NULL THEN 1 ELSE 0 END as rfid_registered,
            eb_facial.created_at as facial_enrolled_at,
            eb_huella.created_at as fingerprint_enrolled_at,
            eb_rfid.created_at as rfid_enrolled_at
        FROM empleado e
        LEFT JOIN employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id AND eb_facial.biometric_type = 'face'
        LEFT JOIN employee_biometrics eb_huella ON e.ID_EMPLEADO = eb_huella.employee_id AND eb_huella.biometric_type = 'fingerprint'
        LEFT JOIN employee_biometrics eb_rfid ON e.ID_EMPLEADO = eb_rfid.employee_id AND eb_rfid.biometric_type = 'rfid'
        WHERE e.ACTIVO = 'S'
        AND (eb_facial.id IS NOT NULL OR eb_huella.id IS NOT NULL OR eb_rfid.id IS NOT NULL)
        ORDER BY e.NOMBRE, e.APELLIDO
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar que se obtuvieron resultados
    if (empty($employees)) {
        echo json_encode([
            'success' => true,
            'employees' => [],
            'total' => 0,
            'message' => 'No se encontraron empleados con datos biométricos registrados'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'employees' => $employees,
            'total' => count($employees)
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in get-employees-verification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_type' => 'database'
    ]);
} catch (Exception $e) {
    error_log("General error in get-employees-verification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage(),
        'error_type' => 'general'
    ]);
}
?>
