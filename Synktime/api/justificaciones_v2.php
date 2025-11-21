<?php
/**
 * API para gestionar justificaciones de faltas v2.0
 * Funciona con la nueva estructura de tabla justificaciones
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers para API REST
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Configuración y autenticación
    require_once 'config/database.php';
    // require_once 'auth/session.php'; // Comentado para testing inicial
    
    // Determinar método y acción
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    // Router principal
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $action);
            break;
            
        case 'POST':
            handlePostRequest($pdo);
            break;
            
        case 'PUT':
            handlePutRequest($pdo);
            break;
            
        case 'DELETE':
            handleDeleteRequest($pdo);
            break;
            
        default:
            throw new Exception('Método HTTP no permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("Error en API justificaciones: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Manejar requests GET
 */
function handleGetRequest($pdo, $action) {
    switch ($action) {
        case 'empleados_elegibles':
        case 'getEmpleadosElegibles':
            getEmpleadosElegibles($pdo);
            break;
            
        case 'recientes':
        case 'getRecientes':
            getJustificacionesRecientes($pdo);
            break;
            
        case 'detalle':
        case 'getDetalle':
            getDetalleJustificacion($pdo);
            break;
            
        case 'config':
            getConfiguracion($pdo);
            break;
            
        case 'estadisticas':
            getEstadisticas($pdo);
            break;
            
        default:
            getJustificaciones($pdo);
    }
}

/**
 * Manejar requests POST (crear)
 */
function handlePostRequest($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido en request body', 400);
    }
    createJustificacion($pdo, $input);
}

/**
 * Manejar requests PUT (actualizar)
 */
function handlePutRequest($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido en request body', 400);
    }
    updateJustificacion($pdo, $input);
}

/**
 * Manejar requests DELETE (eliminar)
 */
function handleDeleteRequest($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido en request body', 400);
    }
    deleteJustificacion($pdo, $input);
}

/**
 * Obtener empleados elegibles para justificación
 */
function getEmpleadosElegibles($pdo) {
    try {
        $fecha_referencia = $_GET['fecha'] ?? date('Y-m-d');
        $sede_id = $_GET['sede_id'] ?? null;
        $establecimiento_id = $_GET['establecimiento_id'] ?? null;
        
        // Query base con información completa del empleado
        $sql = "
            SELECT 
                e.ID_EMPLEADO as id,
                e.DNI as codigo,
                e.NOMBRE as nombre,
                e.APELLIDO as apellido,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
                e.CORREO as email,
                e.TELEFONO as telefono,
                e.ID_ESTABLECIMIENTO as establecimiento_id,
                est.NOMBRE as establecimiento_nombre,
                sed.ID_SEDE as sede_id,
                sed.NOMBRE as sede_nombre,
                
                -- Verificar si ya tiene justificación para esta fecha
                CASE 
                    WHEN j.id IS NOT NULL THEN 1 
                    ELSE 0 
                END as ya_justificado,
                
                -- Información de asistencia del día
                CASE 
                    WHEN a.ID_ASISTENCIA IS NOT NULL THEN 1 
                    ELSE 0 
                END as tiene_asistencia,
                
                -- Horas programadas (default 8)
                8.00 as horas_programadas,
                
                -- Estado de elegibilidad
                CASE 
                    WHEN j.id IS NOT NULL THEN 'Ya justificado'
                    WHEN a.ID_ASISTENCIA IS NOT NULL THEN 'Con asistencia'
                    WHEN DATEDIFF(NOW(), ?) > 0 AND DATEDIFF(NOW(), ?) <= 1 THEN 'Elegible'
                    WHEN DATEDIFF(NOW(), ?) > 1 THEN 'Fuera de tiempo'
                    ELSE 'No elegible'
                END as estado_elegibilidad
                
            FROM empleado e
            INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
            LEFT JOIN justificaciones j ON e.ID_EMPLEADO = j.empleado_id 
                AND DATE(j.fecha_falta) = DATE(?)
                AND j.deleted_at IS NULL
            LEFT JOIN asistencia a ON e.ID_EMPLEADO = a.ID_EMPLEADO 
                AND DATE(a.FECHA) = DATE(?)
            
            WHERE e.ESTADO = 'A' 
            AND e.ACTIVO = 'S'
        ";
        
        $params = [$fecha_referencia, $fecha_referencia, $fecha_referencia, $fecha_referencia, $fecha_referencia];
        
        // Agregar filtros opcionales
        if ($sede_id) {
            $sql .= " AND sed.ID_SEDE = ?";
            $params[] = $sede_id;
        }
        
        if ($establecimiento_id) {
            $sql .= " AND est.ID_ESTABLECIMIENTO = ?";
            $params[] = $establecimiento_id;
        }
        
        $sql .= " ORDER BY e.NOMBRE, e.APELLIDO LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener configuración de límite de horas
        $config = getConfigValue($pdo, 'horas_limite_justificacion', 16);
        
        echo json_encode([
            'success' => true,
            'empleados' => $empleados,
            'total' => count($empleados),
            'config' => [
                'fecha_referencia' => $fecha_referencia,
                'horas_limite' => $config,
                'sede_filtro' => $sede_id,
                'establecimiento_filtro' => $establecimiento_id
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error obteniendo empleados elegibles: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener justificaciones recientes
 */
function getJustificacionesRecientes($pdo) {
    try {
        $limit = (int)($_GET['limit'] ?? 10);
        $estado = $_GET['estado'] ?? null;
        $empleado_id = $_GET['empleado_id'] ?? null;
        
        $sql = "
            SELECT 
                j.id,
                j.empleado_id,
                j.fecha_falta,
                j.fecha_justificacion,
                j.motivo,
                j.detalle_adicional,
                j.horas_programadas,
                j.tipo_falta,
                j.estado,
                j.comentario_aprobacion,
                j.impacto_salario,
                
                -- Información del empleado
                e.NOMBRE as empleado_nombre,
                e.APELLIDO as empleado_apellido,
                e.DNI as empleado_dni,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre_completo,
                
                -- Información de ubicación
                est.NOMBRE as establecimiento_nombre,
                sed.NOMBRE as sede_nombre,
                
                -- Información de aprobación
                ua.username as aprobado_por_usuario,
                j.fecha_aprobacion,
                
                -- Información de creación
                uc.username as creado_por_usuario,
                j.created_at,
                j.updated_at
                
            FROM justificaciones j
            INNER JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
            LEFT JOIN usuario ua ON j.aprobada_por = ua.ID_USUARIO
            LEFT JOIN usuario uc ON j.justificado_por = uc.ID_USUARIO
            
            WHERE j.deleted_at IS NULL
        ";
        
        $params = [];
        
        if ($estado) {
            $sql .= " AND j.estado = ?";
            $params[] = $estado;
        }
        
        if ($empleado_id) {
            $sql .= " AND j.empleado_id = ?";
            $params[] = $empleado_id;
        }
        
        $sql .= " ORDER BY j.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'justificaciones' => $justificaciones,
            'total' => count($justificaciones),
            'filtros' => [
                'limit' => $limit,
                'estado' => $estado,
                'empleado_id' => $empleado_id
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error obteniendo justificaciones recientes: ' . $e->getMessage(), 500);
    }
}

/**
 * Crear nueva justificación
 */
function createJustificacion($pdo, $data) {
    try {
        // Validar datos requeridos
        $required = ['empleado_id', 'fecha_falta', 'motivo'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Campo requerido faltante: $field", 400);
            }
        }
        
        // Validar fecha
        $fecha_falta = new DateTime($data['fecha_falta']);
        $ahora = new DateTime();
        $limite_horas = getConfigValue($pdo, 'horas_limite_justificacion', 16);
        
        $diff_horas = ($ahora->getTimestamp() - $fecha_falta->getTimestamp()) / 3600;
        
        if ($diff_horas > $limite_horas) {
            throw new Exception("Solo se pueden justificar faltas de las últimas $limite_horas horas", 400);
        }
        
        // Verificar que no exista justificación duplicada
        $stmt = $pdo->prepare("
            SELECT id FROM justificaciones 
            WHERE empleado_id = ? AND DATE(fecha_falta) = DATE(?) AND deleted_at IS NULL
        ");
        $stmt->execute([$data['empleado_id'], $data['fecha_falta']]);
        
        if ($stmt->fetch()) {
            throw new Exception('Ya existe una justificación para este empleado en esta fecha', 409);
        }
        
        // Preparar datos para inserción
        $insertData = [
            'empleado_id' => $data['empleado_id'],
            'fecha_falta' => $data['fecha_falta'],
            'motivo' => $data['motivo'],
            'detalle_adicional' => $data['detalle_adicional'] ?? null,
            'horas_programadas' => $data['horas_programadas'] ?? 8.00,
            'tipo_falta' => $data['tipo_falta'] ?? 'completa',
            'hora_inicio_falta' => $data['hora_inicio_falta'] ?? null,
            'hora_fin_falta' => $data['hora_fin_falta'] ?? null,
            'impacto_salario' => $data['impacto_salario'] ?? getConfigValue($pdo, 'impacta_salario_default', false),
            'justificado_por' => $data['usuario_id'] ?? null // TODO: Obtener de sesión
        ];
        
        // Insertar justificación
        $sql = "
            INSERT INTO justificaciones (
                empleado_id, fecha_falta, motivo, detalle_adicional,
                horas_programadas, tipo_falta, hora_inicio_falta, hora_fin_falta,
                impacto_salario, justificado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $insertData['empleado_id'],
            $insertData['fecha_falta'],
            $insertData['motivo'],
            $insertData['detalle_adicional'],
            $insertData['horas_programadas'],
            $insertData['tipo_falta'],
            $insertData['hora_inicio_falta'],
            $insertData['hora_fin_falta'],
            $insertData['impacto_salario'],
            $insertData['justificado_por']
        ]);
        
        $justificacion_id = $pdo->lastInsertId();
        
        // Registrar en log de auditoría
        logJustificacionChange($pdo, $justificacion_id, $insertData['justificado_por'], 'crear', null, 'pendiente', 'Justificación creada');
        
        echo json_encode([
            'success' => true,
            'message' => 'Justificación creada exitosamente',
            'justificacion_id' => $justificacion_id,
            'data' => $insertData,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error creando justificación: ' . $e->getMessage(), $e->getCode() ?: 500);
    }
}

/**
 * Obtener configuración del sistema
 */
function getConfiguracion($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT clave, valor, tipo, descripcion FROM justificaciones_config");
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($configs as $config) {
            $valor = $config['valor'];
            
            // Convertir según tipo
            switch ($config['tipo']) {
                case 'number':
                    $valor = (float)$valor;
                    break;
                case 'boolean':
                    $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'json':
                    $valor = json_decode($valor, true);
                    break;
            }
            
            $result[$config['clave']] = [
                'valor' => $valor,
                'descripcion' => $config['descripcion']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'configuracion' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error obteniendo configuración: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener estadísticas de justificaciones
 */
function getEstadisticas($pdo) {
    try {
        // Estadísticas generales
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN estado = 'aprobada' THEN 1 END) as aprobadas,
                COUNT(CASE WHEN estado = 'rechazada' THEN 1 END) as rechazadas,
                COUNT(CASE WHEN estado = 'revision' THEN 1 END) as en_revision,
                COUNT(CASE WHEN tipo_falta = 'completa' THEN 1 END) as faltas_completas,
                COUNT(CASE WHEN tipo_falta = 'parcial' THEN 1 END) as faltas_parciales,
                COUNT(CASE WHEN DATE(fecha_falta) = CURDATE() THEN 1 END) as hoy,
                COUNT(CASE WHEN DATE(fecha_falta) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as ultima_semana,
                COUNT(CASE WHEN DATE(fecha_falta) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as ultimo_mes
            FROM justificaciones 
            WHERE deleted_at IS NULL
        ");
        $stmt->execute();
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'estadisticas' => $estadisticas,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error obteniendo estadísticas: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener valor de configuración
 */
function getConfigValue($pdo, $clave, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT valor, tipo FROM justificaciones_config WHERE clave = ?");
        $stmt->execute([$clave]);
        $config = $stmt->fetch();
        
        if (!$config) {
            return $default;
        }
        
        $valor = $config['valor'];
        
        switch ($config['tipo']) {
            case 'number':
                return (float)$valor;
            case 'boolean':
                return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($valor, true);
            default:
                return $valor;
        }
    } catch (Exception $e) {
        error_log("Error obteniendo config $clave: " . $e->getMessage());
        return $default;
    }
}

/**
 * Registrar cambio en log de auditoría
 */
function logJustificacionChange($pdo, $justificacion_id, $usuario_id, $accion, $estado_anterior, $estado_nuevo, $comentario = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO justificaciones_log 
            (justificacion_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$justificacion_id, $usuario_id, $accion, $estado_anterior, $estado_nuevo, $comentario]);
    } catch (Exception $e) {
        error_log("Error registrando log de justificación: " . $e->getMessage());
    }
}

// Funciones adicionales para completar el CRUD
function getJustificaciones($pdo) {
    // Implementar listado completo con filtros
    getJustificacionesRecientes($pdo);
}

function getDetalleJustificacion($pdo) {
    // Implementar obtener detalle específico
    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('ID de justificación requerido', 400);
    }
    
    // TODO: Implementar consulta de detalle
    echo json_encode(['success' => true, 'message' => 'Detalle no implementado aún']);
}

function updateJustificacion($pdo, $data) {
    // TODO: Implementar actualización
    echo json_encode(['success' => true, 'message' => 'Actualización no implementada aún']);
}

function deleteJustificacion($pdo, $data) {
    // TODO: Implementar eliminación (soft delete)
    echo json_encode(['success' => true, 'message' => 'Eliminación no implementada aún']);
}
?>