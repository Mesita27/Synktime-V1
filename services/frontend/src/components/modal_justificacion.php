<!-- Modal para Nueva Justificación -->
<div class="modal fade" id="modalNuevaJustificacion" tabindex="-1" aria-labelledby="modalNuevaJustificacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaJustificacionLabel">
                    <i class="fas fa-user-times text-primary"></i>
                    Nueva Justificación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formModalJustificacion">
                    <!-- Filtros de búsqueda de empleados -->
                    <div class="search-section mb-4">
                        <h6 class="text-secondary mb-3">
                            <i class="fas fa-search"></i>
                            Buscar Empleado
                        </h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="searchEmpleado" class="form-label">Buscar por nombre, DNI o correo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="searchEmpleado" class="form-control" 
                                           placeholder="Escriba para buscar...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="filterSede" class="form-label">Sede</label>
                                <select id="filterSede" class="form-select">
                                    <option value="">Todas las sedes</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterEstablecimiento" class="form-label">Establecimiento</label>
                                <select id="filterEstablecimiento" class="form-select">
                                    <option value="">Todos</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <button type="button" id="btnBuscarEmpleados" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                    Buscar Empleados Elegibles
                                </button>
                                <small class="text-muted d-block mt-1">
                                    Se mostrarán empleados sin entrada en las últimas 16 horas y con horario programado
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de empleados -->
                    <div class="empleados-section mb-4">
                        <h6 class="text-secondary mb-3">
                            <i class="fas fa-users"></i>
                            Seleccionar Empleado
                            <span id="empleadosCount" class="badge bg-primary ms-2">0</span>
                        </h6>
                        <div id="empleadosModalList" class="empleados-list" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>Use los filtros de arriba para buscar empleados</p>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de la justificación -->
                    <div class="justificacion-section">
                        <h6 class="text-secondary mb-3">
                            <i class="fas fa-clipboard-list"></i>
                            Datos de la Justificación
                        </h6>
                        <input type="hidden" id="empleadoIdModal" name="empleado_id">
                        
                        <div class="row g-3 mb-3">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="empleadoSeleccionadoInfo" class="form-label">Empleado Seleccionado</label>
                                <input type="text" id="empleadoSeleccionadoInfo" class="form-control" 
                                       placeholder="Seleccione un empleado de la lista" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="fechaFaltaModal" class="form-label">Fecha de Falta *</label>
                                <input type="date" id="fechaFaltaModal" name="fecha_falta" class="form-control" readonly required>
                                <small class="text-muted">Se establece automáticamente a la fecha actual</small>
                            </div>
                            <div class="col-md-6">
                                <label for="motivoModal" class="form-label">Motivo *</label>
                                <select id="motivoModal" name="motivo" class="form-select" required>
                                    <option value="">Seleccionar motivo...</option>
                                    <option value="ENFERMEDAD">Enfermedad</option>
                                    <option value="PERSONAL">Asunto Personal</option>
                                    <option value="FAMILIAR">Asunto Familiar</option>
                                    <option value="MEDICA">Cita Médica</option>
                                    <option value="EMERGENCIA">Emergencia</option>
                                    <option value="DUELO">Duelo</option>
                                    <option value="OTRO">Otro</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sección de Turnos -->
                        <div class="turnos-section mb-3" id="turnosSection" style="display: none;">
                            <h6 class="text-secondary mb-2">
                                <i class="fas fa-clock"></i>
                                Turnos Programados para la Fecha
                            </h6>
                            <div id="turnosContainer" class="mb-3">
                                <!-- Los turnos se cargarán dinámicamente -->
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="tipoFaltaModal" class="form-label">Tipo de Falta *</label>
                                <select id="tipoFaltaModal" name="tipo_falta" class="form-select" required>
                                    <option value="completa" selected>Jornada Completa (todos los turnos)</option>
                                    <option value="parcial">Turno Específico (parcial)</option>
                                    <option value="tardanza">Tardanza</option>
                                </select>
                                <small class="text-muted">Seleccione el tipo de falta según la situación</small>
                            </div>
                            <div class="col-md-6">
                                <label for="horasProgramadasModal" class="form-label">Horas Programadas</label>
                                <input type="number" id="horasProgramadasModal" name="horas_programadas" 
                                       class="form-control" placeholder="0" min="0" max="24" step="0.5" readonly>
                                <small class="text-muted">Se calcula automáticamente según los turnos</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="detalleAdicionalModal" class="form-label">Detalle Adicional</label>
                            <textarea id="detalleAdicionalModal" name="detalle_adicional" class="form-control" 
                                      rows="3" placeholder="Ingrese detalles adicionales sobre la justificación..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" id="btnGuardarJustificacion" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Crear Justificación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos para el modal -->
<style>
.empleados-list {
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--background);
}

.empleado-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.empleado-item:last-child {
    border-bottom: none;
}

.empleado-item:hover {
    background: rgba(67, 97, 238, 0.05);
}

.empleado-item.selected {
    background: var(--primary-lighter);
    border-left: 4px solid var(--primary);
}

.empleado-info {
    flex: 1;
}

.empleado-nombre {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.empleado-meta {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.empleado-meta span {
    margin-right: 1rem;
}

.empleado-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.status-badge-small {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-elegible {
    background: rgba(40, 167, 69, 0.1);
    color: #155724;
}

.status-no-elegible {
    background: rgba(220, 53, 69, 0.1);
    color: #721c24;
}

.search-section {
    background: rgba(67, 97, 238, 0.05);
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid var(--primary);
}

.justificacion-section {
    background: rgba(40, 167, 69, 0.05);
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid var(--success);
}

.input-group-text {
    background: var(--background);
    border-color: var(--border);
    color: var(--text-secondary);
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

.btn-outline-primary {
    color: var(--primary);
    border-color: var(--primary);
}

.btn-outline-primary:hover {
    background-color: var(--primary);
    border-color: var(--primary);
}

.loading-empleados {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.loading-empleados i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.empty-empleados {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.empty-empleados i {
    font-size: 2rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Fix para el backdrop del modal */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

.modal.show {
    z-index: 1050 !important;
}

.modal-dialog {
    z-index: 1060 !important;
}

/* Estilos para sección de turnos */
.turnos-container {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--background);
}

.turno-item {
    padding: 10px 15px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.turno-item:last-child {
    border-bottom: none;
}

.turno-item input[type="checkbox"] {
    margin-right: 10px;
}

.turno-info {
    flex-grow: 1;
}

.turno-tiempo {
    font-weight: 600;
    color: var(--primary);
}

.turno-horas {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.todos-turnos-option {
    background: var(--light-blue);
    border-radius: 6px;
    font-weight: 600;
}
</style>