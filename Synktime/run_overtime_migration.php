<?php
/**
 * Migration script to create overtime approval table
 */

require_once __DIR__ . '/config/database.php';

try {
    global $conn;

    // Read and execute the migration SQL
    $sql = file_get_contents(__DIR__ . '/database/migrations/create_horas_extras_aprobacion_table.sql');

    // Split SQL into individual statements and execute them
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $conn->exec($statement);
        }
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "Table 'horas_extras_aprobacion' has been created.\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>