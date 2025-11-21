<?php
/**
 * SCRIPT DE CONFIGURACIÓN AUTOMÁTICA DE BASE DE DATOS
 * Ejecuta la estructura básica necesaria para el sistema biométrico
 */

header('Content-Type: text/html; charset=utf-8');

try {
    // Configuración de base de datos basada en entorno/docker
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD');
    $password = $password === false ? '' : $password;
    $dbname = getenv('DB_NAME') ?: 'synktime';
    
    echo "<h2>🔧 Configuración Automática de Base de Datos SynkTime</h2>";
    echo "<div style='font-family: monospace; background: #f4f4f4; padding: 20px; border-radius: 5px;'>";
    
    // Paso 1: Conectar al servidor MySQL
    echo "<h3>📡 Paso 1: Conectando al servidor MySQL...</h3>";
    
    try {
        $serverPdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8', $host, $port),
            $username,
            $password
        );
        $serverPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Conexión al servidor MySQL: <strong>EXITOSA</strong><br>";
    } catch (PDOException $e) {
        throw new Exception("❌ Error conectando a MySQL: " . $e->getMessage());
    }
    
    // Paso 2: Crear base de datos
    echo "<h3>🗄️ Paso 2: Creando base de datos '$dbname'...</h3>";
    
    try {
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
        echo "✅ Base de datos '$dbname': <strong>CREADA/VERIFICADA</strong><br>";
    } catch (PDOException $e) {
        throw new Exception("❌ Error creando base de datos: " . $e->getMessage());
    }
    
    // Paso 3: Conectar a la base de datos específica
    echo "<h3>🔗 Paso 3: Conectando a la base de datos '$dbname'...</h3>";
    
    try {
        $dbPdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', $host, $port, $dbname),
            $username,
            $password
        );
        $dbPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Conexión a '$dbname': <strong>EXITOSA</strong><br>";
    } catch (PDOException $e) {
        throw new Exception("❌ Error conectando a la base de datos: " . $e->getMessage());
    }
    
    // Paso 4: Crear estructura de tablas
    echo "<h3>📋 Paso 4: Creando estructura de tablas...</h3>";
    
    // Tabla empleado
    $createEmpleado = "
    CREATE TABLE IF NOT EXISTS `empleado` (
      `ID_EMPLEADO` int(11) NOT NULL AUTO_INCREMENT,
      `CODIGO` varchar(20) DEFAULT NULL,
      `NOMBRES` varchar(100) NOT NULL,
      `APELLIDOS` varchar(100) NOT NULL,
      `EMAIL` varchar(100) DEFAULT NULL,
      `TELEFONO` varchar(20) DEFAULT NULL,
      `ACTIVO` char(1) DEFAULT 'Y',
      `FECHA_CREACION` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `FECHA_MODIFICACION` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`ID_EMPLEADO`),
      UNIQUE KEY `CODIGO_UNIQUE` (`CODIGO`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ";
    
    $dbPdo->exec($createEmpleado);
    echo "✅ Tabla 'empleado': <strong>CREADA</strong><br>";
    
    // Tabla biometric_data
    $createBiometric = "
    CREATE TABLE IF NOT EXISTS `biometric_data` (
      `ID` int(11) NOT NULL AUTO_INCREMENT,
      `ID_EMPLEADO` int(11) NOT NULL,
      `BIOMETRIC_TYPE` enum('face','fingerprint') NOT NULL,
      `FINGER_TYPE` varchar(20) DEFAULT NULL,
      `BIOMETRIC_DATA` longtext NOT NULL,
      `QUALITY_SCORE` decimal(5,2) DEFAULT NULL,
      `ACTIVO` tinyint(1) DEFAULT '1',
      `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `UPDATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`ID`),
      KEY `FK_biometric_empleado` (`ID_EMPLEADO`),
      KEY `IDX_biometric_type` (`BIOMETRIC_TYPE`),
      KEY `IDX_active` (`ACTIVO`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ";
    
    $dbPdo->exec($createBiometric);
    echo "✅ Tabla 'biometric_data': <strong>CREADA</strong><br>";
    
    // Tabla biometric_logs
    $createLogs = "
    CREATE TABLE IF NOT EXISTS `biometric_logs` (
      `ID` int(11) NOT NULL AUTO_INCREMENT,
      `ID_EMPLEADO` int(11) NOT NULL,
      `ACTION` enum('enroll','verify','update','delete') NOT NULL,
      `BIOMETRIC_TYPE` enum('face','fingerprint') NOT NULL,
      `SUCCESS` tinyint(1) NOT NULL,
      `ERROR_MESSAGE` text,
      `IP_ADDRESS` varchar(45) DEFAULT NULL,
      `USER_AGENT` text,
      `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`ID`),
      KEY `FK_logs_empleado` (`ID_EMPLEADO`),
      KEY `IDX_action` (`ACTION`),
      KEY `IDX_created` (`CREATED_AT`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ";
    
    $dbPdo->exec($createLogs);
    echo "✅ Tabla 'biometric_logs': <strong>CREADA</strong><br>";
    
    // Paso 5: Insertar datos de prueba
    echo "<h3>👥 Paso 5: Insertando empleados de prueba...</h3>";
    
    $insertEmployees = "
    INSERT IGNORE INTO `empleado` (`ID_EMPLEADO`, `CODIGO`, `NOMBRES`, `APELLIDOS`, `EMAIL`, `ACTIVO`) VALUES
    (1, 'EMP0001', 'Juan Carlos', 'Pérez López', 'juan.perez@synktime.com', 'Y'),
    (2, 'EMP0002', 'María Elena', 'García Martín', 'maria.garcia@synktime.com', 'Y'),
    (3, 'EMP0003', 'Pedro Antonio', 'Rodríguez Silva', 'pedro.rodriguez@synktime.com', 'Y'),
    (4, 'EMP0004', 'Ana Sofía', 'López Herrera', 'ana.lopez@synktime.com', 'Y'),
    (5, 'EMP0005', 'Carlos Eduardo', 'Martínez Ruiz', 'carlos.martinez@synktime.com', 'Y')
    ";
    
    $result = $dbPdo->exec($insertEmployees);
    echo "✅ Empleados de prueba: <strong>INSERTADOS</strong><br>";
    
    // Paso 6: Verificar estructura
    echo "<h3>🔍 Paso 6: Verificando estructura final...</h3>";
    
    $tables = ['empleado', 'biometric_data', 'biometric_logs'];
    foreach ($tables as $table) {
        $stmt = $dbPdo->prepare("SELECT COUNT(*) as count FROM `$table`");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        echo "✅ Tabla '$table': <strong>$count registros</strong><br>";
    }
    
    // Paso 7: Test final
    echo "<h3>🧪 Paso 7: Test de funcionalidad...</h3>";
    
    $testStmt = $dbPdo->prepare("SELECT ID_EMPLEADO, NOMBRES, APELLIDOS FROM empleado WHERE ACTIVO = 'Y' LIMIT 1");
    $testStmt->execute();
    $testEmployee = $testStmt->fetch();
    
    if ($testEmployee) {
        echo "✅ Test de empleado: <strong>EXITOSO</strong> - " . $testEmployee['NOMBRES'] . " " . $testEmployee['APELLIDOS'] . "<br>";
    }
    
    echo "<h2>🎉 ¡CONFIGURACIÓN COMPLETADA EXITOSAMENTE!</h2>";
    echo "<p><strong>La base de datos está lista para el sistema biométrico.</strong></p>";
    echo "<p>Ahora puedes usar: <a href='test-biometric-system-verification.html' target='_blank'>Página de Verificación</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR EN LA CONFIGURACIÓN</h2>";
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Sugerencias:</strong></p>";
    echo "<ul>";
    echo "<li>Verificar que MySQL esté ejecutándose</li>";
    echo "<li>Comprobar las credenciales de conexión</li>";
    echo "<li>Asegurar que el usuario 'root' tenga permisos</li>";
    echo "</ul>";
}

echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h2 { color: #2c3e50; }
h3 { color: #34495e; margin-top: 20px; }
div { max-width: 800px; margin: 0 auto; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
