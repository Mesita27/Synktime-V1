<?php
/**
 * ENDPOINT SIMPLIFICADO DE REGISTRO DE ASISTENCIA
 * Solo usa empleado_horario_personalizado (eliminamos complejidad legacy)
 */
require_once __DIR__ . '/../auth/session.php';
requireAuth();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    $idEmpleado = intval($input['id_empleado'] ?? 0);
    $tipo = strtoupper($input['tipo'] ?? ''); // ENTRADA o SALIDA
    $fecha = $input['fecha'] ?? date('Y-m-d');
    $hora = $input['hora'] ?? date('H:i:s');
    $observaciones = $input['observaciones'] ?? '';
    $metodoVerificacion = $input['verification_method'] ?? 'manual';
    $forzarRegistro = $input['forzar_registro'] ?? false;
    
    // Validaciones básicas
    if (!$idEmpleado) {
        throw new Exception('ID de empleado requerido');
    }
    
    if (!in_array($tipo, ['ENTRADA', 'SALIDA'])) {
        throw new Exception('Tipo de registro inválido. Debe ser ENTRADA o SALIDA');
    }
    
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        throw new Exception('Sesión inválida - empresa no encontrada');
    }
    
    // Verificar empleado
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
            SELECT ID_ASISTENCIA, HORA
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
    
    // Buscar horario personalizado aplicable
    $diaSemana = date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
    
    $sqlHorario = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA,
            ehp.ORDEN_TURNO,
            ehp.OBSERVACIONES
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = :id_empleado
        AND ehp.ID_DIA = :dia_semana
        AND ehp.ACTIVO = 'S'
        AND ehp.FECHA_DESDE <= :fecha
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
        ORDER BY ehp.ORDEN_TURNO ASC
        LIMIT 1
    ";
    
    $stmtHorario = $conn->prepare($sqlHorario);
    $stmtHorario->bindValue(':id_empleado', $idEmpleado);
    $stmtHorario->bindValue(':dia_semana', $diaSemana);
    $stmtHorario->bindValue(':fecha', $fecha);
    $stmtHorario->execute();
    
    $horarioInfo = $stmtHorario->fetch(PDO::FETCH_ASSOC);
    
    if (!$horarioInfo) {
        throw new Exception("No se encontró horario personalizado válido para el empleado en el día " . date('l', strtotime($fecha)));
    }
    
    // Calcular tardanza
    $horaReferencia = ($tipo === 'ENTRADA') ? $horarioInfo['HORA_ENTRADA'] : $horarioInfo['HORA_SALIDA'];
    $timestampReal = strtotime($hora);
    $timestampEsperado = strtotime($horaReferencia);
    $toleranciaSegundos = $horarioInfo['TOLERANCIA'] * 60;
    
    $diferencia = $timestampReal - $timestampEsperado;
    
    if ($tipo === 'ENTRADA') {
        $tardanza = ($diferencia > $toleranciaSegundos) ? 'S' : 'N';
    } else {
        $tardanza = ($diferencia < -$toleranciaSegundos) ? 'S' : 'N'; // Salida temprana
    }
    
    // Registrar asistencia
    $conn->beginTransaction();
    
    try {
        // Verificar nuevamente si existe (por si acaso)
        $sqlCheckAgain = "
            SELECT ID_ASISTENCIA 
            FROM asistencia 
            WHERE ID_EMPLEADO = :id_empleado 
            AND FECHA = :fecha 
            AND TIPO = :tipo
        ";
        
        $stmtCheckAgain = $conn->prepare($sqlCheckAgain);
        $stmtCheckAgain->bindValue(':id_empleado', $idEmpleado);
        $stmtCheckAgain->bindValue(':fecha', $fecha);
        $stmtCheckAgain->bindValue(':tipo', $tipo);
        $stmtCheckAgain->execute();
        
        $registroExistente = $stmtCheckAgain->fetch(PDO::FETCH_ASSOC);
        
        if ($registroExistente && $forzarRegistro) {
            // UPDATE registro existente
            $sqlUpdate = "
                UPDATE asistencia SET
                    HORA = :hora,
                    TARDANZA = :tardanza,
                    OBSERVACION = :observaciones,
                    VERIFICATION_METHOD = :metodo,
                    ID_EMPLEADO_HORARIO = :id_empleado_horario,
                    UPDATED_AT = NOW()
                WHERE ID_ASISTENCIA = :id_asistencia
            ";
            
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':hora', $hora);
            $stmtUpdate->bindValue(':tardanza', $tardanza);
            $stmtUpdate->bindValue(':observaciones', $observaciones);
            $stmtUpdate->bindValue(':metodo', $metodoVerificacion);
            $stmtUpdate->bindValue(':id_empleado_horario', $horarioInfo['ID_EMPLEADO_HORARIO']);
            $stmtUpdate->bindValue(':id_asistencia', $registroExistente['ID_ASISTENCIA']);
            $stmtUpdate->execute();
            
            $idAsistencia = $registroExistente['ID_ASISTENCIA'];
            $accion = 'actualizado';
            
        } elseif (!$registroExistente) {
            // INSERT nuevo registro
            $sqlInsert = "
                INSERT INTO asistencia (
                    ID_EMPLEADO, FECHA, HORA, TIPO, TARDANZA, 
                    OBSERVACION, ID_EMPLEADO_HORARIO, 
                    VERIFICATION_METHOD, CREATED_AT
                ) VALUES (
                    :id_empleado, :fecha, :hora, :tipo, :tardanza,
                    :observaciones, :id_empleado_horario,
                    :metodo, NOW()
                )
            ";
            
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bindValue(':id_empleado', $idEmpleado);
            $stmtInsert->bindValue(':fecha', $fecha);
            $stmtInsert->bindValue(':hora', $hora);
            $stmtInsert->bindValue(':tipo', $tipo);
            $stmtInsert->bindValue(':tardanza', $tardanza);
            $stmtInsert->bindValue(':observaciones', $observaciones);
            $stmtInsert->bindValue(':id_empleado_horario', $horarioInfo['ID_EMPLEADO_HORARIO']);
            $stmtInsert->bindValue(':metodo', $metodoVerificacion);
            $stmtInsert->execute();
            
            $idAsistencia = $conn->lastInsertId();
            $accion = 'creado';
            
        } else {
            throw new Exception("Registro ya existe y no se está forzando la actualización");
        }
        
        $conn->commit();
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => ucfirst(strtolower($tipo)) . ' registrada exitosamente',
            'data' => [
                'id_asistencia' => $idAsistencia,
                'empleado' => [
                    'id' => $empleado['ID_EMPLEADO'],
                    'nombre' => trim($empleado['NOMBRE'] . ' ' . ($empleado['APELLIDO'] ?? ''))
                ],
                'registro' => [
                    'fecha' => $fecha,
                    'hora' => $hora,
                    'tipo' => $tipo,
                    'tardanza' => $tardanza,
                    'accion' => $accion
                ],
                'horario' => [
                    'id_empleado_horario' => $horarioInfo['ID_EMPLEADO_HORARIO'],
                    'nombre' => $horarioInfo['NOMBRE_TURNO'],
                    'hora_entrada' => $horarioInfo['HORA_ENTRADA'],
                    'hora_salida' => $horarioInfo['HORA_SALIDA'],
                    'tolerancia' => $horarioInfo['TOLERANCIA']
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en registro de asistencia: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'employee_id' => $idEmpleado ?? null,
            'type' => $tipo ?? null,
            'date' => $fecha ?? null,
            'time' => $hora ?? null
        ]
    ]);
}
?>