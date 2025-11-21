<?php
/**
 * Configuración de zona horaria para Bogotá, Colombia
 * Este archivo debe incluirse al inicio de todos los archivos PHP
 * que manejen fechas y horas
 */

// Establecer zona horaria de Bogotá, Colombia (UTC-5)
date_default_timezone_set('America/Bogota');

/**
 * Función para obtener la fecha actual en zona horaria de Bogotá
 * @param string $format Formato de fecha (por defecto 'Y-m-d H:i:s')
 * @return string Fecha formateada en zona horaria de Bogotá
 */
function getBogotaDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * Función para obtener solo la fecha actual en zona horaria de Bogotá
 * @return string Fecha en formato Y-m-d
 */
function getBogotaDate() {
    return date('Y-m-d');
}

/**
 * Función para obtener solo la hora actual en zona horaria de Bogotá
 * @return string Hora en formato H:i:s
 */
function getBogotaTime(string $format = 'H:i:s') {
    return date($format);
}

/**
 * Normaliza un valor de hora para almacenarlo en columnas CHAR(5) (HH:MM)
 *
 * @param string|null $time Valor de hora a normalizar (acepta H:i, H:i:s, timestamps o DateTime strings)
 * @return string Hora en formato HH:MM
 */
function formatTimeForAttendance(?string $time = null): string {
    // Si no se proporciona hora, usar la hora actual
    if ($time === null || $time === '') {
        return date('H:i');
    }

    $time = trim($time);

    // Intentar parsear usando strtotime
    $timestamp = strtotime($time);
    if ($timestamp !== false) {
        return date('H:i', $timestamp);
    }

    // Buscar patrones HH:MM o HH:MM:SS manualmente
    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $time, $matches)) {
        $hour = max(0, min(23, (int) $matches[1]));
        $minute = max(0, min(59, (int) $matches[2]));
        return sprintf('%02d:%02d', $hour, $minute);
    }

    // Fallback: hora actual
    return date('H:i');
}

/**
 * Función para convertir timestamp a zona horaria de Bogotá
 * @param string $timestamp Timestamp a convertir
 * @param string $format Formato de salida
 * @return string Fecha/hora convertida
 */
function convertToBogotaTime($timestamp, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($timestamp));
}

// Verificar que la zona horaria se estableció correctamente
if (date_default_timezone_get() !== 'America/Bogota') {
    error_log("Error: No se pudo establecer la zona horaria America/Bogota");
}
?>