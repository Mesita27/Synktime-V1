<?php
// diagnose_server.php - Diagnóstico del servidor de producción
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico del Servidor - Synktime</h1>";
echo "<hr>";

// 1. Información del servidor
echo "<h2>📊 Información del Servidor</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'No disponible') . "<br>";
echo "<strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No disponible') . "<br>";
echo "<strong>Script Path:</strong> " . __FILE__ . "<br>";
echo "<hr>";

// 2. Verificar extensiones necesarias
echo "<h2>🔧 Extensiones PHP</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'session'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✅ Instalada" : "❌ No instalada";
    echo "<strong>$ext:</strong> $status<br>";
}
echo "<hr>";

// 3. Verificar archivos de configuración
echo "<h2>📁 Archivos de Configuración</h2>";

$config_files = [
    'config/database.php',
    'api/config.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "<strong>$file:</strong> ✅ Existe<br>";
        echo "Tamaño: " . filesize($file) . " bytes<br>";
        echo "Permisos: " . substr(sprintf('%o', fileperms($file)), -4) . "<br><br>";
    } else {
        echo "<strong>$file:</strong> ❌ No encontrado<br><br>";
    }
}
echo "<hr>";

// 4. Test de conexión a base de datos
echo "<h2>🗄️ Test de Conexión a Base de Datos</h2>";

// Probar config/database.php
echo "<h3>Probando config/database.php:</h3>";
if (file_exists('config/database.php')) {
    try {
        ob_start();
        include 'config/database.php';
        $output = ob_get_clean();
        
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "✅ Conexión exitosa con config/database.php<br>";
            
            // Probar consulta simple
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "✅ Consulta de prueba exitosa: " . $result['test'] . "<br>";
            
        } else {
            echo "❌ No se pudo crear conexión PDO<br>";
        }
        
        if (!empty($output)) {
            echo "<strong>Output capturado:</strong><br><pre>" . htmlspecialchars($output) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en config/database.php: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "❌ Archivo config/database.php no encontrado<br>";
}

echo "<br>";

// Probar api/config.php
echo "<h3>Probando api/config.php:</h3>";
if (file_exists('api/config.php')) {
    try {
        ob_start();
        include 'api/config.php';
        $output = ob_get_clean();
        
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "✅ Conexión exitosa con api/config.php<br>";
            
            // Probar consulta simple
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "✅ Consulta de prueba exitosa: " . $result['test'] . "<br>";
            
        } else {
            echo "❌ No se pudo crear conexión PDO<br>";
        }
        
        if (!empty($output)) {
            echo "<strong>Output capturado:</strong><br><pre>" . htmlspecialchars($output) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en api/config.php: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "❌ Archivo api/config.php no encontrado<br>";
}

echo "<hr>";

// 5. Verificar permisos de carpetas
echo "<h2>📂 Permisos de Carpetas</h2>";
$directories = ['.', 'config', 'api', 'components', 'assets'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? "✅ Escribible" : "❌ No escribible";
        echo "<strong>$dir/:</strong> Permisos $perms - $writable<br>";
    } else {
        echo "<strong>$dir/:</strong> ❌ No existe<br>";
    }
}

echo "<hr>";

// 6. Variables de entorno útiles
echo "<h2>🌍 Variables de Entorno</h2>";
$env_vars = ['HTTP_HOST', 'SERVER_NAME', 'REQUEST_URI', 'SCRIPT_NAME'];
foreach ($env_vars as $var) {
    echo "<strong>$var:</strong> " . ($_SERVER[$var] ?? 'No definida') . "<br>";
}

echo "<hr>";
echo "<p><strong>✅ Diagnóstico completado</strong></p>";
echo "<p>📝 Copia esta información y compártela para obtener ayuda específica.</p>";
?>