<?php
/**
 * API: Get pending overtime hours for approval
 * Only accessible by ADMIN users
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Temporarily disable error output to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/generar-horas-extras-lib.php';

    // Simple authentication check
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'ADMIN') {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol ADMIN']);
        exit;
    }

    // Get filters from POST data
    $fechaDesde = isset($_POST['fechaDesde']) ? $_POST['fechaDesde'] : null;
    $fechaHasta = isset($_POST['fechaHasta']) ? $_POST['fechaHasta'] : null;
    $sedeId = isset($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;
    $establecimientoId = isset($_POST['establecimiento_id']) ? (int)$_POST['establecimiento_id'] : null;
    $empleados = isset($_POST['empleados']) ? $_POST['empleados'] : [];
    $estadoAprobacion = isset($_POST['estado_aprobacion']) ? $_POST['estado_aprobacion'] : null;

    // ===== GENERAR HORAS EXTRAS AUTOMÁTICAMENTE =====
    // DESACTIVADO: Esta generación automática usa lógica simple que entra en conflicto
    // con el sistema jerárquico de calculateHoursWithHierarchy en get-horas.php
    // generarHorasExtrasAutomaticamente($conn, $fechaDesde, $fechaHasta, $sedeId, $establecimientoId, $empleados);

    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'horas_extras_aprobacion'");
    if ($tableCheck->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'La tabla horas_extras_aprobacion no existe en la base de datos',
            'debug' => 'Table not found'
        ]);
        exit;
    }

    // Get company ID from session
    $empresaId = isset($_SESSION['id_empresa']) ? (int)$_SESSION['id_empresa'] : 1;

    // Build optimized query - single query with all JOINs
    $query = "
        SELECT
            hea.ID_HORAS_EXTRAS,
            hea.ID_EMPLEADO,
            hea.ID_EMPLEADO_HORARIO,
            hea.FECHA,
            hea.HORA_INICIO,
            hea.HORA_FIN,
            hea.HORAS_EXTRAS,
            hea.TIPO_EXTRA,
            hea.TIPO_HORARIO,
            hea.ESTADO_APROBACION,
            hea.OBSERVACIONES,
            hea.CREATED_AT,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
            est.ID_ESTABLECIMIENTO,
            s.NOMBRE as SEDE_NOMBRE,
            s.ID_SEDE,
            ehp.NOMBRE_TURNO as HORARIO_NOMBRE,
            ehp.HORA_ENTRADA as HORARIO_ENTRADA,
            ehp.HORA_SALIDA as HORARIO_SALIDA,
            ehp.ID_DIA as HORARIO_DIA,
            ehp.ORDEN_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA,
            ehp.ACTIVO as HORARIO_ACTIVO
        FROM horas_extras_aprobacion hea
        JOIN empleado e ON hea.ID_EMPLEADO = e.ID_EMPLEADO
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN empleado_horario_personalizado ehp ON hea.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE s.ID_EMPRESA = ?
    ";

    $params = [$empresaId];

    // Add filters
    if ($estadoAprobacion) {
        $query .= " AND hea.ESTADO_APROBACION = ?";
        $params[] = $estadoAprobacion;
    }

    if ($fechaDesde) {
        $query .= " AND hea.FECHA >= ?";
        $params[] = $fechaDesde;
    }

    if ($fechaHasta) {
        $query .= " AND hea.FECHA <= ?";
        $params[] = $fechaHasta;
    }

    if ($sedeId) {
        $query .= " AND s.ID_SEDE = ?";
        $params[] = $sedeId;
    }

    if ($establecimientoId) {
        $query .= " AND est.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimientoId;
    }

    if (!empty($empleados)) {
        $placeholders = str_repeat('?,', count($empleados) - 1) . '?';
        $query .= " AND hea.ID_EMPLEADO IN ($placeholders)";
        $params = array_merge($params, $empleados);
    }

    $query .= " ORDER BY hea.FECHA DESC, hea.CREATED_AT DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $horasExtras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process data into structured arrays like get-horas.php
    $empleadosData = [];
    $estadisticasAgregadas = [
        'total_horas_extras' => 0,
        'horas_pendientes' => 0,
        'horas_aprobadas' => 0,
        'horas_rechazadas' => 0,
        'empleados_afectados' => 0
    ];

    $horasExtrasPorFecha = [];
    $empleadosUnicos = [];

    foreach ($horasExtras as $horaExtra) {
        $idEmpleado = $horaExtra['ID_EMPLEADO'];
        $fecha = $horaExtra['FECHA'];

        // Track unique employees
        if (!in_array($idEmpleado, $empleadosUnicos)) {
            $empleadosUnicos[] = $idEmpleado;
        }

        // Group by date for horas_extras_por_fecha
        if (!isset($horasExtrasPorFecha[$fecha])) {
            $horasExtrasPorFecha[$fecha] = [];
        }

        // Create processed overtime record
        $registroExtra = [
            'id' => $horaExtra['ID_HORAS_EXTRAS'],
            'empleado_id' => $idEmpleado,
            'empleado_nombre' => $horaExtra['NOMBRE'] . ' ' . $horaExtra['APELLIDO'],
            'fecha' => $fecha,
            'hora_inicio' => $horaExtra['HORA_INICIO'],
            'hora_fin' => $horaExtra['HORA_FIN'],
            'horas_extras' => floatval($horaExtra['HORAS_EXTRAS']),
            'tipo_extra' => $horaExtra['TIPO_EXTRA'],
            'tipo_horario' => $horaExtra['TIPO_HORARIO'],
            'estado_aprobacion' => $horaExtra['ESTADO_APROBACION'],
            'observaciones' => $horaExtra['OBSERVACIONES'],
            'establecimiento' => $horaExtra['ESTABLECIMIENTO_NOMBRE'],
            'sede' => $horaExtra['SEDE_NOMBRE'],
            'horario' => [
                'id_empleado_horario' => $horaExtra['ID_EMPLEADO_HORARIO'],
                'nombre' => $horaExtra['HORARIO_NOMBRE'],
                'hora_entrada' => $horaExtra['HORARIO_ENTRADA'],
                'hora_salida' => $horaExtra['HORARIO_SALIDA'],
                'dia' => $horaExtra['HORARIO_DIA'],
                'orden_turno' => $horaExtra['ORDEN_TURNO']
            ],
            'created_at' => $horaExtra['CREATED_AT'],
            'dia_semana_num' => $horaExtra['DIA_SEMANA_NUM']
        ];

        $horasExtrasPorFecha[$fecha][] = $registroExtra;

        // Update statistics
        $estadisticasAgregadas['total_horas_extras'] += floatval($horaExtra['HORAS_EXTRAS']);

        switch ($horaExtra['ESTADO_APROBACION']) {
            case 'pendiente':
                $estadisticasAgregadas['horas_pendientes'] += floatval($horaExtra['HORAS_EXTRAS']);
                break;
            case 'aprobada':
                $estadisticasAgregadas['horas_aprobadas'] += floatval($horaExtra['HORAS_EXTRAS']);
                break;
            case 'rechazada':
                $estadisticasAgregadas['horas_rechazadas'] += floatval($horaExtra['HORAS_EXTRAS']);
                break;
        }
    }

    $estadisticasAgregadas['empleados_afectados'] = count($empleadosUnicos);

    // Round statistics to 2 decimal places
    foreach ($estadisticasAgregadas as $key => $value) {
        if ($key !== 'empleados_afectados') {
            $estadisticasAgregadas[$key] = round($value, 2);
        }
    }

    // Create response in the same format as get-horas.php
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $estadisticasAgregadas,
            'horas_extras_por_fecha' => $horasExtrasPorFecha,
            'empleados' => $empleadosUnicos,
            'periodo' => [
                'fecha_inicio' => $fechaDesde,
                'fecha_fin' => $fechaHasta
            ]
        ],
        'total' => count($horasExtras)
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Error in get-horas-extras-pendientes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>