<?php
require_once __DIR__ . '/../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;

    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Verificar que se proporcionó un employee_id
    if (!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado no proporcionado']);
        exit;
    }

    $employeeId = intval($_GET['employee_id']);

    // Verificar que el empleado pertenece a la empresa del usuario
    $checkSql = "
        SELECT e.ID_EMPLEADO
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :employee_id
        AND s.ID_EMPRESA = :empresa_id
    ";

    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindValue(':employee_id', $employeeId);
    $checkStmt->bindValue(':empresa_id', $empresaId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o no autorizado']);
        exit;
    }

    // Consultar datos biométricos del empleado
    $sql = "
        SELECT
            biometric_type,
            created_at,
            updated_at
        FROM employee_biometrics
        WHERE employee_id = :employee_id
        ORDER BY biometric_type
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':employee_id', $employeeId);
    $stmt->execute();

    $biometrics = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Mapear los tipos de la base de datos a los tipos esperados por el frontend
        $tipo = '';
        switch ($row['biometric_type']) {
            case 'face':
                $tipo = 'face';
                break;
            case 'fingerprint':
                $tipo = 'fingerprint';
                break;
            case 'rfid':
                $tipo = 'rfid';
                break;
            default:
                $tipo = $row['biometric_type']; // Mantener el tipo original si no está mapeado
                break;
        }

        $biometrics[] = [
            'tipo' => $tipo,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    echo json_encode($biometrics);

} catch (Exception $e) {
    error_log('Error en employee_biometrics.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
