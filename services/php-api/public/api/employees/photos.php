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
 * Employee Photo Management API
 * Handles employee photo upload, retrieval, and management
 */

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['PATH_INFO'] ?? '';
    
    // Route handling
    switch ($method) {
        case 'GET':
            if (preg_match('/^\/(\d+)\/photo$/', $path, $matches)) {
                // Get employee photo
                $employeeId = (int)$matches[1];
                $result = getEmployeePhoto($pdo, $employeeId);
            } elseif (preg_match('/^\/(\d+)$/', $path, $matches)) {
                // Get employee details
                $employeeId = (int)$matches[1];
                $result = getEmployeeDetails($pdo, $employeeId);
            } else {
                // Get all employees with photo status
                $params = $_GET;
                $result = getEmployeesWithPhotos($pdo, $params);
            }
            break;
            
        case 'POST':
            if (preg_match('/^\/(\d+)\/photo$/', $path, $matches)) {
                // Upload employee photo
                $employeeId = (int)$matches[1];
                $result = uploadEmployeePhoto($employeeId);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        case 'PUT':
            if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                // Update employee details
                $employeeId = (int)$matches[1];
                $input = json_decode(file_get_contents('php://input'), true);
                $result = updateEmployeeDetails($pdo, $employeeId, $input);
            } else {
                throw new Exception('Endpoint no encontrado');
            }
            break;
            
        case 'DELETE':
            if (preg_match('/^\/(\d+)\/photo$/', $path, $matches)) {
                // Delete employee photo
                $employeeId = (int)$matches[1];
                $result = deleteEmployeePhoto($pdo, $employeeId);
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
 * Get employee photo
 */
function getEmployeePhoto($pdo, $employeeId) {
    $stmt = $pdo->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO, FOTO_URL
        FROM empleado 
        WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'
    ");
    
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado');
    }
    
    return [
        'success' => true,
        'data' => [
            'employee_id' => $employee['ID_EMPLEADO'],
            'name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
            'photo_url' => $employee['FOTO_URL'],
            'has_photo' => !empty($employee['FOTO_URL'])
        ]
    ];
}

/**
 * Get employee details
 */
function getEmployeeDetails($pdo, $employeeId) {
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
            -- Biometric enrollment status
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'face' AND bd.ACTIVO = 'S' THEN 1 END) as FACIAL_ENROLLED,
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 'S' THEN 1 END) as FINGERPRINT_ENROLLED
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO
        WHERE e.ID_EMPLEADO = ? AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
        GROUP BY e.ID_EMPLEADO
    ");
    
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Get vacation balance
    $balanceStmt = $pdo->prepare("
        SELECT * FROM vw_employee_vacation_balance 
        WHERE ID_EMPLEADO = ?
    ");
    $balanceStmt->execute([$employeeId]);
    $vacationBalance = $balanceStmt->fetch();
    
    return [
        'success' => true,
        'data' => [
            'employee' => $employee,
            'vacation_balance' => $vacationBalance,
            'biometric_status' => [
                'facial_enrolled' => (int)$employee['FACIAL_ENROLLED'] > 0,
                'fingerprint_enrolled' => (int)$employee['FINGERPRINT_ENROLLED'] > 0
            ]
        ]
    ];
}

/**
 * Get all employees with photo status
 */
function getEmployeesWithPhotos($pdo, $params) {
    $limit = $params['limit'] ?? 50;
    $offset = $params['offset'] ?? 0;
    $search = $params['search'] ?? '';
    $hasPhoto = $params['has_photo'] ?? null;
    
    $whereClause = "WHERE e.ESTADO = 'A' AND e.ACTIVO = 'S'";
    $whereParams = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (e.NOMBRE LIKE ? OR e.APELLIDO LIKE ? OR e.DNI LIKE ?)";
        $searchParam = "%$search%";
        $whereParams[] = $searchParam;
        $whereParams[] = $searchParam;
        $whereParams[] = $searchParam;
    }
    
    if ($hasPhoto !== null) {
        if ($hasPhoto === '1' || $hasPhoto === 'true') {
            $whereClause .= " AND e.FOTO_URL IS NOT NULL AND e.FOTO_URL != ''";
        } else {
            $whereClause .= " AND (e.FOTO_URL IS NULL OR e.FOTO_URL = '')";
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            e.CORREO,
            e.TELEFONO,
            e.FECHA_INGRESO,
            e.FOTO_URL,
            est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
            CASE WHEN e.FOTO_URL IS NOT NULL AND e.FOTO_URL != '' THEN 1 ELSE 0 END as HAS_PHOTO,
            -- Biometric status
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'face' AND bd.ACTIVO = 'S' THEN 1 END) as FACIAL_ENROLLED,
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 'S' THEN 1 END) as FINGERPRINT_ENROLLED
        FROM empleado e
        LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO
        $whereClause
        GROUP BY e.ID_EMPLEADO
        ORDER BY e.NOMBRE, e.APELLIDO
        LIMIT ? OFFSET ?
    ");
    
    $whereParams[] = $limit;
    $whereParams[] = $offset;
    $stmt->execute($whereParams);
    $employees = $stmt->fetchAll();
    
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.ID_EMPLEADO) as total
        FROM empleado e
        $whereClause
    ");
    $countStmt->execute(array_slice($whereParams, 0, -2)); // Remove limit and offset
    $totalCount = $countStmt->fetch()['total'];
    
    return [
        'success' => true,
        'data' => $employees,
        'pagination' => [
            'total' => (int)$totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'count' => count($employees)
        ]
    ];
}

/**
 * Upload employee photo
 */
function uploadEmployeePhoto($employeeId) {
    // Validate employee exists
    global $pdo;
    $stmt = $pdo->prepare("SELECT ID_EMPLEADO FROM empleado WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'");
    $stmt->execute([$employeeId]);
    if (!$stmt->fetch()) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió el archivo de foto o hubo un error en la subida');
    }
    
    $file = $_FILES['photo'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Use JPG, PNG o GIF');
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../../uploads/employee_photos';
    if (!is_dir($uploadsDir)) {
        if (!mkdir($uploadsDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de uploads');
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "employee_{$employeeId}_" . time() . ".$extension";
    $filepath = $uploadsDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Resize image if needed
    resizeImage($filepath, 400, 400);
    
    // Update database with photo URL
    $photoUrl = "uploads/employee_photos/$filename";
    
    try {
        $pdo->beginTransaction();
        
        // Get current photo URL to delete old file
        $stmt = $pdo->prepare("SELECT FOTO_URL FROM empleado WHERE ID_EMPLEADO = ?");
        $stmt->execute([$employeeId]);
        $currentPhoto = $stmt->fetch();
        
        // Update with new photo URL
        $stmt = $pdo->prepare("UPDATE empleado SET FOTO_URL = ? WHERE ID_EMPLEADO = ?");
        $stmt->execute([$photoUrl, $employeeId]);
        
        $pdo->commit();
        
        // Delete old photo file if it exists
        if ($currentPhoto && !empty($currentPhoto['FOTO_URL'])) {
            $oldFilePath = __DIR__ . '/../../' . $currentPhoto['FOTO_URL'];
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Foto subida exitosamente',
            'data' => [
                'employee_id' => $employeeId,
                'photo_url' => $photoUrl,
                'filename' => $filename
            ]
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Delete uploaded file if database update failed
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        throw new Exception('Error al actualizar la base de datos: ' . $e->getMessage());
    }
}

/**
 * Update employee details
 */
function updateEmployeeDetails($pdo, $employeeId, $data) {
    // Check if employee exists
    $stmt = $pdo->prepare("SELECT ID_EMPLEADO FROM empleado WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'");
    $stmt->execute([$employeeId]);
    if (!$stmt->fetch()) {
        throw new Exception('Empleado no encontrado');
    }
    
    $updateFields = [];
    $updateValues = [];
    
    $allowedFields = ['NOMBRE', 'APELLIDO', 'DNI', 'CORREO', 'TELEFONO', 'ID_ESTABLECIMIENTO'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            $updateFields[] = "$field = ?";
            $updateValues[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    $updateValues[] = $employeeId;
    
    $stmt = $pdo->prepare("
        UPDATE empleado 
        SET " . implode(', ', $updateFields) . "
        WHERE ID_EMPLEADO = ?
    ");
    
    $stmt->execute($updateValues);
    
    return [
        'success' => true,
        'message' => 'Empleado actualizado exitosamente'
    ];
}

/**
 * Delete employee photo
 */
function deleteEmployeePhoto($pdo, $employeeId) {
    // Get current photo URL
    $stmt = $pdo->prepare("SELECT FOTO_URL FROM empleado WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado');
    }
    
    if (empty($employee['FOTO_URL'])) {
        throw new Exception('El empleado no tiene foto');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Remove photo URL from database
        $stmt = $pdo->prepare("UPDATE empleado SET FOTO_URL = NULL WHERE ID_EMPLEADO = ?");
        $stmt->execute([$employeeId]);
        
        $pdo->commit();
        
        // Delete physical file
        $filePath = __DIR__ . '/../../' . $employee['FOTO_URL'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        return [
            'success' => true,
            'message' => 'Foto eliminada exitosamente'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Error al eliminar la foto: ' . $e->getMessage());
    }
}

/**
 * Resize image to specified dimensions
 */
function resizeImage($filepath, $maxWidth, $maxHeight) {
    // Get image info
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) {
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $imageType = $imageInfo[2];
    
    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    
    // If image is already smaller, don't resize
    if ($ratio >= 1) {
        return true;
    }
    
    $newWidth = round($originalWidth * $ratio);
    $newHeight = round($originalHeight * $ratio);
    
    // Create image resource based on type
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($filepath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Save resized image
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($newImage, $filepath, 90);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($newImage, $filepath, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($newImage, $filepath);
            break;
        default:
            $result = false;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $result;
}
?>