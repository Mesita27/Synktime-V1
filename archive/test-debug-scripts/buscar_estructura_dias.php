<?php
require_once 'config/database.php';

echo "🔍 BÚSQUEDA DE TABLAS RELACIONADAS CON DÍAS\n";
echo "==========================================\n\n";

// Mostrar todas las tablas
$stmt = $pdo->query('SHOW TABLES');
$tables = [];
while($row = $stmt->fetch()) {
    $tables[] = $row[0];
    if(strpos(strtolower($row[0]), 'dia') !== false) {
        echo "Tabla relacionada con días: " . $row[0] . "\n";
    }
}

echo "\n📋 TODAS LAS TABLAS:\n";
foreach($tables as $table) {
    echo "- $table\n";
}

// Verificar datos únicos de ID_DIA en empleado_horario_personalizado
echo "\n🔍 VALORES ÚNICOS DE ID_DIA:\n";
$stmt = $pdo->query("SELECT DISTINCT ID_DIA FROM empleado_horario_personalizado ORDER BY ID_DIA");
while($row = $stmt->fetch()) {
    echo "ID_DIA: " . $row['ID_DIA'] . "\n";
}

// Revisar algunos registros para entender la estructura
echo "\n📊 MUESTRA DE empleado_horario_personalizado:\n";
$stmt = $pdo->query("
    SELECT 
        ehp.ID_EMPLEADO,
        ehp.ID_DIA,
        ehp.HORA_ENTRADA,
        ehp.HORA_SALIDA,
        ehp.NOMBRE_TURNO,
        ehp.ACTIVO,
        CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado
    FROM empleado_horario_personalizado ehp
    JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
    WHERE ehp.ACTIVO = 'S'
    LIMIT 10
");

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Empleado: {$row['empleado']}\n";
    echo "  ID_DIA: {$row['ID_DIA']}\n";
    echo "  Horario: {$row['HORA_ENTRADA']} - {$row['HORA_SALIDA']}\n";
    echo "  Turno: {$row['NOMBRE_TURNO']}\n\n";
}
?>