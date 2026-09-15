<?php
// Configuración general del sitio "Sakoneta Gimnasia Erritmiko Taldea"
// -----------------------------------------------------------
// Cambia estos valores antes de publicar el sitio en un servidor real.

define('SITE_NAME', 'Sakoneta Gimnasia Erritmiko Taldea');
define('SITE_SHORT', 'Sakoneta');
define('SITE_CLAIM', 'Erritmoa, grazia eta talde-lana');
define('DB_PATH', __DIR__ . '/data/club.sqlite');

// Contraseña de administrador: se cambia SIEMPRE desde el panel
// ("Cambiar contraseña" en el menú), nunca editando este archivo a
// mano. El hash de aquí abajo es solo el que se usa la primerísima
// vez, antes de que nadie haya cambiado la contraseña desde la web.
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2b$10$F7HjHqmCpX.6v6s7lIjbiuRV5vhrdFq.hRqGbHZHF3vwDJrr/Ilzy');

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
