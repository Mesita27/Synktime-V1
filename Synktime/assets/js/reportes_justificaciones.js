// Reportes de Justificaciones JavaScript Module
// Administra interfaz, filtros, paginación y visualización de datos

class ReportesJustificaciones {
    constructor() {
        this.currentPage = 1;
        this.currentLimit = 50;
        this.currentFilters = {};
        this.charts = {}; // Para almacenar instancias de Chart.js
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.cargarDatosIniciales();
        this.configurarFiltrosRapidos();
    }
    
    setupEventListeners() {
        // Formulario de filtros
        document.getElementById('formFiltros').addEventListener('submit', (e) => {
            e.preventDefault();
            this.aplicarFiltros();
        });
        
        // Botones de filtros rápidos
        document.getElementById('btnHoy').addEventListener('click', () => this.aplicarFiltroRapido('hoy'));
        document.getElementById('btnSemanaActual').addEventListener('click', () => this.aplicarFiltroRapido('semana'));
        document.getElementById('btnMesActual').addEventListener('click', () => this.aplicarFiltroRapido('mes'));
        document.getElementById('btnTrimestre').addEventListener('click', () => this.aplicarFiltroRapido('trimestre'));
        
        // Botones de acción
        document.getElementById('btnLimpiarFiltros').addEventListener('click', () => this.limpiarFiltros());
        document.getElementById('btnExportarCSV').addEventListener('click', () => this.exportarCSV());
        document.getElementById('btnEstadisticas').addEventListener('click', () => this.mostrarEstadisticas());
        
        // Toggle filtros
        document.getElementById('btnToggleFiltros').addEventListener('click', () => this.toggleFiltros());
        
        // Cambio de límite
        document.getElementById('limitSelect').addEventListener('change', (e) => {
            this.currentLimit = parseInt(e.target.value);
            this.currentPage = 1;
            this.cargarJustificaciones();
        });
        
        // Filtros dependientes (sede -> establecimiento)
        document.getElementById('sedeSelect').addEventListener('change', () => this.cargarEstablecimientos());
    }
    
    async cargarDatosIniciales() {
        try {
            await Promise.all([
                this.cargarEmpleados(),
                this.cargarSedes(),
                this.cargarJustificaciones()
            ]);
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.mostrarError('Error al cargar los datos iniciales');
        }
    }
    
    async cargarEmpleados() {
        try {
            const response = await fetch('api/justificaciones.php?action=empleados_elegibles');
            const data = await response.json();
            
            if (data.success) {
                const select = document.getElementById('empleadoSelect');
                select.innerHTML = '<option value="">Todos los empleados</option>';
                
                data.empleados.forEach(emp => {
                    const option = document.createElement('option');
                    option.value = emp.id;
                    option.textContent = `${emp.nombre} ${emp.apellido} (${emp.dni})`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error cargando empleados:', error);
        }
    }
    
    async cargarSedes() {
        try {
            // Simulamos carga de sedes - adaptar según tu API
            const sedes = [
                { id: 1, nombre: 'Sede Principal' },
                { id: 2, nombre: 'Sede Norte' },
                { id: 3, nombre: 'Sede Sur' }
            ];
            
            const select = document.getElementById('sedeSelect');
            select.innerHTML = '<option value="">Todas las sedes</option>';
            
            sedes.forEach(sede => {
                const option = document.createElement('option');
                option.value = sede.id;
                option.textContent = sede.nombre;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error cargando sedes:', error);
        }
    }
    
    async cargarEstablecimientos() {
        const sedeId = document.getElementById('sedeSelect').value;
        const select = document.getElementById('establecimientoSelect');
        
        select.innerHTML = '<option value="">Todos los establecimientos</option>';
        
        if (!sedeId) return;
        
        try {
            // Simulamos carga de establecimientos por sede
            const establecimientos = [
                { id: 1, nombre: 'Establecimiento A', sede_id: 1 },
                { id: 2, nombre: 'Establecimiento B', sede_id: 1 },
                { id: 3, nombre: 'Establecimiento C', sede_id: 2 }
            ].filter(est => est.sede_id == sedeId);
            
            establecimientos.forEach(est => {
                const option = document.createElement('option');
                option.value = est.id;
                option.textContent = est.nombre;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error cargando establecimientos:', error);
        }
    }
    
    configurarFiltrosRapidos() {
        const hoy = new Date();
        document.getElementById('fechaFin').value = hoy.toISOString().split('T')[0];
        
        // Por defecto, últimos 30 días
        const hace30Dias = new Date();
        hace30Dias.setDate(hace30Dias.getDate() - 30);
        document.getElementById('fechaInicio').value = hace30Dias.toISOString().split('T')[0];
    }
    
    aplicarFiltroRapido(tipo) {
        const hoy = new Date();
        const fechaFin = document.getElementById('fechaFin');
        const fechaInicio = document.getElementById('fechaInicio');
        
        fechaFin.value = hoy.toISOString().split('T')[0];
        
        switch (tipo) {
            case 'hoy':
                fechaInicio.value = hoy.toISOString().split('T')[0];
                break;
            case 'semana':
                const inicioSemana = new Date(hoy);
                inicioSemana.setDate(hoy.getDate() - hoy.getDay());
                fechaInicio.value = inicioSemana.toISOString().split('T')[0];
                break;
            case 'mes':
                const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                fechaInicio.value = inicioMes.toISOString().split('T')[0];
                break;
            case 'trimestre':
                const inicioTrimestre = new Date(hoy.getFullYear(), Math.floor(hoy.getMonth() / 3) * 3, 1);
                fechaInicio.value = inicioTrimestre.toISOString().split('T')[0];
                break;
        }
        
        this.aplicarFiltros();
    }
    
    aplicarFiltros() {
        this.currentPage = 1;
        this.currentFilters = this.obtenerFiltrosFormulario();
        this.cargarJustificaciones();
    }
    
    obtenerFiltrosFormulario() {
        const form = document.getElementById('formFiltros');
        const formData = new FormData(form);
        const filters = {};
        
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                filters[key] = value;
            }
        }
        
        return filters;
    }
    
    limpiarFiltros() {
        document.getElementById('formFiltros').reset();
        this.configurarFiltrosRapidos();
        this.currentFilters = {};
        this.currentPage = 1;
        this.cargarJustificaciones();
    }
    
    async cargarJustificaciones() {
        this.mostrarCargando(true);
        
        try {
            const params = new URLSearchParams({
                action: 'reporte',
                page: this.currentPage,
                limit: this.currentLimit,
                ...this.currentFilters
            });
            
            const response = await fetch(`api/reportes_justificaciones.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderizarTabla(data.justificaciones);
                this.renderizarPaginacion(data.paginacion);
                this.actualizarEstadisticasRapidas(data.estadisticas_rapidas);
                this.actualizarBadgeTotal(data.paginacion.total);
            } else {
                this.mostrarError(data.message || 'Error al cargar justificaciones');
            }
        } catch (error) {
            console.error('Error cargando justificaciones:', error);
            this.mostrarError('Error de conexión al cargar los datos');
        } finally {
            this.mostrarCargando(false);
        }
    }
    
    renderizarTabla(justificaciones) {
        const tbody = document.getElementById('tablaBody');
        const tablaContainer = document.getElementById('tablaContainer');
        const noDataMessage = document.getElementById('noDataMessage');
        
        if (!justificaciones || justificaciones.length === 0) {
            tablaContainer.style.display = 'none';
            noDataMessage.style.display = 'block';
            return;
        }
        
        tablaContainer.style.display = 'block';
        noDataMessage.style.display = 'none';
        
        tbody.innerHTML = justificaciones.map(j => `
            <tr>
                <td>#${j.id}</td>
                <td>${this.formatearFecha(j.fecha_falta)}</td>
                <td>${j.empleado_nombre} ${j.empleado_apellido}</td>
                <td>${j.empleado_dni}</td>
                <td class="text-truncate" style="max-width: 200px;" title="${j.motivo}">
                    ${j.motivo}
                </td>
                <td>
                    <span class="badge ${this.getBadgeClass(j.tipo_falta)}">${this.formatearTipoFalta(j.tipo_falta)}</span>
                </td>
                <td>${j.turno_nombre || 'Todos los turnos'}</td>
                <td>${j.horas_justificadas}h</td>
                <td>${j.establecimiento || 'N/A'}</td>
                <td>${this.formatearFechaHora(j.fecha_creacion)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="reportes.verDetalle(${j.id})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    renderizarPaginacion(paginacion) {
        const container = document.getElementById('paginacionContainer');
        const pagination = document.getElementById('paginacion');
        
        if (paginacion.total_paginas <= 1) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        
        let html = '';
        
        // Botón anterior
        html += `
            <li class="page-item ${paginacion.pagina_actual <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="reportes.irAPagina(${paginacion.pagina_actual - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Páginas
        const inicio = Math.max(1, paginacion.pagina_actual - 2);
        const fin = Math.min(paginacion.total_paginas, paginacion.pagina_actual + 2);
        
        if (inicio > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="reportes.irAPagina(1)">1</a></li>`;
            if (inicio > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        
        for (let i = inicio; i <= fin; i++) {
            html += `
                <li class="page-item ${i === paginacion.pagina_actual ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="reportes.irAPagina(${i})">${i}</a>
                </li>
            `;
        }
        
        if (fin < paginacion.total_paginas) {
            if (fin < paginacion.total_paginas - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            html += `<li class="page-item"><a class="page-link" href="#" onclick="reportes.irAPagina(${paginacion.total_paginas})">${paginacion.total_paginas}</a></li>`;
        }
        
        // Botón siguiente
        html += `
            <li class="page-item ${paginacion.pagina_actual >= paginacion.total_paginas ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="reportes.irAPagina(${paginacion.pagina_actual + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
        
        pagination.innerHTML = html;
    }
    
    irAPagina(pagina) {
        this.currentPage = pagina;
        this.cargarJustificaciones();
    }
    
    actualizarEstadisticasRapidas(stats) {
        if (stats) {
            document.getElementById('totalJustificaciones').textContent = stats.total_justificaciones || 0;
            document.getElementById('empleadosDistintos').textContent = stats.empleados_distintos || 0;
            document.getElementById('horasJustificadas').textContent = (stats.horas_justificadas || 0).toFixed(1);
            document.getElementById('faltasCompletas').textContent = stats.faltas_completas || 0;
        }
    }
    
    actualizarBadgeTotal(total) {
        document.getElementById('badgeTotal').textContent = total || 0;
    }
    
    async mostrarEstadisticas() {
        try {
            const params = new URLSearchParams({
                action: 'estadisticas',
                ...this.currentFilters
            });
            
            const response = await fetch(`api/reportes_justificaciones.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderizarGraficos(data.estadisticas);
                const modal = new bootstrap.Modal(document.getElementById('modalEstadisticas'));
                modal.show();
            } else {
                this.mostrarError(data.message || 'Error al cargar estadísticas');
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
            this.mostrarError('Error al cargar las estadísticas');
        }
    }
    
    renderizarGraficos(estadisticas) {
        // Destruir gráficos existentes
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
        this.charts = {};
        
        // Gráfico de motivos
        if (estadisticas.por_motivo && estadisticas.por_motivo.length > 0) {
            const ctx1 = document.getElementById('chartMotivos');
            this.charts.motivos = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: estadisticas.por_motivo.map(item => item.motivo),
                    datasets: [{
                        data: estadisticas.por_motivo.map(item => item.total),
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Gráfico de tipos
        if (estadisticas.por_tipo && estadisticas.por_tipo.length > 0) {
            const ctx2 = document.getElementById('chartTipos');
            this.charts.tipos = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: estadisticas.por_tipo.map(item => this.formatearTipoFalta(item.tipo_falta)),
                    datasets: [{
                        label: 'Justificaciones',
                        data: estadisticas.por_tipo.map(item => item.total),
                        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Gráfico mensual
        if (estadisticas.por_mes && estadisticas.por_mes.length > 0) {
            const ctx3 = document.getElementById('chartMensual');
            this.charts.mensual = new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: estadisticas.por_mes.map(item => `${item.mes}/${item.año}`),
                    datasets: [{
                        label: 'Justificaciones por mes',
                        data: estadisticas.por_mes.map(item => item.total),
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Top empleados
        if (estadisticas.top_empleados && estadisticas.top_empleados.length > 0) {
            const tbody = document.getElementById('topEmpleadosBody');
            tbody.innerHTML = estadisticas.top_empleados.map((emp, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${emp.nombre} ${emp.apellido}</td>
                    <td><span class="badge bg-primary">${emp.total}</span></td>
                </tr>
            `).join('');
        }
    }
    
    async exportarCSV() {
        try {
            const params = new URLSearchParams({
                action: 'exportar',
                format: 'csv',
                ...this.currentFilters
            });
            
            const response = await fetch(`api/reportes_justificaciones.php?${params}`);
            const blob = await response.blob();
            
            // Crear link de descarga
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `justificaciones_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.mostrarExito('Archivo CSV descargado correctamente');
        } catch (error) {
            console.error('Error exportando CSV:', error);
            this.mostrarError('Error al exportar el archivo CSV');
        }
    }
    
    async verDetalle(id) {
        try {
            const response = await fetch(`api/reportes_justificaciones.php?action=detalle&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderizarDetalle(data.justificacion);
                const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
                modal.show();
            } else {
                this.mostrarError(data.message || 'Error al cargar el detalle');
            }
        } catch (error) {
            console.error('Error cargando detalle:', error);
            this.mostrarError('Error al cargar el detalle de la justificación');
        }
    }
    
    renderizarDetalle(justificacion) {
        const content = document.getElementById('detalleContent');
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-user"></i> Información del Empleado</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Nombre:</strong></td><td>${justificacion.empleado_nombre} ${justificacion.empleado_apellido}</td></tr>
                        <tr><td><strong>DNI:</strong></td><td>${justificacion.empleado_dni}</td></tr>
                        <tr><td><strong>Email:</strong></td><td>${justificacion.empleado_email || 'N/A'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-calendar"></i> Información de la Falta</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Fecha:</strong></td><td>${this.formatearFecha(justificacion.fecha_falta)}</td></tr>
                        <tr><td><strong>Tipo:</strong></td><td><span class="badge ${this.getBadgeClass(justificacion.tipo_falta)}">${this.formatearTipoFalta(justificacion.tipo_falta)}</span></td></tr>
                        <tr><td><strong>Horas:</strong></td><td>${justificacion.horas_justificadas}h</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-comment"></i> Motivo de la Justificación</h6>
                    <div class="card bg-light">
                        <div class="card-body">
                            ${justificacion.motivo}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6><i class="fas fa-clock"></i> Información del Turno</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Turno:</strong></td><td>${justificacion.turno_nombre || 'Todos los turnos'}</td></tr>
                        <tr><td><strong>Hora entrada:</strong></td><td>${justificacion.turno_hora_entrada || 'N/A'}</td></tr>
                        <tr><td><strong>Hora salida:</strong></td><td>${justificacion.turno_hora_salida || 'N/A'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle"></i> Información Adicional</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Establecimiento:</strong></td><td>${justificacion.establecimiento || 'N/A'}</td></tr>
                        <tr><td><strong>Fecha creación:</strong></td><td>${this.formatearFechaHora(justificacion.fecha_creacion)}</td></tr>
                    </table>
                </div>
            </div>
        `;
    }
    
    toggleFiltros() {
        const container = document.getElementById('filtrosContainer');
        const btn = document.getElementById('btnToggleFiltros');
        const icon = btn.querySelector('i');
        
        if (container.style.display === 'none') {
            container.style.display = 'block';
            icon.className = 'fas fa-chevron-down';
        } else {
            container.style.display = 'none';
            icon.className = 'fas fa-chevron-up';
        }
    }
    
    mostrarCargando(mostrar) {
        const spinner = document.getElementById('loadingSpinner');
        const tabla = document.getElementById('tablaContainer');
        const paginacion = document.getElementById('paginacionContainer');
        const noData = document.getElementById('noDataMessage');
        
        if (mostrar) {
            spinner.style.display = 'block';
            tabla.style.display = 'none';
            paginacion.style.display = 'none';
            noData.style.display = 'none';
        } else {
            spinner.style.display = 'none';
        }
    }
    
    // Utilidades de formato
    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }
    
    formatearFechaHora(fechaHora) {
        if (!fechaHora) return 'N/A';
        const date = new Date(fechaHora);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    formatearTipoFalta(tipo) {
        const tipos = {
            'completa': 'Día Completo',
            'parcial': 'Parcial',
            'tardanza': 'Tardanza'
        };
        return tipos[tipo] || tipo;
    }
    
    getBadgeClass(tipo) {
        const classes = {
            'completa': 'bg-danger',
            'parcial': 'bg-warning',
            'tardanza': 'bg-info'
        };
        return classes[tipo] || 'bg-secondary';
    }
    
    // Utilidades de notificación
    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }
    
    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }
    
    mostrarNotificacion(mensaje, tipo) {
        // Crear notificación Bootstrap toast
        const toastContainer = document.querySelector('.toast-container') || this.crearToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${tipo === 'error' ? 'danger' : 'success'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${tipo === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                    ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remover del DOM después de que se oculte
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    crearToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1090';
        document.body.appendChild(container);
        return container;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.reportes = new ReportesJustificaciones();
});