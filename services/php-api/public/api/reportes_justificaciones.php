<?php
/**
 * API para reportes de justificaciones de faltas
 * Permite consultar y exportar justificaciones con filtros avanzados
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers para API REST
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Configuración y autenticación
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../auth/session.php';
    
    // Inicializar sesión
    initSession();
    
    // Determinar acción
    $action = $_GET['action'] ?? 'reporte';
    
    // Router principal
    switch ($action) {
        case 'reporte':
        case 'getReporte':
            getReporteJustificaciones($pdo);
            break;
            
        case 'estadisticas':
        case 'getEstadisticas':
            getEstadisticasJustificaciones($pdo);
            break;
            
        case 'export':
        case 'exportar':
            exportarJustificaciones($pdo);
            break;
            
        default:
            throw new Exception('Acción no válida', 400);
    }
    
} catch (Exception $e) {
    error_log("Error en API reportes justificaciones: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Obtener reporte de justificaciones con filtros
 */
function getReporteJustificaciones($pdo) {
    try {
        // Parámetros de filtrado
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $empleado_id = $_GET['empleado_id'] ?? null;
        $empleado_nombre = $_GET['empleado_nombre'] ?? null;
        $sede_id = $_GET['sede_id'] ?? null;
        $establecimiento_id = $_GET['establecimiento_id'] ?? null;
        $motivo = $_GET['motivo'] ?? null;
        $tipo_falta = $_GET['tipo_falta'] ?? null;
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        
        // Validaciones
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 500) $limit = 50;
        $offset = ($page - 1) * $limit;
        
        // Construir query base
        $sql_base = "
            FROM justificaciones j
            INNER JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
            LEFT JOIN empresa emp ON sed.ID_EMPRESA = emp.ID_EMPRESA
            LEFT JOIN empleado_horario_personalizado ehp ON j.turno_id = ehp.ID_EMPLEADO_HORARIO
            WHERE j.fecha_falta BETWEEN ? AND ?
        ";
        
        $params = [$fecha_inicio, $fecha_fin];
        
        // Agregar filtros opcionales
        if ($empleado_id) {
            $sql_base .= " AND j.empleado_id = ?";
            $params[] = $empleado_id;
        }
        
        if ($empleado_nombre) {
            $sql_base .= " AND (CONCAT(e.NOMBRE, ' ', e.APELLIDO) LIKE ? OR e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
            $params[] = "%$empleado_nombre%";
            $params[] = "%$empleado_nombre%";
            $params[] = "%$empleado_nombre%";
        }
        
        if ($sede_id) {
            $sql_base .= " AND sed.ID_SEDE = ?";
            $params[] = $sede_id;
        }
        
        if ($establecimiento_id) {
            $sql_base .= " AND e.ID_ESTABLECIMIENTO = ?";
            $params[] = $establecimiento_id;
        }
        
        if ($motivo) {
            $sql_base .= " AND j.motivo LIKE ?";
            $params[] = "%$motivo%";
        }
        
        if ($tipo_falta) {
            $sql_base .= " AND j.tipo_falta = ?";
            $params[] = $tipo_falta;
        }
        
        // Query para contar total
        $sql_count = "SELECT COUNT(*) as total " . $sql_base;
        $stmt = $pdo->prepare($sql_count);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Query para obtener datos
        $sql_data = "
            SELECT 
                j.id,
                j.empleado_id,
                j.fecha_falta,
                j.motivo,
                j.detalle_adicional,
                j.tipo_falta,
                j.hora_inicio_falta,
                j.hora_fin_falta,
                j.horas_programadas,
                j.turno_id,
                j.justificar_todos_turnos,
                j.turnos_ids,
                j.created_at,
                e.NOMBRE as empleado_nombre,
                e.APELLIDO as empleado_apellido,
                e.DNI as empleado_dni,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre_completo,
                est.NOMBRE as establecimiento_nombre,
                sed.NOMBRE as sede_nombre,
                emp.NOMBRE as empresa_nombre,
                ehp.NOMBRE_TURNO as turno_nombre,
                ehp.HORA_ENTRADA as turno_hora_entrada,
                ehp.HORA_SALIDA as turno_hora_salida,
                CASE 
                    WHEN j.tipo_falta = 'completa' THEN 'Día Completo'
                    WHEN j.tipo_falta = 'parcial' THEN 'Parcial'
                    WHEN j.tipo_falta = 'tardanza' THEN 'Tardanza'
                    ELSE j.tipo_falta
                END as tipo_falta_texto,
                CASE 
                    WHEN j.justificar_todos_turnos = 1 THEN 'Todos los turnos'
                    WHEN j.turno_id IS NOT NULL THEN CONCAT('Turno: ', ehp.NOMBRE_TURNO)
                    ELSE 'Sin turno específico'
                END as turno_descripcion
            " . $sql_base . "
            ORDER BY j.fecha_falta DESC, j.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $pdo->prepare($sql_data);
        $stmt->execute($params);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular estadísticas básicas
        $stats_sql = "
            SELECT 
                COUNT(*) as total_justificaciones,
                COUNT(DISTINCT j.empleado_id) as empleados_distintos,
                SUM(CASE WHEN j.tipo_falta = 'completa' THEN 1 ELSE 0 END) as faltas_completas,
                SUM(CASE WHEN j.tipo_falta = 'parcial' THEN 1 ELSE 0 END) as faltas_parciales,
                SUM(CASE WHEN j.tipo_falta = 'tardanza' THEN 1 ELSE 0 END) as tardanzas,
                SUM(j.horas_programadas) as total_horas_justificadas
            " . $sql_base;
            
        $stmt = $pdo->prepare($stats_sql);
        $stmt->execute($params);
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'justificaciones' => $justificaciones,
            'total' => intval($total),
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'estadisticas' => $estadisticas,
            'filtros' => [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'empleado_id' => $empleado_id,
                'empleado_nombre' => $empleado_nombre,
                'sede_id' => $sede_id,
                'establecimiento_id' => $establecimiento_id,
                'motivo' => $motivo,
                'tipo_falta' => $tipo_falta
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error en reporte justificaciones: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo reporte: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener estadísticas generales de justificaciones
 */
function getEstadisticasJustificaciones($pdo) {
    try {
        // Estadísticas de los últimos 30 días
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        // Estadísticas por motivo
        $stmt = $pdo->prepare("
            SELECT 
                j.motivo,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM justificaciones WHERE fecha_falta BETWEEN ? AND ?)), 2) as porcentaje
            FROM justificaciones j
            WHERE j.fecha_falta BETWEEN ? AND ?
            GROUP BY j.motivo
            ORDER BY cantidad DESC
            LIMIT 10
        ");
        $stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
        $por_motivo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estadísticas por tipo de falta
        $stmt = $pdo->prepare("
            SELECT 
                j.tipo_falta,
                COUNT(*) as cantidad,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM justificaciones WHERE fecha_falta BETWEEN ? AND ?)), 2) as porcentaje
            FROM justificaciones j
            WHERE j.fecha_falta BETWEEN ? AND ?
            GROUP BY j.tipo_falta
            ORDER BY cantidad DESC
        ");
        $stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
        $por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estadísticas por mes (últimos 12 meses)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(j.fecha_falta, '%Y-%m') as mes,
                COUNT(*) as cantidad
            FROM justificaciones j
            WHERE j.fecha_falta >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(j.fecha_falta, '%Y-%m')
            ORDER BY mes DESC
        ");
        $stmt->execute();
        $por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top empleados con más justificaciones
        $stmt = $pdo->prepare("
            SELECT 
                e.NOMBRE,
                e.APELLIDO,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
                COUNT(*) as total_justificaciones
            FROM justificaciones j
            INNER JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            WHERE j.fecha_falta BETWEEN ? AND ?
            GROUP BY j.empleado_id, e.NOMBRE, e.APELLIDO
            ORDER BY total_justificaciones DESC
            LIMIT 10
        ");
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        $top_empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'estadisticas' => [
                'por_motivo' => $por_motivo,
                'por_tipo' => $por_tipo,
                'por_mes' => $por_mes,
                'top_empleados' => $top_empleados
            ],
            'periodo' => [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error en estadísticas justificaciones: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()
        ]);
    }
}

/**
 * Exportar justificaciones a CSV/Excel
 */
function exportarJustificaciones($pdo) {
    try {
        // Los mismos filtros que el reporte
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $empleado_id = $_GET['empleado_id'] ?? null;
        $sede_id = $_GET['sede_id'] ?? null;
        $establecimiento_id = $_GET['establecimiento_id'] ?? null;
        $motivo = $_GET['motivo'] ?? null;
        $tipo_falta = $_GET['tipo_falta'] ?? null;
        $formato = $_GET['formato'] ?? 'csv';
        
        // Query para exportación (sin límite)
        $sql = "
            SELECT 
                j.id as 'ID',
                j.fecha_falta as 'Fecha Falta',
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as 'Empleado',
                e.DNI as 'DNI',
                j.motivo as 'Motivo',
                j.detalle_adicional as 'Detalle',
                CASE 
                    WHEN j.tipo_falta = 'completa' THEN 'Día Completo'
                    WHEN j.tipo_falta = 'parcial' THEN 'Parcial'
                    WHEN j.tipo_falta = 'tardanza' THEN 'Tardanza'
                    ELSE j.tipo_falta
                END as 'Tipo Falta',
                j.hora_inicio_falta as 'Hora Inicio',
                j.hora_fin_falta as 'Hora Fin',
                j.horas_programadas as 'Horas Programadas',
                CASE 
                    WHEN j.justificar_todos_turnos = 1 THEN 'Todos los turnos'
                    WHEN j.turno_id IS NOT NULL THEN CONCAT('Turno: ', ehp.NOMBRE_TURNO)
                    ELSE 'Sin turno específico'
                END as 'Turno',
                est.NOMBRE as 'Establecimiento',
                sed.NOMBRE as 'Sede',
                j.created_at as 'Fecha Creación'
            FROM justificaciones j
            INNER JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
            LEFT JOIN empleado_horario_personalizado ehp ON j.turno_id = ehp.ID_EMPLEADO_HORARIO
            WHERE j.fecha_falta BETWEEN ? AND ?
        ";
        
        $params = [$fecha_inicio, $fecha_fin];
        
        // Agregar filtros
        if ($empleado_id) {
            $sql .= " AND j.empleado_id = ?";
            $params[] = $empleado_id;
        }
        
        if ($sede_id) {
            $sql .= " AND sed.ID_SEDE = ?";
            $params[] = $sede_id;
        }
        
        if ($establecimiento_id) {
            $sql .= " AND e.ID_ESTABLECIMIENTO = ?";
            $params[] = $establecimiento_id;
        }
        
        if ($motivo) {
            $sql .= " AND j.motivo LIKE ?";
            $params[] = "%$motivo%";
        }
        
        if ($tipo_falta) {
            $sql .= " AND j.tipo_falta = ?";
            $params[] = $tipo_falta;
        }
        
        $sql .= " ORDER BY j.fecha_falta DESC, j.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($formato === 'csv') {
            // Configurar headers para descarga CSV
            header('Content-Type: application/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="justificaciones_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Crear CSV
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            if (!empty($datos)) {
                fputcsv($output, array_keys($datos[0]), ';');
                
                // Datos
                foreach ($datos as $row) {
                    fputcsv($output, $row, ';');
                }
            }
            
            fclose($output);
            exit;
        } else {
            // Formato JSON para otras opciones
            echo json_encode([
                'success' => true,
                'datos' => $datos,
                'total' => count($datos),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error en exportación justificaciones: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error en exportación: ' . $e->getMessage()
        ]);
    }
}
?>