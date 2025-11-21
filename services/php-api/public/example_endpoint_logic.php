<?php
/**
 * EJEMPLO: Cómo debería funcionar el endpoint de registro de asistencia
 * con el nuevo esquema empleado_horario_personalizado
 */

// Función para encontrar el horario personalizado de un empleado en una fecha
function findEmployeeSchedule($empleadoId, $fecha, $conn) {
    $diaSemana = date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
    
    $sql = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.ORDEN_TURNO
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = :empleado_id
        AND ehp.ID_DIA = :dia_semana
        AND ehp.ACTIVO = 'S'
        AND ehp.FECHA_DESDE <= :fecha
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
        ORDER BY ehp.ORDEN_TURNO
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':empleado_id' => $empleadoId,
        ':dia_semana' => $diaSemana,
        ':fecha' => $fecha
    ]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para registrar asistencia
function registerAttendance($empleadoId, $tipo, $hora, $fecha, $conn) {
    // 1. Buscar horario personalizado del empleado
    $horario = findEmployeeSchedule($empleadoId, $fecha, $conn);
    
    if (!$horario) {
        throw new Exception("No se encontró horario personalizado para el empleado en esta fecha");
    }
    
    // 2. Calcular si hay tardanza
    $tardanza = calculateLateness($tipo, $hora, $horario);
    
    // 3. Insertar registro de asistencia
    $sql = "
        INSERT INTO asistencia (
            ID_EMPLEADO,
            FECHA,
            TIPO,
            HORA,
            TARDANZA,
            ID_EMPLEADO_HORARIO,  -- Aquí va el FK al horario personalizado
            VERIFICATION_METHOD,
            CREATED_AT
        ) VALUES (
            :empleado_id,
            :fecha,
            :tipo,
            :hora,
            :tardanza,
            :id_empleado_horario,  -- FK constraint apunta a empleado_horario_personalizado
            :verification_method,
            NOW()
        )
    ";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        ':empleado_id' => $empleadoId,
        ':fecha' => $fecha,
        ':tipo' => $tipo,
        ':hora' => $hora,
        ':tardanza' => $tardanza,
        ':id_empleado_horario' => $horario['ID_EMPLEADO_HORARIO'], // Clave del horario personalizado
        ':verification_method' => 'traditional'
    ]);
}

// Función para calcular tardanza
function calculateLateness($tipo, $horaRegistro, $horario) {
    if ($tipo === 'ENTRADA') {
        $horaEsperada = $horario['HORA_ENTRADA'];
        $tolerancia = $horario['TOLERANCIA'];
        
        $tiempoEsperado = strtotime($horaEsperada);
        $tiempoRegistro = strtotime($horaRegistro);
        $tiempoLimite = $tiempoEsperado + ($tolerancia * 60);
        
        if ($tiempoRegistro > $tiempoLimite) {
            return 'S'; // Tardía
        } else {
            return 'N'; // Puntual
        }
    }
    
    return null; // Para salidas no calculamos tardanza aquí
}

/**
 * ESTRUCTURA DE ESQUEMA RECOMENDADA:
 * 
 * tabla: asistencia
 * ┌─────────────────────┬────────────────────────────────────┐
 * │ ID_ASISTENCIA       │ PK Auto increment                  │
 * │ ID_EMPLEADO         │ FK → empleado.ID_EMPLEADO          │
 * │ FECHA               │ Date                               │
 * │ TIPO                │ 'ENTRADA' / 'SALIDA'              │
 * │ HORA                │ Time                               │
 * │ TARDANZA            │ 'S' / 'N' / NULL                  │
 * │ ID_EMPLEADO_HORARIO │ FK → empleado_horario_personaliz.. │ ← IMPORTANTE
 * │ VERIFICATION_METHOD │ 'traditional' / 'facial' / etc    │
 * │ OBSERVACION         │ Text                               │
 * │ FOTO                │ VARCHAR (nombre archivo)          │
 * │ REGISTRO_MANUAL     │ 'S' / 'N'                         │
 * │ CREATED_AT          │ Timestamp                          │
 * └─────────────────────┴────────────────────────────────────┘
 * 
 * FK CONSTRAINT:
 * CONSTRAINT fk_asistencia_empleado_horario 
 * FOREIGN KEY (ID_EMPLEADO_HORARIO) 
 * REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) 
 * ON DELETE SET NULL 
 * ON UPDATE CASCADE
 */

// Ejemplo de uso
try {
    // Registrar entrada del empleado 100 hoy
    $empleadoId = 100;
    $fecha = date('Y-m-d');
    $hora = date('H:i');
    
    $result = registerAttendance($empleadoId, 'ENTRADA', $hora, $fecha, $conn);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Asistencia registrada correctamente'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>