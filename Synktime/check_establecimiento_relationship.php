<?php
require_once __DIR__ . '/config/database.php';

try {
    // Verificar si ESTABLECIMIENTO tiene columna ID_EMPRESA
    $sql = "DESCRIBE ESTABLECIMIENTO";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columnas de ESTABLECIMIENTO:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . "\n";
    }

    // Verificar la relación EMPLEADO -> ESTABLECIMIENTO -> SEDE -> EMPRESA
    echo "\nVerificando relación de un empleado de prueba:\n";
    $sqlTest = "
        SELECT
            e.ID_EMPLEADO,
            e.NOMBRE,
            est.ID_ESTABLECIMIENTO,
            est.NOMBRE as establecimiento,
            s.ID_SEDE,
            s.NOMBRE as sede,
            s.ID_EMPRESA
        FROM EMPLEADO e
        INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = 100
        LIMIT 1
    ";

    $stmtTest = $conn->prepare($sqlTest);
    $stmtTest->execute();
    $result = $stmtTest->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "Empleado ID: " . $result['ID_EMPLEADO'] . "\n";
        echo "Nombre: " . $result['NOMBRE'] . "\n";
        echo "Establecimiento: " . $result['establecimiento'] . "\n";
        echo "Sede: " . $result['sede'] . "\n";
        echo "ID Empresa: " . $result['ID_EMPRESA'] . "\n";
    } else {
        echo "No se encontró el empleado de prueba\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>