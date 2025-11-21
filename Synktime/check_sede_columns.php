<?php

require_once 'config/database.php';

try {
    $stmt = $conn->query('DESCRIBE SEDE');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columnas de SEDE:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>