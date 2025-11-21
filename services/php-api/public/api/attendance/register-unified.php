<?php
/**
 * ENDPOINT UNIFICADO DE REGISTRO DE ASISTENCIA
 * Maneja tanto horarios legacy como personalizados
 */
require_once __DIR__ . '/../auth/session.php';
requireAuth();
require_once __DIR__ . '/../config/timezone.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/horario_unificado.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    $idEmpleado = intval($input['id_empleado'] ?? 0);
    $tipo = strtoupper($input['tipo'] ?? ''); // ENTRADA o SALIDA
    $fecha = $input['fecha'] ?? date('Y-m-d');
    $hora = trim($input['hora'] ?? date('H:i:s'));
    $horaNormalizada = formatTimeForAttendance($hora);
    $observaciones = $input['observaciones'] ?? '';
    $metodoVerificacion = normalizeVerificationMethod($input['verification_method'] ?? 'manual');
    $forzarRegistro = $input['forzar_registro'] ?? false;
    
    // Validaciones básicas
    if (!$idEmpleado) {
        throw new Exception('ID de empleado requerido');
    }
    
    if (!in_array($tipo, ['ENTRADA', 'SALIDA'])) {
        throw new Exception('Tipo de registro inválido. Debe ser ENTRADA o SALIDA');
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
    
    $stmt = $conn->prepare($sqlVerifyEmployee);
    $stmt->bindValue(':id_empleado', $idEmpleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        throw new Exception('Empleado no encontrado o sin permisos');
    }
    
    // Verificar si ya existe un registro (si no se está forzando)
    if (!$forzarRegistro) {
        $sqlCheckExisting = "
            SELECT ID_ASISTENCIA, HORA, TIPO_HORARIO
            FROM asistencia 
            WHERE ID_EMPLEADO = :id_empleado 
            AND FECHA = :fecha 
            AND TIPO = :tipo
        ";
        
        $stmtCheck = $conn->prepare($sqlCheckExisting);
        $stmtCheck->bindValue(':id_empleado', $idEmpleado);
        $stmtCheck->bindValue(':fecha', $fecha);
        $stmtCheck->bindValue(':tipo', $tipo);
        $stmtCheck->execute();
        
        $registroExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($registroExistente) {
            echo json_encode([
                'success' => false,
                'message' => "Ya existe un registro de $tipo para esta fecha a las {$registroExistente['HORA']}",
                'registro_existente' => $registroExistente,
                'puede_forzar' => true
            ]);
            exit;
        }
    }
    
    // Registrar asistencia usando sistema unificado
    $resultado = registrarAsistenciaUnificada(
        $conn, 
        $idEmpleado, 
        $fecha, 
        $hora, 
        $tipo, 
    $observaciones, 
    $metodoVerificacion
    );
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => ucfirst(strtolower($tipo)) . ' registrada exitosamente',
        'data' => [
            'id_asistencia' => $resultado['id_asistencia'],
            'empleado' => [
                'id' => $empleado['ID_EMPLEADO'],
                'nombre' => trim($empleado['NOMBRE'] . ' ' . ($empleado['APELLIDO'] ?? ''))
            ],
            'registro' => [
                'fecha' => $fecha,
                'hora' => $resultado['hora_normalizada'] ?? $horaNormalizada,
                'hora_original' => $resultado['hora_original'] ?? $hora,
                'tipo' => $tipo,
                'tardanza' => $resultado['tardanza'],
                'accion' => $resultado['accion'],
                'verification_method' => $resultado['metodo'] ?? $metodoVerificacion
            ],
            'horario' => [
                'tipo' => $resultado['horario_info']['TIPO_HORARIO'],
                'nombre' => $resultado['horario_info']['NOMBRE_TURNO'],
                'hora_entrada' => $resultado['horario_info']['HORA_ENTRADA'],
                'hora_salida' => $resultado['horario_info']['HORA_SALIDA'],
                'tolerancia' => $resultado['horario_info']['TOLERANCIA']
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en registro de asistencia unificado: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'employee_id' => $idEmpleado ?? null,
            'type' => $tipo ?? null,
            'date' => $fecha ?? null,
            'time' => $hora ?? null,
            'verification_method' => $metodoVerificacion ?? null
        ]
    ]);
}
?>