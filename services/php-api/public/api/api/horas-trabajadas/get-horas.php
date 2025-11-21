<?php

/**
 * API REFACTORIZADO PARA CÁLCULO DE HORAS TRABAJADAS
 * Nueva lógica con categorías específicas de recargos y extras
 * 
 * Categorías implementadas:
 * - Recargo nocturno (9PM-6AM)
 * - Recargo dominical/festivo
 * - Recargo nocturno dominical/festivo
 * - Extra diurna (6AM-9PM, fuera del horario)
 * - Extra nocturna (9PM-6AM, fuera del horario)
 * - Extra diurna dominical/festiva
 * - Extra nocturna dominical/festiva
 */

// Control de debug output - deshabilitar cuando se incluye desde otro script
$debug_enabled = (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']));

// Deshabilitar visualización de errores HTML para evitar que se devuelva HTML en lugar de JSON
if ($debug_enabled) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
}

// Headers y configuración inicial
if ($debug_enabled) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

// Variable global para acumular mensajes de debug
$debug_messages = [];

// Función helper para logging
function debug_log($message) {
    global $debug_messages;
    $debug_messages[] = $message;
    error_log($message); // También mantener en error log por si acaso
}

// Extraer parámetros
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_POST)) {
        // POST request with multiple employees (or included script with POST data)
        $empleados = isset($_POST['empleados']) ? $_POST['empleados'] : [];
        $fecha_inicio = isset($_POST['fechaDesde']) ? $_POST['fechaDesde'] : null;
        $fecha_fin = isset($_POST['fechaHasta']) ? $_POST['fechaHasta'] : null;
        $sede_id = isset($_POST['sede_id']) ? $_POST['sede_id'] : null;
        $establecimiento_id = isset($_POST['establecimiento_id']) ? $_POST['establecimiento_id'] : null;
        $multipleEmployees = true;
        
        // Si no hay empleados especificados en POST, cargar todos los activos filtrados por sede/establecimiento
        if (empty($empleados)) {
            try {
                $query = "SELECT DISTINCT e.ID_EMPLEADO FROM empleado e";
                $params = [];
                $conditions = ["e.ACTIVO = 'S'"];
                
                // Agregar filtro por sede si se especifica
                if ($sede_id) {
                    $query .= " JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO";
                    $query .= " JOIN sede s ON est.ID_SEDE = s.ID_SEDE";
                    $conditions[] = "s.ID_SEDE = ?";
                    $params[] = $sede_id;
                }
                
                // Agregar filtro por establecimiento si se especifica
                if ($establecimiento_id) {
                    if (!$sede_id) {
                        $query .= " JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO";
                    }
                    $conditions[] = "est.ID_ESTABLECIMIENTO = ?";
                    $params[] = $establecimiento_id;
                }
                
                $query .= " WHERE " . implode(" AND ", $conditions) . " ORDER BY e.ID_EMPLEADO LIMIT 50";
                
                $stmt = $conn->prepare($query);
                $stmt->execute($params);
                $empleados = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $empleados[] = (int) $row['ID_EMPLEADO'];
                }
                error_log("INFO: Cargados " . count($empleados) . " empleados activos con filtros (sede: $sede_id, establecimiento: $establecimiento_id)");
            } catch (Exception $e) {
                error_log("ERROR: No se pudieron cargar empleados activos con filtros: " . $e->getMessage());
                // Continuar con array vacío, se manejará en validación
            }
        }
        
    } else {
        // GET request with single employee (backward compatibility)
        $id_empleado = isset($_GET['id_empleado']) ? (int) $_GET['id_empleado'] : null;
        $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
        $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
        $empleados = $id_empleado ? [$id_empleado] : [];
        $multipleEmployees = false;
    }

    // Validar parámetros
    if (empty($empleados) || (!$multipleEmployees && $empleados[0] === null)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de empleado requerido',
            'debug' => [
                'empleados' => $empleados,
                'multipleEmployees' => $multipleEmployees,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'sede_id' => $sede_id ?? null,
                'establecimiento_id' => $establecimiento_id ?? null,
                'post_data' => $_POST,
                'get_data' => $_GET
            ]
        ]);
        exit;
    }
    
    if (!$fecha_inicio || !$fecha_fin) {
        error_log("ERROR: Fechas faltantes. fecha_inicio: '$fecha_inicio', fecha_fin: '$fecha_fin'");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Fechas de inicio y fin son requeridas',
            'debug' => [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'post_data' => $_POST,
                'get_data' => $_GET
            ]
        ]);
        exit;
    }

    // ===============================
    // FUNCIONES AUXILIARES
    // ===============================
    
    /**
     * Determina si una fecha es domingo
     */
    function esDomingo($fecha) {
        return date('w', strtotime($fecha)) == 0;
    }
    
    /**
     * Determina si una fecha es festiva
     */
    function esFestivo($fecha, $pdo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM festivos 
            WHERE FECHA = ? 
            AND ACTIVO = 'S'
        ");
        $stmt->execute([$fecha]);
        return $stmt->fetch()['count'] > 0;
    }
    
    /**
     * Determina si una fecha es día cívico
     */
    function esDiaCivico($fecha, $pdo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM dias_civicos 
            WHERE FECHA = ? 
            AND ESTADO = 'A'
        ");
        $stmt->execute([$fecha]);
        return $stmt->fetch()['count'] > 0;
    }
    
    /**
     * Determina si una fecha es especial (domingo o festivo/cívico)
     */
    function esFechaEspecial($fecha, $pdo) {
        return esDomingo($fecha) || esFestivo($fecha, $pdo) || esDiaCivico($fecha, $pdo);
    }
    
    /**
     * Determina si una hora está en rango nocturno (9PM-6AM)
     */
    function esHoraNocturna($hora) {
        $horaNum = (int)date('H', strtotime($hora));
        return $horaNum >= 21 || $horaNum < 6;
    }

    /**
     * Determina si un turno completo es nocturno usando lógica mejorada
     * Basada en la implementación mejorada de asistencias
     */
    function esTurnoNocturnoMejorado($esTurnoNocturnoDB, $horaEntrada, $horaSalida) {
        // 1. Si la base de datos marca explícitamente como nocturno
        if ($esTurnoNocturnoDB === 'S') {
            return true;
        }

        // 2. Si la hora de salida es menor que la de entrada (turno cruza medianoche)
        if ($horaSalida && $horaEntrada && $horaSalida < $horaEntrada) {
            return true;
        }

        return false;
    }

    /**
     * Normaliza una hora H:i(:s) al formato H:i o retorna null si no es válida
     */
    function normalizarHora($hora) {
        if ($hora === null) {
            return null;
        }

        $horaLimpia = trim((string)$hora);
        if ($horaLimpia === '') {
            return null;
        }

        $formatos = ['H:i:s', 'H:i'];
        foreach ($formatos as $formato) {
            $dt = DateTime::createFromFormat($formato, $horaLimpia);
            if ($dt !== false) {
                return $dt->format('H:i:s');
            }
        }

        return null;
    }

    /**
     * Construye DateTimeImmutable seguro bajo la zona horaria del sistema
     */
    function crearDateTimeSeguro($fecha, $hora, DateTimeZone $tz) {
        $horaNormalizada = normalizarHora($hora);
        $horaFinal = $horaNormalizada ?? trim((string)$hora);

        if ($horaFinal === '') {
            $horaFinal = '00:00:00';
        }

        try {
            return new DateTimeImmutable("$fecha $horaFinal", $tz);
        } catch (Exception $e) {
            return new DateTimeImmutable("$fecha 00:00:00", $tz);
        }
    }

    /**
     * Determina si un instante pertenece al rango nocturno (21:00 - 05:59)
     */
    function esNocturnoDateTime(DateTimeInterface $dateTime) {
        $horaCompleta = $dateTime->format('H:i:s');
        if ($horaCompleta >= '21:00:00') {
            return true;
        }

        if ($horaCompleta <= '06:00:00') {
            return true;
        }

        return false;
    }

    /**
     * Cachea la información de día especial para reducir consultas repetidas
     */
    function obtenerEstadoDiaConCache($fecha, $pdo) {
        static $cache = [];

        if (!isset($cache[$fecha])) {
            $cache[$fecha] = [
                'es_domingo' => esDomingo($fecha),
                'es_festivo' => esFestivo($fecha, $pdo),
                'es_dia_civico' => esDiaCivico($fecha, $pdo)
            ];
            $cache[$fecha]['es_especial'] = $cache[$fecha]['es_domingo'] || $cache[$fecha]['es_festivo'] || $cache[$fecha]['es_dia_civico'];
        }

        return $cache[$fecha];
    }

    /**
     * Función simplificada para dividir segmentos - mantiene compatibilidad pero usa lógica mejorada
     */
    function dividirSegmentoPorPeriodo($horaInicio, $horaFin) {
        $segmentos = [];

        // Usar la lógica mejorada para determinar si el segmento completo es nocturno
        $esNocturno = esHoraNocturna($horaInicio) || esHoraNocturna($horaFin);

        // Para simplificar, devolver el segmento completo con la clasificación
        $segmentos[] = [
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'horas' => calcularDiferenciaHoras($horaInicio, $horaFin),
            'es_nocturno' => $esNocturno
        ];

        return $segmentos;
    }


    
    /**
     * Calcula horas trabajadas en turno nocturno que cruza medianoche
     */
    function calcularHorasNocturnas($horaEntrada, $horaSalida, $horaCorte = '06:00:00') {
        // Verificar si realmente es un turno nocturno
        if (!esTurnoNocturnoMejorado(null, $horaEntrada, $horaSalida)) {
            // No es turno nocturno, usar cálculo normal
            return calcularDiferenciaHoras($horaEntrada, $horaSalida);
        }
        
        // Convertir a timestamps para cálculos
        $entradaTime = strtotime($horaEntrada);
        $salidaTime = strtotime($horaSalida);
        $medianoche = strtotime('23:59:59');
        $inicioDia = strtotime('00:00:00');
        $corteTime = strtotime($horaCorte);
        
        $horasTotales = 0;
        
        // 1. Horas desde entrada hasta medianoche (23:59:59)
        $horasAntesMedianoche = ($medianoche - $entradaTime + 1) / 3600; // +1 segundo para incluir el cambio a 00:00
        $horasTotales += $horasAntesMedianoche;
        
        // 2. Horas desde medianoche hasta salida (solo si salida es antes del corte)
        if ($salidaTime <= $corteTime) {
            $horasDespuesMedianoche = ($salidaTime - $inicioDia) / 3600;
            $horasTotales += $horasDespuesMedianoche;
        } else {
            // Si la salida es después del corte, solo contar hasta el corte
            $horasDespuesMedianoche = ($corteTime - $inicioDia) / 3600;
            $horasTotales += $horasDespuesMedianoche;
        }
        
        return round($horasTotales, 2);
    }
    
    /**
     * Calcula horas regulares y extras para turnos nocturnos que cruzan medianoche
     */
    function calcularHorasRegularesExtrasNocturno($horaEntrada, $horaSalida, $horaEntradaProg, $horaSalidaProg, $horasTotales) {
        $horasRegulares = 0;
        $horasExtra = 0;
        
        // Si el horario programado también es nocturno (cruza medianoche)
        if (esTurnoNocturnoMejorado(null, $horaEntradaProg, $horaSalidaProg)) {
            // Ambos horarios cruzan medianoche - comparar segmentos
            
            // Calcular horas programadas total
            $horasProgramadas = calcularHorasNocturnas($horaEntradaProg, $horaSalidaProg, '06:00:00');
            
            // Si trabajó exactamente las horas programadas o menos, todas son regulares
            if ($horasTotales <= $horasProgramadas) {
                $horasRegulares = $horasTotales;
                $horasExtra = 0;
            } else {
                // Trabajó más de las programadas
                $horasRegulares = $horasProgramadas;
                $horasExtra = $horasTotales - $horasProgramadas;
            }
        } else {
            // El horario real cruza medianoche pero el programado no
            // Todo el tiempo trabajado es extra
            $horasExtra = $horasTotales;
        }
        
        return [
            'regulares' => round($horasRegulares, 2),
            'extras' => round($horasExtra, 2)
        ];
    }
    
    /**
     * Calcula diferencia en horas entre dos tiempos
     */
    function calcularDiferenciaHoras($horaInicio, $horaFin) {
        $inicio = new DateTime($horaInicio);
        $fin = new DateTime($horaFin);
        
        // Si la hora fin es menor que inicio, significa que cruza medianoche
        if ($fin < $inicio) {
            $fin->add(new DateInterval('P1D'));
        }
        
        $diferencia = $inicio->diff($fin);
        return $diferencia->h + ($diferencia->i / 60);
    }
    
    /**
     * Calcula horas regulares y extras dividiendo el turno registrado según el horario programado
     * Devuelve tanto las cantidades como los segmentos de tiempo
     */
    function calcularHorasRegularesExtras($horaEntrada, $horaSalida, $horaEntradaProg, $horaSalidaProg, $tolerancia = 0) {
        $horasRegulares = 0;
        $horasExtra = 0;
        $segmentos = [];

        // IMPORTANTE: Para determinar horas regulares vs extras, usamos el horario programado BASE
        // La tolerancia solo afecta si se marca como tardanza, no el límite de horas regulares
        $entradaProgBase = strtotime($horaEntradaProg);
        $salidaProgBase = strtotime($horaSalidaProg);

        // Convertir horas trabajadas a timestamps
        $entradaReg = strtotime($horaEntrada);
        $salidaReg = strtotime($horaSalida);

        // Caso 1: Todo el turno está dentro del horario programado BASE
        if ($entradaReg >= $entradaProgBase && $salidaReg <= $salidaProgBase) {
            $horasRegulares = calcularDiferenciaHoras($horaEntrada, $horaSalida);
            $segmentos[] = [
                'hora_inicio' => $horaEntrada,
                'hora_fin' => $horaSalida,
                'horas' => $horasRegulares,
                'tipo' => 'regular'
            ];
        }
        // Caso 2: Todo el turno está fuera del horario programado BASE
        elseif ($salidaReg <= $entradaProgBase || $entradaReg >= $salidaProgBase) {
            $horasExtra = calcularDiferenciaHoras($horaEntrada, $horaSalida);
            $segmentos[] = [
                'hora_inicio' => $horaEntrada,
                'hora_fin' => $horaSalida,
                'horas' => $horasExtra,
                'tipo' => 'extra'
            ];
        }
        // Caso 3: El turno se superpone parcialmente con el horario programado BASE
        else {
            // Calcular la intersección entre el turno registrado y el horario programado BASE
            $inicioInterseccion = max($entradaReg, $entradaProgBase);
            $finInterseccion = min($salidaReg, $salidaProgBase);
            if ($inicioInterseccion < $finInterseccion) {
                // Hay horas regulares (dentro del horario programado BASE)
                $horaInicioReg = date('H:i', $inicioInterseccion);
                $horaFinReg = date('H:i', $finInterseccion);
                $horasRegulares = calcularDiferenciaHoras($horaInicioReg, $horaFinReg);
                $segmentos[] = [
                    'hora_inicio' => $horaInicioReg,
                    'hora_fin' => $horaFinReg,
                    'horas' => $horasRegulares,
                    'tipo' => 'regular'
                ];
            }

            // Calcular horas extras (antes del horario programado BASE)
            if ($entradaReg < $entradaProgBase) {
                $horaInicioExtraAntes = $horaEntrada;
                $horaFinExtraAntes = date('H:i', min($salidaReg, $entradaProgBase));
                $horasExtraAntes = calcularDiferenciaHoras($horaInicioExtraAntes, $horaFinExtraAntes);
                if ($horasExtraAntes > 0) {
                    $horasExtra += $horasExtraAntes;
                    $segmentos[] = [
                        'hora_inicio' => $horaInicioExtraAntes,
                        'hora_fin' => $horaFinExtraAntes,
                        'horas' => $horasExtraAntes,
                        'tipo' => 'extra'
                    ];
                }
            }

            // Calcular horas extras (después del horario programado BASE)
            if ($salidaReg > $salidaProgBase) {
                $horaInicioExtraDespues = date('H:i', max($entradaReg, $salidaProgBase));
                $horaFinExtraDespues = $horaSalida;
                $horasExtraDespues = calcularDiferenciaHoras($horaInicioExtraDespues, $horaFinExtraDespues);
                if ($horasExtraDespues > 0) {
                    $horasExtra += $horasExtraDespues;
                    $segmentos[] = [
                        'hora_inicio' => $horaInicioExtraDespues,
                        'hora_fin' => $horasExtraDespues,
                        'horas' => $horasExtraDespues,
                        'tipo' => 'extra'
                    ];
                }
            }
        }

        return [
            'regulares' => round($horasRegulares, 2),
            'extras' => round($horasExtra, 2),
            'segmentos' => $segmentos
        ];
    }
    
    /**
     * Obtiene todos los horarios/turnos del empleado para una fecha específica
     */
    function obtenerTodosLosHorarios($idEmpleado, $fecha, $diaSemana, $pdo) {
        // Primero intentar obtener todos los horarios específicos para el día
        $stmt = $pdo->prepare("
            SELECT
                ehp.HORA_ENTRADA,
                ehp.HORA_SALIDA,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.ES_TURNO_NOCTURNO,
                ehp.ID_DIA,
                ehp.ORDEN_TURNO,
                ehp.ID_EMPLEADO_HORARIO,
                ehp.ES_TEMPORAL
            FROM empleado_horario_personalizado ehp
            WHERE ehp.ID_EMPLEADO = ?
            AND ehp.ID_DIA = ?
            AND ehp.ACTIVO = 'S'
            AND (ehp.FECHA_DESDE IS NULL OR ehp.FECHA_DESDE <= ?)
            AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
            ORDER BY ehp.ORDEN_TURNO ASC
        ");

        $stmt->execute([$idEmpleado, $diaSemana, $fecha, $fecha]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si no encuentra horarios para el día específico, buscar cualquier horario activo del empleado
        if (empty($horarios)) {
            error_log("INFO: No se encontraron horarios para empleado $idEmpleado en día $diaSemana, buscando horarios alternativos");

            $stmt = $pdo->prepare("
                SELECT
                    ehp.HORA_ENTRADA,
                    ehp.HORA_SALIDA,
                    ehp.TOLERANCIA,
                    ehp.NOMBRE_TURNO,
                    ehp.ES_TURNO_NOCTURNO,
                    ehp.ID_DIA,
                    ehp.ORDEN_TURNO,
                    ehp.ID_EMPLEADO_HORARIO,
                    ehp.ES_TEMPORAL
                FROM empleado_horario_personalizado ehp
                WHERE ehp.ID_EMPLEADO = ?
                AND ehp.ACTIVO = 'S'
                AND (ehp.FECHA_DESDE IS NULL OR ehp.FECHA_DESDE <= ?)
                AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
                ORDER BY ehp.FECHA_DESDE DESC, ehp.ORDEN_TURNO ASC
                LIMIT 3
            ");
            
            $stmt->execute([$idEmpleado, $fecha, $fecha]);
            $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($horarios)) {
                error_log("INFO: Horarios alternativos encontrados para empleado $idEmpleado: " . count($horarios) . " turnos");
                // Marcar que son horarios alternativos
                foreach ($horarios as &$horario) {
                    $horario['ES_HORARIO_ALTERNATIVO'] = true;
                    $horario['NOMBRE_TURNO'] = $horario['NOMBRE_TURNO'] . ' (Horario ref.)';
                }
                unset($horario);
            }
        }
        
        return $horarios;
    }

    /**
     * Obtiene la justificación de un empleado para una fecha específica
     */
    function obtenerJustificacion($idEmpleado, $fecha, $pdo) {
        $stmt = $pdo->prepare("
            SELECT
                id,
                motivo,
                detalle_adicional,
                tipo_falta,
                fecha_justificacion,
                horas_programadas,
                turno_id,
                justificar_todos_turnos,
                turnos_ids
            FROM justificaciones
            WHERE empleado_id = ?
            AND fecha_falta = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$idEmpleado, $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las justificaciones de un empleado dentro de un rango de fechas
     * Retorna la más reciente por cada día justificado
     */
    function obtenerJustificacionesEnRango($idEmpleado, $fechaInicio, $fechaFin, $pdo) {
        $stmt = $pdo->prepare("
            SELECT
                id,
                empleado_id,
                motivo,
                detalle_adicional,
                tipo_falta,
                fecha_justificacion,
                horas_programadas,
                turno_id,
                justificar_todos_turnos,
                turnos_ids,
                fecha_falta
            FROM justificaciones
            WHERE empleado_id = ?
            AND fecha_falta BETWEEN ? AND ?
            ORDER BY fecha_falta ASC, id DESC
        ");

        $stmt->execute([$idEmpleado, $fechaInicio, $fechaFin]);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$justificaciones) {
            return [];
        }

        $agrupadas = [];
        foreach ($justificaciones as $justificacion) {
            $fecha = $justificacion['fecha_falta'];
            if (!isset($agrupadas[$fecha])) {
                $agrupadas[$fecha] = $justificacion;
            }
        }

        return array_values($agrupadas);
    }

    /**
     * Obtiene el horario personalizado del empleado para una fecha específica
     * Si no encuentra para el día específico, busca el horario más reciente
     */
    function obtenerHorariosPersonalizados($idEmpleado, $fecha, $diaSemana, $pdo) {
        // Buscar TODOS los horarios activos del empleado, ordenados por prioridad
        $sql = "
            SELECT
                ehp.ID_EMPLEADO_HORARIO,
                ehp.HORA_ENTRADA,
                ehp.HORA_SALIDA,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.ES_TURNO_NOCTURNO,
                ehp.ID_DIA,
                ehp.ORDEN_TURNO,
                ehp.HORA_CORTE_NOCTURNO,
                ehp.FECHA_DESDE,
                ehp.FECHA_HASTA,
                ehp.ES_TEMPORAL,
                ehp.ACTIVO,
                CASE
                    WHEN ehp.ID_DIA = ? THEN 1
                    WHEN ehp.ID_DIA IS NULL THEN 2
                    ELSE 3
                END as prioridad_dia,
                CASE
                    WHEN (ehp.FECHA_DESDE IS NULL OR ehp.FECHA_DESDE <= ?) AND
                         (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?) THEN 1
                    ELSE 2
                END as prioridad_fecha,
                CASE WHEN ehp.ACTIVO = 'S' THEN 0 ELSE 1 END as prioridad_activo
            FROM empleado_horario_personalizado ehp
            WHERE ehp.ID_EMPLEADO = ?
            AND (
                ehp.ACTIVO = 'S'
                OR (
                    (ehp.FECHA_DESDE IS NULL OR ehp.FECHA_DESDE <= ?)
                    AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
                )
            )
            ORDER BY prioridad_dia ASC, prioridad_fecha ASC, prioridad_activo ASC, ehp.FECHA_DESDE DESC, ehp.ORDEN_TURNO ASC
        ";
        
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$diaSemana, $fecha, $fecha, $idEmpleado, $fecha, $fecha]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $horarios;
    }

    // Mantener la función original para compatibilidad
    function obtenerHorarioPersonalizado($idEmpleado, $fecha, $diaSemana, $pdo) {
        $horarios = obtenerHorariosPersonalizados($idEmpleado, $fecha, $diaSemana, $pdo);
        return !empty($horarios) ? $horarios[0] : null;
    }

    /**
     * Construye los intervalos de horario válidos para un día específico.
     * Permite manejar turnos partidos en la evaluación jerárquica.
     */
    function construirIntervalosHorarioDia(array $horarios, $fechaReferencia, $diaSemanaReferencia) {
        if (empty($horarios)) {
            return [];
        }

        $tz = new DateTimeZone('America/Bogota');
        $intervalos = [];
        $deduplicador = [];

        foreach ($horarios as $horario) {
            $idDia = isset($horario['ID_DIA']) ? $horario['ID_DIA'] : null;
            if ($idDia !== null && $idDia !== '' && (int)$idDia !== (int)$diaSemanaReferencia) {
                continue;
            }

            $fechaDesde = $horario['FECHA_DESDE'] ?? null;
            if (!empty($fechaDesde) && $fechaReferencia < $fechaDesde) {
                continue;
            }

            $fechaHasta = $horario['FECHA_HASTA'] ?? null;
            if (!empty($fechaHasta) && $fechaReferencia > $fechaHasta) {
                continue;
            }

            $horaInicio = normalizarHora($horario['HORA_ENTRADA'] ?? null);
            $horaFin = normalizarHora($horario['HORA_SALIDA'] ?? null);

            if ($horaInicio === null || $horaFin === null) {
                continue;
            }

            $inicioDT = crearDateTimeSeguro($fechaReferencia, $horaInicio, $tz);
            $finDT = crearDateTimeSeguro($fechaReferencia, $horaFin, $tz);

            if ($finDT <= $inicioDT) {
                $finDT = $finDT->modify('+1 day');
            }

            $clave = $inicioDT->format(DateTimeInterface::ATOM) . '|' . $finDT->format(DateTimeInterface::ATOM);
            if (isset($deduplicador[$clave])) {
                continue;
            }

            $deduplicador[$clave] = true;
            $intervalos[] = [
                'inicio' => $inicioDT,
                'fin' => $finDT
            ];
        }

        usort($intervalos, function ($a, $b) {
            return $a['inicio'] <=> $b['inicio'];
        });

        return $intervalos;
    }

    /**
     * Obtiene datos de asistencia con lógica de asociación entrada-salida
     * Adaptado de la lógica de asistencia pero para rango de fechas específico
     */
    /**
     * Wrapper para compatibilidad - procesa un solo empleado
     */
    function getAsistenciaData($idEmpleado, $fechaInicio, $fechaFin, $pdo) {
        return getAsistenciaDataOptimizada([$idEmpleado], $fechaInicio, $fechaFin, $pdo);
    }

    function getAsistenciaDataOptimizada($empleadosIds, $fechaInicio, $fechaFin, $pdo) {
        // Una sola consulta optimizada para obtener todas las entradas y salidas de todos los empleados
        $sql = "
            SELECT
                -- Información de entrada
                entrada.ID_ASISTENCIA as id_entrada,
                entrada.FECHA as fecha_entrada,
                entrada.HORA as hora_entrada,
                entrada.TARDANZA as tardanza_entrada,
                entrada.OBSERVACION as observacion_entrada,
                entrada.FOTO as foto_entrada,
                entrada.REGISTRO_MANUAL as registro_manual_entrada,
                entrada.ID_HORARIO as id_horario_tradicional,
                entrada.ID_EMPLEADO_HORARIO,
                entrada.ID_EMPLEADO,

                -- Información del horario tradicional
                h.NOMBRE as HORARIO_NOMBRE,
                h.HORA_ENTRADA as HORA_ENTRADA,
                h.HORA_SALIDA as HORA_SALIDA,
                h.TOLERANCIA as TOLERANCIA,

                -- Información del horario personalizado
                ehp.NOMBRE_TURNO as NOMBRE_TURNO,
                ehp.HORA_ENTRADA as HORA_ENTRADA_PERSONALIZADO,
                ehp.HORA_SALIDA as HORA_SALIDA_PERSONALIZADO,
                ehp.ACTIVO as HORARIO_PERSONALIZADO_ACTIVO,
                ehp.ES_TURNO_NOCTURNO as ES_TURNO_NOCTURNO,
                ehp.HORA_CORTE_NOCTURNO as HORA_CORTE_NOCTURNO,
                ehp.ES_TEMPORAL as ES_TEMPORAL,
                ds.NOMBRE as DIA_NOMBRE,
                ehp.ORDEN_TURNO as ORDEN_TURNO,
                ehp.FECHA_DESDE as FECHA_DESDE,
                ehp.FECHA_HASTA as FECHA_HASTA,
                ehp.ID_DIA as HORARIO_DIA,
                DAYOFWEEK(entrada.FECHA) as DIA_SEMANA_NUM,

                -- Información del empleado
                e.NOMBRE,
                e.APELLIDO,

                -- Información de salida (usando LEFT JOIN para encontrar la salida correspondiente)
                salida.ID_ASISTENCIA as id_salida,
                salida.FECHA as fecha_salida,
                salida.HORA as hora_salida,
                salida.TARDANZA as tardanza_salida,
                salida.OBSERVACION as observacion_salida,
                salida.FOTO as foto_salida,
                salida.REGISTRO_MANUAL as registro_manual_salida

            FROM asistencia entrada
            JOIN empleado e ON entrada.ID_EMPLEADO = e.ID_EMPLEADO
            LEFT JOIN horario h ON entrada.ID_HORARIO = h.ID_HORARIO
            LEFT JOIN empleado_horario_personalizado ehp ON entrada.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
            LEFT JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA

            -- LEFT JOIN para encontrar la salida correspondiente
            LEFT JOIN asistencia salida ON salida.ID_ASISTENCIA = (
                SELECT s.ID_ASISTENCIA
                FROM asistencia s
                WHERE s.ID_EMPLEADO = entrada.ID_EMPLEADO
                AND s.TIPO = 'SALIDA'
                AND CONCAT(s.FECHA, ' ', s.HORA) > CONCAT(entrada.FECHA, ' ', entrada.HORA)
                AND CONCAT(s.FECHA, ' ', s.HORA) <= DATE_ADD(CONCAT(entrada.FECHA, ' ', entrada.HORA), INTERVAL 24 HOUR)
                AND (
                    (entrada.ID_EMPLEADO_HORARIO IS NOT NULL AND s.ID_EMPLEADO_HORARIO = entrada.ID_EMPLEADO_HORARIO)
                    OR (entrada.ID_EMPLEADO_HORARIO IS NULL AND entrada.ID_HORARIO IS NOT NULL AND s.ID_HORARIO = entrada.ID_HORARIO)
                    OR (entrada.ID_EMPLEADO_HORARIO IS NULL AND entrada.ID_HORARIO IS NULL)
                )
                ORDER BY s.FECHA, s.HORA
                LIMIT 1
            )

            WHERE entrada.ID_EMPLEADO IN (" . str_repeat('?,', count($empleadosIds) - 1) . "?)
            AND entrada.TIPO = 'ENTRADA'
            AND entrada.FECHA BETWEEN ? AND ?

            -- Ordenar para que las salidas queden correctamente asociadas
            ORDER BY entrada.ID_EMPLEADO, entrada.FECHA, entrada.HORA, CONCAT(salida.FECHA, ' ', salida.HORA) ASC
        ";

        $stmt = $pdo->prepare($sql);

        // Bind parameters: empleados IDs + fecha inicio + fecha fin
        $params = array_merge($empleadosIds, [$fechaInicio, $fechaFin]);
        $stmt->execute($params);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // DEBUG: Mostrar todos los resultados de la consulta antes de procesar
        debug_log("DEBUG: Total filas devueltas por consulta SQL: " . count($resultados));
        foreach ($resultados as $i => $row) {
            $idEmpleado = $row['ID_EMPLEADO'];
            $idHorario = $row['ID_EMPLEADO_HORARIO'] ?? 'null';
            $idAsistencia = $row['id_entrada'];
            debug_log("DEBUG: Fila $i - EMPLEADO: $idEmpleado, ID_ASISTENCIA: $idAsistencia, ID_HORARIO: $idHorario, FECHA: " . $row['fecha_entrada'] . ", HORA: " . $row['hora_entrada']);
        }

        // Organizar resultados por empleado
        $empleadosData = [];
        foreach ($resultados as $row) {
            $idEmpleado = $row['ID_EMPLEADO'];

            if (!isset($empleadosData[$idEmpleado])) {
                $empleadosData[$idEmpleado] = [];
            }

            // Crear registro combinado similar al formato original
            $registro = [
                'FECHA' => $row['fecha_entrada'],
                'HORA' => $row['hora_entrada'],
                'TIPO' => 'ENTRADA',
                'ID_ASISTENCIA' => $row['id_entrada'],
                'DIA_SEMANA_NUM' => $row['DIA_SEMANA_NUM'],
                'NOMBRE' => $row['NOMBRE'],
                'APELLIDO' => $row['APELLIDO'],
                'ES_TURNO_NOCTURNO' => $row['ES_TURNO_NOCTURNO'],
                'HORARIO_ENTRADA' => $row['HORA_ENTRADA_PERSONALIZADO'] ?: $row['HORA_ENTRADA'],
                'HORARIO_SALIDA' => $row['HORA_SALIDA_PERSONALIZADO'] ?: $row['HORA_SALIDA'],
                'HORA_CORTE_NOCTURNO' => $row['HORA_CORTE_NOCTURNO'],
                'NOMBRE_TURNO' => $row['NOMBRE_TURNO'],
                'HORARIO_DIA' => $row['HORARIO_DIA'],
                'ID_EMPLEADO_HORARIO' => $row['ID_EMPLEADO_HORARIO'],
                'ID_HORARIO_TRADICIONAL' => $row['id_horario_tradicional'],
                'TARDANZA_ENTRADA' => $row['tardanza_entrada'],
                'TARDANZA_SALIDA' => $row['tardanza_salida'],
                'OBSERVACION_ENTRADA' => $row['observacion_entrada'],
                'OBSERVACION_SALIDA' => $row['observacion_salida'],
                'FOTO_ENTRADA' => $row['foto_entrada'],
                'FOTO_SALIDA' => $row['foto_salida'],
                'REGISTRO_MANUAL_ENTRADA' => $row['registro_manual_entrada'],
                'REGISTRO_MANUAL_SALIDA' => $row['registro_manual_salida'],
                // Agregar información de salida si existe
                'SALIDA_FECHA' => $row['fecha_salida'],
                'SALIDA_HORA' => $row['hora_salida'],
                'SALIDA_ID_ASISTENCIA' => $row['id_salida']
            ];

            $empleadosData[$idEmpleado][] = $registro;
        }

        return $empleadosData;
    }

    /**
     * Procesa los registros de asistencia de un empleado y calcula las estadísticas
     */
    function procesarEmpleado($id_empleado, $registros, $fecha_inicio, $fecha_fin, $pdo) {

        $resultado = [
            'empleado_id' => $id_empleado,
            'periodo' => [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ],
            'estadisticas' => [
                'recargo_nocturno' => 0,
                'recargo_dominical_festivo' => 0,
                'recargo_nocturno_dominical_festivo' => 0,
                'extra_diurna' => 0,
                'extra_nocturna' => 0,
                'extra_diurna_dominical_festiva' => 0,
                'extra_nocturna_dominical_festiva' => 0,
                'horas_regulares' => 0,
                'total_horas' => 0
            ],
            'detalle_dias' => [],
            'justificaciones' => []
        ];

        $extrasCalculadasPorFecha = [];

        // Obtener información del empleado
    $stmt = $pdo->prepare("SELECT NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ?");
        $stmt->execute([$id_empleado]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empleado) {
            $resultado['empleado_nombre'] = $empleado['NOMBRE'];
            $resultado['empleado_apellido'] = $empleado['APELLIDO'];
        }

        // Agrupar registros por fecha
        $registrosPorFecha = [];
        foreach ($registros as $registro) {
            $fecha = $registro['FECHA'];
            if (!isset($registrosPorFecha[$fecha])) {
                $registrosPorFecha[$fecha] = [];
            }
            $registrosPorFecha[$fecha][] = $registro;
        }

        // Ya no necesitamos deduplicar por fecha aquí, porque ya lo hicimos por horario al inicio
        // La deduplicación por fecha podría estar eliminando registros válidos de diferentes horarios

        // Procesar registros y agrupar por turnos (considerando turnos nocturnos como una unidad)
        // Usar los registros deduplicados en lugar de los originales
        $turnosPorFecha = [];
        $turnosNocturnosCompletos = [];

        foreach ($registros as $registro) {
            $horaEntrada = $registro['HORA'];
            $horaSalida = $registro['SALIDA_HORA'];
            $fechaEntrada = $registro['FECHA'];

            if (!$horaSalida) {
                continue; // Saltar si no hay salida
            }

            // Determinar si el turno cruza medianoche
            $cruzaMedianoche = $horaSalida < $horaEntrada;

            if ($cruzaMedianoche) {
                // Este es un turno nocturno completo - almacenarlo por separado
                $fechaSalida = date('Y-m-d', strtotime($fechaEntrada . ' +1 day'));
                $turnosNocturnosCompletos[] = [
                    'registro' => $registro,
                    'fecha_entrada' => $fechaEntrada,
                    'fecha_salida' => $fechaSalida,
                    'hora_entrada' => $horaEntrada,
                    'hora_salida' => $horaSalida,
                    'horas_totales' => calcularDiferenciaHoras($horaEntrada, $horaSalida)
                ];
            } else {
                // Turno normal - agrupar por fecha
                if (!isset($turnosPorFecha[$fechaEntrada])) {
                    $turnosPorFecha[$fechaEntrada] = [];
                }
                $turnosPorFecha[$fechaEntrada][] = $registro;
            }
        }

        // Ahora procesar cada fecha con sus turnos
        foreach ($turnosPorFecha as $fecha => $registrosFecha) {
            $diaSemana = date('w', strtotime($fecha));

            // Obtener TODOS los horarios personalizados para este día (antes de procesar justificaciones)
            $todosLosHorarios = obtenerHorariosPersonalizados($id_empleado, $fecha, $diaSemana, $pdo);
            $intervalosHorarioDia = construirIntervalosHorarioDia($todosLosHorarios, $fecha, $diaSemana);
            $intervalosHorarioPorFecha = empty($intervalosHorarioDia) ? [] : [$fecha => $intervalosHorarioDia];

            // Verificar si hay justificación
            $justificacion = obtenerJustificacion($id_empleado, $fecha, $pdo);

            // Determinar qué turnos están justificados
            $turnosJustificados = [];
            $justificarTodoElDia = false;

            if ($justificacion) {
                if ($justificacion['justificar_todos_turnos'] == 1) {
                    // Justificar todo el día
                    $justificarTodoElDia = true;
                } elseif ($justificacion['turno_id']) {
                    // Justificar un turno específico
                    $turnosJustificados[] = $justificacion['turno_id'];
                } elseif ($justificacion['turnos_ids']) {
                    // Justificar múltiples turnos específicos
                    $turnosIds = json_decode($justificacion['turnos_ids'], true);
                    if (is_array($turnosIds)) {
                        $turnosJustificados = array_merge($turnosJustificados, $turnosIds);
                    }
                }

                // Si hay justificación (de cualquier tipo), agregarla a la lista
                // Obtener información del empleado
                $queryEmpleado = "SELECT NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ?";
                $stmtEmpleado = $pdo->prepare($queryEmpleado);
                $stmtEmpleado->execute([$id_empleado]);
                $empleado = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);

                $resultado['justificaciones'][] = [
                    'ID_EMPLEADO' => $id_empleado,
                    'NOMBRE' => $empleado['NOMBRE'] ?? 'Desconocido',
                    'APELLIDO' => $empleado['APELLIDO'] ?? '',
                    'FECHA' => $fecha,
                    'DETALLE' => $justificacion['motivo'] . ($justificacion['detalle_adicional'] ? ' - ' . $justificacion['detalle_adicional'] : ''),
                    'ES_FESTIVO' => false, // TODO: Implementar lógica de días festivos
                    'motivo' => $justificacion['motivo'],
                    'detalle_adicional' => $justificacion['detalle_adicional'],
                    'tipo_falta' => $justificacion['tipo_falta'],
                    'estado' => 'aprobada', // Todas las justificaciones en la tabla se consideran aprobadas
                    'fecha_justificacion' => $justificacion['fecha_justificacion'],
                    'horas_programadas' => $justificacion['horas_programadas'],
                    'turno_id' => $justificacion['turno_id'],
                    'justificar_todos_turnos' => $justificacion['justificar_todos_turnos'],
                    'turnos_ids' => $justificacion['turnos_ids'],
                    'horarios_programados' => $todosLosHorarios
                ];
            }

            // Si se justifica todo el día, agregar el registro de justificación general
            if ($justificarTodoElDia) {
                debug_log("DEBUG: Agregando detalle_dias por JUSTIFICACION COMPLETA - Empleado $id_empleado, Fecha $fecha");
                $resultado['detalle_dias'][] = [
                    'fecha' => $fecha,
                    'es_domingo' => esDomingo($fecha),
                    'es_festivo' => esFestivo($fecha, $pdo),
                    'es_dia_civico' => esDiaCivico($fecha, $pdo),
                    'justificado' => true,
                    'horas_trabajadas' => 0,
                    'detalle_horas' => [],
                    'detalle_turnos' => [],
                    'todos_los_horarios' => $todosLosHorarios,
                    'horario_personalizado' => null,
                    'justificacion_general' => $justificacion ? [
                        'motivo' => $justificacion['motivo'],
                        'detalle_adicional' => $justificacion['detalle_adicional'],
                        'tipo_falta' => $justificacion['tipo_falta'],
                        'justificar_todos_turnos' => true,
                        'horarios_programados' => $todosLosHorarios
                    ] : null
                ];
                continue; // Saltar procesamiento de turnos individuales
            }
            
            foreach ($todosLosHorarios as $h) {
            }

            // Variable para compatibilidad (ya no se usa la lógica especial de turnos nocturnos)
            $esTurnoNocturno = false;

            // Procesar registros del día (turnos normales)
            $detalleHoras = [];
            $horasDelDia = 0;
            $turnosDetallados = [];
            $sumatoriasDia = [
                'horas_regulares' => 0,
                'recargo_nocturno' => 0,
                'recargo_dominical_festivo' => 0,
                'recargo_nocturno_dominical_festivo' => 0,
                'extra_diurna' => 0,
                'extra_nocturna' => 0,
                'extra_diurna_dominical_festiva' => 0,
                'extra_nocturna_dominical_festiva' => 0
            ];

            foreach ($registrosFecha as $registro) {
                $horaEntrada = $registro['HORA'];
                $horaSalida = $registro['SALIDA_HORA'];
                $fechaSalidaRegistro = $registro['SALIDA_FECHA'] ?? null;
                $intervalosParaEsteCalculo = is_array($intervalosHorarioPorFecha) ? $intervalosHorarioPorFecha : [];

                // Calcular horas trabajadas
                $horasTrabajadas = calcularDiferenciaHoras($horaEntrada, $horaSalida);

                // Determinar características del día
                $esFechaEsp = esFechaEspecial($fecha, $pdo);
                $esNocturnoEntrada = esHoraNocturna($horaEntrada);
                $esNocturnoSalida = esHoraNocturna($horaSalida);

                // Determinar si el turno cruza medianoche (es nocturno completo)
                $cruzaMedianoche = $horaSalida < $horaEntrada;
                $esTurnoNocturnoCompleto = $cruzaMedianoche || ($esNocturnoEntrada && $esNocturnoSalida);

                // Usar la determinación correcta para categorizar horas
                $esNocturnoParaCategoria = $esTurnoNocturnoCompleto;

                // BUSCAR EL HORARIO ESPECÍFICO PARA ESTE REGISTRO
                $horarioPersonalizado = null;
                $idEmpleadoHorario = $registro['ID_EMPLEADO_HORARIO'] ?? null;
                $turnoJustificado = $idEmpleadoHorario ? in_array($idEmpleadoHorario, $turnosJustificados) : false;

                if ($idEmpleadoHorario) {
                    // Buscar el horario específico por ID_EMPLEADO_HORARIO
                    foreach ($todosLosHorarios as $horario) {
                        if ($horario['ID_EMPLEADO_HORARIO'] == $idEmpleadoHorario) {
                            $horarioPersonalizado = $horario;
                            break;
                        }
                    }
                }

                // Si no se encontró horario específico por ID, buscar uno válido para el día actual
                if (!$horarioPersonalizado && !empty($todosLosHorarios)) {
                    // Seleccionar el horario con mejor coincidencia respecto a la entrada/salida real
                    $tzCalculo = new DateTimeZone('America/Bogota');
                    $entradaActualDT = crearDateTimeSeguro($fecha, $horaEntrada, $tzCalculo);
                    $salidaActualDT = crearDateTimeSeguro($fechaSalidaRegistro ?: $fecha, $horaSalida, $tzCalculo);

                    if ($salidaActualDT <= $entradaActualDT) {
                        $salidaActualDT = $salidaActualDT->modify('+1 day');
                    }

                    $mejorCoincidencia = null;
                    $mejorPuntaje = PHP_FLOAT_MIN;
                    $mejorDiferencia = PHP_FLOAT_MAX;

                    foreach ($todosLosHorarios as $horario) {
                        if (isset($horario['ID_DIA']) && $horario['ID_DIA'] !== null && (int)$horario['ID_DIA'] !== (int)$diaSemana) {
                            continue;
                        }

                        $horaInicioProg = normalizarHora($horario['HORA_ENTRADA']);
                        $horaFinProg = normalizarHora($horario['HORA_SALIDA']);

                        if ($horaInicioProg === null || $horaFinProg === null) {
                            continue;
                        }

                        $inicioHorarioDT = crearDateTimeSeguro($fecha, $horaInicioProg, $tzCalculo);
                        $finHorarioDT = crearDateTimeSeguro($fecha, $horaFinProg, $tzCalculo);

                        if ($finHorarioDT <= $inicioHorarioDT) {
                            $finHorarioDT = $finHorarioDT->modify('+1 day');
                        }

                        $puntaje = 0.0;
                        if ($entradaActualDT >= $inicioHorarioDT && $entradaActualDT <= $finHorarioDT) {
                            $puntaje += 1000.0; // Priorizar horarios que contienen la entrada real
                        }

                        if ($salidaActualDT >= $inicioHorarioDT && $salidaActualDT <= $finHorarioDT) {
                            $puntaje += 100.0; // Considerar también la salida dentro del rango
                        }

                        $diferenciaEntrada = abs($entradaActualDT->getTimestamp() - $inicioHorarioDT->getTimestamp());
                        $diferenciaSalida = abs($salidaActualDT->getTimestamp() - $finHorarioDT->getTimestamp());
                        $diferenciaTotal = min($diferenciaEntrada, $diferenciaSalida);

                        // Mientras más pequeño sea el desfase, mejor
                        $puntaje -= ($diferenciaTotal / 60.0);

                        if ($puntaje > $mejorPuntaje || ($puntaje === $mejorPuntaje && $diferenciaTotal < $mejorDiferencia)) {
                            $mejorPuntaje = $puntaje;
                            $mejorDiferencia = $diferenciaTotal;
                            $mejorCoincidencia = $horario;
                        }
                    }

                    if ($mejorCoincidencia) {
                        $horarioPersonalizado = $mejorCoincidencia;
                    }
                }

                // Usar el nuevo sistema jerárquico de cálculo de horas
                if ($horarioPersonalizado) {
                    $horaEntradaProg = $horarioPersonalizado['HORA_ENTRADA'];
                    $horaSalidaProg = $horarioPersonalizado['HORA_SALIDA'];

                    // VERIFICAR SI ES HORARIO TEMPORAL - SI LO ES, TODAS LAS HORAS SON EXTRAS
                    $esHorarioTemporal = isset($horarioPersonalizado['ES_TEMPORAL']) && $horarioPersonalizado['ES_TEMPORAL'] == 'S';

                    if ($esHorarioTemporal) {
                        // Para horarios temporales, todas las horas trabajadas son extras
                        // Calcular horas usando el sistema jerárquico pero considerando todo como fuera del horario
                        $calculoJerarquico = calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fecha, '00:00', '00:00', $pdo, $fechaSalidaRegistro, $intervalosParaEsteCalculo);

                        // Mover todas las horas regulares y recargos a horas extras
                        $horasRegularesTotales = $calculoJerarquico['horas_regulares'] +
                                                $calculoJerarquico['recargo_nocturno'] +
                                                $calculoJerarquico['recargo_dominical_festivo'] +
                                                $calculoJerarquico['recargo_nocturno_dominical_festivo'];

                        // Convertir horas regulares en extras
                        if ($horasRegularesTotales > 0) {
                            // Determinar la categoría de extra basada en el día y hora
                            $esFechaEspecialTemp = esFechaEspecial($fecha, $pdo) || esDomingo($fecha);
                            $esNocturnoTemp = esHoraNocturna($horaEntrada) || esHoraNocturna($horaSalida);

                            if ($esFechaEspecialTemp) {
                                if ($esNocturnoTemp) {
                                    $calculoJerarquico['extra_nocturna_dominical_festiva'] += $horasRegularesTotales;
                                } else {
                                    $calculoJerarquico['extra_diurna_dominical_festiva'] += $horasRegularesTotales;
                                }
                            } else {
                                if ($esNocturnoTemp) {
                                    $calculoJerarquico['extra_nocturna'] += $horasRegularesTotales;
                                } else {
                                    $calculoJerarquico['extra_diurna'] += $horasRegularesTotales;
                                }
                            }

                            // Resetear horas regulares y recargos
                            $calculoJerarquico['horas_regulares'] = 0;
                            $calculoJerarquico['recargo_nocturno'] = 0;
                            $calculoJerarquico['recargo_dominical_festivo'] = 0;
                            $calculoJerarquico['recargo_nocturno_dominical_festivo'] = 0;

                            // Actualizar segmentos para marcar como extras
                            foreach ($calculoJerarquico['segmentos'] as &$segmento) {
                                $segmento['tipo'] = 'extra';
                                $segmento['es_extra'] = true;
                            }
                        }
                    } else {
                        // Normalizar horas del horario programado a formato H:i:s
                        $horaEntradaProgNormalizada = $horaEntradaProg ? date('H:i:s', strtotime($horaEntradaProg)) : '00:00:00';
                        $horaSalidaProgNormalizada = $horaSalidaProg ? date('H:i:s', strtotime($horaSalidaProg)) : '23:59:59';

                        // Calcular horas usando el sistema jerárquico normal
            $calculoJerarquico = calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fecha, $horaEntradaProgNormalizada, $horaSalidaProgNormalizada, $pdo, $fechaSalidaRegistro, []);
            error_log("DEBUG: Horario prog - entradaProg=$horaEntradaProg, salidaProg=$horaSalidaProg, normalizadaEntrada=$horaEntradaProgNormalizada, normalizadaSalida=$horaSalidaProgNormalizada");
            error_log("DEBUG: calculateHoursWithHierarchy called with entrada=$horaEntrada, salida=$horaSalida, fecha=$fecha, entradaProg=$horaEntradaProgNormalizada, salidaProg=$horaSalidaProgNormalizada, fechaSalida=$fechaSalidaRegistro");
            error_log("DEBUG: Result - reg:" . $calculoJerarquico['horas_regulares'] . " rec_noct:" . $calculoJerarquico['recargo_nocturno'] . " extra_diurna:" . $calculoJerarquico['extra_diurna'] . " extra_noct:" . $calculoJerarquico['extra_nocturna']);
                    }

                    // Acumular en estadísticas (solo horas regulares y recargos, horas extras se manejan por separado)
                    // Solo acumular si el turno no está justificado
                    if (!$turnoJustificado) {
                        $resultado['estadisticas']['horas_regulares'] += $calculoJerarquico['horas_regulares'];
                        $resultado['estadisticas']['recargo_nocturno'] += $calculoJerarquico['recargo_nocturno'];
                        $resultado['estadisticas']['recargo_dominical_festivo'] += $calculoJerarquico['recargo_dominical_festivo'];
                        $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $calculoJerarquico['recargo_nocturno_dominical_festivo'];
                        $resultado['estadisticas']['extra_diurna'] += $calculoJerarquico['extra_diurna'];
                        $resultado['estadisticas']['extra_nocturna'] += $calculoJerarquico['extra_nocturna'];
                        $resultado['estadisticas']['extra_diurna_dominical_festiva'] += $calculoJerarquico['extra_diurna_dominical_festiva'];
                        $resultado['estadisticas']['extra_nocturna_dominical_festiva'] += $calculoJerarquico['extra_nocturna_dominical_festiva'];

                        $sumatoriasDia['horas_regulares'] += $calculoJerarquico['horas_regulares'];
                        $sumatoriasDia['recargo_nocturno'] += $calculoJerarquico['recargo_nocturno'];
                        $sumatoriasDia['recargo_dominical_festivo'] += $calculoJerarquico['recargo_dominical_festivo'];
                        $sumatoriasDia['recargo_nocturno_dominical_festivo'] += $calculoJerarquico['recargo_nocturno_dominical_festivo'];
                        $sumatoriasDia['extra_diurna'] += $calculoJerarquico['extra_diurna'];
                        $sumatoriasDia['extra_nocturna'] += $calculoJerarquico['extra_nocturna'];
                        $sumatoriasDia['extra_diurna_dominical_festiva'] += $calculoJerarquico['extra_diurna_dominical_festiva'];
                        $sumatoriasDia['extra_nocturna_dominical_festiva'] += $calculoJerarquico['extra_nocturna_dominical_festiva'];
                    }

                    // Crear registros de detalle para cada segmento
                    foreach ($calculoJerarquico['segmentos'] as $segmento) {
                        // Verificar si este turno específico está justificado

                        $tipoExtraRelativo = null;
                        if (!empty($segmento['es_extra'])) {
                            if (in_array($segmento['posicion'], ['antes', 'despues'], true)) {
                                $tipoExtraRelativo = $segmento['posicion'];
                            } elseif (in_array($segmento['posicion'], ['sin_horario', 'fuera'], true)) {
                                $tipoExtraRelativo = 'sin_horario';
                            } else {
                                $tipoExtraRelativo = 'despues';
                            }
                        }

                        $detalleHoras[] = [
                            'hora_entrada' => $segmento['hora_inicio'],
                            'hora_salida' => $segmento['hora_fin'],
                            'hora_entrada_turno' => $horaEntrada,
                            'hora_salida_turno' => $horaSalida,
                            'fecha_segmento' => $segmento['fecha'] ?? $fecha,
                            'horas_trabajadas' => $segmento['horas'],
                            'dentro_horario' => !$segmento['es_extra'],
                            'es_nocturno' => $segmento['es_nocturno'],
                            'id_empleado_horario' => $registro['ID_EMPLEADO_HORARIO'] ?? null,
                            'nombre_turno' => $registro['NOMBRE_TURNO'] ?? null,
                            'categoria' => $segmento['categoria'],
                            'tipo_extra' => $tipoExtraRelativo,
                            'posicion' => $segmento['posicion'] ?? null,
                            'segmentos' => [$segmento],
                            'justificado' => $turnoJustificado
                        ];
                    }

                    registrarExtrasSegmentosPorFecha($extrasCalculadasPorFecha, $calculoJerarquico['segmentos']);

                    $horasRegulares = $calculoJerarquico['horas_regulares'] + $calculoJerarquico['recargo_nocturno'] + $calculoJerarquico['recargo_dominical_festivo'] + $calculoJerarquico['recargo_nocturno_dominical_festivo'];
                    $horasExtra = $calculoJerarquico['extra_diurna'] + $calculoJerarquico['extra_nocturna'] + $calculoJerarquico['extra_diurna_dominical_festiva'] + $calculoJerarquico['extra_nocturna_dominical_festiva'];
                } else {
                    // Sin horario personalizado, todas las horas son extras usando jerarquía
                    $calculoJerarquico = calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fecha, '00:00', '00:00', $pdo, $fechaSalidaRegistro, []);

                    $resultado['estadisticas']['extra_diurna'] += $calculoJerarquico['extra_diurna'];
                    $resultado['estadisticas']['extra_nocturna'] += $calculoJerarquico['extra_nocturna'];
                    $resultado['estadisticas']['extra_diurna_dominical_festiva'] += $calculoJerarquico['extra_diurna_dominical_festiva'];
                    $resultado['estadisticas']['extra_nocturna_dominical_festiva'] += $calculoJerarquico['extra_nocturna_dominical_festiva'];

                    if (!$turnoJustificado) {
                        $sumatoriasDia['extra_diurna'] += $calculoJerarquico['extra_diurna'];
                        $sumatoriasDia['extra_nocturna'] += $calculoJerarquico['extra_nocturna'];
                        $sumatoriasDia['extra_diurna_dominical_festiva'] += $calculoJerarquico['extra_diurna_dominical_festiva'];
                        $sumatoriasDia['extra_nocturna_dominical_festiva'] += $calculoJerarquico['extra_nocturna_dominical_festiva'];
                    }

                    // Solo generar registros para horas extras (ya que no hay horario programado)
                    foreach ($calculoJerarquico['segmentos'] as $segmento) {
                        if ($segmento['es_extra']) {
                            // Verificar si este turno específico está justificado

                            $tipoExtraRelativo = in_array($segmento['posicion'], ['antes', 'despues'], true)
                                ? $segmento['posicion']
                                : (in_array($segmento['posicion'], ['sin_horario', 'fuera'], true) ? 'sin_horario' : 'despues');

                            $detalleHoras[] = [
                                'hora_entrada' => $segmento['hora_inicio'],
                                'hora_salida' => $segmento['hora_fin'],
                                'hora_entrada_turno' => $horaEntrada,
                                'hora_salida_turno' => $horaSalida,
                                'fecha_segmento' => $segmento['fecha'] ?? $fecha,
                                'horas_trabajadas' => $segmento['horas'],
                                'dentro_horario' => false,
                                'es_nocturno' => $segmento['es_nocturno'],
                                'id_empleado_horario' => $registro['ID_EMPLEADO_HORARIO'] ?? null,
                                'nombre_turno' => $registro['NOMBRE_TURNO'] ?? null,
                                'categoria' => $segmento['categoria'],
                                'tipo_extra' => $tipoExtraRelativo,
                                'posicion' => $segmento['posicion'] ?? null,
                                'segmentos' => [$segmento],
                                'justificado' => $turnoJustificado
                            ];
                        }
                    }

                    registrarExtrasSegmentosPorFecha($extrasCalculadasPorFecha, $calculoJerarquico['segmentos']);

                    $horasRegulares = 0;
                    $horasExtra = $calculoJerarquico['extra_diurna'] + $calculoJerarquico['extra_nocturna'] + $calculoJerarquico['extra_diurna_dominical_festiva'] + $calculoJerarquico['extra_nocturna_dominical_festiva'];
                }

                $horasTotalesTurno = $horasRegulares + $horasExtra;
                $segmentosTurno = is_array($calculoJerarquico['segmentos']) ? array_values($calculoJerarquico['segmentos']) : [];

                $horarioEntradaBase = $horarioPersonalizado['HORA_ENTRADA'] ?? ($registro['HORARIO_ENTRADA'] ?? null);
                $horarioSalidaBase = $horarioPersonalizado['HORA_SALIDA'] ?? ($registro['HORARIO_SALIDA'] ?? null);
                $horarioResumenTurno = null;

                if ($horarioEntradaBase && $horarioSalidaBase) {
                    $horarioResumenTurno = substr((string)$horarioEntradaBase, 0, 5) . ' - ' . substr((string)$horarioSalidaBase, 0, 5);
                } elseif ($horarioEntradaBase) {
                    $horarioResumenTurno = substr((string)$horarioEntradaBase, 0, 5);
                }

                $nombreTurnoAsignado = $horarioPersonalizado['NOMBRE_TURNO'] ?? ($registro['NOMBRE_TURNO'] ?? null);
                if ($horarioResumenTurno && $nombreTurnoAsignado) {
                    $horarioResumenTurno .= ' (' . $nombreTurnoAsignado . ')';
                } elseif (!$horarioResumenTurno && $nombreTurnoAsignado) {
                    $horarioResumenTurno = $nombreTurnoAsignado;
                }

                if (!empty($horarioPersonalizado['ES_TEMPORAL']) && $horarioPersonalizado['ES_TEMPORAL'] === 'S') {
                    $horarioResumenTurno = trim(($horarioResumenTurno ?? '') . ' [Temporal]');
                }

                $fechaSalidaTurno = $fechaSalidaRegistro ?: $fecha;
                if (!$fechaSalidaRegistro && $horaEntrada !== null && $horaSalida !== null && $horaSalida < $horaEntrada) {
                    $fechaSalidaTurno = date('Y-m-d', strtotime($fecha . ' +1 day'));
                }

                $turnosDetallados[] = [
                    'id_asistencia_entrada' => $registro['ID_ASISTENCIA'] ?? null,
                    'id_asistencia_salida' => $registro['SALIDA_ID_ASISTENCIA'] ?? null,
                    'fecha_turno' => $fecha,
                    'fecha_salida' => $fechaSalidaTurno,
                    'hora_entrada' => $horaEntrada,
                    'hora_salida' => $horaSalida,
                    'horario_resumen' => $horarioResumenTurno,
                    'nombre_turno' => $nombreTurnoAsignado,
                    'id_empleado_horario' => $idEmpleadoHorario,
                    'id_horario_tradicional' => $registro['ID_HORARIO_TRADICIONAL'] ?? null,
                    'clasificacion' => [
                        'horas_regulares' => $calculoJerarquico['horas_regulares'],
                        'recargo_nocturno' => $calculoJerarquico['recargo_nocturno'],
                        'recargo_dominical_festivo' => $calculoJerarquico['recargo_dominical_festivo'],
                        'recargo_nocturno_dominical_festivo' => $calculoJerarquico['recargo_nocturno_dominical_festivo'],
                        'extra_diurna' => $calculoJerarquico['extra_diurna'],
                        'extra_nocturna' => $calculoJerarquico['extra_nocturna'],
                        'extra_diurna_dominical_festiva' => $calculoJerarquico['extra_diurna_dominical_festiva'],
                        'extra_nocturna_dominical_festiva' => $calculoJerarquico['extra_nocturna_dominical_festiva']
                    ],
                    'horas_totales' => $horasTotalesTurno,
                    'horas_regulares_sumadas' => $horasRegulares,
                    'horas_extras_sumadas' => $horasExtra,
                    'segmentos' => $segmentosTurno,
                    'justificado' => $turnoJustificado,
                    'observaciones' => [
                        'entrada' => $registro['OBSERVACION_ENTRADA'] ?? null,
                        'salida' => $registro['OBSERVACION_SALIDA'] ?? null
                    ],
                    'tardanza' => [
                        'entrada' => $registro['TARDANZA_ENTRADA'] ?? null,
                        'salida' => $registro['TARDANZA_SALIDA'] ?? null
                    ],
                    'registro_manual' => [
                        'entrada' => $registro['REGISTRO_MANUAL_ENTRADA'] ?? null,
                        'salida' => $registro['REGISTRO_MANUAL_SALIDA'] ?? null
                    ],
                    'fotos' => [
                        'entrada' => $registro['FOTO_ENTRADA'] ?? null,
                        'salida' => $registro['FOTO_SALIDA'] ?? null
                    ],
                    'detalle_tipo' => $esTurnoNocturnoCompleto ? 'turno_nocturno' : 'turno_diurno',
                    'es_turno_nocturno' => $esTurnoNocturnoCompleto,
                    'es_horario_temporal' => !empty($horarioPersonalizado['ES_TEMPORAL']) && $horarioPersonalizado['ES_TEMPORAL'] === 'S'
                ];

                $horasDelDia += $horasRegulares; // Solo contar horas regulares como horas trabajadas

                // Si este turno está justificado, restar las horas que acabamos de agregar
                if ($turnoJustificado) {
                    $horasDelDia -= $horasRegulares;
                }
            }
            // Construir respuesta del día
            if ($esTurnoNocturno) {
                // Para turnos nocturnos, devolver objeto con información del turno
                $detalleHorasObj = [
                    'tipo' => 'turno_nocturno',
                    'horario' => $horarioPersonalizado ? 
                        ($horarioPersonalizado['HORA_ENTRADA'] . ' - ' . $horarioPersonalizado['HORA_SALIDA']) : 
                        'Horario no definido',
                    'horas_trabajadas' => $horasDelDia
                ];

                // Agregar primera entrada y última salida si existen registros
                if (!empty($detalleHoras)) {
                    $primeraEntrada = null;
                    $ultimaSalida = null;
                    foreach ($detalleHoras as $detalle) {
                        if ($detalle['hora_entrada'] && (!$primeraEntrada || $detalle['hora_entrada'] < $primeraEntrada)) {
                            $primeraEntrada = $detalle['hora_entrada'];
                        }
                        if ($detalle['hora_salida'] && (!$ultimaSalida || $detalle['hora_salida'] > $ultimaSalida)) {
                            $ultimaSalida = $detalle['hora_salida'];
                        }
                    }
                    
                    if ($primeraEntrada) $detalleHorasObj['entrada_real'] = $primeraEntrada;
                    if ($ultimaSalida) $detalleHorasObj['salida_real'] = $ultimaSalida;
                }

                $detalleHorasFinal = $detalleHorasObj;
            } else {
                // Para turnos diurnos, devolver array con detalle de horas
                $detalleHorasFinal = $detalleHoras;
            }

            // Usar estadísticas calculadas directamente del sistema jerárquico
            // Eliminado el recálculo con calcularHorasRegularesExtras que causaba inconsistencias
            $estadisticasDia = [
                'horas_regulares' => $sumatoriasDia['horas_regulares'],
                'recargo_nocturno' => $sumatoriasDia['recargo_nocturno'],
                'recargo_dominical_festivo' => $sumatoriasDia['recargo_dominical_festivo'],
                'recargo_nocturno_dominical_festivo' => $sumatoriasDia['recargo_nocturno_dominical_festivo'],
                'extra_diurna' => $sumatoriasDia['extra_diurna'],
                'extra_nocturna' => $sumatoriasDia['extra_nocturna'],
                'extra_diurna_dominical_festiva' => $sumatoriasDia['extra_diurna_dominical_festiva'],
                'extra_nocturna_dominical_festiva' => $sumatoriasDia['extra_nocturna_dominical_festiva'],
                'total_horas' => array_sum($sumatoriasDia)
            ];

            // Determinar si el día completo está justificado
            // Un día está justificado si se justificó todo el día O si todos los turnos están justificados
            $diaCompletamenteJustificado = $justificarTodoElDia;

            if (!$diaCompletamenteJustificado && !empty($detalleHoras)) {
                // Contar turnos justificados vs total de turnos
                $totalTurnos = count($detalleHoras);
                $turnosJustificadosCount = 0;

                foreach ($detalleHoras as $detalle) {
                    if ($detalle['justificado']) {
                        $turnosJustificadosCount++;
                    }
                }

                // Si todos los turnos están justificados, marcar el día como justificado
                if ($turnosJustificadosCount > 0 && $turnosJustificadosCount >= $totalTurnos) {
                    $diaCompletamenteJustificado = true;
                }
            }

            $resultado['detalle_dias'][] = [
                'fecha' => $fecha,
                'es_domingo' => esDomingo($fecha),
                'es_festivo' => esFestivo($fecha, $pdo),
                'es_dia_civico' => esDiaCivico($fecha, $pdo),
                'justificado' => $diaCompletamenteJustificado,
                'horas_trabajadas' => $horasDelDia,
                'horario_personalizado' => $horarioPersonalizado,
                'todos_los_horarios' => $todosLosHorarios,
                'detalle_horas' => $detalleHorasFinal,
                'detalle_turnos' => $turnosDetallados,
                // Agregar estadísticas calculadas del día
                'horas_regulares' => round($estadisticasDia['horas_regulares'], 2),
                'recargo_nocturno' => round($estadisticasDia['recargo_nocturno'], 2),
                'recargo_dominical_festivo' => round($estadisticasDia['recargo_dominical_festivo'], 2),
                'recargo_nocturno_dominical_festivo' => round($estadisticasDia['recargo_nocturno_dominical_festivo'], 2),
                'extra_diurna' => round($estadisticasDia['extra_diurna'], 2),
                'extra_nocturna' => round($estadisticasDia['extra_nocturna'], 2),
                'extra_diurna_dominical_festiva' => round($estadisticasDia['extra_diurna_dominical_festiva'], 2),
                'extra_nocturna_dominical_festiva' => round($estadisticasDia['extra_nocturna_dominical_festiva'], 2),
                'total_horas' => round($estadisticasDia['total_horas'], 2)
            ];

            // Generar horas extras para aprobación solo si no existen para este empleado en esta fecha
            if (is_array($detalleHoras)) {
                foreach ($detalleHoras as $detalle) {
                    // Generar horas extras SOLO para segmentos que están FUERA del horario asignado
                    // Los recargos por días especiales dentro del horario son automáticos y no requieren aprobación
                    $esSegmentoExtra = isset($detalle['dentro_horario']) && $detalle['dentro_horario'] === false && isset($detalle['horas_trabajadas']) && $detalle['horas_trabajadas'] > 0;

                    if ($esSegmentoExtra) {
                        // Es una hora extra real (fuera del horario), generar registro para aprobación
                        $fechaExtra = $detalle['fecha_segmento'] ?? $fecha;
                        $horaInicio = $detalle['hora_entrada'] ?? '00:00';
                        $horaFin = $detalle['hora_salida'] ?? '23:59';
                        $horasExtras = $detalle['horas_trabajadas'];
                        $tipoExtra = $detalle['tipo_extra'] ?? ($detalle['posicion'] ?? 'despues');

                        // Usar la clasificación del cálculo jerárquico en lugar de recalcular
                        // El detalle ya incluye la categoría correcta del sistema jerárquico
                        $categoria = $detalle['categoria'] ?? 'diurna';
                        $tipoHorario = 'diurna';
                        if (strpos($categoria, 'festiva') !== false || strpos($categoria, 'dominical') !== false) {
                            $tipoHorario = strpos($categoria, 'nocturna') !== false ? 'nocturna_dominical' : 'diurna_dominical';
                        } elseif (strpos($categoria, 'nocturna') !== false) {
                            $tipoHorario = 'nocturna';
                        }

                        // Asegurar que tipoExtra sea válido para el enum
                        if ($tipoExtra !== 'antes' && $tipoExtra !== 'despues') {
                            $tipoExtra = 'despues'; // valor por defecto válido
                        }

                        // Generar hora extra solo si no existe
                        // Usar el ID_EMPLEADO_HORARIO específico del segmento, no el general del día
                        $idHorarioSegmento = $detalle['id_empleado_horario'] ?? ($horarioPersonalizado ? $horarioPersonalizado["ID_EMPLEADO_HORARIO"] : null);
                        generarHorasExtrasSiNoExisten($id_empleado, $idHorarioSegmento, $fechaExtra, $horaInicio, $horaFin, $horasExtras, $tipoExtra, $tipoHorario, $pdo);
                    }
                }
            }
            
            $resultado['estadisticas']['total_horas'] += $horasDelDia;
        }

        // Procesar turnos nocturnos completos (que cruzan medianoche)
        foreach ($turnosNocturnosCompletos as $turnoNocturno) {
            $registro = $turnoNocturno['registro'];
            $fechaEntrada = $turnoNocturno['fecha_entrada'];
            $fechaSalida = $turnoNocturno['fecha_salida'];
            $horaEntrada = $turnoNocturno['hora_entrada'];
            $horaSalida = $turnoNocturno['hora_salida'];
            $horasTotales = $turnoNocturno['horas_totales'];

            // Usar la fecha de entrada como fecha principal para el turno
            $fechaPrincipal = $fechaEntrada;
            $diaSemana = date('w', strtotime($fechaPrincipal));

            // Obtener horarios para la fecha de entrada
            $todosLosHorarios = obtenerHorariosPersonalizados($id_empleado, $fechaPrincipal, $diaSemana, $pdo);
            $intervalosHorarioDia = construirIntervalosHorarioDia($todosLosHorarios, $fechaPrincipal, $diaSemana);
            $intervalosHorarioPorFecha = empty($intervalosHorarioDia) ? [] : [$fechaPrincipal => $intervalosHorarioDia];
            $intervalosHorarioPorFecha = empty($intervalosHorarioDia) ? [] : [$fechaPrincipal => $intervalosHorarioDia];

            // Verificar si hay justificación en alguna de las fechas
            $justificacionEntrada = obtenerJustificacion($id_empleado, $fechaEntrada, $pdo);
            $justificacionSalida = obtenerJustificacion($id_empleado, $fechaSalida, $pdo);

            if ($justificacionEntrada || $justificacionSalida) {
                // Hay justificación - registrar pero con 0 horas
                $justificacion = $justificacionEntrada ?: $justificacionSalida;
                $resultado['justificaciones'][] = [
                    'ID_EMPLEADO' => $id_empleado,
                    'NOMBRE' => $resultado['empleado_nombre'] ?? 'Desconocido',
                    'APELLIDO' => $resultado['empleado_apellido'] ?? '',
                    'FECHA' => $fechaPrincipal,
                    'DETALLE' => $justificacion['motivo'] . ($justificacion['detalle_adicional'] ? ' - ' . $justificacion['detalle_adicional'] : ''),
                    'ES_FESTIVO' => esFestivo($fechaPrincipal, $pdo),
                    'motivo' => $justificacion['motivo'],
                    'detalle_adicional' => $justificacion['detalle_adicional'],
                    'tipo_falta' => $justificacion['tipo_falta'],
                    'estado' => $justificacion['estado'] ?? 'aprobada',
                    'fecha_justificacion' => $justificacion['fecha_justificacion'],
                    'horas_programadas' => $justificacion['horas_programadas'],
                    'turno_id' => $justificacion['turno_id'] ?? null,
                    'justificar_todos_turnos' => $justificacion['justificar_todos_turnos'] ?? 0,
                    'turnos_ids' => $justificacion['turnos_ids'] ?? null,
                    'horarios_programados' => $todosLosHorarios
                ];

                $resultado['detalle_dias'][] = [
                    'fecha' => $fechaPrincipal,
                    'FECHA_COMPLETA' => $fechaEntrada . ' - ' . $fechaSalida, // Mostrar ambas fechas
                    'es_domingo' => esDomingo($fechaPrincipal),
                    'es_festivo' => esFestivo($fechaPrincipal, $pdo),
                    'es_dia_civico' => esDiaCivico($fechaPrincipal, $pdo),
                    'justificado' => true,
                    'horas_trabajadas' => 0,
                    'detalle_horas' => [],
                    'detalle_turnos' => [],
                    'todos_los_horarios' => $todosLosHorarios,
                    'horario_personalizado' => null,
                    'justificacion_general' => [
                        'motivo' => $justificacion['motivo'],
                        'detalle_adicional' => $justificacion['detalle_adicional'],
                        'tipo_falta' => $justificacion['tipo_falta'],
                        'justificar_todos_turnos' => (int)($justificacion['justificar_todos_turnos'] ?? 0) === 1,
                        'horarios_programados' => $todosLosHorarios
                    ]
                ];
                continue;
            }

            // Verificar si es turno nocturno programado
            $horarioPersonalizado = null;
            if (count($todosLosHorarios) === 1) {
                $horarioPersonalizado = $todosLosHorarios[0];
            }

            $intervalosHorarioDia = construirIntervalosHorarioDia($todosLosHorarios, $fechaPrincipal, $diaSemana);

            // Determinar características del día (usar fecha de entrada)
            $esFechaEsp = esFechaEspecial($fechaPrincipal, $pdo);

            // Usar el nuevo sistema jerárquico para turnos nocturnos completos
            if ($horarioPersonalizado) {
                $horaEntradaProg = $horarioPersonalizado['HORA_ENTRADA'];
                $horaSalidaProg = $horarioPersonalizado['HORA_SALIDA'];

                // Normalizar horas del horario programado
                $horaEntradaProgNormalizada = date('H:i:s', strtotime($horaEntradaProg));
                $horaSalidaProgNormalizada = date('H:i:s', strtotime($horaSalidaProg));

                // Calcular horas usando el sistema jerárquico
                $calculoJerarquico = calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fechaPrincipal, $horaEntradaProgNormalizada, $horaSalidaProgNormalizada, $pdo, $fechaSalida, $intervalosHorarioPorFecha);

                // Acumular en estadísticas (solo horas regulares y recargos)
                $resultado['estadisticas']['horas_regulares'] += $calculoJerarquico['horas_regulares'];
                $resultado['estadisticas']['recargo_nocturno'] += $calculoJerarquico['recargo_nocturno'];
                $resultado['estadisticas']['recargo_dominical_festivo'] += $calculoJerarquico['recargo_dominical_festivo'];
                $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $calculoJerarquico['recargo_nocturno_dominical_festivo'];
                $resultado['estadisticas']['extra_diurna'] += $calculoJerarquico['extra_diurna'];
                $resultado['estadisticas']['extra_nocturna'] += $calculoJerarquico['extra_nocturna'];
                $resultado['estadisticas']['extra_diurna_dominical_festiva'] += $calculoJerarquico['extra_diurna_dominical_festiva'];
                $resultado['estadisticas']['extra_nocturna_dominical_festiva'] += $calculoJerarquico['extra_nocturna_dominical_festiva'];

                // Crear registros de detalle para cada segmento
                $detallesTurno = [];
                foreach ($calculoJerarquico['segmentos'] as $segmento) {
                    $tipoExtraRelativo = null;
                    if (!empty($segmento['es_extra'])) {
                        if (in_array($segmento['posicion'], ['antes', 'despues'], true)) {
                            $tipoExtraRelativo = $segmento['posicion'];
                        } elseif (in_array($segmento['posicion'], ['sin_horario', 'fuera'], true)) {
                            $tipoExtraRelativo = 'sin_horario';
                        } else {
                            $tipoExtraRelativo = 'despues';
                        }
                    }

                    $detallesTurno[] = [
                        'hora_entrada' => $segmento['hora_inicio'],
                        'hora_salida' => $segmento['hora_fin'],
                        'hora_entrada_turno' => $horaEntrada,
                        'hora_salida_turno' => $horaSalida,
                        'fecha_segmento' => $segmento['fecha'] ?? $fechaPrincipal,
                        'horas_trabajadas' => $segmento['horas'],
                        'dentro_horario' => !$segmento['es_extra'],
                        'es_nocturno' => $segmento['es_nocturno'],
                        'es_turno_nocturno_completo' => true,
                        'fecha_entrada' => $fechaEntrada,
                        'fecha_salida' => $fechaSalida,
                        'id_empleado_horario' => $registro['ID_EMPLEADO_HORARIO'] ?? null,
                        'nombre_turno' => $registro['NOMBRE_TURNO'] ?? null,
                        'categoria' => $segmento['categoria'],
                        'tipo_extra' => $tipoExtraRelativo,
                        'posicion' => $segmento['posicion'] ?? null,
                        'segmentos' => [$segmento]
                    ];
                }

                $horasRegulares = $calculoJerarquico['horas_regulares'] + $calculoJerarquico['recargo_nocturno'] + $calculoJerarquico['recargo_dominical_festivo'] + $calculoJerarquico['recargo_nocturno_dominical_festivo'];
                $horasExtra = $calculoJerarquico['extra_diurna'] + $calculoJerarquico['extra_nocturna'] + $calculoJerarquico['extra_diurna_dominical_festiva'] + $calculoJerarquico['extra_nocturna_dominical_festiva'];
                $horasTotalesTurno = $horasRegulares + $horasExtra;
            } else {
                // Sin horario personalizado, todas las horas son extras usando jerarquía
                $calculoJerarquico = calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fechaPrincipal, '00:00', '00:00', $pdo, $fechaSalida, []);


                // Asegurar que las justificaciones aparezcan incluso sin registros de asistencia
                $justificacionesPeriodo = obtenerJustificacionesEnRango($id_empleado, $fecha_inicio, $fecha_fin, $pdo);
                if (!empty($justificacionesPeriodo)) {
                    $fechasDetalle = [];
                    foreach ($resultado['detalle_dias'] as $diaExistente) {
                        $fechasDetalle[$diaExistente['fecha']] = true;
                    }

                    foreach ($justificacionesPeriodo as $justificacionDia) {
                        $fechaJustificada = $justificacionDia['fecha_falta'];
                        $esJornadaCompleta = (int)($justificacionDia['justificar_todos_turnos'] ?? 0) === 1;

                        $diaSemanaJust = date('w', strtotime($fechaJustificada));
                        $horariosProgramados = obtenerHorariosPersonalizados($id_empleado, $fechaJustificada, $diaSemanaJust, $pdo);

                        if (!isset($fechasDetalle[$fechaJustificada])) {
                            $resultado['detalle_dias'][] = [
                                'fecha' => $fechaJustificada,
                                'es_domingo' => esDomingo($fechaJustificada),
                                'es_festivo' => esFestivo($fechaJustificada, $pdo),
                                'es_dia_civico' => esDiaCivico($fechaJustificada, $pdo),
                                'justificado' => true,
                                'horas_trabajadas' => 0,
                                'detalle_horas' => [],
                                'detalle_turnos' => [],
                                'todos_los_horarios' => $horariosProgramados,
                                'horario_personalizado' => null,
                                'justificacion_general' => [
                                    'motivo' => $justificacionDia['motivo'],
                                    'detalle_adicional' => $justificacionDia['detalle_adicional'],
                                    'tipo_falta' => $justificacionDia['tipo_falta'],
                                    'justificar_todos_turnos' => $esJornadaCompleta,
                                    'horarios_programados' => $horariosProgramados
                                ]
                            ];
                            $fechasDetalle[$fechaJustificada] = true;
                        }

                        $justificacionRegistrada = false;
                        foreach ($resultado['justificaciones'] as $registroJustificacion) {
                            $fechaRegistrada = $registroJustificacion['FECHA'] ?? ($registroJustificacion['fecha'] ?? null);
                            if ($fechaRegistrada === $fechaJustificada) {
                                $justificacionRegistrada = true;
                                break;
                            }
                        }

                        if (!$justificacionRegistrada) {
                            $resultado['justificaciones'][] = [
                                'ID_EMPLEADO' => $id_empleado,
                                'NOMBRE' => $resultado['empleado_nombre'] ?? 'Desconocido',
                                'APELLIDO' => $resultado['empleado_apellido'] ?? '',
                                'FECHA' => $fechaJustificada,
                                'DETALLE' => $justificacionDia['motivo'] . ($justificacionDia['detalle_adicional'] ? ' - ' . $justificacionDia['detalle_adicional'] : ''),
                                'ES_FESTIVO' => esFestivo($fechaJustificada, $pdo),
                                'motivo' => $justificacionDia['motivo'],
                                'detalle_adicional' => $justificacionDia['detalle_adicional'],
                                'tipo_falta' => $justificacionDia['tipo_falta'],
                                'estado' => 'aprobada',
                                'fecha_justificacion' => $justificacionDia['fecha_justificacion'],
                                'horas_programadas' => $justificacionDia['horas_programadas'],
                                'turno_id' => $justificacionDia['turno_id'],
                                'justificar_todos_turnos' => $justificacionDia['justificar_todos_turnos'],
                                'turnos_ids' => $justificacionDia['turnos_ids'],
                                'horarios_programados' => $horariosProgramados
                            ];
                        }
                    }
                }

                // Ordenar resultados por fecha para mantener consistencia en la UI
                usort($resultado['detalle_dias'], function ($a, $b) {
                    return strcmp($a['fecha'], $b['fecha']);
                });
                // Solo generar registros para horas extras
                $detallesTurno = [];
                foreach ($calculoJerarquico['segmentos'] as $segmento) {
                    if ($segmento['es_extra']) {
                        $tipoExtraRelativo = in_array($segmento['posicion'], ['antes', 'despues'], true)
                            ? $segmento['posicion']
                            : (in_array($segmento['posicion'], ['sin_horario', 'fuera'], true) ? 'sin_horario' : 'despues');

                        $detallesTurno[] = [
                            'hora_entrada' => $segmento['hora_inicio'],
                            'hora_salida' => $segmento['hora_fin'],
                            'hora_entrada_turno' => $horaEntrada,
                            'hora_salida_turno' => $horaSalida,
                            'fecha_segmento' => $segmento['fecha'] ?? $fechaPrincipal,
                            'horas_trabajadas' => $segmento['horas'],
                            'dentro_horario' => false,
                            'es_nocturno' => $segmento['es_nocturno'],
                            'es_turno_nocturno_completo' => true,
                            'fecha_entrada' => $fechaEntrada,
                            'fecha_salida' => $fechaSalida,
                            'id_empleado_horario' => $registro['ID_EMPLEADO_HORARIO'] ?? null,
                            'nombre_turno' => $registro['NOMBRE_TURNO'] ?? null,
                            'categoria' => $segmento['categoria'],
                            'tipo_extra' => $tipoExtraRelativo,
                            'posicion' => $segmento['posicion'] ?? null,
                            'segmentos' => [$segmento]
                        ];
                    }
                }

                $horasRegulares = 0;
                $horasExtra = $calculoJerarquico['extra_diurna'] + $calculoJerarquico['extra_nocturna'] + $calculoJerarquico['extra_diurna_dominical_festiva'] + $calculoJerarquico['extra_nocturna_dominical_festiva'];

                registrarExtrasSegmentosPorFecha($extrasCalculadasPorFecha, $calculoJerarquico['segmentos']);
            }

            // Estadísticas del día ya calculadas arriba con sistema jerárquico
            // Eliminada la sección que recalculaba con calcularHorasRegularesExtras causando inconsistencias
            
            if (!isset($horasTotalesTurno)) {
                $horasTotalesTurno = ($horasRegulares ?? 0) + ($horasExtra ?? 0);
            }
            $resultado['estadisticas']['total_horas'] += $horasTotalesTurno;
        }

        // Procesar turnos nocturnos completos (que cruzan medianoche)
        foreach ($turnosNocturnosCompletos as $turnoNocturno) {
            $registro = $turnoNocturno['registro'];
            $fechaEntrada = $turnoNocturno['fecha_entrada'];
            $fechaSalida = $turnoNocturno['fecha_salida'];
            $horaEntrada = $turnoNocturno['hora_entrada'];
            $horaSalida = $turnoNocturno['hora_salida'];
            $horasTotales = $turnoNocturno['horas_totales'];

            // Usar la fecha de entrada como fecha principal para el turno
            $fechaPrincipal = $fechaEntrada;
            $diaSemana = date('w', strtotime($fechaPrincipal));

            // Verificar si hay justificación en alguna de las fechas
            $justificacionEntrada = obtenerJustificacion($id_empleado, $fechaEntrada, $pdo);
            $justificacionSalida = obtenerJustificacion($id_empleado, $fechaSalida, $pdo);

            if ($justificacionEntrada || $justificacionSalida) {
                // Hay justificación - registrar pero con 0 horas
                $justificacion = $justificacionEntrada ?: $justificacionSalida;
                $resultado['justificaciones'][] = [
                    'fecha' => $fechaPrincipal,
                    'motivo' => $justificacion['motivo'],
                    'detalle_adicional' => $justificacion['detalle_adicional'],
                    'tipo_falta' => $justificacion['tipo_falta'],
                    'estado' => $justificacion['estado'],
                    'fecha_justificacion' => $justificacion['fecha_justificacion'],
                    'horas_programadas' => $justificacion['horas_programadas']
                ];

                $resultado['detalle_dias'][] = [
                    'fecha' => $fechaPrincipal,
                    'FECHA_COMPLETA' => $fechaEntrada . ' - ' . $fechaSalida, // Mostrar ambas fechas
                    'es_domingo' => esDomingo($fechaPrincipal),
                    'es_festivo' => esFestivo($fechaPrincipal, $pdo),
                    'es_dia_civico' => esDiaCivico($fechaPrincipal, $pdo),
                    'justificado' => true,
                    'horas_trabajadas' => 0,
                    'detalle_horas' => [],
                    'detalle_turnos' => []
                ];
                continue;
            }

            // Obtener horarios para la fecha de entrada
            $todosLosHorarios = obtenerHorariosPersonalizados($id_empleado, $fechaPrincipal, $diaSemana, $pdo);

            // Verificar si es turno nocturno programado
            $horarioPersonalizado = null;
            if (count($todosLosHorarios) === 1) {
                $horarioPersonalizado = $todosLosHorarios[0];
            }

            // Determinar características del día (usar fecha de entrada)
            $esFechaEsp = esFechaEspecial($fechaPrincipal, $pdo);

            // Usar el nuevo sistema jerárquico para turnos nocturnos completos
            if ($horarioPersonalizado) {
                $horaEntradaProg = $horarioPersonalizado['HORA_ENTRADA'];
                $horaSalidaProg = $horarioPersonalizado['HORA_SALIDA'];

                // Normalizar horas del horario programado
                $horaEntradaProgNormalizada = date('H:i:s', strtotime($horaEntradaProg));
                $horaSalidaProgNormalizada = date('H:i:s', strtotime($horaSalidaProg));

                // Calcular horas usando el sistema jerárquico
                $calculoJerarquico = calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fechaPrincipal, $horaEntradaProgNormalizada, $horaSalidaProgNormalizada, $pdo, $fechaSalida, $intervalosHorarioPorFecha);

                // Acumular en estadísticas (solo horas regulares y recargos)
                $resultado['estadisticas']['horas_regulares'] += $calculoJerarquico['horas_regulares'];
                $resultado['estadisticas']['recargo_nocturno'] += $calculoJerarquico['recargo_nocturno'];
                $resultado['estadisticas']['recargo_dominical_festivo'] += $calculoJerarquico['recargo_dominical_festivo'];
                $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $calculoJerarquico['recargo_nocturno_dominical_festivo'];

                // Crear registros de detalle para cada segmento
                $detallesTurno = [];
                foreach ($calculoJerarquico['segmentos'] as $segmento) {
                    $detallesTurno[] = [
                        'hora_entrada' => $horaEntrada,
                        'hora_salida' => $horaSalida,
                        'horas_trabajadas' => $segmento['horas'],
                        'dentro_horario' => !$segmento['es_extra'],
                        'es_nocturno' => $segmento['es_nocturno'],
                        'es_turno_nocturno_completo' => true,
                        'fecha_entrada' => $fechaEntrada,
                        'fecha_salida' => $fechaSalida,
                        'id_empleado_horario' => $registro['ID_EMPLEADO_HORARIO'] ?? null,
                        'nombre_turno' => $registro['NOMBRE_TURNO'] ?? null,
                        'categoria' => $segmento['categoria'],
                        'tipo_extra' => $segmento['es_extra'] ? 'despues' : null,
                        'segmentos' => [$segmento]
                    ];
                }

                $horasRegulares = $calculoJerarquico['horas_regulares'] + $calculoJerarquico['recargo_nocturno'] + $calculoJerarquico['recargo_dominical_festivo'] + $calculoJerarquico['recargo_nocturno_dominical_festivo'];
                $horasExtra = $calculoJerarquico['extra_diurna'] + $calculoJerarquico['extra_nocturna'] + $calculoJerarquico['extra_diurna_dominical_festiva'] + $calculoJerarquico['extra_nocturna_dominical_festiva'];

                registrarExtrasSegmentosPorFecha($extrasCalculadasPorFecha, $calculoJerarquico['segmentos']);
            } else {
                // Sin horario personalizado, todas las horas son extras usando jerarquía
                $calculoJerarquico = calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fechaPrincipal, '00:00', '00:00', $pdo, $fechaSalida, []);

                $resultado['estadisticas']['extra_diurna'] += $calculoJerarquico['extra_diurna'];
                $resultado['estadisticas']['extra_nocturna'] += $calculoJerarquico['extra_nocturna'];
                $resultado['estadisticas']['extra_diurna_dominical_festiva'] += $calculoJerarquico['extra_diurna_dominical_festiva'];
                $resultado['estadisticas']['extra_nocturna_dominical_festiva'] += $calculoJerarquico['extra_nocturna_dominical_festiva'];

                // Solo generar registros para horas extras
                $detallesTurno = [];
                foreach ($calculoJerarquico['segmentos'] as $segmento) {
                    if ($segmento['es_extra']) {
                        $tipoExtraRelativo = in_array($segmento['posicion'], ['antes', 'despues'], true)
                            ? $segmento['posicion']
                            : (in_array($segmento['posicion'], ['sin_horario', 'fuera'], true) ? 'sin_horario' : 'despues');

                        $detallesTurno[] = [
                            'hora_entrada' => $horaEntrada,
                            'hora_salida' => $horaSalida,
                            'horas_trabajadas' => $segmento['horas'],
                            'dentro_horario' => false,
                            'es_nocturno' => $segmento['es_nocturno'],
                            'es_turno_nocturno_completo' => true,
                            'fecha_entrada' => $fechaEntrada,
                            'fecha_salida' => $fechaSalida,
                            'id_empleado_horario' => $registro['ID_EMPLEADO_HORARIO'] ?? null,
                            'nombre_turno' => $registro['NOMBRE_TURNO'] ?? null,
                            'categoria' => $segmento['categoria'],
                            'tipo_extra' => $tipoExtraRelativo,
                            'posicion' => $segmento['posicion'] ?? null,
                            'segmentos' => [$segmento]
                        ];
                    }
                }

                $horasRegulares = 0;
                $horasExtra = $calculoJerarquico['extra_diurna'] + $calculoJerarquico['extra_nocturna'] + $calculoJerarquico['extra_diurna_dominical_festiva'] + $calculoJerarquico['extra_nocturna_dominical_festiva'];
                $horasTotalesTurno = $horasRegulares + $horasExtra;

                registrarExtrasSegmentosPorFecha($extrasCalculadasPorFecha, $calculoJerarquico['segmentos']);
            }

            $horarioResumenNocturno = null;
            $horarioEntradaBaseNoct = $horarioPersonalizado['HORA_ENTRADA'] ?? ($registro['HORARIO_ENTRADA'] ?? null);
            $horarioSalidaBaseNoct = $horarioPersonalizado['HORA_SALIDA'] ?? ($registro['HORARIO_SALIDA'] ?? null);

            if ($horarioEntradaBaseNoct && $horarioSalidaBaseNoct) {
                $horarioResumenNocturno = substr($horarioEntradaBaseNoct, 0, 5) . ' - ' . substr($horarioSalidaBaseNoct, 0, 5);
            } elseif ($horarioEntradaBaseNoct) {
                $horarioResumenNocturno = substr($horarioEntradaBaseNoct, 0, 5);
            }

            $nombreTurnoNoct = $horarioPersonalizado['NOMBRE_TURNO'] ?? ($registro['NOMBRE_TURNO'] ?? null);
            if ($horarioResumenNocturno && $nombreTurnoNoct) {
                $horarioResumenNocturno .= ' (' . $nombreTurnoNoct . ')';
            } elseif (!$horarioResumenNocturno && $nombreTurnoNoct) {
                $horarioResumenNocturno = $nombreTurnoNoct;
            }

            if (!empty($horarioPersonalizado['ES_TEMPORAL']) && $horarioPersonalizado['ES_TEMPORAL'] === 'S') {
                $horarioResumenNocturno = trim(($horarioResumenNocturno ?? '') . ' [Temporal]');
            }

            $turnosDetalladosNocturno = [[
                'id_asistencia_entrada' => $registro['ID_ASISTENCIA'] ?? null,
                'id_asistencia_salida' => $registro['SALIDA_ID_ASISTENCIA'] ?? null,
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida' => $fechaSalida,
                'hora_entrada' => $horaEntrada,
                'hora_salida' => $horaSalida,
                'horario_resumen' => $horarioResumenNocturno,
                'nombre_turno' => $nombreTurnoNoct,
                'id_empleado_horario' => $registro['ID_EMPLEADO_HORARIO'] ?? null,
                'id_horario_tradicional' => $registro['ID_HORARIO_TRADICIONAL'] ?? null,
                'clasificacion' => [
                    'horas_regulares' => $calculoJerarquico['horas_regulares'],
                    'recargo_nocturno' => $calculoJerarquico['recargo_nocturno'],
                    'recargo_dominical_festivo' => $calculoJerarquico['recargo_dominical_festivo'],
                    'recargo_nocturno_dominical_festivo' => $calculoJerarquico['recargo_nocturno_dominical_festivo'],
                    'extra_diurna' => $calculoJerarquico['extra_diurna'],
                    'extra_nocturna' => $calculoJerarquico['extra_nocturna'],
                    'extra_diurna_dominical_festiva' => $calculoJerarquico['extra_diurna_dominical_festiva'],
                    'extra_nocturna_dominical_festiva' => $calculoJerarquico['extra_nocturna_dominical_festiva']
                ],
                'horas_totales' => $horasTotales,
                'horas_regulares_sumadas' => $horasRegulares,
                'horas_extras_sumadas' => $horasExtra,
                'segmentos' => $calculoJerarquico['segmentos'],
                'justificado' => false,
                'observaciones' => [
                    'entrada' => $registro['OBSERVACION_ENTRADA'] ?? null,
                    'salida' => $registro['OBSERVACION_SALIDA'] ?? null
                ],
                'tardanza' => [
                    'entrada' => $registro['TARDANZA_ENTRADA'] ?? null,
                    'salida' => $registro['TARDANZA_SALIDA'] ?? null
                ],
                'registro_manual' => [
                    'entrada' => $registro['REGISTRO_MANUAL_ENTRADA'] ?? null,
                    'salida' => $registro['REGISTRO_MANUAL_SALIDA'] ?? null
                ],
                'fotos' => [
                    'entrada' => $registro['FOTO_ENTRADA'] ?? null,
                    'salida' => $registro['FOTO_SALIDA'] ?? null
                ],
                'es_turno_nocturno' => true
            ]];

            // Calcular estadísticas del día basadas en los segmentos procesados
            $estadisticasDiaNocturno = [
                'horas_regulares' => $calculoJerarquico['horas_regulares'],
                'recargo_nocturno' => $calculoJerarquico['recargo_nocturno'],
                'recargo_dominical_festivo' => $calculoJerarquico['recargo_dominical_festivo'],
                'recargo_nocturno_dominical_festivo' => $calculoJerarquico['recargo_nocturno_dominical_festivo'],
                'extra_diurna' => $calculoJerarquico['extra_diurna'],
                'extra_nocturna' => $calculoJerarquico['extra_nocturna'],
                'extra_diurna_dominical_festiva' => $calculoJerarquico['extra_diurna_dominical_festiva'],
                'extra_nocturna_dominical_festiva' => $calculoJerarquico['extra_nocturna_dominical_festiva'],
                'total_horas' => $horasTotales
            ];

            // Agregar información del turno nocturno completo a detalle_dias
            $resultado['detalle_dias'][] = [
                'fecha' => $fechaPrincipal,
                'FECHA_COMPLETA' => $fechaEntrada . ' - ' . $fechaSalida, // Mostrar ambas fechas
                'es_domingo' => esDomingo($fechaPrincipal),
                'es_festivo' => esFestivo($fechaPrincipal, $pdo),
                'es_dia_civico' => esDiaCivico($fechaPrincipal, $pdo),
                'justificado' => false,
                'horas_trabajadas' => $horasTotales,
                'detalle_horas' => $detallesTurno,
                'detalle_turnos' => $turnosDetalladosNocturno,
                // Agregar estadísticas calculadas del día
                'horas_regulares' => round($estadisticasDiaNocturno['horas_regulares'], 2),
                'recargo_nocturno' => round($estadisticasDiaNocturno['recargo_nocturno'], 2),
                'recargo_dominical_festivo' => round($estadisticasDiaNocturno['recargo_dominical_festivo'], 2),
                'recargo_nocturno_dominical_festivo' => round($estadisticasDiaNocturno['recargo_nocturno_dominical_festivo'], 2),
                'extra_diurna' => round($estadisticasDiaNocturno['extra_diurna'], 2),
                'extra_nocturna' => round($estadisticasDiaNocturno['extra_nocturna'], 2),
                'extra_diurna_dominical_festiva' => round($estadisticasDiaNocturno['extra_diurna_dominical_festiva'], 2),
                'extra_nocturna_dominical_festiva' => round($estadisticasDiaNocturno['extra_nocturna_dominical_festiva'], 2),
                'total_horas' => round($estadisticasDiaNocturno['total_horas'], 2)
            ];

            // Generar horas extras por cada segmento fuera del horario
            if (!empty($detallesTurno)) {
                $idEmpleadoHorario = $horarioPersonalizado ? $horarioPersonalizado['ID_EMPLEADO_HORARIO'] : ($registro['ID_EMPLEADO_HORARIO'] ?? null);

                foreach ($detallesTurno as $detalleExtra) {
                    if (!empty($detalleExtra['dentro_horario'])) {
                        continue;
                    }

                    $fechaExtra = $detalleExtra['fecha_segmento'] ?? $fechaPrincipal;
                    $horaInicioExtra = $detalleExtra['hora_entrada'] ?? $horaEntrada;
                    $horaFinExtra = $detalleExtra['hora_salida'] ?? $horaSalida;
                    $horasExtras = $detalleExtra['horas_trabajadas'] ?? 0;
                    if ($horasExtras <= 0) {
                        continue;
                    }

                    $categoria = $detalleExtra['categoria'] ?? 'nocturna';
                    $tipoHorario = 'nocturna';
                    if (strpos($categoria, 'festiva') !== false || strpos($categoria, 'dominical') !== false) {
                        $tipoHorario = strpos($categoria, 'nocturna') !== false ? 'nocturna_dominical' : 'diurna_dominical';
                    } elseif (strpos($categoria, 'diurna') !== false) {
                        $tipoHorario = 'diurna';
                    }

                    $tipoExtraGenerado = $detalleExtra['tipo_extra']
                        ?? ($detalleExtra['posicion'] ?? null);

                    if ($tipoExtraGenerado === null) {
                        $tipoExtraGenerado = 'despues';
                    }

                    // Asegurar que tipoExtraGenerado sea válido para el enum
                    if ($tipoExtraGenerado !== 'antes' && $tipoExtraGenerado !== 'despues') {
                        $tipoExtraGenerado = 'despues'; // valor por defecto válido
                    }

                    generarHorasExtrasSiNoExisten($id_empleado, $idEmpleadoHorario, $fechaExtra, $horaInicioExtra, $horaFinExtra, $horasExtras, $tipoExtraGenerado, $tipoHorario, $pdo);
                }
            }
        }

        // Obtener TODAS las horas extras para mostrar estados en la UI
        $todasHorasExtras = obtenerTodasHorasExtras($id_empleado, $fecha_inicio, $fecha_fin, $pdo);

        // Agregar información de horas extras al resultado para mostrar en UI
        $resultado['horas_extras_por_fecha'] = $todasHorasExtras;

        // Obtener horas extras aprobadas para este empleado (solo para estadísticas)
        $horasExtrasAprobadas = obtenerHorasExtrasAprobadas($id_empleado, $fecha_inicio, $fecha_fin, $pdo);

        $extrasPendientesPorFecha = $extrasCalculadasPorFecha;

        // Agregar horas extras aprobadas a las estadísticas (solo diferencias no calculadas previamente)
        foreach ($horasExtrasAprobadas as $fecha => $extrasFecha) {
            foreach ($extrasFecha as $extra) {
                $horas = $extra['horas_extras'];
                $tipoHorario = $extra['tipo_horario'];

                $claveEstadistica = null;
                switch ($tipoHorario) {
                    case 'nocturna_dominical':
                    case 'nocturna_dominical_festiva':
                        $claveEstadistica = 'extra_nocturna_dominical_festiva';
                        break;
                    case 'diurna_dominical':
                    case 'diurna_dominical_festiva':
                        $claveEstadistica = 'extra_diurna_dominical_festiva';
                        break;
                    case 'nocturna':
                        $claveEstadistica = 'extra_nocturna';
                        break;
                    case 'diurna':
                        $claveEstadistica = 'extra_diurna';
                        break;
                    default:
                        $claveEstadistica = null;
                        break;
                }

                if ($claveEstadistica === null) {
                    continue;
                }

                if (!isset($extrasPendientesPorFecha[$fecha])) {
                    $extrasPendientesPorFecha[$fecha] = [
                        'extra_diurna' => 0,
                        'extra_nocturna' => 0,
                        'extra_diurna_dominical_festiva' => 0,
                        'extra_nocturna_dominical_festiva' => 0
                    ];
                }

                $horasPendientes = $extrasPendientesPorFecha[$fecha][$claveEstadistica] ?? 0;

                if ($horasPendientes >= $horas) {
                    $extrasPendientesPorFecha[$fecha][$claveEstadistica] -= $horas;
                    continue;
                }

                $horasAAgregar = $horas - max(0, $horasPendientes);
                $extrasPendientesPorFecha[$fecha][$claveEstadistica] = 0;

                if ($horasAAgregar <= 0) {
                    continue;
                }

                $resultado['estadisticas'][$claveEstadistica] += $horasAAgregar;
                $resultado['estadisticas']['total_horas'] += $horasAAgregar;
            }
        }

        // Obtener estados de aprobación de horas extras para poblar los campos de estado
        $estadosHorasExtras = obtenerEstadosAprobacionHorasExtras($id_empleado, $fecha_inicio, $fecha_fin, $pdo);

        // Poblar los campos de estado en cada día del detalle
        foreach ($resultado['detalle_dias'] as &$dia) {
            $fecha = $dia['fecha'];
            $dia['extra_diurna_estado'] = $estadosHorasExtras[$fecha]['extra_diurna_estado'] ?? null;
            $dia['extra_nocturna_estado'] = $estadosHorasExtras[$fecha]['extra_nocturna_estado'] ?? null;
            $dia['extra_diurna_dominical_estado'] = $estadosHorasExtras[$fecha]['extra_diurna_dominical_estado'] ?? null;
            $dia['extra_nocturna_dominical_estado'] = $estadosHorasExtras[$fecha]['extra_nocturna_dominical_estado'] ?? null;
        }

        // Redondear valores a 2 decimales
        foreach ($resultado['estadisticas'] as $key => $value) {
            $resultado['estadisticas'][$key] = round($value, 2);
        }

        return $resultado;
    }

    // ===============================
    // CONSULTA PRINCIPAL
    // ===============================
    
    if ($multipleEmployees) {
        // Procesar múltiples empleados
        $estadisticasAgregadas = [
            'recargo_nocturno' => 0,
            'recargo_dominical_festivo' => 0,
            'recargo_nocturno_dominical_festivo' => 0,
            'extra_diurna' => 0,
            'extra_nocturna' => 0,
            'extra_diurna_dominical_festiva' => 0,
            'extra_nocturna_dominical_festiva' => 0,
            'horas_regulares' => 0,
            'total_horas' => 0
        ];
        
        $detalleEmpleados = [];
        
        // Usar la función optimizada para obtener datos de todos los empleados en una sola consulta
        $empleadosData = getAsistenciaDataOptimizada($empleados, $fecha_inicio, $fecha_fin, $pdo);
        
        foreach ($empleados as $empleado_id) {
            if (empty($empleado_id)) continue;

        // Obtener registros del empleado desde los datos optimizados
            $registros = isset($empleadosData[$empleado_id]) ? $empleadosData[$empleado_id] : [];
            
            // Procesar este empleado
            $resultadoEmpleado = procesarEmpleado($empleado_id, $registros, $fecha_inicio, $fecha_fin, $pdo);
            
            // Agregar a las estadísticas totales
            foreach ($resultadoEmpleado['estadisticas'] as $key => $value) {
                if (isset($estadisticasAgregadas[$key])) {
                    $estadisticasAgregadas[$key] += $value;
                }
            }
            
            $detalleEmpleados[] = $resultadoEmpleado;
        }
        
        // Redondear valores agregados
        foreach ($estadisticasAgregadas as $key => $value) {
            $estadisticasAgregadas[$key] = round($value, 2);
        }
        
        // Crear datos combinados para la respuesta
        $horasCombinadas = [];
        $justificacionesCombinadas = [];
        $horasExtrasCombinadas = [];
        
        foreach ($detalleEmpleados as $empleado) {
            // Agregar nombre del empleado a cada día
            foreach ($empleado['detalle_dias'] as $dia) {
                $dia['empleado_id'] = $empleado['empleado_id'];
                $dia['empleado_nombre'] = $empleado['empleado_nombre'];
                $dia['empleado_apellido'] = $empleado['empleado_apellido'];
                $horasCombinadas[] = $dia;
            }
            
            // Agregar justificaciones
            foreach ($empleado['justificaciones'] as $justificacion) {
                $justificacion['empleado_id'] = $empleado['empleado_id'];
                $justificacion['empleado_nombre'] = $empleado['empleado_nombre'];
                $justificacion['empleado_apellido'] = $empleado['empleado_apellido'];
                $justificacionesCombinadas[] = $justificacion;
            }
            
            // Combinar horas extras por empleado
            if (isset($empleado['horas_extras_por_fecha'])) {
                foreach ($empleado['horas_extras_por_fecha'] as $fecha => $extras) {
                    if (!isset($horasExtrasCombinadas[$fecha])) {
                        $horasExtrasCombinadas[$fecha] = [];
                    }
                    // Agregar ID del empleado a cada hora extra para identificarlas
                    foreach ($extras as $extra) {
                        $extra['empleado_id'] = $empleado['empleado_id'];
                        $horasExtrasCombinadas[$fecha][] = $extra;
                    }
                }
            }
        }
        
        // Respuesta para múltiples empleados (compatible con JavaScript)
        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => $estadisticasAgregadas,
                'horas' => $horasCombinadas,
                'horas_extras_por_fecha' => $horasExtrasCombinadas,
                'justificaciones' => $justificacionesCombinadas,
                'periodo' => [
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin
                ]
            ],
            'debug_messages' => $debug_messages
        ], JSON_PRETTY_PRINT);
        
    } else {
        // Procesamiento individual (compatibilidad)
        $id_empleado = $empleados[0];

        // Usar nueva lógica de asociación entrada-salida
        $registros = getAsistenciaData($id_empleado, $fecha_inicio, $fecha_fin, $pdo);
        
        // Procesar empleado individual
        $resultado = procesarEmpleado($id_empleado, $registros, $fecha_inicio, $fecha_fin, $pdo);
        
        // Formato compatible para empleado individual
        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => $resultado['estadisticas'],
                'horas' => $resultado['detalle_dias'],
                'horas_extras_por_fecha' => $resultado['horas_extras_por_fecha'] ?? [],
                'justificaciones' => $resultado['justificaciones'],
                'periodo' => $resultado['periodo']
            ]
        ], JSON_PRETTY_PRINT);
    }


/**
 * Procesa registros de un turno nocturno con lógica especial
 */
function procesarTurnoNocturno($registrosDia, $horaCorteNocturno, $fecha, $pdo, &$resultado, $horarioPersonalizado, $empleado_id) {
    $horasDelDia = 0;
    $fechaMostrar = $fecha; // Por defecto usar la fecha original
    $esFechaEsp = esFechaEspecial($fecha, $pdo);
    
    // Si no hay registros o solo hay uno, usar lógica mejorada
    if (count($registrosDia) <= 1) {
        // Si hay exactamente un registro y es de SALIDA antes del corte nocturno
        // buscar registro de ENTRADA del día anterior
        if (count($registrosDia) == 1) {
            $registro = $registrosDia[0];
            $hora = $registro['HORA'];
            $tipo = $registro['TIPO'] ?? '';
            
            // Si es un registro de salida antes del corte nocturno, buscar entrada del día anterior
            if ($tipo === 'SALIDA' && $hora <= $horaCorteNocturno) {
                $fechaAnterior = date('Y-m-d', strtotime($fecha . ' -1 day'));
                
                // Buscar registro de entrada del día anterior
                $stmt = $pdo->prepare("
                    SELECT HORA, TIPO 
                    FROM asistencia 
                    WHERE ID_EMPLEADO = ? 
                    AND FECHA = ? 
                    AND TIPO = 'ENTRADA' 
                    ORDER BY HORA DESC 
                    LIMIT 1
                ");
                $stmt->execute([$empleado_id, $fechaAnterior]);
                $registroEntrada = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($registroEntrada) {
                    $horaEntrada = $registroEntrada['HORA'];
                    $horaSalida = $registro['HORA'];
                    
                    // Verificar si es realmente un turno nocturno
                    if (esTurnoNocturnoMejorado(null, $horaEntrada, $horaSalida)) {
                        $horasNocturnas = calcularHorasNocturnas($horaEntrada, $horaSalida, $horaCorteNocturno);
                        
                        if ($horasNocturnas > 0) {
                            $horasDelDia += $horasNocturnas;
                            
                            // Clasificar según tipo de fecha (usar fecha de entrada para clasificación dominical)
                            if ($esFechaEsp || esDomingo($fechaAnterior)) {
                                $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $horasNocturnas;
                            } else {
                                $resultado['estadisticas']['recargo_nocturno'] += $horasNocturnas;
                            }
                            
                            // Usar fecha anterior (fecha de entrada) para mostrar en la tabla
                            $fechaMostrar = $fechaAnterior;
                            return [
                                'horas' => $horasDelDia, 
                                'fecha_mostrar' => $fechaMostrar,
                                'entrada_real' => $horaEntrada,
                                'salida_real' => $horaSalida
                            ];
                        }
                    }
                }
            }
        }
        
        // Si no se pudo procesar con la nueva lógica, usar lógica antigua
        foreach ($registrosDia as $registro) {
            $hora = $registro['HORA'];
            
            // Si es antes de la hora de corte (ej: 06:00), calcular horas desde medianoche
            if ($hora <= $horaCorteNocturno) {
                // Calcular horas desde medianoche hasta la hora registrada
                $horasTrabajadas = calcularHorasDesdeMedianoche($hora, $horaCorteNocturno);
                
                if ($horasTrabajadas > 0) {
                    $horasDelDia += $horasTrabajadas;
                    
                    // Clasificar como horas nocturnas
                    if ($esFechaEsp) {
                        $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $horasTrabajadas;
                    } else {
                        $resultado['estadisticas']['recargo_nocturno'] += $horasTrabajadas;
                    }
                }
            }
        }
        return ['horas' => $horasDelDia, 'fecha_mostrar' => $fechaMostrar];
    }
    
    // Nueva lógica para turnos con entrada y salida
    $primerRegistro = $registrosDia[0];
    $ultimoRegistro = end($registrosDia);
    
    $horaEntrada = $primerRegistro['HORA'];
    $horaSalida = $ultimoRegistro['HORA'];
    
    // Verificar si es realmente un turno nocturno (salida < entrada = cruza medianoche)
    if (esTurnoNocturnoMejorado(null, $horaEntrada, $horaSalida)) {
        // Usar nueva función para calcular horas nocturnas
        $horasNocturnas = calcularHorasNocturnas($horaEntrada, $horaSalida, $horaCorteNocturno);
        
        if ($horasNocturnas > 0) {
            $horasDelDia += $horasNocturnas;
            
            // Clasificar según tipo de fecha
            if ($esFechaEsp || esDomingo($fecha)) {
                $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $horasNocturnas;
            } else {
                $resultado['estadisticas']['recargo_nocturno'] += $horasNocturnas;
            }
        }
    } else {
        // No es turno nocturno real, usar lógica normal pero clasificar como nocturno por configuración
        $inicio = new DateTime($horaEntrada);
        $fin = new DateTime($horaSalida);
        if ($fin < $inicio) {
            $fin->add(new DateInterval('P1D'));
        }
        $diferencia = $inicio->diff($fin);
        $horas = $diferencia->h + ($diferencia->i / 60);
        
        if ($horas > 0) {
            $horasDelDia += $horas;
            
            // Clasificar según tipo de fecha
            if ($esFechaEsp || esDomingo($fecha)) {
                $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $horas;
            } else {
                $resultado['estadisticas']['recargo_nocturno'] += $horas;
            }
        }
    }
    
    return [
        'horas' => $horasDelDia, 
        'fecha_mostrar' => $fechaMostrar,
        'entrada_real' => null,
        'salida_real' => null
    ];
}

/**
 * Calcula horas trabajadas desde medianoche hasta una hora específica
 */
function calcularHorasDesdeMedianoche($horaRegistro, $horaCorte) {
    // Si la hora registrada es mayor que la hora de corte, no cuenta
    if ($horaRegistro > $horaCorte) {
        return 0;
    }
    
    // Convertir horas a timestamp para cálculo
    $medianoche = strtotime('00:00:00');
    $horaRegistroTime = strtotime($horaRegistro);
    
    // Calcular diferencia en horas
    $diferencia = $horaRegistroTime - $medianoche;
    $horas = $diferencia / 3600; // Convertir segundos a horas
    
    return round($horas, 2);
}

/**
 * Divide las horas trabajadas de un turno nocturno entre los dos días
 * Devuelve array con horas para cada fecha
 */
function dividirHorasTurnoNocturno($horaEntrada, $horaSalida, $fechaEntrada) {
    // Solo dividir si realmente cruza medianoche
    if ($horaSalida >= $horaEntrada) {
        return [
            $fechaEntrada => [
                'horas' => calcularDiferenciaHoras($horaEntrada, $horaSalida),
                'hora_inicio' => $horaEntrada,
                'hora_fin' => $horaSalida
            ]
        ];
    }
    
    // Calcular horas del día de entrada (desde entrada hasta medianoche)
    $medianoche = '23:59:59';
    $horasDiaEntrada = calcularDiferenciaHoras($horaEntrada, $medianoche);
    
    // Calcular horas del día siguiente (desde medianoche hasta salida)
    $fechaSalida = date('Y-m-d', strtotime($fechaEntrada . ' +1 day'));
    $horasDiaSalida = calcularDiferenciaHoras('00:00:00', $horaSalida);
    
    return [
        $fechaEntrada => [
            'horas' => $horasDiaEntrada,
            'hora_inicio' => $horaEntrada,
            'hora_fin' => $medianoche
        ],
        $fechaSalida => [
            'horas' => $horasDiaSalida,
            'hora_inicio' => '00:00:00',
            'hora_fin' => $horaSalida
        ]
    ];
}

/**
 * Verifica si ya existen horas extras para un empleado en una fecha específica
 */
/**
 * Verifica si ya existen horas extras para un empleado en una fecha específica con el mismo tipo de horario
 * Más permisiva: permite múltiples registros si son de diferentes tipos o rangos
 */
function verificarHorasExtrasExistentes($idEmpleado, $fecha, $horaInicio, $horaFin, $tipoHorario, $pdo) {
    // Verificar si ya existen horas extras del mismo tipo para esta fecha
    // Permitir múltiples registros si son de diferentes tipos o rangos de horas
    $query = "
        SELECT COUNT(*) as count
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND FECHA = ?
        AND TIPO_HORARIO = ?
        AND ABS(TIMESTAMPDIFF(MINUTE, HORA_INICIO, ?)) < 60
        AND ABS(TIMESTAMPDIFF(MINUTE, HORA_FIN, ?)) < 60
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$idEmpleado, $fecha, $tipoHorario, $horaInicio, $horaFin]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

/**
 * Verifica si ya existen horas extras similares para evitar duplicados
 */
function verificarHorasExtrasDuplicadas($idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $tipoHorario, $tipoExtra, $pdo) {
    // Verificar si ya existen horas extras para el mismo empleado, fecha, rango de horas Y tipo de horario
    // Esto permite múltiples tipos de horas extras (diurna/nocturna) para el mismo período
    $query = "
        SELECT COUNT(*) as count
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND FECHA = ?
        AND (
            (ID_EMPLEADO_HORARIO IS NULL AND ? IS NULL) OR
            (ID_EMPLEADO_HORARIO = ?)
        )
        AND HORA_INICIO = ?
        AND HORA_FIN = ?
    AND TIPO_HORARIO = ?
    AND TIPO_EXTRA = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $idEmpleado,
        $fecha,
        $idEmpleadoHorario,
        $idEmpleadoHorario,
        $horaInicio,
        $horaFin,
    $tipoHorario,
    $tipoExtra
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

/**
 * Genera horas extras para aprobación solo si no existen duplicadas
 */
function generarHorasExtrasSiNoExisten($idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $horasExtras, $tipoExtra, $tipoHorario, $pdo) {
    // Verificar si ya existen horas extras similares
    if (verificarHorasExtrasDuplicadas($idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $tipoHorario, $tipoExtra, $pdo)) {
        // Ya existen horas extras similares, no generar nuevas
        return false;
    }

    // Generar nuevas horas extras para aprobación
    $query = "
        INSERT INTO horas_extras_aprobacion
        (ID_EMPLEADO, ID_EMPLEADO_HORARIO, FECHA, HORA_INICIO, HORA_FIN, HORAS_EXTRAS, TIPO_EXTRA, TIPO_HORARIO, ESTADO_APROBACION, CREATED_AT, UPDATED_AT)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW(), NOW())
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $horasExtras, $tipoExtra, $tipoHorario]);
        return true; // Se generaron horas extras
    } catch (Exception $e) {
        // Si hay error de duplicado (unique constraint), es normal - ya existe
        if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'duplicate key') !== false) {
            return false; // Ya existe, no se generó nuevo
        }
        error_log("Error al generar horas extras: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene TODAS las horas extras (pendientes, aprobadas, rechazadas) para un empleado en un rango de fechas
 * Filtra duplicados basados en horas de inicio/fin para evitar mostrar el mismo registro múltiples veces
 */
function obtenerTodasHorasExtras($idEmpleado, $fechaInicio, $fechaFin, $pdo) {
    $query = "
        SELECT
            MIN(ID_HORAS_EXTRAS) as ID_HORAS_EXTRAS,
            MAX(ID_EMPLEADO_HORARIO) as ID_EMPLEADO_HORARIO,
            FECHA,
            HORA_INICIO,
            HORA_FIN,
            HORAS_EXTRAS,
            TIPO_EXTRA,
            TIPO_HORARIO,
            ESTADO_APROBACION,
            OBSERVACIONES
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND FECHA BETWEEN ? AND ?
        GROUP BY FECHA, HORA_INICIO, HORA_FIN, HORAS_EXTRAS
        ORDER BY FECHA ASC, HORA_INICIO ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$idEmpleado, $fechaInicio, $fechaFin]);

    $horasExtras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by date
    $horasPorFecha = [];
    foreach ($horasExtras as $horaExtra) {
        $fecha = $horaExtra['FECHA'];
        if (!isset($horasPorFecha[$fecha])) {
            $horasPorFecha[$fecha] = [];
        }

        $horasPorFecha[$fecha][] = [
            'id' => $horaExtra['ID_HORAS_EXTRAS'],
            'empleado_id' => $idEmpleado,
            'id_empleado_horario' => $horaExtra['ID_EMPLEADO_HORARIO'] ?? null,
            'hora_inicio' => $horaExtra['HORA_INICIO'],
            'hora_fin' => $horaExtra['HORA_FIN'],
            'horas_extras' => floatval($horaExtra['HORAS_EXTRAS']),
            'tipo_extra' => $horaExtra['TIPO_EXTRA'],
            'posicion' => $horaExtra['TIPO_EXTRA'],
            'tipo_horario' => $horaExtra['TIPO_HORARIO'],
            'estado_aprobacion' => $horaExtra['ESTADO_APROBACION'],
            'observaciones' => $horaExtra['OBSERVACIONES']
        ];
    }

    return $horasPorFecha;
}

/**
 * Obtiene los estados de aprobación de horas extras por fecha y tipo
 */
function obtenerEstadosAprobacionHorasExtras($idEmpleado, $fechaInicio, $fechaFin, $pdo) {
    $query = "
        SELECT
            FECHA,
            TIPO_HORARIO,
            ESTADO_APROBACION
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND FECHA BETWEEN ? AND ?
        AND ESTADO_APROBACION IS NOT NULL
        ORDER BY FECHA ASC, TIPO_HORARIO ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$idEmpleado, $fechaInicio, $fechaFin]);

    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar por fecha y tipo de horario
    $estadosPorFecha = [];
    foreach ($estados as $estado) {
        $fecha = $estado['FECHA'];
        $tipoHorario = $estado['TIPO_HORARIO'];
        $estadoAprobacion = $estado['ESTADO_APROBACION'];

        if (!isset($estadosPorFecha[$fecha])) {
            $estadosPorFecha[$fecha] = [
                'extra_diurna_estado' => null,
                'extra_nocturna_estado' => null,
                'extra_diurna_dominical_estado' => null,
                'extra_nocturna_dominical_estado' => null
            ];
        }

        // Mapear tipo de horario a campo de estado
        switch ($tipoHorario) {
            case 'diurna':
                $estadosPorFecha[$fecha]['extra_diurna_estado'] = $estadoAprobacion;
                break;
            case 'nocturna':
                $estadosPorFecha[$fecha]['extra_nocturna_estado'] = $estadoAprobacion;
                break;
            case 'diurna_dominical':
            case 'diurna_dominical_festiva':
                $estadosPorFecha[$fecha]['extra_diurna_dominical_estado'] = $estadoAprobacion;
                break;
            case 'nocturna_dominical':
            case 'nocturna_dominical_festiva':
                $estadosPorFecha[$fecha]['extra_nocturna_dominical_estado'] = $estadoAprobacion;
                break;
        }
    }

    return $estadosPorFecha;
}

/**
 * Obtiene horas extras aprobadas para un empleado en un rango de fechas
 */
function obtenerHorasExtrasAprobadas($idEmpleado, $fechaInicio, $fechaFin, $pdo) {
    $query = "
        SELECT
            FECHA,
            HORA_INICIO,
            HORA_FIN,
            HORAS_EXTRAS,
            TIPO_EXTRA,
            TIPO_HORARIO,
            ID_EMPLEADO_HORARIO
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND ESTADO_APROBACION = 'aprobada'
        AND ESTADO_APROBACION IS NOT NULL
        AND FECHA BETWEEN ? AND ?
        ORDER BY FECHA ASC, HORA_INICIO ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$idEmpleado, $fechaInicio, $fechaFin]);

    $horasExtras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by date
    $horasPorFecha = [];
    foreach ($horasExtras as $horaExtra) {
        $fecha = $horaExtra['FECHA'];
        if (!isset($horasPorFecha[$fecha])) {
            $horasPorFecha[$fecha] = [];
        }

        $horasPorFecha[$fecha][] = [
            'hora_inicio' => $horaExtra['HORA_INICIO'],
            'hora_fin' => $horaExtra['HORA_FIN'],
            'horas_extras' => floatval($horaExtra['HORAS_EXTRAS']),
            'tipo_extra' => $horaExtra['TIPO_EXTRA'],
            'tipo_horario' => $horaExtra['TIPO_HORARIO'],
            'id_empleado_horario' => $horaExtra['ID_EMPLEADO_HORARIO'] ?? null,
            'posicion' => $horaExtra['TIPO_EXTRA']
        ];
    }

    return $horasPorFecha;
}

/**
 * Calcula horas trabajadas con jerarquía de prioridades (8 niveles)
 * Recorre desde la hora de salida hacia la hora de entrada (counter-clockwise)
 */
function calculateHoursWithHierarchy($horaEntrada, $horaSalida, $fechaEntrada, $horaEntradaProg, $horaSalidaProg, $pdo, $fechaSalida = null, array $intervalosHorarioPorFecha = []) {
    $resultado = [
        'horas_regulares' => 0,
        'recargo_nocturno' => 0,
        'recargo_dominical_festivo' => 0,
        'recargo_nocturno_dominical_festivo' => 0,
        'extra_diurna' => 0,
        'extra_nocturna' => 0,
        'extra_diurna_dominical_festiva' => 0,
        'extra_nocturna_dominical_festiva' => 0,
        'segmentos' => []
    ];

    if (!$horaEntrada || !$horaSalida) {
        return $resultado;
    }

    $tz = new DateTimeZone('America/Bogota');

    $entradaDT = crearDateTimeSeguro($fechaEntrada, $horaEntrada, $tz);
    $fechaSalidaReal = $fechaSalida ?: $fechaEntrada;
    $salidaDT = crearDateTimeSeguro($fechaSalidaReal, $horaSalida, $tz);

    if ($salidaDT <= $entradaDT) {
        $salidaDT = $salidaDT->modify('+1 day');
    }

    $horaEntradaProgNorm = normalizarHora($horaEntradaProg);
    $horaSalidaProgNorm = normalizarHora($horaSalidaProg);

    $scheduleWindows = [];
    $agregarVentana = function (DateTimeImmutable $inicio, DateTimeImmutable $fin) use (&$scheduleWindows) {
        if ($fin <= $inicio) {
            $fin = $fin->modify('+1 day');
        }

        $scheduleWindows[] = [
            'start' => $inicio->getTimestamp(),
            'end' => $fin->getTimestamp()
        ];
    };

    foreach ($intervalosHorarioPorFecha as $fechaClave => $intervalosDia) {
        foreach ($intervalosDia as $intervalo) {
            $inicio = $intervalo['inicio'] ?? null;
            $fin = $intervalo['fin'] ?? null;

            if ($inicio === null || $fin === null) {
                continue;
            }

            if (!($inicio instanceof DateTimeInterface)) {
                if (is_numeric($inicio)) {
                    $inicio = (new DateTimeImmutable('@' . (int)$inicio))->setTimezone($tz);
                } else {
                    $horaInicioNormalizada = normalizarHora($inicio);
                    if ($horaInicioNormalizada === null) {
                        continue;
                    }
                    $inicio = crearDateTimeSeguro($fechaClave, $horaInicioNormalizada, $tz);
                }
            }

            if (!($fin instanceof DateTimeInterface)) {
                if (is_numeric($fin)) {
                    $fin = (new DateTimeImmutable('@' . (int)$fin))->setTimezone($tz);
                } else {
                    $horaFinNormalizada = normalizarHora($fin);
                    if ($horaFinNormalizada === null) {
                        continue;
                    }
                    $fin = crearDateTimeSeguro($fechaClave, $horaFinNormalizada, $tz);
                }
            }

            if ($inicio instanceof DateTimeInterface && $fin instanceof DateTimeInterface) {
                $agregarVentana($inicio, $fin);
            }
        }
    }

    if (empty($scheduleWindows) && $horaEntradaProgNorm !== null && $horaSalidaProgNorm !== null && !($horaEntradaProgNorm === '00:00:00' && $horaSalidaProgNorm === '00:00:00')) {
        $inicioProg = crearDateTimeSeguro($fechaEntrada, $horaEntradaProgNorm, $tz);
        $finProg = crearDateTimeSeguro($fechaEntrada, $horaSalidaProgNorm, $tz);
        $agregarVentana($inicioProg, $finProg);
    }

    if (!empty($scheduleWindows)) {
        usort($scheduleWindows, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $ventanasFusionadas = [];
        foreach ($scheduleWindows as $ventana) {
            if (empty($ventanasFusionadas)) {
                $ventanasFusionadas[] = $ventana;
                continue;
            }

            $ultimoIndice = count($ventanasFusionadas) - 1;
            $ultimaVentana = $ventanasFusionadas[$ultimoIndice];

            if ($ventana['start'] <= $ultimaVentana['end']) {
                if ($ventana['end'] > $ultimaVentana['end']) {
                    $ventanasFusionadas[$ultimoIndice]['end'] = $ventana['end'];
                }
            } else {
                $ventanasFusionadas[] = $ventana;
            }
        }

        $scheduleWindows = $ventanasFusionadas;
    }

    $firstScheduleTs = null;
    $lastScheduleTs = null;
    if (!empty($scheduleWindows)) {
        $firstScheduleTs = $scheduleWindows[0]['start'];
        $lastScheduleTs = $scheduleWindows[count($scheduleWindows) - 1]['end'];
    }

    $segmentosProcesados = [];
    $segmentoActual = null;

    $entradaTs = $entradaDT->getTimestamp();
    $salidaTs = $salidaDT->getTimestamp();

    // Limitar duración máxima a 24 horas para evitar memory exhaustion
    if ($salidaTs - $entradaTs > 24 * 3600) {
        $salidaTs = $entradaTs + 24 * 3600;
    }

    $currentTs = $salidaTs;

    while ($currentTs > $entradaTs) {
        $chunkSeconds = min(1, $currentTs - $entradaTs);
        if ($chunkSeconds <= 0) {
            break;
        }

        $segmentStartTs = $currentTs - $chunkSeconds;
        $segmentEndTs = $currentTs;

        $segmentStartDT = (new DateTimeImmutable('@' . $segmentStartTs))->setTimezone($tz);
        $segmentEndDT = (new DateTimeImmutable('@' . $segmentEndTs))->setTimezone($tz);

        $midTs = $segmentStartTs + ($chunkSeconds / 2);
        $midTsRounded = round($midTs);
        $midDT = (new DateTimeImmutable('@' . $midTsRounded))->setTimezone($tz);

        $duracionHoras = $chunkSeconds / 3600;
        if ($duracionHoras <= 0) {
            $currentTs = $segmentStartTs;
            continue;
        }

        $fechaSegmento = $segmentStartDT->format('Y-m-d');
        $estadoDia = obtenerEstadoDiaConCache($fechaSegmento, $pdo);
        $esEspecial = $estadoDia['es_especial'];

        $dentroHorario = false;
        foreach ($scheduleWindows as $ventana) {
            if ($midTs >= $ventana['start'] && $midTs < $ventana['end']) {
                $dentroHorario = true;
                break;
            }
        }

        if ($dentroHorario) {
            $posicion = 'durante';
        } elseif (empty($scheduleWindows)) {
            $posicion = 'sin_horario';
        } else {
            if ($midTs < $firstScheduleTs) {
                $posicion = 'antes';
            } elseif ($midTs >= $lastScheduleTs) {
                $posicion = 'despues';
            } else {
                $anterior = null;
                $siguiente = null;

                // Identify the closest programmed windows around the mid point to decide if this gap is before or after the shift.

                foreach ($scheduleWindows as $ventana) {
                    if ($ventana['end'] <= $midTs) {
                        $anterior = $ventana;
                        continue;
                    }

                    if ($ventana['start'] > $midTs) {
                        $siguiente = $ventana;
                        break;
                    }
                }

                if ($anterior === null && $siguiente !== null) {
                    $posicion = 'antes';
                } elseif ($anterior !== null && $siguiente === null) {
                    $posicion = 'despues';
                } elseif ($anterior !== null && $siguiente !== null) {
                    $distanciaPrev = $midTs - $anterior['end'];
                    $distanciaNext = $siguiente['start'] - $midTs;
                    $posicion = $distanciaNext < $distanciaPrev ? 'antes' : 'despues';
                } else {
                    $posicion = 'fuera';
                }
            }
        }

        $esNocturno = esNocturnoDateTime($midDT);

        $bucket = clasificarSegmentoJerarquico($esEspecial, $dentroHorario, $esNocturno);
        aplicarAcumuladorJerarquico($resultado, $bucket, $duracionHoras);

        $segmentMeta = [
            'inicio' => $segmentStartDT,
            'fin' => $segmentEndDT,
            'bucket' => $bucket,
            'duracion' => $duracionHoras,
            'posicion' => $posicion
        ];

        if ($segmentoActual !== null
            && $segmentoActual['bucket'] === $segmentMeta['bucket']
            && $segmentoActual['posicion'] === $segmentMeta['posicion']
            && $segmentoActual['inicio']->getTimestamp() === $segmentMeta['fin']->getTimestamp()) {
            $segmentoActual['inicio'] = $segmentMeta['inicio'];
            $segmentoActual['duracion'] += $segmentMeta['duracion'];
        } else {
            if ($segmentoActual !== null) {
                $segmentosProcesados[] = $segmentoActual;
            }
            $segmentoActual = $segmentMeta;
        }

        $currentTs = $segmentStartTs;
    }

    if ($segmentoActual !== null) {
        $segmentosProcesados[] = $segmentoActual;
    }

    $segmentosProcesados = array_reverse($segmentosProcesados);

    $segmentosSalida = [];
    foreach ($segmentosProcesados as $segmento) {
        $meta = obtenerMetadataSegmentoJerarquico($segmento['bucket']);

        $segmentosSalida[] = [
            'fecha' => $segmento['inicio']->format('Y-m-d'),
            'hora_inicio' => $segmento['inicio']->format('H:i:s'),
            'hora_fin' => $segmento['fin']->format('H:i:s'),
            'horas' => round($segmento['duracion'], 2),
            'tipo' => $meta['tipo'],
            'es_nocturno' => $meta['es_nocturno'],
            'es_extra' => $meta['es_extra'],
            'categoria' => $meta['categoria'],
            'posicion' => $segmento['posicion'],
            'es_antes_horario' => $segmento['posicion'] === 'antes',
            'es_despues_horario' => $segmento['posicion'] === 'despues',
            'tipo_extra' => $meta['es_extra']
                ? (in_array($segmento['posicion'], ['sin_horario', 'fuera'], true) ? 'sin_horario' : $segmento['posicion'])
                : null
        ];
    }

    $resultado['segmentos'] = $segmentosSalida;

    foreach ($resultado as $clave => &$valor) {
        if ($clave === 'segmentos') {
            continue;
        }
        if (is_numeric($valor)) {
            $valor = round($valor, 4);
        }
    }

    return $resultado;
}

function clasificarSegmentoJerarquico($esEspecial, $dentroHorario, $esNocturno) {
    $fueraHorario = !$dentroHorario;

    if ($esEspecial) {
        if ($fueraHorario && $esNocturno) {
            return 'extra_nocturna_dominical_festiva';
        }
        if ($dentroHorario && $esNocturno) {
            return 'recargo_nocturno_dominical_festivo';
        }
        if ($fueraHorario && !$esNocturno) {
            return 'extra_diurna_dominical_festiva';
        }
        return 'recargo_dominical_festivo';
    }

    if ($fueraHorario && $esNocturno) {
        return 'extra_nocturna';
    }
    if ($dentroHorario && $esNocturno) {
        return 'recargo_nocturno';
    }
    if ($fueraHorario && !$esNocturno) {
        return 'extra_diurna';
    }

    return 'horas_regulares';
}

function aplicarAcumuladorJerarquico(array &$resultado, $bucket, $duracionHoras) {
    switch ($bucket) {
        case 'extra_nocturna_dominical_festiva':
            $resultado['extra_nocturna_dominical_festiva'] += $duracionHoras;
            break;
        case 'recargo_nocturno_dominical_festivo':
            $resultado['recargo_nocturno_dominical_festivo'] += $duracionHoras;
            break;
        case 'extra_diurna_dominical_festiva':
            $resultado['extra_diurna_dominical_festiva'] += $duracionHoras;
            break;
        case 'recargo_dominical_festivo':
            $resultado['recargo_dominical_festivo'] += $duracionHoras;
            break;
        case 'extra_nocturna':
            $resultado['extra_nocturna'] += $duracionHoras;
            break;
        case 'recargo_nocturno':
            $resultado['recargo_nocturno'] += $duracionHoras;
            break;
        case 'extra_diurna':
            $resultado['extra_diurna'] += $duracionHoras;
            break;
        default:
            $resultado['horas_regulares'] += $duracionHoras;
            break;
    }
}

function obtenerMetadataSegmentoJerarquico($bucket) {
    switch ($bucket) {
        case 'extra_nocturna_dominical_festiva':
            return ['tipo' => 'extra', 'categoria' => 'nocturna_dominical_festiva', 'es_nocturno' => true, 'es_extra' => true];
        case 'recargo_nocturno_dominical_festivo':
            return ['tipo' => 'regular', 'categoria' => 'nocturna_dominical_festiva', 'es_nocturno' => true, 'es_extra' => false];
        case 'extra_diurna_dominical_festiva':
            return ['tipo' => 'extra', 'categoria' => 'diurna_dominical_festiva', 'es_nocturno' => false, 'es_extra' => true];
        case 'recargo_dominical_festivo':
            return ['tipo' => 'regular', 'categoria' => 'diurna_dominical_festiva', 'es_nocturno' => false, 'es_extra' => false];
        case 'extra_nocturna':
            return ['tipo' => 'extra', 'categoria' => 'nocturna', 'es_nocturno' => true, 'es_extra' => true];
        case 'recargo_nocturno':
            return ['tipo' => 'regular', 'categoria' => 'nocturna', 'es_nocturno' => true, 'es_extra' => false];
        case 'extra_diurna':
            return ['tipo' => 'extra', 'categoria' => 'diurna', 'es_nocturno' => false, 'es_extra' => true];
        default:
            return ['tipo' => 'regular', 'categoria' => 'diurna', 'es_nocturno' => false, 'es_extra' => false];
    }
}

function registrarExtrasSegmentosPorFecha(array &$registro, array $segmentos) {
    foreach ($segmentos as $segmento) {
        if (empty($segmento['es_extra'])) {
            continue;
        }

        $categoria = $segmento['categoria'] ?? null;
        $claveExtra = null;

        switch ($categoria) {
            case 'diurna':
                $claveExtra = 'extra_diurna';
                break;
            case 'nocturna':
                $claveExtra = 'extra_nocturna';
                break;
            case 'diurna_dominical_festiva':
                $claveExtra = 'extra_diurna_dominical_festiva';
                break;
            case 'nocturna_dominical_festiva':
                $claveExtra = 'extra_nocturna_dominical_festiva';
                break;
            default:
                $claveExtra = null;
                break;
        }

        if ($claveExtra === null) {
            continue;
        }

        $fechaSegmento = $segmento['fecha'] ?? null;
        if ($fechaSegmento === null) {
            continue;
        }

        if (!isset($registro[$fechaSegmento])) {
            $registro[$fechaSegmento] = [
                'extra_diurna' => 0,
                'extra_nocturna' => 0,
                'extra_diurna_dominical_festiva' => 0,
                'extra_nocturna_dominical_festiva' => 0
            ];
        }

        $horasSegmento = isset($segmento['horas']) ? floatval($segmento['horas']) : 0.0;
        if ($horasSegmento <= 0) {
            continue;
        }

        $registro[$fechaSegmento][$claveExtra] += $horasSegmento;
    }
}