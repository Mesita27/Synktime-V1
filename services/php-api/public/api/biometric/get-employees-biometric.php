<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';

// Verificar autenticación
requireAuth();

header('Content-Type: application/json');

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    $userRole = $currentUser['rol'] ?? null;

    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Empresa no válida']);
        exit;
    }

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10))); // Entre 10 y 50
    $offset = ($page - 1) * $limit;

    // Parámetros de filtro
    $filtros = [
        'codigo' => isset($_GET['codigo']) ? intval($_GET['codigo']) : null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => isset($_GET['sede']) ? intval($_GET['sede']) : null,
        'establecimiento' => isset($_GET['establecimiento']) ? intval($_GET['establecimiento']) : null,
        'estado' => $_GET['estado'] ?? null
    ];

    // Construcción de la consulta
    $where = [];
    $params = [];

    // SIEMPRE filtrar por empresa del usuario logueado
    $where[] = "s.ID_EMPRESA = ?";
    $params[] = $empresaId;

    // Aplicar otros filtros
    if (!empty($filtros['codigo'])) {
        $where[] = "e.ID_EMPLEADO = ?";
        $params[] = $filtros['codigo'];
    }

    if (!empty($filtros['nombre'])) {
        $where[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
        $params[] = "%{$filtros['nombre']}%";
        $params[] = "%{$filtros['nombre']}%";
    }

    if (!empty($filtros['sede'])) {
        $where[] = "s.ID_SEDE = ?";
        $params[] = $filtros['sede'];
    }

    if (!empty($filtros['establecimiento'])) {
        $where[] = "est.ID_ESTABLECIMIENTO = ?";
        $params[] = $filtros['establecimiento'];
    }

    // Por defecto, mostrar solo empleados activos
    $where[] = "e.ACTIVO = 'S'";

    $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

    // Obtener límite si se proporciona
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10)));
    $limit = max(1, min(500, $limit));

    $query = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.DNI AS IDENTIFICACION,
            e.NOMBRE,
            e.APELLIDO,
            e.ESTADO,
            e.ACTIVO,
            e.FECHA_INGRESO,
            est.NOMBRE AS ESTABLECIMIENTO,
            est.ID_ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            s.ID_SEDE,
            -- Información biométrica
            CASE WHEN eb_facial.id IS NOT NULL THEN 1 ELSE 0 END as facial_enrolled,
            CASE WHEN eb_huella.id IS NOT NULL THEN 1 ELSE 0 END as fingerprint_enrolled,
            CASE WHEN eb_rfid.id IS NOT NULL THEN 1 ELSE 0 END as rfid_enrolled,
            -- Últimas actualizaciones
            GREATEST(
                COALESCE(eb_facial.updated_at, '2000-01-01'),
                COALESCE(eb_huella.updated_at, '2000-01-01'),
                COALESCE(eb_rfid.updated_at, '2000-01-01')
            ) as last_update
        FROM
            empleado e
        LEFT JOIN
            establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN
            sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN
            employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id
            AND eb_facial.biometric_type = 'face'
        LEFT JOIN
            employee_biometrics eb_huella ON e.ID_EMPLEADO = eb_huella.employee_id
            AND eb_huella.biometric_type = 'fingerprint'
        LEFT JOIN
            employee_biometrics eb_rfid ON e.ID_EMPLEADO = eb_rfid.employee_id
            AND eb_rfid.biometric_type = 'rfid'
        {$whereClause}
        ORDER BY
            e.NOMBRE, e.APELLIDO
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar empleados para agregar campos calculados
    foreach ($employees as &$employee) {
        // Generar código si no existe
        if (!isset($employee['CODIGO']) || empty($employee['CODIGO'])) {
            $employee['CODIGO'] = 'EMP' . str_pad($employee['ID_EMPLEADO'], 4, '0', STR_PAD_LEFT);
        }

        // Formatear nombre completo
        $employee['NOMBRE_COMPLETO'] = trim($employee['NOMBRE'] . ' ' . $employee['APELLIDO']);

        // Calcular estado biométrico general
        $biometric_count = 0;
        if ($employee['facial_enrolled']) $biometric_count++;
        if ($employee['fingerprint_enrolled']) $biometric_count++;
        if ($employee['rfid_enrolled']) $biometric_count++;

        if ($biometric_count === 3) {
            $employee['estado_biometrico'] = 'completo';
        } elseif ($biometric_count > 0) {
            $employee['estado_biometrico'] = 'parcial';
        } else {
            $employee['estado_biometrico'] = 'pendiente';
        }

        // Formatear fecha de última actualización
        if ($employee['last_update'] && $employee['last_update'] !== '2000-01-01 00:00:00') {
            $employee['last_update_formatted'] = date('d/m/Y H:i', strtotime($employee['last_update']));
        } else {
            $employee['last_update_formatted'] = 'Nunca';
        }
    }

    // Contar el total de registros
    $countQuery = "
        SELECT COUNT(DISTINCT e.ID_EMPLEADO) as total
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        {$whereClause}
    ";

    $stmtCount = $pdo->prepare($countQuery);
    if (!empty($params)) {
        $stmtCount->execute($params);
    } else {
        $stmtCount->execute();
    }

    $totalRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $total = $totalRow ? (int)$totalRow['total'] : 0;

    // Estadísticas biométricas
    $statsQuery = "
        SELECT
            COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
            COUNT(DISTINCT CASE WHEN eb_facial.id IS NOT NULL THEN e.ID_EMPLEADO END) as facial_count,
            COUNT(DISTINCT CASE WHEN eb_huella.id IS NOT NULL THEN e.ID_EMPLEADO END) as fingerprint_count,
            COUNT(DISTINCT CASE WHEN eb_rfid.id IS NOT NULL THEN e.ID_EMPLEADO END) as rfid_count
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id
            AND eb_facial.biometric_type = 'face'
        LEFT JOIN employee_biometrics eb_huella ON e.ID_EMPLEADO = eb_huella.employee_id
            AND eb_huella.biometric_type = 'fingerprint'
        LEFT JOIN employee_biometrics eb_rfid ON e.ID_EMPLEADO = eb_rfid.employee_id
            AND eb_rfid.biometric_type = 'rfid'
        {$whereClause}
    ";

    $stmtStats = $pdo->prepare($statsQuery);
    if (!empty($params)) {
        $stmtStats->execute($params);
    } else {
        $stmtStats->execute();
    }

    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    // Calcular porcentajes
    $enrolled_count = max($stats['facial_count'], $stats['fingerprint_count'], $stats['rfid_count']);
    $enrollment_percentage = $total > 0 ? round(($enrolled_count / $total) * 100, 1) : 0;

    // Calcular información de paginación
    $totalPages = ceil($total / $limit);

    echo json_encode([
        'success' => true,
        'message' => 'Empleados obtenidos correctamente',
        'employees' => $employees,
        'total' => $total,
        'filtered' => count($employees),
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $total,
            'limit' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ],
        'stats' => [
            'total_employees' => (int)$stats['total_empleados'],
            'enrolled_count' => $enrolled_count,
            'pending_count' => $total - $enrolled_count,
            'enrollment_percentage' => $enrollment_percentage,
            'facial_count' => (int)$stats['facial_count'],
            'fingerprint_count' => (int)$stats['fingerprint_count'],
            'rfid_count' => (int)$stats['rfid_count']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en get-employees-biometric.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
