<?php
/**
 * Configuración del portal principal con los sistemas disponibles.
 * Cada entrada representa un sistema aislado (puede estar en otra ruta, subdominio o servidor)
 * que deseamos exponer desde la página principal.
 */

$toBool = static function ($value, bool $default = false): bool {
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    $filtered = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

    return $filtered !== null ? $filtered : $default;
};

$normalizeStatus = static function ($value, string $default = 'online'): string {
    $allowed = ['online', 'maintenance', 'offline', 'coming-soon'];

    if (!is_string($value) || $value === '') {
        return $default;
    }

    $value = strtolower(trim($value));

    return in_array($value, $allowed, true) ? $value : $default;
};

return [
    [
        'key' => 'synktime',
        'name' => 'SynkTime',
        'description' => 'Plataforma de asistencia inteligente para controlar turnos, incidencias y puntualidad.',
        'url' => getenv('PORTAL_SYSTEM_SYNKTIME_URL') ?: '/login.php',
        'cta' => getenv('PORTAL_SYSTEM_SYNKTIME_CTA') ?: 'Entrar a SynkTime',
        'badge' => getenv('PORTAL_SYSTEM_SYNKTIME_BADGE') ?: 'Operativo',
        'status' => $normalizeStatus(getenv('PORTAL_SYSTEM_SYNKTIME_STATUS'), 'online'),
        'health_url' => getenv('PORTAL_SYSTEM_SYNKTIME_HEALTH_URL') ?: null,
        'icon' => getenv('PORTAL_SYSTEM_SYNKTIME_ICON') ?: 'fa-regular fa-clock',
        'color' => getenv('PORTAL_SYSTEM_SYNKTIME_COLOR') ?: '#2563EB',
        'image' => getenv('PORTAL_SYSTEM_SYNKTIME_IMAGE') ?: '/assets/img/synktime-logo.png',
        'open_in_new_tab' => $toBool(getenv('PORTAL_SYSTEM_SYNKTIME_NEW_TAB'), false),
        'tags' => ['Kromez', 'Asistencia'],
    ],
];
