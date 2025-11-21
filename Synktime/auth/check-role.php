<?php
/**
 * API: Check if user has specific role
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session.php';

// Check if user is authenticated
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'has_permission' => false, 'message' => 'No autenticado']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$requiredRole = isset($input['required_role']) ? $input['required_role'] : null;

if (!$requiredRole) {
    echo json_encode(['success' => false, 'has_permission' => false, 'message' => 'Rol requerido no especificado']);
    exit;
}

$userRole = isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
$hasPermission = ($userRole === $requiredRole);

echo json_encode([
    'success' => true,
    'has_permission' => $hasPermission,
    'user_role' => $userRole,
    'required_role' => $requiredRole
]);
?>