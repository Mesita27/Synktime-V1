<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

$empresaId = $_SESSION['id_empresa'] ?? null;
if (!$empresaId) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$data = $_POST;
if (empty($data['nombre']) || empty($data['apellido']) || empty($data['identificacion'])) {
    echo json_encode(['success' => false, 'error' => 'Campos requeridos']);
    exit;
}
// Si hay ID, es edición
if (!empty($data['id'])) {
    $sql = "UPDATE EMPLEADO SET NOMBRE=:nombre, APELLIDO=:apellido, DNI=:dni, 
            CORREO=:correo, TELEFONO=:telefono, FECHA_INGRESO=:fecha, ESTADO=:estado
            WHERE ID_EMPLEADO=:id";
    $stmt = $conn->prepare($sql);
    $ok = $stmt->execute([
        ':nombre' => $data['nombre'],
        ':apellido' => $data['apellido'],
        ':dni' => $data['identificacion'],
        ':correo' => $data['email'] ?? null,
        ':telefono' => $data['telefono'] ?? null,
        ':fecha' => $data['fecha_contratacion'] ?? null,
        ':estado' => $data['estado'] ?? 'A',
        ':id' => $data['id']
    ]);
} else {
    $sql = "INSERT INTO EMPLEADO (NOMBRE, APELLIDO, DNI, CORREO, TELEFONO, FECHA_INGRESO, ESTADO, ACTIVO, ID_ESTABLECIMIENTO)
            VALUES (:nombre, :apellido, :dni, :correo, :telefono, :fecha, :estado, 'S', :id_establecimiento)";
    $stmt = $conn->prepare($sql);
    $ok = $stmt->execute([
        ':nombre' => $data['nombre'],
        ':apellido' => $data['apellido'],
        ':dni' => $data['identificacion'],
        ':correo' => $data['email'] ?? null,
        ':telefono' => $data['telefono'] ?? null,
        ':fecha' => $data['fecha_contratacion'] ?? null,
        ':estado' => $data['estado'] ?? 'A',
        ':id_establecimiento' => $data['id_establecimiento'] ?? null
    ]);
}
echo json_encode(['success' => $ok]);