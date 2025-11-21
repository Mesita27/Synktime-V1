<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

$id_empleado = $_POST['id_empleado'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$dni = trim($_POST['dni'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$id_establecimiento = $_POST['establecimiento'] ?? '';
$fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
$estado = $_POST['estado'] ?? '';
$activo = 'S';

if (!$id_empleado || !$nombre || !$apellido || !$dni || !$correo || !$id_establecimiento || !$fecha_ingreso || !$estado) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

// Validar id_empleado único
$stmt = $conn->prepare("SELECT 1 FROM EMPLEADO WHERE ID_EMPLEADO = ?");
$stmt->execute([$id_empleado]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Ya existe un empleado con ese código']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO EMPLEADO 
    (ID_EMPLEADO, NOMBRE, APELLIDO, DNI, CORREO, TELEFONO, ID_ESTABLECIMIENTO, FECHA_INGRESO, ESTADO, ACTIVO)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
try {
    $stmt->execute([
        $id_empleado,
        $nombre,
        $apellido,
        $dni,
        $correo,
        $telefono,
        $id_establecimiento,
        $fecha_ingreso,
        $estado,
        $activo
    ]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al insertar: ' . $e->getMessage()]);
}