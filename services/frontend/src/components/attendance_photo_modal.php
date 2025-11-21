<!-- Modal para captura de foto de asistencia -->
<div class="modal" id="attendancePhotoModal">
  <div class="modal-content photo-capture-modal">
    <button type="button" class="modal-close" onclick="closeAttendancePhotoModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-camera"></i> Capturar Foto</h3>
    </div>
    
    <div class="modal-body">
      <div class="camera-container">
        <video id="video" autoplay playsinline></video>
        <canvas id="canvas" width="640" height="480" style="display:none;"></canvas>
      </div>
      
      <div id="photoPreview" class="photo-preview"></div>
      
      <div class="camera-actions">
        <!-- Important: buttons are not inside a form and have type="button" -->
        <button type="button" class="btn-secondary" id="takePhotoBtn">
          <i class="fas fa-camera"></i> Tomar Foto
        </button>
        <button type="button" class="btn-primary" id="saveAttendanceBtn" disabled>
          <i class="fas fa-save"></i> Registrar Asistencia
        </button>
      </div>
    </div>
  </div>
</div>