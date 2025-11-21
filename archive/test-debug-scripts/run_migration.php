<?php
/**
 * Script de migración para crear tabla justificaciones v2.0
 * Ejecuta la migración de base de datos de forma segura
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Migración: Tabla Justificaciones v2.0</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // Incluir configuración de base de datos
    require_once 'config/database.php';
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>✅ Conexión a base de datos establecida</h2>";
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Database:</strong> $dbname</p>";
    echo "</div>";
    
    // Leer archivo de migración
    $migrationFile = 'database/migrations/create_justificaciones_table_v2.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Archivo de migración no encontrado: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>📄 Archivo de migración cargado</h2>";
    echo "<p><strong>Archivo:</strong> $migrationFile</p>";
    echo "<p><strong>Tamaño:</strong> " . number_format(strlen($sql)) . " caracteres</p>";
    echo "</div>";
    
    // Verificar estructura actual
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>🔍 Verificando estructura actual</h2>";
    
    // Verificar si existe tabla justificacion antigua
    $stmt = $pdo->query("SHOW TABLES LIKE 'justificacion'");
    $oldTableExists = $stmt->rowCount() > 0;
    
    if ($oldTableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM justificacion");
        $oldCount = $stmt->fetch()['total'];
        echo "<p>📦 Tabla 'justificacion' encontrada con $oldCount registros</p>";
    } else {
        echo "<p>📦 Tabla 'justificacion' no existe</p>";
    }
    
    // Verificar si existe tabla justificaciones nueva
    $stmt = $pdo->query("SHOW TABLES LIKE 'justificaciones'");
    $newTableExists = $stmt->rowCount() > 0;
    
    if ($newTableExists) {
        echo "<p>⚠️ Tabla 'justificaciones' ya existe - se recreará</p>";
    } else {
        echo "<p>✨ Tabla 'justificaciones' no existe - se creará nueva</p>";
    }
    echo "</div>";
    
    // Dividir SQL en statements individuales
    $statements = array_filter(
        array_map('trim', explode(';', $sql)), 
        function($stmt) { 
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*$/', $stmt); 
        }
    );
    
    echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>⚡ Ejecutando migración</h2>";
    echo "<p><strong>Total de statements:</strong> " . count($statements) . "</p>";
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            // Limpiar statement
            $cleanStatement = trim($statement);
            if (empty($cleanStatement)) continue;
            
            echo "<p>📝 Ejecutando statement " . ($index + 1) . "...</p>";
            
            $result = $pdo->exec($cleanStatement);
            $executed++;
            
            // Mostrar resultado si es SELECT
            if (stripos($cleanStatement, 'SELECT') === 0) {
                $stmt = $pdo->query($cleanStatement);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($result)) {
                    echo "<div style='background: #f5f5f5; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
                    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                    echo "</div>";
                }
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "<p style='color: red;'>❌ Error en statement " . ($index + 1) . ": " . $e->getMessage() . "</p>";
            
            // Si es un error crítico, hacer rollback
            if (strpos($e->getMessage(), 'Syntax error') !== false || 
                strpos($e->getMessage(), 'Unknown column') !== false) {
                throw $e;
            }
        }
    }
    echo "</div>";
    
    if ($errors === 0) {
        // Commit transacción
        $pdo->commit();
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h2>🎉 Migración completada exitosamente</h2>";
        echo "<p><strong>Statements ejecutados:</strong> $executed</p>";
        echo "<p><strong>Errores:</strong> $errors</p>";
        echo "</div>";
        
        // Verificar resultado
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM justificaciones");
            $newCount = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("DESCRIBE justificaciones");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h2>📊 Verificación post-migración</h2>";
            echo "<p><strong>Registros en nueva tabla:</strong> $newCount</p>";
            echo "<p><strong>Columnas creadas:</strong> " . count($columns) . "</p>";
            echo "<details><summary>Ver estructura de tabla</summary>";
            echo "<ul>";
            foreach ($columns as $column) {
                echo "<li>$column</li>";
            }
            echo "</ul></details>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error verificando resultado: " . $e->getMessage() . "</p>";
        }
        
    } else {
        // Rollback en caso de errores
        $pdo->rollback();
        throw new Exception("Migración falló con $errors errores. Se hizo rollback.");
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #f44336;'>";
    echo "<h2>❌ Error en migración</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    
    error_log("Error en migración justificaciones: " . $e->getMessage());
}

echo "<hr>";
echo "<p><strong>Migración finalizada:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='test_simple_api.php'>🧪 Probar APIs</a> | <a href='diagnose_api.php'>🔍 Diagnóstico</a></p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
}

h1, h2 {
    color: #333;
}

div {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

pre {
    font-size: 12px;
    max-height: 300px;
    overflow-y: auto;
}

details {
    margin: 10px 0;
}

summary {
    cursor: pointer;
    font-weight: bold;
    padding: 5px;
    background: #f0f0f0;
    border-radius: 3px;
}

a {
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin: 5px;
}

a:hover {
    background: #0056b3;
}
</style>