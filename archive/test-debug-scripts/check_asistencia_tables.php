<?php
require_once 'config/database.php';

try {
    // Verificar las tablas de asistencia disponibles
    $sql = "SHOW TABLES LIKE '%ASISTENCIA%'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tablas de asistencia encontradas:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    // Si existe ASISTENCIA_FECHA, verificar su estructura
    if (in_array('ASISTENCIA_FECHA', $tables)) {
        echo "\nEstructura de ASISTENCIA_FECHA:\n";
        $sql = "DESCRIBE ASISTENCIA_FECHA";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>