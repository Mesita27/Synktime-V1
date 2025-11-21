<?php
/**
 * Manejador de autenticación para el login
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';

// Iniciar sesión
initSession();

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicializar respuesta
    $response = [
        'success' => false,
        'message' => 'Error desconocido'
    ];
    
    // Verificar si se recibieron los datos necesarios
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Validar datos básicos
        if (empty($username) || empty($password)) {
            $response['message'] = 'Por favor complete todos los campos';
        } else {
            try {
                // Usar la conexión global $conn del database.php
                global $conn;
                
                // Consultar usuario por nombre de usuario
                $stmt = $conn->prepare("
                    SELECT 
                        u.ID_USUARIO,
                        u.USERNAME,
                        u.CONTRASENA,
                        u.NOMBRE_COMPLETO,
                        u.EMAIL,
                        u.ROL,
                        u.ID_EMPRESA,
                        u.ESTADO,
                        e.NOMBRE AS EMPRESA_NOMBRE,
                        e.ESTADO AS EMPRESA_ESTADO
                    FROM USUARIO u
                    INNER JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA
                    WHERE u.USERNAME = :username
                ");
                
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar si el usuario existe
                if (!$user) {
                    $response['message'] = 'Usuario no encontrado';
                } 
                // Verificar si el usuario está activo
                else if ($user['ESTADO'] !== 'A') {
                    $response['message'] = 'Usuario inactivo. Contacte al administrador';
                }
                // Verificar si la empresa está activa
                else if ($user['EMPRESA_ESTADO'] !== 'A') {
                    $response['message'] = 'Empresa inactiva. Contacte al administrador';
                }
                // Verificar contraseña
                else {
                    $passwordValid = false;
                    
                    // Intentar con password_verify primero (contraseñas hasheadas)
                    if (password_verify($password, $user['CONTRASENA'])) {
                        $passwordValid = true;
                    } 
                    // Si no funciona, intentar comparación directa (compatibilidad con datos existentes)
                    else if ($password === $user['CONTRASENA']) {
                        $passwordValid = true;
                        
                        // Actualizar a hash seguro para futuras verificaciones
                        try {
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $conn->prepare("UPDATE USUARIO SET CONTRASENA = :hash WHERE ID_USUARIO = :id");
                            $updateStmt->bindParam(':hash', $hashedPassword);
                            $updateStmt->bindParam(':id', $user['ID_USUARIO'], PDO::PARAM_INT);
                            $updateStmt->execute();
                        } catch (Exception $e) {
                            error_log("Error al actualizar hash de contraseña: " . $e->getMessage());
                        }
                    }
                    
                    if ($passwordValid) {
                        // Guardar datos en sesión
                        $_SESSION['user_id'] = $user['ID_USUARIO'];
                        $_SESSION['username'] = $user['USERNAME'];
                        $_SESSION['nombre_completo'] = $user['NOMBRE_COMPLETO'];
                        $_SESSION['email'] = $user['EMAIL'];
                        $_SESSION['rol'] = $user['ROL'];
                        $_SESSION['id_empresa'] = $user['ID_EMPRESA'];
                        $_SESSION['empresa_nombre'] = $user['EMPRESA_NOMBRE'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        
                        // Registrar login exitoso
                        try {
                            $stmt = $conn->prepare("
                                INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) 
                                VALUES (:userId, 'LOGIN', :details)
                            ");
                            
                            $stmt->bindParam(':userId', $user['ID_USUARIO'], PDO::PARAM_INT);
                            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            $details = 'Inicio de sesión exitoso - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' - User Agent: ' . substr($userAgent, 0, 200);
                            $stmt->bindParam(':details', $details, PDO::PARAM_STR);
                            $stmt->execute();
                        } catch (Exception $e) {
                            error_log("Error al registrar login exitoso: " . $e->getMessage());
                        }
                        
                        // Preparar respuesta exitosa
                        $response['success'] = true;
                        $response['message'] = 'Login exitoso';
                        
                        // Redirigir según el rol del usuario
                        if ($user['ROL'] === 'ASISTENCIA') {
                            $response['redirect'] = 'attendance.php';
                        } else {
                            $response['redirect'] = 'dashboard.php';
                        }
                        
                        $response['user'] = [
                            'username' => $user['USERNAME'],
                            'nombre_completo' => $user['NOMBRE_COMPLETO'],
                            'rol' => $user['ROL'],
                            'empresa' => $user['EMPRESA_NOMBRE']
                        ];
                    } else {
                        // Registrar intento fallido
                        try {
                            $stmt = $conn->prepare("
                                INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) 
                                VALUES (:userId, 'LOGIN_FAILED', :details)
                            ");
                            
                            $stmt->bindParam(':userId', $user['ID_USUARIO'], PDO::PARAM_INT);
                            $details = 'Intento de login fallido - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
                            $stmt->bindParam(':details', $details, PDO::PARAM_STR);
                            $stmt->execute();
                        } catch (Exception $e) {
                            error_log("Error al registrar login fallido: " . $e->getMessage());
                        }
                        
                        $response['message'] = 'Usuario o contraseña incorrectos';
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Error en login: " . $e->getMessage());
                $response['message'] = 'Error interno del servidor';
            } catch (Exception $e) {
                error_log("Error general en login: " . $e->getMessage());
                $response['message'] = 'Error interno del servidor';
            }
        }
    } else {
        $response['message'] = 'Datos incompletos';
    }
    
    // Devolver respuesta como JSON
    echo json_encode($response);
    exit;
}

// Si no es POST, redirigir a la página de login
header('Location: ../login.php');
exit;
?>