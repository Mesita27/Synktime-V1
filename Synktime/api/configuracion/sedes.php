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
    error_log("Error en API de sedes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function handleGet($pdo, $currentUser) {
    // Filtrar sedes por empresa del usuario logueado
    $stmt = $pdo->prepare("
        SELECT 
            s.ID_SEDE as id,
            s.NOMBRE as nombre,
            s.DIRECCION as direccion,
            s.ID_EMPRESA as id_empresa,
            s.ESTADO as estado,
            e.NOMBRE as empresa_nombre,
            e.RUC as empresa_ruc,
            0 as total_empleados
        FROM sede s
        LEFT JOIN empresa e ON s.ID_EMPRESA = e.ID_EMPRESA
        WHERE s.ID_EMPRESA = ?
        ORDER BY s.NOMBRE ASC
    ");
    
    $stmt->execute([$currentUser['id_empresa']]);
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sedes' => $sedes
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
    
    // Verificar si ya existe una sede con el mismo nombre en la empresa
    $stmt = $pdo->prepare("SELECT ID_SEDE FROM sede WHERE NOMBRE = ? AND ID_EMPRESA = ? AND ID_SEDE != ?");
    $stmt->execute([$input['nombre'], $currentUser['id_empresa'], $input['id'] ?? 0]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe una sede con este nombre en su empresa']);
        return;
    }
    
    // Insertar nueva sede asociada a la empresa del usuario
    $stmt = $pdo->prepare("
        INSERT INTO sede (NOMBRE, DIRECCION, ID_EMPRESA, ESTADO) 
        VALUES (?, ?, ?, 'A')
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['direccion'] ?? null,
        $currentUser['id_empresa']
    ]);
    
    $sedeId = $pdo->lastInsertId();
    
    // Obtener la sede creada
    $stmt = $pdo->prepare("
        SELECT 
            s.ID_SEDE as id,
            s.NOMBRE as nombre,
            s.DIRECCION as direccion,
            s.ID_EMPRESA as id_empresa,
            s.ESTADO as estado,
            e.NOMBRE as empresa_nombre,
            e.RUC as empresa_ruc
        FROM sede s
        LEFT JOIN empresa e ON s.ID_EMPRESA = e.ID_EMPRESA
        WHERE s.ID_SEDE = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$sedeId, $currentUser['id_empresa']]);
    $sede = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sede creada exitosamente',
        'sede' => $sede
    ]);
}

function handlePut($pdo, $currentUser) {
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
    
    // Verificar que la sede pertenece a la empresa del usuario
    $stmt = $pdo->prepare("SELECT ID_SEDE FROM sede WHERE ID_SEDE = ? AND ID_EMPRESA = ?");
    $stmt->execute([$input['id'], $currentUser['id_empresa']]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sede no encontrada o no pertenece a su empresa']);
        return;
    }
    
    // Verificar si ya existe una sede con el mismo nombre en la empresa (excluyendo la actual)
    $stmt = $pdo->prepare("SELECT ID_SEDE FROM sede WHERE NOMBRE = ? AND ID_EMPRESA = ? AND ID_SEDE != ?");
    $stmt->execute([$input['nombre'], $currentUser['id_empresa'], $input['id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe una sede con este nombre en su empresa']);
        return;
    }
    
    // Actualizar sede (solo si pertenece a la empresa del usuario)
    $stmt = $pdo->prepare("
        UPDATE sede 
        SET NOMBRE = ?, DIRECCION = ?, ESTADO = ?
        WHERE ID_SEDE = ? AND ID_EMPRESA = ?
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['direccion'] ?? null,
        $input['estado'] ?? 'A',
        $input['id'],
        $currentUser['id_empresa']
    ]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sede no encontrada o no pertenece a su empresa']);
        return;
    }
    
    // Obtener la sede actualizada
    $stmt = $pdo->prepare("
        SELECT 
            s.ID_SEDE as id,
            s.NOMBRE as nombre,
            s.DIRECCION as direccion,
            s.ID_EMPRESA as id_empresa,
            s.ESTADO as estado,
            e.NOMBRE as empresa_nombre,
            e.RUC as empresa_ruc
        FROM sede s
        LEFT JOIN empresa e ON s.ID_EMPRESA = e.ID_EMPRESA
        WHERE s.ID_SEDE = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$input['id'], $currentUser['id_empresa']]);
    $sede = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sede actualizada exitosamente',
        'sede' => $sede
    ]);
}

function handleDelete($pdo, $currentUser) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Manejar eliminación múltiple
    if ($input && isset($input['ids']) && is_array($input['ids'])) {
        $ids = array_filter($input['ids'], 'is_numeric');
        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'IDs de sedes requeridos']);
            return;
        }
        
        $deleted = 0;
        $errors = [];
        
            foreach ($ids as $id) {
                // Verificar que la sede existe y pertenece a la empresa del usuario
                $stmt = $pdo->prepare("SELECT ID_SEDE FROM sede WHERE ID_SEDE = ? AND ID_EMPRESA = ?");
                $stmt->execute([$id, $currentUser['id_empresa']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$result) {
                    $errors[] = "Sede ID $id no encontrada o no pertenece a su empresa";
                    continue;
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // Verificar si hay empleados en establecimientos de esta sede
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as empleado_count 
                        FROM empleado emp 
                        JOIN establecimiento est ON emp.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                        WHERE est.ID_SEDE = ?
                    ");
                    $stmt->execute([$id]);
                    $empleadoCount = $stmt->fetch(PDO::FETCH_ASSOC)['empleado_count'];
                    
                    if ($empleadoCount > 0) {
                        $errors[] = "No se puede eliminar la sede ID $id porque tiene $empleadoCount empleado(s) asignado(s)";
                        $pdo->rollBack();
                        continue;
                    }
                    
                    // Eliminar primero todos los establecimientos de esta sede
                    $stmt = $pdo->prepare("DELETE FROM establecimiento WHERE ID_SEDE = ?");
                    $stmt->execute([$id]);
                    
                    // Luego eliminar la sede
                    $stmt = $pdo->prepare("DELETE FROM sede WHERE ID_SEDE = ? AND ID_EMPRESA = ?");
                    $stmt->execute([$id, $currentUser['id_empresa']]);
                    
                    if ($stmt->rowCount() > 0) {
                        $deleted++;
                        $pdo->commit();
                    } else {
                        $errors[] = "Error al eliminar sede ID $id";
                        $pdo->rollBack();
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = "Error al eliminar sede ID $id: " . $e->getMessage();
                }
            }        if ($deleted > 0) {
            $message = $deleted === 1 ? '1 sede eliminada' : "$deleted sedes eliminadas";
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
                'message' => 'No se pudo eliminar ninguna sede',
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
            echo json_encode(['success' => false, 'message' => 'ID de sede requerido']);
            return;
        }
    } else {
        $id = $input['id'];
    }
    
    // Verificar que la sede existe y pertenece a la empresa del usuario
    $stmt = $pdo->prepare("SELECT ID_SEDE FROM sede WHERE ID_SEDE = ? AND ID_EMPRESA = ?");
    $stmt->execute([$id, $currentUser['id_empresa']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Sede no encontrada o no pertenece a su empresa'
        ]);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verificar si hay empleados en establecimientos de esta sede
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as empleado_count 
            FROM empleado emp 
            JOIN establecimiento est ON emp.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
            WHERE est.ID_SEDE = ?
        ");
        $stmt->execute([$id]);
        $empleadoCount = $stmt->fetch(PDO::FETCH_ASSOC)['empleado_count'];
        
        if ($empleadoCount > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => "No se puede eliminar la sede porque tiene $empleadoCount empleado(s) asignado(s). Elimine primero los empleados."
            ]);
            return;
        }
        
        // Eliminar primero todos los establecimientos de esta sede
        $stmt = $pdo->prepare("DELETE FROM establecimiento WHERE ID_SEDE = ?");
        $stmt->execute([$id]);
        
        // Luego eliminar la sede
        $stmt = $pdo->prepare("DELETE FROM sede WHERE ID_SEDE = ? AND ID_EMPRESA = ?");
        $stmt->execute([$id, $currentUser['id_empresa']]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sede no encontrada o no pertenece a su empresa']);
            return;
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la sede: ' . $e->getMessage()]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sede eliminada exitosamente'
    ]);
}
?>
