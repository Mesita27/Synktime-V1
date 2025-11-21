<?php
/**
 * Bootstrap file for Synktime PHP API
 * 
 * This file sets up the autoloader, defines paths, and initializes
 * the application environment.
 */

declare(strict_types=1);

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('SRC_PATH', BASE_PATH . '/src');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// Load Composer autoloader
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Timezone configuration
date_default_timezone_set('America/Bogota');

// Error reporting (adjust based on environment)
$env = getenv('APP_ENV') ?: 'production';
if ($env === 'development' || $env === 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'cookie_samesite' => 'Lax',
    ]);
}

// Load legacy path compatibility
// For gradual migration, map old paths to new locations
$legacyPaths = [
    'config/database.php' => SRC_PATH . '/Config/database.php',
    'config/timezone.php' => SRC_PATH . '/Config/timezone.php',
    'auth/session.php' => SRC_PATH . '/Auth/session.php',
    'utils/attendance_status_utils.php' => SRC_PATH . '/Utils/attendance_status_utils.php',
    'utils/horario_utils.php' => SRC_PATH . '/Utils/horario_utils.php',
    'utils/justificaciones_utils.php' => SRC_PATH . '/Utils/justificaciones_utils.php',
];

// Helper function for legacy includes
function resolve_legacy_path(string $path): string
{
    global $legacyPaths;
    
    // If it's already an absolute path, return it
    if (strpos($path, '/') === 0) {
        return $path;
    }
    
    // Check if it's a known legacy path
    if (isset($legacyPaths[$path])) {
        return $legacyPaths[$path];
    }
    
    // Try to resolve relative to current location
    $basePath = dirname(__DIR__);
    $resolved = $basePath . '/' . $path;
    
    if (file_exists($resolved)) {
        return $resolved;
    }
    
    // Return original path if not found
    return $path;
}
