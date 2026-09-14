<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('SELECT foto FROM gimnastas WHERE id = ?');
    $stmt->execute([$id]);
    $ruta = $stmt->fetchColumn();

    $pdo->prepare('UPDATE gimnastas SET foto = NULL WHERE id = ?')->execute([$id]);
    if ($ruta) eliminarArchivoSiNoSeUsa($pdo, $ruta);
}

header('Location: gimnasta_form.php?id=' . $id);
exit;
