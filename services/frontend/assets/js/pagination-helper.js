/**
 * Helper class para manejar paginación AJAX reutilizable en todos los módulos
 */
class PaginationHelper {
    constructor(config) {
        this.apiEndpoint = config.apiEndpoint;
        this.tableBodyId = config.tableBodyId;
        this.renderFunction = config.renderFunction;
        this.availableLimits = config.availableLimits || [10, 15, 20, 30, 40, 50];
        this.defaultLimit = config.defaultLimit || 10;
        
        // Estado
        this.currentPage = 1;
        this.currentLimit = this.defaultLimit;
        this.totalPages = 1;
        this.currentFilters = {};
        
        this.init();
    }

    init() {
        this.createPaginationControls();
        this.setupEventListeners();
    }

    createPaginationControls() {
        if (document.getElementById('paginationControls')) return;

        const controlsHTML = `
            <div class="pagination-controls" id="paginationControls">
                <div class="limit-selector">
                    <label for="limitSelector">Mostrar:</label>
                    <select id="limitSelector" class="form-control limit-select">
                        ${this.availableLimits.map(limit => 
                            `<option value="${limit}" ${limit === this.currentLimit ? 'selected' : ''}>${limit} registros</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="pagination-info">
                    <span id="paginationInfo">Cargando...</span>
                </div>
                <div class="pagination-buttons" id="paginationButtons">
                    <!-- Botones generados dinámicamente -->
                </div>
            </div>
        `;

        const tableContainer = document.getElementById(this.tableBodyId).closest('table').parentElement;
        tableContainer.insertAdjacentHTML('beforebegin', controlsHTML);
    }

    setupEventListeners() {
        // Selector de límite
        document.getElementById('limitSelector').addEventListener('change', (e) => {
            this.currentLimit = parseInt(e.target.value);
            this.currentPage = 1;
            this.loadData();
        });
    }

    async loadData() {
        try {
            this.showLoadingState();
            
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.currentLimit,
                ...this.currentFilters
            });

            const response = await fetch(`${this.apiEndpoint}?${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderFunction(data.data);
                this.updatePaginationInfo(data.pagination);
                this.renderPaginationButtons(data.pagination);
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            this.showErrorState(error.message);
        }
    }

    setFilters(filters) {
        this.currentFilters = { ...filters };
        this.currentPage = 1;
        this.loadData();
    }

    clearFilters() {
        this.currentFilters = {};
        this.currentPage = 1;
        this.loadData();
    }

    goToPage(page) {
        if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
            this.currentPage = page;
            this.loadData();
        }
    }

    showLoadingState() {
        const tbody = document.getElementById(this.tableBodyId);
        const colspan = tbody.closest('table').querySelector('thead tr').children.length;
        tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}" class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                </td>
            </tr>
        `;
    }

    showErrorState(message) {
        const tbody = document.getElementById(this.tableBodyId);
        const colspan = tbody.closest('table').querySelector('thead tr').children.length;
        tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}" class="error-state">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Error: ${message}
                    <button onclick="pagination.loadData()" class="btn-retry">Reintentar</button>
                </td>
            </tr>
        `;
    }

    updatePaginationInfo(pagination) {
        const info = document.getElementById('paginationInfo');
        if (info && pagination) {
            const start = ((pagination.current_page - 1) * pagination.limit) + 1;
            const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
            
            info.textContent = `Mostrando ${start} - ${end} de ${pagination.total_records} registros`;
            
            this.currentPage = pagination.current_page;
            this.totalPages = pagination.total_pages;
        }
    }

    renderPaginationButtons(pagination) {
        const container = document.getElementById('paginationButtons');
        if (!container || !pagination) return;

        let buttonsHTML = '';
        
        // Botón anterior
        if (pagination.has_prev) {
            buttonsHTML += `<button class="pagination-btn" onclick="pagination.goToPage(${pagination.current_page - 1})">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>`;
        }

        // Lógica de botones de páginas (similar a la anterior)
        const maxButtons = 5;
        let startPage = Math.max(1, pagination.current_page - Math.floor(maxButtons / 2));
        let endPage = Math.min(pagination.total_pages, startPage + maxButtons - 1);
        
        if (endPage - startPage + 1 < maxButtons) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        if (startPage > 1) {
            buttonsHTML += `<button class="pagination-btn" onclick="pagination.goToPage(1)">1</button>`;
            if (startPage > 2) {
                buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            buttonsHTML += `<button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                                onclick="pagination.goToPage(${i})">${i}</button>`;
        }

        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
            }
            buttonsHTML += `<button class="pagination-btn" onclick="pagination.goToPage(${pagination.total_pages})">${pagination.total_pages}</button>`;
        }

        // Botón siguiente
        if (pagination.has_next) {
            buttonsHTML += `<button class="pagination-btn" onclick="pagination.goToPage(${pagination.current_page + 1})">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>`;
        }

        container.innerHTML = buttonsHTML;
    }
}

// Función helper para renderizar empleados (ejemplo)
function renderEmployeesPaginated(data) {
    const tbody = document.getElementById('employeeTableBody');
    
    if (!data.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data-state">
                    <i class="fas fa-users"></i>
                    No se encontraron empleados
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = data.map(emp => `
        <tr>
            <td>${emp.id || ''}</td>
            <td>${emp.identificacion || ''}</td>
            <td>${emp.nombre || ''} ${emp.apellido || ''}</td>
            <td>${emp.email || ''}</td>
            <td>${emp.establecimiento || ''}</td>
            <td>${emp.sede || ''}</td>
            <td>${emp.fecha_contratacion || ''}</td>
            <td>
                <span class="${emp.estado === 'A' ? 'status-active' : 'status-inactive'}">
                    ${emp.estado === 'A' ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td>
                <button class="btn-icon btn-edit" title="Editar" onclick="editEmployee(${emp.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon btn-delete" title="Eliminar" onclick="deleteEmployee(${emp.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}