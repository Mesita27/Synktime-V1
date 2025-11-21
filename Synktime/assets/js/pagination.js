/**
 * Sistema de paginación unificado
 * Para usar en los diferentes módulos del sistema
 */

// Variables globales de paginación
let paginationState = {
    currentPage: 1,
    itemsPerPage: 10,
    totalItems: 0,
    pageCount: 0
};

/**
 * Inicializar el sistema de paginación
 * @param {Object} options - Opciones de inicialización
 * @param {number} options.currentPage - Página actual
 * @param {number} options.itemsPerPage - Elementos por página
 * @param {number} options.totalItems - Total de elementos
 * @param {string} options.containerId - ID del contenedor de paginación
 * @param {Function} options.onPageChange - Función a ejecutar cuando cambia la página
 */
function initPagination(options = {}) {
    // Establecer valores por defecto o usar los proporcionados
    paginationState.currentPage = options.currentPage || 1;
    paginationState.itemsPerPage = options.itemsPerPage || 10;
    paginationState.totalItems = options.totalItems || 0;
    
    // Calcular el número de páginas
    paginationState.pageCount = Math.ceil(paginationState.totalItems / paginationState.itemsPerPage);
    
    // Si se proporciona un contenedor, renderizar la paginación
    const container = document.getElementById(options.containerId);
    if (container) {
        renderPaginationControls(container, options.onPageChange);
    }
    
    return paginationState;
}

/**
 * Renderizar los controles de paginación
 * @param {HTMLElement} container - Contenedor donde se renderizará la paginación
 * @param {Function} onPageChange - Función a ejecutar cuando cambia la página
 */
function renderPaginationControls(container, onPageChange = null) {
    // Si no hay páginas o sólo hay una, no mostrar paginación
    if (paginationState.pageCount <= 1) {
        container.innerHTML = '';
        return;
    }
    
    // Crear los elementos de paginación
    let html = `
        <div class="pagination-controls">
            <div class="pagination-info">
                Mostrando página ${paginationState.currentPage} de ${paginationState.pageCount}
            </div>
            <div class="pagination-buttons">
    `;
    
    // Botón anterior
    html += `
        <button class="pagination-btn" 
                ${paginationState.currentPage === 1 ? 'disabled' : ''} 
                onclick="changePage(${paginationState.currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    // Primera página
    if (paginationState.currentPage > 3) {
        html += `<button class="pagination-btn" onclick="changePage(1)">1</button>`;
    }
    
    // Elipsis inicial
    if (paginationState.currentPage > 4) {
        html += `<span class="pagination-ellipsis">...</span>`;
    }
    
    // Páginas centrales
    for (let i = Math.max(1, paginationState.currentPage - 2); 
         i <= Math.min(paginationState.pageCount, paginationState.currentPage + 2); i++) {
        html += `
            <button class="pagination-btn ${i === paginationState.currentPage ? 'active' : ''}" 
                    onclick="changePage(${i})">
                ${i}
            </button>
        `;
    }
    
    // Elipsis final
    if (paginationState.currentPage < paginationState.pageCount - 3) {
        html += `<span class="pagination-ellipsis">...</span>`;
    }
    
    // Última página
    if (paginationState.currentPage < paginationState.pageCount - 2) {
        html += `
            <button class="pagination-btn" onclick="changePage(${paginationState.pageCount})">
                ${paginationState.pageCount}
            </button>
        `;
    }
    
    // Botón siguiente
    html += `
        <button class="pagination-btn" 
                ${paginationState.currentPage === paginationState.pageCount ? 'disabled' : ''} 
                onclick="changePage(${paginationState.currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    html += `
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Asignar el callback de cambio de página si se proporciona
    window.changePage = function(page) {
        if (page < 1 || page > paginationState.pageCount) return;
        paginationState.currentPage = page;
        
        // Volver a renderizar los controles
        renderPaginationControls(container, onPageChange);
        
        // Llamar a la función de cambio de página si existe
        if (typeof onPageChange === 'function') {
            onPageChange(page);
        }
    };
}

/**
 * Actualizar el estado de paginación y volver a renderizar
 * @param {Object} options - Opciones para actualizar
 */
function updatePaginationState(options = {}) {
    // Actualizar el estado con los valores proporcionados
    if (options.currentPage !== undefined) paginationState.currentPage = options.currentPage;
    if (options.itemsPerPage !== undefined) paginationState.itemsPerPage = options.itemsPerPage;
    if (options.totalItems !== undefined) {
        paginationState.totalItems = options.totalItems;
        // Recalcular el número de páginas
        paginationState.pageCount = Math.ceil(paginationState.totalItems / paginationState.itemsPerPage);
    }
    
    // Si la página actual es mayor que el número de páginas, ajustarla
    if (paginationState.currentPage > paginationState.pageCount && paginationState.pageCount > 0) {
        paginationState.currentPage = paginationState.pageCount;
    }
    
    // Si la página actual es menor que 1, ajustarla
    if (paginationState.currentPage < 1) {
        paginationState.currentPage = 1;
    }
    
    // Si se proporciona un contenedor, volver a renderizar
    if (options.containerId) {
        const container = document.getElementById(options.containerId);
        if (container) {
            renderPaginationControls(container, options.onPageChange);
        }
    }
    
    return paginationState;
}
