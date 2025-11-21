<?php

require_once __DIR__ . '/../utils/attendance_verification.php';
/**
 * FUNCIÓN UNIFICADA PARA BÚSQUEDA DE HORARIOS
 * Busca horarios tanto en sistema legacy como personalizado
 * 
 * @param PDO $conn Conexión a base de datos
 * @param int $idEmpleado ID del empleado
 * @param string $fecha Fecha en formato Y-m-d
 * @param string $tipoRegistro 'ENTRADA' o 'SALIDA'
 * @return array|null Información del horario aplicable
 */
function buscarHorarioUnificado($conn, $idEmpleado, $fecha, $tipoRegistro = 'ENTRADA') {
    $diaSemana = date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
    
    // PRIORIDAD 1: Buscar horarios personalizados activos
    $sqlPersonalizado = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO as ID_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA,
            ehp.ORDEN_TURNO,
            'personalizado' as TIPO_HORARIO,
            ehp.OBSERVACIONES
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = :id_empleado
        AND ehp.ID_DIA = :dia_semana
        AND ehp.ACTIVO = 'S'
        AND ehp.FECHA_DESDE <= :fecha
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
        ORDER BY ehp.ORDEN_TURNO ASC
    ";
    
    $stmtPersonalizado = $conn->prepare($sqlPersonalizado);
    $stmtPersonalizado->bindValue(':id_empleado', $idEmpleado);
    $stmtPersonalizado->bindValue(':dia_semana', $diaSemana);
    $stmtPersonalizado->bindValue(':fecha', $fecha);
    $stmtPersonalizado->execute();
    
    $horariosPersonalizados = $stmtPersonalizado->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($horariosPersonalizados)) {
        // Devolver el primer horario personalizado encontrado
        return $horariosPersonalizados[0];
    }
    
    // PRIORIDAD 2: Buscar en sistema legacy si no hay personalizados
    $sqlLegacy = "
        SELECT 
            h.ID_HORARIO,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            COALESCE(h.TOLERANCIA, 15) as TOLERANCIA,
            h.NOMBRE as NOMBRE_TURNO,
            NULL as FECHA_DESDE,
            NULL as FECHA_HASTA,
            1 as ORDEN_TURNO,
            'legacy' as TIPO_HORARIO,
            NULL as OBSERVACIONES
        FROM empleado e
        INNER JOIN horario h ON e.ID_HORARIO = h.ID_HORARIO
        WHERE e.ID_EMPLEADO = :id_empleado
        AND e.ACTIVO = 'S'
    ";
    
    $stmtLegacy = $conn->prepare($sqlLegacy);
    $stmtLegacy->bindValue(':id_empleado', $idEmpleado);
    $stmtLegacy->execute();
    
    $horarioLegacy = $stmtLegacy->fetch(PDO::FETCH_ASSOC);
    
    return $horarioLegacy ?: null;
}

/**
 * FUNCIÓN PARA REGISTRAR ASISTENCIA UNIFICADA
 * Registra asistencia usando el sistema de horarios unificado
 */
function registrarAsistenciaUnificada($conn, $idEmpleado, $fecha, $hora, $tipo, $observaciones = '', $metodoVerificacion = 'manual') {
    $metodoVerificacion = normalizeVerificationMethod($metodoVerificacion);
    if (!function_exists('formatTimeForAttendance')) {
        require_once __DIR__ . '/../config/timezone.php';
    }

    $horaOriginal = trim($hora);
    $horaNormalizada = formatTimeForAttendance($horaOriginal);
    $horaParaCalculo = strlen($horaOriginal) === 5 ? $horaOriginal . ':00' : $horaOriginal;
    // Buscar horario aplicable
    $horarioInfo = buscarHorarioUnificado($conn, $idEmpleado, $fecha, $tipo);
    
    if (!$horarioInfo) {
        throw new Exception("No se encontró horario válido para el empleado en la fecha especificada");
    }
    
    // Calcular tardanza
    $horaReferencia = ($tipo === 'ENTRADA') ? $horarioInfo['HORA_ENTRADA'] : $horarioInfo['HORA_SALIDA'];
    $tardanza = calcularTardanza($horaParaCalculo, $horaReferencia, $horarioInfo['TOLERANCIA'], $tipo);
    
    // Preparar datos para inserción
    $idHorarioParaGuardar = $horarioInfo['ID_HORARIO'];
    $tipoHorario = $horarioInfo['TIPO_HORARIO'];
    
    // Verificar si ya existe registro
    $sqlCheck = "
        SELECT ID_ASISTENCIA, HORA 
        FROM asistencia 
        WHERE ID_EMPLEADO = :id_empleado 
        AND FECHA = :fecha 
        AND TIPO = :tipo
    ";
    
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bindValue(':id_empleado', $idEmpleado);
    $stmtCheck->bindValue(':fecha', $fecha);
    $stmtCheck->bindValue(':tipo', $tipo);
    $stmtCheck->execute();
    
    $registroExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($registroExistente) {
        // UPDATE registro existente
        $sqlUpdate = "
            UPDATE asistencia SET
                HORA = :hora,
                TARDANZA = :tardanza,
                OBSERVACION = :observaciones,
                VERIFICATION_METHOD = :metodo,
                ID_HORARIO = :id_horario,
                TIPO_HORARIO = :tipo_horario,
                UPDATED_AT = NOW()
            WHERE ID_ASISTENCIA = :id_asistencia
        ";
        
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':hora', $horaNormalizada);
        $stmtUpdate->bindValue(':tardanza', $tardanza);
        $stmtUpdate->bindValue(':observaciones', $observaciones);
    $stmtUpdate->bindValue(':metodo', $metodoVerificacion);
        $stmtUpdate->bindValue(':id_horario', $idHorarioParaGuardar);
        $stmtUpdate->bindValue(':tipo_horario', $tipoHorario);
        $stmtUpdate->bindValue(':id_asistencia', $registroExistente['ID_ASISTENCIA']);
        $stmtUpdate->execute();
        
        $idAsistencia = $registroExistente['ID_ASISTENCIA'];
        
    } else {
        // INSERT nuevo registro
        $sqlInsert = "
            INSERT INTO asistencia (
                ID_EMPLEADO, FECHA, HORA, TIPO, TARDANZA, 
                OBSERVACION, ID_HORARIO, TIPO_HORARIO, 
                VERIFICATION_METHOD, CREATED_AT
            ) VALUES (
                :id_empleado, :fecha, :hora, :tipo, :tardanza,
                :observaciones, :id_horario, :tipo_horario,
                :metodo, NOW()
            )
        ";
        
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bindValue(':id_empleado', $idEmpleado);
    $stmtInsert->bindValue(':fecha', $fecha);
    $stmtInsert->bindValue(':hora', $horaNormalizada);
        $stmtInsert->bindValue(':tipo', $tipo);
        $stmtInsert->bindValue(':tardanza', $tardanza);
        $stmtInsert->bindValue(':observaciones', $observaciones);
        $stmtInsert->bindValue(':id_horario', $idHorarioParaGuardar);
        $stmtInsert->bindValue(':tipo_horario', $tipoHorario);
    $stmtInsert->bindValue(':metodo', $metodoVerificacion);
        $stmtInsert->execute();
        
        $idAsistencia = $conn->lastInsertId();
    }
    
    return [
        'success' => true,
        'id_asistencia' => $idAsistencia,
        'tardanza' => $tardanza,
        'horario_info' => $horarioInfo,
        'hora_normalizada' => $horaNormalizada,
        'hora_original' => $horaOriginal,
        'accion' => $registroExistente ? 'actualizado' : 'creado',
        'metodo' => $metodoVerificacion
    ];
}

/**
 * FUNCIÓN PARA CALCULAR TARDANZA
 */
function calcularTardanza($horaReal, $horaEsperada, $tolerancia, $tipo) {
    $timestampReal = strtotime($horaReal);
    $timestampEsperado = strtotime($horaEsperada);
    $toleranciaSegundos = $tolerancia * 60;
    
    $diferencia = $timestampReal - $timestampEsperado;
    
    if ($tipo === 'ENTRADA') {
        // Para entrada: tardanza si llega después de la hora + tolerancia
        if ($diferencia <= $toleranciaSegundos) {
            return 'N'; // Normal
        } else {
            return 'S'; // Tardanza
        }
    } else {
        // Para salida: tardanza si sale antes de la hora - tolerancia
        if ($diferencia >= -$toleranciaSegundos) {
            return 'N'; // Normal
        } else {
            return 'S'; // Salida temprana
        }
    }
}
?>