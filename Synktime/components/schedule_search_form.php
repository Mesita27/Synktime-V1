<form id="scheduleQueryForm" class="schedule-query-form" autocomplete="off" style="margin-bottom: 1.5rem;">
    <div class="query-row">
        <div class="form-group">
            <label for="q_id_horario">ID</label>
            <input type="text" id="q_id_horario" name="id_horario" placeholder="ID horario">
        </div>
        <div class="form-group">
            <label for="q_nombre">Nombre</label>
            <input type="text" id="q_nombre" name="nombre" placeholder="Nombre de horario">
        </div>
        <div class="form-group">
            <label for="q_sede">Sede</label>
            <select id="q_sede" name="sede"></select>
        </div>
        <div class="form-group">
            <label for="q_establecimiento">Establecimiento</label>
            <select id="q_establecimiento" name="establecimiento"></select>
        </div>
        <div class="form-group">
            <label for="q_dia">Día</label>
            <select id="q_dia" name="dia">
                <option value="">Seleccionar una Sede</option>
                <option value="1">Lunes</option>
                <option value="2">Martes</option>
                <option value="3">Miércoles</option>
                <option value="4">Jueves</option>
                <option value="5">Viernes</option>
                <option value="6">Sábado</option>
                <option value="7">Domingo</option>
            </select>
        </div>
        <div class="form-group">
            <label for="q_hora_entrada">Entrada</label>
            <input type="time" id="q_hora_entrada" name="hora_entrada">
        </div>
        <div class="form-group">
            <label for="q_hora_salida">Salida</label>
            <input type="time" id="q_hora_salida" name="hora_salida">
        </div>
        <div class="form-group query-btns">
            <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Consultar</button>
            <button type="button" class="btn-secondary" id="btnClearScheduleQuery"><i class="fas fa-redo"></i> Limpiar</button>
        </div>
    </div>
</form>