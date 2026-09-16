<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: noticias.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    borrarNoticiaCompleta($pdo, $id);
}
header('Location: noticias.php?ok=1');
exit;
