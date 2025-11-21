<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';

// Verificar autenticación
requireAuth();

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    $userRole = $currentUser['rol'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Empresa no válida']);
        exit;
    }

    // Parámetros de paginación - Asegurar que sean enteros
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $page = max(1, $page); // Asegurar que page sea al menos 1
    
    // Asegurar que el límite sea un entero válido
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $limit = max(1, min(500, $limit)); // Entre 1 y 500
    
    // Calcular el offset manualmente para evitar problemas con la consulta preparada
    $offset = ($page - 1) * $limit;

    // Parámetros de filtro
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'identificacion' => $_GET['identificacion'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'estado' => $_GET['estado'] ?? null
    ];

    // Construcción de la consulta - SEGURA
    $where = [];
    $params = [];
    
    // SIEMPRE filtrar por empresa para todos los roles
    $where[] = "s.ID_EMPRESA = ?";
    $params[] = $empresaId;

    // Aplicar otros filtros
    if ($filtros['codigo']) {
        $where[] = "e.ID_EMPLEADO = ?";
        $params[] = $filtros['codigo'];
    }

    if ($filtros['identificacion']) {
        $where[] = "e.DNI LIKE ?";
        $params[] = '%' . $filtros['identificacion'] . '%';
    }

    if ($filtros['nombre']) {
        $where[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
        $params[] = '%' . $filtros['nombre'] . '%';
        $params[] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = ?";
        $params[] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = ?";
        $params[] = $filtros['establecimiento'];
    }

    if ($filtros['estado']) {
        $where[] = "e.ESTADO = ?";
        $params[] = $filtros['estado'];
    } else {
        $where[] = "e.ACTIVO = 'S'"; // Solo activos por defecto
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : 'WHERE e.ACTIVO = \'S\'';

    // Consulta para contar total de registros
    $countSql = "
        SELECT COUNT(*) as total
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        {$whereClause}
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consulta principal con paginación
    $sql = "
        SELECT 
            e.ID_EMPLEADO as id,
            e.DNI as identificacion,
            e.NOMBRE as nombre,
            e.APELLIDO as apellido,
            e.CORREO as email,
            e.TELEFONO as telefono,
            est.NOMBRE as establecimiento,
            est.ID_ESTABLECIMIENTO as establecimiento_id,
            s.NOMBRE as sede,
            s.ID_SEDE as sede_id,
            s.ID_EMPRESA as empresa_id,
            e.FECHA_INGRESO as fecha_contratacion,
            e.ESTADO as estado
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        {$whereClause}
        ORDER BY s.ID_EMPRESA, s.NOMBRE, est.NOMBRE, e.APELLIDO, e.NOMBRE
        LIMIT $limit OFFSET $offset
    ";

    // Eliminamos los parámetros para LIMIT y OFFSET e incluimos directamente los valores
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);

    echo json_encode([
        'success' => true,
        // Asegurar consistencia con otras APIs - importante: data en lugar de employees
        'data' => $empleados,
        'employees' => $empleados, // Para compatibilidad con código existente
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $totalRecords,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ],
        'filters' => $filtros,
        'user_info' => [
            'empresa_id' => $empresaId,
            'role' => $userRole,
            'empresa_filter_applied' => in_array($userRole, ['ASISTENCIA', 'EMPLEADO'])
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en employee/list-fixed.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar empleados: ' . $e->getMessage()
    ]);
}
?>
