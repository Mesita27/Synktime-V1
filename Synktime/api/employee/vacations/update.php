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

function normalizeFlag($value): ?string
{
    if ($value === null) {
        return null;
    }

    if (is_bool($value)) {
        return $value ? 'S' : 'N';
    }

    if (is_numeric($value)) {
        return ((int)$value) === 1 ? 'S' : 'N';
    }

    $value = strtoupper(trim((string)$value));
    if ($value === '') {
        return null;
    }

    return in_array($value, ['S', 'SI', 'Y', 'YES', 'TRUE', '1'], true) ? 'S' : 'N';
}

try {
    ensureVacationSchema($pdo);

    $vacationId = isset($input['id_vacacion']) ? (int)$input['id_vacacion'] : 0;
    if ($vacationId <= 0) {
        throw new InvalidArgumentException('El identificador de la vacación es obligatorio.');
    }

    $row = getVacationById($pdo, $vacationId);

    $employeeId = (int)$row['ID_EMPLEADO'];

    syncEmployeeVacationStates($pdo, $employeeId);
    $row = getVacationById($pdo, $vacationId);

    $fechaInicio = trim($input['fecha_inicio'] ?? $row['FECHA_INICIO']);
    $fechaFin = trim($input['fecha_fin'] ?? $row['FECHA_FIN']);
    $motivo = $input['motivo'] ?? $row['MOTIVO'];
    $observaciones = $input['observaciones'] ?? $row['OBSERVACIONES'];
    $reactivacion = normalizeFlag($input['reactivacion_automatica'] ?? null) ?? ($row['REACTIVACION_AUTOMATICA'] ?? 'S');

    $estadoActual = strtoupper($row['ESTADO']);
    if (isset($input['estado'])) {
        $estadoSolicitado = normalizeVacationStatus($input['estado']);
        if ($estadoSolicitado !== $estadoActual) {
            throw new InvalidArgumentException('Para cambiar el estado utiliza el endpoint de cambio de estado.');
        }
    }

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

    if (hasVacationOverlap($pdo, $employeeId, $start, $end, $vacationId)) {
        throw new InvalidArgumentException('El rango actualizado se traslapa con otra vacación activa o programada.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE empleado_vacaciones SET
            FECHA_INICIO = :inicio,
            FECHA_FIN = :fin,
            MOTIVO = :motivo,
            OBSERVACIONES = :obs,
            REACTIVACION_AUTOMATICA = :reactivacion,
            FECHA_ACTUALIZACION = NOW()
        WHERE ID_VACACION = :id');

    $stmt->execute([
        ':inicio' => $start->format('Y-m-d'),
        ':fin' => $end->format('Y-m-d'),
        ':motivo' => $motivo !== null && trim((string)$motivo) !== '' ? trim((string)$motivo) : null,
        ':obs' => $observaciones !== null && trim((string)$observaciones) !== '' ? trim((string)$observaciones) : null,
        ':reactivacion' => $reactivacion,
        ':id' => $vacationId,
    ]);

    if ($estadoActual === 'ACTIVO') {
        suspendEmployeeForVacation($pdo, $employeeId, $vacationId);
    }

    $pdo->commit();

    syncEmployeeVacationStates($pdo, $employeeId);

    echo json_encode([
        'success' => true,
        'message' => 'Vacación actualizada correctamente.',
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
        'message' => 'Error al actualizar la vacación: ' . $e->getMessage(),
    ]);
}
