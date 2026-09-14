<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare('SELECT logo FROM patrocinadores WHERE id = ?');
    $stmt->execute([$id]);
    $logo = $stmt->fetchColumn();

    $pdo->prepare('DELETE FROM patrocinadores WHERE id = ?')->execute([$id]);
    if ($logo) eliminarArchivoSiNoSeUsa($pdo, $logo);
}

header('Location: patrocinadores.php?ok=1');
exit;
