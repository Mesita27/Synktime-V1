<?php
/**
 * Sistema de manejo de sesiones para SynkTime
 */

// Ajustar cabeceras cuando SynkTime corre detrás de un proxy (ej. Nginx en HTTPS)
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protoHeader = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));

    if ($protoHeader === 'https') {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;
    }
}

if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
}

if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
    $_SERVER['SERVER_PORT'] = (int) trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PORT'])[0]);
}

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwardedFor = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $_SERVER['REMOTE_ADDR'] = trim($forwardedFor[0]);
}

// Prevenir cualquier output antes de session_start()
if (!headers_sent()) {
    ob_start();
}

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (isset($protoHeader) && $protoHeader === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

ini_set('session.cookie_secure', $isHttps ? 1 : 0);

/**
 * Inicializa la sesión
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica si el usuario está autenticado
 */
function isAuthenticated() {
    initSession();
    return isset($_SESSION['user_id']) &&
           isset($_SESSION['username']) &&
           !empty($_SESSION['user_id']);
}

/**
 * Obtiene la conexión a la base de datos
 */
function getConnection() {
    // Incluir el archivo de conexión existente
    require_once __DIR__ . '/../config/database.php';
    
    // Retornar la conexión global $conn
    global $conn;
    return $conn;
}

/**
 * Registra actividad en el log
 */
function logActivity($accion, $detalle = '') {
    try {
        $conn = getConnection();
        
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("
                INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) 
                VALUES (:userId, :accion, :detalle)
            ");
            
            $stmt->bindParam(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
            $stmt->bindParam(':detalle', $detalle, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

/**
 * Inicia sesión para un usuario
 */
function startUserSession($userData) {
    initSession();
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
    
    // Establecer datos de sesión
    $_SESSION['user_id'] = $userData['ID_USUARIO'];
    $_SESSION['username'] = $userData['USERNAME'];
    $_SESSION['nombre_completo'] = $userData['NOMBRE_COMPLETO'];
    $_SESSION['email'] = $userData['EMAIL'];
    $_SESSION['rol'] = $userData['ROL'];
    $_SESSION['id_empresa'] = $userData['ID_EMPRESA'];
    $_SESSION['empresa_nombre'] = $userData['EMPRESA_NOMBRE'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Cierra la sesión del usuario
 */
function endUserSession() {
    initSession();
    
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    return true;
}

/**
 * Actualiza la actividad del usuario
 */
function updateUserActivity() {
    if (isAuthenticated()) {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Verifica si la sesión ha expirado (opcional)
 */
function isSessionExpired($timeout = 7200) { // 2 horas por defecto
    if (!isAuthenticated()) {
        return true;
    }
    
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > $timeout) {
        return true;
    }
    
    return false;
}

/**
 * Requiere autenticación - redirige al login si no está autenticado
 */
function requireAuth() {
    initSession(); // Asegurar que la sesión está iniciada
    
    if (!isAuthenticated() || isSessionExpired()) {
        // Limpiar sesión expirada
        if (isSessionExpired()) {
            endUserSession();
        }
        
        // Verificar si es una petición AJAX
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Si es AJAX, devolver JSON en lugar de redirigir
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Sesión expirada o usuario no autenticado',
                'redirect' => '/login.php'
            ]);
            exit;
        }
        
        // Para peticiones normales, redirigir
        header('Location: /login.php');
        exit;
    }
    
    // Actualizar actividad
    updateUserActivity();
}

/**
 * Alias para compatibilidad
 */
function requireLogin() {
    requireAuth();
}

/**
 * Obtiene datos del usuario actual
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nombre_completo' => $_SESSION['nombre_completo'],
        'email' => $_SESSION['email'],
        'rol' => $_SESSION['rol'],
        'id_empresa' => $_SESSION['id_empresa'],
        'empresa_nombre' => $_SESSION['empresa_nombre']
    ];
}

/**
 * Verifica si el usuario tiene un rol específico
 */
function hasRole($role) {
    if (!isAuthenticated()) {
        return false;
    }
    
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $role;
}

/**
 * Verifica si el usuario es administrador
 */
function isAdmin() {
    return hasRole('ADMINISTRADOR');
}

/**
 * Verifica si el usuario tiene permisos completos (no es solo ASISTENCIA)
 */
function hasFullAccess() {
    if (!isAuthenticated()) {
        return false;
    }
    
    $rol = $_SESSION['rol'] ?? '';
    
    // GERENTE, ADMIN, DUEÑO tienen acceso completo
    return in_array($rol, ['GERENTE', 'ADMINISTRADOR', 'ADMIN', 'DUEÑO', 'DUENO']);
}
function hasModuleAccess($module) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['rol'] ?? '';
    
    // GERENTE, ADMIN, DUEÑO tienen acceso completo a todos los módulos
    if (in_array($userRole, ['GERENTE', 'ADMINISTRADOR', 'ADMIN', 'DUEÑO', 'DUENO'])) {
        return true;
    }
    
    // Rol ASISTENCIA solo tiene acceso limitado
    if ($userRole === 'ASISTENCIA') {
        // Módulos permitidos para ASISTENCIA
        $allowedModules = ['asistencia', 'attendance'];
        return in_array($module, $allowedModules);
    }
    
    // Por defecto, denegar acceso para roles no definidos
    return false;
}

/**
 * Obtiene condiciones adicionales para filtrar datos según el rol del usuario
 * Solo usuarios con rol ASISTENCIA tienen restricciones
 */
function getRoleBasedWhereConditions($empresaId) {
    if (!isAuthenticated()) {
        return ['conditions' => [], 'params' => []];
    }
    
    $rol = $_SESSION['rol'] ?? '';
    
    // Si no es ASISTENCIA, no hay restricciones adicionales
    if ($rol !== 'ASISTENCIA') {
        return ['conditions' => [], 'params' => []];
    }
    
    // Para ASISTENCIA: restringir a empleados de la misma empresa
    // En el futuro se puede refinar esta lógica para ser más específica
    return [
        'conditions' => ['ID_EMPRESA = :empresa_id'],
        'params' => ['empresa_id' => $empresaId]
    ];
}

/**
 * Requiere acceso específico a un módulo - redirige si no tiene permisos
 */
function requireModuleAccess($module) {
    requireAuth(); // Primero verificar autenticación
    
    if (!hasModuleAccess($module)) {
        // Si es rol ASISTENCIA y trata de acceder a otro módulo, redirigir a asistencia
        if (hasRole('ASISTENCIA')) {
            header('Location: attendance.php');
            exit;
        } else {
            // Para otros casos, redirigir al dashboard
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>