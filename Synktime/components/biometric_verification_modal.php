<!-- Modal de Verificación Biométrica Completa Mejorado -->
<div class="modal fade" id="biometricVerificationModal" tabindex="-1" aria-labelledby="biometricVerificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95vw; width: 95vw;">
        <div class="modal-content" style="min-height: 90vh;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="biometricVerificationModalLabel">
                    <i class="fas fa-shield-alt"></i> Verificación Biométrica Completa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Información del empleado -->
                <div class="employee-info mb-4 p-3 bg-light rounded">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Información del Empleado</h6>
                            <p class="mb-1"><strong>Código:</strong> <span id="verification-employee-code">-</span></p>
                            <p class="mb-1"><strong>Nombre:</strong> <span id="verification-employee-name">-</span></p>
                            <p class="mb-0"><strong>Establecimiento:</strong> <span id="verification-employee-establishment">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-shield-alt"></i> Estado de Verificación</h6>
                            <div id="verification-status" class="d-flex flex-wrap gap-2">
                                <span id="facial-verification-status" class="badge bg-secondary">Facial: Pendiente</span>
                                <span id="fingerprint-verification-status" class="badge bg-secondary">Huella: Pendiente</span>
                                <span id="rfid-verification-status" class="badge bg-secondary">RFID: Pendiente</span>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <strong>Última verificación:</strong> <span id="last-verification-time">-</span>
                                </small>
                            </div>
                        </div>
                    </div>
                    <!-- Campos ocultos para compatibilidad -->
                    <input type="hidden" id="verification-employee-id" name="employee_id" value="">
                    <input type="hidden" id="verification-attendance-type" value="ENTRADA">
                </div>

                <!-- Pestañas de verificación -->
                <ul class="nav nav-tabs mb-4" id="verificationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="facial-verification-tab" data-bs-toggle="tab" data-bs-target="#facial-verification-panel" type="button" role="tab">
                            <i class="fas fa-camera"></i> Reconocimiento Facial
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="fingerprint-verification-tab" data-bs-toggle="tab" data-bs-target="#fingerprint-verification-panel" type="button" role="tab">
                            <i class="fas fa-fingerprint"></i> Huella Dactilar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rfid-verification-tab" data-bs-toggle="tab" data-bs-target="#rfid-verification-panel" type="button" role="tab">
                            <i class="fas fa-id-card"></i> RFID/Carné
                        </button>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="verificationTabContent">
                    <!-- Panel de verificación facial -->
                    <div class="tab-pane fade show active" id="facial-verification-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-lg-8 col-md-12">
                                <div class="camera-container border rounded p-3 bg-white">
                                    <video id="facialVerificationVideo" autoplay muted class="w-100 rounded"></video>
                                    <canvas id="facialVerificationCanvas" class="d-none"></canvas>
                                </div>
                                <div class="camera-controls mt-3 d-flex justify-content-center flex-wrap gap-2">
                                    <button type="button" id="startFacialVerification" class="btn btn-success">
                                        <i class="fas fa-camera"></i> Iniciar Verificación
                                    </button>
                                    <button type="button" id="stopFacialVerification" class="btn btn-secondary" disabled>
                                        <i class="fas fa-stop"></i> Detener
                                    </button>
                                    <button type="button" id="verifyFacialNow" class="btn btn-primary" disabled>
                                        <i class="fas fa-search"></i> Verificar Ahora
                                    </button>
                                </div>
                                <!-- Indicador de detección -->
                                <div class="detection-status mt-3 text-center">
                                    <div class="alert alert-info">
                                        <p class="mb-1"><strong>Estado:</strong> <span id="facial-verification-status-text">Esperando iniciar verificación</span></p>
                                        <p class="mb-1"><strong>Confianza:</strong> <span id="facial-verification-confidence">0%</span></p>
                                        <p class="mb-1"><strong>Calidad:</strong> <span id="facial-verification-quality">0%</span></p>
                                        <p class="mb-0"><strong>Empleado detectado:</strong> <span id="facial-verification-employee">No identificado</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="verification-info p-3 bg-light rounded">
                                    <h6><i class="fas fa-info-circle"></i> Instrucciones de Verificación</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-check text-success"></i> Mire directamente a la cámara</li>
                                        <li><i class="fas fa-check text-success"></i> Mantenga buena iluminación</li>
                                        <li><i class="fas fa-check text-success"></i> No use gafas oscuras ni sombreros</li>
                                        <li><i class="fas fa-check text-success"></i> Rostro completamente visible</li>
                                        <li><i class="fas fa-check text-success"></i> Evite movimientos bruscos</li>
                                    </ul>
                                    <div class="alert alert-success small mb-3">
                                        <i class="fas fa-lightbulb"></i> <strong>Recomendación:</strong> La verificación se realiza automáticamente cuando la confianza sea mayor al 85%.
                                    </div>
                                    <div class="verification-result mt-3" id="facial-verification-result" style="display: none;">
                                        <div class="alert" id="facial-result-alert">
                                            <h6 id="facial-result-title"></h6>
                                            <p id="facial-result-message"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="verification-progress mt-3">
                                    <h6>Estado de Verificación Facial</h6>
                                    <div class="progress mb-2">
                                        <div id="facialVerificationProgress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <strong>Servicio:</strong> Python FastAPI con InsightFace |
                                        <strong>Estado:</strong> <span id="facial-service-status">Verificando...</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de verificación de huella dactilar -->
                    <div class="tab-pane fade" id="fingerprint-verification-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-md-8">
                                <div class="fingerprint-scanner border rounded p-4 bg-white text-center">
                                    <div class="scanner-area mb-3">
                                        <i class="fas fa-fingerprint scanner-icon fa-4x text-primary" id="fingerprintVerificationIcon"></i>
                                        <p class="scanner-text mt-3 h5">Coloque el dedo en el escáner</p>
                                        <div class="scanner-animation mt-3" id="fingerprintVerificationAnimation" style="display: none;">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Verificando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="scanner-controls d-flex justify-content-center flex-wrap gap-2">
                                        <button type="button" id="startFingerprintVerification" class="btn btn-success">
                                            <i class="fas fa-fingerprint"></i> Iniciar Verificación
                                        </button>
                                        <button type="button" id="verifyFingerprintNow" class="btn btn-primary" disabled>
                                            <i class="fas fa-search"></i> Verificar Huella
                                        </button>
                                        <button type="button" id="stopFingerprintVerification" class="btn btn-secondary" disabled>
                                            <i class="fas fa-stop"></i> Detener
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="fingerprint-verification-info p-3 bg-light rounded">
                                    <h6><i class="fas fa-info-circle"></i> Instrucciones de Huella</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-check text-success"></i> Asegúrese de que el escáner esté conectado</li>
                                        <li><i class="fas fa-check text-success"></i> Limpie su dedo antes de colocarlo</li>
                                        <li><i class="fas fa-check text-success"></i> Coloque el dedo correctamente en el escáner</li>
                                        <li><i class="fas fa-check text-success"></i> Mantenga el dedo quieto durante la lectura</li>
                                        <li><i class="fas fa-check text-success"></i> Use el mismo dedo registrado</li>
                                    </ul>
                                    <div class="alert alert-info small mb-3">
                                        <i class="fas fa-info-circle"></i> <strong>Recomendación:</strong> Si tiene problemas, intente con otro dedo o contacte al administrador.
                                    </div>
                                    <div class="fingerprint-result mt-3" id="fingerprint-verification-result" style="display: none;">
                                        <div class="alert" id="fingerprint-result-alert">
                                            <h6 id="fingerprint-result-title"></h6>
                                            <p id="fingerprint-result-message"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="fingerprint-verification-progress mt-3">
                                    <h6>Estado de Verificación</h6>
                                    <div class="progress mb-2">
                                        <div id="fingerprintVerificationProgress" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">
                                        Confianza: <span id="fingerprint-verification-confidence">0%</span> |
                                        Estado: <span id="fingerprint-verification-status-text">No iniciado</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de verificación RFID -->
                    <div class="tab-pane fade" id="rfid-verification-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-md-8">
                                <div class="rfid-scanner border rounded p-4 bg-white text-center">
                                    <div class="scanner-area mb-3">
                                        <i class="fas fa-id-card scanner-icon fa-4x text-info" id="rfidVerificationIcon"></i>
                                        <p class="scanner-text mt-3 h5">Acerque el carné o tarjeta RFID</p>
                                        <div class="scanner-animation mt-3" id="rfidVerificationAnimation" style="display: none;">
                                            <div class="spinner-border text-info" role="status">
                                                <span class="visually-hidden">Verificando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rfid-info mt-3 p-3 bg-light rounded">
                                        <p class="mb-1"><strong>UID Detectado:</strong> <span id="rfid-verification-uid">-</span></p>
                                        <p class="mb-1"><strong>Tipo:</strong> <span id="rfid-verification-type">-</span></p>
                                        <p class="mb-0"><strong>Estado:</strong> <span id="rfid-verification-status-text">Esperando...</span></p>
                                    </div>
                                    <div class="scanner-controls d-flex justify-content-center flex-wrap gap-2 mt-3">
                                        <button type="button" id="startRfidVerification" class="btn btn-info">
                                            <i class="fas fa-id-card"></i> Iniciar Verificación RFID
                                        </button>
                                        <button type="button" id="verifyRfidNow" class="btn btn-primary" disabled>
                                            <i class="fas fa-search"></i> Verificar RFID
                                        </button>
                                        <button type="button" id="stopRfidVerification" class="btn btn-secondary" disabled>
                                            <i class="fas fa-stop"></i> Detener
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="rfid-verification-instructions p-3 bg-light rounded">
                                    <h6><i class="fas fa-info-circle"></i> Instrucciones RFID</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-check text-success"></i> Asegúrese de que el dispositivo RFID esté conectado</li>
                                        <li><i class="fas fa-check text-success"></i> Acerque el carné lentamente al lector</li>
                                        <li><i class="fas fa-check text-success"></i> Mantenga el carné quieto hasta que se detecte</li>
                                        <li><i class="fas fa-check text-success"></i> Verifique que el UID se muestre correctamente</li>
                                        <li><i class="fas fa-check text-success"></i> Use el mismo carné registrado</li>
                                    </ul>
                                    <div class="alert alert-info small mb-3">
                                        <i class="fas fa-info-circle"></i> <strong>Recomendación:</strong> Si el carné no se detecta, intente acercarlo desde diferentes ángulos.
                                    </div>
                                    <div class="rfid-result mt-3" id="rfid-verification-result" style="display: none;">
                                        <div class="alert" id="rfid-result-alert">
                                            <h6 id="rfid-result-title"></h6>
                                            <p id="rfid-result-message"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="rfid-verification-progress mt-3">
                                    <h6>Estado de Verificación RFID</h6>
                                    <div class="progress mb-2">
                                        <div id="rfidVerificationProgress" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">
                                        Estado: <span id="rfid-verification-progress-text">No iniciado</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <span class="text-muted small">
                        Empleado ID: <span id="verification-display-employee-id">-</span> |
                        Tipo: <span id="verification-type-display">ENTRADA</span>
                    </span>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" id="completeVerification" class="btn btn-success" disabled>
                        <i class="fas fa-check"></i> Completar Verificación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de verificación -->
<div class="modal fade" id="verificationConfirmationModal" tabindex="-1" aria-labelledby="verificationConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="verificationConfirmationModalLabel">
                    <i class="fas fa-check-circle"></i> Verificación Completada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h5>¡Verificación exitosa!</h5>
                <p class="mb-1">La asistencia ha sido registrada correctamente.</p>
                <div id="verificationSummary" class="mt-3 p-3 bg-light rounded">
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

<!-- Modal de errores mejorado -->
<div class="modal fade" id="verificationErrorModal" tabindex="-1" aria-labelledby="verificationErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="verificationErrorModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Error en Verificación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                </div>
                <h6 id="error-title" class="text-center mb-3"></h6>
                <p id="error-message" class="text-center mb-3"></p>
                <div id="error-recommendations" class="alert alert-info">
                    <h6><i class="fas fa-lightbulb"></i> Recomendaciones:</h6>
                    <ul id="error-recommendation-list" class="mb-0">
                        <!-- Las recomendaciones se llenarán dinámicamente -->
                    </ul>
                </div>
            </div>
            <div class="modal-footer justify-content-end">
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-primary" id="retry-verification-btn">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                </div>
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

.verification-result .alert {
    font-size: 0.9rem;
}

.verification-result .alert-success {
    border-left: 4px solid #28a745;
}

.verification-result .alert-danger {
    border-left: 4px solid #dc3545;
}

.verification-result .alert-warning {
    border-left: 4px solid #ffc107;
}

.verification-result .alert-info {
    border-left: 4px solid #17a2b8;
}

/* Animaciones de éxito y error */
.verification-success {
    animation: successPulse 0.5s ease-in-out;
}

.verification-error {
    animation: errorShake 0.5s ease-in-out;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes errorShake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
    100% { transform: translateX(0); }
}

/* Estados de carga */
.loading-state {
    opacity: 0.7;
    pointer-events: none;
}

.loading-state::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #007bff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive design */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .camera-container video {
        max-height: 200px;
    }

    .scanner-icon {
        font-size: 2rem;
    }

    .modal-footer .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
}
</style>
