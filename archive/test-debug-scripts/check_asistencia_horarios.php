<?php
require_once __DIR__ . '/config/database.php';

echo "=== ESTRUCTURA TABLA ASISTENCIA ===\n";
try {
    $stmt = $conn->query('DESCRIBE ASISTENCIA');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        echo "- {$col['Field']} ({$col['Type']})";
        if ($col['Null'] === 'NO') echo " NOT NULL";
        if ($col['Key']) echo " [{$col['Key']}]";
        echo "\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "\n=== VERIFICANDO DATOS DE PRUEBA ===\n";
try {
    $sql = "SELECT ID_EMPLEADO, FECHA, ID_HORARIO, ID_EMPLEADO_HORARIO, TIPO_HORARIO
            FROM ASISTENCIA
            WHERE ID_EMPLEADO = 1 AND FECHA IN ('2025-10-02', '2025-10-04')
            ORDER BY FECHA";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($asistencias as $a) {
        echo "Fecha: {$a['FECHA']}\n";
        echo "  ID_HORARIO: " . ($a['ID_HORARIO'] ?: 'NULL') . "\n";
        echo "  ID_EMPLEADO_HORARIO: " . ($a['ID_EMPLEADO_HORARIO'] ?: 'NULL') . "\n";
        echo "  TIPO_HORARIO: " . ($a['TIPO_HORARIO'] ?: 'NULL') . "\n\n";
    }
} catch(Exception $e) {
    echo "Error consultando asistencias: " . $e->getMessage();
}
?>