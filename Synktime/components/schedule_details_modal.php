<!-- Modal para ver detalles de un horario -->
<div class="modal" id="scheduleDetailsModal">
  <div class="modal-content">
    <button type="button" class="modal-close" onclick="closeScheduleDetailsModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-info-circle"></i> Detalles del Horario</h3>
    </div>
    
    <div class="modal-body">
      <div class="schedule-detail-info">
        <h4 id="scheduleDetailName">Nombre del Horario</h4>
        
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Sede:</span>
            <span id="scheduleDetailSede" class="detail-value"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Establecimiento:</span>
            <span id="scheduleDetailEstablecimiento" class="detail-value"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Días:</span>
            <div id="scheduleDetailDays" class="detail-value days-badges"></div>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Hora Entrada:</span>
            <span id="scheduleDetailEntrada" class="detail-value"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Hora Salida:</span>
            <span id="scheduleDetailSalida" class="detail-value"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Tolerancia:</span>
            <span id="scheduleDetailTolerancia" class="detail-value"></span>
          </div>
        </div>
      </div>
      
      <div class="schedule-employees-list">
        <h4>Empleados Asignados</h4>
        <div class="table-container">
          <table class="schedule-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Desde</th>
                <th>Hasta</th>
              </tr>
            </thead>
            <tbody id="scheduleEmployeesTable">
              <!-- Empleados asignados se cargan aquí -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeScheduleDetailsModal()">Cerrar</button>
    </div>
  </div>
</div>