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
    error_log("Error en API de establecimientos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function handleGet($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            s.nombre as sede_nombre,
            0 as total_empleados
        FROM establecimientos e
        LEFT JOIN sedes s ON e.sede_id = s.id
        ORDER BY s.nombre ASC, e.nombre ASC
    ");
    
    $stmt->execute();
    $establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'establecimientos' => $establecimientos
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
    
    if (empty($input['sede_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede es requerida']);
        return;
    }
    
    // Verificar que la sede existe y está activa
    $stmt = $pdo->prepare("SELECT id FROM sedes WHERE id = ? AND estado = 'ACTIVO'");
    $stmt->execute([$input['sede_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede seleccionada no existe o está inactiva']);
        return;
    }
    
    // Verificar si ya existe un establecimiento con el mismo nombre en la misma sede
    $stmt = $pdo->prepare("SELECT id FROM establecimientos WHERE nombre = ? AND sede_id = ?");
    $stmt->execute([$input['nombre'], $input['sede_id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe un establecimiento con este nombre en la sede seleccionada']);
        return;
    }
    
    // Insertar nuevo establecimiento
    $stmt = $pdo->prepare("
        INSERT INTO establecimientos (nombre, descripcion, sede_id, estado, created_at, updated_at) 
        VALUES (?, ?, ?, 'ACTIVO', NOW(), NOW())
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['descripcion'] ?? null,
        $input['sede_id']
    ]);
    
    $establecimientoId = $pdo->lastInsertId();
    
    // Obtener el establecimiento creado con datos de sede
    $stmt = $pdo->prepare("
        SELECT e.*, s.nombre as sede_nombre
        FROM establecimientos e
        LEFT JOIN sedes s ON e.sede_id = s.id
        WHERE e.id = ?
    ");
    $stmt->execute([$establecimientoId]);
    $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Establecimiento creado exitosamente',
        'establecimiento' => $establecimiento
    ]);
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de establecimiento requerido']);
        return;
    }
    
    // Validar datos requeridos
    if (empty($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        return;
    }
    
    if (empty($input['sede_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede es requerida']);
        return;
    }
    
    // Verificar que la sede existe y está activa
    $stmt = $pdo->prepare("SELECT id FROM sedes WHERE id = ? AND estado = 'ACTIVO'");
    $stmt->execute([$input['sede_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede seleccionada no existe o está inactiva']);
        return;
    }
    
    // Verificar si ya existe un establecimiento con el mismo nombre en la misma sede (excluyendo el actual)
    $stmt = $pdo->prepare("SELECT id FROM establecimientos WHERE nombre = ? AND sede_id = ? AND id != ?");
    $stmt->execute([$input['nombre'], $input['sede_id'], $input['id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe un establecimiento con este nombre en la sede seleccionada']);
        return;
    }
    
    // Actualizar establecimiento
    $stmt = $pdo->prepare("
        UPDATE establecimientos 
        SET nombre = ?, descripcion = ?, sede_id = ?, estado = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['descripcion'] ?? null,
        $input['sede_id'],
        $input['estado'] ?? 'ACTIVO',
        $input['id']
    ]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Establecimiento no encontrado']);
        return;
    }
    
    // Obtener el establecimiento actualizado con datos de sede
    $stmt = $pdo->prepare("
        SELECT e.*, s.nombre as sede_nombre
        FROM establecimientos e
        LEFT JOIN sedes s ON e.sede_id = s.id
        WHERE e.id = ?
    ");
    $stmt->execute([$input['id']]);
    $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Establecimiento actualizado exitosamente',
        'establecimiento' => $establecimiento
    ]);
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        // Intentar obtener ID de query params
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de establecimiento requerido']);
            return;
        }
    } else {
        $id = $input['id'];
    }
    
    // Verificar si el establecimiento existe
    $stmt = $pdo->prepare("SELECT 1 as total FROM establecimientos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Establecimiento no encontrado'
        ]);
        return;
    }
    
    // Cambiar estado a INACTIVO en lugar de eliminar físicamente
    $stmt = $pdo->prepare("UPDATE establecimientos SET estado = 'INACTIVO', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Establecimiento no encontrado']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Establecimiento eliminado exitosamente'
    ]);
}
?>