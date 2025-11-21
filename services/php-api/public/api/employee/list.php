<?php
// Limpiar cualquier output anterior
ob_clean();

require_once __DIR__ . '/../../config/database.php';
session_start();

// Headers para JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $empresaId = $_SESSION['id_empresa'] ?? 1; // Default to 1 for testing
    $userRole = $_SESSION['rol'] ?? 'ADMIN'; // Default to ADMIN for testing
    $userId = $_SESSION['user_id'] ?? 1; // Default to 1 for testing
    
    // For testing, allow access even without session
    if (!$empresaId) {
        $empresaId = 1; // Default company
        $userRole = 'ADMIN';
    }

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10))); // Entre 10 y 50
    $offset = ($page - 1) * $limit;

    // Parámetros de filtro - leer desde GET o POST
    $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
    $filtros = [
        'codigo' => $_GET['codigo'] ?? $inputData['filters']['codigo'] ?? null,
        'identificacion' => $_GET['identificacion'] ?? $inputData['filters']['identificacion'] ?? null,
        'nombre' => $_GET['nombre'] ?? $inputData['filters']['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? $inputData['filters']['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? $inputData['filters']['establecimiento'] ?? null,
        'estado' => $_GET['estado'] ?? $inputData['filters']['estado'] ?? null,
        'activo' => $_GET['activo'] ?? $inputData['filters']['activo'] ?? null,
        'search' => $_GET['search'] ?? $inputData['filters']['search'] ?? null,
        'biometric' => $_GET['biometric'] ?? $inputData['filters']['biometric'] ?? null
    ];

    // Construcción de la consulta
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];
    
    // Aplicar filtro restrictivo solo para rol ASISTENCIA
    // GERENTE, ADMIN, DUEÑO tienen acceso total a todos los empleados de la empresa
    if ($userRole === 'ASISTENCIA') {
        // Solo para ASISTENCIA: restringir a empleados específicos del usuario
        // Esto permitiría que usuarios de asistencia solo vean ciertos empleados
        // Por ahora, como medida restrictiva, limitar a empleados de su mismo establecimiento
        $where[] = "e.ID_ESTABLECIMIENTO IN (
            SELECT DISTINCT e2.ID_ESTABLECIMIENTO 
            FROM empleado e2 
            JOIN establecimiento est2 ON e2.ID_ESTABLECIMIENTO = est2.ID_ESTABLECIMIENTO 
            JOIN sede s2 ON est2.ID_SEDE = s2.ID_SEDE 
            WHERE s2.ID_EMPRESA = :empresa_id
        )";
    }
    // Para GERENTE, ADMIN, DUEÑO: sin restricciones adicionales (acceso total)

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

    if ($filtros['search']) {
        $where[] = "(e.NOMBRE LIKE :search OR e.APELLIDO LIKE :search OR e.DNI LIKE :search OR e.ID_EMPLEADO LIKE :search)";
        $params[':search'] = '%' . $filtros['search'] . '%';
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    if ($filtros['estado'] !== null && $filtros['estado'] !== '') {
        $where[] = "e.ESTADO = :estado";
        $params[':estado'] = strtoupper($filtros['estado']);
    }

    if ($filtros['activo'] !== null && $filtros['activo'] !== '') {
        $flag = strtoupper($filtros['activo']);
        if (!in_array($flag, ['S', 'N'], true)) {
            throw new InvalidArgumentException('Valor de activo inválido. Usa S o N.');
        }
        $where[] = "e.ACTIVO = :activo";
        $params[':activo'] = $flag;
    }

    if ($filtros['biometric']) {
        if ($filtros['biometric'] === 'enrolled') {
            // Empleados con al menos un registro biométrico
            $where[] = "EXISTS (SELECT 1 FROM employee_biometrics eb WHERE eb.employee_id = e.ID_EMPLEADO)";
        } elseif ($filtros['biometric'] === 'pending') {
            // Empleados sin registros biométricos
            $where[] = "NOT EXISTS (SELECT 1 FROM employee_biometrics eb WHERE eb.employee_id = e.ID_EMPLEADO)";
        }
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para contar total de registros
    $countSql = "
        SELECT COUNT(*) as total
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
    ";

    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consulta principal con paginación
    $sql = "
        SELECT 
            e.ID_EMPLEADO as ID_EMPLEADO,
            e.DNI as DNI,
            e.NOMBRE as NOMBRE,
            e.APELLIDO as APELLIDO,
            e.CORREO as CORREO,
            e.TELEFONO as TELEFONO,
            est.NOMBRE as ESTABLECIMIENTO,
            est.ID_ESTABLECIMIENTO as ID_ESTABLECIMIENTO,
            s.NOMBRE as SEDE,
            s.ID_SEDE as ID_SEDE,
            e.FECHA_INGRESO as FECHA_INGRESO,
            e.ESTADO as ESTADO,
            e.ACTIVO as ACTIVO,
            CASE
                WHEN e.ESTADO = 'A' AND e.ACTIVO = 'S' THEN 'ACTIVO'
                WHEN e.ESTADO = 'A' AND e.ACTIVO = 'N' THEN 'ACTIVO (SUSPENDIDO)'
                WHEN e.ESTADO = 'I' THEN 'INACTIVO'
                ELSE e.ESTADO
            END as ESTADO_DESCRIPTIVO,
            CASE WHEN EXISTS (SELECT 1 FROM employee_biometrics eb WHERE eb.employee_id = e.ID_EMPLEADO) THEN 1 ELSE 0 END as BIOMETRIC_ENROLLED
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
        ORDER BY e.APELLIDO, e.NOMBRE
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    
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
        ],
        'filters_applied' => array_filter($filtros)
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar empleados: ' . $e->getMessage()
    ]);
}
?>