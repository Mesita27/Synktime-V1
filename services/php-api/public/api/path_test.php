<?php
echo "Current working directory: " . getcwd() . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script path: " . __FILE__ . "\n";
echo "Script directory: " . dirname(__FILE__) . "\n";

$configPath = '../../config/database.php';
echo "Trying config path: $configPath\n";
echo "Real path: " . realpath($configPath) . "\n";
echo "File exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "\n";

$configPath2 = __DIR__ . '/../../config/database.php';
echo "Alternative config path: $configPath2\n";
echo "Real path: " . realpath($configPath2) . "\n";
echo "File exists: " . (file_exists($configPath2) ? 'YES' : 'NO') . "\n";
?>
