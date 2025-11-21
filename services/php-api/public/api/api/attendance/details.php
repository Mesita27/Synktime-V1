<?php
/**
 * API para obtener detalles completos de asistencia de un empleado en una fecha específica
 * Incluye información del horario, entrada, salida y fotos
 */

// Evitar cualquier output antes de los headers
ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Evitar que se muestre HTML de error
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Evitar que se muestre HTML de error
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$empresaId = $_SESSION['id_empresa'] ?? 1;

// Manejar OPTIONS request para CORS
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Determina el tipo de turno basado en la hora de entrada
 */
function determinarTipoTurno($horaEntrada) {
    if (!$horaEntrada) {
        return 'Sin horario';
    }

    // Convertir hora a minutos para comparación
    $partes = explode(':', $horaEntrada);
    $horaInt = (int)$partes[0];
    $minutosInt = (int)$partes[1];
    $totalMinutos = $horaInt * 60 + $minutosInt;

    if ($totalMinutos >= 300 && $totalMinutos < 720) { // 05:00 - 11:59
        return 'Mañana';
    } elseif ($totalMinutos >= 720 && $totalMinutos < 1080) { // 12:00 - 17:59
        return 'Tarde';
    } elseif ($totalMinutos >= 1080 || $totalMinutos < 300) { // 18:00 - 04:59
        return 'Noche';
    }

    return 'Diurno';
}

/**
 * Determina si una asistencia específica es nocturna basada en horas reales
 */
function esAsistenciaNocturna($horaEntrada, $horaSalida, $fechaEntrada, $fechaSalida) {
    if (!$horaEntrada || !$horaSalida || !$fechaEntrada || !$fechaSalida) {
        return false;
    }

    if ($fechaEntrada !== $fechaSalida) {
        $fechaEntradaObj = new DateTime($fechaEntrada);
        $fechaSalidaObj = new DateTime($fechaSalida);
        $diferenciaDias = $fechaEntradaObj->diff($fechaSalidaObj)->days;

        if ($diferenciaDias === 1) {
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

        $esAsistenciaNocturna = esAsistenciaNocturna($horaEntrada, $horaSalida, $fechaEntrada, $fechaSalida);

        if ($esAsistenciaNocturna) {
            $medianocheEntrada = new DateTime($fechaEntrada . ' 23:59:59');
            $medianocheSalida = new DateTime($fechaSalida . ' 00:00:00');

            $segundosHastaMedianoche = $medianocheEntrada->getTimestamp() - $entrada->getTimestamp();
            $horasHastaMedianoche = $segundosHastaMedianoche / 3600;

            $segundosDesdeMedianoche = $salida->getTimestamp() - $medianocheSalida->getTimestamp();
            $horasDesdeMedianoche = $segundosDesdeMedianoche / 3600;

            $horas = $horasHastaMedianoche + $horasDesdeMedianoche;
        } else {
            $intervalo = $entrada->diff($salida);
            $horas = $intervalo->h + ($intervalo->i / 60);

            $mismoDiaSalidaAntes = ($fechaEntrada === $fechaSalida) && (strtotime($horaSalida) < strtotime($horaEntrada));
            $fechaEntradaObj = new DateTime($fechaEntrada);
            $fechaSalidaObj = new DateTime($fechaSalida);
            $diasDiferencia = $fechaEntradaObj->diff($fechaSalidaObj)->days;

            if ($horas < 0 || $mismoDiaSalidaAntes || $diasDiferencia > 1) {
                $horas = 0;
            }
        }

        return round($horas, 2);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Busca la salida más cercana asociada a una entrada específica.
 */
function buscarSalidaParaEntrada(PDO $conn, array $entrada) {
    if (empty($entrada['ID_ASISTENCIA']) || empty($entrada['ID_EMPLEADO'])) {
        return null;
    }

    // Intentar obtener la salida directamente si existe una referencia guardada
    if (!empty($entrada['SALIDA_ID_ASISTENCIA'])) {
        $stmtSalidaDirecta = $conn->prepare("
            SELECT *
            FROM ASISTENCIA
            WHERE ID_ASISTENCIA = ?
              AND ID_EMPLEADO = ?
              AND TIPO = 'SALIDA'
            LIMIT 1
        ");
        $stmtSalidaDirecta->execute([$entrada['SALIDA_ID_ASISTENCIA'], $entrada['ID_EMPLEADO']]);
        $salidaDirecta = $stmtSalidaDirecta->fetch(PDO::FETCH_ASSOC);
        if ($salidaDirecta) {
            return $salidaDirecta;
        }
    }

    $consulta = "
        SELECT s.*
        FROM ASISTENCIA s
        WHERE s.ID_EMPLEADO = :empleadoId
          AND s.TIPO = 'SALIDA'
          AND CONCAT(s.FECHA, ' ', s.HORA) >= CONCAT(:fechaEntrada, ' ', :horaEntrada)
          AND CONCAT(s.FECHA, ' ', s.HORA) <= DATE_ADD(CONCAT(:fechaEntrada, ' ', :horaEntrada), INTERVAL 36 HOUR)
    ";

    $params = [
        ':empleadoId' => $entrada['ID_EMPLEADO'],
        ':fechaEntrada' => $entrada['FECHA'],
        ':horaEntrada' => $entrada['HORA']
    ];

    if (!empty($entrada['ID_EMPLEADO_HORARIO'])) {
        $consulta .= " AND s.ID_EMPLEADO_HORARIO = :idHorario";
        $params[':idHorario'] = $entrada['ID_EMPLEADO_HORARIO'];
    }

    $consulta .= " ORDER BY CONCAT(s.FECHA, ' ', s.HORA) ASC LIMIT 1";

    $stmtSalida = $conn->prepare($consulta);
    foreach ($params as $clave => $valor) {
        if ($clave === ':empleadoId' || $clave === ':idHorario') {
            $stmtSalida->bindValue($clave, (int)$valor, PDO::PARAM_INT);
        } else {
            $stmtSalida->bindValue($clave, $valor);
        }
    }
    $stmtSalida->execute();

    $salida = $stmtSalida->fetch(PDO::FETCH_ASSOC);
    return $salida ?: null;
}

try {
    // Obtener parámetros
    $codigoEmpleado = $_GET['codigo'] ?? null;
    $fecha = $_GET['fecha'] ?? null;
    $idAsistencia = $_GET['id_asistencia'] ?? null;

    if (!$codigoEmpleado || !$fecha) {
        throw new Exception('Parámetros requeridos: codigo y fecha');
    }

    // Convertir fecha de formato dd/mm/yyyy a yyyy-mm-dd si es necesario
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
        $fechaObj = DateTime::createFromFormat('d/m/Y', $fecha);
        $fecha = $fechaObj->format('Y-m-d');
    }

    // Obtener información del empleado
    $stmtEmpleado = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, s.NOMBRE as sede, est.NOMBRE as establecimiento
        FROM EMPLEADO e
        INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?
    ");
    $stmtEmpleado->execute([$codigoEmpleado, $empresaId]);
    $empleado = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        throw new Exception('Empleado no encontrado');
    }

    $entradaSeleccionada = null;
    if ($idAsistencia) {
        $stmtEntradaEspecifica = $conn->prepare("
            SELECT *
            FROM ASISTENCIA
            WHERE ID_ASISTENCIA = ?
              AND ID_EMPLEADO = ?
            LIMIT 1
        ");
        $stmtEntradaEspecifica->execute([$idAsistencia, $empleado['ID_EMPLEADO']]);
        $entradaEncontrada = $stmtEntradaEspecifica->fetch(PDO::FETCH_ASSOC);

        if ($entradaEncontrada && strtoupper($entradaEncontrada['TIPO']) === 'ENTRADA') {
            $entradaSeleccionada = $entradaEncontrada;
            $fecha = $entradaEncontrada['FECHA'];
        }
    }

    // Obtener información del horario programado para esa fecha
    $horario = null;

    // Si se proporciona ID de asistencia, usar el horario específico de ese registro
    if ($entradaSeleccionada && !empty($entradaSeleccionada['ID_EMPLEADO_HORARIO'])) {
        require_once __DIR__ . '/../../utils/horario_utils.php';
        $horarioInfo = obtenerHorarioPorId($entradaSeleccionada['ID_EMPLEADO_HORARIO'], $conn);

        if ($horarioInfo) {
            $horario = [
                'id_horario' => $horarioInfo['ID_EMPLEADO_HORARIO'],
                'nombre_horario' => $horarioInfo['horario_nombre'],
                'hora_entrada' => $horarioInfo['HORA_ENTRADA'],
                'hora_salida' => $horarioInfo['HORA_SALIDA'],
                'tolerancia' => $horarioInfo['TOLERANCIA'],
                'tipo_horario' => $horarioInfo['tipo_horario']
            ];
        }
    } elseif ($idAsistencia) {
        // Obtener el ID_EMPLEADO_HORARIO del registro de asistencia específico
        $stmtHorarioEspecifico = $conn->prepare("
            SELECT a.ID_EMPLEADO_HORARIO
            FROM ASISTENCIA a
            WHERE a.ID_ASISTENCIA = ?
        ");
        $stmtHorarioEspecifico->execute([$idAsistencia]);
        $registroAsistencia = $stmtHorarioEspecifico->fetch(PDO::FETCH_ASSOC);

        if ($registroAsistencia && $registroAsistencia['ID_EMPLEADO_HORARIO']) {
            // Usar la nueva función obtenerHorarioPorId para obtener el horario específico
            require_once __DIR__ . '/../../utils/horario_utils.php';
            $horarioInfo = obtenerHorarioPorId($registroAsistencia['ID_EMPLEADO_HORARIO'], $conn);

            if ($horarioInfo) {
                $horario = [
                    'id_horario' => $horarioInfo['ID_EMPLEADO_HORARIO'],
                    'nombre_horario' => $horarioInfo['horario_nombre'],
                    'hora_entrada' => $horarioInfo['HORA_ENTRADA'],
                    'hora_salida' => $horarioInfo['HORA_SALIDA'],
                    'tolerancia' => $horarioInfo['TOLERANCIA'],
                    'tipo_horario' => $horarioInfo['tipo_horario']
                ];
            }
        }
    }

    // Si no se pudo obtener horario específico, buscar horario general (lógica original)
    if (!$horario) {
        // Primero buscar horario personalizado (más específico), luego tradicional
        $stmtHorarioPersonalizado = $conn->prepare("
            SELECT
                ehp.ID_EMPLEADO_HORARIO as id_horario,
                COALESCE(ehp.NOMBRE_TURNO, CONCAT('Horario Personalizado (', TIME_FORMAT(ehp.HORA_ENTRADA, '%H:%i'), '-', TIME_FORMAT(ehp.HORA_SALIDA, '%H:%i'), ')')) as nombre_horario,
                TIME_FORMAT(ehp.HORA_ENTRADA, '%H:%i') as hora_entrada,
                TIME_FORMAT(ehp.HORA_SALIDA, '%H:%i') as hora_salida,
                ehp.TOLERANCIA as tolerancia,
                'personalizado' as tipo_horario
            FROM empleado_horario_personalizado ehp
            WHERE ehp.ID_EMPLEADO = ?
                AND ? BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, CURDATE())
                AND ehp.ACTIVO = 'S'
            ORDER BY ehp.FECHA_DESDE DESC
            LIMIT 1
        ");
        $stmtHorarioPersonalizado->execute([$codigoEmpleado, $fecha]);
        $horario = $stmtHorarioPersonalizado->fetch(PDO::FETCH_ASSOC);

        // Si no hay horario personalizado, buscar horario tradicional
        if (!$horario) {
            $stmtHorario = $conn->prepare("
                SELECT
                    eh.ID_HORARIO,
                    h.NOMBRE as nombre_horario,
                    TIME_FORMAT(h.HORA_ENTRADA, '%H:%i') as hora_entrada,
                    TIME_FORMAT(h.HORA_SALIDA, '%H:%i') as hora_salida,
                    h.TOLERANCIA as tolerancia,
                    'tradicional' as tipo_horario
                FROM EMPLEADO_HORARIO eh
                INNER JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
                WHERE eh.ID_EMPLEADO = ?
                    AND ? BETWEEN eh.FECHA_DESDE AND COALESCE(eh.FECHA_HASTA, CURDATE())
                    AND eh.ACTIVO = 'S'
                ORDER BY eh.FECHA_DESDE DESC
                LIMIT 1
            ");
            $stmtHorario->execute([$codigoEmpleado, $fecha]);
            $horario = $stmtHorario->fetch(PDO::FETCH_ASSOC);
        }
    }

    $entrada = $entradaSeleccionada;
    $salida = null;

    if ($entradaSeleccionada) {
        $salida = buscarSalidaParaEntrada($conn, $entradaSeleccionada);
    }

    $registrosAsistencia = [];

    if (!$entrada || !$salida) {
        // Obtener registros de asistencia para la fecha solicitada como respaldo
        $stmtAsistencia = $conn->prepare("\n        SELECT\n            a.ID_ASISTENCIA,\n            a.FECHA,\n            a.HORA,\n            a.TIPO,\n            a.TARDANZA,\n            a.OBSERVACION,\n            a.FOTO,\n            a.ID_EMPLEADO_HORARIO\n        FROM ASISTENCIA a\n        WHERE a.ID_EMPLEADO = ?\n            AND (a.FECHA = ? OR a.FECHA = DATE_ADD(?, INTERVAL 1 DAY))\n        ORDER BY a.FECHA, a.HORA\n    ");
        $stmtAsistencia->execute([$codigoEmpleado, $fecha, $fecha]);
        $registrosAsistencia = $stmtAsistencia->fetchAll(PDO::FETCH_ASSOC);

        foreach ($registrosAsistencia as $registro) {
            if (!$entrada && $registro['FECHA'] === $fecha && $registro['TIPO'] === 'ENTRADA') {
                $entrada = $registro;
                $fecha = $registro['FECHA'];
            }

            if ($entrada && !$salida && $registro['TIPO'] === 'SALIDA') {
                $fechaSiguienteEntrada = date('Y-m-d', strtotime($entrada['FECHA'] . ' +1 day'));

                if ($registro['FECHA'] === $entrada['FECHA'] && $registro['HORA'] >= $entrada['HORA']) {
                    $salida = $registro;
                } elseif ($registro['FECHA'] === $fechaSiguienteEntrada && esAsistenciaNocturna($entrada['HORA'], $registro['HORA'], $entrada['FECHA'], $registro['FECHA'])) {
                    $salida = $registro;
                }
            }
        }

        if ($entrada && !$salida) {
            $salida = buscarSalidaParaEntrada($conn, $entrada);
        }
    }

    // Procesar fotos
    $fotoEntrada = null;
    $fotoSalida = null;

    if ($entrada && !empty($entrada['FOTO'])) {
        $fotoPath = __DIR__ . '/../../uploads/' . $entrada['FOTO'];
        if (file_exists($fotoPath)) {
            $fotoEntrada = 'uploads/' . $entrada['FOTO'];
        }
    }

    if ($salida && !empty($salida['FOTO'])) {
        $fotoPath = __DIR__ . '/../../uploads/' . $salida['FOTO'];
        if (file_exists($fotoPath)) {
            $fotoSalida = 'uploads/' . $salida['FOTO'];
        }
    }

    // Calcular estados
    $estadoEntrada = '--';
    $estadoSalida = '--';

    if ($entrada && $horario && isset($horario['hora_entrada'])) {
        $estadoEntrada = calcularEstadoEntrada(
            $horario['hora_entrada'],
            $entrada['HORA'],
            $horario['tolerancia'] ?? 15
        );
    }

    if ($salida) {
        $estadoSalida = calcularEstadoSalida(
            $horario['hora_salida'] ?? null,
            $salida['HORA'],
            $horario['tolerancia'] ?? 15
        );
    }

    // Calcular horas trabajadas
    $horasTrabajadasDecimal = 0.0;
    $horasTrabajadas = '00:00';
    if ($entrada && $salida) {
        $fechaEntradaReal = $entrada['FECHA'];
        $fechaSalidaReal = $salida['FECHA'];

        $horasTrabajadasDecimal = calcularHorasTrabajadas(
            $entrada['HORA'],
            $salida['HORA'],
            $fechaEntradaReal,
            $fechaSalidaReal
        );
        $horasTrabajadas = sprintf('%02d:%02d', floor($horasTrabajadasDecimal), ($horasTrabajadasDecimal - floor($horasTrabajadasDecimal)) * 60);
    }

    // Determinar si es turno nocturno
    $esNocturno = false;
    if ($entrada && $salida) {
        $esNocturno = esAsistenciaNocturna($entrada['HORA'], $salida['HORA'], $entrada['FECHA'], $salida['FECHA']);
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'empleado' => [
                'codigo' => $empleado['ID_EMPLEADO'],
                'nombre' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'sede' => $empleado['sede'],
                'establecimiento' => $empleado['establecimiento']
            ],
            'fecha' => date('d/m/Y', strtotime($fecha)),
            'horario_programado' => $horario ?: null,
            'asistencia' => [
                'entrada' => $entrada ? [
                    'id_asistencia' => $entrada['ID_ASISTENCIA'],
                    'fecha' => date('d/m/Y', strtotime($entrada['FECHA'])),
                    'hora' => $entrada['HORA'],
                    'estado' => $estadoEntrada,
                    'tardanza' => $entrada['TARDANZA'],
                    'observacion' => $entrada['OBSERVACION'],
                    'foto' => $fotoEntrada
                ] : null,
                'salida' => $salida ? [
                    'id_asistencia' => $salida['ID_ASISTENCIA'],
                    'fecha' => date('d/m/Y', strtotime($salida['FECHA'])),
                    'hora' => $salida['HORA'],
                    'estado' => $estadoSalida,
                    'observacion' => $salida['OBSERVACION'],
                    'foto' => $fotoSalida
                ] : null
            ],
            'horas_trabajadas' => $horasTrabajadas,
            'horas_trabajadas_formateadas' => $horasTrabajadasDecimal > 0 ? sprintf('%02d:%02d', floor($horasTrabajadasDecimal), ($horasTrabajadasDecimal - floor($horasTrabajadasDecimal)) * 60) : '00:00',
            'es_turno_nocturno' => $esNocturno,
            'registro_dia_siguiente' => ($salida && $salida['FECHA'] !== $fecha) ? [
                'FECHA' => date('d/m/Y', strtotime($salida['FECHA'])),
                'SALIDA_HORA' => $salida['HORA']
            ] : null,
            'tipo_turno' => $esNocturno ? 'Nocturno' : determinarTipoTurno($horario['hora_entrada'] ?? null)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Limpiar output buffer y enviar respuesta
ob_end_flush();
?>