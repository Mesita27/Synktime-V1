<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json');
}

/**
 * Determina si una hora específica está en horario nocturno (21:00 - 06:00)
 */
function esHoraNocturna($hora) {
    $horaNum = (int)date('H', strtotime($hora));
    return $horaNum >= 21 || $horaNum < 6;
}

/**
 * Determina si un turno completo es nocturno basado en horarios personalizados
 */
function esTurnoNocturno($horaEntrada, $horaSalida) {
    // Si la hora de salida es menor que la de entrada, es turno nocturno
    if ($horaSalida && $horaEntrada && strtotime($horaSalida) < strtotime($horaEntrada)) {
        return true;
    }

    // Verificar si alguna de las horas está en horario nocturno
    if (esHoraNocturna($horaEntrada) || esHoraNocturna($horaSalida)) {
        return true;
    }

    return false;
}

/**
 * Formatea horas trabajadas en formato HH:MM
 */
function formatHorasTrabajadas($horasDecimal) {
    if (!$horasDecimal || $horasDecimal <= 0) {
        return '00:00';
    }

    $horas = floor($horasDecimal);
    $minutos = round(($horasDecimal - $horas) * 60);

    return sprintf('%02d:%02d', $horas, $minutos);
}

/**
 * Selecciona el horario más apropiado dentro del grupo de asistencias.
 * Prioriza horarios activos y, como desempate, la vigencia más reciente.
 */
function seleccionarHorarioPreferido(array $registros) {
    $candidatos = [];

    foreach ($registros as $registro) {
        if (empty($registro['ID_EMPLEADO_HORARIO'])) {
            continue;
        }

        $candidatos[] = [
            'registro' => $registro,
            'activo' => ($registro['HORARIO_ACTIVO'] ?? 'S') === 'S',
            'fecha_inicio' => $registro['FECHA_INICIO_VIGENCIA'] ?? null,
            'fecha_fin' => $registro['FECHA_FIN_VIGENCIA'] ?? null
        ];
    }

    if (empty($candidatos)) {
        return null;
    }

    usort($candidatos, function ($a, $b) {
        if ($a['activo'] !== $b['activo']) {
            return $a['activo'] ? -1 : 1;
        }

        $inicioA = $a['fecha_inicio'] ?? '0000-00-00';
        $inicioB = $b['fecha_inicio'] ?? '0000-00-00';
        if ($inicioA !== $inicioB) {
            return strcmp($inicioB, $inicioA);
        }

        $finA = $a['fecha_fin'] ?? '9999-12-31';
        $finB = $b['fecha_fin'] ?? '9999-12-31';
        if ($finA !== $finB) {
            return strcmp($finA, $finB);
        }

        $idA = $a['registro']['ID_EMPLEADO_HORARIO'] ?? 0;
        $idB = $b['registro']['ID_EMPLEADO_HORARIO'] ?? 0;
        return $idA <=> $idB;
    });

    return $candidatos[0]['registro'];
}

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    $userRole = $_SESSION['rol'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Parámetros de paginación
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
    $offset = ($page - 1) * $perPage;

    // Parámetros de filtro
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'estado_entrada' => $_GET['estado_entrada'] ?? null,
        'estado_salida' => $_GET['estado_salida'] ?? null,
        'fecha_desde' => $_GET['fecha_desde'] ?? null,
        'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        'tipo_reporte' => $_GET['tipo_reporte'] ?? null
    ];

    // Construir consulta base usando horarios personalizados
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    // Aplicar filtros de fecha
    if ($filtros['fecha_desde']) {
        $where[] = "a.FECHA >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }

    if ($filtros['fecha_hasta']) {
        $where[] = "a.FECHA <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }

    // Filtros por período rápido
    if ($filtros['tipo_reporte']) {
        switch ($filtros['tipo_reporte']) {
            case 'dia':
                $where[] = "a.FECHA = CURDATE()";
                break;
            case 'semana':
                $where[] = "YEARWEEK(a.FECHA, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'mes':
                $where[] = "YEAR(a.FECHA) = YEAR(CURDATE()) AND MONTH(a.FECHA) = MONTH(CURDATE())";
                break;
        }
    }

    // Filtros adicionales
    if ($filtros['codigo']) {
        $where[] = "e.ID_EMPLEADO = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['nombre']) {
        $where[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre)";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede'] && $filtros['sede'] !== 'Todas') {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento'] && $filtros['establecimiento'] !== 'Todos') {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    $whereClause = implode(' AND ', $where);

    // Consulta principal usando horarios personalizados
    $sql = "
        SELECT
            -- Información del empleado
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,

            -- Información de ubicación
            s.NOMBRE AS sede,
            est.NOMBRE AS establecimiento,

            -- Información de la asistencia
            a.FECHA,
            a.ID_ASISTENCIA,
            a.TIPO,

            -- Horarios programados desde EMPLEADO_HORARIO
            eh.ID_EMPLEADO_HORARIO,
            eh.HORA_ENTRADA AS HORA_ENTRADA_PROGRAMADA,
            eh.HORA_SALIDA AS HORA_SALIDA_PROGRAMADA,
            eh.TOLERANCIA,
            eh.DIAS_SEMANA,
            eh.FECHA_INICIO_VIGENCIA,
            eh.FECHA_FIN_VIGENCIA,
            eh.ACTIVO AS HORARIO_ACTIVO,

            -- Registros de entrada y salida
            entrada.HORA AS ENTRADA_HORA,
            entrada.ID_ASISTENCIA AS ENTRADA_ID,
            entrada.OBSERVACION AS OBSERVACION_ENTRADA,

            salida.HORA AS SALIDA_HORA,
            salida.ID_ASISTENCIA AS SALIDA_ID,
            salida.OBSERVACION AS OBSERVACION_SALIDA

        FROM EMPLEADO e

        -- Joins de ubicación
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE

        -- Join con asistencias
        LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO

        -- Join con horarios personalizados (vigentes)
        LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
            AND eh.FECHA_INICIO_VIGENCIA <= a.FECHA
            AND (eh.FECHA_FIN_VIGENCIA IS NULL OR eh.FECHA_FIN_VIGENCIA >= a.FECHA)

        -- Subconsulta para entrada más reciente del día
        LEFT JOIN (
            SELECT a1.ID_EMPLEADO, a1.FECHA, a1.HORA, a1.ID_ASISTENCIA, a1.OBSERVACION
            FROM ASISTENCIA a1
            WHERE a1.TIPO = 'ENTRADA'
            AND NOT EXISTS (
                SELECT 1 FROM ASISTENCIA a2
                WHERE a2.ID_EMPLEADO = a1.ID_EMPLEADO
                AND a2.FECHA = a1.FECHA
                AND a2.TIPO = 'ENTRADA'
                AND a2.ID_ASISTENCIA > a1.ID_ASISTENCIA
            )
        ) AS entrada ON e.ID_EMPLEADO = entrada.ID_EMPLEADO AND a.FECHA = entrada.FECHA

        -- Subconsulta para salida más reciente del día
        LEFT JOIN (
            SELECT a3.ID_EMPLEADO, a3.FECHA, a3.HORA, a3.ID_ASISTENCIA, a3.OBSERVACION
            FROM ASISTENCIA a3
            WHERE a3.TIPO = 'SALIDA'
            AND NOT EXISTS (
                SELECT 1 FROM ASISTENCIA a4
                WHERE a4.ID_EMPLEADO = a3.ID_EMPLEADO
                AND a4.FECHA = a3.FECHA
                AND a4.TIPO = 'SALIDA'
                AND a4.ID_ASISTENCIA > a3.ID_ASISTENCIA
            )
        ) AS salida ON e.ID_EMPLEADO = salida.ID_EMPLEADO AND a.FECHA = salida.FECHA

        WHERE {$whereClause}
        ORDER BY a.FECHA DESC, e.ID_EMPLEADO ASC
    ";

    // Obtener total de registros para paginación
    $countSql = "
        SELECT COUNT(DISTINCT CONCAT(e.ID_EMPLEADO, '_', a.FECHA)) as total
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO
        LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
            AND eh.FECHA_INICIO_VIGENCIA <= a.FECHA
            AND (eh.FECHA_FIN_VIGENCIA IS NULL OR eh.FECHA_FIN_VIGENCIA >= a.FECHA)
        WHERE {$whereClause}
    ";

    $stmtCount = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtener datos con paginación
    $sql .= " LIMIT :offset, :per_page";
    $params[':offset'] = $offset;
    $params[':per_page'] = $perPage;

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar datos para agrupar por empleado y fecha
    $processedData = [];
    $groupedData = [];

    // Agrupar por empleado y fecha
    foreach ($rawData as $row) {
        $key = $row['ID_EMPLEADO'] . '_' . $row['FECHA'];
        if (!isset($groupedData[$key])) {
            $groupedData[$key] = [
                'empleado' => $row,
                'asistencias' => []
            ];
        }
        $groupedData[$key]['asistencias'][] = $row;
    }

    // Procesar cada grupo
    foreach ($groupedData as $group) {
        $empleado = $group['empleado'];
        $asistencias = $group['asistencias'];

        // Encontrar entrada y salida
        $entrada = null;
        $salida = null;

        foreach ($asistencias as $asistencia) {
            if ($asistencia['TIPO'] === 'ENTRADA' && !$entrada) {
                $entrada = $asistencia;
            } elseif ($asistencia['TIPO'] === 'SALIDA' && !$salida) {
                $salida = $asistencia;
            }
        }

        $horario = seleccionarHorarioPreferido($asistencias) ?? ($asistencias[0] ?? []);
        if (!is_array($horario)) {
            $horario = [];
        }

        $estadoEntrada = 'Ausente';
        $estadoEntradaClase = 'badge-secondary';
        $horaEntrada = null;

        if ($entrada) {
            $horaEntrada = $entrada['ENTRADA_HORA'];
            $estadoCalculadoEntrada = calcularEstadoEntrada(
                $horario['HORA_ENTRADA_PROGRAMADA'] ?? null,
                $horaEntrada,
                $horario['TOLERANCIA'] ?? 0
            );

            switch ($estadoCalculadoEntrada) {
                case 'Temprano':
                    $estadoEntrada = 'Temprano';
                    $estadoEntradaClase = 'badge-success';
                    break;
                case 'Puntual':
                    $estadoEntrada = 'A Tiempo';
                    $estadoEntradaClase = 'badge-info';
                    break;
                case 'Tardanza':
                    $estadoEntrada = 'Tardanza';
                    $estadoEntradaClase = 'badge-warning';
                    break;
                default:
                    $estadoEntrada = 'Ausente';
                    $estadoEntradaClase = 'badge-secondary';
            }

            if ($estadoEntrada === 'Ausente' && empty($horario['HORA_ENTRADA_PROGRAMADA'])) {
                $estadoEntrada = 'Presente';
                $estadoEntradaClase = 'badge-info';
            }
        }

        // Verificar si es turno nocturno
        $esTurnoNocturno = esTurnoNocturno(
            $horario['HORA_ENTRADA_PROGRAMADA'] ?? null,
            $horario['HORA_SALIDA_PROGRAMADA'] ?? null
        );

        // Buscar salida del día siguiente para turnos nocturnos
        $salidaDiaSiguiente = null;
        $horaSalidaReal = null;
        $horaSalidaDisplay = null;
        $salidaCruzaDia = false;
        $estadoSalida = 'Ausente';
        $estadoSalidaClase = 'badge-secondary';

        if ($salida) {
            $horaSalidaReal = $salida['SALIDA_HORA'];
            $horaSalidaDisplay = $horaSalidaReal;
            if ($horario && !empty($horario['HORA_SALIDA_PROGRAMADA'])) {
                $estadoCalculadoSalida = calcularEstadoSalida(
                    $horario['HORA_SALIDA_PROGRAMADA'],
                    $horaSalidaReal,
                    $horario['TOLERANCIA'] ?? 0,
                    $esTurnoNocturno
                );

                switch ($estadoCalculadoSalida) {
                    case 'Temprano':
                        $estadoSalida = 'Temprano';
                        $estadoSalidaClase = 'badge-warning';
                        break;
                    case 'Puntual':
                        $estadoSalida = 'Puntual';
                        $estadoSalidaClase = 'badge-info';
                        break;
                    case 'Tardanza':
                        $estadoSalida = 'Tardanza';
                        $estadoSalidaClase = 'badge-success';
                        break;
                    default:
                        $estadoSalida = 'Ausente';
                        $estadoSalidaClase = 'badge-secondary';
                }
            } else {
                $estadoSalida = 'Registrada';
                $estadoSalidaClase = 'badge-info';
            }
        } elseif ($esTurnoNocturno && $entrada) {
            // Buscar salida en el día siguiente
            $fechaSiguiente = date('Y-m-d', strtotime($empleado['FECHA'] . ' +1 day'));

            foreach ($rawData as $checkAsistencia) {
                if ($checkAsistencia['ID_EMPLEADO'] == $empleado['ID_EMPLEADO'] &&
                    $checkAsistencia['FECHA'] == $fechaSiguiente &&
                    $checkAsistencia['TIPO'] == 'SALIDA') {
                    $salidaDiaSiguiente = $checkAsistencia;
                    $horaSalidaReal = $checkAsistencia['SALIDA_HORA'];
                    $horaSalidaDisplay = $horaSalidaReal . ' (' . date('d/m', strtotime($fechaSiguiente)) . ')';
                    $salidaCruzaDia = true;
                    if ($horario && !empty($horario['HORA_SALIDA_PROGRAMADA'])) {
                        $estadoCalculadoSalida = calcularEstadoSalida(
                            $horario['HORA_SALIDA_PROGRAMADA'],
                            $horaSalidaReal,
                            $horario['TOLERANCIA'] ?? 0,
                            true
                        );

                        switch ($estadoCalculadoSalida) {
                            case 'Temprano':
                                $estadoSalida = 'Temprano';
                                $estadoSalidaClase = 'badge-warning';
                                break;
                            case 'Puntual':
                                $estadoSalida = 'Puntual';
                                $estadoSalidaClase = 'badge-info';
                                break;
                            case 'Tardanza':
                                $estadoSalida = 'Tardanza';
                                $estadoSalidaClase = 'badge-success';
                                break;
                            default:
                                $estadoSalida = 'Ausente';
                                $estadoSalidaClase = 'badge-secondary';
                        }
                    } else {
                        $estadoSalida = 'Registrada';
                        $estadoSalidaClase = 'badge-info';
                    }
                    break;
                }
            }
        }

        if ($horaSalidaDisplay === null && $horaSalidaReal) {
            $horaSalidaDisplay = $horaSalidaReal;
        }

        if (!$salida && !$salidaDiaSiguiente && $entrada && !$horaSalidaReal) {
            $estadoSalida = 'Ausente';
            $estadoSalidaClase = 'badge-secondary';
        }

        // Calcular horas trabajadas
        $horasTrabajadas = 0;
        $horasTrabajadasFormateadas = '00:00';

        if ($horaEntrada && $horaSalidaReal) {
            $fechaSalida = $salidaCruzaDia || ($horaSalidaReal < $horaEntrada) ?
                date('Y-m-d', strtotime($empleado['FECHA'] . ' +1 day')) :
                $empleado['FECHA'];

            $tsEntrada = strtotime($empleado['FECHA'] . ' ' . $horaEntrada);
            $tsSalida = strtotime($fechaSalida . ' ' . $horaSalidaReal);

            if ($tsSalida > $tsEntrada) {
                $horasTrabajadas = round(($tsSalida - $tsEntrada) / 3600, 2);
                $horasTrabajadasFormateadas = formatHorasTrabajadas($horasTrabajadas);
            }
        }

        // Crear registro procesado
        $processedData[] = [
            'ID_ASISTENCIA' => $entrada['ENTRADA_ID'] ?? $empleado['ID_EMPLEADO'] . '_' . $empleado['FECHA'],
            'codigo' => $empleado['ID_EMPLEADO'],
            'nombre' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
            'sede' => $empleado['sede'],
            'establecimiento' => $empleado['establecimiento'],
            'fecha' => $empleado['FECHA'],
            'hora_entrada' => $horaEntrada,
            'estado_entrada' => $estadoEntrada,
            'estado_entrada_clase' => $estadoEntradaClase,
            'hora_salida' => $horaSalidaDisplay,
            'estado_salida' => $estadoSalida,
            'estado_salida_clase' => $estadoSalidaClase,
            'horas_trabajadas' => $horasTrabajadas,
            'horas_trabajadas_formateadas' => $horasTrabajadasFormateadas,
            'es_turno_nocturno' => $esTurnoNocturno,
            'observacion' => $entrada['OBSERVACION_ENTRADA'] ?? '',
            'horario' => [
                'id' => $horario['ID_EMPLEADO_HORARIO'] ?? null,
                'hora_entrada' => $horario['HORA_ENTRADA_PROGRAMADA'] ?? null,
                'hora_salida' => $horario['HORA_SALIDA_PROGRAMADA'] ?? null,
                'tolerancia' => $horario['TOLERANCIA'] ?? 0,
                'dias_semana' => $horario['DIAS_SEMANA'] ?? null,
                'activo' => $horario['HORARIO_ACTIVO'] ?? null,
                'fecha_inicio_vigencia' => $horario['FECHA_INICIO_VIGENCIA'] ?? null,
                'fecha_fin_vigencia' => $horario['FECHA_FIN_VIGENCIA'] ?? null
            ],
            'registro_dia_siguiente' => $salidaDiaSiguiente
        ];
    }

    // Aplicar filtro de estado de entrada si se especificó
    if ($filtros['estado_entrada'] && $filtros['estado_entrada'] !== 'Todos') {
        $processedData = array_filter($processedData, function($item) use ($filtros) {
            return $item['estado_entrada'] === $filtros['estado_entrada'];
        });
    }

    if ($filtros['estado_salida'] && $filtros['estado_salida'] !== 'Todos') {
        $processedData = array_filter($processedData, function($item) use ($filtros) {
            return $item['estado_salida'] === $filtros['estado_salida'];
        });
    }

    // Calcular estadísticas
    $stats = [
        'total_empleados' => count(array_unique(array_column($processedData, 'codigo'))),
        'total_presentes' => count(array_filter($processedData, function($item) {
            return $item['estado_entrada'] !== 'Ausente' && $item['fecha'] === date('Y-m-d');
        })),
        'total_ausentes' => count(array_filter($processedData, function($item) {
            return $item['estado_entrada'] === 'Ausente' && $item['fecha'] === date('Y-m-d');
        })),
        'total_tardanzas' => count(array_filter($processedData, function($item) {
            return $item['estado_entrada'] === 'Tardanza' && $item['fecha'] === date('Y-m-d');
        }))
    ];

    // Respuesta JSON
    echo json_encode([
        'success' => true,
        'data' => array_values($processedData),
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $totalRecords,
            'total_pages' => ceil($totalRecords / $perPage)
        ],
        'stats' => $stats,
        'filters' => $filtros
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>