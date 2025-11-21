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
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../auth/session.php';
    
    // Inicializar sesión
    initSession();
    
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
    http_response_code(500);
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
            
        case 'turnos_empleado':
        case 'getTurnosEmpleado':
            getTurnosEmpleado($pdo);
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
    
    if (!$input) {
        throw new Exception('Datos de entrada inválidos', 400);
    }
    
    createJustificacion($pdo, $input);
}

/**
 * Manejar requests PUT (actualizar)
 */
function handlePutRequest($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('ID de justificación requerido', 400);
    }
    
    updateJustificacion($pdo, $input);
}

/**
 * Manejar requests DELETE (eliminar)
 */
function handleDeleteRequest($pdo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID de justificación requerido', 400);
    }
    
    deleteJustificacion($pdo, $id);
}

/**
 * Obtener configuración del sistema
 */
function getConfiguracion($pdo) {
    try {
        // Configuración por defecto simplificada para el sistema de justificaciones
        $configuracion = [
            'dias_limite_justificacion' => [
                'valor' => 7,
                'descripcion' => 'Días límite para crear una justificación después de la falta'
            ],
            'requiere_documentacion' => [
                'valor' => false,
                'descripcion' => 'Si se requiere documentación adjunta para las justificaciones'
            ],
            'horas_laborales_dia' => [
                'valor' => 8.0,
                'descripcion' => 'Horas laborales por defecto por día'
            ],
            'tipos_falta_permitidos' => [
                'valor' => ['completa', 'parcial', 'tardanza'],
                'descripcion' => 'Tipos de falta permitidos en el sistema'
            ],
            'motivos_justificacion' => [
                'valor' => [
                    'Enfermedad',
                    'Emergencia familiar',
                    'Cita médica',
                    'Trámites personales',
                    'Calamidad doméstica',
                    'Permiso personal',
                    'Otro'
                ],
                'descripcion' => 'Motivos predefinidos para justificaciones'
            ]
        ];

        echo json_encode([
            'success' => true,
            'configuracion' => $configuracion,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        error_log("Error al obtener configuración: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo configuración: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener empleados elegibles para justificación con lógica avanzada
 */
function getEmpleadosElegibles($pdo) {
    try {
        $fecha_referencia = $_GET['fecha'] ?? date('Y-m-d');
        $sede_id = $_GET['sede_id'] ?? null;
        $establecimiento_id = $_GET['establecimiento_id'] ?? null;
        
        // Query simplificado para obtener empleados activos
        $sql = "
            SELECT 
                e.ID_EMPLEADO as id,
                e.DNI as codigo,
                e.NOMBRE as nombre,
                e.APELLIDO as apellido,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
                e.CORREO as email,
                e.ID_ESTABLECIMIENTO as establecimiento_id,
                est.NOMBRE as establecimiento_nombre,
                sed.ID_SEDE as sede_id,
                sed.NOMBRE as sede_nombre
            FROM empleado e
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
            WHERE e.ACTIVO = 'S' 
            AND e.ESTADO = 'A'
        ";
        
        $params = [];
        
        // Agregar filtros opcionales
        if ($sede_id) {
            $sql .= " AND sed.ID_SEDE = ?";
            $params[] = $sede_id;
        }
        
        if ($establecimiento_id) {
            $sql .= " AND e.ID_ESTABLECIMIENTO = ?";
            $params[] = $establecimiento_id;
        }
        
        $sql .= " ORDER BY e.NOMBRE, e.APELLIDO LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Determinar día de la semana para verificar horarios (1=Lunes, 7=Domingo)
        $dia_semana = date('N', strtotime($fecha_referencia));
        
        $empleados_con_horarios = [];
        
        foreach ($empleados as $empleado) {
            // Verificar si ya tiene justificación para esta fecha
            $stmt_just = $pdo->prepare("
                SELECT id FROM justificaciones 
                WHERE empleado_id = ? AND fecha_falta = ?
            ");
            $stmt_just->execute([$empleado['id'], $fecha_referencia]);
            $ya_justificado = $stmt_just->fetch() ? true : false;
            
            // Verificar si ya tiene registro de asistencia para esta fecha
            $stmt_asistencia = $pdo->prepare("
                SELECT COUNT(*) as registros FROM asistencia 
                WHERE ID_EMPLEADO = ? AND FECHA = ?
            ");
            $stmt_asistencia->execute([$empleado['id'], $fecha_referencia]);
            $tiene_asistencia = $stmt_asistencia->fetchColumn() > 0;
            
            // Obtener turnos del empleado para esta fecha
            // La tabla usa ID_DIA donde 1=Lunes, 2=Martes, etc.
            $stmt_turnos = $pdo->prepare("
                SELECT 
                    ehp.ID_EMPLEADO_HORARIO as id_empleado_horario,
                    ehp.NOMBRE_TURNO as nombre_turno,
                    ehp.HORA_ENTRADA as hora_entrada,
                    ehp.HORA_SALIDA as hora_salida,
                    ehp.ID_DIA as dia_id
                FROM empleado_horario_personalizado ehp
                WHERE ehp.ID_EMPLEADO = ? 
                AND ehp.ACTIVO = 'S' 
                AND ehp.ID_DIA = ?
                AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
                AND ehp.FECHA_DESDE <= ?
            ");
            $stmt_turnos->execute([$empleado['id'], $dia_semana, $fecha_referencia, $fecha_referencia]);
            $turnos_empleado = $stmt_turnos->fetchAll(PDO::FETCH_ASSOC);
            
            // Filtrar turnos que ya tienen justificación específica
            $turnos_sin_justificar = [];
            foreach ($turnos_empleado as $turno) {
                $stmt_turno_just = $pdo->prepare("
                    SELECT id FROM justificaciones 
                    WHERE empleado_id = ? AND fecha_falta = ? 
                    AND (turno_id = ? OR justificar_todos_turnos = 1)
                ");
                $stmt_turno_just->execute([$empleado['id'], $fecha_referencia, $turno['id_empleado_horario']]);
                $turno_ya_justificado = $stmt_turno_just->fetch() ? true : false;
                
                if (!$turno_ya_justificado) {
                    $turnos_sin_justificar[] = [
                        'id_empleado_horario' => $turno['id_empleado_horario'],
                        'nombre_turno' => $turno['nombre_turno'],
                        'hora_entrada' => $turno['hora_entrada'],
                        'hora_salida' => $turno['hora_salida']
                    ];
                }
            }
            
            // Solo incluir empleados que:
            // 1. NO tienen asistencia registrada para este día
            // 2. Tienen turnos programados para este día
            // 3. Tienen al menos un turno sin justificar
            if (!$tiene_asistencia && !empty($turnos_sin_justificar)) {
                $empleados_con_horarios[] = array_merge($empleado, [
                    'turnos_disponibles' => $turnos_sin_justificar,
                    'multiple_turnos' => count($turnos_sin_justificar) > 1,
                    'ya_justificado' => $ya_justificado,
                    'fecha_referencia' => $fecha_referencia,
                    'dia_semana' => $dia_semana,
                    'tiene_asistencia' => $tiene_asistencia,
                    'elegible' => true
                ]);
            }
        }

        echo json_encode([
            'success' => true,
            'empleados' => $empleados_con_horarios,
            'total' => count($empleados_con_horarios),
            'fecha_referencia' => $fecha_referencia,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        error_log("Error obteniendo empleados elegibles: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo empleados elegibles: ' . $e->getMessage()
        ]);
    }
}

/**
 * Lógica avanzada de elegibilidad de empleados
 */
function getEmpleadosConElegibilidadAvanzada($pdo, $empresaId, $fecha_referencia, $sede_id = null, $establecimiento_id = null) {
    // Determinar día de la semana (1=Lunes, 7=Domingo)
    $dia_semana = date('N', strtotime($fecha_referencia));
    
    // Construir query base
    $sql = "
        SELECT 
            e.ID_EMPLEADO as id,
            e.DNI as codigo,
            e.NOMBRE as nombre,
            e.APELLIDO as apellido,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
            e.CORREO as email,
            e.ID_ESTABLECIMIENTO as establecimiento_id,
            est.NOMBRE as establecimiento_nombre,
            sed.ID_SEDE as sede_id,
            sed.NOMBRE as sede_nombre
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
        WHERE e.ACTIVO = 'S' 
        AND e.ESTADO = 'A'
        AND sed.ID_EMPRESA = ?
    ";
    
    $params = [$empresaId];
    
    // Agregar filtros opcionales
    if ($sede_id) {
        $sql .= " AND sed.ID_SEDE = ?";
        $params[] = $sede_id;
    }
    
    if ($establecimiento_id) {
        $sql .= " AND e.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimiento_id;
    }
    
    $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $empleados_con_elegibilidad = [];
    
    foreach ($empleados as $empleado) {
        $elegibilidad = verificarElegibilidadEmpleado($pdo, $empleado['id'], $dia_semana, $fecha_referencia);
        
        if ($elegibilidad['es_elegible'] || count($elegibilidad['turnos_disponibles']) > 0) {
            $empleados_con_elegibilidad[] = array_merge($empleado, [
                'estado_elegibilidad' => $elegibilidad['estado'],
                'turnos_disponibles' => $elegibilidad['turnos_disponibles'],
                'multiple_turnos' => count($elegibilidad['turnos_disponibles']) > 1,
                'horas_programadas' => $elegibilidad['total_horas'],
                'ya_justificado' => $elegibilidad['ya_justificado']
            ]);
        }
    }
    
    return $empleados_con_elegibilidad;
}

/**
 * Verificar elegibilidad específica de un empleado
 */
function verificarElegibilidadEmpleado($pdo, $empleado_id, $dia_semana, $fecha_referencia) {
    $turnos_disponibles = [];
    $total_horas = 0;
    $ya_justificado = false;
    
    // Verificar si ya tiene justificación para esta fecha
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM justificaciones WHERE empleado_id = ? AND fecha_falta = ?");
    $stmt->execute([$empleado_id, $fecha_referencia]);
    $ya_justificado = $stmt->fetchColumn() > 0;
    
    if ($ya_justificado) {
        return [
            'es_elegible' => false,
            'estado' => 'Ya justificado',
            'turnos_disponibles' => [],
            'total_horas' => 0,
            'ya_justificado' => true
        ];
    }
    
    // Obtener horarios del empleado para el día específico
    $sql = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.NOMBRE_TURNO,
            ehp.TOLERANCIA,
            TIMESTAMPDIFF(HOUR, ehp.HORA_ENTRADA, ehp.HORA_SALIDA) as horas_turno
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ?
        AND ehp.ID_DIA = ?
        AND ehp.ACTIVO = 'S'
        AND (ehp.FECHA_DESDE IS NULL OR ehp.FECHA_DESDE <= ?)
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        ORDER BY ehp.HORA_ENTRADA
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$empleado_id, $dia_semana, $fecha_referencia, $fecha_referencia]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($horarios)) {
        return [
            'es_elegible' => false,
            'estado' => 'Sin horario para este día',
            'turnos_disponibles' => [],
            'total_horas' => 0,
            'ya_justificado' => false
        ];
    }
    
    // Verificar cada horario
    foreach ($horarios as $horario) {
        // Verificar si ya marcó entrada para este horario específico
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM asistencia 
            WHERE ID_EMPLEADO = ? 
            AND FECHA = ? 
            AND ID_EMPLEADO_HORARIO = ?
            AND TIPO = 'ENTRADA'
        ");
        $stmt->execute([$empleado_id, $fecha_referencia, $horario['ID_EMPLEADO_HORARIO']]);
        $ya_marco_entrada = $stmt->fetchColumn() > 0;
        
        if (!$ya_marco_entrada) {
            // Verificar si el horario está dentro de las próximas 16 horas
            $hora_entrada_completa = $fecha_referencia . ' ' . $horario['HORA_ENTRADA'];
            $hora_entrada_timestamp = strtotime($hora_entrada_completa);
            $ahora = time();
            $limite_16_horas = $ahora + (16 * 60 * 60);
            
            if ($hora_entrada_timestamp <= $limite_16_horas) {
                $turnos_disponibles[] = [
                    'id_empleado_horario' => $horario['ID_EMPLEADO_HORARIO'],
                    'nombre_turno' => $horario['NOMBRE_TURNO'],
                    'hora_entrada' => $horario['HORA_ENTRADA'],
                    'hora_salida' => $horario['HORA_SALIDA'],
                    'horas' => $horario['horas_turno'],
                    'disponible_en_16h' => true
                ];
                
                $total_horas += $horario['horas_turno'];
            }
        }
    }
    
    $es_elegible = count($turnos_disponibles) > 0;
    $estado = $es_elegible ? 
        (count($turnos_disponibles) > 1 ? 'Múltiples turnos disponibles' : 'Elegible') : 
        'Sin turnos disponibles';
    
    return [
        'es_elegible' => $es_elegible,
        'estado' => $estado,
        'turnos_disponibles' => $turnos_disponibles,
        'total_horas' => $total_horas,
        'ya_justificado' => false
    ];
}

/**
 * Obtener turnos específicos de un empleado para una fecha
 */
function getTurnosEmpleado($pdo) {
    try {
        // Verificar autenticación
        requireAuth();
        
        $empleado_id = $_GET['empleado_id'] ?? null;
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        if (!$empleado_id) {
            throw new Exception('ID de empleado requerido', 400);
        }
        
        // Verificar que el empleado pertenece a la empresa del usuario logueado
        $currentUser = getCurrentUser();
        $empresaId = $currentUser['id_empresa'] ?? null;
        
        if (!$empresaId) {
            throw new Exception('Usuario no tiene empresa asignada', 403);
        }
        
        // Verificar que el empleado pertenece a la empresa
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM empleado e
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
            WHERE e.ID_EMPLEADO = ? AND sed.ID_EMPRESA = ?
        ");
        $stmt->execute([$empleado_id, $empresaId]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new Exception('Empleado no encontrado o no pertenece a su empresa', 403);
        }
        
        // Obtener elegibilidad del empleado
        $dia_semana = date('N', strtotime($fecha));
        $elegibilidad = verificarElegibilidadEmpleado($pdo, $empleado_id, $dia_semana, $fecha);
        
        echo json_encode([
            'success' => true,
            'empleado_id' => $empleado_id,
            'fecha' => $fecha,
            'dia_semana' => $dia_semana,
            'turnos_disponibles' => $elegibilidad['turnos_disponibles'],
            'multiple_turnos' => count($elegibilidad['turnos_disponibles']) > 1,
            'estado_elegibilidad' => $elegibilidad['estado'],
            'es_elegible' => $elegibilidad['es_elegible'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo turnos de empleado: " . $e->getMessage());
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo turnos: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener justificaciones recientes
 */
function getJustificacionesRecientes($pdo) {
    try {
        $limit = intval($_GET['limit'] ?? 10);
        $pagina = intval($_GET['pagina'] ?? 1);
        
        // Validar parámetros para seguridad
        if ($limit <= 0 || $limit > 100) {
            $limit = 10;
        }
        if ($pagina <= 0) {
            $pagina = 1;
        }
        
        $offset = ($pagina - 1) * $limit;

        // Contar total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM justificaciones j WHERE 1=1";
        $stmtCount = $pdo->query($sqlCount);
        $totalRegistros = $stmtCount->fetch()['total'];
        $totalPaginas = ceil($totalRegistros / $limit);

        // Query adaptado a la nueva estructura simplificada
        $sql = "
            SELECT 
                j.id,
                j.empleado_id,
                j.fecha_falta,
                j.motivo,
                j.detalle_adicional,
                j.tipo_falta,
                j.turno_id,
                j.justificar_todos_turnos,
                j.created_at,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre_completo,
                e.DNI as empleado_dni,
                est.NOMBRE as establecimiento_nombre,
                ehp.NOMBRE_TURNO as turno_nombre,
                CASE 
                    WHEN j.justificar_todos_turnos = 1 THEN 'Todos los turnos'
                    WHEN j.turno_id IS NOT NULL THEN CONCAT('Turno: ', ehp.NOMBRE_TURNO)
                    ELSE 'Sin turno específico'
                END as turno_descripcion
            FROM justificaciones j
            LEFT JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN empleado_horario_personalizado ehp ON j.turno_id = ehp.ID_EMPLEADO_HORARIO
            ORDER BY j.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $pdo->query($sql);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'justificaciones' => $justificaciones,
            'total' => count($justificaciones),
            'total_registros' => $totalRegistros,
            'total_paginas' => $totalPaginas,
            'pagina_actual' => $pagina,
            'limite_por_pagina' => $limit,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        error_log("Error al obtener justificaciones recientes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo justificaciones: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener todas las justificaciones con filtros
 */
function getJustificaciones($pdo) {
    try {
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $estado = $_GET['estado'] ?? null;
        $limit = intval($_GET['limit'] ?? 10);
        $pagina = intval($_GET['pagina'] ?? 1);
        
        // Validar parámetros para seguridad
        if ($limit <= 0 || $limit > 100) {
            $limit = 10;
        }
        if ($pagina <= 0) {
            $pagina = 1;
        }
        
        $offset = ($pagina - 1) * $limit;
        
        // Contar total de registros
        $sqlCount = "
            SELECT COUNT(*) as total 
            FROM justificaciones j
            LEFT JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            WHERE j.fecha_falta BETWEEN ? AND ?
        ";
        $paramsCount = [$fecha_inicio, $fecha_fin];
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute($paramsCount);
        $totalRegistros = $stmtCount->fetch()['total'];
        $totalPaginas = ceil($totalRegistros / $limit);
        
        $sql = "
            SELECT 
                j.id,
                j.empleado_id,
                j.fecha_falta,
                j.motivo,
                j.detalle_adicional,
                j.tipo_falta,
                j.created_at,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre_completo,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre,
                j.justificado_por
            FROM justificaciones j
            LEFT JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            WHERE j.fecha_falta BETWEEN ? AND ?
        ";
        
        $params = [$fecha_inicio, $fecha_fin];
        
        // Remover filtro por estado ya que la columna no existe
        // if ($estado) {
        //     $sql .= " AND j.estado = ?";
        //     $params[] = $estado;
        // }
        
        $sql .= " ORDER BY j.fecha_falta DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'justificaciones' => $justificaciones,
            'filtros' => [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'estado' => $estado
            ],
            'total' => count($justificaciones),
            'total_registros' => $totalRegistros,
            'total_paginas' => $totalPaginas,
            'pagina_actual' => $pagina,
            'limite_por_pagina' => $limit,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error al obtener justificaciones: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo justificaciones: ' . $e->getMessage()
        ]);
    }
}

/**
 * Crear nueva justificación
 */
function createJustificacion($pdo, $data) {
    try {
        // Validaciones básicas
        if (!isset($data['empleado_id']) || !isset($data['fecha_falta']) || !isset($data['motivo'])) {
            throw new Exception('Campos requeridos: empleado_id, fecha_falta, motivo', 400);
        }
        
        // Verificar si ya existe justificación para este empleado en esta fecha
        // Si se especifica turno_id, verificar solo ese turno específico
        // Si justificar_todos_turnos=true, verificar que no haya justificación general
        if (isset($data['turno_id']) && !empty($data['turno_id'])) {
            // Verificar turno específico
            $stmt = $pdo->prepare("
                SELECT id FROM justificaciones 
                WHERE empleado_id = ? AND fecha_falta = ? 
                AND (turno_id = ? OR justificar_todos_turnos = 1)
            ");
            $stmt->execute([$data['empleado_id'], $data['fecha_falta'], $data['turno_id']]);
            
            if ($stmt->fetch()) {
                throw new Exception('Ya existe una justificación para este empleado, fecha y turno específico', 400);
            }
        } else {
            // Verificar justificación general (cualquier turno para esta fecha)
            $stmt = $pdo->prepare("
                SELECT id FROM justificaciones 
                WHERE empleado_id = ? AND fecha_falta = ?
            ");
            $stmt->execute([$data['empleado_id'], $data['fecha_falta']]);
            
            if ($stmt->fetch()) {
                throw new Exception('Ya existe una justificación para este empleado en esta fecha', 400);
            }
        }
        
        // Preparar datos para inserción
        $justificar_todos_turnos = isset($data['justificar_todos_turnos']) ? 
            ($data['justificar_todos_turnos'] ? 1 : 0) : 0;
        
        $turno_id = null;
        $turnos_ids = null;
        
        if ($justificar_todos_turnos) {
            // Si justifica todos los turnos, guardar los IDs en turnos_ids
            if (isset($data['turnos_ids']) && is_array($data['turnos_ids'])) {
                $turnos_ids = json_encode($data['turnos_ids']);
            }
        } else {
            // Si justifica un turno específico, usar turno_id
            $turno_id = isset($data['turno_id']) ? (int)$data['turno_id'] : null;
        }
        
        // Crear justificación
        $stmt = $pdo->prepare("
            INSERT INTO justificaciones (
                empleado_id, fecha_falta, motivo, detalle_adicional, 
                tipo_falta, hora_inicio_falta, hora_fin_falta, 
                horas_programadas, justificado_por, turno_id,
                justificar_todos_turnos, turnos_ids
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['empleado_id'],
            $data['fecha_falta'],
            $data['motivo'],
            $data['detalle_adicional'] ?? null,
            $data['tipo_falta'] ?? 'completa',
            $data['hora_inicio_falta'] ?? null,
            $data['hora_fin_falta'] ?? null,
            $data['horas_programadas'] ?? 8.0,
            1, // Usuario temporal - justificado_por
            $turno_id,
            $justificar_todos_turnos,
            $turnos_ids
        ]);
        
        $justificacion_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Justificación creada exitosamente',
            'justificacion_id' => $justificacion_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error al crear justificación: " . $e->getMessage());
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Actualizar justificación existente
 */
function updateJustificacion($pdo, $data) {
    try {
        $id = $data['id'];
        
        // Verificar que existe
        $stmt = $pdo->prepare("SELECT id FROM justificaciones WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Justificación no encontrada', 404);
        }
        
        // Actualizar campos modificables
        $fields = [];
        $params = [];
        
        if (isset($data['motivo'])) {
            $fields[] = 'motivo = ?';
            $params[] = $data['motivo'];
        }
        
        if (isset($data['detalle_adicional'])) {
            $fields[] = 'detalle_adicional = ?';
            $params[] = $data['detalle_adicional'];
        }
        
        if (isset($data['estado'])) {
            $fields[] = 'estado = ?';
            $params[] = $data['estado'];
        }
        
        if (empty($fields)) {
            throw new Exception('No hay campos para actualizar', 400);
        }
        
        $fields[] = 'updated_at = NOW()';
        $params[] = $id;
        
        $sql = "UPDATE justificaciones SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Justificación actualizada exitosamente',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error al actualizar justificación: " . $e->getMessage());
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Eliminar justificación (soft delete)
 */
function deleteJustificacion($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM justificaciones WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Justificación no encontrada', 404);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Justificación eliminada exitosamente',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error al eliminar justificación: " . $e->getMessage());
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Obtener detalle de una justificación específica
 */
function getDetalleJustificacion($pdo) {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID de justificación requerido', 400);
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                j.*,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre_completo,
                est.NOMBRE as establecimiento_nombre
            FROM justificaciones j
            LEFT JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        $justificacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$justificacion) {
            throw new Exception('Justificación no encontrada', 404);
        }
        
        // Obtener log de cambios
        $stmt = $pdo->prepare("
            SELECT * FROM justificaciones_log 
            WHERE justificacion_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$id]);
        $log = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'justificacion' => $justificacion,
            'log' => $log,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error al obtener detalle de justificación: " . $e->getMessage());
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Obtener estadísticas del sistema
 */
function getEstadisticas($pdo) {
    try {
        // Estadísticas básicas
        $stats = [];
        
        // Total de justificaciones
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM justificaciones");
        $stats['total_justificaciones'] = $stmt->fetch()['total'];
        
        // Por estado
        $stmt = $pdo->query("
            SELECT estado, COUNT(*) as count 
            FROM justificaciones 
            WHERE 1=1 
            GROUP BY estado
        ");
        $stats['por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Del mes actual
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM justificaciones 
            WHERE MONTH(fecha_falta) = MONTH(NOW()) 
            AND YEAR(fecha_falta) = YEAR(NOW())
        ");
        $stats['mes_actual'] = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'estadisticas' => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()
        ]);
    }
}

/**
 * Función auxiliar para obtener valor de configuración
 */
function getConfigValue($pdo, $clave, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT valor, tipo FROM justificaciones_config WHERE clave = ?");
        $stmt->execute([$clave]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            return $default;
        }
        
        $valor = $config['valor'];
        
        switch ($config['tipo']) {
            case 'number':
                return (float) $valor;
            case 'boolean':
                return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($valor, true);
            default:
                return $valor;
        }
    } catch (Exception $e) {
        error_log("Error obteniendo configuración $clave: " . $e->getMessage());
        return $default;
    }
}
?>