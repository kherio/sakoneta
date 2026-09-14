<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$archivos = $_POST['archivos'] ?? [];

if (is_array($archivos)) {
    foreach ($archivos as $archivo) {
        // Misma validación estricta que en medio_borrar.php
        if (!is_string($archivo) || !preg_match('#^subidas/[A-Za-z0-9_\-]+\.(jpg|jpeg|png|webp|mp4|webm|mov)$#i', $archivo)) {
            continue;
        }

        $pdo->prepare('UPDATE noticias SET imagen = NULL WHERE imagen = ?')->execute([$archivo]);
        $pdo->prepare('DELETE FROM noticia_fotos WHERE archivo = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE gimnastas SET foto = NULL WHERE foto = ?')->execute([$archivo]);
        $pdo->prepare('DELETE FROM gimnasta_fotos WHERE archivo = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE categorias SET imagen_portada = NULL WHERE imagen_portada = ?')->execute([$archivo]);
        $pdo->prepare('DELETE FROM categoria_fotos WHERE archivo = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE competiciones SET imagen_portada = NULL WHERE imagen_portada = ?')->execute([$archivo]);
        $pdo->prepare('DELETE FROM competicion_fotos WHERE archivo = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE ajustes SET splash_imagen = NULL, splash_activo = 0 WHERE splash_imagen = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE ajustes SET inicio_imagen = NULL, inicio_imagen_titulo = NULL WHERE inicio_imagen = ?')->execute([$archivo]);
        $pdo->prepare('UPDATE patrocinadores SET logo = NULL WHERE logo = ?')->execute([$archivo]);

        $rutaCompleta = __DIR__ . '/../img/' . $archivo;
        if (is_file($rutaCompleta)) {
            @unlink($rutaCompleta);
        }
    }
}

header('Location: medios.php?ok=1');
exit;
