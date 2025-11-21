<?php
// Habilitar error reporting para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

// Log inicial
error_log("Change password API called - Method: " . $_SERVER['REQUEST_METHOD']);

// Verificar autenticación
if (!isAuthenticated()) {
    error_log("Change password API - User not authenticated. Session data: " . json_encode($_SESSION ?? []));
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'No has iniciado sesión. Por favor, inicia sesión para cambiar tu contraseña.',
        'error_code' => 'NOT_AUTHENTICATED'
    ]);
    exit;
}

header('Content-Type: application/json');

// Solo permitir POST para cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Log datos recibidos
    error_log("Change password API - POST data: " . json_encode($_POST));
    
    // Obtener datos del formulario
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    error_log("Change password API - Passwords: current=" . (!empty($currentPassword) ? 'SET' : 'EMPTY') . 
              ", new=" . (!empty($newPassword) ? 'SET' : 'EMPTY') . 
              ", confirm=" . (!empty($confirmPassword) ? 'SET' : 'EMPTY'));
    
    // Validar datos requeridos
    if (empty($currentPassword)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es requerida']);
        exit;
    }
    
    if (empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña es requerida']);
        exit;
    }
    
    if (empty($confirmPassword)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La confirmación de contraseña es requerida']);
        exit;
    }
    
    // Validar que las contraseñas coincidan
    if ($newPassword !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit;
    }
    
    // Validar longitud de la nueva contraseña
    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres']);
        exit;
    }
    
    // Verificar que la nueva contraseña sea diferente a la actual
    if ($currentPassword === $newPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe ser diferente a la actual']);
        exit;
    }
    
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    error_log("Change password API - Current user: " . json_encode($currentUser));
    
    if (!$currentUser || !isset($currentUser['id'])) {
        error_log("Change password API - No current user found");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Usuario no válido en sesión']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT ID_USUARIO as id, USERNAME as usuario, CONTRASENA as password FROM usuario WHERE ID_USUARIO = ?");
    $stmt->execute([$currentUser['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Change password API - User from DB: " . json_encode($user ? ['id' => $user['id'], 'usuario' => $user['usuario']] : 'NOT_FOUND'));
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Verificar la contraseña actual
    if (!password_verify($currentPassword, $user['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        exit;
    }
    
    // Verificar si la nueva contraseña ya fue utilizada recientemente (opcional)
    $stmt = $pdo->prepare("
        SELECT PASSWORD_HASH as password_hash 
        FROM password_history 
        WHERE ID_USUARIO = ? 
        ORDER BY CHANGED_AT DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $passwordHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Verificar contra las últimas 5 contraseñas
    foreach ($passwordHistory as $oldHash) {
        if (password_verify($newPassword, $oldHash)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No puedes reutilizar una de tus últimas 5 contraseñas']);
            exit;
        }
    }
    
    $pdo->beginTransaction();
    
    try {
        // Hashear la nueva contraseña
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Actualizar la contraseña del usuario
        $stmt = $pdo->prepare("
            UPDATE usuario 
            SET CONTRASENA = ? 
            WHERE ID_USUARIO = ?
        ");
        $stmt->execute([$newPasswordHash, $user['id']]);
        
        // Guardar la contraseña anterior en el historial
        $stmt = $pdo->prepare("
            INSERT INTO password_history (ID_USUARIO, PASSWORD_HASH, CHANGED_AT) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$user['id'], $user['password']]);
        
        // Limpiar historial antiguo (mantener solo las últimas 10)
        $stmt = $pdo->prepare("
            DELETE FROM password_history 
            WHERE ID_USUARIO = ? 
            AND ID_HISTORY NOT IN (
                SELECT ID_HISTORY FROM (
                    SELECT ID_HISTORY FROM password_history 
                    WHERE ID_USUARIO = ? 
                    ORDER BY CHANGED_AT DESC 
                    LIMIT 10
                ) as recent
            )
        ");
        $stmt->execute([$user['id'], $user['id']]);
        
        $pdo->commit();
        
        // Log de seguridad
        error_log("Password changed for user: " . $user['usuario'] . " (ID: " . $user['id'] . ") at " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña cambiada exitosamente'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error cambiando contraseña: " . $e->getMessage() . " - Stack: " . $e->getTraceAsString());
    
    // Proporcionar mensajes más específicos según el tipo de error
    $message = 'Error interno del servidor';
    
    if (strpos($e->getMessage(), 'session') !== false) {
        $message = 'Error de sesión. Por favor, inicia sesión nuevamente.';
        http_response_code(401);
    } elseif (strpos($e->getMessage(), 'password') !== false) {
        $message = 'Error al procesar la contraseña. Verifica los datos ingresados.';
        http_response_code(400);
    } else {
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $message,
        'error_code' => 'SERVER_ERROR'
    ]);
}
?>