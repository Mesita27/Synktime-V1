<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/holidays-helper.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$empresaId = $_SESSION['id_empresa'];

try {
    $fecha = $_POST['fecha'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Validaciones
    if (!$fecha) {
        throw new Exception('La fecha es requerida');
    }
    
    if (!$nombre) {
        throw new Exception('El nombre del día cívico es requerido');
    }
    
    if (strlen($nombre) > 100) {
        throw new Exception('El nombre no puede exceder 100 caracteres');
    }
    
    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new Exception('Formato de fecha inválido');
    }
    
    // Validar que la fecha no sea pasada
    if ($fecha < date('Y-m-d')) {
        throw new Exception('No se pueden registrar días cívicos en fechas pasadas');
    }
    
    // Usar el helper para registrar el día cívico
    $holidaysHelper = new HolidaysHelper();
    $result = $holidaysHelper->registerCivicDay($fecha, $nombre, $descripcion, $empresaId);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error en register-dia-civico.php: " . $e->getMessage());
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>