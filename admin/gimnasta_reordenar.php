<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);
exigirCsrf();

header('Content-Type: application/json');

$pdo = getDb();
$ids = $_POST['ids'] ?? [];

if (!is_array($ids) || !$ids) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $pdo->prepare('UPDATE gimnastas SET orden = ? WHERE id = ?');
foreach ($ids as $posicion => $id) {
    $stmt->execute([$posicion + 1, (int)$id]);
}

echo json_encode(['ok' => true]);
