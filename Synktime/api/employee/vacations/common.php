<?php

/**
 * Utility helpers for employee vacation operations.
 */

/**
 * Ensure optional columns exist in the empleado_vacaciones table.
 */
function ensureVacationSchema(PDO $pdo): void
{
    static $schemaChecked = false;
    if ($schemaChecked) {
        return;
    }

    $columnsToEnsure = [
        'REACTIVACION_AUTOMATICA' => "ALTER TABLE empleado_vacaciones ADD COLUMN REACTIVACION_AUTOMATICA CHAR(1) NOT NULL DEFAULT 'S' AFTER ESTADO",
        'ESTADO_EMPLEADO_ANTERIOR' => "ALTER TABLE empleado_vacaciones ADD COLUMN ESTADO_EMPLEADO_ANTERIOR CHAR(1) DEFAULT NULL AFTER REACTIVACION_AUTOMATICA"
    ];

    $columnCheckStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empleado_vacaciones' AND COLUMN_NAME = :column"
    );

    foreach ($columnsToEnsure as $column => $alterSql) {
    $columnCheckStmt->execute(['column' => $column]);
    $exists = (int)$columnCheckStmt->fetchColumn() > 0;
    $columnCheckStmt->closeCursor();
        if (!$exists) {
            $pdo->exec($alterSql);
        }
    }

    $schemaChecked = true;
}

/**
 * Normalize the status value to allowed enum values.
 */
function normalizeVacationStatus(string $status): string
{
    $status = strtoupper(trim($status));
    $valid = ['PROGRAMADO', 'ACTIVO', 'FINALIZADO', 'CANCELADO'];
    if (!in_array($status, $valid, true)) {
        throw new InvalidArgumentException('Estado de vacación inválido.');
    }
    return $status;
}

/**
 * Determine the desired status based on dates.
 */
function inferInitialStatus(DateTime $start, DateTime $end, ?DateTime $today = null): string
{
    $today = $today ?? new DateTime('today');

    if ($start <= $today && $today <= $end) {
        return 'ACTIVO';
    }

    if ($start > $today) {
        return 'PROGRAMADO';
    }

    // If the range is already in the past, mark as finalizado.
    if ($end < $today) {
        return 'FINALIZADO';
    }

    return 'PROGRAMADO';
}

/**
 * Fetch the current estado column for the employee.
 */
function getEmployeeEstado(PDO $pdo, int $employeeId): ?string
{
    $stmt = $pdo->prepare('SELECT ESTADO FROM empleado WHERE ID_EMPLEADO = ?');
    $stmt->execute([$employeeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['ESTADO'] ?? null;
}

/**
 * Update the employee ESTADO column when necessary.
 */
function updateEmployeeEstado(PDO $pdo, int $employeeId, string $nuevoEstado, ?string $nuevoActivo = null): void
{
    $nuevoEstado = strtoupper($nuevoEstado);
    if (!in_array($nuevoEstado, ['A', 'I'], true)) {
        throw new InvalidArgumentException('Estado de empleado inválido');
    }

    if ($nuevoActivo === null) {
        $nuevoActivo = $nuevoEstado === 'A' ? 'S' : 'N';
    }

    $nuevoActivo = strtoupper($nuevoActivo) === 'S' ? 'S' : 'N';

    $queries = [
        'UPDATE empleado SET ESTADO = ?, ACTIVO = ?, FECHA_ACTUALIZACION = NOW() WHERE ID_EMPLEADO = ?',
        'UPDATE empleado SET ESTADO = ?, ACTIVO = ? WHERE ID_EMPLEADO = ?',
    ];

    foreach ($queries as $sql) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nuevoEstado, $nuevoActivo, $employeeId]);
            return;
        } catch (Throwable $e) {
            // Try next fallback variation
            continue;
        }
    }

    try {
        $fallback = $pdo->prepare('UPDATE empleado SET ESTADO = ? WHERE ID_EMPLEADO = ?');
        $fallback->execute([$nuevoEstado, $employeeId]);
    } finally {
        try {
            $activoStmt = $pdo->prepare('UPDATE empleado SET ACTIVO = ? WHERE ID_EMPLEADO = ?');
            $activoStmt->execute([$nuevoActivo, $employeeId]);
        } catch (Throwable $ignored) {
            // If ACTIVO column does not exist we ignore the error.
        }
    }
}

/**
 * Check if the employee currently has active vacations (excluding a specific record if provided).
 */
function employeeHasActiveVacation(PDO $pdo, int $employeeId, ?int $excludeVacationId = null): bool
{
    $sql = "SELECT COUNT(*) AS total FROM empleado_vacaciones WHERE ID_EMPLEADO = ? AND ESTADO = 'ACTIVO'";
    $params = [$employeeId];
    if ($excludeVacationId) {
        $sql .= ' AND ID_VACACION <> ?';
        $params[] = $excludeVacationId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Detect overlapping vacation ranges for an employee.
 */
function hasVacationOverlap(PDO $pdo, int $employeeId, DateTime $start, DateTime $end, ?int $excludeVacationId = null): bool
{
        $sql = <<<SQL
                SELECT COUNT(*)
                FROM empleado_vacaciones
                WHERE ID_EMPLEADO = :empleado
                    AND ESTADO IN ('PROGRAMADO', 'ACTIVO')
                    AND NOT (FECHA_FIN < :inicio OR FECHA_INICIO > :fin)
SQL;

    if ($excludeVacationId) {
        $sql .= ' AND ID_VACACION <> :exclude_id';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':empleado', $employeeId, PDO::PARAM_INT);
    $stmt->bindValue(':inicio', $start->format('Y-m-d'));
    $stmt->bindValue(':fin', $end->format('Y-m-d'));
    if ($excludeVacationId) {
        $stmt->bindValue(':exclude_id', $excludeVacationId, PDO::PARAM_INT);
    }

    $stmt->execute();
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Restore employee estado if there are no active vacations and it was previously stored.
 */
function reactivateEmployeeIfNeeded(PDO $pdo, int $employeeId): void
{
    if (employeeHasActiveVacation($pdo, $employeeId)) {
        // Another vacation is still active; keep employee inactive.
        return;
    }

    $sql = <<<SQL
                SELECT ESTADO_EMPLEADO_ANTERIOR
                FROM empleado_vacaciones
                WHERE ID_EMPLEADO = ?
                    AND ESTADO_EMPLEADO_ANTERIOR IS NOT NULL
                ORDER BY FECHA_ACTUALIZACION DESC, FECHA_CREACION DESC
                LIMIT 1
SQL;

        $stmt = $pdo->prepare($sql);
    $stmt->execute([$employeeId]);
    $previousEstado = $stmt->fetchColumn();

    if ($previousEstado === false) {
        return;
    }

    if ($previousEstado === 'A') {
        updateEmployeeEstado($pdo, $employeeId, 'A');
    }
}

/**
 * Keep vacation statuses in sync with current date and adjust employee activity state.
 */
function syncEmployeeVacationStates(PDO $pdo, int $employeeId, ?DateTime $today = null): void
{
    $today = $today ?? new DateTime('today');

    $stmt = $pdo->prepare('SELECT ID_VACACION, ESTADO, FECHA_INICIO, FECHA_FIN FROM empleado_vacaciones WHERE ID_EMPLEADO = :empleado');
    $stmt->execute([':empleado' => $employeeId]);
    $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$vacations) {
        reactivateEmployeeIfNeeded($pdo, $employeeId);
        return;
    }

    $activateStmt = $pdo->prepare("UPDATE empleado_vacaciones SET ESTADO = 'ACTIVO', FECHA_ACTUALIZACION = NOW() WHERE ID_VACACION = :id");
    $finalizeStmt = $pdo->prepare("UPDATE empleado_vacaciones SET ESTADO = 'FINALIZADO', FECHA_ACTUALIZACION = NOW() WHERE ID_VACACION = :id");

    $updated = false;

    foreach ($vacations as $vacation) {
        $estado = strtoupper((string)($vacation['ESTADO'] ?? ''));
        $vacationId = (int)$vacation['ID_VACACION'];

        $start = new DateTime($vacation['FECHA_INICIO']);
        $end = new DateTime($vacation['FECHA_FIN']);

        if ($estado === 'PROGRAMADO' && $start <= $today && $today <= $end) {
            $activateStmt->execute([':id' => $vacationId]);
            suspendEmployeeForVacation($pdo, $employeeId, $vacationId);
            $updated = true;
            continue;
        }

        if ($estado === 'ACTIVO' && $today > $end) {
            $finalizeStmt->execute([':id' => $vacationId]);
            $updated = true;
        }
    }

    // Ensure employee activation status matches active vacations after potential updates.
    reactivateEmployeeIfNeeded($pdo, $employeeId);
}

/**
 * Persist an estado_empleado_anterior value for a vacation when transitioning to ACTIVO.
 */
function attachPreviousEmployeeEstado(PDO $pdo, int $vacationId, string $previousEstado): void
{
    $stmt = $pdo->prepare('UPDATE empleado_vacaciones SET ESTADO_EMPLEADO_ANTERIOR = ?, FECHA_ACTUALIZACION = NOW() WHERE ID_VACACION = ? AND (ESTADO_EMPLEADO_ANTERIOR IS NULL OR ESTADO_EMPLEADO_ANTERIOR = "")');
    $stmt->execute([$previousEstado, $vacationId]);
}

/**
 * Build a normalized vacation record for API responses.
 */
function buildVacationPayload(array $row): array
{
    $start = new DateTime($row['FECHA_INICIO']);
    $end = new DateTime($row['FECHA_FIN']);
    $today = new DateTime('today');

    $diasTotales = (int)$start->diff($end)->days + 1;
    $diasRestantes = ($today > $end) ? 0 : max(0, (int)$today->diff($end)->days + 1);

    return [
        'id_vacacion' => (int)$row['ID_VACACION'],
        'id_empleado' => (int)$row['ID_EMPLEADO'],
        'fecha_inicio' => $row['FECHA_INICIO'],
        'fecha_fin' => $row['FECHA_FIN'],
        'motivo' => $row['MOTIVO'],
        'observaciones' => $row['OBSERVACIONES'],
        'estado' => $row['ESTADO'],
        'reactivacion_automatica' => ($row['REACTIVACION_AUTOMATICA'] ?? 'S') === 'S',
        'estado_empleado_anterior' => $row['ESTADO_EMPLEADO_ANTERIOR'],
        'dias_totales' => $diasTotales,
        'dias_restantes' => $diasRestantes,
        'en_curso' => $row['ESTADO'] === 'ACTIVO',
        'creado_por' => isset($row['CREADO_POR']) ? (int)$row['CREADO_POR'] : null,
        'fecha_creacion' => $row['FECHA_CREACION'] ?? null,
        'fecha_actualizacion' => $row['FECHA_ACTUALIZACION'] ?? null,
    ];
}

/**
 * Fetch a vacation row by ID.
 */
function getVacationById(PDO $pdo, int $vacationId): array
{
    $stmt = $pdo->prepare('SELECT * FROM empleado_vacaciones WHERE ID_VACACION = ?');
    $stmt->execute([$vacationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Vacación no encontrada.');
    }
    return $row;
}

/**
 * Retrieve a tuple of employee estado and activo.
 */
function getEmployeeStateSnapshot(PDO $pdo, int $employeeId): ?array
{
    $stmt = $pdo->prepare('SELECT ESTADO, ACTIVO FROM empleado WHERE ID_EMPLEADO = ?');
    $stmt->execute([$employeeId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        return null;
    }

    return [
        'estado' => $data['ESTADO'] ?? null,
        'activo' => $data['ACTIVO'] ?? null,
    ];
}

/**
 * Deactivate employee for an active vacation storing previous estado if needed.
 */
function suspendEmployeeForVacation(PDO $pdo, int $employeeId, int $vacationId): void
{
    $snapshot = getEmployeeStateSnapshot($pdo, $employeeId);
    if ($snapshot && ($snapshot['estado'] ?? 'A') !== 'I') {
        attachPreviousEmployeeEstado($pdo, $vacationId, (string)$snapshot['estado']);
    }

    updateEmployeeEstado($pdo, $employeeId, 'I', 'N');
}

/**
 * Reload and normalize a vacation by ID.
 */
function buildVacationPayloadById(PDO $pdo, int $vacationId): array
{
    $row = getVacationById($pdo, $vacationId);
    return buildVacationPayload($row);
}

