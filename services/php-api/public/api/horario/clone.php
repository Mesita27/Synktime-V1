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
if (!$data || !isset($data['id_horario']) || !isset($data['nombre_nuevo'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id_horario = intval($data['id_horario']);
$nombre_nuevo = trim($data['nombre_nuevo']);
$empresaId = $_SESSION['id_empresa'] ?? null;

if (!$empresaId) {
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
    exit;
}

if (empty($nombre_nuevo)) {
    echo json_encode(['success' => false, 'message' => 'El nombre del horario es requerido']);
    exit;
}

try {
    // Verificar que el horario original pertenezca a la empresa
    $sqlVerify = "
        SELECT h.*, e.ID_SEDE
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
    
    $horarioOriginal = $stmtVerify->fetch(PDO::FETCH_ASSOC);
    
    if (!$horarioOriginal) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para acceder a este horario']);
        exit;
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Clonar el horario
    $sqlClone = "
        INSERT INTO HORARIO (NOMBRE, ID_ESTABLECIMIENTO, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA)
        VALUES (:nombre, :establecimiento, :hora_entrada, :hora_salida, :tolerancia)
    ";
    
    $stmtClone = $conn->prepare($sqlClone);
    $stmtClone->bindValue(':nombre', $nombre_nuevo);
    $stmtClone->bindValue(':establecimiento', $horarioOriginal['ID_ESTABLECIMIENTO']);
    $stmtClone->bindValue(':hora_entrada', $horarioOriginal['HORA_ENTRADA']);
    $stmtClone->bindValue(':hora_salida', $horarioOriginal['HORA_SALIDA']);
    $stmtClone->bindValue(':tolerancia', $horarioOriginal['TOLERANCIA']);
    $stmtClone->execute();
    
    $nuevoHorarioId = $conn->lastInsertId();
    
    // Copiar los días del horario original
    $sqlDias = "
        INSERT INTO HORARIO_DIA (ID_HORARIO, ID_DIA)
        SELECT :nuevo_id, ID_DIA
        FROM HORARIO_DIA
        WHERE ID_HORARIO = :id_original
    ";
    
    $stmtDias = $conn->prepare($sqlDias);
    $stmtDias->bindValue(':nuevo_id', $nuevoHorarioId);
    $stmtDias->bindValue(':id_original', $id_horario);
    $stmtDias->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Horario clonado correctamente',
        'id_horario' => $nuevoHorarioId
    ]);
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>