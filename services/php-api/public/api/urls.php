<?php
/**
 * Configuración de URLs de API
 * Versión: 2.0
 * Descripción: Centraliza todas las URLs de las APIs del sistema
 */

// Configuración del dominio base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// URL base de la aplicación
define('APP_BASE_URL', $protocol . '://' . $host . $base_path);

// URLs de APIs
define('API_BASE_URL', APP_BASE_URL . '/api');

// APIs de Asistencia
define('API_ATTENDANCE_REGISTER', API_BASE_URL . '/attendance/register-biometric-new.php');
define('API_ATTENDANCE_LIST', API_BASE_URL . '/attendance/list.php');
define('API_ATTENDANCE_DETAIL', API_BASE_URL . '/attendance/detail.php');
define('API_ATTENDANCE_STATS', API_BASE_URL . '/attendance/stats.php');

// APIs Biométricas
define('API_BIOMETRIC_VERIFY', API_BASE_URL . '/biometric/verify.php');
define('API_BIOMETRIC_ENROLL', API_BASE_URL . '/biometric/enroll.php');
define('API_BIOMETRIC_STATUS', API_BASE_URL . '/biometric/status.php');

// APIs de Empleados
define('API_EMPLOYEE_GET', API_BASE_URL . '/employee/get.php');
define('API_EMPLOYEE_LIST', API_BASE_URL . '/employee/list.php');
define('API_EMPLOYEE_SEARCH', API_BASE_URL . '/employee/search.php');

// Función para obtener la configuración como JSON
function getApiConfig() {
    return [
        'base_url' => APP_BASE_URL,
        'api_base_url' => API_BASE_URL,
        'endpoints' => [
            'attendance' => [
                'register' => API_ATTENDANCE_REGISTER,
                'list' => API_ATTENDANCE_LIST,
                'detail' => API_ATTENDANCE_DETAIL,
                'stats' => API_ATTENDANCE_STATS
            ],
            'biometric' => [
                'verify' => API_BIOMETRIC_VERIFY,
                'enroll' => API_BIOMETRIC_ENROLL,
                'status' => API_BIOMETRIC_STATUS
            ],
            'employee' => [
                'get' => API_EMPLOYEE_GET,
                'list' => API_EMPLOYEE_LIST,
                'search' => API_EMPLOYEE_SEARCH
            ]
        ]
    ];
}

// Si se llama directamente, retornar configuración JSON
if (basename($_SERVER['SCRIPT_NAME']) === 'urls.php') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(getApiConfig(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}
?>