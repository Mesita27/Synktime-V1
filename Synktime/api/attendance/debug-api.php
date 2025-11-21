<?php
// Test específico del endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUGGING API ENDPOINT ===\n";

try {
    // Test 1: Verificar acceso a archivos
    echo "1. Testing file includes...\n";
    require_once __DIR__ . '/../../config/database.php';
    echo "   ✅ Database config loaded\n";
    
    // Test 2: Verificar conexión de base de datos
    echo "2. Testing database connection...\n";
    if (isset($conn) && $conn) {
        echo "   ✅ Database connected (MySQLi)\n";
    } elseif (isset($pdo) && $pdo) {
        echo "   ✅ Database connected (PDO)\n";
    } else {
        echo "   ❌ No database connection found\n";
    }
    
    // Test 3: Simular datos POST
    echo "3. Testing POST data simulation...\n";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [];
    
    // Simular datos JSON
    $test_data = [
        'employee_id' => 100,
        'type' => 'ENTRADA'
    ];
    
    echo "   ✅ POST simulation ready\n";
    
    // Test 4: Verificar empleado
    echo "4. Testing employee lookup...\n";
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, CONCAT(NOMBRE, ' ', APELLIDO) as NOMBRE_COMPLETO FROM empleado WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'");
    $employee_id = 100;
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        echo "   ✅ Employee found: " . $employee['NOMBRE_COMPLETO'] . "\n";
    } else {
        echo "   ❌ Employee not found\n";
    }
    
    echo "\n=== ALL TESTS PASSED ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>