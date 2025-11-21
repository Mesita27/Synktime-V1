<?php
include 'config/database.php';

echo "=== Insertar datos de prueba para turno nocturno ===\n";

try {
    // Primero, verificar si ya existen datos
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM asistencia WHERE ID_EMPLEADO = 1231 AND FECHA = "2025-09-18"');
    $existe = $stmt->fetch()['count'];
    
    if ($existe > 0) {
        echo "Ya existen registros para el empleado 1231 en 2025-09-18\n";
    } else {
        // Insertar registros de asistencia para turno nocturno
        // Entrada a las 20:15 (después de las 20:00 programadas)
        $stmt = $pdo->prepare('INSERT INTO asistencia (ID_EMPLEADO, FECHA, HORA, TIPO) VALUES (?, ?, ?, ?)');
        $stmt->execute([1231, '2025-09-18', '20:15:00', 'ENTRADA']);
        
        // Salida nocturna a las 00:30 (antes de las 00:45 programadas y antes de corte 06:00)
        $stmt->execute([1231, '2025-09-18', '00:30:00', 'SALIDA']);
        
        echo "✅ Registros insertados para empleado 1231\n";
    }
    
    // Actualizar el horario para que sea para jueves (día 5)
    $stmt = $pdo->prepare('UPDATE empleado_horario_personalizado SET ID_DIA = 5 WHERE ID_EMPLEADO = 1231 AND ES_TURNO_NOCTURNO = "S"');
    $stmt->execute();
    echo "✅ Horario actualizado para jueves (día 5)\n";
    
    // Verificar los datos insertados
    echo "\n=== Datos insertados ===\n";
    $stmt = $pdo->query('SELECT * FROM asistencia WHERE ID_EMPLEADO = 1231 AND FECHA = "2025-09-18" ORDER BY HORA');
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($registros);
    
    echo "\n=== Horario actualizado ===\n";
    $stmt = $pdo->query('SELECT * FROM empleado_horario_personalizado WHERE ID_EMPLEADO = 1231 AND ES_TURNO_NOCTURNO = "S"');
    $horario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($horario);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>