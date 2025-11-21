<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Employee Vacation Management API
 * Handles vacation requests, approvals, and management
 */

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['PATH_INFO'] ?? '';
    
    // Parse request data
    $input = null;
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido');
        }
    }
    
    // Get query parameters
    $params = $_GET;
    
    // Route handling
    switch ($method) {
        case 'GET':
            if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                // Get specific vacation request
                $vacationId = (int)$matches[1];
                $result = getVacationRequest($pdo, $vacationId);
            } elseif ($path === '/employee') {
                // Get vacation requests for specific employee
                $employeeId = $params['employee_id'] ?? null;
                if (!$employeeId) {
                    throw new Exception('employee_id requerido');
                }
                $result = getEmployeeVacations($pdo, $employeeId, $params);
            } elseif ($path === '/pending') {
                // Get pending vacation requests (for managers)
                $result = getPendingVacations($pdo, $params);
            } elseif ($path === '/balance') {
                // Get vacation balance for employee
                $employeeId = $params['employee_id'] ?? null;
                if (!$employeeId) {
                    throw new Exception('employee_id requerido');
                }
                $result = getVacationBalance($pdo, $employeeId);
            } else {
                // Get all vacation requests (filtered)
                $result = getAllVacations($pdo, $params);
            }
            break;
            
        case 'POST':
            if ($path === '') {
                // Create new vacation request
                $result = createVacationRequest($pdo, $input);
            } elseif (preg_match('/^\/(\d+)\/approve$/', $path, $matches)) {
                // Approve vacation request
                $vacationId = (int)$matches[1];
                $result = approveVacationRequest($pdo, $vacationId, $input);
            } elseif (preg_match('/^\/(\d+)\/reject$/', $path, $matches)) {
                // Reject vacation request
                $vacationId = (int)$matches[1];
                $result = rejectVacationRequest($pdo, $vacationId, $input);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        case 'PUT':
            if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                // Update vacation request
                $vacationId = (int)$matches[1];
                $result = updateVacationRequest($pdo, $vacationId, $input);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        case 'DELETE':
            if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                // Cancel vacation request
                $vacationId = (int)$matches[1];
                $result = cancelVacationRequest($pdo, $vacationId);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        default:
            throw new Exception('Método no permitido');
    }
    
    http_response_code(200);
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Get specific vacation request
 */
function getVacationRequest($pdo, $vacationId) {
    $stmt = $pdo->prepare("
        SELECT 
            ev.*,
            e.NOMBRE, e.APELLIDO, e.DNI,
            u.NOMBRE_COMPLETO as APROBADO_POR_NOMBRE
        FROM employee_vacations ev
        JOIN empleado e ON ev.ID_EMPLEADO = e.ID_EMPLEADO
        LEFT JOIN usuario u ON ev.APROBADO_POR = u.ID_USUARIO
        WHERE ev.ID = ? AND ev.ACTIVO = 1
    ");
    
    $stmt->execute([$vacationId]);
    $vacation = $stmt->fetch();
    
    if (!$vacation) {
        throw new Exception('Solicitud de vacaciones no encontrada');
    }
    
    return [
        'success' => true,
        'data' => $vacation
    ];
}

/**
 * Get vacation requests for specific employee
 */
function getEmployeeVacations($pdo, $employeeId, $params) {
    $year = $params['year'] ?? date('Y');
    $status = $params['status'] ?? null;
    
    $whereClause = "WHERE ev.ID_EMPLEADO = ? AND ev.ACTIVO = 1";
    $whereParams = [$employeeId];
    
    if ($year) {
        $whereClause .= " AND YEAR(ev.FECHA_INICIO) = ?";
        $whereParams[] = $year;
    }
    
    if ($status) {
        $whereClause .= " AND ev.ESTADO = ?";
        $whereParams[] = $status;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            ev.*,
            u.NOMBRE_COMPLETO as APROBADO_POR_NOMBRE
        FROM employee_vacations ev
        LEFT JOIN usuario u ON ev.APROBADO_POR = u.ID_USUARIO
        $whereClause
        ORDER BY ev.FECHA_SOLICITUD DESC
    ");
    
    $stmt->execute($whereParams);
    $vacations = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $vacations,
        'count' => count($vacations)
    ];
}

/**
 * Get pending vacation requests for approval
 */
function getPendingVacations($pdo, $params) {
    $limit = $params['limit'] ?? 50;
    $offset = $params['offset'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT 
            ev.*,
            e.NOMBRE, e.APELLIDO, e.DNI,
            DATEDIFF(ev.FECHA_INICIO, CURDATE()) as DIAS_HASTA_INICIO
        FROM employee_vacations ev
        JOIN empleado e ON ev.ID_EMPLEADO = e.ID_EMPLEADO
        WHERE ev.ESTADO = 'pending' AND ev.ACTIVO = 1
        ORDER BY ev.FECHA_SOLICITUD ASC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$limit, $offset]);
    $vacations = $stmt->fetchAll();
    
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM employee_vacations ev
        WHERE ev.ESTADO = 'pending' AND ev.ACTIVO = 1
    ");
    $countStmt->execute();
    $totalCount = $countStmt->fetch()['total'];
    
    return [
        'success' => true,
        'data' => $vacations,
        'pagination' => [
            'total' => (int)$totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'count' => count($vacations)
        ]
    ];
}

/**
 * Get all vacation requests with filters
 */
function getAllVacations($pdo, $params) {
    $limit = $params['limit'] ?? 50;
    $offset = $params['offset'] ?? 0;
    $status = $params['status'] ?? null;
    $employeeId = $params['employee_id'] ?? null;
    $year = $params['year'] ?? null;
    $tipo = $params['tipo'] ?? null;
    
    $whereClause = "WHERE ev.ACTIVO = 1";
    $whereParams = [];
    
    if ($status) {
        $whereClause .= " AND ev.ESTADO = ?";
        $whereParams[] = $status;
    }
    
    if ($employeeId) {
        $whereClause .= " AND ev.ID_EMPLEADO = ?";
        $whereParams[] = $employeeId;
    }
    
    if ($year) {
        $whereClause .= " AND YEAR(ev.FECHA_INICIO) = ?";
        $whereParams[] = $year;
    }
    
    if ($tipo) {
        $whereClause .= " AND ev.TIPO_VACACION = ?";
        $whereParams[] = $tipo;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            ev.*,
            e.NOMBRE, e.APELLIDO, e.DNI,
            u.NOMBRE_COMPLETO as APROBADO_POR_NOMBRE
        FROM employee_vacations ev
        JOIN empleado e ON ev.ID_EMPLEADO = e.ID_EMPLEADO
        LEFT JOIN usuario u ON ev.APROBADO_POR = u.ID_USUARIO
        $whereClause
        ORDER BY ev.FECHA_SOLICITUD DESC
        LIMIT ? OFFSET ?
    ");
    
    $whereParams[] = $limit;
    $whereParams[] = $offset;
    $stmt->execute($whereParams);
    $vacations = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $vacations,
        'pagination' => [
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'count' => count($vacations)
        ]
    ];
}

/**
 * Get vacation balance for employee
 */
function getVacationBalance($pdo, $employeeId) {
    $stmt = $pdo->prepare("
        SELECT * FROM vw_employee_vacation_balance 
        WHERE ID_EMPLEADO = ?
    ");
    
    $stmt->execute([$employeeId]);
    $balance = $stmt->fetch();
    
    if (!$balance) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Calculate remaining days
    $balance['DIAS_DISPONIBLES'] = max(0, $balance['DIAS_ANUALES'] - $balance['DIAS_USADOS'] - $balance['DIAS_PENDIENTES']);
    
    return [
        'success' => true,
        'data' => $balance
    ];
}

/**
 * Create new vacation request
 */
function createVacationRequest($pdo, $data) {
    $requiredFields = ['ID_EMPLEADO', 'FECHA_INICIO', 'FECHA_FIN', 'TIPO_VACACION', 'MOTIVO'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo requerido: $field");
        }
    }
    
    // Validate employee exists
    $stmt = $pdo->prepare("SELECT ID_EMPLEADO FROM empleado WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'");
    $stmt->execute([$data['ID_EMPLEADO']]);
    if (!$stmt->fetch()) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Validate dates
    $fechaInicio = new DateTime($data['FECHA_INICIO']);
    $fechaFin = new DateTime($data['FECHA_FIN']);
    $today = new DateTime();
    
    if ($fechaInicio <= $today) {
        throw new Exception('La fecha de inicio debe ser posterior a hoy');
    }
    
    if ($fechaFin <= $fechaInicio) {
        throw new Exception('La fecha de fin debe ser posterior a la fecha de inicio');
    }
    
    // Calculate working days
    $diasSolicitados = $fechaInicio->diff($fechaFin)->days + 1;
    $diasHabiles = calculateWorkingDays($fechaInicio, $fechaFin);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO employee_vacations 
            (ID_EMPLEADO, FECHA_INICIO, FECHA_FIN, TIPO_VACACION, DIAS_SOLICITADOS, 
             DIAS_HABILES, MOTIVO, ARCHIVO_ADJUNTO, ESTADO, ACTIVO)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1)
        ");
        
        $stmt->execute([
            $data['ID_EMPLEADO'],
            $data['FECHA_INICIO'],
            $data['FECHA_FIN'],
            $data['TIPO_VACACION'],
            $diasSolicitados,
            $diasHabiles,
            $data['MOTIVO'],
            $data['ARCHIVO_ADJUNTO'] ?? null
        ]);
        
        $vacationId = $pdo->lastInsertId();
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Solicitud de vacaciones creada exitosamente',
            'vacation_id' => $vacationId,
            'dias_solicitados' => $diasSolicitados,
            'dias_habiles' => $diasHabiles
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error al crear solicitud: ' . $e->getMessage());
    }
}

/**
 * Update vacation request
 */
function updateVacationRequest($pdo, $vacationId, $data) {
    // Check if vacation exists and is editable
    $stmt = $pdo->prepare("
        SELECT * FROM employee_vacations 
        WHERE ID = ? AND ESTADO = 'pending' AND ACTIVO = 1
    ");
    $stmt->execute([$vacationId]);
    $vacation = $stmt->fetch();
    
    if (!$vacation) {
        throw new Exception('Solicitud no encontrada o no se puede editar');
    }
    
    $updateFields = [];
    $updateValues = [];
    
    if (isset($data['FECHA_INICIO'])) {
        $updateFields[] = "FECHA_INICIO = ?";
        $updateValues[] = $data['FECHA_INICIO'];
    }
    
    if (isset($data['FECHA_FIN'])) {
        $updateFields[] = "FECHA_FIN = ?";
        $updateValues[] = $data['FECHA_FIN'];
    }
    
    if (isset($data['TIPO_VACACION'])) {
        $updateFields[] = "TIPO_VACACION = ?";
        $updateValues[] = $data['TIPO_VACACION'];
    }
    
    if (isset($data['MOTIVO'])) {
        $updateFields[] = "MOTIVO = ?";
        $updateValues[] = $data['MOTIVO'];
    }
    
    if (empty($updateFields)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    $updateFields[] = "UPDATED_AT = NOW()";
    $updateValues[] = $vacationId;
    
    $stmt = $pdo->prepare("
        UPDATE employee_vacations 
        SET " . implode(', ', $updateFields) . "
        WHERE ID = ?
    ");
    
    $stmt->execute($updateValues);
    
    return [
        'success' => true,
        'message' => 'Solicitud actualizada exitosamente'
    ];
}

/**
 * Approve vacation request
 */
function approveVacationRequest($pdo, $vacationId, $data) {
    return processVacationApproval($pdo, $vacationId, 'approved', $data);
}

/**
 * Reject vacation request
 */
function rejectVacationRequest($pdo, $vacationId, $data) {
    return processVacationApproval($pdo, $vacationId, 'rejected', $data);
}

/**
 * Process vacation approval/rejection
 */
function processVacationApproval($pdo, $vacationId, $action, $data) {
    // Check if vacation exists and is pending
    $stmt = $pdo->prepare("
        SELECT * FROM employee_vacations 
        WHERE ID = ? AND ESTADO = 'pending' AND ACTIVO = 1
    ");
    $stmt->execute([$vacationId]);
    $vacation = $stmt->fetch();
    
    if (!$vacation) {
        throw new Exception('Solicitud no encontrada o ya procesada');
    }
    
    // Get current user (approver)
    $approverId = $_SESSION['user_id'] ?? null;
    if (!$approverId) {
        throw new Exception('Usuario no autenticado');
    }
    
    $comments = $data['comentarios'] ?? '';
    
    $stmt = $pdo->prepare("
        UPDATE employee_vacations 
        SET ESTADO = ?, 
            APROBADO_POR = ?,
            FECHA_APROBACION = NOW(),
            COMENTARIOS_APROBACION = ?,
            UPDATED_AT = NOW()
        WHERE ID = ?
    ");
    
    $stmt->execute([$action, $approverId, $comments, $vacationId]);
    
    $actionText = $action === 'approved' ? 'aprobada' : 'rechazada';
    
    return [
        'success' => true,
        'message' => "Solicitud $actionText exitosamente",
        'vacation_id' => $vacationId,
        'action' => $action
    ];
}

/**
 * Cancel vacation request
 */
function cancelVacationRequest($pdo, $vacationId) {
    $stmt = $pdo->prepare("
        UPDATE employee_vacations 
        SET ESTADO = 'cancelled', UPDATED_AT = NOW()
        WHERE ID = ? AND ESTADO IN ('pending', 'approved') AND ACTIVO = 1
    ");
    
    $stmt->execute([$vacationId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Solicitud no encontrada o no se puede cancelar');
    }
    
    return [
        'success' => true,
        'message' => 'Solicitud cancelada exitosamente'
    ];
}

/**
 * Calculate working days between two dates
 */
function calculateWorkingDays($startDate, $endDate) {
    $workingDays = 0;
    $currentDate = clone $startDate;
    
    while ($currentDate <= $endDate) {
        $dayOfWeek = $currentDate->format('N'); // 1 = Monday, 7 = Sunday
        
        // Count Monday through Friday as working days
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $workingDays++;
        }
        
        $currentDate->add(new DateInterval('P1D'));
    }
    
    return $workingDays;
}
?>