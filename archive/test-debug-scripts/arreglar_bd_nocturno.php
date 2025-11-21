<?php
/**
 * ARREGLAR ESTRUCTURA DE BD PARA TURNOS NOCTURNOS
 */

require_once 'config/database.php';

echo "🔧 ARREGLANDO ESTRUCTURA DE BD PARA TURNOS NOCTURNOS\n";
echo "=" . str_repeat("=", 55) . "\n\n";

// 1. Agregar columna ES_TURNO_NOCTURNO
echo "1️⃣  AGREGANDO COLUMNA ES_TURNO_NOCTURNO:\n";

try {
    $sql = "ALTER TABLE horario ADD COLUMN ES_TURNO_NOCTURNO CHAR(1) DEFAULT 'N'";
    $pdo->exec($sql);
    echo "   ✅ Columna ES_TURNO_NOCTURNO agregada exitosamente\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "   ℹ️  Columna ES_TURNO_NOCTURNO ya existe\n";
    } else {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}

// 2. Agregar columna ACTIVO a empleado_horario
echo "\n2️⃣  AGREGANDO COLUMNA ACTIVO A empleado_horario:\n";

try {
    $sql = "ALTER TABLE empleado_horario ADD COLUMN ACTIVO CHAR(1) DEFAULT 'S'";
    $pdo->exec($sql);
    echo "   ✅ Columna ACTIVO agregada exitosamente\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "   ℹ️  Columna ACTIVO ya existe\n";
    } else {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}

// 3. Crear un horario nocturno de prueba
echo "\n3️⃣  CREANDO HORARIO NOCTURNO DE PRUEBA:\n";

// Primero verificar si ya existe
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM horario WHERE NOMBRE = 'Turno Nocturno Test'");
$stmt->execute();
$exists = $stmt->fetch()['count'] > 0;

if (!$exists) {
    $sql = "
        INSERT INTO horario (ID_ESTABLECIMIENTO, NOMBRE, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, ES_TURNO_NOCTURNO)
        VALUES (3, 'Turno Nocturno Test', '22:00', '06:00', 10, 'S')
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $nuevoHorarioId = $pdo->lastInsertId();
    echo "   ✅ Horario nocturno creado con ID: $nuevoHorarioId\n";
} else {
    $stmt = $pdo->prepare("SELECT ID_HORARIO FROM horario WHERE NOMBRE = 'Turno Nocturno Test'");
    $stmt->execute();
    $nuevoHorarioId = $stmt->fetch()['ID_HORARIO'];
    echo "   ℹ️  Horario nocturno ya existe con ID: $nuevoHorarioId\n";
}

// 4. Asignar horario nocturno al empleado 100
echo "\n4️⃣  ASIGNANDO HORARIO NOCTURNO AL EMPLEADO 100:\n";

// Primero desactivar horarios anteriores
$sql = "UPDATE empleado_horario SET ACTIVO = 'N' WHERE ID_EMPLEADO = 100";
$pdo->exec($sql);

// Verificar si ya tiene asignación nocturna
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM empleado_horario 
    WHERE ID_EMPLEADO = 100 
    AND ID_HORARIO = ?
");
$stmt->execute([$nuevoHorarioId]);
$yaAsignado = $stmt->fetch()['count'] > 0;

if (!$yaAsignado) {
    $sql = "
        INSERT INTO empleado_horario (ID_EMPLEADO, ID_HORARIO, FECHA_DESDE, ACTIVO)
        VALUES (100, ?, CURDATE(), 'S')
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevoHorarioId]);
    echo "   ✅ Horario nocturno asignado al empleado 100\n";
} else {
    // Activar la asignación existente
    $sql = "
        UPDATE empleado_horario 
        SET ACTIVO = 'S', FECHA_DESDE = CURDATE() 
        WHERE ID_EMPLEADO = 100 AND ID_HORARIO = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevoHorarioId]);
    echo "   ✅ Horario nocturno activado para empleado 100\n";
}

// 5. Verificar resultado final
echo "\n5️⃣  VERIFICACIÓN FINAL:\n";

$sql = "
    SELECT eh.*, h.NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA, h.ES_TURNO_NOCTURNO
    FROM empleado_horario eh
    JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
    WHERE eh.ID_EMPLEADO = 100
    AND eh.ACTIVO = 'S'
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$horarioActual = $stmt->fetch(PDO::FETCH_ASSOC);

if ($horarioActual) {
    echo "   ✅ Configuración final del empleado 100:\n";
    echo "      - Horario: {$horarioActual['NOMBRE']}\n";
    echo "      - Horas: {$horarioActual['HORA_ENTRADA']} - {$horarioActual['HORA_SALIDA']}\n";
    echo "      - Es nocturno: {$horarioActual['ES_TURNO_NOCTURNO']}\n";
    echo "      - Activo: {$horarioActual['ACTIVO']}\n";
    
    if ($horarioActual['ES_TURNO_NOCTURNO'] === 'S') {
        echo "   🌙 ¡PERFECTO! Empleado 100 ahora tiene turno nocturno\n";
    }
} else {
    echo "   ❌ Error: No se pudo configurar el horario\n";
}

echo "\n6️⃣  ESTRUCTURA FINAL DE TABLA HORARIO:\n";
$stmt = $pdo->query('DESCRIBE horario');
$cols = $stmt->fetchAll();
foreach($cols as $col) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
}

echo "\n" . str_repeat("=", 55) . "\n";
echo "🎯 RESUMEN:\n";
echo "✅ Base de datos actualizada con soporte para turnos nocturnos\n";
echo "✅ Empleado 100 configurado con horario nocturno (22:00-06:00)\n";
echo "✅ Ahora el código debería funcionar correctamente\n";
echo "\n🚀 SIGUIENTE PASO: Probar el registro de salida nocturna\n";
?>