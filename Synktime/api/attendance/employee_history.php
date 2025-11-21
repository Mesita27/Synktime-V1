<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$empresaId = $_SESSION['id_empresa'];
$userRole = $_SESSION['rol'] ?? '';
$id_empleado = $_GET['id_empleado'] ?? '';
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

if (!$id_empleado) {
    echo json_encode(['success'=>false,'data'=>[]]); exit;
}

// Para rol ASISTENCIA, restringir a solo día actual
if ($userRole === 'ASISTENCIA') {
    $desde = date('Y-m-d');
    $hasta = date('Y-m-d');
}

$sql = "SELECT a.ID_ASISTENCIA, a.FECHA, a.HORA, a.TARDANZA, a.OBSERVACION
FROM asistencia a
JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN sede s ON est.ID_SEDE = s.ID_SEDE
WHERE s.ID_EMPRESA = ? AND a.TIPO='ENTRADA' AND e.ID_EMPLEADO=?";
$params = [$empresaId, $id_empleado];

if ($desde && $hasta) {
    $sql .= " AND a.FECHA BETWEEN ? AND ?";
    $params[] = $desde;
    $params[] = $hasta;
}
$sql .= " ORDER BY a.FECHA DESC, a.HORA ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$data = [];
$lateCount = 0;
$total = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $estado = ($row['TARDANZA'] === 'S') ? 'Tardanza' : (($row['HORA'] < '08:00') ? 'Temprano' : 'Puntual');
    if ($estado === 'Tardanza') $lateCount++;
    $total++;
    $data[] = [
        'id' => $row['ID_ASISTENCIA'],
        'fecha' => $row['FECHA'],
        'hora_entrada' => $row['HORA'],
        'estado_entrada' => $estado,
        'observacion' => $row['OBSERVACION']
    ];
}
$percentLate = $total ? round($lateCount * 100 / $total, 2) : 0;
echo json_encode(['success'=>true, 'data'=>$data, 'percentLate'=>$percentLate]);