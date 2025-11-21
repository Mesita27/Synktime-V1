<?php
/**
 * Run the justificaciones table migration safely
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Running Justificaciones Table Migration</h1>";

try {
    require_once 'config/database.php';
    
    echo "<h2>1️⃣ Current Table State</h2>";
    
    // Check current table structure
    $stmt = $pdo->query("SHOW TABLES LIKE 'justificaciones'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        // Get current record count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM justificaciones");
        $currentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>📊 Current records in justificaciones table: <strong>$currentCount</strong></p>";
        
        // Show current structure
        $stmt = $pdo->query("DESCRIBE justificaciones");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>📋 Current table structure:</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li><strong>" . $column['Field'] . "</strong> (" . $column['Type'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ Table 'justificaciones' does not exist</p>";
    }
    
    echo "<h2>2️⃣ Reading Migration File</h2>";
    
    $migrationFile = 'database/migrations/create_justificaciones_table_v2.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    echo "<p>✅ Migration file loaded: <strong>" . basename($migrationFile) . "</strong></p>";
    echo "<p>📄 File size: " . number_format(strlen($sql)) . " characters</p>";
    
    echo "<h2>3️⃣ Executing Migration</h2>";
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    $pdo->beginTransaction();
    
    try {
        foreach ($statements as $statement) {
            // Skip comments and empty statements
            if (empty($statement) || 
                strpos($statement, '--') === 0 || 
                strpos($statement, '/*') === 0) {
                continue;
            }
            
            // Execute the statement
            $pdo->exec($statement);
            $executed++;
            
            // Show progress for important operations
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "<p>✅ Created table: <strong>$tableName</strong></p>";
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "<p>✅ Inserted data into: <strong>$tableName</strong></p>";
            } elseif (stripos($statement, 'CREATE VIEW') !== false) {
                preg_match('/CREATE VIEW\s+`?(\w+)`?/i', $statement, $matches);
                $viewName = $matches[1] ?? 'unknown';
                echo "<p>✅ Created view: <strong>$viewName</strong></p>";
            } elseif (stripos($statement, 'CREATE INDEX') !== false) {
                echo "<p>✅ Created index</p>";
            } elseif (stripos($statement, 'DROP TABLE') !== false) {
                preg_match('/DROP TABLE.*?`?(\w+)`?/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "<p>⚠️ Dropped table: <strong>$tableName</strong></p>";
            }
        }
        
        $pdo->commit();
        echo "<p style='color: green;'>✅ <strong>Migration completed successfully!</strong></p>";
        echo "<p>📊 Executed $executed SQL statements</p>";
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw new Exception("Migration failed: " . $e->getMessage());
    }
    
    echo "<h2>4️⃣ Post-Migration Verification</h2>";
    
    // Verify new table structure
    $stmt = $pdo->query("DESCRIBE justificaciones");
    $newColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>📋 <strong>New table structure (" . count($newColumns) . " columns):</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($newColumns as $column) {
        echo "<tr>";
        echo "<td><strong>" . $column['Field'] . "</strong></td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check record count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM justificaciones");
    $finalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>📊 Final record count: <strong>$finalCount</strong></p>";
    
    // Check if config table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'justificaciones_config'");
    if ($stmt->fetch()) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM justificaciones_config");
        $configCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>⚙️ Configuration table created with <strong>$configCount</strong> settings</p>";
    }
    
    // Check if view was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'vw_justificaciones_completa'");
    if ($stmt->fetch()) {
        echo "<p>👁️ View 'vw_justificaciones_completa' created successfully</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
        echo "<p>🔄 Transaction rolled back</p>";
    }
}

echo "<h2>🎯 Next Steps</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid blue;'>";
echo "<p><strong>After migration completion:</strong></p>";
echo "<ol>";
echo "<li>Test the justifications modal in attendance.php</li>";
echo "<li>Verify that btnCrearJustificacion saves data to the new table structure</li>";
echo "<li>Check that all field mappings work correctly</li>";
echo "<li>Verify the enhanced features like approval workflow are available</li>";
echo "</ol>";
echo "</div>";

?>