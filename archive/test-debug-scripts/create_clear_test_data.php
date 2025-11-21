<?php
// Recrear datos de prueba con casos claros para testing

require_once 'config/database.php';
require_once 'config/timezone.php';

echo "<h1>Recrear Datos de Prueba Claros</h1>";

session_start();
if (!isset($_SESSION['id_empresa'])) {
    $_SESSION['id_empresa'] = 1;
}

$empresaId = $_SESSION['id_empresa'];
$fecha = getBogotaDate();

echo "<p>Recreando datos para empresa ID: $empresaId, fecha: $fecha</p>";

try {
    // Obtener algunos empleados
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :id
        AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
        LIMIT 5
    ");
    $stmt->bindParam(':id', $empresaId, PDO::PARAM_INT);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleados) == 0) {
        echo "<p>No hay empleados disponibles.</p>";
        exit;
    }
    
    // Obtener horario existente
    $stmt = $conn->prepare("SELECT ID_HORARIO FROM horario WHERE ACTIVO = 'S' LIMIT 1");
    $stmt->execute();
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horario) {
        echo "<p>No hay horarios. Creando uno...</p>";
        $stmt = $conn->prepare("
            INSERT INTO horario (NOMBRE, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, ACTIVO) 
            VALUES ('Test Regular', '08:00:00', '17:00:00', 15, 'S')
        ");
        $stmt->execute();
        $horarioId = $conn->lastInsertId();
        echo "<p>Horario creado con ID: $horarioId</p>";
    } else {
        $horarioId = $horario['ID_HORARIO'];
        echo "<p>Usando horario existente ID: $horarioId</p>";
    }
    
    // Limpiar asistencias del día
    $stmt = $conn->prepare("DELETE FROM asistencia WHERE FECHA = :fecha");
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    echo "<p>Asistencias previas eliminadas</p>";
    
    // Crear casos muy claros
    $casos = [
        ['nombre' => 'CASO TEMPRANO', 'hora' => '07:30:00'], // 30 min antes = Temprano
        ['nombre' => 'CASO PUNTUAL', 'hora' => '08:00:00'],  // Exacto = Puntual  
        ['nombre' => 'CASO A TIEMPO', 'hora' => '08:10:00'], // 10 min después = Puntual (tolerancia 15)
        ['nombre' => 'CASO TARDANZA', 'hora' => '08:20:00'], // 20 min después = Tardanza
        ['nombre' => 'CASO LIMITE', 'hora' => '08:15:00'],   // 15 min después = Puntual (justo en tolerancia)
    ];
    
    foreach ($empleados as $index => $empleado) {
        if ($index >= count($casos)) break;
        
        $caso = $casos[$index];
        
        $stmt = $conn->prepare("
            INSERT INTO asistencia (
                ID_EMPLEADO, 
                FECHA, 
                HORA, 
                TIPO, 
                ID_HORARIO,
                TARDANZA
            ) VALUES (
                :id_empleado,
                :fecha,
                :hora,
                'ENTRADA',
                :id_horario,
                0
            )
        ");
        
        $stmt->execute([
            ':id_empleado' => $empleado['ID_EMPLEADO'],
            ':fecha' => $fecha,
            ':hora' => $caso['hora'],
            ':id_horario' => $horarioId
        ]);
        
        echo "<p>✅ {$caso['nombre']}: {$empleado['NOMBRE']} {$empleado['APELLIDO']} - {$caso['hora']}</p>";
    }
    
    echo "<h2>Casos de prueba creados:</h2>";
    echo "<ul>";
    echo "<li><strong>07:30:00</strong> - Debe ser TEMPRANO (30 min antes)</li>";
    echo "<li><strong>08:00:00</strong> - Debe ser PUNTUAL (exacto)</li>";
    echo "<li><strong>08:10:00</strong> - Debe ser PUNTUAL (10 min tarde, tolerancia 15)</li>";
    echo "<li><strong>08:15:00</strong> - Debe ser PUNTUAL (15 min tarde, justo en tolerancia)</li>";
    echo "<li><strong>08:20:00</strong> - Debe ser TARDANZA (20 min tarde, fuera de tolerancia)</li>";
    echo "</ul>";
    
    echo "<h2>Resultado esperado:</h2>";
    echo "<p>Tempranos: 1, Puntuales: 3, Tardanzas: 1</p>";
    
    echo "<hr>";
    echo "<p><a href='debug_specific_issue.php'>Verificar resultados</a></p>";
    echo "<p><a href='dashboard.php'>Ver Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>