// horarios-personalizados.js
// JavaScript para el módulo de horarios personalizados

class HorariosPersonalizados {
    constructor() {
        this.currentPage = 1;
        this.currentLimit = 15;
        this.currentFilters = {};
        this.selectedEmployees = new Set();
        this.currentEmployeeId = null;
        this.currentDay = 1;
        this.employeeSchedules = {};
        this.unsavedChanges = false;

        // Propiedades para el modal de empleados
        this.availableEmpleados = [];
        this.selectedEmpleados = [];
        this.originalEmpleados = [];

        // Propiedades para el modal de empleados en exportación
        this.availableEmpleadosExport = [];
        this.selectedEmpleadosExport = [];
        this.originalEmpleadosExport = [];
        this.tempEmpleadosExport = []; // Para cambios temporales en el modal
        this.exportEstablecimientosCache = {};

        // Seguimiento de conflictos locales por día para evitar notificaciones repetidas
        this.conflictSignatures = {};

    // Identificadores únicos para contenedores de turnos
    this.shiftIdCounter = 0;
    this.pendingFocus = null;

        // Debounce para reordenamiento automático
        this.reorderTimeout = null;
        this.REORDER_DEBOUNCE_MS = 3000; // 3 segundos

        // Días de la semana
        this.dayNames = {
            1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves',
            5: 'Viernes', 6: 'Sábado', 7: 'Domingo'
        };

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    bindEvents() {
        // Eventos de filtros
        document.getElementById('btnBuscarEmpleado')?.addEventListener('click', () => this.searchEmployees());
        document.getElementById('btnLimpiarFiltros')?.addEventListener('click', () => this.clearFilters());

        // Eventos de modales
        document.getElementById('btnSaveSchedules')?.addEventListener('click', () => this.saveEmployeeSchedules());
        document.getElementById('btnApplyBulkSchedule')?.addEventListener('click', () => this.applyBulkSchedule());

        // Eventos de teclas para búsqueda
        document.getElementById('filtro_nombre')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevenir envío del formulario
                this.searchEmployees();
            }
        });

        // Prevenir envío del formulario de filtros
        document.getElementById('employeeFilterForm')?.addEventListener('submit', (e) => {
            e.preventDefault(); // Prevenir envío del formulario
            this.searchEmployees(); // Ejecutar búsqueda en su lugar
        });

        // Eventos de cambio en filtros de sede/establecimiento
        document.getElementById('filtro_sede')?.addEventListener('change', () => this.loadEstablishments());

        // Eventos del modal de empleados
        document.getElementById('btnSelectEmpleados')?.addEventListener('click', () => this.showEmpleadosModal());
        document.getElementById('closeSelectEmpleados')?.addEventListener('click', () => this.hideEmpleadosModal());
        document.getElementById('cancelSelectEmpleados')?.addEventListener('click', () => this.hideEmpleadosModal());
        document.getElementById('confirmSelectEmpleados')?.addEventListener('click', () => this.confirmEmpleadosSelection());
        document.getElementById('selectAllEmpleados')?.addEventListener('click', () => this.selectAllEmpleados());
        document.getElementById('deselectAllEmpleados')?.addEventListener('click', () => this.deselectAllEmpleados());
        document.getElementById('searchEmpleados')?.addEventListener('input', () => this.filterEmpleadosList());

        // Eventos del modal de empleados en exportación
        document.getElementById('btnSelectEmpleadosExport')?.addEventListener('click', () => this.showEmpleadosExportModal());
        document.getElementById('closeSelectEmpleadosExport')?.addEventListener('click', () => this.hideEmpleadosExportModal());
        document.getElementById('cancelSelectEmpleadosExport')?.addEventListener('click', () => this.hideEmpleadosExportModal());
        document.getElementById('confirmSelectEmpleadosExport')?.addEventListener('click', () => this.confirmEmpleadosExportSelection());
        document.getElementById('selectAllEmpleadosExport')?.addEventListener('click', () => this.selectAllEmpleadosExport());
        document.getElementById('deselectAllEmpleadosExport')?.addEventListener('click', () => this.deselectAllEmpleadosExport());
        document.getElementById('searchEmpleadosExport')?.addEventListener('input', () => this.filterEmpleadosExportList());

        // Evento de click fuera del modal de exportación
        document.getElementById('modalSelectEmpleadosExport')?.addEventListener('click', (e) => {
            if (e.target.id === 'modalSelectEmpleadosExport') {
                this.hideEmpleadosExportModal();
            }
        });

        // Detectar cambios no guardados
        window.addEventListener('beforeunload', (e) => {
            if (this.unsavedChanges) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
            }
        });
    }

    generateShiftClientId() {
        this.shiftIdCounter += 1;
        const randomPart = Math.random().toString(36).substring(2, 8);
        return `shift-${Date.now()}-${this.shiftIdCounter}-${randomPart}`;
    }

    ensureShiftHasClientId(shift) {
        if (!shift || typeof shift !== 'object') {
            return null;
        }

        if (!shift._clientId) {
            shift._clientId = this.generateShiftClientId();
        }

        return shift._clientId;
    }

    restoreShiftFocus(options = {}) {
        const { clientId, field, selectionStart, selectionEnd } = options;
        if (!clientId) {
            return;
        }

        requestAnimationFrame(() => {
            const card = document.querySelector(`.shift-card[data-shift-id="${clientId}"]`);
            if (!card) {
                return;
            }

            if (field) {
                const target = card.querySelector(`[data-field="${field}"]`);
                if (target && typeof target.focus === 'function') {
                    try {
                        target.focus({ preventScroll: true });
                    } catch (error) {
                        target.focus();
                    }
                    if (typeof selectionStart === 'number' && typeof selectionEnd === 'number' && typeof target.setSelectionRange === 'function') {
                        try {
                            target.setSelectionRange(selectionStart, selectionEnd);
                        } catch (error) {
                            console.warn('No se pudo restaurar la selección del campo:', error);
                        }
                    } else if (target.value && typeof target.setSelectionRange === 'function') {
                        const length = target.value.length;
                        try {
                            target.setSelectionRange(length, length);
                        } catch (error) {
                            // Ignorar errores de selección
                        }
                    }

                    target.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                    return;
                }
            }

            card.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        });
    }

    async loadInitialData() {
        try {
            await this.loadSedes();
            await this.loadStats();
            await this.loadEmployees();
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showNotification('Error cargando datos iniciales', 'error');
        }
    }

    async loadSedes() {
        try {
            const response = await fetch('api/get-sedes.php');
            const data = await response.json();
            
            if (data.success && data.data && Array.isArray(data.data)) {
                const sedeSelect = document.getElementById('filtro_sede');
                if (sedeSelect) {
                    sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';
                    data.data.forEach(sede => {
                        sedeSelect.innerHTML += `<option value="${sede.ID_SEDE || sede.id}">${sede.NOMBRE || sede.nombre}</option>`;
                    });
                }
            } else {
                console.warn('No se pudieron cargar las sedes:', data.message || 'Datos no válidos');
            }
        } catch (error) {
            console.error('Error cargando sedes:', error);
        }
    }

    async loadEstablishments() {
        const sedeId = document.getElementById('filtro_sede')?.value;
        const estSelect = document.getElementById('filtro_establecimiento');
        
        if (!estSelect) return;

        estSelect.innerHTML = '<option value="">Todos los establecimientos</option>';

        if (!sedeId) return;

        try {
            const response = await fetch(`api/get-establecimientos.php?sede_id=${sedeId}`);
            const data = await response.json();
            
            if (data.success) {
                data.establecimientos.forEach(est => {
                    estSelect.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
                });
            }
        } catch (error) {
            console.error('Error cargando establecimientos:', error);
        }
    }

    async loadStats() {
        // Only load stats if the required elements exist
        if (!document.getElementById('totalEmployees')) {
            return;
        }

        try {
            const response = await fetch('api/horarios-personalizados/stats.php');
            const data = await response.json();

            if (data.success) {
                this.updateStatsDisplay(data.stats);
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
        }
    }

    updateStatsDisplay(stats) {
        const totalEmployeesEl = document.getElementById('totalEmployees');
        const employeesWithSchedulesEl = document.getElementById('employeesWithSchedules');
        const totalSchedulesEl = document.getElementById('totalSchedules');
        const activeSchedulesEl = document.getElementById('activeSchedules');

        if (totalEmployeesEl) totalEmployeesEl.textContent = stats.general.total_empleados;
        if (employeesWithSchedulesEl) employeesWithSchedulesEl.textContent = stats.general.empleados_con_horarios;
        if (totalSchedulesEl) totalSchedulesEl.textContent = stats.general.total_turnos_configurados;
        if (activeSchedulesEl) activeSchedulesEl.textContent = stats.general.turnos_activos;
    }

    async loadEmployees(page = 1) {
        this.currentPage = page;
        
        try {
            this.showLoading();
            
            const requestData = {
                page: page,
                limit: this.currentLimit,
                ...this.currentFilters,
                _t: Date.now() // Cache buster
            };

            const response = await fetch('api/horarios-personalizados/list-employees.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            const data = await response.json();
            
            if (data.success) {
                this.renderEmployeeTable(data.data);
                this.renderPagination(data.pagination);
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error cargando empleados:', error);
            this.showNotification('Error cargando empleados', 'error');
        } finally {
            this.hideLoading();
        }
    }

    renderEmployeeTable(employees) {
        const tbody = document.getElementById('employeeTableBody');
        if (!tbody) return;

        if (employees.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-muted">
                        No se encontraron empleados con los filtros aplicados
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = employees.map(emp => {
            const estadoClass = emp.estado_horarios.tiene_activos ? 'status-active' : 
                              (emp.estado_horarios.tiene_horarios ? 'status-warning' : 'status-inactive');
            
            return `
                <tr>
                    <td>
                        <div class="employee-info">
                            <div class="employee-name">${emp.nombre_completo}</div>
                            <div class="employee-meta">${emp.establecimiento.nombre}</div>
                        </div>
                    </td>
                    <td>${emp.dni}</td>
                    <td>${emp.sede.nombre}</td>
                    <td>${emp.establecimiento.nombre}</td>
                    <td>
                        <span class="status-badge ${estadoClass}">
                            ${emp.estado_horarios.estado_texto}
                        </span>
                        <small class="d-block text-muted">
                            ${emp.horarios_info.horarios_activos} activos / ${emp.horarios_info.total_horarios} total
                        </small>
                    </td>
                    <td>
                        ${emp.horarios_info.ultima_modificacion ? 
                          new Date(emp.horarios_info.ultima_modificacion).toLocaleDateString() : 
                          'Nunca'}
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn-sm btn-primary" 
                                    onclick="horariosPersonalizados.openEmployeeScheduleModal(${emp.id_empleado})"
                                    title="Configurar horarios">
                                <i class="fas fa-clock"></i>
                            </button>
                            <button type="button" class="btn-sm btn-primary" 
                                    onclick="horariosPersonalizados.viewEmployeeSchedules(${emp.id_empleado})"
                                    title="Ver horarios">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    renderPagination(pagination) {
        const container = document.getElementById('employeePaginationControls');
        if (!container) return;

        const totalPages = pagination.total_pages;
        const currentPage = pagination.page;

        let paginationHTML = `
            <div class="pagination-info">
                Página ${currentPage} de ${totalPages} (${pagination.total_records} registros)
            </div>
            <div class="pagination-buttons">
        `;

        // Botón anterior
        if (currentPage > 1) {
            paginationHTML += `
                <button type="button" class="btn-pagination" onclick="horariosPersonalizados.loadEmployees(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;
        }

        // Páginas numeradas
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            paginationHTML += `
                <button type="button" class="btn-pagination ${activeClass}" 
                        onclick="horariosPersonalizados.loadEmployees(${i})">
                    ${i}
                </button>
            `;
        }

        // Botón siguiente
        if (currentPage < totalPages) {
            paginationHTML += `
                <button type="button" class="btn-pagination" onclick="horariosPersonalizados.loadEmployees(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }

        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    }

    searchEmployees() {
        this.currentFilters = {
            nombre: document.getElementById('filtro_nombre')?.value || '',
            sede: document.getElementById('filtro_sede')?.value || '',
            establecimiento: document.getElementById('filtro_establecimiento')?.value || '',
            estado_horario: document.getElementById('filtro_estado_horario')?.value || ''
        };

        this.loadEmployees(1);
    }

    clearFilters() {
        document.getElementById('filtro_nombre').value = '';
        document.getElementById('filtro_sede').value = '';
        document.getElementById('filtro_establecimiento').value = '';
        document.getElementById('filtro_estado_horario').value = '';
        
        this.currentFilters = {};
        this.loadEmployees(1);
    }

    toggleAllEmployees(checked) {
        document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
            const employeeId = parseInt(checkbox.value);
            if (checked) {
                this.selectedEmployees.add(employeeId);
            } else {
                this.selectedEmployees.delete(employeeId);
            }
        });
        this.updateBulkActions();
    }

    updateBulkActions() {
        const selectedCount = this.selectedEmployees.size;
        const bulkButton = document.getElementById('btnApplyBulkSchedule');
        
        if (bulkButton) {
            bulkButton.disabled = selectedCount === 0;
            bulkButton.innerHTML = selectedCount > 0 ? 
                `<i class="fas fa-check"></i> Aplicar a ${selectedCount} empleados` :
                '<i class="fas fa-check"></i> Aplicar a Empleados Seleccionados';
        }

        // Update selected employees list in bulk modal
        this.updateSelectedEmployeesList();
    }

    updateSelectedEmployeesList() {
        const container = document.getElementById('selectedEmployeesList');
        if (!container) return;

        if (this.selectedEmployees.size === 0) {
            container.innerHTML = '<p class="text-muted">Selecciona empleados de la lista principal usando los checkboxes</p>';
            return;
        }

        // Get employee data for selected IDs
        const selectedEmployeesData = [];
        document.querySelectorAll('.employee-checkbox:checked').forEach(checkbox => {
            const row = checkbox.closest('tr');
            const nameCell = row.querySelector('.employee-name');
            if (nameCell) {
                selectedEmployeesData.push({
                    id: parseInt(checkbox.value),
                    name: nameCell.textContent
                });
            }
        });

        container.innerHTML = `
            <div class="selected-employees-tags">
                ${selectedEmployeesData.map(emp => `
                    <span class="employee-tag">
                        <i class="fas fa-user"></i> ${emp.name}
                        <button type="button" class="remove-employee" onclick="horariosPersonalizados.removeFromSelection(${emp.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                `).join('')}
            </div>
        `;
    }

    removeFromSelection(employeeId) {
        this.selectedEmployees.delete(employeeId);
        const checkbox = document.querySelector(`.employee-checkbox[value="${employeeId}"]`);
        if (checkbox) checkbox.checked = false;
        this.updateBulkActions();
    }

    async openEmployeeScheduleModal(employeeId) {
        this.currentEmployeeId = employeeId;
        
        try {
            this.showLoading();
            
            const response = await fetch(`api/horarios-personalizados/get-employee-schedules.php?id_empleado=${employeeId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateEmployeeScheduleModal(data);
                
                // Cargar horarios existentes en el formulario
                await this.loadExistingSchedules(employeeId);
                
                this.showModal('employeeScheduleModal');
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error cargando horarios del empleado:', error);
            this.showNotification('Error cargando horarios del empleado', 'error');
        } finally {
            this.hideLoading();
        }
    }

    populateEmployeeScheduleModal(data) {
        const empleado = data.empleado;
        const horariosPorDia = data.horarios_por_dia;

        // Update employee info
        const employeeNameElement = document.getElementById('employeeName');
        const employeeInfoElement = document.getElementById('employeeInfo');
        
        if (employeeNameElement) {
            employeeNameElement.textContent = empleado.nombre_completo;
        }
        
        if (employeeInfoElement) {
            employeeInfoElement.innerHTML = `
                <div class="employee-detail">
                    <strong>Identificación:</strong> ${empleado.dni}
                </div>
                <div class="employee-detail">
                    <strong>Sede:</strong> ${empleado.sede.nombre}
                </div>
                <div class="employee-detail">
                    <strong>Establecimiento:</strong> ${empleado.establecimiento.nombre}
                </div>
            `;
        }

        // Store schedules data
        this.employeeSchedules = {};
        horariosPorDia.forEach(day => {
            if (!Array.isArray(day.turnos)) {
                this.employeeSchedules[day.dia_id] = [];
                return;
            }

            const preparedTurnos = day.turnos.map(turno => {
                const shift = { ...turno };
                this.ensureShiftHasClientId(shift);
                return shift;
            });

            this.employeeSchedules[day.dia_id] = preparedTurnos;
            this.sortShiftsByEntrada(day.dia_id);
        });

        // Ya no necesitamos establecer fechas globales por defecto
        // cada horario individual tiene sus propias fechas de validez

        // Switch to first day
        this.switchDay(1);
        this.unsavedChanges = false;
    }

    async viewEmployeeSchedules(employeeId) {
        try {
            this.showLoading();
            this.viewingEmployeeId = employeeId;
            
            const response = await fetch(`api/horarios-personalizados/get-employee-schedules.php?id_empleado=${employeeId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateViewEmployeeScheduleModal(data);
                this.showModal('viewEmployeeScheduleModal');
            } else {
                this.showNotification(data.message || 'Error cargando horarios', 'error');
            }
        } catch (error) {
            console.error('Error cargando horarios para visualización:', error);
            this.showNotification('Error cargando horarios del empleado', 'error');
        } finally {
            this.hideLoading();
        }
    }

    populateViewEmployeeScheduleModal(data) {
        const empleado = data.empleado;
        const horariosPorDia = data.horarios_por_dia;
        
        // Update employee info
        document.getElementById('viewEmployeeName').textContent = empleado.nombre_completo;
        document.getElementById('viewEmployeeInfo').innerHTML = `
            <div class="employee-detail">
                <strong>Identificación:</strong> ${empleado.dni}
            </div>
            <div class="employee-detail">
                <strong>Sede:</strong> ${empleado.sede.nombre}
            </div>
            <div class="employee-detail">
                <strong>Establecimiento:</strong> ${empleado.establecimiento.nombre}
            </div>
        `;

        // Calculate summary stats
        let totalSchedules = 0;
        let activeSchedules = 0;
        let configuredDays = 0;

        horariosPorDia.forEach(day => {
            if (day.turnos.length > 0) {
                configuredDays++;
                day.turnos.forEach(turno => {
                    totalSchedules++;
                    if (turno.activo === true) { // Corregir comparación booleana
                        activeSchedules++;
                    }
                });
            }
        });

        // Update summary
        document.getElementById('viewTotalSchedules').textContent = totalSchedules;
        document.getElementById('viewActiveSchedules').textContent = activeSchedules;
        document.getElementById('viewConfiguredDays').textContent = configuredDays;

        // Generate schedules content
        this.generateViewSchedulesContent(horariosPorDia);
    }

    generateViewSchedulesContent(horariosPorDia) {
        const container = document.getElementById('viewSchedulesContent');
        
        if (horariosPorDia.every(day => day.turnos.length === 0)) {
            container.innerHTML = `
                <div class="no-schedules-message">
                    <i class="fas fa-calendar-times"></i>
                    <h4>Sin horarios configurados</h4>
                    <p>Este empleado no tiene horarios personalizados configurados.</p>
                </div>
            `;
            return;
        }

        let html = '<div class="schedules-by-day">';
        
        horariosPorDia.forEach(day => {
            if (day.turnos.length > 0) {
                html += `
                    <div class="day-schedule-card">
                        <div class="day-header">
                            <h4><i class="fas fa-calendar-day"></i> ${day.dia_nombre}</h4>
                            <span class="turnos-count">${day.turnos.length} turno${day.turnos.length > 1 ? 's' : ''}</span>
                        </div>
                        <div class="turnos-list">
                `;
                
                day.turnos.forEach((turno, index) => {
                    const estadoBadge = turno.activo === true ? // Corregir comparación booleana
                        '<span class="status-badge status-active">Activo</span>' :
                        '<span class="status-badge status-inactive">Inactivo</span>';
                    
                    const vigenciaInfo = turno.fecha_hasta ? 
                        `Hasta: ${new Date(turno.fecha_hasta).toLocaleDateString()}` : 
                        'Vigencia indefinida';
                    
                    html += `
                        <div class="turno-item">
                            <div class="turno-header">
                                <h5>${turno.nombre_turno || `Turno ${index + 1}`}</h5>
                                ${estadoBadge}
                            </div>
                            <div class="turno-details">
                                <div class="time-info">
                                    <i class="fas fa-clock"></i>
                                    ${turno.hora_entrada} - ${turno.hora_salida}
                                </div>
                                <div class="tolerance-info">
                                    <i class="fas fa-hourglass-half"></i>
                                    Tolerancia: ${turno.tolerancia} min
                                </div>
                                <div class="validity-info">
                                    <i class="fas fa-calendar-check"></i>
                                    Desde: ${new Date(turno.fecha_desde).toLocaleDateString()} | ${vigenciaInfo}
                                </div>
                                ${turno.observaciones ? `
                                    <div class="observations-info">
                                        <i class="fas fa-sticky-note"></i>
                                        ${turno.observaciones}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
        });
        
        html += '</div>';
        container.innerHTML = html;
    }

    switchDay(dayId) {
        // Limpiar timeout de reordenamiento al cambiar de día
        this.clearReorderTimeout();
        
        this.currentDay = dayId;
        
        // Update day tabs
        document.querySelectorAll('.day-tab').forEach(tab => {
            tab.classList.toggle('active', parseInt(tab.dataset.day) === dayId);
        });

        // Update day title
        document.getElementById('currentDayTitle').textContent = this.dayNames[dayId];

        // Load shifts for this day
        this.loadDayShifts(dayId);
    }

    loadDayShifts(dayId, options = {}) {
        const container = document.getElementById('shiftsContainer');
        const preserveFocus = options.preserveFocus || null;

        const shifts = this.employeeSchedules[dayId] || [];

        shifts.forEach(shift => this.ensureShiftHasClientId(shift));

        if (shifts.length === 0) {
            container.innerHTML = `
                <div class="no-shifts-message">
                    <i class="fas fa-clock"></i>
                    <p>No hay turnos configurados para ${this.dayNames[dayId]}</p>
                    <button type="button" class="btn-primary btn-sm" onclick="horariosPersonalizados.addShift()">
                        <i class="fas fa-plus"></i> Agregar Primer Turno
                    </button>
                </div>
            `;
            return;
        }

        // Calcular duración y estado nocturno antes de renderizar
        shifts.forEach((_, index) => {
            this.calculateShiftDuration(dayId, index);
        });

        container.innerHTML = shifts.map((shift, index) => this.renderShiftCard(shift, index)).join('');
        
        // Agregar event listeners para cálculo de duración en tiempo real
        this.attachTimeEventListeners();
        
        // Actualizar visualización y estados nocturnos inmediatamente
        shifts.forEach((_, index) => {
            this.updateDurationDisplay(index, dayId);
            this.updateNightShiftVisualState(index, dayId);
        });
        
        // Sincronizar fechas de validez por defecto para turnos nuevos
        this.syncDefaultDates(dayId);

        // Validar superposición de horarios después de renderizar
        this.updateConflictIndicators(dayId);

        if (preserveFocus) {
            this.restoreShiftFocus(preserveFocus);
        }
    }

    renderShiftCard(shift, index) {
        const clientId = this.ensureShiftHasClientId(shift);
        const estadoClass = shift.estado === 'activo' ? 'shift-active' : 
                          (shift.estado === 'vencido' ? 'shift-expired' : 'shift-inactive');
        
        // 🌙 NUEVA LÓGICA: Detectar turno nocturno
        const esNocturno = shift.es_turno_nocturno === 'S' || shift.hora_salida < shift.hora_entrada;
        const nocturnoClass = esNocturno ? 'shift-nocturno' : '';
        const nocturnoIndicatorContent = esNocturno 
            ? '<i class="fas fa-moon text-purple"></i> <span class="badge badge-purple">Nocturno</span>'
            : '';
        
        // Determinar si es un horario existente (tiene ID) o uno nuevo
        const isExisting = shift.id_empleado_horario && shift.id_empleado_horario > 0;
        const deleteAction = isExisting ? 
            `horariosPersonalizados.deleteExistingShift(${shift.id_empleado_horario}, ${index})` :
            `horariosPersonalizados.removeShift(${index})`;
        const deleteTitle = isExisting ? 'Desactivar horario' : 'Quitar turno del formulario';
        const deleteIcon = isExisting ? 'fas fa-ban' : 'fas fa-trash';

        return `
            <div class="shift-card ${estadoClass} ${nocturnoClass}" data-shift-index="${index}" data-shift-id="${clientId}">
                <div class="shift-header">
                    <div class="shift-drag-handle shift-drag-locked" title="Ordenado automáticamente por hora de entrada">
                        <i class="fas fa-lock"></i>
                        <span class="shift-order-index">${index + 1}</span>
                    </div>
                    <div class="shift-title">
                        <input type="text" class="form-control shift-name" 
                               value="${shift.nombre_turno}" 
                               placeholder="Nombre del turno"
                               data-shift-id="${clientId}"
                               data-field="nombre_turno"
                               onchange="horariosPersonalizados.updateShiftField(${index}, 'nombre_turno', this.value)">
                        <span class="shift-night-indicator">${nocturnoIndicatorContent}</span>
                    </div>
                    <div class="shift-actions">
                        ${isExisting ? `<span class="badge badge-info">ID: ${shift.id_empleado_horario}</span>` : ''}
                        <button type="button" class="btn-sm btn-danger" 
                                onclick="${deleteAction}"
                                title="${deleteTitle}">
                            <i class="${deleteIcon}"></i>
                        </button>
                    </div>
                </div>
                <div class="shift-body">
                    <div class="shift-time-row">
                        <div class="form-group">
                            <label>Hora Entrada</label>
                <input type="time" class="form-control time-input" 
                    value="${shift.hora_entrada}"
                    data-shift-index="${index}"
                    data-shift-id="${clientId}"
                    data-field="hora_entrada">
                        </div>
                        <div class="form-group">
                            <label data-role="hora-salida-label">Hora Salida ${esNocturno ? '(día siguiente)' : ''}</label>
                <input type="time" class="form-control time-input" 
                    value="${shift.hora_salida}"
                    data-shift-index="${index}"
                    data-shift-id="${clientId}"
                    data-field="hora_salida">
                        </div>
                        <div class="form-group">
                            <label>Tolerancia (min)</label>
                            <input type="number" class="form-control" 
                                   value="${shift.tolerancia}" min="0" max="120"
                                   data-shift-id="${clientId}"
                                   data-field="tolerancia"
                                   onchange="horariosPersonalizados.updateShiftField(${index}, 'tolerancia', this.value)">
                        </div>
                        <div class="shift-duration">
                            <small class="text-muted">
                                Duración: ${shift.duracion_horas}h
                            </small>
                        </div>
                    </div>
                    <div class="shift-meta-row">
                        <div class="form-group">
                            <label>Observaciones</label>
                            <input type="text" class="form-control" 
                                   value="${shift.observaciones || ''}" 
                                   placeholder="Observaciones opcionales"
                                   data-shift-id="${clientId}"
                                   data-field="observaciones"
                                   onchange="horariosPersonalizados.updateShiftField(${index}, 'observaciones', this.value)">
                        </div>
                    </div>
                    <div class="shift-validity-row">
                        <div class="form-group">
                            <label>Válido desde</label>
                            <input type="date" class="form-control" 
                                   value="${shift.fecha_desde || new Date().toISOString().split('T')[0]}" 
                                   data-shift-id="${clientId}"
                                   data-field="fecha_desde"
                                   onchange="horariosPersonalizados.updateShiftField(${index}, 'fecha_desde', this.value)">
                        </div>
                        <div class="form-group">
                            <label>Válido hasta</label>
                            <input type="date" class="form-control" 
                                   value="${shift.fecha_hasta || ''}"
                                   data-shift-id="${clientId}"
                                   data-field="fecha_hasta"
                                   onchange="horariosPersonalizados.updateShiftField(${index}, 'fecha_hasta', this.value)">
                            <small class="form-text text-muted">Dejar vacío para vigencia indefinida</small>
                        </div>
                        <div class="shift-status">
                            <span class="status-indicator ${estadoClass}">
                                ${shift.estado}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    sortShiftsByEntrada(dayId) {
        if (!this.employeeSchedules[dayId] || !Array.isArray(this.employeeSchedules[dayId])) {
            return false;
        }

        const shifts = this.employeeSchedules[dayId];
        const previousOrder = shifts.map(shift => this.ensureShiftHasClientId(shift));

        console.log('Antes de ordenar - Día', dayId, ':', shifts.map(s => ({hora: s.hora_entrada, orden: s.orden_turno})));

        shifts
            .sort((a, b) => {
                const inicioA = this.normalizeDateForVigencia(a?.fecha_desde, Number.NEGATIVE_INFINITY);
                const inicioB = this.normalizeDateForVigencia(b?.fecha_desde, Number.NEGATIVE_INFINITY);

                if (inicioA !== inicioB) {
                    return inicioA - inicioB;
                }

                const finA = this.normalizeDateForVigencia(a?.fecha_hasta, Number.POSITIVE_INFINITY);
                const finB = this.normalizeDateForVigencia(b?.fecha_hasta, Number.POSITIVE_INFINITY);

                if (finA !== finB) {
                    return finA - finB;
                }

                // Ordenar por hora de entrada (orden lógico)
                const horaEntradaA = this.timeStringToMinutes(a?.hora_entrada);
                const horaEntradaB = this.timeStringToMinutes(b?.hora_entrada);

                if (horaEntradaA !== horaEntradaB) {
                    return horaEntradaA - horaEntradaB;
                }

                return 0;
            })
            .forEach(shift => {
                this.ensureShiftHasClientId(shift);
            });        // Recalcular orden_turno basado en el orden visual actual
        shifts.forEach((shift, index) => {
            shift.orden_turno = index + 1;
        });

        return shifts.some((shift, index) => shift._clientId !== previousOrder[index]);
    }

    renderShiftsForDay(dayId, options = {}) {
        const container = document.getElementById('shiftsContainer');
        const preserveFocus = options.preserveFocus || null;
        const shifts = this.employeeSchedules[dayId] || [];

        shifts.forEach(shift => this.ensureShiftHasClientId(shift));

        if (shifts.length === 0) {
            container.innerHTML = `
                <div class="no-shifts-message">
                    <i class="fas fa-clock"></i>
                    <p>No hay turnos configurados para ${this.dayNames[dayId]}</p>
                    <button type="button" class="btn-primary btn-sm" onclick="horariosPersonalizados.addShift()">
                        <i class="fas fa-plus"></i> Agregar Primer Turno
                    </button>
                </div>
            `;
            return;
        }

        // Calcular duración y estado nocturno antes de renderizar
        shifts.forEach((_, index) => {
            this.calculateShiftDuration(dayId, index);
        });

        container.innerHTML = shifts.map((shift, index) => this.renderShiftCard(shift, index)).join('');

        // Agregar event listeners para cálculo de duración en tiempo real
        this.attachTimeEventListeners();

        // Actualizar visualización y estados nocturnos inmediatamente
        shifts.forEach((_, index) => {
            this.updateDurationDisplay(index, dayId);
            this.updateNightShiftVisualState(index, dayId);
        });

        // Sincronizar fechas de validez por defecto para turnos nuevos
        this.syncDefaultDates(dayId);

        // Validar superposición de horarios después de renderizar
        this.updateConflictIndicators(dayId);

        if (preserveFocus) {
            this.restoreShiftFocus(preserveFocus);
        }
    }

    recalculateShiftOrder(dayId) {
        if (!this.employeeSchedules[dayId] || !Array.isArray(this.employeeSchedules[dayId])) {
            return;
        }

        const shifts = this.employeeSchedules[dayId];

        // Ordenar los turnos por hora de entrada para asignar orden lógico
        shifts.sort((a, b) => {
            const horaEntradaA = this.timeStringToMinutes(a?.hora_entrada);
            const horaEntradaB = this.timeStringToMinutes(b?.hora_entrada);
            return horaEntradaA - horaEntradaB;
        });

        // Asignar orden_turno basado en el orden por hora
        shifts.forEach((shift, index) => {
            shift.orden_turno = index + 1;
        });
    }

    timeStringToMinutes(value) {
        if (typeof value !== 'string' || value.trim() === '') {
            return Number.MAX_SAFE_INTEGER;
        }

        const [hoursStr, minutesStr] = value.split(':');
        const hours = parseInt(hoursStr, 10);
        const minutes = parseInt(minutesStr, 10);

        if (Number.isNaN(hours) || Number.isNaN(minutes)) {
            return Number.MAX_SAFE_INTEGER;
        }

        return (hours * 60) + minutes;
    }

    findShiftConflicts(dayId) {
        const shifts = this.employeeSchedules[dayId] || [];
        const eligibleShifts = shifts
            .map((shift, index) => ({ shift, index }))
            .filter(({ shift }) => this.isShiftEligibleForConflicts(shift));

        const conflictMap = new Map();
        const conflictDetails = [];

        for (let i = 0; i < eligibleShifts.length; i++) {
            const { shift: shiftA, index: indexA } = eligibleShifts[i];
            const rangeA = this.getShiftRange(shiftA);
            const vigenciaA = this.getShiftVigenciaRange(shiftA);

            if (!rangeA || !vigenciaA) {
                continue;
            }

            for (let j = i + 1; j < eligibleShifts.length; j++) {
                const { shift: shiftB, index: indexB } = eligibleShifts[j];
                const rangeB = this.getShiftRange(shiftB);
                const vigenciaB = this.getShiftVigenciaRange(shiftB);

                if (!rangeB || !vigenciaB) {
                    continue;
                }

                if (!this.vigenciasOverlap(vigenciaA, vigenciaB)) {
                    continue;
                }

                if (this.shiftRangesOverlap(rangeA, rangeB)) {
                    const detail = `"${shiftA.nombre_turno}" (${shiftA.hora_entrada} - ${shiftA.hora_salida}) ↔ "${shiftB.nombre_turno}" (${shiftB.hora_entrada} - ${shiftB.hora_salida})`;
                    conflictDetails.push(detail);

                    const existingA = conflictMap.get(indexA) || [];
                    existingA.push(detail);
                    conflictMap.set(indexA, existingA);

                    const existingB = conflictMap.get(indexB) || [];
                    existingB.push(detail);
                    conflictMap.set(indexB, existingB);
                }
            }
        }

        return { conflictMap, conflictDetails };
    }

    isShiftEligibleForConflicts(shift) {
        if (!shift) {
            return false;
        }

        if (shift.activo === false || shift.activo === 'N') {
            return false;
        }

        if (typeof shift.estado === 'string') {
            const normalized = shift.estado.toLowerCase();
            if (normalized === 'inactivo' || normalized === 'deshabilitado' || normalized === 'vencido') {
                return false;
            }
        }

        if (shift.disabled === true || shift.inactivo === true || shift.deshabilitado === true) {
            return false;
        }

        return true;
    }

    getShiftVigenciaRange(shift) {
        const start = this.normalizeDateForVigencia(shift?.fecha_desde, Number.NEGATIVE_INFINITY);
        const end = this.normalizeDateForVigencia(shift?.fecha_hasta, Number.POSITIVE_INFINITY);

        if (start > end) {
            return null;
        }

        return { start, end };
    }

    normalizeDateForVigencia(value, fallback) {
        if (value === null || value === undefined || value === '') {
            return fallback;
        }

        if (value instanceof Date && !Number.isNaN(value.getTime())) {
            const clone = new Date(value.getTime());
            clone.setHours(0, 0, 0, 0);
            return clone.getTime();
        }

        if (typeof value === 'string') {
            const date = new Date(`${value}T00:00:00`);
            if (!Number.isNaN(date.getTime())) {
                return date.getTime();
            }
        }

        const parsed = new Date(value);
        if (!Number.isNaN(parsed.getTime())) {
            return parsed.getTime();
        }

        return fallback;
    }

    vigenciasOverlap(rangeA, rangeB) {
        return rangeA.start <= rangeB.end && rangeB.start <= rangeA.end;
    }

    updateConflictIndicators(dayId) {
        const container = document.getElementById('shiftsContainer');
        if (!container) {
            return;
        }

        const { conflictMap, conflictDetails } = this.findShiftConflicts(dayId);
        const conflictIndexes = new Set(conflictMap.keys());

        container.querySelectorAll('.shift-card').forEach(card => {
            const index = parseInt(card.dataset.shiftIndex, 10);
            if (Number.isNaN(index)) {
                return;
            }

            card.classList.toggle('shift-conflict', conflictIndexes.has(index));

            const existingWarning = card.querySelector('.shift-conflict-warning');
            if (existingWarning) {
                existingWarning.remove();
            }

            if (conflictIndexes.has(index)) {
                const warning = document.createElement('div');
                warning.className = 'shift-conflict-warning';

                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-triangle';
                warning.appendChild(icon);

                const messages = conflictMap.get(index) || [];
                const content = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = 'Conflicto detectado:';
                content.appendChild(title);

                if (messages.length > 0) {
                    content.appendChild(document.createElement('br'));
                }

                messages.forEach((text, msgIndex) => {
                    const span = document.createElement('span');
                    span.textContent = text;
                    content.appendChild(span);
                    if (msgIndex < messages.length - 1) {
                        content.appendChild(document.createElement('br'));
                    }
                });

                warning.appendChild(content);

                const body = card.querySelector('.shift-body');
                if (body) {
                    body.appendChild(warning);
                }
            }
        });

        const signature = conflictDetails.length > 0 ? conflictDetails.join('|') : '';
        const previousSignature = this.conflictSignatures[dayId] || '';

        if (signature !== previousSignature) {
            this.conflictSignatures[dayId] = signature;

            if (signature) {
                const firstDetail = conflictDetails[0];
                const dayLabel = this.dayNames[dayId] || 'el día seleccionado';
                this.showNotification(`Conflicto en ${dayLabel}: ${firstDetail}`, 'warning');
            }
        }
    }

    clearConflictIndicatorsForInput(dayId, shiftIndex) {
        const shiftCard = document.querySelector(`.shift-card[data-shift-index="${shiftIndex}"]`);
        if (!shiftCard) {
            return;
        }

        shiftCard.classList.remove('shift-conflict');
        const warning = shiftCard.querySelector('.shift-conflict-warning');
        if (warning) {
            warning.remove();
        }

        // Forzar a que cualquier validación posterior pueda mostrar notificaciones nuevamente
        if (dayId !== undefined && dayId !== null) {
            this.conflictSignatures[dayId] = '';
        }
    }

    getShiftRange(shift) {
        if (!shift) {
            return null;
        }

        const start = this.timeStringToMinutes(shift.hora_entrada);
        const rawEnd = this.timeStringToMinutes(shift.hora_salida);

        if (start === Number.MAX_SAFE_INTEGER || rawEnd === Number.MAX_SAFE_INTEGER) {
            return null;
        }

        let end = rawEnd;
        if (end <= start) {
            end += 24 * 60;
        }

        return { start, end };
    }

    shiftRangesOverlap(rangeA, rangeB) {
        if (!rangeA || !rangeB) {
            return false;
        }

        return rangeA.start < rangeB.end && rangeB.start < rangeA.end;
    }

    addShift() {
        const dayId = this.currentDay;
        
        if (!this.employeeSchedules[dayId]) {
            this.employeeSchedules[dayId] = [];
        }

        const newShift = {
            id: null, // New shift
            nombre_turno: 'Nuevo Turno',
            hora_entrada: '08:00',
            hora_salida: '17:00',
            tolerancia: 15,
            orden_turno: this.employeeSchedules[dayId].length + 1,
            observaciones: '',
            estado: 'nuevo',
            duracion_horas: 9,
            fecha_desde: new Date().toISOString().split('T')[0], // Fecha por defecto: hoy
            fecha_hasta: '' // Sin fecha de fin por defecto
        };

        this.ensureShiftHasClientId(newShift);

        this.employeeSchedules[dayId].push(newShift);

        // Recalcular orden_turno para todos los turnos después de agregar el nuevo
        this.recalculateShiftOrder(dayId);

        console.log('Después de recalculateShiftOrder - Turnos:', this.employeeSchedules[dayId].map(s => ({hora: s.hora_entrada, orden: s.orden_turno})));

        this.loadDayShifts(dayId); // Esto ya llama a attachTimeEventListeners()
        this.unsavedChanges = true;
        
        // Calcular duración inicial del nuevo turno
    this.calculateShiftDuration(dayId, this.employeeSchedules[dayId].length - 1);
    this.updateDurationDisplay(this.employeeSchedules[dayId].length - 1, dayId);
    }

    updateShiftField(shiftIndex, field, value, options = {}) {
        const {
            skipSort = false,
            skipConflictUpdate = false,
            markUnsaved = true
        } = options;

        const dayId = this.currentDay;
        let resorted = false;

        const triggersSortFields = ['hora_entrada', 'fecha_desde', 'fecha_hasta'];
        const triggersConflictFields = ['hora_entrada', 'hora_salida', 'fecha_desde', 'fecha_hasta'];

        const shifts = this.employeeSchedules[dayId];
        const shift = shifts && shifts[shiftIndex] ? shifts[shiftIndex] : null;

        if (shift) {
            this.ensureShiftHasClientId(shift);
            shift[field] = value;

            const activeElement = document.activeElement;
            let preserveFocus = null;

            if (activeElement && activeElement.dataset && activeElement.dataset.shiftId) {
                preserveFocus = {
                    clientId: activeElement.dataset.shiftId,
                    field: activeElement.dataset.field || field,
                    selectionStart: typeof activeElement.selectionStart === 'number' ? activeElement.selectionStart : null,
                    selectionEnd: typeof activeElement.selectionEnd === 'number' ? activeElement.selectionEnd : null
                };
            }

            if (!preserveFocus) {
                preserveFocus = {
                    clientId: shift._clientId,
                    field
                };
            }

            // Recalculate duration if time fields changed
            if (field === 'hora_entrada' || field === 'hora_salida') {
                this.calculateShiftDuration(dayId, shiftIndex);
                // Update the duration display in real time
                this.updateDurationDisplay(shiftIndex, dayId);
                this.updateNightShiftVisualState(shiftIndex, dayId);
            }

            const shouldResort = triggersSortFields.includes(field) && !skipSort;

            if (shouldResort) {
                // Usar debounce para reordenar automáticamente después de 3 segundos
                this.scheduleReorder(dayId, shiftIndex, field, preserveFocus, markUnsaved);
                resorted = true;
                return { resorted: true };
            }            if (triggersConflictFields.includes(field) && !skipConflictUpdate) {
                this.updateConflictIndicators(dayId);
            }

            if (markUnsaved) {
                this.unsavedChanges = true;
            }
        }

        return { resorted };
    }

    // Programar reordenamiento con debounce de 3 segundos
    scheduleReorder(dayId, shiftIndex, field, preserveFocus, markUnsaved) {
        // Limpiar timeout anterior si existe
        if (this.reorderTimeout) {
            clearTimeout(this.reorderTimeout);
        }

        // Mostrar indicador visual de reordenamiento pendiente
        this.showReorderPendingIndicator();

        // Programar nuevo reordenamiento
        this.reorderTimeout = setTimeout(() => {
            this.performReorder(dayId, preserveFocus, markUnsaved);
            this.reorderTimeout = null;
            this.hideReorderPendingIndicator();
        }, this.REORDER_DEBOUNCE_MS);
    }

    // Ejecutar el reordenamiento real
    performReorder(dayId, preserveFocus, markUnsaved) {
        // Usar recalculateShiftOrder para ordenar por hora de entrada
        this.recalculateShiftOrder(dayId);

        // Re-renderizar directamente sin llamar a loadDayShifts para evitar conflictos
        this.renderShiftsForDay(dayId, { preserveFocus });

        if (markUnsaved) {
            this.unsavedChanges = true;
        }
    }

    // Mostrar indicador de reordenamiento pendiente
    showReorderPendingIndicator() {
        const container = document.getElementById('shifts-container');
        if (!container) return;

        let indicator = container.querySelector('.reorder-pending-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'reorder-pending-indicator alert alert-info';
            indicator.innerHTML = '<i class="fas fa-clock"></i> Reordenando turnos automáticamente en 3 segundos...';
            indicator.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1000; max-width: 300px;';
            container.appendChild(indicator);
        }
    }

    // Ocultar indicador de reordenamiento pendiente
    hideReorderPendingIndicator() {
        const indicator = document.querySelector('.reorder-pending-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    // Limpiar timeout de reordenamiento (llamar cuando se cambia de día o se guarda)
    clearReorderTimeout() {
        if (this.reorderTimeout) {
            clearTimeout(this.reorderTimeout);
            this.reorderTimeout = null;
            this.hideReorderPendingIndicator();
        }
    }

    // 🌙 Nueva función para manejar cambios de tiempo con validación nocturna
    updateShiftTimeField(shiftIndex, field, value) {
        this.updateShiftField(shiftIndex, field, value);
    }

    // 🌙 Mostrar advertencia de turno nocturno
    showNightShiftWarning(shiftIndex) {
        const shiftCard = document.querySelector(`[data-shift-index="${shiftIndex}"]`);
        if (!shiftCard) {
            return;
        }
        const existingWarning = shiftCard.querySelector('.night-schedule-warning');
        
        if (!existingWarning) {
            const warning = document.createElement('div');
            warning.className = 'night-schedule-warning';
            warning.innerHTML = `
                <i class="fas fa-moon"></i> 
                <strong>Turno Nocturno Detectado:</strong> 
                La salida será registrada al día siguiente. 
                <br><small>Ejemplo: entrada 18:00 (lunes) → salida 01:00 (martes)</small>
            `;
            shiftCard.querySelector('.shift-body').appendChild(warning);
        }
    }

    // 🌙 Ocultar advertencia de turno nocturno
    hideNightShiftWarning(shiftIndex) {
        const shiftCard = document.querySelector(`[data-shift-index="${shiftIndex}"]`);
        if (!shiftCard) {
            return;
        }
        const warning = shiftCard.querySelector('.night-schedule-warning');
        if (warning) {
            warning.remove();
        }
    }

    calculateShiftDuration(dayId, shiftIndex) {
        const shift = this.employeeSchedules[dayId][shiftIndex];
        
        // Validar que ambas horas estén definidas
        if (!shift.hora_entrada || !shift.hora_salida) {
            shift.duracion_horas = 0;
            shift.es_turno_nocturno = 'N';
            return;
        }
        
        try {
            const entrada = new Date(`2000-01-01 ${shift.hora_entrada}`);
            let salida = new Date(`2000-01-01 ${shift.hora_salida}`);
            
            // 🌙 NUEVA LÓGICA: Detectar turno nocturno
            const esNocturno = salida <= entrada;
            
            if (esNocturno) {
                // Si la salida es menor o igual a la entrada, es turno nocturno
                salida.setDate(salida.getDate() + 1); // Agregar 1 día a la salida
                shift.es_turno_nocturno = 'S';
            } else {
                shift.es_turno_nocturno = 'N';
            }
            
            const diffMs = salida - entrada;
            const diffHours = diffMs / (1000 * 60 * 60);
            shift.duracion_horas = Math.round(diffHours * 100) / 100;
            
            // Agregar información adicional para turnos nocturnos
            if (esNocturno) {
                shift.tipo_turno = 'Nocturno';
                shift.descripcion_nocturno = `Entrada: ${shift.hora_entrada} → Salida: ${shift.hora_salida} (día siguiente)`;
            } else {
                shift.tipo_turno = 'Diurno';
                shift.descripcion_nocturno = null;
            }
            
        } catch (error) {
            console.error('Error calculating duration:', error);
            shift.duracion_horas = 0;
            shift.es_turno_nocturno = 'N';
        }
    }

    updateDurationDisplay(shiftIndex, dayId = this.currentDay) {
        if (!this.employeeSchedules[dayId] || !this.employeeSchedules[dayId][shiftIndex]) {
            return;
        }
        const shift = this.employeeSchedules[dayId][shiftIndex];
        
        // Buscar el elemento de duración en el DOM
        const shiftCard = document.querySelector(`[data-shift-index="${shiftIndex}"]`);
        if (shiftCard) {
            const durationElement = shiftCard.querySelector('.shift-duration small');
            if (durationElement) {
                const hours = shift.duracion_horas || 0;
                const esNocturno = shift.es_turno_nocturno === 'S';
                
                let durationText = `Duración: ${hours}h`;
                let durationClass = '';
                
                // 🌙 Agregar indicador de turno nocturno
                if (esNocturno) {
                    durationText += ' <br><span class="text-purple"><i class="fas fa-moon"></i> Turno nocturno</span>';
                    durationClass = 'duration-nocturno';
                } else {
                    // Determinar clase CSS y mensaje según la duración para turnos diurnos
                    if (hours === 0) {
                        durationText += ' <span class="text-warning">(⚠️ Verificar horarios)</span>';
                        durationClass = 'duration-error';
                    } else if (hours > 12) {
                        durationText += ' <span class="text-warning">(⚠️ Más de 12h)</span>';
                        durationClass = 'duration-warning';
                    } else if (hours < 1) {
                        durationText += ' <span class="text-warning">(⚠️ Menos de 1h)</span>';
                        durationClass = 'duration-warning';
                    } else if (hours >= 6 && hours <= 10) {
                        durationClass = 'duration-normal';
                    } else {
                        durationClass = 'duration-warning';
                    }
                }
                
                // Aplicar clase CSS y contenido
                durationElement.className = durationClass;
                durationElement.innerHTML = durationText;
                
                // Agregar animación sutil
                durationElement.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    durationElement.style.transform = 'scale(1)';
                }, 200);
            }
        }
    }

    updateNightShiftVisualState(shiftIndex, dayId = this.currentDay) {
        if (!this.employeeSchedules[dayId] || !this.employeeSchedules[dayId][shiftIndex]) {
            return;
        }

        const shift = this.employeeSchedules[dayId][shiftIndex];
        const shiftCard = document.querySelector(`[data-shift-index="${shiftIndex}"]`);

        if (!shiftCard) {
            return;
        }

        const esNocturno = shift.es_turno_nocturno === 'S' || shift.hora_salida < shift.hora_entrada;

        shiftCard.classList.toggle('shift-nocturno', esNocturno);

        // Actualizar indicador nocturno en el encabezado
        const indicatorContainer = shiftCard.querySelector('.shift-night-indicator');
        if (indicatorContainer) {
            indicatorContainer.innerHTML = esNocturno
                ? '<i class="fas fa-moon text-purple"></i> <span class="badge badge-purple">Nocturno</span>'
                : '';
        }

        // Actualizar etiqueta de hora de salida
        const salidaLabel = shiftCard.querySelector('[data-role="hora-salida-label"]');
        if (salidaLabel) {
            salidaLabel.textContent = esNocturno ? 'Hora Salida (día siguiente)' : 'Hora Salida';
        }

        // Mantener advertencias nocturnas en sincronía
        if (esNocturno) {
            this.showNightShiftWarning(shiftIndex);
        } else {
            this.hideNightShiftWarning(shiftIndex);
        }
    }

    attachTimeEventListeners() {
        // Agregar event listeners para todos los campos de tiempo en tiempo real
        const timeInputs = document.querySelectorAll('.time-input');
        
        timeInputs.forEach(input => {
            if (input._previewHandler) {
                input.removeEventListener('input', input._previewHandler);
            }
            if (input._commitHandler) {
                input.removeEventListener('change', input._commitHandler);
                input.removeEventListener('blur', input._commitHandler);
            }

            const previewHandler = (event) => this.handleTimeInputPreview(event);
            const commitHandler = (event) => this.handleTimeInputCommit(event);

            input._previewHandler = previewHandler;
            input._commitHandler = commitHandler;

            input.addEventListener('input', previewHandler);
            input.addEventListener('change', commitHandler);
            input.addEventListener('blur', commitHandler);
        });
    }

    handleTimeInputPreview(event) {
        const input = event.target;
        const shiftIndex = parseInt(input.dataset.shiftIndex, 10);
        const field = input.dataset.field;
        const value = input.value;
        const dayId = this.currentDay;

        const isValidFormat = this.isValidTimeFormat(value);

        // Mientras el usuario escribe, limpiar feedback previo para evitar sacudir el input
        this.resetTimeValidationState(input, isValidFormat);

        if (!Number.isFinite(shiftIndex) || Number.isNaN(shiftIndex)) {
            return;
        }

        this.updateShiftField(shiftIndex, field, value, {
            skipSort: true,
            skipConflictUpdate: true,
            markUnsaved: false
        });

        if (isValidFormat) {
            this.validateShiftTimeLogic(shiftIndex, { suppressWarnings: true });

            const shiftCard = input.closest('.shift-card');
            if (shiftCard) {
                shiftCard.classList.add('real-time-update');
                setTimeout(() => {
                    shiftCard.classList.remove('real-time-update');
                }, 500);
            }
        } else {
            this.clearConflictIndicatorsForInput(dayId, shiftIndex);
        }
    }

    handleTimeInputCommit(event) {
        const input = event.target;
        const shiftIndex = parseInt(input.dataset.shiftIndex, 10);
        const field = input.dataset.field;
        const value = input.value;

        if (!Number.isFinite(shiftIndex) || Number.isNaN(shiftIndex)) {
            return;
        }

        const isValidFormat = this.isValidTimeFormat(value);
        this.applyTimeValidationStyle(input, isValidFormat);

        if (!isValidFormat) {
            return;
        }

        const { resorted } = this.updateShiftField(shiftIndex, field, value);

        // Para blur events (usuario hace clic fuera), cancelar reordenamiento programado
        // ya que cambió de contexto y no quiere reorganizar automáticamente
        if (event.type === 'blur' && this.reorderTimeout) {
            clearTimeout(this.reorderTimeout);
            this.reorderTimeout = null;
            this.hideReorderPendingIndicator();
            return;
        }

        // Para change events (Enter key), ejecutar reordenamiento inmediato
        if (this.reorderTimeout && event.type === 'change') {
            clearTimeout(this.reorderTimeout);
            this.reorderTimeout = null;
            // Ejecutar reordenamiento inmediato
            this.performReorder(this.currentDay, {
                clientId: this.employeeSchedules[this.currentDay][shiftIndex]._clientId,
                field
            }, true);
            return;
        }

        if (resorted) {
            return;
        }

        this.validateShiftTimeLogic(shiftIndex);
    }

    // Aplicar estilos de validación visual a campos de tiempo
    applyTimeValidationStyle(input, isValid) {
        // Remover clases anteriores
        input.classList.remove('is-valid', 'is-invalid');
        
        // Aplicar nueva clase solo si hay contenido
        if (input.value.trim() !== '') {
            if (isValid) {
                input.classList.add('is-valid');
                this.hideTimeValidationError(input);
            } else {
                input.classList.add('is-invalid');
                this.showTimeValidationError(input, 'Formato inválido. Use HH:MM (ej: 08:30)');
            }
        } else {
            // Campo vacío, remover mensajes de error
            this.hideTimeValidationError(input);
        }
    }

    resetTimeValidationState(input, isValid) {
        if (!input) {
            return;
        }

        // El usuario sigue escribiendo: limpiar estados para no forzar animaciones
        input.classList.remove('is-invalid', 'is-valid');

        if (!isValid) {
            this.hideTimeValidationError(input);
        }
    }

    isValidTimeFormat(time) {
        // Validar formato HH:MM
        const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
        return timeRegex.test(time);
    }

    validateShiftTimeLogic(shiftIndex, options = {}) {
        const { suppressWarnings = false } = options;
        const dayId = this.currentDay;
        const shift = this.employeeSchedules[dayId][shiftIndex];
        
        if (shift.hora_entrada && shift.hora_salida) {
            const entrada = new Date(`2000-01-01 ${shift.hora_entrada}`);
            const salida = new Date(`2000-01-01 ${shift.hora_salida}`);

            const entradaInput = document.querySelector(`[data-shift-index="${shiftIndex}"][data-field="hora_entrada"]`);
            const salidaInput = document.querySelector(`[data-shift-index="${shiftIndex}"][data-field="hora_salida"]`);

            // Siempre limpiar estados previos de error
            if (entradaInput) {
                entradaInput.classList.remove('is-invalid');
                this.hideTimeValidationError(entradaInput);
            }

            if (salidaInput) {
                salidaInput.classList.remove('is-invalid');
                this.hideTimeValidationError(salidaInput);
            }

            if (!suppressWarnings) {
                // Mostrar advertencia visual cuando el turno cruza medianoche
                if (salida <= entrada) {
                    this.showNightShiftWarning(shiftIndex);
                } else {
                    this.hideNightShiftWarning(shiftIndex);
                }
            }
        }
    }

    showTimeValidationError(input, message) {
        // Remover error previo
        this.hideTimeValidationError(input);
        
        // Crear elemento de error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        errorDiv.setAttribute('data-validation-error', 'true');
        
        // Insertar después del input
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }

    hideTimeValidationError(input) {
        const errorDiv = input.parentNode.querySelector('[data-validation-error="true"]');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    removeShift(shiftIndex) {
        const dayId = this.currentDay;
        
        if (confirm('¿Estás seguro de que quieres eliminar este turno?')) {
            this.employeeSchedules[dayId].splice(shiftIndex, 1);
            this.loadDayShifts(dayId);
            this.unsavedChanges = true;
        }
    }

    async deleteExistingShift(horarioId, shiftIndex) {
        if (!confirm('¿Estás seguro de que quieres desactivar este horario?\n\nEste cambio se aplicará inmediatamente en la base de datos.')) {
            return;
        }

        try {
            this.showLoading();

            const response = await fetch('api/horarios-personalizados/delete-schedules.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    horario_ids: [horarioId],
                    delete_type: 'soft' // Usar soft delete
                })
            });

            const result = await response.json();

            if (result.success) {
                // Remover del array local inmediatamente
                const dayId = this.currentDay;
                if (this.employeeSchedules[dayId] && this.employeeSchedules[dayId][shiftIndex]) {
                    this.employeeSchedules[dayId].splice(shiftIndex, 1);
                }
                
                // Recargar la vista para reflejar el cambio
                this.loadDayShifts(dayId);
                this.updateDayTabs();
                
                this.showNotification('Horario desactivado exitosamente', 'success');
            } else {
                this.showNotification(result.message || 'Error al eliminar horario', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error de conexión', 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Funciones para drag & drop
    onDragStart(event, shiftIndex) {
        event.dataTransfer.setData('text/plain', shiftIndex);
        event.target.classList.add('dragging');
    }

    onDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }

    onDrop(event, targetIndex) {
        event.preventDefault();
        
        const sourceIndex = parseInt(event.dataTransfer.getData('text/plain'));
        const dayId = this.currentDay;
        
        if (sourceIndex === targetIndex || !this.employeeSchedules[dayId]) {
            return;
        }

        // Reordenar los turnos
        const shifts = this.employeeSchedules[dayId];
        const [movedShift] = shifts.splice(sourceIndex, 1);
        shifts.splice(targetIndex, 0, movedShift);

        // Actualizar orden_turno
        shifts.forEach((shift, index) => {
            shift.orden_turno = index + 1;
        });

        // Recargar vista
        this.loadDayShifts(dayId);
        this.unsavedChanges = true;
        
        // Remover clase de arrastre
        document.querySelectorAll('.dragging').forEach(el => {
            el.classList.remove('dragging');
        });

        this.showNotification('Turnos reordenados. Recuerda guardar los cambios.', 'info');
    }

    syncInputValues() {
        // Sincronizar valores de todos los inputs time visibles con el modelo de datos
        const timeInputs = document.querySelectorAll('.time-input');
        timeInputs.forEach(input => {
            const shiftIndex = parseInt(input.dataset.shiftIndex);
            const field = input.dataset.field;
            const dayId = this.currentDay;
            
            if (this.employeeSchedules[dayId] && this.employeeSchedules[dayId][shiftIndex] && input.value) {
                this.employeeSchedules[dayId][shiftIndex][field] = input.value;
            }
        });
        
        // También sincronizar otros campos (nombres, tolerancia, observaciones)
        const otherInputs = document.querySelectorAll('[data-shift-index]:not(.time-input)');
        otherInputs.forEach(input => {
            const shiftIndex = parseInt(input.dataset.shiftIndex);
            const field = input.dataset.field;
            const dayId = this.currentDay;
            
            if (this.employeeSchedules[dayId] && this.employeeSchedules[dayId][shiftIndex] && field) {
                // Para campos de texto, sincronizar solo si hay valor o si se especifica
                if (input.value !== undefined) {
                    this.employeeSchedules[dayId][shiftIndex][field] = input.value;
                }
            }
        });
        
        // Sincronizar campos de nombre de turno específicamente
        const nameInputs = document.querySelectorAll('.shift-name');
        nameInputs.forEach(input => {
            const shiftCard = input.closest('.shift-card');
            if (shiftCard) {
                const shiftIndex = parseInt(shiftCard.dataset.shiftIndex);
                const dayId = this.currentDay;
                
                if (this.employeeSchedules[dayId] && this.employeeSchedules[dayId][shiftIndex]) {
                    this.employeeSchedules[dayId][shiftIndex]['nombre_turno'] = input.value;
                }
            }
        });
    }

    syncDefaultDates(dayId) {
        // Sincronizar fechas por defecto para turnos que no tengan fecha_desde
        const today = new Date().toISOString().split('T')[0];
        const shifts = this.employeeSchedules[dayId] || [];
        
        shifts.forEach(shift => {
            this.ensureShiftHasClientId(shift);
            if (!shift.fecha_desde) {
                shift.fecha_desde = today;
                // Actualizar el input HTML también
                const dateInput = document.querySelector(`.shift-card[data-shift-id="${shift._clientId}"] input[data-field="fecha_desde"]`);
                if (dateInput) {
                    dateInput.value = today;
                }
            }
        });
    }

    async saveEmployeeSchedules() {
        if (!this.currentEmployeeId) return;

        // Limpiar timeout de reordenamiento al guardar
        this.clearReorderTimeout();

        // Mostrar confirmación antes de guardar
        const confirmed = confirm(
            '¿Guardar horarios?\n\n¿Está seguro de que desea guardar los horarios personalizados? Se verificarán automáticamente los conflictos entre turnos.'
        );

        if (!confirmed) {
            return;
        }

        try {
            // Sincronizar todos los valores antes de guardar
            this.syncInputValues();
            
            this.showLoading();

            // Verificar que al menos uno de los horarios tenga fecha desde válida
            let hayHorariosValidos = false;
            let fechaBasePrimerHorario = null;
            const horariosParaGuardar = [];
            
            Object.keys(this.employeeSchedules).forEach(dayId => {
                this.sortShiftsByEntrada(dayId);

                this.employeeSchedules[dayId].forEach((shift, index) => {
                    // Cada horario debe tener su propia fecha desde
                    const fechaDesdeIndividual = shift.fecha_desde;
                    const fechaHastaIndividual = shift.fecha_hasta || null;
                    
                    if (!fechaDesdeIndividual) {
                        // Skip horarios sin fecha desde válida pero no detener el proceso
                        return;
                    }
                    
                    hayHorariosValidos = true;
                    
                    // Usar la primera fecha válida como fecha base para compatibilidad con API
                    if (!fechaBasePrimerHorario) {
                        fechaBasePrimerHorario = fechaDesdeIndividual;
                    }
                    
                    horariosParaGuardar.push({
                        id_dia: parseInt(dayId),
                        hora_entrada: shift.hora_entrada,
                        hora_salida: shift.hora_salida,
                        tolerancia: parseInt(shift.tolerancia),
                        nombre_turno: shift.nombre_turno,
                        orden_turno: shift.orden_turno || index + 1,
                        observaciones: shift.observaciones,
                        id_empleado_horario: shift.id_empleado_horario || null,
                        // ✨ CAMPOS NUEVOS: Vigencia individual
                        fecha_desde: fechaDesdeIndividual,
                        fecha_hasta: fechaHastaIndividual
                    });
                });
            });

            if (horariosParaGuardar.length === 0) {
                this.hideLoading();
                this.showNotification('Debe configurar al menos un turno', 'warning');
                return;
            }

            if (!hayHorariosValidos) {
                this.hideLoading();
                this.showNotification('Debe establecer una fecha desde válida para al menos un horario', 'error');
                return;
            }

            const requestData = {
                id_empleado: this.currentEmployeeId,
                fecha_desde: fechaBasePrimerHorario, // Usar la primera fecha válida para compatibilidad con API
                fecha_hasta: null, // Ya no se usa fecha global, cada horario tiene la suya
                horarios: horariosParaGuardar,
                replace_existing: true
            };

            console.log('💾 Guardando horarios con vigencia individual:', requestData);

            const response = await fetch('api/horarios-personalizados/save-employee-schedules.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Horarios guardados exitosamente', 'success');
                this.unsavedChanges = false;
                this.closeEmployeeScheduleModal();
                this.loadEmployees(this.currentPage); // Refresh the list
            } else {
                this.showNotification(data.message, 'error');
                console.error('Error del servidor:', data);
            }

        } catch (error) {
            console.error('Error guardando horarios:', error);
            this.showNotification('Error guardando horarios', 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Modal management
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Recargar empleados automáticamente al cerrar cualquier modal
            // para refrescar cualquier cambio que se haya hecho
            this.loadEmployees(this.currentPage);
        }
    }

    async loadExistingSchedules(employeeId) {
        try {
            // Ya no usamos selectores globales, cargar todos los horarios del empleado
            const today = new Date().toISOString().split('T')[0];
            
            let url = `api/horarios-personalizados/get-employee-schedules.php?id_empleado=${employeeId}`;
            // Cargar horarios desde hoy hacia adelante por defecto
            url += `&fecha_desde=${today}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.horarios_por_dia) {
                // Limpiar horarios actuales
                this.employeeSchedules = {};
                
                // Cargar horarios existentes organizados por día
                data.horarios_por_dia.forEach(dayData => {
                    const dayId = dayData.dia_id;
                    console.log('Día', dayId, 'tiene', dayData.turnos.length, 'turnos:', dayData.turnos);
                    
                    if (dayData.turnos.length > 0) {
                        // Filtrar solo horarios activos y no vencidos
                        const activeSchedules = dayData.turnos.filter(turno => 
                            turno.activo === true && turno.estado !== 'vencido'
                        );
                        
                        if (activeSchedules.length > 0) {
                            this.employeeSchedules[dayId] = activeSchedules.map(turno => {
                                const shift = {
                                id_empleado_horario: turno.id, // La API devuelve 'id', no 'id_empleado_horario'
                                id_dia: dayId,
                                hora_entrada: turno.hora_entrada || '',
                                hora_salida: turno.hora_salida || '',
                                tolerancia: turno.tolerancia || 15,
                                nombre_turno: turno.nombre_turno || 'Turno sin nombre',
                                fecha_desde: turno.fecha_desde || '',
                                fecha_hasta: turno.fecha_hasta || '',
                                orden_turno: turno.orden_turno || 1,
                                observaciones: turno.observaciones || '',
                                estado: turno.estado || (turno.activo ? 'activo' : 'inactivo'),
                                    activo: turno.activo // Preservar el campo booleano
                                };

                                this.ensureShiftHasClientId(shift);
                                return shift;
                            });
                        }
                    }
                });
                
                // Si no hay día seleccionado, seleccionar el primero con horarios
                if (!this.currentDay && Object.keys(this.employeeSchedules).length > 0) {
                    this.currentDay = Object.keys(this.employeeSchedules)[0];
                }
                
                // Actualizar la vista
                this.updateDayTabs();
                if (this.currentDay) {
                    this.loadDayShifts(this.currentDay);
                }
                
                this.showNotification(`Cargados ${Object.values(this.employeeSchedules).reduce((total, day) => total + day.length, 0)} horarios activos para edición`, 'info');
            } else {
                console.log('No schedules found or different data structure');
                // Inicializar con estructura vacía
                this.employeeSchedules = {};
                this.updateDayTabs();
            }
        } catch (error) {
            console.error('Error cargando horarios existentes:', error);
            this.showNotification('Error al cargar horarios existentes', 'error');
        }
    }

    async loadEmployeeSchedules(employeeId) {
        // Alias para loadExistingSchedules - mantener compatibilidad
        return this.loadExistingSchedules(employeeId);
    }

    closeEmployeeScheduleModal() {
        if (this.unsavedChanges) {
            if (!confirm('Tienes cambios sin guardar. ¿Estás seguro de que quieres cerrar?')) {
                return;
            }
        }
        this.hideModal('employeeScheduleModal');
        this.currentEmployeeId = null;
        this.employeeSchedules = {};
        this.unsavedChanges = false;
    }

    // Utility methods
    showLoading() {
        const tbody = document.getElementById('employeeTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="loading-text">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </td>
                </tr>
            `;
        }
    }

    hideLoading() {
        // Loading will be hidden when data is rendered
    }

    showNotification(message, type = 'info') {
        // Determinar qué contenedor usar
        let container = null;

        // Si el modal temporal está abierto, usar el contenedor del modal
        const temporalModal = document.getElementById('temporalScheduleModal');
        if (temporalModal && temporalModal.style.display === 'block') {
            container = document.getElementById('modalNotificationsContainer');
        }

        // Si no hay modal abierto o no se encontró el contenedor del modal, usar el contenedor global
        if (!container) {
            container = document.getElementById('globalNotificationsContainer');
        }

        if (!container) {
            console.warn('Notifications container not found');
            return;
        }

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.opacity = '1'; // Force initial opacity
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.prepend(notification);

        // Force visibility after a short delay to ensure animation works
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto-remove after 5 seconds
        const autoRemoveTimer = setTimeout(() => {
            this.removeNotification(notification);
        }, 5000);

        // Close button
        const closeBtn = notification.querySelector('.notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(autoRemoveTimer); // Clear auto-remove timer when manually closed
                this.removeNotification(notification);
            });
        }
    }

    removeNotification(notification) {
        if (notification && notification.parentNode) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 400); // Tiempo de la transición CSS
        }
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    async exportToExcel() {
        try {
            this.showLoading();
            
            // Obtener filtros actuales
            const filtros = this.getActiveFilters();
            const params = new URLSearchParams(filtros);
            
            const response = await fetch(`api/horarios-personalizados/export-excel.php?${params}`);
            
            if (!response.ok) {
                throw new Error('Error en la exportación');
            }
            
            // Crear un blob con el archivo Excel
            const blob = await response.blob();
            
            // Crear URL temporal para descarga
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `horarios_personalizados_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.showNotification('Archivo Excel exportado exitosamente', 'success');
            
            // Recargar la página para refrescar los datos
            setTimeout(() => {
                window.location.reload();
            }, 1000); // Pequeño delay para que se vea la notificación
            
        } catch (error) {
            console.error('Error exportando a Excel:', error);
            this.showNotification('Error al exportar archivo Excel', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async exportToExcelWithFilters(customParams) {
        try {
            this.showLoading();

            const response = await fetch(`api/horarios-personalizados/export-excel.php?${customParams}`);

            if (!response.ok) {
                throw new Error('Error en la exportación');
            }

            // Crear un blob con el archivo Excel
            const blob = await response.blob();

            // Crear URL temporal para descarga
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `horarios_personalizados_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.showNotification('Archivo Excel exportado exitosamente', 'success');

            // Recargar la página para refrescar los datos
            setTimeout(() => {
                window.location.reload();
            }, 1000); // Pequeño delay para que se vea la notificación
            
        } catch (error) {
            console.error('Error exportando a Excel:', error);
            this.showNotification('Error al exportar archivo Excel', 'error');
        } finally {
            this.hideLoading();
        }
    }

    getActiveFilters() {
        const filtros = {};

        const nombreInput = document.getElementById('filtro_nombre');
        if (nombreInput && nombreInput.value.trim()) {
            filtros.nombre = nombreInput.value.trim();
        }

        const sedeSelect = document.getElementById('filtro_sede');
        if (sedeSelect && sedeSelect.value) {
            filtros.sede = sedeSelect.value;
        }

        const establecimientoSelect = document.getElementById('filtro_establecimiento');
        if (establecimientoSelect && establecimientoSelect.value) {
            filtros.establecimiento = establecimientoSelect.value;
        }

        const estadoSelect = document.getElementById('filtro_estado_horario');
        if (estadoSelect && estadoSelect.value) {
            filtros.estado_horario = estadoSelect.value;
        }

        if (this.selectedEmployees && this.selectedEmployees.size > 0) {
            filtros.empleados = Array.from(this.selectedEmployees).join(',');
        }

        if (Array.isArray(this.selectedEmpleados) && this.selectedEmpleados.length > 0) {
            filtros.lista_empleados = this.selectedEmpleados.join(',');
        }

        return filtros;
    }

    async applyBulkSchedule() {
        try {
            // Validar que hay empleados seleccionados
            if (this.selectedEmployees.size === 0) {
                this.showNotification('Debe seleccionar al menos un empleado', 'error');
                return;
            }

            // Validar datos del formulario
            const scheduleTemplateElement = document.getElementById('scheduleTemplate');
            const fechaDesdeElement = document.getElementById('bulkFechaDesde');
            const fechaHastaElement = document.getElementById('bulkFechaHasta');
            
            const scheduleTemplate = scheduleTemplateElement ? scheduleTemplateElement.value : '';
            const fechaDesde = fechaDesdeElement ? fechaDesdeElement.value : '';
            const fechaHasta = fechaHastaElement ? fechaHastaElement.value : '';

            if (!scheduleTemplate) {
                this.showNotification('Debe seleccionar una plantilla de horario', 'error');
                return;
            }

            if (!fechaDesde) {
                this.showNotification('La fecha desde es requerida', 'error');
                return;
            }

            this.showLoading();

            // Preparar horarios según la plantilla seleccionada
            const horariosTemplate = this.getScheduleTemplate(scheduleTemplate);
            
            // Aplicar horarios a cada empleado seleccionado
            const promises = Array.from(this.selectedEmployees).map(employeeId => {
                const requestData = {
                    id_empleado: employeeId,
                    fecha_desde: fechaDesde,
                    fecha_hasta: fechaHasta || null,
                    horarios: horariosTemplate,
                    replace_existing: true
                };

                return fetch('api/horarios-personalizados/save-employee-schedules.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });
            });

            // Esperar a que se completen todas las operaciones
            const responses = await Promise.all(promises);
            const results = await Promise.all(responses.map(r => r.json()));

            // Verificar resultados
            const successful = results.filter(r => r.success).length;
            const failed = results.filter(r => !r.success).length;

            if (successful > 0) {
                this.showNotification(`Horarios aplicados exitosamente a ${successful} empleado(s)`, 'success');
                this.hideModal('bulkScheduleModal');
                this.loadEmployees(this.currentPage); // Refresh the list
                this.clearEmployeeSelection(); // Limpiar selección
            }

            if (failed > 0) {
                this.showNotification(`Falló la configuración de ${failed} empleado(s)`, 'warning');
            }

        } catch (error) {
            console.error('Error aplicando configuración masiva:', error);
            this.showNotification('Error aplicando configuración masiva', 'error');
        } finally {
            this.hideLoading();
        }
    }

    getScheduleTemplate(template) {
        const templates = {
            'standard': [
                // Lunes a Viernes: 8:00 - 17:00
                { id_dia: 1, hora_entrada: '08:00', hora_salida: '17:00', tolerancia: 15, nombre_turno: 'Turno Estándar', orden_turno: 1, observaciones: '' },
                { id_dia: 2, hora_entrada: '08:00', hora_salida: '17:00', tolerancia: 15, nombre_turno: 'Turno Estándar', orden_turno: 1, observaciones: '' },
                { id_dia: 3, hora_entrada: '08:00', hora_salida: '17:00', tolerancia: 15, nombre_turno: 'Turno Estándar', orden_turno: 1, observaciones: '' },
                { id_dia: 4, hora_entrada: '08:00', hora_salida: '17:00', tolerancia: 15, nombre_turno: 'Turno Estándar', orden_turno: 1, observaciones: '' },
                { id_dia: 5, hora_entrada: '08:00', hora_salida: '17:00', tolerancia: 15, nombre_turno: 'Turno Estándar', orden_turno: 1, observaciones: '' }
            ],
            'halfday': [
                // Lunes a Viernes: 8:00 - 13:00
                { id_dia: 1, hora_entrada: '08:00', hora_salida: '13:00', tolerancia: 15, nombre_turno: 'Medio Día', orden_turno: 1, observaciones: '' },
                { id_dia: 2, hora_entrada: '08:00', hora_salida: '13:00', tolerancia: 15, nombre_turno: 'Medio Día', orden_turno: 1, observaciones: '' },
                { id_dia: 3, hora_entrada: '08:00', hora_salida: '13:00', tolerancia: 15, nombre_turno: 'Medio Día', orden_turno: 1, observaciones: '' },
                { id_dia: 4, hora_entrada: '08:00', hora_salida: '13:00', tolerancia: 15, nombre_turno: 'Medio Día', orden_turno: 1, observaciones: '' },
                { id_dia: 5, hora_entrada: '08:00', hora_salida: '13:00', tolerancia: 15, nombre_turno: 'Medio Día', orden_turno: 1, observaciones: '' }
            ],
            'afternoon': [
                // Lunes a Viernes: 13:00 - 22:00
                { id_dia: 1, hora_entrada: '13:00', hora_salida: '22:00', tolerancia: 15, nombre_turno: 'Turno Tarde', orden_turno: 1, observaciones: '' },
                { id_dia: 2, hora_entrada: '13:00', hora_salida: '22:00', tolerancia: 15, nombre_turno: 'Turno Tarde', orden_turno: 1, observaciones: '' },
                { id_dia: 3, hora_entrada: '13:00', hora_salida: '22:00', tolerancia: 15, nombre_turno: 'Turno Tarde', orden_turno: 1, observaciones: '' },
                { id_dia: 4, hora_entrada: '13:00', hora_salida: '22:00', tolerancia: 15, nombre_turno: 'Turno Tarde', orden_turno: 1, observaciones: '' },
                { id_dia: 5, hora_entrada: '13:00', hora_salida: '22:00', tolerancia: 15, nombre_turno: 'Turno Tarde', orden_turno: 1, observaciones: '' }
            ]
        };

        return templates[template] || [];
    }

    clearEmployeeSelection() {
        const checkboxes = document.querySelectorAll('input[name="selectedEmployees"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        this.selectedEmployees.clear();
        this.updateBulkActions();
    }

    removeFromSelection(employeeId) {
        this.selectedEmployees.delete(employeeId);
        
        // Uncheck the corresponding checkbox
        const checkbox = document.querySelector(`input[name="selectedEmployees"][value="${employeeId}"]`);
        if (checkbox) {
            checkbox.checked = false;
        }
        
        this.updateBulkActions();
    }

    // Funciones del modal de empleados
    showEmpleadosModal() {
        document.getElementById('modalSelectEmpleados').style.display = 'block';
        document.getElementById('btnSelectEmpleados').classList.add('active');

        // Show loading state
        const loading = document.getElementById('empleadosLoading');
        const container = document.getElementById('empleadosListContent');
        const noResults = document.getElementById('empleadosNoResults');

        loading.style.display = 'block';
        container.style.display = 'none';
        noResults.style.display = 'none';

        // Load employees if not already loaded or if empty
        if (this.availableEmpleados.length === 0) {
            console.log('Loading empleados for modal...');
            this.loadEmpleadosForModal().then(() => {
                console.log('Empleados loaded for modal:', this.availableEmpleados.length);
            });
        } else {
            console.log('Using cached empleados:', this.availableEmpleados.length);
            this.populateEmpleadosList();
        }

        // Focus search input
        setTimeout(() => {
            document.getElementById('searchEmpleados').focus();
        }, 100);
    }

    hideEmpleadosModal() {
        document.getElementById('modalSelectEmpleados').style.display = 'none';
        document.getElementById('btnSelectEmpleados').classList.remove('active');
        document.getElementById('searchEmpleados').value = '';
        this.populateEmpleadosList(); // Reset list
    }

    async loadEmpleadosForModal() {
        const sedeId = document.getElementById('filtro_sede').value;
        const establecimientoId = document.getElementById('filtro_establecimiento').value;

        try {
            let url = 'api/horarios-personalizados/get-empleados-modal.php?';
            if (sedeId) url += `sede_id=${sedeId}&`;
            if (establecimientoId) url += `establecimiento_id=${establecimientoId}&`;

            console.log('Loading empleados from:', url);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Empleados response:', data);

            if (data.success && data.empleados) {
                this.availableEmpleados = data.empleados;
                this.originalEmpleados = [...data.empleados];

                // Filter selected employees to only include those still available
                this.selectedEmpleados = this.selectedEmpleados.filter(selectedId =>
                    this.availableEmpleados.some(emp => emp.ID_EMPLEADO == selectedId)
                );

                this.updateEmpleadosButton();
                this.populateEmpleadosList();

                console.log(`Loaded ${data.empleados.length} empleados`);
            } else {
                console.error('API error:', data);
                this.showError(data.message || 'Error al cargar empleados');
            }
        } catch (error) {
            console.error('Error loading empleados:', error);
            this.showError('Error al cargar empleados: ' + error.message);
        }
    }

    populateEmpleadosList(empleados = null) {
        const container = document.getElementById('empleadosListContent');
        const loading = document.getElementById('empleadosLoading');
        const noResults = document.getElementById('empleadosNoResults');

        const empleadosToShow = empleados || this.availableEmpleados;

        console.log('Populating list with empleados:', empleadosToShow.length);

        if (empleadosToShow.length === 0) {
            container.style.display = 'none';
            loading.style.display = 'none';
            noResults.style.display = 'block';
            this.updateSelectedCount();
            console.log('No empleados to show', empleadosToShow);
            return;
        }

        loading.style.display = 'none';
        noResults.style.display = 'none';
        container.style.display = 'block';

        container.innerHTML = empleadosToShow.map(empleado => {
            const isSelected = this.selectedEmpleados.includes(empleado.ID_EMPLEADO);
            const initials = (empleado.NOMBRE.charAt(0) + empleado.APELLIDO.charAt(0)).toUpperCase();

            return `
                <div class="empleado-item" data-id="${empleado.ID_EMPLEADO}">
                    <input type="checkbox" class="empleado-checkbox" ${isSelected ? 'checked' : ''}
                           data-id="${empleado.ID_EMPLEADO}">
                    <div class="empleado-info">
                        <div class="empleado-avatar">${initials}</div>
                        <div class="empleado-details">
                            <div class="empleado-name">${this.escapeHtml(empleado.NOMBRE + ' ' + empleado.APELLIDO)}</div>
                            <div class="empleado-meta">
                                <span>#EMP${String(empleado.ID_EMPLEADO).padStart(3, '0')}</span>
                                ${empleado.SEDE_NOMBRE ? `<span>${this.escapeHtml(empleado.SEDE_NOMBRE)}</span>` : ''}
                                ${empleado.ESTABLECIMIENTO_NOMBRE ? `<span>${this.escapeHtml(empleado.ESTABLECIMIENTO_NOMBRE)}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Bind checkbox events
        container.querySelectorAll('.empleado-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', this.onEmpleadoToggle.bind(this));
        });

        // Bind item click events
        container.querySelectorAll('.empleado-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.type !== 'checkbox') {
                    const checkbox = item.querySelector('.empleado-checkbox');
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        });

        this.updateSelectedCount();
        console.log('List populated successfully with', empleadosToShow.length, 'empleados');
    }

    onEmpleadoToggle(e) {
        const empleadoId = e.target.dataset.id;
        const isChecked = e.target.checked;

        if (isChecked) {
            if (!this.selectedEmpleados.includes(empleadoId)) {
                this.selectedEmpleados.push(empleadoId);
            }
        } else {
            this.selectedEmpleados = this.selectedEmpleados.filter(id => id !== empleadoId);
        }

        this.updateSelectedCount();
    }

    filterEmpleadosList() {
        const searchTerm = document.getElementById('searchEmpleados').value.toLowerCase();

        if (!searchTerm) {
            this.populateEmpleadosList();
            return;
        }

        const filteredEmpleados = this.availableEmpleados.filter(empleado => {
            const fullName = `${empleado.NOMBRE} ${empleado.APELLIDO}`.toLowerCase();
            const dni = empleado.DNI || '';
            const sede = empleado.SEDE_NOMBRE || '';
            const establecimiento = empleado.ESTABLECIMIENTO_NOMBRE || '';

            return fullName.includes(searchTerm) ||
                   dni.includes(searchTerm) ||
                   sede.toLowerCase().includes(searchTerm) ||
                   establecimiento.toLowerCase().includes(searchTerm);
        });

        this.populateEmpleadosList(filteredEmpleados);
    }

    selectAllEmpleados() {
        const searchTerm = document.getElementById('searchEmpleados').value.toLowerCase();
        let empleadosToSelect = this.availableEmpleados;

        if (searchTerm) {
            empleadosToSelect = this.availableEmpleados.filter(empleado => {
                const fullName = `${empleado.NOMBRE} ${empleado.APELLIDO}`.toLowerCase();
                const dni = empleado.DNI || '';
                const sede = empleado.SEDE_NOMBRE || '';
                const establecimiento = empleado.ESTABLECIMIENTO_NOMBRE || '';

                return fullName.includes(searchTerm) ||
                       dni.includes(searchTerm) ||
                       sede.toLowerCase().includes(searchTerm) ||
                       establecimiento.toLowerCase().includes(searchTerm);
            });
        }

        empleadosToSelect.forEach(empleado => {
            if (!this.selectedEmpleados.includes(empleado.ID_EMPLEADO)) {
                this.selectedEmpleados.push(empleado.ID_EMPLEADO);
            }
        });

        this.populateEmpleadosList(searchTerm ? empleadosToSelect : null);
    }

    deselectAllEmpleados() {
        const searchTerm = document.getElementById('searchEmpleados').value.toLowerCase();

        if (searchTerm) {
            const empleadosToDeselect = this.availableEmpleados.filter(empleado => {
                const fullName = `${empleado.NOMBRE} ${empleado.APELLIDO}`.toLowerCase();
                const dni = empleado.DNI || '';
                const sede = empleado.SEDE_NOMBRE || '';
                const establecimiento = empleado.ESTABLECIMIENTO_NOMBRE || '';

                return fullName.includes(searchTerm) ||
                       dni.includes(searchTerm) ||
                       sede.toLowerCase().includes(searchTerm) ||
                       establecimiento.toLowerCase().includes(searchTerm);
            });

            empleadosToDeselect.forEach(empleado => {
                this.selectedEmpleados = this.selectedEmpleados.filter(id => id !== empleado.ID_EMPLEADO);
            });

            this.populateEmpleadosList(empleadosToDeselect);
        } else {
            this.selectedEmpleados = [];
            this.populateEmpleadosList();
        }
    }

    confirmEmpleadosSelection() {
        this.updateEmpleadosButton();
        this.hideEmpleadosModal();

        // Aplicar filtros automáticamente
        this.searchEmployees();
    }

    updateEmpleadosButton() {
        const btn = document.getElementById('btnSelectEmpleados');
        const textSpan = btn.querySelector('.empleados-text');
        const countSpan = btn.querySelector('.empleados-count');

        if (this.selectedEmpleados.length === 0) {
            textSpan.textContent = 'Seleccionar empleados...';
            countSpan.classList.remove('show');
        } else {
            textSpan.textContent = `${this.selectedEmpleados.length} empleados seleccionados`;
            countSpan.textContent = this.selectedEmpleados.length;
            countSpan.classList.add('show');
        }
    }

    updateSelectedCount() {
        const countElement = document.getElementById('selectedCount');
        if (countElement) {
            countElement.textContent = `${this.selectedEmpleados.length} empleado(s) seleccionado(s)`;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Funciones para el modal de exportación
    async loadExportFilters() {
        try {
            await this.loadExportSedes();
        } catch (error) {
            console.error('Error cargando filtros de exportación:', error);
        }
    }

    async loadExportSedes() {
        try {
            const response = await fetch('api/get-sedes.php');
            const data = await response.json();

            if (data.success) {
                const sedeSelect = document.getElementById('export_sede');
                sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';

                data.sedes.forEach(sede => {
                    const option = document.createElement('option');
                    option.value = sede.ID_SEDE;
                    option.textContent = sede.NOMBRE;
                    sedeSelect.appendChild(option);
                });

                // Evento para cargar establecimientos cuando cambia la sede
                sedeSelect.onchange = () => this.loadExportEstablishments();
                this.loadExportEstablishments();
            }
        } catch (error) {
            console.error('Error cargando sedes:', error);
        }
    }

    async loadExportEstablishments() {
        const sedeSelect = document.getElementById('export_sede');
        const establecimientoSelect = document.getElementById('export_establecimiento');

        if (!establecimientoSelect) {
            return;
        }

        establecimientoSelect.innerHTML = '<option value="">Todos los establecimientos</option>';

        const sedeId = sedeSelect ? sedeSelect.value : '';
        if (!sedeId) {
            return;
        }

        const cacheKey = String(sedeId);

        const renderOptions = (establecimientos) => {
            establecimientos.forEach(establecimiento => {
                const option = document.createElement('option');
                option.value = establecimiento.ID_ESTABLECIMIENTO;
                option.textContent = establecimiento.NOMBRE;
                establecimientoSelect.appendChild(option);
            });
        };

        if (this.exportEstablecimientosCache[cacheKey]) {
            renderOptions(this.exportEstablecimientosCache[cacheKey]);
            return;
        }

        try {
            const response = await fetch(`api/get-establecimientos.php?sede_id=${encodeURIComponent(sedeId)}`);
            const data = await response.json();

            if (data.success && Array.isArray(data.establecimientos)) {
                this.exportEstablecimientosCache[cacheKey] = data.establecimientos;
                renderOptions(data.establecimientos);
            }
        } catch (error) {
            console.error('Error cargando establecimientos:', error);
        }
    }

    // Funciones del modal de empleados en exportación
    showEmpleadosExportModal() {
        const modal = document.getElementById('modalSelectEmpleadosExport');
        modal.style.display = 'block';
        document.getElementById('btnSelectEmpleadosExport').classList.add('active');

        // Inicializar selección temporal con la selección actual
        this.tempEmpleadosExport = [...this.selectedEmpleadosExport];

        // Show loading state
        const loading = document.getElementById('empleadosExportLoading');
        const container = document.getElementById('empleadosExportListContent');
        const noResults = document.getElementById('empleadosExportNoResults');

        loading.style.display = 'block';
        container.style.display = 'none';
        noResults.style.display = 'none';

        // Load employees if not already loaded or if empty
        if (this.availableEmpleadosExport.length === 0) {
            console.log('Loading empleados for export modal...');
            this.loadEmpleadosForExportModal().then(() => {
                console.log('Empleados loaded for export modal:', this.availableEmpleadosExport.length);
            });
        } else {
            console.log('Using cached empleados for export:', this.availableEmpleadosExport.length);
            this.populateEmpleadosExportList();
        }
    }

    hideEmpleadosExportModal() {
        document.getElementById('modalSelectEmpleadosExport').style.display = 'none';
        document.getElementById('btnSelectEmpleadosExport').classList.remove('active');
    }

    async loadEmpleadosForExportModal() {
        const sedeId = document.getElementById('export_sede').value;
        const establecimientoId = document.getElementById('export_establecimiento').value;

        try {
            let url = 'api/horarios-personalizados/get-empleados-modal.php?';
            if (sedeId) url += `sede_id=${sedeId}&`;
            if (establecimientoId) url += `establecimiento_id=${establecimientoId}&`;

            console.log('Loading empleados from:', url);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Empleados export response:', data);

            if (data.success && data.empleados) {
                this.availableEmpleadosExport = data.empleados;
                this.originalEmpleadosExport = [...data.empleados];

                // Filter selected employees to only include those still available
                this.selectedEmpleadosExport = this.selectedEmpleadosExport.filter(selectedId =>
                    this.availableEmpleadosExport.some(emp => emp.ID_EMPLEADO == selectedId)
                );

                this.updateEmpleadosExportButton();
                this.populateEmpleadosExportList();
                this.updateSelectedCountExport();

                console.log(`Loaded ${data.empleados.length} empleados for export`);
            } else {
                console.error('API error:', data);
                this.showError(data.message || 'Error al cargar empleados');
            }
        } catch (error) {
            console.error('Error loading empleados for export:', error);
            this.showError('Error al cargar empleados: ' + error.message);
        }
    }

    populateEmpleadosExportList(empleados = null) {
        const empleadosToShow = empleados || this.availableEmpleadosExport;
        const container = document.getElementById('empleadosExportListContent');
        const loading = document.getElementById('empleadosExportLoading');
        const noResults = document.getElementById('empleadosExportNoResults');

        loading.style.display = 'none';

        if (empleadosToShow.length === 0) {
            container.style.display = 'none';
            noResults.style.display = 'block';
            return;
        }

        container.style.display = 'block';
        noResults.style.display = 'none';

        container.innerHTML = '';

        empleadosToShow.forEach(empleado => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'empleado-item';
            itemDiv.innerHTML = `
                <input type="checkbox"
                       class="empleado-checkbox"
                       value="${empleado.ID_EMPLEADO}"
                       ${this.tempEmpleadosExport.includes(empleado.ID_EMPLEADO) ? 'checked' : ''}>
                <div class="empleado-info">
                    <div class="empleado-avatar">
                        ${this.escapeHtml(empleado.NOMBRE.charAt(0) + empleado.APELLIDO.charAt(0)).toUpperCase()}
                    </div>
                    <div class="empleado-details">
                        <div class="empleado-name">${this.escapeHtml(empleado.NOMBRE + ' ' + empleado.APELLIDO)}</div>
                        <div class="empleado-meta">
                            <span>ID: ${empleado.ID_EMPLEADO}</span>
                            <span>${this.escapeHtml(empleado.SEDE_NOMBRE || 'Sin sede')}</span>
                            <span>${this.escapeHtml(empleado.ESTABLECIMIENTO_NOMBRE || 'Sin establecimiento')}</span>
                        </div>
                    </div>
                </div>
            `;

            // Event listener for checkbox
            const checkbox = itemDiv.querySelector('.empleado-checkbox');
            checkbox.addEventListener('change', () => this.toggleEmpleadoExportSelection(empleado.ID_EMPLEADO));

            // Event listener for clicking on the entire item (except checkbox)
            itemDiv.addEventListener('click', (e) => {
                // Don't trigger if clicking on checkbox itself
                if (e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    this.toggleEmpleadoExportSelection(empleado.ID_EMPLEADO);
                }
            });

            container.appendChild(itemDiv);
        });

        this.updateSelectedCountExport();
    }

    toggleEmpleadoExportSelection(empleadoId) {
        const index = this.tempEmpleadosExport.indexOf(empleadoId);
        if (index > -1) {
            this.tempEmpleadosExport.splice(index, 1);
        } else {
            this.tempEmpleadosExport.push(empleadoId);
        }
        this.updateSelectedCountExport();
    }

    selectAllEmpleadosExport() {
        this.tempEmpleadosExport = this.availableEmpleadosExport.map(emp => emp.ID_EMPLEADO);
        this.populateEmpleadosExportList();
    }

    deselectAllEmpleadosExport() {
        this.tempEmpleadosExport = [];
        this.populateEmpleadosExportList();
    }

    filterEmpleadosExportList() {
        const searchTerm = document.getElementById('searchEmpleadosExport').value.toLowerCase();

        if (!searchTerm) {
            this.populateEmpleadosExportList(this.availableEmpleadosExport);
            return;
        }

        const filtered = this.availableEmpleadosExport.filter(empleado =>
            empleado.NOMBRE.toLowerCase().includes(searchTerm) ||
            empleado.APELLIDO.toLowerCase().includes(searchTerm) ||
            empleado.DNI.toLowerCase().includes(searchTerm) ||
            empleado.ID_EMPLEADO.toString().includes(searchTerm)
        );

        this.populateEmpleadosExportList(filtered);
    }

    confirmEmpleadosExportSelection() {
        // Aplicar los cambios temporales a la selección definitiva
        this.selectedEmpleadosExport = [...this.tempEmpleadosExport];
        this.updateEmpleadosExportButton();
        this.hideEmpleadosExportModal();
    }

    updateEmpleadosExportButton() {
        const btn = document.getElementById('btnSelectEmpleadosExport');
        const textSpan = btn.querySelector('.empleados-text');
        const countSpan = btn.querySelector('.empleados-count');

        if (this.selectedEmpleadosExport.length === 0) {
            textSpan.textContent = 'Seleccionar empleados...';
            countSpan.classList.remove('show');
        } else {
            textSpan.textContent = `${this.selectedEmpleadosExport.length} empleados seleccionados`;
            countSpan.textContent = this.selectedEmpleadosExport.length;
            countSpan.classList.add('show');
        }
    }

    updateSelectedCountExport() {
        const countElement = document.getElementById('selectedCountExport');
        if (countElement) {
            countElement.textContent = `${this.tempEmpleadosExport.length} empleado(s) seleccionado(s)`;
        }
    }

    // Función para mostrar notificaciones
    showNotification(message, type = 'info') {
        // Buscar contenedor de notificaciones en el modal temporal primero
        let container = document.querySelector('.modal-temporal-schedule .modal-notifications-container');
        if (!container) {
            container = document.querySelector('.modal-notifications-container');
        }
        if (!container) {
            container = document.querySelector('.notification-container');
        }
        if (!container) {
            // Crear contenedor si no existe
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        const icon = type === 'success' ? 'check-circle' :
                    type === 'error' ? 'exclamation-circle' :
                    type === 'warning' ? 'exclamation-triangle' : 'info-circle';

        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.prepend(notification);

        // Agregar clase 'show' para animación
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }
}

// Initialize when DOM is loaded - only on horarios-personalizados pages
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the horarios-personalizados page
    if (document.getElementById('totalEmployees') || window.location.pathname.includes('horarios-personalizados')) {
        window.horariosPersonalizados = new HorariosPersonalizados();
    }
});

// Global functions for inline event handlers
function openEmployeeScheduleModal(employeeId) {
    window.horariosPersonalizados.openEmployeeScheduleModal(employeeId);
}

function closeEmployeeScheduleModal() {
    window.horariosPersonalizados.closeEmployeeScheduleModal();
}

function switchDay(dayId) {
    window.horariosPersonalizados.switchDay(dayId);
}

function addShift() {
    window.horariosPersonalizados.addShift();
}

function showHelpModal() {
    window.horariosPersonalizados.showModal('helpModal');
}

function switchHelpTab(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.help-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Add active class to clicked tab
    event.target.classList.add('active');

    // Hide all help content
    document.querySelectorAll('.help-content').forEach(content => {
        content.classList.remove('active');
    });

    // Show selected content
    document.getElementById('help-' + tabName).classList.add('active');
}

// Temporal schedule modal functions
let selectedEmpleadosTemporal = [];
let temporalModalEmployees = [];

function openTemporalScheduleModal() {
    selectedEmpleadosTemporal = [];
    temporalModalEmployees = [];
    document.getElementById('temporalScheduleModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    const searchInput = document.getElementById('searchEmpleadosTemporal');
    if (searchInput) {
        searchInput.value = '';
    }
    loadEmpleadosForTemporalModal();
}

function closeTemporalScheduleModal() {
    document.getElementById('temporalScheduleModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Limpiar selecciones
    selectedEmpleadosTemporal = [];
    temporalModalEmployees = [];
    updateTemporalSelectedCount();
    updateTemporalScheduleConfigVisibility();
}

function loadEmpleadosForTemporalModal() {
    const loading = document.getElementById('empleadosTemporalLoading');
    const list = document.getElementById('empleadosTemporalListContent');
    const noResults = document.getElementById('empleadosTemporalNoResults');

    loading.style.display = 'block';
    list.style.display = 'none';
    noResults.style.display = 'none';

    fetch('api/horarios-personalizados/list-employees.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            page: 1,
            limit: 1000,
            _t: Date.now()
        })
    })
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';

        if (data.success && data.empleados && data.empleados.length > 0) {
            populateEmpleadosTemporalList(data.empleados);
        } else {
            list.style.display = 'none';
            noResults.style.display = 'block';
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        noResults.style.display = 'block';
        console.error('Error loading employees for temporal modal:', error);
    });
}

function populateEmpleadosTemporalList(empleados) {
    // Función local para escapar HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    temporalModalEmployees = Array.isArray(empleados) ? empleados : [];

    renderTemporalEmployeesList(temporalModalEmployees, escapeHtml);
}

function renderTemporalEmployeesList(empleados, escapeHtmlFn) {
    const container = document.getElementById('empleadosTemporalListContent');
    const noResults = document.getElementById('empleadosTemporalNoResults');

    if (!container || !noResults) {
        return;
    }

    const escapeHtml = typeof escapeHtmlFn === 'function' ? escapeHtmlFn : (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    container.innerHTML = '';

    if (!empleados || empleados.length === 0) {
        container.style.display = 'none';
        noResults.style.display = 'block';
        return;
    }

    empleados.forEach(empleado => {
        const empleadoDiv = document.createElement('div');
        empleadoDiv.className = 'empleado-item';

        const isSelected = selectedEmpleadosTemporal.includes(empleado.ID_EMPLEADO);
        const nombreCompleto = `${empleado.NOMBRE || ''} ${empleado.APELLIDO || ''}`.trim();

        empleadoDiv.innerHTML = `
            <input type="checkbox"
                   class="empleado-checkbox"
                   value="${empleado.ID_EMPLEADO}"
                   ${isSelected ? 'checked' : ''}>
            <div class="empleado-info">
                <div class="empleado-details">
                    <div class="empleado-name">${escapeHtml(nombreCompleto)}</div>
                    <div class="empleado-meta">
                        <span>ID: ${empleado.ID_EMPLEADO}</span>
                        <span>${escapeHtml(empleado.SEDE_NOMBRE || 'Sin sede')}</span>
                        <span>${escapeHtml(empleado.ESTABLECIMIENTO_NOMBRE || 'Sin establecimiento')}</span>
                    </div>
                </div>
            </div>
        `;

        const checkbox = empleadoDiv.querySelector('.empleado-checkbox');
        checkbox.addEventListener('change', () => toggleEmpleadoTemporalSelection(empleado.ID_EMPLEADO));

        empleadoDiv.addEventListener('click', (e) => {
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
                toggleEmpleadoTemporalSelection(empleado.ID_EMPLEADO);
            }
        });

        container.appendChild(empleadoDiv);
    });

    container.style.display = 'block';
    noResults.style.display = 'none';
}

function toggleEmpleadoTemporalSelection(empleadoId) {
    const index = selectedEmpleadosTemporal.indexOf(empleadoId);

    if (index > -1) {
        selectedEmpleadosTemporal.splice(index, 1);
    } else {
        selectedEmpleadosTemporal.push(empleadoId);
    }

    if (window.horariosPersonalizados) {
        window.horariosPersonalizados.selectedEmpleadosTemporal = selectedEmpleadosTemporal;
    }

    updateTemporalSelectedCount();
    updateTemporalScheduleConfigVisibility();
}

function selectAllEmpleadosTemporal() {
    const checkboxes = document.querySelectorAll('#empleadosTemporalListContent input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            toggleEmpleadoTemporalSelection(parseInt(checkbox.value));
        }
    });
}

function deselectAllEmpleadosTemporal() {
    const checkboxes = document.querySelectorAll('#empleadosTemporalListContent input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            toggleEmpleadoTemporalSelection(parseInt(checkbox.value));
        }
    });
}

function updateTemporalSelectedCount() {
    const count = selectedEmpleadosTemporal.length;
    document.getElementById('selectedCountTemporal').textContent = `${count} empleado(s) seleccionado(s)`;
}

function updateTemporalScheduleConfigVisibility() {
    const configDiv = document.getElementById('temporalScheduleConfig');
    const confirmBtn = document.getElementById('confirmTemporalSchedule');
    const stepContainer = document.querySelector('.temporal-step-1');
    const empleadosSection = document.querySelector('.empleados-section');
    const scheduleSection = document.querySelector('.schedule-config-section');

    if (selectedEmpleadosTemporal.length > 0) {
        configDiv.style.display = 'block';
        confirmBtn.disabled = false;

        // Aplicar clase para layout de dos columnas
        stepContainer.classList.remove('no-selection');
        stepContainer.classList.add('has-selection');

        // Mostrar sección de configuración
        if (scheduleSection) {
            scheduleSection.style.display = 'flex';
        }
    } else {
        configDiv.style.display = 'none';
        confirmBtn.disabled = true;

        // Aplicar clase para layout de una columna
        stepContainer.classList.remove('has-selection');
        stepContainer.classList.add('no-selection');

        // Ocultar sección de configuración
        if (scheduleSection) {
            scheduleSection.style.display = 'none';
        }
    }
}

function filterEmpleadosTemporalList() {
    const searchInput = document.getElementById('searchEmpleadosTemporal');
    if (!searchInput) {
        return;
    }

    const normalizeText = (text) => (text || '')
        .toString()
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const searchTerm = normalizeText(searchInput.value);

    if (!temporalModalEmployees || temporalModalEmployees.length === 0) {
        renderTemporalEmployeesList([]);
        return;
    }

    if (!searchTerm) {
        renderTemporalEmployeesList(temporalModalEmployees);
        return;
    }

    const filteredEmployees = temporalModalEmployees.filter(empleado => {
        const nombreCompleto = normalizeText(`${empleado.NOMBRE || ''} ${empleado.APELLIDO || ''}`);
        const identificador = normalizeText(`${empleado.ID_EMPLEADO || ''}`);
        const sede = normalizeText(empleado.SEDE_NOMBRE);
        const establecimiento = normalizeText(empleado.ESTABLECIMIENTO_NOMBRE);

        return nombreCompleto.includes(searchTerm) ||
            identificador.includes(searchTerm) ||
            sede.includes(searchTerm) ||
            establecimiento.includes(searchTerm);
    });

    renderTemporalEmployeesList(filteredEmployees);
}

function createTemporalSchedule() {
    const selectedEmpleados = selectedEmpleadosTemporal;
    const fecha = document.getElementById('temporal_fecha').value;
    const horaEntrada = document.getElementById('temporal_hora_entrada').value;
    const horaSalida = document.getElementById('temporal_hora_salida').value;
    const tolerancia = document.getElementById('temporal_tolerancia').value;
    const observaciones = document.getElementById('temporal_observaciones').value;

    // Validation
    if (selectedEmpleados.length === 0) {
        window.horariosPersonalizados.showNotification('Debe seleccionar al menos un empleado', 'error');
        return;
    }

    if (!fecha) {
        window.horariosPersonalizados.showNotification('Debe seleccionar una fecha', 'error');
        return;
    }

    if (!horaEntrada || !horaSalida) {
        window.horariosPersonalizados.showNotification('Debe especificar hora de entrada y salida', 'error');
        return;
    }

    // Show loading
    const confirmBtn = document.getElementById('confirmTemporalSchedule');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
    confirmBtn.disabled = true;

    // Prepare data
    const scheduleData = {
        empleados: selectedEmpleados,
        fecha: fecha,
        hora_entrada: horaEntrada,
        hora_salida: horaSalida,
        tolerancia: parseInt(tolerancia) || 15,
        observaciones: observaciones,
        es_temporal: 'S'
    };

    // Send to API
    fetch('api/horarios-personalizados/create-temporal-schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(scheduleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeTemporalScheduleModal();
            // Mostrar notificación después de que el modal se cierre
            setTimeout(() => {
                window.horariosPersonalizados.showNotification(`Horario temporal creado exitosamente para ${selectedEmpleados.length} empleado(s)`, 'success');
            }, 300);
            // Reload the schedules list if needed
            if (window.horariosPersonalizados && window.horariosPersonalizados.loadEmployeeSchedules) {
                window.horariosPersonalizados.loadEmployeeSchedules();
            }
        } else {
            window.horariosPersonalizados.showNotification(data.message || 'Error al crear el horario temporal', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating temporal schedule:', error);
        window.horariosPersonalizados.showNotification('Error de conexión al crear el horario temporal', 'error');
    })
    .finally(() => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

function closeHelpModal() {
    window.horariosPersonalizados.hideModal('helpModal');
}

function showBulkScheduleModal() {
    window.horariosPersonalizados.showModal('bulkScheduleModal');
}

function closeBulkScheduleModal() {
    window.horariosPersonalizados.hideModal('bulkScheduleModal');
}

function exportPersonalizedSchedules() {
    window.horariosPersonalizados.exportToExcel();
}

// Funciones para el modal de exportación
function openExportModal() {
    // Cargar filtros de sede y establecimiento
    if (window.horariosPersonalizados) {
        window.horariosPersonalizados.loadExportFilters();
    }

    // Establecer fechas por defecto (día actual)
    const today = new Date().toISOString().split('T')[0];
    const fechaDesdeInput = document.getElementById('export_fecha_vigencia_desde');
    const fechaHastaInput = document.getElementById('export_fecha_vigencia_hasta');
    
    if (fechaDesdeInput) fechaDesdeInput.value = today;
    if (fechaHastaInput) fechaHastaInput.value = today;

    // Mostrar modal
    const modal = document.getElementById('exportModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Agregar event listener para cerrar al hacer click en el backdrop
        modal.onclick = function(event) {
            if (event.target === modal) {
                closeExportModal();
            }
        };
    }
}

function closeExportModal() {
    const modal = document.getElementById('exportModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

async function loadExportEmployees() {
    try {
        const select = document.getElementById('export_empleados');
        if (!select) return;

        select.innerHTML = '<option value="">Cargando empleados...</option>';

        // Obtener empleados con filtros actuales
        const filtros = window.horariosPersonalizados.getActiveFilters();
        const params = new URLSearchParams(filtros);

        const response = await fetch(`api/horarios-personalizados/get-empleados-export.php?${params}`);
        const data = await response.json();

        if (data.success && data.empleados) {
            select.innerHTML = '<option value="">Seleccionar empleados...</option>';

            data.empleados.forEach(empleado => {
                const option = document.createElement('option');
                option.value = empleado.ID_EMPLEADO;
                option.textContent = `${empleado.nombre_completo} - ${empleado.DNI}`;
                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">Error cargando empleados</option>';
        }
    } catch (error) {
        console.error('Error cargando empleados para exportación:', error);
        const select = document.getElementById('export_empleados');
        if (select) {
            select.innerHTML = '<option value="">Error cargando empleados</option>';
        }
    }
}

function confirmExport() {
    // Obtener filtros del modal
    const fechaDesde = document.getElementById('export_fecha_vigencia_desde')?.value;
    const fechaHasta = document.getElementById('export_fecha_vigencia_hasta')?.value;
    const sedeId = document.getElementById('export_sede')?.value;
    const establecimientoId = document.getElementById('export_establecimiento')?.value;
    const incluirInactivos = document.getElementById('export_incluir_inactivos')?.checked;

    // VALIDACIÓN OBLIGATORIA: Las fechas de vigencia son requeridas
    if (!fechaDesde || !fechaHasta) {
        window.horariosPersonalizados.showNotification('Las fechas de vigencia son obligatorias', 'error');
        return;
    }

    // Validar que la fecha desde no sea mayor que la fecha hasta
    if (fechaDesde && fechaHasta && new Date(fechaDesde) > new Date(fechaHasta)) {
        window.horariosPersonalizados.showNotification('La fecha desde no puede ser mayor que la fecha hasta', 'error');
        return;
    }

    // Construir parámetros
    const params = new URLSearchParams();

    if (fechaDesde) params.append('fecha_vigencia_desde', fechaDesde);
    if (fechaHasta) params.append('fecha_vigencia_hasta', fechaHasta);
    if (sedeId) params.append('sede', sedeId);
    if (establecimientoId) params.append('establecimiento', establecimientoId);

    // Empleados seleccionados del modal
    if (window.horariosPersonalizados && window.horariosPersonalizados.selectedEmpleadosExport.length > 0) {
        window.horariosPersonalizados.selectedEmpleadosExport.forEach(empleadoId => {
            params.append('empleados[]', empleadoId);
        });
    }

    // Opciones adicionales
    if (!incluirInactivos) params.append('solo_activos', '1');

    // Cerrar modal
    closeExportModal();

    // Ejecutar exportación
    window.horariosPersonalizados.exportToExcelWithFilters(params);
}

// Funciones para el modal de horario temporal
function openTemporalScheduleModal() {
    document.getElementById('temporalScheduleModal').style.display = 'block';

    selectedEmpleadosTemporal = [];
    temporalModalEmployees = [];
    if (window.horariosPersonalizados) {
        window.horariosPersonalizados.selectedEmpleadosTemporal = selectedEmpleadosTemporal;
    }

    const configSection = document.getElementById('temporalScheduleConfig');
    const confirmBtn = document.getElementById('confirmTemporalSchedule');
    const selectedCountLabel = document.getElementById('selectedCountTemporal');
    const searchInput = document.getElementById('searchEmpleadosTemporal');

    if (configSection) {
        configSection.style.display = 'none';
    }
    if (confirmBtn) {
        confirmBtn.disabled = true;
    }
    if (selectedCountLabel) {
        selectedCountLabel.textContent = '0 empleado(s) seleccionado(s)';
    }
    if (searchInput) {
        searchInput.value = '';
    }

    const stepContainer = document.querySelector('.temporal-step-1');
    if (stepContainer) {
        stepContainer.classList.remove('has-selection');
        stepContainer.classList.add('no-selection');
    }

    const fechaField = document.getElementById('temporal_fecha');
    const entradaField = document.getElementById('temporal_hora_entrada');
    const salidaField = document.getElementById('temporal_hora_salida');
    const toleranciaField = document.getElementById('temporal_tolerancia');
    const observacionesField = document.getElementById('temporal_observaciones');

    if (fechaField) fechaField.value = '';
    if (entradaField) entradaField.value = '';
    if (salidaField) salidaField.value = '';
    if (toleranciaField) toleranciaField.value = '15';
    if (observacionesField) observacionesField.value = '';

    loadEmpleadosForTemporalModal();

    setTimeout(() => {
        searchInput?.focus();
    }, 100);
}

function closeTemporalScheduleModal() {
    document.getElementById('temporalScheduleModal').style.display = 'none';
    const searchInput = document.getElementById('searchEmpleadosTemporal');
    if (searchInput) {
        searchInput.value = '';
    }

    selectedEmpleadosTemporal = [];
    temporalModalEmployees = [];
    if (window.horariosPersonalizados) {
        window.horariosPersonalizados.selectedEmpleadosTemporal = selectedEmpleadosTemporal;
    }

    updateTemporalSelectedCount();
    updateTemporalScheduleConfigVisibility();
}

function loadEmpleadosForTemporalModal() {
    const loading = document.getElementById('empleadosTemporalLoading');
    const container = document.getElementById('empleadosTemporalListContent');
    const noResults = document.getElementById('empleadosTemporalNoResults');

    loading.style.display = 'block';
    container.style.display = 'none';
    noResults.style.display = 'none';

    // Load employees from API
    fetch('api/horarios-personalizados/get-empleados-modal.php')
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';

            if (data.success && data.empleados) {
                populateEmpleadosTemporalList(data.empleados);
            } else {
                noResults.style.display = 'block';
                console.error('Error loading employees:', data);
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            noResults.style.display = 'block';
            console.error('Error loading employees for temporal modal:', error);
        });
}

function populateEmpleadosTemporalList(empleados) {
    temporalModalEmployees = Array.isArray(empleados) ? empleados : [];
    renderTemporalEmployeesList(temporalModalEmployees);
}

function renderTemporalEmployeesList(empleados) {
    const container = document.getElementById('empleadosTemporalListContent');
    const noResults = document.getElementById('empleadosTemporalNoResults');

    if (!container || !noResults) {
        return;
    }

    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    container.innerHTML = '';

    if (!empleados || empleados.length === 0) {
        container.style.display = 'none';
        noResults.style.display = 'block';
        return;
    }

    empleados.forEach(empleado => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'empleado-item';

        const isSelected = selectedEmpleadosTemporal.includes(empleado.ID_EMPLEADO);
        const nombreCompleto = `${empleado.NOMBRE || ''} ${empleado.APELLIDO || ''}`.trim();

        itemDiv.innerHTML = `
            <input type="checkbox"
                   class="empleado-checkbox"
                   value="${empleado.ID_EMPLEADO}"
                   ${isSelected ? 'checked' : ''}>
            <div class="empleado-info">
                <div class="empleado-details">
                    <div class="empleado-name">${escapeHtml(nombreCompleto)}</div>
                    <div class="empleado-meta">
                        <span>ID: ${empleado.ID_EMPLEADO}</span>
                        <span>${escapeHtml(empleado.SEDE_NOMBRE || 'Sin sede')}</span>
                        <span>${escapeHtml(empleado.ESTABLECIMIENTO_NOMBRE || 'Sin establecimiento')}</span>
                    </div>
                </div>
            </div>
        `;

        const checkbox = itemDiv.querySelector('.empleado-checkbox');
        checkbox.addEventListener('change', () => toggleEmpleadoTemporalSelection(empleado.ID_EMPLEADO));

        itemDiv.addEventListener('click', (e) => {
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
                toggleEmpleadoTemporalSelection(empleado.ID_EMPLEADO);
            }
        });

        container.appendChild(itemDiv);
    });

    container.style.display = 'block';
    noResults.style.display = 'none';
}

// toggle, selectAll, deselectAll, updateTemporalSelectedCount, and updateTemporalScheduleConfigVisibility
// are already defined earlier in the file with the required shared logic.

// filterEmpleadosTemporalList is declared earlier with normalization and filtered rendering.

function createTemporalSchedule() {
    const selectedEmpleados = selectedEmpleadosTemporal;
    const fecha = document.getElementById('temporal_fecha').value;
    const horaEntrada = document.getElementById('temporal_hora_entrada').value;
    const horaSalida = document.getElementById('temporal_hora_salida').value;
    const tolerancia = document.getElementById('temporal_tolerancia').value;
    const observaciones = document.getElementById('temporal_observaciones').value;

    // Validation
    if (selectedEmpleados.length === 0) {
        window.horariosPersonalizados.showNotification('Debe seleccionar al menos un empleado', 'error');
        return;
    }

    if (!fecha) {
        window.horariosPersonalizados.showNotification('Debe seleccionar una fecha', 'error');
        return;
    }

    if (!horaEntrada || !horaSalida) {
        window.horariosPersonalizados.showNotification('Debe especificar hora de entrada y salida', 'error');
        return;
    }

    // Show loading
    const confirmBtn = document.getElementById('confirmTemporalSchedule');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
    confirmBtn.disabled = true;

    // Prepare data
    const scheduleData = {
        empleados: selectedEmpleados,
        fecha: fecha,
        hora_entrada: horaEntrada,
        hora_salida: horaSalida,
        tolerancia: parseInt(tolerancia) || 15,
        observaciones: observaciones,
        es_temporal: 'S'
    };

    // Send to API
    fetch('api/horarios-personalizados/create-temporal-schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(scheduleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeTemporalScheduleModal();
            // Mostrar notificación después de cerrar el modal
            setTimeout(() => {
                window.horariosPersonalizados.showNotification(`Horario temporal creado exitosamente para ${selectedEmpleados.length} empleado(s)`, 'success');
            }, 300);
            // Reload the schedules list if needed
            if (window.horariosPersonalizados && window.horariosPersonalizados.loadEmployeeSchedules) {
                window.horariosPersonalizados.loadEmployeeSchedules();
            }
        } else {
            window.horariosPersonalizados.showNotification(data.message || 'Error al crear el horario temporal', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating temporal schedule:', error);
        window.horariosPersonalizados.showNotification('Error de conexión al crear el horario temporal', 'error');
    })
    .finally(() => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

// Event listeners for temporal modal
document.addEventListener('DOMContentLoaded', function() {
    // Temporal schedule modal events
    document.getElementById('closeTemporalScheduleModal')?.addEventListener('click', closeTemporalScheduleModal);
    document.getElementById('cancelTemporalSchedule')?.addEventListener('click', closeTemporalScheduleModal);
    document.getElementById('confirmTemporalSchedule')?.addEventListener('click', createTemporalSchedule);

    // Employee selection events for temporal modal
    document.getElementById('selectAllEmpleadosTemporal')?.addEventListener('click', selectAllEmpleadosTemporal);
    document.getElementById('deselectAllEmpleadosTemporal')?.addEventListener('click', deselectAllEmpleadosTemporal);
    document.getElementById('searchEmpleadosTemporal')?.addEventListener('input', filterEmpleadosTemporalList);

    // Close modal when clicking outside
    document.getElementById('temporalScheduleModal')?.addEventListener('click', function(e) {
        if (e.target.id === 'temporalScheduleModal') {
            closeTemporalScheduleModal();
        }
    });
});
