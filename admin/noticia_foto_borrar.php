<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor','colaborador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: noticias.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
$noticiaId = 0;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM noticia_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();
    if ($foto) {
        $noticiaId = (int)$foto['noticia_id'];
        limpiarReferenciasArchivo($pdo, $foto['archivo']);
    }
}

header('Location: noticia_form.php?id=' . $noticiaId . '&ok=1');
exit;
