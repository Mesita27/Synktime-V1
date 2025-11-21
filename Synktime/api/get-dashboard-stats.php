<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../dashboard-controller.php';

session_start();
$empresaId = $_SESSION['id_empresa'] ?? null;

if (!$empresaId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

// Parámetros para los niveles
$establecimientoId = !empty($_GET['establecimiento_id']) ? intval($_GET['establecimiento_id']) : null;
$sedeId = !empty($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
$fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Lógica jerárquica: establecimiento > sede > empresa
if ($establecimientoId) {
    $nivel = 'establecimiento';
    $id = $establecimientoId;
} elseif ($sedeId) {
    $nivel = 'sede';
    $id = $sedeId;
} else {
    $nivel = 'empresa';
    $id = $empresaId;
}

try {
    // Estadísticas generales
    $estadisticas = getEstadisticasAsistencia($nivel, $id, $fecha);

    // Gráficos por nivel
    if ($nivel === 'empresa') {
        $asistenciasPorHora = getAsistenciasPorHora($id, $fecha);
        $distribucionAsistencias = getDistribucionAsistencias($id, $fecha);
        $actividadReciente = getActividadReciente($id, $fecha);
    } elseif ($nivel === 'sede') {
        $asistenciasPorHora = getAsistenciasPorHoraSede($id, $fecha);
        $distribucionAsistencias = getDistribucionAsistenciasSede($id, $fecha);
        $actividadReciente = getActividadRecienteSede($id, $fecha);
    } else { // establecimiento
        $asistenciasPorHora = getAsistenciasPorHoraEstablecimiento($id, $fecha);
        $distribucionAsistencias = getDistribucionAsistenciasEstablecimiento($id, $fecha);
        $actividadReciente = getActividadRecienteEstablecimiento($id, $fecha);
    }

    echo json_encode([
        'success' => true,
        'nivel' => $nivel,
        'id' => $id,
        'fecha' => $fecha,
        'estadisticas' => $estadisticas,
        'asistenciasPorHora' => $asistenciasPorHora,
        'distribucionAsistencias' => $distribucionAsistencias,
        'actividadReciente' => $actividadReciente
    ]);
} catch (Exception $e) {
    error_log("Error en get-dashboard-stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}