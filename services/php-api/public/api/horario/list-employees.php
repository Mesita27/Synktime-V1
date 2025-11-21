<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10))); // Entre 10 y 50
    $offset = ($page - 1) * $limit;

    // Parámetros de filtro
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'identificacion' => $_GET['identificacion'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null
    ];

    // Construcción de la consulta
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    if ($filtros['codigo']) {
        $where[] = "e.ID_EMPLEADO = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['identificacion']) {
        $where[] = "e.DNI LIKE :identificacion";
        $params[':identificacion'] = '%' . $filtros['identificacion'] . '%';
    }

    if ($filtros['nombre']) {
        $where[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre)";
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

    // Consulta para contar total de registros
    $countSql = "
        SELECT COUNT(*) as total
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
    ";

    $countStmt = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consulta principal para obtener empleados con conteo de horarios
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            est.ID_ESTABLECIMIENTO,
            est.NOMBRE as establecimiento,
            s.ID_SEDE,
            s.NOMBRE as sede,
            (
                SELECT COUNT(DISTINCT eh.ID_HORARIO)
                FROM EMPLEADO_HORARIO eh
                WHERE eh.ID_EMPLEADO = e.ID_EMPLEADO
                AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= CURDATE())
            ) as horarios_count
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
        ORDER BY e.APELLIDO, e.NOMBRE
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
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $empleados,
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
        'message' => 'Error al cargar empleados: ' . $e->getMessage()
    ]);
}
?>