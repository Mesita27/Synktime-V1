<?php
/**
 * ENDPOINT SIMPLIFICADO PARA CÁLCULO DE HORAS TRABAJADAS
 * Solo usa empleado_horario_personalizado (sin complejidad legacy)
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
    
    // Query simplificada - solo horarios personalizados
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            est.NOMBRE as ESTABLECIMIENTO,
            s.NOMBRE as SEDE,
            a_fecha.FECHA,
            
            -- Información del horario personalizado
            ehp.NOMBRE_TURNO as HORARIO_NOMBRE,
            ehp.HORA_ENTRADA as HORA_ENTRADA_PROGRAMADA,
            ehp.HORA_SALIDA as HORA_SALIDA_PROGRAMADA,
            ehp.TOLERANCIA,
            
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
            ehp.ID_EMPLEADO_HORARIO,
            ehp.ORDEN_TURNO,
            ehp.OBSERVACIONES as HORARIO_OBSERVACIONES
            
        FROM empleado e
        INNER JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        INNER JOIN establecimiento est ON s.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        INNER JOIN empresa emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        
        -- Subconsulta para obtener fechas únicas de asistencia
        INNER JOIN (
            SELECT DISTINCT 
                a.ID_EMPLEADO, 
                a.FECHA, 
                a.ID_EMPLEADO_HORARIO
            FROM asistencia a
            WHERE a.FECHA BETWEEN :fecha_desde AND :fecha_hasta
            AND a.ID_EMPLEADO_HORARIO IS NOT NULL
        ) AS a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO
        
        -- JOIN con horario personalizado
        INNER JOIN empleado_horario_personalizado ehp ON ehp.ID_EMPLEADO_HORARIO = a_fecha.ID_EMPLEADO_HORARIO
        
        -- Subconsulta para obtener la entrada más reciente
        LEFT JOIN (
            SELECT a_entrada.ID_ASISTENCIA, a_entrada.ID_EMPLEADO, a_entrada.FECHA, a_entrada.ID_EMPLEADO_HORARIO,
                a_entrada.HORA, a_entrada.TARDANZA, a_entrada.OBSERVACION
            FROM asistencia a_entrada
            WHERE a_entrada.TIPO = 'ENTRADA'
            AND a_entrada.FECHA BETWEEN :fecha_desde AND :fecha_hasta
            AND NOT EXISTS (
                SELECT 1 FROM asistencia a2
                WHERE a2.ID_EMPLEADO = a_entrada.ID_EMPLEADO
                AND a2.FECHA = a_entrada.FECHA
                AND a2.ID_EMPLEADO_HORARIO = a_entrada.ID_EMPLEADO_HORARIO
                AND a2.TIPO = 'ENTRADA'
                AND a2.ID_ASISTENCIA > a_entrada.ID_ASISTENCIA
            )
        ) AS entrada ON e.ID_EMPLEADO = entrada.ID_EMPLEADO
            AND a_fecha.FECHA = entrada.FECHA
            AND a_fecha.ID_EMPLEADO_HORARIO = entrada.ID_EMPLEADO_HORARIO
            
        -- Subconsulta para obtener la salida más reciente
        LEFT JOIN (
            SELECT a_salida.ID_ASISTENCIA, a_salida.ID_EMPLEADO, a_salida.FECHA, a_salida.ID_EMPLEADO_HORARIO,
                a_salida.HORA, a_salida.TARDANZA, a_salida.OBSERVACION
            FROM asistencia a_salida
            WHERE a_salida.TIPO = 'SALIDA'
            AND a_salida.FECHA BETWEEN :fecha_desde AND :fecha_hasta
            AND NOT EXISTS (
                SELECT 1 FROM asistencia a2
                WHERE a2.ID_EMPLEADO = a_salida.ID_EMPLEADO
                AND a2.FECHA = a_salida.FECHA
                AND a2.ID_EMPLEADO_HORARIO = a_salida.ID_EMPLEADO_HORARIO
                AND a2.TIPO = 'SALIDA'
                AND a2.ID_ASISTENCIA > a_salida.ID_ASISTENCIA
            )
        ) AS salida ON e.ID_EMPLEADO = salida.ID_EMPLEADO
            AND a_fecha.FECHA = salida.FECHA
            AND a_fecha.ID_EMPLEADO_HORARIO = salida.ID_EMPLEADO_HORARIO
            
        WHERE emp.ID_EMPRESA = :empresa_id
        AND e.ACTIVO = 'S'
        AND ehp.ACTIVO = 'S'
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
    
    $sql .= " ORDER BY e.NOMBRE, e.APELLIDO, a_fecha.FECHA DESC, ehp.ORDEN_TURNO";
    
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
    $empleadosUnicos = [];
    
    foreach ($horasTrabajadas as $registro) {
        $empleadosUnicos[$registro['ID_EMPLEADO']] = true;
        
        if ($registro['HORAS_TRABAJADAS']) {
            $totalHoras += $registro['HORAS_TRABAJADAS'];
            $diasConEntradaYSalida++;
        }
        if ($registro['ENTRADA_HORA'] || $registro['SALIDA_HORA']) {
            $diasTrabajados++;
        }
    }
    
    // Obtener resumen por empleado
    $resumenPorEmpleado = [];
    foreach ($horasTrabajadas as $registro) {
        $empleadoId = $registro['ID_EMPLEADO'];
        if (!isset($resumenPorEmpleado[$empleadoId])) {
            $resumenPorEmpleado[$empleadoId] = [
                'empleado' => [
                    'id' => $registro['ID_EMPLEADO'],
                    'nombre' => trim($registro['NOMBRE'] . ' ' . $registro['APELLIDO']),
                    'dni' => $registro['DNI'],
                    'establecimiento' => $registro['ESTABLECIMIENTO'],
                    'sede' => $registro['SEDE']
                ],
                'total_horas' => 0,
                'dias_trabajados' => 0,
                'dias_completos' => 0,
                'registros' => []
            ];
        }
        
        $resumenPorEmpleado[$empleadoId]['registros'][] = $registro;
        
        if ($registro['HORAS_TRABAJADAS']) {
            $resumenPorEmpleado[$empleadoId]['total_horas'] += $registro['HORAS_TRABAJADAS'];
            $resumenPorEmpleado[$empleadoId]['dias_completos']++;
        }
        if ($registro['ENTRADA_HORA'] || $registro['SALIDA_HORA']) {
            $resumenPorEmpleado[$empleadoId]['dias_trabajados']++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $horasTrabajadas,
        'resumen_por_empleado' => array_values($resumenPorEmpleado),
        'estadisticas' => [
            'total_registros' => count($horasTrabajadas),
            'empleados_unicos' => count($empleadosUnicos),
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