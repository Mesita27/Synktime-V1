<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Verificar parámetro
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de horario no proporcionado']);
        exit;
    }

    $id_horario = intval($_GET['id']);

    // Verificar que el horario pertenece a la empresa
    $sqlVerify = "
        SELECT h.ID_HORARIO
        FROM HORARIO h
        JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE h.ID_HORARIO = :id_horario
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerify);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para este horario']);
        exit;
    }

    // Verificar si hay asistencias registradas con este horario
    $sql = "SELECT COUNT(*) as count FROM ASISTENCIA WHERE ID_HORARIO = :id_horario";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasAttendance = $result['count'] > 0;
    
    echo json_encode([
        'success' => true,
        'hasAttendance' => $hasAttendance,
        'count' => $result['count'],
        'message' => $hasAttendance 
            ? "Hay {$result['count']} registros de asistencia vinculados a este horario" 
            : 'No hay asistencias registradas con este horario'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>