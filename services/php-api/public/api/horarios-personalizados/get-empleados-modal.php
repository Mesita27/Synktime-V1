<?php
// Desactivar errores HTML para APIs JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Limpiar cualquier output anterior únicamente si existe un buffer activo
if (ob_get_level() > 0) {
    ob_clean();
}

// Capturar cualquier error fatal
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Error fatal: ' . $error['message'] . ' en ' . $error['file'] . ':' . $error['line']
        ]);
        exit;
    }
});

require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación sin redirigir
if (!isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesión expirada o usuario no autenticado'
    ]);
    exit;
}

$currentUser = getCurrentUser();
$empresaId = $currentUser['id_empresa'] ?? null;

if (!$empresaId) {
    echo json_encode(['success' => false, 'message' => 'Empresa no válida']);
    exit;
}

try {
    // Obtener filtros de la consulta
    $filtros = [];
    $sedeId = $_GET['sede_id'] ?? null;
    $establecimientoId = $_GET['establecimiento_id'] ?? null;

    // Verificar conexión a la base de datos
    if (!isset($conn) || !$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    // Construir consulta SQL para obtener empleados
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            s.NOMBRE as SEDE_NOMBRE,
            est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
            e.ACTIVO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id
        AND e.ACTIVO = 'S'
    ";

    $params = ['empresa_id' => $empresaId];

    if ($sedeId) {
        $sql .= " AND s.ID_SEDE = :sede_id";
        $params['sede_id'] = $sedeId;
    }

    if ($establecimientoId) {
        $sql .= " AND est.ID_ESTABLECIMIENTO = :establecimiento_id";
        $params['establecimiento_id'] = $establecimientoId;
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
    error_log('Error obteniendo empleados para modal: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener empleados: ' . $e->getMessage()
    ]);
}
?>