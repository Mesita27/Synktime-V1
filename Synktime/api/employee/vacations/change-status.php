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

try {
    ensureVacationSchema($pdo);

    $vacationId = isset($input['id_vacacion']) ? (int)$input['id_vacacion'] : 0;
    if ($vacationId <= 0) {
        throw new InvalidArgumentException('El identificador de la vacación es obligatorio.');
    }

    $targetStatus = isset($input['estado']) ? normalizeVacationStatus($input['estado']) : null;
    if (!$targetStatus) {
        throw new InvalidArgumentException('Estado destino inválido.');
    }

    $row = getVacationById($pdo, $vacationId);
    $employeeId = (int)$row['ID_EMPLEADO'];

    syncEmployeeVacationStates($pdo, $employeeId);
    $row = getVacationById($pdo, $vacationId);
    $estadoActual = strtoupper($row['ESTADO']);

    $allowedTransitions = [
        'PROGRAMADO' => ['ACTIVO', 'FINALIZADO', 'CANCELADO'],
        'ACTIVO' => ['FINALIZADO', 'CANCELADO'],
        'FINALIZADO' => [],
        'CANCELADO' => [],
    ];

    if ($targetStatus !== $estadoActual) {
        if (!isset($allowedTransitions[$estadoActual]) || !in_array($targetStatus, $allowedTransitions[$estadoActual], true)) {
            throw new InvalidArgumentException('Transición de estado no permitida.');
        }
    }

    $fechaInicioInput = isset($input['fecha_inicio']) ? trim((string)$input['fecha_inicio']) : $row['FECHA_INICIO'];
    $fechaFinInput = isset($input['fecha_fin']) ? trim((string)$input['fecha_fin']) : $row['FECHA_FIN'];

    $start = DateTime::createFromFormat('Y-m-d', $fechaInicioInput);
    $end = DateTime::createFromFormat('Y-m-d', $fechaFinInput);

    if (!$start || $start->format('Y-m-d') !== $fechaInicioInput) {
        throw new InvalidArgumentException('Fecha de inicio inválida. Usa el formato YYYY-MM-DD.');
    }

    if (!$end || $end->format('Y-m-d') !== $fechaFinInput) {
        throw new InvalidArgumentException('Fecha de fin inválida. Usa el formato YYYY-MM-DD.');
    }

    if ($end < $start) {
        throw new InvalidArgumentException('La fecha de fin no puede ser anterior a la fecha de inicio.');
    }

    $today = new DateTime('today');

    if ($targetStatus === 'ACTIVO') {
        if (employeeHasActiveVacation($pdo, $employeeId, $vacationId)) {
            throw new InvalidArgumentException('El empleado ya cuenta con otra vacación activa.');
        }

        if ($today < $start || $today > $end) {
            throw new InvalidArgumentException('Para activar la vacación, la fecha actual debe estar dentro del rango seleccionado.');
        }
    }

    if ($targetStatus === 'FINALIZADO' && $end > $today) {
        // Permitir finalizar de forma anticipada ajustando la fecha fin al día anterior o al día actual.
        if (!isset($input['fecha_fin'])) {
            $end = clone $today;
            $fechaFinInput = $end->format('Y-m-d');
        }
        if ($end < $start) {
            throw new InvalidArgumentException('Fecha de fin inválida para finalizar la vacación.');
        }
    }

    if (hasVacationOverlap($pdo, $employeeId, $start, $end, $vacationId) && $targetStatus !== 'CANCELADO') {
        throw new InvalidArgumentException('El rango proporcionado se traslapa con otra vacación activa o programada.');
    }

    $observaciones = array_key_exists('observaciones', $input)
        ? trim((string)$input['observaciones'])
        : ($row['OBSERVACIONES'] ?? null);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE empleado_vacaciones SET
            FECHA_INICIO = :inicio,
            FECHA_FIN = :fin,
            OBSERVACIONES = :obs,
            ESTADO = :estado,
            FECHA_ACTUALIZACION = NOW()
        WHERE ID_VACACION = :id');

    $stmt->execute([
        ':inicio' => $start->format('Y-m-d'),
        ':fin' => $end->format('Y-m-d'),
        ':obs' => ($observaciones !== '' ? $observaciones : null),
        ':estado' => $targetStatus,
        ':id' => $vacationId,
    ]);

    if ($targetStatus === 'ACTIVO') {
        suspendEmployeeForVacation($pdo, $employeeId, $vacationId);
    }

    if (in_array($targetStatus, ['FINALIZADO', 'CANCELADO'], true)) {
        reactivateEmployeeIfNeeded($pdo, $employeeId);
    }

    $pdo->commit();

    syncEmployeeVacationStates($pdo, $employeeId);

    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente.',
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
        'message' => 'Error al actualizar el estado de la vacación: ' . $e->getMessage(),
    ]);
}
