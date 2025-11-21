<?php
/**
 * Script para verificar la estructura de las tablas necesarias
 */

require_once __DIR__ . '/config/database.php';

echo "=== ESTRUCTURA DE TABLAS ===\n\n";

$tablas = ['HORARIO', 'EMPLEADO_HORARIO', 'empleado_horario_personalizado'];

foreach ($tablas as $tabla) {
    echo "📋 TABLA: $tabla\n";
    try {
        $stmt = $conn->query("DESCRIBE $tabla");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columnas as $col) {
            echo "   - {$col['Field']} ({$col['Type']})";
            if ($col['Null'] === 'NO') echo " NOT NULL";
            if ($col['Key']) echo " [{$col['Key']}]";
            if ($col['Default'] !== null) echo " DEFAULT '{$col['Default']}'";
            if ($col['Extra']) echo " {$col['Extra']}";
            echo "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>