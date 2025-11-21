// Reportes de Justificaciones Integrado - JavaScript Module
// Maneja la funcionalidad de justificaciones dentro del módulo de reportes

class ReportesJustificacionesIntegrado {
    constructor() {
        this.currentPage = 1;
        this.currentLimit = 50;
        this.currentFilters = {};
        this.charts = {}; // Para almacenar instancias de Chart.js
        
        this.init();
    }
    
    init() {
        // Solo inicializar si estamos en la página de reportes
        if (!document.getElementById('modalJustificaciones')) {
            console.log('Modal de justificaciones no encontrado');
            return;
        }
        
        console.log('Inicializando ReportesJustificacionesIntegrado para modal');
        this.setupEventListeners();
        this.configurarFiltrosRapidos();
    }
    
    setupTabNavigation() {
        // No se necesita navegación de tabs en modal
        console.log('📋 Modal - No se requiere navegación de tabs');
    }
    
    showTab(tabId) {
        // No se necesita en modal
        console.log('📋 Modal - showTab no requerido');
    }
    
    setupEventListeners() {
        // Formulario de filtros
        const formFiltros = document.getElementById('formFiltrosJust');
        if (formFiltros) {
            formFiltros.addEventListener('submit', (e) => {
                e.preventDefault();
                this.aplicarFiltros();
            });
        }
        
        // Botones de filtros rápidos
        this.addEventListenerSafe('btnJustHoy', 'click', () => this.aplicarFiltroRapido('hoy'));
        this.addEventListenerSafe('btnJustSemanaActual', 'click', () => this.aplicarFiltroRapido('semana'));
        this.addEventListenerSafe('btnJustMesActual', 'click', () => this.aplicarFiltroRapido('mes'));
        this.addEventListenerSafe('btnJustTrimestre', 'click', () => this.aplicarFiltroRapido('trimestre'));
        
        // Botones de acción
        this.addEventListenerSafe('btnLimpiarFiltrosJust', 'click', () => this.limpiarFiltros());
        this.addEventListenerSafe('btnExportarJustificacionesCSV', 'click', () => this.exportarCSV());
        this.addEventListenerSafe('btnEstadisticasJustificaciones', 'click', () => this.mostrarEstadisticas());
        
        // Toggle filtros
        this.addEventListenerSafe('btnToggleFiltrosJust', 'click', () => this.toggleFiltros());
        
        // Cambio de límite
        this.addEventListenerSafe('limitSelectJust', 'change', (e) => {
            this.currentLimit = parseInt(e.target.value);
            this.currentPage = 1;
            this.cargarJustificaciones();
        });
        
        // Cascading dropdowns para sedes y establecimientos
        this.addEventListenerSafe('sedeSelectJust', 'change', (e) => {
            this.cargarEstablecimientos(e.target.value);
        });
    }
    
    addEventListenerSafe(elementId, event, handler) {
        const element = document.getElementById(elementId);
        if (element) {
            element.addEventListener(event, handler);
        }
    }
    
    async cargarDatosIniciales() {
        console.log('cargarDatosIniciales iniciado');
        try {
            await Promise.all([
                this.cargarSedes(),
                this.cargarJustificaciones()
            ]);
            console.log('Datos iniciales cargados exitosamente');
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.mostrarError('Error al cargar los datos iniciales');
        }
    }
    
    async cargarSedes() {
        try {
            const response = await fetch('api/get-sedes.php');
            const data = await response.json();
            
            if (data.success) {
                const sedeSelect = document.getElementById('sedeSelectJust');
                if (sedeSelect) {
                    sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';
                    data.sedes.forEach(sede => {
                        sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
                    });
                }
            }
        } catch (error) {
            console.error('Error cargando sedes:', error);
        }
    }
    
    async cargarEstablecimientos(sedeId) {
        const establecimientoSelect = document.getElementById('establecimientoSelectJust');
        if (!establecimientoSelect) return;
        
        if (!sedeId) {
            establecimientoSelect.innerHTML = '<option value="">Selecciona una sede primero</option>';
            establecimientoSelect.disabled = true;
            return;
        }
        
        try {
            establecimientoSelect.innerHTML = '<option value="">Cargando...</option>';
            establecimientoSelect.disabled = true;
            
            const response = await fetch(`api/get-establecimientos.php?sede_id=${sedeId}`);
            const data = await response.json();
            
            if (data.success) {
                establecimientoSelect.innerHTML = '<option value="">Todos los establecimientos</option>';
                data.establecimientos.forEach(establecimiento => {
                    establecimientoSelect.innerHTML += `<option value="${establecimiento.ID_ESTABLECIMIENTO}">${establecimiento.NOMBRE}</option>`;
                });
                establecimientoSelect.disabled = false;
            } else {
                establecimientoSelect.innerHTML = '<option value="">Error cargando establecimientos</option>';
            }
        } catch (error) {
            console.error('Error cargando establecimientos:', error);
            establecimientoSelect.innerHTML = '<option value="">Error cargando establecimientos</option>';
        }
    }
    
    async cargarEmpleados() {
        // Ya no necesitamos cargar empleados para un select
        // El campo empleado ahora es un input de texto para buscar
        console.log('Campo empleado configurado como input de texto');
    }
    
    configurarFiltrosRapidos() {
        const hoy = new Date();
        const fechaFinElement = document.getElementById('fechaFinJust');
        const fechaInicioElement = document.getElementById('fechaInicioJust');
        
        if (fechaFinElement) {
            fechaFinElement.value = hoy.toISOString().split('T')[0];
        }
        
        // Por defecto, últimos 30 días
        const hace30Dias = new Date();
        hace30Dias.setDate(hace30Dias.getDate() - 30);
        if (fechaInicioElement) {
            fechaInicioElement.value = hace30Dias.toISOString().split('T')[0];
        }
    }
    
    aplicarFiltroRapido(tipo) {
        const hoy = new Date();
        const fechaFin = document.getElementById('fechaFinJust');
        const fechaInicio = document.getElementById('fechaInicioJust');
        
        if (!fechaFin || !fechaInicio) return;
        
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
        const form = document.getElementById('formFiltrosJust');
        if (!form) return {};
        
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
        const form = document.getElementById('formFiltrosJust');
        if (form) {
            form.reset();
        }
        
        // Reset cascading dropdowns
        const establecimientoSelect = document.getElementById('establecimientoSelectJust');
        if (establecimientoSelect) {
            establecimientoSelect.innerHTML = '<option value="">Selecciona una sede primero</option>';
            establecimientoSelect.disabled = true;
        }
        
        this.configurarFiltrosRapidos();
        this.currentFilters = {};
        this.currentPage = 1;
        this.cargarJustificaciones();
    }
    
    async cargarJustificaciones() {
        console.log('cargarJustificaciones iniciado');
        this.mostrarCargando(true);
        
        try {
            const params = new URLSearchParams({
                action: 'reporte',
                page: this.currentPage,
                limit: this.currentLimit,
                ...this.currentFilters
            });
            
            console.log('Fetching con parámetros:', params.toString());
            const response = await fetch(`api/reportes_justificaciones.php?${params}`);
            const data = await response.json();
            
            console.log('Respuesta de API:', data);
            
            if (data.success) {
                this.renderizarTabla(data.justificaciones);
                // Adaptar la estructura de paginación
                const paginacionData = {
                    total_paginas: data.total_pages || 0,
                    pagina_actual: data.page || 1,
                    total: data.total || 0,
                    limite_por_pagina: data.limit || 50
                };
                this.renderizarPaginacion(paginacionData);
                this.actualizarEstadisticasRapidas(data.estadisticas);
                this.actualizarBadgeTotal(data.total || 0);
            } else {
                console.error('Error en respuesta API:', data.message);
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
        console.log('renderizarTabla llamado con:', justificaciones.length, 'justificaciones');
        
        const tbody = document.getElementById('tablaBodyJust');
        const tablaContainer = document.getElementById('tablaContainerJust');
        const noDataMessage = document.getElementById('noDataMessageJust');
        
        if (!tbody) {
            console.error('No se encontró tablaBodyJust');
            return;
        }
        
        if (!justificaciones || justificaciones.length === 0) {
            console.log('Sin justificaciones para mostrar');
            if (tablaContainer) tablaContainer.style.display = 'none';
            if (noDataMessage) {
                // Actualizar mensaje según filtros activos
                const empleadoFiltro = this.currentFilters.empleado_nombre;
                if (empleadoFiltro) {
                    noDataMessage.innerHTML = `
                        <i class="fas fa-user-times"></i>
                        <h4>No se encontraron justificaciones</h4>
                        <p>No hay justificaciones para el empleado "<strong>${empleadoFiltro}</strong>" en el período seleccionado</p>
                    `;
                } else {
                    noDataMessage.innerHTML = `
                        <i class="fas fa-search"></i>
                        <h4>No se encontraron justificaciones</h4>
                        <p>Prueba ajustando los filtros de búsqueda</p>
                    `;
                }
                noDataMessage.style.display = 'block';
            }
            return;
        }
        
        console.log('Mostrando tabla con', justificaciones.length, 'justificaciones');
        if (tablaContainer) tablaContainer.style.display = 'block';
        if (noDataMessage) noDataMessage.style.display = 'none';
        
        tbody.innerHTML = justificaciones.map(j => `
            <tr>
                <td>#${j.id}</td>
                <td>${this.formatearFecha(j.fecha_falta)}</td>
                <td>${j.empleado_nombre_completo || 'N/A'}</td>
                <td>${j.empleado_dni || 'N/A'}</td>
                <td class="text-truncate" style="max-width: 200px;" title="${j.motivo}">
                    ${j.motivo}
                </td>
                <td>
                    <span class="badge ${this.getBadgeClass(j.tipo_falta)}">${this.formatearTipoFalta(j.tipo_falta)}</span>
                </td>
                <td>${j.turno_nombre || 'Todos los turnos'}</td>
                <td>${j.horas_programadas || 0}h</td>
                <td>${this.formatearFechaHora(j.created_at)}</td>
                <td class="text-truncate" style="max-width: 150px;" title="${j.detalle_adicional || ''}">${j.detalle_adicional || '-'}</td>
                <td>
                    <button type="button" class="btn-icon" title="Ver detalles"
                        onclick="event.stopPropagation(); openJustificationDetailsFromRow(${j.id})">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    renderizarPaginacion(paginacion) {
        const container = document.getElementById('paginacionContainerJust');
        const pagination = document.getElementById('paginacionJust');
        
        if (!container || !pagination) return;
        
        if (paginacion.total_paginas <= 1) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        
        let html = '';
        
        // Botón anterior
        html += `
            <li class="page-item ${paginacion.pagina_actual <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="reportesJustificaciones.irAPagina(${paginacion.pagina_actual - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Páginas
        const inicio = Math.max(1, paginacion.pagina_actual - 2);
        const fin = Math.min(paginacion.total_paginas, paginacion.pagina_actual + 2);
        
        if (inicio > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="reportesJustificaciones.irAPagina(1)">1</a></li>`;
            if (inicio > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        
        for (let i = inicio; i <= fin; i++) {
            html += `
                <li class="page-item ${i === paginacion.pagina_actual ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="reportesJustificaciones.irAPagina(${i})">${i}</a>
                </li>
            `;
        }
        
        if (fin < paginacion.total_paginas) {
            if (fin < paginacion.total_paginas - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            html += `<li class="page-item"><a class="page-link" href="#" onclick="reportesJustificaciones.irAPagina(${paginacion.total_paginas})">${paginacion.total_paginas}</a></li>`;
        }
        
        // Botón siguiente
        html += `
            <li class="page-item ${paginacion.pagina_actual >= paginacion.total_paginas ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="reportesJustificaciones.irAPagina(${paginacion.pagina_actual + 1})">
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
            this.updateElementText('totalJustificacionesCard', stats.total_justificaciones || 0);
            this.updateElementText('empleadosDistintosCard', stats.faltas_completas || 0);
            this.updateElementText('horasJustificadasCard', parseFloat(stats.total_horas_justificadas || 0).toFixed(1) + ' h');
            this.updateElementText('faltasParcialesCard', stats.faltas_parciales || 0);
        }
    }
    
    updateElementText(elementId, text) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = text;
        }
    }
    
    actualizarBadgeTotal(total) {
        this.updateElementText('badgeTotalJust', total || 0);
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
                this.crearModalEstadisticas(data.estadisticas);
            } else {
                this.mostrarError(data.message || 'Error al cargar estadísticas');
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
            this.mostrarError('Error al cargar las estadísticas');
        }
    }
    
    crearModalEstadisticas(estadisticas) {
        // Crear modal dinámicamente
        const modalHtml = `
            <div class="modal fade" id="modalEstadisticasJust" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-chart-bar"></i> Estadísticas de Justificaciones
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Justificaciones por Motivo</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="chartMotivosJust" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Justificaciones por Tipo de Falta</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="chartTiposJust" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Evolución Mensual (Últimos 12 meses)</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="chartMensualJust" width="400" height="150"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal existente
        const existingModal = document.getElementById('modalEstadisticasJust');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Agregar nuevo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar modal con Bootstrap
        const modal = new bootstrap.Modal(document.getElementById('modalEstadisticasJust'));
        modal.show();
        
        // Renderizar gráficos después de que el modal sea visible
        modal._element.addEventListener('shown.bs.modal', () => {
            this.renderizarGraficos(estadisticas);
        });
    }
    
    renderizarGraficos(estadisticas) {
        // Destruir gráficos existentes
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
        this.charts = {};
        
        // Gráfico de motivos
        if (estadisticas.por_motivo && estadisticas.por_motivo.length > 0) {
            const ctx1 = document.getElementById('chartMotivosJust');
            if (ctx1) {
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
        }
        
        // Gráfico de tipos
        if (estadisticas.por_tipo && estadisticas.por_tipo.length > 0) {
            const ctx2 = document.getElementById('chartTiposJust');
            if (ctx2) {
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
        }
        
        // Gráfico mensual
        if (estadisticas.por_mes && estadisticas.por_mes.length > 0) {
            const ctx3 = document.getElementById('chartMensualJust');
            if (ctx3) {
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
    
    toggleFiltros() {
        const container = document.getElementById('filtrosJustContainer');
        const btn = document.getElementById('btnToggleFiltrosJust');
        
        if (!container || !btn) return;
        
        const icon = btn.querySelector('i');
        
        if (container.style.display === 'none') {
            container.style.display = 'block';
            if (icon) icon.className = 'fas fa-chevron-down';
        } else {
            container.style.display = 'none';
            if (icon) icon.className = 'fas fa-chevron-up';
        }
    }
    
    mostrarCargando(mostrar) {
        const spinner = document.getElementById('loadingSpinnerJust');
        const tabla = document.getElementById('tablaContainerJust');
        const paginacion = document.getElementById('paginacionContainerJust');
        const noData = document.getElementById('noDataMessageJust');
        
        if (mostrar) {
            if (spinner) spinner.style.display = 'block';
            if (tabla) tabla.style.display = 'none';
            if (paginacion) paginacion.style.display = 'none';
            if (noData) noData.style.display = 'none';
        } else {
            if (spinner) spinner.style.display = 'none';
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
        // Crear notificación simple
        const toastContainer = document.querySelector('.toast-container') || this.crearToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo === 'error' ? 'error' : 'success'}`;
        
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${tipo === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                <span>${mensaje}</span>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Mostrar toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }
        }, 5000);
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
    window.reportesJustificaciones = new ReportesJustificacionesIntegrado();
});