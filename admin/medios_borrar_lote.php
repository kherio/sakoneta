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
        if (!is_string($archivo) || !preg_match('#^subidas/[A-Za-z0-9_\-]+\.(jpg|jpeg|png|webp|mp4|webm|mov)$#i', $archivo)) {
            continue;
        }
        limpiarReferenciasArchivo($pdo, $archivo);
    }
}

header('Location: medios.php?ok=1');
exit;
