<?php
require_once __DIR__ . '/../auth/session.php';
$currentUser = getCurrentUser();
$userRole = $currentUser['rol'] ?? '';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="assets/img/synktime-logo.png" alt="SynkTime">
            <span class="logo-text">SynkTime</span>
        </div>
    </div>
    
    <nav class="nav-menu">
        <?php if ($userRole !== 'ASISTENCIA'): ?>
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <ul class="nav-items">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo ' active'; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="nav-section">
            <div class="nav-section-title"><?php echo ($userRole === 'ASISTENCIA') ? 'Asistencia' : 'Gestión'; ?></div>
            <ul class="nav-items">
                <?php if ($userRole !== 'ASISTENCIA'): ?>
                <li class="nav-item">
                    <a href="employee.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'employee.php') echo ' active'; ?>">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'attendance.php') echo ' active'; ?>">
                        <i class="fas fa-clock"></i>
                        <span>Asistencias</span>
                    </a>
                </li>
                
                <?php if ($userRole !== 'ASISTENCIA'): ?>
                <li class="nav-item">
                    <a href="justificaciones.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'justificaciones.php') echo ' active'; ?>">
                        <i class="fas fa-user-times"></i>
                        <span>Justificaciones</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($userRole !== 'ASISTENCIA'): ?>
                <li class="nav-item">
                    <a href="horarios_personalizados.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'horarios_personalizados.php') echo ' active'; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Horarios</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="enrolamiento-biometrico.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'enrolamiento-biometrico.php') echo ' active'; ?>">
                        <i class="fas fa-fingerprint"></i>
                        <span>Inscripción Biométrica</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="horas-trabajadas.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'horas-trabajadas.php') echo ' active'; ?>">
                        <i class="fas fa-business-time"></i>
                        <span>Horas Trabajadas</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reports.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'reports.php') echo ' active'; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if (in_array($userRole, ['GERENTE', 'ADMIN'])): ?>
        <div class="nav-section">
            <div class="nav-section-title">Administración</div>
            <ul class="nav-items">
                <li class="nav-item">
                    <a href="configuracion.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'configuracion.php') echo ' active'; ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Configuración</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </nav>
</aside>
