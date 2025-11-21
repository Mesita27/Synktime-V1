<?php
require_once 'auth/session.php';
requireModuleAccess('configuracion'); // Verificar permisos para configuración
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Base de Datos | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .config-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .config-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border: none;
            border-radius: 12px;
            color: #0c5460;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: none;
            border-radius: 12px;
            color: #856404;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: none;
            border-radius: 12px;
            color: #155724;
        }

        .status-card {
            border-left: 4px solid;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
        }
        
        .status-success {
            border-left-color: #28a745;
            background: #f8fff9;
        }
        
        .status-error {
            border-left-color: #dc3545;
            background: #fff8f8;
        }
        
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .copy-btn:hover {
            background: #5a6fd8;
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            
            <!-- Hero Section -->
            <div class="config-hero">
                <h1><i class="fas fa-database"></i> Configuración de Base de Datos</h1>
                <p class="lead">Guía completa para configurar SynkTime con tu base de datos</p>
            </div>

            <!-- Estado actual de la conexión -->
            <div class="config-card">
                <h3><i class="fas fa-heartbeat text-success"></i> Estado de la Conexión</h3>
                <div id="connection-status">
                    <?php
                    try {
                        require_once 'config/database.php';
                        if ($conn) {
                            echo '<div class="status-card status-success">';
                            echo '<i class="fas fa-check-circle text-success"></i> ';
                            echo '<strong>Conexión exitosa:</strong> La base de datos está configurada correctamente.';
                            echo '</div>';
                            
                            // Verificar tablas principales
                            $tables = ['asistencia', 'empleado', 'biometric_data', 'biometric_logs'];
                            foreach ($tables as $table) {
                                $query = "SHOW TABLES LIKE '$table'";
                                $stmt = $conn->query($query);
                                $result = $stmt->fetchAll();
                                
                                if (count($result) > 0) {
                                    echo '<div class="status-card status-success">';
                                    echo '<i class="fas fa-table text-success"></i> ';
                                    echo "Tabla <strong>$table</strong> encontrada";
                                    echo '</div>';
                                } else {
                                    echo '<div class="status-card status-error">';
                                    echo '<i class="fas fa-exclamation-triangle text-danger"></i> ';
                                    echo "Tabla <strong>$table</strong> no encontrada";
                                    echo '</div>';
                                }
                            }
                        }
                    } catch (Exception $e) {
                        echo '<div class="status-card status-error">';
                        echo '<i class="fas fa-times-circle text-danger"></i> ';
                        echo '<strong>Error de conexión:</strong> ' . $e->getMessage();
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Instrucciones paso a paso -->
            <div class="config-card">
                <h3><i class="fas fa-list-ol"></i> Pasos para Configurar la Base de Datos</h3>
                
                <div class="d-flex align-items-start mb-4">
                    <div class="step-number">1</div>
                    <div>
                        <h5>Crear la Base de Datos</h5>
                        <p>Accede a phpMyAdmin o tu gestor de base de datos preferido y crea una nueva base de datos llamada <strong>synktime</strong>.</p>
                        <div class="code-block">CREATE DATABASE synktime CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;</div>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="step-number">2</div>
                    <div>
                        <h5>Importar el Archivo SQL</h5>
                        <p>Utiliza el archivo <strong>synktime (12).sql</strong> que se encuentra en la raíz del proyecto.</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Archivo recomendado:</strong> synktime (12).sql contiene todas las tablas y estructuras necesarias para el sistema biométrico.
                        </div>
                        <div class="code-block">
-- En phpMyAdmin:
1. Selecciona la base de datos 'synktime'
2. Ve a la pestaña 'Importar'
3. Selecciona el archivo 'synktime (12).sql'
4. Haz clic en 'Continuar'
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="step-number">3</div>
                    <div>
                        <h5>Configurar Conexión</h5>
                        <p>Edita el archivo <strong>config/database.php</strong> con tus credenciales de base de datos.</p>
                        <div class="code-block">
&lt;?php
// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'synktime');
define('DB_CHARSET', 'utf8mb4');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    return $conn;
}
?&gt;
                        </div>
                        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling.textContent)">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="step-number">4</div>
                    <div>
                        <h5>Verificar Tablas Principales</h5>
                        <p>Asegúrate de que las siguientes tablas estén creadas correctamente:</p>
                        <ul>
                            <li><strong>asistencia</strong> - Registros de entrada y salida</li>
                            <li><strong>empleado</strong> - Información de empleados</li>
                            <li><strong>biometric_data</strong> - Datos biométricos (huellas y rostros)</li>
                            <li><strong>biometric_logs</strong> - Logs de verificación biométrica</li>
                            <li><strong>horario</strong> - Horarios de trabajo</li>
                            <li><strong>establecimiento</strong> - Sedes y establecimientos</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Características del Sistema -->
            <div class="config-card">
                <h3><i class="fas fa-star"></i> Características del Sistema</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-fingerprint"></i> Sistema Biométrico</h6>
                            <ul class="mb-0">
                                <li>Reconocimiento de huellas dactilares</li>
                                <li>Reconocimiento facial con TensorFlow</li>
                                <li>Logs de verificación biométrica</li>
                                <li>Múltiples métodos de autenticación</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-clock"></i> Gestión de Asistencias</h6>
                            <ul class="mb-0">
                                <li>Registro automático y manual</li>
                                <li>Control de tardanzas</li>
                                <li>Múltiples horarios por empleado</li>
                                <li>Reportes detallados</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Solución de Problemas -->
            <div class="config-card">
                <h3><i class="fas fa-wrench"></i> Solución de Problemas Comunes</h3>
                
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Error: "Table doesn't exist"</h6>
                    <p>Si ves este error, asegúrate de haber importado correctamente el archivo SQL. Verifica que el archivo <strong>synktime (12).sql</strong> se haya importado completamente.</p>
                </div>

                <div class="alert alert-warning">
                    <h6><i class="fas fa-database"></i> Error de conexión</h6>
                    <p>Verifica las credenciales en <strong>config/database.php</strong> y asegúrate de que el servidor MySQL esté ejecutándose.</p>
                </div>

                <div class="alert alert-info">
                    <h6><i class="fas fa-user-shield"></i> Permisos de Usuario</h6>
                    <p>El usuario de base de datos debe tener permisos completos (SELECT, INSERT, UPDATE, DELETE) sobre la base de datos synktime.</p>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text.trim()).then(function() {
        alert('Código copiado al portapapeles');
    }, function(err) {
        console.error('Error al copiar: ', err);
    });
}

// Verificar estado de conexión cada 30 segundos
setInterval(function() {
    fetch('api/system/check-database.php')
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById('connection-status');
            if (data.success) {
                // Actualizar estado si hay cambios
                console.log('Base de datos funcionando correctamente');
            }
        })
        .catch(error => {
            console.error('Error verificando conexión:', error);
        });
}, 30000);
</script>

</body>
</html>
