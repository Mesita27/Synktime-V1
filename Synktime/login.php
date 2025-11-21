<?php
require_once 'auth/session.php';

// Si ya hay sesión activa, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-background-frame" aria-hidden="true">
        <div class="frame frame1"></div>
        <div class="frame frame2"></div>
        <div class="frame frame3"></div>
    </div>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="assets/img/synktime-logo.png" alt="SynkTime Logo">
            </div>
            <form id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" id="username" name="username" required autocomplete="username" placeholder="Ingrese su usuario">
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Ingrese su contraseña">
                </div>
                <button type="submit" class="btn-primary btn-block">Iniciar sesión</button>
            </form>
            <div id="loginError" class="login-error" style="display:none;">
                Usuario o contraseña incorrectos.
            </div>
        </div>
    </div>
    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        const loginError = document.getElementById('loginError');
        const submitButton = this.querySelector('button[type="submit"]');
        
        // Ocultar mensaje de error anterior
        loginError.style.display = 'none';
        
        // Deshabilitar botón y cambiar texto mientras se procesa
        const originalButtonText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Verificando...';
        
        // Crear objeto FormData para enviar los datos
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        // Enviar petición al backend
        fetch('auth/login-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Si la autenticación fue exitosa, redirigir
                window.location.href = data.redirect || 'dashboard.php';
            } else {
                // Mostrar mensaje de error
                loginError.textContent = data.message || 'Usuario o contraseña incorrectos.';
                loginError.style.display = 'block';
                
                // Restaurar botón
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            loginError.textContent = 'Error de conexión. Intente nuevamente.';
            loginError.style.display = 'block';
            
            // Restaurar botón
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        });
    });
    </script>
</body>
</html>
