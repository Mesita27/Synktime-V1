<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Incluir función getConnection
require_once __DIR__ . '/../../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Leer datos JSON del input
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput) {
        $input = json_decode($jsonInput, true);
    } else {
        $input = $_POST;
    }
    
    $idEmpleado = $input['id_empleado'] ?? null;
    $fecha = $input['fecha'] ?? date('Y-m-d');
    $hora = $input['hora'] ?? date('H:i:s');
    $tipo = $input['tipo'] ?? 'ENTRADA';
    $metodo = $input['metodo'] ?? 'manual';
    $observaciones = $input['observaciones'] ?? '';
    
    if (!$idEmpleado) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit;
    }
    
    $conn = getConnection();
    
    // Verificar si el empleado existe
    $checkEmployeeQuery = "SELECT id, nombre, apellido FROM empleados WHERE id = ?";
    $stmt = $conn->prepare($checkEmployeeQuery);
    $stmt->bind_param("i", $idEmpleado);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    
    if ($employeeResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
        exit;
    }
    
    $employee = $employeeResult->fetch_assoc();
    
    // Verificar si hay horario personalizado para este empleado
    $scheduleQuery = "SELECT id FROM empleado_horario_personalizado WHERE id_empleado = ? AND activo = 1";
    $stmt = $conn->prepare($scheduleQuery);
    $stmt->bind_param("i", $idEmpleado);
    $stmt->execute();
    $scheduleResult = $stmt->get_result();
    
    $hasPersonalizedSchedule = $scheduleResult->num_rows > 0;
    $personalizedScheduleId = $hasPersonalizedSchedule ? $scheduleResult->fetch_assoc()['id'] : null;
    
    // Preparar observaciones con información del horario
    if ($hasPersonalizedSchedule) {
        $observaciones .= " [Horario Personalizado ID: $personalizedScheduleId]";
    }
    
    // Insertar en la tabla de asistencia
    $insertQuery = "INSERT INTO asistencia (ID_EMPLEADO, FECHA, HORA, TIPO, METODO, OBSERVACION, ID_HORARIO, ID_EMPLEADO_HORARIO) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    
    // Para horarios personalizados, usamos NULL en ID_HORARIO y el ID del horario personalizado
    $idHorario = $hasPersonalizedSchedule ? null : null;
    $idEmpleadoHorario = $hasPersonalizedSchedule ? $personalizedScheduleId : null;
    
    $stmt->bind_param("isssssii", 
        $idEmpleado, 
        $fecha, 
        $hora, 
        $tipo, 
        $metodo, 
        $observaciones, 
        $idHorario, 
        $idEmpleadoHorario
    );
    
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Asistencia registrada exitosamente',
            'data' => [
                'id' => $insertId,
                'empleado' => $employee['nombre'] . ' ' . $employee['apellido'],
                'fecha' => $fecha,
                'hora' => $hora,
                'tipo' => $tipo,
                'metodo' => $metodo,
                'horario_personalizado' => $hasPersonalizedSchedule,
                'observaciones' => $observaciones
            ]
        ]);
    } else {
        throw new Exception('Error al insertar en la base de datos: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'input' => $input ?? 'No input received',
            'employee_id' => $idEmpleado ?? 'Not set'
        ]
    ]);
}
?>