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

// Manejar method override para navegadores que no soportan DELETE
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['_method'])) {
        $method = $input['_method'];
    }
}

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
        case 'DELETE':
            handleDelete($pdo, $currentUser);
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

function handleGet($pdo, $currentUser) {
    // Verificar si se solicita un establecimiento específico
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Obtener establecimiento específico
        $stmt = $pdo->prepare("
            SELECT 
                e.ID_ESTABLECIMIENTO as id,
                e.NOMBRE as nombre,
                e.DIRECCION as direccion,
                e.ID_SEDE as sede_id,
                e.ESTADO as estado,
                s.NOMBRE as sede_nombre,
                emp.NOMBRE as empresa_nombre,
                emp.RUC as empresa_ruc
            FROM establecimiento e
            LEFT JOIN sede s ON e.ID_SEDE = s.ID_SEDE
            LEFT JOIN empresa emp ON s.ID_EMPRESA = emp.ID_EMPRESA
            WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
        ");
        
        $stmt->execute([$id, $currentUser['id_empresa']]);
        $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($establecimiento) {
            echo json_encode([
                'success' => true,
                'establecimiento' => $establecimiento
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Establecimiento no encontrado o no pertenece a su empresa'
            ]);
        }
        return;
    }
    
    // Obtener todos los establecimientos (comportamiento original)
    $stmt = $pdo->prepare("
        SELECT 
            e.ID_ESTABLECIMIENTO as id,
            e.NOMBRE as nombre,
            e.DIRECCION as direccion,
            e.ID_SEDE as sede_id,
            e.ESTADO as estado,
            s.NOMBRE as sede_nombre,
            emp.NOMBRE as empresa_nombre,
            emp.RUC as empresa_ruc,
            0 as total_empleados
        FROM establecimiento e
        LEFT JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        LEFT JOIN empresa emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE s.ID_EMPRESA = ?
        ORDER BY s.NOMBRE ASC, e.NOMBRE ASC
    ");
    
    $stmt->execute([$currentUser['id_empresa']]);
    $establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'establecimientos' => $establecimientos
    ]);
}

function handlePost($pdo, $currentUser) {
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
    
    // Aceptar tanto 'id_sede' como 'sede_id' para compatibilidad
    $sede_id = $input['id_sede'] ?? $input['sede_id'] ?? null;
    if (empty($sede_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede es requerida']);
        return;
    }
    
    // Verificar que la sede existe, está activa y pertenece a la empresa del usuario
    $stmt = $pdo->prepare("SELECT ID_SEDE FROM sede WHERE ID_SEDE = ? AND ESTADO = 'A' AND ID_EMPRESA = ?");
    $stmt->execute([$sede_id, $currentUser['id_empresa']]);
    
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede seleccionada no existe, está inactiva o no pertenece a su empresa']);
        return;
    }
    
    // Verificar si ya existe un establecimiento con el mismo nombre en la misma sede
    $stmt = $pdo->prepare("SELECT ID_ESTABLECIMIENTO FROM establecimiento WHERE NOMBRE = ? AND ID_SEDE = ?");
    $stmt->execute([$input['nombre'], $sede_id]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe un establecimiento con este nombre en la sede seleccionada']);
        return;
    }
    
    // Insertar nuevo establecimiento
    $stmt = $pdo->prepare("
        INSERT INTO establecimiento (NOMBRE, DIRECCION, ID_SEDE, ESTADO) 
        VALUES (?, ?, ?, 'A')
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['direccion'] ?? null,
        $sede_id
    ]);
    
    $establecimientoId = $pdo->lastInsertId();
    
    // Obtener el establecimiento creado con datos de sede (verificando empresa)
    $stmt = $pdo->prepare("
        SELECT 
            e.ID_ESTABLECIMIENTO as id,
            e.NOMBRE as nombre,
            e.DIRECCION as direccion,
            e.ID_SEDE as sede_id,
            e.ESTADO as estado,
            s.NOMBRE as sede_nombre,
            emp.NOMBRE as empresa_nombre,
            emp.RUC as empresa_ruc
        FROM establecimiento e
        LEFT JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        LEFT JOIN empresa emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$establecimientoId, $currentUser['id_empresa']]);
    $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Establecimiento creado exitosamente',
        'establecimiento' => $establecimiento
    ]);
}

function handlePut($pdo, $currentUser) {
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
    
    // Aceptar tanto 'id_sede' como 'sede_id' para compatibilidad
    $sede_id = $input['id_sede'] ?? $input['sede_id'] ?? null;
    if (empty($sede_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede es requerida']);
        return;
    }
    
    // Verificar que el establecimiento pertenece a la empresa del usuario
    $stmt = $pdo->prepare("
        SELECT e.ID_ESTABLECIMIENTO 
        FROM establecimiento e
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$input['id'], $currentUser['id_empresa']]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Establecimiento no encontrado o no pertenece a su empresa']);
        return;
    }
    
    // Verificar que la sede existe, está activa y pertenece a la empresa del usuario
    $stmt = $pdo->prepare("SELECT ID_SEDE FROM sede WHERE ID_SEDE = ? AND ESTADO = 'A' AND ID_EMPRESA = ?");
    $stmt->execute([$sede_id, $currentUser['id_empresa']]);
    
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La sede seleccionada no existe, está inactiva o no pertenece a su empresa']);
        return;
    }
    
    // Verificar si ya existe un establecimiento con el mismo nombre en la misma sede (excluyendo el actual)
    $stmt = $pdo->prepare("SELECT ID_ESTABLECIMIENTO FROM establecimiento WHERE NOMBRE = ? AND ID_SEDE = ? AND ID_ESTABLECIMIENTO != ?");
    $stmt->execute([$input['nombre'], $sede_id, $input['id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe un establecimiento con este nombre en la sede seleccionada']);
        return;
    }
    
    // Actualizar establecimiento (solo si pertenece a la empresa del usuario)
    $stmt = $pdo->prepare("
        UPDATE establecimiento e
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        SET e.NOMBRE = ?, e.DIRECCION = ?, e.ID_SEDE = ?
        WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['direccion'] ?? null,
        $sede_id,
        $input['id'],
        $currentUser['id_empresa']
    ]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Establecimiento no encontrado o no pertenece a su empresa']);
        return;
    }
    
    // Obtener el establecimiento actualizado con datos de sede
    $stmt = $pdo->prepare("
        SELECT 
            e.ID_ESTABLECIMIENTO as id,
            e.NOMBRE as nombre,
            e.DIRECCION as direccion,
            e.ID_SEDE as sede_id,
            e.ESTADO as estado,
            s.NOMBRE as sede_nombre,
            emp.NOMBRE as empresa_nombre,
            emp.RUC as empresa_ruc
        FROM establecimiento e
        LEFT JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        LEFT JOIN empresa emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$input['id'], $currentUser['id_empresa']]);
    $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Establecimiento actualizado exitosamente',
        'establecimiento' => $establecimiento
    ]);
}

function handleDelete($pdo, $currentUser) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Manejar eliminación múltiple
    if ($input && isset($input['ids']) && is_array($input['ids'])) {
        $ids = array_filter($input['ids'], 'is_numeric');
        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'IDs de establecimientos requeridos']);
            return;
        }
        
        $deleted = 0;
        $errors = [];
        
        foreach ($ids as $id) {
            // Verificar que el establecimiento existe y pertenece a la empresa del usuario
            $stmt = $pdo->prepare("
                SELECT e.ID_ESTABLECIMIENTO 
                FROM establecimiento e
                JOIN sede s ON e.ID_SEDE = s.ID_SEDE
                WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
            ");
            $stmt->execute([$id, $currentUser['id_empresa']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                $errors[] = "Establecimiento ID $id no encontrado o no pertenece a su empresa";
                continue;
            }
            
            // Verificar si hay empleados asignados a este establecimiento
            $stmt = $pdo->prepare("SELECT COUNT(*) as empleado_count FROM empleado WHERE ID_ESTABLECIMIENTO = ?");
            $stmt->execute([$id]);
            $empleadoCount = $stmt->fetch(PDO::FETCH_ASSOC)['empleado_count'];
            
            if ($empleadoCount > 0) {
                $errors[] = "No se puede eliminar el establecimiento ID $id porque tiene $empleadoCount empleado(s) asignado(s)";
                continue;
            }
            
            // Eliminar físicamente el establecimiento
            $stmt = $pdo->prepare("
                DELETE e FROM establecimiento e
                JOIN sede s ON e.ID_SEDE = s.ID_SEDE
                WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
            ");
            $stmt->execute([$id, $currentUser['id_empresa']]);
            
            if ($stmt->rowCount() > 0) {
                $deleted++;
            } else {
                $errors[] = "Error al eliminar establecimiento ID $id";
            }
        }
        
        if ($deleted > 0) {
            $message = $deleted === 1 ? '1 establecimiento eliminado' : "$deleted establecimientos eliminados";
            if (!empty($errors)) {
                $message .= ', pero hubo algunos errores';
            }
            echo json_encode([
                'success' => true,
                'message' => $message,
                'deleted' => $deleted,
                'errors' => $errors
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo eliminar ningún establecimiento',
                'errors' => $errors
            ]);
        }
        return;
    }
    
    // Manejar eliminación individual
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
    
    // Verificar si el establecimiento existe y pertenece a la empresa del usuario
    $stmt = $pdo->prepare("
        SELECT e.ID_ESTABLECIMIENTO 
        FROM establecimiento e
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$id, $currentUser['id_empresa']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Establecimiento no encontrado o no pertenece a su empresa'
        ]);
        return;
    }
    
    // Verificar si hay empleados asignados a este establecimiento
    $stmt = $pdo->prepare("SELECT COUNT(*) as empleado_count FROM empleado WHERE ID_ESTABLECIMIENTO = ?");
    $stmt->execute([$id]);
    $empleadoCount = $stmt->fetch(PDO::FETCH_ASSOC)['empleado_count'];
    
    if ($empleadoCount > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => "No se puede eliminar el establecimiento porque tiene $empleadoCount empleado(s) asignado(s). Elimine primero los empleados."
        ]);
        return;
    }
    
    // Eliminar físicamente el establecimiento (solo si pertenece a la empresa)
    $stmt = $pdo->prepare("
        DELETE e FROM establecimiento e
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$id, $currentUser['id_empresa']]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Establecimiento no encontrado o no pertenece a su empresa']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Establecimiento eliminado exitosamente'
    ]);
}
?>
