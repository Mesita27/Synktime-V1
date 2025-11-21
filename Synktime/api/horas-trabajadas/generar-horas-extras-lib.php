<?php
/**
 * Funciones para generación automática de horas extras
 * Este archivo contiene las funciones necesarias para generar horas extras automáticamente
 */

/**
 * FUNCIONES AUXILIARES PARA CÁLCULO JERÁRQUICO
 * Copiadas de get-horas.php para mantener consistencia
 */

function esDomingo($fecha) {
    return date('w', strtotime($fecha)) == 0;
}

function esFestivo($fecha, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM festivos
        WHERE FECHA = ?
        AND ACTIVO = 'S'
    ");
    $stmt->execute([$fecha]);
    return $stmt->fetch()['count'] > 0;
}

function esDiaCivico($fecha, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM dias_civicos
        WHERE FECHA = ?
        AND ESTADO = 'A'
    ");
    $stmt->execute([$fecha]);
    return $stmt->fetch()['count'] > 0;
}

function esFechaEspecial($fecha, $pdo) {
    return esDomingo($fecha) || esFestivo($fecha, $pdo) || esDiaCivico($fecha, $pdo);
}

function timeToMinutes($time) {
    list($hours, $minutes) = explode(':', $time);
    return ($hours * 60) + $minutes;
}

function minutesToTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

function esMinutoNocturno($minuto) {
    $hora = floor($minuto / 60);
    $min = $minuto % 60;

    // Horario nocturno: 21:00 (21*60 = 1260) hasta 06:00 (6*60 = 360)
    // Pero considerando que puede cruzar medianoche
    if ($hora >= 21 || $hora < 6) {
        return true;
    }

    return false;
}

function calculateHoursWithHierarchyLegacy($horaEntrada, $horaSalida, $fecha, $horaEntradaProg, $horaSalidaProg, $pdo) {
    // Inicializar resultado con el formato esperado por el código existente
    $resultado = [
        'horas_regulares' => 0,
        'recargo_nocturno' => 0,
        'recargo_dominical_festivo' => 0,
        'recargo_nocturno_dominical_festivo' => 0,
        'extra_diurna' => 0,
        'extra_nocturna' => 0,
        'extra_diurna_dominical_festiva' => 0,
        'extra_nocturna_dominical_festiva' => 0,
        'segmentos' => []
    ];

    // Convertir horas a minutos para cálculo preciso
    $entradaMin = timeToMinutes($horaEntrada);
    $salidaMin = timeToMinutes($horaSalida);
    $entradaProgMin = timeToMinutes($horaEntradaProg);
    $salidaProgMin = timeToMinutes($horaSalidaProg);

    // Determinar si es fecha especial (festivo o domingo)
    $esFechaEspecial = esFechaEspecial($fecha, $pdo) || esDomingo($fecha);

    // Calcular el rango total de trabajo (counter-clockwise: salida -> entrada)
    $rangoTotal = [];
    if ($salidaMin >= $entradaMin) {
        // Trabajo en el mismo día
        $rangoTotal = [['inicio' => $entradaMin, 'fin' => $salidaMin]];
    } else {
        // Cruza medianoche
        $rangoTotal = [
            ['inicio' => $entradaMin, 'fin' => 24*60], // Día actual hasta medianoche
            ['inicio' => 0, 'fin' => $salidaMin]       // Día siguiente desde medianoche
        ];
    }

    // Procesar cada segmento del rango total
    foreach ($rangoTotal as $segmentoRango) {
        $minActual = $segmentoRango['fin']; // Comenzar desde el final (counter-clockwise)

        // Recorrer counter-clockwise hasta el inicio del segmento
        while ($minActual > $segmentoRango['inicio']) {
            $horaActual = minutesToTime($minActual);

            // Determinar si está dentro del horario programado
            $esHorarioProgramado = ($minActual >= $entradaProgMin && $minActual <= $salidaProgMin);

            // Determinar si es nocturno
            $esNocturno = esMinutoNocturno($minActual);

            // Asignar a la categoría correspondiente
            if ($esHorarioProgramado) {
                // Horas regulares
                if ($esFechaEspecial) {
                    if ($esNocturno) {
                        $resultado['recargo_nocturno_dominical_festivo'] += (1/60);
                    } else {
                        $resultado['recargo_dominical_festivo'] += (1/60);
                    }
                } else {
                    if ($esNocturno) {
                        $resultado['recargo_nocturno'] += (1/60);
                    } else {
                        $resultado['horas_regulares'] += (1/60);
                    }
                }
            } else {
                // Horas extras
                if ($esFechaEspecial) {
                    if ($esNocturno) {
                        $resultado['extra_nocturna_dominical_festiva'] += (1/60);
                    } else {
                        $resultado['extra_diurna_dominical_festiva'] += (1/60);
                    }
                } else {
                    if ($esNocturno) {
                        $resultado['extra_nocturna'] += (1/60);
                    } else {
                        $resultado['extra_diurna'] += (1/60);
                    }
                }
            }

            // Agregar al detalle de segmentos
            $categoria = '';
            if ($esFechaEspecial) {
                $categoria = $esNocturno ? 'nocturna_dominical_festiva' : 'diurna_dominical_festiva';
            } else {
                $categoria = $esNocturno ? 'nocturna' : 'diurna';
            }

            $resultado['segmentos'][] = [
                'hora_inicio' => $horaActual,
                'hora_fin' => $horaActual,
                'horas' => (1/60),
                'tipo' => $esHorarioProgramado ? 'regular' : 'extra',
                'es_nocturno' => $esNocturno,
                'es_extra' => !$esHorarioProgramado,
                'categoria' => $categoria
            ];

            $minActual--; // Mover counter-clockwise (hacia atrás)
        }
    }

    // Combinar segmentos similares para optimizar
    $segmentosOptimizados = [];
    $segmentoActual = null;

    foreach ($resultado['segmentos'] as $segmento) {
        if ($segmentoActual === null ||
            $segmentoActual['tipo'] !== $segmento['tipo'] ||
            $segmentoActual['es_nocturno'] !== $segmento['es_nocturno'] ||
            $segmentoActual['categoria'] !== $segmento['categoria']) {

            if ($segmentoActual !== null) {
                $segmentosOptimizados[] = $segmentoActual;
            }

            $segmentoActual = [
                'hora_inicio' => $segmento['hora_inicio'],
                'hora_fin' => $segmento['hora_fin'],
                'horas' => $segmento['horas'],
                'tipo' => $segmento['tipo'],
                'es_nocturno' => $segmento['es_nocturno'],
                'es_extra' => $segmento['es_extra'],
                'categoria' => $segmento['categoria']
            ];
        } else {
            // Combinar con el segmento actual
            $segmentoActual['hora_fin'] = $segmento['hora_fin'];
            $segmentoActual['horas'] += $segmento['horas'];
        }
    }

    if ($segmentoActual !== null) {
        $segmentosOptimizados[] = $segmentoActual;
    }

    $resultado['segmentos'] = $segmentosOptimizados;

    // Redondear todos los valores
    foreach ($resultado as $key => &$value) {
        if (is_numeric($value) && $key !== 'segmentos') {
            $value = round($value, 2);
        }
    }

    return $resultado;
}

/**
 * Determina el tipo de hora extra (antes/despues) basado en la posición del segmento
 */
function determinarTipoExtra($horaEntrada, $horaSalida, $horarioEntrada, $horarioSalida, $segmento) {
    $horaSegmento = strtotime($segmento['hora_inicio']);
    $horarioEntradaTime = strtotime($horarioEntrada);
    $horarioSalidaTime = strtotime($horarioSalida);

    if ($horaSegmento < $horarioEntradaTime) {
        return 'antes';
    } else {
        return 'despues';
    }
}

/**
 * Determina el tipo de horario basado en la categoría del segmento
 */
function determinarTipoHorario($categoria) {
    if (strpos($categoria, 'nocturna') !== false) {
        if (strpos($categoria, 'dominical') !== false || strpos($categoria, 'festiva') !== false) {
            return 'nocturna_dominical';
        } else {
            return 'nocturna';
        }
    } elseif (strpos($categoria, 'dominical') !== false || strpos($categoria, 'festiva') !== false) {
        return 'diurna_dominical';
    } else {
        return 'diurna';
    }
}

/**
 * Genera automáticamente horas extras para empleados que no tienen registros en la tabla de aprobación
 */
function generarHorasExtrasAutomaticamente($conn, $fechaDesde, $fechaHasta, $sedeId, $establecimientoId, $empleados) {
    try {
        // Obtener empleados que cumplen con los filtros
        $empleadosFiltrados = obtenerEmpleadosFiltrados($conn, $sedeId, $establecimientoId, $empleados);

        if (empty($empleadosFiltrados)) {
            return;
        }

        // Para cada empleado y fecha en el rango, verificar y generar horas extras
        $fechaInicio = new DateTime($fechaDesde);
        $fechaFin = new DateTime($fechaHasta);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($fechaInicio, $interval, $fechaFin->modify('+1 day'));

        foreach ($empleadosFiltrados as $empleado) {
            foreach ($period as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');
                generarHorasExtrasParaEmpleadoFecha($conn, $empleado['ID_EMPLEADO'], $fechaStr);
            }
        }

    } catch (Exception $e) {
        error_log("Error generando horas extras automáticamente: " . $e->getMessage());
    }
}

/**
 * Obtiene la lista de empleados que cumplen con los filtros aplicados
 */
function obtenerEmpleadosFiltrados($conn, $sedeId, $establecimientoId, $empleados) {
    $query = "
        SELECT DISTINCT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
        FROM empleado e
        WHERE e.ACTIVO = 'S'
    ";

    $params = [];
    $conditions = [];

    // Filtro por sede
    if ($sedeId) {
        $query .= " JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                   JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
        $conditions[] = "s.ID_SEDE = ?";
        $params[] = $sedeId;
    }

    // Filtro por establecimiento
    if ($establecimientoId) {
        if (!$sedeId) {
            $query .= " JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO";
        }
        $conditions[] = "est.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimientoId;
    }

    // Filtro por empleados específicos
    if (!empty($empleados)) {
        $placeholders = str_repeat('?,', count($empleados) - 1) . '?';
        $conditions[] = "e.ID_EMPLEADO IN ($placeholders)";
        $params = array_merge($params, $empleados);
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY e.ID_EMPLEADO";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Genera horas extras para un empleado en una fecha específica si no existen
 */
function generarHorasExtrasParaEmpleadoFecha($conn, $idEmpleado, $fecha) {
    try {
        // Verificar si ya existen horas extras para este empleado en esta fecha
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM horas_extras_aprobacion
            WHERE ID_EMPLEADO = ? AND FECHA = ?
        ");
        $stmt->execute([$idEmpleado, $fecha]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total'] > 0) {
            // Ya existen horas extras, no generar nuevas
            return;
        }

        // Obtener las horas trabajadas del empleado en esa fecha
        $horasTrabajadas = obtenerHorasTrabajadasEmpleadoFecha($conn, $idEmpleado, $fecha);

        if (empty($horasTrabajadas)) {
            // No hay horas trabajadas, no generar extras
            return;
        }

        // Calcular horas extras basadas en los registros de asistencia
        $horasExtrasCalculadas = calcularHorasExtrasDesdeRegistros($conn, $idEmpleado, $fecha, $horasTrabajadas);

        // Insertar las horas extras calculadas
        foreach ($horasExtrasCalculadas as $horaExtra) {
            insertarHoraExtraAprobacion($conn, $horaExtra);
        }

    } catch (Exception $e) {
        error_log("Error generando horas extras para empleado $idEmpleado fecha $fecha: " . $e->getMessage());
    }
}

/**
 * Obtiene los registros de horas trabajadas de un empleado en una fecha específica
 */
function obtenerHorasTrabajadasEmpleadoFecha($conn, $idEmpleado, $fecha) {
    // Obtener entradas del empleado en la fecha
    $stmt = $conn->prepare("
        SELECT
            a.ID_ASISTENCIA as id_entrada,
            a.FECHA as fecha_entrada,
            a.HORA as hora_entrada,
            a.ID_EMPLEADO_HORARIO,
            ehp.HORA_ENTRADA as HORARIO_ENTRADA,
            ehp.HORA_SALIDA as HORARIO_SALIDA,
            ehp.ES_TURNO_NOCTURNO
        FROM asistencia a
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE a.ID_EMPLEADO = ?
        AND a.FECHA = ?
        AND a.TIPO = 'ENTRADA'
        ORDER BY a.HORA ASC
    ");
    $stmt->execute([$idEmpleado, $fecha]);
    $entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $registros = [];

    // Para cada entrada, buscar la salida correspondiente
    foreach ($entradas as $entrada) {
        $stmt = $conn->prepare("
            SELECT
                a.ID_ASISTENCIA as id_salida,
                a.FECHA as fecha_salida,
                a.HORA as hora_salida
            FROM asistencia a
            WHERE a.ID_EMPLEADO = ?
            AND a.TIPO = 'SALIDA'
            AND CONCAT(a.FECHA, ' ', a.HORA) > CONCAT(?, ' ', ?)
            AND CONCAT(a.FECHA, ' ', a.HORA) <= DATE_ADD(CONCAT(?, ' ', ?), INTERVAL 24 HOUR)
            ORDER BY CONCAT(a.FECHA, ' ', a.HORA) ASC
            LIMIT 1
        ");
        $stmt->execute([
            $idEmpleado,
            $entrada['fecha_entrada'],
            $entrada['hora_entrada'],
            $entrada['fecha_entrada'],
            $entrada['hora_entrada']
        ]);
        $salida = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($salida) {
            $registros[] = [
                'hora_entrada' => $entrada['hora_entrada'],
                'hora_salida' => $salida['hora_salida'],
                'horario_entrada' => $entrada['HORARIO_ENTRADA'],
                'horario_salida' => $entrada['HORARIO_SALIDA'],
                'id_empleado_horario' => $entrada['ID_EMPLEADO_HORARIO'],
                'es_turno_nocturno' => $entrada['ES_TURNO_NOCTURNO'] === 'S'
            ];
        }
    }

    return $registros;
}

/**
 * Calcula horas extras desde los registros de asistencia usando el sistema jerárquico
 * Versión actualizada para usar calculateHoursWithHierarchyLegacy y mantener consistencia
 */
function calcularHorasExtrasDesdeRegistros($conn, $idEmpleado, $fecha, $registros) {
    $horasExtras = [];

    foreach ($registros as $registro) {
        $horaEntrada = $registro['hora_entrada'];
        $horaSalida = $registro['hora_salida'];
        $horarioEntrada = $registro['horario_entrada'];
        $horarioSalida = $registro['horario_salida'];
        $idEmpleadoHorario = $registro['id_empleado_horario'];
        $esTurnoNocturno = $registro['es_turno_nocturno'];

        // Usar el sistema jerárquico para calcular horas
        if ($horarioEntrada && $horarioSalida) {
            // Calcular usando la lógica jerárquica
            $calculoJerarquico = calculateHoursWithHierarchyLegacy($horaEntrada, $horaSalida, $fecha, $horarioEntrada, $horarioSalida, $conn);

            // Solo generar horas extras (ignorar horas regulares)
            foreach ($calculoJerarquico['segmentos'] as $segmento) {
                if ($segmento['es_extra'] && $segmento['horas'] > 0) {
                    // Determinar tipo_extra basado en la posición del segmento
                    $tipoExtra = determinarTipoExtra($horaEntrada, $horaSalida, $horarioEntrada, $horarioSalida, $segmento);

                    // Determinar tipo_horario basado en la categoría
                    $tipoHorario = determinarTipoHorario($segmento['categoria']);

                    $horasExtras[] = [
                        'id_empleado' => $idEmpleado,
                        'id_empleado_horario' => $idEmpleadoHorario,
                        'fecha' => $fecha,
                        'hora_inicio' => $segmento['hora_inicio'],
                        'hora_fin' => $segmento['hora_fin'],
                        'horas_extras' => round($segmento['horas'], 2),
                        'tipo_extra' => $tipoExtra,
                        'tipo_horario' => $tipoHorario
                    ];
                }
            }
        } else {
            // Sin horario definido, todas las horas son extras usando jerarquía con horario completo
            $calculoJerarquico = calculateHoursWithHierarchyLegacy($horaEntrada, $horaSalida, $fecha, '00:00', '23:59', $conn);

            // Solo generar registros para horas extras
            foreach ($calculoJerarquico['segmentos'] as $segmento) {
                if ($segmento['es_extra'] && $segmento['horas'] > 0) {
                    $tipoHorario = determinarTipoHorario($segmento['categoria']);

                    $horasExtras[] = [
                        'id_empleado' => $idEmpleado,
                        'id_empleado_horario' => $idEmpleadoHorario,
                        'fecha' => $fecha,
                        'hora_inicio' => $segmento['hora_inicio'],
                        'hora_fin' => $segmento['hora_fin'],
                        'horas_extras' => round($segmento['horas'], 2),
                        'tipo_extra' => 'despues', // Por defecto cuando no hay horario
                        'tipo_horario' => $tipoHorario
                    ];
                }
            }
        }
    }

    return $horasExtras;
}

/**
 * Calcula la diferencia en horas entre dos horas
 */
function calcularDiferenciaHoras($horaInicio, $horaFin) {
    $inicio = strtotime($horaInicio);
    $fin = strtotime($horaFin);

    if ($fin <= $inicio) {
        return 0;
    }

    return ($fin - $inicio) / 3600;
}

/**
 * Inserta una hora extra en la tabla de aprobación
 */
function insertarHoraExtraAprobacion($conn, $horaExtra) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO horas_extras_aprobacion (
                ID_EMPLEADO,
                ID_EMPLEADO_HORARIO,
                FECHA,
                HORA_INICIO,
                HORA_FIN,
                HORAS_EXTRAS,
                TIPO_EXTRA,
                TIPO_HORARIO,
                ESTADO_APROBACION,
                CREATED_AT
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
        ");

        $stmt->execute([
            $horaExtra['id_empleado'],
            $horaExtra['id_empleado_horario'],
            $horaExtra['fecha'],
            $horaExtra['hora_inicio'],
            $horaExtra['hora_fin'],
            $horaExtra['horas_extras'],
            $horaExtra['tipo_extra'],
            $horaExtra['tipo_horario']
        ]);

    } catch (Exception $e) {
        error_log("Error insertando hora extra: " . $e->getMessage());
    }
}
?>