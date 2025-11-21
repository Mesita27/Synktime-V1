<?php
require_once 'auth/session.php';

// Verificar autenticación
if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Verificar permisos (GERENTE y ADMIN pueden acceder a configuración)
$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['rol'], ['GERENTE', 'ADMIN'])) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

require_once 'config/database.php';

$pageTitle = "Configuración del Sistema";
$currentPage = "configuracion";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SynkTime</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/configuracion.css">
</head>
<body>
    <div class="app-container">
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Top Navigation -->
        <?php include 'components/header.php'; ?>

        <!-- Content Area -->
        <div class="content-wrapper">
            <main class="main-content">
                <div class="container-fluid">
                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="page-header">
                            <div class="page-header-content">
                                <div class="page-title">
                                    <h1><i class="fas fa-cogs"></i> Configuración del Sistema</h1>
                                    <p class="page-subtitle">Gestión y configuración de parámetros del sistema</p>
                                </div>
                                <div class="page-actions">
                                    <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#modalAyudaConfiguracion">
                                        <i class="fas fa-question-circle"></i> Ayuda
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                        <i class="fas fa-sync-alt"></i> Actualizar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Tabs -->
                <div class="row">
                    <div class="col-12">
                        <div class="card config-card">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs config-tabs" id="configTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="sedes-tab" data-bs-toggle="tab" 
                                                data-bs-target="#sedes" type="button" role="tab">
                                            <i class="fas fa-building"></i>
                                            <span>Sedes</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="establecimientos-tab" data-bs-toggle="tab" 
                                                data-bs-target="#establecimientos" type="button" role="tab">
                                            <i class="fas fa-briefcase"></i>
                                            <span>Establecimientos</span>
                                        </button>
                                    </li>
                                    <?php if ($currentUser['rol'] === 'ADMIN'): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" 
                                                data-bs-target="#usuarios" type="button" role="tab">
                                            <i class="fas fa-users"></i>
                                            <span>Usuarios</span>
                                        </button>
                                    </li>
                                    <?php endif; ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" 
                                                data-bs-target="#password" type="button" role="tab">
                                            <i class="fas fa-key"></i>
                                            <span>Contraseña</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="configTabContent">
                                    <!-- Sedes Tab -->
                                    <div class="tab-pane fade show active" id="sedes" role="tabpanel">
                                        <div class="tab-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4><i class="fas fa-building text-primary"></i> Gestión de Sedes</h4>
                                                <p class="text-muted mb-0">Administra las sedes de tu empresa</p>
                                            </div>
                                            <button type="button" class="btn btn-primary" onclick="configuracion.openSedeModal()">
                                                <i class="fas fa-plus me-1"></i>
                                                Nueva Sede
                                            </button>
                                        </div>
                                        <div id="sedesContent">
                                            <div class="loading-placeholder">
                                                <i class="fas fa-spinner fa-spin"></i> Cargando sedes...
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Establecimientos Tab -->
                                    <div class="tab-pane fade" id="establecimientos" role="tabpanel">
                                        <div class="tab-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4><i class="fas fa-briefcase text-success"></i> Gestión de Establecimientos</h4>
                                                <p class="text-muted mb-0">Administra los establecimientos y cargos</p>
                                            </div>
                                            <button type="button" class="btn btn-success" onclick="configuracion.openEstablecimientoModal()">
                                                <i class="fas fa-plus me-1"></i>
                                                Nuevo Establecimiento
                                            </button>
                                        </div>
                                        <div id="establecimientosContent">
                                            <div class="loading-placeholder">
                                                <i class="fas fa-spinner fa-spin"></i> Cargando establecimientos...
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Usuarios Tab (Solo ADMIN) -->
                                    <?php if ($currentUser['rol'] === 'ADMIN'): ?>
                                    <div class="tab-pane fade" id="usuarios" role="tabpanel">
                                        <div class="tab-header">
                                            <div>
                                                <h4><i class="fas fa-users text-primary"></i> Gestión de Usuarios</h4>
                                                <p class="text-muted mb-0">Administra los usuarios de tu empresa (máximo 15 usuarios)</p>
                                            </div>
                                            <button type="button" class="btn btn-primary" onclick="configuracion.openUsuarioModal()">
                                                <i class="fas fa-plus me-1"></i>
                                                Nuevo Usuario
                                            </button>
                                        </div>
                                        <div id="usuariosContent">
                                            <div class="loading-placeholder">
                                                <i class="fas fa-spinner fa-spin"></i> Cargando usuarios...
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Password Tab -->
                                    <div class="tab-pane fade" id="password" role="tabpanel">
                                        <div class="tab-header">
                                            <h4><i class="fas fa-key text-warning"></i> Cambio de Contraseña</h4>
                                            <p class="text-muted">Modifica tu contraseña de acceso</p>
                                        </div>
                                        <div id="passwordContent">
                                            <div class="loading-placeholder">
                                                <i class="fas fa-spinner fa-spin"></i> Cargando formulario...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-circle-notch fa-spin fa-3x"></i>
            <p>Procesando...</p>
        </div>
    </div>

    <!-- Modal para Sedes -->
    <div class="modal fade" id="sedeModal" tabindex="-1" aria-labelledby="sedeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sedeModalLabel">
                        <i class="fas fa-building me-2"></i>
                        <span id="sedeModalTitle">Agregar Sede</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="sedeForm">
                        <input type="hidden" id="sedeId" name="id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sedeEmpresa" class="form-label">
                                        <i class="fas fa-building me-1"></i>
                                        Empresa <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="sedeEmpresa" name="id_empresa" required>
                                        <option value="">Seleccione una empresa...</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sedeNombre" class="form-label">
                                        <i class="fas fa-tag me-1"></i>
                                        Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="sedeNombre" name="nombre" 
                                           placeholder="Nombre de la sede" required maxlength="100">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="sedeDireccion" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Dirección
                                    </label>
                                    <textarea class="form-control" id="sedeDireccion" name="direccion" 
                                              placeholder="Dirección completa de la sede" rows="2" maxlength="200"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="saveSedeBtn">
                        <i class="fas fa-save me-1"></i>
                        <span id="saveSedeText">Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Establecimientos -->
    <div class="modal fade" id="establecimientoModal" tabindex="-1" aria-labelledby="establecimientoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="establecimientoModalLabel">
                        <i class="fas fa-briefcase me-2"></i>
                        <span id="establecimientoModalTitle">Agregar Establecimiento</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="establecimientoForm">
                        <input type="hidden" id="establecimientoId" name="id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="establecimientoSede" class="form-label">
                                        <i class="fas fa-building me-1"></i>
                                        Sede <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="establecimientoSede" name="id_sede" required>
                                        <option value="">Seleccione una sede...</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="establecimientoNombre" class="form-label">
                                        <i class="fas fa-tag me-1"></i>
                                        Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="establecimientoNombre" name="nombre" 
                                           placeholder="Nombre del establecimiento" required maxlength="100">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="establecimientoDireccion" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Dirección
                                    </label>
                                    <textarea class="form-control" id="establecimientoDireccion" name="direccion" 
                                              placeholder="Dirección específica del establecimiento" rows="2" maxlength="200"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="saveEstablecimientoBtn">
                        <i class="fas fa-save me-1"></i>
                        <span id="saveEstablecimientoText">Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">¿Está seguro?</h4>
                        <p class="text-muted" id="deleteMessage">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Usuarios (Solo ADMIN) -->
    <?php if ($currentUser['rol'] === 'ADMIN'): ?>
    <div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="usuarioModalLabel">
                        <i class="fas fa-user-plus text-primary me-2"></i>
                        <span id="usuarioModalTitle">Agregar Usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="usuarioForm">
                        <input type="hidden" id="usuarioId" name="id">
                        
                        <!-- Información Personal -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuarioNombre" class="form-label">
                                        <i class="fas fa-user text-muted me-2"></i>
                                        Nombre Completo *
                                    </label>
                                    <input type="text" class="form-control" id="usuarioNombre" name="nombre" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuarioEmail" class="form-label">
                                        <i class="fas fa-envelope text-muted me-2"></i>
                                        Email *
                                    </label>
                                    <input type="email" class="form-control" id="usuarioEmail" name="email" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Acceso -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuarioUsername" class="form-label">
                                        <i class="fas fa-at text-muted me-2"></i>
                                        Nombre de Usuario *
                                    </label>
                                    <input type="text" class="form-control" id="usuarioUsername" name="username" required>
                                    <div class="form-text">
                                        <small class="text-muted">Solo letras, números y guiones bajos</small>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuarioRol" class="form-label">
                                        <i class="fas fa-shield-alt text-muted me-2"></i>
                                        Rol *
                                    </label>
                                    <select class="form-select" id="usuarioRol" name="rol" required>
                                        <option value="">Seleccione un rol...</option>
                                        <option value="GERENTE">Gerente</option>
                                        <option value="ASISTENCIA">Asistencia</option>
                                    </select>
                                    <div class="form-text">
                                        <small class="text-muted">
                                            <strong>Gerente:</strong> Acceso completo a configuración<br>
                                            <strong>Asistencia:</strong> Solo gestión de asistencias
                                        </small>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Contraseña -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuarioPassword" class="form-label">
                                        <i class="fas fa-key text-muted me-2"></i>
                                        Contraseña <span id="passwordRequired">*</span>
                                    </label>
                                    <input type="password" class="form-control" id="usuarioPassword" name="contrasena">
                                    <div class="form-text">
                                        <small class="text-muted" id="passwordHelp">Mínimo 6 caracteres</small>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuarioPasswordConfirm" class="form-label">
                                        <i class="fas fa-check-double text-muted me-2"></i>
                                        Confirmar Contraseña <span id="passwordConfirmRequired">*</span>
                                    </label>
                                    <input type="password" class="form-control" id="usuarioPasswordConfirm" name="contrasena_confirm">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuarioEstado" class="form-label">
                                        <i class="fas fa-toggle-on text-muted me-2"></i>
                                        Estado
                                    </label>
                                    <select class="form-select" id="usuarioEstado" name="estado">
                                        <option value="ACTIVO">Activo</option>
                                        <option value="INACTIVO">Inactivo</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Empresa (Solo lectura) -->
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Empresa:</strong> <span id="empresaUsuarioInfo"><?= htmlspecialchars($currentUser['empresa_nombre'] ?? 'Empresa actual') ?></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="saveUsuarioBtn">
                        <i class="fas fa-save me-1"></i>
                        <span id="saveUsuarioText">Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal de Ayuda para Configuración del Sistema -->
    <div class="modal fade" id="modalAyudaConfiguracion" tabindex="-1" aria-labelledby="modalAyudaConfiguracionLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #2563eb, #6366f1); color: #fff;">
                    <h5 class="modal-title" id="modalAyudaConfiguracionLabel">
                        <i class="fas fa-cogs me-2"></i>
                        Guía de uso - Configuración del Sistema
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" style="background: #f8fafc;">
                    <div class="mb-4 p-3 rounded" style="background: #eef2ff; border: 1px solid #c7d2fe;">
                        <h6 class="mb-2" style="color: #4338ca;"><i class="fas fa-lightbulb me-2"></i>Resumen rápido</h6>
                        <p class="mb-0" style="color: #4b5563;">Desde aquí administras la estructura base del sistema: sedes, establecimientos, usuarios autorizados y la contraseña de tu cuenta. Los cambios quedan activos de inmediato y afectan a todo SynkTime.</p>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="h-100 p-3 border rounded bg-white shadow-sm">
                                <h6 class="text-primary mb-2"><i class="fas fa-building me-2"></i>Sedes</h6>
                                <p class="small text-muted mb-0">Registra y edita las sedes físicas de la empresa. Cada establecimiento debe asociarse a una sede activa.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="h-100 p-3 border rounded bg-white shadow-sm">
                                <h6 class="text-success mb-2"><i class="fas fa-briefcase me-2"></i>Establecimientos</h6>
                                <p class="small text-muted mb-0">Define las áreas o centros de trabajo dentro de cada sede y vincula cargos o puestos específicos.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="h-100 p-3 border rounded bg-white shadow-sm">
                                <h6 class="text-info mb-2"><i class="fas fa-users me-2"></i>Usuarios (solo ADMIN)</h6>
                                <p class="small text-muted mb-0">Crea o gestiona cuentas que pueden acceder al sistema. Máximo 15 usuarios por empresa.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="h-100 p-3 border rounded bg-white shadow-sm">
                                <h6 class="text-warning mb-2"><i class="fas fa-key me-2"></i>Contraseña personal</h6>
                                <p class="small text-muted mb-0">Actualiza tu contraseña de acceso en cualquier momento. Se requiere la contraseña actual para confirmar.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="text-primary mb-3"><i class="fas fa-route me-2"></i>Flujo recomendado</h6>
                            <ol class="mb-0" style="color: #4b5563;">
                                <li class="mb-2">Revisa la pestaña <strong>Sedes</strong> y asegura que todas las ubicaciones existan y estén activas.</li>
                                <li class="mb-2">Continúa con <strong>Establecimientos</strong> para crear las áreas operativas ligadas a cada sede.</li>
                                <li class="mb-2">Si eres ADMIN, crea o ajusta los <strong>Usuarios</strong> que administrarán asistencia, configuraciones y reportes.</li>
                                <li>Ingresa a <strong>Contraseña</strong> para mantener tus credenciales seguras y renovar contraseñas periódicamente.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="text-secondary mb-3"><i class="fas fa-tools me-2"></i>Acciones disponibles por pestaña</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 160px;">Sección</th>
                                            <th style="min-width: 220px;">Acciones principales</th>
                                            <th>Detalles y consejos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-primary"><i class="fas fa-building me-2"></i>Sedes</td>
                                            <td>
                                                <ul class="mb-0 ps-3 small">
                                                    <li>Agregar nueva sede</li>
                                                    <li>Editar datos existentes</li>
                                                    <li>Selección múltiple y eliminación</li>
                                                </ul>
                                            </td>
                                            <td class="small text-muted">Completa empresa, nombre y dirección. Las sedes se usan como filtro en demás módulos.</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-success"><i class="fas fa-briefcase me-2"></i>Establecimientos</td>
                                            <td>
                                                <ul class="mb-0 ps-3 small">
                                                    <li>Crear establecimientos por sede</li>
                                                    <li>Gestionar cargos asociados</li>
                                                    <li>Eliminación con confirmación</li>
                                                </ul>
                                            </td>
                                            <td class="small text-muted">Selecciona primero la sede. Incluye una descripción clara para facilitar los reportes de asistencia.</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-info"><i class="fas fa-users me-2"></i>Usuarios</td>
                                            <td>
                                                <ul class="mb-0 ps-3 small">
                                                    <li>Registrar cuentas nuevas (Gerente o Asistencia)</li>
                                                    <li>Activar / desactivar usuarios</li>
                                                    <li>Restablecer credenciales</li>
                                                </ul>
                                            </td>
                                            <td class="small text-muted">Disponible solo para rol ADMIN. Valida correo y asigna roles según responsabilidades de cada colaborador.</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-warning"><i class="fas fa-key me-2"></i>Contraseña</td>
                                            <td>
                                                <ul class="mb-0 ps-3 small">
                                                    <li>Cambiar contraseña actual</li>
                                                    <li>Validaciones de seguridad inmediatas</li>
                                                </ul>
                                            </td>
                                            <td class="small text-muted">Define contraseñas de mínimo 6 caracteres y mezcla letras, números y símbolos. El cambio aplica en el siguiente inicio de sesión.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="h-100 p-4 border rounded bg-white shadow-sm">
                                <h6 class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Buenas prácticas</h6>
                                <ul class="mb-0" style="color: #4b5563;">
                                    <li class="mb-2">Centraliza todas las sedes y establecimientos antes de cargar horarios o asistencia.</li>
                                    <li class="mb-2">Usa nombres claros y evita duplicados; el buscador interno utiliza coincidencia exacta.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="h-100 p-4 border rounded bg-white shadow-sm">
                                <h6 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Consideraciones importantes</h6>
                                <ul class="mb-0" style="color: #4b5563;">
                                    <li class="mb-2">Al eliminar sedes o establecimientos se afectan asignaciones dependientes (horarios, empleados, reportes).</li>
                                    <li class="mb-2">Los cambios de usuarios son inmediatos; si desactivas una cuenta el usuario no podrá iniciar sesión.</li>
                                    <li class="mb-2">La contraseña personal solo la puede cambiar el dueño de la cuenta; el sistema no guarda contraseñas anteriores.</li>
                                    <li>Exporta configuraciones periódicamente si necesitas respaldo externo (opción disponible en reportes).</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex align-items-center me-auto text-muted small">
                        <i class="fas fa-life-ring me-2"></i>
                        ¿Necesitas más ayuda? Escríbenos al 304 2844477
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/configuracion.js"></script>
    
    <script>
        // Inicializar configuración cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Configuracion.php - DOM Loaded, verificando elementos...');
            
            // IMPLEMENTACIÓN DIRECTA DEL SIDEBAR TOGGLE
            const sidebar = document.getElementById('sidebar');
            const mainWrapper = document.querySelector('.main-wrapper');
            const toggleBtn = document.getElementById('toggleSidebar');
            
            console.log('Elementos sidebar:', {
                sidebar: !!sidebar,
                mainWrapper: !!mainWrapper,
                toggleBtn: !!toggleBtn
            });
            
            if (sidebar && mainWrapper && toggleBtn) {
                // Remover cualquier listener existente clonando el botón
                const newToggleBtn = toggleBtn.cloneNode(true);
                toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
                
                // Agregar el listener correcto
                newToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Sidebar toggle clicked - window width:', window.innerWidth);
                    
                    if (window.innerWidth <= 1024) {
                        // Modo móvil
                        sidebar.classList.toggle('mobile-active');
                        console.log('Mobile mode - active:', sidebar.classList.contains('mobile-active'));
                    } else {
                        // Modo desktop
                        sidebar.classList.toggle('collapsed');
                        mainWrapper.classList.toggle('sidebar-collapsed');
                        
                        const isCollapsed = sidebar.classList.contains('collapsed');
                        localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
                        console.log('Desktop mode - collapsed:', isCollapsed);
                    }
                });
                
                // Restaurar estado del sidebar en desktop
                if (window.innerWidth > 1024) {
                    const sidebarState = localStorage.getItem('sidebarState');
                    if (sidebarState === 'collapsed') {
                        sidebar.classList.add('collapsed');
                        mainWrapper.classList.add('sidebar-collapsed');
                        console.log('Restored collapsed state from localStorage');
                    }
                }
                
                // Manejar resize de ventana
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 1024) {
                        sidebar.classList.remove('mobile-active');
                    } else {
                        sidebar.classList.remove('collapsed');
                        mainWrapper.classList.remove('sidebar-collapsed');
                    }
                });
                
                console.log('✅ Sidebar toggle inicializado correctamente');
            } else {
                console.error('❌ No se puede inicializar sidebar toggle - elementos faltantes');
            }
            
            // NO TOCAR EL DROPDOWN - Lo maneja completamente header.php
            console.log('Configuracion.php - Dropdown delegado a header.php');
            
            // Inicializar configuración
            if (typeof configuracion !== 'undefined') {
                configuracion.init();
                console.log('✅ Configuración inicializada');
            }
        });
    </script>
</body>
</html>
