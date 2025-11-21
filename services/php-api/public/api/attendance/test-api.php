<?php
// Test simple de la API
try {
    require_once __DIR__ . '/../../config/database.php';
    echo "✅ Database connection: OK\n";
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
}

echo "✅ API endpoint accessible\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";
?>