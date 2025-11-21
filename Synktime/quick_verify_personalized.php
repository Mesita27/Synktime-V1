<?php
// Verificación rápida de horarios personalizados

require_once 'config/database.php';
require_once 'config/timezone.php';

session_start();
if (!isset($_SESSION['id_empresa'])) {
    $_SESSION['id_empresa'] = 1;
}

$empresaId = $_SESSION['id_empresa'];
$fecha = getBogotaDate();

echo "<h1>Verificación Rápida: ¿Se Usan Horarios Personalizados?</h1>";
echo "<p>Fecha: $fecha | Empresa: $empresaId</p>";

try {
    // 1. ¿Existen horarios personalizados activos?
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_personalizados
        FROM empleado_horario_personalizado ehp
        JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresaId
        AND ehp.FECHA_DESDE <= :fecha
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $totalPersonalizados = $stmt->fetchColumn();
    
    echo "<h2>1. Horarios Personalizados Activos:</h2>";
    echo "<p><strong>Total: $totalPersonalizados</strong></p>";
    
    // 2. ¿Hay asistencias que usen horarios personalizados?
    $stmt = $conn->prepare("
        SELECT COUNT(*) as asistencias_con_personalizado
        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE a.FECHA = :fecha
        AND a.TIPO = 'ENTRADA'
        AND a.ID_EMPLEADO_HORARIO IS NOT NULL
        AND s.ID_EMPRESA = :empresaId
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $asistenciasPersonalizadas = $stmt->fetchColumn();
    
    echo "<h2>2. Asistencias con Horario Personalizado:</h2>";
    echo "<p><strong>Total: $asistenciasPersonalizadas</strong></p>";
    
    // 3. ¿Hay asistencias que usen horarios tradicionales?
    $stmt = $conn->prepare("
        SELECT COUNT(*) as asistencias_con_tradicional
        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE a.FECHA = :fecha
        AND a.TIPO = 'ENTRADA'
        AND a.ID_HORARIO IS NOT NULL
        AND s.ID_EMPRESA = :empresaId
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $asistenciasTradicionales = $stmt->fetchColumn();
    
    echo "<h2>3. Asistencias con Horario Tradicional:</h2>";
    echo "<p><strong>Total: $asistenciasTradicionales</strong></p>";
    
    // 4. Desglose detallado de algunas asistencias
    $stmt = $conn->prepare("
        SELECT 
            e.NOMBRE,
            e.APELLIDO,
            a.HORA as hora_entrada,
            a.ID_HORARIO,
            a.ID_EMPLEADO_HORARIO,
            h.HORA_ENTRADA as horario_trad_entrada,
            ehp.HORA_ENTRADA as horario_pers_entrada,
            COALESCE(h.HORA_ENTRADA, ehp.HORA_ENTRADA, '08:00:00') as horario_efectivo
        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE a.FECHA = :fecha
        AND a.TIPO = 'ENTRADA'
        AND s.ID_EMPRESA = :empresaId
        ORDER BY a.HORA
        LIMIT 10
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>4. Muestra de Asistencias (primeras 10):</h2>";
    if (count($muestras) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Empleado</th><th>Hora Real</th><th>ID Trad</th><th>ID Pers</th><th>Horario Trad</th><th>Horario Pers</th><th>Efectivo</th></tr>";
        foreach ($muestras as $m) {
            echo "<tr>";
            echo "<td>{$m['NOMBRE']} {$m['APELLIDO']}</td>";
            echo "<td>{$m['hora_entrada']}</td>";
            echo "<td>" . ($m['ID_HORARIO'] ?? '-') . "</td>";
            echo "<td>" . ($m['ID_EMPLEADO_HORARIO'] ?? '-') . "</td>";
            echo "<td>" . ($m['horario_trad_entrada'] ?? '-') . "</td>";
            echo "<td style='background-color: lightgreen;'>" . ($m['horario_pers_entrada'] ?? '-') . "</td>";
            echo "<td><strong>{$m['horario_efectivo']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay asistencias para mostrar.</p>";
    }
    
    // Conclusión
    echo "<h2>CONCLUSIÓN:</h2>";
    if ($totalPersonalizados > 0 && $asistenciasPersonalizadas > 0) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ SÍ se están utilizando horarios personalizados</p>";
        echo "<ul>";
        echo "<li>Horarios personalizados configurados: $totalPersonalizados</li>";
        echo "<li>Asistencias usando horarios personalizados: $asistenciasPersonalizadas</li>";
        echo "<li>Asistencias usando horarios tradicionales: $asistenciasTradicionales</li>";
        echo "</ul>";
    } elseif ($totalPersonalizados > 0) {
        echo "<p style='color: orange; font-size: 18px; font-weight: bold;'>⚠️ Hay horarios personalizados configurados pero no se están usando en asistencias</p>";
    } else {
        echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ NO hay horarios personalizados configurados</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>