<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categorias.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
$categoriaId = 0;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM categoria_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();
    if ($foto) {
        $categoriaId = (int)$foto['categoria_id'];
        limpiarReferenciasArchivo($pdo, $foto['archivo']);
    }
}

header('Location: categoria_form.php?id=' . $categoriaId . '&ok=1');
exit;
