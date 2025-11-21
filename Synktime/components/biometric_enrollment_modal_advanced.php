<!-- Modal de Enrolamiento Biométrico Avanzado -->
<div class="modal fade" id="biometricEnrollmentModal" tabindex="-1" aria-labelledby="biometricEnrollmentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="biometricEnrollmentModalLabel">
                    <i class="fas fa-fingerprint"></i> Enrolamiento Biométrico
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Información del empleado -->
                <div class="employee-info mb-4">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Información del Empleado</h6>
                            <p class="mb-1"><strong>Código:</strong> <span id="modal-employee-code">-</span></p>
                            <p class="mb-1"><strong>Nombre:</strong> <span id="modal-employee-name">-</span></p>
                            <p class="mb-0"><strong>Establecimiento:</strong> <span id="modal-employee-establishment">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle"></i> Estado del Enrolamiento</h6>
                            <div id="enrollment-status">
                                <p class="mb-1"><strong>Facial:</strong> <span id="facial-status" class="badge bg-secondary">Pendiente</span></p>
                                <p class="mb-0"><strong>Huella:</strong> <span id="fingerprint-status" class="badge bg-secondary">Pendiente</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Campos ocultos para el ID del empleado (compatibilidad con múltiples formatos) -->
                    <input type="hidden" id="current-employee-id" name="employee_id" value="">
                    <input type="hidden" id="hidden_employee_id" value="">
                    <input type="hidden" id="employee_id" value="">
                </div>

                <!-- Mensaje de estado general -->
                <div id="enrollment-message" class="alert alert-info mb-4" role="alert">
                    <i class="fas fa-info-circle"></i> Seleccione un método de enrolamiento para comenzar.
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
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="enrollmentTabContent">
                    <!-- Panel de reconocimiento facial -->
                    <div class="tab-pane fade show active" id="facial-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-lg-8 col-md-12">
                                <div class="camera-container">
                                    <video id="faceVideo" autoplay muted></video>
                                    <canvas id="faceCanvas"></canvas>
                                    <div class="detection-indicator" id="detectionIndicator"></div>
                                </div>
                                <div class="camera-controls mt-3 d-flex justify-content-center flex-wrap gap-2">
                                    <button type="button" id="startFaceCamera" class="btn btn-primary">
                                        <i class="fas fa-camera"></i> Iniciar Cámara
                                    </button>
                                    <button type="button" id="stopFaceCamera" class="btn btn-secondary" disabled>
                                        <i class="fas fa-stop"></i> Detener
                                    </button>
                                </div>
                                <!-- Indicador de detección -->
                                <div class="detection-status mt-3 text-center">
                                    <p class="mb-1">Estado: <span id="face-detection-status">Esperando iniciar cámara</span></p>
                                    <p class="mb-1">Confianza: <span id="face-detection-confidence">0%</span></p>
                                    <p class="mb-1">Calidad: <span id="face-quality-score">0%</span></p>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="capture-info">
                                    <h6><i class="fas fa-info-circle"></i> Instrucciones</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Mire directamente a la cámara</li>
                                        <li><i class="fas fa-check text-success"></i> Mantenga buena iluminación</li>
                                        <li><i class="fas fa-check text-success"></i> No use gafas oscuras</li>
                                        <li><i class="fas fa-check text-success"></i> Rostro completamente visible</li>
                                    </ul>
                                    <div class="alert alert-info mt-3 small">
                                        <i class="fas fa-info-circle"></i> El sistema capturará automáticamente su rostro cuando la detección alcance el 90% de confianza.
                                    </div>
                                </div>
                                <div class="capture-progress mt-3">
                                    <h6>Progreso del Enrolamiento</h6>
                                    <div class="progress mb-2">
                                        <div id="faceProgress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Capturas: <span id="faceCaptures">0</span>/5</small>
                                </div>
                                <!-- Información de debug -->
                                <div id="debug-info" class="debug-container mt-3 p-2 bg-light rounded small">
                                    <!-- La información de debug se mostrará aquí -->
                                </div>
                                <div id="face-samples" class="samples-container mt-3">
                                    <!-- Las miniaturas de las capturas se mostrarán aquí -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de huella dactilar -->
                    <div class="tab-pane fade" id="fingerprint-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-md-8">
                                <div class="fingerprint-scanner">
                                    <div class="scanner-area">
                                        <i class="fas fa-fingerprint scanner-icon" id="fingerprintIcon"></i>
                                        <p class="scanner-text">Coloque el dedo en el escáner</p>
                                        <div class="scanner-animation" id="scannerAnimation"></div>
                                    </div>
                                    <div class="scanner-controls mt-3">
                                        <button type="button" id="startFingerprint" class="btn btn-primary">
                                            <i class="fas fa-fingerprint"></i> Iniciar Escáner
                                        </button>
                                        <button type="button" id="captureFingerprint" class="btn btn-success" disabled>
                                            <i class="fas fa-hand-paper"></i> Capturar Huella
                                        </button>
                                        <button type="button" id="stopFingerprint" class="btn btn-secondary" disabled>
                                            <i class="fas fa-stop"></i> Detener
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="finger-selection">
                                    <h6><i class="fas fa-hand-paper"></i> Seleccionar Dedo</h6>
                                    <div class="finger-options">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="fingerType" id="thumbRight" value="thumb_right" checked>
                                            <label class="form-check-label" for="thumbRight">Pulgar Derecho</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="fingerType" id="indexRight" value="index_right">
                                            <label class="form-check-label" for="indexRight">Índice Derecho</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="fingerType" id="thumbLeft" value="thumb_left">
                                            <label class="form-check-label" for="thumbLeft">Pulgar Izquierdo</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="fingerType" id="indexLeft" value="index_left">
                                            <label class="form-check-label" for="indexLeft">Índice Izquierdo</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="fingerprint-progress">
                                    <h6>Progreso del Enrolamiento</h6>
                                    <div class="progress mb-2">
                                        <div id="fingerprintProgress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Muestras: <span id="fingerprintSamples">0</span>/3</small>
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
