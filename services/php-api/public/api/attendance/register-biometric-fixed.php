<?php
// Sistema de registro de asistencia biométrica - Versión corregida
error_reporting(0); // Desactivar errores PHP en producción
ini_set('display_errors', 0);

// Limpiar cualquier output anterior
ob_clean();

// Headers para JSON response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Includes únicos
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/timezone.php';
    require_once __DIR__ . '/../../auth/session.php';
    require_once __DIR__ . '/../../utils/attendance_verification.php';

    if (!function_exists('normalizeNullableId')) {
        function normalizeNullableId($value): ?int
        {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_numeric($value)) {
                return (int) $value;
            }

            return null;
        }
    }
    
    // Verificar autenticación
    requireAuth();
    
    // Establecer zona horaria
    date_default_timezone_set('America/Bogota');
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos del POST
    $id_empleado = normalizeNullableId($_POST['id_empleado'] ?? null);
    $tipo_registro = strtoupper($_POST['tipo'] ?? 'ENTRADA');
    $verification_method = $_POST['verification_method'] ?? 'facial';
    $confidence_score = $_POST['confidence_score'] ?? null;
    $horario_info = $_POST['horario_info'] ?? null;
    $id_empleado_horario = normalizeNullableId($_POST['id_empleado_horario'] ?? null);
    $id_horario = normalizeNullableId($_POST['id_horario'] ?? null);
    $tipo_horario = $_POST['tipo_horario'] ?? null;
    
    // Validaciones básicas
    if ($id_empleado === null) {
        throw new Exception('ID de empleado requerido');
    }
    
    if (!in_array($tipo_registro, ['ENTRADA', 'SALIDA'])) {
        throw new Exception('Tipo de registro inválido');
    }
    
    // Verificar que el empleado existe
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'");
    $stmt->execute([$id_empleado]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Procesar horario
    $horario_usado = null;
    if ($horario_info) {
        $horario_usado = json_decode($horario_info, true);
    }

    if ($horario_usado) {
        if ($id_horario === null) {
            $id_horario = normalizeNullableId($horario_usado['ID_HORARIO'] ?? $horario_usado['id_horario'] ?? null);
        }
        if ($id_empleado_horario === null) {
            $id_empleado_horario = normalizeNullableId($horario_usado['ID_EMPLEADO_HORARIO'] ?? $horario_usado['id_empleado_horario'] ?? null);
        }
        if ($tipo_horario === null || $tipo_horario === '') {
            $tipo_horario = $horario_usado['TIPO_HORARIO'] ?? $horario_usado['tipo_horario'] ?? null;
        }
    }

    $tipo_horario = is_string($tipo_horario) ? strtolower(trim($tipo_horario)) : null;
    if ($tipo_horario === '' || $tipo_horario === 'ninguno') {
        $tipo_horario = null;
    }
    
    // Datos del registro
    $fecha = getBogotaDate();
    $horaOriginal = $_POST['hora'] ?? getBogotaTime('H:i:s');
    $hora = formatTimeForAttendance($horaOriginal);
    $tardanza = 'N'; // Por ahora simple, se puede mejorar
    $verification_method = normalizeVerificationMethod($verification_method);
    $registroManualFlag = $verification_method === 'traditional' ? 'S' : 'N';
    $verificationSuccess = $verification_method === 'traditional' ? null : 1;
    $confidence_score = $confidence_score !== null && $confidence_score !== '' ? (float) $confidence_score : null;

    $observacion = "Registro biométrico - Método: $verification_method";
    
    if ($horario_usado) {
        $observacion .= " - Horario: " . ($horario_usado['horario_nombre'] ?? 'Sin nombre');
        if (isset($horario_usado['tipo_horario'])) {
            $observacion .= " (" . $horario_usado['tipo_horario'] . ")";
        }
    }
    
    // Procesar foto si existe
    $filename = null;
    $foto_base64 = $_POST['verification_photo'] ?? $_POST['image_data'] ?? null;
    
    if ($foto_base64) {
        $uploads_dir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }
        
        $foto_base64_clean = preg_replace('#^data:image/\w+;base64,#i', '', $foto_base64);
        $img_data = base64_decode($foto_base64_clean);
        
        if ($img_data !== false) {
            $filename = 'facial_' . uniqid() . '_' . date('Ymd_His') . '.jpg';
            $save_path = $uploads_dir . $filename;
            file_put_contents($save_path, $img_data);
        }
    }
    
    // Insertar registro en asistencia
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

    $hasColumn = function (string $column) use ($asistenciaColumnCache): bool {
        return in_array($column, $asistenciaColumnCache, true);
    };

    $fields = ['ID_EMPLEADO', 'FECHA', 'TIPO', 'HORA', 'TARDANZA', 'OBSERVACION'];
    $values = [$id_empleado, $fecha, $tipo_registro, $hora, $tardanza, $observacion];

    if ($hasColumn('FOTO')) {
        $fields[] = 'FOTO';
        $values[] = $filename;
    }

    if ($hasColumn('REGISTRO_MANUAL')) {
        $fields[] = 'REGISTRO_MANUAL';
        $values[] = $registroManualFlag;
    }

    if ($hasColumn('VERIFICATION_METHOD')) {
        $fields[] = 'VERIFICATION_METHOD';
        $values[] = $verification_method;
    }

    if ($hasColumn('CONFIDENCE_SCORE')) {
        $fields[] = 'CONFIDENCE_SCORE';
        $values[] = $confidence_score;
    }

    if ($hasColumn('VERIFICATION_SUCCESS')) {
        $fields[] = 'VERIFICATION_SUCCESS';
        $values[] = $verificationSuccess;
    }

    if ($hasColumn('ID_HORARIO')) {
        $fields[] = 'ID_HORARIO';
        $values[] = $id_horario;
    }

    if ($hasColumn('ID_EMPLEADO_HORARIO')) {
        $fields[] = 'ID_EMPLEADO_HORARIO';
        $values[] = $id_empleado_horario;
    }

    if ($hasColumn('TIPO_HORARIO')) {
        $fields[] = 'TIPO_HORARIO';
        $values[] = $tipo_horario;
    }

    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $sql = 'INSERT INTO asistencia (' . implode(', ', $fields) . ') VALUES (' . $placeholders . ')';
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($values);
    
    if ($result) {
        $asistencia_id = $conn->lastInsertId();
        
        // Respuesta exitosa con información del horario
        $response = [
            'success' => true,
            'message' => "$tipo_registro registrada correctamente",
            'data' => [
                'id_asistencia' => $asistencia_id,
                'empleado' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'fecha' => $fecha,
                'hora' => $hora,
                'hora_original' => $horaOriginal,
                'tipo' => $tipo_registro,
                'hora_normalizada' => $hora,
                'hora_original' => $horaOriginal,
                'tardanza' => $tardanza,
                'verification_method' => $verification_method,
                'verification_success' => $verificationSuccess,
                'confidence_score' => $confidence_score,
                'foto_guardada' => !is_null($filename),
                'horario_usado' => $horario_usado,
                'observacion' => $observacion,
                'id_horario' => $id_horario,
                'id_empleado_horario' => $id_empleado_horario,
                'tipo_horario' => $tipo_horario,
                'registro_manual' => $registroManualFlag
            ]
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        throw new Exception('Error al insertar registro en base de datos');
    }
    
} catch (Exception $e) {
    // Limpiar output buffer en caso de error
    ob_clean();
    
    $error_response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($error_response);
    
    // Log del error
    error_log("Error en registro biométrico: " . $e->getMessage());
}
?>