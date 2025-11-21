<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar si es una solicitud OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Verificar que el archivo de configuración existe
    $configPath = '../../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception("Archivo de configuración no encontrado: $configPath");
    }

    require_once $configPath;

    // Verificar que la conexión PDO existe
    if (!isset($pdo)) {
        throw new Exception('Conexión a base de datos no disponible');
    }

    // Crear tabla attendance si no existe
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            verification_method ENUM('facial', 'fingerprint', 'rfid', 'manual') NOT NULL,
            confidence DECIMAL(5,2) NULL,
            timestamp DATETIME NOT NULL,
            status ENUM('success', 'failed', 'pending') DEFAULT 'success',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES empleado(ID_EMPLEADO) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableQuery);

    // Estadísticas generales
    $totalQuery = "SELECT COUNT(*) as total FROM attendance WHERE status = 'success'";
    $totalStmt = $pdo->query($totalQuery);
    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);

    // Estadísticas de hoy
    $todayQuery = "SELECT COUNT(*) as today FROM attendance WHERE DATE(timestamp) = CURDATE() AND status = 'success'";
    $todayStmt = $pdo->query($todayQuery);
    $todayResult = $todayStmt->fetch(PDO::FETCH_ASSOC);

    // Estadísticas por método
    $methodQuery = "
        SELECT
            verification_method,
            COUNT(*) as count
        FROM attendance
        WHERE status = 'success'
        GROUP BY verification_method
    ";
    $methodStmt = $pdo->query($methodQuery);
    $methodResults = $methodStmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas de empleados únicos hoy
    $uniqueTodayQuery = "
        SELECT COUNT(DISTINCT employee_id) as unique_today
        FROM attendance
        WHERE DATE(timestamp) = CURDATE() AND status = 'success'
    ";
    $uniqueTodayStmt = $pdo->query($uniqueTodayQuery);
    $uniqueTodayResult = $uniqueTodayStmt->fetch(PDO::FETCH_ASSOC);

    // Últimas verificaciones
    $recentQuery = "
        SELECT
            a.id,
            a.employee_id,
            a.verification_method,
            a.confidence,
            a.timestamp,
            e.NOMBRE,
            e.APELLIDO
        FROM attendance a
        JOIN empleado e ON a.employee_id = e.ID_EMPLEADO
        WHERE a.status = 'success'
        ORDER BY a.timestamp DESC
        LIMIT 10
    ";
    $recentStmt = $pdo->query($recentQuery);
    $recentResults = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparar respuesta
    $methodStats = [];
    foreach ($methodResults as $method) {
        $methodStats[$method['verification_method']] = $method['count'];
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_verifications' => (int)$totalResult['total'],
            'today_verifications' => (int)$todayResult['today'],
            'unique_employees_today' => (int)$uniqueTodayResult['unique_today'],
            'pending_verifications' => 0, // Por ahora 0, se puede calcular si hay verificaciones pendientes
            'by_method' => [
                'facial' => (int)($methodStats['facial'] ?? 0),
                'fingerprint' => (int)($methodStats['fingerprint'] ?? 0),
                'rfid' => (int)($methodStats['rfid'] ?? 0),
                'manual' => (int)($methodStats['manual'] ?? 0)
            ]
        ],
        'recent_verifications' => $recentResults
    ]);

} catch (PDOException $e) {
    error_log("Database error in attendance-stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_type' => 'database'
    ]);
} catch (Exception $e) {
    error_log("General error in attendance-stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage(),
        'error_type' => 'general'
    ]);
}
?>
