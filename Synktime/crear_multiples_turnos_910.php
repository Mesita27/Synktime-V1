<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('config/database.php');

echo "=== CREAR MÚLTIPLES TURNOS PARA EMPLEADO 910 ===\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fecha de hoy
    $fecha_hoy = date('Y-m-d');
    echo "Trabajando con fecha: $fecha_hoy\n";
    
    // 1. Crear horarios múltiples para el empleado 910 (día 5 - viernes)
    echo "\n1. Creando horarios múltiples para empleado 910:\n";
    
    // Eliminar horarios existentes del día 5 para empezar limpio
    $stmt = $pdo->prepare("DELETE FROM empleado_horario_personalizado WHERE ID_EMPLEADO = 910 AND ID_DIA = 5");
    $stmt->execute();
    echo "✅ Horarios anteriores del día 5 eliminados\n";
    
    // Crear 3 turnos para el día
    $turnos = [
        ['nombre' => 'Turno Mañana', 'entrada' => '08:00:00', 'salida' => '12:00:00', 'orden' => 1],
        ['nombre' => 'Turno Tarde', 'entrada' => '14:00:00', 'salida' => '18:00:00', 'orden' => 2],
        ['nombre' => 'Turno Noche', 'entrada' => '20:00:00', 'salida' => '23:00:00', 'orden' => 3]
    ];
    
    foreach ($turnos as $turno) {
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
                '2024-12-01',
                'N',
                0
            )
        ");
        
        $stmt->execute([
            $turno['entrada'],
            $turno['salida'],
            $turno['nombre'],
            $turno['orden']
        ]);
        
        echo "✅ Creado: {$turno['nombre']} ({$turno['entrada']} - {$turno['salida']}) - Orden: {$turno['orden']}\n";
    }
    
    // 2. Verificar horarios creados
    echo "\n2. Horarios creados para empleado 910 (día 5):\n";
    $stmt = $pdo->prepare("
        SELECT 
            ID_EMPLEADO_HORARIO,
            HORA_ENTRADA,
            HORA_SALIDA,
            NOMBRE_TURNO,
            ORDEN_TURNO
        FROM empleado_horario_personalizado 
        WHERE ID_EMPLEADO = 910 
        AND ID_DIA = 5
        AND ACTIVO = 'S'
        ORDER BY ORDEN_TURNO
    ");
    $stmt->execute();
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($horarios as $horario) {
        echo "  - ID {$horario['ID_EMPLEADO_HORARIO']}: {$horario['NOMBRE_TURNO']} ({$horario['HORA_ENTRADA']} - {$horario['HORA_SALIDA']}) - Orden: {$horario['ORDEN_TURNO']}\n";
    }
    
    // 3. Crear registros de asistencia para múltiples turnos
    echo "\n3. Creando registros de asistencia para múltiples turnos:\n";
    
    // Limpiar asistencias existentes del día
    $stmt = $pdo->prepare("DELETE FROM asistencia WHERE ID_EMPLEADO = 910 AND FECHA = ?");
    $stmt->execute([$fecha_hoy]);
    echo "✅ Asistencias anteriores eliminadas\n";
    
    // Crear registros de entrada y salida para cada turno
    $registros_asistencia = [
        // Turno 1 - Mañana
        ['tipo' => 'ENTRADA', 'hora' => '08:05', 'id_horario' => $horarios[0]['ID_EMPLEADO_HORARIO']],
        ['tipo' => 'SALIDA', 'hora' => '12:10', 'id_horario' => $horarios[0]['ID_EMPLEADO_HORARIO']],
        
        // Turno 2 - Tarde
        ['tipo' => 'ENTRADA', 'hora' => '14:03', 'id_horario' => $horarios[1]['ID_EMPLEADO_HORARIO']],
        ['tipo' => 'SALIDA', 'hora' => '18:02', 'id_horario' => $horarios[1]['ID_EMPLEADO_HORARIO']],
        
        // Turno 3 - Noche
        ['tipo' => 'ENTRADA', 'hora' => '20:01', 'id_horario' => $horarios[2]['ID_EMPLEADO_HORARIO']],
        ['tipo' => 'SALIDA', 'hora' => '23:05', 'id_horario' => $horarios[2]['ID_EMPLEADO_HORARIO']],
    ];
    
    foreach ($registros_asistencia as $registro) {
        $stmt = $pdo->prepare("
            INSERT INTO asistencia (
                ID_EMPLEADO,
                FECHA,
                HORA,
                TIPO,
                ID_EMPLEADO_HORARIO,
                TIPO_HORARIO
            ) VALUES (
                910,
                ?,
                ?,
                ?,
                ?,
                'personalizado'
            )
        ");
        
        $stmt->execute([
            $fecha_hoy,
            $registro['hora'],
            $registro['tipo'],
            $registro['id_horario']
        ]);
        
        echo "✅ Registro: {$registro['tipo']} a las {$registro['hora']} (ID_HORARIO: {$registro['id_horario']})\n";
    }
    
    // 4. Verificar registros creados
    echo "\n4. Verificando registros de asistencia creados:\n";
    $stmt = $pdo->prepare("
        SELECT 
            a.FECHA,
            a.HORA,
            a.TIPO,
            a.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO,
            ehp.ORDEN_TURNO
        FROM asistencia a
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE a.ID_EMPLEADO = 910 
        AND a.FECHA = ?
        ORDER BY a.FECHA, a.HORA
    ");
    $stmt->execute([$fecha_hoy]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($registros as $registro) {
        echo "  - {$registro['FECHA']} {$registro['HORA']}: {$registro['TIPO']} (Turno: {$registro['NOMBRE_TURNO']}, Orden: {$registro['ORDEN_TURNO']})\n";
    }
    
    echo "\n✅ ¡Configuración de múltiples turnos completada!\n";
    echo "Ahora puedes probar el sistema de asistencias con múltiples turnos.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>