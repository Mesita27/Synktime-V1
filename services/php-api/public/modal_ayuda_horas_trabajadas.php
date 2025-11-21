<?php
// modal_ayuda_horas_trabajadas.php - Modal de ayuda para el módulo de horas trabajadas
?>
<!-- Modal de Ayuda para Horas Trabajadas -->
<div id="horasTrabajadasHelpModal" class="horas-modal-overlay" style="display: none;">
    <div class="horas-modal-container">
        <div class="horas-modal-content">
            <!-- Header del Modal -->
            <div class="horas-modal-header">
                <div class="horas-modal-title">
                    <div class="horas-modal-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="horas-modal-title-text">
                        <h2>Guía de Horas Trabajadas</h2>
                        <p>Sistema de Control y Gestión de Tiempo</p>
                    </div>
                </div>
                <button class="horas-modal-close" onclick="closeHorasTrabajadasHelpModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="horas-modal-tabs">
                <button class="horas-tab-btn active" onclick="showHorasTab('general')">
                    <i class="fas fa-info-circle"></i> General
                </button>
                <button class="horas-tab-btn" onclick="showHorasTab('calculos')">
                    <i class="fas fa-calculator"></i> Cálculos
                </button>
                <button class="horas-tab-btn" onclick="showHorasTab('filtros')">
                    <i class="fas fa-filter"></i> Filtros
                </button>
                <button class="horas-tab-btn" onclick="showHorasTab('extras')">
                    <i class="fas fa-clock"></i> Horas Extras
                </button>
            </div>

            <!-- Body del Modal -->
            <div class="horas-modal-body">
                <div class="horas-help-content">

                    <!-- Tab: General -->
                    <div id="horas-tab-general" class="horas-tab-content active">

                        <!-- Introducción -->
                        <div class="horas-help-section">
                            <div class="horas-intro">
                                <div class="horas-intro-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="horas-intro-content">
                                    <h3>¿Qué es el Módulo de Horas Trabajadas?</h3>
                                    <p>El módulo de horas trabajadas es el sistema central para el control y gestión del tiempo laboral de los empleados. Permite visualizar, calcular y gestionar todas las horas trabajadas, incluyendo horas regulares, extras, nocturnas y dominicales.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Funcionalidades Principales -->
                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-cogs"></i>
                                Funcionalidades Principales
                            </h3>
                            <div class="horas-features-grid">
                                <div class="horas-feature-card">
                                    <div class="horas-feature-icon">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <div class="horas-feature-content">
                                        <h4>Visualización de Horas</h4>
                                        <p>Consulta detallada de todas las horas trabajadas por empleado, con breakdown por día y tipo de horario.</p>
                                    </div>
                                </div>

                                <div class="horas-feature-card">
                                    <div class="horas-feature-icon">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <div class="horas-feature-content">
                                        <h4>Cálculos Automáticos</h4>
                                        <p>Sistema inteligente que calcula automáticamente horas trabajadas, extras y recargos basándose en horarios asignados.</p>
                                    </div>
                                </div>

                                <div class="horas-feature-card">
                                    <div class="horas-feature-icon">
                                        <i class="fas fa-filter"></i>
                                    </div>
                                    <div class="horas-feature-content">
                                        <h4>Filtros Avanzados</h4>
                                        <p>Herramientas de filtrado por fecha, empleado, sede, establecimiento y estado de asistencia.</p>
                                    </div>
                                </div>

                                <div class="horas-feature-card">
                                    <div class="horas-feature-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div class="horas-feature-content">
                                        <h4>Reportes y Análisis</h4>
                                        <p>Generación de reportes detallados para análisis y documentación de horas trabajadas.</p>
                                    </div>
                                </div>

                                <div class="horas-feature-card">
                                    <div class="horas-feature-icon">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div class="horas-feature-content">
                                        <h4>Días Cívicos</h4>
                                        <p>Registro y gestión de días cívicos y festivos que afectan los cálculos de horas trabajadas.</p>
                                    </div>
                                </div>

                                <div class="horas-feature-card">
                                    <div class="horas-feature-icon">
                                        <i class="fas fa-moon"></i>
                                    </div>
                                    <div class="horas-feature-content">
                                        <h4>Turnos Nocturnos</h4>
                                        <p>Manejo especial de turnos nocturnos con recargos y cálculos diferenciados.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tab: Cálculos -->
                    <div id="horas-tab-calculos" class="horas-tab-content">

                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-calculator"></i>
                                Cómo se Calculan las Horas Trabajadas
                            </h3>

                            <div class="horas-calculation-process">
                                <div class="horas-process-step">
                                    <div class="horas-step-number">1</div>
                                    <div class="horas-step-content">
                                        <h4>Registro de Asistencia</h4>
                                        <p>El sistema registra automáticamente las entradas y salidas de los empleados mediante el control biométrico o manual.</p>
                                    </div>
                                </div>

                                <div class="horas-process-step">
                                    <div class="horas-step-number">2</div>
                                    <div class="horas-step-content">
                                        <h4>Comparación con Horario</h4>
                                        <p>Se compara el tiempo registrado con el horario asignado al empleado para determinar horas regulares vs extras.</p>
                                    </div>
                                </div>

                                <div class="horas-process-step">
                                    <div class="horas-step-number">3</div>
                                    <div class="horas-step-content">
                                        <h4>Aplicación de Reglas</h4>
                                        <p>Se aplican reglas específicas según el tipo de horario (diurno, nocturno, dominical) y condiciones especiales.</p>
                                    </div>
                                </div>

                                <div class="horas-process-step">
                                    <div class="horas-step-number">4</div>
                                    <div class="horas-step-content">
                                        <h4>Cálculo de Recargos</h4>
                                        <p>Se calculan automáticamente los recargos por horas extras, nocturnas y dominicales según la legislación laboral.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tipos de Horarios -->
                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-calendar-alt"></i>
                                Tipos de Horarios y Cálculos
                            </h3>

                            <div class="horas-schedule-types">
                                <div class="horas-schedule-type">
                                    <div class="horas-schedule-header">
                                        <i class="fas fa-sun"></i>
                                        <h4>Horario Diurno Personalizado</h4>
                                    </div>
                                    <div class="horas-schedule-content">
                                        <p><strong>Horario variable:</strong> Los horarios diurnos son personalizados y pueden variar según las necesidades (6:00 AM - 9:00 PM)</p>
                                        <p><strong>Horas extras:</strong> Cualquier tiempo trabajado fuera del horario pactado o configurado para el empleado</p>
                                    </div>
                                </div>

                                <div class="horas-schedule-type">
                                    <div class="horas-schedule-header">
                                        <i class="fas fa-moon"></i>
                                        <h4>Horario Nocturno Personalizado</h4>
                                    </div>
                                    <div class="horas-schedule-content">
                                        <p><strong>Horario variable:</strong> Los horarios nocturnos son personalizados y pueden variar según las necesidades (9:00 PM - 6:00 AM)</p>
                                        <p><strong>Horas extras nocturnas:</strong> Cualquier tiempo trabajado fuera del horario pactado o configurado para el empleado</p>
                                    </div>
                                </div>

                                <div class="horas-schedule-type">
                                    <div class="horas-schedule-header">
                                        <i class="fas fa-calendar-week"></i>
                                        <h4>Horario Dominical/Festivo</h4>
                                    </div>
                                    <div class="horas-schedule-content">
                                        <p><strong>Aplicación:</strong> Se aplica a domingos y días festivos registrados en el sistema</p>
                                        <p><strong>Horas extras dominicales:</strong> Todo el tiempo trabajado en estos días se considera extra</p>
                                    </div>
                                </div>

                                <div class="horas-schedule-type">
                                    <div class="horas-schedule-header">
                                        <i class="fas fa-clock"></i>
                                        <h4>Horario Temporal</h4>
                                    </div>
                                    <div class="horas-schedule-content">
                                        <p><strong>Característica especial:</strong> Siempre que el horario sea temporal, todas las horas trabajadas se contarán como extras</p>
                                        <p><strong>Aplicación:</strong> Independientemente del horario asignado, se marca como temporal</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Consideraciones Especiales -->
                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-exclamation-triangle"></i>
                                Consideraciones Especiales en Cálculos
                            </h3>

                            <div class="horas-considerations">
                                <div class="horas-consideration-item">
                                    <div class="horas-consideration-icon">
                                        <i class="fas fa-calendar-times"></i>
                                    </div>
                                    <div class="horas-consideration-content">
                                        <h4>Días Festivos</h4>
                                        <p>Los días festivos y cívicos registrados afectan los cálculos, aplicando automáticamente recargos dominicales.</p>
                                    </div>
                                </div>

                                <div class="horas-consideration-item">
                                    <div class="horas-consideration-icon">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                    <div class="horas-consideration-content">
                                        <h4>Justificaciones</h4>
                                        <p>Las ausencias justificadas no afectan los cálculos de horas trabajadas, manteniendo la integridad del registro.</p>
                                    </div>
                                </div>

                                <div class="horas-consideration-item">
                                    <div class="horas-consideration-icon">
                                        <i class="fas fa-stopwatch"></i>
                                    </div>
                                    <div class="horas-consideration-content">
                                        <h4>Tolerancia de Puntualidad</h4>
                                        <p>Se aplica una tolerancia de 5-10 minutos para entradas tardías, evitando penalizaciones innecesarias.</p>
                                    </div>
                                </div>

                                <div class="horas-consideration-item">
                                    <div class="horas-consideration-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="horas-consideration-content">
                                        <h4>Horarios Personalizados</h4>
                                        <p>Los horarios de trabajo son personalizados y pueden variar según las necesidades del empleado. Una persona puede trabajar más o menos de 8 horas según su horario asignado.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tab: Filtros -->
                    <div id="horas-tab-filtros" class="horas-tab-content">

                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-filter"></i>
                                Sistema de Filtros y Búsqueda
                            </h3>

                            <div class="horas-filters-explanation">
                                <div class="horas-filter-category">
                                    <h4><i class="fas fa-calendar-alt"></i> Filtros de Fecha</h4>
                                    <div class="horas-filter-options">
                                        <div class="horas-filter-option">
                                            <strong>Filtros Rápidos:</strong>
                                            <ul>
                                                <li><strong>Hoy:</strong> Muestra solo registros del día actual</li>
                                                <li><strong>Ayer:</strong> Registros del día anterior</li>
                                                <li><strong>Semana actual/pasada:</strong> Agrupa por semanas completas</li>
                                                <li><strong>Mes actual/pasado:</strong> Vista mensual completa</li>
                                            </ul>
                                        </div>
                                        <div class="horas-filter-option">
                                            <strong>Filtros Personalizados:</strong>
                                            <ul>
                                                <li><strong>Fecha desde/hasta:</strong> Rango de fechas específico</li>
                                                <li><strong>Combinación:</strong> Puedes combinar filtros de fecha con otros criterios</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-filter-category">
                                    <h4><i class="fas fa-users"></i> Filtros de Empleados</h4>
                                    <div class="horas-filter-options">
                                        <div class="horas-filter-option">
                                            <strong>Por Código:</strong> Búsqueda exacta por código de empleado
                                        </div>
                                        <div class="horas-filter-option">
                                            <strong>Por Nombre:</strong> Búsqueda parcial por nombre o apellido
                                        </div>
                                        <div class="horas-filter-option">
                                            <strong>Combinado:</strong> Código + Nombre para búsquedas más precisas
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-filter-category">
                                    <h4><i class="fas fa-building"></i> Filtros de Ubicación</h4>
                                    <div class="horas-filter-options">
                                        <div class="horas-filter-option">
                                            <strong>Sede:</strong> Filtra por ubicación geográfica de la empresa
                                        </div>
                                        <div class="horas-filter-option">
                                            <strong>Establecimiento:</strong> Filtra por sucursal o departamento específico
                                        </div>
                                        <div class="horas-filter-option">
                                            <strong>Jerarquía:</strong> Sedes contienen establecimientos, permitiendo vistas jerárquicas
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-filter-category">
                                    <h4><i class="fas fa-clock"></i> Filtros de Estado</h4>
                                    <div class="horas-filter-options">
                                        <div class="horas-filter-option">
                                            <strong>Estado de Entrada:</strong>
                                            <ul>
                                                <li><strong>A Tiempo:</strong> Entradas dentro de la tolerancia</li>
                                                <li><strong>Temprano:</strong> Entradas antes del horario</li>
                                                <li><strong>Tardanza:</strong> Entradas después de la tolerancia</li>
                                                <li><strong>Justificado:</strong> Ausencias con justificación aprobada</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Consejos de Filtrado -->
                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-lightbulb"></i>
                                Consejos para un Filtrado Efectivo
                            </h3>

                            <div class="horas-filtering-tips">
                                <div class="horas-tip-card">
                                    <div class="horas-tip-icon">
                                        <i class="fas fa-search-plus"></i>
                                    </div>
                                    <div class="horas-tip-content">
                                        <h4>Búsquedas Específicas</h4>
                                        <p>Combina múltiples filtros para obtener resultados más precisos. Por ejemplo: empleado específico + rango de fechas + sede particular.</p>
                                    </div>
                                </div>

                                <div class="horas-tip-card">
                                    <div class="horas-tip-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="horas-tip-content">
                                        <h4>Análisis de Tendencias</h4>
                                        <p>Usa rangos de fechas amplios para identificar patrones de asistencia, horas extras recurrentes o problemas de puntualidad.</p>
                                    </div>
                                </div>

                                <div class="horas-tip-card">
                                    <div class="horas-tip-icon">
                                        <i class="fas fa-filter"></i>
                                    </div>
                                    <div class="horas-tip-content">
                                        <h4>Optimización de Filtros</h4>
                                        <p>Aplica los filtros necesarios para obtener los datos exactos que necesitas analizar. Los filtros aplicados se mantienen durante la sesión.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tab: Horas Extras -->
                    <div id="horas-tab-extras" class="horas-tab-content">

                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-clock"></i>
                                Sistema de Horas Extras
                            </h3>

                            <div class="horas-extras-intro">
                                <div class="horas-extras-intro-content">
                                    <p>El sistema de horas extras permite gestionar automáticamente el cálculo, registro y aprobación de tiempo extra trabajado por los empleados. Incluye diferentes tipos de recargos según el horario y condiciones laborales.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Estados de Aprobación -->
                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-tasks"></i>
                                Estados de Aprobación de Horas Extras
                            </h3>

                            <div class="horas-approval-statuses">
                                <div class="horas-status-card status-pending">
                                    <div class="horas-status-header">
                                        <i class="fas fa-clock"></i>
                                        <h4>Pendiente</h4>
                                    </div>
                                    <div class="horas-status-content">
                                        <p>Horas extras generadas automáticamente pero que requieren aprobación del supervisor antes de ser consideradas válidas para nómina.</p>
                                        <div class="horas-status-details">
                                            <strong>Acciones disponibles:</strong>
                                            <ul>
                                                <li>Aprobar horas extras</li>
                                                <li>Rechazar con justificación</li>
                                                <li>Modificar cantidad de horas</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-status-card status-approved">
                                    <div class="horas-status-header">
                                        <i class="fas fa-check-circle"></i>
                                        <h4>Aprobado</h4>
                                    </div>
                                    <div class="horas-status-content">
                                        <p>Horas extras revisadas y aprobadas por el supervisor. Estas horas se incluyen automáticamente en los cálculos de nómina y reportes.</p>
                                        <div class="horas-status-details">
                                            <strong>Características:</strong>
                                            <ul>
                                                <li>Incluidas en totales de horas trabajadas</li>
                                                <li>Recargos aplicados según tipo</li>
                                                <li>No modificables sin nueva aprobación</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-status-card status-rejected">
                                    <div class="horas-status-header">
                                        <i class="fas fa-times-circle"></i>
                                        <h4>Rechazado</h4>
                                    </div>
                                    <div class="horas-status-content">
                                        <p>Horas extras que fueron rechazadas por el supervisor. No se incluyen en cálculos de nómina ni reportes de horas trabajadas.</p>
                                        <div class="horas-status-details">
                                            <strong>Motivos comunes de rechazo:</strong>
                                            <ul>
                                                <li>Horas no autorizadas previamente</li>
                                                <li>Errores en el registro de tiempo</li>
                                                <li>Duplicación de registros</li>
                                                <li>Violación de políticas de horas extras</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tipos de Horas Extras -->
                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-layer-group"></i>
                                Tipos de Horas Extras
                            </h3>

                                <div class="horas-extra-type">
                                    <div class="horas-extra-header">
                                        <div class="horas-extra-icon">
                                            <i class="fas fa-sun"></i>
                                        </div>
                                        <div class="horas-extra-title">
                                            <h4>Horas Extras Diurnas</h4>
                                        </div>
                                    </div>
                                    <div class="horas-extra-content">
                                        <p>Trabajo adicional realizado fuera del horario diurno pactado o configurado para el empleado.</p>
                                        <div class="horas-extra-details">
                                            <strong>Condiciones:</strong>
                                            <ul>
                                                <li>Cualquier tiempo trabajado fuera del horario establecido</li>
                                                <li>Requiere autorización previa del administrador</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-extra-type">
                                    <div class="horas-extra-header">
                                        <div class="horas-extra-icon">
                                            <i class="fas fa-moon"></i>
                                        </div>
                                        <div class="horas-extra-title">
                                            <h4>Horas Extras Nocturnas</h4>
                                        </div>
                                    </div>
                                    <div class="horas-extra-content">
                                        <p>Trabajo adicional en horario nocturno fuera del horario pactado o configurado para el empleado.</p>
                                        <div class="horas-extra-details">
                                            <strong>Características:</strong>
                                            <ul>
                                                <li>Cualquier tiempo trabajado fuera del horario establecido</li>
                                                <li>Requiere autorización previa del administrador</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-extra-type">
                                    <div class="horas-extra-header">
                                        <div class="horas-extra-icon">
                                            <i class="fas fa-calendar-week"></i>
                                        </div>
                                        <div class="horas-extra-title">
                                            <h4>Horas Extras Dominicales</h4>
                                        </div>
                                    </div>
                                    <div class="horas-extra-content">
                                        <p>Horas trabajadas fuera del horario pactado durante días domingos o festivos registrados en el sistema.</p>
                                        <div class="horas-extra-details">
                                            <strong>Características:</strong>
                                            <ul>
                                                <li>Aplicable cuando se trabaja fuera del horario en domingos y festivos</li>
                                                <li>Solo las horas extras (fuera del horario) cuentan como dominicales</li>
                                                <li>Requiere autorización previa del administrador</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-extra-type">
                                    <div class="horas-extra-header">
                                        <div class="horas-extra-icon">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="horas-extra-title">
                                            <h4>Horas Extras Nocturnas-Dominicales</h4>
                                        </div>
                                    </div>
                                    <div class="horas-extra-content">
                                        <p>Horas trabajadas fuera del horario pactado durante horario nocturno en domingos o festivos.</p>
                                        <div class="horas-extra-details">
                                            <strong>Características:</strong>
                                            <ul>
                                                <li>Combinación de nocturno y dominical</li>
                                                <li>Aplicable a horas extras en horario nocturno durante domingos y festivos</li>
                                                <li>Requiere autorización previa especial del administrador</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="horas-extra-type">
                                    <div class="horas-extra-header">
                                        <div class="horas-extra-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="horas-extra-title">
                                            <h4>Horas Extras Temporales</h4>
                                        </div>
                                    </div>
                                    <div class="horas-extra-content">
                                        <p>Horas trabajadas cuando el horario asignado al empleado está marcado como temporal.</p>
                                        <div class="horas-extra-details">
                                            <strong>Características:</strong>
                                            <ul>
                                                <li>Todas las horas en horarios temporales cuentan como extras</li>
                                                <li>Independientemente del horario pactado, se considera temporal</li>
                                                <li>Requiere autorización previa del administrador</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                        </div>

                        <!-- Proceso de Aprobación -->
                        <div class="horas-help-section">
                            <h3 class="horas-section-title">
                                <i class="fas fa-check-double"></i>
                                Proceso de Aprobación de Horas Extras
                            </h3>

                            <div class="horas-approval-process">
                                <div class="horas-approval-step">
                                    <div class="horas-approval-step-number">1</div>
                                    <div class="horas-approval-step-content">
                                        <h4>Generación Automática</h4>
                                        <p>El sistema detecta automáticamente horas extras basándose en los horarios asignados y registros de asistencia.</p>
                                    </div>
                                </div>

                                <div class="horas-approval-step">
                                    <div class="horas-approval-step-number">2</div>
                                    <div class="horas-approval-step-content">
                                        <h4>Registro en Estado Pendiente</h4>
                                        <p>Las horas extras se registran automáticamente con estado "pendiente" y quedan disponibles para revisión.</p>
                                    </div>
                                </div>

                                <div class="horas-approval-step">
                                    <div class="horas-approval-step-number">3</div>
                                    <div class="horas-approval-step-content">
                                        <h4>Revisión por Administrador</h4>
                                        <p>Los administradores pueden revisar las horas extras pendientes, verificar su validez y tomar decisiones.</p>
                                    </div>
                                </div>

                                <div class="horas-approval-step">
                                    <div class="horas-approval-step-number">4</div>
                                    <div class="horas-approval-step-content">
                                        <h4>Aprobación o Rechazo</h4>
                                        <p>El administrador puede aprobar las horas extras (las cuales pasan a incluirse en cálculos) o rechazarlas con justificación.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Políticas de Horas Extras -->
                        <div class="horas-help-section">
                            <div class="horas-policies-alert">
                                <div class="horas-policies-header">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <h4>Políticas Importantes de Horas Extras</h4>
                                </div>
                                <div class="horas-policies-content">
                                    <div class="horas-policy-item">
                                        <strong>Autorización Previa:</strong> Todas las horas extras deben ser autorizadas previamente por el administrador.
                                    </div>
                                    <div class="horas-policy-item">
                                        <strong>Registro Obligatorio:</strong> Todo trabajo extra debe ser registrado en el sistema para su validación.
                                    </div>
                                    <div class="horas-policy-item">
                                        <strong>Verificación:</strong> Los administradores deben verificar la necesidad real de horas extras antes de aprobarlas.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="horas-modal-footer">
                <button class="horas-btn horas-btn-primary" onclick="closeHorasTrabajadasHelpModal()">
                    <i class="fas fa-times"></i>
                    Cerrar Ayuda
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos CSS para el Modal de Horas Trabajadas -->
<style>
/* Modal de Horas Trabajadas - Optimizado para dispositivos */
.horas-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.horas-modal-container {
    width: 95%;
    max-width: 900px;
    max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.horas-modal-content {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

/* Header */
.horas-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #4f46e5;
    color: #fff;
}

.horas-modal-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.horas-modal-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.horas-modal-title-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.horas-modal-title-text p {
    margin: 4px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.horas-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: background 0.2s;
}

.horas-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Tabs */
.horas-modal-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.horas-tab-btn {
    flex: 1;
    padding: 12px 16px;
    border: none;
    background: transparent;
    color: #6c757d;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
}

.horas-tab-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.horas-tab-btn.active {
    color: #4f46e5;
    background: #fff;
    border-bottom: 2px solid #4f46e5;
}

/* Body */
.horas-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    max-height: calc(90vh - 120px);
}

/* Tab Content */
.horas-tab-content {
    display: none;
}

.horas-tab-content.active {
    display: block;
}

/* Help Sections */
.horas-help-section {
    margin-bottom: 24px;
}

.horas-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #dee2e6;
}

/* Intro */
.horas-intro {
    display: flex;
    gap: 16px;
    background: #f8f9ff;
    padding: 16px;
    border-radius: 6px;
    border: 1px solid #e3f2fd;
    margin-bottom: 20px;
}

.horas-intro-icon {
    width: 48px;
    height: 48px;
    background: #4f46e5;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}

.horas-intro-content h3 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
}

.horas-intro-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.5;
}

/* Features Grid */
.horas-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.horas-feature-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
    display: flex;
    gap: 12px;
    transition: box-shadow 0.2s;
}

.horas-feature-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.horas-feature-icon {
    width: 40px;
    height: 40px;
    background: #4f46e5;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    flex-shrink: 0;
}

.horas-feature-content h4 {
    margin: 0 0 6px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.horas-feature-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Process Steps */
.horas-process-step,
.horas-approval-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 12px;
}

.horas-step-number,
.horas-approval-step-number {
    width: 28px;
    height: 28px;
    background: #4f46e5;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    flex-shrink: 0;
}

.horas-step-content h4,
.horas-approval-step-content h4 {
    margin: 0 0 4px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.horas-step-content p,
.horas-approval-step-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Schedule Types */
.horas-schedule-types {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.horas-schedule-type {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.horas-schedule-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.horas-schedule-header i {
    color: #4f46e5;
    font-size: 18px;
}

.horas-schedule-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.horas-schedule-content p {
    margin: 4px 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

/* Considerations */
.horas-considerations {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.horas-consideration-item {
    display: flex;
    gap: 12px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.horas-consideration-icon {
    color: #4f46e5;
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}

.horas-consideration-content h4 {
    margin: 0 0 6px 0;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 600;
}

.horas-consideration-content p {
    margin: 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

/* Filters */
.horas-filters-explanation {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.horas-filter-category {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.horas-filter-category h4 {
    margin: 0 0 12px 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.horas-filter-category h4 i {
    color: #4f46e5;
}

.horas-filter-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.horas-filter-option {
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.horas-filter-option:last-child {
    border-bottom: none;
}

.horas-filter-option strong {
    color: #2c3e50;
    display: block;
    margin-bottom: 6px;
}

.horas-filter-option ul {
    margin: 0;
    padding-left: 16px;
}

.horas-filter-option li {
    margin: 4px 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

/* Tips */
.horas-filtering-tips,
.horas-extras-intro {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.horas-tip-card {
    display: flex;
    gap: 12px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.horas-tip-icon {
    color: #4f46e5;
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 2px;
}

.horas-tip-content h4 {
    margin: 0 0 6px 0;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 600;
}

.horas-tip-content p {
    margin: 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

.horas-extras-intro-content {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
    border-left: 3px solid #4f46e5;
}

.horas-extras-intro-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.5;
}

/* Approval Statuses */
.horas-approval-statuses {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.horas-status-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.horas-status-card.status-pending {
    border-left: 3px solid #f59e0b;
}

.horas-status-card.status-approved {
    border-left: 3px solid #10b981;
}

.horas-status-card.status-rejected {
    border-left: 3px solid #ef4444;
}

.horas-status-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.horas-status-header i {
    font-size: 18px;
}

.horas-status-card.status-pending .horas-status-header i {
    color: #f59e0b;
}

.horas-status-card.status-approved .horas-status-header i {
    color: #10b981;
}

.horas-status-card.status-rejected .horas-status-header i {
    color: #ef4444;
}

.horas-status-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.horas-status-content p {
    margin: 0 0 12px 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.horas-status-details strong {
    color: #2c3e50;
    display: block;
    margin-bottom: 6px;
}

.horas-status-details ul {
    margin: 0;
    padding-left: 16px;
}

.horas-status-details li {
    margin: 4px 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

/* Extra Types */
.horas-extra-types {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.horas-extra-type {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
}

.horas-extra-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.horas-extra-icon {
    color: #4f46e5;
    font-size: 18px;
    margin-right: 10px;
}

.horas-extra-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.horas-extra-title h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.horas-extra-rate {
    background: #4f46e5;
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.horas-extra-content p {
    margin: 0 0 12px 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.horas-extra-details strong {
    color: #2c3e50;
    display: block;
    margin-bottom: 6px;
}

.horas-extra-details ul {
    margin: 0;
    padding-left: 16px;
}

.horas-extra-details li {
    margin: 4px 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

/* Policies Alert */
.horas-policies-alert {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 16px;
    border-left: 3px solid #f59e0b;
}

.horas-policies-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.horas-policies-header i {
    color: #f59e0b;
    font-size: 16px;
}

.horas-policies-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 15px;
    font-weight: 600;
}

.horas-policies-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.horas-policy-item {
    padding: 6px 0;
    border-bottom: 1px solid #f1f5f9;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

.horas-policy-item:last-child {
    border-bottom: none;
}

.horas-policy-item strong {
    color: #2c3e50;
}

/* Footer */
.horas-modal-footer {
    background: #f8f9fa;
    padding: 16px 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
}

.horas-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.horas-btn-primary {
    background: #4f46e5;
    color: #fff;
}

.horas-btn-primary:hover {
    background: #4338ca;
}

/* Responsive Design */
@media (max-width: 768px) {
    .horas-modal-container {
        width: 98%;
        margin: 5px;
        max-height: 95vh;
    }

    .horas-modal-header {
        padding: 12px 16px;
    }

    .horas-modal-title {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }

    .horas-modal-tabs {
        flex-wrap: wrap;
    }

    .horas-tab-btn {
        padding: 10px 12px;
        font-size: 13px;
    }

    .horas-modal-body {
        padding: 16px;
    }

    .horas-features-grid,
    .horas-considerations,
    .horas-filtering-tips,
    .horas-approval-statuses {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .horas-intro {
        flex-direction: column;
        text-align: center;
    }

    .horas-process-step,
    .horas-approval-step {
        flex-direction: column;
        text-align: center;
    }

    .horas-consideration-item {
        flex-direction: column;
        text-align: center;
    }

    .horas-tip-card {
        flex-direction: column;
        text-align: center;
    }

    .horas-status-card {
        text-align: center;
    }

    .horas-extra-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .horas-modal-container {
        width: 100%;
        margin: 0;
        border-radius: 0;
        max-height: 100vh;
    }

    .horas-modal-header {
        padding: 10px 12px;
    }

    .horas-modal-body {
        padding: 12px;
    }

    .horas-feature-card,
    .horas-consideration-item,
    .horas-tip-card,
    .horas-status-card,
    .horas-extra-type {
        padding: 12px;
    }
}
</style>

<!-- JavaScript para el Modal de Horas Trabajadas -->
<script>
function showHorasTrabajadasHelpModal() {
    console.log('🚀 Mostrando modal de ayuda de horas trabajadas...');

    const modal = document.getElementById('horasTrabajadasHelpModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Agregar event listener para cerrar con Escape
        document.addEventListener('keydown', handleHorasModalEscape);

        // Agregar event listener para cerrar haciendo clic fuera
        modal.addEventListener('click', handleHorasModalOutsideClick);

        console.log('✅ Modal de ayuda de horas trabajadas mostrado correctamente');
    } else {
        console.error('❌ Modal de ayuda de horas trabajadas no encontrado');
    }
}

function closeHorasTrabajadasHelpModal() {
    console.log('🎯 Cerrando modal de ayuda de horas trabajadas...');

    const modal = document.getElementById('horasTrabajadasHelpModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';

        // Remover event listeners
        document.removeEventListener('keydown', handleHorasModalEscape);
        modal.removeEventListener('click', handleHorasModalOutsideClick);

        console.log('✅ Modal de ayuda de horas trabajadas cerrado correctamente');
    } else {
        console.error('❌ Modal de ayuda de horas trabajadas no encontrado para cerrar');
    }
}

function handleHorasModalEscape(event) {
    if (event.key === 'Escape') {
        closeHorasTrabajadasHelpModal();
    }
}

function handleHorasModalOutsideClick(event) {
    const modal = document.getElementById('horasTrabajadasHelpModal');
    if (event.target === modal) {
        closeHorasTrabajadasHelpModal();
    }
}

function showHorasTab(tabName) {
    console.log('🔄 Cambiando a tab:', tabName);

    // Ocultar todos los tabs
    const tabContents = document.querySelectorAll('.horas-tab-content');
    tabContents.forEach(content => content.classList.remove('active'));

    // Remover clase active de todos los botones
    const tabBtns = document.querySelectorAll('.horas-tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));

    // Mostrar tab seleccionado
    const selectedTab = document.getElementById('horas-tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Activar botón correspondiente
    const selectedBtn = document.querySelector(`[onclick="showHorasTab('${tabName}')"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }

    console.log('✅ Tab cambiado a:', tabName);
}

// Función de compatibilidad para el código existente
function hideHorasTrabajadasHelpModal() {
    closeHorasTrabajadasHelpModal();
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Modal de ayuda de horas trabajadas inicializado');

    // Verificar que el modal existe
    const modal = document.getElementById('horasTrabajadasHelpModal');
    if (modal) {
        console.log('✅ Modal de ayuda de horas trabajadas encontrado en el DOM');
    } else {
        console.warn('⚠️ Modal de ayuda de horas trabajadas no encontrado en el DOM');
    }
});
</script>