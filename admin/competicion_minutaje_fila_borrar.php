<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: competiciones.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$filaId = (int)($_POST['fila_id'] ?? 0);
$competicionId = 0;

if ($filaId) {
    $stmt = $pdo->prepare('SELECT competicion_id FROM competicion_minutaje WHERE id = ?');
    $stmt->execute([$filaId]);
    $competicionId = (int)$stmt->fetchColumn();
    if ($competicionId) {
        $pdo->prepare('DELETE FROM competicion_minutaje WHERE id = ?')->execute([$filaId]);
    }
}

header('Location: competicion_form.php?id=' . $competicionId . '&ok=1', true, 303);
exit;
