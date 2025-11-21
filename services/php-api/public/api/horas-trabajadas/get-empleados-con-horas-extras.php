<?php
/**
 * API: Get employees who have overtime hours for approval modal
 * Returns only employees that have records in horas_extras_aprobacion table
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

try {
    require_once __DIR__ . '/../../config/database.php';

    // Get filters from GET parameters
    $sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : null;
    $establecimientoId = isset($_GET['establecimiento_id']) ? (int)$_GET['establecimiento_id'] : null;

    // Build query to get employees with overtime hours
    $query = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            s.NOMBRE as SEDE_NOMBRE,
            est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
            COUNT(hea.ID_HORAS_EXTRAS) as total_horas_extras,
            SUM(CASE WHEN hea.ESTADO_APROBACION = 'pendiente' THEN 1 ELSE 0 END) as horas_pendientes,
            SUM(CASE WHEN hea.ESTADO_APROBACION = 'aprobada' THEN 1 ELSE 0 END) as horas_aprobadas,
            SUM(CASE WHEN hea.ESTADO_APROBACION = 'rechazada' THEN 1 ELSE 0 END) as horas_rechazadas
        FROM horas_extras_aprobacion hea
        JOIN empleado e ON hea.ID_EMPLEADO = e.ID_EMPLEADO
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ACTIVO = 'S'
    ";

    $params = [];

    // Add filters
    if ($sedeId) {
        $query .= " AND s.ID_SEDE = ?";
        $params[] = $sedeId;
    }

    if ($establecimientoId) {
        $query .= " AND est.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimientoId;
    }

    $query .= "
        GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.DNI, s.NOMBRE, est.NOMBRE
        ORDER BY e.NOMBRE, e.APELLIDO
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $formattedEmpleados = [];
    foreach ($empleados as $empleado) {
        $formattedEmpleados[] = [
            'ID_EMPLEADO' => $empleado['ID_EMPLEADO'],
            'NOMBRE' => $empleado['NOMBRE'],
            'APELLIDO' => $empleado['APELLIDO'],
            'DNI' => $empleado['DNI'],
            'SEDE_NOMBRE' => $empleado['SEDE_NOMBRE'],
            'ESTABLECIMIENTO_NOMBRE' => $empleado['ESTABLECIMIENTO_NOMBRE'],
            'total_horas_extras' => (int)$empleado['total_horas_extras'],
            'horas_pendientes' => (int)$empleado['horas_pendientes'],
            'horas_aprobadas' => (int)$empleado['horas_aprobadas'],
            'horas_rechazadas' => (int)$empleado['horas_rechazadas']
        ];
    }

    echo json_encode([
        'success' => true,
        'empleados' => $formattedEmpleados,
        'total' => count($formattedEmpleados)
    ]);

} catch (Exception $e) {
    error_log("Error in get-empleados-con-horas-extras.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>