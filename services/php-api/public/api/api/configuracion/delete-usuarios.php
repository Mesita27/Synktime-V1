<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['rol'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo los administradores pueden eliminar usuarios']);
    exit;
}

header('Content-Type: application/json');

// Solo aceptar método DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Solo se permite el método DELETE']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos JSON requeridos']);
        exit;
    }

    // Manejar eliminación individual
    if (isset($input['id'])) {
        $id = $input['id'];
        
        // Verificar que el usuario existe, pertenece a la empresa y no es el admin actual
        $stmt = $pdo->prepare("
            SELECT ID_USUARIO, USERNAME, NOMBRE_COMPLETO 
            FROM usuario 
            WHERE ID_USUARIO = ? AND ID_EMPRESA = ? AND ID_USUARIO != ?
        ");
        $stmt->execute([$id, $currentUser['id_empresa'], $currentUser['id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o no tienes permisos para eliminarlo']);
            exit;
        }
        
        // Inhabilitar el usuario (cambiar estado a INACTIVO)
        try {
            $stmt = $pdo->prepare("UPDATE usuario SET ESTADO = 'I' WHERE ID_USUARIO = ? AND ID_EMPRESA = ?");
            $stmt->execute([$id, $currentUser['id_empresa']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => "Usuario {$usuario['NOMBRE_COMPLETO']} eliminado exitosamente"
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo eliminar el usuario'
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error al inhabilitar usuario {$id}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el usuario'
            ]);
        }
        
    } elseif (isset($input['ids']) && is_array($input['ids'])) {
        // Manejar eliminación múltiple
        $ids = $input['ids'];
        
        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No se proporcionaron IDs para eliminar']);
            exit;
        }
        
        // Verificar que todos los usuarios pertenecen a la empresa y no incluyen al admin actual
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $params = array_merge($ids, [$currentUser['id_empresa'], $currentUser['id']]);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM usuario 
            WHERE ID_USUARIO IN ($placeholders) 
            AND ID_EMPRESA = ? 
            AND ID_USUARIO != ?
        ");
        $stmt->execute($params);
        $validCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($validCount !== count($ids)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Algunos usuarios no pueden ser eliminados']);
            exit;
        }
        
        // Inhabilitar múltiples usuarios
        try {
            $stmt = $pdo->prepare("
                UPDATE usuario 
                SET ESTADO = 'I' 
                WHERE ID_USUARIO IN ($placeholders) 
                AND ID_EMPRESA = ?
            ");
            $params = array_merge($ids, [$currentUser['id_empresa']]);
            $stmt->execute($params);
            
            $deletedCount = $stmt->rowCount();
            
            if ($deletedCount > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => "Se eliminaron {$deletedCount} usuario(s) exitosamente"
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudieron eliminar los usuarios seleccionados'
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error al inhabilitar usuarios " . implode(',', $ids) . ": " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar los usuarios seleccionados'
            ]);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID o IDs de usuario requeridos']);
    }

} catch (Exception $e) {
    error_log("Error en API de eliminación de usuarios: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>