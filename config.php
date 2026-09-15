<?php
// Configuración general del sitio "Sakoneta Gimnasia Erritmiko Taldea"
// -----------------------------------------------------------
// Cambia estos valores antes de publicar el sitio en un servidor real.

define('SITE_NAME', 'Sakoneta Gimnasia Erritmiko Taldea');
define('SITE_SHORT', 'Sakoneta');
define('SITE_CLAIM', 'Erritmoa, grazia eta talde-lana');

// La base de datos y otros archivos sensibles se guardan FUERA de la
// carpeta pública del sitio (un nivel por encima), no dentro de
// "data/". Así, aunque el servidor no esté configurado para bloquear
// el acceso a "data/" (por ejemplo, en Nginx sin la regla adecuada —
// el .htaccess de Apache no sirve de nada ahí), esos archivos
// sencillamente no están dentro de lo que el servidor web puede
// llegar a servir, pase lo que pase con la configuración.
//
// Si por lo que sea no se puede crear/escribir esa carpeta externa
// (permisos del sistema de archivos), se usa "data/" dentro del
// proyecto como alternativa, y queda anotado en el registro de
// errores para que lo repases — en ese caso es imprescindible que
// el .htaccess de "data/" esté funcionando de verdad.
$carpetaPrivadaExterna = dirname(__DIR__) . '/sakoneta-datos-privados';
$carpetaPrivadaProyecto = __DIR__ . '/data';

if (!is_dir($carpetaPrivadaExterna)) {
    @mkdir($carpetaPrivadaExterna, 0770, true);
}

if (is_dir($carpetaPrivadaExterna) && is_writable($carpetaPrivadaExterna)) {
    define('CARPETA_PRIVADA', $carpetaPrivadaExterna);
} else {
    define('CARPETA_PRIVADA', $carpetaPrivadaProyecto);
    error_log('Sakoneta: no se ha podido usar una carpeta fuera de la web pública para los datos privados (revisa permisos de escritura en ' . dirname($carpetaPrivadaExterna) . '). Usando "' . $carpetaPrivadaProyecto . '" como alternativa: asegúrate de que su .htaccess bloquea el acceso web, sobre todo si el servidor es Nginx.');
}

define('DB_PATH', CARPETA_PRIVADA . '/club.sqlite');

// Migración automática, una sola vez: si ya había una base de datos
// en la ubicación antigua (dentro de la carpeta pública) y todavía no
// existe ninguna en la nueva ubicación privada, se traslada sola.
// No hace falta mover nada a mano por SSH.
$dbAntigua = __DIR__ . '/data/club.sqlite';
if ($dbAntigua !== DB_PATH && is_file($dbAntigua) && !is_file(DB_PATH)) {
    @rename($dbAntigua, DB_PATH);
}

// Nombre de usuario del primer administrador. La contraseña NUNCA se
// guarda aquí: la primera vez que se instala el sitio (o tras esta
// actualización, si el sitio ya existía) se genera sola al azar y se
// escribe una única vez en CARPETA_PRIVADA/contrasena-inicial-admin.txt
// — nunca en un archivo de código, porque un repositorio puede acabar
// siendo público y ese dato quedaría ahí para siempre. Cambia la
// contraseña cuanto antes desde "Cambiar contraseña" en el panel.
define('ADMIN_USER', 'admin');

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
