<?php
header('Content-Type: application/json');

try {
    // Incluir configuración de base de datos
    require_once '../config/database.php';
    
    // Consulta simple para probar la estructura de datos
    $sql = "
        SELECT 
            a.ID_ASISTENCIA as id,
            e.ID_EMPLEADO as codigo_empleado,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_empleado,
            est.NOMBRE as establecimiento,
            s.NOMBRE as sede,
            a.FECHA as fecha,
            a.HORA as hora,
            a.TIPO as tipo,
            a.TARDANZA as tardanza,
            a.OBSERVACION as observacion,
            a.FOTO as foto,
            a.REGISTRO_MANUAL as registro_manual,
            
            -- Información del horario tradicional
            a.ID_HORARIO,
            h.NOMBRE AS HORARIO_NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            
            -- Información del horario personalizado
            a.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO,
            ehp.HORA_ENTRADA as HORA_ENTRADA_PERSONALIZADO,
            ehp.HORA_SALIDA as HORA_SALIDA_PERSONALIZADO,
            ehp.ACTIVO as HORARIO_PERSONALIZADO_ACTIVO,
            ds.NOMBRE as DIA_NOMBRE,
            ehp.ORDEN_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA
            
        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN horario h ON h.ID_HORARIO = a.ID_HORARIO
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        LEFT JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        WHERE a.FECHA >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY CONCAT(a.FECHA, ' ', a.HORA) DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar asistencias para normalizar información de horarios
    $result = [];
    foreach ($asistencias as $asistencia) {
        $estado = '--';
        $tolerancia = 0;
        $hora_entrada_programada = null;
        $hora_salida_programada = null;
        $nombre_horario = 'Sin horario';
        $tipo_horario = 'ninguno';
        
        // Determinar tipo de horario y obtener información
        if ($asistencia['ID_EMPLEADO_HORARIO']) {
            // Horario personalizado
            $tipo_horario = 'personalizado';
            $hora_entrada_programada = $asistencia['HORA_ENTRADA_PERSONALIZADO'];
            $hora_salida_programada = $asistencia['HORA_SALIDA_PERSONALIZADO'];
            $nombre_horario = $asistencia['NOMBRE_TURNO'] ?? 'Turno Personalizado';
            $tolerancia = 5; // Tolerancia por defecto para horarios personalizados
        } elseif ($asistencia['ID_HORARIO']) {
            // Horario tradicional
            $tipo_horario = 'tradicional';
            $hora_entrada_programada = $asistencia['HORA_ENTRADA'];
            $hora_salida_programada = $asistencia['HORA_SALIDA'];
            $nombre_horario = $asistencia['HORARIO_NOMBRE'] ?? 'Horario Fijo';
            $tolerancia = (int)($asistencia['TOLERANCIA'] ?? 0);
        }
        
        // Calcular estado si hay horario asignado
        if ($tipo_horario !== 'ninguno') {
            if ($asistencia['tipo'] === 'ENTRADA' && $hora_entrada_programada) {
                // Calcular estado de entrada
                $ts_entrada_programada = strtotime($asistencia['fecha'] . ' ' . $hora_entrada_programada);
                $ts_entrada_real = strtotime($asistencia['fecha'] . ' ' . $asistencia['hora']);
                
                if ($ts_entrada_real < $ts_entrada_programada - $tolerancia * 60) {
                    $estado = 'Temprano';
                } elseif ($ts_entrada_real <= $ts_entrada_programada + $tolerancia * 60) {
                    $estado = 'Puntual';
                } else {
                    $estado = 'Tardanza';
                }
            } elseif ($asistencia['tipo'] === 'SALIDA' && $hora_salida_programada) {
                // Calcular estado de salida
                $ts_salida_programada = strtotime($asistencia['fecha'] . ' ' . $hora_salida_programada);
                $ts_salida_real = strtotime($asistencia['fecha'] . ' ' . $asistencia['hora']);

                if ($ts_salida_real < $ts_salida_programada - $tolerancia * 60) {
                    $estado = 'Temprano';
                } elseif ($ts_salida_real <= $ts_salida_programada + $tolerancia * 60) {
                    $estado = 'Puntual';
                } else {
                    $estado = 'Tardanza';
                }
            }
        }
        
        // Normalizar los datos para el frontend
        $asistencia['estado'] = $estado;
        $asistencia['tipo_horario'] = $tipo_horario;
        $asistencia['HORARIO_NOMBRE'] = $nombre_horario;
        $asistencia['HORA_ENTRADA_PROGRAMADA'] = $hora_entrada_programada;
        $asistencia['HORA_SALIDA_PROGRAMADA'] = $hora_salida_programada;
        $asistencia['TOLERANCIA'] = $tolerancia;
        
        $result[] = $asistencia;
    }

    // Estadísticas
    $stats = [
        'total_registros' => count($result),
        'con_horario_personalizado' => count(array_filter($result, fn($a) => $a['tipo_horario'] === 'personalizado')),
        'con_horario_tradicional' => count(array_filter($result, fn($a) => $a['tipo_horario'] === 'tradicional')),
        'sin_horario' => count(array_filter($result, fn($a) => $a['tipo_horario'] === 'ninguno')),
    ];

    echo json_encode([
        'success' => true,
        'data' => $result,
        'estadisticas' => $stats,
        'mensaje' => 'Datos de asistencias con estructura adaptada para el frontend'
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage(),
        'linea' => $e->getLine()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error general: ' . $e->getMessage()
    ]);
}
?>