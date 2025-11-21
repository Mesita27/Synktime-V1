<?php
require_once __DIR__ . '/../auth/session.php';
$currentUser = getCurrentUser();
$userRole = $currentUser['rol'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar sidebar-optimized" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="assets/img/synktime-logo.png" alt="SynkTime" class="logo-image">
            <span class="logo-text">SynkTime</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="nav-menu">
        <?php if ($userRole !== 'ASISTENCIA'): ?>
        <div class="nav-section">
            <div class="nav-section-title">
                <i class="fas fa-tachometer-alt"></i>
                <span>Principal</span>
            </div>
            <ul class="nav-items">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link<?php if($currentPage == 'dashboard.php') echo ' active'; ?>" data-tooltip="Dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="nav-section">
            <div class="nav-section-title">
                <i class="fas fa-cogs"></i>
                <span><?php echo ($userRole === 'ASISTENCIA') ? 'Asistencia' : 'Gestión'; ?></span>
            </div>
            <ul class="nav-items">
                <?php if ($userRole !== 'ASISTENCIA'): ?>
                <li class="nav-item">
                    <a href="employee.php" class="nav-link<?php if($currentPage == 'employee.php') echo ' active'; ?>" data-tooltip="Gestión de Empleados">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- ASISTENCIAS CON SUBMENU OPTIMIZADO -->
                <li class="nav-item has-submenu<?php if($currentPage == 'attendance.php') echo ' active'; ?>">
                    <a href="attendance.php" class="nav-link<?php if($currentPage == 'attendance.php') echo ' active'; ?>" data-tooltip="Sistema de Asistencias">
                        <i class="fas fa-clock"></i>
                        <span>Asistencias</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="attendance.php" class="submenu-link<?php if($currentPage == 'attendance.php') echo ' active'; ?>">
                                <i class="fas fa-list"></i>
                                <span>Lista de Asistencias</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="#" onclick="attendanceSystem.openEmployeeSelection()" class="submenu-link">
                                <i class="fas fa-plus-circle"></i>
                                <span>Registrar Asistencia</span>
                            </a>
                        </li>
                        <?php if ($userRole !== 'ASISTENCIA'): ?>
                        <li class="submenu-item">
                            <a href="attendance.php?view=statistics" class="submenu-link">
                                <i class="fas fa-chart-bar"></i>
                                <span>Estadísticas</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <?php if ($userRole !== 'ASISTENCIA'): ?>
                <li class="nav-item">
                    <a href="schedules.php" class="nav-link<?php if($currentPage == 'schedules.php') echo ' active'; ?>" data-tooltip="Gestión de Horarios">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Horarios</span>
                    </a>
                </li>

                <!-- ENROLAMIENTO BIOMÉTRICO MEJORADO -->
                <li class="nav-item has-submenu<?php if($currentPage == 'biometric-enrollment.php') echo ' active'; ?>">
                    <a href="biometric-enrollment.php" class="nav-link<?php if($currentPage == 'biometric-enrollment.php') echo ' active'; ?>" data-tooltip="Sistema Biométrico">
                        <i class="fas fa-fingerprint"></i>
                        <span>Sistema Biométrico</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="biometric-enrollment.php" class="submenu-link<?php if($currentPage == 'biometric-enrollment.php') echo ' active'; ?>">
                                <i class="fas fa-user-plus"></i>
                                <span>Enrolar Empleados</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="biometric-enrollment.php?view=registered" class="submenu-link">
                                <i class="fas fa-list-check"></i>
                                <span>Empleados Registrados</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="biometric-enrollment.php?view=pending" class="submenu-link">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Pendientes</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="horas-trabajadas.php" class="nav-link<?php if($currentPage == 'horas-trabajadas.php') echo ' active'; ?>" data-tooltip="Horas Trabajadas">
                        <i class="fas fa-business-time"></i>
                        <span>Horas Trabajadas</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reports.php" class="nav-link<?php if($currentPage == 'reports.php') echo ' active'; ?>" data-tooltip="Reportes del Sistema">
                        <i class="fas fa-file-alt"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- SECCIÓN DE USUARIO -->
        <div class="nav-section nav-user-section">
            <div class="nav-section-title">
                <i class="fas fa-user"></i>
                <span>Usuario</span>
            </div>
            <ul class="nav-items">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-tooltip="Perfil de Usuario">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($currentUser['usuario'] ?? 'Usuario'); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link nav-logout" data-tooltip="Cerrar Sesión">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- PIE DEL SIDEBAR -->
    <div class="sidebar-footer">
        <div class="system-status">
            <div class="status-item">
                <i class="fas fa-circle status-online"></i>
                <span>Sistema Online</span>
            </div>
            <div class="version-info">
                <small>v2.0.0</small>
            </div>
        </div>
    </div>
</aside>

<!-- JavaScript para Sidebar Optimizado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle del sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Guardar estado en localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }
    
    // Restaurar estado del sidebar
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
    }
    
    // Manejo de submenus
    const submenuItems = document.querySelectorAll('.has-submenu > .nav-link');
    submenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (!this.getAttribute('href') || this.getAttribute('href') === '#') {
                e.preventDefault();
            }
            
            const parent = this.parentElement;
            const isActive = parent.classList.contains('submenu-open');
            
            // Cerrar otros submenus
            document.querySelectorAll('.has-submenu.submenu-open').forEach(openItem => {
                if (openItem !== parent) {
                    openItem.classList.remove('submenu-open');
                }
            });
            
            // Toggle del submenu actual
            parent.classList.toggle('submenu-open', !isActive);
        });
    });
    
    // Auto-abrir submenu si hay una página activa dentro
    document.querySelectorAll('.has-submenu .submenu-link.active').forEach(activeLink => {
        const submenuParent = activeLink.closest('.has-submenu');
        if (submenuParent) {
            submenuParent.classList.add('submenu-open');
        }
    });
    
    // Tooltips para sidebar colapsado
    const navLinks = document.querySelectorAll('.nav-link[data-tooltip]');
    navLinks.forEach(link => {
        let tooltip;
        
        link.addEventListener('mouseenter', function() {
            if (sidebar.classList.contains('collapsed')) {
                tooltip = document.createElement('div');
                tooltip.className = 'sidebar-tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = (rect.right + 10) + 'px';
                tooltip.style.top = (rect.top + rect.height / 2 - tooltip.offsetHeight / 2) + 'px';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            if (tooltip) {
                tooltip.remove();
                tooltip = null;
            }
        });
    });
});
</script>
