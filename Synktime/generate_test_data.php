<?php
// Generar datos de prueba para horarios personalizados y asistencias

require_once 'config/database.php';
require_once 'config/timezone.php';

session_start();
if (!isset($_SESSION['id_empresa'])) {
    $_SESSION['id_empresa'] = 1;
}

$empresaId = $_SESSION['id_empresa'];
$fecha = getBogotaDate();

echo "<h1>🔧 Generador de Datos de Prueba - Horarios Personalizados</h1>";
echo "<p>Fecha: $fecha | Empresa: $empresaId</p>";

try {
    // 1. Crear algunos horarios personalizados de ejemplo
    echo "<h2>1. Creando Horarios Personalizados</h2>";
    
    // Obtener algunos empleados para asignar horarios personalizados
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO 
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id 
        AND e.ACTIVO = 'S'
        LIMIT 10
    ");
    $stmt->bindParam(':empresa_id', $empresaId, PDO::PARAM_INT);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleados) == 0) {
        echo "<p style='color: red;'>No hay empleados disponibles para crear horarios personalizados.</p>";
        exit;
    }
    
    // Definir diferentes horarios personalizados
    $horariosEjemplo = [
        ['08:00:00', '17:00:00', 15, 'Horario Estándar'],
        ['07:00:00', '16:00:00', 10, 'Horario Temprano'],
        ['09:00:00', '18:00:00', 20, 'Horario Tardío'], 
        ['06:30:00', '15:30:00', 5, 'Horario Madrugador'],
        ['10:00:00', '19:00:00', 15, 'Horario Vespertino'],
    ];
    
    $horariosCreados = 0;
    foreach ($empleados as $i => $empleado) {
        if ($i >= count($horariosEjemplo)) break;
        
        $horario = $horariosEjemplo[$i];
        
        // Verificar si ya existe un horario personalizado para este empleado
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM empleado_horario_personalizado 
            WHERE ID_EMPLEADO = :empleado_id 
            AND FECHA_DESDE <= :fecha 
            AND (FECHA_HASTA IS NULL OR FECHA_HASTA >= :fecha)
        ");
        $stmt->bindParam(':empleado_id', $empleado['ID_EMPLEADO'], PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            // Crear horario personalizado
            $stmt = $conn->prepare("
                INSERT INTO empleado_horario_personalizado 
                (ID_EMPLEADO, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, FECHA_DESDE, NOMBRE_TURNO)
                VALUES (:empleado_id, :hora_entrada, :hora_salida, :tolerancia, :fecha_desde, :nombre_turno)
            ");
            $stmt->bindParam(':empleado_id', $empleado['ID_EMPLEADO'], PDO::PARAM_INT);
            $stmt->bindParam(':hora_entrada', $horario[0], PDO::PARAM_STR);
            $stmt->bindParam(':hora_salida', $horario[1], PDO::PARAM_STR);
            $stmt->bindParam(':tolerancia', $horario[2], PDO::PARAM_INT);
            $stmt->bindParam(':fecha_desde', $fecha, PDO::PARAM_STR);
            $stmt->bindParam(':nombre_turno', $horario[3], PDO::PARAM_STR);
            $stmt->execute();
            
            $horarioId = $conn->lastInsertId();
            $horariosCreados++;
            
            echo "<p>✅ Horario creado para {$empleado['NOMBRE']} {$empleado['APELLIDO']}: {$horario[3]} ({$horario[0]} - {$horario[1]})</p>";
            
            // 2. Crear asistencias de ejemplo con estos horarios personalizados
            $horasEjemplo = [
                '07:55:00',  // Temprano
                '08:05:00',  // A tiempo
                '08:20:00',  // Tarde
                '06:25:00',  // Muy temprano
                '10:10:00',  // A tiempo para horario vespertino
            ];
            
            $horaEjemplo = $horasEjemplo[$i];
            
            // Verificar si ya existe asistencia para hoy
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM asistencia 
                WHERE ID_EMPLEADO = :empleado_id 
                AND FECHA = :fecha 
                AND TIPO = 'ENTRADA'
            ");
            $stmt->bindParam(':empleado_id', $empleado['ID_EMPLEADO'], PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                // Crear asistencia
                $stmt = $conn->prepare("
                    INSERT INTO asistencia 
                    (ID_EMPLEADO, FECHA, HORA, TIPO, ID_EMPLEADO_HORARIO, OBSERVACION)
                    VALUES (:empleado_id, :fecha, :hora, 'ENTRADA', :horario_id, 'Datos de prueba')
                ");
                $stmt->bindParam(':empleado_id', $empleado['ID_EMPLEADO'], PDO::PARAM_INT);
                $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                $stmt->bindParam(':hora', $horaEjemplo, PDO::PARAM_STR);
                $stmt->bindParam(':horario_id', $horarioId, PDO::PARAM_INT);
                $stmt->execute();
                
                echo "<p>📝 Asistencia creada: {$empleado['NOMBRE']} llegó a las {$horaEjemplo}</p>";
            }
        } else {
            echo "<p>ℹ️ {$empleado['NOMBRE']} {$empleado['APELLIDO']} ya tiene horario personalizado</p>";
        }
    }
    
    echo "<h2>2. Resumen de Datos Creados</h2>";
    echo "<p><strong>Horarios personalizados creados:</strong> $horariosCreados</p>";
    
    // 3. Verificar los datos creados
    echo "<h2>3. Verificación de Datos Creados</h2>";
    
    $stmt = $conn->prepare("
        SELECT 
            e.NOMBRE,
            e.APELLIDO,
            ehp.NOMBRE_TURNO,
            ehp.HORA_ENTRADA,
            ehp.TOLERANCIA,
            a.HORA as hora_asistencia,
            a.ID_ASISTENCIA
        FROM empleado_horario_personalizado ehp
        JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN asistencia a ON ehp.ID_EMPLEADO = a.ID_EMPLEADO 
            AND a.FECHA = :fecha 
            AND a.TIPO = 'ENTRADA'
            AND a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE s.ID_EMPRESA = :empresa_id
        AND ehp.FECHA_DESDE <= :fecha
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
        ORDER BY ehp.HORA_ENTRADA
    ");
    $stmt->bindParam(':empresa_id', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($resultados) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Empleado</th><th>Turno</th><th>Hora Programada</th><th>Tolerancia</th><th>Hora Real</th><th>Estado</th></tr>";
        
        foreach ($resultados as $result) {
            $estado = '--';
            if ($result['hora_asistencia']) {
                require_once 'utils/attendance_status_utils.php';
                $estado = calcularEstadoEntrada(
                    $result['HORA_ENTRADA'],
                    $result['hora_asistencia'],
                    (int)$result['TOLERANCIA']
                );
            }
            
            echo "<tr>";
            echo "<td>{$result['NOMBRE']} {$result['APELLIDO']}</td>";
            echo "<td>{$result['NOMBRE_TURNO']}</td>";
            echo "<td>{$result['HORA_ENTRADA']}</td>";
            echo "<td>{$result['TOLERANCIA']} min</td>";
            echo "<td>" . ($result['hora_asistencia'] ?? 'Sin asistencia') . "</td>";
            echo "<td style='color: " . ($estado === 'Temprano' ? 'blue' : ($estado === 'Puntual' ? 'green' : ($estado === 'Tardanza' ? 'red' : 'gray'))) . ";'><strong>$estado</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>4. ✅ Datos de Prueba Listos</h2>";
    echo "<p>Ahora puedes probar el dashboard con datos reales de horarios personalizados.</p>";
    echo "<p><a href='dashboard.php' target='_blank'>🚀 Abrir Dashboard</a></p>";
    echo "<p><a href='test_states_calculation.php' target='_blank'>🧪 Probar Cálculos</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>