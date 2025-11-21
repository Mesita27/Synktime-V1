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

// Headers y configuración inicial
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

// Obtener parámetros
$id_empleado = $_GET['id_empleado'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t'); // Último día del mes actual

// Validaciones
if (!$id_empleado) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de empleado requerido']);
    exit;
}

try {
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
        $horaInt = (int)str_replace(':', '', $hora);
        return $horaInt >= 2100 || $horaInt < 600;
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
     * Obtiene el horario personalizado del empleado para una fecha específica
     */
    function obtenerHorarioPersonalizado($idEmpleado, $fecha, $diaSemana, $pdo) {
        $stmt = $pdo->prepare("
            SELECT 
                ehp.HORA_ENTRADA,
                ehp.HORA_SALIDA,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.ES_TURNO_NOCTURNO
            FROM empleado_horario_personalizado ehp
            WHERE ehp.ID_EMPLEADO = ?
            AND ehp.ID_DIA = ?
            AND ehp.ACTIVO = 'S'
            AND (ehp.FECHA_DESDE IS NULL OR ehp.FECHA_DESDE <= ?)
            AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
            ORDER BY ehp.ORDEN_TURNO ASC
            LIMIT 1
        ");
        
        $stmt->execute([$idEmpleado, $diaSemana, $fecha, $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verifica si hay justificación para una fecha
     */
    function obtenerJustificacion($idEmpleado, $fecha, $pdo) {
        $stmt = $pdo->prepare("
            SELECT 
                motivo,
                detalle_adicional,
                tipo_falta,
                estado,
                fecha_justificacion,
                horas_programadas
            FROM justificaciones
            WHERE empleado_id = ?
            AND fecha_falta = ?
            AND estado IN ('aprobada', 'pendiente')
            LIMIT 1
        ");
        
        $stmt->execute([$idEmpleado, $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ===============================
    // CONSULTA PRINCIPAL
    // ===============================
    
    // Obtener registros de asistencia del empleado en el rango de fechas
    $stmt = $pdo->prepare("
        SELECT 
            a.FECHA,
            a.HORA,
            a.TIPO,
            a.ID_ASISTENCIA,
            DAYOFWEEK(a.FECHA) as DIA_SEMANA_NUM
        FROM asistencia a
        WHERE a.ID_EMPLEADO = ?
        AND a.FECHA BETWEEN ? AND ?
        ORDER BY a.FECHA ASC, a.HORA ASC
    ");
    
    $stmt->execute([$id_empleado, $fecha_inicio, $fecha_fin]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===============================
    // PROCESAMIENTO DE DATOS
    // ===============================
    
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
    
    // Agrupar registros por fecha
    $registrosPorFecha = [];
    foreach ($registros as $registro) {
        $fecha = $registro['FECHA'];
        if (!isset($registrosPorFecha[$fecha])) {
            $registrosPorFecha[$fecha] = [];
        }
        $registrosPorFecha[$fecha][] = $registro;
    }
    
    // Procesar cada fecha
    foreach ($registrosPorFecha as $fecha => $registrosDia) {
        $diaSemana = $registrosDia[0]['DIA_SEMANA_NUM'];
        
        // Verificar si hay justificación
        $justificacion = obtenerJustificacion($id_empleado, $fecha, $pdo);
        
        if ($justificacion) {
            // Hay justificación - registrar pero con 0 horas
            $resultado['justificaciones'][] = [
                'fecha' => $fecha,
                'motivo' => $justificacion['motivo'],
                'detalle_adicional' => $justificacion['detalle_adicional'],
                'tipo_falta' => $justificacion['tipo_falta'],
                'estado' => $justificacion['estado'],
                'fecha_justificacion' => $justificacion['fecha_justificacion'],
                'horas_programadas' => $justificacion['horas_programadas']
            ];
            
            $resultado['detalle_dias'][] = [
                'fecha' => $fecha,
                'es_domingo' => esDomingo($fecha),
                'es_festivo' => esFestivo($fecha, $pdo),
                'es_dia_civico' => esDiaCivico($fecha, $pdo),
                'justificado' => true,
                'horas_trabajadas' => 0,
                'detalle_horas' => []
            ];
            
            continue;
        }
        
        // Obtener horario personalizado
        $horarioPersonalizado = obtenerHorarioPersonalizado($id_empleado, $fecha, $diaSemana, $pdo);
        
        // Procesar registros del día (buscar pares entrada-salida)
        $detalleHoras = [];
        $horasDelDia = 0;
        
        for ($i = 0; $i < count($registrosDia); $i += 2) {
            if (!isset($registrosDia[$i + 1])) break; // No hay par completo
            
            $entrada = $registrosDia[$i];
            $salida = $registrosDia[$i + 1];
            
            if ($entrada['TIPO'] !== 'ENTRADA' || $salida['TIPO'] !== 'SALIDA') {
                continue; // Par inválido
            }
            
            // Calcular horas trabajadas
            $horaEntrada = $entrada['HORA'];
            $horaSalida = $salida['HORA'];
            $horasTrabajadas = calcularDiferenciaHoras($horaEntrada, $horaSalida);
            
            // Determinar características del día
            $esFechaEsp = esFechaEspecial($fecha, $pdo);
            $esNocturnoEntrada = esHoraNocturna($horaEntrada);
            $esNocturnoSalida = esHoraNocturna($horaSalida);
            
            // Determinar si está dentro del horario programado
            $dentroDelHorario = false;
            $horasRegulares = 0;
            $horasExtra = 0;
            
            if ($horarioPersonalizado) {
                $horaEntradaProg = $horarioPersonalizado['HORA_ENTRADA'];
                $horaSalidaProg = $horarioPersonalizado['HORA_SALIDA'];
                $tolerancia = $horarioPersonalizado['TOLERANCIA'] ?? 0;
                
                // Verificar si está dentro del horario (con tolerancia)
                $entradaConTolerancia = date('H:i', strtotime($horaEntradaProg . " -{$tolerancia} minutes"));
                $salidaConTolerancia = date('H:i', strtotime($horaSalidaProg . " +{$tolerancia} minutes"));
                
                if ($horaEntrada >= $entradaConTolerancia && $horaSalida <= $salidaConTolerancia) {
                    $dentroDelHorario = true;
                    $horasRegulares = $horasTrabajadas;
                } else {
                    $horasExtra = $horasTrabajadas;
                }
            } else {
                // Sin horario personalizado, todas las horas son extra
                $horasExtra = $horasTrabajadas;
            }
            
            // Categorizar las horas
            if ($dentroDelHorario) {
                // HORAS REGULARES (dentro del horario)
                if ($esFechaEsp) {
                    if ($esNocturnoEntrada || $esNocturnoSalida) {
                        $resultado['estadisticas']['recargo_nocturno_dominical_festivo'] += $horasRegulares;
                    } else {
                        $resultado['estadisticas']['recargo_dominical_festivo'] += $horasRegulares;
                    }
                } else {
                    if ($esNocturnoEntrada || $esNocturnoSalida) {
                        $resultado['estadisticas']['recargo_nocturno'] += $horasRegulares;
                    } else {
                        $resultado['estadisticas']['horas_regulares'] += $horasRegulares;
                    }
                }
            } else {
                // HORAS EXTRA (fuera del horario)
                if ($esFechaEsp) {
                    if ($esNocturnoEntrada || $esNocturnoSalida) {
                        $resultado['estadisticas']['extra_nocturna_dominical_festiva'] += $horasExtra;
                    } else {
                        $resultado['estadisticas']['extra_diurna_dominical_festiva'] += $horasExtra;
                    }
                } else {
                    if ($esNocturnoEntrada || $esNocturnoSalida) {
                        $resultado['estadisticas']['extra_nocturna'] += $horasExtra;
                    } else {
                        $resultado['estadisticas']['extra_diurna'] += $horasExtra;
                    }
                }
            }
            
            $detalleHoras[] = [
                'hora_entrada' => $horaEntrada,
                'hora_salida' => $horaSalida,
                'horas_trabajadas' => $horasTrabajadas,
                'dentro_horario' => $dentroDelHorario,
                'es_nocturno' => $esNocturnoEntrada || $esNocturnoSalida,
                'categoria' => $dentroDelHorario ? 
                    ($esFechaEsp ? 
                        ($esNocturnoEntrada || $esNocturnoSalida ? 'recargo_nocturno_dominical_festivo' : 'recargo_dominical_festivo') :
                        ($esNocturnoEntrada || $esNocturnoSalida ? 'recargo_nocturno' : 'horas_regulares')
                    ) :
                    ($esFechaEsp ?
                        ($esNocturnoEntrada || $esNocturnoSalida ? 'extra_nocturna_dominical_festiva' : 'extra_diurna_dominical_festiva') :
                        ($esNocturnoEntrada || $esNocturnoSalida ? 'extra_nocturna' : 'extra_diurna')
                    )
            ];
            
            $horasDelDia += $horasTrabajadas;
        }
        
        $resultado['detalle_dias'][] = [
            'fecha' => $fecha,
            'es_domingo' => esDomingo($fecha),
            'es_festivo' => esFestivo($fecha, $pdo),
            'es_dia_civico' => esDiaCivico($fecha, $pdo),
            'justificado' => false,
            'horas_trabajadas' => $horasDelDia,
            'horario_personalizado' => $horarioPersonalizado,
            'detalle_horas' => $detalleHoras
        ];
        
        $resultado['estadisticas']['total_horas'] += $horasDelDia;
    }
    
    // Redondear valores a 2 decimales
    foreach ($resultado['estadisticas'] as $key => $value) {
        $resultado['estadisticas'][$key] = round($value, 2);
    }
    
    echo json_encode($resultado, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>