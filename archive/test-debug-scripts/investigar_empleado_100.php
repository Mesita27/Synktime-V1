<?php
require_once 'config/database.php';

echo "🔍 INVESTIGACIÓN COMPLETA DEL EMPLEADO 100\n";
echo "=" . str_repeat("=", 45) . "\n\n";

echo "📊 DATOS DEL EMPLEADO 100:\n";
$sql = "SELECT * FROM empleado WHERE ID_EMPLEADO = 100";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

if ($empleado) {
    echo "   ✅ Empleado encontrado:\n";
    foreach ($empleado as $key => $value) {
        echo "      $key: $value\n";
    }
} else {
    echo "   ❌ Empleado 100 no encontrado\n";
    exit;
}

echo "\n📋 HORARIOS ASIGNADOS AL EMPLEADO 100:\n";
$sql = "
    SELECT eh.*, h.NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA
    FROM empleado_horario eh
    JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
    WHERE eh.ID_EMPLEADO = 100
    ORDER BY eh.FECHA_DESDE DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($horarios) {
    echo "   📅 Horarios encontrados:\n";
    foreach ($horarios as $h) {
        echo "      - ID_HORARIO: {$h['ID_HORARIO']} | {$h['NOMBRE']} | {$h['HORA_ENTRADA']}-{$h['HORA_SALIDA']} | Desde: {$h['FECHA_DESDE']} | Hasta: " . ($h['FECHA_HASTA'] ?? 'SIN LÍMITE') . "\n";
    }
} else {
    echo "   ❌ No se encontraron horarios asignados\n";
}

echo "\n🕐 VERIFICANDO COLUMNA ES_TURNO_NOCTURNO:\n";
$sql = "SHOW COLUMNS FROM horario LIKE '%nocturno%'";
$stmt = $pdo->query($sql);
$columnas = $stmt->fetchAll();

if ($columnas) {
    echo "   ✅ Columnas relacionadas con nocturno:\n";
    foreach ($columnas as $col) {
        echo "      - {$col['Field']}\n";
    }
} else {
    echo "   ❌ NO existe columna ES_TURNO_NOCTURNO\n";
    
    // Mostrar todas las columnas
    echo "\n📋 TODAS LAS COLUMNAS DE HORARIO:\n";
    $stmt = $pdo->query('DESCRIBE horario');
    $cols = $stmt->fetchAll();
    foreach($cols as $col) {
        echo "      - {$col['Field']} ({$col['Type']})\n";
    }
}

echo "\n🔍 BUSCANDO LÓGICA DE TURNO NOCTURNO:\n";
echo "   Verificando si existe en otra tabla...\n";

// Buscar en otras tablas
$tablas = ['empleado', 'asistencia', 'establecimiento'];
foreach ($tablas as $tabla) {
    echo "   📋 Tabla $tabla:\n";
    try {
        $sql = "SHOW COLUMNS FROM $tabla LIKE '%nocturno%'";
        $stmt = $pdo->query($sql);
        $cols = $stmt->fetchAll();
        
        if ($cols) {
            foreach ($cols as $col) {
                echo "      ✅ {$col['Field']}\n";
            }
        } else {
            echo "      - Sin columnas de nocturno\n";
        }
    } catch (Exception $e) {
        echo "      ❌ Error: {$e->getMessage()}\n";
    }
}

echo "\n🤔 ANÁLISIS DE HORARIOS:\n";
if ($horarios) {
    $horarioActual = $horarios[0]; // El más reciente
    $horaEntrada = $horarioActual['HORA_ENTRADA'];
    $horaSalida = $horarioActual['HORA_SALIDA'];
    
    echo "   Horario actual: {$horaEntrada} - {$horaSalida}\n";
    
    // Convertir a formato comparable
    $entradaInt = (int)str_replace(':', '', $horaEntrada);
    $salidaInt = (int)str_replace(':', '', $horaSalida);
    
    if ($salidaInt < $entradaInt) {
        echo "   🌙 DETECTADO: Turno nocturno (salida menor que entrada)\n";
        echo "   ⚠️  PROBLEMA: No hay columna ES_TURNO_NOCTURNO en BD\n";
    } else {
        echo "   ☀️  Turno diurno normal\n";
    }
}

echo "\n" . str_repeat("=", 45) . "\n";
?>