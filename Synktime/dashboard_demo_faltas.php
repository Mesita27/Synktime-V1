<?php
// Dashboard demo sin autenticación para mostrar el nuevo conteo de faltas
require_once 'config/database.php';
require_once 'dashboard-controller-simplified.php';

$fecha = '2025-09-15';
$empresaId = 1;

// Obtener estadísticas usando las funciones actualizadas
$estadisticas = getEstadisticasAsistenciaSimplified('empresa', $empresaId, $fecha);
$asistenciasPorHora = getAsistenciasPorHoraSimplified($empresaId, $fecha);
$distribucionAsistencias = getDistribucionAsistenciasSimplified($empresaId, $fecha);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SynkTime - Dashboard Demo (Nuevo Conteo de Faltas)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .alert {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.faltas {
            border-left: 5px solid #dc3545;
        }

        .stat-card.temprano {
            border-left: 5px solid #28a745;
        }

        .stat-card.tiempo {
            border-left: 5px solid #17a2b8;
        }

        .stat-card.tarde {
            border-left: 5px solid #ffc107;
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .stat-icon.faltas { color: #dc3545; }
        .stat-icon.temprano { color: #28a745; }
        .stat-icon.tiempo { color: #17a2b8; }
        .stat-icon.tarde { color: #ffc107; }

        .stat-value {
            font-size: 3em;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1;
        }

        .stat-value.faltas { color: #dc3545; }
        .stat-value.temprano { color: #28a745; }
        .stat-value.tiempo { color: #17a2b8; }
        .stat-value.tarde { color: #ffc107; }

        .stat-label {
            font-size: 1.1em;
            color: #6c757d;
            font-weight: 500;
        }

        .comparison-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .method-card {
            padding: 20px;
            border-radius: 8px;
            border: 2px solid;
        }

        .method-old {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .method-new {
            border-color: #28a745;
            background: #d4edda;
        }

        .info-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-clock"></i> SynkTime Dashboard Demo</h1>
        <p>Demostración del Nuevo Conteo de Turnos Faltantes</p>
        <p><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($fecha)); ?> (<?php echo date('l', strtotime($fecha)); ?>)</p>
    </div>

    <div class="container">
        <div class="alert">
            <h4><i class="fas fa-info-circle"></i> Nueva Funcionalidad Implementada</h4>
            <p>El conteo de faltas ahora muestra el número exacto de <strong>turnos programados</strong> para el día específico que no se cumplieron, en lugar del método anterior que contaba empleados totales menos empleados que asistieron.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card faltas">
                <div class="stat-icon faltas">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-value faltas"><?php echo $estadisticas['faltas'] ?? 0; ?></div>
                <div class="stat-label">Turnos Faltantes</div>
            </div>

            <div class="stat-card temprano">
                <div class="stat-icon temprano">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value temprano"><?php echo $estadisticas['llegadas_temprano'] ?? 0; ?></div>
                <div class="stat-label">Llegadas Tempranas</div>
            </div>

            <div class="stat-card tiempo">
                <div class="stat-icon tiempo">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value tiempo"><?php echo $estadisticas['llegadas_tiempo'] ?? 0; ?></div>
                <div class="stat-label">Llegadas a Tiempo</div>
            </div>

            <div class="stat-card tarde">
                <div class="stat-icon tarde">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-value tarde"><?php echo $estadisticas['llegadas_tarde'] ?? 0; ?></div>
                <div class="stat-label">Llegadas Tarde</div>
            </div>
        </div>

        <div class="comparison-section">
            <h3><i class="fas fa-balance-scale"></i> Comparación de Métodos</h3>
            
            <div class="comparison-grid">
                <div class="method-card method-old">
                    <h4><i class="fas fa-times-circle"></i> Método Anterior</h4>
                    <ul>
                        <li><strong>Fórmula:</strong> Total empleados - Empleados que asistieron</li>
                        <li><strong>Resultado:</strong> ~82 faltas</li>
                        <li><strong>Problema:</strong> Incluía empleados sin horario programado para el día</li>
                        <li><strong>Impreciso:</strong> No consideraba días específicos de la semana</li>
                    </ul>
                </div>

                <div class="method-card method-new">
                    <h4><i class="fas fa-check-circle"></i> Método Nuevo</h4>
                    <ul>
                        <li><strong>Cuenta:</strong> Solo turnos específicos programados para el día</li>
                        <li><strong>Resultado:</strong> <?php echo $estadisticas['faltas'] ?? 0; ?> turnos faltantes</li>
                        <li><strong>Preciso:</strong> Filtra por día de semana (lunes = ID_DIA 1)</li>
                        <li><strong>Vigencia:</strong> Solo horarios activos en la fecha consultada</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3><i class="fas fa-cogs"></i> Detalles Técnicos</h3>
            
            <div class="highlight">
                <h4><i class="fas fa-database"></i> Consulta SQL Actualizada:</h4>
                <p>La nueva consulta filtra por:</p>
                <ul>
                    <li>✅ <strong>ID_DIA:</strong> Solo horarios del día de semana consultado</li>
                    <li>✅ <strong>FECHA_DESDE/FECHA_HASTA:</strong> Solo horarios vigentes en la fecha</li>
                    <li>✅ <strong>ACTIVO = 'S':</strong> Solo horarios activos</li>
                    <li>✅ <strong>Sin asistencia:</strong> Empleados que no registraron entrada</li>
                </ul>
            </div>

            <p><strong>Beneficios del nuevo método:</strong></p>
            <ul>
                <li>Mayor precisión en las estadísticas</li>
                <li>Información más relevante para la toma de decisiones</li>
                <li>Consistencia entre stat cards y popups de detalles</li>
                <li>Filtrado inteligente por día específico</li>
            </ul>

            <div class="highlight">
                <h4><i class="fas fa-info-circle"></i> Estado Actual:</h4>
                <p>✅ <strong>Backend actualizado:</strong> Función <code>contarTurnosFaltantes()</code> implementada</p>
                <p>✅ <strong>Dashboard controller:</strong> Todas las funciones actualizadas</p>
                <p>✅ <strong>API endpoints:</strong> Devolviendo datos correctos</p>
                <p>🔄 <strong>Frontend:</strong> Funcionando con nuevos datos (requiere autenticación en producción)</p>
            </div>
        </div>
    </div>
</body>
</html>