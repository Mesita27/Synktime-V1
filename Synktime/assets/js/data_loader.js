/**
 * SynkTime - DataLoader
 * Clase para gestionar la carga AJAX de datos con paginación
 * para todos los módulos del sistema
 */
class DataLoader {
    /**
     * Constructor de DataLoader
     * @param {Object} config - Configuración del data loader
     * @param {string} config.containerId - ID del contenedor donde se muestran los datos
     * @param {string} config.endpoint - URL del API endpoint para cargar datos
     * @param {Function} config.renderCallback - Función para renderizar los datos
     * @param {string} config.paginationContainerId - ID del contenedor de la paginación
     * @param {Object} config.defaultFilters - Filtros por defecto
     */
    constructor(config) {
        this.containerId = config.containerId;
        this.endpoint = config.endpoint;
        this.renderCallback = config.renderCallback;
        this.paginationContainerId = config.paginationContainerId || 'pagination-container';
        this.loaderContainerId = config.loaderContainerId || 'loader-container';
        
        this.currentPage = 1;
        this.itemsPerPage = 10; // Por defecto 10 items
        this.totalItems = 0;
        this.totalPages = 0;
        this.filters = config.defaultFilters || {};
        
        this.setupPerPageSelector();
    }
    
    /**
     * Configuración del selector de items por página
     */
    setupPerPageSelector() {
        // Crear el selector de items por página si no existe
        if (!document.getElementById('perPageSelector')) {
            const selectorContainer = document.createElement('div');
            selectorContainer.className = 'per-page-selector';
            selectorContainer.innerHTML = `
                <label for="perPageSelector">Mostrar:</label>
                <select id="perPageSelector" class="form-control form-control-sm">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                    <option value="40">40</option>
                    <option value="50">50</option>
                </select>
                <span>registros</span>
            `;
            
            // Insertar antes del container de paginación
            const paginationContainer = document.getElementById(this.paginationContainerId);
            if (paginationContainer) {
                paginationContainer.parentNode.insertBefore(selectorContainer, paginationContainer);
            }
            
            // Manejar el evento change
            document.getElementById('perPageSelector').addEventListener('change', (e) => {
                this.itemsPerPage = parseInt(e.target.value);
                this.currentPage = 1; // Resetear a la primera página
                this.loadData();
            });
        }
    }
    
    /**
     * Aplicar filtros a la carga de datos
     * @param {Object} filters - Filtros a aplicar
     */
    applyFilters(filters) {
        this.filters = {...this.filters, ...filters};
        this.currentPage = 1; // Resetear a la primera página
        this.loadData();
    }
    
    /**
     * Resetear filtros y recargar datos
     */
    resetFilters() {
        this.filters = {};
        this.currentPage = 1;
        this.loadData();
    }
    
    /**
     * Cargar datos desde el API
     */
    loadData() {
        // Mostrar loader
        this.showLoader();
        
        // Construir parámetros
        const params = new URLSearchParams({
            page: this.currentPage,
            per_page: this.itemsPerPage,
            ...this.filters
        });
        
        // Hacer la petición AJAX
        fetch(`${this.endpoint}?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                this.totalItems = data.total || 0;
                this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
                
                // Renderizar datos usando el callback
                if (typeof this.renderCallback === 'function') {
                    this.renderCallback(data.data || []);
                }
                
                // Actualizar paginación
                this.renderPagination();
                
                // Ocultar loader
                this.hideLoader();
            })
            .catch(error => {
                console.error('Error al cargar los datos:', error);
                this.hideLoader();
                this.showError('Error al cargar los datos. Intente nuevamente.');
            });
    }
    
    /**
     * Mostrar loader durante la carga
     */
    showLoader() {
        const container = document.getElementById(this.containerId);
        if (container) {
            // Crear loader si no existe
            let loader = document.getElementById(this.loaderContainerId);
            if (!loader) {
                loader = document.createElement('div');
                loader.id = this.loaderContainerId;
                loader.className = 'data-loader';
                loader.innerHTML = '<div class="spinner"></div>';
                document.body.appendChild(loader);
            }
            loader.style.display = 'flex';
        }
    }
    
    /**
     * Ocultar loader
     */
    hideLoader() {
        const loader = document.getElementById(this.loaderContainerId);
        if (loader) {
            loader.style.display = 'none';
        }
    }
    
    /**
     * Mostrar mensaje de error
     */
    showError(message) {
        const container = document.getElementById(this.containerId);
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            `;
        }
    }
    
    /**
     * Renderizar controles de paginación
     */
    renderPagination() {
        const container = document.getElementById(this.paginationContainerId);
        if (!container) return;
        
        let html = '';
        
        if (this.totalPages > 1) {
            html = '<ul class="pagination">';
            
            // Botón anterior
            html += `
                <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${this.currentPage - 1}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
            
            // Mostrar páginas
            const maxPages = 5;
            let startPage = Math.max(1, this.currentPage - Math.floor(maxPages / 2));
            let endPage = Math.min(this.totalPages, startPage + maxPages - 1);
            
            // Ajustar startPage si es necesario
            if (endPage - startPage + 1 < maxPages) {
                startPage = Math.max(1, endPage - maxPages + 1);
            }
            
            // Primera página si no está en rango
            if (startPage > 1) {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="1">1</a>
                    </li>
                `;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            
            // Páginas numeradas
            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
            
            // Última página si no está en rango
            if (endPage < this.totalPages) {
                if (endPage < this.totalPages - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${this.totalPages}">${this.totalPages}</a>
                    </li>
                `;
            }
            
            // Botón siguiente
            html += `
                <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${this.currentPage + 1}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
            
            html += '</ul>';
            
            // Información de paginación
            html += `
                <div class="pagination-info">
                    Mostrando ${Math.min((this.currentPage - 1) * this.itemsPerPage + 1, this.totalItems)} - 
                    ${Math.min(this.currentPage * this.itemsPerPage, this.totalItems)} 
                    de ${this.totalItems} registros
                </div>
            `;
        }
        
        container.innerHTML = html;
        
        // Agregar event listeners a los links de paginación
        const pageLinks = container.querySelectorAll('.page-link');
        pageLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (!isNaN(page) && page !== this.currentPage && page > 0 && page <= this.totalPages) {
                    this.currentPage = page;
                    this.loadData();
                }
            });
        });
    }
}