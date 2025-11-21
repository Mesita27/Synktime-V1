<?php
// Probar también turno diurno para confirmar compatibilidad
require_once 'config/database.php';

echo "=== Probando turno diurno para comparación ===\n";

// Buscar un empleado con turno diurno
$query = "SELECT eh.ID_EMPLEADO, eh.NOMBRE_TURNO, eh.ES_TURNO_NOCTURNO, eh.HORA_ENTRADA, eh.HORA_SALIDA 
          FROM empleado_horario_personalizado eh 
          WHERE eh.ACTIVO = 'S' 
          AND (eh.ES_TURNO_NOCTURNO = 'N' OR eh.ES_TURNO_NOCTURNO IS NULL)
          LIMIT 1";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $empleado_diurno = $result->fetch_assoc();
    echo "Empleado encontrado: " . $empleado_diurno['ID_EMPLEADO'] . "\n";
    echo "Turno: " . $empleado_diurno['NOMBRE_TURNO'] . "\n";
    echo "Horario: " . $empleado_diurno['HORA_ENTRADA'] . " - " . $empleado_diurno['HORA_SALIDA'] . "\n";
    echo "Es nocturno: " . ($empleado_diurno['ES_TURNO_NOCTURNO'] ?: 'NULL') . "\n\n";
    
    // Insertar datos de prueba para turno diurno
    $id_empleado = $empleado_diurno['ID_EMPLEADO'];
    $fecha_prueba = '2025-09-19';
    
    // Limpiar datos anteriores
    $conn->query("DELETE FROM asistencia WHERE ID_EMPLEADO = $id_empleado AND FECHA = '$fecha_prueba'");
    
    // Insertar entrada y salida normales
    $conn->query("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
                  VALUES ($id_empleado, '$fecha_prueba', 'ENTRADA', '08:00', 'traditional', 'legacy')");
    $conn->query("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
                  VALUES ($id_empleado, '$fecha_prueba', 'SALIDA', '17:00', 'traditional', 'legacy')");
    
    echo "✅ Datos insertados para turno diurno\n";
    echo "Entrada: 08:00, Salida: 17:00\n";
    echo "Empleado: $id_empleado, Fecha: $fecha_prueba\n";
    
} else {
    echo "❌ No se encontró empleado con turno diurno\n";
}

$conn->close();
?>