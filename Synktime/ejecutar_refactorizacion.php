<?php
try {
    require_once 'config/database.php';
    $sql = file_get_contents('refactorizar_tabla_justificaciones.sql');
    
    // Dividir en statements individuales
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "🔧 EJECUTANDO REFACTORIZACIÓN DE TABLA JUSTIFICACIONES\n";
    echo "=====================================================\n\n";
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        // Ejecutar statement
        try {
            $pdo->exec($statement);
            $firstWords = implode(' ', array_slice(explode(' ', $statement), 0, 3));
            echo "✅ Ejecutado: $firstWords...\n";
        } catch (Exception $e) {
            $firstWords = implode(' ', array_slice(explode(' ', $statement), 0, 3));
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), "doesn't exist") === false) {
                echo "⚠️  $firstWords: " . $e->getMessage() . "\n";
            } else {
                echo "ℹ️  $firstWords: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n🎉 REFACTORIZACIÓN COMPLETADA\n";
    
    // Verificar que la tabla se creó correctamente
    echo "\n📊 VERIFICANDO NUEVA ESTRUCTURA:\n";
    $stmt = $pdo->query("DESCRIBE justificaciones");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) FROM justificaciones");
    $count = $stmt->fetchColumn();
    echo "\n📈 Total registros: $count\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>