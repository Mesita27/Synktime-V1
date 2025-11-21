<?php
require_once __DIR__ . '/../../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/common.php';

header('Content-Type: application/json');

try {
    ensureVacationSchema($pdo);

    $employeeId = isset($_GET['id_empleado']) ? (int)$_GET['id_empleado'] : 0;
    if ($employeeId <= 0) {
        throw new InvalidArgumentException('Parámetro id_empleado es obligatorio.');
    }

    syncEmployeeVacationStates($pdo, $employeeId);

    $estadoFilter = isset($_GET['estado']) ? strtoupper(trim($_GET['estado'])) : null;
    if ($estadoFilter && !in_array($estadoFilter, ['PROGRAMADO', 'ACTIVO', 'FINALIZADO', 'CANCELADO'], true)) {
        throw new InvalidArgumentException('Estado de filtro inválido.');
    }

    $sql = "SELECT * FROM empleado_vacaciones WHERE ID_EMPLEADO = :id";
    $params = [':id' => $employeeId];

    if ($estadoFilter) {
        $sql .= " AND ESTADO = :estado";
        $params[':estado'] = $estadoFilter;
    }

    $sql .= " ORDER BY FECHA_INICIO DESC, FECHA_CREACION DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $vacations = array_map('buildVacationPayload', $rows);

    $summary = [
        'total' => count($vacations),
        'activos' => 0,
        'programados' => 0,
        'finalizados' => 0,
        'cancelados' => 0,
        'tiene_activa' => false,
        'proxima_vacacion' => null,
    ];

    $proximas = [];

    foreach ($vacations as $vacation) {
        switch ($vacation['estado']) {
            case 'ACTIVO':
                $summary['activos']++;
                $summary['tiene_activa'] = true;
                break;
            case 'PROGRAMADO':
                $summary['programados']++;
                $proximas[] = $vacation;
                break;
            case 'FINALIZADO':
                $summary['finalizados']++;
                break;
            case 'CANCELADO':
                $summary['cancelados']++;
                break;
        }
    }

    if (!empty($proximas)) {
        usort($proximas, fn($a, $b) => strcmp($a['fecha_inicio'], $b['fecha_inicio']));
        $summary['proxima_vacacion'] = $proximas[0];
    }

    echo json_encode([
        'success' => true,
        'data' => $vacations,
        'summary' => $summary,
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

