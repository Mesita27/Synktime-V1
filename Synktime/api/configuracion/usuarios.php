<?php
require_once '../../auth/session.php';
require_once '../../config/database.php';

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['rol'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo los administradores pueden gestionar usuarios']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $currentUser);
            break;
        case 'POST':
            handlePost($pdo, $currentUser);
            break;
        case 'PUT':
            handlePut($pdo, $currentUser);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log("Error en API de usuarios: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function handleGet($pdo, $currentUser) {
    // Verificar si se solicita un usuario específico
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Obtener usuario específico
        $stmt = $pdo->prepare("
            SELECT 
                u.ID_USUARIO as id,
                u.USERNAME as username,
                u.NOMBRE_COMPLETO as nombre,
                u.EMAIL as email,
                u.ROL as rol,
                u.ESTADO as estado,
                CASE 
                    WHEN u.ESTADO = 'A' THEN 'ACTIVO'
                    ELSE 'INACTIVO'
                END as estado_texto,
                e.NOMBRE as empresa_nombre
            FROM usuario u
            JOIN empresa e ON u.ID_EMPRESA = e.ID_EMPRESA
            WHERE u.ID_USUARIO = ? AND u.ID_EMPRESA = ?
        ");
        
        $stmt->execute([$id, $currentUser['id_empresa']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            // Formatear datos para el frontend
            $usuario['estado'] = $usuario['estado_texto'];
            unset($usuario['estado_texto']);
            
            echo json_encode([
                'success' => true,
                'usuario' => $usuario
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no encontrado o no pertenece a su empresa'
            ]);
        }
        return;
    }
    
    // Obtener todos los usuarios activos solamente
    $stmt = $pdo->prepare("
        SELECT 
            u.ID_USUARIO as id,
            u.USERNAME as username,
            u.NOMBRE_COMPLETO as nombre,
            u.EMAIL as email,
            u.ROL as rol,
            u.ESTADO as estado,
            CASE 
                WHEN u.ESTADO = 'A' THEN 'ACTIVO'
                ELSE 'INACTIVO'
            END as estado_texto,
            e.NOMBRE as empresa_nombre,
            DATE_FORMAT(NOW(), '%Y-%m-%d') as created_at
        FROM usuario u
        JOIN empresa e ON u.ID_EMPRESA = e.ID_EMPRESA
        WHERE u.ID_EMPRESA = ? 
        AND u.ID_USUARIO != ?
        AND u.ESTADO = 'A'
        ORDER BY u.NOMBRE_COMPLETO ASC
    ");
    
    $stmt->execute([$currentUser['id_empresa'], $currentUser['id']]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para el frontend
    foreach ($usuarios as &$usuario) {
        $usuario['estado'] = $usuario['estado_texto'];
        unset($usuario['estado_texto']);
    }
    
    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios
    ]);
}

function handlePost($pdo, $currentUser) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos JSON requeridos']);
        return;
    }
    
    // Validar límite de 15 usuarios por empresa
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuario WHERE ID_EMPRESA = ?");
    $stmt->execute([$currentUser['id_empresa']]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count >= 15) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Límite máximo de 15 usuarios por empresa alcanzado']);
        return;
    }
    
    // Validar datos requeridos
    $requiredFields = ['nombre', 'email', 'username', 'rol', 'contrasena'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "El campo {$field} es requerido"]);
            return;
        }
    }
    
    // Validar rol permitido
    if (!in_array($input['rol'], ['GERENTE', 'ASISTENCIA'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rol no válido. Solo se permiten GERENTE y ASISTENCIA']);
        return;
    }
    
    // Validar longitud de contraseña
    if (strlen($input['contrasena']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
        return;
    }
    
    // Validar formato de email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato de email inválido']);
        return;
    }
    
    // Validar username único
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE USERNAME = ?");
    $stmt->execute([$input['username']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe']);
        return;
    }
    
    // Validar email único
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE EMAIL = ?");
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
        return;
    }
    
    // Hashear contraseña
    $hashedPassword = password_hash($input['contrasena'], PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $pdo->prepare("
        INSERT INTO usuario (USERNAME, CONTRASENA, NOMBRE_COMPLETO, EMAIL, ROL, ID_EMPRESA, ESTADO) 
        VALUES (?, ?, ?, ?, ?, ?, 'A')
    ");
    
    $stmt->execute([
        $input['username'],
        $hashedPassword,
        $input['nombre'],
        $input['email'],
        $input['rol'],
        $currentUser['id_empresa']
    ]);
    
    $usuarioId = $pdo->lastInsertId();
    
    // Obtener el usuario creado
    $stmt = $pdo->prepare("
        SELECT 
            u.ID_USUARIO as id,
            u.USERNAME as username,
            u.NOMBRE_COMPLETO as nombre,
            u.EMAIL as email,
            u.ROL as rol,
            CASE 
                WHEN u.ESTADO = 'A' THEN 'ACTIVO'
                ELSE 'INACTIVO'
            END as estado,
            e.NOMBRE as empresa_nombre
        FROM usuario u
        JOIN empresa e ON u.ID_EMPRESA = e.ID_EMPRESA
        WHERE u.ID_USUARIO = ?
    ");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado exitosamente',
        'usuario' => $usuario
    ]);
}

function handlePut($pdo, $currentUser) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    // Verificar que el usuario pertenece a la misma empresa y no es el admin actual
    $stmt = $pdo->prepare("
        SELECT ID_USUARIO, ROL 
        FROM usuario 
        WHERE ID_USUARIO = ? AND ID_EMPRESA = ? AND ID_USUARIO != ?
    ");
    $stmt->execute([$input['id'], $currentUser['id_empresa'], $currentUser['id']]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o no tienes permisos para editarlo']);
        return;
    }
    
    // Validar datos requeridos
    $requiredFields = ['nombre', 'email', 'username', 'rol'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "El campo {$field} es requerido"]);
            return;
        }
    }
    
    // Validar rol permitido
    if (!in_array($input['rol'], ['GERENTE', 'ASISTENCIA'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rol no válido. Solo se permiten GERENTE y ASISTENCIA']);
        return;
    }
    
    // Validar formato de email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato de email inválido']);
        return;
    }
    
    // Validar username único (excluyendo el usuario actual)
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE USERNAME = ? AND ID_USUARIO != ?");
    $stmt->execute([$input['username'], $input['id']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe']);
        return;
    }
    
    // Validar email único (excluyendo el usuario actual)
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE EMAIL = ? AND ID_USUARIO != ?");
    $stmt->execute([$input['email'], $input['id']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
        return;
    }
    
    // Preparar actualización
    $updateFields = [
        'USERNAME = ?',
        'NOMBRE_COMPLETO = ?',
        'EMAIL = ?',
        'ROL = ?',
        'ESTADO = ?'
    ];
    
    $updateValues = [
        $input['username'],
        $input['nombre'],
        $input['email'],
        $input['rol'],
        $input['estado'] === 'ACTIVO' ? 'A' : 'I'
    ];
    
    // Si se proporcionó contraseña nueva, incluirla
    if (!empty($input['contrasena'])) {
        if (strlen($input['contrasena']) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            return;
        }
        
        $updateFields[] = 'CONTRASENA = ?';
        $updateValues[] = password_hash($input['contrasena'], PASSWORD_DEFAULT);
    }
    
    // Agregar condición WHERE
    $updateValues[] = $input['id'];
    $updateValues[] = $currentUser['id_empresa'];
    
    // Ejecutar actualización
    $sql = "UPDATE usuario SET " . implode(', ', $updateFields) . " WHERE ID_USUARIO = ? AND ID_EMPRESA = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateValues);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o sin cambios']);
        return;
    }
    
    // Obtener el usuario actualizado
    $stmt = $pdo->prepare("
        SELECT 
            u.ID_USUARIO as id,
            u.USERNAME as username,
            u.NOMBRE_COMPLETO as nombre,
            u.EMAIL as email,
            u.ROL as rol,
            CASE 
                WHEN u.ESTADO = 'A' THEN 'ACTIVO'
                ELSE 'INACTIVO'
            END as estado,
            e.NOMBRE as empresa_nombre
        FROM usuario u
        JOIN empresa e ON u.ID_EMPRESA = e.ID_EMPRESA
        WHERE u.ID_USUARIO = ?
    ");
    $stmt->execute([$input['id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario actualizado exitosamente',
        'usuario' => $usuario
    ]);
}
?>
