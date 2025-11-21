<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(100, intval($_GET['limit'] ?? 50))); // Límite más alto para ver más
    $offset = ($page - 1) * $limit;

    // Consulta SIN filtrar por empresa para ver todos los empleados
    $countSql = "
        SELECT COUNT(*) as total
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ACTIVO = 'S'
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consulta principal - TODOS los empleados activos
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
            e.ESTADO as estado,
            e.ACTIVO as activo
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ACTIVO = 'S'
        ORDER BY s.ID_EMPRESA, s.NOMBRE, est.NOMBRE, e.APELLIDO, e.NOMBRE
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);

    // Agrupar estadísticas por empresa
    $empresaStats = [];
    foreach ($empleados as $emp) {
        $empresaId = $emp['empresa_id'];
        if (!isset($empresaStats[$empresaId])) {
            $empresaStats[$empresaId] = ['count' => 0, 'sedes' => []];
        }
        $empresaStats[$empresaId]['count']++;
        $empresaStats[$empresaId]['sedes'][$emp['sede']] = ($empresaStats[$empresaId]['sedes'][$emp['sede']] ?? 0) + 1;
    }

    echo json_encode([
        'success' => true,
        'debug_mode' => true,
        'session_empresa' => $_SESSION['id_empresa'] ?? 'No definida',
        'session_role' => $_SESSION['rol'] ?? 'No definido',
        'employees' => $empleados,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $totalRecords,
            'per_page' => $limit
        ],
        'empresa_stats' => $empresaStats,
        'query_info' => [
            'filtered_by_empresa' => false,
            'total_found' => count($empleados),
            'limit_applied' => $limit,
            'offset_applied' => $offset
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug_mode' => true
    ]);
}
?>
