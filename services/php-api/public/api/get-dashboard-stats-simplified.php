<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/timezone.php';
require_once __DIR__ . '/../dashboard-controller.php';
require_once __DIR__ . '/../dashboard-controller-simplified.php';

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
$fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) ? $_GET['fecha'] : getBogotaDate();

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
    // **USAR FUNCIONES SIMPLIFICADAS**
    $estadisticas = getEstadisticasAsistenciaSimplified($nivel, $id, $fecha);

    // Gráficos simplificados por nivel
    if ($nivel === 'empresa') {
        $asistenciasPorHora = getAsistenciasPorHoraSimplified($id, $fecha);
        $distribucionAsistencias = getDistribucionAsistenciasSimplified($id, $fecha);
        // Mantener funciones existentes para actividad reciente (no necesita adaptación)
        $actividadReciente = getActividadReciente($id, $fecha);
    } elseif ($nivel === 'sede') {
        // Para sede y establecimiento, usar funciones simplificadas
        $asistenciasPorHora = getAsistenciasPorHoraSedeSimplified($id, $fecha);
        $distribucionAsistencias = getDistribucionAsistenciasSedeSimplified($id, $fecha);
        $actividadReciente = getActividadRecienteSedeSimplified($id, $fecha);
    } else { // establecimiento
        $asistenciasPorHora = getAsistenciasPorHoraEstablecimientoSimplified($id, $fecha);
        $distribucionAsistencias = getDistribucionAsistenciasEstablecimientoSimplified($id, $fecha);
        $actividadReciente = getActividadRecienteEstablecimientoSimplified($id, $fecha);
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
    error_log("Error en get-dashboard-stats-simplified.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>