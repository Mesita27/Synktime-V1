<?php
echo "=== INSERTAR DATO DE PRUEBA JUSTIFICACIONES ===\n\n";

require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insertar justificación de prueba
    $sql = "INSERT INTO justificaciones (
        empleado_id, 
        fecha_falta, 
        motivo, 
        detalle_adicional, 
        tipo_falta, 
        horas_programadas,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        1, // empleado_id (Juan Pérez)
        '2025-09-16', // fecha_falta (ayer)
        'Cita médica de rutina',
        'Cita médica programada con antelación en el centro de salud',
        'completa',
        8.00
    ]);
    
    if ($result) {
        echo "✅ Justificación de prueba insertada correctamente\n";
        echo "   Empleado ID: 1 (Juan Pérez)\n";
        echo "   Fecha falta: 2025-09-16\n";
        echo "   Motivo: Cita médica de rutina\n";
        echo "   Tipo: Falta completa\n";
        
        // Verificar que se insertó
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM justificaciones");
        $total = $stmt->fetch()['total'];
        echo "   Total justificaciones en BD: $total\n";
        
    } else {
        echo "❌ Error al insertar justificación de prueba\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
?>