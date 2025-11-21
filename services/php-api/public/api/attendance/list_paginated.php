<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Parámetros de paginación
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

// Filtros
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$codigo = $_GET['codigo'] ?? null;
$nombre = $_GET['nombre'] ?? null;
$sede = $_GET['sede'] ?? null;
$establecimiento = $_GET['establecimiento'] ?? null;

// Empresa del usuario actual y rol
$empresaId = $_SESSION['id_empresa'] ?? 0;
$userRole = $_SESSION['rol'] ?? '';

// Para rol ASISTENCIA, forzar fecha actual
if ($userRole === 'ASISTENCIA') {
    $fecha = date('Y-m-d');
}

// Query base
$sql = "SELECT 
        a.ID_ASISTENCIA as id,
        e.ID_EMPLEADO as codigo_empleado,
        e.NOMBRE as nombre,
        e.APELLIDO as apellido,
        est.NOMBRE as establecimiento,
        s.NOMBRE as sede,
        a.FECHA as fecha,
        a.HORA as hora,
        a.TIPO as tipo,
        a.TARDANZA as tardanza,
        a.FOTO as foto
     FROM ASISTENCIA a
     JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
     JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
     JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
     WHERE s.ID_EMPRESA = :empresa_id AND a.FECHA = :fecha";

// Parámetros
$params = [':empresa_id' => $empresaId, ':fecha' => $fecha];

// Aplicar filtros
if ($codigo) {
    $sql .= " AND e.ID_EMPLEADO = :codigo";
    $params[':codigo'] = $codigo;
}
if ($nombre) {
    $nombreBusqueda = trim($nombre);
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
        $sql .= " AND (" . implode(' AND ', $condiciones) . ")";
    } else {
        // Si es una sola palabra, buscar en nombre, apellido o combinación
        $sql .= " AND (e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre OR CONCAT(e.NOMBRE, ' ', e.APELLIDO) LIKE :nombre)";
        $params[':nombre'] = '%' . $nombreBusqueda . '%';
    }
}
if ($sede) {
    $sql .= " AND s.NOMBRE LIKE :sede";
    $params[':sede'] = "%$sede%";
}
if ($establecimiento) {
    $sql .= " AND est.NOMBRE LIKE :establecimiento";
    $params[':establecimiento'] = "%$establecimiento%";
}

// Contar total de registros
$countSql = "SELECT COUNT(*) FROM ($sql) as count_query";
$countStmt = $conn->prepare($countSql);
foreach ($params as $param => $value) {
    $countStmt->bindValue($param, $value);
}
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Aplicar ordenamiento y límites
$sql .= " ORDER BY a.HORA DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);

// Bind params
foreach ($params as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Respuesta
echo json_encode([
    'success' => true,
    'data' => $asistencias,
    'page' => $page,
    'limit' => $limit,
    'total' => $totalRecords,
    'totalPages' => $totalPages
]);