<?php
/**
 * SNKTIME Python Biometric Service Client
 * 
 * PHP client for communicating with the Python FastAPI biometric service
 * Handles enrollment, verification, and device management
 */

class BiometricServiceClient 
{
    private $baseUrl;
    private $timeout;
    private $apiKey;
    private $lastError;
    private $debug;
    
    /**
     * Constructor
     * 
     * @param string $baseUrl Python service base URL (e.g., http://127.0.0.1:8000)
     * @param int $timeout Request timeout in seconds
     * @param string $apiKey Optional API key for authentication
     * @param bool $debug Enable debug logging
     */
    public function __construct($baseUrl = 'http://127.0.0.1:8000', $timeout = 30, $apiKey = '', $debug = false) 
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->apiKey = $apiKey;
        $this->debug = $debug;
        $this->lastError = null;
    }
    
    /**
     * Get last error message
     * 
     * @return string|null
     */
    public function getLastError() 
    {
        return $this->lastError;
    }
    
    /**
     * Make HTTP request to Python service
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|false Response data or false on error
     */
    private function makeRequest($method, $endpoint, $data = null) 
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        
        // Basic cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        // Add API key if provided
        if (!empty($this->apiKey)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
                curl_getopt($ch, CURLOPT_HTTPHEADER),
                ['X-API-Key: ' . $this->apiKey]
            ));
        }
        
        // Add request data for POST/PUT requests
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        // Debug logging
        if ($this->debug) {
            error_log("BiometricServiceClient: $method $url");
            if ($data) {
                error_log("BiometricServiceClient: Request data: " . json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle cURL errors
        if ($response === false || !empty($error)) {
            $this->lastError = "cURL error: $error";
            if ($this->debug) {
                error_log("BiometricServiceClient: " . $this->lastError);
            }
            return false;
        }
        
        // Parse JSON response
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = "JSON decode error: " . json_last_error_msg();
            if ($this->debug) {
                error_log("BiometricServiceClient: " . $this->lastError);
                error_log("BiometricServiceClient: Raw response: $response");
            }
            return false;
        }
        
        // Handle HTTP errors
        if ($httpCode >= 400) {
            $this->lastError = "HTTP $httpCode: " . ($responseData['detail'] ?? 'Unknown error');
            if ($this->debug) {
                error_log("BiometricServiceClient: " . $this->lastError);
            }
            return false;
        }
        
        if ($this->debug) {
            error_log("BiometricServiceClient: Response: " . json_encode($responseData));
        }
        
        return $responseData;
    }
    
    /**
     * Health check
     * 
     * @return array|false
     */
    public function healthCheck() 
    {
        return $this->makeRequest('GET', '/health');
    }
    
    /**
     * Scan for compatible devices
     * 
     * @return array|false
     */
    public function scanDevices() 
    {
        return $this->makeRequest('GET', '/devices/scan');
    }
    
    /**
     * Enroll facial biometric
     * 
     * @param int $employeeId Employee ID
     * @param string $imageData Base64 encoded image
     * @param float $qualityThreshold Quality threshold (0.0-1.0)
     * @return array|false
     */
    public function enrollFacial($employeeId, $imageData, $qualityThreshold = 0.5) 
    {
        $data = [
            'employee_id' => (int)$employeeId,
            'image_data' => $imageData,
            'quality_threshold' => (float)$qualityThreshold
        ];
        
        return $this->makeRequest('POST', '/facial/enroll', $data);
    }
    
    /**
     * Verify facial biometric
     * 
     * @param int $employeeId Employee ID
     * @param string $imageData Base64 encoded image
     * @param float $confidenceThreshold Confidence threshold (0.0-1.0)
     * @return array|false
     */
    public function verifyFacial($employeeId, $imageData, $confidenceThreshold = 0.6) 
    {
        $data = [
            'employee_id' => (int)$employeeId,
            'image_data' => $imageData,
            'confidence_threshold' => (float)$confidenceThreshold
        ];
        
        return $this->makeRequest('POST', '/facial/verify', $data);
    }
    
    /**
     * Extract facial embedding
     * 
     * @param string $imageData Base64 encoded image
     * @return array|false
     */
    public function extractFacialEmbedding($imageData) 
    {
        $data = [
            'image_data' => $imageData
        ];
        
        return $this->makeRequest('POST', '/facial/extract', $data);
    }
    
    /**
     * Enroll fingerprint
     * 
     * @param int $employeeId Employee ID
     * @param string $fingerType Finger type (e.g., 'index_right')
     * @param string|null $deviceId Device ID (optional)
     * @return array|false
     */
    public function enrollFingerprint($employeeId, $fingerType, $deviceId = null) 
    {
        $data = [
            'employee_id' => (int)$employeeId,
            'finger_type' => $fingerType
        ];
        
        if ($deviceId !== null) {
            $data['device_id'] = $deviceId;
        }
        
        return $this->makeRequest('POST', '/fingerprint/enroll', $data);
    }
    
    /**
     * Verify fingerprint
     * 
     * @param int $employeeId Employee ID
     * @param string|null $fingerType Finger type (optional)
     * @param string|null $deviceId Device ID (optional)
     * @return array|false
     */
    public function verifyFingerprint($employeeId, $fingerType = null, $deviceId = null) 
    {
        $data = [
            'employee_id' => (int)$employeeId
        ];
        
        if ($fingerType !== null) {
            $data['finger_type'] = $fingerType;
        }
        
        if ($deviceId !== null) {
            $data['device_id'] = $deviceId;
        }
        
        return $this->makeRequest('POST', '/fingerprint/verify', $data);
    }
    
    /**
     * Enroll RFID card/tag
     * 
     * @param int $employeeId Employee ID
     * @param string|null $deviceId Device ID (optional)
     * @param int $timeout Timeout in seconds
     * @return array|false
     */
    public function enrollRFID($employeeId, $deviceId = null, $timeout = 10) 
    {
        $data = [
            'employee_id' => (int)$employeeId,
            'timeout' => (int)$timeout
        ];
        
        if ($deviceId !== null) {
            $data['device_id'] = $deviceId;
        }
        
        return $this->makeRequest('POST', '/rfid/enroll', $data);
    }
    
    /**
     * Verify RFID card/tag
     * 
     * @param string $uid Card/tag UID
     * @param string|null $deviceId Device ID (optional)
     * @return array|false
     */
    public function verifyRFID($uid, $deviceId = null) 
    {
        $data = [
            'uid' => $uid
        ];
        
        if ($deviceId !== null) {
            $data['device_id'] = $deviceId;
        }
        
        return $this->makeRequest('POST', '/rfid/verify', $data);
    }
    
    /**
     * Check if Python service is available
     * 
     * @return bool
     */
    public function isServiceAvailable() 
    {
        $health = $this->healthCheck();
        return $health !== false && isset($health['status']) && $health['status'] === 'healthy';
    }
    
    /**
     * Get service info
     * 
     * @return array|false
     */
    public function getServiceInfo() 
    {
        return $this->healthCheck();
    }
    
    /**
     * Encode image file to base64
     * 
     * @param string $imagePath Path to image file
     * @return string|false Base64 encoded image or false on error
     */
    public static function encodeImage($imagePath) 
    {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
            return false;
        }
        
        return base64_encode($imageData);
    }
    
    /**
     * Encode image data URL to base64 (remove data URL prefix)
     * 
     * @param string $dataUrl Data URL (e.g., "data:image/jpeg;base64,/9j/4AA...")
     * @return string Base64 data without prefix
     */
    public static function extractBase64FromDataUrl($dataUrl) 
    {
        if (strpos($dataUrl, ',') !== false) {
            return substr($dataUrl, strpos($dataUrl, ',') + 1);
        }
        
        return $dataUrl;
    }
    
    /**
     * Store biometric result in database
     * 
     * @param PDO $pdo Database connection
     * @param array $result Python service result
     * @param string $operation 'enrollment' or 'verification'
     * @return bool Success status
     */
    public function storeBiometricResult($pdo, $result, $operation = 'verification') 
    {
        try {
            if (!isset($result['employee_id']) || !isset($result['biometric_type'])) {
                return false;
            }
            
            $employeeId = $result['employee_id'];
            $biometricType = $result['biometric_type'];
            $success = $result['success'] ? 1 : 0;
            $confidence = $result['confidence'] ?? null;
            $deviceId = $result['device_id'] ?? null;
            $processingTime = $result['processing_time_ms'] ?? null;
            $errorMessage = $result['message'] ?? null;
            
            if ($operation === 'enrollment' && $success) {
                // Store enrollment data
                $stmt = $pdo->prepare("
                    INSERT INTO biometric_data 
                    (ID_EMPLEADO, BIOMETRIC_TYPE, PYTHON_SERVICE_ID, DEVICE_ID, 
                     QUALITY_SCORE, TEMPLATE_VERSION, CREATED_AT, ACTIVO)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)
                    ON DUPLICATE KEY UPDATE
                    PYTHON_SERVICE_ID = VALUES(PYTHON_SERVICE_ID),
                    DEVICE_ID = VALUES(DEVICE_ID),
                    QUALITY_SCORE = VALUES(QUALITY_SCORE),
                    UPDATED_AT = NOW()
                ");
                
                $stmt->execute([
                    $employeeId,
                    $biometricType,
                    $result['template_id'] ?? null,
                    $deviceId,
                    $result['quality_score'] ?? null,
                    '2.0'
                ]);
            }
            
            // Store log entry
            $stmt = $pdo->prepare("
                INSERT INTO biometric_logs 
                (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, CONFIDENCE_SCORE,
                 API_SOURCE, OPERATION_TYPE, DEVICE_ID, PROCESSING_TIME_MS, ERROR_MESSAGE,
                 FECHA, HORA, CREATED_AT)
                VALUES (?, ?, ?, ?, 'python_service', ?, ?, ?, ?, CURDATE(), CURTIME(), NOW())
            ");
            
            $stmt->execute([
                $employeeId,
                $biometricType,
                $success,
                $confidence,
                $operation,
                $deviceId,
                $processingTime,
                $success ? null : $errorMessage
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error storing biometric result: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * BiometricServiceManager - Singleton wrapper for easy access
 */
class BiometricServiceManager 
{
    private static $instance = null;
    private $client = null;
    private $config = [];
    
    private function __construct() 
    {
        $this->loadConfig();
        $this->initializeClient();
    }
    
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function loadConfig() 
    {
        // Load configuration from file or database
        $this->config = [
            'base_url' => $_ENV['PYTHON_SERVICE_URL'] ?? 'http://127.0.0.1:8000',
            'timeout' => $_ENV['PYTHON_SERVICE_TIMEOUT'] ?? 30,
            'api_key' => $_ENV['PYTHON_SERVICE_API_KEY'] ?? '',
            'debug' => $_ENV['PYTHON_SERVICE_DEBUG'] ?? false
        ];
        
        // Try to load from database configuration
        try {
            global $pdo;
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave LIKE 'python_service_%'");
                $stmt->execute();
                
                while ($row = $stmt->fetch()) {
                    $key = str_replace('python_service_', '', $row['clave']);
                    $this->config[$key] = $row['valor'];
                }
            }
        } catch (Exception $e) {
            // Ignore database errors
        }
    }
    
    private function initializeClient() 
    {
        $this->client = new BiometricServiceClient(
            $this->config['base_url'],
            $this->config['timeout'],
            $this->config['api_key'],
            $this->config['debug']
        );
    }
    
    public function getClient() 
    {
        return $this->client;
    }
    
    public function getConfig() 
    {
        return $this->config;
    }
    
    public function isServiceEnabled() 
    {
        return $this->client->isServiceAvailable();
    }
}
?>