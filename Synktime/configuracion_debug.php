<?php
require_once 'auth/session.php';

// Verificar autenticación
if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Verificar permisos (GERENTE y ADMIN pueden acceder a configuración)
$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['rol'], ['GERENTE', 'ADMIN'])) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración (Debug) - SynkTime</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/header.css">
    
    <style>
        .debug-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .api-result {
            background: #fff;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 10px 0;
        }
        .loading {
            color: #6c757d;
            font-style: italic;
        }
        .error {
            color: #dc3545;
        }
        .success {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'components/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php include 'components/header.php'; ?>
            <main class="main-content">
                <div class="container-fluid">
                    <h1><i class="fas fa-cogs"></i> Configuración del Sistema (Debug)</h1>
                    
                    <div class="debug-section">
                        <h3>Usuario Actual</h3>
                        <pre><?php echo json_encode($currentUser, JSON_PRETTY_PRINT); ?></pre>
                    </div>
                    
                    <div class="debug-section">
                        <h3>Test APIs</h3>
                        <button class="btn btn-primary" onclick="testSedesAPI()">
                            <i class="fas fa-building"></i> Test Sedes API
                        </button>
                        <button class="btn btn-success" onclick="testEstablecimientosAPI()">
                            <i class="fas fa-briefcase"></i> Test Establecimientos API
                        </button>
                        
                        <div id="apiResults"></div>
                    </div>
                    
                    <div class="debug-section">
                        <h3>Configuración con Tabs</h3>
                        
                        <ul class="nav nav-tabs" id="configTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="sedes-tab" data-bs-toggle="tab" 
                                        data-bs-target="#sedes" type="button" role="tab">
                                    <i class="fas fa-building"></i> Sedes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="establecimientos-tab" data-bs-toggle="tab" 
                                        data-bs-target="#establecimientos" type="button" role="tab">
                                    <i class="fas fa-briefcase"></i> Establecimientos
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="configTabContent">
                            <div class="tab-pane fade show active" id="sedes" role="tabpanel">
                                <div class="p-3">
                                    <h4>Gestión de Sedes</h4>
                                    <div id="sedesContent">
                                        <div class="loading">
                                            <i class="fas fa-spinner fa-spin"></i> Cargando sedes...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="establecimientos" role="tabpanel">
                                <div class="p-3">
                                    <h4>Gestión de Establecimientos</h4>
                                    <div id="establecimientosContent">
                                        <div class="loading">
                                            <i class="fas fa-spinner fa-spin"></i> Cargando establecimientos...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery (required for Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Debug version of configuration functions
        const configDebug = {
            init: function() {
                console.log('Inicializando configuración debug...');
                this.bindEvents();
                this.loadSedesData();
            },
            
            bindEvents: function() {
                document.querySelectorAll('#configTabs button[data-bs-toggle="tab"]').forEach(tab => {
                    tab.addEventListener('shown.bs.tab', (e) => {
                        const targetTab = e.target.getAttribute('data-bs-target').replace('#', '');
                        console.log('Cambiando a tab:', targetTab);
                        if (targetTab === 'establecimientos') {
                            this.loadEstablecimientosData();
                        }
                    });
                });
            },
            
            loadSedesData: function() {
                console.log('Cargando sedes...');
                fetch('./api/configuracion/sedes.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include'
                })
                .then(response => {
                    console.log('Sedes response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Sedes response text:', text);
                    try {
                        const data = JSON.parse(text);
                        this.renderSedesTable(data.sedes || []);
                    } catch (e) {
                        document.getElementById('sedesContent').innerHTML = 
                            `<div class="error">Error parsing JSON: ${e.message}<br>Response: ${text}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error cargando sedes:', error);
                    document.getElementById('sedesContent').innerHTML = 
                        `<div class="error">Error: ${error.message}</div>`;
                });
            },
            
            loadEstablecimientosData: function() {
                console.log('Cargando establecimientos...');
                fetch('./api/configuracion/establecimientos.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include'
                })
                .then(response => {
                    console.log('Establecimientos response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Establecimientos response text:', text);
                    try {
                        const data = JSON.parse(text);
                        this.renderEstablecimientosTable(data.establecimientos || []);
                    } catch (e) {
                        document.getElementById('establecimientosContent').innerHTML = 
                            `<div class="error">Error parsing JSON: ${e.message}<br>Response: ${text}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error cargando establecimientos:', error);
                    document.getElementById('establecimientosContent').innerHTML = 
                        `<div class="error">Error: ${error.message}</div>`;
                });
            },
            
            renderSedesTable: function(sedes) {
                const content = `
                    <div class="success">✓ Sedes cargadas: ${sedes.length}</div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Dirección</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sedes.map(sede => `
                                <tr>
                                    <td>${sede.id}</td>
                                    <td>${sede.nombre}</td>
                                    <td>${sede.direccion || 'No especificada'}</td>
                                    <td><span class="badge bg-success">${sede.estado}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                document.getElementById('sedesContent').innerHTML = content;
            },
            
            renderEstablecimientosTable: function(establecimientos) {
                const content = `
                    <div class="success">✓ Establecimientos cargados: ${establecimientos.length}</div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Sede</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${establecimientos.map(est => `
                                <tr>
                                    <td>${est.id}</td>
                                    <td>${est.nombre}</td>
                                    <td>${est.sede_nombre || 'Sin sede'}</td>
                                    <td><span class="badge bg-success">${est.estado}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                document.getElementById('establecimientosContent').innerHTML = content;
            }
        };
        
        // Test functions for buttons
        function testSedesAPI() {
            configDebug.loadSedesData();
            addAPIResult('Sedes API', 'Test ejecutado, ver consola y tabla');
        }
        
        function testEstablecimientosAPI() {
            configDebug.loadEstablecimientosData();
            addAPIResult('Establecimientos API', 'Test ejecutado, ver consola y tabla');
        }
        
        function addAPIResult(title, content) {
            const results = document.getElementById('apiResults');
            const div = document.createElement('div');
            div.className = 'api-result';
            div.innerHTML = `<strong>${title}:</strong> ${content}`;
            results.appendChild(div);
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Asegurar que el dropdown del usuario funcione
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuBtn && userMenu) {
                userMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    userMenu.classList.toggle('show');
                });
                
                // Cerrar el menú al hacer click fuera
                document.addEventListener('click', function(e) {
                    if (!userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.remove('show');
                    }
                });
                
                // Cerrar con escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userMenu.classList.remove('show');
                    }
                });
            }
            
            // Inicializar debug
            configDebug.init();
        });
    </script>
</body>
</html>