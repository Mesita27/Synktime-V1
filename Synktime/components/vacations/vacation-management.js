/**
 * Vacation Management System JavaScript
 * Handles vacation requests, approvals, and management interface
 */

class VacationManager {
    constructor() {
        this.apiBase = '../../api/vacations/manage.php';
        this.currentUser = this.getCurrentUser();
        this.currentTab = 'my-vacations';
        this.vacationData = {};
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadUserPermissions();
        this.loadVacationBalance();
        this.loadMyVacations();
    }
    
    setupEventListeners() {
        // Date inputs change listeners
        const startDate = document.getElementById('start-date');
        const endDate = document.getElementById('end-date');
        
        if (startDate) {
            startDate.addEventListener('change', () => this.calculateDays());
        }
        
        if (endDate) {
            endDate.addEventListener('change', () => this.calculateDays());
        }
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        if (startDate) startDate.min = today;
        if (endDate) endDate.min = today;
        
        // Modal close events
        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                this.closeModal(event.target.id);
            }
        });
        
        // Keyboard events
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }
    
    getCurrentUser() {
        // This would typically come from session/authentication
        return {
            id: 1,
            name: 'Usuario Actual',
            role: 'employee', // employee, supervisor, manager, admin
            permissions: ['vacations.create', 'vacations.view_own']
        };
    }
    
    loadUserPermissions() {
        // Show/hide tabs based on user permissions
        if (this.currentUser.permissions.includes('vacations.approve')) {
            document.getElementById('approvals-tab').style.display = 'block';
        }
        
        if (this.currentUser.permissions.includes('vacations.view_all')) {
            document.getElementById('all-vacations-tab').style.display = 'block';
        }
    }
    
    async loadVacationBalance() {
        try {
            const response = await this.apiCall('GET', '/balance', { employee_id: this.currentUser.id });
            
            if (response.success) {
                const balance = response.data;
                document.getElementById('available-days').textContent = balance.DIAS_DISPONIBLES || 0;
                document.getElementById('used-days').textContent = balance.DIAS_USADOS || 0;
            }
        } catch (error) {
            console.error('Error loading vacation balance:', error);
        }
    }
    
    async loadMyVacations() {
        try {
            const tbody = document.getElementById('my-vacations-list');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="loading"></div> Cargando...</td></tr>';
            
            const response = await this.apiCall('GET', '/employee', { 
                employee_id: this.currentUser.id 
            });
            
            if (response.success) {
                this.renderVacationList(response.data, 'my-vacations-list', false);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">Error al cargar datos</td></tr>';
            }
        } catch (error) {
            console.error('Error loading vacations:', error);
            const tbody = document.getElementById('my-vacations-list');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Error al cargar datos</td></tr>';
        }
    }
    
    async loadPendingApprovals() {
        try {
            const tbody = document.getElementById('pending-approvals-list');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="loading"></div> Cargando...</td></tr>';
            
            const response = await this.apiCall('GET', '/pending');
            
            if (response.success) {
                this.renderVacationList(response.data, 'pending-approvals-list', true, true);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay solicitudes pendientes</td></tr>';
            }
        } catch (error) {
            console.error('Error loading pending approvals:', error);
            const tbody = document.getElementById('pending-approvals-list');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Error al cargar datos</td></tr>';
        }
    }
    
    async loadAllVacations() {
        try {
            const tbody = document.getElementById('all-vacations-list');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="loading"></div> Cargando...</td></tr>';
            
            const response = await this.apiCall('GET', '');
            
            if (response.success) {
                this.renderVacationList(response.data, 'all-vacations-list', true, false, true);
            } else {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Error al cargar datos</td></tr>';
            }
        } catch (error) {
            console.error('Error loading all vacations:', error);
            const tbody = document.getElementById('all-vacations-list');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Error al cargar datos</td></tr>';
        }
    }
    
    renderVacationList(data, tbodyId, showEmployee = false, showApprovalActions = false, showStatus = false) {
        const tbody = document.getElementById(tbodyId);
        
        if (!data || data.length === 0) {
            const colspan = showEmployee ? (showStatus ? 8 : 7) : 7;
            tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center">No hay solicitudes</td></tr>`;
            return;
        }
        
        tbody.innerHTML = data.map(vacation => {
            const startDate = new Date(vacation.FECHA_INICIO).toLocaleDateString('es-ES');
            const endDate = new Date(vacation.FECHA_FIN).toLocaleDateString('es-ES');
            const requestDate = new Date(vacation.FECHA_SOLICITUD).toLocaleDateString('es-ES');
            const employeeName = showEmployee ? `${vacation.NOMBRE} ${vacation.APELLIDO}` : '';
            
            let actions = '';
            if (showApprovalActions) {
                actions = `
                    <div class="action-buttons">
                        <button class="btn-sm btn-success" onclick="vacationManager.openApprovalModal(${vacation.ID}, 'approve')" title="Aprobar">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-sm btn-danger" onclick="vacationManager.openApprovalModal(${vacation.ID}, 'reject')" title="Rechazar">
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="btn-sm btn-info" onclick="vacationManager.viewVacationDetails(${vacation.ID})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                `;
            } else {
                actions = `
                    <div class="action-buttons">
                        <button class="btn-sm btn-info" onclick="vacationManager.viewVacationDetails(${vacation.ID})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${vacation.ESTADO === 'pending' ? `
                            <button class="btn-sm btn-danger" onclick="vacationManager.cancelVacation(${vacation.ID})" title="Cancelar">
                                <i class="fas fa-ban"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
            }
            
            return `
                <tr>
                    ${showEmployee ? `<td>${employeeName}</td>` : ''}
                    <td>${requestDate}</td>
                    <td>${startDate}</td>
                    <td>${endDate}</td>
                    <td>${vacation.DIAS_HABILES}</td>
                    <td>${this.getVacationTypeLabel(vacation.TIPO_VACACION)}</td>
                    ${showStatus ? `<td>${this.getStatusBadge(vacation.ESTADO)}</td>` : ''}
                    ${!showStatus && !showApprovalActions ? `<td>${this.getStatusBadge(vacation.ESTADO)}</td>` : ''}
                    <td>${actions}</td>
                </tr>
            `;
        }).join('');
    }
    
    getVacationTypeLabel(type) {
        const types = {
            'vacation': 'Vacaciones',
            'sick_leave': 'Permiso Médico',
            'personal': 'Permiso Personal',
            'maternity': 'Maternidad',
            'paternity': 'Paternidad',
            'other': 'Otro'
        };
        return types[type] || type;
    }
    
    getStatusBadge(status) {
        const badges = {
            'pending': '<span class="status-badge status-pending">Pendiente</span>',
            'approved': '<span class="status-badge status-approved">Aprobado</span>',
            'rejected': '<span class="status-badge status-rejected">Rechazado</span>',
            'cancelled': '<span class="status-badge status-cancelled">Cancelado</span>'
        };
        return badges[status] || status;
    }
    
    calculateDays() {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end < start) {
                document.getElementById('calculated-days').value = 'Fecha inválida';
                return;
            }
            
            const totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            const workingDays = this.calculateWorkingDays(start, end);
            
            document.getElementById('calculated-days').value = `${totalDays} días totales (${workingDays} días hábiles)`;
        } else {
            document.getElementById('calculated-days').value = '';
        }
    }
    
    calculateWorkingDays(startDate, endDate) {
        let workingDays = 0;
        const currentDate = new Date(startDate);
        
        while (currentDate <= endDate) {
            const dayOfWeek = currentDate.getDay();
            // Monday = 1, Sunday = 0
            if (dayOfWeek >= 1 && dayOfWeek <= 5) {
                workingDays++;
            }
            currentDate.setDate(currentDate.getDate() + 1);
        }
        
        return workingDays;
    }
    
    async submitVacationRequest(event) {
        event.preventDefault();
        
        const submitBtn = event.target.querySelector('button[type="submit"]');
        const btnText = submitBtn.querySelector('.btn-text');
        const loading = submitBtn.querySelector('.loading');
        
        try {
            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            const formData = new FormData(event.target);
            const data = {
                ID_EMPLEADO: this.currentUser.id,
                FECHA_INICIO: formData.get('start_date'),
                FECHA_FIN: formData.get('end_date'),
                TIPO_VACACION: formData.get('vacation_type'),
                MOTIVO: formData.get('reason')
            };
            
            const response = await this.apiCall('POST', '', data);
            
            if (response.success) {
                this.showAlert('success', 'Solicitud de vacaciones enviada exitosamente');
                this.closeModal('new-vacation-modal');
                document.getElementById('vacation-form').reset();
                this.loadMyVacations();
                this.loadVacationBalance();
            } else {
                this.showAlert('danger', response.message || 'Error al enviar la solicitud');
            }
        } catch (error) {
            console.error('Error submitting vacation request:', error);
            this.showAlert('danger', 'Error al enviar la solicitud');
        } finally {
            btnText.style.display = 'inline';
            loading.style.display = 'none';
            submitBtn.disabled = false;
        }
    }
    
    async viewVacationDetails(vacationId) {
        try {
            const response = await this.apiCall('GET', `/${vacationId}`);
            
            if (response.success) {
                const vacation = response.data;
                this.showVacationDetailsModal(vacation);
            } else {
                this.showAlert('danger', 'Error al cargar los detalles');
            }
        } catch (error) {
            console.error('Error loading vacation details:', error);
            this.showAlert('danger', 'Error al cargar los detalles');
        }
    }
    
    showVacationDetailsModal(vacation) {
        const startDate = new Date(vacation.FECHA_INICIO).toLocaleDateString('es-ES');
        const endDate = new Date(vacation.FECHA_FIN).toLocaleDateString('es-ES');
        const requestDate = new Date(vacation.FECHA_SOLICITUD).toLocaleDateString('es-ES');
        const approvalDate = vacation.FECHA_APROBACION ? 
            new Date(vacation.FECHA_APROBACION).toLocaleDateString('es-ES') : 'N/A';
        
        const content = `
            <div class="vacation-details">
                <div class="detail-row">
                    <strong>Empleado:</strong> ${vacation.NOMBRE} ${vacation.APELLIDO}
                </div>
                <div class="detail-row">
                    <strong>Fecha de Solicitud:</strong> ${requestDate}
                </div>
                <div class="detail-row">
                    <strong>Período:</strong> ${startDate} - ${endDate}
                </div>
                <div class="detail-row">
                    <strong>Días Solicitados:</strong> ${vacation.DIAS_SOLICITADOS} (${vacation.DIAS_HABILES} días hábiles)
                </div>
                <div class="detail-row">
                    <strong>Tipo:</strong> ${this.getVacationTypeLabel(vacation.TIPO_VACACION)}
                </div>
                <div class="detail-row">
                    <strong>Estado:</strong> ${this.getStatusBadge(vacation.ESTADO)}
                </div>
                <div class="detail-row">
                    <strong>Motivo:</strong><br>
                    <div class="detail-text">${vacation.MOTIVO}</div>
                </div>
                ${vacation.APROBADO_POR_NOMBRE ? `
                    <div class="detail-row">
                        <strong>Aprobado/Rechazado por:</strong> ${vacation.APROBADO_POR_NOMBRE}
                    </div>
                    <div class="detail-row">
                        <strong>Fecha de Aprobación:</strong> ${approvalDate}
                    </div>
                ` : ''}
                ${vacation.COMENTARIOS_APROBACION ? `
                    <div class="detail-row">
                        <strong>Comentarios:</strong><br>
                        <div class="detail-text">${vacation.COMENTARIOS_APROBACION}</div>
                    </div>
                ` : ''}
            </div>
        `;
        
        document.getElementById('vacation-details-content').innerHTML = content;
        this.openModal('vacation-details-modal');
    }
    
    openApprovalModal(vacationId, action) {
        document.getElementById('approval-vacation-id').value = vacationId;
        document.getElementById('approval-action').value = action;
        
        const submitBtn = document.getElementById('approval-submit-btn');
        if (action === 'approve') {
            submitBtn.textContent = 'Aprobar';
            submitBtn.className = 'btn btn-success';
        } else {
            submitBtn.textContent = 'Rechazar';
            submitBtn.className = 'btn btn-danger';
        }
        
        this.openModal('approval-modal');
    }
    
    async processApproval(event) {
        event.preventDefault();
        
        const submitBtn = event.target.querySelector('button[type="submit"]');
        const btnText = submitBtn.querySelector('.btn-text');
        const loading = submitBtn.querySelector('.loading');
        
        try {
            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            const vacationId = document.getElementById('approval-vacation-id').value;
            const action = document.getElementById('approval-action').value;
            const comments = document.getElementById('approval-comments').value;
            
            const endpoint = action === 'approve' ? 'approve' : 'reject';
            const response = await this.apiCall('POST', `/${vacationId}/${endpoint}`, {
                comentarios: comments
            });
            
            if (response.success) {
                const actionText = action === 'approve' ? 'aprobada' : 'rechazada';
                this.showAlert('success', `Solicitud ${actionText} exitosamente`);
                this.closeModal('approval-modal');
                this.loadPendingApprovals();
                
                // Update pending count
                this.updatePendingCount();
            } else {
                this.showAlert('danger', response.message || 'Error al procesar la solicitud');
            }
        } catch (error) {
            console.error('Error processing approval:', error);
            this.showAlert('danger', 'Error al procesar la solicitud');
        } finally {
            btnText.style.display = 'inline';
            loading.style.display = 'none';
            submitBtn.disabled = false;
        }
    }
    
    async cancelVacation(vacationId) {
        if (!confirm('¿Está seguro de que desea cancelar esta solicitud?')) {
            return;
        }
        
        try {
            const response = await this.apiCall('DELETE', `/${vacationId}`);
            
            if (response.success) {
                this.showAlert('success', 'Solicitud cancelada exitosamente');
                this.loadMyVacations();
                this.loadVacationBalance();
            } else {
                this.showAlert('danger', response.message || 'Error al cancelar la solicitud');
            }
        } catch (error) {
            console.error('Error cancelling vacation:', error);
            this.showAlert('danger', 'Error al cancelar la solicitud');
        }
    }
    
    async updatePendingCount() {
        try {
            const response = await this.apiCall('GET', '/pending');
            if (response.success) {
                document.getElementById('pending-requests').textContent = response.data.length;
            }
        } catch (error) {
            console.error('Error updating pending count:', error);
        }
    }
    
    showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName).classList.add('active');
        
        // Add active class to clicked button
        event.target.classList.add('active');
        
        // Load data for the tab
        this.currentTab = tabName;
        switch (tabName) {
            case 'my-vacations':
                this.loadMyVacations();
                break;
            case 'pending-approvals':
                this.loadPendingApprovals();
                break;
            case 'all-vacations':
                this.loadAllVacations();
                break;
        }
    }
    
    openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }
    
    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
    
    showAlert(type, message) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
        
        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        // Insert at top of container
        const container = document.querySelector('.vacation-container');
        container.insertBefore(alert, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
    
    async apiCall(method, endpoint, data = null) {
        const url = this.apiBase + endpoint;
        
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
}

// Global functions (called from HTML)
let vacationManager;

document.addEventListener('DOMContentLoaded', () => {
    vacationManager = new VacationManager();
});

function openNewVacationModal() {
    vacationManager.openModal('new-vacation-modal');
}

function closeModal(modalId) {
    vacationManager.closeModal(modalId);
}

function showTab(tabName) {
    vacationManager.showTab(tabName);
}

function submitVacationRequest(event) {
    return vacationManager.submitVacationRequest(event);
}

function processApproval(event) {
    return vacationManager.processApproval(event);
}