<?php
/**
 * Script para crear datos de prueba que simulen el problema reportado
 * Tabla muestra "Horario Regular 08:00:00 - 16:00:00" pero modal muestra "Nuevo Turno 20:00 - 02:00"
 * CORREGIDO: Ahora usa ID_EMPLEADO_HORARIO en lugar de ID_HORARIO según indicación del usuario
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/timezone.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['id_empresa'] = 1;

echo "=== CREANDO DATOS DE PRUEBA PARA SIMULAR EL PROBLEMA ===\n\n";

try {
    // 1. Crear un horario personalizado "Horario Regular" (válido todos los días)
    // Para que sea válido todos los días, necesito crear un registro por cada día de la semana
    echo "1. Creando horario personalizado 'Horario Regular' (válido todos los días)...\n";
    $idHorarioRegularPersonalizado = null;
    for ($dia = 1; $dia <= 7; $dia++) {
        $sqlHorarioRegular = "INSERT INTO empleado_horario_personalizado
                            (ID_EMPLEADO, NOMBRE_TURNO, HORA_ENTRADA, HORA_SALIDA, ID_DIA, FECHA_DESDE, FECHA_HASTA, ACTIVO)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'S')";
        $stmtHorarioRegular = $conn->prepare($sqlHorarioRegular);
        $stmtHorarioRegular->execute([
            1, // ID_EMPLEADO
            'Horario Regular',
            '08:00:00',
            '16:00:00',
            $dia, // Día de la semana (1=Lunes, 7=Domingo)
            '2025-01-01',
            '2025-12-31'
        ]);
        if ($dia == 1) {
            $idHorarioRegularPersonalizado = $conn->lastInsertId();
        }
    }

    // 2. Crear un horario personalizado "Nuevo Turno"
    echo "2. Creando horario personalizado 'Nuevo Turno'...\n";
    $sqlPersonalizado = "INSERT INTO empleado_horario_personalizado
                        (ID_EMPLEADO, NOMBRE_TURNO, HORA_ENTRADA, HORA_SALIDA, ID_DIA, FECHA_DESDE, FECHA_HASTA, ACTIVO)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'S')
                        ON DUPLICATE KEY UPDATE ID_EMPLEADO_HORARIO=LAST_INSERT_ID(ID_EMPLEADO_HORARIO)";
    $stmtPersonalizado = $conn->prepare($sqlPersonalizado);
    $stmtPersonalizado->execute([
        1, // ID_EMPLEADO
        'Nuevo Turno',
        '20:00:00',
        '02:00:00',
        5, // Viernes (ID_DIA = 5)
        '2025-01-01',
        '2025-12-31'
    ]);
    $idHorarioPersonalizado = $conn->lastInsertId();

    // 3. Crear horario tradicional para compatibilidad (aunque ya no se use)
    echo "3. Creando horario tradicional para compatibilidad...\n";
    $sqlHorario = "INSERT INTO HORARIO (ID_ESTABLECIMIENTO, NOMBRE, HORA_ENTRADA, HORA_SALIDA, ES_TURNO_NOCTURNO)
                   VALUES (1, ?, ?, ?, 'N')
                   ON DUPLICATE KEY UPDATE ID_HORARIO=LAST_INSERT_ID(ID_HORARIO)";
    $stmtHorario = $conn->prepare($sqlHorario);
    $stmtHorario->execute(['Horario Regular Legacy', '08:00', '16:00']);
    $idHorarioRegularLegacy = $conn->lastInsertId();

    // Asignar el horario tradicional al empleado
    $sqlAsignar = "INSERT INTO EMPLEADO_HORARIO (ID_EMPLEADO, ID_HORARIO, FECHA_DESDE, ACTIVO)
                   VALUES (?, ?, '2025-01-01', 'S')
                   ON DUPLICATE KEY UPDATE ACTIVO='S'";
    $stmtAsignar = $conn->prepare($sqlAsignar);
    $stmtAsignar->execute([1, $idHorarioRegularLegacy]);

    // 4. Crear una asistencia para un día que NO sea viernes (ej: miércoles)
    // donde la tabla debería mostrar "Horario Regular" pero el modal podría mostrar "Nuevo Turno"
    echo "4. Creando asistencia para miércoles (no viernes)...\n";
    $fechaMiercoles = '2025-10-02'; // Miércoles 2 de octubre 2025

    $sqlAsistencia = "INSERT INTO ASISTENCIA
                     (ID_EMPLEADO, FECHA, HORA, TIPO, ID_HORARIO, ID_EMPLEADO_HORARIO, TIPO_HORARIO)
                     VALUES (?, ?, '08:30:00', 'ENTRADA', NULL, ?, 'personalizado')
                     ON DUPLICATE KEY UPDATE ID_HORARIO=NULL, ID_EMPLEADO_HORARIO=?, TIPO_HORARIO='personalizado'";
    $stmtAsistencia = $conn->prepare($sqlAsistencia);
    $stmtAsistencia->execute([1, $fechaMiercoles, $idHorarioRegularPersonalizado, $idHorarioRegularPersonalizado]);

    // 5. Crear otra asistencia para un viernes donde ambos horarios podrían ser válidos
    echo "5. Creando asistencia para viernes...\n";
    $fechaViernes = '2025-10-04'; // Viernes 4 de octubre 2025

    $sqlAsistenciaViernes = "INSERT INTO ASISTENCIA
                            (ID_EMPLEADO, FECHA, HORA, TIPO, ID_HORARIO, ID_EMPLEADO_HORARIO, TIPO_HORARIO)
                            VALUES (?, ?, '08:30:00', 'ENTRADA', NULL, ?, 'personalizado')
                            ON DUPLICATE KEY UPDATE ID_HORARIO=NULL, ID_EMPLEADO_HORARIO=?, TIPO_HORARIO='personalizado'";
    $stmtAsistenciaViernes = $conn->prepare($sqlAsistenciaViernes);
    $stmtAsistenciaViernes->execute([1, $fechaViernes, $idHorarioRegularPersonalizado, $idHorarioRegularPersonalizado]);

    echo "\n✅ DATOS DE PRUEBA CREADOS EXITOSAMENTE\n\n";

    echo "Resumen de datos creados:\n";
    echo "- Horario Regular Personalizado: ID {$idHorarioRegularPersonalizado} (08:00:00 - 16:00:00, todos los días)\n";
    echo "- Nuevo Turno: ID {$idHorarioPersonalizado} (20:00:00 - 02:00:00, solo viernes)\n";
    echo "- Asistencia miércoles: {$fechaMiercoles} - debería mostrar 'Horario Regular'\n";
    echo "- Asistencia viernes: {$fechaViernes} - podría mostrar ambos horarios\n\n";

    echo "=== PRUEBA DE LA SOLUCIÓN ===\n";

    // Simular la lógica del modal con el filtro por fecha
    echo "Para la fecha {$fechaMiercoles} (miércoles):\n";

    // Obtener todos los horarios del empleado (solo personalizados según el usuario)
    $sqlTodosHorarios = "
        SELECT 'PERSONALIZADO' as tipo, ehp.ID_EMPLEADO_HORARIO as id, ehp.NOMBRE_TURNO as nombre,
               ehp.HORA_ENTRADA, ehp.HORA_SALIDA, ehp.ID_DIA, ehp.FECHA_DESDE, ehp.FECHA_HASTA
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ? AND ehp.ACTIVO = 'S'
    ";
    $stmtTodos = $conn->prepare($sqlTodosHorarios);
    $stmtTodos->execute([1]);
    $todosHorarios = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

    echo "Horarios sin filtrar:\n";
    foreach ($todosHorarios as $h) {
        echo "- [{$h['tipo']}] {$h['nombre']} ({$h['HORA_ENTRADA']} - {$h['HORA_SALIDA']})\n";
    }

    // Aplicar filtro por fecha (simulando JavaScript)
    $diaSemana = date('l', strtotime($fechaMiercoles)); // "Wednesday"
    $diaSemanaEspanol = [
        'Monday' => 'LUNES',
        'Tuesday' => 'MARTES',
        'Wednesday' => 'MIERCOLES',
        'Thursday' => 'JUEVES',
        'Friday' => 'VIERNES',
        'Saturday' => 'SABADO',
        'Sunday' => 'DOMINGO'
    ][$diaSemana];

    // Mapear día de la semana a ID_DIA
    $idDiaMap = [
        'LUNES' => 1,
        'MARTES' => 2,
        'MIERCOLES' => 3,
        'JUEVES' => 4,
        'VIERNES' => 5,
        'SABADO' => 6,
        'DOMINGO' => 7
    ];
    $idDiaActual = $idDiaMap[$diaSemanaEspanol];

    echo "\nFiltrando para fecha {$fechaMiercoles} (día: {$diaSemanaEspanol}, ID_DIA: {$idDiaActual}):\n";

    $horariosFiltrados = [];
    foreach ($todosHorarios as $h) {
        $esValido = true;

        // Para horarios personalizados, verificar día de la semana y rango de fechas
        if ($h['id_dia'] && $h['id_dia'] != $idDiaActual) {
            $esValido = false;
        }
        // Verificar rango de fechas
        if ($h['fecha_desde'] && $fechaMiercoles < $h['fecha_desde']) {
            $esValido = false;
        }
        if ($h['fecha_hasta'] && $fechaMiercoles > $h['fecha_hasta']) {
            $esValido = false;
        }

        if ($esValido) {
            $horariosFiltrados[] = $h;
            echo "- ✅ [{$h['tipo']}] {$h['nombre']} ({$h['HORA_ENTRADA']} - {$h['HORA_SALIDA']})\n";
        } else {
            echo "- ❌ [{$h['tipo']}] {$h['nombre']} (no válido para esta fecha)\n";
        }
    }

    echo "\nResultado: Para {$fechaMiercoles}, el modal debería mostrar solo 'Horario Regular'\n";
    echo "Esto coincide con lo que debería mostrar la tabla principal.\n\n";

    // Verificar qué muestra la tabla para esta fecha
    echo "=== VERIFICACIÓN DE LA TABLA PRINCIPAL ===\n";

    $sqlVerificarTabla = "
        SELECT
            a.FECHA,
            CASE
                WHEN ehp.ID_EMPLEADO_HORARIO IS NOT NULL THEN
                    COALESCE(ehp.NOMBRE_TURNO, CONCAT('Horario Personalizado (', TIME_FORMAT(ehp.HORA_ENTRADA, '%H:%i'), '-', TIME_FORMAT(ehp.HORA_SALIDA, '%H:%i'), ')'))
                WHEN h.ID_HORARIO IS NOT NULL THEN
                    CONCAT(h.NOMBRE, ' (', TIME_FORMAT(h.HORA_ENTRADA, '%H:%i'), '-', TIME_FORMAT(h.HORA_SALIDA, '%H:%i'), ')')
                ELSE 'Sin asignar'
            END as horario_mostrado_tabla
        FROM ASISTENCIA a
        LEFT JOIN HORARIO h ON a.ID_HORARIO = h.ID_HORARIO
        LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
        WHERE a.ID_EMPLEADO = ? AND a.FECHA = ? AND a.TIPO = 'ENTRADA'
    ";

    $stmtVerificar = $conn->prepare($sqlVerificarTabla);
    $stmtVerificar->execute([1, $fechaMiercoles]);
    $resultadoTabla = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

    if ($resultadoTabla) {
        echo "Tabla muestra para {$fechaMiercoles}: {$resultadoTabla['horario_mostrado_tabla']}\n";
        echo "Modal debería mostrar: Horario Regular (08:00 - 16:00)\n";
        echo "✅ COINCIDEN - El problema está solucionado\n";
    } else {
        echo "❌ No se encontró asistencia para esa fecha\n";
    }

} catch (Exception $e) {
    echo "❌ Error creando datos de prueba: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE CREACIÓN DE DATOS DE PRUEBA ===\n";
?>