<?php
/**
 * API para guardar datos biométricos
 * Guarda los datos biométricos (faciales o de huellas) en la base de datos
 */

// Cabeceras necesarias
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With");

// Conexión a la base de datos
require_once '../../config/database.php';

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST'
    ]);
    exit();
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Verificar datos requeridos
if (!isset($data['employee_id']) || !isset($data['biometric_type']) || !isset($data['biometric_data'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan datos requeridos (employee_id, biometric_type, biometric_data)'
    ]);
    exit();
}

// Validar ID del empleado
$employeeId = intval($data['employee_id']);
if ($employeeId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de empleado inválido'
    ]);
    exit();
}

// Validar tipo biométrico
$biometricType = $data['biometric_type'];
if (!in_array($biometricType, ['face', 'fingerprint'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo biométrico no válido. Use "face" o "fingerprint"'
    ]);
    exit();
}

// Validar datos biométricos
$biometricData = $data['biometric_data'];
if (empty($biometricData) || !is_array($biometricData)) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos biométricos inválidos o vacíos'
    ]);
    exit();
}

// Información adicional (opcional)
$additionalInfo = isset($data['additional_info']) ? json_encode($data['additional_info']) : null;

try {
    // Usar la conexión existente ($pdo) que se incluye de database.php
    
    // Primero, verificar si la tabla existe y crearla si no
    try {
        $tableCheckSQL = "SHOW TABLES LIKE 'employee_biometrics'";
        $tableCheck = $pdo->query($tableCheckSQL);
        $tableExists = ($tableCheck->rowCount() > 0);
        
        if (!$tableExists) {
            // Crear la tabla si no existe
            $createTableSQL = "CREATE TABLE `employee_biometrics` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `employee_id` int(11) NOT NULL,
                `biometric_type` varchar(50) NOT NULL COMMENT 'face, fingerprint, etc',
                `biometric_data` longtext NOT NULL COMMENT 'JSON encoded data',
                `additional_info` text DEFAULT NULL COMMENT 'JSON encoded additional info',
                `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `employee_id` (`employee_id`),
                KEY `biometric_type` (`biometric_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $pdo->exec($createTableSQL);
            error_log("Tabla employee_biometrics creada automáticamente");
        }
    } catch (Exception $e) {
        error_log("Error al verificar/crear tabla: " . $e->getMessage());
        // Continuar con la ejecución aunque haya fallado la verificación
    }
    
    // Verificar si ya existe un registro biométrico para este empleado y tipo
    $checkSql = "SELECT id FROM employee_biometrics WHERE employee_id = ? AND biometric_type = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$employeeId, $biometricType]);
    $existingRecord = $checkStmt->fetch();
    
    $biometricDataJson = json_encode($biometricData);
    
    if ($existingRecord) {
        // Actualizar registro existente
        $biometricId = $existingRecord['id'];
        
        $updateSql = "UPDATE employee_biometrics 
                     SET biometric_data = ?, 
                         additional_info = ?, 
                         updated_at = NOW() 
                     WHERE id = ?";
        
        $stmt = $pdo->prepare($updateSql);
        
        if ($stmt->execute([$biometricDataJson, $additionalInfo, $biometricId])) {
            echo json_encode([
                'success' => true,
                'message' => 'Datos biométricos actualizados correctamente',
                'biometric_id' => $biometricId
            ]);
        } else {
            throw new Exception("Error al actualizar datos biométricos");
        }
    } else {
        // Insertar nuevo registro
        $insertSql = "INSERT INTO employee_biometrics 
                     (employee_id, biometric_type, biometric_data, additional_info, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($insertSql);
        
        if ($stmt->execute([$employeeId, $biometricType, $biometricDataJson, $additionalInfo])) {
            $biometricId = $pdo->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Datos biométricos guardados correctamente',
                'biometric_id' => $biometricId
            ]);
        } else {
            throw new Exception("Error al guardar datos biométricos");
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
