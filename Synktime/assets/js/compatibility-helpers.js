// Helper Functions for JSON Compatibility

/**
 * Normaliza objetos de empleado desde diferentes fuentes de API
 * para asegurar compatibilidad con el sistema
 * @param {Object} emp - Objeto empleado de la API
 * @returns {Object} - Objeto normalizado
 */
function normalizeEmployeeObject(emp) {
    if (!emp) return {};
    
    // Crear objeto normalizado con valores predeterminados
    const normalized = {
        // Propiedades básicas con detección de mayúsculas/minúsculas
        id: emp.ID_EMPLEADO || emp.id_empleado || emp.id || emp.Id || emp.ID || null,
        nombre: emp.NOMBRE || emp.nombre || '',
        apellido: emp.APELLIDO || emp.apellido || '',
        establecimiento: emp.ESTABLECIMIENTO || emp.establecimiento || '',
        sede: emp.SEDE || emp.sede || 'Sin Sede'
    };
    
    // Nombre completo para conveniencia
    normalized.nombreCompleto = `${normalized.nombre} ${normalized.apellido}`.trim();
    
    return normalized;
}

/**
 * Detecta el formato de datos de un objeto JSON y extrae la lista de empleados
 * @param {Object|Array} data - Datos de respuesta del servidor
 * @returns {Array} - Lista de empleados normalizada
 */
function extractEmployeesFromResponse(data) {
    let empleados = [];
    
    // Si es un array directamente
    if (Array.isArray(data)) {
        empleados = data;
    }
    // Si tiene propiedad data o employees
    else if (data && typeof data === 'object') {
        if (data.data && Array.isArray(data.data)) {
            empleados = data.data;
        } else if (data.employees && Array.isArray(data.employees)) {
            empleados = data.employees;
        }
    }
    
    // Normalizar cada empleado
    return empleados.map(normalizeEmployeeObject);
}
