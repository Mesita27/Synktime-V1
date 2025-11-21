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

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log("Error en API de sedes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function handleGet($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            0 as total_empleados
        FROM sedes s
        ORDER BY s.nombre ASC
    ");
    
    $stmt->execute();
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sedes' => $sedes
    ]);
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // Validar datos requeridos
    if (empty($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        return;
    }
    
    // Verificar si ya existe una sede con el mismo nombre
    $stmt = $pdo->prepare("SELECT id FROM sedes WHERE nombre = ? AND id != ?");
    $stmt->execute([$input['nombre'], $input['id'] ?? 0]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe una sede con este nombre']);
        return;
    }
    
    // Insertar nueva sede
    $stmt = $pdo->prepare("
        INSERT INTO sedes (nombre, direccion, telefono, email, estado, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'ACTIVO', NOW(), NOW())
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['direccion'] ?? null,
        $input['telefono'] ?? null,
        $input['email'] ?? null
    ]);
    
    $sedeId = $pdo->lastInsertId();
    
    // Obtener la sede creada
    $stmt = $pdo->prepare("SELECT * FROM sedes WHERE id = ?");
    $stmt->execute([$sedeId]);
    $sede = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sede creada exitosamente',
        'sede' => $sede
    ]);
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de sede requerido']);
        return;
    }
    
    // Validar datos requeridos
    if (empty($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        return;
    }
    
    // Verificar si ya existe una sede con el mismo nombre (excluyendo la actual)
    $stmt = $pdo->prepare("SELECT id FROM sedes WHERE nombre = ? AND id != ?");
    $stmt->execute([$input['nombre'], $input['id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe una sede con este nombre']);
        return;
    }
    
    // Actualizar sede
    $stmt = $pdo->prepare("
        UPDATE sedes 
        SET nombre = ?, direccion = ?, telefono = ?, email = ?, estado = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['direccion'] ?? null,
        $input['telefono'] ?? null,
        $input['email'] ?? null,
        $input['estado'] ?? 'ACTIVO',
        $input['id']
    ]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sede no encontrada']);
        return;
    }
    
    // Obtener la sede actualizada
    $stmt = $pdo->prepare("SELECT * FROM sedes WHERE id = ?");
    $stmt->execute([$input['id']]);
    $sede = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sede actualizada exitosamente',
        'sede' => $sede
    ]);
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        // Intentar obtener ID de query params
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de sede requerido']);
            return;
        }
    } else {
        $id = $input['id'];
    }
    
    // Verificar si la sede tiene empleados asociados
    $stmt = $pdo->prepare("SELECT 1 as total FROM sedes WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Sede no encontrada'
        ]);
        return;
    }
    
    // Cambiar estado a INACTIVO en lugar de eliminar físicamente
    $stmt = $pdo->prepare("UPDATE sedes SET estado = 'INACTIVO', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sede no encontrada']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sede eliminada exitosamente'
    ]);
}
?>
