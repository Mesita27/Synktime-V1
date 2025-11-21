<?php
header('Content-Type: application/json');

try {
    // Incluir configuración de base de datos
    require_once '../config/database.php';
    
    // El $pdo ya está configurado en database.php
    
    // Consulta para obtener asistencias con información de horarios personalizados
    $sql = "
        SELECT 
            a.ID_ASISTENCIA,
            a.ID_EMPLEADO,
            a.FECHA,
            a.HORA,
            a.TIPO,
            a.TARDANZA,
            a.FOTO,
            a.OBSERVACION,
            a.ID_HORARIO,
            a.ID_EMPLEADO_HORARIO,
            
            -- Información del empleado
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            
            -- Información del establecimiento
            est.NOMBRE as establecimiento,
            s.NOMBRE as sede,
            
            -- Información del horario tradicional (si aplica)
            h.NOMBRE as HORARIO_NOMBRE,
            h.HORA_ENTRADA as HORA_ENTRADA_TRADICIONAL,
            h.HORA_SALIDA as HORA_SALIDA_TRADICIONAL,
            h.TOLERANCIA,
            
            -- Información del horario personalizado (si aplica)
            ehp.NOMBRE_TURNO,
            ehp.HORA_ENTRADA as HORA_ENTRADA_PERSONALIZADO,
            ehp.HORA_SALIDA as HORA_SALIDA_PERSONALIZADO,
            ehp.ACTIVO as HORARIO_PERSONALIZADO_ACTIVO,
            ds.NOMBRE as DIA_NOMBRE,
            ehp.ORDEN_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA
            
        FROM asistencia a
        
        -- JOIN obligatorio con empleado
        INNER JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        
        -- JOIN con establecimiento
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        
        -- JOIN con horario tradicional (opcional)
        LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
        
        -- JOIN con horario personalizado (opcional)
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        LEFT JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        
        WHERE a.FECHA >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY a.FECHA DESC, a.HORA DESC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar los datos para incluir información detallada
    $asistenciasConInfo = array_map(function($asistencia) {
        // Determinar qué tipo de horario se está usando
        $tipoHorario = 'ninguno';
        $infoHorario = [];
        
        if ($asistencia['ID_EMPLEADO_HORARIO']) {
            $tipoHorario = 'personalizado';
            $infoHorario = [
                'tipo' => 'personalizado',
                'nombre' => $asistencia['NOMBRE_TURNO'] ?? 'Turno Personalizado',
                'hora_entrada' => $asistencia['HORA_ENTRADA_PERSONALIZADO'],
                'hora_salida' => $asistencia['HORA_SALIDA_PERSONALIZADO'],
                'activo' => $asistencia['HORARIO_PERSONALIZADO_ACTIVO'],
                'dia' => $asistencia['DIA_NOMBRE'],
                'orden' => $asistencia['ORDEN_TURNO'],
                'fecha_desde' => $asistencia['FECHA_DESDE'],
                'fecha_hasta' => $asistencia['FECHA_HASTA'],
                'id_empleado_horario' => $asistencia['ID_EMPLEADO_HORARIO']
            ];
        } elseif ($asistencia['ID_HORARIO']) {
            $tipoHorario = 'tradicional';
            $infoHorario = [
                'tipo' => 'tradicional',
                'nombre' => $asistencia['HORARIO_NOMBRE'] ?? 'Horario Fijo',
                'hora_entrada' => $asistencia['HORA_ENTRADA_TRADICIONAL'],
                'hora_salida' => $asistencia['HORA_SALIDA_TRADICIONAL'],
                'tolerancia' => $asistencia['TOLERANCIA'],
                'id_horario' => $asistencia['ID_HORARIO']
            ];
        }
        
        return [
            'ID_ASISTENCIA' => $asistencia['ID_ASISTENCIA'],
            'ID_EMPLEADO' => $asistencia['ID_EMPLEADO'],
            'NOMBRE' => $asistencia['NOMBRE'] . ' ' . $asistencia['APELLIDO'],
            'DNI' => $asistencia['DNI'],
            'establecimiento' => $asistencia['establecimiento'],
            'sede' => $asistencia['sede'],
            'FECHA' => $asistencia['FECHA'],
            'HORA' => $asistencia['HORA'],
            'TIPO' => $asistencia['TIPO'],
            'TARDANZA' => $asistencia['TARDANZA'],
            'FOTO' => $asistencia['FOTO'],
            'OBSERVACION' => $asistencia['OBSERVACION'],
            'tipo_horario' => $tipoHorario,
            'info_horario' => $infoHorario,
            
            // Para compatibilidad con el frontend existente
            'ID_HORARIO' => $asistencia['ID_HORARIO'],
            'ID_EMPLEADO_HORARIO' => $asistencia['ID_EMPLEADO_HORARIO'],
            'HORARIO_NOMBRE' => $infoHorario['nombre'] ?? 'Sin horario',
            'HORA_ENTRADA_PROGRAMADA' => $infoHorario['hora_entrada'] ?? null,
            'HORA_SALIDA_PROGRAMADA' => $infoHorario['hora_salida'] ?? null,
            'TOLERANCIA' => $asistencia['TOLERANCIA'] ?? 0
        ];
    }, $asistencias);
    
    // Estadísticas
    $stats = [
        'total_registros' => count($asistenciasConInfo),
        'con_horario_personalizado' => count(array_filter($asistenciasConInfo, fn($a) => $a['tipo_horario'] === 'personalizado')),
        'con_horario_tradicional' => count(array_filter($asistenciasConInfo, fn($a) => $a['tipo_horario'] === 'tradicional')),
        'sin_horario' => count(array_filter($asistenciasConInfo, fn($a) => $a['tipo_horario'] === 'ninguno')),
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $asistenciasConInfo,
        'estadisticas' => $stats,
        'sql_usado' => $sql,
        'mensaje' => 'Asistencias obtenidas con información de horarios personalizado y tradicional'
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