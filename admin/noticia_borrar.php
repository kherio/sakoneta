<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: noticias.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM noticias WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: noticias.php?ok=1');
exit;
