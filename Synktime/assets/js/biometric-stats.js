/**
 * Función para actualizar los contadores de estadísticas biométricas
 */
function updateBiometricStats(data) {
    try {
        console.log('🔄 Actualizando contadores biométricos...');
        
        // Verificar si tenemos estadísticas en los datos
        const stats = data.stats || {};
        
        // Actualizar contadores si existen los elementos
        const totalEmpleados = document.getElementById('totalEmpleados');
        const inscritos = document.getElementById('empleadosInscritos');
        const noInscritos = document.getElementById('empleadosNoInscritos');
        const facialInscritos = document.getElementById('facialInscritos');
        const huellaInscritos = document.getElementById('huellaInscritos');
        
        if (totalEmpleados) totalEmpleados.textContent = stats.total_empleados || '0';
        if (inscritos) inscritos.textContent = stats.total_inscritos || '0';
        if (noInscritos) noInscritos.textContent = stats.no_inscritos || '0';
        if (facialInscritos) facialInscritos.textContent = stats.facial_inscritos || '0';
        if (huellaInscritos) huellaInscritos.textContent = stats.huella_inscritos || '0';
        
        // Si no hay elementos o datos de estadísticas, intentar calcular desde los datos de empleados
        if (!stats.total_empleados && data.data && Array.isArray(data.data)) {
            const empleados = data.data;
            const total = empleados.length;
            const inscritosBio = empleados.filter(e => e.facial_enrolled || e.fingerprint_enrolled).length;
            const noInscritosBio = total - inscritosBio;
            const facialTotal = empleados.filter(e => e.facial_enrolled).length;
            const huellaTotal = empleados.filter(e => e.fingerprint_enrolled).length;
            
            if (totalEmpleados) totalEmpleados.textContent = total;
            if (inscritos) inscritos.textContent = inscritosBio;
            if (noInscritos) noInscritos.textContent = noInscritosBio;
            if (facialInscritos) facialInscritos.textContent = facialTotal;
            if (huellaInscritos) huellaInscritos.textContent = huellaTotal;
        }
        
        console.log('✅ Contadores biométricos actualizados');
    } catch (error) {
        console.error('❌ Error al actualizar contadores biométricos:', error);
    }
}
