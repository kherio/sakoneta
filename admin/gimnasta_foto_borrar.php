<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gimnastas.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
$gimnastaId = 0;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM gimnasta_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();
    if ($foto) {
        $gimnastaId = (int)$foto['gimnasta_id'];
        borrarFotoDeEntidad($pdo, 'gimnastas', 'gimnasta_id', 'foto', 'gimnasta_fotos', $id);
    }
}

header('Location: gimnasta_form.php?id=' . $gimnastaId . '&ok=1');
exit;
