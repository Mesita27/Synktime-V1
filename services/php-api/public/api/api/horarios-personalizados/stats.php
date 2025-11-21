<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Estadísticas generales
    $sql = "
        SELECT 
            COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
            COUNT(DISTINCT CASE WHEN ehp.ID_EMPLEADO IS NOT NULL THEN e.ID_EMPLEADO END) as empleados_con_horarios,
            COUNT(DISTINCT ehp.ID_EMPLEADO_HORARIO) as total_turnos_configurados,
            COUNT(DISTINCT CASE WHEN ehp.ACTIVO = 'S' AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= CURDATE()) THEN ehp.ID_EMPLEADO_HORARIO END) as turnos_activos,
            COUNT(DISTINCT CASE WHEN ehp.ACTIVO = 'S' AND ehp.FECHA_HASTA < CURDATE() THEN ehp.ID_EMPLEADO_HORARIO END) as turnos_vencidos,
            COUNT(DISTINCT CASE WHEN ehp.ACTIVO = 'N' THEN ehp.ID_EMPLEADO_HORARIO END) as turnos_inactivos
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO
        WHERE s.ID_EMPRESA = :empresa_id AND e.ACTIVO = 'S'
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Estadísticas por día de la semana
    $sqlDias = "
        SELECT 
            ds.NOMBRE as dia_nombre,
            ds.ID_DIA,
            COUNT(DISTINCT ehp.ID_EMPLEADO_HORARIO) as turnos_configurados,
            COUNT(DISTINCT ehp.ID_EMPLEADO) as empleados_con_turnos,
            AVG(TIME_TO_SEC(TIMEDIFF(ehp.HORA_SALIDA, ehp.HORA_ENTRADA))/3600) as promedio_horas_dia
        FROM dia_semana ds
        LEFT JOIN empleado_horario_personalizado ehp ON ds.ID_DIA = ehp.ID_DIA 
            AND ehp.ACTIVO = 'S' 
            AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= CURDATE())
        LEFT JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id OR s.ID_EMPRESA IS NULL
        GROUP BY ds.ID_DIA, ds.NOMBRE
        ORDER BY ds.ID_DIA
    ";

    $stmtDias = $conn->prepare($sqlDias);
    $stmtDias->bindValue(':empresa_id', $empresaId);
    $stmtDias->execute();
    $statsDias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas por sede
    $sqlSedes = "
        SELECT 
            s.NOMBRE as sede_nombre,
            s.ID_SEDE,
            COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
            COUNT(DISTINCT CASE WHEN ehp.ID_EMPLEADO IS NOT NULL THEN e.ID_EMPLEADO END) as empleados_con_horarios,
            COUNT(DISTINCT ehp.ID_EMPLEADO_HORARIO) as total_turnos
        FROM sede s
        JOIN establecimiento est ON s.ID_SEDE = est.ID_SEDE
        JOIN empleado e ON est.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO
            AND ehp.ACTIVO = 'S' 
            AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= CURDATE())
        WHERE s.ID_EMPRESA = :empresa_id AND e.ACTIVO = 'S'
        GROUP BY s.ID_SEDE, s.NOMBRE
        ORDER BY s.NOMBRE
    ";

    $stmtSedes = $conn->prepare($sqlSedes);
    $stmtSedes->bindValue(':empresa_id', $empresaId);
    $stmtSedes->execute();
    $statsSedes = $stmtSedes->fetchAll(PDO::FETCH_ASSOC);

    // Turnos más comunes
    $sqlTurnos = "
        SELECT 
            ehp.NOMBRE_TURNO,
            COUNT(*) as cantidad_empleados,
            COUNT(DISTINCT ehp.ID_EMPLEADO) as empleados_unicos,
            AVG(TIME_TO_SEC(TIMEDIFF(ehp.HORA_SALIDA, ehp.HORA_ENTRADA))/3600) as promedio_horas,
            GROUP_CONCAT(DISTINCT ds.NOMBRE ORDER BY ds.ID_DIA) as dias_aplicados
        FROM empleado_horario_personalizado ehp
        JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        WHERE s.ID_EMPRESA = :empresa_id 
        AND ehp.ACTIVO = 'S' 
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= CURDATE())
        AND e.ACTIVO = 'S'
        GROUP BY ehp.NOMBRE_TURNO
        ORDER BY cantidad_empleados DESC
        LIMIT 10
    ";

    $stmtTurnos = $conn->prepare($sqlTurnos);
    $stmtTurnos->bindValue(':empresa_id', $empresaId);
    $stmtTurnos->execute();
    $statsTurnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

    // Empleados con próximos vencimientos de horarios
    $sqlVencimientos = "
        SELECT 
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            ehp.NOMBRE_TURNO,
            ehp.FECHA_HASTA,
            DATEDIFF(ehp.FECHA_HASTA, CURDATE()) as dias_restantes,
            s.NOMBRE as sede_nombre
        FROM empleado_horario_personalizado ehp
        JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id 
        AND ehp.ACTIVO = 'S' 
        AND ehp.FECHA_HASTA IS NOT NULL
        AND ehp.FECHA_HASTA BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND e.ACTIVO = 'S'
        ORDER BY ehp.FECHA_HASTA ASC
        LIMIT 10
    ";

    $stmtVencimientos = $conn->prepare($sqlVencimientos);
    $stmtVencimientos->bindValue(':empresa_id', $empresaId);
    $stmtVencimientos->execute();
    $statsVencimientos = $stmtVencimientos->fetchAll(PDO::FETCH_ASSOC);

    // Formatear respuesta
    $response = [
        'success' => true,
        'stats' => [
            'general' => [
                'total_empleados' => (int)$stats['total_empleados'],
                'empleados_con_horarios' => (int)$stats['empleados_con_horarios'],
                'empleados_sin_horarios' => (int)$stats['total_empleados'] - (int)$stats['empleados_con_horarios'],
                'total_turnos_configurados' => (int)$stats['total_turnos_configurados'],
                'turnos_activos' => (int)$stats['turnos_activos'],
                'turnos_vencidos' => (int)$stats['turnos_vencidos'],
                'turnos_inactivos' => (int)$stats['turnos_inactivos'],
                'porcentaje_configurados' => $stats['total_empleados'] > 0 ? 
                    round(($stats['empleados_con_horarios'] / $stats['total_empleados']) * 100, 1) : 0
            ],
            'por_dia' => array_map(function($dia) {
                return [
                    'dia' => $dia['dia_nombre'],
                    'id_dia' => (int)$dia['ID_DIA'],
                    'turnos_configurados' => (int)$dia['turnos_configurados'],
                    'empleados_con_turnos' => (int)$dia['empleados_con_turnos'],
                    'promedio_horas' => $dia['promedio_horas_dia'] ? round($dia['promedio_horas_dia'], 1) : 0
                ];
            }, $statsDias),
            'por_sede' => array_map(function($sede) {
                return [
                    'sede' => $sede['sede_nombre'],
                    'id_sede' => (int)$sede['ID_SEDE'],
                    'total_empleados' => (int)$sede['total_empleados'],
                    'empleados_con_horarios' => (int)$sede['empleados_con_horarios'],
                    'total_turnos' => (int)$sede['total_turnos'],
                    'porcentaje_configurados' => $sede['total_empleados'] > 0 ? 
                        round(($sede['empleados_con_horarios'] / $sede['total_empleados']) * 100, 1) : 0
                ];
            }, $statsSedes),
            'turnos_populares' => array_map(function($turno) {
                return [
                    'nombre_turno' => $turno['NOMBRE_TURNO'],
                    'cantidad_empleados' => (int)$turno['cantidad_empleados'],
                    'empleados_unicos' => (int)$turno['empleados_unicos'],
                    'promedio_horas' => round($turno['promedio_horas'], 1),
                    'dias_aplicados' => explode(',', $turno['dias_aplicados'])
                ];
            }, $statsTurnos),
            'proximos_vencimientos' => array_map(function($venc) {
                return [
                    'empleado' => trim($venc['NOMBRE'] . ' ' . $venc['APELLIDO']),
                    'dni' => $venc['DNI'],
                    'turno' => $venc['NOMBRE_TURNO'],
                    'fecha_vencimiento' => $venc['FECHA_HASTA'],
                    'dias_restantes' => (int)$venc['dias_restantes'],
                    'sede' => $venc['sede_nombre'],
                    'urgencia' => (int)$venc['dias_restantes'] <= 7 ? 'alta' : 
                                ((int)$venc['dias_restantes'] <= 15 ? 'media' : 'baja')
                ];
            }, $statsVencimientos)
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>