/**
 * Script de diagnóstico para el sistema biométrico
 * Detecta y soluciona problemas comunes
 */

// Ejecutar diagnóstico cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Iniciando diagnóstico del sistema biométrico...');
    
    // Verificar componentes críticos
    setTimeout(runDiagnostics, 500);
    
    // Monitorear cambios en el DOM para detectar la apertura del modal
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                const modalElement = document.getElementById('biometricEnrollmentModal');
                if (modalElement && modalElement.classList.contains('show')) {
                    console.log('Modal detectado - ejecutando diagnóstico específico');
                    runModalDiagnostics();
                }
            }
        });
    });
    
    // Configurar el observador
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Registrar evento para el botón de iniciar cámara
    document.body.addEventListener('click', function(event) {
        if (event.target && event.target.id === 'startFaceCamera') {
            console.log('🔍 Botón de iniciar cámara clickeado - verificando elementos');
            setTimeout(checkCameraElements, 100);
        }
    });
});

/**
 * Ejecutar diagnóstico general del sistema
 */
function runDiagnostics() {
    console.group('🔍 Diagnóstico General del Sistema Biométrico');
    
    // Verificar disponibilidad de APIs críticas
    checkCriticalAPIs();
    
    // Verificar carga de scripts
    checkScriptLoading();
    
    // Verificar estructura del DOM
    checkDOMStructure();
    
    console.groupEnd();
}

/**
 * Verificar APIs críticas
 */
function checkCriticalAPIs() {
    console.log('Verificando APIs críticas...');
    
    // Verificar API de cámara
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.error('❌ API MediaDevices no disponible - la cámara no funcionará');
        fixMediaDevicesAPI();
    } else {
        console.log('✅ API MediaDevices disponible');
    }
    
    // Verificar TensorFlow y BlazeFace
    if (typeof tf === 'undefined') {
        console.error('❌ TensorFlow.js no disponible');
        loadTensorFlow();
    } else {
        console.log('✅ TensorFlow.js disponible:', tf.version.tfjs);
    }
    
    if (typeof blazeface === 'undefined') {
        console.error('❌ BlazeFace no disponible');
        loadBlazeFace();
    } else {
        console.log('✅ BlazeFace disponible');
    }
}

/**
 * Verificar carga de scripts
 */
function checkScriptLoading() {
    console.log('Verificando carga de scripts...');
    
    // Verificar scripts críticos
    const requiredScripts = [
        { name: 'biometric-blazeface.js', variable: window.startFaceCamera },
        { name: 'biometric-integration.js', variable: window.openEnrollmentModal || window.initBiometricModal }
    ];
    
    let missingScripts = [];
    
    requiredScripts.forEach(script => {
        if (!script.variable) {
            console.warn(`⚠️ El script ${script.name} podría no estar cargado correctamente`);
            missingScripts.push(script.name);
        } else {
            console.log(`✅ Script ${script.name} cargado`);
        }
    });
    
    // Cargar scripts faltantes
    if (missingScripts.length > 0) {
        console.warn(`⚠️ Intentando cargar ${missingScripts.length} scripts faltantes...`);
        loadMissingScripts(missingScripts);
    }
}

/**
 * Verificar estructura del DOM
 */
function checkDOMStructure() {
    console.log('Verificando estructura del DOM...');
    
    // Verificar modal biométrico
    const modal = document.getElementById('biometricEnrollmentModal');
    if (!modal) {
        console.error('❌ Modal biométrico no encontrado en el DOM');
    } else {
        console.log('✅ Modal biométrico encontrado');
    }
    
    // Verificar componentes críticos si el modal existe
    if (modal) {
        const criticalElements = [
            'faceVideo',
            'faceCanvas',
            'startFaceCamera',
            'stopFaceCamera',
            'face-detection-status'
        ];
        
        criticalElements.forEach(id => {
            const element = document.getElementById(id);
            if (!element) {
                console.warn(`⚠️ Elemento crítico #${id} no encontrado`);
            }
        });
    }
}

/**
 * Diagnóstico específico para el modal biométrico
 */
function runModalDiagnostics() {
    console.group('🔍 Diagnóstico del Modal Biométrico');
    
    // Verificar elementos del modal
    const videoElement = document.getElementById('faceVideo');
    const canvasElement = document.getElementById('faceCanvas');
    const startButton = document.getElementById('startFaceCamera');
    const stopButton = document.getElementById('stopFaceCamera');
    
    if (!videoElement) {
        console.error('❌ Elemento de video no encontrado');
        fixVideoElement();
    } else {
        console.log('✅ Elemento de video encontrado');
    }
    
    if (!canvasElement) {
        console.error('❌ Elemento de canvas no encontrado');
    }
    
    if (!startButton) {
        console.error('❌ Botón de inicio no encontrado');
    } else {
        // Verificar eventos del botón
        const clickHandlers = getEventHandlers(startButton, 'click');
        if (clickHandlers.length === 0) {
            console.warn('⚠️ No hay manejadores de eventos para el botón de inicio');
            fixStartButton(startButton);
        } else {
            console.log('✅ Botón de inicio tiene manejadores de eventos');
        }
    }
    
    // Verificar ID de empleado
    const employeeIdFields = [
        document.getElementById('current-employee-id'),
        document.getElementById('employee_id'),
        document.getElementById('hidden_employee_id')
    ];
    
    let hasEmployeeId = false;
    
    employeeIdFields.forEach(field => {
        if (field && field.value) {
            console.log('✅ ID de empleado encontrado en campo:', field.id);
            hasEmployeeId = true;
        }
    });
    
    if (!hasEmployeeId) {
        console.warn('⚠️ No se encontró ID de empleado en los campos ocultos');
        
        // Verificar si hay ID en el texto del modal
        const codeElement = document.getElementById('modal-employee-code');
        if (codeElement && codeElement.textContent.trim()) {
            console.log('✅ ID de empleado encontrado en el texto del modal');
            // Copiar el ID a los campos ocultos
            copyEmployeeIdToFields(codeElement.textContent.trim());
        } else {
            console.error('❌ No se encontró ID de empleado');
        }
    }
    
    // Verificar nombre y establecimiento
    const nameElement = document.getElementById('modal-employee-name');
    const establishmentElement = document.getElementById('modal-employee-establishment');
    
    if (nameElement && nameElement.textContent === '-') {
        console.warn('⚠️ Nombre de empleado no está establecido');
        fixEmployeeData();
    }
    
    if (establishmentElement && establishmentElement.textContent === '-') {
        console.warn('⚠️ Establecimiento de empleado no está establecido');
    }
    
    console.groupEnd();
}

/**
 * Verificar elementos de la cámara después de hacer clic en el botón
 */
function checkCameraElements() {
    console.group('🔍 Verificando elementos de cámara');
    
    // Verificar elementos críticos para la cámara
    const videoElement = document.getElementById('faceVideo');
    const canvasElement = document.getElementById('faceCanvas');
    const startButton = document.getElementById('startFaceCamera');
    const stopButton = document.getElementById('stopFaceCamera');
    
    if (!videoElement || !canvasElement) {
        console.error('❌ Elementos críticos de cámara no encontrados');
        fixCameraElements();
    } else {
        if (videoElement.srcObject === null) {
            console.warn('⚠️ Elemento de video no tiene srcObject');
        } else {
            console.log('✅ Video tiene srcObject');
        }
    }
    
    if (startButton && startButton.disabled !== true) {
        console.warn('⚠️ Botón de inicio debería estar deshabilitado');
        startButton.disabled = true;
    }
    
    if (stopButton && stopButton.disabled !== false) {
        console.warn('⚠️ Botón de parada debería estar habilitado');
        stopButton.disabled = false;
    }
    
    console.groupEnd();
}

/**
 * Funciones de corrección
 */

// Cargar scripts faltantes
function loadMissingScripts(scripts) {
    scripts.forEach(script => {
        const scriptElement = document.createElement('script');
        scriptElement.src = `assets/js/${script}`;
        document.head.appendChild(scriptElement);
        console.log(`🔄 Intentando cargar: ${script}`);
    });
}

// Corregir API de Media Devices
function fixMediaDevicesAPI() {
    if (!navigator.mediaDevices) {
        navigator.mediaDevices = {};
    }
    
    if (!navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia = function(constraints) {
            const getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
            
            if (!getUserMedia) {
                return Promise.reject(new Error('getUserMedia no está implementado en este navegador'));
            }
            
            return new Promise(function(resolve, reject) {
                getUserMedia.call(navigator, constraints, resolve, reject);
            });
        };
        
        console.log('🔄 API MediaDevices polyfill aplicado');
    }
}

// Cargar TensorFlow.js
function loadTensorFlow() {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.11.0/dist/tf.min.js';
    script.onload = function() {
        console.log('✅ TensorFlow.js cargado dinámicamente');
        loadBlazeFace();
    };
    script.onerror = function() {
        console.error('❌ Error al cargar TensorFlow.js');
    };
    document.head.appendChild(script);
}

// Cargar BlazeFace
function loadBlazeFace() {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js';
    script.onload = function() {
        console.log('✅ BlazeFace cargado dinámicamente');
    };
    script.onerror = function() {
        console.error('❌ Error al cargar BlazeFace');
    };
    document.head.appendChild(script);
}

// Corregir elementos de video
function fixVideoElement() {
    const container = document.querySelector('.camera-container');
    if (container) {
        if (!document.getElementById('faceVideo')) {
            const video = document.createElement('video');
            video.id = 'faceVideo';
            video.autoplay = true;
            video.muted = true;
            container.appendChild(video);
            console.log('🔄 Elemento de video creado dinámicamente');
        }
        
        if (!document.getElementById('faceCanvas')) {
            const canvas = document.createElement('canvas');
            canvas.id = 'faceCanvas';
            container.appendChild(canvas);
            console.log('🔄 Elemento de canvas creado dinámicamente');
        }
    }
}

// Corregir botón de inicio
function fixStartButton(button) {
    if (button && typeof startFaceCamera === 'function') {
        button.addEventListener('click', function() {
            startFaceCamera();
        });
        console.log('🔄 Manejador de eventos añadido al botón de inicio');
    }
}

// Corregir elementos de la cámara
function fixCameraElements() {
    fixVideoElement();
    
    setTimeout(() => {
        if (typeof getDOMElements === 'function') {
            getDOMElements();
        }
    }, 100);
}

// Copiar ID de empleado a los campos ocultos
function copyEmployeeIdToFields(employeeId) {
    const fields = ['current-employee-id', 'employee_id', 'hidden_employee_id'];
    
    fields.forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.value = employeeId;
            console.log(`🔄 ID ${employeeId} copiado al campo ${id}`);
        }
    });
}

// Corregir datos del empleado
function fixEmployeeData() {
    // Intentar buscar el empleado por ID en los datos existentes
    const employeeId = document.getElementById('modal-employee-code')?.textContent.trim();
    
    if (!employeeId) return;
    
    if (typeof getEmployeeById === 'function') {
        const employee = getEmployeeById(employeeId);
        if (employee) {
            updateEmployeeDataInModal(employee);
        }
    } else {
        // Intentar buscar en los datos del empleado globales
        if (typeof employeeData !== 'undefined' && Array.isArray(employeeData)) {
            const employee = employeeData.find(emp => 
                emp.ID_EMPLEADO == employeeId || 
                emp.id_empleado == employeeId || 
                emp.id == employeeId ||
                emp.CODIGO == employeeId ||
                emp.codigo == employeeId
            );
            
            if (employee) {
                updateEmployeeDataInModal(employee);
            }
        }
    }
}

// Actualizar datos del empleado en el modal
function updateEmployeeDataInModal(employee) {
    if (!employee) return;
    
    const nameElement = document.getElementById('modal-employee-name');
    const establishmentElement = document.getElementById('modal-employee-establishment');
    
    if (nameElement) {
        const firstName = employee.NOMBRES || employee.nombres || employee.NOMBRE || employee.nombre || '';
        const lastName = employee.APELLIDOS || employee.apellidos || employee.APELLIDO || employee.apellido || '';
        nameElement.textContent = `${firstName} ${lastName}`.trim() || '-';
        console.log('🔄 Nombre de empleado actualizado');
    }
    
    if (establishmentElement) {
        establishmentElement.textContent = employee.ESTABLECIMIENTO || employee.establecimiento || 
                                          employee.NOMBRE_ESTABLECIMIENTO || employee.nombre_establecimiento ||
                                          employee.SEDE || employee.sede || '-';
        console.log('🔄 Establecimiento de empleado actualizado');
    }
}

// Obtener manejadores de eventos (función auxiliar)
function getEventHandlers(element, eventType) {
    if (!element) return [];
    
    // En navegadores modernos no podemos acceder directamente a los manejadores de eventos
    // Esta es una aproximación basada en si el elemento tiene atributos de eventos
    const hasAttribute = element.hasAttribute(`on${eventType}`);
    const hasProperty = typeof element[`on${eventType}`] === 'function';
    
    return hasAttribute || hasProperty ? [true] : [];
}

// Función global de diagnóstico para llamar desde consola
window.diagnosticoBiometrico = function() {
    runDiagnostics();
    if (document.getElementById('biometricEnrollmentModal')) {
        runModalDiagnostics();
    }
};
