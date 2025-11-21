<?php
/**
 * API simple para testing de justificaciones (sin autenticación)
 */

require_once 'config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'getEmpleadosElegibles':
                        getEmpleadosElegibles($pdo);
                        break;
                    case 'getRecientes':
                        getJustificacionesRecientes($pdo);
                        break;
                    case 'getDetalle':
                        getDetalleJustificacion($pdo);
                        break;
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
                }
            } else {
                getJustificacionesRecientes($pdo);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            createJustificacion($pdo, $input);
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            deleteJustificacion($pdo, $input);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    error_log("Error en API justificaciones: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

function getEmpleadosElegibles($pdo) {
    try {
        $fechaReferencia = date('Y-m-d H:i:s', strtotime('-16 hours'));
        $fechaHoy = date('Y-m-d');
        
        $sql = "
            SELECT DISTINCT
                e.ID_EMPLEADO as id,
                e.CODIGO as codigo,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre,
                est.NOMBRE as establecimiento,
                s.NOMBRE as sede,
                '$fechaHoy' as fecha_falta,
                '8' as horas_programadas
            FROM empleado e
            INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            WHERE e.ACTIVO = 'S' 
            AND e.ESTADO = 'A'
            AND NOT EXISTS (
                SELECT 1 FROM justificacion j 
                WHERE j.ID_EMPLEADO = e.ID_EMPLEADO 
                AND j.FECHA = ?
            )
            ORDER BY s.NOMBRE, est.NOMBRE, e.NOMBRE, e.APELLIDO 
            LIMIT 10
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fechaHoy]);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'empleados' => $empleados,
            'total' => count($empleados)
        ]);
        
    } catch (Exception $e) {
        error_log("Error empleados elegibles: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function getJustificacionesRecientes($pdo) {
    try {
        $limit = $_GET['limit'] ?? 10;
        
        $stmt = $pdo->prepare("
            SELECT 
                j.ID_JUSTIFICACION as id,
                j.FECHA as fecha,
                j.MOTIVO as motivo,
                j.APROBADO as aprobado,
                e.CODIGO as empleado_codigo,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre
            FROM justificacion j
            INNER JOIN empleado e ON j.ID_EMPLEADO = e.ID_EMPLEADO
            ORDER BY j.ID_JUSTIFICACION DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $justificaciones = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'justificaciones' => $justificaciones
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getDetalleJustificacion($pdo) {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID requerido');
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                j.ID_JUSTIFICACION as id,
                j.FECHA as fecha,
                j.MOTIVO as motivo,
                j.APROBADO as aprobado,
                e.CODIGO as empleado_codigo,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre
            FROM justificacion j
            INNER JOIN empleado e ON j.ID_EMPLEADO = e.ID_EMPLEADO
            WHERE j.ID_JUSTIFICACION = ?
        ");
        
        $stmt->execute([$id]);
        $justificacion = $stmt->fetch();
        
        if (!$justificacion) {
            throw new Exception('No encontrada');
        }
        
        echo json_encode([
            'success' => true,
            'justificacion' => $justificacion
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function createJustificacion($pdo, $data) {
    try {
        if (!isset($data['empleado_id']) || !isset($data['fecha']) || !isset($data['motivo'])) {
            throw new Exception('Datos requeridos faltantes');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO justificacion (ID_EMPLEADO, FECHA, MOTIVO, APROBADO) 
            VALUES (?, ?, ?, '1')
        ");
        
        $stmt->execute([
            $data['empleado_id'],
            $data['fecha'],
            $data['motivo']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Justificación creada',
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function deleteJustificacion($pdo, $data) {
    try {
        if (!isset($data['id_justificacion'])) {
            throw new Exception('ID requerido');
        }
        
        $stmt = $pdo->prepare("DELETE FROM justificacion WHERE ID_JUSTIFICACION = ?");
        $stmt->execute([$data['id_justificacion']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Eliminada exitosamente'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>