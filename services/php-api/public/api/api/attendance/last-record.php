<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$employeeId = $_GET['employee_id'] ?? '';

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
    exit;
}

try {
    // Obtener el último registro de asistencia para el empleado
    $sql = "SELECT a.ID_ASISTENCIA, a.FECHA, a.HORA, a.TIPO, a.OBSERVACION,
                   e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.CODIGO
            FROM ASISTENCIA a
            JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            WHERE e.ID_EMPLEADO = ?
            ORDER BY a.FECHA DESC, a.HORA DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$employeeId]);
    $lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastRecord) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id_asistencia' => $lastRecord['ID_ASISTENCIA'],
                'fecha' => $lastRecord['FECHA'],
                'hora' => $lastRecord['HORA'],
                'tipo' => $lastRecord['TIPO'],
                'observacion' => $lastRecord['OBSERVACION'],
                'employee' => [
                    'id' => $lastRecord['ID_EMPLEADO'],
                    'nombre' => $lastRecord['NOMBRE'],
                    'apellido' => $lastRecord['APELLIDO'],
                    'codigo' => $lastRecord['CODIGO']
                ]
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No hay registros de asistencia para este empleado'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el último registro: ' . $e->getMessage()
    ]);
}
?>
