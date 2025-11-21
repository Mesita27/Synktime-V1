<?php
// Evitar cualquier output antes de los headers
ob_start();

require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../utils/attendance_verification.php';

if (!function_exists('normalizeNullableId')) {
    function normalizeNullableId($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '' || strcasecmp($trimmed, 'null') === 0 || strcasecmp($trimmed, 'undefined') === 0) {
                return null;
            }

            if (!is_numeric($trimmed)) {
                return null;
            }

            $value = $trimmed;
        }

        if (is_numeric($value)) {
            $intValue = (int) $value;
            return $intValue > 0 ? $intValue : null;
        }

        return null;
    }
}

// Limpiar cualquier output anterior
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Establecer zona horaria de Colombia
// Zona horaria configurada en config/timezone.php

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Verificar que existe el directorio uploads
    $uploads_dir = __DIR__ . '/../../uploads/';
    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    // Preparar directorio de logs
    $logs_dir = realpath(dirname(__DIR__, 2));
    $logFileError = null;
    $debug_log_file = null;

    if ($logs_dir !== false) {
        $logs_dir = $logs_dir . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logs_dir)) {
            if (!@mkdir($logs_dir, 0755, true)) {
                $logFileError = 'No se pudo crear directorio logs: ' . $logs_dir;
            }
        }

        if ($logFileError === null) {
            $debug_log_file = $logs_dir . DIRECTORY_SEPARATOR . 'register_salida_debug.log';
            if (!is_writable($logs_dir)) {
                $logFileError = 'Directorio logs no es escribible: ' . $logs_dir;
            }
        }
    } else {
        $logFileError = 'No se pudo resolver el path base para logs';
    }

    // Recoge los datos POST
    $id_empleado = normalizeNullableId($_POST['id_empleado'] ?? null);
    $fecha_referencia = $_POST['fecha'] ?? null; // Fecha enviada por el frontend para referencia (generalmente la entrada)
    $id_horario = normalizeNullableId($_POST['id_horario'] ?? null); // **NUEVO: Recibir id_horario del frontend**
    $hora_manual = $_POST['hora'] ?? null;

    $hora_actual = $hora_manual ?? getBogotaTime();
    $hora_normalizada = formatTimeForAttendance($hora_actual);

    $fecha_salida_input = $_POST['fecha_salida'] ?? null;
    $fecha_salida = null;
    if (is_string($fecha_salida_input)) {
        $fecha_salida_input = trim($fecha_salida_input);
        if ($fecha_salida_input !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_salida_input)) {
            $fecha_salida = $fecha_salida_input;
        }
    }

    if ($fecha_salida === null) {
        $fecha_salida = getBogotaDate();
    }

    try {
        $fechaSalidaDateTime = new DateTime($fecha_salida . ' ' . $hora_normalizada, new DateTimeZone('America/Bogota'));
    } catch (Exception $e) {
        $fecha_salida = getBogotaDate();
        $fechaSalidaDateTime = new DateTime($fecha_salida . ' ' . $hora_normalizada, new DateTimeZone('America/Bogota'));
    }

    $fechaHoraSalida = $fechaSalidaDateTime->format('Y-m-d H:i:s');
    $entradaBusquedaInicio = (clone $fechaSalidaDateTime)->modify('-48 hours')->format('Y-m-d H:i:s');

    $initialDebug = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'id_empleado' => $id_empleado,
        'fecha_referencia' => $fecha_referencia,
        'fecha_salida' => $fecha_salida,
        'id_horario' => $id_horario,
        'hora_manual' => $hora_manual,
        'hora_normalizada' => $hora_normalizada,
        'server' => $_SERVER['HTTP_HOST'] ?? '',
        'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'ventana_busqueda_inicio' => $entradaBusquedaInicio,
        'fecha_hora_salida' => $fechaHoraSalida,
        'log_error' => $logFileError
    ];

    if ($debug_log_file) {
        @file_put_contents($debug_log_file, json_encode($initialDebug, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    // Validación de datos obligatorios
    if ($id_empleado === null) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit;
    }

    // Validar que el empleado pertenezca a la empresa del usuario
    $sql_employee_check = "
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 'S' AND s.ID_EMPRESA = ?
    ";
    $stmt = $conn->prepare($sql_employee_check);
    $stmt->execute([$id_empleado, $empresaId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado, inactivo o no pertenece a su empresa']);
        exit;
    }

    // **VALIDACIÓN MEJORADA: Determinar el tipo de horario y buscar en la columna correcta**
    // Primero verificar si el id_horario corresponde a un horario personalizado o tradicional
    $tipo_horario = null;
    $id_horario_real = null;
    $id_empleado_horario_real = null;

    // Verificar si es un ID de horario personalizado
    $sql_check_personalizado = "
        SELECT ID_EMPLEADO_HORARIO, 'personalizado' as tipo
        FROM empleado_horario_personalizado
        WHERE ID_EMPLEADO_HORARIO = ?
        AND ID_EMPLEADO = ?
    ";
    $horario_personalizado = null;
    if ($id_horario !== null) {
        $stmt = $conn->prepare($sql_check_personalizado);
        $stmt->execute([$id_horario, $id_empleado]);
        $horario_personalizado = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($horario_personalizado) {
        $tipo_horario = 'personalizado';
        $id_empleado_horario_real = $id_horario;
    } else {
        // Verificar si es un ID de horario tradicional
        $sql_check_tradicional = "
            SELECT eh.ID_EMPLEADO_HORARIO, 'legacy' as tipo
            FROM empleado_horario eh
            WHERE eh.ID_HORARIO = ?
            AND eh.ID_EMPLEADO = ?
            AND eh.ACTIVO = 'S'
        ";
        $horario_tradicional = null;
        if ($id_horario !== null) {
            $stmt = $conn->prepare($sql_check_tradicional);
            $stmt->execute([$id_horario, $id_empleado]);
            $horario_tradicional = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($horario_tradicional) {
            $tipo_horario = 'legacy';
            $id_horario_real = $id_horario;
            $id_empleado_horario_real = $horario_tradicional['ID_EMPLEADO_HORARIO'];
        }
    }

    static $asistenciaColumnCache = null;
    if ($asistenciaColumnCache === null) {
        try {
            $columnsStmt = $conn->query('SHOW COLUMNS FROM asistencia');
            $asistenciaColumnCache = $columnsStmt ? array_map(function ($row) {
                return $row['Field'] ?? null;
            }, $columnsStmt->fetchAll(PDO::FETCH_ASSOC)) : [];
        } catch (Exception $e) {
            $asistenciaColumnCache = [];
        }
    }

    $hasTipoHorarioColumn = in_array('TIPO_HORARIO', $asistenciaColumnCache ?? [], true);
    $hasEmpleadoHorarioColumn = in_array('ID_EMPLEADO_HORARIO', $asistenciaColumnCache ?? [], true);
    $hasHorarioColumn = in_array('ID_HORARIO', $asistenciaColumnCache ?? [], true);

    $notExistsComparisons = [];
    if ($hasEmpleadoHorarioColumn) {
        $notExistsComparisons[] = '(a.ID_EMPLEADO_HORARIO IS NOT NULL AND a2.ID_EMPLEADO_HORARIO = a.ID_EMPLEADO_HORARIO)';
    }
    if ($hasHorarioColumn) {
        $notExistsComparisons[] = '(a.ID_HORARIO IS NOT NULL AND a2.ID_HORARIO = a.ID_HORARIO)';
    }
    $notExistsCondition = $notExistsComparisons ? implode(' OR ', $notExistsComparisons) : '1=1';

    $entradaFallback = null;

    if (!$tipo_horario) {
        $fallbackSelectPieces = ['a.ID_ASISTENCIA'];
        if ($hasHorarioColumn) {
            $fallbackSelectPieces[] = 'a.ID_HORARIO';
        }
        if ($hasEmpleadoHorarioColumn) {
            $fallbackSelectPieces[] = 'a.ID_EMPLEADO_HORARIO';
        }

        $fallbackSql = sprintf("\n            SELECT %s\n            FROM asistencia a\n            WHERE a.ID_EMPLEADO = ?\n            AND a.TIPO = 'ENTRADA'\n            AND CONCAT(a.FECHA, ' ', a.HORA) BETWEEN ? AND ?\n            AND NOT EXISTS (\n                SELECT 1 FROM asistencia a2\n                WHERE a2.ID_EMPLEADO = a.ID_EMPLEADO\n                AND a2.TIPO = 'SALIDA'\n                AND (%s)\n                AND CONCAT(a2.FECHA, ' ', a2.HORA) >= CONCAT(a.FECHA, ' ', a.HORA)\n            )\n            ORDER BY CONCAT(a.FECHA, ' ', a.HORA) DESC\n            LIMIT 1\n        ", implode(', ', $fallbackSelectPieces), $notExistsCondition);

        $stmt = $conn->prepare($fallbackSql);
        $stmt->execute([$id_empleado, $entradaBusquedaInicio, $fechaHoraSalida]);
        $entradaFallback = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($entradaFallback) {
            if ($hasEmpleadoHorarioColumn) {
                $fallbackEmpleadoHorario = normalizeNullableId($entradaFallback['ID_EMPLEADO_HORARIO'] ?? null);
                if ($fallbackEmpleadoHorario !== null) {
                    $tipo_horario = 'personalizado';
                    $id_empleado_horario_real = $fallbackEmpleadoHorario;
                }
            }

            if (!$tipo_horario && $hasHorarioColumn) {
                $fallbackHorario = normalizeNullableId($entradaFallback['ID_HORARIO'] ?? null);
                if ($fallbackHorario !== null) {
                    $tipo_horario = 'legacy';
                    $id_horario_real = $fallbackHorario;
                }
            }

            if (!$tipo_horario) {
                $tipo_horario = 'legacy';
            }
        }
    }

    if (!$tipo_horario) {
        echo json_encode(['success' => false, 'message' => 'Horario no encontrado o no válido para este empleado.']);
        exit;
    }

    if ($tipo_horario === 'personalizado' && !$hasEmpleadoHorarioColumn) {
        echo json_encode(['success' => false, 'message' => 'La columna ID_EMPLEADO_HORARIO no existe en esta versión de la tabla asistencia.']);
        exit;
    }

    if ($tipo_horario === 'legacy' && !$hasHorarioColumn) {
        echo json_encode(['success' => false, 'message' => 'La columna ID_HORARIO no existe en esta versión de la tabla asistencia.']);
        exit;
    }

    $scheduleMatchCondition = $tipo_horario === 'personalizado'
        ? 'a.ID_EMPLEADO_HORARIO = ?'
        : 'a.ID_HORARIO = ?';
    $scheduleMatchParams = $tipo_horario === 'personalizado'
        ? [$id_empleado_horario_real]
        : [$id_horario_real];

    if (($scheduleMatchParams[0] ?? null) === null && $entradaFallback && isset($entradaFallback['ID_ASISTENCIA'])) {
        $fallbackAsistenciaId = normalizeNullableId($entradaFallback['ID_ASISTENCIA']);
        if ($fallbackAsistenciaId !== null) {
            $scheduleMatchCondition = 'a.ID_ASISTENCIA = ?';
            $scheduleMatchParams = [$fallbackAsistenciaId];
        }
    }

    // **VALIDACIÓN CORREGIDA: Verificar que existe una entrada para este horario específico que NO tenga salida**
    $sql_check_entrada_horario = sprintf("
        SELECT COUNT(*) as entradas_sin_salida
        FROM asistencia a
        WHERE a.ID_EMPLEADO = ?
        AND a.TIPO = 'ENTRADA'
        AND CONCAT(a.FECHA, ' ', a.HORA) BETWEEN ? AND ?
        AND %s
        AND NOT EXISTS (
            SELECT 1 FROM asistencia a2
            WHERE a2.ID_EMPLEADO = a.ID_EMPLEADO
            AND a2.TIPO = 'SALIDA'
            AND (%s)
            AND CONCAT(a2.FECHA, ' ', a2.HORA) >= CONCAT(a.FECHA, ' ', a.HORA)
        )
    ", $scheduleMatchCondition, $notExistsCondition);
    $stmt = $conn->prepare($sql_check_entrada_horario);
    $stmt->execute(array_merge([
        $id_empleado,
        $entradaBusquedaInicio,
        $fechaHoraSalida
    ], $scheduleMatchParams));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['entradas_sin_salida'] == 0) {
        echo json_encode(['success' => false, 'message' => 'No hay entrada abierta para este horario específico, o ya se registró la salida correspondiente.']);
        exit;
    }

    // **CORREGIDO: Encontrar la entrada específica de este horario**
    $selectFields = [
        'a.ID_ASISTENCIA',
        'a.OBSERVACION',
        'a.HORA as hora_entrada',
        'a.VERIFICATION_METHOD'
    ];
    if ($hasHorarioColumn) {
        $selectFields[] = 'a.ID_HORARIO';
    }
    if ($hasEmpleadoHorarioColumn) {
        $selectFields[] = 'a.ID_EMPLEADO_HORARIO';
    }
    $selectFieldsSql = implode(",\n            ", $selectFields);

    $sql_entrada_horario = sprintf("
        SELECT
            %s
        FROM asistencia a
        WHERE a.ID_EMPLEADO = ?
        AND a.TIPO = 'ENTRADA'
        AND %s
        AND CONCAT(a.FECHA, ' ', a.HORA) BETWEEN ? AND ?
        AND NOT EXISTS (
            SELECT 1 FROM asistencia a2
            WHERE a2.ID_EMPLEADO = a.ID_EMPLEADO
            AND a2.TIPO = 'SALIDA'
            AND (%s)
            AND CONCAT(a2.FECHA, ' ', a2.HORA) >= CONCAT(a.FECHA, ' ', a.HORA)
        )
        ORDER BY CONCAT(a.FECHA, ' ', a.HORA) DESC
        LIMIT 1
    ", $selectFieldsSql, $scheduleMatchCondition, $notExistsCondition);
    $stmt = $conn->prepare($sql_entrada_horario);
    $stmt->execute(array_merge([
        $id_empleado,
    ], $scheduleMatchParams, [
        $entradaBusquedaInicio,
        $fechaHoraSalida
    ]));
    $entradaHorario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entradaHorario) {
        echo json_encode(['success' => false, 'message' => 'No se encontró una entrada abierta para este horario específico.']);
        exit;
    }

    // Crear observación basada en la entrada
    // **CORREGIDO: Usar los datos de la entrada específica del horario**
    $idHorario = $tipo_horario === 'legacy' ? $id_horario_real : null;
    $idEmpleadoHorario = $tipo_horario === 'personalizado' ? $id_empleado_horario_real : null;
    $observacion = $entradaHorario['OBSERVACION'];
    
    // Preparar datos para registro
    $tardanza = 'N';  // Por defecto no hay tardanza en salidas

    $verificationMethod = normalizeVerificationMethod($entradaHorario['VERIFICATION_METHOD'] ?? 'manual');
    $registroManualFlag = $verificationMethod === 'traditional' ? 'S' : 'N';
    if ($registroManualFlag === 'S') {
        $verificationMethod = 'traditional';
    }

    // Detectar columnas disponibles en la tabla asistencia para compatibilidad con versiones anteriores
    $fields = [
        'ID_EMPLEADO',
        'FECHA',
        'TIPO',
        'HORA',
        'TARDANZA',
        'OBSERVACION',
        'FOTO',
        'REGISTRO_MANUAL',
        'VERIFICATION_METHOD'
    ];

    $values = [
        $id_empleado,
    $fecha_salida,
        'SALIDA',
        $hora_normalizada,
        $tardanza,
        $observacion,
        null,
        $registroManualFlag,
        $verificationMethod
    ];

    if ($hasHorarioColumn) {
        $fields[] = 'ID_HORARIO';
        $values[] = $idHorario;
    }

    if ($hasEmpleadoHorarioColumn) {
        $fields[] = 'ID_EMPLEADO_HORARIO';
        $values[] = $idEmpleadoHorario;
    }

    if ($hasTipoHorarioColumn) {
        $fields[] = 'TIPO_HORARIO';
        $values[] = $tipo_horario;
    }

    $insertDebug = [
        'timestamp' => date('Y-m-d H:i:s'),
        'id_empleado' => $id_empleado,
        'fecha_referencia' => $fecha_referencia,
        'fecha_salida' => $fecha_salida,
        'fecha_hora_salida' => $fechaHoraSalida,
        'hora_normalizada' => $hora_normalizada,
        'fields' => $fields,
        'values' => $values,
        'columns_detected' => $asistenciaColumnCache
    ];
    if ($debug_log_file) {
        @file_put_contents($debug_log_file, json_encode($insertDebug, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $sql = 'INSERT INTO asistencia (' . implode(', ', $fields) . ') VALUES (' . $placeholders . ')';
    $stmt = $conn->prepare($sql);
    $ok = $stmt->execute($values);
    
    if ($ok) {
        echo json_encode([
            'success' => true, 
            'message' => 'Salida registrada correctamente',
            'registro' => [
                'fecha' => $fecha_salida,
                'hora' => $hora_normalizada,
                'hora_original' => $hora_actual,
                'verification_method' => $verificationMethod
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar la salida.']);
    }

} catch (PDOException $e) {
    if ($debug_log_file) {
        @file_put_contents($debug_log_file, json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'PDOException',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($debug_log_file) {
        @file_put_contents($debug_log_file, json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'Exception',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>