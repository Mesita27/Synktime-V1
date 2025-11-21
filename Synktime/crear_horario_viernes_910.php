<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('config/database.php');

echo "=== CREAR HORARIO VIERNES PARA EMPLEADO 910 ===\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verificar horario actual del empleado 910 para día 4
    echo "1. Horario actual del empleado 910:\n";
    $stmt = $pdo->prepare("
        SELECT 
            ID_EMPLEADO_HORARIO,
            ID_DIA,
            HORA_ENTRADA,
            HORA_SALIDA,
            NOMBRE_TURNO,
            ORDEN_TURNO,
            FECHA_DESDE,
            FECHA_HASTA
        FROM empleado_horario_personalizado 
        WHERE ID_EMPLEADO = 910 
        AND ACTIVO = 'S'
        ORDER BY ID_DIA, ORDEN_TURNO
    ");
    $stmt->execute();
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($horarios as $horario) {
        echo "  - Día {$horario['ID_DIA']}: {$horario['HORA_ENTRADA']} - {$horario['HORA_SALIDA']} ({$horario['NOMBRE_TURNO']}) - Orden: {$horario['ORDEN_TURNO']}\n";
        echo "    ID: {$horario['ID_EMPLEADO_HORARIO']}, Desde: {$horario['FECHA_DESDE']}, Hasta: {$horario['FECHA_HASTA']}\n";
    }
    
    // 2. Copiar horario del día 4 al día 5
    if (!empty($horarios)) {
        $horarioDia4 = $horarios[0]; // Tomar el primer horario (día 4)
        
        // Verificar si ya existe horario para día 5
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM empleado_horario_personalizado 
            WHERE ID_EMPLEADO = 910 
            AND ID_DIA = 5 
            AND ACTIVO = 'S'
        ");
        $stmt->execute();
        $existeViernes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($existeViernes > 0) {
            echo "\n2. ❌ Ya existe horario para día 5 (viernes)\n";
        } else {
            echo "\n2. Creando horario para día 5 (viernes) basado en día 4:\n";
            
            $stmt = $pdo->prepare("
                INSERT INTO empleado_horario_personalizado (
                    ID_EMPLEADO,
                    ID_DIA,
                    HORA_ENTRADA,
                    HORA_SALIDA,
                    NOMBRE_TURNO,
                    ORDEN_TURNO,
                    ACTIVO,
                    FECHA_DESDE,
                    FECHA_HASTA,
                    ES_TURNO_NOCTURNO,
                    TOLERANCIA
                ) VALUES (
                    910,
                    5,
                    ?,
                    ?,
                    ?,
                    ?,
                    'S',
                    ?,
                    ?,
                    'N',
                    0
                )
            ");
            
            $stmt->execute([
                $horarioDia4['HORA_ENTRADA'],
                $horarioDia4['HORA_SALIDA'],
                $horarioDia4['NOMBRE_TURNO'],
                $horarioDia4['ORDEN_TURNO'],
                $horarioDia4['FECHA_DESDE'],
                $horarioDia4['FECHA_HASTA']
            ]);
            
            echo "✅ Horario creado para día 5 (viernes): {$horarioDia4['HORA_ENTRADA']} - {$horarioDia4['HORA_SALIDA']} ({$horarioDia4['NOMBRE_TURNO']})\n";
        }
    } else {
        echo "❌ No se encontraron horarios para copiar\n";
    }
    
    // 3. Verificar resultado final
    echo "\n3. Horarios finales del empleado 910:\n";
    $stmt = $pdo->prepare("
        SELECT 
            ID_DIA,
            HORA_ENTRADA,
            HORA_SALIDA,
            NOMBRE_TURNO,
            ORDEN_TURNO
        FROM empleado_horario_personalizado 
        WHERE ID_EMPLEADO = 910 
        AND ACTIVO = 'S'
        ORDER BY ID_DIA, ORDEN_TURNO
    ");
    $stmt->execute();
    $horariosFinales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($horariosFinales as $horario) {
        echo "  - Día {$horario['ID_DIA']}: {$horario['HORA_ENTRADA']} - {$horario['HORA_SALIDA']} ({$horario['NOMBRE_TURNO']}) - Orden: {$horario['ORDEN_TURNO']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>