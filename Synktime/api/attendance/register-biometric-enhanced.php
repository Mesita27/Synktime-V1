<?php
/**
 * API Mejorada: Registro de Asistencia Biométrica 
 * Versión: 3.0 - Con validaciones completas y captura de fotos
 */

// Headers básicos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configuración
require_once __DIR__ . '/../../config/database.php';

// Incluir utilidades de justificaciones
require_once __DIR__ . '/../../utils/justificaciones_utils.php';

// Zona horaria
date_default_timezone_set('America/Bogota');

// Funciones auxiliares
function getBogotaDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

function getBogotaDate() {
    return date('Y-m-d');
}

function getBogotaTime() {
    return date('H:i');
}

function sendResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => getBogotaDateTime()
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Función para capturar y guardar foto usando el método simple de attendance.js
function captureAndSavePhoto($employee_id, $type, $photo_data) {
    try {
        // Crear directorio uploads si no existe
        $uploads_dir = __DIR__ . '/../../uploads/';
        if (!file_exists($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }

        // Generar nombre único para la foto
        $timestamp = getBogotaDateTime('Ymd_His');
        $filename = "biometric_{$type}_{$employee_id}_{$timestamp}.jpg";
        $filepath = $uploads_dir . $filename;

        // Verificar que tenemos datos de foto
        if (!$photo_data) {
            error_log("No se proporcionaron datos de foto para: $filename");
            return null;
        }

        // Limpiar el prefijo data:image si existe
        $image_data = preg_replace('#^data:image/\w+;base64,#i', '', $photo_data);
        $decoded = base64_decode($image_data);

        if ($decoded === false || empty($decoded)) {
            error_log("Error al decodificar datos base64 para foto: $filename");
            return null;
        }

        // Método simple: guardar directamente sin compresión (como attendance.js)
        $bytes_written = file_put_contents($filepath, $decoded);

        if ($bytes_written === false || $bytes_written === 0) {
            error_log("Error al guardar archivo de foto: $filename");
            return null;
        }

        error_log("Foto guardada exitosamente (método simple): $filename (" . $bytes_written . " bytes)");
        return $filename;

    } catch (Exception $e) {
        error_log("Error capturando foto: " . $e->getMessage());
        return null;
    }
}

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Método no permitido', null, 405);
    }
    
    // Obtener datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        sendResponse(false, 'Datos JSON inválidos', null, 400);
    }
    
    // Extraer parámetros principales
    $employee_id = $data['employee_id'] ?? null;
    $type = strtoupper($data['type'] ?? 'ENTRADA');
    $verification_results = $data['verification_results'] ?? [];
    $confidence_score = $verification_results['confidence_score'] ?? 0;
    $photo_data = $data['photo_data'] ?? null;
    
    // Validaciones básicas
    if (!$employee_id) {
        sendResponse(false, 'ID de empleado requerido', null, 400);
    }
    
    if (!in_array($type, ['ENTRADA', 'SALIDA'])) {
        sendResponse(false, 'Tipo inválido', null, 400);
    }
    
    // Verificar empleado
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, CONCAT(NOMBRE, ' ', APELLIDO) as NOMBRE_COMPLETO FROM empleado WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'");
    $stmt->execute([$employee_id]);
    $result = $stmt->fetchAll();
    
    if (count($result) === 0) {
        sendResponse(false, 'Empleado no encontrado o inactivo', null, 404);
    }
    
    $employee = $result[0];
    
    // Preparar fechas y datos
    $fecha = getBogotaDate();
    $hora_actual = getBogotaTime();
    $dia_semana = date('N'); // 1=Lunes, 7=Domingo
    
    // Buscar horarios personalizados del empleado para el día actual
    $sqlPersonalizados = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.ORDEN_TURNO,
            ehp.ES_TURNO_NOCTURNO,
            ehp.HORA_CORTE_NOCTURNO,
            ehp.NOMBRE_TURNO,
            'personalizado' as TIPO_HORARIO
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ?
        AND ehp.ID_DIA = ?
        AND ehp.FECHA_DESDE <= ?
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        AND ehp.ACTIVO = 'S'
        ORDER BY ehp.ORDEN_TURNO
    ";
    
    $stmt = $conn->prepare($sqlPersonalizados);
    $stmt->execute([$employee_id, $dia_semana, $fecha, $fecha]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($horarios)) {
        sendResponse(false, 'No se encontraron horarios asignados para el empleado en este día', null, 422);
    }

    // **NUEVA FUNCIONALIDAD: Filtrar turnos justificados**
    $resultadoFiltrado = filtrarHorariosPorJustificaciones($employee_id, $fecha, $horarios, $conn);
    $horarios = $resultadoFiltrado['horarios_disponibles'];
    $turnosJustificados = $resultadoFiltrado['turnos_justificados'];
    $todosJustificados = $resultadoFiltrado['todos_justificados'];

    if (empty($horarios)) {
        if ($todosJustificados) {
            sendResponse(false, 'Todos los turnos para hoy están justificados. No se requiere registro de asistencia.', null, 422);
        } else {
            sendResponse(false, 'No hay turnos disponibles para registro de asistencia.', null, 422);
        }
    }

    // Verificar registros existentes del día (excluyendo turnos justificados)
    $sqlVerificar = "
        SELECT 
            a.TIPO, 
            a.HORA, 
            a.ID_EMPLEADO_HORARIO,
            a.CREATED_AT
        FROM asistencia a 
        JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE a.ID_EMPLEADO = ? 
        AND a.FECHA = ?
        AND ehp.ID_EMPLEADO_HORARIO NOT IN (
            SELECT turno_id FROM justificaciones
            WHERE empleado_id = ?
            AND fecha_falta = ?
            AND (justificar_todos_turnos = 1 OR turno_id IS NOT NULL)
        )
        ORDER BY a.CREATED_AT DESC
    ";
    
    $stmt = $conn->prepare($sqlVerificar);
    $stmt->execute([$employee_id, $fecha, $employee_id, $fecha]);
    $registrosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    // Validaciones según el tipo de registro - LÓGICA MEJORADA PARA MÚLTIPLES TURNOS
    if ($type === 'ENTRADA') {
        // Contabilizar entradas y salidas del día (excluyendo turnos justificados)
        $totalEntradas = count(array_filter($registrosExistentes, function($reg) {
            return $reg['TIPO'] === 'ENTRADA';
        }));

        $totalSalidas = count(array_filter($registrosExistentes, function($reg) {
            return $reg['TIPO'] === 'SALIDA';
        }));

        if ($totalEntradas > $totalSalidas) {
            sendResponse(false, 'Existe una entrada previa sin salida registrada. Debe registrar la salida antes de una nueva entrada.', [
                'entradas_registradas' => $totalEntradas,
                'salidas_registradas' => $totalSalidas
            ], 422);
        }

        $turnosDisponibles = count($horarios);

        if ($totalEntradas >= $turnosDisponibles) {
            sendResponse(false, 'Ya se han registrado entradas para todos los turnos disponibles hoy (' . $totalEntradas . ' de ' . $turnosDisponibles . ' turnos).', [
                'entradas_registradas' => $totalEntradas,
                'turnos_disponibles' => $turnosDisponibles,
                'turnos_justificados' => $turnosJustificados
            ], 422);
        }
        // Si no se ha alcanzado el límite y no hay entradas abiertas, permitir la nueva entrada
    } elseif ($type === 'SALIDA') {
        // Para salida: verificar que haya al menos una entrada sin salida correspondiente
        $totalEntradas = count(array_filter($registrosExistentes, function($reg) {
            return $reg['TIPO'] === 'ENTRADA';
        }));

        $totalSalidas = count(array_filter($registrosExistentes, function($reg) {
            return $reg['TIPO'] === 'SALIDA';
        }));

        if ($totalEntradas <= $totalSalidas) {
            sendResponse(false, 'No hay entradas pendientes de salida. Debe registrar primero una entrada.', [
                'entradas_registradas' => $totalEntradas,
                'salidas_registradas' => $totalSalidas
            ], 422);
        }
        // Si hay más entradas que salidas, permitir la salida
    }
    
    // Validar límite de turnos
    $registrosDelDia = count($registrosExistentes);
    $maxTurnos = count($horarios) * 2; // Cada turno tiene entrada y salida
    
    if ($registrosDelDia >= $maxTurnos) {
        sendResponse(false, 'Se ha alcanzado el número máximo de registros para los turnos asignados hoy.', [
            'registros_actuales' => $registrosDelDia,
            'maximo_permitido' => $maxTurnos,
            'turnos_asignados' => count($horarios)
        ], 422);
    }
    
    // Determinar el horario correspondiente (el siguiente disponible)
    $horarioSeleccionado = null;
    $id_empleado_horario = null;
    
    foreach ($horarios as $horario) {
        // Contar registros para este horario específico
        $registrosHorario = array_filter($registrosExistentes, function($reg) use ($horario) {
            return $reg['ID_EMPLEADO_HORARIO'] == $horario['ID_EMPLEADO_HORARIO'];
        });
        
        $tieneEntrada = false;
        $tieneSalida = false;
        
        foreach ($registrosHorario as $registro) {
            if ($registro['TIPO'] === 'ENTRADA') $tieneEntrada = true;
            if ($registro['TIPO'] === 'SALIDA') $tieneSalida = true;
        }
        
        // Determinar si este horario necesita el tipo de registro solicitado
        if ($type === 'ENTRADA' && !$tieneEntrada) {
            $horarioSeleccionado = $horario;
            $id_empleado_horario = $horario['ID_EMPLEADO_HORARIO'];
            break;
        } elseif ($type === 'SALIDA' && $tieneEntrada && !$tieneSalida) {
            $horarioSeleccionado = $horario;
            $id_empleado_horario = $horario['ID_EMPLEADO_HORARIO'];
            break;
        }
    }
    
    if (!$horarioSeleccionado) {
        sendResponse(false, "No se encontró un horario válido para registrar {$type} en este momento.", [
            'tipo_solicitado' => $type,
            'registros_existentes' => $registrosExistentes
        ], 422);
    }
    
    // Capturar foto de evidencia
    $filename = null;
    if ($type === 'ENTRADA' && $photo_data) {
        $filename = captureAndSavePhoto($employee_id, $type, $photo_data);
    }
    
    // Preparar datos para inserción
    $created_at = getBogotaDateTime();
    $verification_method = 'facial';
    
    // NO incluir observaciones para registro biométrico (según solicitud)
    $observacion = null;
    
    // Insertar registro de asistencia
    $sql = "INSERT INTO asistencia (
        ID_EMPLEADO, 
        FECHA, 
        HORA, 
        TIPO, 
        VERIFICATION_METHOD, 
        OBSERVACION,
        FOTO,
        CREATED_AT,
        REGISTRO_MANUAL,
        TIPO_HORARIO,
        ID_EMPLEADO_HORARIO
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'N', 'personalizado', ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([
        $employee_id,
        $fecha,
        $hora_actual,
        $type,
        $verification_method,
        $observacion,
        $filename,
        $created_at,
        $id_empleado_horario
    ])) {
        $attendance_id = $conn->lastInsertId();
        
        // Preparar respuesta exitosa
        $response_data = [
            'attendance_id' => $attendance_id,
            'employee_id' => $employee_id,
            'employee_name' => $employee['NOMBRE_COMPLETO'],
            'type' => $type,
            'date' => $fecha,
            'time' => $hora_actual,
            'verification_method' => $verification_method,
            'confidence_score' => $confidence_score,
            'created_at' => $created_at,
            'horario_info' => [
                'id_empleado_horario' => $id_empleado_horario,
                'nombre_turno' => $horarioSeleccionado['NOMBRE_TURNO'],
                'orden_turno' => $horarioSeleccionado['ORDEN_TURNO'],
                'es_turno_nocturno' => $horarioSeleccionado['ES_TURNO_NOCTURNO']
            ]
        ];
        
        // Incluir información de foto si se capturó
        if ($filename) {
            $response_data['photo'] = [
                'filename' => $filename,
                'url' => 'uploads/' . $filename
            ];
        }
        
        sendResponse(true, 'Asistencia registrada exitosamente', $response_data, 201);
        
    } else {
        throw new Exception('Error al insertar registro en base de datos');
    }
    
} catch (Exception $e) {
    error_log("Error en API Biometric Register Enhanced: " . $e->getMessage());
    sendResponse(false, 'Error interno del servidor: ' . $e->getMessage(), null, 500);
}
?>