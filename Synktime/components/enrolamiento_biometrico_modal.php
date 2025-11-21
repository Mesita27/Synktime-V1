<!-- Modal de Enrolamiento Biométrico Mejorado -->
<div class="modal fade" id="enrolamientoBiometricoModal" tabindex="-1" aria-labelledby="enrolamientoBiometricoModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95vw; width: 95vw;">
        <div class="modal-content" style="min-height: 90vh;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="enrolamientoBiometricoModalLabel">
                    <i class="fas fa-fingerprint"></i> Enrolamiento Biométrico Completo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Información del empleado -->
                <div class="employee-info mb-4 p-3 bg-light rounded">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Información del Empleado</h6>
                            <p class="mb-1"><strong>Código:</strong> <span id="modal-employee-code">-</span></p>
                            <p class="mb-1"><strong>Nombre:</strong> <span id="modal-employee-name">-</span></p>
                            <p class="mb-0"><strong>Establecimiento:</strong> <span id="modal-employee-establishment">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle"></i> Estado del Enrolamiento</h6>
                            <div id="enrollment-status" class="d-flex flex-wrap gap-2">
                                <span id="facial-status" class="badge bg-secondary">Facial: Pendiente</span>
                                <span id="fingerprint-status" class="badge bg-secondary">Huella: Pendiente</span>
                                <span id="rfid-status" class="badge bg-secondary">RFID: Pendiente</span>
                            </div>
                        </div>
                    </div>
                    <!-- Campos ocultos para compatibilidad -->
                    <input type="hidden" id="current-employee-id" name="employee_id" value="">
                    <input type="hidden" id="hidden_employee_id" value="">
                    <input type="hidden" id="employee_id" value="">
                </div>

                <!-- Pestañas de enrolamiento -->
                <ul class="nav nav-tabs mb-4" id="enrollmentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="facial-tab" data-bs-toggle="tab" data-bs-target="#facial-panel" type="button" role="tab">
                            <i class="fas fa-camera"></i> Reconocimiento Facial
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="fingerprint-tab" data-bs-toggle="tab" data-bs-target="#fingerprint-panel" type="button" role="tab">
                            <i class="fas fa-fingerprint"></i> Huella Dactilar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rfid-tab" data-bs-toggle="tab" data-bs-target="#rfid-panel" type="button" role="tab">
                            <i class="fas fa-id-card"></i> RFID/Carné
                        </button>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="enrollmentTabContent">
                    <!-- Panel de reconocimiento facial -->
                    <div class="tab-pane fade show active" id="facial-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-lg-8 col-md-12">
                                <div class="camera-container border rounded p-3 bg-white">
                                    <video id="faceVideo" autoplay muted class="w-100 rounded"></video>
                                    <canvas id="faceCanvas" class="d-none"></canvas>
                                </div>
                                <div class="camera-controls mt-3 d-flex justify-content-center flex-wrap gap-2">
                                    <button type="button" id="startFaceCamera" class="btn btn-success">
                                        <i class="fas fa-camera"></i> Iniciar Cámara
                                    </button>
                                    <button type="button" id="stopFaceCamera" class="btn btn-secondary" disabled>
                                        <i class="fas fa-stop"></i> Detener
                                    </button>
                                    <button type="button" id="captureFace" class="btn btn-primary" disabled>
                                        <i class="fas fa-hand-paper"></i> Capturar Rostro
                                    </button>
                                </div>
                                <!-- Indicador de detección -->
                                <div class="detection-status mt-3 text-center">
                                    <div class="alert alert-info">
                                        <p class="mb-1"><strong>Estado:</strong> <span id="face-detection-status">Esperando iniciar cámara</span></p>
                                        <p class="mb-1"><strong>Confianza:</strong> <span id="face-detection-confidence">0%</span></p>
                                        <p class="mb-0"><strong>Calidad:</strong> <span id="face-quality-score">0%</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="capture-info p-3 bg-light rounded">
                                    <h6><i class="fas fa-info-circle"></i> Instrucciones</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-check text-success"></i> Mire directamente a la cámara</li>
                                        <li><i class="fas fa-check text-success"></i> Mantenga buena iluminación</li>
                                        <li><i class="fas fa-check text-success"></i> No use gafas oscuras</li>
                                        <li><i class="fas fa-check text-success"></i> Rostro completamente visible</li>
                                        <li><i class="fas fa-check text-success"></i> Evite movimientos bruscos</li>
                                    </ul>
                                    <div class="alert alert-warning small mb-3">
                                        <i class="fas fa-exclamation-triangle"></i> El sistema capturará automáticamente cuando la calidad alcance el 90%.
                                    </div>
                                </div>
                                <div class="capture-progress mt-3">
                                    <h6>Progreso del Enrolamiento Facial</h6>
                                    <div id="facial-progress-container" class="d-flex justify-content-between mb-3">
                                        <!-- Las capturas se generan dinámicamente por JavaScript -->
                                    </div>
                                    <div class="alert alert-info small">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Requisitos:</strong> Confianza ≥90% y Calidad ≥90% para captura automática
                                    </div>
                                    <small class="text-muted">
                                        <strong>Servicio:</strong> Python FastAPI con InsightFace |
                                        <strong>Estado:</strong> <span id="python-service-status">Verificando...</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de huella dactilar -->
                    <div class="tab-pane fade" id="fingerprint-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-md-8">
                                <div class="fingerprint-scanner border rounded p-4 bg-white text-center">
                                    <div class="scanner-area mb-3">
                                        <i class="fas fa-fingerprint scanner-icon fa-4x text-primary" id="fingerprintIcon"></i>
                                        <p class="scanner-text mt-3 h5">Coloque el dedo en el escáner</p>
                                        <div class="scanner-animation mt-3" id="scannerAnimation">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="scanner-controls d-flex justify-content-center flex-wrap gap-2">
                                        <button type="button" id="startFingerprint" class="btn btn-success">
                                            <i class="fas fa-fingerprint"></i> Iniciar Escáner
                                        </button>
                                        <button type="button" id="captureFingerprint" class="btn btn-primary" disabled>
                                            <i class="fas fa-hand-paper"></i> Capturar Huella
                                        </button>
                                        <button type="button" id="stopFingerprint" class="btn btn-secondary" disabled>
                                            <i class="fas fa-stop"></i> Detener
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="finger-selection p-3 bg-light rounded">
                                    <h6><i class="fas fa-hand-paper"></i> Seleccionar Dedo</h6>
                                    <div class="finger-options">
                                        <div class="row">
                                            <div class="col-6">
                                                <h6 class="text-primary">Mano Derecha</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="fingerType" id="thumbRight" value="thumb_right" checked>
                                                    <label class="form-check-label" for="thumbRight">Pulgar</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="fingerType" id="indexRight" value="index_right">
                                                    <label class="form-check-label" for="indexRight">Índice</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="fingerType" id="middleRight" value="middle_right">
                                                    <label class="form-check-label" for="middleRight">Medio</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h6 class="text-primary">Mano Izquierda</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="fingerType" id="thumbLeft" value="thumb_left">
                                                    <label class="form-check-label" for="thumbLeft">Pulgar</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="fingerType" id="indexLeft" value="index_left">
                                                    <label class="form-check-label" for="indexLeft">Índice</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="fingerType" id="middleLeft" value="middle_left">
                                                    <label class="form-check-label" for="middleLeft">Medio</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="fingerprint-progress mt-3">
                                    <h6>Progreso del Enrolamiento</h6>
                                    <div class="progress mb-2">
                                        <div id="fingerprintProgress" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Muestras: <span id="fingerprintSamples">0</span>/3 | Calidad: <span id="fingerprintQuality">0%</span></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de RFID/Carné -->
                    <div class="tab-pane fade" id="rfid-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-md-8">
                                <div class="rfid-scanner border rounded p-4 bg-white text-center">
                                    <div class="scanner-area mb-3">
                                        <i class="fas fa-id-card scanner-icon fa-4x text-info" id="rfidIcon"></i>
                                        <p class="scanner-text mt-3 h5">Acerque el carné o tarjeta RFID</p>
                                        <div class="scanner-animation mt-3" id="rfidAnimation">
                                            <div class="spinner-border text-info" role="status">
                                                <span class="visually-hidden">Escaneando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rfid-info mt-3 p-3 bg-light rounded">
                                        <p class="mb-1"><strong>UID Detectado:</strong> <span id="rfidUid">-</span></p>
                                        <p class="mb-1"><strong>Tipo:</strong> <span id="rfidType">-</span></p>
                                        <p class="mb-0"><strong>Estado:</strong> <span id="rfidStatus">Esperando...</span></p>
                                    </div>
                                    <div class="scanner-controls d-flex justify-content-center flex-wrap gap-2 mt-3">
                                        <button type="button" id="startRfid" class="btn btn-info">
                                            <i class="fas fa-id-card"></i> Iniciar Escáner RFID
                                        </button>
                                        <button type="button" id="captureRfid" class="btn btn-primary" disabled>
                                            <i class="fas fa-save"></i> Registrar UID
                                        </button>
                                        <button type="button" id="stopRfid" class="btn btn-secondary" disabled>
                                            <i class="fas fa-stop"></i> Detener
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="rfid-instructions p-3 bg-light rounded">
                                    <h6><i class="fas fa-info-circle"></i> Instrucciones RFID</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-check text-success"></i> Asegúrese de que el dispositivo RFID esté conectado</li>
                                        <li><i class="fas fa-check text-success"></i> Acerque el carné lentamente al lector</li>
                                        <li><i class="fas fa-check text-success"></i> Mantenga el carné quieto hasta que se detecte</li>
                                        <li><i class="fas fa-check text-success"></i> Verifique que el UID se muestre correctamente</li>
                                    </ul>
                                    <div class="alert alert-info small">
                                        <i class="fas fa-info-circle"></i> El sistema detectará automáticamente la tarjeta RFID cuando esté cerca del lector.
                                    </div>
                                </div>
                                <div class="rfid-progress mt-3">
                                    <h6>Estado del Enrolamiento RFID</h6>
                                    <div class="progress mb-2">
                                        <div id="rfidProgress" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Estado: <span id="rfidEnrollmentStatus">No iniciado</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <span class="text-muted small">Empleado ID: <span id="display-employee-id">-</span></span>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" id="saveEnrollment" class="btn btn-success" disabled>
                        <i class="fas fa-save"></i> Guardar Enrolamiento
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de enrolamiento -->
<div class="modal fade" id="enrollmentConfirmationModal" tabindex="-1" aria-labelledby="enrollmentConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="enrollmentConfirmationModalLabel">
                    <i class="fas fa-check-circle"></i> Enrolamiento Completado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h5>¡Enrolamiento exitoso!</h5>
                <p class="mb-1">Los datos biométricos han sido registrados correctamente.</p>
                <div id="enrollmentSummary" class="mt-3 p-3 bg-light rounded">
                    <!-- El resumen se llenará dinámicamente -->
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
.scanner-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.camera-container video {
    max-height: 300px;
    object-fit: cover;
}

.fingerprint-options .form-check-label {
    font-size: 0.9rem;
}

.rfid-info {
    font-family: monospace;
    font-size: 0.9rem;
}

.detection-status .alert {
    font-size: 0.9rem;
}
</style>
