<?php
/**
 * Herramienta de auto-diagnóstico para el sistema biométrico
 * Esta herramienta realiza múltiples pruebas para identificar problemas
 * en la configuración y funcionamiento del sistema biométrico
 */

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

$testsMap = [
    'database' => 'testDatabase',
    'tables' => 'testTables',
    'apis' => 'testAPIs',
];

$requestedTest = $_GET['test'] ?? ($_POST['test'] ?? 'general');

try {
    $results = [];

    if ($requestedTest === 'general') {
        foreach ($testsMap as $name => $callable) {
            if (function_exists($callable)) {
                $results[$name] = $callable();
            }
        }
    } elseif (isset($testsMap[$requestedTest]) && function_exists($testsMap[$requestedTest])) {
        $results[$requestedTest] = $testsMap[$requestedTest]();
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Prueba no soportada'
        ]);
        return;
    }

    $isSuccessful = !array_filter($results, function ($result) {
        return isset($result['success']) && $result['success'] === false;
    });

    echo json_encode([
        'success' => $isSuccessful,
        'timestamp' => date('c'),
        'tests' => $results
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error ejecutando diagnóstico',
        'error' => $e->getMessage()
    ]);
}
