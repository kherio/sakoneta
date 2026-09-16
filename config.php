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

// Alternativa a la variable de entorno para servidores donde no se
// tiene acceso a un <VirtualHost> propio (por ejemplo, un sitio
// colgado del VirtualHost por defecto de Apache): un archivo local,
// que NUNCA se sube a git (está en .gitignore), donde definir la
// ruta directamente. Créalo una sola vez en el servidor:
//
//   <?php
//   putenv('SAKONETA_PRIVATE_DIR=/ruta/fuera/del/docroot');
//
// y sobrevive a los "git pull" de después, porque git nunca lo toca.
$configLocal = __DIR__ . '/config.local.php';
if (is_file($configLocal)) {
    require $configLocal;
}

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
        "Soluciónalo con UNA de estas dos opciones:\n\n" .
        "  1) Variable de entorno SAKONETA_PRIVATE_DIR (si tienes un <VirtualHost> propio):\n" .
        "       Apache: SetEnv SAKONETA_PRIVATE_DIR /var/lib/sakoneta\n" .
        "       PHP-FPM (en el pool): env[SAKONETA_PRIVATE_DIR] = /var/lib/sakoneta\n\n" .
        "  2) Si no tienes acceso a un <VirtualHost> propio (por ejemplo, el sitio cuelga\n" .
        "     del VirtualHost por defecto de Apache), crea el archivo config.local.php\n" .
        "     junto a este config.php, con este contenido:\n" .
        "       <?php\n" .
        "       putenv('SAKONETA_PRIVATE_DIR=/var/lib/sakoneta');\n" .
        "     Ese archivo nunca se sube a git (está en .gitignore), así que sobrevive a\n" .
        "     futuras actualizaciones del código.\n\n" .
        "En ambos casos, esa carpeta debe existir (o poder crearse) y ser escribible por " .
        "el usuario con el que corre PHP (normalmente www-data). Después, recarga Apache/PHP-FPM."
    );
}

define('CARPETA_PRIVADA', $carpetaPrivada);
define('DB_PATH', CARPETA_PRIVADA . '/club.sqlite');

// Migración automática, una sola vez: si ya había una base de datos
// en la ubicación antigua (dentro de la carpeta pública) y todavía no
// existe ninguna en la nueva ubicación privada, se traslada sola.
// No hace falta mover nada a mano por SSH — PERO se comprueba de
// verdad que la copia antigua ha desaparecido. Si por lo que sea
// (permisos del sistema de archivos) no se puede mover ni eliminar,
// el sitio se detiene en vez de seguir funcionando con una copia de
// la base de datos (con usuarios, hashes y datos privados) dentro de
// la carpeta pública, donde un servidor mal configurado podría
// llegar a servirla directamente.
$dbAntigua = __DIR__ . '/data/club.sqlite';
if ($dbAntigua !== DB_PATH && is_file($dbAntigua)) {
    if (!is_file(DB_PATH)) {
        @rename($dbAntigua, DB_PATH);
        clearstatcache(true, $dbAntigua);
        clearstatcache(true, DB_PATH);
    }
    if (is_file($dbAntigua)) {
        http_response_code(500);
        error_log('Sakoneta: no se ha podido eliminar/mover la base de datos antigua de ' . $dbAntigua . ' — sigue dentro de la carpeta pública del sitio.');
        die(
            "Hay una copia de la base de datos dentro de la carpeta pública del sitio\n" .
            "($dbAntigua) que no se ha podido trasladar ni eliminar automáticamente\n" .
            "(seguramente por permisos de archivo).\n\n" .
            "Por seguridad, el sitio no continúa mientras esa copia siga ahí: contiene\n" .
            "usuarios, contraseñas y datos privados, y si el servidor no protegiera bien\n" .
            "esa carpeta (por ejemplo, con Nginx sin la regla adecuada), podría llegar a\n" .
            "descargarse directamente.\n\n" .
            "Soluciónalo por SSH con uno de estos dos pasos, y recarga la página:\n\n" .
            "  Si " . DB_PATH . " ya existe y es la copia buena y actualizada:\n" .
            "    rm " . $dbAntigua . "\n\n" .
            "  Si no, da permisos de escritura a PHP sobre esa carpeta y vuelve a\n" .
            "  intentarlo (recargando la página, no hace falta reiniciar nada):\n" .
            "    sudo chown www-data:www-data " . dirname($dbAntigua) . " " . $dbAntigua
        );
    }
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
