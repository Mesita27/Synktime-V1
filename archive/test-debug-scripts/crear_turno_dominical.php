<?php
require_once 'config/database.php';

echo "=== CREANDO TURNO NOCTURNO DOMINICAL ===\n\n";

try {
    // Crear un turno nocturno que inicia domingo
    $fechaDomingo = '2025-09-21'; // Domingo
    $fechaLunes = '2025-09-22';   // Lunes (día siguiente)
    
    // Agregar entrada dominical
    $stmt = $conn->prepare("
        INSERT IGNORE INTO asistencia (ID_EMPLEADO, FECHA, HORA, TIPO) 
        VALUES (1231, ?, '22:00', 'ENTRADA')
    ");
    $stmt->execute([$fechaDomingo]);
    echo "✅ Entrada dominical agregada: $fechaDomingo 22:00\n";
    
    // Agregar salida del día siguiente
    $stmt = $conn->prepare("
        INSERT IGNORE INTO asistencia (ID_EMPLEADO, FECHA, HORA, TIPO) 
        VALUES (1231, ?, '06:00', 'SALIDA')
    ");
    $stmt->execute([$fechaLunes]);
    echo "✅ Salida agregada: $fechaLunes 06:00\n";
    
    echo "\nEste turno debería ser clasificado como DOMINICAL NOCTURNO\n";
    echo "porque inicia en domingo, aunque termine en lunes.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>