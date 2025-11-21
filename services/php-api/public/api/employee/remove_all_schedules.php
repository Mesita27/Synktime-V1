<?php
require_once '../../auth/session.php';
requireAuth();
require_once '../../config/database.php';

$id_empleado = $_POST['id_empleado'] ?? '';
if (!$id_empleado) {
    echo json_encode(['success'=>false]);
    exit;
}
$stmt = $conn->prepare("DELETE FROM EMPLEADO_HORARIO WHERE ID_EMPLEADO=?");
$stmt->execute([$id_empleado]);
echo json_encode(['success'=>true]);
