/**
 * Script para el botón de diagnóstico del sistema biométrico
 * Este script añade funcionalidad al botón de diagnóstico
 */

document.addEventListener('DOMContentLoaded', function() {
    // Buscar el botón de diagnóstico
    const diagnosticButton = document.getElementById('btnDiagnostic');
    
    if (diagnosticButton) {
        diagnosticButton.addEventListener('click', function() {
            runSystemDiagnostic();
        });
    }
});

/**
 * Ejecutar diagnóstico completo del sistema
 */
function runSystemDiagnostic() {
    // Mostrar indicador de carga
    const notification = showNotification('Ejecutando diagnóstico del sistema...', 'info', 60000);
    
    // Añadir spinner al botón
    const btnDiagnostic = document.getElementById('btnDiagnostic');
    if (btnDiagnostic) {
        const originalContent = btnDiagnostic.innerHTML;
        btnDiagnostic.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ejecutando...';
        btnDiagnostic.disabled = true;
        
        // Restaurar después del diagnóstico
        setTimeout(function() {
            btnDiagnostic.innerHTML = originalContent;
            btnDiagnostic.disabled = false;
        }, 10000);
    }
    
    // Hacer la llamada al diagnóstico
    fetch('api/biometric/self-diagnostic.php')
        .then(response => response.json())
        .then(data => {
            // Ocultar notificación anterior
            if (notification) {
                notification.remove();
            }
            
            // Procesar resultados
            const success = data.success === true;
            const tests = data.tests || [];
            const recommendations = data.recommendations || [];
            
            // Mostrar resultados
            if (success) {
                showNotification('Diagnóstico completo: Todos los sistemas funcionan correctamente', 'success');
                
                // Refrescar datos si todo está bien
                refreshData();
            } else {
                // Mostrar un modal con los resultados detallados
                showDiagnosticResults(data);
                
                // Mostrar notificación de advertencia
                showNotification('Se encontraron problemas en el sistema. Vea los detalles en la pantalla.', 'warning');
            }
        })
        .catch(error => {
            console.error('Error en el diagnóstico:', error);
            showNotification('Error al ejecutar diagnóstico: ' + error.message, 'error');
        });
}

/**
 * Mostrar resultados detallados del diagnóstico
 */
function showDiagnosticResults(data) {
    // Crear contenido para el modal
    const tests = data.tests || [];
    const recommendations = data.recommendations || [];
    
    // Contar pruebas exitosas y fallidas
    const testsCount = tests.length;
    const passedCount = tests.filter(test => test.success).length;
    const failedCount = testsCount - passedCount;
    
    // Crear HTML para las pruebas
    let testsHtml = '';
    if (tests.length > 0) {
        testsHtml = `
            <div class="table-responsive mt-3">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Prueba</th>
                            <th>Estado</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tests.map(test => `
                            <tr>
                                <td>${test.name}</td>
                                <td>
                                    ${test.success 
                                        ? '<span class="badge bg-success">Éxito</span>' 
                                        : '<span class="badge bg-danger">Error</span>'}
                                </td>
                                <td>${test.message}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    // Crear HTML para recomendaciones
    let recommendationsHtml = '';
    if (recommendations.length > 0) {
        recommendationsHtml = `
            <div class="mt-4">
                <h5>Recomendaciones</h5>
                <ul class="list-group">
                    ${recommendations.map(rec => `
                        <li class="list-group-item list-group-item-${rec.priority === 'high' ? 'danger' : rec.priority === 'medium' ? 'warning' : 'info'}">
                            ${rec.message}
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
    }
    
    // Crear el modal
    const modalHtml = `
        <div class="modal fade" id="diagnosticResultsModal" tabindex="-1" aria-labelledby="diagnosticModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="diagnosticModalLabel">
                            <i class="fas fa-stethoscope"></i> Resultados del diagnóstico
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="diagnostic-summary">
                            <div class="alert ${data.success ? 'alert-success' : 'alert-warning'}">
                                <h4>${data.success ? '✅ Diagnóstico exitoso' : '⚠️ Se detectaron problemas'}</h4>
                                <p>${data.message || 'Se completó el diagnóstico del sistema.'}</p>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">${testsCount}</h5>
                                            <p class="card-text">Pruebas realizadas</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center text-success">
                                            <h5 class="card-title">${passedCount}</h5>
                                            <p class="card-text">Pruebas exitosas</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center ${failedCount > 0 ? 'text-danger' : 'text-muted'}">
                                            <h5 class="card-title">${failedCount}</h5>
                                            <p class="card-text">Pruebas fallidas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            ${testsHtml}
                            ${recommendationsHtml}
                            
                            <div class="mt-4 text-muted">
                                <small>Diagnóstico ejecutado: ${data.timestamp || new Date().toLocaleString()}</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="refreshData()">Refrescar datos</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Añadir el modal al DOM
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer);
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('diagnosticResultsModal'));
    modal.show();
    
    // Limpiar después de cerrar
    document.getElementById('diagnosticResultsModal').addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modalContainer);
    });
}
