<?php
// modal_ayuda_justificaciones.php - Modal de ayuda para el módulo de justificaciones
?>
<!-- Modal de Ayuda del Módulo de Justificaciones (Personalizado) -->
<div id="modalAyudaJustificaciones" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal-content">
        <div class="custom-modal-header">
            <h5 class="custom-modal-title">
                <i class="fas fa-question-circle me-2"></i>
                Ayuda - Módulo de Justificaciones
            </h5>
            <button type="button" class="custom-modal-close" onclick="hideHelpModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="custom-modal-body">
            <div class="help-content">
                <div class="help-alert help-alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>¿Qué es el Módulo de Justificaciones?</h6>
                    <p>El módulo de justificaciones permite gestionar y validar las ausencias laborales de los empleados de manera eficiente y automática.</p>
                </div>

                <h6 class="help-section-title"><i class="fas fa-cogs me-2"></i>Funcionalidades Principales</h6>
                <ul class="help-list">
                    <li><i class="fas fa-plus-circle text-success me-2"></i><strong>Crear justificaciones:</strong> Registra ausencias con motivos válidos</li>
                    <li><i class="fas fa-list text-info me-2"></i><strong>Ver justificaciones:</strong> Lista completa de todas las justificaciones</li>
                    <li><i class="fas fa-filter text-warning me-2"></i><strong>Filtros avanzados:</strong> Busca por empleado, fecha, estado y tipo</li>
                    <li><i class="fas fa-clock text-secondary me-2"></i><strong>Historial completo:</strong> Registro de todas las acciones realizadas</li>
                </ul>

                <h6 class="help-section-title"><i class="fas fa-play-circle me-2"></i>Cómo Usar el Módulo</h6>
                <ol class="help-steps">
                    <li><strong>Crear justificación:</strong> Haz clic en "Nueva Justificación"</li>
                    <li><strong>Seleccionar empleado:</strong> Elige de la lista de empleados disponibles</li>
                    <li><strong>Fecha de ausencia:</strong> Se selecciona la fecha de la falta laboral basada en el dia actual</li>
                    <li><strong>Motivo:</strong> Elige el tipo de justificación (enfermedad, personal, etc.)</li>
                    <li><strong>Detalles:</strong> Agrega observaciones adicionales si es necesario</li>
                    <li><strong>Guardar:</strong> La justificación se registra automáticamente</li>
                </ol>

                <h6 class="help-section-title"><i class="fas fa-filter me-2"></i>Filtros Disponibles</h6>
                <div class="help-filters">
                    <div class="filter-item">
                        <strong>Por Empleado:</strong> Busca justificaciones de un empleado específico
                    </div>
                    <div class="filter-item">
                        <strong>Por Fecha:</strong> Rango de fechas para ver justificaciones en período
                    </div>
                    <div class="filter-item">
                        <strong>Por Estado:</strong> Filtra por pendiente, aprobado o rechazado
                    </div>
                    <div class="filter-item">
                        <strong>Por Tipo:</strong> Filtra por tipo de justificación
                    </div>
                </div>

                <div class="help-alert help-alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Reglas y Restricciones</h6>
                    <ul class="help-rules">
                        <li><strong>Horario requerido:</strong> Solo aparecen empleados con horario programado</li>
                        <li><strong>Tiempo límite:</strong> Las justificaciones deben hacerse dentro de las 16 horas posteriores al horario de entrada</li>
                        <li><strong>Validación automática:</strong> El sistema valida que no exista asistencia registrada</li>
                        <li><strong>Historial completo:</strong> Todas las acciones quedan registradas para auditoría</li>
                    </ul>
                </div>

                <div class="help-alert help-alert-success">
                    <h6><i class="fas fa-lightbulb me-2"></i>Consejos Útiles</h6>
                    <ul class="help-tips">
                        <li>Usa los filtros para encontrar justificaciones rápidamente</li>
                        <li>Las justificaciones aparecen automáticamente cuando un empleado no registra asistencia</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="custom-btn custom-btn-secondary" onclick="hideHelpModal()">
                <i class="fas fa-times me-1"></i>
                Cerrar
            </button>
        </div>
    </div>
</div>