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
        echo json_encode(['success' => false, 'message' => 'ID de horario no proporcionado']);
        exit;
    }

    $id_horario = intval($_GET['id']);

    // Consultar datos del horario verificando que pertenece a la empresa del usuario
    $sql = "
        SELECT 
            h.ID_HORARIO,
            h.NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            e.ID_ESTABLECIMIENTO,
            e.NOMBRE as establecimiento,
            s.ID_SEDE,
            s.NOMBRE as sede
        FROM horario h
        JOIN establecimiento e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE h.ID_HORARIO = :id_horario
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horario) {
        echo json_encode(['success' => false, 'message' => 'Horario no encontrado o sin permisos']);
        exit;
    }
    
    // Obtener días asignados
    $sqlDias = "
        SELECT ID_DIA 
        FROM HORARIO_DIA 
        WHERE ID_HORARIO = :id_horario 
        ORDER BY ID_DIA
    ";
    $stmtDias = $conn->prepare($sqlDias);
    $stmtDias->bindValue(':id_horario', $id_horario);
    $stmtDias->execute();
    $dias = $stmtDias->fetchAll(PDO::FETCH_COLUMN);
    $horario['dias'] = implode(',', $dias);
    
    // Consultar empleados asignados a este horario
    $sqlEmpleados = "
        SELECT 
            e.ID_EMPLEADO as codigo,
            e.DNI as dni,
            e.NOMBRE as nombre,
            e.APELLIDO as apellido,
            eh.FECHA_DESDE as fecha_desde,
            eh.FECHA_HASTA as fecha_hasta,
            est.NOMBRE as establecimiento
        FROM EMPLEADO_HORARIO eh
        JOIN EMPLEADO e ON eh.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE eh.ID_HORARIO = :id_horario
        AND s.ID_EMPRESA = :empresa_id
        ORDER BY eh.FECHA_DESDE DESC
    ";
    
    $stmt = $conn->prepare($sqlEmpleados);
    $stmt->bindValue(':id_horario', $id_horario);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar empleados al resultado
    $horario['empleados'] = $empleados;
    
    echo json_encode([
        'success' => true,
        'data' => $horario
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar detalles del horario: ' . $e->getMessage()
    ]);
}
?>