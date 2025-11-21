<?php
// Test para diagnosticar el problema de cálculo de horas
echo "=== DIAGNÓSTICO DEL PROBLEMA DE HORAS ===\n\n";

require_once 'config/database.php';

// Verificar datos de asistencia directamente
echo "1. Verificando datos de asistencia en BD:\n";
$empleados_test = [100, 1231];

foreach ($empleados_test as $emp_id) {
    echo "\n--- EMPLEADO $emp_id ---\n";
    
    $query = "SELECT a.*, 
                     ehp.HORA_ENTRADA as HORARIO_ENTRADA,
                     ehp.HORA_SALIDA as HORARIO_SALIDA,
                     ehp.NOMBRE_TURNO,
                     ehp.ES_TURNO_NOCTURNO,
                     ehp.HORA_CORTE_NOCTURNO,
                     DAYOFWEEK(a.FECHA) as DIA_SEMANA_NUM
              FROM asistencia a
              LEFT JOIN empleado_horario_personalizado ehp ON (
                  ehp.ID_EMPLEADO = a.ID_EMPLEADO 
                  AND ehp.ID_DIA = DAYOFWEEK(a.FECHA)
                  AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '2099-12-31')
                  AND ehp.ACTIVO = 'S'
              )
              WHERE a.ID_EMPLEADO = ? 
              AND a.FECHA = '2025-09-18'
              ORDER BY a.FECHA ASC, a.HORA ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$emp_id]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Registros encontrados: " . count($registros) . "\n";
    
    foreach ($registros as $i => $reg) {
        echo "  [$i] TIPO: {$reg['TIPO']}, HORA: {$reg['HORA']}, ";
        echo "TURNO: " . ($reg['NOMBRE_TURNO'] ?: 'No asignado') . ", ";
        echo "ES_NOCTURNO: " . ($reg['ES_TURNO_NOCTURNO'] ?: 'N') . "\n";
    }
    
    // Verificar si se forman pares válidos
    echo "\nAnálisis de pares:\n";
    for ($i = 0; $i < count($registros); $i += 2) {
        if (!isset($registros[$i + 1])) {
            echo "  Par $i: INCOMPLETO - Solo hay entrada\n";
            break;
        }
        
        $entrada = $registros[$i];
        $salida = $registros[$i + 1];
        
        if ($entrada['TIPO'] !== 'ENTRADA' || $salida['TIPO'] !== 'SALIDA') {
            echo "  Par $i: INVÁLIDO - {$entrada['TIPO']} -> {$salida['TIPO']}\n";
        } else {
            $diff = (strtotime($salida['HORA']) - strtotime($entrada['HORA'])) / 3600;
            echo "  Par $i: VÁLIDO - {$entrada['HORA']} -> {$salida['HORA']} = {$diff} horas\n";
        }
    }
}

echo "\n2. Verificando horarios personalizados:\n";
foreach ($empleados_test as $emp_id) {
    echo "\n--- HORARIOS EMPLEADO $emp_id ---\n";
    
    $query = "SELECT * FROM empleado_horario_personalizado 
              WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'
              ORDER BY ID_DIA";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$emp_id]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($horarios as $horario) {
        echo "  Día {$horario['ID_DIA']}: {$horario['HORA_ENTRADA']}-{$horario['HORA_SALIDA']} ";
        echo "({$horario['NOMBRE_TURNO']}) ";
        echo "Nocturno: " . ($horario['ES_TURNO_NOCTURNO'] ?: 'N') . "\n";
    }
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
?>