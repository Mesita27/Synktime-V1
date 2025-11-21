<?php
// Script de diagnóstico para registro biométrico
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Capturar errores para debug
ob_start();

try {
    require_once '../config/database.php';
    require_once '../auth/session.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'Diagnóstico de APIs',
        'tests' => [
            'database_connection' => $conn ? 'OK' : 'FAILED',
            'session_auth' => function_exists('requireAuth') ? 'OK' : 'FAILED',
            'employee_100_exists' => 'TESTING...'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

$output = ob_get_clean();
if (!empty($output)) {
    error_log("Output captured: " . $output);
}
?>