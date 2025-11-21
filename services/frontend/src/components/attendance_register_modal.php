<!-- Modal Registrar Asistencia -->
<div class="modal" id="attendanceRegisterModal">
  <div class="modal-content">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeAttendanceRegisterModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-user-check"></i> Registrar Asistencia</h3>
      <p class="modal-subtitle">Fecha: <span id="reg_fecha"></span></p>
    </div>
    
    <div class="modal-body">
      <!-- Filtros de búsqueda estilizados pero manteniendo IDs originales -->
      <div class="attendance-query-box">
        <form class="attendance-query-form" autocomplete="off">
          <div class="query-row">
            <div class="form-group">
              <label for="reg_sede">Sede</label>
              <select id="reg_sede" name="sede" class="form-control"></select>
            </div>
            <div class="form-group">
              <label for="reg_establecimiento">Establecimiento</label>
              <select id="reg_establecimiento" name="establecimiento" class="form-control"></select>
            </div>
            <div class="form-group">
              <label for="codigoRegistroBusqueda">Código</label>
              <input type="text" id="codigoRegistroBusqueda" name="codigo" class="form-control" placeholder="Ingrese código">
            </div>
            <div class="form-group">
              <label for="nombreRegistroBusqueda">Nombre</label>
              <input type="text" id="nombreRegistroBusqueda" name="nombre" class="form-control" placeholder="Ingrese nombre">
            </div>
            <div class="form-group query-btns">
              <button type="button" id="btnBuscarCodigoRegistro" class="btn-primary">
                <i class="fas fa-search"></i> Buscar
              </button>
            </div>
        </div>
      </div>
      
      <!-- Información de filtro -->
      <div id="filtroInfo" class="filter-info">
        <i class="fas fa-info-circle"></i> Empleados con horarios asignados para hoy
      </div>

      <!-- Tabla de empleados con estilo mejorado -->
      <div class="employee-table-container">
        <table class="attendance-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Establecimiento</th>
              <th>Sede</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody id="attendanceRegisterTableBody">
            <!-- Aquí se cargan los empleados disponibles -->
            <tr>
              <td colspan="5" class="no-data-text">
                <div class="text-center p-3">
                  <i class="fas fa-filter fa-2x mb-2 text-muted"></i>
                  <p>Para ver empleados, seleccione al menos un filtro:</p>
                  <ul class="mt-2 list-unstyled">
                    <li><i class="fas fa-building text-primary"></i> Seleccione una sede, o</li>
                    <li><i class="fas fa-store text-success"></i> Seleccione un establecimiento, o</li>
                    <li><i class="fas fa-id-card text-info"></i> Ingrese un código de empleado, o</li>
                    <li><i class="fas fa-user text-warning"></i> Ingrese un nombre de empleado</li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>