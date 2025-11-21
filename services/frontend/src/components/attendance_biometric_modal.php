<!-- Modal de Verificación Biométrica Completa Mejorado -->
<div class="modal fade" id="biometricVerificationModal" tabindex="-1" aria-labelledby="biometricVerificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95vw; width: 95vw;">
        <div class="modal-content" style="min-height: 90vh;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="biometricVerificationModalLabel">
                    <i class="fas fa-shield-alt"></i> Verificación Biométrica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Información del empleado -->
                <div class="employee-info mb-4 p-3 bg-light rounded">
                    <div class="row justify-content-center">
                        <div class="col-md-12">
                            <h6><i class="fas fa-user"></i> Información del Empleado</h6>
                            <p class="mb-1"><strong>Código:</strong> <span id="verification-employee-code">-</span></p>
                            <p class="mb-1"><strong>Nombre:</strong> <span id="verification-employee-name">-</span></p>
                            <p class="mb-0"><strong>Establecimiento:</strong> <span id="verification-employee-establishment">-</span></p>
                        </div>
                    </div>
                    <!-- Campos ocultos para compatibilidad -->
                    <input type="hidden" id="verification-employee-id" name="employee_id" value="">
                    <input type="hidden" id="verification-attendance-type" value="ENTRADA">
                </div>

                <!-- Pestañas de verificación -->
                <ul class="nav nav-tabs mb-4" id="verificationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="face-verification-tab" data-bs-toggle="tab" data-bs-target="#face-verification-panel" type="button" role="tab">
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

                <!-- Mensaje de selección de método -->
                <div class="alert alert-info mb-3" id="biometric-method-selection-message">
                    <i class="fas fa-info-circle"></i> <strong>Seleccione su método de verificación biométrico</strong>
                    <br>
                    <small>Elija uno de los métodos disponibles para completar la verificación de asistencia.</small>
                </div>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="verificationTabContent">
                    <!-- Panel de verificación facial -->
                    <div class="tab-pane fade show active" id="face-verification-panel" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            <div class="col-lg-8 col-md-12">
                                <div class="camera-container border rounded p-3 bg-white">
                                    <video id="faceVerificationVideo" autoplay muted class="w-100 rounded"></video>
                                    <canvas id="faceVerificationCanvas" class="d-none"></canvas>
                                </div>
                                <div class="camera-controls mt-3 d-flex justify-content-center flex-wrap gap-2">
                                    <button type="button" id="startAutoIdentification" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Identificación Automática
                                    </button>
                                    <button type="button" id="startFaceVerification" class="btn btn-success">
                                        <i class="fas fa-camera"></i> Verificación Manual
                                    </button>
                                    <button type="button" id="stopFaceVerification" class="btn btn-secondary" disabled>
                                        <i class="fas fa-stop"></i> Detener
                                    </button>
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
                                        <i class="fas fa-lightbulb"></i> <strong>Modo Automático:</strong> La identificación automática reconoce empleados sin selección previa. Use "Verificación Manual" si ya seleccionó un empleado.
                                    </div>
                                    <div class="verification-result mt-3" id="face-verification-result" style="display: none;">
                                        <div class="alert" id="face-result-alert">
                                            <h6 id="face-result-title"></h6>
                                            <p id="face-result-message"></p>
                                        </div>
                                    </div>
                                    <!-- Área para mostrar foto capturada -->
                                    <div class="captured-photo-container mt-3" id="captured-photo-container" style="display: none;">
                                        <h6><i class="fas fa-camera"></i> Foto de Evidencia</h6>
                                        <div class="photo-preview border rounded p-2 bg-white text-center">
                                            <img id="captured-photo-preview" src="" alt="Foto capturada" class="img-fluid rounded" style="max-height: 200px;">
                                            <p class="text-muted small mt-2 mb-0">Foto capturada para evidencia de asistencia</p>
                                        </div>
                                    </div>
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

<!-- Incluir timezone de Bogotá para fechas y horas -->
<script src="js/timezone-bogota.js"></script>

<script>
/**
 * Modal de Verificación Biométrica Completa - JavaScript
 * Sistema SNKTIME - Verificación mejorada con manejo de errores
 */

const synktimePythonService = window.SynktimePythonService || null;
const PYTHON_SERVICE_URL = synktimePythonService
    ? synktimePythonService.getBaseUrl()
    : (window.SYNKTIME?.pythonService?.baseUrl || 'http://127.0.0.1:8000');
const PYTHON_SERVICE_TIMEOUT = synktimePythonService
    ? synktimePythonService.getTimeout()
    : (window.SYNKTIME?.pythonService?.timeout || 30);
const PYTHON_SERVICE_HEALTH_URL = synktimePythonService
    ? synktimePythonService.getHealthUrl()
    : `${PYTHON_SERVICE_URL.replace(/\/+$/, '')}/${(window.SYNKTIME?.pythonService?.healthPath || 'healthz')}`;
const PYTHON_SERVICE_HEALTH_PATH = synktimePythonService
    ? synktimePythonService.getHealthPath()
    : (window.SYNKTIME?.pythonService?.healthPath || 'healthz');
const PYTHON_SERVICE_PROXY_URL = synktimePythonService && typeof synktimePythonService.getProxyUrl === 'function'
    ? synktimePythonService.getProxyUrl()
    : (window.SYNKTIME?.pythonService?.proxyUrl || '/api/biometric/python-proxy.php');

function pythonServiceEndpoint(path = '') {
    if (synktimePythonService) {
        return synktimePythonService.buildUrl(path);
    }

    const normalizedBase = PYTHON_SERVICE_URL.replace(/\/+$/, '');
    const normalizedPath = String(path || '').replace(/^\/+/, '');
    return normalizedPath ? `${normalizedBase}/${normalizedPath}` : normalizedBase;
}

function resolvePythonServiceHealthTarget() {
    if (synktimePythonService && typeof synktimePythonService.fetch === 'function') {
        return PYTHON_SERVICE_HEALTH_PATH;
    }

    return PYTHON_SERVICE_HEALTH_URL;
}

function pythonServiceFetch(path, options = {}) {
    if (synktimePythonService && typeof synktimePythonService.fetch === 'function') {
        const mergedOptions = Object.assign({}, options, { forceProxy: true });
        return synktimePythonService.fetch(path, mergedOptions);
    }

    const targetUrl = path.startsWith('http') ? path : pythonServiceEndpoint(path);
    const { timeoutSeconds, forceProxy = true, ...restOptions } = options;
    const timeoutMs = (timeoutSeconds || PYTHON_SERVICE_TIMEOUT) * 1000;
    const normalizedOptions = normalizeFetchOptions(restOptions);

    if (forceProxy && PYTHON_SERVICE_PROXY_URL) {
        return proxyFetchFallback(targetUrl, normalizedOptions, timeoutMs);
    }

    return runFetchWithTimeout(targetUrl, normalizedOptions, timeoutMs);
}

function normalizeFetchOptions(source = {}) {
    const cloned = Object.assign({}, source);
    const originalHeaders = source.headers;

    if (originalHeaders instanceof Headers) {
        cloned.headers = new Headers(originalHeaders);
    } else if (Array.isArray(originalHeaders)) {
        const headerInstance = new Headers();
        originalHeaders.forEach((entry) => {
            if (!entry || entry.length < 2) {
                return;
            }
            const [key, value] = entry;
            if (value !== undefined && value !== null) {
                headerInstance.append(key, value);
            }
        });
        cloned.headers = headerInstance;
    } else if (originalHeaders && typeof originalHeaders === 'object') {
        const headerInstance = new Headers();
        Object.entries(originalHeaders).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => headerInstance.append(key, item));
            } else if (value !== undefined && value !== null) {
                headerInstance.append(key, value);
            }
        });
        cloned.headers = headerInstance;
    } else {
        cloned.headers = new Headers();
    }

    if (cloned.method) {
        cloned.method = String(cloned.method).toUpperCase();
    }

    return cloned;
}

function proxyFetchFallback(targetUrl, options, timeoutMs) {
    const proxyOptions = Object.assign({}, options);
    const headers = proxyOptions.headers instanceof Headers ? proxyOptions.headers : new Headers();
    const method = proxyOptions.method || 'GET';

    let urlObj;
    try {
        urlObj = new URL(targetUrl, window.location ? window.location.origin : undefined);
    } catch (error) {
        return Promise.reject(error);
    }

    const pathWithQuery = `${urlObj.pathname.replace(/^\/+/, '')}${urlObj.search}`;
    headers.set('X-Synktime-Proxy-Path', pathWithQuery);
    headers.set('X-Synktime-Proxy-Method', method);

    if (!proxyOptions.body) {
        proxyOptions.body = JSON.stringify({
            method,
            target: pathWithQuery
        });
        if (!headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/json');
        }
    }

    proxyOptions.headers = headers;
    proxyOptions.method = 'POST';
    proxyOptions.mode = 'same-origin';

    return runFetchWithTimeout(PYTHON_SERVICE_PROXY_URL, proxyOptions, timeoutMs);
}

function runFetchWithTimeout(url, options, timeoutMs) {
    const finalOptions = Object.assign({}, options || {});
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

    if (controller) {
        finalOptions.signal = controller.signal;
    }

    const timeoutId = controller && Number.isFinite(timeoutMs) && timeoutMs > 0
        ? setTimeout(() => controller.abort(), timeoutMs)
        : null;

    return fetch(url, finalOptions).finally(() => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
    });
}

class BiometricVerificationModal {
    constructor() {
        this.modal = null;
        this.selectedEmployee = null;
        this.employeeData = null;
        this.employeeBiometrics = {
            face: false,
            fingerprint: false,
            rfid: false
        };
        this.biometricsLoaded = false;
        this.currentTab = 'face';
        this.verificationResults = {
            face: null,
            fingerprint: null,
            rfid: null
        };
        
        // Control de registro para prevenir duplicados
        this.isRegistering = false;

        // Estados de verificación
        this.isVerifying = {
            face: false,
            fingerprint: false,
            rfid: false
        };

        // Streams de medios
        this.videoStream = null;
        this.fingerprintStream = null;
        this.rfidStream = null;

        // Configuración
        this.config = {
            face: {
                confidenceThreshold: 0.80,  // Umbral balanceado para facial
                qualityThreshold: 0.80,
                maxAttempts: 3
            },
            fingerprint: {
                confidenceThreshold: 0.90,
                maxAttempts: 3
            },
            rfid: {
                confidenceThreshold: 0.95,
                readTimeout: 5000
            }
        };

        // Intentos de verificación
        this.attempts = {
            face: 0,
            fingerprint: 0,
            rfid: 0
        };

        // Flag para controlar si estamos en proceso de confirmación de asistencia
        this.isConfirmingAttendance = false;

        // Flag para controlar si la verificación facial fue exitosa (mantener cámara abierta)
        this.faceVerificationSuccessful = false;

        // Estado de dispositivos
        this.deviceStatus = {
            face: { connected: false, available: false, lastCheck: null },
            fingerprint: { connected: false, available: false, lastCheck: null },
            rfid: { connected: false, available: false, lastCheck: null }
        };

        // Foto capturada durante verificación (para usar en completeVerification)
        this.capturedVerificationPhoto = null;

        this.init();
    }

    async checkDeviceConnectivity() {
        try {
            // Verificar cámara
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                this.deviceStatus.face.available = true;
                this.deviceStatus.face.lastCheck = Date.now();
            }

            // Verificar servicios Python para otros dispositivos
            const healthTarget = resolvePythonServiceHealthTarget();
            const response = await pythonServiceFetch(healthTarget, {
                method: 'GET',
                timeoutSeconds: 5
            });
            if (response.ok) {
                const health = await response.json();
                this.deviceStatus.fingerprint.available = health.services?.fingerprint?.available || false;
                this.deviceStatus.rfid.available = health.services?.rfid?.available || false;
                this.deviceStatus.fingerprint.lastCheck = Date.now();
                this.deviceStatus.rfid.lastCheck = Date.now();
            }
        } catch (error) {
            console.warn('Error checking device connectivity:', error);
        }
    }

    init() {
        try {
            console.log('🔄 Initializing BiometricVerificationModal...');
            this.bindEvents();
            this.loadConfiguration();
            this.checkServiceStatus();
            console.log('✅ BiometricVerificationModal initialization completed');
        } catch (error) {
            console.error('❌ Error during initialization:', error);
            console.warn('⚠️ Initialization partially failed, but instance may still be usable');
        }
    }

    bindEvents() {
        try {
            // Evento cuando se abre el modal (verificar que existe)
            const modalElement = document.getElementById('biometricVerificationModal');
            if (modalElement) {
                modalElement.addEventListener('show.bs.modal', (event) => {
                    const button = event.relatedTarget;
                    let employeeId, attendanceType;

                    if (button) {
                        // Modal abierto desde un botón (método tradicional)
                        employeeId = button.getAttribute('data-employee-id');
                        attendanceType = button.getAttribute('data-attendance-type') || 'ENTRADA';
                    } else {
                        // Modal abierto programáticamente
                        const modal = document.getElementById('biometricVerificationModal');
                        employeeId = modal.getAttribute('data-employee-id');
                        attendanceType = modal.getAttribute('data-attendance-type') || 'ENTRADA';
                    }

                    if (employeeId) {
                        this.loadEmployeeData(employeeId, attendanceType);
                    }
                });

                // Evento cuando se cierra el modal
                modalElement.addEventListener('hide.bs.modal', () => {
                    // Solo detener verificación si no estamos en proceso de confirmación y la verificación facial no fue exitosa
                    if (!this.isConfirmingAttendance && !this.faceVerificationSuccessful) {
                        this.stopAllVerification();
                    }
                    // Si estamos en confirmación o verificación exitosa, no detener verificación aquí
                    // El modal de confirmación se encargará de detener todo
                });

                // Evento cuando el modal se ha cerrado completamente
                modalElement.addEventListener('hidden.bs.modal', () => {
                    // Siempre resetear el modal y detener la cámara cuando se cierre completamente
                    this.resetModal();
                    this.ensureCameraStop();
                    this.faceVerificationSuccessful = false; // Resetear flag
                    this.refreshAttendanceData(); // Refrescar datos después de cerrar
                });
            } else {
                console.warn('⚠️ biometricVerificationModal element not found, skipping modal events');
            }

            // Evento cuando se cierra el modal de confirmación de verificación
            const confirmationModal = document.getElementById('verificationConfirmationModal');
            if (confirmationModal) {
                confirmationModal.addEventListener('hide.bs.modal', () => {
                    console.log('Modal de confirmación cerrado, cerrando modal de verificación y deteniendo cámara...');
                    this.isConfirmingAttendance = false; // Resetear flag
                    this.faceVerificationSuccessful = false; // Resetear flag de verificación exitosa

                    // Cerrar también el modal de verificación
                    const verificationModal = bootstrap.Modal.getInstance(document.getElementById('biometricVerificationModal'));
                    if (verificationModal) {
                        verificationModal.hide();
                    }

                    // El evento hidden.bs.modal del modal de verificación se encargará de detener la cámara
                });
            } else {
                console.warn('⚠️ verificationConfirmationModal element not found, skipping confirmation events');
            }

            // Eventos de pestañas - solo cambiar pestaña, no iniciar verificación automáticamente
            const tabElements = document.querySelectorAll('#verificationTabs .nav-link');
            if (tabElements.length > 0) {
                tabElements.forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        const tabType = e.target.id.replace('-verification-tab', '').replace('verification-', '');
                        this.switchTab(tabType);
                        this.enableVerificationControls(tabType);
                    });
                });
            } else {
                console.warn('⚠️ Verification tabs not found, skipping tab events');
            }

            // Eventos de controles faciales
            const startAutoBtn = document.getElementById('startAutoIdentification');
            const startFaceBtn = document.getElementById('startFaceVerification');
            const stopFaceBtn = document.getElementById('stopFaceVerification');
            
            if (startAutoBtn) {
                startAutoBtn.addEventListener('click', () => this.startAutoIdentification());
            }
            if (startFaceBtn) {
                startFaceBtn.addEventListener('click', () => this.startFaceVerification());
            }
            if (stopFaceBtn) {
                stopFaceBtn.addEventListener('click', () => this.stopFaceVerification());
            }

            // Eventos de controles de huella
            const startFingerprintBtn = document.getElementById('startFingerprintVerification');
            const verifyFingerprintBtn = document.getElementById('verifyFingerprintNow');
            const stopFingerprintBtn = document.getElementById('stopFingerprintVerification');
            
            if (startFingerprintBtn) {
                startFingerprintBtn.addEventListener('click', () => this.startFingerprintVerification());
            }
            if (verifyFingerprintBtn) {
                verifyFingerprintBtn.addEventListener('click', () => this.verifyFingerprintNow());
            }
            if (stopFingerprintBtn) {
                stopFingerprintBtn.addEventListener('click', () => this.stopFingerprintVerification());
            }

            // Eventos de controles RFID
            const startRfidBtn = document.getElementById('startRfidVerification');
            const verifyRfidBtn = document.getElementById('verifyRfidNow');
            const stopRfidBtn = document.getElementById('stopRfidVerification');
            
            if (startRfidBtn) {
                startRfidBtn.addEventListener('click', () => this.startRfidVerification());
            }
            if (verifyRfidBtn) {
                verifyRfidBtn.addEventListener('click', () => this.verifyRfidNow());
            }
            if (stopRfidBtn) {
                stopRfidBtn.addEventListener('click', () => this.stopRfidVerification());
            }

            // Evento de completar verificación
            const completeBtn = document.getElementById('completeVerification');
            if (completeBtn) {
                completeBtn.addEventListener('click', () => this.completeVerification());
            }

            // Evento de reintentar verificación
            const retryBtn = document.getElementById('retry-verification-btn');
            if (retryBtn) {
                retryBtn.addEventListener('click', () => this.retryVerification());
            }

        } catch (error) {
            console.error('❌ Error binding events:', error);
            console.warn('⚠️ Some events may not be bound, but instance can still be used for selectCandidate');
        }
    }

    async loadConfiguration() {
        try {
            const response = await fetch('biometric_config.json');
            if (response.ok) {
                const config = await response.json();
                this.config = {
                    facial: {
                        confidenceThreshold: config.biometric.methods.facial.confidence_threshold,
                        qualityThreshold: 0.80,
                        maxAttempts: config.biometric.verification.max_attempts
                    },
                    fingerprint: {
                        confidenceThreshold: config.biometric.methods.fingerprint.confidence_threshold,
                        maxAttempts: config.biometric.verification.max_attempts
                    },
                    rfid: {
                        confidenceThreshold: config.biometric.methods.rfid.confidence_threshold,
                        readTimeout: config.biometric.methods.rfid.read_timeout * 1000
                    }
                };
            }
        } catch (error) {
            console.warn('No se pudo cargar la configuración, usando valores por defecto:', error);
        }
    }

    async checkServiceStatus() {
        try {
            const healthTarget = resolvePythonServiceHealthTarget();
            const response = await pythonServiceFetch(healthTarget, {
                method: 'GET',
                timeoutSeconds: 5
            });
            const status = response.ok ? 'Conectado' : 'Desconectado';
            
            const statusElement = document.getElementById('face-service-status');
            if (statusElement) {
                statusElement.textContent = status;
                statusElement.className = response.ok ? 'text-success' : 'text-danger';
            } else {
                console.warn('⚠️ face-service-status element not found');
            }
        } catch (error) {
            const statusElement = document.getElementById('face-service-status');
            if (statusElement) {
                statusElement.textContent = 'Desconectado';
                statusElement.className = 'text-danger';
            } else {
                console.warn('⚠️ face-service-status element not found, service status not updated');
            }
        }
    }

    async loadEmployeeData(employeeId, attendanceType) {
        try {
            this.showLoading('Cargando información del empleado...');

            const response = await fetch(`/api/employee/get.php?id=${employeeId}`);
            if (!response.ok) {
                throw new Error('No se pudo cargar la información del empleado');
            }

            const result = await response.json();
            if (!result.success || !result.data) {
                throw new Error(result.message || 'Respuesta inválida del servidor');
            }

            const employee = result.data;
            this.employeeData = employee;
            this.selectedEmployee = parseInt(employeeId, 10); // Asegurar que sea número

            // Cargar información biométrica del empleado
            await this.loadEmployeeBiometrics(employeeId);

            // Verificar conectividad de dispositivos
            await this.checkDeviceConnectivity();

            // Actualizar UI después de verificar dispositivos
            this.updateAvailableBiometricServices();

            // Actualizar UI - mapear campos correctamente
            document.getElementById('verification-employee-id').value = employeeId;
            document.getElementById('verification-display-employee-id').textContent = employeeId;
            document.getElementById('verification-employee-code').textContent = employee.ID_EMPLEADO || employeeId;
            document.getElementById('verification-employee-name').textContent = `${employee.NOMBRE || ''} ${employee.APELLIDO || ''}`.trim() || 'Sin nombre';
            document.getElementById('verification-employee-establishment').textContent = employee.ESTABLECIMIENTO || 'No especificado';
            document.getElementById('verification-attendance-type').value = attendanceType;
            document.getElementById('verification-type-display').textContent = attendanceType;

            // Cargar estado biométrico
            await this.loadEmployeeBiometrics(employeeId);

            this.hideLoading();

        } catch (error) {
            this.hideLoading();
            this.showError('Error al cargar empleado', error.message, [
                'Verifique que el empleado esté registrado en el sistema',
                'Contacte al administrador si el problema persiste',
                'Intente recargar la página'
            ]);
        }
    }

    async loadEmployeeBiometrics(employeeId) {
        try {
            const response = await fetch(`/api/employee_biometrics.php?employee_id=${employeeId}`);
            if (response.ok) {
                const biometrics = await response.json();
                console.log('Biometric data received:', biometrics);

                // La API devuelve un array de registros biométricos
                // Procesar el array para determinar qué servicios están disponibles
                this.employeeBiometrics.face = false;
                this.employeeBiometrics.fingerprint = false;
                this.employeeBiometrics.rfid = false;

                // Verificar si hay registros para cada tipo (todos los registros se consideran activos)
                if (Array.isArray(biometrics)) {
                    biometrics.forEach(bio => {
                        switch (bio.tipo) {
                            case 'face':
                                this.employeeBiometrics.face = true;
                                this.deviceStatus.face.available = true; // Marcar como disponible si tiene datos
                                break;
                            case 'fingerprint':
                                this.employeeBiometrics.fingerprint = true;
                                this.deviceStatus.fingerprint.available = true; // Marcar como disponible si tiene datos
                                break;
                            case 'rfid':
                                this.employeeBiometrics.rfid = true;
                                this.deviceStatus.rfid.available = true; // Marcar como disponible si tiene datos
                                break;
                        }
                    });
                }

                console.log('Biometric services loaded:', this.employeeBiometrics);
                console.log('Device status updated:', this.deviceStatus);

                // Actualizar la interfaz para mostrar solo los métodos disponibles
                this.updateAvailableBiometricServices();

                // Actualizar mensaje de estado
                this.updateVerificationStatus();
                
                // Marcar que los datos biométricos se han cargado
                this.biometricsLoaded = true;
            } else {
                console.warn('Failed to load biometric data, response status:', response.status);
                this.biometricsLoaded = true; // Marcar como cargado incluso si falló
            }
        } catch (error) {
            console.warn('No se pudo cargar el estado biométrico:', error);
            // Por defecto, asumir que solo facial está disponible si hay error
            this.employeeBiometrics.face = true;
            this.employeeBiometrics.fingerprint = false;
            this.employeeBiometrics.rfid = false;
            this.deviceStatus.face.available = true;
            this.biometricsLoaded = true; // Marcar como cargado incluso si hay error
        }
    }

    updateAvailableBiometricServices() {
        // SIEMPRE MOSTRAR TODAS LAS PESTAÑAS EN MODO AUTOMÁTICO
        // En modo automático no hay empleado seleccionado, así que mostramos todos los métodos disponibles
        if (this.identificationMode === 'auto') {
            const facialTab = document.getElementById('face-verification-tab');
            const fingerprintTab = document.getElementById('fingerprint-verification-tab');
            const rfidTab = document.getElementById('rfid-verification-tab');

            if (facialTab) {
                facialTab.style.display = 'block';
                facialTab.classList.remove('disabled', 'text-muted');
            }

            if (fingerprintTab) {
                fingerprintTab.style.display = 'block';
                fingerprintTab.classList.remove('disabled', 'text-muted');
            }

            if (rfidTab) {
                rfidTab.style.display = 'block';
                rfidTab.classList.remove('disabled', 'text-muted');
            }

            console.log('🔓 Auto identification mode: All verification tabs enabled');
            return;
        }

        // Para modo manual (con empleado seleccionado), mostrar según configuración del empleado
        const facialTab = document.getElementById('face-verification-tab');
        const fingerprintTab = document.getElementById('fingerprint-verification-tab');
        const rfidTab = document.getElementById('rfid-verification-tab');

        if (facialTab) {
            if (this.employeeBiometrics.face && this.deviceStatus.face.available) {
                facialTab.style.display = 'block';
                facialTab.classList.remove('disabled', 'text-muted');
            } else {
                facialTab.style.display = 'none';
            }
        }

        if (fingerprintTab) {
            if (this.employeeBiometrics.fingerprint && this.deviceStatus.fingerprint.available) {
                fingerprintTab.style.display = 'block';
                fingerprintTab.classList.remove('disabled', 'text-muted');
            } else {
                fingerprintTab.style.display = 'none';
            }
        }

        if (rfidTab) {
            if (this.employeeBiometrics.rfid && this.deviceStatus.rfid.available) {
                rfidTab.style.display = 'block';
                rfidTab.classList.remove('disabled', 'text-muted');
            } else {
                rfidTab.style.display = 'none';
            }
        }

        // NO seleccionar automáticamente ninguna pestaña
        // El usuario debe hacer clic manualmente en la pestaña deseada
    }

    switchTab(tabName) {
        this.currentTab = tabName;

        // Detener verificación actual
        this.stopAllVerification();

        // Resetear estados
        this.resetTabStates();

        // Cambiar a la nueva pestaña
        const tabButton = document.getElementById(`${tabName}-verification-tab`);
        if (tabButton) {
    
            tabButton.click();
        }
    }

    resetTabStates() {
        // Resetear todos los estados de verificación
        Object.keys(this.isVerifying).forEach(key => {
            this.isVerifying[key] = false;
        });

        // Resetear UI
        document.querySelectorAll('.scanner-animation').forEach(el => {
            el.style.display = 'none';
        });

        document.querySelectorAll('.btn').forEach(btn => {
            btn.disabled = false;
        });

        // Resetear resultados
        document.querySelectorAll('.verification-result').forEach(el => {
            el.style.display = 'none';
        });
    }

    // === VERIFICACIÓN FACIAL ===
    async startFaceVerification() {
        try {
            // Verificar que los datos biométricos se hayan cargado
            if (!this.biometricsLoaded) {
                // Mostrar mensaje de carga en lugar de error
                this.showInfo('Cargando datos biométricos', 'Los datos del empleado se están cargando. Por favor espere un momento...');
                // Intentar cargar datos si no están cargados
                if (this.selectedEmployee) {
                    await this.loadEmployeeBiometrics(this.selectedEmployee);
                }
                return;
            }

            // Verificar que el empleado tenga registro facial
            if (!this.employeeBiometrics.face) {
                this.showError('Servicio no disponible', 'Este empleado no tiene registro facial configurado.', [
                    'Configure el registro facial del empleado primero',
                    'Contacte al administrador del sistema'
                ]);
                return;
            }

            // Verificar que la cámara esté disponible
            if (!this.deviceStatus.face.available) {
                this.showError('Dispositivo no disponible', 'La cámara no está disponible o conectada.', [
                    'Verifique que la cámara esté conectada',
                    'Asegúrese de que no esté siendo usada por otra aplicación',
                    'Contacte al soporte técnico si el problema persiste'
                ]);
                return;
            }

            this.isVerifying.face = true;
            this.updateFaceStatus('Iniciando cámara...', 'info');

            // Solicitar acceso a la cámara
            this.videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: 640,
                    height: 480,
                    facingMode: 'user'
                }
            });

            const video = document.getElementById('faceVerificationVideo');
            video.srcObject = this.videoStream;

            video.onloadedmetadata = () => {
                video.play();
                this.updateFaceStatus(`Cámara iniciada. Verificación automática en progreso... (Umbral: ${(this.config?.face?.confidenceThreshold || 0.85) * 100}%)`, 'success');
                this.enableFaceControls(true);
                // Iniciar verificación automática inmediatamente
                this.startFaceDetection();
            };

        } catch (error) {
            this.isVerifying.face = false;
            console.error('Error accessing camera:', error);
            this.updateFaceStatus('Error al acceder a la cámara. Verifique permisos.', 'danger');
            // No mostrar modal de error para errores de cámara - solo actualizar estado
        }
    }

    stopFaceVerification() {
        console.log('🛑 Stopping face verification completely');
        this.isVerifying.face = false;
        this.identificationMode = null; // Limpiar modo de identificación
        this.isRegistering = false; // Asegurar que no haya registro en progreso

        // Limpiar cualquier timeout de detección pendiente
        if (this.detectionTimeout) {
            clearTimeout(this.detectionTimeout);
            this.detectionTimeout = null;
            console.log('✅ Detection timeout cleared');
        }

        if (this.videoStream) {
            this.videoStream.getTracks().forEach(track => {
                track.stop();
                console.log('Face verification track stopped:', track.kind, track.readyState);
            });
            this.videoStream = null;
        }

        const video = document.getElementById('faceVerificationVideo');
        if (video) {
            video.srcObject = null;
            video.load(); // Forzar limpieza del elemento video
        }

        this.updateFaceStatus('Verificación detenida', 'secondary');
        this.enableFaceControls(false);

        console.log('✅ Face verification stopped and camera released');
    }

    enableFaceControls(enabled) {
        const startBtn = document.getElementById('startFaceVerification');
        if (startBtn) startBtn.disabled = enabled;

        const autoBtn = document.getElementById('startAutoIdentification');
        if (autoBtn) autoBtn.disabled = enabled;

        const stopBtn = document.getElementById('stopFaceVerification');
        if (stopBtn) stopBtn.disabled = !enabled;
    }

    async startAutoIdentification() {
        try {
            console.log('Iniciando identificación automática...');
            console.log('Estado de dispositivos:', this.deviceStatus);
            
            this.isVerifying.face = true;
            this.identificationMode = 'auto'; // Flag para modo automático
            this.updateFaceStatus('Iniciando cámara para identificación automática...', 'info');

            // En modo automático, intentar acceso directo a la cámara sin verificar deviceStatus
            // ya que no tenemos empleado preseleccionado para verificar datos biométricos
            console.log('Solicitando acceso a la cámara...');
            
            // Solicitar acceso a la cámara
            this.videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: 640,
                    height: 480,
                    facingMode: 'user'
                }
            });

            console.log('Acceso a cámara obtenido exitosamente');
            
            const video = document.getElementById('faceVerificationVideo');
            video.srcObject = this.videoStream;

            video.onloadedmetadata = () => {
                console.log('Video metadata cargado, iniciando reproducción...');
                video.play();
                this.updateFaceStatus('Identificando empleado automáticamente...', 'success');
                this.enableFaceControls(true);
                // Iniciar detección automática con manejo de errores separado
                try {
                    this.startAutoFaceDetection();
                } catch (detectionError) {
                    console.error('Error iniciando detección automática:', detectionError);
                    this.showError('Error de detección', 'Error al iniciar la detección automática de rostros.');
                }
            };

            video.onerror = (error) => {
                console.error('Error en el elemento video:', error);
                this.showError('Error de video', 'Error al inicializar el elemento de video');
            };

        } catch (error) {
            console.error('Error en startAutoIdentification:', error);
            this.isVerifying.face = false;
            this.identificationMode = null;
            
            let errorMessage = 'No se pudo acceder a la cámara del dispositivo.';
            let suggestions = [
                'Asegúrese de que la cámara esté conectada y funcionando',
                'Verifique los permisos de cámara en el navegador',
                'Cierre otras aplicaciones que puedan estar usando la cámara',
                'Intente refrescar la página y volver a intentar'
            ];
            
            // Detalles específicos del error
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Permisos de cámara denegados.';
                suggestions = [
                    'Haga clic en el icono de cámara en la barra de direcciones',
                    'Seleccione "Permitir" para el acceso a la cámara',
                    'Recargue la página después de otorgar permisos'
                ];
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No se encontró ninguna cámara en el dispositivo.';
                suggestions = [
                    'Verifique que la cámara esté conectada correctamente',
                    'Pruebe con otra cámara si está disponible',
                    'Reinicie el navegador y vuelva a intentar'
                ];
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'La cámara está siendo usada por otra aplicación.';
                suggestions = [
                    'Cierre otras aplicaciones que puedan estar usando la cámara',
                    'Reinicie el navegador',
                    'Reinicie el dispositivo si es necesario'
                ];
            }
            
            this.showError('Error de cámara', errorMessage, suggestions);
        }
    }

    startAutoFaceDetection() {
        console.log('🎥 Starting auto face detection loop');

        const video = document.getElementById('faceVerificationVideo');
        const canvas = document.getElementById('faceVerificationCanvas');
        const ctx = canvas.getContext('2d');

        // Flag para prevenir múltiples loops simultáneos
        let detectionActive = true;

        // Almacenar referencia al timeout para poder cancelarlo
        this.detectionTimeout = null;

        const detectFrame = async () => {
            // Verificación múltiple para asegurar que se detenga completamente
            if (!this.isVerifying.face || this.identificationMode !== 'auto' || !detectionActive || this.isRegistering) {
                console.log('🛑 Detection loop stopped:', {
                    isVerifying: this.isVerifying.face,
                    identificationMode: this.identificationMode,
                    detectionActive,
                    isRegistering: this.isRegistering
                });
                return;
            }

            // Verificar que el video esté listo
            if (video.videoWidth === 0 || video.videoHeight === 0) {
                console.warn('Video not ready, skipping frame');
                if (this.isVerifying.face) {
                    setTimeout(detectFrame, 2000);
                }
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);

            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            const base64Image = imageData.split(',')[1];

            if (!base64Image || base64Image.length < 1000) {
                console.warn('Invalid or too small image data, skipping frame');
                if (this.isVerifying.face) {
                    setTimeout(detectFrame, 2000);
                }
                return;
            }

            console.log('Sending auto identification request:', {
                image_data_length: base64Image.length,
                video_dimensions: `${video.videoWidth}x${video.videoHeight}`,
                confidence_threshold: this.config?.face?.confidenceThreshold || 0.80  // Umbral balanceado
            });

            // Llamar al endpoint de identificación automática
            const response = await fetch('api/biometric/identify-facial.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    image_data: `data:image/jpeg;base64,${base64Image}`,
                    confidence_threshold: this.config?.face?.confidenceThreshold || 0.80  // Umbral balanceado
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const result = await response.json();
            console.log('Auto identification result:', result);

            if (result.success && result.employee) {
                // Empleado identificado exitosamente
                console.log('✅ Employee identified successfully, stopping detection loop');
                detectionActive = false; // Detener completamente el loop

                // Capturar la foto del frame actual ANTES de detener la verificación
                let capturedPhoto = null;
                try {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg', 0.8);
                    capturedPhoto = imageData.split(',')[1];
                    console.log('📸 Foto capturada del frame identificado, tamaño:', capturedPhoto.length);
                } catch (photoError) {
                    console.warn('⚠️ Error al capturar foto del frame identificado:', photoError);
                }

                this.updateFaceStatus(`¡Empleado identificado! ${result.employee.NOMBRE_COMPLETO} (${(result.confidence * 100).toFixed(1)}%)`, 'success');

                // Detener identificación
                this.stopFaceVerification();

                // Auto-seleccionar el empleado identificado
                this.selectedEmployee = result.employee.ID_EMPLEADO;
                this.employeeData = result.employee;

                // Actualizar la información del empleado en el modal
                this.updateEmployeeInfo(result.employee);

                // Almacenar la foto capturada para el registro
                if (capturedPhoto) {
                    this.capturedVerificationPhoto = capturedPhoto;
                }

                // Registrar asistencia automáticamente solo si no hay un registro en progreso
                if (!this.isRegistering) {
                    await this.registerAttendanceAfterIdentification(result.employee);
                } else {
                    console.log('⚠️ Registro ya en progreso desde auto-identificación, saltando');
                }

                return; // Salir del loop de detección

            } else if (result.candidates && result.candidates.length > 0) {
                // Aplicar nueva lógica de umbrales
                // Manejar tanto 'confidence' como 'CONFIDENCE' en los candidatos
                const maxConfidence = Math.max(...result.candidates.map(c => {
                    return c.confidence || c.CONFIDENCE || 0;
                }));

                console.log('🎯 Análisis de confianza:', {
                    maxConfidence,
                    threshold_85: 0.85,
                    threshold_70: 0.70,
                    candidates_data: result.candidates.map(c => ({
                        name: c.NOMBRE_COMPLETO || c.full_name,
                        confidence: c.confidence || c.CONFIDENCE || 0
                    }))
                });

                if (maxConfidence >= 0.85) {
                    // >= 85%: Selección automática del empleado con mayor confianza
                    console.log('✅ High confidence (>= 85%), stopping detection loop');
                    detectionActive = false; // Detener completamente el loop

                    const bestCandidate = result.candidates.find(c =>
                        (c.confidence || c.CONFIDENCE || 0) === maxConfidence
                    );
                    console.log('✅ Confianza >= 85%, mostrando confirmación automática:', bestCandidate);

                    this.updateFaceStatus(`¡Empleado identificado automáticamente! ${bestCandidate.NOMBRE_COMPLETO || bestCandidate.full_name} (${(maxConfidence * 100).toFixed(1)}%)`, 'success');

                    // Capturar la foto del frame actual ANTES de detener la verificación
                    let capturedPhoto = null;
                    try {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        ctx.drawImage(video, 0, 0);
                        const imageData = canvas.toDataURL('image/jpeg', 0.8);
                        capturedPhoto = imageData.split(',')[1];
                        console.log('📸 Foto capturada del frame identificado (>=85%), tamaño:', capturedPhoto.length);
                    } catch (photoError) {
                        console.warn('⚠️ Error al capturar foto del frame identificado (>=85%):', photoError);
                    }

                    // Detener identificación
                    this.stopFaceVerification();

                    // Auto-seleccionar el empleado
                    this.selectedEmployee = bestCandidate.ID_EMPLEADO || bestCandidate.employee_id;
                    this.employeeData = bestCandidate;
                    this.updateEmployeeInfo(bestCandidate);

                    // Almacenar la foto capturada para el registro
                    if (capturedPhoto) {
                        this.capturedVerificationPhoto = capturedPhoto;
                    }

                    // Mostrar confirmación elegante en lugar de candidatos múltiples
                    this.showAutoIdentificationConfirmation(bestCandidate, maxConfidence);
                    return;

                } else if (maxConfidence >= 0.70) {
                    // 70-85%: Empleado está en DB pero necesita mejor posicionamiento
                    const bestCandidate = result.candidates.find(c =>
                        (c.confidence || c.CONFIDENCE || 0) === maxConfidence
                    );
                    console.log('⚠️ Confianza 70-85%, requiere reposicionamiento:', maxConfidence);

                    const employeeName = bestCandidate.NOMBRE_COMPLETO || bestCandidate.full_name;
                    this.updateFaceStatus(`Empleado detectado: ${employeeName} (${(maxConfidence * 100).toFixed(1)}%). Por favor, posiciónese mejor frente a la cámara.`, 'warning');

                    // Continuar intentando pero mostrar sugerencia de reposicionamiento
                    this.showRepositioningGuide(bestCandidate, maxConfidence);

                } else {
                    // < 70%: No existe en DB o confianza muy baja
                    console.log('❌ Confianza < 70%, empleado no encontrado en DB:', maxConfidence);
                    this.updateFaceStatus(`Empleado no reconocido (${(maxConfidence * 100).toFixed(1)}%). Verifique que esté registrado en el sistema.`, 'danger');

                    // Mostrar mensaje de empleado no encontrado
                    this.showEmployeeNotFound(maxConfidence);
                }

            } else {
                // Sin candidatos encontrados
                this.updateFaceStatus('Buscando empleado...', 'info');
            }

            // Continuar con el siguiente frame si seguimos verificando
            if (this.isVerifying.face && this.identificationMode === 'auto') {
                this.detectionTimeout = setTimeout(detectFrame, 2000); // Detectar cada 2 segundos
            }
        };

        // Iniciar detección inmediatamente
        detectFrame();
    }    updateEmployeeInfo(employee) {
        document.getElementById('verification-employee-id').value = employee.ID_EMPLEADO;
        document.getElementById('verification-employee-code').textContent = employee.ID_EMPLEADO;
        document.getElementById('verification-employee-name').textContent = employee.NOMBRE_COMPLETO || `${employee.NOMBRE} ${employee.APELLIDO}`;
        document.getElementById('verification-employee-establishment').textContent = employee.ESTABLECIMIENTO || 'No especificado';
    }

    async registerAttendanceAfterIdentification(employee) {
        // Prevenir múltiples registros simultáneos
        if (this.isRegistering) {
            console.log('⚠️ Registro ya en progreso, ignorando nueva solicitud');
            return;
        }
        
        this.isRegistering = true;
        
        try {
            this.updateFaceStatus('Validando horarios y registrando asistencia...', 'info');
            
            const attendanceType = document.getElementById('verification-attendance-type').value || 'ENTRADA';
            
            // Extraer confianza correctamente
            const confidence = employee.confidence || employee.CONFIDENCE || 0;
            
            // Usar foto ya capturada durante la identificación, o capturar nueva si no existe
            let photoData = null;
            if (this.capturedVerificationPhoto) {
                // Usar foto capturada durante la identificación automática
                photoData = `data:image/jpeg;base64,${this.capturedVerificationPhoto}`;
                console.log('✅ Usando foto capturada durante identificación automática, tamaño:', this.capturedVerificationPhoto.length);
            } else {
                // Capturar foto nueva si no hay una capturada
                try {
                    console.log('📸 Capturando foto para identificación automática...');
                    const photoCaptured = await this.capturePhotoForVerification();
                    
                    if (photoCaptured && this.capturedVerificationPhoto) {
                        // Convertir a formato completo con data URL
                        photoData = `data:image/jpeg;base64,${this.capturedVerificationPhoto}`;
                        console.log('✅ Foto capturada exitosamente para identificación automática');
                    } else {
                        console.warn('⚠️ No se pudo capturar foto para identificación automática');
                    }
                } catch (photoError) {
                    console.warn('Error al capturar foto para identificación automática:', photoError);
                }
            }
            
            // Preparar datos para el registro usando la nueva API mejorada
            const attendanceData = {
                employee_id: employee.ID_EMPLEADO || employee.employee_id,
                type: attendanceType,
                timestamp: window.Bogota ? window.Bogota.getISOString() : new Date().toISOString(),
                verification_method: 'biometric_facial',
                verification_results: {
                    biometric_type: 'facial',
                    confidence_score: confidence * 100, // Convertir a porcentaje
                    verification_success: true,
                    employee_data: {
                        id: employee.ID_EMPLEADO || employee.employee_id,
                        name: employee.NOMBRE_COMPLETO || employee.full_name
                    }
                },
                photo_data: photoData // Incluir datos de la foto
            };
            
            console.log('📝 Registrando asistencia con datos:', attendanceData);
            
            // Construir URL de la API usando configuración base
            const apiUrl = (window.BIOMETRIC_API_BASE || 'api/attendance/') + 'register-biometric-enhanced.php';
            console.log('🌐 Usando URL de API:', apiUrl);
            
            // Usar la nueva API mejorada con validaciones completas
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(attendanceData)
            });

            let result;
            try {
                result = await response.json();
            } catch (parseError) {
                throw new Error(`Error del servidor: HTTP ${response.status} - No se pudo procesar la respuesta`);
            }

            if (response.ok && result.success) {
                this.updateFaceStatus('¡Asistencia registrada exitosamente!', 'success');
                const employeeName = employee.NOMBRE_COMPLETO || employee.full_name || 'Empleado';
                
                // Mostrar información detallada del registro
                let successMessage = `✅ Asistencia registrada: ${employeeName} (${attendanceType})`;
                if (result.data.horario_info) {
                    successMessage += `\n🕐 Turno: ${result.data.horario_info.nombre_turno} (Orden: ${result.data.horario_info.orden_turno})`;
                }
                if (result.data.photo) {
                    successMessage += `\n📸 Foto de evidencia capturada`;
                }
                
                showMessage(successMessage, 'success', 5000);
                
                // Mostrar foto capturada si existe
                if (result.data.photo && result.data.photo.filename) {
                    showCapturedPhoto(result.data.photo.url);
                }
                
                // Cerrar modal y recargar página después de unos segundos
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('biometricVerificationModal'));
                    if (modal) {
                        modal.hide();
                    }
                    // Recargar página para mostrar la nueva asistencia
                    window.location.reload();
                }, 3000);
                
            } else {
                // Manejo mejorado de errores del servidor
                const errorMessage = result.message || result.error || 'No se pudo registrar la asistencia';
                this.updateFaceStatus('Error registrando asistencia', 'danger');
                
                // Mostrar mensaje de error específico al usuario
                showMessage(`❌ ${errorMessage}`, 'danger', 8000);
                
                // Si hay detalles adicionales, mostrarlos en consola
                if (result.data) {
                    console.warn('Detalles del error:', result.data);
                }
            }
            
        } catch (error) {
            console.error('Error registering attendance:', error);
            this.updateFaceStatus('Error registrando asistencia', 'danger');
            this.showError('Error de registro', error.message || 'Error conectando con el servidor para registrar asistencia');
        } finally {
            // Liberar el lock de registro
            this.isRegistering = false;
            
            // Limpiar la foto capturada después de usarla
            this.capturedVerificationPhoto = null;
        }
    }

    showCandidateSelection(candidates, confidence) {
        const resultDiv = document.getElementById('face-verification-result');
        const alertDiv = document.getElementById('face-result-alert');
        const titleElement = document.getElementById('face-result-title');
        const messageElement = document.getElementById('face-result-message');

        alertDiv.className = 'alert alert-warning';
        titleElement.textContent = 'Múltiples candidatos encontrados';
        
        let candidatesHTML = `<p>Confianza máxima: ${(confidence * 100).toFixed(1)}%</p>`;
        candidatesHTML += '<p>Seleccione el empleado correcto:</p>';
        candidatesHTML += '<div class="list-group">';
        
        candidates.forEach(candidate => {
            const employeeName = candidate.NOMBRE_COMPLETO || candidate.full_name || 'Empleado';
            const employeeId = candidate.ID_EMPLEADO || candidate.employee_id || 0;
            const employeeDNI = candidate.DNI || candidate.dni || 'N/A';
            const candidateConfidence = candidate.CONFIDENCE || candidate.confidence || 0;
            
            candidatesHTML += `
                <button type="button" class="list-group-item list-group-item-action" 
                        onclick="selectEmployeeCandidate(${employeeId}, '${employeeName}')">
                    <strong>${employeeName}</strong><br>
                    <small>DNI: ${employeeDNI} | Confianza: ${(candidateConfidence * 100).toFixed(1)}%</small>
                </button>
            `;
        });
        
        candidatesHTML += '</div>';
        messageElement.innerHTML = candidatesHTML;
        resultDiv.style.display = 'block';
    }



    startFaceDetection() {
        const video = document.getElementById('faceVerificationVideo');
        const canvas = document.getElementById('faceVerificationCanvas');
        const ctx = canvas.getContext('2d');

        const detectFrame = async () => {
            if (!this.isVerifying.face) return;

            try {
                // Verificar que el video esté listo
                if (video.videoWidth === 0 || video.videoHeight === 0) {
                    console.warn('Video not ready, skipping frame');
                    if (this.isVerifying.face) {
                        setTimeout(detectFrame, 2000);
                    }
                    return;
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);

                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                // Extraer solo la parte base64 del data URL
                const base64Image = imageData.split(',')[1];

                // Validar datos antes de enviar
                if (!this.selectedEmployee) {
                    console.error('No employee selected for facial verification');
                    return;
                }

                if (!base64Image || base64Image.length < 1000) { // Aumentar el mínimo para imágenes reales
                    console.warn('Invalid or too small image data, skipping frame. Length:', base64Image.length);
                    if (this.isVerifying.face) {
                        setTimeout(detectFrame, 2000);
                    }
                    return;
                }

                console.log('Sending facial verification request:', {
                    employee_id: this.selectedEmployee,
                    image_data_length: base64Image.length,
                    video_dimensions: `${video.videoWidth}x${video.videoHeight}`,
                    confidence_threshold: this.config?.face?.confidenceThreshold || 0.80  // Umbral balanceado
                });

                // Enviar frame al servicio Python
                const response = await pythonServiceFetch('attendance/verify-facial', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_id: this.selectedEmployee,
                        image_data: base64Image,
                        confidence_threshold: this.config?.face?.confidenceThreshold || 0.80  // Umbral balanceado
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    console.log('Facial verification response:', result);
                    this.handleFacialResult(result);
                } else {
                    const errorText = await response.text();
                    console.error(`HTTP ${response.status}:`, errorText);
                    throw new Error(`Error del servicio: ${response.status} - ${errorText}`);
                }

            } catch (error) {
                console.error('Error en detección facial:', error);
                this.attempts.face++;

                if (this.attempts.face >= (this.config?.face?.maxAttempts || 3)) {
                    this.showError('Error de verificación facial', 'No se pudo completar la verificación facial después de varios intentos.', [
                        'Verifique que el servicio Python esté ejecutándose',
                        'Asegúrese de que el empleado tenga datos faciales registrados',
                        'Intente con mejor iluminación',
                        'Contacte al administrador del sistema'
                    ]);
                    this.stopFaceVerification();
                }
            }

            if (this.isVerifying.face) {
                setTimeout(detectFrame, 2000); // Verificar cada 2 segundos
            }
        };

        detectFrame();
    }

    handleFacialResult(result) {
        const confidencePercent = (result.confidence * 100).toFixed(1);
        const confidenceThreshold = this.config?.face?.confidenceThreshold || 0.85;

        if (result.success && result.confidence >= confidenceThreshold) {
            this.verificationResults.face = result;
            this.faceVerificationSuccessful = true; // Marcar como exitosa para mantener cámara abierta
            this.updateFaceStatus(`Verificación exitosa - ${result.employee_name || 'Empleado identificado'}`, 'success');
            this.showFaceResult('success', 'Verificación Exitosa', `Empleado identificado: ${result.employee_name || 'Empleado'} (Confianza: ${confidencePercent}%)`);

            // Capturar foto inmediatamente (mantener cámara abierta)
            this.capturePhotoForVerification().then(() => {
                // NO detener la cámara aquí - mantenerla abierta hasta cerrar modal
                this.isVerifying.face = false; // Solo detener la verificación, no la cámara
                this.identificationMode = null; // Limpiar modo de identificación

                // Completar automáticamente la verificación cuando la verificación sea exitosa
                setTimeout(() => this.completeVerification(), 1500); // Pequeño delay para mostrar el resultado
            }).catch((error) => {
                console.error('Error al capturar foto:', error);
                // En caso de error, detener todo
                this.stopFaceVerification();
                // Completar de todos modos
                setTimeout(() => this.completeVerification(), 1500);
            });
        } else {
            // Determinar el tipo de error y proporcionar mensaje específico con recomendaciones
            let errorTitle = 'Verificación Fallida';
            let errorMessage = '';
            let recommendations = [];

            if (result.message && result.message.includes('no reconocido')) {
                errorTitle = 'Rostro No Reconocido';
                errorMessage = 'No se pudo identificar al empleado con el rostro capturado.';
                recommendations = [
                    'Asegúrese de estar registrado en el sistema biométrico',
                    'Verifique que su rostro esté bien iluminado',
                    'Quite gafas oscuras o sombreros si los usa',
                    'Mantenga una expresión facial neutral',
                    'Intente desde un ángulo diferente si es necesario'
                ];
            } else if (result.message && result.message.includes('múltiples')) {
                errorTitle = 'Múltiples Rostros Detectados';
                errorMessage = 'Se detectaron varios rostros en la imagen. Solo debe aparecer una persona.';
                recommendations = [
                    'Asegúrese de que solo aparezca su rostro en la cámara',
                    'Aleje a otras personas del campo de visión',
                    'Posiciónese correctamente frente a la cámara',
                    'Evite reflejos o imágenes en segundo plano'
                ];
            } else if (result.confidence !== undefined && result.confidence < confidenceThreshold) {
                errorTitle = 'Confianza Insuficiente';
                errorMessage = `La confianza de verificación es del ${confidencePercent}% (mínimo requerido: ${(confidenceThreshold * 100).toFixed(1)}%).`;
                recommendations = [
                    'Mejore la iluminación del rostro',
                    'Asegúrese de que su rostro esté completamente visible',
                    'Quite gafas oscuras o accesorios que cubran el rostro',
                    'Manténgase quieto durante la captura',
                    'Intente desde una distancia adecuada de la cámara'
                ];
            } else {
                // Error genérico
                errorMessage = result.message || `Confianza insuficiente: ${confidencePercent}% (Mínimo requerido: ${(confidenceThreshold * 100).toFixed(1)}%)`;
                recommendations = [
                    'Verifique la iluminación y posición del rostro',
                    'Asegúrese de estar registrado en el sistema',
                    'Contacte al administrador si el problema persiste'
                ];
            }

            this.updateFaceStatus(`${errorTitle} - ${errorMessage}`, 'danger');
            this.showFaceResult('danger', errorTitle, errorMessage);

            // Mostrar recomendaciones en el modal de error si es un error crítico
            if (this.attempts.face >= (this.config?.face?.maxAttempts || 3)) {
                this.showError(errorTitle, errorMessage, recommendations);
            }
        }
    }

    updateFaceStatus(message, type) {
        const statusElement = document.getElementById('face-verification-status-text');
        if (statusElement) {
            statusElement.textContent = message;
            const alertElement = statusElement.closest('.alert');
            if (alertElement) {
                alertElement.className = `alert alert-${type}`;
            }
        }
    }

    showFaceResult(type, title, message) {
        const resultDiv = document.getElementById('face-verification-result');
        const alertDiv = document.getElementById('face-result-alert');
        const titleDiv = document.getElementById('face-result-title');
        const messageDiv = document.getElementById('face-result-message');

        if (resultDiv && alertDiv && titleDiv && messageDiv) {
            alertDiv.className = `alert alert-${type}`;
            titleDiv.textContent = title;
            messageDiv.textContent = message;
            resultDiv.style.display = 'block';

            // Animación
            resultDiv.classList.add(type === 'success' ? 'verification-success' : 'verification-error');
            setTimeout(() => {
                resultDiv.classList.remove('verification-success', 'verification-error');
            }, 500);
        }
    }

    // === VERIFICACIÓN DE HUELLA ===
    async startFingerprintVerification() {
        try {
            this.isVerifying.fingerprint = true;
            this.updateFingerprintStatus('Iniciando escáner...', 'info');

        // Mostrar animación de carga
        const animationDiv = document.getElementById('fingerprintVerificationAnimation');
        if (animationDiv) {
            animationDiv.style.display = 'block';
        }

            // Aquí iría la lógica para conectar con el escáner de huellas
            // Por ahora simulamos la conexión
            setTimeout(() => {
                this.updateFingerprintStatus('Escáner listo. Coloque el dedo.', 'success');
                this.enableFingerprintControls(true);
            }, 2000);

        } catch (error) {
            this.isVerifying.fingerprint = false;
            this.showError('Error de escáner', 'No se pudo conectar con el escáner de huellas.', [
                'Verifique que el escáner esté conectado y encendido',
                'Instale los drivers del dispositivo',
                'Reinicie el escáner y vuelva a intentar',
                'Contacte al soporte técnico'
            ], error);
        }
    }

    stopFingerprintVerification() {
        this.isVerifying.fingerprint = false;
        const animationDiv = document.getElementById('fingerprintVerificationAnimation');
        if (animationDiv) {
            animationDiv.style.display = 'none';
        }
        this.updateFingerprintStatus('Verificación detenida', 'secondary');
        this.enableFingerprintControls(false);
    }

    enableFingerprintControls(enabled) {
        const startBtn = document.getElementById('startFingerprintVerification');
        if (startBtn) startBtn.disabled = enabled;

        const stopBtn = document.getElementById('stopFingerprintVerification');
        if (stopBtn) stopBtn.disabled = !enabled;

        const verifyBtn = document.getElementById('verifyFingerprintNow');
        if (verifyBtn) verifyBtn.disabled = !enabled;
    }

    async verifyFingerprintNow() {
        try {
            this.updateFingerprintStatus('Verificando huella...', 'info');

            // Simular verificación de huella (reemplazar con lógica real)
            const response = await fetch('/api/verify_fingerprint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee,
                    fingerprint_data: 'simulated_fingerprint_data'
                })
            });

            if (response.ok) {
                const result = await response.json();
                this.handleFingerprintResult(result);
            } else {
                throw new Error('Error en verificación de huella');
            }

        } catch (error) {
            this.showError('Error en verificación de huella', 'No se pudo verificar la huella dactilar.', [
                'Asegúrese de colocar el dedo correctamente en el escáner',
                'Limpie el dedo y el escáner antes de intentar nuevamente',
                'Verifique que el escáner esté calibrado',
                'Intente con otro dedo si está disponible'
            ], error);
        }
    }

    handleFingerprintResult(result) {
        if (result.verified) {
            this.verificationResults.fingerprint = result;
            this.updateFingerprintStatus('Verificación exitosa', 'success');
            this.showFingerprintResult('success', 'Huella Verificada', `Empleado identificado correctamente (Confianza: ${(result.confidence * 100).toFixed(1)}%)`);
            this.stopFingerprintVerification();
            // Completar automáticamente la verificación cuando la verificación sea exitosa
            setTimeout(() => this.completeVerification(), 1500); // Pequeño delay para mostrar el resultado
        } else {
            this.updateFingerprintStatus('Huella no reconocida', 'danger');
            this.showFingerprintResult('danger', 'Huella No Reconocida', 'La huella no coincide con los registros del empleado.');
        }
    }

    updateFingerprintStatus(message, type) {
        const statusElement = document.getElementById('fingerprint-verification-status-text');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `text-${type}`;
        }
    }

    showFingerprintResult(type, title, message) {
        const resultDiv = document.getElementById('fingerprint-verification-result');
        const alertDiv = document.getElementById('fingerprint-result-alert');
        const titleDiv = document.getElementById('fingerprint-result-title');
        const messageDiv = document.getElementById('fingerprint-result-message');

        if (resultDiv && alertDiv && titleDiv && messageDiv) {
            alertDiv.className = `alert alert-${type}`;
            titleDiv.textContent = title;
            messageDiv.textContent = message;
            resultDiv.style.display = 'block';
        }
    }

    // === VERIFICACIÓN RFID ===
    async startRfidVerification() {
        try {
            this.isVerifying.rfid = true;
            this.updateRfidStatus('Iniciando lector RFID...', 'info');

            // Mostrar animación de carga
            const animationDiv = document.getElementById('rfidVerificationAnimation');
            if (animationDiv) {
                animationDiv.style.display = 'block';
            }

            // Simular conexión con lector RFID
            setTimeout(() => {
                this.updateRfidStatus('Lector listo. Acerque el carné.', 'success');
                this.enableRfidControls(true);
                this.startRfidDetection();
            }, 2000);

        } catch (error) {
            this.isVerifying.rfid = false;
            this.showError('Error de lector RFID', 'No se pudo conectar con el lector RFID.', [
                'Verifique que el lector esté conectado y encendido',
                'Instale los drivers del dispositivo RFID',
                'Asegúrese de que el puerto USB esté funcionando',
                'Contacte al soporte técnico'
            ], error);
        }
    }

    stopRfidVerification() {
        this.isVerifying.rfid = false;
        const animationDiv = document.getElementById('rfidVerificationAnimation');
        if (animationDiv) {
            animationDiv.style.display = 'none';
        }
        this.updateRfidStatus('Verificación detenida', 'secondary');
        this.enableRfidControls(false);
    }

    enableRfidControls(enabled) {
        const startBtn = document.getElementById('startRfidVerification');
        if (startBtn) startBtn.disabled = enabled;

        const stopBtn = document.getElementById('stopRfidVerification');
        if (stopBtn) stopBtn.disabled = !enabled;

        const verifyBtn = document.getElementById('verifyRfidNow');
        if (verifyBtn) verifyBtn.disabled = !enabled;
    }

    startRfidDetection() {
        // Simular detección continua de RFID
        const detectRfid = async () => {
            if (!this.isVerifying.rfid) return;

            try {
                // Simular lectura RFID (reemplazar con lógica real)
                const mockUid = 'A1B2C3D4E5F6'; // UID simulado
                document.getElementById('rfid-verification-uid').textContent = mockUid;
                document.getElementById('rfid-verification-type').textContent = 'MIFARE Classic';
                this.updateRfidStatus('UID detectado. Verificando...', 'info');

                // Verificar UID
                const response = await fetch('/api/verify_rfid.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_id: this.selectedEmployee,
                        rfid_uid: mockUid
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    this.handleRfidResult(result);
                } else {
                    throw new Error('Error en verificación RFID');
                }

            } catch (error) {
                console.error('Error en detección RFID:', error);
                this.attempts.rfid++;

                if (this.attempts.rfid >= 3) {
                    this.showError('Error en verificación RFID', 'No se pudo completar la verificación RFID.', [
                        'Asegúrese de que el carné esté cerca del lector',
                        'Verifique que el carné no esté dañado',
                        'Intente desde diferentes ángulos',
                        'Contacte al administrador si el carné necesita reprogramación'
                    ], error);
                    this.stopRfidVerification();
                }
            }

            if (this.isVerifying.rfid) {
                setTimeout(detectRfid, 2000); // Verificar cada 2 segundos
            }
        };

        detectRfid();
    }

    async verifyRfidNow() {
        const uid = document.getElementById('rfid-verification-uid').textContent;
        if (uid === '-') {
            this.showError('Error', 'No se ha detectado ningún UID.', ['Acerque el carné al lector primero']);
            return;
        }

        try {
            this.updateRfidStatus('Verificando UID...', 'info');

            const response = await fetch('/api/verify_rfid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee,
                    rfid_uid: uid
                })
            });

            if (response.ok) {
                const result = await response.json();
                this.handleRfidResult(result);
            } else {
                throw new Error('Error en verificación RFID');
            }

        } catch (error) {
            this.showError('Error en verificación RFID', 'No se pudo verificar el UID.', [
                'Verifique que el UID sea correcto',
                'Asegúrese de que el empleado tenga RFID registrado',
                'Contacte al administrador del sistema'
            ], error);
        }
    }

    handleRfidResult(result) {
        if (result.verified) {
            this.verificationResults.rfid = result;
            this.updateRfidStatus('Verificación exitosa', 'success');
            this.showRfidResult('success', 'RFID Verificado', `Carné identificado correctamente (UID: ${result.uid})`);
            this.stopRfidVerification();
            // Completar automáticamente la verificación cuando la verificación sea exitosa
            setTimeout(() => this.completeVerification(), 1500); // Pequeño delay para mostrar el resultado
        } else {
            this.updateRfidStatus('UID no reconocido', 'danger');
            this.showRfidResult('danger', 'RFID No Reconocido', 'El UID no coincide con los registros del empleado.');
        }
    }

    updateRfidStatus(message, type) {
        const statusElement = document.getElementById('rfid-verification-status-text');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `text-${type}`;
        }
    }

    showRfidResult(type, title, message) {
        const resultDiv = document.getElementById('rfid-verification-result');
        const alertDiv = document.getElementById('rfid-result-alert');
        const titleDiv = document.getElementById('rfid-result-title');
        const messageDiv = document.getElementById('rfid-result-message');

        if (resultDiv && alertDiv && titleDiv && messageDiv) {
            alertDiv.className = `alert alert-${type}`;
            titleDiv.textContent = title;
            messageDiv.textContent = message;
            resultDiv.style.display = 'block';
        }
    }

    enableVerificationControls(method) {
        // Deshabilitar todos los botones de inicio primero
        const startFaceBtn = document.getElementById('startFaceVerification');
        if (startFaceBtn) startFaceBtn.disabled = true;

        const startFingerprintBtn = document.getElementById('startFingerprintVerification');
        if (startFingerprintBtn) startFingerprintBtn.disabled = true;

        const startRfidBtn = document.getElementById('startRfidVerification');
        if (startRfidBtn) startRfidBtn.disabled = true;

        // Habilitar solo el botón del método seleccionado si está disponible
        switch (method) {
            case 'face':
                if (this.employeeBiometrics.face && this.deviceStatus.face.available && startFaceBtn) {
                    startFaceBtn.disabled = false;
                }
                break;
            case 'fingerprint':
                if (this.employeeBiometrics.fingerprint && this.deviceStatus.fingerprint.available && startFingerprintBtn) {
                    startFingerprintBtn.disabled = false;
                }
                break;
            case 'rfid':
                if (this.employeeBiometrics.rfid && this.deviceStatus.rfid.available && startRfidBtn) {
                    startRfidBtn.disabled = false;
                }
                break;
        }
    }
    stopAllVerification() {
        this.stopFaceVerification();
        this.stopFingerprintVerification();
        this.stopRfidVerification();
    }

    ensureCameraStop() {
        // Forzar detención completa de todos los streams de video
        try {
            // Detener cualquier stream de video activo
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(track => {
                    track.stop();
                    console.log('Camera track stopped:', track.kind);
                });
                this.videoStream = null;
            }

            // Limpiar el video element
            const video = document.getElementById('faceVerificationVideo');
            if (video) {
                video.srcObject = null;
                video.load(); // Forzar limpieza del video
            }

            // Verificar otros posibles streams activos
            navigator.mediaDevices.getUserMedia({video: true}).then(stream => {
                // Si hay stream activo, detenerlo
                stream.getTracks().forEach(track => track.stop());
            }).catch(() => {
                // Es normal que falle si no hay stream activo
            });

            console.log('Camera cleanup completed');
        } catch (error) {
            console.error('Error stopping camera:', error);
        }
    }

    async ensureCameraActiveForCapture(video, canvas) {
        console.log('🔄 Verificando estado de la cámara para captura...');

        try {
            // Verificar si la cámara ya está activa
            if (video.srcObject && video.srcObject.active && video.videoWidth > 0 && video.videoHeight > 0) {
                console.log('✅ Cámara ya está activa y lista');
                return true;
            }

            console.log('⚠️ Cámara no está activa, reactivando...');

            // Si no hay stream guardado, intentar obtener uno nuevo
            if (!this.videoStream) {
                console.log('📹 Solicitando nuevo stream de video...');
                this.videoStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }
                });
            }

            // Asignar el stream al video element
            video.srcObject = this.videoStream;

            // Esperar a que el video esté listo
            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject(new Error('Timeout esperando video'));
                }, 5000);

                video.onloadedmetadata = () => {
                    clearTimeout(timeout);
                    console.log('📹 Video metadata loaded, dimensions:', video.videoWidth, 'x', video.videoHeight);
                    resolve();
                };

                video.oncanplay = () => {
                    clearTimeout(timeout);
                    console.log('📹 Video can play, listo para captura');
                    resolve();
                };

                video.onerror = () => {
                    clearTimeout(timeout);
                    reject(new Error('Error cargando video'));
                };
            });

            console.log('✅ Cámara reactivada exitosamente para captura');
            return true;

        } catch (error) {
            console.error('❌ Error reactivando cámara para captura:', error);
            return false;
        }
    }

    resetModal() {
        // Limpiar completamente el estado del modal
        this.selectedEmployee = null;
        this.employeeData = null;
        this.isConfirmingAttendance = false; // Resetear flag de confirmación
        this.faceVerificationSuccessful = false; // Resetear flag de verificación exitosa
        this.currentTab = null; // No seleccionar ninguna pestaña por defecto
        this.employeeBiometrics = {
            face: false,
            fingerprint: false,
            rfid: false
        };
        this.biometricsLoaded = false;
        this.deviceStatus = {
            face: { available: false, status: 'unknown' },
            fingerprint: { available: false, status: 'unknown' },
            rfid: { available: false, status: 'unknown' }
        };
        this.verificationResults = {
            face: null,
            fingerprint: null,
            rfid: null
        };
        this.attempts = {
            face: 0,
            fingerprint: 0,
            rfid: 0
        };
        this.isVerifying = {
            face: false,
            fingerprint: false,
            rfid: false
        };

        // Resetear UI completamente
        document.querySelectorAll('.badge').forEach(badge => {
            badge.className = 'badge bg-secondary';
            badge.textContent = 'Pendiente';
        });

        document.querySelectorAll('.verification-result').forEach(el => {
            if (el) el.style.display = 'none';
        });

        // Limpiar mensajes de estado
        document.querySelectorAll('.text-success, .text-danger, .text-warning, .text-info').forEach(el => {
            if (el) el.className = el.className.replace(/text-(success|danger|warning|info)/g, 'text-muted');
        });

        // Limpiar campos de texto
        document.querySelectorAll('input[type="text"], input[type="number"], textarea').forEach(input => {
            if (input && input.id && input.id.includes('verification')) {
                input.value = '';
            }
        });

        // Resetear elementos específicos si existen
        const completeVerificationBtn = document.getElementById('completeVerification');
        if (completeVerificationBtn) {
            completeVerificationBtn.disabled = true;
        }

        // Deshabilitar todos los botones de inicio de verificación si existen
        const startFaceBtn = document.getElementById('startFaceVerification');
        if (startFaceBtn) {
            startFaceBtn.disabled = true;
        }

        const startFingerprintBtn = document.getElementById('startFingerprintVerification');
        if (startFingerprintBtn) {
            startFingerprintBtn.disabled = true;
        }

        const startRfidBtn = document.getElementById('startRfidVerification');
        if (startRfidBtn) {
            startRfidBtn.disabled = true;
        }

        // Limpiar mensajes de estado de verificación
        const statusMessage = document.getElementById('biometric-method-selection-message');
        if (statusMessage) {
            statusMessage.innerHTML = `
                <i class="fas fa-info-circle"></i> <strong>Seleccione un método de verificación</strong>
                <br>
                <small>Cargando información del empleado...</small>
            `;
        }

        // Limpiar información del empleado
        const employeeElements = [
            'verification-display-employee-id',
            'verification-employee-code',
            'verification-employee-name',
            'verification-employee-establishment',
            'verification-type-display'
        ];

        employeeElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = '';
            }
        });

        // Ocultar todas las pestañas inicialmente
        const tabs = document.querySelectorAll('#verificationTabs .nav-link');
        tabs.forEach(tab => {
            if (tab) {
                tab.classList.remove('active');
                tab.style.display = 'none'; // Ocultar todas las pestañas
            }
        });

        // Ocultar todos los paneles de contenido
        const panels = document.querySelectorAll('.tab-pane');
        panels.forEach(panel => {
            if (panel) {
                panel.classList.remove('show', 'active');
            }
        });

        // Limpiar foto capturada y contenedor
        const capturedPhotoPreview = document.getElementById('captured-photo-preview');
        if (capturedPhotoPreview) {
            capturedPhotoPreview.src = '';
            capturedPhotoPreview.alt = 'Foto no capturada';
        }

        const capturedPhotoContainer = document.getElementById('captured-photo-container');
        if (capturedPhotoContainer) {
            capturedPhotoContainer.style.display = 'none';
        }

        // Limpiar propiedad de foto capturada
        this.capturedVerificationPhoto = null;

        console.log('Modal completamente reseteado y limpio');
    }

    checkVerificationComplete() {
        const hasAnyVerification = Object.values(this.verificationResults).some(result => result !== null);
        const completeBtn = document.getElementById('completeVerification');
        if (completeBtn) {
            completeBtn.disabled = !hasAnyVerification;
        }
    }

    async completeVerification() {
        try {
            if (!this.selectedEmployee) {
                throw new Error('No se ha seleccionado un empleado para registrar asistencia');
            }

            const attendanceType = document.getElementById('verification-attendance-type').value || 'ENTRADA';
            const today = window.Bogota?.getDateString ? window.Bogota.getDateString() : new Date().toISOString().slice(0, 10);
            const timestamp = window.Bogota?.getISOString ? window.Bogota.getISOString() : new Date().toISOString();

            console.log('Completando verificación para empleado:', this.selectedEmployee, 'tipo:', attendanceType, 'fecha:', today);
            this.showLoading('Registrando asistencia con validaciones...');

            let horarioInfo = null;
            try {
                const horarioResponse = await fetch(`api/check-employee-schedule.php?empleado_id=${this.selectedEmployee}&fecha=${today}&tipo=${attendanceType}`);
                if (horarioResponse.ok) {
                    const horarioData = await horarioResponse.json();
                    console.log('Respuesta del API de horario:', horarioData);

                    if (horarioData.success) {
                        horarioInfo = horarioData.horario || null;

                        if (horarioData.puede_registrar === false) {
                            const scheduleError = new Error(horarioData.message || 'No se puede registrar asistencia en este momento');
                            scheduleError.recommendations = horarioData.recommendations || [];
                            throw scheduleError;
                        }
                    } else if (attendanceType !== 'SALIDA') {
                        const scheduleError = new Error(horarioData.message || 'Error al verificar el horario del empleado');
                        scheduleError.recommendations = horarioData.recommendations || [];
                        throw scheduleError;
                    }
                } else if (attendanceType !== 'SALIDA') {
                    throw new Error('Error al consultar el horario del empleado');
                }
            } catch (horarioError) {
                console.error('Error al obtener horario:', horarioError);
                if (attendanceType !== 'SALIDA') {
                    throw horarioError;
                }
                console.warn('Continuando con registro de salida sin horario específico');
            }

            const numericEmployeeId = Number.parseInt(this.selectedEmployee, 10);
            const employeeId = Number.isNaN(numericEmployeeId) ? this.selectedEmployee : numericEmployeeId;

            const verificationResults = this.buildVerificationResultsPayload(horarioInfo, timestamp) || {};
            const maxConfidence = this.getMaxConfidenceScore();

            if (!Number.isNaN(maxConfidence) && maxConfidence > 0) {
                verificationResults.confidence_score = Math.round(maxConfidence * 100);
                verificationResults.verification_success = true;
            }

            const attendancePayload = {
                employee_id: employeeId,
                type: attendanceType,
                verification_method: this.getVerificationMethod(),
                verification_results: verificationResults,
                timestamp
            };

            if (this.capturedVerificationPhoto) {
                attendancePayload.photo_data = `data:image/jpeg;base64,${this.capturedVerificationPhoto}`;
            }

            if (horarioInfo) {
                attendancePayload.schedule_hint = horarioInfo;
            }

            console.log('Enviando payload a api/attendance/register-biometric-enhanced.php:', attendancePayload);

            const response = await fetch('api/attendance/register-biometric-enhanced.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(attendancePayload)
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error HTTP en registro:', response.status, errorText);
                throw new Error(`Error del servidor (${response.status})`);
            }

            const result = await response.json();
            console.log('Respuesta del registro:', result);

            if (!result.success) {
                const apiError = new Error(result.message || 'No se pudo registrar la asistencia');
                apiError.recommendations = result.recommendations || [];
                throw apiError;
            }

            const responseData = result.data || {};
            const horarioDescripcion = responseData.horario_info
                ? `${responseData.horario_info.nombre_turno || 'Turno'}${responseData.horario_info.orden_turno ? ` (Orden ${responseData.horario_info.orden_turno})` : ''}`
                : null;

            showMessage(`✅ ${result.message || 'Asistencia registrada exitosamente'}`, 'success', 6000);

            this.showVerificationConfirmation({
                employee_name: responseData.employee_name,
                type: responseData.type,
                date: responseData.date,
                time: responseData.time,
                timestamp: responseData.created_at || result.timestamp,
                schedule: horarioDescripcion,
                horario_info: responseData.horario_info,
                photo_url: responseData.photo?.url || responseData.photo_url,
                photo_filename: responseData.photo?.filename || responseData.photo_filename,
                photo_path: responseData.photo?.url || responseData.photo_path
            });

            this.refreshAttendanceData();

        } catch (error) {
            console.error('Error al completar verificación:', error);
            const recommendations = Array.isArray(error.recommendations) && error.recommendations.length > 0
                ? error.recommendations
                : [
                    'Verifique que el empleado tenga horarios personalizados asignados',
                    'Asegúrese de que el registro anterior no se haya completado',
                    'Confirme que el servicio biométrico esté activo',
                    'Intente nuevamente en unos minutos'
                ];

            this.showError(
                'Error al completar verificación',
                error.message || 'No se pudo registrar la asistencia.',
                recommendations,
                error
            );
        } finally {
            this.hideLoading();
            this.capturedVerificationPhoto = null;
        }
    }

    buildVerificationResultsPayload(horarioInfo = null, timestamp = null) {
        const payload = {};
        const cloneData = (data) => {
            if (!data) return null;
            if (typeof structuredClone === 'function') {
                try {
                    return structuredClone(data);
                } catch (error) {
                    console.warn('structuredClone falló, usando fallback para datos de verificación:', error);
                }
            }

            try {
                return JSON.parse(JSON.stringify(data));
            } catch (error) {
                console.warn('No se pudo clonar completamente los datos de verificación, aplicando sanitización básica', error);
                const sanitized = {};
                Object.entries(data).forEach(([key, value]) => {
                    if (value === null || value === undefined) {
                        sanitized[key] = value;
                    } else if (['string', 'number', 'boolean'].includes(typeof value)) {
                        sanitized[key] = value;
                    }
                });
                return sanitized;
            }
        };

        const faceData = cloneData(this.verificationResults.face);
        if (faceData) {
            if (this.capturedVerificationPhoto && !faceData.photo) {
                faceData.photo = this.capturedVerificationPhoto;
            }
            faceData.method = faceData.method || 'facial';
            payload.face = faceData;
        }

        const fingerprintData = cloneData(this.verificationResults.fingerprint);
        if (fingerprintData) {
            fingerprintData.method = fingerprintData.method || 'fingerprint';
            payload.fingerprint = fingerprintData;
        }

        const rfidData = cloneData(this.verificationResults.rfid);
        if (rfidData) {
            rfidData.method = rfidData.method || 'rfid';
            payload.rfid = rfidData;
        }

        if (horarioInfo) {
            payload.schedule = horarioInfo;
        }

        payload.metadata = {
            source: 'manual_verification_modal',
            generated_at: timestamp || (window.Bogota?.getISOString ? window.Bogota.getISOString() : new Date().toISOString())
        };

        return payload;
    }

    // Capturar foto usando el servicio de Python
    async capturePhotoWithPython() {
        try {
            console.log('Intentando capturar foto con servicio de Python...');

            const response = await fetch('http://localhost:8001/attendance/capture-photo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.warn('Servicio de Python no disponible:', errorText);
                return null;
            }

            const result = await response.json();
            console.log('Foto capturada por Python:', result);

            if (result.success && result.filename) {
                return result;
            } else {
                console.warn('Python capturó foto pero sin éxito:', result.message);
                return null;
            }

        } catch (error) {
            console.warn('Error al capturar foto con Python, usando fallback:', error);
            return null;
        }
    }

    // Capturar foto del canvas actual para verificación (cuando la cámara está abierta)
    async capturePhotoForVerification() {
        try {
            console.log('Capturando foto de verificación desde canvas...');

            const video = document.getElementById('faceVerificationVideo');
            const canvas = document.getElementById('faceVerificationCanvas');

            if (!video || !canvas) {
                console.warn('Elementos de video o canvas no encontrados');
                return false;
            }

            // Verificar si la cámara está activa (tiene stream)
            if (!video.srcObject) {
                console.log('Cámara no está activa, intentando activarla...');

                try {
                    // Solicitar acceso a la cámara
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: 640,
                            height: 480,
                            facingMode: 'user'
                        }
                    });

                    video.srcObject = stream;
                    this.videoStream = stream;

                    // Esperar a que el video esté listo
                    await new Promise((resolve, reject) => {
                        const onLoadedMetadata = () => {
                            video.removeEventListener('loadedmetadata', onLoadedMetadata);
                            video.removeEventListener('error', onError);
                            resolve();
                        };

                        const onError = (error) => {
                            video.removeEventListener('loadedmetadata', onLoadedMetadata);
                            video.removeEventListener('error', onError);
                            reject(error);
                        };

                        video.addEventListener('loadedmetadata', onLoadedMetadata);
                        video.addEventListener('error', onError);

                        // Timeout de 5 segundos
                        setTimeout(() => {
                            video.removeEventListener('loadedmetadata', onLoadedMetadata);
                            video.removeEventListener('error', onError);
                            reject(new Error('Timeout esperando que el video se cargue'));
                        }, 5000);
                    });

                    // Iniciar reproducción del video
                    await video.play();

                    console.log('Cámara activada exitosamente para captura de foto');

                } catch (cameraError) {
                    console.error('Error al activar la cámara para captura:', cameraError);
                    return false;
                }
            }

            // Verificar que el video esté listo y tenga dimensiones válidas
            if (video.videoWidth === 0 || video.videoHeight === 0) {
                console.warn('Video no tiene dimensiones válidas después de activar cámara');
                return false;
            }

            // Verificar que el video esté reproduciéndose
            if (video.paused || video.ended) {
                console.warn('Video no está reproduciéndose');
                try {
                    await video.play();
                    // Pequeña pausa para asegurar que el video esté listo
                    await new Promise(resolve => setTimeout(resolve, 200));
                } catch (playError) {
                    console.error('Error al reproducir video:', playError);
                    return false;
                }
            }

            console.log('Estado del video antes de captura:', {
                videoWidth: video.videoWidth,
                videoHeight: video.videoHeight,
                paused: video.paused,
                ended: video.ended,
                readyState: video.readyState,
                srcObject: !!video.srcObject
            });

            // Configurar canvas con dimensiones del video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Obtener contexto 2D y verificar que esté disponible
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('No se pudo obtener el contexto 2D del canvas');
                return false;
            }

            // Limpiar el canvas completamente
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Pequeña pausa para asegurar que el canvas esté listo
            await new Promise(resolve => setTimeout(resolve, 100));

            // Verificar nuevamente que el video sigue activo después de la pausa
            if (!video.srcObject || video.videoWidth === 0 || video.videoHeight === 0) {
                console.warn('Video se desactivó durante la preparación del canvas');
                return false;
            }

            console.log('Dibujando video en canvas:', {
                canvasWidth: canvas.width,
                canvasHeight: canvas.height,
                videoReadyState: video.readyState,
                videoCurrentTime: video.currentTime
            });

            // Dibujar el frame actual del video en el canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Verificar que el canvas tenga contenido (no esté vacío)
            const imageDataCheck = ctx.getImageData(0, 0, 1, 1);
            if (!imageDataCheck || imageDataCheck.data.every(pixel => pixel === 0)) {
                console.warn('Canvas parece estar vacío después de dibujar');
                // Intentar dibujar nuevamente con una pequeña pausa
                await new Promise(resolve => setTimeout(resolve, 200));
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            }

            // Convertir a base64 (método simple sin compresión)
            const imageData = canvas.toDataURL('image/jpeg', 0.95); // Calidad aún más alta
            const base64Data = imageData.split(',')[1];

            console.log('Datos de imagen generados:', {
                dataURLLength: imageData.length,
                base64Length: base64Data ? base64Data.length : 0,
                canvasDimensions: `${canvas.width}x${canvas.height}`
            });

            if (!base64Data || base64Data.length < 1000) {
                console.warn('Datos de imagen inválidos o demasiado pequeños:', {
                    hasBase64: !!base64Data,
                    length: base64Data ? base64Data.length : 0
                });
                return false;
            }

            // Guardar la foto capturada para usar en completeVerification
            this.capturedVerificationPhoto = base64Data;

            // Mostrar preview de la foto capturada
            const previewImg = document.getElementById('captured-photo-preview');
            const previewContainer = document.getElementById('captured-photo-container');

            if (previewImg && previewContainer) {
                previewImg.src = imageData;
                previewContainer.style.display = 'block';
            }

            console.log('Foto de verificación capturada exitosamente, tamaño:', base64Data.length);
            return true;

        } catch (error) {
            console.error('Error al capturar foto de verificación:', error);
            return false;
        }
    }

    // Capturar foto actual del video para el registro
    async captureCurrentPhoto() {
        try {
            console.log('Intentando capturar foto con JavaScript...');

            const video = document.getElementById('faceVerificationVideo');
            const canvas = document.getElementById('faceVerificationCanvas');

            if (!video || !canvas) {
                console.warn('Elementos de video o canvas no encontrados');
                return null;
            }

            console.log('Estado del video:', {
                videoWidth: video.videoWidth,
                videoHeight: video.videoHeight,
                paused: video.paused,
                ended: video.ended,
                readyState: video.readyState,
                srcObject: !!video.srcObject
            });

            // Si el video no tiene dimensiones, intentar reinicializarlo
            if (video.videoWidth === 0 || video.videoHeight === 0) {
                console.log('Video no tiene dimensiones, intentando reinicializar...');

                // Intentar reiniciar el stream de video
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: 640, height: 480, facingMode: 'user' }
                    });
                    video.srcObject = stream;

                    // Esperar a que el video se cargue
                    await new Promise((resolve) => {
                        const onLoadedMetadata = () => {
                            video.removeEventListener('loadedmetadata', onLoadedMetadata);
                            resolve();
                        };
                        video.addEventListener('loadedmetadata', onLoadedMetadata);

                        // Timeout de 3 segundos
                        setTimeout(() => {
                            video.removeEventListener('loadedmetadata', onLoadedMetadata);
                            resolve();
                        }, 3000);
                    });

                    console.log('Video reinicializado:', {
                        videoWidth: video.videoWidth,
                        videoHeight: video.videoHeight,
                        paused: video.paused,
                        ended: video.ended
                    });
                } catch (streamError) {
                    console.error('Error al reinicializar stream de video:', streamError);
                    return null;
                }
            }

            // Verificar que el video esté listo
            if (video.videoWidth === 0 || video.videoHeight === 0) {
                console.warn('Video no se pudo inicializar correctamente');
                return null;
            }

            // Verificar que el video esté reproduciendo
            if (video.paused || video.ended) {
                console.log('Video está pausado, intentando reproducir...');
                try {
                    await video.play();
                    // Esperar un poco para que el video se estabilice
                    await new Promise(resolve => setTimeout(resolve, 500));
                } catch (playError) {
                    console.error('Error al reproducir video:', playError);
                    return null;
                }
            }

            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            console.log('Capturando frame del video:', {
                canvasWidth: canvas.width,
                canvasHeight: canvas.height
            });

            // Dibujar frame actual del video en el canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Usar la nueva función simple para obtener datos
            const imageData = this.capturePhoto(video, canvas);

            console.log('Foto capturada exitosamente con método simple, tamaño aproximado:', imageData ? imageData.length : 0);
            return imageData;

        } catch (error) {
            console.error('Error al capturar foto con JavaScript:', error);
            return null;
        }
    }

    // Obtener el método de verificación principal
    getVerificationMethod() {
        if (this.verificationResults.face && this.verificationResults.face.success) {
            return 'facial';
        } else if (this.verificationResults.fingerprint && this.verificationResults.fingerprint.verified) {
            return 'fingerprint';
        } else if (this.verificationResults.rfid && this.verificationResults.rfid.verified) {
            return 'rfid';
        }
        return 'biometric'; // Método por defecto si no hay verificación específica
    }

    // Obtener el puntaje de confianza más alto
    getMaxConfidenceScore() {
        let maxScore = 0;

        if (this.verificationResults.face && this.verificationResults.face.confidence) {
            maxScore = Math.max(maxScore, this.verificationResults.face.confidence);
        }

        if (this.verificationResults.fingerprint && this.verificationResults.fingerprint.confidence) {
            maxScore = Math.max(maxScore, this.verificationResults.fingerprint.confidence);
        }

        if (this.verificationResults.rfid && this.verificationResults.rfid.confidence) {
            maxScore = Math.max(maxScore, this.verificationResults.rfid.confidence);
        }

        return maxScore;
    }

    showVerificationConfirmation(result) {
        // Usar datos del empleado desde la respuesta de la API o desde this.employeeData
        const employeeName = result.employee_name ||
                           (this.employeeData ? `${this.employeeData.NOMBRE || ''} ${this.employeeData.APELLIDO || ''}`.trim() : 'Sin nombre');

        const attendanceType = result.attendance_type || result.type || 'No especificado';

        let displayTime = window.Bogota.getDateTimeString();
        if (result.timestamp) {
            const tsDate = new Date(result.timestamp);
            if (!Number.isNaN(tsDate.getTime())) {
                displayTime = tsDate.toLocaleString();
            }
        } else if (result.date && result.time) {
            const dateTimeString = `${result.date}T${result.time}`;
            const dateObj = new Date(dateTimeString);
            if (!Number.isNaN(dateObj.getTime())) {
                displayTime = dateObj.toLocaleString();
            }
        } else if (result.time) {
            const timeParts = String(result.time).split(':');
            if (timeParts.length === 3) {
                const now = window.Bogota.getBogotaDate();
                now.setHours(parseInt(timeParts[0], 10), parseInt(timeParts[1], 10), parseInt(timeParts[2], 10));
                displayTime = now.toLocaleString();
            }
        }

        const summaryDiv = document.getElementById('verificationSummary');
        summaryDiv.innerHTML = `
            <p><strong>Empleado:</strong> ${employeeName}</p>
            <p><strong>Tipo:</strong> ${attendanceType}</p>
            <p><strong>Hora:</strong> ${displayTime}</p>
            <p><strong>Método de verificación:</strong> ${this.getVerificationMethodsText()}</p>
            ${result.schedule ? `<p><strong>Horario:</strong> ${result.schedule}</p>` : ''}
        `;

        let rawPhotoUrl = result.photo_url || result.photo_path || (result.photo_filename ? `uploads/${result.photo_filename}` : null);
        if (!rawPhotoUrl && this.capturedVerificationPhoto) {
            rawPhotoUrl = `data:image/jpeg;base64,${this.capturedVerificationPhoto}`;
        }
        const normalizedPhotoUrl = this.normalizePhotoUrl(rawPhotoUrl);

        if (normalizedPhotoUrl) {
            const photoSection = document.createElement('div');
            photoSection.className = 'mt-3 text-center';

            const photoImg = document.createElement('img');
            photoImg.src = normalizedPhotoUrl;
            photoImg.alt = 'Foto registrada de asistencia';
            photoImg.className = 'img-fluid rounded shadow-sm';
            photoImg.style.maxHeight = '240px';

            const photoCaption = document.createElement('p');
            photoCaption.className = 'text-muted small mt-2 mb-0';
            photoCaption.textContent = 'Foto registrada durante la verificación biométrica';

            photoSection.appendChild(photoImg);
            photoSection.appendChild(photoCaption);
            summaryDiv.appendChild(photoSection);

            const previewImg = document.getElementById('captured-photo-preview');
            const previewContainer = document.getElementById('captured-photo-container');
            if (previewImg && previewContainer) {
                previewImg.src = normalizedPhotoUrl;
                previewImg.alt = 'Foto registrada de asistencia';
                previewContainer.style.display = 'block';
            }
        } else {
            const previewContainer = document.getElementById('captured-photo-container');
            if (previewContainer) {
                previewContainer.style.display = 'none';
            }
        }

        // Activar flag de confirmación para mantener la cámara encendida
        this.isConfirmingAttendance = true;

        // NO cerrar el modal de verificación aquí - mantenerlo abierto con la cámara
        // Solo abrir el modal de confirmación sobre el modal de verificación
        const confirmationModal = new bootstrap.Modal(document.getElementById('verificationConfirmationModal'), {
            backdrop: 'static', // Evitar cerrar haciendo clic fuera
            keyboard: false     // Evitar cerrar con teclado
        });
        confirmationModal.show();
    }

    getVerificationMethodsText() {
        const methods = [];
        if (this.verificationResults.face) methods.push('Facial');
        if (this.verificationResults.fingerprint) methods.push('Huella');
        if (this.verificationResults.rfid) methods.push('RFID');
        return methods.join(', ') || 'Ninguno';
    }

    normalizePhotoUrl(photoUrl) {
        if (!photoUrl || typeof photoUrl !== 'string') {
            return null;
        }

        const trimmed = photoUrl.trim();
        if (trimmed === '') {
            return null;
        }

        if (/^(data:image|https?:\/\/)/i.test(trimmed)) {
            return trimmed;
        }

        if (trimmed.startsWith('./') || trimmed.startsWith('../') || trimmed.startsWith('/')) {
            return trimmed;
        }

        return `./${trimmed.replace(/^\/+/, '')}`;
    }

    showError(title, message, recommendations = []) {
        const titleElement = document.getElementById('error-title');
        if (titleElement) titleElement.textContent = title;

        const messageElement = document.getElementById('error-message');
        if (messageElement) messageElement.textContent = message;

        const recommendationsList = document.getElementById('error-recommendation-list');
        if (recommendationsList) {
            recommendationsList.innerHTML = recommendations.map(rec => `<li>${rec}</li>`).join('');
        }

        const errorModalElement = document.getElementById('verificationErrorModal');
        if (errorModalElement) {
            const errorModal = new bootstrap.Modal(errorModalElement);
            errorModal.show();
        }
    }

    showRepositioningGuide(candidate, confidence) {
        const employeeName = candidate.NOMBRE_COMPLETO || candidate.full_name || 'Empleado';
        const employeeId = candidate.ID_EMPLEADO || candidate.employee_id || 0;
        
        console.log('📍 Showing repositioning guide for candidate:', employeeName);
        
        // Mostrar guía visual de reposicionamiento
        const resultDiv = document.getElementById('face-verification-result');
        const alertDiv = document.getElementById('face-result-alert');
        const titleElement = document.getElementById('face-result-title');
        const messageElement = document.getElementById('face-result-message');

        if (resultDiv && alertDiv && titleElement && messageElement) {
            alertDiv.className = 'alert alert-warning';
            titleElement.textContent = '⚠️ Reposicionamiento Necesario';
            
            messageElement.innerHTML = `
                <div class="text-center">
                    <h5>Empleado detectado: <strong>${employeeName}</strong></h5>
                    <p>Confianza actual: <strong>${(confidence * 100).toFixed(1)}%</strong></p>
                    <div class="mt-3 mb-3">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-camera"></i> Para mejor reconocimiento:</h6>
                            <ul class="list-unstyled mb-0">
                                <li>✓ Mire directamente a la cámara</li>
                                <li>✓ Mantenga el rostro centrado</li>
                                <li>✓ Asegúrese de tener buena iluminación</li>
                                <li>✓ Retire lentes o gorras si los usa</li>
                                <li>✓ Mantenga una distancia de 50-70 cm</li>
                            </ul>
                        </div>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: ${confidence * 100}%" 
                             aria-valuenow="${confidence * 100}" aria-valuemin="0" aria-valuemax="100">
                            ${(confidence * 100).toFixed(1)}%
                        </div>
                    </div>
                    <p><small>Se necesita al menos 85% para identificación automática</small></p>
                    <button type="button" class="btn btn-primary" onclick="selectEmployeeCandidate(${employeeId}, '${employeeName}')">
                        <i class="fas fa-user-check"></i> Confirmar que soy ${employeeName}
                    </button>
                </div>
            `;
            
            resultDiv.style.display = 'block';
        }
    }

    showEmployeeNotFound(confidence) {
        console.log('❌ Showing employee not found message, confidence:', confidence);
        
        // Mostrar mensaje pequeño de advertencia
        const resultDiv = document.getElementById('face-verification-result');
        const alertDiv = document.getElementById('face-result-alert');
        const titleElement = document.getElementById('face-result-title');
        const messageElement = document.getElementById('face-result-message');

        if (resultDiv && alertDiv && titleElement && messageElement) {
            alertDiv.className = 'alert alert-warning';
            titleElement.textContent = '⚠️ Persona No Reconocida';
            
            messageElement.innerHTML = `
                <div class="text-center">
                    <p><strong>No se reconoce la persona.</strong></p>
                    <p>Haga el registro manual o intente nuevamente con otra persona.</p>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-primary me-2" onclick="window.location.reload()">
                            <i class="fas fa-redo"></i> Intentar Nuevamente
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('codigo_empleado').focus()">
                            <i class="fas fa-keyboard"></i> Registro Manual
                        </button>
                    </div>
                </div>
            `;
            
            resultDiv.style.display = 'block';
            
            // Detener identificación después de mostrar el mensaje
            setTimeout(() => {
                this.stopFaceVerification();
            }, 1000);
        }
    }

    showAutoIdentificationConfirmation(candidate, confidence) {
        const employeeName = candidate.NOMBRE_COMPLETO || candidate.full_name || 'Empleado';
        const employeeId = candidate.ID_EMPLEADO || candidate.employee_id || 0;
        
        console.log('✅ Showing auto identification confirmation for:', employeeName, 'with confidence:', confidence);
        
        // Mostrar confirmación de identificación automática
        const resultDiv = document.getElementById('face-verification-result');
        const alertDiv = document.getElementById('face-result-alert');
        const titleElement = document.getElementById('face-result-title');
        const messageElement = document.getElementById('face-result-message');

        if (resultDiv && alertDiv && titleElement && messageElement) {
            alertDiv.className = 'alert alert-success';
            titleElement.textContent = '✅ Empleado Identificado Automáticamente';
            
            messageElement.innerHTML = `
                <div class="text-center">
                    <div class="mb-4">
                        <div class="display-4 text-success mb-3">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h3><strong>${employeeName}</strong></h3>
                        <p class="text-muted">ID: ${employeeId}</p>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-chart-line"></i> Confianza de Identificación</h5>
                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ${confidence * 100}%" 
                                 aria-valuenow="${confidence * 100}" aria-valuemin="0" aria-valuemax="100">
                                <strong>${(confidence * 100).toFixed(1)}%</strong>
                            </div>
                        </div>
                        <small>Identificación de alta precisión (≥85%)</small>
                    </div>
                    
                    <div class="alert alert-warning mb-4">
                        <h6><i class="fas fa-question-circle"></i> ¿Es usted ${employeeName}?</h6>
                        <p class="mb-0">Confirme su identidad para proceder con el registro de asistencia</p>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button type="button" class="btn btn-success btn-lg" onclick="confirmAutoIdentification(${employeeId}, '${employeeName}', ${confidence})">
                            <i class="fas fa-check"></i> Sí, soy ${employeeName}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="rejectAutoIdentification()">
                            <i class="fas fa-times"></i> No soy esta persona
                        </button>
                    </div>
                </div>
            `;
            
            resultDiv.style.display = 'block';
        }
    }

    showMessage(message, type = 'info', duration = 3000) {
        console.log(`💬 showMessage: ${message} (${type})`);
        
        // Crear o encontrar elemento para mostrar mensajes
        let messageElement = document.getElementById('biometric-status-message');
        
        if (!messageElement) {
            // Crear elemento si no existe
            messageElement = document.createElement('div');
            messageElement.id = 'biometric-status-message';
            messageElement.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
                padding: 15px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            `;
            document.body.appendChild(messageElement);
        }
        
        // Definir colores según el tipo
        const colors = {
            info: '#17a2b8',
            success: '#28a745',
            warning: '#ffc107',
            error: '#dc3545'
        };
        
        // Aplicar estilo según el tipo
        messageElement.style.backgroundColor = colors[type] || colors.info;
        messageElement.textContent = message;
        messageElement.style.display = 'block';
        messageElement.style.opacity = '1';
        
        // Auto-ocultar después del tiempo especificado
        if (duration > 0) {
            setTimeout(() => {
                if (messageElement) {
                    messageElement.style.opacity = '0';
                    setTimeout(() => {
                        if (messageElement && messageElement.parentNode) {
                            messageElement.parentNode.removeChild(messageElement);
                        }
                    }, 300);
                }
            }, duration);
        }
    }

    // Función para mostrar la foto capturada de evidencia
    showCapturedPhoto(photoUrl) {
        try {
            console.log('📸 Mostrando foto capturada:', photoUrl);
            
            // Crear elemento de imagen para mostrar la foto
            const photoContainer = document.createElement('div');
            photoContainer.className = 'captured-photo-container mt-3 text-center';
            photoContainer.innerHTML = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-camera"></i> Foto de Evidencia Capturada</h6>
                    <img src="${photoUrl}" alt="Foto de evidencia" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                    <p class="mb-0 mt-2 small">Foto guardada como evidencia del registro biométrico</p>
                </div>
            `;
            
            // Insertar en el área de resultados
            const resultArea = document.getElementById('face-verification-result');
            if (resultArea) {
                resultArea.appendChild(photoContainer);
                resultArea.style.display = 'block';
            }
            
        } catch (error) {
            console.error('Error mostrando foto capturada:', error);
        }
    }

    // Función de compatibilidad para showVerificationSuccess
    showVerificationSuccess(title, message, duration = 5000) {
        console.log(`✅ showVerificationSuccess: ${title} - ${message}`);
        // Usar showMessage con tipo success
        this.showMessage(`${title}: ${message}`, 'success', duration);
    }

    retryVerification() {
        // Cerrar modal de error
        const errorModal = bootstrap.Modal.getInstance(document.getElementById('verificationErrorModal'));
        errorModal.hide();

        // Reiniciar intentos
        this.attempts[this.currentTab] = 0;

        // Reintentar según la pestaña actual
        switch (this.currentTab) {
            case 'face':
                this.startFaceVerification();
                break;
            case 'fingerprint':
                this.startFingerprintVerification();
                break;
            case 'rfid':
                this.startRfidVerification();
                break;
        }
    }

    // Iniciar verificación automática de todos los métodos disponibles
    startAutomaticVerification() {
        console.log('Starting automatic verification for available methods...');

        // Iniciar verificación facial si está disponible
        if (this.employeeBiometrics.face && this.deviceStatus.face.available) {
            console.log('Starting automatic face verification...');
            this.startFaceVerification();
        }

        // Iniciar verificación de huella si está disponible
        if (this.employeeBiometrics.fingerprint && this.deviceStatus.fingerprint.available) {
            console.log('Starting automatic fingerprint verification...');
            this.startFingerprintVerification();
        }

        // Iniciar verificación RFID si está disponible
        if (this.employeeBiometrics.rfid && this.deviceStatus.rfid.available) {
            console.log('Starting automatic RFID verification...');
            this.startRfidVerification();
        }

        // Actualizar mensaje de estado
        this.updateVerificationStatus();
    }

    // Actualizar el estado de verificación
    updateVerificationStatus() {
        const statusMessage = document.getElementById('biometric-method-selection-message');
        if (statusMessage) {
            const availableMethods = [];
            if (this.employeeBiometrics.face && this.deviceStatus.face.available) availableMethods.push('Facial');
            if (this.employeeBiometrics.fingerprint && this.deviceStatus.fingerprint.available) availableMethods.push('Huella');
            if (this.employeeBiometrics.rfid && this.deviceStatus.rfid.available) availableMethods.push('RFID');

            if (availableMethods.length > 0) {
                statusMessage.innerHTML = `
                    <i class="fas fa-hand-pointer"></i> <strong>Seleccione un método de verificación</strong>
                    <br>
                    <small>Métodos disponibles: ${availableMethods.join(', ')}. Haga clic en una pestaña para seleccionar el método deseado y luego presione "Iniciar Verificación".</small>
                `;
            } else {
                statusMessage.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i> <strong>No hay métodos biométricos disponibles</strong>
                    <br>
                    <small>Este empleado no tiene métodos biométricos registrados.</small>
                `;
            }
        }
    }

    showLoading(message = 'Cargando...') {
        // Crear overlay de carga si no existe
        let loadingOverlay = document.getElementById('loading-overlay');
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loading-overlay';
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2" id="loading-message">${message}</p>
                </div>
            `;
            document.body.appendChild(loadingOverlay);
        }

        const loadingMessage = document.getElementById('loading-message');
        if (loadingMessage) {
            loadingMessage.textContent = message;
        }
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    }

    hideLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

    // Función para recargar los datos de asistencia en la tabla principal
    refreshAttendanceData() {
        console.log('🔄 Refrescando datos de asistencia...');
        
        // Verificar si existe la función loadAttendanceDay en el scope global
        if (typeof window.loadAttendanceDay === 'function') {
            // Llamar a la función con un pequeño delay para permitir que el modal se cierre
            setTimeout(() => {
                window.loadAttendanceDay();
                console.log('✅ Datos de asistencia actualizados');
            }, 500);
        } else if (typeof loadAttendanceDay === 'function') {
            // Intentar sin window.
            setTimeout(() => {
                loadAttendanceDay();
                console.log('✅ Datos de asistencia actualizados');
            }, 500);
        } else {
            console.warn('⚠️ Función loadAttendanceDay no encontrada, intentando recarga manual');
            // Fallback: recargar la página si no se encuentra la función
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    async selectCandidate(employeeId, employeeName) {
        console.log('🎯 selectCandidate called with:', employeeId, employeeName);
        
        try {
            // Mostrar mensaje de cargando
            this.showMessage('Procesando selección de empleado...', 'info');
            
            // Ocultar selección de candidatos
            const resultElement = document.getElementById('face-verification-result');
            if (resultElement) {
                resultElement.style.display = 'none';
            }
            
            // Validar parámetros de entrada
            if (!employeeId || !employeeName) {
                throw new Error('ID o nombre de empleado no válido');
            }
            
            console.log('📡 Fetching employee details for ID:', employeeId);
            
            // Buscar datos completos del empleado con timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos timeout
            
            const response = await fetch(`api/employee/get_details.php?id=${employeeId}`, {
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('📋 Employee details response:', result);
            
            if (result.success && result.employee) {
                // Confirmar selección con el usuario
                const confirmMessage = `¿Confirma que desea registrar asistencia para:\n\n${result.employee.nombre_completo || employeeName}\nCódigo: ${result.employee.codigo_empleado || 'N/A'}\nCargo: ${result.employee.cargo || 'N/A'}?`;
                
                if (!confirm(confirmMessage)) {
                    console.log('👤 User cancelled employee selection');
                    this.showMessage('Selección cancelada por el usuario', 'warning');
                    // Volver a mostrar los candidatos
                    if (resultElement) {
                        resultElement.style.display = 'block';
                    }
                    return;
                }
                
                // Almacenar datos del empleado seleccionado
                this.selectedEmployee = employeeId;
                this.employeeData = result.employee;
                
                console.log('✅ Employee selected successfully:', result.employee);
                
                // Actualizar información en la interfaz
                this.updateEmployeeInfo(result.employee);
                
                // Mostrar mensaje de éxito
                this.showMessage(`Empleado ${employeeName} seleccionado. Registrando asistencia...`, 'success');
                
                // Registrar asistencia automáticamente solo si no hay un registro en progreso
                if (!this.isRegistering) {
                    await this.registerAttendanceAfterIdentification(result.employee);
                } else {
                    console.log('⚠️ Registro ya en progreso desde selectCandidate, saltando');
                }
                
            } else {
                const errorMsg = result.message || 'No se pudieron obtener los datos del empleado seleccionado';
                console.error('❌ Employee details error:', errorMsg);
                throw new Error(errorMsg);
            }
            
        } catch (error) {
            console.error('❌ Error in selectCandidate:', error);
            
            let errorMessage = 'Error seleccionando empleado';
            
            if (error.name === 'AbortError') {
                errorMessage = 'Timeout: La solicitud tardó demasiado tiempo';
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            this.showError('Error de Selección', errorMessage);
            
            // Volver a mostrar los candidatos en caso de error
            const resultElement = document.getElementById('face-verification-result');
            if (resultElement) {
                resultElement.style.display = 'block';
            }
        }
    }

    /**
     * Captura una foto del video usando el mismo método que attendance.js
     * @param {HTMLVideoElement} video - Elemento video
     * @param {HTMLCanvasElement} canvas - Elemento canvas
     * @returns {string|null} - Datos base64 de la imagen o null si falla
     */
    capturePhoto(video, canvas) {
        try {
            console.log('📸 Capturando foto usando método simple...');

            if (!video || !canvas) {
                console.error('❌ Video o canvas no proporcionados');
                return null;
            }

            if (!video.srcObject || !video.srcObject.active) {
                console.error('❌ La cámara no está activa');
                return null;
            }

            // Usar el mismo método que funciona en attendance.js
            canvas.style.display = 'none';
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = canvas.toDataURL('image/jpeg');

            console.log('✅ Foto capturada exitosamente con método simple');
            console.log('📊 Tamaño de datos:', imageData.length, 'caracteres');

            return imageData;

        } catch (error) {
            console.error('❌ Error capturando foto:', error);
            return null;
        }
    }
}

// Función global para abrir el modal de verificación biométrica
function openBiometricAutoIdentification(attendanceType = 'ENTRADA') {
    console.log('🚀 Opening biometric auto identification mode for:', attendanceType);

    // Asegurar que cualquier cámara previa esté cerrada
    if (window.biometricVerificationModal && typeof window.biometricVerificationModal.ensureCameraStop === 'function') {
        console.log('🔄 Stopping any previous camera...');
        window.biometricVerificationModal.ensureCameraStop();
    }

    // Configurar el modal para modo automático
    document.getElementById('verification-attendance-type').value = attendanceType;
    
    // Limpiar información de empleado (será llenada automáticamente)
    document.getElementById('verification-employee-id').value = '';
    document.getElementById('verification-employee-code').textContent = 'Por identificar...';
    document.getElementById('verification-employee-name').textContent = 'Identificación automática en progreso...';
    document.getElementById('verification-employee-establishment').textContent = '-';

    // Configurar el modal para indicar modo automático
    const modalTitle = document.getElementById('biometricVerificationModalLabel');
    modalTitle.innerHTML = '<i class="fas fa-search"></i> Verificación Automática';

    // Mostrar mensaje especial para modo automático
    const methodMessage = document.getElementById('biometric-method-selection-message');
    methodMessage.className = 'alert alert-primary mb-3';
    methodMessage.innerHTML = `
        <i class="fas fa-robot"></i> <strong>Modo de Identificación Automática</strong><br>
        <small>El sistema identificará automáticamente al empleado usando reconocimiento facial. No es necesario seleccionar empleado previamente.</small>
    `;

    // Ocultar el botón de verificación manual
    const manualBtn = document.getElementById('startFaceVerification');
    if (manualBtn) {
        manualBtn.style.display = 'none';
    }

    // Mostrar el botón de identificación automática
    const autoBtn = document.getElementById('startAutoIdentification');
    if (autoBtn) {
        autoBtn.style.display = 'block';
    }

    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('biometricVerificationModal'));
    modal.show();

    // Inicializar verificación biométrica en modo automático cuando el modal se muestre
    const modalElement = document.getElementById('biometricVerificationModal');
    modalElement.addEventListener('shown.bs.modal', function onModalShown() {
        console.log('📱 Modal shown, initializing auto identification mode...');
        
        if (window.biometricVerificationModal) {
            // Configurar modo automático
            window.biometricVerificationModal.selectedEmployee = null;
            window.biometricVerificationModal.employeeData = null;
            window.biometricVerificationModal.identificationMode = 'auto';
            
            console.log('✅ Biometric verification modal configured for auto identification mode');
            
            // ACTUALIZAR PESTAÑAS PARA MODO AUTOMÁTICO - Mostrar todas las pestañas
            window.biometricVerificationModal.updateAvailableBiometricServices();
            
            // Forzar estado de dispositivos para modo automático
            console.log('🔧 Initializing devices for auto mode...');
            
            // Simular estado de dispositivos disponibles para modo automático
            window.biometricVerificationModal.deviceStatus = {
                face: { available: true, error: null, connected: true },
                fingerprint: { available: false, error: null, connected: false },
                rfid: { available: false, error: null, connected: false },
                initialized: true
            };
            
            // Verificar cámara disponible directamente
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                console.log('📷 Camera API available');
                window.biometricVerificationModal.deviceStatus.face.available = true;
                window.biometricVerificationModal.deviceStatus.face.connected = true;
            } else {
                console.warn('� Camera API not available');
                window.biometricVerificationModal.deviceStatus.face.available = false;
            }
            
            console.log('🎯 Auto identification mode ready, device status:', window.biometricVerificationModal.deviceStatus);
        } else {
            console.error('❌ biometricVerificationModal not initialized');
        }
        
        // Remover el event listener para evitar múltiples llamadas
        modalElement.removeEventListener('shown.bs.modal', onModalShown);
    });
}

function openBiometricVerificationForEmployee(employeeId, employeeName, attendanceType = 'ENTRADA') {
    console.log('Opening biometric verification for employee:', employeeId, employeeName);

    // Función para proceder con la verificación
    const proceedWithVerification = () => {
        // PRIMERO: Asegurar que cualquier cámara previa esté cerrada
        if (window.biometricVerificationModal && typeof window.biometricVerificationModal.ensureCameraStop === 'function') {
            window.biometricVerificationModal.ensureCameraStop();
        } else {
            console.warn('biometricVerificationModal not fully initialized yet');
        }

        // SEGUNDO: Validar si el empleado tiene horarios para hoy
        console.log('Validating employee schedule...');
        const hoy = window.Bogota.getDateString(); // YYYY-MM-DD en zona horaria de Bogotá
        const diaSemana = window.Bogota.getDayOfWeek(); // 0=domingo, 1=lunes, etc.
        
        fetch(`api/check-employee-schedule.php?employee_id=${employeeId}&fecha=${hoy}&dia_semana=${diaSemana}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(async response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Intentar obtener el texto de la respuesta para debugging
            const responseText = await response.text();
            console.log('Response text (first 200 chars):', responseText.substring(0, 200));
            
            try {
                // Intentar parsear como JSON
                const jsonData = JSON.parse(responseText);
                return jsonData;
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was not valid JSON. Response text:', responseText);
                throw new Error(`Invalid JSON response: ${parseError.message}`);
            }
        })
        .then(validationResult => {
            console.log('Schedule validation result:', validationResult);
            
            // Verificar si la respuesta indica redirección (sesión expirada)
            if (validationResult.redirect) {
                console.log('Session expired, redirecting to:', validationResult.redirect);
                window.location.href = validationResult.redirect;
                return;
            }

            // Si no tiene horarios o no puede registrar, mostrar error y NO abrir el modal
            if (!validationResult.success || !validationResult.tiene_horario) {
                const errorMessage = validationResult.message || 'El empleado no tiene horario asignado para hoy';
                console.log('Schedule validation failed:', errorMessage);

                // Mostrar error usando el modal existente o alert
                if (typeof window.biometricVerificationModal !== 'undefined' && window.biometricVerificationModal.showAttendanceValidationError) {
                    window.biometricVerificationModal.showAttendanceValidationError(errorMessage, {
                        tipo: 'horario',
                        detalles: 'No se encontraron horarios asignados para este día'
                    });
                } else {
                    alert('❌ ERROR: ' + errorMessage + '\n\nNo se puede abrir el modal de verificación.');
                }
                return; // NO continuar abriendo el modal
            }

            // Verificar si hay entrada abierta (nueva validación)
            if (validationResult.puede_registrar === false) {
                const errorMessage = validationResult.message || 'Ya existe una entrada registrada sin salida correspondiente';
                console.log('Open entry validation failed:', errorMessage);

                // Mostrar error usando el modal existente o alert
                if (typeof window.biometricVerificationModal !== 'undefined' && window.biometricVerificationModal.showAttendanceValidationError) {
                    window.biometricVerificationModal.showAttendanceValidationError(errorMessage, {
                        tipo: 'entrada_abierta',
                        detalles: 'Debe registrar la salida antes de una nueva entrada'
                    });
                } else {
                    alert('❌ ERROR: ' + errorMessage + '\n\nNo se puede abrir el modal de verificación.');
                }
                return; // NO continuar abriendo el modal
            }

            console.log('Schedule validation passed, opening modal...');

            // SEGUNDO: Verificar que el modal existe en el DOM
            const modalElement = document.getElementById('biometricVerificationModal');
            if (!modalElement) {
                throw new Error('Modal element not found in DOM');
            }

            // Verificar que Bootstrap esté disponible
            if (typeof bootstrap === 'undefined') {
                throw new Error('Bootstrap is not loaded');
            }

            // Obtener o crear la instancia del modal
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

            // Configurar los datos del empleado antes de mostrar el modal
            modalElement.setAttribute('data-employee-id', employeeId);
            modalElement.setAttribute('data-attendance-type', attendanceType || 'ENTRADA');

            // Mostrar el modal usando la API de Bootstrap
            modal.show();

            // Configurar modo manual: mostrar solo el botón de verificación manual
            const manualBtn = document.getElementById('startFaceVerification');
            const autoBtn = document.getElementById('startAutoIdentification');

            if (manualBtn) {
                manualBtn.style.display = 'block';
                manualBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Verificación Biométrica';
            }

            if (autoBtn) {
                autoBtn.style.display = 'none';
            }

            // Configurar título del modal para modo manual
            const modalTitle = document.getElementById('biometricVerificationModalLabel');
            if (modalTitle) {
                modalTitle.innerHTML = '<i class="fas fa-shield-alt"></i> Verificación Biométrica';
            }

            // Configurar mensaje para modo manual
            const methodMessage = document.getElementById('biometric-method-selection-message');
            if (methodMessage) {
                methodMessage.className = 'alert alert-success mb-3';
                methodMessage.innerHTML = `
                    <i class="fas fa-user-check"></i> <strong>Modo de Verificación Manual</strong><br>
                    <small>Empleado seleccionado: ${employeeName}. Use la verificación biométrica para registrar la asistencia.</small>
                `;
            }

            console.log('Biometric verification modal opened successfully');
        })
        .catch(error => {
            console.error('Error opening biometric verification modal:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                employeeId: employeeId,
                attendanceType: attendanceType,
                url: `/api/attendance/validate-attendance.php?employee_id=${employeeId}&attendance_type=${attendanceType}`
            });
            
            // Mostrar un mensaje de error más informativo
            let errorMessage = 'Error al abrir el modal de verificación biométrica';
            if (error.message.includes('JSON')) {
                errorMessage += ': La respuesta del servidor no es válida';
            } else if (error.message.includes('HTTP')) {
                errorMessage += `: ${error.message}`;
            } else if (error.message.includes('fetch')) {
                errorMessage += ': Error de conexión con el servidor';
            } else {
                errorMessage += `: ${error.message}`;
            }
            
            alert(errorMessage);
        });
    };

    // Verificar si la instancia del modal está disponible
    if (window.biometricVerificationModal) {
        proceedWithVerification();
    } else {
        // Esperar a que se inicialice
        console.log('Waiting for biometric modal to initialize...');
        const checkInterval = setInterval(() => {
            if (window.biometricVerificationModal) {
                console.log('Biometric modal initialized, proceeding...');
                clearInterval(checkInterval);
                proceedWithVerification();
            }
        }, 100);
        
        // Timeout después de 5 segundos
        setTimeout(() => {
            clearInterval(checkInterval);
            if (!window.biometricVerificationModal) {
                console.error('Biometric modal failed to initialize');
                alert('Error: El sistema de verificación biométrica no pudo inicializarse. Recarga la página e intenta nuevamente.');
            }
        }, 5000);
    }
}

// Hacer las funciones globalmente disponibles
window.openBiometricVerificationForEmployee = openBiometricVerificationForEmployee;
window.openBiometricAutoIdentification = openBiometricAutoIdentification;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    try {
        console.log('🚀 DOMContentLoaded - Initializing BiometricVerificationModal...');
        
        // Usar un nombre diferente para evitar conflictos con elementos DOM
        window.biometricModalInstance = new BiometricVerificationModal();
        
        // También mantener la referencia original para compatibilidad
        window.biometricVerificationModal = window.biometricModalInstance;
        
        console.log('✅ BiometricVerificationModal initialized successfully');
        console.log('📋 Available methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(window.biometricModalInstance)));
        console.log('🔍 Instance type:', typeof window.biometricModalInstance);
        console.log('🔍 Constructor name:', window.biometricModalInstance.constructor.name);
    } catch (error) {
        console.error('❌ Error initializing BiometricVerificationModal:', error);
    }
});

// Inicialización alternativa si el DOM ya está cargado
if (document.readyState === 'loading') {
    // DOM aún no está cargado, el event listener se encargará
    console.log('⏳ DOM still loading, waiting for DOMContentLoaded...');
} else {
    // DOM ya está cargado, inicializar inmediatamente
    try {
        if (!window.biometricModalInstance) {
            console.log('🔄 DOM already loaded, initializing BiometricVerificationModal immediately...');
            
            // Usar instancia separada
            window.biometricModalInstance = new BiometricVerificationModal();
            window.biometricVerificationModal = window.biometricModalInstance;
            
            console.log('✅ BiometricVerificationModal initialized successfully (immediate)');
            console.log('📋 Available methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(window.biometricModalInstance)));
            console.log('🔍 Instance type:', typeof window.biometricModalInstance);
            console.log('🔍 Constructor name:', window.biometricModalInstance.constructor.name);
        } else {
            console.log('ℹ️ BiometricVerificationModal already initialized');
        }
    } catch (error) {
        console.error('❌ Error initializing BiometricVerificationModal immediately:', error);
    }
}

// Función global para seleccionar candidato de empleado
function selectEmployeeCandidate(employeeId, employeeName) {
    console.log('🎯 Selecting employee candidate:', employeeId, employeeName);
    
    // Debug: Verificar estado del objeto
    console.log('🔍 Debug info:');
    console.log('  - window.biometricVerificationModal exists:', typeof window.biometricVerificationModal);
    console.log('  - is undefined?', window.biometricVerificationModal === undefined);
    console.log('  - is null?', window.biometricVerificationModal === null);
    
    if (window.biometricVerificationModal) {
        console.log('  - object prototype:', Object.getPrototypeOf(window.biometricVerificationModal));
        console.log('  - has selectCandidate?', typeof window.biometricVerificationModal.selectCandidate);
    }
    
    // Verificar que el modal esté inicializado
    if (typeof window.biometricVerificationModal !== 'undefined' && window.biometricVerificationModal) {
        if (typeof window.biometricVerificationModal.selectCandidate === 'function') {
            console.log('✅ Calling selectCandidate method...');
            window.biometricVerificationModal.selectCandidate(employeeId, employeeName);
        } else {
            console.error('❌ selectCandidate method not found in biometricVerificationModal');
            console.log('Available methods:', Object.getOwnPropertyNames(window.biometricVerificationModal));
            alert('Error: Función de selección no disponible. Recargue la página.');
        }
    } else {
        console.error('❌ biometricVerificationModal not initialized');
        console.log('🔄 Attempting to initialize as fallback...');
        
        // Intentar inicializar como fallback
        try {
            if (typeof BiometricVerificationModal !== 'undefined') {
                console.log('� BiometricVerificationModal class available, creating instance...');
                window.biometricVerificationModal = new BiometricVerificationModal();
                console.log('✅ Fallback initialization successful');
                
                if (window.biometricVerificationModal.selectCandidate) {
                    console.log('🎯 Calling selectCandidate after fallback initialization...');
                    window.biometricVerificationModal.selectCandidate(employeeId, employeeName);
                } else {
                    console.error('❌ selectCandidate still not available after fallback init');
                    alert('Error: No se pudo inicializar la función de selección.');
                }
            } else {
                console.error('❌ BiometricVerificationModal class not available');
                alert('Error: Clase BiometricVerificationModal no disponible. Recargue la página.');
            }
        } catch (error) {
            console.error('❌ Failed to initialize modal in fallback:', error);
            alert('Error crítico: No se pudo inicializar el modal biométrico.');
        }
    }
}

// Función para confirmar la identificación automática
function confirmAutoIdentification(employeeId, employeeName, confidence) {
    console.log('✅ User confirmed auto identification for:', employeeName);
    
    // Buscar el modal instance
    const modalInstance = window.biometricVerificationModal || window.biometricModalInstance;
    
    if (modalInstance && typeof modalInstance.registerAttendanceAfterIdentification === 'function') {
        // Crear objeto empleado para el registro
        const employeeData = {
            ID_EMPLEADO: employeeId,
            employee_id: employeeId,
            NOMBRE_COMPLETO: employeeName,
            full_name: employeeName,
            confidence: confidence,
            CONFIDENCE: confidence
        };
        
        console.log('🎯 Proceeding with attendance registration for:', employeeData);
        modalInstance.registerAttendanceAfterIdentification(employeeData);
    } else {
        console.error('❌ registerAttendanceAfterIdentification method not available');
        alert('Error: No se pudo proceder con el registro de asistencia.');
    }
}

// Función para rechazar la identificación automática
function rejectAutoIdentification() {
    console.log('❌ User rejected auto identification');
    
    // Ocultar resultados y mostrar opciones
    const resultDiv = document.getElementById('face-verification-result');
    if (resultDiv) {
        resultDiv.style.display = 'none';
    }
    
    // Volver a enfocar en código manual
    const codigoInput = document.getElementById('codigo_empleado');
    if (codigoInput) {
        codigoInput.focus();
    }
    
    // Mostrar mensaje
    alert('Identificación rechazada. Por favor, ingrese su código manualmente o intente nuevamente.');
}

// Función para mostrar la foto capturada
function showCapturedPhoto(photoUrl) {
    try {
        const container = document.getElementById('captured-photo-container');
        const preview = document.getElementById('captured-photo-preview');
        
        if (container && preview && photoUrl) {
            // Asegurar que la URL sea absoluta o relativa correcta
            let finalUrl = photoUrl;
            if (photoUrl.startsWith('/uploads/')) {
                finalUrl = photoUrl; // Ya es relativa correcta
            } else if (photoUrl.startsWith('uploads/')) {
                finalUrl = '/' + photoUrl; // Agregar / al inicio
            }
            
            preview.src = finalUrl;
            preview.onload = () => {
                console.log('✅ Imagen cargada correctamente:', finalUrl);
                container.style.display = 'block';
            };
            preview.onerror = () => {
                console.error('❌ Error cargando imagen:', finalUrl);
                // Intentar con URL alternativa
                if (!finalUrl.includes('Synktime')) {
                    preview.src = '/Synktime' + finalUrl;
                }
            };
            
            console.log('📸 Intentando mostrar foto:', finalUrl);
        }
    } catch (error) {
        console.warn('No se pudo mostrar la foto capturada:', error);
    }
}

// Función para mostrar mensajes de estado
function showMessage(message, type = 'info', duration = 3000) {
    // Crear o actualizar el elemento de mensaje
    let messageElement = document.getElementById('biometric-status-message');
    if (!messageElement) {
        messageElement = document.createElement('div');
        messageElement.id = 'biometric-status-message';
        messageElement.className = 'alert mt-3';
        messageElement.style.position = 'fixed';
        messageElement.style.top = '20px';
        messageElement.style.right = '20px';
        messageElement.style.zIndex = '9999';
        messageElement.style.maxWidth = '400px';
        document.body.appendChild(messageElement);
    }
    
    // Configurar el mensaje
    messageElement.className = `alert alert-${type} mt-3`;
    messageElement.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
    messageElement.style.display = 'block';
    
    // Auto-ocultar después del tiempo especificado
    if (duration > 0) {
        setTimeout(() => {
            if (messageElement) {
                messageElement.style.display = 'none';
            }
        }, duration);
    }
}

// Función para confirmar identificación automática
function confirmAutoIdentification(employeeId, employeeName, confidence) {
    console.log('✅ Confirming auto identification for:', employeeId, employeeName, 'with confidence:', confidence);
    
    if (!window.biometricVerificationModal) {
        console.error('❌ biometricVerificationModal not available');
        showMessage('Error: Modal de verificación no disponible', 'error');
        return;
    }
    
    try {
        // DETENER COMPLETAMENTE cualquier verificación en progreso
        console.log('🛑 Stopping all verification processes...');
        window.biometricVerificationModal.stopFaceVerification();
        window.biometricVerificationModal.stopAllVerification();
        
        // Resetear flags de verificación para evitar cualquier loop pendiente
        window.biometricVerificationModal.isVerifying.face = false;
        window.biometricVerificationModal.identificationMode = null;
        window.biometricVerificationModal.isRegistering = false;
        
        // Limpiar cualquier timeout pendiente (si existe)
        if (window.biometricVerificationModal.detectionTimeout) {
            clearTimeout(window.biometricVerificationModal.detectionTimeout);
            window.biometricVerificationModal.detectionTimeout = null;
        }
        
        // Configurar empleado seleccionado
        window.biometricVerificationModal.selectedEmployee = employeeId;
        window.biometricVerificationModal.employeeData = {
            ID_EMPLEADO: employeeId,
            NOMBRE_COMPLETO: employeeName,
            confidence: confidence,
            CONFIDENCE: confidence
        };
        
        // Actualizar información del empleado en la interfaz
        window.biometricVerificationModal.updateEmployeeInfo(window.biometricVerificationModal.employeeData);
        
        // Ocultar confirmación
        const resultDiv = document.getElementById('face-verification-result');
        if (resultDiv) {
            resultDiv.style.display = 'none';
        }
        
        // Mostrar mensaje de confirmación
        showMessage(`✅ Identificación confirmada: ${employeeName}`, 'success');
        
        // Registrar asistencia automáticamente
        window.biometricVerificationModal.registerAttendanceAfterIdentification(window.biometricVerificationModal.employeeData);
        
    } catch (error) {
        console.error('❌ Error confirming auto identification:', error);
        showMessage('Error confirmando identificación automática', 'error');
    }
}

// Función para rechazar identificación automática
function rejectAutoIdentification() {
    console.log('❌ Rejecting auto identification');
    
    if (!window.biometricVerificationModal) {
        console.error('❌ biometricVerificationModal not available');
        showMessage('Error: Modal de verificación no disponible', 'error');
        return;
    }
    
    try {
        // DETENER COMPLETAMENTE cualquier verificación en progreso
        console.log('🛑 Stopping all verification processes...');
        window.biometricVerificationModal.stopFaceVerification();
        window.biometricVerificationModal.stopAllVerification();
        
        // Resetear flags de verificación para evitar cualquier loop pendiente
        window.biometricVerificationModal.isVerifying.face = false;
        window.biometricVerificationModal.identificationMode = null;
        window.biometricVerificationModal.isRegistering = false;
        
        // Limpiar cualquier timeout pendiente (si existe)
        if (window.biometricVerificationModal.detectionTimeout) {
            clearTimeout(window.biometricVerificationModal.detectionTimeout);
            window.biometricVerificationModal.detectionTimeout = null;
        }
        
        // Ocultar confirmación
        const resultDiv = document.getElementById('face-verification-result');
        if (resultDiv) {
            resultDiv.style.display = 'none';
        }
        
        // Mostrar mensaje de rechazo
        showMessage('❌ Identificación automática rechazada. Puede intentar nuevamente o usar registro manual.', 'warning');
        
        // Resetear estado para permitir nueva identificación
        window.biometricVerificationModal.selectedEmployee = null;
        window.biometricVerificationModal.employeeData = null;
        window.biometricVerificationModal.identificationMode = null;
        
        // Limpiar información del empleado
        document.getElementById('verification-employee-id').value = '';
        document.getElementById('verification-employee-code').textContent = 'Por identificar...';
        document.getElementById('verification-employee-name').textContent = 'Identificación rechazada';
        document.getElementById('verification-employee-establishment').textContent = '-';
        
    } catch (error) {
        console.error('❌ Error rejecting auto identification:', error);
        showMessage('Error rechazando identificación automática', 'error');
    }
}
</script>

<!-- Script de corrección para inicialización del modal -->
<script src="fix_modal_initialization.js"></script>

<!-- NO incluir archivo JS conflictivo que sobrescribe la clase -->
<!-- <script src="js/biometric_verification.js"></script> -->