<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';
require_once __DIR__ . '/../../utils/horario_utils.php';
session_start();

header('Content-Type: application/json');

function mapEstadoSalidaDisplay($estado)
{
    if ($estado === null || $estado === '') {
        return '--';
    }

    if ($estado === 'Puntual') {
        return 'A Tiempo';
    }

    if ($estado === '--') {
        return 'Sin salida';
    }

    return $estado;
}

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    $userRole = $_SESSION['rol'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');
    
    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

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

    // Construir consulta base
    $where = [];
    $params = [];

    // Aplicar filtro restrictivo solo para rol ASISTENCIA
    if ($userRole === 'ASISTENCIA') {
        $where[] = "e.ID_ESTABLECIMIENTO IN (
            SELECT DISTINCT e2.ID_ESTABLECIMIENTO
            FROM empleado e2
            JOIN ESTABLECIMIENTO est2 ON e2.ID_ESTABLECIMIENTO = est2.ID_ESTABLECIMIENTO
            JOIN SEDE s2 ON est2.ID_SEDE = s2.ID_SEDE
            WHERE s2.ID_EMPRESA = :empresa_id
        )";
        $params[':empresa_id'] = $empresaId;
    } else {
        // Para otros roles, aplicar filtro directo de empresa
    $where[] = "id_sede IN (SELECT id_sede FROM sede WHERE id_empresa = :empresa_id)";
        $params[':empresa_id'] = $empresaId;
    }

    // Aplicar filtros de fecha
    if ($filtros['fecha_desde']) {
        $where[] = "fecha_evento >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }

    if ($filtros['fecha_hasta']) {
        $where[] = "fecha_evento <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }

    // Filtros para reporte del día, semana o mes actual
    if ($filtros['tipo_reporte']) {
        switch ($filtros['tipo_reporte']) {
            case 'dia':
                $where[] = "fecha_evento = CURDATE()";
                break;
            case 'ultimos7dias':
                $where[] = "fecha_evento >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'semana':
                $where[] = "YEARWEEK(fecha_evento, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'ultimos30dias':
                $where[] = "fecha_evento >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'mes':
                $where[] = "YEAR(fecha_evento) = YEAR(CURDATE()) AND MONTH(fecha_evento) = MONTH(CURDATE())";
                break;
        }
    }

    // Filtros adicionales
    if ($filtros['codigo']) {
        $where[] = "codigo_empleado = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['nombre']) {
        $where[] = "(nombre_empleado LIKE :nombre OR apellido_empleado LIKE :nombre)";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede'] && $filtros['sede'] !== 'Todas') {
        $where[] = "id_sede = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento'] && $filtros['establecimiento'] !== 'Todos') {
        $where[] = "id_establecimiento = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    $filtro_estado = null;
    if ($filtros['estado_entrada'] && $filtros['estado_entrada'] !== 'Todos') {
        $filtro_estado = $filtros['estado_entrada'];
    }

    $filtro_estado_salida = null;
    if ($filtros['estado_salida'] && $filtros['estado_salida'] !== 'Todos') {
        $filtro_estado_salida = $filtros['estado_salida'];
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Consulta principal: combinar asistencia y justificaciones
    $sql = "
        SELECT *
        FROM (
            SELECT
                'asistencia' as tipo_registro,
                e.ID_EMPLEADO as codigo_empleado,
                e.NOMBRE as nombre_empleado,
                e.APELLIDO as apellido_empleado,
                s.ID_SEDE as id_sede,
                s.NOMBRE as sede,
                est.ID_ESTABLECIMIENTO as id_establecimiento,
                est.NOMBRE as establecimiento,
                a.FECHA as fecha_evento,
                a.HORA as hora_entrada,
                NULL as hora_salida,
                ehp.ID_EMPLEADO_HORARIO,
                ehp.HORA_ENTRADA as hora_entrada_programada,
                ehp.HORA_SALIDA as hora_salida_programada,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.ES_TURNO_NOCTURNO,
                ehp.FECHA_DESDE as horario_fecha_desde,
                ehp.FECHA_HASTA as horario_fecha_hasta,
                ehp.ACTIVO as horario_activo,
                a.OBSERVACION,
                a.ID_ASISTENCIA as id_registro,
                0 as justificar_todos_turnos,
                NULL as detalle_adicional
            FROM asistencia a
            JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
            WHERE a.TIPO = 'ENTRADA'

            UNION ALL

            SELECT
                'justificacion' as tipo_registro,
                e.ID_EMPLEADO as codigo_empleado,
                e.NOMBRE as nombre_empleado,
                e.APELLIDO as apellido_empleado,
                s.ID_SEDE as id_sede,
                s.NOMBRE as sede,
                est.ID_ESTABLECIMIENTO as id_establecimiento,
                est.NOMBRE as establecimiento,
                j.fecha_falta as fecha_evento,
                NULL as hora_entrada,
                NULL as hora_salida,
                j.turno_id as ID_EMPLEADO_HORARIO,
                ehp.HORA_ENTRADA as hora_entrada_programada,
                ehp.HORA_SALIDA as hora_salida_programada,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.ES_TURNO_NOCTURNO,
                ehp.FECHA_DESDE as horario_fecha_desde,
                ehp.FECHA_HASTA as horario_fecha_hasta,
                ehp.ACTIVO as horario_activo,
                j.motivo as OBSERVACION,
                j.id as id_registro,
                j.justificar_todos_turnos,
                j.detalle_adicional
            FROM justificaciones j
            JOIN EMPLEADO e ON j.empleado_id = e.ID_EMPLEADO
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN empleado_horario_personalizado ehp ON j.turno_id = ehp.ID_EMPLEADO_HORARIO
                AND j.fecha_falta BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
            WHERE j.estado = 'aprobada'
        ) AS combined_data
        {$whereClause}
        ORDER BY fecha_evento DESC, codigo_empleado ASC
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar registros para buscar salidas y calcular estados
    $result = [];
    
    foreach ($registros as $registro) {
        $hora_entrada = $registro['hora_entrada'];
        $fecha = $registro['fecha_evento'];
        $id_empleado = $registro['codigo_empleado'];
        $tipo_registro = $registro['tipo_registro'];
        $id_empleado_horario = $registro['ID_EMPLEADO_HORARIO'];

        // Obtener horario correcto usando el ID específico del registro de asistencia
        // Si no hay ID_EMPLEADO_HORARIO, usar la función general
        if ($id_empleado_horario) {
            $horarioInfo = obtenerHorarioPorId($id_empleado_horario, $conn);
        } else {
            $horarioInfo = obtenerHorarioEmpleadoSimplificado($id_empleado, $fecha, $conn);
        }

        $horaEntradaProgramada = $registro['hora_entrada_programada'] ?? null;
        $horaSalidaProgramada = $registro['hora_salida_programada'] ?? null;
        $nombreTurnoRegistro = $registro['NOMBRE_TURNO'] ?? null;

        if (!$horaEntradaProgramada && $horarioInfo['HORA_ENTRADA']) {
            $horaEntradaProgramada = $horarioInfo['HORA_ENTRADA'];
        }
        if (!$horaSalidaProgramada && $horarioInfo['HORA_SALIDA']) {
            $horaSalidaProgramada = $horarioInfo['HORA_SALIDA'];
        }

        $hora_entrada_programada = $horaEntradaProgramada;
        $hora_salida_programada = $horaSalidaProgramada;
    $tolerancia = normalizarToleranciaMinutos($horarioInfo['TOLERANCIA'] ?? 15);
        $nombre_turno = $nombreTurnoRegistro ?: $horarioInfo['horario_nombre'];

        $justificarTodosTurnos = (int)($registro['justificar_todos_turnos'] ?? 0) === 1;
        $detalleAdicional = $registro['detalle_adicional'] ?? null;

        // Buscar salida correspondiente (solo para asistencia)
        $hora_salida = null;
        if ($tipo_registro === 'asistencia' && $id_empleado_horario) {
            $salidaSql = "
                SELECT HORA
                FROM asistencia
                WHERE ID_EMPLEADO = :id_empleado
                AND TIPO = 'SALIDA'
                AND ID_EMPLEADO_HORARIO = :id_horario
                AND CONCAT(FECHA, ' ', HORA) BETWEEN CONCAT(:fecha, ' ', :hora_entrada)
                AND DATE_ADD(CONCAT(:fecha, ' ', :hora_entrada), INTERVAL 24 HOUR)
                ORDER BY CONCAT(FECHA, ' ', HORA) ASC
                LIMIT 1
            ";
            $salidaStmt = $conn->prepare($salidaSql);
            $salidaStmt->bindValue(':id_empleado', $id_empleado);
            $salidaStmt->bindValue(':id_horario', $id_empleado_horario);
            $salidaStmt->bindValue(':fecha', $fecha);
            $salidaStmt->bindValue(':hora_entrada', $hora_entrada);
            $salidaStmt->execute();
            $salidaResult = $salidaStmt->fetch(PDO::FETCH_ASSOC);
            if ($salidaResult) {
                $hora_salida = $salidaResult['HORA'];
            }
        }
        
        // Calcular estado de entrada
        $estado_entrada = 'Ausente';
        $estado_entrada_clase = 'badge-secondary';
        
        if ($tipo_registro === 'justificacion') {
            $estado_entrada = 'Justificado';
            $estado_entrada_clase = 'badge-danger';
        } elseif ($hora_entrada && $hora_entrada_programada) {
            $estado = calcularEstadoEntrada($hora_entrada_programada, $hora_entrada, $tolerancia);
            switch ($estado) {
                case 'Temprano':
                    $estado_entrada = 'Temprano';
                    $estado_entrada_clase = 'badge-success';
                    break;
                case 'Puntual':
                    $estado_entrada = 'A Tiempo';
                    $estado_entrada_clase = 'badge-info';
                    break;
                case 'Tardanza':
                    $estado_entrada = 'Tardanza';
                    $estado_entrada_clase = 'badge-warning';
                    break;
                default:
                    $estado_entrada = 'Ausente';
                    $estado_entrada_clase = 'badge-secondary';
            }
        } elseif ($hora_entrada) {
            $estado_entrada = 'Presente';
            $estado_entrada_clase = 'badge-info';
        }
        
        // Calcular horas trabajadas
        $horas_trabajadas = null;
        if ($hora_entrada && $hora_salida) {
            $hora_inicio = strtotime($fecha . ' ' . $hora_entrada);
            $hora_fin = strtotime($fecha . ' ' . $hora_salida);
            
            // Si hora_salida < hora_entrada, es turno nocturno (salida al día siguiente)
            if ($hora_salida < $hora_entrada) {
                $hora_fin = strtotime($fecha . ' ' . $hora_salida . ' +1 day');
            }
            
            $diferencia_segundos = $hora_fin - $hora_inicio;
            $horas_trabajadas = round($diferencia_segundos / 3600, 2);
        }
        
        // Estado de salida
        $estado_salida = '--';
        $estado_salida_clase = 'badge-secondary';
        if ($hora_salida && $hora_salida_programada) {
            $estado_salida = calcularEstadoSalida($hora_salida_programada, $hora_salida, $tolerancia);
            switch ($estado_salida) {
                case 'Temprano':
                    $estado_salida_clase = 'badge-warning';
                    break;
                case 'Puntual':
                    $estado_salida_clase = 'badge-info';
                    break;
                case 'Tardanza':
                    $estado_salida_clase = 'badge-success';
                    break;
                default:
                    $estado_salida_clase = 'badge-secondary';
            }
        } elseif ($hora_salida) {
            $estado_salida = 'Registrada';
            $estado_salida_clase = 'badge-info';
        }
        
        // Filtrar por estado si necesario
        if ($filtro_estado && $estado_entrada !== $filtro_estado) {
            continue;
        }

        if ($filtro_estado_salida) {
            $valorFiltroSalida = $filtro_estado_salida;
            if (strcasecmp($valorFiltroSalida, 'Sin salida') === 0) {
                $valorFiltroSalida = '--';
            } elseif (strcasecmp($valorFiltroSalida, 'A Tiempo') === 0) {
                $valorFiltroSalida = 'Puntual';
            }

            if ($estado_salida !== $valorFiltroSalida) {
                continue;
            }
        }

        $estado_salida_display = mapEstadoSalidaDisplay($estado_salida);

        // Ajustar campos de horario y observación para justificaciones de jornada completa
        $horarioHoras = 'N/A';
        if ($tipo_registro === 'justificacion' && $justificarTodosTurnos) {
            $horarioHoras = 'Jornada completa';
        } elseif ($hora_entrada_programada && $hora_salida_programada) {
            $horarioHoras = $hora_entrada_programada . ' - ' . $hora_salida_programada;
        } elseif ($horarioInfo['HORA_ENTRADA'] && $horarioInfo['HORA_SALIDA']) {
            $horarioHoras = $horarioInfo['HORA_ENTRADA'] . ' - ' . $horarioInfo['HORA_SALIDA'];
        }

        $vigenciaDesde = $registro['horario_fecha_desde'] ?? null;
        $vigenciaHasta = $registro['horario_fecha_hasta'] ?? null;
        $vigenciaEtiqueta = null;
        if ($vigenciaDesde && $vigenciaHasta) {
            $vigenciaEtiqueta = $vigenciaDesde . ' al ' . $vigenciaHasta;
        } elseif ($vigenciaDesde) {
            $vigenciaEtiqueta = 'desde ' . $vigenciaDesde;
        }

        // Vigencia se expone como metadato para evitar duplicar información en la etiqueta principal.

        $horarioNombre = $nombre_turno;
        if ($tipo_registro === 'justificacion' && $justificarTodosTurnos) {
            $horarioNombre = $horarioNombre ?: 'Jornada completa';
        }

        $observacion = $registro['OBSERVACION'];
        if ($tipo_registro === 'justificacion') {
            $observacion = $observacion ?: 'Justificación';
            if ($detalleAdicional) {
                $observacion .= ' - ' . $detalleAdicional;
            }
            if ($justificarTodosTurnos) {
                $observacion = 'Jornada completa: ' . $observacion;
            }
        }

        $tipoLabel = $tipo_registro === 'justificacion'
            ? ($justificarTodosTurnos ? 'Justificación (Jornada completa)' : 'Justificación')
            : 'Asistencia';
        
        $result[] = [
            'codigo' => $registro['codigo_empleado'],
            'nombre' => $registro['nombre_empleado'] . ' ' . $registro['apellido_empleado'],
            'sede' => $registro['sede'],
            'establecimiento' => $registro['establecimiento'],
            'fecha' => $registro['fecha_evento'],
            'hora_entrada' => $registro['hora_entrada'],
            'estado_entrada' => $estado_entrada,
            'estado_entrada_clase' => $estado_entrada_clase,
            'hora_salida' => $hora_salida,
            'estado_salida' => $estado_salida_display,
            'estado_salida_clase' => $estado_salida_clase,
            'horas_trabajadas' => $horas_trabajadas,
            'tipo' => $tipoLabel,
            'horario_nombre' => $horarioNombre,
            'horario_horas' => $horarioHoras,
            'horario_activo' => $horarioInfo['ACTIVO'] ?? ($registro['horario_activo'] ?? null),
            'horario_vigencia_desde' => $vigenciaDesde,
            'horario_vigencia_hasta' => $vigenciaHasta,
            'observacion' => $observacion,
            'id_registro' => $registro['id_registro'],
            'justificacion_jornada_completa' => $justificarTodosTurnos
        ];
    }
    
    // Aplicar paginación
    $totalRecords = count($result);
    $result = array_slice($result, $offset, $limit);
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar reportes: ' . $e->getMessage()
    ]);
}
?>