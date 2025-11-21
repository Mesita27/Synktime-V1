<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación
requireAuth();

// Configurar respuesta JSON
header('Content-Type: application/json');

try {
    // Obtener estadísticas biométricas
    
    // Total de empleados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleado WHERE ESTADO = 'A'");
    $stmt->execute();
    $totalEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Empleados con datos biométricos enrollados
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT employee_id) as enrolled
        FROM employee_biometrics
    ");
    $stmt->execute();
    $enrolledEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['enrolled'];
    
    // Empleados pendientes
    $pendingEmployees = $totalEmployees - $enrolledEmployees;
    
    // Porcentaje de enrolamiento
    $percentage = $totalEmployees > 0 ? round(($enrolledEmployees / $totalEmployees) * 100) : 0;
    
    // Estadísticas por tipo
    $stmt = $conn->prepare("
        SELECT
            biometric_type,
            COUNT(DISTINCT employee_id) as count
        FROM employee_biometrics
        GROUP BY biometric_type
    ");
    $stmt->execute();
    $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $facialCount = 0;
    $fingerprintCount = 0;
    
    foreach ($byType as $type) {
        if ($type['biometric_type'] === 'face') {
            $facialCount = $type['count'];
        } elseif ($type['biometric_type'] === 'fingerprint') {
            $fingerprintCount = $type['count'];
        }
    }
    
    $response = [
        'success' => true,
        'stats' => [
            'total' => $totalEmployees,
            'enrolled' => $enrolledEmployees,
            'pending' => $pendingEmployees,
            'percentage' => $percentage,
            'facial' => $facialCount,
            'fingerprint' => $fingerprintCount
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error obteniendo estadísticas biométricas',
        'error' => $e->getMessage()
    ]);
}
?>
