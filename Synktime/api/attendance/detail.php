<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['success'=>false]);
    exit;
}

$stmt = $conn->prepare("SELECT a.ID_ASISTENCIA, a.FECHA, a.HORA, a.TARDANZA, a.OBSERVACION, a.FOTO,
    e.ID_EMPLEADO as codigo, e.NOMBRE, e.APELLIDO, est.NOMBRE as establecimiento, s.NOMBRE as sede,
    COALESCE(ehp.HORA_ENTRADA, h.HORA_ENTRADA) as HORA_ENTRADA, 
    COALESCE(ehp.TOLERANCIA, h.TOLERANCIA) as TOLERANCIA,
    COALESCE(ehp.NOMBRE_TURNO, h.NOMBRE) as HORARIO_NOMBRE,
    CASE WHEN ehp.ID_EMPLEADO_HORARIO IS NOT NULL THEN 'personalizado' ELSE 'tradicional' END as tipo_horario
FROM asistencia a
JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN sede s ON est.ID_SEDE = s.ID_SEDE
LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO 
    AND ehp.ID_EMPLEADO_HORARIO = a.ID_HORARIO
    AND ehp.ACTIVO = 'S'
LEFT JOIN empleado_horario eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO 
    AND eh.FECHA_DESDE <= a.FECHA AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= a.FECHA)
    AND eh.ID_HORARIO = a.ID_HORARIO
LEFT JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
WHERE a.ID_ASISTENCIA = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo json_encode(['success'=>false]); exit; }

$estado = 'Puntual';
if ($row['HORA_ENTRADA']) {
    $hora_entrada = $row['HORA_ENTRADA'];
    $tolerancia = (int)($row['TOLERANCIA'] ?? 0);
    $hora_real = $row['HORA'];

    $entrada_min = strtotime($row['FECHA'] . ' ' . $hora_entrada);
    $real_min = strtotime($row['FECHA'] . ' ' . $hora_real);

    if ($real_min < $entrada_min) {
        $estado = 'Temprano';
    } elseif ($real_min <= $entrada_min + $tolerancia * 60) {
        $estado = 'Puntual';
    } else {
        $estado = 'Tardanza';
    }
}

$foto_url = null;
if (!empty($row['FOTO'])) {
    $foto_path = __DIR__ . '/../../uploads/' . $row['FOTO'];
    if (file_exists($foto_path)) {
        $foto_url = 'uploads/' . $row['FOTO'];
    }
}

echo json_encode(['success'=>true, 'data'=>[
    'codigo' => $row['codigo'],
    'nombre' => $row['NOMBRE'].' '.$row['APELLIDO'],
    'sede' => $row['sede'],
    'establecimiento' => $row['establecimiento'],
    'fecha' => $row['FECHA'],
    'hora_entrada' => $row['HORA'],
    'estado_entrada' => $estado,
    'observacion' => $row['OBSERVACION'],
    'foto' => $foto_url
]]);