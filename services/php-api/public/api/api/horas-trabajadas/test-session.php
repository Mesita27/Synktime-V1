<?php
session_start();

// Simple test endpoint
if (!isset($_SESSION['id_empresa'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No session']);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Session is valid',
    'empresa_id' => $_SESSION['id_empresa'],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>