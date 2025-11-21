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

    // Parámetros de filtro
    $filtros = [
        'codigo' => isset($_GET['codigo']) ? intval($_GET['codigo']) : null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => isset($_GET['sede']) ? intval($_GET['sede']) : null,
        'establecimiento' => isset($_GET['establecimiento']) ? intval($_GET['establecimiento']) : null,
        'estado' => $_GET['estado'] ?? null
    ];

    // Construcción de la consulta - MÁS PERMISIVA
    $where = [];
    $params = [];
    
    // Solo filtrar por empresa para roles específicos
    if (in_array($userRole, ['ASISTENCIA', 'EMPLEADO'])) {
        $where[] = "s.ID_EMPRESA = ?";
        $params[] = $empresaId;
    }
    
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

    if (!empty($filtros['estado'])) {
        if ($filtros['estado'] === 'enrolled') {
            $where[] = "EXISTS (SELECT 1 FROM biometric_data bd WHERE bd.ID_EMPLEADO = e.ID_EMPLEADO)";
        } else if ($filtros['estado'] === 'pending') {
            $where[] = "NOT EXISTS (SELECT 1 FROM biometric_data bd WHERE bd.ID_EMPLEADO = e.ID_EMPLEADO)";
        }
    }

    // Por defecto, mostrar solo empleados activos
    $where[] = "e.ACTIVO = 'S'";

    $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

    // Obtener límite si se proporciona (con valor predeterminado 100)
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $limit = max(1, min(500, $limit)); // Garantizar un rango razonable

    $query = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.DNI AS IDENTIFICACION,
            e.NOMBRE,
            e.APELLIDO,
            e.ESTADO,
            e.ACTIVO,
            e.FECHA_REGISTRO,
            est.NOMBRE AS ESTABLECIMIENTO,
            s.NOMBRE AS SEDE
        FROM 
            empleado e
        LEFT JOIN 
            establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN 
            sede s ON est.ID_SEDE = s.ID_SEDE
        {$whereClause}
        ORDER BY 
            e.NOMBRE, e.APELLIDO
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // Asegurarnos de devolver la misma estructura que list-fixed.php
    echo json_encode([
        'success' => true,
        'message' => 'Empleados obtenidos correctamente',
        'data' => $employees,
        'total' => $total,
        'filtered' => count($employees),
        'debug_info' => [
            'limit' => $limit,
            'filter_count' => count($where)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
