<?php
/**
 * URL base pública de la aplicación (con slash final).
 * Vacío = se calcula solo con cada petición (host/IP, http/https, carpeta del proyecto).
 * Úsalo solo si la detección automática falla (proxy, dominio distinto, etc.).
 */
if (!defined('BASE_URL_OVERRIDE')) {
    define('BASE_URL_OVERRIDE', '');
}

/**
 * Construye la URL base desde $_SERVER (mismo host/IP que usa el navegador).
 */
function liga_futbol_base_url() {
    if (BASE_URL_OVERRIDE !== '') {
        return rtrim((string) BASE_URL_OVERRIDE, '/') . '/';
    }
    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return 'http://localhost/LIGA_FUTBOL/';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '/index.php';
    $dir = dirname($scriptName);
    if ($dir === '/' || $dir === '.' || $dir === '') {
        $basePath = '/';
    } else {
        $basePath = rtrim($dir, '/') . '/';
    }
    return $scheme . '://' . $host . $basePath;
}

if (!defined('BASE_URL')) {
    define('BASE_URL', liga_futbol_base_url());
}

define('APP_NAME', 'Gestión de Ligas de Fútbol');
/** Zona horaria (México). Afecta date(), strtotime, etc. */
define('APP_TIMEZONE', 'America/Mexico_City');
date_default_timezone_set(APP_TIMEZONE);
/** Firma de URLs en QR de asistencia por jugador (cámbielo en producción). */
define('ATTENDANCE_QR_SECRET', 'LIGA_FUTBOL_QR_ASISTENCIA_CAMBIAR_EN_PRODUCCION');
?>
