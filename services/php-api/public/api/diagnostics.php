<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'checks' => []
];

// Verificar archivos de configuración
$configChecks = [
    'database_config' => file_exists('../../config/database.php'),
    'biometric_api' => file_exists('biometric/get-employees-verification.php'),
    'attendance_api' => file_exists('attendance/stats.php'),
    'attendance_record' => file_exists('attendance/record.php')
];

$diagnostics['checks']['files'] = $configChecks;

// Verificar conexión a base de datos
$dbStatus = 'unknown';
$dbError = null;
try {
    if (file_exists('../../config/database.php')) {
        require_once '../../config/database.php';
        if (isset($pdo)) {
            $stmt = $pdo->query("SELECT 1");
            $dbStatus = 'connected';
        } else {
            $dbStatus = 'no_pdo';
            $dbError = 'Variable $pdo no está definida';
        }
    } else {
        $dbStatus = 'config_missing';
        $dbError = 'Archivo database.php no encontrado';
    }
} catch (Exception $e) {
    $dbStatus = 'error';
    $dbError = $e->getMessage();
}

$diagnostics['checks']['database'] = [
    'status' => $dbStatus,
    'error' => $dbError
];

// Verificar tablas requeridas
$tablesStatus = [];
if ($dbStatus === 'connected') {
    $requiredTables = ['empleado', 'employee_biometrics', 'asistencia'];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            $tablesStatus[$table] = $exists ? 'exists' : 'missing';
        } catch (Exception $e) {
            $tablesStatus[$table] = 'error: ' . $e->getMessage();
        }
    }
}

$diagnostics['checks']['tables'] = $tablesStatus;

// Verificar extensiones PHP requeridas
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$extensionsStatus = [];
foreach ($requiredExtensions as $ext) {
    $extensionsStatus[$ext] = extension_loaded($ext) ? 'loaded' : 'missing';
}

$diagnostics['checks']['extensions'] = $extensionsStatus;

// Verificar permisos de escritura
$writeChecks = [
    'logs_dir' => is_writable('../../logs/') || is_writable('../logs/'),
    'backups_dir' => is_writable('../../backups/') || is_writable('../backups/')
];

$diagnostics['checks']['write_permissions'] = $writeChecks;

// Resumen general
$diagnostics['summary'] = [
    'overall_status' => ($dbStatus === 'connected' && !in_array('missing', $tablesStatus) && !in_array('missing', $extensionsStatus)) ? 'healthy' : 'issues_detected',
    'critical_issues' => []
];

if ($dbStatus !== 'connected') {
    $diagnostics['summary']['critical_issues'][] = 'Database connection failed';
}

if (in_array('missing', $tablesStatus)) {
    $diagnostics['summary']['critical_issues'][] = 'Required database tables missing';
}

if (in_array('missing', $extensionsStatus)) {
    $diagnostics['summary']['critical_issues'][] = 'Required PHP extensions missing';
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
?>
