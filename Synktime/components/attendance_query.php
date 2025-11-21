<div class="attendance-query-box">
    <form id="attendanceQueryForm" class="attendance-query-form" autocomplete="off">
        <div class="query-row">
            <div class="form-group">
                <label for="filtro_sede">Sede</label>
                <select id="filtro_sede" name="sede" class="form-control">
                    <option value="">Todas las sedes</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filtro_establecimiento">Establecimiento</label>
                <select id="filtro_establecimiento" name="establecimiento" class="form-control">
                    <option value="">Todos los establecimientos</option>
                </select>
            </div>
            <div class="form-group">
                <label for="codigoBusqueda">Código Empleado</label>
                <input type="text" id="codigoBusqueda" name="codigo" class="form-control" placeholder="Ingrese código" maxlength="20">
            </div>
            <div class="form-group">
                <label for="nombreBusqueda">Nombre Empleado</label>
                <input type="text" id="nombreBusqueda" name="nombre" class="form-control" placeholder="Ingrese nombre o apellido" maxlength="100">
            </div>
            <div class="form-group query-btns">
                <button type="button" id="btnBuscarCodigo" class="btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" id="btnLimpiar" class="btn-secondary">
                    <i class="fas fa-redo"></i> Limpiar
                </button>
            </div>
        </div>
    </form>
</div>