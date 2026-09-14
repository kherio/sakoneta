<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirCsrf();

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: categorias.php?ok=1');
exit;
