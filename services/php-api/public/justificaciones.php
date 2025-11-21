<?php
/**
 * Módulo de Gestión de Justificaciones de Faltas
 * Sistema completo de justificaciones con validaciones y filtros avanzados
 */
require_once 'config/database.php';
require_once 'auth/session.php';

// Verificar autenticación - Todos los usuarios tienen acceso
requireModuleAccess(['ADMIN', 'GERENTE', 'SUPERVISOR', 'ASISTENCIA']);

$usuarioInfo = getCurrentUser();
$empresaId = $usuarioInfo ? $usuarioInfo['id_empresa'] : 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SynkTime - Justificaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
    <style>
        /* Estilos específicos para el módulo de justificaciones */
        .justificaciones-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
        }

        .filters-container {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .filter-select, .filter-input {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.3s ease;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-lighter);
        }

        .quick-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .quick-filter-btn {
            padding: 0.5rem 1rem;
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-filter-btn:hover, .quick-filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .tab-content.active {
            display: block;
        }

        .justificaciones-table {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table-header {
            background: var(--background);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .table-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--background);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .data-table tbody tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pendiente {
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
        }

        .status-aprobada {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
        }

        .status-rechazada {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
        }

        .status-revision {
            background: rgba(108, 117, 125, 0.1);
            color: #495057;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: var(--primary-lighter);
            color: var(--primary);
        }

        .btn-edit {
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
        }

        .btn-delete {
            background: var(--danger-lighter);
            color: var(--danger);
        }

        .btn-help {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.75rem;
        }

        .btn-help:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }

        .btn-help i {
            font-size: 1rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .crear-justificacion-form {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .form-control {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-lighter);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .empleado-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .empleado-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .empleado-card.selected {
            border-color: var(--primary);
            background: var(--primary-lighter);
        }

        .empleado-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .empleado-details {
            flex: 1;
        }

        .empleado-nombre {
            font-weight: 600;
            color: var(--text-primary);
        }

        .empleado-meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .turnos-faltantes {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .justificaciones-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'components/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php include 'components/header.php'; ?>
            <main class="main-content">
                <div class="justificaciones-container">
                    <!-- Header -->
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class="fas fa-user-times"></i>
                            Gestión de Justificaciones
                        </h1>
                        <div class="header-actions">
                            <button class="btn btn-info" id="btnAyudaJustificaciones" onclick="showHelpModal()" style="background-color: #007bff; border-color: #007bff; color: white;" title="Ayuda del módulo" aria-label="Mostrar ayuda del módulo de justificaciones">
                                <i class="fas fa-question-circle"></i>
                                Ayuda
                            </button>
                            <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaJustificacion">
                                <i class="fas fa-plus-circle"></i>
                                Nueva Justificación
                            </button>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="filters-container">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label for="filtroEmpleado">Buscar Empleado</label>
                                <input type="text" id="filtroEmpleado" class="filter-input" placeholder="Buscar por nombre...">
                            </div>
                            <div class="filter-group">
                                <label for="filtroSede">Sede</label>
                                <select id="filtroSede" class="filter-select">
                                    <option value="">Todas las sedes</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="filtroEstablecimiento">Establecimiento</label>
                                <select id="filtroEstablecimiento" class="filter-select">
                                    <option value="">Todos los establecimientos</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="filtroFechaDesde">Desde</label>
                                <input type="date" id="filtroFechaDesde" class="filter-input">
                            </div>
                            <div class="filter-group">
                                <label for="filtroFechaHasta">Hasta</label>
                                <input type="date" id="filtroFechaHasta" class="filter-input">
                            </div>
                            <div class="filter-group">
                                <label>&nbsp;</label>
                                <button id="btnAplicarFiltros" class="btn-primary">
                                    <i class="fas fa-search"></i>
                                    Aplicar Búsqueda
                                </button>
                            </div>
                        </div>
                        <div class="quick-filters">
                            <button class="quick-filter-btn active" data-filter="all">Todas</button>
                            <button class="quick-filter-btn" data-filter="today">Hoy</button>
                            <button class="quick-filter-btn" data-filter="week">Esta Semana</button>
                            <button class="quick-filter-btn" data-filter="month">Este Mes</button>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value" id="statTotal">0</div>
                            <div class="stat-label">Total Justificaciones</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="statMotivoComun">-</div>
                            <div class="stat-label">Motivo Más Común</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="statJornadaParcial">0</div>
                            <div class="stat-label">Jornada Parcial</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="statJornadaCompleta">0</div>
                            <div class="stat-label">Jornada Completa</div>
                        </div>
                    </div>

                    <!-- Lista de justificaciones -->
                    <div class="justificaciones-table">
                        <div class="table-header">
                            <h3 class="table-title">Justificaciones Registradas</h3>
                        </div>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Fecha Falta</th>
                                        <th>Turno/Horario</th>
                                        <th>Motivo</th>
                                        <th>Tipo</th>
                                        <th>Creada</th>
                                        <th>Observación</th>
                                    </tr>
                                </thead>
                                <tbody id="justificacionesTableBody">
                                    <tr>
                                        <td colspan="7" class="loading">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            Cargando justificaciones...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Controles de Paginación -->
                        <div class="pagination-container mt-3">
                            <div class="pagination-info">
                                <span id="paginationInfo">Mostrando 0 de 0 registros</span>
                            </div>
                            <div class="pagination-controls">
                                <button id="prevPage" class="btn btn-pagination" disabled>
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </button>
                                <div class="pagination-pages" id="paginationPages">
                                    <!-- Páginas se generan dinámicamente -->
                                </div>
                                <button id="nextPage" class="btn btn-pagination" disabled>
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="pagination-size">
                                <label for="pageSize">Registros por página:</label>
                                <select id="pageSize" class="form-control form-control-sm">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Incluir el modal -->
    <?php include 'components/modal_justificacion.php'; ?>

    <style>
        .detail-group {
            margin-bottom: 1.5rem;
        }
        
        .detail-group label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-group p {
            color: var(--text-primary);
            margin: 0;
            padding: 0.75rem 1rem;
            background: var(--bg-light);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* Estilos para observaciones expandibles */
        .observacion-expandible {
            background: var(--surface, #ffffff) !important;
            border-radius: var(--border-radius, 8px) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
            max-width: 90vw !important;
            width: 800px !important;
            max-height: 90vh !important;
            overflow: hidden !important;
            transform: scale(0.8) translateY(-30px) !important;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            border: 1px solid var(--border, #e0e0e0) !important;
            position: relative !important;
            /* Reset Bootstrap modal content styles */
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
            /* Asegurar que tenga dimensiones mínimas */
            min-width: 400px !important;
            min-height: 300px !important;
        }
        
        #modalDetallesJustificacion.show .custom-modal {
            transform: scale(1) translateY(0) !important;
        }
        
        #modalDetallesJustificacion .custom-modal-header {
            background: var(--primary) !important;
            color: white !important;
            padding: 1.5rem !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-bottom: 1px solid var(--border) !important;
        }
        
        #modalDetallesJustificacion .custom-modal-title {
            margin: 0 !important;
            font-size: 1.25rem !important;
            font-weight: 600 !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            color: white !important;
        }
        
        #modalDetallesJustificacion .custom-modal-close {
            background: none !important;
            border: none !important;
            color: white !important;
            font-size: 1.25rem !important;
            cursor: pointer !important;
            padding: 0.5rem !important;
            border-radius: 6px !important;
            transition: background-color 0.3s ease !important;
        }
        
        #modalDetallesJustificacion .custom-modal-close:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }
        
        #modalDetallesJustificacion .custom-modal-body {
            padding: 1.5rem !important;
            max-height: 60vh !important;
            overflow-y: auto !important;
            background: var(--surface) !important;
        }
        
        .custom-modal-footer {
            padding: 1rem 1.5rem;
            background: var(--bg-light);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        .loading-content {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto 1rem;
            border: 3px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            background: var(--text-secondary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: var(--text-primary);
            transform: translateY(-1px);
        }
        
        /* Estilos para contenido del modal personalizado */
        .justificacion-details {
            font-family: 'Inter', sans-serif;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .detail-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .detail-item {
            background: var(--bg-light);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        
        .detail-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-size: 0.95rem;
            color: var(--text-primary);
            line-height: 1.5;
        }
        
        .motivo-content, .detalle-content {
            background: white;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            margin-top: 0.5rem;
        }
        
        .badge-alcance {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-alcance.completa {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #b8daff;
        }
        
        .badge-alcance.parcial {
            background: #fef3cd;
            color: #856404;
            border: 1px solid #fde68a;
        }
        
        .error-content {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        
        .error-icon {
            font-size: 3rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        
        .error-content h4 {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .custom-modal {
                margin: 1rem;
                width: calc(100% - 2rem);
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .custom-modal-body {
                padding: 1rem;
            }
        }
        
        .badge-tipo-falta {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .badge-tipo-falta.completa {
            background: #fef3cd;
            color: #856404;
            border: 1px solid #fde68a;
        }
        
        .badge-tipo-falta.parcial {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #b8daff;
        }
        
        .badge-tipo-falta.tardanza {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Estilos para información de turno */
        .turno-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .turno-nombre {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
        }
        
        .turno-horario {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .turno-detalle {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }
        
        .turno-detalle p {
            margin: 0 0 4px 0;
        }
        
        .turno-detalle small {
            display: block;
            font-size: 0.85rem;
        }
    </style>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Funciones para el modal personalizado de ayuda -->
    <script>
        function showHelpModal() {
            console.log('🚀 showHelpModal() called - FORZANDO APARICIÓN');

            try {
                let modal = document.getElementById('modalAyudaJustificaciones');
                const button = document.getElementById('btnAyudaJustificaciones');

                // Si el modal no existe, cargarlo dinámicamente
                if (!modal) {
                    console.log('📥 Modal not found, loading from external PHP file...');

                    // Cargar el modal desde el archivo PHP externo
                    fetch('modal_ayuda_justificaciones.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.status);
                            }
                            return response.text();
                        })
                        .then(html => {
                            console.log('✅ Modal PHP loaded, injecting into DOM...');

                            // Crear un contenedor temporal para parsear el HTML
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;

                            // Extraer el modal del HTML cargado
                            const loadedModal = tempDiv.querySelector('#modalAyudaJustificaciones');
                            if (loadedModal) {
                                // Agregar el modal al body
                                document.body.appendChild(loadedModal);
                                console.log('✅ Modal injected into DOM successfully');

                                // Ahora mostrar el modal con fuerza
                                setTimeout(() => showHelpModal(), 100);
                            } else {
                                console.error('❌ Modal not found in loaded HTML');
                                alert('Error: No se pudo cargar el modal de ayuda.');
                            }
                        })
                        .catch(error => {
                            console.error('❌ Error loading modal:', error);
                            alert('Error al cargar el modal de ayuda: ' + error.message);
                        });

                    return; // Salir ya que la carga es asíncrona
                }

                console.log('✅ Modal element found, showing modal...');

                // Agregar clase show para mostrar el modal
                modal.classList.add('show');

                // Agregar clase modal-open al body
                document.body.style.overflow = 'hidden';
                document.body.classList.add('modal-open');

                // Agregar clase active al botón
                if (button) {
                    button.classList.add('active');
                }

                // Agregar event listeners para cerrar
                document.addEventListener('keydown', handleEscapeKey);
                modal.addEventListener('click', handleOverlayClick);

                console.log('✅ Modal FORZADO A APARECER EXITOSAMENTE');

                // Focus trap para accesibilidad
                setTimeout(() => {
                    const firstFocusableElement = modal.querySelector('.custom-modal-close');
                    if (firstFocusableElement) {
                        firstFocusableElement.focus();
                    }
                }, 100);

            } catch (error) {
                console.error('❌ Error showing modal:', error);
                alert('Error al mostrar el modal de ayuda: ' + error.message);
            }
        }

        function hideHelpModal() {
            console.log('🎯 hideHelpModal() called');

            try {
                const modal = document.getElementById('modalAyudaJustificaciones');
                const button = document.getElementById('btnAyudaJustificaciones');

                if (!modal) {
                    console.error('❌ Modal element not found for hiding!');
                    return;
                }

                // Quitar clase show para ocultar el modal
                modal.classList.remove('show');
                document.body.style.overflow = '';
                document.body.classList.remove('modal-open');

                // Remover clase active del botón
                if (button) {
                    button.classList.remove('active');
                }

                // Remover event listeners
                document.removeEventListener('keydown', handleEscapeKey);
                modal.removeEventListener('click', handleOverlayClick);

                console.log('✅ Modal hidden successfully');

            } catch (error) {
                console.error('❌ Error hiding modal:', error);
            }
        }

        function handleEscapeKey(event) {
            if (event.key === 'Escape') {
                console.log('🎯 Escape key pressed, hiding modal');
                hideHelpModal();
            }
        }

        function handleOverlayClick(event) {
            const modal = document.getElementById('modalAyudaJustificaciones');
            if (event.target === modal) {
                console.log('🎯 Overlay clicked, hiding modal');
                hideHelpModal();
            }
        }

        // Función de respaldo por si el botón no funciona
        function toggleHelpModal() {
            const modal = document.getElementById('modalAyudaJustificaciones');
            if (modal && modal.classList.contains('show')) {
                hideHelpModal();
            } else {
                showHelpModal();
            }
        }

        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando modal de ayuda...');

            const helpButton = document.getElementById('btnAyudaJustificaciones');
            const modal = document.getElementById('modalAyudaJustificaciones');

            if (helpButton) {
                console.log('✅ Botón de ayuda encontrado');
                // Asegurar que el botón tenga el event listener correcto
                helpButton.onclick = function(e) {
                    e.preventDefault();
                    showHelpModal();
                };
            } else {
                console.warn('⚠️ Botón de ayuda no encontrado');
            }

            if (modal) {
                console.log('✅ Modal de ayuda encontrado');
                // Asegurar que el modal esté oculto inicialmente
                modal.style.display = 'none';
            } else {
                console.error('❌ Modal de ayuda no encontrado');
            }

            console.log('✅ Inicialización del modal completada');
        });

        // Función de debug para probar el modal desde la consola
        window.testHelpModal = function() {
            console.log('🧪 Testing help modal...');
            showHelpModal();
            setTimeout(() => {
                console.log('🧪 Auto-hiding modal in 3 seconds...');
                setTimeout(() => {
                    hideHelpModal();
                    console.log('🧪 Test completed');
                }, 3000);
            }, 1000);
        };

        // Función para verificar el estado del modal
        window.checkModalStatus = function() {
            const modal = document.getElementById('modalAyudaJustificaciones');
            const button = document.getElementById('btnAyudaJustificaciones');

            console.log('📊 Modal Status Check:');
            console.log('   Modal exists:', !!modal);
            console.log('   Modal display:', modal ? modal.style.display : 'N/A');
            console.log('   Button exists:', !!button);
            console.log('   Button has active class:', button ? button.classList.contains('active') : 'N/A');
            console.log('   Body has modal-open class:', document.body.classList.contains('modal-open'));
            console.log('   Body overflow:', document.body.style.overflow);
        };
    </script>

    <!-- Estilos para el modal personalizado de ayuda -->
    <style>
        /* Modal Overlay - Estilo normal */
        #modalAyudaJustificaciones {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            display: none !important;
            justify-content: center !important;
            align-items: center !important;
            z-index: 1055 !important;
        }

        /* Modal visible */
        #modalAyudaJustificaciones.show {
            display: flex !important;
        }

        /* Animaciones para el modal */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Estilos para el contenido del modal */
        .custom-modal-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            position: relative;
            border: 1px solid #dee2e6;
        }

        /* Estilos para el contenido de ayuda */
        .help-content {
            padding: 1rem;
        }

        .help-alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .help-alert-info {
            background-color: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }

        .help-alert-warning {
            background-color: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }

        .help-alert-success {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        .help-alert h6 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .help-section-title {
            color: #2c3e50;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .help-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .help-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .help-list li:last-child {
            border-bottom: none;
        }

        .help-steps {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .help-steps li {
            counter-increment: step-counter;
            padding: 0.75rem 0;
            border-left: 3px solid #007bff;
            padding-left: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            background-color: #f8f9fa;
            border-radius: 0 4px 4px 0;
        }

        .help-steps li::before {
            content: counter(step-counter);
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            background: #007bff;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .help-rules {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .help-rules li {
            padding: 0.25rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .help-rules li::before {
            content: "⚠";
            flex-shrink: 0;
        }

        .help-tips {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .help-tips li {
            padding: 0.25rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .help-tips li::before {
            content: "💡";
            flex-shrink: 0;
        }

        .help-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-item {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 6px;
            border-left: 3px solid #6c757d;
        }

        .filter-item strong {
            color: #495057;
            display: block;
            margin-bottom: 0.25rem;
        }

        .custom-modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-right: 3rem; /* Espacio para el botón de cerrar */
        }

        /* Modal Header */
        .custom-modal-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .custom-modal-close {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 1);
            color: #333;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 1rem;
            right: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .custom-modal-close:hover {
            background: white;
            border-color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .custom-modal-close:active {
            transform: scale(0.95);
        }

        /* Modal Body */
        .custom-modal-body {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Modal Footer */
        .custom-modal-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            border-radius: 0 0 12px 12px;
        }

        /* Botones personalizados */
        .custom-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .custom-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .custom-btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* Contenido de ayuda */
        .help-content {
            font-family: 'Inter', sans-serif;
        }

        .help-alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .help-alert-info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }

        .help-alert-warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }

        .help-alert h6 {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .help-section-title {
            color: #007bff;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 1.5rem 0 1rem 0;
            display: flex;
            align-items: center;
        }

        .help-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
        }

        .help-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }

        .help-steps {
            margin: 0 0 1.5rem 0;
            padding-left: 1.5rem;
        }

        .help-steps li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .help-rules {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .help-rules li {
            padding: 0.25rem 0;
            position: relative;
            padding-left: 1rem;
        }

        .help-rules li:before {
            content: "•";
            color: #ffc107;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* Animaciones */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .custom-modal-content {
                width: 95% !important;
                margin: 1rem !important;
            }

            .custom-modal-header,
            .custom-modal-body,
            .custom-modal-footer {
                padding: 1rem !important;
            }

            .custom-modal-title {
                font-size: 1.1rem !important;
            }
        }

        /* Estilos de accesibilidad */
        .custom-modal-close:focus,
        .custom-btn:focus {
            outline: 2px solid #007bff !important;
            outline-offset: 2px !important;
        }

        /* Indicador visual para el botón cuando el modal está activo */
        #btnAyudaJustificaciones.active {
            background-color: #0056b3 !important;
            border-color: #0056b3 !important;
            transform: scale(1.05) !important;
        }

        /* Prevenir scroll cuando el modal está abierto */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 15px !important; /* Compensar scrollbar */
        }
    </style>

    <script src="assets/js/justificaciones_module.js"></script>
    <script src="assets/js/justificaciones_modal.js"></script>
    <script src="assets/js/observaciones_expandibles.js"></script>

    <!-- CSS adicional para fix del dropdown -->
    <style>
        /* Fix crítico para dropdown de usuario en justificaciones */
        .header .user-dropdown {
            position: relative !important;
            z-index: 99999 !important;
        }
        
        .header .user-menu {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            z-index: 99999 !important;
            background: white !important;
            border: 1px solid #ddd !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            min-width: 250px !important;
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-10px) !important;
            transition: all 0.3s ease !important;
        }
        
        .header .user-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }
        
        /* Asegurar que no haya interferencias con el click */
        .user-dropdown .user-info {
            pointer-events: auto !important;
            cursor: pointer !important;
            z-index: 99999 !important;
        }
        
        /* Estilos para observaciones como labels fijos */
        .observacion-label-container {
            min-width: 300px;
            max-width: 400px;
        }
        
        .observacion-label {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.4rem 0.6rem;
            font-size: 0.85rem;
            line-height: 1.3;
            word-wrap: break-word;
            white-space: nowrap;
            height: 30px;
            overflow: hidden;
            display: flex;
            align-items: center;
            text-align: left;
            text-overflow: ellipsis;
        }
        
        .observacion-label .observacion-content {
            color: #495057;
            font-size: 0.85rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
        }
        
        /* Estilos para paginación */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }
        
        .pagination-info {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-pagination {
            background: white;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-pagination:hover:not(:disabled) {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .btn-pagination:disabled {
            background: #f8f9fa;
            border-color: #dee2e6;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        .btn-pagination-page {
            background: white;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            cursor: pointer;
            min-width: 40px;
        }
        
        .btn-pagination-page:hover {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .btn-pagination-page.active {
            background: #007bff;
            border-color: #007bff;
            color: white;
            font-weight: 600;
        }
        
        .pagination-ellipsis {
            padding: 0.375rem 0.25rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .pagination-size {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #495057;
        }
        
        .pagination-size select {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            background: white;
        }
    </style>

    <script>
        // Funciones para el modal personalizado
        // DEBUG: Función de test temporal
        function testModal() {
            console.log('🧪 TEST: Iniciando test del modal');
            
            // Llenar contenido de test
            document.getElementById('modalTitleText').textContent = 'Modal de Prueba';
            document.getElementById('modalBodyContent').innerHTML = `
                <div style="padding: 1rem;">
                    <h4 style="color: #28a745; margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i>
                        ✅ Modal Funcionando Correctamente
                    </h4>
                    <p>Este es un modal personalizado que funciona sin Bootstrap.</p>
                    <p><strong>Características:</strong></p>
                    <ul>
                        <li>Animaciones suaves</li>
                        <li>Backdrop blur</li>
                        <li>Responsive design</li>
                        <li>Cierre con Escape</li>
                    </ul>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                        <strong>Debug Info:</strong><br>
                        Tiempo: ${new Date().toLocaleTimeString()}<br>
                        Modal ID: modalDetallesJustificacion<br>
                        Bootstrap CSS: Cargado pero overridden
                    </div>
                </div>
            `;
            
            openCustomModal();
        }
        
        // DEBUG: Función de fuerza bruta
        function forceShowModal() {
            console.log('🔨 FORCE: Mostrando modal con fuerza bruta');
            
            const modal = document.getElementById('modalDetallesJustificacion');
            if (modal) {
                // Crear HTML completamente nuevo en el modal
                modal.innerHTML = `
                    <div style="
                        background: white;
                        width: 600px;
                        height: 400px;
                        margin: auto;
                        border-radius: 10px;
                        border: 3px solid red;
                        padding: 20px;
                        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                        position: relative;
                        display: block;
                    ">
                        <h2 style="color: red; margin: 0 0 20px 0;">🔨 MODAL FORZADO</h2>
                        <p>Este modal se creó con fuerza bruta para verificar que la funcionalidad básica funcione.</p>
                        <button onclick="document.getElementById('modalDetallesJustificacion').style.display='none'" 
                                style="background: red; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                            CERRAR
                        </button>
                        <div style="margin-top: 20px; background: #f0f0f0; padding: 15px; border-radius: 5px;">
                            <strong>Test Info:</strong><br>
                            Si ves este modal, significa que el overlay funciona.<br>
                            El problema está en los estilos CSS del contenido original.
                        </div>
                    </div>
                `;
                
                // Mostrar con estilos inline absolutos
                modal.style.cssText = `
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    background: rgba(0, 0, 0, 0.8) !important;
                    z-index: 99999 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                `;
                
                console.log('🔨 Modal forzado mostrado');
            }
        }
        
        function openCustomModal() {
            console.log('🎭 openCustomModal llamado');
            const modalOverlay = document.getElementById('modalDetallesJustificacion');
            console.log('🎯 Modal element:', modalOverlay);
            
            if (modalOverlay) {
                console.log('✅ Modal encontrado, aplicando estilos...');
                
                // Debug: Estado inicial
                console.log('📊 Estado inicial - Display:', window.getComputedStyle(modalOverlay).display);
                console.log('📊 Estado inicial - Visibility:', window.getComputedStyle(modalOverlay).visibility);
                console.log('📊 Estado inicial - Opacity:', window.getComputedStyle(modalOverlay).opacity);
                console.log('📊 Estado inicial - Z-index:', window.getComputedStyle(modalOverlay).zIndex);
                
                // Forzar estilos directamente con cssText
                modalOverlay.style.cssText = `
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    background: rgba(0, 0, 0, 0.5) !important;
                    z-index: 99999 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                `;
                
                console.log('📊 Después de cssText - Display:', window.getComputedStyle(modalOverlay).display);
                console.log('📊 Después de cssText - Visibility:', window.getComputedStyle(modalOverlay).visibility);
                console.log('📊 Después de cssText - Opacity:', window.getComputedStyle(modalOverlay).opacity);
                
                // Agregar clase show
                modalOverlay.classList.add('show');
                
                // Prevenir scroll del body
                document.body.style.overflow = 'hidden';
                
                console.log('🎭 Modal debería estar visible ahora');
                console.log('📊 Modal classes:', modalOverlay.className);
                console.log('📊 Modal display:', modalOverlay.style.display);
                
                // Test: Revisar si hay elementos encima
                const rect = modalOverlay.getBoundingClientRect();
                const elementAtPoint = document.elementFromPoint(window.innerWidth/2, window.innerHeight/2);
                console.log('🎯 Elemento en el centro de la pantalla:', elementAtPoint);
                console.log('📐 BoundingRect del modal:', rect);
                
                // Debug adicional: revisar el contenido del modal
                const modalContent = modalOverlay.querySelector('.custom-modal');
                if (modalContent) {
                    const contentRect = modalContent.getBoundingClientRect();
                    console.log('📦 Modal content rect:', contentRect);
                    console.log('📦 Modal content display:', window.getComputedStyle(modalContent).display);
                    console.log('📦 Modal content width:', window.getComputedStyle(modalContent).width);
                    console.log('📦 Modal content height:', window.getComputedStyle(modalContent).height);
                    
                    // Forzar estilos del contenido también
                    modalContent.style.cssText += `
                        background: white !important;
                        width: 600px !important;
                        height: 400px !important;
                        display: block !important;
                        position: relative !important;
                        border-radius: 8px !important;
                        border: 2px solid red !important;
                    `;
                    console.log('📦 Estilos forzados en modal content');
                } else {
                    console.log('❌ No se encontró .custom-modal dentro del overlay');
                }
                
            } else {
                console.error('❌ Modal element no encontrado');
            }
        }
        
        function closeCustomModal() {
            console.log('🎭 closeCustomModal llamado');
            const modalOverlay = document.getElementById('modalDetallesJustificacion');
            if (modalOverlay) {
                console.log('✅ Cerrando modal...');
                modalOverlay.classList.remove('show');
                
                // Esperar a que termine la animación antes de ocultar
                setTimeout(() => {
                    modalOverlay.style.display = 'none';
                    // Restaurar scroll del body
                    document.body.style.overflow = '';
                    console.log('🎭 Modal cerrado completamente');
                }, 300);
            }
        }
        
        function showModalLoading() {
            const modalBody = document.getElementById('modalBodyContent');
            const modalTitle = document.getElementById('modalTitleText');
            
            if (modalTitle) {
                modalTitle.innerHTML = 'Cargando detalles...';
            }
            
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="loading-content">
                        <div class="loading-spinner"></div>
                        <p>Cargando detalles de la justificación...</p>
                    </div>
                `;
            }
        }
        
        // Cerrar modal con Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modalOverlay = document.getElementById('modalDetallesJustificacion');
                if (modalOverlay && modalOverlay.classList.contains('show')) {
                    closeCustomModal();
                }
            }
        });
        
        // Cerrar modal al hacer click en el overlay
        document.addEventListener('click', function(event) {
            const modalOverlay = document.getElementById('modalDetallesJustificacion');
            if (event.target === modalOverlay) {
                closeCustomModal();
            }
        });
        
        // Inicializar módulo cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Iniciando módulo de justificaciones...');
            window.justificacionesModule = new JustificacionesModule();
        });
    </script>

    <!-- Script de prueba para modal dinámico -->
    <script src="test_dynamic_modal.js"></script>

    </script>
</body>
</html>