<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

// Headers para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permite POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Verificar autenticación y obtener empresa del usuario
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        throw new Exception('Usuario no autenticado');
    }
    
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        throw new Exception('Sesión inválida - empresa no encontrada');
    }
    
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos inválidos');
    }
    
    // Validar campos requeridos
    if (empty($input['image_data'])) {
        throw new Exception('Imagen requerida');
    }
    
    $imageData = $input['image_data'];
    $confidenceThreshold = $input['confidence_threshold'] ?? 0.80; // Umbral balanceado por defecto
    
    // Configurar datos para el servicio Python
    $pythonServiceBase = rtrim(
        synktime_env(
            'PYTHON_SERVICE_URL',
            synktime_env('PY_SERVICE_URL', 'http://127.0.0.1:8000')
        ),
        '/'
    );
    $pythonServiceUrl = $pythonServiceBase . '/facial/identify';
    
    $requestData = [
        'image_data' => $imageData,
        'company_id' => (int)$empresaId,
        'confidence_threshold' => (float)$confidenceThreshold
    ];
    
    // Llamar al servicio Python
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $pythonServiceUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Error conectando al servicio Python: $error");
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['detail'] ?? 'Error en el servicio de reconocimiento facial';
        throw new Exception($errorMessage);
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        throw new Exception('Respuesta inválida del servicio Python');
    }
    
    // Formatear respuesta para el frontend
    $responseData = [
        'success' => $result['success'],
        'message' => $result['message'],
        'confidence' => $result['confidence'] ?? 0.0,
        'processing_time_ms' => $result['processing_time_ms'] ?? 0
    ];
    
    // Si se identificó un empleado, agregar sus datos
    if ($result['success'] && isset($result['employee_id'])) {
        $responseData['employee'] = [
            'ID_EMPLEADO' => $result['employee_id'],
            'NOMBRE_COMPLETO' => $result['employee_name'],
            'DNI' => $result['dni'] ?? '',
            'ESTABLECIMIENTO' => $result['establishment_name'] ?? '',
            'ID_ESTABLECIMIENTO' => $result['establishment_id'] ?? 0
        ];
        
        // Agregar información adicional del empleado desde la base de datos
        try {
            $stmt = $conn->prepare("
                SELECT 
                    e.ID_EMPLEADO,
                    e.NOMBRE,
                    e.APELLIDO,
                    e.DNI,
                    e.CORREO,
                    e.TELEFONO,
                    e.ID_ESTABLECIMIENTO,
                    est.NOMBRE as ESTABLECIMIENTO
                FROM empleado e
                JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                JOIN sede s ON est.ID_SEDE = s.ID_SEDE
                WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ? AND e.ACTIVO = 'S'
            ");
            
            $stmt->execute([$result['employee_id'], $empresaId]);
            $employeeDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($employeeDetails) {
                $responseData['employee'] = [
                    'ID_EMPLEADO' => $employeeDetails['ID_EMPLEADO'],
                    'NOMBRE' => $employeeDetails['NOMBRE'],
                    'APELLIDO' => $employeeDetails['APELLIDO'],
                    'NOMBRE_COMPLETO' => $employeeDetails['NOMBRE'] . ' ' . $employeeDetails['APELLIDO'],
                    'DNI' => $employeeDetails['DNI'],
                    'CORREO' => $employeeDetails['CORREO'],
                    'TELEFONO' => $employeeDetails['TELEFONO'],
                    'ID_ESTABLECIMIENTO' => $employeeDetails['ID_ESTABLECIMIENTO'],
                    'ESTABLECIMIENTO' => $employeeDetails['ESTABLECIMIENTO']
                ];
            }
        } catch (Exception $e) {
            // Si hay error obteniendo detalles, usar los datos básicos del Python service
            error_log("Error obteniendo detalles del empleado: " . $e->getMessage());
        }
    }
    
    // Si hay candidatos para selección manual, incluirlos
    if (!empty($result['candidates'])) {
        $responseData['candidates'] = [];
        
        foreach ($result['candidates'] as $candidate) {
            $responseData['candidates'][] = [
                'ID_EMPLEADO' => $candidate['employee_id'],
                'NOMBRE_COMPLETO' => $candidate['full_name'],
                'DNI' => $candidate['dni'] ?? '',
                'ESTABLECIMIENTO' => $candidate['establishment_name'] ?? '',
                'ID_ESTABLECIMIENTO' => $candidate['establishment_id'] ?? 0,
                'CONFIDENCE' => $candidate['confidence']
            ];
        }
    }
    
    // Log de la operación
    $logMessage = "Identificación facial automática - Empresa: $empresaId";
    if ($result['success']) {
        $logMessage .= " - Empleado identificado: " . ($result['employee_id'] ?? 'N/A');
    } else {
        $logMessage .= " - Sin identificación exitosa";
    }
    
    try {
        logActivity('IDENTIFICACION_FACIAL_AUTO', $logMessage);
    } catch (Exception $e) {
        error_log("Error registrando actividad: " . $e->getMessage());
    }
    
    echo json_encode($responseData);
    
} catch (Exception $e) {
    error_log("Error en identificación facial automática: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'identification_error'
    ]);
}
?>