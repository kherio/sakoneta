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
$competicionId = (int)($_POST['competicion_id'] ?? 0);

if ($competicionId) {
    $pdo->prepare('DELETE FROM competicion_minutaje WHERE competicion_id = ?')->execute([$competicionId]);
    unset($_SESSION['minutaje_borrador'][$competicionId]);
}

header('Location: competicion_form.php?id=' . $competicionId . '&ok=1', true, 303);
exit;
