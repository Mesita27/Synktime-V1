<?php
require_once 'config/database.php';

try {
    // Insertar sedes de ejemplo
    $stmt = $pdo->prepare("INSERT IGNORE INTO sedes (nombre, direccion, telefono, email) VALUES (?, ?, ?, ?)");
    
    $sedes = [
        ['Sede Principal', 'Av. Principal 123, Ciudad', '+57 1 234-5678', 'principal@empresa.com'],
        ['Sede Norte', 'Calle 80 #45-67, Ciudad', '+57 1 234-5679', 'norte@empresa.com'],
        ['Sede Sur', 'Carrera 30 #20-15, Ciudad', '+57 1 234-5680', 'sur@empresa.com']
    ];
    
    foreach ($sedes as $sede) {
        $stmt->execute($sede);
    }
    
    echo "Sedes de ejemplo insertadas.\n";
    
    // Obtener IDs de sedes
    $stmt = $pdo->prepare("SELECT id, nombre FROM sedes ORDER BY id");
    $stmt->execute();
    $sedesDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Insertar establecimientos de ejemplo
    $stmt = $pdo->prepare("INSERT IGNORE INTO establecimientos (nombre, descripcion, sede_id) VALUES (?, ?, ?)");
    
    foreach ($sedesDb as $sede) {
        $establecimientos = [
            ["Administración", "Área administrativa principal", $sede['id']],
            ["Operaciones", "Área de operaciones y producción", $sede['id']],
            ["Recursos Humanos", "Departamento de recursos humanos", $sede['id']]
        ];
        
        foreach ($establecimientos as $est) {
            $stmt->execute($est);
        }
    }
    
    echo "Establecimientos de ejemplo insertados.\n";
    
    // Mostrar resumen
    $stmt = $pdo->prepare("
        SELECT 
            s.nombre as sede,
            COUNT(e.id) as total_establecimientos
        FROM sedes s
        LEFT JOIN establecimientos e ON s.id = e.sede_id
        GROUP BY s.id, s.nombre
    ");
    $stmt->execute();
    $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nResumen de datos insertados:\n";
    foreach ($resumen as $row) {
        echo "- {$row['sede']}: {$row['total_establecimientos']} establecimientos\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>