<?php
require_once 'config/database.php';
try {
    echo "Agregando campos faltantes a la tabla biometric_data...\n";

    // Agregar campos faltantes uno por uno
    $alter_queries = [
        "ALTER TABLE biometric_data ADD COLUMN EMBEDDING_DATA LONGTEXT AFTER BIOMETRIC_DATA",
        "ALTER TABLE biometric_data ADD COLUMN QUALITY_SCORE DECIMAL(3,2) DEFAULT 0.0 AFTER EMBEDDING_DATA",
        "ALTER TABLE biometric_data ADD COLUMN PYTHON_SERVICE_ID VARCHAR(100) AFTER QUALITY_SCORE",
        "ALTER TABLE biometric_data ADD COLUMN DEVICE_ID VARCHAR(100) AFTER PYTHON_SERVICE_ID",
        "ALTER TABLE biometric_data ADD COLUMN TEMPLATE_VERSION VARCHAR(20) DEFAULT '1.0' AFTER DEVICE_ID"
    ];

    foreach ($alter_queries as $query) {
        try {
            $pdo->exec($query);
            echo "✓ Ejecutado: " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "⚠ Error en query: " . $e->getMessage() . "\n";
        }
    }

    echo "\nVerificando estructura final de la tabla...\n";
    $stmt = $pdo->query('DESCRIBE biometric_data');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Campos en biometric_data después de la migración:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} {$col['Null']} {$col['Default']}\n";
    }

    echo "\n¡Migración completada!\n";

} catch (Exception $e) {
    echo "Error general: {$e->getMessage()}\n";
}
?>
