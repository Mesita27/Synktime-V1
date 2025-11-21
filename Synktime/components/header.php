<?php
// La sesión ya debería estar iniciada por el sistema de autenticación
// Obtener información del usuario usando las funciones de sesión
require_once __DIR__ . '/../auth/session.php';

$nombreUsuario = 'Usuario';
$nombreCompleto = 'Usuario';

if (isAuthenticated()) {
    $currentUser = getCurrentUser();
    if ($currentUser) {
        $nombreUsuario = $currentUser['username'];
        $nombreCompleto = $currentUser['nombre_completo'];
    }
} else {
    // Fallback para compatibilidad
    if (isset($_SESSION['username'])) {
        $nombreUsuario = $_SESSION['username'];
        $nombreCompleto = $_SESSION['nombre_completo'] ?? $nombreUsuario;
    }
}

// Establecer la zona horaria del servidor a Bogotá
date_default_timezone_set('America/Bogota');
?>
<header class="header">
    <div class="header-left">
        <button class="toggle-sidebar" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="header-title">
            <?php
                // Detectar título dinámico según el archivo
                $titles = [
                    'dashboard.php' => 'Dashboard',
                    'employees.php' => 'Empleados',
                    'attendance.php' => 'Asistencias',
                    'schedules.php' => 'Horarios',
                    'horarios_personalizados.php' => 'Horarios',
                    'configuracion.php' => 'Configuración',
                    'reports.php' => 'Reportes',
                    'index.php' => 'Inicio'
                ];
                $file = basename($_SERVER['PHP_SELF']);
                echo isset($titles[$file]) ? $titles[$file] : 'SynkTime';
            ?>
        </h1>
    </div>
    <div class="header-right">
        <div class="system-info">
            <div class="datetime-display">
                <i class="fas fa-clock"></i>
                <span id="currentDateTime">Cargando...</span>
                <span class="timezone-label"></span>
            </div>
            <div class="user-dropdown">
                <button class="user-info" id="userMenuBtn" type="button">
                    <i class="fas fa-user"></i>
                    <span class="user-name" title="<?php echo htmlspecialchars($nombreCompleto); ?>">
                        <?php echo htmlspecialchars($nombreUsuario); ?>
                    </span>
                    <i class="fas fa-caret-down dropdown-arrow"></i>
                </button>
                <div class="user-menu" id="userMenu">
                    <div class="user-info-details">
                        <div class="user-full-name"><?php echo htmlspecialchars($nombreCompleto); ?></div>
                        <div class="user-role">
                            <?php echo isset($_SESSION['rol']) ? htmlspecialchars($_SESSION['rol']) : 'Usuario'; ?>
                        </div>
                        <div class="user-company">
                            <?php echo isset($_SESSION['empresa_nombre']) ? htmlspecialchars($_SESSION['empresa_nombre']) : ''; ?>
                        </div>
                    </div>
                    <hr class="user-menu-divider">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Función para actualizar la hora de Bogotá en tiempo real usando JavaScript
function updateDateTime() {
    const now = new Date();
    // Opciones para la zona horaria de Bogotá
    const options = {
        timeZone: 'America/Bogota',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false
    };
    // Formatear fecha y hora para Bogotá, CO
    const dateTimeString = now.toLocaleString('es-CO', options).replace(',', '');
    const dateTimeElement = document.getElementById('currentDateTime');
    if (dateTimeElement) {
        dateTimeElement.textContent = dateTimeString;
    }
}

// Actualizar inmediatamente y luego cada segundo
updateDateTime();
setInterval(updateDateTime, 1000);

// DROPDOWN ROBUSTO - SOLUCIÓN DEFINITIVA
(function() {
    'use strict';
    
    let dropdownInitialized = false;
    
    function initDropdownRobust() {
        if (dropdownInitialized) {
            return;
        }
        
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        
        if (!userMenuBtn || !userMenu) {
            console.error('Header.php - ERROR: Elementos del dropdown no encontrados');
            return;
        }
        
        // Marcar como inicializado
        dropdownInitialized = true;
        userMenuBtn.setAttribute('data-dropdown-initialized', 'true');
        
        // Eliminar todos los event listeners previos
        userMenuBtn.replaceWith(userMenuBtn.cloneNode(true));
        const freshBtn = document.getElementById('userMenuBtn');
        freshBtn.setAttribute('data-dropdown-initialized', 'true');
        
        // Event listener principal
        freshBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const menu = document.getElementById('userMenu');
            const isOpen = menu.classList.contains('show');
            
            if (isOpen) {
                menu.classList.remove('show');
            } else {
                menu.classList.add('show');
            }
        };
        
        // Click fuera del dropdown
        document.onclick = function(e) {
            const btn = document.getElementById('userMenuBtn');
            const menu = document.getElementById('userMenu');
            
            if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
                if (menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            }
        };
        
        // ESC key
        document.onkeydown = function(e) {
            if (e.key === 'Escape') {
                const menu = document.getElementById('userMenu');
                if (menu && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            }
        };
    }
    
    // Múltiples puntos de inicialización
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDropdownRobust);
    } else {
        initDropdownRobust();
    }
    
    // Timeout de emergencia
    setTimeout(initDropdownRobust, 100);
    setTimeout(initDropdownRobust, 500);
    
})();
</script>