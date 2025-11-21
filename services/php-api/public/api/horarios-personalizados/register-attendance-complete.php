<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Configuración de base de datos directa (para testing)
// En producción, debería usar sesiones y auth
$host = 'localhost';
$dbname = 'synktime';
$username = 'root';
$password = '';

// Para producción, descomentar las siguientes líneas:
// require_once __DIR__ . '/../../auth/session.php';
// requireAuth();
// require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Usar la conexión PDO del archivo de configuración
    require_once __DIR__ . '/../../config/database.php';
    
    // Leer datos JSON del input
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput) {
        $input = json_decode($jsonInput, true);
    } else {
        $input = $_POST;
    }
    
    // Para testing, usar datos globales si están disponibles
    if (isset($GLOBALS['test_input'])) {
        $input = json_decode($GLOBALS['test_input'], true);
    }
    
    $idEmpleado = $input['id_empleado'] ?? null;
    $fecha = $input['fecha'] ?? date('Y-m-d');
    $hora = $input['hora'] ?? date('H:i:s');
    $tipo = strtoupper($input['tipo'] ?? 'ENTRADA');
    $metodo = $input['metodo'] ?? 'manual';
    $observaciones = $input['observaciones'] ?? '';
    $foto = $input['foto'] ?? null;
    
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
    
    // Obtener día de la semana (1=lunes, 7=domingo)
    $diaSemana = date('N', strtotime($fecha));
    
    // Buscar horarios personalizados primero
    $scheduleQuery = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.ORDEN_TURNO
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ? 
        AND ehp.ID_DIA = ? 
        AND ehp.ACTIVO = 'S'
        AND ehp.FECHA_DESDE <= ?
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        ORDER BY ehp.ORDEN_TURNO
    ";
    
    $stmt = $conn->prepare($scheduleQuery);
    $stmt->bind_param("iiss", $idEmpleado, $diaSemana, $fecha, $fecha);
    $stmt->execute();
    $scheduleResult = $stmt->get_result();
    
    $hasPersonalizedSchedule = false;
    $horarioInfo = null;
    $idHorario = null;
    
    if ($scheduleResult->num_rows > 0) {
        // Tiene horarios personalizados
        $hasPersonalizedSchedule = true;
        $horarios = $scheduleResult->fetch_all(MYSQLI_ASSOC);
        
        // Determinar qué horario usar según la hora actual
        $horaActual = date('H:i:s', strtotime($hora));
        
        // Buscar el horario más apropiado
        foreach ($horarios as $horario) {
            if ($tipo === 'ENTRADA') {
                // Para entrada, buscar el primer turno que aún no haya comenzado o esté en tolerancia
                $horaEntrada = $horario['HORA_ENTRADA'];
                $tolerancia = date('H:i:s', strtotime($horaEntrada . ' +30 minutes'));
                
                if ($horaActual <= $tolerancia) {
                    $horarioInfo = $horario;
                    break;
                }
            } else {
                // Para salida, buscar según la hora de salida
                $horaSalida = $horario['HORA_SALIDA'];
                $tolerancia = date('H:i:s', strtotime($horaSalida . ' -30 minutes'));
                
                if ($horaActual >= $tolerancia) {
                    $horarioInfo = $horario;
                    break;
                }
            }
        }
        
        // Si no se encontró uno específico, usar el primero
        if (!$horarioInfo) {
            $horarioInfo = $horarios[0];
        }
        
        // Para horarios personalizados, usar el ID_EMPLEADO_HORARIO como ID_HORARIO
        $idHorario = $horarioInfo['ID_EMPLEADO_HORARIO'];
        
        // Agregar información del horario a observaciones
        $observaciones .= " [Horario Personalizado: {$horarioInfo['NOMBRE_TURNO']} - Turno {$horarioInfo['ORDEN_TURNO']}]";
        
    } else {
        // Buscar horario tradicional
        $traditionalScheduleQuery = "
            SELECT 
                h.ID_HORARIO,
                h.NOMBRE,
                h.HORA_ENTRADA,
                h.HORA_SALIDA
            FROM empleado_horario eh
            JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE eh.ID_EMPLEADO = ?
            AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
        ";
        
        $stmt = $conn->prepare($traditionalScheduleQuery);
        $stmt->bind_param("is", $idEmpleado, $fecha);
        $stmt->execute();
        $traditionalResult = $stmt->get_result();
        
        if ($traditionalResult->num_rows > 0) {
            $horarioInfo = $traditionalResult->fetch_assoc();
            $idHorario = $horarioInfo['ID_HORARIO'];
            $observaciones .= " [Horario Tradicional: {$horarioInfo['NOMBRE']}]";
        }
    }
    
    // Verificar si ya existe un registro del mismo tipo para esta fecha
    $checkExistingQuery = "
        SELECT ID_ASISTENCIA, HORA 
        FROM asistencia 
        WHERE ID_EMPLEADO = ? 
        AND FECHA = ? 
        AND TIPO = ?
    ";
    
    $stmt = $conn->prepare($checkExistingQuery);
    $stmt->bind_param("iss", $idEmpleado, $fecha, $tipo);
    $stmt->execute();
    $existingResult = $stmt->get_result();
    
    if ($existingResult->num_rows > 0) {
        $existing = $existingResult->fetch_assoc();
        echo json_encode([
            'success' => false, 
            'message' => "Ya existe un registro de $tipo para esta fecha a las {$existing['HORA']}"
        ]);
        exit;
    }
    
    // Determinar tardanza
    $tardanza = 'N'; // Normal por defecto
    if ($horarioInfo && $tipo === 'ENTRADA') {
        $horaEsperada = $horarioInfo['HORA_ENTRADA'];
        $horaRegistro = $hora;
        
        if ($horaRegistro > $horaEsperada) {
            $diff = strtotime($horaRegistro) - strtotime($horaEsperada);
            if ($diff > 300) { // 5 minutos de tolerancia
                $tardanza = 'S';
            }
        }
    }
    
    // Procesar foto si se envió
    $fotoPath = null;
    if ($foto && strpos($foto, 'data:image') === 0) {
        // Crear directorio si no existe
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único para la foto
        $timestamp = date('Y-m-d_H-i-s');
        $fotoFileName = "attendance_{$idEmpleado}_{$timestamp}.jpg";
        $fotoPath = $uploadDir . $fotoFileName;
        
        // Decodificar y guardar la imagen
        $imageData = explode(',', $foto)[1];
        $decodedImage = base64_decode($imageData);
        
        if (file_put_contents($fotoPath, $decodedImage)) {
            $fotoPathRelativo = $fotoFileName; // Solo el nombre del archivo para BD
            $fotoPathCompleto = $fotoPath; // Ruta completa para respuesta
            $fotoPath = $fotoPathRelativo; // Usar solo nombre en BD
        } else {
            $fotoPath = null;
            $fotoPathCompleto = null;
        }
    }
    
    // Insertar en la tabla de asistencia
    $insertQuery = "
        INSERT INTO asistencia (
            ID_EMPLEADO, 
            FECHA, 
            HORA, 
            TIPO, 
            TARDANZA, 
            OBSERVACION, 
            VERIFICATION_METHOD, 
            REGISTRO_MANUAL, 
            ID_HORARIO,
            FOTO
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($insertQuery);
    
    $registroManual = ($metodo === 'manual') ? 'S' : 'N';
    $verificationMethod = $metodo; // facial, manual, biometrico, etc.
    
    $stmt->bind_param("isssssssss", 
        $idEmpleado, 
        $fecha, 
        $hora, 
        $tipo, 
        $tardanza,
        $observaciones, 
        $verificationMethod,
        $registroManual,
        $idHorario,
        $fotoPath
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
                'tardanza' => $tardanza,
                'metodo' => $verificationMethod,
                'horario_personalizado' => $hasPersonalizedSchedule,
                'horario_info' => $horarioInfo,
                'id_horario' => $idHorario,
                'observaciones' => $observaciones,
                'foto_guardada' => !is_null($fotoPath),
                'foto_path_relativo' => $fotoPath,
                'foto_path_completo' => $fotoPathCompleto ?? null
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