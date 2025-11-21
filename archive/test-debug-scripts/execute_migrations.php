<?php
// Ejecutar migraciones para el módulo de configuración
require_once 'config/database.php';

echo "=== Ejecutando Migraciones del Módulo de Configuración ===\n";

try {
    // Leer el archivo SQL
    $sql = file_get_contents('database/migrations/configuracion_schema.sql');
    
    // Dividir en statements individuales (manejo más sofisticado)
    $statements = [];
    $current = '';
    $lines = explode("\n", $sql);
    $inProcedure = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Ignorar comentarios y líneas vacías
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        // Detectar inicio de procedimiento/función
        if (stripos($line, 'DELIMITER') !== false) {
            if (stripos($line, '//') !== false) {
                $inProcedure = true;
            } else {
                $inProcedure = false;
            }
            continue;
        }
        
        $current .= $line . "\n";
        
        // Si estamos en un procedimiento, buscar END seguido de //
        if ($inProcedure) {
            if (stripos($line, 'END //') !== false) {
                $statements[] = trim($current);
                $current = '';
            }
        } else {
            // Statement normal termina con ;
            if (substr($line, -1) === ';') {
                $statements[] = trim($current);
                $current = '';
            }
        }
    }
    
    // Agregar último statement si queda algo
    if (!empty(trim($current))) {
        $statements[] = trim($current);
    }
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            echo "✅ Ejecutado: " . substr($statement, 0, 60) . "...\n";
            $executed++;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "   Statement: " . substr($statement, 0, 100) . "...\n";
            $errors++;
        }
    }
    
    echo "\n=== Resumen ===\n";
    echo "Statements ejecutados: $executed\n";
    echo "Errores: $errors\n";
    
    if ($errors === 0) {
        echo "🎉 Todas las migraciones se ejecutaron correctamente\n";
    } else {
        echo "⚠️ Algunas migraciones fallaron, revisa los errores arriba\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}

?>