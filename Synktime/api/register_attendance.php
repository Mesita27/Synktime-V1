<?php
/**
 * API Mejorada para registrar asistencia con todas las verificaciones tradicionales
 * SNKTIME Biometric System - Versión Mejorada
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configurar zona horaria de Bogotá, Colombia
require_once '../config/timezone.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST.'
    ]);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';
require_once '../utils/attendance_verification.php';

try {
    // Obtener datos de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['employee_id']) || !isset($input['type'])) {
        throw new Exception('Datos incompletos: se requiere employee_id y type');
    }

    $employeeId = $input['employee_id'];
    $type = strtoupper($input['type']);
    $verificationResults = $input['verification_results'] ?? [];
    $timestamp = $input['timestamp'] ?? getBogotaDateTime();
    $fecha = getBogotaDate();
    $hora = getBogotaTime();
    $horaDb = formatTimeForAttendance($hora);
    $verificationMethodOriginal = $input['verification_method'] ?? 'biometric';
    $verificationMethod = normalizeVerificationMethod($verificationMethodOriginal);

    $confidenceScore = null;
    if (isset($input['confidence_score']) && is_numeric($input['confidence_score'])) {
        $confidenceScore = (float) $input['confidence_score'];
    } elseif (isset($verificationResults['face']['confidence']) && is_numeric($verificationResults['face']['confidence'])) {
        $confidenceScore = (float) $verificationResults['face']['confidence'];
    } elseif (isset($verificationResults['fingerprint']['confidence']) && is_numeric($verificationResults['fingerprint']['confidence'])) {
        $confidenceScore = (float) $verificationResults['fingerprint']['confidence'];
    }

    $successCandidates = [];
    if (isset($verificationResults['face']['success'])) {
        $successCandidates[] = (bool) $verificationResults['face']['success'];
    }
    if (isset($verificationResults['fingerprint']['verified'])) {
        $successCandidates[] = (bool) $verificationResults['fingerprint']['verified'];
    }
    if (isset($verificationResults['rfid']['verified'])) {
        $successCandidates[] = (bool) $verificationResults['rfid']['verified'];
    }
    $verificationSuccess = null;
    if (!empty($successCandidates)) {
        $verificationSuccess = in_array(true, $successCandidates, true) ? 1 : 0;
    }

    $photoFilename = null;
    $photoRelativePath = null;
    $photoCandidates = [];
    if (!empty($input['verification_photo'])) {
        $photoCandidates[] = $input['verification_photo'];
    }
    if (isset($verificationResults['face']) && is_array($verificationResults['face'])) {
        foreach (['photo', 'image', 'image_data', 'snapshot', 'verification_photo'] as $photoKey) {
            if (!empty($verificationResults['face'][$photoKey])) {
                $photoCandidates[] = $verificationResults['face'][$photoKey];
            }
        }
    }

    $decodeImage = static function ($value) {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $cleaned = preg_replace('#^data:image/\w+;base64,#i', '', $value);
        if ($cleaned === null) {
            $cleaned = $value;
        }

        $cleaned = trim(str_replace([' ', "\r", "\n", "\t"], '', $cleaned));
        if ($cleaned === '') {
            return null;
        }

        $decoded = base64_decode($cleaned, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        return $decoded;
    };

    foreach ($photoCandidates as $candidate) {
        $binaryPhoto = $decodeImage($candidate);
        if ($binaryPhoto === null) {
            continue;
        }

        $uploadsDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
            error_log('No se pudo crear el directorio de uploads para guardar foto de asistencia');
            break;
        }

        $generatedName = 'attendance_' . uniqid('', true) . '_' . date('Ymd_His') . '.jpg';
        $savePath = $uploadsDir . $generatedName;

        if (file_put_contents($savePath, $binaryPhoto) !== false) {
            $photoFilename = $generatedName;
            $photoRelativePath = 'uploads/' . $generatedName;
            break;
        }
    }

    // Validar tipo de registro
    if (!in_array($type, ['ENTRADA', 'SALIDA', 'DESCANSO_INICIO', 'DESCANSO_FIN'])) {
        throw new Exception('Tipo de registro inválido. Use ENTRADA, SALIDA, DESCANSO_INICIO o DESCANSO_FIN');
    }

    // Verificar que el empleado existe y está activo
    $stmt = $pdo->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.ID_ESTABLECIMIENTO,
               e.ESTADO, e.ACTIVO, e.FECHA_INGRESO
        FROM empleado e
        WHERE e.ID_EMPLEADO = ? AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
    ");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Empleado no encontrado, inactivo o no autorizado');
    }

    // Obtener horarios del empleado para el día actual (usando zona horaria de Bogotá)
    $diaSemana = date('N'); // Día de la semana actual en zona horaria de Bogotá
    
    // **CORREGIDO: Buscar SOLO horarios personalizados basándose en FECHA_DESDE/FECHA_HASTA**
    $stmt = $pdo->prepare("
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO as NOMBRE_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            'personalizado' as TIPO_HORARIO
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ?
        AND ehp.ID_DIA = ?
        AND ehp.FECHA_DESDE <= ?
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        AND ehp.ACTIVO = 'S'
        ORDER BY ehp.FECHA_DESDE DESC, ehp.HORA_ENTRADA
    ");
    $stmt->execute([$employeeId, $diaSemana, $fecha, $fecha]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // **CAMBIO FUNDAMENTAL: Solo usar horarios personalizados, no tradicionales**
    if (empty($horarios)) {
        throw new Exception('No se encontraron horarios personalizados asignados para el empleado en este día');
    }

    // Verificar registros existentes del día
    $stmt = $pdo->prepare("
        SELECT ID_ASISTENCIA, TIPO, HORA, TARDANZA, ID_HORARIO
        FROM asistencia
        WHERE ID_EMPLEADO = ? AND DATE(FECHA) = ?
        ORDER BY HORA
    ");
    $stmt->execute([$employeeId, $fecha]);
    $registrosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aplicar validaciones tradicionales
    $validacionResultado = validarRegistroAsistencia($type, $registrosExistentes, $horarios, $fecha, $hora);

    if (!$validacionResultado['permitido']) {
        throw new Exception($validacionResultado['mensaje']);
    }

    // Determinar el horario correspondiente
    $horarioSeleccionado = determinarHorarioCorrespondiente($type, $registrosExistentes, $horarios, $hora);

    if (!$horarioSeleccionado) {
        throw new Exception('No se pudo determinar el horario correspondiente para este registro');
    }

    // Calcular tardanza si es entrada
    $tardanza = 'N';
    if ($type === 'ENTRADA') {
        $tardanza = calcularTardanza($hora, $horarioSeleccionado['HORA_ENTRADA'], $horarioSeleccionado['TOLERANCIA']);
    }

    // **SIMPLIFICADO: Preparar datos para inserción con empleado_horario_personalizado únicamente**
    $observaciones = null;
    
    if ($horarioSeleccionado) {
        $observaciones = "Horario personalizado: " . ($horarioSeleccionado['NOMBRE_HORARIO'] ?? 'Sin nombre');
    }
    
    static $asistenciaColumnCache = null;
    if ($asistenciaColumnCache === null) {
        try {
            $columnsStmt = $pdo->query('SHOW COLUMNS FROM asistencia');
            $asistenciaColumnCache = $columnsStmt ? array_map(static function ($row) {
                return $row['Field'] ?? null;
            }, $columnsStmt->fetchAll(PDO::FETCH_ASSOC)) : [];
        } catch (Exception $e) {
            $asistenciaColumnCache = [];
        }
    }

    $hasColumn = static function (string $column) use ($asistenciaColumnCache): bool {
        return in_array($column, $asistenciaColumnCache, true);
    };

    $datosInsercion = [
        'ID_EMPLEADO' => $employeeId,
        'FECHA' => $fecha,
        'TIPO' => $type,
        'HORA' => $horaDb,
        'TARDANZA' => $tardanza,
        'ID_HORARIO' => null,
        'ID_EMPLEADO_HORARIO' => $horarioSeleccionado['ID_EMPLEADO_HORARIO'],
        'VERIFICATION_METHOD' => $verificationMethod,
        'REGISTRO_MANUAL' => 'N'
    ];

    if ($observaciones) {
        $datosInsercion['OBSERVACION'] = $observaciones;
    }

    if ($photoFilename && $hasColumn('FOTO')) {
        $datosInsercion['FOTO'] = $photoFilename;
    }

    if ($hasColumn('CONFIDENCE_SCORE') && $confidenceScore !== null) {
        $datosInsercion['CONFIDENCE_SCORE'] = $confidenceScore;
    }

    if ($hasColumn('VERIFICATION_SUCCESS') && $verificationSuccess !== null) {
        $datosInsercion['VERIFICATION_SUCCESS'] = $verificationSuccess;
    }

    // Insertar registro de asistencia
    $columnas = implode(', ', array_keys($datosInsercion));
    $placeholders = implode(', ', array_fill(0, count($datosInsercion), '?'));
    $stmt = $pdo->prepare("INSERT INTO asistencia ($columnas) VALUES ($placeholders)");
    $stmt->execute(array_values($datosInsercion));

    $attendanceId = $pdo->lastInsertId();

    // Registrar en el log de verificaciones biométricas
    registrarLogBiometrico($pdo, $employeeId, $verificationResults, $attendanceId, $verificationMethod);

    // Calcular horas trabajadas si es salida
    $horasTrabajadas = null;
    if ($type === 'SALIDA') {
        $horasTrabajadas = calcularHorasTrabajadasDia($registrosExistentes, $hora, $fecha);
    }

    // Preparar respuesta completa
    $response = [
        'success' => true,
        'attendance_id' => $attendanceId,
        'employee_id' => $employeeId,
        'employee_name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'type' => $type,
        'timestamp' => $timestamp,
        'date' => $fecha,
        'time' => $horaDb,
        'time_full' => $hora,
        'attendance_type' => $type,
        'schedule' => $horarioSeleccionado['NOMBRE_HORARIO'] ?? null,
        'schedule_details' => $horarioSeleccionado,
        'late_status' => $tardanza,
        'validation_message' => $validacionResultado['mensaje'],
        'worked_hours' => $horasTrabajadas,
        'verification_method' => $verificationMethod,
        'verification_method_original' => $verificationMethodOriginal,
        'confidence_score' => $confidenceScore,
        'verification_success' => $verificationSuccess,
        'photo_saved' => (bool) $photoFilename,
        'photo_filename' => $photoFilename,
        'photo_url' => $photoRelativePath,
        'message' => "Asistencia registrada correctamente: {$type}"
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error al registrar asistencia: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al registrar asistencia',
        'message' => $e->getMessage(),
        'recommendations' => [
            'Verifique que el empleado esté activo en el sistema',
            'Asegúrese de que no exista ya un registro para este tipo de asistencia',
            'Verifique que el empleado tenga horarios asignados para este día',
            'Contacte al administrador si el problema persiste'
        ]
    ]);
}

/**
 * Valida si el registro de asistencia es permitido según reglas tradicionales
 */
function validarRegistroAsistencia($type, $registrosExistentes, $horarios, $fecha, $hora) {
    // Contar registros por tipo
    $conteoTipos = ['ENTRADA' => 0, 'SALIDA' => 0, 'DESCANSO_INICIO' => 0, 'DESCANSO_FIN' => 0];
    foreach ($registrosExistentes as $registro) {
        $conteoTipos[$registro['TIPO']]++;
    }

    switch ($type) {
        case 'ENTRADA':
            if ($conteoTipos['ENTRADA'] >= count($horarios)) {
                return [
                    'permitido' => false,
                    'mensaje' => 'Ya se han registrado todas las entradas programadas para hoy'
                ];
            }

            // Verificar que no haya una entrada muy reciente (evitar duplicados accidentales)
            $ultimoRegistro = end($registrosExistentes);
            if ($ultimoRegistro && $ultimoRegistro['TIPO'] === 'ENTRADA') {
                $ultimaHora = strtotime($fecha . ' ' . $ultimoRegistro['HORA']);
                $horaActual = strtotime($fecha . ' ' . $hora);
                $minutosDiferencia = ($horaActual - $ultimaHora) / 60;

                if ($minutosDiferencia < 5) { // Menos de 5 minutos
                    return [
                        'permitido' => false,
                        'mensaje' => 'Registro de entrada duplicado. Debe esperar al menos 5 minutos entre registros.'
                    ];
                }
            }
            break;

        case 'SALIDA':
            if ($conteoTipos['ENTRADA'] === 0) {
                return [
                    'permitido' => false,
                    'mensaje' => 'No se puede registrar salida sin una entrada previa'
                ];
            }

            if ($conteoTipos['SALIDA'] >= $conteoTipos['ENTRADA']) {
                return [
                    'permitido' => false,
                    'mensaje' => 'Ya se han registrado todas las salidas correspondientes a las entradas'
                ];
            }
            break;

        case 'DESCANSO_INICIO':
            if ($conteoTipos['ENTRADA'] === 0) {
                return [
                    'permitido' => false,
                    'mensaje' => 'No se puede iniciar descanso sin una entrada previa'
                ];
            }

            if ($conteoTipos['DESCANSO_INICIO'] > $conteoTipos['DESCANSO_FIN']) {
                return [
                    'permitido' => false,
                    'mensaje' => 'Ya hay un descanso iniciado sin finalizar'
                ];
            }
            break;

        case 'DESCANSO_FIN':
            if ($conteoTipos['DESCANSO_INICIO'] === 0) {
                return [
                    'permitido' => false,
                    'mensaje' => 'No se puede finalizar descanso sin haberlo iniciado'
                ];
            }

            if ($conteoTipos['DESCANSO_FIN'] >= $conteoTipos['DESCANSO_INICIO']) {
                return [
                    'permitido' => false,
                    'mensaje' => 'No hay descansos pendientes de finalizar'
                ];
            }
            break;
    }

    return [
        'permitido' => true,
        'mensaje' => 'Registro válido'
    ];
}

/**
 * Determina el horario correspondiente para el registro
 */
function determinarHorarioCorrespondiente($type, $registrosExistentes, $horarios, $hora) {
    if (count($horarios) === 1) {
        return $horarios[0];
    }

    // Para múltiples horarios, encontrar el más cercano a la hora actual
    $horarioMasCercano = null;
    $menorDiferencia = PHP_INT_MAX;

    foreach ($horarios as $horario) {
        $horaObjetivo = ($type === 'ENTRADA' || $type === 'DESCANSO_INICIO')
            ? $horario['HORA_ENTRADA']
            : $horario['HORA_SALIDA'];

        $diferencia = abs(strtotime($hora) - strtotime($horaObjetivo));

        if ($diferencia < $menorDiferencia) {
            $menorDiferencia = $diferencia;
            $horarioMasCercano = $horario;
        }
    }

    return $horarioMasCercano;
}

/**
 * Calcula si hay tardanza en la entrada
 */
function calcularTardanza($horaActual, $horaEntrada, $tolerancia) {
    $tsActual = strtotime($horaActual);
    $tsEntrada = strtotime($horaEntrada);

    if ($tsActual <= $tsEntrada + ($tolerancia * 60)) {
        return 'N'; // Puntual
    } else {
        return 'S'; // Tardanza
    }
}

/**
 * Calcula las horas trabajadas del día
 */
function calcularHorasTrabajadasDia($registrosExistentes, $horaSalida, $fecha) {
    $entradas = [];
    $salidas = [];
    $descansos = [];

    // Separar registros por tipo
    foreach ($registrosExistentes as $registro) {
        switch ($registro['TIPO']) {
            case 'ENTRADA':
                $entradas[] = $registro['HORA'];
                break;
            case 'SALIDA':
                $salidas[] = $registro['HORA'];
                break;
            case 'DESCANSO_INICIO':
                $descansos[] = ['inicio' => $registro['HORA']];
                break;
            case 'DESCANSO_FIN':
                if (!empty($descansos) && !isset(end($descansos)['fin'])) {
                    $descansos[key($descansos)]['fin'] = $registro['HORA'];
                }
                break;
        }
    }

    // Agregar la salida actual
    $salidas[] = $horaSalida;

    $totalMinutos = 0;

    // Calcular tiempo trabajado por cada par entrada-salida
    for ($i = 0; $i < min(count($entradas), count($salidas)); $i++) {
        $entrada = strtotime($fecha . ' ' . $entradas[$i]);
        $salida = strtotime($fecha . ' ' . $salidas[$i]);

        if ($salida > $entrada) {
            $minutosTrabajados = ($salida - $entrada) / 60;

            // Restar tiempo de descansos dentro de este período
            foreach ($descansos as $descanso) {
                if (isset($descanso['inicio']) && isset($descanso['fin'])) {
                    $inicioDescanso = strtotime($fecha . ' ' . $descanso['inicio']);
                    $finDescanso = strtotime($fecha . ' ' . $descanso['fin']);

                    // Verificar si el descanso está dentro del período de trabajo
                    if ($inicioDescanso >= $entrada && $finDescanso <= $salida && $finDescanso > $inicioDescanso) {
                        $minutosDescanso = ($finDescanso - $inicioDescanso) / 60;
                        $minutosTrabajados -= $minutosDescanso;
                    }
                }
            }

            $totalMinutos += max(0, $minutosTrabajados);
        }
    }

    return [
        'total_horas' => round($totalMinutos / 60, 2),
        'total_minutos' => round($totalMinutos, 0),
        'detalle' => [
            'entradas' => $entradas,
            'salidas' => $salidas,
            'descansos' => $descansos
        ]
    ];
}

/**
 * Registra el log de verificación biométrica
 */
function registrarLogBiometrico($pdo, $employeeId, $verificationResults, $attendanceId, $verificationMethod) {
    try {
        $verificationTypes = [];
        if (isset($verificationResults['face'])) $verificationTypes[] = 'facial';
        if (isset($verificationResults['fingerprint'])) $verificationTypes[] = 'fingerprint';
        if (isset($verificationResults['rfid'])) $verificationTypes[] = 'rfid';

        // Verificar si existe la tabla biometric_verification_log
        $tableExists = $pdo->query("SHOW TABLES LIKE 'biometric_verification_log'")->rowCount() > 0;

        if ($tableExists && !empty($verificationTypes)) {
            $stmt = $pdo->prepare("
                INSERT INTO biometric_verification_log
                (employee_id, verification_type, success, attendance_id, ip_address, user_agent, created_at)
                VALUES (?, ?, 1, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $employeeId,
                implode(',', $verificationTypes),
                $attendanceId,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        error_log('Error al registrar en log biométrico: ' . $e->getMessage());
    }
}
?>
