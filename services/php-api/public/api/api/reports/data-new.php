<?php
/**
 * Nueva API de Reportes de Asistencia - Versión Completa
 * Maneja horarios personalizados, turnos nocturnos, y cálculos avanzados
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
// TEMPORALMENTE DESACTIVADO PARA PRUEBAS
/*
if (!isset($_SESSION['id_empresa'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}
*/

$empresaId = $_SESSION['id_empresa'] ?? 1; // Valor por defecto para pruebas

/**
 * Determina si una asistencia específica es nocturna basada en horas reales
 * Una asistencia es nocturna si:
 * 1. La entrada y salida están en días diferentes (dentro de 24 horas)
 * 2. Y la hora de salida es menor que la hora de entrada
 */
function esAsistenciaNocturna($horaEntrada, $horaSalida, $fechaEntrada, $fechaSalida) {
    // Si no hay entrada o salida, no puede ser asistencia nocturna
    if (!$horaEntrada || !$horaSalida || !$fechaEntrada || !$fechaSalida) {
        return false;
    }

    // Si las fechas son diferentes (salida al día siguiente)
    if ($fechaEntrada !== $fechaSalida) {
        // Verificar que la diferencia sea de exactamente 1 día (no más de 24 horas)
        $fechaEntradaObj = new DateTime($fechaEntrada);
        $fechaSalidaObj = new DateTime($fechaSalida);
        $diferenciaDias = $fechaEntradaObj->diff($fechaSalidaObj)->days;

        if ($diferenciaDias === 1) {
            // Verificar que la hora de salida sea menor que la hora de entrada
            if (strtotime($horaSalida) < strtotime($horaEntrada)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Calcula horas trabajadas considerando asistencias nocturnas
 */
function calcularHorasTrabajadas($horaEntrada, $horaSalida, $fechaEntrada, $fechaSalida) {
    if (!$horaEntrada || !$horaSalida) {
        return 0;
    }

    try {
        $entrada = new DateTime($fechaEntrada . ' ' . $horaEntrada);
        $salida = new DateTime($fechaSalida . ' ' . $horaSalida);

        // Detectar si es asistencia nocturna
        $esAsistenciaNocturna = esAsistenciaNocturna($horaEntrada, $horaSalida, $fechaEntrada, $fechaSalida);

        // Si es asistencia nocturna, ajustar el cálculo
        if ($esAsistenciaNocturna) {
            // Para asistencias nocturnas: calcular desde entrada hasta medianoche,
            // más desde medianoche del día siguiente hasta salida
            $medianocheEntrada = new DateTime($fechaEntrada . ' 23:59:59');
            $medianocheSalida = new DateTime($fechaSalida . ' 00:00:00');

            // Cálculo más preciso en segundos
            $segundosHastaMedianoche = $medianocheEntrada->getTimestamp() - $entrada->getTimestamp();
            $horasHastaMedianoche = $segundosHastaMedianoche / 3600;

            $segundosDesdeMedianoche = $salida->getTimestamp() - $medianocheSalida->getTimestamp();
            $horasDesdeMedianoche = $segundosDesdeMedianoche / 3600;

            $horas = $horasHastaMedianoche + $horasDesdeMedianoche;
        } else {
            // Cálculo normal
            $intervalo = $entrada->diff($salida);
            $horas = $intervalo->h + ($intervalo->i / 60);

            // Verificar si la salida es anterior a la entrada en la misma fecha
            $mismoDiaSalidaAntes = ($fechaEntrada === $fechaSalida) && (strtotime($horaSalida) < strtotime($horaEntrada));

            // Si las horas son negativas, hay error de datos, o diferencia de días > 1
            $fechaEntradaObj = new DateTime($fechaEntrada);
            $fechaSalidaObj = new DateTime($fechaSalida);
            $diasDiferencia = $fechaEntradaObj->diff($fechaSalidaObj)->days;

            if ($horas < 0 || $mismoDiaSalidaAntes || $diasDiferencia > 1) {
                $horas = 0;
            }
        }

        return round($horas, 2);
    } catch (Exception $e) {
        error_log("Error calculando horas trabajadas: " . $e->getMessage());
        return 0;
    }
}

/**
 * Formatea horas decimales a formato HH:MM
 */
function formatHorasTrabajadas($horasDecimal) {
    if (!$horasDecimal || $horasDecimal <= 0) {
        return '00:00';
    }

    $horas = floor($horasDecimal);
    $minutos = round(($horasDecimal - $horas) * 60);

    return sprintf('%02d:%02d', $horas, $minutos);
}

/**
 * Obtiene información completa de un empleado incluyendo todos sus horarios
 */
function getEmployeeCompleteInfo($employeeCode) {
    // Obtener la conexión de la base de datos desde el archivo de configuración
    require_once __DIR__ . '/../../config/database.php';
    global $empresaId;

    error_log("=== INICIANDO getEmployeeCompleteInfo ===");
    error_log("Parámetros - employeeCode: $employeeCode, empresaId: $empresaId");
    error_log("Conexión disponible: " . ($conn ? "sí" : "no"));

    try {
        // Obtener información básica del empleado
        // Primero intentar con el filtro de empresa
        $sqlEmployee = "
            SELECT
                e.ID_EMPLEADO as codigo,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
                e.NOMBRE as nombre,
                e.APELLIDO as apellido,
                e.DNI,
                e.FECHA_NACIMIENTO,
                e.FECHA_INGRESO,
                CASE
                    WHEN e.ACTIVO = 'S' THEN 'Activo'
                    ELSE 'Inactivo'
                END as estado,
                s.ID_SEDE,
                s.NOMBRE as sede,
                est.ID_ESTABLECIMIENTO,
                est.NOMBRE as establecimiento,
                e.CORREO
            FROM EMPLEADO e
            INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?
        ";

        $stmtEmployee = $conn->prepare($sqlEmployee);
        $stmtEmployee->execute([$employeeCode, $empresaId]);
        $employee = $stmtEmployee->fetch(PDO::FETCH_ASSOC);

        // Si no se encuentra con el filtro de empresa, intentar sin filtro (para casos donde el empleado existe pero la relación de empresa está mal)
        if (!$employee) {
            error_log("Empleado $employeeCode no encontrado con filtro de empresa $empresaId, intentando sin filtro");

            $sqlEmployeeNoFilter = "
                SELECT
                    e.ID_EMPLEADO as codigo,
                    CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
                    e.NOMBRE as nombre,
                    e.APELLIDO as apellido,
                    e.DNI,
                    e.FECHA_NACIMIENTO,
                    e.FECHA_INGRESO,
                    CASE
                        WHEN e.ACTIVO = 'S' THEN 'Activo'
                        ELSE 'Inactivo'
                    END as estado,
                    COALESCE(s.ID_SEDE, 0) as ID_SEDE,
                    COALESCE(s.NOMBRE, 'Sin sede') as sede,
                    COALESCE(est.ID_ESTABLECIMIENTO, 0) as ID_ESTABLECIMIENTO,
                    COALESCE(est.NOMBRE, 'Sin establecimiento') as establecimiento,
                    e.CORREO
                FROM EMPLEADO e
                LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                LEFT JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
                WHERE e.ID_EMPLEADO = ?
            ";

            $stmtEmployeeNoFilter = $conn->prepare($sqlEmployeeNoFilter);
            $stmtEmployeeNoFilter->execute([$employeeCode]);
            $employee = $stmtEmployeeNoFilter->fetch(PDO::FETCH_ASSOC);
        }

        if (!$employee) {
            error_log("Empleado no encontrado ni siquiera sin filtro: $employeeCode");
            return null;
        }

        error_log("Empleado encontrado: {$employee['nombre_completo']} (ID: {$employee['codigo']})");

        // Obtener todos los horarios del empleado (tanto regulares como personalizados)
        $sqlSchedules = "
            SELECT
                eh.ID_EMPLEADO_HORARIO,
                eh.ID_HORARIO,
                CASE
                    WHEN eh.ID_EMPLEADO_HORARIO IS NOT NULL THEN
                        COALESCE(eh.NOMBRE_TURNO, CONCAT('Horario Personalizado (', TIME_FORMAT(eh.HORA_ENTRADA, '%H:%i'), '-', TIME_FORMAT(eh.HORA_SALIDA, '%H:%i'), ')'))
                    WHEN h.ID_HORARIO IS NOT NULL THEN
                        h.NOMBRE
                    ELSE 'Sin asignar'
                END as nombre_horario,
                COALESCE(eh.HORA_ENTRADA, h.HORA_ENTRADA) as hora_entrada,
                COALESCE(eh.HORA_SALIDA, h.HORA_SALIDA) as hora_salida,
                COALESCE(eh.TOLERANCIA, h.TOLERANCIA, 0) as tolerancia,
                eh.FECHA_DESDE as fecha_inicio_vigencia,
                eh.FECHA_HASTA as fecha_fin_vigencia,
                CASE
                    WHEN eh.ID_EMPLEADO_HORARIO IS NOT NULL THEN 'personalizado'
                    ELSE 'regular'
                END as tipo_horario,
                CASE
                    WHEN eh.ID_EMPLEADO_HORARIO IS NOT NULL THEN
                        CASE WHEN eh.ACTIVO = 'S' THEN 'S' ELSE 'N' END
                    WHEN eh.ID_EMPLEADO IS NOT NULL THEN 'S'
                    ELSE 'N'
                END as activo,
                -- Días de la semana para horarios personalizados
                eh.LUNES, eh.MARTES, eh.MIERCOLES, eh.JUEVES, eh.VIERNES, eh.SABADO, eh.DOMINGO,
                -- Descripción del horario personalizado
                eh.DESCRIPCION
            FROM EMPLEADO_HORARIO eh_rel
            LEFT JOIN HORARIO h ON eh_rel.ID_HORARIO = h.ID_HORARIO
            LEFT JOIN empleado_horario_personalizado eh ON eh_rel.ID_EMPLEADO_HORARIO = eh.ID_EMPLEADO_HORARIO
            WHERE eh_rel.ID_EMPLEADO = ? AND eh_rel.ACTIVO = 'S'
            ORDER BY eh.FECHA_DESDE DESC, h.NOMBRE ASC
        ";

        $stmtSchedules = $conn->prepare($sqlSchedules);
        $stmtSchedules->execute([$employeeCode]);
        $schedulesRaw = $stmtSchedules->fetchAll(PDO::FETCH_ASSOC);

        error_log("Horarios encontrados para empleado $employeeCode: " . count($schedulesRaw));

        // Procesar y formatear los horarios
        $horarios = [];
        foreach ($schedulesRaw as $schedule) {
            // Determinar días de la semana legibles
            $diasSemana = [];
            if ($schedule['tipo_horario'] === 'personalizado') {
                $diasMap = [
                    'LUNES' => 'Lunes',
                    'MARTES' => 'Martes',
                    'MIERCOLES' => 'Miércoles',
                    'JUEVES' => 'Jueves',
                    'VIERNES' => 'Viernes',
                    'SABADO' => 'Sábado',
                    'DOMINGO' => 'Domingo'
                ];

                foreach ($diasMap as $dia => $diaLegible) {
                    if ($schedule[$dia] === 'S') {
                        $diasSemana[] = $diaLegible;
                    }
                }
            } else {
                // Para horarios regulares, obtener días de la tabla HORARIO_DIA
                $sqlDias = "
                    SELECT d.NOMBRE as dia
                    FROM HORARIO_DIA hd
                    INNER JOIN DIA d ON hd.ID_DIA = d.ID_DIA
                    WHERE hd.ID_HORARIO = ?
                    ORDER BY hd.ID_DIA
                ";
                $stmtDias = $conn->prepare($sqlDias);
                $stmtDias->execute([$schedule['ID_HORARIO']]);
                $diasSemana = array_column($stmtDias->fetchAll(PDO::FETCH_ASSOC), 'dia');
            }

            $diasSemanaLegibles = implode(', ', $diasSemana);

            $horarios[] = [
                'id_horario' => $schedule['ID_HORARIO'],
                'id_empleado_horario' => $schedule['ID_EMPLEADO_HORARIO'],
                'nombre_horario' => $schedule['nombre_horario'],
                'hora_entrada' => $schedule['hora_entrada'] ? date('H:i', strtotime($schedule['hora_entrada'])) : null,
                'hora_salida' => $schedule['hora_salida'] ? date('H:i', strtotime($schedule['hora_salida'])) : null,
                'tolerancia' => (int)$schedule['tolerancia'],
                'fecha_inicio_vigencia' => $schedule['fecha_inicio_vigencia'],
                'fecha_fin_vigencia' => $schedule['fecha_fin_vigencia'],
                'tipo_horario' => $schedule['tipo_horario'],
                'activo' => $schedule['activo'] === 'S',
                'dias_semana' => $diasSemana,
                'dias_semana_legibles' => $diasSemanaLegibles,
                'descripcion' => $schedule['DESCRIPCION']
            ];
        }

        // Formatear las fechas
        $employee['fecha_nacimiento'] = $employee['FECHA_NACIMIENTO'] ? date('d/m/Y', strtotime($employee['FECHA_NACIMIENTO'])) : null;
        $employee['fecha_ingreso'] = $employee['FECHA_INGRESO'] ? date('d/m/Y', strtotime($employee['FECHA_INGRESO'])) : null;

        // Estructura final del empleado
        $result = [
            'codigo' => $employee['codigo'],
            'nombre_completo' => $employee['nombre_completo'],
            'nombre' => $employee['nombre'],
            'apellido' => $employee['apellido'],
            'dni' => $employee['DNI'],
            'fecha_nacimiento' => $employee['fecha_nacimiento'],
            'fecha_ingreso' => $employee['fecha_ingreso'],
            'estado' => $employee['estado'],
            'sede' => [
                'id' => $employee['ID_SEDE'],
                'nombre' => $employee['sede']
            ],
            'establecimiento' => [
                'id' => $employee['ID_ESTABLECIMIENTO'],
                'nombre' => $employee['establecimiento']
            ],
            'correo' => $employee['CORREO'],
            'horarios' => $horarios
        ];

        error_log("Retornando información completa del empleado $employeeCode con " . count($horarios) . " horarios");
        return $result;

    } catch (Exception $e) {
        error_log("Error obteniendo información completa del empleado: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene los datos de reportes con filtros avanzados
 */
function getReportesData($filtros = [], $pagina = 1, $limite = 10) {
    global $empresaId, $conn;

    try {
        // Verificar que la conexión esté disponible
        if (!$conn) {
            throw new Exception("No se pudo establecer conexión con la base de datos");
        }

        // Construir consulta base con JOIN a EMPLEADO_HORARIO
        $where = [];
        $params = [$empresaId];

        // Filtros de fecha
        if (!empty($filtros['fecha_desde'])) {
            $where[] = "combined.FECHA >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "combined.FECHA <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        // Filtro de tipo de reporte rápido
        if (!empty($filtros['tipo_reporte'])) {
            switch ($filtros['tipo_reporte']) {
                case 'dia':
                    $where[] = "combined.FECHA = CURDATE()";
                    break;
                case 'semana':
                    $where[] = "YEARWEEK(combined.FECHA, 1) = YEARWEEK(CURDATE(), 1)";
                    break;
                case 'mes':
                    $where[] = "YEAR(combined.FECHA) = YEAR(CURDATE()) AND MONTH(combined.FECHA) = MONTH(CURDATE())";
                    break;
            }
        }

        // Filtros de empleado
        if (!empty($filtros['codigo'])) {
            $where[] = "e.ID_EMPLEADO = ?";
            $params[] = $filtros['codigo'];
        }

        if (!empty($filtros['nombre'])) {
            $where[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
            $params[] = '%' . $filtros['nombre'] . '%';
            $params[] = '%' . $filtros['nombre'] . '%';
        }

        // Filtros de ubicación
        if (!empty($filtros['sede']) && $filtros['sede'] !== 'Todas') {
            $where[] = "s.ID_SEDE = ?";
            $params[] = $filtros['sede'];
        }

        if (!empty($filtros['establecimiento']) && $filtros['establecimiento'] !== 'Todos') {
            $where[] = "est.ID_ESTABLECIMIENTO = ?";
            $params[] = $filtros['establecimiento'];
        }

        $whereClause = implode(' AND ', $where);

        // Consulta principal que agrupa por empleado y fecha
        $sql = "
            SELECT
                e.ID_EMPLEADO as codigo_empleado,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
                s.NOMBRE as sede,
                est.NOMBRE as establecimiento,
                combined.FECHA as fecha,

                -- Horarios disponibles
                COALESCE(ehp.ID_EMPLEADO_HORARIO, h.ID_HORARIO) as id_horario_asignado,
                CASE
                    WHEN ehp.ID_EMPLEADO_HORARIO IS NOT NULL THEN
                        COALESCE(ehp.NOMBRE_TURNO, CONCAT('Horario Personalizado (', TIME_FORMAT(ehp.HORA_ENTRADA, '%H:%i'), '-', TIME_FORMAT(ehp.HORA_SALIDA, '%H:%i'), ')'))
                    WHEN h.ID_HORARIO IS NOT NULL THEN
                        CONCAT(h.NOMBRE, ' (', TIME_FORMAT(h.HORA_ENTRADA, '%H:%i'), '-', TIME_FORMAT(h.HORA_SALIDA, '%H:%i'), ')')
                    ELSE 'Sin asignar'
                END as nombre_horario,

                -- Horas programadas (usando horario personalizado si existe)
                COALESCE(ehp.HORA_ENTRADA, h.HORA_ENTRADA) as hora_entrada_programada,
                COALESCE(ehp.HORA_SALIDA, h.HORA_SALIDA) as hora_salida_programada,
                COALESCE(ehp.TOLERANCIA, h.TOLERANCIA, 0) as tolerancia_minutos,

                -- Registro de entrada
                a_entrada.ID_ASISTENCIA as id_asistencia_entrada,
                a_entrada.FECHA as fecha_entrada_real,
                a_entrada.HORA as hora_entrada_real,
                a_entrada.TARDANZA as tardanza_entrada,
                a_entrada.OBSERVACION as observacion_entrada,

                -- Registro de salida
                a_salida.ID_ASISTENCIA as id_asistencia_salida,
                a_salida.FECHA as fecha_salida_real,
                a_salida.HORA as hora_salida_real,
                a_salida.TARDANZA as tardanza_salida,
                a_salida.OBSERVACION as observacion_salida,

                -- Información de justificaciones
                j.num_justificaciones as num_justificaciones,
                j.ids_justificaciones as ids_justificaciones,
                j.motivos_justificaciones as motivos_justificaciones,
                j.turnos_justificados as turnos_justificados,
                j.nombres_turnos as nombres_turnos,

                -- Información adicional
                combined.TIPO_HORARIO as tipo_horario_sistema,
                ehp.ID_EMPLEADO_HORARIO as id_empleado_horario,
                CASE WHEN ehp.ID_EMPLEADO_HORARIO IS NOT NULL THEN 'S' ELSE 'N' END as tiene_horario_personalizado

            FROM (
                -- Combinar fechas de asistencias y justificaciones
                SELECT DISTINCT a.ID_EMPLEADO, a.FECHA, a.ID_HORARIO, a.ID_EMPLEADO_HORARIO, a.TIPO_HORARIO
                FROM ASISTENCIA a
                WHERE a.TIPO = 'ENTRADA'
                
                UNION
                
                SELECT DISTINCT j.empleado_id as ID_EMPLEADO, j.fecha_falta as FECHA, NULL as ID_HORARIO, j.turno_id as ID_EMPLEADO_HORARIO, NULL as TIPO_HORARIO
                FROM justificaciones j
                WHERE j.estado = 'aprobada' AND j.deleted_at IS NULL
            ) combined
            
            INNER JOIN EMPLEADO e ON combined.ID_EMPLEADO = e.ID_EMPLEADO
            INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE

            -- LEFT JOIN para horario normal
            LEFT JOIN HORARIO h ON h.ID_HORARIO = combined.ID_HORARIO

            -- LEFT JOIN para horario personalizado (obtener horas del horario personalizado)
            LEFT JOIN empleado_horario_personalizado ehp ON combined.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO

            -- LEFT JOIN para relación empleado-horario (para determinar si tiene horario personalizado)
            LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                AND combined.FECHA BETWEEN eh.FECHA_DESDE AND COALESCE(eh.FECHA_HASTA, CURDATE())
                AND eh.ACTIVO = 'S'

            -- LEFT JOIN para obtener entrada del día
            LEFT JOIN ASISTENCIA a_entrada ON a_entrada.ID_EMPLEADO = combined.ID_EMPLEADO
                AND a_entrada.FECHA = combined.FECHA
                AND a_entrada.TIPO = 'ENTRADA'
                AND a_entrada.ID_EMPLEADO_HORARIO = combined.ID_EMPLEADO_HORARIO

            -- LEFT JOIN para obtener salida del día (misma fecha o día siguiente para nocturnas)
            LEFT JOIN ASISTENCIA a_salida ON a_salida.ID_EMPLEADO = combined.ID_EMPLEADO
                AND (
                    (a_salida.FECHA = combined.FECHA AND a_salida.TIPO = 'SALIDA' AND a_salida.ID_EMPLEADO_HORARIO = combined.ID_EMPLEADO_HORARIO)
                    OR
                    (a_salida.FECHA = DATE_ADD(combined.FECHA, INTERVAL 1 DAY) AND a_salida.TIPO = 'SALIDA'
                     AND a_salida.ID_EMPLEADO_HORARIO = combined.ID_EMPLEADO_HORARIO
                     AND NOT EXISTS (
                         SELECT 1 FROM ASISTENCIA a_salida_mismo_dia
                         WHERE a_salida_mismo_dia.ID_EMPLEADO = combined.ID_EMPLEADO
                         AND a_salida_mismo_dia.FECHA = combined.FECHA
                         AND a_salida_mismo_dia.TIPO = 'SALIDA'
                         AND a_salida_mismo_dia.ID_EMPLEADO_HORARIO = combined.ID_EMPLEADO_HORARIO
                     ))
                )

            -- Subconsulta para verificar si hay justificaciones aprobadas
            LEFT JOIN (
                SELECT j.empleado_id, j.fecha_falta, j.turno_id, COUNT(*) as num_justificaciones,
                       GROUP_CONCAT(j.id) as ids_justificaciones,
                       GROUP_CONCAT(j.motivo) as motivos_justificaciones,
                       CASE 
                           WHEN j.justificar_todos_turnos = 1 THEN 'Todos los turnos'
                           WHEN j.turno_id IS NOT NULL THEN CONCAT('Turno: ', COALESCE(ehp.NOMBRE_TURNO, 'Sin nombre'))
                           ELSE 'Sin turno específico'
                       END as turnos_justificados,
                       COALESCE(ehp.NOMBRE_TURNO, 'N/A') as nombres_turnos
                FROM justificaciones j
                LEFT JOIN empleado_horario_personalizado ehp ON j.turno_id = ehp.ID_EMPLEADO_HORARIO
                WHERE j.deleted_at IS NULL AND j.estado = 'aprobada'
                GROUP BY j.empleado_id, j.fecha_falta, j.turno_id
            ) j ON j.empleado_id = combined.ID_EMPLEADO AND j.fecha_falta = combined.FECHA AND j.turno_id = combined.ID_EMPLEADO_HORARIO

            WHERE s.ID_EMPRESA = ?
            GROUP BY e.ID_EMPLEADO, combined.FECHA, combined.ID_EMPLEADO_HORARIO -- Agrupar por empleado, fecha y turno
        ";

        // Agregar WHERE clause a la consulta
        if (!empty($whereClause)) {
            $sql .= " AND " . $whereClause;
        }

        // Ordenamiento y paginación
        $sql .= " ORDER BY combined.FECHA DESC, e.ID_EMPLEADO";
        $sql .= " LIMIT ?, ?";
        $params[] = ($pagina - 1) * $limite; // offset
        $params[] = $limite; // limit

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conn->errorInfo()[2]);
        }

        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . implode(' ', $stmt->errorInfo()));
        }

        $registros = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verificar si hay justificación (cualquier justificación aprobada para el empleado en la fecha)
            $tieneJustificacion = !empty($row['num_justificaciones']) && $row['num_justificaciones'] > 0;

            // Calcular estado de asistencia basado en hora de llegada y tolerancia
            $estadoAsistencia = calcularEstadoAsistencia(
                $row['hora_entrada_real'],
                $row['hora_entrada_programada'],
                $row['tolerancia_minutos'],
                $tieneJustificacion
            );

            // Calcular horas trabajadas si hay entrada y salida
            $horasTrabajadas = '00:00';
            if ($row['hora_entrada_real'] && $row['hora_salida_real']) {
                $fechaEntradaReal = $row['fecha_entrada_real'] ?: $row['fecha'];
                $fechaSalidaReal = $row['fecha_salida_real'] ?: $row['fecha'];

                $horasTrabajadasDecimal = calcularHorasTrabajadas(
                    $row['hora_entrada_real'],
                    $row['hora_salida_real'],
                    $fechaEntradaReal,
                    $fechaSalidaReal
                );
                $horasTrabajadas = sprintf('%02d:%02d', floor($horasTrabajadasDecimal), ($horasTrabajadasDecimal - floor($horasTrabajadasDecimal)) * 60);
            } elseif ($row['hora_entrada_real']) {
                // Si solo hay entrada, asumir jornada completa
                $horasTrabajadas = '08:00';
            }

            $registro = [
                'id_asistencia_entrada' => $row['id_asistencia_entrada'],
                'codigo_empleado' => $row['codigo_empleado'],
                'nombre_completo' => $row['nombre_completo'],
                'sede' => $row['sede'],
                'establecimiento' => $row['establecimiento'],
                'fecha' => date('d/m/Y', strtotime($row['fecha'])),
                'hora_entrada' => $row['hora_entrada_real'] ?: '--',
                'hora_salida' => $row['hora_salida_real'] ?: '--',
                'estado_asistencia' => $estadoAsistencia['estado'],
                'horas_trabajadas' => $horasTrabajadas,
                'horario_asignado' => $row['nombre_horario'],
                'hora_entrada_real' => $row['hora_entrada_real'] ?: '--',
                'hora_salida_real' => $row['hora_salida_real'] ?: '--',
                'estado_entrada' => $estadoAsistencia['estado_entrada'],
                'estado_salida' => $row['hora_salida_real'] ? 'A tiempo' : '--',
                'horas_formateadas' => $horasTrabajadas,
                'es_turno_nocturno' => esAsistenciaNocturna(
                    $row['hora_entrada_real'],
                    $row['hora_salida_real'],
                    $row['fecha_entrada_real'] ?: $row['fecha'],
                    $row['fecha_salida_real'] ?: $row['fecha']
                ),
                'tipo_turno' => esAsistenciaNocturna(
                    $row['hora_entrada_real'],
                    $row['hora_salida_real'],
                    $row['fecha_entrada_real'] ?: $row['fecha'],
                    $row['fecha_salida_real'] ?: $row['fecha']
                ) ? 'Noche' : determinarTipoTurno($row['hora_entrada_programada']),
                'horario_programado' => [
                    'id_horario' => $row['id_horario_asignado'],
                    'nombre_horario' => $row['nombre_horario'],
                    'hora_entrada_programada' => $row['hora_entrada_programada'],
                    'hora_salida_programada' => $row['hora_salida_programada'],
                    'tolerancia' => $row['tolerancia_minutos']
                ],
                'horario_personalizado' => $row['tiene_horario_personalizado'] === 'S' ? [
                    'id_empleado_horario' => $row['id_empleado_horario'],
                    'hora_entrada_personalizada' => $row['hora_entrada_programada'], // Ya viene del COALESCE
                    'hora_salida_personalizada' => $row['hora_salida_programada'], // Ya viene del COALESCE
                    'tolerancia_personalizada' => $row['tolerancia_minutos'], // Ya viene del COALESCE
                    'dias_semana' => null, // No disponible en la estructura actual
                    'activo' => 'S'
                ] : null,
                'observacion_entrada' => $row['observacion_entrada'],
                'tardanza_entrada' => $estadoAsistencia['minutos_tardanza'],
                'tardanza_salida' => null,
                'justificacion' => $tieneJustificacion ? [
                    'num_justificaciones' => $row['num_justificaciones'],
                    'ids' => $row['ids_justificaciones'],
                    'motivos' => $row['motivos_justificaciones'],
                    'turnos_justificados' => $row['turnos_justificados'],
                    'nombres_turnos' => $row['nombres_turnos']
                ] : null
            ];

            $registros[] = $registro;
        }

        // Filtrar registros duplicados: cuando hay entrada real, priorizar sobre justificación
        $registrosFiltrados = [];
        $registrosAgrupadosPorDia = [];

        // Agrupar por empleado y fecha
        foreach ($registros as $registro) {
            $key = $registro['codigo_empleado'] . '-' . date('Y-m-d', strtotime(str_replace('/', '-', $registro['fecha'])));
            if (!isset($registrosAgrupadosPorDia[$key])) {
                $registrosAgrupadosPorDia[$key] = [];
            }
            $registrosAgrupadosPorDia[$key][] = $registro;
        }

        // Para cada grupo de registros del mismo día
        foreach ($registrosAgrupadosPorDia as $key => $registrosDia) {
            // Si hay solo un registro, mantenerlo
            if (count($registrosDia) === 1) {
                $registrosFiltrados[] = $registrosDia[0];
                continue;
            }

            // Si hay múltiples registros, buscar el que tenga entrada real
            $registroConEntrada = null;
            $registroJustificacion = null;

            foreach ($registrosDia as $registro) {
                if ($registro['hora_entrada_real'] !== '--') {
                    $registroConEntrada = $registro;
                } elseif ($registro['estado_asistencia'] === 'Justificado' || $registro['estado_entrada'] === 'Justificado') {
                    $registroJustificacion = $registro;
                }
            }

            // Priorizar: entrada real > justificación
            if ($registroConEntrada) {
                $registrosFiltrados[] = $registroConEntrada;
            } elseif ($registroJustificacion) {
                $registrosFiltrados[] = $registroJustificacion;
            } else {
                // Si ninguno tiene entrada ni es justificación, mantener el primero
                $registrosFiltrados[] = $registrosDia[0];
            }
        }

        $registros = $registrosFiltrados;
        // Estas deben aparecer en todos los registros del empleado para esa fecha
        $registrosAgrupados = [];
        foreach ($registros as $registro) {
            $key = $registro['codigo_empleado'] . '-' . date('Y-m-d', strtotime(str_replace('/', '-', $registro['fecha'])));
            if (!isset($registrosAgrupados[$key])) {
                $registrosAgrupados[$key] = [];
            }
            $registrosAgrupados[$key][] = $registro;
        }

        // Buscar justificaciones que justifican todos los turnos
        $justificacionesTodosTurnos = [];
        $sqlJustTodos = "
            SELECT j.empleado_id, j.fecha_falta, j.motivo, j.id,
                   GROUP_CONCAT(j.id) as ids_justificaciones,
                   GROUP_CONCAT(j.motivo) as motivos_justificaciones
            FROM justificaciones j
            WHERE j.estado = 'aprobada'
              AND j.deleted_at IS NULL
              AND j.justificar_todos_turnos = 1
            GROUP BY j.empleado_id, j.fecha_falta
        ";
        $stmtJustTodos = $conn->prepare($sqlJustTodos);
        $stmtJustTodos->execute();
        while ($rowJust = $stmtJustTodos->fetch(PDO::FETCH_ASSOC)) {
            $key = $rowJust['empleado_id'] . '-' . $rowJust['fecha_falta'];
            $justificacionesTodosTurnos[$key] = $rowJust;
        }

        // Aplicar justificaciones de "todos los turnos" a todos los registros del día
        foreach ($registrosAgrupados as $key => $registrosDia) {
            if (isset($justificacionesTodosTurnos[$key])) {
                $justificacion = $justificacionesTodosTurnos[$key];
                foreach ($registrosDia as &$registro) {
                    if (!$registro['justificacion']) {
                        $registro['justificacion'] = [
                            'num_justificaciones' => 1,
                            'ids' => $justificacion['ids_justificaciones'],
                            'motivos' => $justificacion['motivos_justificaciones'],
                            'turnos_justificados' => 'Todos los turnos',
                            'nombres_turnos' => 'N/A'
                        ];
                        // Cambiar estado a justificado si no hay asistencia
                        if ($registro['estado_asistencia'] !== 'Justificado') {
                            $registro['estado_asistencia'] = 'Justificado';
                            $registro['estado_entrada'] = 'Justificado';
                        }
                    }
                }
            }
        }

        // Aplicar filtro de estado de entrada después de calcular estados
        if (!empty($filtros['estado_entrada'])) {
            $registros = array_filter($registros, function($registro) use ($filtros) {
                return $registro['estado_entrada'] === $filtros['estado_entrada'];
            });
            // Reindexar array
            $registros = array_values($registros);
        }

        // Obtener total de registros para paginación (después del filtro)
        $totalRegistrosFiltrados = count($registros);
        $sqlCount = "
            SELECT COUNT(*) as total
            FROM (
                SELECT DISTINCT a.ID_EMPLEADO, a.FECHA, a.ID_HORARIO
                FROM ASISTENCIA a
                WHERE a.TIPO = 'ENTRADA'
                
                UNION
                
                SELECT DISTINCT j.empleado_id as ID_EMPLEADO, j.fecha_falta as FECHA, NULL as ID_HORARIO
                FROM justificaciones j
                WHERE j.estado = 'aprobada' AND j.deleted_at IS NULL
            ) combined
            
            INNER JOIN EMPLEADO e ON combined.ID_EMPLEADO = e.ID_EMPLEADO
            INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN HORARIO h ON h.ID_HORARIO = combined.ID_HORARIO
            LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                AND combined.FECHA BETWEEN eh.FECHA_DESDE AND COALESCE(eh.FECHA_HASTA, CURDATE())
                AND eh.ACTIVO = 'S'
            WHERE s.ID_EMPRESA = ?";
        
        if (!empty($whereClause)) {
            $sqlCount .= " AND " . $whereClause;
        }

        $stmtCount = $conn->prepare($sqlCount);
        if (!$stmtCount) {
            throw new Exception("Error al preparar consulta de conteo: " . $conn->errorInfo()[2]);
        }

        // Usar los mismos parámetros que la consulta principal, sin limit y offset
        $countParams = array_slice($params, 0, -2); // Remover limit y offset
        if (!empty($countParams)) {
            foreach ($countParams as $key => $value) {
                $stmtCount->bindValue($key + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
        }

        if (!$stmtCount->execute()) {
            throw new Exception("Error al ejecutar consulta de conteo: " . $stmtCount->errorInfo()[2]);
        }

        // Aplicar paginación después del filtro
        $totalRegistros = $totalRegistrosFiltrados;
        $registrosPaginados = array_slice($registros, ($pagina - 1) * $limite, $limite);

        // Calcular estadísticas con todos los registros filtrados (no paginados)
        $estadisticas = calcularEstadisticas($registros);

        $stmt->closeCursor();
        $stmtCount->closeCursor();
        // No cerrar $conn ya que es una conexión global

        // Si se está filtrando por un empleado específico, incluir información completa del empleado
        $employeeInfo = null;
        if (!empty($filtros['codigo'])) {
            error_log("Llamando getEmployeeCompleteInfo con código: " . $filtros['codigo']);
            $employeeInfo = getEmployeeCompleteInfo($filtros['codigo']);
            error_log("Resultado de getEmployeeCompleteInfo: " . ($employeeInfo ? "encontrado" : "null"));
        } else {
            error_log("No hay filtro de código, filtros: " . json_encode($filtros));
        }

        return [
            'success' => true,
            'data' => $registrosPaginados,
            'pagination' => [
                'currentPage' => $pagina,
                'per_page' => $limite,
                'totalRecords' => $totalRegistros,
                'totalPages' => ceil($totalRegistros / $limite),
                'from' => (($pagina - 1) * $limite) + 1,
                'to' => min((($pagina - 1) * $limite) + $limite, $totalRegistros),
                'hasNext' => $pagina < ceil($totalRegistros / $limite),
                'hasPrev' => $pagina > 1
            ],
            'statistics' => $estadisticas,
            'employee' => $employeeInfo
        ];

    } catch (Exception $e) {
        error_log("Error en getReportesData: " . $e->getMessage());
        error_log("Archivo: " . __FILE__ . ", Línea: " . __LINE__);
        error_log("Trace: " . $e->getTraceAsString());

        // Asegurar que siempre se devuelva JSON
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        return [
            'success' => false,
            'message' => 'Error al obtener datos de reportes: ' . $e->getMessage(),
            'debug' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'empresa_id' => $empresaId ?? 'no definido'
            ]
        ];
    }
}

/**
 * Calcula el estado de asistencia basado en la hora de llegada y tolerancia
 */
function calcularEstadoAsistencia($horaReal, $horaProgramada, $toleranciaMinutos, $tieneJustificacion = false) {
    // Si tiene justificación aprobada, el estado es Justificado
    if ($tieneJustificacion) {
        return [
            'estado' => 'Justificado',
            'estado_entrada' => 'Justificado',
            'minutos_tardanza' => 0
        ];
    }

    if (!$horaReal || !$horaProgramada) {
        return [
            'estado' => 'Sin registro',
            'estado_entrada' => '--',
            'minutos_tardanza' => 0
        ];
    }

    $horaRealTimestamp = strtotime($horaReal);
    $horaProgramadaTimestamp = strtotime($horaProgramada);
    $toleranciaSegundos = $toleranciaMinutos * 60;

    $diferencia = $horaRealTimestamp - $horaProgramadaTimestamp;

    if ($diferencia <= $toleranciaSegundos) {
        if ($diferencia <= 0) {
            return [
                'estado' => 'Temprano',
                'estado_entrada' => 'Temprano',
                'minutos_tardanza' => 0
            ];
        } else {
            return [
                'estado' => 'A tiempo',
                'estado_entrada' => 'A tiempo',
                'minutos_tardanza' => 0
            ];
        }
    } else {
        $minutosDiferencia = abs($diferencia) / 60;
        return [
            'estado' => 'Tarde',
            'estado_entrada' => 'Tarde',
            'minutos_tardanza' => round($minutosDiferencia)
        ];
    }
}

/**
 * Determina el tipo de turno basado en la hora de entrada
 */
function determinarTipoTurno($horaEntrada) {
    if (!$horaEntrada) {
        return 'Diurno';
    }

    $horaInt = (int)str_replace(':', '', $horaEntrada);

    if ($horaInt >= 500 && $horaInt < 1200) {
        return 'Mañana';
    } elseif ($horaInt >= 1200 && $horaInt < 1800) {
        return 'Tarde';
    } elseif ($horaInt >= 1800 || $horaInt < 500) {
        return 'Noche';
    }

    return 'Diurno';
}

/**
 * Calcula estadísticas generales de los reportes
 */
function calcularEstadisticas($registros) {
    $totalRegistros = count($registros);
    $empleadosUnicos = count(array_unique(array_column($registros, 'codigo_empleado')));
    $turnosNocturnos = count(array_filter($registros, function($r) { return $r['es_turno_nocturno']; }));

    $horasTotales = array_sum(array_map(function($r) {
        $horas = explode(':', $r['horas_trabajadas']);
        return $horas[0] + ($horas[1] / 60);
    }, $registros));
    $horasPromedio = $empleadosUnicos > 0 ? round($horasTotales / $empleadosUnicos, 2) : 0;

    // Contar estados de llegada
    $temprano = count(array_filter($registros, function($r) {
        return $r['estado_entrada'] === 'Temprano';
    }));
    $aTiempo = count(array_filter($registros, function($r) {
        return $r['estado_entrada'] === 'A tiempo';
    }));
    $tarde = count(array_filter($registros, function($r) {
        return $r['estado_entrada'] === 'Tarde';
    }));
    $justificado = count(array_filter($registros, function($r) {
        return $r['estado_entrada'] === 'Justificado';
    }));

    // Estadísticas de estados de llegada
    return [
        'total_empleados' => $empleadosUnicos,
        'total_registros' => $totalRegistros,
        'temprano' => $temprano,
        'a_tiempo' => $aTiempo,
        'tarde' => $tarde,
        'justificado' => $justificado,
        'horas_promedio' => number_format($horasPromedio, 2),
        'turnos_nocturnos' => $turnosNocturnos,
        'horas_totales' => round($horasTotales, 2)
    ];
}

/**
 * Maneja las solicitudes GET
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'estado_entrada' => $_GET['estado_entrada'] ?? null,
        'tipo_turno' => $_GET['tipo_turno'] ?? null,
        'fecha_desde' => $_GET['fecha_desde'] ?? null,
        'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        'tipo_reporte' => $_GET['tipo_reporte'] ?? null
    ];

    $pagina = max(1, intval($_GET['page'] ?? 1));
    $limite = max(10, min(100, intval($_GET['limit'] ?? 10)));

    $resultado = getReportesData($filtros, $pagina, $limite);
    echo json_encode($resultado);
    exit;
}

/**
 * Maneja las solicitudes OPTIONS (CORS)
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Método no permitido
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Método no permitido'
]);
?>