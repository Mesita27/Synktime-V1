<?php
require_once 'config/database.php';
$stmt = $conn->query('DESCRIBE dia_semana');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) {
    echo $col['Field'] . ' - ' . $col['Type'] . PHP_EOL;
}
?>