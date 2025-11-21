<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/holidays-helper.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empresaId = $_SESSION['id_empresa'];
$userRole = $_SESSION['rol'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

try {
    // Parámetros de filtro
    $filtros = [
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'empleados' => $_GET['empleados'] ?? [], // Support array of employee IDs
        'fechaDesde' => $_GET['fechaDesde'] ?? getBogotaDate(),
        'fechaHasta' => $_GET['fechaHasta'] ?? getBogotaDate()
    ];

    // Construir consulta base
    $where = ["emp.ID_EMPRESA = ?"];
    $params = [$empresaId];

    // Aplicar filtro restrictivo solo para rol ASISTENCIA
    if ($userRole === 'ASISTENCIA') {
        $where[] = "e.ID_ESTABLECIMIENTO IN (
            SELECT DISTINCT e2.ID_ESTABLECIMIENTO 
            FROM EMPLEADO e2 
            JOIN ESTABLECIMIENTO est2 ON e2.ID_ESTABLECIMIENTO = est2.ID_ESTABLECIMIENTO 
            JOIN SEDE s2 ON est2.ID_SEDE = s2.ID_SEDE 
            WHERE s2.ID_EMPRESA = ?
        )";
        $params[] = $empresaId;
    }

    // Aplicar filtros de fecha
    if ($filtros['fechaDesde']) {
        $where[] = "a.FECHA >= ?";
        $params[] = $filtros['fechaDesde'];
    }

    if ($filtros['fechaHasta']) {
        $where[] = "a.FECHA <= ?";
        $params[] = $filtros['fechaHasta'];
    }

    // Filtros adicionales
    if (!empty($filtros['empleados']) && is_array($filtros['empleados'])) {
        $empleadosPlaceholders = implode(',', array_fill(0, count($filtros['empleados']), '?'));
        $where[] = "e.ID_EMPLEADO IN ($empleadosPlaceholders)";
        foreach ($filtros['empleados'] as $empleadoId) {
            $params[] = $empleadoId;
        }
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = ?";
        $params[] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = ?";
        $params[] = $filtros['establecimiento'];
    }

    $whereClause = implode(' AND ', $where);

    // **CONSULTA SIMPLIFICADA: Obtener entradas y salidas por fecha, sin depender de horarios complejos**
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE AS ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            a.FECHA,
            
            -- Información del horario (puede ser tradicional o personalizado)
            COALESCE(h.ID_HORARIO, 0) as ID_HORARIO,
            COALESCE(h.NOMBRE, 'Horario personalizado') AS HORARIO_NOMBRE,
            COALESCE(h.HORA_ENTRADA, ehp.HORA_ENTRADA, '08:00:00') AS HORA_ENTRADA_PROGRAMADA,
            COALESCE(h.HORA_SALIDA, ehp.HORA_SALIDA, '17:00:00') AS HORA_SALIDA_PROGRAMADA,
            COALESCE(h.TOLERANCIA, ehp.TOLERANCIA, 15) AS TOLERANCIA,
            
            -- Entrada (la más reciente del día)
            MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.ID_ASISTENCIA END) AS ENTRADA_ID,
            MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.HORA END) AS ENTRADA_HORA,
            MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.TARDANZA END) AS ENTRADA_TARDANZA,
            MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.OBSERVACION END) AS OBSERVACION,
            
            -- Salida (la más reciente del día)
            MAX(CASE WHEN a.TIPO = 'SALIDA' THEN a.ID_ASISTENCIA END) AS SALIDA_ID,
            MAX(CASE WHEN a.TIPO = 'SALIDA' THEN a.HORA END) AS SALIDA_HORA,
            MAX(CASE WHEN a.TIPO = 'SALIDA' THEN a.TARDANZA END) AS SALIDA_TARDANZA
            
        FROM asistencia a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA

        -- LEFT JOIN con horario tradicional
        LEFT JOIN HORARIO h ON a.ID_HORARIO = h.ID_HORARIO

        -- LEFT JOIN con horario personalizado
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO

        WHERE {$whereClause}
        GROUP BY e.ID_EMPLEADO, a.FECHA
        ORDER BY a.FECHA DESC, e.NOMBRE, e.APELLIDO
    ";

    $stmt = $conn->prepare($sql);
    
    // Bind parameters by position
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamos para calcular las horas trabajadas y clasificaciones
    $result = [];
    $stats = [
        'total' => 0,
        'regular' => 0,
        'extra' => 0,
        'dominicales' => 0,
        'festivos' => 0
    ];
    
    // Obtener festivos para el rango de fechas
    $festivosHelper = new HolidaysHelper();
    $festivos = $festivosHelper->getFestivosRango($filtros['fechaDesde'], $filtros['fechaHasta']);
    
    foreach ($asistencias as $registro) {
        $fecha = $registro['FECHA'];
        $diaSemana = date('w', strtotime($fecha));
        $esDomingo = ($diaSemana == 0);
        $esFestivo = in_array($fecha, $festivos) || $festivosHelper->esDiaCivico($fecha);
        
        // Calcular horas trabajadas si hay entrada y salida
        $horasRegulares = 0;
        $horasExtras = 0;
        $horasDominicales = 0;
        $horasFestivos = 0;
        $totalHoras = 0;
        
        if ($registro['ENTRADA_HORA'] && $registro['SALIDA_HORA']) {
            $entradaTs = strtotime($fecha . ' ' . $registro['ENTRADA_HORA']);
            $salidaTs = strtotime($fecha . ' ' . $registro['SALIDA_HORA']);
            
            // Solo calcular si la salida es posterior a la entrada
            if ($salidaTs > $entradaTs) {
                $horasTrabajadas = ($salidaTs - $entradaTs) / 3600;
                
                // Clasificar las horas según el tipo de día y horario
                if ($esFestivo) {
                    $horasFestivos = round($horasTrabajadas, 2);
                } elseif ($esDomingo) {
                    $horasDominicales = round($horasTrabajadas, 2);
                } else {
                    // Calcular horas regulares vs extras basado en el horario programado
                    $horasRegularesProgramadas = 8; // Por defecto 8 horas
                    
                    if ($registro['HORA_ENTRADA_PROGRAMADA'] && $registro['HORA_SALIDA_PROGRAMADA']) {
                        $entradaProgramadaTs = strtotime($fecha . ' ' . $registro['HORA_ENTRADA_PROGRAMADA']);
                        $salidaProgramadaTs = strtotime($fecha . ' ' . $registro['HORA_SALIDA_PROGRAMADA']);
                        $horasRegularesProgramadas = ($salidaProgramadaTs - $entradaProgramadaTs) / 3600;
                    }
                    
                    if ($horasTrabajadas <= $horasRegularesProgramadas) {
                        $horasRegulares = round($horasTrabajadas, 2);
                    } else {
                        $horasRegulares = round($horasRegularesProgramadas, 2);
                        $horasExtras = round($horasTrabajadas - $horasRegularesProgramadas, 2);
                    }
                }
                
                $totalHoras = $horasRegulares + $horasExtras + $horasDominicales + $horasFestivos;
            }
        }
        
        // Añadir clasificaciones al registro
        $registro['HORAS_REGULARES'] = $horasRegulares;
        $registro['HORAS_EXTRAS'] = $horasExtras;
        $registro['HORAS_DOMINICALES'] = $horasDominicales;
        $registro['HORAS_FESTIVOS'] = $horasFestivos;
        $registro['TOTAL_HORAS'] = round($totalHoras, 2);
        $registro['ES_FESTIVO'] = $esFestivo ? 'S' : 'N';
        $registro['ES_DOMINGO'] = $esDomingo ? 'S' : 'N';
        $registro['OBSERVACIONES'] = $registro['OBSERVACION'] ?? '';
        
        // Actualizar estadísticas
        $stats['total'] += $totalHoras;
        $stats['regular'] += $horasRegulares;
        $stats['extra'] += $horasExtras;
        $stats['dominicales'] += $horasDominicales;
        $stats['festivos'] += $horasFestivos;
        
        $result[] = $registro;
    }
    
    // Redondear estadísticas
    foreach ($stats as $key => $value) {
        $stats[$key] = round($value, 2);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'horas' => $result,
        'stats' => $stats,
        'total_registros' => count($result)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get-horas-simplified-v2.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las horas trabajadas: ' . $e->getMessage()
    ]);
}
?>