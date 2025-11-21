<?php
require_once __DIR__ . '/auth/session.php';
requireModuleAccess('empleados'); // Verificar permisos para módulo de empleados
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscripción Biométrica | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/pagination.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/employee.css">
    <link rel="stylesheet" href="assets/css/biometric-enrollment.css">
    <link rel="stylesheet" href="assets/css/biometric-page.css">
    <link rel="stylesheet" href="assets/css/modal-fixes.css">
    <link rel="stylesheet" href="assets/css/biometric-blazeface.css">
    <link rel="stylesheet" href="assets/css/biometric-pagination-fix.css">
    <link rel="stylesheet" href="assets/css/biometric-enrollment-advanced.css">
    <link rel="stylesheet" href="assets/css/notification-system.css">
    <!-- CORRECCIÓN FINAL: CSS específico para modales 1920x1080 -->
    <link rel="stylesheet" href="assets/css/modal-size-fix.css">
    <!-- jQuery primero para asegurar que esté disponible -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- TensorFlow.js para reconocimiento facial -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.11.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js"></script>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="employee-header">
                <h2 class="page-title"><i class="fas fa-fingerprint"></i> Inscripción Biométrica</h2>
                <div class="employee-actions">
                    <button class="btn-primary" id="btnRefreshStats"><i class="fas fa-sync-alt"></i> Actualizar</button>
                </div>
            </div>

            <!-- Estadísticas de enrolamiento -->
            <div class="stats-container" id="enrollmentStats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="totalEmployees">0</h3>
                        <p class="stat-label">Total Empleados</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-fingerprint text-success"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="enrolledCount">0</h3>
                        <p class="stat-label">Inscritos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus text-warning"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="pendingCount">0</h3>
                        <p class="stat-label">Pendientes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-pie text-info"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="enrollmentPercentage">0%</h3>
                        <p class="stat-label">Progreso</p>
                    </div>
                </div>
            </div>

            <!-- Filtros de búsqueda -->
            <div class="employee-query-box">
                <form class="employee-query-form" autocomplete="off" onsubmit="return false;" id="formBusquedaEmpleados">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtro_sede">Sede</label>
                            <select id="filtro_sede" name="sede" class="form-control">
                                <option value="">Todas las sedes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_establecimiento">Establecimiento</label>
                            <select id="filtro_establecimiento" name="establecimiento" class="form-control">
                                <option value="">Todos los establecimientos</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_estado">Estado Biométrico</label>
                            <select id="filtro_estado" name="estado" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="enrolled">Inscrito</option>
                                <option value="pending">Pendiente</option>
                                <option value="partial">Parcial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="busqueda_empleado">Buscar</label>
                            <input type="text" id="busqueda_empleado" name="busqueda" class="form-control" placeholder="Código o nombre...">
                        </div>
                        <div class="form-group query-btns">
                            <button type="button" id="btnBuscarEmpleados" class="btn-primary" onclick="console.log('Botón de búsqueda clickeado inline'); if(typeof loadEmployeeData==='function'){loadEmployeeData();}else{buscarEmpleadosDirectamente();}">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" id="btnLimpiarFiltros" class="btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla de empleados -->
            <div class="employee-table-container">
                <!-- Controles de paginación (arriba) -->
                <div class="pagination-controls" id="paginationControls">
                    <div class="limit-selector">
                        <label for="limitSelector">Mostrar:</label>
                        <select id="limitSelector" class="form-control limit-select">
                            <option value="10">10 registros</option>
                            <option value="15">15 registros</option>
                            <option value="20" selected>20 registros</option>
                            <option value="30">30 registros</option>
                            <option value="50">50 registros</option>
                            <option value="100">100 registros</option>
                        </select>
                    </div>
                    <div class="pagination-info">
                        <span id="paginationInfo">Cargando...</span>
                    </div>
                    <div class="pagination-buttons" id="paginationButtons">
                        <!-- Los botones se generan dinámicamente -->
                    </div>
                </div>

                <table class="employee-table" id="employeeTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre Completo</th>
                            <th>DNI</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Estado Facial</th>
                            <th>Estado Huella</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <!-- El JS llenará dinámicamente las filas aquí -->
                    </tbody>
                </table>
            </div>
            <!-- Modales -->
            <?php include 'components/biometric_enrollment_modal_advanced.php'; ?>

        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Scripts del sistema -->
<script src="assets/js/layout.js"></script>
<script src="assets/js/modal-fixes.js"></script>

<!-- Sistema AJAX de inscripción biométrica -->
<script src="assets/js/biometric-enrollment-ajax.js"></script>

<!-- Sistema de verificación y reparación automática -->
<script src="assets/js/biometric-system-check.js"></script>

<!-- Script para verificar que Bootstrap esté cargado correctamente -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que Bootstrap esté disponible
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap no está cargado correctamente');
        // Intentar cargar de nuevo
        const bootstrapScript = document.createElement('script');
        bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
        bootstrapScript.onload = function() {
            console.log('✅ Bootstrap cargado correctamente en segundo intento');
        };
        document.head.appendChild(bootstrapScript);
    } else {
        console.log('✅ Bootstrap cargado correctamente');
    }
});
</script>

<!-- Solo mantenemos los scripts esenciales, el resto está en biometric-system-unified.js -->

<!-- Script de diagnóstico para depuración -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Biometric Enrollment System: Inicializado');
    
    // Verificar elementos críticos
    const criticalElements = [
        'employeeTableBody',
        'totalEmployees',
        'enrolledCount',
        'pendingCount',
        'enrollmentPercentage',
        'biometricEnrollmentModal', // Añadido para verificar el modal
        'startFaceCamera',          // Añadido para verificar botón de cámara
        'faceVideo'                 // Añadido para verificar elemento de video
    ];
    
    criticalElements.forEach(id => {
        const element = document.getElementById(id);
        console.log(`Elemento ${id}: ${element ? '✅ Encontrado' : '❌ No encontrado'}`);
    });
    
    // Verificar si Bootstrap está disponible
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap no está cargado correctamente');
        // Intentar cargar de nuevo con una prioridad mayor
        const bootstrapScript = document.createElement('script');
        bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
        bootstrapScript.async = false;
        bootstrapScript.onload = function() {
            console.log('✅ Bootstrap cargado correctamente en segundo intento');
            // Reintentar inicialización del modal
            if (typeof initBiometricModal === 'function') {
                setTimeout(() => initBiometricModal(), 500);
            }
        };
        document.head.insertBefore(bootstrapScript, document.head.firstChild);
    } else {
        console.log('✅ Bootstrap cargado correctamente');
    }
    
    // Cargar datos automáticamente si no se ha hecho
    if (typeof loadEmployeeData === 'function' && document.querySelector('#employeeTableBody tr') === null) {
        console.log('Cargando datos de empleados automáticamente...');
        loadEmployeeData().then(() => {
            console.log('Datos cargados exitosamente');
        }).catch(error => {
            console.error('Error al cargar datos:', error);
        });
    }
});

// Verificar si un empleado está inscrito biométricamente
async function checkBiometricStatus(employeeId) {
    try {
        const response = await fetch(`api/biometric/status.php?employee_id=${employeeId}`);
        const data = await response.json();
        
        console.log('Estado biométrico para empleado #' + employeeId + ':', data);
        
        return data.success ? data.status : null;
    } catch (error) {
        console.error('Error al verificar estado biométrico:', error);
        return null;
    }
}

// Función de respaldo para abrir el modal (compatible con la función en biometric-enrollment.js)
function openBiometricEnrollmentModal(employeeId, name) {
    console.log('Función de respaldo llamada para empleado:', employeeId, name);
    
    // Si existe la función principal en el archivo JS, usarla
    if (typeof openEnrollmentModal === 'function') {
        console.log('Delegando a la función principal openEnrollmentModal...');
        openEnrollmentModal(employeeId);
        return;
    }
    
    // Código de respaldo si la función principal falla
    const modal = document.getElementById('biometricEnrollmentModal');
    if (modal) {
        try {
            // Actualizar información del empleado en el modal
            document.getElementById('modal-employee-code').textContent = employeeId;
            document.getElementById('modal-employee-name').textContent = name;
            
            // Verificar si Bootstrap está disponible
            if (typeof bootstrap !== 'undefined') {
                // Mostrar el modal usando Bootstrap
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                console.log('Modal de enrolamiento abierto para empleado:', employeeId, name);
            } else if (typeof $ !== 'undefined' && typeof $(modal).modal === 'function') {
                // Intentar con jQuery como alternativa
                $(modal).modal('show');
                console.log('Modal mostrado con jQuery para empleado:', employeeId, name);
            } else {
                throw new Error('Bootstrap no está disponible');
            }
            
            // Verificar estado biométrico del empleado
            checkBiometricStatus(employeeId).then(status => {
                if (status) {
                    // Actualizar indicadores de estado
                    document.getElementById('facial-status').className = 
                        status.facial ? 'badge bg-success' : 'badge bg-secondary';
                    document.getElementById('facial-status').textContent = 
                        status.facial ? 'Inscrito' : 'Pendiente';
                        
                    document.getElementById('fingerprint-status').className = 
                        status.fingerprint ? 'badge bg-success' : 'badge bg-secondary';
                    document.getElementById('fingerprint-status').textContent = 
                        status.fingerprint ? 'Inscrito' : 'Pendiente';
                }
            });
        } catch (error) {
            console.error('Error al mostrar el modal:', error);
            // Usar sistema de notificaciones en lugar de alert
            if (typeof window.showNotification === 'function') {
                window.showNotification({
                    type: 'error',
                    title: 'Error',
                    message: 'Error al abrir el modal: ' + error.message
                });
            } else {
                alert('Error al abrir el modal: ' + error.message);
            }
        }
    } else {
        console.error('Modal de enrolamiento no encontrado en el DOM');
        // Usar sistema de notificaciones en lugar de alert
        if (typeof window.showNotification === 'function') {
            window.showNotification({
                type: 'error',
                title: 'Error',
                message: 'El modal de enrolamiento no está disponible'
            });
        } else {
            alert('Error: El modal de enrolamiento no está disponible');
        }
    }
}
</script>
<!-- El modal ya está incluido arriba, no es necesario incluirlo de nuevo -->
<!-- NUEVA IMPLEMENTACIÓN UNIFICADA DEL SISTEMA BIOMÉTRICO -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.11.0/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js"></script>
<!-- Sistema de notificaciones -->
<script src="assets/js/notification-system.js"></script>

<!-- Sistema biométrico avanzado con BlazeFace -->
<script src="assets/js/biometric-blazeface.js"></script>

<!-- Sistema biométrico unificado (comentado para usar versión AJAX) -->
<!-- <script src="assets/js/biometric-system-unified.js"></script> -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📊 Sistema biométrico con AJAX inicializado...');
    
    // Inicializar sistema de notificaciones si está disponible
    if (typeof initNotificationSystem === 'function') {
        initNotificationSystem();
        
        // Mostrar notificación de bienvenida
        setTimeout(() => {
            showNotification({
                title: 'Sistema Biométrico AJAX',
                message: 'Sistema de enrolamiento con paginación inicializado correctamente',
                type: 'info',
                duration: 3000
            });
        }, 1000);
    }
});</script>

<!-- Scripts de compatibilidad -->
<script src="assets/js/search-fix.js"></script>

</body>
</html>
