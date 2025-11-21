<?php
/**
 * API: Get approved overtime hours for calculation
 * Used by the main hours calculation API
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

try {
    global $conn;

    // Get parameters
    $idEmpleado = isset($_GET['id_empleado']) ? (int)$_GET['id_empleado'] : null;
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;

    if (!$idEmpleado) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit;
    }

    // Build query to get approved overtime hours
    $query = "
        SELECT
            ID_HORAS_EXTRAS,
            FECHA,
            HORA_INICIO,
            HORA_FIN,
            HORAS_EXTRAS,
            TIPO_EXTRA,
            TIPO_HORARIO
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND ESTADO_APROBACION = 'aprobada'
    ";

    $params = [$idEmpleado];

    if ($fechaDesde) {
        $query .= " AND FECHA >= ?";
        $params[] = $fechaDesde;
    }

    if ($fechaHasta) {
        $query .= " AND FECHA <= ?";
        $params[] = $fechaHasta;
    }

    $query .= " ORDER BY FECHA ASC, HORA_INICIO ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $horasExtrasAprobadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by date for easier processing
    $horasPorFecha = [];
    foreach ($horasExtrasAprobadas as $horaExtra) {
        $fecha = $horaExtra['FECHA'];
        if (!isset($horasPorFecha[$fecha])) {
            $horasPorFecha[$fecha] = [];
        }

        $horasPorFecha[$fecha][] = [
            'hora_inicio' => $horaExtra['HORA_INICIO'],
            'hora_fin' => $horaExtra['HORA_FIN'],
            'horas_extras' => floatval($horaExtra['HORAS_EXTRAS']),
            'tipo_extra' => $horaExtra['TIPO_EXTRA'],
            'tipo_horario' => $horaExtra['TIPO_HORARIO']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $horasPorFecha,
        'total_registros' => count($horasExtrasAprobadas)
    ]);

} catch (Exception $e) {
    error_log("Error in get-horas-extras-aprobadas.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>