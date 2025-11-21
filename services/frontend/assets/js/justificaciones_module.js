/**
 * Módulo de Gestión de Justificaciones
 * Sistema completo de justificaciones con validaciones y filtros avanzados
 */

class JustificacionesModule {
    constructor() {
        this.currentFilters = {
            empleado_search: '',
            sede: '',
            establecimiento: '',
            fecha_desde: '',
            fecha_hasta: ''
        };
        this.justificaciones = [];
        this.empleadosElegibles = [];
        this.empleadoSeleccionado = null;
    this.establecimientosCache = {};
        
        // Variables de paginación
        this.currentPage = 1;
        this.pageSize = 25;
        this.totalPages = 1;
        this.totalRecords = 0;
        
        this.init();
    }

    /**
     * Obtener fecha actual en zona horaria de Bogotá, Colombia
     * Método más preciso que compensa la diferencia horaria
     */
    getBogotaDate() {
        // Crear fecha actual
        const now = new Date();
        
        // Obtener la fecha en zona horaria de Bogotá
        const bogotaTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));
        
        // Formatear como YYYY-MM-DD
        const year = bogotaTime.getFullYear();
        const month = String(bogotaTime.getMonth() + 1).padStart(2, '0');
        const day = String(bogotaTime.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    }

    /**
     * Obtener objeto Date en zona horaria de Bogotá
     */
    getBogotaDateObject() {
        const now = new Date();
        return new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
        this.setDefaultDates();
    }

    bindEvents() {
        // Botón aplicar filtros
        $('#btnAplicarFiltros').on('click', () => {
            this.updateFilters();
            this.applyFilters();
        });

        // Enter en el campo de búsqueda de empleado
        $('#filtroEmpleado').on('keypress', (e) => {
            if (e.which === 13) { // Enter key
                this.updateFilters();
                this.applyFilters();
            }
        });

        // Quick filters
        $('.quick-filter-btn').on('click', (e) => {
            this.applyQuickFilter($(e.target).data('filter'));
        });

        // Filter events para selects
        $('#filtroSede').on('change', async (e) => {
            const selectedSede = $(e.target).val();
            $('#filtroEstablecimiento').val('');
            await this.loadEstablecimientos(selectedSede);
            this.updateFilters();
            this.applyFilters();
        });

        $('#filtroEstablecimiento').on('change', () => {
            this.updateFilters();
            this.applyFilters();
        });

        $('#filtroFechaDesde, #filtroFechaHasta').on('change', () => {
            this.updateFilters();
            this.applyFilters();
        });
        
        // Event listeners para paginación
        $('#prevPage').on('click', () => {
            if (this.currentPage > 1) {
                this.goToPage(this.currentPage - 1);
            }
        });
        
        $('#nextPage').on('click', () => {
            if (this.currentPage < this.totalPages) {
                this.goToPage(this.currentPage + 1);
            }
        });
        
        // Event listener para números de página (delegado)
        $('#paginationPages').on('click', '.btn-pagination-page', (e) => {
            const page = parseInt($(e.target).data('page'));
            this.goToPage(page);
        });
        
        // Event listener para cambio de tamaño de página
        $('#pageSize').on('change', (e) => {
            this.changePageSize($(e.target).val());
        });
    }

    setDefaultDates() {
        const today = this.getBogotaDate();
        const weekAgo = this.getBogotaDateObject();
        weekAgo.setDate(weekAgo.getDate() - 7);
        
        // Formatear fecha de hace una semana manualmente para evitar problemas de zona horaria
        const year = weekAgo.getFullYear();
        const month = String(weekAgo.getMonth() + 1).padStart(2, '0');
        const day = String(weekAgo.getDate()).padStart(2, '0');
        const weekAgoFormatted = `${year}-${month}-${day}`;
        
        $('#filtroFechaDesde').val(weekAgoFormatted);
        $('#filtroFechaHasta').val(today);
        $('#fechaFalta').val(today);
    }

    async loadInitialData() {
        await Promise.all([
            this.loadSedes(),
            this.loadJustificaciones(),
            this.loadEstadisticas()
        ]);

        const initialSede = $('#filtroSede').val();
        if (initialSede) {
            await this.loadEstablecimientos(initialSede);
        } else {
            this.populateSelect('#filtroEstablecimiento', [], 'ID_ESTABLECIMIENTO', 'NOMBRE');
        }
    }

    async loadSedes() {
        try {
            const response = await fetch('api/justificaciones.php?action=getSedes');
            const data = await response.json();
            
            if (data.success) {
                this.populateSelect('#filtroSede', data.sedes, 'ID_SEDE', 'NOMBRE');
            }
        } catch (error) {
            console.error('Error cargando sedes:', error);
        }
    }

    async loadEstablecimientos(sedeId = '') {
        const selectSelector = '#filtroEstablecimiento';
        const cacheKey = sedeId && sedeId !== '' ? `sede_${sedeId}` : null;

        try {
            if (!sedeId) {
                this.populateSelect(selectSelector, [], 'ID_ESTABLECIMIENTO', 'NOMBRE');
                return;
            }

            const cached = cacheKey ? this.establecimientosCache[cacheKey] : null;
            if (cached) {
                this.populateSelect(selectSelector, cached, 'ID_ESTABLECIMIENTO', 'NOMBRE');
                return;
            }

            const params = new URLSearchParams();
            if (sedeId) {
                params.append('sede_id', sedeId);
            }

            const queryString = params.toString();
            const url = `api/justificaciones.php?action=getEstablecimientos${queryString ? `&${queryString}` : ''}`;

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                const establecimientos = data.establecimientos || [];
                if (cacheKey) {
                    this.establecimientosCache[cacheKey] = establecimientos;
                }
                this.populateSelect(selectSelector, establecimientos, 'ID_ESTABLECIMIENTO', 'NOMBRE');
            } else {
                this.populateSelect(selectSelector, [], 'ID_ESTABLECIMIENTO', 'NOMBRE');
            }
        } catch (error) {
            console.error('Error cargando establecimientos:', error);
            this.populateSelect(selectSelector, [], 'ID_ESTABLECIMIENTO', 'NOMBRE');
        }
    }

    async loadJustificaciones() {
        try {
            $('#justificacionesTableBody').html(`
                <tr>
                    <td colspan="6" class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Cargando justificaciones...
                    </td>
                </tr>
            `);

            // Construir parámetros de búsqueda
            const params = new URLSearchParams();
            
            if (this.currentFilters.empleado_search) {
                params.append('empleado_search', this.currentFilters.empleado_search);
            }
            if (this.currentFilters.sede) {
                params.append('sede', this.currentFilters.sede);
            }
            if (this.currentFilters.establecimiento) {
                params.append('establecimiento', this.currentFilters.establecimiento);
            }
            if (this.currentFilters.fecha_desde) {
                params.append('fecha_desde', this.currentFilters.fecha_desde);
            }
            if (this.currentFilters.fecha_hasta) {
                params.append('fecha_hasta', this.currentFilters.fecha_hasta);
            }
            
            // Agregar parámetros de paginación
            params.append('page', this.currentPage);
            params.append('limit', this.pageSize);

            const response = await fetch(`api/justificaciones.php?action=getJustificaciones&${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.justificaciones = data.justificaciones;
                this.totalRecords = data.total_registros || 0;
                this.totalPages = data.total_paginas || 1;
                
                this.renderJustificaciones();
                this.renderPagination();
                this.calculateLocalStats(); // Calcular estadísticas con datos actuales
            } else {
                $('#justificacionesTableBody').html(`
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-info-circle"></i>
                            ${data.message || 'No se encontraron justificaciones.'}
                        </td>
                    </tr>
                `);
                this.renderPagination();
                this.calculateLocalStats(); // Actualizar estadísticas aunque no haya datos
            }
        } catch (error) {
            console.error('Error cargando justificaciones:', error);
            $('#justificacionesTableBody').html(`
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error al cargar las justificaciones.
                    </td>
                </tr>
            `);
            this.calculateLocalStats(); // Estadísticas en cero en caso de error
        }
    }

    renderJustificaciones() {
        if (!this.justificaciones || this.justificaciones.length === 0) {
            $('#justificacionesTableBody').html(`
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        No se encontraron justificaciones con los filtros aplicados.
                    </td>
                </tr>
            `);
            return;
        }

        const html = this.justificaciones.map(justificacion => `
            <tr>
                <td>
                    <div>
                        <strong>${justificacion.empleado_nombre}</strong>
                        <br>
                        <small class="text-muted">${justificacion.empleado_codigo || 'Sin código'}</small>
                    </div>
                </td>
                <td>${this.formatDate(justificacion.fecha_falta)}</td>
                <td>
                    <div class="turno-info">
                        <div class="turno-nombre">
                            <strong>${justificacion.turno_nombre || 'No especificado'}</strong>
                        </div>
                        <div class="turno-horario">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                ${justificacion.turno_horario || 'Horario no definido'}
                            </small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="motivo-badge">${justificacion.motivo}</span>
                </td>
                <td>
                    <span class="tipo-badge tipo-${justificacion.tipo_falta}">
                        ${this.formatTipoFalta(justificacion.tipo_falta)}
                    </span>
                </td>
                <td>${this.formatDateTime(justificacion.created_at)}</td>
                <td>
                    <div class="observacion-label-container">
                        <div class="observacion-label">
                            <div class="observacion-content">
                                ${justificacion.motivo || 'Sin motivo especificado'}
                                ${justificacion.detalle_adicional ? ` - ${justificacion.detalle_adicional}` : ''}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');

        $('#justificacionesTableBody').html(html);
        
        // Inicializar observaciones expandibles después de renderizar
        if (typeof window.initializeObservaciones === 'function') {
            setTimeout(() => {
                window.initializeObservaciones();
            }, 100);
        }
    }

    async loadEstadisticas() {
        // Calcular estadísticas basadas en los datos actuales cargados
        this.calculateLocalStats();
    }
    
    calculateLocalStats() {
        if (!this.justificaciones || this.justificaciones.length === 0) {
            // Sin datos, mostrar ceros
            $('#statTotal').text('0');
            $('#statMotivoComun').text('-');
            $('#statJornadaParcial').text('0');
            $('#statJornadaCompleta').text('0');
            return;
        }
        
        const total = this.justificaciones.length;
        
        // Contar motivos para encontrar el más común
        const motivoCount = {};
        let jornadaParcial = 0;
        let jornadaCompleta = 0;
        
        this.justificaciones.forEach(justificacion => {
            // Contar motivos
            const motivo = justificacion.motivo || 'Sin motivo';
            motivoCount[motivo] = (motivoCount[motivo] || 0) + 1;
            
            // Contar por tipo de jornada - usar valores correctos de la BD
            const tipoFalta = justificacion.tipo_falta;
            if (tipoFalta === 'parcial') {
                jornadaParcial++;
            } else if (tipoFalta === 'completa') {
                jornadaCompleta++;
            }
            // Nota: 'tardanza' no se cuenta en ninguna de las dos categorías
        });
        
        // Encontrar motivo más común
        let motivoMasComun = '-';
        let maxCount = 0;
        for (const [motivo, count] of Object.entries(motivoCount)) {
            if (count > maxCount) {
                maxCount = count;
                motivoMasComun = motivo;
            }
        }
        
        // Truncar motivo si es muy largo
        if (motivoMasComun.length > 15) {
            motivoMasComun = motivoMasComun.substring(0, 15) + '...';
        }
        
        // Actualizar estadísticas en la interfaz
        $('#statTotal').text(total);
        $('#statMotivoComun').text(motivoMasComun);
        $('#statJornadaParcial').text(jornadaParcial);
        $('#statJornadaCompleta').text(jornadaCompleta);
        
        console.log('📊 Estadísticas actualizadas:', {
            total,
            motivoMasComun,
            jornadaParcial: `${jornadaParcial} (tipo: parcial)`,
            jornadaCompleta: `${jornadaCompleta} (tipo: completa)`,
            datosMuestra: this.justificaciones.slice(0, 3).map(j => ({
                motivo: j.motivo,
                tipo_falta: j.tipo_falta
            }))
        });
    }



    applyQuickFilter(filter) {
        $('.quick-filter-btn').removeClass('active');
        $(`[data-filter="${filter}"]`).addClass('active');
        
        const today = this.getBogotaDateObject();
        let fechaDesde, fechaHasta;
        
        switch (filter) {
            case 'today':
                fechaDesde = fechaHasta = this.getBogotaDate();
                break;
            case 'week':
                const weekAgo = this.getBogotaDateObject();
                weekAgo.setDate(weekAgo.getDate() - 7);
                fechaDesde = this.formatDateManual(weekAgo);
                fechaHasta = this.getBogotaDate();
                break;
            case 'month':
                const monthStart = this.getBogotaDateObject();
                monthStart.setDate(1);
                fechaDesde = this.formatDateManual(monthStart);
                fechaHasta = this.getBogotaDate();
                break;
            case 'all':
            default:
                // Para "todas", limpiar los filtros de fecha para mostrar todos los registros
                fechaDesde = '';
                fechaHasta = '';
                break;
        }
        
        $('#filtroFechaDesde').val(fechaDesde);
        $('#filtroFechaHasta').val(fechaHasta);
        this.updateFilters();
        this.loadJustificaciones();
    }

    updateFilters() {
        this.currentFilters = {
            empleado_search: $('#filtroEmpleado').val(),
            sede: $('#filtroSede').val(),
            establecimiento: $('#filtroEstablecimiento').val(),
            fecha_desde: $('#filtroFechaDesde').val(),
            fecha_hasta: $('#filtroFechaHasta').val()
        };
    }

    populateSelect(selector, options, valueField, textField) {
        const $select = $(selector);
        const currentValue = $select.val();
        
        $select.find('option:not(:first)').remove();
        
        options.forEach(option => {
            $select.append(`<option value="${option[valueField]}">${option[textField]}</option>`);
        });
        
        if (currentValue) {
            $select.val(currentValue);
        }

        if (!$select.val()) {
            $select.prop('selectedIndex', 0);
        }
    }

    /**
     * Formatear fecha manualmente para evitar problemas de zona horaria
     */
    formatDateManual(dateObj) {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    formatDate(dateString) {
        // Evitar conversión automática que puede cambiar la fecha por zona horaria
        if (!dateString) return '';
        
        // Si es formato YYYY-MM-DD, parsearlo manualmente
        if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
            const [year, month, day] = dateString.split('-');
            const date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
            return date.toLocaleDateString('es-ES');
        }
        
        // Para otros formatos, usar conversión normal
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES');
    }

    formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
    }

    formatTipoFalta(tipo) {
        const tipos = {
            'completa': 'Completa',
            'parcial': 'Parcial',
            'tardanza': 'Tardanza'
        };
        return tipos[tipo] || tipo;
    }

    formatEstado(estado) {
        const estados = {
            'pendiente': 'Pendiente',
            'aprobada': 'Aprobada',
            'rechazada': 'Rechazada',
            'revision': 'En Revisión'
        };
        return estados[estado] || estado;
    }

    async viewJustificacion(id) {
        try {
            console.log('🔍 Abriendo modal personalizado para justificación ID:', id);
            
            // Abrir modal personalizado
            openCustomModal();
            
            // Mostrar loading
            showModalLoading();
            
            // Obtener detalles de la justificación
            const response = await fetch(`api/justificaciones.php?action=detalle&id=${id}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('📊 Datos recibidos:', data);
            
            if (data.success && data.justificacion) {
                this.showJustificacionContent(data.justificacion);
            } else {
                this.showModalError(data.message || 'Error desconocido al cargar detalles');
            }
            
        } catch (error) {
            console.error('❌ Error en viewJustificacion:', error);
            this.showModalError('Error de conexión al cargar detalles');
        }
    }
    
    showModalError(message) {
        const modalBody = document.getElementById('modalBodyContent');
        const modalTitle = document.getElementById('modalTitleText');
        
        if (modalTitle) {
            modalTitle.innerHTML = 'Error';
        }
        
        if (modalBody) {
            modalBody.innerHTML = `
                <div class="error-content">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4>Error al cargar detalles</h4>
                    <p>${message}</p>
                </div>
            `;
        }
    }

    showJustificacionContent(justificacion) {
        // Formatear datos para mostrar
        const fechaFalta = this.formatDate(justificacion.fecha_falta);
        const fechaCreacion = this.formatDateTime(justificacion.created_at);
        const tipoFalta = this.formatTipoFalta(justificacion.tipo_falta);
        
        // Actualizar título del modal
        const modalTitle = document.getElementById('modalTitleText');
        if (modalTitle) {
            modalTitle.innerHTML = `Justificación #${justificacion.id}`;
        }
        
        // Construir contenido del modal con el diseño del módulo
        const modalContent = `
            <div class="justificacion-details">
                <div class="details-grid">
                    <div class="detail-section">
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-user"></i>
                                Empleado
                            </label>
                            <div class="detail-value">${justificacion.empleado_nombre_completo || 'N/A'}</div>
                        </div>
                        
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-calendar"></i>
                                Fecha de la Falta
                            </label>
                            <div class="detail-value">${fechaFalta}</div>
                        </div>
                        
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-tag"></i>
                                Tipo de Falta
                            </label>
                            <div class="detail-value">
                                <span class="badge-tipo-falta ${justificacion.tipo_falta}">${tipoFalta}</span>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-clock"></i>
                                Turno/Horario
                            </label>
                            <div class="detail-value">
                                <div class="turno-detalle">
                                    <strong>${justificacion.turno_nombre || 'No especificado'}</strong>
                                    <br>
                                    <small class="text-muted">${justificacion.turno_horario || 'Horario no definido'}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-building"></i>
                                Establecimiento
                            </label>
                            <div class="detail-value">${justificacion.establecimiento_nombre || 'N/A'}</div>
                        </div>
                        
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-plus-circle"></i>
                                Fecha de Creación
                            </label>
                            <div class="detail-value">${fechaCreacion}</div>
                        </div>
                        
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-business-time"></i>
                                Alcance
                            </label>
                            <div class="detail-value">
                                ${justificacion.es_jornada_completa === 1 ? 
                                    '<span class="badge-alcance completa">Jornada Completa</span>' : 
                                    '<span class="badge-alcance parcial">Turno Específico</span>'
                                }
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <label class="detail-label">
                                <i class="fas fa-hashtag"></i>
                                ID de Justificación
                            </label>
                            <div class="detail-value">#${justificacion.id}</div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-item full-width">
                    <label class="detail-label">
                        <i class="fas fa-comment"></i>
                        Motivo
                    </label>
                    <div class="detail-value motivo-content">
                        ${justificacion.motivo || 'N/A'}
                    </div>
                </div>
                
                ${justificacion.detalle_adicional ? `
                    <div class="detail-item full-width">
                        <label class="detail-label">
                            <i class="fas fa-info-circle"></i>
                            Detalle Adicional
                        </label>
                        <div class="detail-value detalle-content">
                            ${justificacion.detalle_adicional}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
        // Actualizar contenido del modal
        const modalBody = document.getElementById('modalBodyContent');
        if (modalBody) {
            modalBody.innerHTML = modalContent;
        }
        
        console.log('✅ Modal content updated successfully');
    }

    showNotification(message, type = 'info') {
        // Crear notificación temporal
        const notification = $(`
            <div class="notification notification-${type}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--primary)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: var(--shadow-lg);
                z-index: 10000;
                max-width: 400px;
            ">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
    }



    async deleteJustificacion(id) {
        if (confirm('¿Está seguro de que desea eliminar esta justificación?')) {
            try {
                const response = await fetch(`api/justificaciones.php?id=${id}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Justificación eliminada exitosamente.', 'success');
                    this.loadJustificaciones();
                    this.loadEstadisticas();
                } else {
                    this.showNotification('Error: ' + (data.message || 'No se pudo eliminar la justificación.'), 'error');
                }
            } catch (error) {
                console.error('Error eliminando justificación:', error);
                this.showNotification('Error de conexión. Intente nuevamente.', 'error');
            }
        }
    }
    
    /**
     * Renderizar controles de paginación
     */
    renderPagination() {
        // Actualizar información de paginación
        const start = ((this.currentPage - 1) * this.pageSize) + 1;
        const end = Math.min(this.currentPage * this.pageSize, this.totalRecords);
        $('#paginationInfo').text(`Mostrando ${start}-${end} de ${this.totalRecords} registros`);
        
        // Actualizar botones anterior/siguiente
        $('#prevPage').prop('disabled', this.currentPage <= 1);
        $('#nextPage').prop('disabled', this.currentPage >= this.totalPages);
        
        // Generar páginas
        this.renderPaginationPages();
    }
    
    /**
     * Renderizar números de páginas
     */
    renderPaginationPages() {
        const pagesContainer = $('#paginationPages');
        pagesContainer.empty();
        
        // Calcular rango de páginas a mostrar
        const maxPagesToShow = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(this.totalPages, startPage + maxPagesToShow - 1);
        
        // Ajustar si estamos cerca del final
        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        // Agregar primera página si no está visible
        if (startPage > 1) {
            pagesContainer.append(`
                <button class="btn btn-pagination-page" data-page="1">1</button>
                ${startPage > 2 ? '<span class="pagination-ellipsis">...</span>' : ''}
            `);
        }
        
        // Agregar páginas del rango
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === this.currentPage ? 'active' : '';
            pagesContainer.append(`
                <button class="btn btn-pagination-page ${isActive}" data-page="${i}">${i}</button>
            `);
        }
        
        // Agregar última página si no está visible
        if (endPage < this.totalPages) {
            pagesContainer.append(`
                ${endPage < this.totalPages - 1 ? '<span class="pagination-ellipsis">...</span>' : ''}
                <button class="btn btn-pagination-page" data-page="${this.totalPages}">${this.totalPages}</button>
            `);
        }
    }
    
    /**
     * Navegar a una página específica
     */
    goToPage(page) {
        if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
            this.currentPage = page;
            this.loadJustificaciones();
        }
    }
    
    /**
     * Cambiar tamaño de página
     */
    changePageSize(newSize) {
        this.pageSize = parseInt(newSize);
        this.currentPage = 1; // Resetear a primera página
        this.loadJustificaciones();
    }
    
    /**
     * Aplicar filtros (resetea a página 1)
     */
    applyFilters() {
        this.currentPage = 1; // Resetear a primera página cuando se aplican filtros
        this.loadJustificaciones();
    }
}

// Inicializar el módulo cuando el DOM esté listo
let justificacionesModule;
$(document).ready(() => {
    justificacionesModule = new JustificacionesModule();
});