<?php
require_once __DIR__ . '/auth/session.php';
requireModuleAccess('empleados');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enrolamiento Biométrico | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS (cargado primero para que los estilos personalizados lo sobrescriban) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Dashboard Styles (cargados después para tener prioridad) -->
    <link rel="stylesheet" href="assets/css/pagination.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/employee.css">
    <link rel="stylesheet" href="assets/css/biometric-modals-responsive.css">
    <!-- TensorFlow.js para reconocimiento facial (cargado dinámicamente) -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.8.0/dist/tf.min.js"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.js"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/facemesh@0.0.5/dist/facemesh.js"></script> -->
    <?php include 'components/python_service_config.php'; ?>
    <script src="assets/js/python-service-client.js"></script>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="employee-header">
                <h2 class="page-title"><i class="fas fa-fingerprint"></i> Enrolamiento Biométrico</h2>
                <div class="employee-actions">
                    <button class="btn-secondary" id="btnAyudaBiometrico" title="Ayuda - Enrolamiento Biométrico">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </button>
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
                <form class="employee-query-form" autocomplete="off">
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
                            <button type="button" id="btnBuscarEmpleados" class="btn-primary">
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
                <table class="employee-table" id="employeeTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Establecimiento</th>
                            <th>Sede</th>
                            <th>Estado Biométrico</th>
                            <th>Facial</th>
                            <th>Huella</th>
                            <th>RFID</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando empleados...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando empleados...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="pagination-wrapper">
                <div class="pagination-info" id="paginationInfo">
                    <!-- Información de paginación se mostrará aquí -->
                </div>
                <div class="pagination-container" id="paginationContainer">
                    <!-- El JS generará los controles de paginación aquí -->
                </div>
            </div>

            <!-- Modales -->
            <?php include 'components/enrolamiento_biometrico_modal.php'; ?>

        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/pagination.js"></script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/enrolamiento-biometrico.js"></script>

<!-- Script de verificación -->
<script>
    // Verificar que Bootstrap esté cargado correctamente
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 Verificando componentes del sistema de enrolamiento...');

        // Verificar Bootstrap
        if (typeof bootstrap === 'undefined') {
            console.error('❌ Bootstrap no está cargado correctamente');
        } else {
            console.log('✅ Bootstrap cargado correctamente');
        }

        // Verificar modal
        const modalElement = document.getElementById('enrolamientoBiometricoModal');
        if (modalElement) {
            console.log('✅ Modal de enrolamiento encontrado');
        } else {
            console.error('❌ Modal de enrolamiento no encontrado');
        }

        // Verificar función openEnrollmentModal
        if (typeof openEnrollmentModal === 'function') {
            console.log('✅ Función openEnrollmentModal disponible');
        } else {
            console.error('❌ Función openEnrollmentModal no encontrada');
        }

        // Verificar elementos del formulario de búsqueda
        const searchForm = document.getElementById('employee-query-form');
        if (searchForm) {
            console.log('✅ Formulario de búsqueda encontrado');

            // Verificar campo de búsqueda
            const searchInput = searchForm.querySelector('input[type="text"], input[name*="search"]');
            if (searchInput) {
                console.log('✅ Campo de búsqueda encontrado');

                // Agregar verificación de Enter key
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        console.log('🔍 Enter presionado en campo de búsqueda - previniendo envío de formulario');
                        e.preventDefault();
                        return false;
                    }
                });
            } else {
                console.warn('⚠️ Campo de búsqueda no encontrado');
            }
        } else {
            console.warn('⚠️ Formulario de búsqueda no encontrado');
        }

        // Agregar función de debug global
        window.debugEmployeeData = function() {
            console.log('🔍 Debug Employee Data:');
            console.log('employeeData length:', window.employeeData ? window.employeeData.length : 'undefined');
            console.log('employeeData sample:', window.employeeData ? window.employeeData.slice(0, 3) : 'undefined');
            console.log('Available employee IDs:', window.employeeData ? window.employeeData.map(emp => emp.ID_EMPLEADO || emp.id) : 'undefined');
        };

        console.log('🔍 Verificación completada. Use debugEmployeeData() en la consola para inspeccionar los datos.');
    });
</script>

<!-- Modal de Ayuda Biométrica Personalizado -->
<?php include 'modal_ayuda_biometrico_custom.php'; ?>

<!-- Inicialización del modal personalizado de ayuda biométrica -->
<script>
// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando modal de ayuda biométrica personalizado...');

    const helpButton = document.getElementById('btnAyudaBiometrico');

    if (helpButton) {
        console.log('✅ Botón de ayuda biométrica encontrado');
        // Asegurar que el botón tenga el event listener correcto
        helpButton.onclick = function(e) {
            e.preventDefault();
            showBiometricHelpModal();
        };
    } else {
        console.warn('⚠️ Botón de ayuda biométrica no encontrado');
    }

    console.log('✅ Inicialización del modal biométrico personalizado completada');
});

// Script de debug para verificar el modal biométrico personalizado
console.log('🔍 === DEBUG MODAL BIOMÉTRICO PERSONALIZADO ===');

// Verificar elementos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Verificando elementos del modal biométrico personalizado...');

    const button = document.getElementById('btnAyudaBiometrico');
    const modal = document.getElementById('biometricHelpModal');

    console.log('Botón encontrado:', !!button);
    console.log('Modal encontrado:', !!modal);

    if (modal) {
        console.log('Modal display inicial:', modal.style.display);
        console.log('Modal visibility:', window.getComputedStyle(modal).visibility);
        console.log('Modal opacity:', window.getComputedStyle(modal).opacity);

        // Verificar contenido del modal
        const content = modal.querySelector('.biometric-modal-content');
        console.log('Contenido del modal encontrado:', !!content);

        if (content) {
            const body = content.querySelector('.biometric-modal-body');
            console.log('Cuerpo del modal encontrado:', !!body);

            if (body) {
                const helpContent = body.querySelector('.biometric-help-content');
                console.log('Contenido de ayuda encontrado:', !!helpContent);

                if (helpContent) {
                    const sections = helpContent.querySelectorAll('.biometric-help-section');
                    console.log('Número de secciones encontradas:', sections.length);

                    const titles = helpContent.querySelectorAll('.biometric-section-title');
                    console.log('Número de títulos de sección encontrados:', titles.length);
                }
            }
        }
    }

    // Agregar función de debug global
    window.debugBiometricModal = function() {
        console.log('🔍 === DEBUG MANUAL DEL MODAL BIOMÉTRICO PERSONALIZADO ===');

        const modal = document.getElementById('biometricHelpModal');
        if (!modal) {
            console.error('❌ Modal personalizado no encontrado');
            return;
        }

        console.log('Modal element:', modal);
        console.log('Modal classes:', modal.className);
        console.log('Modal display:', modal.style.display);
        console.log('Modal computed display:', window.getComputedStyle(modal).display);
        console.log('Modal computed visibility:', window.getComputedStyle(modal).visibility);

        const content = modal.querySelector('.biometric-modal-content');
        if (content) {
            console.log('Content found, innerHTML length:', content.innerHTML.length);
            console.log('Content visible:', window.getComputedStyle(content).display !== 'none');
        } else {
            console.error('❌ Content not found');
        }

        const body = modal.querySelector('.biometric-modal-body');
        if (body) {
            console.log('Body found, innerHTML length:', body.innerHTML.length);
        } else {
            console.error('❌ Body not found');
        }
    };

    console.log('✅ Debug functions available. Use debugBiometricModal() in console.');
});

console.log('🔍 === FIN DEBUG MODAL BIOMÉTRICO PERSONALIZADO ===');
</script>

</body>
</html>
