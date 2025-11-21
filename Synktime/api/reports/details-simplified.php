<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$empresaId = $_SESSION['id_empresa'] ?? null;

if (!$empresaId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

/**
 * Determina si un horario es nocturno (hora salida < hora entrada)
 */
function esHorarioNocturno($horaEntrada, $horaSalida) {
    if (!$horaEntrada || !$horaSalida) {
        return false;
    }
    
    try {
        $entrada = new DateTime($horaEntrada);
        $salida = new DateTime($horaSalida);
        
        // Si la salida es anterior a la entrada, es nocturno
        return $salida < $entrada;
    } catch (Exception $e) {
        return false;
    }
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

try {
    // Obtener parámetros - aceptar tanto 'id' como 'empleado_id'
    $asistenciaId = isset($_GET['id']) ? intval($_GET['id']) : null;
    $empleadoId = isset($_GET['empleado_id']) ? intval($_GET['empleado_id']) : null;
    $fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) ? $_GET['fecha'] : getBogotaDate();
    
    // Si se proporciona ID de asistencia, obtener el empleado y fecha de esa asistencia
    if ($asistenciaId && !$empleadoId) {
        $stmt = $conn->prepare("
            SELECT a.ID_EMPLEADO, a.FECHA 
            FROM asistencia a 
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            WHERE a.ID_ASISTENCIA = ? AND s.ID_EMPRESA = ?
        ");
        $stmt->execute([$asistenciaId, $empresaId]);
        $asistenciaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$asistenciaInfo) {
            throw new Exception('Asistencia no encontrada o no autorizada');
        }
        
        $empleadoId = $asistenciaInfo['ID_EMPLEADO'];
        $fecha = $asistenciaInfo['FECHA'];
    }
    
    if (!$empleadoId) {
        throw new Exception('ID de empleado o asistencia requerido');
    }
    
    // Validar que el empleado pertenece a la empresa
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?
    ");
    $stmt->execute([$empleadoId, $empresaId]);
    
    if ($stmt->fetchColumn() == 0) {
        throw new Exception('Empleado no encontrado o no autorizado');
    }
    
    // Obtener información del horario personalizado para determinar si es nocturno
    $stmtHorario = $conn->prepare("
        SELECT 
            COALESCE(ehp.HORA_ENTRADA, '08:00:00') AS HORA_ENTRADA,
            COALESCE(ehp.HORA_SALIDA, '17:00:00') AS HORA_SALIDA
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ?
        AND ehp.FECHA_DESDE <= ?
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        ORDER BY ehp.FECHA_DESDE DESC
        LIMIT 1
    ");
    $stmtHorario->execute([$empleadoId, $fecha, $fecha]);
    $horarioInfo = $stmtHorario->fetch(PDO::FETCH_ASSOC);
    
    $esNocturno = false;
    if ($horarioInfo) {
        $esNocturno = esHorarioNocturno($horarioInfo['HORA_ENTRADA'], $horarioInfo['HORA_SALIDA']);
    }
    
    // Calcular fecha siguiente para horarios nocturnos
    $fechaSiguiente = date('Y-m-d', strtotime($fecha . ' +1 day'));

    // PRIMERO: Obtener asistencias del día actual
    $sqlDiaActual = "
        SELECT
            a.ID_ASISTENCIA,
            a.FECHA,
            a.HORA,
            a.TIPO,
            a.TARDANZA,
            a.OBSERVACION,
            a.FOTO,
            a.ID_EMPLEADO_HORARIO,
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE AS establecimiento,
            s.NOMBRE AS sede,

            -- Información del horario personalizado únicamente
            COALESCE(ehp.NOMBRE_TURNO, 'Horario personalizado') AS nombre_horario,
            COALESCE(ehp.HORA_ENTRADA, '08:00:00') AS HORA_ENTRADA,
            COALESCE(ehp.HORA_SALIDA, '17:00:00') AS HORA_SALIDA,
            COALESCE(ehp.TOLERANCIA, 15) AS TOLERANCIA

        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE

        -- LEFT JOIN solo con horario personalizado
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO

        WHERE a.ID_EMPLEADO = :empleado_id
          AND a.FECHA = :fecha
          AND s.ID_EMPRESA = :empresa_id
        ORDER BY a.FECHA ASC, a.HORA ASC
    ";

    $stmt = $conn->prepare($sqlDiaActual);
    $stmt->bindParam(':empleado_id', $empleadoId, PDO::PARAM_INT);
    $stmt->bindParam(':empresa_id', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $asistenciasDiaActual = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // SEGUNDO: Para horarios nocturnos, verificar si hay entradas del día actual
    $tieneEntradasDiaActual = false;
    $ultimaEntradaDiaActual = null;

    foreach ($asistenciasDiaActual as $asistencia) {
        if ($asistencia['TIPO'] === 'ENTRADA') {
            $tieneEntradasDiaActual = true;
            if (!$ultimaEntradaDiaActual || strtotime($asistencia['HORA']) > strtotime($ultimaEntradaDiaActual['HORA'])) {
                $ultimaEntradaDiaActual = $asistencia;
            }
        }
    }

    // TERCERO: Si es nocturno y hay entradas del día actual, obtener salidas del día siguiente
    $asistenciasDiaSiguiente = [];
    if ($esNocturno && $tieneEntradasDiaActual && $ultimaEntradaDiaActual) {
        $sqlDiaSiguiente = "
            SELECT
                a.ID_ASISTENCIA,
                a.FECHA,
                a.HORA,
                a.TIPO,
                a.TARDANZA,
                a.OBSERVACION,
                a.FOTO,
                a.ID_EMPLEADO_HORARIO,
                e.ID_EMPLEADO,
                e.DNI,
                e.NOMBRE,
                e.APELLIDO,
                est.NOMBRE AS establecimiento,
                s.NOMBRE AS sede,

                -- Información del horario personalizado únicamente
                COALESCE(ehp.NOMBRE_TURNO, 'Horario personalizado') AS nombre_horario,
                COALESCE(ehp.HORA_ENTRADA, '08:00:00') AS HORA_ENTRADA,
                COALESCE(ehp.HORA_SALIDA, '17:00:00') AS HORA_SALIDA,
                COALESCE(ehp.TOLERANCIA, 15) AS TOLERANCIA

            FROM asistencia a
            JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN sede s ON est.ID_SEDE = s.ID_SEDE

            -- LEFT JOIN solo con horario personalizado
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO

            WHERE a.ID_EMPLEADO = :empleado_id
              AND a.FECHA = :fecha_siguiente
              AND a.TIPO = 'SALIDA'
              AND a.HORA > :ultima_entrada_hora
              AND s.ID_EMPRESA = :empresa_id
            ORDER BY a.HORA ASC
        ";

        $stmtSiguiente = $conn->prepare($sqlDiaSiguiente);
        $stmtSiguiente->bindParam(':empleado_id', $empleadoId, PDO::PARAM_INT);
        $stmtSiguiente->bindParam(':empresa_id', $empresaId, PDO::PARAM_INT);
        $stmtSiguiente->bindParam(':fecha_siguiente', $fechaSiguiente, PDO::PARAM_STR);
        $stmtSiguiente->bindParam(':ultima_entrada_hora', $ultimaEntradaDiaActual['HORA'], PDO::PARAM_STR);
        $stmtSiguiente->execute();
        $asistenciasDiaSiguiente = $stmtSiguiente->fetchAll(PDO::FETCH_ASSOC);
    }

    // COMBINAR asistencias del día actual y siguiente
    $asistencias = array_merge($asistenciasDiaActual, $asistenciasDiaSiguiente);
    
    if (empty($asistencias)) {
        // Obtener información básica del empleado aunque no tenga asistencias
        $stmt = $conn->prepare("
            SELECT 
                e.ID_EMPLEADO,
                e.DNI,
                e.NOMBRE,
                e.APELLIDO,
                est.NOMBRE AS establecimiento,
                s.NOMBRE AS sede,
                
                -- Horario personalizado por defecto
                COALESCE(ehp.NOMBRE_TURNO, 'Horario personalizado') AS nombre_horario,
                COALESCE(ehp.HORA_ENTRADA, '08:00:00') AS HORA_ENTRADA,
                COALESCE(ehp.HORA_SALIDA, '17:00:00') AS HORA_SALIDA,
                COALESCE(ehp.TOLERANCIA, 15) AS TOLERANCIA
                
            FROM empleado e
            JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO 
                AND ehp.FECHA_DESDE <= :fecha 
                AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
            WHERE e.ID_EMPLEADO = :empleado_id
              AND s.ID_EMPRESA = :empresa_id
        ");
        
        $stmt->bindParam(':empleado_id', $empleadoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':empresa_id', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        $empleadoInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$empleadoInfo) {
            throw new Exception('Empleado no encontrado');
        }
        
        // Verificar si hay justificación para esta fecha
        $justificacion = obtenerJustificacion($empleadoId, $fecha, $conn);
        
        $asistenciasEspeciales = [];
        if ($justificacion) {
            // Crear registro especial para justificación de ausencia
            $asistenciasEspeciales[] = [
                'id' => 'justificacion_' . $justificacion['id'],
                'hora' => null,
                'tipo' => 'JUSTIFICACION',
                'tardanza' => null,
                'observacion' => $justificacion['motivo'] . 
                    ($justificacion['detalle_adicional'] ? ' - ' . $justificacion['detalle_adicional'] : ''),
                'estado' => 'Ausente',
                'es_justificacion' => true,
                'tipo_falta' => $justificacion['tipo_falta']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'empleado' => [
                'id' => $empleadoInfo['ID_EMPLEADO'],
                'dni' => $empleadoInfo['DNI'],
                'nombre_completo' => trim($empleadoInfo['NOMBRE'] . ' ' . $empleadoInfo['APELLIDO']),
                'establecimiento' => $empleadoInfo['establecimiento'],
                'sede' => $empleadoInfo['sede'],
                'horario' => [
                    'nombre' => $empleadoInfo['nombre_horario'],
                    'hora_entrada' => $empleadoInfo['HORA_ENTRADA'],
                    'hora_salida' => $empleadoInfo['HORA_SALIDA'],
                    'tolerancia' => $empleadoInfo['TOLERANCIA']
                ]
            ],
            'fecha' => $fecha,
            'asistencias' => $asistenciasEspeciales,
            'entradas' => [],
            'salidas' => [],
            'resumen' => [
                'total_registros' => count($asistenciasEspeciales),
                'entradas' => 0,
                'salidas' => 0,
                'estado_entrada' => $justificacion ? 'Ausente' : 'Sin registro',
                'horas_trabajadas' => '0:00'
            ]
        ]);
        exit;
    }
    
    // Procesar asistencias
    $entradas = [];
    $salidas = [];
    $empleadoInfo = null;
    $fotoEntrada = null; // Para almacenar la foto de la primera entrada
    
    foreach ($asistencias as $asistencia) {
        if (!$empleadoInfo) {
            $empleadoInfo = [
                'id' => $asistencia['ID_EMPLEADO'],
                'dni' => $asistencia['DNI'],
                'nombre_completo' => trim($asistencia['NOMBRE'] . ' ' . $asistencia['APELLIDO']),
                'establecimiento' => $asistencia['establecimiento'],
                'sede' => $asistencia['sede'],
                'horario' => [
                    'nombre' => $asistencia['nombre_horario'],
                    'hora_entrada' => $asistencia['HORA_ENTRADA'],
                    'hora_salida' => $asistencia['HORA_SALIDA'],
                    'tolerancia' => $asistencia['TOLERANCIA']
                ]
            ];
        }
        
        $registro = [
            'id' => $asistencia['ID_ASISTENCIA'],
            'fecha' => $asistencia['FECHA'],
            'hora' => date('H:i:s', strtotime($asistencia['HORA'])),
            'tipo' => $asistencia['TIPO'],
            'tardanza' => $asistencia['TARDANZA'],
            'observacion' => $asistencia['OBSERVACION'],
            'foto' => $asistencia['FOTO']
        ];
        
        if ($asistencia['TIPO'] === 'ENTRADA') {
            // Para horarios nocturnos, calcular estado considerando la fecha del registro
            if ($esNocturno && $asistencia['FECHA'] === $fecha) {
                // Entrada del día actual para horario nocturno
                $estado = calcularEstadoEntrada(
                    $asistencia['HORA_ENTRADA'],
                    $asistencia['HORA'],
                    (int)$asistencia['TOLERANCIA']
                );
            } elseif (!$esNocturno) {
                // Horario normal
                $estado = calcularEstadoEntrada(
                    $asistencia['HORA_ENTRADA'],
                    $asistencia['HORA'],
                    (int)$asistencia['TOLERANCIA']
                );
            } else {
                // Entrada del día siguiente (no debería tener estado para el día actual)
                $estado = '--';
            }

            if ($estado !== '--') {
                $registro['estado'] = $estado;
            }

            $entradas[] = $registro;

            // Guardar la foto de la primera entrada del día actual
            if (!$fotoEntrada && $asistencia['FECHA'] === $fecha && $asistencia['FOTO']) {
                $fotoEntrada = $asistencia['FOTO'];
            }
        } else {
            // Procesar salidas
            if ($esNocturno && $asistencia['FECHA'] !== $fecha) {
                // Salida del día siguiente para horario nocturno - estado normal
                $registro['estado'] = 'Salida';
            } else {
                // Salida del día actual - calcular estado basado en hora de salida programada
                $estadoSalida = calcularEstadoSalida(
                    $asistencia['HORA_SALIDA'],
                    $asistencia['HORA'],
                    (int)$asistencia['TOLERANCIA']
                );
                $registro['estado'] = $estadoSalida;
            }

            $salidas[] = $registro;
        }
    }
    
    // Verificar si hay justificación para esta fecha y agregarla a las asistencias
    $justificacion = obtenerJustificacion($empleadoId, $fecha, $conn);
    $asistenciasFinal = array_merge($entradas, $salidas);
    
    if ($justificacion) {
        // Crear registro especial para justificación
        $registroJustificacion = [
            'id' => 'justificacion_' . $justificacion['id'],
            'hora' => null,
            'tipo' => 'JUSTIFICACION',
            'tardanza' => null,
            'observacion' => $justificacion['motivo'] . 
                ($justificacion['detalle_adicional'] ? ' - ' . $justificacion['detalle_adicional'] : ''),
            'estado' => 'Ausente',
            'es_justificacion' => true,
            'tipo_falta' => $justificacion['tipo_falta']
        ];
        $asistenciasFinal[] = $registroJustificacion;
    }
    
    // Calcular estado de entrada
    $estado_entrada = 'Sin registro';
    if (!empty($entradas)) {
        $estado_entrada = $entradas[0]['estado']; // Usar la primera entrada
    } elseif ($justificacion) {
        $estado_entrada = 'Ausente'; // Si hay justificación pero no entradas
    }
    
    // Calcular horas trabajadas (considerando horarios nocturnos)
    $horas_trabajadas = '0:00';
    if (!empty($entradas) && !empty($salidas)) {
        // Para horarios nocturnos, necesitamos considerar todas las combinaciones posibles
        if ($esNocturno) {
            // Encontrar la primera entrada y la última salida considerando días consecutivos
            $primeraEntrada = null;
            $ultimaSalida = null;
            
            foreach ($entradas as $entrada) {
                if (!$primeraEntrada || strtotime($entrada['fecha'] . ' ' . $entrada['hora']) < strtotime($primeraEntrada['fecha'] . ' ' . $primeraEntrada['hora'])) {
                    $primeraEntrada = $entrada;
                }
            }
            
            foreach ($salidas as $salida) {
                if (!$ultimaSalida || strtotime($salida['fecha'] . ' ' . $salida['hora']) > strtotime($ultimaSalida['fecha'] . ' ' . $ultimaSalida['hora'])) {
                    $ultimaSalida = $salida;
                }
            }
            
            if ($primeraEntrada && $ultimaSalida) {
                $ts_entrada = strtotime($primeraEntrada['fecha'] . ' ' . $primeraEntrada['hora']);
                $ts_salida = strtotime($ultimaSalida['fecha'] . ' ' . $ultimaSalida['hora']);
                
                if ($ts_salida > $ts_entrada) {
                    $diff = $ts_salida - $ts_entrada;
                    $horas = floor($diff / 3600);
                    $minutos = floor(($diff % 3600) / 60);
                    $horas_trabajadas = sprintf('%d:%02d', $horas, $minutos);
                }
            }
        } else {
            // Lógica normal para horarios diurnos
            $entrada_hora = strtotime($entradas[0]['hora']);
            $salida_hora = strtotime(end($salidas)['hora']);
            
            if ($salida_hora > $entrada_hora) {
                $diff = $salida_hora - $entrada_hora;
                $horas = floor($diff / 3600);
                $minutos = floor(($diff % 3600) / 60);
                $horas_trabajadas = sprintf('%d:%02d', $horas, $minutos);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'empleado' => $empleadoInfo,
        'fecha' => $fecha,
        'asistencias' => $asistenciasFinal,
        'entradas' => $entradas,
        'salidas' => $salidas,
        'foto' => $fotoEntrada, // Foto de la primera entrada
        'resumen' => [
            'total_registros' => count($asistenciasFinal),
            'entradas' => count($entradas),
            'salidas' => count($salidas),
            'estado_entrada' => $estado_entrada,
            'horas_trabajadas' => $horas_trabajadas
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en reports/details-simplified.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>