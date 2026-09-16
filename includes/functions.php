<?php

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatearFecha(?string $fecha): string {
    if (!$fecha) return '';
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($fecha);
    return (int)date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

/**
 * Añade "?v=fecha_de_modificación" a la URL de un archivo estático
 * (CSS, JS...) para que el navegador descargue la versión nueva justo
 * después de cada actualización del sitio, en vez de quedarse con una
 * copia antigua guardada en caché.
 *
 * $hrefRelativo es lo que se pone en el href/src tal cual (relativo a
 * la página actual). $rutaFisica es dónde está el archivo de verdad
 * en el disco, relativa a la raíz del proyecto; si no se indica, se
 * asume que coincide con $hrefRelativo (válido en las páginas
 * públicas, no en las del panel, donde el enlace es relativo a /admin/).
 */
function versionArchivo(string $hrefRelativo, ?string $rutaFisica = null): string {
    $rutaFisica ??= $hrefRelativo;
    $rutaCompleta = __DIR__ . '/../' . ltrim($rutaFisica, '/');
    $version = is_file($rutaCompleta) ? filemtime($rutaCompleta) : time();
    return $hrefRelativo . '?v=' . $version;
}

function redirigir(string $ruta): void {
    header('Location: ' . $ruta);
    exit;
}

const LOGIN_MAX_INTENTOS = 6;
const LOGIN_BLOQUEO_MINUTOS = 15;

function ipVisitante(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Devuelve los minutos restantes de bloqueo para esta IP, o 0 si puede
 * intentar iniciar sesión.
 */
function minutosBloqueoRestantes(PDO $pdo): int {
    $stmt = $pdo->prepare('SELECT bloqueado_hasta FROM intentos_login WHERE ip = ?');
    $stmt->execute([ipVisitante()]);
    $hasta = $stmt->fetchColumn();
    if (!$hasta) return 0;
    $restante = strtotime($hasta) - time();
    return $restante > 0 ? (int)ceil($restante / 60) : 0;
}

/**
 * Registra un intento de login fallido para la IP actual. Si se supera
 * el máximo de intentos, bloquea esa IP durante un tiempo.
 */
function registrarIntentoFallido(PDO $pdo): void {
    $ip = ipVisitante();
    $stmt = $pdo->prepare('SELECT intentos FROM intentos_login WHERE ip = ?');
    $stmt->execute([$ip]);
    $intentos = (int)$stmt->fetchColumn() + 1;

    $bloqueadoHasta = null;
    if ($intentos >= LOGIN_MAX_INTENTOS) {
        $bloqueadoHasta = date('Y-m-d H:i:s', time() + LOGIN_BLOQUEO_MINUTOS * 60);
    }

    $pdo->prepare('INSERT INTO intentos_login (ip, intentos, ultimo_intento, bloqueado_hasta) VALUES (?, ?, ?, ?)
                    ON CONFLICT(ip) DO UPDATE SET intentos = excluded.intentos, ultimo_intento = excluded.ultimo_intento, bloqueado_hasta = excluded.bloqueado_hasta')
        ->execute([$ip, $intentos, date('Y-m-d H:i:s'), $bloqueadoHasta]);
}

/**
 * Se llama tras un login correcto para olvidar los intentos fallidos
 * previos de esta IP.
 */
function resetearIntentosLogin(PDO $pdo): void {
    $pdo->prepare('DELETE FROM intentos_login WHERE ip = ?')->execute([ipVisitante()]);
}

/**
 * Crea las tablas si no existen y añade cualquier columna nueva que
 * falte (migraciones). Se llama automáticamente en cada petición
 * desde getDb(), así que la base de datos se pone al día sola en
 * cuanto se sube código nuevo, sin depender de que alguien recuerde
 * ejecutar init_db.php a mano.
 */
// Se incrementa cada vez que se añade una tabla o columna nueva al
// esquema. Gracias a esto, ejecutarMigracionesEsquema() solo hace el
// trabajo de verdad (CREATE TABLE / ALTER TABLE) la primera vez que
// se ejecuta con código nuevo, y no en cada petición: en el caso
// normal, se limita a una única consulta muy barata (PRAGMA
// user_version) y sale enseguida.
const VERSION_ESQUEMA_SAKONETA = 4;

function ejecutarMigracionesEsquema(PDO $pdo): void {
    $versionActual = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    if ($versionActual >= VERSION_ESQUEMA_SAKONETA) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS noticias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        resumen TEXT NOT NULL,
        contenido TEXT NOT NULL,
        imagen TEXT,
        fecha TEXT NOT NULL,
        publicado INTEGER NOT NULL DEFAULT 1,
        likes INTEGER NOT NULL DEFAULT 0,
        imagen_posicion TEXT NOT NULL DEFAULT 'arriba'
    )");
    agregarColumnaSiFalta($pdo, 'noticias', 'likes', 'INTEGER NOT NULL DEFAULT 0');
    agregarColumnaSiFalta($pdo, 'noticias', 'imagen_posicion', "TEXT NOT NULL DEFAULT 'arriba'");
    agregarColumnaSiFalta($pdo, 'noticias', 'autor_id', 'INTEGER');

    $pdo->exec("CREATE TABLE IF NOT EXISTS gimnastas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        categoria TEXT NOT NULL,
        modalidad TEXT NOT NULL,
        aparato TEXT,
        foto TEXT,
        orden INTEGER DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL UNIQUE,
        orden INTEGER DEFAULT 0,
        imagen_portada TEXT
    )");
    agregarColumnaSiFalta($pdo, 'categorias', 'imagen_portada', 'TEXT');

    $pdo->exec("CREATE TABLE IF NOT EXISTS competiciones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        categoria TEXT NOT NULL,
        lugar TEXT NOT NULL,
        fecha TEXT NOT NULL,
        resultado TEXT,
        disputada INTEGER NOT NULL DEFAULT 0,
        imagen_portada TEXT,
        descripcion TEXT
    )");
    agregarColumnaSiFalta($pdo, 'competiciones', 'imagen_portada', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'descripcion', 'TEXT');
    agregarColumnaSiFalta($pdo, 'competiciones', 'imagen_posicion', "TEXT NOT NULL DEFAULT 'arriba'");
    agregarColumnaSiFalta($pdo, 'competiciones', 'hora', 'TEXT');

    $pdo->exec("CREATE TABLE IF NOT EXISTS competicion_categorias (
        competicion_id INTEGER NOT NULL,
        categoria TEXT NOT NULL,
        PRIMARY KEY (competicion_id, categoria)
    )");
    // Migración: cada competición que ya tuviera una categoría en la
    // columna antigua pasa a tener también esa misma categoría aquí,
    // sin perder nada. A partir de ahora una competición puede tener
    // varias a la vez.
    $pdo->exec("INSERT OR IGNORE INTO competicion_categorias (competicion_id, categoria)
                SELECT id, categoria FROM competiciones WHERE categoria IS NOT NULL AND categoria != ''");

    foreach (['competicion_fotos' => 'competicion_id', 'noticia_fotos' => 'noticia_id', 'gimnasta_fotos' => 'gimnasta_id', 'categoria_fotos' => 'categoria_id'] as $tabla => $columnaId) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS $tabla (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            $columnaId INTEGER NOT NULL,
            archivo TEXT NOT NULL,
            orden INTEGER DEFAULT 0,
            tipo TEXT NOT NULL DEFAULT 'imagen'
        )");
        agregarColumnaSiFalta($pdo, $tabla, 'tipo', "TEXT NOT NULL DEFAULT 'imagen'");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS ajustes (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        splash_activo INTEGER NOT NULL DEFAULT 0,
        splash_imagen TEXT,
        inicio_imagen TEXT,
        inicio_imagen_titulo TEXT
    )");
    $pdo->exec("INSERT OR IGNORE INTO ajustes (id, splash_activo, splash_imagen, inicio_imagen, inicio_imagen_titulo)
                VALUES (1, 0, NULL, NULL, NULL)");
    foreach (['sobre_historia', 'sobre_palmares', 'hero_kicker', 'hero_titulo', 'hero_texto', 'nombre_sitio', 'eslogan_sitio', 'admin_password_hash', 'pie_titulo', 'pie_texto', 'hero_titulo_tamano'] as $columnaAjuste) {
        agregarColumnaSiFalta($pdo, 'ajustes', $columnaAjuste, 'TEXT');
    }
    foreach ([1, 2, 3, 4] as $n) {
        agregarColumnaSiFalta($pdo, 'ajustes', "est{$n}_valor", 'INTEGER');
        agregarColumnaSiFalta($pdo, 'ajustes', "est{$n}_texto", 'TEXT');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS mensajes_contacto (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        email TEXT NOT NULL,
        mensaje TEXT NOT NULL,
        fecha TEXT NOT NULL,
        leido INTEGER NOT NULL DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS patrocinadores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        logo TEXT,
        url TEXT,
        orden INTEGER DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS suscriptores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        fecha TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS intentos_login (
        ip TEXT PRIMARY KEY,
        intentos INTEGER NOT NULL DEFAULT 0,
        ultimo_intento TEXT,
        bloqueado_hasta TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS limite_envios (
        ip TEXT NOT NULL,
        tipo TEXT NOT NULL,
        ventana_inicio TEXT NOT NULL,
        intentos INTEGER NOT NULL DEFAULT 1,
        PRIMARY KEY (ip, tipo)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comentarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        noticia_id INTEGER NOT NULL,
        nombre TEXT NOT NULL,
        email TEXT,
        mensaje TEXT NOT NULL,
        fecha TEXT NOT NULL,
        estado TEXT NOT NULL DEFAULT 'pendiente'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario TEXT NOT NULL UNIQUE,
        nombre TEXT NOT NULL,
        password_hash TEXT NOT NULL,
        rol TEXT NOT NULL DEFAULT 'colaborador',
        activo INTEGER NOT NULL DEFAULT 1,
        creado TEXT NOT NULL,
        session_version INTEGER NOT NULL DEFAULT 1
    )");
    agregarColumnaSiFalta($pdo, 'usuarios', 'session_version', 'INTEGER NOT NULL DEFAULT 1');

    // Igual que las tablas y columnas, el primer usuario administrador
    // también se crea solo, sin depender de que alguien ejecute
    // init_db.php a mano tras esta actualización. Se respeta la
    // contraseña que ya hubiera (cambiada desde el panel de Ajustes en
    // versiones anteriores); si no había ninguna, se genera una nueva
    // al azar en este mismo momento — nunca se usa un hash fijo
    // guardado en el código fuente, porque un repositorio puede llegar
    // a ser público y ese hash quedaría ahí para siempre.
    $numUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if ($numUsuarios === 0) {
        $columnasAjustes = $pdo->query('PRAGMA table_info(ajustes)')->fetchAll();
        $hashPrevio = null;
        if (in_array('admin_password_hash', array_column($columnasAjustes, 'name'), true)) {
            $hashPrevio = $pdo->query('SELECT admin_password_hash FROM ajustes WHERE id = 1')->fetchColumn();
        }

        if ($hashPrevio) {
            $hashInicial = $hashPrevio;
        } else {
            $claveGenerada = bin2hex(random_bytes(9)); // 18 caracteres hexadecimales
            $hashInicial = password_hash($claveGenerada, PASSWORD_DEFAULT);
            $rutaAviso = CARPETA_PRIVADA . '/contrasena-inicial-admin.txt';
            @file_put_contents($rutaAviso, "Usuario: " . ADMIN_USER . "\nContraseña inicial: $claveGenerada\n\n" .
                "Este archivo se generó automáticamente porque no había ninguna contraseña de\n" .
                "administrador guardada todavía. Entra con estos datos y cámbiala cuanto antes\n" .
                "desde \"Cambiar contraseña\" en el panel. Después, borra este archivo del\n" .
                "servidor (ya está fuera de la carpeta pública del sitio, pero no hace falta\n" .
                "dejarlo ahí): rm " . $rutaAviso . "\n");
            error_log('Sakoneta: se ha generado una contraseña de administrador inicial. Consulta ' . $rutaAviso . ' en el servidor.');
        }

        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, nombre, password_hash, rol, activo, creado, session_version) VALUES (?,?,?,?,1,?,1)');
        $stmt->execute([ADMIN_USER, 'Administrador', $hashInicial, 'administrador', date('Y-m-d H:i:s')]);
    }

    $pdo->exec('PRAGMA user_version = ' . VERSION_ESQUEMA_SAKONETA);
}

/**
 * Añade una columna a una tabla si todavía no existe. Usado por
 * ejecutarMigracionesEsquema() para no repetir la misma comprobación
 * una y otra vez.
 */
function agregarColumnaSiFalta(PDO $pdo, string $tabla, string $columna, string $definicionSql): void {
    $columnas = $pdo->query("PRAGMA table_info($tabla)")->fetchAll();
    if (!in_array($columna, array_column($columnas, 'name'), true)) {
        try {
            $pdo->exec("ALTER TABLE $tabla ADD COLUMN $columna $definicionSql");
        } catch (PDOException $e) {
            // Si dos peticiones llegan a la vez justo después de un
            // despliegue, ambas pueden ver la columna como "no
            // existe todavía" y las dos intentar añadirla. La primera
            // gana; a la segunda solo le toca ignorar el error de
            // "ya existe" en vez de romper la petición.
            if (stripos($e->getMessage(), 'duplicate column') === false) {
                throw $e;
            }
        }
    }
}

/**
 * Recorta un texto a una longitud máxima sin depender de la extensión
 * mbstring (no siempre está instalada). Corta por espacio para no
 * partir una palabra a la mitad.
 */
function recortarTexto(string $texto, int $longitud): string {
    if (strlen($texto) <= $longitud) return $texto;
    $recortado = substr($texto, 0, $longitud);
    $ultimoEspacio = strrpos($recortado, ' ');
    if ($ultimoEspacio !== false) {
        $recortado = substr($recortado, 0, $ultimoEspacio);
    }
    return $recortado . '…';
}

/**
 * Detecta el caso en que PHP ha descartado toda la petición POST (incluidos
 * $_POST y $_FILES) por superar "post_max_size" en el servidor. En ese caso
 * $_POST y $_FILES llegan vacíos sin ningún otro aviso, así que hay que
 * comprobarlo explícitamente para poder mostrar un error claro en vez de
 * que el formulario parezca no hacer nada.
 */
function subidaDemasiadoGrande(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST)
        && empty($_FILES)
        && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

/**
 * Comprueba que un archivo temporal es realmente una imagen del tipo esperado
 * (no solo que su nombre termine en .jpg/.png/.webp). Usa getimagesize(),
 * que lee la cabecera real del archivo.
 */
function esImagenValida(string $rutaTemporal, string $extensionEsperada): bool {
    $info = @getimagesize($rutaTemporal);
    if ($info === false) return false;

    $tiposValidos = [
        'jpg' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
        'webp' => IMAGETYPE_WEBP,
    ];
    return isset($tiposValidos[$extensionEsperada]) && $info[2] === $tiposValidos[$extensionEsperada];
}

/**
 * Comprueba que un archivo temporal es realmente un vídeo del tipo esperado,
 * a partir de su tipo MIME real (no solo la extensión del nombre).
 */
function esVideoValido(string $rutaTemporal, array $mimesValidos): bool {
    if (!function_exists('finfo_open')) {
        // Sin la extensión fileinfo no podemos comprobar el contenido real;
        // en ese caso confiamos en la validación de extensión ya hecha antes.
        return true;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $rutaTemporal);
    finfo_close($finfo);
    return in_array($mime, $mimesValidos, true);
}

/**
 * Procesa varias fotos y/o vídeos subidos desde un campo
 * <input type="file" multiple>. Devuelve un array de arrays
 * ['archivo' => 'subidas/xxx.jpg', 'tipo' => 'imagen'|'video']
 * (puede estar vacío). Los archivos no válidos se ignoran salvo que
 * $errores se pase por referencia, en cuyo caso se añaden los mensajes.
 */
function procesarImagenesMultiples(string $campo, array &$errores = []): array {
    if (empty($_FILES[$campo]) || !is_array($_FILES[$campo]['name'])) {
        return [];
    }

    $extensionesImagen = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
    $extensionesVideo = ['mp4' => 'mp4', 'webm' => 'webm', 'mov' => 'mov'];
    $mimesVideo = [
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mov' => ['video/quicktime', 'video/mp4'],
    ];

    $carpetaDestino = __DIR__ . '/../img/subidas';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0775, true);
    }

    $guardados = [];
    $total = count($_FILES[$campo]['name']);

    for ($i = 0; $i < $total; $i++) {
        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_INI_SIZE || $_FILES[$campo]['error'][$i] === UPLOAD_ERR_FORM_SIZE) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" supera el límite de subida configurado en el servidor (revisa "upload_max_filesize" y "post_max_size" en PHP).';
            continue;
        }
        if ($_FILES[$campo]['error'][$i] !== UPLOAD_ERR_OK) {
            $errores[] = 'No se ha podido subir "' . $_FILES[$campo]['name'][$i] . '".';
            continue;
        }

        $extension = strtolower(pathinfo($_FILES[$campo]['name'][$i], PATHINFO_EXTENSION));
        $esImagen = isset($extensionesImagen[$extension]);
        $esVideo = isset($extensionesVideo[$extension]);

        if (!$esImagen && !$esVideo) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es un formato admitido (JPG, PNG, WEBP para fotos; MP4, WEBM o MOV para vídeo).';
            continue;
        }

        $limiteMb = $esVideo ? 80 : 20;
        if ($_FILES[$campo]['size'][$i] > $limiteMb * 1024 * 1024) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" pesa demasiado (máximo ' . $limiteMb . ' MB).';
            continue;
        }

        if ($esImagen && !esImagenValida($_FILES[$campo]['tmp_name'][$i], $extensionesImagen[$extension])) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es una imagen válida.';
            continue;
        }
        if ($esVideo && !esVideoValido($_FILES[$campo]['tmp_name'][$i], $mimesVideo[$extension])) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es un archivo de vídeo válido.';
            continue;
        }

        $extensionFinal = $esImagen ? $extensionesImagen[$extension] : $extensionesVideo[$extension];
        $nombreFinal = 'subida-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $extensionFinal;
        $rutaFinal = $carpetaDestino . '/' . $nombreFinal;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'][$i], $rutaFinal)) {
            if ($esImagen) {
                redimensionarImagenSiHaceFalta($rutaFinal, $extensionFinal);
            }
            $guardados[] = ['archivo' => 'subidas/' . $nombreFinal, 'tipo' => $esImagen ? 'imagen' : 'video'];
        } else {
            $errores[] = 'No se ha podido guardar "' . $_FILES[$campo]['name'][$i] . '".';
        }
    }

    return $guardados;
}

/**
 * Cuenta en cuántos sitios de la base de datos se usa un archivo de img/subidas/.
 */
function contarUsosArchivo(PDO $pdo, string $ruta): int {
    $total = 0;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM noticias WHERE imagen = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM noticia_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM gimnastas WHERE foto = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM gimnasta_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE imagen_portada = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM categoria_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM competiciones WHERE imagen_portada = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM competicion_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ajustes WHERE splash_imagen = ? OR inicio_imagen = ?'); $stmt->execute([$ruta, $ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM patrocinadores WHERE logo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    return $total;
}

/**
 * Devuelve una lista de textos legibles indicando dónde se usa un archivo.
 */
function descripcionUsosArchivo(PDO $pdo, string $ruta): array {
    $usos = [];

    $stmt = $pdo->prepare('SELECT titulo FROM noticias WHERE imagen = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) $usos[] = 'Noticia: ' . $t;

    $stmt = $pdo->prepare('SELECT n.titulo FROM noticia_fotos f JOIN noticias n ON n.id = f.noticia_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) $usos[] = 'Foto de noticia: ' . $t;

    $stmt = $pdo->prepare('SELECT nombre FROM gimnastas WHERE foto = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Gimnasta: ' . $n;

    $stmt = $pdo->prepare('SELECT g.nombre FROM gimnasta_fotos f JOIN gimnastas g ON g.id = f.gimnasta_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Foto de gimnasta: ' . $n;

    $stmt = $pdo->prepare('SELECT nombre FROM categorias WHERE imagen_portada = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Portada de categoría: ' . $n;

    $stmt = $pdo->prepare('SELECT c.nombre FROM categoria_fotos f JOIN categorias c ON c.id = f.categoria_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Foto de categoría: ' . $n;

    $stmt = $pdo->prepare('SELECT nombre FROM competiciones WHERE imagen_portada = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Portada de competición: ' . $n;

    $stmt = $pdo->prepare('SELECT c.nombre FROM competicion_fotos f JOIN competiciones c ON c.id = f.competicion_id WHERE f.archivo = ?');
    $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Foto de competición: ' . $n;

    $ajustes = $pdo->query('SELECT splash_imagen, inicio_imagen FROM ajustes WHERE id = 1')->fetch();
    if ($ajustes) {
        if ($ajustes['splash_imagen'] === $ruta) $usos[] = 'Pantalla de bienvenida (splash)';
        if ($ajustes['inicio_imagen'] === $ruta) $usos[] = 'Foto de portada de inicio';
    }

    $stmt = $pdo->prepare('SELECT nombre FROM patrocinadores WHERE logo = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Logo de patrocinador: ' . $n;

    return $usos;
}

/**
 * Borra físicamente un archivo de img/subidas/ solo si ya no lo usa nada en la base de datos.
 */
function eliminarArchivoSiNoSeUsa(PDO $pdo, ?string $ruta): void {
    if (!$ruta) return;
    if (contarUsosArchivo($pdo, $ruta) > 0) return;
    $rutaCompleta = __DIR__ . '/../img/' . $ruta;
    if (is_file($rutaCompleta)) {
        @unlink($rutaCompleta);
    }
}

/**
 * Redimensiona una imagen ya guardada si supera el ancho/alto máximo,
 * para que las fotos de móvil (a veces de más de 4000px) no viajen a
 * tamaño completo. Si la extensión GD no está disponible en el
 * servidor, no hace nada (la imagen se queda en su tamaño original).
 */
function redimensionarImagenSiHaceFalta(string $rutaCompleta, string $extension, int $maximoPx = 1600): void {
    if (!function_exists('imagecreatefromjpeg')) return;

    $cargar = ['jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'webp' => 'imagecreatefromwebp'];
    $guardar = ['jpg' => 'imagejpeg', 'png' => 'imagepng', 'webp' => 'imagewebp'];
    if (!isset($cargar[$extension]) || !function_exists($cargar[$extension])) return;

    $origen = @$cargar[$extension]($rutaCompleta);
    if (!$origen) return;

    $anchoOriginal = imagesx($origen);
    $altoOriginal = imagesy($origen);

    if (max($anchoOriginal, $altoOriginal) <= $maximoPx) {
        imagedestroy($origen);
        return;
    }

    $ratio = $maximoPx / max($anchoOriginal, $altoOriginal);
    $anchoNuevo = (int)round($anchoOriginal * $ratio);
    $altoNuevo = (int)round($altoOriginal * $ratio);

    $destino = imagecreatetruecolor($anchoNuevo, $altoNuevo);
    if ($extension === 'png' || $extension === 'webp') {
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
    }
    imagecopyresampled($destino, $origen, 0, 0, 0, 0, $anchoNuevo, $altoNuevo, $anchoOriginal, $altoOriginal);

    if ($extension === 'jpg') {
        $guardar[$extension]($destino, $rutaCompleta, 85);
    } elseif ($extension === 'webp') {
        $guardar[$extension]($destino, $rutaCompleta, 82);
    } else {
        $guardar[$extension]($destino, $rutaCompleta, 6);
    }

    imagedestroy($origen);
    imagedestroy($destino);
}

/**
 * Nombre del sitio: el que se haya guardado en Ajustes, o si no hay
 * ninguno, el nombre por defecto definido en config.php.
 */
function nombreSitio(): string {
    static $nombre = null;
    if ($nombre === null) {
        $ajustes = obtenerAjustes(getDb());
        $nombre = $ajustes['nombre_sitio'] ?: SITE_NAME;
    }
    return $nombre;
}

/**
 * Lema del sitio: el que se haya guardado en Ajustes, o si no hay
 * ninguno, el lema por defecto definido en config.php.
 */
function claimSitio(): string {
    static $claim = null;
    if ($claim === null) {
        $ajustes = obtenerAjustes(getDb());
        $claim = $ajustes['eslogan_sitio'] ?: SITE_CLAIM;
    }
    return $claim;
}

/**
 * Limpia TODAS las referencias posibles a un archivo (portada/foto
 * principal, filas de galería en cualquier entidad, ajustes,
 * patrocinadores) y borra el archivo físico al final. Es el único
 * punto donde se hace esta limpieza, para que el resultado sea
 * siempre el mismo se borre desde donde se borre (galería de una
 * noticia, biblioteca de medios, borrado en bloque...).
 */
function limpiarReferenciasArchivo(PDO $pdo, string $archivo): void {
    $entidades = [
        'noticias' => ['fotos' => 'noticia_fotos', 'columna_id' => 'noticia_id', 'columna_portada' => 'imagen'],
        'gimnastas' => ['fotos' => 'gimnasta_fotos', 'columna_id' => 'gimnasta_id', 'columna_portada' => 'foto'],
        'categorias' => ['fotos' => 'categoria_fotos', 'columna_id' => 'categoria_id', 'columna_portada' => 'imagen_portada'],
        'competiciones' => ['fotos' => 'competicion_fotos', 'columna_id' => 'competicion_id', 'columna_portada' => 'imagen_portada'],
    ];

    foreach ($entidades as $tabla => $info) {
        $columnaPortada = $info['columna_portada'];
        $tablaFotos = $info['fotos'];
        $columnaId = $info['columna_id'];

        $stmt = $pdo->prepare("SELECT id FROM $tabla WHERE $columnaPortada = ?");
        $stmt->execute([$archivo]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $pdo->prepare("DELETE FROM $tablaFotos WHERE archivo = ? AND $columnaId = ?")->execute([$archivo, $id]);
            $siguiente = $pdo->prepare("SELECT archivo FROM $tablaFotos WHERE $columnaId = ? AND tipo = 'imagen' ORDER BY orden ASC LIMIT 1");
            $siguiente->execute([$id]);
            $nuevaPortada = $siguiente->fetchColumn() ?: null;
            $pdo->prepare("UPDATE $tabla SET $columnaPortada = ? WHERE id = ?")->execute([$nuevaPortada, $id]);
        }
        // Por si el archivo era una foto de galería que no era la portada de nadie
        $pdo->prepare("DELETE FROM $tablaFotos WHERE archivo = ?")->execute([$archivo]);
    }

    $pdo->prepare('UPDATE ajustes SET splash_imagen = NULL, splash_activo = 0 WHERE splash_imagen = ?')->execute([$archivo]);
    $pdo->prepare('UPDATE ajustes SET inicio_imagen = NULL, inicio_imagen_titulo = NULL WHERE inicio_imagen = ?')->execute([$archivo]);
    $pdo->prepare('UPDATE patrocinadores SET logo = NULL WHERE logo = ?')->execute([$archivo]);

    $rutaCompleta = __DIR__ . '/../img/' . $archivo;
    if (is_file($rutaCompleta)) {
        @unlink($rutaCompleta);
    }
}

/**
 * Lista los archivos (fotos y vídeos) ya subidos a img/subidas/, del
 * más reciente al más antiguo. Se usa para poder elegir una foto ya
 * subida en vez de tener que volver a subirla.
 */
function listarMediaSubida(): array {
    $carpeta = __DIR__ . '/../img/subidas';
    if (!is_dir($carpeta)) return [];
    $archivos = glob($carpeta . '/*.{jpg,jpeg,png,webp,mp4,webm,mov}', GLOB_BRACE);
    usort($archivos, fn($a, $b) => filemtime($b) <=> filemtime($a));

    $resultado = [];
    foreach ($archivos as $rutaCompleta) {
        $relativa = 'subidas/' . basename($rutaCompleta);
        $extension = strtolower(pathinfo($relativa, PATHINFO_EXTENSION));
        $resultado[] = ['archivo' => $relativa, 'tipo' => in_array($extension, ['mp4', 'webm', 'mov'], true) ? 'video' : 'imagen'];
    }
    return $resultado;
}

/**
 * Convierte lo que haya guardado en imagen_posicion (nuevo formato
 * "X Y" en porcentajes del selector visual, o el antiguo
 * 'arriba'/'centro'/'abajo') en una pareja [x, y] de porcentajes.
 */
function posicionXY(?string $posicion): array {
    if ($posicion && preg_match('/^(\d{1,3})\s+(\d{1,3})$/', trim($posicion), $m)) {
        return [max(0, min(100, (int)$m[1])), max(0, min(100, (int)$m[2]))];
    }
    return match ($posicion) {
        'centro' => [50, 50],
        'abajo' => [50, 90],
        default => [50, 12], // 'arriba', vacío o cualquier valor no reconocido
    };
}

/**
 * Traduce el encuadre elegido para una foto de portada al valor CSS
 * background-position correspondiente.
 */
function posicionCss(?string $posicion): string {
    [$x, $y] = posicionXY($posicion);
    return "{$x}% {$y}%";
}

/**
 * Límite de envíos por IP para formularios públicos (contacto,
 * comentarios, likes...), independiente del de intentos de login.
 * Cada $tipo lleva su propia cuenta. Devuelve true si esta IP ya ha
 * superado el máximo permitido en la ventana de tiempo indicada (en
 * ese caso, el que llama debe rechazar el envío).
 */
function superaLimiteEnvios(PDO $pdo, string $tipo, int $maxIntentos, int $minutosVentana): bool {
    $ip = ipVisitante();
    $ahora = time();

    $stmt = $pdo->prepare('SELECT intentos, ventana_inicio FROM limite_envios WHERE ip = ? AND tipo = ?');
    $stmt->execute([$ip, $tipo]);
    $fila = $stmt->fetch();

    $ventanaCaducada = !$fila || strtotime($fila['ventana_inicio']) < ($ahora - $minutosVentana * 60);

    if ($ventanaCaducada) {
        $pdo->prepare('INSERT INTO limite_envios (ip, tipo, ventana_inicio, intentos) VALUES (?, ?, ?, 1)
                        ON CONFLICT(ip, tipo) DO UPDATE SET ventana_inicio = excluded.ventana_inicio, intentos = 1')
            ->execute([$ip, $tipo, date('Y-m-d H:i:s', $ahora)]);
        return false;
    }

    if ((int)$fila['intentos'] >= $maxIntentos) {
        return true;
    }

    $pdo->prepare('UPDATE limite_envios SET intentos = intentos + 1 WHERE ip = ? AND tipo = ?')->execute([$ip, $tipo]);
    return false;
}

/**
 * Borra una noticia por completo: sus fotos de galería, sus
 * comentarios y la propia noticia, todo en una transacción (o no se
 * borra nada, si algo falla a medias). Los archivos físicos solo se
 * eliminan después, y solo si ya no los usa ninguna otra entidad.
 */
function borrarNoticiaCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT imagen FROM noticias WHERE id = ?');
    $stmt->execute([$id]);
    if ($portada = $stmt->fetchColumn()) $archivos[] = $portada;

    $stmt = $pdo->prepare('SELECT archivo FROM noticia_fotos WHERE noticia_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM noticia_fotos WHERE noticia_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM comentarios WHERE noticia_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM noticias WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
}

/**
 * Borra una gimnasta por completo: sus fotos de galería y la propia
 * ficha, en una transacción. Ver borrarNoticiaCompleta().
 */
function borrarGimnastaCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT foto FROM gimnastas WHERE id = ?');
    $stmt->execute([$id]);
    if ($foto = $stmt->fetchColumn()) $archivos[] = $foto;

    $stmt = $pdo->prepare('SELECT archivo FROM gimnasta_fotos WHERE gimnasta_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM gimnasta_fotos WHERE gimnasta_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM gimnastas WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
}

/**
 * Borra una categoría por completo: sus fotos de galería y la propia
 * categoría, en una transacción. Ver borrarNoticiaCompleta(). No
 * toca a las gimnastas/competiciones que ya tuvieran asignado su
 * nombre (eso no cambia respecto a como funcionaba antes).
 */
function borrarCategoriaCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT imagen_portada FROM categorias WHERE id = ?');
    $stmt->execute([$id]);
    if ($portada = $stmt->fetchColumn()) $archivos[] = $portada;

    $stmt = $pdo->prepare('SELECT archivo FROM categoria_fotos WHERE categoria_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM categoria_fotos WHERE categoria_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
}

/**
 * Borra una competición por completo: sus categorías asignadas, sus
 * fotos de galería y la propia competición, en una transacción. Ver
 * borrarNoticiaCompleta().
 */
function borrarCompeticionCompleta(PDO $pdo, int $id): void {
    $archivos = [];
    $stmt = $pdo->prepare('SELECT imagen_portada FROM competiciones WHERE id = ?');
    $stmt->execute([$id]);
    if ($portada = $stmt->fetchColumn()) $archivos[] = $portada;

    $stmt = $pdo->prepare('SELECT archivo FROM competicion_fotos WHERE competicion_id = ?');
    $stmt->execute([$id]);
    $archivos = array_merge($archivos, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM competicion_fotos WHERE competicion_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM competicion_categorias WHERE competicion_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM competiciones WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    foreach (array_unique(array_filter($archivos)) as $archivo) {
        eliminarArchivoSiNoSeUsa($pdo, $archivo);
    }
}

function obtenerAjustes(PDO $pdo): array {
    $ajustes = $pdo->query('SELECT * FROM ajustes WHERE id = 1')->fetch();
    if (!$ajustes) {
        $pdo->exec("INSERT OR IGNORE INTO ajustes (id, splash_activo, splash_imagen, inicio_imagen, inicio_imagen_titulo) VALUES (1,0,NULL,NULL,NULL)");
        $ajustes = $pdo->query('SELECT * FROM ajustes WHERE id = 1')->fetch();
    }
    return $ajustes ?: ['splash_activo' => 0, 'splash_imagen' => null, 'inicio_imagen' => null, 'inicio_imagen_titulo' => null];
}

/**
 * Procesa una imagen subida por un formulario y la guarda en img/subidas/.
 * Devuelve el nombre de archivo generado, o null si no se subió nada válido.
 * $error se rellena con un mensaje si el archivo no es válido.
 */
function procesarImagenSubida(string $campo, ?string &$error = null): ?string {
    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $archivo = $_FILES[$campo];
    if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'La imagen supera el límite de subida configurado en el servidor (revisa "upload_max_filesize" en PHP).';
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'No se ha podido subir el archivo (código ' . $archivo['error'] . ').';
        return null;
    }
    if ($archivo['size'] > 20 * 1024 * 1024) {
        $error = 'La imagen pesa demasiado (máximo 20 MB).';
        return null;
    }
    $extensionesValidas = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
    $extensionOriginal = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!isset($extensionesValidas[$extensionOriginal])) {
        $error = 'Formato no soportado. Usa JPG, PNG o WEBP.';
        return null;
    }
    if (!esImagenValida($archivo['tmp_name'], $extensionesValidas[$extensionOriginal])) {
        $error = 'El archivo no es una imagen válida.';
        return null;
    }

    $carpetaDestino = __DIR__ . '/../img/subidas';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0775, true);
    }

    $nombreFinal = 'subida-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $extensionesValidas[$extensionOriginal];
    $rutaFinal = $carpetaDestino . '/' . $nombreFinal;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
        $error = 'No se ha podido guardar el archivo en el servidor.';
        return null;
    }
    redimensionarImagenSiHaceFalta($rutaFinal, $extensionesValidas[$extensionOriginal]);

    return 'subidas/' . $nombreFinal;
}
