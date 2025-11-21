<!-- Modal para Llegadas Tempranas -->
<div class="attendance-modal" id="temprano-modal">
    <div class="attendance-modal-dialog">
        <div class="attendance-modal-content">
            <div class="attendance-modal-header">
                <h3 class="attendance-modal-title"><i class="fas fa-user-clock attendance-text-success"></i> Llegadas Tempranas</h3>
                <button type="button" class="attendance-modal-close" onclick="attendancePopup.closeModal('temprano-modal')">&times;</button>
            </div>
            <div class="attendance-modal-body">
                <div class="attendance-modal-filters">
                    <div class="attendance-filter-badge">
                        <i class="fas fa-calendar"></i> <span id="temprano-modal-fecha"></span>
                    </div>
                    <div class="attendance-filter-badge">
                        <i class="fas fa-building"></i> <span id="temprano-modal-ubicacion"></span>
                    </div>
                </div>
                
                <div class="attendance-table-responsive">
                    <table class="attendance-data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Establecimiento</th>
                                <th>Hora Entrada</th>
                                <th>Anticipación</th>
                            </tr>
                        </thead>
                        <tbody id="temprano-table-body">
                            <tr>
                                <td colspan="5" class="attendance-text-center">
                                    <div class="attendance-spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="attendance-modal-footer">
                <button class="attendance-btn-secondary" onclick="attendancePopup.closeModal('temprano-modal')">Cerrar</button>
                <button class="attendance-btn-primary" onclick="attendancePopup.exportToExcel('temprano')">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Llegadas a Tiempo -->
<div class="attendance-modal" id="aTiempo-modal">
    <div class="attendance-modal-dialog">
        <div class="attendance-modal-content">
            <div class="attendance-modal-header">
                <h3 class="attendance-modal-title"><i class="fas fa-user-check attendance-text-info"></i> Llegadas a Tiempo</h3>
                <button type="button" class="attendance-modal-close" onclick="attendancePopup.closeModal('aTiempo-modal')">&times;</button>
            </div>
            <div class="attendance-modal-body">
                <div class="attendance-modal-filters">
                    <div class="attendance-filter-badge">
                        <i class="fas fa-calendar"></i> <span id="aTiempo-modal-fecha"></span>
                    </div>
                    <div class="attendance-filter-badge">
                        <i class="fas fa-building"></i> <span id="aTiempo-modal-ubicacion"></span>
                    </div>
                </div>
                
                <div class="attendance-table-responsive">
                    <table class="attendance-data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Establecimiento</th>
                                <th>Hora Entrada</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                        <tbody id="aTiempo-table-body">
                            <tr>
                                <td colspan="5" class="attendance-text-center">
                                    <div class="attendance-spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="attendance-modal-footer">
                <button class="attendance-btn-secondary" onclick="attendancePopup.closeModal('aTiempo-modal')">Cerrar</button>
                <button class="attendance-btn-primary" onclick="attendancePopup.exportToExcel('aTiempo')">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Llegadas Tarde -->
<div class="attendance-modal" id="tarde-modal">
    <div class="attendance-modal-dialog">
        <div class="attendance-modal-content">
            <div class="attendance-modal-header">
                <h3 class="attendance-modal-title"><i class="fas fa-user-clock attendance-text-warning"></i> Llegadas Tarde</h3>
                <button type="button" class="attendance-modal-close" onclick="attendancePopup.closeModal('tarde-modal')">&times;</button>
            </div>
            <div class="attendance-modal-body">
                <div class="attendance-modal-filters">
                    <div class="attendance-filter-badge">
                        <i class="fas fa-calendar"></i> <span id="tarde-modal-fecha"></span>
                    </div>
                    <div class="attendance-filter-badge">
                        <i class="fas fa-building"></i> <span id="tarde-modal-ubicacion"></span>
                    </div>
                </div>
                
                <div class="attendance-table-responsive">
                    <table class="attendance-data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Establecimiento</th>
                                <th>Hora Entrada</th>
                                <th>Tardanza</th>
                            </tr>
                        </thead>
                        <tbody id="tarde-table-body">
                            <tr>
                                <td colspan="5" class="attendance-text-center">
                                    <div class="attendance-spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="attendance-modal-footer">
                <button class="attendance-btn-secondary" onclick="attendancePopup.closeModal('tarde-modal')">Cerrar</button>
                <button class="attendance-btn-primary" onclick="attendancePopup.exportToExcel('tarde')">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Faltas -->
<div class="attendance-modal" id="faltas-modal">
    <div class="attendance-modal-dialog">
        <div class="attendance-modal-content">
            <div class="attendance-modal-header">
                <h3 class="attendance-modal-title"><i class="fas fa-user-times attendance-text-danger"></i> Faltas</h3>
                <button type="button" class="attendance-modal-close" onclick="attendancePopup.closeModal('faltas-modal')">&times;</button>
            </div>
            <div class="attendance-modal-body">
                <div class="attendance-modal-filters">
                    <div class="attendance-filter-badge">
                        <i class="fas fa-calendar"></i> <span id="faltas-modal-fecha"></span>
                    </div>
                    <div class="attendance-filter-badge">
                        <i class="fas fa-building"></i> <span id="faltas-modal-ubicacion"></span>
                    </div>
                </div>
                
                <div class="attendance-table-responsive">
                    <table class="attendance-data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Establecimiento</th>
                                <th>Sede</th>
                                <th>Horario</th>
                            </tr>
                        </thead>
                        <tbody id="faltas-table-body">
                            <tr>
                                <td colspan="5" class="attendance-text-center">
                                    <div class="attendance-spinner">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="attendance-modal-footer">
                <button class="attendance-btn-secondary" onclick="attendancePopup.closeModal('faltas-modal')">Cerrar</button>
                <button class="attendance-btn-primary" onclick="attendancePopup.exportToExcel('faltas')">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>