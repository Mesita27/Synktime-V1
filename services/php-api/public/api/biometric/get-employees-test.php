<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Parámetros de filtro
    $filtros = [
        'codigo' => isset($_GET['codigo']) ? intval($_GET['codigo']) : null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => isset($_GET['sede']) ? intval($_GET['sede']) : null,
        'establecimiento' => isset($_GET['establecimiento']) ? intval($_GET['establecimiento']) : null,
        'estado' => $_GET['estado'] ?? null
    ];

    // Construcción de la consulta
    $where = [];
    $params = [];

    // Aplicar filtros básicos
    if (!empty($filtros['codigo'])) {
        $where[] = "e.ID_EMPLEADO = ?";
        $params[] = $filtros['codigo'];
    }

    if (!empty($filtros['nombre'])) {
        $where[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
        $params[] = "%{$filtros['nombre']}%";
        $params[] = "%{$filtros['nombre']}%";
    }

    if (!empty($filtros['sede'])) {
        $where[] = "s.ID_SEDE = ?";
        $params[] = $filtros['sede'];
    }

    if (!empty($filtros['establecimiento'])) {
        $where[] = "est.ID_ESTABLECIMIENTO = ?";
        $params[] = $filtros['establecimiento'];
    }

    // Por defecto, mostrar solo empleados activos
    $where[] = "e.ACTIVO = 'S'";

    $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

    // Obtener límite si se proporciona
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $limit = max(1, min(500, $limit));

    $query = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.DNI AS IDENTIFICACION,
            e.NOMBRE,
            e.APELLIDO,
            e.ESTADO,
            e.ACTIVO,
            e.FECHA_INGRESO,
            est.NOMBRE AS ESTABLECIMIENTO,
            est.ID_ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            s.ID_SEDE,
            CASE WHEN eb_facial.id IS NOT NULL THEN 1 ELSE 0 END as facial_enrolled,
            CASE WHEN eb_huella.id IS NOT NULL THEN 1 ELSE 0 END as fingerprint_enrolled,
            CASE WHEN eb_rfid.id IS NOT NULL THEN 1 ELSE 0 END as rfid_enrolled,
            GREATEST(
                COALESCE(eb_facial.updated_at, '2000-01-01'),
                COALESCE(eb_huella.updated_at, '2000-01-01'),
                COALESCE(eb_rfid.updated_at, '2000-01-01')
            ) as last_update
        FROM
            empleado e
        LEFT JOIN
            establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN
            sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN
            employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id
            AND eb_facial.biometric_type = 'face'
        LEFT JOIN
            employee_biometrics eb_huella ON e.ID_EMPLEADO = eb_huella.employee_id
            AND eb_huella.biometric_type = 'fingerprint'
        LEFT JOIN
            employee_biometrics eb_rfid ON e.ID_EMPLEADO = eb_rfid.employee_id
            AND eb_rfid.biometric_type = 'rfid'
        {$whereClause}
        ORDER BY
            e.NOMBRE, e.APELLIDO
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar empleados para agregar campos calculados
    foreach ($employees as &$employee) {
        // Generar código si no existe
        if (!isset($employee['CODIGO']) || empty($employee['CODIGO'])) {
            $employee['CODIGO'] = 'EMP' . str_pad($employee['ID_EMPLEADO'], 4, '0', STR_PAD_LEFT);
        }

        // Formatear nombre completo
        $employee['NOMBRE_COMPLETO'] = trim($employee['NOMBRE'] . ' ' . $employee['APELLIDO']);

        // Calcular estado biométrico general
        $biometric_count = 0;
        if ($employee['facial_enrolled']) $biometric_count++;
        if ($employee['fingerprint_enrolled']) $biometric_count++;
        if ($employee['rfid_enrolled']) $biometric_count++;

        if ($biometric_count === 3) {
            $employee['estado_biometrico'] = 'completo';
        } elseif ($biometric_count > 0) {
            $employee['estado_biometrico'] = 'parcial';
        } else {
            $employee['estado_biometrico'] = 'pendiente';
        }

        // Formatear fecha de última actualización
        if ($employee['last_update'] && $employee['last_update'] !== '2000-01-01 00:00:00') {
            $employee['last_update_formatted'] = date('d/m/Y H:i', strtotime($employee['last_update']));
        } else {
            $employee['last_update_formatted'] = 'Nunca';
        }
    }

    // Obtener estadísticas
    $statsQuery = "
        SELECT
            COUNT(*) as total_employees,
            SUM(CASE WHEN facial_enrolled + fingerprint_enrolled + rfid_enrolled = 3 THEN 1 ELSE 0 END) as fully_enrolled,
            SUM(CASE WHEN facial_enrolled + fingerprint_enrolled + rfid_enrolled > 0 THEN 1 ELSE 0 END) as partially_enrolled,
            ROUND(
                (SUM(facial_enrolled + fingerprint_enrolled + rfid_enrolled) * 100.0) / (COUNT(*) * 3),
                1
            ) as overall_completion_percentage
        FROM (
            SELECT
                CASE WHEN eb_facial.id IS NOT NULL THEN 1 ELSE 0 END as facial_enrolled,
                CASE WHEN eb_huella.id IS NOT NULL THEN 1 ELSE 0 END as fingerprint_enrolled,
                CASE WHEN eb_rfid.id IS NOT NULL THEN 1 ELSE 0 END as rfid_enrolled
            FROM empleado e
            LEFT JOIN employee_biometrics eb_facial ON e.ID_EMPLEADO = eb_facial.employee_id
                AND eb_facial.biometric_type = 'face'
            LEFT JOIN employee_biometrics eb_huella ON e.ID_EMPLEADO = eb_huella.employee_id
                AND eb_huella.biometric_type = 'fingerprint'
            LEFT JOIN employee_biometrics eb_rfid ON e.ID_EMPLEADO = eb_rfid.employee_id
                AND eb_rfid.biometric_type = 'rfid'
            WHERE e.ACTIVO = 'S'
        ) stats
    ";

    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'total' => count($employees),
        'stats' => [
            'total_employees' => (int)$stats['total_employees'],
            'enrolled_count' => (int)$stats['partially_enrolled'],
            'pending_count' => (int)($stats['total_employees'] - $stats['partially_enrolled']),
            'enrollment_percentage' => (float)$stats['overall_completion_percentage']
        ],
        'filters_applied' => $filtros,
        'query' => $query
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
