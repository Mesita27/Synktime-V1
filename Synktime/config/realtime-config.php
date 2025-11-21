<?php
/**
 * Configuración para funcionalidades en tiempo real
 */

// Configuración de zona horaria
date_default_timezone_set('America/Bogota'); // Ajustar según tu zona horaria

// Función para obtener la fecha y hora actual
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

// Función para formatear fecha para mostrar
function formatDisplayDate($date) {
    return date('d/m/Y H:i:s', strtotime($date));
}

// Función para obtener solo la fecha actual
function getCurrentDate() {
    return date('Y-m-d');
}

// Función para validar formato de fecha
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Configuración de refresh automático (en segundos)
define('DASHBOARD_REFRESH_INTERVAL', 30); // 30 segundos

// Configuración de límites
define('MAX_RECENT_ACTIVITIES', 15);
define('MAX_CHART_DATA_POINTS', 24); // Para gráfico por horas
?>