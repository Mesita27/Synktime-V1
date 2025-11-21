<?php

require_once 'config/database.php';

try {
    $stmt = $conn->query('DESCRIBE ESTABLECIMIENTO');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columnas de ESTABLECIMIENTO:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>