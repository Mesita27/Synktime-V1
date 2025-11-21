/**
 * Funciones JavaScript para manejo de zona horaria de Bogotá, Colombia
 * Incluir este archivo en todas las páginas que manejen fechas/horas
 */

/**
 * Obtiene la fecha/hora actual en zona horaria de Bogotá
 * @param {boolean} includeTime - Si incluir la hora (por defecto true)
 * @returns {Date} Objeto Date ajustado a zona horaria de Bogotá
 */
function getBogotaDate(includeTime = true) {
    // UTC-5 para Bogotá (sin considerar horario de verano)
    const now = new Date();
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const bogotaTime = new Date(utc + (-5 * 3600000));
    return bogotaTime;
}

/**
 * Obtiene la fecha actual en formato YYYY-MM-DD en zona horaria de Bogotá
 * @returns {string} Fecha en formato YYYY-MM-DD
 */
function getBogotaDateString() {
    const bogotaDate = getBogotaDate();
    return bogotaDate.getFullYear() + '-' + 
           String(bogotaDate.getMonth() + 1).padStart(2, '0') + '-' + 
           String(bogotaDate.getDate()).padStart(2, '0');
}

/**
 * Obtiene la hora actual en formato HH:MM:SS en zona horaria de Bogotá
 * @returns {string} Hora en formato HH:MM:SS
 */
function getBogotaTimeString() {
    const bogotaDate = getBogotaDate();
    return String(bogotaDate.getHours()).padStart(2, '0') + ':' + 
           String(bogotaDate.getMinutes()).padStart(2, '0') + ':' + 
           String(bogotaDate.getSeconds()).padStart(2, '0');
}

/**
 * Obtiene fecha/hora completa en formato YYYY-MM-DD HH:MM:SS en zona horaria de Bogotá
 * @returns {string} Fecha/hora en formato YYYY-MM-DD HH:MM:SS
 */
function getBogotaDateTimeString() {
    return getBogotaDateString() + ' ' + getBogotaTimeString();
}

/**
 * Obtiene fecha/hora en formato ISO pero ajustada a zona horaria de Bogotá
 * @returns {string} Fecha/hora en formato ISO ajustada a Bogotá
 */
function getBogotaISOString() {
    const bogotaDate = getBogotaDate();
    // Crear ISO string pero con zona horaria de Bogotá
    const isoString = bogotaDate.getFullYear() + '-' + 
                      String(bogotaDate.getMonth() + 1).padStart(2, '0') + '-' + 
                      String(bogotaDate.getDate()).padStart(2, '0') + 'T' +
                      String(bogotaDate.getHours()).padStart(2, '0') + ':' + 
                      String(bogotaDate.getMinutes()).padStart(2, '0') + ':' + 
                      String(bogotaDate.getSeconds()).padStart(2, '0') + '.000-05:00';
    return isoString;
}

/**
 * Obtiene el día de la semana en zona horaria de Bogotá
 * @returns {number} Día de la semana (0=domingo, 1=lunes, etc.)
 */
function getBogotaDayOfWeek() {
    return getBogotaDate().getDay();
}

/**
 * Formatea una fecha en zona horaria de Bogotá para mostrar al usuario
 * @param {Date|string} date - Fecha a formatear (opcional, usa fecha actual si no se proporciona)
 * @param {string} locale - Locale para formateo (por defecto 'es-CO')
 * @returns {string} Fecha formateada
 */
function formatBogotaDate(date = null, locale = 'es-CO') {
    const dateToFormat = date ? new Date(date) : getBogotaDate();
    
    // Crear opciones para formateo con zona horaria de Bogotá
    const options = {
        timeZone: 'America/Bogota',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };
    
    return dateToFormat.toLocaleString(locale, options);
}

/**
 * Funciones de compatibilidad para reemplazar uso directo de new Date()
 */

// Reemplazar new Date().toISOString() con versión de Bogotá
function toISOStringBogota() {
    return getBogotaISOString();
}

// Reemplazar new Date().toLocaleDateString() con versión de Bogotá
function toLocaleDateStringBogota(locale = 'es-CO') {
    const bogotaDate = getBogotaDate();
    const options = {
        timeZone: 'America/Bogota',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    };
    return bogotaDate.toLocaleDateString(locale, options);
}

// Reemplazar Date.now() considerando zona horaria (aunque Date.now() es UTC, 
// estas funciones están disponibles para consistencia)
function dateNowBogota() {
    return getBogotaDate().getTime();
}

/**
 * Utilitarios para debugging
 */
function debugTimezone() {
    console.log('=== DEBUG ZONA HORARIA ===');
    console.log('Fecha/hora UTC:', new Date().toISOString());
    console.log('Fecha/hora Bogotá:', getBogotaISOString());
    console.log('Fecha Bogotá (YYYY-MM-DD):', getBogotaDateString());
    console.log('Hora Bogotá (HH:MM:SS):', getBogotaTimeString());
    console.log('Día de la semana Bogotá:', getBogotaDayOfWeek());
    console.log('========================');
}

// Exponer funciones globalmente para fácil acceso
window.Bogota = {
    getDate: getBogotaDate,
    getDateString: getBogotaDateString,
    getTimeString: getBogotaTimeString,
    getDateTimeString: getBogotaDateTimeString,
    getISOString: getBogotaISOString,
    getDayOfWeek: getBogotaDayOfWeek,
    formatDate: formatBogotaDate,
    toISOString: toISOStringBogota,
    toLocaleDateString: toLocaleDateStringBogota,
    now: dateNowBogota,
    debug: debugTimezone
};

console.log('✅ Funciones de zona horaria de Bogotá cargadas correctamente');
console.log('💡 Usa window.Bogota.* para acceder a las funciones');
console.log('💡 Ejemplo: window.Bogota.getDateString() para fecha actual en Bogotá');