<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$stmt = $conn->query("SELECT ID_DIA, NOMBRE FROM DIA_SEMANA ORDER BY ID_DIA");
$dias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true, 'dias'=>$dias]);