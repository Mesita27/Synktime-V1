<?php
require_once 'config/database.php';

echo "LIMPIANDO HORAS EXTRAS EXISTENTES PARA REGENERAR CON TIPOS CORRECTOS\n";

// Eliminar horas extras existentes para el empleado 100 en la fecha específica
$stmt = $conn->prepare('DELETE FROM horas_extras_aprobacion WHERE ID_EMPLEADO = 100 AND FECHA = "2025-09-28"');
$result = $stmt->execute();

echo "Horas extras eliminadas: " . $stmt->rowCount() . "\n";

echo "Ahora ejecuta la API get-horas.php para regenerar las horas extras con tipos correctos.\n";
?>