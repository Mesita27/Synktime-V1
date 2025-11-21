<?php
/**
 * ENDPOINT UNIFICADO PARA CÁLCULO DE HORAS TRABAJADAS
 * Incluye tanto horarios legacy como personalizados
 */
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        throw new Exception('Sesión inválida');
    }
    
    // Parámetros de filtro
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    $empleadoId = $_GET['empleado_id'] ?? null;
    
    // Query unificada que incluye ambos sistemas de horarios
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            est.NOMBRE as ESTABLECIMIENTO,
            s.NOMBRE as SEDE,
            a_fecha.FECHA,
            
            -- Información del horario (legacy o personalizado)
            CASE 
                WHEN a_fecha.TIPO_HORARIO = 'personalizado' THEN ehp.NOMBRE_TURNO
                WHEN a_fecha.TIPO_HORARIO = 'legacy' THEN h.NOMBRE
                ELSE 'Sin horario'
            END as HORARIO_NOMBRE,
            
            CASE 
                WHEN a_fecha.TIPO_HORARIO = 'personalizado' THEN ehp.HORA_ENTRADA
                WHEN a_fecha.TIPO_HORARIO = 'legacy' THEN h.HORA_ENTRADA
                ELSE NULL
            END as HORA_ENTRADA_PROGRAMADA,
            
            CASE 
                WHEN a_fecha.TIPO_HORARIO = 'personalizado' THEN ehp.HORA_SALIDA
                WHEN a_fecha.TIPO_HORARIO = 'legacy' THEN h.HORA_SALIDA
                ELSE NULL
            END as HORA_SALIDA_PROGRAMADA,
            
            -- Registros de entrada
            entrada.HORA as ENTRADA_HORA,
            entrada.TARDANZA as ENTRADA_TARDANZA,
            entrada.OBSERVACION as ENTRADA_OBSERVACION,
            
            -- Registros de salida
            salida.HORA as SALIDA_HORA,
            salida.TARDANZA as SALIDA_TARDANZA,
            salida.OBSERVACION as SALIDA_OBSERVACION,
            
            -- Cálculo de horas trabajadas
            CASE 
                WHEN entrada.HORA IS NOT NULL AND salida.HORA IS NOT NULL THEN
                    ROUND(
                        (UNIX_TIMESTAMP(CONCAT(a_fecha.FECHA, ' ', salida.HORA)) - 
                         UNIX_TIMESTAMP(CONCAT(a_fecha.FECHA, ' ', entrada.HORA))) / 3600, 2
                    )
                ELSE NULL
            END as HORAS_TRABAJADAS,
            
            -- Información adicional
            a_fecha.TIPO_HORARIO,
            a_fecha.ID_HORARIO
            
        FROM empleado e
        INNER JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        INNER JOIN establecimiento est ON s.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        INNER JOIN empresa emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        
        -- Subconsulta para obtener fechas únicas de asistencia con tipo de horario
        INNER JOIN (
            SELECT DISTINCT 
                a.ID_EMPLEADO, 
                a.FECHA, 
                a.ID_HORARIO,
                COALESCE(a.TIPO_HORARIO, 'legacy') as TIPO_HORARIO
            FROM asistencia a
            WHERE a.FECHA BETWEEN :fecha_desde AND :fecha_hasta
        ) AS a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO
        
        -- JOIN con horario legacy
        LEFT JOIN horario h ON h.ID_HORARIO = a_fecha.ID_HORARIO 
            AND a_fecha.TIPO_HORARIO = 'legacy'
        
        -- JOIN con horario personalizado
        LEFT JOIN empleado_horario_personalizado ehp ON ehp.ID_EMPLEADO_HORARIO = a_fecha.ID_HORARIO 
            AND a_fecha.TIPO_HORARIO = 'personalizado'
        
        -- Subconsulta para obtener la entrada más reciente
        LEFT JOIN (
            SELECT a_entrada.ID_ASISTENCIA, a_entrada.ID_EMPLEADO, a_entrada.FECHA, a_entrada.ID_HORARIO,
                a_entrada.HORA, a_entrada.TARDANZA, a_entrada.OBSERVACION, a_entrada.TIPO_HORARIO
            FROM asistencia a_entrada
            WHERE a_entrada.TIPO = 'ENTRADA'
            AND a_entrada.FECHA BETWEEN :fecha_desde AND :fecha_hasta
            AND NOT EXISTS (
                SELECT 1 FROM asistencia a2
                WHERE a2.ID_EMPLEADO = a_entrada.ID_EMPLEADO
                AND a2.FECHA = a_entrada.FECHA
                AND a2.ID_HORARIO = a_entrada.ID_HORARIO
                AND a2.TIPO = 'ENTRADA'
                AND a2.ID_ASISTENCIA > a_entrada.ID_ASISTENCIA
            )
        ) AS entrada ON e.ID_EMPLEADO = entrada.ID_EMPLEADO
            AND a_fecha.FECHA = entrada.FECHA
            AND a_fecha.ID_HORARIO = entrada.ID_HORARIO
            AND a_fecha.TIPO_HORARIO = entrada.TIPO_HORARIO
            
        -- Subconsulta para obtener la salida más reciente
        LEFT JOIN (
            SELECT a_salida.ID_ASISTENCIA, a_salida.ID_EMPLEADO, a_salida.FECHA, a_salida.ID_HORARIO,
                a_salida.HORA, a_salida.TARDANZA, a_salida.OBSERVACION, a_salida.TIPO_HORARIO
            FROM asistencia a_salida
            WHERE a_salida.TIPO = 'SALIDA'
            AND a_salida.FECHA BETWEEN :fecha_desde AND :fecha_hasta
            AND NOT EXISTS (
                SELECT 1 FROM asistencia a2
                WHERE a2.ID_EMPLEADO = a_salida.ID_EMPLEADO
                AND a2.FECHA = a_salida.FECHA
                AND a2.ID_HORARIO = a_salida.ID_HORARIO
                AND a2.TIPO = 'SALIDA'
                AND a2.ID_ASISTENCIA > a_salida.ID_ASISTENCIA
            )
        ) AS salida ON e.ID_EMPLEADO = salida.ID_EMPLEADO
            AND a_fecha.FECHA = salida.FECHA
            AND a_fecha.ID_HORARIO = salida.ID_HORARIO
            AND a_fecha.TIPO_HORARIO = salida.TIPO_HORARIO
            
        WHERE emp.ID_EMPRESA = :empresa_id
        AND e.ACTIVO = 'S'
    ";
    
    $params = [
        ':empresa_id' => $empresaId,
        ':fecha_desde' => $fechaDesde,
        ':fecha_hasta' => $fechaHasta
    ];
    
    // Filtro por empleado específico
    if ($empleadoId) {
        $sql .= " AND e.ID_EMPLEADO = :empleado_id";
        $params[':empleado_id'] = $empleadoId;
    }
    
    $sql .= " ORDER BY e.NOMBRE, e.APELLIDO, a_fecha.FECHA DESC";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $horasTrabajadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $totalHoras = 0;
    $diasTrabajados = 0;
    $diasConEntradaYSalida = 0;
    
    foreach ($horasTrabajadas as $registro) {
        if ($registro['HORAS_TRABAJADAS']) {
            $totalHoras += $registro['HORAS_TRABAJADAS'];
            $diasConEntradaYSalida++;
        }
        if ($registro['ENTRADA_HORA'] || $registro['SALIDA_HORA']) {
            $diasTrabajados++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $horasTrabajadas,
        'estadisticas' => [
            'total_registros' => count($horasTrabajadas),
            'total_horas' => round($totalHoras, 2),
            'dias_trabajados' => $diasTrabajados,
            'dias_completos' => $diasConEntradaYSalida,
            'promedio_horas_dia' => $diasConEntradaYSalida > 0 ? round($totalHoras / $diasConEntradaYSalida, 2) : 0
        ],
        'parametros' => [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'empleado_id' => $empleadoId
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>