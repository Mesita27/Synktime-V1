/**
 * SynkTime Dashboard
 * Maneja la funcionalidad del dashboard, incluyendo filtros, actualización de datos
 * y visualización de popups de asistencia
 */
class Dashboard {
    constructor() {
        // Referencias a elementos DOM
        this.selectSede = document.getElementById('selectSede');
        this.selectEstablecimiento = document.getElementById('selectEstablecimiento');
        this.datePicker = document.getElementById('datePicker');
        
        // Referencias a elementos de estadísticas
        this.totalEmpleados = document.getElementById('totalEmpleados');
        this.llegadasTempranas = document.getElementById('llegadasTempranas');
        this.llegadasTiempo = document.getElementById('llegadasTiempo');
        this.llegadasTarde = document.getElementById('llegadasTarde');
        this.faltas = document.getElementById('faltas');
        this.horasTrabajadas = document.getElementById('horasTrabajadas');
        this.activityList = document.getElementById('activityList');
        
        // Referencias a gráficos
        this.attendanceByHourChart = null;
        this.attendanceDistributionChart = null;
        
        // Almacenamiento de datos actuales
        this.currentData = {};
        this.attendanceDetails = {
            temprano: [],
            aTiempo: [],
            tarde: [],
            faltas: []
        };
        
        // Inicializar fecha si existe el elemento
        if (this.datePicker) {
            this.datePicker.value = this.formatDate(new Date());
        }
        
        // Inicializar eventos
        this.initEvents();
        
        // Configurar event listeners para los popups
        this.setupAttendanceCardListeners();
    }
    
    /**
     * Inicializar eventos de la página
     */
    initEvents() {
        if (this.selectSede) {
            this.selectSede.addEventListener('change', () => this.cargarEstablecimientos());
        }
        
        if (this.selectEstablecimiento) {
            this.selectEstablecimiento.addEventListener('change', () => this.cargarEstadisticas());
        }
        
        if (this.datePicker) {
            this.datePicker.addEventListener('change', () => this.cargarEstadisticas());
        }
        
        // Botón de actualizar
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.cargarEstadisticas());
        }
    }
    
    /**
     * Inicializar gráficos con datos iniciales
     */
    initializeChartsWithData(initialData) {
        // Inicializar gráfico de asistencias por hora
        const hourlyOptions = {
            series: [{
                name: 'Entradas',
                data: initialData.hourlyAttendanceData ? initialData.hourlyAttendanceData.data : []
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 800 },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        // Al hacer clic en cualquier punto del gráfico de horas, mostrar todas las entradas del día
                        if (window.mostrarModalAsistencias) {
                            // Puedes mostrar un modal general o uno específico - aquí mostramos "a tiempo" por defecto
                            window.mostrarModalAsistencias('aTiempo');
                        }
                    }
                }
            },
            colors: ['#4B96FA'],
            fill: {
                type: 'gradient',
                gradient: { 
                    shade: 'dark', 
                    type: 'vertical', 
                    shadeIntensity: 0.3, 
                    opacityFrom: 0.7, 
                    opacityTo: 0.2, 
                    stops: [0, 90, 100] 
                }
            },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: { 
                categories: initialData.hourlyAttendanceData ? initialData.hourlyAttendanceData.categories : [], 
                labels: { style: { colors: '#718096' } } 
            },
            yaxis: { 
                labels: { style: { colors: '#718096' } } 
            },
            tooltip: { 
                theme: 'light', 
                y: { formatter: value => value + ' empleados' } 
            },
            grid: { 
                borderColor: '#e0e6ed', 
                strokeDashArray: 5, 
                xaxis: { lines: { show: true } }, 
                yaxis: { lines: { show: true } } 
            }
        };

        // Inicializar gráfico de distribución
        const distributionOptions = {
            series: initialData.distributionData ? [
                initialData.distributionData.tempranos || 0,
                initialData.distributionData.atiempo || 0,
                initialData.distributionData.tarde || 0,
                initialData.distributionData.faltas || 0
            ] : [0, 0, 0, 0],
            chart: { 
                type: 'donut', 
                height: 350,
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        // Mapear índices del gráfico a tipos de modal
                        const tipoMap = ['temprano', 'aTiempo', 'tarde', 'faltas'];
                        const tipoSeleccionado = tipoMap[config.dataPointIndex];
                        
                        if (tipoSeleccionado && window.mostrarModalAsistencias) {
                            window.mostrarModalAsistencias(tipoSeleccionado);
                        }
                    }
                }
            },
            colors: ['#28A745', '#48BB78', '#F6AD55', '#F56565'],
            labels: ['Tempranos', 'A Tiempo', 'Tardanzas', 'Faltas'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                            }
                        }
                    }
                }
            },
            legend: { position: 'bottom', horizontalAlign: 'center' },
            dataLabels: { 
                enabled: true, 
                formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] 
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: { width: 300 },
                    legend: { position: 'bottom' }
                }
            }]
        };

        this.attendanceByHourChart = new ApexCharts(document.querySelector("#hourlyAttendanceChart"), hourlyOptions);
        this.attendanceDistributionChart = new ApexCharts(document.querySelector("#attendanceDistributionChart"), distributionOptions);
        
        this.attendanceByHourChart.render();
        this.attendanceDistributionChart.render();
    }

    /**
     * Update charts with new data
     */
    updateCharts(hourlyData, distributionData) {
        if (this.attendanceByHourChart) {
            this.attendanceByHourChart.updateOptions({
                xaxis: { categories: hourlyData.categories || [] },
                chart: {
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            // Al hacer clic en cualquier punto del gráfico de horas, mostrar todas las entradas del día
                            if (window.mostrarModalAsistencias) {
                                window.mostrarModalAsistencias('aTiempo');
                            }
                        }
                    }
                }
            });
            this.attendanceByHourChart.updateSeries([{
                name: 'Entradas',
                data: hourlyData.data || []
            }]);
        }
        if (this.attendanceDistributionChart) {
            this.attendanceDistributionChart.updateSeries([
                distributionData.tempranos || 0,
                distributionData.atiempo || 0,
                distributionData.tarde || 0,
                distributionData.faltas || 0
            ]);
            
            // Actualizar también las opciones del gráfico para mantener la funcionalidad de clic
            this.attendanceDistributionChart.updateOptions({
                chart: {
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            const tipoMap = ['temprano', 'aTiempo', 'tarde', 'faltas'];
                            const tipoSeleccionado = tipoMap[config.dataPointIndex];
                            
                            if (tipoSeleccionado && window.mostrarModalAsistencias) {
                                window.mostrarModalAsistencias(tipoSeleccionado);
                            }
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Configurar event listeners para las tarjetas de asistencia
     */
    setupAttendanceCardListeners() {
        // Mapeo de IDs de tarjetas a tipos de popup
        const cardMappings = [
            { cardId: 'llegadasTempranas-card', type: 'temprano' },
            { cardId: 'llegadasTiempo-card', type: 'aTiempo' },
            { cardId: 'llegadasTarde-card', type: 'tarde' },
            { cardId: 'faltas-card', type: 'faltas' }
        ];
        
        // Configurar cada tarjeta
        cardMappings.forEach(mapping => {
            const card = document.getElementById(mapping.cardId);
            if (card) {
                card.style.cursor = 'pointer';
                card.addEventListener('click', () => this.showAttendancePopup(mapping.type));
            }
        });
    }
    
    /**
     * Cargar establecimientos según la sede seleccionada
     */
    cargarEstablecimientos() {
        const sedeId = this.selectSede.value;
        if (!sedeId) return;
        
        // Limpiar establecimientos actuales
        this.selectEstablecimiento.innerHTML = '';
        
        // Mostrar indicador de carga
        this.selectEstablecimiento.innerHTML = '<option>Cargando...</option>';
        
        // Obtener establecimientos para la sede seleccionada
        fetch(`api/get-establecimientos.php?sede_id=${sedeId}`)
            .then(response => response.json())
            .then(data => {
                // Limpiar opciones actuales
                this.selectEstablecimiento.innerHTML = '';
                
                if (data.success && data.establecimientos && data.establecimientos.length > 0) {
                    // Agregar opción "Todos"
                    const allOption = document.createElement('option');
                    allOption.value = '';
                    allOption.textContent = 'Todos los establecimientos';
                    this.selectEstablecimiento.appendChild(allOption);
                    
                    // Agregar nuevas opciones
                    data.establecimientos.forEach(establecimiento => {
                        const option = document.createElement('option');
                        option.value = establecimiento.ID_ESTABLECIMIENTO;
                        option.textContent = establecimiento.NOMBRE;
                        this.selectEstablecimiento.appendChild(option);
                    });
                    
                    // Cargar estadísticas para todos los establecimientos de la sede
                    this.cargarEstadisticas();
                } else {
                    // No hay establecimientos
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No hay establecimientos disponibles';
                    this.selectEstablecimiento.appendChild(option);
                    
                    // Limpiar estadísticas
                    this.limpiarEstadisticas();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.selectEstablecimiento.innerHTML = '<option>Error al cargar establecimientos</option>';
            });
    }
    
    /**
     * Cargar estadísticas según los filtros seleccionados
     */
    cargarEstadisticas() {
        const sedeId = this.selectSede.value;
        const establecimientoId = this.selectEstablecimiento.value;
        const fecha = this.datePicker.value;
        
        if (!sedeId) {
            this.limpiarEstadisticas();
            return;
        }
        
        // Mostrar indicador de carga
        this.showLoading(true);
        
        // Construir URL para la API
        let apiUrl = `api/get-dashboard-stats.php?fecha=${fecha}`;
        
        if (establecimientoId) {
            apiUrl += `&establecimiento_id=${establecimientoId}`;
        } else if (sedeId) {
            apiUrl += `&sede_id=${sedeId}`;
        }
        
        // Obtener estadísticas
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                // Ocultar indicador de carga
                this.showLoading(false);
                
                if (data.success) {
                    // Guardar datos para uso posterior
                    this.currentData = data;
                    
                    // Actualizar tarjetas de estadísticas
                    this.totalEmpleados.textContent = data.estadisticas.totalEmpleados || 0;
                    this.llegadasTempranas.textContent = data.estadisticas.totalTemprano || 0;
                    this.llegadasTiempo.textContent = data.estadisticas.totalATiempo || 0;
                    this.llegadasTarde.textContent = data.estadisticas.totalTarde || 0;
                    this.faltas.textContent = data.estadisticas.totalFaltas || 0;
                    this.horasTrabajadas.textContent = data.estadisticas.horasTrabajadas || 0;
                    
                    // Actualizar gráfico de asistencias por hora
                    if (this.attendanceByHourChart && data.asistenciasPorHora) {
                        this.attendanceByHourChart.updateOptions({
                            xaxis: { categories: data.asistenciasPorHora.categories || [] }
                        });
                        this.attendanceByHourChart.updateSeries([{
                            name: 'Entradas',
                            data: data.asistenciasPorHora.data || []
                        }]);
                    }
                    
                    // Actualizar gráfico de distribución de asistencias
                    if (this.attendanceDistributionChart) {
                        this.attendanceDistributionChart.updateSeries([
                            data.estadisticas.llegadas_temprano || 0,
                            data.estadisticas.llegadas_tiempo || 0,
                            data.estadisticas.llegadas_tarde || 0,
                            data.estadisticas.faltas || 0
                        ]);
                    }
                    
                    // Actualizar actividad reciente
                    if (data.actividadReciente) {
                        this.actualizarActividadReciente(data.actividadReciente);
                    }
                } else {
                    console.error('Error en la respuesta:', data.error);
                    this.limpiarEstadisticas();
                    this.showError(data.error || 'Error al cargar estadísticas');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.limpiarEstadisticas();
                this.showLoading(false);
                this.showError('Error de conexión al servidor');
            });
    }
    
    /**
     * Limpiar todas las estadísticas cuando no hay datos
     */
    limpiarEstadisticas() {
        // Limpiar tarjetas de estadísticas
        this.totalEmpleados.textContent = '0';
        this.llegadasTempranas.textContent = '0';
        this.llegadasTiempo.textContent = '0';
        this.llegadasTarde.textContent = '0';
        this.faltas.textContent = '0';
        this.horasTrabajadas.textContent = '0';
        
        // Limpiar gráficos
        if (this.attendanceByHourChart) {
            this.attendanceByHourChart.updateOptions({
                xaxis: { categories: [] }
            });
            this.attendanceByHourChart.updateSeries([{
                name: 'Entradas',
                data: []
            }]);
        }
        
        if (this.attendanceDistributionChart) {
            this.attendanceDistributionChart.updateSeries([0, 0, 0, 0]);
        }
        
        // Limpiar actividad reciente
        this.activityList.innerHTML = '<p class="no-activity">No hay actividad reciente para mostrar.</p>';
    }
    
    /**
     * Actualizar la lista de actividad reciente
     */
    actualizarActividadReciente(actividades) {
        // Limpiar lista actual
        this.activityList.innerHTML = '';
        
        if (actividades && actividades.length > 0) {
            actividades.forEach(actividad => {
                const fechaHora = new Date(actividad.FECHA + 'T' + actividad.HORA);
                const fechaFormateada = fechaHora.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }) + ' ' + fechaHora.toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const item = document.createElement('div');
                item.className = 'activity-item';
                
                const iconClass = actividad.TIPO === 'ENTRADA' ? 'entry' : 'exit';
                const iconType = actividad.TIPO === 'ENTRADA' ? 'sign-in-alt' : 'sign-out-alt';
                const actionText = actividad.TIPO === 'ENTRADA' ? 'llegó' : 'salió';
                
                item.innerHTML = `
                    <div class="activity-icon ${iconClass}">
                        <i class="fas fa-${iconType}"></i>
                    </div>
                    <div class="activity-details">
                        <p class="activity-title">
                            ${this.escapeHtml(actividad.NOMBRE)} ${this.escapeHtml(actividad.APELLIDO || '')} 
                            <span class="activity-type">${actionText}</span>
                            ${actividad.TIPO === 'ENTRADA' && actividad.TARDANZA === 'S' ? 
                                '<span class="badge-late">Tarde</span>' : ''}
                        </p>
                        <p class="activity-time">${fechaFormateada}</p>
                        ${actividad.OBSERVACION ? 
                            `<p class="activity-note">${this.escapeHtml(actividad.OBSERVACION)}</p>` : ''}
                    </div>
                `;
                
                this.activityList.appendChild(item);
            });
        } else {
            this.activityList.innerHTML = '<p class="no-activity">No hay actividad reciente para mostrar.</p>';
        }
    }
    
    /**
     * Mostrar popup de detalles de asistencia
     */
    showAttendancePopup(tipo) {
        const sedeId = this.selectSede.value;
        const establecimientoId = this.selectEstablecimiento.value;
        const fecha = this.datePicker.value;
        
        if (!sedeId || !fecha) {
            alert('Por favor seleccione una sede y fecha');
            return;
        }
        
        // Mapear tipo a nombre para mostrar en el título
        const tipoNombres = {
            'temprano': 'Llegadas Tempranas',
            'aTiempo': 'Llegadas A Tiempo',
            'tarde': 'Llegadas Tarde',
            'faltas': 'Faltas'
        };
        
        // Mostrar modal con indicador de carga
        const modalId = `${tipo}Modal`;
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        modal.classList.add('show');
        
        const tableBody = document.getElementById(`${tipo}ModalTableBody`);
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        // Actualizar información de filtros en el modal
        document.getElementById(`${tipo}ModalDate`).textContent = this.formatDateDisplay(fecha);
        
        // Nombre de la ubicación (sede o establecimiento)
        let locationName = this.selectSede.options[this.selectSede.selectedIndex].text;
        if (establecimientoId) {
            locationName = this.selectEstablecimiento.options[this.selectEstablecimiento.selectedIndex].text;
        }
        
        document.getElementById(`${tipo}ModalLocation`).textContent = locationName;
        
        // Construir URL para la API
        let apiUrl = `api/get-attendance-details.php?tipo=${tipo}&fecha=${fecha}`;
        
        if (establecimientoId) {
            apiUrl += `&establecimiento_id=${establecimientoId}`;
        } else if (sedeId) {
            apiUrl += `&sede_id=${sedeId}`;
        }
        
        // Cargar los datos
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Error desconocido');
                }
                
                // Guardar datos para la exportación
                this.attendanceDetails[tipo] = data.data;
                
                // Actualizar la tabla
                this.populateAttendanceTable(tipo, data.data);
            })
            .catch(error => {
                console.error(`Error al cargar datos de ${tipo}:`, error);
                
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al cargar datos: ${error.message}
                            </td>
                        </tr>
                    `;
                }
            });
    }
    
    /**
     * Poblar tabla de asistencias según el tipo
     */
    populateAttendanceTable(tipo, data) {
        const tbody = document.getElementById(`${tipo}ModalTableBody`);
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (!data || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay registros disponibles
                    </td>
                </tr>
            `;
            return;
        }
        
        if (tipo === 'faltas') {
            // Tabla específica para faltas
            data.forEach(item => {
                tbody.innerHTML += `
                    <tr>
                        <td>${this.escapeHtml(item.codigo || '')}</td>
                        <td>${this.escapeHtml(item.NOMBRE || '')} ${this.escapeHtml(item.apellido || '')}</td>
                        <td>${this.escapeHtml(item.establecimiento || '-')}</td>
                        <td>${this.escapeHtml(item.sede || '-')}</td>
                        <td>${this.escapeHtml(item.horario_nombre || '-')} (${item.HORA_ENTRADA || '--:--'})</td>
                    </tr>
                `;
            });
        } else {
            // Tabla para asistencias (temprano, a tiempo, tarde)
            data.forEach(item => {
                const minutosDiff = item.minutos_diferencia_formateada || 
                                   (item.minutos_diferencia ? 
                                   `${Math.abs(item.minutos_diferencia)} min` + 
                                   (item.minutos_diferencia > 0 ? ' antes' : ' tarde') : '');
                
                tbody.innerHTML += `
                    <tr>
                        <td>${this.escapeHtml(item.codigo || '')}</td>
                        <td>${this.escapeHtml(item.NOMBRE || '')} ${this.escapeHtml(item.apellido || '')}</td>
                        <td>${this.escapeHtml(item.establecimiento || '-')}</td>
                        <td>${this.formatTime(item.entrada_hora) || '--:--'}</td>
                        <td>${minutosDiff}</td>
                    </tr>
                `;
            });
        }
    }
    
    /**
     * Cerrar popup de asistencia
     */
    closeAttendancePopup(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    /**
     * Exportar datos a Excel
     */
    exportToExcel(tipo) {
        if (!this.attendanceDetails[tipo] || this.attendanceDetails[tipo].length === 0) {
            alert('No hay datos para exportar');
            return;
        }
        
        // Obtener el nombre de la ubicación y fecha para el nombre del archivo
        const fecha = this.datePicker.value;
        let ubicacion = this.selectSede.options[this.selectSede.selectedIndex].text;
        if (this.selectEstablecimiento.value) {
            ubicacion = this.selectEstablecimiento.options[this.selectEstablecimiento.selectedIndex].text;
        }
        
        // Mapear tipos a nombres amigables
        const tipoNombres = {
            'temprano': 'Llegadas_Tempranas',
            'aTiempo': 'Llegadas_A_Tiempo',
            'tarde': 'Llegadas_Tarde',
            'faltas': 'Faltas'
        };
        
        // Nombre del archivo
        const fileName = `${tipoNombres[tipo]}_${fecha}_${ubicacion.replace(/[\s\/]/g, '_')}.xlsx`;
        
        // Preparar los datos para Excel
        let excelData = [];
        
        // Agregar encabezados según el tipo
        if (tipo === 'faltas') {
            excelData.push(['Código', 'Nombre', 'Establecimiento', 'Sede', 'Horario']);
        } else {
            excelData.push(['Código', 'Nombre', 'Establecimiento', 'Hora Entrada', 'Diferencia']);
        }
        
        // Agregar filas de datos
        this.attendanceDetails[tipo].forEach(item => {
            if (tipo === 'faltas') {
                excelData.push([
                    item.codigo || '',
                    `${item.NOMBRE || ''} ${item.apellido || ''}`,
                    item.establecimiento || '',
                    item.sede || '',
                    `${item.horario_nombre || ''} (${item.HORA_ENTRADA || '--:--'})`
                ]);
            } else {
                const minutosDiff = item.minutos_diferencia_formateada || 
                                   (item.minutos_diferencia ? 
                                   `${Math.abs(item.minutos_diferencia)} min` + 
                                   (item.minutos_diferencia > 0 ? ' antes' : ' tarde') : '');
                
                excelData.push([
                    item.codigo || '',
                    `${item.NOMBRE || ''} ${item.apellido || ''}`,
                    item.establecimiento || '',
                    this.formatTime(item.entrada_hora) || '--:--',
                    minutosDiff
                ]);
            }
        });
        
        // Crear libro de Excel
        const ws = XLSX.utils.aoa_to_sheet(excelData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, tipoNombres[tipo]);
        
        // Guardar el archivo
        XLSX.writeFile(wb, fileName);
    }
    
    /**
     * Mostrar/ocultar indicador de carga
     */
    showLoading(show) {
        const loader = document.getElementById('dashboard-loader');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }
    
    /**
     * Mostrar mensaje de error
     */
    showError(message) {
        const alertsContainer = document.getElementById('dashboard-alerts');
        if (alertsContainer) {
            alertsContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            `;
        }
    }
    
    /**
     * Formatear fecha para input date
     */
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    /**
     * Formatear fecha para mostrar
     */
    formatDateDisplay(dateStr) {
        if (!dateStr) return '';
        
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    /**
     * Formatear hora
     */
    formatTime(timeStr) {
        if (!timeStr) return '';
        
        // Si es formato HH:MM:SS, extraer solo HH:MM
        if (timeStr.length > 5) {
            return timeStr.substring(0, 5);
        }
        
        return timeStr;
    }
    
    /**
     * Función auxiliar para escapar HTML y prevenir XSS
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar el dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Instanciar el dashboard
    window.dashboardApp = new Dashboard();
    
    // Configurar eventos de cierre para los modales
    document.querySelectorAll('.modal').forEach(modal => {
        // Cerrar al hacer clic fuera del contenido del modal
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
    
    document.querySelectorAll('.modal-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            document.getElementById(modalId).classList.remove('show');
        });
    });
    
    // Cerrar modales con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(modal => {
                modal.classList.remove('show');
            });
        }
    });
    
    // Configurar los botones de exportación
    document.querySelectorAll('[data-export-type]').forEach(btn => {
        btn.addEventListener('click', function() {
            const tipo = this.getAttribute('data-export-type');
            window.dashboardApp.exportToExcel(tipo);
        });
    });
    
    // Manejar la apertura/cierre del menú de usuario
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', () => {
            userMenu.classList.toggle('active');
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('active');
            }
        });
    }
    
    // Manejar toggle del sidebar
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    const container = document.querySelector('.container');
    
    if (toggleSidebarBtn && container) {
        toggleSidebarBtn.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
        });
    }
});