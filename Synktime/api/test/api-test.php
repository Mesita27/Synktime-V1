<?php
// API de prueba para diagnóstico
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API de diagnóstico funcionando correctamente',
    'server_info' => [
        'time' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ],
    'test_data' => [
        'id' => 1,
        'name' => 'Test Employee',
        'active' => true
    ]
]);
?>
