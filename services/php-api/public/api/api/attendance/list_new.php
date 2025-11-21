<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';

// Verificar autenticación
requireAuth();

try {
    $currentUser = getCurrentUser();
    if (!$currentUser || !$currentUser['id_empresa']) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }
    
    $empresaId = $currentUser['id_empresa'];
    $userRole = $currentUser['rol'];
    
    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // Filtros
    $filtros = [
        'codigo' => $_GET['codigo'] ?? '',
        'sede' => $_GET['sede'] ?? '',
        'establecimiento' => $_GET['establecimiento'] ?? '',
        'nombre' => $_GET['nombre'] ?? ''
    ];

    // Construir WHERE clause básico
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    // Para rol ASISTENCIA, restringir a solo día actual
    // Lógica corregida: mostrar empleados que tuvieron ENTRADA en últimas 20 horas
    // y TODAS sus asistencias RECIENTES (incluyendo de ayer si la entrada fue reciente)
    $fecha_20_horas_atras = date('Y-m-d H:i:s', strtotime('-20 hours'));

    // Subconsulta para encontrar empleados con ENTRADA en últimas 20 horas
    $where[] = "a.ID_EMPLEADO IN (
        SELECT DISTINCT a2.ID_EMPLEADO
        FROM ASISTENCIA a2
        WHERE a2.TIPO = 'ENTRADA'
        AND CONCAT(a2.FECHA, ' ', a2.HORA) >= :fecha_20_horas_atras
    )";

    // También filtrar que las asistencias mostradas sean de las últimas 20 horas
    $where[] = "CONCAT(a.FECHA, ' ', a.HORA) >= :fecha_20_horas_atras";

    $params[':fecha_20_horas_atras'] = $fecha_20_horas_atras;

    // Aplicar filtros adicionales
    if ($filtros['codigo']) {
        $where[] = "e.ID_EMPLEADO = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    if ($filtros['nombre']) {
        $nombreBusqueda = trim($filtros['nombre']);
        $palabras = array_filter(explode(' ', $nombreBusqueda)); // Separar por espacios y filtrar vacíos
        
        if (count($palabras) > 1) {
            // Si hay múltiples palabras, buscar cada una en nombre o apellido
            $condiciones = [];
            foreach ($palabras as $index => $palabra) {
                $paramNombre = ":nombre_{$index}";
                $paramApellido = ":apellido_{$index}";
                $condiciones[] = "(e.NOMBRE LIKE {$paramNombre} OR e.APELLIDO LIKE {$paramApellido})";
                $params[$paramNombre] = '%' . $palabra . '%';
                $params[$paramApellido] = '%' . $palabra . '%';
            }
            $where[] = '(' . implode(' AND ', $condiciones) . ')';
        } else {
            // Si es una sola palabra, buscar en nombre, apellido o combinación
            $where[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre OR CONCAT(e.NOMBRE, ' ', e.APELLIDO) LIKE :nombre)";
            $params[':nombre'] = '%' . $nombreBusqueda . '%';
        }
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para contar total de registros
    $countSql = "
        SELECT COUNT(*) as total
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN HORARIO h ON h.ID_HORARIO = a.ID_HORARIO
        WHERE {$whereClause}
    ";

    $countStmt = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consulta principal con paginación
    $sql = "
        SELECT 
            a.ID_ASISTENCIA as id,
            e.ID_EMPLEADO as codigo_empleado,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_empleado,
            est.NOMBRE as establecimiento,
            s.NOMBRE as sede,
            a.FECHA as fecha,
            a.HORA as hora,
            a.TIPO as tipo,
            a.TARDANZA as tardanza,
            a.OBSERVACION as observacion,
            a.FOTO as foto,
            a.REGISTRO_MANUAL as registro_manual,
            a.ID_HORARIO,
            h.NOMBRE AS HORARIO_NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN HORARIO h ON h.ID_HORARIO = a.ID_HORARIO
        WHERE {$whereClause}
        ORDER BY CONCAT(a.FECHA, ' ', a.HORA) DESC, a.ID_ASISTENCIA DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $conn->prepare($sql);
    
    // Bind parámetros de filtro
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind parámetros de paginación
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar asistencias para calcular estados
    $result = [];
    foreach ($asistencias as $asistencia) {
        $estado = '--';
        $tolerancia = (int)($asistencia['TOLERANCIA'] ?? 0);
        
        if ($asistencia['tipo'] === 'ENTRADA' && $asistencia['HORA_ENTRADA']) {
            // Calcular estado de entrada
            $ts_entrada_programada = strtotime($asistencia['fecha'] . ' ' . $asistencia['HORA_ENTRADA']);
            $ts_entrada_real = strtotime($asistencia['fecha'] . ' ' . $asistencia['hora']);
            
            if ($ts_entrada_real < $ts_entrada_programada - $tolerancia * 60) {
                $estado = 'Temprano';
            } elseif ($ts_entrada_real <= $ts_entrada_programada + $tolerancia * 60) {
                $estado = 'Puntual';
            } else {
                $estado = 'Tardanza';
            }
        } elseif ($asistencia['tipo'] === 'SALIDA' && $asistencia['HORA_SALIDA']) {
            // Calcular estado de salida
            $ts_salida_programada = strtotime($asistencia['fecha'] . ' ' . $asistencia['HORA_SALIDA']);
            $ts_salida_real = strtotime($asistencia['fecha'] . ' ' . $asistencia['hora']);
            
            if ($ts_salida_real < $ts_salida_programada - $tolerancia * 60) {
                $estado = 'Temprano';
            } elseif ($ts_salida_real <= $ts_salida_programada + $tolerancia * 60) {
                $estado = 'Normal';
            } else {
                $estado = 'Tardanza';
            }
        }
        
        // Agregar estado al registro
        $asistencia['estado'] = $estado;
        $result[] = $asistencia;
    }

    // Calcular información de paginación
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
        ],
        'filters_applied' => array_filter($filtros)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar asistencias: ' . $e->getMessage()
    ]);
}
?>
