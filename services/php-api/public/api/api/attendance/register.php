<?php
// Limpiar cualquier output anterior
ob_clean();

// Headers para JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Configurar zona horaria de Bogotá, Colombia ANTES que cualquier cosa
require_once __DIR__ . '/../../config/timezone.php';

require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/attendance_verification.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

// Incluir utilidades de justificaciones
require_once __DIR__ . '/../../utils/justificaciones_utils.php';

// Verificar que existe el directorio uploads
$uploads_dir = __DIR__ . '/../../uploads/';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Verificar que existe directorio de logs
$logs_dir = __DIR__ . '/../../logs/';
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}
$debug_log_file = $logs_dir . 'attendance_register_debug.log';

// Recoge los datos POST usando zona horaria de Bogotá
$id_empleado = $_POST['id_empleado'] ?? null;
$fecha = getBogotaDate(); // Fecha actual en zona horaria de Bogotá
$verification_method_original = $_POST['verification_method'] ?? 'traditional';
$verification_method = normalizeVerificationMethod($verification_method_original);
$confidence_score = $_POST['confidence'] ?? $_POST['confidence_score'] ?? null;
$photo_timestamp = $_POST['photo_timestamp'] ?? getBogotaDateTime();

// Procesar la imagen desde verification_photo, image_data, o información de Python
$foto_base64 = $_POST['verification_photo'] ?? $_POST['image_data'] ?? null;
$python_photo_info = $_POST['python_photo_info'] ?? null;

// Debug logging
error_log("=== REGISTRO DE ASISTENCIA DEBUG ===");
error_log("ID Empleado: " . $id_empleado);
error_log("Método de verificación (original): " . $verification_method_original);
error_log("Método de verificación (normalizado): " . $verification_method);
error_log("Confianza: " . $confidence_score);
error_log("Foto timestamp: " . $photo_timestamp);
error_log("Foto base64 presente: " . (!empty($foto_base64) ? 'SI' : 'NO'));
error_log("Foto Python presente: " . (!empty($python_photo_info) ? 'SI' : 'NO'));
error_log("===================================");

// Validación de datos obligatorios
if (!$id_empleado) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
    exit;
}

// Validar método de verificación
$valid_methods = ['traditional', 'fingerprint', 'facial'];
if (!in_array($verification_method, $valid_methods)) {
    $verification_method = 'traditional';
}

// Procesar la imagen si existe o generar placeholder para huella
$foto_base64 = $_POST['verification_photo'] ?? $_POST['image_data'] ?? null;
$python_photo_info = $_POST['python_photo_info'] ?? null;
$filename = null;

if ($python_photo_info) {
    // Usar foto ya capturada por Python
    error_log("Procesando foto capturada por Python");
    $photo_data = json_decode($python_photo_info, true);

    if ($photo_data && isset($photo_data['filename'])) {
        $filename = $photo_data['filename'];

        // Verificar que el archivo existe
        $expected_path = $uploads_dir . '/' . $filename;
        if (!file_exists($expected_path)) {
            error_log("ERROR: Archivo de foto Python no encontrado: " . $expected_path);
            echo json_encode(['success' => false, 'message' => 'Foto capturada no encontrada.']);
            exit;
        }

        error_log("Foto Python encontrada: " . $expected_path);
        error_log("Tamaño del archivo: " . filesize($expected_path) . " bytes");
    } else {
        error_log("ERROR: Información de foto Python inválida");
        echo json_encode(['success' => false, 'message' => 'Información de foto inválida.']);
        exit;
    }
} elseif ($verification_method === 'fingerprint') {
    // Para huella dactilar, generar imagen placeholder
    require_once __DIR__ . '/../biometric/placeholder.php';
    $placeholder_data = getBiometricPlaceholder('fingerprint', $id_empleado, $_POST['finger_type'] ?? 'index_right');
    
    // Limpiar el base64 y guardar
    $placeholder_clean = preg_replace('#^data:image/\w+;base64,#i', '', $placeholder_data);
    $img_data = base64_decode($placeholder_clean);
    
    if ($img_data !== false) {
        $filename = uniqid('fingerprint_') . '_' . getBogotaDateTime('Ymd_His') . '.png';
        $save_path = $uploads_dir . $filename;
        file_put_contents($save_path, $img_data);
    }
} elseif ($foto_base64) {
    // Para facial y traditional, procesar imagen capturada
    error_log("Procesando imagen para método: " . $verification_method);
    error_log("Foto base64 length: " . strlen($foto_base64));
    
    $foto_base64_clean = preg_replace('#^data:image/\w+;base64,#i', '', $foto_base64);
    $img_data = base64_decode($foto_base64_clean);
    
    if ($img_data === false) {
        error_log("ERROR: Falló decodificación base64");
        echo json_encode(['success' => false, 'message' => 'Formato de imagen inválido.']);
        exit;
    }
    
    // Generar nombre de archivo único según método
    $prefix = $verification_method === 'facial' ? 'facial_' : 'att_';
    $filename = uniqid($prefix) . '_' . getBogotaDateTime('Ymd_His') . '.jpg';
    $save_path = $uploads_dir . $filename;
    
    error_log("Generando archivo con prefijo: " . $prefix);
    error_log("Nombre de archivo: " . $filename);
    error_log("Ruta de guardado: " . $save_path);
    error_log("Directorio uploads existe: " . (is_dir($uploads_dir) ? 'SI' : 'NO'));
    error_log("Directorio uploads es writable: " . (is_writable($uploads_dir) ? 'SI' : 'NO'));
    
    if (file_put_contents($save_path, $img_data) === false) {
        error_log("ERROR: No se pudo guardar la foto en: " . $save_path);
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto. Verificar permisos.']);
        exit;
    }
    
    error_log("Foto guardada exitosamente en: " . $save_path);
    error_log("Tamaño del archivo: " . filesize($save_path) . " bytes");
    
    error_log("Foto guardada exitosamente en: " . $save_path);
}

// Obtener día de la semana y hora actual (usando zona horaria de Bogotá)
$dia_semana = date('N'); // Día de la semana actual en zona horaria de Bogotá
$hora_actual = getBogotaTime();
$hora_normalizada = formatTimeForAttendance($hora_actual);

// **CORREGIDO: Buscar horarios personalizados basándose en vigencia, incluyendo campos nocturno**
$sqlPersonalizados = "
    SELECT 
        ehp.ID_EMPLEADO_HORARIO,
        ehp.NOMBRE_TURNO as NOMBRE_HORARIO,
        ehp.HORA_ENTRADA,
        ehp.HORA_SALIDA,
        ehp.TOLERANCIA,
        ehp.ORDEN_TURNO,
        ehp.ES_TURNO_NOCTURNO,
        ehp.HORA_CORTE_NOCTURNO,
        'personalizado' as TIPO_HORARIO
    FROM empleado_horario_personalizado ehp
    WHERE ehp.ID_EMPLEADO = ?
    AND ehp.ID_DIA = ?
    AND ehp.FECHA_DESDE <= ?
    AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
    ORDER BY ehp.ORDEN_TURNO
";
$stmt = $conn->prepare($sqlPersonalizados);
$stmt->execute([$id_empleado, $dia_semana, $fecha, $fecha]);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// **CAMBIO FUNDAMENTAL: Solo usar horarios personalizados, no tradicionales**
if (empty($horarios)) {
    echo json_encode(['success' => false, 'message' => 'No se encontraron horarios personalizados asignados para el empleado en este día.']);
    exit;
}

// **NUEVA FUNCIONALIDAD: Filtrar turnos justificados**
$resultadoFiltrado = filtrarHorariosPorJustificaciones($id_empleado, $fecha, $horarios, $conn);
$horariosDisponibles = $resultadoFiltrado['horarios_disponibles'];
$turnosJustificados = $resultadoFiltrado['turnos_justificados'];
$todosJustificados = $resultadoFiltrado['todos_justificados'];

error_log("HORARIOS DISPONIBLES DESPUÉS DE FILTRAR JUSTIFICACIONES: " . count($horariosDisponibles));
error_log("TURNOS JUSTIFICADOS: " . implode(', ', $turnosJustificados));
error_log("TODOS JUSTIFICADOS: " . ($todosJustificados ? 'SÍ' : 'NO'));

if (empty($horariosDisponibles)) {
    if ($todosJustificados) {
        echo json_encode(['success' => false, 'message' => 'Todos los turnos para hoy están justificados. No se requiere registro de asistencia.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ya se han registrado todas las entradas y salidas para los horarios disponibles de hoy.']);
    }
    exit;
}

// Ordenar por turno para mantener secuencia
usort($horariosDisponibles, function ($a, $b) {
    $ordenA = (int)($a['ORDEN_TURNO'] ?? 0);
    $ordenB = (int)($b['ORDEN_TURNO'] ?? 0);

    if ($ordenA === $ordenB) {
        return strcmp($a['HORA_ENTRADA'], $b['HORA_ENTRADA']);
    }

    return $ordenA <=> $ordenB;
});

// Mapear el estado actual de cada turno (entrada/salida registrados)
$attendanceStatus = [];
$scheduleIds = array_column($horariosDisponibles, 'ID_EMPLEADO_HORARIO');

if (!empty($scheduleIds)) {
    $placeholders = implode(',', array_fill(0, count($scheduleIds), '?'));
    $fechaAnterior = date('Y-m-d', strtotime($fecha . ' -1 day'));
    $fechaSiguiente = date('Y-m-d', strtotime($fecha . ' +1 day'));

    $attendanceQuery = "
        SELECT ID_EMPLEADO_HORARIO, TIPO
        FROM asistencia
        WHERE ID_EMPLEADO = ?
        AND FECHA BETWEEN ? AND ?
        AND ID_EMPLEADO_HORARIO IS NOT NULL
        AND ID_EMPLEADO_HORARIO IN ($placeholders)
    ";

    $attendanceParams = array_merge([$id_empleado, $fechaAnterior, $fechaSiguiente], $scheduleIds);
    $stmtAttendance = $conn->prepare($attendanceQuery);
    $stmtAttendance->execute($attendanceParams);
    $attendanceRows = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);

    foreach ($attendanceRows as $row) {
        $turnoId = (int)$row['ID_EMPLEADO_HORARIO'];
        if (!$turnoId) {
            continue;
        }

        if (!isset($attendanceStatus[$turnoId])) {
            $attendanceStatus[$turnoId] = ['entrada' => false, 'salida' => false];
        }

        $tipoRegistroExistente = strtoupper($row['TIPO'] ?? '');
        if ($tipoRegistroExistente === 'ENTRADA') {
            $attendanceStatus[$turnoId]['entrada'] = true;
        } elseif ($tipoRegistroExistente === 'SALIDA') {
            $attendanceStatus[$turnoId]['salida'] = true;
        }
    }
}

// Filtrar turnos que aún tienen registros pendientes
$horariosPendientes = [];
foreach ($horariosDisponibles as $horario) {
    $turnoId = (int)($horario['ID_EMPLEADO_HORARIO'] ?? 0);
    $status = $attendanceStatus[$turnoId] ?? ['entrada' => false, 'salida' => false];

    if (!$status['entrada'] || !$status['salida']) {
        $horariosPendientes[] = $horario;
    }
}

$horariosDisponibles = $horariosPendientes;

if (empty($horariosDisponibles)) {
    if ($todosJustificados) {
        echo json_encode(['success' => false, 'message' => 'Todos los turnos para hoy están justificados. No se requiere registro de asistencia.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ya se han registrado todas las entradas y salidas para los horarios disponibles de hoy.']);
    }
    exit;
}

// Determinar cuál es el siguiente registro (entrada/salida) respetando el orden de turnos
$entradaPendiente = null;
$salidaPendiente = null;

foreach ($horariosDisponibles as $horario) {
    $turnoId = (int)($horario['ID_EMPLEADO_HORARIO'] ?? 0);
    $status = $attendanceStatus[$turnoId] ?? ['entrada' => false, 'salida' => false];

    if (!$status['entrada'] && $entradaPendiente === null) {
        $entradaPendiente = $horario;
    }

    if ($status['entrada'] && !$status['salida'] && $salidaPendiente === null) {
        $salidaPendiente = $horario;
    }
}

$horaActualTimestamp = strtotime($fecha . ' ' . $hora_actual);
$tipoRegistro = null;
$horarioSeleccionado = null;

$obtenerTimestampProgramadoCercano = static function (array $horario, string $tipo, string $fechaReferencia, int $timestampActual) {
    $horaProgramada = $tipo === 'ENTRADA' ? ($horario['HORA_ENTRADA'] ?? null) : ($horario['HORA_SALIDA'] ?? null);
    if (!$horaProgramada) {
        return false;
    }

    $fechasCandidatas = [
        date('Y-m-d', strtotime($fechaReferencia . ' -1 day')),
        $fechaReferencia,
        date('Y-m-d', strtotime($fechaReferencia . ' +1 day'))
    ];

    $mejorTimestamp = false;
    $menorDiferencia = PHP_INT_MAX;

    foreach ($fechasCandidatas as $fechaBase) {
        $timestampCandidato = strtotime($fechaBase . ' ' . $horaProgramada);
        if ($timestampCandidato === false) {
            continue;
        }

        $diferencia = abs($timestampActual - $timestampCandidato);
        if ($diferencia < $menorDiferencia) {
            $menorDiferencia = $diferencia;
            $mejorTimestamp = $timestampCandidato;
        }
    }

    return $mejorTimestamp;
};

if ($entradaPendiente && $salidaPendiente) {
    $tsEntrada = $obtenerTimestampProgramadoCercano($entradaPendiente, 'ENTRADA', $fecha, $horaActualTimestamp);
    $tsSalida = $obtenerTimestampProgramadoCercano($salidaPendiente, 'SALIDA', $fecha, $horaActualTimestamp);

    $difEntrada = $tsEntrada !== false ? abs($horaActualTimestamp - $tsEntrada) : PHP_INT_MAX;
    $difSalida = $tsSalida !== false ? abs($horaActualTimestamp - $tsSalida) : PHP_INT_MAX;

    if ($difEntrada <= $difSalida) {
        $tipoRegistro = 'ENTRADA';
        $horarioSeleccionado = $entradaPendiente;
    } else {
        $tipoRegistro = 'SALIDA';
        $horarioSeleccionado = $salidaPendiente;
    }
} elseif ($entradaPendiente) {
    $tipoRegistro = 'ENTRADA';
    $horarioSeleccionado = $entradaPendiente;
} elseif ($salidaPendiente) {
    $tipoRegistro = 'SALIDA';
    $horarioSeleccionado = $salidaPendiente;
} else {
    echo json_encode(['success' => false, 'message' => 'No hay turnos pendientes de registro para hoy.']);
    exit;
}

// **NUEVA VALIDACIÓN: Límite de turnos considerando justificaciones**
// Solo permitir entrada hasta el número de turnos disponibles no justificados
if ($tipoRegistro === 'ENTRADA') {
    // Contar cuántos turnos tienen entrada registrada hoy (excluyendo justificados)
    $sqlContarEntradas = "
        SELECT COUNT(DISTINCT a.ID_EMPLEADO_HORARIO) as entradas_registradas
        FROM asistencia a
        JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE a.ID_EMPLEADO = ?
        AND a.FECHA = ?
        AND a.TIPO = 'ENTRADA'
        AND ehp.ID_EMPLEADO_HORARIO NOT IN (
            SELECT turno_id FROM justificaciones
            WHERE empleado_id = ?
            AND fecha_falta = ?
            AND (justificar_todos_turnos = 1 OR turno_id IS NOT NULL)
        )
    ";

    $stmtContar = $conn->prepare($sqlContarEntradas);
    $stmtContar->execute([$id_empleado, $fecha, $id_empleado, $fecha]);
    $resultadoContar = $stmtContar->fetch(PDO::FETCH_ASSOC);

    $entradasRegistradas = (int)$resultadoContar['entradas_registradas'];
    $turnosDisponibles = count($horariosDisponibles);

    error_log("ENTRADAS REGISTRADAS (excluyendo justificados): " . $entradasRegistradas);
    error_log("TURNOS DISPONIBLES: " . $turnosDisponibles);

    if ($entradasRegistradas >= $turnosDisponibles) {
        echo json_encode(['success' => false, 'message' => 'Ya se han registrado entradas para todos los turnos disponibles hoy (' . $entradasRegistradas . ' de ' . $turnosDisponibles . ' turnos).']);
        exit;
    }
}

// Calcular si hay tardanza según el tipo de registro aplicando la tolerancia completa
$tardanza = "N";
$calcularDiferenciaRedondeada = static function (int $actual, int $programado) {
    $diferenciaSegundos = $actual - $programado;

    if ($diferenciaSegundos >= 0) {
        return (int)floor($diferenciaSegundos / 60);
    }

    return (int)ceil($diferenciaSegundos / 60);
};

$tolerancia = normalizarToleranciaMinutos($horarioSeleccionado['TOLERANCIA'] ?? 0);
$timestampProgramado = $obtenerTimestampProgramadoCercano($horarioSeleccionado, $tipoRegistro, $fecha, $horaActualTimestamp);

if ($timestampProgramado !== false) {
    $diferenciaRedondeada = $calcularDiferenciaRedondeada($horaActualTimestamp, $timestampProgramado);

    if ($tipoRegistro === 'ENTRADA') {
        if ($tolerancia <= 0) {
            if ($diferenciaRedondeada > 0) {
                $tardanza = "S";
            }
        } elseif ($diferenciaRedondeada > $tolerancia) {
            $tardanza = "S";
        }
    } elseif ($tipoRegistro === 'SALIDA') {
        if ($tolerancia <= 0) {
            if ($diferenciaRedondeada !== 0) {
                $tardanza = "S";
            }
        } elseif (abs($diferenciaRedondeada) > $tolerancia) {
            $tardanza = "S";
        }
    }
}

// **SIMPLIFICADO: Siempre usar ID_EMPLEADO_HORARIO ya que solo trabajamos con horarios personalizados**
$id_horario_registro = null; // SIEMPRE NULL - no usamos horarios tradicionales
$id_empleado_horario_registro = null;
$observacion_horario = "";

if ($horarioSeleccionado) {
    // **CORREGIDO: SIEMPRE usar ID_EMPLEADO_HORARIO para horarios personalizados**
    $id_empleado_horario_registro = $horarioSeleccionado['ID_EMPLEADO_HORARIO'];
    $observacion_horario = " [Horario personalizado: " . ($horarioSeleccionado['NOMBRE_HORARIO'] ?? 'Sin nombre') . " - Turno " . ($horarioSeleccionado['ORDEN_TURNO'] ?? 1) . "]";
}

// Agregar información del horario personalizado a las observaciones
if (!empty($observacion_horario)) {
    $observacion = $observacion . $observacion_horario;
}

// NUEVA LÓGICA: Determinar fecha correcta para turnos nocturnos
$fechaRegistro = getBogotaDate(); // Comenzar con fecha actual real del sistema

// 🌙 NUEVA FUNCIONALIDAD: Para SALIDAS de turnos nocturnos, agregar un día
if ($tipoRegistro === 'SALIDA' && $horarioSeleccionado && $horarioSeleccionado['ES_TURNO_NOCTURNO'] === 'S') {
    // Para salidas de turnos nocturnos, la fecha debe ser del día siguiente
    $fechaRegistro = date('Y-m-d', strtotime($fechaRegistro . ' +1 day'));
    
    // Log para debug
    error_log("TURNO NOCTURNO DETECTADO - Salida registrada para día siguiente: " . $fechaRegistro);
}

// **CORREGIDO: SQL solo con ID_EMPLEADO_HORARIO - ID_HORARIO siempre NULL**
$sql = "INSERT INTO ASISTENCIA 
    (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, REGISTRO_MANUAL, ID_HORARIO, ID_EMPLEADO_HORARIO, VERIFICATION_METHOD)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'N', ?, ?, ?)";

$insertDebugPayload = [
    'id_empleado' => $id_empleado,
    'fecha_registro' => $fechaRegistro,
    'tipo_registro' => $tipoRegistro,
    'hora_original' => $hora_actual,
    'hora_normalizada' => $hora_normalizada,
    'tardanza' => $tardanza,
    'observacion' => $observacion,
    'foto' => $filename,
    'id_horario' => $id_horario_registro,
    'id_empleado_horario' => $id_empleado_horario_registro,
    'verification_method_original' => $verification_method_original,
    'verification_method_normalized' => $verification_method
];

error_log('PAYLOAD ASISTENCIA (ANTES INSERT): ' . json_encode($insertDebugPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
file_put_contents(
    $debug_log_file,
    '[' . date('Y-m-d H:i:s') . "] " . json_encode($insertDebugPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
    FILE_APPEND
);

try {
    $stmt = $conn->prepare($sql);
    $ok = $stmt->execute([
        $id_empleado, 
        $fechaRegistro,  // Usar fecha corregida para turnos nocturnos
        $tipoRegistro, 
        $hora_normalizada, 
        $tardanza, 
        $observacion, // CORREGIR: Usar observacion en lugar de NULL
        $filename,
        $id_horario_registro,
        $id_empleado_horario_registro, // AGREGAR: Campo para horarios personalizados
        $verification_method
    ]);

    error_log('RESULTADO INSERT ASISTENCIA: ' . ($ok ? 'OK' : 'FALLÓ'));
    
    // Si es verificación biométrica y hay score de confianza, registrar en logs
    if ($ok && $verification_method !== 'traditional' && $confidence_score) {
        try {
            $log_stmt = $conn->prepare("
                INSERT INTO biometric_logs 
                (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, CONFIDENCE_SCORE, 
                 API_SOURCE, OPERATION_TYPE, FECHA, HORA) 
                VALUES (?, ?, 1, ?, 'attendance_api', 'verification', ?, ?)
            ");
            $log_stmt->execute([
                $id_empleado,
                $verification_method,
                $confidence_score,
                $fecha,
                $hora_actual
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the attendance registration
            error_log("Error registering biometric log: " . $e->getMessage());
        }
    }
    
    if ($ok) {
        // Obtener nombre del empleado para la respuesta
        $emp_stmt = $conn->prepare("SELECT NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ?");
        $emp_stmt->execute([$id_empleado]);
        $empleado = $emp_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => $tipoRegistro === 'ENTRADA' ? 'Entrada registrada correctamente' : 'Salida registrada correctamente',
            'employee_name' => $empleado ? $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'] : '',
            'attendance_type' => $tipoRegistro,
            'time' => $hora_normalizada,
            'time_full' => $hora_actual,
            'date' => $fechaRegistro, // Siempre fecha actual real del sistema
            'schedule' => $horarioSeleccionado['NOMBRE_HORARIO'],
            'schedule_type' => 'personalizado',
            'schedule_order' => $horarioSeleccionado['ORDEN_TURNO'] ?? 1,
            'is_night_shift' => $horarioSeleccionado['ES_TURNO_NOCTURNO'] === 'S',
            'night_cutoff_time' => $horarioSeleccionado['HORA_CORTE_NOCTURNO'] ?? null,
            'photo_saved' => $filename,
            'late_status' => $tardanza,
            'verification_method' => $verification_method,
            'verification_method_original' => $verification_method_original,
            'confidence_score' => $confidence_score
        ]);
    } else {
        if ($filename && file_exists($uploads_dir . $filename)) {
            unlink($uploads_dir . $filename);
        }
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Error al registrar la asistencia.',
            'debug_payload' => $insertDebugPayload
        ]);
    }
} catch (PDOException $e) {
    if ($filename && file_exists($uploads_dir . $filename)) {
        unlink($uploads_dir . $filename);
    }
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'debug_payload' => $insertDebugPayload
    ]);
}
?>
