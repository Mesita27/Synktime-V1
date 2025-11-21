<?php
session_start();
require_once '../../config/database.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
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
    error_log("Error en API de plantillas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function handleGet($pdo) {
    // Si se especifica un ID, obtener plantilla específica con sus horarios
    if (isset($_GET['id'])) {
        getPlantillaById($pdo, $_GET['id']);
        return;
    }
    
    // Obtener todas las plantillas con resumen de horarios
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            COUNT(pd.id) as total_horarios
        FROM plantillas_horarios p
        LEFT JOIN plantilla_horario_detalle pd ON p.id = pd.plantilla_id
        GROUP BY p.id
        ORDER BY p.nombre ASC
    ");
    
    $stmt->execute();
    $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'plantillas' => $plantillas
    ]);
}

function getPlantillaById($pdo, $id) {
    // Obtener información de la plantilla
    $stmt = $pdo->prepare("SELECT * FROM plantillas_horarios WHERE id = ?");
    $stmt->execute([$id]);
    $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plantilla) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada']);
        return;
    }
    
    // Obtener horarios de la plantilla
    $stmt = $pdo->prepare("
        SELECT * FROM plantilla_horario_detalle 
        WHERE plantilla_id = ? 
        ORDER BY dia_semana ASC, hora_entrada ASC
    ");
    $stmt->execute([$id]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $plantilla['horarios'] = $horarios;
    
    echo json_encode([
        'success' => true,
        'plantilla' => $plantilla
    ]);
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
        // Si viene de form data, los horarios pueden estar en formato diferente
        if (isset($_POST['horarios'])) {
            $input['horarios'] = json_decode($_POST['horarios'], true);
        }
    }
    
    // Validar datos requeridos
    if (empty($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        return;
    }
    
    if (empty($input['horarios']) || !is_array($input['horarios'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe especificar al menos un horario']);
        return;
    }
    
    // Verificar si ya existe una plantilla con el mismo nombre
    $stmt = $pdo->prepare("SELECT id FROM plantillas_horarios WHERE nombre = ?");
    $stmt->execute([$input['nombre']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe una plantilla con este nombre']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Insertar plantilla
        $stmt = $pdo->prepare("
            INSERT INTO plantillas_horarios (nombre, descripcion, estado, created_at, updated_at) 
            VALUES (?, ?, 'ACTIVO', NOW(), NOW())
        ");
        
        $stmt->execute([
            $input['nombre'],
            $input['descripcion'] ?? null
        ]);
        
        $plantillaId = $pdo->lastInsertId();
        
        // Insertar horarios de la plantilla
        $stmtHorario = $pdo->prepare("
            INSERT INTO plantilla_horario_detalle 
            (plantilla_id, dia_semana, hora_entrada, hora_salida, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        foreach ($input['horarios'] as $horario) {
            // Validar datos del horario
            if (empty($horario['dia_semana']) || empty($horario['hora_entrada']) || empty($horario['hora_salida'])) {
                throw new Exception('Datos de horario incompletos');
            }
            
            $stmtHorario->execute([
                $plantillaId,
                $horario['dia_semana'],
                $horario['hora_entrada'],
                $horario['hora_salida']
            ]);
        }
        
        $pdo->commit();
        
        // Obtener la plantilla creada con sus horarios
        getPlantillaById($pdo, $plantillaId);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error creando plantilla: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear la plantilla: ' . $e->getMessage()]);
    }
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
        return;
    }
    
    // Validar datos requeridos
    if (empty($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        return;
    }
    
    // Verificar si ya existe una plantilla con el mismo nombre (excluyendo la actual)
    $stmt = $pdo->prepare("SELECT id FROM plantillas_horarios WHERE nombre = ? AND id != ?");
    $stmt->execute([$input['nombre'], $input['id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya existe una plantilla con este nombre']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Actualizar plantilla
        $stmt = $pdo->prepare("
            UPDATE plantillas_horarios 
            SET nombre = ?, descripcion = ?, estado = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['nombre'],
            $input['descripcion'] ?? null,
            $input['estado'] ?? 'ACTIVO',
            $input['id']
        ]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Plantilla no encontrada');
        }
        
        // Si se proporcionan horarios, actualizar
        if (isset($input['horarios']) && is_array($input['horarios'])) {
            // Eliminar horarios existentes
            $stmt = $pdo->prepare("DELETE FROM plantilla_horario_detalle WHERE plantilla_id = ?");
            $stmt->execute([$input['id']]);
            
            // Insertar nuevos horarios
            $stmtHorario = $pdo->prepare("
                INSERT INTO plantilla_horario_detalle 
                (plantilla_id, dia_semana, hora_entrada, hora_salida, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            foreach ($input['horarios'] as $horario) {
                if (empty($horario['dia_semana']) || empty($horario['hora_entrada']) || empty($horario['hora_salida'])) {
                    throw new Exception('Datos de horario incompletos');
                }
                
                $stmtHorario->execute([
                    $input['id'],
                    $horario['dia_semana'],
                    $horario['hora_entrada'],
                    $horario['hora_salida']
                ]);
            }
        }
        
        $pdo->commit();
        
        // Obtener la plantilla actualizada
        getPlantillaById($pdo, $input['id']);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error actualizando plantilla: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la plantilla: ' . $e->getMessage()]);
    }
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        // Intentar obtener ID de query params
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de plantilla requerido']);
            return;
        }
    } else {
        $id = $input['id'];
    }
    
    $pdo->beginTransaction();
    
    try {
        // Cambiar estado a INACTIVO en lugar de eliminar físicamente
        $stmt = $pdo->prepare("UPDATE plantillas_horarios SET estado = 'INACTIVO', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Plantilla no encontrada');
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Plantilla eliminada exitosamente'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error eliminando plantilla: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la plantilla: ' . $e->getMessage()]);
    }
}
?>
