<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';

// Limpiar buffer de salida para evitar caracteres extra
ob_start();

/**
 * API DE DIAGNÓSTICO BIOMÉTRICO CORREGIDA
 * Verifica el estado completo del sistema
 */

try {
    // Obtener el tipo de test solicitado
    $input = json_decode(file_get_contents('php://input'), true);
    $testType = $input['test'] ?? ($_GET['test'] ?? 'general');
    
    // Limpiar cualquier salida previa
    ob_clean();
    
    switch ($testType) {
        case 'database':
            $result = testDatabase();
            break;
            
        case 'tables':
            $result = testTables();
            break;
            
        case 'apis':
            $result = testAPIs();
            break;
            
        case 'complete':
            $result = testComplete();
            break;
            
        default:
            $result = testGeneral();
            break;
    }
    
    // Asegurar que solo enviamos JSON válido
    echo json_encode($result);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'diagnostic_error'
    ]);
}

/**
 * Test de conexión a base de datos
 */
function testDatabase() {
    try {
        // Configuración de base de datos basada en entorno/docker
        $cfg = synktime_db_config();
        $host = $cfg['host'];
        $port = $cfg['port'];
        $dbname = $cfg['dbname'];
        $username = $cfg['username'];
        $password = $cfg['password'];

        // Primero probar conexión al servidor MySQL
        try {
            $serverPdo = new PDO(
                sprintf('mysql:host=%s;port=%s;charset=utf8', $host, $port),
                $username,
                $password
            );
            $serverPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar si la base de datos existe
            $stmt = $serverPdo->query("SHOW DATABASES LIKE '$dbname'");
            $dbExists = $stmt->rowCount() > 0;
            
            if (!$dbExists) {
                // Crear la base de datos si no existe
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
            }
            
            // Conectar a la base de datos específica
            $testPdo = synktime_get_pdo();
            $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Test de consulta simple
            $stmt = $testPdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'message' => 'Conexión exitosa a la base de datos',
                'data' => [
                    'server' => $host,
                    'database' => $info['current_db'],
                    'mysql_version' => $info['mysql_version'],
                    'connection_status' => 'active',
                    'database_created' => !$dbExists
                ]
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'data' => [
                    'server' => $host,
                    'database' => $dbname,
                    'error_code' => $e->getCode(),
                    'suggestion' => 'Verificar que MySQL esté ejecutándose y las credenciales sean correctas'
                ]
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => [
                'server' => 'localhost',
                'database' => null,
                'status' => 'connection_failed'
            ]
        ];
    }
}

/**
 * Test de estructura de tablas
 */
function testTables() {
    try {
    $cfg = synktime_db_config();
    $conn = synktime_get_pdo();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tables = [
            'empleado' => 'Tabla principal de empleados',
            'biometric_data' => 'Datos biométricos almacenados',
            'biometric_logs' => 'Registros de actividad biométrica'
        ];
        
        $tableInfo = [];
        
        foreach ($tables as $tableName => $description) {
            try {
                // Verificar si la tabla existe
                $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$tableName]);
                $exists = $stmt->fetch();
                
                if ($exists) {
                    // Contar registros
                    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM `$tableName`");
                    $countStmt->execute();
                    $count = $countStmt->fetch()['count'];
                    
                    // Si es la tabla empleado, verificar estructura
                    if ($tableName === 'empleado') {
                        $columns = getTableColumns($conn, $tableName);
                        $hasRequiredColumns = checkEmployeeColumns($columns);
                        
                        $tableInfo[] = [
                            'name' => $tableName,
                            'description' => $description,
                            'exists' => true,
                            'rows' => $count,
                            'columns' => $columns,
                            'structure_ok' => $hasRequiredColumns
                        ];
                    } else {
                        $tableInfo[] = [
                            'name' => $tableName,
                            'description' => $description,
                            'exists' => true,
                            'rows' => $count
                        ];
                    }
                } else {
                    $tableInfo[] = [
                        'name' => $tableName,
                        'description' => $description,
                        'exists' => false,
                        'rows' => 0
                    ];
                }
                
            } catch (Exception $e) {
                $tableInfo[] = [
                    'name' => $tableName,
                    'description' => $description,
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $missingTables = array_filter($tableInfo, function($table) {
            return !$table['exists'];
        });
        
        return [
            'success' => count($missingTables) === 0,
            'message' => count($missingTables) === 0 ? 'Todas las tablas están presentes' : 'Faltan ' . count($missingTables) . ' tablas',
            'data' => [
                'tables' => $tableInfo,
                'missing_count' => count($missingTables),
                'total_tables' => count($tables)
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error verificando tablas: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener columnas de una tabla
 */
function getTableColumns($conn, $tableName) {
    try {
        $stmt = $conn->prepare("DESCRIBE `$tableName`");
        $stmt->execute();
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        return $columns;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Verificar si la tabla empleado tiene las columnas necesarias
 */
function checkEmployeeColumns($columns) {
    $requiredColumns = ['ID_EMPLEADO', 'NOMBRES', 'APELLIDOS'];
    $optionalColumns = ['CODIGO', 'EMAIL', 'ACTIVO'];
    
    $hasRequired = true;
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            $hasRequired = false;
            break;
        }
    }
    
    return $hasRequired;
}

/**
 * Test de APIs
 */
function testAPIs() {
    $apis = [
        '/api/biometric/enroll.php' => 'API de enrolamiento',
        '/api/biometric/verify-fingerprint.php' => 'API de verificación',
        '/api/biometric/stats.php' => 'API de estadísticas'
    ];
    
    $apiResults = [];
    
    foreach ($apis as $apiPath => $description) {
        $fullPath = __DIR__ . '/../..' . $apiPath;
        
        $apiResults[] = [
            'path' => $apiPath,
            'description' => $description,
            'exists' => file_exists($fullPath),
            'readable' => file_exists($fullPath) ? is_readable($fullPath) : false
        ];
    }
    
    $workingApis = array_filter($apiResults, function($api) {
        return $api['exists'] && $api['readable'];
    });
    
    return [
        'success' => count($workingApis) === count($apis),
        'message' => count($workingApis) . '/' . count($apis) . ' APIs disponibles',
        'data' => [
            'apis' => $apiResults,
            'working_count' => count($workingApis),
            'total_apis' => count($apis)
        ]
    ];
}

/**
 * Test completo del sistema
 */
function testComplete() {
    $results = [
        'database' => testDatabase(),
        'tables' => testTables(),
        'apis' => testAPIs()
    ];
    
    $allSuccess = true;
    foreach ($results as $test) {
        if (!$test['success']) {
            $allSuccess = false;
            break;
        }
    }
    
    return [
        'success' => $allSuccess,
        'message' => $allSuccess ? 'Sistema completamente funcional' : 'Sistema requiere atención',
        'data' => $results
    ];
}

/**
 * Test general
 */
function testGeneral() {
    return [
        'success' => true,
        'message' => 'API de diagnóstico funcionando',
        'data' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => $_SERVER['SERVER_NAME'] ?? 'localhost',
            'php_version' => PHP_VERSION,
            'available_tests' => ['database', 'tables', 'apis', 'complete']
        ]
    ];
}

// Finalizar buffer
ob_end_flush();
?>
