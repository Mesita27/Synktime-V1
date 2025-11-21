/**
 * JavaScript para el Modal de Nueva Justificación
 * Incluye búsqueda avanzada de empleados y validaciones
 */

class JustificacionesModal {
    constructor() {
        this.empleados = [];
        this.empleadoSeleccionado = null;
        this.sedes = [];
        this.establecimientos = [];
        
        this.init();
    }

    /**
     * Obtener fecha actual en zona horaria de Bogotá, Colombia
     * Método más preciso que compensa la diferencia horaria
     */
    getBogotaDate() {
        // Crear fecha actual
        const now = new Date();
        
        // Obtener la fecha en zona horaria de Bogotá
        const bogotaTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));
        
        // Formatear como YYYY-MM-DD
        const year = bogotaTime.getFullYear();
        const month = String(bogotaTime.getMonth() + 1).padStart(2, '0');
        const day = String(bogotaTime.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    }

    /**
     * Obtener fecha y hora actual en zona horaria de Bogotá
     */
    getBogotaDateTime() {
        const now = new Date();
        const bogotaTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));
        
        const year = bogotaTime.getFullYear();
        const month = String(bogotaTime.getMonth() + 1).padStart(2, '0');
        const day = String(bogotaTime.getDate()).padStart(2, '0');
        const hours = String(bogotaTime.getHours()).padStart(2, '0');
        const minutes = String(bogotaTime.getMinutes()).padStart(2, '0');
        const seconds = String(bogotaTime.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
        this.setDefaultDate();
        this.setupForm();
    }

    setupForm() {
        // Configurar fecha automática y readonly con zona horaria de Bogotá
        const today = this.getBogotaDate();
        $('#fechaFaltaModal').val(today).prop('readonly', true);
        
        // Habilitar selección de tipo de falta
        $('#tipoFaltaModal').val('completa').prop('disabled', false);
        
        // Configurar placeholder para horas programadas
        $('#horasProgramadasModal').attr('placeholder', '0');
    }

    bindEvents() {
        // Modal events
        $('#modalNuevaJustificacion').on('shown.bs.modal', () => {
            $('#searchEmpleado').focus();
            this.resetModal();
        });

        // Búsqueda de empleados
        $('#btnBuscarEmpleados').on('click', () => {
            this.searchEmpleados();
        });

        // Búsqueda en tiempo real
        let searchTimeout;
        $('#searchEmpleado').on('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if ($('#searchEmpleado').val().length >= 2) {
                    this.searchEmpleados();
                }
            }, 500);
        });

        // Cambio de filtros
        $('#filterSede, #filterEstablecimiento').on('change', () => {
            this.searchEmpleados();
        });

        // Cambio de fecha - cargar turnos del empleado seleccionado
        $('#fechaFaltaModal').on('change', () => {
            if (this.empleadoSeleccionado) {
                this.loadTurnosEmpleado();
            }
        });

        // Selección de empleado
        $(document).on('click', '.empleado-item', (e) => {
            this.selectEmpleado($(e.currentTarget));
        });

        // Cambio de tipo de falta
        $('#tipoFaltaModal').on('change', () => {
            this.handleTipoFaltaChange();
        });

        // Selección de turnos
        $(document).on('change', '.turno-checkbox', () => {
            this.calculateHoras();
        });

        // Guardar justificación
        $('#btnGuardarJustificacion').on('click', () => {
            this.saveJustificacion();
        });

        // Filtro de sede para cargar establecimientos
        $('#filterSede').on('change', () => {
            this.loadEstablecimientos($('#filterSede').val());
        });
    }

    setDefaultDate() {
        const today = this.getBogotaDate();
        $('#fechaFaltaModal').val(today);
    }

    async loadInitialData() {
        await this.loadSedes();
    }

    async loadSedes() {
        try {
            const response = await fetch('api/justificaciones.php?action=getSedes');
            const data = await response.json();
            
            if (data.success) {
                this.sedes = data.sedes;
                this.populateSelect('#filterSede', data.sedes, 'ID_SEDE', 'NOMBRE');
            }
        } catch (error) {
            console.error('Error cargando sedes:', error);
        }
    }

    async loadEstablecimientos(sedeId) {
        try {
            $('#filterEstablecimiento').html('<option value="">Todos</option>');
            
            if (!sedeId) return;

            // Simular carga de establecimientos por sede
            const response = await fetch(`api/justificaciones.php?action=getEstablecimientos&sede_id=${sedeId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateSelect('#filterEstablecimiento', data.establecimientos, 'ID_ESTABLECIMIENTO', 'NOMBRE');
            }
        } catch (error) {
            console.error('Error cargando establecimientos:', error);
        }
    }

    async searchEmpleados() {
        try {
            const search = $('#searchEmpleado').val();
            const sedeId = $('#filterSede').val();
            const establecimientoId = $('#filterEstablecimiento').val();

            // Mostrar loading
            $('#empleadosModalList').html(`
                <div class="loading-empleados">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Buscando empleados elegibles...</p>
                </div>
            `);

            const params = new URLSearchParams({
                search: search,
                solo_elegibles: '1' // Siempre solo elegibles
            });

            if (sedeId) params.append('sede_id', sedeId);
            if (establecimientoId) params.append('establecimiento_id', establecimientoId);

            const response = await fetch(`api/justificaciones.php?action=searchEmpleados&${params}`);
            const data = await response.json();

            if (data.success) {
                this.empleados = data.empleados;
                this.renderEmpleados();
                $('#empleadosCount').text(data.total);
            } else {
                $('#empleadosModalList').html(`
                    <div class="empty-empleados">
                        <i class="fas fa-user-times"></i>
                        <p>No hay empleados elegibles</p>
                        <small class="text-muted">
                            Los empleados deben tener horario programado y no haber registrado entrada en las últimas 16 horas
                        </small>
                    </div>
                `);
                $('#empleadosCount').text('0');
            }
        } catch (error) {
            console.error('Error buscando empleados:', error);
            $('#empleadosModalList').html(`
                <div class="empty-empleados">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error al buscar empleados</p>
                </div>
            `);
            $('#empleadosCount').text('0');
        }
    }

    renderEmpleados() {
        if (!this.empleados || this.empleados.length === 0) {
            $('#empleadosModalList').html(`
                <div class="empty-empleados">
                    <i class="fas fa-users-slash"></i>
                    <p>No se encontraron empleados con los criterios especificados</p>
                </div>
            `);
            return;
        }

        const html = this.empleados.map(empleado => `
            <div class="empleado-item" data-empleado-id="${empleado.id}">
                <div class="empleado-info">
                    <div class="empleado-nombre">${empleado.nombre_completo}</div>
                    <div class="empleado-meta">
                        <span><i class="fas fa-id-card"></i> ${empleado.codigo}</span>
                        <span><i class="fas fa-building"></i> ${empleado.establecimiento_nombre || 'Sin establecimiento'}</span>
                        ${empleado.sede_nombre ? `<span><i class="fas fa-map-marker-alt"></i> ${empleado.sede_nombre}</span>` : ''}
                        ${empleado.email ? `<span><i class="fas fa-envelope"></i> ${empleado.email}</span>` : ''}
                    </div>
                </div>
                <div class="empleado-status">
                    <span class="status-badge-small status-elegible">
                        <i class="fas fa-check-circle"></i>
                        Elegible
                    </span>
                </div>
            </div>
        `).join('');

        $('#empleadosModalList').html(html);
    }

    selectEmpleado($item) {
        // Remover selección anterior
        $('.empleado-item').removeClass('selected');
        
        // Seleccionar nuevo empleado
        $item.addClass('selected');
        
        const empleadoId = $item.data('empleado-id');
        this.empleadoSeleccionado = this.empleados.find(emp => emp.id == empleadoId);
        
        if (this.empleadoSeleccionado) {
            $('#empleadoIdModal').val(empleadoId);
            $('#empleadoSeleccionadoInfo').val(this.empleadoSeleccionado.nombre_completo);
            
            // Cargar turnos si hay fecha seleccionada
            if ($('#fechaFaltaModal').val()) {
                this.loadTurnosEmpleado();
            }
            
            this.showNotification('Empleado seleccionado: ' + this.empleadoSeleccionado.nombre_completo, 'success');
        }
    }

    async saveJustificacion() {
        try {
            // Validar datos requeridos
            const empleadoId = $('#empleadoIdModal').val();
            const fechaFalta = $('#fechaFaltaModal').val();
            const motivo = $('#motivoModal').val();

            if (!empleadoId) {
                this.showNotification('Por favor seleccione un empleado.', 'error');
                return;
            }

            if (!fechaFalta) {
                this.showNotification('Por favor seleccione la fecha de falta.', 'error');
                return;
            }

            if (!motivo) {
                this.showNotification('Por favor seleccione el motivo.', 'error');
                return;
            }

            // Deshabilitar botón y mostrar loading
            $('#btnGuardarJustificacion').prop('disabled', true).html(`
                <i class="fas fa-spinner fa-spin"></i>
                Guardando...
            `);

            // Preparar datos
            const justificarTodosTurnos = $('#todosTurnos').is(':checked') || $('#tipoFaltaModal').val() === 'completa';
            const turnosSeleccionados = [];
            
            if (!justificarTodosTurnos) {
                $('.turno-individual:checked').each((i, checkbox) => {
                    turnosSeleccionados.push($(checkbox).val());
                });
            }
            
            const requestData = {
                empleado_id: empleadoId,
                fecha_falta: fechaFalta,
                motivo: motivo,
                tipo_falta: $('#tipoFaltaModal').val(),
                horas_programadas: $('#horasProgramadasModal').val(),
                detalle_adicional: $('#detalleAdicionalModal').val(),
                justificar_todos_turnos: justificarTodosTurnos ? 1 : 0,
                impacto_salario: 0
            };
            
            // Agregar información de turnos
            if (justificarTodosTurnos) {
                requestData.justificar_todos_turnos = 1;
            } else if (turnosSeleccionados.length > 0) {
                requestData.turno_id = turnosSeleccionados[0]; // Para un turno específico
                if (turnosSeleccionados.length > 1) {
                    requestData.turnos_ids = turnosSeleccionados;
                }
            }

            const response = await fetch('api/justificaciones.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Justificación creada exitosamente.', 'success');
                
                // Cerrar modal y actualizar lista
                $('#modalNuevaJustificacion').modal('hide');
                
                // Actualizar el módulo principal si existe
                if (window.justificacionesModule) {
                    window.justificacionesModule.loadJustificaciones();
                    window.justificacionesModule.loadEstadisticas();
                }
                
                this.resetModal();
                
            } else {
                this.showNotification('Error: ' + (data.message || 'No se pudo crear la justificación.'), 'error');
            }

        } catch (error) {
            console.error('Error guardando justificación:', error);
            this.showNotification('Error de conexión. Intente nuevamente.', 'error');
        } finally {
            // Restaurar botón
            $('#btnGuardarJustificacion').prop('disabled', false).html(`
                <i class="fas fa-save"></i>
                Crear Justificación
            `);
        }
    }

    resetModal() {
        // Limpiar formulario
        $('#formModalJustificacion')[0].reset();
        
        // Limpiar selecciones
        $('#empleadoIdModal').val('');
        $('#empleadoSeleccionadoInfo').val('');
        this.empleadoSeleccionado = null;
        
        // Limpiar lista de empleados
        $('#empleadosModalList').html(`
            <div class="text-center text-muted py-4">
                <i class="fas fa-search fa-2x mb-2"></i>
                <p>Use los filtros de arriba para buscar empleados</p>
            </div>
        `);
        
        $('#empleadosCount').text('0');
        
        // Ocultar sección de turnos
        $('#turnosSection').hide();
        $('#turnosContainer').html('');
        
        // Resetear fecha por defecto y configuraciones
        this.setDefaultDate();
        this.setupForm();
        
        // Limpiar filtros
        $('#filterSede').val('');
        $('#filterEstablecimiento').html('<option value="">Todos</option>');
        
        // Resetear valores por defecto
        $('#horasProgramadasModal').val(8);
        $('#tipoFaltaModal').val('completa');
    }

    populateSelect(selector, options, valueField, textField) {
        const $select = $(selector);
        const currentValue = $select.val();
        
        $select.find('option:not(:first)').remove();
        
        options.forEach(option => {
            $select.append(`<option value="${option[valueField]}">${option[textField]}</option>`);
        });
        
        if (currentValue) {
            $select.val(currentValue);
        }
    }

    async loadTurnosEmpleado() {
        if (!this.empleadoSeleccionado) return;
        
        try {
            const fecha = $('#fechaFaltaModal').val();
            if (!fecha) return;
            
            const response = await fetch(`api/justificaciones.php?action=getTurnosEmpleado&empleado_id=${this.empleadoSeleccionado.id}&fecha=${fecha}`);
            const data = await response.json();
            
            if (data.success && data.turnos && data.turnos.length > 0) {
                this.renderTurnos(data.turnos);
                $('#turnosSection').show();
            } else {
                $('#turnosSection').hide();
                // Si no hay turnos, usar horario estándar
                this.setHorarioEstandar();
            }
        } catch (error) {
            console.error('Error cargando turnos:', error);
            $('#turnosSection').hide();
            this.setHorarioEstandar();
        }
    }

    renderTurnos(turnos) {
        // Si solo hay un turno, automáticamente seleccionar "todos los turnos"
        const autoSelectAll = turnos.length === 1;
        
        let turnosHtml = `
            <div class="turnos-container">
                <div class="turno-item todos-turnos-option">
                    <input type="checkbox" id="todosTurnos" class="turno-checkbox" value="todos" ${autoSelectAll ? 'checked' : ''}>
                    <div class="turno-info">
                        <div class="turno-tiempo">Todos los turnos</div>
                        <div class="turno-horas">Jornada completa</div>
                    </div>
                </div>
        `;
        
        turnos.forEach(turno => {
            const horasCalculadas = this.calcularHorasTurno(turno.HORA_INICIO, turno.HORA_FIN);
            turnosHtml += `
                <div class="turno-item">
                    <input type="checkbox" class="turno-checkbox turno-individual" 
                           value="${turno.ID_HORARIO}" 
                           data-horas="${horasCalculadas}"
                           data-inicio="${turno.HORA_INICIO}"
                           data-fin="${turno.HORA_FIN}"
                           ${autoSelectAll ? 'disabled' : ''}>
                    <div class="turno-info">
                        <div class="turno-tiempo">${turno.HORA_INICIO} - ${turno.HORA_FIN}</div>
                        <div class="turno-horas">${horasCalculadas} horas</div>
                        <div class="turno-nombre">${turno.NOMBRE_TURNO || 'Turno'}</div>
                    </div>
                </div>
            `;
        });
        
        turnosHtml += '</div>';
        $('#turnosContainer').html(turnosHtml);
        
        // Si se autoseleccionó, forzar tipo completa y calcular horas
        if (autoSelectAll) {
            $('#tipoFaltaModal').val('completa');
            this.calculateHoras();
        }
        
        // Evento para "todos los turnos"
        $('#todosTurnos').on('change', () => {
            if ($('#todosTurnos').is(':checked')) {
                $('.turno-individual').prop('checked', false).prop('disabled', true);
                $('#tipoFaltaModal').val('completa');
                this.calculateHoras();
            } else {
                $('.turno-individual').prop('disabled', false);
            }
        });
        
        // NUEVA LÓGICA: Permitir selección de turnos individuales
        $('.turno-individual').on('change', () => {
            const checkedIndividuals = $('.turno-individual:checked');
            
            if (checkedIndividuals.length > 0) {
                // Si hay turnos individuales seleccionados
                $('#todosTurnos').prop('checked', false);
                
                // Cambiar a falta parcial automáticamente
                $('#tipoFaltaModal').val('parcial');
                
                // Calcular horas de los turnos seleccionados
                this.calculateHorasIndividuales();
            } else {
                // Si no hay turnos individuales, volver a todos
                $('#todosTurnos').prop('checked', true);
                $('#tipoFaltaModal').val('completa');
                this.calculateHoras();
            }
        });
        
        // Evento para cambio de tipo de falta
        $('#tipoFaltaModal').on('change', () => {
            this.handleTipoFaltaChange();
        });
    }

    calculateHorasIndividuales() {
        const checkedIndividuals = $('.turno-individual:checked');
        let totalHoras = 0;
        
        checkedIndividuals.each(function() {
            const horas = parseFloat($(this).data('horas')) || 0;
            totalHoras += horas;
        });
        
        $('#horasProgramadasModal').val(totalHoras.toFixed(1));
    }

    handleTipoFaltaChange() {
        const tipoFalta = $('#tipoFaltaModal').val();
        
        switch(tipoFalta) {
            case 'completa':
                // Forzar selección de todos los turnos
                $('#todosTurnos').prop('checked', true);
                $('.turno-individual').prop('checked', false).prop('disabled', true);
                this.calculateHoras();
                break;
                
            case 'parcial':
                // Permitir selección individual de turnos
                $('#todosTurnos').prop('checked', false);
                $('.turno-individual').prop('disabled', false);
                
                // Si no hay turnos seleccionados, sugerir seleccionar uno
                if ($('.turno-individual:checked').length === 0) {
                    $('#horasProgramadasModal').val('');
                }
                break;
                
            case 'tardanza':
                // Similar a parcial pero específico para tardanzas
                $('#todosTurnos').prop('checked', false);
                $('.turno-individual').prop('disabled', false);
                break;
        }
    }

    setHorarioEstandar() {
        // Sin turnos específicos, usar 8 horas estándar
        $('#horasProgramadasModal').val(8);
        $('#tipoFaltaModal').val('completa');
    }

    calcularHorasTurno(horaInicio, horaFin) {
        const inicio = new Date(`1970-01-01T${horaInicio}`);
        const fin = new Date(`1970-01-01T${horaFin}`);
        
        let diferencia = fin - inicio;
        if (diferencia < 0) {
            diferencia += 24 * 60 * 60 * 1000; // Agregar 24 horas si el turno cruza medianoche
        }
        
        return (diferencia / (1000 * 60 * 60)).toFixed(1);
    }

    calculateHoras() {
        let totalHoras = 0;
        
        if ($('#todosTurnos').is(':checked')) {
            // Sumar todas las horas de todos los turnos
            $('.turno-individual').each((i, checkbox) => {
                totalHoras += parseFloat($(checkbox).data('horas') || 0);
            });
        } else {
            // Sumar solo los turnos seleccionados
            $('.turno-individual:checked').each((i, checkbox) => {
                totalHoras += parseFloat($(checkbox).data('horas') || 0);
            });
        }
        
        if (totalHoras === 0) {
            totalHoras = 8; // Valor por defecto
        }
        
        $('#horasProgramadasModal').val(totalHoras);
    }

    handleTipoFaltaChange() {
        const tipoFalta = $('#tipoFaltaModal').val();
        
        if (tipoFalta === 'completa') {
            $('#todosTurnos').prop('checked', true);
            $('.turno-individual').prop('checked', false);
        } else if (tipoFalta === 'parcial') {
            $('#todosTurnos').prop('checked', false);
        }
        
        this.calculateHoras();
    }

    showNotification(message, type = 'info') {
        // Crear notificación temporal
        const notification = $(`
            <div class="notification notification-${type}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--primary)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: var(--shadow-lg);
                z-index: 10001;
                max-width: 400px;
            ">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
    }
}

// Inicializar cuando el DOM esté listo
$(document).ready(() => {
    window.justificacionesModal = new JustificacionesModal();
});