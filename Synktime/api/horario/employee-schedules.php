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
            e.NOMBRE, 
            e.APELLIDO, 
            e.DNI, 
            e.ID_ESTABLECIMIENTO,
            est.NOMBRE as establecimiento,
            s.ID_SEDE,
            s.NOMBRE as sede
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

    // Información del empleado para la respuesta
    $empleadoInfo = [
        'id_empleado' => $empleado['ID_EMPLEADO'],
        'nombre' => $empleado['NOMBRE'],
        'apellido' => $empleado['APELLIDO'],
        'dni' => $empleado['DNI'],
        'establecimiento' => $empleado['establecimiento'],
        'sede' => $empleado['sede'],
        'id_sede' => $empleado['ID_SEDE'],
        'id_establecimiento' => $empleado['ID_ESTABLECIMIENTO']
    ];

    // Consultar horarios asignados al empleado
    $sql = "
        SELECT 
            h.ID_HORARIO,
            h.NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            eh.FECHA_DESDE,
            eh.FECHA_HASTA
        FROM EMPLEADO_HORARIO eh
        JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE eh.ID_EMPLEADO = :id_empleado
        AND s.ID_EMPRESA = :empresa_id
        ORDER BY 
            CASE 
                WHEN eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= CURDATE() THEN 1
                ELSE 2
            END,
            eh.FECHA_DESDE DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':empresa_id', $empresaId);
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
        'empleado' => $empleadoInfo,
        'data' => $horarios
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar horarios del empleado: ' . $e->getMessage()
    ]);
}
?>