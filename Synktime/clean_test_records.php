<?php
require_once __DIR__ . '/config/database.php';

echo "=== LIMPIEZA DE REGISTROS DE HOY PARA TESTING ===\n\n";

$fecha_hoy = date('Y-m-d');

try {
    // Mostrar registros actuales del empleado 100 de hoy
    $stmt = $conn->prepare("SELECT * FROM asistencia WHERE ID_EMPLEADO = 100 AND FECHA = ?");
    $stmt->execute([$fecha_hoy]);
    $registros = $stmt->fetchAll();
    
    echo "Registros encontrados del empleado 100 para hoy ($fecha_hoy): " . count($registros) . "\n";
    
    foreach ($registros as $registro) {
        echo "  - ID: {$registro['ID_ASISTENCIA']}, Tipo: {$registro['TIPO']}, Hora: {$registro['HORA']}\n";
    }
    
    if (count($registros) > 0) {
        // Eliminar registros de hoy para testing
        $stmt = $conn->prepare("DELETE FROM asistencia WHERE ID_EMPLEADO = 100 AND FECHA = ?");
        $stmt->execute([$fecha_hoy]);
        
        echo "\n✅ Registros eliminados: " . $stmt->rowCount() . "\n";
    } else {
        echo "\n✅ No hay registros para eliminar\n";
    }
    
    echo "\n✅ Base de datos lista para testing limpio\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN LIMPIEZA ===\n";
?>