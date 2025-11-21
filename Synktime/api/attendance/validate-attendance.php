<?php
// Limpiar cualquier output anterior

// Configurar zona horaria de Bogotá, Colombia
require_once __DIR__ . '/../../config/timezone.php';
ob_clean();

// Headers para JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

// Función de utilidad para normalizar horas al formato HH:MM:SS
function normalizarHora($hora) {
    if (empty($hora)) {
        return '00:00:00';
    }

    // Si ya tiene formato HH:MM:SS, devolver tal cual
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
        return $hora;
    }

    // Si tiene formato HH:MM, agregar :00
    if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
        return $hora . ':00';
    }

    // Si tiene formato H:MM, agregar 0 al inicio y :00 al final
    if (preg_match('/^\d{1}:\d{2}$/', $hora)) {
        return '0' . $hora . ':00';
    }

    // Para cualquier otro formato, intentar parsear con strtotime
    $timestamp = strtotime($hora);
    if ($timestamp !== false) {
        return date('H:i:s', $timestamp);
    }

    // Si no se puede parsear, devolver 00:00:00
    return '00:00:00';
}

$employeeId = $_GET['employee_id'] ?? '';
$attendanceType = $_GET['attendance_type'] ?? 'ENTRADA'; // ENTRADA o SALIDA

if (!$employeeId) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
    exit;
}

try {
    $currentDate = getBogotaDate();
    $currentTime = getBogotaTime();
    $currentDateTime = getBogotaDateTime();

    // 1. Verificar si hay entradas abiertas (sin salida correspondiente)
    $sqlOpenEntries = "SELECT COUNT(*) as open_entries
                      FROM asistencia
                      WHERE ID_EMPLEADO = ? AND TIPO = 'ENTRADA'
                      AND FECHA = ?
                      AND NOT EXISTS (
                          SELECT 1 FROM asistencia a2
                          WHERE a2.ID_EMPLEADO = ASISTENCIA.ID_EMPLEADO
                          AND a2.FECHA = ASISTENCIA.FECHA
                          AND a2.HORA > ASISTENCIA.HORA
                          AND a2.TIPO = 'SALIDA'
                      )";

    $stmt = $conn->prepare($sqlOpenEntries);
    $stmt->execute([$employeeId, $currentDate]);
    $openEntriesResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasOpenEntries = $openEntriesResult['open_entries'] > 0;

    // 2. Obtener horarios disponibles para hoy - incluyendo horarios personalizados
    // Primero buscar horarios personalizados
    $sqlPersonalizados = "SELECT 
                            ehp.ID_EMPLEADO_HORARIO as ID_HORARIO,
                            ehp.HORA_ENTRADA, 
                            ehp.HORA_SALIDA,
                            ehp.NOMBRE_TURNO as NOMBRE, 
                            ehp.TOLERANCIA,
                            'personalizado' as tipo_horario
                         FROM empleado_horario_personalizado ehp
                         WHERE ehp.ID_EMPLEADO = ?
                         AND ehp.ID_DIA = WEEKDAY(?) + 1
                         AND ehp.ACTIVO = 'S'
                         AND ehp.FECHA_DESDE <= ?
                         AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
                         ORDER BY ehp.ORDEN_TURNO, ehp.HORA_ENTRADA";

    $stmt = $conn->prepare($sqlPersonalizados);
    $stmt->execute([$employeeId, $currentDate, $currentDate, $currentDate]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si no hay horarios personalizados, buscar horarios tradicionales
    if (empty($schedules)) {
        $sqlTradicionales = "SELECT h.ID_HORARIO, h.HORA_ENTRADA, h.HORA_SALIDA,
                                h.NOMBRE, h.TOLERANCIA, 'tradicional' as tipo_horario
                             FROM horario h
                             JOIN HORARIO_DIA hd ON h.ID_HORARIO = hd.ID_HORARIO
                             JOIN EMPLEADO_HORARIO eh ON h.ID_HORARIO = eh.ID_HORARIO
                             WHERE eh.ID_EMPLEADO = ?
                             AND hd.ID_DIA = WEEKDAY(?) + 1
                             AND eh.FECHA_DESDE <= ?
                             AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
                             ORDER BY h.HORA_ENTRADA";

        $stmt = $conn->prepare($sqlTradicionales);
        $stmt->execute([$employeeId, $currentDate, $currentDate, $currentDate]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Verificar asistencias ya registradas hoy
    $sqlTodayAttendance = "SELECT HORA, TIPO
                          FROM asistencia
                          WHERE ID_EMPLEADO = ?
                          AND FECHA = ?
                          ORDER BY HORA";

    $stmt = $conn->prepare($sqlTodayAttendance);
    $stmt->execute([$employeeId, $currentDate]);
    $todayAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Verificar si ya se completaron todos los horarios
    $completedSchedules = 0;
    $availableSchedules = count($schedules);

    foreach ($schedules as $schedule) {
        $scheduleStart = $schedule['HORA_ENTRADA'];
        $scheduleEnd = $schedule['HORA_SALIDA'];
        $hasEntry = false;
        $hasExit = false;

        foreach ($todayAttendance as $attendance) {
            $hora_asistencia = normalizarHora($attendance['HORA']);
            $hora_entrada = normalizarHora($scheduleStart);

            if ($attendance['TIPO'] === 'ENTRADA' && $hora_asistencia >= $hora_entrada) {
                $hasEntry = true;
            }
            if ($attendance['TIPO'] === 'SALIDA' && $hora_asistencia >= $hora_entrada) {
                $hasExit = true;
            }
        }

        if ($hasEntry && $hasExit) {
            $completedSchedules++;
        }
    }

    // 5. Verificar límite de tiempo desde el horario más cercano
    $exceededTimeLimit = false;
    $currentScheduleLimit = null;

    if (!empty($schedules)) {
        // Encontrar el horario más cercano a la hora actual
        $closestSchedule = null;
        $minTimeDiff = PHP_INT_MAX;

        foreach ($schedules as $schedule) {
            $scheduleStartTime = strtotime($currentDate . ' ' . $schedule['HORA_ENTRADA']);
            $timeDiff = abs(strtotime($currentDateTime) - $scheduleStartTime);

            if ($timeDiff < $minTimeDiff) {
                $minTimeDiff = $timeDiff;
                $closestSchedule = $schedule;
            }
        }

        if ($closestSchedule) {
            // Verificar si ya se registró entrada para este horario
            $hasEntryForSchedule = false;
            foreach ($todayAttendance as $attendance) {
                $hora_asistencia = normalizarHora($attendance['HORA']);
                $hora_entrada_horario = normalizarHora($closestSchedule['HORA_ENTRADA']);
                $hora_salida_horario = normalizarHora($closestSchedule['HORA_SALIDA']);

                if ($attendance['TIPO'] === 'ENTRADA' &&
                    $hora_asistencia >= $hora_entrada_horario &&
                    $hora_asistencia <= $hora_salida_horario) {
                    $hasEntryForSchedule = true;
                    break;
                }
            }

            // Si ya hay entrada para este horario, verificar el límite de 8 horas
            if ($hasEntryForSchedule) {
                $scheduleStartDateTime = $currentDate . ' ' . $closestSchedule['HORA_ENTRADA'];
                $hoursDiff = (strtotime($currentDateTime) - strtotime($scheduleStartDateTime)) / 3600;
                $exceededTimeLimit = $hoursDiff > 8;
                $currentScheduleLimit = $closestSchedule['HORA_ENTRADA'];
            }
        }
    }

    // Determinar si se permite el registro
    $canRegister = true;
    $blockReason = '';

    if ($attendanceType === 'ENTRADA') {
        if ($hasOpenEntries) {
            $canRegister = false;
            $blockReason = 'Ya tiene una entrada abierta sin salida correspondiente. Debe registrar la salida primero.';
        } elseif ($completedSchedules >= $availableSchedules && $availableSchedules > 0) {
            $canRegister = false;
            $blockReason = 'Ya ha completado todos los horarios disponibles para hoy.';
        } elseif ($exceededTimeLimit) {
            $canRegister = false;
            $blockReason = 'Han transcurrido más de 8 horas desde el inicio de su horario actual (' . $currentScheduleLimit . '). No se permite registrar nueva asistencia.';
        }
    } elseif ($attendanceType === 'SALIDA') {
        if (!$hasOpenEntries) {
            $canRegister = false;
            $blockReason = 'No tiene entradas abiertas para registrar salida.';
        }
    }

    echo json_encode([
        'success' => true,
        'can_register' => $canRegister,
        'block_reason' => $blockReason,
        'validation_details' => [
            'has_open_entries' => $hasOpenEntries,
            'available_schedules' => $availableSchedules,
            'completed_schedules' => $completedSchedules,
            'exceeded_time_limit' => $exceededTimeLimit,
            'current_schedule_limit' => $currentScheduleLimit,
            'current_time' => $currentTime
        ],
        'schedules' => $schedules,
        'today_attendance' => $todayAttendance
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error al validar asistencia: ' . $e->getMessage()
    ]);
}
?>
