/**
 * Horas Trabajadas Module
 * Handles worked hours management functionality
 */

class HorasTrabajadas {
    constructor() {
        this.currentFilters = {
            sede: '',
            establecimiento: '',
            empleados: [], // Changed from empleado to empleados array
            fechaDesde: new Date().toISOString().split('T')[0],
            fechaHasta: new Date().toISOString().split('T')[0]
        };
        
        this.selectedEmpleados = [];
        this.availableEmpleados = [];
        this.originalEmpleados = [];
        this.selectedEmpleadosExtras = [];
        this.availableEmpleadosExtras = [];
        this.originalEmpleadosExtras = [];
        this.selectedHorasExtras = new Set();
        this.isInitializing = true; // Flag to prevent event listeners during init
        this.establecimientosCache = {};
        
        // Almacenar los datos procesados para exportación
        this.processedRecords = [];
        this.isExporting = false;

        this.detailRecords = [];
        this.detailPagination = {
            pageSizeOptions: [10, 20, 30, 50],
            pageSize: 20,
            currentPage: 1,
            totalItems: 0,
            controlsInitialized: false
        };

        this.horasExtrasRecords = [];
        this.horasExtrasPagination = {
            pageSizeOptions: [5, 10, 20, 50],
            pageSize: 10,
            currentPage: 1,
            totalItems: 0,
            controlsInitialized: false
        };

        this.paginationMaxButtons = 5;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeDetailPaginationControls();
        this.initializeHorasExtrasPaginationControls();
        this.setupDateRestrictions();
        this.setQuickFilterDates('hoy'); // Set dates only, don't apply filters yet
        const initialSede = document.getElementById('selectSede')?.value || '';
        this.loadEstablecimientos(initialSede);
        this.loadInitialData(); // This will load employees and then apply filters
        this.checkAndShowAdminFeatures();
    }

    bindEvents() {
        // Quick filter buttons
        document.getElementById('btnHoy').addEventListener('click', () => this.setQuickFilter('hoy'));
        document.getElementById('btnAyer').addEventListener('click', () => this.setQuickFilter('ayer'));
        document.getElementById('btnSemanaActual').addEventListener('click', () => this.setQuickFilter('semanaActual'));
        document.getElementById('btnSemanaPasada').addEventListener('click', () => this.setQuickFilter('semanaPasada'));
        document.getElementById('btnMesActual').addEventListener('click', () => this.setQuickFilter('mesActual'));
        document.getElementById('btnMesPasado').addEventListener('click', () => this.setQuickFilter('mesPasado'));

        // Filter controls
        document.getElementById('selectSede').addEventListener('change', this.onSedeChange.bind(this));
        document.getElementById('selectEstablecimiento').addEventListener('change', this.onEstablecimientoChange.bind(this));
        document.getElementById('btnFiltrar').addEventListener('click', this.applyFilters.bind(this));
        document.getElementById('btnLimpiarFiltros').addEventListener('click', this.clearFilters.bind(this));
    document.getElementById('btnExportExcel').addEventListener('click', this.exportToExcel.bind(this));
        document.getElementById('btnRefresh').addEventListener('click', this.refreshData.bind(this));

        // Employee selection
        document.getElementById('btnSelectEmpleados').addEventListener('click', this.showEmpleadosModal.bind(this));
        document.getElementById('closeSelectEmpleados').addEventListener('click', this.hideEmpleadosModal.bind(this));
        document.getElementById('cancelSelectEmpleados').addEventListener('click', this.hideEmpleadosModal.bind(this));
        document.getElementById('confirmSelectEmpleados').addEventListener('click', this.confirmEmpleadosSelection.bind(this));
        document.getElementById('searchEmpleados').addEventListener('input', this.filterEmpleadosList.bind(this));
        document.getElementById('selectAllEmpleados').addEventListener('click', this.selectAllEmpleados.bind(this));
        document.getElementById('deselectAllEmpleados').addEventListener('click', this.deselectAllEmpleados.bind(this));

        // Civic day registration
        document.getElementById('btnRegistrarDiaCivico').addEventListener('click', this.showDiaCivicoModal.bind(this));
        document.getElementById('closeDiaCivico').addEventListener('click', this.hideDiaCivicoModal.bind(this));
        document.getElementById('cancelDiaCivico').addEventListener('click', this.hideDiaCivicoModal.bind(this));
        document.getElementById('formDiaCivico').addEventListener('submit', this.submitDiaCivico.bind(this));

        // Modal outside click close
        document.getElementById('modalDiaCivico').addEventListener('click', (e) => {
            if (e.target.id === 'modalDiaCivico') {
                this.hideDiaCivicoModal();
            }
        });
        
        document.getElementById('modalSelectEmpleados').addEventListener('click', (e) => {
            if (e.target.id === 'modalSelectEmpleados') {
                this.hideEmpleadosModal();
            }
        });

        // Overtime approval modal events
        document.getElementById('btnAprobacionHorasExtras').addEventListener('click', this.showAprobacionHorasExtrasModal.bind(this));
        document.getElementById('closeAprobacionHorasExtras').addEventListener('click', this.hideAprobacionHorasExtrasModal.bind(this));

        // Overtime filters
        document.getElementById('filtroSedeExtras').addEventListener('change', this.onSedeExtrasChange.bind(this));
        document.getElementById('btnFiltrarHorasExtras').addEventListener('click', this.filtrarHorasExtras.bind(this));
        document.getElementById('btnLimpiarFiltrosExtras').addEventListener('click', this.limpiarFiltrosExtras.bind(this));

        // Quick filter buttons for overtime
        document.getElementById('btnQuickPendientes').addEventListener('click', () => this.quickFilterHorasExtras('pendiente'));
        document.getElementById('btnQuickAprobadas').addEventListener('click', () => this.quickFilterHorasExtras('aprobada'));
        document.getElementById('btnQuickRechazadas').addEventListener('click', () => this.quickFilterHorasExtras('rechazada'));
        document.getElementById('btnQuickTodas').addEventListener('click', () => this.quickFilterHorasExtras(''));

        // Quick date filter buttons
        document.getElementById('btnQuickHoy').addEventListener('click', () => this.quickFilterFechas(0));
        document.getElementById('btnQuickUltimos7').addEventListener('click', () => this.quickFilterFechas(7));
        document.getElementById('btnQuickUltimos30').addEventListener('click', () => this.quickFilterFechas(30));
        document.getElementById('btnQuickMesActual').addEventListener('click', () => this.quickFilterFechas('mes'));
        document.getElementById('btnQuickLimpiarFechas').addEventListener('click', () => this.quickFilterFechas('limpiar'));

        // Overtime bulk actions
        document.getElementById('selectAllExtras').addEventListener('change', this.toggleSelectAllExtras.bind(this));
        document.getElementById('btnAprobarSeleccionadas').addEventListener('click', () => this.aprobarHorasExtras('aprobar'));
        document.getElementById('btnRechazarSeleccionadas').addEventListener('click', () => this.aprobarHorasExtras('rechazar'));

        // Employee selection for overtime
        document.getElementById('btnSelectEmpleadosExtras').addEventListener('click', this.showEmpleadosExtrasModal.bind(this));
        document.getElementById('closeSelectEmpleadosExtras').addEventListener('click', this.hideEmpleadosExtrasModal.bind(this));
        document.getElementById('cancelSelectEmpleadosExtras').addEventListener('click', this.hideEmpleadosExtrasModal.bind(this));
        document.getElementById('confirmSelectEmpleadosExtras').addEventListener('click', this.confirmEmpleadosExtrasSelection.bind(this));
        document.getElementById('selectAllEmpleadosExtras').addEventListener('click', this.selectAllEmpleadosExtras.bind(this));
        document.getElementById('deselectAllEmpleadosExtras').addEventListener('click', this.deselectAllEmpleadosExtras.bind(this));
        document.getElementById('searchEmpleadosExtras').addEventListener('input', this.searchEmpleadosExtras.bind(this));
    }

    setupDateRestrictions() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fechaDesde').setAttribute('max', today);
        document.getElementById('fechaHasta').setAttribute('max', today);
        document.getElementById('fechaDiaCivico').setAttribute('min', today);
    }

    async loadInitialData() {
        await this.loadEmpleados();
        await this.applyFilters();
        this.isInitializing = false; // Allow event listeners after initialization
    }

    async onSedeChange() {
        if (this.isInitializing) return; // Skip during initialization
        
        const sedeId = document.getElementById('selectSede').value;
        await this.loadEstablecimientos(sedeId);
        await this.loadEmpleados();
        this.updateEmpleadosButton();
    }

    async onEstablecimientoChange() {
        if (this.isInitializing) return; // Skip during initialization
        
        await this.loadEmpleados();
        this.updateEmpleadosButton();
    }

    async loadEstablecimientos(sedeId) {
        const select = document.getElementById('selectEstablecimiento');
        select.innerHTML = '<option value="">Todos los establecimientos</option>';

        if (!sedeId) {
            return;
        }

        try {
            if (this.establecimientosCache[sedeId]) {
                this.populateEstablecimientosSelect(select, this.establecimientosCache[sedeId]);
                return;
            }

            const response = await fetch(`api/get-establecimientos.php?sede_id=${sedeId}`);
            const data = await response.json();
            
            if (data.success && data.establecimientos) {
                this.establecimientosCache[sedeId] = data.establecimientos;
                this.populateEstablecimientosSelect(select, data.establecimientos);
            }
        } catch (error) {
            console.error('Error loading establecimientos:', error);
            this.showError('Error al cargar establecimientos');
        }
    }

    populateEstablecimientosSelect(selectElement, establecimientos) {
        establecimientos.forEach(establecimiento => {
            const option = document.createElement('option');
            option.value = establecimiento.ID_ESTABLECIMIENTO;
            option.textContent = establecimiento.NOMBRE;
            selectElement.appendChild(option);
        });
    }

    async loadEmpleados() {
        const sedeId = document.getElementById('selectSede').value;
        const establecimientoId = document.getElementById('selectEstablecimiento').value;
        
        try {
            let url = 'api/horas-trabajadas/get-empleados.php?';
            if (sedeId) url += `sede_id=${sedeId}&`;
            if (establecimientoId) url += `establecimiento_id=${establecimientoId}&`;

            console.log('Loading empleados from:', url); // Debug

            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Empleados response:', data); // Debug
            
            if (data.success && data.empleados) {
                this.availableEmpleados = data.empleados;
                this.originalEmpleados = [...data.empleados];
                
                // Filter selected employees to only include those still available
                this.selectedEmpleados = this.selectedEmpleados.filter(selectedId =>
                    this.availableEmpleados.some(emp => emp.ID_EMPLEADO == selectedId)
                );
                
                this.updateEmpleadosButton();
                this.populateEmpleadosList();
                
                console.log(`Loaded ${data.empleados.length} empleados`); // Debug
            } else {
                console.error('API error:', data);
                this.showError(data.message || 'Error al cargar empleados');
            }
        } catch (error) {
            console.error('Error loading empleados:', error);
            this.showError('Error al cargar empleados: ' + error.message);
        }
    }

    setQuickFilter(filterType) {
        // Remove active class from all buttons
        document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
        
        const today = new Date();
        let fechaDesde, fechaHasta;

        switch (filterType) {
            case 'hoy':
                fechaDesde = fechaHasta = today.toISOString().split('T')[0];
                document.getElementById('btnHoy').classList.add('active');
                break;
            case 'ayer':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                fechaDesde = fechaHasta = yesterday.toISOString().split('T')[0];
                document.getElementById('btnAyer').classList.add('active');
                break;
            case 'semanaActual':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + 1); // Monday
                fechaDesde = startOfWeek.toISOString().split('T')[0];
                fechaHasta = today.toISOString().split('T')[0];
                document.getElementById('btnSemanaActual').classList.add('active');
                break;
            case 'semanaPasada':
                const startOfLastWeek = new Date(today);
                startOfLastWeek.setDate(today.getDate() - today.getDay() - 6); // Last Monday
                const endOfLastWeek = new Date(startOfLastWeek);
                endOfLastWeek.setDate(startOfLastWeek.getDate() + 6); // Last Sunday
                fechaDesde = startOfLastWeek.toISOString().split('T')[0];
                fechaHasta = endOfLastWeek.toISOString().split('T')[0];
                document.getElementById('btnSemanaPasada').classList.add('active');
                break;
            case 'mesActual':
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                fechaDesde = startOfMonth.toISOString().split('T')[0];
                fechaHasta = today.toISOString().split('T')[0];
                document.getElementById('btnMesActual').classList.add('active');
                break;
            case 'mesPasado':
                const startOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                fechaDesde = startOfLastMonth.toISOString().split('T')[0];
                fechaHasta = endOfLastMonth.toISOString().split('T')[0];
                document.getElementById('btnMesPasado').classList.add('active');
                break;
        }

        document.getElementById('fechaDesde').value = fechaDesde;
        document.getElementById('fechaHasta').value = fechaHasta;
        
        this.applyFilters();
    }

    setQuickFilterDates(filterType) {
        // Same logic as setQuickFilter but without applying filters
        // Remove active class from all buttons
        document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
        
        const today = new Date();
        let fechaDesde, fechaHasta;

        switch (filterType) {
            case 'hoy':
                fechaDesde = fechaHasta = today.toISOString().split('T')[0];
                document.getElementById('btnHoy').classList.add('active');
                break;
            case 'ayer':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                fechaDesde = fechaHasta = yesterday.toISOString().split('T')[0];
                document.getElementById('btnAyer').classList.add('active');
                break;
            case 'semanaActual':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + 1); // Monday
                fechaDesde = startOfWeek.toISOString().split('T')[0];
                fechaHasta = today.toISOString().split('T')[0];
                document.getElementById('btnSemanaActual').classList.add('active');
                break;
            case 'semanaPasada':
                const startOfLastWeek = new Date(today);
                startOfLastWeek.setDate(today.getDate() - today.getDay() - 6); // Last Monday
                const endOfLastWeek = new Date(startOfLastWeek);
                endOfLastWeek.setDate(startOfLastWeek.getDate() + 6); // Last Sunday
                fechaDesde = startOfLastWeek.toISOString().split('T')[0];
                fechaHasta = endOfLastWeek.toISOString().split('T')[0];
                document.getElementById('btnSemanaPasada').classList.add('active');
                break;
            case 'mesActual':
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                fechaDesde = startOfMonth.toISOString().split('T')[0];
                fechaHasta = today.toISOString().split('T')[0];
                document.getElementById('btnMesActual').classList.add('active');
                break;
            case 'mesPasado':
                const startOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                fechaDesde = startOfLastMonth.toISOString().split('T')[0];
                fechaHasta = endOfLastMonth.toISOString().split('T')[0];
                document.getElementById('btnMesPasado').classList.add('active');
                break;
        }

        // Clear inputs first to avoid validation errors
        document.getElementById('fechaDesde').value = '';
        document.getElementById('fechaHasta').value = '';
        
        // Only set dates, don't apply filters
        document.getElementById('fechaDesde').value = fechaDesde;
        document.getElementById('fechaHasta').value = fechaHasta;
    }

    updateEmpleadosButton() {
        const btn = document.getElementById('btnSelectEmpleados');
        const textSpan = btn.querySelector('.empleados-text');
        let countSpan = btn.querySelector('.empleados-count');
        
        // Create count span if it doesn't exist
        if (!countSpan) {
            countSpan = document.createElement('span');
            countSpan.className = 'empleados-count';
            btn.insertBefore(countSpan, btn.querySelector('i'));
        }
        
        if (this.selectedEmpleados.length === 0) {
            textSpan.textContent = 'Todos los empleados';
            countSpan.classList.remove('show');
        } else if (this.selectedEmpleados.length === 1) {
            const empleado = this.availableEmpleados.find(emp => emp.ID_EMPLEADO == this.selectedEmpleados[0]);
            textSpan.textContent = empleado ? `${empleado.NOMBRE} ${empleado.APELLIDO}` : '1 empleado seleccionado';
            countSpan.textContent = '1';
            countSpan.classList.add('show');
        } else {
            textSpan.textContent = `${this.selectedEmpleados.length} empleados seleccionados`;
            countSpan.textContent = this.selectedEmpleados.length;
            countSpan.classList.add('show');
        }
    }

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
            console.log('Loading empleados for modal...'); // Debug
            this.loadEmpleados().then(() => {
                console.log('Empleados loaded for modal:', this.availableEmpleados.length); // Debug
            });
        } else {
            console.log('Using cached empleados:', this.availableEmpleados.length); // Debug
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

    populateEmpleadosList(empleados = null) {
        const container = document.getElementById('empleadosListContent');
        const loading = document.getElementById('empleadosLoading');
        const noResults = document.getElementById('empleadosNoResults');
        
        const empleadosToShow = empleados || this.availableEmpleados;
        
        console.log('Populating list with empleados:', empleadosToShow.length); // Debug
        
        if (empleadosToShow.length === 0) {
            container.style.display = 'none';
            loading.style.display = 'none';
            noResults.style.display = 'block';
            this.updateSelectedCount();
            console.log('No empleados to show', empleadosToShow); // Debug
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
        console.log('List populated successfully with', empleadosToShow.length, 'empleados'); // Debug
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

    updateSelectedCount() {
        const countElement = document.getElementById('selectedCount');
        const count = this.selectedEmpleados.length;
        countElement.textContent = count;
        
        // Update the visual state of the count element
        const countContainer = countElement.closest('.selected-count');
        if (countContainer) {
            if (count > 0) {
                countContainer.style.display = 'flex';
            } else {
                countContainer.style.display = 'flex'; // Always show, just with 0
            }
        }
    }

    confirmEmpleadosSelection() {
        this.currentFilters.empleados = [...this.selectedEmpleados];
        this.updateEmpleadosButton();
        this.hideEmpleadosModal();
        
        // Apply filters automatically with AJAX
        this.applyFilters();
    }

    formatDate(date) {
        if (!(date instanceof Date) || isNaN(date)) {
            console.error('Invalid date passed to formatDate:', date);
            return '';
        }
        return date.toISOString().split('T')[0];
    }

    async applyFilters() {
        this.updateCurrentFilters();
        await this.loadHorasTrabajadasAjax(); // Use AJAX version
    }

    updateCurrentFilters() {
        this.currentFilters = {
            sede: document.getElementById('selectSede').value,
            establecimiento: document.getElementById('selectEstablecimiento').value,
            empleados: [...this.selectedEmpleados], // Use selected employees array
            fechaDesde: document.getElementById('fechaDesde').value,
            fechaHasta: document.getElementById('fechaHasta').value
        };
    }

    async loadHorasTrabajadasAjax() {
        this.showLoading(true);
        
        try {
            
            // Verificar que hay empleados disponibles antes de continuar
            if (this.currentFilters.empleados.length === 0 && this.availableEmpleados.length === 0) {
                console.log('No hay empleados disponibles, esperando...');
                this.showLoading(false);
                return;
            }
            
            // Usar FormData para envío POST al nuevo API
            const formData = new FormData();
            
            // Agregar empleados seleccionados, o todos si no hay selección
            if (this.currentFilters.empleados.length > 0) {
                this.currentFilters.empleados.forEach(empleadoId => {
                    formData.append('empleados[]', empleadoId);
                });
            } else if (this.availableEmpleados.length > 0) {
                // Si no hay empleados seleccionados, usar todos los disponibles
                this.availableEmpleados.forEach(empleado => {
                    formData.append('empleados[]', empleado.ID_EMPLEADO);
                });
            } else {
                this.showLoading(false);
                return;
            }
            
            // Agregar fechas - enviar directamente sin conversión para evitar problemas de zona horaria
            formData.append('fechaDesde', this.currentFilters.fechaDesde);
            formData.append('fechaHasta', this.currentFilters.fechaHasta);
            
            // Agregar filtros adicionales si existen
            if (this.currentFilters.sede) {
                formData.append('sede_id', this.currentFilters.sede);
            }
            if (this.currentFilters.establecimiento) {
                formData.append('establecimiento_id', this.currentFilters.establecimiento);
            }
            
            const response = await fetch('api/horas-trabajadas/get-horas.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            // Mostrar mensajes de debug si existen
            if (result.debug_messages && result.debug_messages.length > 0) {
                console.log('=== MENSAJES DE DEBUG DEL API ===');
                result.debug_messages.forEach((message, index) => {
                    console.log(`${index + 1}. ${message}`);
                });
                console.log('=== FIN MENSAJES DE DEBUG ===');
            }
            
            if (result.success) {
                this.updateStats(result.data.stats, result.data.horas_extras_por_fecha || {});
                this.updateTable(result.data.horas, result.data.justificaciones, result.data.horas_extras_por_fecha || {});
            } else {
                this.showError(result.message || 'Error desconocido en la API');
            }
            
        } catch (error) {
            console.error('Error loading horas trabajadas:', error);
            this.showError('Error al cargar las horas trabajadas: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    // Función helper para formatear horas en formato "Xh Ym"
    formatHorasMinutos(horasDecimal) {
        if (Number.isNaN(horasDecimal) || horasDecimal === null || horasDecimal === undefined) {
            return '0h 0m';
        }

        let horas = Math.trunc(horasDecimal);
        let minutos = Math.round((horasDecimal - horas) * 60);

        if (minutos === 60) {
            horas += 1;
            minutos = 0;
        }

        return `${horas}h ${minutos}m`;
    }

    formatHoraCorta(horaCompleta) {
        if (!horaCompleta) {
            return '--';
        }

        const partes = horaCompleta.toString().split(':');
        if (partes.length >= 2) {
            const horas = partes[0].padStart(2, '0');
            const minutos = partes[1].padStart(2, '0');
            return `${horas}:${minutos}`;
        }

        return horaCompleta.toString();
    }

    updateStats(stats, horasExtrasGlobal = {}) {
        // Verificar y actualizar elementos que existen
        const updateElement = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = this.formatHorasMinutos(value || 0);
            } else {
                console.warn(`Elemento DOM no encontrado: ${id}`);
            }
        };
        
        // Nuevas estadísticas refactorizadas
        updateElement('horasRegular', stats.horas_regulares);
        
        // Recargos - mapear a los elementos existentes
        updateElement('recargoNocturno', stats.recargo_nocturno);
        updateElement('recargoDominical', stats.recargo_dominical_festivo);
        updateElement('recargoNocturnoDominical', stats.recargo_nocturno_dominical_festivo);
        
        // Horas extra - calcular solo las aprobadas
        let extraDiurnaAprobada = 0;
        let extraNocturnaAprobada = 0;
        let extraDiurnaDominicalAprobada = 0;
        let extraNocturnaDominicalAprobada = 0;
        
        // Calcular horas extras pendientes y rechazadas
        let horasExtrasPendientes = 0;
        let horasExtrasRechazadas = 0;
        let horasExtrasAprobadas = 0;
        
        // Recorrer todas las horas extras por fecha
        Object.values(horasExtrasGlobal).forEach(extrasFecha => {
            extrasFecha.forEach(extra => {
                const horas = extra.horas_extras || 0;
                const estado = extra.estado_aprobacion;
                const tipo = extra.tipo_horario;
                
                if (estado === 'aprobada') {
                    horasExtrasAprobadas += horas;
                    // Sumar por tipo
                    if (tipo === 'diurna') {
                        extraDiurnaAprobada += horas;
                    } else if (tipo === 'nocturna') {
                        extraNocturnaAprobada += horas;
                    } else if (tipo === 'diurna_dominical_festiva') {
                        extraDiurnaDominicalAprobada += horas;
                    } else if (tipo === 'nocturna_dominical_festiva') {
                        extraNocturnaDominicalAprobada += horas;
                    }
                } else if (estado === 'rechazada') {
                    horasExtrasRechazadas += horas;
                } else if (estado === 'pendiente') {
                    horasExtrasPendientes += horas;
                }
            });
        });
        
        // Actualizar los elementos con valores filtrados por aprobadas
        updateElement('extraDiurna', extraDiurnaAprobada);
        updateElement('extraNocturna', extraNocturnaAprobada);
        updateElement('extraDiurnaDominical', extraDiurnaDominicalAprobada);
        updateElement('extraNocturnaDominical', extraNocturnaDominicalAprobada);
        
        // Actualizar los elementos
        updateElement('horasExtrasPendientes', horasExtrasPendientes);
        updateElement('horasExtrasRechazadas', horasExtrasRechazadas);
        updateElement('horasExtrasAprobadas', horasExtrasAprobadas);
        
        updateElement('totalHoras', stats.total_horas + horasExtrasAprobadas);
        
        // Mantener compatibilidad con estadísticas anteriores
        if (stats.regular !== undefined) {
            updateElement('horasRegular', stats.regular);
        }
        if (stats.extra !== undefined) {
            const totalExtra = (stats.extra_diurna || 0) + (stats.extra_nocturna || 0) + 
                              (stats.extra_diurna_dominical_festiva || 0) + (stats.extra_nocturna_dominical_festiva || 0);
            updateElement('horasExtra', totalExtra);
        }
        
        if (stats.dominicales !== undefined) {
            const totalDominical = (stats.recargo_dominical_festivo || 0) + (stats.recargo_nocturno_dominical_festivo || 0) +
                                   (stats.extra_diurna_dominical_festiva || 0) + (stats.extra_nocturna_dominical_festiva || 0);
            updateElement('horasDominicales', totalDominical);
        }
        if (stats.festivos !== undefined) {
            updateElement('horasFestivos', stats.festivos);
        }
    }

    updateTable(horas, justificaciones = [], horasExtrasGlobal = {}) {
        console.log('🔄 updateTable called with:', { horasCount: horas?.length, justificacionesCount: justificaciones?.length });
        console.log('🔍 Raw horas data:', horas?.map(h => ({
            empleado_id: h.empleado_id,
            empleado_nombre: h.empleado_nombre,
            fecha: h.fecha,
            horas_regulares: h.horas_regulares
        })));
    const justificacionesIndex = this.groupJustificacionesByEmpleadoDia(justificaciones);

    // Combinar registros de horas y justificaciones
    const allRecords = [];

        // Procesar registros de horas trabajadas
        if (horas && horas.length > 0) {
            console.log('📊 Procesando horas:', horas.map(h => ({ id: h.empleado_id, fecha: h.fecha, horario_personalizado: !!h.horario_personalizado })));
            horas.forEach(hora => {
                // Validar que la fecha existe y es válida
                if (!hora.fecha) {
                    console.warn('⚠️ Registro de horas sin fecha válida:', hora);
                    return; // Saltar este registro
                }
                // Usar valores calculados directamente del API (ya aplican la jerarquía correctamente)
                const horasRegulares = hora.horas_regulares || 0;
                const recargoNocturno = hora.recargo_nocturno || 0;
                const recargoDominical = hora.recargo_dominical_festivo || 0;
                const recargoNocturnoDominical = hora.recargo_nocturno_dominical_festivo || 0;

                // Usar horas extras calculadas directamente del API (ya categorizadas correctamente)
                const extraDiurna = hora.extra_diurna || 0;
                const extraNocturna = hora.extra_nocturna || 0;
                const extraDiurnaDominical = hora.extra_diurna_dominical_festiva || 0;
                const extraNocturnaDominical = hora.extra_nocturna_dominical_festiva || 0;

                // Determinar estados de aprobación para colores (solo desde horasExtrasGlobal)
                let estadoExtraDiurna = null;
                let estadoExtraNocturna = null;
                let estadoExtraDiurnaDominical = null;
                let estadoExtraNocturnaDominical = null;

                // Buscar estados de aprobación en horasExtrasGlobal para mostrar colores
                const horasExtrasFecha = horasExtrasGlobal[hora.fecha] || [];
                const horasExtrasEmpleado = horasExtrasFecha.filter(extra => extra.empleado_id == hora.empleado_id);

                horasExtrasEmpleado.forEach(extra => {
                    const tipoHorario = extra.tipo_horario;
                    const estado = extra.estado_aprobacion || 'pendiente';

                    // Asignar estado según el tipo de horario
                    if (tipoHorario === 'nocturna_dominical' || tipoHorario === 'nocturna_dominical_festiva') {
                        if (!estadoExtraNocturnaDominical) estadoExtraNocturnaDominical = estado;
                    } else if (tipoHorario === 'diurna_dominical' || tipoHorario === 'diurna_dominical_festiva') {
                        if (!estadoExtraDiurnaDominical) estadoExtraDiurnaDominical = estado;
                    } else if (tipoHorario === 'nocturna') {
                        if (!estadoExtraNocturna) estadoExtraNocturna = estado;
                    } else if (tipoHorario === 'diurna') {
                        if (!estadoExtraDiurna) estadoExtraDiurna = estado;
                    }
                });

                // Calcular total de horas (solo incluir horas extras APROBADAS)
                const extraDiurnaAprobadas = estadoExtraDiurna === 'aprobada' ? extraDiurna : 0;
                const extraNocturnaAprobadas = estadoExtraNocturna === 'aprobada' ? extraNocturna : 0;
                const extraDiurnaDominicalAprobadas = estadoExtraDiurnaDominical === 'aprobada' ? extraDiurnaDominical : 0;
                const extraNocturnaDominicalAprobadas = estadoExtraNocturnaDominical === 'aprobada' ? extraNocturnaDominical : 0;

                const totalHoras = horasRegulares + recargoNocturno + recargoDominical + recargoNocturnoDominical + extraDiurnaAprobadas + extraNocturnaAprobadas + extraDiurnaDominicalAprobadas + extraNocturnaDominicalAprobadas;

                // Extraer información de horarios
                let horario_asignado = '--';
                let observaciones = '';
                let horariosProgramados = [];
                let horarioJustificadoTexto = '';
                let tieneJustificacionParcial = false;
                let justificacionesParciales = [];

                const turnosDetallados = Array.isArray(hora.detalle_turnos)
                    ? hora.detalle_turnos.filter(turno => turno && (turno.hora_entrada || turno.hora_salida))
                    : [];

                const buildHorarioResumenTurno = turno => {
                    if (!turno) {
                        return '';
                    }

                    if (typeof turno.horario_resumen === 'string' && turno.horario_resumen.trim() !== '') {
                        return turno.horario_resumen.trim();
                    }

                    const entrada = this.formatHoraCorta(turno.hora_entrada || turno.entrada_real);
                    const salida = this.formatHoraCorta(turno.hora_salida || turno.salida_real);
                    let resumen = '';

                    if (entrada !== '--' && salida !== '--') {
                        resumen = `${entrada} - ${salida}`;
                    } else if (entrada !== '--') {
                        resumen = entrada;
                    } else if (salida !== '--') {
                        resumen = salida;
                    }

                    if (turno.nombre_turno) {
                        resumen = resumen ? `${resumen} (${turno.nombre_turno})` : turno.nombre_turno;
                    }

                    return resumen;
                };

                const justificacionGeneral = hora.justificacion_general || null;
                if (justificacionGeneral && justificacionGeneral.justificar_todos_turnos) {
                    horariosProgramados = Array.isArray(justificacionGeneral.horarios_programados)
                        ? justificacionGeneral.horarios_programados
                        : (Array.isArray(hora.todos_los_horarios) ? hora.todos_los_horarios : []);

                    const horariosTexto = this.formatHorariosProgramados(horariosProgramados, { marcarComoJustificado: true });
                    horario_asignado = horariosTexto || 'Jornada completa';
                    const observacionGeneral = this.buildJustificacionObservacion(justificacionGeneral, 'Jornada completa');
                    observaciones = observaciones ? `${observaciones} | ${observacionGeneral}` : observacionGeneral;
                    horarioJustificadoTexto = horario_asignado;
                }

                if (!horarioJustificadoTexto) {
                    const justKey = this.buildJustificacionKey(hora.empleado_id, hora.fecha);
                    const justificacionesDelDia = justKey ? (justificacionesIndex.get(justKey) || []) : [];
                    if (justificacionesDelDia.length > 0) {
                        justificacionesParciales = justificacionesDelDia.filter(j => parseInt(j.justificar_todos_turnos, 10) !== 1);
                        if (justificacionesParciales.length > 0) {
                            const horariosJustificados = this.collectHorariosJustificados(justificacionesParciales, hora.todos_los_horarios);
                            if (horariosJustificados.length > 0) {
                                horariosProgramados = horariosJustificados;
                                const horariosTexto = this.formatHorariosProgramados(horariosJustificados, { marcarComoJustificado: true });
                                if (horariosTexto) {
                                    horario_asignado = horariosTexto;
                                    horarioJustificadoTexto = horariosTexto;
                                }
                            }

                            const observacionesParciales = justificacionesParciales
                                .map(j => this.buildJustificacionObservacion(j, 'Justificación parcial'))
                                .filter(Boolean);

                            if (observacionesParciales.length > 0) {
                                const observacionesTexto = observacionesParciales.join(' | ');
                                observaciones = observaciones ? `${observaciones} | ${observacionesTexto}` : observacionesTexto;
                            }

                            tieneJustificacionParcial = true;
                        }
                    }
                }

                if (tieneJustificacionParcial && (!Array.isArray(horariosProgramados) || horariosProgramados.length === 0) && Array.isArray(hora.todos_los_horarios)) {
                    horariosProgramados = hora.todos_los_horarios;
                }

                // Procesar horario asignado
                if (!horarioJustificadoTexto && turnosDetallados.length > 0) {
                    const multiplesTurnos = turnosDetallados.length > 1;
                    const resumenTurnos = turnosDetallados
                        .map((turno, index) => {
                            const resumen = buildHorarioResumenTurno(turno);
                            if (!resumen) {
                                return null;
                            }
                            const etiqueta = multiplesTurnos ? `${index + 1}. ${this.escapeHtml(resumen)}` : this.escapeHtml(resumen);
                            return etiqueta;
                        })
                        .filter(Boolean);

                    if (resumenTurnos.length > 0) {
                        horario_asignado = resumenTurnos.join('<br>');
                    }
                }

                if (turnosDetallados.length === 0 && hora.detalle_horas && Array.isArray(hora.detalle_horas) && hora.detalle_horas.length > 0) {
                    const primerDetalle = hora.detalle_horas[0];

                    // Buscar horario completo
                    let horarioEncontrado = null;

                    if (primerDetalle.id_empleado_horario && hora.todos_los_horarios && hora.todos_los_horarios.length > 0) {
                        horarioEncontrado = hora.todos_los_horarios.find(h => h.ID_EMPLEADO_HORARIO == primerDetalle.id_empleado_horario);
                    }

                    // Si no se encontró por ID, buscar por nombre de turno
                    if (!horarioEncontrado && primerDetalle.nombre_turno && hora.todos_los_horarios && hora.todos_los_horarios.length > 0) {
                        horarioEncontrado = hora.todos_los_horarios.find(h => h.NOMBRE_TURNO === primerDetalle.nombre_turno);
                    }

                    // Si no se encontró por nombre, tomar el primer horario disponible
                    if (!horarioEncontrado && hora.todos_los_horarios && hora.todos_los_horarios.length > 0) {
                        horarioEncontrado = hora.todos_los_horarios[0];
                    }

                    if (horarioEncontrado) {
                        if (!horarioJustificadoTexto) {
                            horario_asignado = `${horarioEncontrado.HORA_ENTRADA.substring(0,5)} - ${horarioEncontrado.HORA_SALIDA.substring(0,5)}`;
                            if (horarioEncontrado.NOMBRE_TURNO) {
                                horario_asignado += ` (${horarioEncontrado.NOMBRE_TURNO})`;
                            }
                            // Agregar indicación de temporal si es horario temporal
                            if (horarioEncontrado.ES_TEMPORAL === 'S') {
                                horario_asignado = `<span class="temporal-badge">TEMPORAL</span><br>${horario_asignado}`;
                            }
                        }
                    } else if (!horarioJustificadoTexto) {
                        horario_asignado = primerDetalle.nombre_turno || '--';
                    }
                } else if (turnosDetallados.length === 0 && hora.horario_personalizado) {
                    const hp = hora.horario_personalizado;
                    if (!horarioJustificadoTexto) {
                        if (hp.HORA_ENTRADA && hp.HORA_SALIDA) {
                            horario_asignado = `${hp.HORA_ENTRADA.substring(0,5)} - ${hp.HORA_SALIDA.substring(0,5)}`;
                            if (hp.NOMBRE_TURNO) {
                                horario_asignado += ` (${hp.NOMBRE_TURNO})`;
                            }
                            // Agregar indicación de temporal si es horario temporal
                            if (hp.ES_TEMPORAL === 'S') {
                                horario_asignado = `<span class="temporal-badge">TEMPORAL</span><br>${horario_asignado}`;
                            }
                        } else if (hp.NOMBRE_TURNO) {
                            horario_asignado = hp.NOMBRE_TURNO;
                            // Agregar indicación de temporal si es horario temporal
                            if (hp.ES_TEMPORAL === 'S') {
                                horario_asignado = `<span class="temporal-badge">TEMPORAL</span><br>${horario_asignado}`;
                            }
                        }
                    }
                }

                if (tieneJustificacionParcial) {
                    if (horarioJustificadoTexto) {
                        horario_asignado = horarioJustificadoTexto;
                    } else if (horario_asignado && horario_asignado !== '--' && !/Justificado/i.test(horario_asignado)) {
                        horario_asignado = `${horario_asignado} (Justificado)`;
                    } else if (!horario_asignado || horario_asignado === '--') {
                        horario_asignado = 'Turno justificado';
                    }
                }

                const observacionesGenerales = observaciones;
                const baseRecord = {
                    tipo: 'horas',
                    FECHA: hora.fecha,
                    ID_EMPLEADO: hora.empleado_id,
                    NOMBRE: hora.empleado_nombre || '',
                    APELLIDO: hora.empleado_apellido || '',
                    ES_FESTIVO: hora.es_festivo || false,
                    HORARIOS_PROGRAMADOS: horariosProgramados,
                    JUSTIFICACION_JORNADA_COMPLETA: !!(justificacionGeneral && justificacionGeneral.justificar_todos_turnos),
                    JUSTIFICACION_PARCIAL: tieneJustificacionParcial,
                    JUSTIFICACIONES_DETALLE: justificacionesParciales
                };

                if (turnosDetallados.length > 0) {
                    const multiplesTurnos = turnosDetallados.length > 1;

                    turnosDetallados.forEach((turno, index) => {
                        const prefijo = multiplesTurnos ? `${index + 1}. ` : '';
                        const resumenTurno = buildHorarioResumenTurno(turno);
                        const resumenSeguro = resumenTurno ? this.escapeHtml(resumenTurno) : '';
                        const horarioDisplay = resumenSeguro || horario_asignado || '--';
                        const prefijoHtml = multiplesTurnos ? this.escapeHtml(prefijo) : '';

                        const entradaTurno = this.formatHoraCorta(turno.entrada_real || turno.hora_entrada);
                        const salidaTurno = this.formatHoraCorta(turno.salida_real || turno.hora_salida);
                        const fechaEntradaTurno = turno.fecha_turno || hora.fecha;
                        const fechaSalidaTurno = turno.fecha_salida || fechaEntradaTurno;
                        const salidaSuffix = fechaSalidaTurno && fechaEntradaTurno && fechaSalidaTurno !== fechaEntradaTurno ? ' (+1)' : '';

                        const clasif = turno.clasificacion || {};
                        console.log('🔍 Clasificación del turno:', turno.fecha_turno, turno.hora_entrada, turno.hora_salida, 'clasif:', clasif);
                        const horasRegTurno = Number(clasif.horas_regulares || 0);
                        const recargoNocturnoTurno = Number(clasif.recargo_nocturno || 0);
                        const recargoDominicalTurno = Number(clasif.recargo_dominical_festivo || 0);
                        const recargoNocturnoDominicalTurno = Number(clasif.recargo_nocturno_dominical_festivo || 0);
                        const extraDiurnaTurno = Number(clasif.extra_diurna || 0);
                        const extraNocturnaTurno = Number(clasif.extra_nocturna || 0);
                        const extraDiurnaDominicalTurno = Number(clasif.extra_diurna_dominical_festiva || 0);
                        const extraNocturnaDominicalTurno = Number(clasif.extra_nocturna_dominical_festiva || 0);
                        console.log('🔍 Valores calculados:', { extraDiurnaTurno, extraNocturnaTurno, horasRegTurno });

                        const extraDiurnaAprobadasTurno = estadoExtraDiurna === 'aprobada' ? extraDiurnaTurno : 0;
                        const extraNocturnaAprobadasTurno = estadoExtraNocturna === 'aprobada' ? extraNocturnaTurno : 0;
                        const extraDiurnaDominicalAprobadasTurno = estadoExtraDiurnaDominical === 'aprobada' ? extraDiurnaDominicalTurno : 0;
                        const extraNocturnaDominicalAprobadasTurno = estadoExtraNocturnaDominical === 'aprobada' ? extraNocturnaDominicalTurno : 0;

                        const totalHorasTurno = horasRegTurno +
                            recargoNocturnoTurno +
                            recargoDominicalTurno +
                            recargoNocturnoDominicalTurno +
                            extraDiurnaAprobadasTurno +
                            extraNocturnaAprobadasTurno +
                            extraDiurnaDominicalAprobadasTurno +
                            extraNocturnaDominicalAprobadasTurno;

                        const observacionesTurno = [];
                        if (observacionesGenerales) {
                            observacionesTurno.push(observacionesGenerales);
                        }

                        if (turno.observaciones) {
                            if (turno.observaciones.entrada) {
                                observacionesTurno.push(`${prefijo}Entrada: ${turno.observaciones.entrada}`);
                            }
                            if (turno.observaciones.salida) {
                                observacionesTurno.push(`${prefijo}Salida: ${turno.observaciones.salida}`);
                            }
                        }

                        const rowRecord = {
                            ...baseRecord,
                            HORARIO_ASIGNADO: prefijoHtml ? `${prefijoHtml}${horarioDisplay}` : horarioDisplay,
                            ENTRADA_HORA: this.escapeHtml(multiplesTurnos ? `${prefijo}${entradaTurno}` : entradaTurno),
                            SALIDA_HORA: this.escapeHtml(multiplesTurnos ? `${prefijo}${salidaTurno}${salidaSuffix}` : `${salidaTurno}${salidaSuffix}`),
                            HORAS_REGULARES: horasRegTurno,
                            RECARGO_NOCTURNO: recargoNocturnoTurno,
                            RECARGO_DOMINICAL_FESTIVO: recargoDominicalTurno,
                            RECARGO_NOCTURNO_DOMINICAL_FESTIVO: recargoNocturnoDominicalTurno,
                            EXTRA_DIURNA: extraDiurnaTurno,
                            EXTRA_NOCTURNA: extraNocturnaTurno,
                            EXTRA_DIURNA_DOMINICAL_FESTIVA: extraDiurnaDominicalTurno,
                            EXTRA_NOCTURNA_DOMINICAL_FESTIVA: extraNocturnaDominicalTurno,
                            EXTRA_DIURNA_ESTADO: estadoExtraDiurna,
                            EXTRA_NOCTURNA_ESTADO: estadoExtraNocturna,
                            EXTRA_DIURNA_DOMINICAL_ESTADO: estadoExtraDiurnaDominical,
                            EXTRA_NOCTURNA_DOMINICAL_ESTADO: estadoExtraNocturnaDominical,
                            TOTAL_HORAS: totalHorasTurno,
                            OBSERVACIONES: observacionesTurno.length > 0 ? observacionesTurno.join(' | ') : '--',
                            DETALLE_TURNOS: [turno]
                        };

                        rowRecord.JUSTIFICACION_PARCIAL = rowRecord.JUSTIFICACION_PARCIAL || !!turno.justificado;

                        allRecords.push(rowRecord);
                    });

                    return;
                }

                let entrada_hora = '--';
                let salida_hora = '--';
                let observacionesFallback = observacionesGenerales;

                if (hora.detalle_horas) {
                    if (hora.detalle_horas.tipo === 'turno_nocturno') {
                        const descripcionNocturno = `Turno nocturno - ${hora.detalle_horas.horario || ''}`;
                        observacionesFallback = observacionesFallback
                            ? `${observacionesFallback} | ${descripcionNocturno}`
                            : descripcionNocturno;
                        if (hora.detalle_horas.entrada_real && hora.detalle_horas.salida_real) {
                            entrada_hora = hora.detalle_horas.entrada_real.substring(0,5);
                            salida_hora = hora.detalle_horas.salida_real.substring(0,5);
                        } else if (hora.detalle_horas.horario) {
                            const horarioPartes = hora.detalle_horas.horario.split(' - ');
                            if (horarioPartes.length === 2) {
                                entrada_hora = horarioPartes[0].trim();
                                salida_hora = horarioPartes[1].trim();
                            }
                        }
                    } else if (Array.isArray(hora.detalle_horas)) {
                        let primera_entrada = null;
                        let ultima_salida = null;

                        hora.detalle_horas.forEach(detalle => {
                            if (detalle.hora_entrada && (!primera_entrada || detalle.hora_entrada < primera_entrada)) {
                                primera_entrada = detalle.hora_entrada;
                            }
                            if (detalle.hora_salida && (!ultima_salida || detalle.hora_salida > ultima_salida)) {
                                ultima_salida = detalle.hora_salida;
                            }
                        });

                        entrada_hora = primera_entrada ? primera_entrada.substring(0,5) : '--';
                        salida_hora = ultima_salida ? ultima_salida.substring(0,5) : '--';
                    }
                }

                const fallbackRecord = {
                    ...baseRecord,
                    HORARIO_ASIGNADO: horario_asignado,
                    ENTRADA_HORA: entrada_hora,
                    SALIDA_HORA: salida_hora,
                    HORAS_REGULARES: horasRegulares,
                    RECARGO_NOCTURNO: recargoNocturno,
                    RECARGO_DOMINICAL_FESTIVO: recargoDominical,
                    RECARGO_NOCTURNO_DOMINICAL_FESTIVO: recargoNocturnoDominical,
                    EXTRA_DIURNA: extraDiurna,
                    EXTRA_NOCTURNA: extraNocturna,
                    EXTRA_DIURNA_DOMINICAL_FESTIVA: extraDiurnaDominical,
                    EXTRA_NOCTURNA_DOMINICAL_FESTIVA: extraNocturnaDominical,
                    EXTRA_DIURNA_ESTADO: estadoExtraDiurna,
                    EXTRA_NOCTURNA_ESTADO: estadoExtraNocturna,
                    EXTRA_DIURNA_DOMINICAL_ESTADO: estadoExtraDiurnaDominical,
                    EXTRA_NOCTURNA_DOMINICAL_ESTADO: estadoExtraNocturnaDominical,
                    TOTAL_HORAS: totalHoras,
                    OBSERVACIONES: observacionesFallback || '--',
                    DETALLE_TURNOS: turnosDetallados
                };

                allRecords.push(fallbackRecord);
            });
        }

        // Agregar justificaciones con 0 horas trabajadas
        if (justificaciones && justificaciones.length > 0) {
            justificaciones.forEach(just => {
                // Validar que la fecha existe y es válida
                const fechaJustificacion = just.FECHA || just.fecha;
                if (!fechaJustificacion) {
                    console.warn('⚠️ Justificación sin fecha válida:', just);
                    return; // Saltar esta justificación
                }

                const esJornadaCompleta = parseInt(just.justificar_todos_turnos, 10) === 1;
                const horariosJustificados = this.getHorariosJustificados(just);
                const horariosTextoJust = this.formatHorariosProgramados(horariosJustificados, { marcarComoJustificado: true });

                let horarioAsignadoJust;
                if (esJornadaCompleta) {
                    horarioAsignadoJust = horariosTextoJust || 'Jornada completa (Justificada)';
                } else if (horariosTextoJust) {
                    horarioAsignadoJust = horariosTextoJust;
                } else if (typeof just.horas_programadas === 'string' && just.horas_programadas.trim() !== '') {
                    horarioAsignadoJust = `${just.horas_programadas.trim()} (Justificado)`;
                } else {
                    horarioAsignadoJust = 'Turno justificado';
                }

                const observacionJust = this.buildJustificacionObservacion(just, esJornadaCompleta ? 'Jornada completa' : 'Justificación parcial');

                allRecords.push({
                    tipo: 'justificacion',
                    ID_EMPLEADO: just.ID_EMPLEADO,
                    NOMBRE: just.NOMBRE,
                    APELLIDO: just.APELLIDO,
                    FECHA: fechaJustificacion,
                    ES_FESTIVO: just.ES_FESTIVO || false,
                    HORARIO_ASIGNADO: horarioAsignadoJust,
                    ENTRADA_HORA: '--',
                    SALIDA_HORA: '--',
                    HORAS_REGULARES: 0,
                    RECARGO_NOCTURNO: 0,
                    RECARGO_DOMINICAL_FESTIVO: 0,
                    RECARGO_NOCTURNO_DOMINICAL_FESTIVO: 0,
                    EXTRA_DIURNA: 0,
                    EXTRA_NOCTURNA: 0,
                    EXTRA_DIURNA_DOMINICAL_FESTIVA: 0,
                    EXTRA_NOCTURNA_DOMINICAL_FESTIVA: 0,
                    TOTAL_HORAS: 0,
                    OBSERVACIONES: observacionJust,
                    HORARIOS_PROGRAMADOS: horariosJustificados,
                    JUSTIFICACION_JORNADA_COMPLETA: esJornadaCompleta,
                    JUSTIFICACION_PARCIAL: !esJornadaCompleta,
                    JUSTIFICACIONES_DETALLE: [just]
                });
            });
        }

        // Ordenar por fecha y empleado (con validaciones de seguridad)
        allRecords.sort((a, b) => {
            // Validar que ambas fechas existen
            const fechaA = a.FECHA || '';
            const fechaB = b.FECHA || '';

            const dateCompare = fechaA.localeCompare(fechaB);
            if (dateCompare !== 0) return dateCompare;

            // Si las fechas son iguales, ordenar por nombre
            const nombreA = (a.NOMBRE || '') + ' ' + (a.APELLIDO || '');
            const nombreB = (b.NOMBRE || '') + ' ' + (b.APELLIDO || '');
            return nombreA.localeCompare(nombreB);
        });

        this.detailRecords = allRecords;
        this.processedRecords = allRecords;
        this.detailPagination.totalItems = allRecords.length;
        this.detailPagination.currentPage = 1;

        console.log('📋 Total records after processing:', allRecords.length);

        this.renderDetailTablePage(1);
    }

    renderDetailTablePage(page = this.detailPagination.currentPage) {
        this.initializeDetailPaginationControls();

        const tbody = document.getElementById('horasTableBody');
        if (!tbody) return;

        const totalItems = this.detailRecords.length;
        const pageSize = Number.isInteger(this.detailPagination.pageSize) && this.detailPagination.pageSize > 0
            ? this.detailPagination.pageSize
            : 20;

        if (this.detailPagination.pageSize !== pageSize) {
            this.detailPagination.pageSize = pageSize;
        }

        if (totalItems === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="16" class="no-data">
                        <i class="fas fa-info-circle"></i> No se encontraron registros para los filtros seleccionados.
                    </td>
                </tr>
            `;
            this.detailPagination.totalItems = 0;
            this.updateDetailPaginationControls();
            return;
        }

        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (!Number.isInteger(page) || page < 1) {
            page = 1;
        } else if (page > totalPages) {
            page = totalPages;
        }

        this.detailPagination.currentPage = page;
        this.detailPagination.totalItems = totalItems;

        const startIndex = (page - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);
        const pageRecords = this.detailRecords.slice(startIndex, endIndex);

        tbody.innerHTML = pageRecords.map(record => this.buildDetailRow(record)).join('');

        console.log(`✅ updateTable paginated render: página ${page} de ${totalPages}, ${pageRecords.length} filas mostradas`);

        this.updateDetailPaginationControls();
    }

    buildDetailRow(record) {
        const nombre = (record.NOMBRE || '').toString();
        const apellido = (record.APELLIDO || '').toString();
        const fullName = `${nombre} ${apellido}`.trim();
        const firstInitial = nombre.trim().charAt(0) || '';
        const lastInitial = apellido.trim().charAt(0) || '';
        const empleadoInitials = (firstInitial + lastInitial || '--').toUpperCase();

        const dayName = this.getDayName(record.FECHA);
        const dayClass = this.getDayClass(record.FECHA, record.ES_FESTIVO);
        const isJustification = record.tipo === 'justificacion';
        const isJustificationRow = isJustification || record.JUSTIFICACION_JORNADA_COMPLETA || record.JUSTIFICACION_PARCIAL;

        let justificationBadgeHtml = '';
        if (record.JUSTIFICACION_JORNADA_COMPLETA) {
            justificationBadgeHtml = '<div class="justification-badge">JORNADA COMPLETA JUSTIFICADA</div>';
        } else if (record.JUSTIFICACION_PARCIAL) {
            justificationBadgeHtml = '<div class="justification-badge">JUSTIFICACIÓN PARCIAL</div>';
        } else if (isJustification) {
            justificationBadgeHtml = '<div class="justification-badge">JUSTIFICACIÓN</div>';
        }

        const avatarClass = isJustificationRow ? 'justification' : '';
        const empleadoId = record.ID_EMPLEADO ? `#EMP${String(record.ID_EMPLEADO).padStart(3, '0')}` : '';

        return `
            <tr class="${isJustificationRow ? 'justification-row' : ''}">
                <td>
                    <div class="employee-info">
                        <div class="employee-avatar ${avatarClass}">${empleadoInitials}</div>
                        <div class="employee-details">
                            <div class="employee-name">${this.escapeHtml(fullName || 'Sin nombre')}</div>
                            <div class="employee-id">${empleadoId}</div>
                            ${justificationBadgeHtml}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="date-info">${this.formatDisplayDate(record.FECHA)}</div>
                </td>
                <td>
                    <div class="day-info ${dayClass}">${dayName}</div>
                    ${record.ES_FESTIVO === 'S' ? '<div class="day-info holiday">Festivo</div>' : ''}
                </td>
                <td class="horario-asignado">${record.HORARIO_ASIGNADO || '--'}</td>
                <td>${record.ENTRADA_HORA || '--'}</td>
                <td>${record.SALIDA_HORA || '--'}</td>
                <td class="hours-cell hours-regular">${this.formatHorasMinutos(record.HORAS_REGULARES || 0)}</td>
                <td class="hours-cell hours-nocturno">${this.formatHorasMinutos(record.RECARGO_NOCTURNO || 0)}</td>
                <td class="hours-cell hours-dominical">${this.formatHorasMinutos(record.RECARGO_DOMINICAL_FESTIVO || 0)}</td>
                <td class="hours-cell hours-nocturno-dominical">${this.formatHorasMinutos(record.RECARGO_NOCTURNO_DOMINICAL_FESTIVO || 0)}</td>
                <td class="hours-cell hours-extra-diurna ${(record.EXTRA_DIURNA || 0) > 0 ? this.getEstadoClass(record.EXTRA_DIURNA_ESTADO) : ''}">${this.formatHorasMinutos(record.EXTRA_DIURNA || 0)}</td>
                <td class="hours-cell hours-extra-nocturna ${(record.EXTRA_NOCTURNA || 0) > 0 ? this.getEstadoClass(record.EXTRA_NOCTURNA_ESTADO) : ''}">${this.formatHorasMinutos(record.EXTRA_NOCTURNA || 0)}</td>
                <td class="hours-cell hours-extra-diurna-dominical ${(record.EXTRA_DIURNA_DOMINICAL_FESTIVA || 0) > 0 ? this.getEstadoClass(record.EXTRA_DIURNA_DOMINICAL_ESTADO) : ''}">${this.formatHorasMinutos(record.EXTRA_DIURNA_DOMINICAL_FESTIVA || 0)}</td>
                <td class="hours-cell hours-extra-nocturna-dominical ${(record.EXTRA_NOCTURNA_DOMINICAL_FESTIVA || 0) > 0 ? this.getEstadoClass(record.EXTRA_NOCTURNA_DOMINICAL_ESTADO) : ''}">${this.formatHorasMinutos(record.EXTRA_NOCTURNA_DOMINICAL_FESTIVA || 0)}</td>
                <td class="hours-cell total-hours"><strong>${this.formatHorasMinutos(record.TOTAL_HORAS || 0)}</strong></td>
                <td class="observations">${record.OBSERVACIONES || '--'}</td>
            </tr>
        `;
    }

    updateDetailPaginationControls() {
        const container = document.getElementById('horasPaginationContainer');
        const info = document.getElementById('horasPaginationInfo');
        const buttonsContainer = document.getElementById('horasPaginationButtons');
        if (!container || !info || !buttonsContainer) return;

        const totalItems = this.detailRecords.length;
        if (totalItems === 0) {
            container.style.display = 'none';
            info.textContent = 'Sin registros';
            buttonsContainer.innerHTML = '';
            return;
        }

        container.style.display = 'flex';

        const currentPage = this.detailPagination.currentPage;
        const pageSize = this.detailPagination.pageSize;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        const start = (currentPage - 1) * pageSize + 1;
        const end = Math.min(start + pageSize - 1, totalItems);

        info.textContent = `Mostrando ${start} - ${end} de ${totalItems} registros`;
        buttonsContainer.innerHTML = this.generatePaginationButtonsHtml('detail', currentPage, totalPages);
    }

    initializeDetailPaginationControls() {
        const select = document.getElementById('horasPaginationSize');
        const buttonsContainer = document.getElementById('horasPaginationButtons');
        if (!select || !buttonsContainer) return;

        if (!this.detailPagination.controlsInitialized) {
            const defaultValue = parseInt(select.value, 10);
            if (Number.isInteger(defaultValue) && defaultValue > 0) {
                this.detailPagination.pageSize = defaultValue;
            } else {
                select.value = this.detailPagination.pageSize;
            }

            select.addEventListener('change', (event) => {
                const newSize = parseInt(event.target.value, 10);
                if (Number.isInteger(newSize) && newSize > 0) {
                    this.detailPagination.pageSize = newSize;
                    this.detailPagination.currentPage = 1;
                    this.renderDetailTablePage(1);
                }
            });

            buttonsContainer.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-page]');
                if (!button) return;

                const target = button.dataset.target || 'detail';
                const page = parseInt(button.dataset.page, 10);
                if (!Number.isInteger(page)) return;

                this.handlePaginationButtonClick(target, page);
            });

            this.detailPagination.controlsInitialized = true;
        } else if (String(select.value) !== String(this.detailPagination.pageSize)) {
            select.value = this.detailPagination.pageSize;
        }
    }

    generatePaginationButtonsHtml(target, currentPage, totalPages) {
        if (totalPages <= 1) {
            return '';
        }

        const buttons = [];

        if (currentPage > 1) {
            buttons.push(`
                <button class="pagination-btn prev" data-target="${target}" data-page="${currentPage - 1}" aria-label="Página anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `);
        }

        const maxButtons = this.paginationMaxButtons || 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);

        if (endPage - startPage + 1 < maxButtons) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        if (startPage > 1) {
            buttons.push(`<button class="pagination-btn" data-target="${target}" data-page="1">1</button>`);
            if (startPage > 2) {
                buttons.push('<span class="pagination-ellipsis">...</span>');
            }
        }

        for (let page = startPage; page <= endPage; page++) {
            buttons.push(`<button class="pagination-btn ${page === currentPage ? 'active' : ''}" data-target="${target}" data-page="${page}">${page}</button>`);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                buttons.push('<span class="pagination-ellipsis">...</span>');
            }
            buttons.push(`<button class="pagination-btn" data-target="${target}" data-page="${totalPages}">${totalPages}</button>`);
        }

        if (currentPage < totalPages) {
            buttons.push(`
                <button class="pagination-btn next" data-target="${target}" data-page="${currentPage + 1}" aria-label="Página siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `);
        }

        return buttons.join('');
    }

    handlePaginationButtonClick(target, page) {
        if (!Number.isInteger(page) || page < 1) return;

        if (target === 'extras') {
            if (page === this.horasExtrasPagination.currentPage) return;
            this.renderHorasExtrasPage(page);
        } else {
            if (page === this.detailPagination.currentPage) return;
            this.renderDetailTablePage(page);
        }
    }

    initializeHorasExtrasPaginationControls() {
        const select = document.getElementById('horasExtrasPaginationSize');
        const buttonsContainer = document.getElementById('horasExtrasPaginationButtons');
        if (!select || !buttonsContainer) return;

        if (!this.horasExtrasPagination.controlsInitialized) {
            const defaultValue = parseInt(select.value, 10);
            if (Number.isInteger(defaultValue) && defaultValue > 0) {
                this.horasExtrasPagination.pageSize = defaultValue;
            } else {
                select.value = this.horasExtrasPagination.pageSize;
            }

            select.addEventListener('change', (event) => {
                const newSize = parseInt(event.target.value, 10);
                if (Number.isInteger(newSize) && newSize > 0) {
                    this.horasExtrasPagination.pageSize = newSize;
                    this.horasExtrasPagination.currentPage = 1;
                    this.renderHorasExtrasPage(1);
                }
            });

            buttonsContainer.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-page]');
                if (!button) return;

                const target = button.dataset.target || 'extras';
                const page = parseInt(button.dataset.page, 10);
                if (!Number.isInteger(page)) return;

                this.handlePaginationButtonClick(target, page);
            });

            this.horasExtrasPagination.controlsInitialized = true;
        } else if (String(select.value) !== String(this.horasExtrasPagination.pageSize)) {
            select.value = this.horasExtrasPagination.pageSize;
        }
    }

    renderHorasExtrasPage(page = this.horasExtrasPagination.currentPage) {
        this.initializeHorasExtrasPaginationControls();

        const tbody = document.getElementById('horasExtrasTableBody');
        if (!tbody) return;

        const totalItems = this.horasExtrasRecords.length;
        const pageSize = Number.isInteger(this.horasExtrasPagination.pageSize) && this.horasExtrasPagination.pageSize > 0
            ? this.horasExtrasPagination.pageSize
            : 10;

        if (this.horasExtrasPagination.pageSize !== pageSize) {
            this.horasExtrasPagination.pageSize = pageSize;
        }

        if (totalItems === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="13" class="no-data">
                        <i class="fas fa-info-circle"></i> No hay horas extras para mostrar.
                    </td>
                </tr>
            `;
            this.horasExtrasPagination.totalItems = 0;
            this.updateHorasExtrasPaginationControls();
            this.updateSelectAllCheckbox();
            this.updateBulkActionsVisibility();
            return;
        }

        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (!Number.isInteger(page) || page < 1) {
            page = 1;
        } else if (page > totalPages) {
            page = totalPages;
        }

        this.horasExtrasPagination.currentPage = page;
        this.horasExtrasPagination.totalItems = totalItems;

        const startIndex = (page - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);
        const pageRecords = this.horasExtrasRecords.slice(startIndex, endIndex);

        tbody.innerHTML = pageRecords.map(extra => this.buildHorasExtrasRow(extra)).join('');

        tbody.querySelectorAll('.extra-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', this.onExtraCheckboxChange.bind(this));
        });

        this.updateHorasExtrasPaginationControls();
        this.updateSelectAllCheckbox();
        this.updateBulkActionsVisibility();
    }

    buildHorasExtrasRow(horaExtra) {
        const getSafeValue = (value, defaultValue = 'N/A') => (
            value !== null && value !== undefined && value !== '' ? value : defaultValue
        );

        const escape = (value) => this.escapeHtml(getSafeValue(value).toString());

        const idHoraExtra = this.extractHoraExtraId(horaExtra);
        const isChecked = idHoraExtra !== null && this.selectedHorasExtras.has(idHoraExtra);
        const checkboxAttributes = idHoraExtra !== null
            ? `value="${idHoraExtra}" ${isChecked ? 'checked' : ''}`
            : 'value="" disabled';

        const empleadoNombre = escape(horaExtra.empleado_nombre || horaExtra.empleado?.nombre);
        const sede = escape(horaExtra.sede);
        const establecimiento = escape(horaExtra.establecimiento);

        let horarioInfo = 'Sin horario asignado';
        if (horaExtra.horario) {
            const nombreHorario = escape(horaExtra.horario.nombre || horaExtra.horario.nombre_turno);
            const entrada = escape(horaExtra.horario.hora_entrada);
            const salida = escape(horaExtra.horario.hora_salida);
            horarioInfo = `${nombreHorario} (${entrada} - ${salida})`;
        } else if (horaExtra.id_empleado_horario) {
            horarioInfo = `Turno #${this.escapeHtml(String(horaExtra.id_empleado_horario))}`;
        }

        const fecha = escape(horaExtra.fecha);
        const horaInicio = escape(horaExtra.hora_inicio);
        const horaFin = escape(horaExtra.hora_fin);
        const horasExtras = horaExtra.horas_extras !== undefined
            ? this.formatHorasMinutos(parseFloat(horaExtra.horas_extras) || 0)
            : 'N/A';

    const tipoExtraText = this.escapeHtml(
        this.getTipoExtraText(horaExtra.tipo_extra || horaExtra.posicion || horaExtra.tipo_horario)
    );
    const tipoHorarioText = this.escapeHtml(this.getTipoHorarioText(horaExtra.tipo_horario));
    const rawEstadoAprobacion = horaExtra.estado_aprobacion || 'pendiente';
    const estadoAprobacion = this.escapeHtml(rawEstadoAprobacion);
    const estadoClass = this.getEstadoClass(rawEstadoAprobacion);

        return `
            <tr data-id="${idHoraExtra !== null ? idHoraExtra : ''}">
                <td>
                    <input type="checkbox" class="extra-checkbox" ${checkboxAttributes}>
                </td>
                <td>${empleadoNombre}</td>
                <td>${sede}</td>
                <td>${establecimiento}</td>
                <td>${horarioInfo}</td>
                <td>${fecha}</td>
                <td>${horaInicio}</td>
                <td>${horaFin}</td>
                <td>${horasExtras}</td>
                <td>${tipoExtraText}</td>
                <td>${tipoHorarioText}</td>
                <td><span class="estado-badge ${estadoClass}">${estadoAprobacion}</span></td>
                <td>
                    <div class="action-buttons">
                        ${(estadoAprobacion === 'pendiente') ? `
                            <button class="btn-icon btn-approve" onclick="horasTrabajadasInstance.aprobarHoraExtra(${idHoraExtra}, 'aprobar')">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn-icon btn-reject" onclick="horasTrabajadasInstance.aprobarHoraExtra(${idHoraExtra}, 'rechazar')">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }

    updateHorasExtrasPaginationControls() {
        const container = document.getElementById('horasExtrasPaginationContainer');
        const info = document.getElementById('horasExtrasPaginationInfo');
        const buttonsContainer = document.getElementById('horasExtrasPaginationButtons');
        if (!container || !info || !buttonsContainer) return;

        const totalItems = this.horasExtrasRecords.length;
        if (totalItems === 0) {
            container.style.display = 'none';
            info.textContent = 'Sin registros';
            buttonsContainer.innerHTML = '';
            return;
        }

        container.style.display = 'flex';

        const currentPage = this.horasExtrasPagination.currentPage;
        const pageSize = this.horasExtrasPagination.pageSize;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        const start = (currentPage - 1) * pageSize + 1;
        const end = Math.min(start + pageSize - 1, totalItems);

        info.textContent = `Mostrando ${start} - ${end} de ${totalItems} registros`;
        buttonsContainer.innerHTML = this.generatePaginationButtonsHtml('extras', currentPage, totalPages);
    }

    extractHoraExtraId(horaExtra) {
        if (!horaExtra) return null;
        const possibleKeys = ['id', 'ID', 'id_hora_extra', 'ID_HORA_EXTRA', 'id_horas_extra', 'ID_HORAS_EXTRA', 'id_registro', 'ID_REGISTRO'];
        for (const key of possibleKeys) {
            if (horaExtra[key] !== undefined && horaExtra[key] !== null && horaExtra[key] !== '') {
                const parsed = parseInt(horaExtra[key], 10);
                if (!Number.isNaN(parsed)) {
                    return parsed;
                }
            }
        }
        return null;
    }

    getDayName(dateStr) {
        const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const date = new Date(dateStr + 'T00:00:00');
        return days[date.getDay()];
    }

    getDayClass(dateStr, esFestivo) {
        const date = new Date(dateStr + 'T00:00:00');
        if (esFestivo === 'S') return 'holiday';
        if (date.getDay() === 0) return 'sunday';
        return '';
    }

    formatDisplayDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('es-CO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    buildJustificacionKey(empleadoId, fecha) {
        if (empleadoId === undefined || empleadoId === null || !fecha) {
            return null;
        }
        return `${String(empleadoId).trim()}__${fecha}`;
    }

    groupJustificacionesByEmpleadoDia(justificaciones) {
        const map = new Map();
        if (!Array.isArray(justificaciones)) {
            return map;
        }

        justificaciones.forEach(just => {
            const fecha = just?.FECHA || just?.fecha;
            const empleadoId = just?.ID_EMPLEADO ?? just?.id_empleado ?? just?.empleado_id;
            const key = this.buildJustificacionKey(empleadoId, fecha);
            if (!key) return;
            if (!map.has(key)) {
                map.set(key, []);
            }
            map.get(key).push(just);
        });

        return map;
    }

    extractJustificacionTurnoIds(justificacion) {
        if (!justificacion) return [];
        const ids = [];
        const pushId = value => {
            if (value === undefined || value === null) return;
            const normalized = String(value).trim();
            if (normalized) ids.push(normalized);
        };

        pushId(justificacion.turno_id);

        let turnos = justificacion.turnos_ids;
        if (typeof turnos === 'string' && turnos.trim() !== '') {
            try {
                turnos = JSON.parse(turnos);
            } catch (error) {
                turnos = turnos.split(/[\s,;]+/).filter(Boolean);
            }
        }

        if (Array.isArray(turnos)) {
            turnos.forEach(pushId);
        } else if (typeof turnos === 'number') {
            pushId(turnos);
        }

        return ids;
    }

    getHorariosJustificados(justificacion, fallbackHorarios = []) {
        if (!justificacion) return [];

        const baseHorarios = Array.isArray(justificacion.horarios_programados) && justificacion.horarios_programados.length > 0
            ? justificacion.horarios_programados
            : (Array.isArray(fallbackHorarios) ? fallbackHorarios : []);

        const turnoIds = this.extractJustificacionTurnoIds(justificacion);
        const normalize = value => String(value ?? '').trim();

        if (turnoIds.length === 0) {
            return Array.isArray(baseHorarios) ? baseHorarios : [];
        }

        const turnoSet = new Set(turnoIds.map(normalize));

        const filtrados = Array.isArray(baseHorarios)
            ? baseHorarios.filter(horario => horario && (turnoSet.has(normalize(horario.ID_EMPLEADO_HORARIO)) || turnoSet.has(normalize(horario.id_empleado_horario))))
            : [];

        if (filtrados.length > 0) {
            return filtrados;
        }

        const horasProgramadas = justificacion.horas_programadas;
        if (typeof horasProgramadas === 'string' && horasProgramadas.trim() !== '') {
            const segmentos = horasProgramadas.split(/[,;\n]+/);
            const sinteticos = segmentos.map(segmento => {
                const partes = segmento.split('-');
                if (partes.length < 2) return null;
                const entrada = partes[0].trim();
                const salida = partes[1].trim();
                if (!entrada || !salida) return null;

                const normalizarHora = hora => hora.length >= 5 ? hora.substring(0, 5) : hora;
                return {
                    HORA_ENTRADA: normalizarHora(entrada),
                    HORA_SALIDA: normalizarHora(salida),
                    NOMBRE_TURNO: justificacion.turno_nombre || null,
                    ID_EMPLEADO_HORARIO: null,
                    ES_SINTETICO: true
                };
            }).filter(Boolean);

            if (sinteticos.length > 0) {
                return sinteticos;
            }
        }

        return Array.isArray(baseHorarios) ? baseHorarios : [];
    }

    collectHorariosJustificados(justificaciones, fallbackHorarios = []) {
        if (!Array.isArray(justificaciones) || justificaciones.length === 0) {
            return [];
        }

        const acumulados = [];
        const firmas = new Set();

        justificaciones.forEach(justificacion => {
            const horarios = this.getHorariosJustificados(justificacion, fallbackHorarios);
            if (!Array.isArray(horarios) || horarios.length === 0) return;

            horarios.forEach(horario => {
                const firma = [
                    horario.ID_EMPLEADO_HORARIO ?? horario.id_empleado_horario ?? '',
                    horario.HORA_ENTRADA ?? '',
                    horario.HORA_SALIDA ?? '',
                    horario.NOMBRE_TURNO ?? ''
                ].map(valor => String(valor ?? '').trim()).join('__');

                if (!firmas.has(firma)) {
                    firmas.add(firma);
                    acumulados.push(horario);
                }
            });
        });

        return acumulados;
    }

    formatHorariosProgramados(horarios, opciones = {}) {
        const { marcarComoJustificado = false } = opciones;
        if (!Array.isArray(horarios) || horarios.length === 0) {
            return '';
        }

        const segmentos = [];
        const vistos = new Set();

        horarios.forEach(horario => {
            if (!horario) return;

            const entrada = horario.HORA_ENTRADA ? horario.HORA_ENTRADA.substring(0, 5) : '--';
            const salida = horario.HORA_SALIDA ? horario.HORA_SALIDA.substring(0, 5) : '--';
            const nombreTurno = horario.NOMBRE_TURNO ? ` (${horario.NOMBRE_TURNO})` : '';
            const clave = `${entrada}-${salida}${nombreTurno}`;

            if (vistos.has(clave)) return;
            vistos.add(clave);

            let segmento = `${entrada} - ${salida}${nombreTurno}`.trim();
            if (marcarComoJustificado) {
                segmento += ' (Justificado)';
            }

            segmentos.push(segmento);
        });

        return segmentos.join('<br>');
    }

    buildJustificacionObservacion(justificacion, prefijo = 'Justificación') {
        if (!justificacion) {
            return prefijo;
        }

        const motivo = justificacion.motivo || prefijo;
        const detalle = justificacion.detalle_adicional ? ` - ${justificacion.detalle_adicional}` : '';
        return `${prefijo}: ${motivo}${detalle}`;
    }

    clearFilters() {
        document.getElementById('selectSede').value = '';
        document.getElementById('selectEstablecimiento').value = '';
        
        // Clear employee selection
        this.selectedEmpleados = [];
        this.updateEmpleadosButton();
        
        // Reset to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fechaDesde').value = today;
        document.getElementById('fechaHasta').value = today;
        
        // Clear active quick filter
        document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
        
        // Reset establishments and employees
        this.loadEstablecimientos('');
        this.loadEmpleados();
        
        this.applyFilters();
    }

    refreshData() {
        this.applyFilters(); // Use AJAX version
    }

    extractTableData() {
        const tbody = document.getElementById('horasTableBody');
        if (!tbody) {
            console.warn('Tabla de horas no encontrada');
            return [];
        }

        const rows = tbody.querySelectorAll('tr');
        const data = [];

        rows.forEach(row => {
            // Saltar filas sin datos (como "No se encontraron registros")
            if (row.querySelector('.no-data')) {
                return;
            }

            const cells = row.querySelectorAll('td');
            if (cells.length < 16) { // La tabla debe tener al menos 16 columnas
                return;
            }

            // Extraer datos de cada celda
            const rowData = {
                empleado: this.extractTextFromCell(cells[0]), // Nombre del empleado
                fecha: this.extractTextFromCell(cells[1]), // Fecha
                dia: this.extractTextFromCell(cells[2]), // Día de la semana
                horario_asignado: this.extractTextFromCell(cells[3]), // Horario asignado
                entrada: this.extractTextFromCell(cells[4]), // Hora entrada
                salida: this.extractTextFromCell(cells[5]), // Hora salida
                horas_regulares: this.extractNumberFromCell(cells[6]), // Horas regulares
                recargo_nocturno: this.extractNumberFromCell(cells[7]), // Recargo nocturno
                recargo_dominical: this.extractNumberFromCell(cells[8]), // Recargo dominical
                recargo_nocturno_dominical: this.extractNumberFromCell(cells[9]), // Recargo nocturno dominical
                extra_diurna: this.extractNumberFromCell(cells[10]), // Extra diurna
                extra_nocturna: this.extractNumberFromCell(cells[11]), // Extra nocturna
                extra_diurna_dominical: this.extractNumberFromCell(cells[12]), // Extra diurna dominical
                extra_nocturna_dominical: this.extractNumberFromCell(cells[13]), // Extra nocturna dominical
                total_horas: this.extractNumberFromCell(cells[14]), // Total horas
                observaciones: this.extractTextFromCell(cells[15]), // Observaciones
                es_justificacion: row.classList.contains('justification-row') // Si es justificación
            };

            data.push(rowData);
        });

        console.log(`📋 Extraídos ${data.length} registros de la tabla`);
        return data;
    }

    getFiltersForExport() {
        const getOptionLabel = (selectId) => {
            const select = document.getElementById(selectId);
            if (!select) return null;
            const option = select.options[select.selectedIndex];
            if (!option || option.value === '') return null;
            return option.textContent.trim();
        };

        return {
            sede: getOptionLabel('selectSede'),
            sedeId: this.currentFilters.sede || null,
            establecimiento: getOptionLabel('selectEstablecimiento'),
            establecimientoId: this.currentFilters.establecimiento || null,
            fechaDesde: this.currentFilters.fechaDesde,
            fechaHasta: this.currentFilters.fechaHasta,
            empleados: Array.isArray(this.currentFilters.empleados)
                ? [...this.currentFilters.empleados]
                : []
        };
    }

    setExportButtonState(isLoading) {
        const button = document.getElementById('btnExportExcel');
        if (!button) return;

        const icon = button.querySelector('i');

        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
            if (icon) {
                icon.classList.add('fa-spin');
            }
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            if (icon) {
                icon.classList.remove('fa-spin');
            }
        }
    }

    async exportToExcel() {
        if (this.isExporting) {
            return;
        }

        if (!Array.isArray(this.processedRecords) || this.processedRecords.length === 0) {
            this.showError('No hay registros para exportar. Aplique filtros y recargue la tabla primero.');
            return;
        }

        this.isExporting = true;
        this.setExportButtonState(true);

        try {
            const payload = {
                filters: this.getFiltersForExport(),
                processedData: this.processedRecords
            };

            const response = await fetch('api/horas-trabajadas/export-excel-styled.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const contentType = response.headers.get('Content-Type') || '';

            if (!response.ok) {
                let errorMessage = 'No se pudo generar el archivo Excel.';
                if (contentType.includes('application/json')) {
                    const errorPayload = await response.json();
                    errorMessage = errorPayload.message || errorMessage;
                } else {
                    const errorText = await response.text();
                    if (errorText) {
                        errorMessage += ` Detalles: ${errorText}`;
                    }
                }
                throw new Error(errorMessage);
            }

            if (contentType.includes('application/json')) {
                const errorPayload = await response.json();
                throw new Error(errorPayload.message || 'El servidor no devolvió el archivo Excel.');
            }

            const blob = await response.blob();
            if (!blob || blob.size === 0) {
                throw new Error('El archivo Excel generado está vacío.');
            }

            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            const timestamp = new Date().toISOString().replace(/[:T]/g, '-').split('.')[0];
            link.href = downloadUrl;
            link.download = `horas_trabajadas_${timestamp}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);

            // Se elimina la notificación de éxito a solicitud del usuario.
        } catch (error) {
            console.error('Error al exportar horas trabajadas:', error);
            this.showError(error.message || 'No se pudo generar el archivo Excel.');
        } finally {
            this.setExportButtonState(false);
            this.isExporting = false;
        }
    }

    extractTextFromCell(cell) {
        // Extraer texto limpio de una celda, removiendo HTML
        const text = cell.textContent || cell.innerText || '';
        return text.trim().replace(/\s+/g, ' ');
    }

    extractNumberFromCell(cell) {
        // Extraer números decimales de celdas de horas (formato: "8h 30m" -> 8.5)
        const text = this.extractTextFromCell(cell);
        const match = text.match(/(\d+)h\s*(\d+)m/);
        if (match) {
            const horas = parseInt(match[1]);
            const minutos = parseInt(match[2]);
            return horas + (minutos / 60);
        }
        // Si no hay match, intentar extraer solo número
        const numMatch = text.match(/(\d+(?:\.\d+)?)/);
        return numMatch ? parseFloat(numMatch[1]) : 0;
    }

    showDiaCivicoModal() {
        document.getElementById('modalDiaCivico').style.display = 'block';
        document.getElementById('fechaDiaCivico').focus();
    }

    hideDiaCivicoModal() {
        document.getElementById('modalDiaCivico').style.display = 'none';
        document.getElementById('formDiaCivico').reset();
    }

    async submitDiaCivico(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('api/horas-trabajadas/register-dia-civico.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Día cívico registrado correctamente');
                this.hideDiaCivicoModal();
                this.refreshData();
            } else {
                this.showError(data.message || 'Error al registrar el día cívico');
            }
        } catch (error) {
            console.error('Error registering dia civico:', error);
            this.showError('Error al registrar el día cívico');
        }
    }

    showLoading(show) {
        const tbody = document.getElementById('horasTableBody');
        if (show) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="no-data">
                        <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                    </td>
                </tr>
            `;
        }
    }

    showError(message) {
        // You can implement a toast notification system here
        console.error(message);
        alert(message); // Temporary implementation
    }

    showSuccess(message) {
        // You can implement a toast notification system here
        console.log(message);
        alert(message); // Temporary implementation
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ===== OVERTIME APPROVAL METHODS =====

    showAprobacionHorasExtrasModal() {
        // Check if user has ADMIN role
        this.checkAdminPermission().then(hasPermission => {
            if (!hasPermission) {
                this.showError('No tiene permisos para acceder a la aprobación de horas extras. Se requiere rol ADMIN.');
                return;
            }

            document.getElementById('modalAprobacionHorasExtras').style.display = 'block';
            this.loadSedesExtras();

            // Configurar filtros por defecto para el día actual y estado pendiente
            this.setDefaultFiltersForApproval();

            // Esperar un poco para que los filtros se inicialicen, luego filtrar y generar automáticamente
            setTimeout(() => {
                this.filtrarYGenerarHorasExtrasAutomaticamente();
            }, 100);
        });
    }

    hideAprobacionHorasExtrasModal() {
        document.getElementById('modalAprobacionHorasExtras').style.display = 'none';
        this.selectedHorasExtras.clear();
        this.updateBulkActionsVisibility();
    }

    async checkAdminPermission() {
        try {
            const response = await fetch('auth/check-role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ required_role: 'ADMIN' })
            });
            const data = await response.json();
            return data.has_permission || false;
        } catch (error) {
            console.error('Error checking admin permission:', error);
            return false;
        }
    }

    async checkAndShowAdminFeatures() {
        try {
            console.log('🔍 Checking admin permissions...');
            const response = await fetch('auth/check-role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    required_role: 'ADMIN'
                })
            });

            const data = await response.json();
            console.log('📋 Admin check response:', data);

            if (data.success && data.has_permission) {
                console.log('✅ User has admin role, showing admin features');
                // Show admin features
                const btnAprobacion = document.getElementById('btnAprobacionHorasExtras');
                if (btnAprobacion) {
                    btnAprobacion.style.display = 'inline-flex';
                }

                // Bind admin event listeners
                this.bindAdminEvents();
            } else {
                console.log('❌ User does not have ADMIN role or check failed');
                console.log('   - Success:', data.success);
                console.log('   - Has permission:', data.has_permission);
                console.log('   - User role:', data.user_role);
                console.log('   - Required role:', data.required_role);
            }
        } catch (error) {
            console.error('Error checking admin permissions:', error);
        }
    }

    bindAdminEvents() {
        const btnAprobacion = document.getElementById('btnAprobacionHorasExtras');
        if (btnAprobacion) {
            btnAprobacion.addEventListener('click', this.showAprobacionHorasExtrasModal.bind(this));
        }
    }

    showHorasExtrasModal() {
        // This will be implemented when we add the modal
        console.log('Showing overtime approval modal');
        // TODO: Implement modal display
    }

    loadSedesExtras() {
        const sedeSelect = document.getElementById('filtroSedeExtras');
        sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';

        // Copy sedes from main filter
        const mainSedeSelect = document.getElementById('selectSede');
        Array.from(mainSedeSelect.options).forEach(option => {
            if (option.value) {
                sedeSelect.innerHTML += `<option value="${option.value}">${option.text}</option>`;
            }
        });
    }

    onSedeExtrasChange() {
        const sedeId = document.getElementById('filtroSedeExtras').value;
        this.loadEstablecimientosExtras(sedeId);
    }

    loadEstablecimientosExtras(sedeId) {
        const establecimientoSelect = document.getElementById('filtroEstablecimientoExtras');
        establecimientoSelect.innerHTML = '<option value="">Todos los establecimientos</option>';

        if (!sedeId) return;

        // Load establishments for the selected sede
        fetch(`api/get-establecimientos.php?sede_id=${sedeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.establecimientos.forEach(est => {
                        establecimientoSelect.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error loading establishments:', error));
    }

    setDefaultDateRangeExtras() {
        const today = new Date();
        const lastMonth = new Date();
        lastMonth.setMonth(today.getMonth() - 1);

        document.getElementById('filtroFechaDesdeExtras').value = lastMonth.toISOString().split('T')[0];
        document.getElementById('filtroFechaHastaExtras').value = today.toISOString().split('T')[0];
    }

    setDefaultFiltersForApproval() {
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];

        // Configurar filtros por defecto para el día actual
        document.getElementById('filtroFechaDesdeExtras').value = todayStr;
        document.getElementById('filtroFechaHastaExtras').value = todayStr;

        // Configurar estado como pendiente
        document.getElementById('filtroEstadoExtras').value = 'pendiente';

        // Limpiar selecciones de empleados
        this.selectedEmpleadosExtras = [];
        this.updateEmpleadosExtrasCount();

        // Activar botones rápidos correspondientes
        this.activateQuickFilterButtons();
    }

    activateQuickFilterButtons() {
        // Activar botón de filtro de fechas para "hoy" (0 días)
        document.querySelectorAll('.btn-quick[data-dias]').forEach(btn => {
            btn.classList.remove('active');
        });
        const todayButton = document.querySelector('[data-dias="0"]');
        if (todayButton) {
            todayButton.classList.add('active');
        }

        // Activar botón de filtro de estado para "pendiente"
        document.querySelectorAll('.btn-quick[data-estado]').forEach(btn => {
            btn.classList.remove('active');
        });
        const pendienteButton = document.querySelector('[data-estado="pendiente"]');
        if (pendienteButton) {
            pendienteButton.classList.add('active');
        }
    }

    async filtrarHorasExtras(skipAutoGeneration = false) {
        // Primero generar automáticamente las nuevas horas extras (si no se especificó skip)
        if (!skipAutoGeneration) {
            console.log('🔄 Generando horas extras automáticamente antes de filtrar...');
            await this.generarHorasExtrasDesdeConsultaAutomatica();
        }

        // Después de generar (o si se saltó), cargar las horas extras con las nuevas incluidas
        const filtros = {
            fechaDesde: document.getElementById('filtroFechaDesdeExtras').value,
            fechaHasta: document.getElementById('filtroFechaHastaExtras').value,
            sede_id: document.getElementById('filtroSedeExtras').value,
            establecimiento_id: document.getElementById('filtroEstablecimientoExtras').value,
            estado_aprobacion: document.getElementById('filtroEstadoExtras').value,
            empleados: this.selectedEmpleadosExtras
        };

        this.loadHorasExtras(filtros);
    }

    // Nueva función para filtrar y generar horas extras automáticamente al abrir el modal
    async filtrarYGenerarHorasExtrasAutomaticamente() {
        // Filtrar horas extras (que ahora incluye generación automática interna)
        await this.filtrarHorasExtras();
    }

    // Nueva función para generar horas extras desde consulta independiente
    async generarHorasExtrasDesdeConsulta() {
        const fechaDesde = document.getElementById('filtroFechaDesdeExtras').value;
        const fechaHasta = document.getElementById('filtroFechaHastaExtras').value;
        const sedeId = document.getElementById('filtroSedeExtras').value;
        const establecimientoId = document.getElementById('filtroEstablecimientoExtras').value;

        if (!fechaDesde || !fechaHasta) {
            this.showError('Debe seleccionar un rango de fechas para la consulta');
            return;
        }

        // Mostrar indicador de carga
        this.showSuccess('Consultando asistencia y generando horas extras...');

        try {
            // Usar la API existente get-horas.php para obtener los datos calculados
            const formData = new FormData();
            formData.append('fechaDesde', fechaDesde);
            formData.append('fechaHasta', fechaHasta);

            // Agregar empleados seleccionados, o todos si no hay selección
            if (this.selectedEmpleadosExtras.length > 0) {
                this.selectedEmpleadosExtras.forEach(empleadoId => {
                    formData.append('empleados[]', empleadoId);
                });
            }

            if (sedeId) formData.append('sede_id', sedeId);
            if (establecimientoId) formData.append('establecimiento_id', establecimientoId);

            const response = await fetch('api/horas-trabajadas/get-horas.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                console.error('Error al parsear respuesta JSON de get-horas.php:', jsonError);
                this.showError('Error de conexión: La respuesta del servidor no es válida. Por favor, inténtelo de nuevo.');
                return;
            }

            if (result.success && result.data) {
                // Procesar los datos para extraer horas extras y filtrar las que ya existen
                const horasExtrasData = this.procesarHorasExtrasDesdeGetHoras(result.data);

                console.log('Horas extras encontradas en los datos:', horasExtrasData.length);

                if (horasExtrasData.length === 0) {
                    this.showSuccess('No se encontraron horas extras en el rango de fechas seleccionado, o todas las horas extras ya están en proceso de aprobación.');
                    return;
                }

                // Enviar las horas extras a la API de aprobación
                const insertResponse = await fetch('api/horas-trabajadas/insertar-horas-extras-modal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        horas_extras: horasExtrasData
                    }),
                    credentials: 'same-origin'
                });

                console.log('Respuesta HTTP de inserción:', {
                    status: insertResponse.status,
                    statusText: insertResponse.statusText,
                    ok: insertResponse.ok,
                    headers: Object.fromEntries(insertResponse.headers.entries())
                });

                if (!insertResponse.ok) {
                    const errorText = await insertResponse.text();
                    console.error('Error HTTP en inserción:', errorText);
                    this.showError(`Error HTTP ${insertResponse.status}: ${insertResponse.statusText}. Respuesta: ${errorText}`);
                    return;
                }

                let insertResult;
                try {
                    const responseText = await insertResponse.text();
                    console.log('Contenido de respuesta antes de parsear:', responseText);

                    // Intentar parsear como JSON
                    insertResult = JSON.parse(responseText);
                    console.log('Respuesta parseada exitosamente:', insertResult);
                } catch (jsonError) {
                    console.error('Error al parsear respuesta JSON:', jsonError);
                    console.error('Contenido que falló al parsear:', await insertResponse.clone().text());
                    this.showError('Error de conexión: La respuesta del servidor no es válida. Por favor, inténtelo de nuevo.');
                    return;
                }

                if (insertResult.success) {
                    // Verificar si se insertaron nuevas horas extras o todas ya existían
                    const resultado = insertResult.resultado || {};
                    const nuevasInsertadas = resultado.insertadas || 0;
                    const yaExistian = resultado.existentes || 0;

                    if (nuevasInsertadas > 0) {
                        const totalEncontradas = nuevasInsertadas + yaExistian;
                        this.showSuccess(`✅ Proceso completado exitosamente.\n\n📊 Resumen:\n• Total de horas extras encontradas: ${totalEncontradas}\n• Nuevas insertadas en la base de datos: ${nuevasInsertadas}\n• Ya existían (no se duplicaron): ${yaExistian > 0 ? yaExistian : 0}\n\nTodas las horas extras nuevas están ahora pendientes de aprobación.`);
                    } else if (yaExistian > 0) {
                        this.showSuccess(`ℹ️ No se insertaron nuevas horas extras.\n\n📊 Resumen:\n• Total de horas extras encontradas: ${yaExistian}\n• Todas ya existían en la base de datos\n• No se crearon registros duplicados\n\nTodas las horas extras ya están registradas en el sistema.`);
                    } else {
                        this.showSuccess('ℹ️ No se encontraron horas extras para procesar en el rango de fechas seleccionado.');
                    }

                    // Recargar la tabla de horas extras para mostrar las nuevas generadas
                    this.filtrarHorasExtras();

                    // También actualizar la tabla principal si está disponible
                    if (typeof this.loadHorasTrabajadasAjax === 'function') {
                        await this.loadHorasTrabajadasAjax();
                    }
                } else {
                    this.showError(insertResult.message || 'Error al procesar horas extras');
                }
            } else {
                this.showError('Error al consultar los datos de asistencia');
            }
        } catch (error) {
            console.error('Error generando horas extras:', error);
            this.showError('Error de conexión al generar horas extras: ' + error.message);
        }
    }

    // Nueva función para generar horas extras automáticamente (sin mensajes de usuario)
    async generarHorasExtrasDesdeConsultaAutomatica() {
        const fechaDesde = document.getElementById('filtroFechaDesdeExtras').value;
        const fechaHasta = document.getElementById('filtroFechaHastaExtras').value;
        const sedeId = document.getElementById('filtroSedeExtras').value;
        const establecimientoId = document.getElementById('filtroEstablecimientoExtras').value;

        // Solo proceder si hay fechas configuradas
        if (!fechaDesde || !fechaHasta) {
            console.log('Generación automática omitida: no hay fechas configuradas');
            return;
        }

        console.log('🔄 Generando horas extras automáticamente para el rango:', fechaDesde, 'a', fechaHasta);

        try {
            // Usar la API existente get-horas.php para obtener los datos calculados
            const formData = new FormData();
            formData.append('fechaDesde', fechaDesde);
            formData.append('fechaHasta', fechaHasta);

            // Agregar empleados seleccionados, o todos si no hay selección
            if (this.selectedEmpleadosExtras.length > 0) {
                this.selectedEmpleadosExtras.forEach(empleadoId => {
                    formData.append('empleados[]', empleadoId);
                });
            }

            if (sedeId) formData.append('sede_id', sedeId);
            if (establecimientoId) formData.append('establecimiento_id', establecimientoId);

            const response = await fetch('api/horas-trabajadas/get-horas.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                console.error('Error al parsear respuesta JSON de get-horas.php:', jsonError);
                return; // No mostrar error al usuario en modo automático
            }

            if (result.success && result.data) {
                // Procesar los datos para extraer horas extras y filtrar las que ya existen
                const horasExtrasData = this.procesarHorasExtrasDesdeGetHoras(result.data);

                console.log('Horas extras encontradas automáticamente:', horasExtrasData.length);

                if (horasExtrasData.length === 0) {
                    console.log('ℹ️ No se encontraron nuevas horas extras para generar automáticamente');
                    return; // Silenciosamente, sin mensaje al usuario
                }

                // Enviar las horas extras a la API de aprobación
                const insertResponse = await fetch('api/horas-trabajadas/insertar-horas-extras-modal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        horas_extras: horasExtrasData
                    }),
                    credentials: 'same-origin'
                });

                if (!insertResponse.ok) {
                    console.error('Error HTTP en inserción automática:', insertResponse.status, insertResponse.statusText);
                    return; // No mostrar error al usuario en modo automático
                }

                let insertResult;
                try {
                    const responseText = await insertResponse.text();
                    insertResult = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Error al parsear respuesta JSON en inserción automática:', jsonError);
                    return;
                }

                if (insertResult.success) {
                    const resultado = insertResult.resultado || {};
                    const nuevasInsertadas = resultado.insertadas || 0;
                    const yaExistian = resultado.existentes || 0;

                    if (nuevasInsertadas > 0) {
                        console.log(`✅ Generación automática completada: ${nuevasInsertadas} nuevas horas extras insertadas`);
                        // No recargar aquí - filtrarHorasExtras() se encargará de mostrar los datos actualizados
                    } else {
                        console.log('ℹ️ Generación automática: no se insertaron nuevas horas extras (todas ya existían)');
                    }
                } else {
                    console.error('Error en inserción automática:', insertResult.message);
                }
            } else {
                console.log('ℹ️ Generación automática: no se pudieron obtener datos de asistencia');
            }
        } catch (error) {
            console.error('Error en generación automática de horas extras:', error);
        }
    }

    // Función para procesar los datos de get-horas.php y extraer horas extras
    procesarHorasExtrasDesdeGetHoras(data) {
        const horasExtras = [];

        console.log('Procesando datos de get-horas.php:', data);

        // La estructura correcta es data.horas_extras_por_fecha
        // que es un objeto donde las claves son fechas y los valores son arrays de horas extras
        if (data.horas_extras_por_fecha) {
            console.log('Encontrado horas_extras_por_fecha:', data.horas_extras_por_fecha);

            Object.entries(data.horas_extras_por_fecha).forEach(([fecha, extrasDelDia]) => {
                console.log(`Procesando fecha ${fecha} con ${Array.isArray(extrasDelDia) ? extrasDelDia.length : 'N/A'} extras`);

                if (Array.isArray(extrasDelDia)) {
                    extrasDelDia.forEach((horaExtra, index) => {
                        console.log(`Procesando hora extra ${index}:`, horaExtra);

                        // Solo procesar horas extras que no sean cero
                        if (horaExtra.horas_extras && parseFloat(horaExtra.horas_extras) > 0) {
                            horasExtras.push({
                                id_empleado: horaExtra.id_empleado || horaExtra.empleado_id || horaExtra.id,
                                id_empleado_horario: horaExtra.id_empleado_horario || null,
                                fecha: fecha,
                                hora_inicio: horaExtra.hora_inicio,
                                hora_fin: horaExtra.hora_fin,
                                horas_extras: parseFloat(horaExtra.horas_extras),
                                tipo_extra: horaExtra.tipo_extra || horaExtra.posicion || 'despues',
                                tipo_horario: horaExtra.tipo_horario || 'diurno'
                            });
                        } else {
                            console.log(`Hora extra ${index} descartada: horas_extras = ${horaExtra.horas_extras}`);
                        }
                    });
                } else {
                    console.log(`extrasDelDia no es array para fecha ${fecha}:`, extrasDelDia);
                }
            });
        } else {
            console.log('No se encontró horas_extras_por_fecha en los datos');
        }

        console.log('Horas extras procesadas desde get-horas.php:', horasExtras.length, 'horas extras encontradas');
        console.log('Detalle de horas extras:', horasExtras);

        return horasExtras;
    }

    quickFilterHorasExtras(estado) {
        // Update the filter select
        document.getElementById('filtroEstadoExtras').value = estado;

        // Update button states for estado
        document.querySelectorAll('.btn-quick[data-estado]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-estado="${estado}"]`).classList.add('active');

        // Apply the filter
        this.filtrarHorasExtras();
    }

    quickFilterFechas(periodo) {
        const fechaDesde = document.getElementById('filtroFechaDesdeExtras');
        const fechaHasta = document.getElementById('filtroFechaHastaExtras');
        const hoy = new Date();

        // Update button states for dates
        document.querySelectorAll('.btn-quick[data-dias]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-dias="${periodo}"]`).classList.add('active');

        if (periodo === 'limpiar') {
            fechaDesde.value = '';
            fechaHasta.value = '';
        } else if (periodo === 0) { // Hoy
            const hoyStr = hoy.toISOString().split('T')[0];
            fechaDesde.value = hoyStr;
            fechaHasta.value = hoyStr;
        } else if (periodo === 'mes') { // Mes actual
            const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            fechaDesde.value = primerDia.toISOString().split('T')[0];
            fechaHasta.value = ultimoDia.toISOString().split('T')[0];
        } else { // Últimos X días
            const fechaInicio = new Date(hoy);
            fechaInicio.setDate(hoy.getDate() - periodo + 1);
            fechaDesde.value = fechaInicio.toISOString().split('T')[0];
            fechaHasta.value = hoy.toISOString().split('T')[0];
        }

        // Apply the filter
        this.filtrarHorasExtras();
    }

    // Función para limpiar filtros específicos de horas extras (sede, establecimiento, estado)
    limpiarFiltrosExtras() {
        // Limpiar sede
        document.getElementById('filtroSedeExtras').value = '';

        // Limpiar establecimiento
        document.getElementById('filtroEstablecimientoExtras').value = '';

        // Limpiar estado (resetear a vacío para mostrar todos)
        document.getElementById('filtroEstadoExtras').value = '';

        // Limpiar empleados seleccionados para horas extras
        this.selectedEmpleadosExtras = [];
        this.updateEmpleadosExtrasCount();

        // Limpiar fechas (poner vacías)
        document.getElementById('filtroFechaDesdeExtras').value = '';
        document.getElementById('filtroFechaHastaExtras').value = '';

        // Resetear establecimientos dependientes
        this.loadEstablecimientosExtras('');

        // Activar botones rápidos específicos para estado consistente
        // Limpiar fechas
        document.querySelectorAll('.btn-quick[data-dias]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById('btnQuickLimpiarFechas').classList.add('active');

        // Todas los estados
        document.querySelectorAll('.btn-quick[data-estado]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById('btnQuickTodas').classList.add('active');

        // Aplicar filtros con los valores limpios
        this.filtrarHorasExtras();
    }

    async loadHorasExtras(filtros) {
        const tbody = document.getElementById('horasExtrasTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="13" class="no-data">
                    <i class="fas fa-spinner fa-spin"></i> Cargando horas extras...
                </td>
            </tr>
        `;

        try {
            const formData = new FormData();
            Object.keys(filtros).forEach(key => {
                if (Array.isArray(filtros[key])) {
                    filtros[key].forEach(value => formData.append(`${key}[]`, value));
                } else if (filtros[key]) {
                    formData.append(key, filtros[key]);
                }
            });

            const response = await fetch('api/horas-trabajadas/get-horas-extras-pendientes.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            console.log('🔍 Respuesta del API:', data);

            if (data.success) {
                console.log('✅ API exitoso, llamando a renderHorasExtrasTable con:', data.data);
                this.renderHorasExtrasTable(data.data);
            } else {
                console.error('❌ Error del API:', data.message);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="13" class="no-data">
                            <i class="fas fa-exclamation-triangle"></i> ${data.message || 'Error al cargar horas extras'}
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Error loading overtime hours:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="13" class="no-data">
                        <i class="fas fa-exclamation-triangle"></i> Error de conexión
                    </td>
                </tr>
            `;
        }
    }

    renderHorasExtrasTable(responseData) {
        console.log('🎯 Iniciando renderizado de horas extras con paginación');
        console.log('📦 Datos recibidos:', responseData);

        let horasExtras = [];

        if (responseData && responseData.horas_extras_por_fecha) {
            console.log('🔄 Nuevo formato detectado - aplanando datos por fecha...');
            Object.values(responseData.horas_extras_por_fecha).forEach(fechaExtras => {
                if (Array.isArray(fechaExtras)) {
                    horasExtras = horasExtras.concat(fechaExtras);
                }
            });
            console.log(`📊 Datos aplanados: ${horasExtras.length} registros totales`);
        } else if (Array.isArray(responseData)) {
            console.log('📋 Array directo detectado - compatibilidad backward');
            horasExtras = responseData;
        } else if (responseData) {
            console.warn('❌ Formato de respuesta no reconocido:', responseData);
        }

        if (!Array.isArray(horasExtras)) {
            horasExtras = [];
        }

        this.horasExtrasRecords = horasExtras;
        this.horasExtrasPagination.currentPage = 1;
        this.horasExtrasPagination.totalItems = horasExtras.length;

        if (this.selectedHorasExtras.size > 0) {
            const availableIds = new Set(horasExtras.map(extra => this.extractHoraExtraId(extra)).filter(id => id !== null));
            let changed = false;
            this.selectedHorasExtras.forEach(id => {
                if (!availableIds.has(id)) {
                    this.selectedHorasExtras.delete(id);
                    changed = true;
                }
            });
            if (changed) {
                console.log('ℹ️ Ajustando selección de horas extras para coincidir con los datos actuales');
            }
        }

        this.renderHorasExtrasPage(1);
    }

    getEstadoClass(estado) {
        if (!estado) return '';
        switch (estado) {
            case 'aprobada': return 'estado-aprobada';
            case 'rechazada': return 'estado-rechazada';
            case 'pendiente': return 'estado-pendiente';
            default: return '';
        }
    }

    getTipoHorarioText(tipoHorario) {
        const tipos = {
            'diurna': 'Diurna',
            'nocturna': 'Nocturna',
            'diurna_dominical': 'Dominical Diurna',
            'nocturna_dominical': 'Dominical Nocturna',
            'diurna_dominical_festiva': 'Dominical Diurna',
            'nocturna_dominical_festiva': 'Dominical Nocturna'
        };
        return tipos[tipoHorario] || tipoHorario;
    }

    // Función helper para formatear texto del tipo de hora extra
    getTipoExtraText(tipoExtra) {
        const tipos = {
            'antes': 'Antes del horario',
            'despues': 'Después del horario',
            'durante': 'Dentro del horario',
            'sin_horario': 'Fuera de horario (sin turno)',
            'diurna': 'Diurna',
            'nocturna': 'Nocturna',
            'diurna_dominical': 'Diurna Dom/Fest',
            'nocturna_dominical': 'Nocturna Dom/Fest',
            'diurna_dominical_festiva': 'Diurna Dom/Fest',
            'nocturna_dominical_festiva': 'Nocturna Dom/Fest'
        };
        return tipos[tipoExtra] || tipoExtra;
    }

    onExtraCheckboxChange(event) {
        const checkbox = event.target;
        const id = parseInt(checkbox.value);

        if (checkbox.checked) {
            this.selectedHorasExtras.add(id);
        } else {
            this.selectedHorasExtras.delete(id);
        }

        this.updateBulkActionsVisibility();
        this.updateSelectAllCheckbox();
    }

    updateBulkActionsVisibility() {
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedExtrasCount');

        if (this.selectedHorasExtras.size > 0) {
            bulkActions.style.display = 'flex';
            selectedCount.textContent = this.selectedHorasExtras.size;
        } else {
            bulkActions.style.display = 'none';
        }
    }

    updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('selectAllExtras');
        const allCheckboxes = document.querySelectorAll('.extra-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.extra-checkbox:checked');

        selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
    }

    toggleSelectAllExtras() {
        const selectAllCheckbox = document.getElementById('selectAllExtras');
        const checkboxes = document.querySelectorAll('.extra-checkbox');

        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            const id = parseInt(checkbox.value);
            if (selectAllCheckbox.checked) {
                this.selectedHorasExtras.add(id);
            } else {
                this.selectedHorasExtras.delete(id);
            }
        });

        this.updateBulkActionsVisibility();
    }

    async aprobarHorasExtras(accion) {
        if (this.selectedHorasExtras.size === 0) {
            this.showError('Seleccione al menos una hora extra');
            return;
        }

        const confirmMessage = accion === 'aprobar' ?
            `¿Está seguro de aprobar ${this.selectedHorasExtras.size} horas extras?` :
            `¿Está seguro de rechazar ${this.selectedHorasExtras.size} horas extras?`;

        if (!confirm(confirmMessage)) {
            return;
        }

        try {
            const response = await fetch('api/horas-trabajadas/aprobar-horas-extras.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ids: Array.from(this.selectedHorasExtras),
                    accion: accion
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                this.selectedHorasExtras.clear();
                this.updateBulkActionsVisibility();
                this.filtrarHorasExtras(); // Reload the overtime table
                await this.loadHorasTrabajadasAjax(); // Reload main table with current filters
            } else {
                this.showError(data.message || 'Error al procesar la solicitud');
            }
        } catch (error) {
            console.error('Error approving overtime:', error);
            this.showError('Error de conexión');
        }
    }

    async aprobarHoraExtra(id, accion) {
        const confirmMessage = accion === 'aprobar' ?
            '¿Está seguro de aprobar esta hora extra?' :
            '¿Está seguro de rechazar esta hora extra?';

        if (!confirm(confirmMessage)) {
            return;
        }

        try {
            const response = await fetch('api/horas-trabajadas/aprobar-horas-extras.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ids: [id],
                    accion: accion
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                this.filtrarHorasExtras(); // Reload the overtime table
                await this.loadHorasTrabajadasAjax(); // Reload main table with current filters
            } else {
                this.showError(data.message || 'Error al procesar la solicitud');
            }
        } catch (error) {
            console.error('Error approving single overtime:', error);
            this.showError('Error de conexión');
        }
    }

    showEmpleadosExtrasModal() {
        document.getElementById('modalSelectEmpleadosExtras').style.display = 'block';

        // Clear search input
        const searchInput = document.getElementById('searchEmpleadosExtras');
        if (searchInput) {
            searchInput.value = '';
        }

        // Load employees with current filters
        this.loadEmpleadosExtras();

        // Focus search input after a short delay
        setTimeout(() => {
            if (searchInput) {
                searchInput.focus();
            }
        }, 100);
    }

    hideEmpleadosExtrasModal() {
        document.getElementById('modalSelectEmpleadosExtras').style.display = 'none';

        // Clear search input
        const searchInput = document.getElementById('searchEmpleadosExtras');
        if (searchInput) {
            searchInput.value = '';
        }

        // Reset list to show all employees
        this.availableEmpleadosExtras = [...this.originalEmpleadosExtras];
        this.renderEmpleadosExtrasList();
    }

    async loadEmpleadosExtras() {
        const sedeId = document.getElementById('filtroSedeExtras').value;
        const establecimientoId = document.getElementById('filtroEstablecimientoExtras').value;
        
        try {
            let url = 'api/horas-trabajadas/get-empleados-con-horas-extras.php?';
            if (sedeId) url += `sede_id=${sedeId}&`;
            if (establecimientoId) url += `establecimiento_id=${establecimientoId}&`;

            console.log('Loading empleados with overtime hours from:', url);

            const response = await fetch(url);
            const data = await response.json();

            console.log('Empleados con horas extras response:', data);

            if (data.success && data.empleados && data.empleados.length > 0) {
                this.availableEmpleadosExtras = data.empleados;
                this.originalEmpleadosExtras = [...data.empleados];

                // Filter selected employees to only include those still available
                this.selectedEmpleadosExtras = this.selectedEmpleadosExtras.filter(selectedId =>
                    this.availableEmpleadosExtras.some(emp => emp.ID_EMPLEADO == selectedId)
                );

                this.renderEmpleadosExtrasList();
                document.getElementById('empleadosExtrasListContent').style.display = 'block';

                console.log(`Loaded ${data.empleados.length} empleados with overtime hours for modal`);
            } else {
                console.log('No empleados with overtime hours found or API error:', data);
                document.getElementById('empleadosExtrasNoResults').style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading empleados with overtime hours:', error);
            document.getElementById('empleadosExtrasNoResults').style.display = 'block';
            this.showError('Error al cargar empleados: ' + error.message);
        } finally {
            document.getElementById('empleadosExtrasLoading').style.display = 'none';
        }
    }

    renderEmpleadosExtrasList(empleados = null) {
        const container = document.getElementById('empleadosExtrasListContent');
        const noResults = document.getElementById('empleadosExtrasNoResults');
        const empleadosToShow = empleados || this.availableEmpleadosExtras;

        console.log('Rendering empleados extras list with:', empleadosToShow.length, 'empleados');

        if (empleadosToShow.length === 0) {
            container.style.display = 'none';
            noResults.style.display = 'block';
            this.updateEmpleadosExtrasCount();
            return;
        }

        noResults.style.display = 'none';
        container.style.display = 'block';

        container.innerHTML = empleadosToShow.map(empleado => {
            const isSelected = this.selectedEmpleadosExtras.includes(empleado.ID_EMPLEADO);
            const initials = (empleado.NOMBRE.charAt(0) + empleado.APELLIDO.charAt(0)).toUpperCase();

            // Información adicional de horas extras
            const totalExtras = empleado.total_horas_extras || 0;
            const pendientes = empleado.horas_pendientes || 0;
            const aprobadas = empleado.horas_aprobadas || 0;
            const rechazadas = empleado.horas_rechazadas || 0;

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
                            <div class="empleado-extras-info">
                                <span class="extras-total">Total extras: ${totalExtras}</span>
                                ${pendientes > 0 ? `<span class="extras-pendientes">Pendientes: ${pendientes}</span>` : ''}
                                ${aprobadas > 0 ? `<span class="extras-aprobadas">Aprobadas: ${aprobadas}</span>` : ''}
                                ${rechazadas > 0 ? `<span class="extras-rechazadas">Rechazadas: ${rechazadas}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Bind checkbox events
        container.querySelectorAll('.empleado-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', this.onEmpleadoExtraToggle.bind(this));
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

        this.updateEmpleadosExtrasCount();
        console.log('Empleados extras list rendered successfully with', empleadosToShow.length, 'empleados');
    }

    onEmpleadoExtraToggle(e) {
        const empleadoId = e.target.dataset.id;
        const isChecked = e.target.checked;

        if (isChecked) {
            if (!this.selectedEmpleadosExtras.includes(empleadoId)) {
                this.selectedEmpleadosExtras.push(empleadoId);
            }
        } else {
            this.selectedEmpleadosExtras = this.selectedEmpleadosExtras.filter(id => id !== empleadoId);
        }

        this.updateEmpleadosExtrasCount();
    }

    updateEmpleadosExtrasCount() {
        const count = this.selectedEmpleadosExtras.length;
        const countElement = document.getElementById('selectedExtrasEmpleadosCount');
        const textElement = document.querySelector('#btnSelectEmpleadosExtras .empleados-text');

        countElement.textContent = count;

        if (count === 0) {
            textElement.textContent = 'Todos los empleados';
        } else if (count === 1) {
            textElement.textContent = '1 empleado seleccionado';
        } else {
            textElement.textContent = `${count} empleados seleccionados`;
        }
    }

    selectAllEmpleadosExtras() {
        this.selectedEmpleadosExtras = this.availableEmpleadosExtras.map(emp => emp.ID_EMPLEADO);
        this.renderEmpleadosExtrasList();
    }

    deselectAllEmpleadosExtras() {
        this.selectedEmpleadosExtras = [];
        this.renderEmpleadosExtrasList();
    }

    searchEmpleadosExtras(event) {
        this.filterEmpleadosExtrasList();
    }

    filterEmpleadosExtrasList() {
        const searchTerm = document.getElementById('searchEmpleadosExtras').value.toLowerCase();

        if (!searchTerm) {
            this.renderEmpleadosExtrasList();
            return;
        }

        const filteredEmpleados = this.availableEmpleadosExtras.filter(empleado => {
            const fullName = `${empleado.NOMBRE} ${empleado.APELLIDO}`.toLowerCase();
            const dni = empleado.DNI || '';
            const sede = empleado.SEDE_NOMBRE || '';
            const establecimiento = empleado.ESTABLECIMIENTO_NOMBRE || '';
            const idEmpleado = empleado.ID_EMPLEADO.toString();

            return fullName.includes(searchTerm) ||
                   dni.includes(searchTerm) ||
                   sede.toLowerCase().includes(searchTerm) ||
                   establecimiento.toLowerCase().includes(searchTerm) ||
                   idEmpleado.includes(searchTerm);
        });

        this.renderEmpleadosExtrasList(filteredEmpleados);
    }

    confirmEmpleadosExtrasSelection() {
        this.hideEmpleadosExtrasModal();
    }

    // ===== ADMIN FEATURES =====

    async checkAndShowAdminFeatures() {
        console.log('🔍 Checking admin permissions...');

        try {
            const response = await fetch('auth/check-role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    required_role: 'ADMIN'
                })
            });

            const data = await response.json();
            console.log('📋 Admin check response:', data);

            if (data.success && data.has_permission) {
                console.log('✅ User has ADMIN role, showing button');
                // Show admin features
                const btnAprobacion = document.getElementById('btnAprobacionHorasExtras');
                if (btnAprobacion) {
                    btnAprobacion.style.display = 'inline-flex';
                    console.log('🎯 Button should now be visible');
                }

                // Bind admin event listeners
                this.bindAdminEvents();
            } else {
                console.log('❌ User does not have ADMIN role or check failed');
                console.log('   - Success:', data.success);
                console.log('   - Has permission:', data.has_permission);
                console.log('   - User role:', data.user_role);
                console.log('   - Required role:', data.required_role);
            }
        } catch (error) {
            console.error('💥 Error checking admin permissions:', error);
        }
    }

    bindAdminEvents() {
        const btnAprobacion = document.getElementById('btnAprobacionHorasExtras');
        if (btnAprobacion) {
            btnAprobacion.addEventListener('click', this.showAprobacionHorasExtrasModal.bind(this));
        }
    }

    showHorasExtrasModal() {
        const modal = document.getElementById('modalHorasExtras');
        if (modal) {
            modal.style.display = 'flex';
            this.loadHorasExtrasData();
            this.loadSedesForExtras();
        }
    }

    hideHorasExtrasModal() {
        const modal = document.getElementById('modalHorasExtras');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async loadSedesForExtras() {
        try {
            const response = await fetch('api/empresa/get-sedes.php');
            const data = await response.json();

            if (data.success) {
                const selectSede = document.getElementById('filtroSedeExtras');
                selectSede.innerHTML = '<option value="">Todas las sedes</option>';

                data.sedes.forEach(sede => {
                    const option = document.createElement('option');
                    option.value = sede.ID_SEDE;
                    option.textContent = sede.NOMBRE;
                    selectSede.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading sedes for extras:', error);
        }
    }

    async loadHorasExtrasData() {
        const tableBody = document.getElementById('horasExtrasTableBody');
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Cargando horas extras...
                </td>
            </tr>
        `;

        try {
            const response = await fetch('api/horas-trabajadas/get-horas-extras-pendientes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    fechaDesde: document.getElementById('filtroFechaDesdeExtras').value,
                    fechaHasta: document.getElementById('filtroFechaHastaExtras').value,
                    sedeId: document.getElementById('filtroSedeExtras').value,
                    establecimientoId: document.getElementById('filtroEstablecimientoExtras').value,
                    estado: document.getElementById('filtroEstadoExtras').value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.renderHorasExtrasTable(data.horasExtras);
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.message || 'Error al cargar horas extras'}
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Error loading horas extras:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error de conexión
                    </td>
                </tr>
            `;
        }
    }

    // ===== END OF CLASS =====
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.horasTrabajadasInstance = new HorasTrabajadas();
});