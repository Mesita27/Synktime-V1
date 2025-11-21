<!-- Modal para agregar/editar observaciones de asistencia -->
<div class="modal" id="observationModal">
  <div class="modal-content">
    <button type="button" class="modal-close" onclick="closeObservationModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-comment-alt"></i> <span id="observationModalTitle">Agregar Observación</span></h3>
    </div>
    
    <div class="modal-body">
      <p id="observationModalInfo" class="modal-subtitle"></p>
      
      <div class="form-group">
        <label for="observacionTexto">Observación (máx. 200 caracteres):</label>
        <textarea id="observacionTexto" class="form-control" maxlength="200" rows="4" placeholder="Escriba su observación aquí..."></textarea>
        <small class="char-counter"><span id="charCount">0</span>/200</small>
      </div>
      
      <input type="hidden" id="observacionIdAsistencia" value="">
      <input type="hidden" id="observacionTipo" value="">
    </div>
    
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeObservationModal()">Cancelar</button>
      <button type="button" class="btn-primary" id="btnSaveObservation" onclick="saveObservation()">
        <i class="fas fa-save"></i> Guardar
      </button>
    </div>
  </div>
</div>