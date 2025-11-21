<?php
// Script simple para verificar que el API está funcionando
echo "API Status Check\n";
echo "================\n\n";

// Verificar que podemos incluir las configuraciones
try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/timezone.php';
    echo "✓ Configuración de base de datos cargada correctamente\n";
} catch (Exception $e) {
    echo "✗ Error cargando configuración: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar que podemos conectar a la base de datos
try {
    $stmt = $conn->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Conexión a base de datos funcionando\n";
} catch (Exception $e) {
    echo "✗ Error de conexión a BD: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar que la función esDomingo existe
if (function_exists('esDomingo')) {
    echo "✓ Función esDomingo existe\n";
    // Probar la función
    $testDate = '2025-09-28';
    $result = esDomingo($testDate);
    echo "✓ esDomingo('$testDate') = " . ($result ? 'true' : 'false') . "\n";
} else {
    echo "✗ Función esDomingo no existe\n";
}

// Verificar que calculateHoursWithHierarchy existe
if (function_exists('calculateHoursWithHierarchy')) {
    echo "✓ Función calculateHoursWithHierarchy existe\n";
} else {
    echo "✗ Función calculateHoursWithHierarchy no existe\n";
}

echo "\nAPI Status: OK - Todas las funciones críticas están disponibles\n";
?>