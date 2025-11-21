<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación Biométrica | SynkTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .step {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .step.success {
            background-color: #d4edda;
        }
        .step.error {
            background-color: #f8d7da;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
        }
        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Instalación del Sistema Biométrico</h1>
            <p class="lead">Este asistente creará las tablas necesarias para el funcionamiento del sistema biométrico</p>
        </div>

        <div id="stepVerifyConnection" class="step">
            <h3>Paso 1: Verificando conexión a la base de datos</h3>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>

        <div id="stepCreateTables" class="step" style="display: none;">
            <h3>Paso 2: Creando tablas biométricas</h3>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>

        <div id="stepVerifySystem" class="step" style="display: none;">
            <h3>Paso 3: Verificando sistema biométrico</h3>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>

        <div id="stepResult" class="step" style="display: none;">
            <h3>Resultado de la instalación</h3>
            <div id="resultContent">
                <!-- El resultado se mostrará aquí -->
            </div>
        </div>

        <div class="footer">
            <div id="actionButtons" style="display: none;">
                <a href="biometric-enrollment.php" class="btn btn-primary">Ir al sistema biométrico</a>
                <button id="retryButton" class="btn btn-secondary">Reintentar instalación</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para mostrar mensaje
        function showMessage(stepId, message, isError = false) {
            const step = document.getElementById(stepId);
            step.innerHTML = step.innerHTML.split('<div class="alert').shift();
            
            const alertClass = isError ? 'alert-danger' : 'alert-success';
            const alertHTML = `<div class="alert ${alertClass} mt-3" role="alert">${message}</div>`;
            step.innerHTML += alertHTML;

            if (isError) {
                step.classList.add('error');
            } else {
                step.classList.add('success');
            }
        }

        // Función para iniciar instalación
        async function startInstallation() {
            try {
                // Paso 1: Verificar conexión
                document.getElementById('stepVerifyConnection').style.display = 'block';
                const connectionResult = await fetch('api/install/check_connection.php');
                const connectionData = await connectionResult.json();
                
                if (connectionData.success) {
                    showMessage('stepVerifyConnection', 'Conexión exitosa a la base de datos');
                } else {
                    throw new Error('Error de conexión: ' + connectionData.message);
                }
                
                // Paso 2: Crear tablas
                document.getElementById('stepCreateTables').style.display = 'block';
                const tablesResult = await fetch('api/install/create_biometric_tables.php');
                const tablesData = await tablesResult.json();
                
                if (tablesData.success) {
                    showMessage('stepCreateTables', tablesData.message);
                } else {
                    throw new Error('Error al crear tablas: ' + tablesData.message);
                }
                
                // Paso 3: Verificar sistema
                document.getElementById('stepVerifySystem').style.display = 'block';
                const systemResult = await fetch('api/install/check_system.php');
                const systemData = await systemResult.json();
                
                if (systemData.success) {
                    showMessage('stepVerifySystem', 'Sistema biométrico verificado correctamente');
                } else {
                    showMessage('stepVerifySystem', 'Advertencia: ' + systemData.message, false);
                }
                
                // Mostrar resultado final
                document.getElementById('stepResult').style.display = 'block';
                document.getElementById('resultContent').innerHTML = `
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">¡Instalación completada!</h4>
                        <p>El sistema biométrico se ha instalado correctamente. Ahora puede utilizar todas las funcionalidades.</p>
                    </div>
                `;
                
            } catch (error) {
                console.error('Error durante la instalación:', error);
                document.getElementById('stepResult').style.display = 'block';
                document.getElementById('resultContent').innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">Error de instalación</h4>
                        <p>${error.message}</p>
                        <hr>
                        <p class="mb-0">Por favor, revise los logs del servidor para más detalles.</p>
                    </div>
                `;
            }
            
            // Mostrar botones de acción
            document.getElementById('actionButtons').style.display = 'block';
        }

        // Iniciar la instalación cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            startInstallation();
            
            // Manejar botón de reintentar
            document.getElementById('retryButton').addEventListener('click', function() {
                location.reload();
            });
        });
    </script>
</body>
</html>
