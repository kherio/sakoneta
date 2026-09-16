<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categorias.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    borrarCategoriaCompleta($pdo, $id);
}
header('Location: categorias.php?ok=1');
exit;
