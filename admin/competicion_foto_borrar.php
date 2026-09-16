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
$id = (int)($_POST['id'] ?? 0);
$competicionId = 0;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM competicion_fotos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();
    if ($foto) {
        $competicionId = (int)$foto['competicion_id'];
        borrarFotoDeEntidad($pdo, 'competiciones', 'competicion_id', 'imagen_portada', 'competicion_fotos', $id);
    }
}

header('Location: competicion_form.php?id=' . $competicionId . '&ok=1');
exit;
