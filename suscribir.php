<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
exigirCsrf();

$pdo = getDb();

$email = trim($_POST['email'] ?? '');
$volver = $_POST['volver'] ?? 'index.php';

// Solo permitir volver a una ruta relativa dentro del propio sitio (evita redirecciones abiertas)
if ($volver === '' || strpos($volver, '://') !== false || strpos($volver, '//') === 0) {
    $volver = 'index.php';
}
$separador = (strpos($volver, '?') !== false) ? '&' : '?';

if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirigir($volver . $separador . 'suscrito=error');
}

if (superaLimiteEnvios($pdo, 'suscripcion', 5, 10)) {
    redirigir($volver . $separador . 'suscrito=error');
}

try {
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO suscriptores (email, fecha) VALUES (?, ?)');
    $stmt->execute([$email, date('Y-m-d H:i:s')]);
} catch (PDOException $e) {
    // Si algo falla, no rompemos la navegación del visitante
}

redirigir($volver . $separador . 'suscrito=1');
