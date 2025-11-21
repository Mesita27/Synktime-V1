<?php
// Función de utilidad para normalizar horas al formato HH:MM:SS
function normalizarHora($hora) {
    if (empty($hora)) {
        return '00:00:00';
    }

    // Si ya tiene formato HH:MM:SS, devolver tal cual
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
        return $hora;
    }

    // Si tiene formato HH:MM, agregar :00
    if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
        return $hora . ':00';
    }

    // Si tiene formato H:MM, agregar 0 al inicio y :00 al final
    if (preg_match('/^\d{1}:\d{2}$/', $hora)) {
        return '0' . $hora . ':00';
    }

    // Para cualquier otro formato, intentar parsear con strtotime
    $timestamp = strtotime($hora);
    if ($timestamp !== false) {
        return date('H:i:s', $timestamp);
    }

    // Si no se puede parsear, devolver 00:00:00
    return '00:00:00';
}

// Función para comparar horas considerando tolerancia
function compararHorasConTolerancia($hora_real, $hora_programada, $tolerancia_minutos = 0) {
    $hora_real_norm = normalizarHora($hora_real);
    $hora_programada_norm = normalizarHora($hora_programada);

    $ts_real = strtotime("2024-01-01 $hora_real_norm");
    $ts_programada = strtotime("2024-01-01 $hora_programada_norm");

    if ($ts_real === false || $ts_programada === false) {
        return false;
    }

    $diferencia_minutos = abs($ts_real - $ts_programada) / 60;

    return $diferencia_minutos <= $tolerancia_minutos;
}

// Función para determinar estado de asistencia basado en hora y tolerancia
function determinarEstadoAsistencia($hora_real, $hora_programada, $tolerancia_minutos = 0, $tipo = 'ENTRADA') {
    $hora_real_norm = normalizarHora($hora_real);
    $hora_programada_norm = normalizarHora($hora_programada);

    $ts_real = strtotime("2024-01-01 $hora_real_norm");
    $ts_programada = strtotime("2024-01-01 $hora_programada_norm");

    if ($ts_real === false || $ts_programada === false) {
        return 'DESCONOCIDO';
    }

    $diferencia_segundos = $ts_real - $ts_programada;
    $diferencia_minutos = abs($diferencia_segundos) / 60;

    if ($tipo === 'SALIDA') {
        // Para salidas, si llega dentro de la tolerancia (antes o después), es NORMAL
        if ($diferencia_minutos <= $tolerancia_minutos) {
            return 'NORMAL';
        } elseif ($diferencia_segundos < 0) {
            // Llegó antes (temprano)
            return 'TEMPRANO';
        } else {
            // Llegó después (tardanza)
            return 'TARDANZA';
        }
    } else {
        // Para entradas
        if ($diferencia_minutos <= $tolerancia_minutos) {
            return 'NORMAL';
        } elseif ($diferencia_segundos < 0) {
            // Llegó antes (temprano)
            return 'TEMPRANO';
        } else {
            // Llegó después (tardanza)
            return 'TARDANZA';
        }
    }
}

