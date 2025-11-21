<?php
/**
 * API para obtener detalles completos de empleados
 * Incluye información personal y todos los horarios asignados con tolerancias
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empresaId = $_SESSION['id_empresa'];

/**
 * Obtiene detalles completos de un empleado
 */
function getEmployeeDetails($codigoEmpleado) {
    global $conn, $empresaId;

    try {
        // Obtener información básica del empleado
        $sqlEmpleado = "
            SELECT
                e.ID_EMPLEADO,
                e.DNI,
                e.NOMBRE,
                e.APELLIDO,
                e.FECHA_INGRESO,
                e.ESTADO,
                e.CORREO,
                e.TELEFONO,
                est.NOMBRE as establecimiento,
                est.ID_ESTABLECIMIENTO,
                s.NOMBRE as sede,
                s.ID_SEDE
            FROM EMPLEADO e
            INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            WHERE e.ID_EMPLEADO = ?
            AND s.ID_EMPRESA = ?
        ";

        $stmt = $conn->prepare($sqlEmpleado);
        $stmt->execute([$codigoEmpleado, $empresaId]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$empleado) {
            return [
                'success' => false,
                'message' => 'Empleado no encontrado'
            ];
        }

        // Formatear fechas
        if ($empleado['FECHA_INGRESO']) {
            $empleado['FECHA_INGRESO'] = date('d/m/Y', strtotime($empleado['FECHA_INGRESO']));
        }

        // Obtener todos los horarios del empleado (tanto tradicionales como personalizados)
        $horarios = [];

        // Primero obtener horarios tradicionales
        $sqlHorariosTrad = "
            SELECT
                eh.ID_EMPLEADO,
                eh.ID_HORARIO,
                eh.FECHA_DESDE,
                eh.FECHA_HASTA,
                eh.ACTIVO,
                h.NOMBRE as nombre_horario,
                TIME_FORMAT(h.HORA_ENTRADA, '%H:%i') as hora_entrada,
                TIME_FORMAT(h.HORA_SALIDA, '%H:%i') as hora_salida,
                h.TOLERANCIA,
                'tradicional' as tipo_horario
            FROM EMPLEADO_HORARIO eh
            INNER JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE eh.ID_EMPLEADO = ?
            ORDER BY eh.ACTIVO DESC, eh.FECHA_DESDE DESC
        ";

        $stmtHorariosTrad = $conn->prepare($sqlHorariosTrad);
        $stmtHorariosTrad->execute([$codigoEmpleado]);
        $horariosTrad = $stmtHorariosTrad->fetchAll(PDO::FETCH_ASSOC);

        foreach ($horariosTrad as $row) {
            // Formatear fechas
            if ($row['FECHA_DESDE']) {
                $row['FECHA_DESDE'] = date('d/m/Y', strtotime($row['FECHA_DESDE']));
            }
            if ($row['FECHA_HASTA']) {
                $row['FECHA_HASTA'] = date('d/m/Y', strtotime($row['FECHA_HASTA']));
            }

            $horario = [
                'id_empleado_horario' => $row['ID_EMPLEADO'] . '_' . $row['ID_HORARIO'],
                'hora_entrada' => $row['hora_entrada'],
                'hora_salida' => $row['hora_salida'],
                'tolerancia' => $row['TOLERANCIA'] ?: 0,
                'dias_semana' => 'Lunes a Domingo', // Horarios tradicionales aplican todos los días
                'dias_semana_legibles' => 'Lunes a Domingo',
                'fecha_inicio_vigencia' => $row['FECHA_DESDE'],
                'fecha_fin_vigencia' => $row['FECHA_HASTA'] ?: 'Indefinida',
                'activo' => $row['ACTIVO'] === 'S',
                'id_horario' => $row['ID_HORARIO'],
                'nombre_horario' => $row['nombre_horario'],
                'descripcion' => 'Horario tradicional',
                'tipo' => $row['tipo_horario']
            ];

            $horarios[] = $horario;
        }

        // Luego obtener horarios personalizados
        $sqlHorariosPers = "
            SELECT
                ehp.ID_EMPLEADO_HORARIO,
                ehp.ID_EMPLEADO,
                ehp.ID_DIA,
                ehp.HORA_ENTRADA,
                ehp.HORA_SALIDA,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.FECHA_DESDE,
                ehp.FECHA_HASTA,
                ehp.ACTIVO,
                'personalizado' as tipo_horario
            FROM empleado_horario_personalizado ehp
            WHERE ehp.ID_EMPLEADO = ?
            ORDER BY ehp.ACTIVO DESC, ehp.FECHA_DESDE DESC
        ";

        $stmtHorariosPers = $conn->prepare($sqlHorariosPers);
        $stmtHorariosPers->execute([$codigoEmpleado]);
        $horariosPers = $stmtHorariosPers->fetchAll(PDO::FETCH_ASSOC);

        foreach ($horariosPers as $row) {
            // Formatear fechas
            if ($row['FECHA_DESDE']) {
                $row['FECHA_DESDE'] = date('d/m/Y', strtotime($row['FECHA_DESDE']));
            }
            if ($row['FECHA_HASTA']) {
                $row['FECHA_HASTA'] = date('d/m/Y', strtotime($row['FECHA_HASTA']));
            }

            // Determinar días de la semana de forma legible
            $diasLegibles = 'Día ' . $row['ID_DIA']; // Simplificado por ahora

            $horario = [
                'id_empleado_horario' => $row['ID_EMPLEADO_HORARIO'],
                'hora_entrada' => date('H:i', strtotime($row['HORA_ENTRADA'])),
                'hora_salida' => date('H:i', strtotime($row['HORA_SALIDA'])),
                'tolerancia' => $row['TOLERANCIA'] ?: 0,
                'dias_semana' => $row['ID_DIA'],
                'dias_semana_legibles' => $diasLegibles,
                'fecha_inicio_vigencia' => $row['FECHA_DESDE'],
                'fecha_fin_vigencia' => $row['FECHA_HASTA'] ?: 'Indefinida',
                'activo' => $row['ACTIVO'] === 'S',
                'id_horario' => null,
                'nombre_horario' => $row['NOMBRE_TURNO'] ?: 'Horario Personalizado',
                'descripcion' => 'Horario personalizado',
                'tipo' => $row['tipo_horario']
            ];

            $horarios[] = $horario;
        }

        // Obtener estadísticas de asistencia del último mes
        $estadisticas = getEmployeeAttendanceStats($codigoEmpleado, $conn);

        return [
            'success' => true,
            'empleado' => [
                'codigo' => $empleado['ID_EMPLEADO'],
                'dni' => $empleado['DNI'],
                'nombre' => $empleado['NOMBRE'],
                'apellido' => $empleado['APELLIDO'],
                'nombre_completo' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'fecha_ingreso' => $empleado['FECHA_INGRESO'],
                'estado' => $empleado['ESTADO'],
                'sede' => [
                    'id' => $empleado['ID_SEDE'],
                    'nombre' => $empleado['sede']
                ],
                'establecimiento' => [
                    'id' => $empleado['ID_ESTABLECIMIENTO'],
                    'nombre' => $empleado['establecimiento']
                ],
                'correo' => $empleado['CORREO'],
                'telefono' => $empleado['TELEFONO'],
                'horarios' => $horarios,
                'estadisticas' => $estadisticas
            ]
        ];

    } catch (Exception $e) {
        error_log("Error en getEmployeeDetails: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al obtener detalles del empleado: ' . $e->getMessage()
        ];
    }
}

/**
 * Formatea los días de la semana de forma legible
 */
function formatDiasSemana($diasString) {
    if (!$diasString) {
        return 'No especificado';
    }

    $diasMap = [
        'L' => 'Lunes',
        'M' => 'Martes',
        'X' => 'Miércoles',
        'J' => 'Jueves',
        'V' => 'Viernes',
        'S' => 'Sábado',
        'D' => 'Domingo'
    ];

    $dias = str_split($diasString);
    $diasLegibles = [];

    foreach ($dias as $dia) {
        if (isset($diasMap[$dia])) {
            $diasLegibles[] = $diasMap[$dia];
        }
    }

    return implode(', ', $diasLegibles);
}

/**
 * Obtiene estadísticas de asistencia del empleado
 */
function getEmployeeAttendanceStats($codigoEmpleado, $conn) {
    try {
        // Estadísticas del último mes
        $sql = "
            SELECT
                COUNT(DISTINCT DATE(FECHA)) as dias_trabajados,
                COUNT(CASE WHEN TIPO = 'ENTRADA' THEN 1 END) as entradas_registradas,
                COUNT(CASE WHEN TIPO = 'SALIDA' THEN 1 END) as salidas_registradas,
                0 as horas_promedio_diarias
            FROM ASISTENCIA
            WHERE ID_EMPLEADO = ?
            AND FECHA >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$codigoEmpleado]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'dias_trabajados_ultimo_mes' => (int)$stats['dias_trabajados'],
            'entradas_registradas' => (int)$stats['entradas_registradas'],
            'salidas_registradas' => (int)$stats['salidas_registradas'],
            'horas_promedio_diarias' => round($stats['horas_promedio_diarias'] ?: 0, 2)
        ];

    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        return [
            'dias_trabajados_ultimo_mes' => 0,
            'entradas_registradas' => 0,
            'salidas_registradas' => 0,
            'horas_promedio_diarias' => 0
        ];
    }
}

/**
 * Maneja las solicitudes GET
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $codigoEmpleado = $_GET['codigo'] ?? null;

    if (!$codigoEmpleado) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Código de empleado requerido'
        ]);
        exit;
    }

    $resultado = getEmployeeDetails($codigoEmpleado);
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