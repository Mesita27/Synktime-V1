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
 * User Administration API with RBAC
 * Manages users, roles, and permissions
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
            if ($path === '/users') {
                $result = getUsers($pdo, $params);
            } elseif (preg_match('/^\/users\/(\d+)$/', $path, $matches)) {
                $userId = (int)$matches[1];
                $result = getUserDetails($pdo, $userId);
            } elseif ($path === '/roles') {
                $result = getRoles($pdo, $params);
            } elseif (preg_match('/^\/roles\/(\d+)$/', $path, $matches)) {
                $roleId = (int)$matches[1];
                $result = getRoleDetails($pdo, $roleId);
            } elseif ($path === '/permissions') {
                $result = getPermissions($pdo, $params);
            } elseif (preg_match('/^\/users\/(\d+)\/permissions$/', $path, $matches)) {
                $userId = (int)$matches[1];
                $result = getUserPermissions($pdo, $userId);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        case 'POST':
            if ($path === '/users') {
                $result = createUser($pdo, $input);
            } elseif ($path === '/roles') {
                $result = createRole($pdo, $input);
            } elseif (preg_match('/^\/users\/(\d+)\/roles$/', $path, $matches)) {
                $userId = (int)$matches[1];
                $result = assignUserRole($pdo, $userId, $input);
            } elseif (preg_match('/^\/roles\/(\d+)\/permissions$/', $path, $matches)) {
                $roleId = (int)$matches[1];
                $result = assignRolePermissions($pdo, $roleId, $input);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        case 'PUT':
            if (preg_match('/^\/users\/(\d+)$/', $path, $matches)) {
                $userId = (int)$matches[1];
                $result = updateUser($pdo, $userId, $input);
            } elseif (preg_match('/^\/roles\/(\d+)$/', $path, $matches)) {
                $roleId = (int)$matches[1];
                $result = updateRole($pdo, $roleId, $input);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        case 'DELETE':
            if (preg_match('/^\/users\/(\d+)$/', $path, $matches)) {
                $userId = (int)$matches[1];
                $result = deleteUser($pdo, $userId);
            } elseif (preg_match('/^\/roles\/(\d+)$/', $path, $matches)) {
                $roleId = (int)$matches[1];
                $result = deleteRole($pdo, $roleId);
            } elseif (preg_match('/^\/users\/(\d+)\/roles\/(\d+)$/', $path, $matches)) {
                $userId = (int)$matches[1];
                $roleId = (int)$matches[2];
                $result = removeUserRole($pdo, $userId, $roleId);
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
 * Get users list
 */
function getUsers($pdo, $params) {
    $limit = $params['limit'] ?? 50;
    $offset = $params['offset'] ?? 0;
    $search = $params['search'] ?? '';
    $role = $params['role'] ?? '';
    $estado = $params['estado'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $whereParams = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (u.USERNAME LIKE ? OR u.NOMBRE_COMPLETO LIKE ? OR u.EMAIL LIKE ?)";
        $searchParam = "%$search%";
        $whereParams[] = $searchParam;
        $whereParams[] = $searchParam;
        $whereParams[] = $searchParam;
    }
    
    if (!empty($role)) {
        $whereClause .= " AND r.NOMBRE = ?";
        $whereParams[] = $role;
    }
    
    if (!empty($estado)) {
        $whereClause .= " AND u.ESTADO = ?";
        $whereParams[] = $estado;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            u.ID_USUARIO,
            u.USERNAME,
            u.NOMBRE_COMPLETO,
            u.EMAIL,
            u.ESTADO,
            u.ROL as OLD_ROL,
            GROUP_CONCAT(r.NOMBRE) as ROLES,
            COUNT(ur.ID_ROL) as TOTAL_ROLES
        FROM usuario u
        LEFT JOIN user_roles ur ON u.ID_USUARIO = ur.ID_USUARIO AND ur.ACTIVO = 1
        LEFT JOIN roles r ON ur.ID_ROL = r.ID AND r.ACTIVO = 1
        $whereClause
        GROUP BY u.ID_USUARIO
        ORDER BY u.NOMBRE_COMPLETO
        LIMIT ? OFFSET ?
    ");
    
    $whereParams[] = $limit;
    $whereParams[] = $offset;
    $stmt->execute($whereParams);
    $users = $stmt->fetchAll();
    
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT u.ID_USUARIO) as total
        FROM usuario u
        LEFT JOIN user_roles ur ON u.ID_USUARIO = ur.ID_USUARIO AND ur.ACTIVO = 1
        LEFT JOIN roles r ON ur.ID_ROL = r.ID AND r.ACTIVO = 1
        $whereClause
    ");
    $countStmt->execute(array_slice($whereParams, 0, -2)); // Remove limit and offset
    $totalCount = $countStmt->fetch()['total'];
    
    return [
        'success' => true,
        'data' => $users,
        'pagination' => [
            'total' => (int)$totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'count' => count($users)
        ]
    ];
}

/**
 * Get user details with roles and permissions
 */
function getUserDetails($pdo, $userId) {
    // Get user basic info
    $stmt = $pdo->prepare("
        SELECT * FROM usuario WHERE ID_USUARIO = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Get user roles
    $stmt = $pdo->prepare("
        SELECT 
            r.ID,
            r.NOMBRE,
            r.DESCRIPCION,
            r.NIVEL_ACCESO,
            ur.FECHA_ASIGNACION,
            ua.NOMBRE_COMPLETO as ASIGNADO_POR_NOMBRE
        FROM user_roles ur
        JOIN roles r ON ur.ID_ROL = r.ID
        LEFT JOIN usuario ua ON ur.ASIGNADO_POR = ua.ID_USUARIO
        WHERE ur.ID_USUARIO = ? AND ur.ACTIVO = 1 AND r.ACTIVO = 1
        ORDER BY r.NIVEL_ACCESO DESC
    ");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll();
    
    // Get effective permissions
    $permissions = getUserPermissions($pdo, $userId);
    
    // Remove password from response
    unset($user['CONTRASENA']);
    
    return [
        'success' => true,
        'data' => [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions['data']
        ]
    ];
}

/**
 * Get all roles
 */
function getRoles($pdo, $params) {
    $includeInactive = $params['include_inactive'] ?? false;
    
    $whereClause = $includeInactive ? "" : "WHERE ACTIVO = 1";
    
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            COUNT(ur.ID_USUARIO) as USERS_COUNT,
            COUNT(rp.ID_PERMISO) as PERMISSIONS_COUNT
        FROM roles r
        LEFT JOIN user_roles ur ON r.ID = ur.ID_ROL AND ur.ACTIVO = 1
        LEFT JOIN role_permissions rp ON r.ID = rp.ID_ROL
        $whereClause
        GROUP BY r.ID
        ORDER BY r.NIVEL_ACCESO DESC, r.NOMBRE
    ");
    
    $stmt->execute();
    $roles = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $roles
    ];
}

/**
 * Get role details with permissions
 */
function getRoleDetails($pdo, $roleId) {
    // Get role basic info
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE ID = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch();
    
    if (!$role) {
        throw new Exception('Rol no encontrado');
    }
    
    // Get role permissions
    $stmt = $pdo->prepare("
        SELECT 
            p.ID,
            p.NOMBRE,
            p.DESCRIPCION,
            p.MODULO,
            p.ACCION,
            p.RECURSO
        FROM role_permissions rp
        JOIN permissions p ON rp.ID_PERMISO = p.ID
        WHERE rp.ID_ROL = ? AND p.ACTIVO = 1
        ORDER BY p.MODULO, p.ACCION, p.RECURSO
    ");
    $stmt->execute([$roleId]);
    $permissions = $stmt->fetchAll();
    
    // Get users with this role
    $stmt = $pdo->prepare("
        SELECT 
            u.ID_USUARIO,
            u.USERNAME,
            u.NOMBRE_COMPLETO,
            ur.FECHA_ASIGNACION
        FROM user_roles ur
        JOIN usuario u ON ur.ID_USUARIO = u.ID_USUARIO
        WHERE ur.ID_ROL = ? AND ur.ACTIVO = 1
        ORDER BY u.NOMBRE_COMPLETO
    ");
    $stmt->execute([$roleId]);
    $users = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => [
            'role' => $role,
            'permissions' => $permissions,
            'users' => $users
        ]
    ];
}

/**
 * Get all permissions
 */
function getPermissions($pdo, $params) {
    $modulo = $params['modulo'] ?? '';
    
    $whereClause = "WHERE ACTIVO = 1";
    $whereParams = [];
    
    if (!empty($modulo)) {
        $whereClause .= " AND MODULO = ?";
        $whereParams[] = $modulo;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            COUNT(rp.ID_ROL) as ROLES_COUNT
        FROM permissions p
        LEFT JOIN role_permissions rp ON p.ID = rp.ID_PERMISO
        $whereClause
        GROUP BY p.ID
        ORDER BY p.MODULO, p.ACCION, p.RECURSO
    ");
    
    $stmt->execute($whereParams);
    $permissions = $stmt->fetchAll();
    
    // Group by module for easier handling
    $groupedPermissions = [];
    foreach ($permissions as $permission) {
        $modulo = $permission['MODULO'];
        if (!isset($groupedPermissions[$modulo])) {
            $groupedPermissions[$modulo] = [];
        }
        $groupedPermissions[$modulo][] = $permission;
    }
    
    return [
        'success' => true,
        'data' => [
            'permissions' => $permissions,
            'grouped' => $groupedPermissions
        ]
    ];
}

/**
 * Get effective permissions for user
 */
function getUserPermissions($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            p.ID,
            p.NOMBRE,
            p.DESCRIPCION,
            p.MODULO,
            p.ACCION,
            p.RECURSO,
            r.NOMBRE as GRANTED_BY_ROLE
        FROM user_roles ur
        JOIN roles r ON ur.ID_ROL = r.ID
        JOIN role_permissions rp ON r.ID = rp.ID_ROL
        JOIN permissions p ON rp.ID_PERMISO = p.ID
        WHERE ur.ID_USUARIO = ? 
        AND ur.ACTIVO = 1 
        AND r.ACTIVO = 1 
        AND p.ACTIVO = 1
        ORDER BY p.MODULO, p.ACCION, p.RECURSO
    ");
    
    $stmt->execute([$userId]);
    $permissions = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $permissions
    ];
}

/**
 * Create new user
 */
function createUser($pdo, $data) {
    $requiredFields = ['USERNAME', 'CONTRASENA', 'NOMBRE_COMPLETO', 'EMAIL'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo requerido: $field");
        }
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE USERNAME = ?");
    $stmt->execute([$data['USERNAME']]);
    if ($stmt->fetch()) {
        throw new Exception('El nombre de usuario ya existe');
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE EMAIL = ?");
    $stmt->execute([$data['EMAIL']]);
    if ($stmt->fetch()) {
        throw new Exception('El correo electrónico ya está registrado');
    }
    
    // Hash password
    $hashedPassword = password_hash($data['CONTRASENA'], PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO usuario 
            (USERNAME, CONTRASENA, NOMBRE_COMPLETO, EMAIL, ROL, ID_EMPRESA, ESTADO)
            VALUES (?, ?, ?, ?, ?, ?, 'A')
        ");
        
        $stmt->execute([
            $data['USERNAME'],
            $hashedPassword,
            $data['NOMBRE_COMPLETO'],
            $data['EMAIL'],
            $data['ROL'] ?? 'Empleado', // Keep legacy ROL field
            $data['ID_EMPRESA'] ?? 1
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Assign default role if specified
        if (!empty($data['default_role_id'])) {
            $roleStmt = $pdo->prepare("
                INSERT INTO user_roles (ID_USUARIO, ID_ROL, ASIGNADO_POR, ACTIVO)
                VALUES (?, ?, ?, 1)
            ");
            $roleStmt->execute([$userId, $data['default_role_id'], $_SESSION['user_id'] ?? 1]);
        } else {
            // Assign default "Empleado" role
            $roleStmt = $pdo->prepare("
                INSERT INTO user_roles (ID_USUARIO, ID_ROL, ASIGNADO_POR, ACTIVO)
                SELECT ?, r.ID, ?, 1
                FROM roles r 
                WHERE r.NOMBRE = 'Empleado' AND r.ACTIVO = 1
                LIMIT 1
            ");
            $roleStmt->execute([$userId, $_SESSION['user_id'] ?? 1]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user_id' => $userId
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error al crear usuario: ' . $e->getMessage());
    }
}

/**
 * Update user
 */
function updateUser($pdo, $userId, $data) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE ID_USUARIO = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('Usuario no encontrado');
    }
    
    $updateFields = [];
    $updateValues = [];
    
    $allowedFields = ['USERNAME', 'NOMBRE_COMPLETO', 'EMAIL', 'ESTADO'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            // Check for uniqueness on username and email
            if ($field === 'USERNAME') {
                $checkStmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE USERNAME = ? AND ID_USUARIO != ?");
                $checkStmt->execute([$data[$field], $userId]);
                if ($checkStmt->fetch()) {
                    throw new Exception('El nombre de usuario ya existe');
                }
            }
            
            if ($field === 'EMAIL') {
                $checkStmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE EMAIL = ? AND ID_USUARIO != ?");
                $checkStmt->execute([$data[$field], $userId]);
                if ($checkStmt->fetch()) {
                    throw new Exception('El correo electrónico ya está registrado');
                }
            }
            
            $updateFields[] = "$field = ?";
            $updateValues[] = $data[$field];
        }
    }
    
    // Handle password update separately
    if (!empty($data['new_password'])) {
        $updateFields[] = "CONTRASENA = ?";
        $updateValues[] = password_hash($data['new_password'], PASSWORD_DEFAULT);
    }
    
    if (empty($updateFields)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    $updateValues[] = $userId;
    
    $stmt = $pdo->prepare("
        UPDATE usuario 
        SET " . implode(', ', $updateFields) . "
        WHERE ID_USUARIO = ?
    ");
    
    $stmt->execute($updateValues);
    
    return [
        'success' => true,
        'message' => 'Usuario actualizado exitosamente'
    ];
}

/**
 * Assign role to user
 */
function assignUserRole($pdo, $userId, $data) {
    $roleId = $data['role_id'] ?? null;
    
    if (!$roleId) {
        throw new Exception('role_id requerido');
    }
    
    // Check if user and role exist
    $stmt = $pdo->prepare("SELECT ID_USUARIO FROM usuario WHERE ID_USUARIO = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('Usuario no encontrado');
    }
    
    $stmt = $pdo->prepare("SELECT ID FROM roles WHERE ID = ? AND ACTIVO = 1");
    $stmt->execute([$roleId]);
    if (!$stmt->fetch()) {
        throw new Exception('Rol no encontrado');
    }
    
    // Check if assignment already exists
    $stmt = $pdo->prepare("SELECT ID FROM user_roles WHERE ID_USUARIO = ? AND ID_ROL = ? AND ACTIVO = 1");
    $stmt->execute([$userId, $roleId]);
    if ($stmt->fetch()) {
        throw new Exception('El usuario ya tiene este rol asignado');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (ID_USUARIO, ID_ROL, ASIGNADO_POR, ACTIVO)
        VALUES (?, ?, ?, 1)
    ");
    
    $stmt->execute([$userId, $roleId, $_SESSION['user_id'] ?? 1]);
    
    return [
        'success' => true,
        'message' => 'Rol asignado exitosamente'
    ];
}

/**
 * Remove role from user
 */
function removeUserRole($pdo, $userId, $roleId) {
    $stmt = $pdo->prepare("
        UPDATE user_roles 
        SET ACTIVO = 0
        WHERE ID_USUARIO = ? AND ID_ROL = ? AND ACTIVO = 1
    ");
    
    $stmt->execute([$userId, $roleId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Asignación de rol no encontrada');
    }
    
    return [
        'success' => true,
        'message' => 'Rol removido exitosamente'
    ];
}

/**
 * Delete user (deactivate)
 */
function deleteUser($pdo, $userId) {
    $stmt = $pdo->prepare("
        UPDATE usuario 
        SET ESTADO = 'I'
        WHERE ID_USUARIO = ?
    ");
    
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Also deactivate user roles
    $stmt = $pdo->prepare("
        UPDATE user_roles 
        SET ACTIVO = 0
        WHERE ID_USUARIO = ?
    ");
    
    $stmt->execute([$userId]);
    
    return [
        'success' => true,
        'message' => 'Usuario desactivado exitosamente'
    ];
}

/**
 * Create new role
 */
function createRole($pdo, $data) {
    $requiredFields = ['NOMBRE', 'DESCRIPCION', 'NIVEL_ACCESO'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo requerido: $field");
        }
    }
    
    // Check if role name already exists
    $stmt = $pdo->prepare("SELECT ID FROM roles WHERE NOMBRE = ?");
    $stmt->execute([$data['NOMBRE']]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un rol con este nombre');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO roles (NOMBRE, DESCRIPCION, NIVEL_ACCESO, ACTIVO)
        VALUES (?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $data['NOMBRE'],
        $data['DESCRIPCION'],
        $data['NIVEL_ACCESO']
    ]);
    
    $roleId = $pdo->lastInsertId();
    
    return [
        'success' => true,
        'message' => 'Rol creado exitosamente',
        'role_id' => $roleId
    ];
}

/**
 * Update role
 */
function updateRole($pdo, $roleId, $data) {
    // Don't allow updating system roles
    $stmt = $pdo->prepare("SELECT NOMBRE FROM roles WHERE ID = ? AND NIVEL_ACCESO >= 4");
    $stmt->execute([$roleId]);
    if ($stmt->fetch()) {
        throw new Exception('No se pueden modificar roles del sistema');
    }
    
    $updateFields = [];
    $updateValues = [];
    
    $allowedFields = ['NOMBRE', 'DESCRIPCION', 'NIVEL_ACCESO'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            $updateFields[] = "$field = ?";
            $updateValues[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    $updateValues[] = $roleId;
    
    $stmt = $pdo->prepare("
        UPDATE roles 
        SET " . implode(', ', $updateFields) . "
        WHERE ID = ?
    ");
    
    $stmt->execute($updateValues);
    
    return [
        'success' => true,
        'message' => 'Rol actualizado exitosamente'
    ];
}

/**
 * Delete role (deactivate)
 */
function deleteRole($pdo, $roleId) {
    // Don't allow deleting system roles
    $stmt = $pdo->prepare("SELECT NOMBRE FROM roles WHERE ID = ? AND NIVEL_ACCESO >= 4");
    $stmt->execute([$roleId]);
    if ($stmt->fetch()) {
        throw new Exception('No se pueden eliminar roles del sistema');
    }
    
    $stmt = $pdo->prepare("
        UPDATE roles 
        SET ACTIVO = 0
        WHERE ID = ?
    ");
    
    $stmt->execute([$roleId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Rol no encontrado');
    }
    
    // Also deactivate role assignments
    $stmt = $pdo->prepare("
        UPDATE user_roles 
        SET ACTIVO = 0
        WHERE ID_ROL = ?
    ");
    
    $stmt->execute([$roleId]);
    
    return [
        'success' => true,
        'message' => 'Rol desactivado exitosamente'
    ];
}

/**
 * Assign permissions to role
 */
function assignRolePermissions($pdo, $roleId, $data) {
    $permissionIds = $data['permission_ids'] ?? [];
    
    if (empty($permissionIds) || !is_array($permissionIds)) {
        throw new Exception('permission_ids requerido como array');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Remove existing permissions for this role
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE ID_ROL = ?");
        $stmt->execute([$roleId]);
        
        // Add new permissions
        $stmt = $pdo->prepare("
            INSERT INTO role_permissions (ID_ROL, ID_PERMISO)
            VALUES (?, ?)
        ");
        
        foreach ($permissionIds as $permissionId) {
            $stmt->execute([$roleId, $permissionId]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Permisos asignados exitosamente',
            'assigned_permissions' => count($permissionIds)
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error al asignar permisos: ' . $e->getMessage());
    }
}
?>