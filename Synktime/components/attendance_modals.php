<!-- Modal para Llegadas Tempranas -->
<div class="modal" id="temprano-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-clock text-success"></i> Llegadas Tempranas</h3>
            <span class="modal-close" onclick="cerrarModal('temprano-modal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-filters">
                <div class="filter-info">
                    <i class="fas fa-calendar"></i> Fecha: <span id="temprano-modal-fecha"></span>
                </div>
                <div class="filter-info">
                    <i class="fas fa-building"></i> Ubicación: <span id="temprano-modal-ubicacion"></span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empleado</th>
                            <th>Establecimiento</th>
                            <th>Hora Entrada</th>
                            <th>Anticipación</th>
                        </tr>
                    </thead>
                    <tbody id="temprano-table-body">
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="cerrarModal('temprano-modal')">Cerrar</button>
            <button class="btn-primary" onclick="exportarExcel('temprano')">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Llegadas a Tiempo -->
<div class="modal" id="aTiempo-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-check text-primary"></i> Llegadas a Tiempo</h3>
            <span class="modal-close" onclick="cerrarModal('aTiempo-modal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-filters">
                <div class="filter-info">
                    <i class="fas fa-calendar"></i> Fecha: <span id="aTiempo-modal-fecha"></span>
                </div>
                <div class="filter-info">
                    <i class="fas fa-building"></i> Ubicación: <span id="aTiempo-modal-ubicacion"></span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empleado</th>
                            <th>Establecimiento</th>
                            <th>Hora Entrada</th>
                            <th>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody id="aTiempo-table-body">
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="cerrarModal('aTiempo-modal')">Cerrar</button>
            <button class="btn-primary" onclick="exportarExcel('aTiempo')">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Llegadas Tarde -->
<div class="modal" id="tarde-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-clock text-warning"></i> Llegadas Tarde</h3>
            <span class="modal-close" onclick="cerrarModal('tarde-modal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-filters">
                <div class="filter-info">
                    <i class="fas fa-calendar"></i> Fecha: <span id="tarde-modal-fecha"></span>
                </div>
                <div class="filter-info">
                    <i class="fas fa-building"></i> Ubicación: <span id="tarde-modal-ubicacion"></span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empleado</th>
                            <th>Establecimiento</th>
                            <th>Hora Entrada</th>
                            <th>Tardanza</th>
                        </tr>
                    </thead>
                    <tbody id="tarde-table-body">
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="cerrarModal('tarde-modal')">Cerrar</button>
            <button class="btn-primary" onclick="exportarExcel('tarde')">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Faltas -->
<div class="modal" id="faltas-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-times text-danger"></i> Faltas</h3>
            <span class="modal-close" onclick="cerrarModal('faltas-modal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-filters">
                <div class="filter-info">
                    <i class="fas fa-calendar"></i> Fecha: <span id="faltas-modal-fecha"></span>
                </div>
                <div class="filter-info">
                    <i class="fas fa-building"></i> Ubicación: <span id="faltas-modal-ubicacion"></span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empleado</th>
                            <th>Establecimiento</th>
                            <th>Sede</th>
                            <th>Nombre Horario</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                        </tr>
                    </thead>
                    <tbody id="faltas-table-body">
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="cerrarModal('faltas-modal')">Cerrar</button>
            <button class="btn-primary" onclick="exportarExcel('faltas')">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos para los modales */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fff;
    margin: auto;
    width: 90%;
    max-width: 900px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}

.modal-header h3 i {
    color: white;
}

.modal-close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    opacity: 0.8;
}

.modal-close:hover {
    color: white;
    opacity: 1;
}

.modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-filters {
    margin-bottom: 20px;
    background-color: #f9f9f9;
    padding: 10px;
    border-radius: 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.filter-info {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
}

.filter-info i {
    color: #666;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, 
.data-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.data-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}

.data-table tr:hover {
    background-color: #f9f9f9;
}

.text-center {
    text-align: center;
}

.loading-spinner {
    padding: 20px;
    color: #666;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.btn-secondary, 
.btn-primary {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-primary {
    background-color: #4B96FA;
    color: white;
}

.btn-primary:hover {
    background-color: #3A85E9;
}

.btn-secondary {
    background-color: #e0e0e0;
    color: #333;
}

.btn-secondary:hover {
    background-color: #d0d0d0;
}

.text-success { color: #28a745; }
.text-primary { color: #4B96FA; }
.text-warning { color: #ffc107; }
.text-danger { color: #dc3545; }

.table-responsive {
    overflow-x: auto;
}

/* Modificar las tarjetas del dashboard para hacerlas clicables */
.stat-card.clickable {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card.clickable:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 0.8125rem;
    font-weight: 500;
}

.status-badge.temprano {
    background-color: rgba(40, 167, 69, 0.15);
    color: #155724;
}

.status-badge.a-tiempo {
    background-color: rgba(75, 150, 250, 0.15);
    color: #004085;
}

.status-badge.tarde {
    background-color: rgba(255, 193, 7, 0.15);
    color: #856404;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 10px auto;
    }
    
    .modal-filters {
        flex-direction: column;
        gap: 10px;
    }
}
</style>