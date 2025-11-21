<?php
require_once '../auth/session.php';
require_once '../config/database.php';

requireAuth();

$currentUser = getCurrentUser();
$empresaId = $currentUser['id_empresa'] ?? null;

if (!$empresaId) {
    echo json_encode(['success' => false, 'message' => 'Empresa no válida']);
    exit;
}

try {
    // Obtener filtros de la consulta
    $filtros = [];
    $sede = $_GET['sede'] ?? null;
    $establecimiento = $_GET['establecimiento'] ?? null;
    $estado = $_GET['estado'] ?? null;
    $search = $_GET['search'] ?? null;

    // Construir consulta SQL para obtener empleados
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.DNI,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
            s.NOMBRE as sede_nombre,
            est.NOMBRE as establecimiento_nombre,
            e.ACTIVO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id
    ";

    $params = ['empresa_id' => $empresaId];

    if ($sede) {
        $sql .= " AND s.ID_SEDE = :sede";
        $params['sede'] = $sede;
    }

    if ($establecimiento) {
        $sql .= " AND est.ID_ESTABLECIMIENTO = :establecimiento";
        $params['establecimiento'] = $establecimiento;
    }

    if ($search) {
        $sql .= " AND (CONCAT(e.NOMBRE, ' ', e.APELLIDO) LIKE :search OR e.DNI LIKE :search)";
        $params['search'] = "%$search%";
    }

    $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'empleados' => $empleados
    ]);

} catch (Exception $e) {
    error_log('Error obteniendo empleados para exportación: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener empleados: ' . $e->getMessage()
    ]);
}
?>