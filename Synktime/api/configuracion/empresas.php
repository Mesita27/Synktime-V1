<?php
require_once '../../auth/session.php';
require_once '../../config/database.php';

// Verificar autenticación y permisos
if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['rol'], ['GERENTE', 'ADMIN'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permisos insuficientes']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener todas las empresas activas
    $stmt = $pdo->prepare("
        SELECT ID_EMPRESA, NOMBRE, RUC, DIRECCION, ESTADO
        FROM empresa 
        WHERE ESTADO = 'A'
        ORDER BY NOMBRE
    ");
    
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $empresas = [];
    foreach ($result as $row) {
        $empresas[] = [
            'id' => (int)$row['ID_EMPRESA'],
            'nombre' => $row['NOMBRE'],
            'ruc' => $row['RUC'],
            'direccion' => $row['DIRECCION'],
            'estado' => $row['ESTADO']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $empresas]);
    
} catch (Exception $e) {
    error_log("Error en API de empresas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener empresas']);
}
?>