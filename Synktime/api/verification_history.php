<?php
/**
 * API para obtener historial de verificaciones biométricas
 * SNKTIME Biometric System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use GET.'
    ]);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';

try {
    // Obtener parámetros
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $verificationType = isset($_GET['type']) ? $_GET['type'] : null;
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

    // Validar límite
    if ($limit < 1 || $limit > 1000) {
        $limit = 50;
    }

    // Construir consulta
    $sql = "
        SELECT
            v.id,
            v.employee_id,
            CONCAT(e.nombre, ' ', e.apellido) as employee_name,
            v.verification_type,
            v.success,
            v.confidence,
            v.attendance_id,
            v.ip_address,
            v.additional_data,
            v.created_at
        FROM biometric_verification_log v
        LEFT JOIN empleado e ON v.employee_id = e.id
        WHERE 1=1
    ";

    $params = [];

    if ($employeeId) {
        $sql .= " AND v.employee_id = ?";
        $params[] = $employeeId;
    }

    if ($verificationType) {
        $sql .= " AND v.verification_type LIKE ?";
        $params[] = '%' . $verificationType . '%';
    }

    if ($dateFrom) {
        $sql .= " AND DATE(v.created_at) >= ?";
        $params[] = $dateFrom;
    }

    if ($dateTo) {
        $sql .= " AND DATE(v.created_at) <= ?";
        $params[] = $dateTo;
    }

    $sql .= " ORDER BY v.created_at DESC LIMIT ?";

    $stmt = $pdo->prepare($sql);
    $params[] = $limit;

    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar registros para formato más amigable
    $processedRecords = array_map(function($record) {
        return [
            'id' => $record['id'],
            'employee_id' => $record['employee_id'],
            'employee_name' => $record['employee_name'] ?: 'Empleado desconocido',
            'verification_type' => $record['verification_type'],
            'success' => (bool)$record['success'],
            'confidence' => $record['confidence'] ? (float)$record['confidence'] : null,
            'attendance_id' => $record['attendance_id'],
            'ip_address' => $record['ip_address'],
            'created_at' => $record['created_at'],
            'additional_data' => $record['additional_data'] ? json_decode($record['additional_data'], true) : null
        ];
    }, $records);

    // Estadísticas
    $totalRecords = count($processedRecords);
    $successfulVerifications = count(array_filter($processedRecords, function($record) {
        return $record['success'];
    }));

    $stats = [
        'total' => $totalRecords,
        'successful' => $successfulVerifications,
        'failed' => $totalRecords - $successfulVerifications,
        'success_rate' => $totalRecords > 0 ? round(($successfulVerifications / $totalRecords) * 100, 2) : 0
    ];

    echo json_encode([
        'success' => true,
        'data' => $processedRecords,
        'stats' => $stats,
        'limit' => $limit,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log('Error al obtener historial de verificaciones: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}
?>
