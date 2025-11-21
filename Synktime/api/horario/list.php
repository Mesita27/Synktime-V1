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
        'id' => $_GET['id'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'dia' => $_GET['dia'] ?? null
    ];

    // Construcción de la consulta
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    if ($filtros['id']) {
        $where[] = "h.ID_HORARIO = :id";
        $params[':id'] = $filtros['id'];
    }

    if ($filtros['nombre']) {
        $where[] = "h.NOMBRE LIKE :nombre";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "e.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    if ($filtros['dia']) {
        $where[] = "EXISTS (SELECT 1 FROM HORARIO_DIA hd WHERE hd.ID_HORARIO = h.ID_HORARIO AND hd.ID_DIA = :dia)";
        $params[':dia'] = $filtros['dia'];
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para contar total de registros
    $countSql = "
        SELECT COUNT(DISTINCT h.ID_HORARIO) as total
        FROM horario h
        JOIN establecimiento e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
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
            h.ID_HORARIO,
            h.NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            s.ID_SEDE,
            s.NOMBRE as sede,
            e.ID_ESTABLECIMIENTO,
            e.NOMBRE as establecimiento,
            (SELECT COUNT(*) FROM EMPLEADO_HORARIO eh WHERE eh.ID_HORARIO = h.ID_HORARIO) as empleados_count
        FROM horario h
        JOIN establecimiento e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
        GROUP BY h.ID_HORARIO
        ORDER BY h.ID_HORARIO DESC
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
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener días para cada horario
    foreach ($horarios as &$horario) {
        $sqlDias = "
            SELECT ID_DIA 
            FROM HORARIO_DIA 
            WHERE ID_HORARIO = :id_horario 
            ORDER BY ID_DIA
        ";
        $stmtDias = $conn->prepare($sqlDias);
        $stmtDias->bindValue(':id_horario', $horario['ID_HORARIO']);
        $stmtDias->execute();
        $dias = $stmtDias->fetchAll(PDO::FETCH_COLUMN);
        $horario['dias'] = implode(',', $dias);
    }

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $horarios,
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
        'message' => 'Error al cargar horarios: ' . $e->getMessage()
    ]);
}
?>
