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

    // Obtener datos
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Validar datos requeridos
    $nombre = $data['nombre'] ?? '';
    $establecimiento = $data['establecimiento'] ?? '';
    $id_horario = $data['id_horario'] ?? '';

    if (empty($nombre) || empty($establecimiento)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
        exit;
    }
    
    // Verificar que el establecimiento pertenezca a la empresa
    $sqlVerifyEstablecimiento = "
        SELECT e.ID_ESTABLECIMIENTO
        FROM ESTABLECIMIENTO e
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.ID_ESTABLECIMIENTO = :establecimiento
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerifyEstablecimiento);
    $stmt->bindValue(':establecimiento', $establecimiento);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para este establecimiento']);
        exit;
    }

    // Verificar duplicados
    $sql = "
        SELECT COUNT(*) as count 
        FROM HORARIO h
        JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE h.NOMBRE = :nombre 
        AND h.ID_ESTABLECIMIENTO = :establecimiento
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $params = [
        ':nombre' => $nombre,
        ':establecimiento' => $establecimiento,
        ':empresa_id' => $empresaId
    ];
    
    // Si es una actualización, excluir el horario actual
    if ($id_horario) {
        $sql .= " AND h.ID_HORARIO != :id_horario";
        $params[':id_horario'] = $id_horario;
    }
    
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isDuplicate = $result['count'] > 0;
    
    echo json_encode([
        'success' => true,
        'isDuplicate' => $isDuplicate,
        'message' => $isDuplicate ? 'Ya existe un horario con este nombre en el establecimiento seleccionado' : ''
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar duplicados: ' . $e->getMessage()
    ]);
}
?>