<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('SELECT imagen FROM noticias WHERE id = ?');
    $stmt->execute([$id]);
    $ruta = $stmt->fetchColumn();

    $pdo->prepare('UPDATE noticias SET imagen = NULL WHERE id = ?')->execute([$id]);
    if ($ruta) eliminarArchivoSiNoSeUsa($pdo, $ruta);
}

header('Location: noticia_form.php?id=' . $id);
exit;
