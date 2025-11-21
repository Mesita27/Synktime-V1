<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }
    
    // Obtener datos del cuerpo de la petición
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['empleado']) || !isset($data['horario']) || !isset($data['fecha_desde'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $id_empleado = intval($data['empleado']);
    $id_horario = intval($data['horario']);
    $fecha_desde = $data['fecha_desde'];
    
    // Verificar que el empleado y el horario pertenecen a la empresa
    $sqlVerify = "
        SELECT 1
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s1 ON est.ID_SEDE = s1.ID_SEDE
        JOIN HORARIO h ON 1=1
        JOIN ESTABLECIMIENTO e2 ON h.ID_ESTABLECIMIENTO = e2.ID_ESTABLECIMIENTO
        JOIN SEDE s2 ON e2.ID_SEDE = s2.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado
        AND h.ID_HORARIO = :id_horario
        AND s1.ID_EMPRESA = :empresa_id
        AND s2.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerify);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para modificar esta asignación']);
        exit;
    }
    
    // Eliminar la asignación
    $sql = "
        DELETE FROM EMPLEADO_HORARIO 
        WHERE ID_EMPLEADO = :id_empleado 
        AND ID_HORARIO = :id_horario 
        AND FECHA_DESDE = :fecha_desde
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->bindValue(':fecha_desde', $fecha_desde);
    $stmt->execute();
    
    $count = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => $count > 0 ? 'Asignación eliminada correctamente' : 'No se encontró la asignación especificada',
        'count' => $count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar asignación: ' . $e->getMessage()
    ]);
}
?>