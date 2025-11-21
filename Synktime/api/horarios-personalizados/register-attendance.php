<?php

// Configurar zona horaria de Bogotá, Colombia
require_once __DIR__ . '/../../config/timezone.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/attendance_verification.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    $idEmpleado = $input['id_empleado'] ?? null;
    $fecha = $input['fecha'] ?? getBogotaDate();
    $hora = trim($input['hora'] ?? getBogotaTime());
    $horaFormateada = formatTimeForAttendance($hora);
    $tipoRegistro = strtoupper($input['tipo'] ?? 'ENTRADA');
    $metodoRegistroOriginal = $input['metodo'] ?? 'manual'; // manual, biometrico, facial
    $metodoRegistro = normalizeVerificationMethod($metodoRegistroOriginal);
    $observaciones = $input['observaciones'] ?? null;
    $forzarRegistro = $input['forzar'] ?? false; // Para registros fuera de horario

    // Validaciones básicas
    if (!$idEmpleado) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit;
    }

    if (!in_array($tipoRegistro, ['ENTRADA', 'SALIDA'])) {
        echo json_encode(['success' => false, 'message' => 'Tipo de registro inválido']);
        exit;
    }

    // Validar formato de fecha y hora
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit;
    }

    if (!preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $hora)) {
        echo json_encode(['success' => false, 'message' => 'Formato de hora inválido']);
        exit;
    }

    // Verificar que el empleado pertenece a la empresa
    $sqlVerifyEmployee = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            e.ID_ESTABLECIMIENTO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
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
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    // Verificar si ya existe un registro de este tipo para esta fecha
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
    $stmtCheck->bindValue(':tipo', $tipoRegistro);
    $stmtCheck->execute();
    $registroExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($registroExistente && !$forzarRegistro) {
        echo json_encode([
            'success' => false, 
            'message' => "Ya existe un registro de $tipoRegistro para esta fecha a las {$registroExistente['HORA']}",
            'registro_existente' => [
                'id' => $registroExistente['ID_ASISTENCIA'],
                'hora' => $registroExistente['HORA']
            ]
        ]);
        exit;
    }

    // Obtener información del horario aplicable
    $diaSemana = date('N');
    $horarioInfo = null;
    $tardanza = 'N'; // Por defecto normal

    // Buscar horarios personalizados primero
    $sqlPersonalizados = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.ORDEN_TURNO
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = :id_empleado
        AND ehp.ID_DIA = :dia_semana
        AND ehp.ACTIVO = 'S'
        AND ehp.FECHA_DESDE <= :fecha
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
        ORDER BY ehp.ORDEN_TURNO
    ";

    $stmtPersonalizados = $conn->prepare($sqlPersonalizados);
    $stmtPersonalizados->bindValue(':id_empleado', $idEmpleado);
    $stmtPersonalizados->bindValue(':dia_semana', $diaSemana);
    $stmtPersonalizados->bindValue(':fecha', $fecha);
    $stmtPersonalizados->execute();
    $horariosPersonalizados = $stmtPersonalizados->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($horariosPersonalizados)) {
        // Usar horarios personalizados
        if ($tipoRegistro === 'ENTRADA') {
            $horarioInfo = $horariosPersonalizados[0]; // Primer turno
        } else {
            // Para salida, encontrar el turno correspondiente
            $horaActual = $hora;
            foreach ($horariosPersonalizados as $horario) {
                if ($horaActual >= $horario['HORA_ENTRADA']) {
                    $horarioInfo = $horario;
                } else {
                    break;
                }
            }
            if (!$horarioInfo) {
                $horarioInfo = end($horariosPersonalizados);
            }
        }
    } else {
        // Buscar horario tradicional
        $sqlTradicional = "
            SELECT 
                h.ID_HORARIO,
                h.HORA_ENTRADA,
                h.HORA_SALIDA,
                h.TOLERANCIA,
                h.NOMBRE
            FROM empleado_horario eh
            JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
            JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO
            WHERE eh.ID_EMPLEADO = :id_empleado
            AND hd.ID_DIA = :dia_semana
            AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
            AND eh.FECHA_DESDE <= :fecha
            ORDER BY eh.FECHA_DESDE DESC
            LIMIT 1
        ";

        $stmtTradicional = $conn->prepare($sqlTradicional);
        $stmtTradicional->bindValue(':id_empleado', $idEmpleado);
        $stmtTradicional->bindValue(':dia_semana', $diaSemana);
        $stmtTradicional->bindValue(':fecha', $fecha);
        $stmtTradicional->execute();
        $horarioInfo = $stmtTradicional->fetch(PDO::FETCH_ASSOC);
    }

    // Calcular tardanza y horas extras si hay horario configurado
    if ($horarioInfo) {
    $toleranciaMinutos = normalizarToleranciaMinutos($horarioInfo['TOLERANCIA'] ?? 0);
    $toleranciaSegundos = $toleranciaMinutos * 60;
        $horaReferencia = $tipoRegistro === 'ENTRADA' ? 
            $horarioInfo['HORA_ENTRADA'] : 
            $horarioInfo['HORA_SALIDA'];
        
        $tsRegistro = strtotime($fecha . ' ' . $hora);
        $tsReferencia = strtotime($fecha . ' ' . $horaReferencia);
        
        $diferencia = $tsRegistro - $tsReferencia;
        
        if ($tipoRegistro === 'ENTRADA') {
            if ($diferencia > $toleranciaSegundos) {
                $tardanza = 'S'; // Tarde
            } elseif ($diferencia < -$toleranciaSegundos) {
                $tardanza = 'T'; // Temprano
            }
        } else {
            if ($diferencia < -$toleranciaSegundos) {
                $tardanza = 'T'; // Salida temprana
            } elseif ($diferencia > $toleranciaSegundos) {
                $tardanza = 'S'; // Salida tardía
                // Nota: horas extras se calculan pero no se almacenan en esta tabla
            }
        }
    }

    // Preparar datos para inserción
    $fechaRegistro = getBogotaDateTime();

    if ($registroExistente && $forzarRegistro) {
        // Actualizar registro existente
        $sqlUpdate = "
            UPDATE asistencia SET
                HORA = :hora,
                TARDANZA = :tardanza,
                OBSERVACION = :observaciones,
                VERIFICATION_METHOD = :metodo,
                ID_HORARIO = :id_horario
            WHERE ID_ASISTENCIA = :id_asistencia
        ";

    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':hora', $horaFormateada);
        $stmtUpdate->bindValue(':tardanza', $tardanza);
    $stmtUpdate->bindValue(':metodo', $metodoRegistro);
        // CORREGIDO: Manejar correctamente los IDs de horarios
        $idHorarioParaGuardar = null;
        if ($horarioInfo) {
            if (isset($horarioInfo['ID_HORARIO'])) {
                // Horario tradicional - usar ID_HORARIO
                $idHorarioParaGuardar = $horarioInfo['ID_HORARIO'];
            } elseif (isset($horarioInfo['ID_EMPLEADO_HORARIO'])) {
                // Horario personalizado - usar NULL para evitar constraint violation
                // La información del horario personalizado se guarda en observaciones
                $idHorarioParaGuardar = null;
                if (empty($observaciones)) {
                    $observaciones = "Horario personalizado: " . ($horarioInfo['NOMBRE_TURNO'] ?? 'Sin nombre');
                } else {
                    $observaciones .= " [Horario personalizado: " . ($horarioInfo['NOMBRE_TURNO'] ?? 'Sin nombre') . "]";
                }
            }
        }
        $stmtUpdate->bindValue(':observaciones', $observaciones);
        $stmtUpdate->bindValue(':id_horario', $idHorarioParaGuardar);
        $stmtUpdate->bindValue(':id_asistencia', $registroExistente['ID_ASISTENCIA']);
        
        $resultado = $stmtUpdate->execute();
        $idAsistencia = $registroExistente['ID_ASISTENCIA'];
        $operacion = 'actualizado';

    } else {
        // Crear nuevo registro
        $sqlInsert = "
            INSERT INTO asistencia (
                ID_EMPLEADO,
                FECHA,
                HORA,
                TIPO,
                TARDANZA,
                OBSERVACION,
                VERIFICATION_METHOD,
                REGISTRO_MANUAL,
                ID_HORARIO
            ) VALUES (
                :id_empleado,
                :fecha,
                :hora,
                :tipo,
                :tardanza,
                :observaciones,
                :metodo,
                'N',
                :id_horario
            )
        ";

    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bindValue(':id_empleado', $idEmpleado);
    $stmtInsert->bindValue(':fecha', $fecha);
    $stmtInsert->bindValue(':hora', $horaFormateada);
        $stmtInsert->bindValue(':tipo', $tipoRegistro);
        $stmtInsert->bindValue(':tardanza', $tardanza);
    $stmtInsert->bindValue(':metodo', $metodoRegistro);
        // CORREGIDO: Manejar correctamente los IDs de horarios
        $idHorarioParaGuardar = null;
        if ($horarioInfo) {
            if (isset($horarioInfo['ID_HORARIO'])) {
                // Horario tradicional - usar ID_HORARIO
                $idHorarioParaGuardar = $horarioInfo['ID_HORARIO'];
            } elseif (isset($horarioInfo['ID_EMPLEADO_HORARIO'])) {
                // Horario personalizado - usar NULL para evitar constraint violation
                // La información del horario personalizado se guarda en observaciones
                $idHorarioParaGuardar = null;
                if (empty($observaciones)) {
                    $observaciones = "Horario personalizado: " . ($horarioInfo['NOMBRE_TURNO'] ?? 'Sin nombre');
                } else {
                    $observaciones .= " [Horario personalizado: " . ($horarioInfo['NOMBRE_TURNO'] ?? 'Sin nombre') . "]";
                }
            }
        }
        $stmtInsert->bindValue(':observaciones', $observaciones);
        $stmtInsert->bindValue(':id_horario', $idHorarioParaGuardar);
        
        $resultado = $stmtInsert->execute();
        $idAsistencia = $conn->lastInsertId();
        $operacion = 'registrado';
    }

    if ($resultado) {
        // Preparar información de respuesta
        $estadoDescripcion = '';
        switch ($tardanza) {
            case 'S': $estadoDescripcion = $tipoRegistro === 'ENTRADA' ? 'Tarde' : 'Horas extras'; break;
            case 'T': $estadoDescripcion = $tipoRegistro === 'ENTRADA' ? 'Temprano' : 'Salida temprana'; break;
            default: $estadoDescripcion = 'Puntual'; break;
        }

        echo json_encode([
            'success' => true,
            'message' => "Asistencia $operacion correctamente",
            'registro' => [
                'id_asistencia' => (int)$idAsistencia,
                'empleado' => [
                    'id' => (int)$empleado['ID_EMPLEADO'],
                    'nombre_completo' => trim($empleado['NOMBRE'] . ' ' . $empleado['APELLIDO']),
                    'dni' => $empleado['DNI']
                ],
                'fecha' => $fecha,
                'hora' => $horaFormateada,
                'hora_original' => $hora,
                'tipo' => $tipoRegistro,
                'estado' => $estadoDescripcion,
                'tardanza_codigo' => $tardanza,
                'metodo' => $metodoRegistro,
                'metodo_original' => $metodoRegistroOriginal,
                'operacion' => $operacion
            ],
            'horario_info' => $horarioInfo ? [
                'nombre' => $horarioInfo['NOMBRE_TURNO'] ?? $horarioInfo['NOMBRE'] ?? 'Horario estándar',
                'hora_entrada' => $horarioInfo['HORA_ENTRADA'],
                'hora_salida' => $horarioInfo['HORA_SALIDA'],
                'tolerancia' => (int)($horarioInfo['TOLERANCIA'] ?? 0),
                'tipo' => isset($horarioInfo['NOMBRE_TURNO']) ? 'personalizado' : 'tradicional'
            ] : null,
            'timestamp' => $fechaRegistro
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el registro de asistencia']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>