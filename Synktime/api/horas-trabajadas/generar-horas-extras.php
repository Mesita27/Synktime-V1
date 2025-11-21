<?php
/**
 * API: Generate overtime records from attendance data
 * This endpoint analyzes attendance records and creates pending overtime approval records
 * Only accessible by ADMIN users
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../auth/session.php';

// Check if user is authenticated and has ADMIN role
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol ADMIN']);
    exit;
}

try {
    global $conn;

    // Get parameters
    $fechaDesde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : date('Y-m-d', strtotime('-30 days'));
    $fechaHasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : date('Y-m-d');
    $empleados = isset($_POST['empleados']) ? $_POST['empleados'] : [];

    // Include helper functions from the main hours calculation API
    require_once __DIR__ . '/get-horas.php';

    $registrosCreados = 0;
    $registrosExistentes = 0;

    // If specific employees provided, process only those
    if (!empty($empleados)) {
        foreach ($empleados as $idEmpleado) {
            $result = procesarHorasExtrasEmpleado($idEmpleado, $fechaDesde, $fechaHasta, $conn);
            $registrosCreados += $result['creados'];
            $registrosExistentes += $result['existentes'];
        }
    } else {
        // Process all active employees
        $stmtEmpleados = $conn->prepare("SELECT ID_EMPLEADO FROM empleado WHERE ACTIVO = 'S'");
        $stmtEmpleados->execute();
        $empleadosActivos = $stmtEmpleados->fetchAll(PDO::FETCH_COLUMN);

        foreach ($empleadosActivos as $idEmpleado) {
            $result = procesarHorasExtrasEmpleado($idEmpleado, $fechaDesde, $fechaHasta, $conn);
            $registrosCreados += $result['creados'];
            $registrosExistentes += $result['existentes'];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Procesamiento completado',
        'registros_creados' => $registrosCreados,
        'registros_existentes' => $registrosExistentes,
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta
    ]);

} catch (Exception $e) {
    error_log("Error in generar-horas-extras.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}

/**
 * Process overtime hours for a specific employee
 */
function procesarHorasExtrasEmpleado($idEmpleado, $fechaDesde, $fechaHasta, $conn) {
    $creados = 0;
    $existentes = 0;

    try {
        // Get attendance records for the employee in the date range
        $query = "
            SELECT
                a.ID_ASISTENCIA,
                a.FECHA,
                a.HORA as HORA_ENTRADA,
                a.TIPO,
                a.ID_EMPLEADO_HORARIO,
                ehp.HORA_ENTRADA as HORARIO_ENTRADA,
                ehp.HORA_SALIDA as HORARIO_SALIDA,
                ehp.NOMBRE_TURNO
            FROM asistencia a
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
            WHERE a.ID_EMPLEADO = ?
            AND a.FECHA BETWEEN ? AND ?
            AND a.TIPO IN ('ENTRADA', 'SALIDA')
            ORDER BY a.FECHA ASC, a.HORA ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute([$idEmpleado, $fechaDesde, $fechaHasta]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by date to process complete workdays
        $registrosPorFecha = [];
        foreach ($registros as $registro) {
            $fecha = $registro['FECHA'];
            if (!isset($registrosPorFecha[$fecha])) {
                $registrosPorFecha[$fecha] = [];
            }
            $registrosPorFecha[$fecha][] = $registro;
        }

        // Process each date
        foreach ($registrosPorFecha as $fecha => $registrosDia) {
            $horasExtrasDia = calcularHorasExtrasDia($registrosDia, $fecha, $idEmpleado, $conn);

            foreach ($horasExtrasDia as $horaExtra) {
                // Check if this overtime record already exists
                $checkStmt = $conn->prepare("
                    SELECT ID_HORAS_EXTRAS FROM horas_extras_aprobacion
                    WHERE ID_EMPLEADO = ?
                    AND FECHA = ?
                    AND HORA_INICIO = ?
                    AND HORA_FIN = ?
                    AND HORAS_EXTRAS = ?
                    AND TIPO_EXTRA = ?
                ");

                $checkStmt->execute([
                    $idEmpleado,
                    $fecha,
                    $horaExtra['hora_inicio'],
                    $horaExtra['hora_fin'],
                    $horaExtra['horas_extras'],
                    $horaExtra['tipo_extra']
                ]);

                if ($checkStmt->rowCount() == 0) {
                    // Create new overtime record
                    $insertStmt = $conn->prepare("
                        INSERT INTO horas_extras_aprobacion
                        (ID_EMPLEADO, ID_EMPLEADO_HORARIO, FECHA, HORA_INICIO, HORA_FIN, HORAS_EXTRAS, TIPO_EXTRA, TIPO_HORARIO, ESTADO_APROBACION)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
                    ");

                    $insertStmt->execute([
                        $idEmpleado,
                        $horaExtra['id_empleado_horario'],
                        $fecha,
                        $horaExtra['hora_inicio'],
                        $horaExtra['hora_fin'],
                        $horaExtra['horas_extras'],
                        $horaExtra['tipo_extra'],
                        $horaExtra['tipo_horario']
                    ]);

                    $creados++;
                } else {
                    $existentes++;
                }
            }
        }

    } catch (Exception $e) {
        error_log("Error procesando empleado $idEmpleado: " . $e->getMessage());
    }

    return ['creados' => $creados, 'existentes' => $existentes];
}

/**
 * Calculate overtime hours for a specific day
 */
function calcularHorasExtrasDia($registrosDia, $fecha, $idEmpleado, $conn) {
    $horasExtras = [];

    // Find entry and exit times
    $horaEntrada = null;
    $horaSalida = null;
    $idEmpleadoHorario = null;

    foreach ($registrosDia as $registro) {
        if ($registro['TIPO'] === 'ENTRADA') {
            $horaEntrada = $registro['HORA_ENTRADA'];
            $idEmpleadoHorario = $registro['ID_EMPLEADO_HORARIO'];
        } elseif ($registro['TIPO'] === 'SALIDA') {
            $horaSalida = $registro['HORA_ENTRADA']; // HORA field contains the time
        }
    }

    if (!$horaEntrada || !$horaSalida) {
        return $horasExtras; // No complete workday
    }

    // Get schedule for this employee and date
    $horario = obtenerHorarioEmpleadoFecha($idEmpleado, $fecha, $conn);

    if (!$horario) {
        return $horasExtras; // No schedule found
    }

    $horaEntradaProg = $horario['HORA_ENTRADA'];
    $horaSalidaProg = $horario['HORA_SALIDA'];

    // Calculate regular vs extra hours
    $resultado = calcularHorasRegularesExtras($horaEntrada, $horaSalida, $horaEntradaProg, $horaSalidaProg);

    // Process extra hours segments
    foreach ($resultado['segmentos'] as $segmento) {
        if ($segmento['tipo'] === 'extra') {
            // Determine if it's before or after schedule
            $horaInicioSegmento = strtotime($segmento['hora_inicio']);
            $horaEntradaProgTime = strtotime($horaEntradaProg);

            $tipoExtra = ($horaInicioSegmento < $horaEntradaProgTime) ? 'antes' : 'despues';

            // Determine if it's diurnal or nocturnal
            $horaInicio = strtotime($segmento['hora_inicio']);
            $horaFin = strtotime($segmento['hora_fin']);

            $tipoHorario = determinarTipoHorario($horaInicio, $horaFin, $fecha, $horaEntrada, $horaSalida);

            $horasExtras[] = [
                'id_empleado_horario' => $idEmpleadoHorario,
                'hora_inicio' => $segmento['hora_inicio'],
                'hora_fin' => $segmento['hora_fin'],
                'horas_extras' => $segmento['horas'],
                'tipo_extra' => $tipoExtra,
                'tipo_horario' => $tipoHorario
            ];
        }
    }

    return $horasExtras;
}

/**
 * Get employee schedule for a specific date
 */
function obtenerHorarioEmpleadoFecha($idEmpleado, $fecha, $conn) {
    // Use the same logic as the main API
    $diaSemana = date('N', strtotime($fecha)); // 1=Monday, 7=Sunday

    $stmt = $conn->prepare("
        SELECT HORA_ENTRADA, HORA_SALIDA, NOMBRE_TURNO
        FROM empleado_horario_personalizado
        WHERE ID_EMPLEADO = ?
        AND ID_DIA = ?
        AND ACTIVO = 'S'
        AND (FECHA_DESDE IS NULL OR FECHA_DESDE <= ?)
        AND (FECHA_HASTA IS NULL OR FECHA_HASTA >= ?)
        ORDER BY ORDEN_TURNO ASC
        LIMIT 1
    ");

    $stmt->execute([$idEmpleado, $diaSemana, $fecha, $fecha]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Determine the type of schedule (diurnal/nocturnal/dominical)
 */
function determinarTipoHorario($horaInicio, $horaFin, $fecha, $horaEntradaReal = null, $horaSalidaReal = null) {
    // Check if it's a holiday/Sunday
    $esEspecial = esFechaEspecial($fecha);

    // CRÍTICO: Si el turno real cruza medianoche (salida < entrada), es nocturno completo
    // independientemente del horario programado o segmentos individuales
    if ($horaEntradaReal && $horaSalidaReal && $horaSalidaReal < $horaEntradaReal) {
        return $esEspecial ? 'nocturna_dominical' : 'nocturna';
    }

    // Check if it's night hours (9PM-6AM)
    $horaInicioInt = (int)date('H', $horaInicio);
    $horaFinInt = (int)date('H', $horaFin);

    $esNocturno = ($horaInicioInt >= 21 || $horaInicioInt <= 6) || ($horaFinInt >= 21 || $horaFinInt <= 6);

    if ($esEspecial && $esNocturno) {
        return 'nocturna_dominical';
    } elseif ($esEspecial) {
        return 'diurna_dominical';
    } elseif ($esNocturno) {
        return 'nocturna';
    } else {
        return 'diurna';
    }
}

/**
 * Check if date is special (Sunday or holiday)
 */
function esFechaEspecial($fecha) {
    $diaSemana = date('N', strtotime($fecha));

    // Sunday
    if ($diaSemana == 7) {
        return true;
    }

    // Check festivos table
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM festivos WHERE FECHA = ?");
    $stmt->execute([$fecha]);

    if ($stmt->fetchColumn() > 0) {
        return true;
    }

    // Check dias_civicos table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM dias_civicos WHERE FECHA = ?");
    $stmt->execute([$fecha]);

    return $stmt->fetchColumn() > 0;
}
?>