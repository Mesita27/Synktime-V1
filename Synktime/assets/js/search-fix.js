/**
 * Script para corregir el botón de búsqueda
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🛠️ Inicializando corrección del botón de búsqueda...');
    
    // Esperar a que todo el DOM esté completamente cargado
    setTimeout(function() {
        const searchButton = document.getElementById('btnBuscarEmpleados');
        
        if (searchButton) {
            console.log('✅ Encontrado botón de búsqueda - Aplicando corrección directa');
            
            // Agregar un event listener directo con método onclick
            searchButton.onclick = function() {
                console.log('🔍 BOTÓN DE BÚSQUEDA PULSADO - Ejecutando búsqueda inmediata');
                
                // Mostrar indicador de búsqueda en lugar de alerta
                showSearchIndicator();
                
                // Reiniciar paginación
                if (typeof window.currentPage !== 'undefined') {
                    window.currentPage = 1;
                }
                
                // Intentar llamar a la función de carga de datos
                if (typeof window.loadEmployeeData === 'function') {
                    window.loadEmployeeData();
                } else {
                    // Si la función no está disponible, hacer una búsqueda directa
                    buscarEmpleadosDirectamente();
                }
                
                return false; // Evitar comportamiento por defecto
            };
            
            // También escuchar clic con addEventListener como método alternativo
            searchButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('🔍 Click detectado con addEventListener');
                // No hacer nada más, ya que el onclick debería ejecutarse
            });
        } else {
            console.error('❌ NO SE ENCONTRÓ EL BOTÓN DE BÚSQUEDA');
            showNotification({
                type: 'warning',
                message: 'No se encontró el botón de búsqueda, la funcionalidad puede verse afectada'
            });
        }
        
        // Configurar campo de búsqueda para Enter
        const searchField = document.getElementById('busqueda_empleado');
        if (searchField) {
            searchField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    console.log('⌨️ TECLA ENTER PRESIONADA - Ejecutando búsqueda');
                    
                    // Mostrar indicador de búsqueda
                    showSearchIndicator();
                    
                    // Simular clic en el botón de búsqueda si existe
                    const searchBtn = document.getElementById('btnBuscarEmpleados');
                    if (searchBtn) {
                        searchBtn.click();
                    }
                }
            });
        }
    }, 1000);
});

/**
 * Función para buscar empleados directamente si todo lo demás falla
 */
function buscarEmpleadosDirectamente() {
    console.log('🔄 Ejecutando búsqueda directa...');
    
    // Mostrar indicador de búsqueda
    showSearchIndicator();
    
    // Obtener valores de los filtros
    const busqueda = document.getElementById('busqueda_empleado')?.value || '';
    const sede = document.getElementById('filtro_sede')?.value || '';
    const establecimiento = document.getElementById('filtro_establecimiento')?.value || '';
    const estado = document.getElementById('filtro_estado')?.value || '';
    
    // Construir URL con parámetros
    let url = 'api/biometric/enrollment-employees.php?';
    const params = [];
    
    if (busqueda) params.push(`busqueda=${encodeURIComponent(busqueda)}`);
    if (sede) params.push(`sede=${encodeURIComponent(sede)}`);
    if (establecimiento) params.push(`establecimiento=${encodeURIComponent(establecimiento)}`);
    if (estado) params.push(`estado=${encodeURIComponent(estado)}`);
    params.push('page=1');
    params.push('limit=10');
    
    url += params.join('&');
    
    // Realizar la búsqueda
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('✅ Datos obtenidos directamente:', data);
            
            // Ocultar indicador de búsqueda
            hideSearchIndicator();
            
            if (data.success && data.data) {
                // Actualizar datos globales si existen
                if (typeof window.employeeData !== 'undefined') {
                    window.employeeData = data.data;
                }
                
                // Actualizar la tabla
                actualizarTablaEmpleados(data.data);
                
                // Mostrar notificación de éxito si hay resultados relevantes
                if (data.data.length > 0) {
                    showNotification({
                        type: 'success',
                        message: `Se encontraron ${data.data.length} empleados`,
                        duration: 3000
                    });
                }
            } else {
                console.error('❌ Error en la búsqueda:', data.message || 'Error desconocido');
                // Mostrar error en la tabla y notificación
                actualizarTablaEmpleados([]);
                showNotification({
                    type: 'warning',
                    message: data.message || 'No se encontraron resultados con los filtros aplicados'
                });
            }
        })
        .catch(error => {
            console.error('❌ Error fetch:', error);
            // Ocultar indicador de búsqueda
            hideSearchIndicator();
            
            // Mostrar error en la tabla y notificación
            actualizarTablaEmpleados([]);
            showNotification({
                type: 'error',
                message: 'Error de conexión al buscar empleados'
            });
        });
}

/**
 * Actualiza la tabla de empleados con los datos proporcionados
 */
function actualizarTablaEmpleados(employees) {
    const tableBody = document.getElementById('employeeTableBody');
    if (!tableBody) return;
    
    if (!employees || employees.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="empty-state">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No se encontraron empleados</h5>
                        <p>Intente con otros filtros de búsqueda</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tableBody.innerHTML = employees.map(employee => {
        // Información básica
        const employeeId = employee.ID_EMPLEADO || employee.id || employee.codigo;
        const nombreCompleto = `${employee.NOMBRE || ''} ${employee.APELLIDO || ''}`.trim();
        const sede = employee.sede || employee.SEDE || '-';
        const establecimiento = employee.establecimiento || employee.ESTABLECIMIENTO || '-';
        
        // Estado biométrico
        const facialStatus = employee.facial_enrolled ? 
            '<span class="badge bg-success"><i class="fas fa-check"></i> Registrado</span>' : 
            '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendiente</span>';
        
        const fingerprintStatus = employee.fingerprint_enrolled ? 
            '<span class="badge bg-success"><i class="fas fa-check"></i> Registrado</span>' : 
            '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendiente</span>';
        
        return `
            <tr>
                <td><strong>${employeeId}</strong></td>
                <td>${nombreCompleto}</td>
                <td>${sede}</td>
                <td>${establecimiento}</td>
                <td class="text-center">${facialStatus}</td>
                <td class="text-center">${fingerprintStatus}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm me-1" 
                            onclick="openEnrollmentModal(${employeeId})"
                            title="Enrolar empleado">
                        <i class="fas fa-fingerprint"></i> Enrolar
                    </button>
                    <button type="button" class="btn btn-info btn-sm" 
                            onclick="viewEnrollmentHistory(${employeeId})"
                            title="Ver historial">
                        <i class="fas fa-history"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}
