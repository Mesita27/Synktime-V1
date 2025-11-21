<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

$id_empleado = $_POST['id_empleado'] ?? '';
if (!$id_empleado) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM EMPLEADO WHERE ID_EMPLEADO = ?");
try {
    $stmt->execute([$id_empleado]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
}