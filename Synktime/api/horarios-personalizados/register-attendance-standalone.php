<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Configuración de base de datos directa
$host = 'localhost';
$dbname = 'synktime';
$username = 'root';
$password = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Crear conexión directa
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
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
    
    // Verificar si el empleado existe
    $checkEmployeeQuery = "SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ?";
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
    $scheduleQuery = "SELECT ID_EMPLEADO_HORARIO FROM empleado_horario_personalizado WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'";
    $stmt = $conn->prepare($scheduleQuery);
    $stmt->bind_param("i", $idEmpleado);
    $stmt->execute();
    $scheduleResult = $stmt->get_result();
    
    $hasPersonalizedSchedule = $scheduleResult->num_rows > 0;
    $personalizedScheduleId = $hasPersonalizedSchedule ? $scheduleResult->fetch_assoc()['ID_EMPLEADO_HORARIO'] : null;
    
    // Preparar observaciones con información del horario
    if ($hasPersonalizedSchedule) {
        $observaciones .= " [Horario Personalizado ID: $personalizedScheduleId]";
    }
    
    // Insertar en la tabla de asistencia
    $insertQuery = "INSERT INTO asistencia (ID_EMPLEADO, FECHA, HORA, TIPO, TARDANZA, OBSERVACION, VERIFICATION_METHOD, REGISTRO_MANUAL, ID_HORARIO) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    
    // Para horarios personalizados, usamos NULL en ID_HORARIO
    $idHorario = null; // Siempre NULL para esta prueba
    $tardanza = 'N'; // Normal por defecto
    $registroManual = 'S'; // Siempre manual en esta prueba
    
    $stmt->bind_param("isssssssi", 
        $idEmpleado, 
        $fecha, 
        $hora, 
        $tipo, 
        $tardanza,
        $observaciones, 
        $metodo,
        $registroManual,
        $idHorario
    );
    
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Asistencia registrada exitosamente',
            'data' => [
                'id' => $insertId,
                'empleado' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
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
    
    $conn->close();
    
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