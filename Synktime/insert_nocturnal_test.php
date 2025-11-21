<?php
require_once 'config/database.php';

try {
    // Insertar hora extra nocturna dominical de prueba
    $stmt = $pdo->prepare('
        INSERT INTO horas_extras_aprobacion (
            ID_EMPLEADO,
            ID_EMPLEADO_HORARIO,
            FECHA,
            HORA_INICIO,
            HORA_FIN,
            HORAS_EXTRAS,
            TIPO_EXTRA,
            TIPO_HORARIO,
            ESTADO_APROBACION,
            CREATED_AT
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pendiente", NOW())
    ');

    $stmt->execute([
        100, // ID_EMPLEADO
        213, // ID_EMPLEADO_HORARIO
        '2025-09-28', // FECHA
        '20:00', // HORA_INICIO
        '23:26', // HORA_FIN
        3.43, // HORAS_EXTRAS
        'despues', // TIPO_EXTRA
        'nocturna_dominical' // TIPO_HORARIO
    ]);

    echo '✅ Hora extra nocturna dominical insertada con ID: ' . $pdo->lastInsertId() . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>