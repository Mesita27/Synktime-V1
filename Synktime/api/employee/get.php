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

    // Verificar que se proporcionó un ID de empleado
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado no proporcionado']);
        exit;
    }

    $id_empleado = intval($_GET['id']);

    // Consultar datos del empleado
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            e.CORREO,
            e.TELEFONO,
            e.FECHA_INGRESO,
            e.ESTADO,
            e.ACTIVO,
            est.NOMBRE AS ESTABLECIMIENTO,
            est.ID_ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            s.ID_SEDE
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    // Consultar horarios asignados al empleado
    $sqlHorarios = "
        SELECT 
            eh.ID_HORARIO,
            h.NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            eh.FECHA_DESDE,
            eh.FECHA_HASTA
        FROM empleado_horario eh
        JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = :id_empleado
        ORDER BY eh.FECHA_DESDE DESC
    ";
    
    $stmtHorarios = $conn->prepare($sqlHorarios);
    $stmtHorarios->bindValue(':id_empleado', $id_empleado);
    $stmtHorarios->execute();
    
    $horarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultar horarios personalizados del empleado
    $sqlHorariosPersonalizados = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.ID_DIA,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA,
            ehp.ACTIVO,
            ehp.ORDEN_TURNO,
            ehp.OBSERVACIONES,
            ds.NOMBRE as DIA_NOMBRE
        FROM empleado_horario_personalizado ehp
        LEFT JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        WHERE ehp.ID_EMPLEADO = :id_empleado
        AND ehp.ACTIVO = 'S'
        ORDER BY ehp.ID_DIA, ehp.ORDEN_TURNO
    ";
    
    $stmtHorariosPersonalizados = $conn->prepare($sqlHorariosPersonalizados);
    $stmtHorariosPersonalizados->bindValue(':id_empleado', $id_empleado);
    $stmtHorariosPersonalizados->execute();
    
    $horariosPersonalizados = $stmtHorariosPersonalizados->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear la respuesta
    $empleado['HORARIOS'] = $horarios;
    $empleado['HORARIOS_PERSONALIZADOS'] = $horariosPersonalizados;
    
    // Traducir estado según el valor almacenado
    switch ($empleado['ESTADO']) {
        case 'A':
            $empleado['ESTADO_TEXTO'] = 'Activo';
            break;
        case 'I':
            $empleado['ESTADO_TEXTO'] = 'Inactivo';
            break;
        default:
            $empleado['ESTADO_TEXTO'] = 'Desconocido';
    }
    
    // Devolver datos del empleado con nombres de campo compatibles
    $response = [
        'ID_EMPLEADO' => $empleado['ID_EMPLEADO'],
        'DNI' => $empleado['DNI'],
        'NOMBRE' => $empleado['NOMBRE'],
        'APELLIDO' => $empleado['APELLIDO'],
        'CORREO' => $empleado['CORREO'],
        'TELEFONO' => $empleado['TELEFONO'],
        'FECHA_INGRESO' => $empleado['FECHA_INGRESO'],
        'ESTADO' => $empleado['ESTADO'],
        'ACTIVO' => $empleado['ACTIVO'],
        'ESTABLECIMIENTO' => $empleado['ESTABLECIMIENTO'],
        'ID_ESTABLECIMIENTO' => $empleado['ID_ESTABLECIMIENTO'],
        'SEDE' => $empleado['SEDE'],
        'ID_SEDE' => $empleado['ID_SEDE'],
        'HORARIOS' => $horarios,
        'HORARIOS_PERSONALIZADOS' => $horariosPersonalizados,
        'ESTADO_TEXTO' => $empleado['ESTADO_TEXTO']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar datos del empleado: ' . $e->getMessage()
    ]);
}
?>