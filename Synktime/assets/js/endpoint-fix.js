// Archivo de corrección para problemas en el endpoint direct-employees.php
console.log('Cargando correcciones para el endpoint...');

// Cuando carga la página, verificamos el estado del endpoint
document.addEventListener('DOMContentLoaded', () => {
    // Esperar a que otros scripts inicialicen
    setTimeout(checkEndpointStatus, 800);
});

// Verificar el estado del endpoint
function checkEndpointStatus() {
    console.log('Verificando estado del endpoint direct-employees.php...');
    
    fetch('api/biometric/direct-employees.php')
        .then(response => {
            if (!response.ok) {
                console.error('❌ El endpoint direct-employees.php no está funcionando');
                activateBackupEndpoint();
            } else {
                console.log('✅ Endpoint direct-employees.php funciona correctamente');
                return response.json();
            }
        })
        .then(data => {
            if (data && data.success && data.data && data.data.length > 0) {
                console.log(`✅ El endpoint devolvió ${data.data.length} empleados correctamente`);
            } else {
                console.warn('⚠️ El endpoint no devolvió datos válidos');
                activateBackupEndpoint();
            }
        })
        .catch(error => {
            console.error('❌ Error al verificar endpoint:', error);
            activateBackupEndpoint();
        });
}

// Activar endpoint de respaldo
function activateBackupEndpoint() {
    console.log('⚠️ Endpoint principal no disponible. Verificar configuración de base de datos.');
    
    // Ya no usamos datos de respaldo, solo reportamos el error
    console.error('❌ API de empleados no disponible. Contacte al administrador del sistema.');
    
    // Mostrar mensaje al usuario si es posible
    if (typeof showNotification === 'function') {
        showNotification('Error: No se pueden cargar los empleados. Contacte al administrador.', 'error');
    }
    
    return false;
}

// Función de diagnóstico ejecutable desde la consola
window.verificarEndpoint = function() {
    checkEndpointStatus();
    return "Verificación de endpoint iniciada. Revisa la consola para detalles.";
};

console.log('Correcciones para el endpoint cargadas correctamente');
