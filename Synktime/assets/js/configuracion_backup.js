/**
 * Configuración Module JavaScript
 * Maneja toda la funcionalidad del módulo de configuración del sistema
 */

// Sistema de Modal de Confirmación Unificado
const UnifiedDeleteModal = {
    modal: null,
    currentCallback: null,
    isMultiple: false,

    init: function() {
        this.modal = document.getElementById('confirmDeleteModal');
        this.bindEvents();
    },

    bindEvents: function() {
        // Evento para el input de confirmación múltiple
        const confirmInput = document.getElementById('multipleDeleteConfirmText');
        if (confirmInput) {
            confirmInput.addEventListener('input', (e) => {
                const btn = document.getElementById('confirmDeleteBtn');
                if (this.isMultiple) {
                    btn.disabled = e.target.value.toUpperCase() !== 'ELIMINAR';
                }
            });
        }

        // Evento del botón de confirmación
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (this.currentCallback) {
                    this.currentCallback();
                }
            });
        }

        // Limpiar estado al cerrar modal
        if (this.modal) {
            this.modal.addEventListener('hidden.bs.modal', () => {
                this.resetModal();
            });
        }
    },

    showSingleDelete: function(title, message, callback) {
        this.isMultiple = false;
        this.currentCallback = callback;
        
        // Configurar elementos para eliminación individual
        document.getElementById('deleteModalTitle').textContent = title || 'Confirmar Eliminación';
        document.getElementById('deleteModalQuestion').textContent = '¿Estás seguro?';
        document.getElementById('confirmDeleteMessage').textContent = message || 'Esta acción no se puede deshacer.';
        document.getElementById('deleteButtonText').textContent = 'Sí, Eliminar';
        
        // Mostrar/ocultar elementos apropiados
        document.getElementById('singleDeleteWarning').style.display = 'block';
        document.getElementById('multipleDeleteWarning').style.display = 'none';
        document.getElementById('multipleDeleteConfirmation').style.display = 'none';
        
        // Habilitar botón
        document.getElementById('confirmDeleteBtn').disabled = false;
        
        // Mostrar modal
        const bootstrapModal = new bootstrap.Modal(this.modal);
        bootstrapModal.show();
    },

    showMultipleDelete: function(count, type, callback) {
        this.isMultiple = true;
        this.currentCallback = callback;
        
        // Configurar elementos para eliminación múltiple
        document.getElementById('deleteModalTitle').textContent = 'Confirmar Eliminación Múltiple';
        document.getElementById('deleteModalQuestion').textContent = '¿Eliminar elementos seleccionados?';
        document.getElementById('confirmDeleteMessage').innerHTML = `Vas a eliminar <strong>${count}</strong> ${type}(s).`;
        document.getElementById('deleteButtonText').textContent = 'Eliminar Todo';
        
        // Mostrar/ocultar elementos apropiados
        document.getElementById('singleDeleteWarning').style.display = 'none';
        document.getElementById('multipleDeleteWarning').style.display = 'block';
        document.getElementById('multipleDeleteConfirmation').style.display = 'block';
        
        // Deshabilitar botón hasta que se escriba la confirmación
        document.getElementById('confirmDeleteBtn').disabled = true;
        
        // Limpiar input de confirmación
        document.getElementById('multipleDeleteConfirmText').value = '';
        
        // Mostrar modal
        const bootstrapModal = new bootstrap.Modal(this.modal);
        bootstrapModal.show();
    },

    resetModal: function() {
        this.currentCallback = null;
        this.isMultiple = false;
        document.getElementById('multipleDeleteConfirmText').value = '';
        document.getElementById('confirmDeleteBtn').disabled = false;
    },

    hide: function() {
        const bootstrapModal = bootstrap.Modal.getInstance(this.modal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }
};

const MAX_USERS = 15;

const configuracion = {
    // Estado actual del módulo
    currentTab: 'sedes',
    data: {
        empresas: [],
        sedes: [],
        establecimientos: []
    },
    
    // Inicialización del módulo
    init: function() {
        console.log('Inicializando módulo de configuración...');
        UnifiedDeleteModal.init(); // Inicializar modal unificado
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
        
        // Eventos de los modales
        this.bindModalEvents();
        
        // Eventos de búsqueda de usuarios (solo si existe - para ADMIN)
        const userSearch = document.getElementById('userSearch');
        const userRoleFilter = document.getElementById('userRoleFilter');
        
        if (userSearch && userRoleFilter) {
            userSearch.addEventListener('input', () => {
                this.filterUsers();
            });
            
            userRoleFilter.addEventListener('change', () => {
                this.filterUsers();
            });
        }
        
        // Recargar página
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    },
    
    // Vincular eventos de modales
    bindModalEvents: function() {
        // Modal de sedes
        document.getElementById('saveSedeBtn').addEventListener('click', () => {
            this.saveSede();
        });
        
        // Modal de establecimientos
        document.getElementById('saveEstablecimientoBtn').addEventListener('click', () => {
            this.saveEstablecimiento();
        });
        
        // Modal de usuarios (solo si existe - para ADMIN)
        const saveUserBtn = document.getElementById('saveUserBtn');
        if (saveUserBtn) {
            saveUserBtn.addEventListener('click', () => {
                this.saveUser();
            });
        }
        
        // Limpiar formularios al cerrar modales
        document.getElementById('sedeModal').addEventListener('hidden.bs.modal', () => {
            this.clearSedeForm();
        });
        
        document.getElementById('establecimientoModal').addEventListener('hidden.bs.modal', () => {
            this.clearEstablecimientoForm();
        });
        
        // Limpiar formulario de usuario al cerrar modal (solo si existe - para ADMIN)
        const userModal = document.getElementById('userModal');
        if (userModal) {
            userModal.addEventListener('hidden.bs.modal', () => {
                this.clearUserForm();
            });
        }
    },
    
    // Cargar datos iniciales
    loadInitialData: function() {
        this.loadEmpresas().then(() => {
            this.loadTabContent('sedes');
        });
    },
    
    // Cargar empresas
    loadEmpresas: async function() {
        try {
            const response = await fetch('api/configuracion/empresas.php');
            const result = await response.json();
            
            if (result.success) {
                this.data.empresas = result.data;
                // Ya no necesitamos poblar el select de empresas
            } else {
                this.showError('Error al cargar empresas: ' + result.message);
            }
        } catch (error) {
            console.error('Error al cargar empresas:', error);
            this.showError('Error de conexión al cargar empresas');
        }
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
            case 'usuarios':
                this.loadUsuariosContent();
                break;
        }
    },
    
    // Cargar contenido de sedes
    loadSedesContent: async function() {
        console.log('🔄 Iniciando carga de sedes...');
        const container = document.getElementById('sedesContent');
        if (!container) {
            console.error('❌ Container sedesContent no encontrado');
            return;
        }
        
        container.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Cargando sedes...</div>';
        
        try {
            console.log('📡 Solicitando datos de sedes...');
            const response = await fetch('api/configuracion/sedes.php');
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Sedes cargadas exitosamente:', result.data.length, 'sedes');
                this.data.sedes = result.data;
                this.renderSedesTable();
                this.populateSedeSelect(); // Para el modal de establecimientos
            } else {
                console.log('❌ Error al cargar sedes:', result.message);
                container.innerHTML = `<div class="alert alert-danger">Error: ${result.message}</div>`;
            }
        } catch (error) {
            console.error('Error al cargar sedes:', error);
            container.innerHTML = '<div class="alert alert-danger">Error de conexión al cargar sedes</div>';
        }
    },
    
    // Renderizar tabla de sedes
    renderSedesTable: function() {
        const container = document.getElementById('sedesContent');
        
        if (this.data.sedes.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h5>No hay sedes registradas</h5>
                    <p class="text-muted">Haz clic en "Nueva Sede" para agregar la primera sede.</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllSedes" 
                           onchange="configuracion.toggleSelectAllSedes(this)">
                    <label class="form-check-label" for="selectAllSedes">
                        Seleccionar todas
                    </label>
                </div>
                <button type="button" class="btn btn-danger btn-sm" id="deleteSelectedSedesBtn" 
                        onclick="configuracion.deleteSelectedSedes()" style="display: none;">
                    <i class="fas fa-trash me-1"></i>
                    Eliminar Seleccionadas (<span id="selectedSedesCount">0</span>)
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="50">
                                <i class="fas fa-check-square text-muted"></i>
                            </th>
                            <th>Empresa</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.data.sedes.forEach(sede => {
            html += `
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input sede-checkbox" type="checkbox" 
                                   value="${sede.id}" onchange="configuracion.updateSedeSelection()">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-medium">${sede.empresa_nombre}</span>
                            <small class="text-muted">${sede.empresa_ruc || ''}</small>
                        </div>
                    </td>
                    <td>
                        <span class="fw-medium">${sede.nombre}</span>
                    </td>
                    <td>
                        <span class="text-muted">${sede.direccion || 'Sin dirección'}</span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="configuracion.editSede(${sede.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    onclick="configuracion.deleteSede(${sede.id}, '${sede.nombre}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;
    },
    
    // Cargar contenido de establecimientos
    loadEstablecimientosContent: async function() {
        const container = document.getElementById('establecimientosContent');
        container.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Cargando establecimientos...</div>';
        
        try {
            const response = await fetch('api/configuracion/establecimientos.php');
            const result = await response.json();
            
            if (result.success) {
                this.data.establecimientos = result.data;
                this.renderEstablecimientosTable();
            } else {
                container.innerHTML = `<div class="alert alert-danger">Error: ${result.message}</div>`;
            }
        } catch (error) {
            console.error('Error al cargar establecimientos:', error);
            container.innerHTML = '<div class="alert alert-danger">Error de conexión al cargar establecimientos</div>';
        }
    },
    
    // Renderizar tabla de establecimientos
    renderEstablecimientosTable: function() {
        const container = document.getElementById('establecimientosContent');
        
        if (this.data.establecimientos.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                    <h5>No hay establecimientos registrados</h5>
                    <p class="text-muted">Haz clic en "Nuevo Establecimiento" para agregar el primero.</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllEstablecimientos" 
                           onchange="configuracion.toggleSelectAllEstablecimientos(this)">
                    <label class="form-check-label" for="selectAllEstablecimientos">
                        Seleccionar todos
                    </label>
                </div>
                <button type="button" class="btn btn-danger btn-sm" id="deleteSelectedEstablecimientosBtn" 
                        onclick="configuracion.deleteSelectedEstablecimientos()" style="display: none;">
                    <i class="fas fa-trash me-1"></i>
                    Eliminar Seleccionados (<span id="selectedEstablecimientosCount">0</span>)
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="50">
                                <i class="fas fa-check-square text-muted"></i>
                            </th>
                            <th>Empresa</th>
                            <th>Sede</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.data.establecimientos.forEach(est => {
            html += `
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input establecimiento-checkbox" type="checkbox" 
                                   value="${est.id}" onchange="configuracion.updateEstablecimientoSelection()">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-medium">${est.empresa_nombre}</span>
                            <small class="text-muted">${est.empresa_ruc || ''}</small>
                        </div>
                    </td>
                    <td>
                        <span class="text-primary">${est.sede_nombre}</span>
                    </td>
                    <td>
                        <span class="fw-medium">${est.nombre}</span>
                    </td>
                    <td>
                        <span class="text-muted">${est.direccion || 'Sin dirección'}</span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="configuracion.editEstablecimiento(${est.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    onclick="configuracion.deleteEstablecimiento(${est.id}, '${est.nombre}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;
    },
    
    // Poblar select de sedes
    populateSedeSelect: function() {
        const select = document.getElementById('establecimientoSede');
        select.innerHTML = '<option value="">Seleccione una sede...</option>';
        
        this.data.sedes.forEach(sede => {
            const option = document.createElement('option');
            option.value = sede.id;
            option.textContent = `${sede.empresa_nombre} - ${sede.nombre}`;
            select.appendChild(option);
        });
    },
    
    // Abrir modal de sede
    openSedeModal: function(id = null) {
        const modal = new bootstrap.Modal(document.getElementById('sedeModal'));
        const title = document.getElementById('sedeModalTitle');
        const saveBtn = document.getElementById('saveSedeText');
        
        // Cargar información de la empresa del usuario
        this.loadCurrentUserCompanyInfo();
        
        if (id) {
            title.textContent = 'Editar Sede';
            saveBtn.textContent = 'Actualizar';
            this.loadSedeData(id);
        } else {
            title.textContent = 'Agregar Sede';
            saveBtn.textContent = 'Guardar';
            this.clearSedeForm();
        }
        
        modal.show();
    },
    
    // Cargar información de la empresa del usuario
    loadCurrentUserCompanyInfo: async function() {
        const empresaInfo = document.getElementById('sedeEmpresaInfo');
        
        try {
            // Si ya tenemos datos de sedes, usar la información de empresa de cualquier sede
            if (this.data.sedes && this.data.sedes.length > 0) {
                const firstSede = this.data.sedes[0];
                empresaInfo.innerHTML = `
                    <i class="fas fa-building me-2"></i>
                    <strong>${firstSede.empresa_nombre}</strong> - RUC: ${firstSede.empresa_ruc}
                `;
            } else {
                // Si no hay sedes, obtener información del usuario actual
                const response = await fetch('auth/session.php?action=get_user');
                const result = await response.json();
                
                if (result.success && result.user) {
                    empresaInfo.innerHTML = `
                        <i class="fas fa-building me-2"></i>
                        <strong>${result.user.empresa_nombre}</strong> - RUC: ${result.user.empresa_ruc || 'N/A'}
                    `;
                } else {
                    empresaInfo.innerHTML = `
                        <i class="fas fa-building me-2"></i>
                        Tu empresa
                    `;
                }
            }
        } catch (error) {
            console.error('Error al cargar información de empresa:', error);
            empresaInfo.innerHTML = `
                <i class="fas fa-building me-2"></i>
                Tu empresa
            `;
        }
    },
    
    // Cargar datos de sede para edición
    loadSedeData: function(id) {
        const sede = this.data.sedes.find(s => s.id === id);
        if (sede) {
            document.getElementById('sedeId').value = sede.id;
            document.getElementById('sedeNombre').value = sede.nombre;
            document.getElementById('sedeDireccion').value = sede.direccion || '';
        }
    },
    
    // Limpiar formulario de sede
    clearSedeForm: function() {
        document.getElementById('sedeForm').reset();
        document.getElementById('sedeId').value = '';
        this.clearValidation('sedeForm');
    },
    
    // Guardar sede
    saveSede: async function() {
        const form = document.getElementById('sedeForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        if (!this.validateSedeForm(data)) {
            return;
        }
        
        const saveBtn = document.getElementById('saveSedeBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
        saveBtn.disabled = true;
        
        try {
            const url = 'api/configuracion/sedes.php';
            const method = data.id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                bootstrap.Modal.getInstance(document.getElementById('sedeModal')).hide();
                this.loadSedesContent();
            } else {
                this.showError('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error al guardar sede:', error);
            this.showError('Error de conexión al guardar la sede');
        } finally {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    },
    
    // Validar formulario de sede
    validateSedeForm: function(data) {
        let isValid = true;
        
        if (!data.nombre || data.nombre.trim().length < 2) {
            this.showFieldError('sedeNombre', 'El nombre debe tener al menos 2 caracteres');
            isValid = false;
        }
        
        return isValid;
    },
    
    // Editar sede
    editSede: function(id) {
        this.openSedeModal(id);
    },
    
    // Eliminar sede
    deleteSede: function(id, nombre) {
        UnifiedDeleteModal.showSingleDelete(
            'Confirmar Eliminación de Sede',
            `Estás a punto de eliminar la sede: "${nombre}". Esta acción no se puede deshacer.`,
            () => this.executeSedeDelete(id)
        );
    },
    
    // Ejecutar eliminación de sede
    executeSedeDelete: async function(id) {
        console.log('🗑️ Iniciando eliminación de sede:', id);
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (!confirmBtn) {
            console.error('❌ Botón de confirmación no encontrado');
            this.showError('Error: Botón de confirmación no encontrado');
            return;
        }
        
        const originalText = confirmBtn.innerHTML;
        
        // Mostrar estado de carga
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminando...';
        
        try {
            console.log('📡 Enviando request DELETE a API...');
            const requestData = { _method: 'DELETE', id: id };
            console.log('📋 Datos del request:', requestData);
            
            const response = await fetch('api/configuracion/sedes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', [...response.headers.entries()]);
            
            const responseText = await response.text();
            console.log('📄 Raw response:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
                console.log('✅ Parsed result:', result);
            } catch (parseError) {
                console.error('❌ Error parsing JSON response:', parseError);
                throw new Error('Respuesta del servidor no válida: ' + responseText.substring(0, 100));
            }
            
            // Cerrar modal
            console.log('🚪 Cerrando modal...');
            UnifiedDeleteModal.hide();
            
            if (result.success) {
                console.log('✅ Success message:', result.message);
                this.showSuccess(result.message);
                console.log('🔄 Recargando contenido de sedes...');
                this.loadSedesContent();
                console.log('🔄 Recargando contenido de establecimientos...');
                this.loadEstablecimientosContent(); // Recargar establecimientos también
            } else {
                console.log('❌ Error message:', result.message);
                this.showError(result.message);
            }
        } catch (error) {
            console.error('❌ Error al eliminar sede:', error);
            this.showError('Error de conexión al eliminar la sede: ' + error.message);
            
            // Cerrar modal en caso de error también
            UnifiedDeleteModal.hide();
        } finally {
            // Restaurar botón
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            console.log('🔧 Botón restaurado');
        }
    },
    
    // Abrir modal de establecimiento
    openEstablecimientoModal: function(id = null) {
        const modal = new bootstrap.Modal(document.getElementById('establecimientoModal'));
        const title = document.getElementById('establecimientoModalTitle');
        const saveBtn = document.getElementById('saveEstablecimientoText');
        
        if (id) {
            title.textContent = 'Editar Establecimiento';
            saveBtn.textContent = 'Actualizar';
            this.loadEstablecimientoData(id);
        } else {
            title.textContent = 'Agregar Establecimiento';
            saveBtn.textContent = 'Guardar';
            this.clearEstablecimientoForm();
        }
        
        modal.show();
    },
    
    // Cargar datos de establecimiento para edición
    loadEstablecimientoData: function(id) {
        const est = this.data.establecimientos.find(e => e.id === id);
        if (est) {
            document.getElementById('establecimientoId').value = est.id;
            document.getElementById('establecimientoSede').value = est.id_sede;
            document.getElementById('establecimientoNombre').value = est.nombre;
            document.getElementById('establecimientoDireccion').value = est.direccion || '';
        }
    },
    
    // Limpiar formulario de establecimiento
    clearEstablecimientoForm: function() {
        document.getElementById('establecimientoForm').reset();
        document.getElementById('establecimientoId').value = '';
        this.clearValidation('establecimientoForm');
    },
    
    // Limpiar formulario de usuario
    clearUserForm: function() {
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        this.clearValidation('userForm');
    },
    
    // Guardar establecimiento
    saveEstablecimiento: async function() {
        const form = document.getElementById('establecimientoForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        if (!this.validateEstablecimientoForm(data)) {
            return;
        }
        
        const saveBtn = document.getElementById('saveEstablecimientoBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
        saveBtn.disabled = true;
        
        try {
            const url = 'api/configuracion/establecimientos.php';
            const method = data.id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                bootstrap.Modal.getInstance(document.getElementById('establecimientoModal')).hide();
                this.loadEstablecimientosContent();
            } else {
                this.showError('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error al guardar establecimiento:', error);
            this.showError('Error de conexión al guardar el establecimiento');
        } finally {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    },
    
    // Validar formulario de establecimiento
    validateEstablecimientoForm: function(data) {
        let isValid = true;
        
        if (!data.id_sede) {
            this.showFieldError('establecimientoSede', 'Seleccione una sede');
            isValid = false;
        }
        
        if (!data.nombre || data.nombre.trim().length < 2) {
            this.showFieldError('establecimientoNombre', 'El nombre debe tener al menos 2 caracteres');
            isValid = false;
        }
        
        return isValid;
    },
    
    // Editar establecimiento
    editEstablecimiento: function(id) {
        this.openEstablecimientoModal(id);
    },
    
    // Eliminar establecimiento
    deleteEstablecimiento: function(id, nombre) {
        UnifiedDeleteModal.showSingleDelete(
            'Confirmar Eliminación de Establecimiento',
            `Estás a punto de eliminar el establecimiento: "${nombre}". Esta acción no se puede deshacer.`,
            () => this.executeEstablecimientoDelete(id)
        );
    },
    
    // Ejecutar eliminación de establecimiento
    executeEstablecimientoDelete: async function(id) {
        console.log('🗑️ Iniciando eliminación de establecimiento:', id);
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (!confirmBtn) {
            console.error('❌ Botón de confirmación no encontrado');
            this.showError('Error: Botón de confirmación no encontrado');
            return;
        }
        
        const originalText = confirmBtn.innerHTML;
        
        // Mostrar estado de carga
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminando...';
        
        try {
            console.log('📡 Enviando request DELETE a API...');
            const requestData = { _method: 'DELETE', id: id };
            console.log('📋 Datos del request:', requestData);
            
            const response = await fetch('api/configuracion/establecimientos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', [...response.headers.entries()]);
            
            const responseText = await response.text();
            console.log('📄 Raw response:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
                console.log('✅ Parsed result:', result);
            } catch (parseError) {
                console.error('❌ Error parsing JSON response:', parseError);
                throw new Error('Respuesta del servidor no válida: ' + responseText.substring(0, 100));
            }
            
            // Cerrar modal
            console.log('🚪 Cerrando modal...');
            UnifiedDeleteModal.hide();
            
            if (result.success) {
                console.log('✅ Success message:', result.message);
                this.showSuccess(result.message);
                console.log('🔄 Recargando contenido de establecimientos...');
                this.loadEstablecimientosContent();
            } else {
                console.log('❌ Error message:', result.message);
                this.showError(result.message);
            }
        } catch (error) {
            console.error('❌ Error al eliminar establecimiento:', error);
            this.showError('Error de conexión al eliminar el establecimiento: ' + error.message);
            
            // Cerrar modal en caso de error también
            UnifiedDeleteModal.hide();
        } finally {
            // Restaurar botón
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            console.log('🔧 Botón restaurado');
        }
    },

    // ==================== USER MANAGEMENT FUNCTIONS ====================    // Cargar contenido de usuarios
    loadUsuariosContent: async function() {
        const container = document.getElementById('usuariosContent');
        container.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Cargando usuarios...</div>';
        
        try {
            const response = await fetch('api/configuracion/usuarios.php');
            const result = await response.json();
            
            if (result.success) {
                this.data.usuarios = result.data;
                this.data.usuariosCount = result.count;
                this.data.usuariosMaxLimit = MAX_USERS;
                this.data.usuariosCanAddMore = (result.count || 0) < MAX_USERS;
                this.renderUsuariosTable();
            } else {
                container.innerHTML = `<div class="alert alert-danger">Error: ${result.message}</div>`;
            }
        } catch (error) {
            console.error('Error al cargar usuarios:', error);
            container.innerHTML = '<div class="alert alert-danger">Error de conexión al cargar usuarios</div>';
        }
    },
    
    // Renderizar tabla de usuarios
    renderUsuariosTable: function() {
        const container = document.getElementById('usuariosContent');
        
        if (this.data.usuarios.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5>No hay usuarios registrados</h5>
                    <p class="text-muted">Haz clic en "Nuevo Usuario" para agregar el primer usuario.</p>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Límite:</strong> Puedes crear hasta ${MAX_USERS} usuarios con roles GERENTE y ASISTENCIA.
                    </div>
                </div>
            `;
            return;
        }
        
        const userCount = this.data.usuariosCount || this.data.usuarios.length;
        const maxUsers = this.data.usuariosMaxLimit || MAX_USERS;
        const canAddMore = userCount < maxUsers;
        
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted">
                    <i class="fas fa-users me-1"></i>
                    ${userCount} de ${maxUsers} usuarios máximo
                </div>
                ${canAddMore ? '' : '<div class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Límite alcanzado</div>'}
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.data.usuarios.forEach(usuario => {
            const badgeClass = usuario.estado === 'A' ? 'bg-success' : 'bg-secondary';
            const estadoText = usuario.estado === 'A' ? 'Activo' : 'Inactivo';
            const roleClass = usuario.rol === 'GERENTE' ? 'bg-primary' : 'bg-info';
            
            html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle text-muted me-2" style="font-size: 1.5rem;"></i>
                            <span class="fw-medium">${usuario.username}</span>
                        </div>
                    </td>
                    <td>${usuario.nombre_completo}</td>
                    <td>
                        <i class="fas fa-envelope text-muted me-1"></i>
                        ${usuario.email}
                    </td>
                    <td>
                        <span class="badge ${roleClass}">${usuario.rol}</span>
                    </td>
                    <td>
                        <span class="badge ${badgeClass}">${estadoText}</span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" 
                                    onclick="configuracion.openUserModal(${usuario.id})" 
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="configuracion.deleteUser(${usuario.id}, '${usuario.username}')" 
                                    title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Update the "Nuevo Usuario" button state
        const newUserBtn = document.querySelector('button[onclick="configuracion.openUserModal()"]');
        if (newUserBtn) {
            if (!canAddMore) {
                newUserBtn.disabled = true;
                newUserBtn.innerHTML = '<i class="fas fa-ban me-2"></i>Límite Alcanzado';
            } else {
                newUserBtn.disabled = false;
                newUserBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Usuario';
            }
        }
    },
    
    // Abrir modal de usuario
    openUserModal: function(id = null) {
        const modal = new bootstrap.Modal(document.getElementById('userModal'));
        const title = document.getElementById('userModalTitle');
        const saveBtn = document.getElementById('saveUserText');
        const passwordRequired = document.getElementById('passwordRequired');
        const passwordConfirmRequired = document.getElementById('passwordConfirmRequired');
        const passwordHelp = document.getElementById('passwordHelp');
        
        if (id) {
            title.textContent = 'Editar Usuario';
            saveBtn.textContent = 'Actualizar';
            passwordRequired.textContent = '';
            passwordConfirmRequired.textContent = '';
            passwordHelp.textContent = 'Dejar en blanco para mantener la contraseña actual';
            
            const usuario = this.data.usuarios.find(u => u.id === id);
            if (usuario) {
                document.getElementById('userId').value = usuario.id;
                document.getElementById('userName').value = usuario.username;
                document.getElementById('userEmail').value = usuario.email;
                document.getElementById('userFullName').value = usuario.nombre_completo;
                document.getElementById('userRole').value = usuario.rol;
                document.getElementById('userStatus').value = usuario.estado;
                document.getElementById('userPassword').value = '';
                document.getElementById('userPasswordConfirm').value = '';
            }
        } else {
            title.textContent = 'Agregar Usuario';
            saveBtn.textContent = 'Guardar';
            passwordRequired.textContent = '*';
            passwordConfirmRequired.textContent = '*';
            passwordHelp.textContent = 'Mínimo 6 caracteres';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
        }
        
        this.clearValidation('userForm');
        modal.show();
    },
    
    // Guardar usuario
    saveUser: async function() {
        const form = document.getElementById('userForm');
        const formData = new FormData(form);
        const userId = formData.get('id');
        const isEditing = !!userId;
        
        // Clear validation
        this.clearValidation('userForm');
        
        // Client-side validation
        if (!this.validateUserForm(formData, isEditing)) {
            return;
        }
        
        const submitBtn = document.getElementById('saveUserBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
        
        try {
            const url = isEditing ? `api/configuracion/usuarios.php/${userId}` : 'api/configuracion/usuarios.php';
            const method = isEditing ? 'PUT' : 'POST';
            
            // Prepare data
            const userData = {
                username: formData.get('username'),
                email: formData.get('email'),
                nombre_completo: formData.get('nombre_completo'),
                rol: formData.get('rol'),
                estado: formData.get('estado') || 'A'
            };
            
            // Add password only if provided
            const password = formData.get('contrasena');
            if (password) {
                userData.contrasena = password;
            }
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(isEditing ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente');
                bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
                this.loadUsuariosContent();
            } else {
                this.showError(result.message || 'Error al guardar usuario');
                
                // Show field errors
                if (result.field_errors) {
                    Object.keys(result.field_errors).forEach(field => {
                        this.showFieldError(field, result.field_errors[field]);
                    });
                }
            }
        } catch (error) {
            console.error('Error al guardar usuario:', error);
            this.showError('Error de conexión. Inténtalo de nuevo.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<i class="fas fa-save me-2"></i>${document.getElementById('saveUserText').textContent}`;
        }
    },
    
    // Validar formulario de usuario
    validateUserForm: function(formData, isEditing) {
        let isValid = true;
        
        const username = formData.get('username');
        const email = formData.get('email');
        const nombreCompleto = formData.get('nombre_completo');
        const rol = formData.get('rol');
        const password = formData.get('contrasena');
        const passwordConfirm = formData.get('contrasena_confirm');
        
        if (!username || username.trim().length < 3) {
            this.showFieldError('userName', 'El usuario debe tener al menos 3 caracteres');
            isValid = false;
        }
        
        if (!email || !this.isValidEmail(email)) {
            this.showFieldError('userEmail', 'Ingresa un email válido');
            isValid = false;
        }
        
        if (!nombreCompleto || nombreCompleto.trim().length < 2) {
            this.showFieldError('userFullName', 'El nombre completo es requerido');
            isValid = false;
        }
        
        if (!rol) {
            this.showFieldError('userRole', 'Selecciona un rol');
            isValid = false;
        }
        
        // Password validation
        if (!isEditing || password) {
            if (!password || password.length < 6) {
                this.showFieldError('userPassword', 'La contraseña debe tener al menos 6 caracteres');
                isValid = false;
            }
            
            if (password !== passwordConfirm) {
                this.showFieldError('userPasswordConfirm', 'Las contraseñas no coinciden');
                isValid = false;
            }
        }
        
        return isValid;
    },
    
    // Validar email
    isValidEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // Eliminar usuario
    deleteUser: function(id, nombre) {
        UnifiedDeleteModal.showSingleDelete(
            'Confirmar Eliminación de Usuario',
            `Estás a punto de eliminar el usuario: "${nombre}". Esta acción no se puede deshacer.`,
            () => this.executeUserDelete(id)
        );
    },
    
    // Función para ejecutar eliminación de usuario (se llama desde las nuevas funciones de confirmación)
    executeUserDelete: async function(id) {
        console.log('🗑️ Iniciando eliminación de usuario:', id);
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (!confirmBtn) {
            console.error('❌ Botón de confirmación no encontrado');
            this.showError('Error: Botón de confirmación no encontrado');
            return;
        }
        
        const originalText = confirmBtn.innerHTML;
        
        // Mostrar estado de carga
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminando...';
        
        try {
            console.log('📡 Enviando request DELETE a API...');
            const requestData = { _method: 'DELETE', id: id };
            console.log('📋 Datos del request:', requestData);
            
            const response = await fetch(`api/configuracion/usuarios.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', [...response.headers.entries()]);
            
            const responseText = await response.text();
            console.log('📄 Raw response:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
                console.log('✅ Parsed result:', result);
            } catch (parseError) {
                console.error('❌ Error parsing JSON response:', parseError);
                throw new Error('Respuesta del servidor no válida: ' + responseText.substring(0, 100));
            }
            
            // Cerrar modal
            console.log('🚪 Cerrando modal...');
            UnifiedDeleteModal.hide();
            
            if (result.success) {
                console.log('✅ Success message:', result.message);
                this.showSuccess('Usuario eliminado exitosamente');
                console.log('🔄 Recargando contenido de usuarios...');
                this.loadUsuariosContent();
            } else {
                console.log('❌ Error message:', result.message);
                this.showError(result.message || 'Error al eliminar usuario');
            }
        } catch (error) {
            console.error('❌ Error al eliminar usuario:', error);
            this.showError('Error de conexión. Inténtalo de nuevo: ' + error.message);
            
            // Cerrar modal en caso de error también
            UnifiedDeleteModal.hide();
        } finally {
            // Restaurar botón
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            console.log('🔧 Botón restaurado');
        }
    },
    
    // Filtrar usuarios
    filterUsers: function() {
        if (!this.data.usuarios) return;
        
        const searchTerm = document.getElementById('userSearch').value.toLowerCase();
        const roleFilter = document.getElementById('userRoleFilter').value;
        
        const filteredUsers = this.data.usuarios.filter(usuario => {
            const matchesSearch = !searchTerm || 
                usuario.username.toLowerCase().includes(searchTerm) ||
                usuario.nombre_completo.toLowerCase().includes(searchTerm) ||
                usuario.email.toLowerCase().includes(searchTerm);
                
            const matchesRole = !roleFilter || usuario.rol === roleFilter;
            
            return matchesSearch && matchesRole;
        });
        
        // Temporalmente almacenar usuarios filtrados
        const originalUsers = this.data.usuarios;
        this.data.usuarios = filteredUsers;
        this.renderUsuariosTable();
        this.data.usuarios = originalUsers;
    },
    
    // ==================== END USER MANAGEMENT FUNCTIONS ====================

    // Cargar contenido de contraseña
    loadPasswordContent: function() {
        console.log('Cargando contenido de contraseña...');
        const container = document.getElementById('passwordContent');
        
        container.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form id="changePasswordForm">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">
                                        <i class="fas fa-unlock-alt text-muted me-2"></i>
                                        Contraseña Actual *
                                    </label>
                                    <input type="password" class="form-control" id="currentPassword" 
                                           name="currentPassword" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">
                                        <i class="fas fa-key text-muted me-2"></i>
                                        Nueva Contraseña *
                                    </label>
                                    <input type="password" class="form-control" id="newPassword" 
                                           name="newPassword" required minlength="6">
                                    <div class="form-text">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Mínimo 6 caracteres
                                        </small>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirmPassword" class="form-label">
                                        <i class="fas fa-check-double text-muted me-2"></i>
                                        Confirmar Nueva Contraseña *
                                    </label>
                                    <input type="password" class="form-control" id="confirmPassword" 
                                           name="confirmPassword" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>
                                        Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Consejos de seguridad:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Usa una contraseña única y segura</li>
                                <li>Combina letras, números y símbolos</li>
                                <li>No compartas tu contraseña con nadie</li>
                                <li>Cambia tu contraseña periódicamente</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Bind form submit event
        document.getElementById('changePasswordForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handlePasswordChange();
        });
    },
    
    // Manejar cambio de contraseña
    handlePasswordChange: async function() {
        const form = document.getElementById('changePasswordForm');
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Clear previous validation
        this.clearValidation('changePasswordForm');
        
        // Client-side validation
        const currentPassword = formData.get('currentPassword');
        const newPassword = formData.get('newPassword');
        const confirmPassword = formData.get('confirmPassword');
        
        if (!currentPassword) {
            this.showFieldError('currentPassword', 'La contraseña actual es requerida');
            return;
        }
        
        if (!newPassword || newPassword.length < 6) {
            this.showFieldError('newPassword', 'La nueva contraseña debe tener al menos 6 caracteres');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            this.showFieldError('confirmPassword', 'Las contraseñas no coinciden');
            return;
        }
        
        if (currentPassword === newPassword) {
            this.showFieldError('newPassword', 'La nueva contraseña debe ser diferente a la actual');
            return;
        }
        
        // Update button state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cambiando...';
        
        try {
            const response = await fetch('api/configuracion/change-password.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Contraseña cambiada exitosamente');
                form.reset();
            } else {
                this.showError(result.message || 'Error al cambiar la contraseña');
                
                // Show specific field errors if provided
                if (result.field_errors) {
                    Object.keys(result.field_errors).forEach(field => {
                        this.showFieldError(field, result.field_errors[field]);
                    });
                }
            }
        } catch (error) {
            console.error('Error al cambiar contraseña:', error);
            this.showError('Error de conexión. Inténtalo de nuevo.');
        } finally {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Cambiar Contraseña';
        }
    },
    
    // Utilidades
    showSuccess: function(message) {
        // Implementar notificación de éxito
        this.showNotification(message, 'success');
    },
    
    showError: function(message) {
        // Implementar notificación de error
        this.showNotification(message, 'error');
    },
    
    showNotification: function(message, type) {
        console.log(`📢 Mostrando notificación ${type}:`, message);
        
        // Sistema simple de notificaciones
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
        
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        alert.innerHTML = `
            <i class="fas fa-${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alert);
        console.log('📢 Notificación agregada al DOM');
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
                console.log('📢 Notificación removida del DOM');
            }
        }, 5000);
    },
    
    showFieldError: function(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = message;
        }
    },
    
    clearValidation: function(formId) {
        const form = document.getElementById(formId);
        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.textContent = '';
        });
    },
    
    // ========== Funciones de Selección Múltiple ==========
    
    // Sedes
    toggleSelectAllSedes: function(checkbox) {
        const sedeCheckboxes = document.querySelectorAll('.sede-checkbox');
        sedeCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
        this.updateSedeSelection();
    },
    
    updateSedeSelection: function() {
        const checkboxes = document.querySelectorAll('.sede-checkbox');
        const selectedCount = document.querySelectorAll('.sede-checkbox:checked').length;
        const selectAllCheckbox = document.getElementById('selectAllSedes');
        const deleteBtn = document.getElementById('deleteSelectedSedesBtn');
        const countSpan = document.getElementById('selectedSedesCount');
        
        // Actualizar checkbox "Seleccionar todas"
        if (selectedCount === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (selectedCount === checkboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
        
        // Mostrar/ocultar botón de eliminar
        if (selectedCount > 0) {
            deleteBtn.style.display = 'block';
            countSpan.textContent = selectedCount;
        } else {
            deleteBtn.style.display = 'none';
        }
    },
    
    deleteSelectedSedes: function() {
        const selectedCheckboxes = document.querySelectorAll('.sede-checkbox:checked');
        const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
        
        if (selectedIds.length === 0) {
            this.showError('No hay sedes seleccionadas');
            return;
        }
        
        // Mostrar modal unificado para eliminación múltiple
        UnifiedDeleteModal.showMultipleDelete(
            selectedIds.length,
            'sede',
            () => this.executeMultipleSedeDelete(selectedIds)
        );
    },
    
    async executeMultipleSedeDelete(ids) {
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const originalText = confirmBtn.innerHTML;
        
        // Mostrar estado de carga
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminando...';
        
        try {
            const response = await fetch('api/configuracion/sedes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ _method: 'DELETE', ids: ids })
            });
            
            const result = await response.json();
            
            // Cerrar modal y resetear
            UnifiedDeleteModal.hide();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.loadSedesContent();
                this.loadEstablecimientosContent(); // Recargar establecimientos
                
                // Resetear selecciones
                this.resetSedeSelections();
            } else {
                this.showError(result.message);
                if (result.errors && result.errors.length > 0) {
                    result.errors.forEach(error => {
                        this.showError(error);
                    });
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('Error de conexión al eliminar sedes');
            
            // Cerrar modal en caso de error también
            UnifiedDeleteModal.hide();
        } finally {
            // Restaurar botón
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    },
    
    // Establecimientos
    toggleSelectAllEstablecimientos: function(checkbox) {
        const establecimientoCheckboxes = document.querySelectorAll('.establecimiento-checkbox');
        establecimientoCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
        this.updateEstablecimientoSelection();
    },
    
    updateEstablecimientoSelection: function() {
        const checkboxes = document.querySelectorAll('.establecimiento-checkbox');
        const selectedCount = document.querySelectorAll('.establecimiento-checkbox:checked').length;
        const selectAllCheckbox = document.getElementById('selectAllEstablecimientos');
        const deleteBtn = document.getElementById('deleteSelectedEstablecimientosBtn');
        const countSpan = document.getElementById('selectedEstablecimientosCount');
        
        // Actualizar checkbox "Seleccionar todos"
        if (selectedCount === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (selectedCount === checkboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
        
        // Mostrar/ocultar botón de eliminar
        if (selectedCount > 0) {
            deleteBtn.style.display = 'block';
            countSpan.textContent = selectedCount;
        } else {
            deleteBtn.style.display = 'none';
        }
    },
    
    deleteSelectedEstablecimientos: function() {
        const selectedCheckboxes = document.querySelectorAll('.establecimiento-checkbox:checked');
        const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
        
        if (selectedIds.length === 0) {
            this.showError('No hay establecimientos seleccionados');
            return;
        }
        
        // Mostrar modal unificado para eliminación múltiple
        UnifiedDeleteModal.showMultipleDelete(
            selectedIds.length,
            'establecimiento',
            () => this.executeMultipleEstablecimientoDelete(selectedIds)
        );
    },
    
    async executeMultipleEstablecimientoDelete(ids) {
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const originalText = confirmBtn.innerHTML;
        
        // Mostrar estado de carga
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminando...';
        
        try {
            const response = await fetch('api/configuracion/establecimientos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ _method: 'DELETE', ids: ids })
            });
            
            const result = await response.json();
            
            // Cerrar modal y resetear
            UnifiedDeleteModal.hide();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.loadEstablecimientosContent();
                
                // Resetear selecciones
                this.resetEstablecimientoSelections();
            } else {
                this.showError(result.message);
                if (result.errors && result.errors.length > 0) {
                    result.errors.forEach(error => {
                        this.showError(error);
                    });
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('Error de conexión al eliminar establecimientos');
            
            // Cerrar modal en caso de error también
            UnifiedDeleteModal.hide();
        } finally {
            // Restaurar botón
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    },
    
    // Funciones de reseteo de selecciones
    resetSedeSelections: function() {
        // Desmarcar todos los checkboxes
        document.querySelectorAll('.sede-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllSedes').checked = false;
        document.getElementById('selectAllSedes').indeterminate = false;
        
        // Ocultar botón de eliminar seleccionadas
        const deleteBtn = document.getElementById('deleteSelectedSedesBtn');
        if (deleteBtn) {
            deleteBtn.style.display = 'none';
        }
    },
    
    resetEstablecimientoSelections: function() {
        // Desmarcar todos los checkboxes
        document.querySelectorAll('.establecimiento-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllEstablecimientos').checked = false;
        document.getElementById('selectAllEstablecimientos').indeterminate = false;
        
        // Ocultar botón de eliminar seleccionadas
        const deleteBtn = document.getElementById('deleteSelectedEstablecimientosBtn');
        if (deleteBtn) {
            deleteBtn.style.display = 'none';
        }
    },

    // Limpieza
    cleanup: function() {
        console.log('Limpiando módulo de configuración...');
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // El módulo se inicializa desde configuracion.php
});