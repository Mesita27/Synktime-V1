<?php
/**
 * API: Approve or reject overtime hours
 * Only accessible by ADMIN users
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../auth/session.php';

// Check if user is authenticated and has ADMIN role
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol ADMIN']);
    exit;
}

try {
    global $conn;

    // Get JSON POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $idsHorasExtras = isset($input['ids']) ? $input['ids'] : [];
    $accion = isset($input['accion']) ? $input['accion'] : null; // 'aprobar' or 'rechazar'
    $observaciones = isset($input['observaciones']) ? trim($input['observaciones']) : null;

    // Validate input
    if (empty($idsHorasExtras) || !is_array($idsHorasExtras)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'IDs de horas extras requeridos']);
        exit;
    }

    if (!in_array($accion, ['aprobar', 'rechazar'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción inválida. Use "aprobar" o "rechazar"']);
        exit;
    }

    $estadoAprobacion = ($accion === 'aprobar') ? 'aprobada' : 'rechazada';
    $idUsuarioAprobacion = $_SESSION['user_id'];
    $fechaAprobacion = date('Y-m-d H:i:s');

    // Begin transaction
    $conn->beginTransaction();

    // Update the overtime records
    $placeholders = str_repeat('?,', count($idsHorasExtras) - 1) . '?';
    $query = "
        UPDATE horas_extras_aprobacion
        SET
            ESTADO_APROBACION = ?,
            ID_USUARIO_APROBACION = ?,
            FECHA_APROBACION = ?,
            OBSERVACIONES = ?
        WHERE ID_HORAS_EXTRAS IN ($placeholders)
        AND ESTADO_APROBACION = 'pendiente'
    ";

    $params = [
        $estadoAprobacion,
        $idUsuarioAprobacion,
        $fechaAprobacion,
        $observaciones
    ];

    $params = array_merge($params, $idsHorasExtras);

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $affectedRows = $stmt->rowCount();

    // Commit transaction
    $conn->commit();

    // Log the action
    $detalle = "Horas extras $accion" . "das por usuario " . $_SESSION['username'] . " - IDs: " . implode(',', $idsHorasExtras);
    $logStmt = $conn->prepare("INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, 'APROBACION_HORAS_EXTRAS', ?)");
    $logStmt->execute([$idUsuarioAprobacion, $detalle]);

    echo json_encode([
        'success' => true,
        'message' => "$affectedRows horas extras han sido $estadoAprobacion" . "das exitosamente",
        'affected_rows' => $affectedRows,
        'accion' => $accion
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Error in aprobar-horas-extras.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>