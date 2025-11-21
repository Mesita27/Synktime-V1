<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración de base de datos desde config
require_once __DIR__ . '/../../config/database.php';

// Verificar método
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
    $stmt->execute([$idEmpleado]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
        exit;
    }
    
    // Verificar duplicados
    $checkDuplicateQuery = "
        SELECT COUNT(*) as count 
        FROM asistencia 
        WHERE ID_EMPLEADO = ? AND FECHA = ? AND TIPO = ?
    ";
    
    $stmt = $conn->prepare($checkDuplicateQuery);
    $stmt->execute([$idEmpleado, $fecha, $tipo]);
    $duplicateResult = $stmt->fetch();
    
    if ($duplicateResult['count'] > 0) {
        $horaExistente = date('H:i', strtotime($hora));
        echo json_encode([
            'success' => false, 
            'message' => "Ya existe un registro de $tipo para esta fecha a las $horaExistente"
        ]);
        exit;
    }
    
    // Buscar horarios en empleado_horario
    $scheduleQuery = "
        SELECT 
            eh.ID_EMPLEADO,
            eh.ID_HORARIO,
            h.NOMBRE as NOMBRE_HORARIO,
            h.HORA_ENTRADA,
            h.HORA_SALIDA
        FROM empleado_horario eh
        JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = ? 
        AND eh.FECHA_DESDE <= ?
        AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
    ";
    
    $stmt = $conn->prepare($scheduleQuery);
    $stmt->execute([$idEmpleado, $fecha, $fecha]);
    $scheduleResult = $stmt->fetchAll();
    
    $hasPersonalizedSchedule = false;
    $horarioInfo = null;
    $idHorario = null;
    
    if (count($scheduleResult) > 0) {
        // Tiene horarios personalizados en empleado_horario
        $hasPersonalizedSchedule = true;
        $horarios = $scheduleResult;
        
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
        
        // Para empleado_horario, usar directamente el ID_HORARIO de la tabla horario
        $idHorario = $horarioInfo['ID_HORARIO'];
        
        // Agregar información del horario a observaciones
        $observaciones .= " [Horario Personalizado: {$horarioInfo['NOMBRE_HORARIO']}]";
    } else {
        // No tiene horarios personalizados, buscar horario por defecto
        // En este caso, podemos usar un horario por defecto o null
        $hasPersonalizedSchedule = false;
        
        // Si no hay horarios definidos, podemos usar null o un horario por defecto
        // Por ahora usaremos null para permitir flexibilidad
        $idHorario = null;
        $horarioInfo = ['mensaje' => 'Sin horario específico asignado'];
        $observaciones .= " [Sin horario específico]";
    }
    
    // Calcular tardanza (simplificado)
    $tardanza = 'N'; // Por defecto no hay tardanza
    if ($hasPersonalizedSchedule && $horarioInfo && isset($horarioInfo['HORA_ENTRADA'])) {
        $horaEntradaEsperada = $horarioInfo['HORA_ENTRADA'];
        $horaActual = date('H:i:s', strtotime($hora));
        
        if ($tipo === 'ENTRADA' && $horaActual > $horaEntradaEsperada) {
            $tardanza = 'S';
        }
    }
    
    // Procesar foto si se envió
    $fotoPath = null;
    $fotoPathCompleto = null;
    if ($foto && strpos($foto, 'data:image') === 0) {
        // Crear directorio si no existe
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único para la foto
        $timestamp = date('Y-m-d_H-i-s');
        $fotoFileName = "attendance_{$idEmpleado}_{$timestamp}.jpg";
        $fullPath = $uploadDir . $fotoFileName;
        
        // Decodificar y guardar la imagen
        $imageData = explode(',', $foto)[1];
        $decodedImage = base64_decode($imageData);
        
        if (file_put_contents($fullPath, $decodedImage)) {
            $fotoPath = $fotoFileName; // Solo el nombre del archivo para BD
            $fotoPathCompleto = $fullPath; // Ruta completa para respuesta
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
    
    $result = $stmt->execute([
        $idEmpleado, 
        $fecha, 
        $hora, 
        $tipo, 
        $tardanza,
        $observaciones, 
        $verificationMethod,
        $registroManual,
        $idHorario, // Puede ser null si no hay horario específico
        $fotoPath
    ]);
    
    if ($result) {
        $insertId = $conn->lastInsertId();
        
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
                'foto_path_completo' => $fotoPathCompleto
            ]
        ]);
    } else {
        throw new Exception('Error al insertar en la base de datos');
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
    error_log("Error en register-attendance: " . $e->getMessage());
}
?>