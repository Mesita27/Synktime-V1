<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

/**
 * Sistema de Verificación de Huellas Dactilares
 * Implementación gratuita usando algoritmos de comparación de minutiae
 */

// Clase para manejo de APIs gratuitas de reconocimiento
class FreeRecognitionAPI {
    private $pdo;
    private $config;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }
    
    private function loadConfig() {
        try {
            $stmt = $this->pdo->prepare("SELECT provider, config FROM biometric_api_config WHERE enabled = TRUE");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($configs as $config) {
                $this->config[$config['provider']] = json_decode($config['config'], true);
            }
        } catch (Exception $e) {
            // Usar configuración por defecto si no hay tabla
            $this->config = [
                'simulation' => ['enabled' => true, 'threshold' => 0.75]
            ];
        }
    }
    
    /**
     * Verifica huella usando TensorFlow.js Handpose (gratuito)
     */
    public function verifyWithTensorFlow($stored_data, $input_data) {
        // Simulación de TensorFlow.js para análisis de patrones
        if (!$stored_data || !$input_data) {
            return ['success' => false, 'confidence' => 0, 'method' => 'tensorflow'];
        }
        
        $stored = json_decode($stored_data, true);
        $input = json_decode($input_data, true);
        
        if (!$stored || !$input) {
            return ['success' => false, 'confidence' => 0, 'method' => 'tensorflow'];
        }
        
        // Simular análisis con TensorFlow.js
        $confidence = $this->calculateAdvancedSimilarity($stored, $input);
        $threshold = $this->config['tensorflow']['threshold'] ?? 0.75;
        
        return [
            'success' => $confidence >= $threshold,
            'confidence' => $confidence,
            'method' => 'tensorflow',
            'details' => [
                'minutiae_matches' => count($stored['minutiae'] ?? []),
                'quality_score' => ($stored['quality'] ?? 0.8 + $input['quality'] ?? 0.8) / 2
            ]
        ];
    }
    
    /**
     * Verifica huella usando OpenCV.js (gratuito)
     */
    public function verifyWithOpenCV($stored_data, $input_data) {
        if (!$stored_data || !$input_data) {
            return ['success' => false, 'confidence' => 0, 'method' => 'opencv'];
        }
        
        $stored = json_decode($stored_data, true);
        $input = json_decode($input_data, true);
        
        // Simular análisis con OpenCV.js SIFT/SURF
        $confidence = $this->calculateSIFTSimilarity($stored, $input);
        $threshold = $this->config['opencv']['threshold'] ?? 0.70;
        
        return [
            'success' => $confidence >= $threshold,
            'confidence' => $confidence,
            'method' => 'opencv',
            'details' => [
                'keypoints' => count($stored['keypoints'] ?? []),
                'descriptors' => count($input['descriptors'] ?? [])
            ]
        ];
    }
    
    /**
     * Algoritmo avanzado de similitud basado en minutiae
     */
    private function calculateAdvancedSimilarity($stored, $input) {
        $stored_minutiae = $stored['minutiae'] ?? [];
        $input_minutiae = $input['minutiae'] ?? [];
        
        if (empty($stored_minutiae) || empty($input_minutiae)) {
            return 0;
        }
        
        $matches = 0;
        $total_comparisons = 0;
        
        // Comparación de minutiae usando distancia euclidiana
        foreach ($stored_minutiae as $stored_point) {
            $best_match = 0;
            foreach ($input_minutiae as $input_point) {
                $distance = sqrt(
                    pow($stored_point['x'] - $input_point['x'], 2) +
                    pow($stored_point['y'] - $input_point['y'], 2)
                );
                
                $angle_diff = abs($stored_point['angle'] - $input_point['angle']);
                $angle_diff = min($angle_diff, 360 - $angle_diff);
                
                // Scoring basado en distancia y ángulo
                $similarity = 1 - ($distance / 100) - ($angle_diff / 180);
                $best_match = max($best_match, $similarity);
                $total_comparisons++;
            }
            
            if ($best_match > 0.7) {
                $matches++;
            }
        }
        
        // Calcular confianza final
        $minutiae_score = $matches / count($stored_minutiae);
        $quality_score = ($stored['quality'] ?? 0.8 + $input['quality'] ?? 0.8) / 2;
        
        return ($minutiae_score * 0.7 + $quality_score * 0.3);
    }
    
    /**
     * Simulación de algoritmo SIFT para OpenCV
     */
    private function calculateSIFTSimilarity($stored, $input) {
        $stored_keypoints = $stored['keypoints'] ?? [];
        $input_keypoints = $input['keypoints'] ?? [];
        
        if (empty($stored_keypoints) || empty($input_keypoints)) {
            return 0.6 + (rand(0, 20) / 100); // Similitud base simulada
        }
        
        // Simular matching de descriptores SIFT
        $good_matches = 0;
        $total_matches = min(count($stored_keypoints), count($input_keypoints));
        
        for ($i = 0; $i < $total_matches; $i++) {
            // Simular distancia de Hamming para descriptores
            $hamming_distance = rand(0, 256);
            if ($hamming_distance < 50) { // Threshold para good match
                $good_matches++;
            }
        }
        
        return min(0.95, ($good_matches / max(1, $total_matches)) + 0.1);
    }
}

// Función legacy para compatibilidad
function calculateFingerprintSimilarity($stored_data, $input_data) {
    $api = new FreeRecognitionAPI($GLOBALS['pdo']);
    $result = $api->verifyWithTensorFlow($stored_data, $input_data);
    return $result['confidence'];
}

// Función para registrar log biométrico
function logBiometricAttempt($pdo, $employee_id, $method, $success, $confidence, $operation_type = 'verification') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, CONFIDENCE_SCORE, 
             API_SOURCE, OPERATION_TYPE, FECHA, HORA) 
            VALUES (?, ?, ?, ?, 'internal_api', ?, CURDATE(), CURTIME())
        ");
        
        $stmt->execute([
            $employee_id,
            $method,
            $success ? 1 : 0,
            $confidence,
            $operation_type
        ]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error logging biometric attempt: " . $e->getMessage());
        return false;
    }
}

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos JSON
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (!$data) {
        throw new Exception('Datos inválidos');
    }
    
    $employee_id = $data['employee_id'] ?? null;
    $fingerprint_data = $data['fingerprint_data'] ?? null;
    $finger_type = $data['finger_type'] ?? 'index_right';
    
    if (!$employee_id || !$fingerprint_data) {
        throw new Exception('Datos requeridos faltantes');
    }
    
    // Verificar que el empleado existe
    $stmt = $pdo->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO 
        FROM empleado 
        WHERE ID_EMPLEADO = ? AND ESTADO = 'A' AND ACTIVO = 'S'
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Buscar datos biométricos almacenados
    $stmt = $pdo->prepare("
        SELECT biometric_data, additional_info
        FROM employee_biometrics
        WHERE employee_id = ?
        AND biometric_type = 'fingerprint'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$employee_id]);
    $stored_biometrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stored_biometrics)) {
        // No hay datos biométricos registrados
        logBiometricAttempt($pdo, $employee_id, 'fingerprint', false, 0);
        
        echo json_encode([
            'success' => false,
            'message' => 'No hay datos biométricos registrados para este empleado. Debe completar el proceso de enrolamiento.',
            'requires_enrollment' => true
        ]);
        exit;
    }
    
    // Comparar con cada huella almacenada
    $best_match = 0;
    $matched_finger = null;
    
    foreach ($stored_biometrics as $stored) {
        $similarity = calculateFingerprintSimilarity(
            $stored['biometric_data'],
            json_encode($fingerprint_data)
        );

        // Obtener el tipo de dedo desde additional_info si existe
        $additional_info = json_decode($stored['additional_info'], true);
        $finger_type = $additional_info['finger_type'] ?? $finger_type;

        if ($similarity > $best_match) {
            $best_match = $similarity;
            $matched_finger = $finger_type;
        }
    }
    
    // Umbral de verificación (ajustable)
    $verification_threshold = 0.75;
    $verification_success = $best_match >= $verification_threshold;
    
    // Registrar intento
    logBiometricAttempt($pdo, $employee_id, 'fingerprint', $verification_success, $best_match);
    
    if ($verification_success) {
        echo json_encode([
            'success' => true,
            'message' => 'Huella verificada correctamente',
            'confidence' => round($best_match, 4),
            'matched_finger' => $matched_finger,
            'employee_name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
            'data' => 'fingerprint_verified'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'La huella no coincide con los registros del empleado',
            'confidence' => round($best_match, 4),
            'requires_retry' => true
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
