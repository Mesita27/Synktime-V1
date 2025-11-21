<?php
/**
 * API REFACTORIZADA PARA HORAS TRABAJADAS
 * Nueva lógica con categorización avanzada:
 * - Recargo nocturno (9PM-6AM)
 * - Recargo dominical/festivo
 * - Recargo nocturno dominical/festivo
 * - Extra diurna (6AM-9PM fuera de horario)
 * - Extra nocturna (9PM-6AM fuera de horario)
 * - Extra diurna dominical/festiva
 * - Extra nocturna dominical/festiva
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/holidays-helper.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    date_default_timezone_set('America/Bogota');
    
    $empresaId = $_SESSION['id_empresa'];
    $filtros = [
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'empleados' => $_GET['empleados'] ?? [],
        'fechaDesde' => $_GET['fechaDesde'] ?? date('Y-m-d'),
        'fechaHasta' => $_GET['fechaHasta'] ?? date('Y-m-d')
    ];

    // Construir consulta base
    $where = ["emp.ID_EMPRESA = ?"];
    $params = [$empresaId];

    // Aplicar filtros
    if ($filtros['fechaDesde']) {
        $where[] = "a.FECHA >= ?";
        $params[] = $filtros['fechaDesde'];
    }

    if ($filtros['fechaHasta']) {
        $where[] = "a.FECHA <= ?";
        $params[] = $filtros['fechaHasta'];
    }

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

    // Consulta principal: obtener asistencias completas (ENTRADA y SALIDA en el mismo día)
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE AS ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            a.FECHA,
            
            -- Datos de entrada
            entrada.HORA AS HORA_ENTRADA,
            entrada.ID_ASISTENCIA AS ENTRADA_ID,
            
            -- Datos de salida
            salida.HORA AS HORA_SALIDA,
            salida.ID_ASISTENCIA AS SALIDA_ID,
            salida.FECHA AS FECHA_SALIDA,
            
            -- Horario personalizado (si existe)
            ehp.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO,
            ehp.HORA_ENTRADA AS HORARIO_ENTRADA,
            ehp.HORA_SALIDA AS HORARIO_SALIDA,
            ehp.ES_TURNO_NOCTURNO,
            ehp.DIA_SEMANA,
            
            -- Horario tradicional (si no tiene personalizado)
            h.NOMBRE AS HORARIO_TRADICIONAL,
            h.HORA_ENTRADA AS TRADICIONAL_ENTRADA,
            h.HORA_SALIDA AS TRADICIONAL_SALIDA,
            h.ES_TURNO_NOCTURNO AS TRADICIONAL_NOCTURNO,
            
            -- Justificación (si existe)
            j.ID_JUSTIFICACION,
            j.FECHA_JUSTIFICACION,
            j.OBSERVACION AS JUSTIFICACION_OBSERVACION,
            j.DETALLE_ADICIONAL AS JUSTIFICACION_DETALLE
            
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        JOIN empresa emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        JOIN asistencia a ON e.ID_EMPLEADO = a.ID_EMPLEADO
        
        -- Obtener entrada del día
        LEFT JOIN asistencia entrada ON (
            entrada.ID_EMPLEADO = e.ID_EMPLEADO 
            AND entrada.FECHA = a.FECHA 
            AND entrada.TIPO = 'ENTRADA'
        )
        
        -- Obtener salida (puede ser el mismo día o día siguiente para nocturnos)
        LEFT JOIN asistencia salida ON (
            salida.ID_EMPLEADO = e.ID_EMPLEADO 
            AND salida.TIPO = 'SALIDA'
            AND (
                salida.FECHA = a.FECHA 
                OR salida.FECHA = DATE_ADD(a.FECHA, INTERVAL 1 DAY)
            )
            AND salida.ID_ASISTENCIA > entrada.ID_ASISTENCIA
        )
        
        -- Horario personalizado activo
        LEFT JOIN empleado_horario_personalizado ehp ON (
            ehp.ID_EMPLEADO = e.ID_EMPLEADO
            AND ehp.ACTIVO = 'S'
            AND ehp.DIA_SEMANA = DAYOFWEEK(a.FECHA)
            AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '2999-12-31')
        )
        
        -- Horario tradicional (backup)
        LEFT JOIN empleado_horario eh ON (
            eh.ID_EMPLEADO = e.ID_EMPLEADO
            AND eh.ACTIVO = 'S'
        )
        LEFT JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
        
        -- Justificaciones
        LEFT JOIN justificacion j ON (
            j.ID_EMPLEADO = e.ID_EMPLEADO
            AND j.FECHA_JUSTIFICACION = a.FECHA
            AND j.ACTIVO = 'S'
        )
        
        WHERE $whereClause
        AND entrada.ID_ASISTENCIA IS NOT NULL
        AND salida.ID_ASISTENCIA IS NOT NULL
        
        ORDER BY a.FECHA DESC, e.APELLIDO, e.NOMBRE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultados = [];
    $stats = [
        'recargo_nocturno' => 0,
        'recargo_dominical' => 0,
        'recargo_nocturno_dominical' => 0,
        'extra_diurna' => 0,
        'extra_nocturna' => 0,
        'extra_diurna_dominical' => 0,
        'extra_nocturna_dominical' => 0,
        'total_horas' => 0
    ];

    foreach ($registros as $registro) {
        $procesado = procesarRegistroHoras($registro);
        $resultados[] = $procesado;
        
        // Acumular estadísticas
        foreach ($stats as $key => $value) {
            if (isset($procesado[$key])) {
                $stats[$key] += $procesado[$key];
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'stats' => $stats,
        'filtros' => $filtros
    ]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar horas trabajadas: ' . $e->getMessage()
    ]);
}

/**
 * Procesa un registro individual y calcula todas las categorías de horas
 */
function procesarRegistroHoras($registro) {
    // Si hay justificación, las horas trabajadas son 0
    if (!empty($registro['ID_JUSTIFICACION'])) {
        return [
            'id_empleado' => $registro['ID_EMPLEADO'],
            'empleado' => $registro['NOMBRE'] . ' ' . $registro['APELLIDO'],
            'dni' => $registro['DNI'],
            'fecha' => $registro['FECHA'],
            'dia_semana' => getDiaSemana($registro['FECHA']),
            'hora_entrada' => null,
            'hora_salida' => null,
            'recargo_nocturno' => 0,
            'recargo_dominical' => 0,
            'recargo_nocturno_dominical' => 0,
            'extra_diurna' => 0,
            'extra_nocturna' => 0,
            'extra_diurna_dominical' => 0,
            'extra_nocturna_dominical' => 0,
            'total_horas' => 0,
            'justificacion' => [
                'fecha' => $registro['FECHA_JUSTIFICACION'],
                'observacion' => $registro['JUSTIFICACION_OBSERVACION'],
                'detalle' => $registro['JUSTIFICACION_DETALLE']
            ]
        ];
    }

    // Verificar que tenemos entrada y salida
    if (empty($registro['HORA_ENTRADA']) || empty($registro['HORA_SALIDA'])) {
        return generarRegistroVacio($registro);
    }

    // Determinar horario del empleado (personalizado vs tradicional)
    $horario = determinarHorarioEmpleado($registro);
    
    // Calcular tiempo trabajado
    $tiempoTrabajado = calcularTiempoTrabajado($registro);
    
    // Determinar si es domingo o festivo
    $esDomingoOFestivo = esDomingoOFestivo($registro['FECHA']);
    
    // Calcular cada categoría de horas
    $categorias = calcularCategorias($tiempoTrabajado, $horario, $esDomingoOFestivo);

    return [
        'id_empleado' => $registro['ID_EMPLEADO'],
        'empleado' => $registro['NOMBRE'] . ' ' . $registro['APELLIDO'],
        'dni' => $registro['DNI'],
        'fecha' => $registro['FECHA'],
        'dia_semana' => getDiaSemana($registro['FECHA']),
        'hora_entrada' => $registro['HORA_ENTRADA'],
        'hora_salida' => $registro['HORA_SALIDA'],
        'recargo_nocturno' => $categorias['recargo_nocturno'],
        'recargo_dominical' => $categorias['recargo_dominical'],
        'recargo_nocturno_dominical' => $categorias['recargo_nocturno_dominical'],
        'extra_diurna' => $categorias['extra_diurna'],
        'extra_nocturna' => $categorias['extra_nocturna'],
        'extra_diurna_dominical' => $categorias['extra_diurna_dominical'],
        'extra_nocturna_dominical' => $categorias['extra_nocturna_dominical'],
        'total_horas' => $categorias['total_horas'],
        'justificacion' => null,
        'horario_info' => $horario
    ];
}

/**
 * Determina el horario del empleado (personalizado o tradicional)
 */
function determinarHorarioEmpleado($registro) {
    if (!empty($registro['ID_EMPLEADO_HORARIO'])) {
        // Horario personalizado
        return [
            'tipo' => 'personalizado',
            'nombre' => $registro['NOMBRE_TURNO'],
            'hora_entrada' => $registro['HORARIO_ENTRADA'],
            'hora_salida' => $registro['HORARIO_SALIDA'],
            'es_nocturno' => $registro['ES_TURNO_NOCTURNO'] === 'S'
        ];
    } else {
        // Horario tradicional
        return [
            'tipo' => 'tradicional',
            'nombre' => $registro['HORARIO_TRADICIONAL'],
            'hora_entrada' => $registro['TRADICIONAL_ENTRADA'],
            'hora_salida' => $registro['TRADICIONAL_SALIDA'],
            'es_nocturno' => $registro['TRADICIONAL_NOCTURNO'] === 'S'
        ];
    }
}

/**
 * Calcula el tiempo total trabajado
 */
function calcularTiempoTrabajado($registro) {
    $fechaEntrada = $registro['FECHA'] . ' ' . $registro['HORA_ENTRADA'];
    $fechaSalida = $registro['FECHA_SALIDA'] . ' ' . $registro['HORA_SALIDA'];
    
    $entrada = new DateTime($fechaEntrada);
    $salida = new DateTime($fechaSalida);
    
    return $salida->diff($entrada);
}

/**
 * Calcula todas las categorías de horas basadas en la nueva lógica
 */
function calcularCategorias($tiempoTrabajado, $horario, $esDomingoOFestivo) {
    $totalHoras = $tiempoTrabajado->h + ($tiempoTrabajado->i / 60);
    
    // Inicializar categorías
    $categorias = [
        'recargo_nocturno' => 0,
        'recargo_dominical' => 0,
        'recargo_nocturno_dominical' => 0,
        'extra_diurna' => 0,
        'extra_nocturna' => 0,
        'extra_diurna_dominical' => 0,
        'extra_nocturna_dominical' => 0,
        'total_horas' => round($totalHoras, 2)
    ];

    // TODO: Implementar lógica completa de categorización
    // Por ahora, devolver estructura básica
    
    return $categorias;
}

/**
 * Genera un registro vacío para casos sin datos completos
 */
function generarRegistroVacio($registro) {
    return [
        'id_empleado' => $registro['ID_EMPLEADO'],
        'empleado' => $registro['NOMBRE'] . ' ' . $registro['APELLIDO'],
        'dni' => $registro['DNI'],
        'fecha' => $registro['FECHA'],
        'dia_semana' => getDiaSemana($registro['FECHA']),
        'hora_entrada' => $registro['HORA_ENTRADA'] ?? null,
        'hora_salida' => $registro['HORA_SALIDA'] ?? null,
        'recargo_nocturno' => 0,
        'recargo_dominical' => 0,
        'recargo_nocturno_dominical' => 0,
        'extra_diurna' => 0,
        'extra_nocturna' => 0,
        'extra_diurna_dominical' => 0,
        'extra_nocturna_dominical' => 0,
        'total_horas' => 0,
        'justificacion' => null
    ];
}

/**
 * Obtiene el nombre del día de la semana
 */
function getDiaSemana($fecha) {
    $dias = ['', 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return $dias[date('w', strtotime($fecha)) + 1] ?? 'Desconocido';
}

/**
 * Determina si una fecha es domingo o día festivo
 */
function esDomingoOFestivo($fecha) {
    // Verificar si es domingo
    if (date('w', strtotime($fecha)) == 0) {
        return true;
    }
    
    // Verificar si es día festivo (requiere tabla de festivos)
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM festivos 
            WHERE fecha = ? AND activo = 'S'
        ");
        $stmt->execute([$fecha]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (Exception $e) {
        // Si no existe la tabla de festivos, solo verificar domingo
        return false;
    }
}
?>