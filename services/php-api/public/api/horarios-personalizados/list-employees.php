<?php

// Configurar zona horaria de Bogotá, Colombia
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    // Debug mode
    $debug = isset($_GET['debug']) && $_GET['debug'] === '1';
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Determinar si es POST o GET request
    $requestData = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $requestData = $input ?? [];
    } else {
        $requestData = $_GET;
    }

    // Parámetros de paginación
    $page = max(1, intval($requestData['page'] ?? 1));
    $limit = max(10, min(50, intval($requestData['limit'] ?? 15)));
    $offset = ($page - 1) * $limit;

    // Parámetros de filtro
    $filtros = [
        'nombre' => $requestData['nombre'] ?? null,
        'sede' => $requestData['sede'] ?? null,
        'establecimiento' => $requestData['establecimiento'] ?? null,
        'estado_horario' => $requestData['estado_horario'] ?? null
    ];

    // Construcción de la consulta principal
    $where = ["s.ID_EMPRESA = :empresa_id", "e.ACTIVO = 'S'"];
    $params = [':empresa_id' => $empresaId];

    if ($filtros['nombre']) {
        $where[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre OR e.DNI LIKE :nombre)";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    $whereClause = implode(' AND ', $where);

    // Query principal con información de horarios personalizados
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            e.CORREO,
            e.TELEFONO,
            e.FECHA_INGRESO,
            est.NOMBRE as establecimiento_nombre,
            s.NOMBRE as sede_nombre,
            s.ID_SEDE,
            est.ID_ESTABLECIMIENTO,
            
            -- Estadísticas de horarios personalizados
            COUNT(DISTINCT ehp.ID_EMPLEADO_HORARIO) as total_horarios_personalizados,
            COUNT(DISTINCT CASE WHEN ehp.ACTIVO = 'S' AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= CURDATE()) THEN ehp.ID_EMPLEADO_HORARIO END) as horarios_activos,
            COUNT(DISTINCT CASE WHEN ehp.ACTIVO = 'S' AND ehp.FECHA_HASTA < CURDATE() THEN ehp.ID_EMPLEADO_HORARIO END) as horarios_vencidos,
            MAX(ehp.UPDATED_AT) as ultima_modificacion_horarios,
            
            -- Información del último horario modificado
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(ds.NOMBRE, ': ', ehp2.NOMBRE_TURNO) ORDER BY ds.ID_DIA SEPARATOR ', ')
             FROM empleado_horario_personalizado ehp2 
             JOIN dia_semana ds ON ehp2.ID_DIA = ds.ID_DIA
             WHERE ehp2.ID_EMPLEADO = e.ID_EMPLEADO 
             AND ehp2.ACTIVO = 'S' 
             AND (ehp2.FECHA_HASTA IS NULL OR ehp2.FECHA_HASTA >= CURDATE())
             LIMIT 5) as resumen_horarios
             
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO
        WHERE $whereClause
    ";

    // Aplicar filtro por estado de horarios
    if ($filtros['estado_horario']) {
        switch ($filtros['estado_horario']) {
            case 'con_horarios':
                $sql .= " HAVING total_horarios_personalizados > 0";
                break;
            case 'sin_horarios':
                $sql .= " HAVING total_horarios_personalizados = 0";
                break;
            case 'horarios_vencidos':
                $sql .= " HAVING horarios_vencidos > 0";
                break;
        }
    }

    $sql .= "
        GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.DNI, e.CORREO, e.TELEFONO, 
                 e.FECHA_INGRESO, est.NOMBRE, s.NOMBRE, s.ID_SEDE, est.ID_ESTABLECIMIENTO
        ORDER BY e.APELLIDO, e.NOMBRE
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query para contar total de registros
    $countSql = "
        SELECT COUNT(DISTINCT e.ID_EMPLEADO) as total
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO
        WHERE $whereClause
    ";

    // Aplicar mismo filtro de estado para el conteo
    if ($filtros['estado_horario']) {
        $tempCountSql = "
            SELECT COUNT(*) as total FROM (
                SELECT e.ID_EMPLEADO,
                       COUNT(DISTINCT ehp.ID_EMPLEADO_HORARIO) as total_horarios_personalizados,
                       COUNT(DISTINCT CASE WHEN ehp.ACTIVO = 'S' AND ehp.FECHA_HASTA < CURDATE() THEN ehp.ID_EMPLEADO_HORARIO END) as horarios_vencidos
                FROM empleado e
                JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                JOIN sede s ON est.ID_SEDE = s.ID_SEDE
                LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO
                WHERE $whereClause
                GROUP BY e.ID_EMPLEADO
        ";
        
        switch ($filtros['estado_horario']) {
            case 'con_horarios':
                $tempCountSql .= " HAVING total_horarios_personalizados > 0";
                break;
            case 'sin_horarios':
                $tempCountSql .= " HAVING total_horarios_personalizados = 0";
                break;
            case 'horarios_vencidos':
                $tempCountSql .= " HAVING horarios_vencidos > 0";
                break;
        }
        
        $countSql = $tempCountSql . ") as filtered_employees";
    }

    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Formatear datos de respuesta
    $empleadosFormateados = array_map(function($emp) {
        return [
            'id_empleado' => $emp['ID_EMPLEADO'],
            'nombre_completo' => trim($emp['NOMBRE'] . ' ' . $emp['APELLIDO']),
            'nombre' => $emp['NOMBRE'],
            'apellido' => $emp['APELLIDO'],
            'dni' => $emp['DNI'],
            'correo' => $emp['CORREO'],
            'telefono' => $emp['TELEFONO'],
            'fecha_ingreso' => $emp['FECHA_INGRESO'],
            'sede' => [
                'id' => $emp['ID_SEDE'],
                'nombre' => $emp['sede_nombre']
            ],
            'establecimiento' => [
                'id' => $emp['ID_ESTABLECIMIENTO'],
                'nombre' => $emp['establecimiento_nombre']
            ],
            'horarios_info' => [
                'total_horarios' => (int)$emp['total_horarios_personalizados'],
                'horarios_activos' => (int)$emp['horarios_activos'],
                'horarios_vencidos' => (int)$emp['horarios_vencidos'],
                'ultima_modificacion' => $emp['ultima_modificacion_horarios'],
                'resumen' => $emp['resumen_horarios'] ?? 'Sin horarios configurados'
            ],
            'estado_horarios' => [
                'tiene_horarios' => (int)$emp['total_horarios_personalizados'] > 0,
                'tiene_activos' => (int)$emp['horarios_activos'] > 0,
                'tiene_vencidos' => (int)$emp['horarios_vencidos'] > 0,
                'estado_texto' => (int)$emp['horarios_activos'] > 0 ? 'Configurado' : 
                                ((int)$emp['total_horarios_personalizados'] > 0 ? 'Sin horarios activos' : 'Sin configurar')
            ]
        ];
    }, $empleados);

    // Respuesta final
    $response = [
        'success' => true,
        'data' => $empleadosFormateados,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_records' => (int)$totalRecords,
            'total_pages' => ceil($totalRecords / $limit),
            'has_more' => $page < ceil($totalRecords / $limit)
        ],
        'filters_applied' => array_filter($filtros, function($value) {
            return $value !== null && $value !== '';
        })
    ];
    
    // Agregar información de debug si está habilitada
    if ($debug) {
        $response['debug'] = [
            'empresa_id' => $empresaId,
            'usuario_actual' => $currentUser,
            'total_empleados_raw' => count($empleados),
            'sql_where' => $whereClause,
            'sql_params' => $params,
            'empleados_con_horarios' => array_filter($empleadosFormateados, function($emp) {
                return $emp['horarios_info']['total_horarios'] > 0;
            }),
            'timestamp' => getBogotaDateTime()
        ];
    }
    
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>