<?php
require_once 'config/database.php';
try {
    $stmt = $conn->query('DESCRIBE justificaciones');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Columnas de justificaciones:' . PHP_EOL;
    foreach ($columns as $col) {
        echo $col['Field'] . ' (' . $col['Type'] . ')' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
