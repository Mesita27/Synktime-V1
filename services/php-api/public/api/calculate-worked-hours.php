<?php
/**
 * API para calcular horas trabajadas con detalle completo
 * Incluye horas extras, descansos y validaciones tradicionales
 * SNKTIME Biometric System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session.php';

// Verificar autenticación
requireAuth();

header('Content-Type: application/json');

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;

    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Parámetros de consulta
    $employeeId = $_GET['employee_id'] ?? null;
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $sedeId = $_GET['sede_id'] ?? null;
    $establecimientoId = $_GET['establecimiento_id'] ?? null;

    // Validar fechas
    if (strtotime($fechaInicio) > strtotime($fechaFin)) {
        throw new Exception('La fecha de inicio no puede ser posterior a la fecha de fin');
    }

    // Construir consulta base
    $where = ["e.ID_EMPRESA = :empresa_id", "e.ESTADO = 'A'", "e.ACTIVO = 'S'"];
    $params = [':empresa_id' => $empresaId];

    if ($employeeId) {
        $where[] = "e.ID_EMPLEADO = :employee_id";
        $params[':employee_id'] = $employeeId;
    }

    if ($sedeId) {
        $where[] = "s.ID_SEDE = :sede_id";
        $params[':sede_id'] = $sedeId;
    }

    if ($establecimientoId) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento_id";
        $params[':establecimiento_id'] = $establecimientoId;
    }

    $whereClause = implode(' AND ', $where);

    // Obtener empleados con sus registros de asistencia en el período
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
            s.NOMBRE AS SEDE_NOMBRE
        FROM EMPLEADO e
        LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE {$whereClause}
        ORDER BY e.NOMBRE, e.APELLIDO
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $resultados = [];

    while ($empleado = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $idEmpleado = $empleado['ID_EMPLEADO'];

        // Obtener registros de asistencia para el período
        $stmtAsistencia = $conn->prepare("
            SELECT
                a.FECHA,
                a.TIPO,
                a.HORA,
                a.TARDANZA,
                h.NOMBRE AS HORARIO_NOMBRE,
                h.HORA_ENTRADA,
                h.HORA_SALIDA,
                h.TOLERANCIA
            FROM ASISTENCIA a
            LEFT JOIN HORARIO h ON a.ID_HORARIO = h.ID_HORARIO
            WHERE a.ID_EMPLEADO = ?
              AND a.FECHA BETWEEN ? AND ?
            ORDER BY a.FECHA, a.HORA
        ");
        $stmtAsistencia->execute([$idEmpleado, $fechaInicio, $fechaFin]);
        $registros = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

        // Procesar registros por día
        $registrosPorDia = [];
        foreach ($registros as $registro) {
            $fecha = $registro['FECHA'];
            if (!isset($registrosPorDia[$fecha])) {
                $registrosPorDia[$fecha] = [];
            }
            $registrosPorDia[$fecha][] = $registro;
        }

        // Calcular horas trabajadas por día
        $diasCalculados = [];
        $totalHorasTrabajadas = 0;
        $totalHorasExtras = 0;
        $totalDiasTrabajados = 0;

        foreach ($registrosPorDia as $fecha => $registrosDia) {
            $calculoDia = calcularHorasTrabajadasDiaDetallado($registrosDia, $fecha);
            $diasCalculados[] = array_merge(['fecha' => $fecha], $calculoDia);

            if ($calculoDia['horas_trabajadas'] > 0) {
                $totalHorasTrabajadas += $calculoDia['horas_trabajadas'];
                $totalHorasExtras += $calculoDia['horas_extras'];
                $totalDiasTrabajados++;
            }
        }

        $resultados[] = [
            'empleado' => [
                'id' => $idEmpleado,
                'nombre_completo' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'dni' => $empleado['DNI'],
                'establecimiento' => $empleado['ESTABLECIMIENTO_NOMBRE'],
                'sede' => $empleado['SEDE_NOMBRE']
            ],
            'periodo' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ],
            'resumen' => [
                'total_dias_trabajados' => $totalDiasTrabajados,
                'total_horas_trabajadas' => round($totalHorasTrabajadas, 2),
                'total_horas_extras' => round($totalHorasExtras, 2),
                'promedio_horas_diarias' => $totalDiasTrabajados > 0 ? round($totalHorasTrabajadas / $totalDiasTrabajados, 2) : 0
            ],
            'detalle_por_dia' => $diasCalculados
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'metadata' => [
            'fecha_generacion' => date('Y-m-d H:i:s'),
            'total_empleados' => count($resultados),
            'periodo_consultado' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log('Error en calculate-worked-hours.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al calcular horas trabajadas: ' . $e->getMessage()
    ]);
}

/**
 * Calcula las horas trabajadas de un día con detalle completo
 */
function calcularHorasTrabajadasDiaDetallado($registrosDia, $fecha) {
    // Separar registros por tipo
    $entradas = [];
    $salidas = [];
    $descansos = [];

    foreach ($registrosDia as $registro) {
        switch ($registro['TIPO']) {
            case 'ENTRADA':
                $entradas[] = [
                    'hora' => $registro['HORA'],
                    'tardanza' => $registro['TARDANZA'],
                    'horario_entrada' => $registro['HORA_ENTRADA'],
                    'tolerancia' => $registro['TOLERANCIA']
                ];
                break;
            case 'SALIDA':
                $salidas[] = $registro['HORA'];
                break;
            case 'DESCANSO_INICIO':
                $descansos[] = ['inicio' => $registro['HORA']];
                break;
            case 'DESCANSO_FIN':
                if (!empty($descansos) && !isset(end($descansos)['fin'])) {
                    $descansos[key($descansos)]['fin'] = $registro['HORA'];
                }
                break;
        }
    }

    $totalMinutosTrabajados = 0;
    $totalMinutosExtras = 0;
    $totalMinutosDescanso = 0;
    $jornadas = [];

    // Calcular tiempo trabajado por cada jornada (entrada-salida)
    $numJornadas = min(count($entradas), count($salidas));
    for ($i = 0; $i < $numJornadas; $i++) {
        $entrada = $entradas[$i];
        $salida = $salidas[$i];

        $horaEntrada = strtotime($fecha . ' ' . $entrada['hora']);
        $horaSalida = strtotime($fecha . ' ' . $salida);

        if ($horaSalida > $horaEntrada) {
            $minutosJornada = ($horaSalida - $horaEntrada) / 60;

            // Calcular tiempo de descanso dentro de esta jornada
            $minutosDescansoJornada = 0;
            foreach ($descansos as $descanso) {
                if (isset($descanso['inicio']) && isset($descanso['fin'])) {
                    $inicioDescanso = strtotime($fecha . ' ' . $descanso['inicio']);
                    $finDescanso = strtotime($fecha . ' ' . $descanso['fin']);

                    // Verificar si el descanso está completamente dentro de la jornada
                    if ($inicioDescanso >= $horaEntrada && $finDescanso <= $horaSalida && $finDescanso > $inicioDescanso) {
                        $minutosDescansoJornada += ($finDescanso - $inicioDescanso) / 60;
                    }
                }
            }

            // Minutos trabajados en esta jornada (sin descansos)
            $minutosTrabajadosJornada = $minutosJornada - $minutosDescansoJornada;

            // Calcular horas extras (asumiendo jornada estándar de 8 horas = 480 minutos)
            $jornadaEstandar = 480; // 8 horas
            if ($minutosTrabajadosJornada > $jornadaEstandar) {
                $minutosExtrasJornada = $minutosTrabajadosJornada - $jornadaEstandar;
                $minutosTrabajadosJornada = $jornadaEstandar;
            } else {
                $minutosExtrasJornada = 0;
            }

            $totalMinutosTrabajados += $minutosTrabajadosJornada;
            $totalMinutosExtras += $minutosExtrasJornada;
            $totalMinutosDescanso += $minutosDescansoJornada;

            $jornadas[] = [
                'entrada' => $entrada['hora'],
                'salida' => $salida,
                'minutos_jornada' => round($minutosJornada, 0),
                'minutos_descanso' => round($minutosDescansoJornada, 0),
                'minutos_trabajados' => round($minutosTrabajadosJornada, 0),
                'minutos_extras' => round($minutosExtrasJornada, 0),
                'tardanza' => $entrada['tardanza']
            ];
        }
    }

    return [
        'horas_trabajadas' => round($totalMinutosTrabajados / 60, 2),
        'horas_extras' => round($totalMinutosExtras / 60, 2),
        'horas_descanso' => round($totalMinutosDescanso / 60, 2),
        'minutos_trabajados' => round($totalMinutosTrabajados, 0),
        'minutos_extras' => round($totalMinutosExtras, 0),
        'minutos_descanso' => round($totalMinutosDescanso, 0),
        'num_jornadas' => $numJornadas,
        'jornadas' => $jornadas,
        'registros_totales' => count($registrosDia),
        'estado' => $numJornadas > 0 ? 'COMPLETADO' : (count($registrosDia) > 0 ? 'INCOMPLETO' : 'SIN_REGISTROS')
    ];
}
?>
