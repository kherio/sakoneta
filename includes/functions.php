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

function redirigir(string $ruta): void {
    header('Location: ' . $ruta);
    exit;
}

/**
 * Crea las tablas si no existen y añade cualquier columna nueva que
 * falte (migraciones). Se llama automáticamente en cada petición
 * desde getDb(), así que la base de datos se pone al día sola en
 * cuanto se sube código nuevo, sin depender de que alguien recuerde
 * ejecutar init_db.php a mano.
 */
function ejecutarMigracionesEsquema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS noticias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        resumen TEXT NOT NULL,
        contenido TEXT NOT NULL,
        imagen TEXT,
        fecha TEXT NOT NULL,
        publicado INTEGER NOT NULL DEFAULT 1
    )");

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
    foreach (['sobre_historia', 'sobre_palmares', 'hero_kicker', 'hero_titulo', 'hero_texto', 'nombre_sitio', 'eslogan_sitio'] as $columnaAjuste) {
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
}

/**
 * Añade una columna a una tabla si todavía no existe. Usado por
 * ejecutarMigracionesEsquema() para no repetir la misma comprobación
 * una y otra vez.
 */
function agregarColumnaSiFalta(PDO $pdo, string $tabla, string $columna, string $definicionSql): void {
    $columnas = $pdo->query("PRAGMA table_info($tabla)")->fetchAll();
    if (!in_array($columna, array_column($columnas, 'name'), true)) {
        $pdo->exec("ALTER TABLE $tabla ADD COLUMN $columna $definicionSql");
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
