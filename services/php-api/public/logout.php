<?php
// Incluir sistema de sesión
require_once 'auth/session.php';

// Inicializar sesión
initSession();

// Registrar cierre de sesión si hay usuario logueado
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'config/database.php';
        
        // Usar la conexión global $conn
        global $conn;
        
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare("
                INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) 
                VALUES (:userId, 'LOGOUT', :details)
            ");
            
            $stmt->bindParam(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Desconocido';
            $details = 'Cierre de sesión - IP: ' . $_SERVER['REMOTE_ADDR'] . ' - User Agent: ' . substr($userAgent, 0, 200);
            $stmt->bindParam(':details', $details, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Ignorar errores durante el logout para no interferir con el proceso
        error_log("Error al registrar logout: " . $e->getMessage());
    }
}

// Guardar información antes de destruir la sesión
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Cerrar sesión usando la función del sistema
endUserSession();

// Redirigir al login con mensaje
header('Location: login.php?logout=success&user=' . urlencode($username));
exit;
?>