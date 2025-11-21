<?php
require_once 'auth/session.php';
requireModuleAccess('asistencia'); // Verificar permisos para módulo de asistencia
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencias | SynkTime</title>
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
    <link rel="stylesheet" href="assets/css/attendance.css">
    
    <!-- Zona horaria de Bogotá -->
    <script src="js/timezone-bogota.js"></script>
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
                <h2 class="page-title"><i class="fas fa-calendar-check"></i> Asistencias</h2>
                <div class="employee-actions">
                    <button class="btn btn-primary me-2" id="btnRegisterAttendance" onclick="abrirModalRegistro()">
                        <i class="fas fa-plus"></i> Registro Manual
                    </button>
                    <button class="btn btn-success me-2" id="btnAutoIdentification" onclick="abrirIdentificacionAutomatica()">
                        <i class="fas fa-search"></i> Identificación Automática
                    </button>
                    <button class="btn btn-info" id="btnHelpAttendance" onclick="showAttendanceHelpModal()" style="background-color: #007bff; border-color: #007bff; color: white;">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </button>
                </div>
            </div>
            
            <!-- Componente de consulta -->
            <?php include 'components/attendance_query.php'; ?>

            <!-- Los controles de paginación se insertan aquí automáticamente -->
            
            <!-- Tabla de asistencias -->
            <div class="employee-table-container">
                <table class="employee-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Establecimiento</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Horas Registradas</th>
                            <th>Estado</th>
                            <th>Foto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- JS: aquí se cargan las asistencias -->
                    </tbody>
                </table>
            </div>
            
            <!-- Include Attendance Modals -->
            <?php include 'components/attendance_modals.php'; ?>
            <?php include 'components/attendance_register_modal.php'; ?>
            <?php include 'components/attendance_biometric_modal.php'; ?>
            <?php include 'components/attendance_photo_modal.php'; ?>
            <?php include 'components/attendance_observation_modal.php'; ?>
            <?php include 'components/justificaciones_modal.php'; ?>

            <!-- Modal de advertencia para entradas abiertas sin salida -->
            <div class="modal fade" id="openEntriesWarningModal" tabindex="-1" aria-hidden="true" aria-labelledby="openEntriesWarningTitle">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="openEntriesWarningTitle">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Entradas abiertas detectadas
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3" id="openEntriesWarningSummary">
                                Se detectaron registros de entrada sin salida. Por favor revisa los siguientes casos antes de cerrar sesión.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Código</th>
                                            <th>Establecimiento</th>
                                            <th>Sede</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody id="openEntriesWarningList">
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-spinner fa-spin me-2"></i>Verificando registros abiertos...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-warning mt-3 d-none" id="openEntriesWarningError" role="alert"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger" id="confirmLogoutWithOpenEntries">
                                Cerrar sesión de todos modos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/attendance-schedule-enhancement.js"></script>
<script src="assets/js/attendance.js"></script>
<script src="assets/js/biometric.js"></script>
<script src="js/biometric_verification.js"></script>
<script src="assets/js/justificaciones_v2.js"></script>
<script src="assets/js/attendance-open-entry-guard.js"></script>

<!-- Configuración del servicio Python -->
<script>
    (function () {
        const pythonService = window.SynktimePythonService;
        const PYTHON_SERVICE_URL = pythonService ? pythonService.getBaseUrl() : (window.SYNKTIME?.pythonService?.baseUrl || 'http://127.0.0.1:8000');
        const API_BASE_URL = 'api';

        const BIOMETRIC_API_BASE = window.location.origin + window.location.pathname.replace('/attendance.php', '') + '/api/attendance/';

        console.log('🔧 API Base configurada:', BIOMETRIC_API_BASE);
        console.log('Configuración del servicio Python:', PYTHON_SERVICE_URL);

        window.SYNKTIME = window.SYNKTIME || {};
        window.SYNKTIME.pythonService = window.SYNKTIME.pythonService || {};
        window.SYNKTIME.pythonService.baseUrl = PYTHON_SERVICE_URL;
        window.SYNKTIME.pythonService.healthUrl = pythonService ? pythonService.getHealthUrl() : (PYTHON_SERVICE_URL.replace(/\/+$/, '') + '/' + (window.SYNKTIME.pythonService.healthPath || 'healthz'));
        window.SYNKTIME.pythonService.healthPath = pythonService ? pythonService.getHealthPath() : (window.SYNKTIME.pythonService.healthPath || 'healthz');
        window.SYNKTIME.pythonService.timeout = pythonService ? pythonService.getTimeout() : (window.SYNKTIME.pythonService.timeout || 30);

        window.PYTHON_SERVICE_URL = PYTHON_SERVICE_URL;
        window.API_BASE_URL = API_BASE_URL;
        window.BIOMETRIC_API_BASE = BIOMETRIC_API_BASE;
    })();
</script>

<!-- Función de prueba directa -->
<script>
// Las funciones de prueba han sido comentadas para evitar conflictos
// con las implementaciones en attendance.js

// Función para abrir el modal directamente si es necesario
function abrirModalRegistro() {
    console.log('Abriendo modal de registro de asistencia...');
    
    const modal = document.getElementById('attendanceRegisterModal');
    if (!modal) {
        console.error('Modal attendanceRegisterModal no encontrado');
        alert('Error: Modal de registro no encontrado');
        return;
    }
    
    // Mostrar el modal
    modal.style.display = 'block';
    document.body.classList.add('modal-open');
    
    // Inicializar fecha usando zona horaria de Bogotá
    const fechaSpan = document.getElementById('reg_fecha');
    if (fechaSpan) {
        fechaSpan.textContent = window.Bogota.toLocaleDateString();
    }
    
    // Cargar sedes
    cargarSedesRegistro();
    
    // Configurar eventos
    configurarEventosModal();
    
    console.log('Modal de registro abierto exitosamente');
}

// Función para cerrar el modal de registro
function closeAttendanceRegisterModal() {
    console.log('Cerrando modal de registro de asistencia...');
    
    const modal = document.getElementById('attendanceRegisterModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Limpiar el contenido del modal
        const tbody = document.getElementById('attendanceRegisterTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="no-data-text">
                        <div class="text-center p-3">
                            <i class="fas fa-filter fa-2x mb-2 text-muted"></i>
                            <p>Para ver empleados, seleccione al menos un filtro:</p>
                            <ul class="mt-2 list-unstyled">
                                <li><i class="fas fa-building text-primary"></i> Seleccione una sede, o</li>
                                <li><i class="fas fa-store text-success"></i> Seleccione un establecimiento, o</li>
                                <li><i class="fas fa-id-card text-info"></i> Ingrese un código de empleado</li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        // Limpiar filtros
        const sedeSel = document.getElementById('reg_sede');
        const estabSel = document.getElementById('reg_establecimiento');
        const codigoInput = document.getElementById('codigoRegistroBusqueda');
        
        if (sedeSel) sedeSel.value = '';
        if (estabSel) estabSel.value = '';
        if (codigoInput) codigoInput.value = '';
        
        console.log('Modal de registro cerrado y limpiado');
    }
}

// Función para cargar sedes en el modal de registro
async function cargarSedesRegistro() {
    try {
        console.log('Cargando sedes para modal de registro...');
        
        const response = await fetch('api/get-sedes.php', {
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        const sedeSel = document.getElementById('reg_sede');
        if (sedeSel && data.success && data.sedes) {
            sedeSel.innerHTML = '<option value="">Seleccionar Sede</option>';
            data.sedes.forEach(sede => {
                sedeSel.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
            });
            console.log(`${data.sedes.length} sedes cargadas en modal`);
        } else {
            throw new Error(data.message || 'Error al cargar sedes');
        }
    } catch (error) {
        console.error('Error cargando sedes:', error);
        mostrarErrorModal('Error al cargar sedes: ' + error.message);
    }
}

// Función para mostrar errores en el modal
function mostrarErrorModal(mensaje) {
    const tbody = document.getElementById('attendanceRegisterTableBody');
    if (tbody) {
        const mensajeTexto = typeof mensaje === 'string' ? mensaje : String(mensaje ?? '');
        const mensajeLimpio = mensajeTexto.trim();
        const esError = /❌|error/i.test(mensajeLimpio);
        const esExito = /✅|exitoso/i.test(mensajeLimpio);

        const bannerStyle = esError
            ? 'background: #fee2e2; border: 1px solid #fecaca; color: #7f1d1d;'
            : esExito
                ? 'background: #dcfce7; border: 1px solid #a7f3d0; color: #065f46;'
                : 'background: #eef2ff; border: 1px solid #c7d2fe; color: #312e81;';

        const icono = esError
            ? '<i class="fas fa-exclamation-triangle fa-2x mb-3"></i>'
            : esExito
                ? '<i class="fas fa-check-circle fa-2x mb-3"></i>'
                : '<i class="fas fa-info-circle fa-2x mb-3"></i>';

        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center p-4">
                    <div class="modal-message-banner" style="${bannerStyle} padding: 18px 20px; border-radius: 14px; display: inline-block; width: 100%; max-width: 560px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);">
                        ${icono}
                        <div style="font-size: 1rem; font-weight: 600; margin-bottom: 6px;">${esError ? 'Se detectó un problema' : esExito ? 'Operación exitosa' : 'Actualización'}</div>
                        <div style="font-size: 0.95rem; font-weight: 500; line-height: 1.6;">${mensajeTexto}</div>
                    </div>
                </td>
            </tr>
        `;
    }
}

// Función para buscar empleados por código
async function buscarEmpleadoPorCodigo() {
    const codigoInput = document.getElementById('codigoRegistroBusqueda');
    const sedeSel = document.getElementById('reg_sede');
    const estabSel = document.getElementById('reg_establecimiento');
    
    if (!codigoInput || !codigoInput.value.trim()) {
        mostrarErrorModal('Por favor ingrese un código de empleado');
        return;
    }
    
    const codigo = codigoInput.value.trim();
    console.log('Buscando empleado con código:', codigo);
    
    try {
        // Construir URL con filtros
        let url = `api/get-employee-by-code.php?codigo=${encodeURIComponent(codigo)}`;
        
        if (sedeSel && sedeSel.value) {
            url += `&sede_id=${sedeSel.value}`;
        }
        
        if (estabSel && estabSel.value) {
            url += `&establecimiento_id=${estabSel.value}`;
        }
        
        const response = await fetch(url, {
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.empleado) {
            // Verificar si el empleado tiene horario para hoy - VALIDACIÓN ESTRICTA
            const horarioValido = await verificarHorarioEmpleado(data.empleado.ID_EMPLEADO);

            if (!horarioValido.tieneHorario) {
                // MOSTRAR ERROR ESTRICTO - NO PERMITIR CONTINUAR
                mostrarErrorModal(`❌ ERROR: El empleado ${data.empleado.NOMBRE} ${data.empleado.APELLIDO} no tiene horario asignado para hoy. No se puede registrar asistencia.`);
                return;
            }

            if (!horarioValido.puedeRegistrar) {
                // MOSTRAR ERROR ESTRICTO - ENTRADA ABIERTA
                mostrarErrorModal(`❌ ERROR: ${horarioValido.mensaje}`);
                return;
            }

            // Solo mostrar empleado si tiene horarios válidos
            mostrarEmpleadoEnTabla(data.empleado, horarioValido);
        } else {
            mostrarErrorModal(data.message || 'Empleado no encontrado');
        }
    } catch (error) {
        console.error('Error buscando empleado:', error);
        mostrarErrorModal('Error al buscar empleado: ' + error.message);
    }
}

// Función para verificar si el empleado tiene horario asignado para hoy
async function verificarHorarioEmpleado(employeeId) {
    try {
        const hoy = window.Bogota.getDateString(); // YYYY-MM-DD en zona horaria de Bogotá
        const diaSemana = window.Bogota.getDayOfWeek(); // 0=domingo, 1=lunes, etc.

        const response = await fetch(`api/check-employee-schedule.php?employee_id=${employeeId}&fecha=${hoy}&dia_semana=${diaSemana}`, {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        return {
            tieneHorario: data.success && data.tiene_horario,
            puedeRegistrar: data.success && data.puede_registrar !== false, // Nuevo campo
            horario: data.horario || null,
            mensaje: data.message || ''
        };
    } catch (error) {
        console.error('Error verificando horario:', error);
        return {
            tieneHorario: false,
            puedeRegistrar: false,
            horario: null,
            mensaje: 'Error al verificar horario: ' + error.message
        };
    }
}

// Función para mostrar empleado en la tabla
function mostrarEmpleadoEnTabla(empleado, horarioInfo) {
    const tbody = document.getElementById('attendanceRegisterTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr>
            <td>${empleado.ID_EMPLEADO}</td>
            <td>${empleado.NOMBRE} ${empleado.APELLIDO || ''}</td>
            <td>${empleado.ESTABLECIMIENTO_NOMBRE || 'N/A'}</td>
            <td>${empleado.SEDE_NOMBRE || 'N/A'}</td>
            <td>
                <button class="btn btn-success btn-sm" onclick="registrarAsistenciaEmpleado(${empleado.ID_EMPLEADO}, '${empleado.NOMBRE}', '${empleado.APELLIDO || ''}')">
                    <i class="fas fa-check"></i> Registrar
                </button>
            </td>
        </tr>
    `;
}

// Función para registrar asistencia de empleado
async function registrarAsistenciaEmpleado(employeeId, nombre, apellido) {
    try {
        // Verificar nuevamente el horario antes de registrar
        const validacionHorario = await validarHorariosAntesRegistro(employeeId, 'registro de asistencia');
        
        if (!validacionHorario.valido) {
            mostrarErrorValidacion(validacionHorario.mensaje, 'Horario No Válido');
            return;
        }
        
        // Mostrar opciones de tipo de registro
        mostrarOpcionesRegistro(employeeId, nombre, apellido, validacionHorario.horario);
        
    } catch (error) {
        console.error('Error al registrar asistencia:', error);
        mostrarErrorModal('Error al procesar registro: ' + error.message);
    }
}

// Función para mostrar opciones de registro (Entrada/Salida)
function mostrarOpcionesRegistro(employeeId, nombre, apellido, horario) {
    const tbody = document.getElementById('attendanceRegisterTableBody');
    if (!tbody) return;
    
    const nombreCompleto = `${nombre} ${apellido || ''}`.trim();
    
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center p-4">
                <div class="registro-opciones">
                    <h5>Registrar asistencia para: ${nombreCompleto}</h5>
                    <p><strong>Horario:</strong> ${horario.hora_entrada} - ${horario.hora_salida}</p>
                    <div class="mt-3 d-flex flex-column align-items-center gap-3">
                        <button class="btn btn-primary" onclick="procesarRegistroAsistencia(${employeeId}, 'ENTRADA', '${nombreCompleto}')">
                            <i class="fas fa-sign-in-alt"></i> Registrar Entrada Manual
                        </button>
                        <small class="text-muted" style="max-width: 520px; line-height: 1.6;">
                            Este registro manual solo permite <strong>entradas</strong>. Si existe una entrada abierta,
                            primero debe registrarse la salida correspondiente desde los flujos biométricos u operativos autorizados.
                        </small>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

// Función para procesar el registro de asistencia
async function procesarRegistroAsistencia(employeeId, tipo, nombreCompleto) {
    try {
        if (tipo !== 'ENTRADA') {
            console.warn('El registro manual solo admite entradas. Petición ignorada para tipo:', tipo);
            mostrarErrorModal('El registro manual solo permite registrar entradas.');
            return;
        }

        mostrarErrorModal('Verificando horario...');

        // Verificación adicional de horario antes de procesar
        const validacionHorario = await validarHorariosAntesRegistro(employeeId, `registro de ${tipo}`);

        if (!validacionHorario.valido) {
            mostrarErrorValidacion(validacionHorario.mensaje, 'Horario No Válido');
            return;
        }

        mostrarErrorModal('Procesando registro...');

        const response = await fetch('api/register_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                employee_id: employeeId,
                type: tipo,
                timestamp: window.Bogota.getISOString(), // ISO con zona horaria de Bogotá
                verification_method: 'manual'
            })
        });

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('Respuesta del registro:', data);

        if (data.success) {
            // Usar el tipo de registro devuelto por el API
            const tipoRegistrado = data.attendance_type || tipo || 'REGISTRO';
            mostrarErrorModal(`✅ Registro exitoso: ${tipoRegistrado} de ${nombreCompleto}`);

            // Cerrar modal después de 2 segundos
            setTimeout(() => {
                closeAttendanceRegisterModal();
            }, 2000);
        } else {
            throw new Error(data.message || data.error || 'Error en el registro');
        }

    } catch (error) {
        console.error('Error procesando registro:', error);
        mostrarErrorModal(`❌ Error en registro: ${error.message}`);
    }
}

// Configurar eventos para los selectores
function configurarEventosModal() {
    const sedeSel = document.getElementById('reg_sede');
    if (sedeSel) {
        sedeSel.addEventListener('change', function() {
            const sedeId = this.value;
            console.log('Sede seleccionada:', sedeId);
            cargarEstablecimientosPorSede(sedeId);
        });
        console.log('Event listener agregado a sede selector');
    }
}

// Nueva función para cargar establecimientos por sede
async function cargarEstablecimientosPorSede(sedeId) {
    try {
        console.log('Cargando establecimientos para sede:', sedeId);
        const estabSel = document.getElementById('reg_establecimiento');
        
        if (!estabSel) return;
        
        estabSel.innerHTML = '<option value="">Cargando...</option>';
        
        let url = 'api/get-establecimientos.php';
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
        }
        
        const response = await fetch(url);
        console.log('Response status establecimientos:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Establecimientos recibidos:', data);
        
        estabSel.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
        
        if (data.establecimientos && Array.isArray(data.establecimientos)) {
            data.establecimientos.forEach(est => {
                estabSel.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
            });
            console.log(`${data.establecimientos.length} establecimientos cargados para sede ${sedeId || 'todas'}`);
        }
    } catch (error) {
        console.error('Error cargando establecimientos por sede:', error);
        const estabSel = document.getElementById('reg_establecimiento');
        if (estabSel) {
            estabSel.innerHTML = '<option value="">Error al cargar</option>';
        }
    }
}

// Funciones de carga simplificadas
async function cargarSedesRegistroTest() {
    try {
        console.log('Iniciando carga de sedes...');
        const response = await fetch('/api/get-sedes.php');
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Datos de sedes recibidos:', data);
        
        const sedeSel = document.getElementById('reg_sede');
        console.log('Elemento reg_sede:', sedeSel);
        
        if (sedeSel && data.sedes && Array.isArray(data.sedes)) {
            sedeSel.innerHTML = '<option value="">Seleccionar una Sede</option>';
            data.sedes.forEach(sede => {
                sedeSel.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
            });
            console.log(`${data.sedes.length} sedes cargadas exitosamente`);
        } else {
            console.error('Elemento reg_sede no encontrado o datos inválidos');
            console.log('data.sedes:', data.sedes);
        }
    } catch (error) {
        console.error('Error cargando sedes:', error);
    }
}

async function cargarEstablecimientosRegistroTest() {
    try {
        console.log('Iniciando carga de establecimientos...');
        const response = await fetch('/api/get-establecimientos.php');
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Datos de establecimientos recibidos:', data);
        
        const estabSel = document.getElementById('reg_establecimiento');
        console.log('Elemento reg_establecimiento:', estabSel);
        
        if (estabSel && data.establecimientos && Array.isArray(data.establecimientos)) {
            estabSel.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
            data.establecimientos.forEach(est => {
                estabSel.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
            });
            console.log(`${data.establecimientos.length} establecimientos cargados exitosamente`);
        } else {
            console.error('Elemento reg_establecimiento no encontrado o datos inválidos');
            console.log('data.establecimientos:', data.establecimientos);
        }
    } catch (error) {
        console.error('Error cargando establecimientos:', error);
    }
}

async function cargarEmpleadosParaRegistroTest() {
    try {
        console.log('Iniciando carga de empleados (TODOS)...');
        
        // Usar la API corregida que muestra más empleados
        const response = await fetch('/api/employee/list-fixed.php?limit=100');
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Datos de empleados recibidos:', data);
        
        const tbody = document.getElementById('empleados-registro-tbody');
        console.log('Elemento empleados-registro-tbody:', tbody);
        
        if (tbody && data.success && data.data && Array.isArray(data.data)) {
            tbody.innerHTML = '';
            
            // Agrupar empleados por sede para mejor organización
            const empleadosPorSede = {};
            data.data.forEach(emp => {
                const sede = emp.sede || 'Sin Sede';
                if (!empleadosPorSede[sede]) {
                    empleadosPorSede[sede] = [];
                }
                empleadosPorSede[sede].push(emp);
            });
            
            // Mostrar empleados agrupados por sede
            Object.keys(empleadosPorSede).sort().forEach(sede => {
                // Agregar encabezado de sede
                const headerRow = `
                    <tr class="sede-header" style="background-color: #f8f9fa; font-weight: bold;">
                        <td colspan="4" style="text-align: center; padding: 8px;">
                            📍 ${sede} (${empleadosPorSede[sede].length} empleados)
                        </td>
                    </tr>
                `;
                tbody.innerHTML += headerRow;
                
                // Agregar empleados de la sede
                empleadosPorSede[sede].forEach(emp => {
                    const row = `
                        <tr onclick="seleccionarEmpleadoRegistro(${emp.id}, '${emp.nombre}', '${emp.apellido || ''}')" 
                            style="cursor: pointer;" 
                            onmouseover="this.style.backgroundColor='#e3f2fd'" 
                            onmouseout="this.style.backgroundColor=''">
                            <td>${emp.id}</td>
                            <td>${emp.nombre} ${emp.apellido || ''}</td>
                            <td>${emp.establecimiento || 'N/A'}</td>
                            <td>${sede}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            });
            
            console.log(`✅ ${data.data.length} empleados cargados exitosamente de ${Object.keys(empleadosPorSede).length} sedes`);
            
            // Mostrar información adicional en consola
            if (data.user_info) {
                console.log(`ℹ️ Usuario empresa: ${data.user_info.empresa_id}, Rol: ${data.user_info.role}`);
                console.log(`ℹ️ Filtro por empresa aplicado: ${data.user_info.empresa_filter_applied ? 'Sí' : 'No'}`);
            }
            
        } else {
            console.error('❌ Elemento empleados-registro-tbody no encontrado o datos inválidos');
            console.log('data:', data);
            
            // Mostrar mensaje de error en la tabla
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: #dc3545;">
                            ❌ Error al cargar empleados: ${data.message || 'Datos no válidos'}
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('❌ Error cargando empleados:', error);
        
        // Mostrar error en la tabla
        const tbody = document.getElementById('empleados-registro-tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px; color: #dc3545;">
                        ❌ Error de conexión: ${error.message}
                    </td>
                </tr>
            `;
        }
    }
}

// Función para seleccionar empleado del registro
function seleccionarEmpleadoRegistro(id, nombre, apellido) {
    console.log('Empleado seleccionado:', {id, nombre, apellido});
    
    // Aquí puedes agregar la lógica para registrar la asistencia
    // Por ejemplo, llenar campos ocultos o mostrar un modal de confirmación
    
    // Ejemplo: rellenar campos en el modal
    const empleadoSeleccionado = document.getElementById('empleado-seleccionado');
    if (empleadoSeleccionado) {
        empleadoSeleccionado.textContent = `${id} - ${nombre} ${apellido}`;
    }
    
    // Guardar datos para el registro
    window.selectedEmployee = {id, nombre, apellido};
    
    alert(`Empleado seleccionado: ${nombre} ${apellido} (ID: ${id})`);
}

// Event listener alternativo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - Test script');
    const btn = document.getElementById('btnRegisterAttendance');
    if (btn) {
        console.log('Botón encontrado en test script');
    } else {
        console.log('Botón NO encontrado en test script');
    }
    
    // Conectar botón de búsqueda del modal
    const btnBuscar = document.getElementById('btnBuscarCodigoRegistro');
    if (btnBuscar) {
        btnBuscar.addEventListener('click', buscarEmpleadoPorCodigo);
    }
    
    // Permitir búsqueda con Enter
    const codigoInput = document.getElementById('codigoRegistroBusqueda');
    if (codigoInput) {
        codigoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarEmpleadoPorCodigo();
            }
        });
    }
});

// Función para validar empleado antes de abrir modal de registro
async function validarEmpleadoAntesRegistro() {
    console.log('Abriendo modal de validación previa...');
    
    const modal = document.getElementById('employeeValidationModal');
    if (!modal) {
        console.error('Modal employeeValidationModal no encontrado');
        alert('Error: Modal de validación no encontrado');
        return;
    }
    
    // Limpiar campos
    const codeInput = document.getElementById('validationEmployeeCode');
    const messageDiv = document.getElementById('validationMessage');
    
    if (codeInput) codeInput.value = '';
    if (messageDiv) {
        messageDiv.className = 'alert d-none';
        messageDiv.textContent = '';
    }
    
    // Mostrar modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Enfocar el campo de código
    setTimeout(() => {
        if (codeInput) codeInput.focus();
    }, 500);
}

// Función para validar empleado y proceder con el registro
async function validarYProceder() {
    const codeInput = document.getElementById('validationEmployeeCode');
    const messageDiv = document.getElementById('validationMessage');
    
    if (!codeInput || !messageDiv) return;
    
    const employeeCode = codeInput.value.trim();
    
    if (!employeeCode) {
        mostrarMensajeValidacion('Por favor ingrese un código de empleado', 'warning');
        codeInput.focus();
        return;
    }
    
    try {
        // Mostrar mensaje de carga
        mostrarMensajeValidacion('Validando empleado...', 'info');
        
        // Buscar empleado por código
        const response = await fetch(`api/get-employee-by-code.php?codigo=${encodeURIComponent(employeeCode)}`, {
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success || !data.empleado) {
            mostrarMensajeValidacion(data.message || 'Empleado no encontrado', 'danger');
            return;
        }
        
        const empleado = data.empleado;
        
        // Verificar si el empleado tiene horario para hoy
        const horarioValido = await verificarHorarioEmpleado(empleado.ID_EMPLEADO);
        
        if (!horarioValido.tieneHorario) {
            mostrarMensajeValidacion(
                `El empleado ${empleado.NOMBRE} ${empleado.APELLIDO} no tiene horario asignado para hoy. No se puede registrar asistencia.`,
                'danger'
            );
            return;
        }

        if (!horarioValido.puedeRegistrar) {
            mostrarMensajeValidacion(horarioValido.mensaje, 'danger');
            return;
        }
        
        // Si tiene horario, cerrar modal de validación y abrir modal de registro
        const validationModal = bootstrap.Modal.getInstance(document.getElementById('employeeValidationModal'));
        if (validationModal) {
            validationModal.hide();
        }
        
        // Proceder con el registro normal
        setTimeout(() => {
            abrirModalRegistroConEmpleado(empleado, horarioValido.horario);
        }, 300);
        
    } catch (error) {
        console.error('Error validando empleado:', error);
        mostrarMensajeValidacion('Error al validar empleado: ' + error.message, 'danger');
    }
}

// Función auxiliar para mostrar mensajes en el modal de validación
function mostrarMensajeValidacion(mensaje, tipo) {
    const messageDiv = document.getElementById('validationMessage');
    if (messageDiv) {
        messageDiv.textContent = mensaje;
        messageDiv.className = `alert alert-${tipo}`;
        messageDiv.classList.remove('d-none');
    }
}

// Función para abrir modal de registro con empleado ya validado
function abrirModalRegistroConEmpleado(empleado, horario) {
    console.log('Abriendo modal de registro con empleado validado:', empleado);
    
    // Llenar automáticamente el campo de código
    const codeInput = document.getElementById('codigoRegistroBusqueda');
    if (codeInput) {
        codeInput.value = empleado.CODIGO || '';
    }
    
    // Abrir el modal de registro
    abrirModalRegistro();
    
    // Después de un breve delay, simular la búsqueda del empleado
    setTimeout(() => {
        buscarEmpleadoPorCodigo();
    }, 500);
}

// Función para abrir identificación automática
function abrirIdentificacionAutomatica() {
    console.log('Abriendo identificación automática...');
    
    // Función para abrir la identificación automática
    const openAutoIdentification = () => {
        try {
            if (typeof window.openBiometricAutoIdentification === 'function') {
                window.openBiometricAutoIdentification('ENTRADA');
                console.log('Modal de identificación automática abierto exitosamente');
            } else {
                // Fallback: crear función básica si no está disponible
                console.warn('Función openBiometricAutoIdentification no encontrada, creando fallback...');
                
                // Verificar si el modal existe
                const modalElement = document.getElementById('biometricVerificationModal');
                if (!modalElement) {
                    alert('Error: El modal biométrico no está disponible. Recargue la página.');
                    return;
                }
                
                // Abrir modal manualmente
                const modal = new bootstrap.Modal(modalElement);
                
                // Configurar modo automático
                const modalTitle = document.getElementById('biometricVerificationModalLabel');
                if (modalTitle) {
                    modalTitle.innerHTML = '<i class="fas fa-search"></i> Verificación Automática';
                }
                
                // Configurar tipo de asistencia
                const attendanceTypeElement = document.getElementById('verification-attendance-type');
                if (attendanceTypeElement) {
                    attendanceTypeElement.value = 'ENTRADA';
                }
                
                // Limpiar información de empleado
                const employeeIdElement = document.getElementById('verification-employee-id');
                const employeeCodeElement = document.getElementById('verification-employee-code');
                const employeeNameElement = document.getElementById('verification-employee-name');
                
                if (employeeIdElement) employeeIdElement.value = '';
                if (employeeCodeElement) employeeCodeElement.textContent = 'Por identificar...';
                if (employeeNameElement) employeeNameElement.textContent = 'Identificación automática en progreso...';
                
                // Mostrar mensaje de modo automático
                const methodMessage = document.getElementById('biometric-method-selection-message');
                if (methodMessage) {
                    methodMessage.className = 'alert alert-primary mb-3';
                    methodMessage.innerHTML = `
                        <i class="fas fa-robot"></i> <strong>Modo de Identificación Automática</strong><br>
                        <small>El sistema identificará automáticamente al empleado usando reconocimiento facial. No es necesario seleccionar empleado previamente.</small>
                    `;
                }
                
                // Abrir modal
                modal.show();
                
                // Configurar evento para cuando se abra el modal
                modalElement.addEventListener('shown.bs.modal', function onModalShown() {
                    console.log('📱 Modal abierto, configurando modo automático...');
                    
                    // Configurar instancia del modal si está disponible
                    if (window.biometricVerificationModal || window.biometricModalInstance) {
                        const modalInstance = window.biometricModalInstance || window.biometricVerificationModal;
                        
                        if (modalInstance) {
                            modalInstance.selectedEmployee = null;
                            modalInstance.employeeData = null;
                            modalInstance.identificationMode = 'auto';
                            
                            console.log('✅ Modal configurado para identificación automática');
                            
                            // Simular clic en el botón de identificación automática si existe
                            const autoBtn = document.getElementById('startAutoIdentification');
                            if (autoBtn) {
                                console.log('🎯 Iniciando identificación automática...');
                                autoBtn.click();
                            } else {
                                console.warn('Botón de identificación automática no encontrado');
                                alert('El sistema está configurado para identificación automática. Use el botón "Iniciar Identificación" en el modal.');
                            }
                        }
                    } else {
                        console.warn('Instancia del modal biométrico no disponible');
                        alert('El modal está abierto pero la instancia biométrica no está completamente cargada. Intente usar el botón "Iniciar Identificación" manualmente.');
                    }
                    
                    // Remover listener para evitar múltiples ejecuciones
                    modalElement.removeEventListener('shown.bs.modal', onModalShown);
                });
                
                console.log('✅ Modal de identificación automática configurado (fallback)');
            }
        } catch (error) {
            console.error('Error en openAutoIdentification:', error);
            alert('Error al abrir identificación automática: ' + error.message);
        }
    };
    
    // Verificar que las funciones necesarias estén disponibles
    if (typeof window.openBiometricAutoIdentification !== 'function') {
        console.warn('Función openBiometricAutoIdentification no disponible inmediatamente, intentando fallback...');
        
        // Intentar después de un pequeño delay para permitir que se carguen los scripts
        setTimeout(() => {
            openAutoIdentification();
        }, 100);
    } else {
        // Función disponible, ejecutar inmediatamente
        openAutoIdentification();
    }
}

// Agregar event listener para Enter en el campo de código
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('validationEmployeeCode');
    if (codeInput) {
        codeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                validarYProceder();
            }
        });
    }
});

// Función global para validar horarios antes de cualquier operación de registro
async function validarHorariosAntesRegistro(employeeId, operationName = 'registro') {
    console.log(`Validating schedule for ${operationName}, employee:`, employeeId);
    
    const hoy = window.Bogota.getDateString(); // YYYY-MM-DD en zona horaria de Bogotá
    const diaSemana = window.Bogota.getDayOfWeek(); // 0=domingo, 1=lunes, etc.
    
    try {
        const response = await fetch(`api/check-employee-schedule.php?employee_id=${employeeId}&fecha=${hoy}&dia_semana=${diaSemana}`, {
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // Si no tiene horarios, retornar false con mensaje de error
        if (!data.success || !data.tiene_horario) {
            const errorMessage = data.message || 'El empleado no tiene horario asignado para hoy';
            console.log(`Schedule validation failed for ${operationName}:`, errorMessage);
            return {
                valido: false,
                mensaje: errorMessage
            };
        }
        
        console.log(`Schedule validation passed for ${operationName}`);
        return {
            valido: true,
            horario: data.horario,
            mensaje: 'Horario válido'
        };
        
    } catch (error) {
        console.error(`Error validating schedule for ${operationName}:`, error);
        return {
            valido: false,
            mensaje: 'Error al validar horario: ' + error.message
        };
    }
}

// Función mejorada para mostrar errores de validación
function mostrarErrorValidacion(mensaje, titulo = 'Error de Validación') {
    // Intentar usar modal de error si existe
    if (typeof mostrarErrorModal === 'function') {
        mostrarErrorModal(`❌ ${titulo}: ${mensaje}`);
    } else {
        alert(`❌ ${titulo}:\n\n${mensaje}`);
    }
}

// Función para mostrar el modal de ayuda del módulo de asistencia
function showAttendanceHelpModal() {
    const modal = document.getElementById('attendanceHelpModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}
</script>

<!-- Modal de Ayuda del Módulo de Asistencia -->
<div class="modal fade" id="attendanceHelpModal" tabindex="-1" aria-labelledby="attendanceHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-white" style="border-bottom: 2px solid #007bff;">
                <h5 class="modal-title" id="attendanceHelpModalLabel" style="color: #007bff;">
                    <i class="fas fa-question-circle" style="color: #007bff;"></i> Ayuda - Módulo de Asistencia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="attendanceHelpAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                <i class="fas fa-info-circle me-2"></i>¿Qué es el Módulo de Asistencia?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#attendanceHelpAccordion">
                            <div class="accordion-body">
                                <p>El <strong>Módulo de Asistencia</strong> es el componente principal del sistema SynkTime que permite gestionar y controlar el registro de asistencia de los empleados de manera eficiente y precisa.</p>
                                <p><strong>Funcionalidades principales:</strong></p>
                                <ul>
                                    <li><i class="fas fa-plus text-primary me-1"></i> <strong>Registro Manual:</strong> Permite registrar asistencias de empleados de forma manual cuando no se puede usar el sistema biométrico</li>
                                    <li><i class="fas fa-search text-success me-1"></i> <strong>Identificación Automática:</strong> Utiliza reconocimiento facial biométrico para identificar y registrar empleados automáticamente</li>
                                    <li><i class="fas fa-list text-info me-1"></i> <strong>Consulta de Registros:</strong> Visualiza y filtra todos los registros de asistencia por fecha, empleado, establecimiento, etc.</li>
                                    <li><i class="fas fa-edit text-warning me-1"></i> <strong>Corrección de Registros:</strong> Permite editar o corregir registros de asistencia cuando sea necesario</li>
                                    <li><i class="fas fa-camera text-danger me-1"></i> <strong>Registro Fotográfico:</strong> Captura y almacena fotos de los empleados durante el registro de asistencia</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <i class="fas fa-plus me-2"></i>Registro Manual de Asistencia
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#attendanceHelpAccordion">
                            <div class="accordion-body">
                                <p>El <strong>Registro Manual</strong> permite registrar asistencias cuando el sistema biométrico no está disponible o cuando se requiere intervención manual.</p>
                                <p><strong>Pasos para registrar asistencia manual:</strong></p>
                                <ol>
                                    <li>Haz clic en <button class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Registro Manual</button></li>
                                    <li>Busca al empleado por código, nombre o escanea carnet RFID</li>
                                    <li>Selecciona el tipo de registro: <strong>Entrada</strong> o <strong>Salida</strong></li>
                                    <li>El sistema validará automáticamente si el empleado tiene horario asignado para ese día</li>
                                    <li>Si es válido, se registrará la asistencia y se tomará una foto opcional</li>
                                </ol>
                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb"></i> <strong>Nota:</strong> El sistema valida automáticamente que el empleado tenga horario personalizado asignado para el día actual antes de permitir el registro.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <i class="fas fa-search me-2"></i>Identificación Automática Biométrica
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#attendanceHelpAccordion">
                            <div class="accordion-body">
                                <p>La <strong>Identificación Automática</strong> utiliza tecnología de reconocimiento facial para identificar empleados y registrar su asistencia de forma automática.</p>
                                <p><strong>Cómo funciona:</strong></p>
                                <ol>
                                    <li>Haz clic en <button class="btn btn-success btn-sm"><i class="fas fa-search"></i> Identificación Automática</button></li>
                                    <li>El sistema activará la cámara del dispositivo</li>
                                    <li>Posiciona tu rostro frente a la cámara dentro del marco guía</li>
                                    <li>El sistema analizará tu rostro y buscará coincidencias en la base de datos</li>
                                    <li>Si se encuentra una coincidencia, se registrará automáticamente la asistencia</li>
                                    <li>Se mostrará el resultado con foto y datos del empleado identificado</li>
                                </ol>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> <strong>Ventajas:</strong> Rápido, preciso y elimina errores humanos en el registro de asistencia.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                <i class="fas fa-list me-2"></i>Consulta y Filtros de Asistencia
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#attendanceHelpAccordion">
                            <div class="accordion-body">
                                <p>La sección de <strong>consulta</strong> permite visualizar y filtrar todos los registros de asistencia del sistema.</p>
                                <p><strong>Opciones de filtro disponibles:</strong></p>
                                <ul>
                                    <li><i class="fas fa-calendar me-1"></i> <strong>Fecha:</strong> Filtrar por rango de fechas específico</li>
                                    <li><i class="fas fa-user me-1"></i> <strong>Empleado:</strong> Buscar por código, nombre o apellido</li>
                                    <li><i class="fas fa-building me-1"></i> <strong>Establecimiento:</strong> Filtrar por ubicación específica</li>
                                    <li><i class="fas fa-clock me-1"></i> <strong>Horario:</strong> Ver registros de turnos específicos</li>
                                    <li><i class="fas fa-check-circle me-1"></i> <strong>Estado:</strong> Filtrar por estado (Presente, Ausente, Tarde, etc.)</li>
                                </ul>
                                <p><strong>Información mostrada en la tabla:</strong></p>
                                <ul>
                                    <li>Código del empleado</li>
                                    <li>Nombre completo</li>
                                    <li>Establecimiento y sede</li>
                                    <li>Fecha y hora del registro</li>
                                    <li>Horario asignado</li>
                                    <li>Horas trabajadas</li>
                                    <li>Estado del registro</li>
                                    <li>Foto del empleado (si fue tomada)</li>
                                    <li>Acciones disponibles (Agregar Observaciones)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                <i class="fas fa-shield-alt me-2"></i>Validaciones y Seguridad
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#attendanceHelpAccordion">
                            <div class="accordion-body">
                                <p>El módulo incluye múltiples <strong>validaciones de seguridad</strong> para garantizar la integridad de los datos.</p>
                                <p><strong>Validaciones automáticas:</strong></p>
                                <ul>
                                    <li><i class="fas fa-calendar-check me-1"></i> <strong>Horarios Personalizados:</strong> Verifica que el empleado tenga horario asignado antes de registrar</li>
                                    <li><i class="fas fa-fingerprint me-1"></i> <strong>Biométrico:</strong> Asegura que solo empleados registrados puedan acceder al sistema</li>
                                    <li><i class="fas fa-clock me-1"></i> <strong>Tiempo Real:</strong> Registra fecha y hora exacta para evitar manipulaciones</li>
                                    <li><i class="fas fa-user-shield me-1"></i> <strong>Permisos:</strong> Controla qué usuarios pueden realizar cada tipo de operación</li>
                                    <li><i class="fas fa-history me-1"></i> <strong>Auditoría:</strong> Registra todas las acciones para seguimiento y control</li>
                                </ul>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> <strong>Nota de Seguridad:</strong> Todos los registros incluyen información de auditoría completa para mantener la trazabilidad de las operaciones.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>