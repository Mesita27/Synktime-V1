// SECTION: Selectores y estados
let employees = [];
let horariosDisponibles = [];

// Cargar sedes y establecimientos para filtros
document.addEventListener('DOMContentLoaded', async function() {
    await cargarSedesFiltroEmp();
    await cargarEstablecimientosFiltroEmp();
    await loadEmployeesSchedule();
    renderEmployeeScheduleTable(employees);

    // Actualiza establecimientos al cambiar sede en filtros
    document.getElementById('q_emp_sede').addEventListener('change', function() {
        cargarEstablecimientosFiltroEmp(this.value);
    });
});

// -- Helpers para filtros
async function cargarSedesFiltroEmp() {
    const sedeSel = document.getElementById('q_emp_sede');
    sedeSel.innerHTML = '<option value="">Seleccionar una Sede</option>';
    await fetch('api/get-sedes.php')
        .then(r=>r.json())
        .then(res=>{
            (res.sedes||[]).forEach(s=>{
                sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
            });
        });
}
async function cargarEstablecimientosFiltroEmp(idSede = '') {
    const estSel = document.getElementById('q_emp_establecimiento');
    estSel.innerHTML = '<option value="">Seleccionar un Establecimiento</option>';
    if (!idSede) {
        await fetch('api/get-establecimientos.php')
            .then(r=>r.json())
            .then(res=>{
                (res.establecimientos||[]).forEach(e=>{
                    estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
                });
            });
    } else {
        await fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(idSede))
            .then(r=>r.json())
            .then(res=>{
                (res.establecimientos||[]).forEach(e=>{
                    estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
                });
            });
    }
}

// -- Cargar empleados
async function loadEmployeesSchedule(filtros = {}) {
    let params = new URLSearchParams(filtros).toString();
    await fetch('api/employee/list.php' + (params ? '?' + params : ''))
        .then(r=>r.json())
        .then(res=>{
            employees = res.data || [];
        });
}

// -- Render tabla empleados
function renderEmployeeScheduleTable(data) {
    const tbody = document.getElementById('employeeScheduleTableBody');
    tbody.innerHTML = '';
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No se encontraron empleados</td></tr>';
        return;
    }
    data.forEach(emp => {
        tbody.innerHTML += `
            <tr>
                <td>${emp.nombre} ${emp.apellido}</td>
                <td>${emp.identificacion}</td>
                <td>${emp.sede || ''}</td>
                <td>${emp.establecimiento || ''}</td>
                <td>${emp.horarios_asignados || ''}</td>
                <td>
                    <button class="btn-icon btn-edit" title="Vincular horario" onclick="openLinkScheduleModal(${emp.id}, '${emp.nombre} ${emp.apellido}', ${emp.establecimiento_id})"><i class="fas fa-link"></i></button>
                    <button class="btn-icon btn-delete" title="Quitar todos los horarios" onclick="removeAllSchedulesEmployee(${emp.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

// -- Filtros (form)
document.getElementById('employeeScheduleQueryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const filtros = {
        codigo: document.getElementById('q_emp_codigo')?.value,
        identificacion: document.getElementById('q_emp_identificacion')?.value,
        nombre: document.getElementById('q_emp_nombre')?.value,
        sede: document.getElementById('q_emp_sede')?.value,
        establecimiento: document.getElementById('q_emp_establecimiento')?.value
    };
    await loadEmployeesSchedule(filtros);
    renderEmployeeScheduleTable(employees);
});
document.getElementById('btnClearEmployeeScheduleQuery').addEventListener('click', async function(e) {
    e.preventDefault();
    document.getElementById('employeeScheduleQueryForm').reset();
    await loadEmployeesSchedule();
    renderEmployeeScheduleTable(employees);
});


// Abrir el modal y cargar horarios disponibles para ese empleado
window.openLinkScheduleModal = function(idEmpleado, nombreEmpleado, establecimientoId) {
    document.getElementById('linkScheduleModal').classList.add('show');
    document.getElementById('link_emp_id').value = idEmpleado;
    document.getElementById('link_emp_name').value = nombreEmpleado;

    // Si no hay establecimiento, muestra mensaje
    if (!establecimientoId) {
        document.getElementById('link_schedule_select').innerHTML = '<option value="">Sin establecimiento asignado</option>';
        return;
    }

    // Cargar horarios disponibles del establecimiento del empleado
    fetch('api/horario/list.php?establecimiento=' + encodeURIComponent(establecimientoId))
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('link_schedule_select');
            sel.innerHTML = '<option value="">Seleccione horario</option>';
            (res.horarios || []).forEach(h => {
                sel.innerHTML += `<option value="${h.ID_HORARIO}">${h.NOMBRE} (${h.HORA_ENTRADA}-${h.HORA_SALIDA})</option>`;
            });
            if(!(res.horarios || []).length) {
                sel.innerHTML = '<option value="">Sin horarios en este establecimiento</option>';
            }
        });
};

window.closeLinkScheduleModal = function() {
    document.getElementById('linkScheduleModal').classList.remove('show');
};

// Vinculación al enviar el formulario
document.getElementById('linkScheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const empId = document.getElementById('link_emp_id').value;
    const horarioId = document.getElementById('link_schedule_select').value;

    if (!empId || !horarioId) {
        alert("Seleccione un horario válido.");
        return;
    }

    fetch('api/employee/assign_schedule.php', {
        method: 'POST',
        body: new URLSearchParams({
            id_horario: horarioId,
            ids_empleados: JSON.stringify([empId])
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeLinkScheduleModal();
            // Recarga la tabla de empleados actualizada si lo necesitas:
            loadEmployeesSchedule().then(() => renderEmployeeScheduleTable(employees));
        } else {
            alert(res.message || "Error al vincular el horario");
        }
    })
    .catch(() => {
        alert("Error de comunicación con el servidor");
    });
});

// -- Quitar todos los horarios de un empleado
window.removeAllSchedulesEmployee = function(idEmpleado) {
    if(!confirm('¿Quitar todos los horarios de este empleado?')) return;
    fetch('api/employee/remove_all_schedules.php', {
        method: 'POST',
        body: new URLSearchParams({id_empleado: idEmpleado})
    })
    .then(r=>r.json())
    .then(res=>{
        if(res.success){
            loadEmployeesSchedule().then(()=>renderEmployeeScheduleTable(employees));
        } else {
            alert("No se pudo quitar");
        }
    });
};