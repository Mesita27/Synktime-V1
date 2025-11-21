<div id="employeeModal" class="modal">
  <div class="modal-content modal-content-md" id="employeeModalContent">
    <button type="button" class="modal-close" id="closeEmployeeModal">&times;</button>
    <h3 id="employeeModalTitle" style="margin-top:0;">Registrar Empleado</h3>
    <form id="employeeRegisterForm" autocomplete="off">
      <input type="hidden" name="modo" id="modoEmpleado" value="crear">
      <div class="form-row">
        <div class="form-group">
          <label for="id_empleado">Código empleado</label>
          <input type="number" name="id_empleado" id="id_empleado" required>
        </div>
        <div class="form-group">
          <label for="dni">Cédula</label>
          <input type="text" name="dni" id="dni" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input type="text" name="nombre" id="nombre" required>
        </div>
        <div class="form-group">
          <label for="apellido">Apellido</label>
          <input type="text" name="apellido" id="apellido" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="correo">Correo</label>
          <input type="email" name="correo" id="correo" required>
        </div>
        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input type="text" name="telefono" id="telefono">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="sedeEmpleado">Sede</label>
          <select name="sede" id="sedeEmpleado" required>
            <option value="">Seleccione sede</option>
          </select>
        </div>
        <div class="form-group">
          <label for="establecimientoEmpleado">Establecimiento</label>
          <select name="establecimiento" id="establecimientoEmpleado" required>
            <option value="">Seleccione establecimiento</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="fecha_ingreso">Fecha de ingreso</label>
          <input type="date" name="fecha_ingreso" id="fecha_ingreso" required>
        </div>
        <div class="form-group">
          <label for="estado">Estado</label>
          <select name="estado" id="estado" required>
            <option value="A">Activo</option>
            <option value="I">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-primary" id="employeeModalSubmitBtn">Registrar</button>
        <button type="button" class="btn-secondary" id="cancelEmployeeModal">Cancelar</button>
      </div>
      <div id="employeeFormError" style="color:#e53e3e;text-align:center;margin-top:10px;display:none;"></div>
    </form>
  </div>
</div>

<!-- Modal para confirmación de eliminación -->
<div id="employeeDeleteModal" class="modal">
  <div class="modal-content modal-content-sm">
    <button type="button" class="modal-close" id="closeEmployeeDeleteModal">&times;</button>
    <h3>Eliminar empleado</h3>
    <p id="deleteStep1">¿Estás seguro que deseas eliminar este empleado?</p>
    <p id="deleteStep2" style="display:none;">Esta acción es irreversible. ¿Confirmas eliminarlo?</p>
    <div class="form-actions" style="margin-top:1.5rem;">
      <button type="button" class="btn-danger" id="confirmDeleteEmployeeBtn" style="display:none;">Eliminar definitivamente</button>
      <button type="button" class="btn-primary" id="verifyDeleteEmployeeBtn">Sí, eliminar</button>
      <button type="button" class="btn-secondary" id="cancelDeleteEmployeeBtn">Cancelar</button>
    </div>
  </div>
</div>

<!-- Modal de ayuda para empleados -->
<div class="modal" id="employeeHelpModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" style="color: white;">
                    <i class="fas fa-question-circle"></i> Ayuda - Módulo de Empleados
                </h3>
                <button type="button" class="modal-close" onclick="closeEmployeeHelpModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tabs de ayuda -->
                <div class="help-tabs">
                    <button type="button" class="help-tab active" onclick="switchEmployeeHelpTab('general')">
                        <i class="fas fa-users"></i> Gestión General
                    </button>
                    <button type="button" class="help-tab" onclick="switchEmployeeHelpTab('registro')">
                        <i class="fas fa-user-plus"></i> Registrar Empleado
                    </button>
                    <button type="button" class="help-tab" onclick="switchEmployeeHelpTab('filtros')">
                        <i class="fas fa-filter"></i> Filtros y Búsqueda
                    </button>
                    <button type="button" class="help-tab" onclick="switchEmployeeHelpTab('exportar')">
                        <i class="fas fa-file-excel"></i> Exportar Datos
                    </button>
                </div>

                <!-- Contenido de tabs -->
                <div id="employee-help-general" class="help-content active">
                    <h4>¿Qué es el módulo de empleados?</h4>
                    <p>El módulo de empleados es el centro de gestión de toda la información relacionada con el personal de la empresa.
                    Permite mantener un registro completo y actualizado de todos los empleados activos e inactivos.</p>

                    <h4>Funciones principales:</h4>
                    <ul>
                        <li><strong>Registro de empleados:</strong> Crear nuevos registros con toda la información necesaria</li>
                        <li><strong>Gestión de datos:</strong> Actualizar información personal, contacto y ubicación</li>
                        <li><strong>Control de estado:</strong> Activar/desactivar empleados según su situación laboral</li>
                        <li><strong>Filtros avanzados:</strong> Buscar y filtrar empleados por múltiples criterios</li>
                        <li><strong>Exportación de datos:</strong> Generar reportes en Excel con información completa</li>
                    </ul>

                    <h4>Información que se gestiona:</h4>
                    <ul>
                        <li><strong>Datos personales:</strong> Nombre, apellido, identificación</li>
                        <li><strong>Información de contacto:</strong> Correo electrónico y teléfono</li>
                        <li><strong>Ubicación laboral:</strong> Sede y establecimiento</li>
                        <li><strong>Datos administrativos:</strong> Código de empleado, fecha de contratación, estado</li>
                    </ul>
                </div>

                <div id="employee-help-registro" class="help-content">
                    <h4>¿Cómo registrar un nuevo empleado?</h4>
                    <p>El proceso de registro es simple y requiere completar toda la información necesaria para el correcto funcionamiento del sistema.</p>

                    <h4>Pasos para registrar:</h4>
                    <ol>
                        <li>Haz clic en "Registrar empleado" (botón azul con icono de usuario)</li>
                        <li>Completa el <strong>Código del empleado</strong> (número único identificador)</li>
                        <li>Ingresa la <strong>Cédula/DNI</strong> del empleado</li>
                        <li>Completa <strong>Nombre</strong> y <strong>Apellido</strong></li>
                        <li>Ingresa <strong>Correo electrónico</strong> y <strong>Teléfono</strong> (opcional)</li>
                        <li>Selecciona la <strong>Sede</strong> donde trabajará</li>
                        <li>Selecciona el <strong>Establecimiento</strong> correspondiente</li>
                        <li>Haz clic en "Guardar" para completar el registro</li>
                    </ol>

                    <h4>Consideraciones importantes:</h4>
                    <ul>
                        <li><strong>Código único:</strong> Cada empleado debe tener un código numérico único</li>
                        <li><strong>Campos obligatorios:</strong> Código, cédula, nombre, apellido, sede y establecimiento</li>
                        <li><strong>Correo válido:</strong> Se recomienda usar correos corporativos cuando sea posible</li>
                        <li><strong>Estado inicial:</strong> Los nuevos empleados se registran como "Activos" por defecto</li>
                    </ul>

                    <h4>¿Cómo editar un empleado existente?</h4>
                    <ol>
                        <li>Busca el empleado usando los filtros disponibles</li>
                        <li>Haz clic en el botón "Editar" (icono de lápiz) en la columna Acciones</li>
                        <li>Modifica la información necesaria</li>
                        <li>Haz clic en "Actualizar" para guardar los cambios</li>
                    </ol>
                </div>

                <div id="employee-help-filtros" class="help-content">
                    <h4>¿Cómo buscar y filtrar empleados?</h4>
                    <p>El sistema ofrece múltiples opciones de filtrado para encontrar rápidamente empleados específicos o grupos de empleados.</p>

                    <h4>Tipos de filtros disponibles:</h4>
                    <ul>
                        <li><strong>Código:</strong> Buscar por el código numérico del empleado</li>
                        <li><strong>Identificación:</strong> Buscar por número de cédula</li>
                        <li><strong>Nombre:</strong> Buscar por nombre o apellido (búsqueda parcial)</li>
                        <li><strong>Sede:</strong> Filtrar empleados de una sede específica</li>
                        <li><strong>Establecimiento:</strong> Filtrar por establecimiento específico</li>
                        <li><strong>Estado:</strong> Mostrar solo empleados activos o inactivos</li>
                    </ul>

                    <h4>¿Cómo usar los filtros?</h4>
                    <ol>
                        <li>Selecciona los criterios de búsqueda en el panel de filtros</li>
                        <li>Haz clic en "Buscar" para aplicar los filtros</li>
                        <li>La tabla se actualizará automáticamente mostrando solo los resultados filtrados</li>
                        <li>Para limpiar los filtros, haz clic en "Limpiar filtros"</li>
                    </ol>

                    <h4>Consejos de búsqueda:</h4>
                    <ul>
                        <li><strong>Búsqueda parcial:</strong> En nombre e identificación, puedes escribir parte del texto</li>
                        <li><strong>Múltiples filtros:</strong> Puedes combinar varios filtros para búsquedas más específicas</li>
                        <li><strong>Resultados en tiempo real:</strong> La tabla se actualiza automáticamente al aplicar filtros</li>
                        <li><strong>Exportación filtrada:</strong> El botón de exportar respeta los filtros aplicados</li>
                    </ul>
                </div>

                <div id="employee-help-exportar" class="help-content">
                    <h4>📊 Exportar Datos de Empleados</h4>

                    <h5>¿Qué incluye la exportación?</h5>
                    <p>La función de exportación genera un archivo Excel con toda la información de empleados:</p>
                    <ul>
                        <li><strong>Datos personales:</strong> Código, identificación, nombre completo</li>
                        <li><strong>Información de contacto:</strong> Correo electrónico y teléfono</li>
                        <li><strong>Ubicación laboral:</strong> Sede y establecimiento</li>
                        <li><strong>Datos administrativos:</strong> Fecha de contratación y estado</li>
                        <li><strong>Información de empresa:</strong> Nombre de empresa, sede y establecimiento en el encabezado</li>
                    </ul>

                    <h5>Características del archivo Excel:</h5>
                    <ul>
                        <li><strong>Formato profesional:</strong> Encabezados con colores, bordes y estilos</li>
                        <li><strong>Autoajuste de columnas:</strong> Columnas dimensionadas automáticamente</li>
                        <li><strong>Información de contexto:</strong> Fecha de exportación y filtros aplicados</li>
                        <li><strong>Orientación horizontal:</strong> Optimizado para impresión</li>
                    </ul>

                    <h5>¿Cómo exportar?</h5>
                    <ol>
                        <li>Aplica los filtros deseados (opcional, puedes exportar todos los empleados)</li>
                        <li>Haz clic en "Exportar .xlsx" (botón gris con icono de Excel)</li>
                        <li>El sistema procesará automáticamente los datos</li>
                        <li>Se descargará automáticamente un archivo con nombre descriptivo</li>
                    </ol>

                    <h5>Usos del archivo exportado:</h5>
                    <ul>
                        <li><strong>Reportes de RRHH:</strong> Listados completos del personal</li>
                        <li><strong>Auditorías:</strong> Verificación de datos y estados</li>
                        <li><strong>Planificación:</strong> Base de datos para otros sistemas</li>
                        <li><strong>Backup:</strong> Copia de seguridad de información de empleados</li>
                        <li><strong>Integraciones:</strong> Datos para sistemas externos (nómina, etc.)</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary" onclick="closeEmployeeHelpModal()">Entendido</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para gestionar vacaciones -->
<div id="employeeVacationModal" class="modal">
  <div class="modal-content modal-content-wide" id="employeeVacationContent">
    <button type="button" class="modal-close" id="closeVacationModal">&times;</button>
    <div class="vacation-modal-header">
      <h3><i class="fas fa-umbrella-beach"></i> Vacaciones de <span id="vacationEmployeeName"></span></h3>
      <p id="vacationEmployeeMeta" class="vacation-meta"></p>
    </div>

    <div class="vacation-modal-body">
      <div id="vacationLoading" class="vacation-loading" style="display:none;">
        <i class="fas fa-spinner fa-spin"></i> Cargando información de vacaciones...
      </div>

      <div id="vacationContent" style="display:none;">
      <div class="vacation-summary" id="vacationSummaryCards">
        <!-- Se llena dinámicamente -->
      </div>

      <div class="vacation-toolbar">
        <div class="vacation-toolbar-left">
          <button type="button" class="btn-primary" id="btnNewVacation">
            <i class="fas fa-plus"></i> Nueva vacación
          </button>
        </div>
        <div class="vacation-toolbar-right" id="vacationAlert" style="display:none;"></div>
      </div>

      <div class="vacation-table-wrapper">
        <table class="vacation-table" id="vacationTable">
          <thead>
            <tr>
              <th>Estado</th>
              <th>Periodo</th>
              <th>Días</th>
              <th>Motivo</th>
              <th>Observaciones</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- Se llena dinámicamente -->
          </tbody>
        </table>
        <div id="vacationEmptyState" class="vacation-empty" style="display:none;">
          <i class="fas fa-plane"></i>
          <p>Este empleado aún no tiene vacaciones registradas.</p>
        </div>
      </div>

      <div class="vacation-form-container" id="vacationFormContainer">
        <div class="vacation-form-header">
          <h4 id="vacationFormTitle"><i class="fas fa-calendar-plus"></i> Registrar nuevas vacaciones</h4>
          <button type="button" class="btn-secondary" id="cancelVacationEdit" style="display:none;">
            Cancelar edición
          </button>
        </div>
        <form id="vacationForm" autocomplete="off">
          <input type="hidden" id="vacationFormMode" value="create">
          <input type="hidden" id="vacationId" value="">

          <div class="form-row">
            <div class="form-group">
              <label for="vacationStartDate">Fecha inicio</label>
              <input type="date" id="vacationStartDate" name="fecha_inicio" required>
            </div>
            <div class="form-group">
              <label for="vacationEndDate">Fecha fin</label>
              <input type="date" id="vacationEndDate" name="fecha_fin" required>
            </div>
            <div class="form-group">
              <label for="vacationReactiveToggle">Reactivar automáticamente</label>
              <div class="toggle-wrapper">
                <label class="switch">
                  <input type="checkbox" id="vacationReactiveToggle" name="reactivacion_automatica" checked>
                  <span class="slider"></span>
                </label>
                <span class="toggle-text">Al finalizar, reactivar estado del empleado</span>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="vacationReason">Motivo</label>
              <input type="text" id="vacationReason" name="motivo" placeholder="Vacaciones anuales, permiso especial, etc." maxlength="200">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label for="vacationNotes">Observaciones</label>
              <textarea id="vacationNotes" name="observaciones" rows="3" placeholder="Comentarios adicionales (opcional)"></textarea>
            </div>
          </div>

          <div id="vacationFormError" class="form-error" style="display:none;"></div>
          <div id="vacationFormSuccess" class="form-success" style="display:none;"></div>

          <div class="form-actions">
            <button type="submit" class="btn-primary" id="vacationFormSubmit">
              <i class="fas fa-save"></i> Guardar
            </button>
            <button type="button" class="btn-secondary" id="vacationFormReset">
              Limpiar
            </button>
          </div>
        </form>
      </div>
      </div>
    </div>
  </div>
</div>