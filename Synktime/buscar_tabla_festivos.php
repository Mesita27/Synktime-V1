<?php
require_once 'config/database.php';

echo "🔍 BUSCANDO TABLA DE FESTIVOS:\n";

// Buscar tablas que contengan holiday, festivo, etc.
$patterns = ['%holiday%', '%festiv%', '%holy%', '%civic%'];

foreach ($patterns as $pattern) {
    echo "\nBuscando patrón: $pattern\n";
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$pattern]);
    $tables = $stmt->fetchAll();
    
    if ($tables) {
        foreach($tables as $table) {
            echo "   ✅ Encontrada: " . array_values($table)[0] . "\n";
        }
    } else {
        echo "   ❌ Sin resultados\n";
    }
}

// Mostrar todas las tablas para ver si hay algo similar
echo "\n📋 TODAS LAS TABLAS DISPONIBLES:\n";
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll();

foreach($tables as $table) {
    $tableName = array_values($table)[0];
    echo "   - $tableName\n";
}
?>