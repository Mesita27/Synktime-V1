<?php
require_once 'config/database.php';

echo "Estructura de la tabla ASISTENCIA:\n";
echo str_repeat("=", 40) . "\n";

$stmt = $conn->query('DESCRIBE ASISTENCIA');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-20s %-15s\n", $row['Field'], $row['Type']);
}

echo "\n\nAlgunos registros de ejemplo:\n";
echo str_repeat("=", 40) . "\n";

$stmt = $conn->query('SELECT ID_ASISTENCIA, ID_EMPLEADO, FECHA, HORA, TIPO FROM ASISTENCIA LIMIT 10');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("ID: %d, Empleado: %d, Fecha: %s, Hora: %s, Tipo: %s\n",
        $row['ID_ASISTENCIA'], $row['ID_EMPLEADO'], $row['FECHA'], $row['HORA'], $row['TIPO']);
}
?>