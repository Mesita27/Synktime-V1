<?php
/**
 * API Mejorada para obtener empleados con información completa incluyendo horarios
 * SNKTIME Biometric System - Versión Mejorada
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Temporarily disable error output to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../config/database.php';

    // Simple authentication check
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['id_empresa'])) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    $empresaId = $_SESSION['id_empresa'];

    // Parámetros de filtro
    $employeeId = $_GET['employee_id'] ?? null;
    $sedeId = $_GET['sede_id'] ?? null;
    $establecimientoId = $_GET['establecimiento_id'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    // Obtener día de la semana (1=Lunes ... 7=Domingo)
    $diaSemana = date('N', strtotime($fecha));

    // Construir consulta base
    $where = ["e.ID_EMPRESA = :empresa_id", "e.ESTADO = 'A'", "e.ACTIVO = 'S'"];
    $params = [':empresa_id' => $empresaId];

    if ($employeeId) {
        $where[] = "e.ID_EMPLEADO = :employee_id";
        $params[':employee_id'] = $employeeId;
    }

    if ($sedeId) {
        $where[] = "s.ID_SEDE = :sede_id";
        $params[':sede_id'] = $sedeId;
    }

    if ($establecimientoId) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento_id";
        $params[':establecimiento_id'] = $establecimientoId;
    }

    $whereClause = implode(' AND ', $where);

    // Consulta principal con información completa
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            e.ESTADO,
            e.ACTIVO,
            e.FECHA_INGRESO,
            e.CARGO,
            est.ID_ESTABLECIMIENTO,
            est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
            s.ID_SEDE,
            s.NOMBRE AS SEDE_NOMBRE,
            -- Información biométrica
            CASE WHEN eb_facial.id IS NOT NULL THEN 1 ELSE 0 END as facial_enrolled,
            CASE WHEN eb_huella.id IS NOT NULL THEN 1 ELSE 0 END as fingerprint_enrolled,
            CASE WHEN eb_rfid.id IS NOT NULL THEN 1 ELSE 0 END as rfid_enrolled,
            -- Horarios asignados para el día
            GROUP_CONCAT(DISTINCT CONCAT(
                h.ID_HORARIO, ':',
                h.NOMBRE, ':',
                h.HORA_ENTRADA, ':',
                h.HORA_SALIDA, ':',
                h.TOLERANCIA
            ) SEPARATOR '|') as horarios_dia
        FROM EMPLEADO e
        LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        -- Joins para información biométrica
        LEFT JOIN employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id AND eb_facial.biometric_type = 'face'
        LEFT JOIN employee_biometrics eb_huella ON e.ID_EMPLEADO = eb_huella.employee_id AND eb_huella.biometric_type = 'fingerprint'
        LEFT JOIN employee_biometrics eb_rfid ON e.ID_EMPLEADO = eb_rfid.employee_id AND eb_rfid.biometric_type = 'rfid'
        -- Joins para horarios
        LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
            AND eh.FECHA_DESDE <= :fecha_actual
            AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha_actual)
        LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        LEFT JOIN HORARIO_DIA hd ON h.ID_HORARIO = hd.ID_HORARIO AND hd.ID_DIA = :dia_semana
        WHERE {$whereClause}
        GROUP BY e.ID_EMPLEADO
        ORDER BY e.NOMBRE, e.APELLIDO
    ";

    $params[':fecha_actual'] = $fecha;
    $params[':dia_semana'] = $diaSemana;

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $empleados = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Procesar horarios
        $horarios = [];
        if (!empty($row['horarios_dia'])) {
            $horariosRaw = explode('|', $row['horarios_dia']);
            foreach ($horariosRaw as $horarioRaw) {
                if (!empty($horarioRaw)) {
                    list($idHorario, $nombre, $horaEntrada, $horaSalida, $tolerancia) = explode(':', $horarioRaw);
                    $horarios[] = [
                        'id_horario' => $idHorario,
                        'nombre' => $nombre,
                        'hora_entrada' => $horaEntrada,
                        'hora_salida' => $horaSalida,
                        'tolerancia_minutos' => (int)$tolerancia
                    ];
                }
            }
        }

        // Verificar asistencias del día actual
        $asistenciasStmt = $conn->prepare("
            SELECT TIPO, HORA, TARDANZA
            FROM ASISTENCIA
            WHERE ID_EMPLEADO = ? AND FECHA = ?
            ORDER BY HORA
        ");
        $asistenciasStmt->execute([$row['ID_EMPLEADO'], $fecha]);
        $asistencias = $asistenciasStmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular estado actual
        $estadoActual = determinarEstadoAsistencia($asistencias, $horarios, $fecha);

        $empleados[] = [
            'id_empleado' => $row['ID_EMPLEADO'],
            'dni' => $row['DNI'],
            'nombre_completo' => $row['NOMBRE'] . ' ' . $row['APELLIDO'],
            'nombre' => $row['NOMBRE'],
            'apellido' => $row['APELLIDO'],
            'estado' => $row['ESTADO'],
            'activo' => $row['ACTIVO'],
            'fecha_ingreso' => $row['FECHA_INGRESO'],
            'cargo' => $row['CARGO'],
            'establecimiento' => [
                'id' => $row['ID_ESTABLECIMIENTO'],
                'nombre' => $row['ESTABLECIMIENTO_NOMBRE']
            ],
            'sede' => [
                'id' => $row['ID_SEDE'],
                'nombre' => $row['SEDE_NOMBRE']
            ],
            'biometrico' => [
                'facial_enrolled' => (bool)$row['facial_enrolled'],
                'fingerprint_enrolled' => (bool)$row['fingerprint_enrolled'],
                'rfid_enrolled' => (bool)$row['rfid_enrolled']
            ],
            'horarios_dia' => $horarios,
            'asistencias_hoy' => $asistencias,
            'estado_actual' => $estadoActual
        ];
    }

    echo json_encode([
        'success' => true,
        'empleados' => $empleados,
        'fecha_consulta' => $fecha,
        'dia_semana' => $diaSemana,
        'total' => count($empleados)
    ]);

} catch (Exception $e) {
    error_log('Error en get-employees-complete.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener empleados: ' . $e->getMessage()
    ]);
}

/**
 * Determina el estado actual de asistencia del empleado
 */
function determinarEstadoAsistencia($asistencias, $horarios, $fecha) {
    if (empty($asistencias)) {
        return [
            'estado' => 'SIN_REGISTRO',
            'proximo_registro' => 'ENTRADA',
            'mensaje' => 'Sin registros de asistencia hoy'
        ];
    }

    $ultimoRegistro = end($asistencias);

    if ($ultimoRegistro['TIPO'] === 'ENTRADA') {
        return [
            'estado' => 'TRABAJANDO',
            'proximo_registro' => 'SALIDA',
            'mensaje' => 'Empleado trabajando - Pendiente salida',
            'hora_entrada' => $ultimoRegistro['HORA'],
            'tardanza' => $ultimoRegistro['TARDANZA']
        ];
    } else {
        // Calcular horas trabajadas si hay entrada previa
        $entrada = null;
        foreach ($asistencias as $asistencia) {
            if ($asistencia['TIPO'] === 'ENTRADA') {
                $entrada = $asistencia;
                break;
            }
        }

        if ($entrada) {
            $horaEntrada = strtotime($fecha . ' ' . $entrada['HORA']);
            $horaSalida = strtotime($fecha . ' ' . $ultimoRegistro['HORA']);
            $minutosTrabajados = ($horaSalida - $horaEntrada) / 60;
            $horasTrabajadas = round($minutosTrabajados / 60, 2);

            return [
                'estado' => 'COMPLETADO',
                'proximo_registro' => null,
                'mensaje' => 'Jornada completada',
                'hora_entrada' => $entrada['HORA'],
                'hora_salida' => $ultimoRegistro['HORA'],
                'horas_trabajadas' => $horasTrabajadas,
                'tardanza' => $entrada['TARDANZA']
            ];
        }

        return [
            'estado' => 'SALIDA_SIN_ENTRADA',
            'proximo_registro' => null,
            'mensaje' => 'Registro de salida sin entrada previa',
            'hora_salida' => $ultimoRegistro['HORA']
        ];
    }
}
?>
