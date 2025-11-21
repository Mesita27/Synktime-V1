<?php
// Investigar estructura de horarios personalizados
require_once 'config/database.php';

echo "=== Investigación de Horarios Personalizados ===\n";

try {
    // 1. Estructura de la tabla empleado_horario_personalizado
    echo "\n📋 Estructura de tabla 'empleado_horario_personalizado':\n";
    $stmt = $pdo->query("DESCRIBE empleado_horario_personalizado");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    // 2. Datos de ejemplo de horarios personalizados
    echo "\n📊 Datos de ejemplo de horarios personalizados:\n";
    $stmt = $pdo->query("SELECT * FROM empleado_horario_personalizado LIMIT 5");
    $horariosPersonalizados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($horariosPersonalizados) > 0) {
        foreach ($horariosPersonalizados as $hp) {
            echo "ID: {$hp['ID_EMPLEADO_HORARIO']} | Empleado: {$hp['ID_EMPLEADO']} | Día: {$hp['ID_DIA']} | Entrada: {$hp['HORA_ENTRADA']} | Salida: {$hp['HORA_SALIDA']} | Turno: {$hp['NOMBRE_TURNO']}\n";
        }
    } else {
        echo "No hay horarios personalizados en la base de datos\n";
    }
    
    // 3. Verificar empleados con horarios personalizados
    echo "\n👥 Empleados con horarios personalizados:\n";
    $stmt = $pdo->query("
        SELECT DISTINCT 
            ehp.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            COUNT(ehp.ID_EMPLEADO_HORARIO) as total_horarios_personalizados
        FROM empleado_horario_personalizado ehp
        JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
        GROUP BY ehp.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
        ORDER BY total_horarios_personalizados DESC
    ");
    
    $empleadosConHorarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleadosConHorarios) > 0) {
        foreach ($empleadosConHorarios as $emp) {
            echo "- {$emp['NOMBRE']} {$emp['APELLIDO']} (ID: {$emp['ID_EMPLEADO']}) - {$emp['total_horarios_personalizados']} horarios personalizados\n";
        }
    } else {
        echo "No hay empleados con horarios personalizados\n";
    }
    
    // 4. Verificar empleado 100 específicamente
    echo "\n🔍 Horarios del empleado 100:\n";
    
    // Horarios regulares
    echo "Horarios regulares:\n";
    $stmt = $pdo->prepare("
        SELECT 
            h.NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            eh.FECHA_DESDE,
            eh.FECHA_HASTA
        FROM empleado_horario eh
        JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = 100
        ORDER BY eh.FECHA_DESDE DESC
    ");
    $stmt->execute();
    $horariosRegulares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($horariosRegulares) > 0) {
        foreach ($horariosRegulares as $hr) {
            echo "- {$hr['NOMBRE']}: {$hr['HORA_ENTRADA']} - {$hr['HORA_SALIDA']} (Desde: {$hr['FECHA_DESDE']}, Hasta: {$hr['FECHA_HASTA']})\n";
        }
    } else {
        echo "- Sin horarios regulares\n";
    }
    
    // Horarios personalizados
    echo "\nHorarios personalizados:\n";
    $stmt = $pdo->prepare("
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.ID_DIA,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA,
            ehp.ACTIVO,
            ds.NOMBRE as DIA_NOMBRE
        FROM empleado_horario_personalizado ehp
        LEFT JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        WHERE ehp.ID_EMPLEADO = 100
        ORDER BY ehp.ID_DIA
    ");
    $stmt->execute();
    $horariosPersonalizadosEmp100 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($horariosPersonalizadosEmp100) > 0) {
        foreach ($horariosPersonalizadosEmp100 as $hp) {
            $diaTexto = $hp['DIA_NOMBRE'] ?? "Día {$hp['ID_DIA']}";
            echo "- {$diaTexto}: {$hp['HORA_ENTRADA']} - {$hp['HORA_SALIDA']} | Turno: {$hp['NOMBRE_TURNO']} | Activo: {$hp['ACTIVO']}\n";
        }
    } else {
        echo "- Sin horarios personalizados\n";
    }
    
    // 5. Estructura ideal para el endpoint
    echo "\n💡 Estructura ideal para el endpoint:\n";
    echo "Se debe incluir una nueva consulta para obtener horarios personalizados\n";
    echo "y combinar ambos tipos de horarios en una sola respuesta.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>