<?php
// Configuración general del sitio "Sakoneta Gimnasia Erritmiko Taldea"
// -----------------------------------------------------------
// Cambia estos valores antes de publicar el sitio en un servidor real.

define('SITE_NAME', 'Sakoneta Gimnasia Erritmiko Taldea');
define('SITE_SHORT', 'Sakoneta');
define('SITE_CLAIM', 'Erritmoa, grazia eta talde-lana');
define('DB_PATH', __DIR__ . '/data/club.sqlite');

// Usuario y contraseña del panel de administración.
// La contraseña por defecto es "sakoneta2026" — CÁMBIALA generando un
// nuevo hash con: php -r "echo password_hash('tu_password', PASSWORD_DEFAULT);"
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2b$10$mnKMTvJEg5wzwPa52ryqNOTTLGdRrH6Re2aXVJU20Ie3JFRk7L.PW');

date_default_timezone_set('Europe/Madrid');

// Cambia esto a true SOLO mientras desarrollas en local, para ver los
// errores de PHP en pantalla. Déjalo en false en un servidor real: así
// los errores se registran en el log en vez de mostrarse a los visitantes.
define('MODO_DEBUG', false);

if (MODO_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Cookie de sesión más segura: no accesible desde JavaScript, no se
// envía en peticiones de terceros y solo por HTTPS si el sitio ya usa HTTPS.
$httpsActivo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $httpsActivo,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
