<!-- MODAL DE SELECCIÓN DE EMPLEADOS PARA ASISTENCIA OPTIMIZADO -->
<div class="modal fade" id="employeeSelectionModal" tabindex="-1" role="dialog" aria-labelledby="employeeSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="employeeSelectionModalLabel">
                    <i class="fas fa-users"></i> Seleccionar Empleado para Registro de Asistencia
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <!-- FILTROS DE BÚSQUEDA -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-filter"></i> Filtros de Búsqueda</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="searchEmployee" class="form-label">Buscar Empleado:</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="searchEmployee" 
                                               placeholder="Nombre, apellido o DNI..."
                                               autocomplete="off">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="filterEstablishment" class="form-label">Establecimiento:</label>
                                        <select class="form-control" id="filterEstablishment">
                                            <option value="">Todos los establecimientos</option>
                                            <!-- Se llena dinámicamente -->
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="filterStatus" class="form-label">Estado:</label>
                                        <select class="form-control" id="filterStatus">
                                            <option value="">Todos los estados</option>
                                            <option value="A" selected>Activos</option>
                                            <option value="I">Inactivos</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filterBiometric" class="form-label">Biométrico:</label>
                                        <select class="form-control" id="filterBiometric">
                                            <option value="">Seleccionar una Sede</option>
                                            <option value="enrolled">Con registro</option>
                                            <option value="pending">Sin registro</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="searchEmployees()">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearEmployeeFilters()">
                                            <i class="fas fa-times"></i> Limpiar Filtros
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TABLA DE EMPLEADOS -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-list"></i> Lista de Empleados</h6>
                                <small class="text-muted">
                                    <span id="employeeCount">0</span> empleados encontrados
                                </small>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table class="table table-striped table-hover mb-0" id="employeesTable">
                                        <thead class="thead-dark sticky-top">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="25%">Empleado</th>
                                                <th width="15%">DNI</th>
                                                <th width="20%">Establecimiento</th>
                                                <th width="10%">Estado</th>
                                                <th width="15%">Biométrico</th>
                                                <th width="10%">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="employeesTableBody">
                                            <!-- Se llena dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DEL EMPLEADO SELECCIONADO -->
                <div class="row mt-3" id="selectedEmployeeInfo" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-user-check"></i> Empleado Seleccionado:</h6>
                            <p class="mb-0" id="selectedEmployeeDetails"></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" 
                        class="btn btn-primary" 
                        id="proceedToAttendanceBtn" 
                        onclick="proceedToAttendanceRegistration()" 
                        disabled>
                    <i class="fas fa-arrow-right"></i> Proceder al Registro
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE REGISTRO DE ASISTENCIA BIOMÉTRICA -->
<div class="modal fade" id="biometricAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="biometricAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="biometricAttendanceModalLabel">
                    <i class="fas fa-fingerprint"></i> Registro de Asistencia Biométrica
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <!-- INFORMACIÓN DEL EMPLEADO -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-user"></i> Información del Empleado</h6>
                            </div>
                            <div class="card-body">
                                <div id="attendanceEmployeeInfo">
                                    <!-- Se llena dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- OPCIONES DE VERIFICACIÓN -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-cogs"></i> Método de Verificación</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="verification-option" data-method="facial">
                                            <div class="card border-warning h-100 verification-card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-camera fa-2x text-warning mb-2"></i>
                                                    <h6>Reconocimiento Facial</h6>
                                                    <p class="small text-muted">Verificación mediante TensorFlow</p>
                                                    <div class="status-indicator" id="facialStatus">
                                                        <span class="badge badge-secondary">Verificar disponibilidad</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="verification-option" data-method="photo">
                                            <div class="card border-success h-100 verification-card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-camera-retro fa-2x text-success mb-2"></i>
                                                    <h6>Foto Tradicional</h6>
                                                    <p class="small text-muted">Captura de foto manual</p>
                                                    <div class="status-indicator">
                                                        <span class="badge badge-success">Siempre disponible</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ÁREA DE VERIFICACIÓN -->
                <div class="row" id="verificationArea" style="display: none;">
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-circle"></i> 
                                    <span id="verificationTitle">Verificación en Progreso</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- ÁREA DE CÁMARA -->
                                <div id="cameraArea" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="video-container">
                                                <video id="attendanceVideo" 
                                                       width="100%" 
                                                       height="300" 
                                                       autoplay 
                                                       muted 
                                                       playsinline
                                                       style="border: 2px solid #dee2e6; border-radius: 8px;">
                                                </video>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="verification-status">
                                                <h6>Estado de Verificación:</h6>
                                                <div id="verificationStatusText" class="mb-3">
                                                    <span class="badge badge-warning">Iniciando...</span>
                                                </div>
                                                
                                                <div class="progress mb-3">
                                                    <div class="progress-bar" 
                                                         id="verificationProgress" 
                                                         role="progressbar" 
                                                         style="width: 0%" 
                                                         aria-valuenow="0" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">0%</div>
                                                </div>
                                                
                                                <div id="verificationResults" class="small text-muted">
                                                    <!-- Resultados en tiempo real -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- ÁREA DE FOTO TRADICIONAL -->
                                <div id="photoArea" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="video-container">
                                                <video id="photoVideo" 
                                                       width="100%" 
                                                       height="300" 
                                                       autoplay 
                                                       muted 
                                                       playsinline
                                                       style="border: 2px solid #dee2e6; border-radius: 8px;">
                                                </video>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="photo-controls text-center">
                                                <h6>Captura de Foto</h6>
                                                <p class="small text-muted">Posiciónese frente a la cámara</p>
                                                
                                                <button type="button" 
                                                        class="btn btn-success btn-lg" 
                                                        id="capturePhotoBtn" 
                                                        onclick="capturePhoto()">
                                                    <i class="fas fa-camera"></i> Capturar
                                                </button>
                                                
                                                <div id="capturedPhotoPreview" class="mt-3" style="display: none;">
                                                    <img id="previewImage" 
                                                         src="" 
                                                         alt="Foto capturada" 
                                                         class="img-fluid" 
                                                         style="max-height: 150px; border: 1px solid #dee2e6; border-radius: 4px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- RESULTADO DE VERIFICACIÓN -->
                <div class="row mt-3" id="verificationResult" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert" id="verificationAlert">
                            <!-- Resultado de la verificación -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" 
                        class="btn btn-success" 
                        id="completeAttendanceBtn" 
                        onclick="completeAttendanceRegistration()" 
                        disabled>
                    <i class="fas fa-check"></i> Completar Registro
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.verification-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.verification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.verification-option.selected .verification-card {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.video-container {
    position: relative;
}

.video-container::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border: 2px dashed #007bff;
    border-radius: 8px;
    pointer-events: none;
    opacity: 0.3;
}

.status-indicator .badge {
    font-size: 0.75rem;
}

#employeesTable tbody tr {
    cursor: pointer;
}

#employeesTable tbody tr:hover {
    background-color: #f8f9fa;
}

#employeesTable tbody tr.selected {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}
</style>
