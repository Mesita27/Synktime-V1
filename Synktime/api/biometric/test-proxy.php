<?php
// Simple test proxy without authentication
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Type: application/json");

$methodHeader = $_SERVER["HTTP_X_SYNKTIME_PROXY_METHOD"] ?? null;
$pathHeader = $_SERVER["HTTP_X_SYNKTIME_PROXY_PATH"] ?? null;

if ($pathHeader === null || trim($pathHeader) === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "X-Synktime-Proxy-Path header required"
    ]);
    exit;
}

$targetPath = ltrim(trim($pathHeader), "/");

// Get base URL from environment
$baseUrl = getenv("PYTHON_SERVICE_INTERNAL_URL") ?: getenv("PY_SERVICE_URL") ?: "http://synktime-python:8000";
$baseUrl = rtrim($baseUrl, "/");
$targetUrl = $baseUrl . "/" . $targetPath;

$httpMethod = strtoupper($methodHeader ?? $_SERVER["REQUEST_METHOD"]);
$requestBody = file_get_contents("php://input");

// Test the connection
$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

if (!empty($requestBody) && !in_array($httpMethod, ["GET", "HEAD"], true)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
}

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    
    http_response_code(502);
    echo json_encode([
        "success" => false,
        "message" => "Connection failed",
        "error" => $error,
        "target_url" => $targetUrl
    ]);
    exit;
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$responseHeaderBlock = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

http_response_code($statusCode);
echo $responseBody;
?>
