<?php
require_once __DIR__ . '/config/database.php';

echo "Horarios personalizados existentes:\n";
try {
    $stmt = $conn->query('SELECT ID_EMPLEADO_HORARIO, ID_EMPLEADO, NOMBRE_TURNO, ID_DIA, FECHA_DESDE, FECHA_HASTA FROM empleado_horario_personalizado LIMIT 10');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['ID_EMPLEADO_HORARIO']}, Empleado: {$row['ID_EMPLEADO']}, Nombre: {$row['NOMBRE_TURNO']}, Dia: {$row['ID_DIA']}, Desde: {$row['FECHA_DESDE']}, Hasta: {$row['FECHA_HASTA']}\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>