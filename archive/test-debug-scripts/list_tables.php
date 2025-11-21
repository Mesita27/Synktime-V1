<?php
require_once 'config/database.php';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Tablas disponibles:' . PHP_EOL;
foreach ($tables as $table) {
    echo '  - ' . $table . PHP_EOL;
}

// Buscar tablas relacionadas con usuarios y passwords
echo PHP_EOL . 'Tablas relacionadas con usuarios/passwords:' . PHP_EOL;
foreach ($tables as $table) {
    if (stripos($table, 'user') !== false || stripos($table, 'password') !== false || stripos($table, 'history') !== false) {
        echo "  - $table" . PHP_EOL;
    }
}
?>