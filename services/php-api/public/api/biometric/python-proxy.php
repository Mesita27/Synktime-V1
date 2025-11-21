<?php
require_once __DIR__ . '/../../auth/session.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$methodHeader = $_SERVER['HTTP_X_SYNKTIME_PROXY_METHOD'] ?? null;
$pathHeader = $_SERVER['HTTP_X_SYNKTIME_PROXY_PATH'] ?? null;

$rawBody = file_get_contents('php://input');
$parsedBody = null;

if ($rawBody !== false && $rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $parsedBody = $decoded;
    }
}

if (($methodHeader === null || $methodHeader === '') && isset($parsedBody['method'])) {
    $methodHeader = $parsedBody['method'];
}

if (($pathHeader === null || trim($pathHeader) === '') && isset($parsedBody['target'])) {
    $pathHeader = $parsedBody['target'];
}

$httpMethod = strtoupper($methodHeader ?? $_SERVER['REQUEST_METHOD']);

// Lista de endpoints que no requieren autenticación completa
$publicEndpoints = [
    'healthz',
    'health',
    'status',
    '/healthz',
    '/health',
    '/status'
];

$requiresAuth = true;

// Verificar si es un endpoint público
if ($httpMethod === 'GET' && $pathHeader !== null) {
    $cleanPath = trim($pathHeader);
    if (in_array($cleanPath, $publicEndpoints, true)) {
        $requiresAuth = false;
    }
}

// Solo requerir autenticación para endpoints no públicos
if ($requiresAuth) {
    requireAuth();
}

$allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
if (!in_array($httpMethod, $allowedMethods, true)) {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido para el proxy',
        'allowed_methods' => $allowedMethods
    ]);
    exit;
}

if ($pathHeader === null || trim($pathHeader) === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Cabecera X-Synktime-Proxy-Path requerida'
    ]);
    exit;
}

$targetPath = ltrim(trim($pathHeader), '/');

if ($targetPath === '' || preg_match('/^https?:\/\//i', $targetPath)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Ruta de destino inválida para el proxy'
    ]);
    exit;
}

$baseCandidates = [
    'PYTHON_SERVICE_INTERNAL_URL',
    'PYTHON_SERVICE_URL',
    'PY_SERVICE_INTERNAL_URL',
    'PY_SERVICE_URL'
];
$baseUrl = null;

foreach ($baseCandidates as $envKey) {
    $value = getenv($envKey);
    if ($value === false || $value === '') {
        if (isset($_ENV[$envKey]) && $_ENV[$envKey] !== '') {
            $value = $_ENV[$envKey];
        } elseif (isset($_SERVER[$envKey]) && $_SERVER[$envKey] !== '') {
            $value = $_SERVER[$envKey];
        }
    }

    if (!empty($value)) {
        $baseUrl = $value;
        break;
    }
}

if (empty($baseUrl)) {
    $baseUrl = 'http://127.0.0.1:8000';
}

$baseUrl = rtrim($baseUrl, '/');
$targetUrl = $baseUrl . '/' . $targetPath;

$requestBody = $rawBody !== false ? $rawBody : '';

$incomingHeaders = function_exists('getallheaders') ? getallheaders() : [];
$forwardHeaders = [];
$hasContentTypeHeader = false;

foreach ($incomingHeaders as $name => $value) {
    if (stripos($name, 'X-Synktime-Proxy-') === 0) {
        continue;
    }

    $lower = strtolower($name);
    if ($lower === 'host' || $lower === 'content-length') {
        continue;
    }

    if ($lower === 'content-type') {
        $hasContentTypeHeader = true;
    }

    $forwardHeaders[] = $name . ': ' . $value;
}

if (!$hasContentTypeHeader && !empty($requestBody) && in_array($httpMethod, ['POST', 'PUT', 'PATCH'], true)) {
    $forwardHeaders[] = 'Content-Type: application/json';
}

$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

if (!empty($forwardHeaders)) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
}

if ($requestBody !== false && $requestBody !== '' && !in_array($httpMethod, ['GET', 'HEAD'], true)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
}

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error comunicándose con el servicio biométrico',
        'detail' => $error
    ]);
    exit;
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$responseHeaderBlock = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

$allowedResponseHeaders = ['content-type', 'cache-control', 'pragma', 'expires'];
$headerLines = preg_split('/\r\n|\n|\r/', trim($responseHeaderBlock));

http_response_code($statusCode);

foreach ($headerLines as $line) {
    if (strpos($line, ':') === false) {
        continue;
    }

    [$name, $value] = array_map('trim', explode(':', $line, 2));
    if ($name === '' || $value === '') {
        continue;
    }

    if (in_array(strtolower($name), $allowedResponseHeaders, true)) {
        header($name . ': ' . $value, true);
    }
}

echo $responseBody;
