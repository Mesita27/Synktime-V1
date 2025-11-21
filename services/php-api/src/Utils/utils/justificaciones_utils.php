<?php
/**
 * Utilidades para manejo de justificaciones en el sistema de asistencia
 */

/**
 * Verifica si un turno específico está justificado para un empleado en una fecha determinada
 *
 * @param int $idEmpleado ID del empleado
 * @param int $idEmpleadoHorario ID del horario del empleado (ID_EMPLEADO_HORARIO)
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @param PDO $conn Conexión a la base de datos
 * @return bool True si el turno está justificado, false en caso contrario
 */
function turnoEstaJustificado($idEmpleado, $idEmpleadoHorario, $fecha, $conn) {
    try {
        // Buscar justificaciones para este empleado, fecha y turno específico
        $sql = "
            SELECT COUNT(*) as total
            FROM justificaciones
            WHERE empleado_id = ?
            AND fecha_falta = ?
            AND (
                (justificar_todos_turnos = 1)
                OR (turno_id = ?)
                OR (turnos_ids IS NOT NULL AND JSON_CONTAINS(turnos_ids, ?))
            )
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$idEmpleado, $fecha, $idEmpleadoHorario, (string)$idEmpleadoHorario]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;

    } catch (Exception $e) {
        error_log("Error verificando justificación de turno: " . $e->getMessage());
        return false; // En caso de error, asumir que no está justificado
    }
}

/**
 * Obtiene todos los turnos justificados para un empleado en una fecha específica
 *
 * @param int $idEmpleado ID del empleado
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @param PDO $conn Conexión a la base de datos
 * @return array Array con IDs de horarios justificados
 */
function obtenerTurnosJustificados($idEmpleado, $fecha, $conn) {
    try {
        $turnosJustificados = [];

        // Buscar justificaciones para este empleado y fecha
        $sql = "
            SELECT turno_id, turnos_ids, justificar_todos_turnos
            FROM justificaciones
            WHERE empleado_id = ?
            AND fecha_falta = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$idEmpleado, $fecha]);
        $justificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($justificaciones as $justificacion) {
            if ($justificacion['justificar_todos_turnos'] == 1) {
                // Si justifica todos los turnos, devolver un indicador especial
                return ['TODOS_LOS_TURNOS'];
            }

            // Agregar turno específico si existe
            if ($justificacion['turno_id']) {
                $turnosJustificados[] = $justificacion['turno_id'];
            }

            // Agregar turnos del array JSON si existe
            if ($justificacion['turnos_ids']) {
                $turnosArray = json_decode($justificacion['turnos_ids'], true);
                if (is_array($turnosArray)) {
                    $turnosJustificados = array_merge($turnosJustificados, $turnosArray);
                }
            }
        }

        // Eliminar duplicados
        return array_unique($turnosJustificados);

    } catch (Exception $e) {
        error_log("Error obteniendo turnos justificados: " . $e->getMessage());
        return [];
    }
}

/**
 * Verifica si un empleado puede registrar asistencia considerando justificaciones
 *
 * @param int $idEmpleado ID del empleado
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @param array $horariosDisponibles Array de horarios disponibles
 * @param PDO $conn Conexión a la base de datos
 * @return array Array con horarios filtrados (excluyendo justificados) y información adicional
 */
function filtrarHorariosPorJustificaciones($idEmpleado, $fecha, $horariosDisponibles, $conn) {
    $turnosJustificados = obtenerTurnosJustificados($idEmpleado, $fecha, $conn);

    // Si todos los turnos están justificados, devolver array vacío
    if (in_array('TODOS_LOS_TURNOS', $turnosJustificados)) {
        return [
            'horarios_disponibles' => [],
            'turnos_justificados' => $turnosJustificados,
            'todos_justificados' => true
        ];
    }

    // Filtrar horarios disponibles excluyendo los justificados
    $horariosFiltrados = array_filter($horariosDisponibles, function($horario) use ($turnosJustificados) {
        return !in_array($horario['ID_EMPLEADO_HORARIO'], $turnosJustificados);
    });

    return [
        'horarios_disponibles' => array_values($horariosFiltrados), // Reindexar array
        'turnos_justificados' => $turnosJustificados,
        'todos_justificados' => false
    ];
}
?>