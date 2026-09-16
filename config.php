<?php
// Configuración general del sitio "Sakoneta Gimnasia Erritmiko Taldea"
// -----------------------------------------------------------
// Cambia estos valores antes de publicar el sitio en un servidor real.

define('SITE_NAME', 'Sakoneta Gimnasia Erritmiko Taldea');
define('SITE_SHORT', 'Sakoneta');
define('SITE_CLAIM', 'Erritmoa, grazia eta talde-lana');

// Ubicación de los datos privados (base de datos, contraseña inicial
// generada en el primer arranque).
//
// 1) Si se define la variable de entorno SAKONETA_PRIVATE_DIR, se usa
//    esa ruta tal cual: es la forma recomendada, porque es la única
//    que el administrador del servidor puede garantizar de verdad que
//    queda fuera de la carpeta pública. Se configura, por ejemplo:
//      Apache (dentro del <VirtualHost>): SetEnv SAKONETA_PRIVATE_DIR /var/lib/sakoneta
//      PHP-FPM (en el pool):              env[SAKONETA_PRIVATE_DIR] = /var/lib/sakoneta
// 2) Si no se ha definido, se prueba con una carpeta hermana del
//    proyecto (un nivel por encima) — pero, a diferencia de antes, NO
//    se da por hecho que eso queda fuera de lo público: se comprueba
//    de verdad contra el DOCUMENT_ROOT real que informa el propio
//    servidor en esta petición. Si el DocumentRoot configurado en el
//    servidor resulta ser una carpeta por encima del proyecto, esa
//    carpeta hermana SÍ quedaría dentro de lo público, y no se usa.
// 3) Si no hay ninguna ubicación que se pueda confirmar seguro, el
//    sitio se detiene con un error explicando qué hacer, en vez de
//    arriesgarse a guardar contraseñas y datos de gimnastas en una
//    carpeta que el navegador pueda llegar a servir.
function sakonetaCarpetaEsSegura(string $ruta): bool {
    // Por SSH (instalación/mantenimiento desde la terminal) no existe
    // el concepto de DocumentRoot; la comprobación real se hace de
    // todas formas en la primera petición web que llegue después.
    if (PHP_SAPI === 'cli' || empty($_SERVER['DOCUMENT_ROOT'])) {
        return true;
    }
    $rutaReal = realpath($ruta);
    $docRootReal = realpath($_SERVER['DOCUMENT_ROOT']);
    if (!$rutaReal || !$docRootReal) {
        return false;
    }
    return strpos($rutaReal . DIRECTORY_SEPARATOR, $docRootReal . DIRECTORY_SEPARATOR) !== 0;
}

$carpetaPrivada = null;
$carpetaEnv = getenv('SAKONETA_PRIVATE_DIR');

if ($carpetaEnv) {
    if (!is_dir($carpetaEnv)) {
        @mkdir($carpetaEnv, 0770, true);
    }
    if (is_dir($carpetaEnv) && is_writable($carpetaEnv)) {
        $carpetaPrivada = rtrim($carpetaEnv, '/');
    }
} else {
    $carpetaCandidata = dirname(__DIR__) . '/sakoneta-datos-privados';
    if (!is_dir($carpetaCandidata)) {
        @mkdir($carpetaCandidata, 0770, true);
    }
    if (is_dir($carpetaCandidata) && is_writable($carpetaCandidata) && sakonetaCarpetaEsSegura($carpetaCandidata)) {
        $carpetaPrivada = $carpetaCandidata;
    }
}

if ($carpetaPrivada === null) {
    http_response_code(500);
    error_log('Sakoneta: no se ha podido confirmar ninguna ubicación segura (fuera de la carpeta pública) para guardar los datos privados. Define la variable de entorno SAKONETA_PRIVATE_DIR.');
    die(
        "No se ha podido confirmar una ubicación segura y escribible, fuera de la carpeta " .
        "pública del servidor, para guardar la base de datos y otros datos sensibles.\n\n" .
        "Soluciónalo definiendo la variable de entorno SAKONETA_PRIVATE_DIR con una ruta " .
        "fuera del DocumentRoot del servidor, por ejemplo:\n\n" .
        "  Apache (dentro del <VirtualHost>):\n" .
        "    SetEnv SAKONETA_PRIVATE_DIR /var/lib/sakoneta\n\n" .
        "  PHP-FPM (en el pool, por ejemplo /etc/php/8.3/fpm/pool.d/www.conf):\n" .
        "    env[SAKONETA_PRIVATE_DIR] = /var/lib/sakoneta\n\n" .
        "Esa carpeta debe existir (o poder crearse) y ser escribible por el usuario con el " .
        "que corre PHP (normalmente www-data). Después, recarga Apache/PHP-FPM."
    );
}

define('CARPETA_PRIVADA', $carpetaPrivada);
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
