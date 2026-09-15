<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ajustes.php');
    exit;
}
exigirCsrf();

$pdo = getDb();
$campo = $_POST['campo'] ?? '';

if (in_array($campo, ['splash_imagen', 'inicio_imagen'], true)) {
    $ruta = $pdo->query("SELECT $campo FROM ajustes WHERE id = 1")->fetchColumn();

    if ($campo === 'splash_imagen') {
        $pdo->exec("UPDATE ajustes SET splash_imagen = NULL, splash_activo = 0 WHERE id = 1");
    } else {
        $pdo->exec("UPDATE ajustes SET inicio_imagen = NULL, inicio_imagen_titulo = NULL WHERE id = 1");
    }

    if ($ruta) eliminarArchivoSiNoSeUsa($pdo, $ruta);
}

header('Location: ajustes.php');
exit;
