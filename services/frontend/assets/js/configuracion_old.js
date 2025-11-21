/**
 * Configuración Module JavaScript
 * Maneja toda la funcionalidad del módulo de configuración del sistema
 */

const configuracion = {
    // Estado actual del módulo
    currentTab: 'sedes',
    data: {
        sedes: [],
        establecimientos: []
    },
    
    // Inicialización del módulo
    init: function() {
        console.log('Inicializando módulo de configuración...');
        this.bindEvents();
        this.loadInitialData();
    },
    
    // Vincular eventos de la interfaz
    bindEvents: function() {
        // Eventos de las pestañas
        document.querySelectorAll('#configTabs button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const targetTab = e.target.getAttribute('data-bs-target').replace('#', '');
                this.currentTab = targetTab;
                this.loadTabContent(targetTab);
            });
        });
        
        // Recargar página
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    },
    
    // Cargar datos iniciales
    loadInitialData: function() {
        this.loadTabContent('sedes');
    },
    
    // Cargar contenido de una pestaña específica
    loadTabContent: function(tabName) {
        console.log(`Cargando contenido de la pestaña: ${tabName}`);
        
        switch(tabName) {
            case 'sedes':
                this.loadSedesContent();
                break;
            case 'establecimientos':
                this.loadEstablecimientosContent();
                break;
            case 'password':
                this.loadPasswordContent();
                break;
            default:
                console.warn(`Pestaña no reconocida: ${tabName}`);
        }
    },
    
    // Cargar contenido de Sedes
    loadSedesContent: function() {
        const container = document.getElementById('sedesContent');
        
        // Mostrar loading
        container.innerHTML = `
            <div class="loading-placeholder">
                <i class="fas fa-spinner fa-spin"></i> Cargando sedes...
            </div>
        `;
        
        // Simular carga de datos (reemplazar con llamada real a API)
        setTimeout(() => {
            container.innerHTML = `
                <div class="action-buttons">
                    <button type="button" class="btn btn-primary btn-action" onclick="configuracion.openSedeModal()">
                        <i class="fas fa-plus"></i> Nueva Sede
                    </button>
                </div>
                
                <div class="data-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Dirección</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sedesTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <i class="fas fa-database"></i> No hay sedes registradas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            // Cargar datos reales de sedes
            this.loadSedesData();
        }, 500);
    },
    
    // Cargar contenido de Establecimientos
    loadEstablecimientosContent: function() {
        const container = document.getElementById('establecimientosContent');
        
        container.innerHTML = `
            <div class="loading-placeholder">
                <i class="fas fa-spinner fa-spin"></i> Cargando establecimientos...
            </div>
        `;
        
        setTimeout(() => {
            container.innerHTML = `
                <div class="action-buttons">
                    <button type="button" class="btn btn-success btn-action" onclick="configuracion.openEstablecimientoModal()">
                        <i class="fas fa-plus"></i> Nuevo Establecimiento
                    </button>
                </div>
                
                <div class="data-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Sede</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="establecimientosTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <i class="fas fa-database"></i> No hay establecimientos registrados
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            this.loadEstablecimientosData();
        }, 500);
    },
    
    // Cargar contenido de Contraseña
    loadPasswordContent: function() {
        const container = document.getElementById('passwordContent');
        
        container.innerHTML = `
            <div class="config-form">
                <form id="passwordForm" onsubmit="configuracion.changePassword(event)">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currentPassword" class="form-label">
                                    <i class="fas fa-lock text-muted"></i> Contraseña Actual
                                </label>
                                <input type="password" class="form-control" id="currentPassword" 
                                       name="currentPassword" required 
                                       placeholder="Ingresa tu contraseña actual">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="newPassword" class="form-label">
                                    <i class="fas fa-key text-primary"></i> Nueva Contraseña
                                </label>
                                <input type="password" class="form-control" id="newPassword" 
                                       name="newPassword" required 
                                       placeholder="Ingresa la nueva contraseña"
                                       minlength="6">
                                <div class="invalid-feedback"></div>
                                <small class="form-text text-muted">
                                    La contraseña debe tener al menos 6 caracteres
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirmPassword" class="form-label">
                                    <i class="fas fa-check-circle text-success"></i> Confirmar Nueva Contraseña
                                </label>
                                <input type="password" class="form-control" id="confirmPassword" 
                                       name="confirmPassword" required 
                                       placeholder="Confirma la nueva contraseña">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-warning btn-action">
                            <i class="fas fa-save"></i> Cambiar Contraseña
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-action" 
                                onclick="configuracion.resetPasswordForm()">
                            <i class="fas fa-undo"></i> Restablecer
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        // Configurar validación de contraseñas
        this.setupPasswordValidation();
    },
    
    // Cargar datos de sedes desde API
    loadSedesData: function() {
        fetch('./api/configuracion/sedes.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.data.sedes = data.sedes;
                this.renderSedesTable();
            } else {
                this.showAlert('danger', 'Error al cargar sedes: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error cargando sedes:', error);
            this.showAlert('danger', 'Error de conexión al cargar sedes');
        });
    },
    
    // Cargar datos de establecimientos desde API
    loadEstablecimientosData: function() {
        fetch('./api/configuracion/establecimientos.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.data.establecimientos = data.establecimientos;
                this.renderEstablecimientosTable();
            } else {
                this.showAlert('danger', 'Error al cargar establecimientos: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error cargando establecimientos:', error);
            this.showAlert('danger', 'Error de conexión al cargar establecimientos');
        });
    },
    
    // Renderizar tabla de sedes
    renderSedesTable: function() {
        const tbody = document.getElementById('sedesTableBody');
        
        if (this.data.sedes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="fas fa-database"></i> No hay sedes registradas
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.data.sedes.map(sede => `
            <tr>
                <td><strong>${sede.id}</strong></td>
                <td>${sede.nombre}</td>
                <td>${sede.direccion || 'No especificada'}</td>
                <td>
                    <span class="status-badge ${sede.estado === 'ACTIVO' ? 'status-active' : 'status-inactive'}">
                        ${sede.estado}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="configuracion.editSede(${sede.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="configuracion.deleteSede(${sede.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },
    
    // Renderizar tabla de establecimientos
    renderEstablecimientosTable: function() {
        const tbody = document.getElementById('establecimientosTableBody');
        
        if (this.data.establecimientos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="fas fa-database"></i> No hay establecimientos registrados
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.data.establecimientos.map(est => `
            <tr>
                <td><strong>${est.id}</strong></td>
                <td>${est.nombre}</td>
                <td>${est.sede_nombre || 'Sin sede'}</td>
                <td>
                    <span class="status-badge ${est.estado === 'ACTIVO' ? 'status-active' : 'status-inactive'}">
                        ${est.estado}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="configuracion.editEstablecimiento(${est.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="configuracion.deleteEstablecimiento(${est.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },
    
    // Configurar validación de contraseñas
    setupPasswordValidation: function() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        
        confirmPassword.addEventListener('input', () => {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                confirmPassword.nextElementSibling.textContent = 'Las contraseñas no coinciden';
            } else {
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
                confirmPassword.nextElementSibling.textContent = '';
            }
        });
    },
    
    // Cambiar contraseña
    changePassword: function(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Validar que las contraseñas coincidan
        const newPassword = formData.get('newPassword');
        const confirmPassword = formData.get('confirmPassword');
        
        if (newPassword !== confirmPassword) {
            this.showAlert('danger', 'Las contraseñas no coinciden');
            return;
        }
        
        this.showLoading(true);
        
        fetch('./api/configuracion/change-password.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            this.showLoading(false);
            
            if (data.success) {
                this.showAlert('success', 'Contraseña cambiada exitosamente');
                form.reset();
            } else {
                this.showAlert('danger', 'Error: ' + data.message);
            }
        })
        .catch(error => {
            this.showLoading(false);
            console.error('Error cambiando contraseña:', error);
            this.showAlert('danger', 'Error de conexión');
        });
    },
    
    // Restablecer formulario de contraseña
    resetPasswordForm: function() {
        const form = document.getElementById('passwordForm');
        form.reset();
        
        // Limpiar clases de validación
        form.querySelectorAll('.form-control').forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });
    },
    
    // Abrir modal de sede
    openSedeModal: function(sedeId = null) {
        console.log('Abriendo modal de sede:', sedeId);
        // TODO: Implementar modal de sede
    },
    
    // Abrir modal de establecimiento
    openEstablecimientoModal: function(establecimientoId = null) {
        console.log('Abriendo modal de establecimiento:', establecimientoId);
        // TODO: Implementar modal de establecimiento
    },
    
    // Editar sede
    editSede: function(sedeId) {
        this.openSedeModal(sedeId);
    },
    
    // Eliminar sede
    deleteSede: function(sedeId) {
        if (confirm('¿Estás seguro de que deseas eliminar esta sede?')) {
            console.log('Eliminando sede:', sedeId);
            // TODO: Implementar eliminación de sede
        }
    },
    
    // Editar establecimiento
    editEstablecimiento: function(establecimientoId) {
        this.openEstablecimientoModal(establecimientoId);
    },
    
    // Eliminar establecimiento
    deleteEstablecimiento: function(establecimientoId) {
        if (confirm('¿Estás seguro de que deseas eliminar este establecimiento?')) {
            console.log('Eliminando establecimiento:', establecimientoId);
            // TODO: Implementar eliminación de establecimiento
        }
    },
    
    // Mostrar/ocultar loading overlay
    showLoading: function(show) {
        const overlay = document.getElementById('loadingOverlay');
        overlay.style.display = show ? 'flex' : 'none';
    },
    
    // Mostrar alertas
    showAlert: function(type, message) {
        // Crear alerta
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insertar en el contenido actual
        const activeTab = document.querySelector('.tab-pane.active');
        if (activeTab) {
            activeTab.insertBefore(alert, activeTab.firstChild);
        }
        
        // Auto-dismiss después de 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    },
    
    // Limpiar recursos
    cleanup: function() {
        console.log('Limpiando recursos del módulo de configuración...');
    }
};

// Exportar para uso global
window.configuracion = configuracion;