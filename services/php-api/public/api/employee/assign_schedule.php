<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$id_horario = $_POST['id_horario'] ?? '';
$ids_empleados = json_decode($_POST['ids_empleados'] ?? '[]', true);

if (!$id_horario || !is_array($ids_empleados) || !count($ids_empleados)) {
    echo json_encode(['success'=>false, 'message'=>'Parámetros inválidos']);
    exit;
}

try {
    $date = date('Y-m-d');
    $stmt = $conn->prepare("INSERT IGNORE INTO EMPLEADO_HORARIO (ID_EMPLEADO, ID_HORARIO, FECHA_DESDE) VALUES (?, ?, ?)");
    foreach ($ids_empleados as $id_emp) {
        $stmt->execute([$id_emp, $id_horario, $date]);
    }
    echo json_encode(['success'=>true]);
} catch(Exception $e) {
    echo json_encode(['success'=>false, 'message'=>'Error de servidor: '.$e->getMessage()]);
}
