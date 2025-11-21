/**
 * Observaciones como labels fijos - Ya no se necesita funcionalidad expandible
 * Las observaciones ahora se muestran como labels completos debajo del texto
 */

function initializeObservaciones() {
    // Función mantenida para compatibilidad, pero ya no es necesaria
    // Las observaciones ahora son labels fijos que muestran todo el contenido
    console.log('✅ Observaciones inicializadas como labels fijos');
}

// Función legacy mantenida para compatibilidad
function toggleObservacion(element) {
    // Ya no se utiliza - las observaciones son labels fijos
    console.log('⚠️ toggleObservacion llamado pero ya no es necesario');
}

// Funciones globales para compatibilidad
window.initializeObservaciones = initializeObservaciones;
window.toggleObservacion = toggleObservacion;