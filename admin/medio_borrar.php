<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$archivo = $_GET['archivo'] ?? '';

// Validación estricta: solo nombres de archivo generados por el propio
// sistema dentro de img/subidas/ (evita cualquier intento de path traversal)
$esValido = is_string($archivo) && preg_match('#^subidas/[A-Za-z0-9_\-]+\.(jpg|jpeg|png|webp)$#', $archivo);

if ($esValido) {
    // Quitar la referencia de cualquier tabla que la use
    $pdo->prepare('UPDATE noticias SET imagen = NULL WHERE imagen = ?')->execute([$archivo]);
    $pdo->prepare('UPDATE gimnastas SET foto = NULL WHERE foto = ?')->execute([$archivo]);
    $pdo->prepare('UPDATE competiciones SET imagen_portada = NULL WHERE imagen_portada = ?')->execute([$archivo]);
    $pdo->prepare('DELETE FROM competicion_fotos WHERE archivo = ?')->execute([$archivo]);
    $pdo->prepare('UPDATE ajustes SET splash_imagen = NULL, splash_activo = 0 WHERE splash_imagen = ?')->execute([$archivo]);
    $pdo->prepare('UPDATE ajustes SET inicio_imagen = NULL, inicio_imagen_titulo = NULL WHERE inicio_imagen = ?')->execute([$archivo]);

    $rutaCompleta = __DIR__ . '/../img/' . $archivo;
    if (is_file($rutaCompleta)) {
        @unlink($rutaCompleta);
    }
}

header('Location: medios.php?ok=1');
exit;
