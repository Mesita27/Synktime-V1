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
    if (!$data || !isset($data['id_horario'])) {
        echo json_encode(['success' => false, 'message' => 'ID de horario no proporcionado']);
        exit;
    }

    $id_horario = intval($data['id_horario']);
    
    // Verificar que el horario pertenece a la empresa
    $sqlVerifyHorario = "
        SELECT h.ID_HORARIO
        FROM HORARIO h
        JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE h.ID_HORARIO = :id_horario
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerifyHorario);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar este horario']);
        exit;
    }

    $conn->beginTransaction();
    
    // Primero verificar si hay vinculaciones con empleados
    $sqlCheckEmployees = "SELECT COUNT(*) FROM EMPLEADO_HORARIO WHERE ID_HORARIO = :id_horario";
    $stmt = $conn->prepare($sqlCheckEmployees);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->execute();
    
    $empleadosCount = $stmt->fetchColumn();
    
    // Eliminar vinculaciones con empleados
    if ($empleadosCount > 0) {
        $sqlDeleteEmpHor = "DELETE FROM EMPLEADO_HORARIO WHERE ID_HORARIO = :id_horario";
        $stmt = $conn->prepare($sqlDeleteEmpHor);
        $stmt->bindValue(':id_horario', $id_horario);
        $stmt->execute();
    }
    
    // Eliminar días de la semana
    $sqlDeleteDias = "DELETE FROM HORARIO_DIA WHERE ID_HORARIO = :id_horario";
    $stmt = $conn->prepare($sqlDeleteDias);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->execute();
    
    // Eliminar el horario
    $sqlDeleteHorario = "DELETE FROM HORARIO WHERE ID_HORARIO = :id_horario";
    $stmt = $conn->prepare($sqlDeleteHorario);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Horario eliminado correctamente',
        'empleados_desvinculados' => $empleadosCount
    ]);
    
} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar horario: ' . $e->getMessage()
    ]);
}
?>