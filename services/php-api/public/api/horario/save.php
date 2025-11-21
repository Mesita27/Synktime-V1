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
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Validar datos requeridos
    if (empty($data['nombre']) || empty($data['establecimiento']) || 
        empty($data['hora_entrada']) || empty($data['hora_salida']) || 
        !isset($data['tolerancia']) || 
        empty($data['dias']) || !is_array($data['dias'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit;
    }
    
    // Verificar que el establecimiento pertenece a la empresa del usuario
    $sqlVerifyEstablecimiento = "
        SELECT e.ID_ESTABLECIMIENTO
        FROM ESTABLECIMIENTO e
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.ID_ESTABLECIMIENTO = :establecimiento
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sqlVerifyEstablecimiento);
    $stmt->bindValue(':establecimiento', $data['establecimiento']);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para este establecimiento']);
        exit;
    }

    $conn->beginTransaction();
    
    // Determinar si es inserción o actualización
    $isUpdate = !empty($data['id_horario']);
    
    if ($isUpdate) {
        // Verificar que el horario pertenece a la empresa
        $sqlVerifyHorario = "
            SELECT h.ID_HORARIO
            FROM horario h
            JOIN establecimiento e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
            JOIN sede s ON e.ID_SEDE = s.ID_SEDE
            WHERE h.ID_HORARIO = :id_horario
            AND s.ID_EMPRESA = :empresa_id
        ";
        
        $stmt = $conn->prepare($sqlVerifyHorario);
        $stmt->bindValue(':id_horario', $data['id_horario']);
        $stmt->bindValue(':empresa_id', $empresaId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos para modificar este horario']);
            exit;
        }
        
        // Actualizar horario existente
        $sql = "
            UPDATE HORARIO 
            SET NOMBRE = :nombre,
                ID_ESTABLECIMIENTO = :establecimiento,
                HORA_ENTRADA = :hora_entrada,
                HORA_SALIDA = :hora_salida,
                TOLERANCIA = :tolerancia
            WHERE ID_HORARIO = :id_horario
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':establecimiento', $data['establecimiento']);
        $stmt->bindValue(':hora_entrada', $data['hora_entrada']);
        $stmt->bindValue(':hora_salida', $data['hora_salida']);
        $stmt->bindValue(':tolerancia', $data['tolerancia']);
        $stmt->bindValue(':id_horario', $data['id_horario']);
        $stmt->execute();
        
        // Eliminar días anteriores
        $sqlDeleteDias = "DELETE FROM HORARIO_DIA WHERE ID_HORARIO = :id_horario";
        $stmt = $conn->prepare($sqlDeleteDias);
        $stmt->bindValue(':id_horario', $data['id_horario']);
        $stmt->execute();
        
        $horario_id = $data['id_horario'];
    } else {
        // Insertar nuevo horario
        $sql = "
            INSERT INTO HORARIO (NOMBRE, ID_ESTABLECIMIENTO, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA)
            VALUES (:nombre, :establecimiento, :hora_entrada, :hora_salida, :tolerancia)
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':establecimiento', $data['establecimiento']);
        $stmt->bindValue(':hora_entrada', $data['hora_entrada']);
        $stmt->bindValue(':hora_salida', $data['hora_salida']);
        $stmt->bindValue(':tolerancia', $data['tolerancia']);
        $stmt->execute();
        
        $horario_id = $conn->lastInsertId();
    }
    
    // Insertar días de la semana
    $sqlInsertDia = "INSERT INTO HORARIO_DIA (ID_HORARIO, ID_DIA) VALUES (:id_horario, :id_dia)";
    $stmt = $conn->prepare($sqlInsertDia);
    
    foreach ($data['dias'] as $dia) {
        $stmt->bindValue(':id_horario', $horario_id);
        $stmt->bindValue(':id_dia', $dia);
        $stmt->execute();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'Horario actualizado correctamente' : 'Horario creado correctamente',
        'id_horario' => $horario_id
    ]);
    
} catch (PDOException $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>
