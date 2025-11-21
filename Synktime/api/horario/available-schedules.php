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
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado no proporcionado']);
        exit;
    }
    
    $id_empleado = intval($_GET['id']);
    
    // Verificar que el empleado pertenece a la empresa
    $sqlVerifyEmpleado = "
        SELECT 
            e.ID_EMPLEADO, 
            e.ID_ESTABLECIMIENTO,
            est.ID_SEDE
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerifyEmpleado);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }
    
    // Obtener los horarios ya asignados al empleado (vigentes)
    $sqlHorariosAsignados = "
        SELECT h.ID_HORARIO
        FROM EMPLEADO_HORARIO eh
        JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = :id_empleado
        AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= CURDATE())
    ";
    
    $stmt = $conn->prepare($sqlHorariosAsignados);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->execute();
    
    $horariosAsignados = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construir la consulta para horarios disponibles
    $sql = "
        SELECT 
            h.ID_HORARIO,
            h.NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA
        FROM horario h
        JOIN establecimiento e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id
        AND e.ID_SEDE = :id_sede
        AND h.ID_ESTABLECIMIENTO = :id_establecimiento
    ";
    
    $params = [
        ':empresa_id' => $empresaId,
        ':id_sede' => $empleado['ID_SEDE'],
        ':id_establecimiento' => $empleado['ID_ESTABLECIMIENTO']
    ];
    
    // Excluir los horarios ya asignados vigentes
    if (!empty($horariosAsignados)) {
        // Usar parámetros con nombre en lugar de posicionales (?)
        $exclusiones = [];
        foreach ($horariosAsignados as $index => $id) {
            $paramName = ':horario_' . $index;
            $exclusiones[] = $paramName;
            $params[$paramName] = $id;
        }
        $sql .= " AND h.ID_HORARIO NOT IN (" . implode(', ', $exclusiones) . ")";
    }
    
    $sql .= " ORDER BY h.NOMBRE";
    
    $stmt = $conn->prepare($sql);
    
    // Vincular todos los parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener los días para cada horario
    foreach ($horarios as &$horario) {
        $sqlDias = "
            SELECT ID_DIA 
            FROM HORARIO_DIA 
            WHERE ID_HORARIO = :id_horario 
            ORDER BY ID_DIA
        ";
        $stmtDias = $conn->prepare($sqlDias);
        $stmtDias->bindValue(':id_horario', $horario['ID_HORARIO']);
        $stmtDias->execute();
        $dias = $stmtDias->fetchAll(PDO::FETCH_COLUMN);
        $horario['dias'] = implode(',', $dias);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $horarios
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar horarios disponibles: ' . $e->getMessage()
    ]);
}
?>