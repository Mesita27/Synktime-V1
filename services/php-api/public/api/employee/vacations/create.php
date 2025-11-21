<?php
require_once __DIR__ . '/../../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/common.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Usa POST.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

function normalizeFlag($value): string
{
    if (is_bool($value)) {
        return $value ? 'S' : 'N';
    }

    if (is_numeric($value)) {
        return ((int)$value) === 1 ? 'S' : 'N';
    }

    $value = strtoupper(trim((string)$value));
    if (in_array($value, ['S', 'SI', 'Y', 'YES', 'TRUE', '1'], true)) {
        return 'S';
    }

    return 'N';
}

try {
    ensureVacationSchema($pdo);

    $employeeId = isset($input['id_empleado']) ? (int)$input['id_empleado'] : 0;
    $fechaInicio = trim($input['fecha_inicio'] ?? '');
    $fechaFin = trim($input['fecha_fin'] ?? '');
    $motivo = trim($input['motivo'] ?? '');
    $observaciones = trim($input['observaciones'] ?? '');
    $reactivacion = normalizeFlag($input['reactivacion_automatica'] ?? 'S');

    if ($employeeId <= 0) {
        throw new InvalidArgumentException('El id del empleado es obligatorio.');
    }

    syncEmployeeVacationStates($pdo, $employeeId);

    $start = DateTime::createFromFormat('Y-m-d', $fechaInicio);
    $end = DateTime::createFromFormat('Y-m-d', $fechaFin);

    if (!$start || $start->format('Y-m-d') !== $fechaInicio) {
        throw new InvalidArgumentException('Fecha de inicio inválida. Usa el formato YYYY-MM-DD.');
    }

    if (!$end || $end->format('Y-m-d') !== $fechaFin) {
        throw new InvalidArgumentException('Fecha de fin inválida. Usa el formato YYYY-MM-DD.');
    }

    if ($end < $start) {
        throw new InvalidArgumentException('La fecha de fin no puede ser anterior a la fecha de inicio.');
    }

    $snapshot = getEmployeeStateSnapshot($pdo, $employeeId);
    if (!$snapshot) {
        throw new InvalidArgumentException('El empleado no existe.');
    }

    if (hasVacationOverlap($pdo, $employeeId, $start, $end, null)) {
        throw new InvalidArgumentException('El empleado ya tiene vacaciones programadas o activas en el rango proporcionado.');
    }

    $estado = inferInitialStatus($start, $end);
    if ($estado === 'ACTIVO' && employeeHasActiveVacation($pdo, $employeeId)) {
        throw new InvalidArgumentException('El empleado ya cuenta con una vacación activa.');
    }

    $userId = $_SESSION['user_id'] ?? null;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO empleado_vacaciones 
        (ID_EMPLEADO, FECHA_INICIO, FECHA_FIN, MOTIVO, OBSERVACIONES, ESTADO, REACTIVACION_AUTOMATICA, ESTADO_EMPLEADO_ANTERIOR, CREADO_POR)
        VALUES (:id, :inicio, :fin, :motivo, :obs, :estado, :reactivacion, NULL, :creado_por)');

    $stmt->execute([
        ':id' => $employeeId,
        ':inicio' => $start->format('Y-m-d'),
        ':fin' => $end->format('Y-m-d'),
        ':motivo' => $motivo !== '' ? $motivo : null,
        ':obs' => $observaciones !== '' ? $observaciones : null,
        ':estado' => $estado,
        ':reactivacion' => $reactivacion,
        ':creado_por' => $userId,
    ]);

    $vacationId = (int)$pdo->lastInsertId();

    if ($estado === 'ACTIVO') {
        suspendEmployeeForVacation($pdo, $employeeId, $vacationId);
    }

    $pdo->commit();

    syncEmployeeVacationStates($pdo, $employeeId);

    echo json_encode([
        'success' => true,
        'message' => 'Vacación registrada correctamente.',
        'data' => buildVacationPayloadById($pdo, $vacationId),
    ]);

} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar la vacación: ' . $e->getMessage(),
    ]);
}
