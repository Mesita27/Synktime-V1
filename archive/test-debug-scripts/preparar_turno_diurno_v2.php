<?php
// Probar también turno diurno para comparación
require_once 'config/database.php';

echo "=== Verificando empleados con turnos ===\n";

// Listar todos los empleados con sus configuraciones de turno
$query = "SELECT eh.ID_EMPLEADO, eh.NOMBRE_TURNO, eh.ES_TURNO_NOCTURNO, eh.HORA_ENTRADA, eh.HORA_SALIDA, eh.ID_DIA 
          FROM empleado_horario_personalizado eh 
          WHERE eh.ACTIVO = 'S' 
          ORDER BY eh.ID_EMPLEADO
          LIMIT 10";

$result = $conn->query($query);

echo "Empleados con horarios configurados:\n";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $nocturno = $row['ES_TURNO_NOCTURNO'] ?: 'NULL';
    echo "- ID: " . $row['ID_EMPLEADO'] . 
         ", Turno: " . $row['NOMBRE_TURNO'] . 
         ", Horario: " . $row['HORA_ENTRADA'] . "-" . $row['HORA_SALIDA'] .
         ", Día: " . $row['ID_DIA'] .
         ", Nocturno: " . $nocturno . "\n";
}

// Ahora vamos a probar con el empleado 100 que aparece en muchos archivos
$empleado_test = 100;
$fecha_test = '2025-09-19';

echo "\n=== Preparando empleado $empleado_test para prueba diurna ===\n";

// Verificar si tiene horario configurado
$query_check = "SELECT * FROM empleado_horario_personalizado 
                WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'";
$stmt = $conn->prepare($query_check);
$stmt->execute([$empleado_test]);
$horario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($horario) {
    echo "Horario encontrado para empleado $empleado_test:\n";
    echo "- Turno: " . $horario['NOMBRE_TURNO'] . "\n";
    echo "- Horario: " . $horario['HORA_ENTRADA'] . " - " . $horario['HORA_SALIDA'] . "\n";
    echo "- Es nocturno: " . ($horario['ES_TURNO_NOCTURNO'] ?: 'NULL') . "\n";
    
    // Insertar datos de asistencia
    $conn->exec("DELETE FROM asistencia WHERE ID_EMPLEADO = $empleado_test AND FECHA = '$fecha_test'");
    
    $conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
                 VALUES ($empleado_test, '$fecha_test', 'ENTRADA', '08:00', 'traditional', 'legacy')");
    $conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
                 VALUES ($empleado_test, '$fecha_test', 'SALIDA', '17:00', 'traditional', 'legacy')");
    
    echo "✅ Datos de asistencia insertados: 08:00-17:00\n";
    
} else {
    echo "❌ No se encontró horario configurado para empleado $empleado_test\n";
    
    // Crear horario básico para prueba
    echo "Creando horario básico...\n";
    
    $insert_horario = "INSERT INTO empleado_horario_personalizado 
                       (ID_EMPLEADO, ID_DIA, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, NOMBRE_TURNO, FECHA_DESDE, ACTIVO, ES_TURNO_NOCTURNO)
                       VALUES (?, 4, '08:00:00', '17:00:00', 10, 'Horario diurno prueba', ?, 'S', 'N')";
    
    $stmt = $conn->prepare($insert_horario);
    $stmt->execute([$empleado_test, $fecha_test]);
    
    echo "✅ Horario diurno creado para empleado $empleado_test\n";
    
    // Insertar datos de asistencia
    $conn->exec("DELETE FROM asistencia WHERE ID_EMPLEADO = $empleado_test AND FECHA = '$fecha_test'");
    
    $conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
                 VALUES ($empleado_test, '$fecha_test', 'ENTRADA', '08:00', 'traditional', 'legacy')");
    $conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
                 VALUES ($empleado_test, '$fecha_test', 'SALIDA', '17:00', 'traditional', 'legacy')");
    
    echo "✅ Datos de asistencia insertados: 08:00-17:00\n";
}

echo "\n📋 Listo para probar API con empleado $empleado_test el $fecha_test\n";
?>