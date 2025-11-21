<?php
require_once '../config/database.php';

try {
    // Probar con un empleado que pueda tener registros
    $employeeId = 1; // Cambia esto por un ID de empleado que exista
    $fecha = date('Y-m-d');
    $diaSemanaBD = date('N'); // 1=lunes, 7=domingo

    echo "=== VERIFICACIÓN DE HORARIO ===\n";
    echo "Empleado ID: $employeeId\n";
    echo "Fecha: $fecha\n";
    echo "Día semana: $diaSemanaBD\n\n";

    // Verificar horario
    $stmt = $pdo->prepare("
        SELECT
            h.ID_HORARIO,
            h.NOMBRE as horario_nombre,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA
        FROM empleado_horario eh
        JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
        JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO
        WHERE eh.ID_EMPLEADO = ?
        AND eh.FECHA_DESDE <= ?
        AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
        AND hd.ID_DIA = ?
        ORDER BY eh.FECHA_DESDE DESC
        LIMIT 1
    ");

    $stmt->execute([$employeeId, $fecha, $fecha, $diaSemanaBD]);
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($horario) {
        echo "✅ HORARIO ENCONTRADO:\n";
        echo "- ID: {$horario['ID_HORARIO']}\n";
        echo "- Nombre: {$horario['horario_nombre']}\n";
        echo "- Entrada: {$horario['HORA_ENTRADA']}\n";
        echo "- Salida: {$horario['HORA_SALIDA']}\n";
        echo "- Tolerancia: {$horario['TOLERANCIA']} minutos\n\n";

        echo "=== VERIFICACIÓN DE ENTRADA ABIERTA ===\n";

        // Verificar registros existentes del día
        $stmtRegistros = $pdo->prepare("
            SELECT ID_ASISTENCIA, TIPO, HORA, FECHA
            FROM asistencia
            WHERE ID_EMPLEADO = ?
            AND DATE(FECHA) = ?
            ORDER BY HORA
        ");
        $stmtRegistros->execute([$employeeId, $fecha]);
        $registros = $stmtRegistros->fetchAll(PDO::FETCH_ASSOC);

        echo "Registros del día:\n";
        if (empty($registros)) {
            echo "📝 No hay registros para este empleado hoy\n";
        } else {
            foreach ($registros as $reg) {
                echo "- {$reg['TIPO']} a las {$reg['HORA']} (ID: {$reg['ID_ASISTENCIA']})\n";
            }
        }
        echo "\n";

        // Verificar entrada abierta
        $stmtEntrada = $pdo->prepare("
            SELECT COUNT(*) as entradas_abiertas
            FROM asistencia
            WHERE ID_EMPLEADO = ?
            AND DATE(FECHA) = ?
            AND TIPO = 'ENTRADA'
            AND NOT EXISTS (
                SELECT 1 FROM asistencia a2
                WHERE a2.ID_EMPLEADO = asistencia.ID_EMPLEADO
                AND DATE(a2.FECHA) = DATE(asistencia.FECHA)
                AND a2.TIPO = 'SALIDA'
                AND a2.HORA > asistencia.HORA
            )
        ");
        $stmtEntrada->execute([$employeeId, $fecha]);
        $entradaAbierta = $stmtEntrada->fetch(PDO::FETCH_ASSOC);

        if ($entradaAbierta['entradas_abiertas'] > 0) {
            echo "❌ RESULTADO: Hay {$entradaAbierta['entradas_abiertas']} entrada(s) abierta(s)\n";
            echo "💡 No se puede registrar nueva entrada\n";
        } else {
            echo "✅ RESULTADO: No hay entradas abiertas\n";
            echo "💡 Se puede registrar entrada\n";
        }
    } else {
        echo "❌ No se encontró horario para el empleado\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
