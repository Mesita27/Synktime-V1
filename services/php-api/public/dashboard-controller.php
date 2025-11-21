<?php
// Incluir la conexión a la base de datos

// Configurar zona horaria de Bogotá, Colombia
require_once __DIR__ . '/config/timezone.php';
require_once 'config/database.php';
// Incluir utilidades de cálculo de estados de asistencia
require_once 'utils/attendance_status_utils.php';

/**
 * Obtiene información de la empresa
 * 
 * @param int $empresaId ID de la empresa
 * @return array|null Información de la empresa
 */
function getEmpresaInfo($empresaId) {
    global $conn; // Acceder a la conexión global
    
    try {
        $stmt = $conn->prepare("
            SELECT ID_EMPRESA, NOMBRE, RUC, DIRECCION
            FROM empresa
            WHERE ID_EMPRESA = :empresaId AND ESTADO = 'A'
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener información de empresa: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene las sedes de una empresa
 * 
 * @param int $empresaId ID de la empresa
 * @return array Sedes de la empresa
 */
function getSedesByEmpresa($empresaId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT ID_SEDE, NOMBRE, DIRECCION
            FROM sede
            WHERE ID_EMPRESA = :empresaId AND ESTADO = 'A'
            ORDER BY NOMBRE
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener sedes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los establecimientos de una empresa
 * 
 * @param int $empresaId ID de la empresa
 * @param int|null $sedeId ID de la sede (opcional)
 * @return array Establecimientos
 */
function getEstablecimientosByEmpresa($empresaId, $sedeId = null) {
    global $conn;
    
    try {
        $query = "
            SELECT e.ID_ESTABLECIMIENTO, e.NOMBRE, e.DIRECCION, s.ID_SEDE, s.NOMBRE as SEDE_NOMBRE
            FROM establecimiento e
            JOIN sede s ON e.ID_SEDE = s.ID_SEDE
            WHERE s.ID_EMPRESA = :empresaId AND e.ESTADO = 'A'
        ";
        
        if ($sedeId) {
            $query .= " AND s.ID_SEDE = :sedeId";
        }
        
        $query .= " ORDER BY s.NOMBRE, e.NOMBRE";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        
        if ($sedeId) {
            $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener establecimientos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene las estadísticas de asistencia basadas únicamente en registros de tipo ENTRADA
 * Usa el ID_HORARIO vinculado en la tabla ASISTENCIA para los cálculos
 * 
 * @param string $nivel Nivel de filtro: 'empresa', 'sede', 'establecimiento'
 * @param int $id ID del nivel seleccionado
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Estadísticas de asistencia
 */
function getEstadisticasAsistencia($nivel, $id, $fecha) {
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

    // Obtener todas las asistencias de tipo ENTRADA para la fecha y el nivel especificado
    $stmt = $conn->prepare("
        SELECT 
            a.ID_EMPLEADO,
            a.HORA as hora_entrada,
            a.TARDANZA,
            h.HORA_ENTRADA as horario_entrada,
            h.HORA_SALIDA as horario_salida,
            h.TOLERANCIA
        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        $join
        LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
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
        $tolerancia = (int)($asistencia['TOLERANCIA'] ?? 0);

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

    // Calcular faltas (empleados que no registraron entrada)
    $empleados_ids = array_column($empleados, 'ID_EMPLEADO');
    $empleados_asistieron_unique = array_unique($empleados_asistieron);
    $faltas = $total_empleados - count($empleados_asistieron_unique);

    // Calcular horas trabajadas totales del día
    $horas_trabajadas = calcularHorasTrabajadas($nivel, $id, $fecha);

    return [
        'total_empleados'      => $total_empleados,
        'llegadas_temprano'    => $tempranos,
        'llegadas_tiempo'      => $atiempo,
        'llegadas_tarde'       => $tardanzas,
        'faltas'               => $faltas,
        'total_asistencias'    => count($empleados_asistieron_unique),
        'horas_trabajadas'     => round($horas_trabajadas, 2)
    ];
}

/**
 * Calcula las horas trabajadas totales en el día considerando entradas y salidas
 * 
 * @param string $nivel Nivel de filtro: 'empresa', 'sede', 'establecimiento'
 * @param int $id ID del nivel seleccionado
 * @param string $fecha Fecha en formato Y-m-d
 * @return float Total de horas trabajadas
 */
function calcularHorasTrabajadas($nivel, $id, $fecha) {
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

    // Obtener todas las asistencias del día (entradas y salidas) por empleado
    $stmt = $conn->prepare("
        SELECT 
            a.ID_EMPLEADO,
            a.TIPO,
            a.HORA
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        $join
        WHERE a.FECHA = :fecha
        AND $where
        AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
        ORDER BY a.ID_EMPLEADO, a.HORA
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $horas_trabajadas_total = 0;
    $empleados_asistencias = [];

    // Agrupar asistencias por empleado
    foreach ($asistencias as $asistencia) {
        $empleados_asistencias[$asistencia['ID_EMPLEADO']][] = $asistencia;
    }

    // Calcular horas trabajadas para cada empleado
    foreach ($empleados_asistencias as $id_empleado => $asistencias_empleado) {
        $entradas = [];
        $salidas = [];

        // Separar entradas y salidas
        foreach ($asistencias_empleado as $asistencia) {
            if ($asistencia['TIPO'] === 'ENTRADA') {
                $entradas[] = $asistencia['HORA'];
            } else {
                $salidas[] = $asistencia['HORA'];
            }
        }

        // Calcular horas trabajadas emparejando entradas con salidas
        $horas_empleado = 0;
        for ($i = 0; $i < count($entradas); $i++) {
            if (isset($salidas[$i])) {
                $entrada = strtotime($fecha . ' ' . $entradas[$i]);
                $salida = strtotime($fecha . ' ' . $salidas[$i]);
                
                if ($salida > $entrada) {
                    $minutos_trabajados = ($salida - $entrada) / 60;
                    $horas_empleado += $minutos_trabajados / 60;
                }
            }
        }

        $horas_trabajadas_total += $horas_empleado;
    }

    return $horas_trabajadas_total;
}


/**
 * Obtiene datos para el gráfico de asistencias por hora (específico del establecimiento)
 * Basado únicamente en registros de tipo ENTRADA
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */

// SEDE: Entradas por hora en una sede
function getAsistenciasPorHoraSede($sedeId, $fecha) {
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

// ESTABLECIMIENTO: Entradas por hora en un establecimiento
function getAsistenciasPorHoraEstablecimiento($establecimientoId, $fecha) {
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
 * Obtiene datos para el gráfico de distribución de asistencias basado únicamente en registros ENTRADA
 * Usa el ID_HORARIO vinculado en la tabla ASISTENCIA
 * 
 * @param int $sedeId ID de la sede
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */

// SEDE: [Tempranos, A tiempo, Tardanzas, Faltas]
function getDistribucionAsistenciasSede($sedeId, $fecha) {
    global $conn;
    
    try {
        // Obtener todos los empleados activos de la sede
        $stmt = $conn->prepare("
            SELECT e.ID_EMPLEADO
            FROM empleado e
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            WHERE est.ID_SEDE = :sedeId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_empleados = count($empleados);

        // Obtener asistencias de ENTRADA para la fecha usando ID_HORARIO de la tabla ASISTENCIA
        $stmt = $conn->prepare("
            SELECT 
                a.ID_EMPLEADO,
                a.HORA as entrada_hora,
                h.HORA_ENTRADA,
                h.TOLERANCIA
            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
            WHERE est.ID_SEDE = :sedeId
            AND a.FECHA = :fecha
            AND a.TIPO = 'ENTRADA'
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $empleados_asistieron = [];
        
        foreach ($asistencias as $asistencia) {
            $empleados_asistieron[] = $asistencia['ID_EMPLEADO'];
            
            if (!$asistencia['HORA_ENTRADA']) {
                // Si no tiene horario asignado, considerarlo a tiempo
                $llegadas_tiempo++;
                continue;
            }

            $horaEntradaProgramada = $asistencia['HORA_ENTRADA'];
            $horaEntradaReal = $asistencia['entrada_hora'];
            $tolerancia = (int)($asistencia['TOLERANCIA'] ?? 0);

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
        
        // Calcular faltas
        $empleados_asistieron_unique = array_unique($empleados_asistieron);
        $faltas = $total_empleados - count($empleados_asistieron_unique);
        
        return [
            'series' => [
                $llegadas_temprano,
                $llegadas_tiempo,
                $llegadas_tarde,
                $faltas
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias de sede: " . $e->getMessage());
        return [
            'series' => [0, 0, 0, 0]
        ];
    }
}

// ESTABLECIMIENTO: [Tempranos, A tiempo, Tardanzas, Faltas]
function getDistribucionAsistenciasEstablecimiento($establecimientoId, $fecha) {
    global $conn;
    
    try {
        // Obtener todos los empleados activos del establecimiento
        $stmt = $conn->prepare("
            SELECT e.ID_EMPLEADO
            FROM empleado e
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_empleados = count($empleados);

        // Obtener asistencias de ENTRADA para la fecha usando ID_HORARIO de la tabla ASISTENCIA
        $stmt = $conn->prepare("
            SELECT 
                a.ID_EMPLEADO,
                a.HORA as entrada_hora,
                h.HORA_ENTRADA,
                h.TOLERANCIA
            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            AND a.FECHA = :fecha
            AND a.TIPO = 'ENTRADA'
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $empleados_asistieron = [];
        
        foreach ($asistencias as $asistencia) {
            $empleados_asistieron[] = $asistencia['ID_EMPLEADO'];
            
            if (!$asistencia['HORA_ENTRADA']) {
                // Si no tiene horario asignado, considerarlo a tiempo
                $llegadas_tiempo++;
                continue;
            }

            $horaEntradaProgramada = $asistencia['HORA_ENTRADA'];
            $horaEntradaReal = $asistencia['entrada_hora'];
            $tolerancia = (int)($asistencia['TOLERANCIA'] ?? 0);

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
        
        // Calcular faltas
        $empleados_asistieron_unique = array_unique($empleados_asistieron);
        $faltas = $total_empleados - count($empleados_asistieron_unique);
        
        return [
            'series' => [
                $llegadas_temprano,
                $llegadas_tiempo,
                $llegadas_tarde,
                $faltas
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias de establecimiento: " . $e->getMessage());
        return [
            'series' => [0, 0, 0, 0]
        ];
    }
}

/**
 * Obtiene la actividad reciente de asistencias basada únicamente en registros ENTRADA
 * (específico del establecimiento)
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @param int $limit Límite de registros
 * @return array Registros de actividad
 */
// ESTABLECIMIENTO: Últimas actividades en un establecimiento (solo ENTRADA)
function getActividadRecienteEstablecimiento($establecimientoId, $fecha = null, $limit = 10) {
    global $conn;
    if (!$fecha) $fecha = getBogotaDate();
    $stmt = $conn->prepare("
        SELECT a.ID_ASISTENCIA, e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, a.HORA, a.TIPO, a.TARDANZA, a.OBSERVACION, s.NOMBRE as SEDE_NOMBRE, est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
               h.HORA_ENTRADA, h.HORA_SALIDA, h.TOLERANCIA
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
    JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
        WHERE est.ID_ESTABLECIMIENTO = :establecimientoId 
        AND a.FECHA = :fecha 
        AND e.ACTIVO = 'S'
        ORDER BY a.ID_ASISTENCIA DESC
        LIMIT :limite
    ");
    $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estados para cada actividad
    foreach ($actividades as &$actividad) {
        if ($actividad['TIPO'] == 'ENTRADA') {
            $horaProgramada = $actividad['HORA_ENTRADA'];
            $horaReal = $actividad['HORA'];
            $tolerancia = (int)($actividad['TOLERANCIA'] ?? 0);
            
            $estado = calcularEstadoEntrada($horaProgramada, $horaReal, $tolerancia);
            $actividad['ESTADO_ENTRADA'] = $estado;
        } elseif ($actividad['TIPO'] == 'SALIDA') {
            $horaProgramada = $actividad['HORA_SALIDA'];
            $horaReal = $actividad['HORA'];
            $tolerancia = (int)($actividad['TOLERANCIA'] ?? 0);
            
            $estado = calcularEstadoSalida($horaProgramada, $horaReal, $tolerancia);
            $actividad['ESTADO_SALIDA'] = $estado;
        }
    }
    
    return $actividades;
}

/**
 * Obtiene datos para el gráfico de asistencias por hora (nivel empresa)
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getAsistenciasPorHora($empresaId, $fecha) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT SUBSTRING(a.HORA, 1, 2) as hora, COUNT(*) as cantidad
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
    JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresaId
          AND a.FECHA = :fecha
          AND a.TIPO = 'ENTRADA'
          AND e.ACTIVO = 'S'
        GROUP BY hora
        ORDER BY hora
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
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
 * Obtiene datos para el gráfico de distribución de asistencias (nivel empresa)
 * Basado únicamente en registros ENTRADA usando ID_HORARIO de la tabla ASISTENCIA
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getDistribucionAsistencias($empresaId, $fecha) {
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

        // Obtener asistencias de ENTRADA para la fecha usando ID_HORARIO de la tabla ASISTENCIA
        $stmt = $conn->prepare("
            SELECT 
                a.ID_EMPLEADO,
                a.HORA as entrada_hora,
                h.HORA_ENTRADA,
                h.TOLERANCIA
            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
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
            
            if (!$asistencia['HORA_ENTRADA']) {
                // Si no tiene horario asignado, considerarlo a tiempo
                $llegadas_tiempo++;
                continue;
            }

            $horaEntradaProgramada = $asistencia['HORA_ENTRADA'];
            $horaEntradaReal = $asistencia['entrada_hora'];
            $tolerancia = (int)($asistencia['TOLERANCIA'] ?? 0);

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
        
        // Calcular faltas
        $empleados_asistieron_unique = array_unique($empleados_asistieron);
        $faltas = $total_empleados - count($empleados_asistieron_unique);
        
        return [
            'series' => [
                $llegadas_temprano,
                $llegadas_tiempo,
                $llegadas_tarde,
                $faltas
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias: " . $e->getMessage());
        return [
            'series' => [0, 0, 0, 0]
        ];
    }
}

/**
 * Obtiene la actividad reciente de asistencias (nivel empresa)
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @param int $limit Límite de registros
 * @return array Registros de actividad
 */
// EMPRESA: Últimas actividades en la empresa (solo ENTRADA)
function getActividadReciente($empresaId, $fecha = null, $limit = 10) {
    global $conn;
    if (!$fecha) $fecha = getBogotaDate();
    $stmt = $conn->prepare("
        SELECT a.ID_ASISTENCIA, e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, a.HORA, a.TIPO, a.TARDANZA, a.OBSERVACION, s.NOMBRE as SEDE_NOMBRE, est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
               h.HORA_ENTRADA, h.HORA_SALIDA, h.TOLERANCIA
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
    JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
        WHERE s.ID_EMPRESA = :empresaId 
        AND a.FECHA = :fecha 
        AND e.ACTIVO = 'S'
        ORDER BY a.ID_ASISTENCIA DESC
        LIMIT :limite
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estados para cada actividad
    foreach ($actividades as &$actividad) {
        if ($actividad['TIPO'] == 'ENTRADA') {
            $horaProgramada = $actividad['HORA_ENTRADA'];
            $horaReal = $actividad['HORA'];
            $tolerancia = (int)($actividad['TOLERANCIA'] ?? 0);
            
            $estado = calcularEstadoEntrada($horaProgramada, $horaReal, $tolerancia);
            $actividad['ESTADO_ENTRADA'] = $estado;
        } elseif ($actividad['TIPO'] == 'SALIDA') {
            $horaProgramada = $actividad['HORA_SALIDA'];
            $horaReal = $actividad['HORA'];
            $tolerancia = (int)($actividad['TOLERANCIA'] ?? 0);
            
            $estado = calcularEstadoSalida($horaProgramada, $horaReal, $tolerancia);
            $actividad['ESTADO_SALIDA'] = $estado;
        }
    }
    
    return $actividades;
}

// SEDE: Últimas actividades en una sede (ENTRADA y SALIDA)
function getActividadRecienteSede($sedeId, $fecha = null, $limit = 10) {
    global $conn;
    if (!$fecha) $fecha = getBogotaDate();
    $stmt = $conn->prepare("
        SELECT a.ID_ASISTENCIA, e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, a.HORA, a.TIPO, a.TARDANZA, a.OBSERVACION, s.NOMBRE as SEDE_NOMBRE, est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
               h.HORA_ENTRADA, h.HORA_SALIDA, h.TOLERANCIA
    FROM asistencia a
    JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
    JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
        WHERE s.ID_SEDE = :sedeId 
        AND a.FECHA = :fecha 
        AND e.ACTIVO = 'S'
        ORDER BY a.ID_ASISTENCIA DESC
        LIMIT :limite
    ");
    $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estados para cada actividad
    foreach ($actividades as &$actividad) {
        if ($actividad['TIPO'] == 'ENTRADA') {
            $horaProgramada = $actividad['HORA_ENTRADA'];
            $horaReal = $actividad['HORA'];
            $tolerancia = (int)($actividad['TOLERANCIA'] ?? 0);
            
            $estado = calcularEstadoEntrada($horaProgramada, $horaReal, $tolerancia);
            $actividad['ESTADO_ENTRADA'] = $estado;
        } elseif ($actividad['TIPO'] == 'SALIDA') {
            $horaProgramada = $actividad['HORA_SALIDA'];
            $horaReal = $actividad['HORA'];
            $tolerancia = (int)($actividad['TOLERANCIA'] ?? 0);
            
            $estado = calcularEstadoSalida($horaProgramada, $horaReal, $tolerancia);
            $actividad['ESTADO_SALIDA'] = $estado;
        }
    }
    
    return $actividades;
}

/**
 * Determina si una llegada es tardía según el horario del empleado
 * 
 * @param int $idEmpleado ID del empleado
 * @param string $hora Hora en formato HH:MM
 * @param string $fecha Fecha en formato Y-m-d
 * @return bool True si es tardanza, false si no
 */
function esTardanza($idEmpleado, $hora, $fecha) {
    $horario = getHorarioEmpleado($idEmpleado, $fecha);
    if (!$horario) return false;
    
    // Convertir a minutos para comparación numérica
    $horaEntradaPartes = explode(':', $horario['HORA_ENTRADA']);
    $minutosHoraEntrada = (int)$horaEntradaPartes[0] * 60 + (int)$horaEntradaPartes[1];
    
    $horaLlegadaPartes = explode(':', $hora);
    $minutosHoraLlegada = (int)$horaLlegadaPartes[0] * 60 + (int)$horaLlegadaPartes[1];
    
    // Considerar tolerancia
    $toleranciaMinutos = (int)$horario['TOLERANCIA'];
    
    return $minutosHoraLlegada > ($minutosHoraEntrada + $toleranciaMinutos);
}

/**
 * Obtiene el horario de un empleado para una fecha específica
 * 
 * @param int $idEmpleado ID del empleado
 * @param string $fecha Fecha en formato Y-m-d
 * @return array|null Datos del horario o null si no se encuentra
 */
function getHorarioEmpleado($idEmpleado, $fecha) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT h.*
            FROM empleado_horario eh
            JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE eh.ID_EMPLEADO = :idEmpleado
            AND eh.FECHA_DESDE <= :fecha
            AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
            ORDER BY eh.FECHA_DESDE DESC
            LIMIT 1
        ");
        
        $stmt->bindParam(':idEmpleado', $idEmpleado, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado ? $resultado : null;
        
    } catch (PDOException $e) {
        error_log("Error al obtener horario del empleado: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene información del usuario logueado
 * 
 * @param string $username Nombre de usuario
 * @return array|null Información del usuario
 */
function getUsuarioInfo($username) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                u.ID_USUARIO,
                u.USERNAME,
                u.NOMBRE_COMPLETO,
                u.EMAIL,
                u.ROL,
                u.ID_EMPRESA,
                e.NOMBRE as EMPRESA_NOMBRE
            FROM usuario u
            JOIN empresa e ON u.ID_EMPRESA = e.ID_EMPRESA
            WHERE u.USERNAME = :username 
            AND u.ESTADO = 'A'
        ");
        
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener información del usuario: " . $e->getMessage());
        return null;
    }
}
?>
