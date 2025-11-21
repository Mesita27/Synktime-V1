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
    if (!$data || !isset($data['id_empleado']) || !isset($data['horarios']) || !isset($data['fecha_desde'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $id_empleado = intval($data['id_empleado']);
    $horarios = $data['horarios'];
    $fecha_desde = $data['fecha_desde'];
    $fecha_hasta = !empty($data['fecha_hasta']) ? $data['fecha_hasta'] : null;
    
    // Validar datos
    if (empty($horarios) || !is_array($horarios)) {
        echo json_encode(['success' => false, 'message' => 'No se seleccionaron horarios']);
        exit;
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha desde inválido']);
        exit;
    }
    
    if ($fecha_hasta && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha hasta inválido']);
        exit;
    }
    
    // Verificar que el empleado pertenece a la empresa
    $sqlVerifyEmpleado = "
        SELECT e.ID_EMPLEADO
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerifyEmpleado);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }
    
    // Verificar que los horarios pertenecen a la empresa
    // Usar parámetros con nombre en lugar de posicionales
    $placeholders = [];
    $verifyParams = [':empresa_id' => $empresaId];
    
    foreach ($horarios as $index => $id) {
        $paramName = ':horario_' . $index;
        $placeholders[] = $paramName;
        $verifyParams[$paramName] = $id;
    }
    
    $sqlVerifyHorarios = "
        SELECT COUNT(*) as count, COUNT(DISTINCT h.ID_HORARIO) as valid_count
        FROM HORARIO h
        JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE h.ID_HORARIO IN (" . implode(',', $placeholders) . ")
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerifyHorarios);
    foreach ($verifyParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['valid_count'] != count($horarios)) {
        echo json_encode(['success' => false, 'message' => 'Algunos horarios no son válidos o no tiene permisos']);
        exit;
    }
    
    $conn->beginTransaction();
    
    // Insertar cada horario seleccionado
    $insertCount = 0;
    foreach ($horarios as $horario_id) {
        try {
            // Primero verificar si ya existe la asignación para evitar duplicados
            $checkSql = "
                SELECT COUNT(*) 
                FROM EMPLEADO_HORARIO 
                WHERE ID_EMPLEADO = :id_empleado 
                AND ID_HORARIO = :id_horario 
                AND FECHA_DESDE = :fecha_desde
            ";
            
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bindValue(':id_empleado', $id_empleado);
            $checkStmt->bindValue(':id_horario', $horario_id);
            $checkStmt->bindValue(':fecha_desde', $fecha_desde);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                // Ya existe, actualizar fecha_hasta
                $updateSql = "
                    UPDATE EMPLEADO_HORARIO 
                    SET FECHA_HASTA = :fecha_hasta
                    WHERE ID_EMPLEADO = :id_empleado 
                    AND ID_HORARIO = :id_horario 
                    AND FECHA_DESDE = :fecha_desde
                ";
                
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindValue(':id_empleado', $id_empleado);
                $updateStmt->bindValue(':id_horario', $horario_id);
                $updateStmt->bindValue(':fecha_desde', $fecha_desde);
                $updateStmt->bindValue(':fecha_hasta', $fecha_hasta, $fecha_hasta ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateStmt->execute();
            } else {
                // No existe, insertar nuevo
                $insertSql = "
                    INSERT INTO EMPLEADO_HORARIO (ID_EMPLEADO, ID_HORARIO, FECHA_DESDE, FECHA_HASTA)
                    VALUES (:id_empleado, :id_horario, :fecha_desde, :fecha_hasta)
                ";
                
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bindValue(':id_empleado', $id_empleado);
                $insertStmt->bindValue(':id_horario', $horario_id);
                $insertStmt->bindValue(':fecha_desde', $fecha_desde);
                $insertStmt->bindValue(':fecha_hasta', $fecha_hasta, $fecha_hasta ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $insertStmt->execute();
            }
            
            $insertCount++;
        } catch (PDOException $e) {
            // Si ocurre un error, registrar y continuar
            error_log('Error al asignar horario ' . $horario_id . ' a empleado ' . $id_empleado . ': ' . $e->getMessage());
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Horarios asignados correctamente',
        'count' => $insertCount
    ]);
    
} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error al asignar horarios: ' . $e->getMessage()
    ]);
}
?>