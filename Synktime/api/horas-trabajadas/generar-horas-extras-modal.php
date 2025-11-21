<?php

/**
 * API para generar horas extras desde el modal de aprobación
 * Permite hacer consultas independientes y generar horas extras
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

// Función para verificar si ya existen horas extras duplicadas
function verificarHorasExtrasDuplicadas($idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $tipoHorario, $pdo) {
    $query = "
        SELECT COUNT(*) as count
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND FECHA = ?
        AND HORA_INICIO = ?
        AND HORA_FIN = ?
        AND TIPO_HORARIO = ?
        AND ESTADO_APROBACION = 'pendiente'
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idEmpleado, $fecha, $horaInicio, $horaFin, $tipoHorario]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Error verificando duplicados: " . $e->getMessage());
        return false;
    }
}

// Función para generar horas extras
function generarHorasExtrasSiNoExisten($idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $horasExtras, $tipoExtra, $tipoHorario, $pdo) {
    // Verificar si ya existen horas extras similares
    if (verificarHorasExtrasDuplicadas($idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $tipoHorario, $pdo)) {
        return false; // Ya existen
    }

    // Generar nuevas horas extras para aprobación
    $query = "
        INSERT INTO horas_extras_aprobacion
        (ID_EMPLEADO, ID_EMPLEADO_HORARIO, FECHA, HORA_INICIO, HORA_FIN, HORAS_EXTRAS, TIPO_EXTRA, TIPO_HORARIO, ESTADO_APROBACION, CREATED_AT, UPDATED_AT)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW(), NOW())
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $horasExtras, $tipoExtra, $tipoHorario]);
        return true; // Se generaron horas extras
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'duplicate key') !== false) {
            return false; // Ya existe
        }
        error_log("Error al generar horas extras: " . $e->getMessage());
        return false;
    }
}

// Función para obtener empleados con asistencia en un rango de fechas
function obtenerEmpleadosConAsistencia($fechaDesde, $fechaHasta, $sedeId = null, $establecimientoId = null, $pdo) {
    $query = "
        SELECT DISTINCT
            a.ID_EMPLEADO,
            e.NOMBRE as empleado_nombre,
            s.NOMBRE as sede,
            est.NOMBRE as establecimiento
        FROM asistencia a
        JOIN empleados e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        LEFT JOIN sedes s ON e.ID_SEDE = s.ID_SEDE
        LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        WHERE a.FECHA BETWEEN ? AND ?
    ";

    $params = [$fechaDesde, $fechaHasta];

    if ($sedeId) {
        $query .= " AND e.ID_SEDE = ?";
        $params[] = $sedeId;
    }

    if ($establecimientoId) {
        $query .= " AND e.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimientoId;
    }

    $query .= " ORDER BY e.NOMBRE";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo empleados con asistencia: " . $e->getMessage());
        return [];
    }
}

// Función para calcular horas extras para un empleado en una fecha específica
function calcularHorasExtrasParaEmpleado($idEmpleado, $fecha, $pdo) {
    $horasExtrasGeneradas = [];

    try {
        // Obtener registros de asistencia para el empleado en la fecha
        $queryAsistencia = "
            SELECT a.*, e.ID_SEDE, e.ID_ESTABLECIMIENTO
            FROM asistencia a
            JOIN empleados e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            WHERE a.ID_EMPLEADO = ? AND a.FECHA = ?
            ORDER BY a.HORA_ENTRADA
        ";

        $stmt = $pdo->prepare($queryAsistencia);
        $stmt->execute([$idEmpleado, $fecha]);
        $registrosAsistencia = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($registrosAsistencia)) {
            return $horasExtrasGeneradas;
        }

        // Obtener horario del empleado para esa fecha
        $queryHorario = "
            SELECT eh.*, h.*
            FROM empleado_horario eh
            JOIN horarios h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE eh.ID_EMPLEADO = ?
            AND eh.FECHA_INICIO <= ?
            AND (eh.FECHA_FIN >= ? OR eh.FECHA_FIN IS NULL)
            ORDER BY eh.FECHA_INICIO DESC
            LIMIT 1
        ";

        $stmt = $pdo->prepare($queryHorario);
        $stmt->execute([$idEmpleado, $fecha, $fecha]);
        $horario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Procesar cada registro de asistencia
        foreach ($registrosAsistencia as $registro) {
            $horaEntrada = $registro['HORA_ENTRADA'];
            $horaSalida = $registro['HORA_SALIDA'];

            if (!$horaEntrada || !$horaSalida) continue;

            // Si no hay horario asignado, todo el tiempo es extra
            if (!$horario) {
                $horasTrabajadas = calcularDiferenciaHoras($horaEntrada, $horaSalida);
                if ($horasTrabajadas > 0) {
                    $tipoHorario = determinarTipoHorario($horaEntrada, $horaSalida);
                    $horasExtrasGeneradas[] = [
                        'id_empleado' => $idEmpleado,
                        'id_empleado_horario' => null,
                        'fecha' => $fecha,
                        'hora_inicio' => $horaEntrada,
                        'hora_fin' => $horaSalida,
                        'horas_extras' => $horasTrabajadas,
                        'tipo_extra' => 'despues',
                        'tipo_horario' => $tipoHorario
                    ];
                }
                continue;
            }

            // Calcular horas extras comparando con horario programado
            $horaEntradaProgramada = $horario['HORA_ENTRADA'];
            $horaSalidaProgramada = $horario['HORA_SALIDA'];

            // Lógica simplificada para determinar horas extras
            if (strtotime($horaEntrada) < strtotime($horaEntradaProgramada) ||
                strtotime($horaSalida) > strtotime($horaSalidaProgramada)) {

                $horaInicioExtra = strtotime($horaEntrada) < strtotime($horaEntradaProgramada) ?
                    $horaEntrada : $horaEntradaProgramada;
                $horaFinExtra = strtotime($horaSalida) > strtotime($horaSalidaProgramada) ?
                    $horaSalida : $horaSalidaProgramada;

                $horasExtras = calcularDiferenciaHoras($horaInicioExtra, $horaFinExtra);
                if ($horasExtras > 0) {
                    $tipoHorario = determinarTipoHorario($horaInicioExtra, $horaFinExtra);
                    $horasExtrasGeneradas[] = [
                        'id_empleado' => $idEmpleado,
                        'id_empleado_horario' => $horario['ID_EMPLEADO_HORARIO'],
                        'fecha' => $fecha,
                        'hora_inicio' => $horaInicioExtra,
                        'hora_fin' => $horaFinExtra,
                        'horas_extras' => $horasExtras,
                        'tipo_extra' => 'despues',
                        'tipo_horario' => $tipoHorario
                    ];
                }
            }
        }

    } catch (Exception $e) {
        error_log("Error calculando horas extras para empleado $idEmpleado: " . $e->getMessage());
    }

    return $horasExtrasGeneradas;
}

// Función auxiliar para calcular diferencia de horas
function calcularDiferenciaHoras($horaInicio, $horaFin) {
    $inicio = strtotime($horaInicio);
    $fin = strtotime($horaFin);
    $diferencia = $fin - $inicio;
    return round($diferencia / 3600, 2); // Convertir a horas
}

// Función para determinar tipo de horario (diurno/nocturno)
function determinarTipoHorario($horaInicio, $horaFin) {
    $horaInicioNum = (int)date('H', strtotime($horaInicio));
    $horaFinNum = (int)date('H', strtotime($horaFin));

    // Si cruza el período nocturno (21:00 - 06:00)
    if (($horaInicioNum >= 21 || $horaInicioNum <= 6) ||
        ($horaFinNum >= 21 || $horaFinNum <= 6) ||
        ($horaInicioNum <= 6 && $horaFinNum >= 21)) {
        return 'nocturno';
    }

    return 'diurno';
}

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getConnection();

        $fechaDesde = $_POST['fecha_desde'] ?? date('Y-m-d');
        $fechaHasta = $_POST['fecha_hasta'] ?? date('Y-m-d');
        $sedeId = $_POST['sede_id'] ?? null;
        $establecimientoId = $_POST['establecimiento_id'] ?? null;
        $empleadosSeleccionados = isset($_POST['empleados']) ? $_POST['empleados'] : [];

        // Si no se especifican empleados, obtener todos con asistencia
        if (empty($empleadosSeleccionados)) {
            $empleados = obtenerEmpleadosConAsistencia($fechaDesde, $fechaHasta, $sedeId, $establecimientoId, $pdo);
            $empleadosSeleccionados = array_column($empleados, 'ID_EMPLEADO');
        }

        $horasExtrasGeneradas = 0;
        $horasExtrasExistentes = 0;

        // Procesar cada empleado
        foreach ($empleadosSeleccionados as $idEmpleado) {
            // Obtener fechas con asistencia para este empleado
            $fechasConAsistencia = [];

            if ($fechaDesde === $fechaHasta) {
                // Solo una fecha
                $fechasConAsistencia[] = $fechaDesde;
            } else {
                // Rango de fechas - obtener fechas específicas con asistencia
                $query = "
                    SELECT DISTINCT FECHA
                    FROM asistencia
                    WHERE ID_EMPLEADO = ? AND FECHA BETWEEN ? AND ?
                    ORDER BY FECHA
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$idEmpleado, $fechaDesde, $fechaHasta]);
                $fechasConAsistencia = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'FECHA');
            }

            // Procesar cada fecha
            foreach ($fechasConAsistencia as $fecha) {
                $horasExtrasEmpleado = calcularHorasExtrasParaEmpleado($idEmpleado, $fecha, $pdo);

                foreach ($horasExtrasEmpleado as $horaExtra) {
                    $generado = generarHorasExtrasSiNoExisten(
                        $horaExtra['id_empleado'],
                        $horaExtra['id_empleado_horario'],
                        $horaExtra['fecha'],
                        $horaExtra['hora_inicio'],
                        $horaExtra['hora_fin'],
                        $horaExtra['horas_extras'],
                        $horaExtra['tipo_extra'],
                        $horaExtra['tipo_horario'],
                        $pdo
                    );

                    if ($generado) {
                        $horasExtrasGeneradas++;
                    } else {
                        $horasExtrasExistentes++;
                    }
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Consulta completada. Se generaron $horasExtrasGeneradas nuevas horas extras. $horasExtrasExistentes ya existían.",
            'horas_extras_generadas' => $horasExtrasGeneradas,
            'horas_extras_existentes' => $horasExtrasExistentes
        ]);

    } catch (Exception $e) {
        error_log("Error en generar-horas-extras-modal.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>