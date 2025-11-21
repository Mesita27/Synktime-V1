<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['horarios']) || !is_array($data['horarios'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$horarios = array_map('intval', $data['horarios']);
$empresaId = $_SESSION['id_empresa'] ?? null;

if (!$empresaId) {
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
    exit;
}

if (empty($horarios)) {
    echo json_encode(['success' => false, 'message' => 'No se seleccionaron horarios']);
    exit;
}

try {
    // Verificar que todos los horarios pertenezcan a la empresa
    $placeholders = implode(',', array_fill(0, count($horarios), '?'));
    $sqlVerify = "
        SELECT COUNT(*) as count
        FROM HORARIO h 
        JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE 
        WHERE h.ID_HORARIO IN ($placeholders)
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmtVerify = $conn->prepare($sqlVerify);
    foreach ($horarios as $index => $id) {
        $stmtVerify->bindValue($index + 1, $id);
    }
    $stmtVerify->bindValue(':empresa_id', $empresaId);
    $stmtVerify->execute();
    
    $countResult = $stmtVerify->fetch(PDO::FETCH_ASSOC);
    
    if ($countResult['count'] !== count($horarios)) {
        echo json_encode(['success' => false, 'message' => 'Algunos horarios seleccionados no son válidos o no tiene permisos para eliminarlos']);
        exit;
    }
    
    // Procesar cada horario individualmente para mantener un registro de éxitos/errores
    $resultados = [];
    $totalExito = 0;
    $totalError = 0;
    $totalEmpleados = 0;
    
    foreach ($horarios as $id_horario) {
        try {
            $conn->beginTransaction();
            
            // Verificar si hay vinculaciones con empleados
            $sqlCheckEmployees = "SELECT COUNT(*) FROM EMPLEADO_HORARIO WHERE ID_HORARIO = ?";
            $stmt = $conn->prepare($sqlCheckEmployees);
            $stmt->bindValue(1, $id_horario);
            $stmt->execute();
            
            $empleadosCount = $stmt->fetchColumn();
            $totalEmpleados += $empleadosCount;
            
            // Eliminar vinculaciones con empleados
            if ($empleadosCount > 0) {
                $sqlDeleteEmpHor = "DELETE FROM EMPLEADO_HORARIO WHERE ID_HORARIO = ?";
                $stmt = $conn->prepare($sqlDeleteEmpHor);
                $stmt->bindValue(1, $id_horario);
                $stmt->execute();
            }
            
            // Eliminar días de la semana
            $sqlDeleteDias = "DELETE FROM HORARIO_DIA WHERE ID_HORARIO = ?";
            $stmt = $conn->prepare($sqlDeleteDias);
            $stmt->bindValue(1, $id_horario);
            $stmt->execute();
            
            // Eliminar el horario
            $sqlDeleteHorario = "DELETE FROM HORARIO WHERE ID_HORARIO = ?";
            $stmt = $conn->prepare($sqlDeleteHorario);
            $stmt->bindValue(1, $id_horario);
            $stmt->execute();
            
            $conn->commit();
            
            $resultados[] = [
                'id_horario' => $id_horario,
                'success' => true,
                'empleados_desvinculados' => $empleadosCount
            ];
            $totalExito++;
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $resultados[] = [
                'id_horario' => $id_horario,
                'success' => false,
                'message' => $e->getMessage()
            ];
            $totalError++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'total_procesados' => count($horarios),
        'total_exito' => $totalExito,
        'total_error' => $totalError,
        'total_empleados_desvinculados' => $totalEmpleados,
        'resultados' => $resultados
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>