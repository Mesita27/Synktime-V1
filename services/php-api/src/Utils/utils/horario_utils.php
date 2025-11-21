<?php
/**
 * Utilidades para determinar horarios de empleados
 * Funciones comunes para obtener el horario correcto de un empleado en una fecha específica
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Obtiene el horario correcto para un empleado en una fecha específica
 * Esta función centraliza la lógica de determinación de horarios
 *
 * @param int $employeeId ID del empleado
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @param PDO $pdo Conexión a la base de datos
 * @return array|null Información del horario o null si no se encuentra
 */
function obtenerHorarioEmpleado($employeeId, $fecha, $pdo = null) {
    if (!$pdo) {
        global $conn;
        $pdo = $conn;
    }

    // Calcular día de la semana (1=Lunes, 7=Domingo)
    $diaSemana = date('N', strtotime($fecha));

    // Buscar horario personalizado primero
    $stmtPersonalizado = $pdo->prepare("
        SELECT
            ehp.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO as horario_nombre,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.ORDEN_TURNO,
            ehp.ACTIVO,
            'personalizado' as tipo_horario,
            CASE WHEN ehp.ACTIVO = 'S' THEN 0 ELSE 1 END as prioridad_activo
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ?
        AND ehp.ID_DIA = ?
        AND ehp.FECHA_DESDE <= ?
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        ORDER BY prioridad_activo ASC, ehp.FECHA_DESDE DESC, ehp.ORDEN_TURNO ASC
        LIMIT 1
    ");

    $stmtPersonalizado->execute([$employeeId, $diaSemana, $fecha, $fecha]);
    $horarioPersonalizado = $stmtPersonalizado->fetch(PDO::FETCH_ASSOC);

    if ($horarioPersonalizado) {
        return $horarioPersonalizado;
    }

    // Si no hay horario personalizado, buscar en tabla HORARIO tradicional
    // (por compatibilidad con registros antiguos)
    $stmtHorario = $pdo->prepare("
        SELECT
            h.ID_HORARIO,
            h.NOMBRE as horario_nombre,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            'tradicional' as tipo_horario
        FROM EMPLEADO_HORARIO eh
        JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = ?
        AND ? BETWEEN eh.FECHA_DESDE AND COALESCE(eh.FECHA_HASTA, '9999-12-31')
        ORDER BY eh.FECHA_DESDE DESC
        LIMIT 1
    ");

    $stmtHorario->execute([$employeeId, $fecha]);
    $horarioTradicional = $stmtHorario->fetch(PDO::FETCH_ASSOC);

    if ($horarioTradicional) {
        return $horarioTradicional;
    }

    return null;
}

/**
 * Obtiene el horario correcto para un empleado en una fecha específica
 * Versión simplificada que retorna información básica del horario
 *
 * @param int $employeeId ID del empleado
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @param PDO $pdo Conexión a la base de datos
 * @return array Información del horario con campos estandarizados
 */
function obtenerHorarioEmpleadoSimplificado($employeeId, $fecha, $pdo = null) {
    $horario = obtenerHorarioEmpleado($employeeId, $fecha, $pdo);

    if (!$horario) {
        return [
            'ID_HORARIO' => null,
            'ID_EMPLEADO_HORARIO' => null,
            'horario_nombre' => 'Sin horario asignado',
            'HORA_ENTRADA' => null,
            'HORA_SALIDA' => null,
            'TOLERANCIA' => 15,
            'tipo_horario' => 'ninguno',
            'ACTIVO' => 'N'
        ];
    }

    return [
        'ID_HORARIO' => $horario['ID_HORARIO'] ?? null,
        'ID_EMPLEADO_HORARIO' => $horario['ID_EMPLEADO_HORARIO'] ?? null,
        'horario_nombre' => $horario['horario_nombre'],
        'HORA_ENTRADA' => $horario['HORA_ENTRADA'],
        'HORA_SALIDA' => $horario['HORA_SALIDA'],
        'TOLERANCIA' => $horario['TOLERANCIA'] ?? 15,
        'tipo_horario' => $horario['tipo_horario'],
        'ACTIVO' => $horario['ACTIVO'] ?? 'S'
    ];
}

/**
 * Obtiene la información de un horario específico por su ID_EMPLEADO_HORARIO
 * Esta función se usa cuando se conoce exactamente cuál horario usar (del registro de asistencia)
 *
 * @param int $idEmpleadoHorario ID del horario personalizado
 * @param PDO $pdo Conexión a la base de datos
 * @return array Información del horario específico
 */
function obtenerHorarioPorId($idEmpleadoHorario, $pdo = null) {
    if (!$pdo) {
        global $conn;
        $pdo = $conn;
    }

    if (!$idEmpleadoHorario) {
        return [
            'ID_HORARIO' => null,
            'ID_EMPLEADO_HORARIO' => null,
            'horario_nombre' => 'Sin horario asignado',
            'HORA_ENTRADA' => null,
            'HORA_SALIDA' => null,
            'TOLERANCIA' => 15,
            'tipo_horario' => 'ninguno',
            'ACTIVO' => 'N'
        ];
    }

    // Buscar el horario personalizado específico
    $stmt = $pdo->prepare("
        SELECT
            ehp.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO as horario_nombre,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.ORDEN_TURNO,
            'personalizado' as tipo_horario,
            ehp.ACTIVO
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO_HORARIO = ?
    ");

    $stmt->execute([$idEmpleadoHorario]);
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($horario) {
        return [
            'ID_HORARIO' => null,
            'ID_EMPLEADO_HORARIO' => $horario['ID_EMPLEADO_HORARIO'],
            'horario_nombre' => $horario['horario_nombre'],
            'HORA_ENTRADA' => $horario['HORA_ENTRADA'],
            'HORA_SALIDA' => $horario['HORA_SALIDA'],
            'TOLERANCIA' => $horario['TOLERANCIA'] ?? 15,
            'tipo_horario' => $horario['tipo_horario'],
            'ACTIVO' => $horario['ACTIVO'] ?? 'S'
        ];
    }

    // Si no se encuentra el horario personalizado, devolver valores por defecto
    return [
        'ID_HORARIO' => null,
        'ID_EMPLEADO_HORARIO' => null,
        'horario_nombre' => 'Horario no encontrado',
        'HORA_ENTRADA' => null,
        'HORA_SALIDA' => null,
        'TOLERANCIA' => 15,
        'tipo_horario' => 'no_encontrado',
        'ACTIVO' => 'N'
    ];
}
?>