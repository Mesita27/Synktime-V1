<?php
// Test script for dynamic configuration
require_once "components/python_service_config.php";

echo "<h1>SynkTime Dynamic Configuration Test</h1>";
echo "<h2>Environment: " . (getenv("ENV") ?: "Not set") . "</h2>";

echo "<h3>Python Service Configuration:</h3>";
echo "<pre>";
print_r($config["pythonService"]);
echo "</pre>";

echo "<h3>Database Configuration:</h3>";
echo "<pre>";
print_r($config["database"]);
echo "</pre>";

echo "<h3>Web Configuration:</h3>";
echo "<pre>";
print_r($config["web"]);
echo "</pre>";
?>
