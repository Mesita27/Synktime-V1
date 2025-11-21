<?php
/**
 * Endpoint de registro de asistencia biométrica
 * Compatible con el sistema existente
 */

// Headers para JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configurar zona horaria de Bogotá
require_once 'config/timezone.php';

// Verificar sesión
require_once 'auth/session.php';
requireAuth();

// Incluir configuración de base de datos
require_once 'config/database.php';
require_once 'utils/attendance_verification.php';

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

    // Validar tipo de registro
    if (!in_array($type, ['ENTRADA', 'SALIDA', 'DESCANSO_INICIO', 'DESCANSO_FIN'])) {
        throw new Exception('Tipo de registro inválido. Use ENTRADA, SALIDA, DESCANSO_INICIO o DESCANSO_FIN');
    }

    // Obtener información del usuario actual para validar empresa
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;

    if (!$empresaId) {
        throw new Exception('Sesión inválida - empresa no encontrada');
    }

    // Verificar que el empleado pertenece a la empresa
    $sqlVerifyEmployee = "
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.ACTIVO,
               s.ID_EMPRESA
        FROM empleado e
        INNER JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado 
        AND s.ID_EMPRESA = :empresa_id
        AND e.ACTIVO = 'S'
    ";
    
    $stmt = $pdo->prepare($sqlVerifyEmployee);
    $stmt->bindValue(':id_empleado', $employeeId);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        throw new Exception('Empleado no encontrado o sin permisos');
    }

    // Verificar si ya existe un registro para hoy
    $sqlCheck = "
        SELECT ID_ASISTENCIA, HORA 
        FROM asistencia 
        WHERE ID_EMPLEADO = :id_empleado 
        AND FECHA = :fecha 
        AND TIPO = :tipo
    ";
    
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindValue(':id_empleado', $employeeId);
    $stmtCheck->bindValue(':fecha', $fecha);
    $stmtCheck->bindValue(':tipo', $type);
    $stmtCheck->execute();
    
    $registroExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($registroExistente) {
        echo json_encode([
            'success' => false,
            'message' => "Ya existe un registro de $type para hoy a las {$registroExistente['HORA']}",
            'existing_record' => $registroExistente
        ]);
        exit;
    }

    // Insertar nuevo registro
    $sqlInsert = "
        INSERT INTO asistencia (
            ID_EMPLEADO, FECHA, HORA, TIPO, 
            VERIFICATION_METHOD, CREATED_AT
        ) VALUES (
            :id_empleado, :fecha, :hora, :tipo,
            :verification_method, NOW()
        )
    ";
    
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':id_empleado', $employeeId);
    $stmtInsert->bindValue(':fecha', $fecha);
    $stmtInsert->bindValue(':hora', $horaDb);
    $stmtInsert->bindValue(':tipo', $type);
    $stmtInsert->bindValue(':verification_method', $verificationMethod);
    $stmtInsert->execute();
    
    $idAsistencia = $pdo->lastInsertId();

    // Registrar log biométrico si hay datos de verificación
    if (!empty($verificationResults) && isset($verificationResults['confidence_score'])) {
        try {
            // Verificar si existe la tabla biometric_verification_log
            $tableExists = $pdo->query("SHOW TABLES LIKE 'biometric_verification_log'")->rowCount() > 0;
            
            if ($tableExists) {
                $sqlBiometric = "
                    INSERT INTO biometric_verification_log (
                        ID_EMPLEADO, ID_ASISTENCIA, VERIFICATION_METHOD, 
                        CONFIDENCE_SCORE, VERIFICATION_SUCCESS, 
                        BIOMETRIC_DATA, CREATED_AT
                    ) VALUES (
                        :id_empleado, :id_asistencia, :verification_method,
                        :confidence_score, :verification_success,
                        :biometric_data, NOW()
                    )
                ";
                
                $stmtBiometric = $pdo->prepare($sqlBiometric);
                $stmtBiometric->bindValue(':id_empleado', $employeeId);
                $stmtBiometric->bindValue(':id_asistencia', $idAsistencia);
                $stmtBiometric->bindValue(':verification_method', $verificationMethod);
                $stmtBiometric->bindValue(':confidence_score', $verificationResults['confidence_score']);
                $stmtBiometric->bindValue(':verification_success', $verificationResults['verification_success'] ? 1 : 0);
                $stmtBiometric->bindValue(':biometric_data', json_encode($verificationResults));
                $stmtBiometric->execute();
            }
        } catch (Exception $e) {
            // Log el error pero no fallar el registro principal
            error_log("Error registrando log biométrico: " . $e->getMessage());
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => "Asistencia de $type registrada exitosamente",
        'attendance_type' => $type,
        'data' => [
            'id_asistencia' => $idAsistencia,
            'empleado' => [
                'id' => $empleado['ID_EMPLEADO'],
                'nombre' => trim($empleado['NOMBRE'] . ' ' . ($empleado['APELLIDO'] ?? ''))
            ],
            'registro' => [
                'fecha' => $fecha,
                'hora' => $horaDb,
                'tipo' => $type,
                'verification_method' => $verificationMethod,
                'verification_method_original' => $verificationMethodOriginal
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en registro de asistencia biométrica: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => 'Error en el servidor'
    ]);
}
?>