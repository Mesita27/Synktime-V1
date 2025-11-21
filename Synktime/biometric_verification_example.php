<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia Biométrica - SNKTIME</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Incluir el modal de verificación -->
    <?php include 'components/biometric_verification_modal.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 bg-dark">
                <div class="sidebar">
                    <h5 class="text-white p-3">SNKTIME</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="attendance.php">
                                <i class="fas fa-calendar-check"></i> Asistencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="employees.php">
                                <i class="fas fa-users"></i> Empleados
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reportes
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-check"></i> Sistema de Asistencia</h2>
                    <div>
                        <button type="button" class="btn btn-success" onclick="openVerificationModal()">
                            <i class="fas fa-fingerprint"></i> Nueva Verificación
                        </button>
                    </div>
                </div>

                <!-- Lista de empleados para verificación -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Empleados Disponibles</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="employeesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>Estado Biométrico</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="employeesTableBody">
                                    <!-- Los empleados se cargarán dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Historial de verificaciones recientes -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Verificaciones Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="verificationHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Empleado</th>
                                        <th>Método</th>
                                        <th>Resultado</th>
                                        <th>Confianza</th>
                                    </tr>
                                </thead>
                                <tbody id="verificationHistoryTableBody">
                                    <!-- El historial se cargará dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Incluir el JavaScript del modal de verificación -->
    <script src="js/biometric_verification.js"></script>

    <script>
        // Función para abrir el modal de verificación
        function openVerificationModal(employeeId = null, attendanceType = 'ENTRADA') {
            const modal = document.getElementById('biometricVerificationModal');

            if (employeeId) {
                // Si se proporciona un ID de empleado, configurarlo
                modal.setAttribute('data-employee-id', employeeId);
                modal.setAttribute('data-attendance-type', attendanceType);
            } else {
                // Si no se proporciona, quitar los atributos para que el modal los solicite
                modal.removeAttribute('data-employee-id');
                modal.removeAttribute('data-attendance-type');
            }

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

        // Función para verificar empleado específico
        function verifyEmployee(employeeId, attendanceType = 'ENTRADA') {
            openVerificationModal(employeeId, attendanceType);
        }

        // Cargar empleados al iniciar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadEmployees();
            loadVerificationHistory();
        });

        // Función para cargar empleados
        async function loadEmployees() {
            try {
                const response = await fetch('api/employees.php');
                if (response.ok) {
                    const employees = await response.json();
                    displayEmployees(employees);
                } else {
                    console.error('Error al cargar empleados');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Función para mostrar empleados en la tabla
        function displayEmployees(employees) {
            const tbody = document.getElementById('employeesTableBody');
            tbody.innerHTML = '';

            employees.forEach(employee => {
                const row = document.createElement('tr');

                // Estado biométrico
                let biometricStatus = '<span class="badge bg-secondary">Sin datos</span>';
                if (employee.has_biometric_data) {
                    const methods = [];
                    if (employee.facial_registered) methods.push('Facial');
                    if (employee.fingerprint_registered) methods.push('Huella');
                    if (employee.rfid_registered) methods.push('RFID');

                    biometricStatus = methods.length > 0 ?
                        `<span class="badge bg-success">${methods.join(', ')}</span>` :
                        '<span class="badge bg-warning">Pendiente</span>';
                }

                row.innerHTML = `
                    <td>${employee.id}</td>
                    <td>${employee.nombre}</td>
                    <td>${employee.apellido}</td>
                    <td>${biometricStatus}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary"
                                    onclick="verifyEmployee(${employee.id}, 'ENTRADA')"
                                    title="Registrar Entrada">
                                <i class="fas fa-sign-in-alt"></i> Entrada
                            </button>
                            <button type="button" class="btn btn-outline-success"
                                    onclick="verifyEmployee(${employee.id}, 'SALIDA')"
                                    title="Registrar Salida">
                                <i class="fas fa-sign-out-alt"></i> Salida
                            </button>
                        </div>
                    </td>
                `;

                tbody.appendChild(row);
            });
        }

        // Función para cargar historial de verificaciones
        async function loadVerificationHistory() {
            try {
                const response = await fetch('api/verification_history.php?limit=10');
                if (response.ok) {
                    const history = await response.json();
                    displayVerificationHistory(history);
                } else {
                    console.error('Error al cargar historial');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Función para mostrar historial de verificaciones
        function displayVerificationHistory(history) {
            const tbody = document.getElementById('verificationHistoryTableBody');
            tbody.innerHTML = '';

            if (history.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="5" class="text-center text-muted">No hay verificaciones recientes</td>';
                tbody.appendChild(row);
                return;
            }

            history.forEach(record => {
                const row = document.createElement('tr');

                const confidencePercent = record.confidence ? (record.confidence * 100).toFixed(1) + '%' : 'N/A';
                const resultBadge = record.success ?
                    '<span class="badge bg-success">Exitosa</span>' :
                    '<span class="badge bg-danger">Fallida</span>';

                row.innerHTML = `
                    <td>${new Date(record.created_at).toLocaleString()}</td>
                    <td>${record.employee_name}</td>
                    <td>${record.verification_type}</td>
                    <td>${resultBadge}</td>
                    <td>${confidencePercent}</td>
                `;

                tbody.appendChild(row);
            });
        }

        // Función para refrescar datos
        function refreshData() {
            loadEmployees();
            loadVerificationHistory();
        }

        // Auto-refrescar cada 30 segundos
        setInterval(refreshData, 30000);
    </script>

    <style>
        .sidebar {
            min-height: 100vh;
        }

        .sidebar .nav-link {
            padding: 0.75rem 1rem;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            background-color: #0d6efd;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .table th {
            border-top: none;
            font-weight: 600;
        }

        .badge {
            font-size: 0.75rem;
        }
    </style>
</body>
</html>
