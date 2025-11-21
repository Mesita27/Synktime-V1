<?php
/**
 * Refactored Attendance Details Endpoint
 * 
 * Example endpoint using Repository and Service pattern
 * Maintains identical JSON output to legacy endpoint
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Load bootstrap (includes autoloader and config)
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Load database connection
require_once SRC_PATH . '/Config/database.php';

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Repository\AsistenciaRepository;
use App\Service\AsistenciaService;

try {
    // Get request parameters
    $tipo = $_GET['tipo'] ?? null;
    $establecimientoId = !empty($_GET['establecimiento_id']) ? intval($_GET['establecimiento_id']) : null;
    $sedeId = !empty($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
    $empresaId = $_SESSION['id_empresa'] ?? 1;
    
    // Validate and parse date
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $fecha = date('Y-m-d');
    }

    // Initialize repository and service
    $repository = new AsistenciaRepository($conn);
    $service = new AsistenciaService($repository, $conn);

    // Get attendance details
    $result = $service->getAttendanceDetails(
        $tipo,
        $fecha,
        $establecimientoId,
        $sedeId,
        $empresaId
    );

    // Return JSON response
    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error in api/attendance/details-refactored.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ], JSON_UNESCAPED_UNICODE);
}
