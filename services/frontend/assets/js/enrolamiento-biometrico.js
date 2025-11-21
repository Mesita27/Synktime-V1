/**
 * SISTEMA DE ENROLAMIENTO BIOMÉTRICO COMPLETO
 * JavaScript para la página de enrolamiento biométrico
 * Integrado con el sistema SynkTime - Soporta Facial, Huella y RFID
 */

// Variables globales mejoradas
let employeeData = [];
let filteredEmployees = [];
let currentPage = 1;
let currentLimit = 10;
let totalPages = 0;
let totalEmployees = 0;
let currentFilters = {};
let currentModal = null;
const establecimientosCache = {};

// Variables para enrolamiento con verificación de dispositivos
let faceDetectionModel = null;
let faceMeshModel = null;
let faceStream = null;
let faceCaptures = [];
let fingerprintDevice = null;
let rfidDevice = null;
let currentEmployeeId = null;

// Configuración dinámica del servicio Python
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
        proxyOptions.body = JSON.stringify({ method, target: pathWithQuery });
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

// Utilidades de optimización para reducir tamaño de capturas faciales antes de enviarlas
const FACIAL_IMAGE_LIMITS = {
    maxWidth: 720,
    maxHeight: 720,
    minWidth: 320,
    minHeight: 320,
    maxBytes: 260 * 1024,
    qualities: [0.82, 0.72, 0.64, 0.58, 0.5]
};

function sanitizeBase64(value) {
    if (typeof value !== 'string') {
        return '';
    }
    return value.replace(/[^A-Za-z0-9+/=]/g, '');
}

function sanitizeDataUrl(value) {
    if (typeof value !== 'string') {
        return value;
    }
    return value.replace(/\s+/g, '');
}

function ensureDataUrl(imageData, defaultMime = 'image/jpeg') {
    if (typeof imageData !== 'string') {
        return null;
    }

    const trimmed = imageData.trim();
    if (trimmed === '') {
        return null;
    }

    if (trimmed.startsWith('data:image')) {
        return sanitizeDataUrl(trimmed);
    }

    if (/^https?:\/\//i.test(trimmed)) {
        return trimmed;
    }

    const cleaned = sanitizeBase64(trimmed.replace(/^base64,/i, ''));
    return `data:${defaultMime};base64,${cleaned}`;
}

function estimateDataUrlBytes(dataUrl) {
    if (typeof dataUrl !== 'string') {
        return 0;
    }

    const trimmed = dataUrl.trim();
    if (trimmed === '') {
        return 0;
    }

    const base64MarkerIndex = trimmed.indexOf(';base64,');
    const base64Part = base64MarkerIndex >= 0
        ? trimmed.substring(base64MarkerIndex + 8)
        : trimmed;
    const sanitized = sanitizeBase64(base64Part);

    if (sanitized.length === 0) {
        return 0;
    }

    return Math.ceil((sanitized.length * 3) / 4);
}

async function loadImageElement(dataUrl) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = (error) => reject(error);
        img.crossOrigin = 'anonymous';
        img.src = dataUrl;
    });
}

async function optimizeFacialImageData(imageData, overrides = {}) {
    const normalizedDataUrl = ensureDataUrl(imageData);
    if (!normalizedDataUrl) {
        return imageData;
    }

    const limits = Object.assign({}, FACIAL_IMAGE_LIMITS, overrides);
    const originalBytes = estimateDataUrlBytes(normalizedDataUrl);

    if (originalBytes === 0 || originalBytes <= limits.maxBytes) {
        return sanitizeDataUrl(normalizedDataUrl);
    }

    try {
        const img = await loadImageElement(normalizedDataUrl);
        const aspectRatio = img.width / Math.max(img.height, 1);
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: false });

        const computeInitialScale = () => {
            const widthScale = limits.maxWidth / img.width;
            const heightScale = limits.maxHeight / img.height;
            const scale = Math.min(1, widthScale, heightScale);
            return scale <= 0 ? 1 : scale;
        };

        const drawEncoded = (targetWidth, quality) => {
            const width = Math.max(1, Math.round(targetWidth));
            const height = Math.max(1, Math.round(width / aspectRatio));
            canvas.width = width;
            canvas.height = height;
            ctx.clearRect(0, 0, width, height);
            ctx.drawImage(img, 0, 0, width, height);
            return canvas.toDataURL('image/jpeg', quality);
        };

        let scale = computeInitialScale();
        let targetWidth = Math.max(Math.round(img.width * scale), limits.minWidth);
        targetWidth = Math.min(targetWidth, limits.maxWidth);

        const qualities = Array.isArray(limits.qualities) && limits.qualities.length > 0
            ? limits.qualities
            : FACIAL_IMAGE_LIMITS.qualities;

        let qualityIndex = 0;
        let optimizedDataUrl = drawEncoded(targetWidth, qualities[qualityIndex]);
        let optimizedBytes = estimateDataUrlBytes(optimizedDataUrl);

        const minWidth = Math.max(64, limits.minWidth);

        while (optimizedBytes > limits.maxBytes) {
            if (qualityIndex < qualities.length - 1) {
                qualityIndex += 1;
            } else if (targetWidth > minWidth) {
                targetWidth = Math.max(minWidth, Math.round(targetWidth * 0.85));
                qualityIndex = 0;
            } else {
                break;
            }

            optimizedDataUrl = drawEncoded(targetWidth, qualities[qualityIndex]);
            optimizedBytes = estimateDataUrlBytes(optimizedDataUrl);
        }

        if (optimizedBytes > 0 && optimizedBytes < originalBytes) {
            console.log('📦 Optimización de captura facial', {
                originalKB: Math.round((originalBytes / 1024) * 10) / 10,
                optimizadoKB: Math.round((optimizedBytes / 1024) * 10) / 10,
                anchoObjetivo: targetWidth,
                calidad: qualities[qualityIndex]
            });
            return sanitizeDataUrl(optimizedDataUrl);
        }

        return sanitizeDataUrl(normalizedDataUrl);
    } catch (error) {
        console.warn('No se pudo optimizar la captura facial, usando imagen original', error);
        return sanitizeDataUrl(normalizedDataUrl);
    }
}

async function prepareFacialImagesForUpload(facialCaptures = [], overrides = {}) {
    const preparedImages = [];
    let originalBytesTotal = 0;
    let optimizedBytesTotal = 0;

    for (const capture of facialCaptures) {
        if (!capture || !capture.image) {
            continue;
        }

        const normalized = ensureDataUrl(capture.image);
        if (!normalized) {
            continue;
        }

        const originalBytes = estimateDataUrlBytes(normalized);
        originalBytesTotal += originalBytes;

        let finalImage = capture.optimizedImage || null;

        if (!finalImage) {
            try {
                finalImage = await optimizeFacialImageData(normalized, overrides);
            } catch (error) {
                console.warn('Optimización facial fallida, usando imagen original', error);
                finalImage = normalized;
            }
        }

        finalImage = ensureDataUrl(finalImage) || normalized;
        finalImage = sanitizeDataUrl(finalImage);

        capture.optimizedImage = finalImage;
        capture.originalBytes = originalBytes;
        capture.optimizedBytes = estimateDataUrlBytes(finalImage);

        optimizedBytesTotal += capture.optimizedBytes;
        preparedImages.push(finalImage);
    }

    if (preparedImages.length > 0) {
        console.log('📦 Payload facial preparado', {
            capturas: preparedImages.length,
            tamanoOriginalKB: Math.round((originalBytesTotal / 1024) * 10) / 10,
            tamanoOptimizadoKB: Math.round((optimizedBytesTotal / 1024) * 10) / 10
        });
    }

    return preparedImages.map((image) => sanitizeDataUrl(image));
}

// Estado de dispositivos
let deviceStatus = {
    facial: { connected: false, available: false, lastCheck: null },
    fingerprint: { connected: false, available: false, lastCheck: null },
    rfid: { connected: false, available: false, lastCheck: null }
};

// Estado del enrolamiento
let enrollmentStatus = {
    facial: { completed: false, samples: 0, quality: 0 },
    fingerprint: { completed: false, samples: 0, quality: 0, fingerType: null },
    rfid: { completed: false, uid: null, type: null }
};

// Elementos del DOM
const elements = {
    totalEmployees: document.getElementById('totalEmployees'),
    enrolledCount: document.getElementById('enrolledCount'),
    pendingCount: document.getElementById('pendingCount'),
    enrollmentPercentage: document.getElementById('enrollmentPercentage'),
    employeeTableBody: document.getElementById('employeeTableBody'),
    paginationContainer: document.getElementById('paginationContainer'),
    filtroSede: document.getElementById('filtro_sede'),
    filtroEstablecimiento: document.getElementById('filtro_establecimiento'),
    filtroEstado: document.getElementById('filtro_estado'),
    busquedaEmpleado: document.getElementById('busqueda_empleado'),
    btnBuscarEmpleados: document.getElementById('btnBuscarEmpleados'),
    btnLimpiarFiltros: document.getElementById('btnLimpiarFiltros'),
    btnRefreshStats: document.getElementById('btnRefreshStats')
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded event fired');
    console.log('Initializing biometric enrollment system...');
    initializeBiometricEnrollment();
});

/**
 * Inicializar el sistema de enrolamiento
 */
async function initializeBiometricEnrollment() {
    try {
        console.log('Inicializando sistema de enrolamiento biométrico...');

        // Verificar que los elementos del DOM existen
        if (!elements.employeeTableBody) {
            console.error('Elemento employeeTableBody no encontrado');
            return;
        }

        // Cargar datos iniciales primero (prioridad alta)
        console.log('Cargando sedes...');
        await loadSedes();

    console.log('Cargando establecimientos...');
    const initialSede = elements.filtroSede ? elements.filtroSede.value : null;
    await loadEstablecimientos(initialSede);

        console.log('Cargando empleados...');
        await loadEmployeeData();

        // Configurar event listeners
        console.log('Configurando event listeners...');
        setupEventListeners();

        // Inicializar modelos de IA en segundo plano (no bloqueante)
        initializeFaceDetection().catch(error => {
            console.warn('Error al inicializar detección facial:', error);
        });

        console.log('Sistema de enrolamiento biométrico inicializado correctamente');
    } catch (error) {
        console.error('Error al inicializar el sistema:', error);
        showNotification('Error al cargar el sistema de enrolamiento', 'error');

        // Mostrar mensaje de error en la tabla
        if (elements.employeeTableBody) {
            elements.employeeTableBody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <p class="text-danger">Error al inicializar el sistema</p>
                        <small class="text-muted">${error.message}</small>
                    </td>
                </tr>
            `;
        }
    }
}

/**
 * Inicializar modelos de detección facial con integración Python
 */
async function initializeFaceDetection() {
    try {
        console.log('Face detection initialization deferred');

        // Verificar conexión con servicio Python
        const pythonServiceAvailable = await checkPythonService();

        if (pythonServiceAvailable) {
            console.log('✅ Python service available');
            showNotification('Face recognition system initialized with backend', 'success');
        } else {
            console.log('⚠️ Python service not available');
            showNotification('Using local processing - consider starting Python service', 'warning');

            // Cargar TensorFlow.js dinámicamente solo si es necesario
            loadTensorFlowJS().catch(error => {
                console.warn('Error loading TensorFlow.js:', error);
            });
        }

    } catch (error) {
        console.warn('Error initializing face detection:', error);
        showNotification('Error initializing face detection system', 'warning');
    }
}

/**
 * Cargar TensorFlow.js dinámicamente
 */
async function loadTensorFlowJS() {
    return new Promise((resolve, reject) => {
        // Cargar TensorFlow.js
        const tfScript = document.createElement('script');
        tfScript.src = 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.8.0/dist/tf.min.js';
        tfScript.onload = () => {
            // Cargar BlazeFace
            const blazefaceScript = document.createElement('script');
            blazefaceScript.src = 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.js';
            blazefaceScript.onload = () => {
                // Cargar FaceMesh
                const facemeshScript = document.createElement('script');
                facemeshScript.src = 'https://cdn.jsdelivr.net/npm/@tensorflow-models/facemesh@0.0.5/dist/facemesh.js';
                facemeshScript.onload = resolve;
                facemeshScript.onerror = reject;
                document.head.appendChild(facemeshScript);
            };
            blazefaceScript.onerror = reject;
            document.head.appendChild(blazefaceScript);
        };
        tfScript.onerror = reject;
        document.head.appendChild(tfScript);
    });
}

/**
 * Verificar disponibilidad del servicio Python
 */
async function checkPythonService() {
    try {
        const healthTarget = resolvePythonServiceHealthTarget();
        const response = await pythonServiceFetch(healthTarget, {
            method: 'GET',
            timeoutSeconds: 5
        });

        if (response.ok) {
            const data = await response.json();
            return data.status === 'healthy';
        }
        return false;
    } catch (error) {
        console.log('Servicio Python no disponible:', error.message);
        return false;
    }
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Filtros
    if (elements.btnBuscarEmpleados) {
        elements.btnBuscarEmpleados.addEventListener('click', applyFilters);
    }

    if (elements.btnLimpiarFiltros) {
        elements.btnLimpiarFiltros.addEventListener('click', clearFilters);
    }

    if (elements.btnRefreshStats) {
        elements.btnRefreshStats.addEventListener('click', refreshData);
    }

    // Búsqueda por formulario (no en tiempo real)
    if (elements.busquedaEmpleado) {
        // Prevenir envío del formulario al presionar Enter
        elements.busquedaEmpleado.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevenir envío del formulario
                applyFilters();
            }
        });
    }

    // Filtros de dropdown
    if (elements.filtroSede) {
        elements.filtroSede.addEventListener('change', function() {
            const sedeId = this.value;
            loadEstablecimientos(sedeId); // Recargar establecimientos
            applyFilters(); // Aplicar filtros
        });
    }

    if (elements.filtroEstablecimiento) {
        elements.filtroEstablecimiento.addEventListener('change', applyFilters);
    }

    if (elements.filtroEstado) {
        elements.filtroEstado.addEventListener('change', applyFilters);
    }

    // Event listeners para el modal de enrolamiento
    setupEnrollmentModalListeners();
}

/**
 * Configurar event listeners del modal de enrolamiento
 */
function setupEnrollmentModalListeners() {
    // Facial
    document.getElementById('startFaceCamera')?.addEventListener('click', startFaceCamera);
    document.getElementById('stopFaceCamera')?.addEventListener('click', stopFaceCamera);
    document.getElementById('captureFace')?.addEventListener('click', captureFace);

    // Fingerprint
    document.getElementById('startFingerprint')?.addEventListener('click', startFingerprintScanner);
    document.getElementById('captureFingerprint')?.addEventListener('click', captureFingerprint);
    document.getElementById('stopFingerprint')?.addEventListener('click', stopFingerprintScanner);

    // RFID
    document.getElementById('startRfid')?.addEventListener('click', startRfidScanner);
    document.getElementById('captureRfid')?.addEventListener('click', captureRfid);
    document.getElementById('stopRfid')?.addEventListener('click', stopRfidScanner);

    // Guardar enrolamiento
    document.getElementById('saveEnrollment')?.addEventListener('click', saveEnrollment);
}

/**
 * Cargar datos de empleados con estado biométrico
 */
async function loadEmployeeData() {
    try {
        console.log('loadEmployeeData called with page:', currentPage, 'limit:', currentLimit, 'filters:', currentFilters);

        showLoadingState();

        const params = new URLSearchParams({
            page: currentPage,
            limit: currentLimit,
            ...currentFilters
        });

        const url = `api/biometric/get-employees-biometric.php?${params.toString()}`;
        console.log('Making request to:', url);

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        console.log('Response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
            console.log('Data loaded successfully, rendering table...');
            console.log('Employees received:', data.employees.length);
            console.log('First employee sample:', data.employees[0]);

            renderEmployeeTable(data.employees);
            updatePaginationInfo(data.pagination);
            renderPaginationButtons(data.pagination);

            // Actualizar estadísticas desde la API
            updateStatisticsFromAPI(data.stats);

            console.log(`Cargados ${data.employees.length} empleados de ${data.total} total`);
            console.log('EmployeeData after load:', employeeData.length, 'employees');
        } else {
            throw new Error(data.message || 'Error al cargar empleados');
        }
    } catch (error) {
        console.error('Error al cargar empleados:', error);
        showErrorState(error.message);
    }
}

/**
 * Cargar sedes
 */
async function loadSedes() {
    try {
        const response = await fetch('api/get-sedes.php');
        const data = await response.json();

        if (data.success && elements.filtroSede) {
            elements.filtroSede.innerHTML = '<option value="">Todas las sedes</option>';
            data.sedes.forEach(sede => {
                elements.filtroSede.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
            });
        }
    } catch (error) {
        console.error('Error al cargar sedes:', error);
    }
}

/**
 * Cargar establecimientos
 */
async function loadEstablecimientos(sedeId = null) {
    if (!elements.filtroEstablecimiento) {
        return;
    }

    elements.filtroEstablecimiento.innerHTML = '<option value="">Todos los establecimientos</option>';

    if (!sedeId) {
        return;
    }

    const cacheKey = String(sedeId);

    if (establecimientosCache[cacheKey]) {
        populateEstablecimientosOptions(establecimientosCache[cacheKey]);
        return;
    }

    try {
        const response = await fetch(`api/get-establecimientos.php?sede_id=${sedeId}`);
        const data = await response.json();

        if (data.success && Array.isArray(data.establecimientos)) {
            establecimientosCache[cacheKey] = data.establecimientos;
            populateEstablecimientosOptions(data.establecimientos);
        }
    } catch (error) {
        console.error('Error al cargar establecimientos:', error);
    }
}

function populateEstablecimientosOptions(establecimientos) {
    if (!elements.filtroEstablecimiento) {
        return;
    }

    establecimientos.forEach(est => {
        elements.filtroEstablecimiento.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
    });
}

function showLoadingState() {
    const tbody = elements.employeeTableBody;
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </td>
            </tr>
        `;
    }
}

function showErrorState(message) {
    const tbody = elements.employeeTableBody;
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error al cargar empleados: ${message}
                </td>
            </tr>
        `;
    }
}

function renderEmployeeTable(data) {
    const tbody = elements.employeeTableBody;

    if (!tbody) return;

    tbody.innerHTML = '';

    if (!data.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data-state">
                    <i class="fas fa-users"></i>
                    No se encontraron empleados con los filtros aplicados
                </td>
            </tr>
        `;
        return;
    }

    // Guardar los datos de empleados para que openEnrollmentModal pueda acceder a ellos
    employeeData = data;
    console.log('Employee data saved:', employeeData.length, 'employees');

    data.forEach(employee => {
        const row = createEmployeeRow(employee);
        tbody.appendChild(row);
    });
}

function updatePaginationInfo(pagination) {
    const info = document.getElementById('paginationInfo');
    if (info && pagination) {
        const start = ((pagination.current_page - 1) * pagination.limit) + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);

        info.textContent = `Mostrando ${start} - ${end} de ${pagination.total_records} empleados`;

        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
        totalEmployees = pagination.total_records;
    }
}

function renderPaginationButtons(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container || !pagination) return;

    let buttonsHTML = '';

    // Selector de límite
    buttonsHTML += `
        <div class="pagination-controls">
            <div class="limit-selector">
                <label>Mostrar:</label>
                <select id="limitSelector" onchange="changeLimit(this.value)">
                    <option value="10" ${pagination.limit == 10 ? 'selected' : ''}>10</option>
                    <option value="15" ${pagination.limit == 15 ? 'selected' : ''}>15</option>
                    <option value="20" ${pagination.limit == 20 ? 'selected' : ''}>20</option>
                    <option value="30" ${pagination.limit == 30 ? 'selected' : ''}>30</option>
                    <option value="40" ${pagination.limit == 40 ? 'selected' : ''}>40</option>
                    <option value="50" ${pagination.limit == 50 ? 'selected' : ''}>50</option>
                </select>
            </div>
            <div class="pagination-buttons">
    `;

    // Botón anterior
    if (pagination.has_prev) {
        buttonsHTML += `<button class="btn btn-sm btn-outline-primary" onclick="goToPage(${pagination.current_page - 1})">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>`;
    }

    // Botones de páginas
    const maxButtons = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxButtons / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxButtons - 1);

    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
        buttonsHTML += `<button class="btn btn-sm btn-outline-primary" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        buttonsHTML += `<button class="btn btn-sm ${i === pagination.current_page ? 'btn-primary' : 'btn-outline-primary'}"
                            onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        buttonsHTML += `<button class="btn btn-sm btn-outline-primary" onclick="goToPage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }

    // Botón siguiente
    if (pagination.has_next) {
        buttonsHTML += `<button class="btn btn-sm btn-outline-primary" onclick="goToPage(${pagination.current_page + 1})">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
    }

    buttonsHTML += `
            </div>
        </div>
    `;

    container.innerHTML = buttonsHTML;
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadEmployeeData();
    }
}

function changeLimit(newLimit) {
    currentLimit = parseInt(newLimit);
    currentPage = 1; // Reset to first page when changing limit
    loadEmployeeData();
}

/**
 * Mostrar empleados en la tabla
 */
function displayEmployees() {
    // Esta función ya no es necesaria con el sistema AJAX
    // Los empleados se renderizan directamente en renderEmployeeTable()
}

/**
 * Crear fila de empleado
 */
function createEmployeeRow(employee) {
    const row = document.createElement('tr');

    // Determinar estado biométrico
    const biometricStatus = getBiometricStatus(employee);
    const facialStatus = employee.facial_enrolled ? 'Enrolado' : 'Pendiente';
    const fingerprintStatus = employee.fingerprint_enrolled ? 'Enrolado' : 'Pendiente';
    const rfidStatus = employee.rfid_enrolled ? 'Enrolado' : 'Pendiente';

    row.innerHTML = `
        <td>${employee.CODIGO || employee.IDENTIFICACION || 'N/A'}</td>
        <td>${employee.NOMBRE_COMPLETO || `${employee.NOMBRE || employee.NOMBRES || ''} ${employee.APELLIDO || employee.APELLIDOS || ''}`.trim()}</td>
        <td>${employee.ESTABLECIMIENTO || employee.establecimiento || 'N/A'}</td>
        <td>${employee.SEDE || employee.sede || 'N/A'}</td>
        <td>
            <span class="badge ${getStatusBadgeClass(employee.estado_biometrico || biometricStatus)}">
                ${employee.estado_biometrico || biometricStatus}
            </span>
        </td>
        <td>
            <span class="badge ${facialStatus === 'Enrolado' ? 'bg-success' : 'bg-warning'}">
                ${facialStatus}
            </span>
        </td>
        <td>
            <span class="badge ${fingerprintStatus === 'Enrolado' ? 'bg-success' : 'bg-warning'}">
                ${fingerprintStatus}
            </span>
        </td>
        <td>
            <span class="badge ${rfidStatus === 'Enrolado' ? 'bg-success' : 'bg-warning'}">
                ${rfidStatus}
            </span>
        </td>
        <td>
            <button class="btn btn-sm btn-primary" onclick="openEnrollmentModal(${employee.ID_EMPLEADO || employee.id})">
                <i class="fas fa-fingerprint"></i> Enrolar
            </button>
        </td>
    `;

    return row;
}

/**
 * Obtener estado biométrico del empleado
 */
function getBiometricStatus(employee) {
    // Si ya viene calculado desde la API, usarlo
    if (employee.estado_biometrico) {
        return employee.estado_biometrico.charAt(0).toUpperCase() + employee.estado_biometrico.slice(1);
    }

    // Calcular manualmente
    const facial = employee.facial_enrolled || false;
    const fingerprint = employee.fingerprint_enrolled || false;
    const rfid = employee.rfid_enrolled || false;

    if (facial && fingerprint && rfid) return 'Completo';
    if (facial || fingerprint || rfid) return 'Parcial';
    return 'Pendiente';
}

/**
 * Obtener clase de badge para estado
 */
function getStatusBadgeClass(status) {
    switch (status) {
        case 'Completo': return 'bg-success';
        case 'Parcial': return 'bg-warning';
        default: return 'bg-secondary';
    }
}

/**
 * ⚠️ DEPRECATED: Esta función calcula estadísticas basándose solo en los datos visibles de la página actual.
 * No debe usarse porque no refleja las estadísticas correctas de todos los empleados filtrados.
 * Usar updateStatisticsFromAPI() en su lugar.
 */
function updateStatistics() {
    if (!employeeData.length) return;

    const enrolled = employeeData.filter(emp => emp.facial_enrolled || emp.fingerprint_enrolled || emp.rfid_enrolled).length;
    const pending = totalEmployees - enrolled;
    const percentage = totalEmployees > 0 ? Math.round((enrolled / totalEmployees) * 100) : 0;

    if (elements.totalEmployees) elements.totalEmployees.textContent = totalEmployees;
    if (elements.enrolledCount) elements.enrolledCount.textContent = enrolled;
    if (elements.pendingCount) elements.pendingCount.textContent = pending;
    if (elements.enrollmentPercentage) elements.enrollmentPercentage.textContent = `${percentage}%`;
}

/**
 * Actualizar estadísticas desde la API
 */
function updateStatisticsFromAPI(stats) {
    if (!stats) return;

    if (elements.totalEmployees) elements.totalEmployees.textContent = stats.total_employees || 0;
    if (elements.enrolledCount) elements.enrolledCount.textContent = stats.enrolled_count || 0;
    if (elements.pendingCount) elements.pendingCount.textContent = stats.pending_count || 0;
    if (elements.enrollmentPercentage) elements.enrollmentPercentage.textContent = `${stats.enrollment_percentage || 0}%`;
}

/**
 * Verificar estado de los datos antes de abrir modal
 */
function verifyEmployeeData() {
    console.log('🔍 Verificando estado de employeeData...');
    console.log('employeeData length:', employeeData ? employeeData.length : 'undefined');
    console.log('employeeData type:', typeof employeeData);

    if (!employeeData || !Array.isArray(employeeData)) {
        console.error('❌ employeeData no es un array válido');
        return false;
    }

    if (employeeData.length === 0) {
        console.warn('⚠️ employeeData está vacío');
        return false;
    }

    console.log('✅ employeeData verificado correctamente');
    return true;
}

/**
 * Abrir modal de enrolamiento mejorado
 */
function openEnrollmentModal(employeeId) {
    console.log('Opening enrollment modal for employee:', employeeId);

    // Limpiar estado anterior completamente
    resetEnrollmentState();

    // Verificar que los datos estén disponibles
    if (!verifyEmployeeData()) {
        showNotification('Los datos de empleados no están disponibles. Intentando recargar...', 'warning');
        loadEmployeeData().then(() => {
            setTimeout(() => openEnrollmentModal(employeeId), 1000);
        }).catch(error => {
            console.error('Error reloading data:', error);
            showNotification('Error al recargar datos de empleados', 'error');
        });
        return;
    }

    // Buscar empleado en los datos actuales
    const employee = employeeData.find(emp => {
        const empId = emp.ID_EMPLEADO || emp.id;
        console.log('Comparing:', empId, '==', employeeId, 'result:', empId == employeeId);
        return empId == employeeId;
    });

    if (!employee) {
        console.error('Employee not found:', employeeId);
        showNotification(`Empleado con ID ${employeeId} no encontrado`, 'error');
        return;
    }

    console.log('Employee found:', employee);

    // Llenar información del empleado en el modal
    const employeeCode = employee.CODIGO || employee.IDENTIFICACION || employeeId;
    const cleanEmployeeCode = employeeCode.toString().replace(/^EMP\d+/i, '').trim() || employeeCode;
    const employeeName = employee.NOMBRE_COMPLETO ||
                        `${employee.NOMBRE || employee.NOMBRES || ''} ${employee.APELLIDO || employee.APELLIDOS || ''}`.trim() ||
                        'N/A';
    const employeeEstablishment = employee.ESTABLECIMIENTO || employee.establecimiento || 'N/A';

    // Actualizar elementos del modal
    const codeElement = document.getElementById('modal-employee-code');
    if (codeElement) codeElement.textContent = cleanEmployeeCode;

    const nameElement = document.getElementById('modal-employee-name');
    if (nameElement) nameElement.textContent = employeeName;

    const establishmentElement = document.getElementById('modal-employee-establishment');
    if (establishmentElement) establishmentElement.textContent = employeeEstablishment;

    // Establecer IDs del empleado
    const employeeIdFields = ['current-employee-id', 'hidden_employee_id', 'employee_id'];
    employeeIdFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = employeeId;
        }
    });

    currentEmployeeId = employeeId;

    // Actualizar estados biométricos desde la base de datos
    updateEnrollmentStatus(employee);

    // Limpiar interfaz de dispositivos
    clearDeviceInterface();

    // Mostrar modal
    const modalElement = document.getElementById('enrolamientoBiometricoModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
        modal.show();

        console.log('Modal opened successfully');
    } else {
        console.error('Modal element not found');
        showNotification('Error al abrir el modal de enrolamiento', 'error');
    }
}

/**
 * Limpiar interfaz de dispositivos
 */
function clearDeviceInterface() {
    console.log('🧹 Limpiando interfaz de dispositivos');

    // Limpiar facial
    const facialElements = [
        'face-detection-status',
        'face-detection-confidence',
        'face-quality-score'
    ];

    facialElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            if (id.includes('status')) {
                element.textContent = 'Esperando iniciar cámara';
            } else {
                element.textContent = '0%';
            }
            element.className = element.className.replace('text-success', '').replace('text-danger', '').replace('text-warning', '');
        }
    });

    // Limpiar huellas
    cleanupFingerprintCache();

    // Limpiar RFID
    cleanupRFIDCache();

    // Resetear progreso facial
    updateFacialProgress();
}

/**
 * Actualizar estado del enrolamiento en el modal
 */
function updateEnrollmentStatus(employee) {
    console.log('Updating enrollment status for employee:', employee);

    // Actualizar estado facial basado en datos de la BD
    const facialStatus = employee.facial_enrolled ? 'Enrolado' : 'Pendiente';
    const facialElement = document.getElementById('facial-status');
    if (facialElement) {
        facialElement.textContent = `Facial: ${facialStatus}`;
        facialElement.className = `badge ${facialStatus === 'Enrolado' ? 'bg-success' : 'bg-secondary'}`;
    }

    // Actualizar estado huella basado en datos de la BD
    const fingerprintStatus = employee.fingerprint_enrolled ? 'Enrolado' : 'Pendiente';
    const fingerprintElement = document.getElementById('fingerprint-status');
    if (fingerprintElement) {
        fingerprintElement.textContent = `Huella: ${fingerprintStatus}`;
        fingerprintElement.className = `badge ${fingerprintStatus === 'Enrolado' ? 'bg-success' : 'bg-secondary'}`;
    }

    // Actualizar estado RFID basado en datos de la BD
    const rfidStatus = employee.rfid_enrolled ? 'Enrolado' : 'Pendiente';
    const rfidElement = document.getElementById('rfid-status');
    if (rfidElement) {
        rfidElement.textContent = `RFID: ${rfidStatus}`;
        rfidElement.className = `badge ${rfidStatus === 'Enrolado' ? 'bg-success' : 'bg-secondary'}`;
    }

    // Actualizar estado interno
    enrollmentStatus.facial.completed = employee.facial_enrolled || false;
    enrollmentStatus.fingerprint.completed = employee.fingerprint_enrolled || false;
    enrollmentStatus.rfid.completed = employee.rfid_enrolled || false;

    console.log('Enrollment status updated successfully');
}

// Controladores del servicio Python
let enrollmentSessionId = null;

/**
 * Iniciar sesión de enrolamiento facial
 */
function startFacialEnrollment() {
    console.log('🚀 Iniciando sesión de enrolamiento facial');
    faceCaptures = [];
    enrollmentSessionId = Date.now().toString();
    updateFacialProgress();
}

/**
 * Finalizar sesión de enrolamiento facial
 */
function endFacialEnrollment() {
    console.log('🏁 Finalizando sesión de enrolamiento facial');

    if (faceCaptures.length > 0) {
        // Procesar capturas con el servicio Python
        processFacialCaptures();
    } else {
        console.warn('⚠️ No hay capturas para procesar');
    }

    // Limpiar cache
    cleanupFacialCache();
}

/**
 * Limpiar cache de capturas faciales
 */
function cleanupFacialCache() {
    console.log('🧹 Limpiando cache de capturas faciales');
    faceCaptures = [];
    enrollmentSessionId = null;
    updateFacialProgress();
}

/**
 * Procesar capturas faciales con servicio Python
 */
async function processFacialCaptures() {
    if (faceCaptures.length === 0) return;

    try {
        console.log(`📤 Enviando ${faceCaptures.length} capturas al servicio Python`);

        const employeeId = parseInt(document.getElementById('current-employee-id')?.value);
        if (!employeeId) {
            console.error('❌ No se encontró ID del empleado');
            showNotification('Error: No se pudo identificar al empleado', 'error');
            return;
        }

    const response = await pythonServiceFetch('facial/enroll-multiple', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_id: employeeId,
                images: faceCaptures.map(capture => capture.image), // Extraer solo las imágenes
                session_id: enrollmentSessionId,
                quality_threshold: 0.5
            })
        });

        const result = await response.json();

        if (result.success) {
            console.log('✅ Enrolamiento facial completado:', result);
            showNotification('Enrolamiento facial completado exitosamente', 'success');

            // Actualizar estado de enrolamiento
            enrollmentStatus.facial.completed = true;
            enrollmentStatus.facial.samples = faceCaptures.length;
            enrollmentStatus.facial.quality = Math.max(...faceCaptures.map(c => c.quality));

            // Actualizar estado en la interfaz
            updateFacialEnrollmentStatus(true);

            // Verificar si el enrolamiento completo está listo
            checkEnrollmentCompletion();
        } else {
            console.error('❌ Error en enrolamiento facial:', result.message);
            showNotification(`Error en enrolamiento facial: ${result.message}`, 'error');
            enrollmentStatus.facial.completed = false;
        }
    } catch (error) {
        console.error('❌ Error al procesar capturas:', error);
        showNotification('Error al conectar con el servicio de reconocimiento facial', 'error');
        enrollmentStatus.facial.completed = false;
    }
}

/**
 * Actualizar progreso del enrolamiento facial
 */
function updateFacialProgress() {
    const progressContainer = document.getElementById('facial-progress-container');
    if (!progressContainer) return;

    progressContainer.innerHTML = '';

    const captureCount = faceCaptures.length;

    for (let i = 1; i <= 5; i++) {
        const captureItem = document.createElement('div');
        captureItem.className = `capture-item ${i <= captureCount ? 'completed' : 'pending'}`;
        captureItem.innerHTML = `
            <i class="fas fa-${i <= captureCount ? 'check-circle text-success' : 'circle text-secondary'}"></i>
            <span>Captura ${i}</span>
        `;
        progressContainer.appendChild(captureItem);
    }

    // Actualizar estado de enrolamiento facial
    enrollmentStatus.facial.samples = captureCount;

    if (captureCount >= 5) {
        enrollmentStatus.facial.completed = true;
        checkEnrollmentCompletion();
    }
}

/**
 * Actualizar estado del enrolamiento facial en la interfaz
 */
function updateFacialEnrollmentStatus(success) {
    const facialStatusElement = document.getElementById('facial-status');
    if (!facialStatusElement) {
        console.warn('Elemento facial-status no encontrado');
        return;
    }

    if (success) {
        facialStatusElement.className = 'badge bg-success';
        facialStatusElement.textContent = 'Facial: Enrolado';
        console.log('✅ Estado facial actualizado: Enrolado');
        enrollmentStatus.facial.completed = true;
        checkEnrollmentCompletion();
    } else {
        facialStatusElement.className = 'badge bg-secondary';
        facialStatusElement.textContent = 'Facial: Pendiente';
        console.log('⏳ Estado facial actualizado: Pendiente');
        enrollmentStatus.facial.completed = false;
    }
}

/**
 * Verificar estado del servicio Python con mejor manejo de errores
 */
async function checkPythonServiceStatus() {
    try {
        console.log('🔍 Verificando estado del servicio Python...');

        const healthTarget = resolvePythonServiceHealthTarget();
        const response = await pythonServiceFetch(healthTarget, {
            method: 'GET',
            timeoutSeconds: 5
        });

        if (response.ok) {
            const data = await response.json();
            if (data.status === 'healthy') {
                console.log('✅ Servicio Python disponible');
                deviceStatus.facial.available = true;
                deviceStatus.facial.lastCheck = Date.now();

                // Actualizar indicador visual
                const statusElement = document.getElementById('python-service-status');
                if (statusElement) {
                    statusElement.textContent = '✅ Conectado';
                    statusElement.className = 'text-success';
                }

                showNotification('Servicio de reconocimiento facial conectado', 'success');
                return true;
            } else {
                console.warn('⚠️ Servicio Python reporta estado no saludable:', data);
                deviceStatus.facial.available = false;

                const statusElement = document.getElementById('python-service-status');
                if (statusElement) {
                    statusElement.textContent = '⚠️ Estado no saludable';
                    statusElement.className = 'text-warning';
                }

                showNotification('Servicio Python no está funcionando correctamente', 'warning');
                return false;
            }
        } else {
            console.warn('⚠️ Servicio Python respondió con error:', response.status);
            deviceStatus.facial.available = false;

            const statusElement = document.getElementById('python-service-status');
            if (statusElement) {
                statusElement.textContent = '❌ Error de conexión';
                statusElement.className = 'text-danger';
            }

            showNotification('Servicio Python no disponible', 'warning');
            return false;
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            console.error('⏰ Timeout al verificar servicio Python');
            showNotification('Timeout al conectar con servicio Python', 'warning');
        } else {
            console.error('❌ Error al verificar servicio Python:', error.message);
            showNotification('Error al conectar con servicio Python', 'error');
        }

        deviceStatus.facial.available = false;
        deviceStatus.facial.lastCheck = Date.now();

        const statusElement = document.getElementById('python-service-status');
        if (statusElement) {
            statusElement.textContent = '❌ No disponible (usando detección local)';
            statusElement.className = 'text-warning';
        }

        return false;
    }
}

// Función para resetear el estado del enrolamiento
function resetEnrollmentState() {
    console.log('🔄 Reseteando estado del enrolamiento');

    // Resetear estado de enrolamiento
    enrollmentStatus = {
        facial: { completed: false, samples: 0, quality: 0 },
        fingerprint: { completed: false, samples: 0, quality: 0, fingerType: null },
        rfid: { completed: false, uid: null, type: null }
    };

    // Resetear estado de dispositivos
    deviceStatus = {
        facial: { connected: false, available: false, lastCheck: null },
        fingerprint: { connected: false, available: false, lastCheck: null },
        rfid: { connected: false, available: false, lastCheck: null }
    };

    // Limpiar datos de capturas
    faceCaptures = [];
    currentEmployeeId = null;

    // Resetear botones
    const saveButton = document.getElementById('saveEnrollment');
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.classList.remove('btn-success');
        saveButton.classList.add('btn-secondary');
    }
}

/**
 * Limpiar cache cuando se cierra el modal
 */
function cleanupOnModalClose() {
    console.log('🧹 Limpiando cache al cerrar modal');

    // Limpiar intervalo de verificación del servicio Python
    if (window.pythonServiceCheckInterval) {
        clearInterval(window.pythonServiceCheckInterval);
        window.pythonServiceCheckInterval = null;
        console.log('⏰ Intervalo de verificación del servicio Python detenido');
    }

    // Limpiar capturas faciales
    cleanupFacialCache();

    // Limpiar cache de huellas
    cleanupFingerprintCache();

    // Limpiar cache de RFID
    cleanupRFIDCache();

    // Resetear estado completo
    resetEnrollmentState();

    // Detener streams si existen
    if (faceStream) {
        faceStream.getTracks().forEach(track => track.stop());
        faceStream = null;
    }

    showNotification('Cache limpiado correctamente', 'info');
}

/**
 * Limpiar cache de huellas dactilares
 */
function cleanupFingerprintCache() {
    console.log('🧹 Limpiando cache de huellas dactilares');

    // Resetear elementos de la interfaz
    const elements = [
        'fingerprintSamples',
        'fingerprintQuality',
        'fingerprintProgress'
    ];

    elements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            if (id === 'fingerprintSamples' || id === 'fingerprintQuality') {
                element.textContent = id === 'fingerprintSamples' ? '0' : '0%';
            } else if (id === 'fingerprintProgress') {
                element.style.width = '0%';
            }
        }
    });

    // Resetear botones
    resetFingerprintScanner();

    // Resetear estado
    enrollmentStatus.fingerprint = { completed: false, samples: 0, quality: 0, fingerType: null };
}

/**
 * Limpiar cache de RFID
 */
function cleanupRFIDCache() {
    console.log('🧹 Limpiando cache de RFID');

    // Resetear elementos de la interfaz
    const elements = [
        'rfidUid',
        'rfidType',
        'rfidStatus',
        'rfidProgress'
    ];

    elements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            if (id === 'rfidUid') {
                element.textContent = '-';
            } else if (id === 'rfidType') {
                element.textContent = '-';
            } else if (id === 'rfidStatus') {
                element.textContent = 'Esperando...';
            } else if (id === 'rfidProgress') {
                element.style.width = '0%';
            }
        }
    });

    // Resetear botones
    resetRFIDScanner();

    // Resetear estado
    enrollmentStatus.rfid = { completed: false, uid: null, type: null };
}

function stopFaceCamera() {
    if (faceStream) {
        faceStream.getTracks().forEach(track => track.stop());
        faceStream = null;
    }

    document.getElementById('faceVideo').srcObject = null;
    document.getElementById('startFaceCamera').disabled = false;
    document.getElementById('stopFaceCamera').disabled = true;
    document.getElementById('captureFace').disabled = true;

    document.getElementById('face-detection-status').textContent = 'Cámara detenida';
    document.getElementById('face-detection-confidence').textContent = '0%';
}

async function detectFacesContinuously() {
    const video = document.getElementById('faceVideo');
    const canvas = document.getElementById('faceCanvas');
    const ctx = canvas.getContext('2d');

    if (!faceDetectionModel || !faceStream) return;

    try {
        // Detectar rostros
        const predictions = await faceDetectionModel.estimateFaces(video, false);

        if (predictions.length > 0) {
            const face = predictions[0];
            const confidence = Math.round(face.probability[0] * 100);

            document.getElementById('face-detection-status').textContent = 'Rostro detectado';
            document.getElementById('face-detection-confidence').textContent = `${confidence}%`;

            // Calcular calidad del rostro
            const quality = calculateFaceQuality(face);
            document.getElementById('face-quality-score').textContent = `${quality}%`;

            // Captura automática si calidad es buena
            if (quality >= 90 && faceCaptures.length < 5) {
                await captureFaceAutomatically();
            }
        } else {
            document.getElementById('face-detection-status').textContent = 'No se detecta rostro';
            document.getElementById('face-detection-confidence').textContent = '0%';
            document.getElementById('face-quality-score').textContent = '0%';
        }

        // Continuar detección
        if (faceStream) {
            requestAnimationFrame(detectFacesContinuously);
        }
    } catch (error) {
        console.error('Error en detección facial:', error);
    }
}

function calculateFaceQuality(face) {
    // Calcular calidad basada en el tamaño del rostro y la confianza
    const faceSize = (face.bottomRight[0] - face.topLeft[0]) * (face.bottomRight[1] - face.topLeft[1]);
    const confidence = face.probability[0];

    // Normalizar calidad (0-100)
    const sizeScore = Math.min(faceSize / 10000, 1) * 100;
    const confidenceScore = confidence * 100;

    return Math.round((sizeScore + confidenceScore) / 2);
}

async function captureFaceAutomatically() {
    const video = document.getElementById('faceVideo');
    const canvas = document.getElementById('faceCanvas');
    const ctx = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);

    const imageData = canvas.toDataURL('image/jpeg', 0.9);

    try {
        // Intentar usar servicio Python si está disponible
        const pythonAvailable = await checkPythonService();

        if (pythonAvailable) {
            // Procesar con servicio Python
            const processedData = await processFaceWithPython(imageData);

            if (processedData.success) {
                faceCaptures.push({
                    image: processedData.processed_image || imageData,
                    timestamp: new Date().toISOString(),
                    quality: processedData.quality_score || 85,
                    landmarks: processedData.landmarks,
                    embedding: processedData.embedding
                });

                // Actualizar indicadores de calidad
                document.getElementById('face-detection-confidence').textContent = `${processedData.confidence || 95}%`;
                document.getElementById('face-quality-score').textContent = `${processedData.quality_score || 85}%`;

                updateFaceProgress();
                showNotification(`Captura ${faceCaptures.length}/5 procesada con backend seguro`, 'success');
            } else {
                throw new Error(processedData.message || 'Error en procesamiento Python');
            }
        } else {
            // Fallback a procesamiento local con TensorFlow.js
            const processedData = await processFaceLocally(canvas);

            faceCaptures.push({
                image: imageData,
                timestamp: new Date().toISOString(),
                quality: processedData.quality || 75,
                landmarks: processedData.landmarks
            });

            updateFaceProgress();
            showNotification(`Captura ${faceCaptures.length}/5 procesada localmente`, 'info');
        }

        if (faceCaptures.length >= 5) {
            document.getElementById('face-detection-status').textContent = 'Capturas completadas - Listo para guardar';
            document.getElementById('captureFace').disabled = true;
        }

    } catch (error) {
        console.error('Error al procesar captura facial:', error);
        showNotification('Error al procesar la imagen facial', 'error');
    }
}

/**
 * Procesar imagen facial con servicio Python
 */
async function processFaceWithPython(imageData) {
    try {
        const response = await pythonServiceFetch('process-face', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                image: imageData,
                employee_id: currentEmployeeId,
                action: 'enrollment'
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Error en servicio Python:', error);
        throw error;
    }
}

/**
 * Procesar imagen facial localmente (fallback)
 */
async function processFaceLocally(canvas) {
    try {
        const predictions = await faceDetectionModel.estimateFaces(canvas);

        if (predictions.length === 0) {
            throw new Error('No se detectó ningún rostro');
        }

        const face = predictions[0];
        const landmarks = await faceMeshModel.estimateFaces(canvas);

        // Calcular calidad básica
        const faceSize = face.bottomRight[1] - face.topLeft[1];
        const quality = Math.min(100, Math.max(50, (faceSize / canvas.height) * 100));

        return {
            quality: Math.round(quality),
            landmarks: landmarks.length > 0 ? landmarks[0] : null,
            confidence: face.probability ? Math.round(face.probability[0] * 100) : 80
        };
    } catch (error) {
        console.error('Error en procesamiento local:', error);
        return {
            quality: 50,
            landmarks: null,
            confidence: 50
        };
    }
}

function captureFace() {
    if (faceCaptures.length < 5) {
        captureFaceAutomatically();
    }
}

function updateFaceProgress() {
    const progress = (faceCaptures.length / 5) * 100;
    document.getElementById('faceProgress').style.width = `${progress}%`;
    document.getElementById('faceCaptures').textContent = faceCaptures.length;

    const avgQuality = faceCaptures.length > 0
        ? Math.round(faceCaptures.reduce((sum, cap) => sum + cap.quality, 0) / faceCaptures.length)
        : 0;
    document.getElementById('faceAvgQuality').textContent = `${avgQuality}%`;
}

// Funciones de huella dactilar
async function startFingerprintScanner() {
    try {
        console.log('🔍 Verificando dispositivo de huellas...');

        // Verificar conexión con dispositivo de huellas
        const deviceCheck = await checkFingerprintDevice();

        if (!deviceCheck.available) {
            showDeviceErrorModal('fingerprint', {
                error: 'Device not detected',
                details: deviceCheck
            });
            document.getElementById('fingerprint-status').textContent = 'Dispositivo no encontrado';
            document.getElementById('fingerprint-status').className = 'text-danger';
            return;
        }

        showNotification('✅ Dispositivo de huellas detectado - Iniciando escáner...', 'info');

        document.getElementById('startFingerprint').disabled = true;
        document.getElementById('captureFingerprint').disabled = false;
        document.getElementById('stopFingerprint').disabled = false;

        document.getElementById('fingerprintIcon').style.animation = 'pulse 1s infinite';
        document.getElementById('scannerAnimation').style.display = 'block';

        // Actualizar estado del dispositivo
        deviceStatus.fingerprint.connected = true;
        deviceStatus.fingerprint.lastCheck = new Date();

        // Reiniciar contador de muestras
        document.getElementById('fingerprintSamples').textContent = '0';
        document.getElementById('fingerprintQuality').textContent = '0%';
        document.getElementById('fingerprintProgress').style.width = '0%';

        showNotification('Dispositivo de huellas listo para capturar', 'success');

    } catch (error) {
        console.error('Error al iniciar escáner de huellas:', error);
        showNotification('Error al conectar con dispositivo de huellas', 'error');
        resetFingerprintScanner();
    }
}

function captureFingerprint() {
    // Verificar que el dispositivo esté conectado
    if (!deviceStatus.fingerprint.connected) {
        showNotification('❌ No hay dispositivo de huellas conectado', 'error');
        return;
    }

    const fingerType = document.querySelector('input[name="fingerType"]:checked');
    if (!fingerType) {
        showNotification('⚠️ Seleccione un tipo de dedo', 'warning');
        return;
    }

    const fingerTypeValue = fingerType.value;

    try {
        // Intentar captura real usando servicio Python
        const pythonAvailable = checkPythonService();

        if (pythonAvailable) {
            captureFingerprintWithPython(fingerTypeValue);
        } else {
            // Solo simular en modo desarrollo
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                simulateFingerprintCapture(fingerTypeValue);
            } else {
                throw new Error('Servicio de huellas no disponible');
            }
        }

    } catch (error) {
        console.error('Error al capturar huella:', error);
        showNotification('Error al capturar huella dactilar', 'error');
    }
}

function stopFingerprintScanner() {
    document.getElementById('startFingerprint').disabled = false;
    document.getElementById('captureFingerprint').disabled = true;
    document.getElementById('stopFingerprint').disabled = true;

    document.getElementById('fingerprintIcon').style.animation = 'none';
    document.getElementById('scannerAnimation').style.display = 'none';

    // Actualizar estado del dispositivo
    deviceStatus.fingerprint.connected = false;

    showNotification('Escáner de huellas detenido', 'info');
}

// Funciones auxiliares para huellas
async function checkFingerprintDevice() {
    try {
        // Verificar si hay dispositivos USB conectados que puedan ser lectores de huellas
        if (!navigator.usb) {
            console.log('WebUSB no soportado, intentando método alternativo...');
            // Fallback: intentar conectar con servicio Python
            const pythonAvailable = await checkPythonService();
            if (pythonAvailable) {
                const response = await pythonServiceFetch('devices/fingerprint/status', {
                    method: 'GET',
                    timeoutSeconds: 3
                });
                if (response.ok) {
                    const data = await response.json();
                    deviceStatus.fingerprint.available = data.available || false;
                    deviceStatus.fingerprint.connected = data.connected || false;
                    return data;
                }
            }
            return { available: false, connected: false };
        }

        // Intentar obtener dispositivos USB
        const devices = await navigator.usb.getDevices();
        const fingerprintDevices = devices.filter(device => {
            // IDs comunes de lectores de huellas
            const fingerprintVendorIds = [0x08ff, 0x0483, 0x04b4]; // AuthenTec, STMicroelectronics, etc.
            return fingerprintVendorIds.includes(device.vendorId);
        });

        const available = fingerprintDevices.length > 0;
        deviceStatus.fingerprint.available = available;

        return {
            available: available,
            connected: available,
            devices: fingerprintDevices
        };

    } catch (error) {
        console.error('Error verificando dispositivo de huellas:', error);
        return { available: false, connected: false };
    }
}

async function captureFingerprintWithPython(fingerType) {
    try {
        showNotification(`Capturando huella: ${fingerType}...`, 'info');

        const response = await pythonServiceFetch('fingerprint/capture', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                finger_type: fingerType,
                employee_id: currentEmployeeId,
                timeout: 10
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            // Captura exitosa
            const currentSamples = parseInt(document.getElementById('fingerprintSamples').textContent);
            const newSamples = currentSamples + 1;

            document.getElementById('fingerprintSamples').textContent = newSamples;
            document.getElementById('fingerprintQuality').textContent = `${data.quality || 95}%`;
            document.getElementById('fingerprintProgress').style.width = `${(newSamples / 3) * 100}%`;

            enrollmentStatus.fingerprint.samples = newSamples;
            enrollmentStatus.fingerprint.quality = data.quality || 95;
            enrollmentStatus.fingerprint.fingerType = fingerType;

            showNotification(`✅ Huella ${fingerType} capturada (${newSamples}/3)`, 'success');

            if (newSamples >= 3) {
                enrollmentStatus.fingerprint.completed = true;
                document.getElementById('captureFingerprint').disabled = true;
                showNotification('🎯 Enrolamiento de huella completado', 'success');
                checkEnrollmentCompletion();
            }
        } else {
            throw new Error(data.message || 'Error en captura');
        }

    } catch (error) {
        console.error('Error capturando huella con Python:', error);
        showNotification('Error al capturar huella dactilar', 'error');
    }
}

function simulateFingerprintCapture(fingerType) {
    console.log('🎭 Simulando captura de huella (modo desarrollo)');

    const currentSamples = parseInt(document.getElementById('fingerprintSamples').textContent);
    const newSamples = currentSamples + 1;

    document.getElementById('fingerprintSamples').textContent = newSamples;
    document.getElementById('fingerprintQuality').textContent = '95%';
    document.getElementById('fingerprintProgress').style.width = `${(newSamples / 3) * 100}%`;

    enrollmentStatus.fingerprint.samples = newSamples;
    enrollmentStatus.fingerprint.quality = 95;
    enrollmentStatus.fingerprint.fingerType = fingerType;

    showNotification(`🎭 Huella ${fingerType} simulada (${newSamples}/3)`, 'warning');

    if (newSamples >= 3) {
        enrollmentStatus.fingerprint.completed = true;
        document.getElementById('captureFingerprint').disabled = true;
        showNotification('🎯 Enrolamiento de huella completado (SIMULADO)', 'success');
        checkEnrollmentCompletion();
    }
}

function resetFingerprintScanner() {
    document.getElementById('startFingerprint').disabled = false;
    document.getElementById('captureFingerprint').disabled = true;
    document.getElementById('stopFingerprint').disabled = true;
    document.getElementById('fingerprintIcon').style.animation = 'none';
    document.getElementById('scannerAnimation').style.display = 'none';
    deviceStatus.fingerprint.connected = false;
}

// Funciones RFID mejoradas con verificación de dispositivos
async function startRfidScanner() {
    try {
        console.log('🔍 Verificando dispositivo RFID...');

        // Verificar conexión con dispositivo RFID
        const deviceCheck = await checkRFIDDevice();

        if (!deviceCheck.available) {
            showDeviceErrorModal('rfid', {
                error: 'Device not detected',
                details: deviceCheck
            });
            document.getElementById('rfid-status').textContent = 'Dispositivo no encontrado';
            document.getElementById('rfid-status').className = 'text-danger';
            return;
        }

        showNotification('✅ Dispositivo RFID detectado - Iniciando escáner...', 'info');

        document.getElementById('startRfid').disabled = true;
        document.getElementById('captureRfid').disabled = false;
        document.getElementById('stopRfid').disabled = false;

        document.getElementById('rfidIcon').style.animation = 'pulse 1s infinite';
        document.getElementById('rfidAnimation').style.display = 'block';
        document.getElementById('rfidStatus').textContent = 'Escaneando...';

        // Actualizar estado del dispositivo
        deviceStatus.rfid.connected = true;
        deviceStatus.rfid.lastCheck = new Date();

        // Intentar detección automática de RFID
        await scanForRFIDCard();

    } catch (error) {
        console.error('Error al iniciar escáner RFID:', error);
        showNotification('Error al conectar con dispositivo RFID', 'error');
        resetRFIDScanner();
    }
}

async function checkRFIDDevice() {
    try {
        // Verificar si hay dispositivos USB conectados que puedan ser lectores RFID
        if (!navigator.usb) {
            console.log('WebUSB no soportado, intentando método alternativo...');
            // Fallback: intentar conectar con servicio Python
            const pythonAvailable = await checkPythonService();
            if (pythonAvailable) {
                const response = await pythonServiceFetch('devices/rfid/status', {
                    method: 'GET',
                    timeoutSeconds: 3
                });
                if (response.ok) {
                    const data = await response.json();
                    deviceStatus.rfid.available = data.available || false;
                    deviceStatus.rfid.connected = data.connected || false;
                    return data;
                }
            }
            return { available: false, connected: false };
        }

        // Intentar obtener dispositivos USB
        const devices = await navigator.usb.getDevices();
        const rfidDevices = devices.filter(device => {
            // IDs comunes de lectores RFID
            const rfidVendorIds = [0x072f, 0x04e6, 0x08ff, 0x04b4]; // ACS, SCM, etc.
            return rfidVendorIds.includes(device.vendorId);
        });

        const available = rfidDevices.length > 0;
        deviceStatus.rfid.available = available;

        return {
            available: available,
            connected: available,
            devices: rfidDevices
        };

    } catch (error) {
        console.error('Error verificando dispositivo RFID:', error);
        return { available: false, connected: false };
    }
}

async function scanForRFIDCard() {
    try {
        // Intentar leer tarjeta RFID usando servicio Python
        const pythonAvailable = await checkPythonService();

        if (pythonAvailable) {
            const response = await pythonServiceFetch('rfid/scan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ timeout: 10 })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.uid) {
                    // Tarjeta detectada
                    document.getElementById('rfidUid').textContent = data.uid;
                    document.getElementById('rfidType').textContent = data.card_type || 'MIFARE';
                    document.getElementById('rfidStatus').textContent = 'Detectado';
                    document.getElementById('rfidProgress').style.width = '100%';

                    enrollmentStatus.rfid.completed = true;
                    enrollmentStatus.rfid.uid = data.uid;
                    enrollmentStatus.rfid.type = data.card_type;

                    showNotification(`✅ Tarjeta RFID detectada: ${data.uid}`, 'success');
                    checkEnrollmentCompletion();
                    return;
                }
            }
        }

        // Si no hay servicio Python o falla, intentar simulación solo si está en modo desarrollo
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log('⚠️ Modo desarrollo: simulando detección RFID');
            setTimeout(() => {
                const mockUid = generateMockUID();
                document.getElementById('rfidUid').textContent = mockUid;
                document.getElementById('rfidType').textContent = 'MIFARE Classic';
                document.getElementById('rfidStatus').textContent = 'Detectado (SIMULADO)';
                document.getElementById('rfidProgress').style.width = '100%';

                enrollmentStatus.rfid.completed = true;
                enrollmentStatus.rfid.uid = mockUid;
                enrollmentStatus.rfid.type = 'MIFARE Classic';

                showNotification(`🎭 Tarjeta RFID simulada: ${mockUid}`, 'warning');
                checkEnrollmentCompletion();
            }, 3000);
        } else {
            throw new Error('No se pudo detectar tarjeta RFID');
        }

    } catch (error) {
        console.error('Error escaneando RFID:', error);
        document.getElementById('rfidStatus').textContent = 'Error de detección';
        showNotification('Error al detectar tarjeta RFID', 'error');
    }
}

function generateMockUID() {
    return Array.from({length: 8}, () => Math.floor(Math.random() * 256).toString(16).padStart(2, '0').toUpperCase()).join(':');
}

function captureRfid() {
    const uid = document.getElementById('rfidUid').textContent;
    if (uid !== '-') {
        showNotification(`UID RFID registrado: ${uid}`, 'success');
        document.getElementById('captureRfid').disabled = true;
    } else {
        showNotification('No se ha detectado ninguna tarjeta RFID', 'warning');
    }
}

function stopRfidScanner() {
    document.getElementById('startRfid').disabled = false;
    document.getElementById('captureRfid').disabled = true;
    document.getElementById('stopRfid').disabled = true;

    document.getElementById('rfidIcon').style.animation = 'none';
    document.getElementById('rfidAnimation').style.display = 'none';
    document.getElementById('rfidStatus').textContent = 'Detenido';
    document.getElementById('rfidProgress').style.width = '0%';
    document.getElementById('rfidEnrollmentStatus').textContent = 'No iniciado';

    // Actualizar estado del dispositivo
    deviceStatus.rfid.connected = false;

    showNotification('Escáner RFID detenido', 'info');
}

// Funciones auxiliares para RFID

async function scanForRFIDCard() {
    try {
        // Intentar leer tarjeta RFID usando servicio Python
        const pythonAvailable = await checkPythonService();

        if (pythonAvailable) {
            const response = await pythonServiceFetch('rfid/scan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ timeout: 10 })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.uid) {
                    // Tarjeta detectada
                    document.getElementById('rfidUid').textContent = data.uid;
                    document.getElementById('rfidType').textContent = data.card_type || 'MIFARE';
                    document.getElementById('rfidStatus').textContent = 'Detectado';
                    document.getElementById('rfidProgress').style.width = '100%';

                    enrollmentStatus.rfid.completed = true;
                    enrollmentStatus.rfid.uid = data.uid;
                    enrollmentStatus.rfid.type = data.card_type;

                    showNotification(`✅ Tarjeta RFID detectada: ${data.uid}`, 'success');
                    checkEnrollmentCompletion();
                    return;
                }
            }
        }

        // Si no hay servicio Python o falla, intentar simulación solo si está en modo desarrollo
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log('⚠️ Modo desarrollo: simulando detección RFID');
            setTimeout(() => {
                const mockUid = generateMockUID();
                document.getElementById('rfidUid').textContent = mockUid;
                document.getElementById('rfidType').textContent = 'MIFARE Classic';
                document.getElementById('rfidStatus').textContent = 'Detectado (SIMULADO)';
                document.getElementById('rfidProgress').style.width = '100%';

                enrollmentStatus.rfid.completed = true;
                enrollmentStatus.rfid.uid = mockUid;
                enrollmentStatus.rfid.type = 'MIFARE Classic';

                showNotification(`🎭 Tarjeta RFID simulada: ${mockUid}`, 'warning');
                checkEnrollmentCompletion();
            }, 3000);
        } else {
            throw new Error('No se pudo detectar tarjeta RFID');
        }

    } catch (error) {
        console.error('Error escaneando RFID:', error);
        document.getElementById('rfidStatus').textContent = 'Error de detección';
        showNotification('Error al detectar tarjeta RFID', 'error');
    }
}

function resetRFIDScanner() {
    document.getElementById('startRfid').disabled = false;
    document.getElementById('captureRfid').disabled = true;
    document.getElementById('stopRfid').disabled = true;
    document.getElementById('rfidIcon').style.animation = 'none';
    document.getElementById('rfidAnimation').style.display = 'none';
    document.getElementById('rfidStatus').textContent = 'Detenido';
    document.getElementById('rfidProgress').style.width = '0%';
    deviceStatus.rfid.connected = false;
}

// Función para verificar si el enrolamiento está completo y habilitar guardado automático
function checkEnrollmentCompletion() {
    const hasFacial = enrollmentStatus.facial.completed;
    const hasFingerprint = enrollmentStatus.fingerprint.completed;
    const hasRfid = enrollmentStatus.rfid.completed;

    // Al menos un método debe estar completo para permitir guardar
    const canSave = hasFacial || hasFingerprint || hasRfid;

    const saveButton = document.getElementById('saveEnrollment');
    if (saveButton) {
        saveButton.disabled = !canSave;
        if (canSave) {
            saveButton.classList.remove('btn-secondary');
            saveButton.classList.add('btn-success');
            showNotification('✅ Enrolamiento listo para guardar', 'success');
        } else {
            saveButton.classList.remove('btn-success');
            saveButton.classList.add('btn-secondary');
        }
    }

    console.log('📊 Estado del enrolamiento:', {
        facial: hasFacial,
        fingerprint: hasFingerprint,
        rfid: hasRfid,
        canSave: canSave
    });
}

// Guardar enrolamiento mejorado
async function saveEnrollment() {
    try {
        showNotification('Procesando enrolamiento...', 'info');

        // Usar el estado de enrolamiento actualizado
        const enrollmentData = {
            employee_id: currentEmployeeId,
            facial_data: enrollmentStatus.facial.completed ? faceCaptures : null,
            fingerprint_data: enrollmentStatus.fingerprint.completed ? {
                samples: enrollmentStatus.fingerprint.samples,
                quality: enrollmentStatus.fingerprint.quality,
                finger_type: enrollmentStatus.fingerprint.fingerType
            } : null,
            rfid_data: enrollmentStatus.rfid.completed ? {
                uid: enrollmentStatus.rfid.uid,
                type: enrollmentStatus.rfid.type
            } : null
        };

        // Validar que al menos un método esté enrolado
        const hasFacial = enrollmentData.facial_data && enrollmentData.facial_data.length > 0;
        const hasFingerprint = enrollmentData.fingerprint_data !== null;
        const hasRfid = enrollmentData.rfid_data !== null;

        if (!hasFacial && !hasFingerprint && !hasRfid) {
            showNotification('Debe completar al menos un método de enrolamiento', 'warning');
            return;
        }

        console.log('💾 Datos de enrolamiento a guardar:', enrollmentData);

        // Intentar usar servicio Python si está disponible
        const pythonAvailable = await checkPythonService();
        let result;

        if (pythonAvailable && hasFacial) {
            // Procesar enrolamiento facial con Python
            result = await enrollWithPython(enrollmentData);
        } else {
            // Fallback a API PHP
            result = await enrollWithPHP(enrollmentData);
        }

        if (result.success) {
            showEnrollmentConfirmation(enrollmentData);
            bootstrap.Modal.getInstance(document.getElementById('enrolamientoBiometricoModal')).hide();

            // Limpiar estado después de guardar exitosamente
            resetEnrollmentState();

            await refreshData();
            showNotification('Enrolamiento completado exitosamente', 'success');
        } else {
            throw new Error(result.message || 'Error al guardar enrolamiento');
        }

    } catch (error) {
        console.error('Error al guardar enrolamiento:', error);
        showNotification('Error al guardar el enrolamiento: ' + error.message, 'error');
    }
}

/**
 * Enrolar usando servicio Python con endpoints correctos
 */
async function enrollWithPython(enrollmentData) {
    try {
        const results = [];

        // Procesar enrolamiento facial si hay datos
        if (enrollmentData.facial_data && enrollmentData.facial_data.length > 0) {
            console.log('📤 Enviando datos faciales al servicio Python...');

            const optimizedImages = await prepareFacialImagesForUpload(enrollmentData.facial_data);
            if (!optimizedImages || optimizedImages.length === 0) {
                throw new Error('No hay capturas faciales válidas para enviar al servicio Python');
            }

            const facialResponse = await pythonServiceFetch('facial/enroll-multiple', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: enrollmentData.employee_id,
                    images: optimizedImages,
                    session_id: enrollmentSessionId,
                    quality_threshold: 0.5
                })
            });

            if (!facialResponse.ok) {
                throw new Error(`HTTP ${facialResponse.status} en enrolamiento facial`);
            }

            const facialResult = await facialResponse.json();
            results.push({ type: 'facial', result: facialResult });
            console.log('✅ Enrolamiento facial completado:', facialResult);
        }

        // Procesar enrolamiento de huellas si hay datos
        if (enrollmentData.fingerprint_data) {
            console.log('📤 Enviando datos de huellas al servicio Python...');

            const fingerprintResponse = await pythonServiceFetch('fingerprint/enroll', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: enrollmentData.employee_id,
                    fingerprint_data: enrollmentData.fingerprint_data,
                    finger_type: enrollmentData.fingerprint_data.finger_type || 'right_index'
                })
            });

            if (!fingerprintResponse.ok) {
                throw new Error(`HTTP ${fingerprintResponse.status} en enrolamiento de huellas`);
            }

            const fingerprintResult = await fingerprintResponse.json();
            results.push({ type: 'fingerprint', result: fingerprintResult });
            console.log('✅ Enrolamiento de huellas completado:', fingerprintResult);
        }

        // Procesar enrolamiento RFID si hay datos
        if (enrollmentData.rfid_data) {
            console.log('📤 Enviando datos RFID al servicio Python...');

            const rfidResponse = await pythonServiceFetch('rfid/enroll', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: enrollmentData.employee_id,
                    rfid_data: enrollmentData.rfid_data
                })
            });

            if (!rfidResponse.ok) {
                throw new Error(`HTTP ${rfidResponse.status} en enrolamiento RFID`);
            }

            const rfidResult = await rfidResponse.json();
            results.push({ type: 'rfid', result: rfidResult });
            console.log('✅ Enrolamiento RFID completado:', rfidResult);
        }

        // Verificar que al menos un enrolamiento fue exitoso
        const successfulEnrollments = results.filter(r => r.result.success);
        if (successfulEnrollments.length === 0) {
            throw new Error('Ningún método de enrolamiento fue exitoso');
        }

        return {
            success: true,
            message: `Enrolamiento completado: ${successfulEnrollments.map(r => r.type).join(', ')}`,
            results: results
        };

    } catch (error) {
        console.error('❌ Error en enrolamiento Python:', error);
        throw error;
    }
}

/**
 * Enrolar usando API PHP (fallback)
 */
async function enrollWithPHP(enrollmentData) {
    try {
        const response = await fetch('api/biometric/enroll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(enrollmentData)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Error en enrolamiento PHP:', error);
        throw error;
    }
}

function showEnrollmentConfirmation(data) {
    const summary = document.getElementById('enrollmentSummary');
    summary.innerHTML = '';

    if (data.facial_data && data.facial_data.length > 0) {
        summary.innerHTML += `<p><i class="fas fa-camera text-success"></i> Reconocimiento Facial: ${data.facial_data.length} capturas</p>`;
    }

    if (data.fingerprint_data && data.fingerprint_data.samples >= 3) {
        summary.innerHTML += `<p><i class="fas fa-fingerprint text-success"></i> Huella Dactilar: ${data.fingerprint_data.samples} muestras</p>`;
    }

    if (data.rfid_data && data.rfid_data.uid) {
        summary.innerHTML += `<p><i class="fas fa-id-card text-success"></i> RFID: ${data.rfid_data.uid}</p>`;
    }

    const modal = new bootstrap.Modal(document.getElementById('enrollmentConfirmationModal'));
    modal.show();
}

// Funciones de utilidad
function applyFilters() {
    // Recopilar filtros actuales
    currentFilters = {};

    if (elements.filtroSede && elements.filtroSede.value) {
        currentFilters.sede = elements.filtroSede.value;
    }
    if (elements.filtroEstablecimiento && elements.filtroEstablecimiento.value) {
        currentFilters.establecimiento = elements.filtroEstablecimiento.value;
    }
    if (elements.filtroEstado && elements.filtroEstado.value) {
        currentFilters.estado = elements.filtroEstado.value;
    }
    if (elements.busquedaEmpleado && elements.busquedaEmpleado.value.trim()) {
        currentFilters.nombre = elements.busquedaEmpleado.value.trim();
    }

    // Reset a primera página cuando se aplican filtros
    currentPage = 1;

    // Cargar datos con filtros aplicados
    loadEmployeeData();
}

function clearFilters() {
    if (elements.filtroSede) elements.filtroSede.value = '';
    if (elements.filtroEstablecimiento) elements.filtroEstablecimiento.value = '';
    if (elements.filtroEstado) elements.filtroEstado.value = '';
    if (elements.busquedaEmpleado) elements.busquedaEmpleado.value = '';

    loadEstablecimientos(null);

    // Limpiar filtros y resetear paginación
    currentFilters = {};
    currentPage = 1;

    // Recargar datos sin filtros
    loadEmployeeData();
}

async function refreshData() {
    await loadEmployeeData();
}

function updatePagination() {
    // Esta función ya no es necesaria con el sistema AJAX
    // La paginación se maneja en renderPaginationButtons()
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Funciones de cámara facial mejoradas con servicio Python
async function startFaceCamera() {
    try {
        console.log('📹 Iniciando cámara facial con servicio Python');

        const video = document.getElementById('faceVideo');
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 1280, height: 720, facingMode: 'user' }
        });

        video.srcObject = stream;
        faceStream = stream;

        // Iniciar sesión de enrolamiento
        startFacialEnrollment();

        document.getElementById('startFaceCamera').disabled = true;
        document.getElementById('stopFaceCamera').disabled = false;
        document.getElementById('captureFace').disabled = false;

        document.getElementById('face-detection-status').textContent = 'Cámara iniciada - Detectando rostro...';

        // Iniciar detección continua con servicio Python
        detectFacesWithPythonService();

        showNotification('Cámara iniciada correctamente - Usando reconocimiento avanzado', 'success');
    } catch (error) {
        console.error('Error al iniciar cámara:', error);
        showNotification('Error al acceder a la cámara', 'error');
    }
}

function stopFaceCamera() {
    if (faceStream) {
        faceStream.getTracks().forEach(track => track.stop());
        faceStream = null;
    }

    // Finalizar sesión de enrolamiento
    endFacialEnrollment();

    document.getElementById('faceVideo').srcObject = null;
    document.getElementById('startFaceCamera').disabled = false;
    document.getElementById('stopFaceCamera').disabled = true;
    document.getElementById('captureFace').disabled = true;

    document.getElementById('face-detection-status').textContent = 'Cámara detenida';
    document.getElementById('face-detection-confidence').textContent = '0%';
    document.getElementById('face-quality-score').textContent = '0%';
}

/**
 * Detectar rostros usando el servicio Python
 */
async function detectFacesWithPythonService() {
    const video = document.getElementById('faceVideo');
    const canvas = document.getElementById('faceCanvas');
    const ctx = canvas.getContext('2d');

    if (!faceStream) return;

    try {
        // Capturar frame del video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);

        // Convertir a base64
        const imageData = canvas.toDataURL('image/jpeg', 0.9);

        // Medir tiempo de procesamiento
        const startTime = performance.now();

        // Enviar al servicio Python para análisis
        const response = await pythonServiceFetch('facial/extract', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                image_data: imageData
            })
        });

        const processingTime = Math.round(performance.now() - startTime);

        const result = await response.json();

        if (result.success && result.face_detected && result.face_count > 0) {
            // Rostro detectado correctamente
            const confidence = Math.round((result.quality_score || 0) * 100);
            const quality = Math.round((result.quality_score || 0) * 100);

            // Actualizar estado con información detallada
            document.getElementById('face-detection-status').textContent =
                `✅ Rostro detectado (${result.face_count} ${result.face_count === 1 ? 'rostro' : 'rostros'})`;
            document.getElementById('face-detection-confidence').textContent = `${confidence}%`;
            document.getElementById('face-quality-score').textContent = `${quality}%`;

            // Cambiar colores según calidad
            updateQualityIndicators(quality);

            // Captura automática si calidad >= 80%
            if (quality >= 80 && faceCaptures.length < 5) {
                await captureFaceWithPython(result, imageData);
            }
        } else {
            // No se detectó rostro
            document.getElementById('face-detection-status').textContent = '❌ No se detecta rostro';
            document.getElementById('face-detection-confidence').textContent = '0%';
            document.getElementById('face-quality-score').textContent = '0%';

            // Resetear indicadores de calidad
            updateQualityIndicators(0);
        }

        // Actualizar información de debug
        updateDebugInfo(result, processingTime);

        // Continuar detección
        if (faceStream) {
            requestAnimationFrame(detectFacesWithPythonService);
        }
    } catch (error) {
        console.error('Error en detección facial con Python:', error);

        // Mostrar error específico
        if (error.message && error.message.includes('422')) {
            document.getElementById('face-detection-status').textContent = '❌ Error en formato de datos';
        } else if (error.message && error.message.includes('500')) {
            document.getElementById('face-detection-status').textContent = '❌ Error interno del servidor';
        } else {
            document.getElementById('face-detection-status').textContent = '⚠️ Servicio no disponible - usando detección local';
        }

        document.getElementById('face-detection-confidence').textContent = '0%';
        document.getElementById('face-quality-score').textContent = '0%';
        updateQualityIndicators(0);

        if (faceStream) {
            requestAnimationFrame(detectFacesWithPythonService);
        }
    }
}

/**
 * Actualizar indicadores visuales de calidad
 */
function updateQualityIndicators(quality) {
    const statusElement = document.getElementById('face-detection-status');
    const confidenceElement = document.getElementById('face-detection-confidence');
    const qualityElement = document.getElementById('face-quality-score');

    // Resetear clases anteriores
    statusElement.className = '';
    confidenceElement.className = '';
    qualityElement.className = '';

    if (quality >= 90) {
        // Excelente calidad
        statusElement.className = 'text-success fw-bold';
        confidenceElement.className = 'text-success fw-bold';
        qualityElement.className = 'text-success fw-bold';
    } else if (quality >= 70) {
        // Buena calidad
        statusElement.className = 'text-warning fw-bold';
        confidenceElement.className = 'text-warning fw-bold';
        qualityElement.className = 'text-warning fw-bold';
    } else if (quality >= 50) {
        // Calidad regular
        statusElement.className = 'text-info fw-bold';
        confidenceElement.className = 'text-info fw-bold';
        qualityElement.className = 'text-info fw-bold';
    } else if (quality > 0) {
        // Calidad baja
        statusElement.className = 'text-danger fw-bold';
        confidenceElement.className = 'text-danger fw-bold';
        qualityElement.className = 'text-danger fw-bold';
    } else {
        // Sin detección
        statusElement.className = 'text-muted';
        confidenceElement.className = 'text-muted';
        qualityElement.className = 'text-muted';
    }
}

/**
 * Calcular calidad del rostro desde datos del servicio Python
 */
function calculateFaceQualityFromPython(face) {
    // Esta función ya no se usa ya que el servicio Python proporciona quality_score directamente
    // Se mantiene por compatibilidad
    return Math.round((face.quality_score || 0) * 100);
}

/**
 * Mostrar información de debug en tiempo real
 */
function updateDebugInfo(result, processingTime) {
    const debugElement = document.getElementById('debug-info');
    if (debugElement) {
        const debugData = {
            timestamp: new Date().toLocaleTimeString(),
            processing_time: `${processingTime}ms`,
            face_detected: result.face_detected,
            face_count: result.face_count,
            quality_score: result.quality_score,
            embedding_length: result.embedding ? result.embedding.length : 0,
            service_message: result.message
        };

        debugElement.innerHTML = `
            <small class="text-muted">
                <strong>Debug Info:</strong><br>
                ⏱️ Tiempo: ${debugData.processing_time}<br>
                👤 Rostros: ${debugData.face_count}<br>
                📊 Calidad: ${(debugData.quality_score * 100).toFixed(1)}%<br>
                📝 Estado: ${debugData.service_message}
            </small>
        `;
    }
}

/**
 * Capturar rostro usando el servicio Python
 */
async function captureFaceWithPython(faceData, imageData) {
    try {
        console.log(`📸 Capturando rostro ${faceCaptures.length + 1}/5`);

        // Agregar captura al array con los datos del servicio Python
        faceCaptures.push({
            image: imageData,
            face_data: faceData,
            timestamp: Date.now(),
            quality: Math.round((faceData.quality_score || 0) * 100)
        });

        // Actualizar progreso
        updateFacialProgress();

        showNotification(`✅ Captura ${faceCaptures.length}/5 procesada con servicio Python`, 'success');

        // Si tenemos 5 capturas, marcar como completado
        if (faceCaptures.length >= 5) {
            console.log('🎯 5 capturas completadas');
            enrollmentStatus.facial.completed = true;
            enrollmentStatus.facial.quality = Math.max(...faceCaptures.map(c => c.quality));
            checkEnrollmentCompletion();
        }
    } catch (error) {
        console.error('Error al capturar rostro:', error);
        showNotification('Error al procesar captura facial', 'error');
    }
}

function showDeviceErrorModal(deviceType, errorDetails = {}) {
    // Crear modal dinámicamente si no existe
    let modal = document.getElementById('deviceErrorModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'deviceErrorModal';
        modal.setAttribute('tabindex', '-1');
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title text-dark">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="deviceErrorTitle">Dispositivo No Detectado</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="deviceErrorContent">
                            <p class="text-muted mb-3">No se pudo detectar el dispositivo solicitado.</p>
                        </div>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb me-2"></i>Recomendaciones:</h6>
                            <ul id="deviceRecommendations" class="mb-0">
                                <li>Verifique que el dispositivo esté conectado correctamente</li>
                                <li>Asegúrese de que los drivers estén instalados</li>
                                <li>Reinicie el dispositivo y vuelva a intentarlo</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cerrar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="retryDeviceConnection()">
                            <i class="fas fa-redo me-1"></i>Reintentar
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Configurar contenido según el tipo de dispositivo
    const titleElement = modal.querySelector('#deviceErrorTitle');
    const contentElement = modal.querySelector('#deviceErrorContent');
    const recommendationsElement = modal.querySelector('#deviceRecommendations');

    let title, content, recommendations;

    switch(deviceType) {
        case 'fingerprint':
            title = 'Lector de Huellas No Detectado';
            content = `
                <div class="text-center mb-3">
                    <i class="fas fa-fingerprint fa-3x text-muted"></i>
                </div>
                <p><strong>Error:</strong> No se pudo detectar ningún lector de huellas dactilares conectado al sistema.</p>
                <p class="text-muted">Esto puede deberse a que el dispositivo no está conectado físicamente o hay un problema de compatibilidad.</p>
            `;
            recommendations = `
                <li>Conecte el lector de huellas a un puerto USB disponible</li>
                <li>Reinicie el navegador y vuelva a intentarlo</li>
                <li>Comuníquese con el servicio de soporte</li>
            `;
            break;

        case 'rfid':
            title = 'Lector RFID No Detectado';
            content = `
                <div class="text-center mb-3">
                    <i class="fas fa-id-card fa-3x text-muted"></i>
                </div>
                <p><strong>Error:</strong> No se pudo detectar ningún lector RFID conectado al sistema.</p>
                <p class="text-muted">Esto puede deberse a que el dispositivo no está conectado físicamente o hay un problema de compatibilidad.</p>
            `;
            recommendations = `
                <li>Conecte el lector RFID a un puerto USB disponible</li>
                <li>Reinicie el navegador y vuelva a intentarlo</li>
                <li>Comuníquese con el servicio de soporte</li>
            `;
            break;

        default:
            title = 'Dispositivo Biométrico No Detectado';
            content = `
                <div class="text-center mb-3">
                    <i class="fas fa-microchip fa-3x text-muted"></i>
                </div>
                <p><strong>Error:</strong> No se pudo detectar el dispositivo biométrico solicitado.</p>
                <p class="text-muted">Verifique la conexión del dispositivo e inténtelo nuevamente.</p>
            `;
            recommendations = `
                <li>Conecte el dispositivo a un puerto USB disponible</li>
                <li>Reinicie el navegador y vuelva a intentarlo</li>
                <li>Comuníquese con el servicio de soporte</li>
            `;
    }

    titleElement.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${title}`;
    contentElement.innerHTML = content;
    recommendationsElement.innerHTML = recommendations;

    // Mostrar el modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Guardar el tipo de dispositivo para el reintento
    modal.dataset.deviceType = deviceType;
}

// Función para reintentar la conexión del dispositivo
function retryDeviceConnection() {
    const modal = document.getElementById('deviceErrorModal');
    const deviceType = modal ? modal.dataset.deviceType : null;

    if (modal) {
        bootstrap.Modal.getInstance(modal).hide();
    }

    if (deviceType === 'fingerprint') {
        startFingerprintScanner();
    } else if (deviceType === 'rfid') {
        startRfidScanner();
    }

    showNotification('Reintentando conexión del dispositivo...', 'info');
}

function showNotification(message, type = 'info') {
    // Implementar sistema de notificaciones mejorado
    console.log(`[${type.toUpperCase()}] ${message}`);

    // Crear notificación visual si existe el contenedor
    const notificationContainer = document.getElementById('notification-container');
    if (notificationContainer) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        notification.innerHTML = `
            <strong>${type === 'error' ? 'Error:' : type === 'success' ? 'Éxito:' : 'Info:'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        notificationContainer.appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Si tienes un sistema de notificaciones externo, úsalo aquí
    // Por ejemplo: toastr, bootstrap toast, etc.
}

// Event listeners para el modal
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('enrolamientoBiometricoModal');

    if (modalElement) {
        // Cuando se abre el modal
        modalElement.addEventListener('show.bs.modal', async function() {
            console.log('📂 Modal de enrolamiento abierto');
            await initializeEnrollmentModal();
        });

        // Cuando se cierra el modal
        modalElement.addEventListener('hide.bs.modal', function() {
            console.log('📂 Modal de enrolamiento cerrado');
            cleanupOnModalClose();
        });
    }
});

/**
 * Inicializar modal de enrolamiento con verificación de servicios
 */
async function initializeEnrollmentModal() {
    try {
        console.log('🚀 Inicializando modal de enrolamiento...');

        // Verificar estado del servicio Python
        await checkPythonServiceStatus();

        // Verificar dispositivos conectados
        await checkAllDevices();

        // Configurar verificación periódica del servicio Python (cada 30 segundos)
        if (window.pythonServiceCheckInterval) {
            clearInterval(window.pythonServiceCheckInterval);
        }
        window.pythonServiceCheckInterval = setInterval(async () => {
            await checkPythonServiceStatus();
        }, 30000);

        console.log('✅ Modal de enrolamiento inicializado');
    } catch (error) {
        console.error('❌ Error al inicializar modal:', error);
        showNotification('Error al inicializar el modal de enrolamiento', 'error');
    }
}

/**
 * Verificar todos los dispositivos biométricos
 */
async function checkAllDevices() {
    console.log('🔍 Verificando dispositivos biométricos...');

    // Verificar dispositivo facial (servicio Python)
    await checkPythonServiceStatus();

    // Verificar dispositivo de huella
    await checkFingerprintDevice();

    // Verificar dispositivo RFID
    await checkRFIDDevice();

    // Actualizar indicadores visuales
    updateDeviceStatusIndicators();
}

/**
 * Actualizar indicadores visuales del estado de dispositivos
 */
function updateDeviceStatusIndicators() {
    // Actualizar indicador facial
    const facialIndicator = document.getElementById('facial-device-status');
    if (facialIndicator) {
        if (deviceStatus.facial.available) {
            facialIndicator.innerHTML = '<i class="fas fa-check-circle text-success"></i> Servicio Python';
        } else {
            facialIndicator.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Servicio Python';
        }
    }

    // Actualizar indicador de huella
    const fingerprintIndicator = document.getElementById('fingerprint-device-status');
    if (fingerprintIndicator) {
        if (deviceStatus.fingerprint.connected) {
            fingerprintIndicator.innerHTML = '<i class="fas fa-check-circle text-success"></i> Huella';
        } else {
            fingerprintIndicator.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Huella';
        }
    }

    // Actualizar indicador RFID
    const rfidIndicator = document.getElementById('rfid-device-status');
    if (rfidIndicator) {
        if (deviceStatus.rfid.connected) {
            rfidIndicator.innerHTML = '<i class="fas fa-check-circle text-success"></i> RFID';
        } else {
            rfidIndicator.innerHTML = '<i class="fas fa-times-circle text-danger"></i> RFID';
        }
    }
}
