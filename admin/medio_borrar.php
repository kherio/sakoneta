<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medios.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$archivo = $_POST['archivo'] ?? '';

// Validación estricta: solo nombres de archivo generados por el propio
// sistema dentro de img/subidas/ (evita cualquier intento de path traversal)
$esValido = is_string($archivo) && preg_match('#^subidas/[A-Za-z0-9_\-]+\.(jpg|jpeg|png|webp|mp4|webm|mov)$#i', $archivo);

if ($esValido) {
    limpiarReferenciasArchivo($pdo, $archivo);
}

header('Location: medios.php?ok=1');
exit;
