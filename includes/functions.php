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
 * Procesa varias imágenes subidas desde un campo <input type="file" multiple>.
 * Devuelve un array con las rutas relativas guardadas (puede estar vacío).
 * Los archivos no válidos se ignoran silenciosamente salvo que $errores
 * se pase por referencia, en cuyo caso se añaden los mensajes de error.
 */
function procesarImagenesMultiples(string $campo, array &$errores = []): array {
    if (empty($_FILES[$campo]) || !is_array($_FILES[$campo]['name'])) {
        return [];
    }

    $extensionesValidas = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
    $carpetaDestino = __DIR__ . '/../img/subidas';
    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0775, true);
    }

    $guardadas = [];
    $total = count($_FILES[$campo]['name']);

    for ($i = 0; $i < $total; $i++) {
        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_INI_SIZE || $_FILES[$campo]['error'][$i] === UPLOAD_ERR_FORM_SIZE) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" supera el límite de subida configurado en el servidor (revisa "upload_max_filesize" en PHP).';
            continue;
        }
        if ($_FILES[$campo]['error'][$i] !== UPLOAD_ERR_OK) {
            $errores[] = 'No se ha podido subir "' . $_FILES[$campo]['name'][$i] . '".';
            continue;
        }
        if ($_FILES[$campo]['size'][$i] > 6 * 1024 * 1024) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" pesa demasiado (máximo 6 MB).';
            continue;
        }
        $extensionOriginal = strtolower(pathinfo($_FILES[$campo]['name'][$i], PATHINFO_EXTENSION));
        if (!isset($extensionesValidas[$extensionOriginal])) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es JPG, PNG o WEBP.';
            continue;
        }
        if (!esImagenValida($_FILES[$campo]['tmp_name'][$i], $extensionesValidas[$extensionOriginal])) {
            $errores[] = '"' . $_FILES[$campo]['name'][$i] . '" no es una imagen válida.';
            continue;
        }

        $nombreFinal = 'subida-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $extensionesValidas[$extensionOriginal];
        $rutaFinal = $carpetaDestino . '/' . $nombreFinal;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'][$i], $rutaFinal)) {
            $guardadas[] = 'subidas/' . $nombreFinal;
        } else {
            $errores[] = 'No se ha podido guardar "' . $_FILES[$campo]['name'][$i] . '".';
        }
    }

    return $guardadas;
}

/**
 * Cuenta en cuántos sitios de la base de datos se usa un archivo de img/subidas/.
 */
function contarUsosArchivo(PDO $pdo, string $ruta): int {
    $total = 0;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM noticias WHERE imagen = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM gimnastas WHERE foto = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM competiciones WHERE imagen_portada = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM competicion_fotos WHERE archivo = ?'); $stmt->execute([$ruta]); $total += (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ajustes WHERE splash_imagen = ? OR inicio_imagen = ?'); $stmt->execute([$ruta, $ruta]); $total += (int)$stmt->fetchColumn();
    return $total;
}

/**
 * Devuelve una lista de textos legibles indicando dónde se usa un archivo.
 */
function descripcionUsosArchivo(PDO $pdo, string $ruta): array {
    $usos = [];

    $stmt = $pdo->prepare('SELECT titulo FROM noticias WHERE imagen = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) $usos[] = 'Noticia: ' . $t;

    $stmt = $pdo->prepare('SELECT nombre FROM gimnastas WHERE foto = ?'); $stmt->execute([$ruta]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $n) $usos[] = 'Gimnasta: ' . $n;

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
    if ($archivo['size'] > 6 * 1024 * 1024) {
        $error = 'La imagen pesa demasiado (máximo 6 MB).';
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

    return 'subidas/' . $nombreFinal;
}
