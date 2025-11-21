<div class="employee-query-box">
    <form id="employeeQueryForm" class="employee-query-form">
        <div class="query-row">
            <div class="form-group"><label for="q_codigo">Código</label><input type="text" name="codigo" id="q_codigo"></div>
            <div class="form-group"><label for="q_identificacion">Identificación</label><input type="text" name="identificacion" id="q_identificacion"></div>
            <div class="form-group"><label for="q_nombre">Nombre</label><input type="text" name="nombre" id="q_nombre"></div>
            <div class="form-group">
                <label for="q_sede">Sede</label>
                <select name="sede" id="q_sede" class="filter-select"><option value="">Seleccionar una Sede</option></select>
            </div>
            <div class="form-group">
                <label for="q_establecimiento">Establecimiento</label>
                <select name="establecimiento" id="q_establecimiento" class="filter-select"><option value="">Seleccionar un Establecimiento</option></select>
            </div>
            <div class="form-group query-btns">
                <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Consultar</button>
                <a href="employee.php" class="btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
            </div>
        </div>
    </form>
</div>