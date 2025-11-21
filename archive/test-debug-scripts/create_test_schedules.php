<?php
require_once 'config/database.php';

echo "=== CREANDO HORARIOS DE PRUEBA PARA MÚLTIPLES TURNOS ===\n\n";

// Limpiar registros de prueba
$conn->prepare('DELETE FROM asistencia WHERE ID_EMPLEADO = 100 AND FECHA = CURDATE()')->execute();
echo "🧹 Registros de prueba limpiados\n";

// Crear horarios adicionales para múltiples turnos
$horarios = [
    [
        'orden' => 1,
        'nombre' => 'Turno Mañana',
        'entrada' => '08:00:00',
        'salida' => '16:00:00'
    ],
    [
        'orden' => 2,
        'nombre' => 'Turno Tarde',
        'entrada' => '16:00:00',
        'salida' => '24:00:00'
    ]
];

$dia_semana = date('N'); // Día actual
$fecha_desde = date('Y-m-d');
$fecha_hasta = date('Y-m-d', strtotime('+30 days'));

foreach ($horarios as $horario) {
    $stmt = $conn->prepare('
        INSERT INTO empleado_horario_personalizado
        (ID_EMPLEADO, ID_DIA, NOMBRE_TURNO, ORDEN_TURNO, HORA_ENTRADA, HORA_SALIDA,
         FECHA_DESDE, FECHA_HASTA, ACTIVO, ES_TURNO_NOCTURNO, HORA_CORTE_NOCTURNO)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, "S", "N", NULL)
        ON DUPLICATE KEY UPDATE
        NOMBRE_TURNO = VALUES(NOMBRE_TURNO),
        HORA_ENTRADA = VALUES(HORA_ENTRADA),
        HORA_SALIDA = VALUES(HORA_SALIDA)
    ');

    $stmt->execute([
        100, // ID_EMPLEADO
        $dia_semana, // ID_DIA
        $horario['nombre'],
        $horario['orden'],
        $horario['entrada'],
        $horario['salida'],
        $fecha_desde,
        $fecha_hasta
    ]);

    echo "✅ Creado horario: {$horario['nombre']} ({$horario['entrada']}-{$horario['salida']})\n";
}

echo "\n🎯 Horarios de prueba creados para empleado 100\n";
echo "   Ahora puede hacer: Entrada1 → Salida1 → Entrada2 → Salida2\n";
echo "   Turno 1: 08:00-16:00 (Mañana)\n";
echo "   Turno 2: 16:00-24:00 (Tarde)\n";
?>