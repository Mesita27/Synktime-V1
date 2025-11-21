<?php
// Script para insertar datos de prueba para el dashboard

require_once 'config/database.php';
require_once 'config/timezone.php';

echo "<h1>Insertar Datos de Prueba para Dashboard</h1>";

session_start();
if (!isset($_SESSION['id_empresa'])) {
    $_SESSION['id_empresa'] = 1;
}

$empresaId = $_SESSION['id_empresa'];
$fecha = getBogotaDate();

echo "<p>Insertando datos para empresa ID: $empresaId, fecha: $fecha</p>";

try {
    // Obtener algunos empleados para crear asistencias de prueba
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.ID_ESTABLECIMIENTO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :id
        AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
        LIMIT 10
    ");
    $stmt->bindParam(':id', $empresaId, PDO::PARAM_INT);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($empleados) == 0) {
        echo "<p>No hay empleados activos para crear datos de prueba.</p>";
        exit;
    }
    
    // Obtener un horario existente
    $stmt = $conn->prepare("SELECT ID_HORARIO FROM horario WHERE ACTIVO = 'S' LIMIT 1");
    $stmt->execute();
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horario) {
        echo "<p>No hay horarios activos. Creando horario de prueba...</p>";
        
        // Crear horario de prueba
        $stmt = $conn->prepare("
            INSERT INTO horario (NOMBRE, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, ACTIVO) 
            VALUES ('Horario Regular', '08:00:00', '17:00:00', 15, 'S')
        ");
        $stmt->execute();
        $horarioId = $conn->lastInsertId();
        echo "<p>Horario creado con ID: $horarioId</p>";
    } else {
        $horarioId = $horario['ID_HORARIO'];
        echo "<p>Usando horario existente ID: $horarioId</p>";
    }
    
    // Limpiar asistencias existentes del día
    $stmt = $conn->prepare("DELETE FROM asistencia WHERE FECHA = :fecha");
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    echo "<p>Asistencias previas eliminadas para la fecha $fecha</p>";
    
    $inserted = 0;
    
    // Crear asistencias de prueba con diferentes horas
    $horasEntrada = [
        '07:30:00', // Temprano
        '07:45:00', // Temprano
        '08:00:00', // Puntual
        '08:05:00', // Puntual (dentro de tolerancia)
        '08:10:00', // Puntual (dentro de tolerancia)
        '08:20:00', // Tarde
        '08:30:00', // Tarde
        '09:00:00', // Tarde
        '08:02:00', // Puntual
        '07:55:00'  // Temprano
    ];
    
    foreach ($empleados as $index => $empleado) {
        if ($index >= count($horasEntrada)) break;
        
        $horaEntrada = $horasEntrada[$index];
        
        // Insertar entrada
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
        
        $params = [
            ':id_empleado' => $empleado['ID_EMPLEADO'],
            ':fecha' => $fecha,
            ':hora' => $horaEntrada,
            ':id_horario' => $horarioId
        ];
        
        $stmt->execute($params);
        $inserted++;
        
        echo "<p>Asistencia creada: {$empleado['NOMBRE']} {$empleado['APELLIDO']} - Entrada: $horaEntrada</p>";
    }
    
    echo "<h2>Resumen:</h2>";
    echo "<p>$inserted asistencias de entrada insertadas para $fecha</p>";
    echo "<p><a href='debug_dashboard_stats.php'>Verificar estadísticas</a></p>";
    echo "<p><a href='dashboard.php'>Ver Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>