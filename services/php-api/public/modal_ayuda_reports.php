<?php
// modal_ayuda_reports.php - Modal de ayuda optimizado para el módulo de reportes
?>
<!-- Modal de Ayuda para Reportes -->
<div id="reportsHelpModal" class="reports-modal-overlay" style="display: none;">
    <div class="reports-modal-container">
        <div class="reports-modal-content">
            <!-- Header del Modal -->
            <div class="reports-modal-header">
                <div class="reports-modal-title">
                    <i class="fas fa-file-alt"></i>
                    <div class="reports-modal-title-text">
                        <h2>Reportes de Asistencia</h2>
                        <p>Guía rápida del módulo</p>
                    </div>
                </div>
                <button class="reports-modal-close" onclick="closeReportsHelpModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="reports-modal-tabs">
                <button class="reports-tab-btn active" onclick="showReportsTab('general')">
                    <i class="fas fa-info-circle"></i> General
                </button>
                <button class="reports-tab-btn" onclick="showReportsTab('filtros')">
                    <i class="fas fa-filter"></i> Filtros
                </button>
                <button class="reports-tab-btn" onclick="showReportsTab('exportacion')">
                    <i class="fas fa-file-export"></i> Exportar
                </button>
            </div>

            <!-- Body del Modal -->
            <div class="reports-modal-body">
                <div class="reports-help-content">

                    <!-- Tab: General -->
                    <div id="reports-tab-general" class="reports-tab-content active">
                        <div class="reports-help-section">
                            <div class="reports-intro">
                                <i class="fas fa-file-alt"></i>
                                <div class="reports-intro-content">
                                    <h3>Módulo de Reportes</h3>
                                    <p>Genera informes detallados de asistencia. Consulta, filtra y exporta datos de empleados de manera eficiente.</p>
                                </div>
                            </div>
                        </div>

                        <div class="reports-help-section">
                            <h3 class="reports-section-title">
                                <i class="fas fa-cogs"></i> Funcionalidades
                            </h3>
                            <div class="reports-features-grid">
                                <div class="reports-feature-card">
                                    <i class="fas fa-search"></i>
                                    <div class="reports-feature-content">
                                        <h4>Consulta de Datos</h4>
                                        <p>Acceso completo a registros de asistencia con búsqueda avanzada.</p>
                                    </div>
                                </div>
                                <div class="reports-feature-card">
                                    <i class="fas fa-filter"></i>
                                    <div class="reports-feature-content">
                                        <h4>Filtros Avanzados</h4>
                                        <p>Filtra por empleado, sede, fechas y estados de asistencia.</p>
                                    </div>
                                </div>
                                <div class="reports-feature-card">
                                    <i class="fas fa-file-excel"></i>
                                    <div class="reports-feature-content">
                                        <h4>Exportación Excel</h4>
                                        <p>Descarga reportes en formato Excel con datos estructurados.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="reports-help-section">
                            <h3 class="reports-section-title">
                                <i class="fas fa-play"></i> Cómo usar
                            </h3>
                            <div class="reports-steps">
                                <div class="reports-step">
                                    <span class="reports-step-number">1</span>
                                    <div class="reports-step-content">
                                        <h4>Seleccionar período</h4>
                                        <p>Usa filtros rápidos o rango personalizado de fechas.</p>
                                    </div>
                                </div>
                                <div class="reports-step">
                                    <span class="reports-step-number">2</span>
                                    <div class="reports-step-content">
                                        <h4>Aplicar filtros</h4>
                                        <p>Filtra por código, nombre, sede, establecimiento o estado.</p>
                                    </div>
                                </div>
                                <div class="reports-step">
                                    <span class="reports-step-number">3</span>
                                    <div class="reports-step-content">
                                        <h4>Generar reporte</h4>
                                        <p>Haz clic en "Buscar" para ver los datos.</p>
                                    </div>
                                </div>
                                <div class="reports-step">
                                    <span class="reports-step-number">4</span>
                                    <div class="reports-step-content">
                                        <h4>Exportar</h4>
                                        <p>Usa "Exportar a XLS" para descargar el reporte.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Filtros -->
                    <div id="reports-tab-filtros" class="reports-tab-content">
                        <div class="reports-help-section">
                            <h3 class="reports-section-title">
                                <i class="fas fa-filter"></i> Sistema de Filtros
                            </h3>
                            <div class="reports-filter-types">
                                <div class="reports-filter-type">
                                    <h4><i class="fas fa-calendar-alt"></i> Fechas</h4>
                                    <ul>
                                        <li><strong>Rápidos:</strong> Hoy, Últimos 7 días, Semana actual, etc.</li>
                                        <li><strong>Personalizado:</strong> Selecciona fechas específicas</li>
                                    </ul>
                                </div>
                                <div class="reports-filter-type">
                                    <h4><i class="fas fa-users"></i> Empleados</h4>
                                    <ul>
                                        <li><strong>Código:</strong> Búsqueda por código único</li>
                                        <li><strong>Nombre:</strong> Búsqueda parcial por nombre</li>
                                    </ul>
                                </div>
                                <div class="reports-filter-type">
                                    <h4><i class="fas fa-building"></i> Ubicación</h4>
                                    <ul>
                                        <li><strong>Sede:</strong> Filtrar por ubicación geográfica</li>
                                        <li><strong>Establecimiento:</strong> Filtrar por sucursal específica</li>
                                    </ul>
                                </div>
                                <div class="reports-filter-type">
                                    <h4><i class="fas fa-clock"></i> Estado</h4>
                                    <ul>
                                        <li><strong>A Tiempo:</strong> Entradas puntuales</li>
                                        <li><strong>Tardanza:</strong> Entradas retrasadas</li>
                                        <li><strong>Justificado:</strong> Inasistencias con justificación</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="reports-tips">
                            <div class="reports-tip">
                                <i class="fas fa-lightbulb"></i>
                                <span>Combina múltiples filtros para resultados más precisos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Exportación -->
                    <div id="reports-tab-exportacion" class="reports-tab-content">
                        <div class="reports-help-section">
                            <h3 class="reports-section-title">
                                <i class="fas fa-file-export"></i> Exportación
                            </h3>
                            <div class="reports-export-info">
                                <p>Descarga reportes en formato Excel (.xlsx) con formato profesional y datos completos.</p>
                                <div class="reports-export-features">
                                    <h4>Archivo incluye:</h4>
                                    <ul>
                                        <li><i class="fas fa-check"></i> Encabezados descriptivos</li>
                                        <li><i class="fas fa-check"></i> Datos organizados por columnas</li>
                                        <li><i class="fas fa-check"></i> Fechas en formato legible</li>
                                        <li><i class="fas fa-check"></i> Información completa de registros</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="reports-help-section">
                            <h3 class="reports-section-title">
                                <i class="fas fa-play"></i> Pasos para exportar
                            </h3>
                            <div class="reports-export-steps">
                                <div class="reports-export-step">
                                    <span class="reports-step-number">1</span>
                                    <p>Aplica los filtros deseados y genera el reporte</p>
                                </div>
                                <div class="reports-export-step">
                                    <span class="reports-step-number">2</span>
                                    <p>Verifica que los datos sean correctos</p>
                                </div>
                                <div class="reports-export-step">
                                    <span class="reports-step-number">3</span>
                                    <p>Haz clic en "Exportar a XLS"</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="reports-modal-footer">
                <button class="reports-btn-secondary" onclick="closeReportsHelpModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal de Reportes - Optimizado para dispositivos */
.reports-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.reports-modal-container {
    width: 95%;
    max-width: 800px;
    max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.reports-modal-content {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

/* Header */
.reports-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #667eea;
    color: #fff;
}

.reports-modal-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.reports-modal-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.reports-modal-title-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.reports-modal-title-text p {
    margin: 4px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.reports-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: background 0.2s;
}

.reports-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Tabs */
.reports-modal-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.reports-tab-btn {
    flex: 1;
    padding: 12px 16px;
    border: none;
    background: transparent;
    color: #6c757d;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
}

.reports-tab-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.reports-tab-btn.active {
    color: #667eea;
    background: #fff;
    border-bottom: 2px solid #667eea;
}

/* Body */
.reports-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    max-height: calc(90vh - 120px);
}

/* Tab Content */
.reports-tab-content {
    display: none;
}

.reports-tab-content.active {
    display: block;
}

/* Help Sections */
.reports-help-section {
    margin-bottom: 24px;
}

.reports-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #dee2e6;
}

/* Intro */
.reports-intro {
    display: flex;
    gap: 16px;
    background: #f8f9ff;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e3f2fd;
    margin-bottom: 20px;
}

.reports-intro-icon {
    width: 48px;
    height: 48px;
    background: #667eea;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}

.reports-intro-content h3 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
}

.reports-intro-content p {
    margin: 0;
    color: #6c757d;
    line-height: 1.5;
}

/* Features Grid */
.reports-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.reports-feature-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    gap: 12px;
    transition: box-shadow 0.2s;
}

.reports-feature-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.reports-feature-icon {
    width: 40px;
    height: 40px;
    background: #667eea;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    flex-shrink: 0;
}

.reports-feature-content h4 {
    margin: 0 0 6px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.reports-feature-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Steps */
.reports-steps {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.reports-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.reports-step-number {
    width: 28px;
    height: 28px;
    background: #667eea;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    flex-shrink: 0;
}

.reports-step-content h4 {
    margin: 0 0 4px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.reports-step-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Filter Types */
.reports-filter-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.reports-filter-type {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.reports-filter-type h4 {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #495057;
    margin: 0 0 10px 0;
    font-size: 15px;
    font-weight: 600;
}

.reports-filter-type ul {
    margin: 0;
    padding-left: 16px;
}

.reports-filter-type li {
    margin-bottom: 6px;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.reports-filter-type strong {
    color: #495057;
}

/* Tips */
.reports-tips {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.reports-tip {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #e8f5e8;
    padding: 10px 12px;
    border-radius: 6px;
    border-left: 3px solid #4caf50;
}

.reports-tip i {
    color: #4caf50;
    font-size: 14px;
}

.reports-tip span {
    color: #2e7d32;
    font-weight: 500;
    font-size: 14px;
}

/* Export Info */
.reports-export-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.reports-export-description {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.reports-export-description p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.5;
}

.reports-export-features h4 {
    margin: 0 0 10px 0;
    color: #495057;
    font-size: 15px;
    font-weight: 600;
}

.reports-export-features ul {
    margin: 0;
    padding-left: 16px;
}

.reports-export-features li {
    margin-bottom: 6px;
    color: #6c757d;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.reports-export-features i {
    color: #4caf50;
    width: 14px;
}

/* Export Steps */
.reports-export-steps {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.reports-export-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

/* Chart Types */
.reports-chart-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.reports-chart-type {
    display: flex;
    gap: 12px;
    background: #fff;
    padding: 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    transition: box-shadow 0.2s;
}

.reports-chart-type:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.reports-chart-icon {
    width: 36px;
    height: 36px;
    background: #667eea;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    flex-shrink: 0;
}

.reports-chart-content h4 {
    margin: 0 0 4px 0;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 600;
}

.reports-chart-content p {
    margin: 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.3;
}

/* Additional Info */
.reports-additional-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.reports-info-item {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.reports-info-item h4 {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0 0 6px 0;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
}

.reports-info-item h4 i {
    color: #667eea;
}

.reports-info-item p {
    margin: 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.3;
}

/* Footer */
.reports-modal-footer {
    padding: 16px 20px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
}

.reports-btn-secondary {
    padding: 8px 16px;
    background: #6c757d;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}

.reports-btn-secondary:hover {
    background: #5a6268;
}

/* Responsive Design */
@media (max-width: 768px) {
    .reports-modal-container {
        width: 98%;
        margin: 5px;
        max-height: 95vh;
    }

    .reports-modal-header {
        padding: 12px 16px;
    }

    .reports-modal-title {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }

    .reports-modal-title-text h2 {
        font-size: 18px;
    }

    .reports-modal-tabs {
        flex-wrap: wrap;
    }

    .reports-tab-btn {
        padding: 10px 12px;
        font-size: 13px;
    }

    .reports-modal-body {
        padding: 16px;
    }

    .reports-intro {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }

    .reports-features-grid,
    .reports-filter-types,
    .reports-export-info,
    .reports-chart-types,
    .reports-additional-info {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .reports-step {
        gap: 10px;
    }

    .reports-step-number {
        width: 24px;
        height: 24px;
        font-size: 11px;
    }
}

@media (max-width: 480px) {
    .reports-modal-container {
        width: 100%;
        margin: 0;
        border-radius: 0;
        max-height: 100vh;
    }

    .reports-modal-header {
        padding: 10px 12px;
    }

    .reports-modal-title-text h2 {
        font-size: 16px;
    }

    .reports-modal-body {
        padding: 12px;
    }

    .reports-feature-card,
    .reports-filter-type,
    .reports-export-description {
        padding: 12px;
    }
}
</style>

<script>
// Funciones del modal de reportes
function showReportsHelpModal() {
    console.log('🔧 Abriendo modal de ayuda de reportes...');

    const modal = document.getElementById('reportsHelpModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Mostrar primera tab por defecto
        showReportsTab('general');

        console.log('✅ Modal de ayuda de reportes abierto');
    } else {
        console.error('❌ Modal de ayuda de reportes no encontrado');
    }
}

function closeReportsHelpModal() {
    console.log('🔧 Cerrando modal de ayuda de reportes...');

    const modal = document.getElementById('reportsHelpModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        console.log('✅ Modal de ayuda de reportes cerrado');
    }
}

// Función para cambiar de tab
function showReportsTab(tabName) {
    console.log('🔄 Cambiando a tab:', tabName);

    // Ocultar todos los contenidos de tab
    const tabContents = document.querySelectorAll('.reports-tab-content');
    tabContents.forEach(content => content.classList.remove('active'));

    // Remover clase active de todos los botones
    const tabBtns = document.querySelectorAll('.reports-tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));

    // Mostrar tab seleccionado
    const selectedTab = document.getElementById('reports-tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Activar botón correspondiente
    const selectedBtn = document.querySelector(`[onclick="showReportsTab('${tabName}')"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }

    console.log('✅ Tab cambiado a:', tabName);
}

// Función de compatibilidad para el código existente
function hideReportsHelpModal() {
    closeReportsHelpModal();
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Modal de ayuda de reportes inicializado');

    // Verificar que el modal existe
    const modal = document.getElementById('reportsHelpModal');
    if (modal) {
        console.log('✅ Modal de ayuda de reportes encontrado en el DOM');
    } else {
        console.warn('⚠️ Modal de ayuda de reportes no encontrado en el DOM');
    }
});
</script></content>
<parameter name="filePath">c:\Users\datam\Downloads\Synktime\modal_ayuda_reports.php