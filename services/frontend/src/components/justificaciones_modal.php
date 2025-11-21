<!-- Modal de Justificaciones -->
<div class="modal fade" id="justificacionesModal" tabindex="-1" aria-labelledby="justificacionesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-xl-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="justificacionesModalLabel">
                    <i class="fas fa-file-text"></i> Justificar Faltas de Empleados
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filtros -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="filtroEstablecimiento" class="form-label">Establecimiento</label>
                        <select class="form-select" id="filtroEstablecimiento" onchange="cargarSedesPorEstablecimiento(); cargarEmpleadosElegibles();">
                            <option value="">Todos los establecimientos</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="filtroSede" class="form-label">Sede</label>
                        <select class="form-select" id="filtroSede" onchange="cargarEmpleadosElegibles();">
                            <option value="">Todas las sedes</option>
                        </select>
                    </div>
                </div>

                <!-- Información de la regla de 16 horas -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Regla de Elegibilidad:</strong> Solo se muestran empleados que tenían horario programado hace más de 16 horas y no registraron asistencia.
                </div>

                <!-- Lista de empleados elegibles -->
                <div class="row">
                    <div class="col-lg-6 col-md-12">
                        <h6><i class="fas fa-users"></i> Empleados Elegibles para Justificación</h6>
                        <div class="empleados-elegibles-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 1rem;">
                            <div id="loadingEmpleados" class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                <span class="ms-2">Cargando empleados...</span>
                            </div>
                            <div id="listaEmpleadosElegibles">
                                <!-- Se cargan via JavaScript -->
                            </div>
                            <div id="noEmpleadosMessage" class="text-muted text-center" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                No hay empleados elegibles para justificación en este momento.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 col-md-12 mt-4 mt-lg-0">
                        <h6><i class="fas fa-edit"></i> Crear Justificación</h6>
                        <form id="formJustificacion">
                            <input type="hidden" id="empleadoSeleccionado" name="empleado_id">
                            <input type="hidden" id="fechaFalta" name="fecha_falta">
                            <input type="hidden" id="horasProgramadas" name="horas_programadas">
                            
                            <div class="mb-3">
                                <label class="form-label">Empleado Seleccionado</label>
                                <input type="text" class="form-control" id="empleadoNombre" readonly placeholder="Selecciona un empleado de la lista">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Fecha de la Falta</label>
                                <input type="text" class="form-control" id="fechaFaltaDisplay" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Horas Programadas</label>
                                <input type="text" class="form-control" id="horasProgramadasDisplay" readonly>
                            </div>
                            
                            <!-- Selector de turnos para empleados con múltiples turnos -->
                            <div class="mb-3" id="selectorTurnosContainer" style="display: none;">
                                <label for="turnoSeleccionado" class="form-label">Turno a Justificar</label>
                                <select class="form-select" id="turnoSeleccionado" name="turno_id">
                                    <option value="">Seleccione un turno...</option>
                                </select>
                                <div class="form-text text-info">
                                    <i class="fas fa-info-circle"></i> Este empleado tiene múltiples turnos. Seleccione cuál desea justificar.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="motivoJustificacion" class="form-label">Motivo de la Justificación *</label>
                                <select class="form-select" id="motivoJustificacion" name="motivo" required>
                                    <option value="">Selecciona un motivo...</option>
                                    <option value="ENFERMEDAD">Enfermedad</option>
                                    <option value="CALAMIDAD_DOMESTICA">Calamidad Doméstica</option>
                                    <option value="LICENCIA_MEDICA">Licencia Médica</option>
                                    <option value="PERMISO_PERSONAL">Permiso Personal</option>
                                    <option value="FALTA_SISTEMA">Falla del Sistema</option>
                                    <option value="ERROR_HORARIO">Error en Horario</option>
                                    <option value="OTRO">Otro</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="detalleJustificacion" class="form-label">Detalle de la Justificación</label>
                                <textarea class="form-control" id="detalleJustificacion" name="detalle_adicional" rows="3" placeholder="Describe los detalles de la justificación..."></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success" id="btnCrearJustificacion">
                                    <i class="fas fa-save"></i> Crear Justificación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de justificaciones existentes -->
                <div class="mt-4">
                    <h6><i class="fas fa-list"></i> Justificaciones Recientes</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th class="d-none d-sm-table-cell">Empleado</th>
                                    <th class="d-none d-md-table-cell">Fecha</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th class="d-none d-lg-table-cell">Creado por</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaJustificacionesRecientes">
                                <!-- Se cargan via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos para el modal -->
<style>
.empleado-item {
    padding: 10px;
    margin-bottom: 5px;
    border: 1px solid #e9ecef;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.empleado-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.empleado-item.selected {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.empleado-info {
    font-size: 0.9rem;
}

.empleado-codigo {
    font-weight: bold;
    color: #495057;
}

.empleado-item.selected .empleado-codigo {
    color: white;
}

.empleado-fecha {
    font-size: 0.8rem;
    color: #6c757d;
}

.empleado-item.selected .empleado-fecha {
    color: #e9ecef;
}

.empleados-elegibles-container {
    background-color: #f8f9fa;
}

#justificacionesModal .modal-fullscreen-xl-down {
    max-width: 95vw;
    max-height: 95vh;
}

/* Responsive improvements */
@media (max-width: 1200px) {
    #justificacionesModal .modal-fullscreen-xl-down {
        max-width: 95vw;
        margin: 0 auto;
    }
}

@media (max-width: 768px) {
    #justificacionesModal .modal-fullscreen-xl-down {
        max-width: 98vw;
        margin: 1vh auto;
    }

    #justificacionesModal .modal-body {
        padding: 1rem;
    }

    #justificacionesModal .empleados-elegibles-container {
        max-height: 300px;
    }

    #justificacionesModal .table-responsive {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    #justificacionesModal .modal-fullscreen-xl-down {
        max-width: 100vw;
        margin: 0;
        height: 100vh;
    }

    #justificacionesModal .modal-content {
        height: 100vh;
        border-radius: 0;
    }

    #justificacionesModal .modal-body {
        padding: 0.75rem;
        overflow-y: auto;
        max-height: calc(100vh - 120px);
    }

    #justificacionesModal .empleados-elegibles-container {
        max-height: 250px;
    }

    #justificacionesModal .form-select,
    #justificacionesModal .form-control {
        font-size: 0.875rem;
    }

    /* Ocultar elementos no esenciales en móviles */
    #justificacionesModal .alert {
        font-size: 0.875rem;
        padding: 0.5rem;
    }

    /* Mejorar botones en móviles */
    #justificacionesModal .btn {
        min-height: 44px; /* Apple recomienda 44px para touch targets */
        font-size: 0.9rem;
    }
}

/* Mejoras visuales adicionales */
#justificacionesModal .empleados-elegibles-container {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

#justificacionesModal .form-floating > label {
    padding: 1rem 0.75rem;
}

#justificacionesModal .table-responsive {
    border-radius: 0.375rem;
    overflow: hidden;
}

/* Optimizar espacio en modales muy anchos */
@media (min-width: 1400px) {
    #justificacionesModal .modal-body {
        padding: 2rem 3rem;
    }

    #justificacionesModal .empleados-elegibles-container {
        max-height: 500px;
    }

    #justificacionesModal .col-lg-6 {
        padding: 0 1rem;
    }
}

/* Mejorar espaciado en diferentes tamaños */
@media (min-width: 992px) and (max-width: 1399px) {
    #justificacionesModal .modal-body {
        padding: 2rem;
    }
}

@media (max-width: 991px) and (min-width: 769px) {
    #justificacionesModal .modal-body {
        padding: 1.5rem;
    }
}
</style>