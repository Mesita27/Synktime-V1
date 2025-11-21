<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/diagnostic-fixed.php';

$tests = [
    'database' => 'testDatabase',
    'tables' => 'testTables',
    'apis' => 'testAPIs',
];

$requested = $_GET['test'] ?? ($_POST['test'] ?? 'general');

try {
    $results = [];

    if ($requested === 'general') {
        foreach ($tests as $name => $function) {
            if (function_exists($function)) {
                $results[$name] = $function();
            }
        }
    } elseif (isset($tests[$requested]) && function_exists($tests[$requested])) {
        $results[$requested] = $tests[$requested]();
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Prueba no soportada'
        ]);
        return;
    }

    $success = !array_filter($results, function ($result) {
        return isset($result['success']) && $result['success'] === false;
    });

    echo json_encode([
        'success' => $success,
        'tests' => $results,
        'timestamp' => date('c')
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error ejecutando diagnóstico',
        'error' => $e->getMessage()
    ]);
}
