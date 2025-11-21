// Función para mostrar detalles de asistencia
function openAttendanceDetailsFromRow(id, tipo) {
    const tipoNormalizado = typeof tipo === 'string'
        ? tipo.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
        : '';

    if (tipoNormalizado.includes('justificacion')) {
        // Para justificaciones, mostrar detalles de la justificación específica
        openJustificationDetailsFromRow(id);
        return;
    }

    // Para asistencia, obtener datos detallados del API
    const asistenciaData = window.reportData?.asistencias?.find(asistencia => asistencia.id_registro == id);

    if (!asistenciaData) {
        console.error('No se encontró la data de asistencia para ID:', id);
        return;
    }

    // Mostrar loading en el modal
    const modal = document.getElementById('attendanceDetailsModal');
    if (!modal) return;

    modal.classList.add('show');
    document.body.classList.add('modal-open');

    const modalBody = document.getElementById('attendanceDetailsContent');
    if (modalBody) {
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #2B7DE9;"></i>
                <p style="margin-top: 15px; color: #718096;">Cargando detalles...</p>
            </div>
        `;
    }

    // Hacer llamada al API para obtener datos detallados
    // Pasar el ID del registro de asistencia para obtener el horario específico
    fetch(`api/attendance/details.php?codigo=${asistenciaData.codigo}&fecha=${asistenciaData.fecha.replace(/\//g, '-')}&id_asistencia=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAttendanceDetails(data.data);
            } else {
                throw new Error(data.message || 'Error al cargar detalles');
            }
        })
        .catch(error => {
            console.error('Error al cargar detalles de asistencia:', error);
            if (modalBody) {
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #e53e3e;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                        <p style="margin-top: 15px;">Error al cargar detalles: ${error.message}</p>
                    </div>
                `;
            }
        });
}

// Función para renderizar detalles de asistencia
function renderAttendanceDetails(data) {
    const modalBody = document.getElementById('attendanceDetailsContent');
    if (!modalBody) return;

    // Extraer datos de la estructura del API
    const empleado = data.empleado || {};
    const fecha = data.fecha;
    const horario = data.horario_programado;
    const asistencia = data.asistencia || {};
    const entrada = asistencia.entrada;
    const salida = asistencia.salida;
    const horasTrabajadas = data.horas_trabajadas_formateadas || '00:00';
    const esAsistenciaNocturna = data.es_turno_nocturno || false;
    const registroDiaSiguiente = data.registro_dia_siguiente;

    const sanitizeAttr = (value) => {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    };

    const buildPhotoCard = (title, fotoUrl, fechaRegistro, horaRegistro) => {
        if (!fotoUrl) return '';
        const fechaTexto = fechaRegistro ? `<span class="photo-meta"><i class="fas fa-calendar-day"></i> ${fechaRegistro}</span>` : '';
        const horaTexto = horaRegistro ? `<span class="photo-meta"><i class="fas fa-clock"></i> ${horaRegistro}</span>` : '';
        const safeUrl = sanitizeAttr(fotoUrl);
        const safeTitle = sanitizeAttr(title);
        const safeFecha = sanitizeAttr(fechaRegistro || '');
        const safeHora = sanitizeAttr(horaRegistro || '');

        return `
            <div class="attendance-photo-card">
                <div class="photo-header">
                    <h6>${title}</h6>
                    <div class="photo-meta-wrapper">
                        ${fechaTexto}
                        ${horaTexto}
                    </div>
                </div>
                <button type="button"
                    class="attendance-photo-trigger attendance-photo-link"
                    data-photo-url="${safeUrl}"
                    data-photo-title="${safeTitle}"
                    data-photo-date="${safeFecha}"
                    data-photo-time="${safeHora}">
                    <img src="${safeUrl}" alt="${safeTitle}" class="attendance-photo" loading="lazy"/>
                    <span class="photo-zoom-icon" aria-hidden="true">
                        <i class="fas fa-search-plus"></i>
                    </span>
                </button>
            </div>
        `;
    };

    // Información básica
    const infoBasica = `
        <div class="attendance-info-section">
            <h5><i class="fas fa-user"></i> Información del Empleado</h5>
            <div class="info-grid">
                <div class="info-item">
                    <label>Código:</label>
                    <span>${empleado.codigo || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Nombre:</label>
                    <span>${empleado.nombre || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Sede:</label>
                    <span>${empleado.sede || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Establecimiento:</label>
                    <span>${empleado.establecimiento || 'N/A'}</span>
                </div>
            </div>
        </div>
    `;

    const fotosSection = (buildPhotoCard('Entrada', entrada?.foto, entrada?.fecha, entrada?.hora) || buildPhotoCard('Salida', salida?.foto, salida?.fecha, salida?.hora))
        ? `
        <div class="attendance-info-section">
            <h5><i class="fas fa-camera"></i> Registro Fotográfico</h5>
            <div class="attendance-photos-wrapper">
                ${buildPhotoCard('Entrada', entrada?.foto, entrada?.fecha, entrada?.hora)}
                ${buildPhotoCard('Salida', salida?.foto, salida?.fecha, salida?.hora)}
            </div>
        </div>
    ` : '';

    // Información de asistencia
    const asistenciaInfo = `
        <div class="attendance-info-section">
            <h5><i class="fas fa-calendar-day"></i> Registro de Asistencia - ${fecha || 'N/A'}</h5>
            <div class="attendance-details-grid">
                <div class="detail-card ${entrada && entrada.estado === 'Temprano' ? 'success' : entrada && entrada.estado === 'Puntual' ? 'info' : entrada && entrada.estado === 'Tardanza' ? 'warning' : 'secondary'}">
                    <div class="detail-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="detail-content">
                        <h6>Entrada</h6>
                        <div class="detail-time">${entrada ? entrada.hora : 'Sin registro'}</div>
                        <div class="detail-status">${entrada ? entrada.estado : 'N/A'}</div>
                    </div>
                </div>

                <div class="detail-card ${salida && salida.estado === 'Temprano' ? 'warning' : salida && salida.estado === 'Puntual' ? 'info' : salida && salida.estado === 'Tardanza' ? 'success' : 'secondary'}">
                    <div class="detail-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="detail-content">
                        <h6>Salida</h6>
                        <div class="detail-time">${salida ? salida.hora : 'Sin registro'}</div>
                        <div class="detail-status">${salida ? salida.estado : 'N/A'}</div>
                    </div>
                </div>

                <div class="detail-card info">
                    <div class="detail-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="detail-content">
                        <h6>Horas Trabajadas</h6>
                        <div class="detail-time">${horasTrabajadas}</div>
                    </div>
                </div>

                <div class="detail-card ${esAsistenciaNocturna ? 'primary' : 'secondary'}">
                    <div class="detail-icon">
                        <i class="fas ${esAsistenciaNocturna ? 'fa-moon' : 'fa-sun'}"></i>
                    </div>
                    <div class="detail-content">
                        <h6>Tipo de Asistencia</h6>
                        <div class="detail-time">${esAsistenciaNocturna ? 'Nocturna' : 'Diurna'}</div>
                        <div class="detail-status">${esAsistenciaNocturna ? 'Trabaja de noche' : 'Horario diurno'}</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Información del horario
    const horarioInfo = horario ? `
        <div class="attendance-info-section">
            <h5><i class="fas fa-calendar-alt"></i> Horario Programado</h5>
            <div class="schedule-info">
                <div class="schedule-details">
                    <div class="schedule-item">
                        <label>Hora Entrada:</label>
                        <span>${horario.hora_entrada || 'No definido'}</span>
                    </div>
                    <div class="schedule-item">
                        <label>Hora Salida:</label>
                        <span>${horario.hora_salida || 'No definido'}</span>
                    </div>
                    <div class="schedule-item">
                        <label>Tolerancia:</label>
                        <span>${horario.tolerancia || 0} minutos</span>
                    </div>
                    <div class="schedule-item">
                        <label>Nombre:</label>
                        <span>${horario.nombre_horario || 'No especificado'}</span>
                    </div>
                </div>
            </div>
        </div>
    ` : '';

    // Historial de registros
    const historialRegistros = `
        <div class="attendance-info-section">
            <h5><i class="fas fa-history"></i> Historial de Registros</h5>
            <div class="records-history">
                ${generateRecordsHistory(data)}
            </div>
        </div>
    `;

    // Observaciones
    const observaciones = (entrada && entrada.observacion) || (salida && salida.observacion) ? `
        <div class="attendance-info-section">
            <h5><i class="fas fa-comment"></i> Observaciones</h5>
            <div class="observations-content">
                ${entrada && entrada.observacion ? `<p><strong>Entrada:</strong> ${entrada.observacion}</p>` : ''}
                ${salida && salida.observacion ? `<p><strong>Salida:</strong> ${salida.observacion}</p>` : ''}
            </div>
        </div>
    ` : '';

    // Combinar todo el contenido
    modalBody.innerHTML = `
        ${infoBasica}
        ${asistenciaInfo}
        ${fotosSection}
        ${horarioInfo}
        ${historialRegistros}
        ${observaciones}
    `;

    initializeAttendancePhotoPreview(modalBody);
}

// Función para generar historial de registros
function generateRecordsHistory(data) {
    let historyHtml = '';

    const entrada = data.asistencia?.entrada;
    const salida = data.asistencia?.salida;
    const registroDiaSiguiente = data.registro_dia_siguiente;
    const fecha = data.fecha;

    // Entrada del día principal
    if (entrada) {
        historyHtml += `
            <div class="record-item entry">
                <div class="record-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="record-details">
                    <div class="record-type">Entrada</div>
                    <div class="record-time">${entrada.hora}</div>
                    <div class="record-date">${entrada.fecha}</div>
                </div>
            </div>
        `;
    }

    // Salida del día principal
    if (salida && salida.fecha === entrada?.fecha) {
        historyHtml += `
            <div class="record-item exit">
                <div class="record-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="record-details">
                    <div class="record-type">Salida</div>
                    <div class="record-time">${salida.hora}</div>
                    <div class="record-date">${salida.fecha}</div>
                </div>
            </div>
        `;
    }

    // Salida del día siguiente (turnos nocturnos)
    if (registroDiaSiguiente && registroDiaSiguiente.SALIDA_HORA) {
        const fechaSiguiente = registroDiaSiguiente.FECHA;
        historyHtml += `
            <div class="record-item exit-night">
                <div class="record-icon">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="record-details">
                    <div class="record-type">Salida (Turno Nocturno)</div>
                    <div class="record-time">${registroDiaSiguiente.SALIDA_HORA}</div>
                    <div class="record-date">${fechaSiguiente}</div>
                </div>
            </div>
        `;
    }

    if (!historyHtml) {
        historyHtml = '<p class="no-records">No hay registros disponibles</p>';
    }

    return historyHtml;
}

function initializeAttendancePhotoPreview(container) {
    if (!container) return;

    ensurePhotoModalHelpers();

    const triggers = container.querySelectorAll('.attendance-photo-trigger');
    triggers.forEach(trigger => {
        if (trigger.dataset.photoListenerAttached === 'true') {
            return;
        }

        const openPreview = () => {
            const photoUrl = trigger.dataset.photoUrl;
            const photoTitle = trigger.dataset.photoTitle || 'Registro fotográfico';
            const photoDate = trigger.dataset.photoDate;
            const photoTime = trigger.dataset.photoTime;

            const metaParts = [];
            if (photoDate) metaParts.push(photoDate);
            if (photoTime) metaParts.push(photoTime);
            const modalTitle = metaParts.length ? `${photoTitle} • ${metaParts.join(' · ')}` : photoTitle;

            if (typeof window.openPhotoModal === 'function') {
                window.openPhotoModal(photoUrl, modalTitle);
            } else {
                window.open(photoUrl, '_blank');
            }
        };

        trigger.addEventListener('click', openPreview);
        trigger.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPreview();
            }
        });

        trigger.dataset.photoListenerAttached = 'true';
    });
}

function ensurePhotoModalHelpers() {
    const hasPhotoModal = typeof window.openPhotoModal === 'function' && typeof window.closePhotoModal === 'function';
    if (hasPhotoModal) {
        return;
    }

    window.openPhotoModal = function(photoUrl, title = '') {
        let modal = document.getElementById('photoModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'photoModal';
            modal.className = 'photo-modal-overlay';
            modal.innerHTML = `
                <div class="photo-modal-content">
                    <h3 id="photoModalTitle"></h3>
                    <img id="photoModalImage" src="" alt="Foto de asistencia">
                    <button class="photo-modal-close" type="button" aria-label="Cerrar vista de foto">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(modal);

            const closeButton = modal.querySelector('.photo-modal-close');
            closeButton.addEventListener('click', () => window.closePhotoModal());
            modal.addEventListener('click', event => {
                if (event.target === modal) {
                    window.closePhotoModal();
                }
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && modal.classList.contains('show')) {
                    window.closePhotoModal();
                }
            });
        }

        const modalImg = document.getElementById('photoModalImage');
        const modalTitle = document.getElementById('photoModalTitle');

        modalImg.src = photoUrl;

        if (title) {
            modalTitle.textContent = title;
            modalTitle.style.display = 'block';
        } else {
            modalTitle.style.display = 'none';
        }

        modalImg.style.opacity = '0';
        modal.classList.add('show');

        const revealImage = () => {
            requestAnimationFrame(() => {
                modalImg.style.opacity = '1';
            });
        };

        if (modalImg.complete) {
            revealImage();
        } else {
            modalImg.onload = revealImage;
        }
    };

    window.closePhotoModal = function() {
        const modal = document.getElementById('photoModal');
        if (!modal) return;

        const modalImg = document.getElementById('photoModalImage');
        if (modalImg) {
            modalImg.style.opacity = '0';
        }

        setTimeout(() => {
            modal.classList.remove('show');
        }, 150);
    };
}

// Función para cerrar modal de asistencia
function closeAttendanceModal() {
    const modal = document.getElementById('attendanceDetailsModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// Función para mostrar detalles de empleado
function openEmployeeDetailsFromRow(codigo) {
    // Mostrar modal
    const modal = document.getElementById('employeeDetailsModal');
    if (!modal) return;

    modal.classList.add('show');
    document.body.classList.add('modal-open');

    // Renderizar contenido del modal
    renderEmployeeDetails(codigo);
}

// Función para renderizar detalles de empleado
function renderEmployeeDetails(codigo) {
    const modalBody = document.getElementById('employeeDetailsContent');
    if (!modalBody) return;

    // Mostrar loading
    modalBody.innerHTML = `
        <div class="loading-container">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Cargando información del empleado...</span>
        </div>
    `;

    // Cargar datos del empleado via AJAX
    fetch(`api/employees/details.php?codigo=${codigo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderEmployeeDetailsContent(data.empleado);
            } else {
                modalBody.innerHTML = `
                    <div class="error-container">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Error al cargar datos del empleado</span>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="error-container">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Error de conexión</span>
                </div>
            `;
        });
}

// Función para renderizar contenido de detalles de empleado
function renderEmployeeDetailsContent(empleado) {
    const modalBody = document.getElementById('employeeDetailsContent');
    if (!modalBody) return;

    // Información básica del empleado
    const infoBasica = `
        <div class="employee-info-section">
            <h5><i class="fas fa-user"></i> Información Personal</h5>
            <div class="info-grid">
                <div class="info-item">
                    <label>Código:</label>
                    <span>${empleado.ID_EMPLEADO || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>DNI:</label>
                    <span>${empleado.DNI || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Nombre:</label>
                    <span>${empleado.NOMBRE || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Apellido:</label>
                    <span>${empleado.APELLIDO || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Sede:</label>
                    <span>${empleado.SEDE || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Establecimiento:</label>
                    <span>${empleado.ESTABLECIMIENTO || 'N/A'}</span>
                </div>
            </div>
        </div>
    `;

    // Horarios del empleado
    const horariosInfo = empleado.horarios ? `
        <div class="employee-info-section">
            <h5><i class="fas fa-calendar-alt"></i> Horarios Asignados</h5>
            <div class="schedules-list">
                ${empleado.horarios.map(horario => `
                    <div class="schedule-card">
                        <div class="schedule-header">
                            <h6>Horario ID: ${horario.ID_EMPLEADO_HORARIO}</h6>
                            <span class="schedule-status ${horario.ACTIVO === 'S' ? 'active' : 'inactive'}">
                                ${horario.ACTIVO === 'S' ? 'Activo' : 'Inactivo'}
                            </span>
                        </div>
                        <div class="schedule-details">
                            <div class="schedule-item">
                                <label>Entrada:</label>
                                <span>${horario.HORA_ENTRADA || 'No definido'}</span>
                            </div>
                            <div class="schedule-item">
                                <label>Salida:</label>
                                <span>${horario.HORA_SALIDA || 'No definido'}</span>
                            </div>
                            <div class="schedule-item">
                                <label>Tolerancia:</label>
                                <span>${horario.TOLERANCIA || 0} minutos</span>
                            </div>
                            <div class="schedule-item">
                                <label>Días:</label>
                                <span>${horario.DIAS_SEMANA || 'No especificado'}</span>
                            </div>
                            <div class="schedule-item">
                                <label>Vigencia:</label>
                                <span>${horario.FECHA_INICIO_VIGENCIA || 'N/A'} - ${horario.FECHA_FIN_VIGENCIA || 'Indefinida'}</span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    ` : '<div class="employee-info-section"><p>No hay horarios asignados</p></div>';

    // Combinar contenido
    modalBody.innerHTML = `
        ${infoBasica}
        ${horariosInfo}
    `;
}

// Función para cerrar modal de empleado
function closeEmployeeModal() {
    const modal = document.getElementById('employeeDetailsModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// Función para mostrar ayuda
function showHelpModal() {
    const modal = document.getElementById('helpModal');
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// Función para cerrar modal de ayuda
function closeHelpModal() {
    const modal = document.getElementById('helpModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// Función para mostrar detalles de justificación
function openJustificationDetailsFromRow(id) {
    // Mostrar loading en el modal
    const modal = document.getElementById('justificationDetailsModal');
    if (!modal) return;

    modal.classList.add('show');
    document.body.classList.add('modal-open');

    const modalBody = document.getElementById('justificationDetailsContent');
    if (modalBody) {
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #2B7DE9;"></i>
                <p style="margin-top: 15px; color: #718096;">Cargando detalles...</p>
            </div>
        `;
    }

    // Hacer llamada al API para obtener datos detallados de la justificación
    fetch(`api/justificaciones.php?action=detalle&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderJustificationDetails(data.justificacion);
            } else {
                throw new Error(data.message || 'Error al cargar detalles');
            }
        })
        .catch(error => {
            console.error('Error al cargar detalles de justificación:', error);
            if (modalBody) {
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #e53e3e;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                        <p style="margin-top: 15px;">Error al cargar detalles: ${error.message}</p>
                    </div>
                `;
            }
        });
}

// Función para renderizar detalles de justificación
function renderJustificationDetails(data) {
    const modalBody = document.getElementById('justificationDetailsContent');
    if (!modalBody) return;

    // Extraer datos de la justificación
    const justificacion = data || {};

    // Información del empleado
    const infoEmpleado = `
        <div class="attendance-info-section">
            <h5><i class="fas fa-user"></i> Información del Empleado</h5>
            <div class="info-grid">
                <div class="info-item">
                    <label>Nombre:</label>
                    <span>${justificacion.empleado_nombre_completo || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Código:</label>
                    <span>${justificacion.empleado_codigo || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Sede:</label>
                    <span>${justificacion.sede_nombre || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Establecimiento:</label>
                    <span>${justificacion.establecimiento_nombre || 'N/A'}</span>
                </div>
            </div>
        </div>
    `;

    // Detalles de la justificación
    const detallesJustificacion = `
        <div class="attendance-info-section">
            <h5><i class="fas fa-clipboard-list"></i> Detalles de la Justificación</h5>
            <div class="info-grid">
                <div class="info-item">
                    <label>ID Justificación:</label>
                    <span>#${justificacion.id || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Fecha de Falta:</label>
                    <span>${justificacion.fecha_falta ? new Date(justificacion.fecha_falta).toLocaleDateString('es-ES') : 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Motivo:</label>
                    <span>${justificacion.motivo || 'N/A'}</span>
                </div>
                <div class="info-item full-width">
                    <label>Observaciones:</label>
                    <span>${justificacion.detalle_adicional || 'Sin observaciones'}</span>
                </div>
            </div>
        </div>
    `;

    // Información del horario
    const infoHorario = `
        <div class="attendance-info-section">
            <h5><i class="fas fa-clock"></i> Información del Horario</h5>
            <div class="info-grid">
                <div class="info-item">
                    <label>Tipo de Justificación:</label>
                    <span>${justificacion.es_jornada_completa === 1 ? 'Jornada Completa' : 'Turno Específico'}</span>
                </div>
                <div class="info-item">
                    <label>Turno:</label>
                    <span>${justificacion.turno_nombre || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Horario:</label>
                    <span>${justificacion.turno_horario || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <label>Tipo de Falta:</label>
                    <span class="badge ${justificacion.tipo_falta === 'completa' ? 'badge-danger' : justificacion.tipo_falta === 'parcial' ? 'badge-warning' : 'badge-info'}">${justificacion.tipo_falta === 'completa' ? 'Día Completo' : justificacion.tipo_falta === 'parcial' ? 'Parcial' : justificacion.tipo_falta || 'N/A'}</span>
                </div>
            </div>
        </div>
    `;

    // Renderizar todo el contenido
    modalBody.innerHTML = infoEmpleado + detallesJustificacion + infoHorario;
}

// Función para cerrar modal de justificación
function closeJustificationModal() {
    const modal = document.getElementById('justificationDetailsModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// Inicializar event listeners para cerrar modales al hacer click fuera
document.addEventListener('DOMContentLoaded', function() {
    // Modal de asistencia
    const attendanceModal = document.getElementById('attendanceDetailsModal');
    if (attendanceModal) {
        attendanceModal.addEventListener('click', function(e) {
            if (e.target === attendanceModal) {
                closeAttendanceModal();
            }
        });
    }

    // Modal de justificación
    const justificationModal = document.getElementById('justificationDetailsModal');
    if (justificationModal) {
        justificationModal.addEventListener('click', function(e) {
            if (e.target === justificationModal) {
                closeJustificationModal();
            }
        });
    }

    // Modal de empleado
    const employeeModal = document.getElementById('employeeDetailsModal');
    if (employeeModal) {
        employeeModal.addEventListener('click', function(e) {
            if (e.target === employeeModal) {
                closeEmployeeModal();
            }
        });
    }

    // Modal de ayuda
    const helpModal = document.getElementById('helpModal');
    if (helpModal) {
        helpModal.addEventListener('click', function(e) {
            if (e.target === helpModal) {
                closeHelpModal();
            }
        });
    }
});

// Exponer funciones globalmente
window.openJustificationDetailsFromRow = openJustificationDetailsFromRow;
window.closeJustificationModal = closeJustificationModal;