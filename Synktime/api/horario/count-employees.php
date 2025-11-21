<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Verificar parámetro
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de horario no proporcionado']);
    exit;
}

$id_horario = intval($_GET['id']);
$empresaId = $_SESSION['id_empresa'] ?? null;

if (!$empresaId) {
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
    exit;
}

try {
    // Verificar que el horario pertenezca a la empresa
    $sqlVerify = "
        SELECT h.ID_HORARIO 
        FROM HORARIO h 
        JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE 
        WHERE h.ID_HORARIO = :id_horario 
        AND s.ID_EMPRESA = :empresa_id
    ";
    $stmtVerify = $conn->prepare($sqlVerify);
    $stmtVerify->bindValue(':id_horario', $id_horario);
    $stmtVerify->bindValue(':empresa_id', $empresaId);
    $stmtVerify->execute();
    
    if ($stmtVerify->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para acceder a este horario']);
        exit;
    }
    
    // Contar todos los empleados asignados a este horario
    $sqlTotal = "
        SELECT COUNT(DISTINCT eh.ID_EMPLEADO) as total
        FROM EMPLEADO_HORARIO eh
        JOIN EMPLEADO e ON eh.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE eh.ID_HORARIO = :id_horario
        AND s.ID_EMPRESA = :empresa_id
    ";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bindValue(':id_horario', $id_horario);
    $stmtTotal->bindValue(':empresa_id', $empresaId);
    $stmtTotal->execute();
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar empleados con asignaciones vigentes
    $sqlActivos = "
        SELECT COUNT(DISTINCT eh.ID_EMPLEADO) as activos
        FROM EMPLEADO_HORARIO eh
        JOIN EMPLEADO e ON eh.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE eh.ID_HORARIO = :id_horario
        AND s.ID_EMPRESA = :empresa_id
        AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= CURDATE())
    ";
    $stmtActivos = $conn->prepare($sqlActivos);
    $stmtActivos->bindValue(':id_horario', $id_horario);
    $stmtActivos->bindValue(':empresa_id', $empresaId);
    $stmtActivos->execute();
    $activos = $stmtActivos->fetch(PDO::FETCH_ASSOC)['activos'];
    
    echo json_encode([
        'success' => true,
        'total_empleados' => $total,
        'empleados_activos' => $activos,
        'empleados_inactivos' => $total - $activos
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>