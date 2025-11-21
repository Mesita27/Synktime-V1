<?php
// Corrección de datos de prueba
echo "=== CORRECCIÓN DE DATOS DE PRUEBA ===\n";

require_once 'config/database.php';

echo "1. Corrigiendo empleado 100 (horario diurno):\n";

// Limpiar registros anteriores
$conn->exec("DELETE FROM asistencia WHERE ID_EMPLEADO = 100 AND FECHA = '2025-09-18'");

// Insertar registros correctos para empleado 100 (horario 08:00-16:00)
$conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
             VALUES (100, '2025-09-18', 'ENTRADA', '08:00', 'traditional', 'legacy')");
$conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
             VALUES (100, '2025-09-18', 'SALIDA', '17:00', 'traditional', 'legacy')");

echo "  ✅ Insertado: ENTRADA 08:00, SALIDA 17:00 (9 horas)\n";

echo "\n2. Corrigiendo empleado 1231 (horario nocturno):\n";

// Limpiar registros anteriores
$conn->exec("DELETE FROM asistencia WHERE ID_EMPLEADO = 1231 AND FECHA = '2025-09-18'");

// Insertar registros correctos para empleado 1231 (horario nocturno)
// El horario nocturno va de 20:00 a 00:45 (del día siguiente)
$conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
             VALUES (1231, '2025-09-18', 'ENTRADA', '20:15', 'traditional', 'legacy')");
$conn->exec("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, TIPO_HORARIO) 
             VALUES (1231, '2025-09-18', 'SALIDA', '00:30', 'traditional', 'legacy')");

echo "  ✅ Insertado: ENTRADA 20:15, SALIDA 00:30 (horario nocturno)\n";

echo "\n3. Verificando corrección:\n";

$empleados_test = [100, 1231];
foreach ($empleados_test as $emp_id) {
    echo "\n--- EMPLEADO $emp_id ---\n";
    
    $query = "SELECT TIPO, HORA FROM asistencia 
              WHERE ID_EMPLEADO = ? AND FECHA = '2025-09-18'
              ORDER BY HORA ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$emp_id]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($registros as $i => $reg) {
        echo "  [$i] {$reg['TIPO']}: {$reg['HORA']}\n";
    }
    
    // Calcular horas esperadas
    if (count($registros) >= 2) {
        $entrada = null;
        $salida = null;
        
        foreach ($registros as $reg) {
            if ($reg['TIPO'] === 'ENTRADA') $entrada = $reg['HORA'];
            if ($reg['TIPO'] === 'SALIDA') $salida = $reg['HORA'];
        }
        
        if ($entrada && $salida) {
            if ($emp_id == 1231) {
                // Para nocturno, calcular considerando que la salida es del día siguiente
                $entrada_timestamp = strtotime($entrada);
                $salida_timestamp = strtotime($salida);
                
                // Si salida es menor que entrada, agregar 24 horas
                if ($salida_timestamp < $entrada_timestamp) {
                    $salida_timestamp += 24 * 3600;
                }
                
                $horas = ($salida_timestamp - $entrada_timestamp) / 3600;
            } else {
                // Para diurno, cálculo normal
                $horas = (strtotime($salida) - strtotime($entrada)) / 3600;
            }
            
            echo "  Horas esperadas: $horas\n";
        }
    }
}

echo "\n✅ Datos de prueba corregidos\n";
echo "=== FIN CORRECCIÓN ===\n";
?>