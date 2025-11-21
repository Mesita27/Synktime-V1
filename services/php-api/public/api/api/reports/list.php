<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';
session_start();

header('Content-Type: application/json');

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
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    // Aplicar filtro restrictivo solo para rol ASISTENCIA
    // GERENTE, ADMIN, DUEÑO tienen acceso total a todos los reportes de la empresa
    if ($userRole === 'ASISTENCIA') {
        // Solo para ASISTENCIA: restringir a reportes de empleados específicos
        $where[] = "e.ID_ESTABLECIMIENTO IN (
            SELECT DISTINCT e2.ID_ESTABLECIMIENTO 
            FROM EMPLEADO e2 
            JOIN ESTABLECIMIENTO est2 ON e2.ID_ESTABLECIMIENTO = est2.ID_ESTABLECIMIENTO 
            JOIN SEDE s2 ON est2.ID_SEDE = s2.ID_SEDE 
            WHERE s2.ID_EMPRESA = :empresa_id
        )";
    }
    // Para GERENTE, ADMIN, DUEÑO: sin restricciones adicionales (acceso total)

    // Aplicar filtros de fecha
    if ($filtros['fecha_desde']) {
        $where[] = "a_fecha.FECHA >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }

    if ($filtros['fecha_hasta']) {
        $where[] = "a_fecha.FECHA <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }

    // Filtros para reporte del día, semana o mes actual
    if ($filtros['tipo_reporte']) {
        switch ($filtros['tipo_reporte']) {
            case 'dia':
                $where[] = "a_fecha.FECHA = CURDATE()";
                break;
            case 'semana':
                $where[] = "YEARWEEK(a_fecha.FECHA, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'mes':
                $where[] = "YEAR(a_fecha.FECHA) = YEAR(CURDATE()) AND MONTH(a_fecha.FECHA) = MONTH(CURDATE())";
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

    // Aplicar filtro por estado_entrada si se proporciona
    if ($filtros['estado_entrada'] && $filtros['estado_entrada'] !== 'Todos') {
        // Este filtro se aplicará después en PHP ya que el estado se calcula, no está almacenado
        $filtro_estado = $filtros['estado_entrada'];
    } else {
        $filtro_estado = null;
    }

    if ($filtros['estado_salida'] && $filtros['estado_salida'] !== 'Todos') {
        $filtro_estado_salida = $filtros['estado_salida'];
    } else {
        $filtro_estado_salida = null;
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para contar total de registros (sin el filtro de estado)
    $countSql = "
        SELECT COUNT(*) as total
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN (
            SELECT DISTINCT a.ID_EMPLEADO, a.FECHA, a.ID_HORARIO
            FROM ASISTENCIA a
        ) AS a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO
        WHERE {$whereClause}
    ";

    $countStmt = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Consulta principal sin paginación inicialmente para aplicar filtro de estado
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE AS establecimiento,
            s.NOMBRE AS sede,
            a_fecha.FECHA,
            h.ID_HORARIO,
            h.NOMBRE AS HORARIO_NOMBRE,
            h.HORA_ENTRADA AS HORA_ENTRADA_PROGRAMADA,
            h.HORA_SALIDA AS HORA_SALIDA_PROGRAMADA,
            h.TOLERANCIA,
            
            -- Entrada (seleccionamos el registro de entrada más reciente para cada combinación empleado/horario/fecha)
            entrada.ID_ASISTENCIA AS ENTRADA_ID,
            entrada.HORA AS ENTRADA_HORA,
            entrada.TARDANZA AS ENTRADA_TARDANZA,
            entrada.OBSERVACION as OBSERVACION,
            
            -- Salida (seleccionamos el registro de salida más reciente para cada combinación empleado/horario/fecha)
            salida.ID_ASISTENCIA AS SALIDA_ID,
            salida.HORA AS SALIDA_HORA,
            salida.TARDANZA AS SALIDA_TARDANZA
            
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE

        -- Subconsulta para obtener fechas únicas de asistencia
        JOIN (
            SELECT DISTINCT a.ID_EMPLEADO, a.FECHA, a.ID_HORARIO
            FROM ASISTENCIA a
        ) AS a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO

        -- Unión con HORARIO a través del ID_HORARIO en la asistencia
        LEFT JOIN HORARIO h ON h.ID_HORARIO = a_fecha.ID_HORARIO

        -- Subconsulta para obtener la entrada más reciente
        LEFT JOIN (
            SELECT a_entrada.ID_ASISTENCIA, a_entrada.ID_EMPLEADO, a_entrada.FECHA, a_entrada.ID_HORARIO, 
                a_entrada.HORA, a_entrada.TARDANZA, a_entrada.OBSERVACION
            FROM ASISTENCIA a_entrada
            WHERE a_entrada.TIPO = 'ENTRADA'
            AND NOT EXISTS (
                SELECT 1 FROM ASISTENCIA a2
                WHERE a2.ID_EMPLEADO = a_entrada.ID_EMPLEADO
                AND a2.FECHA = a_entrada.FECHA
                AND a2.ID_HORARIO = a_entrada.ID_HORARIO
                AND a2.TIPO = 'ENTRADA'
                AND a2.ID_ASISTENCIA > a_entrada.ID_ASISTENCIA
            )
        ) AS entrada ON e.ID_EMPLEADO = entrada.ID_EMPLEADO 
                    AND a_fecha.FECHA = entrada.FECHA
                    AND a_fecha.ID_HORARIO = entrada.ID_HORARIO

        -- Subconsulta para obtener la salida más reciente
        LEFT JOIN (
            SELECT a_salida.ID_ASISTENCIA, a_salida.ID_EMPLEADO, a_salida.FECHA, a_salida.ID_HORARIO, 
                a_salida.HORA, a_salida.TARDANZA
            FROM ASISTENCIA a_salida
            WHERE a_salida.TIPO = 'SALIDA'
            AND NOT EXISTS (
                SELECT 1 FROM ASISTENCIA a2
                WHERE a2.ID_EMPLEADO = a_salida.ID_EMPLEADO
                AND a2.FECHA = a_salida.FECHA
                AND a2.ID_HORARIO = a_salida.ID_HORARIO
                AND a2.TIPO = 'SALIDA'
                AND a2.ID_ASISTENCIA > a_salida.ID_ASISTENCIA
            )
        ) AS salida ON e.ID_EMPLEADO = salida.ID_EMPLEADO 
                    AND a_fecha.FECHA = salida.FECHA
                    AND a_fecha.ID_HORARIO = salida.ID_HORARIO

        WHERE {$whereClause}
        GROUP BY e.ID_EMPLEADO, a_fecha.FECHA, a_fecha.ID_HORARIO
        ORDER BY a_fecha.FECHA DESC, h.HORA_ENTRADA ASC
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamos para calcular los estados y aplicar filtro si es necesario
    $result = [];
    
    foreach ($asistencias as $registro) {
        // Calculamos el estado de la entrada usando la función consistente
        $horaEntradaProgramada = $registro['HORA_ENTRADA_PROGRAMADA'];
        $horaEntradaReal = $registro['ENTRADA_HORA'];
        $tolerancia = (int)($registro['TOLERANCIA'] ?? 0);

        $estadoEntrada = calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $tolerancia);

        // Si no hay hora de entrada pero sí hay registro, marcar como Presente
        if (!$registro['ENTRADA_HORA'] && !$horaEntradaProgramada) {
            $estadoEntrada = 'Ausente';
        } elseif ($registro['ENTRADA_HORA'] && !$horaEntradaProgramada) {
            $estadoEntrada = 'Presente';
        }

        // Calculamos el estado de la salida usando la función consistente
        $horaSalidaProgramada = $registro['HORA_SALIDA_PROGRAMADA'];
        $horaSalidaReal = $registro['SALIDA_HORA'];

        $estadoSalida = calcularEstadoSalida($horaSalidaProgramada, $horaSalidaReal, $tolerancia);

        // Si no hay hora de salida pero sí hay registro, marcar como Registrada
        if ($registro['SALIDA_HORA'] && !$horaSalidaProgramada) {
            $estadoSalida = 'Registrada';
        } elseif (!$registro['SALIDA_HORA']) {
            $estadoSalida = '--';
        }

        // Añadir estados al registro
        $registro['ENTRADA_ESTADO'] = $estadoEntrada;
        $registro['SALIDA_ESTADO'] = $estadoSalida;
        
        // Filtrar por estado si es necesario
        if ($filtro_estado) {
            // Mapear los valores del filtro a los valores calculados
            $estadoMapeado = $estadoEntrada;
            switch ($filtro_estado) {
                case 'A Tiempo':
                    $estadoMapeado = 'Puntual';
                    break;
                case 'Temprano':
                    $estadoMapeado = 'Temprano';
                    break;
                case 'Tardanza':
                    $estadoMapeado = 'Tardanza';
                    break;
                case 'Ausente':
                    $estadoMapeado = 'Ausente';
                    break;
            }

            if ($estadoEntrada !== $estadoMapeado) {
                continue; // Saltar este registro si no coincide con el filtro de estado
            }
        }

        if ($filtro_estado_salida) {
            $estadoFiltroSalida = $filtro_estado_salida;
            if (strcasecmp($estadoFiltroSalida, 'A Tiempo') === 0) {
                $estadoFiltroSalida = 'Puntual';
            } elseif (strcasecmp($estadoFiltroSalida, 'Sin salida') === 0) {
                $estadoFiltroSalida = '--';
            }

            if ($estadoSalida !== $estadoFiltroSalida) {
                continue;
            }
        }
        
        $result[] = $registro;
    }
    
    // Aplicar paginación manualmente después del filtrado
    $totalFiltered = count($result);
    $result = array_slice($result, $offset, $limit);
    
    // Actualizar total de registros si se aplicó filtro de estado
    if ($filtro_estado || $filtro_estado_salida) {
        $totalRecords = $totalFiltered;
        $totalPages = ceil($totalRecords / $limit);
    } else {
        $totalPages = ceil($totalRecords / $limit);
    }
    
    // Formatear para la respuesta JSON
    $formattedResult = [];
    foreach ($result as $registro) {
        // Mapear los estados calculados a los valores que espera el frontend
        $estadoEntradaFrontend = $registro['ENTRADA_ESTADO'];
        switch ($registro['ENTRADA_ESTADO']) {
            case 'Puntual':
                $estadoEntradaFrontend = 'A Tiempo';
                break;
            case 'Temprano':
                $estadoEntradaFrontend = 'Temprano';
                break;
            case 'Tardanza':
                $estadoEntradaFrontend = 'Tardanza';
                break;
            case 'Ausente':
                $estadoEntradaFrontend = 'Ausente';
                break;
            case 'Presente':
                $estadoEntradaFrontend = 'Presente';
                break;
        }

        $estadoSalidaFrontend = $registro['SALIDA_ESTADO'] ?? '--';
        switch ($estadoSalidaFrontend) {
            case 'Puntual':
                $estadoSalidaFrontend = 'A Tiempo';
                break;
            case '--':
                $estadoSalidaFrontend = 'Sin salida';
                break;
        }

        $formattedResult[] = [
            'ID_ASISTENCIA' => $registro['ENTRADA_ID'],
            'codigo' => $registro['ID_EMPLEADO'],
            'nombre' => $registro['NOMBRE'] . ' ' . $registro['APELLIDO'],
            'sede' => $registro['sede'],
            'establecimiento' => $registro['establecimiento'],
            'fecha' => $registro['FECHA'],
            'hora_entrada' => $registro['ENTRADA_HORA'],
            'hora_salida' => $registro['SALIDA_HORA'],
            'estado_entrada' => $estadoEntradaFrontend,
            'estado_salida' => $estadoSalidaFrontend,
            'observacion' => $registro['OBSERVACION'],
            'ID_HORARIO' => $registro['ID_HORARIO'],
            'horario_nombre' => $registro['HORARIO_NOMBRE'],
            'horario_entrada' => $registro['HORA_ENTRADA_PROGRAMADA'],
            'horario_salida' => $registro['HORA_SALIDA_PROGRAMADA'],
            'tolerancia' => $registro['TOLERANCIA'],
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedResult,
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