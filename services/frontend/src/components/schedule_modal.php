<!-- Modal para crear/editar horarios -->
<div class="modal" id="scheduleModal">
  <div class="modal-content">
    <button type="button" class="modal-close" onclick="closeScheduleModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-clock"></i> <span id="scheduleModalTitle">Registrar Horario</span></h3>
    </div>
    
    <div class="modal-body">
      <form id="scheduleForm" class="schedule-form">
        <input type="hidden" id="schedule_id" name="id_horario">
        
        <div class="form-row">
          <div class="form-group">
            <label for="schedule_nombre">Nombre del Horario*</label>
            <input type="text" id="schedule_nombre" name="nombre" class="form-control" required maxlength="50" placeholder="Ej: Horario Matutino">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="schedule_sede">Sede*</label>
            <select id="schedule_sede" name="sede" class="form-control" required></select>
          </div>
          
          <div class="form-group">
            <label for="schedule_establecimiento">Establecimiento*</label>
            <select id="schedule_establecimiento" name="establecimiento" class="form-control" required></select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="schedule_hora_entrada">Hora de Entrada*</label>
            <input type="time" id="schedule_hora_entrada" name="hora_entrada" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="schedule_hora_salida">Hora de Salida*</label>
            <input type="time" id="schedule_hora_salida" name="hora_salida" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="schedule_tolerancia">Tolerancia (min)*</label>
            <input type="number" id="schedule_tolerancia" name="tolerancia" class="form-control" min="0" max="60" required placeholder="Ej: 15">
          </div>
        </div>
        
        <div class="form-group">
          <label>Días de la semana*</label>
          <div class="days-selector">
            <label class="day-checkbox">
              <input type="checkbox" name="dias[]" value="1"> Lunes
            </label>
            <label class="day-checkbox">
              <input type="checkbox" name="dias[]" value="2"> Martes
            </label>
            <label class="day-checkbox">
              <input type="checkbox" name="dias[]" value="3"> Miércoles
            </label>
            <label class="day-checkbox">
              <input type="checkbox" name="dias[]" value="4"> Jueves
            </label>
            <label class="day-checkbox">
              <input type="checkbox" name="dias[]" value="5"> Viernes
            </label>
            <label class="day-checkbox">
              <input type="checkbox" name="dias[]" value="6"> Sábado
            </label>
            <label class="day-checkbox">
              <input type="checkbox" name="dias[]" value="7"> Domingo
            </label>
          </div>
        </div>
      </form>
    </div>
    
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeScheduleModal()">Cancelar</button>
      <button type="button" class="btn-primary" id="btnSaveSchedule">
        <i class="fas fa-save"></i> Guardar
      </button>
    </div>
  </div>
</div>