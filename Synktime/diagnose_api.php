<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de APIs - Justificaciones</h1>";

// 1. Verificar conexión a base de datos
echo "<h2>1. Verificando conexión a base de datos</h2>";
try {
    include_once 'config/database.php';
    echo "<p style='color:green'>✓ Conexión a base de datos exitosa</p>";
    echo "<p>Host: " . $host . "</p>";
    echo "<p>Database: " . $dbname . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

// 2. Verificar estructura de tablas
echo "<h2>2. Verificando estructura de tablas</h2>";

// Verificar tabla empleado
try {
    $stmt = $pdo->query("DESCRIBE empleado");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tabla empleado:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error verificando tabla empleado: " . $e->getMessage() . "</p>";
}

// Verificar tabla justificacion
try {
    $stmt = $pdo->query("DESCRIBE justificacion");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tabla justificacion:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error verificando tabla justificacion: " . $e->getMessage() . "</p>";
}

// 3. Probar consultas básicas
echo "<h2>3. Probando consultas básicas</h2>";

// Contar empleados
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleado");
    $result = $stmt->fetch();
    echo "<p>✓ Total empleados: " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error contando empleados: " . $e->getMessage() . "</p>";
}

// Contar justificaciones
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM justificacion");
    $result = $stmt->fetch();
    echo "<p>✓ Total justificaciones: " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error contando justificaciones: " . $e->getMessage() . "</p>";
}

// 4. Probar consulta de empleados simplificada
echo "<h2>4. Probando consulta de empleados</h2>";
try {
    $stmt = $pdo->prepare("SELECT ID_EMPLEADO, NOMBRE, APELLIDO, DNI FROM empleado WHERE ESTADO = 'A' LIMIT 5");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Primeros 5 empleados:</h3>";
    echo "<pre>" . json_encode($empleados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error en consulta empleados: " . $e->getMessage() . "</p>";
}

// 5. Verificar archivos API
echo "<h2>5. Verificando archivos API</h2>";

$apiFiles = [
    'api/justificaciones.php',
    'config/database.php',
    'auth/session.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color:green'>✓ $file existe</p>";
    } else {
        echo "<p style='color:red'>✗ $file NO existe</p>";
    }
}

// 6. Test directo del API
echo "<h2>6. Test directo del API de empleados</h2>";
try {
    // Simular llamada GET para empleados elegibles
    $_GET['action'] = 'empleados_elegibles';
    
    ob_start();
    include 'api/justificaciones.php';
    $output = ob_get_clean();
    
    echo "<h3>Salida del API:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Intentar parsear como JSON
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "<p style='color:green'>✓ Respuesta válida JSON</p>";
    } else {
        echo "<p style='color:red'>✗ Respuesta NO es JSON válido</p>";
        echo "<p>Error JSON: " . json_last_error_msg() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error ejecutando API: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Diagnóstico completado - " . date('Y-m-d H:i:s') . "</strong></p>";
?>