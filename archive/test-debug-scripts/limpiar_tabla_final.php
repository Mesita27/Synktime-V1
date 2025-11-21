<?php
require_once 'config/database.php';
try {
    echo "🧹 LIMPIEZA FINAL DE TABLA JUSTIFICACIONES\n";
    echo "=========================================\n\n";
    
    echo "Eliminando restricciones foreign key...\n";
    $pdo->exec('ALTER TABLE justificaciones DROP FOREIGN KEY fk_justificaciones_aprobador');
    echo "✅ Foreign key aprobador eliminada\n";
    
    echo "Eliminando columna aprobada_por...\n";
    $pdo->exec('ALTER TABLE justificaciones DROP COLUMN aprobada_por');
    echo "✅ Columna aprobada_por eliminada\n";
    
    echo "\n📊 ESTRUCTURA FINAL LIMPIA:\n";
    $stmt = $pdo->query("DESCRIBE justificaciones");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['Field']}: {$row['Type']}\n";
    }
    
    echo "\n🎉 LIMPIEZA COMPLETADA - TABLA LISTA PARA USO\n";
} catch (Exception $e) {
    echo "⚠️ " . $e->getMessage() . "\n";
}
?>