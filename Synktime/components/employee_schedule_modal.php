<!-- Modal para vinculación de empleados con horarios -->
<div class="modal" id="employeeScheduleModal">
  <div class="modal-content large-modal">
    <button type="button" class="modal-close" onclick="closeEmployeeScheduleModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title">
        <i class="fas fa-link"></i> 
        <span id="employeeScheduleModalTitle">Gestión de Horarios</span>
      </h3>
    </div>
    
    <div class="modal-body">
      <div class="employee-info-panel">
        <div class="employee-photo">
          <i class="fas fa-user-circle"></i>
        </div>
        <div class="employee-details">
          <h4 id="employeeName">Nombre del Empleado</h4>
          <div id="employeeInfo" class="employee-metadata">
            <!-- Detalles del empleado aquí -->
          </div>
          <input type="hidden" id="currentEmployeeId">
        </div>
      </div>
      
      <div class="schedule-management-container">
        <div class="schedule-panel">
          <h4 class="panel-title"><i class="fas fa-clock"></i> Horarios Asignados</h4>
          <div class="schedule-list-container">
            <table class="schedule-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllAssignedSchedules"></th>
                  <th>Nombre</th>
                  <th>Días</th>
                  <th>Horario</th>
                  <th>Desde</th>
                  <th>Hasta</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="assignedSchedulesTable">
                <!-- Horarios asignados se cargan aquí -->
              </tbody>
            </table>
          </div>
          <div class="panel-actions">
            <button type="button" class="btn-danger btn-sm" id="btnRemoveSchedules" disabled>
              <i class="fas fa-unlink"></i> Desvincular Seleccionados
            </button>
          </div>
        </div>
        
        <div class="schedule-panel">
          <h4 class="panel-title"><i class="fas fa-plus-circle"></i> Horarios Disponibles</h4>
          <div class="schedule-list-container">
            <div class="filter-bar">
              <input type="text" id="filterAvailableSchedules" class="form-control" placeholder="Filtrar horarios...">
            </div>
            <table class="schedule-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllAvailableSchedules"></th>
                  <th>Nombre</th>
                  <th>Días</th>
                  <th>Horario</th>
                </tr>
              </thead>
              <tbody id="availableSchedulesTable">
                <!-- Horarios disponibles se cargan aquí -->
              </tbody>
            </table>
          </div>
          <div class="panel-actions">
            <button type="button" class="btn-primary btn-sm" id="btnAssignSchedules" disabled>
              <i class="fas fa-link"></i> Vincular Seleccionados
            </button>
          </div>
        </div>
      </div>
      
      <div class="assignment-form">
        <h4 class="panel-title"><i class="fas fa-calendar-alt"></i> Vigencia</h4>
        <div class="form-row">
          <div class="form-group">
            <label for="fechaDesde">Fecha desde*</label>
            <input type="date" id="fechaDesde" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="fechaHasta">Fecha hasta</label>
            <input type="date" id="fechaHasta" class="form-control">
            <small class="form-text">Dejar en blanco si no tiene fecha de finalización</small>
          </div>
        </div>
      </div>
    </div>
    
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeEmployeeScheduleModal()">Cerrar</button>
    </div>
  </div>
</div>