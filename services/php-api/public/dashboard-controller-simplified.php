<?php
// Dashboard Controller Simplificado para el nuevo flujo
require_once __DIR__ . '/config/timezone.php';
require_once 'config/database.php';
require_once 'utils/attendance_status_utils.php';

/**
 * Cuenta los turnos programados que no se cumplieron (faltas)
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param string $nivel Nivel: 'empresa', 'sede', 'establecimiento'
 * @param int $id ID del nivel
 * @param string $fecha Fecha en formato Y-m-d
 * @return int Número de turnos faltantes
 */
function contarTurnosFaltantes($conn, $nivel, $id, $fecha) {
    // Obtener día de la semana de la fecha consultada (1=Lunes, 7=Domingo)
    $diaSemana = date('N', strtotime($fecha));
    
    // Construcción del filtro según nivel
    if ($nivel === 'empresa') {
        $where = "AND s.ID_EMPRESA = :id";
        $join = "JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
    } elseif ($nivel === 'sede') {
        $where = "AND s.ID_SEDE = :id";
        $join = "JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
    } else { // establecimiento
        $where = "AND est.ID_ESTABLECIMIENTO = :id";
        $join = "JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
    }

    // Contar turnos programados para el día específico que no tuvieron asistencia
    $stmt = $conn->prepare("
        SELECT COUNT(*) as turnos_faltantes
        FROM empleado e
    $join
    JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO
        WHERE e.ESTADO = 'A' 
          AND e.ACTIVO = 'S'
          AND ehp.ACTIVO = 'S'
          AND :fecha BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
          AND ehp.ID_DIA = :dia_semana
          $where
          AND NOT EXISTS (
              SELECT 1 FROM asistencia a 
              WHERE a.ID_EMPLEADO = e.ID_EMPLEADO 
                AND a.FECHA = :fecha2 
                AND a.TIPO = 'ENTRADA'
          )
    ");
    
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':fecha2', $fecha);
    $stmt->bindParam(':dia_semana', $diaSemana);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['turnos_faltantes'];
}

/**
 * Obtiene estadísticas de asistencia simplificadas (compatible con horarios tradicionales y personalizados)
 * 
 * @param string $nivel Nivel: 'empresa', 'sede', 'establecimiento'
 * @param int $id ID del nivel
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Estadísticas de asistencia
 */
function getEstadisticasAsistenciaSimplified($nivel, $id, $fecha) {
    global $conn;

    // Construcción del filtro y joins según nivel
    if ($nivel === 'empresa') {
        $where = "s.ID_EMPRESA = :id";
        $join = "JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
    } elseif ($nivel === 'sede') {
        $where = "s.ID_SEDE = :id";
        $join = "JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
    } else { // establecimiento
        $where = "est.ID_ESTABLECIMIENTO = :id";
        $join = "JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
    }

    // Obtener todos los empleados activos en el nivel seleccionado
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO
        FROM empleado e
        $join
        WHERE $where
        AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_empleados = count($empleados);

    // **CONSULTA SIMPLIFICADA: Obtener entradas solo con horarios personalizados**
    $stmt = $conn->prepare("
        SELECT 
            a.ID_EMPLEADO,
            a.HORA as hora_entrada,
            a.TARDANZA,
            COALESCE(ehp.HORA_ENTRADA, '08:00:00') as horario_entrada,
            COALESCE(ehp.HORA_SALIDA, '17:00:00') as horario_salida,
            COALESCE(ehp.TOLERANCIA, 15) as tolerancia
        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        $join
        
        -- LEFT JOIN solo con horario personalizado VIGENTE
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
            AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
            AND ehp.ACTIVO = 'S'
        
        WHERE a.FECHA = :fecha
        AND a.TIPO = 'ENTRADA'
        AND $where
        AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
        ORDER BY a.HORA ASC
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $asistencias_entrada = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar contadores
    $tempranos = 0;
    $atiempo = 0;
    $tardanzas = 0;
    $empleados_asistieron = [];

    // Procesar las asistencias de entrada para determinar clasificación
    foreach ($asistencias_entrada as $asistencia) {
        $empleados_asistieron[] = $asistencia['ID_EMPLEADO'];

        $horaEntradaProgramada = $asistencia['horario_entrada'];
        $horaEntradaReal = $asistencia['hora_entrada'];
        $tolerancia = (int)($asistencia['tolerancia'] ?? 0);

        // Usar la función consistente para calcular el estado
        $estado = calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $tolerancia);

        switch ($estado) {
            case 'Temprano':
                $tempranos++;
                break;
            case 'Puntual':
                $atiempo++;
                break;
            case 'Tardanza':
                $tardanzas++;
                break;
        }
    }

    // Calcular faltas (turnos programados que no se cumplieron)
    $faltas = contarTurnosFaltantes($conn, $nivel, $id, $fecha);
    $empleados_asistieron_unique = array_values(array_unique($empleados_asistieron));

    // **CALCULAR HORAS TRABAJADAS TOTALES**
    $horas_trabajadas_totales = 0.0;
    if (!empty($empleados_asistieron_unique)) {
        $placeholders = str_repeat('?,', count($empleados_asistieron_unique) - 1) . '?';

        // Obtener pares de entrada-salida para calcular horas trabajadas
        $stmt = $conn->prepare("
            SELECT
                entrada.ID_EMPLEADO,
                entrada.HORA as hora_entrada,
                entrada.FECHA,
                salida.HORA as hora_salida
            FROM asistencia entrada
            LEFT JOIN asistencia salida ON entrada.ID_EMPLEADO = salida.ID_EMPLEADO
                AND entrada.ID_EMPLEADO_HORARIO = salida.ID_EMPLEADO_HORARIO
                AND salida.TIPO = 'SALIDA'
                AND CONCAT(salida.FECHA, ' ', salida.HORA) BETWEEN CONCAT(entrada.FECHA, ' ', entrada.HORA)
                AND DATE_ADD(CONCAT(entrada.FECHA, ' ', entrada.HORA), INTERVAL 24 HOUR)
            WHERE entrada.FECHA = ?
            AND entrada.ID_EMPLEADO IN ($placeholders)
            AND entrada.TIPO = 'ENTRADA'
            AND salida.ID_EMPLEADO IS NOT NULL
        ");

        $stmt->bindValue(1, $fecha, PDO::PARAM_STR);
        foreach ($empleados_asistieron_unique as $index => $empleadoId) {
            $stmt->bindValue($index + 2, $empleadoId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular horas trabajadas para cada par entrada-salida
        foreach ($registros as $registro) {
            $hora_entrada = $registro['hora_entrada'];
            $hora_salida = $registro['hora_salida'];
            $fecha_registro = $registro['FECHA'];

            if ($hora_entrada && $hora_salida) {
                $hora_inicio = strtotime($fecha_registro . ' ' . $hora_entrada);
                $hora_fin = strtotime($fecha_registro . ' ' . $hora_salida);

                // Si hora_salida < hora_entrada, es turno nocturno (salida al día siguiente)
                if ($hora_salida < $hora_entrada) {
                    $hora_fin = strtotime($fecha_registro . ' ' . $hora_salida . ' +1 day');
                }

                $diferencia_segundos = $hora_fin - $hora_inicio;
                $horas_trabajadas_totales += round($diferencia_segundos / 3600, 2);
            }
        }
    }

    return [
        'total_empleados' => $total_empleados,
        'total_presentes' => count($empleados_asistieron_unique),
        'llegadas_temprano' => $tempranos,     // Mapear nombre esperado por frontend
        'llegadas_tiempo' => $atiempo,         // Mapear nombre esperado por frontend  
        'llegadas_tarde' => $tardanzas,        // Mapear nombre esperado por frontend
        'horas_trabajadas' => $horas_trabajadas_totales,
        'tempranos' => $tempranos,
        'atiempo' => $atiempo,
        'tardanzas' => $tardanzas,
        'faltas' => $faltas,
        'presentes' => count($empleados_asistieron_unique)
    ];
}

/**
 * Obtiene distribución de asistencias simplificada (compatible con horarios tradicionales y personalizados)
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getDistribucionAsistenciasSimplified($empresaId, $fecha) {
    global $conn;
    
    try {
        // Obtener todos los empleados activos de la empresa
        $stmt = $conn->prepare("
            SELECT e.ID_EMPLEADO
            FROM empleado e
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            WHERE s.ID_EMPRESA = :empresaId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_empleados = count($empleados);

        // **CONSULTA SIMPLIFICADA: Obtener entradas solo con horarios personalizados**
        $stmt = $conn->prepare("
            SELECT 
                a.ID_EMPLEADO,
                a.HORA as entrada_hora,
                COALESCE(ehp.HORA_ENTRADA, '08:00:00') as HORA_ENTRADA,
                COALESCE(ehp.TOLERANCIA, 15) as tolerancia
            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            
            -- LEFT JOIN solo con horario personalizado VIGENTE
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
                AND ehp.ACTIVO = 'S'
            
            WHERE s.ID_EMPRESA = :empresaId
            AND a.FECHA = :fecha
            AND a.TIPO = 'ENTRADA'
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $empleados_asistieron = [];
        
        foreach ($asistencias as $asistencia) {
            $empleados_asistieron[] = $asistencia['ID_EMPLEADO'];
            
            $horaEntradaProgramada = $asistencia['HORA_ENTRADA'];
            $horaEntradaReal = $asistencia['entrada_hora'];
            $tolerancia = (int)($asistencia['tolerancia'] ?? 0);

            // Usar la función consistente para calcular el estado
            $estado = calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $tolerancia);

            switch ($estado) {
                case 'Temprano':
                    $llegadas_temprano++;
                    break;
                case 'Puntual':
                    $llegadas_tiempo++;
                    break;
                case 'Tardanza':
                    $llegadas_tarde++;
                    break;
            }
        }
        
        // Calcular faltas (turnos programados que no se cumplieron)
        $faltas = contarTurnosFaltantes($conn, 'empresa', $empresaId, $fecha);
        $empleados_asistieron_unique = array_unique($empleados_asistieron);
        
        return [
            'labels' => ['Temprano', 'A Tiempo', 'Tardanza', 'Ausente'],
            'data' => [$llegadas_temprano, $llegadas_tiempo, $llegadas_tarde, $faltas],
            'colors' => ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
        ];
        
    } catch (PDOException $e) {
        error_log("Error en getDistribucionAsistenciasSimplified: " . $e->getMessage());
        return [
            'labels' => ['Error'],
            'data' => [0],
            'colors' => ['#dc3545']
        ];
    }
}

/**
 * Obtiene asistencias por hora simplificada (compatible con horarios tradicionales y personalizados)
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getAsistenciasPorHoraSimplified($empresaId, $fecha) {
    global $conn;
    
    try {
        // **CONSULTA SIMPLIFICADA: Obtener entradas agrupadas por hora**
        $stmt = $conn->prepare("
            SELECT 
                CONCAT(LPAD(HOUR(CONCAT(a.HORA, ':00')), 2, '0'), ':00') as hora,
                COUNT(*) as cantidad
            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            WHERE s.ID_EMPRESA = :empresaId
            AND a.FECHA = :fecha
            AND a.TIPO = 'ENTRADA'
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
            GROUP BY HOUR(CONCAT(a.HORA, ':00'))
            ORDER BY HOUR(CONCAT(a.HORA, ':00'))
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear los datos para ApexCharts
        $categories = [];
        $data = [];
        
        foreach ($resultado as $row) {
            $categories[] = $row['hora'];
            $data[] = (int)$row['cantidad'];
        }
        
        return [
            'categories' => $categories,
            'data' => $data
        ];
        
    } catch (PDOException $e) {
        error_log("Error en getAsistenciasPorHoraSimplified: " . $e->getMessage());
        return [
            'categories' => [],
            'data' => []
        ];
    }
}

/**
 * Obtiene asistencias por hora para una sede
 */
function getAsistenciasPorHoraSedeSimplified($sedeId, $fecha) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT SUBSTRING(a.HORA, 1, 2) as hora, COUNT(*) as cantidad
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
    JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        WHERE est.ID_SEDE = :sedeId
          AND a.FECHA = :fecha
          AND a.TIPO = 'ENTRADA'
          AND e.ACTIVO = 'S'
        GROUP BY hora
        ORDER BY hora
    ");
    $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = [];
    $data = [];
    foreach ($result as $row) {
        $categories[] = $row['hora'] . ':00';
        $data[] = (int)$row['cantidad'];
    }
    return ['categories' => $categories, 'data' => $data];
}

/**
 * Obtiene asistencias por hora para un establecimiento
 */
function getAsistenciasPorHoraEstablecimientoSimplified($establecimientoId, $fecha) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT SUBSTRING(a.HORA, 1, 2) as hora, COUNT(*) as cantidad
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
          AND a.FECHA = :fecha
          AND a.TIPO = 'ENTRADA'
          AND e.ACTIVO = 'S'
        GROUP BY hora
        ORDER BY hora
    ");
    $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = [];
    $data = [];
    foreach ($result as $row) {
        $categories[] = $row['hora'] . ':00';
        $data[] = (int)$row['cantidad'];
    }
    return ['categories' => $categories, 'data' => $data];
}

/**
 * Obtiene distribución de asistencias para una sede con horarios simplificados
 */
function getDistribucionAsistenciasSedeSimplified($sedeId, $fecha) {
    global $conn;
    
    try {
        // Obtener todos los empleados activos de la sede
        $stmt = $conn->prepare("
            SELECT e.ID_EMPLEADO
            FROM empleado e
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            WHERE est.ID_SEDE = :sedeId AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        $stmt->execute();
        $total_empleados = $stmt->rowCount();
        
        // Obtener asistencias solo con horarios personalizados
        $stmt = $conn->prepare("
            SELECT a.ID_EMPLEADO, a.HORA as entrada_hora,
                   COALESCE(ehp.HORA_ENTRADA, '08:00:00') as HORA_ENTRADA,
                   COALESCE(ehp.tolerancia, 15) as tolerancia
            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
                AND ehp.ACTIVO = 'S'
            WHERE est.ID_SEDE = :sedeId
              AND a.FECHA = :fecha
              AND a.TIPO = 'ENTRADA'
              AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar por categorías usando lógica consistente
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $empleados_asistieron = [];
        
        foreach ($asistencias as $asistencia) {
            $empleados_asistieron[] = $asistencia['ID_EMPLEADO'];
            
            $horaEntradaProgramada = $asistencia['HORA_ENTRADA'];
            $horaEntradaReal = $asistencia['entrada_hora'];
            $tolerancia = (int)($asistencia['tolerancia'] ?? 0);

            $estado = calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $tolerancia);

            switch ($estado) {
                case 'Temprano':
                    $llegadas_temprano++;
                    break;
                case 'Puntual':
                    $llegadas_tiempo++;
                    break;
                case 'Tardanza':
                    $llegadas_tarde++;
                    break;
            }
        }
        
        // Calcular faltas (turnos programados que no se cumplieron)
        $faltas = contarTurnosFaltantes($conn, 'sede', $sedeId, $fecha);
        $empleados_asistieron_unique = array_unique($empleados_asistieron);
        
        return [
            'labels' => ['Temprano', 'A Tiempo', 'Tardanza', 'Ausente'],
            'data' => [$llegadas_temprano, $llegadas_tiempo, $llegadas_tarde, $faltas],
            'colors' => ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
        ];
        
    } catch (PDOException $e) {
        error_log("Error en getDistribucionAsistenciasSede: " . $e->getMessage());
        return [
            'labels' => ['Error'],
            'data' => [0],
            'colors' => ['#dc3545']
        ];
    }
}

/**
 * Obtiene distribución de asistencias para un establecimiento con horarios simplificados
 */
function getDistribucionAsistenciasEstablecimientoSimplified($establecimientoId, $fecha) {
    global $conn;
    
    try {
        // Obtener todos los empleados activos del establecimiento
        $stmt = $conn->prepare("
            SELECT ID_EMPLEADO
            FROM empleado
            WHERE ID_ESTABLECIMIENTO = :establecimientoId AND ACTIVO = 'S'
        ");
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->execute();
        $total_empleados = $stmt->rowCount();
        
        // Obtener asistencias solo con horarios personalizados
        $stmt = $conn->prepare("
            SELECT a.ID_EMPLEADO, a.HORA as entrada_hora,
                   COALESCE(ehp.HORA_ENTRADA, '08:00:00') as HORA_ENTRADA,
                   COALESCE(ehp.tolerancia, 15) as tolerancia
            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
                AND ehp.ACTIVO = 'S'
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
              AND a.FECHA = :fecha
              AND a.TIPO = 'ENTRADA'
              AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar por categorías usando lógica consistente
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $empleados_asistieron = [];
        
        foreach ($asistencias as $asistencia) {
            $empleados_asistieron[] = $asistencia['ID_EMPLEADO'];
            
            $horaEntradaProgramada = $asistencia['HORA_ENTRADA'];
            $horaEntradaReal = $asistencia['entrada_hora'];
            $tolerancia = (int)($asistencia['tolerancia'] ?? 0);

            $estado = calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $tolerancia);

            switch ($estado) {
                case 'Temprano':
                    $llegadas_temprano++;
                    break;
                case 'Puntual':
                    $llegadas_tiempo++;
                    break;
                case 'Tardanza':
                    $llegadas_tarde++;
                    break;
            }
        }
        
        // Calcular faltas (turnos programados que no se cumplieron)
        $faltas = contarTurnosFaltantes($conn, 'establecimiento', $establecimientoId, $fecha);
        $empleados_asistieron_unique = array_unique($empleados_asistieron);
        
        return [
            'labels' => ['Temprano', 'A Tiempo', 'Tardanza', 'Ausente'],
            'data' => [$llegadas_temprano, $llegadas_tiempo, $llegadas_tarde, $faltas],
            'colors' => ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
        ];
        
    } catch (PDOException $e) {
        error_log("Error en getDistribucionAsistenciasEstablecimiento: " . $e->getMessage());
        return [
            'labels' => ['Error'],
            'data' => [0],
            'colors' => ['#dc3545']
        ];
    }
}

/**
 * Obtiene actividad reciente para una sede
 */
function getActividadRecienteSedeSimplified($sedeId, $fecha = null, $limit = 10) {
    global $conn;
    
    if (!$fecha) {
        $fecha = getBogotaDate();
    }
    
    $stmt = $conn->prepare("
        SELECT a.*, e.NOMBRE, e.APELLIDO, est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
               CASE 
                   WHEN a.TIPO = 'ENTRADA' THEN 'Entrada'
                   WHEN a.TIPO = 'SALIDA' THEN 'Salida'
                   ELSE a.TIPO
               END as TIPO_DISPLAY
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
    JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        WHERE est.ID_SEDE = :sedeId
          AND a.FECHA = :fecha
        ORDER BY a.FECHA DESC, a.HORA DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene actividad reciente para un establecimiento
 */
function getActividadRecienteEstablecimientoSimplified($establecimientoId, $fecha = null, $limit = 10) {
    global $conn;
    
    if (!$fecha) {
        $fecha = getBogotaDate();
    }
    
    $stmt = $conn->prepare("
        SELECT a.*, e.NOMBRE, e.APELLIDO,
               CASE 
                   WHEN a.TIPO = 'ENTRADA' THEN 'Entrada'
                   WHEN a.TIPO = 'SALIDA' THEN 'Salida'
                   ELSE a.TIPO
               END as TIPO_DISPLAY
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
          AND a.FECHA = :fecha
        ORDER BY a.FECHA DESC, a.HORA DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>