<?php
/**
 * API ESPECÍFICA PARA EL MÓDULO DE INSCRIPCIÓN BIOMÉTRICA
 * Este endpoint está optimizado para mostrar empleados con su estado biométrico
 * Solo devuelve datos reales de la base de datos
 */

// Configuración de headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejo de preflight CORS
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';

try {
    $pdo = synktime_get_pdo();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Parámetros de la solicitud
    $busqueda = $_GET['busqueda'] ?? '';
    $sede = $_GET['sede'] ?? '';
    $establecimiento = $_GET['establecimiento'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Construir consulta SQL
    $sql = "SELECT 
                e.ID_EMPLEADO,
                e.ID_EMPLEADO as codigo,
                e.NOMBRE,
                e.APELLIDO,
                e.DNI,
                e.ID_ESTABLECIMIENTO,
                est.NOMBRE AS establecimiento,
                s.NOMBRE AS sede,
                s.ID_SEDE,
                -- Estado biométrico usando employee_biometrics
                CASE
                    WHEN eb_facial.id IS NOT NULL THEN 1
                    ELSE 0
                END as facial_enrolled,
                CASE
                    WHEN eb_finger.id IS NOT NULL THEN 1
                    ELSE 0
                END as fingerprint_enrolled,
                GREATEST(
                    COALESCE(eb_facial.updated_at, '1970-01-01'),
                    COALESCE(eb_finger.updated_at, '1970-01-01')
                ) as last_updated
            FROM empleado e
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id
                AND eb_facial.biometric_type = 'face'
            LEFT JOIN employee_biometrics eb_finger ON e.ID_EMPLEADO = eb_finger.employee_id
                AND eb_finger.biometric_type = 'fingerprint'
            WHERE e.ESTADO = 'A'";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($busqueda)) {
        $sql .= " AND (e.NOMBRE LIKE :busqueda OR e.APELLIDO LIKE :busqueda OR e.DNI LIKE :busqueda OR e.ID_EMPLEADO LIKE :busqueda)";
        $params['busqueda'] = "%$busqueda%";
    }
    
    if (!empty($sede)) {
        $sql .= " AND s.ID_SEDE = :sede";
        $params['sede'] = $sede;
    }
    
    if (!empty($establecimiento)) {
        $sql .= " AND est.ID_ESTABLECIMIENTO = :establecimiento";
        $params['establecimiento'] = $establecimiento;
    }
    
    if (!empty($estado)) {
        switch ($estado) {
            case 'enrolled':
                $sql .= " AND eb_facial.id IS NOT NULL AND eb_finger.id IS NOT NULL";
                break;
            case 'partial':
                $sql .= " AND (eb_facial.id IS NOT NULL OR eb_finger.id IS NOT NULL) AND NOT (eb_facial.id IS NOT NULL AND eb_finger.id IS NOT NULL)";
                break;
            case 'pending':
                $sql .= " AND eb_facial.id IS NULL AND eb_finger.id IS NULL";
                break;
        }
    }
    
    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total FROM ($sql) as count_query";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    
    // Agregar orden y paginación
    $sql .= " ORDER BY e.NOMBRE ASC LIMIT $limit OFFSET $offset";
    
    // Ejecutar consulta principal
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();
    
    // Procesar datos para el frontend
    $processedEmployees = [];
    foreach ($employees as $emp) {
        // Determinar estado biométrico
        $facial = (bool)$emp['facial_enrolled'];
        $fingerprint = (bool)$emp['fingerprint_enrolled'];
        
        if ($facial && $fingerprint) {
            $status = 'enrolled';
        } elseif ($facial || $fingerprint) {
            $status = 'partial';
        } else {
            $status = 'pending';
        }
        
        $processedEmployees[] = [
            'id' => $emp['ID_EMPLEADO'],
            'ID_EMPLEADO' => $emp['ID_EMPLEADO'],
            'codigo' => $emp['codigo'],
            'CODIGO' => $emp['codigo'],
            'nombre' => $emp['NOMBRE'] . ' ' . $emp['APELLIDO'],
            'NOMBRE' => $emp['NOMBRE'],
            'APELLIDO' => $emp['APELLIDO'],
            'DNI' => $emp['DNI'],
            'establecimiento' => $emp['establecimiento'],
            'ESTABLECIMIENTO' => $emp['establecimiento'],
            'sede' => $emp['sede'],
            'SEDE' => $emp['sede'],
            'ID_SEDE' => $emp['ID_SEDE'],
            'ID_ESTABLECIMIENTO' => $emp['ID_ESTABLECIMIENTO'],
            'biometric_status' => $status,
            'facial_enrolled' => $facial,
            'fingerprint_enrolled' => $fingerprint,
            'last_updated' => $emp['last_updated']
        ];
    }
    
    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;
    
    // Calcular estadísticas biométricas
    $sqlStats = "
        SELECT 
            COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
            COUNT(DISTINCT CASE WHEN bd_facial.ID IS NOT NULL OR bd_finger.ID IS NOT NULL THEN e.ID_EMPLEADO END) as total_inscritos,
            COUNT(DISTINCT CASE WHEN bd_facial.ID IS NOT NULL THEN e.ID_EMPLEADO END) as facial_inscritos,
            COUNT(DISTINCT CASE WHEN bd_finger.ID IS NOT NULL THEN e.ID_EMPLEADO END) as huella_inscritos
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN biometric_data bd_facial ON e.ID_EMPLEADO = bd_facial.ID_EMPLEADO
            AND bd_facial.BIOMETRIC_TYPE = 'face'
            AND bd_facial.ACTIVO = 'S'
        LEFT JOIN biometric_data bd_finger ON e.ID_EMPLEADO = bd_finger.ID_EMPLEADO
            AND bd_finger.BIOMETRIC_TYPE = 'fingerprint'
            AND bd_finger.ACTIVO = 'S'
        WHERE e.ESTADO = 'A'
    ";
    
    // Aplicar filtros de sede y establecimiento para las estadísticas
    $statsParams = [];
    if (!empty($sede)) {
        $sqlStats .= " AND s.ID_SEDE = :sede_stats";
        $statsParams['sede_stats'] = $sede;
    }
    
    if (!empty($establecimiento)) {
        $sqlStats .= " AND est.ID_ESTABLECIMIENTO = :establecimiento_stats";
        $statsParams['establecimiento_stats'] = $establecimiento;
    }
    
    // Ejecutar la consulta de estadísticas
    $statsStmt = $pdo->prepare($sqlStats);
    $statsStmt->execute($statsParams);
    $stats = $statsStmt->fetch();
    
    // Calcular empleados sin inscripción biométrica
    $stats['no_inscritos'] = $stats['total_empleados'] - $stats['total_inscritos'];
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'Empleados cargados para inscripción biométrica',
        'count' => count($processedEmployees),
        'data' => $processedEmployees,
        'stats' => $stats,
        'pagination' => [
            'current_page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev
        ],
        'module' => 'biometric_enrollment',
        'endpoint' => 'enrollment-employees',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'count' => 0,
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'count' => 0,
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
