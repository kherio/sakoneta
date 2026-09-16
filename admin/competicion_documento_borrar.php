<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: competiciones.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['documento_id'] ?? 0);
$competicionId = 0;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM competicion_documentos WHERE id = ?');
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if ($doc) {
        $competicionId = (int)$doc['competicion_id'];
        $pdo->prepare('DELETE FROM competicion_documentos WHERE id = ?')->execute([$id]);
        // Los documentos no se comparten nunca entre varias
        // competiciones (a diferencia de las fotos), así que el
        // archivo físico se borra directamente.
        $rutaDocumento = __DIR__ . '/../img/' . $doc['archivo'];
        if (is_file($rutaDocumento)) @unlink($rutaDocumento);
    }
}

header('Location: competicion_form.php?id=' . $competicionId . '&ok=1');
exit;
