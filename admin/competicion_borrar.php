<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: competiciones.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare('DELETE FROM competicion_categorias WHERE competicion_id = ?')->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM competiciones WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: competiciones.php?ok=1');
exit;
