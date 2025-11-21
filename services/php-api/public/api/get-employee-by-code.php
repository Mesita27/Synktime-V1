<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../auth/session.php';

// Verificar autenticación
requireAuth();

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;

    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Empresa no válida']);
        exit;
    }

    $codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : null;
    $sedeId = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
    $establecimientoId = isset($_GET['establecimiento_id']) ? intval($_GET['establecimiento_id']) : null;

    if (!$codigo) {
        echo json_encode(['success' => false, 'message' => 'Código de empleado requerido']);
        exit;
    }

    // Buscar empleado por código
    $sql = "
        SELECT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.CODIGO_EMPLEADO,
            est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
            s.NOMBRE as SEDE_NOMBRE,
            e.ID_ESTABLECIMIENTO,
            e.ID_SEDE
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.CODIGO_EMPLEADO = ?
        AND e.ID_EMPRESA = ?
        AND e.ESTADO = 'A'
    ";

    $params = [$codigo, $empresaId];

    // Agregar filtros opcionales
    if ($sedeId) {
        $sql .= " AND e.ID_SEDE = ?";
        $params[] = $sedeId;
    }

    if ($establecimientoId) {
        $sql .= " AND e.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimientoId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($empleado) {
        echo json_encode([
            'success' => true,
            'empleado' => $empleado,
            'message' => 'Empleado encontrado'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Empleado no encontrado con el código especificado'
        ]);
    }

} catch (Exception $e) {
    error_log("Error en get-employee-by-code.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar empleado',
        'error' => $e->getMessage()
    ]);
}
?>
